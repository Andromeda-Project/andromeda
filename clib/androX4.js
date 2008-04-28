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

/*
 * This is the generic and top level object for the
 * Extended Desktop system.
 *
 */
var x4 =  {
    // Simple setting for behavior
    fadeSpeed: 'fast',
    
    /* 
     * initialize a json request that uses x4Page and
     * all inputs on object
     *
     */
    initPost: function(obj) {
        $a.json.init('x4Page', $a.byId('x4Page').value);
        $a.json.inputs();
    },
    
    // Stack of context objects used during initialization
    contextStack:  [ ],
    
    /*
     * Add an item to the context, and return the parent
     * so this object knows who to pass control up to
     *
     */
    context: function(object) {
        /* get the return value before adding an item */
        var retval =
            (this.contextStack.length == 0) 
            ? false 
            : this.contextStack[this.contextStack.length-1];
            
        /* if object was null, they were asking for context */
        if(object==null) return retval;
         
        /* add the item */
        this.contextStack[this.contextStack.length] = object;
        
        /* return the parent */
        return retval;
    },
    contextPop: function() {
        if(this.contextStack.length > 0) this.contextStack.pop();
    },
    
    /*
     * Simple routine to allow passing of events up to
     * parents.  The return value is a little funny.  You return
     * true if event handling should continue, false if it should
     * stop.
     *
     */
     passUp: function(child,functionName,event) {
         if(typeof(child.xParent)!='undefined') {
             if(child.xParent) {
                 if(typeof(child.xParent[functionName])!='undefined') {
                     return child.xParent[functionName](event);
                 }
             }
         }
         return true;
     },
 
    
    /*
     * Code entry point: the server returns a page of HTML that
     * invokes this function, so browser execution
     * begins here.
     *
     */
    main: function() {
        // Capture the data dictionary
        x4dd.dd = $a.data.dd;

        // Find the top object, and begin recursing it
        var rootObj = $a.byId('x4Top');
        this.initRecurse(rootObj);
        
        // The top object is a container only.  The activation
        // system begins with the first child we can find.
        if($(rootObj).children('.x4Pane').length > 0) {
            $(rootObj).children('.x4Pane')[0].activate();
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
        else {
            getString = '?x4Page=menu';
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
        $(obj).children(".x4Pane").each( function() {
            /* tell the object who its parent controller is */
            this.xParent = x4.context(this);
            
            /*
             * Create default keystroke handlers that "pass the buck"
             * up to the parent.  These are only invoked if inputs 
             * on the pane invoke them, otherwise they are inert.
             * Also they are subject to being overwritten by specific
             * code by controller constructors.
             *
             */
            this.keyDown = function(event) {
                return x4.passUp(this,'keyDown',event);
            }
            this.keyPress = function(event) {
                return x4.passUp(this,'keyPress',event);
            }
            this.keyUp = function(event) {
                return x4.passUp(this,'keyUp',event);
            }
            
                
            /* Look for all controller constructors based on classes */
            var classes = this.className.split(' ');
            for(var x in classes) {
                var oneClass = classes[x];
                if(oneClass=='x4Pane') continue;
                var constructor = false;
                /* This attempts to find a constructor */
                try {
                    constructor = eval(oneClass);
                }
                catch(e) { 
                    /* do nothing on error */ 
                }
                if(constructor) {
                    constructor(this);
                }
            }
                
            /* Always do a tab loop */
            $a.tabLoopInit(this);
                
            /* And of course, do the sub-panes as well */
            x4.initRecurse(this);
            
            /* After recursion is complete, surrender control of context */
            x4.contextPop();
        });
    },
}

/*
 * The menu bar object is a conduit to the current
 * controlling object.  When an object is activated
 * it tells the menu bar "go to me!"
 *
 */
var x4MenuBar = {
    obj: false,
    tab: false,
    eventHandler: function(type) {
        if(!this.obj) {
            $a.dialogs.alert("Event "+type+" called, but no object defined.");
        }
        else if( typeof(this.obj[type])==undefined) {
            $a.dialogs.alert(
                "Event "+type+" is undefined on current controller"
            );
        }
        else {
            // Execute!
            this.obj[type]();
        }
    },
    tabEvent: function(tab,tabId) {
        console.log(tabId);
        console.log($('#'+tabId)[0]);
        this.tab.goTab(tabId);
    }
}

/*
 * The x4dd contains the data dictionary of the current
 * table.  It is normally populated by the x4Browse
 * init routine, which pulls it from the json data and
 * puts it here.
 *            
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
        $(this).fadeIn(x4.fadeSpeed,function() {
            if($(this).find(":input,a").length > 0) {
                $(this).find(":input,a")[0].focus();
            }
        });
    }
    /* @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
     * 
     * Activation Code (END)
     *
     * @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ 
     */
    
    /*
     * A keyboard handler runs the three programs
     *
     */
    self.keyDown = function(event) {
        var letter = $a.charLetter(event.which);
        // Various control key functions
        if(event.ctrlKey) {
            if(letter=='p') {
                this.printNow();
                return false;
            }
            if(letter=='o') {
                this.showOnScreen();
                return false;
            }
            if(letter=='q') {
                this.showSql();
                return false;
            }
        }
        
        // ENTER and ESC functions
        var keyLabel = $a.keyLabel(event);
        if(keyLabel=='Enter') {
            if(this.defaultOutput!='') {
                this[this.defaultOutput]();
                return false;
            }
        }
        if(keyLabel == 'Esc') {
            x4.returnToMenu( $a.byId('x4Page').value );
            return false;
        }
        
        // Up and down arrows
        if(keyLabel == 'PageUp') {
            this.highlightRow('row_0');
            return false;
        }
        if(keyLabel == 'PageDown') {
            this.highlightRow('row_'+(this.rowCount-1));
            return false;
        }
        if(keyLabel == 'UpArrow') {
            if(this.rowCurrentId) {
                var id = Number(this.rowCurrentId.slice(4));
                if(id != 0) {
                    this.highlightRow('row_'+(id-1));
                    return false;
                }
            }
        }
        if(keyLabel == 'DownArrow') {
            if(this.rowCurrentId) {
                var id = Number(this.rowCurrentId.slice(4));
                if(id < (this.rowCount-1)) {
                    this.highlightRow('row_'+(id+1));
                    return false;
                }
            }
        }
  
        /* if not handled, pass up to parent */
        return x4.passUp(this,'keyDown',event);
    }
    
    /*
     *
     * Assignment of handlers to children.  Everything goes straight up
     *
     */
    $(self).find(":input,:button").each( function() {
            this.xParent = x4.context();
    }).keydown(function(event) {
        // If we want to prevent event propagation we must return
        // false *here*, in the function that first received the event
        return x4.passUp(this,'keyDown',event);
    });
    
    
    /*
     *
     * The functions
     *
     */
    self.printNow = function() {
        $a.byId('gp_post').value='pdf';
        x4.initPost(this);
        $a.json.windowLocation();
    }
    self.showSql = function() {
        x4.initPost(this);
        $a.json.addParm('showsql',1);
        $a.json.execute();
        $a.json.process('divShowSql');
    }
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
            tbody.parentContext = this;
            this.rowCount = tbody.rows.length;
            
            $(tbody).find("tr").mouseover( function() {
                this.parentNode.parentContext.highlightRow(this.id);
            });
            this.highlightRow('row_0');
        }
    }
    
    /*
     * The row highlighter
     *
     */
    self.highlightRow = function(rowId) {
        if(!this.tBody) return;
        // Turn off any old row
        if(this.rowCurrentId) {
            $a.byId(this.rowCurrentId).className = '';
        }
        this.rowCurrentId = false;
        
        // Turn on the highlighted one if it exists
        $('#'+rowId).each( function() {
                this.parentNode.parentContext.rowCurrentId = this.id;
                this.className = 'highlight'
        });
         
    }     
}

