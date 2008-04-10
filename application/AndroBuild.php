har<?php
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
               s            
   You should have received a copy of the GNU General Public License
   along with Andromeda; if not, write to the Free Software
   Foundation, Inc., 51 Franklin St, Fifth Floor,
   Boston, MA  02110-1301  USA 
   or visit http://www.gnu.org/licenses/gpl.html
\* ================================================================== */
/*  ==================================================================
    AndroDBB, the Andromeda Database Builder
    ------------------------------------------------------------------
    This program is entirely self-contained, even though it can be called
    interactively.  It must remain self-contained so that no change to the
    run-time libraries can possible affect this program in any way.
    
    Note that the routine "LogEntry" does look for the function "x_EchoFlush",
    which will exist in runtime.  LogEntry calls this routine if it can 
    find it, otherwise it just skips it.
    ==========================================================================
*/

// -------------------------------------------------------------------
// Make some settings and then go right into it
// -------------------------------------------------------------------
ini_set("max_execution_time",0);  // allows to run in a web page.
ini_set("allow_url_open",false);
ini_set("error_reporting",E_ALL);
ini_set("display_errors",true);
ini_set("log_errors",true);

$o = new x_builder();
$o->main();
// ===================================================================
// ===================================================================
// CLASS CODE BELOW
// ===================================================================
// ===================================================================
class x_builder {
    function x_builder() {    
        // used to sequence all dd entries, mostly used for columns    
        $this->uicolseq= 0;
        
        // Database connections
        $this->dbconn1 = '';
        $this->dbconn2 = '';
        $this->sqlCommandError = '';
        
        // Default width of columns created manullay
        $this->defColWidth = 50;

        // The data dictionary will be loaded from AndroDBB.add
        $this->ddarr = array();
        $this->ddflat = array();
        $this->utabs = array();
        $this->ufks = array();
        $this->content = array();  // content loaded from specs
        
        $this->cmdsubseq=0;
    }
    
    function main() {	
        $ts=time();
        $this->ts=$ts;
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
        $retval = $retval && $this->SpecFlattenValid();
        $retval = $retval && $this->SpecFlatten();
        $retval = $retval && $this->SpecFlattenRowCol();

        // Now we know everything, we can validate the new
        // spec.  This does not validate changes.
        $retval = $retval && $this->SpecValidate();

        $retval = $retval && $this->SpecLocal();  // now only lists

        
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
        $retval = $retval && $this->ContentDD();
        $retval = $retval && $this->SecurityNodeManager();
        
        // This is code generation
        //
        $retval = $retval && $this->CodeGenerate_Info();
        $retval = $retval && $this->CodeGenerate_Tables();
        $retval = $retval && $this->CodeGenerate_Modules();
        
        // Big new thing, 2/6/08, build scripts
        //
        $retval = $retval && $this->BuildScripts();
        
        $this->DB_Close();
        $this->LogClose($retval,$ts);
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
	$this->dbconn1 = pg_connect($cnx,PGSQL_CONNECT_FORCE_NEW);
	$this->dbconn2 = pg_connect($cnx,PGSQL_CONNECT_FORCE_NEW);

	$retval=true;
	if ($this->dbconn1) $this->LogEntry("Connection 1 is up OK"); else $retval = false;
	if ($this->dbconn2) $this->LogEntry("Connection 2 is up OK"); else $retval = false;
		
	return $retval;
}


function DB_Close() {
    $this->LogStage("Closing database connections (if open)");
    if ($this->dbconn1) {
        pg_close($this->dbconn1);
        $this->LogEntry("Closing connection #1"); 
    }
    if ($this->dbconn2) { 
        pg_close($this->dbconn2);
        $this->LogEntry("Closing connection #2"); 
    }
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
	$this->SQL(
		"CREATE LANGUAGE plperlu ". 
		" HANDLER plperlu_call_handler; ",true);
	$this->LogEntry("Dropping (if exists) schema zdd");
	$this->SQL("DROP SCHEMA zdd CASCADE ",true);
	$this->LogEntry("Creating schema zdd");
	$this->SQL("CREATE SCHEMA zdd");
   
   // Next up is the complex password settings, where the user is not
   // allowed to put in a plaintext password.  We must first generate and
   // then restrict access to the email sending routine
   $this->SQL('create language plperlu',true);
   $sq='
create or replace function pwmail(char,char,char,char,char) 
                  returns integer as 
$$
  my ($sendTo, $Subject, $Message,$host,$from)
    =($_[0], $_[1], $_[2],$_[3],$_[4]);
  use Net::SMTP;
  my $smtp=Net::SMTP->new($host);
  
  $smtp->mail(\'\');
  $smtp->recipient($sendTo);
  $smtp->data();
  $smtp->datasend("To: $sendTo\n");
  $smtp->datasend("Subject: $Subject\n");
  if($from ne \'\') {
      $smtp->datasend("From: ".$from."\n");
  }
  $smtp->datasend("Content-Type: text/plain;\n\n");
  $smtp->datasend($Message);
  $smtp->dataend();
  $smtp->quit();
  return 1;
$$
language plperlu
;';
   $this->SQL($sq);
   $sq="revoke execute
            on function pwmail(char,char,char,char,char)
          from group public";
   $this->SQL($sq);
   
	
	return true;
}

// ==========================================================
// Load stored procedures and the like
// ==========================================================
function SP_Load() {
	global $parm;
	$retval = true;
	$this->LogEntry("");
	$this->LogEntry("Loading server-side code to use during build");

	//$this->LogEntry("This is hard-coded to read and execute file $file");
   //$DIR=isset($parm['D*IR_LINK_LIB']) 
   //   ? $parm['DIR_LINK_LIB'] 
   //   : $parm['DIR_PUB'];
   //$DIR=$parm['DIR_PUB'];
	//$procs = file_get_contents($DIR."/lib/$file");
	//$this->LogEntry("Program is expected to be in: ".$DIR."/lib/");

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
	
    -- Begin by assigning a sequence of zero to all
    -- columns that have no dependencies, the "leaf" columns
	UPDATE zdd.column_seqs set sequence = 0 
	  WHERE NOT EXISTS (Select table_id 
				FROM zdd.column_deps a
				 WHERE a.table_id = zdd.column_seqs.table_id
				   AND a.column_id = zdd.column_seqs.column_id
               AND a.table_id = a.table_dep
                                  );

	while rowcount > 0 LOOP
        -- In plain english this means:
        -- Set all columns at the next sequence level
        -- if they are not sequenced and are dependent
        -- on previously sequenced columns
        --   FINS: A list of columns dependent on
        --         columns that are already sequenced
		UPDATE zdd.column_seqs set sequence = lnSeq
        	  FROM (SELECT t1.table_id,t1.column_id
                  FROM zdd.column_deps t1
                  JOIN zdd.column_seqs t2 
                    ON t1.table_dep  = t2.table_id 
                   AND t1.column_dep = t2.column_id
                 GROUP BY t1.table_id,t1.column_id
                HAVING MIN(t2.sequence) >= 0) fins
	      WHERE zdd.column_seqs.table_id  = fins.table_id
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
   
   return true;
}
// ==========================================================
// HC_DDRMake and related routines, make the server-side
// working tables
// ==========================================================
function DDRMake() {
	$defColWidth=$this->defColWidth;
	global $parm;

	// Load and convert the DD arrays
	$this->LogStage("Bootstrapping the data dictionary tables");
   $specboot = $parm["DIR_PUB"]."lib/".$parm["SPEC_BOOT"].".add";
      
	$this->DBB_LoadAddFile($specboot,$this->ddarr);
   
   // public service detour.  Write out a generated file of the ddarr 
   // array, which is later used by documentation generation stuff
   $fileOut = 
"<?php 
// ================================================================
// GENERATED CODE.  Associative Array version of AndroDBB.add
// File generated: ".date("l dS of F Y h:i:s A")."
// ================================================================ 
\$ddmeta = array(
".$this->zzArrayAsCode($this->ddarr,1)."
\t);
?>";
   $this->zzFileWriteGenerated($fileOut,"dd_AndroDBB.php");
   
	
	$retval = true;
	$this->DDRMake_Make("_c");
	$this->DDRMake_Make("_r");
	//$this->DDRMake_Make("_t");	// for pulling test results later
	
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
        "  cmdsubseq int,".
		"  defines varchar(60),".  
		"  cmddesc char(50),".
		"  cmdsql text,".
		"  cmderr varchar(1000),".
		"  tsbeg timestamp,".
		"  tsend timestamp,".
		"  elapsed int,".
		"  executed_ok char(1)) WITH OIDS";
	$retval = $retval && $this->SQL($strSQL);
   $strSQL = "CREATE INDEX ddl_cmdseq ON zdd.ddl (cmdseq,cmdsubseq)";
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
	$dd = &$this->ddarr["data"];
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
				$t = $this->ddarr["data"]["column"][$c]["type_id"];
				$flat[$c] = array("primary_key"=>"N", "type_id"=>"");
				foreach ($column as $prop=>$val) { $flat[$c][$prop]=$val; }
				$flat[$c]["type_id"] = $t;
				$cols   
               .=$this->AddComma($cols)   
               .$c." ".
               $this->DDRMake_Make_Type($t
                  ,$this->zzArray($this->ddarr["data"]["column"][$c],"colprec")
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
				foreach ($this->ddarr["data"]["table"][$tp]["column"] as $c=>$column) {
					if ($this->zzArray($column,"primary_key")=="Y") {
						$t = $this->ddarr["data"]["column"][$c]["type_id"];
						$flat[$fkp.$c.$fks] = array("primary_key"=>$this->zzArray($fkey,"primary_key"), "type_id"=>$t);
						
						$cols.=$this->AddComma($cols).$fkp.$c.$fks." ".$this->DDRMake_Make_Type($t,$this->zzArray($this->ddarr["data"]["column"][$c],"colprec"));
					}
				}
				
			}
		}
		
		$this->ddflat[$tabname] = $flat;
		
		$SQL = "CREATE TABLE zdd.".$tabname.$sSuffix." (".$cols.$SQLCommon;
		$this->SQL($SQL);

		// Generate pk list and put this stuff into the simulated mini $this->utabs global
		$pklist = "";
		foreach ($flat as $colname=>$colprops) {
			if ($colprops["primary_key"]=="Y") { $pklist.=$this->AddComma($pklist).$colname; }
		}
		$this->utabs[$tabname] = array("flat"=>$flat,"pk"=>$pklist);
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
		"Select tablename,indexname,indexdef 
           FROM pg_indexes
           JOIN zdd.tables_c on pg_indexes.tablename = zdd.tables_c.table_id
		 WHERE schemaname='public'");
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
        $this->ddarr["data"]
        ,$sfx
    );
    $this->LogEntry("Populating reference tables in series $sfx");
    $this->DBB_LoadContent(true,$this->ddarr['content'],"zdd.",$sfx);
    
    // Load any library specification
    $sfx="_c";
    $spec_lib= $parm["DIR_PUB"]."lib/".$parm["SPEC_LIB"].".add";
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
    $this->content = array_merge($this->content,$ta['content']);
    
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
                $this->LogEntry("Looking for spec in YAML format: ");
                $this->LogEntry("   ".$spec);
            }
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
                    foreach($ta['content'] as $table_id=>$values) {
                        if(!isset($values[2])) {
                            $this->logEntry("");
                            $this->logEntry(">>> ERROR");
                            $this->logEntry(">>> Error in CONTENT for table $table_id");
                            $this->logEntry(">>> There do not appear to be any values.");
                            $this->logEntry(">>>  The correct format is: ");
                            $this->logEntry(">>>  content table_id:       ");
                            $this->logEntry(">>>      columns: [ col1,col2 ]");
                            $this->logEntry(">>>      values:            ");
                            $this->logEntry(">>>          - [ val1, val2 ]");
                            $this->logEntry(">>>          - [ val1, val2 ]");
                            $this->logEntry(">>>          - [ val1, val2 ]");
                            return false;
                        }
                        
                        
                        // KFD 3/1/08, major fix to content loading on YAML 
                        $colnames = $values[1];
                        $colnames['__type']='columns';
                        $this->content[$table_id][] = $colnames;
                        
                        unset($values[2]['__type']);
                        foreach($values[2] as $colvalues) {
                            $this->content[$table_id][]=array_merge(
                                array('__type'=>'values')
                                ,$colvalues
                            );
                        }
                        // KFD 3/1/8 END CHANGES
                    }
                }
            }
        }
    }
    return $retval;
}

//   A walk in the park.  Or perhaps a walk of the tree.
//
function SpecLoad_ArrayToTables($arr,$cLoadSuffix,$parent_row=array(),$parent_prefix="") {
	
    global $srcfile;
    global $parm;
	$retval = true;
	
	foreach ($arr as $keyword=>$object) {
		// Ignore prop/value pairs at even-numbered rows
		if (! is_array($object)) { continue; }

		// Now use $keyword to get table name we will insert into
        //$this->LogEntry("$parent_prefix - $keyword");
		$table     = $this->ddarr["meta"]["keyword"][$parent_prefix.$keyword]["table"];
		$pkcolname = $this->ddarr["meta"]["keyword"][$parent_prefix.$keyword]["keycol"];
		$keystub   = $this->zzArray($this->ddarr["meta"]["keyword"][$parent_prefix.$keyword],"keystub");
		//echo "TABLE is $table, pk and keystub are $pkcolname and $keystub <br>";

		$dd = &$this->ddarr["data"]["table"][$table];
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
				if (isset($this->ddflat[$table][$colname])) {
					if ($this->zzArray($this->ddflat[$table][$colname],"primary_key")=="Y") {
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
         // KFD 4/9/08, support for lowercase automations by uppercasing
         // them on the way in
         if(isset($row['automation_id'])) {
             $row['automation_id'] = strtoupper($row['automation_id']);
         }
            
			// Finally, execute it
         $row['srcfile']=$srcfile;
			$retval = $retval &&
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
			$this->DBB_Insert("zdd.",$pfx."chainargs",$cLoadSuffix,$newrow);
		}
	}
}

// ==========================================================
// REGION: Specification Pre-Validation
// ==========================================================
function SpecFlattenValid() {
	// the only known hazard at present is a table that
	// has an fk to itself, and that fk is part of its pk.
	// That would be an infinite regression, can't do that.
	//
    $this->LogStage("Validation prior to flattening");
    $errors = 0;
    $errors += $this->SpecFlattenValidDD();
    $errors += $this->SpecFlattenValidUser();
    return ($errors==0);
}
 
function SpecFlattenValidDD() {
    $this->LogEntry("Validating core entries in AndroDBB");
    
    $errors = 0;
    $errors+=$this->SFVDD_One('compopers'     ,'compoper');
    $errors+=$this->SFVDD_One('funcopers'     ,'funcoper');
    $errors+=$this->SFVDD_One('cascadeactions','cascade_action');
    $errors+=$this->SFVDD_One('types'         ,'type_id');
    $errors+=$this->SFVDD_One('type_exps'     ,'type_id,srvtype');
    $errors+=$this->SFVDD_One('uidisplays'    ,'uidisplay');
}
function SFVDD_One($table_id,$collist) {
    $errors = 0;
    $sq="SELECT count(*) as _cnt,$collist
           FROM zdd.{$table_id}_c
          GROUP BY $collist
         HAVING count(*) > 1";
	$results=$this->SQLRead($sq);
	while ($row = pg_fetch_array($results)) {
        $this->LogEntry("");
		$this->LogEntry("ERROR >> Core table (defined in AndroDBB) $table_id");
		$this->LogEntry("ERROR >>    has duplicates on columns $collist");
		$this->LogEntry("");
		$errors++;
    }
    return $errors;    
}


function SpecFlattenValidUser() {
    // Circular dependencies
    $this->LogEntry("Checking for Circular Dependencies");
	$errors = 0;
	$sql = 
		"SELECT table_id FROM zdd.tabfky_c ".
		" WHERE table_id = table_id_par ".
		"   AND primary_key = 'Y'";
	$results=$this->SQLRead($sql);
	while ($row = pg_fetch_array($results)) {
        $this->LogEntry("");
		$this->LogEntry("ERROR >> Table ".$row["table_id"]." has foreign key ");
		$this->LogEntry("ERROR >>      to itself with 'primary_key: Y', this is a ");
		$this->LogEntry("ERROR >>      circular reference and will not work.");
		$this->LogEntry("");
		$errors++;
	}
    
    // Check for duplicate definitions
    $this->LogEntry("Checking for Duplicate Definitions");
    $errors+=$this->SFVUser_one('modules' ,'module','Module');
    $errors+=$this->SFVUser_one('tables'  ,'table_id','Table');
    $errors+=$this->SFVUser_one('uimenu'  ,'menu_page','Menu');
    $errors+=$this->SFVUser_one('columns' ,'column_id','Column');
    $errors+=$this->SFVUser_one('tabcol'  
        ,'table_id,column_id_src,prefix,suffix','Column Placement');
    $errors+=$this->SFVUser_one('groups'  ,'group_id' ,'Group');
    $errors+=$this->SFVUser_one('tabfky'  
        ,'table_id,table_id_par,prefix,suffix','Foreign Key Definition');

    
    // RI failures
    $this->LogEntry("Checking for Invalid References");
	$sql = 
		"SELECT table_id,module FROM zdd.tables_c 
		 WHERE NOT EXISTS (
            SELECT module FROM zdd.modules_c WHERE module=zdd.tables_c.module
         )";
	$results=$this->SQLRead($sql);
	while ($row = pg_fetch_array($results)) {
        $this->LogEntry("");
		$this->LogEntry("ERROR >> Table ".$row["table_id"]." refers to ");
		$this->LogEntry("ERROR >>      undefined module: ".$row['module']);
		$errors++;
	}
	$sql = 
		"SELECT menu_page FROM zdd.uimenu_c 
		 WHERE NOT EXISTS (
            SELECT module FROM zdd.modules_c WHERE module=zdd.uimenu_c.module
         )";
	$results=$this->SQLRead($sql);
	while ($row = pg_fetch_array($results)) {
        $this->LogEntry("");
		$this->LogEntry("ERROR >> Table ".$row["table_id"]." refers to ");
		$this->LogEntry("ERROR >>      undefined module: ".$row['module']);
		$errors++;
	}
    // permxmodules - > modules
	$sql = 
		"SELECT group_id,module FROM zdd.permxmodules_c
		 WHERE NOT EXISTS (
            SELECT module FROM zdd.modules_c WHERE module=zdd.permxmodules_c.module
         )";
	$results=$this->SQLRead($sql);
	while ($row = pg_fetch_array($results)) {
        $this->LogEntry("");
		$this->LogEntry("ERROR >> Group ".$row["group_id"]." refers to ");
		$this->LogEntry("ERROR >>      undefined module: ".$row['module']);
		$errors++;
	}
    // permxmodules - > groups
	$sql = 
		"SELECT group_id,module FROM zdd.permxmodules_c
		 WHERE NOT EXISTS (
            SELECT group_id
              FROM zdd.groups_c WHERE group_id=zdd.permxmodules_c.group_id
         )";
	$results=$this->SQLRead($sql);
	while ($row = pg_fetch_array($results)) {
        $this->LogEntry("");
		$this->LogEntry("ERROR >> Module ".$row["module"]." refers to ");
		$this->LogEntry("ERROR >>      undefined group: ".$row['group_id']);
		$errors++;
	}
    
    // columns -> automations
	$sql = 
		"SELECT column_id,automation_id FROM zdd.columns_c
          WHERE coalesce(automation_id,'') <> ''
		    AND NOT EXISTS (
            SELECT automation_id
              FROM zdd.automations_c
             WHERE automation_id=zdd.columns_c.automation_id
         )";
	$results=$this->SQLRead($sql);
	while ($row = pg_fetch_array($results)) {
        $this->LogEntry("");
		$this->LogEntry("ERROR >> Column ".$row["column_id"]." refers to ");
		$this->LogEntry("ERROR >>      undefined automation: ".$row['automation_id']);
		$errors++;
	}
    // columns -> types
	$sql = 
		"SELECT column_id,type_id FROM zdd.columns_c
		  WHERE NOT EXISTS (
            SELECT type_id
              FROM zdd.types_c
             WHERE type_id=zdd.columns_c.type_id
         )";
	$results=$this->SQLRead($sql);
	while ($row = pg_fetch_array($results)) {
        $this->LogEntry("");
		$this->LogEntry("ERROR >> Column ".$row["column_id"]." refers to ");
		$this->LogEntry("ERROR >>      undefined type: ".$row['type_id']);
		$errors++;
	}
    // tabcol -> automations
	$sql = 
		"SELECT table_id,column_id,automation_id FROM zdd.tabcol_c
          WHERE coalesce(automation_id,'') <> ''
		    AND NOT EXISTS (
            SELECT automation_id
              FROM zdd.automations_c
             WHERE automation_id=zdd.tabcol_c.automation_id
         )";
	$results=$this->SQLRead($sql);
	while ($row = pg_fetch_array($results)) {
        $this->LogEntry("");
		$this->LogEntry("ERROR >> Column ".trim($row['table_id'])
            .'.'.trim($row["column_id"])." refers to "
        );
		$this->LogEntry("ERROR >>      undefined automation: ".$row['automation_id']);
		$errors++;
	}
    // Make sure all column definitions are real  
    $sq='Select table_id,column_id_src
           FROM zdd.tabcol_c 
          WHERE NOT EXISTS (select * from zdd.columns_c
                              WHERE column_id = zdd.tabcol_c.column_id_src)';
	$results=$this->SQLRead($sq);
	while ($row = pg_fetch_array($results)) {
        $errors++;
        $this->LogEntry("");
		$this->LogEntry("ERROR >> Table ".$row["table_id"]." names");
		$this->LogEntry("ERROR >>    undefined column ".$row['column_id_src']);
    }
    
    // Make sure all foreign key definitions name real tables.  
    $sq='Select table_id,table_id_par 
           FROM zdd.tabfky_c 
          WHERE NOT EXISTS (select * from zdd.tables_c
                              WHERE table_id = zdd.tabfky_c.table_id_par)';
	$results=$this->SQLRead($sq);
	while ($row = pg_fetch_array($results)) {
        $errors++;
        $this->LogEntry("");
		$this->LogEntry("ERROR >> Table ".$row["table_id"]." has foreign key ");
		$this->LogEntry("ERROR >>    to undefined table ".$row['table_id_par']);
    }
    // Make sure all foreign key definitions name real tables.  
    $sq='Select table_id,table_id_par 
           FROM zdd.tabfky_c 
          WHERE NOT EXISTS (select * from zdd.tables_c
                              WHERE table_id = zdd.tabfky_c.table_id)';
	$results=$this->SQLRead($sq);
	while ($row = pg_fetch_array($results)) {
        $errors++;
        $this->LogEntry("");
		$this->LogEntry("ERROR >> Table ".$row["table_id_par"]." names");
		$this->LogEntry("ERROR >>    undefined child table ".$row['table_id']);
    }
          

    // Make sure all chain funcopers and compopers are real  
    $sq="Select table_id,column_id,funcoper
           FROM zdd.colchaintests_c 
          WHERE coalesce(funcoper,'') <> ''
            AND NOT EXISTS (
              select * from zdd.funcopers_c
               WHERE funcoper = zdd.colchaintests_c.funcoper
            )";
	$results=$this->SQLRead($sq);
	while ($row = pg_fetch_array($results)) {
        $errors++;
        $this->LogEntry("");
		$this->LogEntry("ERROR >> Column ".trim($row['table_id'])
            .'.'.trim($row["column_id"])." has chain that refers to "
        );
		$this->LogEntry("ERROR >>      undefined operator: ".$row['funcoper']);
    }
    // Make sure all chain funcopers and compopers are real  
    $sq="Select table_id,column_id,compoper
           FROM zdd.colchaintests_c 
          WHERE coalesce(compoper,'') <> ''
            AND NOT EXISTS (
              select * from zdd.compopers_c
               WHERE compoper = zdd.colchaintests_c.compoper
            )";
	$results=$this->SQLRead($sq);
	while ($row = pg_fetch_array($results)) {
        $errors++;
        $this->LogEntry("");
		$this->LogEntry("ERROR >> Column ".trim($row['table_id'])
            .'.'.trim($row["column_id"])." has chain that refers to "
        );
		$this->LogEntry("ERROR >>      undefined comparison: ".$row['compoper']);
    }

   
	$this->LogEntry("Checking for table-name = module-name");
	$results = $this->SQLRead(
		"select table_id 
         FROM zdd.tables_c  t
         JOIN zdd.modules_c m ON t.table_id = m.module"
    );
    $x=0;
    while($row=pg_fetch_array($results)) {
        $t = $row['table_id'];
        $this->LogEntry("ERROR:  Module $t has same name as table $t "); 
        $errors++;
        $x++;
    }
    if($x > 0 ) {
        $this->LogEntry(" --> PROBLEM WITH MODULE OR TABLE NAME");
        $this->LogEntry(" --> Modules and tables may not have the ");
        $this->LogEntry(" --> same name.  This is actually because");
        $this->LogEntry(" --> our default user interface will     ");
        $this->LogEntry(" --> end up in an infinite loop when it  ");
        $this->LogEntry(" --> tries to build a menu.             ");
        $this->LogEntry(" -->            ");
        $this->LogEntry(" --> Change either the module or table ");
        $this->LogEntry(" --> names that are listed above.      ");
    }
    
    
    return $errors;    
}

function SFVUser_One($table_id,$collist,$name) {
    $errors = 0;
    $sq="SELECT count(*) as _cnt,$collist
           FROM zdd.{$table_id}_c
          GROUP BY $collist
         HAVING count(*) > 1";
	$results=$this->SQLRead($sq);
	while ($row = pg_fetch_array($results)) {
        $this->LogEntry("");
        $this->LogEntry("ERROR >> Duplicate $name definition: ");
        $acols = explode(",",$collist);
        foreach($acols as $colname) {
            $this->LogEntry("ERROR >>    $colname => ".$row[$colname]);
        }
        $errors++;
    }
    return $errors;    
}


// ==========================================================
// REGION: Spec Flatten.  Runs out table definitions,
// and runs out security definitions
// ==========================================================
function SpecFlatten() {
	$this->LogStage("Flattening Definitions");
	$retval = true;
	$retval = $retval && $this->SpecFlatten_ColumnsAll();
	$retval = $retval && $this->SpecFlatten_ColumnsPre();
	$retval = $retval && $this->SpecFlatten_Runout();
    $retval = $retval && $this->SpecFlatten_FixFKO();
	//$retval = $retval && $this->SpecFlatten_Tables();
	$retval = $retval && $this->SpecFlatten_HARDCODE();
	$retval = $retval && $this->SpecFlatten_ColumnDeps();
	$retval = $retval && $this->SpecFlatten_Security();
	return $retval;
}

function SpecFlatten_ColumnsAll() {
	$this->LogEntry("Adding 'alltables' columns to all tables");
	// Distribute into tables those columns that have the
	// "alltables" flag set that are not already in the table.
	//
	
	$this->SQL("
		insert into zdd.tabcol_c (
            table_id,column_id,column_id_src,prefix,suffix
           ,primary_key,uisearch,uino,uicolseq)
		select t.table_id,c.column_id,c.column_id,'',''
               ,'N','N',c.uino,'99999'
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


// KFD 11/27/07.  Resolved.  We split the flattening process into
//    two steps.  Step 1 is *placement*, where we work out which
//    columns are in which tables.  Step 2 is properties, where
//    we pull properties from either the parent table or tabcol
//    In the process, this routine was actually left alone.
// KFD 6/1/07.  It was discovered that this routine is probably
//    the source of a lot of confusion.  The problem is that it
//    subverts the actions of DBB_Runout because it does not take
//    into account the sequencing that is done in that routine.
//    Therefore that routine has corrections that were made w/o
//    knowing that the problem was coming from here.  The solution
//    is most likely to move the actions of this codei nto DBB_Runout
//    and have the actions properly sequenced.
function SpecFlatten_ColumnsPre() {
	$this->LogEntry("Adding automated columns defined in foreign keys");
   
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
    //h*print_r($sql);
    
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
    //h*print_r($sql);
   
   
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
    //h*print_r($sql);
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
   AND UPPER(x.automation_id) IN ('FETCH','FETCHDEF','DISTRIBUTE','SYNCH')";   
   $this->SQL($sql);
    //h*print_r($sql);
   
   return true;
}   


function SpecFlatten_Runout() {
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

	$this->LogEntry("Flattening table definitions");
	$r1 = $this->SQLRead("Select * FROM zdd.tables_c ORDER BY table_seq");
    $rows = pg_fetch_all($r1);
    foreach($rows as $row) {
        $table_id = $row['table_id'];
        $this->utabs[$table_id] = $row;
		$this->utabs[$table_id]["indexes"] = array();
        
        //$this->LogEntry($table_id);
        
        // Insert columns first.  Must do columns first so we
        // can have tables that have fk's to themselves
        $sq="INSERT INTO zdd.tabflat_c (
                 table_id,column_id_src,column_id,suffix,prefix
                ,primary_key,uisearch
                ,colprec,colscale,colres,type_id
                ,table_id_fko
                ,uicolseq,uisearch_ignore_dash
                ,formula,formshort,dispsize
             )
             SELECT 
                  '$table_id',tc.column_id_src
                 ,tc.column_id
                 ,tc.suffix,tc.prefix,tc.primary_key,tc.uisearch
                 ,c.colprec,c.colscale,c.colres,c.type_id
                 ,'' as table_id_fko
                 ,trim(uicolseq)
                 ,case when tc.uisearch_ignore_dash in ('Y','N')
                       then tc.uisearch_ignore_dash
                       when c.uisearch_ignore_dash in ('Y','N')
                       then c.uisearch_ignore_dash
                       else t.uisearch_ignore_dash end as uisearch_ignore_dash
                 ,t.formula,t.formshort,t.dispsize
             FROM zdd.tabcol_c tc
             JOIN zdd.columns_c   c ON tc.column_id_src = c.column_id
             JOIN zdd.type_exps_c t ON c.type_id        = t.type_id
            WHERE tc.table_id = '$table_id'"; 
        $this->SQL($sq);

        // Create flattened column definitions from foreign 
        // keys.  Insert all non-mutable 
        $sq="INSERT INTO zdd.tabflat_c (
                 table_id,column_id_src,column_id,suffix,prefix
                ,description
                ,primary_key,uisearch
                ,colprec,colscale,colres,type_id
                ,table_id_fko
                ,uicolseq
                ,formula,formshort,dispsize
             )
             SELECT 
                  '$table_id',x1.column_id
                 ,rtrim(x1.prefix) || rtrim(x1.column_id) || rtrim(x1.suffix)
                 ,x1.suffix,x1.prefix
                 ,x1.description
                 ,x1.primary_key,x1.uisearch
                 ,c.colprec,c.colscale,c.colres,c.type_id
                 ,x1.table_id_fko
                 ,trim(uicolseq)
                 ,t.formula,t.formshort,t.dispsize
             FROM (
                 SELECT column_id,column_id_src,suffix,prefix
                       ,MAX(description)                as description
                       ,MAX(COALESCE(primary_key ,'N')) as primary_key
                       ,MAX(COALESCE(uisearch    ,'N')) as uisearch
                       ,MAX(COALESCE(table_id_fko,'' )) as table_id_fko
                       ,MAX(uicolseq)                   as uicolseq
                   FROM (
                     -- This is the core data from the flat table
                     SELECT f.column_id,f.column_id_src
                           ,fk.suffix,fk.prefix
                           ,f.description
                           ,fk.primary_key,fk.uisearch
                           ,fk.table_id_par as table_id_fko
                           ,TRIM(fk.uicolseq) || '.' || TRIM(f.uicolseq) as uicolseq
                       FROM zdd.tabfky_c  fk
                       JOIN zdd.tabflat_c f   ON fk.table_id_par = f.table_id
                      WHERE fk.table_id = '$table_id'
                        AND f.primary_key = 'Y'
                        ) x2
                 GROUP BY column_id,column_id_src,suffix,prefix
                 ) x1
             JOIN zdd.columns_c   c ON x1.column_id_src = c.column_id
             JOIN zdd.type_exps_c t ON c.type_id        = t.type_id
            WHERE NOT EXISTS (
                SELECT * from zdd.tabflat_c 
                 WHERE table_id = '$table_id'
                   AND column_id = rtrim(x1.prefix) 
                       || rtrim(x1.column_id)
                       || rtrim(x1.suffix)
                )";
        $this->SQL($sq);
        
        // Second action updates mutable properties with values
        // picked from columns table, overridden by values from
        // tabcol table.
        //
        $sq="UPDATE zdd.tabflat_c 
                SET range_to      = res.range_to
                   ,pk_change     = res.pk_change
                   ,range_from    = res.range_from
                   ,description   = res.description
                   ,tooltip       = res.tooltip
                   ,value_min     = res.value_min
                   ,value_max     = res.value_max
                   ,uirows        = res.uirows
                   ,uicols        = res.uicols
                   ,uiwithnext    = res.uiwithnext
                   ,uino          = res.uino
                   ,uiro          = res.uiro
                   ,automation_id = res.automation_id
                   ,auto_formula  = res.auto_formula
                   ,required      = res.required
                   ,uiinline      = res.uiinline
               FROM (
                   SELECT tc.column_id 
                         ,tc.pk_change
                         ,tc.range_to
                         ,tc.range_from
                         ,COALESCE(tc.value_min,c.value_min) as value_min
                         ,COALESCE(tc.value_max,c.value_max) as value_max
                         ,".$this->col3res('description')."
                         ,".$this->col3res('tooltip')."
                         ,".$this->col3num('uirows')."
                         ,".$this->col3num('uicols')."
                         ,".$this->col3res('uiwithnext')."
                         ,".$this->col3res('uino')."
                         ,".$this->col3res('uiro')."
                         ,".$this->col3res('automation_id')."
                         ,".$this->col3res('auto_formula')."
                         ,".$this->col3res('required')."
                         ,".$this->col3res('uiinline')."
                     FROM zdd.tabcol_c  tc
                     JOIN zdd.columns_c c  ON tc.column_id_src = c.column_id
                    WHERE tc.table_id = '$table_id'
               ) res 
             WHERE zdd.tabflat_c.table_id  = '$table_id'
               AND zdd.tabflat_c.column_id = res.column_id";
        $this->SQL($sq);
        
        // Action 3: Columns created with suffix/prefix need to be
        // put back into tabcol so they are valid if referenced
        // in other tables, automations, etc.
        $SQL = 
            "insert into zdd.columns_c ( 
              column_id, description 
              ,automation_id, auto_table_id, auto_column_id, auto_formula
              ,ins,uiro,uino,required,dispsize,uiwithnext,prefix_table_name
              ,value_min,value_max
              ,type_id,colprec,colscale,colres
              ,uiinline,alltables )
           select
              f.column_id, f.description 
              ,c.automation_id, c.auto_table_id, c.auto_column_id, c.auto_formula
              ,c.ins,c.uiro,c.uino,c.required,c.dispsize,c.uiwithnext,c.prefix_table_name
              ,c.value_min,c.value_max
              ,c.type_id,c.colprec,c.colscale,c.colres
              ,c.uiinline,'N' 
             from zdd.tabflat_c f
             JOIN zdd.columns_c c ON f.column_id_src = c.column_id
            where f.table_id = '$table_id'
              and f.column_id NOT IN (
                  SELECT column_id FROM zdd.columns_c
                  )";
        $this->SQL($SQL);
    }
    return true;
}

