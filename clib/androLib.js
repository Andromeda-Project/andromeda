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

// -----------------------------------------------------
// SPECIAL NOTE FROM KEN 4/2/08.  This file contains
//    Code that code that goes way back to day 1,
//    a lot of it is long since defunct.  This file
//    could be significantly cut down when we
//    do the code cleanup
// -----------------------------------------------------

// ------------------------------------------------------
// Top level execution: determine if IE
// ------------------------------------------------------
var androIE = false;
if(    navigator.userAgent.indexOf('MSIE') >=0 
    && navigator.userAgent.indexOf('Opera')<0) {
    androIE = true;
}

// ------------------------------------------------------
// Some variable conventions
//   obj    :  an object reference returned by ob() as passed as 'this'
//   objname:  name of an object
//   att    :  an attribute returned by obj.attributes.getNamedItem()
//   attname:  attribute name
//   keycode:  the return value from keyCode()
//
// ------------------------------------------------------
// Handy verbosity-reducing routines
// ------------------------------------------------------
// Get an object reference
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
// return keycode, this is the one you
// need to catch function keys, arrows keys and 
// so forth.
function KeyCode(e) {
   if(window.event)
      // IE
      return window.event.keyCode;  
   else
      // firefox
      return e.keyCode;
}
// Return the value of a named attribute, empty
// string if it does not exist
function obAttValue(objname,attname) {
   var obj=ob(objname);
   return objAttValue(obj,attname);
}

