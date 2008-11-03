/* ================================================================== *\
   (C) Copyright 2008 by Secure Data Software, Inc.
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
 
   Debugging using Firebug.
   
   This file is full of commands that make use of firebugs 
   very nice console facilities.  The up-side is that we get very
   thorough event tracing when needed.  The down-side is that they
   do not work at all on Internet Explorer and severely slow you 
   down while running.
   
   Therefore, it is desirable to be able to turn them off an on
   from time to time.  The best thing to do is comment them all
   out completely, but that can be tedious.  The following two
   Beanshell files show the jEdit commands for turning logging
   on and off by commenting and uncommenting the relevant lines.
   
   # Beanshell: Turn logging off by commenting out lines
    SearchAndReplace.setSearchString("//console.");
    SearchAndReplace.setReplaceString("//console.");
    SearchAndReplace.setBeanShellReplace(false);
    SearchAndReplace.setIgnoreCase(true);
    SearchAndReplace.setRegexp(false);
    SearchAndReplace.setSearchFileSet(new CurrentBufferSet());
    SearchAndReplace.replaceAll(view);

   # Beanshell: Turn logging on by uncommenting the lines.
    SearchAndReplace.setSearchString("//console.");
    SearchAndReplace.setReplaceString("//console.");
    SearchAndReplace.setBeanShellReplace(false);
    SearchAndReplace.setIgnoreCase(true);
    SearchAndReplace.setRegexp(false);
    SearchAndReplace.setSearchFileSet(new CurrentBufferSet());
    SearchAndReplace.replaceAll(view);
\* ================================================================== */

/****O* Javascript-API/jsHtml
*
* NAME
*   Javascript-API.jsHtml
*
* FUNCTION
*   The javascript function jsHtml is a constructor 
*   function for a new HTML node.  It is considerably
*   faster and easier than document.createElement() and
*   node.appendChild.
*
*   This function works almost exactly the same way as the
*   PHP function html(), except that it works in the
*   browser.
*
*   The resulting object is considerably simpler than the
*   HTML nodes you can create in PHP, and is designed
*   only for basic tasks.  There are no shortcuts for 
*   creating complex entities, those must be coded by
*   hand.
*
*   You can pass the innerHTML in as the second parameter,
*   or you can set it directly by assigning the innerHtml
*   property of the object.
*
* INPUTS
*   * string - a valid (x)html tag name like 'div' or 'span'
*   * string - (optional) the value of innerHtml
*
* EXAMPLE
*   Use the Javascript "new" operator with this function.
*        <script>
*        var div = new jsHtml('div','Hello, I am a div!');
*        div.hp.style = 'width: 300px';
*        var html = div.bufferedRender();
*        $( -- some jquery selector -- ).append(html);
*        </script>
*
******
*/
function jsHtml(tag,innerHtml) {
    this.tag = tag;
    this.children = [ ];
    this.hp = { };

    /****O* jsHtml/innerHtml
    *
    * NAME
    *   jsHtml.innerHtml
    *
    * FUNCTION
    *   The javascript property innerHtml holds the innerHTML
    *   of an HTML node created by jsHtml().  You can pass in
    *   the innerHtml as the second parameter to jsHtml, or
    *   you can set this property directly.
    *
    * EXAMPLE
    *   Use the Javascript "new" operator with this function.
    *        <script>
    *        var div = new jsHtml('div');
    *        div.innerHtml = 'I set this on the 2nd line!';
    *        var html = div.bufferedRender();
    *        $( -- some jquery selector -- ).append(html);
    *        </script>
    *
    ******
    */
    this.innerHtml = innerHtml ? innerHtml : '';
    
    /****O* jsHtml/addChild
    *
    * NAME
    *   jsHtml.addChild
    *
    * FUNCTION
    *   The javascript method addChild adds one HTML node as
    *   a child to another.  Both nodes must have been 
    *   created by using the jsHtml() constructor function.
    *
    * EXAMPLE
    *   Use the Javascript "new" operator with this function.
    *        <script>
    *        var div = new jsHtml('div');
    *        var span = new jsHtml('span','A span in a div!');
    *        div.addChild(span);
    *        var html = div.bufferedRender();
    *        $( -- some jquery selector -- ).append(html);
    *        </script>
    *
    ******
    */
    this.addChild = function(child) {
        this.children.push(child);
    }
    /******/

    /****O* jsHtml/h
    *
    * NAME
    *   jsHtml.h
    *
    * FUNCTION
    *   The javascript method h creates a new HTML node and
    *   makes it a child of the current node.  This is a 
    *   shortcut for having to call jsHtml and then
    *   addChild.
    *
    * EXAMPLE
    *   Use the Javascript "new" operator with this function.
    *        <script>
    *        var div = new jsHtml('div');
    *        var span = div.h('span','Hello!');
    *        var html = div.bufferedRender();
    *        $( -- some jquery selector -- ).append(html);
    *        </script>
    *
    ******
    */
    this.h = function(tag,innerHtml) {
        var newNode = new jsHtml(tag,innerHtml);
        this.addChild(newNode);
        return newNode;
    }
    /******/

    /****O* jsHtml/bufferedRender
    *
    * NAME
    *   jsHtml.bufferedRender
    *
    * FUNCTION
    *   The javascript method bufferedRender returns a string
    *   of HTML for a node created with jsHtml.  It sets all
    *   properties, and recursively runs through all children.
    *   The innerHtml, if it is present, goes out last.
    *
    * SOURCE
    */
    this.bufferedRender = function() {
        var html = '<' + this.tag;
        for(var attName in this.hp) {
            html+=' '+attName+'="'+this.hp[attName]+'"';
        }
        html+=">";
        for(var idx in this.children) {
            html+=this.children[idx].bufferedRender();
        }
        html+=this.innerHtml;
        html+='</'+this.tag+'>';
        return html;
    }
    /******/

}

    
/****O* Javascript-API/x6events
*
* NAME
*   x6events
*
* FUNCTION
*   The javascript object x6events implements the classic
*   event listener and dispatcher pattern.
*
*   Objects can subscribe to events by name.  Other 
*   objects can notify the events object when an event
*   fires, and it will in turn notify all of the subscribers.
*
* PORTABILITY
*   The u.events object and its methods expect other u
*   methods to be available, but do not have any other
*   dependencies.
*
******
*/
var x6events = {
    /****iv* events/subscribers
    *
    * NAME
    *   u.events.subscribers
    *
    * FUNCTION
    *   The javascript object x6events.subscribers is an object
    *   serving as an Associative Array.  Each entry in the array
    *   has a key that is the name of the event, and a value that
    *   is an array of object ids for the subscribers.  In JSON
    *   format the array might look like this:
    *
    *       u.events.subscribers = {  // example code only
    *           keyPress_Enter: { 
    *              gridEdit_regrules: 'gridEdit_regrules'
    *           }
    *           addRow_customers: {
    *              gridBrowse_customers: 'gridBrowse_customers'
    *           }
    *       }
    *
    *   This object is documented for completeness only, it is
    *   not intended for direct manipulation.
    * 
    ******
    */
    subscribers: { },

    /****m* x6events/subscribeToEvent
    *
    * NAME
    *   x6events.subscribeToEvent
    *
    * FUNCTION
    *   The Javascript method x6events.subscribeToEvent allows an
    *   object to subscribe to a named event.  That object will
    *   then be notified whenever the event fires.  See the
    *   method x6events.notify for more information on how
    *   the notification is handled.
    *
    * INPUTS
    *   * eventName - Any string.  There is no validtion of the 
    *   eventName, so misspellings will result in your object 
    *   not being notified.
    *   * id - the Id of the object
    *   
    * NOTES
    *
    * RESULT
    *   No return value
    *
    * SEE ALSO
    *   x6events.fireEvent
    *
    * SOURCE
    */
    subscribeToEvent: function(eventName,id) {
        //c*onsole.group("subscribeToEvent "+eventName);
        //c*onsole.log("event name: ",eventName)
        //c*onsole.log("id subscbr: ",id);
        if(id=='undefined') {
            u.error('x6events.subscribeToEvent.  Second parameter '
                +' undefined.  First parameter: '+eventName
            );
            return;
        }
        if(id==null) {
            u.error('x6events.subscribeToEvent.  Second parameter '
                +' null.  First parameter: '+eventName
            );
            return;
        }
        
        // First determine if we have any listeners for this
        // event at all.  If not, make up the empty object
        if( u.p(this.subscribers,eventName,null)==null) {
            this.subscribers[eventName] = [ ];
        }
        this.subscribers[eventName].push(id);
        //c*onsole.groupEnd();
    },
    /******/
        
    /****m* events/getSubscribers
    *
    * NAME
    *   x6events.getSubscribers
    *
    * FUNCTION
    *   The Javascript method x6events.getSubscribers returns
    *   an array of subscribers to a particular event.
    *
    * NOTES
    *   Use this method to discover which objects are
    *   subscribed to a particular event.
    *
    * RETURNS
    *   An array of zero or more object id's. 
    *
    * SOURCE
    */
    getSubscribers: function(eventName) {
        return u.p(this.subscribers,eventName,[]);
    },
    /******/

    /****m* x6events/fireEvent
    *
    * NAME
    *   x6events.fireEvent
    *
    * FUNCTION
    *   The Javascript method x6events.fireEvent will notify all
    *   objects that have subscribed to an event.  Each subscribing
    *   object must have a either:
    *   * a method named receiveEvents(eventName,args)
    *   * a method named receiveEvent_{EventName}(args)
    *
    *   If you want your application objects to notify other objects
    *   of its own events, call this function.
    *
    * INPUTS
    *   * eventName, the name of the event
    *   * mixed, a single argument.  If multiple arguments are required,
    *   pass an object that contains property:value assignments or 
    *   an array.  The only requirement for the argument is that the
    *   listeners know what to expect.
    *
    * RESULTS
    *   no return value.
    *
    ******
    */
    retvals: { },
    fireEvent: function(eventName,arguments) {
        //console.group("fireEvent "+eventName);
        //console.log('arguments: ',arguments);
        // Find out if anybody is listening for this event
        var subscribers = this.getSubscribers(eventName);
        
        // loop through subscribers.  Note at the bottom of the list
        // that if an event handler returns false we must stop.
        this.retvals[eventName] = true;
        for(var x in subscribers) {
            var id = subscribers[x];
            //console.log("subscriber: ",id);
            var subscriber = u.byId(id);
            if(subscriber==null) {
                u.error("There is no object with that ID, cannot dispatch");
                continue;
            }
            
            // First possibility is a generic nofity handler
            var retval = false;
            var method = 'receiveEvent_'+eventName;
            if(typeof(subscriber[method])=='function') {
                retval = subscriber[method](arguments);
            }
            else {
                u.error("Subscriber has no method: ",method); 
            }
            if(retval==false) {
                this.retvals[eventName] = false;
                break;
            }
        }
        //console.log("fireEvent RETURNING: ",this.retvals[eventName]);
        //console.groupEnd();
        return this.retvals[eventName];
    }
}
/* **************************************************************** *\

   X6 Data Dictionary
   
\* **************************************************************** */
var x6dd = {
    tables: { }
}

