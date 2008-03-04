<?php
class x_welcome extends x_table2 {
    function main() {
        ?>
        <h1>Welcome to the Andromeda Node Manager</h1>
        <?php
        
        // Work out if there is a new release available
        //
        $urlr="https://andro.svn.sourceforge.net/svnroot/andro/releases/";
        ob_start();
        // KFD 3/1/08, if Andro Dev Station, don't mention andro upgrades
        if(OptionGet('DEV_STATION_ANDRO')=='Y') {
            $htmlVersions = '';
        }
        else {
            $htmlVersions = file_get_contents($urlr);
        }
        ob_end_clean();
        $matches =array();
        preg_match_all(
            '/<li><a href=.*\>(.*)<\/a><\/li>/'
            ,$htmlVersions
            ,$matches
        );
        $versions = ArraySafe($matches,1,'');
        if(count($versions)>0) {
            $latest = array_pop($versions);
            $latest = str_replace('/','',$latest);
            
            // Get current latest
            $current = '';
            $file=$GLOBALS['AG']['dirs']['application'].'_andro_version_.txt';
            if(file_exists($file)) {
                $current = file_get_contents($file);
            }
            
            if($latest >= $current)  {
            ?>
            <br/>
            <div style="border: 5px solid gray; color: blue
            font-weight: bolder; margin: 8px; padding: 0 8px 8px 8px">
            <h2>New Version of Andromeda Available</h2>
            
            <p>Version <?=$latest?> is available at the Andromeda 
            Sourceforge repository.   <a href="?gp_page=a_pullsvna"
            >Click Here </a> to upgrade the Node Manager.
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
    "http://www.andromeda-project.org/pages/cms/Documentation">here</a>.
    <br/>
    <br/>

    If you want to start programming a new application right away, 
    <a target="_blank"  href=
    "http://www.andromeda-project.org/pages/cms/Starting+A+New+Application"
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
   <b><?=$dirs[0]['dir_pub']?>/test/application/test.dd.yaml</b>
<br/>
<br/>

All Andromeda applications start with
a database specification.  These specifications are 
more powerful than anything else out there, 
and you will want learn the Andromeda way to 
<a target="_blank" href=
"http://www.andromeda-project.org/pages/cms/Defining+a+Database"
>Define a Database</a>, and if you don't want to read the primer, you
can start with the <a target="_blank"  href=
"http://www.andromeda-project.org/pages/cms/The+Smallest+Possible+Andromeda+Specification"
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
