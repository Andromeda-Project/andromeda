<?php
/* ================================================================== *\
   (C) Copyright 2005 by Secure Data Software, Inc.
   
   ___    _____  _    _     _      _  ___   
  |  _`\ (  _  )( )  ( )   ( )    (_)(  _`\ 
  | (_) )| (_) |`\`\/'/'   | |    | || (_) )
  | ,  / |  _  |  >  <     | |  _ | ||  _ <'
  | |\ \ | | | | /'/\`\    | |_( )| || (_) )
  (_) (_)(_) (_)(_)  (_)   (____/'(_)(____/'
  
  This is an R-A-X library, part of Andromeda
  Purpose: This the ONE TRUE LIBRARY
  Banner art from: http://www.network-science.de/ascii/
   
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
// ==================================================================
// ==================================================================
// BASIC DATABASE COMMANDS
// ==================================================================
// ==================================================================
/**
name:_default_
parent:Basic Database Commands
*/
// ------------------------------------------------------------------
/**
name:Basic Database Commands
parent:Framework API Reference

Andromeda provides a handful of basic database routines that serve several 
purposes.  The primary purpose is simply to have efficient routines
that reduce the code you need in your application.  

Multi-platform abstraction can always be added later if all basic
SQL commands are wrapped, so this is also a goal, though at this time
Andromeda targets only the Postgres database.
*/

// ==================================================================
// ALL DB routines are prefixed "SQL".  Some create connections,
// some execute commands, some format stuff
// ==================================================================


/**
name:SQL_ConnPush
parm:string User_id
parm:string Database_Name

This routine attempts to make a new database connection.  If
successfull, the currently open default connection, if there is one,
is pushed onto a stack, and this connection becomes the new default.

The connection is closed with [[SQL_ConnPop]].

If the first parameter is 'ADMIN', then the connection is made as the
superuser, otherwise the connection is always made as the username
retrieved by the [[SessionGet]] function for the variable "UID".
*/
function SQL_ConnPush($role='',$db='') {
   return scDBConn_Push($role,$db);
}

/* DEPRECATED */
function scDBConn_Push($role='',$db='') {
   $dbc = isset($GLOBALS['dbconn']) ? $GLOBALS['dbconn'] : null; 
   scStackPush('dbconns',$dbc);
   
   // UID is either admin or logged in user
   if($role==$GLOBALS['AG']['application']) {
      //echo "Going for role!";
      $uid = $role;
      $pwd = $role;
   }
   elseif($role<>'' && $role<>$GLOBALS['AG']['application']) {
      $uid = $GLOBALS['AG']['application']."_".$role;
      $pwd = $GLOBALS['AG']['application']."_".$role;
   }
   else {
      $uid = SessionGet('UID');
      $pwd = SessionGet('PWD');
   }
   
   //$db = $db=='' ? $GLOBALS['AG']['application'] : $db;
   $db = $GLOBALS['AG']['application'];
   
   // Now make a connection
   $GLOBALS['dbconn'] = SQL_Conn($uid,$pwd,$db);
}


/**
name:SQL_ConnPop
returns:void

Closes the current default connection if one is open.

If there is a previous default connection on the stack, then that
is popped off and it becomes the current default connection.
*/
function SQL_ConnPop() {
   return scDBConn_Pop();
}

/**
name:SQL
parm:string SQL_Command
returns:resource DB_Rresult

The basic command for all SQL Pass-through operations.  Returns a 
result resource that can be scanned. 

Use this command when you want to pull rows from a database one-by-one.

There is also a collection of [[Specialized SQL Commands]].
*/
function SQL($sql,&$error=false) {
   return SQL2($sql,$GLOBALS["dbconn"],$error); 
}


/**
name:SQL_Fetch_Array
parm:resource Result
parm:int rownum
parm:int type

Accepts a result resource returned by the [[SQL]] function and 
returns the next row from the server.  Returns boolean false if
there are no more rows.

This is the preferred method for retrieving results if the row count
is likely to go over a hundred or so rows.  Below 100 rows, it can
be more convenient to use [[SQL_AllRows]].
*/
function SQL_fetch_array($results,$rownum=null,$type=null) {
	if (!is_null($type)) {
		return @pg_fetch_array($results,$rownum,$type);	
	}
	if (!is_null($rownum)) {
		return pg_fetch_array($results,$rownum);	
	}
	return pg_fetch_array($results); 
}


/* DEPRECATED */
function scDBConn_Pop() {
   if (isset($GLOBALS['dbconn'])) {
      SQL_CONNCLOSE($GLOBALS['dbconn']);
   }
   $GLOBALS['dbconn'] = scStackPop('dbconns');
}

/* FRAMEWORK */
function SQL_CONNSTRING($tuid,$tpwd,$app="") {
	global $AG;
	if ($app=="") { $app = $AG["application"]; }
	return
		" dbname=".$app.
		" user=".strtolower($tuid).
		" password=".$tpwd;
}

