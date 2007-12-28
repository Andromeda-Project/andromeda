<?php
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );
require($mosConfig_absolute_path."/templates/" . $mainframe->getTemplate() . "/rt_styleswitcher.php");
$iso = split( '=', _ISO );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php
if ( $my->id ) {
	initEditor();
}
mosShowHead();

// ***************************************************
// Change this variable blow to configure the template
//
// If you have any issues, check out the forum at
// http://www.rockettheme.com
//
// ***************************************************
$menu_type = "suckerfish";	// suckerfish  | module
$menu_name = "mainmenu";	// mainmenu by default, can be any Joomla menu name
$default_width = "wide";		// wide | thin | fluid
$default_font = "default";         // smaller | default | larger
$default_color = "blue";           // red | blue | green | orange
$default_contrast = "light";	// light | med | dark
$show_access = "true";            // true | false
$show_menu = "true";             // true | false
$show_pathway = "false";        // true | false

// *************************************************

if ($menu_type != "module") {
	require($mosConfig_absolute_path."/templates/" . $mainframe->getTemplate() . "/rt_suckerfish.php");
}
require($mosConfig_absolute_path."/templates/" . $mainframe->getTemplate() . "/rt_styleloader.php");


// *************************************************
?>
<meta http-equiv="Content-Type" content="text/html; <?php echo _ISO; ?>" />
<link rel="alternate" type="application/rss+xml" title="RSS" href="<?php echo $mosConfig_live_site;?>/index.php?option=com_rss&amp;feed=RSS2.0&amp;no_html=1" />
<link href="<?php echo $mosConfig_live_site;?>/templates/<?php echo $mainframe->getTemplate(); ?>/css/template_css.css" rel="stylesheet" type="text/css" />
<link href="<?php echo $mosConfig_live_site;?>/templates/<?php echo $mainframe->getTemplate(); ?>/css/<?php echo $contraststyle; ?>.css" rel="stylesheet" type="text/css" />
<link href="<?php echo $mosConfig_live_site;?>/templates/<?php echo $mainframe->getTemplate(); ?>/css/<?php echo $colorstyle; ?>.css" rel="stylesheet" type="text/css" />
<link href="<?php echo $mosConfig_live_site;?>/templates/<?php echo $mainframe->getTemplate(); ?>/css/suckerfish.css" rel="stylesheet" type="text/css" />
<!--[if lte IE 6]>
<link href="<?php echo $mosConfig_live_site;?>/templates/<?php echo $mainframe->getTemplate(); ?>/css/template_ie.css" rel="stylesheet" type="text/css" />
<![endif]-->
<link rel="shortcut icon" href="<?php echo $mosConfig_live_site;?>/favicon.ico" />
<!-- ================= -->
<!-- Andromeda Changes -->
<!-- ================= -->
<?php
if(defined("_ANDROMEDA_JOOMLA")) { 
   include("raxlib-htmlhead.php");   
?>
<link href="<?php echo $mosConfig_live_site.'/templates/'.$mainframe->getTemplate().'/css/x2-'.$colorstyle.'.css'?>" rel="stylesheet" type="text/css" />
<link href="<?php echo $mosConfig_live_site.'/templates/'.$mainframe->getTemplate().'/css/x2-'.$contraststyle.'.css'?>" rel="stylesheet" type="text/css" />
<?php } ?>
<!-- ======================= -->
<!-- Andromeda Changes (END) -->
<!-- ======================= -->


