<?php
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
//
// This is an example of an x_home page.  Place a file like this
// into your application directory to define the home page.
//
class x_table_x_home extends x_table {
   function main() {
      $this->PageSubtitle = "Welcome to ".V("PageTitle");
      ?>
      <br>
      You are on the default home page.  We need to put something
      interesting here.
      <br>
      <table width=100% height=100%>
         <tr>
            <td style="width: 100%; height: 100%;
                       text-align: center; vertical-align: center;">
               <img src="images/welcome.gif">
            </td>
         </tr>
      </table>
      <?php
   }
}