/* FRAMEWORK */
function SQL_CONN($tuid,$tpwd,$app="") {
	global $AG;
	//if ($app=="") { $app = $AG["application"]; }
   $app = $AG["application"];
   //echo "$tuid $tpwd $app";
	$tcs = SQL_CONNSTRING($tuid,$tpwd,$app);
   if(function_exists('pg_connect')) {
      $conn = @pg_connect($tcs,PGSQL_CONNECT_FORCE_NEW );
   }
   else {
      $conn = false;
   }
	return $conn;
}

/* FRAMEWORK */
function SQL_CONNCLOSE($tconn) {
   @pg_close($tconn); 
}

/* DEPRECATED */
/* Use SQL_ConnPush() */
function SQL_CONNDEFAULT() {
	global $AG;
	$uid = SessionGet('UID','');
	$pwd = SessionGet('PWD','');
	return SQL_CONN($uid,$pwd);
}

/* DEPRECATED */
function SQL3($sql) { return SQL2($sql,$GLOBALS["dbconn"]); }

/* FRAMEWORK */
function SQL2($sql,$dbconn,&$error=false)
{
	if ($dbconn==null) {
      // 4/4/07.  Rem'd out because if this is a problem we've usually
      // got pleny of other problems.  The only time this can happen
      // w/o a problem is on a new install, and we don't want stray
      // errors there.
		//echo "<b>ERROR: CALL TO SQL2 WITH NO CONNECTION</b>";
      return; 
	}
	global $AG;
	$errlevel = error_reporting(0);
	pg_send_query($dbconn,$sql);
	$results=pg_get_result($dbconn);
	$t=pg_result_error($results);
   $error=false;
	if ($t) {
      $error=true;
      vgfSet('errorSQL',$sql);
      // Made conditional 1/24/07 KFD
      //echo "Error title is".vgfGet("ERROR_TITLE");
      if(SessionGet('ADMIN',false)) {
         ErrorAdd(
            "(ADMIN): You are logged in as an administrator, you will see more"
            ." detail than a regular user."
         );
         ErrorAdd("(ADMIN): ".$sql);
      }
      else {
         // KFD 6/27/07, prevent sending this message more than once
         if(!Errors()) {
            ErrorAdd("There was an error attempting to save:");
         }
      }
		$ts = explode(";",$t);
		foreach ($ts as $onerr) {
         if(trim($onerr)=='') continue;
         // KFD 6/27/07, display errors at top and at column level
         //if(SessionGet('ADMIN',true)) {
         //   ErrorAdd("(ADMIN): ".$onerr);
         //}
         ErrorComprehensive($onerr);
         
      }
	}
	error_reporting($errlevel);
	return $results;
}

/* FRAMEWORK */
// Comprehensive routine to work out what to do with errors
function ErrorComprehensive($onerr) {
   // POSTGRES hardcode, this is what they put in the beginning of a 
   // string of errors.
   $onerr=str_replace('ERROR:','',$onerr);
   $onerr=str_replace("\t",'',$onerr);

   // Save the raw error if a programmer wants to do something with it
   $errsraw=vgfGet('errorsRAW',array());
   $errsraw[]=$onerr;
   vgfSet('errorsRAW',$errsraw);
   

   // Get previously created list of errors
   $colerrs=vgfGet('errorsCOL',array());
   
   // Get the column, error, and text, then see if the
   // application has overridden them.
   list($column,$error,$text) = explode(',',$onerr,3);
   $errorStrings=vgfGet('errorStrings',array());
   if(isset($errorStrings[$error])) {
       $text = $errorStrings[$error];
   }
   
   $column=trim($column);
   
   if($column=='*') {
      // A table-level error begins with an asterisk, report this
      // as an old-fashioned error that appears at the top of the page
      ErrorAdd($text);
   }
   else {
      // This is a column level error.  It is being stored for 
      // display later.
      $colerrs[$column][]=$text;
      
      // KFD 6/27/07, by putting this here, every error gets reported
      // both at its column level and at the top
      ErrorAdd($column.": ".$text);
   }
   
   vgfSet('errorsCOL',$colerrs);
}

/**
name:SQL_Num_Rows
parm:resource DB_Result
returns:int

Accepts a result returned by a call to [[SQL]] and returns the
number of rows in the result.
*/
function SQL_NUM_ROWS($results) { return SQL_NUMROWS($results); } 
function SQL_NUMROWS($results) {
	return pg_numrows($results);
}

