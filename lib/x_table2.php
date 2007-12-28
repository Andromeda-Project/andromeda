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
class x_table2 {
   
   // These are all handled in the default __construct() method
	var $table_id = '';   // Name of table.  Not always required
   var $view_id  = '';   // What we should actually access
   var $table;           // Data Dictionary
   var $PageSubtitle='';

   // Modify this in construct_custom() to use different template
   var $html_template='';

   // If this is true, the output is buffered and then placed
   // somewhere in a file.  To stream content out to the browser,
   // set this to false in custom_construct(), and all content
   // goes right out without buffering.
   var $flag_buffer =true; // this we should probably keep?

   // Should buttons be pictures or images?
   var $button_images=false;
   
   // Which child tables should be displayed as browse tables
   var $extrabrowse=array();
   
   // flag to turn off links, and make vertical by default
   var $hlinks_disable=false;
   // 6/29/07, KFD, after lots of use in medical app, we want to compress
   //  vertically, so we want links always to go horizontal
   //var $hlinks_display='vertical';
   var $hlinks_display='horizontal';
   
   
   // DEPRECATED.  index_hidden.php complains if it cannot find
   // this properties, it was from the old x_table
   var $display;           // completely unknown? What is this?
   
   // Various HTML generation routines populate this array.  Defaults
   // are created here so later code can be unconditional
   var $h=array(  
      'ButtonBar'=>''
      ,'NavBar'=>''
      ,'Links'=>''
      ,'Content'=>''
      ,'Extra'=>array()
   );
   
   // Here is how we do 1:M forms.  The child table is the actual 
   // page that is called.  It generates its output, then calls the
   // parent table and says, "here is the content to nest inside of
   // yourself".  If this property is populated, the child will
   // invoke the parent for display
   //
   var $table_id_parent='';
   var $table_obj_child=null;  // reference back to child object
         
   // -----------------------------------------------------------
   // Default constructor is very smart and should always
   // be called by classes that override x_table2
   // -----------------------------------------------------------
   function __construct($table_id='') {
      // Grab table ID if given, otherwise try to figure
      // one out, but only if we don't have one
      if($table_id<>'') {
         $this->table_id = $table_id;
      }
      else {
         if($this->table_id=='') {
            $this->table_id=get_class($this);
         }
      }
      
      // Load data dictionary.  This is not a tragedy if
      // the page has no table, just forget about it.
		$this->table   = DD_TableRef($this->table_id);
      $this->view_id = '';
      if(is_array($this->table)) {
         if(isset($this->table['projections']['_uisearch'])) {
            // capure this directly so it can be overridden
            $this->projections['_uisearch']
               =$this->table['projections']['_uisearch'];
         }
         $this->view_id=DDTable_IDResolve($this->table_id);
      }
      
      // Look for an application-level variable for button_images
      /**
      level:class
      
      The property "button_images" can be overridden by setting
      an application-level property with the [[vgaSet()]] function.
      */
      if(vgaGet('button_images','')<>'') {
         $this->button_images=vgaGet('button_images');
      }

      // Set the page subtitle if we can find it
      if($this->PageSubtitle=='') {      
         $this->PageSubtitle =ArraySafe(
            $this->table
            ,"description"
            ,"PLEASE SET -PageSubtitle-"
         );
      }
      
      // Set the flag_buffer to false if we detect any flags 
      // that would do that
      if(gpExists('gp_ajaxcol')) {
         $this->flag_buffer=false;
      }
      if(gpExists('gp_fbproc')) {
         $this->flag_buffer=false;
      }
      if(gpExists('gp_xajax')) {
         $this->flag_buffer=false;
      }
      if(gpExists('fwajax')) {
         $this->flag_buffer=false;
      }
      

      // This array can be used to override properties on
      // child objects invoked by this object
      $this->children=array();
      
      // Now set all child tables to be 'drilldown', unless 
      // overridden in datadictionary
      if(isset($this->table['fk_children'])) {
         foreach($this->table['fk_children'] as $table_child=>$tabinfo) {
            $display = trim(ArraySafe($tabinfo,'uidisplay','drilldown'));
            $this->children[$table_child]['display'] 
               = $display <> ''
               ? $display
               : 'drilldown';
         }
      }
      
      
      // KFD 6/30/07, allow a gp variable to specify which control to
      //   set focus.  Do it early so it can be overrridden
      if(gpexists('html_focus')){
         vgfset('HTML_focus','x2t_'.hx(gp('html_focus')));
      }
      
      
      // ((((((((((((((((((((((((((((*))))))))))))))))))))))))))))))))
      // ((((((((((((((((( Run Custom-level Construct ))))))))))))))))
      $this->construct_custom();
      $this->custom_construct();
      // ((((((((((((((((( Run Custom-level Construct ))))))))))))))))
      // ((((((((((((((((((((((((((((*))))))))))))))))))))))))))))))))
     
      // Now pass through child tables again, removing any setting
      // that is not allowed by security privs.  Notice we do this
      // after the custom_construct, since that is where a setting might
      // be that conflicts with security setting.
      if(isset($this->table['fk_children'])) {
         $a=array_keys($this->table['fk_children']);
         foreach($this->table['fk_children'] as $table_child=>$tabinfo) {
            if(!DDUserPerm($table_child,'menu')) {
               $this->children[$table_child]['display']='none';
            }
         }
      }
      
   }
   
   // Meant to be overridden by child classes
   // This one is deprecated, we named it wrong:
   function construct_custom() { }
   // This is the preferred one, any future method of this type
   // will be named "custom_".
   function custom_construct() { }
	
   
   // -----------------------------------------------------------
   // -----------------------------------------------------------
   // Helper routines
   // -----------------------------------------------------------
   // -----------------------------------------------------------
   /**
   name:PKFromSKEYorPK
   returns:any Primary_key
   
   Attempts to determine if either gp_skey has been received, or a
   'txt_/primary_key/' value.  Depending upon which one, returns
   the value of the primary key.  If neither, returns empty string.
   */
   function PKFromSKEYorPK() {
      $pkcol=$this->table['pks'];
      $pkcand=gp('txt_'.$pkcol);
      if($pkcand<>'') { return $pkcand; }
      if(gp('gp_skey')<>'') {
         return SQL_OneValue($pkcol
            ,"SELECT $pkcol FROM ".$this->view_id
            ." WHERE skey=".gp('gp_skey')
         );
      }
      return '';
   }