<!-- Andromeda Changes (END) -->
</head>
<body class="<?php echo $colorstyle; ?> <?php echo $widthstyle; ?> <?php echo $fontstyle; ?>"
   onkeypress="bodyKeyPress(event)">
	<div class="wrapper">
		<div id="outer-border" >
      <?php /* ANDROMEDA CHANGE: MAKE TOP-HEAD AN OPTIONAL MODULE */ ?>
      <?php if(mosCountModules('top-head',-1)) { ?>
			<div id="top-head">
				<a href="<?php echo $mosConfig_live_site;?>" title=""><span id="logo" style="font-size:40px;">&nbsp;</span></a>
				<!-- <div id="color-insert"></div> -->
				<div id="access-bar">
				  <?php if($show_access=="true") { ?>
					<div id="access">
	  				<div id="buttons">
							<a href="<?php echo $thisurl; ?>widthstyle=w-fluid" title="Fluid width" class="fluid"><span class="button">&nbsp;</span></a>
	  					<a href="<?php echo $thisurl; ?>widthstyle=w-wide" title="Wide width" class="wide"><span class="button">&nbsp;</span></a>
	  					<a href="<?php echo $thisurl; ?>widthstyle=w-thin" title="Narrow width" class="thin"><span class="button">&nbsp;</span></a>
							<span class="spacer">&nbsp;</span>
	  					<a href="<?php echo $thisurl; ?>fontstyle=f-larger" title="Increase size" class="large"><span class="button">&nbsp;</span></a>
	  					<a href="<?php echo $thisurl; ?>fontstyle=f-default" title="Default size" class="default"><span class="button">&nbsp;</span></a>
	  					<a href="<?php echo $thisurl; ?>fontstyle=f-smaller" title="Decrease size" class="small"><span class="button">&nbsp;</span></a>
							<span class="spacer">&nbsp;</span>
							<a href="<?php echo $thisurl; ?>colorstyle=orange" title="Orange color" class="orange"><span class="button">&nbsp;</span></a>
							<a href="<?php echo $thisurl; ?>colorstyle=green" title="Green color" class="green"><span class="button">&nbsp;</span></a>
							<a href="<?php echo $thisurl; ?>colorstyle=blue" title="Blue color" class="blue"><span class="button">&nbsp;</span></a>
							<a href="<?php echo $thisurl; ?>colorstyle=red" title="Red color" class="red"><span class="button">&nbsp;</span></a>
							<span class="spacer">&nbsp;</span>
							<a href="<?php echo $thisurl; ?>contraststyle=co-dark" title="Dark contrast" class="dark"><span class="button">&nbsp;</span></a>
							<a href="<?php echo $thisurl; ?>contraststyle=co-med" title="Medium contrast" class="med"><span class="button">&nbsp;</span></a>
							<a href="<?php echo $thisurl; ?>contraststyle=co-light" title="Light contrast" class="light"><span class="button">&nbsp;</span></a>
	  				</div>
					</div>
					<?php } ?>
				</div>
				<div id="top-mod">
					<?php mosLoadModules('top', -1); ?>
				</div>
			</div>
         <?php } /* mosloadmodules('top-head') */ ?>
         
			<?php if($show_menu=="true") { ?>
			<div id="horiz-menu">
				<div id="nav">
        	        	<script type="text/javascript">
<!--//--><![CDATA[//><!--

sfHover = function() {
	var sfEls = document.getElementById("nav").getElementsByTagName("LI");
	for (var i=0; i<sfEls.length; i++) {
		sfEls[i].onmouseover=function() {
			this.className+=" sfhover";
		}
		sfEls[i].onmouseout=function() {
			this.className=this.className.replace(new RegExp(" sfhover\\b"), "");
		}
	}
}
if (window.attachEvent) window.attachEvent("onload", sfHover);
//--><!]]>
</script>             
        	<?php if ($menu_type == "module") mosLoadModules('toolbar'); ?>
        	<?php if ($menu_type == "suckerfish") mosShowListMenu($menu_name);	?>
  			</div>
			</div>
			<div class="clr"></div>
         <?php /* Andromeda change, add a line of links */ ?>
         <?php if (mosCountModules('quicklinks')) { ?> 
         <div id="quicklinks">
           <?=mosLoadModules('quicklinks') ?>
         </div>
         <?php } ?>

         <?php /* Andromeda change, add a spot for commands */ ?>
         <?php if (mosCountModules('commands')) { ?> 
         <div id="quicklinks">
           <?=mosLoadModules('commands') ?>
         </div>
         <?php } ?>

         
         <?php } ?>
			<table class="outer" cellspacing="0">
				<tr valign="top">
				   <?php if (mosCountModules('left')) { ?>
					<td class="left">
					 <div class="sidepad">
					   <?php mosLoadModules('left', -2); ?>
					 </div>
					</td>
					<?php } ?>
					<td class="middle">
					  <?php if ($show_pathway == "true") { ?>
					  <div class="path-padding">
              <?php mosPathway(); ?>
            </div>
            <?php } ?> 
            <?php if (mosCountModules('user1') || mosCountModules('user2') || mosCountModules('user3')) { ?>
            <div class="smallpad">
  					  <table class="nopad" cellspacing="0">
  					    <tr valign="top">
  					      <?php if (mosCountModules('user1')) { ?>
  					      <td>
  					         <?php mosLoadModules('user1', -2); ?>
  					      </td>
  					      <?php } ?> 
  					      <?php if (mosCountModules('user2')) { ?>
  					      <td>
  					       <?php mosLoadModules('user2', -2); ?>
  					      </td>
  					      <?php } ?> 
  					      <?php if (mosCountModules('user3')) { ?>
  					      <td>
  					       <?php mosLoadModules('user3', -2); ?>
  					      </td>
  					      <?php } ?> 
  					    </tr>
  					  </table>
					  </div>
					  <?php } ?> 
					  <?php if (mosCountModules('user4') || mosCountModules('user5') || mosCountModules('user6')) { ?>
						<div class="midbox">
							<table class="nopad" cellspacing="0">
						    <tr valign="top">
						      <?php if (mosCountModules('user4')) { ?>
						      <td>
						        <?php mosLoadModules('user4', -2); ?>
						      </td>
						      <?php } ?>
						      <?php if (mosCountModules('user5')) { ?>
						      <td>
						        <?php mosLoadModules('user5', -2); ?>
						      </td>
						      <?php } ?>
						      <?php if (mosCountModules('user6')) { ?>
						      <td>
						        <?php mosLoadModules('user6', -2); ?>
						      </td>
						      <?php } ?>
						    </tr>
						  </table>
						</div>
						<?php } ?> 
						<div class="padding" id="andromeda_main_content">
								<?php mosMainbody(); ?>
						</div>
						<?php if (mosCountModules('user7') || mosCountModules('user8') || mosCountModules('user9')) { ?>
						<div class="botbox">
							<table class="nopad" cellspacing="0">
						    <tr valign="top">
						      <?php if (mosCountModules('user7')) { ?>
						      <td>
						        <?php mosLoadModules('user7', -2); ?>
						      </td>
						      <?php } ?>
						      <?php if (mosCountModules('user8')) { ?>
						      <td>
						        <?php mosLoadModules('user8', -2); ?>
						      </td>
						      <?php } ?>
						      <?php if (mosCountModules('user9')) { ?>
						      <td>
						        <?php mosLoadModules('user9', -2); ?>
						      </td>
						      <?php } ?>
						    </tr>
						  </table>
						</div>
						<?php } ?> 
					</td>
					<?php if (mosCountModules('right')) { ?>
					<td class="right">
					 <div class="sidepad">
					   <?php mosLoadModules('right', -2); ?>
					 </div>
					</td>
					<?php } ?>
				</tr>
			</table>
			<div id="bot-footer">
				<?php mosLoadModules('footer', -1); ?>
			</div>
		</div>
		<div id="bot-rocket">
			<a href="http://www.rockettheme.com"><span class="rocket">&nbsp;</span></a>
		</div>
	</div>

<?php mosLoadModules( 'debug', -1 );?>
<!-- ================= -->
<!-- Andromeda Changes -->
<!-- ================= -->
<?php
include('raxlib-htmlfoot.php');
?>
<script>
var firebug='watch me!'
</script>
<!-- ======================= -->
<!-- Andromeda Changes (END) -->
<!-- ======================= -->
</body>
</html>