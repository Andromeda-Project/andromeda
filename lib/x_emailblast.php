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
class x_table_x_emailblast extends x_table {
	
	function main() {
		hidden('gp_page','x_emailblast');
		hidden('gp_table',CleanGet('gp_table','',false));
		hidden('gp_posted',1);
		$table = CleanGet('gp_table','',false);
		if ($table=='') {
			ErrorAdd(
				'Incorrect call to x_emailblast, no table parameter.'
				.'This is a programming error, please contact your '
				.'technical support department'
			);
			return;
		}
		
		// Get an object for the page we need.  Then
		// pull the list of skeys in the current search
		// and pull the rows.
		//
		$obj = DispatchObject($table);
		$a_skeys = ContextGet("tables_".$obj->table_id."_skeys",array());
		$l_skeys = implode(',',$a_skeys); 

		// get this little detail taken care of 
		//
		$this->PageSubtitle = 'Email blast to '.$obj->table['description'];
		
		// Get the list of columns of interest, and pull them
		// and slot them by skey, so the list of skeys can be
		// used to order them
		//
		$lDisplayColumns =$obj->table['projections']['email'];
		$aDisplayColumns =explode(',',$lDisplayColumns);
		$EmailColumn     =$obj->table['projections']['emailaddr'];
		$sql 
			='SELECT skey,'.$EmailColumn.','.$lDisplayColumns
			.' FROM '.$table
			.' WHERE skey IN ('.$l_skeys.')';
		$DBRes = SQL($sql);
		$rows = array();
		while ($row = SQL_FETCH_Array($DBRes)) {
			$rows[$row['skey']] = $row;
		}
		
		$okToSend=false;
		if(CleanGet('gp_posted','',false)==1) {
			if (CleanGet('txt_subject','',false)=='') {
				ErrorAdd('Please fill in a subject first');
			}
			if (trim(CleanGet('txt_email','',false))=='') {
				ErrorAdd('Please fill in an email body');
			}
			if (!Errors()) { $okToSend=true; }
		}
		
		
		// Now that we have the results, decide whether we
		// are sending out the email or 
		if ($okToSend) {
			$this->EmailBlast($rows,$a_skeys,$EmailColumn,$aDisplayColumns);
		}
		else {
			$this->EmailHTML($rows,$a_skeys,$aDisplayColumns);	
		}
	
		
	}
	
	// -----------------------------------------------------------------
	// Display options for blasting the email
	// -----------------------------------------------------------------
	function EmailHTML(&$rows,&$a_skeys,$aDisplayColumns) {
		
		?>
		<h2>Email Blast</h2>
		
		<p>Please enter the content of your email below.
		 The email will be sent to each of the recipients 
		 that are checked.  Each recipient will see only 
		 themself in to TO: field, they will not see each other.</p>
		 
		<p>Subject:
		    <input size=50 maxlength=50 name="txt_subject"
		       value="<?=CleanGet('txt_subject','',false)?>">
		    </input>
	   </p>

		Email Text:<br>
		<textarea cols=70 rows=20 name="txt_email"
			><?=CleanGet('txt_email','',false)?></textarea>
		
		<h2>Recipients</h2>
		<?php
			

		
		// Now loop through and show the emails
		//
		echo "<pre>\n";
		foreach ($a_skeys as $skey) {
			$name="embox".$skey;
			$innerHTML = "";
			foreach($aDisplayColumns as $col) {
				$innerHTML.=$rows[$skey][$col].'  ';
			}
			$checked = 'checked';
			if (CleanGet("gp_posted",'',false)=='1') {
				if (CleanGet('embox'.$skey,'',false)=='') {
					$checked ='';
				}
			}
			
			echo 
				'<input type="checkbox" '
				.$checked
				.' value="1"'
				.' name="'.$name.'">'
				.$innerHTML.'<br>'
				.'</input>';
		}
		echo "</pre>";
		
		?>
		<h2>Confirmation</h2>
		
		<p>If all of the recipients and text are ok, then
		<a href="javascript:formSubmit()">Send Emails Now.</a></p>

		<?php
	}
	
	function EmailBlast(&$rows,&$a_skeys,$EmailColumn,$aDispCols) {
		$subject = trim(CleanGet('txt_subject'));
		$email_text=trim(CleanGet('txt_email'));
		?>
		<h2>Email Transmit Report</h2>
		<p>Recipients are listed below. If a recipient
	    	has a missing or invalid email, the administrator
			will get a "bounce" message, which you can use to
			correct the recipient's email address.
		</p>
		<?php
		
		foreach ($a_skeys as $skey) {
			if (CleanGet('embox'.$skey,'',false)=='' ) {
				continue;	
			}
			
			$namepart = '';
			foreach($aDispCols as $col) {
				$namepart.=trim($rows[$skey][$col]).'  ';
			}
			$recipient 
				=$namepart
				.'<'.$rows[$skey][$EmailColumn].'>';
			
			EmailSend($recipient,$subject,$email_text);
			echo $namepart.'  '.$rows[$skey][$EmailColumn].'<br>'; 
		}
		
	}
}
?>