/* ========================================================
 * Controller Constructor Funtion For x4Window
 *
 * A window object contains a menu bar and (assuming) at
 * least one x4TableTop
 * ========================================================
 */
function x4Window(self) {
    self.keyDown = function(event) {
        
        
        
    }
    
    // Activate should be no-nonsense, make visible and 
    // activate first x4Pane
    self.activate = function() {
        this.style.display='block';
        $(this).children(".x4Pane")[0].activate();
    }
}

/* ========================================================
 * Controller Constructor Funtion For x4TableTop
 *
 * Controller for a root object that can have 1 or more child
 * panes that have grids, details, or tab containers.
 * ========================================================
 */
function x4TableTop(self) {
    self.pane1 = $(self).children(".x4Pane")[0];
    self.pane2 = $(self).children(".x4Pane")[1];
    
    // If the object above has a table, find it
    if(self.xParent==false) 
        self.xTableIdPar = '';
    else {
        self.xTableIdPar = self.xParent.xTableId;
    }
    
    /* @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
     * 
     * Activation Code
     *
     * @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
     */
    self.activate=function() {
        if(!this.currentDisplay) {
            this.currentDisplay = this.pane1;
        }
        this.style.display='block';   
        this.currentDisplay.activate();
    }

    self.deactivate = function() {
        if(this.xParent)
            this.xParent.deactivate();
        else 
            x4.returnToMenu();
    }
    
    /* <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
     *                   
     * Branching Code---<
     *
     * <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
     */
    self.goDetail = function(skey,action) {
        this.currentDisplay.style.display = 'none';
        this.currentDisplay = this.pane2;
        this.currentDisplay.activate(skey,action);
    }
    
    self.goGrid = function() {
        this.currentDisplay.style.display = 'none';
        this.currentDisplay = this.pane1;
        this.currentDisplay.activate();
    }
}

