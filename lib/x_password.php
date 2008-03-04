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
class x_password extends x_table2 {
	function main() {
        echo "<h1>Password Processing</h1>";
        
        Hidden("gp_page","x_password");
        
        if($GLOBALS['AG']['flag_pwmd5']=='Y') return $this->MainMD5();
        
        $gpp = gp('gpp');
        if (gp('gpp')=='3')       $this->PW_ForgotPage3();  // process new pw
        if (gp('gpp')=='2')       $this->PW_ForgotPage2();  // link back
        if (gp('gpp')=='1')       $this->PW_ForgotPage1();  // send email
        if (Errors() || $gpp=='') $this->PW_ForgotPage0();  // get info
        
	}
	
	function PW_ForgotPage0() {
		// Next time around go to page 1
		vgfSet("HTML_focus","txt_email");

		?>
        <input type="hidden" name="gpp" value="1">
		<p>Please enter your email below so we can process your
		password request.  You will be sent an email that contains your
        username and password.  The email will also contain a link that
        you can use to change your password.</p>
	
		<p>This page is not case-sensitive, so that 'John' or
		'JOHN' or 'john' (and so forth) all mean the same thing.</p>
        <p>Email:
  		   <input name="txt_email" id="txt_email" width="30"
			  value="<?=CleanGet("txt_email","",false)?>"></input>
        </p>
           
		<br><br>
		<button type="submit" value="Submit">Submit</button>

	<?php
	}
		
	function PW_ForgotPage1() {
        // KFD 11/13/06.  Heavily modified for new system, threw out
        //   the older code entirely, now that all apps have a users
        //   table built into them.
        $eml = trim(gp('txt_email'));
        $seml= SQLFC(strtolower($eml));
        $heml= hx($eml);
        $ueml= urlencode($eml);
        //$leml= MakeUserId(strtolower($eml));
        $db2 = scDBConn_Push('usermaint');
        $sq="Select skey,user_id,member_password,email FROM users "
         ." where LOWER(email)=$seml";
        $member=SQL_AllRows($sq);

        // Nothing of any kind is a bummer, we can't do anything
        if (count($member)== 0) {
            ErrorAdd('There are no active accounts with that email address');
        }
        else {
            $leml=MakeUserID($eml);
            $member=$member[0];
            // If we know who they are, send a password and allow them to change it
            $user_pwkey=md5($member['member_password'].$leml.time());
            //$ref=$_SERVER['HTTP_REFERER'];
            $http=httpWebSite()."/";
            $row=array('skey'=>$member['skey'],'user_pwkey'=>$user_pwkey);
            $UID=$member['user_id'];
            $PWD=$member['member_password'];
            // KFD 12/21/06.  Done for medinfo originally.  If UID looks like
            //  the email, send the email instead
            $emailUID=strtolower($UID)==strtolower($leml) ? $leml : $UID;
            $table_dd=DD_Tableref('users');
            SQLX_Update($table_dd,$row);
            $emailuser_id = OptionGet('EMAIL_USERID','N')=='Y' ? $eml : $emailUID;
            $text_email = "
Your username and password are: $emailuser_id and $PWD.
   
If you would like to change your password, click here:
<$http?gp_page=x_password&gpp=2&eml=$ueml&hash=$user_pwkey>
";
            
            scDBConn_Pop();
            //echo $text_email;
            EmailSend($eml,'System Access Request',$text_email);
            ?>
            <b>Email Has Been Sent</b>.  An email has been sent to you with information
            needed to access the system.
            <?php
            gpSet('gpp','X');
        }
    }
      
	function PW_ForgotPage2() {
		$retval=false;
		
		$eml = strtolower(gp('eml'));
      $hash= gp('hash');
			
		// confirm that user/hash is ok
		//
      scDBConn_Push('usermaint');
		$sql="
Select count(*) as cnt 
  FROM users 
 where LOWER(email) =".SQLFC($eml)."
   and user_pwkey   =".SQLFC($hash)."
   and user_disabled<>'Y'";
      $cnt = SQL_OneValue('cnt',$sql);
      if ($cnt==0) {
         ?>
         <p>The link that you provided to this page has either expired, been
			   superseded, or was not valid at all.</p>
  		   <?php
         return;
      }
      
      hidden('eml',$eml);
      hidden('hash',$hash);
		?>
      <input type="hidden" name="gpp"  value="3">
      <input type="hidden" name="hash" value="<?=hSanitize($hash)?>">
		<p>This is the change password page.  Enter your account ID 
         and new password below to change your password.</p>

		<table>
		<tr><td style="text-align: right">User ID:</td>
			 <td style="text-align: left">
			 <input name="uid" id="uid" width="20"></input></td>
			 </tr>
		<tr><td style="text-align: right">New Password:</td>
			 <td style="text-align: left"><input type="password" name="pw1" id="pw1" width="10"></input></td>
			 </tr>
		<tr><td style="text-align: right">Repeat New Password:</td>
			 <td style="text-align: left"><input type="password" name="pw2" id="pw2" width="10"></input></td>
			 </tr>
		</table>
		<br><br>
		<button type="submit">Save New Password</button>
      <?php
      scDBConn_Pop();
	}