/**
name:SQL_Format
parm:string Type_ID
parm:any Value
parm:int Clip_Length
returns:string

Takes any input value and type and formats it for direct substitution
into a SQL string.  So for instance character values are escaped for
quotes and then surrounded by single quotes.  Numerics are returned
as-is, dates are formatted and so forth.

The optional third parameter specifies a maximum length for character
and varchar fields.  If it is non-zero, the value will be clipped to
that length.

If you use this command for every value received from the browser when
you build SQL queries, then your code will be safe from SQL Injection
attacks.  All framework commands that build queries use this command for
all literals provided to them.
*/
function SQL_FORMAT($t,$v,$clip=0) {
	global $AG;
	switch ($t) {
    case 'mime-x':
        return "'".SQL_ESCAPE_BINARY($v)."'";
        break;
    case "char":
    case "vchar":
    case "text":
    case "url":
    case "obj":
    case "cbool":
    case 'ssn':
    case 'ph12':
    case "gender":
        if($clip>0 && strlen($v) > $clip) $v = substr($v,0,$clip);
        // KFD 9/10/07, one of the doctors wants all caps
        if(OptionGet('ALLCAPS')=='Y') {
            $v= strtoupper($v);
        }
        return "'".SQL_ESCAPE_STRING($v)."'";
        break;   
    case "mime-h":
         if($clip>0 && strlen($v) > $clip) $v = substr($v,0,$clip);
			//return "'".SQL_ESCAPE_BINARY($v)."'";
			return "'".SQL_ESCAPE_STRING($v)."'";
			break;
    case "dtime":
        if ($v=="") return "null"; 
        //else return X_UNIX_TO_SQLTS($v);
        else return "'".date('r',dEnsureTS($v))."'";
        break;
    case "date":
    case "rdate":
         // A blank is sent as null to server
			if($v=="") return "null";
         if($v=='0') return 'null';
          
         // Try to detect case like 060507
         if(   strlen($v)==6 
            && strpos($v,'/')===false
            && strpos($v,'-')===false) {
            
            $year=substr($v,4);
            $year = $year < 20 ? '20'.$year : '19'.$year;
            $v = substr($v,0,2).'/'.substr($v,2,2).'/'.$year;
            $v=strtotime($v);
         }
         // Try to detect case like 06052007
         elseif(   strlen($v)==8 
            && strpos($v,'/')===false
            && strpos($v,'-')===false) {
         
            if(substr($v,0,2)=='19' || substr($v,0,2)=='20') {
               $v = substr($v,0,2).'/'.substr($v,2,2).'/'.substr($v,4);
            }
            else {
               $v = substr($v,4,2).'/'.substr($v,6,2).'/'.substr($v,0,4);
            }
            $v=strtotime($v);
         }
         elseif(!is_numeric($v)) {
            // A USA prejudice, assume they will always enter m-d-y, and
            // convert dashes to slashes so they can use dashes if they want
            $v = str_replace('-','/',$v);
            $parts=explode('/',$v);
            if(count($parts)==2) {
               $parts = array($parts[0],1,$parts[1]);
            }
            if(strlen($parts[0])==4) {
               $parts = array($parts[1],$parts[2],$parts[0]);
            }
            elseif(strlen($parts[2])==2) {
               $parts[2] = $parts[2] < 20 ? '20'.$parts[2] : '19'.$parts[2];
            }
            $v = implode('/',$parts);
            $v=strtotime($v);
         }
         
         // Any case not handled above we conclude was a unix timestamp 
         // already.  So by now we are confident we have a unix timestamp
         return "'".date('Y-m-d',$v)."'";
			break;
		case "money":
		case "numb":
		case "int":
			if ($v=="") { return "0"; }
         else { return SQL_ESCAPE_STRING(trim($v)); }
		case "rtime":
		case "time":
			// Originally we were making users type this in, and here we tried
			// to convert it.  Now we use time drop-downs, which are nifty because
			// the display times while having values of numbers, so we don't need
			// this in some cases.
			//if (strpos($v,":")===false) {	return $v; }
         if($v=='') return 'null';
         return $v;
			//$arr = explode(":",$v);
			//return ($arr[0]*60) + $arr[1];
	}
}

/**
name:SQLFC
parm:string Value
returns:string SQL_Value

Shortcut to [[SQL_Format]] for string values.
*/
function SQLFC($value) { return SQL_Format('char',$value); }
/**
name:SQLFN
parm:numb Value
returns:string SQL_Value

Shortcut to [[SQL_Format]] for numeric values.
*/
function SQLFN($value) { return SQL_Format('numb',$value); }
/**
name:SQLFD
parm:date Value
returns:string SQL_Value

Shortcut to [[SQL_Format]] for date values.
*/
function SQLFD($value) { return SQL_Format('date',$value); }
/**
name:SQLFDT
parm:datetime Value
returns:string SQL_Value

Shortcut to [[SQL_Format]] for datetime values.
*/
function SQLFDT($value) { return SQL_Format('dtime',$value); }



/**
name:SQL_ESCAPE_STRING
parm:string Any_Value
returns:string

Wrapper for pg_escape_string, to provide forward-compatibility with
other back-ends.
*/
function SQL_ESCAPE_STRING($val) {
   // KFD 1/31/07 check for existence of pg_escape_string  
   return function_exists('pg_escape_string')
      ? pg_escape_string(trim($val))
      : str_replace("'","''",trim($val));
	//return p*g_escape_string($val);
}
/* DEPRECATED */
function SQL_ESCAPE_BINARY($val) {
	return base64_encode($val);
}
/* DEPRECATED */
function SQL_UNESCAPE_BINARY($val) {
	return base64_decode($val);
}
// ==================================================================
// ==================================================================
// SPECIALIZED SQL Commands
// ==================================================================
// ==================================================================
/**
name:_default_
parent:Specialized SQL Commands
*/
// ------------------------------------------------------------------
/**
name:Specialized SQL Commands
parent:Framework API Reference

Specialized SQL commands allow you to use a single command for
many common tasks that would otherwise take several commands.  The
routine [[SQL_OneValue]] for instance executes a query and pulls a single
column out of the first row and returns it.  

Some specialized SQL commands are also dictionary-aware, so that the
command [[SQLX_UpdateOrInsert]] will try to find a row based on the table's
primary key, and will also only issue commands for columns that it 
recognizes.

Generous use of Specialized SQL Commands is one of the ways to make 
the most of Andromeda, there is a command for most any common operation
you want to perform.

*/

