<?php
/**
name:x_docgen

NOTE: The information on this page is subject to change.  We are currently
perfecting the documentation system in a separate project called 'cms', and
when that is finished it will be moved into Andromeda as a library.

You can embed documentation into your code that will be read and processed
by the [[x_docgen]] program.

A comment should begin with "/**" and end with "*\/".  Every line before
the first blank line has parameters, everything after the first blank line
is taken to be the content.

Parameter lines go as "name:value", followed by a newline.  The parsing
program is very particular, it does not like whitespace in front of the
name, and it wants the entire name:value pair on a single line.  If it 
cannot find a colon on a line it quietly ignores the line.

'name', will be the name of the entry.  This should be description, including
spaces and punctuation where necessary.  Do not use this for methods or
functions, use it for everything else.  There can be only one 'name' or
'function' in each entry.  If there are more than one the last one wins
and overwrites earlier ones.

'function', is the name of an entry when the entry is a function or 
method.  There can be only one 'name' or
'function' in each entry.  If there are more than one the last one wins
and overwrites earlier ones.

'parm' names a parameter.  You can put any number of these.  They are 
ignored unless 'function' was used.

'returns' should list the return type.  You can only have one, if there is
more than one the last one will overwrite previous ones.  They are ignored
unless 'function' is used.

'parent' names the parent value.

If 'parent' is not specified the entry is quietly ignored.  If neither
'name' nor 'function' is specified the entry is quietly ignored.

You can also specify 'flag:framework' and a special note will be inserted
that says basically, "this is a framework function!"

You can make an entry with the name "_default_", and any parameters you
specify for that entry will apply to all entries that follow within the
same program file.  This is most useful for specifying the "parent" property
for several functions or doc blocks.

Each time you create a "name:_default_" block the previous defaults are
discarded.

*/
class x_docgen extends x_table2 {
   function construct_custom() {
      if(gp('gp_posted')<>'') { $this->flag_buffer=false; }
   }
   
   function main() {
      $this->PageSubtitle = "Documentation Generation";
      if(gp('gp_posted')=='1') return $this->ehProcess();
      ?>
      <h1>Code And Data Dictionary Scanning</h1>
      
      <p>This process will scan all code in this application and
         rebuild the documentation.  The program also scans the data
         dictionary and regenerates table definitions.
         Table definitions are not automatically
         rebuilt during a build, so this process must be run manually
         after each database rebuild.
      
      <p>The process can take anywhere from a few seconds to
         several minutes, depending on how many files and table are
         in the application.
         
      <p><font color=red>This process completely purges out and replaces
         the contents of the DOCPAGES table.  Any changes made to that
         table from other sources will be lost every time this program
         runs.</font></p>
         
      <br><br>
      <p>
      <a href="javascript:Popup('?gp_page=x_docgen&gp_posted=1','Code Scan')">
         Generate Documentation</a>
      <?php
   }

   function ehProcess() {
      ob_start();
      // Delete all current pages, then create the master category
      x_EchoFlush("BEGIN PROCESSING");
      x_EchoFlush("-----------------------------------------");
      x_EchoFlush("Purging generated pages...");
      //SQL("Delete from cms_hiers where flag_auto='Y'");
      //SQL("Delete from cms_pages where flag_auto='Y'");
      SQL("Delete from docpages where pagename='Documentation'");
      SQL("Delete from docpages where pagename='Data Dictionary'");
      $m1="Framework API Reference";
      $m2="Application Files";
      $m3="Data Dictionary";

      // This will store the hierarchy
      $this->parents=array();
      $this->parseqs=array();
      
      $this->pageUpdate('Data Dictionary','');
      /* UNBORK
      // Create/Update the basic pages
      $this->pageUpdate('Global Concepts'        ,'','Documentation',220);
      $this->pageUpdate('Database Reference'     ,'','Documentation',250);
      $this->pageUpdate('Framework Guide'        ,'','Documentation',300);
      $this->pageUpdate('Framework API Reference','','Documentation',400);
      $this->pageUpdate('Application Files'      ,'','Documentation',500);
      $this->pageUpdate('Essays'                 ,'','Documentation',800);
      $this->pageUpdate('Closing Matters'        ,'','Documentation',900);
      */
      
      // Run the two processes
      //$this->ehProcessCode($m1,$m2);
      $this->ehProcessData($m3);


      // Now insert the hierarchies
      //$table_hiers=DD_TableREf('cms_hiers');
          /* UNBORK
      foreach($this->parents as $child=>$info) {
          $sq="Update docpages set pagename_par = '".$info[0]."'"
            ." where pagename = '$child'";
          SQL($sq);
         $row=array(
            'pagename_par'=>$info[0]
            ,'sequence'=>$info[1]
            ,'pagename'=>$child
            ,'flag_auto'=>'Y'
         );
         scDBUpdateOrInsert($table_hiers,$row);
      }
         */

        /* UNBORK      
      // Fix the hierarchies for generated stuff
      SQL("UPDATE cms_hiers SET sequence=220 WHERE pagename='Global Concepts'"); 
      SQL("UPDATE cms_hiers SET sequence=250 WHERE pagename='Database Reference'"); 
      */
      
      x_EchoFlush("-----------------------------------------");
      x_EchoFlush(" ALL PROCESSING COMPLETE");
      x_EchoFlush("-----------------------------------------");
      echo ob_get_clean();
   }