   // -----------------------------------------------------------
   // MAIN function has only one feature, to output HTML.
   // If there were database saves to do, they were done
   // before the page was called.
   // -----------------------------------------------------------
	function main() {
      // ------------------------------------------------
      // Branch out to ajax handling functions
      if(gpExists('fwajax')) {
         return $this->FWAjax();
      }
      //   ...early return
      // ------------------------------------------------

      // Public sites can turn off table maintenance pages
      if(vgfGet('suppress_maintenance',false)) return;
      vgfset('maintenance',true);
      
      // If a "fk jump", retrieve skey and make it look
      // like an edit call.
      if(gp('gp_pk')<>'') { 
         $pkval = gp("gp_pk");
         $pkcol = $this->table["pks"];
         $pktyp = $this->table['flat'][$pkcol]["type_id"];
         $table_id= $this->table["table_id"];
         // KFD 10/26/06, used to be $table_id
         $sq="SELECT skey FROM ".$this->view_id
            ." WHERE ".$pkcol." = ".SQL_Format($pktyp,$pkval);
         gpSet('gp_skey',SQL_OneValue('skey',$sq));
         gpSet('gp_mode','upd');
      }
      

      // If we were invoked by a child table, don't do this
      if(is_null($this->table_obj_child)) {
         // KFD 10/26/06, keep as $table_id
         Hidden('gp_page',$this->table_id); // always return to same page
         Hidden('gp_mode','');
         Hidden('gp_skey','');
         Hidden('gp_action','');
         Hidden('gp_save','');
         hidden('gp_copy','');
      }

      // Work out what to do if mode is blank.  Might mean
      // upd, might mean browse.
      $mode=gp('gp_mode');
      $skey=gp('gp_skey');
      if($mode=='') {
         $mode=$this->MainCheckForMover();
         if($mode=='') {
            $mode= $skey=='' ? 'browse' : 'upd';
            gpSet('gp_mode',$mode);
         }
      }
      $this->mode=$mode;
      // KFD 8/13/07, Experimental COPY ability
      if(gp('gp_action')=='copy') {
         $mode='ins';
         gpSet('gp_mode','ins');
      }
      
      switch($mode) {
         case 'search': $this->PageSubtitle.=" (Lookup Mode)"; break;
         case 'ins'   : $this->PageSubtitle.=" (New Entry)"  ; break;
      }
      
      // ----------------------------------------------
      // Generate the main HTML elements
      if($mode=='browse') {
         $this->hBrowse();
      }
      elseif($mode=='mover') {
         $this->hMover();
      }
      else {
         $this->hBoxes($mode);
      }
      if($mode<>"mover") {
         $this->hButtonBar($mode);
      }
      $this->hLinks($mode);
      $this->hExtra($mode);
      
      // Now if this is a child table in a 1:M, it will not actually 
      // output its own stuff, it will invoke its parent, so let's
      // buffer the output
      if($this->table_id_parent<>'') ob_start();
      
      // Echo out the HTML
      $this->ehMain();

      // Put this out at end, after all HTML has been output      
      if($mode=="search") {
         //$controls=vgfGet('gpControls');
         $controls=ContextGet('OldRow');
         $hScript='';
         foreach($controls as $key=>$info) {
            $hScript.="\nob('x2t_$key').value='';";
         }
         ElementAdd("script","function clearBoxes() { \n".$hScript."}\n\n");
      }
      
      // Again, if this is a child table in a 1:M, capture the output and
      // make it the responsibility of the parent
      if($this->table_id_parent<>'') {
         $this->h['Complete']=ob_get_clean();

         // Wipe out and replace all gp variables, fool the parent object
         $OldRow=ContextGet('OldRow',array());
         $gpsave=aFromGP('gp_');
         gpUnsetPrefix('gp_');
         $dd = ContextGet('drilldown',array());
         $dd1= array_pop($dd);
         gpSet('gp_skey',$dd1['skey']);

         // Now invoke the parent object, tell it about us
         $object=objPage($this->table_id_parent);
         $object->table_obj_child = $this;
         $object->main();
         
         // Replace the wiped out gp variables
         gpUnsetPrefix('gp_');
         gpSetFromArray('gp_',$gpsave);
         ContextSet('OldRow',$OldRow);
         
         // Force the menu to come from the parent
         vgaSet('menu_selected',$this->table_id_parent);
      }
   }

   
   function mainCheckForMover() {
      $dd = ContextGet('drilldown',array());
      if(isset($dd[0]['page'])) {
         $tpar = $dd[0]['page'];
         $uid=trim($this->table['fk_parents'][$tpar]['uidisplay']);
         if($uid=='mover') { 
            return 'mover';
         }
      }
      return '';
   }
   
   // ----------------------------------------------
   // Main HTML otuput
   // ----------------------------------------------
   function ehMain() {
      $hN=$this->h['Links']
         .($this->h['Links']=='' ? '' : '<br/>')
         .$this->h['NavBar']
         .($this->h['NavBar']=='' ? '' : '<br>');
      ?>
      <h1><?=$this->PageSubtitle?></h1>
      <center>
      <table cellpadding=0 cellspacing=0 
             style="width: 100%; border-collapse: collapse">
         <?php  if(is_null($this->table_obj_child)) { ?>
            <?=$this->h['ButtonBar']?>
         <?php } ?>
        </tr>
        <tr>
          <td class="x2_content">
            <?=$hN?>
            <?=$this->ehMainModeComment($this->mode)?>
            <?=$this->h['Content']?>
          </td>
        </tr>
        <tr>
          <td style="vertical-align:top">
          <?php
          foreach ($this->h['Extra'] as $EKey=>$EContent) {
            echo $EContent;  
          }
          ?>
          </td>
        </tr>
      </table>
      </center>
      <?php
   }
   
   function ehMainModeComment($mode) {
      return;
      // KFD 6/28/07.  Nobody reads this message.  It has been superseded
      //    by our use of color hints on boxes.  It pushes text down and
      //    requires users to scroll down.
      /*
      switch($mode) {
         case 'ins':
            echo "<div class=\"x2_modecomment\">";
            echo "This is a new entry.  The information here will only be
              saved if you hit the [SAVE] button above or hit ALT-S or ENTER.";
            echo "</div>";
            break;
      }
      */
   }
      
   // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
   // Work out what buttons to display based on mode
   // and permissions
   function hButtonBar($mode) {
      $buttons =array();
      
      // First work out which are active
      $b_new   = true;
      $b_save  = $mode=='browse' || $mode=='search' ? false : true;
      $b_search= (count(ContextGet('drilldown',array()))>0) ? false: true;
      $b_clear = $mode=='search' ? true  : false;
      $b_reset = $mode=='browse' ? false : true;
      $b_browse= $mode=='browse' || $mode=='search' ? false : true;
      $b_delete= $mode=='upd'    ? true  : false;
      
      // Now add security into the definition of the active buttons
      $b_search = $b_search && ddUserPerm($this->table_id,'sel');
      $b_new    = $b_new    && ddUserPerm($this->table_id,'ins');
      $b_save   = $b_save   && ddUserPerm($this->table_id,'upd');
      $b_delete = $b_delete && ddUserPerm($this->table_id,'del');

      // Now create an array out of them
      $buts[]=$this->hButton($b_new   ,"\New Entry","ins"   ,'newentry');
      if($mode=='search') 
         $buts[]=$this->hButton($b_search,"\Lookup"   ,"browse",'search');
      else
         $buts[]=$this->hButton($b_search,"\Lookup"   ,"search",'search');
      $buts[]=$this->hButton($b_browse,"\Browse"   ,"browse",'browse');
      if($mode=='ins') 
         $buts[]=$this->hButton($b_save  ,"\Save"     ,"save"  ,'save');
      else 
         $buts[]=$this->hButton($b_save  ,"\Save"     ,"saveupd",'save');
      $buts[]=$this->hButton($b_clear ,"\Clear"    ,"clear" ,'clear');
      $buts[]=$this->hButton($b_reset ,"\Reset"    ,"reset" ,'reset');
      $buts[]=$this->hButton($b_delete,"Delete"   ,"delete",'delete');
      //$buts[]=$this->hButton($b_new,"Import"   ,'import','');
      
      // Two different bits of HTML based on which path
      if($this->button_images) {
         // UTTERLY DEPRECATED SINCE LIKE, 2005 OR SO.  
         // DESTINED TO BE REMOVED.
         $this->h['ButtonBar']= "\n".hTable(100)."<tr>\n"
            .implode("\n",$buts)
            ."\n</tr></table>";
         
      }
      else {
         if(vgfget('buttons_in_commands',false)) {
            $this->h['ButtonBar']='';
            vgfSet('html_buttonbar'
               ,implode("&nbsp;|&nbsp;",$buts)
            );
         }
         else {
            $this->h['ButtonBar']
               ="<tr>"
               ."<td height=\"40\" valign=top>"
               ."\n<div class=\"x2menubar\">\n"
               .implode("\n&nbsp;&nbsp;",$buts)
               ."\n</div>";
         }
      }
   }

