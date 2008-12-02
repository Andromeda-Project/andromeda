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
   once or twice and is now dead.  
   
   Generally, anything that is documented with ROBODOC
   blocks is part of the permanent API, and anything
   not commented is deprecated and should not be
   used.
      
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

/****M* Javascript-API/Date-Extensions
*
* NAME
*   Date-Extensions
*
* FUNCTION
*   Javascript lacks a handful of useful date functions, 
*   such as returning the day of the week as a string.
*   These extensions to the Date object provide those
*   facilities.
*
******
*/

/****m* Date-Extensions/getDow
*
* NAME
*   Date.getDow
*
* FUNCTION
*   The Javascript method Date.getDow returns the day of
*   the week as a string.
*
*   If logical true is passed for the first parameter,
*   a 3-digit abbreviation is returned, using 'Thu' for
*   Thursday.
*
* INPUTS
*   boolean - If true, returns a 3-digit abbreviation
*
*
* SOURCE
*
*/
Date.prototype.getDow = function(makeItShort) {
    var days = ['Sunday','Monday','Tuesday','Wednesday'
        ,'Thursday','Friday','Saturday'
    ];
    var retval = days[this.getDay()];
    if(makeItShort) {
        return retval.slice(0,3);
    }
    return retval;
}
/******/


/****M* Javascript-API/String-Extensions
*
* NAME
*   String-Extensions
*
* FUNCTION
*   Javascript lacks a handful of useful string functions, 
*   such as padding and trimming, which we have added
*   to our standard library.
*
******
*/


/****m* String-Extensions/trim
*
* NAME
*   String.trim
*
* FUNCTION
*   The Javascript function trim removes leading and trailing
*   spaces from a string.
*
* EXAMPLE
*   Example usage:
*     var x = '  abc  ';
*     var y = x.trim();  // returns 'abc'
*
* SOURCE
*/
String.prototype.trim = function() {
	return this.replace(/^\s+|\s+$/g,"");
}
/******/

/****m* String-Extensions/ltrim
*
* NAME
*   String.ltrim
*
* FUNCTION
*   The Javascript function ltrim removes leading spaces
*   from a string (spaces on the left side).
*
* EXAMPLE
*   Example usage:
*     var x = '  abc  ';
*     var y = x.ltrim();  // returns 'abc  '
*
* SOURCE
*/
String.prototype.ltrim = function() {
	return this.replace(/^\s+/,"");
}
/******/

/****m* String-Extensions/rtrim
*
* NAME
*   String.rtrim
*
* FUNCTION
*   The Javascript function rtrim removes trailing spaces
*   from a string (spaces on the right side).
*
* EXAMPLE
*   Example usage:
*     var x = '  abc  ';
*     var y = x.ltrim();  // returns '  abc'
*
* SOURCE
*/
String.prototype.rtrim = function() {
	return this.replace(/\s+$/,"");
}
/******/


/****m* String-Extensions/pad
*
* NAME
*   String.pad
*
* FUNCTION
*   The Javascript function pad pads out a string to a given
*   length on either left or right with any character.
*
* INPUTS
*   int - the size of the resulting string
*
*   string - the string that should be added.  You can provide
*   a string of more than one character.  The resulting string
*   will be clipped to ensure it is returned at the requested
*   size.
*
*   character - either an 'L' or left padding or an 'R' for
*   right padding.
*
* EXAMPLE
*   Example usage:
*     var x = 'abc';
*     var y = x.pad(6,'0','L');  // returns '000abc';
*
* SOURCE
*/
String.prototype.pad = function(size,character,where) {
    var val = this;
    while(val.length < size) {
        if(where == 'L') 
            val = character + val;
        else
            val += character;
        if(val.length > size) {
            val = val.slice(0,size);
        }
    }
    return val;
}
/******/


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

/****m* String-Extensions/repeat
*
* NAME
*   String.repeat
*
* FUNCTION
*   The Javascript function repeat returns the input string
*   repeated any number of times
*
* INPUTS
*   * int - the number of times to repeat the string.
*
*
* EXAMPLE
*   Example usage:
*     alert( 'abc'.repeat(3));
*
* SOURCE
*/
String.prototype.repeat = function(count) {
    if(count==null) count = 1;
    retval = '';
    for(var x = 1; x<= count; x++) {
        retval+= this;
    }
    return retval;
}
/******/