/**
name:SQL_OneValue
parm:string Column_ID
parm:string SQL_Command

Accepts and executes a SQL command on the current default connection.
It then fetches the first row of the result, and if it can find the 
named column, returns its value.

Any failure at any stage returns false.

Be careful that the SQL_Command actually return one or at most a few
rows, if a command is issued to the server that would return 1 million 
rows, the server will execute the entire command, even though it only
returns the first row to PHP.
*/
function SQL_OneValue($column,$sql) {
   //echo $column;
   //echo $sql;
	$results = SQL($sql);
   if ($results===false) return false;
	$row = SQL_FETCH_ARRAY($results);
   if ($row===false) return false;
   if (!isset($row[$column])) return false;
	return $row[$column];
}

/**
name:SQL_OneRow
parm:string SQL_Query
returns:array Row

Accepts a SQL query and returns the first row only of the result.  If the
query returns zero rows the routine returns boolean false.

Note that the query itself should return only 1 or  a few rows, using this
routine is not a substitute for planning an efficient query.  If you hand
this routine a query that generates 1 million rows, the server will still
generate the entire result, even though it only gives back the first one.
*/
function SQL_OneRow($sql) {
	$results = SQL($sql);
	$row = SQL_FETCH_ARRAY($results);
	return $row;
}

/**
name:SQL_AllRows
parm:string SQL_Command
parm:string Column_id

Executes a SQL command and retrieves all rows into a [[Rows Array]].

If the second parameter is provided, then the values of the named
column are made into the keys for the rows in the result.  

Extreme care should be taken with this command.  Experience has shown
that PHP's performance drops dramatically with the size of the result
set, so much so that anything over 100 rows or so should probably not
be contemplated for this command.
*/
function SQL_AllRows($sql,$colname='') {
   $results = SQL($sql);
   $rows = SQL_FETCH_ALL($results);
   if ($rows===false) return array();
   
   // Simple default is just the rows
   if ($colname=='') {
      return $rows;
   }
   
   // Maybe though they want each row referenced by some column value
   $retval = array();
   foreach($rows as $row) {
      $retval[trim($row[$colname])] = $row;
   }
   return $retval;
   
}


/* DEPRECATED */
function SQL_FETCH_ARRAY_Decode($dbres,$cols) {
   $retval = SQL_FETCH_ARRAY($dbres);
   foreach($cols as $colname) {
      $retval[$colname] = base64_decode($retval[$colname]);
   }
   return $retval;
}

/* FRAMEWORK */
function SQL_fetch_all($results) {
   // The only case where the function will not exist is on a 
   // new install where it is missing.  In that case we don't want
   // errors all over the screen, we want to trap it and report it
   // gracefully
   if(function_exists('pg_fetch_all') && $results)
      return pg_fetch_all($results);
   else
      return false;
}

/* DEPRECATED */
function SQL_FKJOIN($pks,$fkey_suffix,$child="",$parent="") {
	$retval = "";
	$pksarr = explode(",",$pks);
	foreach ($pksarr as $colname) {
		$retval.=ListDelim($retval," AND ").
			$child.".".$colname.$fkey_suffix." = ".$parent.".".$colname;
	}
	return $retval;
}

/**
name:SQLX_TrxBegin
returns:void

Opens a transaction on the server.  On most platforms this is
equivalent to "BEGIN TRANSACTION".
*/
function SQLX_TrxBegin() {
	global $dbconn,$AG;
   if(!isset($AG['trxlevel'])) $AG['trxlevel']=0;
	if ($AG["trxlevel"]<>0) { ErrorsAdd("ERROR: Nested transactions are not allowed"); }
	else {
		$AG["trxlevel"]++;
		SQL("BEGIN TRANSACTION");
	}
}

/* FRAMEWORK */
function SQLX_TrxCommit() {
	global $AG;
   if(!isset($AG['trxlevel'])) $AG['trxlevel']=0;
	if ($AG["trxlevel"]<>1) {
		ErrorsAdd("Error, can only commit a trx when level is 1, it is now: ".$AG["trxlevel"]);
	}
	else {
		$AG["trxlevel"]--;
		SQL("COMMIT TRANSACTION");
	}
}

/* FRAMEWORK */
function SQLX_TrxRollback() {
	global $AG;
   if(!isset($AG['trxlevel'])) $AG['trxlevel']=0;
	if ($AG["trxlevel"]<>1) {
		ErrorsAdd("Error, can only rollback a trx when level is 1, it is now: ".$AG["trxlevel"]);
	}
	else {
		$AG["trxlevel"]--;
		SQL("ROLLBACK TRANSACTION");
	}
}

