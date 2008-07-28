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
 * This file contains routines that go almost back to the
 * beginning of the Andromed epoch (July 1, 2004).  To 
 * avoid loading this file, set your configuration 
 * variable "deprecated" to "N"
 *
 */

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

