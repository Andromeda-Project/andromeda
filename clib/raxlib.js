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
         x.value=x.attributes.getNamedItem('x_value_original').value;
         if(x.attributes.getNamedItem('x_mode').value=='upd') {
            changeCheck(x);
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
    http.open('get', 'index.php'+getString);
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
// ANDROMEDA AJAX-IFIED DROPDOWN LIST
// Honorable mention to www.dhtmlgoodies.com, whose code I
//    examined while creating this code.  
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
    aSelect.div.innerHTML = ''
    aSelect.div.style.display = 'none';
    //removeEventListener(document        ,'click',androSelect_onClick);
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
    //console.log("in the onclick");
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
(C) www.dhtmlgoodies.com, September 2005

Version 1.2, November 8th - 2005 - Added <iframe> background in IE
Version 1.3, November 12th - 2005 - Fixed top bar position in Opera 7
Version 1.4, December 28th - 2005 - Support for Spanish and Portuguese
Version 1.5, January  18th - 2006 - Fixed problem with next-previous buttons after a month has been selected from dropdown
Version 1.6, February 22nd - 2006 - Added variable which holds the path to images.
									Format todays date at the bottom by use of the todayStringFormat variable
									Pick todays date by clicking on todays date at the bottom of the calendar

This is a script from www.dhtmlgoodies.com. You will find this and a lot of other scripts at our website.	

Terms of use:
You are free to use this script as long as the copyright message is kept intact. However, you may not
redistribute, sell or repost it without our permission.

Thank you!

www.dhtmlgoodies.com
Alf Magne Kalleland

*/
var languageCode = 'en';	// Possible values: 	en,ge,no,nl,es,pt-br,fr	
							// en = english, ge = german, no = norwegian,nl = dutch, es = spanish, pt-br = portuguese, fr = french, da = danish, hu = hungarian(Use UTF-8 doctype for hungarian)

							
// Format of current day at the bottom of the calendar
// [todayString] = the value of todayString
// [dayString] = day of week (examle: Mon, Tue, Wed...)
// [day] = Day of month, 1..31
// [monthString] = Name of current month
// [year] = Current year							
var todayStringFormat = '[todayString] [dayString]. [day]. [monthString] [year]';						
//var pathToImages = 'images/';	// Relative to your HTML file
var pathToImages = 'clib/dhtmlgoodies_calendar_images/';


var calendar_offsetTop = 0;		// Offset - calendar placement - You probably have to modify this value if you're not using a strict doctype
var calendar_offsetLeft = 0;	// Offset - calendar placement - You probably have to modify this value if you're not using a strict doctype
var calendarDiv = false;

var MSIE = false;
var Opera = false;
if(navigator.userAgent.indexOf('MSIE')>=0 && navigator.userAgent.indexOf('Opera')<0)MSIE=true;
if(navigator.userAgent.indexOf('Opera')>=0)Opera=true;


switch(languageCode){
	case "en":	/* English */
		var monthArray = ['January','February','March','April','May','June','July','August','September','October','November','December'];
		var monthArrayShort = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
		var dayArray = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
		var weekString = 'Week';
		var todayString = 'Today is';
		break;
	case "ge":	/* German */
		var monthArray = ['Januar','Februar','M�rz','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'];
		var monthArrayShort = ['Jan','Feb','Mar','Apr','Mai','Jun','Jul','Aug','Sep','Okt','Nov','Dez'];
		var dayArray = ['Mon','Die','Mit','Don','Fre','Sam','Son'];	
		var weekString = 'Woche';
		var todayString = 'Heute';		
		break;
	case "no":	/* Norwegian */
		var monthArray = ['Januar','Februar','Mars','April','Mai','Juni','Juli','August','September','Oktober','November','Desember'];
		var monthArrayShort = ['Jan','Feb','Mar','Apr','Mai','Jun','Jul','Aug','Sep','Okt','Nov','Des'];
		var dayArray = ['Man','Tir','Ons','Tor','Fre','L&oslash;r','S&oslash;n'];	
		var weekString = 'Uke';
		var todayString = 'Dagen i dag er';
		break;	
	case "nl":	/* Dutch */
		var monthArray = ['Januari','Februari','Maart','April','Mei','Juni','Juli','Augustus','September','Oktober','November','December'];
		var monthArrayShort = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Aug','Sep','Okt','Nov','Dec'];
		var dayArray = ['Ma','Di','Wo','Do','Vr','Za','Zo'];
		var weekString = 'Week';
		var todayString = 'Vandaag';
		break;	
	case "es": /* Spanish */
		var monthArray = ['Enero','Febrero','Marzo','April','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
		var monthArrayShort =['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
		var dayArray = ['Lun','Mar','Mie','Jue','Vie','Sab','Dom'];
		var weekString = 'Semana';
		var todayString = 'Hoy es';
		break; 	
	case "pt-br":  /* Brazilian portuguese (pt-br) */
		var monthArray = ['Janeiro','Fevereiro','Mar&ccedil;o','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
		var monthArrayShort = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
		var dayArray = ['Seg','Ter','Qua','Qui','Sex','S&aacute;b','Dom'];
		var weekString = 'Sem.';
		var todayString = 'Hoje &eacute;';
		break;
	case "fr":      /* French */
		var monthArray = ['Janvier','F�vrier','Mars','Avril','Mai','Juin','Juillet','Ao�t','Septembre','Octobre','Novembre','D�cembre'];		
		var monthArrayShort = ['Jan','Fev','Mar','Avr','Mai','Jun','Jul','Aou','Sep','Oct','Nov','Dec'];
		var dayArray = ['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'];
		var weekString = 'Sem';
		var todayString = "Aujourd'hui";
		break; 	
	case "ru":	/* Russian - Remember to use encoding windows-1251 , i.e. the <meta> tag. */
		var monthArray = ['������','�������','����','������','���','����','����','������','��������','�������','������','�������'];
		var monthArrayShort = ['���','���','���','���','���','���','���','���','���','���','���','���'];
		var dayArray = ['��','��','��','��','��','��','��'];
		var weekString = '#';
		var todayString = '�������';
		break;		
	case "da": /*Danish*/
		var monthArray = ['januar','februar','marts','april','maj','juni','juli','august','september','oktober','november','december'];
		var monthArrayShort = ['jan','feb','mar','apr','maj','jun','jul','aug','sep','okt','nov','dec'];
		var dayArray = ['man','tirs','ons','tors','fre','l�r','s�n']
		var weekString = 'Uge';
		var todayString = 'I dag er den';
		break;	
	case "hu":	/* Hungarian  - Remember to use UTF-8 encoding, i.e. the <meta> tag */
		var monthArray = ['Január','Február','Március','�?prilis','Május','Június','Július','Augusztus','Szeptember','Október','November','December'];
		var monthArrayShort = ['Jan','Feb','Márc','�?pr','Máj','Jún','Júl','Aug','Szep','Okt','Nov','Dec'];
		var dayArray = ['Hé','Ke','Sze','Cs','Pé','Szo','Vas'];
		var weekString = 'Hét';
		var todayString = 'Mai nap';	
		break;
	case "it":	/* Italian*/
		var monthArray = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
		var monthArrayShort = ['Gen','Feb','Mar','Apr','Mag','Giu','Lugl','Ago','Set','Ott','Nov','Dic'];
		var dayArray = ['Lun',';Mar','Mer','Gio','Ven','Sab','Dom'];
		var weekString = 'Settimana';
		var todayString = 'Oggi &egrave; il';
		break;


		
}



var daysInMonthArray = [31,28,31,30,31,30,31,31,30,31,30,31];
var currentMonth;
var currentYear;
var calendarContentDiv;
var returnDateTo;
var returnFormat;
var activeSelectBoxMonth;
var activeSelectBoxYear;
var iframeObj = false;

var returnDateToYear;
var returnDateToMonth;
var returnDateToDay;

var inputYear;
var inputMonth;
var inputDay;


var selectBoxHighlightColor = '#D60808'; // Highlight color of select boxes
var selectBoxRolloverBgColor = '#E2EBED'; // Background color on drop down lists(rollover)
function cancelCalendarEvent()
{
	return false;
}
function isLeapYear(inputYear)
{
	if(inputYear%400==0||(inputYear%4==0&&inputYear%100!=0)) return true;
	return false;	
	
}
var activeSelectBoxMonth = false;

function highlightMonthYear()
{
	if(activeSelectBoxMonth)activeSelectBoxMonth.className='';
	
	if(this.className=='monthYearActive'){
		this.className='';	
	}else{
		this.className = 'monthYearActive';
		activeSelectBoxMonth = this;
	}
}

function showMonthDropDown()
{
	if(document.getElementById('monthDropDown').style.display=='block'){
		document.getElementById('monthDropDown').style.display='none';	
	}else{
		document.getElementById('monthDropDown').style.display='block';		
		document.getElementById('yearDropDown').style.display='none';
	}
}

function showYearDropDown()
{
	if(document.getElementById('yearDropDown').style.display=='block'){
		document.getElementById('yearDropDown').style.display='none';	
	}else{
		document.getElementById('yearDropDown').style.display='block';	
		document.getElementById('monthDropDown').style.display='none';	
	}		

}

function selectMonth()
{
	document.getElementById('calendar_month_txt').innerHTML = this.innerHTML
	currentMonth = this.id.replace(/[^\d]/g,'');

	document.getElementById('monthDropDown').style.display='none';
	for(var no=0;no<monthArray.length;no++){
		document.getElementById('monthDiv_'+no).style.color='';	
	}
	this.style.color = selectBoxHighlightColor;
	activeSelectBoxMonth = this;
	writeCalendarContent();
	
}

function selectYear()
{
	document.getElementById('calendar_year_txt').innerHTML = this.innerHTML
	currentYear = this.innerHTML.replace(/[^\d]/g,'');
	document.getElementById('yearDropDown').style.display='none';
	if(activeSelectBoxYear){
		activeSelectBoxYear.style.color='';
	}
	activeSelectBoxYear=this;
	this.style.color = selectBoxHighlightColor;
	writeCalendarContent();
	
}

function switchMonth()
{
	if(this.src.indexOf('left')>=0){
		currentMonth=currentMonth-1;;
		if(currentMonth<0){
			currentMonth=11;
			currentYear=currentYear-1;
		}
	}else{
		currentMonth=currentMonth+1;;
		if(currentMonth>11){
			currentMonth=0;
			currentYear=currentYear/1+1;
		}	
	}	
	
	writeCalendarContent();	
	
	
}

function createMonthDiv(){
	var div = document.createElement('DIV');
	div.className='monthYearPicker';
	div.id = 'monthPicker';
	
	for(var no=0;no<monthArray.length;no++){
		var subDiv = document.createElement('DIV');
		subDiv.innerHTML = monthArray[no];
		subDiv.onmouseover = highlightMonthYear;
		subDiv.onmouseout = highlightMonthYear;
		subDiv.onclick = selectMonth;
		subDiv.id = 'monthDiv_' + no;
		subDiv.style.width = '56px';
		subDiv.onselectstart = cancelCalendarEvent;		
		div.appendChild(subDiv);
		if(currentMonth && currentMonth==no){
			subDiv.style.color = selectBoxHighlightColor;
			activeSelectBoxMonth = subDiv;
		}				
		
	}	
	return div;
	
}

function changeSelectBoxYear()
{

	var yearItems = this.parentNode.getElementsByTagName('DIV');
	if(this.innerHTML.indexOf('-')>=0){
		var startYear = yearItems[1].innerHTML/1 -1;
		if(activeSelectBoxYear){
			activeSelectBoxYear.style.color='';
		}
	}else{
		var startYear = yearItems[1].innerHTML/1 +1;
		if(activeSelectBoxYear){
			activeSelectBoxYear.style.color='';

		}			
	}

	for(var no=1;no<yearItems.length-1;no++){
		yearItems[no].innerHTML = startYear+no-1;	
		yearItems[no].id = 'yearDiv' + (startYear/1+no/1-1);	
		
	}		
	if(activeSelectBoxYear){
		activeSelectBoxYear.style.color='';
		if(document.getElementById('yearDiv'+currentYear)){
			activeSelectBoxYear = document.getElementById('yearDiv'+currentYear);
			activeSelectBoxYear.style.color=selectBoxHighlightColor;;
		}
	}
}

function updateYearDiv()
{
	var div = document.getElementById('yearDropDown');
	var yearItems = div.getElementsByTagName('DIV');
	for(var no=1;no<yearItems.length-1;no++){
		yearItems[no].innerHTML = currentYear/1 -6 + no;	
		if(currentYear==(currentYear/1 -6 + no)){
			yearItems[no].style.color = selectBoxHighlightColor;
			activeSelectBoxYear = yearItems[no];				
		}else{
			yearItems[no].style.color = '';
		}
	}		
}

function updateMonthDiv()
{
	for(no=0;no<12;no++){
		document.getElementById('monthDiv_' + no).style.color = '';
	}		
	document.getElementById('monthDiv_' + currentMonth).style.color = selectBoxHighlightColor;
	activeSelectBoxMonth = 	document.getElementById('monthDiv_' + currentMonth);
}

function createYearDiv()
{

	if(!document.getElementById('yearDropDown')){
		var div = document.createElement('DIV');
		div.className='monthYearPicker';
	}else{
		var div = document.getElementById('yearDropDown');
		var subDivs = div.getElementsByTagName('DIV');
		for(var no=0;no<subDivs.length;no++){
			subDivs[no].parentNode.removeChild(subDivs[no]);	
		}	
	}	
	
	
	var d = new Date();
	if(currentYear){
		d.setFullYear(currentYear);	
	}

	var startYear = d.getFullYear()/1 - 5;

	
	var subDiv = document.createElement('DIV');
	subDiv.innerHTML = '&nbsp;&nbsp;- ';
	subDiv.onclick = changeSelectBoxYear;
	subDiv.onmouseover = highlightMonthYear;
	subDiv.onmouseout = highlightMonthYear;	
	subDiv.onselectstart = cancelCalendarEvent;			
	div.appendChild(subDiv);
	
	for(var no=startYear;no<(startYear+10);no++){
		var subDiv = document.createElement('DIV');
		subDiv.innerHTML = no;
		subDiv.onmouseover = highlightMonthYear;
		subDiv.onmouseout = highlightMonthYear;		
		subDiv.onclick = selectYear;		
		subDiv.id = 'yearDiv' + no;	
		subDiv.onselectstart = cancelCalendarEvent;	
		div.appendChild(subDiv);
		if(currentYear && currentYear==no){
			subDiv.style.color = selectBoxHighlightColor;
			activeSelectBoxYear = subDiv;
		}			
	}
	var subDiv = document.createElement('DIV');
	subDiv.innerHTML = '&nbsp;&nbsp;+ ';
	subDiv.onclick = changeSelectBoxYear;
	subDiv.onmouseover = highlightMonthYear;
	subDiv.onmouseout = highlightMonthYear;		
	subDiv.onselectstart = cancelCalendarEvent;			
	div.appendChild(subDiv);		

	
	return div;
}

function highlightSelect()
{
	if(this.className=='selectBox'){
		this.className = 'selectBoxOver';	
		this.getElementsByTagName('IMG')[0].src = pathToImages + 'down_over.gif';
	}else{
		this.className = 'selectBox';	
		this.getElementsByTagName('IMG')[0].src = pathToImages + 'down.gif';			
	}
	
}

function highlightArrow()
{
	if(this.src.indexOf('over')>=0){
		if(this.src.indexOf('left')>=0)this.src = pathToImages + 'left.gif';	
		if(this.src.indexOf('right')>=0)this.src = pathToImages + 'right.gif';				
	}else{
		if(this.src.indexOf('left')>=0)this.src = pathToImages + 'left_over.gif';	
		if(this.src.indexOf('right')>=0)this.src = pathToImages + 'right_over.gif';	
	}
}

function highlightClose()
{
	if(this.src.indexOf('over')>=0){
		this.src = pathToImages + 'close.gif';
	}else{
		this.src = pathToImages + 'close_over.gif';	
	}	

}

function closeCalendar(){

	document.getElementById('yearDropDown').style.display='none';
	document.getElementById('monthDropDown').style.display='none';
		
	calendarDiv.style.display='none';
	if(iframeObj)iframeObj.style.display='none';
	if(activeSelectBoxMonth)activeSelectBoxMonth.className='';
	if(activeSelectBoxYear)activeSelectBoxYear.className='';
	

}

function writeTopBar()
{

	var topBar = document.createElement('DIV');
	topBar.className = 'topBar';
	topBar.id = 'topBar';
	calendarDiv.appendChild(topBar);
	
	// Left arrow
	var leftDiv = document.createElement('DIV');
	leftDiv.style.marginRight = '1px';
	var img = document.createElement('IMG');
	img.src = pathToImages + 'left.gif';
	img.onmouseover = highlightArrow;
	img.onclick = switchMonth;
	img.onmouseout = highlightArrow;
	leftDiv.appendChild(img);	
	topBar.appendChild(leftDiv);
	if(Opera)leftDiv.style.width = '16px';
	
	// Right arrow
	var rightDiv = document.createElement('DIV');
	rightDiv.style.marginRight = '1px';
	var img = document.createElement('IMG');
	img.src = pathToImages + 'right.gif';
	img.onclick = switchMonth;
	img.onmouseover = highlightArrow;
	img.onmouseout = highlightArrow;
	rightDiv.appendChild(img);
	if(Opera)rightDiv.style.width = '16px';
	topBar.appendChild(rightDiv);		

			
	// Month selector
	var monthDiv = document.createElement('DIV');
	monthDiv.id = 'monthSelect';
	monthDiv.onmouseover = highlightSelect;
	monthDiv.onmouseout = highlightSelect;
	monthDiv.onclick = showMonthDropDown;
	var span = document.createElement('SPAN');		
	span.innerHTML = monthArray[currentMonth];
	span.id = 'calendar_month_txt';
	monthDiv.appendChild(span);

	var img = document.createElement('IMG');
	img.src = pathToImages + 'down.gif';
	img.style.position = 'absolute';
	img.style.right = '0px';
	monthDiv.appendChild(img);
	monthDiv.className = 'selectBox';
	if(Opera){
		img.style.cssText = 'float:right;position:relative';
		img.style.position = 'relative';
		img.style.styleFloat = 'right';
	}
	topBar.appendChild(monthDiv);

	var monthPicker = createMonthDiv();
	monthPicker.style.left = '37px';
	monthPicker.style.top = monthDiv.offsetTop + monthDiv.offsetHeight + 1 + 'px';
	monthPicker.style.width ='60px';
	monthPicker.id = 'monthDropDown';
	
	calendarDiv.appendChild(monthPicker);
			
	// Year selector
	var yearDiv = document.createElement('DIV');
	yearDiv.onmouseover = highlightSelect;
	yearDiv.onmouseout = highlightSelect;
	yearDiv.onclick = showYearDropDown;
	var span = document.createElement('SPAN');		
	span.innerHTML = currentYear;
	span.id = 'calendar_year_txt';
	yearDiv.appendChild(span);
	topBar.appendChild(yearDiv);
	
	var img = document.createElement('IMG');
	img.src = pathToImages + 'down.gif';
	yearDiv.appendChild(img);
	yearDiv.className = 'selectBox';
	
	if(Opera){
		yearDiv.style.width = '50px';
		img.style.cssText = 'float:right';
		img.style.position = 'relative';
		img.style.styleFloat = 'right';
	}	
	
	var yearPicker = createYearDiv();
	yearPicker.style.left = '113px';
	yearPicker.style.top = monthDiv.offsetTop + monthDiv.offsetHeight + 1 + 'px';
	yearPicker.style.width = '35px';
	yearPicker.id = 'yearDropDown';
	calendarDiv.appendChild(yearPicker);
	
		
	var img = document.createElement('IMG');
	img.src = pathToImages + 'close.gif';
	img.style.styleFloat = 'right';
	img.onmouseover = highlightClose;
	img.onmouseout = highlightClose;
	img.onclick = closeCalendar;
	topBar.appendChild(img);
	if(!document.all){
		img.style.position = 'absolute';
		img.style.right = '2px';
	}
	
	

}

function writeCalendarContent()
{
	var calendarContentDivExists = true;
	if(!calendarContentDiv){
		calendarContentDiv = document.createElement('DIV');
		calendarDiv.appendChild(calendarContentDiv);
		calendarContentDivExists = false;
	}
	currentMonth = currentMonth/1;
	var d = new Date();	
	
	d.setFullYear(currentYear);		
	d.setDate(1);		
	d.setMonth(currentMonth);
	
	var dayStartOfMonth = d.getDay();
	if(dayStartOfMonth==0)dayStartOfMonth=7;
	dayStartOfMonth--;
	
	document.getElementById('calendar_year_txt').innerHTML = currentYear;
	document.getElementById('calendar_month_txt').innerHTML = monthArray[currentMonth];
	
	var existingTable = calendarContentDiv.getElementsByTagName('TABLE');
	if(existingTable.length>0){
		calendarContentDiv.removeChild(existingTable[0]);
	}
	
	var calTable = document.createElement('TABLE');
	calTable.cellSpacing = '0';
	calendarContentDiv.appendChild(calTable);
	var calTBody = document.createElement('TBODY');
	calTable.appendChild(calTBody);
	var row = calTBody.insertRow(-1);
	var cell = row.insertCell(-1);
	cell.innerHTML = weekString;
	cell.style.backgroundColor = selectBoxRolloverBgColor;
	
	for(var no=0;no<dayArray.length;no++){
		var cell = row.insertCell(-1);
		cell.innerHTML = dayArray[no]; 
	}
	
	var row = calTBody.insertRow(-1);
	var cell = row.insertCell(-1);
	cell.style.backgroundColor = selectBoxRolloverBgColor;
	var week = getWeek(currentYear,currentMonth,1);
	cell.innerHTML = week;		// Week
	for(var no=0;no<dayStartOfMonth;no++){
		var cell = row.insertCell(-1);
		cell.innerHTML = '&nbsp;';
	}

	var colCounter = dayStartOfMonth;
	var daysInMonth = daysInMonthArray[currentMonth];
	if(daysInMonth==28){
		if(isLeapYear(currentYear))daysInMonth=29;
	}
	
	for(var no=1;no<=daysInMonth;no++){
		d.setDate(no-1);
		if(colCounter>0 && colCounter%7==0){
			var row = calTBody.insertRow(-1);
			var cell = row.insertCell(-1);
			var week = getWeek(currentYear,currentMonth,no);
			cell.innerHTML = week;		// Week	
			cell.style.backgroundColor = selectBoxRolloverBgColor;			
		}
		var cell = row.insertCell(-1);
		if(currentYear==inputYear && currentMonth == inputMonth && no==inputDay){
			cell.className='activeDay';	
		}
		cell.innerHTML = no;
		cell.onclick = pickDate;
		colCounter++;
	}
	
	
	if(!document.all){
		if(calendarContentDiv.offsetHeight)
			document.getElementById('topBar').style.top = calendarContentDiv.offsetHeight + document.getElementById('topBar').offsetHeight -1 + 'px';
		else{
			document.getElementById('topBar').style.top = '';
			document.getElementById('topBar').style.bottom = '0px';
		}
			
	}
	
	if(iframeObj){
		if(!calendarContentDivExists)setTimeout('resizeIframe()',350);else setTimeout('resizeIframe()',10);
	}
		
	
}

function resizeIframe()
{
	iframeObj.style.width = calendarDiv.offsetWidth + 'px';
	iframeObj.style.height = calendarDiv.offsetHeight + 'px' ;	
	
	
}

function pickTodaysDate()
{
	var d = new Date();
	currentMonth = d.getMonth();
	currentYear = d.getFullYear();
	pickDate(false,d.getDate());
	
}

function pickDate(e,inputDay)
{
	var month = currentMonth/1 +1;
	if(month<10)month = '0' + month;
	var day;
	if(!inputDay && this)day = this.innerHTML; else day = inputDay;
	
	if(day/1<10)day = '0' + day;
	if(returnFormat){
		returnFormat = returnFormat.replace('dd',day);
		returnFormat = returnFormat.replace('mm',month);
		returnFormat = returnFormat.replace('yyyy',currentYear);
		returnDateTo.value = returnFormat;
      // ANDROMEDA CHANGE 6/11/07 KFD, test for onchange.
      if(returnDateTo.onchange) { returnDateTo.onchange(); }
	}else{
		for(var no=0;no<returnDateToYear.options.length;no++){
			if(returnDateToYear.options[no].value==currentYear){
				returnDateToYear.selectedIndex=no;
				break;
			}				
		}
		for(var no=0;no<returnDateToMonth.options.length;no++){
			if(returnDateToMonth.options[no].value==month){
				returnDateToMonth.selectedIndex=no;
				break;
			}				
		}
		for(var no=0;no<returnDateToDay.options.length;no++){
			if(returnDateToDay.options[no].value==day){
				returnDateToDay.selectedIndex=no;
				break;
			}				
		}
		
		
	}
	closeCalendar();
	
}

// This function is from http://www.codeproject.com/csharp/gregorianwknum.asp
// Only changed the month add
function getWeek(year,month,day){
	day = day/1;
	year = year /1;
    month = month/1 + 1; //use 1-12
    var a = Math.floor((14-(month))/12);
    var y = year+4800-a;
    var m = (month)+(12*a)-3;
    var jd = day + Math.floor(((153*m)+2)/5) + 
                 (365*y) + Math.floor(y/4) - Math.floor(y/100) + 
                 Math.floor(y/400) - 32045;      // (gregorian calendar)
    var d4 = (jd+31741-(jd%7))%146097%36524%1461;
    var L = Math.floor(d4/1460);
    var d1 = ((d4-L)%365)+L;
    NumberOfWeek = Math.floor(d1/7) + 1;
    return NumberOfWeek;        
}

function writeBottomBar()
{
	var d = new Date();
	var topBar = document.createElement('DIV');
	topBar.id = 'bottomBar';
	topBar.onclick = pickTodaysDate;
	topBar.style.cursor = 'pointer';
	topBar.className = 'todaysDate';
	// var todayStringFormat = '[todayString] [dayString] [day] [monthString] [year]';	;;
	
	var day = d.getDay();
	if(day==0)day = 7;
	day--;
	
	var bottomString = todayStringFormat;
	bottomString = bottomString.replace('[monthString]',monthArrayShort[d.getMonth()]);
	bottomString = bottomString.replace('[day]',d.getDate());
	bottomString = bottomString.replace('[year]',d.getFullYear());
	bottomString = bottomString.replace('[dayString]',dayArray[day].toLowerCase());
	bottomString = bottomString.replace('[todayString]',todayString);
	
	
	topBar.innerHTML = todayString + ': ' + d.getDate() + '. ' + monthArrayShort[d.getMonth()] + ', ' +  d.getFullYear() ;
	topBar.innerHTML = bottomString ;
	calendarDiv.appendChild(topBar);	
	
	
		
}
function getTopPos(inputObj)
{
	
  var returnValue = inputObj.offsetTop + inputObj.offsetHeight;
  while((inputObj = inputObj.offsetParent) != null)returnValue += inputObj.offsetTop;
  return returnValue + calendar_offsetTop;
}

function getleftPos(inputObj)
{
  var returnValue = inputObj.offsetLeft;
  while((inputObj = inputObj.offsetParent) != null)returnValue += inputObj.offsetLeft;
  return returnValue + calendar_offsetLeft;
}

function positionCalendar(inputObj)
{
	calendarDiv.style.left = getleftPos(inputObj) + 'px';
	calendarDiv.style.top = getTopPos(inputObj) + 'px';
	if(iframeObj){
		iframeObj.style.left = calendarDiv.style.left;
		iframeObj.style.top =  calendarDiv.style.top;
	}
		
}
	
function initCalendar()
{
	if(MSIE){
		iframeObj = document.createElement('IFRAME');
		iframeObj.style.position = 'absolute';
		iframeObj.border='0px';
		iframeObj.style.border = '0px';
		iframeObj.style.backgroundColor = '#FF0000';
		document.body.appendChild(iframeObj);
	}
		
	calendarDiv = document.createElement('DIV');	
	calendarDiv.id = 'calendarDiv';
	calendarDiv.style.zIndex = 1000;
	
	document.body.appendChild(calendarDiv);
	writeBottomBar();
	writeTopBar();
	

	
	if(!currentYear){
		var d = new Date();
		currentMonth = d.getMonth();
		currentYear = d.getFullYear();
	}
	writeCalendarContent();	


		
}


function displayCalendar(inputField,format,buttonObj)
{
	if(inputField.value.length==format.length){
		var monthPos = format.indexOf('mm');
		currentMonth = inputField.value.substr(monthPos,2)/1 -1;	
		var yearPos = format.indexOf('yyyy');
		currentYear = inputField.value.substr(yearPos,4);		
		var dayPos = format.indexOf('dd');
		tmpDay = inputField.value.substr(dayPos,2);	
	}else{
		var d = new Date();
		currentMonth = d.getMonth();
		currentYear = d.getFullYear();
		tmpDay = d.getDate();
	}
	
	inputYear = currentYear;
	inputMonth = currentMonth;
	inputDay = tmpDay/1;
	
	if(!calendarDiv){
		initCalendar();			
	}else{
		if(calendarDiv.style.display=='block'){
			closeCalendar();
			return false;
		}
		writeCalendarContent();
	}			
	returnFormat = format;
	returnDateTo = inputField;
	positionCalendar(buttonObj);
	calendarDiv.style.visibility = 'visible';	
	calendarDiv.style.display = 'block';	
	if(iframeObj){
		iframeObj.style.display = '';
		iframeObj.style.height = '140px';
		iframeObj.style.width = '195px';
	}
	updateYearDiv();
	updateMonthDiv();
	
}

function displayCalendarSelectBox(yearInput,monthInput,dayInput,buttonObj)
{
	currentMonth = monthInput.options[monthInput.selectedIndex].value/1-1;
	currentYear = yearInput.options[yearInput.selectedIndex].value;

	inputYear = yearInput.options[yearInput.selectedIndex].value;
	inputMonth = monthInput.options[monthInput.selectedIndex].value/1 - 1;
	inputDay = dayInput.options[dayInput.selectedIndex].value/1;
			
	if(!calendarDiv){
		initCalendar();			
	}else{
		writeCalendarContent();
	}		

	returnDateToYear = yearInput;
	returnDateToMonth = monthInput;
	returnDateToDay = dayInput;
	

	

	
	returnFormat = false;
	returnDateTo = false;
	positionCalendar(buttonObj);
	calendarDiv.style.visibility = 'visible';	
	calendarDiv.style.display = 'block';
	if(iframeObj){
		iframeObj.style.display = '';
		iframeObj.style.height = calendarDiv.offsetHeight + 'px';
		iframeObj.style.width = calendarDiv.offsetWidth + 'px';	
	}
	updateYearDiv();
	updateMonthDiv();
		
}

/************************************************************************************************************

	Form field tooltip
	(C) www.dhtmlgoodies.com, September 2006

	This is a script from www.dhtmlgoodies.com. You will find this and a lot of other scripts at our website.	
	
	Terms of use:
	Look at the terms of use at http://www.dhtmlgoodies.com/index.html?page=termsOfUse
	
	Thank you!
	
	www.dhtmlgoodies.com
	Alf Magne Kalleland

************************************************************************************************************/

var DHTMLgoodies_globalTooltipObj;


/** 
Constructor 
**/
function DHTMLgoodies_formTooltip()
{
	var tooltipDiv;
	var tooltipText;
	var tooltipContentDiv;				// Reference to inner div with tooltip content
	var imagePath;						// Relative path to images
	var arrowImageFile;					// Name of arrow image
	var arrowImageFileRight;			// Name of arrow image
	var arrowRightWidth;
	var arrowTopHeight;
	var tooltipWidth;					// Width of tooltip
	var roundedCornerObj;				// Reference to object of class DHTMLgoodies_roundedCorners
	var tooltipBgColor;
	var closeMessage;					// Close message
	var activeInput;					// Reference to currently active input
	var tooltipPosition;				// Tooltip position, possible values: "below" or "right"
	var tooltipCornerSize;				// Size of rounded corners
	var displayArrow;					// Display arrow above or at the left of the tooltip?
	var cookieName;						// Name of cookie
	var disableTooltipPossibility;		// Possibility of disabling tooltip
	var disableTooltipByCookie;			// If tooltip has been disabled, save the settings in cookie, i.e. for other pages with the same cookie name.
	var disableTooltipMessage;
	var tooltipDisabled;
	var isMSIE;
	var tooltipIframeObj;
	var pageBgColor;					// Color of background - used in ie when applying iframe which covers select boxes
	var currentTooltipObj;				// Reference to form field which tooltip is currently showing for
	
	this.currentTooltipObj = false,
	this.tooltipDiv = false,
	this.tooltipText = false;
	this.imagePath = 'images/';
	this.arrowImageFile = 'green-arrow.gif';
	this.arrowImageFileRight = 'green-arrow-right.gif';
	this.tooltipWidth = 200;
	//this.tooltipBgColor = '#317082';
   this.tooltipBgColor= '#666666';
	this.closeMessage = 'Close';
	this.disableTooltipMessage = 'Don\'t show this message again';
	this.activeInput = false;
	this.tooltipPosition = 'right';
	this.arrowRightWidth = 16;			// Default width of arrow when the tooltip is on the right side of the inputs.
	this.arrowTopHeight = 13;			// Default height of arrow at the top of tooltip
   /* KFD 5/29/07, no rounded corners, this was originally 10 */
   this.tooltipCornerSize = 0
   /* KFD 5/29/07, don't display arrow */
	this.displayArrow = false;
	this.cookieName = 'DHTMLgoodies_tooltipVisibility';
	this.disableTooltipByCookie = false;
	this.tooltipDisabled = false;
	this.disableTooltipPossibility = true;
	this.tooltipIframeObj = false;
	this.pageBgColor = '#FFFFFF';
	
	DHTMLgoodies_globalTooltipObj = this;
	
	if(navigator.userAgent.indexOf('MSIE')>=0)this.isMSIE = true; else this.isMSIE = false;
}


DHTMLgoodies_formTooltip.prototype = {
	// {{{ initFormFieldTooltip()
    /**
     *
	 *
     *  Initializes the tooltip script. Most set methods needs to be executed before you call this method.
     * 
     * @public
     */		
	initFormFieldTooltip : function()
	{
		var formElements = new Array();

      /* KFD, 5/29/07 for Andromeda, also do Hyperlinks */
		var inputs = document.getElementsByTagName('A');
		for(var no=0;no<inputs.length;no++){
			var attr = inputs[no].getAttribute('tooltipText');
			if(!attr)attr = inputs[no].tooltipText;
			if(attr)formElements[formElements.length] = inputs[no];
		}

		var inputs = document.getElementsByTagName('INPUT');
		for(var no=0;no<inputs.length;no++){
			var attr = inputs[no].getAttribute('tooltipText');
			if(!attr)attr = inputs[no].tooltipText;
			if(attr)formElements[formElements.length] = inputs[no];
		}
			
		var inputs = document.getElementsByTagName('TEXTAREA');
		for(var no=0;no<inputs.length;no++){
			var attr = inputs[no].getAttribute('tooltipText');
			if(!attr)attr = inputs[no].tooltipText;
			if(attr)formElements[formElements.length] = inputs[no];
		}
		var inputs = document.getElementsByTagName('SELECT');
		for(var no=0;no<inputs.length;no++){
			var attr = inputs[no].getAttribute('tooltipText');
			if(!attr)attr = inputs[no].tooltipText;
			if(attr)formElements[formElements.length] = inputs[no];
		}
			
		window.refToFormTooltip = this;
		
      /* KFD 5/29/07 for Andromeda, switched to mouseover, added mouseout */
		for(var no=0;no<formElements.length;no++){
			formElements[no].onmouseover = this.__displayTooltip;
			formElements[no].onmouseout = this.__hideTooltip;
		}
		this.addEvent(window,'resize',function(){ window.refToFormTooltip.__positionCurrentToolTipObj(); });
		
		this.addEvent(document.documentElement,'click',function(e){ window.refToFormTooltip.__autoHideTooltip(e); });
	}
	
	// }}}
	,		
	// {{{ setTooltipPosition()
    /**
     *
	 *
     *  Specify position of tooltip(below or right)
     *	@param String newPosition (Possible values: "below" or "right") 
     * 
     * @public
     */	
	setTooltipPosition : function(newPosition)
	{
		this.tooltipPosition = newPosition;
	}
	// }}}
	,		
	// {{{ setCloseMessage()
    /**
     *
	 *
     *  Specify "Close" message
     *	@param String closeMessage
     * 
     * @public
     */
	setCloseMessage : function(closeMessage)
	{
		this.closeMessage = closeMessage;
	}
	// }}}
	,	
	// {{{ setDisableTooltipMessage()
    /**
     *
	 *
     *  Specify disable tooltip message at the bottom of the tooltip
     *	@param String disableTooltipMessage
     * 
     * @public
     */
	setDisableTooltipMessage : function(disableTooltipMessage)
	{
		this.disableTooltipMessage = disableTooltipMessage;
	}
	// }}}
	,		
	// {{{ setTooltipDisablePossibility()
    /**
     *
	 *
     *  Specify whether you want the disable link to appear or not.
     *	@param Boolean disableTooltipPossibility
     * 
     * @public
     */
	setTooltipDisablePossibility : function(disableTooltipPossibility)
	{
		this.disableTooltipPossibility = disableTooltipPossibility;
	}
	// }}}
	,		
	// {{{ setCookieName()
    /**
     *
	 *
     *  Specify name of cookie. Useful if you're using this script on several pages. 
     *	@param String newCookieName
     * 
     * @public
     */
	setCookieName : function(newCookieName)
	{
		this.cookieName = newCookieName;
	}
	// }}}
	,		
	// {{{ setTooltipWidth()
    /**
     *
	 *
     *  Specify width of tooltip
     *	@param Int newWidth
     * 
     * @public
     */	
	setTooltipWidth : function(newWidth)
	{
		this.tooltipWidth = newWidth;
	}
	
	// }}}
	,		
	// {{{ setArrowVisibility()
    /**
     *
	 *
     *  Display arrow at the top or at the left of the tooltip?
     *	@param Boolean displayArrow
     * 
     * @public
     */	
	
	setArrowVisibility : function(displayArrow)
	{
		this.displayArrow = displayArrow;
	}
	
	// }}}
	,		
	// {{{ setTooltipBgColor()
    /**
     *
	 *
     *  Send true to this method if you want to be able to save tooltip visibility in cookie. If it's set to true,
     *	It means that when someone returns to the page, the tooltips won't show.
     * 
     *	@param Boolean disableTooltipByCookie
     * 
     * @public
     */	
	setDisableTooltipByCookie : function(disableTooltipByCookie)
	{
		this.disableTooltipByCookie = disableTooltipByCookie;
	}	
	// }}}
	,		
	// {{{ setTooltipBgColor()
    /**
     *
	 *
     *  This method specifies background color of tooltip
     *	@param String newBgColor
     * 
     * @public
     */	
	setTooltipBgColor : function(newBgColor)
	{
		this.tooltipBgColor = newBgColor;
	}
	
	// }}}
	,		
	// {{{ setTooltipCornerSize()
    /**
     *
	 *
     *  Size of rounded corners around tooltip
     *	@param Int newSize (0 = no rounded corners)
     * 
     * @public
     */	
	setTooltipCornerSize : function(tooltipCornerSize)
	{
		this.tooltipCornerSize = tooltipCornerSize;
	}
	
	// }}}
	,
	// {{{ setTopArrowHeight()
    /**
     *
	 *
     *  Size height of arrow at the top of tooltip
     *	@param Int arrowTopHeight
     * 
     * @public
     */	
	setTopArrowHeight : function(arrowTopHeight)
	{
		this.arrowTopHeight = arrowTopHeight;
	}
	
	// }}}
	,	
	// {{{ setRightArrowWidth()
    /**
     *
	 *
     *  Size width of arrow when the tooltip is on the right side of inputs
     *	@param Int arrowTopHeight
     * 
     * @public
     */	
	setRightArrowWidth : function(arrowRightWidth)
	{
		this.arrowRightWidth = arrowRightWidth;
	}
	
	// }}}
	,	
	// {{{ setPageBgColor()
    /**
     *
	 *
     *  Specify background color of page.
     *	@param String pageBgColor
     * 
     * @public
     */	
	setPageBgColor : function(pageBgColor)
	{
		this.pageBgColor = pageBgColor;
	}
	
	// }}}
	,		
	// {{{ __hideTooltip()
    /**
     *
	 *
     *  This method displays the tooltip
     *
     * 
     * @private
     */		
	__displayTooltip : function()
	{
		if(DHTMLgoodies_globalTooltipObj.disableTooltipByCookie){
			var cookieValue = DHTMLgoodies_globalTooltipObj.getCookie(DHTMLgoodies_globalTooltipObj.cookieName) + '';	
			if(cookieValue=='1')DHTMLgoodies_globalTooltipObj.tooltipDisabled = true;
		}	
		
		if(DHTMLgoodies_globalTooltipObj.tooltipDisabled)return;	// Tooltip disabled
		var tooltipText = this.getAttribute('tooltipText');
		DHTMLgoodies_globalTooltipObj.activeInput = this;
		
		if(!tooltipText)tooltipText = this.tooltipText;
		DHTMLgoodies_globalTooltipObj.tooltipText = tooltipText;

		
		if(!DHTMLgoodies_globalTooltipObj.tooltipDiv)DHTMLgoodies_globalTooltipObj.__createTooltip();
		
		DHTMLgoodies_globalTooltipObj.__positionTooltip(this);
		
		
		
	
		DHTMLgoodies_globalTooltipObj.tooltipContentDiv.innerHTML = tooltipText;
		DHTMLgoodies_globalTooltipObj.tooltipDiv.style.display='block';
		
		if(DHTMLgoodies_globalTooltipObj.isMSIE){
			if(DHTMLgoodies_globalTooltipObj.tooltipPosition == 'below'){
				DHTMLgoodies_globalTooltipObj.tooltipIframeObj.style.height = (DHTMLgoodies_globalTooltipObj.tooltipDiv.clientHeight - DHTMLgoodies_globalTooltipObj.arrowTopHeight);
			}else{
				DHTMLgoodies_globalTooltipObj.tooltipIframeObj.style.height = (DHTMLgoodies_globalTooltipObj.tooltipDiv.clientHeight);
			}
		}
		
	}
	// }}}
	,		
	// {{{ __hideTooltip()
    /**
     *
	 *
     *  This function hides the tooltip
     *
     * 
     * @private
     */		
	__hideTooltip : function()
	{
		try{
			DHTMLgoodies_globalTooltipObj.tooltipDiv.style.display='none';
		}catch(e){
		}
		
	}
	// }}}
	,
	// {{{ getSrcElement()
    /**
     *
	 *
     *  Return the source of an event.
     *
     * 
     * @private
     */		
    getSrcElement : function(e)
    {
    	var el;
		if (e.target) el = e.target;
			else if (e.srcElement) el = e.srcElement;
			if (el.nodeType == 3) // defeat Safari bug
				el = el.parentNode;
		return el;	
    }	
	// }}}
	,
	__autoHideTooltip : function(e)
	{
		if(document.all)e = event;	
		var src = this.getSrcElement(e);
		if(src.tagName.toLowerCase()!='input' && src.tagName.toLowerCase().toLowerCase()!='textarea' && src.tagName.toLowerCase().toLowerCase()!='select')this.__hideTooltip();

		var attr = src.getAttribute('tooltipText');
		if(!attr)attr = src.tooltipText;
		if(!attr){
			this.__hideTooltip();
		}	
		
	}
	// }}}
	,		
	// {{{ __hideTooltipFromLink()
    /**
     *
	 *
     *  This function hides the tooltip
     *
     * 
     * @private
     */	
	__hideTooltipFromLink : function()
	{
		
		this.activeInput.focus();
		window.refToThis = this;
		setTimeout('window.refToThis.__hideTooltip()',10);
	}
	// }}}
	,		
	// {{{ disableTooltip()
    /**
     *
	 *
     *  Hide tooltip and disable it
     *
     * 
     * @public
     */	
	disableTooltip : function()
	{
		this.__hideTooltipFromLink();
		if(this.disableTooltipByCookie)this.setCookie(this.cookieName,'1',500);	
		this.tooltipDisabled = true;	
	}	
	// }}}
	,		
	// {{{ __positionTooltip()
    /**
     *
	 *
     *  This function creates the tooltip elements
     *
     * 
     * @private
     */	
	__createTooltip : function()
	{
		this.tooltipDiv = document.createElement('DIV');
		this.tooltipDiv.style.position = 'absolute';
		
		if(this.displayArrow){
			var topDiv = document.createElement('DIV');
			
			if(this.tooltipPosition=='below'){
				
				topDiv.style.marginLeft = '20px';
				var arrowDiv = document.createElement('IMG');
				arrowDiv.src = this.imagePath + this.arrowImageFile + '?rand='+ Math.random();
				arrowDiv.style.display='block';
				topDiv.appendChild(arrowDiv);
					
			}else{
				topDiv.style.marginTop = '5px';
				var arrowDiv = document.createElement('IMG');
				arrowDiv.src = this.imagePath + this.arrowImageFileRight + '?rand='+ Math.random();	
				arrowDiv.style.display='block';
				topDiv.appendChild(arrowDiv);					
				topDiv.style.position = 'absolute';			
			}
			
			this.tooltipDiv.appendChild(topDiv);	
		}
		
		var outerDiv = document.createElement('DIV');
		outerDiv.style.position = 'relative';
		outerDiv.style.zIndex = 1000;
		if(this.tooltipPosition!='below' && this.displayArrow){			
			outerDiv.style.left = this.arrowRightWidth + 'px';
		}
				
		outerDiv.id = 'DHTMLgoodies_formTooltipDiv';
		outerDiv.className = 'DHTMLgoodies_formTooltipDiv';
		outerDiv.style.backgroundColor = this.tooltipBgColor;
		this.tooltipDiv.appendChild(outerDiv);

		if(this.isMSIE){
			this.tooltipIframeObj = document.createElement('<IFRAME name="tooltipIframeObj" width="' + this.tooltipWidth + '" frameborder="no" src="about:blank"></IFRAME>');
			this.tooltipIframeObj.style.position = 'absolute';
			this.tooltipIframeObj.style.top = '0px';
			this.tooltipIframeObj.style.left = '0px';
			this.tooltipIframeObj.style.width = (this.tooltipWidth) + 'px';
			this.tooltipIframeObj.style.zIndex = 100;
			this.tooltipIframeObj.background = this.pageBgColor;
			this.tooltipIframeObj.style.backgroundColor= this.pageBgColor;
			this.tooltipDiv.appendChild(this.tooltipIframeObj);	
			if(this.tooltipPosition!='below' && this.displayArrow){
				this.tooltipIframeObj.style.left = (this.arrowRightWidth) +  'px';	
			}else{
				this.tooltipIframeObj.style.top = this.arrowTopHeight + 'px';	
			}

			setTimeout("self.frames['tooltipIframeObj'].document.documentElement.style.backgroundColor='" + this.pageBgColor + "'",500);

		}
		
		this.tooltipContentDiv = document.createElement('DIV');	
		this.tooltipContentDiv.style.position = 'relative';
		this.tooltipContentDiv.id = 'DHTMLgoodies_formTooltipContent';
		outerDiv.appendChild(this.tooltipContentDiv);			
		
		var closeDiv = document.createElement('DIV');
		closeDiv.style.textAlign = 'center';
	
		//closeDiv.innerHTML = '<A class="DHTMLgoodies_formTooltip_closeMessage" href="#" onclick="DHTMLgoodies_globalTooltipObj.__hideTooltipFromLink();return false">' + this.closeMessage + '</A>';
		//
		//if(this.disableTooltipPossibility){
		//	var tmpHTML = closeDiv.innerHTML;
		//	tmpHTML = tmpHTML + ' | <A class="DHTMLgoodies_formTooltip_closeMessage" href="#" onclick="DHTMLgoodies_globalTooltipObj.disableTooltip();return false">' + this.disableTooltipMessage + '</A>';
		//	closeDiv.innerHTML = tmpHTML;
		//} 
		
		outerDiv.appendChild(closeDiv);
		
		document.body.appendChild(this.tooltipDiv);
		
		
				
		if(this.tooltipCornerSize>0){
			this.roundedCornerObj = new DHTMLgoodies_roundedCorners();
			// (divId,xRadius,yRadius,color,backgroundColor,padding,heightOfContent,whichCorners)
			this.roundedCornerObj.addTarget('DHTMLgoodies_formTooltipDiv',this.tooltipCornerSize,this.tooltipCornerSize,this.tooltipBgColor,this.pageBgColor,5);
			this.roundedCornerObj.init();
		}
		

		this.tooltipContentDiv = document.getElementById('DHTMLgoodies_formTooltipContent');
	}
	// }}}
	,
	addEvent : function(whichObject,eventType,functionName)
	{ 
	  if(whichObject.attachEvent){ 
	    whichObject['e'+eventType+functionName] = functionName; 
	    whichObject[eventType+functionName] = function(){whichObject['e'+eventType+functionName]( window.event );} 
	    whichObject.attachEvent( 'on'+eventType, whichObject[eventType+functionName] ); 
	  } else 
	    whichObject.addEventListener(eventType,functionName,false); 	    
	} 	
	// }}}
	,
	__positionCurrentToolTipObj : function()
	{
		if(DHTMLgoodies_globalTooltipObj.activeInput)this.__positionTooltip(DHTMLgoodies_globalTooltipObj.activeInput);
		
	}
	// }}}
	,		
	// {{{ __positionTooltip()
    /**
     *
	 *
     *  This function positions the tooltip
     *
     * @param Obj inputObj = Reference to text input
     * 
     * @private
     */	
	__positionTooltip : function(inputObj)
	{	
		var offset = 0;
		if(!this.displayArrow)offset = 3;	
		if(this.tooltipPosition=='below'){
			this.tooltipDiv.style.left = this.getLeftPos(inputObj)+  'px';
			this.tooltipDiv.style.top = (this.getTopPos(inputObj) + inputObj.offsetHeight + offset) + 'px';
		}else{
		
			this.tooltipDiv.style.left = (this.getLeftPos(inputObj) + inputObj.offsetWidth + offset)+  'px';
			this.tooltipDiv.style.top = this.getTopPos(inputObj) + 'px';			
		}
		this.tooltipDiv.style.width=this.tooltipWidth + 'px';
		
	}
	,
	// {{{ getTopPos()
    /**
     * This method will return the top coordinate(pixel) of an object
     *
     * @param Object inputObj = Reference to HTML element
     * @public
     */	
	getTopPos : function(inputObj)
	{		
	  var returnValue = inputObj.offsetTop;
	  while((inputObj = inputObj.offsetParent) != null){
	  	if(inputObj.tagName!='HTML'){
	  		returnValue += inputObj.offsetTop;
	  		if(document.all)returnValue+=inputObj.clientTop;
	  	}
	  } 
	  return returnValue;
	}
	// }}}
	
	,
	// {{{ getLeftPos()
    /**
     * This method will return the left coordinate(pixel) of an object
     *
     * @param Object inputObj = Reference to HTML element
     * @public
     */	
	getLeftPos : function(inputObj)
	{	  
	  var returnValue = inputObj.offsetLeft;
	  while((inputObj = inputObj.offsetParent) != null){
	  	if(inputObj.tagName!='HTML'){
	  		returnValue += inputObj.offsetLeft;
	  		if(document.all)returnValue+=inputObj.clientLeft;
	  	}
	  }
	  return returnValue;
	}
	
	,
	
	// {{{ getCookie()
    /**
     *
     * 	These cookie functions are downloaded from 
	 * 	http://www.mach5.com/support/analyzer/manual/html/General/CookiesJavaScript.htm
	 *
     *  This function returns the value of a cookie
     *
     * @param String name = Name of cookie
     * @param Object inputObj = Reference to HTML element
     * @public
     */	
	getCookie : function(name) { 
	   var start = document.cookie.indexOf(name+"="); 
	   var len = start+name.length+1; 
	   if ((!start) && (name != document.cookie.substring(0,name.length))) return null; 
	   if (start == -1) return null; 
	   var end = document.cookie.indexOf(";",len); 
	   if (end == -1) end = document.cookie.length; 
	   return unescape(document.cookie.substring(len,end)); 
	} 	
	// }}}
	,	
	
	// {{{ setCookie()
    /**
     *
     * 	These cookie functions are downloaded from 
	 * 	http://www.mach5.com/support/analyzer/manual/html/General/CookiesJavaScript.htm
	 *
     *  This function creates a cookie. (This method has been slighhtly modified)
     *
     * @param String name = Name of cookie
     * @param String value = Value of cookie
     * @param Int expires = Timestamp - days
     * @param String path = Path for cookie (Usually left empty)
     * @param String domain = Cookie domain
     * @param Boolean secure = Secure cookie(SSL)
     * 
     * @public
     */	
	setCookie : function(name,value,expires,path,domain,secure) { 
		expires = expires * 60*60*24*1000;
		var today = new Date();
		var expires_date = new Date( today.getTime() + (expires) );
	    var cookieString = name + "=" +escape(value) + 
	       ( (expires) ? ";expires=" + expires_date.toGMTString() : "") + 
	       ( (path) ? ";path=" + path : "") + 
	       ( (domain) ? ";domain=" + domain : "") + 
	       ( (secure) ? ";secure" : ""); 
	    document.cookie = cookieString; 
	}
	// }}}
		
		
}

/************************************************************************************************************<br>
<br>
	@fileoverview
	Rounded corners class<br>
	(C) www.dhtmlgoodies.com, September 2006<br>
	<br>
	This is a script from www.dhtmlgoodies.com. You will find this and a lot of other scripts at our website.	<br>
	<br>
	Terms of use:<br>
	Look at the terms of use at http://www.dhtmlgoodies.com/index.html?page=termsOfUse<br>
	<br>
	Thank you!<br>
	<br>
	www.dhtmlgoodies.com<br>
	Alf Magne Kalleland<br>
<br>
************************************************************************************************************/

// {{{ Constructor
function DHTMLgoodies_roundedCorners()
{
	var roundedCornerTargets;
	
	this.roundedCornerTargets = new Array();
	
}
	var string = '';
// }}}
DHTMLgoodies_roundedCorners.prototype = {

	// {{{ addTarget() 
    /**
     *
	 *
     *  Add rounded corners to an element
     *
     *	@param String divId = Id of element on page. Example "leftColumn" for &lt;div id="leftColumn">
     *	@param Int xRadius = Y radius of rounded corners, example 10
     *	@param Int yRadius = Y radius of rounded corners, example 10
     *  @param String color = Background color of element, example #FFF or #AABBCC
     *  @param String color = backgroundColor color of element "behind", example #FFF or #AABBCC
     *  @param Int padding = Padding of content - This will be added as left and right padding(not top and bottom)
     *  @param String heightOfContent = Optional argument. You can specify a fixed height of your content. example "15" which means pixels, or "50%". 
     *  @param String whichCorners = Optional argument. Commaseparated list of corners, example "top_left,top_right,bottom_left"
     * 
     * @public
     */		
    addTarget : function(divId,xRadius,yRadius,color,backgroundColor,padding,heightOfContent,whichCorners)
    {	
    	var index = this.roundedCornerTargets.length;
    	this.roundedCornerTargets[index] = new Array();
    	this.roundedCornerTargets[index]['divId'] = divId;
    	this.roundedCornerTargets[index]['xRadius'] = xRadius;
    	this.roundedCornerTargets[index]['yRadius'] = yRadius;
    	this.roundedCornerTargets[index]['color'] = color;
    	this.roundedCornerTargets[index]['backgroundColor'] = backgroundColor;
    	this.roundedCornerTargets[index]['padding'] = padding;
    	this.roundedCornerTargets[index]['heightOfContent'] = heightOfContent;
    	this.roundedCornerTargets[index]['whichCorners'] = whichCorners;  
    	
    }
    // }}}
    ,
	// {{{ init()
    /**
     *
	 *
     *  Initializes the script
     *
     * 
     * @public
     */	    
	init : function()
	{
		
		for(var targetCounter=0;targetCounter < this.roundedCornerTargets.length;targetCounter++){
			
			// Creating local variables of each option
			whichCorners = this.roundedCornerTargets[targetCounter]['whichCorners'];
			divId = this.roundedCornerTargets[targetCounter]['divId'];
			xRadius = this.roundedCornerTargets[targetCounter]['xRadius'];
			yRadius = this.roundedCornerTargets[targetCounter]['yRadius'];
			color = this.roundedCornerTargets[targetCounter]['color'];
			backgroundColor = this.roundedCornerTargets[targetCounter]['backgroundColor'];
			padding = this.roundedCornerTargets[targetCounter]['padding'];
			heightOfContent = this.roundedCornerTargets[targetCounter]['heightOfContent'];
			whichCorners = this.roundedCornerTargets[targetCounter]['whichCorners'];

			// Which corners should we add rounded corners to?
			var cornerArray = new Array();
			if(!whichCorners || whichCorners=='all'){
				cornerArray['top_left'] = true;
				cornerArray['top_right'] = true;
				cornerArray['bottom_left'] = true;
				cornerArray['bottom_right'] = true;
			}else{
				cornerArray = whichCorners.split(/,/gi);
				for(var prop in cornerArray)cornerArray[cornerArray[prop]] = true;
			}
					
				
			var factorX = xRadius/yRadius;	// How big is x radius compared to y radius
		
			var obj = document.getElementById(divId);	// Creating reference to element
			obj.style.backgroundColor=null;	// Setting background color blank
			obj.style.backgroundColor='transparent';
			var content = obj.innerHTML;	// Saving HTML content of this element
			obj.innerHTML = '';	// Setting HTML content of element blank-
			
	
			
			
			// Adding top corner div.
			
			if(cornerArray['top_left'] || cornerArray['top_right']){
				var topBar_container = document.createElement('DIV');
				topBar_container.style.height = yRadius + 'px';
				topBar_container.style.overflow = 'hidden';	
		
				obj.appendChild(topBar_container);		
				var currentAntialiasSize = 0;
				var savedRestValue = 0;
				
				for(no=1;no<=yRadius;no++){
					var marginSize = (xRadius - (this.getY((yRadius - no),yRadius,factorX)));					
					var marginSize_decimals = (xRadius - (this.getY_withDecimals((yRadius - no),yRadius,factorX)));					
					var restValue = xRadius - marginSize_decimals;		
					var antialiasSize = xRadius - marginSize - Math.floor(savedRestValue)
					var foregroundSize = xRadius - (marginSize + antialiasSize);	
					
					var el = document.createElement('DIV');
					el.style.overflow='hidden';
					el.style.height = '1px';					
					if(cornerArray['top_left'])el.style.marginLeft = marginSize + 'px';				
					if(cornerArray['top_right'])el.style.marginRight = marginSize + 'px';	
					topBar_container.appendChild(el);				
					var y = topBar_container;		
					
					for(var no2=1;no2<=antialiasSize;no2++){
						switch(no2){
							case 1:
								if (no2 == antialiasSize)
									blendMode = ((restValue + savedRestValue) /2) - foregroundSize;
								else {
								  var tmpValue = this.getY_withDecimals((xRadius - marginSize - no2),xRadius,1/factorX);
								  blendMode = (restValue - foregroundSize - antialiasSize + 1) * (tmpValue - (yRadius - no)) /2;
								}						
								break;							
							case antialiasSize:								
								var tmpValue = this.getY_withDecimals((xRadius - marginSize - no2 + 1),xRadius,1/factorX);								
								blendMode = 1 - (1 - (tmpValue - (yRadius - no))) * (1 - (savedRestValue - foregroundSize)) /2;							
								break;
							default:			
								var tmpValue2 = this.getY_withDecimals((xRadius - marginSize - no2),xRadius,1/factorX);
								var tmpValue = this.getY_withDecimals((xRadius - marginSize - no2 + 1),xRadius,1/factorX);		
								blendMode = ((tmpValue + tmpValue2) / 2) - (yRadius - no);							
						}
						
						el.style.backgroundColor = this.__blendColors(backgroundColor,color,blendMode);
						y.appendChild(el);
						y = el;
						var el = document.createElement('DIV');
						el.style.height = '1px';	
						el.style.overflow='hidden';
						if(cornerArray['top_left'])el.style.marginLeft = '1px';
						if(cornerArray['top_right'])el.style.marginRight = '1px';    						
						el.style.backgroundColor=color;					
					}
					
					y.appendChild(el);				
					savedRestValue = restValue;
				}
			}
			
			// Add content
			var contentDiv = document.createElement('DIV');
			contentDiv.className = obj.className;
			contentDiv.style.border='1px solid ' + color;
			contentDiv.innerHTML = content;
			contentDiv.style.backgroundColor=color;
			contentDiv.style.paddingLeft = padding + 'px';
			contentDiv.style.paddingRight = padding + 'px';
	
			if(!heightOfContent)heightOfContent = '';
			heightOfContent = heightOfContent + '';
			if(heightOfContent.length>0 && heightOfContent.indexOf('%')==-1)heightOfContent = heightOfContent + 'px';
			if(heightOfContent.length>0)contentDiv.style.height = heightOfContent;
			
			obj.appendChild(contentDiv);
	
		
			if(cornerArray['bottom_left'] || cornerArray['bottom_right']){
				var bottomBar_container = document.createElement('DIV');
				bottomBar_container.style.height = yRadius + 'px';
				bottomBar_container.style.overflow = 'hidden';	
		
				obj.appendChild(bottomBar_container);		
				var currentAntialiasSize = 0;
				var savedRestValue = 0;
				
				var errorOccured = false;
				var arrayOfDivs = new Array();
				for(no=1;no<=yRadius;no++){
					
					var marginSize = (xRadius - (this.getY((yRadius - no),yRadius,factorX)));					
					var marginSize_decimals = (xRadius - (this.getY_withDecimals((yRadius - no),yRadius,factorX)));						
	
					var restValue = (xRadius - marginSize_decimals);				
					var antialiasSize = xRadius - marginSize - Math.floor(savedRestValue)
					var foregroundSize = xRadius - (marginSize + antialiasSize);	
					
					var el = document.createElement('DIV');
					el.style.overflow='hidden';
					el.style.height = '1px';					
					if(cornerArray['bottom_left'])el.style.marginLeft = marginSize + 'px';				
					if(cornerArray['bottom_right'])el.style.marginRight = marginSize + 'px';	
					bottomBar_container.insertBefore(el,bottomBar_container.firstChild);				
					
					var y = bottomBar_container;		
					
					for(var no2=1;no2<=antialiasSize;no2++){
						switch(no2){
							case 1:
								if (no2 == antialiasSize)
									blendMode = ((restValue + savedRestValue) /2) - foregroundSize;
								else {
								  var tmpValue = this.getY_withDecimals((xRadius - marginSize - no2),xRadius,1/factorX);
								  blendMode = (restValue - foregroundSize - antialiasSize + 1) * (tmpValue - (yRadius - no)) /2;
								}						
								break;							
							case antialiasSize:								
								var tmpValue = this.getY_withDecimals((xRadius - marginSize - no2 + 1),xRadius,1/factorX);								
								blendMode = 1 - (1 - (tmpValue - (yRadius - no))) * (1 - (savedRestValue - foregroundSize)) /2;							
								break;
							default:			
								var tmpValue2 = this.getY_withDecimals((xRadius - marginSize - no2),xRadius,1/factorX);
								var tmpValue = this.getY_withDecimals((xRadius - marginSize - no2 + 1),xRadius,1/factorX);		
								blendMode = ((tmpValue + tmpValue2) / 2) - (yRadius - no);							
						}
						
						el.style.backgroundColor = this.__blendColors(backgroundColor,color,blendMode);
						
						if(y==bottomBar_container)arrayOfDivs[arrayOfDivs.length] = el;
						
						try{	// Need to look closer at this problem which occures in Opera.
							var firstChild = y.getElementsByTagName('DIV')[0];
							y.insertBefore(el,y.firstChild);
						}catch(e){
							y.appendChild(el);							
							errorOccured = true;
						}
						y = el;
						
						var el = document.createElement('DIV');
						el.style.height = '1px';	
						el.style.overflow='hidden';
						if(cornerArray['bottom_left'])el.style.marginLeft = '1px';
						if(cornerArray['bottom_right'])el.style.marginRight = '1px';    						
										
					}
					
					if(errorOccured){	// Opera fix
						for(var divCounter=arrayOfDivs.length-1;divCounter>=0;divCounter--){
							bottomBar_container.appendChild(arrayOfDivs[divCounter]);
						}
					}
					
					el.style.backgroundColor=color;	
					y.appendChild(el);				
					savedRestValue = restValue;
				}
	
			}			
		}
	}		
	// }}}
	,		
	// {{{ getY()
    /**
     *
	 *
     *  Add rounded corners to an element
     *
     *	@param Int x = x Coordinate
     *	@param Int maxX = Size of rounded corners
	 *
     * 
     * @private
     */		
	getY : function(x,maxX,factorX){
		// y = sqrt(100 - x^2)			
		// Y = 0.5 * ((100 - x^2)^0.5);			
		return Math.max(0,Math.ceil(factorX * Math.sqrt( (maxX * maxX) - (x*x)) ));
		
	}	
	// }}}
	,		
	// {{{ getY_withDecimals()
    /**
     *
	 *
     *  Add rounded corners to an element
     *
     *	@param Int x = x Coordinate
     *	@param Int maxX = Size of rounded corners
	 *
     * 
     * @private
     */		
	getY_withDecimals : function(x,maxX,factorX){
		// y = sqrt(100 - x^2)			
		// Y = 0.5 * ((100 - x^2)^0.5);			
		return Math.max(0,factorX * Math.sqrt( (maxX * maxX) - (x*x)) );
		
	}
	

	,

	// {{{ __blendColors()
    /**
     *
	 *
     *  Simply blending two colors by extracting red, green and blue and subtracting difference between colors from them.
     * 	Finally, we multiply it with the blendMode value
     *
     *	@param String colorA = RGB color
     *	@param String colorB = RGB color
     *	@param Float blendMode 
	 *
     * 
     * @private
     */		
	__blendColors : function (colorA, colorB, blendMode) {
		if(colorA.length=='4'){	// In case we are dealing with colors like #FFF
			colorA = '#' + colorA.substring(1,1) + colorA.substring(1,1) + colorA.substring(2,1) + colorA.substring(2,1) + colorA.substring(3,1) + colorA.substring(3,1);
		}	
		if(colorB.length=='4'){	// In case we are dealing with colors like #FFF
			colorB = '#' + colorB.substring(1,1) + colorB.substring(1,1) + colorB.substring(2,1) + colorB.substring(2,1) + colorB.substring(3,1) + colorB.substring(3,1);
		}
		var colorArrayA = [parseInt('0x' + colorA.substring(1,3)), parseInt('0x' + colorA.substring(3, 5)), parseInt('0x' + colorA.substring(5, 7))];	// Create array of Red, Green and Blue ( 0-255)
		var colorArrayB = [parseInt('0x' + colorB.substring(1,3)), parseInt('0x' + colorB.substring(3, 5)), parseInt('0x' + colorB.substring(5, 7))];	// Create array of Red, Green and Blue ( 0-255)		
		var red = Math.round(colorArrayA[0] + (colorArrayB[0] - colorArrayA[0])*blendMode).toString(16);	// Create new Red color ( Hex )
		var green = Math.round(colorArrayA[1] + (colorArrayB[1] - colorArrayA[1])*blendMode).toString(16);	// Create new Green color ( Hex )
		var blue = Math.round(colorArrayA[2] + (colorArrayB[2] - colorArrayA[2])*blendMode).toString(16);	// Create new Blue color ( Hex )
		
		if(red.length==1)red = '0' + red;
		if(green.length==1)green = '0' + green;
		if(blue.length==1)blue = '0' + blue;
			
		return '#' + red + green+ blue;	// Return new RGB color
	}
}				
