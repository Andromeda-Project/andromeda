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

/* ---------------------------------------------------- *\
   STRUCTURE OF THIS FILE
   
   This file contains the oldest javascript in 
   Andromeda.  Some code is required for b/w 
   compatibility, and other code was used only
   once or twice and is now dead.  The basic 
   sections of this file are:
   
   1) Required for compatibility with x2 applications
   2) Javascript prototype extensions
   3) Jquery Plugins
   4) Universal utility object, u, no Andromeda dependencies   
   5) The Andromeda general utility object, au
   6) Andromeda utilties not part of au, like androSelect()
   7) The (old) general utility object, $a (mixed dependencies)
   8) All other presumed deprecated code.
      
\* ---------------------------------------------------- */
      

/* ---------------------------------------------------- *\
   SECTION 1: X2 COMPATIBILITY.  
   
   Not deprecated outright, but discouraged. You
   should be coding x4 pages which have much 
   nicer functions for everything these functions do.
\* ---------------------------------------------------- */
function SetAndPost(varname,varvalue) {
	ob(varname).value = varvalue;
	formSubmit();
}
function SaveAndPost(varname,varvalue) {
   ob('gp_save').value=1;
	ob(varname).value = varvalue;
	formSubmit();
}
var ajaxTM=0;
function formPostAjax(stringparms) {
   var ajaxTMold=ajaxTM;
   ajaxTM=1;
   formPostString(stringparms);
   ajaxTM=ajaxTMold;
}
function formPostString(stringparms) {
   // Modified KFD 5/30/07 to switch to AJAX calls.  We now pass
   //   the string parms to formSubmit, where it uses them to 
   //   build a post string.
   //ob('Form1').action="index.php?"+stringparms;
   //formSubmit();
   formSubmit(stringparms);
}

function SetAction(gp_var,gp_action,varname,varvalue) {
	ob(gp_var).value = gp_action;
	SetAndPost(varname,varvalue);
}

function drillDown(dd_page,ddc,ddv) {
	drillDownSet(dd_page,ddc,ddv);
	formSubmit();
}

function drillDownSet(dd_page,ddc,ddv) {
	ob("dd_page").value   = dd_page;
	ob("dd_ddc").value    = ddc;
	ob("dd_ddv").value    = ddv;
}

function drillBack(gp_count) { 
	ob("dd_ddback").value=gp_count;
	formSubmit(); 
}
function serverFunctionCall(name,parms) {
   function_return_value=false;
   var str=''
   str = '?gp_function='+name;
   str+= '&gp_parms='+parms;
   andrax(str);
}

function byId(id) {
   return document.getElementById(id);
}
function ob(oname) {
  if (document.getElementById)
	  return document.getElementById(oname);
  else if (document.all) 
	  return document.all[name];
}
// Get the value of an object
function obv(oname) {
   if(ob(oname)) return ob(oname).value;
   else return '';
}

// Return keystroke like 'A' or '1'
function KeyEvent(e) {
   if(window.event)
      // IE
      return window.event.keyCode;  
   else
      // firefox
      return e.which;
}
// Compatibility
// Cross-browser implementation of element.addEventListener()
function addEventListener(element, type, expression,uc) {
    if(uc==null) { uc=false; }
    if (element.addEventListener) { // Standard
        element.addEventListener(type, expression, uc);
        return true;
    } else if (element.attachEvent) { // IE
        element.attachEvent('on' + type, expression);
        return true;
    } else return false;
}
// function contributed by Don Organ 10/29/07
function removeEventListener(el, eType, fn) {
    if (el.removeEventListener) {
        el.removeEventListener(eType, fn, false);
    }
    else if ( el.detachEvent ) {
        el.detachEvent( 'on' + eType, fn );
    } 
    else {
        return false;     
    }
}
// Compatibility
function eventTarget(e) {
    if(window.event) 
        return window.event.target;
    else 
        return e.currentTarget;
}

function bodyKeyPress(e) {
   keycode = KeyCode(e);
   
   // Handle function keys from f1 to f12
   if(keycode >= 112 && keycode <= 123) {
      var objnum = keycode - 111;
      var objname= 'object_for_f'+objnum.toString();
      //c*onsole.log(objname);
      obj = ob(objname);
      if(obj) {
         //c*onsole.log('going to onclick');
         obj.onclick();
         return false;
      }
   }
   
   if(keycode==13) {
      obj = ob('object_for_enter');
      if(obj) {
         obj.onclick();
         return false;
      }
   }
   
   /* Forward compatible to x4.js.  If that is not loaded,
      this array will not exist.  If it does exist, the code
      assumes the complete x4.js is loaded.
    */
   if(typeof(x4letters)!='undefined') {
       var objId='object_for_'+x4letters[e.charCode-97];
       if(byId(objId)) byId(objId).onclick();
   }
   
   return true;
   
   // END key         is 35
   // HOME key        is 36
   // PAGE DOWN   key is 34
   // PAGE UP     key is 33
   //alert(key);
}
// ------------------------------------------------------
// Universal handlers for events on user inputs
// ------------------------------------------------------
function inputOnFocus(obj) {
   /* read only controls don't do anything */
   //var lp = 'onfocus ' + obj.name;
   //c*onsole.log(lp);
   if(obj.readOnly) return;

   // Set the value now, so that we can detect changes
   // in values that need to call back to the server 
   //c*onsole.log(lp + ', getting value');
   obj.attributes.getNamedItem('x_value_focus').value=obj.value;
   
   // Set color on gaining focus
   //c*onsole.log(lp + ', calling focuscolor');
   focusColor(obj,true);
}
function inputOnBlur(obj) {
   /* read only controls don't do anything */
   //var lp = 'onfocus ' + obj.name;
   if(obj.readOnly) return true;
   
   // Check for changes and do the focus color thing
   //c*onsole.log(lp + ', calling changecheck');
   changeCheck(obj);   
   //c*onsole.log(lp + ', calling focuscolor');
   focusColor(obj,false);
   return true;
}
function inputClick(obj) {
   changeCheck(obj);
}
function inputOnKeyUp(e,obj) {
   keycode = KeyCode(e);
   // Check for F2 - F10 or END/Home
   if((keycode >= 113 && keycode <= 121) || (keycode >=33 && keycode <=40)) {
      bodyKeyPress(e);
      return false;
   }

   // read only controls don't do anything 
   if(obj.readOnly) return;
   
   // Check for open ajax dynamic list? not necessary, they take
   // over the keyboard automatically
   // UP ARROW    key is 38
   // DOWN ARROW  key is 40
   // RIGHT ARROW key is 39
   // LEFT ARROW  key is 37
   if(keycode == 38) {
      // handle the up arrow
   }
   else if(keycode == 40) {
      // handle the down arrow
   }
   else {
      fieldformat(obj,keycode);
      changeCheck(obj);
      return true;
   }
}

