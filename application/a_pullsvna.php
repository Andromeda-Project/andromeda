<?php
class a_pullsvna extends x_table2 {
    function custom_construct() {
        if(gpExists('gp_out')) $this->flag_buffer=false;
    }
    
    /**
      *  Standard Andromeda main entry point for an
      *  extension of the x_table2 class.
      * 
      */
    function main() {
        ob_start();
        if(gpExists('gp_out')) return $this->mainPull();
        else $this->mainHTML();
        ob_end_clean();
    }
    
    /**
     * Tell the user what is about to happen and let them
     * click on the "go" button
     *
     */
    function mainHTML() {
        ?>
        <h1>Upgrade Andromeda From Subversion</h1>
        
        <br/>
        This program will pull the latest release code for
        Andromeda directly from our Sourceforget.net Subversion tree.  

        <br/>
        <br/>
        <b>This program directly overwrites the running Node Manager
        Code.</b> <span style="color:red"><b>If you have been making changes to your Andromeda
        code on this machine all of those changes will be lost!</b><span>
           
        <br/>
        <br/>
        <a href="javascript:Popup('?gp_page=a_pullsvna&gp_out=none')"
        >Step 1: Pull Code Now</a>
        
        <br/>
        <br/>
        <a href="javascript:Popup('?gp_page=a_builder&gp_out=none&txt_application=andro')"
        >Step 2: Rebuild Node Manager</a>
        
        <?php
        
    }
    

    /**
     * The user has requested that we download the latest 
     * version of each application from its respective
     * 
     *
     */
    function mainPull() { 
        x_echoFlush('<pre>');
        x_EchoFlush('<h2>Looking For Andromeda Version</h2>');
        x_EchoFlush("");

        // First take care of where we are pulling version 
        // information from
        $def = "https://andro.svn.sourceforge.net/svnroot/andro/releases/";
        $row = SQL_OneRow(
            "Select * from applications where application='andro'"
        );
        if(!isset($row['svn_url'])) {
            x_EchoFlush("-- This looks like the first time this node has");
            x_EchoFlush("   been upgraded from Subversion.  Using default" );
            x_echoFlush("   URL to look for releases:");
            x_EchoFlush("   ".$def);
            $url = $def;
        }
        else {
            if(is_null($row['svn_url']) || trim($row['svn_url'])=='') {
                x_EchoFlush("-- Setting the Subversion URL to default:");
                x_EchoFlush("   ".$def);
                $url = $def;
                $row['svn_url'] = $def;
                SQLX_Update('applications',$row);
            }
            else {
                $url = trim($row['svn_url']);
                x_EchoFlush("-- Using the following URL for Subversion:");
                x_EchoFlush("   ".$url);
            }
        }

        // Find out what the latest version is
        x_EchoFlush("");
        x_EchoFlush("-- Querying for latest version...");
        $command = 'svn list '.$url;
        x_EchoFlush("   Command is: ".$command);
        $rawtext = `$command`;
        if($rawtext=='') {
            x_EchoFlush("-- NO VERSIONS RETRIEVED!");
            x_EchoFlush("   It may be that the Sourceforge site is down?");
            x_EchoFlush("");
            x_echoFlush(" ---- Stopped Unexpectedly --- ");
            return;
        }
        $rawtext = str_replace("\r","",$rawtext);
        $lines = explode("\n",$rawtext);
        // Pop off empty entry at end, then get latest version
        array_pop($lines);
        $latest=array_pop($lines);
        if(substr($latest,-1)=='/') {
            $latest = substr($latest,0,strlen($latest)-1);
        }

        x_EchoFlush("   Latest published version: ".$latest);

        // now find out what version we have
        x_EchoFlush(" ");
        x_EchoFlush("-- Finding out what version the node manager is at");
        $file=$GLOBALS['AG']['dirs']['application'].'_andro_version_.txt';
        x_EchoFlush("   Looking at file: $file");
        if(!file_exists($file)) {
            x_EchoFlush("   File not found, it appears this is the first time");
            x_EchoFlush("   this node has been upgraded this way. Will proceed");
            x_EchoFlush("   to get latest version.");
        }
        else {
            $version = file_get_contents($file);
            x_EchoFlush("   Current version is ".$version);
            
            if($version == $latest) {
                x_echoFlush("   This node is current!  Nothing to do!");
                x_EchoFlush("");
                x_echoFlush(" ---- Processing completed normally ---- ");
                return;
            }
            else {
                x_echoFlush("   Newer version available, will get latest.");
            }
        }
        
        // now get the latest code
        $dir = $GLOBALS['AG']['dirs']['root'];
        $command = 'svn export --force '.$url.$latest.' '.$dir;
        x_EchoFlush("");
        x_EchoFlush("-- Overwriting Node Manager now");
        x_echoFlush("   Command is ".$command);
        `$command`;
        x_echoFlush("");
        file_put_contents($file,$latest);
        x_EchoFlush(" ---- Processing completed normally ---- ");

    }        
}
?>
