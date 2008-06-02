/* ================================================================== *\
   (C) Copyright 2005 by Secure Data Software, Inc.
   This file is part of Andromeda
   
   Andromeda is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   Andromeda is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with Andromeda; if not, write to the Free Software
   Foundation, Inc., 51 Franklin St, Fifth Floor,
   Boston, MA  02110-1301  USA 
   or visit http://www.gnu.org/licenses/gpl.html
\* ================================================================== */

/* =====================================================
 * Top level context controller and utility provider
 *
 * confirmed final form KFD 5/10/08
 * =====================================================
 */
var x4 =  {
    // Simple setting for behavior
    fadeSpeed: 'medium',
    flagDebug: true,
    
    /*
     * Code entry point: the server returns a page of HTML that
     * invokes this function, so browsertab execution
     * begins here.
     *
     */
    main: function() {
        x4.debug(" -- Beginning Initialization -- ");
        var tsBeg = new Date();
        x4.debug(" -- "+tsBeg);
        // Capture the data dictionary
        x4dd.dd = $a.data.dd;
        
        // Do things to the document
        this.mainDocument();
        
        // Find the top object, and begin recursing it
        window.rootObj = $a.byId('x4Top');
        this.initRecurse(window.rootObj);
        
        // The top object is a container only.  The activation
        // system begins with the first child we can find.
        x4.debug(" -- Ending Initialization,going to activate -- ");
        var tsEnd = new Date();
        x4.debug(" -- "+tsEnd);
        x4.debug(" -- " + ((tsEnd.getTime() - tsBeg.getTime())/1000));
        if($(window.rootObj).children('.x4Pane,.x4Div').length > 0) {
            $(window.rootObj).children('.x4Pane,.x4Div')[0].activate();
        }
    },
    
    /*
     * Create default handlers at document level
     *
     */
    mainDocument: function() { 
        document.x4RegkeyPress= [ ];
        document.x4UnRegister = function(eName) {
            var prop = 'x4Reg'+eName;
            if( this[prop].length > 0) this[prop].pop();
            x4.debug(
                "Have popped one entry from "+prop
                +" Current stack is "+this[prop]
            );
        }
        document.x4Register = function(eName,obj) {
            x4.debug("Registering "+eName+" for "+obj.jqId);
            if(eName == 'keyPress') {
                document.x4RegkeyPress[document.x4RegkeyPress.length] = obj;
                x4.debug("current stack is now "+document.x4RegkeyPress);
                if(document.x4RegkeyPress.length==1) {
                    $(document).keypress(function(e) {
                        var suffix= $a.label(e);
                        var label = 'keyPress_'+suffix;
                        x4.debug("document received "+label);
                        for(var x = 0; x<this.x4RegkeyPress.length; x++ ) {
                            var obj = this.x4RegkeyPress[x];
                            if(typeof(obj[label])=='function') {
                                x4.debug("dispatching "+label+" to "+obj.jqId);
                                return obj[label]();
                            }
                            else if(typeof(obj.keyPress)=='function') {
                                x4.debug("dispatching "
                                    +suffix+" to keyPress() of  "+obj.jqId
                                );
                                if(! obj.keyPress(suffix)) {
                                    x4.debug("document keypress done");
                                    return false;
                                }
                            }
                            else {
                                x4.debug("object "+obj.jqId+" has neither "
                                    +" keyPress_"+label+"() or keyPress()");
                            }
                        }
                        x4.debug("document keyPress found nobody to "
                            +" handle "+label);
                        return true;
                    });
                }
            }
            else {
                $a.dialogs.alert("Bad x4Register attempt: " + eName);
            }
        }        
    },
    
    /*
     * Return function attempts to go back to menu
     *
     */
    returnToMenu: function(focus) {
        if($a.aProp($a.data,'return','')=='') {
            window.location="index.php";
        }
        else if($a.aProp($a.data,'return','')=='exit') {
            window.close();
        }
        else {
            getString = '?x4Page=menu';
            if(focus==null && $("#x4Page").length > 0 ) {
                focus = $("#x4Page")[0].value;
            }
            if(focus!=null) {
                getString += '&x4Focus='+focus
            }
            window.location=getString;
        }
    },
    

    /*
     * x4.initRecurse
     *
     * Makes two significant actions.
     *
     * First, it puts activate methods onto all inputs and
     * hyperlinks.
     *
     * Second, it recurses child .x4Pane divs to look
     * for special-purpose constructors.
     *
     */
    initRecurse: function(obj) {
        $(obj).children(".x4Div,.x4Pane").each( function() {
            /*
             * Create default event handler that attempts to
             * pass the buck up the chain.  Individual
             * controller constructors will override this.
             *
             */
            // -- confirmed 5/9/08 -- //
            this.sendUp = function(name,value) {
                if(x4.parent(this)) {
                    return x4.parent(this).sendUp(name,value);
                }
                return false;
            }
            
            /*
             * Return variables
             *
             */
            this.pullDownVars = [ ];
            this.pullDown = function(name) {
                // If variable has been listed as one that is returned,
                // return it now.
                if(this.pullDownVars.indexOf(name) >= 0) {
                    x4.debug(this.id+" is returning "+name+": "+this[name]);
                    return this[name];
                }
                else {
                    if(x4.parent(this)) {
                        x4.debug(this.id+" requesting "+name+" from parent");
                        return x4.parent(this).pullDown(name);
                    }
                    return false;
                }
            }
            
            /*
             * A default deactivate action, call the parent
             * and tell it which of its children is deactivating
             *
             */
            // -- confirmed 5/9/08 -- //
            this.deactivate = function(child) {
                x4.debug("Generic deactivate terminating in: "+this.jqId);
            }     
            this.activate = function(child) {
                x4.debug("Generic activate terminating in: "+this.jqId);
            }     
                
            /* Look for all controller constructors based on classes */
            // -- confirmed 5/9/08 -- //
            this.zParent      = x4.parent(this);
            this.zLastFocusId = false;
            this.jqId         = "var x = $a.byId('"+this.id+"')";
            var classes = this.className.split(' ');
            for(var x in classes) {
                var oneClass = classes[x];
                if(oneClass=='x4Pane') continue;
                var constructor = false;
                // This attempts to find a constructor
                try {
                    constructor = eval(oneClass);
                }
                catch(e) { 
                    // do nothing on error 
                }
                if(constructor) {
                    this.zType = oneClass; 
                    constructor(this);
                }
            }
           
            /* And of course, do the sub-panes as well */
            x4.initRecurse(this);
        });
    },
    
    /*
     * The "stdlib" object provides commonly used code snippets.
     * In an ideal world, these snippets would be assigned as anonymous
     * functions during page initialization, but we have a general speed
     * issue with init, and anonymous code assignments to inputs was the
     * biggest problem.  Therefore we have the PHP input generator
     * assign "onkeypress" and other properties that point to this 
     * standard library of handlers.  So we trade off some unpleasant 
     * code coupling for snappy performance.
     *
     */
    stdlib: {
        inputKeyPress: function(e,self) {
            var prop = false;
            var keyLabel = $a.label(e);
            x4.debug("stdlib.inputKeyPress received: "+keyLabel);
            if(keyLabel=='Tab' && self.getAttribute('xNoTab')!='Y') {
                prop = 'xTabNext';
            }
            if(keyLabel=='Enter' && self.getAttribute('xNoEnter')!='Y') {
                prop = 'xTabNext';
            }
            if(keyLabel=='ShiftTab' && self.getAttribute('xNoTab')!='Y') {
                prop = 'xTabPrev'
            }
            if(keyLabel=='ShiftEnter' && self.getAttribute('xNoEnter')!='Y') {
                prop = 'xTabPrev'
            }
            if(prop) {
                x4.debug('Having decided to do: '+prop);
                var nextId = $(self).attr(prop);
                while(true) {
                    // If we end up looping around, stop
                    if(nextId == self.Id) break;
                    
                    // If the item is not read-only, stop
                    if(!$a.byId(nextId).readOnly) {
                        break;
                    }
                    
                    // ...otherwise, move on and try again
                    var nextId = $('#'+nextId).attr(prop);
                }
                if(nextId != self.id) {
                    $(self).blur();
                    $('#'+nextId).focus();
                }
                e.stopPropagation();
                return false;
            }
            //if(keyLabel == 'PageUp' || keyLabel=='PageDown') {
            //    return false;
            //}
        },
        
        inputKeyUpDate: function(e,obj) {
            var objval = obj.value;
            if(objval.length==6) {
                if(objval.indexOf('/')==-1) {
                    if(objval.indexOf('-')==-1) {
                        var month=objval.substr(0,2);
                        var day  =objval.substr(2,2);
                        var year =objval.substr(4,2);
                        yearint = parseInt(year);
                        if(yearint < 30) {
                            year = '20'+year
                        }
                        else {
                            year = '19'+year
                        }
                        obj.value=month+'/'+day+'/'+year;
                    }
                }
            }
        },
        
        findFocus: function(obj) {
            // see if there is a last focus.  If it is read only,
            // clear it.
            if(obj.zLastFocusId) {
                if( $a.byId(obj.zLastFocusId).readOnly) {
                    x4.debug("last focus was read only, clearing");
                    obj.zLastFocusId=false;
                }
            }
            
            // Now either pick the last focused item, or find the
            // first that is not read only
            if(obj.zLastFocusId) {
                $('#'+obj.zLastFocusId).focus();
            }
            else {
                $(obj).find(":input:not([@readonly]):first").focus();
            }
        }

    },
    
    /* UUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUU
     * 
     * Utilties
     *
     * UUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUU
     */

    /* 
     * initialize a json request that uses x4Page and
     * all inputs on object
     *
     */
    initPost: function(obj) {
        $a.json.init('x4Page', $a.byId('x4Page').value);
        $a.json.inputs();
    },

    /*
     * Find an objects parent.  Shortcut.
     *
     */
    parent: function(self) {
        var x = $(self).attr('xParentId');
        if(x == 'undefined') {
            return { };
        }
        else {
            return $a.byId( x );
        }
    },
    
    /*
     *  Safe log function.  Won't fire if console does not exist
     *
     */
    log: function(msg) {
        if(typeof(console)!='undefined') {
            console.log(msg);
        }
    },
    debugIndent: '',
    debug: function(msg,indent) {
        if(indent==0) { 
            var x = this.debugIndent;
            this.debugIndent = x.slice(0,x.length-2);
        }
        if(this.flagDebug) {
            if(typeof(console)!='undefined') {
                console.log(this.debugIndent + msg);
            }
        }
        if(indent==1) { this.debugIndent+='  '; }
    },
    debugOpen: function(type,jqId) {
        this.debug(type+": "+jqId,1);
    },
    debugClose: function(type,jqId) {
        this.debug(type+": "+jqId+" (COMPLETE)",0);
    },
}