   /**
   name:hButton
   parm:flag Enabled
   parm:string Caption
   parm:string Action
   parm:string image_name (defunct)
   
   Creates a hyperlink to the specified form action.  By its nature
   this routine is loaded with exception code.
   */
   function hButton($switch,$caption,$action,$img) {
      // KFD 5/17/07, give it a name so it can be clicked in code
      $name=str_replace(' ','',$caption);
      $name="but_".strtolower(str_replace('\\','',$name));
      $name='name="'.$name.'" id="'.$name.'"';
      

      // if the button is disabled, we exit early, so do that first
      list($caption,$akey)=FindAccessKey($caption);
      if(!$switch) {
         if ($this->button_images) {
            return "<td><img src=\"images/$img-gray.png\"></td>"; 
         }
         else {
            // the "import" button should not be there at all if disabled
            if($caption=='Import') {
               return '';
            }
            else {
               return "<span>$caption</span>";
            }
         }
      }
      
      // If we are still continuing, then this is a "lit" active
      // button.  First work out the link
      switch($action) {
         case 'import':
            $hlink="?gp_page=x_import&gp_table_id=".$this->table_id;
            break;
         case 'save':
            $hlink="javascript:SetAndPost('gp_action','save')";
            break;
         case 'saveupd':
            $hlink="javascript:SetAction('gp_skey','".$this->row['skey']."'"
               .",'gp_action','save')";
            break;
         case 'reset':
            //$hlink="javascript:ob('Form1').reset()";
            $hlink="javascript:fieldsReset()";
            break;
         case 'clear':
            $hlink="javascript:clearBoxes()";
            break;
         case 'delete':
            //$skey=gp('gp_skey');
            //$name = 'gp_delskey_'.$this->table_id;
            //$hlink="SetAndPost('".$name."',".$skey.')';
            $hlink="javascript:SetAndPost('gp_action','del')";
            //regHidden($name,'');
            break;
         default:
            //$qstring = "gp_mode=".urlencode($action);
            $hlink="javascript:SetAndPost('gp_mode','$action')";
      }
      
      // If a text-button, we will now just make a link.  But if its
      // an image we'll slip in the image instead of a text caption
      $ho=$hc="";
      if(!$this->button_images) {
         // make accesskey from caption
         //list($caption,$akey)=FindAccessKey($caption);
      }
      else {
         // Make image
         $ho="<td>";
         $hc="</td>";
         $caption 
            ="<img src=\"images/".$img.".png\" " 
            ." onmouseover=\"this.src='images/$img-over.png'\" "
            ."  onmouseout=\"this.src='images/$img.png'\" "
            ."  alt='$caption' border=0>";
      }
      return "$ho<a $akey $name href=\"$hlink\" onclick=\"$hlink\">$caption</a>$hc";
   }


   // -----------------------------------------------------------
   // -----------------------------------------------------------
   // -----------------------------------------------------------
   // Eighth (or so) display out of 2: Movers
   // -----------------------------------------------------------
   // -----------------------------------------------------------
   // -----------------------------------------------------------
   function hMover() {
      // Get the parent table, and the "left" side, which is us 
      $dd = ContextGet('drilldown',array());
      $tpar = $dd[0]['page'];
      $tleft= $this->table_id;
      
      // The right side we *assume* is the other parent table
      // of us that is not the drilldown source.  Get it?  It breaks
      // of course if this table has more than one parent
      $tables = array_keys($this->table['fk_parents']);
      unset($tables[$tpar]);
      $tright=array_pop($tables);  // if > 1 parent, this won't work
      $dd_right=dd_tableref($tright);

      // Get match expression for left-hand side
      $matches = $dd[0]['parent'];
      $pmatch  = '';
      foreach($matches as $key=>$value) {
          $ileft  = $key;
          $imatch = SQLFC($value);
         $pmatch .= $key."='".$value."'";
      }
      
      // Do an insert if coming through as ajax
      $sqins='';
      if(gpExists('moveradd') ) {
          if(gp('moveradd')<>'0') {
             $row1=array($dd_right['pks']=>gp('moveradd'));
             $row2=$dd[0]['parent'];
             $rowi=array_merge($row1,$row2);
             SQLX_Insert($this->table_id,$rowi);
          }
          else {
              $tab  = $this->table_id;
              $cols = $this->table['pks'];
              $colr = $dd_right['pks'];
              $sqins="insert into $tab
                   ( $cols ) 
                  SELECT $imatch, $colr 
                    FROM $tright
                  WHERE NOT EXISTS (
                    SELECT * FROM $tleft
                     WHERE $tleft.$key = $imatch
                       AND $tleft.$colr = $tright.$colr)";
              SQL($sqins);
          }
      }
      
      // Do a delete if coming through as ajax
      $sqldel='hi';
      if(gpExists('moverdel')) {
          $sqldel = 'moverdel exists';
          if(gp('moverdel')<>'0') {
             $sqldel=
                "delete from "
                .ddTable_idResolve($this->table_id)
                ." where skey=".SQLFN(gp('moverdel'));
             //echo "echo|$sq";
          }
          else {
             $sqldel=
                "delete from "
                .ddTable_idResolve($this->table_id)
                ." WHERE ".$pmatch;
          }
          SQL($sqldel);
      }


      // Pull the source table, the right-hand side
      $sq="SELECT ".$dd_right['pks']." as pk
                 ,description
             FROM ".ddTable_idResolve($tright)."
            WHERE description <> ''
            ORDER BY ".$dd_right['pks'];
      $rows_right=sql_allrows($sq,'pk');

      $sq="SELECT ".$dd_right['pks']." as pk,skey
             FROM ".ddTable_IDResolve($this->table_id)."
            WHERE $pmatch";
      $rows_left=sql_allrows($sq,'pk');

      // Convert the left hand side into options
      $ahl=array();
      foreach($rows_left as $row) {
         if(isset($rows_right[trim($row['pk'])])) {
            $ahl[]="<OPTION "
               .' VALUE="'.$row['skey'].'"'
               .'>'.$rows_right[trim($row['pk'])]['description']
               .'</option>';
         }
      }
      
      // Convert the right hand side into options
      $ahr=array();
      foreach($rows_right as $row) {
         if(!isset($rows_left[trim($row['pk'])])) {
            $ahr[]="<OPTION "
               .' VALUE="'.$row['pk'].'"'
               .'>'.$row['description']
               .'</option>';
         }
      }
      
      ob_start();
      ?>
      <table width=100%>
        <tr>
        <td>
           <b>Selected Values</b><br/><br/> 
           <select size=20 style="width: 250px"
              onclick="formPostAjax('&gp_xajax=1&moverdel='+this.value)"
              >
           <?=implode("\n",$ahl)?>
           </select>
        <td style="padding:10px; vertical-align: top">
           <br/>
           <br/>
           <button onclick="formPostAjax('&gp_xajax=1&moveradd=0')"
                 >&lt;&lt; All</button>
           <br/>
           <br/>
           <button onclick="formPostAjax('&gp_xajax=1&moverdel=0')"
                 >All &gt;&gt;</button>
        <td>
           <b>Available Values</b><br/><br/> 
           <select size=20 style="width: 250px"
              onclick="formPostAjax('&gp_xajax=1&moveradd='+this.value)"
              >
           <?=implode("\n",$ahr)?>
           </select>
      </table>
      <?php
      $this->h['Content']=ob_get_clean();
      
      if(gpexists('gp_xajax')) {
         echo 'mover|'.$this->h['Content'];
         if(errors()) {
            echo "|-|echo|".asErrors();
         }
         exit;
      }
      else {
         $this->h['Content']
            ='<div id="mover">'
            .$this->h['Content']
            .'</div>';
      }
   }
   
