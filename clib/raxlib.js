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

function andrax(getString) {
    http.open('get', 'index.php'+getString);
    http.onreadystatechange = handleResponse;
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
