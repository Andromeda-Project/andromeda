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
   along with Andromeda; if not, write to the
   Free Software Foundation, Inc.
   51 Franklin St, Fifth Floor,
   Boston, MA  02110-1301  USA 
   or visit http://www.gnu.org/licenses/gpl.html
\* ================================================================== */

/*
 *
 *  These are the only public vars in x4.
 *
 */
// SYJOH
//var x4http       = false;
//var x4httpData   = false;
//var x4httpAfterHandler= false;
//var x4httpAfterObject = false;
//var x4httpAfterMethod = false;

/*
 *  The boot routine.  This code assumes the user has just
 *  logged in and it asks for the menu
 */

function x4Boot() {
    // Ajax initialization
    // SYJOH
    //if(navigator.appName == "Microsoft Internet Explorer")
    //    x4http = new ActiveXObject("Microsoft.XMLHTTP");
    //else
    //    x4http = new XMLHttpRequest();
        
    // Create keystroke handler
    x4.addEventListener(document,'keypress',x4.bodyKeyPress);

    // Execute a request to get the menu
    x4Menu.init();
}

/*
 *
 *  This is the main object that handles framework functions, 
 *  including library functions.
 *
 */
var x4 = {
    data: [],     // data returned by ajax calls
    messages: [], // messages returned by ajax calls
    dd: [],       // data dictionaries returned by ajax calls
    
    /*
     *  x4.ajax: make call to x4Index.php
     *
     *  parm1:  the string, s/b 'get1=val&get2=val'
     *  parm2:  (optional) function reference to handler
     *
     */
    /* SYJOH
    ajax: function(getString,pHandler,pObj,pMethod) {
    //if(navigator.appName == "Microsoft Internet Explorer")
    //    x4http = new ActiveXObject("Microsoft.XMLHTTP");
    //else
    //    x4http = new XMLHttpRequest();
        //x4httpData  = false;

        // Set various method handlers
        x4httpAfterHandler = pHandler==null ? false : pHandler;
        x4httpAfterObject  = pObj==null     ? false : pObj;
        x4httpAfterMethod  = pMethod==null  ? false : pMethod;     

        //alert("ajax called for "+pMethod);

        x4http.open('get', 'x4index.php?'+getString);
        x4http.onreadystatechange = x4.ajaxResponseHandler;
        x4http.send(null);
        
        //alert("ajax sent, exiting");
    },
    */
    
    /*
     *  x4.ajaxResponseHandler: the default return handler
     *     Will attempt to evaluate JSON response and hand it off
     *
     */ 
     /* SYJOH
     
    ajaxResponseHandler: function() {
        if(x4http.readyState != 4) return;
        //alert("ajax returned");
        var dataIsOK=false;
        try {
            eval('x4httpData = ('+x4http.responseText+')');
            dataIsOK=true;
        }
        catch(e) {
            alert('Could not process server response!');
        }
          
        //if(!dataIsOK) return;
        
        if(typeof(x4httpData.message)!='undefined') {
            // Save the messages from the most recent call
            x4.messages = x4httpData.message;
            
            if(typeof(x4httpData.message.error)!='undefined') {
                var msg="ERRORS REPORTED:";
                for(idx in x4httpData.message.error) {
                    msg+="\n\n"+x4httpData.message.error[idx];
                    x4.debug(x4httpData.message.error[idx]);
                }
                alert(msg);
            }
            if(typeof(x4httpData.message.debug)!='undefined') {
                x4.debug("Server debug messages follow:");
                for(idx in x4httpData.message.debug) {
                    x4.debug(x4httpData.message.debug[idx]);
                }
                x4.debug("End of server debug messages.");
            }
        }
        
        // Save data dictionary arrays
        var dd = x4.getProperty(x4httpData,'dd', { } );
        for(var ddidx in dd) {
            x4DD.tables[ddidx] = dd[ddidx];
        }
        
        // Save the data from the most recent call 
        if(typeof(x4httpData.data)!='undefined') {
            x4.data = x4httpData.data;
        }
        
        if(x4httpAfterHandler) {
            x4httpAfterHandler();
            x4httpAfterHandler = false;
        }
        if(x4httpAfterObject) {
            //alert("supposed to be goin to "+x4httpAfterMethod);
            x4httpAfterObject[x4httpAfterMethod]();
            x4httpAfterObject = false;
        }
    },
    */
    
    /*
     * x4.ajaxErrors: returns array of errors or false
     *
     * no parameters
     *
     */
    ajaxErrors: function() {
        if(typeof(x4httpData.message)=='undefined') {
            return false;
        }
        if(typeof(x4httpData.message.error)=='undefined') {
            return false;
        }
        return x4httpData.message.error;
    },

    /*
     *
     *  Set the status message
     *  x4.setStatus()
     *
     */
    setStatus: function(text) {
        x4.byId('statusLeft').innerHTML = text;
    },

    /*
     *
     * x4.bodyKeyPress.  All body key press events go here, and from 
     *                here they are routed to the current display
     *                layer
     *
     */
    bodyKeyPress: function(e) {
        var Label = x4.keyLabel(e);
        x4.debug("x4.bodyKeyPress: INVOKED with "+Label,'keyboard');
        var xx = x4Layers.displayLayers.length-1;
        if(x4Layers.displayLayers[xx].bodyKeyPress(e)) return;
        x4.debug("x4.bodyKeyPress: Layer did not handle, continuing",'keyboard');
        
        keycode = x4.keyCode(e);
       
        // Handle function keys from f1 to f12
        if(keycode >= 112 && keycode <= 123) {
            var objnum = keycode - 111;
            var objname= 'object_for_f'+objnum.toString();
            obj = x4.byId(objname);
            if(obj) {
                x4.debug("x4.bodyKeyPress: Invoking "+objname,'keyboard');
                obj.onclick(e);
                e.preventDefault();
                return false;
            }
        }
       
        if(keycode==13) {
            obj = x4.byId('object_for_enter');
            if(obj) {
                x4.debug("x4.bodyKeyPress: Invoking object_for_enter",'keyboard');
                obj.onclick();
                return false;
            }
        }
        
        return false;
    },
    
    
    /*
     *
     * -------------------------------------------------
     * Utilties and conveniences
     * -------------------------------------------------
     *
     */
    // x4.byId
    byId: function(id) {
        return document.getElementById(id);
    },
    
    // x4.debug
    debug: function(msg,msgType) {
        if(msgType==null) msgType='all';
        if(typeof(x4.debugTypes[msgType])=='undefined') {
            x4.debugTypes[msgType] = true;
        }
        if(x4.debugTypes.all || x4.debugTypes[msgType]) {
            if(typeof(console)!='undefined') {
                console.log(msgType+": "+msg);
            }
        }
    },
    debugTypes: { 
        all: false,
        keyboard: false
    },
    
    // x4.getProperty
    // Return a property if it exists
    getProperty: function(object,property,defvalue) {
        defvalue = defvalue==null ? false : defvalue;
        if(object == null) return defvalue
        if(typeof(object[property])=='undefined') {
            return defvalue;
        }
        else return object[property];
    },
    
    // x4.getSingular
    // Make a singular name for a table
    getSingular: function(pluralName) {
        return pluralName.slice(0,pluralName.length-1);
    },
    
    // x4.createElement
    createElement: function(type,parent,innerHTML) {
        var elm = document.createElement(type);
        if(innerHTML != null) {
            elm.innerHTML = innerHTML;
        }
        if(parent != null) {
            parent.appendChild(elm);
        }
    },
    
    
    appendBR: function(parent) {
        var br = document.createElement('BR');
        parent.appendChild(br);
    },
    appendLink: function(parent,text,url) {
        var a = document.createElement('A');
        a.href=url;
        a.innerHTML = text;
        parent.appendChild(a);
        return a;
    },
    
    // x4.addEventListener
    addEventListener: function(element, type, expression,uc) {
        if(uc==null) { uc=false; }
        if (element.addEventListener) { // Standard
            element.addEventListener(type, expression, uc);
            return true;
        } 
        else if (element.attachEvent) { // IE
            element.attachEvent('on' + type, expression);
            return true;
        }
        else return false;
    },

    // x4.removeEventListener
    // function contributed by Don Organ 10/29/07
    removeEventListener: function(el, eType, fn) {
        if (el.removeEventListener) {
            el.removeEventListener(eType, fn, false);
        }
        else if ( el.detachEvent ) {
            el.detachEvent( 'on' + eType, fn );
        } 
        else {
            return false;     
        }
    },
    
    // x4.KeyCode
    // return keycode, this is the one you
    // need to catch function keys, arrows keys and 
    // so forth.
    keyCode: function(e) {
       if(window.event)
          // IE
          return window.event.keyCode;  
       else
          // firefox
          return e.keyCode;
    },
    // x4.CharCode
    // Char code is for letters and numbers
    charCode: function(e) {
       if(window.event)
          // IE
          return window.event.charCode;  
       else
          // firefox
          return e.charCode;
    },
    
    // x4.KeyLabel
    // Returns a descriptive name of special keys, such as
    // 'CapsLock' or 'ShiftEnd'.
    //
    keyLabel: function(e) {
        if(window.event)
            var x = window.event.keyCode;         // IE
        else
            var x = e.keyCode;                    // firefox
    
        var x4Keys = new Object();
        x4Keys['9']  = 'Tab';
        x4Keys['13'] = 'Enter';
        x4Keys['16'] = '';   // actually Shift, but prefix will take care of it
        x4Keys['17'] = '';   // actually Ctrl,  but prefix will take care of it
        x4Keys['18'] = '';   // actually Alt,   but prefix will take care of it
        x4Keys['20'] = 'CapsLock';
        x4Keys['27'] = 'Esc';
        x4Keys['33'] = 'PageUp';
        x4Keys['34'] = 'PageDown';
        x4Keys['35'] = 'End';
        x4Keys['36'] = 'Home';
        x4Keys['37'] = 'LeftArrow';
        x4Keys['38'] = 'UpArrow';
        x4Keys['39'] = 'RightArrow';
        x4Keys['40'] = 'DownArrow';
        x4Keys['45'] = 'Insert';
        x4Keys['46'] = 'Delete';
        x4Keys['112']= 'F1' ;
        x4Keys['113']= 'F2' ;
        x4Keys['114']= 'F3' ;
        x4Keys['115']= 'F4' ;
        x4Keys['116']= 'F5' ;
        x4Keys['117']= 'F6' ;
        x4Keys['118']= 'F7' ;
        x4Keys['119']= 'F8' ;
        x4Keys['120']= 'F9' ;
        x4Keys['121']= 'F10';
        x4Keys['122']= 'F11';
        x4Keys['123']= 'F12';
    
        // If they did not hit a key we know about, return empty
        if(typeof(x4Keys[x])=='undefined') return '';
    
        // otherwise put on any prefixes and return
        var prefix = '';
        if(e.ctrlKey)  prefix = 'Ctrl';
        if(e.altKey)   prefix += 'Alt';
        if(e.shiftKey) prefix += 'Shift';
        
        var retval = prefix + x4Keys[x];
        return retval;
    },  
    
    // ------------------------------------------------------
    // Popup a window and display something in that.
    // ------------------------------------------------------
    popup: function(url,caption){
        w = 770;
        h = 700;
        mytop = 100;
        myleft = 100;
        settings="width=" + w + ",height=" + h + ",top=" + mytop + ",left=" + myleft + ", " +
            "scrollbars=yes,resizable=yes";
        win=window.open(url,'Popup',settings);
        win.focus();
    },

    // ------------------------------------------------------
    // Database functions are put in here as a separate
    // object so the object persists across weird scoping
    // of ajax calls
    // ------------------------------------------------------
    db: {
        getString: '',  // The only actual property
        returnRow: false, // ok I lied, the 2nd of only 2 properties
        
        sql: function(query,freturn,oreturn,omethodName) {
            this.getString='x4xAjax=sql';
            this.getString+='&x4xSQL='+encodeURIComponent(query);
            this.execute(freturn,oreturn,omethodName);
        },
        
        // Initiate a new database command
        init: function(table,action) {
            x4.data[table]=[];
            this.returnRow = false;
            this.getString
                ="x4xAjax="+action
                +"&x4xTable="+table;
        },
        
        setReturnRow: function() {
            this.returnRow = true;
            this.getString+='&x4xRetRow=1';
        },
        
        // Add a value, or add a filter value
        addValue: function(name,value) {            
            this.getString+="&x4c_"+name+"="+encodeURIComponent(value);
        },
        addFilter: function(name,value) {
            this.getString+="&x4w_"+name+"="+encodeURIComponent(value);
        },
        addParm: function(name,value) {
            this.getString+="&"+name+"="+encodeURIComponent(value);
        },
    
        // Execute the database command
        execute:  function(freturn,oreturn,omethodName) {
            x4.ajax(this.getString,freturn,oreturn,omethodName);
        },
        
        // Fetch the rows that may have been returned from a db command
        getRows: function(table) {
            return x4.getProperty(x4.data,table,[])
            /*
            if(typeof(x4.data[table])=='undefined') {
                return [];
            }
            else {
                return x4.data[table];
            }
            */
        }
    }
}   // var x4 //