	function PW_ForgotPage3() {
		$retval=false;
		
		$UID = gp('uid');
      $eml = gp('eml');
      $hash= gp('hash');
      $pw1 = gp('pw1');
      $pw2 = gp('pw2');
			
      if($pw1<>$pw2)     {ErrorAdd("Password values did not match"); }
      if(strlen($pw1)<5) {ErrorAdd("Password must be at least 5 characters");}
      if(Errors()) {
         echo hErrors();
         gpSet('gpp','2');
         ErrorsClear();
         return;
      }
      
		// confirm that user/hash is ok
		//
      scDBConn_Push('usermaint');
		$sql="
Select count(*) as cnt 
  FROM users 
 where LOWER(email) =".SQLFC($eml)."
   and user_pwkey   =".SQLFC($hash)."
   and user_disabled<>'Y'";
      $cnt = SQL_OneValue('cnt',$sql);
      if ($cnt==0) {
         ErrorAdd("Bad Link, cannot reset password.");
      }
      scDBConn_Pop();
      if (Errors()) {
         echo hErrors();
         gpSet('gpp','2');
         ErrorsClear();
         return;
      }
      
      // All is clear, update the password
      scDBConn_Push('usermaint');
		$sql="
UPDATE users SET member_password=".SQLFC($pw1)."
 WHERE email = ".SQLFC($eml);
      SQL($sql);
      scDBConn_Pop();
      if (Errors()) {
         echo hErrors();
         gpSet('gpp','2');
         ErrorsClear();
      }
      else {
         echo "Password has been reset!";  
      }
      
   }
   
   // ============================================================
   // ============================================================
   // SECOND MAIN BRANCH, WHERE MD5 CHALLENGES ARE REQUIRED
   // ============================================================
   // ============================================================
   function mainMD5() {
      if(gpexists('md5') && !gpExists('gpp')) gpSet('gpp','2');
		$gpp = gp('gpp');
      
      if (gp('gpp')=='3')       $this->MD5_ForgotPage3();  // process new pw
		if (gp('gpp')=='2')       $this->MD5_ForgotPage2();  // link back
		if (gp('gpp')=='1')       $this->MD5_ForgotPage1();  // send email
		if (Errors() || $gpp=='') $this->MD5_ForgotPage0();  // get info
		
		Hidden("gp_page","x_password");
      
   }
   
	function MD5_ForgotPage0() {
		// Next time around go to page 1
		vgfSet("HTML_focus","txt_email");

      echo hErrors();
		?>
      <input type="hidden" name="gpp" value="1">
		<p>Please enter your user_id below so that we can process your
      request.  You will be sent an email that contains
      further instructions.</p>
	
		<p>This page is <b>case-sensitive</b>, so that 'John' is
         not the same thing as 'JOHN' or 'john'.</p>
      <p>User id:
  		   <input name="txt_user_id" id="txt_user_id" width="30"
			  value="<?=CleanGet("txt_user_id","",false)?>"></input>
      </p>
           
		<br><br>
		<button type="submit" value="Submit">Submit</button>

	<?php
	}

	function MD5_ForgotPage1() {
      fwLogEntry('1020','PW Reset Request',gp('txt_user_id'));
      $row=array('user_id'=>gp('txt_user_id'));
      SQLX_Insert('users_pwrequests',$row);
      
      if(!Errors()) {
         ?>
         <p>An email has been sent to the email address that we have on
            file for that user id.  Please consult your email for
            further instructions.
         </p>
         
         <a href="javascript:window.close()">Close This Window</a>
         <?php         
      }
   }
   
	function MD5_ForgotPage2() {
      $md5= gp('md5');
		hidden('gpp',3)	;
      hidden('md5',$md5);
		?>
		<p>This is the change password page.  Enter your account ID 
         and new password below to change your password.</p>

		<table>
		<tr><td style="text-align: right">User ID:</td>
			 <td style="text-align: left">
			 <input name="uid" id="uid" width="20"></input></td>
			 </tr>
		<tr><td style="text-align: right">New Password:</td>
			 <td style="text-align: left"><input type="password" name="pw1" id="pw1" width="10"></input></td>
			 </tr>
		<tr><td style="text-align: right">Repeat New Password:</td>
			 <td style="text-align: left"><input type="password" name="pw2" id="pw2" width="10"></input></td>
			 </tr>
		</table>
		<br><br>
		<button type="submit">Save New Password</button>
      <?php
	}
   
	function MD5_ForgotPage3() {
		
		$UID = gp('uid');
      $md5 = gp('md5');
      $pw1 = gp('pw1');
      $pw2 = gp('pw2');
      fwLogEntry('1025','PW Change Attempt',$UID);
      
      if($pw1<>$pw2)     { ErrorAdd("Password values did not match"); }
      if(strlen($pw1)<6) { ErrorAdd("Password must be at least 5 characters");}
      if(!preg_match("/[0-9]/",$pw1)) {
         ErrorAdd("Password must contain at least one numeric digit");  
      }
      if(!preg_match("/[a-z]/",$pw1)) {
         ErrorAdd("Password must contain at least one lower case character");  
      }
      if(!preg_match("/[A-Z]/",$pw1)) {
         ErrorAdd("Password must contain at least one upper case character");  
      }
      if(strpos(strtolower($pw1),strtolower($UID))!==false) {
         ErrorAdd("You cannot use your user_id in your password!");  
      }
      if(Errors()) {
         echo hErrors();
         gpSet('gpp','2');
         ErrorsClear();
         return;
      }

      $row=array('user_id'=>$UID,'md5'=>$md5,'member_password'=>$pw1);
      SQLX_Insert('users_pwverifies',$row);
      
      
      if(Errors()) {
         echo hErrors();
         gpSet('gpp','2');
         ErrorsClear();
         return;
      }
      else {
         fwLogEntry('1026','PW Change Success',$UID);
         ?>
         <p>Your password has been set, you can now 
            <a href="?gp_page=x_login">Login</a>.
         
         <?php
      }
   }
}
?>

