<?php
/*
 * config.inc.php
 *
 * PHP Toolkit for PayPal v0.51
 * http://www.paypal.com/pdn
 *
 * Copyright (c) 2004 PayPal Inc
 *
 * Released under Common Public License 1.0
 * http://opensource.org/licenses/cpl.php
 *
 * --------------------------------------------------------------- 
 * Andromeda MODS Feb 13, 2006
 * Replaced unquoted array index names with quoted array index names  
 *
 *
 * COPY THIS FILE W/O RENAMING FROM /LIB to /APPLICATION.
 *
 * MAKE CHANGES IN THE /APPLICATION COPY OF THE FILE
 * ---------------------------------------------------------------
 *
 */

// Andromeda MOD, better compatibility
global $paypal;

# KFD 9/13/08, added configuration to discard ship-to info
if(configGet('paypal_noship','N') == 'Y') {
    $paypal['no_shipping'] = 1;
}

// -----------------------------------------------------------------
// STANDARD SETTINGS.  CHANGE THESE TWO SETTINGS AFTER THIS
// FILE HAS BEEN COPIED TO THE APPLICATION DIRECTORY.
// -----------------------------------------------------------------
$paypal['business']="example@example.com";
# KFD 9/6/08, allow overrides from configuration
if(configGet('paypal_payto','')<>'') {
    $paypal['business'] = configGet('paypal_payto');   
}

$paypal['site_url']=
   'http://'.$GLOBALS['_SERVER']['HTTP_HOST']
   .$GLOBALS['_SERVER']['REQUEST_URI'];
 
# KFD 9/6/08, allow overrides from configuration
if(configGet('paypal_site_url','') <> '') {
    $paypal['site_url'] = configGet('paypal_site_url');
}
   
$paypal['success_url']="?gpt=pp&flag=1";  // after ipn has finished
$paypal['cancel_url']="?gpt=pp&flag=0";   // after ipn has finished

# KFD 9/6/08, allow overrides from configuration
if(configGet('paypal_v2','N')=='Y') {
    $paypal['success_url'] = '?gp_page=x_paypalfinal&flag=success';
    $paypal['cancel_url'] = '?gp_page=x_paypalfinal&flag=cancel';
}    

// -----------------------------------------------------------------
// FLIP BETWEEN THESE TWO FOR LIVE/TEST 
// -----------------------------------------------------------------
# KFD 9/6/08, allow overrides from configuration
if(configGet('paypal_test_mode','N')=='Y') {
    $paypal['url']="https://www.sandbox.paypal.com/cgi-bin/webscr"; 
}
else {
    $paypal['url']="https://www.paypal.com/cgi-bin/webscr";    
}

// -----------------------------------------------------------------
// MANY SETTINGS BELOW ARE REM'D OUT IN THE STANDARD ARRANGEMENT,
// ADD BACK IN ANYTHING YOU NEED FOR AN APPLICATION
// -----------------------------------------------------------------
//  An icon user sees while on paypal, personalizes it a bit
$paypal['image_url']=configGet('paypal_image_url','');
 // url to call in background while user is waiting
$paypal['notify_url']="?gp_page=x_paypalipn"; 
                                                
$paypal['return_method']="1"; //1=GET 2=POST
$paypal['currency_code']="USD"; //[USD,GBP,JPY,CAD,EUR]
$paypal['lc']="US";

//fso=fsockopen(); curl=curl command line 
//libCurl=php compiled with libCurl support
$paypal['post_method']="fso"; 
$paypal['curl_location']="/usr/local/bin/curl";

$paypal['bn']="toolkit-php";
$paypal['cmd']="_xclick";

//Payment Page Settings
$paypal['display_comment']="0"; //0=yes 1=no
$paypal['comment_header']="Comments";
$paypal['continue_button_text']="Continue >>";
$paypal['background_color']=""; //""=white 1=black
$paypal['display_shipping_address']=""; //""=yes 1=no
$paypal['display_comment']="1"; //""=yes 1=no


/*

//Product Settings
$paypal['item_name']="$_POST[item_name]";
$paypal['item_number']="$_POST[item_number]";
$paypal['amount']="$_POST[amount]";
$paypal['on0']="$_POST[on0]";
$paypal['os0']="$_POST[os0]";
$paypal['on1']="$_POST[on1]";
$paypal['os1']="$_POST[os1]";
$paypal['quantity']="$_POST[quantity]";
$paypal['edit_quantity']=""; //1=yes ""=no
$paypal['invoice']="$_POST[invoice]";
$paypal['tax']="$_POST[tax]";

//Shipping and Taxes
$paypal['shipping_amount']="$_POST[shipping_amount]";
$paypal['shipping_amount_per_item']="";
$paypal['handling_amount']="";
$paypal['custom_field']="";

//Customer Settings
$paypal['firstname']="$_POST[firstname]";
$paypal['lastname']="$_POST[lastname]";
$paypal['address1']="$_POST[address1]";
$paypal['address2']="$_POST[address2]";
$paypal['city']="$_POST[city]";
$paypal['state']="$_POST[state]";
$paypal['zip']="$_POST[zip]";
$paypal['email']="$_POST[email]";
$paypal['phone_1']="$_POST[phone1]";
$paypal['phone_2']="$_POST[phone2]";
$paypal['phone_3']="$_POST[phone3]";
*/

?>