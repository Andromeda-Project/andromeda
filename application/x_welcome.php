<?php
class x_welcome extends x_table2 {
    function main() {
        /* FUTURE X6 VERSION OF NODE MANAGER
        ?>
        <h1>Node Manager Upgrade Required</h1>
        
        <p>The new version of the Node Manager uses the "x6" 
           interface to provide a richer experience.  Please click
           the link below to upgrade your Node Manager.  Once the
           upgrade is complete, log out and back in.
        </p>
        
        <p><a href="javascript:Popup('index.php?gp_page=a_builder&gp_out=none&x2=1&txt_application=andro','Build')"
            >Upgrade Node Manager Now</a>.</p>
            
        <p><a href="?st2logout=1">Logout After Upgrade</a>.</p>
        
        <?php
        return;
        */
        # <------- EARLY RETURN.
        # KFD 1/10/08, The old x_welcome screen is not used anymore,
        #              we have the new 'cpanel' now in x6.
        # ===============================================================
        ?>
        <h1>Welcome to the Andromeda Node Manager</h1>
        <?php
        
        // Work out if there is a new release available
        //
        $apps = svnVersions();
        $andro = a($apps,'andro',array('svn_url'=>''));
        
        if(trim($andro['svn_url'])=='') {
            $htmlVersions = '';
        }
        else {
            $htmlVersions = @file_get_contents($andro['svn_url']);
        }
        $matches =array();
        preg_match_all(
            '/<li><a href=.*\>(.*)<\/a><\/li>/'
            ,$htmlVersions
            ,$matches
        );
        $versions = ArraySafe($matches,1,array());
        if(count($versions)>0) {
            $latest = array_pop($versions);
            $latest = str_replace('/','',$latest);
            
            // Get current latest
            $current = $andro['local'];
            if($latest >= $current)  {
            ?>
            <br/>
            <div style="border: 5px solid gray; color: blue
            font-weight: bolder; margin: 8px; padding: 0 8px 8px 8px">
            <h2>New Version of Andromeda Available</h2>
            
            <p>Version <?php echo $latest?> is available at the Andromeda 
            Sourceforge repository.   <a href="?gp_page=a_pullsvn"
            >Click Here </a> to go to the Pull Code From Subversion.
            </div>
            <?php        
            }
        }
            
        
            
        $dirs=SQL_AllRows("select * from webpaths where webpath='DEFAULT'");
        ?>
<div style="font-size: 120%; line-height: 120%; padding: 10px">

<h2>For First Time Users</h2>
    This program is the Andromeda <b>Node Manager</b>.  You use this
    program to build your applications.
    <br/>
    <br/>
    Our main documentation is <a target="_blank" href=
    "http://www.andromeda-project.org/">here</a>.
    <br/>
    <br/>

    If you want to start programming a new application right away, 
    <a target="_blank"  href=
    "http://www.andromeda-project.org/pages/cms/creating+an+application.html"
    >The instructions are here</a>, or you can just 
    <a href="?gp_page=applications&gp_mode=ins">define a new application here.</a>
    <br/>
    <br/>

After you defined an application, click on the "build this application"
link to create all of the directories and the empty database.</p>
<br/>
<br/>

<h2>Your Application Program Files</h2>

After building the application skeleton you can start working on the
   database specification.  If your application code is "test", then put
   the database specification into the file
   <br/>
   <br/>
   <b><?php echo $dirs[0]['dir_pub']?>/test/application/test.dd.yaml</b>
<br/>
<br/>

All Andromeda applications start with
a database specification.  These specifications are 
more powerful than anything else out there, 
and you will want learn the Andromeda way to 
<a target="_blank" href=
"http://www.andromeda-project.org/pages/cms/database+programming.html"
>Define a Database</a>, and if you don't want to read the primer, you
can start with the <a target="_blank"  href=
"http://www.andromeda-project.org/pages/cms/a+starter+database+specification.html"
>Sample Specification</a>.

<br/>
<br/>
Once you are ready to try some custom pages, you are ready to look
at <a target="_blank" href=
"http://www.andromeda-project.org/pages/cms/Web+Programming"
>Web Programming</a>, and if you want to dive into your first page, the
instructions for <a target="_blank" href=
"http://www.andromeda-project.org/pages/cms/Pages%2C+Classes+and+Tables"
>Where to put the file and what to put in it.</a>
   
    
</div>        
        <?php
    }
}
?>