   // ================================================================= \\
   // ================================================================= \\
   // PART 1 of 2
   // PROGRAM FILE DOCUMENTATION GENERATION
   //
   // All of the code below here processes the comments in code
   // to produce documentation.
   //
   // ================================================================= \\
   // ================================================================= \\
   function ehProcessCode($PageAPI,$PageApp) {
      // Grab the base directory, and the list of dirs
      $p=$GLOBALS['AG']['dirs']['root'];
      
      // This information is stored in the node manager, but we cannot
      // get to it, so it is duplicated here.
      $dirs=array(
         array('dirname'=>'root'        ,'flag_copy'=>'Y','flag_lib'=>'Y')
         ,array('dirname'=>'lib'        ,'flag_copy'=>'Y','flag_lib'=>'Y')
         ,array('dirname'=>'clib'       ,'flag_copy'=>'Y','flag_lib'=>'Y')
         ,array('dirname'=>'templates'  ,'flag_copy'=>'Y','flag_lib'=>'Y')
         ,array('dirname'=>'application','flag_copy'=>'Y','flag_lib'=>'N')
         ,array('dirname'=>'appclib'    ,'flag_copy'=>'Y','flag_lib'=>'N')
         ,array('dirname'=>'apppub'     ,'flag_copy'=>'Y','flag_lib'=>'N')
      );
      

      // Look only at "copy" directories, those that are published,
      // and list either under "Framework and API" or "Application"
      foreach($dirs as $dir) {
         if($dir['flag_copy']<>'Y') continue;
         
         $parent=$dir['flag_lib']=='Y' ? $PageAPI : $PageApp;
         x_EchoFlush("Processing main branch: ".$dir['dirname']);
         $this->ProcessCodeDir($p.'/'.$dir['dirname'],'',$parent);
      }
   }

   function processCodeDir($basedir,$branch,$parent) {
      $path=$basedir.$branch;
      x_EchoFlush("");
      x_EchoFlush(" :: DIRECTORY $path");
      $FILE=opendir($path);
      while( ($filename=readdir($FILE))!==false ) {
         if($filename=='.') continue;
         if($filename=='..') continue;
         x_EchoFlush("File: $filename");
         if(is_dir($path.$filename)) {
            $this->ProcessCodeDir($basedir,$branch.$filename."/",$parent);  
         }
         else {
            $this->ProcessCode_File($path,$filename,$parent);
         }
      }
   }
   