// KFD 8/8/07, project JS_KEYSTROKE, add lots of functionality
// for javascript
function fieldformat(obj,keycode) {
   var type_id = objAttValue(obj,'x_type_id');
   var objval  = obj.value;
   if(type_id=='ssn') {
      if(objval.length==3) {
         if(keycode!=8) {
            obj.value += '-';
         }
            
      }
      if(objval.length==6) {
         if(keycode!=8) {
            obj.value += '-';
         }
      }
   }
   if(type_id=='ph12') {
      if(objval.length==3) {
         if(keycode!=8) {
            obj.value += '-';
         }
      }
      if(objval.length==7) {
         if(keycode!=8) {
            obj.value += '-';
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
               obj.value=month+'/'+day+'/'+year;
            }
         }
      }
   }
}

// KFD, 5/25/07, Part of AJAX X3
// Revised 8/8/07 project JS_KEYSTROKE
function changeCheck(obj) {
   /* read only controls don't do anything */
   if(obj.readOnly) return;

   var x_value_original = objAttValue(obj,'x_value_original');
   var x_class_base     = objAttValue(obj,'x_class_base');
   var x_mode           = objAttValue(obj,'x_mode');
   if(x_mode!='ins' && x_mode!='search') {
       if(obj.attributes.getNamedItem('x_class_base')) {
           if(obj.value != x_value_original) {
               obj.attributes.getNamedItem('x_class_base').value='ins';
           }
           else {
               obj.attributes.getNamedItem('x_class_base').value='upd';
           }
       }
   }
   fieldColor(obj);
}

// KFD 5/25/07, Part of AJAX X3
// Revised 8/8/07 project JS_KEYSTROKE
// Change class Suffix to Selected
function focusColor(obj,selected) {
   if(obj.readOnly && selected) return;
   if(selected) {
      obj.attributes.getNamedItem('x_class_suffix').value='Selected';
   }
   else {
      obj.attributes.getNamedItem('x_class_suffix').value='';
   }
   fieldColor(obj);
}

// KFD 5/25/07, Part of AJAX X3
// Revised 8/8/07 project JS_KEYSTROKE
// Assign class to an item
function fieldColor(obj) {
   /* If it does not have the x_class_base, it ain't one of ours */
   if(!obj.attributes.getNamedItem('x_class_base')) return;
   
   obj.className='x3'
      +objAttValue(obj,'x_class_base')
      +objAttValue(obj,'x_class_suffix')
}

// ------------------------------------------------------
// KFD 10/8/07.  Backport.  We've started work on X4,
//    so only small fixes go here now.  This one lets
//    us do a two-column foreign key with dropdowns
// ------------------------------------------------------
function fetchSELECT(table,input,col1,col1val,col2,col2val) {
    var getString
        ='?ajxfSELECT='+table
        +'&pfx='+objAttValue(input,"nameprefix")
        +'&col1='+col1
        +'&col1val='+encodeURIComponent(col1val)
        +'&col2='+col2
        +'&col2val='+encodeURIComponent(col2val);
    andrax(getString);
}

function formSubmit(str) {
   // This is the branch that just submits
   if(typeof(ajaxTM)=='undefined') {
      if(str) {
         ob('Form1').action="index.php?"+str;
      }
      ob("Form1").submit();
      return;
   }
   if(ajaxTM==0) {
      if(str) {
         ob('Form1').action="index.php?"+str;
      }
      ob("Form1").submit();
      return;
   }
   
   // this is the ajax branch
   formSubmitajxBUFFER(str);
   return;
}
   
function formSubmitajxBUFFER(str) {
   if(str) {str='&'+str } else { str=''; }
   var postString= '?ajxBUFFER=1' + str;
   var f = ob('Form1');
   for(var no = 0; no < f.elements.length; no++ ) {
      e=f.elements[no];
      if(e.type=='hidden') {
         if(e.value) {
            postString+="&"+e.name+"=";
            postString+=encodeURIComponent(e.value);
         }
      }
      else {
         if(e.attributes.getNamedItem('x_value_original')) {
            xva = e.attributes.getNamedItem('x_value_original');
            if(e.tagName=='TEXTAREA') {
               postString+="&"+e.name+"=";
               postString+=encodeURIComponent(e.innerHTML);
            }
            else {
               postString+="&"+e.name+"=";
               postString+=encodeURIComponent(e.value);
            }
         }
         else {
            postString+="&"+e.name+"="+encodeURIComponent(e.value);
         }
      }
   }
   andrax(postString);
}

function fieldsReset() {
   var f = ob('Form1');
   //f.reset();
   var x = 'x'
   for(var no = 0; no < f.elements.length; no++ ) {
      if(f.elements[no].type!='hidden') {
         x=f.elements[no];
         if(x.attributes.getNamedItem('x_value_original')!=null) {
             x.value=x.attributes.getNamedItem('x_value_original').value;
             if(x.attributes.getNamedItem('x_mode').value=='upd') {
                 changeCheck(x);
             }
         }
      }
   }
}

function clearBoxes() {
   var f = ob('Form1');
   for(var no = 0; no < f.elements.length; no++ ) {
      obj=f.elements[no];
      if(obj.type=='hidden') continue;
      if(obj.type=='button') continue;
      //if(obj.attributes.getNamedItem('x_no_clear')) {
         if(obj.attributes.getNamedItem('x_no_clear').value=='Y') continue;
      //}
      f.elements[no].value='';
   }
}

// ------------------------------------------------------
// Popup a window and display something in that.
// ------------------------------------------------------
function Popup(url,caption){
	w = 770;
	h = 700;
	mytop = 100;
	myleft = 100;
	//settings="width=" + w + ",height=" + h + ",top=" + mytop + ",left=" + myleft + ", " +
	//	"scrollbars=yes,location=no,directories=no,status=no,resizable=yes";
	settings="width=" + w + ",height=" + h + ",top=" + mytop + ",left=" + myleft + ", " +
		"scrollbars=yes,resizable=yes";
	//win=window.open(url,caption,settings);
   //alert(5);
   // KFD 7/19/06, Using the caption fails on IE 6, go figure.
   //              not pursued at this time....
   //settings='';
	win=window.open(url,'Popup',settings);
   //win=window.open('http://www.novell.com','Testing','');
	win.focus();
}

// ------------------------------------------------------
// Popup a custom-size window and display something in that.
// ------------------------------------------------------
function PopupSized(url,caption,w,h,mytop,myleft){
	//w = 770;
	//h = 700;
	//mytop = 100;
	//myleft = 100;
	//settings="width=" + w + ",height=" + h + ",top=" + mytop + ",left=" + myleft + ", " +
	//	"scrollbars=yes,location=no,directories=no,status=no,resizable=yes";
	settings="width=" + w + ",height=" + h + ",top=" + mytop + ",left=" + myleft + ", " +
		"status=no,scrollbars=no,resizable=no,toolbars=no";
	win=window.open(url,caption,settings);
   //alert(5);
   // KFD 7/19/06, Using the caption fails on IE 6, go figure.
   //              not pursued at this time....
	//win=window.open(url,'Popup',settings);
   //win=window.open('http://www.novell.com','Testing','');
	win.focus();
}



// ------------------------------------------------------
// Routines added 4/9/07 for AJAX-ified interface
// ------------------------------------------------------
/**
name:FetchRow
parm:table_id
parm:pk_value

Executes an AJAX call to fetch a row to the browser for general use.

On the server the program [[index_hidden.php]] will handle this
call and return a 

-- This was an experimental function, and will likely disappear --
*/
function FetchRow(table_id,pk_ob) {
   andrax('?gp_fetchrow='+table_id+'&gp_pk='+encodeURI(ob(pk_ob).value))
}

