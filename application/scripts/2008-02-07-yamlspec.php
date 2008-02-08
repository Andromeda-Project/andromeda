<?php
// This script is extremely simple, do an update
// and claim success.  If this does not work we
// have much bigger problems.
$query = "UPDATE applications
             SET appspec = 'andro.dd.yaml'
           WHERE application = 'andro'";
$this->SQL($query); 
$this->scriptSuccess();
?>
