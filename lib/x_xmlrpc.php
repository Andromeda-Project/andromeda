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
/* ==========================================================================
   U_XMLRPC.php
	
	REQUIRES: $AG["xmlrpc"]["callcode"]
	
	REQUIRES: file ddxmlrpc_<callcode>.php, expects $table and $table_cols
	          to simulate the same structure as dd table files.
	
	Library to handle any XML RPC Call.  Behavior:
	-> $AG["xmlrpc"]["inputs"] exists, execute a call immediately
	-> otherwise: 
	   -> $AG["clean"]["xmlrpc"] not exist, put up HTML to show user
		-> $AG["clean"]["xmlrpc"] exists, use POST data to make call
		

	NOTE THE STRONG ASSUMPTION THAT WE ARE BEING CALLED FROM A SUBROUTINE
	OR INDEPENDENTLY, $TABLE and $TABLE_COLS ARE REDEFINED IN THIS ROUTINE
	
	Revisions:
	Feb 18 2005  Created, outlined, drafted
   ========================================================================== 
*/
global $AG;

$flag=false;
if (isset($AG["clean"]["xmlrpc_callcode"])) {
	$AG["xmlrpc"] = array("callcode"=>$AG["clean"]["xmlrpc_callcode"]);
	$flag=true;
}

// Basic reality check errors
if (!isset($AG["xmlrpc"]["callcode"])) {
	$AG["trx_errors"].="Call to undefined XML_RPC without defining call code;";
	if ($flag) echo HTML_TrxErrors();
	return;
}

// Here is where we load the dictionary definition of these calls
$table = array();
$table_cols = array();
$callcode = $AG["xmlrpc"]["callcode"];
include("ddxmlrpc_".$callcode.".php");
if (!isset($table["id"])) {
	$AG["trx_errors"].="Call to undefined XML RPC: ".$callcode.";";
	if ($flag) echo HTML_TrxErrors();
	return;
}
	
// Now that there are no early aborts, bring in the library
//
require_once 'XML/RPC.php';

// Establish default mode.  We are calling from HTML.
//
$stmode = "html";

// A subroutine call was made.  Transfer values into table_cols
// for easier handling.  Updateable columns are inputs, non-updateable
//  are return values.
if (!isset($AG["xmlrpc"]["inputs"])) {
	// 9/21/05, this is dead code.  This would become a call
	// to "cleanboxes()"
	AND_HTTP_TxtToTableCols($table_cols);
}	
else {
	$stmode = "silent";
	$index = 0;
	foreach ($table_cols as $key=>$column) {
		if ($column["UPD"]=="Y") {
			$table_cols[$key]["value"] = $AG["xmlrpc"]["inputs"][$index];
			$index++;
		}
	}
}

// If we were not in silent mode, maybe the user has
// posted a request to execute a call.
//
if ($stmode=="html" && isset($AG["clean"]["xmlrpc"])) {
	$stmode = "post";
}

// if either post or silent, we must now actually make the Remote Procedure Call
// 
if ($stmode == "post" || $stmode == "silent") {
	
	// First build up the parameters for the call
	$params = array();
	foreach ($table_cols as $key=>$col) {
		if ($col["UPD"]=="Y") {
			$params[] 
				=new XML_RPC_Value(
					$col["value"]
					,trim($col["xmltype"])
				);
		}
	}
	
	// Now the message and the client
	$msg = new XML_RPC_Message(trim($table["callmsg"]),$params);
	$client = new XML_RPC_Client(trim($table["path"]),trim($table["url"]),$table["port"]);

	// OK, the big moment, make the call, then proceed to results
	$response = $client->send($msg);
	$v = $response->value();
	
	//$coach_id = rand(1000,5000);
	//echo("\nCreating random coach_id $coach_id\n");
	//$params = array(new XML_RPC_Value($coach_id, 'int'));
	//$msg = new XML_RPC_Message('dash.Coach.create', $params);
	//$client = new XML_RPC_Client('/clients/dash/server.php', 'www.b16g.com', 80);
	//$response = $client->send($msg);
	//$v = $response->value();
	
	//var_dump($v);
	if (!$response->faultCode()) {
		$AG["xmlrpc"]["rets"] = array();
		$result = $v->scalarval();
		$resx = 0;
		foreach ($table_cols as $key=>$col) {
			if ($col["UPD"] == "N") {
				if (is_array($result)) {
					$resval = $result[$resx]->scalarval();
				}
				else {
					$resval = $v->scalarval();	
				}
				$resx++;
				$table_cols[$key]["value"] = $resval;
				$AG["xmlrpc"]["rets"][] = $resval;
			}
		}

		/*  Working code for one value;
		// Needs improvement, works only for single values
		$AG["xmlrpc"]["rets"] = array();
		foreach ($table_cols as $key=>$col) {
			if ($col["UPD"] == "N") {
				$table_cols[$key]["value"] = $v->scalarval();
				$AG["xmlrpc"]["rets"][] = $v->scalarval();
			}
		}
		*/
	} 
	else {
		ErrorAdd("XML RPC Error call ".$callcode.
			" Fault Code and reason: ".$response->faultCode().", ".$response->faultString());
	}
}

// on a silent call, there is nothing more to be done, exit
//
if ($stmode == "silent") { return; }

// ==========================================================================
// Now comes HTML
// ==========================================================================
?>
<h1>Interactive Test of XML RPC Call</h1>
	
<p>Testing call code<b>: <?php echo $callcode; ?></b>

<?php if ($stmode=="post") {
	echo "
	<p>The test call has been executed.  Both the inputs are outputs
	   are displayed below.  If you wish to run another test, change
		the values below and submit again.</p>";
}
?>

<?php echo HTML_TrxErrors(); ?>

<p>This page allows you to manually input parameters to an XML
   Remote Procedure Call (RPC) and then see the results.  Be advised
	that there is no record at all made of these calls, so this
	interactive test should be made only during testing.  If it
	will cause data loss to run one of these calls without saving the
	results, then do not use this interactive facility.</p>
	
<table>
<?php echo HTML_INPUTS($table_cols);?>
</table>

<input type="hidden" name="xmlrpc" id = "xmlrpc" value="X">
<input type="hidden" name="xmlrpc_callcode" id = "xmlrpc_callcode" value="<?php echo $callcode ?>">

<br>
<br>
<input type="button" value="Send Test" name="btnsave" id="btnsave" onclick="formSubmit();"></input>

<script>
	ob("sys_postback").value="literal";
	ob("sys_parm1").value="u_xmlrpc";

<?php 
foreach ($table_cols as $key=>$col) {
	if ($col["UPD"]<>"Y") {
		echo "\tob(\"txt".$key."\").disabled=true;\n";
	} 
	else {
		echo "\tob(\"txt".$key."\").value=\"".$col["value"]."\";\n";
	}
}
?>
</script>