/* =====================================================
 * Data dictionary static object
 *
 * The x4dd contains the data dictionary of the current
 * table.  It is normally populated by the x4Browse
 * init routine, which pulls it from the json data and
 * puts it here.
 *
 * Confirmed Final Form KFD 4/10/08
 * =====================================================            
 */
var x4dd = {
    dd: { },
    
    firstPkColumn: function(table) {
        var list = this.dd[table].pks.split(',');
        return list.pop();
    },
    pkColumnCount: function(table) {
        return this.dd[table].pks.split(',').length;
    }
}

/* ========================================================
 * Controller Constructor Funtion For x4Window
 *
 * A window object contains a menu bar and (assuming) at
 * least one x4TableTop
 *
 * Confirmed in final form: 5/10/08 KFD
 * ========================================================
 */
function x4Window(self) {
    self.menuBar = $(self).find(".x4MenuBar")[0];
    
    // Set up the H1 to handle requests
    var h1 = $a.byId('x4H1Top');
    h1.zStem = '';
    h1.saveStem = function() {
        this.zStem = this.innerHTML;
        x4.debug("Saving h1 stem: "+this.zStem);
    }
    h1.clearStem = function() {
        this.innerHTML = this.zStem;
        this.zStem = '';
        x4.debug("Clearing h1 stem");
    }
    h1.setHTML = function(text) {
        if(h1.zStem == '') {
            this.innerHTML = text;
        }
        else {
            this.innerHTML = this.zStem + ", " + text;
        }
        x4.debug("Changing h1 to "+this.innerHTML); 
    }

    /* @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
     * 
     * Activation Code
     *
     * @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
     */
    self.activate = function() {
        x4.debugOpen('ACTIVATE ',this.jqId);
        // Tell menu bar to look alive
        this.menuBar.activate();

        // Initial make visible     
        var parm1 =$a.aProp($a.data,'init',null);
        $(this).children(".x4Pane")[0].makeVisible(parm1);
        $(this).fadeIn(x4.fadeSpeed,function() {
            $(this).children(".x4Pane")[0].activate(parm1);
        });
        x4.debugClose('ACTIVATE ',this.jqId);
    }
    
    /* \/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/
     * 
     * UP-down code
     *
     * /\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\
     */
    self.sendUp = function(name,value) {
        if(name=='rowInfo') {
            $a.byId('x4RowInfoText').innerHTML = value;
            return this.id;
        }
        else if(name=='Esc') {
            x4.debug("Top level window returning to menu");
            x4.returnToMenu();
        }
        else if(name=='menuBarDisable') {
            if(value=='') {
                x4.debug(this.jqId+" is enabling all menu bar buttons");
                $(this.menuBar).find('a').removeClass('disabled');
            }
            else {
                var arr = value.split(',');
                x4.debug(this.jqId+" disabling menu bar: "+arr);
                for(var x in arr) {
                    var label = arr[x];
                    $(this.menuBar).find('a[xAction='+label+']')
                        .addClass('disabled');
                }
            }
        }
        else if(name=='menuBarLabel') {
            $(this.menuBar).find('a[xAction=newRow]').html(
                '<u>A</u>dd '+value
            );
        }
        else if(name!='menuBar') {
            return false;
        }
        else {
            x4.debug("menu bar now sending events to "+value.jqId);
            this.menuBar.obj = value;  
            
            // Now do a really neat trick, look at each button
            // and have it enable or disable itself if the
            // object has a handler
            $(this.menuBar).find("a").each(function() {
                var act = $(this).attr('xAction');
                var obj = x4.parent(this).obj;
                var mth = 'keyPress_'+act;
                if( typeof(obj[mth]) == 'undefined' ) {
                    $(this).addClass("disabled");
                }
                else {
                    $(this).removeClass("disabled");
                }
            });
            return this.id;
        }
    }    
}