function objAttValue(obj,attname) {
   if(!obj) {
      //alert('Reference to unknown object: '+objname);
      return '';
   }
   else {
      att = obj.attributes.getNamedItem(attname);
      if(!att) {  
         //alert('Reference to unknown attribute: '+objanem+'.'+attname);
         return '';
      }
      else {
         return att.value;
      }           
   }
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


// ------------------------------------------------------
// Universal keystroke handling for HTML body element
// ------------------------------------------------------
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
      if(obj.value != x_value_original) {
         obj.attributes.getNamedItem('x_class_base').value='ins';
      }
      else {
         obj.attributes.getNamedItem('x_class_base').value='upd';
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
// Some basic routines to post vars
// ------------------------------------------------------
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
function u3SS(stringparms) {
   ob('u3string').value=stringparms;
   formSubmit();
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
// A major rewrite.  formSubmit used to have only one
// line, "ob('Form1').submit()".  Now it is AJAX-ified,
// it builds a string and sends back the post, but it is
// clever enough to only send back the changed values!
// And it tells the server to send back only main 
// content, no need for the rest of it.
// ------------------------------------------------------
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
// A simple shortcut
// ------------------------------------------------------
function ob(oname) {
  if (document.getElementById)
	  return document.getElementById(oname);
  else if (document.all) 
	  return document.all[name];
}

function obv(oname) {
   if(ob(oname)) return ob(oname).value;
   else return '';
}

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

// ----------------------------------------------------------------
// 
// ----------------------------------------------------------------


// ----------------------------------------------------------------
// ANDROMEDA AJAX-IFIED DROPDOWN LIST
// Honorable mention to www.dhtmlgoodies.com, whose code I
//    examined while creating this code. --KFD  
// ----------------------------------------------------------------
// begin with the public object that 
// tracks all of the info for the androSelect:
var aSelect = new Object();
aSelect.divWidth = 400;
aSelect.divHeight= 300;
aSelect.div      = false;
aSelect.iframe   = false;
aSelect.row      = false;

// Main routine called when a keystroke is hit on 
// the control that "hosts" the androSelect
function androSelect_onKeyUp(obj,strParms,e) {
    var kc = e.keyCode;

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
            return;
        }
        
        var row = byId('as'+aSelect.row);
        if(kc==38) {
            var prev = objAttValue(row,'x_prev');
            if(prev!='') {
                var row = byId('as'+prev);
                androSelect_mo(row,prev);
            }
        }
        if(kc==40) {
            var next = objAttValue(row,'x_next');
            if(next!='') {
                var row = byId('as'+next);
                androSelect_mo(row,next);
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
        aSelect.div.className = 'androSelect';
        aSelect.div.id = 'androSelect';
        document.body.appendChild(aSelect.div);
        var x = document.createElement('TABLE');
        aSelect.div.appendChild(x);
			
    }
    // If it is invisible, position it and then make it visible
    if(aSelect.div.style.display=='none') {
        var postop = obj.offsetTop;
        var poslft = obj.offsetLeft;
        var objpar = obj;
        while((objpar = objpar.offsetParent) != null) {
            postop += objpar.offsetTop;
            poslft += objpar.offsetLeft;
        }
        aSelect.div.style.top  = (postop + obj.offsetHeight) + "px";
        aSelect.div.style.left = poslft + "px";
        aSelect.div.style.display = 'block';
        
        // As part of making visible, create an onclick
        // that will trap the event target and lose focus
        // if not the input object or the
        addEventListener(document   ,'click',androSelect_documentClick);
    }
    
    // Tell it the current control it is working for
    aSelect.control = obj;
    aSelect.row = false;

    // Make up the URL and send the command
    var url = '?'+strParms+'&gpv=2&gp_letters='+obj.value.replace(" ","+");         
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
        
        removeEventListener(document   ,'click',androSelect_documentClick);
        
        if(aSelect.div.firstChild.rows.length==0) {
            androSelect_hide();
           return true;
        }
        
        if(aSelect.row) {
            var row = byId('as'+aSelect.row);
            var pk  = objAttValue(row,'x_value');
            aSelect.control.value = pk;
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
    tr.className = 'hilite';
}
// User clicked on a row
function androSelect_click(value,suppress_focus) {
    aSelect.control.value = value;
    androSelect_hide();
    if(suppress_focus==null) {
        aSelect.control.focus();
    }
}

/*
 * This is the General Purpose Library of most basic 
 * Andromeda javascript functions.  It has a long 
 * complex name: $a
 *            
 */

var $a = {
    /*
     * Dialogs.  Placeholders to use JQuery plugins
     *
     */
    dialogs: {
        alert: function($msg, $title) {
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
        }
    }, 
    
    /*
     * Sub object for doing things on a detail form
     * like fetch values, recalculate and so forth
     *
     */
    forms: {
        fetch: function(table,column,value) {
            $a.json.init('x4Action','fetch');
            $a.json.addParm('x4Page',table);
            $a.json.addParm('column',column);
            $a.json.addParm('value',value);
            if($a.json.execute()) {
                $a.json.process();
                for(var idx in $a.json.data.row) {
                    var retval = $a.json.data.row[idx];
                    $(":input[@xColumnId="+idx+"]")[0].value=retval;
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
        jdata: { },
        data: { dd: {} },
        init: function(name,value) {
            this.callString = '';
            if(name!=null) {
                this.addParm(name,value);
            }
        },
        addParm: function(name,value) {
            if(this.callString!='') this.callString+="&";
            this.callString += name + '=' + encodeURIComponent(value);
        },

        /**
          * Make a synchronous call to the server, expecting
          * to receive a JSON array of stuff back.
          *
          */
        execute: function(autoProcess) {
            if(autoProcess==null) autoProcess=false;
            
            // Create an object
            var browser = navigator.appName;
            if(browser == "Microsoft Internet Explorer"){
                var http = new ActiveXObject("Microsoft.XMLHTTP");
            }else{
                var http = new XMLHttpRequest();
            }
            
            // Execute the call
            var entireGet = 'index.php?json=1&'+this.callString;
            http.open('POST' , entireGet, false);
            http.send(null);
            
            // Attempt to evaluate the JSON
            try {
                eval('this.jdata = '+http.responseText);
            }
            catch(e) { 
                $a.dialogs.alert("Could not process server response!");
                return false;
            }

            // Fatal errors are thrown up immediately.
            if(this.jdata.fatal!='') {
                $a.dialogs.alert(this.jdata.fatal);
                return false;
            }
            
            // If there were server errors, report those
            //if(this.jdata.errors.length>1) {
            //    x4.dialogs.alert(this.jdata.errors.combine("\n\n"));
            //}
            
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
                    $('#'+x).html(this.jdata.html[x]);
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

    // Retrieve an Andromeda property, creating it if not
    // there and assigning it the default    
    aProp: function(obj,propname,defvalue) {
        if(typeof(obj[propname])=='undefined') {
            if(defvalue==null) defvalue=false;
            obj[propname]=defvalue;
        }
        return obj[propname];
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
        
        var numbers = [ '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ];
        if(charcode >= 48 && charcode <= 57) {
            return numbers[charcode - 48];
        }
    }
}

/* - - - - - - - - - - - - - - - - - - - - - - - -
 * 
 * JQuery Plugins and Embellishments
 *
 * - - - - - - - - - - - - - - - - - - - - - - - -  
 */

 
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

var jqModalClose=function(hash) { hash.w.fadeOut(500, function() { hash.o.fadeOut(250);}); };
var jqModalOpen=function(hash) { hash.w.fadeIn(500);hash.o.fadeIn(500);};