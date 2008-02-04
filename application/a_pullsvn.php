<?php
class a_pullsvn extends x_table2 {
    /**
      *  Standard Andromeda main entry point for an
      *  extension of the x_table2 class.
      * 
      */
    function main() {
        if(gpExists('gp_set')) $this->mainDevSet();
        
        $dev_station = OptionGet('DEV_STATION');
        if($dev_station=='') return $this->mainPick();
        if($dev_station=='Y') return $this->mainNotAllowed();
        
        if(gpExists('svnpull')) return $this->mainPull();
        else $this->mainHTML();
    }
    

    /**
      *  If the user has clicked on a link setting this 
      *  as either a server or workstation, then make
      *  the setting change now.
      * 
      */
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
    
    /**
      *  If the user has never set the DEV_STATION flag
      *  then we will not let them do any kind of SVN pulls
      *  until they tell us if this is a dev station
      *  or a server.
      * 
      */
    function mainPick() {
        ?>
        <h1>Updates From SVN</h1>
        
        <div style="font-size: 110%">
        <h3>Please Make DEV_STATION Setting</h3>
        
        This machine can either be a development workstations or
           a server.  Please click one of the links below to make
           the setting.
        <br/>
        <br/>
        
        <ul><li>
        <a href="?gp_page=a_pullsvn&gp_set=dev">Click Here To Make
        This Machine a Development Workstation</a>.  A Development workstation
        is a machine that a programmer uses to write code.  On a
        development workstation the programmer does not use Andromeda to
        manage code, the programmer uses SVN tools to checkout 
        code and commit changes. 
        <br/><br/>
        
        
        <li><a href="?gp_page=a_pullsvn&gp_set=serve">Click Here To Make
        This Machine a Server</a>.  A server is a machine where no programmer
        is modifying code.  Servers get their code updates by using
        the "Updates From SVN" feature. 
        </ul>
        
        </div>
        <?php
    }
        
    /**
      *  If this is a dev workstation the user may not do
      *  software downloads.  
      * 
      */
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
        
        Development workstations do not need to pull published code
        from SVN repositories.  You can update the code on this workstation
        using SVN tools.  If Andromeda were to pull SVN updates to this
        machine, there is a risk of overwriting a programmer's work.
        <br/>
        <br/>

        If you wish to convert this machine to a server, go
        to the <a href="?gp_page=variables">System Variables</a> table
        and change the setting for "DEV_WORKSTATION" to "N".
        Don't forget to
        click on the "Force Cache Reload" link after you save your
        changes.
        
        
        </div>
        <?php
    }
    
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
        <h1>Check For Updates</h1>
        
        <p>Click the link below to search all SVN repositories
        for software updates.  The search will cover:
        </p>
        
        <table id="chart">
          <thead><tr><th>Application
                     <th>SVN Repository
                     <th>Local Version
                     <th>Latest Available
          </thead>
        <?php foreach($rows as $row) { ?>
            <tr><td><?=$row['application']?>
                <td><?=$row['svn_url']?>
                <td><?=$row['local']?>
                <td> -- ?? -- 
        <?php } ?>
        </table>
        
        <br/>
        <br/>
        <a href="javascript:svnSearch()">Search For Updates Now</a>
        
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
        $rows=svnVersions();
        $dir=$GLOBALS['AG']['dirs']['root'].'pkg-apps/';

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

            x_echoFlush("  SVN Repository: ".$row['svn_url']);
            $command = 'svn list '.$row['svn_url'];
            x_echoFlush("  Command: ".$command);
            x_echoFlush("  Querying for latest version");
            $rawtext = `$command`;
            //x_echoFlush("  return is: $rawtext");
            if($rawtext=='') {
                x_EchoFlush("  No versions listed, nothing to pull.");
                continue;
            }
            $rawtext = str_replace("\r","",$rawtext);
            $lines = explode("\n",$rawtext);
            // clear blank entry, then fetch latest
            array_pop($lines);
            if(count($lines)==0) {
                x_EchoFlush("  There are no versions listed, nothing to pull.");
                continue;
            }
            $latest=array_pop($lines);
            if(substr($latest,-1)=='/') {
                $latest = substr($latest,0,strlen($latest)-1);
            }
            
            x_echoFlush("  Latest version is: ".$latest);
            x_EchoFlush("  Local version is: ".$row['local']);

            if($latest == $row['local']) {
                x_EchoFlush("  Local version is latest, nothing do to.");
            }
            else {
                x_EchoFlush("  Local version is out of date, pulling latest");
                $dirv = $dir.trim($row['application']).'-VER-'.$latest;
                $command = 'svn export '.
                    $row['svn_url'].$latest.' '.$dirv;
                x_echoFlush(
                    "  Pulling code now, this make take a minute or three..."
                );
                `$command`;
                x_echoFlush("  Code pulled, finished with this application.");
            }
        }
        
        x_echoFlush("<hr/>");
        x_EchoFlush("<h3>Processing Complete</h3>");
        
        $this->flag_buffer=false;
    }        
}
?>
