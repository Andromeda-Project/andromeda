<?php
/*
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
class XDocgen extends XTable2
{
    public function __construct()
    {
        parent::__construct();
        if (gp('gp_posted')<>'') {
            $this->flag_buffer=false;
        }
    }
   
    public function main()
    {
        $this->pageSubtitle = "Documentation Generation";
        if (gp('gp_posted')=='1') {
            return $this->ehProcess();
        }
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

    public function ehProcess()
    {
        ob_start();
        // Delete all current pages, then create the master category
        x_EchoFlush("BEGIN PROCESSING");
        x_EchoFlush("-----------------------------------------");
        x_EchoFlush("Purging generated pages...");
        SQL("Delete from docpages where pagename='Documentation'");
        SQL("Delete from docpages where pagename='Data Dictionary'");
        $m1="Framework API Reference";
        $m2="Application Files";
        $m3="Data Dictionary";

        // This will store the hierarchy
        $this->parents=array();
        $this->parseqs=array();
      
        $this->pageUpdate('Data Dictionary', '');
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
    public function ehProcessCode($PageAPI, $PageApp)
    {
        global $AG;
        // Grab the base directory, and the list of dirs
        $p=$AG['dirs']['root'];
      
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
        foreach ($dirs as $dir) {
            if ($dir['flag_copy']<>'Y') {
                continue;
            }
         
            $parent=$dir['flag_lib']=='Y' ? $PageAPI : $PageApp;
            x_EchoFlush("Processing main branch: ".$dir['dirname']);
            $this->processCodeDir($p.'/'.$dir['dirname'], '', $parent);
        }
    }

    public function processCodeDir($basedir, $branch, $parent)
    {
        $path=$basedir.$branch;
        x_EchoFlush("");
        x_EchoFlush(" :: DIRECTORY $path");
        $FILE=opendir($path);
        while (($filename=readdir($FILE))!==false) {
            if ($filename=='.') {
                continue;
            }
            if ($filename=='..') {
                continue;
            }
            x_EchoFlush("File: $filename");
            if (is_dir($path.$filename)) {
                $this->processCodeDir($basedir, $branch.$filename."/", $parent);
            } else {
                $this->processCodeFile($path, $filename, $parent);
            }
        }
    }
   
    public function processCodeFile($path, $filename, $parent)
    {
        $table=DD_TableRef('docpages');
      
        // Step one, grab the file and parse it
        $file=file_get_contents($path.'/'.$filename);
        $matches=array();
        preg_match_all('/\n\/\*\*\n(.*)\n\*\//xmsU', $file, $matches);
        if (isset($matches[1])) {
            x_echoflush('count of returns: '.count($matches[1]));
            $defaults=array();
            foreach ($matches[1] as $matchno => $match) {
                $sequence = 0;
                $flag_function=false;
                //x_EchoFlush($matchno);
                $match=str_replace("\r", '', $match);  // simplify any cr/lf

                // separate parms from text
                $match.="\n\n ";  // makes next line fail-safe
                list($parmstext, $pagetext)=explode("\n\n", $match, 2);
            
                // Split up and process parms.  If the name is "_default_",
                // then use these to overwrite defaults
                $testtypes=array();
                $tests = array();
                $udate = 'unknown';
                $varlines= explode("\n", $parmstext);
                $avars=$varlines[0]=='name:_default_' ? array() : $defaults;
                foreach ($varlines as $varline) {
                    $varpieces=explode(':', $varline);
                    if (count($varpieces)==2) {
                        switch ($varpieces[0]) {
                            case 'testtypes':
                                $testtypes=explode(',', $varpieces[1]);
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
                                // keep going
                            default:
                                $avars[$varpieces[0]]=$varpieces[1];
                        
                        }
                    }
                }
                if ($varlines[0]=='name:_default_') {
                    unset($avars['name']);
                    $defaults=$avars;
                    continue;
                }
            
                // happily skip something that doesn't have a name
                if (!isset($avars['name'])) {
                    continue;
                }
                $pagename = $avars['name'];
            
                if (isset($avars['parm']) || isset($avars['returns'])) {
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
                    if (isset($avars['parm'])) {
                        foreach ($avars['parm'] as $parm) {
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
                if (!isset($avars['parent'])) {
                    $avars['parent']=$parent;
                }
            
                // Add the date to name text
                $nametext.="\n\n''Date Modified'': ".$udate."\n\n";
                $nametext.="\n\n''File:''$filename\n\n";
            
                $hTests=$this->runTests($pagename, $testtypes, $tests);
            
            
                // Create or update the text of the page
                $this->pageUpdate(
                    $pagename,
                    $nametext.$pagetext.$hTests,
                    $avars['parent']
                );
            }
        }
        echo htmlx_errors();
    }
   
    public function runTests($pagename, $testtypes, $tests)
    {
        if (count($testtypes)==0) {
            return '';
        }
        if (count($tests)    ==0) {
            return '';
        }
      
        // If there are tests to run, do them now
        $hError='';
        $hTests='';
        foreach ($tests as $test) {
            // Explode parameters and build a function call
            $parms=explode(',', $test);
            if (count($parms)<>count($testtypes)) {
                $hError="\n\n''There was a column count error in the
                    in-line test declarations for this function''\n";
            } else {
                // Turn some of the values into quotes values
                foreach ($parms as $index => $parm) {
                    if ($testtypes[$index]=='char') {
                        $parms[$index] = "'".$parm."'";
                    }
                }
                // We actually add the evaluated output back onto the
                // parms array.  This lets us then call hTRFromArray,
                // that's the only reason we do it.
                $str='echo '.$pagename."(".implode(',', $parms).");";
                ob_start();
                eval($str);
                $result=ob_get_clean();
                // Eval again and show this time
                x_echoFlusH("Test command: $str");
                eval($str);
                x_echoFlush("");
                x_EchoFlush("Result was $result");
                $parms[]=$result;
                $hTests.="\n".hTrFromArray('', $parms);
            }
        }
        if ($hTests) {
            foreach ($testtypes as $index => $testtype) {
                $htitles[]='Parm '.($index+1);
            }
            $htitles[]='Output';
            $hTests
            ="\n\n==Test output==\n\n"
            .$hError
            ."<table border=1>"
            .hTRFromArray('', $htitles)
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
    public function ehProcessData()
    {
        x_EchoFlush("Processing Tables");
        $this->pageUpdate('Tables', '', 'Data Dictionary');
        x_EchoFlush("Processing Modules");
        $this->pageUpdate('Modules', '', 'Data Dictionary');
        x_EchoFlush("Processing Groups");
        $this->pageUpdate('Groups', '', 'Data Dictionary');
        x_EchoFlush("Processing Column Definitions");
        $this->pageUpdate('Column Definitions', '', 'Data Dictionary');
        x_EchoFlush("Processing Spec Files");
        $this->pageUpdate('Spec Files', '', 'Data Dictionary');

      
        // Build the top page, which is a bunch of links to other pages
        $this->processDataTop();
        $this->processDataColumns();
        $this->processDataModules();
        $this->processDataGroups();
        $this->processDataSpecFiles();
        $this->processDataTables();
        echo hErrors();
    }
   
    public function processDataTop()
    {
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
        $this->pageUpdate('Data Dictionary', $page_text, 'Documentation', 990);
    }
   
    // ---------------------------------------------------------------
    // Columns
    // ---------------------------------------------------------------
    public function processDataColumns()
    {
        ob_start();
        ?>

 <table class="table table-condensed table-striped table-hover table-bordered">
 <thead>
 <tr>
   <th>Column Name
   <th>Description
   <th>Defined In
   <th>Stats
   <th>Automation
   <th>Present In
 </tr>
 </thead>
        <?php
        $sq="SELECT * from zdd.columns order by column_id";
        $cols=SQL_AllRows($sq);
        foreach ($cols as $row) {
            $tabs=SQL_AllRows(
                "SELECT table_id,column_id FROM zdd.tabflat
              WHERE columN_id_src='{$row['column_id']}'"
            );
            $ahtabs=array();
            if ($row['alltables']=='Y') {
                $ahtabs[]="All Tables";
            } else {
                foreach ($tabs as $tab) {
                    $gpp=urlencode('Table:'.$tab['table_id']);
                    $ahtabs[]
                    =$this->pageLink('Table', $tab['table_id'])
                    ." as ".$tab['column_id'];
                }
            }
         
            $display=array(
            "<a name=\"".$row['column_id']."\">".$row['column_id']."</a>"
            ,$row['description']
            ,$this->pageLink('Spec file', $row['srcfile'])
            ,$this->makeFormula($row)
            ,$this->makeAutomation($row)
            ,implode('<br>', $ahtabs)
            );
            echo hTRFromArray('', $display);
        }
        echo "</table>";
        $page_text=ob_get_clean();
        $this->pageUpdate('Column Definitions', $page_text, 'Data Dictionary');
    }
   
    // ---------------------------------------------------------------
    // MODULES
    // ---------------------------------------------------------------
    public function processDataModules()
    {
        ob_start();
        echo "These are the modules in this system:<Br>";
        $this->ehTableHeader();
        $titles = array('Module','Source','Description');
        echo hTRFromArray('adocs_th', $titles);
        $mods=SQL_AllRows('SELECT * from zdd.modules order by module');
        foreach ($mods as $mod) {
            $display=array(
            $this->pageLink('Module', $mod['module'])
            ,$this->pageLink('Spec file', $mod['srcfile'])
            ,$mod['description']
            );
            echo hTRFromArray('', $display);
            $this->processDataOneModule($mod);
        }
        echo "</table>";
        $page_text=ob_get_clean();
        $this->pageUpdate('Modules', $page_text, 'Data Dictionary');
    }
   
    public function processDataOneModule($row)
    {
        ob_start();

        echo "Module description: ".$row['description']."<br>";
        echo "Defined in ".$this->pageLink('Spec file', $row['srcfile']);
        echo "<br><br>";
        echo "Tables in this module:<br>";
        $tabs=SQL_AllRows(
            "SELECT table_id from zdd.tables
           WHERE module='".$row['module']."'"
        );
        $htabs=array();
        foreach ($tabs as $tab) {
            $htabs[]=$this->pageLink('Table', $tab['table_id']);
        }
        echo implode(',&nbsp; ', $htabs);
      
        $bymods=SQL_AllRows(
            "SELECT * FROM zdd.permxmodules
            WHERE module = '".$row['module']."'
            ORDER BY group_id"
        );
        if (count($bymods)>0) {
            echo "<br><br>";
            echo "Default permission for this module";
            $this->ehtableheader();
            $headers=array('Group','Select','Insert','Update','Delete');
            echo hTRFromArray('adocs_th', $headers);
            foreach ($bymods as $bymod) {
                $display=array(
                $this->pageLink('Group', $bymod['group_id'])
                ,$this->permResolve($bymod['permsel'])
                ,$this->permResolve($bymod['permins'])
                ,$this->permResolve($bymod['permupd'])
                ,$this->permResolve($bymod['permdel'])
                );
                echo hTRFromArray('', $display);
            }
            echo "</table>";
        }
        $page_text=ob_get_clean();
        $this->pageUpdate('Module: '.$row['module'], $page_text, 'Modules');
    }
   
    // ---------------------------------------------------------------
    // GROUPS
    // ---------------------------------------------------------------
    public function processDataGroups()
    {
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
 

 <table class="table table-striped table-bordered table-condensed table-hover">
  <thead>
    <tr>
     <th>Group</th>
     <th>Defined In</th>
     <th>Description</th>
     <th>Select</th>
     <th>Insert</th>
     <th>Update</th>
     <th>Delete</th>
    </tr>
   </thead>
   <tbody>
        <?php
        $groups=SQL_AllRows("select * from zdd.groups order by group_id");
        foreach ($groups as $row) {
            // Don't look at the "effective" groups
            if (!$this->processGroup($row['group_id'])) {
                continue;
            } ;
         
            $display=array(
            $this->pageLink('Group', $row['group_id'])
            ,$this->pageLink('Spec file', $row['srcfile'])
            ,$row['description']
            ,$row['permsel']=='Y'?'YES':'-'
            ,$row['permins']=='Y'?'YES':'-'
            ,$row['permupd']=='Y'?'YES':'-'
            ,$row['permdel']=='Y'?'YES':'-'
            );
            echo hTRFromArray('', $display);
            $this->processDataOneGroup($row);
        }
        echo "</tbody>";
        ?>
      </table>
        <?php
        $page_text=ob_get_clean();
        $this->pageUpdate('Groups', $page_text, 'Data Dictionary');
    }
   
    // - - - - - - - - - - - - - - - - - - - - - - - - - -
    // One Group
    public function processDataOneGroup($row)
    {
        ob_start();
        echo "Defined in: ".$row['srcfile']."<br>";
        echo "Default SELECT permission:".($row['permsel']?'YES':'No')."<BR>";
        echo "Default INSERT permission:".($row['permins']?'YES':'No')."<BR>";
        echo "Default UPDATE permission:".($row['permupd']?'YES':'No')."<BR>";
        echo "Default DELETE permission:".($row['permdel']?'YES':'No')."<BR>";

        echo "<br><br>";
        echo "This group's permissions by module: ";
        $this->ehtableheader();
        $headers=array('Module','Select','Insert','Update','Delete');
        echo hTRFromArray('adocs_th', $headers);
        $bymods=SQL_AllRows(
            "SELECT * FROM zdd.permxmodules
            WHERE group_id = '".$row['group_id']."'
            ORDER BY module"
        );
        foreach ($bymods as $bymod) {
            $display=array(
            $this->pageLink('Module', $bymod['module'])
            ,$this->permResolve($bymod['permsel'])
            ,$this->permResolve($bymod['permins'])
            ,$this->permResolve($bymod['permupd'])
            ,$this->permResolve($bymod['permdel'])
            );
            echo hTRFromArray('', $display);
        }
        echo "</table>";

        echo "<br><br>";
        echo "This group's permissions by table: ";
        $this->ehtableheader();
        $headers=array('Module','Select','Insert','Update','Delete');
        echo hTRFromArray('adocs_th', $headers);
        $bymods=SQL_AllRows(
            "SELECT * FROM zdd.perm_tabs
            WHERE group_id = '".$row['group_id']."'
            ORDER BY module"
        );
        foreach ($bymods as $bymod) {
            $display=array(
            $this->pageLink('Table', $bymod['table_id'])
            ,$this->permResolve($bymod['permsel'])
            ,$this->permResolve($bymod['permins'])
            ,$this->permResolve($bymod['permupd'])
            ,$this->permResolve($bymod['permdel'])
            );
            echo hTRFromArray('', $display);
        }
        echo "</table>";
      
        $page_text=ob_get_clean();
        $this->pageUpdate(
            'Group: '.$row['group_id'],
            $page_text,
            'Groups'
        );
    }

    // ---------------------------------------------------------------
    // Spec Files
    // ---------------------------------------------------------------
    public function processDataSpecFiles()
    {
        ob_start();
        echo "These are the spec files that were used to build this system.";
      
        $specs=SQL_AllRows(
            "SELECT distinct srcfile FROM zdd.tables
          UNION 
          SELECT distinct srcfile FROM zdd.columns
          UNION 
          SELECT distinct srcfile FROM zdd.modules
          UNION 
          SELECT distinct srcfile FROM zdd.groups"
        );
        foreach ($specs as $spec) {
            if ($spec['srcfile']=='') {
                continue;
            }
            echo "<br><br>Specification file: ".
            $this->pagelink('Spec file', $spec['srcfile']);
            $this->processDataOneSpecFile($spec['srcfile']);
        }
        $page_text=ob_get_clean();
        $this->pageUpdate('Spec Files', $page_text, 'Data Dictionary');
    }
   
    public function processDataOneSpecFile($specfile)
    {
        ob_start();
        $x=$specfile;
      
        $groups=SQL_AllRows(
            "SELECT * from zdd.groups WHERE srcfile = '$specfile'
          ORDER BY group_id"
        );
        echo "Groups defined in this file: <br>";
        $hGroups=array();
        foreach ($groups as $group) {
            $hGroups[]=$this->pageLink('Group', $group['group_id']);
        }
        echo implode(",&nbsp; ", $hGroups);
        echo "<br><br>";
      
        $groups=SQL_AllRows(
            "SELECT * from zdd.modules WHERE srcfile = '$specfile'
           ORDER BY module"
        );
        echo "Modules defined in this file: <br>";
        $hGroups=array();
        foreach ($groups as $group) {
            $hGroups[]=$this->pageLink('Module', $group['module']);
        }
        echo implode(",&nbsp; ", $hGroups);
        echo "<br><br>";

        $groups=SQL_AllRows(
            "SELECT * from zdd.tables WHERE srcfile = '$specfile'
           ORDER BY table_id"
        );
        echo "Tables defined in this file: <br>";
        $hGroups=array();
        foreach ($groups as $group) {
            $hGroups[]=$this->pageLink('Table', $group['table_id']);
        }
        echo implode(",&nbsp; ", $hGroups);
        echo "<br><br>";

        $groups=SQL_AllRows(
            "SELECT * from zdd.columns WHERE srcfile = '$specfile'
           ORDER BY column_id"
        );
        echo "Columns defined in this file: <br>";
        $hGroups=array();
        foreach ($groups as $group) {
            $href='?gp_page=x_docview&gppn=Column+Definitions'
            .'#'.$group['column_id'];
            $hGroups[]="<a href=\"$href\">".$group['column_id']."</a>";
        }
        echo implode(",&nbsp; ", $hGroups);
        echo "<br><br>";

        $page_text=ob_get_clean();
        $this->pageUpdate('Spec file: '.$specfile, $page_text, 'Spec Files');
    }


    // ---------------------------------------------------------------
    // Spec Files
    // ---------------------------------------------------------------
    public function processDataTables()
    {
        ob_start();
        ?>
 <table class="table table-striped table-bordered table-condensed table-hover">
   <thead>
   <tr class="dark">
       <th>Module</th>
       <th>Table</th>
       <th>Defined In</th>
       <th>Title</th>
   </tr>
   </thead>
   <tbody>
        <?php
        $sql =
         "SELECT t.table_id,t.srcfile,t.description,t.module 
           FROM zdd.tables t
           JOIN zdd.modules m
             ON t.module = m.module
          WHERE t.module <> 'datadict'
          ORDER BY m.uisort,t.uisort";
        $results = SQL($sql);
        //echo hErrors();
        while ($row = SQL_FETCH_ARRAY($results)) {
            //hprint_r($row);
            $display=array(
            $this->pageLink('Module', $row['module'])
            ,$this->pageLink('Table', $row['table_id'])
            ,$this->pageLink('Spec file', $row['srcfile'])
            ,$row['description']
            );
            echo hTRFromArray('', $display);
            $this->processDataOneTable($row);
        }
        echo '</tbody>';
        echo "</table>";
        $page_text=ob_get_clean();
        $this->pageUpdate('Tables', $page_text, 'Data Dictionary');
    }

    public function processDataOneTable($table)
    {
        $tab = trim($table["table_id"]);
        ob_start();
        ?>
        <table class="table table-striped table-bordered table-condensed table-hover">
          <thead>
          <tr>
              <th>Module</th>
              <th>Parent Tables</th>
              <th>Child Tables</th>
          </tr>
          </thead>
          <tr>
        <?php
        echo "<td>".$this->pageLink('Module', $table['module']);
        $pars=SQL_AllRows(
            "Select table_id_par from zdd.tabfky
           WHERE table_id = '$tab'"
        );
        $hpars = array();
        echo "<td>";
        foreach ($pars as $par) {
            $hpars[]=$this->pagelink('Table', $par['table_id_par']);
        }
        echo implode(',&nbsp; ', $hpars);

        echo "<td>";
        $pars=SQL_AllRows(
            "Select table_id from zdd.tabfky
           WHERE table_id_par = '$tab'"
        );
        $hpars = array();
        foreach ($pars as $par) {
            $hpars[]=$this->pagelink('Table', $par['table_id']);
        }
        echo implode(',&nbsp; ', $hpars);
        echo "</table>";

        echo "<br><br>";
        echo "<h3>Column Definitions:</h3><br/>";
        $this->ehTableHeader();
        $titles=array('Column','Caption','PK','Browse'
         ,'Stats','Automation','Parent'
        );
        echo hTRFromArray('adocs_th dark', $titles);
        $cols= SQL_AllRows(
            "select * from zdd.tabflat where table_id = '$tab'
          ORDER BY uicolseq",
            'column_id'
        );
        $hCalcCon=array();
        foreach ($cols as $row) {
            $column_id=$row['column_id'];
            $display=array(
            $row['column_id']
            ,$row['description']
            ,$row['primary_key']
            ,$row['uisearch']
            ,$row['formula']
            ,$this->makeAutomation($row)
            ,$this->pagelink('Table', $row['table_id_fko'])
            );
            echo hTRFromArray('', $display);
        }
        echo "</table>\n";

      
        // Output chain: Calculations and extensions
        $colsCons=SQL_AllRows(
            "
Select c.* from zdd.colchains c
  JOIN zdd.column_seqs     seq
    ON c.table_id = seq.table_id
   AND c.column_id= seq.column_id
 WHERE c.table_id = '$tab' 
 ORDER BY seq.sequence,chain"
        );
        echo "<br>";
        echo "<div class=\"head1\">Column Calculations and Constraints</div>";
        if (count($colsCons)==0) {
            echo "There are no constraints or calculations for this table.";
        }
        foreach ($colsCons as $colsCon) {
            $column_id=$colsCon['column_id'];
            echo "<br>";
            echo "<div class=head2>"
            ."Column: ".$cols[$column_id]['description']." ($column_id) "
            .($colsCon['chain']=='calc' ? 'Calculation' : 'Constraint')
            ."</div>";
            ?>
    <table class="table table-striped table-bordered table-condensed table-hover">
     <thead>
      <tr>
       <th width=50% class="adocs_th">Test</th>
       <th width=50% class="adocs_th">Returns</th>
      </tr>
     </thead>
     <tbody>
      <tr>
            <?php
            $tests=SQL_AllRows(
                "
select arg.*,test.funcoper,test.compoper 
  from zdd.colchainargs  arg 
  JOIN zdd.colchaintests test 
    ON arg.uicolseq = test.uicolseq
 WHERE arg.table_id = '$tab'
   AND arg.column_id = ".SQLFC($column_id)."
 ORDER by uicolseq,argtype,sequence"
            );
            $cat='';
            $cui=0;
            foreach ($tests as $test) {
                $at=$test['argtype'];
                $ui=$test['uicolseq'];
                // Change from one row to the other requires closeup
                if ($cui<>$ui) {
                    if ($cui<>0) {
                        echo "</tr>"; // close prior row
                    }
                    $cui=$ui;
                    echo "<tr><td>";          // open new row and cell
                    if ($at==0) {
                        echo $test['compoper']."&nbsp;";
                    }
                    $cat=0;
                }
             
                // when changing from comparison to
                if ($at==1 && $cat==0) {
                    echo "<td>".$test['funcoper']."&nbsp;";
                    $cat=$at;
                }
             
                if ($test['column_id_arg']<>'') {
                    echo "@".$test['column_id_arg']."&nbsp;";
                } else {
                    echo $test['literal_arg']."&nbsp;";
                }
            }
            echo "</tr>";
            echo "</tbody>";
            echo "</table>";
        }
      
        echo "<br><br>";
        echo "This tables's permissions by group: ";
        $this->ehtableheader();
        $headers=array('Group','Select','Insert','Update','Delete');
        echo hTRFromArray('adocs_th', $headers);
        $bymods=SQL_AllRows(
            "SELECT * FROM zdd.perm_tabs
            WHERE table_id = '$tab'
            ORDER BY group_id"
        );
        foreach ($bymods as $bymod) {
            if (!$this->processGroup($bymod['group_id'])) {
                continue;
            } ;
            $display=array(
            $this->pageLink('Group', $bymod['group_id'])
            ,$this->permResolve($bymod['permsel'])
            ,$this->permResolve($bymod['permins'])
            ,$this->permResolve($bymod['permupd'])
            ,$this->permResolve($bymod['permdel'])
            );
            echo hTRFromArray('', $display);
        }
        echo "</table>";
      
         
        $pagetext=ob_get_Clean();
        $this->pageUpdate('Table: '.$table['table_id'], $pagetext, 'Tables');
    }

   
    // ================================================================= \\
    // ================================================================= \\
    // HELPER FUNCTIONS
    // ================================================================= \\
    // ================================================================= \\
    public function pageUpdate($pagename, $pagetext, $pagename_par = null, $seq = null)
    {
        $table_pg=DD_TableRef('docpages');
        $table_hi=DD_TableRef('dochiers');
        $row=array(
         'pagename'=>$pagename
         ,'pagename_par'=>$pagename_par
         ,'pagetext'=>$pagetext
         ,'flag_auto'=>'Y'
        );
        SQLX_UpdateOrInsert($table_pg, $row);
      
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
   
    public function linkTableFromRow($row)
    {
        $xtab = $this->tableIdFromRow($row);
        $htab = urlencode($xtab);
        return "<a href=\"?gp_page=x_docview&gppn=$htab\">$xtab</a>";
    }
   
    public function tableIdFromRow($row)
    {
        return trim($row['description']).' ('.trim($row['table_id']).')';
    }
   
   
    public function makeFormula($row)
    {
        switch ($row['type_id']) {
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
   
    public function makeAutomation($row)
    {
        if ($row['automation_id']=='NONE') {
            return '';
        }
      
        // special case, extended columns
        //if($row['automation_id']=='EXTEND') {
        //   return '<a href="#extend_'.$row['column_id'].'">CALCULATE</a>';
        //}

        if ($row['auto_formula']=='') {
            return $row['automation_id'];
        } else {
            return $row['automation_id'].':'.$row['auto_formula'];
        }
    }
   
    public function pageLink($prefix, $item)
    {
        $href="?gp_page=x_docview&gppn=".urlencode($prefix.": ".$item);
        return "<a href=\"$href\">$item</a>";
    }
   
    public function ehTableHeader()
    {
        echo "<table class=\"table table-striped table-condensed table-hover table-bordered\">";
    }
   
    public function permResolve($perm)
    {
        return $perm=='Y' ? 'YES' : '-';
    }
   
    public function processGroup($group_id)
    {
        global $AG;
        $app=$AG['application'];
        $appe =$AG['application']."_eff_";
        $appex=strlen($appe);
        return substr($group_id, 0, $appex)==$appe ? false : true;
    }
}
?>
