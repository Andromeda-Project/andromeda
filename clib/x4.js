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
/* ================================================================== *\
   ================================================================== 
   LIBRARY: GENERAL
   
   Shortcuts, Convenience, and compatibility.
   ================================================================== 
\* ================================================================== */
// DEBUG HINT: Examine keystrokes
//             to see how the various key handlers work,
//             uncomment this block and turn on firebug
//             and watch the magic
/*
document.addEventListener('keydown' ,testKeyDown,false);
document.addEventListener('keypress',testKeyPress,false);
document.addEventListener('keyup'   ,testKeyUp,false);

function testKeyDown(e) {
    var kl = keyLabel(e);
    console.log("in keydown "+kl+', '+e.keyCode+', '+e.charCode);
}
function testKeyPress(e) {
    var kl = keyLabel(e);
    console.log("in keypress "+kl+', '+e.keyCode+', '+e.charCode);
}
function testKeyUp(e) {
    var kl = keyLabel(e);
    console.log("in keyup "+kl+', '+e.keyCode+', '+e.charCode);
}
*/

// Shortcut from snippets.dzone.com/posts/show/701
function LTrim( value ) {
	var re = /\s*((\S+\s*)*)/;
	return value.replace(re, "$1");
	
}
// Shortcut from snippets.dzone.com/posts/show/701
function RTrim( value ) {
	var re = /((\s*\S+)*)\s*/;
	return value.replace(re, "$1");
	
}
// Shortcut from snippets.dzone.com/posts/show/701
function trim( value ) {
	return LTrim(RTrim(value));
	
}

// Shortcut
function byId(id) {
   return document.getElementById(id);
}
// Shortcut
function setFocus(id) {
    // This lets you pass id or object
    if(typeof(id)=='string') 
        var obj = document.getElementById(id);
    else 
        var obj = id;
    if(typeof(obj.focus)!='undefined') { 
        obj.focus();
    }
    if(typeof(obj.onfocus)!='undefined') {
        obj.onfocus();
    }
}
// Shortcut
function getProperty(object,property,defvalue) {
    defvalue = defvalue==null ? false : defvalue;
    if(object == null) return defvalue
    if(typeof(object[property])=='undefined') return defvalue;
    else return object[property];
}
// Compatibility
// Cross-browser implementation of element.addEventListener()
function addEventListener(element, type, expression) {
    if (element.addEventListener) { // Standard
        element.addEventListener(type, expression, false);
        return true;
    } else if (element.attachEvent) { // IE
        element.attachEvent('on' + type, expression);
        return true;
    } else return false;
}
// Compatibility
function eventTarget(e) {
    if(window.event) 
        return window.event.target;
    else 
        return e.currentTarget;
}

// Convenience
//
// Returns a descriptive name of special keys, such as
// 'CapsLock' or 'ShiftEnd'.
//
function keyLabel(e) {
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
}

// Shorcut, an array of time strings
var tStr = new Array();
tStr[0   ] = '12:00 am';
tStr[15  ] = '12:15 am';
tStr[30  ] = '12:30 am';
tStr[45  ] = '12:45 am';
tStr[60  ] = ' 1:00 am';
tStr[75  ] = ' 1:15 am';
tStr[90  ] = ' 1:30 am';
tStr[105 ] = ' 1:45 am';
tStr[120 ] = ' 2:00 am';
tStr[135 ] = ' 2:15 am';
tStr[150 ] = ' 2:30 am';
tStr[165 ] = ' 2:45 am';
tStr[180 ] = ' 3:00 am';
tStr[195 ] = ' 3:15 am';
tStr[210 ] = ' 3:30 am';
tStr[225 ] = ' 3:45 am';
tStr[240 ] = ' 4:00 am';
tStr[255 ] = ' 4:15 am';
tStr[270 ] = ' 4:30 am';
tStr[285 ] = ' 4:45 am';
tStr[300 ] = ' 5:00 am';
tStr[315 ] = ' 5:15 am';
tStr[330 ] = ' 5:30 am';
tStr[345 ] = ' 5:45 am';
tStr[360 ] = ' 6:00 am';
tStr[375 ] = ' 6:15 am';
tStr[390 ] = ' 6:30 am';
tStr[405 ] = ' 6:45 am';
tStr[420 ] = ' 7:00 am';
tStr[435 ] = ' 7:15 am';
tStr[450 ] = ' 7:30 am';
tStr[465 ] = ' 7:45 am';
tStr[480 ] = ' 8:00 am';
tStr[495 ] = ' 8:15 am';
tStr[510 ] = ' 8:30 am';
tStr[525 ] = ' 8:45 am';

tStr[540 ] = ' 9:00 am';
tStr[555 ] = ' 9:15 am';
tStr[570 ] = ' 9:30 am';
tStr[585 ] = ' 9:45 am';
tStr[600 ] = '10:00 am';
tStr[615 ] = '10:15 am';
tStr[630 ] = '10:30 am';
tStr[645 ] = '10:45 am';
tStr[660 ] = '11:00 am';
tStr[675 ] = '11:15 am';
tStr[690 ] = '11:30 am';
tStr[705 ] = '11:45 am';

tStr[720 ] = '12:00 pm';
tStr[735 ] = '12:15 pm';
tStr[750 ] = '12:30 pm';
tStr[765 ] = '12:45 pm';

tStr[780 ] = ' 1:00 pm';
tStr[795 ] = ' 1:15 pm';
tStr[810 ] = ' 1:30 pm';
tStr[825 ] = ' 1:45 pm';
tStr[840 ] = ' 2:00 pm';
tStr[855 ] = ' 2:15 pm';
tStr[870 ] = ' 2:30 pm';
tStr[885 ] = ' 2:45 pm';
tStr[900 ] = ' 3:00 pm';
tStr[915 ] = ' 3:15 pm';
tStr[930 ] = ' 3:30 pm';
tStr[945 ] = ' 3:45 pm';
tStr[960 ] = ' 4:00 pm';
tStr[975 ] = ' 4:15 pm';
tStr[990 ] = ' 4:30 pm';
tStr[1005] = ' 4:45 pm';
tStr[1020] = ' 5:00 pm';
tStr[1035] = ' 5:15 pm';
tStr[1050] = ' 5:30 pm';
tStr[1065] = ' 5:45 pm';
tStr[1080] = ' 6:00 pm';
tStr[1095] = ' 6:15 pm';
tStr[1110] = ' 6:30 pm';
tStr[1125] = ' 6:45 pm';
tStr[1140] = ' 7:00 pm';
tStr[1155] = ' 7:15 pm';
tStr[1170] = ' 7:30 pm';
tStr[1185] = ' 7:45 pm';
tStr[1200] = ' 8:00 pm';
tStr[1215] = ' 8:15 pm';
tStr[1230] = ' 8:30 pm';
tStr[1245] = ' 8:45 pm';

tStr[1260] = ' 9:00 pm';
tStr[1275] = ' 9:15 pm';
tStr[1290] = ' 9:30 pm';
tStr[1305] = ' 9:45 pm';
tStr[1320] = '10:00 pm';
tStr[1335] = '10:15 pm';
tStr[1350] = '10:30 pm';
tStr[1365] = '10:45 pm';
tStr[1380] = '11:00 pm';
tStr[1395] = '11:15 pm';
tStr[1410] = '11:30 pm';
tStr[1425] = '11:45 pm';


/* ================================================================== *\
   ================================================================== 
   LIBRARY: Framework, except for Ajax
   
   Routines that expect an x4 context.
   ================================================================== 
\* ================================================================== */ 
// Put out a debug message
function x4Debug(msg) {
   if(ob('x4DivDebug')) {
      ob('x4DivDebug').innerHTML += msg+"<br/>\n";
   }
   if(typeof(console)!='undefined') {
       console.log(msg);
   }
}

// Put out an error message
function x4Error(msg) {
   if(ob('x4DivDebug')) {
      ob('x4DivDebug').innerHTML += msg+"<br/>\n";
   }
   if(typeof(console)!='undefined') {
       console.log(msg);
   }
   alert(msg);
}

// -------------------------------------------------------------
// Register a context as serving a certain purpose for a 
// certain table
// -------------------------------------------------------------
function x4RegisterContext(table,purpose,context) {
    if(typeof(x4global[table])=='undefined') {
        x4global[table] = new Object();
    }
    x4global[table][purpose] = context;

    // When a context registers itself as the "master" of a table,
    // it is accepting responsibility for storing the current data set
    // for that table.  It needs to have the "data" object that has
    // certain properties.  The data object also has to have a pointer
    // back to its parent so other routines can invoke methods for
    // that context.
    if(purpose=='master') {
        if(!getProperty(context,'data')) {
            context.data = new Object();
            context.data.rowSelected = -1;
            context.data.rowCount    = 0;
        }
        context.data.x_context = context;
    }
}

// -------------------------------------------------------------
// Find the context for a certain table for a certain purpose 
// -------------------------------------------------------------
function x4FindContext(table,purpose) {
    return x4global[table][purpose];
}