/* ========================================================
 * Constructor Funtion x4TabContainer
 *
 * Contains a tab bar and tabs, which themselves may be
 * tabletops or details
 * ========================================================
 */
function x4TabContainer(self) {
    self.currentDisplay = false;
    self.pane1 = $(self).children(".x4Pane")[0];
    
    /* @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
     * 
     * Activation Code
     *
     * @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
     */
    self.activate=function(parm1,parm2) {
        x4MenuBar.tab = this;
        if(!this.currentDisplay) {
            this.currentDisplay = this.pane1;
        }
        this.style.display='block';   
        this.currentDisplay.activate(parm1,parm2);
    }

    self.deactivate = function() {
        if(this.currentDisplay != this.pane1) {
            
        }
        if(this.xParent)
            return false;
        else
            x4.returnToMenu();
    }
    
    /* <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
     *                   
     * Branching Code---<
     *
     * <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
     */
    self.goTab = function(tabId) {
        $(this.currentDisplay).css('display','none');
        this.currentDisplay = $('#'+tabId)[0];
        this.currentDisplay.activate();
    }    
}


/* ========================================================
 *
 * Controller Constructor for x4Grid
 *
 * A search or display grid that shows relevant rows.
 *
 * ========================================================
 */
function x4Grid(self) {
    self.xTableIdPar = self.xParent.xTableIdPar;
    self.xTableId    = self.xParent.xTableId;
    self.lastFocus = false;
    self.zRowId = false;
    self.inputs = [];
    self.tabLoop= [];
    
    // Turn on a tab loop
    $a.tabLoopInit(self);
    
    // Make all assignments to inputs
    $(self).find(":input").each(function() {
        this.oHTML = $('#'+this.oHTMLId)[0];
        this.oHTML.inputs[this.oHTML.inputs.length] = this;
        this.oHTML.tabLoop[this.oHTML.tabLoop.length] = this.id;
    }).focus(function() {
        this.oHTML.lastFocus = this;
    }).keypress(function(event) {
        var keyLabel = $a.keyLabel(event);
        
        // On tab, find next input
        var timeout=0;
        if(keyLabel=='Tab') {
            var next = this.oHTML.tabNext[ this.id ];
            while(next.readOnly) {
                next = this.oHTML.tabNext[ next.id ];
                if(timeout++ > 50) break;
            }
            $(this).blur();
            $(next).focus();
            return false;
        }
        if(keyLabel=='ShiftTab') {
            var next = this.oHTML.tabPrev[ this.id ];
            while(next.readOnly) {
                next = this.oHTML.tabPrev[ next.id ];
                if(timeout++ > 50) break;
            }
            $(this).blur();
            $(next).focus();
            return false;
        }
    }).keyup(function(event) {
        // Key label comes first
        var keyLabel = $a.keyLabel(event);
        
        if(keyLabel == 'Esc') {
            x4MenuBar.eventHandler('onEscape');
            event.stopPropagation();
            return;
        }

        // Initialize this flag now
        var doFetch = false;
        if(keyLabel == 'ShiftUpArrow') {
            this.oHTML.setOrderBy(this.xColumnId,'ASC');
            doFetch = true;
        }
        if(keyLabel == 'ShiftDownArrow') {
            this.oHTML.setOrderBy(this.xColumnId,'DESC');
            doFetch = true;
        }
        
        if(this.oHTML.zRowId){
            if(keyLabel == 'UpArrow') {
                this.oHTML.moveUp();
                event.stopPropagation();
                return;
            }
            if(keyLabel == 'DownArrow') {
                this.oHTML.moveDown();
                event.stopPropagation();
                return;
            }
            if(keyLabel == 'PageUp') {
                this.oHTML.moveTop();
                event.stopPropagation();
                return;
            }
            if(keyLabel == 'PageDown') {
                this.oHTML.moveBottom();
                event.stopPropagation();
                return;
            }
            if(keyLabel == 'Enter') {
                $('#'+this.oHTML.zRowId).click(); // ENTER is click
                event.stopPropagation();
                return;
            }
        }
        
        // Do nothing on the keyup for SHIFT, that is confusing
        if(keyLabel=='Shift') return false;
        
        // Pass control to the fetch program
        this.oHTML.fetch(doFetch);
    }).keydown(function(event) {
        // Keydown is where you trap the control functions
        return x4.passUp(this,'keyDown',event);
    });
    

    var idxMax = self.tabLoop.length - 1;
    self.tabNext = [];
    self.tabPrev = [];
    self.tabNext[ self.tabLoop[0] ]     = $a.byId(self.tabLoop[1]);
    self.tabPrev[ self.tabLoop[0] ]     = $a.byId(self.tabLoop[idxMax  ]);
    self.tabNext[ self.tabLoop[idxMax]] = $a.byId(self.tabLoop[0]);
    self.tabPrev[ self.tabLoop[idxMax]] = $a.byId(self.tabLoop[idxMax-1]);
    for(var x = 1; x < idxMax; x++ ) {
        self.tabNext[ self.tabLoop[x] ] = $a.byId(self.tabLoop[x+1]);
        self.tabPrev[ self.tabLoop[x] ] = $a.byId(self.tabLoop[x-1]);
    }
    
    /* @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
     * 
     * Activation Code
     *
     * @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
     */
    self.activate=function() { 
        x4MenuBar.obj = this;
        
        if(this.xTableIdPar!='') {
            this.fetch();
        }

        $("#x4H1Top").html( x4dd.dd[this.xTableId].description);
        
        $(this).fadeIn(x4.fadeSpeed,function() {
            $("#button-sav").css('display','none');
            $("#button-snw").css('display','none');
            $("#button-sxt").css('display','none');
                
            if(this.lastFocus==false) {
                this.lastFocus=$(this).find(":input:first")[0];
            }
            $(this.lastFocus).focus();
        });
    }
    
    self.deactivate = function() {
        $(this.lastFocus).blur();
    }
    
   /*
    * Sets the sort order for searches.  Expects a
    * column name and literal "ASC" or "DESC".  If column
    * name is not passed it makes the first column
    * sortable
    *
    */
    self.setOrderBy = function(inputCol,direction) {
        if(inputCol==null) {
            var inputCol = '';
            if(this.inputs.length>0) {
                inputCol = this.inputs[0].xColumnId;
            }
            direction = 'ASC';
        }
        this.xSortCol = inputCol;
        this.xSortAD = direction;
    }
    /*
     * After defining it, invoke it
     *
     */
    self.setOrderBy();
    
       
    self.fetch = function(doFetch) {
        if(doFetch==null) doFetch=false;
        this.doFetch=doFetch;
        this.cntNoBlank = 0;
        
        // Initialize and then scan 
        $a.json.init('x4Page',this.xTableId);
        $(this).find(":input").each(function() {
            if(this.value!=this.xValue) {
                this.oHTML.doFetch = true;
            }
            if(this.value!='') {
                this.oHTML.cntNoBlank++;
            }
            this.xValue = this.value;
            $a.json.addParm('x4w_'+this.xColumnId,this.value);
        });
        
        if(this.xTableIdPar!='') {
            $a.json.addParm('tableIdPar',this.xTableIdPar);
            //if(typeof(this.xParent.xParent.pane1.skey)!='function') {
            //    var skey = this.xParent.xParent.xParent.pane1.skey();
            //}
            //else {
            //    var skey = this.xParent.xParent.pane1.skey();
            //}
            //$a.json.addParm('skeyPar',skey);
            this.doFetch=true;
        }
        
        console.log("hello "+this.cntNoBlank);
        if(this.doFetch) {
            if(this.cntNoBlank==0) {
                this.clear();
                return;
            }
            $a.json.addParm('sortCol',this.xSortCol);
            $a.json.addParm('sortAD' ,this.xSortAD);
            $a.json.addParm('x4Action','browseFetch');
            $a.json.addParm('x4Limit',300);
            if( $a.json.execute()) {
                var gridBodyId = this.xGridBodyId;
                $a.json.process( gridBodyId );
                this.skeys = $a.data.skeys;
                this.rowCount = $a.data.rowCount;
                
                // Tell x4Browse how many rows it has
                this.zRowCount = $a.byId(gridBodyId).rows.length;

                $('#'+gridBodyId).find('tr').click(function() {
                    x4MenuBar.eventHandler('editRow');
                }).mouseover( function() {
                    if(x4MenuBar.obj.zRowId) {
                        $("#"+x4MenuBar.obj.zRowId).removeClass('highlight');
                    }
                    $(this).addClass('highlight');
                    x4MenuBar.obj.zRowId = this.id;
                });
                
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
        $a.byId(this.xGridBodyId).innerHTML = '';
        $(this).find(":input").each(function() {
            this.value='';
            this.x_value='';
        });
    }
    
    /**
      * Move up a row or down a row, and return the skey
      * that was passed
      */
    self.moveUp = function() {
        var x = $a.byId(this.zRowId);
        if(x.previousSibling!=null) {
            $('#'+x.previousSibling.id).mouseover();
        }
        return this.skey();
    }
    self.moveDown = function() {
        var x = $a.byId(this.zRowId);
        if(x.nextSibling!=null) {
            $('#'+x.nextSibling.id).mouseover();
        }
        return this.skey();
    }
    self.moveTop = function() {
        if(this.rowId != $a.byId(this.xGridBodyId).firstChild.id) {
            $('#'+this.xGridBodyId+' tr:first').mouseover();
        }
        return this.skey();
    }
    self.moveBottom = function() {
        if(this.rowId != $a.byId(this.xGridBodyId).lastChild.id) {
            $('#'+this.xGridBodyId+' tr:last').mouseover();
        }
        return this.skey();
    }
    
    self.skey = function() {
        if(this.zRowId) {
            return this.zRowId.slice(6);
        }
        else { 
            return 0;
        }
    }
    
    self.onEscape = function() {
        if(this.zRowCount > 0 && this.xReturnAll!='Y') {
            this.clear();
        }
        else {
            this.deactivate();
            this.xParent.deactivate();
        }
    }

    /*
     * New row, copy row, delete row
     *
     */
    self.editRow = function() {
        this.deactivate();
        this.xParent.goDetail(this.skey(),'edit');
    }
    self.newRow = function() {
        this.deactivate();
        this.xParent.goDetail(0,'new');
    }
    self.copyRow = function() {
        this.deactivate();
        this.xParent.goDetail(this.skey(),'copy');
    }
    self.deleteRow = function() {
        if(!this.zRowId) {
            $a.dialogs.alert('I cannot delete because there is nothing selected.');
            $(this.xLastFocus).focus();
        }
        else {
            $a.json.init('x4Page',this.xTableId);
            $a.json.addParm('x4Action','delete');
            $a.json.addParm('skey',this.skey());
            if($a.json.execute()) {
                $a.dialogs.alert('The selected row was deleted.');
                this.fetch();
            }
        }
    }
}

/* ========================================================
 * Constructor Funtion x4Detail
 *
 * Shows details for some columns
 * ========================================================
 */
function x4Detail(self) {
    self.xTableId = self.xParent.xTableId;
    self.lastFocus = false;
    self.inputs = [];
    self.tabLoop = [ ];
    self.tabNext = { };
    self.tabPrev = { };
    
    // Get the grid pane
    if(self.xParent.xType=='x4TableTop') {
        self.rootParent = self.xParent;
        self.gridPane = self.xParent.pane1;
    }
    else { 
        self.rootParent = self.xParent.xParent;
        self.gridPane = self.xParent.xParent.pane1;
    }

    // Turn on focus tracking, tab loop, escape key and so forth
    $(self).find(":input").each(function() {
        this.oHTML = $('#'+this.oHTMLId)[0];
        this.oHTML.inputs[this.oHTML.inputs.length] = this;
        this.oHTML.tabLoop[this.oHTML.tabLoop.length] = this.id;
    }).focus(function() {
        this.oHTML.lastFocus = this;
        $(this).addClass('x4Focus');
    }).blur( function() {
        $(this).removeClass('x4Focus');
    }).keyup( function(event) {
        var keyLabel = $a.keyLabel(event);
        if(keyLabel=='') return;
        
        if(keyLabel=='Esc') {
            this.oHTML.onEscape();
        }
    }).keypress( function(event) {
        var keyLabel = $a.keyLabel(event);
        if(keyLabel=='') return;
        var labels ='PageUp PageDown CtrlPageDown CtrlPageUp';
        if(labels.indexOf(keyLabel)>-1) {
            this.oHTML.move(keyLabel);
        }
        
        // On tab, find next input
        var timeout=0;
        if(keyLabel=='Tab') {
            var next = this.oHTML.tabNext[ this.id ];
            while(next.readOnly) {
                next = this.oHTML.tabNext[ next.id ];
                if(timeout++ > 50) break;
            }
            $(this).blur();
            $(next).focus();
            return false;
        }
        if(keyLabel=='ShiftTab') {
            var next = this.oHTML.tabPrev[ this.id ];
            while(next.readOnly) {
                next = this.oHTML.tabPrev[ next.id ];
                if(timeout++ > 50) break;
            }
            $(this).blur();
            $(next).focus();
            return false;
        }
    });
            
    // Now that all iterations are done, populate the
    // tab loops
    var idxMax = self.tabLoop.length - 1;
    self.tabNext[ self.tabLoop[0] ]     = $a.byId(self.tabLoop[1]);
    self.tabPrev[ self.tabLoop[0] ]     = $a.byId(self.tabLoop[idxMax  ]);
    self.tabNext[ self.tabLoop[idxMax]] = $a.byId(self.tabLoop[0]);
    self.tabPrev[ self.tabLoop[idxMax]] = $a.byId(self.tabLoop[idxMax-1]);
    for(var x = 1; x < idxMax; x++ ) {
        self.tabNext[ self.tabLoop[x] ] = $a.byId(self.tabLoop[x+1]);
        self.tabPrev[ self.tabLoop[x] ] = $a.byId(self.tabLoop[x-1]);
    }

    /* @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
     * 
     * Activation Code
     *
     * @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
     */
    self.activate=function(skey,action) {
        x4MenuBar.obj = this;

        // Before displaying, do possible fetch         
        if(skey > 0) {
            this.fetchRow(skey);
            this.displayRow();
        }
        if(action == 'new' || action=='copy') {
            if(action=='new')
                this.setDefaults(true);
            else
                this.setDefaults();
            this.setMode('new');
        }
        else {
            this.setMode('upd');
        }

        $("#button-sav").css('display','');
        $("#button-snw").css('display','');
        $("#button-sxt").css('display','');
        
        
        $(this).fadeIn(x4.fadeSpeed,function() {
            if(this.lastFocus) {
                $(this.lastFocus).focus();
            }
            else {
                $(this).find(":input:not([@readonly]):first").focus();
            }
        });
    }
    self.deactivate = function() {
        $("#button-new").css('display','');
        $("#button-del").css('display','');
        $("#button-cpy").css('display','');
        $(this.lastFocus).blur();
        this.rootParent.goGrid();
    }
    
    
    self.move = function(keyLabel) {
        if(this.tryToSave()) {
            if(     keyLabel=='PageUp')  
                var skey = this.gridPane.moveUp();
            else if(keyLabel=='PageDown')
                var skey = this.gridPane.moveDown();
            else if(keyLabel=='CtrlPageUp')
                var skey = this.gridPane.moveTop();
            else if(keyLabel=='CtrlPageDown')
                var skey = this.gridPane.moveBottom();
            if(skey!=this.skey) {
                this.fetchRow(skey);
            }
        }
    },
    
    self.onEscape = function() {
       this.deactivate(); 
    },
    
    // Magic number alert: zero equals a new row, insert mode
    self.fetchRow = function(skey) {
        this.skey = skey;
        
        // If not a new row, go fetch it
        $a.json.init();
        $a.json.addParm('x4Page',this.xTableId);
        $a.json.addParm('x4Action','fetchRow');
        $a.json.addParm('x4w_skey',skey);
        $a.json.execute(true);
        this.displayRow();
        
        // Tell child tables that our PK is default
        var apks = x4dd.dd[this.xTableId].pks.split(',');
        var row  = $a.data.row;
        for(var idx in x4dd.dd[this.xTableId].fk_children) {
            var tabChild = x4dd.dd[this.xTableId].fk_children[idx].table_id;
            if(typeof(x4dd.dd[tabChild])=='undefined') continue;
            var dd = x4dd.dd[tabChild];
            for(var pkidx in apks) {
                var pk = apks[pkidx];
                x4dd.dd[tabChild].flat[pk].automation_id='DEFAULT';
                x4dd.dd[tabChild].flat[pk].auto_formula = row[pk];
            }
        }
    },
    
    self.displayRow = function() {
        var skeys=$a.aProp(this.gridPane,'skeys',[]);
        var rowNow = $a.aProp(skeys,this.gridPane.skey(),'0')+1;
        var text = 'Row '+rowNow+' of '+this.xParent.pane1.rowCount;
        $("#x4RowInfoText").html(text);
        
        var row = $a.data.row;
        $(this).find(":input").each(function() {
            var row = $a.data.row;
            var id    = this.id;
            var input = this;
            var column_id = this.xColumnId;
            var value = $a.aProp(row,column_id,'');
            if(value==null) value='';
            if(input.xTypeId=='dtime') {
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
    
    self.deleteRow = function() {
        $a.json.init('x4Page',this.obj.xTableId);
        $a.json.addParm('x4Action','delete');
        $a.json.addParm('skey',this.skey);
        if($a.json.execute()) {
            $a.dialogs.alert('The selected row was deleted.');
            this.deactivate();
        }
    },
    
    self.newRow = function() {
        this.setDefaults(true);  // clear
        this.setMode('new');
    },
    
    self.saveRow = function() {
        if(this.tryToSave()) {
            this.displayRow();
            this.setMode('upd');
        }
    },
    
    self.saveRowAndNewRow = function() {
        if(this.tryToSave()) {
            this.newRow();
        }
            
    },
    
    self.saveRowAndExit = function() {
        if(this.tryToSave()) {
            this.deactivate();
        }
        
    },
    
    self.copyRow = function() {
        this.setMode('new');
    },
    
    self.setDefaults = function(blank) {
        for(var idx in this.inputs) {
            var inp = this.inputs[idx];
            var col = inp.xColumnId;
            var autoid = x4dd.dd[this.xTableId].flat[col].automation_id;
            if(autoid=='DEFAULT') {
                inp.value = x4dd.dd[this.xTableId].flat[col].auto_formula;
            }
            else {
                if(blank) {
                    inp.value = '';
                }
            }
            inp.x_value = inp.value;
        }
    },
    
    self.setMode= function(mode) {
        // Buttons
        if(mode=='new') {
            this.skey=0;
            $("#button-new").css('display','none');
            $("#button-del").css('display','none');
            $("#button-cpy").css('display','none');
        }
        else {
            $("#button-new").css('display','');
            $("#button-del").css('display','');
            $("#button-cpy").css('display','');
        }
        
        // Title
        if(mode=='new') {
            var title = 'New ' + x4dd.dd[this.xTableId].singular;
        }
        else {
            var col1  = x4dd.firstPkColumn(this.xTableId);
            var title = x4dd.dd[this.xTableId].singular 
                + ": "+ $a.data.row[col1]; 
            if(x4dd.pkColumnCount(this.xTableId)>1) {
                title += '...';
            }
        }
        $("#x4H1Top").html(title);
        
        // Row counter
        if(mode=='new') {
            $("#x4RowInfoText").html(title);
        }


        // Set the read-only and the coloring, and defaults for new
        $(this).find(":input").each( function() {
            var inp = this;
            var col = inp.xColumnId;
            var ro = mode=='new' ? inp.xRoIns : inp.xRoUpd;
            if(ro==' ') ro = 'N';
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
    
    self.tryToSave = function() {
        var mustSave = false;
        $a.json.init();
        for(var idx in this.inputs) {
            var input = this.inputs[idx];
            if ((input.value != input.x_value) || this.skey==0) {
                mustSave=true;
                $a.json.addParm('x4v_'+input.xColumnId,input.value);
            }
        }
        
        if(!mustSave) return true;
        
        if (mustSave) {
            $a.json.addParm('x4v_skey',this.skey);
            $a.json.addParm('x4Page'  ,this.xTableId);
            $a.json.addParm('x4Action','update');
            $a.json.execute(true);
            if($a.json.jdata.error.length==0)
                return true;
            else 
                return false;
        }
        
        return true;
    }
}