function adl_visible() {
   if(ajax_optionDiv) {
      if(ajax_optionDiv.style.display!='none') return true;
   }
   if(ajax_optionDiv_iframe) {
      if(ajax_optionDiv_iframe.style.display!='none') return true;
   }
}

// DEPRECATED BY JS_KEYSTROKE but very much alive at the moment.
// Project JS_KEYSTROKE will revert to simply noticing that the
// value has changed, seeing that it has a "notify_server_on_change"
// flag, and sending the value back to the server for processing there.
// The server will then decide what to do about it.
//
function ajaxFetch(
    table_id_par
   ,name_prefix
   ,commapklist
   ,commafklist
   ,controls
   ,columns,obj) {

   // KFD 8/15/07.  This is a heartbreak.
   //     The line below checks for adl visibility.  The idea is not
   //     to call the ajax fetch if it is visible.  Without this line,
   //     the fetch is called twice when somebody uses the mouse to make
   //     a selection.  Worse, if somebody clicks somewhere outside of the
   //     the selected box, the ajax fetch fires.
   //
   //     However, if we uncomment it, then using TAB to exit a field does
   //     not work.  WORSE THAN THAT, this fact will be disguised if you have
   //     Firebug enabled.  Having firebug enabled causes it to work ok!
   //     Then the customer says, "hey, it doesn't work" and you pull your
   //     hair out wondering why.
   //
   //     Since we need the TAB key to work as an absolute must, we accept
   //     some strange behavior if people go clicking off in different
   //     places.
   //if(adl_visible()) return;
   
   // If the value did not change, do not do anything
   //var x_value_focus = obj.attributes.getNamedItem('x_value_focus').value
   //if(x_value_focus == obj.value) { return; }

   /* split up the pk list, make a list of controls and values */
   afklist=commafklist.split(',');
   vallist='';
   for(x=0; x<afklist.length; x++) {
      if(vallist.length!=0) {  vallist+=','; }
      ctlname=name_prefix+afklist[x];
      if(ob(ctlname)) {
         if(ob(ctlname).value=='') {
            return
         }
         else {
            vallist+=ob(ctlname).value
         }
      }
   }


   /* if we got this far, make the call */
   href='?ajxFETCH=1'
       +'&ajxtable='+table_id_par
       +'&ajxcol='+commapklist
       +'&ajxval='+vallist
       +'&ajxcontrols='+controls
       +'&ajxcolumns='+columns;
   //alert(href);
   andrax(href);
}

// ------------------------------------------------------
// This gives info on a row in a table
// ------------------------------------------------------
// Introduced w/x_table2
function Info2(table,field) {
var thevalue = ob(field).value;
	if (thevalue!="") {
      $href="?gp_page="+table+"&gp_pk="+thevalue;
      window.open($href);
		//Popup("?gp_out=info&gp_page="+table+"&gp_pk="+thevalue,"Info");
	}
	else {
		alert("Please select a value first!");
	}
}

// ------------------------------------------------------
// These are keyboard handling things
// ------------------------------------------------------

function doButton(e,compareto,obtoclick)  {
   // Obtain the keystroke.
   var key = KeyEvent(e);

   // if matches specified value, click the object
   if (key == compareto) {
      if(ob(obtoclick)) { ob(obtoclick).onclick(); }
   }
   return true;
}
/*  Not used yet
function doButtonActions(e,compareto,obtodo,funcs) {
   // Get the keystroke
   var key = KeyEvent(e);
   // If matches, do the events in order
   if(key==compareto) {
      afuncs=funcs.split(',');
      for(x=0; x<afuncs.length; x++) {
         eval( "ob('"+obtodo+"')."+afuncs[x] )
      }
   }      
}
*/



// ------------------------------------------------------
// Our super-simple AJAX library.  
//
// Originally copied whole from this site:
//
// http://rajshekhar.net/blog/archives/85-Rasmus-30-second-AJAX-Tutorial.html
//
// ...and then we modified it from there
// ------------------------------------------------------
function createRequestObject() {
    var ro;
    var browser = navigator.appName;
    if(browser == "Microsoft Internet Explorer"){
        ro = new ActiveXObject("Microsoft.XMLHTTP");
    }else{
        ro = new XMLHttpRequest();
    }
    return ro;
}

var http = createRequestObject();

function AjaxCol(objname,objvalue) {
   getString='?gp_ajaxcol=1';
   getString+='&gp_colname='+objname;
   getString+='&gp_colval='+objvalue;
   getString+='&gp_page='+ob('gp_page').value;
   if(objname!='appref') {
      getString+='&gp_appref='+ob('x2t_appref').value;  
   }
   andrax(getString);
}

function AjaxWriteVal(ajxc1table,ajxcol,ajxval,ajxskey,list) {
   getString ='?ajxc1table='+ajxc1table;
   getString+='&ajxcol='+ajxcol;
   getString+='&ajxval='+encodeURI(ajxval);
   getString+='&ajxskey='+ajxskey;
   getString+='&ajxlist='+list
   //alert(getString);
   andrax(getString);
}

// KFD 8/6/07.  A server "map-back" function.  Something in the client
//              invokes an ajax server function.  The class for this
//              page should include a function field_changed_<field> 
//              that will do what needs to be done here.
//              
function field_changed(page,field,value) {
   getString='?gp_page='+page
      +'&fwajax=field_changed'
      +'&ajx_field='+field
      +'&ajx_value='+encodeURIComponent(value)
   andrax(getString);
}

function andrax(getString,handler) {
    http.open('POST', 'index.php'+getString);
    if(handler==null) {
        http.onreadystatechange = handleResponse;
    }
    else {
        http.onreadystatechange = handler;
    }
    http.send(null);
}

function sndReq(action) {
    getString="?gp_page="+ob('gp_page').value;
    getString+="&gp_skey="+ob('gp_skey').value;
    getString+=action;
    andrax(getString);
}

function handleResponse() {
    if(http.readyState == 4){
        var response = http.responseText;
        var elements=new Array();
        
        var controls=new Array();

        // either multiples or only one
        if(response.indexOf('|-|') != -1) {
           elements=response.split('|-|');
           for(x=0; x<elements.length; x++) {
              controls = handleResponseOne(elements[x],controls);
           }
        }
        else {
           controls = handleResponseOne(response,controls);
        }
        
        if(controls) {
           for(var x=0;x<controls.length;x++) {
              changeCheck(ob(controls[x]));
              // This causes cascading fetches 
              if(ob(controls[x]).onblur)   { ob(controls[x]).onblur();  }
           }
        }
    }
}

