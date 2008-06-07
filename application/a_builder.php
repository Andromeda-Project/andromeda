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
class a_builder extends x_table2 {
	function custom_construct() {
		$this->flag_buffer=false;
		$this->caption = "Run another build";
	}
	
	function main() {
        $x_app = trim(gp('txt_application'));
        session_write_close();
        ob_start();
        
        echo "<h1>Build in progress</h1>";
        echo "<hr>";
        echo "<p>The system is now building the application: <b>".$x_app."</b>.</p>";
        echo "<p>If you are testing and expect to build several times in a row, do not ";
        echo "close this window, just hit REFRESH and the build will start again.</p>";
        echo "<p>All information below this line is from the build log.</p>";
        echo "<hr>";
        
        // Get everything we need from the database, use it to build
        // a "do" program.
        // 
        $GLOBALS["x_password"] = trim(CleanGet("supassword"));
        
        $tsql = 
            'SELECT * from applications '
            .' WHERE application = '.SQL_Format('char',$x_app);
        $row_a = SQL_OneRow($tsql);
        $tsql = 
            'SELECT * from webpaths '
             .' WHERE webpath = '.SQL_Format('char',$row_a['webpath']);
        $row_n = SQL_OneRow($tsql);
        
        $dirws = trim($row_n["dir_pub"]);
        if (substr($dirws,-1,1)<>"/") $dirws.="/";
        $row["webserver_dir_pub"] = $dirws;
        
        $string = '
<?php
   // To run this program from the command line, you must
   // be logged in as a user that has superuser priveleges, such
   // as root or postgres.  When running from the web app,
   // the current user\'s priveleges are used.
	
   $GLOBALS["parm"] = array(
   "DBSERVER_URL"=>"localhost"
   ,"UID"=>"'.SessionGet('UID').'"
   ,"DIR_PUBLIC"=>"'.trim($row_n["dir_pub"]).'"
	,"DIR_PUBLIC_APP"=>"'.$x_app.'"
   ,"LOCALHOST_SUFFIX"=>"'.ArraySafe($row_n,'dir_local','').'"
   ,"APP"=>"'.$x_app.'"
   ,"APPDSC"=>"'.trim($row_a["description"]).'"
   ,"XDIRS"=>"'.trim($row_a['xdirs']).'"
   ,"ROLE_LOGIN"=>"'.ArraySafe($row_a,'flag_rolelogin','Y').'"
   ,"FLAG_PWMD5"=>"'.ArraySafe($row_a,'flag_pwmd5','N').'"
   ,"TEMPLATE"=>"'.trim($row_a['template']).'"
   ,"SPEC_BOOT"=>"'.trim($row_a["appspec_boot"]).'"
   ,"SPEC_LIB"=>"'.trim($row_a["appspec_lib"]).'"
   ,"SPEC_LIST"=>"'.trim($row_a["appspec"]).'");

	include("androBuild.php");  
?>
';
        $t=pathinfo(__FILE__);
        $dircur = AddSlash($t["dirname"])."../tmp/";
        $file = $dircur."do".$x_app.".php";
        $FILE = fopen($file,"w");
        fwrite($FILE,$string);
        fclose($FILE);
        x_EchoFlush("");
        include($file);
        echo ob_get_clean();
	}
}
?>
