<?php
/*
 * process.php
 *
 * PHP Toolkit for PayPal v0.51
 * http://www.paypal.com/pdn
 *
 * Copyright (c) 2004 PayPal Inc
 *
 * Released under Common Public License 1.0
 * http://opensource.org/licenses/cpl.php
 *
 * Andromeda Mods Feb 13, 2006:
 * -> This file, process.php -> lib/html_main_paypal_process.php
 * -> Changed includes as indicated below
 * -> Changed form action, removed embedded constant
 *
 */

//Configuration File
// Andromeda MOD Feb 13, 2006, expects this file in application dir
include_once('paypal_config.inc.php'); 
//include_once('includes/config.inc.php'); 

//Global Configuration File
// Andromeda MOD Feb 13, 2006, expects this file in lib dir
include_once('paypal_global_config.inc.php');
//include_once('includes/global_config.inc.php');

?> 

<html>
<head><title>::PHP PayPal::</title></head>
<body onLoad="document.paypal_form.submit();">
<form method="post" name="paypal_form" action="<?php echo $paypal['url']?>">
<?php 
//show paypal hidden variables

showVariables(); 

?> 
<center><font face="Verdana, Arial, Helvetica, sans-serif" size="2" color="333333">Processing Transaction . . . </font></center>

</form>
</body>   
</html>