   function ProcessCode_File($path,$filename,$parent) {
      $table=DD_TableRef('docpages');
      
      // Step one, grab the file and parse it
      $file=file_get_contents($path.'/'.$filename);
      $matches=array();
      preg_match_all('/\n\/\*\*\n(.*)\n\*\//xmsU',$file,$matches);
      if(isset($matches[1])) {
         x_echoflush('count of returns: '.count($matches[1]));
         $defaults=array();
         foreach($matches[1] as $matchno=>$match) {
            $sequence = 0;
            $flag_function=false;
            //x_EchoFlush($matchno);
            $match=str_replace("\r",'',$match);  // simplify any cr/lf
            
            // separate parms from text
            $match.="\n\n ";  // makes next line fail-safe
            list($parmstext,$pagetext)=explode("\n\n",$match,2);
            
            // Split up and process parms.  If the name is "_default_",
            // then use these to overwrite defaults
            $testtypes=array();
            $tests = array();
            $udate = 'unknown';
            $varlines= explode("\n",$parmstext);
            $avars=$varlines[0]=='name:_default_' ? array() : $defaults;
            foreach($varlines as $varline) {
               $varpieces=explode(':',$varline);
               if(count($varpieces)==2) {
                  switch ($varpieces[0]) {
                     case 'testtypes':
                        $testtypes=explode(',',$varpieces[1]);
                        break;
                     case 'test':
                        $tests[]=$varpieces[1];
                        break;
                     case 'date':
                        $udate = $varpieces[1];
                        break;
                     case 'sequence':
                        $sequence=$varpieces[1];
                        break;
                     case 'flag':
                        $avars['flag'][$varpieces[1]]=1;
                        break;
                     case 'parm':
                        $avars['parm'][]=$varpieces[1];
                        break;
                     case 'function':
                        $flag_function=true;
                        $varpieces[0]='name';
                     default:
                        $avars[$varpieces[0]]=$varpieces[1];
                        
                  }
               }
            }
            if($varlines[0]=='name:_default_') {
               unset($avars['name']);
               $defaults=$avars;
               continue;
            }
            
            // happily skip something that doesn't have a name
            if(!isset($avars['name'])) continue;
            $pagename = $avars['name'];
            
            if(isset($avars['parm']) || isset($avars['returns'])) {
               $flag_function=true;
            }

            // make title depending on whether or not its a function
            $nametext='';
            if ($flag_function) {
               $nametext
                  ="\n<pre>"
                  .(isset($avars['returns']) ? $avars['returns'].' ' : '') 
                  ."<b>".$pagename."</b>(\n";
               $parmtext='';
               if(isset($avars['parm'])) {
                  foreach($avars['parm'] as $parm) {
                     $parmtext.="\n    ".$parm;
                  }
                  $parmtext.="\n";
               }
               $nametext.=$parmtext.")\n\n</pre>\n";
            }
            
            if (isset($avars['flag']['framework'])) {
               $pagetext = 
                  "''''This is a framework internal function.''''\n\n"
                  .$pagetext;
            }
            
            // if there is no parent page, assign the current default
            if(!isset($avars['parent'])) {
               $avars['parent']=$parent;  
            }
            
            // Add the date to name text
            $nametext.="\n\n''Date Modified'': ".$udate."\n\n";
            $nametext.="\n\n''File:''$filename\n\n";
            
            $hTests=$this->RunTests($pagename,$testtypes,$tests);
            
            
            // Create or update the text of the page
            $this->PageUpdate(
               $pagename
               ,$nametext.$pagetext.$hTests
               ,$avars['parent']
            );
         }
      }
      echo htmlx_errors();
   }
   
   function RunTests($pagename,$testtypes,$tests) {
      if(count($testtypes)==0) return '';
      if(count($tests)    ==0) return '';
      
      // If there are tests to run, do them now
      $hError='';
      $hTests='';
      foreach($tests as $test) {
         // Explode parameters and build a function call
         $parms=explode(',',$test);
         if(count($parms)<>count($testtypes)) {
            $hError="\n\n''There was a column count error in the
                    in-line test declarations for this function''\n";
         }
         else {
            // Turn some of the values into quotes values
            foreach($parms as $index=>$parm) {
               if($testtypes[$index]=='char') {
                  $parms[$index] = "'".$parm."'";
               }
            }
            // We actually add the evaluated output back onto the
            // parms array.  This lets us then call hTRFromArray,
            // that's the only reason we do it.
            $str='echo '.$pagename."(".implode(',',$parms).");";
            ob_start();
            eval($str);
            $result=ob_get_clean();
            // Eval again and show this time
            x_echoFlusH("Test command: $str");
            eval($str);
            x_echoFlush("");
            x_EchoFlush("Result was $result");
            $parms[]=$result;
            $hTests.="\n".hTrFromArray('',$parms);
         }
      }
      if($hTests) {
         foreach($testtypes as $index=>$testtype) {
            $htitles[]='Parm '.($index+1);
         }
         $htitles[]='Output';
         $hTests
            ="\n\n==Test output==\n\n"
            .$hError
            ."<table border=1>"
            .hTRFromArray('',$htitles)
            .$hTests
            ."\n</table>\n\n";
      }
      
      return $hTests; 
   }
      
