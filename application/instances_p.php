<?php
class instances_p extends x_table2 {
    function custom_construct() {
        if(gp('gp_posted')==1){
            $this->flag_buffer=false;
            $this->caption = "Run another build";
        }
    }
   
    function main() {
        $app = gp('gp_app');
        $sApp =SQLFC(gp('gp_app'));
        $sInst=SQLFC(gp('gp_inst'));
        $hApp =hSanitize(gp('gp_app'));
        $hInst=hSanitize(gp('gp_inst'));
        $rows=SQL_AllRows(
         "SELECT * from instances 
           where application=$sApp AND instance=$sInst"
        );
        if(count($rows)<>1) {
         ?>
         <div class="errorbox">Incorrect call to instance processing.</div>
         <?php
         return;
        }
        $row=$rows[0];
        $hVer = hSanitize(trim($row['version']));
        
        // Maybe we are on processing branch
        if(gp('gp_posted')==1) {
            $this->Process($rows[0]);
            return;
        }

        // KFD 2/4/08, Modify this to look for versions on disk
        //     for an svn-enabled server node
        $hWarn = '';
        $av = '';
        if(OptionGet('DEV_STATION','')=='N') {
            $sq="Select * from applications where application=$sApp";
            $rapp = SQL_OneRow($sq);
            if(trim($rapp['svn_url'])=='') {
                $hWarn = '<br/><br/><b>Subversion url needed.</b> '
                    .'You can begin by providing a URL to the subversions '
                    .'repository for this application on '
                    .'<a href="?gp_page=applications&gp_skey='.$rapp['skey']
                    .'">the editing screen</a>.';
            }
            
            $versions = svnVersions();
            $verx = trim($versions[$app]['local']);
            
            $av = "<p>Latest Andromeda Version: "
                .trim($versions['andro']['local']);
        }
        else {
            // Get the current version, and get the latest version available
            $verx=SQL_OneValue("mv",
             "Select max(version) as mv from appversions
               WHERE application=$sApp"
            );
            if(is_null($verx)) $verx='';
        }
      
        ?>
        <h1>Instance Upgrade</h1>
        <p>Application: <?=$hApp?>   </p>
        <p>Instance: <?=$hInst?>     </p>
        <p>Current Version: <?=($hVer=='' ? '-none-' : $hVer)?> </p>
        <p>Latest Version Available: <?=($verx=='' ? '-none-' : $verx)?> </p>
        <?=$av?> 
        <p>&nbsp;</p>
        <p>
        <?php
        if($verx=='') {
            ?>
            <b>No official versions are available.</b>  An instance can only
            be upgraded when an official version is available.  You may
            download release code for this application, or you may
            generate files out of your development code.
            </p>
            <?=$hWarn?>
            <?php
            return;
        }
        else {
            $caption=$hVer=='' ? 'Build as ' : 'Upgrade To';
            echo hLinkPopup(
            ''
            ,$caption.' version '.$verx
                ,array(
                   'gp_app'=>gp('gp_app')
                   ,'gp_inst'=>gp('gp_inst')
                   ,'gp_posted'=>1
                   ,'gp_page'=>'instances_p2'
                   ,'gp_out'=>'none'
                   ,'gp_ver'=>$verx
                )
            );
        }
    }
   
    // ---------------------------------------------------------------
    // Processing Functions Go Here
    // ---------------------------------------------------------------
    function Process($row) {
       ?>
       <h2>Coding Error</h2>
       
       <p>The instance build program is supposed to route through
          to the program instances_p2.  If you see this message
          then the HTTP POST did not go through correctly.
       
       <?php
       
    }
}
?>
