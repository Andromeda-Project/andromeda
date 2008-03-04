<?php
// This is the basic query used maybe twice
$query = "Select * from applications where application='andro'";
$rows=$this->SQLReadRows($query);

// If nothing there, exit now, this must be a node manager build
if(!isset($rows[0])) return;

$row =$rows[0];
if(is_null($row['svn_url']) || trim($row['svn_url'])=='') {
    $url = 'http://andro.svn.sourceforge.net/svnroot/andro/releases/';
    $this->logEntry("Empty svn_url, setting it to default: ");
    $this->logEntry($url);
    $sq="update applications
            set svn_url = '$url'
          where application = 'andro'";
    $this->SQL($sq);
    $row = $this->SQLReadRow($sq);
}

// If the Node URL has been filled in, don't run
// this script anymore.
if(!is_null($row['svn_url']) && trim($row['svn_url'])<>'') {
    $this->scriptSuccess();
}
?>