   // ================================================================= \\
   // ================================================================= \\
   // PART 2 of 2
   // DATA DICTIONARY DOCUMENTATION GENERATION
   //
   // All of the code below here processes the data dictionary tables
   // and generates viewable pages out of them
   //
   // ================================================================= \\
   // ================================================================= \\
   function ehProcessData() {
      // Set the sequence for this main      
      //$this->PageUpdate('Data Dictionary','','Documentation',990);

      //$this->PageUpdate('Data Dictionary','','Documentation');
      x_EchoFlush("Processing Tables");
      $this->PageUpdate('Tables'    ,'','Data Dictionary');
      x_EchoFlush("Processing Modules");
      $this->PageUpdate('Modules'   ,'','Data Dictionary');
      x_EchoFlush("Processing Groups");
      $this->PageUpdate('Groups'    ,'','Data Dictionary');
      x_EchoFlush("Processing Column Definitions");
      $this->PageUpdate('Column Definitions'   ,'','Data Dictionary');
      x_EchoFlush("Processing Spec Files");      
      $this->PageUpdate('Spec Files','','Data Dictionary');

      
      // Build the top page, which is a bunch of links to other pages
      $this->ProcessData_Top();
      $this->ProcessData_Columns();
      $this->ProcessData_Modules();
      $this->ProcessData_Groups();
      $this->ProcessData_SpecFiles();
      $this->ProcessData_Tables();
   }
   
   function ProcessData_Top() {
      // generate the top-level page
      ob_start();
      ?>

A database is composed of one or more <a href="?gp_page=x_docview&gppn=Tables">Tables</a>
The tables are the heart of the database, it is the tables that
hold the actual data.

A database table contains columns.  Each column must be defined in
a <a href="?gp_page=x_docview&gppn=Column+Definitions">Column Definition</a> before it
can be placed into a table.

Tables with similar functions are grouped together into
<a href="?gp_page=x_docview&gppn=Modules">Modules</a>.  
Each table in the database is assigned to a particular module.
        A module contains tables that are similar in some respect, such
        as tables relating to sales, or tables relating to accounts
        receivable.  

No database is complete without security specifications.  Security is
based upon <a href="?gp_page=x_docview&gppn=Groups">Groups</a>.  Every member of a group
is given specific priveleges for modules as a whole and for individual
tables.

The information used to build the system came from the
<a href="?gp_page=x_docview&gppn=Spec+Files">Specification Files</a>.

      <?php
      $page_text=ob_get_clean();
      $this->PageUpdate('Data Dictionary',$page_text,'Documentation',990);
   }
   
   // ---------------------------------------------------------------
   // Columns
   // ---------------------------------------------------------------
   function ProcessData_Columns() {
      ob_start();
      ?>

<table class="adocs_table1" cellspacing=0 cellpadding=0>
<tr>
  <td class="adocs_th">Column Name
  <td class="adocs_th">Description
  <td class="adocs_th">Defined In
  <td class="adocs_th">Stats
  <td class="adocs_th">Automation
  <td class="adocs_th">Present In
</tr>
      <?php
      $sq="SELECT * from zdd.columns_c order by column_id";
      $cols=SQL_AllRows($sq);
      foreach($cols as $row) {
         $tabs=SQL_AllRows(
            "SELECT table_id,column_id FROM zdd.tabflat_c
              WHERE columN_id_src='{$row['column_id']}'"
         );
         $ahtabs=array();
         if($row['alltables']=='Y') {
            $ahtabs[]="All Tables";
         }
         else {
            foreach($tabs as $tab) {
               $gpp=urlencode('Table:'.$tab['table_id']);
               $ahtabs[]
                  =$this->pageLink('Table',$tab['table_id'])
                  ." as ".$tab['column_id'];
            }
         }
         
         $display=array(
            "<a name=\"".$row['column_id']."\">".$row['column_id']."</a>"
            ,$row['description']
            ,$this->pageLink('Spec file',$row['srcfile'])
            ,$this->MakeFormula($row)
            ,$this->MakeAutomation($row)
            ,implode('<br>',$ahtabs)
         );
         echo hTRFromArray('',$display);
      }
      echo "</table>";
      $page_text=ob_get_clean();
      $this->PageUpdate('Column Definitions',$page_text,'Data Dictionary');
   }
   
   // ---------------------------------------------------------------
   // MODULES
   // ---------------------------------------------------------------
   function ProcessData_Modules() {
      ob_start();
      echo "These are the modules in this system:<Br>";
      $this->ehTableHeader();
      $titles = array('Module','Source','Description');
      echo hTRFromArray('adocs_th',$titles);
      $mods=SQL_AllRows('SELECT * from zdd.modules_c order by module');
      foreach($mods as $mod) {
         $display=array(
            $this->pageLink('Module',$mod['module'])
            ,$this->pageLink('Spec file',$mod['srcfile'])
            ,$mod['description']
         );
         echo hTRFromArray('',$display);
         $this->ProcessData_OneModule($mod);
      }
      echo "</table>";
      $page_text=ob_get_clean();
      $this->PageUpdate('Modules',$page_text,'Data Dictionary');
   }
   