/**
name:SQLX_TrxClose
parm:string Trx_Type_Name

Attempts to commit a transaction.  If there are errors, it rollsback
the transaction and makes an entry in the [[syslogs]] table to 
record the error.

If there is an error, and the second parameter has been provided, that 
value will go to the "syslogs_name" column of the [[syslogs]] table.
*/
function SQLX_TrxClose($name='') {
	if (!Errors()) { 
      SQLX_TrxCommit();
   }
   else {
      // In case of error in a transaction, we will report
      // the error
      SQLX_TrxRollBack();
      // First insert into the new syslogs table
      $table1=DD_TableRef('syslogs');
      $table2=DD_TableRef('syslogs_e');
      $row=array(
         'syslog_type'=>'ERROR'
         ,'syslog_subtype'=>'TRX'
         ,'syslog_name'=>$name
         ,'syslog_text'=>'See table syslogs_e'
      );
      $skey = SQLX_Insert($table1,$row);
      $log = SQL_OneValue(
         'syslog'
         ,'select syslog from syslogs where skey='.$skey
      );
      foreach ($GLOBALS['AG']['trx_errors'] as $err) {
         $row = array(
            'syslog'=>$log
            ,'syslog_etext'=>$err
         );
         SQLX_Insert($table2,$row);
      }
   } 
}

/* FRAMEWORK */
function SQLX_TrxLevel() {
   global $AG;           
   if(!isset($AG['trxlevel'])) $AG['trxlevel']=0;
   return $AG["trxlevel"]; 
}


/** 
name:SQLX_Insert
parm:string/array table
parm:array Row
parm:bool Rewrite_Skey
parm:bool Clip_Fields
returns:int

In its most basic form, this routine accepts a [[Row Array]]
and attempts to insert it into a table.  Upon success, the routine
returns the skey value of the new row.

The first entry can be either a [[Table Reference]] or the name of
a table.  The second entry is always a [[Row Array]].  This function 
makes use of the dictionary to determine the correct formatting of all
columns, and ignores any column in the [[Row Array]] that is not
in the table.

The third parameter is used by the framework, and should always be 
false.  If the third parameter is set to true, then this routine 
executes a [[gpSet]] with the value of skey for the new row, making
it look like this row came from the browser.

If the fourth parameter is true, values are clipped to column width
to prevent overflows.  This almost guarantees the insert will succeed,
but should only be done if it is acceptable to throw away the ends of
columns.
*/
function SQLX_Insert($table,$colvals,$rewrite_skey=true,$clip=false) {
   if(!is_array($table)) $table=DD_TableRef($table);
   //if (Errors()) return 0;
	$table_id= $table["table_id"];
   $view_id = DDTable_IDResolve($table_id);
 	$tabflat = &$table["flat"];

	$new_cols = "";
	$new_vals = "";
	foreach($tabflat as $colname=>$colinfo) {
		if (isset($colvals[$colname])) {
         //if($colvals[$colname]<>'') {
            if (DD_ColInsertsOK($colinfo,'db')) {
               $cliplen = $clip ? $colinfo['colprec'] : 0;
               $new_cols.=ListDelim($new_cols)." ".$colname;
               $new_vals
                  .=ListDelim($new_vals)." "
                  .SQL_FORMAT($colinfo["type_id"],$colvals[$colname],$cliplen);
            }
         //}
		}
	}
	$sql = "INSERT INTO ".$view_id." ($new_cols) VALUES ($new_vals)";
   //h*print_r($colvals);
   //h*print_r($sql);

   // ERRORROW CHANGE 5/30/07, big change, SQLX_* routines now save
   //  the row for the table if there was an error
   $errflag=false;
	SQL($sql,$errflag);
   if($errflag) {
      vgfSet('ErrorRow_'.$table_id,$colvals);
   }

	$notices = pg_last_notice($GLOBALS["dbconn"]);
   $retval = 0;
	//echo "notices: $notices<br>";
	$matches = array();
	preg_match_all("/SKEY(\D*)(\d*);/",$notices,$matches);
	if(isset($matches[2][0])) {
      $retval = $matches[2][0];
      if ($rewrite_skey) {
         CleanSet("gp_skey",$matches[2][0]);
         CleanSet("gp_action","edit");
      }
	}
   
   // Possibly cache the row
   $cache_pkey0=vgfget('cache_pkey');
   $cache_pkey=array_flip($cache_pkey0);
   if(isset($cache_pkey[$table_id])) {
      CacheRowPut($table,$colvals);
   }
   
   return $retval;
}

/**
name:SQLX_Inserts
parm:Array Mixed_Rows
parm:Array Constants
parm:boolean stop_on_error

Accepts a [[Mixed_Rows]] array and attempts to insert each row
into its respective table, using [[SQLX_Insert]].

If the 2nd parameter is provided, the values in that array will be
merged into the values of every row.  This is safe because any columns
that do not exist in some tables will be ignored.

If the third parameter is true, the operation will stop on the first
error, otherwise it will continue until every row is processed, even 
if there are 10,000 rows and every one of them fails.
*/
function SQLX_Inserts(&$mixedrows,$constants=array(),$stop=false) {
   return SQLX_InsertMixed($mixedrows,$constants,$stop);
}