/*
 * Object x4SYJOH
 *
 * This object communicates directly with the web server
 * to make syjoh requests and process the results.  
 *
 * SYJOH stands for (SY)nchronous (J)SON (O)over (H)ttp.
 *
 */
var x4SYJOH = {
    // This is a round robin of the last 10 server responses
    // 
    responses: [ 
        false,false, false, false, false, 
        false,false, false, false, false ],
        
    // The current response we are filling in.  Initialize
    // at nine so it flips to zero on the first call
    respnum: 9,
    // response map, use this instead of having to
    // code a round-robin figure-outer-er
    responsemaps: [
        [ 0, 9, 8, 7, 6, 5, 4, 3, 2, 1 ], 
        [ 1, 0, 9, 8, 7, 6, 5, 4, 3, 2 ], 
        [ 2, 1, 0, 9, 8, 7, 6, 5, 4, 3 ], 
        [ 3, 2, 1, 0, 9, 8, 7, 6, 5, 4 ], 
        [ 4, 3, 2, 1, 0, 9, 8, 7, 6, 5 ], 
        [ 5, 4, 3, 2, 1, 0, 9, 8, 7, 6 ], 
        [ 6, 5, 4, 3, 2, 1, 0, 9, 8, 7 ], 
        [ 7, 6, 5, 4, 3, 2, 1, 0, 9, 8 ], 
        [ 8, 7, 6, 5, 4, 3, 2, 1, 0, 9 ], 
        [ 9, 8, 7, 6, 5, 4, 3, 2, 1, 0 ]
     ],
        
    /*
     * function x4SYJOH.serverCall()  
     *
     *  This is the core routine to make requests from
     *  the server. 
     * 
     */
    serverCall: function(getString) {
        // First things first, create the object                
        if(navigator.appName == "Microsoft Internet Explorer")
            x4http = new ActiveXObject("Microsoft.XMLHTTP");
        else
            x4http = new XMLHttpRequest();

        x4http.open('get', 'x4index.php?'+getString);
        x4http.onreadystatechange = this.obligatoryHandler
        x4http.send(null);
        
        // Here is the basic heresy.  We go into a loop 
        // with no way to prevent burning CPU cycles.  The 
        // obvious assumption here is that the call will succeed
        while(x4http.readyState != 4) {
            // Gosh, I sure wish Javascript had some kind of
            // Sleep() function, I would do sleep(100) here
        }

        // Initialize the response tracking 
        this.respnum = (this.respnum==9) ? 0 : this.respnum+1;
        this.responses[this.respnum] = { 
            good: false,
            data: { },
            bad: ''
        }
        var x = false;
        try {
            eval('x = ('+x4http.responseText+')');
        }
        catch(e) {
            x4Popups.alert('Could not process server response');
            this.responses[this.respnum].bad = x4http.responseText;
            return false;
        }
        
        // If we are still here, the server response was 
        // intelligible, put it into the response history
        this.responses[this.respnum].data = x;
        this.responses[this.respnum].good = true;

        // We may wish to report things to the user:
        var messages = x4.getProperty(x,'message',false);
        if(messages) {
            var emsgs = x4.getProperty(message,'error',false);
            var text  = "ERRORS REPORTED:";
            x4.debug("Server errors follow:");
            for(idx in emsgs) {
                text+="\n\n"+emsgs[idx];
                x4.debug(emsgs[idx]);
            }
            x4Popups.alert(msg);
            
            var dmsgs = x4.getProperty(message,'debug',false);
            if(false) {
                x4.debug("Server debug messages follow:");
                for(idx in dmsgs) {
                    x4.debug(dmsgs[idx]);
                }
                x4.debug("End of server debug messages.");
            }
        }
        
        // Save data dictionary arrays
        var dd = x4.getProperty(x,'dd', { } );
        for(var ddidx in dd) {
            x4DD.tables[ddidx] = dd[ddidx];
        }
    },
    
    /*
     * function x4SYJOH.obligatoryHandler
     *
     * A do-nothing routine to satisfy the XML request 
     * object's need for a handler
     */
    obligatoryHandler: function() {
         // Nothing happens here
    }
}


/*
 *
 *  HTML Library 
 *
 */
var x4HTML = {
    tableRowFromArray: function(theArray,makeTH) {
        var telem = (makeTH == null) ? 'TD' : 'TH';
        var tr = document.createElement('TR');
        for(var idx in theArray) {
            var cell = document.createElement(telem);
            cell.innerHTML = theArray[idx];
            tr.appendChild(cell);
        }
        return tr;
    },
    
    // ------------------------------------------------------
    // Create and return an element. Replaces the annoying
    // createElement/appendChild pair of calls
    // ------------------------------------------------------
    createElementId: 0,
    createElement: function(parent,type,id,className,innerHTML) {
        if(id==null) {
            this.createElement_id++;
            id = type + "_" + this.createElementId;
        }
        
        // Create and assign id and class
        var x = document.createElement(type);
        x.id = id;
        if (className!=null) x.className = className;
        
        // Various settings for different kinds of stuff
        if(type=='td' || type=='TD') {
            x.style.verticalAling='top';
        }
        if(type=='table' || type=='TABLE') {
            x.style.width="100%";
        }
            
        // InnerHTML, if given
        if(innerHTML != null) x.innerHTML = innerHTML;
            
        // Append it to the parent and return it
        parent.appendChild(x);
        return x4.byId(id);
    },
    
    // creates a table for createInputsTR
    createInputsTable: function(parent) {
        var table = this.createElement(parent,'TABLE');
        table.className = 'x4Detail';
        return table;
    },
    
    
    // Creates a caption/input pair in a TR
    createInputsTr: function(parent,table,column,context) {
        var row = this.createElement(parent,'TR');
        
        // Put a caption into the left side
        var td1 = this.createElement(row,'TD');
        td1.style.textAlign = 'right';
        td1.innerHTML = x4DD.getColumnProperty(table,column,'description');
        
        var td2 = this.createElement(row,'TD');
        td2.style.textAlign = 'left';
        var colinfo = x4DD.getColumnInfo(table,column);
        var context = x4Layers.getCurrentDisplayLayer();
        var input   = x4Input.make(colinfo,context);
        input.id = 'inp_'+column;
        input.setMode('upd');
        td2.appendChild(input);
    },
    
    createGrid: function(parent,id) {
        if(id==null) {
            this.createElement_id++;
            id = "grid_" + this.createElementId;
        }
        
        // Create and assign id and class
        var x = document.createElement('TABLE');
        parent.appendChild(x);
        var z = document.createElement('tbody');
        x.appendChild(z);
        //var z = x4.createElement(x,'tbody');
        x.className = 'grid';
        x.id = id;
        x.x_rows = [ ];         
        x.x_rowCurrent = null;
        x.x_rowCount   = 0;
        // Create a list of column definitions as an empty array
        x.x_columns = [ ];    
        x.x_rowh = 0;
        x.x_row1 = 1;
        
        // Create the header row
        x.x_headers = x.insertRow(0);
        x.x_headers.className = 'header';
            
        // Method for adding a column as an entry in a row
        x.createTableColumn = function(table,column) {
            var idx = this.x_columns.length;
            this.x_columns[idx] = { 
                kind: 'column',
                table: table,
                column: column                
            }
            
            var x = this.x_headers.insertCell(this.x_headers.childNodes.length);
            x.innerHTML = x4DD.getColumnProperty(table,column,'description');
        }

        // This method clears all rows.  If we have headers
        // we have to save them
        x.clear = function() {
            while(this.rows.length > this.x_row1) {
                this.deleteRow(this.x_row1);
            }
        }
        
        // Populate the grid with data from a row
        x.populateFromRows = function(rows) {
            // save data for later
            this.x_rows = rows;
            this.x_rowCount = rows.length;
            
            // Generate the HTML;
            for(var idx in rows) {
                var row = rows[idx];
                idx = Number(idx);
                
                var tr = this.insertRow(this.rows.length);
                tr.id = 'tr_'+ (idx+1);
                tr.x_rownum = idx+1;
                tr.x_grid = this;
                tr.x_className = '';
                for(var colidx in this.x_columns) {
                    var table = this.x_columns[colidx].table;
                    var column = this.x_columns[colidx].column
                    var iHTML = x4.getProperty(row,column,'');
                    var tdid = tr.id + "_" + column;
                    var td = x4HTML.createElement(
                        tr,'td',tdid,null,iHTML
                    );
                    var type = x4DD.getColumnProperty(table,column,'type_id');
                    if(type=='numb' || type=='int' || type=='money') {
                        td.style.textAlign='right';
                    }
                }
                
                tr.onmouseover = function() {
                    this.x_grid.selectRowByObj(this);
                }
                this.appendChild(tr);
            }
            
            this.selectRowByNumber(this.x_row1);
        }
        
        // If you know the row you want, select it here
        x.selectRowByNumber = function(rowNum) {
            row = x4.byId('tr_'+rowNum);
            this.selectRowByObj(row);
        }
        x.selectRowByObj = function(row) {
            if(this.x_rowCurrent != null) {
                this.x_rowCurrent.className = this.x_rowCurrent.x_className;
            }
            row.className = 'current';
            this.x_rowCurrent = row;
        }
        
        // Next and previous row selection
        x.nextRow = function() {
            if(this.x_rowCurrent == null) {
                this.selectRowByNumber(this.x_row1);
            }
            else {
                var rowNow = Number(this.x_rowCurrent.x_rownum);
                if(rowNow < Number(this.x_rowCount)) {
                    this.selectRowByNumber(Number(rowNow) + 1);
                }
            }
        }
        x.prevRow = function() {
            if(this.x_rowCurrent == null) {
                this.selectRowByNumber(this.x_row1);
            }
            else {
                var rowNow = this.x_rowCurrent.x_rownum;
                if(Number(rowNow) > Number(this.x_row1)) {
                    this.selectRowByNumber(Number(rowNow) - 1);
                }
            }
        }
        return x4.byId(id);
    }
}  // var x4HTML


/*
 *
 *  Popup hooks, so later on we can do something nicer 
 *
 */
