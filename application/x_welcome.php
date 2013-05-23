<?php
class x_welcome extends x_table2 {
    function main() {
        # KFD 2/17/09.  If installed with Debian package, will
        #               have username and password of "start".
        #               Must force a new id now.
        #
        if(SessionGet('UID')=='start') {
            if(gp('user_id')<>'') {
                if(gp('user_id')=='') {
                    ErrorAdd("User Id may not be empty");
                }
                if(substr(gp('user_id'),0,5)=='andro') {
                    ErrorAdd("User Id may not begin with 'andro'");
                }
                if(gp('password1')<>gp('password2')) {
                    ErrorAdd("Passwords do not match");
                }
                if(strlen(trim(gp('password1')))==0) {
                    ErrorAdd("Password may not be empty");
                }
                if(!Errors()) {
                    $row = array(
                        'user_id'=>gp('user_id')
                        ,'member_password'=>gp('password1')
                    );
                    SQLX_Insert('usersroot',$row);
                    if(!Errors()) {
                        scDBConn_Pop();
                        SessionSet('UID',gp('user_id'));
                        SessionSet('PWD',gp('password1'));
                        scDBConn_Push();
                        SQL("DELETE FROM USERSROOT WHERE user_id='start'");
                        
                        # Get rid of the form that replaces login
                        $file=fsDirTop().'application/x_login_form.inc.html';
                        $fileto=$file.'.done';
                        @rename($file,$fileto);
                    
                        ?>
                        <h1>New Root User Created</h1>
                        
                        <p>Your new user is created.</p>
                        
                        <p><a href="index.php?st2logout=1">
                           Return to Login Page</a></p>
                        <?php
                        return;
                    }
                }
            }
            
            
            ?>
            <h1>New Install - Must Create User</h1>
            
            <p>You are logged into your Node Manager with the default
               username of "start" and password "start".  We have to change
               this right now so nobody can get into your new system.
            </p>
            
            <p>Please provide a new ROOT (superuser) user id and password
               below.  Andromeda will create the new user, log you in as
               that user, and remove the "start" user.
            </p>
            
            <table>
              <tr><td align="left">User Name
                  <td><input name = 'user_id' /> (may not begin with 'andro')
              <tr><td align="left">Password
                  <td><input type="password" name = 'password1'/>
              <tr><td align="left">Password (verify)
                  <td><input type="password" name = 'password2'/>
            </table>
            <input type="submit" value="Create User Now" />
            <?php
            return;
        }
        
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
            
            <p>Version <?php echo $latest?> is available.   <a href="?gp_page=a_pullsvn"
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
    "http://www.andromeda-project.org/creatinganapplication.html"
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
and you will want learn the Andromeda's 
<a target="_blank" href=
"http://www.andromeda-project.org/databaseprogramming.html"
>Database Programming</a> language.

<br/>
<br/>
Once you are ready to try some custom pages, you are ready to look
at <a target="_blank" href=
"http://www.andromeda-project.org/webprogramming.html"
>Web Programming</a>.
   
    
</div>        
        <?php
    }
}
?>
