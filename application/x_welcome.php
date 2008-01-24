<?php
class x_welcome extends x_table2 {
    function main() {
        $dirs=SQL_AllRows("select * from webpaths where webpath='DEFAULT'");
        ?>
<h1>Welcome to the Andromeda Node Manager</h1>

<br/>
<div style="font-size: 120%; line-height: 120%; padding: 10px">

This program is the Andromeda <b>Node Manager</b>.  If you are new to
   Andromeda then all you have to know is that you use this program to
   build other programs.
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

After you defined the application, click on the "build this application"
link to create all of the directories and the empty database.</p>
<br/>
<br/>

After building the application skeleton you can start working on the
   database specification.  If you application code is "test", then put
   the database specification into the file
   <b><?=$dirs[0]['dir_pub']?>/test/application/test.dd.yaml.</b>
<br/>
<br/>

In most systems you start with code, but Andromeda always starts with
a database specification.  Since our database specifications are so much
more powerful than anything else out there, 
you will want learn the Andromeda way to 
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
