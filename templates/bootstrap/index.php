<?php defined( '_VALID_MOS' ) 
    or die( 'Direct Access to this location is not allowed.' );
?>
<?php 
# ========================================================
# The templating system expects these functions
# ========================================================
function tmpCountModules($module) { return true; }
function tmpLoadModules($module_name,$var=null) {
   $var=null;  // We do not know what 2nd parm is for
   switch($module_name) {
      case 'left':     return tmpModuleLeft();
      case 'right':    tmpModuleRight();    break;
      case 'top':      tmpModuleTop();      break;
      case 'commands': ehModuleCommands();  break;
      case 'menuright':fwModuleMenuRight();break;
   }
}
function loadMenu() {
    if (LoggedIn()) {
        $dir = dirname(__FILE__);
        include $dir."/menu.php";        
    }
}

function tmpModuleLeft() {
   // April 4, 2007, KFD.  Allow a breakout here
   if(function_exists('appModuleLeft')) {
      $continue=appModuleLeft();
      return $continue;
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
   
			
			echo "<ul class=\"nav nav-list\">";
			echo "<li class=\"nav-header\">$desc</li>";
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
				echo "\n<li>$h</li>";
	   
				// Possibly two more links         
				if(ArraySafe($info,'linknew')=='Y') {
				   $hx=' tabindex="'.hpTabIndexNext(100).'" style="margin-left:30px"';
				   $h=hLink("mainlevel",'New',$d.'&gp_mode=ins',$hx);
				   echo "\n<li>$h</td></li>";
				}
				if(ArraySafe($info,'linksearch')=='Y') {
				   $hx=' tabindex="'.hpTabIndexNext(100).'" style="margin-left:30px"';
				   $h=hLink("mainlevel",'Search',$d.'&gp_mode=search',$hx);
				   echo "\n<li>$h</li>";
				}
			 }
			 echo "</ul>";
		}
	}
	if (isset($continue)) {
		return $continue;
	}
   //while ($kount++ < 30) { echo "<br>"; }
}
# ========================================================
# Get some cookie stuff taken care of before header
# ========================================================
$template = $mainframe->getTemplate();

$app   = $GLOBALS['AG']['application'];
if(gpExists('p2c')) {
    $color = gp('p2c');
    setCookie($app."_color",hx($color),strtotime("+5 years",time()));
}
else {
    $color = a($_REQUEST,$app."_color",'blue');
}
if(gpExists('p2s')) {
    $size  = gp('p2s');
    setCookie($app."_size",hx($size),strtotime("+5 years",time()));
}
else {
    $size  = a($_REQUEST,$app."_size",'1024');
}

?>
<!DOCTYPE html>
<html lang="en">
	<head>
<?php

cssInclude("templates/$template/bootstrap/css/bootstrap.min.css");
cssInclude("templates/$template/bootstrap/css/bootstrap-responsive.min.css");
cssInclude("templates/$template/bootstrap/datepicker/css/datepicker.css");
include('androHTMLHead.php');
?>
</head>
<body>
	<div class="navbar navbar-inverse navbar-fixed-top">
		<div class="navbar-inner">
			<div class="container-fluid">
				<button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse"> <span class="icon-bar"></span>
	 <span class="icon-bar"></span>
	 <span class="icon-bar"></span>

				</button> <a class="brand" href=""><?php echo $GLOBALS['AG']['app_desc']; ?></a>
				<div class="nav-collapse collapse">
					<?php
						if (LoggedIn()) {
					?>
					<ul class="nav pull-right">
						<li class="divider-vertical"></li>
						<li class="dropdown">
							<a href="" class="dropdown-toggle" data-toggle="dropdown">
								Logged in as <?php echo SessionGet('UID')?>
								<b class="caret"></b>
							</a>
							<ul class="dropdown-menu">
								<li><a href="?st2logout=1">Logout</a></li>
							</ul>
						</li>
					</ul>
					<?php
						}
					?>
					<?php echo(loadMenu());?>
				</div>
			</div>
		</div>
	</div>	
	<div class="container-fluid">	
		<div class="row-fluid">
			<?php 
				if (mosCountModules('left')) {
					$left = mosLoadModules('left', -2);
					if (is_null($left)) {
						$left = false;
					}
					if ($left) {
						echo '<div class="span3" style="clear:both;">';
							echo '<div class="well sidebar-nav">';
							echo $left;
							echo '</div>';
						echo '</div>';
					}
				} 
				echo '<div class=" test span' .($left === false ? '12' : '9')  .'" style="padding-bottom:15px;">';
					
				if (mosCountModules('commands')) {
			?>
					<div class="container" style="padding-bottom:15px;">
						<div class="span<?php echo ($left === false ? '12' : '9') ?>">
							<?php
								mosLoadModules('commands');
							?>
						</div>
					</div>
			<?php
				}
				echo mosMainBody();
				echo '</div>';
			?>
		</div>
	</div>
	<div class="templatefooter">
	<?php echo mosLoadModules('footer');?>
	</div>
<?php
	jsInclude("templates/$template/bootstrap/js/bootstrap.min.js");
	jsInclude("templates/$template/bootstrap/datepicker/js/bootstrap-datepicker.js");
	jsInclude("clib/androLib.js");
	jsInclude("clib/androLibDeprecated.js");
	jsInclude("clib/x4.js");
	include('androHTMLFoot.php'); 
?>
</body>
</html>
