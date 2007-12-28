<?php
/* ================================================================== *\
   (C) Copyright 2005 by Secure Data Software, Inc.
   
    ___    _____  _    _     _      _  ___   
  |  _`\ (  _  )( )  ( )   ( )    (_)(  _`\ 
  | (_) )| (_) |`\`\/'/'   | |    | || (_) )
  | ,  / |  _  |  >  <     | |  _ | ||  _ <'
  | |\ \ | | | | /'/\`\    | |_( )| || (_) )
  (_) (_)(_) (_)(_)  (_)   (____/'(_)(____/'
  
  This is an R-A-X library, part of Andromeda
  Purpose: Everything revolving around XML conversion to db stuff
  Banner art from: http://www.network-science.de/ascii/
   
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
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// Utility Routine: Parse an input
// ------------------------------------------------------------------
// ------------------------------------------------------------------
function raxXML($input,$source='file') {
   // Create a stack with a high-level entry at top
   $GLOBALS['raxxml_stack'] = array(array('children'=>array()));
   global $raxxml_stack;

   // Define parser and open file
   $xml_parser = xml_parser_create();
   xml_set_element_handler($xml_parser, "raxXML_PStartElm", "raxXML_PEndElm");
   xml_set_default_handler($xml_parser, "raxXML_PcharData");
   //xml_set_character_data_handler($xml_parser, "raxXML_PcharData");

   $count = 0;
   if ($source=='string') {
      xml_parse($xml_parser, $input, false);
      xml_parse($xml_parser, '',true);
   }
   else {
      if (!($fp = fopen($input, "r"))) {
         ErrorAdd("could not open XML input:" .$input);
         return array();
      }
      // process file
      while ($data = fread($fp, 4096)) {
         xml_parse($xml_parser, $data, feof($fp));
         //    die(sprintf("XML error: %s at line %d",
         //                xml_error_string(xml_get_error_code($xml_parser)),
         //                xml_get_current_line_number($xml_parser)));
         //}
      }
   }
   xml_parser_free($xml_parser);

   // Now we begin some real work, doing the conversion
   // Begin recursing at $stack[0]['children'][0]
   //
   $root = array();
   raxXML_Reformat($root,$raxxml_stack[0]['children'][0]);
   $retval[$raxxml_stack[0]['children'][0]['name']]=&$root;
   return $retval;
} 

// ------------------------------------------------------------------
// Parser helper routines
// ------------------------------------------------------------------
function raxXML_PStartElm($parser, $name, $attrs) {
   $name = strtolower($name);
   $name = str_replace('-','_',$name);
   $parser=$parser;
   // Make a new node 
   $newnode = array(
      'value'=>''
      ,'name'=>$name
      ,'atts'=>$attrs
      ,'children'=>array()
   );
   
   // Push the new node onto the stack.  No more action
   // until the end tag.
   global $raxxml_stack;
   array_push($raxxml_stack,$newnode);
}

function raxXML_PEndElm($parser, $name)
{
   $parser=$parser;  // clears parser warning unused variable
   $name = $name;    // clears parser warning unused variable
   
   // Take last element and make it a child of the 
   // next-to-last element.
   global $raxxml_stack;
   $count = count($raxxml_stack);
   $parnode = &$raxxml_stack[$count-2];
   $parnode['children'][] = &$raxxml_stack[$count-1];
   
   // And pop the finished child off the stack
   array_pop($raxxml_stack);
}

function raxXML_PcharData($parser, $data) {
   $parser=$parser;
   global $raxxml_stack;
   $count = count($raxxml_stack);
   $raxxml_stack[$count-1]['value']=$data;
}

function raxXML_Reformat(&$current,&$node) {
   // Now pull the attributes up and make them array elements.
   // Fold the case.  Note attributes will later be overwritten
   // by sub-elements if the names collide.
   //
   foreach($node['atts'] as $attname=>$attvalue ) {
      $current[strtolower($attname)]=$attvalue;
   }
   
   // Run through kids and count how many by name
   $nodekeys = array_keys($node['children']);
   $counts   = array();
   foreach($nodekeys as $key) {
      $key = strtolower($key);
      if (!isset($counts[$node['children'][$key]['name']])) {
         $counts[$node['children'][$key]['name']]=0;
      }
      $counts[$node['children'][$key]['name']]++;
   }
   
   // A child with 1 instance, a value, and no atts is converted into
   // an attribute of the parent
   //
   $kids = array();
   foreach($nodekeys as $key) {
      $name = $node['children'][$key]['name'];
      
      $makeatt=0;
      if ($counts[$name]==1) {
         if (count($node['children'][$key]['children'])==0) {
            if(count($node['children'][$key]['atts'])==0) {
               $makeatt=1;
               $current[strtolower($name)]=$node['children'][$key]['value'];
            }
         }
      }
      
      if ($makeatt==0) {
         // recurse and add it to the named list for these
         $child = array();
         raxXML_Reformat($child,$node['children'][$key]);
         $kids[strtolower($name)][] = &$child;
         unset($child);
      }
   }
   $kidkeys = array_keys($kids);
   foreach ($kidkeys as $key) {
      $current[strtolower($key)] = &$kids[$key];
   }
}

// ------------------------------------------------------------------
// ------------------------------------------------------------------
// PULLUP:  Flatten out irrelevant elements
// ------------------------------------------------------------------
// ------------------------------------------------------------------
function raxXML_Pullup(&$xmlarr,$pullups) {
   // Run through the list of pullups.  This works for nested
   // elements because they become visible in succession as 
   // prior pullups are executed.
   //
   foreach ($pullups as $pullup) {
      $pullup = strtolower($pullup);
      // Now loop through each instance of this bogus element
      //
      if(isset($xmlarr[$pullup])) {
         $pkeys = array_keys($xmlarr[$pullup]);
         foreach ($pkeys as $pkey) {
            $pkeys2 = array_keys($xmlarr[$pullup][$pkey]);
            foreach($pkeys2 as $pkey2) {
               // If the child is scalar, assign it, else loop
               // through its members and assign them
               if(!is_array($xmlarr[$pullup][$pkey][$pkey2])) {
                  // 5/3/06.  An empty element comes over as a string, 
                  // which causes problems if the element appears again
                  // later with attributes or children.  Don't assign
                  // empties
                  $x = $xmlarr[$pullup][$pkey][$pkey2];
                  if(trim($x)<>'') {
                     $xmlarr[$pkey2] = $x;
                  }
                  
               }
               else {
                  $pkeys3=array_keys($xmlarr[$pullup][$pkey][$pkey2]);
                  foreach ($pkeys3 as $pkey3) {
                     $xmlarr[$pkey2][] = $xmlarr[$pullup][$pkey][$pkey2][$pkey3];
                  }
               }
            }
         }
         unset($xmlarr[$pullup]);
      }
   }
}

// -----------------------------------------------------
// -----------------------------------------------------
// Convert an rax-XML array into rows suitable for
// pushing to a database or for generating a layout
// -----------------------------------------------------
// -----------------------------------------------------
function raxXML_RowPrepare(&$row,$prefix) {
   $retarr = array();
   foreach ($row as $colname=>$colvalue) {
      $retarr[strtolower($prefix.'_'.$colname)] = $colvalue;
   }
   return $retarr;
}

function raxXML_MakeRows_Array(&$rows,&$root,$map,$name,$mode='analyze',$pre=array()) {
   $count = 0;
   foreach($root as $oneitem) {
      raxXML_MakeRows($rows,$oneitem,$map,$name,$mode,$pre);
   }
}
 
function raxXML_MakeRows(&$rows,&$root,$map,$name,$mode='analyze',$pre=array()) {
   //  The element must have its pullups cleared
   if(isset($map['pullups'])) {
      raxXML_Pullup($root,$map['pullups']);
   }

   // Get some arrays handy
   $row  = $pre;
   $keys = array_keys($root);
   $repeaters = array();
   if(isset($map['children'])) {
      foreach($map['children'] as $childname=>$stuff) {
         $repeaters[strtolower($childname)] = 'child';
      }
   }
   if(isset($map['heirs'])) {
      foreach($map['heirs'] as $childname=>$stuff) {
         $repeaters[strtolower($childname)] = 'heir';
      }
   }
   
   // Loop through the keys.  First assign properties, then loop
   // again to do children and heirs
   foreach ($keys as $key) {
      if (!isset($repeaters[$key])) {
         if (!is_array($root[$key])) {
            $keyrow = str_replace('-','_',$key);
            $row[$keyrow] = $root[$key];
         }
      }
   }
   foreach ($keys as $key) {
      if (isset($repeaters[$key])) {
         // The repeaters are recursed for their own structures
         //
         $child = &$root[$key];
         if($repeaters[$key]=='child') {
            $pre = array(); $childmap =$map['children'][$key];
         }
         else {
            $pre = $row;  $childmap =$map['heirs'][$key];
         }
         raxXML_MakeRows_Array($rows,$child,$childmap,$key,$mode,$pre);
      }
   }
   
   // Now that we have made the row, the rest depends on the mode.
   // In analyze mode we are looking for widest values of columns,
   // In data mode we are saving a new row of values destined for the db
   //
   if (!isset($rows[$name])) $rows[$name] = array();
   if ($mode=='data') {
      $rows[$name][] = $row;
   }
   else {
      foreach($row as $colname=>$colvalue) {
         $maxnow = isset($rows[$name][$colname]) ? $rows[$name][$colname] : 0;
         $maxnew = max($maxnow,strlen($colvalue));
         $rows[$name][$colname]=$maxnew;
      }
   }
}

// -----------------------------------------------------
// Produce layout from sizes array generated in
// raxXML_MakeRows()
// -----------------------------------------------------
function raxXML_Layout(&$rows,$map,$top) {
   // Open file and write banner
   //
   $dir = AddSlash($GLOBALS['AG']['dirs']['application']);
   $F = fopen($dir."layout.add","w");
   raxFLn($F,'// ======================================================'); 
   raxFLn($F,'// GENERATED LAYOUTS FOR FEED: '.$map['prefix']);
   raxFLn($F,'// Layouts generated: '.date('r'));
   raxFLn($F,'// ======================================================'); 

   // Now flatten out the complete set of columns, find largest
   // example of any column across all tables.  
   //
   $columns = array();
   foreach ($rows as $row) {
      foreach ($row as $column=>$max) {
         if (!isset($columns[$column])) $columns[$column] = $max;
         $columns[$column] = max($columns[$column],$max,1);
      }
   }
   
   // Write the columns to the spec file all together
   //
   foreach ($columns as $colname=>$colwidth) {
      $colname = strtolower($colname);
      $colx = $map['prefix'].'_'.$colname;
      raxFLn($F
         ,'column '
         .str_pad($colx,25).' { description: '
         .str_pad($colname,25).' ;'
         .'type_id: char; '
         .'colprec: '.str_pad($colwidth,4,' ',STR_PAD_LEFT).'; }'
      );
   }

   // Now begin major loop for tables
   //
   $table_top = strtolower($map['prefix'].'_'.$top);
   foreach ($rows as $table_id=>$columns) {
      $table_id = strtolower($table_id);
      $table_x = $map['prefix'].'_'.$table_id;

      if ($table_id==strtolower($top)) {
         $pkauto = raxArray($map,'pksequence','SEQUENCE');
         $pkcol  = raxArray($map,'pkcolname' ,'pk_'.$table_x);
         $pkdefine=raxArray($map,'pkdefine'  ,'Y');
         
         raxFLn($F,'');
         if ($pkdefine=='Y') {
            raxFLn($F,'column '.$pkcol.' {');
            raxFLn($F,'   type_id: int;  description: PK; uino:Y;');         
            raxFLn($F,'}');
            raxFLn($F,'');
         }
         raxFLn($F,'table '.$table_x.' {');
         raxFLn($F,'   module: imports;');
         raxFLn($F,'   description: Raw '.strtoupper($map['prefix']).' Data;');
         raxFLn($F,'');
         raxFLn($F,"   column $pkcol { ");
         raxFLn($F,"      primary_key:Y; automation_id: $pkauto;");
         raxFLN($F,"   }");
      }
      else {
         raxFLn($F,'');
         raxFLn($F,'column pk_'.$table_x.'  {');
         raxFLn($F,'   type_id: int;');         
         raxFLn($F,'   description: PK; uino:Y; automation_id: SEQUENCE;');
         raxFLn($F,'}');
         raxFLn($F,'');
         raxFLn($F,'table '.$table_x.' {');
         raxFLn($F,'   module: imports;');
         raxFLn($F,'   description: '.strtoupper($table_id).' Details;');
         raxFLn($F,'   nomenu: Y;');
         raxFLn($F,'');
         raxFLn($F,'   column pk_'.$table_x.' {primary_key:Y; }');
         raxFLn($F,'   foreign_key { '.$table_top.'; delete_cascade: Y; }');
      }
      
      // each column within the table, make first 3 uisearch: Y
      //
      $colnum = 0;
      foreach ($columns as $colname=>$colsize) {
         $colname = strtolower($colname);
         $colname = $map['prefix'].'_'.$colname;
         $colnum++;
         if ($colnum<=3) {
            raxFLn($F,'   column '.$colname.' { uisearch: Y; }');
         }
         else {
            raxFLn($F,'   column { '.$colname.'; }');
         }
      }
      raxFLn($F,'}');
   }
   fclose($F);
}
?>
