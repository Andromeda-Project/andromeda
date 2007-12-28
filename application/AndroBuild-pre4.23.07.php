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
   AndroDBB, the Andromeda Database Builder
	------------------------------------------------------------------
	This program is entirely self-contained, even though it can be called
	interactively.  It must remain self-contained so that no change to the
	run-time libraries can possible affect this program in any way.
	
	Note that the routine "LogEntry" does look for the function "x_EchoFlush",
	which will exist in runtime.  LogEntry calls this routine if it can find it,
	otherwise it just skips it.

	This file creates and uses an object, $o, and all routines are attached to
   that.  A really great thing would be to get rid of all of these GLOBALS
   and use $this-> for all of those variables.	
	
	--> Parse FETCH/DIST/S
	
	Keywords in comments:
	EXAMINE:  means I think something is wrong but there are no bugs
	          or trouble spots that we know of.
	
   ==========================================================================
   Revision History (as of 2/14/07)
   
   Feb 15 2007  Created FETCHDEF automation, Fetch by default
   Feb 14 2007  Write out AndroDBB.add in assoc array form to dd_AndroDBB.php
   Feb 13 2007  Corrected mistake in trigger generation for COUNT	
   ==========================================================================
*/
ini_set("max_execution_time",0);  // allows to run in a web page.
ini_set("allow_url_open",false);
//ini_set("error_reporting",E_ALL ^ E_NOTICE);
ini_set("error_reporting",E_ALL);
ini_set("display_errors",true);
ini_set("log_errors",true);

// -------------------------------------------------------------------
// Operational variables used during build
// -------------------------------------------------------------------

$GLOBALS["dbconn1"] = "";
$GLOBALS["dbconn2"] = "";		
$GLOBALS["sqlCommandError"] = ""; 

$GLOBALS["defcolwidth"] = 50;
$GLOBALS["ddarr"]   = array();  // The data dictionary will be loaded from AndroDBB.add
$GLOBALS["ddflat"]  = array();  // The data dictionary will be loaded from AndroDBB.add
$GLOBALS["utabs"]   = array();  // Server-side tables loaded locally for easier processing
$GLOBALS["chains"]  = array();  // Server-side chains pulled locally
$GLOBALS["ufks"]    = array();  // user foreign key info
$GLOBALS["content"] = array();  // Any content to load to non-DD tables
$GLOBALS["uicolseq"]= 0;      // used to sequence all dd entries, mostly used for columns

$o = new x_builder();
$o->main();