/* ========================================================
 *
 * Controller Constructor for a menu bar
 *
 * Confirmed in final form: 5/10/08 KFD
 * ========================================================
 */
function x4MenuBar(self) {
    self.obj = false;
    
    /* @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
     * 
     * Activation Code
     *
     * @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
     */
    self.activate = function() {
        x4.debugOpen("ACTIVATE ",this.jqId);
        document.x4Register('keyPress',this);
        x4.debugClose("ACTIVATE ",this.jqId);
    }
       
    /* <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
     * 
     * Local logic
     *
     * <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
     */
    self.keyPress = function(type) {
        x4.debug("Menubar is in keyPress method for "+type);
        // Refuse to do it if disabled
        if( $(this).find('a[xAction='+type+'].disabled').length>0) {
            x4.debug(" ! button is disabled, refusing to handle it");
            return false;
        }
        if( $(this).find('a[xLabel='+type+'].disabled').length>0) {
            x4.debug(" ! button is disabled, refusing to handle it");
            return false;
        }
        
        var method = 'keyPress_' + type
        if(!this.obj) {
            x4.debug(" -> MenuBar, Event "+type+" called, but no object defined.");
            return true;
        }
        else if( typeof(this.obj[method])!='function') {
            x4.debug(
                " -> Event "+type+" is undefined on current controller, " 
                +'object '+ this.obj.id+' does not have method '+method
            );
            return true;
        }
        else {
            x4.debug(" -> Menubar dispatching to "+this.obj.jqId);
            return this.obj[method]();
        }
    }
}


/* ========================================================
 * Controller Constructor Funtion For x4TableTop
 *
 * Controller for a root object that can have 1 or more child
 * panes that have grids, details, or tab containers.
 *
 * Confirmed final form KFD 5/10/08
 * ========================================================
 */
function x4TableTop(self) {
    self.zTableIdPar = self.zParent.pullDown('zTableIdPar');
    self.zTableId    = self.getAttribute('xTableId');
    self.zPane1 = $(self).children(".x4Pane")[0];
    self.zPane2 = $(self).children(".x4Pane")[1];
    self.zIsChild    = self.zTableIdPar ? true : false;
    
    // Define the pull down variables that are returned
    self.pullDownVars = [ 'zTableId', 'zTableIdPar', 'zGridPane','zSkey' ];
    self.zGridPane    = self.zPane1;
    
    /* @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
     * 
     * Activation Code
     *
     * @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
     */
    self.makeVisible = function(parm1) { 
        x4.debugOpen("Make Visible ",this.jqId);
        if(!this.zCurrentDisplay) {
            if(parm1 == null ) {
                x4.debug("Parm1 is null, going to pane 1");
                this.zPane1.makeVisible();
            }
            else {
                x4.debug("Parm2 is not null, going to pane 2");
                this.zPane2.makeVisible();
            }
        }
        this.style.display='block';
        x4.debugClose("Make Visible ",this.jqId);
    }
    
    self.activate=function(parm1) {
        x4.debugOpen("ACTIVATE ",this.jqId);
        var parm2 = null;
        if(parm1!=null) { 
            parm2 = parm1;
            parm1 = 'edit';
        }
        x4.debug("parm 1 and 2 "+parm1 +" "+parm2);
        this.zSKeyPar    = this.zIsChild
            ? this.zParent.pullDown('zSkeyPar')
            : 0;
        var h1 = $a.byId('x4H1Top');
        if(this.zIsChild) $a.byId('x4H1Top').saveStem();
        
        if(!this.zCurrentDisplay) {
            if(parm1 != null) {
                this.zCurrentDisplay = this.zPane2;
            }
            else {
                this.zCurrentDisplay = this.zPane1;
            }
        }       
        this.style.display='block';
        this.zCurrentDisplay.activate(parm1,parm2);
        x4.debugClose("ACTIVATE ",this.jqId);
    }
    self.deactivate=function() {
        x4.debugOpen("Deactivating ",this.jqId);
        if(this.zIsChild) $a.byId('x4H1Top').clearStem();
        this.zCurrentDisplay.deactivate();
        this.zCurrentDisplay.style.display='none';
        x4.debugClose("Deactivating ",this.jqId);
    }
    
    /* \/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/
     * 
     * UP-down code
     *
     * /\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\
     */
    self.sendUp = function(name,value) {
        if(name=='Esc') {
            x4.debug(
                "Child Esc request from " + value.jqId
                +" to " + this.jqId
            );
            value.deactivate();
            value.style.display='none';
            if(value == this.zPane1) {
                x4.debug(' --> on grid');
                if(value.zCmd == 'Esc') {
                    x4.debug(" --> Sending up esc request");
                    this.zParent.sendUp('Esc',this);
                }
                else { 
                    x4.debug(" --> Assuming request to go to detail");
                    if(this.zPane2.zType=='x4TabContainer') {
                        this.zPane2.makeVisible(false);
                    }
                    this.zCurrentDisplay = this.zPane2;
                    this.zPane2.activate(value.zCmd,value.zCmdSkey);
                }
            }
            else {
                x4.debug(' --> assuming detail, going to grid');
                this.zCurrentDisplay = this.zPane1;
                this.zPane1.activate(value.zCmd);
            }
        }
        else if(name=='tabBarOnlyMe') {
            // A table top must intercept and pass itself
            // as the object that wants to be the only
            // enabled tab
            this.zParent.sendUp('tabBarOnlyMe',this);   
        }
        else if(name=='zSkey') {
            x4.debug(this.jqId+" caught and saved zSkey: "+value);
            this.zSkey = value;
        }
        else {
            return this.zParent.sendUp(name,value);
        }
    }
}

/* ========================================================
 * Constructor Funtion x4TabContainer
 *
 * Contains a tab bar and tabs, which themselves may be
 * tabletops or details
 *
 * Confirmed in final form KFD 5/10/08
 * ========================================================
 */