var x4Popups = {
    alert: function(text,title) {
        alert(text);
    },
    confirm: function(text,title) {
        return confirm(text);
    },
    waitBox: function(text) {
        var ih = window.innerHeight;
        var iw = window.innerWidth;
        
        var div1 = document.createElement('div');
        div1.id = 'waitBox1';
        div1.style.position = 'absolute';
        div1.style.backgroundColor = 'black';
        div1.style.opacity = 0.5;
        div1.style.left = 0;
        div1.style.top  = 0;
        div1.style.height = window.innerHeight+"px";
        div1.style.width  = window.innerWidth+"px";
        div1.style.zIndex = 100;
        document.body.appendChild(div1);

        var div2 = document.createElement('div');
        div2.id = 'waitBox2';
        div2.style.position = 'absolute';
        div2.style.backgroundColor = 'white';
        div2.style.border = '2px solid blue';
        div2.style.opacity = 1;
        var left = Math.round(iw / 2) - 125;
        var right= Math.round(ih / 2) - 75;
        div2.style.left = left + "px";
        div2.style.top  = right + "px";
        div2.style.width  = "250px";
        div2.style.height = "150px";
        div2.style.zIndex = 200;
        div2.style.padding = "10px";
        document.body.appendChild(div2);
        x4HTML.createElement(div2,'b',null,null,text);
        setTimeout("",0.1);

    },
    clearWaitBox: function() {
        setTimeout(test,300);
    }
}

function test() {
        var x = x4.byId('waitBox2');
        x.parentNode.removeChild(x);
        var x = x4.byId('waitBox1');
        x.parentNode.removeChild(x);
}

/*
 *
 *  Data dictionary long-term storage and routines 
 *
 */
var x4DD = {        
    tables: { },
    
    // Notice no error trapping. We want an error to show up immediately
    // on the screen, then it can be debugged in firebug.
    getTableProperty: function(table,property) {
        return this.tables[table][property];
    },

    // Notice no error trapping. We want an error to show up immediately
    // on the screen, then it can be debugged in firebug.
    getColumnProperty: function(table,column,property) {
        return this.tables[table].flat[column][property];
    },
    setColumnProperty: function(table,column,property,value) {
        this.tables[table].flat[column][property] = value;
    },

    // Notice no error trapping. We want an error to show up immediately
    // on the screen, then it can be debugged in firebug.
    getColumnInfo: function(table,column,property) {
        return this.tables[table].flat[column];
    }
}


/*
 *
 *  String Handling Functions 
 *
 */
var x4String = {

    // From snippets.dzone.com/posts/show/701
    // x4String.LTrim
    lTrim: function(value) {
        var re = /\s*((\S+\s*)*)/;
        return value.replace(re, "$1");
	},

    // From snippets.dzone.com/posts/show/701
    // x4String.RTrim
    rTrim: function( value ) {
        var re = /((\s*\S+)*)\s*/;
        return value.replace(re, "$1");
	},
    
    // From snippets.dzone.com/posts/show/701
    // x4String.trim
    trim: function( value ) {
        return x4String.lTrim(x4String.rTrim(value));
	}    
}   // var x4String //

/*
 *
 *  Date Handling Functions 
 *
 */
var x4Date = {
    strmmddyyyy: function(thedate) {
        var retval = thedate.toLocaleDateString();
        return retval;
    }
}



/*
 *
 *  Database Commands 
 *
 */
function x4db(table,action) {
    this.getString
        ="x4xAjax="+action
        +"&x4xTable="+table;
    
    this.addValue = function(name,value) {
        this.getString+="&x4c_"+name+"="+encodeURIComponent(value);
    }

    this.addFilter = function(name,value) {
        this.getString+="&x4w_"+name+"="+encodeURIComponent(value);
    }
    
    this.execute = function(freturn,oreturn,omethodName) {
        x4.ajax(this.getString,freturn,oreturn,omethodName);
    }
}


/*
 *
 *  x4Layers 
 *
 */
var x4Layers = {
    // Two blank lists to get started
    displayLayers: [ ],
    ddPages: { },
    
    /*
     *
     * x4Layers.prepareDisplayLayer
     * Make a layer and save the description
     *
     */
    prepareDisplayLayer: function(page, desc) {
        this.nextLayer = new displayLayer(page, desc);
    },
    
    /*
     *
     * x4Layers.pushDisplayLayer
     * Push a display onto the stack and display it
     *
     */
    pushDisplayLayer: function(obj) {
        var index = this.displayLayers.length;
        this.displayLayers[index] = obj;
        
        if(typeof(obj.HTML)!='undefined') {
            x4.byId('andromeda_main_content').innerHTML = obj.HTML;
        }
        else {
            x4.byId('andromeda_main_content').innerHTML = '';
            x4.byId('andromeda_main_content').appendChild(obj.h);
        }
        document.title = obj.title;   
        obj.onPush();
    },
    
    abortNewLayer: function() {
        this.refreshDisplayLayer();
    },
    
    /*
     *
     * x4Layers.popDisplayLayer
     * Destroy top display layer and restore layer below it
     *
     */
    popDisplayLayer: function() {
        this.displayLayers.pop();    // kills the top entry
        this.refreshDisplayLayer();
    },
    
    refreshDisplayLayer: function() {
        var index = this.displayLayers.length - 1;
        var obj   = this.displayLayers[ index ];
        
        x4.byId('andromeda_main_content').innerHTML = '';
        x4.byId('andromeda_main_content').appendChild(obj.h);
        document.title = obj.title;
        obj.onRestore();
    },
    
    getCurrentDisplayLayer: function() {
        return this.displayLayers[ this.displayLayers.length - 1 ];  
    }
    
}   // var x4Layers //

/*
 *
 *  x4Input
 *  Make individual inputs 
 *
 */
var x4Input = {
    // x4Input.Make
    make: function(colinfo,context) {
        // To keep code functioning right
        colinfo.name = colinfo.column_id;
        
        // determine what input to create
        if(colinfo.type_id=='gender' ) {
            var input = document.createElement('SELECT');
            var opt = document.createElement('OPTION');
            opt.value = 'M';
            opt.innerHTML = 'M'
            input.appendChild(opt);
            opt = document.createElement('OPTION');
            opt.value = 'F';
            opt.innerHTML = 'F';
            input.appendChild(opt);
        }
        else if(colinfo.type_id=='cbool' ) {
            var input = document.createElement('SELECT');
            var opt = document.createElement('OPTION');
            opt.value = 'Y';
            opt.innerHTML = 'Y'
            input.appendChild(opt);
            opt = document.createElement('OPTION');
            opt.value = 'N';
            opt.innerHTML = 'N';
            input.appendChild(opt);
        }
        else if(colinfo.type_id == 'time' ) {
            var input = document.createElement('SELECT');
            var x = 0;
            while(x < 1440) {
                var opt = document.createElement('OPTION');
                opt.value = x;
                opt.innerHTML = this.tStr[x];
                input.appendChild(opt);
                x+=15;
            }
        }
        else {
            var input = document.createElement('INPUT');
            input.size = colinfo['dispsize'] > 24 ? 24 : colinfo['dispsize'];
            input.maxlength = colinfo['dispsize'];
            
            // Some type-specific corrections
            if(colinfo.type_id == 'dtime') {
                input.size = 25;
            }
        }
        
        // Create all of the nifty properties we use to extend this guy's
        // behavior, and copy things from data dictionary
        input.x_dd       = colinfo;
        input.x_save     = '';
        input.x_mode     = '';
        input.x_error    = false;
        input.x_errorspan= false;
        input.x_old      = '';
        
        // Correction for ajax dynamic list, don't want two boxes!
        if(input.x_dd.table_id_fko!='') {
            input.autoComplete = false;
        }
    
        
        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        // This sets the value according to the type.  Originally
        // coded to handle the case of setting dates.
        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        input.establishValue = function(value,mode) {
            this.setValue(value);
            this.x_save = this.value;
            this.setMode(mode);
        }
        
        input.setValue = function(value) {
            if(this.x_dd.type_id == 'dtime') {
                if(value!='' && value!=null) {
                    this.value = value.slice(0,19);
                }
            }
            else if(this.x_dd.type_id=='date') {
                var vx = value;
                if(vx==null) {
                    this.value='';
                }
                else {
                    this.value
                        =vx.slice(5,7)
                        +'/'+vx.slice(8,10)
                        +'/'+vx.slice(0,4);
                }
            }
            else {
                if(value!=null) 
                    this.value = x4String.trim(value);
                else
                    this.value = '';
            }
        }
    
        input.setError = function(msg) {
            this.x_error = true;
            if(this.x_errorspan) {
                this.x_errorspan.innerHTML = msg;
            }
            this.setColor();
        }
        
        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        // Set a mode and then examine the properties to see what
        // CSS class it should get and if it should be read-only.
        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        input.setMode = function(mode) {
            this.x_mode = mode;
            
            // If a mode is being set, we want to clear any errors;
            this.x_error = false;
            if(this.x_errorspan) {
                this.x_errorspan.innerHTML = '';
            }
            
            // Work out a flag for read-only columns based on automation
            // start out assuming the column can be written
            input.readOnly = false;
            var noro = '*NONE*DEFAULT*SEQDEFAULT*BLANK*QUEUEPOS*DOMINANT**';
            if(noro.indexOf('*'+this.x_dd.automation_id+'*')==-1) {
                // This column did not match any of those, so it
                // is always read only
                input.readOnly = true;
            }
            else {
                // This column is normally editable, but if it is
                // the primary key in update mode, the answer is no
                if(this.x_dd.uiro=='Y') {
                    this.readOnly = true;
                }
                else if(mode == 'upd' && this.x_dd.primary_key == 'Y') {
                    this.readOnly = true;
                }
            }
            
            // Override: mode blank is always read only
            if(mode=='blank') input.readOnly = true;
            
            // Always do this when a mode is set
            this.setColor();
        }
        
        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        // Other functions call this one, mostly setMode()
        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        input.setColor = function(x_selected) {
            if(x_selected!=null) this.x_selected = x_selected;
            
            // Several branches need this, so may as well do it now
            var suffix = this.x_selected ? 'Selected' : '';
    
            // If the error flag is set it does not matter
            // what mode we are in
            if(this.x_error) {
                this.className = 'x4err'+suffix;
                return;
            }
            
            if(this.x_mode == 'upd') {
                if(this.readOnly) {
                    if(this.x_dd.primary_key=='Y') 
                        this.className = 'x4pk';
                    else
                        this.className = 'x4ro';
                }
                else {
                    if(this.value == this.x_save) 
                        this.className = 'x4upd'+suffix;
                    else
                        this.className = 'x4ins'+suffix;
                }
            }
            
            if(this.x_mode == 'ins') {
                this.className = 'x4ins'+suffix;                
            }
        }
        
        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        // If there is no context, not much else to do
        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        if(context==null) {
            input.tabIndex  = 0;
            input.x_context = null;
            return input;
        }
            
        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        // These are miscellaneous property assignments from context
        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        // Very important that the object can find its context
        input.x_context = context;
        
        
        // If there is an id prefix, use it to generate an id
        if(x4.getProperty(context,'idPrefix','')!='') {
            input.id = context.idPrefix + colinfo['name'];
        }
    
        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        // Here are various registrations in the context 
        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        // The tabLoop is a numbered array of the inputs on the control
        // It is used for iteration of inputs.
        // set tab index and tell the context who we are
        x4Input.setTabIndex(input,context);
        
        // The inputsByName is an assoc array keyed on column name
        if(x4.getProperty(context,'inputsByName','')=='') {
            context.inputsByName = new Object();
        }
        context.inputsByName[colinfo['name']] = input;
        
        // Set this as first control if not set yet
        if(x4.getProperty(context,'firstFocus','')=='') {
            context.firstTab   = input;
            context.firstFocus = input;
        }
        // The last one created always claims to be last
        context.lastTab = input;
        
        
        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        // Event handlers.
        //
        // The idea here is that all events must be assigned by the
        // context, the base routine here does not do that.  
        // To facilitate this, however, this routine looks for 
        // events in the context and if it can find them then it
        // attaches them to this object.
        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        // On blur and on focus will set colors and try to change
        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        if(x4.getProperty(context,'inputOnFocus','') != '') {
            input.onfocus = function(e) {
                this.x_context.inputOnFocus(this,e);
            }
        }
        if(x4.getProperty(context,'inputOnBlur','') != '') {
            input.onblur = function(e) {
                this.x_context.inputOnBlur(this,e);
            }
        }
        if(x4.getProperty(context,'inputOnKeyUp','') != '') {
            input.onkeyup = function(e) {
                x4Input.fieldFormat(this);
                this.x_context.inputOnKeyUp(this,e);
            }
        }
        else {
            input.onkeyup = function(e) {
                x4Input.fieldFormat(this);
            }
        }
        if(x4.getProperty(context,'inputOnKeyPress','') != '') {
            input.onkeypress = function(e) {
                this.x_context.inputOnKeyPress(this,e); 
            }
        }
        if(x4.getProperty(context,'inputOnKeyDown','') != '') {
            input.onkeydown = function(e) {
                this.x_context.inputOnKeyDown(this,e); 
            }
        }
        if(x4.getProperty(context,'inputOnChange','') != '') {
            input.onchange = function(e) {
                this.x_context.inputOnChange(this,e); 
            }
        }
        
        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        // Give them back the input so they can use it
        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        return input;
    },
    
    setTabIndex: function(input,context) {
        context.tabIndex++;
        input.tabIndex = context.tabIndex;
        if(x4.getProperty(context,'tabLoop','')=='') {
            context.tabLoop = new Array();
        }
        var l = context.tabLoop.length;
        context.tabLoop[l] = input;
        if(l == 0) {
            input.i_next = null;
            input.i_prev = null;
        }
        else {
            // Link this element to the one before it and vice versa
            input.i_prev = context.tabLoop[l - 1];
            context.tabLoop[l - 1].i_next = input;
            
            // Forward link this element back to the beginning 
            // and back-link the first element to this one
            // This gives a closed loop.
            context.tabLoop[0].i_prev = input;
            input.i_next = context.tabLoop[0]; 
        }
    },
        
    
    /*
     *
     * x4Input.fieldFormat
     * Formats ssn's, phones, dates, etc.
     *
     */
    fieldFormat: function(input) {
        objval = x4String.trim(input.value);
        if(input.x_dd.type_id=='date') {
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
                        input.value=month+'/'+day+'/'+year;
                    }
                }
            }
        }
    },

    
    tStr: {
        0   :'12:00 am',
        15  :'12:15 am',
        30  :'12:30 am',
        45  :'12:45 am',
        60  :' 1:00 am',
        75  :' 1:15 am',
        90  :' 1:30 am',
        105 :' 1:45 am',
        120 :' 2:00 am',
        135 :' 2:15 am',
        150 :' 2:30 am',
        165 :' 2:45 am',
        180 :' 3:00 am',
        195 :' 3:15 am',
        210 :' 3:30 am',
        225 :' 3:45 am',
        240 :' 4:00 am',
        255 :' 4:15 am',
        270 :' 4:30 am',
        285 :' 4:45 am',
        300 :' 5:00 am',
        315 :' 5:15 am',
        330 :' 5:30 am',
        345 :' 5:45 am',
        360 :' 6:00 am',
        375 :' 6:15 am',
        390 :' 6:30 am',
        405 :' 6:45 am',
        420 :' 7:00 am',
        435 :' 7:15 am',
        450 :' 7:30 am',
        465 :' 7:45 am',
        480 :' 8:00 am',
        495 :' 8:15 am',
        510 :' 8:30 am',
        525 :' 8:45 am',
        
        540 :' 9:00 am',
        555 :' 9:15 am',
        570 :' 9:30 am',
        585 :' 9:45 am',
        600 :'10:00 am',
        615 :'10:15 am',
        630 :'10:30 am',
        645 :'10:45 am',
        660 :'11:00 am',
        675 :'11:15 am',
        690 :'11:30 am',
        705 :'11:45 am',
        
        720 :'12:00 pm',
        735 :'12:15 pm',
        750 :'12:30 pm',
        765 :'12:45 pm',
        
        780 :' 1:00 pm',
        795 :' 1:15 pm',
        810 :' 1:30 pm',
        825 :' 1:45 pm',
        840 :' 2:00 pm',
        855 :' 2:15 pm',
        870 :' 2:30 pm',
        885 :' 2:45 pm',
        900 :' 3:00 pm',
        915 :' 3:15 pm',
        930 :' 3:30 pm',
        945 :' 3:45 pm',
        960 :' 4:00 pm',
        975 :' 4:15 pm',
        990 :' 4:30 pm',
        1005:' 4:45 pm',
        1020:' 5:00 pm',
        1035:' 5:15 pm',
        1050:' 5:30 pm',
        1065:' 5:45 pm',
        1080:' 6:00 pm',
        1095:' 6:15 pm',
        1110:' 6:30 pm',
        1125:' 6:45 pm',
        1140:' 7:00 pm',
        1155:' 7:15 pm',
        1170:' 7:30 pm',
        1185:' 7:45 pm',
        1200:' 8:00 pm',
        1215:' 8:15 pm',
        1230:' 8:30 pm',
        1245:' 8:45 pm',
        
        1260:' 9:00 pm',
        1275:' 9:15 pm',
        1290:' 9:30 pm',
        1305:' 9:45 pm',
        1320:'10:00 pm',
        1335:'10:15 pm',
        1350:'10:30 pm',
        1365:'10:45 pm',
        1380:'11:00 pm',
        1395:'11:15 pm',
        1410:'11:30 pm',
        1425:'11:45 pm'
    }
    
}   // var x4Input //    