function col3res($colname) {
    return "CASE WHEN COALESCE(tc.$colname,'') <> '' 
                 THEN tc.$colname
                 ELSE c.$colname END AS $colname";
}
function col3num($colname) {
    return "CASE WHEN COALESCE(tc.$colname,0) <> 0 
                 THEN tc.$colname
                 ELSE c.$colname END AS $colname";
}


function SpecFlatten_FixFKO() {
    // KFD 4/2/08, If you have an FK, and also define the column,
    //      the table_id_fko value is lost.  This puts it back in
    $sql="
update zdd.tabflat_c 
   SET table_id_fko = x.table_id_par
      ,column_id_fko = x.column_id_fko
  FROM
(
select fk.table_id,fk.table_id_par
      ,fk.prefix || tf.column_id || fk.suffix as column_id
      ,tf.column_id as column_id_fko
  from zdd.tabfky_c  fk
  JOIN zdd.tabflat_c tf ON fk.table_id_par = tf.table_id
 WHERE tf.primary_key = 'Y'
) x
 WHERE zdd.tabflat_c.table_id = x.table_id
   AND zdd.tabflat_c.column_id= x.column_id
   AND zdd.tabflat_c.table_id_fko = ''";
     $this->SQL($sql);
     return true;
}

function SpecFlatten_Tables()
{
	
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
		$this->utabs[$row["table_id"]] = $row;
		$this->utabs[$row["table_id"]]["indexes"] = array();
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
 where automation_id IN ('FETCH','FETCHDEF','DISTRIBUTE','SUM','COUNT','MIN','MAX','SYNCH')
   AND POSITION('.' IN auto_formula)<>0;");

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
   // KFD 5/30/07, add filtering out self-dependencies, we
   //    don't need to know and it gives false sequencing errors
   // KFD 6/ 8/07, putting in the filter caused other problems,
   //              the sequencing of calculations was thrown
   //              way off.
   // TROUBLE.  This is unresolved.  You cannot have a column constraint
   //           expressed in terms of its own value.  Very Bad.
   // KFD 12/21/07 How about filtering out dependencies based on
   //              constraints?  That makes much more sense.
   //           EXPERIMENTAL  EXPERIMENTAL tried w/project PROMOT
	$sql = "
		INSERT INTO zdd.column_deps 
		 (table_id,column_id,table_dep,column_dep,automation_id) 
		SELECT DISTINCT table_id,column_id
                       ,table_id,column_id_arg,'EXTEND' 
		  FROM zdd.colchainargs_c 
		 WHERE zdd.colchainargs_c.column_id_arg <> ''
           AND zdd.colchainargs_c.chain <> 'cons'
         ";
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
                                             OR automation_id='SYNCH'
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

function SpecFlatten_Security() {
   
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
      ,''
  FROM zdd.tabfkygroups_c fkg
  JOIN zdd.tabflat_c      flt ON fkg.table_id_par = flt.table_id
 WHERE flt.primary_key = 'Y'"; 
   $this->SQL($sq);
      
      	return true;
}

function specFlattenRowCol() {
   $this->LogStage("Flattening Definitions, Pass 2 Row and Column Security");
   // Before anything, make a table where we will store the
   // cross reference of effective groups to real groups
   //
   $this->SQL(
      "SELECT group_id,group_id as group_id_eff
         INTO zdd.groupsx_c
         FROM zdd.groups_c
        WHERE 1=0"
   );
   $this->SQL("create index gx1 ON zdd.groupsx_c (group_id)");
   $this->SQL("create index gx2 ON zdd.groupsx_c (group_id_eff)");
   $this->SQL("create index gx3 ON zdd.groupsx_c (group_id,group_id_eff)");
   $this->SQL(
      "SELECT cast(group_id as varchar) as group_id_eff
               ,cast('N' as char(1)) as flag_used,grouplist,md5_eff
         INTO zdd.groups_eff_c
         FROM zdd.groups_c
        WHERE 1=0"
   );
   
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

   $this->LogEntry("Flagging tables with row security, pass 1 of 2");
   $this->SQL("UPDATE zdd.tables_c SET permrow='N',permcol='N'");
   $this->SQL("
      UPDATE zdd.tables_c
         SET permrow = 'Y'
        FROM ( SELECT table_id
                 FROM zdd.perm_cols_c
                WHERE coalesce(permrow,'')      <> ''
                   OR coalesce(table_id_row,'') <> ''
                GROUP BY table_id
             ) x
        WHERE zdd.tables_c.table_id = x.table_id"
   );
   $this->LogEntry("Flagging tables with row security, pass 2 of 2");
   $this->SQL("
      UPDATE zdd.tables_c
         SET permrow = 'Y'
        FROM ( SELECT table_id
                 FROM zdd.permxtablesrow_c
                GROUP BY table_id
             ) x
        WHERE zdd.tables_c.table_id = x.table_id"
   );

   $this->LogEntry("Flagging tables with column security"); 
   $this->SQL("
update zdd.tables_c set permcol = 'Y'
  FROM (select table_id 
          from zdd.perm_cols_c
         WHERE coalesce(permsel,'') <> ''
            OR coalesce(permupd,'') <> ''
       ) x
  WHERE zdd.tables_c.table_id = x.table_id"
   );
   $this->SQL("
select t.table_id,g.group_id_eff,cast('     ' as char(5)) as view_id
  INTO zdd.table_views_c
  from zdd.tables_c t,zdd.groups_eff_c g
 where t.permcol = 'Y'
 order by table_id,group_id_eff");
   $this->SQL("create index tvc1 on zdd.table_views_c (table_id)");
   $this->SQL("create index tvc2 on zdd.table_views_c (group_id_eff)");
   $this->SQL("create index tvc3 on zdd.table_views_c (table_id,group_id_eff)");
   
   return true;      

}

// Helper to specflattenrowcol 
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
   if($gcount<1) return;
   
   // Make group name and effective hash
   global $parm;
   //if($gcount > 2) {
      $gnumb++;
      $gsuffix=str_pad($gnumb,5,'0',STR_PAD_LEFT);
   //}
   //else {
   //   $gsuffix=substr($egroupname,strlen($parm['APP'])+1);
   //}
   $gname = $parm['APP']."_eff_".$gsuffix;
   $md5_eff= md5($egroupname);
   
   // Insert the row
   $this->SQL(
      "INSERT INTO zdd.groups_eff_c (group_id_eff,flag_used,md5_eff,grouplist) 
       VALUES ('$gname','N','$md5_eff','$egroupname')"
   );

   // Excplode the groups and list them individually also
   $groups=explode("+",$egroupname);
   foreach($groups as $group) {
      $this->SQL(
         "INSERT INTO zdd.groupsx_c (group_id,group_id_eff) 
          VALUES ('$group','$gname')"
      );
   }
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
    $this->LogEntry("Retrieving lists of child tables");$this->SpecHandle_Lists_ChildTables();
    return $retval;
}


// TODO:  The routines below that pull PK and FK would
//        go faster if they  pulled their info from here.
function SpecHandle_Lists_Flat() {

	$tables = array_keys($this->utabs);
	foreach ($tables as $table) {
		$this->utabs[$table]["flat"]=array();
		
		// This is the flat stuff
      $sql="Select * From zdd.tabflat_c where table_id = '$table'";
		$results = $this->SQLRead($sql);
      
		$ilimit = pg_num_rows($results);
		$i=0;
		if ($ilimit>0) {
			while ($row = pg_fetch_array($results,$i,PGSQL_ASSOC)) {
				$this->utabs[$table]["flat"][$row["column_id"]]=$row;
				$i++;
				if ($i==$ilimit) { break; }
			}
		}
	}
}

function SpecHandle_Lists_PK()
{	
	foreach ($this->utabs as $utab) {
		$results=$this->SQLRead(			
			"select column_id from zdd.tabflat_c ".
			" where table_id = '". $utab["table_id"] . "'".
			"   and primary_key = 'Y'");
		$pkarr = $this->SQL_fetch_all_1col($results);
		$pklist = implode(",",$pkarr);
		
		$this->utabs[$utab["table_id"]]["pk"] = $pklist; 
	}
   return true;
}

function SpecHandle_Lists_Req()
{	
	foreach ($this->utabs as $utab) {
		$results=$this->SQLRead(			
			"select column_id from zdd.tabflat_c ".
			" where table_id = '". $utab["table_id"] . "'".
			"   and required = 'Y'");
		$reqarr = $this->SQL_fetch_all_1col($results);
		$reqlist = implode(",",$reqarr);
		
		$this->utabs[$utab["table_id"]]["required"] = $reqlist; 
	}
}

function SpecHandle_Lists_FK()
{
	
    $retval=true;
    $rc=0;
    $results = $this->SQLRead("Select * FROM zdd.tabfky_c");
    
    while ($row = pg_fetch_array($results)) {
        $suffix = trim($row["suffix"]);
        $prefix = trim($row["prefix"]);
        // Get details on pk/fk
        $fk = $pk = $both = $match = "";
        $cols_list = $this->utabs[trim($row["table_id_par"])]["pk"];
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
         //print_r($this->utabs[$row['table_id_par']]);
         //echo "</pre>";
         if($this->utabs[$row['table_id_par']]['flat'][$col]['range_from']<>'') {
            //$this->LogEntry(
            //   " -> FK ".$row['table_id']
            //   ." to ".$row['table_id_par']
            //   ." skipping RANGE-FROM ".$col
            //);
            continue;
         }
			if ($fk!="")    $fk.=",";
			if ($pk<>"")    $pk.=",";
			if ($both<>"")  $both.=",";
         if ($match<>"") $match.=" AND ";
			$pk.=$col; 
			$fk.=$prefix.$col.$suffix; 
			$both.=$prefix.$col.$suffix.":".$col;
         if($this->utabs[$row['table_id_par']]['flat'][$col]['range_to']<>'') {
             // KFD 10/10/07, range keys with different types
             list($rzero,$rinfi,$rcast)=$this->getRange(
                 $this->utabs[$row['table_id_par']]['flat'][$col]['formshort']
             );
            $match
               .= "COALESCE(chd.".$prefix.$col.$suffix.",$rzero::$rcast) BETWEEN "
               ."COALESCE(par.".$col.",$rzero::$rcast) AND COALESCE(par."
               .$this->utabs[$row['table_id_par']]['flat'][$col]['range_to']
               .",$rinfi::$rcast)";
            //$this->LogEntry($match);
         }
         else {
            $match .= "chd.".$prefix.$col.$suffix." = par.".$col;
         }
         
		}
		$combo = 
			trim($row["table_id"])."_".
			trim($row["table_id_par"])."_".
			$suffix;

		$this->ufks[$combo] = array(
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
            
			$this->utabs[$table_id]["indexes"][$idx_name] = array("unique"=>$index["idx_unique"],"cols"=>$cols);
		}
	}
}

function SpecHandle_Lists_Projections() {

	$respre = $this->SQLRead("Select table_id,projection FROM zdd.tabprojcols_c GROUP BY table_id,projection");
	$restables = pg_fetch_all($respre);
	foreach ($restables as $restable) {
		$table_id = trim($restable["table_id"]);
		$projection=trim($restable["projection"]);
		if (!isset($this->utabs[$table_id]["projections"])) { $this->utabs[$table_id]["projections"] = array(); }
		
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
		$this->utabs[$table_id]["projections"][$projection] = $cols;
		//$this->LogEntry("   $table_id projects $projection as $cols");
	}
}