// -------------------------------------------------------------
// Find the data object for a table.  Hint: it will be part of
// the grid or browse for that table.
// -------------------------------------------------------------
function x4FindDataObject(table) {
    var retval = x4FindContext(table,'master');
    return retval.data;
}
// -------------------------------------------------------------
// Make a tab visible for a given context 
// -------------------------------------------------------------
function x4ActivateContext(table,purpose) {
    var context = x4FindContext(table,purpose);
    
    // Now we have to go through a few layers of indirection
    // to get the right tab activated.
    //
    // FIRST : get the HTML parent of that context.  This
    //         will be a DIV of class x4Tab or x4TabSelected, this
    //         is the HTML object that this context is embedded into.
    var htmlPar   = context.htmlPar;
    // SECOND: Get the Tab # of that DIV
    var idx       = htmlPar.x_idx
    // THIRD : Tell the parent context to select that tab
    htmlPar.x_context.selectTab(idx);
}


// ---------------------------------------------------------------
// This wrapper to x4MakeInput (immediately follows) creates
// an input with various extra stuff, like a date image off
// to the right, an error SPAN.  This routine also directly
// adds the input to its parent HTML object.
// ---------------------------------------------------------------
function x4MakeInputComplex(colinfo,context,flagGrid,htmlPar) {
    // Start with a basic input, and add it to its parent's
    // HTML Object.
    var input = x4MakeInput(colinfo,context);
    input.x_context = context;
    htmlPar.appendChild(input);
    
    // For date types we will put a little doo-dad right next to it
    if(input.x_type_id == 'date') {
        var img = document.createElement('IMG');
        img.x_input = input;
        img.className = 'x4dhtml_calendar';
        img.onclick=function() {
            displayCalendar(this.x_input,'mm/dd/yyyy',this,true);        
        }
        img.value = 'Cal';
        img.src  = 'clib/dhtmlgoodies_calendar_images/calendar1.gif';
        htmlPar.appendChild(img);
    }
    
    // On a grid we put the error span *below*
    //if(flagGrid) {
    //    var br = document.createElement('BR');
    //    br.style.visibility = 'none';
    //    input.x_errorbreak = br;
    //    htmlPar.appendChild(br);
    //}
    
    // now put in the error span
    var span = document.createElement('SPAN');
    span.className = 'x4SpanError';
    span.style.visibility = 'none';
    input.x_errorspan = span;
    htmlPar.appendChild(span);
    
    input.setMode('blank');
    
    return input;
}