// ===================================================================
// ===================================================================
// MAIN
// ===================================================================
// ===================================================================
class x_builder {
function main() {	

	// First come the most basic reality checks, that the
	// database exists and the filesystem is writable
	//
	$retval = true;
	$retval = $retval && $this->LogStart();
	$retval = $retval && $this->DB_Connect();
	$retval = $retval && $this->FS_Prepare();

	// If we passed most basic, we prepare the database
	// by loading stored procedures and making the zdd
	// schema to use during build.
	//
	$retval = $retval && $this->DB_Prepare();
	$retval = $retval && $this->SP_Load();
	$retval = $retval && $this->DDRMake();
	
	// Load and run out the new specification
	//
	$retval = $retval && $this->SpecLoad();
   // not necessary because we load straight to _c
	//$retval = $retval && $this->SpecResolve();	// Resolve _h against _s & _n
	$retval = $retval && $this->SpecFlattenValid();
	$retval = $retval && $this->SpecFlatten();
	$retval = $retval && $this->SpecLocal();  // now only lists

	// Now we know everything, we can validate the new
	// spec.  This does not validate changes.
	$retval = $retval && $this->SpecValidate();

	// Generate all of the DDL, this finishes with new spec
	$retval = $retval && $this->SpecDDL();   

	// Now pull current state and look at differences
	//
	$retval = $retval && $this->RealityGet();	// What is current state of db?
	$retval = $retval && $this->Differences();	// diff _r (reality) vs. _c (complete)
	//$retval = $retval && $this->DiffValidate();	// maybe someday

	// Assuming we did not fail validation, do it!
	$retval = $retval && $this->Analyze();
	$retval = $retval && $this->PlanMake(); 
	$retval = $retval && $this->PlanExecute();
	$retval = $retval && $this->ContentLoad();
	$retval = $retval && $this->SecurityNodeManager();
	
	// This is code generation
	//
	$retval = $retval && $this->CodeGenerate_Info();
	$retval = $retval && $this->CodeGenerate_Tables();
	$retval = $retval && $this->CodeGenerate_Modules();
	$retval = $retval && $this->CodeGenerate_XMLRPC();

	$this->DB_Close();
	$this->LogClose($retval);
   $GLOBALS['retval']=$retval;
	return;
}
// ===================================================================
// ===================================================================
// MAIN (END)
// ===================================================================
// ===================================================================

// ==========================================================
// Database Connect Routines
// ==========================================================
function DB_Connect() 
{
	// Log stage and expose needed variables
	$this->LogStage("Connecting to database");
	$parm = $GLOBALS["parm"];
	$pw   = $GLOBALS["x_password"];

	// First sanity check, make sure we can connect
	// 
	$cnx = 
		" dbname=andro".
		" user=".$parm["UID"].
		" password=".$pw;
	$GLOBALS["dbconna"] = pg_connect($cnx,PGSQL_CONNECT_FORCE_NEW);
	if ($GLOBALS["dbconna"]) {
      $this->LogEntry("Connected to andro database OK.");
      
      // Need to know if we are building andro.  if not, find the
      // directory to link to
      if ($parm['APP']=='andro') {
         $GLOBALS['dir_andro']='';
         $this->LogEntry(
            "Building Node Manager, no linking of LIB directory."
         );
      }
      else {
         $sql = 'SELECT w.dir_pub FROM webpaths w JOIN applications a '
            ." ON w.webpath = a.webpath WHERE a.application='andro'";
         $res = pg_query($sql);
         $row = pg_fetch_array($res);
         $GLOBALS['dir_andro']=$this->FS_AddSlash($row['dir_pub']).'andro/';
         $this->LogEntry("Node Manager directory: ".$GLOBALS['dir_andro']);
      }
	}
	else {
		$this->LogEntry("*** ERROR: Could not connect, perhaps password is wrong.");
		return false;
	}
	
	// Now make sure the database exists, and create
	// it otherwise
	//
	$cnx = 
		" dbname=".$parm["APP"].
		" user=".$parm["UID"].
		" password=".$pw;
	$con2 = pg_connect($cnx,PGSQL_CONNECT_FORCE_NEW);
	if (!$con2) {
		$this->LogEntry("Database does not exist, creating it now.");
      pg_query($GLOBALS["dbconna"],"create database ".$parm["APP"]);
		
		$con2 = pg_connect($cnx,PGSQL_CONNECT_FORCE_NEW);
		if (!$con2) {
			$this->LogError("Could not create database, unknown error, giving up.");
			return false;
		}
	}
	pg_close($con2);
	
	// Load driver and establish connection
	// Wow that comment "load driver" is from the java version!
	$cnx = 
		" dbname=".$parm["APP"].
		" user=".$parm["UID"].
		" password=".$pw;
	$this->LogEntry(preg_replace('/password=.*/','password=***',$cnx));
	$GLOBALS["dbconn1"] = pg_connect($cnx,PGSQL_CONNECT_FORCE_NEW);
	$GLOBALS["dbconn2"] = pg_connect($cnx,PGSQL_CONNECT_FORCE_NEW);

	$retval=true;
	if ($GLOBALS["dbconn1"]) $this->LogEntry("Connection 1 is up OK"); else $retval = false;
	if ($GLOBALS["dbconn2"]) $this->LogEntry("Connection 2 is up OK"); else $retval = false;
		
	return $retval;
}


function DB_Close()
{
	global $dbconn1,$dbconn2;
	$this->LogStage("Closing database connections (if open)");
	if ($dbconn1) { pg_close($dbconn1);$this->LogEntry("Closing connection #1"); }
	if ($dbconn2) { pg_close($dbconn2);$this->LogEntry("Closing connection #2"); }
}
// ==========================================================
// Perform vendor-specific commands required before a
// database can be used.  Currently we only support 
// PostgreSQL, so that is what is being done here
// ==========================================================
function DB_Prepare()
{
	$this->LogStage("Preparing Database, vendor-specific requirements.");
	$this->LogEntry("For PostgreSQL, make sure pl/pgSQL and pl/perl are installed.");
	$this->SQL(
		"CREATE FUNCTION plpgsql_call_handler() RETURNS language_handler AS ".
		"'\$libdir/plpgsql' LANGUAGE C;",true);
	$this->SQL(
		"CREATE TRUSTED PROCEDURAL LANGUAGE plpgsql ". 
		" HANDLER plpgsql_call_handler; ",true);
	$this->SQL(
		"CREATE FUNCTION plperl_call_handler() RETURNS language_handler AS ".
		"'\$libdir/plperl' LANGUAGE C;",true);
	$this->SQL(
		"CREATE LANGUAGE plperl ". 
		" HANDLER plperl_call_handler; ",true);
	$this->LogEntry("Dropping (if exists) schema zdd");
	$this->SQL("DROP SCHEMA zdd CASCADE ",true);
	$this->LogEntry("Creating schema zdd");
	$this->SQL("CREATE SCHEMA zdd");
	
	return true;
}

// ==========================================================
// Load stored procedures and the like
// ==========================================================
function SP_Load() {
	global $parm;
   $file = 'AndroDBB.sp.pgsql.7.4.sql';
	$retval = true;
	$this->LogEntry("");
	$this->LogEntry("Loading server-side code to use during build");
	$this->LogEntry("This is hard-coded to read and execute file $file");
   //$DIR=isset($parm['D*IR_LINK_LIB']) 
   //   ? $parm['DIR_LINK_LIB'] 
   //   : $parm['DIR_PUB'];
   $DIR=$parm['DIR_PUB'];
	$procs = file_get_contents($DIR."/lib/$file");
	$this->LogEntry("Program is expected to be in: ".$DIR."/lib/");

   // Significant change.  4/7/07.  This code used to be in a separate
   // file that we sucked in an executed in toto.  But on a fresh Ubuntu
   // install the functions would not load, and we got no error!  They could
   // be loaded from pgadmin3, but simply did not work here.  We moved them
   // into here and it works.  No explanation is known at this time. --KFD   
ob_start();
?>
CREATE OR REPLACE FUNCTION zdd.Table_Sequencer() RETURNS void AS
$BODY$
DECLARE
	rowcount integer := 1;
	lnSeq integer := 1;
BEGIN
	UPDATE zdd.tables_c set table_seq = 0;
	
	DELETE FROM zdd.table_deps;
	INSERT INTO zdd.table_deps 
		(table_id_par,table_id_chd)
		SELECT table_id_par, table_id 
		   FROM zdd.tabfky_c 
		   WHERE zdd.tabfky_c.nocolumns <> 'Y'
			  AND zdd.tabfky_c.table_id <> zdd.tabfky_c.table_id_par;
	UPDATE zdd.tables_c set table_seq = -1 
		FROM zdd.table_deps f 
		WHERE table_id = f.table_id_chd ;

	while rowcount > 0 LOOP
		UPDATE zdd.tables_c set table_seq = lnSeq
        	  FROM (SELECT t1.table_id_chd 
                          FROM zdd.table_deps t1 
                          JOIN zdd.tables_c t2 ON t1.table_id_par = t2.table_id
                         GROUP BY t1.table_id_chd
                        HAVING MIN(t2.table_seq) >= 0) fins
	         WHERE zdd.tables_c.table_id = fins.table_id_chd
        	   AND zdd.tables_c.table_seq = -1;

		lnSeq := lnSeq + 1;
		GET DIAGNOSTICS rowcount = ROW_COUNT;
	END LOOP;
	
	RETURN;
END;
$BODY$
LANGUAGE plpgsql;
<?php
   $routine=ob_get_clean();
   $this->SQL($routine);

ob_start();
?>
CREATE OR REPLACE FUNCTION zdd.column_sequencer()
  RETURNS void AS
$$
DECLARE
	rowcount integer := 1;
	lnSeq integer := 1;
BEGIN
	DELETE FROM zdd.column_seqs;
	INSERT INTO zdd.column_seqs
		(table_id,column_id,sequence)
		SELECT table_id,column_id,-1 FROM zdd.Column_deps
		UNION SELECT table_dep,column_dep,-1 FROM zdd.column_deps;
	
	UPDATE zdd.column_seqs set sequence = 0 
	  WHERE NOT EXISTS (Select table_id 
				FROM zdd.column_deps a
				 WHERE a.table_id = zdd.column_seqs.table_id
				   AND a.column_id = zdd.column_seqs.column_id
               AND a.table_id = a.table_dep
                                  );

	while rowcount > 0 LOOP
		UPDATE zdd.column_seqs set sequence = lnSeq
        	  FROM (SELECT t1.table_id,t1.column_id
                          FROM zdd.column_deps t1
                          JOIN zdd.column_seqs t2 
			    ON t1.table_dep = t2.table_id 
			   AND t1.column_dep = t2.column_id
                         WHERE t1.table_id = t1.table_dep
                         GROUP BY t1.table_id,t1.column_id
                        HAVING MIN(t2.sequence) >= 0) fins
	         WHERE zdd.column_seqs.table_id = fins.table_id
		   AND zdd.column_seqs.column_id = fins.column_id
        	   AND zdd.column_seqs.sequence = -1;

		lnSeq := lnSeq + 1;
		GET DIAGNOSTICS rowcount = ROW_COUNT;
	END LOOP;
	RETURN;
END;
$$
LANGUAGE 'plpgsql' VOLATILE;
<?php
   $routine=ob_get_clean();
   $this->SQL($routine);
   
	//if (!$this->SQL($procs))
	//{
	//	$this->LogEntry("Failure to load stored procedures is fatal, must stop.");
	//	$retval = false;
	//}
	//return $retval;
   return true;
}
// ==========================================================
// HC_DDRMake and related routines, make the server-side
// working tables
// ==========================================================
function DDRMake() {
	$defColWidth=$GLOBALS["defcolwidth"];
	global $parm;

	// Load and convert the DD arrays
	$this->LogStage("Bootstrapping the data dictionary tables");
	global $ddarr;
   $specboot = $parm["DIR_PUB"]."lib/".$parm["SPEC_BOOT"].".add";
      
	$this->DBB_LoadAddFile($specboot,$ddarr);
   
   // public service detour.  Write out a generated file of the ddarr 
   // array, which is later used by documentation generation stuff
   $fileOut = 
"<?php 
// ================================================================
// GENERATED CODE.  Associative Array version of AndroDBB.add
// File generated: ".date("l dS of F Y h:i:s A")."
// ================================================================ 
\$ddmeta = array(
".$this->zzArrayAsCode($ddarr,1)."
\t);
?>";
   $this->zzFileWriteGenerated($fileOut,"dd_AndroDBB.php");
   
   // 12/6/05, slows system down by constantly dropping and repopulating
   // these tables
	//$this->LogEntry("Dropping all data dictionary tables in public schema");
	//foreach ($ddarr["data"]["table"] as $key=>$table) {
	//	$this->SQL("DROP TABLE $key CASCADE",true);
	//}
	
	$retval = true;
	//$this->LogStage("Creating new empty build tables");
	//$this->DDRMake_Make("_s");
	//$this->DDRMake_Make("_n");
	//$this->DDRMake_Make("_h");
	$this->DDRMake_Make("_c");
	$this->DDRMake_Make("_r");
	$this->DDRMake_Make("_t");	// for pulling test results later
	
	//  These are tables used only once, only during build.
	//  For the DDL tables, the command sequence column can be:
	//  1000  - Destruct commands, nso's
	//  3000  - Table commands
	//  5000  - Construct commands, nso's
	//  9000  - Content commands
	$this->LogEntry("Creating single-use build tables");
	$strSQL =
		"CREATE TABLE zdd.ddl (".
		"  cmdseq char(4),". 
		"  cmdtype char(10),".  
		"  defines varchar(60),".  
		"  cmddesc char(50),".
		"  cmdsql text,".
		"  cmderr varchar(1000),".
		"  tsbeg timestamp,".
		"  tsend timestamp,".
		"  elapsed int,".
		"  executed_ok char(1)) WITH OIDS";
	$retval = $retval && $this->SQL($strSQL);
	
	$strSQL = 
		"CREATE TABLE zdd.triggers (".
		"  table_id char(". $defColWidth . "),".
		"  column_id char(". $defColWidth . "),". 
		"  action char(6),". 
		"  before_after char(6),".
      "  statement char(1),".
		"  sequence char(4),".
		"  cmdseq char(4),".
		"  code_fragment text)";
	$retval = $retval && $this->SQL($strSQL);

	$strSQL = 
		"CREATE TABLE zdd.column_deps (".
		"  table_id varchar(". $defColWidth . "),".
		"  column_id varchar(". $defColWidth . "),".
		"  table_dep varchar(". $defColWidth . "),".
		"  column_dep varchar(". $defColWidth . "),".
		"  automation_id varchar(10))";
	$retval = $retval && $this->SQL($strSQL);

	$strSQL = 
		"CREATE TABLE zdd.column_seqs (".
		"  table_id varchar(". $defColWidth . "),".
		"  column_id varchar(". $defColWidth . "),".
		"  sequence int)";
	$retval = $retval && $this->SQL($strSQL);
	
				
	$strSQL = 
		"CREATE TABLE zdd.table_deps (".
		"  table_id_chd varchar(". $defColWidth . "),".
		"  table_id_par varchar(". $defColWidth . "))";
	$retval = $retval && $this->SQL($strSQL);

	return $retval;
}

// This is a special routine that builds tables directly
// out of a dictionary array.  It is used only to build
// the data dictionary tables.
//                           
function DDRMake_Make($sSuffix)
{
	$this->LogEntry("Creating Build Tables, series ". $sSuffix);
	global $ddarr;
	$dd = &$ddarr["data"];
	$SQLCommon = ")";
	if ($sSuffix=="_c") { $SQLCommon = ",skey_hn int,skey_s int)"; }
	if ($sSuffix=="_s") { $SQLCommon = ",skey_s int)"; }
	
	$colprops = array("primary_key"=>"N", "type_id"=>"");
	
	// Walk through the tables and build them
	//
	foreach ($dd["table"] as $tabname=>$table) {
		$flat = array();
		$cols = "";
		if (isset($table["column"])) {
			foreach ($table["column"] as $c=>$column) {
				$t = $ddarr["data"]["column"][$c]["type_id"];
				$flat[$c] = array("primary_key"=>"N", "type_id"=>"");
				foreach ($column as $prop=>$val) { $flat[$c][$prop]=$val; }
				$flat[$c]["type_id"] = $t;
				$cols   
               .=$this->AddComma($cols)   
               .$c." ".
               $this->DDRMake_Make_Type($t
                  ,$this->zzArray($ddarr["data"]["column"][$c],"colprec")
               );
			}
		}
		if (isset($table["foreign_key"])) {
			foreach ($table["foreign_key"] as $tp0=>$fkey) {
				$tp = $fkey["__keystub"];
				$fkp= trim($this->zzArray($fkey,"prefix"));
				$fks= trim($this->zzArray($fkey,"suffix"));
				//$this->LogEntry("table $tabname to $tp");
				//if (isset($fkey["suffix"])) { $fks=$fkey["suffix"]; }
				if ($this->zzArray($fkey,"nocolumns")=="Y") { continue; }
				foreach ($ddarr["data"]["table"][$tp]["column"] as $c=>$column) {
					if ($this->zzArray($column,"primary_key")=="Y") {
						$t = $ddarr["data"]["column"][$c]["type_id"];
						$flat[$fkp.$c.$fks] = array("primary_key"=>$this->zzArray($fkey,"primary_key"), "type_id"=>$t);
						
						$cols.=$this->AddComma($cols).$fkp.$c.$fks." ".$this->DDRMake_Make_Type($t,$this->zzArray($ddarr["data"]["column"][$c],"colprec"));
					}
				}
				
			}
		}
		
		$GLOBALS["ddflat"][$tabname] = $flat;
		
		$SQL = "CREATE TABLE zdd.".$tabname.$sSuffix." (".$cols.$SQLCommon;
		$this->SQL($SQL);

		// Generate pk list and put this stuff into the simulated mini $utabs global
		$pklist = "";
		foreach ($flat as $colname=>$colprops) {
			if ($colprops["primary_key"]=="Y") { $pklist.=$this->AddComma($pklist).$colname; }
		}
		$GLOBALS["utabs"][$tabname] = array("flat"=>$flat,"pk"=>$pklist);
	}
}

function DDRMake_Make_Type($t,$colprec) {
	if ($t=="numb")  { return "numeric(".$colprec.")"; }
	if ($t=="vchar") { return "varchar(".$colprec.")"; }
	if ($t=="char")  { return "char(".$colprec.")"; }
	if ($t=="int")   { return "int"; }
	if ($t=="text")  { return "text"; }	
}
// ==========================================================
// REGION: Reality Get
// There is a SQL standard system catalog supported at least
// in PostgreSQL and MS SQL Server that puts lots of "reality"
// information into a schema called INFORMATION_SCHEMA.  This
// is really good because it gives cross-platform support, but
// the information is useless if you want to compare things
// like the actual text or body of views, triggers and so 
// forth.  As far as has been determined, INFORMATION_SCHEMA
// is good for table and column specifics, but after that
// it is good only to determine *existence* of items.
// ==========================================================
function RealityGet()
{
	$this->LogStage("Querying server for current state of database");
	// Whatever we can, get from Information_Schema
	$this->RealityGet_TablesColumns();
	$this->RealityGet_Groups();
	
	// Whatever is not in the Information_Schema must be
	// obtained from the DBMS system catalogs, which are
	// proprietary to each vendor
	$this->RealityGet_NSO();
	return true;
}
	
// This particular approach is childishly easy
function RealityGet_TablesColumns()
{
	$this->LogEntry("Getting list of tables from INFORMATION_SCHEMA (S)");
	$this->SQL(
		"insert into zdd.tables_r (table_id) ".
		" SELECT table_name as table_id ".
		" FROM information_schema.tables ". 
		" WHERE table_schema = 'public'".
		"   AND table_type = 'BASE TABLE'");

	// The columns we pull down and process here and send back	
	$this->LogEntry("Getting column information from INFORMATION_SCHEMA (S)");
	$this->SQL(
"insert into zdd.tabflat_r 
 (table_id,column_id,formshort,colprec,colscale)  
 SELECT c.table_name,c.column_name, 
        CASE WHEN POSITION('timestamp' IN data_type) > 0 THEN 'timestamp'
	          WHEN POSITION('character varying' IN data_type) > 0 THEN 'varchar'
	          WHEN POSITION('character' IN data_type) > 0 THEN 'char'
             WHEN POSITION('integer' IN data_type) > 0 THEN 'int'
             ELSE data_type END,
        CASE WHEN POSITION('character' IN data_type) > 0 THEN character_maximum_length
		       WHEN POSITION('numeric'   IN data_type) > 0 THEN numeric_precision 
				 ELSE 0 END,
        CASE WHEN POSITION('numeric'   IN data_type) > 0 THEN numeric_scale
		       ELSE 0 END
   FROM information_schema.columns c 
   JOIN information_schema.tables t ON t.table_name = c.table_name  
  WHERE t.table_schema = 'public' 
    AND t.table_type   = 'BASE TABLE'");
    
  // HARDCODE Formulas
  $sql = "
update zdd.tabflat_r set formula =
 case when formshort in ('char','varchar') then formshort || '(' || colprec  || ')'
      when formshort = 'numeric' then 'numeric(' || colprec || ',' || colscale || ')'
      else formshort end";
  $this->SQL($sql);
}

function RealityGet_Groups() {
	$this->LogEntry("Pulling definitions of existing groups (S)");
	$this->SQL("insert into zdd.groups_r select cast(rolname as char(35)) from pg_roles");
}

function RealityGet_NSO()
{
	// Here is the fancy switch on server type
	$this->RealityGet_NSO_Indexes();
	$this->RealityGet_NSO_Triggers();
	$this->RealityGet_NSO_Sequences();
}

function RealityGet_NSO_Indexes()
{
	$this->LogEntry("Pulling definitions of existing indexes (C)");
	$results = $this->SQLRead(
		"Select tablename,indexname,indexdef ".
		" FROM pg_indexes".
		" WHERE schemaname='public'");
	while ($row=pg_fetch_array($results)) {
		$object_id = strtolower($row["indexname"]);
		$table_id = strtolower($row["tablename"]);
		//$this->LogEntry("Index $object_id for table $table_id");
		
		$cols = strtolower($row["indexdef"]);
		//$this->LogEntry("Raw definition: $cols");
		$cols = substr($cols, strpos($cols,"(")+1 );
		//$this->LogEntry("First Clip    : $cols");
		$cols = substr($cols,0,strpos($cols,")"));
		//$this->LogEntry("Second Clip   : $cols");
		$cols = str_replace(" ","",$cols);
		$cols = str_replace('"',"",$cols);
		//$this->LogEntry("Lose Spaces   : $cols");

		$this->SQL(
			"Insert into zdd.ns_objects_r ".
			"(object_id,definition,def_short,sql_drop)".
			" VALUES (".
			"'". $object_id . "',". 
			"'idx:". $table_id . ":". $cols . "',".
			"'idx:". $table_id . ":". $cols . "',".
			"'DROP INDEX ". $object_id . "')");
	}
}

function RealityGet_NSO_Triggers()
{
	$this->LogEntry("Pulling definitions of existing triggers (C)");
	
	// Triggers in PostgreSQL are weird because you have to
	// create a function, then you create a trigger definition
	// that calls that function.  What we are doing here is 
	// pulling the trigger definitions, not the function definitions.
	// When we have a "drop" situation, the function is left there
	// though it is no longer being used.
	//
	$results = $this->SQLRead(
		"SELECT tr.tgname,tr.tgtype,tab.tablename from pg_trigger tr ".
		"  JOIN pg_class cls on tr.tgrelid = cls.oid ".
		"  JOIN pg_tables tab ON cls.relname = tab.tablename ".
		" WHERE tab.schemaname = 'public'");
	while ($row=pg_fetch_array($results)) { 
		$tgname = strtolower($row["tgname"]);
		$tgtype = $row["tgtype"];
		$table_id = strtolower($row["tablename"]);
	
		$ba = "";
		$iud= "";
		if ( ($tgtype & 2) == 2) { $ba = "before"; } else {$ba="after";} 
		if ( ($tgtype & 4) == 4) {$iud = "insert"; }
		if ( ($tgtype & 8) == 8) {$iud = "delete"; }
		if ( ($tgtype &16) ==16) {$iud = "update"; }
		
		$define = "trgt:". $table_id . ":". $iud . ":". $ba;
	
		$this->SQL(
			"Insert into zdd.ns_objects_r ".
			"(object_id,definition,def_short,sql_drop)".
			" VALUES (".
			"'". $tgname . "',". 
			"'". $define . "',".
			"'". $define . "',".
			"'DROP TRIGGER ". $tgname . " ON ". $table_id . "')");
	}
}


function RealityGet_NSO_Sequences()
{
	$this->LogEntry("Pulling definitions of existing sequences (S)");

	$this->SQL("
 Insert into zdd.ns_objects_r 
 (object_id,definition,def_short,sql_drop)
  SELECT
  'sequence_' || LOWER(RTRIM(relname)), 
  'sequence:' || LOWER(RTRIM(relname)),
  'sequence:' || LOWER(RTRIM(relname)),
  'DROP SEQUENCE ' || LOWER(RTRIM(relname))
  FROM pg_class WHERE relkind='S'");	
}

// ==========================================================
// REGION: SPECLOAD
// ==========================================================
function SpecLoad() {
	$retval = true;
	global $parm;
   global $srcfile;
	
	// Load the data dictionary to itself
   $sfx="_c";
	$this->LogStage("Processing bootstrap DD specification into series $sfx");
	$this->LogEntry("Loading spec to series $sfx");
   $srcfile='AndroDBB';
	$retval = $retval && $this->SpecLoad_ArrayToTables(
      $GLOBALS["ddarr"]["data"]
      ,$sfx
   );
	$this->LogEntry("Populating reference tables in series $sfx");
	$this->DBB_LoadContent(true,$GLOBALS["ddarr"]["content"],"zdd.",$sfx);
	
	// Load any library specification
   $sfx="_c";
   //if(isset($parm['D*IR_LINK_LIB'])) {
   //   $spec_lib= $parm["DIR_LINK_LIB"]."/lib/".$parm["SPEC_LIB"].".add";
   //}
   //else {
      $spec_lib= $parm["DIR_PUB"]."lib/".$parm["SPEC_LIB"].".add";
   //}
	$this->LogStage("Processing library spec to series $sfx: ");
	$ta = array();
	$this->DBB_LoadAddFile($spec_lib,$ta);
	$this->LogEntry("Loading spec to series $sfx");
   $srcfile='andro_universal';
	$retval = $retval && $this->SpecLoad_ArrayToTables(
      $ta["data"]
      ,$sfx
   );
	$this->LogEntry("Setting aside content for loading after build");
	$GLOBALS["content"] = array_merge($GLOBALS["content"],$ta["content"]);

	// Load all other specification files
	$this->LogStage("Loading new specification into build tables series $sfx");
	$ta = array();
	if ($parm["SPEC_LIST"]<>"") {
		$speclist = explode(",",$parm["SPEC_LIST"]);
		foreach ($speclist as $spec) {
         $srcfile=$spec;
         // KFD 4/16/07, Added support for YAML specs
         if(substr($spec,-5)<>'.yaml') { 
            $spec = $parm["DIR_PUB"]."application/".$spec.".add";
         }
         else {
            $spec = $parm["DIR_PUB"]."application/".$spec;
            $this->LogEntry("Looking or spec in YAML format: ");
            $this->LogEntry("   ".$spec);
         }
         //}
         if (!file_exists($spec)) {
            $this->LogEntry(" >>>> Missing Specification File");
            $this->LogEntry(" >>>> File Not Found: $spec");
            $this->LogEntry(" >>>> ");
            $this->LogEntry(" >>>> If this is the first time building this app");
            $this->LogEntry(" >>>> then this is not a problem, because the ");
            $this->LogEntry(" >>>> directories are just now being built.  But  ");
            $this->LogEntry(" >>>> if you have created a spec file and are expecting");
            $this->LogEntry(" >>>> it to be built, then it must be misnamed or");
            $this->LogEntry(" >>>> or in the wrong directory.");
         }
         else {
            // KFD 4/16/07, Added support for YAML specs
            if(substr($spec,-5)<>'.yaml') { 
               $this->DBB_LoadAddFile($spec,$ta);
            }
            else {
               $retval = $this->DBB_LoadYAMLFile($spec,$ta);
            }
            if($retval) {
               $this->LogEntry("Loading spec to series $sfx");
               $retval=$retval && $this->SpecLoad_ArrayToTables(
                  $ta["data"]
                  ,$sfx
               );
               $this->LogEntry("Setting aside content for loading after build");
               $GLOBALS["content"] = array_merge($GLOBALS["content"],$ta["content"]);
            }
         }
		}
	}
	return $retval;
}

//   A walk in the park.  Or perhaps a walk of the tree.
//
function SpecLoad_ArrayToTables($arr,$cLoadSuffix,$parent_row=array(),$parent_prefix="") {
	global $ddarr;
   global $srcfile;
   global $parm;
	$retval = true;
	
	foreach ($arr as $keyword=>$object) {
		// Ignore prop/value pairs at even-numbered rows
		if (! is_array($object)) { continue; }

		// Now use $keyword to get table name we will insert into
      //$this->LogEntry("$parent_prefix - $keyword");
		$table     = $ddarr["meta"]["keyword"][$parent_prefix.$keyword]["table"];
		$pkcolname = $ddarr["meta"]["keyword"][$parent_prefix.$keyword]["keycol"];
		$keystub   = $this->zzArray($ddarr["meta"]["keyword"][$parent_prefix.$keyword],"keystub");
		//echo "TABLE is $table, pk and keystub are $pkcolname and $keystub <br>";

		$dd = &$GLOBALS["ddarr"]["data"]["table"][$table];
		if ($table=="") {
			$this->LogEntry("ERROR: Could not find table to match keyword ".$parent_prefix.$keyword);
			$retval = false;
			continue;
		}
		
		foreach ($object as $pkvalue=>$properties) {
			$row=array();
			// Use $keyword to get name of one of the columns at least
			if (!is_numeric($pkvalue)) {
				$row[ $pkcolname ] = $pkvalue;
			}
			
			// Whatever is not a child object is a property/value pair
			foreach ($properties as $colname=>$colvalue) {
				if (! is_array($colvalue)) {
					$row[$colname] = $colvalue;
					if ($keystub <> "" && $colname == "__keystub") { 
						$row[$keystub] = $colvalue;
					}
				}
			}
			
			// Add/override any col/values from parent object if nested
			foreach ($parent_row as $colname=>$colvalue) {
				$row[$colname] = $colvalue;
			}
         
			// HARDCODED BREAK POINT.  Anything we want to do 
			// to deal with the spec has to be done here 
			//
			if ($table=="tabchaintests" || $table=="colchaintests") {
            // For chain tests, parse out the expressions
				$pfx = substr($table,0,3);
				$this->SpecLoad_ArrayToTables_HC_cargs($row,"compare","compoper",$cLoadSuffix,$pfx);
				$this->SpecLoad_ArrayToTables_HC_cargs($row,"return" ,"funcoper",$cLoadSuffix,$pfx);				
			}
         if ($keyword=='child_table') {
            // Flip two columns to make this property of child table instead
            $x = $row['table_id'];
				$row['table_id'] = $row['table_id_par'];
            $row['table_id_par'] = $x;
         }

			// Add the primary key columns for this object into the "parent_row"
			// array, then recurse child objects.
			$pr = $parent_row;
			foreach ($row as $colname=>$colvalue) {
				if (isset($GLOBALS["ddflat"][$table][$colname])) {
					if ($this->zzArray($GLOBALS["ddflat"][$table][$colname],"primary_key")=="Y") {
						$pr[$colname] = $colvalue;
					}
				}
			}
			$retval = $retval && 
				$this->SpecLoad_ArrayToTables($properties,$cLoadSuffix,$pr,$parent_prefix.$keyword."_");
				

         // MORE HARDCODE STUFF.  These are any hardcoded things that
         //    must occur immediately before writing to file, but should
         //    not propagate through to child tables because the action
         //    would be repeated in the child table.
         //
         if (isset($row['group_id'])) {
            if($row['group_id']<>$parm['APP']) {
               $row['group_id']=$parm['APP'].'_'.$row['group_id'];
            }
         }
            
			// Finally, execute it
         $row['srcfile']=$srcfile;
			$this->DBB_Insert("zdd.",$table,$cLoadSuffix,$row);
		}
	}
	return $retval;
}

function SpecLoad_ArrayToTables_HC_cargs(&$row,$element,$rowcol,$cLoadSuffix,$pfx) {
	$thevalue = $this->zzArray($row,$element);
	if ($thevalue <> "") {
		
		// Parse comparisons and returns slightly differently
		// Compare: if any comma, it is delimiter, else space is delimeter
		// Return:  if any @ sign, use same as compare, else no parse
		if ($element=="return") {
			if (strpos($thevalue,"@")===false) $args = array($thevalue);
		}
		if (!isset($args)) {
			$delim = strpos($thevalue,",")===false ? " " : ",";
			$args  = explode($delim,$thevalue);
		}

		$newrow = $row;
		$newrow["argtype"] = ($element=='compare') ? '0' : '1';
		if (isset($row["column_id"])) $newrow["column_id"] = $row["column_id"];
		foreach($args as $index=>$value) {
			if ($index==1)        { $row[$rowcol] = $value; continue; }
			if (trim($value)=="" && $delim==' ') { continue; }
			$newrow["column_id_arg"] = $newrow["retval"] = "";
			if (substr($value,0,1)=="@") 
				$newrow["column_id_arg"] = substr($value,1);
			else 
				$newrow["literal_arg"] = $value; 
			$newrow["sequence"]=$index;
         // added 3/8/06 under mysterious circumstances, absolutely
         // never needed this.  W/o it we got dupes on uicolseq during
         // spec validation, but cannot find out why never got dupes before
         // Then removed 3/10/06 because it causes chains to disappear
         // because uicolseq doesn't match
         //$newrow['uicolseq']=++$GLOBALS['uicolseq'];
			$this->DBB_Insert("zdd.",$pfx."chainargs",$cLoadSuffix,$newrow);
		}
	}
}

// ==========================================================
// REGION: Specification Resolution
// ==========================================================
function SpecResolve()
{
	$this->LogStage("Resolving Database Specification");
	$this->LogEntry("Each primary table will receive a UNION VIEW of _h and _n ");
	$this->LogEntry("      with name prefix zdd_hn_");
	$this->LogEntry("Each table will resolve _s against _hn in a VIEW " );
	$this->LogEntry("      with name prefix zddrxv_shn_");
	$this->LogEntry("Each table's final resolution will go into the *_c series of tables.");

	//  This operation is considerably simplified by the fact that
	//  the _h and _n specs will never overlap.  That allows us to
	//  union them together and overlay the result with _s.  If we
	//  had to overlay three together it would actually be rather
	//  nasty.
	//
	foreach ($GLOBALS["utabs"] as $OneTab=>$table) { 
		if ($this->zzArray($table,"table_seq")<5000) {
			$list1=$list2=$list3=$keys1="";
			//$this->LogEntry("Resolving ".$OneTab);
			
			foreach ($table["flat"] as $col=>$colprops) {
				$list1 .= $this->AddComma($list1) . $col;
				$list3 .= $this->AddComma($list3) . $col;
				$list2 .= $this->AddComma($list2) . "COALESCE(hn.$col,s.$col) as \"$col\"";
				if ($colprops["primary_key"]=="Y") {
					$keys1.=$this->AddList($keys1," AND ")."hn.$col = s.$col";
				}
			}
	
			//' Create the union of h and n
			$this->SQL( 
				"CREATE OR REPLACE VIEW zdd.resolve_hn_$OneTab as ".
				"Select skey as skey_hn,". $list1 . " From zdd.$OneTab"."_n ".
				"UNION ALL ".
				"Select skey as skey_hn,". $list1 . " From zdd.". $OneTab ."_h");
	
			//' Overlay h and n against s
			$this->SQL( 
				"CREATE OR REPLACE VIEW zdd.resolve_hns_". $OneTab . " as ".
				"Select s.skey as skey_s,hn.skey_hn,". $list2 .
				"  FROM zdd.". $OneTab . "_s s ".
				"  FULL JOIN zdd.resolve_hn_". $OneTab . " hn ".
				"    ON ". $keys1);
	
			//' Materialize the result
			$this->SQL("Insert into zdd.". $OneTab . "_c ".
				"(". $list3 . ",skey_s,skey_hn) ".
				" SELECT ". $list3 . ",skey_s,skey_hn FROM zdd.resolve_hns_". $OneTab);
		}
	}
	return true;
}

// ==========================================================
// REGION: Specification Pre-Validation
// All validation occurs after the spec has been 
// flattened.  However, some errors may cause the flatten
// operation itself to fail, so we check for those here.
// ==========================================================
function SpecFlattenValid() {
	// the only known hazard at present is a table that
	// has an fk to itself, and that fk is part of its pk.
	// That would be an infinite regression, can't do that.
	//
	$errors = 0;
	$sql = 
		"SELECT table_id FROM zdd.tabfky_c ".
		" WHERE table_id = table_id_par ".
		"   AND primary_key = 'Y'";
	$results=$this->SQLRead($sql);
	while ($row = pg_fetch_array($results)) {
		$this->LogEntry("** VALIDATION FAILURE: Table ".$row["table_id"]." has foreign key ");
		$this->LogEntry("****                     to itself with 'primary_key: Y', this is a ");
		$this->LogEntry("****                     circular reference of a primary key.");
		$this->LogEntry("");
		$errors++;
	}
	return ($errors==0);
}

// ==========================================================
// REGION: Spec Flatten.  Runs out table definitions,
// and runs out security definitions
// ==========================================================
function SpecFlatten() {
	$this->LogStage("Flattening Definitions");
	$retval = true;
	$retval = $retval && $this->SpecFlatten_Columns1();
	$retval = $retval && $this->SpecFlatten_Columns2();
	$retval = $retval && $this->SpecFlatten_Tables();
	$retval = $retval && $this->SpecFlatten_HARDCODE();
	$retval = $retval && $this->SpecFlatten_ColumnDeps();
	$retval = $retval && $this->SpecFlatten_PseudoColumnDeps();
	$retval = $retval && $this->SpecFlatten_Security();
	return $retval;
}

function SpecFlatten_Columns1() {
	$this->LogEntry("Converting automations defined in foreign keys");
   
   // Generating this shorthand makes things easier                
   $sql 
      ="update zdd.tabfkyautocols_c "
      ."set column_id_arg = rtrim(prefix)||rtrim(column_id)||rtrim(suffix)";
   $this->SQL($sql);

   // Put columns into parent tables if not there already
   $sql="
insert into zdd.tabcol_c ( 
   table_id
  ,column_id,column_id_src
  ,suffix,prefix
  ,uicolseq
)
select table_id_par,column_id,column_id
      ,'',''
      ,MAX(uicolseq)
from zdd.tabfkyautocols_c 
where not exists 
  (select table_id,column_id
     from zdd.tabcol_c
    WHERE zdd.tabcol_c.table_id  = zdd.tabfkyautocols_c.table_id_par
      AND zdd.tabcol_c.column_id = zdd.tabfkyautocols_c.column_id
  )
 GROUP BY table_id_par,column_id
      ";
   $this->SQL($sql);
   $sql = "
update zdd.tabcol_c set 
	automation_id = UPPER(x.automation_id)
	,auto_formula = x.table_id||'.'||x.column_id_arg
   ,auto_suffix  = x.suffix
   ,auto_prefix  = x.prefix
  FROM zdd.tabfkyautocols_c x
 WHERE x.table_id_par = zdd.tabcol_c.table_id
   AND x.column_id    = zdd.tabcol_c.column_id
   AND UPPER(x.automation_id) IN ('SUM','COUNT','LATEST')";   
   $this->SQL($sql);
   
   
   // Put columns into child tables if not there already                
   $sql="
insert into zdd.tabcol_c 
  (table_id,column_id,column_id_src,suffix,prefix,uicolseq)
select table_id,column_id_arg,column_id,suffix,prefix,MAX(uicolseq)
from zdd.tabfkyautocols_c 
where not exists 
   (select table_id,column_id
      from zdd.tabcol_c
     WHERE zdd.tabcol_c.table_id  = zdd.tabfkyautocols_c.table_id
       AND zdd.tabcol_c.column_id = zdd.tabfkyautocols_c.column_id_arg
       AND zdd.tabcol_c.prefix    = zdd.tabfkyautocols_c.prefix        
       AND zdd.tabcol_c.suffix    = zdd.tabfkyautocols_c.suffix    
     )
 GROUP BY table_id,column_id_arg,column_id,suffix,prefix";
   $this->SQL($sql);
   $sql = "
update zdd.tabcol_c set 
	automation_id = UPPER(x.automation_id)
	,auto_formula = x.table_id_par||'.'||x.column_id
   ,auto_suffix  = x.suffix
   ,auto_prefix  = x.prefix
  FROM zdd.tabfkyautocols_c x
 WHERE x.table_id      = zdd.tabcol_c.table_id
   AND x.column_id_arg = zdd.tabcol_c.column_id
   AND x.suffix        = zdd.tabcol_c.suffix
   AND x.prefix        = zdd.tabcol_c.prefix
   AND UPPER(x.automation_id) IN ('FETCH','FETCHDEF','DISTRIBUTE')";   
   $this->SQL($sql);
   
   return true;
}   
   
   


function SpecFlatten_Columns2() {
	$this->LogEntry("Adding 'alltables' columns to all tables");
	// Distribute into tables those columns that have the
	// "alltables" flag set that are not already in the table.
	//
	
	$this->SQL("
		insert into zdd.tabcol_c (table_id,column_id,column_id_src,uino)
		select t.table_id,c.column_id,c.column_id,c.uino
		  from zdd.tables_c t, zdd.columns_c c
		 where c.alltables = 'Y'
		   and not exists 
		      (select table_id,column_id 
			      from zdd.tabcol_c
			     WHERE table_id = t.table_id 
				    AND column_id = c.column_id)");

                
	$this->LogEntry("Indexing every table on skey");
	$this->SQL("INSERT INTO zdd.tabidx_c (idx_name,idx_unique,table_id) ".
		"SELECT RTRIM(t.table_id) || '_skey_idx','N',t.table_id FROM zdd.tables_c t ".
		"WHERE NOT EXISTS (SELECT * FROM zdd.tabidx_c WHERE idx_name = RTRIM(t.table_id) || '_skey')");
	$this->SQL("INSERT INTO zdd.tabidxcol_c (idx_name,idx_unique,table_id,column_id) ".
		"SELECT RTRIM(t.table_id) || '_skey_idx','N',t.table_id,'skey' FROM zdd.tables_c t ".
		"WHERE NOT EXISTS (SELECT * FROM zdd.tabidx_c WHERE idx_name = RTRIM(t.table_id) || '_skey')");
	return true;
}


function SpecFlatten_Tables()
{
	global $utabs;
	
	$this->LogEntry("TABLES: Executing server-side table sequencer: Table_Sequencer");
	$this->SQL("select zdd.table_sequencer();");
	$results = $this->SQLRead("select table_id FROM zdd.tables_c WHERE table_seq<0");
	$errors = pg_fetch_all($results);
	if (false!==$errors) { 
		$this->LogEntry("There were ".count($errors)." tables that could not be sequenced:");
		foreach ($errors as $error) {
			$t = $error["table_id"];
			$this->LogEntry("Cannot sequence $t, which depends upon: ");
			$results = $this->SQLRead("Select table_id_par FROM zdd.table_deps WHERE table_id_chd='$t'");
			while ($row=pg_fetch_array($results)) { $this->LogEntry("   ".$row["table_id_par"]); }
		}
		return false;
	}

	// Fetch the list of tables and begin (re)building utabs
	$this->LogEntry("TABLES: Flattening table definitions");
	$r1 = $this->SQLRead("Select * FROM zdd.tables_c ORDER BY table_seq");
	$i=0;
	$ilimit = pg_num_rows($r1);
	while ($row = pg_fetch_array($r1,$i,PGSQL_ASSOC)) {
		$tab = $row["table_id"];
		$this->DBB_RunOut($tab,"_c");
		$utabs[$row["table_id"]] = $row;
		$utabs[$row["table_id"]]["indexes"] = array();
		$i++;
		if ($i == $ilimit) { break; }
	}

	return true;
}

function SpecFlatten_HARDCODE() {
	$this->LogEntry("HARDCODE: rendering all formulas");
	$this->SQL(
		"UPDATE zdd.tabflat_c set formula = ".
		"  REPLACE(REPLACE(formula,'<colprec>',cast(colprec as char(5))), ".
		"          '<colscale>',cast(colscale as char(5))),".
		"  dispsize = REPLACE(dispsize,'<colprec>',cast(colprec as char(5)))");
	$this->SQL("UPDATE zdd.tabflat_c set formula = REPLACE(formula,'#',',')");


	$this->LogEntry("HARDCODE: Parsing auto_formula for SUM, FETCH, etc.");
	$this->SQL("
update zdd.tabflat_c
  SET auto_table_id = SUBSTR(auto_formula,1,POSITION('.' in auto_formula)-1),
      auto_column_id = SUBSTR(auto_formula,POSITION('.' in auto_formula)+1)
 where automation_id IN ('FETCH','FETCHDEF','DISTRIBUTE','SUM','COUNT')");

 	$this->LogEntry("HARDCODE: Adding chain arguments to validation table");
	$this->SQL("
Insert into zdd.colchainargsv_c  
	(uicolseq,table_id,column_id )
select DISTINCT uicolseq,table_id,column_id_arg from zdd.colchainargs_c
 WHERE column_id_arg <> ''");
	$this->SQL("
Insert into zdd.colchainargsv_c  
	(uicolseq,table_id,column_id )
select DISTINCT uicolseq,table_id,column_id_arg from zdd.tabchainargs_c
 WHERE column_id_arg <> ''");
 
 	$this->LogEntry("HARDCODE: Setting automation_id of calc'd columns");
	$this->SQL("
Update zdd.tabflat_c SET automation_id = 'EXTEND'
  FROM zdd.colchains_c 
 WHERE zdd.tabflat_c.table_id  = zdd.colchains_c.table_id 
   AND zdd.tabflat_c.column_id = zdd.colchains_c.column_id
	AND zdd.colchains_c.chain = 'calc'");
	
	 return true;
}

function SpecFlatten_ColumnDeps() {
   $retval=true;
	$this->LogEntry("COLUMN DEPENDENCIES: From Chains");
	$sql =
		"INSERT INTO zdd.column_deps ".
		" (table_id,column_id,table_dep,column_dep,automation_id) ".
		"SELECT DISTINCT table_id,column_id,table_id,column_id_arg,'EXTEND' ".
		"  FROM zdd.colchainargs_c  ".
		" WHERE zdd.colchainargs_c.column_id_arg <> ''";
	$this->SQL($sql);		

	// This says that the foreign key depends upon each
	// of the fk parent columns.  This way an FK that is
	// itself supplied by a calculation can be sequenced
	// so that it is checked only after the calculation.
	//
	// 8/20/2005
	// CHANGED. There may be a bug here.  It used to refer
	//    to parent column name, which makes no sense, so we
	//    changed it to child column name, but there was
	//    never any bug that we knew of, so maybe it was
	//    supposed to be that way (can't really figger why
	//    it would be though).
	//
	$this->LogEntry("COLUMN DEPENDENCIES: Foreign keys (columns)");
	$this->SQL(
"INSERT INTO zdd.column_deps 
 (table_id,column_id,table_dep,column_dep,automation_id)
 select table_id,'FK:' || table_id_fko,table_id,column_id,'FK' 
   FROM zdd.tabflat_c 
  WHERE table_id_fko || column_id_fko <> ''");
	$this->LogEntry("COLUMN DEPENDENCIES: Foreign keys (nocolumns)");
	$this->SQL(
"INSERT INTO zdd.column_deps 
 (table_id,column_id,table_dep,column_dep,automation_id)
select DISTINCT
       fk.table_id,'FK:' || fk.table_id_par,fk.table_id,
       trim(fk.prefix) || TRIM(f.column_id) || fk.suffix,'FK'
  FROM zdd.tabfky_c fk
  JOIN zdd.tabflat_c f ON fk.table_id_par = f.table_id
 WHERE f.primary_key = 'Y'
   AND fk.nocolumns = 'Y'");
	$this->LogEntry("COLUMN DEPENDENCIES: Foreign keys, Auto-Insert/CopySameCols");
	$this->SQL(
"INSERT INTO zdd.column_deps 
 (table_id,column_id,table_dep,column_dep,automation_id)
SELECT DISTINCT 
       k.table_id,'FK:' || k.table_id_par,k.table_id,f1.column_id,'FK'
  from zdd.tabfky_c  k
  JOIN zdd.tabflat_c f1 ON k.table_id     = f1.table_id 
  JOIN zdd.tabflat_c f2 ON k.table_id_par = f2.table_id 
 WHERE k.auto_insert='Y'
   AND k.copysamecols='Y'
   AND f1.column_id = f2.column_id
   AND f1.column_id <> 'skey'
   AND f1.column_id <> 'skey_quiet'
   AND f2.primary_key <> 'Y'");

	$this->LogEntry("COLUMN DEPENDENCIES: Fetch, Sum, Distribute, etc.");
	$this->SQL(
"INSERT INTO zdd.column_deps 
 (table_id,column_id,table_dep,column_dep,automation_id) 
 SELECT table_id,column_id,auto_table_id,auto_column_id
       ,CASE WHEN automation_id='DISTRIBUTE' OR automation_id='FETCHDEF' 
             THEN 'FETCH' 
             ELSE automation_id END
   FROM zdd.tabflat_c
  WHERE auto_table_id || auto_column_id <> ''"); 


	$this->LogEntry("COLUMN DEPENDENCIES: Fetch & Distribute 2nd entries");
$this->SQL(
"INSERT INTO zdd.column_deps 
 (table_id,column_id,table_dep,column_dep,automation_id) 
 SELECT table_id,column_id,table_id,'FK:'||auto_table_id,'FK(FETCH)'
   FROM zdd.tabflat_c
  WHERE auto_table_id || auto_column_id <> ''"); 
  
  $this->LogEntry("COLUMN DEPENDENCIES: Generating column sequencing");
  $this->SQL("select zdd.Column_Sequencer()");
   $results=$this->SQLRead(
      "SELECT * FROM zdd.column_seqs where sequence<0"
   );
   $badcols=pg_fetch_all($results);
   if($badcols) {
      $this->LogEntry("");
      $this->LogEntry(">> ERROR WITH COLUMN SEQUENCING");
      $this->LogEntry(">>  There is probably a circular dependency");
      $this->LogEntry(">>  This can also be caused by a column being defined");
      $this->LogEntry(">>  twice in the same table, which the parser cannot ");
      $this->LogEntry(">>  detect at this time.");
      foreach($badcols as $badcol) {
         $tid=$badcol['table_id'];
         $cid=$badcol['column_id'];
         $this->LogEntry(
            "ERROR: UNSEQUENCED COLUMN $tid.$cid"
         );
         $this->LogEntry("   Here are the dependencies:");
         $retval=false;
         $sq="SELECT * FROM zdd.column_deps 
                WHERE (table_id = '$tid' AND column_id='$cid')
                   OR (table_dep= '$tid' AND column_dep='$cid')";
         $res=$this->SQLRead($sq);
         while($row=pg_fetch_array($res)) {
            $this->LogEntry(
               "   -> Column "
               .str_pad($row['table_id'].'.'.$row['column_id'],30)
               .' depends upon '
               .$row['table_dep'].'.'.$row['column_dep']
            );
         }
      }
   }
   
  	return $retval;
}

function SpecFlatten_PseudoColumnDeps() {
   return true;
   /*  Turned off because there is no parent table holding these
  $this->LogEntry("PSEUDO-COLUMN DEPENDENCIES: From Defaults");
  $this->SQL("
INSERT INTO zdd.pcoldeps_c (table_id,column_id,pcolumn)
 SELECT table_id,column_id,auto_formula
   FROM zdd.tabflat_c
  WHERE automation_id = 'DEFAULT'
    AND SUBSTRING(auto_formula from 1 for 1) = '%'"
	 );
	 return true;
    */
}


// Helper to specflatten_security 
function GroupBinary($gcount,$prefix,$gkeys,&$gnumb) {
   // Clip off next, then figure out if we are at end 
   $group=array_shift($gkeys);
   if(count($gkeys)==0)  {
      $this->GroupBinary_Make($gcount+1,$prefix.'+'.$group,$gnumb);
      $this->GroupBinary_Make($gcount  ,$prefix           ,$gnumb);
   }
   else {
      $this->GroupBinary($gcount+1,$prefix.'+'.$group,$gkeys,$gnumb);
      $this->GroupBinary($gcount  ,$prefix           ,$gkeys,$gnumb);
   }
}

function GroupBinary_Make($gcount,$egroupname,&$gnumb) {
   // If this is zero or one groups, don't actually do anything
   if($gcount<2) return;
   
   // Make group name and effective hash
   global $parm;
   if($gcount > 2) {
      $gnumb++;
      $gsuffix=str_pad($gnumb,5,'0',STR_PAD_LEFT);
   }
   else {
      $gsuffix=substr($egroupname,strlen($parm['APP'])+1);
   }
   $gname = $parm['APP']."_eff_".$gsuffix;
   $md5_eff= md5($egroupname);
   
   // Insert the row
   $this->SQL(
      "INSERT INTO zdd.groups_c (group_id,md5_eff,grouplist) 
       VALUES ('$gname','$md5_eff','$egroupname')"
   );
   
}

function SpecFlatten_Security() {
   // Generate the list of "effective" groups.  These are all of
   // the possible combination of existing groups.  We add them
   // to the table now.
   global $parm;
   $app=$parm['APP'];
   $dbres=$this->SQLRead(
      "Select * FROM zdd.groups_c 
        WHERE group_id <> '$app'
        ORDER BY group_id"
   );
   while($row=pg_fetch_array($dbres)) {
      $groups[$row['group_id']]=$row;
   }
   // Now they are retrieved, build a new list of groups.  Note we hardcode
   // in that the $login group is always there
   $gkeys=array_keys($groups);
   $gnumb=0;
   $this->GroupBinary(1,$app,$gkeys,$gnumb);
   
   
   
	// First is to assign the group's default permissions.  They
	// are actually at the highest scope.
	$this->LogEntry("SECURITY/TABLES: Default table permissions by group-module ");
	$this->SQL("
INSERT INTO zdd.perm_tabs_c  
  ( group_id,module,table_id,istable
   ,permins,permupd,permdel,permsel
   ,permspec,nomenu
   )  
SELECT g.group_id,t.module,t.table_id,'T', 
   CASE WHEN g.permins='Y'  THEN 'Y' ELSE 'N' END, 
   CASE WHEN g.permupd='Y'  THEN 'Y' ELSE 'N' END, 
   CASE WHEN g.permdel='Y'  THEN 'Y' ELSE 'N' END, 
   CASE WHEN g.permsel='Y'  THEN 'Y' ELSE 'N' END,
   'N',
   CASE WHEN t.nomenu ='Y'  THEN 'Y' ELSE 'N' END
 FROM zdd.groups_c g,
      (SELECT t1.module,t1.table_id,COALESCE(m.nomenu,'N') as nomenu
         FROM zdd.tables_c  t1
         JOIN zdd.modules_c m  ON t1.module=m.module
      ) t
WHERE g.grouplist='' OR g.grouplist IS NULL"
      );
      
	$this->LogEntry("SECURITY/MENUS: Blank permissions by group");
	$this->SQL("
INSERT INTO zdd.perm_tabs_c  
  ( group_id,module,table_id,istable
   ,permins,permupd,permdel,permsel,permspec
   ,nomenu
  )  
SELECT g.group_id,t.module,t.menu_page,'M', 
   CASE WHEN g.permins='Y'  THEN 'Y' ELSE 'N' END, 
   CASE WHEN g.permupd='Y'  THEN 'Y' ELSE 'N' END, 
   CASE WHEN g.permdel='Y'  THEN 'Y' ELSE 'N' END, 
   CASE WHEN g.permsel='Y'  THEN 'Y' ELSE 'N' END,
   'N',
   CASE WHEN t.nomenu ='Y'  THEN 'Y' ELSE 'N' END
 FROM zdd.groups_c g,
      (SELECT t1.module,t1.menu_page,COALESCE(m.nomenu,'N') as nomenu
         FROM zdd.uimenu_c  t1
         JOIN zdd.modules_c m  ON t1.module=m.module
      ) t
WHERE g.grouplist='' OR g.grouplist IS NULL"
      );

  	$this->LogEntry("SECURITY/TABLES-MENUS: Applying group-module permissions");
	$this->SQL(
"UPDATE zdd.perm_tabs_c  
  SET permins  = CASE WHEN p.permins  =''    THEN zdd.perm_tabs_c.permins
                      WHEN p.permins  = 'Y'  THEN  'Y' ELSE 'N' END  
      ,permupd = CASE WHEN p.permupd  =''    THEN zdd.perm_tabs_c.permupd
                      WHEN p.permupd  = 'Y'  THEN  'Y' ELSE 'N' END 
      ,permsel = CASE WHEN p.permsel  =''    THEN zdd.perm_tabs_c.permupd
                      WHEN p.permsel  = 'Y'  THEN  'Y' ELSE 'N' END 
      ,permdel = CASE WHEN p.permdel  =''    THEN zdd.perm_tabs_c.permdel
                      WHEN p.permdel  = 'Y'  THEN  'Y' ELSE 'N' END
      ,nomenu  = CASE WHEN p.nomenu   =''    THEN zdd.perm_tabs_c.nomenu
                      WHEN p.nomenu   = 'Y'  THEN  'Y' ELSE 'N' END 
 FROM zdd.permxmodules_c p 
 WHERE p.group_id = zdd.perm_tabs_c.group_id 
   AND p.module   = zdd.perm_tabs_c.module  ");

   
   $this->LogEntry("SECURITY/TABLES: Applying group-table permissions");
	$this->SQL(
		"UPDATE zdd.perm_tabs_c ". 
"  SET permins  = CASE WHEN p.permins  =''    THEN zdd.perm_tabs_c.permins
                      WHEN p.permins  = 'Y'  THEN  'Y' ELSE 'N' END  
      ,permupd = CASE WHEN p.permupd  =''    THEN zdd.perm_tabs_c.permupd
                      WHEN p.permupd  = 'Y'  THEN  'Y' ELSE 'N' END 
      ,permsel = CASE WHEN p.permsel  =''    THEN zdd.perm_tabs_c.permupd
                      WHEN p.permsel  = 'Y'  THEN  'Y' ELSE 'N' END 
      ,permdel = CASE WHEN p.permdel  =''    THEN zdd.perm_tabs_c.permdel
                      WHEN p.permdel  = 'Y'  THEN  'Y' ELSE 'N' END 
      ,nomenu  = CASE WHEN p.nomenu   =''    THEN zdd.perm_tabs_c.nomenu
                      WHEN p.nomenu   = 'Y'  THEN  'Y' ELSE 'N' END ". 
		"  FROM zdd.permxtables_c p ".
		" WHERE p.table_id = zdd.perm_tabs_c.table_id ".
  		"   AND p.group_id = zdd.perm_tabs_c.group_id ");
   $this->LogEntry("SECURITY/MENUS: Applying menu-level permissions");
	$this->SQL(
		"UPDATE zdd.perm_tabs_c ". 
"  SET permins  = CASE WHEN p.permins  =''    THEN zdd.perm_tabs_c.permins
                      WHEN p.permins  = 'Y'  THEN  'Y' ELSE 'N' END  
      ,permupd = CASE WHEN p.permupd  =''    THEN zdd.perm_tabs_c.permupd
                      WHEN p.permupd  = 'Y'  THEN  'Y' ELSE 'N' END 
      ,permsel = CASE WHEN p.permsel  =''    THEN zdd.perm_tabs_c.permupd
                      WHEN p.permsel  = 'Y'  THEN  'Y' ELSE 'N' END 
      ,permdel = CASE WHEN p.permdel  =''    THEN zdd.perm_tabs_c.permdel
                      WHEN p.permdel  = 'Y'  THEN  'Y' ELSE 'N' END 
      ,nomenu  = CASE WHEN p.nomenu   =''    THEN zdd.perm_tabs_c.nomenu
                      WHEN p.nomenu   = 'Y'  THEN  'Y' ELSE 'N' END ". 
		"  FROM zdd.uimenugroups_c p ".
		" WHERE p.menu_page = zdd.perm_tabs_c.table_id ".
  		"   AND p.group_id  = zdd.perm_tabs_c.group_id ");
      
      
   // Pull foreign-key column permissions out of fk defs and
   //   and copy them over to column permissions.  Notice we don't
   //   pull the 'permrow' flag, that is handled differently
   $this->LogEntry("SECURITY/TABLES: Copying FK col perms to table");
   $sq="
insert into zdd.perm_cols_c 
   (table_id,group_id,column_id,permupd,permsel,permrow)
select fkg.table_id
      ,fkg.group_id
      ,TRIM(fkg.prefix) || trim(flt.column_id) || TRIM(fkg.suffix)
      ,fkg.permupd
      ,fkg.permsel
      ,'N'
  FROM zdd.tabfkygroups_c fkg
  JOIN zdd.tabflat_c      flt ON fkg.table_id_par = flt.table_id
 WHERE flt.primary_key = 'Y'"; 
   $this->SQL($sq);
      
      
   $this->LogEntry("SECURITY/TABLES: Flagging tables w/special permissions");
	$this->SQL("
update zdd.tables_c set permspec='Y'
  FROM zdd.perm_cols_c x
 WHERE zdd.tables_c.table_id = x.table_id"
   );
	$this->SQL("
update zdd.perm_tabs_c set permspec='Y'
  FROM zdd.perm_cols_c x
 WHERE zdd.perm_tabs_c.table_id = x.table_id"
   );

   
   // Fetch list of effective groups, define derived upd/del
   // priveleges for all
   $res=$this->SQLREad("Select group_id,grouplist FROM zdd.groups_c WHERE grouplist<>''");
   $groups=pg_fetch_all($res);
   foreach($groups as $group) {
      $grp=$group['group_id'];
      $grouplist="('".str_replace('+',"','",$group['grouplist'])."')";
      //$this->LogEntry(" ->Resolving table level permissions: ");
      $this->LogEntry(" - Group $grp effectively $grouplist");
      //$this->LogEntry(" --> Resolving table level permissions...");
      
      $sql="
INSERT INTO zdd.perm_tabs_c 
   ( permins,permupd,permdel,permsel,nomenu
    ,permspec,istable,module,table_id,group_id)                  
select CASE WHEN xpi=1 THEN 'Y' ELSE 'N' END as permins
      ,CASE WHEN xpu=1 THEN 'Y' ELSE 'N' END as permupd
      ,CASE WHEN xpd=1 THEN 'Y' ELSE 'N' END as permdel
      ,CASE WHEN xps=1 THEN 'Y' ELSE 'N' END as permsel
      ,CASE WHEN xnm=1 THEN 'Y' ELSE 'N' END as nomenu
      ,'N' as permspec
      ,'V' as istable
      ,module,table_id,'$grp' as group_id
  FROM (SELECT MAX(CASE WHEN permins='Y'  THEN 1 ELSE 0 END) as xpi
              ,MAX(CASE WHEN permupd='Y'  THEN 1 ELSE 0 END) as xpu
              ,MAX(CASE WHEN permdel='Y'  THEN 1 ELSE 0 END) as xpd
              ,MAX(CASE WHEN permsel='Y'  THEN 1 ELSE 0 END) as xps
              ,MAX(CASE WHEN nomenu='Y' THEN 1 ELSE 0 END) as xnm
	           ,module,table_id
         FROM zdd.perm_tabs_c
        WHERE group_id in $grouplist
          AND permspec='Y'
        GROUP BY module,table_id
       ) x ";
      //$this->LogEntry($sql);
      $this->SQL($sql);
      
      //$this->LogEntry(" --> Resolving column level permissions...");
      $sql="
insert into zdd.perm_colsx_c 
   (group_id,table_id,column_id,permsel,permupd)
select '$grp'
       ,t.table_id
       ,c.column_id
       ,MAX(CASE WHEN COALESCE(pc.permsel,'X') NOT IN (' ','X') 
             THEN (CASE WHEN pc.permsel='Y' THEN 1 ELSE 0 END)
             ELSE (CASE WHEN pt.permsel='Y' THEN 1 ELSE 0 END) END) as permsel
       ,MAX(CASE WHEN COALESCE(pc.permupd,'X') NOT IN (' ','X') 
             THEN (CASE WHEN pc.permupd='Y' THEN 1 ELSE 0 END)
             ELSE (CASE WHEN pt.permupd='Y' THEN 1 ELSE 0 END) END) as permupd
  FROM zdd.tables_c    t
  JOIN zdd.tabflat_c   c  ON t.table_id = c.table_id
  JOIN zdd.perm_tabs_c pt ON t.table_id = pt.table_id
  LEFT
  JOIN zdd.perm_cols_c pc ON pc.table_id = c.table_id
                         AND pc.column_id= c.column_id
                         AND pc.group_id = pt.group_id
 WHERE t.permspec='Y'
   AND pt.group_id IN $grouplist
 group by t.table_id,c.column_id
 order by t.table_id,c.column_id";
   //$this->LogEntry($sql);
      $this->SQL($sql);
   }
   
      

	return true;
}
// ==========================================================
// REGION: Spec pull to local memory
// ==========================================================
function SpecLocal()
{
	// Here we take the table definitions and generate useful
	// comma-separated lists for later use by computer 
	// languages.  The first customer will be the next routine,
	// which builds index definitions containing comma-separated
	// lists of values in the SQL DDL.
   $retval=true;
	$this->LogStage("Pulling complete data dictionary to local memory");
	
	$this->LogEntry("Retrieving Flattened Definitions");$this->SpecHandle_Lists_Flat();
	
   $this->LogEntry("Making primary key lists");
   $retval = $retval && $this->SpecHandle_Lists_PK();
	
   $this->LogEntry("Making required column lists");$this->SpecHandle_Lists_Req();
	
   $this->LogEntry("Making foreign key lists");
   $retval = $retval && $this->SpecHandle_Lists_FK();
	$this->LogEntry("Retrieving Index Definitions");$this->SpecHandle_Lists_Indexes();
	$this->LogEntry("Retrieving list of projections");$this->SpecHandle_Lists_Projections();
	return $retval;
}


// TODO:  The routines below that pull PK and FK would
//        go faster if they  pulled their info from here.
function SpecHandle_Lists_Flat() {
	global $utabs;

	$tables = array_keys($utabs);
	foreach ($tables as $table) {
		$utabs[$table]["flat"]=array();
		
		// This is the flat stuff
      $sql="Select * From zdd.tabflat_c where table_id = '$table'";
		$results = $this->SQLRead($sql);
      /*
      $this->LogEntry("Results is: $results");
      ini_set("error_reporting",E_ALL);
      $rows=pg_fetch_all($results);
      echo pg_last_error($GLOBALS['dbconn2']);
      $this->LogEntry("Survived the pg_fetch_all");
      //hprint_r($rows);
      
      x_EchoFlusH("Count of rows ".count($rows));
      foreach($rows as $row) {
         $utabs[$table]["flat"][$row["column_id"]]=$row;
      }
      unset($results);
      */
      
      
		$ilimit = pg_num_rows($results);
		$i=0;
		if ($ilimit>0) {
			while ($row = pg_fetch_array($results,$i,PGSQL_ASSOC)) {
				$utabs[$table]["flat"][$row["column_id"]]=$row;
				$i++;
				if ($i==$ilimit) { break; }
			}
		}
	}
}

function SpecHandle_Lists_PK()
{	
	global $utabs;
	foreach ($utabs as $utab) {
		$results=$this->SQLRead(			
			"select column_id from zdd.tabflat_c ".
			" where table_id = '". $utab["table_id"] . "'".
			"   and primary_key = 'Y'");
		$pkarr = $this->SQL_fetch_all_1col($results);
		$pklist = implode(",",$pkarr);
		
		$utabs[$utab["table_id"]]["pk"] = $pklist; 
	}
   return true;
}

function SpecHandle_Lists_Req()
{	
	global $utabs;
	foreach ($utabs as $utab) {
		$results=$this->SQLRead(			
			"select column_id from zdd.tabflat_c ".
			" where table_id = '". $utab["table_id"] . "'".
			"   and required = 'Y'");
		$reqarr = $this->SQL_fetch_all_1col($results);
		$reqlist = implode(",",$reqarr);
		
		$utabs[$utab["table_id"]]["required"] = $reqlist; 
	}
}

function SpecHandle_Lists_FK()
{
	global $ufks,$utabs;
   $retval=true;
	$rc=0;
	$results = $this->SQLRead("Select * FROM zdd.tabfky_c");
	
	while ($row = pg_fetch_array($results)) {
		$suffix = trim($row["suffix"]);
      $prefix = trim($row["prefix"]);
		// Get details on pk/fk
		$fk = $pk = $both = $match = "";
      $cols_list = $utabs[$row["table_id_par"]]["pk"];
      if($cols_list=='') {
         $this->LogEntry("ERROR:");
         $this->LogEntry("ERROR: no primary key on  ".$row['table_id_par']);
         $retval=false;
         continue;
      }
		$cols = explode(",",$cols_list);
		foreach ($cols as $col) {
         // "Range_from" columns are skipped
         //echo "<pre>";
         //print_r($utabs[$row['table_id_par']]);
         //echo "</pre>";
         //exit;
         if($utabs[$row['table_id_par']]['flat'][$col]['range_from']<>'') {
            $this->LogEntry(
               " -> FK ".$row['table_id']
               ." to ".$row['table_id_par']
               ." skipping RANGE-FROM ".$col
            );
            continue;
         }
			if ($fk!="")    $fk.=",";
			if ($pk<>"")    $pk.=",";
			if ($both<>"")  $both.=",";
         if ($match<>"") $match.=" AND ";
			$pk.=$col; 
			$fk.=$prefix.$col.$suffix; 
			$both.=$prefix.$col.$suffix.":".$col;
         if($utabs[$row['table_id_par']]['flat'][$col]['range_to']<>'') {
            $match
               .= "COALESCE(chd.".$prefix.$col.$suffix.",##1/1/1000##::date) BETWEEN "
               ."COALESCE(par.".$col.",##1/1/1000##::date) AND COALESCE(par."
               .$utabs[$row['table_id_par']]['flat'][$col]['range_to']
               .",##1/1/9999##::date)";
            $this->LogEntry($match);
         }
         else {
            $match .= "chd.".$prefix.$col.$suffix." = par.".$col;
         }
         
		}
		$combo = 
			trim($row["table_id"])."_".
			trim($row["table_id_par"])."_".
			$suffix;

		$ufks[$combo] = array(
			"combo"=>$combo,
			"table_id_chd"=>trim($row["table_id"]),
			"table_id_par"=>trim($row["table_id_par"]),
			"suffix"=>trim($row["suffix"]),
			"auto_insert"=>$row["auto_insert"],
			"copysamecols"=>$row["copysamecols"],
			"nocolumns"=>$row["nocolumns"],
			"allow_empty"=>$row["allow_empty"],
			"allow_orphans"=>$row["allow_orphans"],
			"delete_cascade"=>$row["delete_cascade"],
			"prevent_fk_change"=>$row["prevent_fk_change"],
         "uidisplay"=>$row['uidisplay'],
			"cols_chd"=>$fk,
			"cols_par"=>$pk,
			"cols_both"=>$both,
         "cols_match"=>$match);
		$rc++;
	}
   return $retval;
}


function SpecHandle_Lists_Indexes() {
	global $utabs;

	$respre=$this->SQLRead("
select idx_name,table_id,idx_unique
  from zdd.tabidx_c"); 
	$results=pg_fetch_all($respre);
	
	if (pg_num_rows($respre)>0) {
		foreach ($results as $index) {
			$idx_name = $index["idx_name"];
			$table_id = $index["table_id"];
			
			$cols="";
			$respre = $this->SQLRead("
Select column_id 
  FROM zdd.tabidxcol_c 
 where idx_name = '$idx_name'
   and table_id = '$table_id'
 order by uicolseq");
			while ($col = pg_fetch_array($respre)) {
				$cols.=$this->AddComma($cols).$col["column_id"];
			}
			
			$utabs[$table_id]["indexes"][$idx_name] = array("unique"=>$index["idx_unique"],"cols"=>$cols);
		}
	}
}

function SpecHandle_Lists_Projections() {
	global $utabs;

	$respre = $this->SQLRead("Select table_id,projection FROM zdd.tabprojcols_c GROUP BY table_id,projection");
	$restables = pg_fetch_all($respre);
	foreach ($restables as $restable) {
		$table_id = trim($restable["table_id"]);
		$projection=trim($restable["projection"]);
		if (!isset($utabs[$table_id]["projections"])) { $utabs[$table_id]["projections"] = array(); }
		
		$cols = "";
		$results = $this->SQLRead(
			"Select p.column_id ". 
			"  FROM zdd.tabprojcols_c p ".
			"  JOIN zdd.tabflat_c f ON p.table_id = f.table_id AND p.column_id = f.column_id".
			" WHERE p.table_id = '$table_id' AND p.projection = '$projection'".
			" ORDER BY f.uicolseq ");
		//$results = $this->SQLRead("Select column_id FROM zdd.tabprojcols_c WHERE table_id = '$table_id' AND projection = '$projection'");
		while ($row = pg_fetch_array($results)) {
			$cols.=$this->AddComma($cols).$row[0];
		}
		$utabs[$table_id]["projections"][$projection] = $cols;
		//$this->LogEntry("   $table_id projects $projection as $cols");
	}
}

/* -----------------------------------------------------------------*\
 * SpecDDL()
 *
 *  this is the DDL for all non-storage objects, like views and
 *  triggers and indexes.  DDL for tables and columns is generated
 *  only where needed, after we have examined the database.
\* -----------------------------------------------------------------*/
function SpecDDL()
{
   $retval=true;
	// This stuff generates the DDL for NSO's based on the
	// information generated in the steps above.
	$this->LogStage("Generating DDL for non-storage objects");
	$this->SpecDDL_Indexes();
	$this->SpecDDL_Sequences();
	
	$this->LogStage("Generating DDL for triggers");
	$retval = $retval && $this->SpecDDL_Triggers();
	return $retval;
}

function SpecDDL_Indexes()
{
	$this->SpecDDL_Indexes_Normal();
	$this->SpecDDL_Indexes_keys();
}

function SpecDDL_Indexes_Normal() {
	$this->LogEntry("Generating normal (non-key) index definitions");
	global $utabs,$ufks;
	foreach ($utabs as $utab) {
		
		foreach ($utab["indexes"] as $idx_name=>$index) {
         $idx_name=$utab['table_id'].'_'.$idx_name;
			$sql = "CREATE INDEX ".trim($idx_name) .
				" ON ".$utab["table_id"]." (".$index["cols"].")";
			$def_short = "idx:".strtolower($utab["table_id"]).":".strtolower($index["cols"]);
			$this->SQL(
				"Insert into zdd.ns_objects_c ".
				"(object_id,definition,def_short,sql_create,sql_drop)".
				" values ".
				"('$idx_name','$def_short','$def_short','$sql','')");
			
		}
	}
}


function SpecDDL_Indexes_keys() {
	global $utabs,$ufks;
	$this->LogEntry("Generating primary/foreign key index definitions");
	// Use our associative arrays to create indexes on 
	// primary and foreign keys
	foreach ($utabs as $utab) {
		if(true) {
			//$this->LogEntry("PK for table ".$utab["table_id"]." on (".$utab["pk"].")");
			$index = $utab["table_id"]."_PK";
			$sql = "CREATE INDEX ".$index .
				" ON ".$utab["table_id"]." (".$utab["pk"].")";
			$def_short = "idx:".strtolower($utab["table_id"]).":".strtolower($utab["pk"]);
			$this->SQL(
				"Insert into zdd.ns_objects_c ".
				"(object_id,definition,def_short,sql_create,sql_drop)".
				" values ".
				"('$index','$def_short','$def_short','$sql','')");
			
			// If the index is composite, also add indexes on each column
			if (false!==strpos($utab["pk"],",")) {
				$keys = explode(",",$utab["pk"]);
				foreach ($keys as $key) {
					$index = $utab["table_id"]."_PK_".$key;
					$sql = "CREATE INDEX $index on ".$utab["table_id"]." ($key)";
					$def_short = "idx:".strtolower($utab["table_id"]).":".strtolower($key);
					$this->SQL(
						"Insert into zdd.ns_objects_c ".
						"(object_id,definition,def_short,sql_create,sql_drop)".
						" values ".
						"('$index','$def_short','$def_short','$sql','')");
				}
			}
		}
	}
	
	foreach ($ufks as $ufk) {
		$index = $ufk["table_id_chd"]."_".$ufk["table_id_par"].$ufk["suffix"];
		$sql = "CREATE INDEX ".$index .
			" ON ".$ufk["table_id_chd"]." (".$ufk["cols_chd"].")";
		$def_short = "idx:".strtolower($ufk["table_id_chd"]).":".strtolower($ufk["cols_chd"]);
		$this->SQL(
			"Insert into zdd.ns_objects_c ".
			"(object_id,definition,def_short,sql_create,sql_drop)".
			" values ".
			"('$index','$def_short','$def_short','$sql','')");
	}
}

function SpecDDL_Sequences()
{
	$this->LogEntry("Building sequence DDL (S)");
	$this->SQL(
"Insert into zdd.ns_objects_c 
 (object_id,definition,def_short,sql_create)
 SELECT DISTINCT
        'sequence_' ||  
        LOWER(CASE WHEN auto_formula = '' THEN TRIM(table_id)
		       ELSE TRIM(auto_formula) END || '_' || TRIM(column_id)),  
        'sequence:' ||  
        LOWER(CASE WHEN auto_formula = '' THEN TRIM(table_id)
		       ELSE TRIM(auto_formula) END || '_' || TRIM(column_id)),
        'sequence:' ||  
        LOWER(CASE WHEN auto_formula = '' THEN TRIM(table_id)
		       ELSE TRIM(auto_formula) END || '_' || TRIM(column_id)),
        'CREATE SEQUENCE ' ||  
        CASE WHEN auto_formula = '' THEN TRIM(table_id)
		       ELSE TRIM(auto_formula) END || '_' || TRIM(column_id)
   FROM zdd.tabflat_c
  WHERE automation_id IN ('SEQUENCE','SEQDEFAULT')");	
}
	
/* -----------------------------------------------------------------*\
 * -----------------------------------------------------------------* 
 * Triggers, the big cahuna
 * -----------------------------------------------------------------* 
\* -----------------------------------------------------------------*/
function SpecDDL_Triggers() {
	$retval=true;
	global $parm;
   // KFD 10/5/06, Moved users et al into apps, call unconditionally now,
   //              not just for node manager.  Call a different routine
   //              now for Andromeda Node Manager
	//if ($parm["APP"]=="andro") { $this->SpecDDL_Triggers_Andro(); }
   $this->SpecDDL_Triggers_Security();
	if ($parm["APP"]=="andro") { $this->SpecDDL_Triggers_SecurityAndro(); }

	// Fragments that do not require sequencing
	$this->SpecDDL_Triggers_Defaults();
	$retval = $retval && $this->SpecDDL_Triggers_PK();
   $this->SpecDDL_Triggers_Req();
   $retval = $retval && $this->SpecDDL_Triggers_FK();
   $retval = $retval && $this->SpecDDL_Triggers_Automated();
   $this->SpecDDL_Triggers_ColConsTypes();
   $this->SpecDDL_Triggers_Chains();
   $this->SpecDDL_Triggers_Cascades();

   // List of triggers with fragment count > 0
   $triggers=array();
   $sq="SELECT DISTINCT table_id,action,before_after,statement "
      ." FROM zdd.triggers ";
   $results = $this->SQLRead($sq);
   while ($row = pg_fetch_array($results)) {
      $f =trim($row["table_id"])
         ."_".substr($row["action"],0,3)
         ."_".substr($row["before_after"],0,3)
         .'_'.($row['statement']=='Y' ? 's' : 'r');
      $triggers[] = array(
         "table_id"=>trim($row["table_id"]),
         "action"=>trim($row["action"]),
         "before_after"=>trim($row["before_after"]),
         "statement"=>trim($row['statement']),
         "trigger_id"=>$f,
         "fname"=>$f."_f",
         "tname"=>$f."_t");
   }
      
   // You might expect a trap to ensure trigCount > 0, but we know
   // that the DD tables themselves have keys, so we boldly assume
   // there will always be triggers.
   //
   // Part 3, create start/stop code for each trigger
   $this->SpecDDL_Triggers_Pass2($triggers);
   
   // Part 4, the very end, assemble
   $this->SpecDDL_Triggers_Assemble($triggers);
   
   return $retval;
}

function SpecDDL_Triggers_Security() {
	// SEE-ALSO: SecurityNodeManager, that routine synchronizes our user/group list
	//           with the servers lists.  These trigger fragments then keep it
	//           synchronized going forward.
   global $parm;
   $app=$parm['APP'];
	
   $this->LogStage("Putting Special triggers onto security tables");

	// Add a user to system when added to our tables
	$this->LogEntry("Putting triggers onto users table");
	$sql = "
    -- 1000 Add user to system
    IF new.member_password=#### THEN
       new.member_password=##temp##;
    END IF;
    
	 SELECT INTO AnyInt COUNT(*) FROM pg_shadow WHERE usename = CAST(new.user_id as name);
	 IF AnyInt = 0 THEN
        EXECUTE ##CREATE USER ## || new.user_id || ## PASSWORD ## || quote_literal(new.member_password);
    ELSE 
        ErrorCount = ErrorCount + 1; 
        ErrorList = ErrorList || ##user_id,8001,That User ID already in use on this server;##;
    END IF;\n";
	$this->SpecDDL_TriggerFragment("users","INSERT","AFTER","1000",$sql);
   // Update can change password
	$sql = "
    -- 1000 Update password
    IF new.member_password <> old.member_password THEN
        EXECUTE ##ALTER USER ## ||  new.user_id || ## PASSWORD ## || quote_literal(new.member_password);
    END IF;\n";
	$this->SpecDDL_TriggerFragment("users","UPDATE","AFTER","1000",$sql);
	$sql = "
    -- 1000 Calculate new effective group 
    IF new.flag_newgroup=##Y## THEN
        -- Work out list of groups
        AnyChar = ##$app##;
        AnyInt  = 1;
        FOR AnyRow IN SELECT x.group_id
                        FROM usersxgroups x JOIN groups g 
                          ON x.group_id = g.group_id
                       WHERE x.user_id=old.user_id 
                         AND (g.grouplist=#### OR g.grouplist IS NULL)
                         AND g.group_id <> ##$app##
                       ORDER BY x.group_id LOOP 
            AnyChar = AnyChar || ##+## || TRIM(AnyRow.group_id);
            AnyInt  = AnyInt + 1;
        END LOOP;
        --ErrorCount = ErrorCount + 1;
        --ErrorList = ErrorList || AnyChar || ##;##;
        -- Remove user from previous effective group
        IF old.group_id_eff IS NOT NULL  AND old.group_id_eff <> #### THEN
           EXECUTE ##ALTER GROUP ## || old.group_id_eff || ## DROP USER ## || old.user_id;
           new.group_id_eff=####;
        END IF;
        -- Put user into new effective group
        IF AnyInt > 1 THEN
            SELECT INTO new.group_id_eff group_id 
              FROM zdd.groups_c
             WHERE grouplist=AnyChar;
            EXECUTE ##ALTER GROUP ## || new.group_id_eff || ## ADD USER ## || old.user_id;
        END IF;
        -- turn switch off
        new.flag_newgroup=##N##;
    END IF;\n";
	$this->SpecDDL_TriggerFragment("users","UPDATE","BEFORE","7000",$sql);
	// Delete a user from system when deleted from our tables
	$sql = "\n".
		"    -- 1000 Delete user from system \n".
		"    SELECT INTO AnyInt COUNT(*) FROM pg_shadow WHERE usename = CAST(old.user_id as name);\n".
		"    IF AnyInt > 0 THEN\n".
		"        EXECUTE ##DROP USER ## || old.user_id ;\n".
		"    END IF;\n";
	$this->SpecDDL_TriggerFragment("users","DELETE","AFTER","1000",$sql);
   
   /*  This is not necessary actually because groups must be 
       created during the build process, so that permissions
       can be granted.

	// Add a group to system when added to our tables
	$this->LogEntry("Putting triggers onto groups table");
	$sql = "
    -- 1000 Add group to system, no error if already present 
	 SELECT INTO AnyInt COUNT(*) FROM pg_group WHERE groname = CAST(new.group_id as name);
	 IF AnyInt = 0 THEN
        EXECUTE ##CREATE GROUP ## || new.group_id ;
    END IF;\n";
	$this->SpecDDL_TriggerFragment("groups","INSERT","AFTER","1000",$sql);
	// Delete a group from system when deleted from our tables
	$sql = "
    -- 1000 Delete user from system 
    SELECT INTO AnyInt COUNT(*) FROM pg_group WHERE groname = CAST(old.group_id as name);
    IF AnyInt > 0 THEN
        EXECUTE ##DROP GROUP ## || old.user_id ;
    END IF;";
	$this->SpecDDL_TriggerFragment("groups","DELETE","AFTER","1000",$sql);
   */
	
	// TODO:  Put user in group
	$sqlins = "
    -- 1000 Add user/group to system
    SELECT INTO AnyInt count(*)
      FROM pg_auth_members m
      JOIN pg_roles g ON m.roleid = g.oid
      JOIN pg_roles u ON m.member = u.oid
     WHERE u.rolname = CAST(new.user_id as name)
       AND g.rolname = CAST(new.group_id as name);
	 IF AnyInt = 0 THEN
	     EXECUTE ##ALTER GROUP ## || new.group_id || ## ADD USER ## || new.user_id;
        UPDATE users SET flag_newgroup=##Y## WHERE user_id=new.user_id;
    END IF;\n";
	$sqldel = "
    -- 1000 Add user/group to system
    SELECT INTO AnyInt count(*)
      FROM pg_auth_members m
      JOIN pg_roles g ON m.roleid = g.oid
      JOIN pg_roles u ON m.member = u.oid
     WHERE u.rolname = CAST(old.user_id as name)
       AND g.rolname = CAST(old.group_id as name);
	 IF AnyInt > 0 THEN
        EXECUTE ##ALTER GROUP ## || old.group_id || ## DROP USER ## || old.user_id;
        UPDATE users SET flag_newgroup=##Y## WHERE user_id=old.user_id;
    END IF;\n";
	$this->SpecDDL_TriggerFragment("usersxgroups","INSERT","AFTER","1000",$sqlins);
	$this->SpecDDL_TriggerFragment("usersxgroups","UPDATE","AFTER","1000",$sqldel);
	$this->SpecDDL_TriggerFragment("usersxgroups","UPDATE","AFTER","1010",$sqlins);
	$this->SpecDDL_TriggerFragment("usersxgroups","DELETE","AFTER","1000",$sqldel);		
}


function SpecDDL_Triggers_SecurityAndro() {
	// SEE-ALSO: SecurityNodeManager, that routine synchronizes our user/group list
	//           with the servers lists.  These trigger fragments then keep it
	//           synchronized going forward.  
	
   $this->LogStage("Special Triggers for Superusers table");
   $this->LogEntry("Adding trigger fragments for table usersroot");

	// Add a user to system when added to our tables
	$sql = "
    -- 1000 Add user to system 
    IF new.member_password=#### THEN
       new.member_password=##temp##;
    END IF;
    
	 SELECT INTO AnyInt COUNT(*) FROM pg_shadow WHERE usename = CAST(new.user_id as name);
	 IF AnyInt = 0 THEN
        EXECUTE ##CREATE USER ## || new.user_id || ## SUPERUSER PASSWORD ## || quote_literal(new.member_password);
        -- EXECUTE ##ALTER GROUP root ADD USER ## || new.user_id;
    ELSE 
        ErrorCount = ErrorCount + 1; 
        ErrorList = ErrorList || ##user_id,9001,That User ID already in use on this server;##;
    END IF;
    -- 1000 Add users to root group
    SELECT INTO AnyInt count(*)
      FROM pg_auth_members m
      JOIN pg_roles g ON m.roleid = g.oid
      JOIN pg_roles u ON m.member = u.oid
     WHERE u.rolname = CAST(new.user_id as name)
       AND g.rolname = CAST(##root## as name);
	 IF AnyInt = 0 THEN
	     EXECUTE ##ALTER GROUP root ADD USER ## || new.user_id;
        -- UPDATE users SET flag_newgroup=##Y## WHERE user_id=new.user_id;
    END IF;\n";
	$this->SpecDDL_TriggerFragment("usersroot","INSERT","AFTER","1000",$sql);

   // Update can change password
	$sql = "
    -- 1000 Add user to system
    IF new.member_password <> old.member_password THEN
        EXECUTE ##ALTER USER ## ||  new.user_id || ## PASSWORD ## || quote_literal(new.member_password);
    END IF;\n";
	$this->SpecDDL_TriggerFragment("usersroot","UPDATE","AFTER","1000",$sql);
   
   
	// Delete a user from system when deleted from our tables
	$sql = "\n".
		"    -- 1000 Delete user from system \n".
		"    SELECT INTO AnyInt COUNT(*) FROM pg_shadow WHERE usename = CAST(old.user_id as name);\n".
		"    IF AnyInt > 0 THEN\n".
		"        EXECUTE ##DROP USER ## || old.user_id ;\n".
		"    END IF;\n";
	$this->SpecDDL_TriggerFragment("usersroot","DELETE","AFTER","1000",$sql);
   
}

function SpecDDL_Triggers_Defaults() {
	$this->LogEntry("Building default clauses");
	$results = 	$this->SQLRead(
		"SELECT table_id,column_id,automation_id,formshort,auto_formula,type_id". 
		" FROM zdd.tabflat_c ". 
		" WHERE automation_id IN ('BLANK','DEFAULT','SEQUENCE','SEQDEFAULT','TS_INS','UID_INS','TS_UPD','UID_UPD')"
   ); 

	while ($row=pg_fetch_array($results)) {
		$s1="";
		$table_id = $row["table_id"];
		$column_id = trim($row["column_id"]);
		$automation_id = trim(strtoupper($row["automation_id"]));
		$formshort = $row["formshort"];
		
		if ($automation_id=="SEQUENCE") {
			$s1 = "\n".
				"    -- 1010 sequence validation\n".
				"    IF NOT new.". $column_id . " IS NULL THEN \n".
				"        ErrorCount = ErrorCount + 1;\n". 
				"        ErrorList = ErrorList || ##$column_id,3001,". 
								"$column_id may not be explicitly assigned;##;\n".
				"    END IF;\n";
			$this->SpecDDL_TriggerFragment($table_id,"INSERT","BEFORE","1010",$s1);
			$s1 = "\n".
				"    -- 1010 sequence validation\n".
				"    IF (new.". $column_id . " <> old.". $column_id . ")  THEN \n".
				"        ErrorCount = ErrorCount + 1;\n". 
				"        ErrorList = ErrorList || ##$column_id,3002,". 
								" may not be re-assigned;##;\n".
				"    END IF;\n";
			$this->SpecDDL_TriggerFragment($table_id,"UPDATE","BEFORE","1010",$s1);
					
			// HARDCODE SKEY Behavior.  Currently the ONLY 
			// notify in the system is this liddle guy here
			$nlist = "";
			if ($column_id=="skey") {
				$nlist = 
				   "        IF COALESCE(new.skey_quiet,##N##) <> ##Y## THEN \n".
					"            NotifyList = NotifyList || ##SKEY ($table_id) ## || CAST(new.skey as varchar(10)) || ##;##;\n".
					"        END IF;\n";
			}
			$Seq = $this->DBB_SequenceName($table_id,$row["auto_formula"],$column_id);
			$s1 = 
				"    -- 1011 sequence/default assignment\n".
				"    IF new.". $column_id . " IS NULL THEN \n".
				"        new.". $column_id . " = nextval(##". $Seq . "##);\n".
				$nlist.
				"    END IF;\n";
			$this->SpecDDL_TriggerFragment($table_id,"INSERT","BEFORE","1011",$s1);
		}

		if ($automation_id=="SEQDEFAULT") {   
			$s1 = "\n".
				"    -- 1010 sequence validation\n".
				"    IF (new.". $column_id . " <> old.". $column_id . ")  THEN \n".
				"        ErrorCount = ErrorCount + 1;\n". 
				"        ErrorList = ErrorList || ##$column_id,3002,". 
								"$column_id may not be re-assigned;##;\n".
				"    END IF;\n";
			$this->SpecDDL_TriggerFragment($table_id,"UPDATE","BEFORE","1010",$s1);
					
			$Seq = $this->DBB_SequenceName($table_id,$row["auto_formula"],$column_id);
			$s1 = 
				"    -- 1011 sequence assignment\n".
				"    IF new.". $column_id . " IS NULL OR new.".$column_id." = 0 THEN \n".
				"        new.". $column_id . " = nextval(##". $Seq . "##);\n".
				"    END IF;\n";
			$this->SpecDDL_TriggerFragment($table_id,"INSERT","BEFORE","1011",$s1);


		}
		
		
		if ($automation_id=="TS_INS") {
			$s1 = "\n".
				"    -- 1010 timestamp validation\n".
				"    IF NOT new.". $column_id . " IS NULL THEN \n".
				"        ErrorCount = ErrorCount + 1;\n". 
				"        ErrorList = ErrorList || ##$column_id,3003," . 
								"$column_id(ts_ins) may not be explicitly assigned;##;\n".
				"    END IF;\n";
			$this->SpecDDL_TriggerFragment($table_id,"INSERT","BEFORE","1010",$s1);
			$s1 = "\n".
				"    -- 1010 timestamp validation\n".
				"    IF (new.". $column_id . " <> old.". $column_id . ") THEN \n".
				"        ErrorCount = ErrorCount + 1;\n". 
				"        ErrorList = ErrorList || ##$column_id,3004," . 
								"$column_id(ts_ins) may not be re-assigned;##;\n".
				"    END IF;\n";
			$this->SpecDDL_TriggerFragment($table_id,"UPDATE","BEFORE","1010",$s1);
			
			$s1 = 
				"    -- 1010 insert timestamp assignment\n".
				"    IF new.". $column_id . " IS NULL THEN new.". $column_id . " = now(); END IF;\n";
			$this->SpecDDL_TriggerFragment($table_id,"INSERT","BEFORE","1011",$s1);
		}
		if ($automation_id=="TS_UPD") {
			$s1 = "\n".
				"    -- 1010 timestamp validation\n".
				"    IF NOT new.". $column_id . " IS NULL THEN \n".
				"        ErrorCount = ErrorCount + 1;\n". 
				"        ErrorList = ErrorList || ##$column_id,3005," . 
								"$column_id (ts_upd) may not be explicitly assigned;##;\n".
				"    END IF;\n";
			$this->SpecDDL_TriggerFragment($table_id,"INSERT","BEFORE","1010",$s1);
			$s1 = "\n".
				"    -- 1010 timestamp validation\n".
				"    IF (new.". $column_id . " <> old.". $column_id . ") THEN \n".
				"        ErrorCount = ErrorCount + 1;\n". 
				"        ErrorList = ErrorList || ##$column_id,3004," . 
								"$column_id (ts_upd) may not be re-assigned;##;\n".
				"    END IF;\n";
			$this->SpecDDL_TriggerFragment($table_id,"UPDATE","BEFORE","1010",$s1);
			
			//$Seq = strtoupper($table_id) . "_". strtoupper($column_id);
			$s1 = 
				"    -- 1010 insert timestamp assignment\n".
				"    new.". $column_id . " = now();\n";
			$this->SpecDDL_TriggerFragment($table_id,"INSERT","BEFORE","1011",$s1);
			$this->SpecDDL_TriggerFragment($table_id,"UPDATE","BEFORE","1011",$s1);
		}
		if ($automation_id=="UID_INS") {
			$s1 = "\n".
				"    -- 1010 uid_ins validation\n".
				"    IF NOT new.". $column_id . " IS NULL THEN \n".
				"        ErrorCount = ErrorCount + 1;\n". 
				"        ErrorList = ErrorList || ##$column_id,3007," . 
								"$column_id (uid_ins) may not be explicitly assigned;##;\n".
				"    END IF;\n";
			$this->SpecDDL_TriggerFragment($table_id,"INSERT","BEFORE","1010",$s1);
			$s1 = "\n".
				"    -- 1010 uid_ins validation\n".
				"    IF (new.". $column_id . " <> old.". $column_id . ") THEN \n".
				"        ErrorCount = ErrorCount + 1;\n". 
				"        ErrorList = ErrorList || ##$column_id,3008," . 
								"$column_id (uid_ins) may not be re-assigned;##;\n".
				"    END IF;\n";
			$this->SpecDDL_TriggerFragment($table_id,"UPDATE","BEFORE","1010",$s1);
			
			//$Seq = strtoupper($table_id) . "_". strtoupper($column_id);
			$s1 = 
				"    -- 1010 insert user id assignment\n".
				"    IF new.". $column_id . " IS NULL THEN new.". $column_id . " = session_user; END IF;\n";
			$this->SpecDDL_TriggerFragment($table_id,"INSERT","BEFORE","1011",$s1);
		}
		if ($automation_id=="UID_UPD") {
			$s1 = "\n".
				"    -- 1010 User Update validation\n".
				"    IF NOT new.". $column_id . " IS NULL THEN \n".
				"        ErrorCount = ErrorCount + 1;\n". 
				"        ErrorList = ErrorList || ##$column_id,3009," . 
								"$column_id (uid_upd) may not be explicitly assigned;##;\n".
				"    END IF;\n";
			$this->SpecDDL_TriggerFragment($table_id,"INSERT","BEFORE","1010",$s1);
			$s1 = "\n".
				"    -- 1010 User Update validation\n".
				"    IF (new.". $column_id . " <> old.". $column_id . ") THEN \n".
				"        ErrorCount = ErrorCount + 1;\n". 
				"        ErrorList = ErrorList || ##$column_id,3010,". 
								"$column_id (uid_upd) may not be re-assigned;##;\n".
				"    END IF;\n";
			$this->SpecDDL_TriggerFragment($table_id,"UPDATE","BEFORE","1010",$s1);
			
			//$Seq = strtoupper($table_id) . "_". strtoupper($column_id);
			$s1 = 
				"    -- 1010 Update user id assignment\n".
				"    new.". $column_id . " = session_user; \n";
			$this->SpecDDL_TriggerFragment($table_id,"INSERT","BEFORE","1011",$s1);
			$this->SpecDDL_TriggerFragment($table_id,"UPDATE","BEFORE","1011",$s1);
		}
		
		if ($automation_id=="BLANK") {
			$def = $this->SQLFormatBLank($row["type_id"],true,true);
			$s1 = "\n".
				"    -- 1020 Blank default\n".
				"    IF new.". $column_id . " IS NULL THEN new.". $column_id . " = ". $def . "; END IF;\n";
			$this->SpecDDL_TriggerFragment($table_id,"INSERT","BEFORE","1020",$s1);
		}

		if ($automation_id=="DEFAULT") {
			$formula = trim($row["auto_formula"]);
			$def = $this->SQLFormatLiteral($formula,$row["type_id"],true,true);
         // We are ignoring "%" defaults
			if (substr($formula,0,1)<>'%') {
            $s1="\n"
               ."    -- 1030 Explicit default\n"
               ."    IF new.". $column_id . " IS NULL THEN new."
               . $column_id . " = ". $def . "; END IF;\n";
            $this->SpecDDL_TriggerFragment($table_id
               ,"INSERT","BEFORE","1030",$s1
            );
			}
		}
	}
}
	
function SpecDDL_Triggers_Req() {
	$this->LogEntry("Building required column clauses");
	global $utabs;
	foreach ($utabs as $table_id=>$utab) {
		foreach ($utab["flat"] as $colname=>$colinfo) {
			if ($colinfo["required"]=="Y") {
				// We are doing two checks.  We should be able to do them
				// together as an "OR", but Postgres does not like that.
				// Cause was left uninvestigated.
				$s1 = 
				"\n".
				"    -- 3001 REQUIRED Validation\n".
				"    IF new.".$colname." IS NULL THEN\n". 
				"       	ErrorCount = ErrorCount + 1;\n". 
				"       	ErrorList = ErrorList || ##$colname,4001,".
                        "May not be empty##;\n".
				"    END IF;\n";
				"    -- 3001 END\n";
				$this->SpecDDL_TriggerFragment($table_id,"INSERT","BEFORE","3001",$s1);
				$this->SpecDDL_TriggerFragment($table_id,"UPDATE","BEFORE","3001",$s1);

				// don't do this for dates
				if ($colinfo["type_id"]<>"date") {
					$s1 = 
					"\n".
					"    -- 3001 REQUIRED Validation\n".
					"    IF new.$colname=#### THEN\n". 
					"       	ErrorCount = ErrorCount + 1;\n". 
					"       	ErrorList = ErrorList || ##$colname,4001,".
              "May not be empty##;\n".
					"    END IF;\n";
					"    -- 3001 END\n";
					$this->SpecDDL_TriggerFragment($table_id,"INSERT","BEFORE","3001",$s1);
					$this->SpecDDL_TriggerFragment($table_id,"UPDATE","BEFORE","3001",$s1);
				}
			}
		}
	}
}

function SpecDDL_Triggers_PK() {
	$this->LogEntry("Building primary key clauses");
	global $utabs;
   $retval=true;
	foreach ($utabs as $utab) {
		$retval
         = $retval 
         && $this->SpecDDL_Triggers_PK_DoOne(
            $utab["table_id"],$utab["pk"],true,$utab["rules"]
         );
		foreach ($utab["indexes"] as $index) {
			if ($index["unique"]=="Y") { 
				$retval
               =$retval 
               && $this->SpecDDL_Triggers_PK_DoOne(
                   $utab["table_id"],$index["cols"],false,false
               );
			}
		}
	}
   return $retval;
}

// Big changes 10/13/06, allow range pk's
function SpecDDL_Triggers_PK_DoOne($table_id,$cols,$pk,$rulestable) {
	global $utabs;
	$comp_arr= array();
	$mtchList = "";
	$errmsg  = "";
	$nullList = "";
	$chngList = "";
	$keys = explode(",",$cols);
	$makeuppers="";
	$sp="                  ";
   // calculated primary keys get validated at 7000, not 3000
   $flag_calculated=false;
	
	if ($utabs[$table_id]["rules"]=="Y") { return true; }
   
   $retval=true;
	
	// Process the list of columns
	foreach ($keys as $key) {
      if($key=='') {
         $this->LogEntry("ERROR ");
         $this->LogEntry("ERROR on table $table_id, no primary key?");
         $retval=false;
         continue;
      }
      
      // Get range_from/range_to, they force an overlap validation,
      //  and also have general effects throughout this code
      $range_from=$utabs[$table_id]["flat"][$key]["range_from"];
      $range_to  =$utabs[$table_id]["flat"][$key]["range_to"];
      //$this->LogEntry("$key, range_from: $range_from, range_to: $range_to");
      
      // If this column is calculated, flip the the flag
      if ($utabs[$table_id]['flat'][$key]['automation_id']=='EXTEND') {
         $this->LogEntry(" -> Calculated primary key: $table_id.$key");
         $flag_calculated=true;
      }

      // KFD 12/16/06.  Ranges are allowed to be null, they are not
      // in the null list, but they are in the change list
      if ($range_from.$range_to=='') {
         $nullList .= $this->AddList($nullList," OR ") . " new.$key IS NULL ";
      }
      $chngList .= $this->AddList($chngList," OR \n       ") . "new.$key <> old.$key ";
      if ($utabs[$table_id]["capspk"]=="Y") {
         $makeuppers.="        new.$key=UPPER(new.$key);\n";
      }
		
      // after setting null and change list, a "range_from" has no more
      // work to do, because we did the work on the "range_to"
      if ($range_from<>"") continue;
      
		// A Range-to column gets an overlap constraint instead of an equality
		if ($range_to=="") {
			$mtchList .= $this->AddList($mtchList," AND \n$sp"). " $key = new.$key ";
      }
		else {
         //$this->LogENtry("Doing an overlap");
         // Cannot use postgres "overlaps", allows equal boundary points
         // Do not allow nested intervals
         $mtchList .= $this->AddList($mtchList," AND \n")
            .    "(    (          COALESCE(new.$key ,##1/1/1000##::date) \n"
            .$sp."        between COALESCE($key     ,##1/1/1000##::date) \n"
            .$sp."            AND COALESCE($range_to,##1/1/9999##::date) \n"
            .$sp."     )\n"
            .$sp."  OR (          COALESCE(new.$range_to,##1/1/9999##::date)\n"
            .$sp."        between COALESCE($key     ,##1/1/1000##::date) \n"
            .$sp."            AND COALESCE($range_to,##1/1/9999##::date) \n"
            .$sp."     )\n"
            .$sp.")";
		}
	}
	
   // BIG CHANGE, KFD, 4/11/07, went to column-by-column reporting
   // of errors.
   $errmsg='';
   foreach($keys as $key) {
      $errmsg.="$key,1002,Duplicate Value;";
   }
   /*
	$errmsg = "##*,1002,Already a row in table ".strtoupper(trim($table_id))." where ";
	for ($i=count($keys)-1; $i>=0; $i--) {
		if ($i==count($keys)-1) {
			if (count($keys)>1)
				$errmsg.=": ";
		}
		else {
			$errmsg.=", ";
		}
		$errmsg.=strtoupper($keys[$i])." = ## || COALESCE(new.".strtoupper($keys[$i])."::varchar,####) ||##";
	}
	$errmsg.=";##";
   */  
   // End of big change to pk duplicate message

   // KFD 4/11/07, column-based error messages   
   // Separate no-empty message for each column of pk
   $s1="    -- 3000 PK/UNIQUE Insert Validation\n";
   foreach($keys as $key) {
      $s1.="\n".
			"    IF new.$key is NULL THEN\n".
			"        ErrorCount = ErrorCount + 1;\n". 
			"        ErrorList = ErrorList || ##$key,1001,May not be empty;##;\n".
			"    END IF;\n";
   }
   
	//}
	
	// Primary key values may not be null, but right now we
	// are skipping the unique check for null values of other unique constraints
	if ($pk) {
      if($nullList=='') $nullList="1 = 0";
		$nullrule = 
         "    IF ". $nullList . " THEN\n".
         "        --Error was reported above, will not repeat ".
         "        --ErrorCount = ErrorCount + 1;\n". 
         "        --ErrorList = ErrorList || ##*,1001,Unique columns may not be null - ". $cols . "##;\n".
         "    ELSE\n";
	}
	else {
      if($nullList=='') $nullList="1 = 0";
		$nullrule = 
			"    IF NOT ($nullList) THEN\n"; 
	}
	
	
	$s1.= 
		"\n".
		$nullrule.
		$makeuppers.
		"        -- LOCK TABLE ". $table_id . " IN EXCLUSIVE MODE;\n".
		"        SELECT INTO AnyInt COUNT(*) FROM " . $table_id . "\n".
		"            WHERE ". $mtchList . ";\n".
		"        IF AnyInt> 0 THEN\n".
		"            ErrorCount = ErrorCount + 1;\n".
		"            ErrorList = ErrorList || ##".$errmsg."##;\n".
		"        END IF;\n".
		"    END IF;\n".
		"    -- 3000 END\n";
   $seq = $flag_calculated ? '7000' : '3000' ;
	$this->SpecDDL_TriggerFragment($table_id,"INSERT","BEFORE",$seq,$s1);

	//	"            ErrorList = ErrorList || ##Uniqueness violation;##;\n"

	
	// The prohibition against changes applies only to pk, and only if not a rules table
   $s1="    -- 3000 PK/UNIQUE Change prohibition\n";
   foreach($keys as $key) {
		$s1 = "\n".
			"    -- 3100 PK Change Validation\n".
			"    IF new.$key <> old.$key THEN\n". 
			"        ErrorCount = ErrorCount + 1;\n". 
			"        ErrorList = ErrorList || ##$key,1003,Cannot change value;##;\n".
			"    END IF;\n".
			"    -- 3100 END\n";
   }
   $this->SpecDDL_TriggerFragment($table_id,"UPDATE","BEFORE","3100",$s1);
   return $retval;
}

function SpecDDL_Triggers_FK() {
   $this->LogEntry("Building Foreign Key clauses");
	global $ufks,$utabs;
	
   $retval = true;
	foreach($ufks as $ufk) {
		//$this->LogEntry("Attempting fk for ".$ufk["table_id_chd"]." to ".$ufk["table_id_par"]);
		$ptab = $ufk["table_id_par"];
		$retval
         =$retval
         && $this->SpecDDL_Triggers_FK_PT(
            $ufk
            ,$ptab
            ,explode(",",$ufk["cols_chd"])
            ,explode(",",$ufk["cols_par"])
         );
	}
   return $retval;
}

function SpecDDL_Triggers_FK_PT($ufk,$ptab,$chdlist,$parlist) {
	global $utabs;
   $retval=true;
	// Build the various strings
	$nullList = $emptyList  = $mtchList   = $prntList = "";
	$delList  = $insParList = $insChdList = $chgList  = $chdCols = "";
	$x = 0;
	$insArr = array();
   $tid_par=$ufk['table_id_par'];
   $tid_chd=$ufk['table_id_chd'];
	foreach ($chdlist as $chd) {
      $colpar=$parlist[$x];
		$nullList   .= $this->AddList($nullList," OR ")."new.". $chd . " IS NULL";
      if ($utabs[$tid_par]['flat'][$colpar]['range_to']=='') {
         $mtchList   .= $this->AddList($mtchList," AND ")."par.". $colpar . " = new.". $chd ;
         $prntList   .= $this->AddList($prntList," AND ")."old.". $colpar . " = chd.". $chd ;
      }
      else {
         // KFD 12/16/06, allowed nulls in parent primary key when ranged,
         //   but we hardcode to assume they are dates
         //
         $range_to =$utabs[$tid_par]['flat'][$parlist[$x]]['range_to'];
         $mtchList   .= $this->AddList($mtchList," AND ")
            ."new.". $chd . " BETWEEN COALESCE(par.".$colpar.",##1/1/1000##::date) "
            ." AND COALESCE(par.".$range_to.",##1/1/9999##::date)";
         $prntList   .= $this->AddList($prntList," AND ")
            ."chd.". $chd . " BETWEEN COALESCE(old.".$colpar.",##1/1/1000##::date) "
            ." and COALESCE(old.".$range_to.",##1/1/9999##::date)";
      }
		$delList    .= $this->AddList($delList," AND ").$chd ." = old.".$parlist[$x];
		$insArr[$parlist[$x]] = "new.".$chd;
		$chgList    .= $this->AddList($chgList," OR ")."new.". $chd ." <> old." . $chd;
      // DISABLED BY KFD 4/17/07, will work on this later.  Don't know
      // why we're getting an error on framework tables
      //if(!isset($utabs[$ufk["table_id_chd"]]["flat"][$chd]["description"])) {
      //   $this->LogENTry("ERROR");
      //   $this->LogEntry("ERROR -> trying to access column $chd ");
      //   $this->LogEntry("ERROR -> building fk from $tid_chd to $tid_par");
      //   $retval=false;
      //}
      //else {
         $chdCols    .= $this->AddComma($chdCols).$utabs[$ufk["table_id_chd"]]["flat"][$chd]["description"];
      //}
		$x++;
	}
   if(!$retval) return false;
	

	// Some primary keys do not allow changes
	//
	if ($ufk["prevent_fk_change"]=="Y") {
      $s1='';
      foreach($chdlist as $chd) {
         $s1.="\n"
            ."    -- 6000 FK Prevent Child Change \n"
            ."    IF (new.$chd <> old.$chd) THEN\n"
            ."        ErrorCount = ErrorCount + 1;\n" 
            ."        ErrorList = ErrorList || ##$chd,1004,Value May Not Change;##;\n"
            ."    END IF;\n";
      }
      /*
		$s1 = "\n".
			"    -- 6000 FK Prevent Child Change \n".
			"    IF ($chgList) THEN\n". 
			"        ErrorCount = ErrorCount + 1;\n". 
			"        ErrorList = ErrorList || ##*,1004,Table ". $ufk["table_id_chd"] . 
						" cannot change values of columns: $chdCols##;\n".
			"    END IF;\n";
      */
		$this->SpecDDL_TriggerFragment($ufk["table_id_chd"],"UPDATE","BEFORE","6000",$s1);
	}


	// The EMPTY clause determines what to do if empty. 
	// Normally that is an error, but the "Allow_empty" flag
	// gives 'em a pass.  Notice the HUGE ASSUMPTION that
	// allow empty has a single-column key.
	//
	if ($ufk["allow_empty"] == "Y") {
		$chd = $chdlist[0];
		$chd_type = $utabs[$ufk["table_id_chd"]]["flat"][$chd]["type_id"];
		$chd_blank= ($chd_type=="int" || $chd_type=='numb') ? "0" : '####';
		//$emptyList  .= $this->AddList($emptyList," || ")."COALESCE(new.".$chd.",####)";
		$onEmpty =
			"    -- 5000 FK Insert/Update Child Validation\n".
			"    IF COALESCE(new.$chd,$chd_blank) <> $chd_blank THEN\n"; 
	}
	else {
      $onEmpty="";
      foreach($chdlist as $chd) {
         $onEmpty.=
            "    -- 5000 Insert/Update Child Validation: NOT NULL\n"
            ."    IF new.$chd IS NULL THEN\n"
            ."        ErrorCount = ErrorCount + 1;\n" 
            ."        ErrorList = ErrorList || ##$chd,1005,Required Value;##;\n"
            ."    END IF;\n";
      }
		$onEmpty .= 
			"    -- 5000 FK Insert/Update Child Validation\n".
			"    IF ". $nullList . " THEN\n".
         "        --Error was reported above, not reported again\n".
			"        --ErrorCount = ErrorCount + 1;\n". 
			"        --ErrorList = ErrorList || ##*,1005,Foreign key columns may not be null: ". $ufk["cols_chd"] . ";##;\n".
			"    ELSE\n";
         
	}
   
	// If there is no match under normal circumstances, we get an error,
	// but if it is an "Insert if not there...", it is a different story
	$noMatch="";
	if ($ufk["auto_insert"]=="Y") {
		// Now build any columns caught by "copysamecols", but don't
		// overwrite
		if ($ufk["copysamecols"]=="Y") {
			foreach ($utabs[$ufk["table_id_chd"]]["flat"] as $colname=>$colinfo) {
				// HARDCODE SKEY HARDCODED SKEY
				if ($colname=="skey") continue;
				if ($colname=="skey_quiet") continue; 
				if (isset($utabs[$ptab]["flat"][$colname])) {
					$insArr[$colname] = "new.".$colname;
				}
			}
		}
		if (isset($insArr["skey"])) unset($insArr["skey"]);
		$insArr["skey_quiet"] = $this->SQLFORMATLITERAL('Y',"char",true,true);
		
		// Finally build the parent and child list
		$insParList = implode(",",array_keys($insArr));
		$insChdList = implode(",",$insArr);

		$noMatch = 
			"            -- This is an AUTO-Insert Condition\n".
			"            -- The COPYSAMECOLS Flag was: ".$ufk["copysamecols"]."\n".
			"            INSERT INTO ". $ptab . "\n".
			"               (". $insParList . ") \n".
			"               VALUES \n".
			"               (". $insChdList  . ");\n";
	}
	else {
      $noMatch='';
      foreach($chdlist as $chd) {
         $noMatch .= "\n" 
            ."            ErrorCount = ErrorCount + 1;\n"
            ."            ErrorList = ErrorList || ##$chd,1006,Please Select Valid Value;##;\n";
         
      }
		//$noMatch = 
	   //	"            ErrorCount = ErrorCount + 1;\n".
		//	"            ErrorList = ErrorList || ##*,1006,No match in ". $ptab . " for ". $ufk["cols_chd"] .";##;\n";
	}
		
	$s1 = 
		"\n".
		$onEmpty.
		"        -- LOCK TABLE ". $ptab . " IN EXCLUSIVE MODE;\n".
		"        SELECT INTO AnyInt COUNT(*) FROM ". $ptab . " par \n".
		"            WHERE ".$mtchList . ";\n".
		"        IF AnyInt= 0 THEN\n".
		$noMatch . 
		"        END IF;\n".
		"    END IF;\n";
   // if the "allow_orphan" flag is set, do nothing
   if ($ufk["allow_orphans"]<>'Y') {
      $this->SpecDDL_TriggerFragment($ufk["table_id_chd"],"INSERT","BEFORE","5000",$s1,"FK:".$ufk["table_id_par"]);
      $this->SpecDDL_TriggerFragment($ufk["table_id_chd"],"UPDATE","BEFORE","5000",$s1,"FK:".$ufk["table_id_par"]);
   }
		
	// Now do the delete options.  Notice the sequence is 6000 and
	// not 5000.  We only need 5000 for the child table, where every
	// little piece is sequenced.  Notice for delete cascade we put it
	// at the front, so if there are any complex chains, they will all
	// be worked out before the rest of the trigger fires.  
	if ($this->zzArray($ufk,"delete_cascade")=="Y") {
		$prntList = str_replace("chd.","",$prntList);
		$s1 = "\n".
			"    -- 6000 FK Delete cascades to child rows \n".
			"    DELETE FROM ".$ufk["table_id_chd"]. " WHERE ".$prntList."; \n";
		$this->SpecDDL_TriggerFragment($ptab,"DELETE","BEFORE","0005",$s1);
	}
	else {
		$s1 = "\n".
			"    -- 6000 FK Delete Child Restrict\n".
			"    -- LOCK TABLE ". $ufk["table_id_chd"] . " IN EXCLUSIVE MODE;\n".
			"    SELECT INTO AnyInt COUNT(*) FROM ". $ufk["table_id_chd"] . " chd ".
					" WHERE ". $prntList . ";\n".
			"    IF AnyInt> 0 THEN\n". 
			"        ErrorCount = ErrorCount + 1;\n". 
			"        ErrorList = ErrorList || ##*,1007,Table ". $ufk["table_id_chd"] . 
						" has matches on columns ". $ufk["cols_chd"] . ";##;\n".
			"    END IF;\n";
      // if the "allow_orphan" flag is set, do nothing
      if ($ufk["allow_orphans"]<>'Y') {
         $this->SpecDDL_TriggerFragment($ptab,"DELETE","BEFORE","6000",$s1);
      }
	}
   return $retval;
}


function SpecDDL_Triggers_Automated() {
   $retval = true;
	$this->SpecDDL_Triggers_Automated_FetchDistribute();
	$retval = $retval && $this->SpecDDL_Triggers_Automated_Aggregate();
   return $retval;	
}

function SpecDDL_Triggers_Automated_FetchDistribute() {
   global $utabs;
	// Both FETCH and DISTRIBUTE pull to child on insert/update
	// but a DISTRIBUTE also pushes to child on change in parent
	//
	// Summary of what is going on:
	// 
	// Action                   FETCH  DISTRIBUTE  Result
	// ======================== =================  =========================
	// INS chd, val provided    FETCH  DISTRIBUTE  error
	// INS chd, no val provided FETCH  DISTRIBUTE  FETCH
	// UPD chd, key changed     FETCH  DISTRIBUTE  FETCH
	// UPD chd, val changed     FETCH              error
	// UPD chd, val changed            DISTRIBUTE  if matches, OK, else error
   // UPD par, val changed            DISTRIBUTE  row after, push to child
	
	$this->LogEntry("Building calculated FETCH and DISTRIBUTE clauses");
	global $ufks;
	$results = $this->SQLRead(
		"SELECT tf.table_id,tf.column_id
             ,tf.auto_prefix,tf.auto_suffix
             ,tf.automation_id,tf.formshort,tf.auto_formula 
		   FROM zdd.tabflat_c tf 
		  WHERE tf.automation_id IN ('FETCH','FETCHDEF','DISTRIBUTE') 
		  ORDER BY tf.table_id,tf.column_id");
   $ddall = array();
   $dddst = array();
	while ($row=pg_fetch_array($results)) {
		$autoid = trim(strtoupper($row["automation_id"]));
		list($tp,$cp)= explode(".",strtolower($row['auto_formula']));
      $details = array(
         'table_id'=>$row["table_id"]
         ,'column_id'=>$row['column_id']
         ,'automation_id'=>$autoid
         ,'table_id_par'=>$tp
         ,'column_id_par'=>$cp
         ,'auto_prefix'=>$row['auto_prefix']
         ,'auto_suffix'=>$row['auto_suffix']
      );
      $tpi = $row['table_id'].'_'.$tp.'_'.$row['auto_suffix'];
      
      // This creates definitions grouped by foreign key definitions
      if(!isset($ddall[$row['table_id']][$tpi])) {
         $ddall[$row['table_id']][$tpi]=array();
      }
      $ddall[$row['table_id']][$tpi][]=$details;
      
      //  Also group the definitions of parent->child for DISTRIBUTEs
      if($autoid=='DISTRIBUTE') {
         if(!isset($dddst[$tpi][$row['table_id']])) {
            $dddst[$tpi][$row['table_id']]=array();
         }
         $dddst[$tpi][$row['table_id']][]=$details;
      }
   }
   
   // NOTE: FETCH and EXTEND columns are sequenced on dependencies, as
   // one EXTEND may generate the foreign key for the next FETCH.  This
   // system goes column-by-column.  However, we use just one column
   // pair between any two tables, since all commands between any two
   // tables are grouped together.
   
   // Now we will loop through the $ddall array and generate all of the
   // code snippets for insert and update.  The idea is to end up with
   // only a single fetch for each foreign key definition
   foreach($ddall as $table_id=>$ddall2) {
      foreach($ddall2 as $foreign_key=>$details) {
         $d=$details[0];
         $table_id_par = $details[0]['table_id_par'];
         $cs_errorsi=$cs_errorsu='';
         $cs_col1 = array();
         $cs_col2 = array();

         // Generate the keys match between the two tables
         // KFD 2/16/07, big change to allow suffix/prefix
         //$keyname = $table_id."_".$table_id_par."_";
         $keyname = $foreign_key;
         $keys = $ufks[$keyname]["cols_both"];
         // KFD 10/12/06, part of general changes to range foreign keys
         //$match = str_replace(","," AND new.",$keys);
         //$match = "new.".str_replace(":"," = par.",$match);
         $match=str_replace("chd.","new.",$ufks[$keyname]['cols_match']);
         
         // Generate a key change expression for child table
         $keychga = explode(",",$ufks[$keyname]["cols_chd"]);
         $keychgb = array();
         foreach($keychga as $keycol) {
            $keychgb[] = " coalesce(new.$keycol,0)::varchar <> coalesce(old.$keycol,0)::varchar ";
         }
         $keychg = implode(' OR ',$keychgb);

         // Now build column-by-column details and lists      
         foreach($details as $detail) {
            $colpar=$detail['column_id_par'];
            $col=$detail['column_id'];
            // Insert error
            $cs_errorsi='';
            if($detail['automation_id']<>'FETCHDEF') {
               $cs_errorsi.="\n"
                  ."    IF new.$col IS NOT NULL THEN \n"
                  ."        ErrorCount = ErrorCount + 1; \n"
                  ."        ErrorList = ErrorList || ##$col,5001,"
                  ."may not be explicitly assigned;##;\n"
                  ."    END IF;\n";
            }
            // Update error
            if($detail['automation_id']=='FETCH') {
               $cs_errorsu.="\n"
                  ."    IF new.$col <> old.$col THEN \n"
                  ."        ErrorCount = ErrorCount + 1; \n"
                  ."        ErrorList = ErrorList || ##$col,5001,"
                  ."may not be explicitly assigned;##;\n"
                  ."    END IF;\n";
            }
            elseif($detail['automation_id']=='FETCHDEF') {
               $cs_errorsu.="";
            }
            else {
               $cs_errorsu.="\n"
                  ."    IF new.$col <> old.$col THEN \n"
                  ."       IF new.$col <> (SELECT par.$colpar FROM "
                  .$table_id_par." par WHERE $match ) THEN \n"
                  ."            ErrorCount = ErrorCount + 1; \n"
                  ."            ErrorList = ErrorList || ##$col,5001,"
                  ."may not be explicitly assigned;##;\n"
                  ."       END IF;\n"
                  ."    END IF;\n";
            }
               
            $cs_col1[] = 'new.'.$col;
            $cs_col2[] = 'par.'.$colpar;
         }
         
         // First for insert.  Error plus assignment 
         $s1="\n"
            .$cs_errorsi
            ."    SELECT INTO ".implode(',',$cs_col1)."\n"
            ."                ".implode(',',$cs_col2)."\n"
            ."      FROM $table_id_par par WHERE $match ;\n";
         $this->SpecDDL_TriggerFragment(
            $table_id,"INSERT","BEFORE","5000",$s1,$details[0]['column_id']
         );
         
         // Update is almost the same, different error, same assignment
         $s1="\n"
            .$cs_errorsu
            ."   IF $keychg THEN \n"
            ."       SELECT INTO ".implode(',',$cs_col1)."\n"
            ."                   ".implode(',',$cs_col2)."\n"
            ."         FROM $table_id_par par WHERE $match ;\n"
            ."   END IF;\n";
         $this->SpecDDL_TriggerFragment(
            $table_id,"UPDATE","BEFORE","5000",$s1,$details[0]['column_id']
         );
      }
   }

   // Now, just for distributes, generate the PUSH from the parent
   // when the values change
   foreach($dddst as $foreign_key=>$dddst2) {
      foreach($dddst2 as $table_id=>$details) {
         //hprint_r($details);
         $table_id_par = $details[0]['table_id_par'];
         //echo "Doing $foreign_key on table par $table_id_par";
         //exit;

         // Generate the keys match between the two tables
         // KFD 3/1/07, fix this
         //$keyname = $table_id."_".$table_id_par."_";
         $keyname=$foreign_key;
         $keys = $ufks[$keyname]["cols_both"];
         // KFD 10/12/06, part of range foreign keys actually
         //$match = str_replace(","," AND $table_id.",$keys);
         //$match = "$table_id.".str_replace(":"," = new.",$match);
         $match=str_replace("chd.","new.",$ufks[$keyname]['cols_match']);
         $match=str_replace("par.",$table_id.".",$match);
         

         // Now build column-by-column details and lists      
         $cs_chg = array();
         $cs_set = array();
         foreach($details as $detail) {
            $colpar=$detail['column_id_par'];
            $col=$detail['column_id'];
            //exit;
            // KFD 3/12/07, different code for dates
            $type_id=$utabs[$table_id_par]['flat'][$colpar]['type_id'];
            if($type_id =='date' OR $type_id =='dtime') {
               $cs_chg[] = " COALESCE(new.$colpar,##1900-01-01##) <> COALESCE(old.$colpar,##1900-01-01##) ";
            } else {
               $cs_chg[] = " new.$colpar <> old.$colpar ";
            }
            $cs_set[] = $col. "= new.$colpar ";
         }
         
         $s1="\n"
            ."    -- 6000 DISTRIBUTE PUSH \n"
            ."    IF ".implode(' OR ',$cs_chg)." THEN \n"
            ."        UPDATE $table_id SET \n               "
            .implode("\n              ,",$cs_set)."\n"
            ."         WHERE $match; \n"
            ."    END IF;\n";
			$this->SpecDDL_TriggerFragment(
            $table_id_par,"UPDATE","AFTER","6000",$s1,$details[0]['column_id']
         );	
      }
   }
}

function SpecDDL_Triggers_Automated_Aggregate()  {
	global $ufks,$utabs;
   $retval=true;
	$this->LogEntry("Building calculated AGGREGATE clauses");
   
   // retrieve in order of table, make list of tables 
	$results = $this->SQLRead(
		"SELECT tf.table_id,tf.column_id,tf.automation_id,tf.auto_formula" 
		." FROM zdd.tabflat_c tf " 
		." WHERE tf.automation_id IN ('SUM','COUNT','LATEST','MIN','MAX')"
      ." ORDER BY tf.table_id"
   );
   $tabs_chd = array();
   $tabs_par = array();
	while ($row=pg_fetch_array($results)) {
      // basic stats, including list of child tables 
		$table_par = $row["table_id"];
		$column_par = $row["column_id"];

		$column_parx= " COALESCE($column_par,0)";
		$automation_id = trim(strtoupper($row["automation_id"]));
		$expr = $row["auto_formula"];

		$twovals = explode(".", str_replace("@","",$expr));
		$table_chd = strtolower($twovals[0]);
		$column_chd = strtolower($twovals[1]);
		$table_info_chd = &$GLOBALS["utabs"][$table_chd]["flat"];

      if(!isset($ufks[$table_chd."_".$table_par."_"])) {
         $this->LogEntry("ERROR");
         $this->LogEntry("ERROR -> building aggregate clause, ");
         $this->LogEntry("ERROR -> from $table_chd up to $table_par ");
         $this->LogEntry("ERROR -> a foreign key must be defined.");
         $retval=false;
         continue;
      }

      // notice the matchup doesn't distinguish really between
      // parent and child, it assumes there is only one FK
      $mx    = $ufks[$table_chd."_".$table_par."_"]["cols_match"];
		$match = $ufks[$table_chd."_".$table_par."_"]["cols_match"];
		$match     = str_replace("par.","new.",$mx);
		//$match = str_replace(","," AND ",$match);
      $match_old = str_replace("new.",'old.',$match);

      $match_latest = $ufks[$table_chd."_".$table_par."_"]["cols_match"];
      $match_latest = str_replace("chd.","new.",$match_latest);
      $match_latest = str_replace(","," AND "  ,$match_latest);
      $match_latest = str_replace("par.",""    ,$match_latest);
      
      // New general purpose match expressions
      $mx_old_par = str_replace('chd.','old.',$mx);
      $mx_old_par = str_replace('par.',$table_par.'.',$mx_old_par);
      $mx_new_par = str_replace('chd.','new.',$mx);
      $mx_new_par = str_replace('par.',$table_par.'.',$mx_new_par);
      $mx_old_chd= str_replace('par.','old.',$mx);
      $mx_old_chd = str_replace('chd.',$table_chd.'.',$mx_old_chd);
      $mx_new_chd = str_replace('par.','new.',$mx);
      $mx_new_chd = str_replace('chd.',$table_chd.'.',$mx_new_chd);
      
      // Special simple code for 'LATEST', do this and go to next one
      // Note 3/7/06, when we started "registering" SUM and COUNT
      // for optimization, we left this alone.  W/lots of writes this
      // would be a performance killer.
      if (in_array($row['automation_id'],array('LATEST','SUM','COUNT'))) {
         $table_info_chd = &$GLOBALS["utabs"][$table_chd]["flat"];
         $type_id_chd = $table_info_chd[$column_chd]['type_id'];
         $blank = $this->SQLFormatBlank($type_id_chd,true,true);
         
         if($row['automation_id']=='LATEST') {
            $s1 = "\n".
               "    -- 6000 LATEST Push\n".
               "    IF new.$column_chd <> $blank AND \n".
               "       new.$column_chd IS NOT NULL THEN \n".
               "        UPDATE $table_par SET $column_par = new.$column_chd \n".
               "         WHERE $match_latest;\n".
               "    END IF;\n";
               $this->SpecDDL_TriggerFragment($table_chd,"INSERT","AFTER","6000",$s1);
               $this->SpecDDL_TriggerFragment($table_chd,"UPDATE","AFTER","6000",$s1);
         }
         if($row['automation_id']=='SUM') {
            $s1 = "\n".
               "    -- 6000 SUM Push\n".
               "    IF COALESCE(new.$column_chd,0) <> COALESCE(old.$column_chd,0) THEN\n".
               "        UPDATE $table_par SET $column_par \n".
               "               = COALESCE($column_par,0) \n".
               "               + COALESCE(new.$column_chd,0) \n".
               "               - COALESCE(old.$column_chd,0) \n".
               "         WHERE $mx_new_par;\n".
               "    END IF;\n";
            $this->SpecDDL_TriggerFragment($table_chd,"UPDATE","AFTER","6000",$s1);
            $s1 = "\n".
               "    -- 6000 SUM Push\n".
               "    IF COALESCE(new.$column_chd,0) <> 0 THEN\n".
               "        UPDATE $table_par SET $column_par \n".
               "               = COALESCE($column_par,0) \n".
               "               + COALESCE(new.$column_chd,0) \n".
               "         WHERE $mx_new_par;\n".
               "    END IF;\n";
            $this->SpecDDL_TriggerFragment($table_chd,"INSERT","AFTER","6000",$s1);
            $s1 = "\n".
               "    -- 6000 SUM Push\n".
               "    IF COALESCE(old.$column_chd,0) <> 0 THEN\n".
               "        UPDATE $table_par SET $column_par \n".
               "               = COALESCE($column_par,0) \n".
               "               - COALESCE(old.$column_chd,0) \n".
               "         WHERE $mx_old_par;\n".
               "    END IF;\n";
            $this->SpecDDL_TriggerFragment($table_chd,"DELETE","AFTER","6000",$s1);
         }
         if($row['automation_id']=='COUNT') {
            $s1 = "\n".
               "    -- 6000 COUNT Push\n".
               "    UPDATE $table_par  \n".
               "       SET $column_par = COALESCE($column_par,0) + 1\n".
               "     WHERE $mx_new_par;\n";
            $this->SpecDDL_TriggerFragment($table_chd,"INSERT","AFTER","6000",$s1);
            $s1 = "\n".
               "    -- 6000 COUNT Push\n".
               "    UPDATE $table_par \n".
               "       SET $column_par = COALESCE($column_par,0) - 1\n".
               "     WHERE $mx_old_par;\n";
            $this->SpecDDL_TriggerFragment($table_chd,"DELETE","AFTER","6000",$s1);
         }
         //continue;
      }  

      // Special simple code for 'MIN/MAX', do this and go to next one
      // Note 5/5/06,  W/lots of writes this
      // would be a performance killer.
      if ($row['automation_id'] == 'MIN' || $row['automation_id'] == 'MAX') {
         $table_info_chd = &$GLOBALS["utabs"][$table_chd]["flat"];
         $type_id_chd = $table_info_chd[$column_chd]['type_id'];
         $blank = $this->SQLFormatBlank($type_id_chd,true,true);
         $s1 = "
    -- 6000 MIN/MAX Push
    IF new.$column_chd IS NOT NULL THEN
        UPDATE $table_par SET $column_par
          = (SELECT ".$row['automation_id']."($column_chd)
               FROM $table_chd WHERE $match 
            )
         WHERE $match ;
    END IF;\n";
         $this->SpecDDL_TriggerFragment($table_chd,"INSERT","AFTER","6000",$s1);
         $s1 = "
    -- 6000 MIN/MAX Push
    IF new.$column_chd <> old.$column_chd THEN
        UPDATE $table_par SET $column_par
          = (SELECT ".$row['automation_id']."($column_chd)
               FROM $table_chd WHERE $match 
            )
         WHERE $match ;
    END IF;\n";
         $this->SpecDDL_TriggerFragment($table_chd,"UPDATE","AFTER","6000",$s1);
         $s1 = "
    -- 6000 MIN/MAX Push
    IF new.$column_chd <> old.$column_chd THEN
        UPDATE $table_par SET $column_par
          = (SELECT ".$row['automation_id']."($column_chd)
               FROM $table_chd WHERE $match_old 
            )
         WHERE $match_old;
    END IF;\n";
         $this->SpecDDL_TriggerFragment($table_chd,"UPDATE","AFTER","6000",$s1);
         $s1 = "
    -- 6000 MIN/MAX Push
    IF old.$column_chd IS NOT NULL THEN
        UPDATE $table_par SET $column_par
          = (SELECT ".$row['automation_id']."($column_chd)
               FROM $table_chd WHERE $match_old 
            )
         WHERE $match_old;
    END IF;\n";
         $this->SpecDDL_TriggerFragment($table_chd,"DELETE","AFTER","6000",$s1);
         //continue;
      }  

      
  		// Always default aggregates to zero
		$s1 = "\n".
			"    -- 1020 Aggregate Defaults\n".
			"    new.$column_par = 0;\n";
		$this->SpecDDL_TriggerFragment($table_par,"INSERT","BEFORE","1020",$s1);

      // Build the expression to create the compound key
      // IGNORED AS OF 3/14/07, just get list tabs_par
      $lchd = $ufks[$table_chd."_".$table_par."_"]["cols_both"];
      $apairs= explode(',',$lchd);
      $aexpr = array();
      $apars = array();
      foreach($apairs as $pair) {
         list($colchd,$colpar) = explode(':',$pair);
         $colprec = $utabs[$table_par]['flat'][$colpar]['colprec'];
         switch($table_info_chd[$colchd]['type_id']) {
            case 'char':
            case 'vchar':
               $apar = $colpar.':C:'.$colprec;
               $aexpr[]="RPAD(*-*.".$colchd.",$colprec)";
               break;
            case 'date':
               $apar = $colpar.':D:10';
               $aexpr[]="to_char(*-*.".$colchd.",##YYYY-MM-DD##)"
                  ." || to_char(*-*.".$colchd.",##YYYY-MM-DD##)";
               break;
            case 'int':
               $colprec = 15;
            default:
               $apar = $colpar.':N:'.$colprec;
               $aexpr[]
                  ='RPAD(CAST(*-*.'.$colchd." as char($colprec)),$colprec)";
         }
         $apars[]=$apar;
      }
      $bx   = implode(' || ',$aexpr);
      $bnew = str_replace('*-*','new',$bx);
      $bold = str_replace('*-*','old',$bx);
      // Make entry in list of child-parent links
      $tabs_chd[$table_chd][$table_par]=implode(',',$apars);
      $tabs_par[$table_par][$table_chd][$column_par]
         =array($column_chd,$automation_id);

      $enew = $automation_id=='SUM' ? "coalesce(new.$column_chd,0)" : '1';
      $eold = $automation_id=='SUM' ? "coalesce(old.$column_chd,0)" : '1';
      
      // Register the values in INS, UPD, and DEL
      $s1 = "\n".
          "   -- 6000 Aggregate Push, Register Add new value\n"
         ."   AnyChar := $bnew;\n"
         ."   perform AggRegister(##$table_chd##,##$table_par##,AnyChar"
         .",##$column_par##,$enew,0);\n";
		//$this->SpecDDL_TriggerFragment($table_chd,"INSERT","BEFORE","6000",$s1);
		//$this->SpecDDL_TriggerFragment($table_chd,"UPDATE","BEFORE","6000",$s1);
      //$s1 = "\n".
      //    "   -- 6000 Aggregate Push (Register method)\n"
      //   ."   AnyChar := $bnew;\n"
      //   ."   perform AggRegister(##$table_chd##,##$table_par##,AnyChar"
      //   .",##$column_par##,$enew,$eold);\n";
		//$this->SpecDDL_TriggerFragment($table_chd,"UPDATE","BEFORE","6000",$s1);
      $s1 = "\n".
          "   -- 6000 Aggregate Push, Register Subtract old value\n"
         ."   AnyChar := $bold;\n"
         ."   perform AnyInt AggRegister(##$table_chd##,##$table_par##,AnyChar"
         .",##$column_par##,0,$eold);\n";
		//$this->SpecDDL_TriggerFragment($table_chd,"DELETE","BEFORE","6000",$s1);
		//$this->SpecDDL_TriggerFragment($table_chd,"UPDATE","BEFORE","6000",$s1);
	}
   
   // These are the statement-level commands that initialize and
   // then commit the values that were registered in the row-by-row commands
   /*
   foreach ($tabs_chd as $tab_chd=>$tab_pars) {
      $s1 = "\n".
           "   -- 6000 Aggregate Initialize (Register method)\n"
          ."   perform AggInit(##$tab_chd##);\n";
      $this->SpecDDL_TriggerFragment(
         $tab_chd,"INSERT","BEFORE","6000",$s1,'',true
      );
      $this->SpecDDL_TriggerFragment(
         $tab_chd,"UPDATE","BEFORE","6000",$s1,'',true
      );
      $this->SpecDDL_TriggerFragment(
         $tab_chd,"DELETE","BEFORE","6000",$s1,'',true
      );
      
      foreach($tab_pars as $tab_par=>$keymix) {
         $s1 = "\n".
              "   -- 6000 Aggregate Commit (Register method)\n"
             ."   perform AggCommit(##$tab_chd##,##$tab_par##,##$keymix##);\n";
         $this->SpecDDL_TriggerFragment(
            $tab_chd,"INSERT","AFTER","6000",$s1,'',true
         );
         $this->SpecDDL_TriggerFragment(
            $tab_chd,"UPDATE","AFTER","6000",$s1,'',true
         );
         $this->SpecDDL_TriggerFragment(
            $tab_chd,"DELETE","AFTER","6000",$s1,'',true
         );
      }
   }
   */
   
   // Now comes a batch of commands to recalculate an aggregates
   // goes into the row-by-row for a table
   foreach($tabs_par as $tab_par=>$info_chd) {
      $sq = '';
      foreach($info_chd as $tab_chd=>$acols) {
         // Build lists of columns to SUM/COUNT
         $acolspar=array();
         $acolschd=array();
         foreach($acols as $colpar=>$colinfo) {
            list($colchd,$automation_id)=$colinfo;
            $acolspar[]="new.".$colpar;
            if($automation_id=='SUM') {
               $acolschd[]="SUM(COALESCE($colchd,0))";
            }
            else {
               $acolschd[]="COUNT(*)";
            }
         }
         $scolspar=implode(',',$acolspar);
         $scolschd=implode(',',$acolschd);
         
         // Take the key and build a matching clause and a group by
         $lmatches = $ufks[$tab_chd."_".$tab_par."_"]["cols_both"];
         $apairs= explode(',',$lmatches);
         $amatches = array();
         $agroup = array();
         foreach($apairs as $apair) {
            list($colchd,$colpar)=explode(':',$apair);
            $amatches[] = "$colpar = new.$colchd";
            $agroup[] = $colchd;
         }
         $swhere = implode(' AND ',$amatches);
         $sgroup = implode(','    ,$agroup);
         
         // Now build 
         $sq.="\n"
            ."        SELECT into $scolspar \n"
            ."                    $scolschd \n"
            ."           FROM $tab_chd \n"
            ."          WHERE $swhere ;\n";
      }
      
      // Now build the final SQL for this table
      $sq="\n"
         ."    --- 4000 Recalculate aggregates \n"
         ."    IF new._agg=##C## THEN "
         .$sq
         ."        new._agg=####;\n"
         ."    END IF;\n";
      echo "building for $tab_par";
      //hprint_r($sq);
      $this->SpecDDL_TriggerFragment(
          $tab_par,"UPDATE","BEFORE","4000",$sq
      );
   }
   return $retval;
}

function SpecDDL_Triggers_ColConsTypes()  {
	$this->LogEntry("Building Type-based column constraints");
	
	$results = $this->SQLREAD(
		"Select table_id,column_id,type_id,description FROM zdd.tabflat_c".
		" WHERE type_id in ('cbool','gender','time')");
	while ($row=pg_fetch_array($results)) {
		$c  = "new.".trim($row["column_id"]);
		$uc = $c." = UPPER(".$c.");\n";
		$msg= "Column -".trim($row["description"])."-";
		switch (trim($row["type_id"])) {
			case "cbool" : 
           $type='6001';
				$test = " IN (##Y##,##N##)";
				$msg .= " can be either Y or N";
				break;
			case "gender": 
           $type='6002';
				$test = " IN (##M##,##F##)"; 
				$msg .= " can be either M or F";
				break;
			case "time"  :
           $type='6003';
				$test = " BETWEEN 0 AND 1439";
				$msg  = " must be between 0 and 1439 ";
				$uc = "";
		}
		$s1 = "\n". 
				"    -- 7010 Column Constraint\n".
				"    ".$uc.
				"    IF NOT ($c $test) THEN \n".
				"        ErrorCount = ErrorCount + 1;\n". 
				"        ErrorList = ErrorList || ##$c,$type,$msg;##;\n". 
				"    END IF;\n";
			$this->SpecDDL_TriggerFragment($row["table_id"],"INSERT","BEFORE","7010",$s1);
			$this->SpecDDL_TriggerFragment($row["table_id"],"UPDATE","BEFORE","7010",$s1);
		
	}
}

function SpecDDL_Triggers_Chains() {
	$chains = $this->SpecDDL_Triggers_ChainsList();
	$this->SpecDDL_Triggers_ChainsCode($chains);
	$this->SpecDDL_Triggers_ChainsBuild($chains);
}
	
function SpecDDL_Triggers_ChainsList() { 
	$sq=
"Select a.table_id,a.column_id,a.chain,a.uicolseq,a.argtype,a.sequence,
        a.column_id_arg,a.literal_arg,t.funcoper,t.compoper 
   from zdd.tabchainargs_c  a
	JOIN zdd.tabchaintests_c t ON a.chain = t.chain AND a.uicolseq=t.uicolseq
 UNION ALL
 Select a2.table_id,a2.column_id,a2.chain,a2.uicolseq,a2.argtype,a2.sequence,
        a2.column_id_arg,a2.literal_arg,t2.funcoper,t2.compoper 
   from zdd.colchainargs_c  a2
	JOIN zdd.colchaintests_c t2 ON a2.chain = t2.chain AND a2.uicolseq=t2.uicolseq
 ORDER BY 1,2,3,4,5,6";
   //echo $sq;
	$results = $this->SQLRead($sq);
 	$resall = pg_fetch_all($results);
	if ($resall===false) return array();
	
	$chainname = "";
	$chains = array();
	foreach ($resall as $row) {
		// If break on chain name, make a new name
		$newchain = 
			"T:".trim($row["table_id"]).":".
			"C:".trim($row["column_id"]).":".
			"CH:".trim($row["chain"]);
		if ($newchain <> $chainname) {
			$chainname = $newchain;
			$chains[$chainname] = $row;
			$chains[$chainname]["tests"] = array();
			$uicolseq = "";  // force break to new test
		}
		
		// if break on test, start a new test
		if ($uicolseq <> trim($row["uicolseq"])) {
			$uicolseq = trim($row["uicolseq"]);
			$chains[$chainname]["tests"][$uicolseq] = $row;
			$chains[$chainname]["tests"][$uicolseq]["_compare"] = array();
			$chains[$chainname]["tests"][$uicolseq]["_return"]  = array();
		}
		
		// Now break on test 
		$rowadd = array(
			"literal_arg"=>$row["literal_arg"],
			"column_id_arg"=>$row["column_id_arg"]);
		if (trim($row["argtype"])=="0") 
			$chains[$chainname]["tests"][$uicolseq]["_compare"][] = $rowadd;
		else 
			$chains[$chainname]["tests"][$uicolseq]["_return"][] = $rowadd;
		
	}
   //html_vardump($chains);
   //exit;
	return $chains;
}

function SpecDDL_Triggers_ChainsCode(&$chains) {
	$this->LogEntry("CHAINS: Convert to CASE/WHEN statements");
	// this happy guy takes all of the chains
	// and codes them up into CASE WHEN statements,
	// but does not actually make trigger fragments yet

	foreach ($chains as $key=>$chain) {
		$ctid = $rtid = "char";
		if (trim($chain["column_id"])!="") {
			$tid = $chain["table_id"];
			$cid = $chain["column_id"];
			$ctid = $GLOBALS["utabs"][$tid]["flat"][$cid]["type_id"];
			if (trim($chain["chain"])=="calc") $rtid = $ctid;
		}			

		$chaintext = "";
		foreach ($chain["tests"] as $test) {
			$return    = $this->TrigGen_ChainReturn( $rtid,$test);
			$compare   = $this->TrigGen_ChainCompare($ctid,$test);
			$chaintext.= "        WHEN $compare THEN $return \n";
		}
		
		// This is table prevent constraint
		if (trim($chain["column_id"])=="") {
			$chaintext = str_replace("new.","old.",$chaintext);
		}
		
		$chains[$key]["_chaintext"] = " CASE ".trim($chaintext).
			"        ELSE ".$this->SQLFormatBlank($rtid,true,true)." END";
			
	}
}

function TrigGen_ChainReturn($rtid,$test) {
	$funcoper = trim($test["funcoper"]);
	$retval = "";
   
   // Some functions are simple, or have fixed number of args
   $cret=$test['_return'][0]['column_id_arg'];
   switch ($funcoper) {
      case 'SUBS':
         return $this->TrigGen_CRetSubstring($rtid,$test);
         break;
      case 'REPLACE': 
      case 'LPAD':
      case 'RPAD':
         return $this->TrigGen_CRetString3($rtid,$test);
         break;
      case 'UPPER':
      case 'LOWER':
         return $this->TrigGen_CRetString1($rtid,$test);
         break;
      case 'BITNOT':
         return " ~".$test['_return'][0];
         break;
      case 'EXTRACTYEAR':
         $expr="EXTRACT(year from new.$cret)";
         if($rtid=='char' || $rtid=='vchar') {
            $expr="CAST($expr as char(4))";
         }
         return $expr;
         break;
      case 'EXTRACTMONTH':
         $expr="EXTRACT(month from new.$cret)";
         if($rtid=='char' || $rtid=='vchar') {
            $expr1 = "CAST($expr as char(1))";
            $expr2 = "CAST($expr as char(2))";
            $expr  = " CASE WHEN $expr NOT IN (10,11,12) THEN ##0## || $expr1 ELSE $expr2 END ";
         }
         return $expr;
         break;
      case 'EXTRACTDAY':
         $expr="EXTRACT(day from new.$cret)";
         if($rtid=='char' || $rtid=='vchar') {
            $expr="CAST($expr as char(2))";
         }
         return $expr;
         break;
   }  
   
	foreach ($test["_return"] as $retinfo) {
      $arg = $this->TrigGen_CRet_Arg($rtid,$retinfo);
		
		$fo = $funcoper;
		switch ($funcoper) {
         case "/"      :  $arg="cast($arg as float)"; break;
         case "BITAND" :  $fo = " & "; break;
         case "BITOR"  :  $fo = " | "; break;
         case "BITXOR" :  $fo = " # "; break;
			case "CON"     :
				$fo = " || ";
				$arg = " trim(".$arg.") ";
				break;
			case "subdyear":
				$arg = ' EXTRACT(year from '.$arg.') ';
				$fo = " - ";
				break;
		}
		$retval .= $this->zzListAdd($retval,$fo).$arg;
	}
	
	if ($retval=='') {
		$retval = $this->SQLFORMATBLANK($rtid,true,true);
	}
	
	return $retval;
}

function TrigGen_CRet_Arg($rtid,$retinfo) {
   if ($retinfo["column_id_arg"]<>"")
      $arg = "new.".trim($retinfo["column_id_arg"]);
   else {
      if(substr($retinfo['literal_arg'],0,1)=='!') {
         $arg=substr($retinfo['literal_arg'],1);
      }
      else {
         $arg =$this->SQLFORMATLITERAL(
            $retinfo["literal_arg"]
            ,$rtid,true,true
         );
      }
   }
   // This lets literal blanks be argument.  Note only one blank, but its
   // (ha ha) better than nothing.  (get it?)
   $arg = $arg=='####' ? '## ##' : $arg;
   return $arg;
}

function TrigGen_CRetSubstring($rtid,$test) {
   // This is fixed behavior:
   // return REPLACE($arg1,$arg2,$arg3)
   $arg1 = $this->TrigGen_CRet_arg($rtid,$test['_return'][0]);
   $arg2 = $this->TrigGen_CRet_arg('int',$test['_return'][1]);
   $arg3 = $this->TrigGen_CRet_arg('int',$test['_return'][2]);
   
   return " SUBSTRING($arg1 from $arg2 for $arg3) ";
}

function TrigGen_CRetString3($rtid,$test) {
   // This is fixed behavior:
   // return REPLACE($arg1,$arg2,$arg3)
   $arg1 = $this->TrigGen_CRet_arg($rtid,$test['_return'][0]);
   $arg2 = $this->TrigGen_CRet_arg($rtid,$test['_return'][1]);
   $arg3 = $this->TrigGen_CRet_arg($rtid,$test['_return'][2]);
   
   return ' '.$test['funcoper']."($arg1,$arg2,$arg3) ";
}
function TrigGen_CRetString1($rtid,$test) {
   // This is fixed behavior:
   // return REPLACE($arg1,$arg2,$arg3)
   $arg1 = $this->TrigGen_CRet_arg($rtid,$test['_return'][0]);
   return ' '.$test['funcoper']."($arg1) ";
}


function TrigGen_ChainCompare($ctid,$chaintest) {
	$compoper = trim($chaintest["compoper"]);
	if (count($chaintest["_compare"])==0) {
		return " 1 = 1 ";
	}

	// First get the argument.  Then, depending upon
	// the operator we will do different things.  Then
	// throw away the first so we can loop the rest
	//	
	$retinfo = $chaintest["_compare"][0];
	if ($retinfo["column_id_arg"]<>"") {
		$arg1 = trim($retinfo["column_id_arg"]);
		$cta1 = $GLOBALS["utabs"][$chaintest["table_id"]]["flat"][$arg1]["type_id"];
		$arg1 = "new.".$arg1;
	}
	else {
		$arg1 = $this->SQLFORMATLITERAL($retinfo["literal_arg"],$ctid,true,true);
		$cta1 = $ctid;
	}
	unset($chaintest["_compare"][0]);

	// Convert the remaining values into an array
	// that we can IMPLODE into various arrangements
	//	
	$args = array();
	foreach ($chaintest["_compare"] as $compinfo) {
		if ($compinfo["column_id_arg"]<>"")
			$arg = "new.".trim($compinfo["column_id_arg"]);
		else {
			$arg = $this->SQLFORMATLITERAL($compinfo["literal_arg"],$cta1,true,true); 
		}
		$args[] = $arg;
	}
		
	// Now create various clauses based on the comparison operator
	$retval = "";
	$not    = "";
	switch ($compoper) {
		case "!IN":
			$not = "NOT";
		case "IN":
			$retval = $arg1." ".$not." IN (".implode(",",$args).")";
			break;
		case "!BETWEEN":
			$not = "NOT";
		case "BETWEEN":
			$retval = $arg1. " $not BETWEEN ".implode(" AND ",$args);
			break;
		case "!EMPTY":
         $sfb=$this->SQLFORMATBLANK($cta1,true,true);
			$retval = "COALESCE($arg1,$sfb) <> $sfb";
			break;
		case "EMPTY":
         $sfb=$this->SQLFORMATBLANK($cta1,true,true);
			//$retval = $arg1. " =".$this->SQLFORMATBLANK($cta1,true,true);
			$retval = "COALESCE($arg1,$sfb) = $sfb";
			break;
		case "!NULL":
			$not = "NOT";
		case "NULL":
			$retval = $arg1. " IS $not NULL ";
			break;
		default: 
			$retval = $arg1." ".$compoper." ".$args[0];
	}
	if ($retval == "") {
		$retval = " 1 = 1 ";
	}
	return $retval;
}

function SpecDDL_Triggers_ChainsBuild(&$chains) {
	$this->LogEntry("CHAINS: build trigger fragments");
	
	foreach($chains as $chain) {
		$table_id = $chain["table_id"];
		$chaintext= $chain["_chaintext"];
		
		// Empty column means a table prevention chain
		if ($chain["column_id"]=="") {
			if ($chain["chain"] == "update_pre") {
				$s1 = 
					"    -- 005 Update Prevention Constraint (from chain)\n".
					"    AnyChar := ".$chaintext.";\n".
					"    IF AnyChar <> #### THEN\n".
					"        AnyChar := AnyChar || ##;##;\n".
					"        RAISE EXCEPTION ##%##,##*,7001,## || AnyChar;\n".  
					"    END IF;\n";
				$this->SpecDDL_TriggerFragment($table_id,"UPDATE","BEFORE","0500",$s1);
			}
			if ($chain["chain"] == "delete_pre") {
				$s1 = 
					"    -- 005 Delete Prevention Constraint (from chain)\n".
					"    AnyChar := ".$chaintext.";\n".
					"    IF AnyChar <> #### THEN\n".
					"        AnyChar := AnyChar || ##;##;\n".
					"        RAISE EXCEPTION ##%##,##*,7002,## || AnyChar;\n".  
					"    END IF;\n";
				$this->SpecDDL_TriggerFragment($table_id,"DELETE","BEFORE","0500",$s1);
			}
		}
		else {
			// Populated columns means its a column constraint 
			// or a calculated column
			//
			$colname = $chain["column_id"];
			
			if ($chain["chain"] == "calc") {
            // Define basic string, but can't use this just yet 
				$s1 = "\n".
					"    -- 5000 Extended Columns\n".
            	"    IF <<FAIL_CONDITION>> THEN\n".
					"        ErrorCount = ErrorCount + 1;\n". 
					"        ErrorList = ErrorList || ##$colname,5002,Cannot assign value directly to column $colname ;##;\n".
               "    ELSE \n".
					"        new.$colname = $chaintext ;\n".
					"    END IF;\n";
            // Insert version
            $s2=str_replace('<<FAIL_CONDITION>>',"new.$colname IS NOT NULL",$s1);
				$this->SpecDDL_TriggerFragment($table_id,"INSERT","BEFORE","5000",$s2,$colname);
            // Update Version
            $s2=str_replace('<<FAIL_CONDITION>>',"new.$colname <> old.$colname",$s1);
				$this->SpecDDL_TriggerFragment($table_id,"UPDATE","BEFORE","5000",$s2,$colname);
			}
			else {
				$s1 = 
					"    -- 7020 Column Constraint (from chain)\n".
					"    AnyChar := ".$chaintext.";\n".
					"    IF AnyChar <> #### THEN\n".
					"        ErrorCount = ErrorCount + 1;\n". 
					"        ErrorList = ErrorList || ##$colname,5003, ## || AnyChar || ##;##;\n". 
					"    END IF;\n";
				$this->SpecDDL_TriggerFragment($table_id,"INSERT","BEFORE","7020",$s1,$colname);
				$this->SpecDDL_TriggerFragment($table_id,"UPDATE","BEFORE","7020",$s1,$colname);
				
			}
		}
	}
}

function SpecDDL_Triggers_Cascades()  {
	$this->LogEntry("Building Cascade Actions");
	
	// Retrieve list of tables and their cascades
	$table_id = "";
	$tables   = array();
	$result = $this->SQLRead("Select * from zdd.tabcas_c ORDER BY uicolseq");
	while ($row = pg_fetch_array($result)) {
		if ($table_id <> $row["table_id"]) {
			$table_id = $row["table_id"];
			$tables[$table_id] = array();
		}
		$tables[$table_id][$row["cascade"]] = $row;
		$tables[$table_id][$row["cascade"]]["_cols"] = array();
		$tables[$table_id][$row["cascade"]]["_matches"] = array();
	}
	
	// Now go pull the columns down for each table/cascade,
	// then pull the matches down for each table/cascade
	$result = $this->SQLRead("Select * from zdd.tabcascols_c");
	while ($row = pg_fetch_array($result)) {
		$tables[$row["table_id"]][$row["cascade"]]["_cols"][] = $row;
	}
	$result = $this->SQLRead("Select * from zdd.tabcascolsm_c");
	while ($row = pg_fetch_array($result)) {
		$tables[$row["table_id"]][$row["cascade"]]["_matches"][] = $row;
	}
	
	// We have now locally cached the hierarchy of cascades,
	// we can roll through them and build trigger assignments
	//
	foreach ($tables as $table_id=>$cascades) {
		foreach ($cascades as $cascade) {
			$this->SpecDDL_Triggers_Cascades_One($cascade);
		}
	}
	
}

function SpecDDL_Triggers_Cascades_One(&$cascade)  {
	$utabs  = &$GLOBALS["utabs"];
	$tabsrc = $cascade["table_id"];
	$tabdst = $cascade["table_id_dest"];

	$cols = array();

   // First build any columns caught by "copystripprefix" then suffix
   $stripx = trim($this->zzArraySafe($cascade,'copystripprefix'));
	if ($stripx<>'') {
      //echo "Stripping prefix: $stripx\n";
		foreach ($utabs[$tabsrc]["flat"] as $colsrc=>$colinfo) {
         $x = substr($colsrc,0,strlen($stripx));
         //echo "Beginning of column is : $x\n";
         if (substr($colsrc,0,strlen($stripx))<>$stripx) continue;
         $coldst = substr($colsrc,strlen($stripx));
         //echo "Looking at destination column $coldst\n";
         if (isset($utabs[$tabdst]["flat"][$coldst])) {
            //echo " --- Destination is there!\n";
				$cols[$coldst] = "new.".$colsrc;
			}
		}
	}
	
   $stripx = trim($this->zzArraySafe($cascade,'copystripsuffix'));
	if ($stripx<>'') {
		foreach ($utabs[$tabsrc]["flat"] as $colsrc=>$colinfo) {
         if (substr($colsrc,-1,strlen($stripx))<>$stripx) continue;
         $coldst = substr($colsrc,0,strlen($colsrc)-strlen($stripx));
         if (isset($utabs[$tabdst]["flat"][$coldst])) {
				$cols[$coldst] = "new.".$colsrc;
			}
		}
	}
	

 	// Now build any columns caught by "copysamecols"
	if ($cascade["copysamecols"]=="Y") {
		foreach ($utabs[$tabsrc]["flat"] as $colname=>$colinfo) {
			if (!isset($cols[$colname])) {
				if (isset($utabs[$tabdst]["flat"][$colname])) {
					$cols[$colname] = "new.".$colname;
				}
			}
		}
	}

	// Explicit column assignments overwrite any options above
	//
	foreach ($cascade["_cols"] as $colassign) {
		$col = $colassign["column_id"];
		$val = ($colassign["retcol"]<>'') ?
			"new.".$colassign["retcol"] :
			$this->SQLFORMATLITERAL(
            $colassign["retval"]
            ,$utabs[$tabdst]["flat"][$col]["type_id"]
            ,true
            ,true
         );
		$cols[$col] = $val;
	}
	
	
	// HARDCODE skey behavior
	if (isset($cols["skey"])) unset($cols["skey"]);
	$cols["skey_quiet"] = $this->SQLFORMATLITERAL('Y',"char",true,true);
   
   // Audit columns
   $column_ts = '';
   if ($cascade['column_id_ts']<>'') {
      $column_ts = "        new.".$cascade['column_id_ts']."=now();\n";
   }
   $test = ' 1 = 1 ';
   if ($cascade['column_id_flag']<>'') {
      $test = 'new.'.$cascade['column_id_flag']."=##Y##";
   }
   $reset = '';
   if ($cascade['flag_reset']=='Y') {
      $reset = 'new.'.$cascade['column_id_flag'].'=##N##;';
   }
	
	// Now branch out on whether it is an insert or update
	if ($cascade["cascade_action"]=="INSERT") {
		// Convert the two array into comma-separated lists
		$inscols = implode(",",array_keys($cols));
		$insvals = implode(",",$cols);
		
		//$test = ($cascade['testins']=='') ? ' 1 = 1 ' : $cascade['testins'];
		
		// OK, build the SQL statement 
		$s1 = "\n"
			."    -- 9800 Cascade actions \n"
			."    IF $test THEN \n"
			."        INSERT INTO $tabdst ($inscols) VALUES ($insvals);\n"
         ."        $reset\n"
         .$column_ts
			."    END IF;\n";
	}
	else {
		// Convert the array into assignment statements
		$sets = "";
		foreach ($cols as $colname=>$colval) {
			$sets .=$this->AddComma($sets).$colname." = ".$colval;
		}
		
      $fk = $tabsrc."_".$tabdst."_".$cascade['suffix'];
		$colsmatch = $GLOBALS["ufks"][$fk]["cols_both"];
		$this->LogEntry("cols_both is: ".$colsmatch);
		$colsmatch = str_replace(":"," = ",$colsmatch);
		$colsmatch = "new.".str_replace(","," AND new.",$colsmatch);
		//$test = ($cascade['testupd']=='') ? ' 1 = 1 ' : $cascade['testupd'];
		
		$s1 = "\n"
			."    -- 9000 Cascade actions \n"
			."    IF $test THEN \n"
			."        UPDATE $tabdst SET $sets WHERE $colsmatch;\n"
         ."        $reset\n"
         .$column_ts
			."    END IF;\n";
	}
	if ($cascade['afterins']=='Y') {
		$this->SpecDDL_TriggerFragment($tabsrc,"INSERT","BEFORE","9800",$s1);
	}
	if ($cascade['afterupd']=='Y') {
		$this->SpecDDL_TriggerFragment($tabsrc,"UPDATE","BEFORE","9800",$s1);
	}
}	


function SpecDDL_Triggers_Pass2($trgs) {
	$this->LogEntry("Writing out CREATE TRIGGER... commands to wrap fragments");
	foreach ($trgs as $trg) {
      $statement = $trg['statement']=='Y' ? true : false;
		// Here is the header, sequence 0000
		$s1 = 
			"CREATE OR REPLACE FUNCTION ".$trg["fname"]."() RETURNS TRIGGER AS #\n".
			"DECLARE\n".
			"    NotifyList text = ####;\n".
			"    ErrorList text = ####;\n".
			"    ErrorCount int = 0;\n".
			"    AnyInt int;\n".
			"    AnyRow RECORD;\n".
			"    AnyChar varchar;\n".
			"BEGIN\n".
         "    SET search_path TO public;\n\n";
		$this->SpecDDL_TriggerFragment(
         $trg["table_id"],$trg["action"],$trg["before_after"]
         ,"0000",$s1,'',$statement
      );
		
		// here is the universal trailer for all
		$ret = "new";
		if ($trg["action"]=="DELETE") { $ret="old"; }
		$s1 = "";
		if ($trg["before_after"]=="BEFORE" && !$statement) {
			$s1 = "\n".
				"    IF ErrorCount > 0 THEN\n". 
				"        RAISE EXCEPTION ##%##,ErrorList;\n".
				"        RETURN null;\n".
				"    ELSE\n".
				"        IF NotifyList <> #### THEN \n".
				"             RAISE NOTICE ##%##,NotifyList;\n".
				"        END IF; \n".
				"        RETURN ".$ret . ";\n".
				"    END IF;\n".
		      "END; # Language plpgsql SECURITY DEFINER\n";
		}
		else {
			$s1 = "\n".
				"    RETURN ".$ret . ";\n".
		      "END; # Language plpgsql SECURITY DEFINER\n";
		}
		$this->SpecDDL_TriggerFragment(
         $trg["table_id"],$trg["action"],$trg["before_after"]
         ,"9999",$s1,'',$statement
      );
	}
}

function SpecDDL_TriggerFragment(
   $table_id,$action,$before_after
   ,$sequence, $code_fragment
   , $column_id="",$statement=false) {
      
   $statement=$statement ? 'Y' : 'N';
	$this->SQL(
		"Insert into zdd.triggers ".
		"(table_id,action,before_after,sequence,statement
        ,code_fragment,column_id) ". 
		" values (".
		"'". $table_id . "',".
		"'". $action . "',". 
		"'". $before_after . "',".
		"'". $sequence . "',".
		"'". $statement. "',".
		"'". $code_fragment . "',".
		"'". $column_id . "')");
}
	
function SpecDDL_Triggers_Assemble($trgs) {
	// Here we take all of the fragments and make unified
	// ns_object definitions out of them
	$this->LogEntry("Assembling trigger fragments into complete triggers");
	foreach($trgs as $trg) {
		$s1 = "";
		//$this->LogEntry("looking for ". $trg["table_id"] . ": ". $trg["action"] . ": ". $trg["before_after"]);
		$results = $this->SQLRead(
			"select code_fragment,sequence as tseq,cast(0 AS INT) as sseq".
			" FROM zdd.triggers ". 
			" WHERE table_id = '". $trg["table_id"] . "' ".
			"   AND action   = '". $trg["action"] . "' ".
			"   AND before_after= '". $trg["before_after"] . "' ".
         "   AND statement= '". $trg['statement'] ."' ".
			"   AND sequence <> '5000'". 
			"UNION ALL ".
			"select t.code_fragment,t.sequence as tseq,s.sequence as sseq".
			" FROM zdd.triggers t". 
			" JOIN zdd.column_seqs s ". 
			"   ON t.table_id = s.table_id ". 
			"  AND t.column_id = s.column_id ". 
			" WHERE t.table_id = '". $trg["table_id"] . "' ".
			"   AND t.action   = '". $trg["action"] . "' ".
			"   AND t.before_after= '". $trg["before_after"] . "' ".
         "   AND statement= '". $trg['statement'] ."' ".
			"   AND t.sequence = '5000'". 
			" ORDER BY 2,3 ");
		while ($row=pg_fetch_array($results)) { $s1.=$row["code_fragment"]; }
		
      $TF="TRGF:".$trg["table_id"] 
         .":".$trg["action"]
         .":".$trg["before_after"]
         .":".$trg["statement"]; 
		$this->SQL(
			"insert into zdd.ns_objects_c ".
			"(object_id,definition,def_short,sql_create,sql_drop,sequence)".
			" values (".
			"'". strtolower($trg["fname"]) . "',". 
			"'$TF',".
			"'$TF',".
			"'". $s1 . "','',1)");

		// For postgreSQL, there is a second command that is 
		// required.  It is a trigger command that invokes the 
      // previously defined function.
      $foreach=$trg['statement']=='Y' ? ' statement ' : ' row ';
		$s1= "create trigger ". $trg["tname"] . " "
			.$trg["before_after"] . " ". $trg["action"]  
			." on ". $trg["table_id"]  
			." for each $foreach "
         ."execute procedure ". $trg["fname"] . "()";
		$trg["fname"] = strtolower($trg["fname"]);
		$trg["action"]= strtolower($trg["action"]);
		$trg["before_after"] = strtolower($trg["before_after"]);
      $TT="trgt:"
         .$trg["table_id"]
         .":".$trg["action"]
         .":".$trg["before_after"]
         .":".$trg["statement"]; 
		$this->SQL(
			"insert into zdd.ns_objects_c ".
			"(object_id,definition,def_short,sql_create,sql_drop,sequence)".
			" values (".
			"'". $trg["tname"] . "',".
         "'$TT',".
			"'$TT',".
			"'". $s1 . "','',2)");
	}
}
		
// ==========================================================
// REGION: Differences
// ==========================================================
function Differences()
{
	$this->LogStage("Finding Differences between specification and current state.");
	//
	//  We are only going to diff three tables, which are
	//  the table of tables, the flat table, and the
	//  table of secondaries.  So we do the same thing for
	//  all three.
	//
	$this->Differences_One(
		"tables", "tables",
		"Table of Tables", 
		"a.table_id = b.table_id","");
	$this->Differences_One(
		"groups", "groups",
		"Table of Groups", 
		"a.group_id = b.group_id");
	$this->Differences_One(
		"tabflat", "tabflat",
		"Table of Columns", 
		"a.table_id = b.table_id and a.column_id = b.column_id");
	$this->Differences_One(
		"ns_objects", "ns_objects", 
		"Non-storage Objects", 
		"a.def_short = b.def_short");
	return true;
}

function Differences_One($strStub, $dst,$strDesc,$strKeys,$strWhere="")
{

	$strList1 = "";
	$this->LogEntry("Generating differences for ". $strStub . ", ". $strDesc);
	foreach ($GLOBALS["utabs"][$strStub]["flat"] as $colname=>$colprops) {
		$strList1.=
			"a.". $colname . ",".
			"b.". $colname . " as \"". $colname . "_r\",";
	}

	$strSQL = 
		"SELECT ". $strList1 .
		"       CAST(' ' as char(1)) as XFate ".
		" INTO zdd.". $dst . "_d ".
		" FROM zdd.". $strStub . "_c a ".
		" FULL JOIN zdd.". $strStub . "_r b ".
		" ON ". $strKeys.
		$strWhere;
	$this->SQL($strSQL);
}

// ==========================================================
// REGION: Specification Validation
// Use the bootstrap information in $utabs to do unique
// and referential checks on the resolved spec
// ==========================================================

function SpecValidate()
{
	$retval = true;
	$this->LogStage("Validating New Specification");
	$failures = 0;
	$this->LogEntry("Looking for uniqueness violations");
	$failures += $this->SpecValidate_Unique();	
	$this->LogEntry("Looking for referential violations");
	$failures += $this->SpecValidate_RI();
	if ($failures>0) { 
		$this->LogEntry("******");
		$this->LogEntry("****** Validation failure, $failures failures, processing cannot continue.");
		$this->LogEntry("******");
		$retval = false;
	}
	return $retval;
}


function SpecValidate_Unique()
{
	$failures = 0;
	foreach ($GLOBALS["utabs"] as $table_id=>$tabinfo) {
		if ($tabinfo["module"]<>"datadict") continue;
		$pk_list = $this->zzArray($tabinfo,"pk");
		if ($pk_list=="") {
			$this->LogEntry("Table ".$table_id." has no primary key! ");
			$failures++;
		}
		else {
			$sql = 
				"SELECT count(*) as _count,$pk_list ". 
				" FROM zdd.". $table_id."_c".
				" GROUP BY ". $pk_list . 
				" HAVING COUNT(*) > 1 ";
			$results = $this->SQLRead($sql);
			while ($row = pg_fetch_row($results)) {
				$vals = "";
				for ($x = 1; $x< count($row); $x++) {
					$vals.=$this->AddComma($vals).trim($row[$x]);
				}
				$this->LogEntry("Table ". $table_id . " has duplicates on columns ". $pk_list);
				$this->LogEntry("    ".$row[0]." occurrences of: ".$vals);
				$failures++;
			}
		}
	}
   //$utabs[$table_id]["indexes"][$idx_name] = array("unique"=>$index["idx_unique"],"cols"=>$cols);
 	foreach ($GLOBALS["utabs"] as $table_id=>$tabinfo) {
		if ($tabinfo["module"]<>"datadict") continue;
      foreach($tabinfo['indexes'] as $indexinfo) {
         if($indexinfo['unique']<>'Y') continue;
         
         $pk_list = $indexinfo['cols'];
         $sql = 
            "SELECT count(*) as _count,$pk_list ". 
            " FROM zdd.". $table_id."_c".
            " GROUP BY ". $pk_list . 
            " HAVING COUNT(*) > 1 ";
         $results = $this->SQLRead($sql);
         while ($row = pg_fetch_row($results)) {
            $vals = "";
            for ($x = 1; $x< count($row); $x++) {
               $vals.=$this->AddComma($vals).trim($row[$x]);
            }
            $this->LogEntry("Table ". $table_id . " has duplicates on columns ". $pk_list);
            $this->LogEntry("    ".$row[0]." occurrences of: ".$vals);
            $failures++;
         }
      }
	}

	return $failures;
}

function SpecValidate_RI() {
	$failures=0;
	$fks = &$GLOBALS["ddarr"]["data"]["table"];

	// Loop through each table looking for each FKEY
	foreach ($GLOBALS["utabs"] as $table_id=>$tabstuff) {
		if (isset($fks[$table_id]["foreign_key"])) {
			foreach ($fks[$table_id]["foreign_key"] as $fkinfo) {
				$table_id_par = $fkinfo["__keystub"];
				$prefix  = $this->zzArray($fkinfo,"prefix");
				$suffix  = $this->zzArray($fkinfo,"suffix");
				
				// explode the primary key of parent and put back together
				$compare = $fkchd = $fkpar = $fkcon = $fkEmp = "";
				$pkarr = explode(",",$GLOBALS["utabs"][$table_id_par]["pk"]);
				foreach ($pkarr as $pkone) {
					$compare .= $this->AddList($compare," AND ").'x.'.$pkone."= ".
						"zdd.".$table_id."_c.".$prefix.$pkone.$suffix;
					$fkchd .= $this->AddComma($fkchd).$prefix.$pkone.$suffix;
					$fkEmp .= $prefix.$pkone.$suffix." <> '' AND ";
					$fkpar .= $this->AddComma($fkpar).$pkone;
				}
				
				// This is so-called 'optional RI' 
				if ($this->zzArray($fkinfo,"allow_empty")!="Y") $fkEmp = "";
			
				$sql = 
					"SELECT ".$fkchd." FROM zdd.".$table_id."_c ".
					" WHERE ".$fkEmp." NOT EXISTS (".
					"  SELECT * FROM zdd.".$table_id_par."_c x WHERE ".$compare.")";
				$results = $this->SQLRead($sql);
				if (!$results) {
					$this->LogEntry($sql);
				}
				if (pg_num_rows($results)>0) {
					$failures++;
					//$this->LogEntry($sql);
					$this->LogEntry("");
					$this->LogEntry("RI Failure $table_id($fkchd) to $table_id_par($fkpar)");
					while ($row = pg_fetch_row($results)) {
						$vals = "";
						for ($x = 0; $x< count($row); $x++) {
							$vals.=$this->AddComma($vals).trim($row[$x]);
						}
						$this->LogEntry("    Values are: ".$vals);
					}
				}
			}
		}
	}
	return $failures;
}

function SpecValidate_Hardcode() {
	$this->LogEntry("Ensuring all tables have at least one UISearch column");
	$results = $this->SQLRead(
		"select table_id from zdd.tabflat_c ".
		" group by table_id ".
		" having sum(case when uisearch='Y' then 1 else 0 end) = 0");

	$retval = true;
	while($row=pg_fetch_array($results)) {
		$retval = false;
		$this->LogEntry("  Error, table ". $row["table_id"]." has no UISEARCH columns");
	}
	return $retval;

	$this->LogEntry("Validating automated columns for RI");
	$results=$this->SQLRead(
		"select table_id,column_id,table_dep,column_dep ".
		" FROM zdd.column_deps ".
		" WHERE automation_id <> 'FK' ".
		"   AND NOT EXISTS ".
		"   (SELECT table_id,column_id FROM zdd.tabflat_c tf ".
		"    WHERE tf.table_id = zdd.column_deps.table_id ".
		"      AND tf.column_id = zdd.column_deps.column_id)");
	while ($row=pg_fetch_array($results)) {
		$retval = false;
			$this->LogEntry(
				"Column ". $row["table_id"].".".$row["column_id"]. 
				" references non-existent column: ".
				$row["table_dep"].".".$row["column_dep"]);
	}
	
	$this->LogEntry("Validating automated columns for circular references");
	$results=$this->SQLRead("select table_id,column_id FROM zdd.column_seqs WHERE sequence = -1");
	while ($row=pg_fetch_array($results)) {
		$retval=false;	
		$this->LogEntry("Column ". $row["table_id"].".".$row["column_id"]." is involved in circular reference.");
	}
	
	$this->LogEntry("Validating FETCH columns have foreign key to parents.");
	$results=$this->SQLRead(
			"select table_id,column_id,table_dep ".
			" FROM zdd.column_deps ".
			" WHERE automation_id in ('FETCH','DISTRIBUTE') ". 
			"   AND NOT EXISTS ".
			"   (SELECT table_id FROM zdd.tabfky_c fk ". 
			"    WHERE fk.table_id     = zdd.column_deps.table_id ".
			"      AND fk.table_id_par = zdd.column_deps.table_dep)");
	while ($row=pg_fetch_array($results)) {
		$retval=false;	
		$this->LogEntry(
			"Column ". $row["table_id"].".".$row["column_id"]. 
			" FETCHes from non-parent table: ".$row["table_dep"]);
	}

	$this->LogEntry("Validating SUM/COUNT columns have foreign key to children.");
	$results=$this->SQLRead(
			"select table_id,column_id,table_dep ".
			" FROM zdd.column_deps ".
			" WHERE automation_id IN ('SUM','COUNT') ". 
			"   AND NOT EXISTS ".
			"   (SELECT table_id FROM zdd.tabfky_c fk ". 
			"    WHERE fk.table_id     = zdd.column_deps.table_dep ".
			"      AND fk.table_id_par = zdd.column_deps.table_id)");
	while ($row=pg_fetch_array($results)) {
		$retval=false;	
		$this->LogEntry(
			"Column ". $row["table_id"].".".$row["column_id"].
			" aggregates from non-child table: ".$row["table_dep"]);
	}

	// Pulling a sequence from a table/column that is not sequenced!
	$this->LogEntry("Ensuring out-of-table SEQUENCEs come from SEQUENCE'd tables");
	$results=$this->SQLRead(
			"		select table_id,column_id,auto_formula from zdd.tabflat_c ". 
			" where automation_id IN ('SEQUENCE','SEQDEFAULT') ".
			"   AND auto_formula <> '' ".
			"   AND not exists ( ".
			"       SELECT column_id FROM zdd.tabflat_c x ". 
			"        WHERE x.table_id = zdd.tabflat_c.auto_formula ".
			"          AND x.column_id= zdd.tabflat_c.column_id ".
			"          AND x.automation_id IN ('SEQUENCE','SEQDEFAULT')) ");
	while ($row=pg_fetch_array($results)) {
		$retval=false;	
		$this->LogEntry(
			"Column ". $row["table_id"].".".$row["column_id"].
			" SEQUENCEs from non-sequenced column: ".$row["auto_formula"].".".$row["column_id"]);
	}
	return $retval;
}



// ==========================================================
// REGION: Analyze
// Most of the real mental effort behind this system comes
// across as the fact that the entire analysis step is just
// a few queries, against only three tables.  Anything that
// causes this section to blow up should be looked upon with
// extreme suspicion, such an action, like the ring of power,
// will likely betray you to your death.
// ==========================================================
function Analyze()
{
	$this->LogStage("Determine Differences");

	$this->LogEntry("Examining for new and altered columns");
   $sql ="UPDATE zdd.tabflat_d SET XFate = "
      ." CASE WHEN column_id_r IS NULL THEN 'N' "
      ."      WHEN formula <> formula_r THEN 'U' "
      ."      ELSE '' END";
	$this->SQL($sql);

	$this->LogEntry("Examining for new tables and altered tables");
	$this->SQL("UPDATE zdd.tables_d SET XFate = CASE WHEN zdd.tables_d.table_id_r IS NULL THEN 'N' ELSE '' END");
	$this->SQL( 
		"UPDATE zdd.tables_d SET XFate = 'U' ".
		"  FROM zdd.tabflat_d f ".
		"  WHERE f.table_id = zdd.tables_d.table_id ".
		"    AND zdd.tables_d.XFate = '' ".
		"    AND f.XFate IN('N','U')");

	$this->LogEntry("Analyzing Non-storage Objects");
	$this->SQL( 
		"UPDATE zdd.ns_objects_d SET XFate = ".
		"CASE WHEN object_id_r IS NULL THEN 'N' ". 
		"     WHEN object_id   IS NULL THEN 'D' ". 
		"     WHEN definition <> definition_r THEN 'U' ".
		"     ELSE '' END ");

	return true;
}
		
// ==========================================================
// REGION: Plan Make.   
// ==========================================================

function PlanMake()
{
	$this->LogStage("Building DDL Plan");
	// Table commands:
	// 3000: Create table commands
	// 3010: Alter table commands (add columns)
	//
	// 4000: NSO Drop commands
	//
	// 5010: Backfill and data commands.  Put here because 
	//          triggers that might interfere do not exist
	//       ***  Triggers cannot be disabled in Postgres ***
   //           WRONG!  alter table...disable trigger all!
	// 5020: DDR Reconciliation commands.  Put here for same
	//       reason as backfill commands.
	//
	// 6000: All NSO Create commands
   // 6050: Sequence update commands
	//
	// 9000: Group create commands
	// 9100: create 
	//

	//'  The basic outline is to remove things that would get
	//'  in the way of table changes, then do the table changes,
	//'  then layer back up the triggers, views, etc.
	$this->PlanMake_Tables();
   $this->PlanMake_Views();
	$this->PlanMake_Build_NSO();
	$this->PlanMake_Security();
   
   // 11/27/06, fix all sequences so they are always safe
   $res=$this->SQLRead("Select table_id,column_id FROM zdd.tabflat_c 
         Where automation_id in ('SEQUENCE','SEQDEFAULT')");
   while ($row=pg_fetch_array($res)) {
      $tid=$row['table_id'];
      $cid=$row['column_id'];
      $seq=$tid."_".$cid;
      $sq="SELECT SETVAL(#$seq#,(SELECT MAX($cid) FROM $tid)+1)";
      $this->PlanMakeEntry("6050",$sq);
   }
   


	//$this->PlanMake_DDR();  //--this routine still exists
	return true;
}

function PlanMake_Tables()
{

	$this->LogEntry("Generating Table Create and Alter Commands");
	$this->LogEntry("Retrieving list of tables");
	$results = $this->SQLRead("select table_id from zdd.tables_d where XFate ='N'");
	$TablesNew = pg_fetch_all($results);
	$results = $this->SQLRead("select table_id from zdd.tables_d where XFate ='U'");
	$TablesUpd = pg_fetch_all($results);

	if ($TablesNew) { foreach ($TablesNew as $tab) { $this->PlanMake_TablesNew($tab["table_id"]); }}
	if ($TablesUpd) { foreach ($TablesUpd as $tab) { $this->PlanMake_TablesUpd($tab["table_id"]); }}
}

function PlanMake_TablesNew($table_id)
{
	$cols="";
	$results = $this->SQLRead( 
		"Select column_id,formula ".
		" from zdd.tabflat_c ".
		" where table_id = '". $table_id . "'");
	while ($row = pg_fetch_array($results)) { 
		$cols .= $this->AddList($cols,",").$row["column_id"]." ".$row["formula"];
	}
	
	//$this->LogEntry("Storing create table command  : ". $table_id);
	$this->PlanMakeEntry("3000","CREATE TABLE ". $table_id . "(". $cols . ")");
}

function PlanMake_TablesUpd($table_id)
{
	$newcols = array();
	$this->LogEntry("Getting new columns in table: ". $table_id);
	$results = $this->SQLRead( 
		"Select xfate,column_id,formula,formula_r ".
		" from zdd.tabflat_d ".
		" WHERE table_id = '". $table_id . "'". 
		"   AND XFate IN ('N','U') ");
   $changes = array();
	while ($row = pg_fetch_array($results)) {
      if($row['xfate']=='N') {
         $changes[] = " ADD ".$row["column_id"]." ".$row["formula"];
         $newcols[]=$row["column_id"];
      }
      else {
         $changes[] 
            ="ALTER COLUMN ".$row["column_id"]
            ." TYPE ".$row["formula"];
         $this->LogEntry("Altering column ".$row['column_id'].' to '
            .$row['formula'].' from '.$row['formula_r']);
      }
		
	}
   $changes = implode(",",$changes);
   $SQL = "ALTER TABLE $table_id ".$changes;
   $this->PlanMakeEntry("3010",$SQL);

	// Now we will build a single table update command for all of
   // these new columns.
	//
   if (false) {
	//if (count($newcols)>0) {
		$SQL = "";
		while (list($index,$column_id) = each($newcols)) {
			//$this->LogEntry("  Adding to table $table_id new column $column_id");
			$aid = trim(strtolower($GLOBALS["utabs"][$table_id]["flat"][$column_id]["automation_id"]));
			//$this->LogEntry("  Automation id is: $aid");
			switch ($aid) {
				case "sequence":
				case "seqdefault":
					$newval = "nextval(#$table_id"."_$column_id#)";
					break;
				case "default": 
					$type_id = trim(strtolower($GLOBALS["utabs"][$table_id]["flat"][$column_id]["type_id"]));
					$auto_f  = trim($GLOBALS["utabs"][$table_id]["flat"][$column_id]["auto_formula"]);
					//$this->LogEntry("Type is $type_id and formula is $auto_f");
					$newval = $this->SQLFORMATLITERAL($auto_f,$type_id,true);
					break;
				default: 
					$newval = $this->DBB_SQLBlank($GLOBALS["utabs"][$table_id]["flat"][$column_id]["formshort"]);
			}
			$SQL.=$this->AddComma($SQL)." $column_id = $newval";
		}
		$this->PlanMakeEntry("5010","UPDATE $table_id SET $SQL");
	}
}
	

function PlanMake_Views() {
   global $parm;
   $app=$parm['APP'];
   global $ufks;


 	$this->LogEntry("Generating unconditional DROP VIEW commands.");
   $res=$this->SQLRead(
   	" SELECT table_name as table_id ".
		" FROM information_schema.tables ". 
		" WHERE table_schema = 'public'".
		"   AND table_type = 'VIEW'"
   );
   while ($row=pg_fetch_array($res)) {
      $this->PlanMakeEntry("2900","DROP VIEW ".$row['table_id']);
   }


 	$this->LogEntry("Generating View Create Commands");

   // Pull the list of views out of perm_tabs.
   $res=$this->SQLRead(
      "SELECT pt.table_id,pt.group_id,g.grouplist 
         FROM zdd.perm_tabs_c  pt
         JOIN zdd.groups_c     g  ON pt.group_id=g.group_id
        WHERE pt.istable='V'"
   );
   $rows=pg_fetch_all($res);
   if(!$rows) $rows=array();
   foreach($rows as $row) {
      // Get some shorthand vars for the table
      $tab=$row['table_id'];
      $grp=$row['group_id'];
      $gno=substr($grp,strlen($app)+5);
      $vwn=$tab."_".$gno;
      
      // now fetch the list of rows, and build relevant arrays
      $cols=array();
      $colsu=array();
      $res=$this->SQLRead(
         "SELECT cx.column_id,cx.permupd
            FROM zdd.perm_colsx_c  cx 
            JOIN zdd.tabflat_c     cf 
                  ON cx.table_id = cf.table_id
                 AND cx.column_id= cf.column_id
           WHERE cx.group_id = '$grp'
             AND cx.table_id = '$tab'
             AND cx.permsel = 1
           ORDER BY cf.uicolseq"
      );
      while($rowc=pg_fetch_array($res)) {
         $cols[$rowc['column_id']]=$rowc['permupd'];
         if($rowc['permupd']==1) {
            $colsu[]=$rowc['column_id'];
         }
      }
      
      // now select the list of columns that have id security
      // First determine if any group (except the login grou) has
      // unfiltered access.  If there is at least one, we do no
      // filters and skip all of this. 
      //
      // ...otherwise we must build filters for columns and fks
      // 
      $gl="'".str_replace('+',"','",$row['grouplist'])."'";
      $gl2=substr($gl,strlen($app)+3);  // skip login group in this list
      $agroups = array();
      $sql="
select count(*) as cnt FROM (
   SELECT group_id 
    FROM zdd.groups_c 
   WHERE group_id IN ($gl2)
     AND NOT EXISTS (
         SELECT column_id 
           FROM zdd.perm_cols_c   
          WHERE table_id  = '$tab'
            AND (   COALESCE(permrow,'N')    = 'Y'
                 OR COALESCE(table_id_row,'')<>'')
            --AND group_id in ($gl2)
            AND group_id = zdd.groups_c.group_id
         )
) x";
      $res=$this->SQLRead($sql);
      $gclear=pg_fetch_array($res);
      if($gclear['cnt']>0) {
         // At least one unfiltered group, cancel all filters
         $SWhere='';
      }
      else {
        // No unfiltered groups, must do all filters
        $agroups=array();
        
        // Pull out straight columns
         $sq=
"select DISTINCT column_id
   FROM zdd.perm_cols_c    
  WHERE table_id  = '$tab'
    AND permrow   = 'Y'
    AND group_id  IN ($gl2)";
         $res=$this->SQLRead($sq);
         while($rowrc=pg_fetch_array($res)) {
            $agroups[] = $rowrc['column_id'].' = current_user';
         }

         // Pull out columns that point to xrefs
         $sq=
"select DISTINCT column_id,table_id_row
   FROM zdd.perm_cols_c    
  WHERE table_id  = '$tab'
    AND COALESCE(table_id_row,'')<>''
    AND group_id  IN ($gl2)";
         $res=$this->SQLRead($sq);
         if(!$res) $this->LogEntry($sq);
         while($rowrc=pg_fetch_array($res)) {
            $rccol = $rowrc['column_id'];
            $rctab = $rowrc['table_id_row'];
            $x="EXISTS (SELECT skey FROM $rctab 
                         WHERE user_id = current_user
                           AND $rccol = $tab.$rccol)
               ";
            $agroups[] = $x;
         }

         $SWhere=" WHERE ".implode(' OR ',$agroups);
      }
      
      // Now generate the create view command
      if(count($cols)==0) $cols=array('skey'=>0); 
      $colsname=array_keys($cols);
      $sq="CREATE VIEW $vwn AS 
           SELECT $tab.".implode(",$tab.",$colsname)." 
             FROM $tab $SWhere";
      //print_r($sq);
      $this->PlanMakeEntry("3050",$sq);
           
      // Now generate the delete rule.  Two actually, the
      // unconditional and the specific
      $this->PlanMakeEntry("3060",
         "CREATE OR REPLACE RULE $vwn"."_delete1 AS
              ON DELETE TO $vwn
              DO INSTEAD NOTHING"
      );
      $this->PlanMakeEntry("3060",
         "CREATE OR REPLACE RULE $vwn"."_delete2 AS
              ON DELETE TO $vwn
              DO INSTEAD
              DELETE FROM $tab WHERE skey = old.skey"
      );
     
      // The insert rule.  First the unconditional, then the specific
      $this->PlanMakeEntry("3060",
         "CREATE OR REPLACE RULE $vwn"."_insert1 AS
              ON INSERT TO $vwn
              DO INSTEAD NOTHING"
      );
      if(count($colsu)>0) {
         $list1=implode(',',$colsu);
         $list2='new.'.implode(',new.',$colsu);
         $this->PlanMakeEntry("3060",
            "CREATE OR REPLACE RULE $vwn"."_insert2 AS
                 ON INSERT TO $vwn
                 DO INSTEAD INSERT INTO $tab ($list1) VALUES ($list2)"
         );
      }
      
      
      // ...and finally, the update rule
      $this->PlanMakeEntry("3060",
         "CREATE OR REPLACE RULE $vwn"."_update1 AS
              ON UPDATE TO $vwn
              DO INSTEAD NOTHING"
      );
      if(count($colsu)>0) {
         $aupd=array();
         foreach($colsu as $colu) {
            $aupd[]="$colu = new.$colu";
         }
         $updlist = implode(',',$aupd);
         $this->PlanMakeEntry("3060",
            "CREATE OR REPLACE RULE $vwn"."_update2 AS
                 ON UPDATE TO $vwn
                 DO INSTEAD UPDATE $tab SET $updlist
                 WHERE skey = new.skey"
                 );
      }
   }
}

function PlanMake_Build_NSO()
{
   // Build all non-storage objects.  There is only one dependency
   // to worry about, and it is only because of PostgreSQL, which must
   // define triggers in two stages.  We make sure the trigger functions
   // are created first before everything so that they exist when the
   // trigger commands run that link the functions to table events.
	//
	$this->LogEntry("Planning commands for new and updated NSOs.");
	$results = $this->SQLRead(
		"select def_short,sql_create,sql_drop_r,xfate ".
		" from zdd.ns_objects_d ".
		" where xfate IN ('N','U','D')".
      //"  order by case when definition like 'TRGF:%' then 0 else 1 end ");
      " order by coalesce(sequence,0)");
	while ($row=pg_fetch_array($results)) {
		if ($row["xfate"]=="D" || $row["xfate"]=="U") {
			$this->PlanMakeEntry("4000",$row["sql_drop_r"]);
		}
      if ($row["xfate"]=="U" || $row["xfate"]=="N") {
         // Need sequences to be created before content is loaded
         $seq = substr($row['def_short'],0,9)=='sequence:'
	         ? "5010" : "6000";
         $this->PlanMakeEntry($seq,$row["sql_create"]);
      }
	}
}

function PlanMake_DDR() {
	$this->LogStage("Generating DDR update commands");
   $this->LogEntry(" -- PLACEHOLDER, no action performed.");
   return true;
	
	foreach ($GLOBALS["ddarr"]["data"]["table"] as $OneTab=>$table) {
		$List1 = "";
		$List1a= "";
		$List2 = "";
		$StrCmd = 
			"Select column_id,primary_key ". 
			" FROM zdd.tabflat_c ".
			" WHERE table_id = '". $OneTab . "'";
		$results = $this->SQLRead($StrCmd);
		while ($row=pg_fetch_array($results)) {
			$cid = $row["column_id"];
			$List1 .= $this->AddList($List1,",").$cid;
			if ($cid=="skey") {	$List1a.= $this->AddList($List1a,",")."nextval(#".$OneTab."_skey#)"; }
			else { $List1a.= $this->AddList($List1a,",").$cid; }

			if ($row["primary_key"]=="Y") {
				$List2 .= $this->AddList($List2,",") .$cid." = a.". $cid;
			}
		}

		$s1 = '
INSERT INTO public.'. $OneTab .'
 ('. $List1 . ') 
 SELECT '. $List1a . ' FROM zdd.'. $OneTab . '_c a
 WHERE a.skey_s = 0 OR a.skey_s IS NULL';
		$this->PlanMakeEntry("5020",$s1);

	}
}

// Generate all grant and deny commands.  Only two hard-coded
// actions at this time:
function PlanMake_Security() {
	global $parm;
   $app=$parm['APP'];

   // See if eponymous g-role was created yet
   $results = $this->SQLREad(
      "SELECT count(*) from pg_roles where rolname='$app'"
   );
   $row=pg_fetch_array($results);
   if($row[0]==0) {
      $this->PlanMakeEntry("9000","CREATE ROLE $app LOGIN NOCREATEROLE PASSWORD #$app#");
   }
   else {
      $this->PlanMakeEntry("9000","ALTER ROLE $app LOGIN NOCREATEROLE PASSWORD #$app#");
   }
   
   // This hardcoded entry allows some stuff at login
   $results = $this->SQLREad("GRANT ALL  ON SCHEMA zdd TO $app");
   $results = $this->SQLREad("GRANT SELECT ON TABLE  zdd.perm_tabs_c TO $app");
   $results = $this->SQLREad("GRANT SELECT ON TABLE  zdd.groups_c    TO $app");

   
   // 10/20/06, converted all group creation into user creation
   //           convert existing groups to users
	$this->LogEntry("Creating any groups that don't exist");
	$results = $this->SQLRead(
      "SELECT group_id,group_id_r,permrole FROM zdd.groups_d 
        WHERE group_id <> '$app'"
   );
	while ($row = pg_fetch_array($results)) {
      $gname = $row[0];
      $cr=($row['permrole']=='Y' ? '' : 'NO').'CREATEROLE';
      //$gname = $gname==$parm['APP'] ? $gname : $parm['APP'].'_'.$gname;
      if(is_null($row['group_id_r'])) {
         $this->LogEntry("Creating g-role: $gname");
         $this->PlanMakeEntry("9000","CREATE ROLE $gname $cr LOGIN PASSWORD #$gname#");
         $this->PlanMakeEntry("9000","GRANT $app TO $gname");
      }
      else {
         $this->LogEntry("Altering g-role: $gname");
         $this->PlanMakeEntry("9000","ALTER ROLE $gname $cr LOGIN PASSWORD #$gname#");
         $this->PlanMakeEntry("9000","GRANT $app TO $gname");
      }
	}
	
	$this->LogEntry("Generating table permissions commands");
	
	$results = $this->SQLRead("
SELECT group_id,table_id,permins,permupd,permdel,permsel,istable,COALESCE(permspec,'N') as permspec
  FROM zdd.perm_tabs_c"
   );
	while ($row = pg_fetch_array($results)) {
      $tab=$row['table_id']; 
      $grp=$row['group_id'];
      if($row['permspec']=='Y') {
         $this->PlanMakeEntry("9100","REVOKE ALL ON $tab FROM GROUP $grp"); 
      } 
      elseif($row['istable']<>'M') {
         //  Rename table in terms of the effective group
         if($row['istable']=='V') {
            $tab.='_'.substr($grp,strlen($app)+5);
         }
         
         $GRANT="";
         if ($row["permins"]=="Y") { $GRANT.=$this->AddComma($GRANT)."INSERT"; }
         if ($row["permupd"]=="Y") { $GRANT.=$this->AddComma($GRANT)."UPDATE"; }
         if ($row["permdel"]=="Y") { $GRANT.=$this->AddComma($GRANT)."DELETE"; }
         if ($row["permsel"]=="Y") { $GRANT.=$this->AddComma($GRANT)."SELECT"; }
         if ($GRANT!="") { 
            $this->PlanMakeEntry("9100","GRANT $GRANT on $tab TO GROUP $grp"); 
         }
         
         $GRANT="";
         if ($row["permins"]=="N") { $GRANT.=$this->AddComma($GRANT)."INSERT"; }
         if ($row["permupd"]=="N") { $GRANT.=$this->AddComma($GRANT)."UPDATE"; }
         if ($row["permdel"]=="N") { $GRANT.=$this->AddComma($GRANT)."DELETE"; }
         if ($row["permsel"]=="N") { $GRANT.=$this->AddComma($GRANT)."SELECT"; }
         if ($GRANT!="") { 
            $this->PlanMakeEntry("9100","REVOKE $GRANT on $tab FROM GROUP $grp");
         }
      }
	}

	//  This is for sequences, we may need them later
	$results = $this->SQLRead("select def_short FROM zdd.ns_objects_c where def_short like 'sequence:%'");
	while ($row = pg_fetch_array($results)) {
		$seq_arr = explode(":",$row["def_short"]);
		$this->PlanMakeEntry("9110","grant all on ".$seq_arr[1]." to group ".$parm["APP"]); 
	}
}

function PlanMakeEntry($cmdseq,$cmdtext)
{
	$this->SQL(
		"Insert into zdd.ddl (cmdseq,cmdsql) ". 
		"values (".
		"'". $cmdseq . "',". 
		"'". $cmdtext . "')");
}

// ==========================================================
// REGION: Plan Execution
// ==========================================================
function PlanExecute() {
	$this->LogStage("Executing Plan");
	global $parm,$sqlCommandError;

	$FILEOUT=fopen($parm["DIR_TMP"].$parm["APP"].".plan","w");
	$results = $this->SQLRead("SELECT oid,cmdsql,cmdseq FROM zdd.ddl order by cmdseq");
	while ($row=pg_fetch_array($results)) {
		$TheCmd=$row["cmdsql"];
		$TheOID=$row["oid"];
		
		// This is aimed at triggers.  Triggers in PG are enclosed
		// in strings, requiring strings inside of them to be
		// escaped.  Then we come along and build those strings of
		// strings, making it worse.  So we just use # sign in the
		// build stage, and switch it here, just before executing.
		$TheCmd=str_replace("#","'",$TheCmd);
		fputs($FILEOUT,"=============================\n");
      fputs($FILEOUT,"Currently executing command: \n");
		fputs($FILEOUT,"-----------------------------\n");
		fputs($FILEOUT,$TheCmd . "\n\n");
      fclose($FILEOUT);

		if ($this->SQL($TheCmd,false,false))	{	$TheRes = "'Y'";	$cmdErr = "''"; }
		else { $TheRes = "'N'"; $cmdErr = $sqlCommandError; }

		$this->SQL( 
			"Update zdd.ddl set executed_ok = ". $TheRes . ", ".
			"       cmderr = '". $cmdErr . "' ".
			"Where oid = ". $TheOID);
	
      $FILEOUT=fopen($parm["DIR_TMP"].$parm["APP"].".plan","a");
		fputs($FILEOUT,"-----------------------------\n");
		fputs($FILEOUT,"SUCCESS: ". $TheRes." ".date('r')."\n");
		fputs($FILEOUT,"=============================\n");
	}
	fclose($FILEOUT);
	return true;
}


// ==========================================================
// REGION: ContentLoad
// Load group information into the node manager tables
// ==========================================================
function ContentLoad() {
	$this->LogStage("Loading data to user tables");
	$this->DBB_LoadContent(false,$GLOBALS["content"],"","");
	return true;
}

// ==========================================================
// REGION: BootstrapNodeManager
// 10/6/06.  Most of this stuff was in the routine for
//           setting up security.  Moved it into its own
//           area here.
//   ** NOT ACTUALLY CALLED ** Everything here should be
//           be taken care of by the installer
// ==========================================================
function BootstrapNodeManager() {
   global $parm;
	// Make new connection to server, node manager
	$this->LogEntry("Making connection to node manager database");
	$cnx = 
		"dbname=andro".
		" user=".$parm["UID"].
		" password=".$GLOBALS["x_password"];
   global $dbconna;
   $dbconna=pg_connect($cnx);  // allows use of SQL_ANDRO routine

   // Bootstrap test:  create webpath if not there
   $webpath=$GLOBALS['parm']['DIR_PUBLIC'];
   $dbres = pg_query($dbconna
      ,"select count(*) as cnt from webpaths 
         where dir_pub ='".$GLOBALS['parm']['DIR_PUBLIC']."'"
   );
	$row = pg_fetch_array($dbres);
   if($row['cnt']==0) {
      $this->SQL_Andro(
         "insert into webpaths (webpath,dir_pub) 
          values ('DEFAULT', '$webpath')"
      );
   }
   else {
      $dbres = pg_query($dbconna
         ,"select webpath from webpaths 
            where dir_pub ='".$GLOBALS['parm']['DIR_PUBLIC']."'"
      );
      $row=pg_fetch_Array($dbres);
      $webpath=$row['webpath'];
   }
   
 	// Bootstrap test: create this app if it is not there
   $dbres = pg_query($dbconna
      ,"select count(*) as cnt from applications 
         where application ='".$parm['APP']."'"
   );
	$row = pg_fetch_array($dbres);
	if($row['cnt']==0) {
	   $this->LogEntry("Bootstrap build detected, adding app to Node Manager");
      $this->SQL_Andro(
         "insert into applications (application,appspec,webpath,description) "
	     ." values ('".$parm['APP']."','".$parm['APP']."','DEFAULT','Andromeda Node Manager')"
      );
	}
	pg_close($dbconna);
}


// ==========================================================
// REGION: Security Node Manager
// Load group information into the node manager tables
// ==========================================================
function SecurityNodeManager() {
	// See-Also: SpecDDL_Triggers_Security().  Those triggers keep
   //           security synchronized at run-time
   
	// In all events, put group defs into node manager
	$this->SecurityNodeManager_Normal();
	
	// If we are working on the node manager itself, rebuild the list
	// of actual users and groups
	global $parm;
	if ($parm["APP"]=="andro") {
		$this->SecurityNodeManager_Andro();	
	}
	return true;
}

// KFD 10/6/06.  Users are now in all databases, do this for all
function SecurityNodeManager_Normal() {
	$this->LogStage("Reconciling User/Group system with DB Server");
	global $parm;
   
	// Now sweep out the cross-ref groups to apps for this app and rebuild it
	//$this->LogEntry("Sweeping out apps-groups x-ref for this app in node manager");
   // KFD 10/5/06, remove all ref's a*ppsxgroups
   //$this->SQL_Andro("Delete from a*ppsxgroups where application = '".$parm["APP"]."'");
	
	// Get list of groups, add each one that does not exist.
   // KFD 10/6/06, significantly reworked
   $this->LogEntry("Copying group definitions into security area.");
	$this->SQL(
      "insert into groups (group_id,description) 
       Select group_id,description FROM zdd.groups_c
        WHERE group_id NOT IN (
              SELECT group_id from groups
              )"
   );

   // Get the users who are authorized to connect to this database.  Must
   //   disable triggers so it does not try to create the users.   
   $this->SQL("ALTER TABLE users DISABLE TRIGGER ALL");
   $this->SQL("ALTER TABLE usersxgroups DISABLE TRIGGER ALL");

	$this->LogEntry("Adding missing users to users table");
	$this->SQL("
      INSERT INTO users (user_id,skey,user_disabled) 
      select usename
            ,nextval('users_skey')
            ,'N'
        FROM pg_shadow s 
        JOIN pg_group g ON s.usesysid = ANY(g.grolist) 
       where g.groname='".$parm['APP']."'
         AND usename <> 'anonymous'
         and usename NOT IN (select cast(user_id as name) from users)
         AND usename NOT IN (
             SELECT usename FROM pg_shadow S2 
               JOIN pg_group g2
                 ON s2.usesysid = ANY(g2.grolist)
              WHERE g2.groname = 'root'
             )"
    );
    

	$this->LogEntry("Adding missing group assignments");
	$this->SQL("
      INSERT INTO usersxgroups (user_id,group_id,skey) 
      SELECT s.usename,g.groname,nextval('usersxgroups_skey')
        FROM pg_shadow s 
        JOIN pg_group  g ON s.usesysid = ANY(g.grolist)
       WHERE s.usename IN (
             SELECT cast(user_id as name)  FROM users
             )
         AND g.groname IN (
             SELECT cast(group_id as name) FROM groups
             )
         AND NOT exists (
             SELECT user_id,group_id FROM usersxgroups
              WHERE CAST(user_id  as name) = s.usename
                AND CAST(group_id as name)= g.groname
             )"
    );



    // Now delete all groups  
    //$this->LogEntry("Deleting stray users from node manager user table");
	 //$this->SQL("delete from users where cast(user_id as name) not in (select usename from pg_shadow)");
    //$this->SQL("ALTER TABLE users ENABLE TRIGGER ALL");
    // Now delete xref entries for non-existent groups
    // now delete non-existent users


   // Now turn everything back on
   $this->SQL("ALTER TABLE users ENABLE TRIGGER ALL");
   $this->SQL("ALTER TABLE usersxgroups ENABLE TRIGGER ALL");

   return true;
}

// KFD 10/6/06, now only superusers
function SecurityNodeManager_Andro() {
	$this->LogStage("Special Node Manager Operation: Superusers");

	$this->LogEntry("Adding all members of root to superusers table");
	$this->SQL("
      INSERT INTO usersroot (user_id) 
      select usename
        FROM pg_shadow s 
        JOIN pg_group g ON s.usesysid = ANY(g.grolist) 
       where g.groname='root'
         AND usename <> 'anonymous'
         and usename NOT IN (select cast(user_id as name) from usersroot)"
   );

	$this->LogEntry("Deleting all non-members of root from superusers table");
	$this->SQL("
      DELETE FROM usersroot 
       WHERE user_id NOT IN ( 
             SELECT rolname FROM pg_roles WHERE rolsuper= true
             )"
   );
    
   return true;
   
   /*
	$this->LogEntry("Adding missing users to node manager user table");
	$this->SQL(
		"insert into users (user_id,user_name)". 
		" select usename,'unknown' from pg_shadow". 
		"  where usename not in (select cast(user_id as name) from users)");
	$this->LogEntry("Deleting stray users from node manager user table");
	$this->SQL("delete from users where cast(user_id as name) not in (select usename from pg_shadow)");
		
	$this->LogEntry("Adding missing groups to node manager node groups list");
	$this->SQL("	insert into nodegroups (nodegroup,description) ".
		"select groname,'unknown' from pg_group ".
		" where groname not in (select cast(nodegroup as name) from nodegroups)"); 
	$this->LogEntry("Deleting stray groups from node manager node groups list");
	$this->SQL("delete from nodegroups where cast(nodegroup as name) not in (Select groname from pg_group)");
	
	// Now report on and add User/Group Membership changes
	$this->LogEntry("Adding missing user/group combinations to node manager tables");
	$this->SQL(
		"insert into usersxgroups (nodegroup,user_id) ".
		"select g.groname,u.usename ".
		"  FROM pg_group g ".
		"  JOIN pg_shadow u ON u.usesysid = ANY(g.grolist) ".
		" where not exists ". 
		"       (select nodegroup,user_id ".
      "          FROM usersxgroups x ".
      "         WHERE cast(x.nodegroup as name) = g.groname ". 
      "           AND cast(x.user_id   as name) = u.usename) ");

	$this->LogEntry("Deleting invalid user/group combinations to node manager tables");
	$this->SQL(
		"delete FROM usersxgroups ". 
		" WHERE NOT EXISTS ( ".
		"       select g.groname,u.usename ".
		"         FROM pg_group g ".
		"         JOIN pg_shadow u ON u.usesysid = ANY(g.grolist) ".
		"        WHERE cast(usersxgroups.nodegroup as name) = g.groname ". 
		"          AND cast(usersxgroups.user_id   as name) = u.usename)");
   */
}

// ==========================================================
// REGION: Build Utilities
// ==========================================================
function DBB_SequenceName($table_id,$autoform,$column_id) {
	$autoform = is_null($autoform) ? '' : $autoform;
	$table_id = ($autoform=='') ? $table_id : $autoform;
	return 
		strtoupper(trim($table_id))."_".
		strtoupper(trim($column_id));
}
		
function DBB_RunOut($tb, $sf) {
	$defColWidth=$GLOBALS["defcolwidth"];
   
	// This giant bit of code runs out a single table 
	// pulling column definitions from all possible sources,
	// such as tabcol, tabflat
   //
	$tb = strtolower(trim($tb));

	// The second pull brings in the foreign key stuff
	$SQL = 
		"Insert into zdd.tabflat". $sf .
		"(table_id,column_id,description,table_id_src,column_id_src,".
		" auto_table_id,auto_column_id,".
		" table_id_fko,column_id_fko,".
      " range_to,range_from,".
		" suffix,prefix,prevent_fk_change,".
		" dispsize,uiwithnext,uino,".
		" uicolseq,primary_key,uisearch,".
		" automation_id,auto_formula,". 
		" colprec,colscale,colres,type_id,formula,formshort,".
		" uiinline,".
		" required) ".
		"Select fk.table_id, ".
		"       rtrim(fk.prefix) || rtrim(f.column_id) || rtrim(fk.suffix),".
		"       rtrim(f.description) || ".
		"           CASE WHEN fk.description <> '' THEN ' (' || rtrim(fk.description) || ')'".
		"                ELSE '' END as description, ".
		"       f.table_id,f.column_id,".
		"       '' as auto_table_id,'',".
		"       f.table_id,f.column_id,".
      "       '','',".
		"       fk.suffix,fk.prefix,".
		"       COALESCE(fk.prevent_fk_change,'N') as prevent_fk_change,".
		"       f.dispsize,f.uiwithnext,f.uino,".
		"       RTRIM(fk.uicolseq) || '.' || f.uicolseq, ".
		"       fk.primary_key,fk.uisearch, ".
		"       'NONE' as automation_id,'' as auto_formula, ".
		"       f.colprec,f.colscale,f.colres,".
		"       CASE WHEN f.column_id = 'skey' THEN 'I'   else f.type_id END as \"type_id\", ".
		"       CASE WHEN f.column_id = 'skey' THEN 'INT' else f.formula END as \"formula\", ".
		"       f.formshort,".
		"       '',".
		"       f.required".
		"  FROM zdd.tabfky".$sf. " fk ".
		"  JOIN zdd.tabflat".$sf. " f ON fk.table_id_par = f.table_id ".
		" WHERE fk.table_id = '". $tb . "'".
		"   AND f.primary_key = 'Y'".
		"   AND fk.nocolumns <> 'Y'".
      "   AND f.range_from = ''";
	$this->SQL($SQL);
   
   
	$SQL = 
		"Insert into zdd.tabflat". $sf .
		"(table_id,column_id,description,table_id_src,column_id_src,".
		" auto_table_id,auto_column_id,".
		" table_id_fko,column_id_fko,".
      " range_to,range_from, ".
		" suffix,prefix,auto_suffix,auto_prefix,prevent_fk_change,".
		" dispsize,uiwithnext,uino,".
		" uicolseq,primary_key,uisearch,".
		" automation_id,auto_formula,". 
		" colprec,colscale,colres,type_id,formula,formshort,".
		" uiinline,".
		" required) ".
		"Select tc.table_id,".
		"       tc.column_id,".
		$this->DBB_RunOutOverride("description","tc","c").",".
		"       tc.table_id,tc.column_id_src,".
		"       tc.auto_table_id,tc.auto_column_id,".		
		"       '','',".
      "       tc.range_to,tc.range_from,".
		"       suffix,prefix,auto_suffix,auto_prefix,'',". 
		"       t.dispsize,".
		"       CASE WHEN tc.uiwithnext<>'' THEN tc.uiwithnext ".
		"            WHEN c.uiwithnext<>''  THEN c.uiwithnext ELSE t.uiwithnext END,".
		"       tc.uino,".
		"       tc.uicolseq,tc.primary_key,tc.uisearch,".
		"       CASE WHEN tc.automation_id <> '' ".
		"            THEN tc.automation_id ".
		"            ELSE c.automation_ID END as automation_id, ".
		"       CASE WHEN tc.auto_formula <> '' ".
		"            THEN tc.auto_formula ".
		"            ELSE c.auto_formula END as auto_formula, ".
		"       c.colprec,c.colscale,".
		"       CASE WHEN tc.colres <> 0 THEN tc.colres ELSE c.colres END,".
		"       c.type_id,".
		"       t.formula, t.formshort,".
  		$this->DBB_RunOutOverride("uiinline","tc","c").",".
		"       CASE WHEN tc.required    <> '' THEN tc.required   ".
		"            ELSE  c.required    END".
		"  FROM zdd.tabcol".$sf ." tc ".
		"  JOIN zdd.tables".$sf ." tab ON tc.table_id = tab.table_id ". 
		"  JOIN zdd.columns".$sf ." c ON tc.column_id_src = c.column_id ".
		"  JOIN zdd.type_exps".$sf ." t ON c.type_id = t.type_id ".
		" WHERE tc.table_id = '". $tb . "'".
      "   AND NOT EXISTS (SELECT skey 
                            FROM zdd.tabflat_c x
                           WHERE x.table_id      = tc.table_id
                             AND x.column_id_src = tc.column_id
                             AND x.suffix        = tc.suffix
                             AND x.prefix        = tc.prefix
                          )";
	$this->SQL($SQL);
		

	// now override any explicit foreign key definitions
	$SQL = 
		"update zdd.tabflat_c ". 
		"  set automation_id = CASE WHEN z.automation_id = '' THEN zdd.tabflat_c.automation_id ELSE z.automation_id END,".
		"      auto_formula  = CASE WHEN z.auto_formula  = '' THEN zdd.tabflat_c.auto_formula  ELSE z.auto_formula  END,".
		"      description   = ".$this->DBB_RunOutOverride('description','z','zdd.tabflat_c').",".
		"      uino          = ".$this->DBB_RunOutOverride('uino'       ,'z','zdd.tabflat_c').",".
		"      uiwithnext    = ".$this->DBB_RunOutOverride('uiwithnext' ,'z','zdd.tabflat_c').
		"  FROM zdd.tabfkycol_c z".
		"  WHERE zdd.tabflat_c.table_id  = '$tb'".
		"    AND z.table_id              = '$tb'".
		"    AND zdd.tabflat_c.table_id_src  = z.table_id_par".
		"    AND zdd.tabflat_c.suffix        = z.suffix".
		"    AND zdd.tabflat_c.prefix        = z.prefix".
		"    AND zdd.tabflat_c.column_id     = z.column_id";
	$this->SQL($SQL);
   
	// Any columns created in this table by using prefix/suffix, should now
   // be put into tabflat so they can be referenced by other downstream
   // tables.  Only safe to be used in agg, fetch, etc.
	$SQL = 
		"insert into zdd.columns_c ( 
          column_id, description 
          ,automation_id, auto_table_id, auto_column_id, auto_formula
          ,ins,uiro,uino,required,dispsize,uiwithnext,prefix_table_name
          ,type_id,colprec,colscale,colres
          ,uiinline,alltables )
       select
          f.column_id, f.description 
          ,c.automation_id, c.auto_table_id, c.auto_column_id, c.auto_formula
          ,c.ins,c.uiro,c.uino,c.required,c.dispsize,c.uiwithnext,c.prefix_table_name
          ,c.type_id,c.colprec,c.colscale,c.colres
          ,c.uiinline,'N' 
         from zdd.tabflat_c f
         JOIN zdd.columns_c c ON f.column_id_src = c.column_id
        where f.table_id = '$tb'
          and f.column_id NOT IN (
              SELECT column_id FROM zdd.columns_c
              )";
	$this->SQL($SQL);
   
}

function DBB_RunOutOverride($column,$src1,$src2) {
	return "CASE WHEN ".$src1.".".$column." <> '' THEN $src1.$column ELSE ".$src2.".".$column." END";
}
// ==========================================================
// Our own utility functions
// ==========================================================

function DBB_LoadYAMLFile($filename,&$retarr) {
   // Now convert to YAML and dump
   include_once("spyc.php");
   $temparray=Spyc::YAMLLoad($filename);
   $this->YAMLError=false;
   
   $this->YAMLPrevious=array("no entries yet, look at top of file");
   $this->YAMLStack=array();
   $this->YAMLContent=array();
   $retarr['data']=$this->YAMLWalk($temparray);
   $retarr['content']=$this->YAMLContent;
   //hprint_r($retarr['data']);
   //exit;
   return !$this->YAMLError;
}

function YAMLWalk($source) {
   $destination=array();
   foreach($source as $key=>$item) {
      // Error 1, usually caused by misplaced or missing semicolon
      if(is_numeric($key)) {
         $this->YAMLWalkError('numindex',$item);
      }
      else {
         $split=explode(' ',$key);
         if(count($split)==1) {
            // If no split, this must be a property/value assignment
            if(is_array($item)) {
               $this->YAMLWalkError('arrayvalue',$item);
            }
            else {
               $destination[$key]=$item;
            }
         }
         else {
            // This an entity, like 'column first_name:', where we have
            // split the key and renested it.  This is where we do real work
            // 
            $type=$split[0];
            $name=$split[1];
            if($type=="content") {
               $cols=$item['columns'];
               $cols['__type']='columns';
               $this->YAMLContent[$name]=array($cols);
               foreach($item['values'] as $onerow) {
                  $onerow['__type']='values';
                  $this->YAMLContent[$name][]=$onerow;
               }
            }
            else {
               $uicolseq=str_pad(++$GLOBALS["uicolseq"],6,'0',STR_PAD_LEFT);
               if(!$item) {
                  // This is a blank item, you get this if the spec file
                  // has entries like:
                  // column description:
                  // column add1:
                  // column add2:
                  $destination[$type][$name]=array('uicolseq'=>$uicolseq);
               }
               else {
                  // A non-blank item, an item with properties, you
                  // get this with:
                  // column vendor:
                  //   primary_key: Y
                  //   uisearch: Y
                  $this->YAMLStack[]=$type;
                  $this->YAMLStack[]=$name;
                  $destination[$type][$name]=$this->YAMLWalk($item);
                  $destination[$type][$name]['uicolseq']=$uicolseq;
                  array_pop($this->YAMLStack);               
                  array_pop($this->YAMLStack);
               }
               $keystub=$name;
               if(isset($item['prefix'])) {
                  $prefix=substr($keystub,0,strlen($item['prefix']));
                  if($item['prefix']<>$prefix) {
                     $this->YAMLWalkError('prefix',$item);
                  }
                  else {
                     $keystub=substr($keystub,strlen($item['prefix'])-1);
                  }
               }
               if(isset($item['suffix'])) {
                  $suffix=substr($keystub,-strlen($item['suffix']));
                  if($item['suffix']<>$suffix) {
                     $this->YAMLWalkError('suffix',$item);
                  }
                  else {
                     $keystub=substr(
                        $keystub
                        ,0
                        ,strlen($keystub)-strlen($item['suffix'])
                     );
                  }
               }
               if(isset($item['keystub'])) $keystub=$item['keystub'];
               $destination[$type][$name]['__keystub']=$keystub;
               if($type=="foreign_key") {
                  $destination[$type][$name]['table_id_par']=$keystub;
               }
            }
         }
      }
   }
   return $destination;
}

function YAMLWalkError($type,$item) {
   switch($type) {
      case 'numindex':
         $this->LogEntry("ERROR IN YAML FILE, NUMERIC INDEX");
         $this->LogEntry("  Below is a dump of nearby values, there may be");
         $this->LogEntry("  a missing or misplaced semicolon in one of the");
         $this->LogEntry("  lines just above.");
         break;
      case 'arrayvalue':
         $this->LogEntry("ERROR IN YAML FILE, ARRAY WHEN EXPECTING VALUE");
         $this->LogEntry("  The array displayed below was encountered");
         $this->LogEntry("  where a scalar value was expected.  This ");
         $this->LogEntry("  can be caused by a semi-colon in the middle");
         $this->LogEntry("  of a line when it should be at the end, or");
         $this->LogEntry("  by a single value like 'test:' where two ");
         $this->LogEntry("  values like 'test 00:' are expected. ");
         break;
      case 'prefix':
         $this->LogEntry("ERROR IN YAML FILE, BAD PREFIX VALUE");
         $this->LogEntry("  The value for 'prefix' does not match the ");
         $this->LogEntry("  beginning of the column/table name");
         break;
      case 'suffix':
         $this->LogEntry("ERROR IN YAML FILE, BAD SUFFIX VALUE");
         $this->LogEntry("  The value for 'suffix' does not match the ");
         $this->LogEntry("  end of the column/table name");
         break;
   }
   hprint_r($item);
   $this->LogEntry("  Current object stack:");
   hprint_r($this->YAMLStack);
   $this->YAMLError=true;
}


// Notes on loading files.  The return value of this function
// is a giant associative array that looks like the {} form,
// so that:
//
// keyword value {...} becomes $return["keyword"]["value"] = array();
function DBB_LoadAddFile($filename,&$retarr) {
	global $parm;

   $this->LogEntry("Loading Spec File: $filename");
   
	// Poor man's basic syntax validation
	$x = array();
	$line = 0;
	$p_curl = array("count"=>0, "lineok"=>0);
	$string_arr = file($filename);
	$string = "";
	foreach ($string_arr as $oneline) {
		$line++;
		
		// Lose comments, and drop line if result is empty
		$oneline = preg_replace('/\/\/.*\n/','',$oneline);
		if (preg_replace('/[\s\n]/','',$oneline) == '') { continue; }
		
		$string.=$oneline;

		$p_curl["count"] += (preg_match_all("/\{/",$oneline,$x) - preg_match_all("/\}/",$oneline,$x));

		if($p_curl["count"] <0) { $p_curl["lineok"]= $line; break; } 

		if ($p_curl["count"]==0)  { $p_curl["lineok"] = $line; }
	}
	if ($p_curl["count"] <> 0) {
		$this->LogEntry("ERROR: Input file has curly-brace mismatch, level ".$p_curl["count"]);
		$this->LogEntry("       Last good line was ".$p_curl["lineok"]);
	}

	// Substitute macros.  At the moment there is only "$LOGIN"
	$string = preg_replace('/\$LOGIN/',$parm["APP"],$string);
	
	// Remove comments and blank lines 
	// (mostly to make it easier to debug the output)
	$string = preg_replace("/\/\*[.\n]*\*\//","",$string); 
	$string = preg_replace("/\/\/.*\n/","\n",$string);
	$string = preg_replace("/\n\s*\n/","\n",$string);
	
	// Add the semi-colon to end of list if not found, now all
	// items have semi-colon terminators.  This is favor to typist.
	$string = preg_replace("/([^;^\s^\}])(\s*)\}/",'$1;$2}',$string);
	
	// A match must:
	// begin with { : ; or }       $1
	// Any amount of whitespace    $2
	// Any character not { : ; } " $3
	// end with ;
	//
	// DO NOT put the greedy setting on /U, that really screws it up
	//
	// These combination of features allows users to put quotes around their own values,
	// but not within the values.
	//
	$string = preg_replace("/([\}\{:;])(\s*)([^\{^\}^:^;^\"]*);/",'$1$2"$3"$4;',$string);
	$string = preg_replace("/([\}\{:;])(\s*)([^\{^\}^:^;^\"]*);/",'$1$2"$3"$4;',$string);
	
	// Now the property assignments, which we comfortably assume are words
	$string = preg_replace("/(\w+):/U",'"$1"=>',$string);
			
	// Take the semi-colons back *out* of the end of lists, then
	// convert all semi colons to commas.
	$string = preg_replace("/;(\s*)\}/",'$1}',$string);
	$string = str_replace(";",",",$string);
	
	// Turn stuff into arrays.  Start with keyword+identifier,
	// then just identifier
	//
	$string = preg_replace("/\b(\w+)\s+(\w+)\s*{/",'array("__type"=>"$1", "__id"=>"$2", ',$string);
	$string = preg_replace("/\b(\w+)\s*\{/"       ,'array("__type"=>"$1", ',$string);
	$string = str_replace("}",")",$string);
	
	// lose the quotes before arrays.  Earlier stage does this, easier to let it do it
	// and then take them out
	$string = preg_replace("/\)(\s*)array/",'),$1array',$string);
	
	// This puts a comma after a close paren, if it can determine that
	// a word follows
	$string = preg_replace("/\)(\s*)\"([^\"]+)\"([,=\)])/",'),$1"$2"$3',$string);

	// Write the output to a file and execute it
	$temp = array();  // to avoid compiler warning
   $fileout=$parm["DIR_TMP"].basename($filename).".php";
	$this->FS_PUT_CONTENTS($fileout,
		"<?php\n".
		"// File generated from $filename during build \n".
		"// If there are errors reported, take the line # of the \n".
		"// error and subtract five to get the line # from $filename \n".
		"\$temp=array(\n".$string."\n);\n?>");
	include($fileout);
	$retarr = $temp;

	// Set form of return information	
	$retarr = array("meta"=>array(),"data"=>array(),"content"=>array());

	// Get us a pointer to the data stuff and walk through
	// and restructure that
	//
	foreach ($temp as $item) {
		if ($item["__type"]=="content") {
			$table = $item["__id"];
			unset($item["__type"]); 
			unset($item["__id"]);
			if (!isset($retarr["content"][$table])) { $retarr["content"][$table]=array(); }
			$retarr["content"][$table] = array_merge($retarr["content"][$table],$item);
		}
		elseif ($item["__type"]=="meta") { 
			unset($item["__type"]);
			foreach ($item as $metaitem) {
				$this->DBB_LoadAddFileWalk($metaitem,$retarr["meta"],null);
			}
		}
		else {
			$this->DBB_LoadAddFileWalk($item,$retarr["data"],$retarr["meta"]);
		}
	}
   
   //h*print_r($retarr);
   
   // Now convert to YAML and dump
   //include_Once("spyc.php");
   //$outstring=Spyc::YAMLDump($retarr);
   //file_put_contents($fileout.".yaml",$outstring);
}


function DBB_LoadAddFileWalk(&$src,&$dst) {
	// We assume that the meta tag comes first, so that meta-data
	// exists once we start walking through data 

	// It's easier to pull this now instead of while walking
	$type=$src["__type"]; 
	unset($src["__type"]);
	
	// The $id array holds all name values. 
	$id = array();
	if (isset($src["__id"])) {
		$id[] = $src["__id"];
		unset($src["__id"]);
	}
	
	$row= array();  // will contain all property-value pairs
	foreach ($src as $key=>$item) {
		// An array will nest, adding subitem as if it were
		// a prop-value pair, which really it is.
		if (is_array($item)) { $this->DBB_LoadAddFileWalk($item,$row); }
		
		// a numeric index means it is lone value, which is a key (name)
		elseif (is_numeric($key)) { $id[] = $item; }
		
		// all others are key->item pairs
		else { $row[$key] = trim($item); }
	}

	// make sure the array for this type exists
	if (!isset($dst[$type])) { $dst[$type]=array(); }
	
	// if there are no id's, just add the row with no index
	if (count($id)==0) {
		$row["uicolseq"]=str_pad(++$GLOBALS["uicolseq"],6,'0',STR_PAD_LEFT);
		$dst[$type][] = $row; }
	else {
		foreach ($id as $idone) {
			// the actual ID we will use is a composition.
			$idone = trim($idone);
			$iduse = trim($this->zzArray($row,"prefix")).$idone.trim($this->zzArray($row,"suffix"));
			
			if (!isset($dst[$type][$iduse])) {
				$row["uicolseq"]
               =str_pad(++$GLOBALS["uicolseq"],6,'0',STR_PAD_LEFT);
				$dst[$type][$iduse] = $row;
				$dst[$type][$iduse]["__keystub"] = $idone;
			}
			else { 
            array_merge($dst[$type][$iduse],$row);
            $dst[$type][$iduse]['uicolseq']
               =str_pad(++$GLOBALS["uicolseq"],6,'0',STR_PAD_LEFT);
         }
		}
	}
}

function DBB_LoadContent($simple,$arr,$prefix,$suffix) {
	// If this a data dictionary, assume early stage of
	// build and unconditional code
	if ($simple) {
		$this->DBB_LoadContentSimpleInsert($arr,$prefix,$suffix);
	}
	else {
		$this->DBB_LoadContentComplex($arr,$prefix,$suffix);
	}
}

/** Load Data unconditionally to tables
 *
 * This routine loads the values stored in content { } 
 * records but only respects the keyword "values" and inserts
 * blanks for unspecified columns.  Intended to be used only
 * by loads to data dictionary tables.
 */
function DBB_LoadContentSimpleInsert($arr,$prefix,$suffix) {
	foreach ($arr as $table_id=>$stuff) {
		foreach ($stuff as $onelist) {
			if ($onelist["__type"]=="columns") { $cols = $onelist; }
			if ($onelist["__type"]=="values" || $onelist["__type"]=="insert") {
				$this->DBB_Insert($prefix,$table_id,$suffix,$this->array_combine($cols,$onelist));
			}
		}
	}
}

/** Load Data to fully-implemented public tables
 *
 *  This routine is loads the values stored in 
 *  content {  } records.  It assumes the tables it is loading
 *  to are fully implemented with automation and constraints,
 *  and tries to be appropriately well-behaved by not inserting
 *  to automated columns, and so forth.
 *
 *  Treats all keywords as "insert" except the keyword "update"
 *  which will update all non-pk columns based on pk, or insert
 *  if not there.
 */
function DBB_LoadContentComplex($arr,$prefix,$suffix) {
	foreach ($arr as $table_id=>$stuff) {
      $this->LogEntry("Processing for table: ".$table_id);
		$pk    = $GLOBALS["utabs"][$table_id]["pk"];
      $flat  = &$GLOBALS["utabs"][$table_id]["flat"];
		$pkarr = explode(",", $pk );
		
		foreach ($stuff as $onelist) {
			if ($onelist["__type"]=="columns") { $cols = $onelist; }
			else {
				$colvals = $this->array_combine($cols,$onelist);

            // Get the PK stuff				
            $match = "";
            foreach ($pkarr as $pkcol) {
               $match .= $this->AddList($match," AND ").$pkcol. " = '".$colvals[$pkcol]."'";
            }
            $sql = "SELECT * FROM $table_id WHERE $match";
            $result = $this->SQLRead($sql);
            if (pg_num_rows($result)==0) { 
               $this->DBB_Insert($prefix,$table_id,$suffix,$colvals,true); 
            }
            else {
               if ($onelist["__type"]=="update") {
                  // Get list of columns w/o PK stuff
                  $colvals2 = $colvals;
                  unset($colvals2['columns']);
                  foreach ($pkarr as $pkcol) { unset($colvals2[$pkcol]); }
                  $update = '';
                  foreach ($colvals2 as $colname=>$colvalue) {
                     $update
                        .=$this->zzListComma($update)
                        .$colname.' = '
                        .$this->SQLFormatLiteral(
                           $colvalue
                           ,$flat[$colname]['type_id']
                           ,false,false);
                  }
                  $sql = "UPDATE $table_id SET $update WHERE $match";
                  //echo $sql."\n\n";
                  $this->SQL($sql);
               }
            }
			}
		}
	}
}

function DBB_Insert($prefix,$table,$suffix,$colvals,$noblanks=false) {
	$cols = '';
	$vals = '';
	
	foreach ($GLOBALS["utabs"][$table]["flat"] as $colname=>$colinfo) {
		if (isset($colvals[$colname])) { $val = $colvals[$colname]; }
		else { if ($noblanks) continue; }
		
		$cols.=$this->AddComma($cols).$colname;

		$type = $colinfo["type_id"];
		if ($type == "int" || $type=="numb") {		
			if (!isset($colvals[$colname])) { $val = '0'; }
			$vals .= $this->AddComma($vals).$val;
		}
		else {
			if (!isset($colvals[$colname])) { $val = ''; }
         // KFD 1/31/07.  Not all builds support pg_escape_string for
         //    some strange reason, so we must use str_replace.  
         //    We must use \' instead of '' because of strange behavior of
         //    str_replace that can get confused and mess up.  There is a
         //    known vulnerability with \', but the builder does not use
         //    any code from a web browser, so we are ok.
         if($colinfo['type_id']=='char' || $colinfo['type_id']=='vchar') {
            if(isset($colinfo['colprec'])) {
               $val=substr($val,0,$colinfo['colprec']);
            }
         }
         $valx=function_exists('pg_escape_string')
            ? pg_escape_string(trim($val))
            : str_replace("'","\'",trim($val));
			$vals .= $this->AddComma($vals)."'".$valx."'";
			//$vals 
         //	.= $this->AddComma($vals)
			// ."'".pg_escape_string(trim($val))."'";
		}
	}
	
	$sql = "INSERT INTO ".$prefix.$table.$suffix." ( ".$cols." ) VALUES ( ".$vals." )";
   //echo $sql;
	$this->SQL($sql);
}

function DBB_SQLBlank($formshort) {
	$retval = "";
	switch ($formshort) {
		case "char": 
		case "varchar":
		case "text":
			$retval = "##";
			break;
		case "numb":
		case "int":
		case "time":
		 	$retval = "0";
		case "date":
		case "timestamp":
			$retval = " NULL ";
	}
	return $retval;
}

function CodeGenerate_Info() {
	$this->LogStage("Generating Application information files.");
	$this->LogEntry("Generating Info file: generated/appinfo.php");
   global $parm;
   $localhost_suffix=ArraySafe($parm,'LOCALHOST_SUFFIX');
	$text = 
		"<?php\n".
		"\$AG['application']='".$parm["APP"]."';\n".
		"\$AG['app_desc']='".$parm["APPDSC"]."';\n".
      "\$AG['localhost_suffix']='".$localhost_suffix."';\n".
      "\$AG['template']='".ArraySafe($parm,'TEMPLATE')."';\n".
      "?>";
	$this->zzFileWriteGenerated($text,'appinfo.php');
   return true;
}

// =====================================================================
// TABLE INFORMATION
//
// The basic idea is to create associative arrays that exactly match
// what is on the server, except where we *add* more information.
// We do not generate any derived or shorthand variables.
// =====================================================================
function CodeGenerate_Tables() {
	$this->LogStage("Saving data dictionary as PHP Associative Arrays");
	
   // Pull from tables, add in whether there are col/row perms
	$results = $this->SQLRead("Select * from zdd.tables_c");
	$resrows = pg_fetch_all($results);
	
   // We will also need the list of composite groups
   $results=$this->SQLRead("Select * from zdd.groups_c WHERE grouplist<>''");
   $groups=pg_fetch_all($results);
   
	foreach($resrows as $utab) {
		// First put the table stuff into the array
		$table_id = $utab["table_id"];
		$table = $this->zzArrayAssociative($utab);
		
		// Now put columns into the array 
		$table["flat"] = array();
		$results = $this->SQLRead("Select * from zdd.tabflat_c WHERE table_id = '$table_id' ORDER BY uicolseq");
		while ($row = pg_fetch_array($results)) {
			$table["flat"][$row["column_id"]] = $this->zzArrayAssociative($row);
		}
      
      // create blank projections array
      $table['projections']=array();
		
		// Loop through rows creating some projections
		$pks = $u_uisearch = $u_uino ="";
		foreach ($table["flat"] as $colname=>$colinfo) {
			if ($colinfo["primary_key"]=="Y") 
            $pks       .=$this->AddComma($pks).$colname;
			if ($colinfo["uisearch"]=="Y") 
            $u_uisearch.=$this->AddComma($u_uisearch).$colname;
			if ($colinfo["uino"]=="Y") 
            $u_uino    .=$this->AddComma($u_uino).$colname;
		}
		$table["pks"] = $pks;
      $table['projections']['_primary_key']=$pks;
      $table['projections']['_uisearch']   =$u_uisearch;
      $table['projections']['_uino']       =$u_uino;
		
		$table["fk_parents"] = $this->CodeGenerate_Tables_FK($table_id,"table_id");
		$table["fk_children"]= $this->CodeGenerate_Tables_FK($table_id,"table_id_par");
		
		// Now do projections
		$results = $this->SQLRead("Select p.projection,p.table_id,p.column_id ".
			" FROM zdd.tabprojcols_c p ".
			" JOIN zdd.tabflat_c f ON p.table_id=f.table_id AND p.column_id = f.column_id ".
			" WHERE p.table_id = '$table_id' ".
			" ORDER BY p.uicolseq ");
		while ($row = pg_fetch_array($results)) {
			$p = trim($row["projection"]);
			if (!isset($table["projections"][$p])) $table["projections"][$p]="";
			$table["projections"][$p].=$this->AddComma($table["projections"][$p]).trim($row["column_id"]);
		}
		
      // Create a blank array of view definitions, very similar
      // to projections
      //
      /*
      $views=array();
		foreach($groups as $group) {
         $results=$this->SQLRead(
            "SELECT pc.column_id,pc.permupd 
               FROM zdd.perm_colsx_c pc
               JOIN zdd.tabflat_c    f 
                 ON f.column_id = pc.column_id
                AND f.table_id  = pc.table_id
              WHERE pc.group_id = '".$group['group_id']."'
                AND pc.table_id = '$table_id'
                AND pc.permsel  = 1
              ORDER BY f.uicolseq"
          );
          while($rowx=pg_fetch_array($results)) {
             $views[$group['group_id']][$rowx['column_id']]=$rowx['permupd'];
          }
      }
      $table['views']=$views;
      */

      // KFD 12/18/06
      // Same as above, newer optimized code
      $views=array();
      $results=$this->SQLRead(
         "SELECT pc.group_id,pc.column_id,pc.permupd 
            FROM zdd.perm_colsx_c pc
            JOIN zdd.tabflat_c    f 
              ON f.column_id = pc.column_id
             AND f.table_id  = pc.table_id
           WHERE pc.table_id = '$table_id'
             AND pc.permsel  = 1
           ORDER BY f.uicolseq"
      );
      $rowsx=pg_fetch_all($results);
      if($rowsx) {
         $this->LogEntry("Doing views for $table_id");
         //hprint_r($rowsx);
         //exit;
         foreach($rowsx as $rowx) {
            $views[$rowx['group_id']][$rowx['column_id']]=$rowx['permupd'];
         }
      }
      $table['views']=$views;
		
		$fileOut = 
"<?php 
// ================================================================
// GENERATED CODE.  This stub contains the information particular 
// to this table, which is used by the universal library
// File generated: ".date("l dS of F Y h:i:s A")."
// ================================================================ 
if (!isset(\$GLOBALS['AG']['tables'])) \$GLOBALS['AG']['tables']=array();
if (!isset(\$GLOBALS['AG']['tables']['$table_id'])) 
\t\$GLOBALS['AG']['tables']['$table_id'] = array(
".$this->zzArrayAsCode($table,2)."
\t);
?>";
	
		$this->zzFileWriteGenerated($fileOut,"ddtable_$table_id.php");
	}
	return true;
}

// =====================================================================
// Helper Routines
// =====================================================================
function CodeGenerate_Tables_FK($table_id,$column_id) {
	$retval = array();

	// The other foreign key column	
	$col2 = ($column_id=="table_id") ? "table_id_par" : "table_id";

	// First the basic foreign key	
	$sql = 
		"SELECT * ".
		"  FROM zdd.tabfky_c ".
		" WHERE ".$column_id ."= '".$table_id."'";
	$results = $this->SQLRead($sql);
	if ($resall  = pg_fetch_all($results)) {
		foreach ($resall as $row) {
			$onerow = $this->zzArrayAssociative($row);
			$key = $onerow["prefix"].$onerow[$col2].$onerow["suffix"];
	
			// Now get information about the other table
			$results = $this->SQLRead(
				"SELECT * from zdd.tables_c ".
				"WHERE table_id = '".trim($row[$col2])."'");
			$rowtable = pg_fetch_array($results);
			$onerow["_table"] = $this->zzArrayAssociative($rowtable);
			
			$retval[$key] = $onerow;
		}
	}
	
	return $retval;
}
	

// =====================================================================
// MODULE INFORMATION
// =====================================================================

function CodeGenerate_Modules() {
	$this->LogStage("Generating PHP Menu Information");
	global $parm;
	$sql =" 
SELECT  m.module,m.description as module_text,m.uisort,t.uisort
       ,t.table_id,t.description as table_text
       ,t.nomenu,t.menuins
       ,'' as menu_parms
  FROM zdd.modules_c m
  JOIN zdd.tables_c t ON t.module = m.module
 WHERE t.nomenu <> 'Y'  
 UNION ALL 
 SELECT  m.module,m.description as module_text,m.uisort,u.uisort
        ,u.menu_page as table_id
        ,u.description as table_text 
        ,'N' as nomenu,'N' as menuins
        ,u.menu_parms 
 FROM zdd.modules_c m 
 JOIN zdd.uimenu_c u ON m.module = u.module  
 ORDER BY 3,4,5";
	$results = $this->SQLRead($sql);
	
	$file2= "<?php\n".
		"\$PAGES=array();\n";
	$file = "<?php\n".
		"\$AGMENU=array();\n";
	
	$module="";
	while ($row = pg_fetch_array($results)) {
      // A simple flat list of pages and descriptions
      $file2.="\$PAGES[#".$row["table_id"]."#]=#".$row['table_text']."#;\n";
      
		if ($row["module"]!==$module) {
			$file.="\$AGMENU[#".$row["module"]."#]=array();\n";
			$file.="\$AGMENU[#".$row["module"]."#][#description#] = #".$row["module_text"]."#;\n";
			$file.="\$AGMENU[#".$row["module"]."#][#items#]=array();\n";
			$module = $row["module"];
		}
		
		//if ($row["nomenu"]=="Y") {
			$file.="\$AGMENU[#".$row["module"]."#][#items#][#".$row["table_id"]."#] = ".
				"array(#name#=>#".$row["table_id"]."#,".
				"#description#=>#".$row["table_text"]."#,".
				"#menu_parms#=>#".$row["menu_parms"]."#,".
				"#mode#=>#normal#);\n";
		//}

		// Here is a rapid-insert operation
		//if ($row["menuins"]=="Y") {
		//	$file.="\$AGMENU[#".$row["module"]."#][#items#][#".$row["table_id"]."#] = ".
		//		"array(#name#=>#".$row["table_id"]."#,".
		//		"#description#=>#".$row["table_text"]."#,".
		//		"#mode#=>#ins#);\n";
		//}

	}	
	$file.="?>";
	$file = str_replace("#","\"",$file);
	$file2.="?>";
	$file2 = str_replace("#","\"",$file2);

	$this->zzFileWriteGenerated($file ,"ddmodules.php");
	$this->zzFileWriteGenerated($file2,"ddpages.php");
	return true;
}

// =====================================================================
// XMLRPC INFORMATION
// =====================================================================
function CodeGenerate_XMLRPC() {
	global $parm;
	$this->LogStage("Generating XML RPC Call Interfaces");
	$results = $this->SQLRead(
		"select c.callcode,h.hosturl,h.hostport,c.callmsg,".
		"       h.hostuid,h.hostpwd,".
		"       h.hostpath,c.callpath ".
		"  FROM xmlrpchosts h ".
		"  JOIN xmlrpccalls c ON h.hostcode = c.hostcode");
	$allrows= array();
	while ($row = pg_fetch_array($results)) {
		$this->LogEntry("Registering ".$row["callcode"]);
		$allrows[] = $row;
	}
	
	$eol = "#,\n";
	foreach ($allrows as $call) {
		$this->LogEntry("Processing ".$call["callcode"]);
		$callcode = trim($call["callcode"]);
		$file = "<?php\n".
			"\$table = array(\n".
			"\t#id#=>#".$callcode.$eol.
			"\t#url#=>#".trim($call["hosturl"]).$eol.
			"\t#port#=>#".trim($call["hostport"]).$eol.
			"\t#callmsg#=>#".trim($call["callmsg"]).$eol.
			"\t#uid#=>#".trim($call["hostuid"]).$eol.
			"\t#pwd#=>#".trim($call["hostpwd"]).$eol.
			"\t#path#=>#".trim($call["hostpath"]).trim($call["callpath"])."#);\n\n".
			"\$table_cols = array(\n";	
		
		$results = $this->SQLRead("SELECT * from xmlrpcparms WHERE callcode='$callcode' ORDER BY parm_seq");
		$colnum = 0;
		while ($row = pg_fetch_array($results)) {
			$colnum++;
			if ($colnum>1) { $file .= ",\n"; }
			$colname = "col".$colnum;
			$type = $row["parmdtype"];
			if ($type=="double") { $type=="int"; }
			$upd = "N";
			if ($row["parmtype"]=="inp") { $upd="Y"; }
			$file .=
				"\t#$colname#=>array(\n".
				"\t\t#name#=>#".trim($colname)."#,#value#=>#".$eol.
				"\t\t#size#=>#20".$eol.
				"\t\t#desc#=>#".trim($row["parm_desc"]).$eol.
				"\t\t#type#=>#".trim($type).$eol.
				"\t\t#xmltype#=>#".trim($row["parmdtype"]).$eol.
				"\t\t#UI#=>#Y".$eol.
				"\t\t#INS#=>#Y#,#UPD#=>#$upd".$eol.
				"\t\t#TABLE_PAR#=>##,#UIWITHNEXT#=>#N#)";
		}
		
		$file.=");\n?>";
		$file = str_replace("#","\"",$file);

		$this->zzFileWriteGenerated($file,"ddxmlrpc_$callcode.php");

	}
	return true;
}

// ==========================================================
// Database Access Routines
// ==========================================================
function SQLRead($sqlText,$noReport=false) {
	//$errlevel = error_reporting(0);
   
	//pg_send_query($dbconn2,$sqlText);
	//$results=pg_get_result($dbconn2);
	$results=pg_query($GLOBALS["dbconn2"],$sqlText);
	if (!$noReport) {
		if ($t=pg_result_error($results)) { 
			$this->LogEntry("");
			$this->LogEntry("**** SQL ERROR ****: ".$t);
			$this->LogEntry("Command was: " );
			$this->LogEntry($sqlText,true);
			$GLOBALS["sqlCommandError"]=$sqlText;
		}
	}
	//error_reporting($errlevel);
	return $results;
}

function SQL_ANDRO($sqlText) {
	return $this->SQL($sqlText,false,true,$GLOBALS["dbconna"]);	
}

function SQL($sqlText,$noReport=false,$split80=true,$dbx=null) {
	if (is_null($dbx)) { $dbx = $GLOBALS["dbconn1"];}
	$retval = true;
	$errlevel = error_reporting(0);
	
	pg_send_query($dbx,$sqlText);
	$results=pg_get_result($dbx);
	if (!$noReport) {
		if ($t=pg_result_error($results)) { 
			$this->LogEntry("");
			$this->LogEntry("**** SQL ERROR ****: ".$t);
			$this->LogEntry("Affected Rows :".pg_affected_rows($results));
			$this->LogEntry("Command was: " );
			$this->LogEntry($sqlText,$split80);
			$retval=false;
		}
	}
	error_reporting($errlevel);
	return $retval;
}

function SQLFORMATBLANK($type,$fortheplan=false,$doubleplan=false) {
	switch ($type) {
		case "char": 
		case "vchar":
		case "text":
		case "cbool":
		case "gender":
			return $this->SQLFORMATLITERAL('',$type,$fortheplan,$doubleplan);
			break;
      case 'date':
      case 'dtime':
         return 'NULL';
         break;
		default:
			return $this->SQLFORMATLITERAL('0',$type,$fortheplan,$doubleplan);
	}
}

function SQLFORMATLITERAL($val,$type,$fortheplan,$doubleplan=false) {
	if ($fortheplan) { $q="#"; } else { $q="'"; }
	if ($doubleplan) { $q = $q.$q; }
	
	switch ($type) {
		case "char":
		case "vchar":
		case "text":
		case "cbool":
      case "gender":
			$retval = $q.trim($val).$q;
			break;
		case "int":
		case "numb":
		case "money":
		case "time":
			$retval = "".$val;
			break;
		case "date":
			// TODO
			$retval = "null";
	}
	return $retval;
}

function SQL_fetch_all_1col($res) {
	$retval = array();
	while ($row=pg_fetch_row($res)) {
		$retval[]=$row[0];
	}
	return $retval;
}

// ==========================================================
// Filesystem related subroutines
// ==========================================================

// This is a main flow routine.  It ensures all directories
// and files exist and are writable
function FS_Prepare() {
	$this->LogStage("Confirming server has proper file permissions");
	$grp = $this->Shellwhoami();
	$app = $GLOBALS["parm"]["APP"];
	$scn = "/tmp/andro_fix_$app.sh";
	
	$dir_pub = $this->FS_ADDSLASH($GLOBALS["parm"]["DIR_PUBLIC"]);
	$dir_pubx= $dir_pub.$this->FS_ADDSLASH($GLOBALS["parm"]["DIR_PUBLIC_APP"]);
	
	$SCRIPT = "";
	
	// Step 1: Make sure parent directories are writable by us.
	// If not, fail here.
	//
	$SCRIPT .= $this->FS_CHECKDIR($dir_pub,$grp);
	if ($SCRIPT<>"") {
		return $this->FS_PrepareFail($SCRIPT);
	}
	
	// Next, the appplication subdir must exist or be creatable
	//
	if (!$this->FS_MKDIR($dir_pubx)) {
		return false; 
	}

 	// Now we know the directories exist.  Let's make sure they
	// are completely clean, every single file is readable 
	// and writable by the web server
	//
	$SCRIPT .= $this->FS_CHECKDIR($dir_pubx,$grp,true);
	if ($SCRIPT<>"") {
		return $this->FS_PrepareFail($SCRIPT);
	}

   // Now split off for a dev mode vs. an instance. Fail
   // early if we cannot continue
   global $parm;
   if(isset($parm['IVER'])) {
      $retval= $this->FS_PrepareInst();
   }
   else {
      $retval= $this->FS_PrepareDev($SCRIPT,$dir_pubx);
   }
   if(!$retval) return false;
   
   // After we've returned, link any node manager templates into
   // the application templates directory.  Notice that this is the
   // same for instances and dev-mode apps.
   //
   $dirl='';
   if($GLOBALS['dir_andro']<>'') {
      $dirl= AddSlash($GLOBALS['dir_andro'])."templates/";
   }
   elseif(isset($parm['IVER'])) {
      $dirl = AddSlash($parm['DIR_LINK_LIB'])."templates/";
   }
   if($dirl<>'') {
      $this->LogEntry("");
      $this->LogEntry("Linking node manager templates into templates dir");
      clearstatcache();

      // First delete any links already there, might be defunct
      $DIR=opendir($dir_pubx."templates");
      while( ($file=readdir($DIR))!==false ) {
         if($file=='.' || $file=='..') continue;
         
         $tgt=$dir_pubx."templates/".$file;
         if(is_link($tgt)) {
            $this->LogEntry("Removing prior link $tgt");
            $this->FSDelTree($tgt);
         }
      }
      
      // Now link in any directories 
      $DIR=opendir($dirl);
      while( ($file=readdir($DIR))!==false ) {
         if($file=='.' || $file=='..') continue;
         
         $target=$dirl.$file;
         $this->LogEntry("Creating link to $file");
         $this->CopyTree($target,$dir_pubx."/templates/".$file);
      }
   }
   
   // Another big step is to create directories that link
   // to the main directory.  These are aliases.  Virtual domains
   // can be pointed at these different directories, and the
   // framework or application can look at $HTTP_SERVER_VARS['pwd']
   // to detect the path being used.
   //
   $dirs=explode(",",$GLOBALS['parm']['XDIRS']);
   foreach($dirs as $dir) {
      if($dir=='') continue;

      // Work up the name of the alias directory
      $dir_pubx2= $dir_pub.$GLOBALS["parm"]["DIR_PUBLIC_APP"];
      $dir_pubx3= $dir_pubx2.'_'.$dir;
      
      if(file_exists($dir_pubx3)) {
         //unlink($dir_pubx3);
      }
      else {
         $this->LogEntry("Copying Files to $dir_pubx3 ");
         $this->LogEntry("            from $dir_pubx2 ");   
         $this->CopyTree($dir_pubx2,$dir_pubx3);
      }
   }
   
   return $retval;
}

function FS_PrepareInst() {
   $this->LogEntry("Preparing Directories for Instance Upgrade");
   
   // Get the three directories of insterest
   global $parm;
   $tpl  = ArraySafe($parm,'TEMPLATE');
   $dir  = AddSlash($parm['DIR_PUB']);
   $dirl = AddSlash($parm['DIR_LINK_LIB']);
   $dira = AddSlash($parm['DIR_LINK_APP']);
   
   // Handle all of the subdirectories
   $dbres=pg_query($GLOBALS["dbconna"],"SELECT * FROM appdirs");
   while($row=pg_fetch_array($dbres)) {
      $tgt=trim($row['dirname']);
      if($row['flag_copy']=='Y') {
         // These are directories that are packaged up.  All of
         // these have to be un-linked and re-symlinked
         //
         /*
         // KFD 3/16/07.  Strange exception.  If a single template
         //  has been named, make an actual dir and link to that
         //  template only.  This is to prevent other templates, which
         //  may have branding information, from in any way being 
         //  visible or exposed on a different site
         if($tgt=='templates' && $tpl<>'') {
            x_echoFlush("Linking to preferred template $tpl");
            mkdir($dir."/templates");
            symlink($dira."/templates/$tpl",$dir."/templates/$tpl");
         }
         else {
         */
            if(file_exists($dir.$tgt)) $this->DelTree($dir.$tgt);
            if($row['flag_lib']=='Y') {
               $this->CopyTree($dirl.$tgt,$dir.$tgt);
            }
            else {
               $this->CopyTree($dira.$tgt,$dir.$tgt);
            }
         /*
         }
         */
      }
      else {
         // These are not packaged, they are built in run-time,
         // so make sure they exist as real directories
         //
         if($tgt=='instpub' && ArraySafe($GLOBALS['parm'],'IVER')=='') {
            // do nothing for this instance directory
         }
         else {
            if(!file_exists($dir.$tgt)) mkdir($dir.$tgt);
         }
         
         // Hardcode!
         if($tgt=='generated' || $tgt=='dynamic') {
            $cmd="rm $dir$tgt/*";
            `$cmd`;
         }
      }
   }
   
   // Copy up the root files
   $this->LogEntry("Copying /root files into root directory...");
   $cmd="cp $dir/root/* $dir/";
   `$cmd`;
   $cmd="cp $dir/root/htaccess $dir/.htaccess";
   `$cmd`;
   `rm $dir/htaccess`;
   return true;
}

function FS_PrepareDev($SCRIPT,$dir_pubx) {   
   // Special handling of the "lib" directory.  If not building a node
   // manager, create as link to node manager
   if ($GLOBALS['dir_andro']<>'') {
      if(!is_dir($dir_pubx."lib")) {
         $this->CopyTree($GLOBALS['dir_andro']."/lib/",$dir_pubx."lib");
      }
      if(!is_dir($dir_pubx."clib")) {
         $this->CopyTree($GLOBALS['dir_andro']."/clib/",$dir_pubx."clib");
      }
   }

	// Finally, at the end, we can only get this far if we
	// have total control of the trees, so create all of 
	// the directories we need
	//
   $retval=true;
   $retval=$retval && $this->FS_MKDIR($dir_pubx."application/",true);
   $retval=$retval && $this->FS_MKDIR($dir_pubx."generated/"  ,true);
   $retval=$retval && $this->FS_MKDIR($dir_pubx."dynamic/"    ,true);
   $retval=$retval && $this->FS_MKDIR($dir_pubx."lib/"        ,true);
   $retval=$retval && $this->FS_MKDIR($dir_pubx."appclib/"    ,false);
   $retval=$retval && $this->FS_MKDIR($dir_pubx."clib/"       ,false);
   $retval=$retval && $this->FS_MKDIR($dir_pubx."templates/"  ,false);
   $retval=$retval && $this->FS_MKDIR($dir_pubx."apppub/"     ,false);
   $retval=$retval && $this->FS_MKDIR($dir_pubx."instpub/"    ,false);
   $retval=$retval && $this->FS_MKDIR($dir_pubx."files/"      ,true);
   $retval=$retval && $this->FS_MKDIR($dir_pubx."tmp/"        ,true);

   if($retval) {
      $dirs=$GLOBALS['dir_andro'] <> '' ? $GLOBALS['dir_andro'] : $dir_pubx;  
      $this->LogEntry("Copying /root files into root directory...");
      $cmd="cp $dirs/root/* $dir_pubx/";
      `$cmd`;
      $cmd="cp $dirs/root/htaccess $dir_pubx/.htaccess";
      `$cmd`;
      `rm $dir_pubx/htaccess`;
   }
	
	return $retval;
}

function FS_PrepareFail($SCRIPT) {
	$scn = "/tmp/andro_fix_perms.sh";
	$this->LogEntry("");
	$this->LogEntry(">>> Problem with file/directory permissions");
	$this->LogEntry("");
	$this->LogEntry("The web server must have write permissions to ");
	$this->LogEntry("various directories and files so it can run a ");
	$this->LogEntry("build.  Right now it does not have enough permissions.");
	$this->LogEntry("This usually happens only when a server is first ");
	$this->LogEntry("installed, or if somebody manually changes files");
	$this->LogEntry("in the server-controlled directories.");
	$this->LogEntry("");
	$this->LogEntry("A script has been written");
	$this->LogEntry("to $scn that will fix the file permissions.");
	$this->LogEntry("You must run this script as root, then run the");
	$this->LogEntry("build again.");
	$this->FS_PUT_CONTENTS($scn,"#!/bin/bash\n".$SCRIPT);
	chmod($scn,0755);
	return false;
}

function FS_ADDSLASH($input) {
	if (substr($input,-1,1)=="/") { return $input; }
	else { return $input."/"; }
}

function FS_MKDIR($dir,$noaccess=false) {
	$this->LogEntry("Making directory if not exists: $dir");
	if (file_exists($dir)) {
		if (is_dir($dir)) {
         $this->FS_MKDIR_Noaccess($dir,$noaccess);
      }
      else {
			$this->LogError("File exists blocking a directory");
			$this->LogEntry("We need to create directory $dir");
			$this->LogEntry("There is a FILE by that name preventing a build.");
			$this->LogENTRY("");
			$this->LogENTRY("THIS ERROR REQUIRES MANUAL INTERVENTION");
			$this->LogENTRY("");
			$this->LogEntry("Please manually remove that file before ");
			$this->LogEntry("attempting a build.");
			return false;
		}
	}
	else {
		mkdir($dir);
		chmod($dir,0770);
      $this->FS_MKDIR_Noaccess($dir,$noaccess);
	}
	return true;
}

function FS_MKDIR_NoAccess($dir,$noaccess) {
   if ($noaccess) {
      $file=$dir.'/.htaccess';
      $text="Deny From All";
      $this->FS_PUT_CONTENTS($file,$text);
   }
}


function FS_CHECKDIR($dir,$grp,$checkallfiles=false) {
	$this->LogEntry("Checking permissions on: $dir");
	clearstatcache();	// get latest info 

	$SCRIPT = "";	
	if (!file_exists($dir)) {
		$this->LogError("Directory does not exist: $dir");
		$SCRIPT.="\n";
		$SCRIPT.="mkdir $dir\n";
		$SCRIPT.="chgrp ".$grp." $dir \n" ;
		$SCRIPT.="chmod g+wr $dir\n";
	}
	else {
		if (! is_writable($dir) || !is_readable($dir)) {
			$this->LogError("Wrong read/write perms on directory: $dir");
			$SCRIPT.="chgrp $grp $dir \n"; 
			$SCRIPT.="chmod g+wr $dir\n";
		}
		else {
			// The "Checkallfiles" checks files and also recurses
			//
			if ($checkallfiles) {
				$DIR = opendir($dir);
				while (($file = readdir($DIR)) !== false) {
					if ($file==".") continue;
					if ($file=="..") continue;
               if(is_link($dir.$file)) continue; // no action for s*ymlinks
					if (!is_writable($dir.$file) || !is_readable($dir.$file)) {
						$this->LogError("Unwritable file in directory: $dir");
                  $this->LogEntry("-- file is: ".$file);
						$SCRIPT.="chgrp $grp $dir -R\n"; 
						$SCRIPT.="chmod g+wr $dir -R\n";
                  break;
					}
               if (is_dir($dir.$file)) {
                  if($file<>'pkg-apps') {
                     $SCRIPT.=$this->FS_CHECKDIR($dir.$file.'/',$grp,true);
                  }
               }
				}
				closedir($DIR);
			}
      }
	}
	return $SCRIPT;
}

function FS_GET_CONTENTS($filespec) {
	return file_get_contents($filespec); 
}

function FS_PUT_CONTENTS($file,$text,$changequotes=false) {
	if ($changequotes) {
		$text = str_replace("#","\"",$text);
	}
	$FILEOUT=fopen($file,"w");
	fwrite($FILEOUT,$text);
	fclose($FILEOUT);
}



// ==========================================================
// Wrappers to PHP functions.  The general idea is to wrap
// anything that needs error handling.  The idea is *NOT* to
// try to build some abstract layer to separate us from
// PHP, that would be completely silly.
// ==========================================================
// ==========================================================
// REGION: LOGGING utilities
// ==========================================================
function LogStart() 
{
	// this works out if we are flushing
	$GLOBALS["log_flush"]=false;
	if (function_exists("x_EchoFlush")) {
		echo "<pre>\n";
		//ob_start();
		$GLOBALS["log_flush"] = true;
	}

	// This prevents attempts to write to file before we've
	// established that we can actually write to it
	//
	$GLOBALS["log_file"] = "";
	$grp = $this->Shellwhoami();
	
	if (!isset($GLOBALS["parm"])) {
		$this->LogEntry("===================================================" );
		$this->LogEntry(" ANDROMEDA UPGRADE STARTUP ERROR                   " );
		$this->LogEntry(" >>> this program expects to be invoked using <<<  " );
		$this->LogEntry(" >>> PHP 'include' and for parameters to be   <<<  " );
		$this->LogEntry(' >>> found in $GLOBALS["parm"]                <<<  ' );
		$this->LogEntry("===================================================" );
		return false;
	}
	
	// First big error is that we cannot write to the current
	// directory.
	$GLOBALS["log_file"] = "";	
	$t=pathinfo(__FILE__);
	$dircur  = $this->FS_AddSlash(trim($t["dirname"]));
	if (! is_writable($dircur)) {
		$this->LogEntry("===================================================" );
		$this->LogEntry(" ANDROMEDA UPGRADE STARTUP ERROR                   " );
		$this->LogEntry(" >>> this program must be able to write to");  
		$this->LogEntry(" >>> workding dir: ".$dircur );
		$this->LogEntry(' >>> Program is running as user '.$grp);
		$this->LogEntry("===================================================" );
		return false;
	}

	// Next big fatal error is that we cannot write to the log
	//
	$pLogFile = "AndroDBB.".$GLOBALS["parm"]["APP"].".log";
	$pLogPath = $this->FS_ADDSLASH($dircur).'../tmp/'.$pLogFile;
	if (file_exists($pLogPath)) {
		if (! is_writable($pLogPath)) {
			$this->LogEntry("===================================================" );
			$this->LogEntry(" ANDROMEDA UPGRADE STARTUP ERROR                   " );
			$this->LogEntry(" >>> this program must be able to write to");  
			$this->LogEntry(" >>> the log file: ".$pLogPath);
			$this->LogEntry(' >>> Program is running as user '.$grp);
			$this->LogEntry("===================================================" );
			return false;
		}
	}
	
	// clear the log
	$cmd = 'echo "" > '.$pLogPath;
	`$cmd`;
	$GLOBALS["log_file"] = $pLogPath;	

   //  get the password if necessary
   if(function_exists("SessionGet")) {
      // this means called from web app, get current user's pw
      $GLOBALS['x_password']=SessionGet('PWD');
   }
   else {
      // Called from CLI, Unix user must be priveleged or it won't work
      $GLOBALS['x_password']='';
   }
   
   /*  October 20, 2006.  No longer require a password in the file, 
       either the logged on user is OK or we are running from the command
       line as a priveleged user.
	if (!isset($GLOBALS["x_password"])) {
		$this->LogEntry("===================================================" );
		$this->LogEntry(" ANDROMEDA UPGRADE STARTUP ERROR                   " );
		$this->LogEntry(" >>> please set superuser password in         <<<  " );
		$this->LogEntry(' >>> $GLOBALS["x_password"] before starting   <<<  ' );
		$this->LogEntry("===================================================" );
		return false;
	}
   */
	
	$parm = &$GLOBALS["parm"];

	$parm["DIR_PUB"] = 
		$this->FS_AddSlash($parm["DIR_PUBLIC"]).
		$this->FS_AddSlash($parm["DIR_PUBLIC_APP"]);
	
	$this->LogEntry("===================================================");
	$this->LogEntry(" ANDROMEDA CLIENT UPGRADE PROGRAM ");
	$this->LogEntry("===================================================");
	$this->LogEntry("Starting Log at ".date("r"));
   $this->LogEntry("This program: ".__FILE__);
	$this->LogEntry("===================================================");
	$this->LogEntry("Parameters: ");
	$this->LogEntry("Application Code       : ". $parm["APP"]);
	$this->LogEntry("Instance Code (if appl): ". $this->zzArray($parm,'INST'));
	$this->LogEntry("Instance Vers (if appl): ". $this->zzArray($parm,'IVER'));
	$this->LogEntry("Application Description: ". $parm["APPDSC"]);
	$this->LogEntry("Node Public Directory  : ". $parm["DIR_PUBLIC"]);
	$this->LogEntry("App Public SubDir      : ". $parm["DIR_PUBLIC_APP"]);
   $this->LogEntry("App Public Directory   : ". $parm["DIR_PUB"]);
   $this->LogEntry("Library Symlink Source : ". $this->zzArray($parm,"DIR_LINK_LIB"));
   $this->LogEntry("Application Symlink Src: ". $this->zzArray($parm,"DIR_LINK_APP"));
	$this->LogEntry("Database Server        : ". $parm["DBSERVER_URL"]);
	$this->LogEntry("Connecting as user     : ". $parm["UID"]);
	$this->LogEntry("Password               : *** NOT DISPLAYED ***");
	$this->LogEntry("Connecting to database : ". $parm["APP"]);
	$this->LogEntry("Bootstrap Dictionary   : ". $parm["SPEC_BOOT"]);
	$this->LogEntry("Universal Dictionary   : ". $parm["SPEC_LIB"]);
	$this->LogEntry("Application Dictionary : ". $parm["SPEC_LIST"]);
	$this->LogEntry("Log File: ".$pLogFile);
	$this->LogEntry("---------------------------------------------------");
	$parm["DIR_WORKING"]=dirname(__FILE__);
	$this->LogEntry("Program executing in  : ". $parm["DIR_WORKING"]);
   $parm["DIR_TMP"]=$parm["DIR_PUB"]."tmp/";
	$this->LogEntry("Temporary files go to : ". $parm["DIR_TMP"]);
	$this->LogEntry("===================================================");
	
	return true;
}

function LogClose($ok) {
	if (!$ok) {
		$this->LogEntry("");
		$this->LogEntry("((((((((((((((((( FATAL ERROR )))))))))))))))))))))");
		$this->LogEntry("(((((((((((((((((             )))))))))))))))))))))");
		$this->LogEntry("(((    Process did not complete successfully    )))");     
		$this->LogEntry("(((((((((((((((((             )))))))))))))))))))))");
		$this->LogEntry("((((((((((((((((( FATAL ERROR )))))))))))))))))))))");
		$this->LogEntry("");
	}
	$this->LogStage("Closing Log");
	$this->LogEntry("===================================================");
	$this->LogEntry(" ANDROMEDA CLIENT UPGRADE PROGRAM - LOG CLOSED     ");
	$this->LogEntry("===================================================");

	if ($GLOBALS["log_flush"]) {
		x_EchoFlush("</pre>");
		//ob_flush();
		//ob_end_clean();
	}
	
}
	
function LogError($message) {
	$this->LogEntry(" ");
	$this->LogEntry(" ERROR!");
	$this->LogEntry(" ERROR: ".$message);
	$this->LogEntry();
}

function LogEntry($logText="",$Split80=false) {
	if (!$Split80 || strlen($logText)<75) {	
		
		if (!$GLOBALS["log_flush"]) {
			echo $logText."\n";
		}
		else {
			// THIS IS NOT A MISTAKE!  If this function exists,
			// it will be in u_dispatch.php, and is not part of
			// an object.
			x_EchoFlush($logText);
		}
		
		if ($GLOBALS["log_file"]<>"") {
         file_put_contents($GLOBALS['log_file'],$logText,FILE_APPEND);
			//$cmd = "echo \"$logText\" >> ".$GLOBALS["log_file"];
			//`$cmd`;
		}
	}
	else {
		$prefix="";
		while (strlen($logText)>75) {
			$x=min(75,strlen($logText));
			while ($x>=0) {
				$x--;
				if (substr($logText,$x,1)==" " || substr($logText,$x,1)==",")
				{ break; }
			}
			$this->LogEntry($prefix.substr($logText,0,$x+1));
			if ($prefix=="") { $prefix="   "; }
			$logText = substr($logText,$x+1);
		}
		$this->LogEntry($prefix.$logText);
	}
}
	
function LogStage($logText) {
	$this->LogEntry("");
	$this->LogEntry("---------------------------------------------------");
	$this->LogEntry($logText);
	$this->LogEntry("---------------------------------------------------");
	$this->LogEntry(date("Y-m-d H:i:s a",time()));
	$this->LogEntry("");
}

// =========================================================================
// Routines deprecated only because of names, but
// these two will likely live on forever
// =========================================================================
function AddList($input,$add) { return $this->zzListAdd($input,$add); }
function AddComma($input) { return $this->zzListComma($input); }
// =========================================================================
// zz Routines.  Completely general purpose stuff
// =========================================================================
function zzPULLVARS($input) {
	$input = strtolower($input);
	//$this->LogEntry( "processing $input");
	$retval= array();
	$proc = false;
	$curr = "";
	for ($x=0;$x<strlen($input);$x++) {
		$c = substr($input,$x,1);
		if (!$proc) {
			if ($c=="@") { $proc=true; $curr=""; }
		}
		else {
			$t = strpos("abcdefghijklmnopqrstuvwyz0123456789_",$c);
			if ($t===false) {
				//$this->LogEntry("stopping on $c");
				$retval[] = $curr;
				$proc=false;
			}
			else { 
				//$this->LogEntry("Adding $c");
				$curr.=$c;
 			}
		}
	}
	if ($proc) { $retval[] = $curr;}
	return $retval;
}

function zzArray($arr,$key) {
	if (isset($arr[$key])) { return $arr[$key]; } else { return ""; }
}

function zzListAdd($input,$add) {
	if ($input!="") { return $add; } else { return ""; }
}

function zzListComma($input) { return $this->zzListAdd($input,","); }

// Pulls out only the "key"=>"value" stuff, leaving out
// items in this form $x = array("z","x","y") and leaving
// out child arrays
function zzArrayAssociative($array) {
	$retval = array();
	foreach ($array as $key=>$value) {
		if (!is_numeric($key) && !is_array($value)) {
			$retval[$key] = $value;
		}
	}
	return $retval;
}

// Returns a string of executable PHP code that would
// recreate the given array
function zzArrayAsCode($array,$level=0) {
	$retval = "";
	foreach ($array as $key=>$value) {
		$retval .= $this->zzListComma($retval);
		$retval .= "\n".str_repeat("\t",$level)."'$key'=>";
		if (is_array($value)) {
			$retval.=" array(";
			$retval.=$this->zzArrayAsCode($value,$level+1).")";
		}
		else {
			$retval.="'$value'";
		}
	}
	return $retval;
}

// Returns the key if it exists, else blank
function zzArraySafe($arr,$key) {
	if (isset($arr[$key])) return $arr[$key]; else return "";
}

function array_combine($arr1,$arr2) {
	$retval = array();
	while (list($x,$key)=each($arr1)) {
		list($y,$value)=each($arr2);
		$retval[trim($key)]=trim($value);
	}
	return $retval;
}

function zzFileWriteGenerated($content,$filename) {
   global $parm;
	$fname=$parm["DIR_PUB"].'generated/'.$filename; 
	$FILEOUT=fopen($fname,"w");
	$this->LogEntry("Writing Generated File: $fname");
	fwrite($FILEOUT,$content);
	fclose($FILEOUT);
}


function fsUnDeltree($tgt) {
   unlink($tgt);
}
function fsCopyPath($src,$dst) {
   
}


function ShellWhoAmi() {
   return $this->ShellExec('whoami');	
}

function ShellExec($cmd) {
   // This function exists so we can hopefully someday eliminate the
   // nasty flashing CMD.exe on Windows.  This command supposedly does
   // it but did not work on SDS win2003 machine:
   // exec('start /B /path/to/$cmd');
   //
   // Likewise this was supposed to work and did not for us:
   // 
   // $shell = new COM('WScript.Shell');
   // $shell->Run('cmd /c start "" "' . $url . '"', 0, FALSE);
   //unset($shell); 
   return exec($cmd);
}

function GetOS() {
   return $_ENV['OS'];
}

// =========================================================================
// End of Class Definition
// =========================================================================
}
// =========================================================================
// End of File
// =========================================================================
?>