function x4TabContainer(self) {
    self.zTableIdPar     = self.zParent.pullDown('zTableId');
    self.zCurrentDisplay = false;
    self.zPane1 = $(self).children(".x4Pane")[0];
    self.zTabBar = $(self).children(".x4TabBar")[0];
    
    self.pullDownVars = [ 'zSkeyPar','zTableIdPar' ];
    
    /* @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
     * 
     * Activation Code
     *
     * @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
     */
    self.makeVisible = function(doYourself) {
        x4.debug("makeVisible in "+this.jqId);
        if(doYourself) {
            x4.debug(" -> Instructed to make myself visible");
            this.style.display='block';
        }
        if(!this.zCurrentDisplay) {
            x4.debug(" -> No current display, making first pane visible.");
            this.zPane1.style.display='block';
        }
        else {
            x4.debug(" -> There was a current display, doing nothing");
        }
    }
     
    self.activate=function(parm1,parm2) {
        x4.debugOpen("ACTIVATE ",this.jqId);
        this.zSkeyPar    = this.zParent.pullDown('zSkey');
        this.zTabBar.activate();
        $(this).fadeIn(x4.fadeSpeed,function() {
            this.goTab(this.zPane1.id,parm1,parm2);
        });
        x4.debugClose("ACTIVATE ",this.jqId);
    }
    self.deactivate = function() {
        x4.debugOpen("DEACTIVATE",this.jqId);
        this.zTabBar.deactivate();
        x4.debugClose("DEACTIVATE",this.jqId);
    }

    /* <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
     *                   
     * Local Logic
     *
     * <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
     */
    self.dispatch = function(tabId) {
        if( tabId != this.zCurrentDisplay.id) {
            this.goTab(tabId);
        }
    }
     
    self.goTab = function(tabId,parm1,parm2) {
        x4.debug("Tab switch from "+this.zCurrentDisplay.jqId
            +" to " + tabId
        );
        if( $(this.zTabBar).find('#tabFor_'+tabId+".disabled").length>0 ) {
            x4.debug(" ! That tab is disabled, ignoring");
            return false;
        }
        if(this.zCurrentDisplay) {
            x4.debug(" -> Deactivating old tab");
            x4.debug("old: "+this.zCurrentDisplay.id);
            x4.debug("new: "+tabId);
            if(this.zCurrentDisplay.id == tabId) {
                x4.debug(" -> old an new are the same.");
            }
            else {
                x4.debug(" -> old and new are not the same, deactivating old");
                $('#tabFor_'+this.zCurrentDisplay.id).removeClass('tabSelected');
                this.zCurrentDisplay.deactivate();
                this.zCurrentDisplay.style.display = 'none';
            }
        }
        this.zCurrentDisplay = $a.byId(tabId);
        $('#tabFor_'+tabId).addClass('tabSelected');
        this.zCurrentDisplay.activate(parm1,parm2);
    }    

    /* \/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/
     * 
     * UP-down code
     *
     * /\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\
     */
    self.sendUp = function(name,value) {
        x4.debug(this.jqId+" handling sendUp("+name+")");
        x4.debug(value);
        if(name=='tabBarOnlyMe') {
            // Disable all tabs except this one
            window.value = value;
            var id = 'tabFor_'+value.id;
            window.theId = id;
            $(this.zTabBar).find("a:not([id="+id+"])").addClass("disabled");
            return true;
        }
        else if(name=='tabBarEverybody') {
            $(this.zTabBar).find("a").removeClass("disabled");
            return true;
        }
        else if(name=='Esc') {
            value.deactivate();
            if(value == this.zPane1) {
                this.zParent.sendUp('Esc',this);
            }
            else {
                this.goTab(this.zPane1.id);
            }
        }
        else {
            x4.debug("Tab container passing up request "+name);
            this.zParent.sendUp(name,value);
        }
    }
}
/* ========================================================
 * Constructor Funtion x4TabBar
 *
 * Contains tabs
 *
 * Confirmed in final form KFD 5/13/08
 * ========================================================
 */
function x4TabBar(self) {
    /* @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
     * 
     * Activation Code
     *
     * @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
     */
    self.activate=function() {
        x4.debugOpen("ACTIVATE",this.jqId);
        document.x4Register('keyPress',this);
        x4.debugClose("ACTIVATE",this.jqId);
    }
    self.deactivate = function() {
        x4.debugOpen("ACTIVATE",this.jqId);
        document.x4UnRegister('keyPress',this);
        x4.debugClose("ACTIVATE",this.jqId);
    }
    
    /* <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
     *                   
     * Local Logic
     *
     * <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
     */
    self.keyPress = function(label) {
        // Determine if there is an enabled button
        x4.debugOpen("TABBAR KEYPRESS",this.jqId);
        var x = $(this).find('a[xAction='+label+']:not(.disabled)');
        var retval = true;        
        if(x.length==0) {
            x4.debug('No enabled tabs found, no action');
            retval = true;
        }
        else {
            x4.debug('Found a tab, going for it');
            $(x).click();
            retval = false;
        }
        x4.debugClose("TABBAR KEYPRESS",this.jqId);
        return retval;
    }     
}


/* ========================================================
 *
 * Controller Constructor for x4GridSearch
 *
 * A search or display grid that shows relevant rows.
 *
 * Confirmed in final form KFD 5/10/08
 * ========================================================
 */