function  handleResponseOne(one_element,controls) {
   //alert(one_element);
   //return;
   var update = new Array();
   //alert(one_element);
   if(one_element.indexOf('|') == -1) {
      if(one_element.length==0) return;
      alert('Bad Ajax Response: '+one_element);
   }
   else {
      update = one_element.split('|');
      //alert(update[0]);
      if(update[0]=='echo') {
         alert(update[1]);
      }
      else if(update[0]=='_focus') {
         ob(update[1]).focus();
      }
      else if(update[0]=='_prompt') {
         prompt(update[1],update[2]);
      }
      else if(update[0]=='_value') {
         if(ob(update[1])) {
            ob(update[1]).value=update[2];

            // save the name of the control, we will run the onchange
            // of all of them at the end.  We don't want to do it now
            // or we will have multiple ajax calls running at the same time.
            // That actually made firefox hang!
            controls.push(update[1]);
         }
         
      }
      else if(update[0]=='_script') {
         eval(update[1]);
      }
      else if(update[0]=='_redirect') {
         window.location=update[1];
      }
      else if(update[0]=='_title') {
         document.title=update[1];
      }
      else if(update[0]=='_alert') {
         if(ob('ql_right')) ob('ql_right').innerHTML=update[1];
         //alert(update[1]);
      }
      else {
         if(!ob(update[0])) {
            alert('Bad Object: '+update[0]);
            alert('Value: '+update[1]);
         }
         else {
            ob(update[0]).innerHTML=update[1];
         }
      }
   }
   return controls;
}


/* ---------------------------------------------------- *\

   SECTION 2: javascript prototype extensions  
   
\* ---------------------------------------------------- */

String.prototype.trim = function() {
	return this.replace(/^\s+|\s+$/g,"");
}
String.prototype.ltrim = function() {
	return this.replace(/^\s+/,"");
}
String.prototype.rtrim = function() {
	return this.replace(/\s+$/,"");
}

String.prototype.pad = function(size,character,where) {
    var val = this;
    while(val.length < size) {
        if(where == 'L') 
            val = character + val;
        else
            val += character;
    }
    return val;
}

// Taken from http://www.sourcesnippets.com/javascript-string-pad.html
var STR_PAD_LEFT = 1;
var STR_PAD_RIGHT = 2;
var STR_PAD_BOTH = 3;
 
String.prototype.strpad = function(str, len, pad, dir) {
 
    str = String(str);
	if (typeof(len) == "undefined") { var len = 0; }
	if (typeof(pad) == "undefined") { var pad = ' '; }
	if (typeof(dir) == "undefined") { var dir = STR_PAD_RIGHT; }
 
	if (len + 1 >= str.length) {
 
		switch (dir){
 
			case STR_PAD_LEFT:
				str = Array(len + 1 - str.length).join(pad) + str;
			break;
 
			case STR_PAD_BOTH:
				var right = Math.ceil((padlen = len - str.length) / 2);
				var left = padlen - right;
				str = Array(left+1).join(pad) + str + Array(right+1).join(pad);
			break;
 
			default:
				str = str + Array(len + 1 - str.length).join(pad);
			break;
 
		} // switch
 
	}
 
	return str;
 
}

String.prototype.pad = function(len, pad, dir) {
 
    str = this.toString();
	if (typeof(len) == "undefined") { var len = 0; }
	if (typeof(pad) == "undefined") { var pad = ' '; }
	if (typeof(dir) == "undefined") { var dir = STR_PAD_RIGHT; }
 
	if (len + 1 >= str.length) {
 
		switch (dir){
 
			case STR_PAD_LEFT:
				str = Array(len + 1 - str.length).join(pad) + str;
			break;
 
			case STR_PAD_BOTH:
				var right = Math.ceil((padlen = len - str.length) / 2);
				var left = padlen - right;
				str = Array(left+1).join(pad) + str + Array(right+1).join(pad);
			break;
 
			default:
				str = str + Array(len + 1 - str.length).join(pad);
			break;
 
		} // switch
 
	}
 
	return str;
 
}


/* ---------------------------------------------------- *\

   SECTION 3: jQuery plugins  
   
\* ---------------------------------------------------- */

 
/*
 * Plugin to track focus
 */
jQuery.focusTrack = false;
jQuery.focusTrackStack = [ ];
jQuery.focusTrackPop = function() {
    if(jQuery.focusTrackStack.length > 0) {
        $( jQuery.focusTrack ).blur();
        jQuery.focusTrack = jQuery.focusTrackStack.pop(); 
    }    
}
jQuery.focusTrackBlur = function() {
    if(jQuery.focusTrackStack.length > 0) {
        $( jQuery.focusTrack ).blur();
    }    
}
jQuery.focusTrackRestore = function() {
    $(jQuery.focusTrack).focus();
}
jQuery.fn.focusTrack = function(newContext) {
    // If a new context, push the old back a layer     
    if(newContext) {
        if(jQuery.focusTrack) {
            $(jQuery.focusTrack).blur();
        }
        jQuery.focusTrackStack[jQuery.focusTrackStack.length]=jQuery.focusTrack;
        jQuery.focusTrack = false;
    }
    return this.each(function() {
        $(this).focus( function() {
            jQuery.focusTrack = this;
        })
    });
}

jQuery.getCSS = function( url, media, rel, title ) {
   jQuery( document.createElement('link') ).attr({
       href: url,
       media: media || 'screen',
       type: 'text/css',
       title: title || '',
       rel: rel || 'stylesheet'
   }).appendTo('head');
};

var jqModalClose=function(hash) { hash.w.fadeOut(500, function() { hash.o.fadeOut(250);}); };
var jqModalOpen=function(hash) { hash.w.fadeIn(500);hash.o.fadeIn(500);};