   // -----------------------------------------------------------
   // -----------------------------------------------------------
   // -----------------------------------------------------------
   // One of two big displays.  This is the browse display
   // that shows search results
   // -----------------------------------------------------------
   // -----------------------------------------------------------
   // -----------------------------------------------------------
   function hBrowse($filters=array()) {
      // Pull the rows so we know how many we have
      if(count($filters)<>0) {
         $filters=ConSet('table',$this->table_id,'search',$filters);
      }
      $rows=rowsFromUserSearch($this->table,$this->projections['_uisearch']);
      
      // Pull the nav bar.  Do this after pulling rows so we
      // know how many rows there are, what page we're on, etc.
      $this->h['NavBar']=$this->hBrowse_NavBar();
      
      ob_start();
      $this->ehBrowse_Data($rows);
      // KFD 8/9/07 DUPECHECK.  If they did a dupe check before entering
      //            a new row, give them a button to say they want to do
      //            a new one anyway
      if(gp('gp_action')=='dupecheck') {
         $href="?gp_page=".$this->table_id."&gp_mode=ins&gp_nodupecheck=1";
         ?>
         <script>
         function keypress_f9() {
            window.location="<?=$href?>";
         }
         </script>
         <br/><br/>
         <a href="<?=$href?>"
           onclick="window.location='<?=$href?>'"
              id="object_for_f9"
            name="object_for_f9">(F9) Do New Entry</a>
         <?php
      }
      $this->h['Content']=ob_get_clean();
   }

   // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
   function ehBrowse_Data(&$rows) {
      // Generate the table header as sortable columns
      echo "\n";
      $cols=asliceValsFromKeys(
         $this->table['flat']
         ,'description'
        ,$this->projections['_uisearch']
      );
      echo hTableSortable($this->table_id,$cols);

      // Retrieve the rows
      if (count($rows)>0) {
         vgfSet('HTML_focus','browse_row0');
         $hrows=array();
         $j=0;
         foreach($rows as $row) {
            $j++;
            $i=0;
            $newrow=array();
            foreach($cols as $colname=>$coldesc) {
               if($i<>0) {
                  switch($this->table['flat'][$colname]['type_id']) {
                     case 'time':
                        $newrow[]=hTime($row[$colname]);
                        break;
                     case 'date':
                        if(is_null($row[$colname])) {
                           $newrow[]='';
                        }
                        else {
                           $newrow[]=hDate(strtotime($row[$colname]));
                        }
                        //$newrow[]=$row[$colname];
                        break;
                     default:
                        $newrow[]=$row[$colname];
                  }
               }
               else {
                  $hName=$j==1 ? ' id="browse_row0" ' : '' ;
                  $js 
                     ="SetAction('gp_page','".$this->table_id."'"
                     .",'gp_skey','".$row['skey']."')";
                  $value='<a tabindex='.$j.$hName.' '
                     .'href="javascript:'.$js.'">'.$row[$colname].'</a>';
                  $newrow[]=$value;
                  $i=1;
               }
            }
            $hrows[]=$newrow;
         }
         echo hTBodyFromRows('dlite',$hrows);
      }
      else {
         echo "\n<tr><td colspan=99 class='dlite'>"
            ."<b>There are no records to display</b>"
            ."</td></tr>\n";
      }

      // Wrap up by closing the table.
      echo "\n<tr><td colspan=99 class='dhead'>&nbsp;</td></tr>";      
      echo "\n</table>";
   }
   
   // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
   function hBrowse_NavBar() {
      // Get vital stats, work out what the max page is
      $table_id = $this->table['table_id'];
      $var='gp_spage_'.$table_id;
      regHidden($var,'');
      list($spage,$srows,$rppage,$maxpage)=arrPageInfo($table_id);
      
      if ($srows==0) { return ''; }
      
      // Work out the "Page x of y" text
      $hcenter
         ='Page '
         .($srows==0 ? '0' : $spage)
         ." of $maxpage";
     
      // Work out if the First/Prev links are enabled
      $lprev = $spage==1 ? false : true; 
      $lnext = $spage==$maxpage ? false : true;
      
      //if(!($lprev || $lnext)) return '';

         
      // Two different bits of HTML based on which path
      if($this->button_images) {
         $hprev
            =hLinkImage('first','First',$var,0,$lprev)
            ."&nbsp;"
            .hLinkImage('previous','Prev',$var,1,$lprev);
         $hnext
            =hLinkImage('next','Next',$var,2,$lnext)
            ."&nbsp;"
            .hLinkImage('last','Last',$var,3,$lnext);
         return "\n".hTable(100)."<tr>"
            ."\n<td align=left   width=39%>$hprev</td>"
            ."\n<td align=center width=22%>$hcenter</td>"
            ."\n<td align=right  width=39%>$hnext</td>"
            ."\n</tr></table>";
      }
      else {
         $hprev=
            $this->hTextButton('\First',$var,0,$lprev)
            .$this->hTextButton('\Previous',$var,1,$lprev);
         $hnext=
            $this->hTextButton('Ne\xt',$var,2,$lnext)
            .$this->hTextButton('Las\t',$var,3,$lnext);
         if(vgfget('buttons_in_commands')) {
            vgfSet('html_navbar'
               ,'<span style="border:1px solid gray; padding: 2px">'
               .$hprev.$hcenter."&nbsp;&nbsp;&nbsp;".$hnext
               .'</span>'
            );
            return '';
         }
         else {
            return "\n<div class=\"x2menubar\">\n"
               .$hprev.$hcenter."&nbsp;&nbsp;&nbsp;".$hnext
               ."\n</div>";
         }
      }
         
         
      // Return the assembled HTML fragment
   }
   
   function hTextButton($caption,$var,$val,$enabled) {
      list($caption,$akey)=FindAccessKey($caption);
      if($enabled) {
         return "<a $akey href=\"javascript:formPostString('"
            .$var."=".urlencode($val)
            ."')\">".$caption."</a>&nbsp;&nbsp;&nbsp;";
      }
      else {
         return "<span>$caption</span>&nbsp;&nbsp;&nbsp;";
      }
      
   }
   