/* **************************************************************** *\

   X6 Object
   
\* **************************************************************** */
var x6 = {
    // Find all plugins in the x6plugins object.  Find all
    // DOM elements with property x6plugIn=xxx.  
    // Invoke the constructor for each one.
    init: function() {
        u.debugFlag = true;
        
        // Job 1: Activate all of the plugins
        for(var plugInId in x6plugins) {
            $('[x6plugIn='+plugInId+']').each(function() {
                    if(u.p(this,'id','')=='') {
                        this.id = u.uniqueId();
                    }
                    //s="Initializing object "+this.id+" as plugIn "+plugInId;
                    //c*onsole.log(s);
                    this.zTable = u.p(this,'x6table');
                    x6plugins[plugInId](this,this.id,u.p(this,'x6table'));
            });
        }
        
        // Job 2, activate a global keyboard handler
        $(document).keypress(function(e) {
                //console.group("Document Keypress");
                //console.log(e); 
                var retval= x6.keyDispatcher(e);
                //console.groupEnd(); 
                return retval;
        });
        
        // We used to fade in here, but that was no good, because
        // the fade in would occur before any page-specific code
        // had run, and the user would see things jumping around.
        // Now the index_hidden x6 dispatcher sends this command
        // very last, so it is the last thing that happens.
        //$('.fadein').fadeIn('slow',function() { x6.initFocus(); });
    },
    
    initFocus: function() {
        $('[x6firstFocus=Y]:last').focus();
    },
    
    // Keyboard handler
    keyDispatcher: function(e) {
        var x = e.keyCode;
        
        // First make a big list of codes and look for the event
        var x4Keys = { };
        x4Keys['8']  = 'BackSpace';
        x4Keys['9']  = 'Tab';
        x4Keys['13'] = 'Enter';
        //x4Keys['16'] = '';   // actually Shift, but prefix will take care of it
        //x4Keys['17'] = '';   // actually Ctrl,  but prefix will take care of it
        //x4Keys['18'] = '';   // actually Alt,   but prefix will take care of it
        x4Keys['16'] = 'Shift';
        x4Keys['17'] = 'Ctrl';
        x4Keys['18'] = 'Alt';
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
    
        // If they did not hit a control key of some sort, look
        // next for letters
        var retval = '';
        if(typeof(x4Keys[x])!='undefined') {
            retval = x4Keys[x];
        }
        else {
            var letters = 
                [ 'A', 'B', 'C', 'D', 'E', 'F', 'G',
                  'H', 'I', 'J', 'K', 'L', 'M', 'N',
                  'O', 'P', 'Q', 'R', 'S', 'T', 'U',
                  'V', 'W', 'X', 'Y', 'Z' ];
            var numbers = [ '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ];
            if(e.charCode >= 65 && e.charCode <= 90) {
                retval = letters[e.charCode - 65];
            }
            else if(e.charCode >= 97 && e.charCode <= 121) {
                retval = letters[e.charCode - 97];
            }
            else if(e.charCode >= 48 && e.charCode <= 57) {
                retval = numbers[e.charCode - 48];
            }
        }
    
        // otherwise put on any prefixes and return
        if(e.ctrlKey)  retval = 'Ctrl'  + retval;
        // KFD 8/4/08, this never worked, removed.
        if(e.altKey)   retval = 'Alt'   + retval;
        if(e.shiftKey) retval = 'Shift' + retval;
        
        // Make list of keys to stop no matter what
        var stopThem = [ 'CtrlF5', 'F10' ];
        
        // Now we have a complete key label, fire the event
        //console.log("In x6.keyDispatch, code and event follow");
        //console.log(retval);
        //console.log(e);
        if(stopThem.indexOf(retval)>0) {
            //console.log("x6.keyDispatch: key is in force stop list, stopping propagation.");
            e.stopPropagation();
            return false;
        }
        else if (!x6events.fireEvent('key_'+retval,null)) {
            //console.log("x6.keyDispatch: handler returned false, stopping propagation.");
            e.stopPropagation();
            return false;
        }
        else {
            //console.log("x6.keyDispatch: handler returned true, continuing propagation.");
            return true;
        }
    }    
}

/* **************************************************************** *\

   Universal x6 input keyup handler
   
\* **************************************************************** */
var x6inputs = {
    // This routine takes an input that has no x6 event
    // handlers and adds all of the event handlers to it
    initInput: function(input,tabIndex,mode,tabGroup) {
        //console.group("Initializing Input");
        //console.log("tabindex, mode, tabgroup: ",tabIndex,mode,tabGroup);
        //console.log(input);
        
        // This is standard events and attributes
        input.setAttribute('xTabGroup',tabGroup);
        input.setAttribute('tabIndex' ,tabIndex);
        input.zOriginalValue = input.value.trim();
        $(input)
            .keyup(function(e)   { x6inputs.keyUp(e,this)   })
            .focus(function(e)   { x6inputs.focus(e,this)   })
            .blur(function(e)    { x6inputs.blur(e,this)    })
            .keydown(function(e) { x6inputs.keyDown(e,this) });
        if(mode=='new') {
            input.zNew = 1;
            x6inputs.setClass(input);
        }
        
        // KFD 11/1/08, EXPERIMENTAL use of jquery.maskedinput
        //if(u.p(input,'xinputmask','')!='') {
        //    $(input).mask(u.p(input,'xinputmask'));
        //}

        // This is important, it says that this is an 
        // active input.  This distinguishes it from possible
        // hidden inputs that were used as a clone source 
        // that have many of the same properties.
        input.zActive = 1;

        //console.groupEnd();
    },
    
    // Key up is used to look for changed values because
    // you do not see an input's new value until the keyup 
    // event.  You do not see it in keypress or keydown.
    keyUp: function(e,inp) {
        //console.group("Input keyUp");
        //console.log(e);
        //console.log(inp);
        x6inputs.setClass(inp);
        //console.groupEnd("Input keyUp");
    },
    
    // Keydown is used only for tab or shift tab, to enforce
    // the concept of a "tab loop".  This function only does
    // anything if there are no enabled controls after the
    // current control
    //
    keyDown: function(e,inp) {
        if(e.keyCode!=9) return true;
        //console.group("input keyDown handler");
        //console.log(e);
        //console.log(inp);
        
        var tg = u.p(inp,'xTabGroup','tgdefault');
        //console.log("Tab Group",tg);
        
        if(e.shiftKey) { 
            // hitting shift-tab on the first control means
            // jump back to the last control
            var first = $('[xTabGroup='+tg+']:not([disabled]):first')[0];
            //console.log("The first is:");
            //console.log(first);
            //console.log("The input is:");
            //console.log(inp);
            if(first==inp) {
                //console.log("This is first, jumping to last");
                $('[xTabGroup='+tg+']:not([disabled]):last').focus();
                e.preventDefault();
            }
        }
        else {
            // hitting tab on the last control is the only
            // thing I care about.  If I'm not on the last
            // control, let the browser do it, much faster.
            var last = $('[xTabGroup='+tg+']:not([disabled]):last')[0];
            //console.log("The last is:");
            //console.log(last);
            //console.log("The input is:");
            //console.log(inp);
            if(last==inp) {
                //console.log("This is last, jumping to first");
                $('[xTabGroup='+tg+']:not([disabled]):first').focus();
                e.preventDefault();
            }
        }
        //console.groupEnd();
        return;
    },
    
    focus: function(inp) {
        inp.zSelected = 1;
        x6inputs.setClass(inp);
    },
    xFocus: function(anyObject) {
        $(this).addClass('selected');
    },
    
    blur: function(inp) {
        inp.zSelected = 0;
        x6inputs.setClass(inp);
    },
    xBlur: function(anyObject) {
        $(anyObject).removeClass('selected');
    },
    
    setClass: function(inp) {
        ux = u.uniqueId();
        //console.group("setClass for an input  "+ux);
        //console.log(inp);
        if(u.p(inp,'zOriginalValue',null)==null) inp.zOriginalValue = '';
        if(inp.value==inp.zOriginalValue) {
            inp.zChanged = 0;
        }
        else {
            inp.zChanged = 1;
        }
        
        // First grab the flags that determine
        // what we will do
        var zSelected = u.p(inp,'zSelected',0);
        var zChanged  = u.p(inp,'zChanged', 0);
        var zError    = u.p(inp,'zError'  , 0);
        var zRO       = u.p(inp,'zRO'     , 0);
        var zNew      = u.p(inp,'zNew'    , 0);
        
        // now pick them in order of preference,
        // we only pick one stem.
        if     (zRO)      css = 'readOnly';
        else if(zError)   css = 'error';
        else if(zNew)     css = 'changed';
        else if(zChanged) css = 'changed';
        else              css = '';
        //console.log("initial class is "+css);
        
        // Now pick the selected version if required
        if(zSelected) css += 'Selected';
        //console.log("Final class is "+css);
        
        // Now do some stuff if it is read only
        inp.disabled = zRO;
        //console.log("Read Only Decision is",inp.disabled);
        
        // Flag to do the row
        doRow = u.p(inp,'xClassRow',0);
            
        // Now set the class name
        inp.className = css;
        if(doRow && zSelected) {
            inp.parentNode.parentNode.className = 'selected';
        }
        if(doRow && !zSelected) {
            inp.parentNode.parentNode.className = '';
        }
        //console.groupEnd();
    },
    
    clearOut: function(inp) {
        inp.zRO            = 1;
        inp.zNew           = 0;
        inp.zSelected      = 0;
        inp.value          = '';
        inp.zOriginalValue = '';
        x6inputs.setClass(inp);
    },
    
    findFocus: function(obj) {
        if(typeof(obj)=='string') {
            $(obj+" :input:first:not([disabled])").focus();
        }
        else {
            $(obj).find(":input:first:not([disabled])").focus();
        }
    },
    
    jqFocusString: function() {
        return ":input:not([disabled]):first";
    }
}



/* **************************************************************** *\

   X6 Builtin plugins
   
\* **************************************************************** */

/****O* Javascript-API/x6plugins
*
* NAME
*   x6plugins
*
* FUNCTION
*   The javascript object x6plugins is a collection of functions.
*   Each function is a 'constructor'.
*  
*
******
*/
var x6plugins = {
    buttonNew: function(self,id,table) {
        x6plugins.buttonStandard(self,'new','CtrlN');
        self.main = function() {
            x6events.fireEvent('reqNewRow_'+this.zTable);   
        }
    },
    buttonDuplicate: function(self,id,table) {
        x6plugins.buttonStandard(self,'duplicate','CtrlD');
        self.main = function() {
            x6events.fireEvent('reqNewRow_'+this.zTable,true);   
        }
    },
    buttonRemove: function(self,id,table) {
        x6plugins.buttonStandard(self,'remove','CtrlR');
        self.main = function() {
            x6events.fireEvent('reqDelRow_'+this.zTable);
        }
    },
    buttonAbandon: function(self,id,table) {
        x6plugins.buttonStandard(self,'abandon','CtrlT');
        self.main = function() {
            if(confirm("Abandon all changes?")) {
                x6events.fireEvent('reqUndoRow_'+this.zTable);
            }
        }
    },
    buttonSave: function(self,id,table) {
        x6plugins.buttonStandard(self,'save','CtrlS');
        self.main = function() {
            x6events.fireEvent('reqSaveRow_'+this.zTable);
        }
    },
    /****m* x6plugins/buttonStandard
    *
    * NAME
    *   x6plugins.buttonStandard
    *
    * FUNCTION
    *   The Javascript method buttonStandard gives a button 
    *   object the standard behavior of responding to 
    *   a single keystroke and responding to enable/disable
    *   commands.
    * 
    *   Use this function when you create a custom button to
    *   put onto the screen.
    *
    *   A custom button requires a uniquely named 'action' and
    *   a hotkey.  Actions reserved by andromeda are:
    *   *  duplicate
    *   *  new
    *   *  remove
    *   *  save
    *
    * EXAMPLE
    *   A custom button is created in PHP code like so:
    *
    *    <?php
    *    # option 1, straight html
    *    <input type='button' x6plugIn='buttonMine' x6table='example'>
    *
    *    # option 2, or a link
    *    $div = html('div');
    *    $a = $div->h('a-void','My Action');
    *    $a->hp['x6plugIn'] = 'buttonMine';
    *    $a->hp['x6table'] = 'example';  // only if relevant
    *    ?>
    *
    *   Then you define a javascript x6plugIn that includes a 
    *   single function, main(), which is called when the button
    *   is enabled and is clicked or the hotkey is pressed.
    *
    *     <script>
    *     x6plugins.buttonMine = function(self,id,table) {
    *         // the first line activates normal behavior, 
    *         // replace the values in this line with those
    *         // appropriate to your button.
    *         x6plugins.buttonStandard(self,'save','CtrlS');
    *
    *         // Then create the main function 
    *         self.main = function() {
    *            // fire off some event
    *         }
    *     }
    *     </script>    
    *
    ******/
    buttonStandard: function(self,action,key) {
        // Assume everything starts out enabled
        self.zDisabled = false;
        self.zTable    = u.p(self,'x6table');
        self.zAction   = action;
        self.zKey      = key;
        
        // Respond to an enable event
        x6events.subscribeToEvent('enable_'+action,self.id);
        self['receiveEvent_enable_'+action] = function() {
            this.className = 'button';
            this.zDisabled = false;
        }

        // Respond to an disable event
        x6events.subscribeToEvent('disable_'+action,self.id);
        self['receiveEvent_disable_'+action] = function() {
            this.className = 'button_disabled';
            this.zDisabled = true;
        }
        
        // Create an empty main routine to be replaced
        // button by button.  Put out a useful error message
        // when they have not 
        self.main = function() {
            u.error("Button "+this.id+", handling action "+this.zAction
                +" and keypress "+this.zKey+" has no main() function."
            );
        }
        // Respond to a keypress event
        x6events.subscribeToEvent('key_'+key,self.id);
        self['receiveEvent_key_'+key] = function() {
            if(!this.zDisabled) this.main();
            // if a key event is received, we *always* stop 
            // propagation
            x6events.retvals['key_'+this.zKey] = false;
        }
        
        // finally of course set the onclick method
        $(self).click(function() { 
            if(!this.zDisabled) this.main(); 
        });
    }
}
 

/****m* x6plugins/tableController
*
* NAME
*   x6plugins.tableController
*
* FUNCTION
*   The Javascript method x6plugins.tableController is
*   a constructor function.  It accepts as a parameter the
*   ID of a DOM element.  It adds functions to that DOM 
*   element so that it will fully implement all browser-side
*   features of our tableController object.
*
*   A tableController subscribes to all events in which a
*   user requests to do something like add a row or delete
*   a row.  The tableController executes whatever server-side
*   requests are required, and then fires various events to
*   notify other UI elements that they should display the
*   results.
* 
*   Normally you do not invoke this method directly.  All 
*   x6 plugins are detected and implmented automatically on
*   page load.

*   To turn any DOM element into a table controller, just set
*   the properties x6plugIn and x6table, as in either
*   of these:
*
*      <?php
*      # here is one way to do it:
*      echo "<div x6plugIn='tableController' x6table='users'>";
*
*      # another way to do it:
*      $div = html('div');
*      $div->hp['x6plugIn'] = 'tableController';
*      $div->hp['x6table'] = 'users';
*      $div->render();
*      ?>
*
*   You should not have more than one table controller per 
*   table on a page -- Andromeda will not trap for this!
*
* INPUTS
*   id - the ID of the object to be 'activated'.
*
* RESULTS
*   no return value.
*
******
*/
x6plugins.tableController = function(self,id,table) {
    // Initialize new properties
    u.bb.vgfSet('skey_'+table,-1);
    self.zSortCol = false;
    self.zSortAsc = false;
    self.zCache   = u.p(self,'xCache')=='Y' ? true : false;
    
    /*
    *   Table controller accepts the request to
    *   save current changes.  First checks if this
    *   makes sense.
    *   
    */
    x6events.subscribeToEvent('reqSaveRow_'+table,id);
    self['receiveEvent_reqSaveRow_'+table] = function(dupe) {
        //console.group("tableController reqSaveRow "+this.zTable);
        
        var result = this.saveOk();
        u.bb.vgfSet('lastSave_'+this.zTable,result);
        //console.log('tableController reqSaveRow finished');
        //console.groupEnd();
    }
    
    
    /*
    *   Table controller accepts the request to
    *   begin editing a new row.  It must first 
    *   work out if any open rows being edited must
    *   be saved.  If everything works out it 
    *   broadcasts a UI notification that UI elements
    *   should display their inputs in NEW mode.
    *   
    */
    x6events.subscribeToEvent('reqNewRow_'    +table,id);
    self['receiveEvent_reqNewRow_'+table] = function(dupe) {
        //console.group("tableController reqNewRow "+this.zTable);
        
        var result = this.saveOk();
        u.bb.vgfSet('lastSave_'+this.zTable,result);
        if(result!='fail') {
            x6events.fireEvent('uiNewRow_'+table);
        }
        //console.groupEnd();
    }

    /*
    *   Table controller accepts a request to edit a 
    *   row.  It is smart enough that if we are already
    *   editing that row it does nothing.  Otherwise
    *   it tries to save any existing row before 
    *   deciding whether to move on.
    *   
    */
    x6events.subscribeToEvent('reqEditRow_'+table,id);
    self['receiveEvent_reqEditRow_'+table] = function(skey) {
        //console.group("tableController reqEditRow "+this.zTable);
        var skeynow = u.bb.vgfGet('skey_'+this.zTable);
        if(skeynow == skey) {
            //console.log("Request to edit same row, no action");
        } 
        else {
            var result = this.saveOk();
            u.bb.vgfSet('lastSave_'+this.zTable,result);
            if(result!='fail') {
                x6events.fireEvent('uiEditRow_'+table,skey);
            }
        }
        //console.log("tableController reqEditRow finished");
        //console.groupEnd();
        return true;
    }
    
    /*
    *   The saveOk figures out if it needs to save and
    *   tries to do so.  If no active fields have changed,
    *   it just returns 'noaction'.  If it needs to save,
    *   it attempts to do so and returns 'success' or
    *   'fail'.
    */
    self.saveOk = function() {
        //console.group("tableController saveOK");
        var inpAll = { };
        var inpChg = { };
        var cntChg = 0;
        var jq = ':input[xtableid='+this.zTable+'][zActive]';
        //console.log("Query string",jq);
        $(this).find(jq).each(
            function() {
                var col = u.p(this,'xcolumnid');
                inpAll[col] = this.value;
                var oval = u.p(this,'zOriginalValue','').trim();
                if(this.value.trim()!= oval) {
                    inpChg[col] = this.value.trim();
                    cntChg++;
                }
            }
        );
        //console.log("All inputs: ",inpAll);
        //console.log("Changed inputs: ",inpChg);
        //console.log("Count of changes: ",cntChg);
        
        // Only attempt a save if something changed
        if(cntChg == 0) {
            //console.log("no changes, not trying to save");
            var retval = 'noaction';
        }
        else {
            //console.log("attempting database save");
            //console.log("Sending x4v_skey ",this.zSkey);
            ua.json.init('x6page',this.zTable);
            ua.json.addParm('x6action','save');
            ua.json.addParm('x4v_skey',u.bb.vgfGet('skey_'+this.zTable));
            ua.json.inputs(jq);
            if(ua.json.execute()) {
                var retval = 'success';
                ua.json.process();
            }
            else {
                var retval = 'fail';
                var errors = [ ];
                for(var idx in ua.json.jdata.error) {
                    if(ua.json.jdata.error[idx].slice(0,8)!='(ADMIN):') {
                        errors.push(ua.json.jdata.error[idx]);
                    }
                }
                //console.log("save failed, here are errors");
                //console.log(errors);
                x6events.fireEvent('uiShowErrors_'+this.zTable,errors);
            }
        }
        
        // If save went ok, notify any ui elements, then 
        // fire off a cache save also if required.
        if(retval=='success') {
            //console.log(retval);
            x6events.fireEvent('uiRowSaved_'+table,$a.data.row);
            if(this.zCache) {
                this.zRows[$a.data.row.skey] = $a.data.row;
            }
        }            
        
        //console.log("tableController saveOK RETURNING: ",retval);
        //console.groupEnd();
        return retval;
    };


    /*
    *   The table controller accepts requests to undo
    *   changes to a row.  It actually rolls back all
    *   inputs and sets their classes, and then
    *   fires of a uiUndoRow event so various other
    *   elements can do their own thing.
    */
    x6events.subscribeToEvent('reqUndoRow_'+table,id);
    self['receiveEvent_reqUndoRow_'+table] = function() {
        //console.group("tableController reqUndoRow");
        var skey = u.bb.vgfGet('skey_'+table);
        if(skey>=0) {
            //console.log("Skey is >= 0, continuing ",skey);
            $(this).find(":input:not([disabled])[zActive]").each( 
                function() {
                    this.value = this.zOriginalValue;
                    this.zError = 0;
                    x6inputs.setClass(this);
                }
            );
            x6events.fireEvent('uiUndoRow_'+this.zTable);
        }
        //console.log("tableController reqUndoRow Finished");
        //console.groupEnd();
        return true;
    }
    

    /*
    *   The table controller accepts delete request
    *   and asks the database to do the delete.  If
    *   this is successful, it tells any UI subscribers
    *   to update their displays accordingly.
    */
    x6events.subscribeToEvent('reqDelRow_'    +table,id);
    self['receiveEvent_reqDelRow_'+table] = function() {
        //console.group("tableController reqDelRow ",this.zTable);
        var skey = u.bb.vgfGet('skey_'+this.zTable);
        if(this.zSkey<1) {
            //console.log("nothing being edited, quietly ignoring");
        }
        else {
            if(confirm("Delete current row?")) {
                //console.log("sending delete to server");
                ua.json.init('x6page',this.zTable);
                ua.json.addParm('x6action','delete');
                ua.json.addParm('skey',skey);
                ua.json.addParm('json',1);
                if(ua.json.execute()) {
                    x6events.fireEvent('uiDelRow_'+table,skey);
                }
            }
        }
        //console.log("tableController reqDelRow finished");
        //console.groupEnd();
        return true;
    }
    
    // Sort requests are sorted out here.        
    x6events.subscribeToEvent('reqSort_'+table,id);
    self['receiveEvent_reqSort_'+table] = function(args) {
        // Work out sort order
        table = this.zTable
        xColumn = args.xColumn;
        xChGroup= args.xChGroup;
        if(xColumn == this.zSortCol) {
            this.zSortAsc = ! this.zSortAsc;
        }
        else {
            this.zSortCol = xColumn;
            this.zSortAsc = true;
        }
        
        // Flip all icons to both
        $('[xChGroup='+xChGroup+']').html('&uarr;&darr');
        
        // Flip just this icon to up or down
        var icon = this.zSortAsc ? '&darr;' : '&uarr;';
        $('[xChGroup='+xChGroup+'][xColumn='+xColumn+']').html(icon);
        
        // Make the request to the server
        var args2 = { sortCol: this.zSortCol, sortAsc: this.zSortAsc };
        x6events.fireEvent('doSort_'+this.zTable,args2);
    }
    

    /*
    *   Table controller will be happy to cache
    *   rows for a table if they are offered. It
    *   will also be happy to add a row to that
    *   cache if offered.  There ought also to be
    *   something here to remove a row, but that
    *   seems to be missing?
    *
    *   Note: shouldn't that be cacheAddRow ?
    *   Note: and then wouldn't we want cacheDelRow?
    */
    this.zRows = { };
    x6events.subscribeToEvent('cacheRows_'+table,id);
    self['receiveEvent_cacheRows_'+table] = function(rows) {
        this.zRows = rows;
    }
    x6events.subscribeToEvent('addRow_'+table,id);
    self['receiveEvent_addRow_'+table] = function(row) {
        this.zRows[row.skey] = row;
    }
    
    /*
    *    A request to put the current row onto
    *    the bulletin board.
    */
    x6events.subscribeToEvent('dbFetchRow_'+table,id);
    self['receiveEvent_dbFetchRow_'+table] = function(skey) {
        
        if(typeof(this.zRows[skey])=='undefined') {
            //console.log("tableController bbRow, no row found");
        }
        else {
            //console.log("tableController bbRow, publishing row "+skey);
            //console.log("putting onto bb as dbRow_"+this.zTable);
            u.bb.vgfSet('dbRow_'+this.zTable,this.zRows[skey]);
        }
    }
    
    /*
    *   Two requests, one to turn on editing-mode buttons,
    *   another to turn them off.
    */
    x6events.subscribeToEvent('buttonsOn_'+table,id);
    self['receiveEvent_buttonsOn_'+table] = function() {
        x6events.fireEvent('enable_save');
        x6events.fireEvent('enable_abandon');
        x6events.fireEvent('enable_remove');
    }
    x6events.subscribeToEvent('buttonsOff_'+table,id);
    self['receiveEvent_buttonsOff_'+table] = function() {
        x6events.fireEvent('disable_save');
        x6events.fireEvent('disable_abandon');
        x6events.fireEvent('disable_remove');
    }
}
    
    
/****m* x6plugins/detailDisplay
*
* NAME
*   x6plugins.detailDisplay
*
* FUNCTION
*   The Javascript method x6plugins.detailDisplay implements
*   all browser-side functionality for Andromeda's built-in
*   plugIn detailDisplay.
*
*   A 'detailDisplay' plugIn displays user inputs to edit
*   the values for a particular row in a table.  
*
*   This plugin subscribes to the following events:
*   *  goMode_{table}
*
* INPUTS
*   self - the DOM object to be activated.
*   id - the ID of the object to be 'activated'.
*   table - the database table that the detailPane is handling.
*
* RESULTS
*   no return value.
*
******
*/
x6plugins.detailDisplay = function(self,id,table) {
    // detail receives a request to go to a mode which
    // is unconditional, it will do what it is told
    x6events.subscribeToEvent('uiEditRow_'+table,id);
    self['receiveEvent_uiEditRow_'+table] = function(skey) {
        //console.group("detailDisplay uiEditRow",skey);
        
        // Ask somebody to publish the row
        x6events.fireEvent('dbFetchRow_'+table,skey);
        var row = u.bb.vgfGet('dbRow_'+table);
        
        // Branch out to display in edit mode
        this.displayRow('edit',row);
        //console.log("detailDisplay displayRow FINISHED");
        //console.groupEnd();
    }
        
    // Detail receives an addRow event and interprets it
    // as a goMode
    x6events.subscribeToEvent('uiNewRow_'+table,id);
    self['receiveEvent_uiNewRow_'+table] = function(row) {
        //console.group("detailDisplay uiNewRow");
        this.displayRow('new',{});
        //console.log("detailDisplay uiNewRow FINISHED");
        //console.groupEnd();
    }

    /*
    *    A uiRowSaved says there are new values.  This
    *    will be caught and interpreted as a uiEditRow
    */
    x6events.subscribeToEvent('uiRowSaved_'+table,id);
    self['receiveEvent_uiRowSaved_'+table] = function(row) {
        //console.group("detailDisplay uiRowSaved",skey);
        this.displayRow('edit',row);
        //console.log("detailDisplay uiRowSaved FINISHED");
        //console.groupEnd();
    }
        
    /*
    *    A uiDelRow clears all inputs
    */
    x6events.subscribeToEvent('uiDelRow_'+table,id);
    self['receiveEvent_uiDelRow_'+table] = function(skey) {
        //console.group("detailDisplay uiDelRow",skey);
        $(this).find(':input').each(function() {
            this.value='';
            this.zOriginalValue = '';
            this.zChanged = 0;
            this.zActive  = 0;
            this.zRo      = 1;
            x6inputs.setClass(this);
        });
        x6events.fireEvent('buttonsOff_'+this.zTable);
        //console.log("detailDisplay uiDelRow FINISHED");
        //console.groupEnd();
    }

    self.displayRow = function(mode,row) { 
        //console.group("detailDisplay displayRow ",mode);
        //console.log(row);
        if(mode!='new' && mode!='edit') {
            u.error("Object "+this.id+" has received a 'goMode' event "
                +"for unhandled mode "+mode+".  Cannot process this "
                +"request."
            );
        }

        // Set values and remember skey value
        x6events.fireEvent('buttonsOn_'+this.zTable);
        if(mode=='new')  {
            //console.log("detail display going into new mode");
            // For new rows, set defaults, otherwise blank
            // out.
            $(this).find(':input').each(function() {
                this.value=u.p(this,'xdefault','');
                this.zOriginalValue = this.value;
                this.zChanged = 0;
                this.zActive  = 1;
            });
            //if(typeof(row.skey)!='undefined') {
            //    u.debug("detail display populating inputs");
            //    this.populateInputs(row);
            //}
            u.bb.vgfSet('skey_'+this.zTable,0);
        }
        else {
            this.populateInputs(row);
            u.bb.vgfSet('skey_'+this.zTable,row.skey);
        }
        
        
        // now set the readonly and new flags on all controls
        $(this).find(':input').each(function() {
            this.zNew = mode=='new' ? 1 : 0;
            if(mode=='new') {
                var ro = u.p(this,'xroins','N');
            }
            else {
                var ro = u.p(this,'xroupd','N');
            }
            if(ro=='Y') {
                this.zRO = 1;
                this.disabled = true;
            }
            else {
                this.zRO = 0;
                this.disabled = false;
            }
            x6inputs.setClass(this);
        });
        $(this).find(':input:not(.readOnly):first').focus();
        
        //console.log("detailDisplay displayRow FINISHED");
        //console.groupEnd();
        return true;
    }

    self.populateInputs = function(row) {
        $(this).find(":input").each(function() {
                this.zOriginalValue = '';
                this.zChanged       = false;
        });
        for(var colname in row) {
            var val = row[colname];
            colname=colname.trim();
            var jqobj =$(this).find(':input[xcolumnid='+colname+']');
            if(jqobj.length>0) {
                if(val==null) val='';
                jqobj[0].value          = val;
                jqobj[0].zOriginalValue = val;
                jqobj[0].zChanged       = false;
                jqobj[0].zActive        = 1;
            }
        }
    }
}


/***im* x6plugins/tabDiv
*
* NAME
*   x6plugins.tabDiv
*
* FUNCTION
*   The Javascript method x6plugins.tabDiv implements
*   all browser-side functionality for Andromeda's built-in
*   plugIn tabDiv.  A "tabDiv" appears to the user to be an
*   HTML TABLE but it is implemented with divs.
*
*   This routine is called automatically by x6.init, there
*   is not usually any reason for calling this routine
*   directly.
*
* INPUTS
*   self - the DOM object to be activated.
*   id - the ID of the object to be 'activated'.
*   table - the database table that the tabDiv is handling.
*
* RESULTS
*   no return value.
*
******
*/
x6plugins.x6tabDiv = function(self,id,table) {
    /*
    *    These two will tell us down below if the grid
    *    displays inputs for new rows and allows 
    *    inline editing of rows
    */
    var uiNewRow  = u.p(self,'uiNewRow' ,'');
    var uiEditRow = u.p(self,'uiEditRow','');
    
    /*
    *   The grid is happy to display a new row for
    *   editing if a certain flag has been set.
    *   The event uiNewRow is unconditional, it means
    *   all prerequisites have been met and the grid
    *   should proceed forthwith.
    */
    if(uiNewRow=='inline') {
        x6events.subscribeToEvent('uiNewRow_'+table,id);
        
        self['receiveEvent_uiNewRow_'+table] = function() {
            //console.group("tabDiv uiNewRow "+this.zTable);
            var skey = u.bb.vgfGet('skey_'+this.zTable,-1);
            
            /*
            *   If we are currently editing a new row, just
            *   focus on it.
            */
            if(skey==0 && u.bb.vgfGet('lastSave_'+this.zTable)=='noaction') {
                $(this).find("#row_0 :input:first:not([disabled])").focus();
                //console.log("On an empty new row, setting focus");
                //console.groupEnd();
                return;
            }
            
            /*
            *   If editing some other row, we know it was saved
            *   and is ok, convert it back to display
            */
            if(skey>=0) {
                this.removeInputs();
            }

            /*
            *   This is the major UI stuff.  We need to slip a
            *   row into the grid, clone some of the invisible
            *   inputs that have been provided by the PHP code,
            *   and get them all initialized and ready to go.
            */
            var newRow = new jsHtml('div');
            newRow.hp.id = 'row_0';
            newRow.hp.style = 'display: none;';
            var numbers = [ 'int', 'numb', 'money' ];
            for (var idx in this.zColsInfo) {
                var colInfo = this.zColsInfo[idx];
                if(colInfo.column_id == '') continue;
                
                var innerDiv = newRow.h('div');
                innerDiv.hp.style= "width: "+colInfo.width+"px;";
                innerDiv.hp.gColumn = idx;
                if(numbers.indexOf(colInfo.type_id)>=0) {
                    innerDiv.hp.style+="text-align: right";
                }
                var id = '#wrapper_'+this.zTable+'_'+colInfo.column_id;
                var newInput = $(id).html();
                //console.log("column: ",colInfo.column_id);
                innerDiv.innerHtml = newInput;
            }
            
            /*
            *   Now do everything required to make the 
            *   row visible and editable
            */
            $(this).find('.tbody').prepend(newRow.bufferedRender());
            tabIndex = 1000;
            $(this).find(':input').each(
                function() { 
                    x6inputs.initInput(this,tabIndex++,'new','rowNew'); 
                    this.setAttribute('xTabGroup','rowEdit');
                }
            );
            var grid = this;
            $(this).find('#row_0').fadeIn('fast'
                ,function() {
                    x6inputs.findFocus( this );
                }
            );
            
            // Send a message and get lost
            u.bb.vgfSet('skey_'+this.zTable,0);
            x6events.fireEvent('buttonsOn_'+this.zTable);
            //console.log('New row created, ready to edit');
            //console.groupEnd();
            return true;
        }
    }
    
    /*
    *   The grid is happy to display an existing row
    *   for editing if the flag has been set.  This
    *   is an unconditional event, it assumes all is
    *   well and nothing stands in the way of editing.
    */
    if(uiEditRow=='inline') {
        x6events.subscribeToEvent('uiEditRow_'+table,id);
        
        self['receiveEvent_uiEditRow_'+table] = function(skey) {
            //console.group("tabDiv uiEditRow "+this.zTable);
    
            if( $(this).find('#row_'+skey).length == 0) {
                //console.log("We don't have that row, cannot edit");
                //console.groupEnd();
                return;
            }
            
            /*
            *   If editing some other row, we know it was saved
            *   and is ok, convert it back to display
            */
            if(u.bb.vgfGet('skey_'+this.zTable)>=0) {
                this.removeInputs();
            }
    
            //console.log("Putting inputs into div cells");
            grid = this;
            $(this).find('.tbody #row_'+skey+' div').each(
                function() {
                    // Work up to figuring out the name of the
                    // id that holds the hidden input, then
                    // grab the input and put it in.
                    var colnum = u.p(this,'gColumn');
                    var colid  = grid.zColsInfo[colnum].column_id;
                    var id = 'wrapper_'+grid.zTable+'_'+colid;

                    // Current Value
                    var curval = this.innerHTML;
                    //console.log(id,curval);
                    
                    this.innerHTML = u.byId(id).innerHTML;
                    $(this).find(":input")[0].value=curval;
                }
            );
            tabIndex = 1000;
            $(this).find('.tbody #row_'+skey+' :input').each(
                function() {
                    x6inputs.initInput(this,tabIndex++,'new','rowEdit'); 
                }
            );
            var string = x6inputs.jqFocusString();
            $(this).find('.tbody #row_'+skey+' '+string).focus();
            x6events.fireEvent('buttonsOn_'+this.zTable);
            u.bb.vgfSet('skey_'+this.zTable,skey);
            //console.log('uiEditRow Completed, returning true');
            //console.groupEnd();
            return true;
        }
    }
    
    /*
    *   A grid may need to convert inputs back into display
    *   elements.  This routine is unconditionally created
    *   and is only called by ui event handlers.
    *
    */
    self.removeInputs = function() {
        //console.group("tabDiv removeInputs");

        var skey = u.bb.vgfGet('skey_'+this.zTable);
        //console.log("skey is ",skey);
        $(this).find("#row_"+skey+" div").each(
            function() {
                var val = $(this).find(":input")[0].value; 
                //console.log(val);
                this.innerHTML = val;
            }
        );

        // If we are removing inputs from the 0 row
        // and the last save had no action, kill the row
        if(skey==0) {
            if(u.bb.vgfGet('lastSave_'+this.zTable)=='noaction') {
                //console.log("No action on last save, removing row ",skey);
                $(this).find("#row_0").fadeOut(
                    function() { $(this).remove() }
                );
            }
        }
        
        // Since we are no longer editing, set skey appropriately
        //console.log("tabDiv removeInputs Finished");
        //console.groupEnd();
        return true;
    }
    
    
    /*
    *   A grid must always have a facility to receive 
    *   new values from any source.  The code is smart
    *   enough to figure out if the row is in edit mode
    *   or display mode.
    *
    */
    x6events.subscribeToEvent('uiRowSaved_'+table,id);
    self['receiveEvent_uiRowSaved_'+table] = function(row) {
        //console.group("tabDiv uiRowSaved: "+this.zTable);
        // Replace the input values with server returned values
        skey = u.bb.vgfGet('skey_'+this.zTable);
        if( $(this).find("#row_"+skey+" :input").length > 0) {
            //console.log($(this).find("#row_"+skey+" :input"));
            //console.log("found inputs, going rowSavedEdit");
            this.uiRowSavedEdit(row);
        }
        else {
            //console.log("no inputs, going rowSavedNoEdit");
            this.uiRowSavedNoEdit(row);
        }
        //console.log("tabDiv uiRowSaved finished, returning TRUE");
        //console.groupEnd();
    }
    
    self.uiRowSavedEdit = function(row) {
        var skey = u.bb.vgfGet('skey_'+this.zTable);
        $(this).find("#row_"+skey+" :input").each(
            function() {
                col = u.p(this,'xColumnId');
                //console.log(col,row[col]);
                this.value = row[col];
                this.zOriginalValue = row[col];
            }
        );
        this.removeInputs();
        x6events.fireEvent('buttonsOff_'+this.zTable);
        u.bb.vgfSet('skey_'+this.zTable,-1);
        
        // If this was a new row, set it up
        if(skey==0) {
            //console.log("Was new row, setting up the row for editing");
            table = this.zTable;
            $(this).find("#row_0").each(
                function() {
                    this.id = 'row_'+row.skey;
                }
            );
        
            this.initRow(skey);
        }
        
    }
        
    self.uiRowSavedNoEdit = function(row) {
        var skey = row.skey;
        
        // If a new row has been saved and we don't 
        // have it, create it now
        if($(this).find('.tbody #row_'+skey).length==0) {
            var newRow = new jsHtml('div');
            newRow.hp.id = 'row_'+skey;
            newRow.hp.style = 'display: none;';
            var numbers = [ 'int', 'numb', 'money' ];
            for (var idx in this.zColsInfo) {
                var colInfo = this.zColsInfo[idx];
                if(colInfo.column_id == '') continue;
                
                var innerDiv = newRow.h('div');
                innerDiv.hp.style= "width: "+colInfo.width+"px;";
                innerDiv.hp.gColumn = idx;
                if(numbers.indexOf(colInfo.type_id)>=0) {
                    innerDiv.hp.style+="text-align: right";
                }
                innerDiv.innerHtml = row[colInfo.column_id];
            }
            $(this).find('.tbody').prepend(newRow.bufferedRender());
            this.initRow(skey);
            $(this).find("#row_"+skey).fadeIn();
        }
        else {
            for(var idx in this.zColsInfo) {
                var col = this.zColsInfo[idx].column_id;
                if(col!='') {
                    var str="#row_"+skey+" div[gColumn="+idx+"]";
                    $(this).find(str).html(row[col]);
                }
            }
        }
    }
    
    self.initRow = function(skey) {
        // PHP-JAVASCRIPT DUPLICATION ALERT!
        // This code also exists in androLib.php
        // addRow method of the tabDiv class
        var table = this.zTable;
        $(this).find('#row_'+skey)
            .mouseover(
                function() { 
                    $(this).siblings('.hilight').removeClass('hilight');
                    $(this).addClass('hilight') 
                }
            )
            .click(
                function() {
                    x6events.fireEvent(
                        'reqEditRow_'+table,skey
                    );
                }
            );
        
    }
                
    /*
    *   A uiDelRow command means a row has been deleted
    *   from the server, and anybody displaying it must
    *   remove it from the display.
    */
    x6events.subscribeToEvent('uiDelRow_'+table,id);
    self['receiveEvent_uiDelRow_'+table] = function() {
        //console.group("tabDiv uiDelRow "+this.zTable);
        skey = u.bb.vgfGet('skey_'+this.zTable);
        if(skey==-1) {
            //console.log("No row, ignoring");
            return;
        }
        else if(this.zSkey==0) {
            //console.log("on a new row, firing cancelEdit command");
            x6events.fireEvent('cancelEdit_'+this.zTable);
        }
        else {
            $(this).find("#row_"+skey).fadeOut(
                function() {
                    $(this).remove();
                }
            );
            u.bb.vgfSet('skey_'+this.zTable,-1);
            x6events.fireEvent('buttonsOff_'+this.zTable);
        }
        //console.log("uiDelRow finished");
        //console.groupEnd();
    }
    
    /*
    *    If a grid is displaying inputs, it may also have
    *    to display errors.
    */
    if(uiEditRow=='inline' || uiNewRow=='inline') {
        x6events.subscribeToEvent('uiShowErrors_'+table,id);
        self['receiveEvent_uiShowErrors_'+table] = function(errors) {
            //console.group("tabDiv uiShowErrors");
            //console.log(errors);
            for(var idx in errors) {
                //console.log(errors[idx]);
                var aError = errors[idx].split(':');
                var column = aError[0];
                //console.log("Setting zError for column ",column);
                $(this).find(":input[xColumnId="+column+"]").each(
                    function() {
                        this.zError = 1;
                        x6inputs.setClass(this);
                    }
                );
            }
            //console.log("tabDiv uiShowErrors finished");
            //console.groupEnd();
            return true;
        }
    }
    
    /*
    *    Keyboard handling.  At very
    *    least there are always 3 we accept: arrow keys and
    *    ENTER key.  
    */
    // First keyboard event, keydown
    x6events.subscribeToEvent('key_UpArrow',id);
    self.receiveEvent_key_UpArrow = function(e) {
        //console.group("tabDiv key_UpArrow");
        var jqRows = $(this).find('.hilight').prev();
        if(jqRows.length==0) {
            //console.log("going for first row");
            $(this).find('.tbody div:first').addClass('hilight');
        }
        else {
            //console.log("Going for previous row");
            $(this).find('.tbody div.hilight').removeClass('hilight')
                .prev().addClass('hilight');
        }
        x6events.retvals['key_UpArrow'] =false;
        //console.log("tabDiv key_UpArrow finished");
        //console.groupEnd();
    }
    x6events.subscribeToEvent('key_DownArrow',id);
    self.receiveEvent_key_DownArrow = function(e) {
        //console.group("tabDiv key_DownArrow");
        var jqRows = $(this).find('.hilight').next();
        if(jqRows.length==0) {
            //console.log("going for first row");
            $(this).find('.tbody div:first').addClass('hilight');
        }
        else {
            //console.log("going for next row");
            $(this).find('.hilight').removeClass('hilight')
                .next().addClass('hilight');
        }
        x6events.retvals['key_DownArrow'] =false;
        //console.log("tabDiv key_DownArrow finished");
        //console.groupEnd();
    }
    x6events.subscribeToEvent('key_Enter',id);
    self.receiveEvent_key_Enter = function(e) {
        //console.group("tabDiv key_Enter - clicking hilighted rows");
        $(this).find('.tbody div.hilight').click();
        //console.groupEnd();
    }
    
    /*
    *    Lookup stuff.  If we have a row of input lookups on the
    *    grid, they will all route to here.
    *
    */
    self.fetch = function(doFetch) {
        if(doFetch==null) doFetch=false;
        var cntNoBlank = 0;
        
        // Initialize and then scan
        ua.json.init('x6page',this.zTable);
        $(this).find(".thead :input").each(function() {
            if(typeof(this.zValue)=='undefined') 
                this.zValue = this.getAttribute('xValue');
            if(this.value!=this.zValue) {
                doFetch = true;
            }
            if(this.value!='') {
                cntNoBlank++;
            }
            this.zValue = this.value;
            ua.json.addParm('x6w_'+u.p(this,'xColumnId'),this.value);
        });
        
        if(doFetch) {
            // Clear the previous results
            ua.data.browseFetchHtml = '';
            if(cntNoBlank==0) {
                $(this).find('.tbody').html('');
                return;
            }
            //if(this.zSortCol) {
            //    $a.json.addParm('sortCol',this.zSortCol);
            //    $a.json.addParm('sortAD' ,this.zSortAD);
            //}
            ua.json.addParm('x6action','browseFetch');
            if( ua.json.execute()) {
                ua.json.process();
                // The standard path is to take data returned
                // by the server and render it.  This is safe
                // even if the server does not return anything,
                // because we initialized to an empty object.
                $(this).find(".tbody").html(ua.json.jdata.html.browseFetchHtml);
                
                // index the rows
                //this.indexRows();
            }
        }
    }
}