/****m* String-Extensions/htmlDisplay
*
* NAME
*   String.htmlDisplay
*
* FUNCTION
*   The Javascript function htmlDisplay converts the HTML
*   characters ampersand '&amp;', less than '&lt;' and
*   greater-than '&gt;' to their HTML entities equivalents
*   for safe display.
*
*   The reverse function is htmlEdit.
*
* SOURCE
*/
String.prototype.htmlDisplay = function() {
    return this.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
/******/

/****m* String-Extensions/htmlEdit
*
* NAME
*   String.htmlEdit
*
* FUNCTION
*   The Javascript function htmlEdit removes HTML entities 
*   from a string and replaces them with literal characters,
*   suitable for editing in an input.  The characters replaced
*   are ampersand '&amp;', less-than '&lt;', greater-than '&gt;'
*   and non-breaking space '&amp;nbsp;'.
*
* SOURCE
*/
String.prototype.htmlEdit = function() {
    return this.replace(/&amp;/g,'&')
        .replace(/&lt;/g,'<')
        .replace(/&gt;/g,'>')
        .replace(/&nbsp;/g,' ');
}
/******/

/* ---------------------------------------------------- *\

   FIX BRAIN-DAMAGED INTERNET EXPLORER  
   
\* ---------------------------------------------------- */

if(!Array.indexOf){
    Array.prototype.indexOf = function(obj){
        for(var i=0; i<this.length; i++){
            if(this[i]==obj){
                return i;
            }
        }
        return -1;
    }
}


/* ---------------------------------------------------- *\

   SECTION 3: jQuery plugins and additions  
   
\* ---------------------------------------------------- */
jQuery.extend(
  jQuery.expr[ ":" ], 
  { reallyvisible : "!(jQuery(a).is(':hidden') || jQuery(a).parents(':hidden').length)" }
);
 
/*
* Plugin to track focus
*
* KFD 9/2/08, make conditional, this is safer
*/
if(typeof(jQuery)!='undefined') {
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
}

var jqModalClose=function(hash) { hash.w.fadeOut(500, function() { hash.o.fadeOut(250);}); };
var jqModalOpen=function(hash) { hash.w.fadeIn(500);hash.o.fadeIn(500);};

/****O* Javascript-API/u
* NAME
*   u (Javascript General Utility Object)
*
* FUNCTION
*   Contains a variety of shortcut methods such as byId,
*   which makes code smaller and tighter.
*
*   Also contains several subobjects with specific areas
*   of utility.
*
*   The Javascript u object is present on all Andromeda
*   pages, you can access it from Javascript code on any
*   custom page you write.
*
* PORTABILITY
*   Requires no other Andromeda files or libraries, is
*   completely standalone.
*
******
*/
var u = {
    /****v* u/debugFlag
    *
    * NAME
    *    debugFlag
    *
    * FUNCTION 
    *    If this flag is false, messages sent to u.debug will be
    *    ignored and that function will return false.
    *
    *    If this flag is true, messages sent to u.debug will be 
    *    sent to console.log, if Firebug is installed and
    *    enabled.
    *
    * SEE ALSO
    *    u.debug
    *
    ******
    */
    debugFlag: false,
    debugStack: [ ],
    
    /****m* u/debugPush
    *
    * NAME
    *   u/debugPush
    *
    * FUNCTION
    *   The javascript method debugPush allows you to turn on 
    *   debugging and then later turn it back to its original
    *   setting (either on or off).  This is useful when you 
    *   do not want to turn debugging on globally, but just want
    *   to turn it on or off in a particular routine.
    *
    * INPUTS
    *   * boolean - new debugging setting (default: true)
    *
    * EXAMPLE
    *   A javascript example would be:
    *
    *      u.debugPush(true); // force debugging on
    *      u.debug("I want this message no matter what.");
    *      u.debugPop();      // return to prior setting
    *
    * SOURCE
    */
    debugPush: function(value) {
        if(value==null) value = true;
        this.debugStack.push(this.debugFlag);
        this.debugFlag = value;
    },
    /******/
    
    /****m* u/debugPop
    *
    * NAME
    *   u/debugPop
    *
    * FUNCTION
    *   The javascript method debugPop returns the u.debugFlag
    *   setting to whatever it was before the most recent
    *   call to u.debugPush.
    *
    *   If debugPop is called too many times (more than debugPush)
    *   was called, a call is made to u.error() but no javascript
    *   error actually occurs -- program execution will not fail.
    *
    * INPUTS
    *   none.
    *
    * EXAMPLE
    *   A javascript example would be:
    *
    *      u.debugPush(true); // force debugging on
    *      u.debug("I want this message no matter what.");
    *      u.debugPop();      // return to prior setting
    *
    * SOURCE
    */
    debugPop: function() {
        if(this.debugStack.length==0) {
            u.error("Call to debugPop with no prior call to debugPush");
        }
        else {
            this.debugFlag = this.debugStack.pop();
        }
    },
    /******/
    
    
    /****m* u/p
    * NAME
    *   p (Safe Object Property Access)
    *
    * FUNCTION
    *   The Javascript function u.p retrieves the 
    *   named property of an HTML or
    *   Javascript object, and returns a default value if
    *   the property does not exist.
    *
    *   This function resolves the answer by checking
    *   for the following in this order:
    *   *  An HTML attribute such as <td colspan="99">
    *   *  Any non-HTML attribute that may be have previously
    *      assigned in a command like "obj.zSpecialProperty = 5"
    *   *  A non-standard HTML attribute such
    *      as <td xColumnId="customer">
    *
    *   If all else fales, the parameter defValue is returned.
    *
    * INPUTS
    *   obj - The object whose property you need to access
    *   
    *   propName - The property whose value you are retrieving
    *   
    *   defValue - The value to return if the object has no 
    *   HTML attribute, no javascript-assigned property,
    *   and no HTML attribute accessible through
    *   HTMLElement.getAttribute().
    *
    * RESULT
    *   mixed - returns the value of the property, which can
    *   be any valid Javscript type.
    *
    * SOURCE
    */
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
    /******/
    
    /****m* u/byId
    * NAME
    *   u.byId 
    *
    * FUNCTION
    *   The Javascript method u.byId is a
    *   shortcut for javascript document.getElementById()
    *
    * RESULT
    *   HTMLElement - returns the same result as 
    *   document.getElementById
    *
    * SOURCE 
    */
    byId: function(id) {
        return document.getElementById(id );
    },
    /******/
    
    logIndent: 0,
    /****m* u/log
    * NAME
    *   u.log
    *
    * FUNCTION
    *   Sends a message to console.log
    *   if it exists.  If firebug is not installed or enabled
    *   then this function has no effect.  Compare to u.debug.
    *
    * RESULT
    *   message - If firebug is active and the message can
    *   be sent to the console, then the message itself is
    *   returned.  Otherwise returns false.
    *
    * SOURCE
    */
    log: function(message,logIndent) {
        if(logIndent==-1) this.logIndent--;
        message = ' '.repeat(this.logIndent*3)+message;
        if(logIndent ==1) this.logIndent++; 
        if(typeof(console)!='undefined') {
            console.log(message);
            return message;
        }
        return false;
    },
    /******/

    /****m* u/debug
    * NAME
    *   u.debug
    *
    * FUNCTION
    *   This Javascript method sends a message to console.log()
    *   if firebug is installed
    *   and the property u.debugFlag is true.  Compare
    *   to u.log which does not consider the value of u.debugFlag.
    *
    *   You can put commands to u.debug into your Javascript code,
    *   and then control whether or not they go to the console
    *   by setting and resetting the u.debugFlag.
    *
    * RESULT
    *   message - If firebug is present and enabled, and the flag
    *   u.debugFlag is true, returns the message back to the calling
    *   program.  Otherwise returns false.
    *
    * SOURCE
    */
    debug: function(message,logIndent) {
        if(this.debugFlag) {
            return this.log(message,logIndent);
        }
        return false;
    },
    /******/

    /****m* u/error
    * NAME
    *   u.error
    *
    * FUNCTION
    *   This Javascript method sends an error message to 
    *   console.log if firebug is installed.
    *
    *
    * RESULT
    *   Returns the original message if console.log is defined
    *   (that is, if Firebug is installed), otherwise returns false.
    *
    * SOURCE
    */
    error: function(message,logIndent) {
        if(typeof(console)!='undefined') {
            console.error(message);
            return message;
        }
        return false;
    },
    /******/
    
    /****m* u/uniqueId
    * NAME
    *   u.uniqueId
    *
    * FUNCTION
    *   Generates a random number between 1 and 1,000,000 that is
    *   not being used as the ID for any DOM object.  Useful for
    *   generating unique and content-free Id's for DOM objects.
    *
    * EXAMPLE
    *   Example usage would be:
    *
    *     var x = document.createElement('div');
    *     x.id = u.uniqueId();
    *
    * RESULT
    *   id - returns the id
    *
    * SOURCE
    */
    uniqueId: function() {
        var retval = 0;
        while( $('#'+retval).length > 1  || retval==0) {
            var retval=Math.floor(Math.random()*1000000);
        }
        return retval;
    },
    /******/
    
    /** NODOC **/
    /** DELETE **/    
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
    
    /****O* u/bb
    * NAME
    *   u.bb
    *
    * FUNCTION
    *   "Bulletin Board" object that lets you "stick" values on it
    *   and "grab" them from elsewhere.
    *
    *   u.bb methods should be used instead of global variables
    *   because they allow you to avoid collisions with framework
    *   globals.  The framework uses the methods u.bb.vgfGet
    *   and u.bb.vgfSet, and your application should use u.bb.vgaGet
    *   and u.bb.vgaSet.
    *
    * EXAMPLE:
    *   Example usage:
    *      u.bb.vgaSet('myvar','value');
    *      var myvar = u.bb.vgaGet('myvar','acceptableDefault');
    *
    ******
    */
    bb: {
        /****v* bb/fwvars
        * NAME
        *   u.bb.fwvars
        *
        * FUNCTION
        *   Global bulletin board framework variables.  Not intended
        *   for direct access, manipulate at your own risk!
        ******
        */
        fwvars: { },
        /****v* bb/appvars
        * NAME
        *   u.bb.appvars
        *
        * FUNCTION
        *   Global bulletin board application variables.  Not intended
        *   for direct access, manipulate at your own risk!
        ******
        */
        appvars: { },
        /****m* bb/vgfSet
        * NAME
        *   u.bb.vgfSet
        *
        * FUNCTION
        *   Used by the Andromeda framework to save global variables
        *   for later use.  Application code should never use this,
        *   or you risk overwriting framework values and disrupting
        *   normal performance.
        *
        * INPUTS
        *   varName - The name of your variable
        *   varValue   - The value to store
        *
        * SOURCE
        */
        vgfSet: function(varName,value) {
            this.fwvars[varName] = value;
            //if(typeof(console)!='undefined') {
            //    console.log("vgfSet",varName,value);
            //}
        },
        /******/
        
        /****m* bb/vgfGet
        * NAME
        *   u.bb.vgfGet
        *
        * FUNCTION
        *   Retrieves a variable saved by the framework.
        *
        *   Unlike vfgSet, which application code should never call,
        *   it may be appropriate from time-to-time to call this
        *   function to find out what the framework is up to.
        *
        * INPUTS
        *   varName - the variable you wish to retrieve
        *   defValue - the value to return if the variable does not exist.
        *
        * SOURCE
        */
        vgfGet: function(varName,defValue) {
            var retval = u.p(this.fwvars,varName,defValue);
            //if(typeof(console)!='undefined') {
            //    console.log("vgfGet",varName,retval,defValue);
            //}
            return retval;
        },
        /******/

        /****m* bb/vgaSet
        * NAME
        *   u.bb.vgaSet
        *
        * FUNCTION
        *   Sets a global variable.  Globals saved with this method
        *   will not collide with any framework globals.
        *
        * INPUTS
        *   varName - the variable you wish to save
        *   varValue - the value to save
        *
        * SOURCE
        */
        vgaSet: function(varName,value) {
            this.appvars[varName] = value;
        },
        /******/
        
        
        /****m* bb/vgaGet
        * NAME
        *   u.bb.vgaGet
        *
        * FUNCTION
        *   Retrieves a global variable, or the default value
        *   if the global has not been set.
        *
        * INPUTS
        *   varName - the variable you wish to save
        *   defValue - the value to return if the global does
        *              not exist.
        *
        * SOURCE
        */
        vgaGet: function(varName,defValue) {
            return u.p(this.appvars,varName,defValue);
        }
        /******/
    },
    
    
    /****O* u/events
    *
    * NAME
    *   u.events
    *
    * FUNCTION
    *   The javascript object u.events implements the classic
    *   event listener and dispatcher pattern.
    *
    *   Objects can subscribe to events by name.  Other 
    *   objects can notify the events object when an event
    *   occurs, and it will in turn notify all of the subscribers.
    *
    * PORTABILITY
    *   The u.events object and its methods expect other u
    *   methods to be available, but do not have any other
    *   dependencies.
    *
    ******
    */
    events: {
        /****iv* events/subscribers
        *
        * NAME
        *   u.events.subscribers
        *
        * FUNCTION
        *   The javascript object u.events.subscribers is an object
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
        *   not intended for direct manipulation.  Use u.events.subscribe,
        *   u.events.unSubscribe, u.events.suppressByPrefix and 
        *   u.events.unSuppress to control event subscriptions.
        * 
        ******
        */
        subscribers: { },

        /****v* events/subStack
        *
        * NAME
        *   u.events.subStack
        *
        * FUNCTION
        *   The javascript object u.events.subStack is used by
        *   u.events as a stack. See u.events.suppressByPrefix
        *   and u.events.unSuppress.
        * 
        ******
        */
        subStack: [ ],
        
        /****m* events/subscribe
        *
        * NAME
        *   u.events.subscribe
        *
        * FUNCTION
        *   The Javascript method u.events.subscribe allows an
        *   object to subscribe to a named event.  That object will
        *   then be notified whenever the event fires.  See the
        *   method u.events.notify for more information on how
        *   the notification is handled.
        *
        * INPUTS
        *   eventName - Any string.  There is no validtion of the 
        *   eventName, so misspellings will result in your object 
        *   not being notified.
        *   object - The object itself.
        *   
        * NOTES
        *   The Andromeda framework fires the following events:
        *   * newRow_<table_name>, whenever any object has added a row
        *     to a back end table it fires newRow_<table_name>, passing
        *     the new row as the argument.
        *   * changeRow_<table_name>, whenever any object has modified
        *     a row it fires changeRow_<table_name>, passing the new
        *     version of the row as the argument.
        *   * deleteRow_<table_name>, whenever any object deletes a 
        *     row from the database, it fires deleteRow_<table_name>,
        *     passing in the skey of the deleted row.
        *   * keyPress, On Extended_Desktop pages all document level
        *     keystrokes are captured and handed first to the u.events
        *     object for dispatching.  Each is dispatched twice, the
        *     first time it is fired as a 'keyPress' event where the 
        *     x4.keyCode value is the argument.
        *   * keyPress_<keyCode>, On Extended Desktop pages all document
        *     level keystrokes are captured and handed first to the
        *     u.events object for dispatching.  Each is dispatched
        *     twice, the second time it is fired as a 'keyPress_<keyCode>'
        *     event with no arguments, such as 'keyPress_Enter' or
        *     'keyPress_F6'.
        *
        * RESULT
        *   No return value
        *
        * SEE ALSO
        *   u.events.notify
        *
        * SOURCE
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
                id = object.id;
            }
            if( u.p(subs,id,null)==null ) {
                subs[id] = id;
            }
        },
        /******/
        
        /****m* events/unSubscribe
        *
        * NAME
        *   u.events.unSubscribe
        *
        * FUNCTION
        *   The Javascript method u.events.unSubscribe allows an
        *   object to unscribe to an event that it has previously
        *   subscribed to with u.events.subscribe.
        *
        * SOURCE
        */
        unSubscribe: function(eventName,object) {
            var id = object.id;
            if( u.p(this.subscribers[eventName],id,null)!=null ) {
                delete this.subscribers[eventName][id];
            }
        },
        /******/

        /*m* events/getSubscribers
        *
        * NAME
        *   u.events.getSubscribers
        *
        * FUNCTION
        *   The Javascript method u.events.getSubscribes returns
        *   an array of subscribers to a particular event.
        *
        * NOTES
        *   Use this method to register "owners" or objects that
        *   have particular responsibility for a task.  For instance
        *   a grid that displays "patients" might register itself
        *   as a subscriber to "grid_patients".  Then another object
        *   that needs to know the owner can call
        *   u.events.getSubscribers('grid_patients') and find the
        *   object.
        *
        * RETURNS
        *   An array of zero or more object id's. 
        *
        * SOURCE
        */
        getSubscribers: function(eventName) {
            // This code works even if there are no subscribers
            var retval = [ ];
            var subscribers = u.p(this.subscribers,eventName,{ });
            for(var id in subscribers) {
                retval.push(id);
            }
            return retval;
        },
        /******/

        
        /****m* events/suppressByPrefix
        *
        * NAME
        *   u.events.suppressByPrefix
        *
        * FUNCTION
        *   The Javacript method u.event.suppressByPrefix suppresses
        *   event notification for one or more previously subscribed
        *   events.
        *
        *   The Framework uses this method when it creates a modal
        *   dialog to suppress all keyPress events, afterwhich it
        *   re-subscribes  to whatever keyPress events are appropriate
        *   to the particular dialog.
        *
        *   Events are suppressed by giving the event name or the
        *   beginning of a name.  To suppress all keyPress events
        *   call this with the argument 'keyPress', to suppress only
        *   one event you may call with 'keyPress_Enter'.  
        *
        * INPUTS
        *   String - the event name or prefix.
        *
        * RESULT
        *   Number - returns the count of events that were suppressed.
        *
        * SEE ALSO
        *   u.events.unSuppress
        *   u.events.subscribe (for a list of Framework events)
        *
        * SOURCE
        */
        suppressByPrefix: function(prefix) {
            this.subStack.push(u.clone(this.subscribers));
            var count = 0;
            for(var x in this.subscribers) {
                if (x.slice(0,prefix.length)==prefix) {
                    count++;
                    delete this.subscribers[x];
                }
            }
            return count;
        },
        /******/

        /****m* events/unSuppress
        *
        * NAME
        *   u.events.unSuppress
        *
        * FUNCTION
        *   This Javascript method reverses the effect of a previous
        *   call to u.events.suppressByPrefix, so that previously
        *   suppressed events are once again active.
        *
        *   If u.events.suppresByPrefix is called more than once,
        *   the u.events.unSuppress method always reverses the most
        *   recent call.  It is not possible to selectively unsuppress
        *   events or to unsuppress them in a different order than
        *   they were suppressed.
        *
        * INPUTS
        *   none
        *
        * RESULT
        *   true - Always returns true.
        *
        * SEE ALSO
        *   u.events.unSuppress
        *
        * SOURCE
        */
        unSuppress: function() {
            this.subscribers = this.subStack.pop();
            return true;
        },
        /******/
        
        
        /****m* events/notify
        *
        * NAME
        *   u.events.notify
        *
        * FUNCTION
        *   The Javascript method u.events.notify will notify all
        *   objects that have subscribed to an event by calling
        *   u.events.subscribe.
        *
        *   See below for examples on how to code up object listener
        *   methods that will receive the events.
        *
        *   If you want your application objects to notify other objects
        *   of its own events, call this function.
        *
        * INPUTS
        *   eventName, the name of the event
        *   mixed, a single argument.  If multiple arguments are required,
        *   pass an object that contains property:value assignments.
        *
        * RESULTS
        *   boolen - returns true if any listening object has reported
        *   that further handling of the event should stop.  This is
        *   particularly used by the framework to stop propagation of
        *   keystroke events that have been handled by some listener.
        *
        * SEE ALSO
        *   u.events.subscribe, for a list of Framework Events.
        *
        * EXAMPLE
        *    There are two ways to subscribe to events.  If your
        *    object has its own notify method, this method will 
        *    receive all events that the object is subscribed to,
        *    and it must dispatch them according to eventName.
        *
        *    If your object has a method that has the same name
        *    as the event, that method will be invoked.
        *
        *    If you mix both of these approaches you must be careful
        *    to handle each event in only one way, or it will fire
        *    twice.
        *
        *        var myObject = {
        *            notify: function(eventName,arguments) {
        *               // this function receives all events
        *               // and must dispatch them
        *               if(eventName=='keyPress_F10') {
        *                   this.doSomething();
        *               }
        *            },
        * 
        *            keyPress_Enter: function(arguments) {
        *                // do something when Enter is hit
        *                if(whatever) {
        *                    // tell the dispatcher the event
        *                    // was not handled
        *                    return false;
        *                }
        *                else {
        *                    // tell the dispatcher we handled the
        *                    // event.  Other subscribers will still
        *                    // get it, but for keystrokes the
        *                    // default behavior is suppressed.
        *                    return true;
        *               }
        *            }
        *        }
        *
        ******
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

    /****O* u/dialogs
    *
    * NAME
    *   u.dialogs
    *
    * FUNCTION
    *   The two Javascript dialogs u.dialogs.alert and
    *   u.dialogs.confirm replace the Javascript native functions
    *   alert() and confirm().
    *
    *   The third dialog, u.dialogs.pleaseWait, puts up a 
    *   suitable notice if your application must do something that
    *   will take more than 1-2 seconds.
    *
    *   They are all fully modal, respond to appropriate keystrokes
    *   like 'Y', 'N', 'Enter' and 'Esc', and maintain the same
    *   style as the rest of the template.
    *
    * PORTABILITY
    *   The u.dialogs object expects your HTML to contain two
    *   invisible (display:none) divs.  One is called "dialogoverlay"
    *   and the other is called "dialogbox".  These two divs are 
    *   provided by default on Andromeda templates.  If you make
    *   your own template and include androHTMLFoot.php at the bottom
    *   then your templates will also have these divs.
    *
    *   Making a page fully modal is difficult, because if an INPUT
    *   has focus it will be possible for the user to use the keyboard
    *   to navigate around.  Therefore the code in x4 checks
    *   the u.dialogs.currentDialog property, and disallow all activity
    *   if that property is not false.  If you make your own 
    *   custom pages that are not Extended Desktop pages, you must have
    *   your input's onkeyPress methods also check this property.
    *
    *   If this object is used outside of Andromeda, you must have
    *   the file phpWait.php in your public web root, otherwise the
    *   u.dialogs.confirm function will not work.
    *
    ******
    */
    dialogs: {
        /** NO DOC **/
        id: 'u_dialogs',
        answer: null,
        json: null,
        
        /****v* dialogs/currentDialog
        *
        * NAME
        *    u.events.currentDialog   
        *
        * FUNCTION
        *    This Javascript property will hold any of the value of:
        *    * false, no dialog is active
        *    * alert, an alert dialog is active
        *    * confirm, a confirm dialog is active
        *    * pleaseWait, a "Please Wait" box is being displayed
        *
        ******
        */
        currentDialog: false,
        
        /****v* dialogs/clear
        *
        * NAME
        *   u.events.clear
        *
        * FUNCTION
        *   The Javascript Method u.events.clear
        *   clears the current modal dialog.  
        *
        *   The two dialogs u.dialogs.alert and u.dialogs.confirm are
        *   cleared by user action.  But the u.dialogs.pleaseWait 
        *   dialog will remain on the screen until your application
        *   Javascript code clears it by calling this method.
        *
        * INPUTS
        *   ignore - This method accepts a paremeter that is useful
        *   only to the framework when managing a confirm dialog.
        *
        * SOURCE
        */
        clear: function(answer) {
            this.answer = answer;
            this.currentDialog = false;
            u.events.unSuppress();
            $('#dialogbox,#dialogoverlay').css('display','none');
        },
        /******/
        
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
        
        /****m* dialogs/alert
        *
        * NAME
        *   u.dialogs.alert
        *
        * FUNCTION
        *   The Javascript method u.dialogs.alert replaces the native
        *   Javascript alert() function with one that is stylistically
        *   consistent with the rest of the application.
        *
        *   Unlike Javascript's native alert() function, execution 
        *   continues after you call this function.  The user must
        *   clear it by clicking the OK button, hitting Enter, or
        *   hitting Esc.
        *
        *   When this alert is active, all keyboard events are
        *   suppressed except Enter and Esc.
        *
        * EXAMPLE
        *   Here is a usage example:
        *
        *       u.dialogs.alert("New data has been saved");
        *       // maybe do some other stuff while 
        *       // waiting for the user
        *       u.events.notify('myEventName',objParms);
        *
        ******
        */
        alert: function(msg) {
            alert(msg);
            return;
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
        
        /****m* dialogs/confirm
        *
        * NAME
        *   u.dialogs.confirm
        *
        * FUNCTION
        *   The Javascript method u.dialogs.confirm replaces the native
        *   Javascript confirm() function with one that is stylistically
        *   consistent with the rest of the application.
        *
        *   Like Javascript's native confirm() function, execution 
        *   *stops* until the user answers the question.  This makes
        *   coding far easier because you do not have to code
        *   anonymous callback functions.
        *
        *   When this alert is active, all keyboard events are
        *   suppressed except 'Y' and 'N'.
        *
        * EXAMPLE
        *   Here is a usage example:
        *
        *       if(u.dialogs.confirm("Do you really want to delete?")) {
        *           // code to delete
        *       }
        *       else {
        *           u.events.debug("user chose not to delete");
        *       }
        *
        * PORTABILITY
        *    Javascript does not natively support an elegant way to
        *    pause execution.  For instance, it does not have a 
        *    "sleep" function that would allow a low-CPU idefinite
        *    loop to be executing while waiting for user input.
        *
        *    We could solve this by throwing caution to the wind and
        *    doing an indefinite loop anyway, which checks over and over
        *    to see if the user has responded, but this spikes CPU usage
        *    and is very bad form.
        *
        *    The technique used by u.dialogs.confirm is unusual, but it
        *    has the benefit of being extremely low on CPU power and
        *    extremely low on network bandwidth.  The approach contains
        *    an indefinite loop that makes a call to the program
        *    phpWait.php, which does a sleep for 1/4 second and returns.
        *    Even at four calls per second, the overall CPU and network
        *    bandwidth is practically zero.
        *
        *    Therefore, u.dialogs.confirm has a dependency that the
        *    php file phpWait.php be present in the web server's public
        *    root.  This is handled automatically by Andromeda, but you
        *    must provide such a file if you use this object in 
        *    a non-Andromeda application.
        *
        ******
        */
        confirm: function(msg,options) {
            return confirm(msg);
            
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
        
        /****m* dialogs/pleaseWait
        *
        * NAME
        *   u.dialogs.pleaseWait
        *
        * FUNCTION
        *   The Javascript method u.dialogs.pleaseWait is not,
        *   strictly speaking, a dialog, because it does not require
        *   any user feedback, and in fact does not even allow it.
        *
        *   When you call u.dialogs.pleaseWait, a modal box pops up
        *   that is stylistically consistent with the overall template
        *   and which has an animated gif and the message "Please Wait".
        *
        *   Use this method when you are executing a long-running
        *   (greater than 2-3 seconds) process and you must let the
        *   user know the program is working on something.
        *
        *   The user cannot clear this display.  You must clear it
        *   yourself when work has been completed by calling
        *   u.dialogs.clear().
        *
        *  EXAMPLE
        *    Here is a usage example:
        *
        *         u.dialogs.pleaseWait();
        *         for(var x in rowsToSave()) {
        *            // some actions to save to server
        *         }
        *         u.dialogs.clear();
        *  
        ******
        */
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
    },
    
    /* KFD 11/26/08
       EXPERIMENTAL
       Put here because there is already one in ua, where it
       really does not belong.
       Originally used by x6inputs.keydown to figure things out
    */
    metaKeys: {
        8: 'BackSpace',
        9: 'Tab',
        13: 'Enter',
        16: 'Shift',
        17: 'Ctrl',
        18: 'Alt',
        20: 'CapsLock',
        27: 'Esc',
        33: 'PageUp',
        34: 'PageDown',
        35: 'End',
        36: 'Home',
        37: 'LeftArrow',
        38: 'UpArrow',
        39: 'RightArrow',
        40: 'DownArrow',
        45: 'Insert',
        46: 'Delete',
        112: 'F1' ,
        113: 'F2' ,
        114: 'F3' ,
        115: 'F4' ,
        116: 'F5' ,
        117: 'F6' ,
        118: 'F7' ,
        119: 'F8' ,
        120: 'F9' ,
        121: 'F10',
        122: 'F11',
        123: 'F12'
    },
    keyLabel: function(e) {
        var x = e.keyCode || e.charCode;
        
        x4Keys = this.metaKeys;
    
        // If they hit one of the control keys, check for
        // Shift, Ctrl, or Alt
        var retval = '';
        if(typeof(x4Keys[x])!='undefined') {
            retval = x4Keys[x];
            if(e.ctrlKey)  retval = 'Ctrl'  + retval;
            if(e.altKey)   retval = 'Alt'   + retval;
            if(e.shiftKey) retval = 'Shift' + retval;
            return retval;
        }
        
        // If letters we look at shift key and return
        // upper or lower case
        if(x >= 65 && x <= 90) {
            if(e.shiftKey) {
                var letters = 
                    [ 'A', 'B', 'C', 'D', 'E', 'F', 'G',
                      'H', 'I', 'J', 'K', 'L', 'M', 'N',
                      'O', 'P', 'Q', 'R', 'S', 'T', 'U',
                      'V', 'W', 'X', 'Y', 'Z' ];
            }
            else {
                var letters = 
                    [ 'a', 'b', 'c', 'd', 'e', 'f', 'g',
                      'h', 'i', 'j', 'k', 'l', 'm', 'n',
                      'o', 'p', 'q', 'r', 's', 't', 'u',
                      'v', 'w', 'x', 'y', 'z' ];
            }
            var retval = letters[x - 65];
        }
        
        // Numbers or the corresponding codes go here
        if(x >= 48 && x <= 57) {
            if(e.shiftKey) {
                var numbers = [ ')','!','@','#','$','%','^','&','*','(' ];
            }
            else {
                var numbers = [ '0','1','2','3','4','5','6','7','8','9' ];
            }
            var retval = numbers[x - 48];
        }
        if(retval!='') {
            if(e.ctrlKey)  retval = 'Ctrl'  + retval;
            if(e.altKey)   retval = 'Alt'   + retval;
            if(e.shiftKey) retval = 'Shift' + retval;
            return retval;            
        }
        
        var lastChance = {
            192: '`',
            109: '-',
            61:  '=',
            219: '[',
            221: ']',
            220: '\\',
            188: ',',
            190: '.',
            191: '/',
            59:  ';',
            222: "'"
        }
        if(typeof(lastChance[x])!='undefined') {
            if(e.shiftKey) {
                var lastChance = {
                    192: '~',
                    109: '_',
                    61:  '+',
                    219: '{',
                    221: '}',
                    220: '|',
                    188: '<',
                    190: '>',
                    191: '?',
                    59:  ':',
                    222: '"'
                }
            }
            return lastChance[x];
        }
        // otherwise put on any prefixes and return
        console.log(x);
        return retval;
    },
    
    keyIsNumeric: function(e) {
        var keyLabel = this.keyLabel(e);
        var numbers = [ '0','1','2','3','4','5','6','7','8','9' ];
        return numbers.indexOf(keyLabel)>=0;
    },
    
    keyIsMeta: function(e) {
        var code = e.keyCode || e.charCode;
        return typeof(this.metaKeys[code])!='undefined';
    }
    
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
        var position = $(obj).offset();
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
        aSelect.hasFocus = false;
        
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
            aSelect.hasFocus = false;
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
function androSelect_moout(tr,skey) {
    aSelect.hasFocus = false;   
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


/****O* Javascript-API/ua
*
* NAME
*   ua
*
* FUNCTION
*   The Javascript ua object provides Andromeda-specific 
*   utilities.  This object is used to communicate with the
*   web server, and it also contains parsed results that
*   have been received from the server.
*
*   Compare ua to the Javascript u object, which
*   contains general purpose utilities that do not depend
*   upon or expect other Andromeda objects to be present.
*
*   The Javascript ua object is present on all HTML sent to
*   the browser and can be used on any custom page you write.
*
* PORTABILITY
*   The Javascript ua object is not meant to be used outside
*   of Andromeda.  Specifically it is hardcoded to expect
*   an Andromeda web-server.  
*
*   The ua object also depends on the u object, and it expects
*   jQuery to be available.
******
*/
window.a = window.ua = window.$a = {
    /****v* ua/data
    *
    * NAME
    *   ua.data
    *
    * FUNCTION
    *   The javascript object ua.data contains data that has been
    *   sent to the browser by the web server.  Such data can
    *   be sent in a normal HTML page or in a JSON call.
    *
    *   The data that is sent by PHP is JSON encoded, so it can 
    *   conceivably contain numeric arrays, associative arrays, objects,
    *   and even objects with javascript code.
    *
    * NOTES
    *   The Andromeda framework uses this mechanism in its own code,
    *   so your application code should not send data with the
    *   following names:
    *
    *   * row - used by the Framework for sending back individual rows
    *   * dd.* - used by the Framework for sending data dictionaries
    *   * fetch - used to return FETCH values when the user changes
    *     a foreign key during editing.
    *   * init
    *   * x4Mode - reserved for b/w compatibility
    *   * x4Focus - reserved for b/w compatiblity
    *   * returnto - specifies where the page should go when the user
    *     exits.  No value means go back to menu, "exit" means close
    *     the tab.
    *
    * EXAMPLE
    *   If you have PHP code that must return a set of rows
    *   the the browser, you can use this command:
    *
    *       <?php
    *       $sql = "Select * from states";
    *       $rows = sql_allRows($sql);
    *       x4Data('states',$rows);
    *       ?>
    *
    *   And then in script you will find this data here:
    *
    *       <script>
    *       var states = ua.data.states;
    *       for(var idx in states) {
    *           .....
    *       }
    *       </script>
    *******
    */
    data: { 
       dd: {} 
    },
     
    /** NO DOC **/
    /** DEPRECATED **/
    returnto: '',
    
    /** NO DOC **/
    /** DEPRECATED **/
    /** Was moved to u, because it is independent of Andromeda **/
    bb: {
        vars: { },
        stick: function(varName,value) {
            this.vars[varName] = value;
        },
        grab: function(varName,defValue) {
            return $a.p(this.vars,varName,defValue);
        }
    },
    
    /** NO DOC **/
    /** DEPRECATED **/
    openWindow: function(url) {
        $a.window = window.open(url);
    },
     
    /** NO DOC **/
    /** DEPRECATED **/
    /** Was moved to u, because it is independent of Andromeda **/
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
            // KFD 9/7/08   If there is an androSelect visible,
            //              don't do this.
            if(aSelect) {
                if(aSelect.hasFocus && aSelect.div.style.display != 'none') {
                    return;
                }
            }
            // KFD 9/7/08   Hack.  Make sure he goes away if we
            //              are fetching.
            androSelect_hide();
            
            
            // KFD 7/30/08, detect if this value matches
            //              a "pre" value that was sent in,
            //              and if so, fire no matter what
            //              otherwise fire only on change
            var go = false;
            var column = u.p(inp,'xColumnId');
            if(typeof(a.data.init)!='undefined') {
                if(typeof(a.data.init[column])!='undefined') {
                    delete a.data.init[column];
                    go = true;
                }
            }
            if(! go) {
                var valold = u.p(inp,'xValue','');
                if(value.trim() == valold.trim()) return;
            }
            
            // MAJOR BUG: KFD 8/22/08.  This line is evil.  It causes
            //       the browser to think the value was not changed, and
            //       so the browser does not try to save it, and you 
            //       can figure out the awful calls you get after that.
            //inp.xValue = value;
            
            $a.json.init('x4Action','fetch');
            $a.json.reportErrors = false;
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

    /****M* Javascript-API/AJAX
    *
    * NAME
    *   AJAX
    *
    * FUNCTION
    *   In Andromeda, all Ajax requests are handled by the
    *   ua.json object.  We do not use the term AJAX because
    *   it is not a correct technical description.  We use
    *   term "JSON", as in 'make a JSON call to the server'.
    *
    *   Specifically, Andromeda PHP pages return JSON data
    *   instead of XML (hence no "X" in AJA"X"), and many calls
    *   are actually synchronous (hence no "A"synchronous
    *   in AJAX).
    *
    *   For a complete reference, see $a.json.
    ******
    */
    
    /****O* ua/json
    *
    * NAME
    *   ua.json
    *
    * FUNCTION
    *   The Javascript object ua.json is used to send any request
    *   to the web server, either for a new complete page or for
    *   data and HTML fragments.
    *   Examples include requesting a single row from a table,
    *   requesting search results, fetching HTML fragments, or
    *   popping up a new window.
    *
    *   Andromeda uses the term JSON instead of AJAX because 
    *   the term AJAX does not describe how Andromeda works.
    *   Specifically, Andromeda PHP pages return JSON data
    *   instead of XML (hence no "X" in AJA"X"), and many calls
    *   are actually synchronous (hence no "A" in "A"JAX).
    *
    *   The ua.json object is always present on all pages, and
    *   you can use it in Javascript code on any custom page.
    *
    * NOTES
    *   Andromeda handles all of the values returned in the
    *   request automatically.  On custom pages you do not have
    *   to code a response handler because Andromeda handles
    *   the response for you.
    *
    *   The PHP code that handles a JSON request sends data back
    *   by using the routines x4Debug, x4Data, x4HTML, x4Script,
    *   x4Error.  While the complete PHP-API documentation on
    *   those functions will give you most of what you need, we
    *   must note here how the returned data is handled:
    *   * x4Debug - ignored, but you can examine the results in
    *     firebug.
    *   * x4Error - any errors sent back by this function are reported
    *     to the user and ua.json.execute returns false.
    *   * x4HTML - calls to this function in PHP code provide an 
    *     element Id and a fragment of HTML.  The HTML replaces the
    *     innerHTML of the specific item.
    *   * x4Data - calls to this function in PHP code provide a name
    *     and some data value (including arrays and objects).  The
    *     result can be examined when the call completes in ua.data.<name>.
    *   * x4Script - provides script that should execute on the browser
    *     when the call returns.
    *
    * EXAMPLE
    *
    *   The basic usage of ua.json is to initialize a call 
    *   with ua.json.init, and then to add parameters with
    *   ua.json.addParm, and finally to execute and process the
    *   call with ua.json.execute and ua.json.process.
    *
    *   There are also special-purpose methods like ua.json.inputs
    *   that will take all of the inputs inside of an object and
    *   add them to the request.
    *
    *   You can also use the function ua.json.windowLocation to
    *   execute the call as a new page request, and ua.json.newWindow
    *   to execute the call as a new page request in a tab.
    *  
    *      <script>
    *      // Initialize the call
    *      ua.json.init('x4Page','myCustomPage'); 
    *      // Name the server-side PHP method to call
    *      ua.json.addParm('x4Action','getSomething'); 
    *      // Add some parms
    *      ua.json.addParm('parm1','value');
    *      ua.json.addParm('parm2','value');
    *      // Execute and process in one step.  Note that this
    *      // is synchronous, there is no need for a callback
    *      // function.
    *      ua.json.execute(true);
    *     
    *      for(var x in $a.data.returnedStuff) {
    *        ....
    *      }
    *      </script>
    *
    *  This call requires an Extended-Desktop page to be defined
    *  in PHP that will service the request.  A super-simple example
    *  is here, more information is provided in the
    *  Extended-Desktop documentation.
    *
    *      <?php
    *      # This is file application/x4MyCustomPage.php
    *      class x4MyCustomPage extends androX4 {
    *          # this function handles the call given above
    *          function getSomething() {
    *               $parm1 = gp('parm1');
    *               $parm2 = gp('parm2');
    *               $sql = "Select blah blah blah";
    *               $rows = SQL_AllRows($sql);
    *               x4Data('returnedStuff',$rows
    *          }
    *      }
    *      ?>
    *
    *   Sometimes you make a call that returns replacement HTML
    *   for a single object.  In this case your PHP code supplies
    *   the HTML by calling x4HTML with the value of '*MAIN*' for
    *   the first parameter, as in x4HTML('*MAIN*',$html);
    *   Such a call is handled this way in script:
    *
    *      <script>
    *      ua.json.init('x4Page','myCustomPage');
    *
    *      // We need the conditional in case the server returns
    *      // an error and we should not replace the html
    *      if(ua.json.execute()) {
    *         ua.json.process('nameofItemToReplace');
    *      }
    *      </script>
    *
    ******
    */
    json: {
        callString: '',
        http:       false,
        active:     false,
        jdata:      { },
        data:       { dd: {} },
        requests:   { },
        parms:      { },
        reportErrors: true,
        x4Page:     '',
        x4Action:   '',
        explicitParms: '',
        hadErrors: false,
        
        /****m* json/init
        *
        * NAME
        *   ua.json.init
        *
        * FUNCTION
        *   The Javascript method ua.json.init initiates a new 
        *   JSON request.
        *
        *   Optionally you can pass two inputs and eliminate one
        *   call to ua.json.addParm.
        *
        * INPUTS
        *   string - if provided, a parameter name
        *   mixed - if provided, the value for the parameter
        *
        * EXAMPLE
        *   Here are two examples for initiating a JSON request
        *
        *      <script>
        *      // The short way
        *      ua.json.init('x4Page','myCustomPage');
        * 
        *      // Passing w/o parameters requires at least one
        *      // call to ua.json.addParm.
        *      ua.json.init();
        *      ua.json.addParm('x4Page','myCustomPage');
        *      </script>
        *
        * SOURCE
        */
        init: function(name,value) {
            this.x4Page     = '';
            this.x4Action   = '';
            this.callString = '';
            this.parms      = { };
            this.reportErrors=true;
            this.explicitParms= '';
            if(name!=null) {
                this.addParm(name,value);
            }
        },
        /******/

        /****m* json/addParm
        *
        * NAME
        *   ua.json.addParm
        *
        * FUNCTION
        *   The Javascript method ua.json.addParm adds one parameter
        *   to a JSON call previously initiated with ua.json.init.
        *
        * INPUTS
        *   string - required, a parameter name
        *   mixed - required, the value for the parameter
        *
        * EXAMPLE
        *   Here are two examples for initiating a JSON request
        *
        *      <script>
        *      ua.json.init();
        *      // Name the server-side page to call
        *      ua.json.addParm('x4Page','myCustomPage');
        *      // Name the server-side method to call
        *      ua.json.addParm('x4Action','fetchSomething');
        *      </script>
        *
        * SOURCE
        */
        addParm: function(name,value) {
            this.parms[name] = value;
            if(name=='x4Page')   this.x4Page = value;
            if(name=='x4Action') this.x4Action = value;
        },
        /******/
        
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
        
        /****m* json/inputs
        *
        * NAME
        *   ua.json.inputs
        *
        * FUNCTION
        *   The Javascript method ua.json.inputs adds inputs to
        *   a JSON call previously initiated with ua.json.init.
        *
        *   This method accepts an object as its parameter, and
        *   will add every input that is a child (at any level)
        *   of that object.
        *
        *   This method uses the "id" property of the input to
        *   name the parameter, not the "name" property.  Andromeda
        *   makes no use of the "name" property.
        *
        *   This method is equivalent to use ua.json.addParm
        *   for each of the desired inputs.
        *
        *   Checkboxes receive special treatment.  If the box is 
        *   checked a value of 'Y' is sent, and if the box is not
        *   checked a value of 'N' is sent.
        *
        *   The name of each parameter is normally the Id of the
        *   input.  If the inputs were generated by Andromeda
        *   on an Extended-Desktop page, they will have the names
        *   'x4inp_<tableId>_<columnId>.  
        *
        * INPUTS
        *   object - optional, the object to recurse.  You must
        *   pass the object itself, not its Id.  If no object is
        *   passed the Extended-Desktop top-level object x4Top
        *   is used, which means you get every input on the page,
        *   whether or not it is visible or
        *
        *   direct - a special flag that says to name the parameters
        *   'x4c_<columnId>'.  This is required when you are sending
        *   Direct-Database-Access calls.
        *
        *
        * SOURCE
        */
        inputs: function(obj,direct) {
            if(direct==null) direct=false;
            if(obj==null) {
                if(u.byId('x4Top')!=null) {
                    obj = u.byId('x4Top');
                }
                else {
                    obj = $('.x6main')[0];
                }
            }
            if(typeof(obj)=='string') {
                var jqObjects = $(obj);
            }
            else {
                var jqObjects = $(obj).find(":input");
            }
            jqObjects.each( function() {
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
        /******/
        
        /****m* json/serialize
        *
        * NAME
        *   ua.json.serialize
        *
        * FUNCTION
        *   The Javascript method ua.json.serialize takes a
        *   Javascript Object or Array and serializes it and
        *   adds the values to a JSON request previously
        *   initialized with ua.json.init.
        *
        *   This method accepts an object as its parameter.
        *
        *   When you call this function, the parameters sent
        *   back take the form of an associative array.
        *
        * INPUTS
        *   prefix - The base name of the parameter
        *
        *   object - the object to serialize.
        *
        * EXAMPLE
        *   Consider the following object that is serialized
        *
        *      <script>
        *      var x = {
        *         parm1: [ 1, 2, 3],
        *         parm2: 'hello',
        *         parm3: {
        *             x: 5,
        *             y: 10,
        *         }
        *      ua.json.init('x4Page','myCustomPage');
        *      ua.json.addParm('x4Action','serialHandler');
        *      ua.json.serialize('example',x);
        *      <script>
        *
        *   Then on the server, you can grab the "example" parameter
        *   and you will get the following associative array:
        *
        *      <?php
        *      # this is file x4myCustomPage.php
        *      class x4myCustomPage extends androX4 {
        * 
        *          # this handles the 'x4Action' specified above
        *          function serialHandler() {
        *              $example = gp('example');
        *            
        *              # ...the following code shows how 
        *              #    the values that are in x4
        *              $example['parm1'][0] = 1;
        *              $example['parm1'][1] = 2;
        *              $example['parm1'][2] = 3;
        *              $example['parm2'] = 'hello';
        *              $example['parm3']['x'] = 5;
        *              $example['parm3']['y'] = 10;
        *          }
        *      }
        *      ?>
        *
        * SOURCE
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
        /******/
        
        /****m* json/windowLocation
        *
        * NAME
        *   ua.json.windowLocation
        *
        * FUNCTION
        *   The Javascript method ua.json.windowLocation takes a
        *   JSON request and executes it as a page request.
        *
        * EXAMPLE
        *   The following example loads a new page
        *
        *      <script>
        *      ua.json.init('x4Page','calendar');
        *      ua.json.windowLocation();
        *      </script>
        *
        * SOURCE
        */
        windowLocation: function() {
            var entireGet = 'index.php?'+this.makeString()
            window.location = entireGet;
        },
        /******/
        
        /****m* json/newWindow
        *
        * NAME
        *   ua.json.newWindow
        *
        * FUNCTION
        *   The Javascript method ua.json.newWindow takes a
        *   JSON request and executes it as a page request, popping
        *   the result up in a new tab or window.
        *
        *   When the user exits the resulting tab or window, it
        *   will close.  
        *
        * EXAMPLE
        *   The following example loads a new page
        *
        *      <script>
        *      ua.json.init('x4Page','calendar');
        *      ua.json.newWindow();
        *      </script>
        *
        * SOURCE
        */
        newWindow: function() {
            var entireGet = 'index.php?'+this.makeString()+'&x4Return=exit';
            $a.openWindow(entireGet);
        },
        /******/

        /****m* json/executeAsync
        *
        * NAME
        *   ua.json.executeAsync
        *
        * FUNCTION
        *   By default Andromeda sends JSON requests synchronously,
        *   which is more appropriate for business database applications
        *   than asynchronous requests.
        *
        *   There are however some times when you do not want the user
        *   to wait, and so you can make asynchronous calls. 
        *
        *   Andromeda does not make use of response handlers, see the
        *   above section on ua.json for more details.
        *
        * SOURCE
        */
        executeAsync: function() {
            this.execute(true,true);
        },
        /******/
        
        /****m* json/execute
        *
        * NAME
        *   ua.json.execute
        *
        * FUNCTION
        *   The Javascript method ua.json.execute sends a request to
        *   the server that has been initialized with ua.json.init
        *   and has received parameters with any of ua.json.addParm,
        *   ua.json.inputs and ua.json.serialize.
        *
        *   In normal usage, you call this routine and check for
        *   a return value of true.  If the routine returns true
        *   you call ua.json.process to process the returned
        *   results.
        *
        * RESULTS
        *   This routine returns true if the server reports no 
        *   errors.
        *
        *   If the server reports errors, they are displayed to the
        *   user using u.dialogs.alert, and this routine returns
        *   false.
        *
        *******
        */
        execute: function(autoProcess,async) {
            this.hadErrors = false;
            if(async==null) async = false;
            if(autoProcess==null) autoProcess=false;
            
            // Create an object
            var browser = navigator.appName;
            if(browser == "Microsoft Internet Explorer"){
                // KFD 11/24
                var http = new ActiveXObject("Microsoft.XMLHTTP");
            }
            else {
                // KFD 11/24
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
            // KFD 11/24, did nothing yet for async
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

            // KFD 11/24A
            this.active = false;
            
            
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
                if(u.byId('x6Log')) {
                    u.byId('x6Log').innerHTML = http.responseText;
                    u.byId('x6Log').style.display='block';
                }
                return false;
            }
            
            // KFD 7/8/08, additional housekeeping, throw away
            //             references to the object  
            delete this.requests[key];
            delete http;

            // If there were server errors, report those
            if(this.jdata.error.length>0 && this.reportErrors) {
                this.hadErrors = true;
                $a.dialogs.alert(this.jdata.error.join("\n\n"));
                return false;
            }
            if(this.jdata.notice.length>0 && this.reportErrors) {
                $a.dialogs.alert(this.jdata.notice.join("\n\n"));
            }
            
            if(autoProcess) {
                this.process();
            }
            
            return true;
        },
        
        /****m* json/process
        *
        * NAME
        *   ua.json.process
        *
        * FUNCTION
        *   The Javascript method ua.json.execute is the final
        *   step in sending and receiving JSON requests.  This
        *   routine does the following:
        *   * Any HTML sent back via PHP x4HTML replaces the 
        *     innerHTML of the named items (actually item Ids are used).
        *   * Any script sent back via PHP x4Script is executed.
        *   * Any data sent back via PHP x4Data is placed into
        *     ua.data.
        *
        * EXAMPLE
        *   This example shows how you can retrieve table data and
        *   then process it:
        *
        *      <script>
        *      ua.json.init('x4Page','myCustomPage');
        *      ua.json.addParm('x4Action','getStates');
        *      // ua.json.execute will return false on errors
        *      if(ua.json.execute()) {
        *         // ua.json.process puts everything in its place...
        *         ua.json.process();
        *         // ...so that we can handle the returned data
        *         for (var idx in ua.data.states) {
        *            // do something
        *         }
        *      }
        *      <script>
        *
        *   This code requires the following PHP code on the server:
        *
        *      <?php
        *      # this is file application/x4myCustomPage.php
        *      class x4myCustomPage extends androX4 {
        *          function getStates() {
        *              $states = SQL("Select * from states");
        *              x4Data('states',$states);
        *          }
        *      }
        *
        *******
        */
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

    /** NO DOC **/
    /** DEPRECATED **/
    /** Moved into u **/
    byId: function(id) {
        return document.getElementById(id );
    },
    value: function(id) {
        return $a.byId(id).value;
    },

    /** NO DOC **/
    /** DEPRECATED **/
    /** Moved into u **/
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
    /** NO DOC **/
    /** DEPRECATED **/
    /** Moved into u **/
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
        // KFD 11/26/08, nifty fix to recognize letters etc
        var x = e.keyCode || e.charCode;
        
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
    
        // If they hit one of the control keys, check for
        // Shift, Ctrl, or Alt
        var retval = '';
        if(typeof(x4Keys[x])!='undefined') {
            retval = x4Keys[x];
            if(e.ctrlKey)  retval = 'Ctrl'  + retval;
            // KFD 8/4/08, this never worked, removed.
            if(e.altKey)   retval = 'Alt'   + retval;
            if(e.shiftKey) retval = 'Shift' + retval;
        }
        else {
            var letters = 
                [ 'A', 'B', 'C', 'D', 'E', 'F', 'G',
                  'H', 'I', 'J', 'K', 'L', 'M', 'N',
                  'O', 'P', 'Q', 'R', 'S', 'T', 'U',
                  'V', 'W', 'X', 'Y', 'Z' ];
            var numbers = [ '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ];
            if(x >= 65 && x <= 90) {
                retval = letters[x - 65];
            }
            else if(x >= 97 && x <= 121) {
                retval = letters[x - 97];
            }
            else if(x >= 48 && x <= 57) {
                retval = numbers[x - 48];
            }
        }
    
        // otherwise put on any prefixes and return
        
        return retval;
    }
}

function showHide( id ) {
    var tag = '#' + id;
    $(tag).slideToggle();
}


/* ----------------------------------------------------- *\
   EXPERIMENTAL, json constructor
\* ----------------------------------------------------- */
function androJSON(parm,value) {
    
    this.callString = '';
    this.http       =  false,
    this.active     =false,
    this.jdata      ={ },
    this.data       ={ dd: {} },
    this.requests   ={ },
    this.parms      ={ },
    this.reportErrors= true,
    this.x4Page     ='',
    this.x4Action   ='',
    this.explicitParms= '',
    this.hadErrors= false,

    /****m* json/addParm
    *
    * NAME
    *   ua.json.addParm
    *
    * FUNCTION
    *   The Javascript method ua.json.addParm adds one parameter
    *   to a JSON call previously initiated with ua.json.init.
    *
    * INPUTS
    *   string - required, a parameter name
    *   mixed - required, the value for the parameter
    *
    * EXAMPLE
    *   Here are two examples for initiating a JSON request
    *
    *      <script>
    *      ua.json.init();
    *      // Name the server-side page to call
    *      ua.json.addParm('x4Page','myCustomPage');
    *      // Name the server-side method to call
    *      ua.json.addParm('x4Action','fetchSomething');
    *      </script>
    *
    * SOURCE
    */
    this.addParm = function(name,value) {
        this.parms[name] = value;
        if(name=='x4Page')   this.x4Page = value;
        if(name=='x4Action') this.x4Action = value;
    }
    /******/
    
    // Original init code
    this.x4Page     = '';
    this.x4Action   = '';
    this.callString = '';
    this.parms      = { };
    this.reportErrors=true;
    this.explicitParms= '';
    if(parm!=null) {
        this.addParm(parm,value);
    }
    // Create an object
    var browser = navigator.appName;
    if(browser == "Microsoft Internet Explorer"){
        // KFD 11/24
        this.http = new ActiveXObject("Microsoft.XMLHTTP");
    }
    else {
        // KFD 11/24
        this.http = new XMLHttpRequest();
    }
    
    this.makeString = function() {
        if(this.explicitParms!='') {
            return this.explicitParms;
        }
        var list = [ ];
        for(var x in this.parms) {
            list[list.length] = x + "=" +encodeURIComponent(this.parms[x]);
        }
        return list.join('&');
    }
    //addValue: function(name,value) {
    //    if(this.callString!='') this.callString+="&";
    //    this.callString += 'x4c_' + name + '=' + encodeURIComponent(value);
    //},
    
    /****m* json/inputs
    *
    * NAME
    *   ua.json.inputs
    *
    * FUNCTION
    *   The Javascript method ua.json.inputs adds inputs to
    *   a JSON call previously initiated with ua.json.init.
    *
    *   This method accepts an object as its parameter, and
    *   will add every input that is a child (at any level)
    *   of that object.
    *
    *   This method uses the "id" property of the input to
    *   name the parameter, not the "name" property.  Andromeda
    *   makes no use of the "name" property.
    *
    *   This method is equivalent to use ua.json.addParm
    *   for each of the desired inputs.
    *
    *   Checkboxes receive special treatment.  If the box is 
    *   checked a value of 'Y' is sent, and if the box is not
    *   checked a value of 'N' is sent.
    *
    *   The name of each parameter is normally the Id of the
    *   input.  If the inputs were generated by Andromeda
    *   on an Extended-Desktop page, they will have the names
    *   'x4inp_<tableId>_<columnId>.  
    *
    * INPUTS
    *   object - optional, the object to recurse.  You must
    *   pass the object itself, not its Id.  If no object is
    *   passed the Extended-Desktop top-level object x4Top
    *   is used, which means you get every input on the page,
    *   whether or not it is visible or
    *
    *   direct - a special flag that says to name the parameters
    *   'x4c_<columnId>'.  This is required when you are sending
    *   Direct-Database-Access calls.
    *
    *
    * SOURCE
    */
    this.inputs= function(obj,direct) {
        if(direct==null) direct=false;
        if(obj==null) {
            obj = $a.byId('x4Top');
        }
        if(typeof(obj)=='string') {
            var jqObjects = $(obj);
        }
        else {
            var jqObjects = $(obj).find(":input");
        }
        jqObjects.each( function() {
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
    }
    /******/
    
    /****m* json/serialize
    *
    * NAME
    *   ua.json.serialize
    *
    * FUNCTION
    *   The Javascript method ua.json.serialize takes a
    *   Javascript Object or Array and serializes it and
    *   adds the values to a JSON request previously
    *   initialized with ua.json.init.
    *
    *   This method accepts an object as its parameter.
    *
    *   When you call this function, the parameters sent
    *   back take the form of an associative array.
    *
    * INPUTS
    *   prefix - The base name of the parameter
    *
    *   object - the object to serialize.
    *
    * EXAMPLE
    *   Consider the following object that is serialized
    *
    *      <script>
    *      var x = {
    *         parm1: [ 1, 2, 3],
    *         parm2: 'hello',
    *         parm3: {
    *             x: 5,
    *             y: 10,
    *         }
    *      ua.json.init('x4Page','myCustomPage');
    *      ua.json.addParm('x4Action','serialHandler');
    *      ua.json.serialize('example',x);
    *      <script>
    *
    *   Then on the server, you can grab the "example" parameter
    *   and you will get the following associative array:
    *
    *      <?php
    *      # this is file x4myCustomPage.php
    *      class x4myCustomPage extends androX4 {
    * 
    *          # this handles the 'x4Action' specified above
    *          function serialHandler() {
    *              $example = gp('example');
    *            
    *              # ...the following code shows how 
    *              #    the values that are in x4
    *              $example['parm1'][0] = 1;
    *              $example['parm1'][1] = 2;
    *              $example['parm1'][2] = 3;
    *              $example['parm2'] = 'hello';
    *              $example['parm3']['x'] = 5;
    *              $example['parm3']['y'] = 10;
    *          }
    *      }
    *      ?>
    *
    * SOURCE
    */
    this.serialize = function(prefix,obj) {
        for(var x in obj) {
            if(typeof(obj[x])=='object') {
                this.serialize(prefix+'['+x+']',obj[x]);
            }
            else {
                this.addParm(prefix+'['+x+']',obj[x]);
            }
        }
    }
    /******/
    
    /****m* json/windowLocation
    *
    * NAME
    *   ua.json.windowLocation
    *
    * FUNCTION
    *   The Javascript method ua.json.windowLocation takes a
    *   JSON request and executes it as a page request.
    *
    * EXAMPLE
    *   The following example loads a new page
    *
    *      <script>
    *      ua.json.init('x4Page','calendar');
    *      ua.json.windowLocation();
    *      </script>
    *
    * SOURCE
    */
    this.windowLocation = function() {
        var entireGet = 'index.php?'+this.makeString()
        window.location = entireGet;
    }
    /******/
    
    /****m* json/newWindow
    *
    * NAME
    *   ua.json.newWindow
    *
    * FUNCTION
    *   The Javascript method ua.json.newWindow takes a
    *   JSON request and executes it as a page request, popping
    *   the result up in a new tab or window.
    *
    *   When the user exits the resulting tab or window, it
    *   will close.  
    *
    * EXAMPLE
    *   The following example loads a new page
    *
    *      <script>
    *      ua.json.init('x4Page','calendar');
    *      ua.json.newWindow();
    *      </script>
    *
    * SOURCE
    */
    this.newWindow = function() {
        var entireGet = 'index.php?'+this.makeString()+'&x4Return=exit';
        $a.openWindow(entireGet);
    }
    /******/

    /****m* json/executeAsync
    *
    * NAME
    *   ua.json.executeAsync
    *
    * FUNCTION
    *   By default Andromeda sends JSON requests synchronously,
    *   which is more appropriate for business database applications
    *   than asynchronous requests.
    *
    *   There are however some times when you do not want the user
    *   to wait, and so you can make asynchronous calls. 
    *
    *   Andromeda does not make use of response handlers, see the
    *   above section on ua.json for more details.
    *
    * SOURCE
    */
    this.executeAsync = function() {
        this.execute(true,true);
    }
    /******/
    
    /****m* json/execute
    *
    * NAME
    *   ua.json.execute
    *
    * FUNCTION
    *   The Javascript method ua.json.execute sends a request to
    *   the server that has been initialized with ua.json.init
    *   and has received parameters with any of ua.json.addParm,
    *   ua.json.inputs and ua.json.serialize.
    *
    *   In normal usage, you call this routine and check for
    *   a return value of true.  If the routine returns true
    *   you call ua.json.process to process the returned
    *   results.
    *
    * RESULTS
    *   This routine returns true if the server reports no 
    *   errors.
    *
    *   If the server reports errors, they are displayed to the
    *   user using u.dialogs.alert, and this routine returns
    *   false.
    *
    *******
    */
    this.execute = function(autoProcess,async) {
        this.hadErrors = false;
        if(async==null) async = false;
        if(autoProcess==null) autoProcess=false;
        
        // KFD 7/8/08, When the user is clicking on
        //             search boxes, they can click faster
        //             than we can get answers, so if
        //             we notice we are running an action
        //             that is already in progress, we
        //             cancel the earlier action.
        //var key = this.x4Page + this.x4Action;
        //if( typeof(this.requests[key])!='undefined') {
        //    this.requests[key].abort();
        //}
        //this.requests[key] = http;
        
        // If async, we have to do it a little differently
        // KFD 11/24, did nothing yet for async
        if(async) {
            http.onreadystatechange = function() {
                if(this.readyState!=4) return;
                $a.json.processPre(this,key,false);
                $a.json.process();
            }
        }
        
        // Execute the call
        var entireGet = 'index.php?json=1&'+this.makeString();
        this.http.open('POST' , entireGet, async);
        this.http.send(null);
        
        // An asynchronous call now exits, but a
        // synchronous call continues            
        if (async) return;
        else return this.processPre(autoProcess);
        
    }
    
    this.processPre = function(autoProcess) {
        // Attempt to evaluate the JSON
        try {
            eval('this.jdata = '+this.http.responseText);
        }
        catch(e) { 
            $a.dialogs.alert("Could not process server response!");
            x4.debug(this.http.responseText);
            if(u.byId('x6Log')) {
                u.byId('x6Log').innerHTML = http.responseText;
                u.byId('x6Log').style.display='block';
            }
            this.http = false;
            return false;
        }
        
        // KFD 7/8/08, additional housekeeping, throw away
        //             references to the object
        this.http = false;

        // If there were server errors, report those
        if(this.jdata.error.length>0 && this.reportErrors) {
            this.hadErrors = true;
            $a.dialogs.alert(this.jdata.error.join("\n\n"));
            return false;
        }
        if(this.jdata.notice.length>0 && this.reportErrors) {
            $a.dialogs.alert(this.jdata.notice.join("\n\n"));
        }
        
        if(autoProcess) {
            this.process();
        }
        
        return true;
    }
    
    /****m* json/process
    *
    * NAME
    *   ua.json.process
    *
    * FUNCTION
    *   The Javascript method ua.json.execute is the final
    *   step in sending and receiving JSON requests.  This
    *   routine does the following:
    *   * Any HTML sent back via PHP x4HTML replaces the 
    *     innerHTML of the named items (actually item Ids are used).
    *   * Any script sent back via PHP x4Script is executed.
    *   * Any data sent back via PHP x4Data is placed into
    *     ua.data.
    *
    * EXAMPLE
    *   This example shows how you can retrieve table data and
    *   then process it:
    *
    *      <script>
    *      ua.json.init('x4Page','myCustomPage');
    *      ua.json.addParm('x4Action','getStates');
    *      // ua.json.execute will return false on errors
    *      if(ua.json.execute()) {
    *         // ua.json.process puts everything in its place...
    *         ua.json.process();
    *         // ...so that we can handle the returned data
    *         for (var idx in ua.data.states) {
    *            // do something
    *         }
    *      }
    *      <script>
    *
    *   This code requires the following PHP code on the server:
    *
    *      <?php
    *      # this is file application/x4myCustomPage.php
    *      class x4myCustomPage extends androX4 {
    *          function getStates() {
    *              $states = SQL("Select * from states");
    *              x4Data('states',$states);
    *          }
    *      }
    *
    *******
    */
    this.process = function(divMain) {
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

