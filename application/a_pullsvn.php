<?php
class a_pullsvn extends x_table2 {
    /**
      *  Standard Andromeda main entry point for an
      *  extension of the x_table2 class.
      * 
      */
    function main() {
        # KFD 4/15/08, overhaul to go app-by-app on downloads
        #if(gpExists('gp_set')) $this->mainDevSet();
        
        # KFD 4/15/08, overhaul to go app-by-app on downloads
        #$dev_station = OptionGet('DEV_STATION');
        #if($dev_station=='') return $this->mainPick();
        #if($dev_station=='Y') return $this->mainNotAllowed();
        
        if(gpExists('svnpull')) return $this->mainPull();
        else $this->mainHTML();
    }
    

    /**
      *  If the user has clicked on a link setting this 
      *  as either a server or workstation, then make
      *  the setting change now.
      * 
      */
    /* KFD 4/15/08
    function mainDevSet() {
        $row = array(
            'variable'=>'DEV_STATION'
            ,'description'=>'Is Development Workstation'
            ,'variable_value'=>(gp('gp_set')=='dev' ? 'Y' : 'N')
        );
        SQLX_insert('variables',$row);
        
        // Asking for option 'x' is a magic number that causes
        // a force cache reload.
        OptionGet('X');
    }
    */
    
    /**
      *  If the user has never set the DEV_STATION flag
      *  then we will not let them do any kind of SVN pulls
      *  until they tell us if this is a dev station
      *  or a server.
      * 
      */
    /* KFD 4/15/08
    function mainPick() {
        ?>
        <h1>Updates From SVN</h1>
        
        <div style="font-size: 110%">
        <h3>Please Make DEV_STATION Setting</h3>
        
        This machine can either be a development workstation or
        a server.  Please click one of the links below to make
        the setting.
        <br/>
        <br/>
        
        <ul><li>
        <a href="?gp_page=a_pullsvn&gp_set=dev">Click Here To Make
        This Machine a Development Workstation</a>.  A Development workstation
        is a machine that a programmer uses to write code.  The programmer
        uses Subversion to retrieve code updates and commit updates
        to the Subversion server.
        <br/><br/>
        
        
        <li><a href="?gp_page=a_pullsvn&gp_set=serve">Click Here To Make
        This Machine a Server</a>.  Programmers do not directly modify
        the code on a server.  Servers always get their code from Subversion
        repositories using the "Update From Subversion" feature.
        </ul>
        
        </div>
        <?php
    }
    */
        
    /**
      *  If this is a dev workstation the user may not do
      *  software downloads.  
      * 
      */
    /* KFD 4/15/08
    function mainNotAllowed() {
        ?>
        <h1>Updates From SVN</h1>
        
        <div style="font-size: 110%">
        <br/>
        <b>This is a development workstation.</b>  This machine has
        the "DEV_STATION" flag set to "Y", which means it is a development
        workstation.
        
        <br/>
        <br/>
        Development workstations do not use Andromeda to pull code,
        because of the danger of overwriting programmers' changes.  Instead,
        use your SVN client tool to retrieve code from your SVN server
        to this workstation.
        
        <br/>
        <br/>
        If this machine is not supposed to be development workstation,
        you can change it to a server by going to the
        <a href="?gp_page=variables">System Variables</a> table
        and changing "DEV_WORKSTATION" to "N".
        Don't forget to
        click on the "Force Cache Reload" link after you save your
        changes.
        </div>
        <?php
    }
    */
    