function x4GridSearch(self) {
    self.zTableId    = self.getAttribute('xTableId');
    self.zTableIdPar = self.zParent.pullDown('zTableIdPar');
    self.zRowId      = false;
    self.zRowCount   = 0;
    self.zActivated  = false;
    self.zSortCol    = false;
    self.zSortAD     = false;
    
    // Work out if a child pane
    self.zIsChild = self.zTableIdPar ? true : false;
    
    /* 
     * keyUp handler for inputs is for fetching only:
     * detects changed values and changed sort orders.
     * Need "KeyUp" because it is after the fact, all inputs
     * reflect the new values.
     *
     */
    $(self).find(":input").keyup(function(event) {
        // Key label comes first
        var keyLabel = $a.label(event);
        var par    = x4.parent(this);
        
        if(keyLabel=='Enter') {
            x4.parent(this).keyPress_editRow();
            return false;
        }
        if(keyLabel=='ShiftUpArrow') {
            var colNew = this.getAttribute('xColumnId');
            if(colNew != par.zSortCol || par.zSortAD != 'ASC') {
                par.setOrderBy(this,'ASC');
                par.fetch(true);
            }
            return false;
        }
        if(keyLabel=='ShiftDownArrow') {
            var colNew = this.getAttribute('xColumnId');
            if(colNew != par.zSortCol || par.zSortAD != 'DESC') {
                par.setOrderBy(this,'DESC');
                par.fetch(true);
            }
            return false;
        }
        
        // If no sort order is set yet, and this is not empty,
        // set sort order to this control
        if(!par.zSortCol && this.value!='') {
            par.setOrderBy(this,'ASC');
        }

        // Pass control to the fetch program
        x4.parent(this).fetch();
        return;
    });
    
    /* @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
     * 
     * Activation Code
     *
     * @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
     */
    self.makeVisible = function() {
        x4.debugOpen("Make Visible",this.jqId);
        this.style.display='block';
        x4.debugClose("Make Visible",this.jqId);
    }
    self.activate=function() {
        x4.debugOpen("ACTIVATE ",this.jqId);
        if(this.zActivated) {
            x4.debug("no action, already activated");
        }
        else {
            document.x4Register('keyPress',this);
            this.zParent.sendUp('menuBar',this);
            this.zParent.sendUp('tabBarEverybody');
            this.zParent.sendUp(
                'menuBarLabel',x4dd.dd[this.zTableId].singular
            );
            
            this.setButtonBar();
            
            if(this.zIsChild) {
                this.zSkeyPar = x4.parent(this).pullDown('zSkeyPar'); 
                this.fetch(true);
            }
            
            $a.byId('x4H1Top').setHTML(x4dd.dd[this.zTableId].description);
            
            // Make your entrance
            $(this).fadeIn(x4.fadeSpeed,function() {
                if(!this.zLastFocus) {
                    this.zLastFocusId=$(this).find(":input:first")[0].id;
                }
                $('#'+this.zLastFocusId).focus();
                this.zActivated = true;
            });
        }
        x4.debugClose("ACTIVATE ",this.jqId);
    }

    self.deactivate = function() {    
        x4.debugOpen("DEACTIVATE ",this.jqId);
        if(!this.zActivated) {
            x4.debug("No action, already deactivated");
        }
        else {
            document.x4UnRegister('keyPress');
            $('#'+this.zLastFocusId).blur();
            this.zActivated = false;
        }
        x4.debugClose("DEACTIVATE ",this.jqId);
    }

    /* <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
     * 
     * Basic logic for fetching
     *
     * <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
     */

    self.setOrderBy = function(inputObj,direction) {
        if(inputObj==null || inputObj==undefined) {
            var inputObj = $(this).find(":input:first")[0];
            direction = 'ASC';
        }
        if(inputObj==null || inputObj==undefined) return;
        inputCol = inputObj.getAttribute('xColumnId');
        this.zSortCol = inputCol;
        this.zSortAD = direction;
        x4.debug("sorting on "+this.zSortCol+", "+this.zSortAD);
    }
    //self.setOrderBy();
    
    self.fetch = function(doFetch) {
        if(doFetch==null) doFetch=false;
        this.doFetch=doFetch;
        this.cntNoBlank = 0;
        
        // Initialize and then scan 
        $a.json.init('x4Page',this.zTableId);
        $(this).find(":input").each(function() {
            if(typeof(this.zValue)=='undefined') 
                this.zValue = this.getAttribute('xValue');
            if(this.value!=this.zValue) {
                x4.parent(this).doFetch = true;
            }
            if(this.value!='') {
                x4.parent(this).cntNoBlank++;
            }
            this.zValue = this.value;
            $a.json.addParm('x4w_'+this.getAttribute('xColumnId'),this.value);
        });
        
        // If there is a parent table, this code "fakes it out"
        // to force a fetch and not to clear
        if(this.zIsChild) {
            $a.json.addParm('tableIdPar',this.zTableIdPar);
            $a.json.addParm('skeyPar',this.zSkeyPar);
            this.doFetch=true;
            this.cntNoBlank = 100;
        }
        
        if(this.doFetch) {
            if(this.cntNoBlank==0) {
                this.clear();
                return;
            }
            $a.json.addParm('sortCol',this.zSortCol);
            $a.json.addParm('sortAD' ,this.zSortAD);
            $a.json.addParm('x4Action','browseFetch');
            $a.json.addParm('x4Limit',300);
            if( $a.json.execute()) {
                var gridBodyId = this.getAttribute('xGridBodyId');
                $a.json.process( gridBodyId );
                this.skeys = $a.data.skeys;
                this.rowCount = $a.data.rowCount;
                
                // Tell x4Browse how many rows it has
                this.zRowCount = $a.byId(gridBodyId).rows.length;
                this.setButtonBar();

                window.temp = this;
                $('#'+gridBodyId).find('tr').each( function() {
                    this.zParent = window.temp;
                }).click(function() {
                    this.zParent.keyPress_editRow();
                }).mouseover( function() {
                    if(this.zParent.zRowId) {
                        $("#"+this.zParent.zRowId).removeClass('highlight');
                    }
                    $(this).addClass('highlight');
                    this.zParent.zRowId = this.id;
                    this.zParent.zSkey = Number(this.id.slice(6));
                    this.zParent.zParent.zSkey = this.zParent.zSkey;
                    var rx = Number(this.getAttribute('xIndex'))+1;
                    this.zParent.sendUp(
                        'rowInfo','Record '+rx+' of '+this.zParent.zRowCount
                    );
                });
                window.temp=null;
                
                $('#'+gridBodyId).find('tr:first').mouseover();
            }
        }
    }
    
    /**
      * Clear search results
      *
      */
    self.clear = function() {
        this.zRowId = false;
        this.zRowCount = 0;
        $a.byId(this.getAttribute('xGridBodyId')).innerHTML = '';
        $(this).find(":input").each(function() {
            this.value='';
            this.x_value='';
        });
        this.sendUp('rowInfo','No Records');
        
        this.setButtonBar();
    }
    
    self.setButtonBar = function() {
        if(this.zRowCount > 0) {
            this.zParent.sendUp('menuBarDisable','');
        }
        else {
             // Disable copy and delete
             var str='deleteRow,copyRow,saveRow,CtrlPageUp,PageUp,CtrlPageDown,PageDown';
             this.zParent.sendUp('menuBarDisable',str);
        }        
    }
    
    /* <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
     * 
     * Row navigation, up and down
     *
     * <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
     */
    self.keyPress_UpArrow = function() {
        if(!this.zRowId) return false;
        var x = $a.byId(this.zRowId);
        if(x.previousSibling!=null) {
            $('#'+x.previousSibling.id).mouseover();
        }
        return false; 
    }
    self.keyPress_PageUp = self.keyPress_UpArrow;
    
    self.keyPress_DownArrow = function() {
        if(!this.zRowId) return false;
        var x = $a.byId(this.zRowId);
        if(x.nextSibling!=null) {
            $('#'+x.nextSibling.id).mouseover();
        }
        return false; 
    }
    self.keyPress_PageDown = self.keyPress_DownArrow; 

    self.keyPress_CtrlUpArrow = function() {
        var bid = $a.byId(this.getAttribute('xGridBodyId'));
        window.bid = bid;
        if(bid.firstChild) {
            if(this.zRowId != bid.firstChild.id) {
                $(bid).find('tr:first').mouseover();
            }
        }
        return false; 
    }
    self.keyPress_CtrlPageUp = self.keyPress_CtrlUpArrow;

    self.keyPress_CtrlDownArrow = function() {
        var bid = $a.byId(this.getAttribute('xGridBodyId'));
        if(bid.lastChild) {
            if(this.zRowId != bid.lastChild.id) {
                $(bid).find('tr:last').mouseover();
            }
        }
        return false; 
    }
    self.keyPress_CtrlPageDown = self.keyPress_CtrlDownArrow;

    
    /* <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
     *                   
     * Menu Bar dispatching event
     *
     * <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
     */
    self.keyPress_Esc = function() {
        if(this.zRowCount > 0 && !this.zIsChild) {
            this.clear();
        }
        else {
            x4.debug("Sending escape request to parent from "+this.jqId);
            this.zCmd = 'Esc';
            this.zParent.sendUp('Esc',this);
            return false;
        }
    }
    self.keyPress_newRow = function() {
        this.zCmdSkey = 0;
        this.zCmd     = 'new';
        this.zParent.sendUp('Esc',this);
        return false;
    }
    self.keyPress_CtrlA = self.keyPress_newRow;
    self.keyPress_editRow = function() {
        if(this.zRowCount > 0) {
            this.zCmdSkey = this.zSkey;
            this.zCmd     = 'edit';
            this.zParent.sendUp('Esc',this);
            return false;
        }
    }
    self.keyPress_copyRow = function() {
        if(this.zRowCount > 0) {
            this.zCmdSkey = this.zSkey;
            this.zCmd     = 'new';
            this.zParent.sendUp('Esc',this);
        }
        return false;
    }
    self.keyPress_CtrlP = self.keyPress_copyRow;
 
    self.keyPress_deleteRow = function() {
        if(!this.zRowId) {
            $a.dialogs.alert('I cannot delete because there is nothing selected.');
        }
        else {
            if($a.dialogs.confirm("Do you really want to delete?")) {
                $a.json.init('x4Page',this.zTableId);
                $a.json.addParm('x4Action','delete');
                $a.json.addParm('skey',this.zSkey);
                if($a.json.execute()) {
                    if(!$a.json.hadErrors) {
                        $a.dialogs.alert('The selected row was deleted.');
                        this.fetch(true);
                    }
                }
            }
        }
        return false;
    }
    self.keyPress_CtrlD = self.keyPress_deleteRow;
    x4.debug(self);
}