var x4Select = {
    divWidth: 400,
    divHeight: 300,
    div: false,
    iframe: false,
    row: false,

    // Main routine called when a keystroke is hit on 
    // the control that "hosts" the androSelect
    onKeyUp: function(obj,strParms,e) {
        var kc = e.keyCode;
    
        // If TAB or ENTER, clear the box
        if(kc == 9 || kc == 13) { return true; }
        
        // If downarrow or uparrow....
        if(kc == 38 || kc == 40) {
            if(!x4Select.visible()) return;
            if(x4Select.div.firstChild.rows.length==0) return;
    
            if(!x4Select.row) { 
                var row = x4Select.div.firstChild.rows[0];
                var skey= row.x_skey;
                x4Select.mo(row,skey);
                return;
            }
            
            var row = x4.byId('as'+x4Select.row);
            var prev= row.getAttribute('x_prev');
            var next= row.getAttribute('x_next');
            if(kc==38) {
                if(prev) {
                    var row = x4.byId('as'+prev);
                    x4Select.mo(row,prev);
                }
            }
            if(kc==40) {
                if(next) {
                    var row = x4.byId('as'+next);
                    x4Select.mo(row,next);
                }
            }
            
            // No matter what, never proceed if it was up/down arrow
            return;
        }
    
        // This is used to track the last value we searched for
        if(typeof(obj.androSelect == 'undefined')) {
            obj.androSelect = '';
        }
        // Now test to see if the value has changed.  If not, return 
        if(obj.androSelect == obj.value) {
            return;
        }
        // From this point forward we are going to do a search,
        // because the user has changed the value of the input
        
        // If no DIV, set one up.
        if(!this.div) {
            this.div = document.createElement('DIV');
            this.div.style.display  = 'none';
            this.div.style.width    = this.divWidth + "px";
            this.div.style.height   = this.divHeight+ "px";
            this.div.style.position = 'absolute';
            this.div.className = 'androSelect';
            this.div.id = 'androSelect';
            document.body.appendChild(x4Select.div);
            var x = document.createElement('TABLE');
            x4Select.div.appendChild(x);
                
        }
        // If it is invisible, position it and then make it visible
        if(x4Select.div.style.display=='none') {
            var postop = obj.offsetTop;
            var poslft = obj.offsetLeft;
            var objpar = obj;
            while((objpar = objpar.offsetParent) != null) {
                postop += objpar.offsetTop;
                poslft += objpar.offsetLeft;
            }
            x4Select.div.style.top  = (postop + obj.offsetHeight) + "px";
            x4Select.div.style.left = poslft + "px";
            x4Select.div.style.display = 'block';
            
            // As part of making visible, create an onclick
            // that will trap the event target and lose focus
            // if not the input object or the
            // KFD x4SELECT
            x4.addEventListener(document ,'click',x4Select_documentClick);
        }
        
        // Tell it the current control it is working for
        this.control = obj;
        this.row = false;
    
        // Make up the URL and send the command
        var url = 'x4xDropdown='+obj.x_dd.table_id_fko
            +'&gpv=2&gp_letters='+obj.value.replace(" ","+");
        //var url = '?'+strParms+'&gpv=2&gp_letters='+obj.value.replace(" ","+");         
        x4.ajax(url,null,this,'handler');
    },

    handler: function() {
        // do default action
        //handleResponse();
        if(typeof(x4httpData.x4Select)!='undefined') {
            this.div.firstChild.innerHTML = x4httpData.x4Select.rows;
        }
        
        if(this.div.firstChild) {
            var table = this.div.firstChild;
            if(table.rows.length > 0) { 
                table.rows[0].onmouseover();
            }
        }    
    },

    onKeyDown: function(e) {
        var kc = e.keyCode;
    
        // If TAB or ENTER, clear the box
        if(kc == 9 || kc == 13) { 
            if(!x4Select.visible()) return true;
            if(x4Select.div.firstChild.rows.length==0) {
                x4Select.hide();
                return true;
            }
            
            if(this.row) {
                var row = x4.byId('as'+x4Select.row);
                var pk  = row.getAttribute('x_value');
                this.control.value = pk;
            }
            this.hide();
            return true;
        }
    },


    // Make the div go away.  Actually choosing a value
    // is done elsewhere
    hide: function() {
        this.div.firstChild.innerHTML = ''
        this.div.style.display = 'none';
    },

    visible: function() {
        if(this.div == false) return false;
        if(this.div.style.display=='none') return false;
        
        return true;
    },


    // User is rolling over a row
    mo: function(tr,skey) {
        if(x4.byId('as'+this.row)) {
            x4.byId('as'+this.row).className = '';
        }
        this.row = skey;
        tr.className = 'hilite';
    },
    // User clicked on a row
    click: function(value,suppress_focus) {
        this.control.value = value;
        this.hide();
        if(suppress_focus==null) {
            this.control.focus();
        }
    }
}  // var x4Select

function x4Select_documentClick() {
    // Main purpose is to see if user clicked anywhere except
    // on current control or the div.  If they did, hide it
    // w/o making a choice.
    x4Select.hide();
    x4.removeEventListener(document,'click',x4Select_documentClick);
    return false;
}



/*
 *
 * x4Pages: Fetch and display pages
 *
 */
var x4Pages = {
    
    // x4Pages.load
    load: function(x4xPage,desc,objParent) {
        if(desc==null) { desc = x4xPage; }
    
        // Tell x4 to get another layer ready
        x4Layers.prepareDisplayLayer(x4xPage,desc);
        if(objParent != null) {
            x4Layers.nextLayer.objParent = objParent;
        }
        
        // If we've previously loaded this page, skip the
        // ajax call
        //if(typeof(x4Layers.ddPages[x4xPage])!='undefined') {
        //    x4.debug("Page request: we have dd already");
        //    this.displayPage(x4xPage);    
        //    return;
        //}
        x4.debug("Page request: fetching dd from server");
    
        // This code makes a "loading" picture
        var div = document.createElement('div');
        div.style.textAlign = 'center';
        div.style.paddingTop = '200px';
        var img = document.createElement('img');
        img.src = 'clib/ajax-loader.gif';
        div.appendChild(img);
        var span = document.createElement('span');
        span.innerHTML = '&nbsp;&nbsp;Loading Page: '+desc+'</div>';
        div.appendChild(span);
        x4.byId('andromeda_main_content').innerHTML = '';
        x4.byId('andromeda_main_content').appendChild(div);
    
        // Build the page request and send it
        getString='x4xPage='+x4xPage;
        x4.ajax(getString,null,this,'loadReturnHandler');
    },

    // x4Pages.loadReturnHandler
    loadReturnHandler: function() {
        if(x4.getProperty(x4httpData,'page','')=='') {
            console.log('aborting a layer');
            x4Layers.abortNewLayer();
            return;
        }
    
        var x4xPage = x4Layers.nextLayer.page;
        x4Layers.ddPages[x4xPage] = x4httpData.page.data;
    
        this.displayPage(x4xPage);    
    },
    
    // x4Pages.displayPage
    displayPage: function(x4xPage) {
        var pageInfo = x4Layers.ddPages[x4xPage];
        
        // Look for any kind of special page, and if
        // there are none, revert to standard browse
        if(typeof(pageInfo.HTML)!='undefined') {
            var x_context = x4Layers.nextLayer;
            x4Layers.nextLayer = false;
        
            x_context.HTML = x4Layers.ddPages[x4xPage].HTML;
            x4Layers.pushDisplayLayer(x_context);
            
            //displayLayerHTML(x4xPage);
            if(typeof(pageInfo.Script)!='undefined') {
                eval(pageInfo.Script);
            }
            x_context.onRestore();
        }
        else {
            displayLayerBrowse(x4xPage);   
        }        
    }
}   // var x4Pages





