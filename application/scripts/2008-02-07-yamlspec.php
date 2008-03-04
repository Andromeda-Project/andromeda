<?php

// This will turn up empty if we are in a node manager
//  build during first installation.
$query = "Select * from applications where application='andro'";
$rows=$this->SQLReadRows($query);
// If nothing there, exit now, this must be a node manager build
if(!isset($rows[0])) return;


// This script is extremely simple, do an update
// and claim success.  If this does not work we
// have much bigger problems.
$query = "UPDATE applications
             SET appspec = 'andro.dd.yaml'
           WHERE application = 'andro'";
$this->SQL($query); 
$this->scriptSuccess();
?>