   // -----------------------------------------------------------
   // -----------------------------------------------------------
   // -----------------------------------------------------------
   // Fifth of two big displays, the child table as browse
   // -----------------------------------------------------------
   // -----------------------------------------------------------
   // -----------------------------------------------------------
   function hBrowseChild($filters=array()) {
      // Pull the rows so we know how many we have
      if(count($filters)<>0) {
         $filters=ConSet('table',$this->table_id,'search',$filters);
         processPost_TableSearchResultsClear($this->table_id);
      }
      $rows=rowsFromUserSearch($this->table,$this->projections['_uisearch']);
      
      // Pull the nav bar.  Do this after pulling rows so we
      // know how many rows there are, what page we're on, etc.
      $hNavBar=$this->hBrowse_NavBar();

      // Generate the table header
      $cols1=array('Edit');
      $cols2=asliceValsFromKeys(
         $this->table['flat']
         ,'description'
        ,$this->projections['_uisearch']
      );
      $cols=array_merge($cols1,$cols2);

      if(count($rows)==0) {
         $hContent="<tr><td colspan=99 class=\"dlite\">"
            ."<b>There are no records to display</b>"
            ."</td></tr>\n";
      }
      else {
         // Convert first column to hyperlink to that page/row
         $hContent='';
         foreach($rows as $index=>$row) {
            // The edit button
            $slipin=hLinkPostFromArray('','Edit',
               array('gp_dd_page'=>$this->table_id
                  ,'gp_skey'=>$row['skey']
                  ,'gp_mode'=>'upd'
               )
            );
            $slipin=array('_edit'=>$slipin);
            if(isset($row['skey'])) unset($rows[$index]['skey']);
            $rows[$index]=array_merge($slipin,$rows[$index]);
         }
         $hContent = hTBodyFromRows('',$rows);
      }
      
      $hNew=hLinkPostFromArray('','New Entry'
         ,array('gp_dd_page'=>$this->table_id
            ,'gp_mode'=>'ins'
         )
      );
      $hDsc="<span class=\"x2menubar_text\">"
         .$this->table['description']
         .'</span>';
      ob_start();
      ?>
      <br>
      <div class="x2menubar" style="text-align: left">
        <?=$hDsc.'&nbsp;'.$hNew?>
      </div>
      <table cellpadding=0 cellspacing=0 width=100%>
        <?=hTRFromArray('dhead',$cols)?>
        <?=$hContent?>
        <tr>
            <td colspan=99 class='dhead'>&nbsp;
            </td>
        </tr>
      </table>
      <?php
      return ob_get_clean();
   }
   
   
   function hDisplayOnScreenOverride($rows,$filters,$parent_row) {
      $NEVERUSED=$rows;
      $NEVERUSED=$filters;
      $NEVERUSED=$parent_row;
      return '';
   }
   
