<?php
// ----------------------------------------------------------------
// JOOMLA-ANDROMEDA Compatibility File.  
// This program will make your joomla template plugin-compatible
//   with Andromeda.
// ----------------------------------------------------------------
// ----------------------------------------------------------------
// IMPLEMENTATION
//
// The joomla functions like mosCountModules() are in raxlib.php,
// and they call out to the functions here. 
//
// Generally speaking, Andromeda does not need all of the modular
// abilities of Joomla, so the code below shuts off the "user" 
// modules, the "top", "debug" and so forth.  Our most common
// scenario is to put the menu on the left, a login box on the
// right, and some kind of credit in the bottom.
// ----------------------------------------------------------------
function tmpCountModules($module_name) {
   if(substr($module_name,0,4)=='user') return false;
   if($module_name=='quicklinks')       return false;
   if($module_name=='right')            return false;
   if($module_name=='commands')         return loggedin();
   return true;
}
function tmpLoadModules($module_name,$var=null) {
   $var=null;  // We do not know what 2nd parm is for
   switch($module_name) {
      case 'left':    tmpModuleLeft();    break;
      case 'right':   tmpModuleRight();   break;
      case 'footer':  tmpModuleFooter();  break;
      case 'top':     tmpModuleTop();     break;
      case 'commands':ehModuleCommands(); break;
   }
}

function tmpModuleTop() {
   echo "<div style='height:6px; overflow:hidden'>&nbsp;</div>";
   if (vgfGet('x4')!==true) {
       ehLoginHorizontal();
   }
}

function tmpModuleLeft() {
   // April 4, 2007, KFD.  Allow a breakout here
   if(function_exists('appModuleLeft')) {
      $continue=appModuleLeft();
      if(!$continue) return;
   }
   
   //echo "<br/>";
   if (!LoggedIn()) return;

   // Only display menu if 
   if(OptionGet('MENULEFT','Y')=='Y') {   
      $module=SessionGet("AGMENU_MODULE");
      $AGMENU=SessionGet("AGMENU");
      $kount=0;
      
      if(isset($AGMENU[$module])) {
         $desc=$AGMENU[$module]['description'];
   
         echo "<div class=\"moduletable\"><h3>$desc</h3></div>";
         echo "<table width=\"100%\">";
         foreach($AGMENU[$module]['items'] as $key=>$info) {
            $hExtra=' tabindex="'.hpTabIndexNext(100).'"';
            // We may make the first item the focused item if nothing else
            // has been picked yet.  This code runs when rendering is going on
            // and the class-specific biz code has all run already, so it will
            // not override any business-specific focus setting
            if(vgfGet('HTML_focus')=='') {
               $hExtra.=' ID="FIRSTSPLITMENU" NAME="FIRSTSPLITMENU" ';
               vgfSet('HTML_focus','FIRSTSPLITMENU');
            }
            
            $kount++;
            $d='?gp_page='.$key;
            $h=hLink("mainlevel",$info['description'],$d,$hExtra);
            echo "\n<tr><td class=\"leftcol\">$h</td></tr>";
   
            // Possibly two more links         
            if(ArraySafe($info,'linknew')=='Y') {
               $hx=' tabindex="'.hpTabIndexNext(100).'" style="margin-left:30px"';
               $h=hLink("mainlevel",'New',$d.'&gp_mode=ins',$hx);
               echo "\n<tr><td class=\"leftcol\">$h</td></tr>";
            }
            if(ArraySafe($info,'linksearch')=='Y') {
               $hx=' tabindex="'.hpTabIndexNext(100).'" style="margin-left:30px"';
               $h=hLink("mainlevel",'Search',$d.'&gp_mode=search',$hx);
               echo "\n<tr><td class=\"leftcol\">$h</td></tr>";
            }
         }
         echo "</table>";
      }
   }
   //while ($kount++ < 30) { echo "<br>"; }
}

function tmpModuleRight() {
   echo "<br>";
   ehFWLogin();
   echo '<br><br><div class="moduletable-hilite"></div>';
   //echo str_repeat('<br>',25);

}
function tmpModuleFooter() {
   ?>
   Site sponsored by <a href="www.secdat.com">Secure Data Software</a>,
   powered by Andromeda.
   <?php
}
?>