// MOD LINE


/*
 *
 * 
 * Menu handling object.
 * Not an instance of displayLayer because it pretty much
 * does everything its own way.
 * 
 *
 */
var x4Menu = {
    // Some useful constants
    //
    x4letters: [
         'a','b','c','d','e','f','g'
        ,'h','i','j','k','l','m','n'
        ,'o','p','q','r','s','t','u'
        ,'v','w','x','y','z'
    ],
    x4numbers: ['1','2','3','4','5','6','7','8','9'],

    // Tracking variables: current column, current row, list of columns
    menuCol:  false,
    menuRow:  false,
    menuCols: new Array(),

    // Every stack item needs a title
    title: 'Main Menu',

    // Placeholders, not needed for this object
    onPush: function() { return false; },
    onRestore: function() { return false; },
    
    // 
    // The init function, called to actually create the menu
    init: function() {
        x4SYJOH.serverCall('x4xMenu=1');
        
        this.h=document.createElement('DIV');
        this.h.className = 'mainmenu';
        var xx = document.createElement('H1');
        xx.innerHTML = 'MAIN MENU';
        this.h.appendChild(xx);
    
        // This one-row table holds all modules
        var topTab=document.createElement('TABLE');
        var row = topTab.insertRow(topTab.rows.length);
        topTab.className = 'gsMenu';
        this.h.appendChild(topTab);        

        // Now run through each module...
        this.initCols(row);
                
        // Choose 1st column and 1st row
        this.hiliteCol(this.menuCols[0]);
        this.hiliteRow(this.menuCols[0].x_kids[0]);
        
        x4Layers.pushDisplayLayer(this);
    },

    //
    // Initialize the columns across 
    //
    initCols: function(row) {
        var idx=-1;
        var retval = null;
        var x4menu = x4httpData.menu.default;
        for(var module in x4menu) {
            idx++;
            // Get a TD element to hold our menu entries
            var td = document.createElement('TD');
            td.x_index  = idx;
            td.x_kids   = new Array();
            td.x_context= this;
            td.onmouseover = function() { this.x_context.hiliteCol(this); }
            td.className = 'scheme0 dim';
            row.appendChild(td);
            if(idx==0) retval = td;
            
            // Assign this column to the array of columns.  Notice
            // we start with 49, which is charcode 1
            this.menuCols[idx] = td;
    
            // Get description of this module and put it out there
            var desc = x4menu[module].description;
            var a = document.createElement('H4');
            a.href='javascript:void(0);';
            a.innerHTML = this.x4numbers[idx]+'. '+desc;
            td.appendChild(a);
            
            this.initRows(x4menu,td,module);
        }
        return retval;
    },

    //
    // Inside a column initialize the rows 
    //
    initRows: function(x4menu,td,module) {
        var idx=-1;
        for(var page in x4menu[module].items) {
            idx++;
            var desc = x4menu[module].items[page].description;
            
            var a = document.createElement('A');
            a.href='javascript:void(0);';
            a.innerHTML   = this.x4letters[idx]+'. '+desc;
            a.x_context   = this;
            a.onmouseover = function() { this.x_context.hiliteRow(this); }
            a.x_page      = page;
            a.x_desc      = desc;
            a.x_index     = idx;
            a.onclick     = function() { this.x_context.chooseItem(this); } 
            td.appendChild(a);
            td.x_kids[td.x_kids.length] = a;
        }
    },
    
    // 
    // Hilite a particular column 
    //
    hiliteCol: function(td) { 
        // Reset any existing if they are there
        if(this.menuCol!=td) {
            if(this.menuRow) { 
                this.menuRow.className = '';
                this.menuRow = false;
            }
            if(this.menuCol) { 
                this.menuCol.className = 'scheme0 dim';
                this.menuCol = false; 
            }
        }
        // Pick new values;
        td.className='scheme0';
        this.menuCol = td;
    },
    
    //
    // Hilite an item inside of a column 
    //
    hiliteRow: function(a) {
        if(this.menuRow) { 
            this.menuRow.className = '';
            this.menuRow = false; 
        }
        a.className = 'hilite';
        this.menuRow = a;    
    },

    //
    // Choose an item 
    //
    chooseItem: function(a) {
        x4Pages.load(a.x_page,a.x_desc);    
    },

    // - - - - - - - - - - - - - - - - - - - - - - - - - -
    // Keyboard handler
    //
    bodyKeyPress: function(e) {
        var charCode = x4.charCode(e);
        var keyCode  = x4.keyCode(e);
        
        // First look for digits 1-9.
        // Charcode 49 is a 1
        if(typeof(this.menuCols[charCode-49])!='undefined') {
            this.hiliteCol(this.menuCols[charCode-49]);
            this.hiliteRow(this.menuCols[charCode-49].x_kids[0]);
            return true;a
        }
        
        // Now we will look for letters.  Does current column have
        // a letter for the key you hit?
        if(this.menuCol && charCode >= 97) {
            var index = charCode - 97;
            if(index <= (this.menuCol.x_kids.length-1)) {
                this.chooseItem(this.menuCol.x_kids[index]);
                return true;
            }
        }
        
        // Enter key with a selected row picks it
        if(keyCode == 13 && this.menuRow) {
            this.chooseItem(this.menuRow);
            return true;
        }
        
        // look for up arrow and down arrow within currently selected
        // down is 40, up is 38
        if(keyCode==40 || keyCode==38) {
            if(this.menuRow) {
                if(!this.menuRow) {
                    this.hiliteRow(this.menuCol.x_kids[0]);
                }
                else { 
                    if(keyCode == 38) {
                        if(this.menuRow.x_index>0) {
                            this.hiliteRow(this.menuCol.x_kids[ this.menuRow.x_index-1 ]);
                        }
                    }
                    if(keyCode == 40) {
                        if(this.menuRow.x_index < (this.menuCol.x_kids.length-1)) {
                            this.hiliteRow(this.menuCol.x_kids[ this.menuRow.x_index+1 ]);
                        }
                    }
                }
            }
            return true;
        }
        
        // Look for left and right.  37 is left, 39 is right
        if(keyCode==37 || keyCode == 39) { 
            if(this.menuCol) {
                // Get the row index of currently selected item
                var rowIndex = 0;
                if(this.menuRow) {
                    rowIndex = this.menuRow.x_index;
                }
                else {
                    rowIndex = 0;
                }
                // Determine the new column
                var newGuy = false;
                if(keyCode == 37 && this.menuCol.x_index > 0) {
                    newGuy = this.menuCols[ this.menuCol.x_index-1 ];
                }
                if(keyCode == 39 && this.menuCol.x_index < (this.menuCols.length-1)) {
                     newGuy = this.menuCols[ this.menuCol.x_index+1 ];
                }
                if(newGuy) {
                    // Pick the new column, and try to pick the new row
                    this.hiliteCol(newGuy);
                    if( rowIndex > (newGuy.x_kids.length - 1)) 
                        rowIndex = newGuy.x_kids.length - 1;
                    this.hiliteRow(newGuy.x_kids[rowIndex]);
                }
            }
            return true;  // we handled it, go no futher
        }
        return false;  // we did not handle it here, higher code should handle it
    }
}   // var x4Menu //

/*
 *
 *
 * x4Print object
 * Handles everything relating to printing
 *
 *
 */
var x4Print = {
    dialog: function(controls,page,title) {
        var getString="x4Index.php?x4xReport="+page;
        for (var idx in controls) {
            var cid = controls[idx];
            getString+="&x4c_"+cid+"="
                +encodeURIComponent(x4.byId(cid).value);
        }
        
        // make the dialog, add title and links
        var div = x4Dialogs.blank(title);
        x4.createElement('h3',div,'Print: '+title); 
        x4.appendBR(div);
        var a= x4.appendLink(div,'Preview as PDF',getString);
        a.onclose = function() { x4Dialogs.close(); }
        
        x4Dialogs.show();
    }
}

var x4Dialogs = {
    div: false,
    
    blank: function() {
        if(!this.div) {
            this.div = document.createElement('DIV');
            this.div.style.display  = 'none';
            this.div.style.width    = "500px";
            this.div.style.height   = "350px";
            this.div.style.top      = "100px";
            this.div.style.left     = "100px";
            this.div.style.padding  = "10px";
            this.div.style.position = 'absolute';
            this.div.className = 'androSelect';
            this.div.id = 'androDialog';
            document.body.appendChild(this.div);
        }
        
        // Clear out the innerHTML, start over
        this.div.innerHTML = "";
        var a = document.createElement('A');
        a.style.float = 'right';
        a.href="javascript:x4Dialogs.close()";
        a.innerHTML = "Close";
        this.div.appendChild(a);
        x4.createElement('br',this.div,'');
        return this.div;
    },
    
    show: function() {
        if(this.div) this.div.style.display='block';
    },
    hide: function() {
        if(this.div) this.div.style.display='none';
    },

    close: function() {
        if(this.div) {
            this.hide();
        }
    }
}

/*
 *
 *
 * displayLayer class
 * This is a CONSTRUCTOR function, it defines the displayLayer class
 *
 *
 */