   function hDisplayOnscreen($filters=array(),&$parent_row) {
      $parent_pks=$filters; // capture for later reference
      
      //hprint_r($filters);
      // Pull the rows so we know how many we have
      if(count($filters)<>0) {
         //$filters=ConSet('table',$this->table_id,'search',$filters);
         processPost_TableSearchResultsClear($this->table_id);
      }
      $rows=rowsFromUserSearch($this->table,$this->projections['_uisearch'],$filters);

      $early_return=$this->hDisplayOnscreenOverride($rows,$filters,$parent_row);
      if($early_return<>'') return $early_return;
      
      // Pull the nav bar.  Do this after pulling rows so we
      // know how many rows there are, what page we're on, etc.
      $hNavBar=$this->hBrowse_NavBar();

      // Generate the table header
      $cols1=asliceValsFromKeys(
         $this->table['flat']
         ,'description'
        ,$this->projections['_uisearch']
      );
      $cols2=array('Edit');
      if(DDUserPerm($this->table_id,'del')) {
         $cols2[]='Delete';
      }
      $cols=array_merge($cols1,$cols2);

      if(count($rows)==0) {
         $hContent="<tr><td colspan=99 class=\"dlite\">"
            ."<b>There are no records to display</b>"
            ."</td></tr>\n";
      }
      else {
         // Convert last column to hyperlink to that page/row
         foreach($rows as $index=>$row) {
            $slipin1=hLinkPostFromArray('','Edit',
               array('gp_dd_page'=>$this->table_id
                  ,'gp_skey'=>$row['skey']
                  ,'gp_mode'=>'upd'
               )
            );
            //$slipin1=array('_edit'=>$slipin);
            $slipin2 = '';
            if(DDUserPerm($this->table_id,'del')) {
               $slipin2=hLinkPostFromArray('','Delete'
                  ,array(
                     'gp_delskey_'.$this->table_id=>$row['skey']
                     ,'gp_mode'=>'upd'
                     ,'gp_skey'=>gp('gp_skey')
                  )
               );
            }
            //$slipin=array('_del'=>$slipin);
                        
            // Get the formatted value
            foreach($row as $colname=>$colvalue) {
               $value=hFormat($this->table['flat'][$colname]['type_id'],$colvalue);
               $rows[$index][$colname]=$value;
            }
            if(isset($row['skey'])) unset($rows[$index]['skey']);
            $rows[$index][]=$slipin1;
            $rows[$index][]=$slipin2;
            //$rows[$index]=array_merge($rows[$index],$slipin);

         }
         $hContent = hTBodyFromRows('',$rows);
      }
      // Add an empty row for inserting
      $empty_row  = '';
      $table_id   = $this->table['table_id'];
      /*
      foreach($cols1 as $column=>$description){
         $value = '';
         $name_id = 'gp_onscreen_'.$table_id.'_'.$column.'';
         $iname   = 'name="'.$name_id.'" '.'id="'.$name_id.'"';
         $value   = '<input type="text" '.$iname.'></input>';
         $empty_row  .= hTD('',$value,'');
      }
      */
      $empty_row  = '';
      $parent_skey   = $parent_row['skey'];
      $pkcols=explode(',',$this->table['pks']);
      
      // KFD 1/11/07, unconditionally copied in all values supplied
      // from parent.
      foreach($pkcols as $pkcol) {
         if(!isset($parent_row[$pkcol])) continue;
         $prefilled[$pkcol]=$parent_row[$pkcol];
      }
      $prefilled=$parent_pks;
      //hprint_r($prefilled);
      $opts = array( 'drilldownmatches'   => $prefilled
                    ,'name_prefix'        => 'gp_onscreen_'.$table_id.'_'
                    ,'hpsize'=>12
      );
                    
      // KFD 5/29/07, refactoring code to use new widget generation
      //   routines, no more ahInputsComprehensive.
      //$inputs = ahInputsComprehensive($this->table,'ins',$prefilled,'_uisearch',$opts);
      //foreach($inputs as $column=>$details){
      //   $empty_row  .= hTD('',$details['html'],'');
      //}
      $acols=aColsModeProj($this->table,'ins','_uisearch');
      $ahcols=aHColsfromACols($acols,$prefilled);
      $name_prefix='gp_onscreen_'.$table_id.'_';
      $xh=hDetailFromAHCols(
         $ahcols,$name_prefix,500,'tds'
      );
      $xh=jsValues($ahcols,$name_prefix,$prefilled,$xh);
      $empty_row.=$xh;
      // END OF CHANGES 5/29/07 code refactoring
      
      
      //hprint_r(htmlentities($empty_row));
      // Add a save link
      hidden('gp_child_onscreen','');
      $name_id = 'onscreen_save';
      $iname   = 'name="'.$name_id.'" '.'id="'.$name_id.'"';
      $value   = '<a tabindex="'.hpTabIndexNext(500).'" href="'."javascript:SetAction('gp_skey',$parent_skey,'gp_child_onscreen','$table_id')".'">Save</a>';
      $empty_row  .= hTD('',$value,'');
      // Add a <tr></tr>
      $empty_row  = '<tr>'.$empty_row.'</tr>';
      // Attatch the empty row
      $hContent .= $empty_row;
      
      $hNew=hLinkPostFromArray(''
         ,$this->table['description']
         ,array('gp_dd_page'=>$this->table_id)
      );
      //$hDsc="<span class=\"x2menubar_text\">"
      //   .$this->table['description']
      //   .'</span>';
      ob_start();
      ?>
      <br>
      <br>
      <div class="x2menubar" style="text-align: left">
        <?=$hNew?>
      </div>
      <div class="andro_space2"></div>
      <table cellpadding="0" cellspacing="0" width="100%" class="x3detail">
        <?=hTRFromArray('dhead',$cols)?>
        <?=$hContent?>
        <tr>
            <td colspan=99 class='dhead'>&nbsp;
            </td>
        </tr>
      </table>
      <?php
      return ob_get_clean();
   }
   
   
   // -----------------------------------------------------------
   // -----------------------------------------------------------
   // -----------------------------------------------------------
   // Other of the two big displays, show the boxes.
   // -----------------------------------------------------------
   // -----------------------------------------------------------
   // -----------------------------------------------------------
   function hBoxes($mode) {
      // Obtain a row depending on the mode we are in.  If there
      // was an error then reload the first row for this table
      if(is_array(vgfGet('ErrorRow_'.$this->table_id,''))) {
         $row=vgfGet('ErrorRow_'.$this->table_id);
         $row['skey']=gp('gpx_skey');
      }
      else {
         switch($mode) {
            case 'search':
               // if a previous search, use that, else fall through
               // to using current row
               $row=ConGet('table',$this->table_id,'search',array());
               if(count($row)<>0) break; 
            case 'ins':
               $row=DrillDownMatches();
               if(count($row)==0) {
                  $row=aFromGP('pre_');
               }
               // KFD 8/13/07, part of COPY ability
               if(gp('gp_action')=='copy') {
                  $row2=SQL_OneRow(
                     "SELECT * FROM ".$this->table_id
                     ." where skey=".SQLFN(gp('gp_skey'))
                  );
                  foreach($row2 as $column_id=>$colvalue) {
                     if(is_numeric($column_id)) continue;
                     if(!isset($this->table['flat'][$column_id])) continue;
                     $aid=$this->table['flat'][$column_id]['automation_id'];
                     if($aid=='SEQUENCE' || $column_id==gp('gp_exclude')) { 
                        unset($row2[$column_id]);
                     }
                  }
                  $row=array_merge($row,$row2);
               }
               break;
            case 'upd': {
               $skey=gp('gp_skey');
               regHidden('gp_skey','');
               if(trim($skey)=='') {
                  $row=array();
               }
               else {
                  $skey=" WHERE skey=".$skey;
                  $sq="Select * FROM ".$this->view_id.$skey;
                  $row=SQL_OneRow($sq);
               }
            }
         }
      }
      
      // Save the row for other routines
      $this->row = $row;
      
  		// Find out what skey we are on, give a next/prev
		// kind of button for stuff like that.
		// Set the next/prev stuff based on rows
		$HTML_PagePrev = $HTML_PageNext = $HTML_ViewingPage = "";
		if ($mode=="upd") {
         $lprev=$lnext=false;
         $skey = $this->row['skey'];
			$sess_skeys = ConGet("table",$this->table_id,"skeys",array());
         if(count($sess_skeys)>1) {
            $sess_srows = array_flip($sess_skeys);
            $sess_srow  = $sess_srows[$row['skey']];
            $lprev = $sess_srow==0 ? false : true;
            $skeyf= $sess_srow==0 ? 0 : $sess_skeys[0]; 
            $skeyp= $sess_srow==0 ? 0 : $sess_skeys[$sess_srow-1];
            $skeyn= $sess_srow>=(count($sess_srows)-1)
               ? 0 : $sess_skeys[$sess_srow+1]; 
            $skeyl= $sess_srow>=(count($sess_srows)-1) 
               ? 0 : $sess_skeys[count($sess_srows)-1]; 
            $hprev
               =hLinkImage('first','First','gp_skey',$skeyf,$lprev)
               ."&nbsp;"
               .hLinkImage('previous','Previous','gp_skey',$skeyp,$lprev);
            $lnext = $sess_srow < (count($sess_srows)-1) ? true : false; 
            $hnext
               =hLinkImage('next','Next','gp_skey',$skeyn,$lnext)
               ."&nbsp;"
               .hLinkImage('last','Last','gp_skey',$skeyl,$lnext);
            $HTML_ViewingPage = "Page ".($sess_srow+1)." of ".(count($sess_srows));
         }
      }

      // Output and save the navbar
      ob_start();
      if ($HTML_ViewingPage<>'') {
         $hprev=
            $this->hTextButton('\First','gp_skey',$skeyf,$lprev)
            .$this->hTextButton('\Previous','gp_skey',$skeyp,$lprev);
         $hnext=
            $this->hTextButton('Ne\xt','gp_skey',$skeyn,$lnext)
            .$this->hTextButton('Las\t','gp_skey',$skeyl,$lnext);
         if(vgfget('buttons_in_commands')) {
            $this->h['NavBar']='';
            vgfSet('html_navbar'
               ,'<span style="border:1px solid gray; padding: 2px">'
               .$hprev.$HTML_ViewingPage."&nbsp;&nbsp;&nbsp;".$hnext
               .'</span>'
            );
         }
         else {
            $this->h['NavBar']
               ="\n<div class=\"x2menubar\">\n"
               .$hprev.$HTML_ViewingPage."&nbsp;&nbsp;&nbsp;".$hnext
               ."\n</div><br>";
         }
      }

      // Second output is main content      
      // KFD 8/9/07, Project DUPECHECK
      //             If a "dupecheck" projection exists, and they are
      //             doing a new entry, we first ask them to enter
      //             those values
      $dc=ArraySafe($this->table['projections'],'dupecheck');
      if($dc !='' && $mode=='ins' && !gpExists('gp_nodupecheck')) {
         hidden('gp_action','dupecheck');
         $this->h['Content']=$this->hBoxesX3($mode,'dupecheck');
         $this->h['Content']
            .='<br/><br/>'
            .'<button id="object_for_enter" onclick="formSubmit()">(ENTER) Check For Duplicates</button>';
      }
      else {
         $this->h['Content']=$this->hBoxesDefault($mode);
      }
   }
   
   function hBoxesDefault($mode) {
      ContextSet('OldRow',$this->row);

      // KFD 5/27/07, This line replaces the commented line 
      //    below that used ahInputsComprehensive().  We now
      //    want to allow more complex arrangements, and 
      //    eventually do ui.yaml and stuff.
      // 
      //    In any event, this remains a two-line routine
      return $this->hBoxesX3($mode);
      //return $this->hBoxesFromProjection($mode); 
   }

   function hBoxesX3($mode,$projection='',$title='') {
      $acols=aColsModeProj($this->table,$mode,$projection);
      $ahcols=aHColsfromACols($acols);
      $title=$title ? '<h3 style="padding:5px; margin:0px">'.$title.'</h3>' : $title;
      $xh=hDetailFromAHCols($ahcols,'x2t_',500);
      $xh=jsValues($ahcols,'x2t_',$this->row,$xh);
      //return 
      //   '<div class="x2fieldset" style="padding: 5px">'
      //   .$title
      //   .$xh
      //   .'</div>';
      return $title.$xh;
   }