/* ========================================================
 * Constructor Funtion x4Detail
 *
 * Shows details for some columns
 *
 * Confirmed in final form KFD 5/10/08
 * ========================================================
 */
function x4Detail(self) {
    self.zTableId    = self.getAttribute('xTableId');
    self.zTableIdPar = self.zParent.pullDown('zTableIdPar');
    self.zGridPane   = self.zParent.pullDown('zGridPane')
    
    // Work out if child
    if(!self.zTableIdPar) 
        self.zIsChild = false;
    else 
        self.zIsChild = self.zTableId == self.zTableIdPar ? false : true;
    
    // Assign input masks to dates
    // No Good: Works only for fixed-length, no good for numerics
    //$(self).find(':input[xInputMask]').each(function() {
    //    $(this).mask(this.getAttribute('xInputMask'),{placeholder:' '});
    //});
    
    /* @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
     * 
     * Activation Code
     *
     * @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
     */
    self.makeVisible = function() {
        x4.debug("makeVisible in "+this.jqId);
        this.style.display='block';
    }
     
    self.activate=function(action,skey) {
        // Tell the powers that be we want menu bar events
        x4.debugOpen("ACTIVATE ",this.jqId);
        x4.debug("With '"+action+"' and '" +skey+"'");
        document.x4Register('keyPress',this);
        this.zParent.sendUp('menuBar',this);
        this.zParent.sendUp(
            'menuBarLabel',x4dd.dd[this.zTableId].singular
        );
        
        // Before displaying, do possible fetch         
        if(skey > 0) {
            this.fetchRow(skey);
            this.displayRow();
        }
        if(action == 'new' || action=='copy') {
            if(action=='new')
                this.setDefaults(true);
            else
                this.setDefaults(false);
            this.setMode('new');
        }
        else {
            this.setMode('upd');
        }

        $(this).fadeIn(x4.fadeSpeed,function() {
            x4.stdlib.findFocus(this);
        });
        x4.debugClose("ACTIVATE ",this.jqId);
    }
    
    self.deactivate = function() {
        x4.debug("Deactivating "+this.jqId);
        document.x4UnRegister('keyPress');
        $('#'+this.zLastFocusId).blur();
    }
    
    /* <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
     *                   
     * Local Logic Code
     *
     * <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
     */
    
    // Magic number alert: zero equals a new row, insert mode
    self.fetchRow = function(skey) {
        this.skey = skey;
        
        // If not a new row, go fetch it
        $a.json.init();
        $a.json.addParm('x4Page',this.zTableId);
        $a.json.addParm('x4Action','fetchRow');
        $a.json.addParm('x4w_skey',skey);
        $a.json.execute(true);
        this.displayRow();
        
        // Tell child tables that our PK is default
        var apks = x4dd.dd[this.zTableId].pks.split(',');
        var row  = $a.data.row;
        for(var idx in x4dd.dd[this.zTableId].fk_children) {
            var tabChild = x4dd.dd[this.zTableId].fk_children[idx].table_id;
            if(typeof(x4dd.dd[tabChild])=='undefined') continue;
            var dd = x4dd.dd[tabChild];
            for(var pkidx in apks) {
                var pk = apks[pkidx];
                //x4.debug("assigning default value "+row[pk]+" to "
                //    +tabChild+'.'+pk
                //);
                x4dd.dd[tabChild].flat[pk].automation_id='DEFAULT';
                x4dd.dd[tabChild].flat[pk].auto_formula = row[pk];
            }
        }
    },
    
    self.displayRow = function() {
        var skeys=$a.aProp(this.zGridPane,'skeys',[]);
        var rowNow = $a.aProp(skeys,this.zGridPane.zSkey,'0')+1;
        var text = 'Record '+rowNow+' of '+this.zGridPane.zRowCount;
        $("#x4RowInfoText").html(text);
        
        this.setTitle('');
        
        var row = $a.data.row;
        $(this).find(":input").each(function() {
            var row = $a.data.row;
            var id    = this.id;
            var input = this;
            var column_id = this.getAttribute('xColumnId');
            var value = $a.aProp(row,column_id,'');
            if(value==null) value='';
            if(input.getAttribute('xTypeId')=='dtime') {
                if(value=='') {
                    input.value = '';
                }
                else {
                    value = value.slice(5,7)+'/'+value.slice(8,9)
                        +'/'+value.slice(0,4)
                        +' '+value.slice(11,19);
                    input.value = value;
                }
            }
            else {
                input.value   = $a.aProp(row,column_id,'');
            }
            input.xValue = input.value;
        });
    },
    
    self.setDefaults = function(blank) {
        window.temp = blank;
        $(this).find(":input").each( function() {
            var tab = this.getAttribute('xTableId');
            var col = this.getAttribute('xColumnId');
            var autoid = x4dd.dd[tab].flat[col].automation_id;
            if(autoid=='DEFAULT') {
                this.value = x4dd.dd[tab].flat[col].auto_formula;
            }
            else {
                if(window.temp) {
                    this.value = '';
                }
            }
            this.xValue = this.value;
        });
    },
    
    self.setMode= function(mode) {
        // Send up a request to enable/disable tabs
        if(mode=='new') {
            this.zParent.sendUp('tabBarOnlyMe',this);
        }
        else {
            this.zParent.sendUp('tabBarEverybody',this);
        }
        
        if(mode=='new') {
            this.skey=0;
            var str='newRow,deleteRow,copyRow';
            str   += ',CtrlPageUp,PageUp,CtrlPageDown,PageDown';
            this.zParent.sendUp('menuBarDisable',str);
        }
        else {
            this.zParent.sendUp('menuBarDisable','');
        }
        
        // Title
        this.setTitle(mode);

        // Set the read-only and the coloring, and defaults for new
        $(this).find(":input").each( function() {
            var inp = this;
            var col = inp.getAttribute('xColumnId');
            var ro = mode=='new' 
                ? inp.getAttribute('xRoIns')
                : inp.getAttribute('xRoUpd');
            if(ro=='')   ro = 'N';
            if(ro==' ')  ro = 'N';
            if(ro==null) ro = 'N';
            if( ro=='Y') {
                inp.readOnly = true;
                $(inp).addClass('x4inputReadOnly');
            }
            else {
                inp.readOnly = false;
                $(inp).removeClass('x4inputReadOnly');
            }
            
            // Now for coloring
            if(mode=='new' && ro == 'N') {
                $(inp).addClass('x4Insert');
            }
            else {
                $(inp).removeClass('x4Insert');
            }
        });
    }
    
    self.setTitle = function(mode) {
        if(mode=='new') {
            var title = 'New ' + x4dd.dd[this.zTableId].singular;
            this.zParent.sendUp('rowInfo','New Entry');
        }
        else {
            var col1  = x4dd.firstPkColumn(this.zTableId);
            var title = x4dd.dd[this.zTableId].singular 
                + ": "+ $a.data.row[col1]; 
            if(x4dd.pkColumnCount(this.zTableId)>1) {
                title += '...';
            }
        }
        $a.byId('x4H1Top').setHTML(title);
    }
    
    self.tryToSave = function(force) {
        this.zMustSave = false;
        window.temp = this;
        window.changes = '';
        $a.json.init();
        $(this).find(":input").each(function() {
            if ((this.value != this.xValue) || window.temp.skey==0) {
                if(this.value !=this.xValue) {
                    var col = this.getAttribute('xColumnId');
                    var tab = this.getAttribute('xTableId');
                    var cap = x4dd.dd[tab].flat[col].description;
                    window.changes+=cap+" changed to: "+this.value;
                    window.changes+="\n";
                }
                window.temp.zMustSave=true;
                $a.json.addParm('x4v_'+this.getAttribute('xColumnId')
                    ,this.value
                );
            }
        });
        
        if(!this.zMustSave) return true;
        
        if(force!=true) {
            var text ="Would you like to save changes?\n\n"+window.changes;
            if(!$a.dialogs.confirm(text)) {
                return false;
            }
        }
        
        $a.json.addParm('x4v_skey',this.skey);
        $a.json.addParm('x4Page'  ,this.zTableId);
        $a.json.addParm('x4Action','update');
        $a.json.execute(true);
        if(!$a.json.hadErrors) {
            if(this.skey == 0) {
                $a.dialogs.alert("New "+x4dd.dd[this.zTableId].singular
                    +" has been saved.");
                this.skey = $a.data.row.skey;
                this.zParent.sendUp('zSkey',this.skey);
                if(this.zParent.zType = 'x4TabContainer') {
                    this.zParent.zSkeyPar = this.skey;
                }
            }
            else {
                $a.dialogs.alert("Changes to "
                    +$a.byId('x4H1Top').innerHTML
                    +" have been saved.");
            }
            return true;
        }
        else {
            return false;
        }
    }

    /* <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
     *                   
     * Record Navigation 
     *
     * <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
     */
     self.move = function(keyLabel) {
        if(this.tryToSave()) {
            this.zGridPane['keyPress_'+keyLabel]();
            var skey = this.zGridPane.zSkey;
            if(skey!=this.skey) {
                this.fetchRow(skey);
            }
            return false;
        }
    }
    self.keyPress_PageDown = function()     { return self.move('PageDown'); }
    self.keyPress_PageUp   = function()     { return self.move('PageUp'); }
    self.keyPress_CtrlPageDown = function() { return self.move('CtrlPageDown');}
    self.keyPress_CtrlPageUp   = function() { return self.move('CtrlPageUp'); }
    
    /* <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
     *                   
     * Menu Bar and keyboard dispatching 
     *
     * <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
     */
    self.keyPress_Esc = function() {
        this.zCmd = false;
        window.isChanges = false;
        $(this).find(":input").each( function() {
                if(this.xValue != this.value) {
                    window.isChanges=true;
                }
        });
        if(!window.isChanges) {
            this.zParent.sendUp('Esc',this);
        }
        else {
            if($a.dialogs.confirm("Abandon changes?")) {
                this.zParent.sendUp('Esc',this);
            }
            else {
                this.zParent.sendUp('Esc',this);
            }
        }
    }
    
    self.keyPress_deleteRow = function() {
        if($a.dialogs.confirm("Do you really want to delete?")) {
            $a.json.init('x4Page',this.zTableId);
            $a.json.addParm('x4Action','delete');
            $a.json.addParm('skey',this.skey);
            if($a.json.execute()) {
                if(!$a.json.hadErrors) {
                    $a.dialogs.alert('The selected row was deleted.');
                    this.zCmd = true;
                    this.zParent.sendUp('Esc',this);
                }
            }
        }
        return false;
    }
    self.keyPress_CtrlD     = self.keyPress_deleteRow;
    
    self.keyPress_newRow = function() {
        this.setDefaults(true);  // clear
        this.setMode('new');
        return false;
    }
    self.keyPress_CtrlN  = self.keyPress_newRow;
    
    self.keyPress_saveRow = function() {
        if(this.tryToSave(true)) {
            this.displayRow();
            this.setMode('upd');
        }
        return false;
    }
    self.keyPress_CtrlS  = self.keyPress_saveRow;
    
    self.keyPress_saveRowAndNewRow = function() {
        if(this.tryToSave(true)) {
            this.keyPress_newRow();
        }
        return false;
    }
    self.keyPress_CtrlA  = self.keyPress_saveRowAndNewRow;
    
    self.keyPress_saveRowAndExit = function() {
        if(this.tryToSave(true)) {
            this.keyPress_Esc();
        }
        return false;
    }
    self.keyPress_CtrlX  = self.keyPress_saveRowAndExit;
    
    self.keyPress_copyRow = function() {
        this.setDefaults(false);
        this.setMode('new');
        return false;
    }
    self.keyPress_CtrlP   = self.keyPress_copyRow;
}

