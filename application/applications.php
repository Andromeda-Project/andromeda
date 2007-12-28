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
class applications extends x_table2 {
   function aLinks_Extra($mode) {
      if($mode<>'upd') return array();
      $app = trim($this->row['application']);
      
      $retval= array(
         hLinkBuild($app,'Build This Application')
         ,hLinkPopup(
            ''
            ,'View Most Recent Log'
            ,array(
               "gp_page"=>"a_builder_log"
               ,"gp_out"=>"info"
               ,'txt_application'=>$app
            )
         )
      );

      // If no authoritative node is listed, we must be it, so list
      // an option.  Otherwise allow code control options.
      $lnk1="?gp_page=appversions_p&gp_app=".trim($this->row['application']);
      $lnk2="?gp_page=a_scontrol&gp_skey=".$this->row['skey'];
      //hprint_r($this->row);
      if(trim($this->row['node'])=='LOCAL'  
        || trim($this->row['node'])==''
        || is_null($this->row['node'])) {
         $retval[]=hLink('',"Publish Current Code",$lnk1);
      }
      else {
         $retval[]=hLink('',"Source Code Functions",$lnk2);
      }
      


      return $retval;
   }
}
?>