   function hBoxesFromProjection($mode,$proj='') { 
      // Obtain the HTML for the inputs and output as table
      $ahc= ahInputsComprehensive($this->table,$mode,$this->row,$proj);
      return 
         "\n<center>".hTable(100)
         .$this->hBoxesFromAHComprehensive($ahc,$mode)      
         ."\n</table></center>";
   }
   
   function ehProjection($mode,$proj,$title='',$opts=array()) {
      $NEVERUSED=$title;
      $ahc= ahInputsComprehensive($this->table,$mode,$this->row,$proj,$opts);
      ?>
      <div class="x2fieldset" style="margin-bottom: 2px;">
      <h4>&nbsp;&nbsp;<?=$title?></h4>
      <center>
         <table height="100%">
         <?=$this->hBoxesFromAHComprehensive($ahc,$mode);?>
         </table>
      </center>
      </div>
      <?php
   }
   
   function ehProjectionTD($mode,$proj,$title='',$opts=array()) {
      $NEVERUSED=$title;
      $ahc= ahInputsComprehensive($this->table,$mode,$this->row,$proj,$opts);
      ?>
      <td class="x2fieldset">
      <div style="margin-bottom: 2px;">
      <h4>&nbsp;&nbsp;<?=$title?></h4>
      <center>
         <table height="100%">
         <?=$this->hBoxesFromAHComprehensive($ahc);?>
         </table>
      </center>
      </div>
      <?php
   }

   
   function hBoxesFromAhComprehensive($ahc,$mode='upd') {
      $HFirst='';
      ob_start();
      $count=0;
      foreach($ahc as $colname=>$colinfo) {
         $count++;
         $extra=$colinfo['input']=='textarea' ? 'style="vertical-align:top; padding-top:2px"' : '';
         if($colinfo['writeable'] && $HFirst=='') 
            $HFirst=$colinfo['parms']['name'];
         if(!isset($this->table['flat'][$colname])) {
            echo "Reference non-existent column: ".$colname;
            exit;
         }
         $desc = $this->table['flat'][$colname]['description'];
         $hr=$colinfo['hright']=='' ? '' : '&nbsp;&nbsp;'.$colinfo['hright'];
         echo "\n<tr>";
         echo "\n".hTD('inp-caption',$desc,'id="inp-caption" '.$extra);
         echo "\n".hTD('inp-input'  ,$colinfo['html'].$hr);
         if($count==1 && $mode=='search') {
            ?>
            <td rowspan="99" 
                  style="border: 1px solid #606060;
                        width: 33%;
                        padding: 0px 8px;
                        background-color: #E0FFE0">
            <h3>Lookup Mode</h3>
            Fill in as many boxes as you like.  Hit
            ENTER or ALT-L to execute the lookup.
            <br/><br/>
            Put a "%" sign anywhere to act as a wildcard.
            <br/><br/>
            Use "&gt;" for greater than, as in "&gt;Jones"
            or "&gt;5/1/07" or "&gt;200".  The "&lt;" works
            the same way.
            <br/><br/>
            Use a dash for ranges as in "100-200" or
            "5/1/07-6/31/07".
            <br/><br/>
            Use a comma for lists of exact values, as in
            "5/1/07,3/31/06" or "Jones,Smith"
            <br/><br/>
            You can also do combinations, such as "&lt;2,6,7-10,&gt;15"
            <br/><br/>
            All searches are case-insensitive.
            
            </td>
            <?php
         }
         echo "\n</tr>";
      }
      vgfSet('HTML_focus',$HFirst);
      return ob_get_clean();
   }

   // -----------------------------------------------------------
   // -----------------------------------------------------------
   // Generate any 'extra' links, which might be:
   // 1) drilldowns, 2) drillbacks, 3) other
   // -----------------------------------------------------------
   // -----------------------------------------------------------
   function hLinks($mode) {
      // maybe routine was disabled by setting a flag
      if($this->hlinks_disable==true)   return '';
      if($this->hlinks_display=='none') return '';
      if($this->hlinks_display=='')     return '';
      
      $aDD=$this->aLinks_DrillDown($mode);
      $aDB=$this->aLinks_DrillBack($mode);
      $aEX=$this->aLinks_Extra($mode);
      
      if(count($aDD)+count($aDB)+count($aEX)==0) return '';
      
      if($this->hlinks_display=='vertical') {
         $hDD=implode("<br>",$aDD);
         if($hDD<>'') $hDD='More Detail:<br><br>'.$hDD;
         $hDB=implode("<br>",$aDB);
         if($hDB<>'') $hDB='Go Back:<br><br>'.$hDB;
         $hEX=implode("<br>",$aEX);
         if($hEX<>'') $hEX='Other Links:<br><br>'.$hEX;
         
         ob_start();
         ?>
         <table cellpadding=0 cellspacing=0 width=100% class="x2_drilldown"> 
            <tr><td style="width: 33%; padding: 5px; vertical-align: top;"><?=$hDD?></td>
                <td style="width: 33%; padding: 5px; vertical-align: top;"><?=$hDB?></td>
                <td style="width: 33%; padding: 5px; vertical-align: top;"><?=$hEX?></td>
            </tr>
         </table>
         <?php
         $this->h['Links']=ob_get_clean();
      }
      else {
         $hDD=implode(", ",$aDD);
         if($hDD<>'') $hDD='More Detail: '.$hDD."<br/>";
         $hDB=implode(", ",$aDB);
         if($hDB<>'') $hDB='Go Back: '.$hDB."<br/>";
         $hEX=implode(", ",$aEX);
         if($hEX<>'') $hEX='Other Links: '.$hEX."<br/>";
         
         ob_start();
         ?>
         <table cellpadding=0 cellspacing=0 width=100% class="x2_drilldown"> 
            <tr><td style="padding: 5px; text-align: left;">
               <?=$hDD?>
               <?=$hDB?>
               <?=$hEX?>
            </tr>
         </table>
         <?php
         $this->h['Links']=ob_get_clean();
      }
   }

   // Generate drilldown links for any child table   
   function aLinks_DrillDown($mode) {
      if($mode<>'upd') return array();
      if($this->table_id_parent<>'') return array();
      $retval=array();
      
      // Get the pks into a string value, do the loop so we can trim
      $lpks = $this->table['pks'];
      $apks = explode(",",$lpks);
      $lpkvals='';
      foreach ($apks as $colname) {
         $lpkvals.=ListDelim($lpkvals).trim($this->row[$colname]);
      }

      $dd=array('drilldown','mover');
      foreach($this->children as $table_id=>$tabinfo) {
         if(!in_array($tabinfo['display'],$dd)) continue;
         $tabdesc=DD_TableProperty($table_id,'description');
         $h=hLinkPostFromArray('',$tabdesc,array('gp_dd_page'=>$table_id));
         //$js = "drillDown('$table_id','$lpks','$lpkvals')";
         $retval[]=$h;
      }
      return $retval;
   }

