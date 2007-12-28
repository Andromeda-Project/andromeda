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
?>
<?php echo "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?".">"; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?=ValueGet("PageTitle")?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<link   type="text/css"        href="templates/andro_classic/skin-tc.css" rel="stylesheet" />
	<link   type="text/css"        href="templates/andro_classic/x2.css"      rel="stylesheet" />
   <link rel="shortcut icon" href="templates/andro_classic/favicon.ico" />
   
   <?php
   include("raxlib-htmlhead.php");   
   ?>


   <!-- Special, added 6/8/06, not present on all systems -->
   <script type="text/javascript">
       _editor_url  = "htmleditor/"  // (preferably absolute) URL
       _editor_lang = "en";      // And the language we need to use in the editor.
   </script>
   <script type="text/javascript" src="htmleditor/editor.js"></script>
</head>
<body>
<!-- Beginning -->
<table cellpadding=0 cellspacing=0 class="tc-maintab">
	<tr>
		<td id="tc-left">
         <table cellpadding=0 cellspacing=0 class="tc-maintab">
           <tr><td class="menufiller" style="height: 5px">
               <center>
               <img src="templates/andro_classic/andro-logo.gif">
               </center>
               <br>
               <hr style="width: 85%">
               <center>
               <?php
               if(LoggedIn()) {
                  echo "Logged in as: ".SessionGet('UID');
                  echo "<br>";
                  echo "<a href='?st2logout=1'>Logout</a>";
                  //if(SessionGet('root')) {
                     echo "<br><a href='?celltoggle=1'>Toggle Cell Mode</a>";
                  //}
                  $link=vgaGet('menu_preferences','');
                  if($link<>'') {
                     echo "&nbsp;&nbsp;&nbsp;";
                     echo "<a href=\"javascript:formPostString('$link')\">";
                     echo "Preferences</a>";
                     
                  }
               }
               else {
                   echo "not logged in";  
               }
               ?>
               </center>
               <hr style="width: 85%">
               </td></tr>
               <?php
                   $menufile = 'menu_'.SessionGet('UID').'.php';
                   if (FILE_EXISTS_INCPATH($menufile)){
                     include ($menufile); 
                   }
               ?>
            <tr><td class="menufiller">
                <br><br><br>
                </td></tr>
         </table>
      </td>
      <td id="tc-right">
        <?=ehStandardContent()?>
      </td>
   </tr>
</table>
<!-- This plus body:height95% gives perm scroll bar-->
<br><br><br>
<!-- Ending -->
<?php
// If a different menu has been forced, use that
$gm=vgaGet('menu_selected','');
if($gm<>'') {
   $gp='menu_' .($gm<>'' ? $gm : gp('gp_page'));
   ?>
   <script>
     ob("<?=$gp?>").className="menuselected";
   </script>
   <?php
}
// Script goes out at absolute end, after <html> element is closed
if (vgfGet("HTML_focus")<>"") {
   ?>
   <script>
   ob('<?=vgfGet("HTML_focus")?>').focus();
   </script>
   <?php
}
if (Element("scriptend")) {
	echo '<script language="javascript" type="text/javascript">'."\n";
	echo ElementOut("scriptend")."\n";
	echo "</script>\n";
}
?>
</body>
</html>

