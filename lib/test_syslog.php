<?php
include('../index.php');

$lognum = syslogopen('TEST');
echo "Log # is $lognum\n";
sysLogEntry($lognum,"Entry 1 of 1");
syslogClose($lognum);
?>