function displayLayer(page, desc) {
    this.page  = page;
    this.title = desc;
    this.objParent = false;
    this.initHasRun= false;
    this.tabIndex  = 1000;
    
    this.lastFocus  = false;
    this.firstFocus = false;
    this.lastTab    = false;
    this.firstTab   = false;
    
    // For custom forms.  The current writing surface
    this.surface = false;
    
    /*
     * These two are required
     */
    this.onPush    = function() { return false; }
    this.onRestore = function() { return false; }

    /*
     * 
     * Two focus routines.  First one remembers the
     *  last item that has focus, second one tries
     *  to reset focus.
     *
     */
    this.setFocus = function(id) {
        // This lets you pass id or object
        if(typeof(id)=='string') 
            var obj = x4.byId(id);
        else 
            var obj = id;
        if(typeof(obj.focus)!='undefined') { 
            obj.focus();
        }
        if(typeof(obj.onfocus)!='undefined') {
            obj.onfocus();
        }
    }
    this.fixFocus  = function() { 
        if(x4.getProperty(this,'lastFocus')) {
            this.setFocus(this.lastFocus);
        }
        else if(x4.getProperty(this,'firstFocus')) {
            this.setFocus(this.firstFocus);
        }
    }

    /*
     *
     * bodyKeyPress by defaults pops the layer when
     * ESC is pushed.  This code is here so that it will
     * work even for pages that pass literal HTML.
     *
     */
    this.bodyKeyPress = function(e) {
        // This tells the main handler we handled it.  We don't
        // want the main object doing anything when the browse is
        // active.
        Label = x4.keyLabel(e);
        x4.debug("displayLayer(generic).bodyKeyPress: INVOKED with "+Label,'keyboard');
        if(Label == 'Esc') {
            x4Layers.popDisplayLayer();
            // Tell calling function we handled the keystroke
            return true;
        }
        return false;
    }
    
    
    /*
     * The input navigation routines can be called
     * by any class that needs them, but they are
     * never automatically called
     */
    this.inputsNavigationTab = function(input,e) {
        var Label = x4.keyLabel(e);
        if(Label=='Tab') {
            this.inputsNavigateNext(input);
            e.preventDefault();
            return false;
        }
        if(Label=='ShiftTab') {
            this.inputsNavigatePrev(input);
            e.preventDefault();
            return false;
        }
        return true;    
    }
    this.inputsNavigationEnter = function(input,e) {
        var Label = x4.keyLabel(e);
        if(Label=='Enter') {
            this.inputsNavigateNext(input);
            e.preventDefault();
            return false;
        }
        if(Label=='ShiftEnter') {
            this.inputsNavigatePrev(input);
            e.preventDefault();
            return false;
        }
        return true;
    }
    this.inputsNavigationArrows = function(input,e) {
        var Label = x4.keyLabel(e);
        // Regular arrows only work on inputs
        if(input.tagName=='INPUT') { 
            if(Label == 'DownArrow') {
                this.inputsNavigateNext(input);
                e.preventDefault();
                return false;
            }
            else if(Label == 'UpArrow') {
                this.inputsNavigatePrev(input);
                e.preventDefault();
                return false;
            }
        }
    }
    this.inputsNavigateNext = function(input) {
        var inputOrig = input;
        while(input.i_next.readOnly == true) {
            input = input.i_next;
            // Prevent an endless loop
            if(input == inputOrig) {
                return;
            }
        }
        this.setFocus(input.i_next);
    }
    this.inputsNavigatePrev = function(input) {
        var inputOrig = input;
        while(input.i_prev.readOnly == true) {
            input = input.i_prev;    
            // Prevent an endless loop
            if(input == inputOrig) {
                return;
            }
        }
        this.setFocus(input.i_prev);
    }
    

    /*
     * displayLayer.setupStandard
     * Sets up the basic most often-desired keyboard stuff
     *
     */
    this.setupStandard = function() {
        this.onRestore = function() { this.fixFocus(); }
        this.onPush    = function() { this.fixFocus(); }
        this.inputOnKeyPress= function(input,e) { 
            this.inputsNavigationTab(input,e);
            this.inputsNavigationEnter(input,e);
        }
    }
    
    // ------------------------------------------------------
    // ------------------------------------------------------
    // Major area: Rendering HTML objects.  All of the
    //             code in this area is about putting
    //             HTML into the document.
    // ------------------------------------------------------
    // ------------------------------------------------------

    // ------------------------------------------------------
    // ------------------------------------------------------
    // Major area: Rendering code added before Feb 11, 2008
    //             that may or may not be referenced.  If
    //             not we want to get rid of it.
    // ------------------------------------------------------
    // ------------------------------------------------------
    
    /*
     * displayLayer.appendChild
     * Append an item to the current context surface
     * KFD 2/11/08 Suspected never used
     *
     */
    this.appendChild = function(obj) {
        if(!this.surface) {
            this.surface = x4.byId('andromeda_main_content');
        }
        this.surface.appendChild(obj);
    }
    
    /*
     * displayLayer.setSurfaceById
     * Set a new writing surface
     * KFD 2/11/08 Suspected never used
     *
     */
    this.setSurfaceById = function(id) {
        this.surface = x4.byId(id);
    }
    
    
    /*
     * displayLayer.addTitle
     * Very simple, put out a span with some text in it
     * KFD 2/11/08 Suspected never used
     */
    this.addTitle = function(text) {
        var span = document.createElement('H3');
        span.innerHTML = text;
        this.appendChild(span);
    }
    
    /*
     * displayLayer.addCaption
     * Very simple, put out a span with some text in it
     */
    this.addCaption = function(text) {
        var span = document.createElement('SPAN');
        span.innerHTML = text+' ';
        this.appendChild(span);
    }
   

    /*
     * displayLayer.newLine
     * Very simple, break to a new line
     */
    this.newLine = function() {
        var br = document.createElement('BR');
        this.appendChild(br);
    }
    
    /*
     *
     * displayLayer.addDateInput
     * Generates a column description array and calls x4Input.make()
     *
     */
    this.addDateInput = function(id) {
        // Initialize some variables
        var colinfo = {
            table_id: '', column_id: '',
            type_id: 'date',
            dispsize: 11
        }
        var input = x4Input.make(colinfo,this);
        input.id = id;
        this.appendChild(input);
    }
    
    /*
     * displayLayer.addLookup
     * Adds a dynamic lookup to the named table
     *
     */
    this.addLookup = function(id,table) {
        var colinfo = {
            table_id: '', column_id: '',
            type_id: 'date',
            dispsize: 11
        };
        colinfo.table_id_fko = table;
        var input = x4Input.make(colinfo,this);
        input.id = id;
        input.onkeydown = function(e) { x4Select.onKeyDown(e);    }
        input.onkeyup   = function(e) { x4Select.onKeyUp(this,'gp_dropdown=customers',e); }
        this.appendChild(input);
    }

    
}   // function displayLayer // 

/*
 *
 *
 * DISPLAY LAYER: Browse
 * This is a BUILDER Function, it builds a subclass of displayLayer
 *
 *
 */