/* DEPRECATED */
function SQLX_InsertMixed(&$mixedrows,$constants=array(),$stop=false) {
   foreach ($mixedrows as $table_id=>$rows) {
      $table = DD_TableRef($table_id);
      foreach ($rows as $row) {
         $rownew = array_merge($row,$constants);
         SQLX_Insert($table,$rownew,false);
         if($stop==true && Errors()) return;
      }
   }
}

/**
name:SQLX_Update
parm:string/array table
parm:array Row

In its most basic form, this routine accepts a [[Row Array]]
and attempts to update that row in the table.

The first entry can be either a [[Table Reference]] or the name of
a table.  The second entry is always a [[Row Array]].  This function 
makes use of the dictionary to determine the correct formatting of all
columns, and ignores any column in the [[Row Array]] that is not
in the table.

*/
function SQLX_Update(&$table,$colvals,$errrow=array()) {
   if(!is_array($table)) $table=DD_TableRef($table);
	$table_id= $table["table_id"];
   $view_id = DDTable_IDResolve($table_id);
 	$tabflat = &$table["flat"];

	$sql = "";
	$st_skey = isset($colvals["skey"]) ? $colvals["skey"] : CleanGet("gp_skey");
	foreach($tabflat as $colname=>$colinfo) {
		if (isset($colvals[$colname])) {
			if (DD_ColUpdatesOK($colinfo)) {
				$sql.=ListDelim($sql).
					$colname." = ".SQL_FORMAT($colinfo["type_id"],$colvals[$colname]);
			}
		}
	}
   if ($sql <> '') {
       
      $sql = "UPDATE ".$view_id." SET ".$sql." WHERE skey = ".$st_skey;
       
      // ERRORROW CHANGE 5/30/07, big change, SQLX_* routines now save
      //  the row for the table if there was an error
      $errflag=false;
      SQL($sql,$errflag);
      if($errflag) {
         vgfSet('ErrorRow_'.$table_id,$errrow);
      }

      // Possibly cache the row
      if(!Errors()) {
         $cache_pkey0=vgfget('cache_pkey');
         $cache_pkey=array_flip($cache_pkey0);
         if(isset($cache_pkey[$table_id])) {
            CacheRowPutBySkey($table,$st_skey);
         }
      }
   }
}

/* DEPRECATED */
//function  SQLX_Delete($table_id,$skey) {
//	SQL("Delete from ".$table_id." where skey = ".$skey);
//}

/* DEPRECATED */
function SQLX_FetchRow($table,$column,$value) {
	$t = SQL3("Select * FROM ".$table." WHERE ".$column." = '".$value."'");
	return SQL_FETCH_ARRAY($t);
}


/* DEPRECATED */
function scDBInserts($table_id,&$rows,$skey=false,$clip=false) {
   $table=DD_TableRef($table_id);
   foreach ($rows as $row) {
      SQLX_Insert($table,$row,$skey,$clip);
   }
}

/**
name:SQLX_Delete
parm:string table_id
parm:array Row

Accepts a [[Row Array]] and a [[table_id]] and builds a SQL delete 
command out of the values of the [[Row Array]].

Can be extremely destructive!  This routine will delete all of the
rows of a table that match the given columns.  Calling this routine
on an orders table and providing only a customer ID will delete all 
of the orders for that customer!
*/
function SQLX_Delete($table_id,$row) {
   $table_dd=DD_TableRef($table_id);
   $view_id = DDTable_IDResolve($table_id);

   
   $awhere=array(); 
   foreach ($row as $colname=>$colval) {
      $awhere[]
         =$colname.' = '
         .SQL_Format($table_dd['flat'][$colname]['type_id'],$row[$colname]);
   }
   
   $SQL="DELETE FROM $view_id WHERE ".implode(' AND ',$awhere);
   //echo $SQL;
   SQL($SQL);
}


/**
name:SQLX_UpdatesOrInserts
parm:string Table_ID
parm:array Rows
returns:void

This function accepts a [[Rows Array]] and processes each row.  Based
on primary key, if the row does not exist in the database it is inserted.
If it does exist, then any non-primary key value in the row will be
updated.

All of the rows are expected to be belong to table Table_ID.
*/
function SQLX_UpdatesOrInserts($table_id,&$rows) {
   return scDBUpdatesOrInserts($table_id,$rows);
}
/* DEPRECATED */
function scDBUpdatesOrInserts($table_id,&$rows) {
   $table=DD_TableRef($table_id);
   foreach ($rows as $row) {
      scDBUpdateOrInsert($table,$row);
   }
}

/**
name:SQLX_UpdatesOrInsert
parm:ref Table_Definition
parm:array Rows
returns:void

This function accepts a single [[Row Array]].  Based
on primary key, if the row does not exist in the database it is inserted.
If it does exist, then any non-primary key value in the row will be
updated.

The first parameter must be a data dictionary table definition, which
you can get with [[DD_TableRef]].
*/
function SQLX_UpdateOrInsert(&$table,$colvals) {
   return scDBUpdateOrInsert($table,$colvals);
}