/* ---------------------------------------------------- *\

   SECTION 4: Universal Utility Object   
   
   This is *nearly* independent of all Andromeda
   dependencies, except the following:
   
   1) Expects a div at bottom of html w/ID "invisible"
   2) Assumes Andromeda style sheet (ie "style1")
   
\* ---------------------------------------------------- */
var u = {
    /**
    * Basic utility functions are in the "u" subobject
    */
    debugFlag: false,
    
    p: function(obj,varName,defValue) {
        if(typeof(obj)!='object') {
            return defValue;
        }
        
        // First try, maybe it is a direct property
        if(typeof(obj[varName])!='undefined') {
            return obj[varName];
        }
        // Second try, maybe it is an attribute
        if(obj.getAttribute) {
            if(obj.getAttribute(varName)!=null) {
                return obj.getAttribute(varName);
            }
        }
        // Give up, return the defvalue
        return defValue;
    },
    
    // Get an object by ID
    byId: function(id) {
        return document.getElementById(id );
    },
    
    // Make a log entry if firebug console is available
    log: function(message) {
        if(typeof(console)!='undefined') {
            console.log(msg);
        }
    },
    // If flagDebug is true, send message to log
    debug: function(message) {
        if(this.debugFlag) {
            this.log(message);
        }
    },
    
    uniqueId: function() {
        return Math.floor(Math.random()*1000000);
    },
    
    clone: function(obj,level) {
        if(level==null) level = 1;
        if(level==10) return { };
        var retval = { };
        for (var x in obj) {
            if(typeof(obj[x])=='object') {
                retval[x] = u.clone(obj[x],level+1);
            }
            else {
                retval[x] = obj[x];
            }
        }
        return retval;
    },
    
    /*
    * bulletin board to simulate safe global variables.  You
    * can "stick" them here and grab them later
    */
    bb: {
        fwvars: { },
        appvars: { },
        vgfSet: function(varName,value) {
            this.fwvars[varName] = value;
        },
        vgfGet: function(varName,defValue) {
            return u.p(this.fwvars,varName,defValue);
        },
        vgaSet: function(varName,value) {
            this.appvars[varName] = value;
        },
        vgaGet: function(varName,defValue) {
            return u.p(this.appvars,varName,defValue);
        }
    },
    
    
    /*
    * Event listener and dispatcher
    */
    events: {
        subscribers: { },
        subStack: [ ],
        
        /**
        * Objects subscribe to events by calling u.events.subscribe() with
        * the name of the event and a back reference to themselves.
        *
        */
        subscribe: function(eventName,object) {
            // First determine if we have any listeners for this
            // event at all.  If not, make up the empty object
            if( u.p(this.subscribers,eventName,null)==null) {
                this.subscribers[eventName] = { };
            }
            var subs = this.subscribers[eventName];
            
            // Assign the listener by its ID.  This lets us prevent duplication
            // if the object is confused and registers itself twice.
            //
            var id = object.id;
            if(id == '' || id==null) {
                object.id = u.uniqueId(); 
            }
            if( u.p(subs,id,null)==null ) {
                subs[id] = id;
            }
        },
        
        /**
        * An object can unsubscribe from an event.
        *
        */
        unSubscribe: function(eventName,object) {
            var id = object.id;
            if( u.p(this.subscribers[eventName],id,null)!=null ) {
                delete this.subscribers[eventName][id];
            }
        },
        
        /**
        * Two commands to remove all current subscriptions
        * after saving them to a stack, and command to restore
        * prior prescriptions from the stack
        *
        */
        suppressByPrefix: function(prefix) {
            this.subStack.push(u.clone(this.subscribers));
            for(var x in this.subscribers) {
                if (x.slice(0,prefix.length)==prefix) {
                    delete this.subscribers[x];
                }
            }
        },
        unSuppress: function() {
            this.subscribers = this.subStack.pop();
        },
        
        
        /**
        * An object that fires an event will call u.events.notify with the
        * name of the event and a single argument.  If multiple arguments are
        * required, they should be put into an array or object 
        * that the receiving objects must understand.
        *
        */
        notify: function(eventName,arguments) {
            x4.debug("in u.events.notify, eventname and arguments follow");
            x4.debug(eventName);
            x4.debug(arguments);
            // Find out if anybody is listening for this event
            var subscribers = u.p(this.subscribers,eventName,{ });
            
            this.zStop = false;
            for(var id in subscribers) {
                var subscriber = u.byId(id);
                if(subscriber==null) {
                    x4.debug("The subscriber is null!");
                    continue;
                }
                
                // First possibility is a generic nofity handler
                if(typeof(subscriber.notify)=='function') {
                    x4.debug("Dispatching to "+id+" generic NOTIFY method");
                    var x = subscriber.notify(eventName,arguments);
                    if(x) this.zStop = true;
                }
                // next possibility is a specific handler
                if(typeof(subscriber[eventName])=='function') {
                    x4.debug("Dispatching to "+id+" specific method "+eventName);
                    var x = subscriber[eventName](arguments);
                    if(x) this.zStop = true;
                }
            }
            x4.debug("Returning: =="+this.zStop+"==");
            return this.zStop;
        }
    }, 

    /*
    * Dialogs
    *
    * HACK ALERT: I could not figure out how to make sure no item
    *             had focus, so user could still tab around on
    *             controls.  So I added something to stdlib.keyPress
    *             that checks for the current dialog and returns
    *             false if there is any dialog in play.  I ain't
    *             proud of it, but it works.
    * FILES AFFECTED: androLib.js (this)
    *                 androX4.js
    * HACK ID: MODAL_KEYPRESS
    */
    dialogs: {
        id: 'u_dialogs',
        answer: null,
        json: null,
        currentDialog: false,
        
        clear: function(answer) {
            this.answer = answer;
            this.currentDialog = false;
            u.events.unSuppress();
            $('#dialogbox,#dialogoverlay').css('display','none');
        },
        
        prepare: function(type) {
            // Tell the master what we are doing, 
            // and suppress all keystrokes except ENTER and ESC
            this.currentDialog = type;

            // Suppress all events 
            u.events.suppressByPrefix('keyPress');

            // Get some basic heights and widths
            var wh = $(window).height();
            var ww = $(window).width();
            
            // Make complete assignment to the overlay
            $('#dialogoverlay')
                .css('position','absolute')
                .css('top',0)
                .css('left',0)
                .css('width' ,ww)
                .css('height',wh)
                .css('background-color','black')
                .css('opacity',0)
                .css('display','')
                .css('z-index',500);

            // Get height and width of the inner guy and center him
            var ch = $('#dialogbox').height();
            $('#dialogbox').css('width',300);
            cw = 300;
            
            // Center and otherwise prepare the box
            $('#dialogbox')
                .css('position','absolute')
                .css('top' , 300)
                .css('left', (ww - cw)/2)
                .css('opacity',0)
                .css('display','')
                .css('z-index',501)
                .addClass('dialog');
            u.byId('dialogbox').notify = function(eventName,args) {
                if(u.dialogs.currentDialog == 'alert') {
                    if(eventName == 'keyPress_Enter') {
                        u.dialogs.clear();
                        return true;
                    }
                    if(eventName == 'keyPress_Esc')  {
                        u.dialogs.clear();
                        return true;
                    }
                }
                if(u.dialogs.currentDialog == 'confirm') {
                    if(eventName == 'keyPress_Y') {
                        u.events.zStop = true;
                        u.dialogs.clear(true);
                        return true;
                    }
                    if(eventName == 'keyPress_N') {
                        u.events.zStop = true;
                        u.dialogs.clear(false);
                        return true;
                    }
                }
                return false;
            }
        },
        
        alert: function(msg) {
            this.prepare('alert');

            // Create the content for the dialog itself
            var html =
                "<h1>Message</h1>"
                +"<p>"+msg+"</p><br/>"
                +"<center>"
                +"<a href='javascript:u.dialogs.clear()'>OK</a>"
                +"</center>"
                +"<br/>"
                +"</div>";
                
            u.events.subscribe('keyPress_Enter',u.byId('dialogbox'));
            u.events.subscribe('keyPress_Esc'  ,u.byId('dialogbox'));

            u.byId('dialogbox').innerHTML=html;
            
            // Finally, display the dialog
            $('#dialogoverlay').css('opacity',0.4);
            $('#dialogbox').css(    'opacity',1);                
        },
        
        confirm: function(msg,options) {
            this.prepare('confirm');
            u.events.subscribe('keyPress_Y'  ,u.byId('dialogbox'));
            u.events.subscribe('keyPress_N'  ,u.byId('dialogbox'));
            

            // Create the content for the dialog itself
            var html =
                "<h1>Please Confirm:</h1>"
                +"<p>"+msg+"</p><br/>"
                +"<center>"
                +"<a href='javascript:u.dialogs.clear(true)'>"
                +"&nbsp;&nbsp;Yes&nbsp;&nbsp;</a>"
                +"&nbsp;&nbsp;&nbsp;"
                +"<a href='javascript:u.dialogs.clear(false)'>"
                +"&nbsp;&nbsp;No&nbsp;&nbsp;</a>"
                +"</center>"
                +"<br/>"
                +"</div>";

            u.byId('dialogbox').innerHTML=html;
            
            
            // Finally, display the dialog
            $('#dialogoverlay').css('opacity',0.4);
            $('#dialogbox').css(    'opacity',1);
            
            // For this guy we need to make sure there
            // is a request object
            if(this.json!=null) {
                this.json.abort();
            }
            else {
                var browser = navigator.appName;
                if(browser == "Microsoft Internet Explorer"){
                    this.json = new ActiveXObject("Microsoft.XMLHTTP");
                }else{
                    this.json = new XMLHttpRequest();
                }
            }
            
            // The loop sends a request to the server,
            // which sleeps for 250ms, then comes back.
            // This gives us pretty good response w/o
            // chewing up the CPU
            while(true) {
                this.answer = null;
                this.json.open('POST' , 'phpWait.php', false);
                this.json.send(null);
                
                if(this.answer!=null) break;
            }
            return this.answer;
        },
        
        pleaseWait: function(msg) {
            this.prepare('pleaseWait');
            
            // Create the content for the dialog itself
            var html =
                "<center><br/>"
                +"<img src='clib/ajax-loader.gif'>"
                +"<br/><br/>"
                +"Please Wait...<br/><br/>"
                +"</center>";

            u.byId('dialogbox').innerHTML=html;
            
            // Finally, display the dialog
            $('#dialogoverlay').css('opacity',0.4);
            $('#dialogbox').css(    'opacity',1);
        }
    }
}