// ---------------------------------------------------------------
// Make an input based on information about the column
// ---------------------------------------------------------------
function x4MakeInput(colinfo,context) {
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
    else if(colinfo.x_type_id=='cbool' ) {
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
            opt.innerHTML = tStr[x];
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
    input.x_type_id  = colinfo.type_id;
    input.x_column   = colinfo.name
    input.x_table        = getProperty(colinfo,'table'      ,'');
    input.x_primary_key  = getProperty(colinfo,'primary_key','N');
    input.x_automation_id= getProperty(colinfo,'automation_id','NONE');
    input.x_auto_formula = getProperty(colinfo,'auto_formula' ,'');
    input.x_table_id_fko = getProperty(colinfo,'table_id_fko' ,'');
    input.x_fkdisplay    = getProperty(colinfo,'fkdisplay'    ,'');
    input.x_save     = '';
    input.x_saved    = '';
    input.x_mode     = '';
    input.x_error    = false;
    input.x_errorspan= false;
    
    // Correction for ajax dynamic list, don't want two boxes!
    if(input.x_table_id_fko!='') {
        input.autoComplete = false;
    }

    
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // This sets the value according to the type.  Originally
    // coded to handle the case of setting dates.
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    input.setValue = function(value) {
        if(this.x_type_id == 'dtime') {
            if(value!='' && value!=null) {
                this.value = value.slice(0,19);
            }
            /*
            var year  = value.slice(0,4);
            var month = value.slice(5,7);
            var day   = value.slice(8,10);
            var hrs   = value.slice(11,13);
            var mins  = value.slice(14,16);
            var secs  = value.slice(17,19);
            x4Debug(' '+year+' '+month+' '+day+' '+hrs+' '+mins+' '+secs);
            var temp = new Date( year,month,day,hrs,mins,secs);
            //var temp = new Date(value);
            this.value = temp.toLocaleString();
            */
        }
        else if(this.x_type_id=='date') {
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
                this.value = trim(value);
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
        
        // if blank mode is being set, clear the values
        if(mode=='blank') this.value = '';
        
        // Work out a flag for read-only columns based on automation
        // start out assuming the column can be written
        input.readOnly = false;
        var noro = '*NONE*DEFAULT*SEQDEFAULT*BLANK*';
        if(noro.indexOf('*'+this.x_automation_id+'*')==-1) {
            // This column did not match any of those, so it
            // is always read only
            input.readOnly = true;
        }
        else {
            // This column is normally editable, but if it is
            // the primary key in update mode, the answer is no
            if(mode == 'upd') {
                if(getProperty(this,'x_primary_key','N')=='Y') {
                    this.readOnly = true;
                }
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
    input.setColor = function() {
        // Several branches need this, so may as well do it now
        var suffix = this.x_selected ? 'Selected' : '';

        // If the error flag is set it does not matter
        // what mode we are in
        if(this.x_error) {
            this.className = 'x4err'+suffix;
            return;
        }
        
        // The major switch is on mode.
        if(this.x_mode == 'blank') {
            this.className = 'x4ro';
        }
        
        if(this.x_mode == 'upd') {
            if(this.readOnly) {
                if(getProperty(this,'x_primary_key','N')=='Y') 
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
    
    // set tab index and tell the context who we are
    context.tabIndex++;
    input.tabIndex = context.tabIndex;
    // If there is an id prefix, use it to generate an id
    if(getProperty(context,'idPrefix','')!='') {
        input.id = context.idPrefix + colinfo['name'];
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // Here are various registrations in the context 
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // The tabLoop is a numbered array of the inputs on the control
    // It is used for iteration of inputs.
    if(getProperty(context,'tabLoop','')=='') {
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
    
    // The inputsByName is an assoc array keyed on column name
    if(getProperty(context,'inputsByName','')=='') {
        context.inputsByName = new Object();
    }
    context.inputsByName[colinfo['name']] = input;
    
    // Set this as first control if not set yet
    if(getProperty(context,'firstFocus','')=='') {
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
    if(getProperty(context,'event_onfocus','') != '') {
        input.onfocus = function(e) {
            this.x_context.event_onfocus(this,e);
        }
    }
    if(getProperty(context,'event_onblur','') != '') {
        input.onblur = function(e) {
            this.x_context.event_onblur(this,e);
        }
    }
    if(getProperty(context,'event_onkeyup','') != '') {
        input.onkeyup = function(e) {
            this.x_context.event_onkeyup(this,e);
        }
    }
    if(getProperty(context,'event_onkeypress','') != '') {
        input.onkeypress = function(e) {
            this.x_context.event_onkeypress(this,e); 
        }
    }
    if(getProperty(context,'event_onkeydown','') != '') {
        input.onkeydown = function(e) {
            this.x_context.event_onkeydown(this,e); 
        }
    }
    if(getProperty(context,'event_onchange','') != '') {
        input.onchange = function(e) {
            this.x_context.event_onchange(this,e); 
        }
    }
    
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // Give them back the input so they can use it
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    return input;
}

/* ================================================================== *\
   ================================================================== 
   LIBRARY: AJAX
   
   Lowest level ajax routines
   ================================================================== 
\* ================================================================== */ 

// Three public variables that we always use
var x4http      = false;       // the request object
var x4httpData  = false;       // will hold the returned data
var x4httpRH    = false;
var x4httpRHObj = false;
var x4httpRHFnc = false;
var x4Objects   = new Object() // list of objects and their responsibilities
var x4Self      = false;       // Current object, set by ajax callers  
                               // so the return can find the object

// and make a real value for x4http
if(navigator.appName == "Microsoft Internet Explorer")
  x4http = new ActiveXObject("Microsoft.XMLHTTP");
else
  x4http = new XMLHttpRequest();

// if x4global does not exist, create it
if(typeof(x4global)=='undefined') {
   var x4global = new Object();
}
  
// The ajax caller
// Parm 1: the string, with no leading ? 'get1=val&get2=val'
// Parm 2: function reference to the handler that should 
//         be called to deal with the data
// Parm 3: An object to use instead of 'handler'
// Parm 4: The *NAME* of the object's method to call, not
//             an object reference, pass the name!
function x4Ajax(getString,handler,m2obj,m2fnc) {
    x4httpData= false;
    // Set all function handlers
    x4httpRH    = handler == null ? false : handler;
    x4httpRHObj = m2obj   == null ? false : m2obj;
    x4httpRHFnc = m2fnc   == null ? false : m2fnc;

    // if there is an error display, clear it
    if(byId('x4ServerError')) byId('x4ServerError').innerHTML = '';
    
    // Go ahead and make the call
    x4http.open('get', 'x4index.php?'+getString);
    x4http.onreadystatechange = x4ResponseHandler;
    x4http.send(null);
}

// This is the response handler.
function x4ResponseHandler() {
   if(x4http.readyState == 4){
      // Attempt to retrieve data
      var dataIsOK=false;
      try {
         // Attempt to convert JSON to objects
         eval('x4httpData = ('+x4http.responseText+')');
         dataIsOK=true;
      }
      catch(e) {
         alert('Could not process server response!');
         x4Debug(x4http.responseText);
         // if there is an error display, clear it
         if(byId('x4ServerError'))
             byId('x4ServerError').innerHTML = x4http.responseText;
      }
      
      // If data retrieved, attempt to process it
      if(dataIsOK) {
         // Framework handling of data would go here
         x4ResponseHandlerFramework();
         
         // And now we do custom handling
         if(x4httpRH)   {
             x4httpRH();
             x4httpRH = false;
         }
         if(x4httpRHObj) {
             x4httpRHObj[x4httpRHFnc]() ;
             x4httpRHObj = false;
         }
         
         // Clear out x4http response text and 
         // the x4httpData, they would be taking up space
         //x4httpData = false;
      }
   }  
}

function x4ResponseHandlerFramework() {
    // Display any errors.  If there are any
    // errors, halt processing
    //
    var idx;
    if(typeof(x4httpData.message)!='undefined') {
        if(typeof(x4httpData.message.error)!='undefined') {
            var msg="ERRORS REPORTED:";
            for(idx in x4httpData.message.error) {
                msg+="\n\n"+x4httpData.message.error[idx];
                x4Debug(x4httpData.message.error[idx]);
            }
            alert(msg);
        }
        if(typeof(x4httpData.message.debug)!='undefined') {
            x4Debug("Server debug messages follow:");
            for(idx in x4httpData.message.debug) {
                x4Debug(x4httpData.message.debug[idx]);
            }
            x4Debug("End of server debug messages:");
        }
    }

    
    // Replace any HTML elements
    var ID;
    if(typeof(x4httpData.html)!='undefined') {
        var ID;
        for(ID in x4httpData.html) {
            byID(ID).innerHTML = x4httpData.html[ID]; 
        }
    }
   

    // Run script
    var x; 
    var y;
    if(typeof(x4httpData.script)!='undefined') {
        for(x in x4httpData.script) {
            for(y in x4httpData.script[x]) {
                eval(x4httpData.script[x][y]);
            }
        }
    }   
}
/* ================================================================== *\
   ================================================================== 
   LIBRARY: x4Page and Return Handler
   
   This routine goes after a certain page with no parameters
   ================================================================== 
\* ================================================================== */ 
// Replace current content with a new page
function x4Page(x4xPage) {
    // Build the page request and send it
    getString='x4xPage='+x4xPage;
    byId('andromeda_main_content').style.height = '500px';
    x4Ajax(getString,x4PageReturnHandler);
}

function x4PageReturnHandler() {
    // Set the title
    document.title = x4global.pageTitle;

    // Clear out the current content window, and assign
    // it a blank context object.
    var aMain = byId('andromeda_main_content');
    aMain.innerHTML = '';
    aMain.x4ContextObject = new Object();
    
    // Clear out the public list of objects and their responsibilities
    x4Objects = new Object();
    
    // Go into the recurse rendering routine.  Give it the main 
    // content window so it will add children to that.
    //aMain.style.visibility='hidden';
    x4ObjectRender(x4global,aMain.x4ContextObject,aMain);
    //aMain.style.visibility='visible';
    
    // Put in a debug handler
    div = document.createElement('div');
    div.className = 'x4ServerError';
    div.id        = 'x4ServerError';
    aMain.appendChild(div);
}

// Object Rendering routine.  This routine expects three 
// parameters:
//
// 1. Object Reference: meta-data 
// 2. Object Reference: current context object.  This object solves
//       the problem of incomplete encapsulation with js/html, it
//       serves the purpose of the "container" for the html object.
//       It will be assigned the property x_context of every rendered
//       HTML object until it is masked by a downstream 
// 3. HTML Object: add any rendered objects to this object
function x4ObjectRender(mdObj,contextObj,htmlPar) {
    var objectType = getProperty(mdObj,'objectType','');
    
    // Figure out which class to use to render this object
    if     (getProperty(mdObj,'flagHTML','N')=='Y') 
                                    x4HTML(    mdObj,contextObj,htmlPar);
    else if(objectType=='input'   ) x4Input(   mdObj,contextObj,htmlPar);
    else if(objectType=='tabBar'  ) x4TabBar(  mdObj,contextObj,htmlPar);
    else if(objectType=='titleBar') x4TitleBar(mdObj,contextObj,htmlPar);
    else if(objectType=='detail'  ) x4Detail(  mdObj,contextObj,htmlPar);
    else if(objectType=='browse'  ) x4Browse(  mdObj,contextObj,htmlPar);
    else if(objectType=='grid'    ) x4Grid(    mdObj,contextObj,htmlPar);
    else                            x4Blank(   mdObj,contextObj,htmlPar);
}

function x4ObjectRenderRecurse(mdObj,contextObj,htmlPar) {
    var kids = getProperty(mdObj,'kids','');
    if(kids!='') {
        for(var x in kids) {
            x4ObjectRender(kids[x],contextObj,htmlPar);
        }
    }
}
/* ================================================================== *\
   ================================================================== 
   RENDER LIBRARY: HTML

   Very simple, create the element as a child of the parent and
   recurse children.
   ================================================================== 
\* ================================================================== */
function x4HTML(mdObj,contextObj,htmlPar) {
    // Create the object and make two simple assignments
    var html = document.createElement(mdObj.objectType);
    html.x_context = contextObj;
    html.className = getProperty(mdObj,'className');
    html.innerHTML = getProperty(mdObj,'innerHTML');
    if(mdObj.objectType == 'TABLE') {
        html.cellPadding = 0;
        html.cellSpacing = 0;
    }
    htmlPar.appendChild(html);
    
    // Now recurse the kids
    x4ObjectRenderRecurse(mdObj,html.x_context,html);
}
/* ================================================================== *\
   ================================================================== 
   RENDER LIBRARY: Input

   Generates a single input and puts it onto the form. 
   ================================================================== 
\* ================================================================== */
// quick search: rinput  (render input)
function x4Input(mdObj,contextObj,htmlPar) {
    x4MakeInputComplex(mdObj.column,contextObj,false,htmlPar);
}
   
/* ================================================================== *\
   ================================================================== 
   RENDER LIBRARY: Title bar

   Just about the simplest one we have 
   ================================================================== 
\* ================================================================== */ 
function x4TitleBar(mdObj,contextObj,htmlPar) {
    var titleBar = document.createElement('DIV');
    titleBar.x_context = contextObj;
    titleBar.className='x4TitleBar';
    titleBar.innerHTML = mdObj.title;
        
    htmlPar.appendChild(titleBar);
}
/* ================================================================== *\
   ================================================================== 
   RENDER LIBRARY: Blank

   This class is for objects in the UI hierarchy that are "blank", 
   that don't actually hold anything.  All it does it immediately
   attempt to recurse the child objects. 
   ================================================================== 
\* ================================================================== */ 
function x4Blank(mdObj,contextObj,htmlPar) {
    var kids = getProperty(mdObj,'kids','');
    if(kids!='') {
        for(var x in kids) {
            x4ObjectRender(kids[x],contextObj,htmlPar);
        }
    }
}
/* ================================================================== *\
   ================================================================== 
   LIBRARY: Tab bar

   Render the bar, recurse to contained elements, event handlers.
   ================================================================== 
\* ================================================================== */ 
function x4TabBar(mdObj,contextObj,htmlPar) {
    // Top level element is the container for tabs and surfaces
    var retVal = document.createElement('DIV');

    // This is important: The tab bar establishes a new context.
    // We will create a new contextObj object and pass that down
    // to children.  This is how we will keep track of current
    // tab and any other context common to all tabs.
    var newContext  = new x4TabBarContext();
    newContext.kids = new Object();
    retVal.x_contextParent  = contextObj;
    retVal.x_context        = newContext;
    
    // Set some more mundane stuff for the tab bar
    retVal.className ='x4TabContainer';
    htmlPar.appendChild(retVal);
    
    // this is a keyboard handler, believe it or not.  The idea
    // is to create a hidden input that is tied into the context.
    // The id, "object_for_f9", causes the framework to pass
    // f9 keystrokes to its onclick method.
    var inpH = document.createElement('input');
    inpH.type='hidden';
    inpH.id = 'object_for_f9';
    inpH.x_context = newContext;
    inpH.onclick = function(e) {
        this.x_context.selectTab(this.x_context.nextKeyboard);
    }
    htmlPar.appendChild(inpH);
    
    // Add a div that will be the tab bar itself
    var x4TabBar = document.createElement('DIV');
    retVal.appendChild(x4TabBar);
    x4TabBar.className = 'x4TabBar';
    x4TabBar.x_context = newContext;
        
    // Now recurse the child tabs.  There are two tasks for each
    // tab.  First is to make the SPAN, next is to render the
    // object inside the tab.
    for(var idx in mdObj.kids) {
        newContext.tabCount ++;
        var kid = mdObj.kids[idx];
        newContext.kids[idx] = kid; // permanent record of tab

        // Work out the details for the span            
        var caption = getProperty(kid,'caption');
        if(kid.objectType == 'browse')  caption = 'Browse';
        if(kid.objectType == 'detail')  caption = 'Detail';
        var span = document.createElement('SPAN');
        span.onmouseover = function() {
            this.className='mouseover';
        }
        span.onmouseout  = function() {
            if(this.x_selected) 
                this.className = 'selected';
            else 
                this.className = '';
        }
        span.onclick     = function() {
            this.x_context.selectTab(this.x_idx);
        }
        span.id = 'spantab_'+idx;
        span.innerHTML = caption;
        span.x_selected= false;
        span.x_idx     = idx;
        span.x_context = newContext;
        x4TabBar.appendChild(span);
        
        // Render the child tab here as a DIV
        var kidDiv = document.createElement('DIV');
        kidDiv.className = 'x4Tab';
        kidDiv.id        = 'divtab_'+idx
        kidDiv.x_idx     = idx;
        kidDiv.x_context = newContext;
        retVal.appendChild(kidDiv);
        
        // Every tab is expected to have children, because a tab by
        // itself doesn't really do anything.  So we assume we will
        // render each child item.
        x4ObjectRender(kid,newContext,kidDiv);
    }
    newContext.selectTab(0);
}
// ---------------------------------------------------------------
// Context Controller.  All of the objects within a 
// context have property x_context that points to an
// instance of this function prototype.  This gives us
// a measure of encapsulation.
// ---------------------------------------------------------------
function x4TabBarContext() {
    // This is populated by x4TabBar() as it recurses tabs
    this.kids = false;
    // These are defaults
    this.selectedTab  = -1;
    this.nextKeyboard = 0;
    this.tabCount     = 0;

    // -----------------------------------------------------------
    // Function that selects a tab.  This function makes any
    // visible tab invisible, then selects the new one, and
    // invokes the object's Onfocus().
    // -----------------------------------------------------------
    this.selectTab = function(idxNew) {
        // Find currently selected
        var idxOld = this.selectedTab;
        
        // If they are not the same, turn off current and
        // turn on the new
        if(idxOld != idxNew) {
            // This is all of the visual stuff
            if(idxOld!=-1) {
                byId('spantab_'+idxOld).x_selected= false;
                byId('spantab_'+idxOld).className = '';
                byId('divtab_' +idxOld).className = 'x4Tab';
            }
            this.selectedTab = idxNew;
            byId('spantab_'+idxNew).x_selected= true;
            byId('spantab_'+idxNew).className = 'selected';
            divTab = byId('divtab_'+idxNew);
            divTab.className = 'x4TabSelected';
            if(divTab.firstChild!=null) {
                divTab.firstChild.x_context.x4OnActivate();
            }
            
            // remove f9 from all tabs
            for(var idx in this.kids) {
                span = byId('spantab_'+idx);
                span.innerHTML = span.innerHTML.replace('F9: ','');
            }
            // Figure out which one is next and label it
            var idxLabel = Number(idxNew) + 1;
            if(idxNew == (this.tabCount - 1)) {
                idxLabel = 0;
            }
            this.nextKeyboard = idxLabel;
            span = byId('spantab_'+idxLabel);
            span.innerHTML = 'F9: '+span.innerHTML;
        }
    }
}
/* ================================================================== *\
   ================================================================== 
   LIBRARY: search browse

   Render the HTML table and set up event listeners on the
   widgets.  
   
   The objects in here have their own context.
   ================================================================== 
\* ================================================================== */ 
function x4Browse(mdObj,contextObj,htmlPar) {
    // mdObj is the original associative array in x4global.
    // Pluck out the properties of interest and make them properties
    // of the new context object.  This is how we establish a
    // resemblence of encapsulation for HTML object + JS code.
    var newContext         = new x4BrowseContext(mdObj);
    newContext.htmlPar     = htmlPar;
    x4RegisterContext(mdObj.table,'browse',newContext);
    x4RegisterContext(mdObj.table,'master',newContext);

    // This is important: The browse establishes a new context.
    // We will create a new contextObj object and pass that down
    // to children.  This is how we will keep track of current
    // tab and any other context common to all tabs.
    var div = document.createElement('DIV');
    div.className = 'x4Browse'
    div.x_contextParent = contextObj;  // the context for the div itself
    div.x_context       = newContext;
    htmlPar.appendChild(div);
    
    var divHelp = document.createElement('DIV');
    divHelp.className = 'x4BrowseHelp';
    divHelp.x_context = newContext;
    divHelp.innerHTML = newContext.helpText;
    div.appendChild(divHelp);
    
    // Construct a Table
    var hTab = document.createElement('TABLE');
    hTab.cellPadding = 0;
    hTab.cellSpacing = 0;
    hTab.className   = 'x4Browse';
    hTab.x_context   = newContext;
    div.appendChild(hTab);
    newContext.htmlTable = hTab;
    
    // Make the row of headers and row of inputs by cycling through
    // the metat data that was copied in from the server
    var trh = hTab.insertRow(hTab.rows.length);
    var tri = hTab.insertRow(hTab.rows.length);
    newContext['headers'] = new Object();
    for(var idx in newContext.columns) {
        column = newContext.columns[idx];
        
        // establish sort order as first column;
        if(newContext.sortCol == '') {
            newContext.sortCol = column['name'];
            newContext.sortDir = 'DESC';
        }
        
        // The header cell
        var cellh = document.createElement('TH');
        cellh.innerHTML = column['description'];
        cellh.x_caption = column['description'];
        cellh.x_context = newContext;
        cellh.x_name    = column['name'];
        cellh.onmouseover = function(e) { this.className = 'highlight'; }
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
        // Keep list of references to headers by column name, for settting
        // and changing styles based on sort order
        newContext['headers'][column['name']] = cellh;
        
        // The input cell
        var cell = document.createElement('TD');
        var input= x4MakeInput(column,newContext);

        cell.appendChild(input);
        tri.appendChild(cell);
    }
    
    newContext.setSort(newContext.sortCol,'DESC',true);

}

// ---------------------------------------------------------------
// Context Controller.  All of the objects within a 
// context have property x_context that points to an
// instance of this function prototype.  That is how we
// treat collections of objects together.
// ---------------------------------------------------------------
// This is how you do inheritance in Javascript
x4BrowseContext.prototype = new x4Context();
// Here is how we set up our constructor
x4BrowseContext.prototype.constructor = x4BrowseContext;

// quick search: fx4bc
function x4BrowseContext(mdObj) {
    // This is used by x4MakeInput to generate an ID
    this.tabIndex    = 1000;
    this.idPrefix    = 'x4w_';
    this.table       = mdObj.table;
    this.sortCol     = '';
    this.sortDir     = 'ASC';
    this.columns     = mdObj.columns;
    this.collist     = mdObj.collist;
    this.limit       = 15;
    this.data       = new Object()
    this.data.rowCount    = 0;
    this.data.rowSelected =-1;
    this.data.rows        = new Array();

    this.helpText = ""
+"Start typing in any box to begin searching.<br/><br/>"
+"Use dashes to search for ranges, like a-m.<br/><br/>"
+"Use commas to search for lists, like a,j,m or 1,3-5,7.<br/><br/>"
+"ESC clears the search.<br/><br/>"
+"Arrow keys to move up and down.<br/><br/>"
+"PageUp and PageDown go to first and last.<br/><br/>"
+"Shift-UpArrow and Shift-DownArrow change sort order.<br/><br/>"
+"ENTER edits current row.<br/><br/>"
+"Clicking a row edits that row.<br/><br/>"
+"Clicking a column header changes sort order.<br/><br/>"

    // -----------------------------------------------------------
    // Simplest on activate: set focus on last selected    
    // -----------------------------------------------------------
    this.x4OnActivate = function() {
        this.fixFocus();
    }

    // -----------------------------------------------------------
    // Keep track of last input that had focus, so we can set
    // the focus when returning to this context.    
    // -----------------------------------------------------------
    this.event_onfocus = function(input,e) { 
        x4InputSaveFocus(input,e);
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // Keyup is used for the keystrokes that trigger a search
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // x4Browse.event_onkeyup
    // x4Browse.onkeyup
    this.event_onkeyup = function(input,e) {
        if(keyLabel(e)=='') {
            this.fetchData();
        }
    }            
    
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // The keydown handler is used for special keys like arrows,
    // ESC and so forth.  Using keydown makes the program very
    // responsive, because actions are occuring even while the
    // key is springing back up as the user releases his/her finger.
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // x4Browse.event_onkeydown
    // x4Browse.onkeydown
    this.event_onkeydown = function(et,e) {
        // Everything in here is about special keys, so if the keystroke
        // was not special we don't care.
        Label = keyLabel(e);
        if(Label=='') return;

        // Move around on tab keys
        x4InputsNavigationTab(et,e);
        
        // Escape key clears all search results
        if(Label == 'Esc') { 
            for(var idx in this.tabLoop) {
                input = this.tabLoop[idx];
                input.x_old = '';
                input.value = '';
            }
            this.purgeDataAndHTML(); 
            return; 
        }
        // ENTER selects the row and edits it
        if(Label == 'Enter' && this.data.rowSelected >= 0) {
            this.editRow(this.data.rowSelected);
            return;
        }
        if(Label=='DownArrow') {
            if(this.data.rowSelected < (this.data.rowCount-1)) {
                this.chooseRow(this.data.rowSelected+1);
            }
            e.preventDefault();
        }
        if(Label=='UpArrow')   {
            if(this.data.rowSelected > 0) {
                this.chooseRow(this.data.rowSelected - 1);
            }
            e.preventDefault();
        }
        if(Label=='PageUp') {
            this.chooseRow(0);
            e.preventDefault();
        }
        if(Label=='PageDown') {
            this.chooseRow(this.data.rowCount - 1);
            e.preventDefault();
        }
        if(Label=='ShiftUpArrow') {
            this.setSort(et.x_column,'DESC');
        }
        if(Label=='ShiftDownArrow') {
            this.setSort(et.x_column,'ASC');
        }
    }
        
    // -----------------------------------------------------------
    // Set the sort order 
    // -----------------------------------------------------------
    this.setSort = function(sortCol,sortDir,nofetch) {
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
            header.innerHTML = header.x_caption;
            header.className = '';
        }
        
        // Set styles for new header
        this.sortCol = sortCol;
        this.sortDir = sortDir;
        var header = this.headers[this.sortCol];
        if(sortDir == 'ASC') {
            header.innerHTML = '&dArr; '+header.x_caption;
        }
        else {
            header.innerHTML = '&uArr; '+header.x_caption;
        }
        header.className = 'sorted';

        // Finally, go get the data.
        if(nofetch == null) {
            this.fetchData(true);
        }
    }
    

    // -----------------------------------------------------------
    // Clears out all of the prior search results from the 
    // data cache and removes any HTML rows.
    // -----------------------------------------------------------
    // x4Browse.purgeDataAndHTML
    this.purgeDataAndHTML = function() {
        this.data.rowSelected=-1;
        this.data.rowCount = 0;
        this.data.rows = new Object();
        while(this.htmlTable.rows.length > 2 ) {
            this.htmlTable.deleteRow(this.htmlTable.rows.length - 1);
        }
    }
    
    // x4Browse.fetchData
    this.fetchData = function(force) {
        // notice we are not even looking at the event, we will
        // read the values of the inputs directly.  If they have
        // changed, we will run a search.
        var goforit = false;
        var getVals = '';
        for(idx in this.tabLoop) {
            var input = this.tabLoop[idx];
            var valOld = input.x_old;
            var valNew = input.value;
            if(valOld != valNew ) {
                goforit = true;
                input.x_old = input.value;
            }
            // Do this just in case
            getVals +='&'+input.id+'='+encodeURIComponent(valNew);
        }
        if(goforit || force) {
            x4Self = this;
            var getString
                ='x4xAjax=bsrch'
                +'&x4xTable='+this.table
                +'&sortCol=' +this.sortCol
                +'&sortDir=' +this.sortDir
                +'&columns=' +this.collist
                +'&limit='   +this.limit
                +getVals;
            x4Ajax(getString,null,this,'returnHandler');
        }
    }
    
    // x4Browse.returnHandler
    this.returnHandler = function() {
        // Clear the search data and delete the html table rows
        this.purgeDataAndHTML();
            
        // Loop through the search results, if there are any,
        // and add them in.
        var tab = this.table;
        if(typeof(x4httpData.data)=='undefined') return;
        if(typeof(x4httpData.data[tab])=='undefined') return;
        
        // There is data, so let's copy it to ourself
        this.data.rows = x4httpData.data[tab];
        this.data.rowCount = this.data.rows.length;
        
        // Add each row to the display now
        for(x in this.data.rows) {
            this.addRowToDisplay( this.data.rows[x] , x);
        }
        if(this.data.rowCount > 0) {
            this.chooseRow(0);
        }
    }

    // -------------------------------------------------------------
    // Replace the currently selected row
    // -------------------------------------------------------------
    this.replaceRow = function(row) {
        this.data.rows[ this.data.rowSelected ] = row;
        this.htmlTable.deleteRow( Number(this.data.rowSelected)+2 );
        this.addRowToDisplay( row, Number(this.data.rowSelected));
        this.chooseRow(this.data.rowSelected);
    }

    // -------------------------------------------------------------
    // Add a row to Search Results.
    // This adds a row to the data set, and then adds that row
    // to the visual display.  Used when the detail tab saves
    // a new row.
    // -------------------------------------------------------------
    this.addRowToSearchResults = function(row) {
        this.data.rowCount++;
        this.data.rows[ this.data.rowCount-1 ] = row;
        this.addRowToDisplay(row, Number(this.data.rowCount)-1);
        this.chooseRow(Number(this.data.rowCount) - 1);
    }

    // -------------------------------------------------------------
    // Add a row to the visual display
    // -------------------------------------------------------------
    // x4Browse.addRowToDisplay
    this.addRowToDisplay = function(row,idx,alsoChoose) {
        // Here is where we add a visual row to the search results
        htab = this.htmlTable;
        tr = htab.insertRow(Number(idx)+2);
        tr.x_idx     = idx;
        tr.id        = "x4browse_"+tr.x_idx;
        tr.x_context = this;
        tr.onmouseover = function(e) {
            this.className = 'highlight';
        }
        tr.onmouseout = function(e) {
            if(this.x_idx == this.x_context.data.rowSelected) {
                this.className = 'selected';
            }
            else {
                this.className = '';
            }
        }
        tr.onclick = function(e) {
            this.x_context.chooseRow(this.x_idx);
            this.x_context.fixFocus();
            this.x_context.editRow(this.x_idx);
        }
        for(var idx in this.columns) {
            td = document.createElement('TD');
            td.innerHTML=row[this.columns[idx]['name']];
            tr.appendChild(td);
        }
        
        // When a new row is added, we pass in the command to
        // "also highlight", meaning we want this as the chosen row
        if(alsoChoose != null) {
            this.chooseRow( this.rowCount - 1 );
        }
    }

    // -------------------------------------------------------------
    // Selects a given row as the current row.  This is very
    // important for other tabs that will be looking at this
    // object and asking it who the current row is.
    // -------------------------------------------------------------
    // x4Browse.chooseRow
    this.chooseRow = function(idx) {
        // Turn off the highlight for currently selected row      
        if(this.data.rowSelected!=-1) {
            byId("x4browse_"+this.data.rowSelected).className='';
        }
        
        // Set current row and highlight it
        this.data.rowSelected = idx;
        if(byId("x4browse_"+idx)) {
            byId("x4browse_"+idx).className='selected';
        }
    }    
    
    // -------------------------------------------------------------
    // Attemps to edit a given row
    // -------------------------------------------------------------
    // x4Browse.editRow
    this.editRow = function(idx) {
        x4ActivateContext(this.table,'detail');
    }
}
/* ================================================================== *\
   ==================================================================
   LIBRARY: DETAIL
   
   A detail is really kind of generic, except that it establishes
   a new context and is responsible for a table.
   ==================================================================
\* ================================================================== */
function x4Detail(mdObj,contextObject,htmlPar) {
    var div = document.createElement('DIV');
    div.x_contextParent  = contextObject;
    div.x_context        = new x4DetailContext(mdObj);
    div.x_context.htmlPar= htmlPar;
    x4RegisterContext(mdObj.table,'detail',div.x_context);
    htmlPar.appendChild(div);

    var divHelp = document.createElement('DIV');
    divHelp.className = 'x4BrowseHelp';
    divHelp.x_context = div.x_context;
    divHelp.innerHTML = div.x_context.helpText;
    div.appendChild(divHelp);
    
    // TEMPORARY, put in the new and save links
    var a = document.createElement('A');
    a.href = 'javascript:void(0);';
    a.accesskey = 'N'
    a.innerHTML = '<u>N</u>ew Entry';
    a.x_context = div.x_context;
    a.onclick = function(e) { this.x_context.newEntry(); }
    div.appendChild(a);

    var a = document.createElement('SPAN');
    a.innerHTML = '&nbsp;&nbsp;&nbsp;';
    div.appendChild(a);
    
    var a = document.createElement('A');
    a.href = 'javascript:void(0);';
    a.accesskey = 'S'
    a.innerHTML = '<u>S</u>ave Entry';
    a.x_context = div.x_context;
    a.onclick = function(e) { this.x_context.saveNewEntry(); }
    div.appendChild(a);
    
    var a = document.createElement('DIV');
    a.innerHTML = '<br/>';
    div.appendChild(a);
        
    
    x4ObjectRenderRecurse(mdObj,div.x_context,div);
}

// This basically says that x4DetailContext is a subclass
// of x4Context.  It's like they've got a different word for everything
x4DetailContext.prototype = new x4Context();

// Now define and code the constructor
x4DetailContext.prototype.constructor = x4DetailContext;
// Quick search: fx4dc
function x4DetailContext(mdObj) {
    this.table    = mdObj.table;
    this.idPrefix = 'x4c_';
    this.lastSkey = 0;

    this.helpText = "ENTER or TAB to move to next field.";
    this.helpText+='<br/><br/>';
    this.helpText+='Shift-ENTER or Shift-TAB moves back.';
    this.helpText+='<br/><br/>';
    this.helpText+='Any change made to a field saves automatically';
    this.helpText+='when you move off the field.';
    this.helpText+='<br/><br/>';
    this.helpText+='ESC erases changes while on a field.';
    this.helpText+='<br/><br/>';
    this.helpText+='PageDown goes to next row<br/>';
    this.helpText+='Ctrl-PageDown goes to last row<br/>';
    this.helpText+='PageUp goes to previous row<br/>';
    this.helpText+='Ctrl-PageUp goes to first row<br/>';

    // -----------------------------------------------------------
    // What to do when the tab becomes visible
    // -----------------------------------------------------------
    this.x4OnActivate = function() {
        // Get parent context, find skey of selected row, or
        // none if there is none
        var data = x4FindDataObject(this.table);
        if(data.rowSelected == -1) {
            this.setMode('blank');
        }
        else {
            this.refreshDisplay(data.rows[data.rowSelected]);
        }
    
        // Always do this
        this.fixFocus();
    }
        
    // -----------------------------------------------------------
    // Assign standard input events w/o modification
    // -----------------------------------------------------------
    this.event_onfocus   = x4InputOnFocus;
    this.event_onblur    = function(input,e) {
        input.setMode(input.x_mode);  // clears error state
        x4InputSaveValue(input,e,this,'saveValueReturnHandler');
        x4InputOnBlur(input,e);
    }
    // Alas, we *must* have an onchange event because the calendar
    // control writes to our input, but it never gets focus.
    this.event_onchange  = function(input,e) {
        x4InputSaveValue(input,e,this,'saveValueReturnHandler');
    }
    this.event_onkeydown = function(input,e) {
        // Navigation functions prevent further processing.  Putting
        // these into keyup causes TAB to function twice.  
        if(!x4InputsNavigationTab(input,e)) return;
        if(!x4InputsNavigationEnter(input,e)) return;
        if(!x4InputsNavigationArrows(input,e)) return; 

        x4InputsKeyDown(input,e);
        this.recordNavigation(input,e);
    }

    // This did not work when being called from keydown, go figure    
    this.event_onkeyup = function(input,e) {
        x4InputsFieldFormat(input,e);

        // If the input needs a dynamic list, do that here
        if(input.x_table_id_fko != '') {
            ajax_showOptions(input,'gp_dropdown='+input.x_table_id_fko,e);
        }
    }

    // -----------------------------------------------------------
    // The refresh display function loads the values from the
    // given row into the inputs, clearing all inputs who have
    // not been given a value
    // -----------------------------------------------------------
    this.refreshDisplay = function(row) {
        for(var idx in this.tabLoop) {
            input = this.tabLoop[idx];
            input.setValue(getProperty(row,input.x_column,''));
            input.x_saved = input.value;
            input.x_save  = input.value;
            input.x_skey = row['skey'];
            input.setMode('upd');
        }
    }

    // -----------------------------------------------------------
    // This is the return handler for saving changes, not
    // for saving a new row    
    // -----------------------------------------------------------
    // x4detailContext.saveValueReturnhandler
    this.saveValueReturnHandler = function() {
        var table = this.table;
        
        // Modify the markup for anything that has errors
        this.processErrors();
        
        // Grab the returned row
        var row = new Object();
        if(typeof(x4httpData.data)=='undefined') return;
        if(typeof(x4httpData.data[table])=='undefined') return;
        var row=x4httpData.data[table][0];
        
        // Replace the currently saved row and refresh the display
        var data = x4FindDataObject(this.table);
        // Tell the data object's context to replace the row
        data.x_context.replaceRow(row);
        this.refreshDisplay(row);
    }

    // -----------------------------------------------------------
    // Command to set different modes
    // -----------------------------------------------------------
    // x4DetailContext.setMode
    this.setMode = function(mode) {
        for(var x in this.tabLoop) {
            var input = this.tabLoop[x];
            if(mode == 'upd') {
                input.x_save  = input.value;
                input.x_saved = input.value;
                input.setMode('upd');
            }
            else {
                input.setMode(mode);
            }
        }        
    }
    
    // -----------------------------------------------------------
    // Commands to move to different records in search results
    // -----------------------------------------------------------
    this.recordNavigation = function(input,e) {
        var Label = keyLabel(e);
        var shift = false;
        
        // Look for record navigation keys, return if none of
        // them were pressed.
        if(Label == 'PageDown')     shift =  1;
        if(Label == 'PageUp')       shift = -1;
        if(Label == 'CtrlPageDown') shift =  2;
        if(Label == 'CtrlPageUp')   shift = -2;
        if(shift==false) return;
            
        // if any of they keys were pressed, find the context 
        // object for the browse/search, so we can ask it stuff
        //
        var newRow = -1;
        var data   = x4FindDataObject(this.table);
        if(shift > 0) {
            // Work out what to do if they want to go down
            if(data.rowSelected < (data.rowCount - 1)) {
                if(shift == 1) newRow = data.rowSelected + 1;
                else           newRow = data.rowCount - 1;
            }
        }
        else {
            // Work out what to do if they want to go up
            if(data.rowSelected > 0) {
                if(shift == -2) newRow = 0;
                else            newRow = data.rowSelected - 1;
            }
        }
        if(newRow==-1) return;
        
        // If we have not returned, then we have found the row and
        // are ready to go to it.  However, first we should fire
        // off the onblur of the current control, which takes care
        // of any housekeeping
        this.event_onblur(input,e);
        
        // Now finally we ask the context to select the row
        // then we need to ask ourselves to display it
        data.x_context.chooseRow(newRow);
        this.refreshDisplay(data.rows[newRow]);
        
        // not done yet!  Now call the onfocus for the input
        // to get it back going.
        this.event_onfocus(input,e);
    }

    // -----------------------------------------------------------
    // Command to create a new row
    // -----------------------------------------------------------
    this.newEntry = function() {
        this.setMode('ins');
        setFocus(this.firstFocus);
    }
    
    // -----------------------------------------------------------
    // Command to save a new row and 
    // -----------------------------------------------------------
    this.saveNewEntry = function() {
        // Assemble an insert command
        var getString 
            ='x4xAjax=ins'
            +'&x4xRetRow=1'
            +'&x4xTable='+this.table;
        for(var x in this.tabLoop) {
            var input = this.tabLoop[x];
            if(input.value!='') {
                getString
                    +='&x4c_'+input.x_column
                    +'='+encodeURIComponent(input.value);
            }
        }
        // Send out the ajax command
        x4Ajax(getString,null,this,'saveNewEntryReturnHandler');
    }
    
    this.saveNewEntryReturnHandler = function() {
        var table = this.table;
        
        var errors = this.processErrors();
        if(errors) return;

        // If we've got a row, 
        if(typeof(x4httpData.data)=='undefined') return;
        if(typeof(x4httpData.data[table]) == 'undefined') return;
        var row = x4httpData.data[table][0];
    
        rnContext = x4FindContext(this.table,'master');
        rnContext.addRowToSearchResults(row);
        this.refreshDisplay(row);
        this.setMode('upd'); 
    }
    
    // -----------------------------------------------------------
    // Process errors returned either by saving a new
    // row or saving a column in the current row
    // -----------------------------------------------------------
    // x4DetailContext.processErrors
    this.processErrors = function() {
        if(typeof(x4httpData.message)=='undefined') return false;
        if(typeof(x4httpData.message.sqlerr)=='undefined') return false;

        retval = true;
        // Split the list of errors, and run through them
        var aErrors = x4httpData.message.sqlerr[0].split(';');
        var aDetails= false;
        var widget  = false;
        for(var x = 0;x<aErrors.length;x++) {
            if(aErrors[x].length==0) continue;
            aDetails = aErrors[x].split(',');

            // If there is an input for the column, handle it                    
            input = getProperty(this.inputsByName,aDetails[0],'');
            if(input != '') {
                var msg = aDetails[2].replace(' ','&nbsp;');
                input.setError(msg);
            }
        }
        return true;
    }
}
/* ================================================================== *\
   ==================================================================
   LIBRARY: GRID
   
   A grid is something like a spreadsheet.  It is editable data
   presented in tabular form.  It might also be a child table of
   some other table present in the current collection.
   ==================================================================
\* ================================================================== */
function x4Grid(mdObj,contextObj,htmlPar) {
    var newContext = new x4GridContext(mdObj);
    x4RegisterContext(mdObj.table,'grid'  ,newContext);
    x4RegisterContext(mdObj.table,'master',newContext);
    newContext.htmlPar     = htmlPar;
    
    var retVal = document.createElement('DIV');
    retVal.className ='x4Grid';
    retVal.x_contextParent = contextObj;
    retVal.x_context = newContext;
    htmlPar.appendChild(retVal);
    
    var href = document.createElement('A');
    href.x_context = newContext;
    href.accessKey = 'N';
    href.innerHTML = '<u>N</u>ew Line';
    href.href = 'javascript:void(0);';
    href.onclick = function() {
        this.x_context.newLine();
    }
    retVal.appendChild(href);
    
    var href = document.createElement('SPAN');
    href.innerHTML = '&nbsp;&nbsp;';
    retVal.appendChild(href);

    var href = document.createElement('A');
    href.x_context = newContext;
    href.accessKey = 'S';
    href.innerHTML = '<u>S</u>ave';
    href.href = 'javascript:void(0);';
    href.onclick = function() {
        this.x_context.saveLine();
    }
    retVal.appendChild(href);
    
    var br = document.createElement('BR');
    retVal.appendChild(br);
    var br = document.createElement('BR');
    retVal.appendChild(br);
    
    var tb   = document.createElement('TABLE');
    tb.className = 'x4Grid';
    tb.cellPadding = 0;
    tb.cellSpacing = 0;
    var tr = tb.insertRow(0);
    newContext.headers = new Object();
    for (var x in newContext.columns) {
        var column = newContext.columns[x];
        var colname= column.name;
        // SKip any primary key column of the parent
        var th = document.createElement('TH');
        newContext.headers[ colname ] = th;
        th.innerHTML = column.description;
        th.x_caption = column.description;
        th.x_name    = column['name'];
        th.x_context = newContext;
        th.onmouseover = function(e) { this.className = 'highlight'; }
        th.onmouseout  = function(e) {
            if(this.x_name == this.x_context.sortCol) {
                this.className='sorted';
            }
            else {
                this.className = '';
            }
        }
        th.onclick  = function(e) { 
            this.x_context.setSort(this.x_name,null);
            this.x_context.fixFocus();
        }
        
        
        tr.appendChild(th);
    }
    // Set the sortable column
    for(var x in newContext.columns) {
        newContext.setSort(x,'DESC',true);
        break;
    }

    // Put it all out there
    newContext.htmlTable = tb;
    retVal.appendChild(tb);
    
    newContext.fetchData();
}

// This basically says that x4GridContext is a subclass
// of x4Context.  It's like they've got a different word for everything
x4GridContext.prototype             = new x4Context();
x4GridContext.prototype.constructor = x4GridContext;

// ---------------------------------------------------------------
// Context Controller.  All of the objects within a 
// context have property x_context that points to an
// instance of this function prototype.  This gives us
// a measure of encapsulation.
// ---------------------------------------------------------------
// quick find: x4gc
function x4GridContext(mdObj) {
    this.table      = mdObj.table;
    this.parentTable= mdObj.parentTable; 
    this.columns    = mdObj.columns;
    this.data       = new Object()
    this.data.rowCount    = 0;
    this.data.rowSelected =-1;
    this.data.rows        = new Array();
    this.tabIndex   = 1000;
    this.firstFocus = '';
    this.lastFocus  = '';
    this.htmlTable  = false;  // generated by x4Grid()
    this.sortDir    = '';
    this.sortCol    = '';
    this.pkVals     = new Object();  // tracks last row refreshed
    this.pkValsJoined='';

    // Make an array of the primary keys and also
    // make a list of properties.  Also drop parent table's pk
    // columns from the column list, they are not necessary.
    this.parTabPK    = mdObj.parTabPK;
    this.aKeys = this.parTabPK.split(',');
    this.oKeys = new Object();
    for(var x in this.aKeys) {
        this.oKeys[ this.aKeys[x] ] = x;
        
        if(typeof(this.columns[ this.aKeys[x] ])!='undefined') { 
            delete this.columns[ this.aKeys[x] ];
        }
    }
        
    // -----------------------------------------------------------
    // Simplest on activate: set focus on last selected    
    // -----------------------------------------------------------
    // x4Grid.x4OnActivate
    this.x4OnActivate = function() {
        // Fetch the data and set the focus
        if(this.fetchData()) {
            this.fixFocus();
        }
    }
    
    
    // -----------------------------------------------------------
    // Here are the event handlers for the inputs
    // -----------------------------------------------------------
    this.event_onfocus   = x4InputOnFocus;
    this.event_onblur    = function(input,e) {
        x4InputSaveValue(input,e,this,'saveValueReturnHandler');
        x4InputOnBlur(input,e);
    }
    // Alas, we *must* have an onchange event because the calendar
    // control writes to our input, but it never gets focus.
    this.event_onchange  = function(input,e) {
        x4InputSaveValue(input,e,this,'saveValueReturnHandler');
    }
    this.event_onkeydown = function(input,e) {
        // Navigation functions prevent further processing.  Putting
        // these into keyup causes TAB to function twice.  
        if(!x4InputsNavigationTab(input,e)) return;
        if(!x4InputsNavigationEnter(input,e)) return;
        if(!x4InputsNavigationArrows(input,e)) return; 

        x4InputsKeyDown(input,e);
        //this.recordNavigation(input,e);

        // Check for changes to sort order
        Label = keyLabel(e);
        if(Label=='ShiftUpArrow') {
            this.setSort(input.x_column,'DESC',null);
        }
        if(Label=='ShiftDownArrow') {
            this.setSort(input.x_column,'ASC',null);
        }

    }

    // This did not work when being called from keydown, go figure    
    this.event_onkeyup = function(input,e) {
        x4InputsFieldFormat(input,e);

        // If the input needs a dynamic list, do that here
        if(input.x_table_id_fko != '') {
            ajax_showOptions(input,'gp_dropdown='+input.x_table_id_fko,e);
        }
    }

    // -----------------------------------------------------------
    // The return handler when there is an error.  The major
    // idea here is to set errors more than anything else.
    // -----------------------------------------------------------
    this.saveValueReturnHandler = function() {
        if(typeof(x4httpData.message)=='undefined') return false;
        if(typeof(x4httpData.message.sqlerr)=='undefined') return false;
        
        // Split the list of errors, and run through them
        var aErrors = x4httpData.message.sqlerr[0].split(';');
        var aDetails= false;
        var widget  = false;
        for(var x = 0;x<aErrors.length;x++) {
            if(aErrors[x].length==0) continue;
            aDetails = aErrors[x].split(',');

            // If there is an input for the column, handle it                    
            input = getProperty(this.inputsByName,aDetails[0],'');
            if(input != '') {
                if(input.x_errorspan) {
                    var msg = aDetails[2].replace(' ','&nbsp;');
                    input.x_errorspan.innerHTML = msg;
                }
                input.x_error = true;
                input.setColor();
            }
        }
        return true;
    }
    
    // -----------------------------------------------------------
    // Set the sort order 
    //
    // This routine was copied complete from the BROWSE 
    // context.  Maybe later we should make the various 
    // sortable type stuff into an Interface that can be added
    // to both grid and browse.
    // -----------------------------------------------------------
    this.setSort = function(sortCol,sortDir,nofetch) {
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
            if(header) { 
                header.innerHTML = header.x_caption;
                header.className = '';
            }
        }
        
        // Set styles for new header
        this.sortCol = sortCol;
        this.sortDir = sortDir;
        var header = this.headers[this.sortCol];
        if(sortDir == 'ASC') {
            header.innerHTML = '&dArr; '+header.x_caption;
        }
        else {
            header.innerHTML = '&uArr; '+header.x_caption;
        }
        header.className = 'sorted';

        // Finally, go get the data.
        if(nofetch == null) {
            this.fetchData(true);
        }
    }
    
    // -----------------------------------------------------------
    // Purge out all existing rows.
    // -----------------------------------------------------------
    this.purgeDataAndHTML = function() {
        var htab = this.htmlTable;
        this.tabIndex = 1000;
        this.firstFocus = null;
        this.lastFocus  = null;
        while (htab.rows.length > 1 ) {
            htab.deleteRow(htab.rows.length - 1);
        }       
    }
    
    // -----------------------------------------------------------
    // For now we'll fetch all rows of a table with no 
    // filters or anything like that.
    // -----------------------------------------------------------
    this.fetchData = function() {
        // Get the context of the parent table
        var parTable  = this.parentTable;
        var data      = x4FindDataObject(parTable,'master');
        
        // <<------ RETURN EARLY
        // <<------ RETURN EARLY
        //
        // If the parent context has no row selected, we have
        // nothing to do!
        if(data.rowSelected < 0) return true;
        var row = data.rows[data.rowSelected];

        // Get the last pkwhere we used, and then ask for the
        // one we'd have now, and return if they are the same
        pkWhereLast = getProperty(this,'pkWhere','');
        pkWhere = this.getPKClause(row,'x4w_');
        if(pkWhere == pkWhereLast) return;
        this.pkWhere = pkWhere;
        
        // Build the string and send it out
        var getString
            ='x4xAjax=sel'
            +'&x4xTable='+this.table
            +'&sortCol='+this.sortCol
            +'&sortDir='+this.sortDir
            +'&columns='
            +'&limit='
            +'&'+pkWhere;
        x4Ajax(getString,null,this,'fetchDataReturnHandler');
        return false;
    }

    // -----------------------------------------------------------
    // generate new data 
    // -----------------------------------------------------------
    this.getPKClause = function(row,prefix) {
        // Get the values of the primary key for the parent's row
        // and run them into a string
        var aKeys        = this.aKeys;
        var pkReturn     = '';
        for(var x in aKeys) {
            var col = aKeys[x];
            pkReturn  += prefix+col+'='+encodeURIComponent(row[col]);
        }
        return pkReturn;
    }
    
    // -----------------------------------------------------------
    // generate new data 
    // -----------------------------------------------------------
    this.fetchDataReturnHandler = function() {
        // First clear out existing rows
        this.purgeDataAndHTML();
        
        // Make sure there is something there
        if(typeof(x4httpData.data)=='undefined') return;
        if(typeof(x4httpData.data[this.table])=='undefined') return;
        this.data.rows = x4httpData.data[this.table];
        this.data.rowCount    = this.data.rows.length;
        this.data.rowSelected = 0;
        
        // Loop through the rows, and for each row loop through
        // the columns and build an input widget.
        var tb          = this.htmlTable;
        this.tabIndex   = 1000;
        this.firstFocus = false;
        this.lastFocus  = false;
        for(var x in x4httpData.data[this.table]) {
            this.idPrefix = 'grid_'+x+'_'; // x4MakeInput will now assign ID
            var row = x4httpData.data[this.table][x];
            var tr = tb.insertRow(tb.rows.length);
            for(var y in this.columns) {
                column = this.columns[y];
                td = document.createElement('TD');
                input = x4MakeInputComplex(column,this,true,td);
                //var input = x4MakeInput(column,this);
                input.setValue(row[column['name']]);
                input.x_saved = input.value;
                input.x_save  = input.value;
                if(!this.firstFocus) {
                    if(getProperty(column,'primary_key','N')=='N') {
                        this.firstFocus=input;
                    }
                }
                input.x_skey   = row['skey'];
                input.setMode('upd');
                tr.appendChild(td);
            }
        }
        this.fixFocus();
    }

    // -----------------------------------------------------------
    // Enter a new line into the grid
    // -----------------------------------------------------------
    this.newLine = function() {
        var tb= this.htmlTable;
        this.rowCount++;
        var tr = tb.insertRow(tb.rows.length);
        this.lastFocus = false;
        this.firstFocus= false;
        for(var y in this.columns) {
            this.idPrefix = 'grid_'+this.data.rowCount+'_';
            column = this.columns[y];
            td = document.createElement('TD');
            var input = x4MakeInputComplex(column,this,true,td);
            if(!this.firstFocus) this.firstFocus=input;
            input.x_skey  = 0;
            input.setMode('ins');
            tr.appendChild(td);
        }
        this.fixFocus();
    }

    // -----------------------------------------------------------
    // Enter a new line into the grid
    // This is the easy part, assembling an ajax call and 
    // sending it...
    // -----------------------------------------------------------
    this.saveLine = function() {
        // Get the row of the parent table
        var data = x4FindDataObject(this.parentTable);
        var row  = data.rows[ data.rowSelected ];
        pkContent = this.getPKClause(row,'x4c_');
        
        // Assemble an insert command
        var getString
            ='x4xAjax=ins'
            +'&x4xRetRow=1'
            +'&x4xTable='+this.table
            +'&'+pkContent;
        var x;
        var wpfx = 'grid_'+this.data.rowCount+'_';
        var value;
        for(x in this.columns) {
            var colname = this.columns[x].name;
            
            var widname = wpfx+colname;
            var parmname= 'x4c_'+colname;
            var parmval = byId(widname).value;
            getString+='&'+parmname+'='+encodeURIComponent(parmval);
        }
    
        // Send out the ajax command
        x4Ajax(getString,null,this,'saveLineReturnHandler');
    }
    
    // -----------------------------------------------------------
    // the harder part is the return handler, which must either
    // set the mode of all boxes if no errors, or set error modes
    // and complain to the user.
    // -----------------------------------------------------------
    this.saveLineReturnHandler = function() {
        var wpfx = 'grid_'+(this.data.rowCount)+'_';
        var itsok = true;
        if(typeof(x4httpData.message)!='undefined') {
            if(typeof(x4httpData.message.sqlerr)!='undefined') {
                itsok = false;
                // Split the list of errors, and run through them
                var aErrors = x4httpData.message.sqlerr[0].split(';');
                var aDetails= false;
                var widget  = false;
                for(var x = 0;x<aErrors.length;x++) {
                    if(aErrors[x].length==0) continue;
                    aDetails = aErrors[x].split(',');
                    // If we have a widget for this column, display the error
                    var iId = wpfx + aDetails[0];
                    var input=byId(iId);
                    if(input) {
                        input.x_prefix='err';
                        input.setColor();
                    }
                }
            }
        }


        if(itsok) {
            for(var x in this.columns) {
                iname = wpfx+this.columns[x]['name'];
                x4Debug('tring to find input ' + iname);
                input = byId(iname);
                input.setMode('upd');
            }
        }

        var row = x4httpData.data[this.table][0]; 
        this.data.rows[this.data.rows.length] = row;
        this.rowCount++;
    }
    
    
    
}  
/* ================================================================== *\
   ==================================================================
   LIBRARY: GENERIC CONTEXT CLASS  
   
   This is a generic class that implements context functions, and
   is used for x4Detail, x4Browse and any others that need it.
   
   Also in this library are useful routines for inputs that might
   be adopted by various contexts
   ==================================================================
\* ================================================================== */
// This is the equivalent of a class definition
function x4Context() { 
    this.tabIndex = 1000;
}

// Routine that can be called to set focus back where it
// was after a user clicked on something.
x4Context.prototype.fixFocus = function() {
    if(getProperty(this,'lastFocus')) {
        setFocus(this.lastFocus);
    }
    else if(getProperty(this,'firstFocus')) {
        setFocus(this.firstFocus);
    }
}

// ---------------------------------------------------------------
// Input routines for coloring, saving, etc.
// ---------------------------------------------------------------
function x4InputOnFocus(input,e) {
    // before anything, if this input is readonly and
    // there is an "i_next", move the focus to the next
    // object.
    if(input.readOnly) {
        x4NavigateNext(input);
    }
    
    // OK, we did not bump off to another control
    x4InputSaveFocus(input,e);
    input.x_save    = input.value;
    input.x_selected= true;
    input.setColor();
}

// This routine is sometimes used by contexts that do not need
// any other function, so it is split out on its own
function x4InputSaveFocus(input,e) {
    input.x_context.lastFocus = input;
    x4global.currentFocus = input;
}

function x4InputOnBlur(input,e) {
    input.x_selected = false;
    input.setColor();
}

function x4InputSaveValue(input,e,rhObj,rhFunction) {
    // Don't save the same value twice
    if(trim(input.value)  == trim(input.x_saved)) return;
    if(input.x_mode != 'upd')         return;
    
    // If the ajax dynamic list is active, don't try anything
    adl = byId('ajax_listOfOptions');
    if(adl) {
        if(adl.style.display!='none') return;
    }

    // Since we have not saved the current value, mark it
    // as the value last saved to prevent repeats
    input.x_saved = input.value;
    input.x_save  = input.value; // makes color go correctly
    
    // For now this is a "no return" Ajax call, we don't do
    // anything with the results if there is an error.  However,
    // we may expect to get the results of calculations back
    // from here.
    var getString
        ='x4xAjax=upd'
        +'&x4xRetRow=1'
        +'&x4xTable='+input.x_table
        +'&x4w_skey='+input.x_skey
        +'&x4c_'+input.x_column+'='+encodeURIComponent(input.value);
     x4Ajax(getString,false,rhObj,rhFunction);    
}

// ---------------------------------------------------------------
// The two navigation routines completely replace the behaviors
// of TAB, SHIFT-TAB and the up and down arrows.  It uses the
// context.tabLoop to work out the next and previous controls,
// and skips over readOnly controls.
// ---------------------------------------------------------------
function x4InputsNavigationTab(input,e) {
    var Label = keyLabel(e);
    if(Label=='Tab') {
        x4NavigateNext(input);
        e.preventDefault();
        return false;
    }
    if(Label=='ShiftTab') {
        x4NavigatePrev(input);
        e.preventDefault();
        return false;
    }
    return true;
}
function x4InputsNavigationEnter(input,e) {
    var Label = keyLabel(e);
    if(Label=='Enter') {
        x4NavigateNext(input);
        e.preventDefault();
        return false;
    }
    if(Label=='ShiftEnter') {
        x4NavigatePrev(input);
        e.preventDefault();
        return false;
    }
    return true;
}
function x4InputsNavigationArrows(input,e) {
    // Currently we've turned this off because of unpredicable
    // interactions with SELECT boxes
    // Shift-arrow will work on SELECT and TEXTAREA
    return true;

    var Label = keyLabel(e);
    if(Label == 'ShiftDownArrow') {
        x4NavigateNext(input);
        e.preventDefault();
        return false;
    }
    else if(Label == 'ShiftUpArrow') {
        x4NavigatePrev(input);
        e.preventDefault();
        return false;
    }
    
    var Label = keyLabel(e);
    // Regular arrows only work on inputs
    if(input.tagName=='INPUT') { 
        if(Label == 'DownArrow') {
            x4NavigateNext(input);
            e.preventDefault();
            return false;
        }
        else if(Label == 'UpArrow') {
            x4NavigatePrev(input);
            e.preventDefault();
            return false;
        }
    }
}
function x4InputsKeyDown(input,e) {
    // Otherwise see about things to do in update mode
    if(input.x_mode == 'upd') {
        var kl = keyLabel(e);
        if(kl == 'Esc') {
            input.setValue(input.x_save);
        }

        if(input.value != input.x_save) 
            input.x_prefix = 'ins';
        else 
            input.x_prefix = 'upd';
        
        input.setColor();
    }
}
function x4InputsFieldFormat(input,e) {
    var type_id = input.x_type_id;
    var objval  = input.value;
    if(type_id=='ssn') {
        if(objval.length==3) {
            if(keycode!=8) {
                input.value += '-';
            }
        }
        if(objval.length==6) {
            if(keycode!=8) {
                input.value += '-';
            }
        }
    }
    if(type_id=='ph12') {
        if(objval.length==3) {
            if(keycode!=8) {
                input.value += '-';
            }
        }
        if(objval.length==7) {
            if(keycode!=8) {
                input.value += '-';
            }
        }
    }
    if(type_id=='date') {
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
}

function x4NavigateNext(input) {
    var inputOrig = input;
    while(input.i_next.readOnly == true) {
        input = input.i_next;
        // Prevent an endless loop
        if(input == inputOrig) {
            return;
        }
    }
    setFocus(input.i_next);
}

function x4NavigatePrev(input) {
    var inputOrig = input;
    while(input.i_prev.readOnly == true) {
        input = input.i_prev;    
        // Prevent an endless loop
        if(input == inputOrig) {
            return;
        }
    }
    setFocus(input.i_prev);
}