   function aLinks_DrillBack($mode) { 
      if($this->table_id_parent<>'') return array();
      $x=$mode;
		$retval = array();
      
      // First kind of "goback" is drillbacks
      //  THESE ARE X_TABLE drillbacks, from deprecated x_table
      /*
		$gp_dlev = DrilldownLevel();
		for ($x=1;$x<=$gp_dlev;$x++) {
         $lev=$gp_dlev-$x+1;
			$drill = DrilldownGet($x);
         $table_id=$drill['gp']['page'];
         $tabdesc=DD_TableProperty($table_id,'description');
			//$msg = array_values($drill["values"]);
			//$msg = implode("&nbsp;&nbsp;",$msg);
			//$msg = $drill["gp"]["pagedesc"].": $msg<br>";
         $retval[]
            ="<a href=\"javascript:drillBack($lev)\">"
            .$tabdesc."</a>";
			//$HTML=HTMLE_A_JS("drillBack('".($gp_dlev-$x+1)."')",$msg);
			//$retval.='<img src="images/lessdetails.jpg">'.$HTML."<br />\n";
		}
      */
      
      // First kind of "goback" is drillbacks
      // These are x_table2 drillbacks, a little simpler
      $ddstack = ContextGet('drilldown',array());
      $lev  = count($ddstack);
      foreach($ddstack as $dd) {
         $tabdesc=DD_TableProperty($dd['page'],'description');
         $retval[]
            ="<a href=\"javascript:SetAndPost('gp_dd_back',$lev)\">"
            .$tabdesc."</a>";
         $lev--;
      }
		return $retval;
	}

   /**
   name:aLinks_Extra
   return:array
   parm:$mode
   seealso:Methods To Override
   
   This method returns an array of links that should appear in
   the [[link bar]] of an editing page.  It takes the parameter $mode
   which can be used to decide when a link should appear.
   */
   function aLinks_Extra($mode) {
      return array();
      $mode='Fill in your own override code that returns an array';
   }

   // -----------------------------------------------------------
   // -----------------------------------------------------------
   // -----------------------------------------------------------
   // Fourth of two big displays, all extra content below
   // the main body. Currently this means child tables 
   // displayed as browse boxes.
   // -----------------------------------------------------------
   // -----------------------------------------------------------
   // -----------------------------------------------------------
   function hExtra($mode) {
      if($mode<>'upd') return '';

      // Slice out the primary key from the row, use as search
      // filters for child tables.  Also save to be used as
      // constants for inserts into child tables.
      $pks=asliceFromKeys($this->row,$this->table['pks']);
      
      // Now maybe there are children to deal with.  Maybe not.
      $i=0;
      $retval=array();
      foreach($this->extrabrowse as $table_id) {
         $tabinfo=$this->children[$table_id];
         $i++;
            
         $obj_child=DispatchObject($table_id);
         if(isset($this->children[$table_id]['projections']['_uisearch'])) {
            $obj_child->projections['_uisearch']=
               $this->children[$table_id]['projections']['_uisearch'];
         }
         $retval[$table_id]="<br>".$obj_child->hBrowseChild($pks);
      }
      
      // Generate display for all valid child table rows where display
      // is 'onscreen'
      foreach($this->children as $table_id=>$tabinfo) {
         $obj_child=DispatchObject($table_id);
         if($tabinfo['display']<>'onscreen') continue;
         $retval[$table_id]="<br>".$obj_child->hDisplayOnscreen($pks,$this->row);
      }
      
      // Always save values of main table's pk values.  Can be
      foreach($pks as $colname=>$colvalue) {
         reghidden("parent_".$colname,$colvalue);
      }   
      if(isset($this->h['Extra'])) {
         $this->h['Extra'] = array_merge($this->h['Extra'],$retval);
      }
      else {
         $this->h['Extra'] = $retval;
      }
   }

   // -----------------------------------------------------------
   // -----------------------------------------------------------
   // -----------------------------------------------------------
   // Third of two big displays, a search result as an
   // editable grid
   // -----------------------------------------------------------
   // -----------------------------------------------------------
   // -----------------------------------------------------------
   /*ADOCS
   name:ehRowsOfBoxes
   returns: HTML (string)
   parm:filters
   parm:lcols
   
   Experimental code that was used briefly but is now "dead", there are
   no calls to it.  It takes child tables and produces a grid of 
   INPUT controls for direct access, including blank rows for new entries.
   Was used to replace the browse-box listing of child tables, but found
   to introduce lots of confusion because of cases where the grid
   contains only a subset of columns.
   
   Usage: From within any table class, create an object for some child
   table, call this method directly, and then place the resulting HTML
   into the output.
   
   Note the "rowsfromFilters" was rem'd out, but if you revive this
   code that should be unrem'd.  Should also examine the call to that
   routine in raxlib.php, it has been changed since this code was written.
   */
   function ehRowsOfBoxes($filters,$lcols=null) {
      $x=$filters;
      if(is_null($lcols)) {
         $lcols=$this->projections['_uisearch'];
      }
      $acols = explode(',',$lcols);
      //html_vardump($acols);
      
      // This will be used to hold skey value of deletions
      $table_id=$this->table['table_id'];
      $skey_delete="gp_delskey_".$this->table['table_id'];
      regHidden($skey_delete,'');
      
      // Start output with a table and column headers
      ?>
      <br><br>
      <table cellspacing=0 cellpadding=0>
        <tr>
        <td class="inp-br-h1"><?=$this->table['description']?></td>
      <?php
      $aheaders=asliceValsFromKeys($this->table['flat'],'description',$acols);
      foreach ($aheaders as $aheader ) {
         echo "\n<td class=\"inp-br-head\">$aheader</td>";
      }
      echo "</td>";
      //echo "\n".hTRFromArray('void',$aheaders);

      // Pull the data and dump it, put details and delete buttons here
      //$rows=r*owsFromFilters($this->table,$filters,'skey,'.$lcols);
      $rows=array();
      $opts['dispsize']=12;
      foreach($rows as $row) {
         $inputs=ahInputsComprehensive($this->table,'upd',$row,$acols,$opts);
         $skey=$row['skey'];
         echo "\n<tr>";
         //$js="SaveAndPost('$skey_delete','{$row['skey']}')";
         //$js="<a class=\"del\" href=\"javascript:$js\">Delete</a>";
         //echo "<td>$js</td>";
         $js="('gp_dd_page','$table_id','gp_dd_skey','$skey')";
         $js="<a href=\"javascript:SetAction$js\">More..</a>";
         echo "<td align=\"center\" class=\"inp-br-more\">$js</td>";
         foreach($inputs as $input) {
            echo "\n<td class=\"inp-input\">".$input['html']."</td>";
         }
         echo "\n</tr>";
      }

      $newrows=count($rows)==0 ? 3 : 1;
      for($x=1;$x<=$newrows;$x++) {
         // Generate inputs for 1..x new rows  
         //$row=$filters;
         //$row['skey']=-$x;
         $row=array('skey'=>-$x);
         $inputs=ahInputsComprehensive($this->table,'ins',$row,$acols,$opts);
         echo "\n<tr>";
         echo "<td class=\"inp-br-more\">&nbsp;</td>";
         foreach($inputs as $input) {
            echo "\n<td class=\"inp-input\">".$input['html']."</td>";
         }
         echo "<td>&nbsp;</td>";
         echo "\n</tr>";
      }
      echo "</table>";
   }
   
   // ==============================================================
   // SERVER MAP-BACK FUNCTIONS
   //   These are functions that handle events that occur on the
   //   client which are then sent back via ajax calls
   // ==============================================================
   function fwAjax() {
      if(gp('fwajax')=='field_changed') {
         $this->field_changed();
      }
      
      returns_as_ajax();
   }
   
   function field_changed() {
      $field=gp('ajx_field');
      $value=gp('ajx_value');
      
      $method='field_changed_'.$field;
      if(method_exists($this,$method)) {
         $this->$method($value);
      }
   }
   
}
?>
