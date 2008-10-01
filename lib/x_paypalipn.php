<?php
/*
 * ipn.php
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
 * Andromeda MODS May 15, 2006
 * -> converted into a class based on x_table2, put processing into
 *    function main()
 * -> replaced unquoted array index names w/quoted array index names
 * -> removed explicit paths to configuration files
 * ---------------------------------------------------------------
 *
 */
class x_paypalipn extends x_table2 {
   function main() {    
      //get global configuration information

      // Debugging.  If you want to trace through what happens,
      // turn this on here. THen turn on the "file_put_contents" at
      // the bottom of the method.
      $log = SysLogOpen('IPN'); 
      sysLogEntry($log,'Began work in x_paypalipn, next entry is $_POST');
      
      # KFD 10/1/08, add more logging.  Project CME occassionally
      # misses the trx, we want more logging.
      ob_start();
      hprint_r($_POST);
      sysLogEntry($log,ob_get_clean());
      sysLogEntry($log,'Invoice '.a($_POST,'invoice',''));
      
      global $paypal;
      include_once('paypal_global_config.inc.php'); 
      
      //get pay pal configuration file
      include_once('paypal_config.inc.php'); 
      
      
      //decide which post method to use
      sysLogEntry($log,'Paypal postmethod is '.$paypal['post_method']);
      switch($paypal['post_method']) { 
         case "libCurl": //php compiled with libCurl support
            $result=libCurlPost($paypal['url'],$_POST); 
            break;
         case "curl": //cURL via command line
            $result=curlPost($paypal['url'],$_POST); 
            break; 
         case "fso": //php fsockopen(); 
            $result=fsockPost($paypal['url'],$_POST); 
            break; 
         default: //use the fsockopen method as default post method
            $result=fsockPost($paypal['url'],$_POST);
            break;
      }

      sysLogEntry($log,"Next log entry is postback result");
      sysLogEntry($log,$result);
      
      //check the ipn result received back from paypal
      if(eregi("VERIFIED",$result)) {
         sysLogEntry($log,"Verified, continuing");
         if(function_exists('paypal_ipn_success')) {
            sysLogEntry($log
               ,"Calling function paypal_ipn_success"
            );
            paypal_ipn_success($log);           
         }
         else {
            sysLogEntry($log
               ,"ERROR, function paypal_ipn_success does not exist"
            );
         }
      } 
      else {
         sysLogEntry($log,"Not verified, no further action");
         if (function_exists('paypal_ipn_cancel')) {
            sysLogEntry($log
               ,"Calling function paypal_ipn_cancel"
            );
            paypal_ipn_cancel($log);
         }
         else {
            sysLogEntry($log
               ,"Function paypal_ipn_cancel does not exist"
            );
         }
      }
      
      syslogEntry($log,'end of processing');
      sysLogClose($log);
   }
}   
?>