function displayLayerBrowse(x4xPage) {
    var x_browse    = x4Layers.nextLayer;
    x4Layers.nextLayer = false;
    
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // The load function is invoked at the bottom of this
    // builder function, it creates the HTML.
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    x_browse.build = function() {
        // Property Assignments come first
        this.dd          = x4Layers.ddPages[x4xPage];
        this.tabIndex    = 1000;
        this.idPrefix    = 'x4w_';
        this.table_id    = x4Layers.ddPages[x4xPage].table_id;
        this.sortCol     = '';
        this.sortDir     = 'ASC';
        this.collist     = x4Layers.ddPages[x4xPage].aProjections._uisearch;
        this.dd.data       = new Object()
        this.dd.data.rowCount    = 0;
        this.dd.data.rowSelected =-1;
        this.dd.data.rows        = new Array();
        this.dd.data.rowNext     =-1;
        
        // Make decisions about various options based on
        // flags and environment
        this.returnall = false;
        this.displayInputs = true;
        if(this.objParent || x4.getProperty(this.dd,'returnall','')=='Y') {
            this.returnall = true;
            this.displayInputs = false;
        }
        this.pkValues = new Array();
        if(this.objParent) {
            this.pkValues = this.objParent.getPKValues();
        }
    
        var center = document.createElement('div');
        this.h = center;
        var div = document.createElement('DIV');
        div.className = 'browse';
        this.h.appendChild(div);
        
        // Add the title
        var title = document.createElement('H1');
        title.innerHTML = this.title;
        div.appendChild(title);
        
        // Add the 'new' link
        var linknew = document.createElement('A');
        linknew.id = 'object_for_f7';
        linknew.x_context = this;
        linknew.href = "javascript:void(0)";
        linknew.innerHTML = 'F7: New '+x4.getSingular(this.dd.description);
        linknew.className = 'browse_anew';
        linknew.onclick = function(e) { 
            displayLayerDetail(this.x_context.dd.table_id
                ,'ins',null,this.x_context
            );
        }
        div.appendChild(linknew);
        x4.createElement('BR',div,'');
        x4.createElement('BR',div,'');
        
        
        // Construct a Table for head
        var hTab = document.createElement('TABLE');
        hTab.className   = 'browseHead';
        hTab.x_context   = this;
        div.appendChild(hTab);

        // Make table for body
        var hDiv = document.createElement('DIV');
        hDiv.style.display = 'none';
        hDiv.setAttribute( 'align', 'left' );
        hDiv.className   = 'browseBody';
        div.appendChild(hDiv);
        var hTabb = document.createElement('TABLE');
        hTabb.setAttribute( 'align', 'left' );
        hDiv.appendChild(hTabb);
        hTabb.x_context   = this;
        hTabb.style.height  ="370px";
        this.divBody     = hDiv;
        this.htmlTable   = hTabb;
        this.hBrowseBody = hTabb;

        // Get the constant we will use to calculate column widths
        var charWidth = x4.byId('x4CharacterInformation').scrollWidth;
        var tabWidth  = 0;
        
        // Make the row of headers and row of inputs by cycling through
        // the meta data that was copied in from the server
        var trh = hTab.insertRow(0);
        if(this.displayInputs) {
            var tri = hTab.insertRow(1);
        }
        this['headers'] = new Object();
        for(var idx in this.collist) {
            column = this.dd.flat[ this.collist[idx] ];
            
            // calculate pixel width for column
            var dw = column.dispsize;
            if(column.dispsize < column['description'].length) {
                dw = column['description'].length;
            }
            //console.debug(column.dispsize+" : " +dw+" : " + charWidth);
            var pw = Number(charWidth) * Number(dw);
            this.dd.flat[ this.collist[idx] ].sbWidth = pw;
            tabWidth+=pw;
            
            // establish sort order as first column;
            if(this.sortCol == '') {
                this.sortCol = column.column_id
                this.sortDir = 'DESC';
            }
            
            // The header cell
            var cellh = document.createElement('TH');
            cellh.style.width = pw+"px";
            var cellhdiv = document.createElement('DIV');
            cellhdiv.style.maxWidth = pw+"px";
            cellhdiv.style.overflow = "hidden";
            cellhdiv.innerHTML = column['description'];
            cellh.appendChild(cellhdiv);
            cellh.style.width = pw+"px";
            //cellh.innerHTML = column['description'];
            cellh.x_caption = column['description'];
            cellh.x_context = this;
            cellh.x_name    = column.column_id
            cellh.onmouseover = function(e) { this.className = 'hilite'; }
            cellh.onmouseout  = function(e) {
                if(this.x_name == this.x_context.sortCol) {
                    this.className='sorted';
                }
                else {
                    this.className = '';
                }
            }
            cellh.onclick  = function(e) { 
                this.x_context.setSort(this.x_name,null);
                this.x_context.fixFocus();
            }
            trh.appendChild(cellh);
            // Keep list of references to headers by column name, for
            // setting and changing styles based on sort order
            this['headers'][column.column_id] = cellh;
            
            // The input cell
            if(this.displayInputs) {
                var cell = document.createElement('TD');
                cell.style.width = pw+"px";
                var input= x4Input.make(column,this);
                input.style.width=(pw-3)+"px";
        
                cell.appendChild(input);
                tri.appendChild(cell);
            }
        }
        
        // Take accumulated pixel width and apply it to bottom table
        tabWidth += 2 * this.collist.length;   // padding in cells
        //tabWidth += this.collist.length+1;     // border of cells
        //tabWidth += 21;                        // scrollbar height
        hDiv.style.width=tabWidth+"px";
        hDiv.style.height="370px";
        hDiv.style.overflow='auto';
        
        // Make it a display layer 
        x_browse.setSort(this.sortCol,'DESC',true);
        x4Layers.pushDisplayLayer(this);
    }
    
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // onPush is called after the displayLayer has become visible.
    // onRestore is called if a layer on top has been removed,
    //     so the layer becomes visibleagain
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    x_browse.onRestore = function() { this.fixFocus(); }

    x_browse.onPush    = function() { 
        this.fixFocus(); 
        if(this.returnall) {
            this.fetchData(true);
        }
        this.initHasRun = true;
    }
    
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // BodyKeyPress is intended to handle events when no input
    // is currently selected.  This includes things like ESC.
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    x_browse.bodyKeyPress = function(e) {
        // This tells the main handler we handled it.  We don't
        // want the main object doing anything when the browse is
        // active.
        Label = x4.keyLabel(e);
        x4.debug("x_browse.bodyKeyPress: INVOKED with "+Label,'keyboard');
        if(Label == 'Esc') {
            if(this.returnall) {
                x4Layers.popDisplayLayer();
            }
            else {
                if(this.dd.data.rowCount==0) {
                    x4Layers.popDisplayLayer();
                }
                else {
                    for(var idx in this.tabLoop) {
                        input = this.tabLoop[idx];
                        input.x_old = '';
                        input.value = '';
                    }
                    this.purgeDataAndHTML();
                }
            }
            return true;
        }

        // Check for arrows, pageup/down
        if(Label!='') {
            if(this.keysArrowsAndPages(Label,null,e)) 
                return true;
        }

        // ENTER selects the row and edits it
        if(Label == 'Enter' && this.dd.data.rowSelected >= 0) {
            this.editRow(this.dd.data.rowSelected);
            return true;
        }
        
        // If no code above handled it, return false
        return false;
    }
    
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // KeyPress by default is used for everything
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    x_browse.inputOnKeyPress = function(input,e) {
        var Label = x4.keyLabel(e);
        x4.debug("x_browse.inputOnKeyPress INVOKED with "+Label,'keyboard');

        if(Label!='') {
            if(this.keysArrowsAndPages(Label,input,e)) 
                return true;
        }
        this.fetchData();
        this.inputsNavigationTab(input,e);
    }
    
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // KeyUp must be used for inputs if you must query their value,
    // if you use keyPress the value is not updated yet
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    x_browse.inputOnKeyUp = function(input,e) {
        var Label = x4.keyLabel(e);
        if(Label=='') {
            x4.debug('x_browse.inputOnKeyUp INVOKED for unlabeled key'
                ,'keyboard'
            );
            this.fetchData();
        }
    }
    
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // Key handler for arrows and pageup/down
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    x_browse.keysArrowsAndPages = function(Label,input,e) {    
        x4.debug("x_browse.keysArrowsAndPages INVOKED with "+Label,'keyboard');
        if(Label=='DownArrow') {
            console.log("caught a down arrow");
            if(this.dd.data.rowSelected < (this.dd.data.rowCount-1)) {
                console.log("choosing Row + 1");
                this.chooseRow(this.dd.data.rowSelected+1);
            }
            e.preventDefault();
            return true;
        }
        if(Label=='UpArrow')   {
            if(this.dd.data.rowSelected > 0) {
                this.chooseRow(this.dd.data.rowSelected - 1);
            }
            e.preventDefault();
            return true;
        }
        if(Label=='CtrlPageUp' || Label=='Home') {
            this.chooseRow(0);
            e.preventDefault();
            return true;
        }
        if(Label=='PageUp') {
            var x = 0;
            if(this.dd.data.rowSelected > 19) x = this.dd.data.rowSelected - 20;
            this.chooseRow(x);
            e.preventDefault();
            return true;
        }
        if(Label=='CtrlPageDown'  || Label=='End') {
            this.chooseRow(this.dd.data.rowCount - 1);
            e.preventDefault();
            return true;
        }
        if(Label=='PageDown') {
            var x = Number(this.dd.data.rowCount) - 1;
            if(this.dd.data.rowSelected < (this.dd.data.rowCount - 20))
                x = this.dd.data.rowSelected + 20;
            this.chooseRow(x);
            e.preventDefault();
            return true;
        }
        if(Label=='ShiftUpArrow') {
            if(input!=null) {
                this.setSort(input.x_dd.column_id,'DESC');
            }
            e.preventDefault();
            return true;
        }
        if(Label=='ShiftDownArrow') {
            if(input!=null) {
                this.setSort(input.x_dd.column_id,'ASC');
            }
            e.preventDefault();
            return true;
        }
    }

        
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // Deal with focus and tab order
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    x_browse.inputOnFocus = function(input,e) {
        this.lastFocus = input;
    }
    
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // Set the sort order 
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    x_browse.setSort = function(sortCol,sortDir,nofetch) {
        // Work out new order if it was not assigned.  If the
        // column is the same, we switch, otherwise we pick
        // the new column and make it ascending.
        if(sortDir == null) {
            if(sortCol == this.sortCol) {
                sortDir = this.sortDir == 'ASC' ? 'DESC' : 'ASC';
            }
            else {
                sortDir = 'ASC';
            }
        }
        
        // If columns are not the same, turn off the original
        // column and change its caption
        if(sortCol != this.sortCol) {
            var header = this.headers[this.sortCol];
            //header.innerHTML = header.x_caption;
            //header.className = '';
        }
        
        // Set styles for new header
        this.sortCol = sortCol;
        this.sortDir = sortDir;
        var header = this.headers[this.sortCol];
        if(sortDir == 'ASC') {
            //header.innerHTML = '&dArr; '+header.x_caption;
        }
        else {
            //header.innerHTML = '&uArr; '+header.x_caption;
        }
        //header.className = 'sorted';

        // Finally, go get the data.
        if(nofetch == null) {
            this.fetchData(true);
        }
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // Clears out all of the prior search results from the 
    // data cache and removes any HTML rows.
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    x_browse.purgeDataAndHTML = function() {
        this.dd.data.rowSelected=-1;
        this.dd.data.rowCount = 0;
        this.dd.data.rows = new Object();
        /*
        while(this.htmlTable.rows.length > 2 ) {
            this.htmlTable.deleteRow(this.htmlTable.rows.length - 1);
        }
        */
        /*
        x4.byId('x4browse_tbody').innerHTML = '';
        */
        this.hBrowseBody.innerHTML = '';
        x4.setStatus('0 rows');
    }
    
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // Fetch data from search results
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    x_browse.fetchData = function(force,offset) {
        x_browse.rowNext = -1;
        
        // notice we are not even looking at the event, we will
        // read the values of the inputs directly.  If they have
        // changed, we will run a search.
        var goforit = false;
        var getVals = '';
        if(this.returnall) {
            for(var idx in this.pkValues) {
                getVals
                    += '&x4w_'+idx+'='
                    +encodeURIComponent(this.pkValues[idx]);
            }
        }
        else {
            for(var idx in this.tabLoop) {
                var input = this.tabLoop[idx];
                var valOld = input.x_old;
                var valNew = input.value;
                if(valOld != valNew) {
                    if(valNew != '') {
                        goforit = true;
                    }
                    input.x_old = input.value;
                }
                // Do this just in case
                getVals +='&'+input.id+'='+encodeURIComponent(valNew);
            }
        }
        if(goforit || force) {
            var getString
                ='x4xAjax=bsrch'
                +'&x4xTable='+this.table_id
                +'&sortCol=' +this.sortCol
                +'&sortDir=' +this.sortDir
                +getVals;
            x4.ajax(getString,null,this,'returnHandler');
        }
    }
    
    x_browse.returnHandler = function() {
        // Clear the search data and delete the html table rows
        this.purgeDataAndHTML();
            
        // Loop through the search results, if there are any,
        // and add them in.
        var tab = this.table_id;
        if(typeof(x4httpData.data)!='undefined') {
            if(typeof(x4httpData.data[tab])!='undefined') {
                // There is data, so let's copy it to ourself
                this.dd.data.rows = x4httpData.data[tab];
                this.dd.data.rowCount = this.dd.data.rows.length;

                // Add each row to the display now
                this.dd.data.rowNext = 0;
                browseDisplayRows();
            }
        }
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // Replace the currently selected row
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    x_browse.replaceRow = function(row) {
        this.dd.data.rows[ this.dd.data.rowSelected ] = row;
        this.htmlTable.deleteRow( Number(this.dd.data.rowSelected)+2 );
        this.addRowToDisplay( row, Number(this.dd.data.rowSelected));
        this.chooseRow(this.dd.data.rowSelected);
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // Delete the currently selected row
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    x_browse.deleteSelectedRow = function() {
        var idx = this.dd.data.rowSelected;
        this.htmlTable.deleteRow( Number(this.dd.data.rowSelected)+2 );
        if(this.dd.data.rowSelected > 0) this.dd.data.rowSelected--;
        this.chooseRow(this.dd.data.rowSelected);
    }
    
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // Add a row to Search Results.
    // This adds a row to the data set, and then adds that row
    // to the visual display.  Used when the detail tab saves
    // a new row.
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    x_browse.addRowToSearchResults = function(row) {
        this.dd.data.rowCount++;
        this.dd.data.rows[ this.dd.data.rowCount-1 ] = row;
        this.addRowToDisplay(row, Number(this.dd.data.rowCount)-1);
        this.chooseRow(Number(this.dd.data.rowCount) - 1);
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // Add a row to the visual display
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    x_browse.addRowToDisplay = function(row,idx,alsoChoose) {
        // Here is where we add a visual row to the search results
        tbody = this.hBrowseBody;
        //tbody  = byId('x4browse_tbody'); // this.htmlTable;
        //if(!tbody) return;
        tr = tbody.insertRow(Number(idx));
        tr.x_idx     = idx;
        tr.id        = "x4browse_"+tr.x_idx;
        tr.x_context = this;
        tr.onmouseover = function(e) {
            this.x_context.chooseRow(this.x_idx);
        }
        tr.onclick = function(e) {
            this.x_context.chooseRow(this.x_idx);
            this.x_context.fixFocus();
            this.x_context.editRow(this.x_idx);
        }
        for(var idx in this.collist) {
            td = document.createElement('TD');
            div = document.createElement( 'DIV' );
            div.style.maxWidth = this.dd.flat[ this.collist[idx] ].sbWidth+"px";
            div.style.width = this.dd.flat[ this.collist[idx] ].sbWidth+"px";
            td.style.width = this.dd.flat[ this.collist[idx] ].sbWidth+"px";
            div.innerHTML = row[this.collist[idx]];
            //td.innerHTML=row[this.collist[idx]];
            td.appendChild( div );
            tr.appendChild(td);
        }
        // When a new row is added, we pass in the command to
        // "also highlight", meaning we want this as the chosen row
        if(alsoChoose != null) {
            this.chooseRow( this.rowCount - 1 );
        }
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // Selects a given row as the current row.
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    x_browse.chooseRow = function(idx) {
        // Turn off the highlight for currently selected row      
        if(this.dd.data.rowSelected!=-1) {
            if(x4.byId("x4browse_"+this.dd.data.rowSelected))
                x4.byId("x4browse_"+this.dd.data.rowSelected).className='';
        }
        
        // Set current row and highlight it
        this.dd.data.rowSelected = idx;
        if(x4.byId("x4browse_"+idx)) {
            x4.byId("x4browse_"+idx).className='hilite';
            this.setStatus();
        }
    }    
    
    x_browse.setStatus = function() {
        tidx = Number(this.dd.data.rowSelected)+1;
        x4.setStatus('Row '+tidx+' of '+this.dd.data.rowCount);        
    }
    
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // Attemps to edit a given row
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    x_browse.editRow = function(idx) {
        displayLayerDetail(this.table_id,'upd',idx,this);
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // LAST LINE OF CODE INVOKES THE BUILD FUNCTION, WHICH
    // MOSTLY RENDERS THE HTML
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    x_browse.build();
}

function browseDisplayRows() {
    // We assume we are always displaying at the top layer
    // 
    var topLayer = x4Layers.displayLayers.length - 1;
    self = x4Layers.displayLayers[ topLayer ];
    self.divBody.style.display='';
    
    // Work out the next highest, but skip 
    // out if we were set to -1
    if(self.dd.data.rowNext==-1) return;
    
    var max1 = self.dd.data.rowNext + 20;
    var max2 = self.dd.data.rowCount - 1;
    var next = max1 > max2 ? max2 : max1;
    for(var x=self.dd.data.rowNext;x<=next;x++) {
        self.addRowToDisplay( self.dd.data.rows[x] , x);
    }
    if(self.dd.data.rowNextS==0) self.chooseRow(0);
    self.dd.data.rowNext = next + 1;
    if(self.dd.data.rowNext < self.dd.data.rowCount-1) {
        setTimeout("browseDisplayRows()",10);
    }
}


/*
 *
 *
 * display layer: DETAIL
 * This is a BUILDER Function, it builds a subclass of DisplayLayer
 *
 *
 */
function displayLayerDetail(x4xPage,mode,rowIdx,parent) {
    var x_detail        = new displayLayer();
    x_detail.parent = parent;

    // ------------------------------------------------------
    // This function is invoked at the very end of the
    // builder function, before the layer is visible.
    //
    x_detail.build = function() {    
        this.dd          = x4Layers.ddPages[x4xPage];
        this.tabIndex    = 1000;
        this.idPrefix    = 'x4c_';
        this.table_id    = x4Layers.ddPages[x4xPage].table_id;
        this.mode        = mode;
        this.afterExit   = false;
    
        if(mode=='ins') {
            this.title = this.dd.description + " (new entry) ";
        }
        else { 
            this.title = this.dd.description + " (viewing) ";
        }
        
        this.h = document.createElement('DIV');
        this.h.className = 'detail';
        var divm = document.createElement('CENTER');
        divm.className = 'detailInner';
        this.h.appendChild(divm);
        
        
        var dTable = document.createElement('TABLE');
        dTable.className = 'detail';

        var tRow = dTable.insertRow(0);
        var tCell= tRow.insertCell(0);
        tCell.colSpan=2;
        x4.createElement('H1',tCell,this.title);


        var dRow   = dTable.insertRow(1);
        var d1     = dRow.insertCell(0);
        divm.appendChild(dTable);
        
        var div2 = document.createElement('DIV');
        div2.className = 'x4Box';
        d1.appendChild(div2);
        var tab = document.createElement('TABLE');
        tab.className = 'x4Detail';
        div2.appendChild(tab);
        
        // ------------------------------------------------------
        // Now create the table and add the inputs
        //
        var idx = 0;
        for(colname in this.dd.flat) {
            column = this.dd.flat[colname];
            if(column['uino']=='Y')   continue;
            if(colname=='skey')       continue;
            if(colname=='skey_quiet') continue;
            if(colname=='_agg')       continue;
            
            // Put the label and the input
            //
            var row = tab.insertRow(tab.rows.length);
            var label = row.insertCell(0);
            label.className = 'caption';
            label.innerHTML = column['description'];
            if(idx==0) {
                idx=1;
                label.innerHTML = 'F9:'+label.innerHTML;
                label.id = 'object_for_f9';
                label.x_context = this;
                label.onclick = function() {
                    this.x_context.lastFocus.focus();
                }
            }
            var input = x4Input.make(column,this);
            input.className = 'input';
            var tdinp = row.insertCell(1);
            tdinp.appendChild(input);
        }
        
        // ------------------------------------------------------
        // Now create the list of links to child tables
        //
        var tabIndex = 3000;
        var d2     = dRow.insertCell(1);
        d2.className = 'linksOnRight';
        var div2    = document.createElement('DIV');
        var goforit= false;    
        for(idx in this.dd.fk_children) {
            goforit = true;
            var table_chd = this.dd.fk_children[idx];
    
            var akid = document.createElement('A');
            akid.innerHTML = table_chd.description;
            akid.x_context = this;
            akid.x_page = idx;
            akid.x_description = table_chd.description;
            akid.tabIndex = ++tabIndex;
            akid.href='javascript:void(0);';
            akid.onclick = function() {
                x4Pages.load(this.x_page,this.x_description,this.x_context);
            }
            
            div2.appendChild(akid);
            x4.createElement('BR',div2,'');
        }
        if(goforit) {
            x4.createElement('BR',div2,'');
            x4.createElement('BR',div2,'');
            x4.createElement('DIV',div2,'Actions:');
            x4.createElement('BR',div2,'');
        }

        // ------------------------------------------------------
        // ...and action links
        var a = document.createElement('A');
        a.innerHTML = 'ESC: Back';
        a.href="javascript:void(0)";
        a.tabIndex = ++tabIndex;
        a.x_context = this;
        a.onclick = function() {
            this.x_context.attemptExit('pop');
        }
        div2.appendChild(a);
        x4.createElement('BR',div2,'');
    
        if(this.mode != 'ins') {
            var a = document.createElement('A');
            a.innerHTML = 'Delete';
            a.href="javascript:void(0)";
            a.tabIndex = ++tabIndex;
            a.x_context = this;
            a.onclick = function() {
                this.x_context.attemptDelete();
            }
            div2.appendChild(a);
        }
        

        if(goforit) {
            var akids  = document.createElement('A');
            akids.href="javascript:void(0)";
            akids.id = 'object_for_f8';
            akids.innerHTML='F8:LINKS';
            akids.tabIndex = 3000;
            akids.onclick = function() {
                this.focus();
            }
            d2.appendChild(akids);
            x4.createElement('BR',d2,'');
            x4.createElement('BR',d2,'');
            d2.appendChild(div2);
        }
    
        // If row index was passed, fetch it
        if(rowIdx != null) {
            this.populateRow(rowIdx);
        }
    
        
        // At very bottom of constructor, load
        // the display layer
        //
        x4Layers.pushDisplayLayer(this);
    }
    
    // ------------------------------------------------------
    // All keys are handled in input controls, tell the
    // global we handled it so it doesn't get handled twice
    //
    x_detail.bodyKeyPress = function(e) {
        var Label = x4.keyLabel(e);
        x4.debug("x_detail.bodyKeyPress: INVOKED label "+Label,'keyboard');        

        //  Escape attempts to leave 
        if(Label=='Esc') {
            x4.debug('x_detail.bodyKeyPress: ESC, attemptExit()'    ,'keyboard');
            //e.stopPropagation();
            this.attemptExit('pop');
        }
        
        if( this.inputsArrowsAndPages(e) ) return true;
        
        x4.debug('x_detail.bodyKeyPress: returning false','keyboard');        
        return false;

    } 
    
    x_detail.inputOnKeyPress = function(input,e) {
        x4.debug('x_detail.inputOnKeyPress: INVOKED','keyboard');        
        // Move around on tab keys and ENTER
        this.inputsNavigationTab(input,e);
        this.inputsNavigationEnter(input,e);
        this.inputsNavigationArrows(input,e);
        
        this.inputsArrowsAndPages(e);
    }
    
    x_detail.inputsArrowsAndPages = function(e) {
        x4.debug('x_detail.inputsArrowsAndPages: INVOKED','keyboard');        
        Label = x4.keyLabel(e);
        if(Label=='PageDown') {
            this.attemptExit('next');
            return true;
        }
        if(Label=='PageUp') {
            this.attemptExit('prev');
            return true;
        }
        if(Label=='CtrlPageUp') {
            this.attemptExit('first');
            e.preventDefault();
            e.stopPropagation();
            return true;
        }
        if(Label=='CtrlPageDown') {
            this.attemptExit('last');
            e.preventDefault();
            e.stopPropagation();
            return true;
        }
        return false;
    }
    
    
    // ------------------------------------------------------
    // These various routines deal with focus and tab order
    //
    x_detail.inputOnFocus = function(input,e) {
        input.setColor(true);
        this.lastFocus = input;
    }
    x_detail.inputOnBlur  = function(input,e) {
        input.setColor(false);
    }
    x_detail.inputOnKeyUp = function(input,e) {
        input.setColor();
    }
    x_detail.onPush    = function() { this.fixFocus(); }
    x_detail.onRestore = function() { this.fixFocus(); }

    // ------------------------------------------------------
    // Attempt to exit.  If ok, go ahead and do it
    // detail.attemptExit()
    //
    x_detail.attemptExit = function(afterExit) {
        // the action we are trying to do
        this.afterExit = afterExit;

        // Figure out if any values have changed
        var dirty = false;        
        for(idx in this.tabLoop) {
            var inpx = this.tabLoop[idx];
            if(inpx.value != inpx.x_save) {
                dirty=true;
                break;
            }
        }
        
        // If no dirty buffer its easy, just go ahead
        if(!dirty) {
            this.exit();
        }
        else {
            if(confirm("Save Changes?")) {
                this.attemptSave();
            }
            else {
                this.exit();
            }
        }
    }
    
    // detail.exit
    x_detail.exit = function(forceItem) {
        if(forceItem != null) 
            var afterExit = forceItem;
        else
            var afterExit = this.afterExit;
        
        if(afterExit=='pop')
            x4Layers.popDisplayLayer();
        else 
            this.navigate();
    }
    
    // ------------------------------------------------------
    // Navigate to a new row
    // detail.navigate
    //
    x_detail.navigate = function() {
        var dx = this.dd.data;
        var rs = Number(dx.rowSelected);
        var rc = Number(dx.rowCount);
        if(this.afterExit == 'first' && rs != 0)      rn = 0;
        if(this.afterExit == 'prev'  && rs != 0)      rn = rs - 1;
        if(this.afterExit == 'next'  && rs != (rc-1)) rn = rs + 1;
        if(this.afterExit == 'last'  && rs != (rc-1)) rn = rc - 1;
        if(rn != rs) {
            this.populateRow(rn);
            dx.rowSelected = rn;
            x4.setStatus('Row '+(rn+1)+' of '+rc);
        }
        this.afterExit = '';
    }

    // ------------------------------------------------------
    // Save the row.  If it comes back ok the return  
    // handler will take care of it
    // detail.attemptSave()
    //
    x_detail.attemptSave = function() {
        // Build a quaint little update/insert statement 
        var str=
            'x4xTable='+this.dd.table_id
            +'&x4xAjax='+this.mode
            +'&x4xRetRow=1';
        if(this.mode=='upd') {
            var skey = this.dd.data.rows[this.dd.data.rowSelected].skey;
            str+='&x4w_skey='+skey;
        }
        for(idx in this.tabLoop) {
            var input = this.tabLoop[idx];
            if(input.readOnly) continue;
            if(input.value == input.x_save) continue;
            str+='&x4c_'+input.x_dd.column_id
                +'='+encodeURIComponent(input.value);
        }
        x4.ajax(str,null,this,'attemptSaveReturn');                
    }
    
    // detail.attemptSaveReturn()
    x_detail.attemptSaveReturn = function() {
        if(typeof(x4httpData.message)=='undefined') {
            var row = x4httpData.data[this.table_id][0];
            if(this.mode=='ins') {
                this.parent.addRowToSearchResults(row);
            }
            else {
                this.parent.replaceRow(row,this.dd.data.rowSelected);
            }
            this.exit();
        }
    }

    // ------------------------------------------------------
    // Delete the row  
    // detail.attemptDelete()
    //
    x_detail.attemptDelete = function() {
        var skey = this.dd.data.rows[this.dd.data.rowSelected].skey;
        var str=
            'x4xTable='+this.dd.table_id
            +'&x4xAjax=del'
            +'&x4xRetRow=1'
            +'&x4w_skey='+skey;
        x4.ajax(str,null,this,'attemptDeleteReturn');                
    }

    // detail.attemptDeleteReturn()
    x_detail.attemptDeleteReturn = function() {
        if(typeof(x4httpData.message)=='undefined') {
            this.parent.deleteSelectedRow();
            this.exit('pop');
        }
    }

    // ------------------------------------------------------
    // Populate text boxes from the parent browse 
    // ------------------------------------------------------
    x_detail.populateRow = function(rowIdx) {
        // Get the row
        var row = this.dd.data.rows[rowIdx];
        
        // Populate the values
        for(var idx in this.tabLoop) {
            input = this.tabLoop[idx];
            colname = input.x_dd.column_id;
            if(typeof(row[colname])!='undefined') {
                input.establishValue(row[colname],'upd');
            }
            else {
                input.establishValue('','upd');
            }
        }
    }
    
    // ------------------------------------------------------
    // Provide an array of the primary key values 
    // ------------------------------------------------------
    x_detail.getPKValues = function() {
        var retval = new Object();
        var row    = this.dd.data.rows[this.dd.data.rowSelected];
        for(var idx in this.dd.flat) {
            if( this.dd.flat[idx].primary_key == 'Y') {
                retval[idx] = row[idx];
            }
        }
        return retval;
    }
  
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // LAST LINE OF CODE INVOKES THE BUILD FUNCTION, WHICH
    // MOSTLY RENDERS THE HTML
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    x_detail.build();
} 