   function ProcessData_OneModule($row) {
      ob_start();

      echo "Module description: ".$row['description']."<br>";
      echo "Defined in ".$this->pageLink('Spec file',$row['srcfile']);
      echo "<br><br>";
      echo "Tables in this module:<br>";
      $tabs=SQL_AllRows(
         "SELECT table_id from zdd.tables_c
           WHERE module='".$row['module']."'"
      );
      $htabs=array();
      foreach($tabs as $tab) {
         $htabs[]=$this->PageLink('Table',$tab['table_id']);
      }
      echo implode(',&nbsp; ',$htabs);
      
      $bymods=SQL_AllRows(
         "SELECT * FROM zdd.permxmodules_c
            WHERE module = '".$row['module']."'
            ORDER BY group_id"
      );
      if(count($bymods)>0) {
         echo "<br><br>";
         echo "Default permission for this module"; 
         $this->ehtableheader();
         $headers=Array('Group','Select','Insert','Update','Delete');
         echo hTRFromArray('adocs_th',$headers);
         foreach($bymods as $bymod) {
            $display=array(
               $this->pageLink('Group',$bymod['group_id'])
               ,$this->PermResolve($bymod['permsel'])
               ,$this->PermResolve($bymod['permins'])
               ,$this->PermResolve($bymod['permupd'])
               ,$this->PermResolve($bymod['permdel'])
            );
            echo hTRFromArray('',$display);
         }
         echo "</table>";
      }
      $page_text=ob_get_clean();
      $this->PageUpdate('Module: '.$row['module'],$page_text,'Modules');
   }
   
   // ---------------------------------------------------------------
   // GROUPS
   // ---------------------------------------------------------------
   function ProcessData_Groups() {
      ob_start();
      ?>
Security begins with groups.

Each group is given default permissions application-wide, which are
normally very restrictive.  Permissions assigned to a group at the
module level extend to all of the tables in that module.  Permissions
can then be applied at the table level.

A user can be in any number of groups.  The permissions given to the user
"add up" from all of that user's groups.  If the user has permission to
read from table "X" because they are in group "A", then they will be able
to read from table "X" even if one or more of the other groups they 
belong to cannot read from that table. 
 

<table class="adocs_table1" cellspacing=0 cellpadding=0>
  <tr><td class="adocs_th">Group
      <td class="adocs_th">Defined In
      <td class="adocs_th">Description
      <td class="adocs_th">Select
      <td class="adocs_th">Insert
      <td class="adocs_th">Update
      <td class="adocs_th">Delete
  </tr>
      <?php
      $groups=SQL_AllRows("select * from zdd.groups_c order by group_id");
      foreach($groups as $row) {
         // Don't look at the "effective" groups
         if(!$this->ProcessGroup($row['group_id'])) continue; ;
         
         $display=array(
            $this->PageLink('Group',$row['group_id'])
            ,$this->PageLink('Spec file',$row['srcfile'])
            ,$row['description']
            ,$row['permsel']=='Y'?'YES':'-'
            ,$row['permins']=='Y'?'YES':'-'
            ,$row['permupd']=='Y'?'YES':'-'
            ,$row['permdel']=='Y'?'YES':'-'
         );
         echo hTRFromArray('',$display);
         $this->ProcessData_OneGroup($row);
      }
      echo "</table>";
      $page_text=ob_get_clean();
      $this->PageUpdate('Groups',$page_text,'Data Dictionary');
   }
   