    /**
     * The user is running on a server and wants to get the
     * latest versions available.  Show them the list of
     * applications
     *
     */
    function mainHTML() {    
        $rows = svnVersions();
        
        ?>
        <style>
        #chart {
            border-spacing: 0;
            border-collapse: collapse;
        }
        #chart th {
            padding: 2px;
            border: 1px solid #C0C0C0;
            background-color: #E0E0E0;
        }
        #chart td {
            padding: 2px;
            border: 1px solid #C0C0C0;
        }
        </style>
        <script>
        function svnSearch() {
            var url='?gp_page=a_pullsvn&svnpull=1&gp_out=process';
            Popup(url,'Pull Software Updates');
        }
        </script>
        
        
        
        <div id="svn1">
        <h1>Pull Code From Subversion</h1>
        
        <?php if(count($rows)==0) { ?>
            <b>None of your applications have the "Overwrite From SVN" flag
               set to "Y".  Therefore there is nothing to do on this page.
               <br/><br/>
               This may not be a bad thing -- normally on a development
               workstation you do not use this feature because you use a 
               Subversion client manually.
               <br/><br/>
               If this is a server
               then you will want to set the "Overwrite From SVN" flag to 
               "Y" for each application you will host on this server.
            </b>
            <?php return; ?>
        <?php } ?>
        
        <div class="warning">
        Warning! If you use this program on a development workstation you
        can <i>overwrite your own work</i>, because this program 
        unconditionally overwrites the application code.  On development
        workstations you should use manual Subversion tools.
        </div>
        <h2>Application List</h2>
        <table id="chart">
          <thead><tr><th>Application / Respository
                     <th>Local Version
                     <th>User Name
                     <th>Password
          </thead>
        <?php
        $dd = ddTable('applications');
        foreach($rows as $row) {
            $tr = html('tr');
            $a=html('a');
            $a->setHTML($row['application']);
            $a->hp['href']="?gp_page=applications&gp_skey=".$row['skey'];
            $td = html('td',$tr,$a->bufferedRender());
            $td = html('td',$tr,$row['local']);
            $td = html('td',$tr,$row['svn_uid']);
            $pwd = $row['svn_pwd']=='' ? '' : '********';
            $td = html('td',$tr,$pwd);
            $tr->render();
            
            $tr = html('tr');
            $td = html('td',$tr,$row['svn_url']."<br/><br/>");
            $td->hp['colspan'] = 4;
            $tr->render();
        }
        ?>
        </table>

        <br/>
        <br/>
        This program will use any username and password that are supplied.
        If they are not supplied, the program assumes they are not
        needed.  If you need to change the username or password, go to the
        details page for the particular application.
        <br/>
        <br/>
        <a href="javascript:svnSearch()">Search For Updates Now</a>

        <br/>
        <br/>
        <a href="javascript:window.location.reload()">Refresh This Page</a>
        
        </div>
        <?php
        
    }
    

    /**
     * The user has requested that we download the latest 
     * version of each application from its respective
     * 
     *
     */
    function mainPull() { 
        # Don't hold up the system
        Session_write_close();
        
        $rows=svnVersions();
        $dir=fsDirTop().'pkg-apps/';

        x_echoFlush('<pre>');
        x_EchoFlush('<h2>Pulling Software Updates From SVN</h2>');
        
        // Loop through the apps.
        foreach($rows as $row) {
            x_EchoFlush("");
            x_echoFlush("<b>Application: ".$row['application']."</b>");
            if($row['svn_url']=='') {
                x_echoFlush("  No SVN repository, skipping.");
                continue;
            }

            # Add a trailing slash to svn_url
            $row['svn_url'] = AddSlash(trim($row['svn_url']));
            
            # If there is a username and password both, use those
            $urlDisplay = $row['svn_url'];
            $url = $row['svn_url'];
            if($row['svn_uid']<>'' && $row['svn_pwd'] <> '') {
                list($proto,$urlstub) = explode("//",$url);
                $uid=$row['svn_uid'];
                $pwd=$row['svn_pwd'];
                $url="$proto//$uid:$pwd@$urlstub";
                $urlDisplay = "$proto//$uid:*****@$urlstub";
                    
            }
            x_echoFlush("  Complete URL: ".$urlDisplay);
            
            # Now pull the list of versions
            x_echoFlush("  Querying for latest version");
            $rawtext = file_get_contents($url);
            $matches=array();
            preg_match_all('!\<li\>\<a.*\>(.*)\</a\>\</li\>!U',$rawtext,$matches);
            $versions = $matches[1];
            if(count($versions)==0) {
                x_EchoFlush("  No versions listed, nothing to pull.");
                continue;
            }
            
            # Work out what the latest was and report it
            $latest=array_pop($versions);
            if(substr($latest,-1)=='/') {
                $latest = substr($latest,0,strlen($latest)-1);
            }
            x_echoFlush("  Latest version is: ".$latest);
            x_EchoFlush("  Local version is: ".$row['local']);

            # Decide if we need to continue
            if($latest == $row['local']) {
                x_EchoFlush("  Local version is latest, nothing do to.");
                continue;
            }

            # Determine some stub values and pass processing to
            # the recursive file puller
            x_EchoFlush("  Local version is out of date, pulling latest");
            $dirv = $dir.trim($row['application']).'-VER-'.$latest.'/';
            mkdir($dirv);
            $this->svnWalk("$url/$latest/",$dirv);
            
            x_echoFlush("  Copying files into application directory");
            $basedir = str_replace( 'andro/', '', fsDirTop()); 
            if ( isWindows() ) {
               $command = 'xcopy /y /e /c /k /o ' .$dirv .'* ' 
                    .$basedir .trim( $row['application'] ) .'/';
            }
            else {
               $command2 = 'cp -Rf ' .$dirv .'* ' 
                    .$basedir .trim( $row['application']) .'/';
            }
            echo( $command2 );
            `$command2`;
        }
        
        x_echoFlush("<hr/>");
        x_EchoFlush("<h3>Processing Complete</h3>");
        
        $this->flag_buffer=false;
    }        
    
    function svnWalk($url,$dirv) {
        x_EchoFlush("    Directory: ".$dirv);
        # pull the URL, which is expected to be a list
        # of directories and files, and process each
        # accordingly
        #
        $raw = file_get_contents($url);
        $matches=array();
        preg_match_all('!\<li\>\<a.*\>(.*)\</a\>\</li\>!U',$raw,$matches);
        $files = $matches[1];
        
        #  A trailing backslash means a directory, make the
        #  directory and recurse.  Otherwise write the file
        foreach($files as $file) {
            if($file=='..') continue;
            if(substr($file,-1)=='/') {
                mkdir($dirv.$file);
                $this->svnWalk($url."/$file",$dirv.$file);
            }
            else {
                $fileText = file_get_contents($url.$file);
                file_put_contents($dirv.$file,$fileText);
            }
        }
    }
}
?>
