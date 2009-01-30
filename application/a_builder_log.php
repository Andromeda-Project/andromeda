<?php
/* ================================================================== *\
   (C) Copyright 2005 by Secure Data Software, Inc.
   This file is part of Andromeda
   
   Andromeda is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   Andromeda is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with Andromeda; if not, write to the Free Software
   Foundation, Inc., 51 Franklin St, Fifth Floor,
   Boston, MA  02110-1301  USA 
   or visit http://www.gnu.org/licenses/gpl.html
\* ================================================================== */
class a_builder_log extends x_table2 {
	
	function main() {
		echo "<h1>View Build Log</h1>";
		?>
<p>Currently viewing the log for application code "
  <b><?php echo gp('application')?></b>".  Click any
   of the links below to see the logs for the particular application:</p>
<?php 
		// Run out list of apps they can see
		echo "<hr>\n";
		$results = SQL("select * from applications");
		while ($row = pg_fetch_array($results)) {
			echo HTMLE_A_STD(
				$row["application"]." - ".$row["description"],
				"a_builder_log",
				"gp_out=info&application=".$row["application"])."<br>\n";
		}
		echo "<hr>\n";
		
		// Make up a filename
		global $AG;
		$t=pathinfo(__FILE__);
		$pLogDir  = $t["dirname"];
		$pLogFile = "AndroDBB.".CleanGet("application").".log";
		$pLogPath = "$pLogDir/$pLogFile";
		
		if (!file_exists($pLogPath)) {
			echo "<p>There is no build log file for this application at this time.  This usually
				means that the application has not been built yet.  Try launching a build process
				and then coming back to this page.</p>";
			return;
		}
		
		//echo "<pre style=\"background-color: silver; color: blue\">";
		$fgc = file_get_contents($pLogPath);
		$fgc = str_replace("\n","<br>",$fgc);
		echo "<div style=\"font: 10pt courier; color: navy;\">".$fgc."</div>";
		//echo file_get_contents($pLogPath);
		//echo "</pre>";
		
		return "View Build/Update Log for: ".CleanGet("application");
	}
}
?>