   // - - - - - - - - - - - - - - - - - - - - - - - - - - 
   // One Group
   function ProcessData_OneGroup($row) {
      ob_start();
      echo "Defined in: ".$row['srcfile']."<br>";
      echo "Default SELECT permission:".($row['permsel']?'YES':'No')."<BR>";
      echo "Default INSERT permission:".($row['permins']?'YES':'No')."<BR>";
      echo "Default UPDATE permission:".($row['permupd']?'YES':'No')."<BR>";
      echo "Default DELETE permission:".($row['permdel']?'YES':'No')."<BR>";

      echo "<br><br>";
      echo "This group's permissions by module: ";
      $this->ehtableheader();
      $headers=Array('Module','Select','Insert','Update','Delete');
      echo hTRFromArray('adocs_th',$headers);
      $bymods=SQL_AllRows(
         "SELECT * FROM zdd.permxmodules_c
            WHERE group_id = '".$row['group_id']."'
            ORDER BY module"
      );
      foreach($bymods as $bymod) {
         $display=array(
            $this->pageLink('Module',$bymod['module'])
            ,$this->PermResolve($bymod['permsel'])
            ,$this->PermResolve($bymod['permins'])
            ,$this->PermResolve($bymod['permupd'])
            ,$this->PermResolve($bymod['permdel'])
         );
         echo hTRFromArray('',$display);
      }
      echo "</table>";

      echo "<br><br>";
      echo "This group's permissions by table: ";
      $this->ehtableheader();
      $headers=Array('Module','Select','Insert','Update','Delete');
      echo hTRFromArray('adocs_th',$headers);
      $bymods=SQL_AllRows(
         "SELECT * FROM zdd.perm_tabs_c
            WHERE group_id = '".$row['group_id']."'
            ORDER BY module"
      );
      foreach($bymods as $bymod) {
         $display=array(
            $this->pageLink('Table',$bymod['table_id'])
            ,$this->PermResolve($bymod['permsel'])
            ,$this->PermResolve($bymod['permins'])
            ,$this->PermResolve($bymod['permupd'])
            ,$this->PermResolve($bymod['permdel'])
         );
         echo hTRFromArray('',$display);
      }
      echo "</table>";
      
      $page_text=ob_get_clean();
      $this->PageUpdate('Group: '.$row['group_id']
         ,$page_text
         ,'Groups'
      );
   }

   // ---------------------------------------------------------------
   // Spec Files
   // ---------------------------------------------------------------
   function ProcessData_SpecFiles() {
      ob_start();
      echo "These are the spec files that were used to build this system.";
      
      $specs=SQL_AllRows(
         "SELECT distinct srcfile FROM zdd.tables_c
          UNION 
          SELECT distinct srcfile FROM zdd.columns_c
          UNION 
          SELECT distinct srcfile FROM zdd.modules_c
          UNION 
          SELECT distinct srcfile FROM zdd.groups_c"
      );
      foreach ($specs as $spec) {
         if($spec['srcfile']=='') continue; 
         echo "<br><br>Specification file: ".
            $this->pagelink('Spec file',$spec['srcfile']);
         $this->ProcessData_OneSPecFile($spec['srcfile']);
      }
      $page_text=ob_get_clean();
      $this->PageUpdate('Spec Files',$page_text,'Data Dictionary');
   }
   
   function ProcessData_OneSpecFile($specfile) {
      ob_start();
      $x=$specfile;
      
      $groups=SQL_AllRows(
         "SELECT * from zdd.groups_c WHERE srcfile = '$specfile'
          ORDER BY group_id"
      );
      echo "Groups defined in this file: <br>";
      $hGroups=array();
      foreach ($groups as $group) {
         $hGroups[]=$this->pageLink('Group',$group['group_id']);
      }
      echo implode(",&nbsp; ",$hGroups);
      echo "<br><br>";
      
      $groups=SQL_AllRows(
         "SELECT * from zdd.modules_c WHERE srcfile = '$specfile'
           ORDER BY module"
      );
      echo "Modules defined in this file: <br>";
      $hGroups=array();
      foreach ($groups as $group) {
         $hGroups[]=$this->pageLink('Module',$group['module']);
      }
      echo implode(",&nbsp; ",$hGroups);
      echo "<br><br>";

      $groups=SQL_AllRows(
         "SELECT * from zdd.tables_c WHERE srcfile = '$specfile'
           ORDER BY table_id"
      );
      echo "Tables defined in this file: <br>";
      $hGroups=array();
      foreach ($groups as $group) {
         $hGroups[]=$this->pageLink('Table',$group['table_id']);
      }
      echo implode(",&nbsp; ",$hGroups);
      echo "<br><br>";

      $groups=SQL_AllRows(
         "SELECT * from zdd.columns_c WHERE srcfile = '$specfile'
           ORDER BY column_id"
      );
      echo "Columns defined in this file: <br>";
      $hGroups=array();
      foreach ($groups as $group) {
         $href='?gp_page=x_docview&gppn=Column+Definitions'
            .'#'.$group['column_id'];
         $hGroups[]="<a href=\"$href\">".$group['column_id']."</a>";
      }
      echo implode(",&nbsp; ",$hGroups);
      echo "<br><br>";

      $page_text=ob_get_clean();
      $this->PageUpdate('Spec file: '.$specfile,$page_text,'Spec Files');
   }