/* ---------------------------------------------------- *\

   SECTION 5: Andromeda Utility Object

   Assumes the presence of Andromeda on the server-side   
   
\* ---------------------------------------------------- */
var au = {
    
    
    
}


/* ---------------------------------------------------- *\

   SECTION 6: Andromeda Code not part of au

   
\* ---------------------------------------------------- */
var aSelect = new Object();
aSelect.divWidth = 400;
aSelect.divHeight= 300;
aSelect.div      = false;
aSelect.iframe   = false;
aSelect.row      = false;
aSelect.hasFocus = false;

// Main routine called when a keystroke is hit on 
// the control that "hosts" the androSelect
function androSelect_onKeyUp(obj,strParms,e) {
    var kc = e.keyCode;
    if(e.ctrlKey || e.altKey) return;
    
    // KFD 7/11/08.
    // Prevent popup on shift, ctrl or alt.  Specifically
    // this is what alerted me to this.  If the user
    // does the following:
    //    hold down SHIFT
    //    hit TAB
    //    release TAB
    //    release SHIFT -- that's when this fires, we want
    //                   to prevent that
    var klabel = $a.label(e);
    //if(klabel=='Shift') return;
    if(klabel=='Alt')   return;
    if(klabel=='Ctrl')  return;
    
    // KFD 7/11/08.  If user has cleared the contents of
    //               the input, kill the box
    if(obj.value.trim()=='') {
        androSelect_hide();
        return;
    }

    // If TAB or ENTER, clear the box
    if(kc == 9 || kc == 13) { return true; }
    
    // If downarrow or uparrow....
    if(kc == 38 || kc == 40) {
        if(!androSelect_visible()) return;
        if(aSelect.div.firstChild.rows.length==0) return;

        if(!aSelect.row) { 
            var row = aSelect.div.firstChild.rows[0];
            var skey= objAttValue(row,'x_skey');
            androSelect_mo(row,skey);
            $('#androSelect').scrollTo(row);
            return;
        }
        
        var row = byId('as'+aSelect.row);
        if(kc==38) {
            var prev = objAttValue(row,'x_prev');
            if(prev!='') {
                var row = byId('as'+prev);
                androSelect_mo(row,prev);
                $('#androSelect').scrollTo(row);
            }
        }
        if(kc==40) {
            var next = objAttValue(row,'x_next');
            if(next!='') {
                var row = byId('as'+next);
                androSelect_mo(row,next);
                $('#androSelect').scrollTo(row);
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
    if(!aSelect.div) {
        aSelect.div = document.createElement('DIV');
        aSelect.div.style.display  = 'none';
        aSelect.div.style.width    = aSelect.divWidth + "px";
        aSelect.div.style.height   = aSelect.divHeight+ "px";
        aSelect.div.style.position = 'absolute';
        aSelect.div.style.backgroundColor = 'white';
        aSelect.div.style.overflow = 'scroll';
        aSelect.div.style.border="1px solid black";
        //aSelect.div.className = 'androSelect';
        aSelect.div.id = 'androSelect';
        document.body.appendChild(aSelect.div);
        var x = document.createElement('TABLE');
        aSelect.div.appendChild(x);
    }
    // If it is invisible, position it and then make it visible
    if(aSelect.div.style.display=='none') {
        var position = $(obj).position();
        var postop = position.top;
        var poslft = position.left;
        //var objpar = obj;
        //while((objpar = objpar.offsetParent) != null) {
        //    postop += objpar.offsetTop;
        //    poslft += objpar.offsetLeft;
        // }
        aSelect.div.style.top  = (postop + obj.offsetHeight +1) + "px";
        aSelect.div.style.left = poslft + "px";
        aSelect.div.style.display = 'block';
        
        // As part of making visible, create an onclick
        // that will trap the event target and lose focus
        // if not the input object or the
        //addEventListener(document   ,'click',androSelect_documentClick);
    }
    
    // Tell it the current control it is working for
    aSelect.control = obj;
    aSelect.row = false;

    // KFD 7/21/08, add matches if present
    var matchurl = '' 
    var matchcols = $a.p(obj,'xMatches','');
    if(matchcols!='') {
        // WARNING!  x4 Assumed!
        var acols = matchcols.split(',');
        for(var cidx in acols) {
            var colname = acols[cidx];
            var colval  = $('[xcolumnid='+colname+']')[0].value.trim();
            matchurl+='&match_'+colname+"="+encodeURIComponent(colval);
        }
    }
    
    // Make up the URL and send the command
    var url = '?'+strParms+matchurl
        +'&gpv=2&gp_letters='+obj.value.replace(" ","+");         
    andrax(url,androSelect_handler);
}

function androSelect_handler() {
    // do default action
    handleResponse();
    
    if(aSelect.div.firstChild) {
        var table = aSelect.div.firstChild;
        if(table.rows.length > 0) { 
            table.rows[0].onmouseover();
        }
    }    
}

function androSelect_onKeyDown(e) {
    var kc = e.keyCode;

    // If TAB or ENTER, clear the box
    if(kc == 9 || kc == 13) { 
        if(!androSelect_visible()) return true;
        
        if(aSelect.div.firstChild.rows.length==0) {
            androSelect_hide();
           return true;
        }
        
        if(aSelect.row) {
            var row = byId('as'+aSelect.row);
            var pk  = objAttValue(row,'x_value');
            aSelect.control.value = pk;
            u.events.notify('assigned_'+aSelect.control.id,pk);
        }
        androSelect_hide();
        return true;
    }
}


// Make the div go away.  Actually choosing a value
// is done elsewhere
function androSelect_hide() {
    if(aSelect.div) {
        aSelect.div.innerHTML = ''
        aSelect.div.style.display = 'none';
    }
}

// Make the div go away.  Actually choosing a value
// is done elsewhere
function androSelect_onBlur() {
    if(aSelect.hasFocus) {
        return;
    } 
    else {
        androSelect_hide();
    }
}


function androSelect_visible() {
    if(aSelect.div == false) return false;
    if(aSelect.div.style.display=='none') return false;
    
    return true;
}

// Main purpose is to see if user clicked anywhere except
// on current control or the div.  If they did, hide it
// w/o making a choice.
function androSelect_documentClick(e) {
    androSelect_hide();
    return false;
}

// User is rolling over a row
function androSelect_mo(tr,skey) {
    if(byId('as'+aSelect.row)) {
        byId('as'+aSelect.row).className = '';
    }
    aSelect.row = skey;
    tr.className = 'lightgray';
    aSelect.hasFocus = true;   
}
// User clicked on a row
function androSelect_click(value,suppress_focus) {
    aSelect.control.value = value;
    u.events.notify('assigned_'+aSelect.control.id,value);
    androSelect_hide();
    if(suppress_focus==null) {
        aSelect.control.focus();
    }
    return false;
}


/* ---------------------------------------------------- *\

   SECTION 7: Old general utility object
   which had dependencies on Andromeda and was
   not truly general or universal.
   
   Will be phased out as things are moved into
   uu and au.
   
\* ---------------------------------------------------- */

/*
 * This is the General Purpose Library of most basic 
 * Andromeda javascript functions.  It has a long 
 * complex name: $a
 *            
 */

window.a = window.$a = {
    /*
     *  For data returned from a json call
     */
     data: { 
        dd: {} 
     },
     returnto: '',
    
    /*
    * global variable system, you can "stick" variables
    * here and "grab" them later.
    */
    bb: {
        vars: { },
        stick: function(varName,value) {
            this.vars[varName] = value;
        },
        grab: function(varName,defValue) {
            return $a.p(this.vars,varName,defValue);
        }
    },
    
    /*
     * Window open.  Used to allow javascript to 
     * subsequently close a window, which you can't
     * do with <a target="_blank" href="....
     */
    openWindow: function(url) {
        $a.window = window.open(url);
    },
     
    /*
     * Dialogs.  Placeholders to use JQuery plugins
     *
     */
    dialogs: {
        alert: function(msg) {
            alert(msg);
        },
        confirm: function(msg) {
            return confirm(msg);
        },
        alertx: function($msg, $title) {
           if ( $title == null ) {
               $title = 'Alert';
           }
           
           // Center the modal dialog horizontally
           var $winwid = $(window).width();
           var $boxwid = 300;
           if($winwid < 300) { $boxwid = $winwid; }
           var $boxleft= parseInt(($winwid - $boxwid)/2);
           $(".jqmWindow").css("width",$boxwid+"px").css("left",$boxleft);
           
           $('#jqmTitle').html($title);
           $('#jqmMessage').html('<p>' + $msg  
               +'</p><br/><p align="center">'
               +'<input type="button" name="OK" value="OK" class="jqmClose" '
               +'   style="font-weight:bold;text-align:center;'
               +'cursor:pointer;" /></p>');
           $('#jqmModal').jqm({modal:true,overlay:75,onHide:jqModalClose,onShow:jqModalOpen}).jqmShow();
        },
        confirm: function(msg) {
            return confirm(msg);
        }
    }, 
    
    /*
     * Sub object for doing things on a detail form
     * like fetch values, recalculate and so forth
     *
     */
    forms: {
        fetch: function(table,column,value,obj,inp) {
            // KFD 7/30/08, detect if this value matches
            //              a "pre" value that was sent in,
            //              and if so, fire no matter what
            //              otherwise fire only on change
            var go = false;
            var column = u.p(inp,'xColumnId');
            if(typeof(a.data.init[column])!='undefined') {
                delete a.data.init[column];
                go = true;
            }
            if(! go) {
                var valold = u.p(inp,'xValue');
                if(value.trim() == valold.trim()) return;
            }
            inp.xValue = value;
            
            $a.json.init('x4Action','fetch');
            $a.json.addParm('x4Page',table);
            $a.json.addParm('column',column);
            $a.json.addParm('value',value);
            if($a.json.execute()) {
                $a.json.process();
                for(var idx in $a.data.fetch) {
                    var retval = $a.data.fetch[idx];
                    var inputs = $(obj).find("[xcolumnid="+idx+"]");
                    if(inputs.length > 0) {
                        inputs[0].value = retval;
                    }
                }
            }
        }
    },

    /**
      * Sub object for making calls to the server to retrieve
      * stuff in JSON format
      *
      */
    json: {
        callString: '',
        jdata:      { },
        data:       { dd: {} },
        requests:   { },
        parms:      { },
        x4Page:     '',
        x4Action:   '',
        explicitParms: '',
        hadErrors: false,
        init: function(name,value) {
            this.x4Page     = '';
            this.x4Action   = '';
            this.callString = '';
            this.parms      = { };
            this.explicitParms= '';
            if(name!=null) {
                this.addParm(name,value);
            }
        },
        addParm: function(name,value) {
            this.parms[name] = value;
            if(name=='x4Page')   this.x4Page = value;
            if(name=='x4Action') this.x4Action = value;
        },
        makeString: function() {
            if(this.explicitParms!='') {
                return this.explicitParms;
            }
            var list = [ ];
            for(var x in this.parms) {
                list[list.length] = x + "=" +encodeURIComponent(this.parms[x]);
            }
            return list.join('&');
        },
        //addValue: function(name,value) {
        //    if(this.callString!='') this.callString+="&";
        //    this.callString += 'x4c_' + name + '=' + encodeURIComponent(value);
        //},
        
        /*
         * Add the value of all inputs to the json request, only
         * if not empty.
         *
         */
        inputs: function(obj,direct) {
            if(direct==null) direct=false;
            if(obj==null) {
                obj = $a.byId('x4Top');
            }
            $(obj).find(':input').each( function() {
                    if(direct) 
                        var id = 'x4c_'+u.p(this,'xColumnId');
                    else
                        var id = this.id;
                        
                    
                    if(this.type=='checkbox') {
                        if(this.checked) {
                            $a.json.addParm(id,'Y');
                        }
                        else {
                            $a.json.addParm(id,'N');
                        }
                    }
                    else {
                        if(this.value!='') {
                            $a.json.addParm(id,this.value);
                        }
                    }
            });
        },
        
        /**
        * Serialize an array or an object for sending back
        *
        */
        serialize: function(prefix,obj) {
            for(var x in obj) {
                if(typeof(obj[x])=='object') {
                    this.serialize(prefix+'['+x+']',obj[x]);
                }
                else {
                    this.addParm(prefix+'['+x+']',obj[x]);
                }
            }
        },
        
        /*
         * Take what was supposed to be a JSON call and execute
         * it as if it were a regular hyperlink
         *
         */
        windowLocation: function() {
            var entireGet = 'index.php?'+this.makeString()
            window.location = entireGet;
        },
        newWindow: function() {
            var entireGet = 'index.php?'+this.makeString()+'&x4Return=exit';
            $a.openWindow(entireGet);
        },

        /**
        * Make asychronous call
        *
        */
        executeAsync: function() {
            this.execute(true,true);
        },
        
        /**
          * Make a synchronous call to the server, expecting
          * to receive a JSON array of stuff back.
          *
          */
        execute: function(autoProcess,async) {
            this.hadErrors = false;
            if(async==null) async = false;
            if(autoProcess==null) autoProcess=false;
            
            // Create an object
            var browser = navigator.appName;
            if(browser == "Microsoft Internet Explorer"){
                var http = new ActiveXObject("Microsoft.XMLHTTP");
            }
            else {
                var http = new XMLHttpRequest();
            }
            // KFD 7/8/08, When the user is clicking on
            //             search boxes, they can click faster
            //             than we can get answers, so if
            //             we notice we are running an action
            //             that is already in progress, we
            //             cancel the earlier action.
            var key = this.x4Page + this.x4Action;
            if( typeof(this.requests[key])!='undefined') {
                this.requests[key].abort();
            }
            this.requests[key] = http;
            
            // If async, we have to do it a little differently
            if(async) {
                http.onreadystatechange = function() {
                    if(this.readyState!=4) return;
                    $a.json.processPre(this,key,false);
                    $a.json.process();
                }
            }
            
            // Execute the call
            var entireGet = 'index.php?json=1&'+this.makeString();
            http.open('POST' , entireGet, async);
            http.send(null);

            // An asynchronous call now exits, but a
            // synchronous call continues            
            if (async) return;
            else return this.processPre(http,key,autoProcess);
            
        },
        
        processPre: function(http,key,autoProcess) {
            // Attempt to evaluate the JSON
            try {
                eval('this.jdata = '+http.responseText);
            }
            catch(e) { 
                $a.dialogs.alert("Could not process server response!");
                x4.debug(http.responseText);
                return false;
            }
            
            // KFD 7/8/08, additional housekeeping, throw away
            //             references to the object  
            delete this.requests[key];
            delete http;

            // If there were server errors, report those
            if(this.jdata.error.length>0) {
                this.hadErrors = true;
                $a.dialogs.alert(this.jdata.error.join("\n\n"));
                return false;
            }
            if(this.jdata.notice.length>0) {
                $a.dialogs.alert(this.jdata.notice.join("\n\n"));
            }
            
            if(autoProcess) {
                this.process();
            }
            
            return true;
        },
        
        process: function(divMain) {
            for(var x in this.jdata.html) {
                if(x=='*MAIN*') {
                    $('#'+divMain).html(this.jdata.html[x]);
                }
                else {
                    var obj = u.byId(x);
                    if(obj) {
                        if (obj.tagName =='INPUT') {
                            obj.value = this.jdata.html[x];
                        }
                        else {
                            obj.innerHTML = this.jdata.html[x];
                        }
                    }
                }
            }
            
            // Execute any script that was provided
            for(var x in this.jdata.script) {
                eval(this.jdata.script[x]); 
            }
            
            return true;
        }
    },

    byId: function(id) {
        return document.getElementById(id );
    },
    value: function(id) {
        return $a.byId(id).value;
    },

    // Retrieve an object's property, creating it if not
    // there and assigning it the default    
    aProp: function(obj,propname,defvalue) {
        if(typeof(obj)!='object') {
            return defvalue;
        }
        
        // First try, maybe it is a direct property
        if(typeof(obj[propname])!='undefined') {
            return obj[propname];
        }
        // Second try, maybe it is an attribute
        if(obj.getAttribute) {
            if(obj.getAttribute(propname)!=null) {
                return obj.getAttribute(propname);
            }
        }
        // Give up, return the defvalue
        return defvalue;
    },
    p: function(obj,propname,defvalue) {
        return this.aProp(obj,propname,defvalue);
    },
    
    /*
     * Create a tab loop 
     */
    tabLoopInit: function(jqo) {
        if( $(jqo).find(":input:not([@readonly])").length == 0) return;
        
        // Assign first to shift back to the last
        $(jqo).find(":input:not([@readonly]):first")[0].tabPrev =
            '#' + $(jqo).find(":input:not([@readonly]):last")[0].id;
        $(jqo).find(":input:not([@readonly]):first").keypress( function(event) {
            var label = $a.keyLabel(event);
            if(label=='ShiftTab') {
                $(event.currentTarget.tabPrev).focus();
                event.preventDefault();
            }
        });
    
        // Assign last to shift to the first
        $(jqo).find(":input:not([@readonly]):last")[0].tabNext =
            '#' + $(jqo).find(":input:not([@readonly]):first")[0].id;
        $(jqo).find(":input:not([@readonly]):last").keypress( function(event) {
            var label = $a.keyLabel(event);
            // A tab key on the last element will loop around    
            if(label=='Tab') {
                $(event.currentTarget.tabNext).focus();
                event.preventDefault();
            }
        });
    
        // TAB LOOP: Put focus on first non-readonly element
        $(jqo).find(':input:not([@readonly]):first').focus();      
    },

    keyLabel: function(e) {
        var x = e.keyCode;
        
        var x4Keys = { };
        x4Keys['8']  = 'BackSpace';
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
    
    charLetter: function(charcode) {
        var letters = 
            [ 'a', 'b', 'c', 'd', 'e', 'f', 'g',
              'h', 'i', 'j', 'k', 'l', 'm', 'n',
              'o', 'p', 'q', 'r', 's', 't', 'u',
              'v', 'w', 'x', 'y', 'z' ];
        if(charcode >= 65 && charcode <= 90) {
            return letters[charcode - 65];
        }
    },
    charNumber: function(charcode) {
        var numbers = [ '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ];
        if(charcode >= 48 && charcode <= 57) {
            return numbers[charcode - 48];
        }
    },
    
    /**
      * A comprehensive function that returns letters, numbers, labels
      * and so forth, like CtrlPageDown, ShiftR etc.
      *
      */
    label: function(e) {
        var x = e.keyCode;
        
        var x4Keys = { };
        x4Keys['8']  = 'BackSpace';
        x4Keys['9']  = 'Tab';
        x4Keys['13'] = 'Enter';
        //x4Keys['16'] = '';   // actually Shift, but prefix will take care of it
        //x4Keys['17'] = '';   // actually Ctrl,  but prefix will take care of it
        //x4Keys['18'] = '';   // actually Alt,   but prefix will take care of it
        // KFD Added these three lines, so that keyup/keydown are tracked
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
        
        return retval;
    }
}


/* ---------------------------------------------------- *\
   SECTION 8: SUSPECTED DEAD CODE  
   
   We want to move everything here
   to androLibDeprecated.js
\* ---------------------------------------------------- */
var androIE = false;
if(    navigator.userAgent.indexOf('MSIE') >=0 
    && navigator.userAgent.indexOf('Opera')<0) {
    androIE = true;
}


function u3SS(stringparms) {
   ob('u3string').value=stringparms;
   formSubmit();
}