function SpecHandle_Lists_ChildTables() {
	global $ukids;

	$qres = $this->SQLRead(
        "Select table_id,suffix,prefix,table_id_fko
           FROM zdd.tabflat_c WHERE table_id_fko <> ''
          GROUP BY table_id,suffix,prefix,table_id_fko"
    );
    while($row=pg_fetch_array($qres)) {
        $tfko = trim($row['table_id_fko']);
        $ukids[$tfko][] = $row;
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

	//$this->LogStage("Generating Security Stored Procedures");
	//$retval = $retval && $this->SpecDDL_SPs();
	
	$this->LogStage("Generating DDL for triggers");
	$retval = $retval && $this->SpecDDL_Triggers();
	return $retval;
}

function SpecDDL_Indexes()
{
	$this->SpecDDL_Indexes_Normal();
    $this->SpecDDL_Indexes_queuepos();
    $this->SpecDDL_Indexes_dominant();
	$this->SpecDDL_Indexes_keys();
}

function SpecDDL_Indexes_queuepos() {
	$this->LogEntry("Generating index definitions for QUEUEPOS columns");
	$results = $this->SQLREAD(
		"Select table_id,column_id FROM zdd.tabflat_c".
		" WHERE automation_id = 'QUEUEPOS'"
   );
   $qps = pg_fetch_all($results);
   if(is_array($qps)) {
      foreach($qps as $qp) {
         $t = $qp['table_id'];
         $c = $qp['column_id'];
         $idx_name = $t.'_QP_'.$c;
         $sql = "CREATE INDEX $idx_name ON $t ($c)";
         $def_short = "idx:".strtolower("$t:$c");
         $this->SQL(
            "Insert into zdd.ns_objects_c ".
            "(object_id,definition,def_short,sql_create,sql_drop)".
            " values ".
            "('$idx_name','$def_short','$def_short','$sql','')"
         );
      }
   }
}

function SpecDDL_Indexes_dominant() {
	$this->LogEntry("Generating index definitions for DOMINANT columns");
	$results = $this->SQLREAD(
		"Select table_id,column_id,auto_formula FROM zdd.tabflat_c".
		" WHERE automation_id = 'DOMINANT'"
   );
   $qps = pg_fetch_all($results);
   if(is_array($qps)) {
      foreach($qps as $qp) {
         $t = $qp['table_id'];
         $c = $qp['column_id'];
         $c0= $c;
         $af= $qp['auto_formula'];
         // If a table is mentioned, index on that
         if($af<>'') {
             $res = $this->SQLREAD(
                 "Select column_id FROM zdd.tabflat_c
                   WHERE table_id = '$af'
                     AND primary_key = 'Y'
                   ORDER BY uicolseq"
             );
             while($row=pg_fetch_array($res)) {
                 $c.=','.trim($row['column_id']);
             }
         }
         $idx_name = $t.'_DM_'.$c0;
         $sql = "CREATE INDEX $idx_name ON $t ($c)";
         $def_short = "idx:".strtolower("$t:$c");
         $this->SQL(
            "Insert into zdd.ns_objects_c ".
            "(object_id,definition,def_short,sql_create,sql_drop)".
            " values ".
            "('$idx_name','$def_short','$def_short','$sql','')"
         );
      }
   }
}

function SpecDDL_Indexes_Normal() {
	$this->LogEntry("Generating normal (non-key) index definitions");
	foreach ($this->utabs as $utab) {
		
		foreach ($utab["indexes"] as $idx_name=>$index) {
         $idx_name=$utab['table_id'].'_IDX_'.$idx_name;
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
	$this->LogEntry("Generating primary/foreign key index definitions");
	// Use our associative arrays to create indexes on 
	// primary and foreign keys
	foreach ($this->utabs as $utab) {
		if(true) {
			//$this->LogEntry("PK for table ".$utab["table_id"]." on (".$utab["pk"].")");
			$index = $utab["table_id"]."_pk";
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
					$index = $utab["table_id"]."_pk_".$key;
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
	
	foreach ($this->ufks as $ufk) {
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
		       ELSE TRIM(auto_formula) END || '_SEQ_' || TRIM(column_id)),  
        'sequence:' ||  
        LOWER(CASE WHEN auto_formula = '' THEN TRIM(table_id)
		       ELSE TRIM(auto_formula) END || '_SEQ_' || TRIM(column_id)),
        'sequence:' ||  
        LOWER(CASE WHEN auto_formula = '' THEN TRIM(table_id)
		       ELSE TRIM(auto_formula) END || '_SEQ_' || TRIM(column_id)),
        'CREATE SEQUENCE ' ||  
        CASE WHEN auto_formula = '' THEN TRIM(table_id)
		       ELSE TRIM(auto_formula) END || '_SEQ_' || TRIM(column_id)
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
    $this->SpecDDL_Triggers_ColConsMinMax();
    $this->SpecDDL_Triggers_Chains();
    $this->SpecDDL_Triggers_Cascades();
    $this->SpecDDL_Triggers_Histories();

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
   
   $this->SpecDDL_Triggers_Security_New();
   
   $this->LogEntry("Putting Special triggers onto security tables");

	// Adding a user to user tables will create database user.
   if($parm['FLAG_PWMD5']=='Y') {
      $this->LogEntry("Putting MD5 password triggers onto user tables");
      $sql = "
       -- 1000 Add user to system, goes in as nologin, no password
       new.member_password=####;
       SELECT INTO AnyInt COUNT(*) FROM pg_shadow WHERE usename = CAST(new.user_id as name);
       IF AnyInt = 0 THEN
           EXECUTE ##CREATE USER ## || new.user_id || ## NOLOGIN ##;
       ELSE 
           ErrorCount = ErrorCount + 1; 
           ErrorList = ErrorList || ##user_id,8001,That User ID already in use on this server;##;
       END IF;\n";
      $this->SpecDDL_TriggerFragment("users","INSERT","AFTER","1000",$sql);
   }
   else {
      $this->LogEntry("Putting simple password triggers onto user tables");
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
   
      // Update can change password, but only if md5 security is not set
      $sql = "
       -- 1000 Update password
       IF new.member_password <> old.member_password THEN
           EXECUTE ##ALTER USER ## ||  new.user_id || ## LOGIN PASSWORD ## || quote_literal(new.member_password);
       END IF;\n";
      $this->SpecDDL_TriggerFragment("users","UPDATE","AFTER","1000",$sql);
   }

   // Calculate new effective group   
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
           if (select count(*) from pg_roles where rolname=cast(old.group_id_eff as name))>0 THEN 
              EXECUTE ##ALTER GROUP ## || old.group_id_eff || ## DROP USER ## || old.user_id;
           END IF;
           new.group_id_eff=####;
        END IF;
        -- Put user into new effective group
        IF AnyInt > 1 THEN
            SELECT INTO new.group_id_eff group_id_eff 
              FROM zdd.groups_eff_c
             WHERE grouplist=AnyChar;
            if (select count(*) from pg_roles where rolname=cast(new.group_id_eff as name))>0 THEN 
               EXECUTE ##ALTER GROUP ## || new.group_id_eff || ## ADD USER ## || old.user_id;
            END IF;
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
   
   // Check that a user is not in conflict with solo group assignments
   $sqlinsbef="
    -- disallow solo plus other groups
    select into AnyInt case when csolo >= 1 AND call > 1 THEN 1 else 0 end
        FROM (
            select coalesce(sum(case when solo = ##Y## then 1 else 0 end),0) as csolo
                  ,count(*) as call
             FROM groups
            WHERE (   exists (select * from usersxgroups where user_id = new.user_id and group_id =groups.group_id)
                   OR group_id = new.group_id
		            )
              AND group_id <> ##".$GLOBALS['parm']['APP']."##
	          ) x;
    IF AnyInt= 1 THEN
        ErrorCount = ErrorCount + 1;
        ErrorList = ErrorList || ##group_id,1006,Group assignment conflict - solo group;##;
    END IF;";
	$this->SpecDDL_TriggerFragment(
      "usersxgroups","INSERT","BEFORE","7000",$sqlinsbef
   );
        
        
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
   
   // If a user inserts into this table, a trigger will
   // handle sending out the email.  This allows us to 
   // both: a) track requests
   //       b) process request w/o allowing read of pw table
   $sq='
    -- 8000 Send out email with link in it
    SELECT INTO AnyInt Count(*)
           FROM users WHERE user_id = new.user_id;
    IF AnyInt > 0 THEN 
       SELECT INTO AnyChar email
              FROM users WHERE user_id = new.user_id;
       SELECT INTO AnyChar4 member_password
              FROM users WHERE user_id = new.user_id;
       SELECT INTO AnyChar2 variable_value
              FROM variables
             WHERE variable = ##PW_EMAILCONTENT##;
       SELECT INTO AnyChar3 variable_value
              FROM variables
             WHERE variable = ##SMTP_SERVER##;
             
       -- Replace a user_id if it is present in email
       AnyChar2 = regexp_replace(AnyChar2,##%%USER_ID%%##,new.user_id);

       -- Replace a password if it is present in email
       AnyChar2 = regexp_replace(AnyChar2,##%%USER_PWD%%##,AnyChar4);

       -- Reuse anychar4 as the email from
       SELECT INTO AnyChar4 variable_value
              FROM variables
             WHERE variable = ##EMAIL_FROM##;
       IF AnyChar4 IS NULL THEN AnyChar4 = ####; END IF;
       
       new.md5 := md5(now()::varchar);
       PERFORM pwmail(AnyChar
          ,##Password Reset Request##
          ,AnyChar2 || new.md5
          ,AnyChar3
          ,AnyChar4);
       EXECUTE ## ALTER ROLE ## || new.user_id || ## NOLOGIN ##;
    END IF;';
   $this->SpecDDL_TriggerFragment(
     "users_pwrequests","INSERT","BEFORE","8000",$sq
   );
   
   // Add the trigger fragment to the verification
   $sq='
    -- 8000 If link is ok, set the password
    SELECT INTO AnyInt Count(*)
           FROM users_pwrequests
          WHERE user_id = new.user_id
            AND md5     = new.md5
            AND age(now(),ts_ins) < ##20  min##;         
    IF AnyInt = 0 THEN                                
        ErrorCount = ErrorCount + 1; 
        ErrorList  = ErrorList || ##user_id,9005,Invalid Link;##;
    ELSE 
        EXECUTE ##ALTER ROLE ## ||  new.user_id || ## LOGIN PASSWORD ## || quote_literal(new.member_password);
        new.member_password := ####;
    END IF;';
   $this->SpecDDL_TriggerFragment(
     "users_pwverifies","INSERT","BEFORE","8000",$sq
   );
   
   $sq="   
    -- Send out emails when events occur
    IF ErrorCount = 0 THEN
      IF (Select count(*) FROM elogs_codes 
           WHERE elogcode=new.elogcode 
             AND flag_send=##Y##) > 0 THEN 

         SELECT INTO AnyChar2 variable_value
           FROM variables
          WHERE variable = ##SMTP_SERVER##;

	      AnyChar
            =##Database Server Time is ## || cast(now() as varchar) || ##\n##
	         || ##Log Event Code: ## || new.elogcode || ##\n##
            || ##Log Event Desc: ## || COALESCE(new.elogdesc,####) || ##\n##
            || ##IP Number     : ## || COALESCE(new.elogipv4,####) || ##\n##
            || ##Argument 1    : ## || COALESCE(new.elogarg1,####) || ##\n##
            || ##Argument 2    : ## || COALESCE(new.elogarg2,####) || ##\n##
            || ##Argument 3    : ## || COALESCE(new.elogarg3,####) || ##\n##;

         FOR AnyRow IN SELECT * FROM elogs_emails LOOP
	         PERFORM pwmail(AnyRow.email
		         ,##Log Event: ## || new.elogcode || ## ## || new.elogdesc
		         ,AnyChar
		         ,AnyChar2
            );
         END LOOP; 
      END IF;
    END IF;";
   $this->SpecDDL_TriggerFragment(
     "elogs","INSERT","AFTER","8000",$sq
   );
   
}

function SpecDDL_Triggers_Security_New() {
    // Work out name of special group, $um, that
    // is the user maintenance group
    global $parm;
    $app= strtolower($parm['APP']);

    // This flag will be set if there is at least one freejoin group    
    $freejoins=array();

    $qresult = $this->SQLREAD(
        "Select group_id,freejoin From zdd.groups_c"
    );
    $groups  = pg_fetch_all($qresult);
    foreach($groups as $group) {
        $g= strtolower($group['group_id']);
        // Generate commands to delete any stored procedures
        // that grant access to this group
        $this->PlanMakeEntry("6002", 
            "DELETE FROM pg_proc WHERE LOWER(proname)=#add_me_to_$g#"
        );
        
        // If not a freejoin, we are now done.
        if($group['freejoin']<>'Y') {
            continue;
        }
        else {
            $freejoins[$g] = $g;
            $this->FreeJoinGroup($g);
        }
    }
    
    if(count($freejoins)>0 && !isset($freejoins[$app])) {
        $this->FreeJoinGroup($app);
    }
    
    // If the adduser routine exists, delete it unconditionally,
    // so the security settings always start out clean
    $sq="delete from pg_proc where LOWER(proname)=#adduser#";
    $this->PlanMakeEntry("6002",$sq);
    
    // Now, if there is at least one freejoin group, then anybody
    // can also create users
    if (count($freejoins)>0) {
        $sq="
CREATE OR REPLACE FUNCTION addUser(
        p_user_id varchar,p_name varchar,
        p_email varchar,p_pwd varchar) returns void as
\$BODY\$
BEGIN
	INSERT INTO users (user_id,user_name,email,member_password) 
        VALUES (p_user_id,p_name,p_email,p_pwd);

	RETURN;
END;
\$BODY\$
LANGUAGE plpgsql SECURITY DEFINER";
        $this->PlanMakeEntry("6002",$sq);
        $this->PlanMakeEntry("6002",
            "GRANT EXECUTE ON FUNCTION addUser(varchar,varchar,varchar,varchar)
                TO PUBLIC"
        );
    }
}

function FreeJoinGroup($g) {
    $sq="
CREATE OR REPLACE FUNCTION add_me_to_$g(p_user_id varchar) returns void as
\$BODY\$
BEGIN
	INSERT INTO usersxgroups (user_id,group_id)
	VALUES (p_user_id,#$g#);

	RETURN;
END;
\$BODY\$ LANGUAGE plpgsql SECURITY DEFINER";      
    $this->PlanMakeEntry("6002",$sq);
    $this->PlanMakeEntry("6002",
        "GRANT EXECUTE ON FUNCTION add_me_to_$g(varchar) TO PUBLIC"
    );    
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
		" WHERE automation_id IN ('BLANK','DEFAULT','SEQUENCE','SEQDEFAULT','TS_INS','UID_INS','TS_UPD','UID_UPD','QUEUEPOS','TS_UPD_PG','UID_UPD_PG')"
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
		if ($automation_id=="TS_UPD_PG") {
			$s1 = "\n".
				"    -- 1010 timestamp validation\n".
				"    IF NOT new.". $column_id . " IS NULL THEN \n".
				"        ErrorCount = ErrorCount + 1;\n". 
				"        ErrorList = ErrorList || ##$column_id,3005," . 
								"$column_id (ts_upd_pg) may not be explicitly assigned;##;\n".
				"    END IF;\n";
			$this->SpecDDL_TriggerFragment($table_id,"INSERT","BEFORE","1010",$s1);
			$s1 = "\n".
				"    -- 1010 timestamp validation\n".
				"    IF (new.". $column_id . " <> old.". $column_id . ") THEN \n".
				"        ErrorCount = ErrorCount + 1;\n". 
				"        ErrorList = ErrorList || ##$column_id,3004," . 
								"$column_id (ts_upd_pg) may not be re-assigned;##;\n".
				"    END IF;\n";
			$this->SpecDDL_TriggerFragment($table_id,"UPDATE","BEFORE","1010",$s1);
			
			//$Seq = strtoupper($table_id) . "_". strtoupper($column_id);
			$s1 = 
				"    -- 1010 insert timestamp assignment\n".
                                 " IF ( session_user <> ##postgres## ) THEN \n" .
				"    new.". $column_id . " = now();\n" .
                                " END IF;\n";
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
                if ($automation_id=='UID_UPD_PG' ) {
                        $s1 = "\n".
				"    -- 1010 User Update validation\n".
				"    IF NOT new.". $column_id . " IS NULL THEN \n".
				"        ErrorCount = ErrorCount + 1;\n". 
				"        ErrorList = ErrorList || ##$column_id,3009," . 
								"$column_id (uid_upd_pg) may not be explicitly assigned;##;\n".
				"    END IF;\n";
			$this->SpecDDL_TriggerFragment($table_id,"INSERT","BEFORE","1010",$s1);
			$s1 = "\n".
				"    -- 1010 User Update validation\n".
				"    IF (new.". $column_id . " <> old.". $column_id . ") THEN \n".
				"        ErrorCount = ErrorCount + 1;\n". 
				"        ErrorList = ErrorList || ##$column_id,3010,". 
								"$column_id (uid_upd_pg) may not be re-assigned;##;\n".
				"    END IF;\n";
			$this->SpecDDL_TriggerFragment($table_id,"UPDATE","BEFORE","1010",$s1);
			
			//$Seq = strtoupper($table_id) . "_". strtoupper($column_id);
			$s1 = 
				"    -- 1010 Update user id assignment\n".
                                " IF ( session_user <> ##postgres## ) THEN \n" .
				"    new.". $column_id . " = session_user; \n" .
                                " END IF;\n";
			$this->SpecDDL_TriggerFragment($table_id,"INSERT","BEFORE","1011",$s1);
			$this->SpecDDL_TriggerFragment($table_id,"UPDATE","BEFORE","1011",$s1);
                }
		
		if ($automation_id=="BLANK" || $automation_id=='QUEUEPOS') {
			$def = $this->SQLFormatBLank($row["type_id"],true,true);
			$s1 = "\n".
				"    -- 1020 Blank/queuepos default\n".
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
	foreach ($this->utabs as $table_id=>$utab) {
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
   $retval=true;
	foreach ($this->utabs as $utab) {
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
    global $ukids;
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
	
    // REM'D OUT 10/22/07
    // DEPRECATED.  Rules tables were experimental back in like 2005 or
    //              something, and were never used.
	//if ($this->utabs[$table_id]["rules"]=="Y") { return true; }
   
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
        $range_from=$this->utabs[$table_id]["flat"][$key]["range_from"];
        $range_to  =$this->utabs[$table_id]["flat"][$key]["range_to"];
        //$this->LogEntry("$key, range_from: $range_from, range_to: $range_to");
      
        // REM'D OUT 10/22/07
        // If this column is calculated, flip the the flag
        //if ($this->utabs[$table_id]['flat'][$key]['automation_id']=='EXTEND') {
        //    $this->LogEntry(" -> Calculated primary key: $table_id.$key");
        //    $flag_calculated=true;
        //}

        // KFD 12/16/06.  Ranges are allowed to be null, they are not
        // in the null list, but they are in the change list
        if ($range_from.$range_to=='') {
            $nullList .= $this->AddList($nullList," OR ") . " new.$key IS NULL ";
        }
        $chngList .= $this->AddList($chngList," OR \n       ") . "new.$key <> old.$key ";
        if ($this->utabs[$table_id]["capspk"]=="Y") {
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
            // KFD 10/10/07, support character and integers
            $formshort = $this->utabs[$table_id]["flat"][$key]["formshort"];
            list($rzero,$rinfi,$rcast) 
                =$this->getRange($this->utabs[$table_id]["flat"][$key]["formshort"]);
            $mtchList .= $this->AddList($mtchList," AND \n")
            .    "(    (          COALESCE(new.$key ,$rzero::$rcast) \n"
            .$sp."        between COALESCE($key     ,$rzero::$rcast) \n"
            .$sp."            AND COALESCE($range_to,$rinfi::$rcast) \n"
            .$sp."     )\n"
            .$sp."  OR (          COALESCE(new.$range_to,$rinfi::$rcast)\n"
            .$sp."        between COALESCE($key     ,$rzero::$rcast) \n"
            .$sp."            AND COALESCE($range_to,$rinfi::$rcast) \n"
            .$sp."     )\n"
            .$sp.")";
        }
	}
	
    // BIG CHANGE, KFD, 4/11/07, went to column-by-column reporting
    // of errors.
    $aerrmsg=array();
    foreach($keys as $key) {
        $aerrmsg[]="##$key,1002,Duplicate Value: ## || cast(new.$key as varchar) || ##;##";
    }
    $errmsg = implode(" || ",$aerrmsg);
    

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
		"            ErrorList = ErrorList || ".$errmsg.";\n".
		"        END IF;\n".
		"    END IF;\n".
		"    -- 3000 END\n";
    $seq='8000';
	$this->SpecDDL_TriggerFragment($table_id,"INSERT","BEFORE",$seq,$s1);

    // KFD 10/22/07, for PROMAT Project, cascade PK changes
    // The prohibition against changes applies only to pk,
    // and only if not a rules table
    $s1="    -- 3000 PK/UNIQUE Change prohibition\n";
    foreach($keys as $key) {
        if($this->utabs[$table_id]["flat"][$key]['pk_change']=='Y') {
            if(isset($ukids[$table_id])) {
                foreach($ukids[$table_id] as $ukid) {
                    $tkid = trim($ukid['table_id']);
                    $sfx = trim($ukid['suffix']);
                    $pfx = trim($ukid['prefix']);
                    $s1="\n"
                        ."    -- 3100 PK Change Cascade\n"
                        ."    IF new.$key <> old.$key THEN\n"
                        ."        UPDATE $tkid SET $pfx$key$sfx = new.$key\n"
                        ."         WHERE $pfx$key$sfx = old.$key;\n" 
                        ."    END IF;\n"
                        ."    -- 3100 END\n";
                    $this->SpecDDL_TriggerFragment(
                         $table_id,"UPDATE","AFTER","3100",$s1
                    );
                }
            }
        }
        else {
            $s1 = "\n"
                ."    -- 3100 PK Change Validation\n"
                ."    IF new.$key <> old.$key THEN\n"
                ."        ErrorCount = ErrorCount + 1;\n" 
                ."        ErrorList = ErrorList || "
                ."##$key,1003,Cannot change value;##;\n"
                ."    END IF;\n"
                ."    -- 3100 END\n";
             $this->SpecDDL_TriggerFragment(
                 $table_id,"UPDATE","BEFORE","3100",$s1
             );
        }
    }
    return $retval;
}

function SpecDDL_Triggers_FK() {
    $this->LogEntry("Building Foreign Key clauses");
    
    $retval = true;
    foreach($this->ufks as $ufk) {
        //$this->LogEntry("Attempting fk for ".$ufk["table_id_chd"]." to ".$ufk["table_id_par"]);
        $ptab = $ufk["table_id_par"];
        $retval=$retval && $this->SpecDDL_Triggers_FK_PT(
            $ufk
            ,$ptab
            ,explode(",",$ufk["cols_chd"])
            ,explode(",",$ufk["cols_par"])
         );
    }
    return $retval;
}

function SpecDDL_Triggers_FK_PT($ufk,$ptab,$chdlist,$parlist) {
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
        if ($this->utabs[$tid_par]['flat'][$colpar]['range_to']=='') {
            $mtchList   .= $this->AddList($mtchList," AND ")."par.". $colpar . " = new.". $chd ;
            $prntList   .= $this->AddList($prntList," AND ")."old.". $colpar . " = chd.". $chd ;
            }
        else {
             // KFD 12/16/06, allowed nulls in parent primary key when ranged,
             //   but we hardcode to assume they are dates
             //
             // KFD 10/10/07, allow ranges for integers and stuff
             $formshort = $this->utabs[$tid_par]['flat'][$colpar]["formshort"];
             list($rzero,$rinfi,$rcast)=$this->getRange($formshort); 
             $range_to =$this->utabs[$tid_par]['flat'][$parlist[$x]]['range_to'];
             $mtchList   .= $this->AddList($mtchList," AND ")
                ."new.". $chd . " BETWEEN COALESCE(par.".$colpar.",$rzero::$rcast) "
                ." AND COALESCE(par.".$range_to.",$rinfi::$rcast)";
             $prntList   .= $this->AddList($prntList," AND ")
                ."chd.". $chd . " BETWEEN COALESCE(old.".$colpar.",$rzero::$rcast) "
                ." and COALESCE(old.".$range_to.",$rinfi::$rcast)";
        }
        $delList    .= $this->AddList($delList," AND ").$chd ." = old.".$parlist[$x];
        $insArr[$parlist[$x]] = "new.".$chd;
        $chgList    .= $this->AddList($chgList," OR ")."new.". $chd ." <> old." . $chd;
        if(!isset($this->utabs[$ufk["table_id_chd"]]["flat"][$chd])) {
            hprint_r($this->utabs[$ufk['table_id_chd']]);
            x_EchoFlush("ERROR:");
            x_EchoFlush("ERROR: Problem building a foreign key from "
                 .$ufk["table_id_chd"]
                 .' to '.$ufk["table_id_par"]
            );
            x_EchoFlush("ERROR: column $chd not defined in child table.");
            x_EchoFlush("ERROR: If you used a 'nocolumns' flag, be sure to ");
            x_EchoFlush("ERROR:    manually define the columns in the child table.");
            x_EchoFlush("ERROR:");
            $retval = false;
        } 
        else {
            $chdCols    .= $this->AddComma($chdCols).$this->utabs[$ufk["table_id_chd"]]["flat"][$chd]["description"];
            $x++;
        }
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
		$this->SpecDDL_TriggerFragment($ufk["table_id_chd"],"UPDATE","BEFORE","6000",$s1);
	}


	// The EMPTY clause determines what to do if empty. 
	// Normally that is an error, but the "Allow_empty" flag
	// gives 'em a pass.  Notice the HUGE ASSUMPTION that
	// allow empty has a single-column key.
	//
	if ($ufk["allow_empty"] == "Y") {
		$chd = $chdlist[0];
		$chd_type = $this->utabs[$ufk["table_id_chd"]]["flat"][$chd]["formshort"];
        //$this->logEntry("Doing $chd_type for $chd"); 
		$chd_blank= ($chd_type=='int' || $chd_type=='numb') ? "0" : '####';
		//$emptyList  .= $this->AddList($emptyList," || ")."COALESCE(new.".$chd.",####)";
		$onEmpty =
			"    -- 8001 FK Insert/Update Child Validation\n".
			"    IF COALESCE(new.$chd,$chd_blank) <> $chd_blank THEN\n"; 
	}
	else {
      $onEmpty="";
      foreach($chdlist as $chd) {
         $onEmpty.=
            "    -- 8001 Insert/Update Child Validation: NOT NULL\n"
            ."    IF new.$chd IS NULL THEN\n"
            ."        ErrorCount = ErrorCount + 1;\n" 
            ."        ErrorList = ErrorList || ##$chd,1005,Required Value;##;\n"
            ."    END IF;\n";
      }
		$onEmpty .= 
			"    -- 8001 FK Insert/Update Child Validation\n".
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
			foreach ($this->utabs[$ufk["table_id_chd"]]["flat"] as $colname=>$colinfo) {
				// HARDCODE SKEY HARDCODED SKEY
				if ($colname=="skey") continue;
				if ($colname=="skey_quiet") continue; 
				if (isset($this->utabs[$ptab]["flat"][$colname])) {
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
   // EXPERIMENT KFD 6/11/06
   if ($ufk["allow_orphans"]<>'Y') {
      $this->SpecDDL_TriggerFragment($ufk["table_id_chd"],"INSERT","BEFORE","8001",$s1,"FK:".$ufk["table_id_par"]);
      $this->SpecDDL_TriggerFragment($ufk["table_id_chd"],"UPDATE","BEFORE","8001",$s1,"FK:".$ufk["table_id_par"]);
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
    $retval = $retval && $this->specddl_triggers_automated_queuepos();
    $retval = $retval && $this->specddl_triggers_automated_dominant();
    $retval = $retval && $this->specddl_triggers_automated_dominant_agg();
    return $retval;	
}

function SpecDDL_Triggers_Automated_FetchDistribute() {
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
		$results = $this->SQLRead(
		"SELECT tf.table_id,tf.column_id
             ,tf.auto_prefix,tf.auto_suffix
             ,tf.automation_id,tf.formshort,tf.auto_formula 
		   FROM zdd.tabflat_c tf 
		  WHERE tf.automation_id IN ('FETCH','FETCHDEF','DISTRIBUTE','SYNCH') 
		  ORDER BY tf.table_id,tf.column_id");
    $ddall = array();
    $dddst = array();
	while ($row=pg_fetch_array($results)) {
        $autoid = trim(strtoupper($row["automation_id"]));
        if(strpos($row['auto_formula'],'.')===false) {
            x_EchoFlush("ERROR:");
            x_EchoFlush("ERROR: Bad formula: ".$row['auto_formula']);
            x_EchoFlush("ERROR: Table: ".$row['table_id'].', column: '.$row['column_id']);
            x_EchoFlush("ERROR:");
            return false;
        }
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
        //  and also for synchs
        if($autoid=='DISTRIBUTE' || $autoid == 'SYNCH') {
            if(!isset($dddst[$tpi][$row['table_id']])) {
                $dddst[$tpi][$row['table_id']]=array();
            }
            $dddst[$tpi][$row['table_id']][]=$details;
        }
    }
   
   
   
   // NOTE: FETCH and EXTEND columns are sequenced on dependencies, as
   // one EXTEND may generate the foreign key for the next FETCH.  This
   // system goes column-by-column.  However, we use just one column
   // pair between any two tables (to detect changes), since all 
   // commands between any two tables are grouped together.
   
   // Now we will loop through the $ddall array and generate all of the
   // code snippets for insert and update.  The idea is to end up with
   // only a single fetch for each foreign key definition
   foreach($ddall as $table_id=>$ddall2) {
      // KFD 5/21/07.  Save this consolidated FETCH/DIST information
      //  to be written out to the generated dd files.  This way the
      //  web client can make use of it.
      $this->utabs[$table_id]['FETCHDIST']=$ddall[$table_id];

      foreach($ddall2 as $foreign_key=>$details) {
         $d=$details[0];
         $table_id_par = $details[0]['table_id_par'];
         $cs_errorsi=$cs_errorsu='';
         $cs_col1  = array();
         $cs_col2u = array();
         $cs_col2i = array();

         // Generate the keys match between the two tables
         // KFD 2/16/07, big change to allow suffix/prefix
         //$keyname = $table_id."_".$table_id_par."_";
         $keyname = $foreign_key;
         $keys = $this->ufks[$keyname]["cols_both"];
         // KFD 10/12/06, part of general changes to range foreign keys
         //$match = str_replace(","," AND new.",$keys);
         //$match = "new.".str_replace(":"," = par.",$match);
         $match=str_replace("chd.","new.",$this->ufks[$keyname]['cols_match']);
         
         
         // KFD 6/22/07, don't do a fetch if the foreign key is null
         $keyskids=$this->ufks[$keyname]['cols_chd'];
         $akeyskids=explode(',',$keyskids);
         $nullchecks = array();
         foreach($akeyskids as $akeykid) {
            $nullchecks[]="new.$akeykid IS NOT NULL";
         }
         
         // Generate a key change expression for child table
         $keychga = explode(",",$this->ufks[$keyname]["cols_chd"]);
         $keychgb = array();
         foreach($keychga as $keycol) {
            $type_id=$this->utabs[$table_id]['flat'][$keycol]['formshort'];
            if($type_id=='int' || $type_id=='numb') {
               $blank='0';
            }
            elseif($type_id=='timestamp' || $type_id=='date') {
               $blank="##1900-01-01##";
            }
            else {
               $blank="####";
            }
            $keychgb[] = " coalesce(new.$keycol,$blank) <> coalesce(old.$keycol,$blank) ";
         }
         $keychg = implode(' OR ',$keychgb);

         // Now build column-by-column details and lists      
         foreach($details as $detail) {
            $nochange=array('FETCH','DISTRIBUTE');
            $colpar=$detail['column_id_par'];
            $col=$detail['column_id'];
            // Insert error
            $cs_errorsi='';
            if(in_array($detail['automation_id'],$nochange)) {
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
            elseif(!in_array($detail['automation_id'],$nochange)) {
               // means fetchdef and synch
               $cs_errorsu.="";
            }
            else {
               // intended only to be distribute
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
               
            // This is the destination column, where the result goes
            $cs_col1[] = 'new.'.$col;
            // This is the "from" column in the parent table.  For
            // Fetchdef we do not overwrite user inputs.
            if($detail['automation_id']=='FETCHDEF' || $detail['automation_id']=='SYNCH') {
               $cs_col2u[]="CASE WHEN new.$col<>old.$col THEN new.$col ELSE par.$colpar END";
               $cs_col2i[]="COALESCE(new.$col,par.$colpar)";
            }
            else {
               $cs_col2u[] = 'par.'.$colpar;
               $cs_col2i[] = 'par.'.$colpar;
            }
         }
         
         // First for insert.  Error plus assignment
         $match = str_replace(' AND ',"\n       AND ",$match);
         $s1="\n"
            .$cs_errorsi
            ."  IF ".implode("\n     AND ",$nullchecks)." THEN \n"
            ."    SELECT INTO ".implode("\n               ,",$cs_col1)."\n"
            ."                ".implode("\n               ,",$cs_col2i)."\n"
            ."      FROM $table_id_par par \n"
            ."     WHERE $match ;\n"
            ."  END IF;\n";
         $this->SpecDDL_TriggerFragment(
            $table_id,"INSERT","BEFORE","5000",$s1,$details[0]['column_id']
         );
         
         // Update is almost the same, different error, same assignment
         $s1="\n"
            .$cs_errorsu
            ."   IF $keychg THEN \n"
            ."       SELECT INTO ".implode("\n                   ,",$cs_col1)."\n"
            ."                   ".implode("\n                   ,",$cs_col2u)."\n"
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
         $table_id_par = $details[0]['table_id_par'];
         //echo "Doing $foreign_key on table par $table_id_par";

         // Generate the keys match between the two tables
         // KFD 3/1/07, fix this
         $keyname=$foreign_key;
         $keys = $this->ufks[$keyname]["cols_both"];
         // KFD 10/12/06, part of range foreign keys actually
         // KFD Fixed 6/18/07, this was wrong, making the wrong match
         //   not picked up cuz we don't use DISTRIBUTE much
         $match=str_replace("chd.",$table_id.".",$this->ufks[$keyname]['cols_match']);
         $match=str_replace('par.','new.',$match);

         // For "SYNCH" automations, build the reverse match
         $matchr=str_replace("chd.","new.",$this->ufks[$keyname]['cols_match']);
         $matchr=str_replace("par.",$table_id_par.".",$matchr);
         

         // Now build column-by-column details and lists      
         $cs_chg = array();
         $cs_set = array();
         $cs_chgr= array();
         $cs_setr= array();
         foreach($details as $detail) {
            $colpar=$detail['column_id_par'];
            $col=$detail['column_id'];
            // KFD 3/12/07, different code for dates
            $type_id=$this->utabs[$table_id_par]['flat'][$colpar]['type_id'];
            $blank = $this->SQLFormatBlank($type_id,true,true);
            
            // Original code, just for distributes
            if($type_id =='date' OR $type_id =='dtime') {
               $cs_chg[] = " COALESCE(new.$colpar,##1900-01-01##) <> COALESCE(old.$colpar,##1900-01-01##) ";
            } else {
               $cs_chg[] = " COALESCE(new.$colpar,$blank) <> COALESCE(old.$colpar,$blank) ";
            }
            if($detail['automation_id'] =='DISTRIBUTE') {
               $cs_set[] = $col. "= new.$colpar ";
            }
            else {
               $cs_set[] = $col. "= CASE WHEN new.$colpar=$table_id.$col THEN $table_id.$col ELSE new.$colpar END";
            }

            // repeated in mirror image for synchs
            if($type_id =='date' OR $type_id =='dtime') {
               $cs_chgr[] = " COALESCE(new.$col,##1900-01-01##) <> COALESCE(old.$col,##1900-01-01##) ";
            } else {
               $cs_chgr[] = " new.$col <> old.$col ";
            }
            $cs_setr[] = $colpar. "= CASE WHEN new.$col=$table_id_par.$colpar THEN $table_id_par.$colpar ELSE new.$col END";
         }
         
         $s1="\n"
            ."    -- 6000 DISTRIBUTE/SYNCH PUSH \n"
            ."    IF ".implode("\n    OR ",$cs_chg)." THEN \n"
            ."        UPDATE $table_id SET \n               "
            .implode("\n              ,",$cs_set)."\n"
            ."         WHERE $match; \n"
            ."    END IF;\n";
			$this->SpecDDL_TriggerFragment(
            $table_id_par,"UPDATE","AFTER","6000",$s1,$details[0]['column_id']
         );	

         if($details[0]['automation_id']=='SYNCH') {         
            $s1="\n"
               ."    -- 6000 SYNCH UPSAVE \n"
               ."    IF ".implode("\n    OR ",$cs_chgr)." THEN \n"
               ."        UPDATE $table_id_par SET \n               "
               .implode("\n              ,",$cs_setr)."\n"
               ."         WHERE $matchr; \n"
               ."    END IF;\n";
            $this->SpecDDL_TriggerFragment(
               $table_id,"UPDATE","AFTER","6000",$s1,$details[0]['column_id']
            );
            $s1="\n"
               ."    -- 6000 SYNCH UPSAVE \n"
               ."    UPDATE $table_id_par SET \n               "
               ."          ".implode("\n          ,",$cs_setr)."\n"
               ."     WHERE $matchr; \n";
            $this->SpecDDL_TriggerFragment(
               $table_id,"INSERT","AFTER","6000",$s1,$details[0]['column_id']
            );
         }       
      }
   }
}

function SpecDDL_Triggers_Automated_Aggregate()  {
	
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
		$table_info_chd = &$this->utabs[$table_chd]["flat"];

      if(!isset($this->ufks[$table_chd."_".$table_par."_"])) {
         $this->LogEntry("ERROR");
         $this->LogEntry("ERROR -> building aggregate clause, ");
         $this->LogEntry("ERROR -> from $table_chd up to $table_par ");
         $this->LogEntry("ERROR -> a foreign key must be defined.");
         $retval=false;
         continue;
      }

      // notice the matchup doesn't distinguish really between
      // parent and child, it assumes there is only one FK
      $mx    = $this->ufks[$table_chd."_".$table_par."_"]["cols_match"];
		$match = $this->ufks[$table_chd."_".$table_par."_"]["cols_match"];
		$match     = str_replace("par.","new.",$mx);
		//$match = str_replace(","," AND ",$match);
      $match_old = str_replace("new.",'old.',$match);

      $match_latest = $this->ufks[$table_chd."_".$table_par."_"]["cols_match"];
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
         $type_id_chd = $table_info_chd[$column_chd]['type_id'];
      
      // Special simple code for 'LATEST', do this and go to next one
      // Note 3/7/06, when we started "registering" SUM and COUNT
      // for optimization, we left this alone.  W/lots of writes this
      // would be a performance killer.
      if (in_array($row['automation_id'],array('LATEST','SUM','COUNT'))) {
         $table_info_chd = &$this->utabs[$table_chd]["flat"];
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
         $table_info_chd = &$this->utabs[$table_chd]["flat"];
         $type_id_chd = $table_info_chd[$column_chd]['type_id'];
         $blank = $this->SQLFormatBlank($type_id_chd,true,true);
         $mmm     = str_replace('chd.','',$match);
         $mmm_old = str_replace('chd.','',$match_old);
         $mmmw    = str_replace('chd.','new.',$mx);
         $mmmw    = str_replace('par.',$table_par.'.',$mmmw);
         $mmmw_o  = str_replace('new.','old.',$mmmw);
         $s1 = "
    -- 6000 MIN/MAX Push
    IF new.$column_chd IS NOT NULL THEN
        UPDATE $table_par SET $column_par
          = (SELECT COALESCE(".$row['automation_id']."($column_chd),$blank)
               FROM $table_chd WHERE $mmm 
            )
         WHERE $mmmw ;
    END IF;\n";
         $this->SpecDDL_TriggerFragment($table_chd,"INSERT","AFTER","6000",$s1);
         $s1 = "
    -- 6000 MIN/MAX Push
    IF new.$column_chd <> old.$column_chd THEN
        UPDATE $table_par SET $column_par
          = (SELECT COALESCE(".$row['automation_id']."($column_chd),$blank)
               FROM $table_chd WHERE $mmm 
            )
         WHERE $mmmw ;
    END IF;\n";
         $this->SpecDDL_TriggerFragment($table_chd,"UPDATE","AFTER","6000",$s1);
         $s1 = "
    -- 6000 MIN/MAX Push
    IF new.$column_chd <> old.$column_chd THEN
        UPDATE $table_par SET $column_par
          = (SELECT COALESCE(".$row['automation_id']."($column_chd),$blank)
               FROM $table_chd WHERE $mmm_old 
            )
         WHERE $mmmw_o ;
    END IF;\n";
         $this->SpecDDL_TriggerFragment($table_chd,"UPDATE","AFTER","6000",$s1);
         $s1 = "
    -- 6000 MIN/MAX Push
    IF old.$column_chd IS NOT NULL THEN
        UPDATE $table_par SET $column_par
          = (SELECT COALESCE(".$row['automation_id']."($column_chd),$blank)
               FROM $table_chd WHERE $mmm_old 
            )
         WHERE $mmmw_o ;
    END IF;\n";
         $this->SpecDDL_TriggerFragment($table_chd,"DELETE","AFTER","6000",$s1);
         //continue;
      }  

      
  		// Always default aggregates to zero
		$s1 = "\n".
			"    -- 1020 Aggregate Defaults\n".
			"    new.$column_par = $blank;\n";
		$this->SpecDDL_TriggerFragment($table_par,"INSERT","BEFORE","1020",$s1);

      // Build the expression to create the compound key
      // IGNORED AS OF 3/14/07, just get list tabs_par
      $lchd = $this->ufks[$table_chd."_".$table_par."_"]["cols_both"];
      $apairs= explode(',',$lchd);
      $aexpr = array();
      $apars = array();
      foreach($apairs as $pair) {
         list($colchd,$colpar) = explode(':',$pair);
         $colprec = $this->utabs[$table_par]['flat'][$colpar]['colprec'];
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
             $table_info_chd = &$this->utabs[$tab_chd]["flat"];
             $type_id_chd = $table_info_chd[$colchd]['type_id'];
             $blank = $this->SQLFormatBlank($type_id_chd,true,true);
            $acolspar[]="new.".$colpar;
            if($automation_id=='SUM') {
               $acolschd[]="COALESCE( SUM(COALESCE($colchd,0)), $blank)";
            }
            elseif($automation_id=='MIN') {
               $acolschd[]="COALESCE( MIN(         $colchd   ), $blank)";
            }
            elseif($automation_id=='MAX') {
               $acolschd[]="COALESCE( max(         $colchd   ), $blank)";
            }
            else {
               $acolschd[]="COALESCE( COUNT(*), 0)";
            }
         }
         $scolspar=implode(',',$acolspar);
         $scolschd=implode(',',$acolschd);
         
         // Take the key and build a matching clause and a group by
         $lmatches = $this->ufks[$tab_chd."_".$tab_par."_"]["cols_both"];
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
      //echo "building for $tab_par";
      $this->SpecDDL_TriggerFragment(
          $tab_par,"UPDATE","BEFORE","4000",$sq
      );
   }
   return $retval;
}

function SpecDDL_Triggers_Automated_Queuepos()  {
   // Pull any column with QUEUE POSITION automation
	$results = $this->SQLREAD(
		"Select table_id,column_id FROM zdd.tabflat_c".
		" WHERE automation_id = 'QUEUEPOS'"
   );
   $qps = pg_fetch_all($results);
   
   // Make each one "bump" equal values when this value is set
   if(is_array($qps)) {
      foreach($qps as $qp) {
         $t = $qp['table_id'];
         $c = $qp['column_id'];
         $sq="\n"
            ."    --- 4000 Queue position bumps \n"
            ."    UPDATE $t SET $c = $c + 1\n"
            ."     WHERE $c = new.$c \n"
            ."       AND skey <> new.skey;\n";
         $this->SpecDDL_TriggerFragment($t,"UPDATE","AFTER","4000",$sq);
         $this->SpecDDL_TriggerFragment($t,"INSERT","AFTER","4000",$sq);
      }
   }
   return true;
}

function SpecDDL_Triggers_Automated_Dominant()  {
    
    // Pull any column with DOMINANT automation
    $results = $this->SQLREAD(
        "Select table_id,column_id,auto_formula FROM zdd.tabflat_c".
        " WHERE automation_id = 'DOMINANT'"
    );
    $qps = pg_fetch_all($results);
    
    if(is_array($qps)) {
        foreach($qps as $qp) {
            $t = $qp['table_id'];
            $c = $qp['column_id'];
            $af= $qp['auto_formula'];
            
            $SWhere = "$c = ##Y##";
            if($af<>'') {
                $match = $this->ufks[trim($t).'_'.trim($af).'_']['cols_match'];
                $match = str_replace('chd.',''    ,$match);
                $match = str_replace('par.','new.',$match);
                $SWhere = $SWhere .' AND '.$match;
            }
            // Do this as an AFTER so that we know the skey value
            $sq="\n"
                ."    --- 4001 DOMINANT bumps \n"
                ."    IF new.$c = ##Y## THEN\n"
                ."        UPDATE $t SET $c = ##N##\n"
                ."         WHERE $SWhere \n"
                ."           AND skey <> new.skey;\n"
                ."    END IF;\n";
            $this->SpecDDL_TriggerFragment($t,"UPDATE","AFTER" ,"4001",$sq);
            $this->SpecDDL_TriggerFragment($t,"INSERT","BEFORE","8000",$sq);
        }
    }
    return true;
}

function SpecDDL_Triggers_Automated_Dominant_agg()  {
    
    // Pull any column with DOMINANT automation
    $results = $this->SQLREAD(
        "Select table_id,column_id,auto_formula FROM zdd.tabflat_c".
        " WHERE automation_id = 'FETCH_DOM'"
    );
    $qps = pg_fetch_all($results);
    if(!is_array($qps)) return true;
    
    // Split out into combinations of parent/child tables
    $ads = array();
    foreach($qps as $qp) {
        list($tab_chd,$col_chd) = explode('.',$qp['auto_formula']);
        $ads[$tab_chd][$qp['table_id']][] = array(
            'col_chd'=>$col_chd,'col_par'=>$qp['column_id']
        );
    }

    // Now get a list of the dominant flag columns for each pair
    $results = $this->SQLREAD(
            "Select table_id as tab_chd,auto_formula as tab_par
                   ,column_id
               FROM zdd.tabflat_c
              WHERE automation_id = 'DOMINANT'"
    );
    $qps  = pg_fetch_all($results);
    $doms = array();
    foreach($qps as $qp) {
        $dom[trim($qp['tab_chd'])][trim($qp['tab_par'])] = $qp['column_id'];
    }
    
    
    // Now process sets of commands for each child-> parent combination
    //
    foreach($ads as $tab_chd=>$adss1) {
        foreach($adss1 as $tab_par=>$adss2) {
            // Now convert the pairs into SQL comparisons
            foreach($adss2 as $adss3) {
                $col_chd = $adss3['col_chd'];
                $col_par = $adss3['col_par'];
                $pairs[] = "$col_par = new.$col_chd";
            }
            
            // Get the SQL Match
            $match = $this->ufks[$tab_chd.'_'.$tab_par.'_']['cols_match'];
            $match = str_replace('chd.','new.'     ,$match);
            $match = str_replace('par.',"$tab_par.",$match);
            $SWhere = $match;
            
            // Fetch the dominant column
            $col_dom = $dom[$tab_chd][$tab_par];
            
            // Create the insert....
            $sq="\n"
                ."    --- 4001 AGGREGATE DOMINANT upsaves\n"
                ."    IF new.$col_dom = ##Y## THEN\n"
                ."        UPDATE $tab_par SET ".implode("\n              ,",$pairs)."\n"
                ."         WHERE $SWhere; \n"
                ."    END IF;\n";
            $this->SpecDDL_TriggerFragment($tab_chd,"INSERT","AFTER" ,"4002",$sq);

            // Create the update...
            $sq="\n"
                ."    --- 4001 AGGREGATE DOMINANT upsaves\n"
                ."    IF new.$col_dom = ##Y## AND old.$col_dom = ##N## THEN\n"
                ."        UPDATE $tab_par SET ".implode("\n              ,",$pairs)."\n"
                ."         WHERE $SWhere; \n"
                ."    END IF;\n";
            $this->SpecDDL_TriggerFragment($tab_chd,"UPDATE","AFTER" ,"4001",$sq);
        }
    }
    return true;
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

function SpecDDL_Triggers_ColConsMinMax()  {
	$this->LogEntry("Building Min/Max column constraints");
	
	$results = $this->SQLREAD(
		"Select table_id,column_id,type_id,value_min,value_max 
         FROM zdd.tabflat_c
		  WHERE value_min<>'' OR value_max<>''"
   );
	while ($row=pg_fetch_array($results)) {
      
      if($row['value_min']<>'') {
         $c=$row['column_id'];
         $msg="Value must be at least ".$row['value_min'];
         $compare
            ="new.".trim($row['column_id'])
            .' < '
            .SQL_Format($row['type_id'],$row['value_min']);
         $s1="\n" 
				."    -- 7010 Column Minimum Constraint\n"
				."    IF ($compare) THEN \n"
				."        ErrorCount = ErrorCount + 1;\n" 
				."        ErrorList = ErrorList || ##$c,6010,$msg;##;\n" 
				."    END IF;\n";
			$this->SpecDDL_TriggerFragment($row["table_id"],"INSERT","BEFORE","7010",$s1);
			$this->SpecDDL_TriggerFragment($row["table_id"],"UPDATE","BEFORE","7010",$s1);
      }
      if($row['value_max']<>'') {
         $c=$row['column_id'];
         $msg="Value must be less than or equal to ".$row['value_max'];
         $compare
            ="new.".trim($row['column_id'])
            .' > '
            .SQL_Format($row['type_id'],$row['value_max']);
         $s1="\n" 
				."    -- 7010 Column Maximum Constraint\n"
				."    IF ($compare) THEN \n"
				."        ErrorCount = ErrorCount + 1;\n" 
				."        ErrorList = ErrorList || ##$c,6011,$msg;##;\n" 
				."    END IF;\n";
			$this->SpecDDL_TriggerFragment($row["table_id"],"INSERT","BEFORE","7010",$s1);
			$this->SpecDDL_TriggerFragment($row["table_id"],"UPDATE","BEFORE","7010",$s1);
      }
	}
}

function SpecDDL_Triggers_Chains() {
	$chains = $this->SpecDDL_Triggers_ChainsList();

   // KFD 5/26/07, attach chains to $this->utabs, so we can save them
   // out in the dd and use them in client code.
   foreach($chains as $x=>$chain) {
      $this->utabs[$chain['table_id']]['chains'][$x]=$chain;
   }


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
   
   // Final, send them back to calling program
	return $chains;
}

function SpecDDL_Triggers_ChainsCode(&$chains) {
	$this->LogEntry("CHAINS: Convert to CASE/WHEN statements");
	// this happy guy takes all of the chains
	// and codes them up into CASE WHEN statements,
	// but does not actually make trigger fragments yet

	foreach ($chains as $key=>$chain) {
		$ctid = $rtid = "char";
        $dsiz=0;
		if (trim($chain["column_id"])!="") {
            $tid = $chain["table_id"];
            $cid = $chain["column_id"];
            $ctid = $this->utabs[$tid]["flat"][$cid]["type_id"];
            $dsiz = $this->utabs[$tid]["flat"][$cid]["dispsize"];
			if (trim($chain["chain"])=="calc") $rtid = $ctid;
		}			

		$chaintext = "";
		foreach ($chain["tests"] as $test) {
			$return    = $this->TrigGen_ChainReturn( $rtid,$test,$dsiz);
			$compare   = $this->TrigGen_ChainCompare($ctid,$test,$dsiz);
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

function TrigGen_ChainReturn($rtid,$test,$dsiz) {
	$funcoper = trim($test["funcoper"]);
	$retval = "";
   
    // Some functions are simple, or have fixed number of args
    $cret0 = $this->zzArraySafe($test['_return'],0,array());
    $cret  = $this->zzArraySafe($cret0,'column_id_arg','');
    //$cret=$test['_return'][0]['column_id_arg'];
    switch ($funcoper) {
      case 'SUBS':
         return $this->TrigGen_CRetSubstring($rtid,$test);
         break;
      case 'REPLACE': 
         return $this->TrigGen_CRetString3($rtid,$test);
         break;
      case 'LPAD':
         $arg1 = $this->TrigGen_CRet_arg($rtid,$test['_return'][0]);
         $arg2 = $this->TrigGen_CRet_arg($rtid,$test['_return'][1]);
         return " LPAD($arg1::varchar,$dsiz,$arg2::varchar)";
         break;
      case 'RPAD':
         $arg1 = $this->TrigGen_CRet_arg($rtid,$test['_return'][0]);
         $arg2 = $this->TrigGen_CRet_arg($rtid,$test['_return'][1]);
         return " RPAD($arg1::varchar,$dsiz,$arg2::varchar)";
         //return $this->TrigGen_CRetString2($rtid,$test);
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

function TrigGen_CRetString2($rtid,$test) {
   // This is fixed behavior:
   // return REPLACE($arg1,$arg2,$arg3)
   $arg1 = $this->TrigGen_CRet_arg($rtid,$test['_return'][0]);
   $arg2 = $this->TrigGen_CRet_arg($rtid,$test['_return'][1]);
   
   return ' '.$test['funcoper']."($arg1,$arg2) ";
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
		$cta1 = $this->utabs[$chaintest["table_id"]]["flat"][$arg1]["type_id"];
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

// ---------------------------------------------------------------
// Cascades
// ---------------------------------------------------------------
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
	$this->utabs  = &$this->utabs;
	$tabsrc = $cascade["table_id"];
	$tabdst = $cascade["table_id_dest"];

	$cols = array();

   // First build any columns caught by "copystripprefix" then suffix
   $stripx = trim($this->zzArraySafe($cascade,'copystripprefix'));
	if ($stripx<>'') {
      //echo "Stripping prefix: $stripx\n";
		foreach ($this->utabs[$tabsrc]["flat"] as $colsrc=>$colinfo) {
         $x = substr($colsrc,0,strlen($stripx));
         //echo "Beginning of column is : $x\n";
         if (substr($colsrc,0,strlen($stripx))<>$stripx) continue;
         $coldst = substr($colsrc,strlen($stripx));
         //echo "Looking at destination column $coldst\n";
         if (isset($this->utabs[$tabdst]["flat"][$coldst])) {
            //echo " --- Destination is there!\n";
				$cols[$coldst] = "new.".$colsrc;
			}
		}
	}
	
   $stripx = trim($this->zzArraySafe($cascade,'copystripsuffix'));
	if ($stripx<>'') {
		foreach ($this->utabs[$tabsrc]["flat"] as $colsrc=>$colinfo) {
         if (substr($colsrc,-1,strlen($stripx))<>$stripx) continue;
         $coldst = substr($colsrc,0,strlen($colsrc)-strlen($stripx));
         if (isset($this->utabs[$tabdst]["flat"][$coldst])) {
				$cols[$coldst] = "new.".$colsrc;
			}
		}
	}
	

 	// Now build any columns caught by "copysamecols"
	if ($cascade["copysamecols"]=="Y") {
		foreach ($this->utabs[$tabsrc]["flat"] as $colname=>$colinfo) {
			if (!isset($cols[$colname])) {
				if (isset($this->utabs[$tabdst]["flat"][$colname])) {
					$cols[$colname] = "new.".$colname;
				}
			}
		}
	}

	// Explicit column assignments overwrite any options above
	//
	foreach ($cascade["_cols"] as $colassign) {
		$col = $colassign["column_id"];
      if($colassign['retcol']=='') {
         $val=$this->SQLFORMATLITERAL(
            $colassign["retval"]
            ,$this->utabs[$tabdst]["flat"][$col]["type_id"]
            ,true
            ,true
         );
      }
      else {
         if(   $cascade['onlychanged']=='Y' 
            && $cascade["cascade_action"]=="UPDATE") {
            $val='CASE WHEN new.'.$colassign['retcol']
               .' <> old.'.$colassign['retcol']
               .' THEN new.'.$colassign['retcol']
               .' ELSE '.$col.' END';
         }
         else {
            $val = "new.".$colassign["retcol"];
         }
      }
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
      $sets2=array();
		foreach ($cols as $colname=>$colval) {
			$sets .=$this->AddComma($sets).$colname." = ".$colval;
         $sets2[] = $colname." = ".$colval;
		}
		
      $fk = $tabsrc."_".$tabdst."_".$cascade['suffix'];
		$colsmatch = $this->ufks[$fk]["cols_both"];
		$this->LogEntry("cols_both is: ".$colsmatch);
		$colsmatch = str_replace(":"," = ",$colsmatch);
		$colsmatch = "new.".str_replace(","," AND new.",$colsmatch);
		//$test = ($cascade['testupd']=='') ? ' 1 = 1 ' : $cascade['testupd'];
		
		$s1 = "\n"
			."    -- 9000 Cascade actions \n"
			."    IF $test THEN \n"
			."        UPDATE $tabdst SET "
         ."               ".implode("\n              ,",$sets2)."\n"
         ."         WHERE $colsmatch;\n"
         ."        $reset\n"
         .$column_ts
			."    END IF;\n";
	}
	if ($cascade['afterins']=='Y') {
      $this->LogEntry("  **** putting ins cascade onto $tabsrc to $tabdst");
		$this->SpecDDL_TriggerFragment($tabsrc,"INSERT","BEFORE","9800",$s1);
	}
	if ($cascade['afterupd']=='Y') {
      $this->LogEntry("  **** putting upd cascade onto $tabsrc to $tabdst");
		$this->SpecDDL_TriggerFragment($tabsrc,"UPDATE","BEFORE","9800",$s1);
	}
}	


// ---------------------------------------------------------------
// Histories
// Code copied from cascades 9/25/07 and then modified
// ---------------------------------------------------------------
function SpecDDL_Triggers_Histories()  {
	$this->LogEntry("Building History Actions");
	
	// Retrieve list of tables and their cascades
	$table_id = "";
	$tables   = array();
	$result = $this->SQLRead("Select * from zdd.histories_c ORDER BY uicolseq");
	while ($row = pg_fetch_array($result)) {
		if ($table_id <> $row["table_id"]) {
			$table_id = $row["table_id"];
			$tables[$table_id] = array();
		}
		$tables[$table_id][$row["history"]] = $row;
		$tables[$table_id][$row["history"]]["_cols"] = array();
	}
	
	// Now go pull the columns down for each table/cascade,
	// then pull the matches down for each table/cascade
	$result = $this->SQLRead("Select * from zdd.histcols_c");
	while ($row = pg_fetch_array($result)) {
		$tables[$row["table_id"]][$row["history"]]["_cols"][] = $row;
	}
	
	// We have now locally cached the hierarchy of cascades,
	// we can roll through them and build trigger assignments
	//
	foreach ($tables as $table_id=>$cascades) {
		foreach ($cascades as $cascade) {
			$this->SpecDDL_Triggers_Histories_One($cascade);
		}
	}
	
}

function SpecDDL_Triggers_Histories_One(&$history)  {
	$this->utabs  = &$this->utabs;
	$tabsrc = $history["table_id"];
	$tabdst = $history["table_id_dest"];
    
	$cols   = array();
    $awhere = array();
    
    foreach ($history["_cols"] as $colassign) {
        $col = $colassign["column_id"];
        $cols[$col]['i'] = null; 
        $cols[$col]['u'] = null; 
        $cols[$col]['d'] = null; 
        if($colassign['retval']!='') {
             $val=$this->SQLFORMATLITERAL(
                $colassign["retval"]
                ,$this->utabs[$tabdst]["flat"][$col]["type_id"]
                ,true
                ,true
             );
             $cols[$col]['i'] = $val;
             $cols[$col]['u'] = $val;
             $cols[$col]['d'] = $val;
        }
        elseif($colassign['retdiff'] != '') {
            $rd = $colassign['retdiff'];
            $cols[$col]['i'] = 'new.'.$colassign['retdiff'];
            $cols[$col]['u'] = 'new.'.$colassign['retdiff'] 
                .' - old.'.$colassign['retdiff'];
            $cols[$col]['d'] = '-old.'.$colassign['retdiff'];
            $awhere[]="new.$rd <> old.$rd";
        }
        elseif($colassign['retold'] != '') {
            $cols[$col]['u'] = 'old.'.$colassign['retold']; 
            $cols[$col]['d'] = 'old.'.$colassign['retold'];
        }
        elseif($colassign['retnew'] != '') {
            $cols[$col]['i'] = 'new.'.$colassign['retnew'];
            $cols[$col]['u'] = 'new.'.$colassign['retnew']; 
        }
        elseif($colassign['retcol'] != '') {
            $cols[$col]['i'] = 'new.'.$colassign['retcol'];
            $cols[$col]['u'] = 'new.'.$colassign['retcol']; 
            $cols[$col]['d'] = 'old.'.$colassign['retcol'];
        }
    }
	
    // HARDCODE skey behavior
    if (isset($cola["skey"]['i'])) unset($cols["skey"]['i']);
    if (isset($cola["skey"]['u'])) unset($cols["skey"]['u']);
    if (isset($cola["skey"]['d'])) unset($cols["skey"]['d']);
    $cols["skey_quiet"]['i'] = $this->SQLFORMATLITERAL('Y',"char",true,true);
    $cols["skey_quiet"]['u'] = $this->SQLFORMATLITERAL('Y',"char",true,true);
    $cols["skey_quiet"]['d'] = $this->SQLFORMATLITERAL('Y',"char",true,true);
    
    // Now build the three triggers
    $inscols = array();
    $insvals = array();
    $updcols = array();
    $updvals = array();
    $delcols = array();
    $delvals = array();
    foreach($cols as $colname=>$actions) {
        // Build insert clauses
        if(! is_null($actions['i'])) {
            $inscols[] = $colname;
            $insvals[] = $actions['i'];
        }
        if(! is_null($actions['u'])) {
            $updcols[] = $colname;
            $updvals[] = $actions['u'];
        }
        if(! is_null($actions['d'])) {
            $delcols[] = $colname;
            $delvals[] = $actions['d'];
        }
        
    }
    
    // Now build the three clauses
    if(count($inscols)>0) {
        $ic = implode("\n               ,",$inscols);
        $iv = implode("\n               ,",$insvals);
        $s1 = "\n"
            ."    -- 9900 History logging \n"
            ."    INSERT INTO $tabdst \n "
            ."               ( $ic) \n"
            ."        VALUES ( $iv);\n";
        $this->SpecDDL_TriggerFragment($tabsrc,"INSERT","AFTER","9900",$s1);
        
    }
    if(count($updcols)>0) {
        $uc = implode("\n               ,",$updcols);
        $uv = implode("\n               ,",$updvals);
        $s1 = "\n"
            ."    -- 9900 History logging \n"
            ."    IF ".implode(' OR ',$awhere)." THEN\n"
            ."        INSERT INTO $tabdst \n"
            ."                   ( $uc) \n"
            ."            VALUES ( $uv);\n"
            ."    END IF;\n";
        $this->SpecDDL_TriggerFragment($tabsrc,"UPDATE","AFTER","9900",$s1);
        
    }
    if(count($delcols)>0) {
        $dc = implode("\n               ,",$delcols);
        $dv = implode("\n               ,",$delvals);
        $s1 = "\n"
            ."    -- 9900 History logging \n"
            ."    INSERT INTO $tabdst \n"
            ."               ( $dc) \n"
            ."        VALUES ( $dv);\n";
        $this->SpecDDL_TriggerFragment($tabsrc,"DELETE","AFTER","9900",$s1);
        
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
         "    AnyChar2 varchar;\n".
         "    AnyChar3 varchar;\n".
         "    AnyChar4 varchar;\n".
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
		"a.def_short = b.def_short AND a.object_id = b.object_id");
	return true;
}

function Differences_One($strStub, $dst,$strDesc,$strKeys,$strWhere="")
{

	$strList1 = "";
	$this->LogEntry("Generating differences for ". $strStub . ", ". $strDesc);
	foreach ($this->utabs[$strStub]["flat"] as $colname=>$colprops) {
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
// Use the bootstrap information in $this->utabs to do unique
// and referential checks on the resolved spec
// ==========================================================

function SpecValidate()
{
	$retval = true;
	$this->LogStage("Validating After Flattening");
	$errors = 0;

    	$this->LogEntry("Looking for primary key on each table");
	$results = $this->SQLRead(
		"select table_id from zdd.tabflat_c ".
		" group by table_id ".
		" having sum(case when primary_key='Y' then 1 else 0 end) = 0");
	while($row=pg_fetch_array($results)) {
        $errors++;
        $this->LogEntry("");
        $this->LogEntry("ERROR >> table ". $row["table_id"]." has no primary key!");
        $this->LogEntry("ERROR >> ");
	}
    
    
    // Check for no automation Id for some automations
    $sql="SELECT table_id,column_id from zdd.tabflat_c
          WHERE automation_id in 
             ('FETCH','DISTRIBUTE','SUM','COUNT','MIN','MAX','LATEST')
            AND COALESCE(auto_formula,'') = ''";
	$results = $this->SQLRead($sql);
    while($row=pg_fetch_array($results)) {
        $this->LogEntry("");
        $this->LogEntry("ERROR >> No auto_formula for "
            .$row['table_id'].'.'.$row['column_id']
        );
        $errors++;        
    }
    
    
	$results=$this->SQLRead(
			"select table_id,column_id,table_dep ".
			" FROM zdd.column_deps ".
			" WHERE automation_id in ('FETCH','DISTRIBUTE','SYNCH') ". 
			"   AND NOT EXISTS ".
			"   (SELECT table_id FROM zdd.tabfky_c fk ". 
			"    WHERE fk.table_id     = zdd.column_deps.table_id ".
			"      AND fk.table_id_par = zdd.column_deps.table_dep)");
	while ($row=pg_fetch_array($results)) {
		$retval=false;
        $this->LogEntry("");
		$this->LogEntry(
			"ERROR >> Column ". $row["table_id"].".".$row["column_id"]. 
			" FETCHes from non-parent table: ".$row["table_dep"]);
        $errors++;
	}

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
        $this->LogEntry("");
		$this->LogEntry(
			"ERROR >> Column ". $row["table_id"].".".$row["column_id"].
			" aggregates from non-child table: ".$row["table_dep"]);
        $errors++;
	}
    
	$results=$this->SQLRead("
select table_id,column_id,
       auto_table_id,auto_column_id,automation_id
 from zdd.tabflat_c
 where not exists (select skey from zdd.tabflat_c x
                    where x.table_id = zdd.tabflat_c.auto_table_id
                      AND x.column_id= zdd.tabflat_c.auto_column_id)
   AND ( auto_table_id <> '' OR auto_column_id <> '')"
     );
	while ($row=pg_fetch_array($results)) {
		$retval=false;	
        $this->LogEntry("");
		$this->LogEntry(
			"ERROR >> Column ". $row["table_id"].".".$row["column_id"].
			" has automation ".$row['automation_id']
            ." referencing non-existent column: ".$row["auto_table_id"].'.'.$row['auto_column_id']);
        $errors++;
	}
    
    // Check that all table/column references work out
    	$this->LogEntry("Checking all column references are valid");
    $errors+=$this->SpecValidateRI('tabidxcol'    ,'column_id','Index');
    $errors+=$this->SpecValidateRI('tabcascols'   ,'column_id','Upsave Definition');
    $errors+=$this->SpecValidateRI('histcols'     ,'retcol'   ,'History Definition');
    $errors+=$this->SpecValidateRI('tabprojcols'  ,'column_id','Projection');
    $errors+=$this->SpecValidateRI('colchaintests','column_id','Chain Test Definition');
    $errors+=$this->SpecValidateRI('colchainargs' ,'column_id_arg','Chain Argument Definition');
    
    // Some manual RI checks
    $sq="SELECT h.history,hc.table_id,hc.column_id
           FROM zdd.histcols_c   hc
           JOIN zdd.histories_c  h  ON hc.table_id = h.table_id
                                 AND hc.history  = h.history
          WHERE column_id <> ''
            AND NOT EXISTS (
                SELECT * from zdd.tabflat_c 
                WHERE table_id  = h.table_id_dest
                  AND column_id = hc.column_id
               )";
    $results = $this->SQLRead($sq);
	while ($row=pg_fetch_array($results)) {
		$retval=false;	
        $this->LogEntry("");
		$this->LogEntry(
			"ERROR >> History definition ".$row['history']
            ." in table ".$row['table_id']
            ." assigns to undefined target column: ".$row['column_id']
        );
        $errors++;
	}

    return ($errors==0);
}

function SpecValidateRI($table,$col,$description) {
    $errors = 0;
    $sq="SELECT table_id,{$col} as column_id FROM zdd.{$table}_c
          WHERE $col <> ''
            AND NOT EXISTS (
                SELECT * from zdd.tabflat_c 
                WHERE table_id  = zdd.{$table}_c.table_id
                AND column_id = zdd.{$table}_c.$col
               )";
    $results = $this->SQLRead($sq);
	while ($row=pg_fetch_array($results)) {
		$retval=false;	
        $this->LogEntry("");
		$this->LogEntry(
			"ERROR >> $description in table ".$row['table_id']
            ." refers to undefined column ".$row['column_id']
        );
        $errors++;
	}
    return $errors;
}

/*
// NOT CURRENTLY BEING CALLED, see SpecValidate_Special above
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
			" WHERE automation_id in ('FETCH','DISTRIBUTE','SYNCH') ". 
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
*/

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

function PlanMake() {
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
        $seq=$tid."_SEQ_".$cid;
        $sq="SELECT SETVAL(#$seq#,(SELECT MAX($cid) FROM $tid)+1)";
        $this->PlanMakeEntry("6050",$sq);
    }
    
    $this->PlanMakeEntry("9200","UPDATE USERS set flag_newgroup=#Y#");
    
    //$this->PlanMake_DDR();  //--this routine still exists
    return true;
}

function PlanMake_Tables()                                      
{
   $ts=time();
	$this->LogEntry("Generating Table Create and Alter Commands");
	$this->LogEntry("Retrieving list of tables");
	$results = $this->SQLRead("select table_id from zdd.tables_d where XFate ='N'");
	$TablesNew = pg_fetch_all($results);
	$results = $this->SQLRead("select table_id from zdd.tables_d where XFate ='U'");
	$TablesUpd = pg_fetch_all($results);

	if ($TablesNew) { foreach ($TablesNew as $tab) { $this->PlanMake_TablesNew($tab["table_id"]); }}
	if ($TablesUpd) { foreach ($TablesUpd as $tab) { $this->PlanMake_TablesUpd($tab["table_id"]); }}
   $this->LogElapsedTime($ts);
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
			$aid = trim(strtolower($this->utabs[$table_id]["flat"][$column_id]["automation_id"]));
			//$this->LogEntry("  Automation id is: $aid");
			switch ($aid) {
				case "sequence":
				case "seqdefault":
					$newval = "nextval(#$table_id"."_$column_id#)";
					break;
				case "default": 
					$type_id = trim(strtolower($this->utabs[$table_id]["flat"][$column_id]["type_id"]));
					$auto_f  = trim($this->utabs[$table_id]["flat"][$column_id]["auto_formula"]);
					//$this->LogEntry("Type is $type_id and formula is $auto_f");
					$newval = $this->SQLFORMATLITERAL($auto_f,$type_id,true);
					break;
				default: 
					$newval = $this->DBB_SQLBlank($this->utabs[$table_id]["flat"][$column_id]["formshort"]);
			}
			$SQL.=$this->AddComma($SQL)." $column_id = $newval";
		}
		$this->PlanMakeEntry("5010","UPDATE $table_id SET $SQL");
	}
}
	
function PlanMake_Views() {
   // Is this cruel?  Will we wipe out a view created by some sysadmin?
   // Yes we will, but those could be huge security leaks
   //
 	$this->LogEntry("Generating DROP VIEW commands.");
   $ts=time();
   $res=$this->SQLRead("
      SELECT table_name as table_id 
        FROM information_schema.tables 
       WHERE table_schema = 'public'
         AND table_type = 'VIEW'"
   );
   $dropviews='';
   while ($row=pg_fetch_array($res)) {
      //$dropviews="DROP VIEW ".$row['table_id'];
      $this->PlanMakeEntry("2900","DROP VIEW ".$row['table_id'].' CASCADE');
   }
   $this->LogElapsedTime($ts);

   // A system view we need
   $sq="
create view an_authname_members as
select pgg.rolname as gname,pgu.rolname as uname
  from pg_roles        pgg
  JOIN pg_auth_members am  ON pgg.oid = am.roleid
  JOIN pg_roles        pgu ON pgu.oid = am.member";
   $this->PlanMakeEntry("3050",$sq);
   

   $defsrow = $this->PlanMake_ViewsRowSecurity();
   $defscol = $this->Planmake_ViewsColSecurity();
   
   $this->ViewDefs=array();   
   $this->Planmake_viewsRowColCombine($defsrow,$defscol);
   return true;
}

function planMake_ViewsRowSecurity() {
    // ----- big step: create the row security clauses   
    $ts=time();
    $this->LogEntry("Creating View commands for row security");
    // Pull out the definitions
    $res=$this->SQLRead("
       SELECT table_id,column_id,group_id
              ,coalesce(permrow,'')      as permrow
              ,coalesce(table_id_row,'') as table_id_row
         FROM zdd.perm_cols_c  
        WHERE coalesce(permrow,'')      <> ''
           OR coalesce(table_id_row,'') <> ''"
    );
    $defs=pg_fetch_all($res);
    
    // Now run through them and reslot them by table/column.  The
    // result is
    //  defs2:         master list
    //    table_id:  
    //       column_id:
    //          'table_id_row'=> table_id_row
    //          groups:  array()         These have NO security
    $defs2=array();
    if($defs) {
       foreach($defs as $def) {
          if($def['table_id_row']<>'') {
             $defs2[$def['table_id']][$def['column_id']]['table_id_row']
               =$def['table_id_row'];
          }
          if($def['permrow']=='N') {
             $defs2[$def['table_id']][$def['column_id']]['groups'][]
               =$def['group_id'];
          }
       }
    }

    // Now Pull out the secondary definitions and slot those
    $res=$this->SQLRead("
       SELECT table_id,table_id_row,column_id,group_id
         FROM zdd.permxtablesrow_c"
    );
    $d2nd=pg_fetch_all($res);
    // Begin slotting the defs2 array.  We want an array of the
    // group/column/tables that are using 2ndary lookups.
    if($d2nd) {
        foreach($d2nd as $d2) {
            $defs2[$d2['table_id']][$d2['column_id']]['xg'][$d2['group_id']][]
                =$d2['table_id_row'];
        }
    }

    // Now loop through the defs2 array and build the row-level
    // filtering clause for the row-level view.  There is exactly
    // one row-level view per table.  
    foreach($defs2 as $tab=>$columns) {
       $colclauses=array();
       foreach($columns as $column_id=>$colinfo) {
          // Do finisher first actually
          if(isset($colinfo['xg'])) {
              $finisher='';
              foreach($colinfo['xg'] as $group_id=>$atables) {
                  $finisher.="
                         (select count(*) from an_authname_members
                          where uname = current_user 
                            AND gname = #$group_id# )> 0 ";  
                  foreach($atables as $atable) {
                      $view = $atable.'_v_99999';
                      $finisher.="
                            AND exists (SELECT skey FROM $view
                                        WHERE $tab.$column_id = $view.$column_id
                                        ORDER BY skey limit 1)";
                  }
              }
          }
          elseif(!isset($colinfo['table_id_row'])) {
             $finisher="$column_id = current_user ";
          }
          else {
             $tirow=$colinfo['table_id_row'];
             $finisher="
                 EXISTS (SELECT skey FROM $tirow 
                                WHERE $tirow.user_id = current_user\n
                           AND $tab.$column_id = $tirow.$column_id)
               ";
          }
          if(!isset($colinfo['groups'])) {
             $colclause = $finisher;
          }
          else {
             $colclause='CASE ';
             foreach($colinfo['groups'] as $group_id) {
                $colclause.="
                     WHEN (select count(*) from an_authname_members
                          where uname = current_user 
                            AND gname = #$group_id# )> 0 
                     THEN true";
             }
             $colclause.="\n                     ELSE $finisher 
             END ";
          }
          $colclauses[]=$colclause;
       }
       $tabclause
            ="\nWHERE CASE"
            ."\n      WHEN (select rolsuper from pg_roles where rolname=current_user)"
            ."\n      THEN true "
            ."\n      ELSE ( ".implode("\n       AND\n",$colclauses)
            ."\n           ) "
            ."\n      END ";
       $defs2[$tab]['clause']=$tabclause;
    }
    // DEBUG NOTE: The best way to debug the code above is to examine
    // the resulting associative arrray.  You can examine the data and
    // the resulting clause together and see if it all makes sense
    $this->LogElapsedTime($ts);
    return $defs2;
}    
           

function PlanMake_ViewsColSecurity() {
   global $parm;
   $app=$parm['APP'];                       
   
   $ts=time();
   $this->LogEntry("Creating View Commands for Column Security");

   // Pull down definitions
   $res=$this->SQLRead("
   select table_id,group_id,column_id
       ,coalesce(permsel,'') as permsel
       ,coalesce(permupd,'') as permupd
 from zdd.perm_cols_c 
  where coalesce(permsel,'') <> ''
     OR coalesce(permupd,'') <> ''"
   );
   $cols=pg_fetch_all($res);
   if(!$cols) return array();
   // ...and reslot them into an array that goes by
   //    table and group.
   //    $tabs:
   //        table_id:
   //           group_id:
   //              column_id:
   //                  permsel: Y/N
   //                  permupd: Y/N
   $tabs=array();
   $tg  =array();
   foreach($cols as $col) {
      $tabs[$col['table_id']][$col['column_id']][$col['group_id']]=array(
         'permsel'=>$col['permsel']
         ,'permupd'=>$col['permupd']
      );
      $tg[$col['table_id']][$col['group_id']] = 1;
   }
   // now make sure there is a setting for the login group for 
   // each column in each table
   foreach($tabs as $table_id => $tabinfo) {
      foreach($tabinfo as $column_id=>$colinfo) {
         if(!isset($colinfo[$app])) {
            $tabs[$table_id][$column_id][$app] = array(
               'permsel'=>'N','permupd'=>'N'
            );
         }
      }
   }
   foreach($tg as $table_id=>$grouplist) {
      if(!isset($grouplist[$app])) {
         $tg[$table_id][$app]=1;
      }
   }
   
   //  Generate the combinations of groups that are meaningful
   //  for each table.
   //
   //   tgf[ table ] = array(
   //          group+group+group => 1
   //          group+group+group => 1
   //   tgf2[ table ] = array(
   //          groupcount = array(
   //             group+group+group => 1
   //             group+group+group => 1
   $tgf = array(); 
   foreach($tg as $tab=>$groups) {
      // Drop the login group if found, it will be added in ListFactorial(),
      // then grab the keys so we can process those
      if(isset($groups[$app])) unset($groups[$app]);
      $groups = array_keys($groups);
      $tgf[$tab]=array();
      $this->ListFactorial($groups,$tgf[$tab],array($app));
      
      // Now split out the combinations according to how many
      // groups are in that particular combination, we'll need these
      // sorted from most to smallest
      foreach($tgf[$tab] as $key=>$throwaway) {
         $x = explode("+",$key);
         $tgf2[$tab][count($x)][]=$key;
      }
    
   }

   // This at at last is the core routine, where we will do the deed
   // For each table we have to go by the most complext view first,
   // because we need to assign it.
   $retval=array();
   foreach($tgf2 as $table_id=>$tabinfo) {
      $counts = array_keys($tabinfo);
      sort($counts);
      $x=0;
      while(count($counts)>0) {
         $x++;
         if($x>100) {
            echo "Some kind of looping error, must exit";
            exit;
         }
         $count=array_pop($counts);
         foreach($tabinfo[$count] as $grouplist=>$glist) {
            //$this->LogEntry("Doing table $table_id for $glist");
            $this->PlanMake_ViewsColSecurityOne(
               $table_id,$tabs[$table_id],$glist,$retval
            );
         }
      }      
   }
   $this->LogElapsedTime($ts);
   return $retval;
}


function PlanMake_ViewsColSecurityOne($table_id,$tabinfo,$glist,&$retval) {
   $groups=explode("+",$glist);
  
   // Identify the effective group that contains exactly this list
   // of primary groups.  We do this with a query of this form:
   //
   //    -- this line gets the group
   //  SELECT group_id_eff from zdd.groupsx_c
   //     -- this join repeats for each group in the list
   //    JOIN (select * from zdd.groupsx_ where group_id = 'xxx') ax
   //      ON x.group_id_eff = ax.group_id_eff
   //     -- the not-exists make sure there are no OTHER groups
   //   AND NOT EXISTS (
   //         select * from zdd.gorupsx_c where group_id not in ('''''')
   //       
   //        )
   //  
   $joins='';
   $x=0;
   $sgroups=array();
   foreach($groups as $group) {
      $sgroups[]="'".$group."'";
      $x++;
      $subq='a'.$x;
      $joins.=" 
    JOIN ( SELECT group_id_eff from zdd.groupsx_c where group_id='$group') $subq
      ON  x.group_id_eff = $subq.group_id_eff";
   }
   $sq="
select x.group_id_eff
  from zdd.groupsx_c x
  $joins
  AND not exists (
         Select * from zdd.groupsx_c 
          where group_id NOT IN (".implode(',',$sgroups).")
            and group_id_eff = x.group_id_eff
      )
 group by x.group_id_eff";
   //h*print_r($sq);
   $res=$this->SQLRead($sq);
   $row=pg_fetch_array($res);
   $effective = $row['group_id_eff'];
   $eno = substr($row['group_id_eff'],-5);
   
   // Update the specific group
   $sq="update zdd.table_views_c 
           SET view_id = '$eno'
         WHERE group_id_eff = '$effective'
           AND table_id     = '$table_id'";
   //h*print_r($sq);
   $this->SQL($sq);
   
   
   // If the above code worked, now go on and mark all views more complex
   // than this as using this view.  This is basically the opposite of
   // the query that finds the effective group.  We want to find the 
   // effective group that matches all of our groups, but which also
   // has more groups.  We map all of those to the effective group.
   $sq="
update zdd.table_views_c 
   SET view_id = '$eno'
  FROM (   
      select x.group_id_eff
        from zdd.groupsx_c x
        $joins
        AND exists (
               Select * from zdd.groupsx_c 
                where group_id NOT IN (".implode(',',$sgroups).")
                  and group_id_eff = x.group_id_eff
            )
       group by x.group_id_eff
       ) x2
 WHERE zdd.table_views_c.group_id_eff = x2.group_id_eff
   AND zdd.table_views_c.table_id     = '$table_id'
   AND coalesce(zdd.table_views_c.view_id,'')=''";
   //h*print_R($sq);
   $this->SQL($sq);
   
   // If all of that works, mark the effective group as being active
   // KFD 7/18/07, removed this finally and for good
   //$this->SQL("update zdd.groups_eff_c SET flag_used='Y' 
   //      where group_id_eff = '$effective'");
   
   // Here we loop through the permission assignments of each group
   // and each column.  If we find any group that allows the column
   // then it is allowed.  A blank for permupd is an implied Y.
   //
   // KFD 7/18/07, this could all be replaced if we were
   //    doing a simple materialization and examination
   // 
   // KFD 7/18/07, we ignore permupd!  It ain't real!
   foreach($tabinfo as $column_id=>$groupinfo) {
      $cperms=array('permsel'=>'N','permupd'=>'N');
      foreach($groups as $group) {
      //foreach($groupinfo as $group_id=>$perms) {
         if(isset($groupinfo[$group])) {
            $perms = $groupinfo[$group];
            if($perms['permsel']=='Y') {
               $cperms['permsel']='Y';
               if($perms['permupd']<>'N') {
                  $cperms['permupd']='Y';
               }
            }
         }         
      }
      $retval[$table_id][$eno][$column_id]=$cperms;
   }
}



function ListFactorial($groups,&$list,$current) {
   // Pop off the value.  If there are none left, commit
   // the string both with and without the value
   $new=array_shift($groups);
   if(count($groups)==0) {
      // Make an entry not including this one, and an entry that includes
      $list[implode("+",$current)] = 1;
      $current[] = $new;
      $list[implode("+",$current)] = 1;
   }
   else {
      // now branch off for the rest of the list of groups, one
      // branch including the new value, one not including.
      $this->ListFactorial($groups,$list,$current);
      $current[]=$new;
      $this->ListFactorial($groups,$list,$current);
   }
}

function planMake_ViewsRowColCombine(&$defsrow,&$defscol) {
   $app=$GLOBALS['parm']['APP'];
   $res=$this->sqlread(
      "select table_id,permrow,permcol from zdd.tables_c
        WHERE coalesce(permrow,'')='Y'
           OR coalesce(permcol,'')='Y'"
   );
   $tables=pg_fetch_all($res);
   if(!$tables) { return; }
   foreach($tables as $table) {
      $table_id = $table['table_id'];
      
      // Do the row views
      if($table['permrow']=='Y') {
         $vwn=$table_id."_v_99999";
         $sq="CREATE OR REPLACE VIEW $vwn AS 
              SELECT * 
                FROM $table_id 
              ".$defsrow[$table_id]['clause'];
         $this->PlanMakeEntry("3050",$sq);
         
         // The two delete rules.  A weird postgres thing.
         $this->PlanMakeEntry("3060",
            "CREATE OR REPLACE RULE $vwn"."_delete1 AS
                 ON DELETE TO $vwn
                 DO INSTEAD NOTHING"
         );
         $this->PlanMakeEntry("3060",
            "CREATE OR REPLACE RULE $vwn"."_delete2 AS
                 ON DELETE TO $vwn
                 DO INSTEAD
                 DELETE FROM $table_id WHERE skey = old.skey"
         );

         // The two update rules
         $this->PlanMakeEntry("3060",
            "CREATE OR REPLACE RULE $vwn"."_update1 AS
                 ON UPDATE TO $vwn
                 DO INSTEAD NOTHING"
         );
         $cols=array_keys($this->utabs[$table_id]['flat']);
         $ins1=implode(',',$cols);
         $ins2="new.".implode(',new.',$cols);
         $aupd=array();
         foreach($cols as $col) {
            $aupd[]="$col = new.$col";
         }
         $updlist = implode(',',$aupd);
         $this->PlanMakeEntry("3060",
            "CREATE OR REPLACE RULE $vwn"."_update2 AS
                 ON UPDATE TO $vwn
                 DO INSTEAD UPDATE $table_id SET $updlist
                 WHERE skey = new.skey"                          
         );
         // The insert rule.  First the unconditional, then the specific
         $this->PlanMakeEntry("3060",
            "CREATE OR REPLACE RULE $vwn"."_insert1 AS
                 ON INSERT TO $vwn
                 DO INSTEAD NOTHING"
         );
         $this->PlanMakeEntry("3060",
            "CREATE OR REPLACE RULE $vwn"."_insert2 AS
                 ON INSERT TO $vwn
                 DO INSTEAD INSERT INTO $table_id ($ins1) VALUES ($ins2)"
         );
      }

      // Pick the source for the column level security views,
      // then do the column level view
      if($table['permrow']<>'Y') {
         $colsource = $table_id;
      }
      else {
         $colsource = $table_id.'_v_99999';         
      }
      if($table['permcol']=='Y') {
         foreach($defscol[$table_id] as $effective=>$perms) {
            // Establish view name
            $vwn = $table_id."_v_".$effective;
            
            // Grab default list of columns, build the select list
            // and the update list
            $cols =array_keys($this->utabs[$table_id]['flat']);
            $cols =array_flip($cols);
            $colsu=$cols;
            foreach($perms as $column_id=>$colperms) {
               if($colperms['permsel']=='N') {
                  unset($cols[$column_id]);
                  unset($colsu[$column_id]);
               }
               else {
                  if($colperms['permupd']=='N') {
                     unset($colsu[$column_id]);
                  }
               }
            }
            $x=array();
            foreach($cols as $col=>$throwaway) {
               $x[$col]=isset($colsu[$col]) ? '1' : '0';
            }
            $this->ViewDefs[$table_id][$app.'_eff_'.$effective]=$x;
            
            // Now generate the create view command
            if(count($cols)==0) $cols=array('skey'=>0); 
            $colsname=array_keys($cols);
            $sq="CREATE OR REPLACE VIEW $vwn AS 
                 SELECT ".implode("\n                ,",$colsname)." 
                   FROM $colsource";
            $this->PlanMakeEntry("3051",$sq);
            
            // The two delete rules.  A weird postgres thing.
            $this->PlanMakeEntry("3060",
               "CREATE OR REPLACE RULE $vwn"."_delete1 AS
                    ON DELETE TO $vwn
                    DO INSTEAD NOTHING"
            );
            $this->PlanMakeEntry("3060",
               "CREATE OR REPLACE RULE $vwn"."_delete2 AS
                    ON DELETE TO $vwn
                    DO INSTEAD
                    DELETE FROM $table_id WHERE skey = old.skey"
            );
            
            
            // The insert rule.  First the unconditional, then the specific
            $this->PlanMakeEntry("3060",
               "CREATE OR REPLACE RULE $vwn"."_insert1 AS
                    ON INSERT TO $vwn
                    DO INSTEAD NOTHING"
            );
            if(count($colsu)>0) {
               $colsux=array_keys($colsu);
               $x1=implode(',',$colsux);
               $x2='new.'.implode(',new.',$colsux);
               $this->PlanMakeEntry("3060",
                  "CREATE OR REPLACE RULE $vwn"."_insert2 AS
                       ON INSERT TO $vwn
                       DO INSTEAD INSERT INTO $table_id ($x1) VALUES ($x2)"
               );
            }
            
            // ...and finally, the update rule
            $this->PlanMakeEntry("3060",
               "CREATE OR REPLACE RULE $vwn"."_update1 AS
                    ON UPDATE TO $vwn
                    DO INSTEAD NOTHING"
            );
            if(count($colsu)>0) {
               $colsu=array_keys($colsu);
               $aupd=array();
               foreach($colsu as $colu) {
                  $aupd[]="$colu = new.$colu";
               }
               $updlist = implode(',',$aupd);
               $this->PlanMakeEntry("3060",
                  "CREATE OR REPLACE RULE $vwn"."_update2 AS
                       ON UPDATE TO $vwn
                       DO INSTEAD UPDATE $table_id SET $updlist
                       WHERE skey = new.skey"                          
               );
            }
         }
      }
   }
   return true;
}

function PlanMake_Build_NSO()
{
   $ts=time();
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
         // KFD 7/12/07, Changed to move 2nd trigger commands forward
         $seq='6000';
         if(substr($row['def_short'],0,9)=='sequence:') $seq='5010';
         if(substr($row['def_short'],0,5)=='trgt:') $seq='6001';
         //$seq = substr($row['def_short'],0,9)=='sequence:'
	      //   ? "5010" : "6000";
         $this->PlanMakeEntry($seq,$row["sql_create"]);
      }
	}
   $this->LogElapsedTime($ts);
}

function PlanMake_DDR() {
   $ts=time();
	$this->LogStage("Generating DDR update commands");
   $this->LogEntry(" -- PLACEHOLDER, no action performed.");
   return true;
	
	foreach ($this->ddarr["data"]["table"] as $OneTab=>$table) {
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
   $this->LogElapsedTime($ts);
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
   // KFD 3/3/08 ouch, these were direct queries instead of plan entries!
   $this->PlanMakeEntry("9000","GRANT ALL  ON SCHEMA zdd TO $app");
   $this->PlanMakeEntry("9000","GRANT SELECT ON TABLE  zdd.perm_tabs_c TO $app");
   $this->PlanMakeEntry("9000","GRANT SELECT ON TABLE  zdd.groups_c    TO $app");
   //$results = $this->SQLREad("GRANT ALL  ON SCHEMA zdd TO $app");
   //$results = $this->SQLREad("GRANT SELECT ON TABLE  zdd.perm_tabs_c TO $app");
   //$results = $this->SQLREad("GRANT SELECT ON TABLE  zdd.groups_c    TO $app");

   
   // KFD 7/16/07, part of row-column fixup, drop effective groups we dont
   //      need and create the ones we do.  Effective groups are not in
   //      the main list of groups anymore.
   // KFD 7/18/07, this will always drop all effective groups,
   //              as we set them all to 'N' now, we don't need them to exist
   $sq="
select pg.rolname,e.group_id_eff
  INTO zdd.groups_eff_d
  from             pg_roles pg
  FULL OUTER JOIN (
         SELECT group_id_eff::name 
           FROM zdd.groups_eff_c
          WHERE flag_used = 'Y'
       ) e ON pg.rolname = e.group_id_eff::name
  WHERE pg.rolname like '{$app}\_eff\_%'";
   //h*print_r($sq);
   $this->SQL($sq);
   $res=$this->SQLRead("Select * from zdd.groups_eff_d");
	while ($row = pg_fetch_array($res)) {
      if(is_null($row['rolname'])) {
         $this->PlanMakeEntry("9000","CREATE ROLE NOLOGIN ".$row['rolname']);
         $this->PlanMakeEntry("9000","GRANT $app TO ".$row['rolname']);
      }
      if(is_null($row['group_id_eff'])) {
         $this->PlanMakeEntry("9000","DROP ROLE ".$row['rolname']);
      }
   }
   
   // 10/20/06, converted all group creation into user creation
   //           convert existing groups to users
   $ts=time();
	$this->LogEntry("Creating any groups that don't exist");
	$results = $this->SQLRead(
      "SELECT group_id,group_id_r,permrole FROM zdd.groups_d 
        WHERE group_id <> '$app'"
   );
	while ($row = pg_fetch_array($results)) {
      $gname = $row[0];
      $cr=($row['permrole']=='Y' ? '' : 'NO').'CREATEROLE';
      //$gname = $gname==$parm['APP'] ? $gname : $parm['APP'].'_'.$gname;
      if($parm['ROLE_LOGIN']=='Y') {
         $extra = " LOGIN PASSWORD #$gname#";
      }
      else {
         $extra = " NOLOGIN ";
      }
      if(is_null($row['group_id_r'])) {
         //$this->LogEntry("Creating g-role: $gname");
         $this->PlanMakeEntry("9000","CREATE ROLE $gname $cr $extra");
         $this->PlanMakeEntry("9000","GRANT $app TO $gname");
      }
      else {
         //$this->LogEntry("Altering g-role: $gname");
         $this->PlanMakeEntry("9000","ALTER ROLE $gname $cr $extra");
         $this->PlanMakeEntry("9000","GRANT $app TO $gname");
      }
	}
   $this->LogElapsedTime($ts);
	
	$this->LogEntry("Generating table permissions commands");
   $ts=time();
   
   
   // Deny by default:  Create list of all groups, another list of
   //      all tables and all views, and deny access to all of them
   $res=$this->SQLRead(
      "Select group_id_eff as group_id 
         from zdd.groups_eff_c where flag_used='Y'
       UNION ALL 
       SELECT group_id FROm zdd.groups_c");
   $groups = pg_fetch_all($res);
   $res=$this->SQLRead(
      "Select table_id from zdd.tables_c 
       UNION ALL 
       (SELECT table_id || '_v_' || view_id
          FROM zdd.table_views_c
         GROUP BY table_id || '_v_' || view_id
       )"
   );
   $tv = pg_fetch_all($res);
   foreach($groups as $group) {
      $xg=$group['group_id'];
      foreach($tv as $one) {
         $xt = $one['table_id'];
         $this->PlanMakeEntry("9050","REVOKE ALL ON $xt FROM $xg");
      }
   }
   
   //  MAJOR AREA: SET PERMISSIONS FOR ROW/COL VIEWS.  
   // 
   //
   
   // KFD 7/16/07, Add the row-only views to table_views_c.
   //              This table now has a complete list of all custom
   //              views, and of all tables that have views.  This will
   //              be useful for setting the permissions.
   //              
   $this->SQL("
INSERT INTO zdd.table_views_c (table_id,group_id_eff,view_id) 
select t.table_id,g.group_id_eff,cast('99999' as char(5)) as view_id
  FROM zdd.tables_c     t,zdd.groups_eff_c g
 WHERE t.permrow = 'Y'
   AND COALESCE(t.permcol,'')<>'Y'
   ORDER BY t.table_id,g.group_id_eff");
   
   // KFD 7/16/07.  Now add permissions records for the 99999 views.
   //               These are easy because they are the same as the
   //               base table.
   // 
   $sq="insert into zdd.perm_tabs_c
             ( permins,permupd,permdel,permsel,nomenu,istable
              ,module,group_id,table_id,permspec)
select pt.permins,pt.permupd,pt.permdel,pt.permsel,pt.nomenu
      ,cast('V' as char(1)) as istable
      ,pt.module,pt.group_id
      ,trim(pt.table_id) || '_v_' || trim(v.view_id) as table_id
      ,'N' as permspec
  FROM zdd.perm_tabs_c pt
  JOIN (  SELECT table_id,view_id 
            FROM zdd.table_views_c
           WHERE view_id = '99999'
           GROUP BY table_id,view_id
       ) v
    ON pt.table_id = v.table_id";
    $this->SQL($sq);

    
    $sq="
-- IN ENGLISH:  Any group
--              whose column list matches an effective view's column list
--              gets its original table permissions on that view
--
-- SEMI-SQL: insert into perm_tabs
--           rows from perm_tabs
--           with table_id's converted to view_id's
--           if the group sees all of the columns in that view_id
-- THE WHERE SUB-QUERY:
--           pull columns the group sees
--           full other join to
--           pull columns the effective group sees
--           Only pull if there is a mismatch
--           If the query is not empty we don't want that combination    
INSERT INTO zdd.perm_tabs_c 
         (table_id,group_id,istable,permsel,permins,permupd,permdel,nomenu)
select  pc.table_id || '_v_' || tv.view_id as table_id
       ,pc.group_id
       ,'V' as istable
       ,pc.permsel,pc.permins,pc.permupd,pc.permdel,pc.nomenu
  FROM zdd.perm_tabs_c                    pc
  JOIN (SELECT distinct table_id,view_id
          FROM zdd.table_views_c 
       )                                  tv 
    ON pc.table_id = tv.table_id
 WHERE (permsel='Y' OR permins='Y' OR permupd='Y' OR permdel='Y' or nomenu='N')
   AND tv.view_id <> '99999'
   AND NOT EXISTS
(
select e.column_id 
      ,case when e.table_id IS NULL THEN 1 ELSE 0 END AS notineff
      ,b.column_id
      ,case when b.table_id IS NULL THEN 1 ELSE 0 END AS notinbase
      ,b.table_id,b.group_id
  FROM (SELECT pc2.table_id,pc2.column_id
          FROM zdd.perm_cols_c    pc2
          JOIN zdd.groupsx_c      gx  ON pc2.group_id = gx.group_id
         WHERE gx.group_id_eff  = '{$app}_eff_' || tv.view_id
           AND pc2.table_id     = pc.table_id                           
           AND pc2.permsel      = 'Y'  )   e
  FULL OUTER JOIN
       (SELECT table_id,column_id,group_id
          FROM zdd.perm_cols_c
         WHERE group_id = pc.group_id
           AND table_id = pc.table_id
           AND permsel  = 'Y'      )       b
    ON e.table_id = b.table_id
   AND e.column_id= b.column_id
 WHERE b.table_id IS NULL OR e.table_id IS NULL
)";
   $this->SQL($sq);
   
 
    
   // DONE!  Now execute them!
   //        Note the filter that does not apply any perms to
   //        tables that have column or row security
	$results = $this->SQLRead("
SELECT pt.group_id,pt.table_id
      ,pt.permins,pt.permupd,pt.permdel,pt.permsel
      ,pt.istable
  FROM zdd.perm_tabs_c   pt
  LEFT JOIN zdd.tables_c  t   ON pt.table_id = t.table_id  
 WHERE istable<>'M'
   AND COALESCE(t.permrow,'N') <> 'Y'
   AND COALESCE(t.permcol,'N') <> 'Y'"
   );
	while ($row = pg_fetch_array($results)) {
      $tab=$row['table_id']; 
      $grp=$row['group_id'];
      //  Rename table in terms of the effective group
      //if($row['istable']=='V') {
      //   $tab.='_'.substr($grp,strlen($app)+5);
      //}
      
      $GRANT="";
      if ($row["permins"]=="Y") { $GRANT.=$this->AddComma($GRANT)."INSERT"; }
      if ($row["permupd"]=="Y") { $GRANT.=$this->AddComma($GRANT)."UPDATE"; }
      if ($row["permdel"]=="Y") { $GRANT.=$this->AddComma($GRANT)."DELETE"; }
      if ($row["permsel"]=="Y") { $GRANT.=$this->AddComma($GRANT)."SELECT"; }
      if ($GRANT!="") { 
         $this->PlanMakeEntry("9100","GRANT $GRANT on $tab TO GROUP $grp"); 
      }
      
      // Do not do revokes, our deny from all took care of that.
      //
      /*
      $GRANT="";
      if ($row["permins"]=="N") { $GRANT.=$this->AddComma($GRANT)."INSERT"; }
      if ($row["permupd"]=="N") { $GRANT.=$this->AddComma($GRANT)."UPDATE"; }
      if ($row["permdel"]=="N") { $GRANT.=$this->AddComma($GRANT)."DELETE"; }
      if ($row["permsel"]=="N") { $GRANT.=$this->AddComma($GRANT)."SELECT"; }
      if ($GRANT!="") { 
         $this->PlanMakeEntry("9100","REVOKE $GRANT on $tab FROM GROUP $grp");
      }
      */
	}

	//  This is for sequences, we may need them later
	$results = $this->SQLRead("select def_short FROM zdd.ns_objects_c where def_short like 'sequence:%'");
	while ($row = pg_fetch_array($results)) {
		$seq_arr = explode(":",$row["def_short"]);
		$this->PlanMakeEntry("9110","grant all on ".$seq_arr[1]." to group ".$parm["APP"]); 
	}
   $this->LogElapsedTime($ts);

}

function PlanMakeEntry($cmdseq,$cmdtext)
{
    $this->cmdsubseq+=1;
	$this->SQL(
		"Insert into zdd.ddl (cmdseq,cmdsql,cmdsubseq)  
		values (
		'$cmdseq', 
		'$cmdtext',
        $this->cmdsubseq)");
}

// ==========================================================
// REGION: Plan Execution
// ==========================================================
function PlanExecute() {
	$this->LogStage("Executing Plan");
    $ts=time();
    global $parm;
    $retval = true;

    // Work out the statistics on how many commands we
    // need to execute   
    $cmdseq=array(
        2900=>'Drop View'
        ,3000=>'CREATE Table commands'
        ,3010=>'ALTER TABLE commands'      
        ,3050=>'CREATE VIEW commands - row security'
        ,3051=>'CREATE VIEW commands - column security'
        ,3060=>'Create RULE commands'
        ,4000=>'NSO Drop Commands'
        ,5010=>'Backfill commands'
        ,6000=>'NSO Create Commands'
        ,6001=>'Trigger Create Part 2'
        ,6002=>'FreeJoin Security Commands'
        ,6050=>'Sequence Update Commands'
        ,9000=>'Group and User Creation'
        ,9050=>'Deny by default REVOKE commands'
        ,9100=>'Permission REVOKE and GRANT'
        ,9110=>'Grant EXECUTE on sequences'
        ,9200=>'Reset all users effective groups'
    );
    $bunchers=array(3060,4000,6050,9000,9050,9100,9110);
    $noerrors=array(2900);
    // We skipped: 6000, contains trigger create commands
    // We skipped: 3050, 3051 create view commands
    $dbres=$this->SQLRead(
      'SELECT cmdseq,count(*) as cnt 
         FROM zdd.ddl 
        GROUP BY cmdseq
        ORDER BY cmdseq'
    );
    $stats=pg_fetch_all($dbres);
    $this->LogEntry(date("Y-m-d H:i:s a",time()));
    foreach($stats as $stat) {
        $le="There are ".$this->hCount($stat['cnt'])
            ." commands of type "
            .$stat['cmdseq']
            .', '.$this->zzArraySafe($cmdseq,$stat['cmdseq'],'NOT DEFINED');
        $this->LogEntry($le);
    }
   
    // Now execute the commands
    $FILEOUT=fopen($parm["DIR_TMP"].$parm["APP"].".plan","w");
    foreach($stats as $stat) {
      $noreperr = in_array($stat['cmdseq'],$noerrors);
      $this->LogEntry("");
      $this->LogEntry(
         "Executing type ".$stat['cmdseq']
         .' '.$cmdseq[$stat['cmdseq']]
      );
      $ts=time();
      $res=$this->SQLRead(
         "SELECT oid,cmdsql,cmdseq 
            FROM zdd.ddl
           WHERE cmdseq = '".$stat['cmdseq']."'
           ORDER BY cmdsubseq"
      );
      /*
      if(in_array($stat['cmdseq'],$bunchers)) {
         $this->LogEntry("Executing as a compound statement.");
         // Put them all into one command and execute
         // together as one command
         $cmds=pg_fetch_all($res);
         $cmdsql='';
         foreach($cmds as $cmd) {
            $cmdsql.="\n".$cmd['cmdsql'].";";
         }
         $retval = $retval &&
            $this->PlanExecuteCommand($cmdsql,$FILEOUT,$noreperr);
      }
      else {
          */
         $this->LogEntry("Executing as individual statements.");
         while ($row=pg_fetch_array($res)) {
            $TheCmd=$row["cmdsql"];
            //$TheOID=$row["oid"];
            $retval = $retval &&
                $this->PlanExecuteCommand($TheCmd,$FILEOUT,$noreperr);
         }
         /*
      }
      */
      $this->LogElapsedTime($ts);
        if(!$retval) break;      
   }
	fclose($FILEOUT);

   $this->LogEntry("");
   $this->LogEntry("DDL Plan execution completed");
	$this->LogEntry(date("Y-m-d H:i:s a",time()));
	return $retval;
}


function PlanExecuteCommand($TheCmd,$FILEOUT,$noreperr=false) {
   global $parm;
   $retval=true;
   
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


   if ($this->SQL($TheCmd,$noreperr,false))	{
       $TheRes = "'Y'";	$cmdErr = "''"; 
   }
   else { $TheRes = "'N'";
       $retval = false;
       $cmdErr = $this->sqlCommandError;
       //$this->LogEntry($TheCmd);
       $this->LogEntry("ERROR: >> ");
       $this->LogEntry("ERROR: >> BUILD CANNOT CONTINUE.");
       $this->LogEntry("ERROR: >> Please see command and error just above");
       $this->LogEntry("ERROR: >> ");
   }

   fputs($FILEOUT,"-----------------------------\n");
   fputs($FILEOUT,"SUCCESS: ". $TheRes." ".date('r')."\n");
   fputs($FILEOUT,"=============================\n");
   return $retval;
}


// ==========================================================
// REGION: ContentLoad
// Load group information into the node manager tables
// ==========================================================
function ContentLoad() {
	$this->LogStage("Loading data to user tables");
	$this->DBB_LoadContent(false,$this->content,"","");
	return true;
}


// ==========================================================
// REGION: ContentDD
// Copy all tables in the dd module 
// ==========================================================
function ContentDD() {
	$this->LogStage("Copying Data Dictionary into public schema");
   $this->LogEntry("  Each table has its triggers disabled ");
   $this->LogEntry("  and is completely repopulated.");
	$sql="SELECT table_id FROM zdd.tables_c WHERE module='datadict'";
   $res=$this->SQLRead($sql);
   $tabs=pg_fetch_all($res);
   foreach($tabs as $tabrow) {
      $table_id=$tabrow['table_id'];
      $cs=array_keys($this->utabs[$table_id]['flat']);
      $cs=implode(',',$cs);
      $this->LogEntry("Processing $table_id");
      $this->SQL("ALTER TABLE $table_id DISABLE TRIGGER ALL");
      $this->SQL("DELETE FROM $table_id");
      $sq="INSERT INTO $table_id ($cs) SELECT $cs FROM zdd.$table_id"."_c";
      //hprint_r($sq);
      $this->SQL($sq);
   }
	return true;
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
            ,nextval('users_SEQ_skey')
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
      SELECT s.usename,g.groname,nextval('usersxgroups_SEQ_skey')
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
		strtoupper(trim($table_id))."_SEQ_".
		strtoupper(trim($column_id));
}
		
function DBB_RunOut($tb, $sf) {
    // This giant bit of code runs out a single table 
    // pulling column definitions from all possible sources,
    // such as tabcol, tabflat
    //
    $tb = strtolower(trim($tb));
    
    // The second pull brings in the foreign key stuff
    $SQLFK = 
		"Insert into zdd.tabflat$sf 
		(table_id,column_id,description,table_id_src,column_id_src,
		 auto_table_id,auto_column_id,
		 table_id_fko,column_id_fko,
       range_to,range_from,
       value_min,value_max,
       uirows,uicols,
		 suffix,prefix,prevent_fk_change,
		 dispsize,uiwithnext,uino,
		 uicolseq,primary_key,pk_change,uisearch,
		 automation_id,auto_formula,
		 colprec,colscale,colres,type_id,formula,formshort,
		 uiinline,
		 required) 
		Select fk.table_id, 
		       rtrim(fk.prefix) || rtrim(f.column_id) || rtrim(fk.suffix),
		       rtrim(f.description) || 
		           CASE WHEN fk.description <> '' THEN ' (' || rtrim(fk.description) || ')'
		                ELSE '' END as description, 
		       f.table_id,f.column_id,
		       '' as auto_table_id,'',
		       f.table_id,f.column_id,
             '','',
             f.value_min,f.value_max,
             f.uirows,f.uicols,
		       fk.suffix,fk.prefix,
		       COALESCE(fk.prevent_fk_change,'N') as prevent_fk_change,
		       f.dispsize,f.uiwithnext,f.uino,
		       RTRIM(fk.uicolseq) || '.' || f.uicolseq, 
		       fk.primary_key,fk.pk_change,fk.uisearch, 
		       'NONE' as automation_id,'' as auto_formula, 
		       f.colprec,f.colscale,f.colres,
		       CASE WHEN f.column_id = 'skey' THEN 'I'   else f.type_id END as \"type_id\", 
		       CASE WHEN f.column_id = 'skey' THEN 'INT' else f.formula END as \"formula\", 
		       f.formshort,
		       '',
		       f.required
		  FROM zdd.tabfky$sf fk 
		  JOIN zdd.tabflat$sf f ON fk.table_id_par = f.table_id 
		 WHERE fk.table_id = '$tb'
		   AND f.primary_key = 'Y'
		   AND fk.nocolumns <> 'Y'
         AND f.range_from = ''
         AND NOT EXISTS (SELECT table_id
                            FROM zdd.tabflat$sf 
                           WHERE table_id = fk.table_id
                             AND column_id = rtrim(fk.prefix) 
                                          || rtrim(f.column_id)
                                          || rtrim(fk.suffix))";
   
   
	$SQLC = 
		"Insert into zdd.tabflat$sf 
		(table_id,column_id,description,table_id_src,column_id_src,
		 auto_table_id,auto_column_id,
		 table_id_fko,column_id_fko,
       range_to,range_from, 
       value_min,value_max, 
       uirows,uicols,
		 suffix,prefix,auto_suffix,auto_prefix,prevent_fk_change,
		 dispsize,uiwithnext,uino,
		 uicolseq,primary_key,pk_change,uisearch,
		 automation_id,auto_formula,
		 colprec,colscale,colres,type_id,formula,formshort,
		 uiinline,
		 required) 
		Select tc.table_id,
		       tc.column_id,".
		$this->DBB_RunOutOverride("description","tc","c").",
		       tc.table_id,tc.column_id_src,
		       tc.auto_table_id,tc.auto_column_id,		
		       '','',
             tc.range_to,tc.range_from,
             CASE WHEN COALESCE(tc.value_min,'') <> '' 
                   THEN tc.value_min
                   ELSE c.value_min   END,
              CASE WHEN COALESCE(tc.value_max,'') <> '' 
                   THEN tc.value_max
                   ELSE c.value_max   END,                   
              CASE WHEN COALESCE(tc.uirows,0) <> 0 
                   THEN tc.uirows
                   ELSE c.uirows   END,                   
              CASE WHEN COALESCE(tc.uicols,0) <> 0 
                   THEN tc.uicols
                   ELSE c.uicols   END,
		       suffix,prefix,auto_suffix,auto_prefix,'', 
		       t.dispsize,
		       CASE WHEN tc.uiwithnext<>'' THEN tc.uiwithnext 
		            WHEN c.uiwithnext<>''  THEN c.uiwithnext ELSE t.uiwithnext END,
		       tc.uino,
		       tc.uicolseq,tc.primary_key,tc.pk_change,tc.uisearch,
		       CASE WHEN tc.automation_id <> '' 
		            THEN tc.automation_id 
		            ELSE c.automation_ID END as automation_id, 
		       CASE WHEN tc.auto_formula <> '' 
		            THEN tc.auto_formula 
		            ELSE c.auto_formula END as auto_formula, 
		       c.colprec,c.colscale,
		       CASE WHEN tc.colres <> 0 THEN tc.colres ELSE c.colres END,
		       c.type_id,
		       t.formula, t.formshort,".
  		$this->DBB_RunOutOverride("uiinline","tc","c")."
		       ,CASE WHEN tc.required    <> '' THEN tc.required  
		            ELSE  c.required    END
		  FROM zdd.tabcol$sf  tc 
		  JOIN zdd.tables$sf  tab ON tc.table_id = tab.table_id  
		  JOIN zdd.columns$sf  c ON tc.column_id_src = c.column_id 
		  JOIN zdd.type_exps$sf  t ON c.type_id = t.type_id 
		 WHERE tc.table_id = '$tb'
         AND NOT EXISTS (SELECT skey 
                            FROM zdd.tabflat_c x
                           WHERE x.table_id      = tc.table_id
                             AND x.column_id_src = tc.column_id
                             AND x.suffix        = tc.suffix
                             AND x.prefix        = tc.prefix
                          )";

	$this->SQL($SQLC);
    $this->SQL($SQLFK);
		

	// now override any explicit foreign key definitions
	$SQL = 
		"update zdd.tabflat_c  
		  set  automation_id = CASE WHEN z.automation_id = '' 
                             THEN zdd.tabflat_c.automation_id 
                             ELSE z.automation_id END
		      ,auto_formula  = CASE WHEN z.auto_formula  = '' 
                             THEN zdd.tabflat_c.auto_formula  
                             ELSE z.auto_formula  END
		      ,description   = ".$this->DBB_RunOutOverride('description','z','zdd.tabflat_c')."
		      ,uino          = ".$this->DBB_RunOutOverride('uino'       ,'z','zdd.tabflat_c')."
		      ,uiwithnext    = ".$this->DBB_RunOutOverride('uiwithnext' ,'z','zdd.tabflat_c')."
		  FROM zdd.tabfkycol_c z
		  WHERE zdd.tabflat_c.table_id  = '$tb'
		    AND z.table_id              = '$tb'
		    AND zdd.tabflat_c.table_id_src  = z.table_id_par
		    AND zdd.tabflat_c.suffix        = z.suffix
		    AND zdd.tabflat_c.prefix        = z.prefix
		    AND zdd.tabflat_c.column_id     = z.column_id";
	$this->SQL($SQL);
   
    // KEYWORD: TROUBLE
    // KFD 6/1/07.  This correction would not be necessary if we 
    //     eliminated SpecFlatten_Columns1(), or worked the sequence
    //     of it into dbb_runout more properly
    $SQL="UPDATE zdd.tabflat_c
            SET  table_id_fko = f.table_id
                ,column_id_fko= f.column_id
  		     FROM zdd.tabfky$sf fk 
		     JOIN zdd.tabflat$sf f ON fk.table_id_par = f.table_id 
		    WHERE fk.table_id            = '$tb'
            AND zdd.tabflat_c.table_id = '$tb'
            AND zdd.tabflat_c.column_id
                = trim(fk.prefix) || trim(f.column_id) || trim(fk.suffix)
		      AND f.primary_key = 'Y'
		      AND fk.nocolumns <> 'Y'
            AND f.range_from = ''";
    $this->SQL($SQL);
             
   
    // Any columns created in this table by using prefix/suffix, should now
    // be put into tabflat so they can be referenced by other downstream
    // tables.  Only safe to be used in agg, fetch, etc.
    $SQL = 
		"insert into zdd.columns_c ( 
          column_id, description 
          ,automation_id, auto_table_id, auto_column_id, auto_formula
          ,ins,uiro,uino,required,dispsize,uiwithnext,prefix_table_name
          ,value_min,value_max
          ,type_id,colprec,colscale,colres
          ,uiinline,alltables )
       select
          f.column_id, f.description 
          ,c.automation_id, c.auto_table_id, c.auto_column_id, c.auto_formula
          ,c.ins,c.uiro,c.uino,c.required,c.dispsize,c.uiwithnext,c.prefix_table_name
          ,c.value_min,c.value_max
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
    // KFD 12/8/07 New pre-scan, try to prevent known
    //   syntax errors that will not be reported by spyc
    // 
    if(!$this->DBB_LoadYAMLFile_PreScan($filename)) return false;
    
    // Now convert to YAML and dump
    include_once("spyc.php");
    $temparray=Spyc::YAMLLoad($filename);
    $this->YAMLError=false;
    
    $this->YAMLPrevious=array("no entries yet, look at top of file");
    $this->YAMLStack=array();
    $this->YAMLContent=array();
    $retarr['data']=$this->YAMLWalk($temparray);
    $retarr['content']=$this->YAMLContent;
    return !$this->YAMLError;
}

// Scan for indentation errors
function DBB_LoadYAMLFile_Prescan($filename) {
    $this->LogEntry("Scanning file for correct formatting");
    
    // Load file, get rid of carriage returns, explode into lines
    $fc = file_get_contents($filename);
    $fc = str_replace("\r","",$fc);
    $afc= explode("\n",$fc);
    
    $errors = 0;
    
    // Scan and look for stuff to report
    foreach($afc as $linenum=>$oneline) {
        $linenum++;
        // Clip off comments
        if(strpos($oneline,"#") !==false) {
            if(strpos($oneline,"#")===0) continue;
            $oneline = substr($oneline,0,strpos($oneline,'#')-1);
        }
        
        // If nothing but whitespace, skip this line
        if(strlen( preg_replace('/\s/','',$oneline) )==0) continue;
        
        // If any kind of bad quoting of "Y", spit out error
        if(preg_match('/[a-z]:\s*"[YN]\s*$/',$oneline)) {
            $this->LogEntry("Line $linenum, missing close quote");
            $errors++;
        }
        if(preg_match('/[a-z]:\s*[YN]"\s*$/',$oneline)) {
            $this->LogEntry("Line $linenum, missing open quote");
            $errors++;
        }
        if(preg_match('/[a-z]:\s*[YN]\s*$/',$oneline)) {
            $this->LogEntry("Line $linenum, missing quotes");
            $errors++;
        }
        
        // Look for bad indentations
        $m = array();
        preg_match('/^(\s*)[a-z]/',$oneline,$m);
        if(isset($m[1])) {
            if(strlen($m[1])>0) {
                if( (strlen($m[1]) % 2)==1) {
                    $this->LogEntry(
                        "Line $linenum, Bad indentation level: ".strlen($m[1])
                    );
                    $errors++;
                }
            }
        }
    }    
    return ($errors == 0);
}


function YAMLWalk($source) {
    global $parm;
   $destination=array();
   if(!is_array($source)) { 
       $this->YAMLWalkError('freetext',$source); 
       return $source;
   };
   foreach($source as $key=>$item) {
      // Error 1, usually caused by misplaced or missing semicolon
      if(is_numeric($key)) {
         $this->YAMLWalkError('numindex',$item);
      }
      else {
         //$split=explode(' ',$key);
         $split = preg_split('/\s+/',$key);
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
            // KFD 9/26/07, allow $LOGIN group
            if($name=='$LOGIN') $name = $parm['APP'];
            
            // DO 2/14/08, Check for existance of at least one uisearch set to yes
            if($type=='table') {
                $uisearch_found = false;
                foreach( $item as $key2=>$table ) {
                    $split2 = preg_split('/\s+/',$key2);
                    if ( $split2[0] == 'column' || $split2[0] == 'foreign_key' ) {
                        if ( count( $item[$key2] ) > 0 ) {
                            foreach( $item[$key2] as $key3=>$prop ) {
                                if( $key3 == 'uisearch' ) {
                                    if ( $prop == 'Y' ) {
                                        $uisearch_found = true;
                                    }
                                }
                            }
                        }
                    }
                }
                if ( !$uisearch_found ) {
                    $this->YAMLWalkError('uisearch',$item);
                }
            }
            if($type=="content") {
               $colnames=array('__type'=>'columns');
               $values = array();
               foreach($item as $key=>$stuff) {
                  if(is_array($stuff)) {
                     $values[]=array_merge(array('__type'=>'values'),$stuff);
                  }
                  else {
                     $cols=explode(' ',$key);
                     if(count($cols)<2) {
                        $this->YAMLWalkError('contentkey',$item);
                     }
                     else {
                        $colnames[] = $cols[1];
                     }
                  }
               }
               //$this->YAMLContent[$name][]=$colnames;
               $this->YAMLContent[$name]=Array_Merge(array($colnames),$values);
            }
            else {
               $uicolseq=str_pad(++$this->uicolseq,5,'0',STR_PAD_LEFT);
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
                     $keystub=substr($keystub,strlen($item['prefix']));
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
      case 'freetext':
         $this->LogEntry("ERROR IN YAML FILE, POSSIBLE FREE TEXT");
         $this->LogEntry("  It appears that there may be some unformatted");
         $this->LogEntry("  text in your file.  The value is:");
         break;
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
      case 'contentkey':
         $this->LogEntry("ERROR IN YAML FILE, BAD CONTENT");
         $this->LogEntry("  The only things you can put inside of 'content'");
         $this->LogEntry("  are the 'column colname:' and the");
         $this->LogEntry("  'values 00:' and 'values 01:' actual values.");
         break;
      case 'uisearch':
        $this->LogEntry("ERROR IN YAML FILE, MISSING TABLE REQUIREMENT");
        $this->LogEntry("    You must have at least one uisearch property defined");
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
			if (!isset($retarr['content'][$table])) { $retarr['content'][$table]=array(); }
			$retarr['content'][$table] = array_merge($retarr['content'][$table],$item);
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
		$row["uicolseq"]=str_pad(++$this->uicolseq,6,'0',STR_PAD_LEFT);
		$dst[$type][] = $row; }
	else {
		foreach ($id as $idone) {
			// the actual ID we will use is a composition.
			$idone = trim($idone);
			$iduse = trim($this->zzArray($row,"prefix")).$idone.trim($this->zzArray($row,"suffix"));
			
			if (!isset($dst[$type][$iduse])) {
				$row["uicolseq"]
               =str_pad(++$this->uicolseq,6,'0',STR_PAD_LEFT);
				$dst[$type][$iduse] = $row;
				$dst[$type][$iduse]["__keystub"] = $idone;
			}
			else { 
            array_merge($dst[$type][$iduse],$row);
            $dst[$type][$iduse]['uicolseq']
               =str_pad(++$this->uicolseq,6,'0',STR_PAD_LEFT);
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
		$pk    = $this->utabs[$table_id]["pk"];
      $flat  = &$this->utabs[$table_id]["flat"];
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
	
	foreach ($this->utabs[$table]["flat"] as $colname=>$colinfo) {
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
	return $this->SQL($sql);
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
   $localhost_suffix=$this->zzArraySafe($parm,'LOCALHOST_SUFFIX');
	$text = 
		"<?php\n".
		"\$AG['application']='".$parm["APP"]."';\n".
		"\$AG['app_desc']='".$parm["APPDSC"]."';\n".
      "\$AG['localhost_suffix']='".$localhost_suffix."';\n".
      "\$AG['template']='".$this->zzArraySafe($parm,'TEMPLATE')."';\n".
      "\$AG['flag_pwmd5']='".$parm['FLAG_PWMD5']."';\n".
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
   $app = $GLOBALS['parm']['APP'];
	
   // Pull from tables, add in whether there are col/row perms
	$results = $this->SQLRead("Select * from zdd.tables_c");
	$resrows = pg_fetch_all($results);
   
   // Pull a list of columns that *CAUSE* calculations to run,
   // these are the inputs of EXTEND, and the outputs of FETCH
   $sq="SELECT table_dep  as table_id
              ,column_dep as column_id
         FROM zdd.column_deps 
        WHERE automation_id='EXTEND'
        UNION 
        SELECT table_id 
              ,column_id
         FROM zdd.column_deps
        WHERE automation_id IN ('FETCH','FETCHDEF')";
   $results=$this->SQLRead($sq);
   $deps=pg_fetch_all($results);
   //hprint_r($deps);
   $calcs=array();
   if($deps) {
      foreach($deps as $dep) {
         $calcs[$dep['table_id']][]=$dep['column_id'];
      }
   }
   
   
   // Pull a sequenced list of columns out.  This is not the
   // user's sequence, but the generated sequence.
   $sq="select *  from zdd.column_seqs  order by table_id,sequence";
   $results=$this->SQLRead($sq);
   $seqsret=pg_fetch_all($results);
   $seqs=array();
   foreach($seqsret as $seqret) {
      $seqs[$seqret['table_id']][]=$seqret['column_id'];
   }
   
	
   // We will also need the list of composite groups
   $results=$this->SQLRead("Select * from zdd.groups_c WHERE grouplist<>''");
   $groups=pg_fetch_all($results);
   
	foreach($resrows as $utab) {
		// First put the table stuff into the array
		$table_id = $utab["table_id"];
		$table = $this->zzArrayAssociative($utab);
      
      // Clean out some clutter.  
      if(array_key_exists('skey_hn',$table))  unset($table['skey_hn']);
      if(array_key_exists('skey_s' ,$table))  unset($table['skey_s']);
      if(isset($table['column_prefix']))      unset($table['column_prefix']);
      if(isset($table['ins']))                unset($table['ins']);
      if(isset($table['risimple']))           unset($table['risimple']);
      if(isset($table['rules']))              unset($table['rules']);
      if(isset($table['menuins']))            unset($table['menuins']);
      if(isset($table['uisort']))             unset($table['uisort']);
      if(isset($table['skey']))               unset($table['skey']);
      if(isset($table['skey_quiet']))         unset($table['skey_quiet']);
      if(isset($table['_agg']))               unset($table['_agg']);
      if(isset($table['skey_hn']))            unset($table['skey_hn']);
      if(isset($table['skey_s']))             unset($table['skey_s']);
      
		// Now put columns into the array 
		$table["flat"] = array();
		$results = $this->SQLRead("Select * from zdd.tabflat_c WHERE table_id = '$table_id' ORDER BY uicolseq");
		while ($row = pg_fetch_array($results)) {
         if(array_key_exists('skey_quiet',$row)) unset($row['skey_quiet']);
         if(array_key_exists('skey'      ,$row)) unset($row['skey']);
         if(array_key_exists('_agg'      ,$row)) unset($row['_agg']);
         if(array_key_exists('skey_hn'   ,$row)) unset($row['skey_hn']);
         if(array_key_exists('skey_s'    ,$row)) unset($row['skey_s']);
         
         // If there is a foreign key, grab his fkdisplay setting
         $row['fkdisplay']='';
         if($row['table_id_fko']<>'') {
            //hprint_r($this->utabs[$row['table_id_fko']]);
            $row['fkdisplay']=$this->utabs[$row['table_id_fko']]['fkdisplay'];
         }

         $row['uicolseq']=trim($row['uicolseq']);
			$table["flat"][$row["column_id"]] = $this->zzArrayAssociative($row);
		}
      
      // Put in a list of columns that force recalculations of the row
      $table['calcs']=array();
      if(count($this->zzArraySafe($calcs,$table_id,array()))>0) {
         foreach($calcs[$table_id] as $colid) {
            $table['calcs'][]=$colid;
         }
      }
      
      // Add in the ordered list of columns
      $table['sequenced']=$this->zzArraySafe($seqs,$table_id,array());
      
      // If there are chains for the table, run those out
      if(count($this->zzArraySafe($this->utabs[$table_id],'chains',array()))>0) {
         $this->LogEntry("Found chains for $table_id");
         foreach ($this->utabs[$table_id]['chains'] as $chain) {
            if($chain['chain']=='calc') {
               $table['flat'][$chain['column_id']]['chaincalc']=$chain;
            }
         }
      }
      
      // create blank projections array
      $table['projections']=array();
      
      // Copy in the fk fetch stuff
      $table['FETCHDIST']=$this->zzArraySafe($this->utabs[$table_id],'FETCHDIST',array());
		
		// Loop through rows creating some projections
		$pks = $u_uisearch = $u_uino ="";
		foreach ($table["flat"] as $colname=>$colinfo) {
			if ($this->zzArraySafe($colinfo,"primary_key")=="Y") 
            $pks       .=$this->AddComma($pks).$colname;
			if ($this->zzArraySafe($colinfo,"uisearch")=="Y") 
            $u_uisearch.=$this->AddComma($u_uisearch).$colname;
			if ($this->zzArraySafe($colinfo,"uino")=="Y") 
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
		
      // Copy out the row definitions for views, if there are any
      //
      $table['views']=$this->zzArraySafe($this->ViewDefs,$table_id,array());
      
      $table['tableresolve']=array();
      $res=$this->SQLRead(
         "select * from zdd.table_views_c where table_id='$table_id'"
      );
      $tabres=pg_fetch_all($res);
      if($tabres) {
         foreach($tabres as $tr) {
            $table['tableresolve'][$tr['group_id_eff']]
               =$table_id.'_v_'.$tr['view_id'];
         }
      }
		
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
         // Get row and trim it down a little
			$rowx = $this->zzArrayAssociative($row);
         if(array_key_exists('skey_quiet',$rowx)) unset($rowx['skey_quiet']);
         if(array_key_exists('skey'      ,$rowx)) unset($rowx['skey']);
         if(array_key_exists('_agg'      ,$rowx)) unset($rowx['_agg']);
         if(array_key_exists('skey_hn'   ,$rowx)) unset($rowx['skey_hn']);
         if(array_key_exists('skey_s'    ,$rowx)) unset($rowx['skey_s']);
         
         // Get the "colsboth", we'll need that
         $fk=$table_id.'_'.Trim($rowx[$col2]).'_'.trim($row['suffix']);
         if(isset($this->ufks[$fk])) {
            $ufk=$this->ufks[$fk];
            $rowx['cols_chd']  = $ufk['cols_chd'];
            $rowx['cols_par']  = $ufk['cols_par'];
            $rowx['cols_both'] = $ufk['cols_both'];
            $rowx['fkdisplay'] = $this->utabs[$rowx[$col2]]['fkdisplay'];
         }
         else {
            $rowx['cols_chd']  = 'X';
            $rowx['cols_par']  = 'X';
            $rowx['cols_both'] = 'X';
            $rowx['fkdisplay'] = '';
         }
         
         $utab = $this->utabs[$row[$col2]];
         $rowx['description'] = $utab['description'];
         $rowx['module'] = $utab['module'];
         
	

         // Establish key and save			
			$key = $rowx["prefix"].$rowx[$col2].$rowx["suffix"];
			$retval[$key] = $rowx;
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
       ,t.nomenu,'N' as menuins
       ,t.linknew,t.linksearch
       ,'' as menu_parms
  FROM zdd.modules_c m
  JOIN zdd.tables_c t ON t.module = m.module
 WHERE t.nomenu <> 'Y'  
 UNION ALL 
 SELECT  m.module,m.description as module_text,m.uisort,u.uisort
        ,u.menu_page as table_id
        ,u.description as table_text 
        ,'N' as nomenu,'N' as menuins
        ,'N' as linknew,'N' as linksearch
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
			//$file.="\$AGMENU[#".$row["module"]."#]=array();\n";
			$file.="\$AGMENU[#".$row["module"]."#][#description#] = #".$row["module_text"]."#;\n";
			//$file.="\$AGMENU[#".$row["module"]."#][#items#]=array();\n";
			$module = $row["module"];
		}
		
		//if ($row["nomenu"]=="Y") {
			$file.="\$AGMENU[#".$row["module"]."#][#items#][#".$row["table_id"]."#] = ".
				"array(#name#=>#".$row["table_id"]."#,".
				"#description#=>#".$row["table_text"]."#,".
				"#menu_parms#=>#".$row["menu_parms"]."#,".
				"#linknew#=>#".$row["linknew"]."#,".
				"#linksearch#=>#".$row["linksearch"]."#,".
				"#mode#=>#normal#);\n";

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
// Run Build Scripts
// =====================================================================
function buildScripts() {
	$this->LogStage("Running Build Scripts");
    global $parm;
    
    // Get director, pull files, sort them, pop out
    // the first two, which will be . and ..
    $dir = $parm['DIR_PUB'].'/application/scripts/';
    $this->logEntry("Will look for scripts here: ");
    $this->logEntry($dir);
    $this->LogEntry("");
    if(!is_dir($dir)) {
        $this->LogEntry("Script directory does not exist, nothing to do!");
        return true;
    }

    // alphabetize and pop off the . and ..
    $scripts = scandir($dir);
    sort($scripts);
    array_shift($scripts);
    array_shift($scripts);
    if(count($scripts)==0) {
        $this->LogEntry("No scripts, nothing to do!");
        return true;
    }
        
    // Get the scripts that have been cleared
    $finished = $this->SQLReadRows("Select * from scripts");
    $completed = array();
    if(is_array($finished)) {
        foreach($finished as $onedone) {
            $completed[$onedone['script']] = $onedone['script'];
        }
    }
    
    // Run em!
    foreach($scripts as $script) {
        if(in_array($script,$completed)) {
            $this->logEntry("NOT RUNNING ".$script);
            continue;
        }
        // Ignore SVN
        if(substr($script,0,4)=='.svn') continue;
        $this->ScriptSet($script);
        
        $this->logEntry("");
        $this->logEntry("Executing Script: $script"); 
        include($dir.$script);
    }
    
    return true;
}

function ScriptSet($script) {
    $this->runningScript = $script;
}

function ScriptSuccess() {
    $sql="insert into scripts (script) values ('$this->runningScript')";
    $this->SQL($sql);
    $this->LogEntry("  --- Script reported success --- ");
    $this->LogEntry("");
}

function checkSuccess($script) {
    $sql="SELECT * from scripts where script ='$script'";
    $count = $this->SQLReadRows($sql);
    return (count($count)>0) ? true : false;
    
}


// ==========================================================
// Database Access Routines
// ==========================================================
function SQLRead($sqlText,$noReport=false) {
	//$errlevel = error_reporting(0);
   
	//pg_send_query($dbconn2,$sqlText);
	//$results=pg_get_result($dbconn2);
	$results=pg_query($this->dbconn2,$sqlText);
	if (!$noReport) {
		if ($t=pg_result_error($results)) { 
			$this->LogEntry("");
			$this->LogEntry("**** SQL ERROR ****: ".$t);
			$this->LogEntry("Command was: " );
			$this->LogEntry($sqlText,true);
			$this->sqlCommandError=$sqlText;
		}
	}
	//error_reporting($errlevel);
	return $results;
}

function SQL_ANDRO($sqlText) {
	return $this->SQL($sqlText,false,true,$GLOBALS["dbconna"]);	
}

function SQLReadRow($dbres) {
    return pg_fetch_array($dbres);
}
function SQLReadRows($sqlText) {
    $dbres = $this->SQLRead($sqlText);
    $retval = pg_fetch_all($dbres);
    
    if(!$retval) return array();
    else return $retval;
}


function SQL($sqlText,$noReport=false,$split80=true,$dbx=null) {
	if (is_null($dbx)) { $dbx = $this->dbconn1;}
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
        case 'ssn':
        case 'ph12':
		case "gender":
			return $this->SQLFORMATLITERAL('',$type,$fortheplan,$doubleplan);
			break;
   		case "cbool":
			return $this->SQLFORMATLITERAL('N',$type,$fortheplan,$doubleplan);
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
      case 'ssn':
      case 'ph12':
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
   $retval=true;
   $retval = $retval && $this->FS_PrepareCheck();
   $retval = $retval && $this->FS_PrepareMake();
   return $retval;
}

function FS_PrepareCheck() {
	$this->LogStage("Confirming server has proper file permissions");
	$grp = $this->ShellWhoAmI();
   global $parm;
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
    // KFD 12/21/07, process only directories named in node manager,
    // lets you ignore directories you create yourself.
    //
    // KFD 2/5/08, fix huge bug introduced by this.  On a new
    //             install this does not work, must have hardcoded dir
    if(isset($this->dirsAll)) {
        $dirs = $this->dirsAll;
    }
    else {
        $dbres=pg_query($GLOBALS["dbconna"],"SELECT * FROM appdirs");
        $dirs =pg_fetch_all($dbres);
    }
    foreach($dirs as $dir) {
        $dir_pubx2=$dir_pubx.$this->FS_ADDSLASH($dir['dirname']);
        if(is_dir($dir_pubx2)) {
            $SCRIPT .= $this->FS_CHECKDIR($dir_pubx2,$grp,true);
        }
    }
	//$SCRIPT .= $this->FS_CHECKDIR($dir_pubx,$grp,true);
	if ($SCRIPT<>"") {
		return $this->FS_PrepareFail($SCRIPT);
	}
    
   
    return true;
}

function FS_PrepareMake() {
    $grp = $this->ShellWhoAmI();
    global $parm;
    $app = $GLOBALS["parm"]["APP"];
    $dir_pub = $this->FS_ADDSLASH($GLOBALS["parm"]["DIR_PUBLIC"]);
    $dir_pubx= $dir_pub.$this->FS_ADDSLASH($GLOBALS["parm"]["DIR_PUBLIC_APP"]);
	

    // Establish the source 
    $this->LogStage("Building Directories and Copying Files");
    if(isset($parm['IVER'])) {
        $dirl = AddSlash($parm['DIR_LINK_LIB']);
        $dira = AddSlash($parm['DIR_LINK_APP']);
    }
    else {
        $dirl = $dira = $GLOBALS['dir_andro'];       
    }

    // Now handle all of the subdirectories, including templates, lib,
    // clib and so forth.  Read them out of the node manager.
    //
    // KFD 2/5/08, fix huge bug introduced by this.  On a new
    //             install this does not work, must have hardcoded dir
    if(isset($this->dirsAll)) {
        $dirs = $this->dirsAll;
    }
    else {
        $dbres=pg_query($GLOBALS["dbconna"],"SELECT * FROM appdirs");
        $dirs =pg_fetch_all($dbres);
    }
    foreach($dirs as $row) {
        $tgt=trim($row['dirname']);
        $this->LogEntry("Processing subdir: $tgt");
        
        if(!file_exists($dir_pubx.$tgt)) {
            $this->LogEntry(" -> Creating this directory");
            mkdir($dir_pubx.$tgt);
        }

        // KFD 4/13/08, must remove minified JS files during build
        if($tgt=='clib') {
            $jsfiles=scandir($dir_pubx.$tgt);
            foreach($jsfiles as $jsfile) {
                if($jsfile=='.') continue;
                if($jsfile=='..') continue;
                if(substr($jsfile,-3)=='.js') { 
                    if(substr($jsfile,0,7)=='js-min-') {
                        $jsfile2 = "$dir_pubx$tgt/$jsfile";
                        $this->LogENtry("Deleting minified file: ".$jsfile2);
                        unlink($jsfile2);
                    }
                }
                if(substr($jsfile,-4)=='.css') { 
                    if(substr($jsfile,0,8)=='css-min-') {
                        $jsfile2 = "$dir_pubx$tgt/$jsfile";
                        $this->LogENtry("Deleting css combo file: ".$jsfile2);
                        unlink($jsfile2);
                    }
                }
            }
        }
      
        if($row['flag_copy']<>'Y') {
            $this->LogEntry(" -> Nothing will be copied for this directory.");
            if($tgt=='generated' || $tgt=='dynamic') {
                $this->LogEntry(" -> Purging this directory");
                //$cmd="rm $dir$tgt/*";
                //`$cmd`;
            }
        }
      else {
         // In this branch we have directories that must be copied.
         // They may be application or library directories, and this
         // may be an app or an instance, so there are a few more
         // switches.  And of course, nothing gets copied for the node
         // manager itself.
         //
         if($app=='andro') {
            $this->LogEntry(" -> NODE MANAGER build, no copy.");
         }
         else {
            if($row['flag_lib']=='Y') {
               $this->LogEntry(" -> Library copy from: $dirl");
               $this->LogEntry(" ->                to:  $dir_pubx");
               $this->FSCopyTree($dirl,$dir_pubx,$tgt);
            }
            else {
               if(!isset($parm['IVER'])) {
                  $this->LogEntry(" -> DEV Instance build, no copy");
               }
               else {
                  $this->LogEntry("Directory $tgt will be copied");
                  $this->LogEntry(" -> Application copy from: $dira");
                  $this->LogEntry(" ->                    to:  $dir_pubx");
                  $this->FSCopyTree($dira,$dir_pubx,$tgt);
               }
            }
         }
      }
      
      // Cleanup: Make sure hidden if required
      if($row['flag_vis']<>'Y') {
         $file=$dir_pubx.$tgt.'/.htaccess';
         $text="Deny From All";
         $this->FS_PUT_CONTENTS($file,$text);         
      }
   }


   $this->LogEntry("Copying /root files into root directory...");
   if(isWindows()) {
      $cmd="copy \"$dir_pubx\\root\\*\" \"$dir_pubx\"\\";
      $cmd=str_replace('/','\\',$cmd);
      $this->LogEntry("  ".$cmd);
      `$cmd`;
      $cmd="copy \"$dir_pubx/root/htaccess\" \"$dir_pubx/.htaccess\"";
      $cmd=str_replace('/','\\',$cmd);
      $this->LogEntry("  ".$cmd);
      `$cmd`;
      $cmd = "del \"$dir_pubx\\htaccess\"";
      $cmd=str_replace('/','\\',$cmd);
      $this->LogEntry("  ".$cmd);
      `$cmd`;
   }
   else {
      $cmd="cp $dir_pubx/root/* $dir_pubx/";
      `$cmd`;
      $cmd="cp $dir_pubx/root/htaccess $dir_pubx/.htaccess";
      `$cmd`;
      `rm $dir_pubx/htaccess`;
   }
   
   return true;
}

function FSCopyTree($src,$tgt,$name) {
   if(is_link($tgt.$name)) {
      // This little trick lets an Andromeda hacker work on an application
      // and Andromeda at the same time.  Delete the lib directory and
      // generate a symlink to andro/lib.  Then you can update files like
      // raxlib.php and watch the app get the changes in realtime.  This
      // switch here prevents catastrophic copies.
      $this->LogEntry(" -> Destination exists and is symlink, no action taken");
   }
   else {
      if(isWindows()) {
         $cmd="\"$src\\$name\*\" \"$tgt\\$name\"";
         $cmd=str_replace("/","\\",$cmd);
         $cmd=str_replace("\\\\","\\",$cmd);
         $cmd="xcopy /y /e /c /k /o ".$cmd;
         
      }
      else {
         $cmd="cp -r $src/$name/* $tgt/$name/";
      }
      $this->LogEntry($cmd);
      `$cmd`;
   }
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
        // Don't check subversion directories
        if(substr($dir,-5)==".svn/") return '';
        
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
	$grp = $this->ShellWhoAmI();
	
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
	// no need to depend on external system calls
    $handle = fopen($pLogPath, 'w');
    if (!$handle ) {
           $this->LogEntry("Cannot open file ($pLogPath)");
           return false;
	}
	fclose($handle);

	$GLOBALS["log_file"] = $pLogPath;	
    
    
    // KFD 2/5/08.  If installer is detected, hardcode some entries
    //              so the install can run.
    $x = dirname(__FILE__);
    if(file_exists($x.'/install.php')) {
        $this->dirsAll = array(
            array('dirname'=>'root'        ,'flag_copy'=>'Y','flag_lib'=>'Y','flag_vis'=>'N')
            ,array('dirname'=>'lib'        ,'flag_copy'=>'Y','flag_lib'=>'Y','flag_vis'=>'N')
            ,array('dirname'=>'clib'       ,'flag_copy'=>'Y','flag_lib'=>'Y','flag_vis'=>'Y')
            ,array('dirname'=>'application','flag_copy'=>'Y','flag_lib'=>'N','flag_vis'=>'N')
            ,array('dirname'=>'appclib'    ,'flag_copy'=>'Y','flag_lib'=>'N','flag_vis'=>'Y')
            ,array('dirname'=>'generated'  ,'flag_copy'=>'N','flag_lib'=>'N','flag_vis'=>'N')
            ,array('dirname'=>'files'      ,'flag_copy'=>'N','flag_lib'=>'N','flag_vis'=>'N')
            ,array('dirname'=>'tmp'        ,'flag_copy'=>'N','flag_lib'=>'N','flag_vis'=>'N')
            ,array('dirname'=>'apppub'     ,'flag_copy'=>'Y','flag_lib'=>'N','flag_vis'=>'Y')
            ,array('dirname'=>'dynamic'    ,'flag_copy'=>'N','flag_lib'=>'N','flag_vis'=>'N')
            ,array('dirname'=>'templates'  ,'flag_copy'=>'Y','flag_lib'=>'N','flag_vis'=>'Y')
            ,array('dirname'=>'instpub'    ,'flag_copy'=>'N','flag_lib'=>'N','flag_vis'=>'Y')
            ,array('dirname'=>'docslib'    ,'flag_copy'=>'Y','flag_lib'=>'Y','flag_vis'=>'N')
            ,array('dirname'=>'docsapp'    ,'flag_copy'=>'Y','flag_lib'=>'N','flag_vis'=>'N')
            ,array('dirname'=>'docsgen'    ,'flag_copy'=>'N','flag_lib'=>'N','flag_vis'=>'N')
         );
    }
    

   //  get the password if necessary
   if(function_exists("SessionGet")) {
      // this means called from web app, get current user's pw
      $GLOBALS['x_password']=SessionGet('PWD');
   }
   else {
      // Called from CLI, Unix user must be priveleged or it won't work
      $GLOBALS['x_password']='';
   }
	
    $parm = &$GLOBALS["parm"];
    if($this->zzArraySafe($parm,'ROLE_LOGIN','')=='') {
        $parm['ROLE_LOGIN']='Y';
    }
    if($this->zzArraySafe($parm,'FLAG_PWMD5','')=='') {
        $parm['FLAG_PWMD5']='N';
    }

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
   $this->LogEntry("ROLE Logins allowed    : ". $parm['ROLE_LOGIN']);
   $this->LogEntry("Hardened PW Security   : ". $parm['FLAG_PWMD5']);
	$this->LogEntry("Log File: ".$pLogFile);
	$this->LogEntry("---------------------------------------------------");
	$parm["DIR_WORKING"]=dirname(__FILE__);
	$this->LogEntry("Program executing in  : ". $parm["DIR_WORKING"]);
   $parm["DIR_TMP"]=$parm["DIR_PUB"]."tmp/";
	$this->LogEntry("Temporary files go to : ". $parm["DIR_TMP"]);
	$this->LogEntry("===================================================");
	
	return true;
}

function LogClose($ok,$ts) {
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
   $this->LogElapsedTime($ts);
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
         file_put_contents($GLOBALS['log_file'],$logText."\n",FILE_APPEND);
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
   $this->LogStageClose();
   $this->stage_ts=time();
   $this->stage_text=$logText;
	$this->LogEntry("");
	$this->LogEntry("---------------------------------------------------");
	$this->LogEntry($logText);
	$this->LogEntry("---------------------------------------------------");
	$this->LogEntry(date("Y-m-d H:i:s a",time()));
	$this->LogEntry("");
}

function LogStageClose() {                                  
   if(!isset($this->stage_ts)) return;
   
   $this->LogEntry("");
   $this->LogEntry("End of stage: ".$this->stage_text);
   $this->LogElapsedTime($this->stage_ts);
   $time=$this->hCount(time()-$this->stage_ts);
}

function LogElapsedTime($ts) {
   $time=$this->hCount(time()-$ts);
   $this->LogEntry(
      '   ...elapsed time: '.$time.' seconds'
      .' (build total: '.$this->hCount(time()-$this->ts).' seconds)'
   );
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
function zzArraySafe($arr,$key,$default='') {
	if (isset($arr[$key])) return $arr[$key]; else return $default;
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
	//$this->LogEntry("Writing Generated File: $fname");
	fwrite($FILEOUT,$content);
	fclose($FILEOUT);
}


function fsUnDeltree($tgt) {
   unlink($tgt);
}
function fsCopyPath($src,$dst) {
   
}


function ShellWhoAmI() {
   // By virtue of this small change this function needs to be renamed.
   //
   if (isWindows()) {
      // This will fail in IIS so run this from the command line.
      echo "Is windows\n";
      return $this->ArraySafe($_SERVER, "USERNAME", "");
   } else {
      echo "Not windows\n";
      return $this->ShellExec('whoami');
   }
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

function hCount($number) {
   $retval = number_format($number,0);
   return str_pad($retval,7,' ',STR_PAD_LEFT);
}

function getRange($formshort) {
    if($formshort == 'date') {
        $rzero = '##1/1/1000##';
        $rinfi = '##1/1/9999##';
        $rcast = 'date';
    }
    elseif(in_array($formshort,array('numeric','int'))) {
        $rzero = '-9999999';
        $rinfi =  '9999999';
        $rcast = 'numeric';
    }
    else {
        $rzero = '';
        $rinfi = 'ZZZZZZZZZ';
        $rcast = 'varchar';
    }
    return array($rzero,$rinfi,$rcast);
           
}

// =========================================================================
// End of Class Definition
// =========================================================================
}
// =========================================================================
// End of File
// =========================================================================
?>