   // ---------------------------------------------------------------
   // Spec Files
   // ---------------------------------------------------------------
   function ProcessData_Tables() {
      ob_start();
      ?>
<table class="adocs_table1" cellspacing=0 cellpadding=0>
  <tr><td class="adocs_th">Table
      <td class="adocs_th">Defined In
      <td class="adocs_th">Title
      <td class="adocs_th">Module
  </tr>
      <?php 
      $sql = 
         "SELECT table_id,srcfile,description,module 
           FROM zdd.tables_c 
          ORDER BY table_id ";
      $results = SQL($sql);
      //echo hErrors();
      while ($row = SQL_FETCH_ARRAY($results)) {
         //hprint_r($row);
         $display=array(
            $this->PageLink('Table',$row['table_id'])
            ,$this->PageLink('Spec file',$row['srcfile'])
            ,$row['description']
            ,$this->PageLink('Module',$row['module'])
         );
         echo hTRFromArray('',$display);
         $this->ProcessData_OneTable($row);
      }
      echo "</table>";
      $page_text=ob_get_clean();
      $this->PageUpdate('Tables',$page_text,'Data Dictionary');
   }

   function ProcessData_OneTable($table) {

      $tab = trim($table["table_id"]);
      ob_start();
      echo "Module: ".$this->PageLink('Module',$table['module']);
      echo "<br><br>";
      echo "Parent Tables:<br>";
      $pars=SQL_AllRows(
         "Select table_id_par from zdd.tabfky_c
           WHERE table_id = '$tab'"
      );
      $hpars = array();
      foreach($pars as $par) {
         $hpars[]=$this->pagelink('Table',$par['table_id_par']);
      }
      echo implode(',&nbsp; ',$hpars);

      echo "<br><br>";
      echo "Child Tables:<br>";
      $pars=SQL_AllRows(
         "Select table_id from zdd.tabfky_c
           WHERE table_id_par = '$tab'"
      );
      $hpars = array();
      foreach($pars as $par) {
         $hpars[]=$this->pagelink('Table',$par['table_id']);
      }
      echo implode(',&nbsp; ',$hpars);

      echo "<br><br>";
      echo "Column Definitions:<Br>";
      $this->ehTableHeader();
      $titles=array('Column','Caption','PK','Browse'
         ,'Stats','Automation','Parent'
      );
      echo hTRFromArray('adocs_th',$titles);
      $cols= SQL_AllRows(
         "select * from zdd.tabflat_c where table_id = '$tab'
          ORDER BY uicolseq"
          ,'column_id'
      );
      $hCalcCon=array();
      foreach($cols as $row) {
         $column_id=$row['column_id'];
         $display=array(
            $row['column_id']
            ,$row['description']
            ,$row['primary_key']
            ,$row['uisearch']
            ,$row['formula']
            ,$this->MakeAutomation($row)
            ,$this->pagelink('Table',$row['table_id_fko'])
         );
         echo hTRFromArray('',$display);
      }
      echo "</table>\n";

      
      // Output chain: Calculations and extensions
      $colsCons=SQL_AllRows("
Select c.* from zdd.colchains_c c
  JOIN zdd.column_seqs     seq
    ON c.table_id = seq.table_id
   AND c.column_id= seq.column_id
 WHERE c.table_id = '$tab' 
 ORDER BY seq.sequence,chain"
      );
      echo "<br>";
      echo "<div class=\"head1\">Column Calculations and Constraints</div>";
      if(count($colsCons)==0) {
         echo "There are no constraints or calculations for this table.";
      }
      foreach($colsCons as $colsCon) {
         $column_id=$colsCon['column_id'];
         echo "<br>";
         echo "<div class=head2>"
            ."Column: ".$cols[$column_id]['description']." ($column_id) "
            .($colsCon['chain']=='calc' ? 'Calculation' : 'Constraint')
            ."</div>";
         ?>
         <table width=100% class="adocs_table1">
           <tr>
             <td width=50% class="adocs_th">Test</td>
             <td width=50% class="adocs_th">Returns</td>
           </tr>
         <?php
         $tests=SQL_AllRows("
select arg.*,test.funcoper,test.compoper 
  from zdd.colchainargs_c  arg 
  JOIN zdd.colchaintests_c test 
    ON arg.uicolseq = test.uicolseq
 WHERE arg.table_id = '$tab'
   AND arg.column_id = ".SQLFC($column_id)."
 ORDER by uicolseq,argtype,sequence"      );
          $cat='';
          $cui=0;
          foreach($tests as $test) {
             $at=$test['argtype'];
             $ui=$test['uicolseq'];
             // Change from one row to the other requires closeup 
             if($cui<>$ui) {
                if($cui<>0) echo "</tr>"; // close prior row
                $cui=$ui;
                echo "<tr><td>";          // open new row and cell
                if($at==0) {
                   echo $test['compoper']."&nbsp;";
                }
                $cat=0;
             }
             
             // when changing from comparison to 
             if($at==1 && $cat==0) {
                echo "<td>".$test['funcoper']."&nbsp;";
                $cat=$at;
             }
             
             if($test['column_id_arg']<>'') {
                echo "@".$test['column_id_arg']."&nbsp;";
             }
             else {
                echo $test['literal_arg']."&nbsp;";
             }
          }
          echo "</tr>";
          echo "</table>";
      }
      
      echo "<br><br>";
      echo "This tables's permissions by group: ";
      $this->ehtableheader();
      $headers=Array('Group','Select','Insert','Update','Delete');
      echo hTRFromArray('adocs_th',$headers);
      $bymods=SQL_AllRows(
         "SELECT * FROM zdd.perm_tabs_c
            WHERE table_id = '$tab'
            ORDER BY group_id"
      );
      foreach($bymods as $bymod) {
         if(!$this->ProcessGroup($bymod['group_id'])) continue; ;
         $display=array(
            $this->pageLink('Group',$bymod['group_id'])
            ,$this->PermResolve($bymod['permsel'])
            ,$this->PermResolve($bymod['permins'])
            ,$this->PermResolve($bymod['permupd'])
            ,$this->PermResolve($bymod['permdel'])
         );
         echo hTRFromArray('',$display);
      }
      echo "</table>";
      
         
      $pagetext=ob_get_Clean();
      $this->PageUpdate('Table: '.$table['table_id'],$pagetext,'Tables');
   }

   
   // ================================================================= \\
   // ================================================================= \\
   // HELPER FUNCTIONS
   // ================================================================= \\
   // ================================================================= \\
   function PageUpdate($pagename,$pagetext,$pagename_par=null,$seq=null) {
      $table_pg=DD_TableRef('docpages');
      $table_hi=DD_TableRef('dochiers');
      $row=array(
         'pagename'=>$pagename
         ,'pagename_par'=>$pagename_par
         ,'pagetext'=>$pagetext
         ,'flag_auto'=>'Y'
      );
      SQLX_UpdateOrInsert($table_pg,$row);
      
      /*
      if (!is_null($pagename_par)) {
         // Get sequence if necessary.  
         if(is_null($seq)) {
            if(!isset($this->parseqs[$pagename_par])) {
               $this->parseqs[$pagename_par]=0;
            }
            $this->parseqs[$pagename_par]+=10;
            $seq=$this->parseqs[$pagename_par];
         }
         
         // Store the hierachy link for saving later
         $this->parents[$pagename]=array($pagename_par,$seq);
      }
      */
   }
   
   function linkTableFromRow($row) {
      $xtab = $this->TableIDFromRow($row);
      $htab = urlencode($xtab);
      return "<a href=\"?gp_page=x_docview&gppn=$htab\">$xtab</a>";
   }
   
   function TableIDFromRow($row) {
      return trim($row['description']).' ('.trim($row['table_id']).')';
   }
   
   
   function MakeFormula($row) {
      switch($row['type_id']) {
         case 'numb':
            $retval='numeric('.$row['colprec'].','.$row['colscale'].')';
            break;
         case 'char':
            $retval='char('.$row['colprec'].')';
            break;
         case 'vchar':
            $retval='varchar('.$row['colprec'].')';
            break;
         default:
            $retval=$row['type_id'];
      }
      return $retval;
   }
   
   function MakeAutomation($row) {
      if($row['automation_id']=='NONE') return '';
      
      // special case, extended columns
      //if($row['automation_id']=='EXTEND') {
      //   return '<a href="#extend_'.$row['column_id'].'">CALCULATE</a>';
      //}
      
      if($row['auto_formula']=='') return $row['automation_id'];
      else return $row['automation_id'].':'.$row['auto_formula'];
      
   }
   
   function PageLink($prefix,$item) {
      $href="?gp_page=x_docview&gppn=".urlencode($prefix.": ".$item);
      return "<a href=\"$href\">$item</a>";
   }
   
   function ehTableHeader() {
      echo "<table cellpadding=0 cellspacing=0 class=\"adocs_table1\">";
   }
   
   function PermResolve($perm) {
      return $perm=='Y' ? 'YES' : '-';
   }
   
   function ProcessGroup($group_id) {
      $app=$GLOBALS['AG']['application'];
      $appe =$GLOBALS['AG']['application']."_eff_";
      $appex=strlen($appe);
      return substr($group_id,0,$appex)==$appe ? false : true;
   }
}
?>