function  scDBUpdateOrInsert(&$table,$colvals) {
   $table_id= $table["table_id"];
   $tabflat = &$table["flat"];
   
   // First query for the pk value.  If not found we will
   // just do an insert
   //
   $abort = false;
   $a_pk = explode(',',$table['pks']);
   $s_where = '';
   foreach ($a_pk as $colname) {
       if(!isset($colvals[$colname])) {
           $abort = true;
           break;
       }
      $a_where[]=
         $colname.' = '
         .SQL_Format($tabflat[$colname]['type_id'],$colvals[$colname]);
   }
   
   if($abort) {
       $skey = false;
   }
   else {
       $s_where=implode(' AND ',$a_where);
       
       $sql = 'SELECT skey FROM '.DDTable_IDResolve($table_id).' WHERE '.$s_where;
       $skey = SQL_OneValue('skey',$sql);
   }
   // STD says on 12/15/2006 that this routine should not put errors on screen
   //if (Errors()) echo HTMLX_Errors();
   
   if (!$skey) {
      //echo "insert into ".$table_id."\n";
      $retval = SQLX_Insert($table,$colvals,false);
      if (Errors()) {
         // STD says on 12/15/2006 that this routine should not put errors on screen
         //echo HTMLX_Errors();
         //echo $sql;
         $retval = 0;
      }
   }
   else {
      //echo "update ".$table_id." on $skey\n";
      $colvals['skey']=$skey;
      $retval = -$skey;
      SQLX_Update($table,$colvals);
      if (Errors()) {
         // STD says on 12/15/2006 that this routine should not put errors on screen
         //echo HTMLX_Errors();
         //echo $sql;
         $retval = 0;
      }
   }   
   return $retval;
}

/* DEPRECATED */
function scDBInsert($table_id,$row,$rewrite_skey=true) {
   $table = DD_TableRef($table_id);
   return SQLX_Insert($table,$row,$rewrite_skey);
}

/* FRAMEWORK */
// Rem'd out 10/26/06, when row-level security was moved server-side
/*
function S*QLX_Filters($tabflat) {
	$ret = "";
	$groupuids = SessionGet("groupuids",array());
	foreach ($groupuids as $col) {
		if (isset($tabflat[$col])) {
			$ret .= ListDelim($ret," AND ")."UPPER($col)=UPPER('".SessionGet("UID")."')";
		}
	}
	return $ret;
}
*/

/* NO DOCUMENTATION */
function SQLX_ToDyn($table,$pkcol,$lcols,$filters=array()) {
   // Turn filters into two strings
   $filt_name=$filt_where='';
   foreach($filters as $colname=>$colvalue) {
      $filt_name .='_'.$colname.'_'.$colvalue;
      $filt_where.=$filt_where=='' ? '' : ' AND ';
      $filt_where.=" $colname = '$colvalue' ";
   }
   $filt_where=$filt_where=='' ? '' : ' WHERE '.$filt_where;
   
   // first get the name
   $fname='table_'.$table.'_'
      .str_replace(',','_',$lcols).$filt_name
      .'.rpk';

   // Pull from memory if processed, else cache
   if(!isset($GLOBALS['cache'][$fname])) {
      // not in memory, is it on disk?  If not, must
      // execute the query
      $rows=aFromDyn($fname);
      if($rows===false) {
         $rows=array();
         $sq="SELECT $pkcol,$lcols FROM $table $filt_where";
         $db=SQL($sq);
         while ($row=SQL_Fetch_Array($db)) {
            $rows[$row[$pkcol]] = $row;
         }
         DynFromA($fname,$rows);
      }
      $GLOBALS['cache'][$fname]=$rows;
   }
   $retval = &$GLOBALS['cache'][$fname];
   return $retval;
}

/* NO DOCUMENTATION */
function SQLX_SelectIntoTemp($cols,$from,$into) {
	// this is a postgres version
	global $dbconn;
	$sql = "SELECT ".$cols." INTO TEMPORARY ".$into." FROM ".$from;
	SQL2($sql,$dbconn);
}

/**
name:SQLX_Cleanup
parm:array Mixed_Rows

A complete general cleanup of a mixed set of rows to ensure
that they will all insert ok.  This will smear over errors, such
as a value of '-' will become integer 0, strings will be
truncated, and so forth.

There is no return value, the array is accepted by reference.
*/
function SQLX_Cleanup(&$mixedrows) {
   foreach ($mixedrows as $table_id=>$rows) {
      $table = DD_TableRef($table_id);
      $rowkeys = array_keys($rows);
      foreach ($rowkeys as $rowkey) {
         $row = &$mixedrows[$table_id][$rowkey];
         $colnames=array_keys($row);
         foreach ($colnames as $colname) {
            if (isset($table['flat'][$colname])) {
               switch($table['flat'][$colname]['type_id']) {
                  case 'int':
                     $row[$colname] = intval($row[$colname]);
                     break;
                  case 'money':
                  case 'numb':
                     $row[$colname] = floatval($row[$colname]);
                     break;
                  case 'cbool':
                  case 'gender':
                     $row[$colname] = substr($row[$colname],0,1);
                  case 'char':
                  case 'varchar':
                     $len = $table['flat'][$colname]['colprec'];
                     $row[$colname] = substr($row[$colname],0,$len);
                     break;
               }
            }
         }
      }
   }
}


