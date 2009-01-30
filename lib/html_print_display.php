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
	<title><?php echo ValueGet("PageTitle")?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

	<style type="text/css">
	/* Styles generated specifically for a page */
	<?php echo ElementOut("styles");?>
	</style>
	<script language="javascript" type="text/javascript">
	/* Script generated specifically for a page */
	<?php echo ElementOut("script");?>
	</script>

</head>
<body>
<?php
ehStandardContent();
echo "</body>";
echo "</html>";
return;
?>

<!-- Beginning -->
<table border="0" cellpadding="0" cellspacing="0" class="andromedabigtable">
	<tr>
		<td class="leftgraybg" align="left">
		<table border="0" cellpadding="0" cellspacing="0" class="topleftgray">
			<tr>
			<td align="center" class="topleftmargin">
				<?php echo ValueGet("PageTitle")?>
				<br />
				<img src="images/hrthing.jpg" width="200" height="2" border="0" alt="hrthing" />
				<br />
				<?php echo date('M-d-Y g:ia',time())?> (NY Time)
				<br />
            <?php
            $uid = SessionGet("UID");
            if ($uid)
               echo "You are logged in as: <b>$uid</b>";
            else
               echo 'You are not logged in'; 
            ?>
				<br />
				<img src="images/hrthing.jpg" width="200" height="2" border="0" alt="hrthing" />
				<br />
				<a href="index.php?st2logout=logout" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('logoutset','','images/logoutup.jpg',0)"><img src="images/logoutdown.jpg" alt="Log Out" name="logoutset" width="99" height="19" border="0" id="logoutset" /></a>
				<a href="index.php" target="_blank"  onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('newwindow','','images/newwindowup.jpg',0)"><img src="images/newwindowdown.jpg" alt="New Window" name="newwindow" width="99" height="19" border="0" id="newwindow" /></a>
			</td>
			</tr>
			
			<tr>
			<td class="leftmenumoduleposition">
				<?php echo ehStandardMenu()?>
			</td>
			</tr>

			<tr>
			<td class="leftgraybottom">
				<img src="images/leftgraybottom.jpg" alt="leftgraybottom grill" width="215" height="89" border="0" />
			</td>
			</tr>
		</table>
		</td>
		
		<td valign="top" bgcolor="green" style="text-align: left">
			<table cellspacing="0" cellpadding="0" height="100%" width="100%">
				<tr>
					<td height=50px>
						<table width=100% cellspacing=0 cellpadding=0 height=100%>
							<tr>
								<td class="btop-nw"></td>
								<td class="btop-filler"></td>
								<td class="btop-ttl-left"></td>
								<td class="btop-ttl-mid">
									<font class="nameoftable">
									<?php echo V("PageSubtitle")?>
									</font></td>
								<td class="btop-ttl-right"></td>
								<td class="btop-filler"></td>
								<td class="btop-ne"></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table width=100% cellspacing=0 cellpadding=0  height=100%>
							<tr>
								<td class="bmid-left"></td>
								<!-- for 800x600 displays, use width=550 -->
								<td class="bmid-center" colspan=5 width=720px valign="top">
									<?php echo $HTML_Message?>

									<?php 
									$HTML_nots = HTMLX_Notices();
									if ($HTML_nots) {
										if ($HTML_Message) echo "<br>"; 
									?>
										<center>
										<table cellpadding="0" cellspacing="0" class="noticebox">
											<tr>
												<td class="noticepic"></td>
												<td class="noticetext">
													<?php echo $HTML_nots?>
												</td>
											</tr>
										</table>
										</center>
									<?php } ?>

									
									<?php 
									$HTML_errs = HTMLX_Errors();
									if ($HTML_errs) {
									?>
										<center>
										<table cellpadding="0" cellspacing="0" class="errorbox">
											<tr>
												<td class="errorpic"></td>
												<td class="errortext">
													<?php echo $HTML_errs?>
												</td>
											</tr>
										</table>
										</center>
									<?php } ?>
					
								
									<form method="post" enctype="multipart/form-data"
                                 action="index.php"  
                                 id="Form1" 
                                 name="Form1">
				
									<?php echo ValueGet("HTML")?>
                           <?php echo ehHiddenAndData()?>
									</form>
								</td>
								<td class="bmid-right"></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td height=50px>
						<table width=100% cellspacing=0 cellpadding=0  height=100%>
							<tr>
								<td class="bbot-sw"></td>
								<td class="bbot-center">&nbsp;</td>
								<td class="bbot-se"></td>
							</tr>
						</table>
					</td>
				</tr>
		  </table>
		</td>
	</tr>
</table>
<!-- Ending -->
</body>
</html>