/* ========================================================
 *
 * Controller Constructor for the androPage system
 *
 * ========================================================
 */
function x4AndroPage(self) {
    /* @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
     * 
     * Activation Code
     *
     * @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
     */
    self.activate = function() {
        document.x4Register('keyPress',this);
        
        $(this).fadeIn(x4.fadeSpeed,function() {
            if($(this).find(":input,a").length > 0) {
                $(this).find(":input,a")[0].focus();
            }
        });
    }
    
    /* <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
     *                   
     * Keyboard events
     *
     * <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
     */
    self.keyPress_Enter = function() {
        if(this.defaultOutput!='') {
            this[this.getAttribute('defaultOutput')]();
        }
        return false;
    }
    
    self.keyPress_Esc = function() {    
        if( $(this).find("#divOnScreen").html() == '' ) {
            x4.returnToMenu( $a.byId('x4Page').value );
        }
        else {
            $(this).find(":input").each( function() {
                    this.value='';
            });
            $(this).find("#divOnScreen").html('');
        }
        return false;
    }

    self.keyPress_UpArrow = function() {
        if(this.rowCurrentId) {
            var id = Number(this.rowCurrentId.slice(4));
            if(id != 0) {
                this.highlightRow('row_'+(id-1),true);
            }
        }
        return false;
    }
    self.keyPress_PageUp = function() {
        if(this.rowCurrentId) {
            var id = Number(this.rowCurrentId.slice(4));
            if(id > 20) {
                this.highlightRow('row_'+(id-20),true);
            }
            else {
                this.highlightRow('row_0',true);
            }
        }
        return false;
    }
    
    self.keyPress_DownArrow = function() {
        if(this.rowCurrentId) {
            var id = Number(this.rowCurrentId.slice(4));
            if(id < (this.rowCount-1)) {
                this.highlightRow('row_'+(id+1),true);
            }
        }
        return false;
    }
    self.keyPress_PageDown = function() {
        if(this.rowCurrentId) {
            var id = Number(this.rowCurrentId.slice(4));
            if(id >= (this.rowCount-20)) {
                this.highlightRow('row_'+(id+1),true);
            }
            else {
                this.highlightRow('row_'+(id+20),true);
            }
        }
        return false;
    }
        
    self.keyPress_RightArrow = function() {
        if(this.rowCurrentId) {
            var x = $('#'+this.rowCurrentId+' a:first').attr('href');
            if(typeof(x)!='undefined') {
                window.location=x;
            }
        }
        return false;
    }
        
    self.keyPress_F1 = function() {
        this.help();
        return false;
    }
    
    /*
     *
     * The functions
     *
     */
    self.printNow = function() {
        $a.byId('gp_post').value='pdf';
        x4.initPost(this);
        $a.json.windowLocation();
        return false;
    }
    self.keyPress_CtrlP = self.printNow;
    
    self.showSql = function() {
        x4.initPost(this);
        $a.json.addParm('showsql',1);
        $a.json.execute();
        $a.json.process('divShowSql');
        return false;
    }
    self.keyPress_CtrlQ = self.showSql;
    
    self.showOnScreen = function() {
        this.tBody = null;
        
        $a.byId('gp_post').value='onscreen';
        x4.initPost(this);
        $a.json.execute();
        $a.json.process('divOnScreen');
        
        // Always remove the last row from the body, because 
        // AndroPage always has a trailing blank row
        var tbody = $(this).find("#divOnScreen table tbody")[0];
        $(tbody).find("tr:last").remove();
        this.rowCurrentId = false;
        this.tBody = false;
        this.rowCount = 0;
        if(tbody.rows.length > 0) {
            this.tBody = tbody;
            this.rowCount = tbody.rows.length;
            
            window.temp = this;
            $(tbody).find("tr").each(function() {
                this.zParent = window.temp;
            }).mouseover( function() {
                this.zParent.highlightRow(this.id);
            });
            this.highlightRow('row_0');
            window.temp = null;
            
            $(this).find("#divOnScreen table").Scrollable(500,500);
        }
        return false;
    }
    self.keyPress_CtrlO = self.showOnScreen;
    
    /*
     * The row highlighter
     *
     */
    self.highlightRow = function(rowId,fromKeyBoard) {
        if(!this.tBody) return;
        // Turn off any old row
        if(this.rowCurrentId) {
            $a.byId(this.rowCurrentId).className = '';
        }
        this.rowCurrentId = false;
        
        // Turn on the highlighted one if it exists
        $('#'+rowId).each( function() {
                this.zParent.rowCurrentId = this.id;
                this.className = 'highlight'
        });

        // If they came from the keyboard, we need to 
        // scroll down
        if(fromKeyBoard!=null) {
            var height = $("#row_1").height();
            var row = Number(rowId.slice(4));
            if(row < 23) {
                $("#divOnScreen table tbody").scrollTop(0);
            }
            else {
                var offSet = (height+2) * (row - 23);
                $("#divOnScreen table tbody").scrollTop(offSet);
            }
        }
    },
    
    /*
     *
     * The Help Stuff
     *
     */
    self.help = function() {
        var $msg='';
        $msg+="This is the "+$('#x4H1Top').html()+" inquiry screen.";
        $msg+="\n\n";
        $msg+="The input boxes accept a very flexible set of values,\n";
        $msg+="you can enter ranges like a-e or 100-200, you can enter\n";
        $msg+="comparisons like >x or <500, and you can put multiple\n";
        $msg+="criteria separated by commas, like <b,d,g-k,>x";
        $msg+="\n\n";
        $msg+="Hit CTRL-P to get a printable PDF report, or hit \n";
        $msg+="CTRL-O to see the results displayed onscreen.";
        $msg+="\n\n";
        $msg+="When results are displayed onscreen, use the up and down\n";
        $msg+="arrow keys to navigate, or the pageUp and pageDown keys.";
        $msg+="\n\n";
        $msg+="Sometimes the onscreen results will show hyperlinks to \n";
        $msg+="other pages.  Hit rightArrow to jump to the link.";
        $msg+="\n\n";
        $msg+="Hit ESC to clear results, and ESC to return to menu";
        
        alert($msg,"Inquiry Help Screen");
        $('#'+this.lastFocusId).focus();
    }
}


