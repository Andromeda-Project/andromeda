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
/* ==================================================================
   APP_Post.php
	
	Library to process EMAIL.  Detects three states:
	-> $AG["email"] exists, execute a send immediately
	-> $AG["clean"]["emailto"] exists, a postback, read vars and send
	-> otherwise, put up HTML form to user to send test email
	
	Revisions:
	Apr 29 2005  Modified for new gp vars and x_page class system
	Mar 18 2005  CLASSified
	Feb 23 2005  Revised for new state system
	Feb 16 2005  Created, outlined, drafted
   ================================================================== 
*/
class x_table_x_email extends x_table {
	
	function main() {
		$this->PageSubtitle="Send a Test Email";
		include_once("ddtable_adm_emails.php");

		Hidden("gp_page","x_email");	
		
		$table = &$GLOBALS["AG"]["tables"]["adm_emails"];
		$em = array(
			"email_to"=>CleanBox("email_to","",false),
			"email_subject"=>CleanBox("email_subject","",false),
			"email_message"=>CleanBox("email_message","",false));

		// If this is postback, process the email before putting
		// the boxes back up.
		//
		if ($em["email_to"]) {
			X_EMAIL_SEND($em);
			echo "<p>The email to ".$em["email_to"]." has been accepted ".
				"by the email server, please check your email system ".
				"to see if the server has successfully sent the email.</p>".
				"You may also ".HTMLE_A_STD("View Sent Emails",'adm_emails').
				" or ".HTMLE_A_STD("Send another email",'x_email')."</p>";
			return;
		}

		// In this little trick we set "uino" to all of the columns
		// except the ones we are setting.
		foreach ($table["flat"] as $colname=>$colinfo) {
			if ($colname=="email_to" || $colname=="email_subject" || $colname=="email_message")
				continue;
			$table["flat"][$colname]["uino"] = "Y";
		}
			
		?>
		<?php echo HTMLX_Errors(); ?>
		<?php echo ElementOut("msg",true); ?>
		
		
		<p>This is a very minimal page that can be used to verify that this system
			can send emails.  Enter a valid address, subject, and short message below
			and then click on the [SEND] button.</p>
			
		<table>
		<?=$this->HTML_INPUTS($GLOBALS["AG"]["tables"]["adm_emails"],$em,"ins",true);?>
		</table>
		
		
		<br>
		<br>
		<?=HTMLE_A_JS("formSubmit()","Send Email Now");?>
		<?php
	}
}

// =======================================================================
// send the email.  Library function, not part of the class
// =======================================================================

function X_EMAIL_SEND($em)	{
   $retval = false;
   //scDBConn_Push('admin');
	if (SQLX_TrxLevel() > 0) {
		ErrorAdd("ERROR: Cannot send an email within a transaction");
	}
	else {
		$from_addr  = trim(OPTION_GET("EMAILFROM_ADDR"));
		$from_name  = trim(OPTION_GET("EMAILFROM_NAME"));
      $smtp_server= trim(OPTION_GET('SMTP_SERVER','localhost'));
		if ($from_addr=="") {
			ErrorAdd("The system's return email address, defined in system variable ".
				"EMAILFROM_ADDR, must be set to a valid email address.  ".  
				HTMLE_A_STD("System Variables","variables",""));
		}
		else {
			if ($from_name!="") { $from_name = '"'.$from_name.'"'; }
			$from = "From: ".$from_name." <".$from_addr.">";
			
			include_once('Mail.php');
			$recipients =$em["email_to"];
			$headers['From']    = $from_name. "<".$from_addr.">";
			$headers['To']      = $em["email_to"];
			$headers['Subject'] = $em["email_subject"];
			$headers['Date']    = date("D, j M Y H:i:s O",time());
			$body = $em["email_message"];
			$params['sendmail_path'] = '/usr/lib/sendmail';
         $params['host'] = $smtp_server;
			// Create the mail object using the Mail::factory method
			$mail_object = Mail::factory('smtp', $params);
			$mail_object->send($recipients, $headers, $body);
		
			if (!$mail_object) {
				ErrorAdd("Email was not accepted by server");
			}
			else {
				$table_ref =DD_TableRef('adm_emails');
				SQLX_Insert($table_ref,$em,false);
				$retval=false;
			}
		}
	}
   //scDBConn_Pop();
   return $retval;
}
?>