/**
name:rowsForSelect
parm:string Table_id
parm:string First_Letters
return:array rows

Returns an array of rows that can be put into a drop-down select box.
The first column is always "_value" and the second is always "_display".

The second parameter, if provided, filters to the results so that 
only values of _display that start with "First_Letters" are returned.

For a multiple-column primary key, this routine will filter for any pk
column that exists in the session array "ajaxvars".  This feature is
controlled by an (as-yet undocumented) feature in [[ahInputsComprehensive]]
that can make inputs use Ajax when their value changes to store their
value in the session on the server.

This was created 1/15/07 to work with Ajax-dynamic-list from 
dhtmlgoodies.com.
*/
function RowsForSelect($table_id,$firstletters='',$matches=array(),$distinct='') {
   $table=DD_TableRef($table_id);

   // Determine which columns to pull and get them
   // KFD 10/8/07, a DISTINCT means we are pulling a single column of 
   //              a multiple column key, pull only that column
   if($distinct<>'') {
       $proj = $distinct;
   }
   else {
       if(ArraySafe($table['projections'],'dropdown')=='') {
          $proj=$table['pks'];
       }
       else {
          $proj=$table['projections']['dropdown'];
       }
   }
   $aproj=explode(',',$proj);
   $acollist=array();
   foreach($aproj as $aproj1) {
      $acollist[]="COALESCE($aproj1,'')";
   }
   $collist=str_replace(','," || ' - ' || ",$proj);
   //$collist = implode(" || ' - ' || ",$acollist);
   //syslog($collist);
   
   // Get the primary key, and resolve which view we have perms for
   // KFD 10/8/07, do only one column if passed
   if($distinct<>'') {
       $pk = $distinct;
   }
   else {
       $pk = $table['pks'];
   }
   $view_id=ddtable_idResolve($table_id);

   // Initialize the filters
   $aWhere=array();

   // Generate a filter for each pk that exists in session ajaxvars.  
   // There is a BIG unchecked for issue here, which is that a multi-column
   //  PK must have *all but one* column supplied, and it then returns
   //  the unsupplied column.
   $pkeys   = explode(',',$table['pks']);
   $ajaxvars=afromGP('adl_');
   foreach($pkeys as $index=>$pkey) {
      if(isset($ajaxvars[$pkey])) {
         $aWhere[]="$pkey=".SQLFC($ajaxvars[$pkey]);
         // This is important!  Unset the pk column, we'll pick the leftover
         unset($pkeys[$index]);
      }
   }
   // If we did the multi-pk route, provide the missing column
   //  as the key value
   if(count($ajaxvars)>0) {
      $pk=implode(',',$pkeys);
   } 

   // Determine if this is a filtered table
   if(isset($table['flat']['flag_noselect'])) {
      $aWhere[]= "COALESCE(flag_noselect,'N')<>'Y'";
   }
   
   // Add more matches on 
   foreach($matches as $matchcol=>$matchval) {
      $aWhere[] = $matchcol.' = '.SQLFC($matchval); 
   }
   
   
   // If "firstletters" have been passed, we will filter each 
   // select column on it
   //
   // KFD 8/8/07, a comma in first letters now means look in 
   //             1st column only + second column only
   $SLimit='';
   $xWhere=array();
   if($firstletters<>'') {
      $SLimit="Limit 20 ";
      if(strpos($firstletters,',')===false) {
         // original code, search all columns
         $implode=' OR ';
         foreach($aproj as $aproj1) { 
            $sl=strlen($firstletters);
            $xWhere[]
               ="SUBSTRING(LOWER($aproj1) FROM 1 FOR $sl)"
               ."=".strtolower(SQLFC($firstletters));
         }
      }
      else {
         // New code 8/8/07, search first column, 2nd, third only,
         // based on existence of commas
         $implode=' AND ';
         $afl = explode(',',$firstletters);
         foreach($afl as $x=>$fl) {
            $sl = strlen($fl);
            $xWhere[]
               ="SUBSTRING(LOWER({$aproj[$x+1]}) FROM 1 FOR $sl)"
               ."=".strtolower(SQLFC($fl));
         }
      }
   }
   if(count($xWhere)>0) {
      $aWhere[] = "(".implode($implode,$xWhere).")";
   }
   
   // Finish off the where clause
   if (count($aWhere)>0) {
      $SWhere = "WHERE ".implode(' AND ',$aWhere);
   }
   else {
      $SWhere = '';
   }

   // Execute and return
   $sDistinct = $distinct<>'' ? ' DISTINCT ' : '';
   $SOB=$aproj[0];
   $sq="SELECT $sDistinct $pk as _value,$collist as _display 
          FROM $view_id 
       $SWhere 
         ORDER BY $SOB $SLimit ";
   /*
   openlog(false,LOG_NDELAY,LOG_USER);
   syslog(LOG_INFO,$table['projections']['dropdown']);
   syslog(LOG_INFO,$sq);
   closelog();
   */
   //echo 'echo|'.$sq;
   $rows=SQL_Allrows($sq);
   return $rows;
}

?>
