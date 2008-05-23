<?php
/* ================================================================== *\
   (C) Copyright 2005 by Secure Data Software, Inc.

   Purpose: This the ONE TRUE LIBRARY

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

# ==============================================================
#
# SECTION: GP VARIABLES
#
# ==============================================================
function gp($key,$vardefault='') {
	$post=$GLOBALS["AG"]["clean"];
	if (!isset($post[$key])) return $vardefault;
	else return $post[$key];
}

function gpExists($key) {
	return isset($GLOBALS["AG"]["clean"][$key]);
}

function hgp($key,$default='') {
   $temp=gp($key,$default);
   return htmlentities($temp);
}

function rowFromgp($prefix) {
   return aFromgp($prefix);
}

function removetrailingnewlines($input) {
   while(substr($input,-1,1)=="\n") {
      $input=substr($input,0,strlen($input)-1);
   }
   return $input;
}

/* DEPRECATED  (it was named wrong, should have been rowFromGP */
function aFromgp($prefix) {
	$strlen = strlen($prefix);
	$row = array();
	foreach ($GLOBALS["AG"]["clean"] as $colname=>$colvar) {
		if (substr($colname,0,$strlen)==$prefix) {
         $row[substr($colname,$strlen)] = $colvar;
		}
	}
	return $row;
}


function gpSet($key,$value='') {
	$GLOBALS["AG"]["clean"][$key] = $value;
}

function gpSetFromArray($prefix,$array) {
   foreach($array as $key=>$value) {
      gpSet($prefix.$key,$value);
   }
}

function gpUnSet($key) {
	if (isset($GLOBALS["AG"]["clean"][$key])) {
      unset($GLOBALS["AG"]["clean"][$key]);
   }
}

function gpUnsetPrefix($prefix) {
   foreach($GLOBALS['AG']['clean'] as $key=>$value) {
      if(substr($key,0,strlen($prefix))==$prefix) {
         gpUnset($key);
      }
   }
}

function gpControls() {
   return unserialize(base64_decode(gp('gpControls')));
}


/* DEPRECATED */
function rowFromgpInputs() {
   return afromgp('txt_');
}

/* DEPRECATED */
/*
function rowFromgp($table_id) {
   // First look for gp_skey
   $skey=CleanGet('gp_skey','',false);
   $skey=$skey<>'' ? $skey : Cleanget('txt_skey','',false);
   if($skey<>'') {
      $sq="SELECT * FROM ".$table_id." WHERE skey=".SQL_Format('numb',$skey);
      return SQL_OneRow($sq);
   }

   // no skey?  Look for the primary key, assume one column
   $table=DD_TableRef($table_id);
   $pkcol=$table['pks'];
   $pkval = CleanGet('gp_'.$pkcol,'',false);
   $pkval = $pkval<>'' ? $pkval : CleanGet('txt_'.$pkcol,'',false);
   if($pkval=='') {
      return false;
   }
   $sq="SELECT * FROM ".$table_id." WHERE $pkcol=".SQL_Format('char',$pkval);
   return SQL_OneRow($sq);
}
*/

function gpToSession() {
   SessionSet('clean',$GLOBALS['AG']['clean']);
}

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# DOCUMENTATION LINE
#
# EVERYTHING ABOVE HERE HAS BEEN DOCUMENTED ON THE NEW 2008
# DOCUMENTATION SITE
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

# ==============================================================
#
# SECTION: JSON RETURNS
#
# Add elements to the JSON RETURN ARRAY
# ==============================================================
// KFD X4
function x4Error($parm1) {
    $GLOBALS['AG']['x4']['error'][] = $parm1;
}
function x4Notice($parm1) {
    $GLOBALS['AG']['x4']['notice'][] = $parm1;
}
function x4Debug($parm1) {
    $GLOBALS['AG']['x4']['debug'][] = $parm1;
}
function x4HTML($parm1,$parm2) {
    $GLOBALS['AG']['x4']['html'][$parm1] = $parm2;
}
function x4SCRIPT($parm1) {
    $parm1 = preg_replace("/<script>/i",'',$parm1);
    $parm1 = preg_replace("/<\/script>/i",'',$parm1);
    $GLOBALS['AG']['x4']['script'][] = $parm1;
}
function x4Data($name,$data) {
    $script = "\n\$a.data.$name = ".json_encode_safe($data).";";
    x4Script($script);

}
function jsonPrint_r($data) {
    ob_start();
    hprint_r();
    $GLOBALS['AG']['x4']['html']['*MAIN'].=ob_get_clean();
}
function json_encode_safe($data) {
    // Package up the JSON
    if(function_exists('json_encode')) {
        return json_encode($data);
    }
    else {
        return '{ "error": ["No JSON function in this version of PHP!"] }';
    }
    return;
}
# ==============================================================
#
# SECTION: HTML RENDERING
#
# INITIATED KFD 3/24/08, FINAL FORM OF RENDERING LIBRARY AFTER
# MANY EXPERIMENTS WITH MANY DIFFERENT KINDS.  GOAL IS ABSOLUTE
# MINIMUM CODE TO CREATE OBJECT-ORIENTED HTML ELEMENTS.
# ==============================================================
// KFD X4
function html($tag,&$parent=null,$innerHTML='') {
    // Branch off if an array and return an array
    if(is_array($innerHTML) ) {
        $retval = array();
        foreach($innerHTML as $oneval) {
            $retval[] = html($tag,$parent,$oneval);
        }
        return $retval;
    }


    $retval = & new androHtml();
    $retval->setHtml($innerHTML);

    if($tag<>'a-void') {
        $retval->htype = $tag;
    }
    else {
        $retval->htype='a';
        $retval->hp['href']='javascript:void(0)';
    }

    if($parent != null) {
        $parent->children[] = $retval;
    }
    return $retval;
}

class androHtml {
    var $children = array();
    var $hp   = array();
    var $ap   = array();
    var $style= array();
    var $innerHtml  = '';
    var $htype      = '';
    var $classes    = array();
    var $autoFormat = false;
    var $isParent   = false;

    #set
    function setHtml($value) {
        $this->innerHtml = $value;
    }
    function addClass($value) {
        $this->classes[] = $value;
    }
    function removeClass($value) {
        $index = array_search($value,$this->classes);
        if($index) unset($this->classes[$index]);
    }
    function addChild($object) {
        $this->children[] = $object;
    }
    function br($count=1) {
        for($x=1;$x<=$count;$x++) {
            $this->children[] = '<br/>';
        }
    }
    function hr($count=1) {
        for($x=1;$x<=$count;$x++) {
            $this->children[] = '<hr/>';
        }
    }
    function nbsp($count=1) {
        for($x=1;$x<=$count;$x++) {
            $this->children[] = '&nbsp;';
        }
    }
    function autoFormat($setting=true) {
        $this->autoFormat = $setting;
    }
    
    # Add a set of elements to something, with striping option
    function TbodyRows($rows,$options=array()) {
        $rowIdPrefix='row_';
        $stripe = a($options,'stripeCss')=='' ? 0 : 1;
        $tbody = html('tbody',$this);
        foreach($rows as $index=>$row) {
            $tr = html('tr',$tbody);
            $tr->hp['id'] = $rowIdPrefix.($index+1);
            # flip the striping variable
            $stripe*=-1;
            if($stripe==1) {
                $tr->addClass($options['stripeCss']);  
            }
            
            foreach($row as $colname=>$colvalue) {
                html('td',$tr,$colvalue);
            }
        }
    }

    # Add one or more cells to a row.  
    function addItems($tag,$values) {
        if(!is_array($values)) {
            $values = explode(',',$values);
        }
        foreach($values as $value) {
            html($tag,$this,$value);
        }
    }
        
    #  Sets a flag to work as parent
    function setAsParent() {
        $this->isParent = true;
    }
    
    #  Returns pointer to first child
    function firstChild() {
        if(count($this->children)==0) {
            return null;
        }
        else {
            $retval = &$this->children[0];
            return $retval;
        }
    }

    #  Returns pointer to last child
    function lastChild() {
        if(count($this->children)==0) {
            return null;
        }
        else {
            $retval = &$this->children[count($this->children)-1];
            return $retval;
        }
    }

    # Buffered Render
    function bufferedRender() {
        ob_start();
        $this->render();
        return ob_get_clean();
    }

    # The Render Command
    function render($parentId='') {
        # Accept a parentId, maybe assign one to
        if($parentId <> '') {
            $this->ap['xParentId'] = $parentId;
        }
        if($this->isParent) {
            $parentId = a($this->hp,'id','');
            if($parentId=='') {
                echo "Object has no id but wants to be parent";
                hprint_r($this);
                exit;
            }
        }
        
        if($this->autoFormat) {
            echo "\n<!-- ELEMENT ID ".$this->hp['id']." (BEGIN) -->";   
            //echo "$indent\n<!-- ELEMENT ID ".$this->hp['id']." (BEGIN) -->";
        }
        $parms='';
        if(count($this->classes) > 0) {
            $this->hp['class'] = implode(' ',$this->classes);
        }
        if(count($this->style)>0) {
            $style='';
            foreach($this->style as $prop=>$value) {
                $style.="$prop: $value;";
            }
            $this->hp['style']=$style;
        }
        foreach($this->hp as $parmname=>$parmvalue) {
            $parms.="\n  $parmname=\"$parmvalue\"";
        }
        foreach($this->ap as $parmname=>$parmvalue) {
            $parms.="\n  $parmname=\"$parmvalue\"";
        }
        echo "\n<".$this->htype.' '.$parms.'>'.$this->innerHtml;
        foreach($this->children as $child) {
            if(is_string($child)) {
                echo $child;
            }
            else {
                $child->render($parentId);
            }
        }
        echo "</$this->htype>";
        if($this->autoFormat) {
            echo "\n<!-- ELEMENT ID ".$this->hp['id']." (END) -->";   
            //echo "$indent\n<!-- ELEMENT ID ".$this->hp['id']." (END) -->";
        }
    }
}

# Give me a form
function htmlForm(&$parent,$page='') {
    if($page=='') $page = gp('x4Page');
    if($page=='') $page = gp('gp_page');
    $form = html('form',$parent);
    $form->hp['method'] = 'POST';
    $form->hp['action'] = "index.php?x4Page=$page";
    $form->hp['id'] = 'form1';
    $form->hp['enctype'] = 'multipart/form-data';
    $inp = html('input',$form);
    $inp->hp['type'] = 'hidden';
    $inp->hp['name'] = 'MAX_FILE_SIZE';
    $inp->hp['value']= '10000000';
    return $form;
}

# Lower level routine to generate an input
function input($colinfo,&$tabLoop = null,$options=array()) {
    $formshort= a($colinfo,'formshort',a($colinfo,'type_id','char'));
    $type_id  = a($colinfo,'type_id');
    $colprec  = a($colinfo,'colprec');
    $colscale = a($colinfo,'colscale');
    $table_id = a($colinfo,'table_id');
    $column_id= a($colinfo,'column_id');

    # Work out the read-only status for insert and update
    # Begin with unconditional
    $xRoIns = '';
    $xRoUpd = '';
    if( ($parent = a($options,'parentTable'))<>'') {
        if($colinfo['table_id_fko']==$parent) {
            $xRoIns = 'Y';
            $xRoUpd = 'Y';
        }
    }
    if(!$xRoIns) {
        $xRoIns = a($colinfo,'uiro','N');
        $xRoUpd = a($colinfo,'uiro','N');
        $autos = array('SUM','COUNT','FETCH','DISTRIBUTE','SEQUENCE'
            ,'TS_INS','TS_UPD','UID_INS','UID_UPD','EXTEND'
        );
        if(in_array(a($colinfo,'automation_id','none'),$autos)) {
            $xRoIns = 'Y';
            $xRoUpd = 'Y';
        }
        if(a($colinfo,'uiro','N')=='Y') {
            $xRoIns = 'Y';
            $xRoUpd = 'Y';
        }
        if(a($colinfo,'primary_key','N')=='Y') {
            $xRoUpd = 'Y';
        }
    }
    
    
    
    # First decision is to work out what kind of control to make
    if($type_id=='gender') {
        $input = html('select');
        $option = html('option',$input);  // this is a blank option
        $option = html('option',$input);
        $option->hp['value']='M';
        $option->innerHTML = 'M';
        $option = html('option',$input);
        $option->hp['value']='F';
        $option->innerHTML = 'F';
    }
    elseif($type_id=='cbool') {
        $input = html('select');
        $option = html('option',$input);  // this is a blank option
        $option = html('option',$input);
        $option->hp['value']='Y';
        $option->setHtml('Y');
        $option = html('option',$input);
        $option->hp['value']='N';
        $option->setHtml('N');
    }
    elseif($type_id=='text' || $type_id=='mime-h') {
        $input = html('textarea');
        $rows = a($colinfo,'uirows',10);
        $rows = $rows == 0 ? 10 : $rows;
        $cols = a($colinfo,'uicols',50);
        $cols = $cols == 0 ? 50 : $cols;
        $input->hp['rows'] = $rows;
        $input->hp['cols'] = $cols;
    }
    elseif(a($colinfo,'value_max','')<>'' && a($colinfo,'value_min','')<>'') {
        $input = html('select');
        $min = a($colinfo,'value_min');
        $max = a($colinfo,'value_max');
        for($x = $min; $x <= $max; $x++) {
            $option = html('option',$input);
            $option->hp['value']=$x;
            $option->setHTML($x);
        }
    }
    elseif(a($colinfo,'table_id_fko')<>'') {
        // First work out which control to use
        $table_id_fko = $colinfo['table_id_fko'];
        $ddfko = ddTable($table_id_fko);
        if($ddfko['fkdisplay']=='' && $xRoIns<>'Y') {
            $input = html('select');
            $rows = rowsForSelect($colinfo['table_id_fko']);
            $x = '';
            foreach($rows as $idx=>$row) {
                $x .="<option value='".$row['_value']."'>"
                    .$row['_display']."</option>";
                if($idx > 100) break;
            }
            $input->setHtml($x);
        }
        else {
            $input = html('input');
            if($ddfko['fkdisplay']<>'none') {
                $fkparms='gp_dropdown='.$colinfo['table_id_fko'];
                $input->hp['onkeyup']  ="androSelect_onKeyUp(  this,'$fkparms',event)";
                $input->hp['onkeydown']='androSelect_onKeyDown(event)';
            }
        }

        // If any columns are supposed to fetch from here,
        // set an event to go to server looking for fetches
        //
        if($table_id <> '') {
            $tabdd = ddTable($table_id);
            $fetchdist = $table_id."_".$table_id_fko."_";
            if(isset($tabdd['FETCHDIST'][$fetchdist])) {
                $input->hp['onchange']
                    ="\$a.forms.fetch("
                    ."'$table_id','$column_id',this.value"
                    .")";
            }
        }
    }
    else {
        $input = html('input');
    }

    # Apply the readonly stuff we figured out first
    $input->ap['xRoIns'] = $xRoIns;
    $input->ap['xRoUpd'] = $xRoUpd;
    
    
    #  If we ended up with an INPUT above, set the size
    if($input->htype=='input') {
        # KFD 4/24/08, makes it easier to make widgets in
        #              in custom code, don't require 'dispsize';
        if(!isset($colinfo['dispsize'])&&isset($colinfo['colprec'])) {
            $colinfo['dispsize'] = $colinfo['colprec']+1;
        }

        $input->hp['size'] = min(
            a($colinfo,'dispsize',30)
            ,OptionGet('dispsize',30)
        );
        $input->hp['maxlength'] = a($colinfo,'dispsize',10);
    }

    # Establish identifying stuff
    $input->ap['xTableId']  = $table_id;
    $input->ap['xColumnId'] = $column_id;
    if(a($colinfo,'inputId','') <> '') {
        $input->hp['id'] = $colinfo['inputId'];
    }
    elseif($table_id<>'') {
        $input->hp['id'] = 'x4inp_'.$table_id.'_'.$column_id;
    }
    else {
        $inputno = vgfGet('inputNumber',0)+1;
        $input->hp['id'] = 'x4inp_'.$inputno;
        vgfSet('inputNumber',$inputno);
    }
    $input->hp['name'] = $input->hp['id'];

    # If a value is included, set it.  In some situations a value
    # is set in JS, in other situatons (like androPage) it is
    # set during form generation
    #
    if(a($colinfo,'value')<>'') {
        $input->hp['value'] = $colinfo['value'];
    }

    # Set text alignment
    if($formshort=='numb' || $type_id=='int') {
        $input->style['text-align'] = 'right';
    }

    if($type_id=='date') {
        $input->addClass('jqdate');
    }

    # These are universal properties that were passed in
    $input->ap['xTypeId'] = $type_id;
    $input->ap['xColprec'] = $colprec;
    $input->ap['xColscale'] = $colscale;

    # Optional properties
    if(a($colinfo,'inputmask','')<>'') {
        $input->ap['xInputMask'] = $colinfo['inputmask'];
    }
    
    # If the tabloop object has been passed in, add this
    # input to it
    if(!is_null($tabLoop)) {
        $tabLoop[] = &$input;
    }
    
    # For now that's all we are going to do.
    return $input;
}

function inputsTabLoop(&$tabLoop,$options=array()) {
    if(count($tabLoop)<2) return;

    # Do the first and last manually
    $last = count($tabLoop)-1;
    $tabLoop[0    ]->ap['xTabPrev']=$tabLoop[$last  ]->hp['id'];  
    $tabLoop[0    ]->ap['xTabNext']=$tabLoop[1      ]->hp['id'];
    $tabLoop[0    ]->hp['tabindex']=1000;
    $tabLoop[$last]->ap['xTabPrev']=$tabLoop[$last-1]->hp['id'];  
    $tabLoop[$last]->ap['xTabNext']=$tabLoop[0      ]->hp['id'];
    $tabLoop[$last]->hp['tabindex']=1000 + count($tabLoop);
    
    # Now loop through the others and assign next and last dudes
    for($x=1; $x< $last; $x++) {
        $tabLoop[$x]->ap['xTabPrev']=$tabLoop[$x-1]->hp['id'];  
        $tabLoop[$x]->ap['xTabNext']=$tabLoop[$x+1]->hp['id'];
        $tabLoop[$x]->hp['tabindex']=1000 + $x;
    }
    
    # Now assign keyboard handlers and anything else
    $xpId = a($options,'xParentId');
    for($x=0; $x<= $last; $x++) {
        $tabLoop[$x]->hp['onkeypress']
            ='return x4.stdlib.inputKeyPress(event,this)';
        
        $tabLoop[$x] = inputFixupByType($tabLoop[$x]);
        
        if($xpId <> '') {
            $tabLoop[$x]->hp['onfocus']
                ="\$a.byId('$xpId').zLastFocusId = this.id";
        }
                
    }    
}

function inputFixupByType($input) {
    if($input->ap['xTypeId'] == 'date') {
        $input->hp['onkeyup']
            ='return x4.stdlib.inputKeyUpDate(event,this)';
    }
    return $input;
}

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# DEVELOPMENT LINE
#
# EVERYTHING ABOVE HERE IS OK'D FOR RELEASE 1 IN ITS FINAL
# FORM.
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~






/**
name:hprint_r
parm:any Input
returns:string HTML_Fragment

Invokes the PHP print_r command, but wraps it in HTML PRE tags so
it is readable in a browser.
*/
function hprint_r($anyvalue) {
	echo "<pre>\n";
   print_r($anyvalue);
	echo "</pre>";
}

// ------------------------------------------------------------------
/**
name:Session Variables
parent:Framework API Reference

Andromeda provides wrappers for accessing session variables.  The
PHP superglobal $_SESSION should not be directly accessed, instead
an Andromeda program should use [[SessionGet]] and [[SessionSet]].

Do not use session variables for storing information across different
requests, such as storing user replies going page-to-page through
a wizard.  Use [[Context Functions]] or [[Hidden Variables]]
for these instead, they are much more flexible and robust.

It may happen that you have multiple Andromeda applications on a server,
and that a browser is connected to more than one of them in multiple
tabls.  This would result in a collision if you were access $_SESSION
directly, because each app would overwrite the variables of the others.
Andromeda prevents these collisions automatically whenever
[[SessionGet]] and [[SessionSet]] are used.

Andromeda also prevents collissions between session variables used by
the framework and those you may put into your application.  All of the
Session variables accept an optional last parameter (not documented in
the individual functions)  The default value of this parameter is
'app', but some framework functions call it with a value of 'fw' to keep
these variables separate from application variables.  It should be noted
that sometimes the framework uses application session variables, so that
the application can find them if necessary.  Examples of this are session
variables UID (current user_id) and PWD (password of current user).

*/

/**
name:SessionGet
parm:string Var_Name
parm:any Default_Value
returns:any

This program returns a session variable.  The second parameter
is a [[Standard Default Value]] and will be returned if the
Session variable Var_Name does not exist.

The framework itself tracks only 2 session variables.  These are UID, which
is user_id, and PWD, which is user password.  An application must be
careful not to overwrite those values, as the framework will make no
provision to prevent such an accident.
*/
function SessionGet($key,$default="",$sfx='app') {
   $xkey=$GLOBALS["AG"]["application"]."_".$sfx."_".$key;
	if (isset($_SESSION[$xkey])) {
		return $_SESSION[$xkey];
	}
	else return $default;
}

/**
name:SessionSet
parm:string Var_Name
parm:any Var_Value
returns:any

This program sets a session variable.  The variable will exist
as long as the PHP session is alive.

The framework tracks only 2 session variables.  These are UID, which
is user_id, and PWD, which is user password.  An application must be
careful not to overwrite those values, as the framework will make no
provision to prevent such an accident.
*/
function SessionSet($key,$value,$sfx='app') {
   $xkey=$GLOBALS["AG"]["application"]."_".$sfx."_".$key;
	$_SESSION[$xkey] = $value;
}

/**
name:SessionUnSet
parm:string Var_Name
returns:void

Destroys the named session variable.

The framework tracks only 2 session variables.  These are UID, which
is user_id, and PWD, which is user password.  An application should
never call SessionUnSet on these variables.
*/
function SessionUnSet($key,$context='app',$sfx='app') {
   $x=$context;
   $xkey=$GLOBALS["AG"]["application"]."_".$sfx."_".$key;
	unset($_SESSION[$xkey]);
}

/**
name:SessionReset
returns:void

Destroys all session variables for the current application.  We use this
instead of PHP session_destroy because it allows a user to be logged in
to several apps at once, because the framework makes effective sessions
for each separate application.

Note that this function destroys both application and framework session
variables, there is more information on what these are on the
[[Session Variables]] page.
*/
function SessionReset() {
   global $AG;
   foreach($_SESSION as $key=>$value) {
      $app = $AG['application'].'_';
      if (substr($key,0,strlen($app))==$app) {
         unset($_SESSION[$key]);
      }
   }
}

/* DEPRECATED */
function SessionUnSet_Prefix($prefix) {
	$prefix = $GLOBALS["AG"]["application"]."_".$prefix;
	foreach ($_SESSION as $key=>$value) {
		if (substr($key,0,strlen($prefix))==$prefix) {
			unset($_SESSION[$key]);
		}
	}
}
// ==================================================================
// ==================================================================
// Global Variables
// ==================================================================
// ==================================================================
/**
name:_default_
parent:Global Variables
*/
// ------------------------------------------------------------------
/**
name:Global Variables
parent:Framework API Reference

Andromeda provides some wrapper functions for getting and setting
framework variables.  The main purpose of these is to allow your
application to create variables without worrying about name collisions
with framework global variables.

A global variable is one that exists from the beginning to the end of
a server request.  Once the HTML has been delivered to the browser, the
globals are all gone.  If you need to store variables that are
persistent from request to request, consider using [[Context Variables]].

You can set a Global Variable with [[vgaSet]] and
retrieve it with [[vgaGet]].

These values can be any valid PHP type.

The framework uses the corresponding functions [[vgfSet]] and
[[vgfGet]].
*/
/* DEPRECATED */
function ValueSet($key,$value) {
   echo "Calling Valueset $key <br/>";
   vgfSet($key,$value);
   if(!isset($GLOBALS['AG']['values'])) $GLOBALS['AG']['values']=array();
	$GLOBALS["AG"]["values"][$key] = $value;
}
/* DEPRECATED */
function ValueGet($key) {
   if(!isset($GLOBALS['AG']['values'])) $GLOBALS['AG']['values']=array();
	if (isset($GLOBALS["AG"]["values"][$key]))
      return $GLOBALS["AG"]["values"][$key];
	else
   	return "";
}
/* DEPRECATED */
function V($key,$value=null) {
	if (is_null($value)) { return ValueGet($key); }
	else ValueSet($key,$value);
}

/**
name:vgaGet
parm:string var_name
parm:string Default_Value
returns:any
group:

This function returns a [[Global Variable]].  The second parameter
names a [[Standard Default Value]] that will be returned if the
requested variable does not eixst.

You can use [[vgaGet]] and [[vgaSet]] to store and retrieve global
variables without worrying about naming collisions with the framework.
*/
function vgaGet($key,$default='') {
   return isset($GLOBALS['appdata'][$key])
      ? $GLOBALS['appdata'][$key]
      : $default;
}
/**
name:vgaSet
parm:string var_name
parm:any var_value
returns:void

This function sets the value of a global variable.
The variable will exist during the current request and can be
accessed from any scope with the [[vgaGet]] function.

You can use [[vgaGet]] and [[vgaSet]] to store and retrieve global
variables without worrying about naming collisions with the framework.
*/
function vgaSet($key,$value='') {
   $GLOBALS['appdata'][$key]=$value;
}


/**
name:vgfGet
parm:string var_name
parm:string Default_Value
returns:any
group:

This function returns a [[Global Variable]].  The second parameter
names a [[Standard Default Value]] that will be returned if the
requested variable does not eixst.

The framework uses [[vgfGet]] and [[vgfSet]] to store and retrieve global
variables without worrying about naming collisions with an application.
*/
function vgfGet($key,$default='') {
   // hardcopy routines.  Some framework variables are actually
   // constructed from other things
   $hc=array('PageTitle');
   if(in_array($key,$hc)) return vgfGetHC($key,$default);

   if(isset($GLOBALS['fwdata'][$key])) {
      return $GLOBALS['fwdata'][$key];
   }
   elseif(isset($GLOBALS['AG'][$key])) {
      return $GLOBALS['AG'][$key];
   }
   else {
      return $default;
   }
}


function vgfGetHC($key,$default='') {
   switch($key) {
      case 'PageTitle':
         if(vgfGet('UseSubtitle',false)) {
            return vgfGet('PageSubtitle');
            break;
         }
         else {
            $repl=Optionget('SITETITLE');
            $base=$repl=='' ? ValueGet('PageTitle') : $repl;
            if(vgaGet('PageTitleSuffix')<>'') {
               $base=trim($base).": ".trim(vgaGet('PageTitleSuffix'));
            }
            return $base;
            break;
         }
      default:
         return $default;
   }
}

/**
name:vgfSet
parm:string var_name
parm:any var_value
returns:void

This function sets the value of a global variable.
The variable will exist during the current request and can be
accessed from any scope with the [[vgfGet]] function.

The framework uses [[vgfGet]] and [[vgfSet]] to store and retrieve global
variables without worrying about naming collisions with the framework.
*/
function vgfSet($key,$value='') {
    //echo $key." - ".$value;
    //$a=xdebug_get_function_stack();
    //hprint_r($a);
    //echo $key." - ".$value."<br/><br/>";
   $GLOBALS['fwdata'][$key]=$value;
}
// ==================================================================
// ==================================================================
// Library Routines: Post/Get processing
// ==================================================================
// ==================================================================
/**
name:_default_
parent:GET-POST Variables
*/
// ------------------------------------------------------------------
/**
name:GET-POST Variables
parent:Framework API Reference

=Accessing POST and GET Variables=

Andromeda combines the PHP superglobals <span class="syntax10">$_GET</span>
and <span class="syntax10">$_POST</span> into one array.  The POST variables
are processed first, and then the GET variables are processed,
so that a GET will override a POST of the same name.  This feature
is entirely for the convenience of the programmer so that you do not
have to distinguish between these two sources.

Unlike many systems, Andromeda does ''not want to sanitize''
or in any way modify the data that comes in through POST/GET.
There are two reasons for this:

*The sanitation process is different for a browser or a database,
      and sanitizing for one corrupts for the other.  Therfore we
      <a href="coding.html#5">Sanitize when Sending</a>.
*You may need to handle the raw data.

The "no-sanitization" policy runs counter to the default installation
   of PHP5.  By default PHP5 has a setting turned on called
   <a class="phpfunc" href="http://www.php.net/manual/en/ref.info.php#ini.magic-quotes-gpc">magic-quotes-gpc</a> which modifies data.  During the
   processing of GET-POST Variables, Andromeda detects this setting.
   If the setting is turned on, Andromeda will pass the data through
   <a class="phpfunc" href="http://www.php.net/manual/en/function.stripslashes.php">stripslashes()</a> to return it to its original state.
   However, there is a small chance that this process will not return
   the exact value originally posted, so if you have a server used
   exclusively for Andromeda, you should turn off
   <a class="phpfunc" href="http://www.php.net/manual/en/ref.info.php#ini.magic_quotes_gpc">magic_quotes_gpc</a> in PHP.INI.

=Reading Variables From A Request=

You can pull any value from the current request with the [[gp]] function,
which takes as its arguments the variable name.

You can find out if a variable was posted in by passing the variable name to
the [[gpExists]] function, which returns true or false.

You can capture a family of variables into a [[row array]] with the
function [[roowFromGP]], which takes as its single argument a string prefix.
All variables whose name begins with that prefix will be put into the array
that is returned.  The key names will have the prefix itself stripped off.

You can set the value of a posted variable, to make it look to later code as
if it came from the browser, with [[gpSet]].  A variable set
this way does not go out to the browser, it appears as if it came in on the
current request.  You can set the value of hidden variables that will go
back to the browser with the [[Hidden]] function.

=Writing Variables=

You can set the value of a hidden variable that will go out to the browser with
[[Hidden]] which takes as its arguments a name and a value.  This is not the
same as using [[gpSet]], because the former puts a value onto the form that
will be sent to the browser, and therefore returned on the next request, while the
latter "fakes" the appearance of a variable coming in on the current request.

=Framework Conventions=

The framework generates a lot of its own variables, which follow certain conventions.
The framework uses prefixes to group variables together for similar treatment.
The special prefix for application-specific variables is "ga_", the framework will
absolutely never create a form variable with that name prefix.

The conventions in use by the framework are:

*prefix: gp_, control parameters for a page request, such as a table name,
     a flag to go to the next page, and so forth.  Never contains user data.
*subset: gp_dd_, used by the framework to specify drilldown and drillback commands.
8prefix: gpx_, These appear in every page sent to the browser, and contain
     the parameters used to process and generate this HTML.  The gp_* variables
     that are read and processed at the beginning of a page request are written
     out at the end of the page request to generate these values.
*prefix: ga_, <b>reserved for application use</b>.  The framework will never produce
    variables with this name prefix.
*prefix: array_, visible user input controls such as HTML INPUT
        and TEXTAREA controls.
*prefix: parent_, hidden controls that contain the values of the primary key
    of the current row of the current table.
*variable: gpContext, contains the entire [[window context]].  Serialized and base64'd.
*variable: gpControls, contains information about the array_* controls
    Serialized and base64'd.

The following are [[deprecated]] form variable conventions:

*prefix: txt_, deprecated.  Class x_table used these for user input controls.
*prefix: dd_, deprecated.  Class x_table used these
  for drilldown information.


*/


// ------------------------------------------------------------------
// Named stack functions
// ------------------------------------------------------------------
/** (SYSTEM) Initialize a Stack
  *
  * Initializes a stack for {@link scStackPush} and {@link scStackPop}
  *
  * @param $stackname string
  * @category miscellaneous utility
  */
function _scStackInit($stackname) {
   if (!isset($GLOBALS['STACK'])) {
      $GLOBALS['STACK']=array();
   }
   if (!isset($GLOBALS['STACK'][$stackname])) {
      $GLOBALS['STACK'][$stackname]=array();
   }
}
/** Push a value to a named stack
  *
  * Pushes $value to the stack named by $stackname.  The value
  * can be retrieved with scStackPop.
  */
function scStackPush($stackname,$value) {
   _scStackInit($stackname);
   $GLOBALS['STACK'][$stackname][] = $value;
}
/** Pops a value from a named stack
  *
  * Pops the last-added value from a named stack.  Returns
  * null if the stack is empty, an empty stack does not
  * throw an error.
  *
  */
function scStackPop($stackname) {
   _scStackInit($stackname);
   return array_pop($GLOBALS['STACK'][$stackname]);
}
// ------------------------------------------------------------------
// Routines to assemble return values
// ------------------------------------------------------------------
function return_value_add($element,$value) {
   global $AG;
   $retvals=ArraySafe($AG,'retvals',array());
   $retvals[$element]=$value;
   $GLOBALS['AG']['retvals']=$retvals;
}
function retCmd($command,$element,$value) {
   return_command_add($command,$element,$value);
}
function return_command_add($command,$element,$value) {
   global $AG;
   $retcommands=ArraySafe($AG,'retcommands',array());
   $retcommands[$command][$element]=$value;
   $GLOBALS['AG']['retcommands']=$retcommands;
}

function returns_as_ajax() {
   global $AG;
   $retvals=ArraySafe($AG,'retvals',array());
   $rv2=array();
   foreach($retvals as $element=>$value) {
      $rv2[]=$element.'|'.$value;
   }
   $retcommands=ArraySafe($AG,'retcommands',array());
   foreach($retcommands as $cmd=>$info) {
      foreach($info as $element=>$value) {
         $rv2[] = $cmd.'|'.$element.'|'.$value;
      }
   }
   echo implode("|-|",$rv2);
}



// ------------------------------------------------------------------
// Data Dictionary Routines
// ------------------------------------------------------------------
function DD_EnsureREf(&$unknown) {
   if(is_array($unknown)) return $unknown;
   else return dd_TableRef($unknown);
}
function DD_Table($table_id) {
	include_once("ddtable_".$table_id.".php");
	return $GLOBALS["AG"]["tables"][$table_id];
}

function ddNoWrites() {
   return array(
      'SEQUENCE'
      ,'FETCH','DISTRIBUTE'
      ,'EXTEND'
      ,'SUM','MIN','MAX','COUNT','LATEST'
      ,'TS_INS','TS_UPD','UID_INS','UID_UPD'
   );
}


// FINAL Form of the various "give me the dd" routines.
//       This version will filter the array based on user
//       credentials.  This means that this is the only
//       call you need, the array it gives you is completely
//       appropriate for the user.
// KFD X4
function ddTable($table_id) {
    # Don't repeat all of this work. If this has already
    # been run don't run't it again
    if(is_array($table_id)) {
        $table_id = $table_id['table_id'];
    }
    if(isset($GLOBALS['AG']['tables'][$table_id])) {
        $retval = &$GLOBALS['AG']['tables'][$table_id];
        return $retval;
    }

    # First run the include and get a reference
    if(!file_exists(fsDirTop()."generated/ddtable_$table_id.php")) {
        $GLOBALS['AG']['tables'][$table_id] = array(
            'flat'=>array()
            ,'description'=>$table_id
        );
    }
    else {
        include_once("ddtable_".$table_id.".php");
    }
    $tabdd = &$GLOBALS['AG']['tables'][$table_id];

    # First action, assign the permissions from the session so
    # they are handy
    $tabdd['perms']['menu']
        = in_array($table_id,SessionGet('TABLEPERMSMENU'));
    $tabdd['perms']['sel']
        = in_array($table_id,SessionGet('TABLEPERMSSEL'));
    $tabdd['perms']['ins']
        = in_array($table_id,SessionGet('TABLEPERMSINS'));
    $tabdd['perms']['upd']
        = in_array($table_id,SessionGet('TABLEPERMSUPD'));
    $tabdd['perms']['del']
        = in_array($table_id,SessionGet('TABLEPERMSDEL'));

    # By default assume the appropriate view is the table name itself,
    # which may change below
    $tabdd['viewname'] = $table_id;

    #  Work out the singular form of the description
    if(a($tabdd,'singular')=='') {
        $desc = $tabdd['description'];
        if(substr($desc,-3)=='ies') {
            $sing = substr($desc,0,strlen($desc)-3).'y';
        }
        else {
            $sing = substr($desc,0,strlen($desc)-1);
        }
        $tabdd['singular'] = $sing;
    }
    
    # If there is a post-processor, execute it now
    $func = 'ddTable_'.$table_id;
    if(function_exists($func)) {
        $tabdd = $func($tabdd);
    }

    # --> EARLY RETURN
    #     If a root user, or there is no group, no point
    #     in continuing
    if(SessionGet('ROOT')) return $tabdd;
    if(SessionGet('GROUP_ID_EFF','')=='') return $tabdd;

    # Capture the effective group and keep going
    $group = SessionGet('GROUP_ID_EFF');

    # Check for a view assignment
    if(isset($tabdd['tableresolve'][$group])) {
        $tabdd['viewname'] = $tabdd['tableresolve'][$group];
    }

    # If there is a view for my group, I have to knock out the columns
    # I will not be allowed to deal with on the server
    if(isset($tabdd['views'][$group])) {
        foreach($tabdd['flat'] as $column_id=>$colinfo) {
            # drop any column not listed
            if(!isset($tabdd['views'][$group][$column_id])) {
                unset($tabdd['flat'][$column_id]);
                continue;
            }

            # If there is a "0" instead of a one, set it read-only
            if($tabdd['views'][$group][$column_id]==0) {
                $tabdd['flat'][$column_id]['uiro'] = 'Y';
            }
        }
    }
    return $tabdd;
}

// KFD X4
function ddView($tabx) {
    # If not given an array, assume we were given the name of
    # the table and go get the array
    if(!is_array($tabx)) {
        $tabx = ddTable($tabx);
    }

    # Return the viewname
    return $tabx['viewname'];
}

/**
name:ddUserPerm
parm:string Table_ID
parm:string Perm_ID
returns:boolean

This function will tell you if the user is granted a particular permission
on a particular table.

The permissions you can request are:
* sel: May the user select?
* ins: May the user Insert?
* upd: May the user Update?
* del: May the user Delete?
* menu: Does this person see this on the menu?  To return a true for this
  permission, the user must have menu permission and SELECT permission.
*/
function ddUserPerm($table_id,$perm_id) {
   // Menu is done a little differently than the rest
   if($perm_id=='menu') {
      // KFD 7/19/07.  This code assumes that tablepermsmenu lists
      //               both the base table and derived column-security table,
      //               while tablepermssel lists only the views, go figure.
      $view_id=DDTable_idresolve($table_id);
      $pm = in_array($table_id,SessionGet('TABLEPERMSMENU',array()));
      $ps = in_array($view_id,SessionGet('TABLEPERMSSEL',array()));
      return $pm && ($ps || SessionGet("ROOT"));
   }

   // These are pretty simple
   $perm_id=strtoupper($perm_id);

   //$prms=SessionGET('TABLEPERMS'.$perm_id);

   return in_array($table_id,SessionGET('TABLEPERMS'.$perm_id));
}

//function D*D_arrBrowseColumns(&$table) {
//   $table=DD_EnsureRef($table);
//   $retval=array();
//   foreach($table['flat'] as $colname=>$colinfo) {
//      if(DD_ColumnBrowse($colinfo,$table)) {
//         $retval[$colname]=$colinfo['description'];
//      }
//   }
//   return $retval;
//}
function DD_ColumnBrowse(&$col,&$table)
{
	if ($col["column_id"]=="skey") return false;
	if ($col["uino"]=="Y")        return false;
	if ($col["uisearch"]=="Y")    return true;
	if ($table["risimple"]=="Y")  return true;
   return false;
}
function DD_TableProperty($table_id,$property) {
	$table = DD_Tableref($table_id);
	return $table[$property];
}
function DD_TableDropdown($table_id) {
	// Get reference to table's data dictionary
	$table = DD_TableRef($table_id);

	// Look for a projection called "dropdown".  If
	// not found, use the list "pks"
	if (isset($table["projections"]["dropdown"])) {
		$ret = $table["projections"]["dropdown"];
	}
	else {
		$ret = $table["pks"];
	}
	return explode(",",$ret);
}

/**
name:DDTable_IDResolve
parm:string $table_id
returns:string $view_id

Accepts the name of a table and returns the appropriate view to
access based on the user's effective group.

The name of a view is only returned if there is some reason to redirect
the user to a view.  In very many cases, oftentimes in all cases, the
function returns the base table name itself, such as:

* If no column or row security is on the table
* If the user is a root user
* If the user is the anonymous (login) user

*/
function DDTable_IDResolve($table_id) {
    // Both super user and nobody get original table
    if(SessionGet("ROOT")) {
      return $table_id;
    }
    // KFD 1/23/08.  This probably should never have been here,
    //     since it would always return an unusable answer.
    //
    //if(!LoggedIn()) {
    //   return $table_id;
    //}

    $ddTable=dd_TableRef($table_id);
    // This is case of nonsense table, give them back original table
    if(count($ddTable)==0) return $table_id;

    //echo "permspec is: ".$ddTable['permspec'];
    $views=ArraySafe($ddTable,'tableresolve',array());
    if(count($views)==0)
        return $table_id;
    else
        // KFD 1/23/08.  This code takes advantage of the fact that
        //   the public user by itself is always the very last
        //   effective group.  Therefore, if a user is not logged
        //   in, we will take the very last entry, assuming that it
        //   gives the answer for somebody who is only in one group.
        //
        if(LoggedIn()) {
           return $views[SessionGet('GROUP_ID_EFF')];
        }
        else {
           return array_pop($views);
        }
}

/**
name:DD_ColInsertsOK
parm:$colinfo
parm:$mode='html'
returns:bool

Accepts an array of dictionary information about a column and
then works out if inserts are allowed to that column.  Useful for
disabling HTML controls.

The optional 2nd parameter defaults to "html" but can also be "db".
If it is "html" it tells you if the user should be allowed to
specify a value, while the value of "db" determines if a SQL Insert
should be allowed to specify a value for this column.

*/
function DD_ColInsertsOK(&$colinfo,$mode='html') {
   // If in a drilldown, any parent column is read-only
   if(DrillDownLevel()>0 & $mode=='html') {
      $dd=DrillDownTop();
      if(isset($dd['parent'][$colinfo['column_id']])) return false;
   }
	$aid = strtolower(trim($colinfo["automation_id"]));
	return in_array($aid,
      array('seqdefault','fetchdef','default'
          ,'blank','none','','synch'
          ,'queuepos','dominant'
      )
   );
}

function DD_ColUpdatesOK(&$colinfo) {
    // KFD 10/22/07, allow changes to primary key
    if($colinfo['primary_key']=='Y') {
        if(ArraySafe($colinfo,'pk_change','N')=='Y')
            return true;
        else
            return false;
    }
    if(DrillDownLevel()>0) {
        $dd=DrillDownTop();
        if(isset($dd['parent'][$colinfo['column_id']])) return false;
    }
    $aid = strtolower(trim($colinfo["automation_id"]));
    if($aid=='') return true;
    $automations=array('seqdefault','fetchdef','default'
        ,'blank','none','','synch'
        ,'queuepos','dominant'
    );
    return in_array($aid,$automations);
}

function DDColumnWritable(&$colinfo,$gpmode,$value) {
   $NEVERUSED=$value;
   // If neither update or ins we don't know, just say ok
   if($gpmode <> 'ins' && $gpmode <> 'upd') return true;

   // Look for explicit settings in the dd arrays
   if(ArraySafe($colinfo,'upd','')=='N' && $gpmode=='upd') return false;
   if(ArraySafe($colinfo,'ins','')=='N' && $gpmode=='ins') return false;

   // so much for the exceptions, now just go for normal answer
   if ($gpmode=='ins') return DD_ColInsertsOK($colinfo);
   else return DD_ColUpdatesOK($colinfo);
}

/**
name:dd_tableref
parm:string table_id
returns:array Table_data_dictionary

Loads the data dictionary for a given table and returns a reference.
*/
function DD_TableRef($table_id) {
	if (!isset($GLOBALS["AG"]["tables"][$table_id])) {
      $file=fsDirTop()."generated/ddtable_".$table_id.".php";
      if(!file_exists($file)) {
         return array();
      }
      else {
         include($file);
      }
   }
   $retval=&$GLOBALS["AG"]["tables"][$table_id];
   return $retval;
}

// ------------------------------------------------------------------
// File system functions
// ------------------------------------------------------------------
/**
name:fsDirTop
returns:string Directory Path

This function returns the path to the application's
[[top directory]].  All other directories, such as the
[[lib directory]] and the [[application directory]] are all
directly below the [[top directory]].

The return value already contains a trailing slash.
*/
function fsDirTop() {
   return $GLOBALS['AG']['dirs']['root'];
}

// ------------------------------------------------------------------
// Generic Language Extensions
// ------------------------------------------------------------------
/**
name:ArraySafe
parm:Array Candidate_Array
parm:string Array_Key
parm:any Default_value

Allows you to safely retrieve the value of an array by index value,
returning a [[Standard Default Value]] if the key does not exist.
*/
function ArraySafe(&$arr,$key,$value="") {
	if(isset($arr[$key])) return $arr[$key]; else return $value;
}
function a(&$a,$key,$value='') {
    return ArraySafe($a,$key,$value);
}

// ------------------------------------------------------------------
// PHP functions that mimic Javascript DOM functions
// ------------------------------------------------------------------
/**
name:createElement
parm:type

Allows you to safely retrieve the value of an array by index value,
returning a [[Standard Default Value]] if the key does not exist.
*/
function createElement($type) {
	 return new androHElement($type);
}

class androHElement {
    var $style = array();
    var $atts  = array();

    function androHElement($type) {
        $this->type = $type;
        $this->children = array();
        $this->atts = array();
        $this->innerHTML = '';
    }

    function appendChild($object) {
        $this->children[] = $object;
    }

    function render($indent=0) {
        $hIndent = str_pad('',$indent*3);

        $retval="\n$hIndent<".$this->type;

        // Do style attributes
        $hstyle = '';
        foreach($this->style as $stylename=>$value) {
            $hstyle.="$stylename: $value;";
        }
        if($hstyle<>'') {
            $this->atts['style'] = $hstyle;
        }
        // Now output the attributes
        foreach($this->atts as $name=>$value) {
            $retval.=" $name = \"$value\"";
        }
        $retval.=">";
        foreach($this->children as $onechild) {
            $retval.=$onechild->render($indent+1);
        }
        $retval.=$this->innerHTML;
        $retval.="\n$hIndent</".$this->type.">";
        return $retval;
    }
}
// ==================================================================
//
//  le    Language Extensions, including session handling, good to
//        load up in index_hidden, almost always necessary, should
//        probably be in index_hidden, useful for ajax calls,
//        vgfGet, vgfSet, etc., elementadd, stack functions
//
//  h     HTML Generation, almost always necessary
//  hx    HTML / database, like ahRowsForSelect
//  SQL   Database access, almost always necessary
//
//  post  Save things back to database.  We need a way to determine
//        if this should happen.  A flag perhaps that is present on
//        all db writes, so that we only load this up when we know
//        there is going to be a database write.
//
//  ehstd Standard content routines, spit out hiddens etc.
//          Most likely move into x_table2 or the whole rendering
//          thing, and only when necessary.  This means as well the
//          entire template decision stuff can be taken out of
//          index_hidden and moved into there.
//  table All table maintenance routines.  Related to ehstd
//  joom  Joomla compatibility, only required if rendering a
//           complete html template
//
//  logs  log entry routines
//
//  user  user options routines
//        user logged in, security settings and so forth
//
//  spec  one-timers, likehttpHeadersForDownload, this would be
//             in a library for downloads
//
//  dyn   dynamic saving, loading stuff
//
//  dd    drilldown routines
// ==================================================================
// ==================================================================
// Documentation
// ==================================================================
// ==================================================================
/**
name:Standard Default Value
parent:Framework API Reference

Many Andromeda library functions provide a flexible way to
handle default values.

For example, consider the case where you want to retrieve the
value of a [[GET-POST Variable]].  Your code must be robust enough
to detect the cases where the value was not actually passed, and
in those cases provide a default.  Straight PHP might look like this:

<div class="php">
if (!isset($_POST['book_name'])) {
   $var='Mastering PHP';
}
else {
   $var=$_POST['book_name'];
}
</div>

The equivalent Andromeda code would look like this:

<div class="php">
$var=gp('book_name','Mastering PHP');
</div>

The second parameter is called the "Standard Default Value", and it
tells the [[gp]] function what to return if the requested value is
undefined or blank.
*/

// ==================================================================
// ==================================================================
// Framework Log Functions
// ==================================================================
// ==================================================================
function fwLogEntry($code,$desc,$arg1='',$arg2='',$arg3='') {
   xLogEntry('Y',$code,$desc,$arg1,$arg2,$arg3);
}
function appLogEntry($code,$desc,$arg1='',$arg2='',$arg3='') {
   xLogEntry('N',$code,$desc,$arg1,$arg2,$arg3);
}

function xLogEntry($fw,$code,$desc,$arg1='',$arg2='',$arg3='') {

   // create our own connection as the anonymous user, but only
   // if not already logged in as anonymous user!  Otherwise the
   // stack program don't work.
   scDBConn_Push($GLOBALS['AG']['application']);
   //$needed_connect=false;
   //$uid = $GLOBALS['AG']['application'];
   //$dbc=SQL_Conn($uid,$uid);

   // get the ip address
   $ip = SQLFC($_SERVER['REMOTE_ADDR']);

   // Do the SQL Command
   $fw       = SQLFC($fw);
   $elogcode = SQLFC($code);
   $elogdesc = SQLFC($desc);
   $elogarg1 = SQLFC($arg1);
   $elogarg2 = SQLFC($arg2);
   $elogarg3 = SQLFC($arg3);
   $sq="insert into elogs
         (flag_fw,elogcode,elogdesc,elogipv4,elogarg1,elogarg2,elogarg3)
         values
         ($fw,$elogcode,$elogdesc,$ip,$elogarg1,$elogarg2,$elogarg3)";
   SQL($sq);

   // Close out, we're done
   //SQL_ConnClose($dbc);
   scDBConn_Pop();
}

// ==================================================================
// ==================================================================
// System Log Functions
// ==================================================================
// ==================================================================
/**
name:_default_
parent:System Log Functions
*/
// ------------------------------------------------------------------
/**
name:System Log Functions
parent:Framework API Reference

System Log functions are used when you must be certain that a log
entry will be written, even if the current transaction rolls back.
These kinds of logs are intended for use in debugging or tracking
invisible processes, such as a Paypal IPN transaction.

A log can be opened with [[SysLogOpen]], which returns a handle to
the log.  Log entries are made with [[SysLogEntry]] and the log is
eventually closed with [[SysLogClose]].

The logs are stored in tables [[syslogs]] and [[syslogs_e]].

The guarantee that the log entry will always be written comes at the
price of a separate connection to the database for each log.  In a
debugging situation you can open as many of them as you need, but in
a production system they should only be used in highest need.


*/

/**
name:SysLogOpen
parm:string Name
returns:int LogNumber

Use this function to open a system log.  Returns a LogNumber, which
is used for subsequent calls to [[SysLogEntry]].  When the log is
finished, close it with [[SysLogClose]].

Any number of system logs can be open at a time.
*/
function SysLogOpen($name) {
   if(!isset($GLOBALS['AG']['logs'])) {
      $GLOBALS['AG']['logs']=array();
   }
   $app=$GLOBALS['AG']['application'];
   $conn = SQL_CONN($app,$app);
   $sq="insert into syslogs (syslog_name,syslog_type,syslog_subtype) "
      ."values ('".$name."','PHP-FW','APP LOG')";
   SQL2($sq,$conn);

   // Assume the notice comes back looking like this:
   // NOTICE:  SKEY (syslogs) 11073;
 	$notices = pg_last_notice($conn);
   $skey =substr($notices,24);
   $skey =substr($skey,0,strlen($notices)-1);

   $dbres=SQL2("select syslog from syslogs where skey=$skey",$conn);
   $row=SQL_Fetch_Array($dbres);
   $syslog=$row['syslog'];

   // record the connection with the log number
   $GLOBALS['AG']['logs'][$syslog]=$conn;

   SysLogEntry($syslog,'Log Open Command Received');

   return $syslog;
}

/**
name:SysLogEntry
parm:int LogNubmer
parm:string EntryText
returns:void

Writes an entry to a log opened with [[SysLogOpen]].  The value
of LogNumber is returned from [[SysLogOpen]].  The value of
EntryText is written to the log.

*/
function SysLogEntry($syslog,$text) {
   if(!is_null($syslog)) {
      $conn = $GLOBALS['AG']['logs'][$syslog];
      SQL2("insert into syslogs_e (syslog,syslog_etext)"
         .' values '
         ."(".$syslog.",".SQL_Format('char',$text).")"
         ,$conn
      );
   }
}

/**
name:SysLogClose
parm:int LogNubmer
returns:void

Closes a system log, and closes the database connection that
was opened just for that log.
*/
function SysLogClose($skey) {
   SysLogEntry($skey,'Log Close Command Received');
   SQL_ConnClose($GLOBALS['AG']['logs'][$skey]);
   unset($GLOBALS['AG']['logs'][$skey]);
}


// ==================================================================
// Options Routines
// ==================================================================
/* DEPRECATED */
function raxOptionSet($name,$value) {
   raxArraySet('options',$name,$value);
}

/* DEPRECATED */
function raxOptionGet($name,$default='') {
   global $rax;
   return isset($rax['options'][$name]) ? $rax['options'][$name] : $default;
}

/* DEPRECATED */
function raxArrayInit($aname) {
   global $rax;
   if (!isset($rax[$aname])) {
      $rax[$aname] = array();
   }
}

/* DEPRECATED */
function raxArraySet($family,$name,$value) {
   global $rax;
   raxArrayInit($family);
   $rax[$family][$name]=$value;
}




// ==================================================================
// Headers and other HTTP stuff
// ==================================================================
/* DEPRECATED */
function HTTP_redirect($page,$vars) {
	Header("Location: ".HTTP_Address($page,$vars));
}

/* DEPRECATED */
function HTTP_Address($page,$vars) {
	$args = "";
	foreach ($vars as $key=>$value) {
		$args.=ListDelim($args,"&").trim($key)."=$value";
	}
	if ($args) { $args = "&".$args; }
	return "index.php?gp_page=".$page.$args;
}

/* DEPRECATED */
function CleanExists($key) {
	return isset($GLOBALS["AG"]["clean"][$key]);
}

/* DEPRECATED */
function CleanSetArray($arr,$prefix="") {
	foreach ($arr as $key=>$value) { CleanSet($prefix.$key,$value); }
}

/* DEPRECATED */
function CleanSet($key,$value) {
	$GLOBALS["AG"]["clean"][$key] = $value;
}
/* DEPRECATED */
function CleanSet_Subset($clear_if_unset,$prefix,$arr) {
	$strlen = strlen($prefix);

	// First clear existing if told to
	if ($clear_if_unset) {
		foreach ($GLOBALS["AG"]["clean"] as $colname=>$colvar) {
			if (substr($colname,0,$strlen)==$prefix) {
				$GLOBALS["AG"]["clean"][$colname] = "";
			}
		}
	}

	// Then assign the values we were given
	foreach ($arr as $key=>$value) {
		$GLOBALS["AG"]["clean"][$prefix.$key] = $value;
	}
}

/* DEPRECATED */
function CleanUnset($key) {
	if (isset($GLOBALS["AG"]["clean"][$key])) {
		unset($GLOBALS["AG"]["clean"][$key]);
	}
}

/* DEPRECATED */
function CleanBox($key,$tdefault="",$reportmissing=true) {
	return CleanGet("txt_".$key,$tdefault,$reportmissing);
}

/* DEPRECATED */
function CleanControl($skipempty=false) {  return Clean_Subset("gp_",$skipempty);  }
/* DEPRECATED */
function CleanBoxes($skipempty=false)   {  return Clean_Subset("txt_",$skipempty); }

/* DEPRECATED */
function Clean_Subset($prefix,$skipempty=false) {
	$strlen = strlen($prefix);
	$colvars = array();
	foreach ($GLOBALS["AG"]["clean"] as $colname=>$colvar) {
		if (substr($colname,0,$strlen)==$prefix) {
			if ($colvar!="" || !$skipempty) {
				$colvars[substr($colname,$strlen)] = $colvar;
			}
		}
	}
	return $colvars;
}

/* DEPRECATED */
function CleanGetUnset($key) {
	$retval = CleanGet($key,"",false);
	unset ($GLOBALS["AG"]["clean"][$key]);
	return $retval;
}


/* DEPRECATED */
function CleanGet($key,$tdefault="",$reportmissing=true) {
	$post=$GLOBALS["AG"]["clean"];
	if (!isset($post[$key])) {
		if ($reportmissing) {
			ErrorAdd("System Error, Received variable does not exist: ".$key);
		}
		return $tdefault;
	}
	else {
		return $post[$key];
	}
}

// ==================================================================
// ==================================================================
// Session Functions
// ==================================================================
// ==================================================================
/**
name:_default_
parent:Session Variables
*/

// ==================================================================
// ==================================================================
// Hidden Variables
// ==================================================================
// ==================================================================
/**
name:_default_
parent:Hidden Variables
*/
// ------------------------------------------------------------------
/**
name:Hidden Variables
parent:Framework API Reference

Hidden variables are the simplest and most time-honoured way to
send data to the browser that will come back on the next form post.

Andromeda allows you to "register" hidden variables at any time
using the function [[Hidden]].  The framework function
[[ehHiddenAndData]] then outputs them when the HTML is being
generated.
*/

/**
name:Hidden
parm:string Variable_Name
parm:string Variable_Value

Registers a value that should be output as a hidden variable when the
HTML is generated.

The values get written by the framework function [[ehHiddenAndData]].
*/
function Hidden($varname=null,$val=null) {
   arrDefault($GLOBALS['AG'],'hidden',array());
   $GLOBALS['AG']['hidden'][$varname]=$val;
}

/**
name:HiddenFromTable
parm:string Table_id
parm:array row (optional)
date: April 18, 2007

Generates one hidden variable for each column in Table_id.  The name
of the variables is formed as $table_id."_".$column_id.

If the second parameter, a [[Row Array]], is passed, the hidden
variables will be populated with values from that array, otherwise
they will be blank.

!>example
!>php
hiddenFromTable('nodes');
!<
!>output:Will produce this in the HTML
<input type="hidden" name="nodes_node" value="">
<input type="hidden" name="nodes_node_url" value="">
...and so forth...
!<
!<

*/
function hiddenFromTable($table_id,$row=array()) {
   $table_id=trim($table_id);
   $table=DD_TableRef($table_id);
   $cols = array_keys($table['flat']);
   foreach($cols as $col) {
      hidden($table_id."_".$col,ArraySafe($row,$col));
   }
}


// ==================================================================
// ==================================================================
// Context Variables
// ==================================================================
// ==================================================================
/**
name:_default_
parent:Context Variables
*/
// ------------------------------------------------------------------
/**
name:Context Variables
parent:Framework API Reference

Andromeda provides Context Functions as a more robust and flexible
alternative to [[Session Variables]].

A "context" is all of the state that is specific to a particular
browser tab.  If a user opens three browser windows
to your app, and three tabs in each browser, each of the nine tabs will
have its own context.

The context is returned to the server only when the user navigates by
POST.  If you need to use context, make sure you are using SUBMITs and
not hyperlinks.

[[Session Variables]] are stored on the server, but Context Variables
are sent out to the browser and then returned with each round trip.
This means that care must be taken only to add the most essential
information to context.

The Andromeda framework sometimes writes its own context variables.  You
can avoid collisions with these variables by always using the functions
whose names begin with "app".
*/


/**
name:appConGet
parm:string Category
parm:string Name
parm:string Key
parm:any Default
returns:any

Returns a context variable.  A context variable is completely
specified by three levels, a Category, a Name, and a Key.

Context variables are specific to each window the user has open,
and remain alive as long as the user navigates with form POSTs.  When
the user navigates with an HTML hyperlink the context is lost.

Use this routine instead of [[ConGet]] to ensure your variables do not
collide with framework context variables.

The fourth parameter is a [[Standard Default Value]], which is returned
if the variable cannot be found.
*/
function appconget($category,$name,$key,$default='') {
   return ContextGet('app_'.$category.'_'.$name.'_'.$key,$default);
}

/**
name:ConGet
parm:string Category
parm:string Name
parm:string Key
parm:any Default
returns:any

Returns a context variable.  A context variable is completely
specified by at three levels, a Category, a Name, and a Key.

This function is used exclusively by the framework, your applications
should use [[appConGet]].

The fourth parameter is a [[Standard Default Value]].
*/
function conget($category,$name,$key,$default='') {
   return ContextGet('fw_'.$category.'_'.$name.'_'.$key,$default);
}


/**
name:ContextGet
parm:string VarName
parm:any Default
returns:any
group:Framework Internals

This is the lowest-level routine that returns context variables.

Applications should not use this routine, they should use [[appConGet]].
Framework library code uses [[ConGet]].
*/
function ContextGet($name,$default='') {
   $sc=&$GLOBALS['AG']['clean']['gpContext'];
   return isset($sc[$name])
      ? $sc[$name]
      : $default;
}


/**
name:appConSet
parm:string Category
parm:string Name
parm:string Key
parm:any Default
returns:any

Sets a context variable.  A context variable is completely
specified by at three levels, a Category, a Name, and a Key.

Application code should use this routine to avoid naming collisions
with context variables set by the framework.
*/
function appConSet($category,$name,$key,$value='') {
   return ContextSet('app_'.$category.'_'.$name.'_'.$key,$value);
}

/**
name:ConSet
parm:string Category
parm:string Name
parm:string Key
parm:any Default
returns:any

Sets a context variable.  A context variable is completely
specified by at three levels, a Category, a Name, and a Key.

This routine is reserved for use by the framework.
Application code should use [[appConSet]].
*/
function conSet($category,$name,$key,$value='') {
   return ContextSet('fw_'.$category.'_'.$name.'_'.$key,$value);
}


/**
name:ContextSet
parm:string VarName
parm:any Default
returns:any
group:Framework Internals

This is the lowest-level routine that sets context variables.

Applications should not use this routine, they should use [[appConSet]].
Framework library code uses [[ConSet]].
*/
function ContextSet($name,$value='') {
   $sc=&$GLOBALS['AG']['clean']['gpContext'];
   $sc[$name]=$value;
}


/**
name:appConUnSet
parm:string Category
parm:string Name
parm:string Key
parm:any Default
returns:any

Destroys a context variable.  A context variable is completely
specified by at three levels, a Category, a Name, and a Key.

Application code should use this routine to avoid naming collisions
with context variables set by the framework.
*/
function appConUnSet($category,$name,$key) {
   return ContextUnSet('app_'.$category.'_'.$name.'_'.$key);
}

/**
name:ConUnSet
parm:string Category
parm:string Name
parm:string Key
parm:any Default
returns:any

Destroys a framework context variable.  A context variable is completely
specified by at three levels, a Category, a Name, and a Key.

This routine is reserved for use by the framework.
Application code should use [[appConUnSet]].
*/
function conUnSet($category,$name,$key) {
   return ContextUnSet('fw_'.$category.'_'.$name.'_'.$key);
}


/**
name:ContextUnSet
parm:string VarName
returns:any

This is the lowest-level routine that destroys context variables.

Applications should not use this routine, they should use
[[appConUnSet]].  Framework library code should use [[ConUnSet]].
*/
function ContextUnSet($name) {
   if (isset($GLOBALS['gpContext'][$name]))
      unset($GLOBALS['gpContext'][$name]);
}


/**
name:appConClear
returns:void

Destroys all context variables.

Application code should use this routine to avoid naming collisions
with context variables set by the framework.
*/
function appConClear() {
   return ContextClear('app');
}


/**
name:ConClear

Destroys all framework context variables.

This routine is reserved for use by the framework.
Application code should use [[appConClear]].
*/
function ConClear() {
   return ContextClear('fw');
}

/**
name:ContextClear

This is the lowest-level routine that destroys all context variables.

Applications should not use this routine, they should use
[[appConClear]].  Framework library code uses [[ConClear]].
*/
function ContextClear($prefix='') {
   if($prefix=='') {
      if(isset($GLOBALS['gpContext'])) {
         unset($GLOBALS['gpContext']);
      }
   }
   else {
      foreach($GLOBALS['gpContext'] as $name=>$value) {
         if(substr($name,0,strlen($prefix))==$prefix) {
            unset($GLOBALS['gpContext'][$name]);
         }
      }
   }
}




// ==================================================================
// ==================================================================
// Notices and Errors
// ==================================================================
// ==================================================================
/**
name:_default_
parent:Notices and Errors
*/
// ------------------------------------------------------------------
/**
name:Notices and Errors
parent:Framework API Reference

Andromeda supports (and in fact requires) delayed error reporting.

When an error occurs in code, the error is saved temporarily using
[[ErrorAdd]], and execution then always continues to the end.  The
errors are then reported when the HTML is sent to the browser.

There can be multiple errors in one request.  Any framework function
that sends commands to the database server will also take database
server errors and run them through [[ErrorAdd]] so that they can be
reported.

An error is anything at all that causes the user's expected action
not to occur.  A notice is information that the user may need that is
not an error.

The Andromeda default screens report all errors and notices.
When you make a completely custom screen or template, you must make
provision to report errors and notices.
*/

/**
name:NoticeAdd
parm:string Notice_Text

Adds a notice to the list of notices to report to the user.
*/
/* CODE PURGE CANDIDATE */
/* this routine is not used by the framework */
function NoticeAdd($notice) {
	$GLOBALS["AG"]["messages"][]=$notice;
}

/**
name:Notices
returns:boolean

Returns true if any notices have been registered with [[NoticeAdd]].
*/
function Notices() {
	if (count($GLOBALS["AG"]["messages"])>0) return true; else return false;
}

/**
name:NoticesGet
returns:array Notice_Texts

returns an array of the currently reported notices.
*/
/* CODE PURGE CANDIDATE */
/* this routine is not used by the framework */
function NoticesGet() {
   $retval= isset($GLOBALS['AG']['messages'])
      ? $GLOBALS['AG']['messages']
      : array();
   return $retval;
}



/**
name:ErrorAdd
parm:string Error_Text

Adds an error to the list of errors to report to the user.
*/
function ErrorAdd($error) {
   $error=preg_replace('/^[Ee][Rr][Rr][Oo][Rr]:\w*(.*)/','$1',$error);
	$GLOBALS["AG"]["trx_errors"][]=$error;
}
/**
name:ErrorsAdd
parm:array Error_Texts

Adds a list of errors to the the list of errors to report to the user.
*/
function ErrorsAdd($semilist) {
	$arr = explode(";",$semilist);
	foreach ($arr as $err) {
		ErrorAdd($err);
	}
}

/**
name:ErrorsClear

Clears the list of previously registered errors.
*/
function ErrorsClear() {
   $GLOBALS['AG']['trx_errors']=array();
}

/**
name:Errors
returns:boolean

Returns true if any errors have been registered
*/
function Errors($prefix='') { return ErrorsExist($prefix); }

/* DEPRECATED */
function ErrorsExist($prefix='') {
	global $AG;
	if (!isset($AG["trx_errors"])) return false;  // never set, no errors
	if (count($AG["trx_errors"])==0) return false; // empty list of errors
   if ($prefix=='') return true;  // no distinguishing prefix, any error=true

   // finally, look through each error for the prefix.  first found
   // returns true
   foreach($AG['trx_errors'] as $err) {
      if (substr(trim($err),0,strlen($prefix))==$prefix) return true;
   }
   return false;
}

/**
name:ErrorsGet
returns:array Error_Texts

returns an array of the currently reported errors.
*/
function ErrorsGet($errorsclear=false) {
   $retval= isset($GLOBALS['AG']['trx_errors'])
      ? $GLOBALS['AG']['trx_errors']
      : array();
   if ($errorsclear) ErrorsClear();
   return $retval;
}

/* DEPRECATED */
function aErrorsClean() {
   if (!isset($GLOBALS['AG']['trx_errors'])) {
      $retval = array();
   }
   else {
      $retval = $GLOBALS['AG']['trx_errors'];
      ErrorsClear();
   }
   return $retval;
}

/* DEPRECATED */
/* CODE PURGE CANDIDATE */
/* this routine is not used by the framework */
function aNoticesClean() {
   if (!isset($GLOBALS['AG']['messages'])) {
      $retval = array();
   }
   else {
      $retval = $GLOBALS['AG']['messages'];
      unset($GLOBALS['AG']['messages']);
   }
   return $retval;
}

/**
name:hErrors
parm:string CSS_Class
returns:string HTML_Fragment

Returns an HTML DIV element containing all reported errors.  Each error
is in an HTML P element.

You can specify a CSS Class to assign to the DIV element.  If nothing
is specified, the CSS Class "errorbox" is used.

Your CSS that defines the errors might look like this:

<div class="CSS">
div.errorbox {
   ...properties for the errorbox..
}
div.errorbox p {
   ...properties for each error entry
}
</div>
*/
function hErrors($class='errorbox') {
    $retval="";

    global $AG;
    $errors=ErrorsGet();

    if(count($errors)>0) {
        $retval="\n<div class=\"".$class."\">";
        foreach($errors as $error) {
            $retval.="\n<p>".$error."</p>";
        }
        $retval.="\n</div>";
    }
    return $retval;
}

function asErrors() {
	global $AG;
	$retval="";
   $errors=ErrorsGet();
   foreach($errors as $error) {
      $retval.="\n".$error."\n";
   }
   return $retval;
}

/**
name:hNotices
parm:string CSS_Class
returns:string HTML_Fragment

Returns an HTML DIV element containing all reported notices.  Each notice
is in an HTML P element.

You can specify a CSS Class to assign to the DIV element.  If nothing
is specified, the CSS Class "noticebox" is used.

Your CSS that defines the notices might look like this:

<div class="CSS">
div.noticebox {
   ...properties for the noticebox..
}
div.noticebox p {
   ...properties for each notice entry
}
</div>
*/
function hNotices($class='noticebox') {
	global $AG;
	$retval="";
   $notices=NoticesGet();
   if(count($notices)>0) {
      $retval="\n<div class=\"".$class."\">";
      foreach($notices as $notice) {
         $retval.="\n<p>".$notice."</p>";
      }
      $retval.="\n</div>";
   }
   return $retval;
}

// ==================================================================
// ==================================================================
// User Preferences
// ==================================================================
// ==================================================================
/**
name:_default_
parent:User Preferences
*/
// ------------------------------------------------------------------
/**
name:User Preferences
parent:Framework API Reference

The User Preferences system is EXPERIMENTAL and may change
considerably before Version 1.0 is released.

The basic idea is to allow users to override system default
behaviors, such as how dates are displayed.
*/

/**
name:UserPref
parm:string Key
parm:any Default
flag:experimental

''*EXPERIMENTAL*''

Expects the user preferences to have been set with [[vgaSet]] under the
name "this_user_prefs".  Expects the user preferences to be an array.

If the Key is in the array, then the preference is returned, else the
Default value is returned.

Note that this is experimental and has been set up only for one client.
*/
function userPref($key,$default) {
   $array=vgfGet('this_user_prefs');
   return ArraySafe($array,$key,$default);
}


/**
function:UserPrefsLoad

''*EXPERIMENTAL*''

Loads a [[Row Array]] of user preferences via [[vgfSet]] to
framework variable 'this_user_prefs'.

Normally if you want to make use of user preferences you put a call
to this routine in applib.php.

This function needs the application variable 'user_preferences' to be
set to the name of the table that contains user preferences.  That
table is expected to have column 'user_id' in it.

The row selected is where user_id=SessionGet("UID").
*/
function userPrefsLoad() {
   $table=vgaGet('user_preferences');
   if($table=='') {
      $row = array();
   }
   else {
      $row = SQL_OneRow(
         "select * from $table where user_id="
         ."'".SessionGet("UID")."'"
      );
   }
   vgfSet('this_user_prefs',$row);
}


// ==================================================================
// ==================================================================
// Simple HTML Generation
// ==================================================================
// ==================================================================
/**
name:_default_
parent:Simple HTML Generation
*/
// ------------------------------------------------------------------
/**
name:Simple HTML Generation
parent:Framework API Reference

Andromeda contains a very large number of routines that generate
simple snippets of HTML.  The real purpose of these routines is to
avoid messy mixtures of HTML and PHP, mixtures that are difficult
to write and very difficult to maintain.
*/

/**
name:Optional CSS Class

Almost all HTML generation functions allow you to specify the
CSS class of the generated element, and almost all of them allow
this to be specified as the first parameter to the function.

The CSS Class can always be safely passed as an empty string, in which
case no class assignment is made.
*/


/**
name:hTagParm
parm:string parmname
parm:string parmval
returns:string HTML_fragment

This handy routine returns either an HTML property assignment or
an empty string.  It is a useful helper routine for building HTML
element definitions when you don't know if the parameters being passed
in are going to be empty.

So for instance, if you have been passed a value of $CSS_class which
may be empty, you can call:

$class=hTagParm('class',$CSS_Class)

if the value passed to $CSS_Class is empty it will give you back an
empty string, otherwise it will give you the string 'class="-CSS_Class-"'.

This allows for safe unconditional placement of $class into an HTML
element definition.
*/
function hTagParm($parmname,$parmval) {
   return $parmval==''
      ? ''
      : $parmname. ' ="'.trim($parmval).'"';
}

/**
name:hElement
parm:string CSS_Class
parm:string HTML_element
parm:string innerHTML
returns:HTML (string)

This function generates a single arbitrary HTML element, with open and close tags
and optional class asignment.  It does not save a great deal of typing
but it does allow you to avoid to confusing mixtures of PHP and HTML.

The first parameter, CSS_Class, can be an empty string.
*/
function hElement($class,$element,$innerHTML) {
   $hclass = hTagParm("class",$class);
   return "<".$element.' '.$hclass.'>'.$innerHTML."</$element>";
}


/**
name:hTD
parm:string CSS_Class
parm:string Value
returns:string HTML_Fragment

Returns an HTML TD element with open and close tags.

The first parameter is the [[Optional CSS Class]].
*/
function hTD($class,$value,$extra='') {
   $class=hTagParm('class',$class);
   return "\n  <td $class ".$extra.">".$value."</td>";
}

/**
name:hSpan
parm:string CSS_Class
parm:string Value
returns:string HTML_Fragment

Returns an HTML SPAN element with open and close tags.

The first parameter is the [[Optional CSS Class]].
*/
function hSpan($class,$value,$extra='') {
   $class=hTagParm('class',$class);
   return "\n  <span $class ".$extra.">".$value."</span>";
}


/**
name:hTRFromRow
parm:string CSS_Class
parm:Array Row

Accepts a [[Row Array]] and returns a complete HTML TR element,
populated with on TD element per element of the [[Row Array]].

The first parameter is the [[Optional CSS Class]].  This class is
assigned to the TR and to each of the TD elements.
*/
function hTRFromRow($class,$row) {
   $hclass=hTagParm('class',$class);
   $retval="<tr $hclass>";
   foreach($row as $key=>$value) {
      $retval.=hTD($class,$value);
   }
   return $retval.'</tr>';
}

/**
name:hTRFiller
parm:int height
parm:int colspan
returns:string HTML_Fragment

Returns an HTML TR with a single TD element of fixed height "Height".
Good for putting spacers into table.

The second parameter is an optional COLSPAN setting for the TD element.
*/
function hTRFiller($height,$colspan='') {
   $colspan=hTagParm('colspan',$colspan);
   return "<tr><td height=".$height." $colspan></td></tr>";
}

/* DEPRECATED */
/* DEPRECATED BECAUSE THERE ARE SO MANY WAYS TO DO TABLES */
function hTable($width=0,$height=0) {
   $pw = $width=0  ? '' : ' WIDTH="'.$width.'%" ';
   $ph = $height=0 ? '' : ' HEIGHT="'.$height.'%" ';
   return '<table'.$pw.$ph.' border="0" cellpadding="0" cellspacing="0">';
}


/**
name:hpct
parm:number inputval
parm_optional:number trailing_decimals
parm_optional:bool trailing_pct
returns:HTML (string)

Takes a number between 0 and 1 and returns a formatted string between
0 and 100, with an optional trailing % sign.
*/
function hPct($inputval,$decimals=1,$trailing_pct=false) {
   $retval=number_format($inputval*100,$decimals);
   return $retval.($trailing_pct ? '%' : '');
}

/**
name:hDate
parm:String/Number input_value
parm:String Format_String (optional)
return:HTML
date:3/31/07
testtypes:char,char
test:3/31/07,
test:3/9/07,d-mm-yyyy
test:3/9/07,dd-mm-yyyy
test:3/9/07,ddd-mm-yyyy
test:3/9/07,Ddd-mm-yyyy
test:3/9/07,DDD-mm-yyyy
test:3/9/07,dddd-mm-yyyy
test:3/9/07,Dddd-mm-yyyy
test:3/9/07,DDDD-mm-yyyy
test:3/9/07,m/d/yy
test:3/9/07,mm/d/yy
test:3/9/07,mmm/d/yy
test:3/9/07,Mmm/d/yy
test:3/9/07,MMM/d/yy
test:3/9/07,mmmm/d/yy
test:3/9/07,Mddd/d/yy
test:3/9/07,MMMM/d/yy
test:3/9/07,m/d/yy
test:3/9/07,m/d/yyyy
test:1/1/07 ,m-d-y EXTRA m ** d ** yyyy
test:12/31/07,m-d-y EXTRA mm ** ddd ** yyyy


Accepts either a string or a unix timestamp and returns
a string that can be sent to the browser.  This is a great
function for people who cannot remember the
formatting codes for the [[php:date]] function.

If the first parameter is a string, hDate passes it through
[[php:strototime]] to convert it into a timestamp.  If the first
parameter is a number hDate assumes it is a unix timestamp.

If no second parameter is provided, hDate calls
[[php:date]] with the string 'm/d/Y', a standard US date format.

The real value of hDate comes into play if you can never remember those
strange formatting strings for [[php:date]].  The strings for
hDate are much easier to remember.  They are:

* d: day of the month without leading zeros.  date('j')
* dd: day of the month with leading zeros. date('d')
* ddd: short textual day of week, same as strtolower(date('D'))
* Ddd: short textual day of week, same as date('D')
* DDD: short textual day of week, same as strtoupper(date('D'))
* dddd: full textual day of week, same as strtolower(date('l'))
* Dddd: full textual day of week, same as date('l')
* DDDD: full textual day of week, same as strtoupper(date('l'))
* m: numeric month without leading zeros. date('n')
* mm: numeric month with leading zeros. date('m')
* mmm: short textual month name, same as strtolower(date('M'))
* Mmm: short textual month name, same as date('M')
* MMM: short textual month name, same as strtoupper(date('M'))
* mmmm: full textual month name, same as strtolower(date('F'))
* Mmmm: full textual month name, same as date('F')
* MMMM: full textual month name, same as strtoupper(date('F'))
* yy: 2-digit year. date('y')
* yyyy: 4-digit year. date('Y')

*/
function hDate($date,$format='') {
   $date=dEnsureTS($date);
   if($format=='') {
      return date('m/d/Y',$date);
   }

   // Convert all codes.  Each time we locate a string,
   // we split it into left, right, and middle.  The middle
   // is replaced, the
   $out=$format;
   $out= hDateHelper($date,$out,'yyyy',"Y");
   $out= hDateHelper($date,$out,'yy'  ,"y");
   $out= hDateHelper($date,$out,'MMMM',"F",'U');
   $out= hDateHelper($date,$out,'Mmmm',"F");
   $out= hDateHelper($date,$out,'mmmm',"F",'L');
   $out= hDateHelper($date,$out,'MMM' ,"M",'U');
   $out= hDateHelper($date,$out,'Mmm' ,"M");
   $out= hDateHelper($date,$out,'mmm' ,"M",'L');
   $out= hDateHelper($date,$out,'mm'  ,"m");
   $out= hDateHelper($date,$out,'m'   ,"n");
   $out= hDateHelper($date,$out,'DDDD',"l",'U');
   $out= hDateHelper($date,$out,'Dddd',"l");
   $out= hDateHelper($date,$out,'dddd',"l",'L');
   $out= hDateHelper($date,$out,'DDD' ,"D",'U');
   $out= hDateHelper($date,$out,'Ddd' ,"D");
   $out= hDateHelper($date,$out,'ddd' ,"D",'L');
   $out= hDateHelper($date,$out,'dd'  ,"d");
   $out= hDateHelper($date,$out,'d'   ,"j");

   return hDateBuild($out);
}
// - - - - - - - - - - - - - - - - - - - - - - - - - - -
function hDateHelper($date,$haystack,$needle,$datearg,$extra='') {
   if(is_array($haystack)) {
      // For an array, split into left and right and call for them.
      // The middle is a piece that has already been processing
      $left   = $haystack['left'];
      $middle = $haystack['mid'];
      $right  = $haystack['right'];
      $left   = hDateHelper($date,$left,$needle,$datearg,$extra);
      $right  = hDateHelper($date,$right,$needle,$datearg,$extra);
      return array('left'=>$left,'mid'=>$middle,'right'=>$right);
   }

   // This path means it is a string, has not been split yet.  If
   // the string we are looking for is not there, just return
   $strpos = strpos($haystack,$needle);
   //echo "<br/>Looking for $needle in $haystack, result $strpos";
   if($strpos===false) return $haystack;

   // Otherwise parse it and recurse
   $left   = substr($haystack,0,$strpos);
   $slhs   = strlen($haystack);
   $slnd   = strlen($needle);
   $right  = substr($haystack,$strpos+$slnd,$slhs-$slnd-$strpos);
   $middle = date($datearg,$date);
   if($extra=='U') $middle=strtoupper($middle);
   if($extra=='L') $middle=strtolower($middle);
   //echo "<br/>The math was $strpos, $slhs, $slnd";
   //echo "<br/>Cut up into -$left- -$middle- -$right-";
   $left   = hDateHelper($date,$left,$needle,$datearg,$extra);
   $right  = hDateHelper($date,$right,$needle,$datearg,$extra);
   return array('left'=>$left,'mid'=>$middle,'right'=>$right);
}

function hDateBuild($item) {
   // End of a chain
   if(!is_array($item)) {
      return $item;
   }
   else {
      return
         hDateBuild($item['left'])
         .$item['mid']
         .hDateBuild($item['right']);
   }
}

/**
name:hDateWords
parm:Unix_ts

Equivalent to calling the PHP function date('l, F j, Y',unix_ts), which
returns a date as "Weekday, Month x, YYYY".
*/
function hDateWords($unixts) {
   return date('l, F j, Y',$unixts);
}


/**
name:hNumber
parm:numb Input
parm:numb ZeroValue
returns:string HTML_Fragment

Returns a "minimal" number.  Trailing decimal is removed
if there are no decimals.  By default a blank string is returned
if the value is zero, but if the second parameter is passed in
then the second parameter is returned instead.
Typical values for second parameter might
be "-0-" or "n/a" or just plain "0".

*/
function hNumber($value,$zero='') {
   if ($value==0) return $zero;
   $retval = ''.$value;
   if(strpos($retval,'.')===false) {
      return $retval;
   }
   else {
      $pos = strpos($retval,'.');
      $left=substr($retval,0,$pos);
      $right=substr($retval,$pos+1);
      while(strlen($right)>0 && substr($right,-1,1)=='0') {
         $right=substr($right,0,strlen($right)-1);
      }
      if(strlen($right)==0) {
         return $left;
      }
      else {
         return $left.'.'.$right;
      }
   }


   //return str_replace('.0','',$retval);
}

/**
name:hNumFormat
parm:numb Input
parm:numb Width  (default to length)
parm:numb Decimals (default zero)
returns:string HTML_Fragment

Returns a number formatted with commas and decimal, padded to the
left.  Useful for generated tables in fixed-width fonts or on reports.

*/
function hNumFormat($value,$width=0,$decimals=0) {
   $retval=number_format($value,$decimals);
   if($width==0) return $retval;
   else return str_pad($retval,$width,' ',STR_PAD_LEFT);
}


/**
name:hImg
parm:string Table
parm:string Value
paym:string Column (optional)
returns:string HTML_Fragment

This routine will return the first image that it can find in the [[apppub]]
directory for the given table and (optionally) column.

If no third parameter is passed in, the routine assumes the second parameter,
"Value", is a value for the given table's primary key.  It looks for any
file in "apppub/$Table" named after $Value and having an extension .jpg,
.png, or .gif.  The routine returns an IMG tag pointing to the first
such image it finds.

If a third parameter is passed,the routine assumes the second parameter,
"Value", is a value of that named column.  It looks for any file in
"apppub/$Table/$Column" named after $Value and having an extension .jpg,
.png, or .gif.  The routine returns an IMG tag pointing to the first
such image it finds.
*/
function hImg($table_id,$value,$column='') {
   $afiles=aImg($table_id,$value,$column);

   // If we found anything, return it
   if(count($afiles)>0) {
      return hImgAppPub($column,$afiles[0]);
   }
   else return '';
}

function hImgAppPub($filename,$column='') {
   $THISROUTINEDEPRECATED=$filename;
   if($column<>'') $column.='/';
   return "<img src='apppub/$column$filename' border=0>";
}

/**
name:ahImg
parm:string Table
parm:string Value
paym:string Column (optional)
returns:array of string HTML_Fragment

This routine is almost identical to [[hImg]] except that it returns
an array of images tags if more than one result is found, and it
returns an empty array if none are found.
*/
function ahImg($table_id,$value,$column='') {
   $afiles=aImg($table_id,$value,$column);
   $retval=array();
   foreach($afiles as $afile) {
      $retval[]=hImgAppPub($afile,$column);
   }
   return $retval;
}

/**
name:aImg
parm:string Table
parm:string Value
paym:string Column (optional)
returns:array of string HTML_Fragment

Returns an array of image names in apppub
*/
function aImg($table_id,$value,$column='') {
   $NEVERUSED=$value;
   $x=$table_id;
   // Get the directory name worked out
   $dir=$GLOBALS['dir']['root'].'apppub/';
   $hcol='';
   if($column<>'') {
      $dir.=$column.'/';
      $hcol=$column.'/';
   }

   // Get a list of files, notice we use backtick
   //  because the linux commands are the easiest
   //  way to do this.
   $tfiles=`ls -1 $dir*.gif $dir*.jpg $dir*.png`;

   // Convert into an array
   return explode("\n",$tfiles);
}


/**
name:hNumberPlus
parm:numb Input
parm:numb ZeroValue
returns:string HTML_Fragment

First passes number through [[hNumber]], then prefixes either a
'+' or '-' sign if positive or negative.  Adds nothing if the value is zero.
*/
function hNumberPlus($value,$zero='') {
   if($value==0) return $zero;
   if($value<=0) return hNumber($value,$zero);
   return '+'.hNumber($value,$zero);
}

/**
name:hMoney

parm:number Input
returns:string HTML_Fragment

Equivalent of number_format($input,2).
*/
function hMoney($input) {
   return number_format($input,2);
}

/**
name:hZip9
parm:string Input
returns:string HTML_Fragment

Inserts a dash into a zip code if it has more than 5 characters and
there is no dash there already.  Allows you to have zip code columns
that do not contain a dash, or that may or may not contain a dash.
*/
function hZip9($input='') {
   if(strlen($input)<6) return $input;
   if(strpos($input,'-')!==false) return $input;
   return substr($input,0,5).'-'.substr($input,5);
}


/* DEPRECATED */
function hSimpleNumber($value) {
   if(intval($value)==$value) {
      return number_format($value);
   }
   else {
      return $value;
   }
}

/* DEPRECATED */
function HTMLE_IMG($src) {
	$src = htmlentities(urlencode($src));
	return "<img src=\"index.php?gp_page=x_object&oname=$src\">";
}

/* DEPRECATED */
function HTMLE_IMG_INLINE($src) {
   if ($src=='') {
      $srcfile = file_get_contents('rax-blank.jpg',true);
      $src = base64_encode($srcfile);
   }
   $pic = 'xx'.rand(10000,99999);
   $F=FOPEN($GLOBALS['AG']['dirs']['root'].'/'.$pic,'w');
   fputs($F,base64_decode($src));
   fclose($F);
   return '<span><image src="'.$pic.'"></span>';
   //return
   //   '<span><object style="float:left;"'
   //   .'  type="image/jpeg" data="data:;base64,'.$src.'">'
   //   .'</object></span>';

      //.'  type="image/jpeg" data="data:;base64,'.$src.'">'
}

/* DEPRECATED */
function hImgFromBytes($table_id,$column_id,$skey,$bytes,$decode=true) {
   $x=$bytes;  //annoying compile error
   $x=$decode;
   // First step is to save the image if it is not already
   // saved in the "dbobj" directory
   $fname=$table_id.'_'.$column_id.'_'.$skey;
   $fdir =AddSlash($GLOBALS['AG']['dirs']['root'])."dbobj/";
   //if(!file_exists($fdir.$fname)) {
      /*
      if ($decode) {
         file_put_contents($fdir.$fname,base64_decode($bytes));
      }
      else {
         file_put_contents($fdir.$fname,$bytes);
      }
      */
   //}
   // Now return some hypertext that refers to this image
   //if (strlen($bytes)>0) {
   //   return '<span><image src="dbobj/'.$fname.'"></span>';
   //}
   //else {
      return '';
   //}
}


// ==================================================================
// ==================================================================
// HTML HYPERLINKS
// ==================================================================
// ==================================================================
/**
name:_default_
parent:HTML Hyperlinks
*/

/**
name:HTML Hyperlinks
parent:Framework API Reference

The Andromeda PHP framework provides a few functions for generating
hyperlinks easily.  Their main purpose is to provide consistency.

All of these functions begin with the letter 'h' and return a snippet
of HTML that can be embedded into an HTML stream.

All of these functions take as a first parameter an optional class
assignment.

*/

/**
name:hLink
parm:string CSS_Class
parm:string Caption
parm:string href
returns: string HTML_fragment

This is a very simple routine that does not save a lot of typing but
avoids a lot of intermixing of HTML and PHP.

The first parameter is an [[Optional CSS Class]].  The link is built
from the "Caption" and the "href" parameters.

This routine was modified on 3/16/07 so that it would continue to work
with friendly URLs.  The two changes are:

* Now returns absolute paths, always begins with '/'.
* Invoke [[tmpPathInsert]] and prefixes the result to the link

!>example
!>php
<div class="moduletable">
  <?=hLink('bolder','Link1','?explicit=links&parm=value')?>
  <?=hLink('','Second Link','?second=example&parm=value')?>
</div>
!<
!<

*/
function hLink($class,$caption,$href,$extra='') {
   $class=hTagParm('class',$class);
   //if(substr($href,0,1)=='&') $href=substr($href,1);
   $prefix='/'.tmpPathInsert();

   // Try to figure out if they need a question mark in front
   // if there is an equal sign but no question mark, put it in front
   if(substr($href,0,1)<>'?') {
      if(strpos($href,'=')!==false && strpos($href,'?')===false) {
         $prefix.='?';
      }
   }
   return "<a href=\"".$prefix.$href."\" ".$class." $extra>".$caption."</a>";
}


/**
name:hLinkPage
parm:string Page_name
returns: string HTML_fragment

This is a simple routine that generates a framework-standard link to
a page.  Please see [[Pages, Classes, and Tables]] for more information
on the definition of a 'page'.

!>example
!>php
<div>
  You can jump straight to <?=hLinkPage('orders')?> from here.
</div>
!<
!<


*/
function hLinkPage($class,$page_id) {
   // Load the list of pages.
   $PAGES='explicit assignment avoids compiler warning';
   include('ddpages.php');
   $caption=ArraySafe($PAGES,$page_id,'Link to unknown page: '.$page_id);
   return hlink($class,$caption,"?gp_page=".urlencode($page_id));
}

/**
name:hjxCheckFirst
parm:string Caption
parm:string href

Returns a hyperlink that will invoke the javascript [[CheckFirst]] function
before executing the link. The [[CheckFirst]] function makes sure it is safe
to leave the current page, saving changes first and things like that.

This function always builds links that explicitly go to index.php.

There is no provision for specifying the class or id of the object
at this time.  It is expected that the hyperlink will get its styles
defined in descendant selectors.
*/
function hjxCheckFirst($caption,$href) {
   //if (substr($href,0,1)<>'?') $href='?'.$href;
   //$href='index.php'.$href;
   return "<a href=\"javascript:CheckFirst('$href')\">$caption</a>";
}



/**
name:hpHREF
parm:mixed HREF_info
returns: string HTML_property_fragment

Use this routine when putting links that are internal to your site
directly into literal HTML.  This routine is not necessary for links
to outside pages.

The hpHREF routine does two things.  First, it processes your href
string through urlencode.  Second, it prepends the [[Site Prefix]] to
the URL so that your link will work in any run-time situation, such
as a development machine, a development server, or a live server.

If the first parameter is an array, it is converted into a URL string.

!>example:Using hpHREF
!>php:Literal HTML should wrap HREFs
<div id="someid">
Please proceed to the
<a href="<?=hpHREF('?gp_page=somepage')?>">Ordering Page</a>
so that we can process your order.
</div>
!<
!<
*/
function hpHref($parms) {
   if(is_array($parms)) $parms=http_build_query($parms);
   return '/'.tmpPathInsert().$parms;
}

/**
name:hFileUpload
returns: string HTML_fragment

Returns an HTML Input control for a file upload, with a SUBMIT button
that says "Upload Now".  File uploads are automatically moved to
the [[files]] directory by [[index_hidden.php]] and the information about
the file can be retrieved with [[vgfGet]]('files').
*/
function hFileUpload() {
   ?>
   <input type="hidden" name="MAX_FILE_SIZE" value="150000000" />
   <input type="file" name="andro_file">&nbsp;&nbsp;
   <button type="submit" value="1">Upload Now</button>
   <?php
}


/**
name:hLinkPageRow
parm:string CSS_Class
parm:string Caption
parm:string Page
parm:int Column_value
returns: string HTML_fragment

This is a simple routine that does not save a lot of typing but
avoids a lot of intermixing of HTML and PHP.

The first parameter is an [[Optional CSS Class]].  The parameter
"page" is assigned to "gp_page" and the parameter "Column_Value" is assigned
to "gp_colval".
*/
function hLinkPageRow($class,$caption,$page,$colval) {
   $class=hTagParm('class',$class);
   $href="gp_page=".$page."&gp_colval=".urlencode($colval);
   return "<a href=\"?".$href."\" ".$class.">".$caption."</a>";
}

/**
name:hLinkFromStub
parm:string CSS_Class
parm:string Caption
parm:string Extra

This routine is useful when you need to make a lot of links that will be
very similar.  First you assign a default or 'stub' hyperlink by using
[[vgaSet]] to assign a value to 'hLinkStub'.

When hLinkFromStub is called, it adds the value of 'hLinkStub' to the
href for the link it returns.

The first parameter is an [[Optional CSS Class]].  The value of the
"href" is made by combining the [[Global Variable]] 'hLinkStub' to the
value passed in.

This routine strips a leading '&' or '?' from the HREF passed in, and
then prepends an appropriate '&' or '?'.
*/
function hLinkFromStub($class,$caption,$href) {
   $hStub=vgaGet('hLinkStub');
   $hPrefix=$hStub=='' ? '?' : '&';
   if(substr($href,0,1)=='?') $href=substr($href,1);
   if(substr($href,0,1)=='&') $href=substr($href,1);
   return hLink($class,$caption,$hStub.$hPrefix.$href);
}


/**
name:hLinkPopup
parm:string CSS_Class
parm:string Caption
parm:array parms
returns: string HTML_fragment

Similar to [[hLink]], but the link will activate a popup form.
*/
function hLinkPopup($class,$caption,$parms) {
   $class=hTagParm('class',$class);
   $hparms = is_array($parms) ? http_build_query($parms) : $parms;
   return "<a href=\"javascript:Popup('index.php?$hparms','$caption')\""
      ." $class>"
      .$caption."</a>";
}

/**
name:hLinkSetAndPost
parm:string Caption
parm:string GP_Variable_Name
parm:string GP_Value
returns:string HTML_Fragment

Returns a hyperlink that invokes the javascript function [[SetAndPost]],
with caption given by Caption and GP_Variable_Name and GP_Value becoming
the arguments to [[SetAndPost]].

These links are handy for having a button that sets the value of a form
variable and then posts the form.
*/
function hLInkSetAndPost($caption,$gp_var,$gp_val) {
   return
      '<a href="javascript:SetAndPost('
      ."'".$gp_var."','".$gp_val."')\">".$caption."</a>";
}

/* DEPRECATED */
function hLinkPostFromArray($class,$caption,$parms,$hExtra='') {
   $hclass = hTagParm("class",$class);
   $hparms=http_build_query($parms);
   return "<a ".$hExtra." $hclass href=\"javascript:formPostString('$hparms');\">"
      .$caption."</a>";
}

/* DEPRECATED */
/* See: hLinkSetAndPost */
function hLinkPost($caption,$var,$val) {
   $js="SetAndPost('".$var."','".$val."')";
   regHidden($var,'');
   return '<a href="javascript:'.$js.'">'.$caption.'</a>';
}

/* DEPRECATED */
function hLinkArray($caption,$parms,$target='',$class='') {
   return HTMLE_A_Array($caption,$parms,$target,$class);
}
/* DEPRECATED */
function HTMLE_A_ARRAY($caption,$parms,$target="",$class="") {
	if ($target) { $target=' target="'.$target.'" '; }
	if ($class)  { $class =' class="'.$class.'" ';   }
	$parmlist = "";
	foreach($parms as $var=>$value) {
		if ($parmlist<>"") $parmlist.="&";
		$parmlist .= $var."=".urlencode($value);
	}
	return
		'<a href="index.php?'.htmlentities($parmlist).'"'
		.$target
		.$class.'>'
		.$caption
		.'</a>';
}

/* DEPRECATED */
function HTMLE_A_JSCancel() {
	return HTMLE_A_JS("ob('Form1').reset()","Cancel Changes");
}
/* DEPRECATED */
function HTMLE_A_JSSubmit() {
	return HTMLE_A_JS("formSubmit()","Save Changes");
}
/* DEPRECATED */
function HTMLE_A_JS($href,$content,$class="") {
	if ($href)   { $href='href="javascript:'.$href.'"'; }
	if ($class)  { $class='class="'.$class.'"'; }
	return '<a '.$href.' '.$class.'>'.$content.'</a>';
}
/* DEPRECATED */
function HTMLE_A_POPUP($caption,$parms) {
	$parmlist = "";
	foreach($parms as $var=>$value) {
		$parmlist.=ListDelim($parmlist,"&").$var."=".urlencode($value);
	}
	return
		"<a href=\"javascript:Popup('index.php?".htmlentities($parmlist)."',".
		"'".$caption."')\">".$caption."</a>";
}

/* DEPRECATED */
function hLinkImage($pic,$alt,$var,$val,$enabled) {
   $hparms=http_build_query(array($var=>$val));
   if(strpos($pic,'.')===false) {
      $ext="jpg";
   }
   else {
      list($pic,$ext) = explode(".",$pic);
   }
   if ($enabled) {
      return
         "<a href=\"javascript:formPostString('".$hparms."')\">"
         ."<img src=\"images/$pic.$ext\" border=0 alt=\"".$alt."\""
            ." onmouseover=\"this.src='images/$pic-over.$ext'\" "
            ." onmouseout=\"this.src='images/$pic.$ext'\"> "
         ."</a>";
   }
   else {
      return "<img src=\"images/$pic-gray.$ext\">";
   }
}
/* DEPRECATED */
function HTMLE_A_IMG($href,$stub,$alt) {
	return "
<a href=\"".$href."\"
   onmouseout=\"MM_swapImgRestore()\"
	onmouseover=\"MM_swapImage('$stub','','images/".$stub."over.jpg',0)\">
	<img src=\"images/".$stub."reg.jpg\" alt=\"".$alt."\" name=\"$stub\"
	 border=\"0\" id=\"$stub\" />
</a>
";
}

/* DEPRECATED */
function HTMLE_A_STD($caption,$page,$parms="",$target="") {
	if ($parms)  { $parms = "&".$parms; }
	if ($target) { $target = 'target = "'.$target.'"'; }
	return '<a href="index.php?gp_page='.$page.$parms.'" '.$target.'>'.$caption.'</a>';
}

/**
name:hImageFromBytes
parm:string Table_ID
parm:string Column_ID
parm:string PK_Value
parm:string ImageBytes
returns:string HTML_Fragment

Accepts the name of a table and column that is supposed to be a mime-x
type, containing an image.  The PK_Value is primary key value.  Forces
a save of the image to a dynamic file named Table_ID-Column_ID-PK_Value,
and returns an HTML IMG element pointing to the file.
*/
function hImageFromBytes(
   $table_id
   ,$colname
   ,$pkval
   ,$bytes) {

  $filename='dbobj/'.$table_id.'-'.$colname.'-'.$pkval;
  $dirname =$GLOBALS['AG']['dirs']['root'].'/';

  file_put_contents($dirname.$filename,base64_decode($bytes));
  return "<img src=\"$filename\">";
}

/* DEPRECATED */
/* Needs the CSS Class setting, and an option for checked */
function hCheckBox($name,$value) {
   return '<input type="checkbox" name="'.$name.'" value="'.$value.'">';
}

/* DEPRECATED */
/* Needs the CSS Class setting, and an option for checked */
function hCheckBoxFromCBool($name,$cbool='N',$caption) {
   $checked='';
   if($cbool=='Y') $checked=' CHECKED ';
   return '<input type="checkbox" '
      .'name="'.$name.'" value="Y"'
      .$checked .' >'
      .$caption
      .'</input>';
}


/* DEPRECATED */
function hDateVerbose($time) {
   return date('D, F j, Y',$time);
}

/* DEPRECATED */
function hFlagLogin($caption) {
   hidden('gp_flaglogin','');
   $hHref = "javascript:SetAndPost('gp_flaglogin','1')";
   return '<a href="'.$hHref.'">'.$caption.'</a>';

}

/**
name:hMonthWords
parm:number Month
returns:string

Returns the name of the month from the number.

Returns empty string if month is not between 1 and 12.
*/
function hMonthWords($month) {
   $m=array('January','February','March','April','May'
      ,'June','July','September','October','November','December'
   );
   if($month<0) return '';
   if($month>12) return '';
   return $m[$month-1];
}

// ==================================================================
// ==================================================================
// Template Level HTML
// ==================================================================
// ==================================================================
/**
name:_default_
parent:Template Level HTML
*/
// ------------------------------------------------------------------
/**
name:Template Level HTML
parent:Framework API Reference

These are functions that are only used at the very end of processing,
usually inside of your html_main template.  Each one outputs a
significant and very important part of the page.

If you make your own template, you will need to know about these.
*/


/**
name:ehStandardContent
parm:boolean Output_title

This is the grand-daddy function that must be in every template.

During normal processing, control always passes to some instance of
[[x_table2]].  That object always echos HTML directly.  However, that
HTML is being buffered and is captured and saved.  This function
re-echos that HTML directly, plus it outputs all hidden variables
and various other essential goodies.

Invoke this command in the main content area of your template.
*/
function ehStandardContent($dotitle=false) {
   $NEVERUSED=$dotitle;

   if(vgaGet('NOFORM')<>true) {
      ehStandardFormOpen();
   }
	$HTML_nots = aNoticesClean();
   if (count($HTML_nots)>0) {
      echo '<div class="noticebox">';
      foreach ($HTML_nots as $not) {
         echo '<p>'.$not.'</p>';
      }
      echo '</div>';
   }
   ehErrors();
   echo vgfGet("HTML");
   if(!vgfGet('suppress_hidden')) {
       ehHiddenAndData();
   }

   if(vgaGet('NOFORM')<>true) {
      echo "</form>";
      /*
      $scr1=implode("",ArraySafe($GLOBALS['AG'],'fset',array()));
      $scr2=implode("",ArraySafe($GLOBALS['AG'],'freset',array()));
      ?>
      </form>
      <script type="text/javascript">
      function fieldsSet() {
         alert('Ran fieldsset');
         <?=$scr1?>
         alert("end of fieldsSet");
      }

      function fieldsReset() {
         alert('Ran FieldsReset');
         <?=$scr2?>
         alert("end of fieldsReset");
      }
      </script>
      <?php
      */
   }

}

/**
name:ehStandardFormOpen
parm:string FormName
returns:echos HTML

This function outputs a standard HTML FORM open tag that will POST
results back to [[index.php]].  You will need to close the form manually
at then end of your content.  The name of the form should not be 'Form1',
as that will collide with the name of the standard form that is on all
pages.

For all normal Andromeda pages you never need to output an HTML FORM, all
of your main output is always automatically wrapped in a form, and so you
would not normally need this function.

You may need this function in cases where you have other forms on the page
that are outside of the main content, such as a login form that is always
sitting off on the left or something similar.

This function is not meant to save a lot of typing, its purpose is to give
you an HTML FORM that is framework-consistent.  It also helps to avoid
the eternal problem of [[Messy HTML and PHP]].

*/
function ehStandardFormOpen($id='Form1') {
   $x=$id; //annoying jedit compiler warning
   ?>
   <form method="post" action="index.php" id="<?=$x?>"
                 enctype="multipart/form-data"
                 name="Form1" style="height:100%;">
   <?php
}

/* DEPRECATED */
// USE hErrors();
function ehErrors() {
   $aErrors = aErrorsClean();
   if (count($aErrors)>0) {
      echo '<div class="errorbox">';
      if(vgfGet('ERROR_TITLE')=='') {
         // KFD 6/27/07, think this got broken by changes to SQL2 and
         // error reporting system, just take it out
         //echo "There was an error attempting to save:<br/>";
      }
      elseif (vgfGet('ERROR_TITLE')=='*') {
         // do nothing, the asterisk means do nothing
      }
      else {
         echo vgfGet('ERROR_TITLE');
      }
      foreach ($aErrors as $error) {
         // Don't do htmlentities on errors, as they may contain
         // hyperlinks, and they are all system generated so we consider
         // them safe.
         //echo '<p>'.htmlentities($error).'</p>';
         echo '<p>'.$error.'</p>';
      }
      echo '</div>';
   }
}

/**
name:ehHiddenAndData
returns:echos HTML

Echos directly all hidden variables.  Not necessary if you
use [[ehStandardContent]].
*/
function ehHiddenAndData() {
   // Some parts of the framework create data that should
   // be sent out as hidden variables
   $x=vgfGet('gpControls','');
   if(is_array($x)) {
      regHidden('gpControls',base64_encode(serialize($x)));
   }
   // Now take the prior request and pass it through
   $x=aFromgp('gp_');
   foreach($x as $key=>$value) {
      if($value<>'') {
         hidden('gpx_'.$key,$value);
      }
   }

   echo "\n<!-- Hidden and Data value assignments-->\n";
   $x = ArraySafe($GLOBALS['AG'],'hidden',array());
   foreach ($x as $key=>$value) {
      echo
         '<input type="hidden" '.
         ' name="'.$key.'" id="'.$key.'" '.
         ' value="'.$value."\"/>\n";
   }
   $x = ArraySafe($GLOBALS['AG'],'data',array());
   echo "<script language=\"javascript\" type=\"text/javascript\">\n";
   foreach ($x as $key=>$value) {
      echo "ob('".$key."').value='".$value."';\n";
   }
   echo "</script>\n";
   echo "\n<!-- Hidden and Data value assignments  (END)-->\n";
}

/**
name:ehStandardMenu
returns:echos HTML

Echos directly the current user's menu.  Intended to be used with
the "plain vanilla" Andromeda template.
*/
function ehStandardMenu() {
   $menufile = 'menu_'.SessionGet('UID').'.php';
   if (FILE_EXISTS_INCPATH($menufile)){
      include($menufile);
   }
}


// Either display login boxes or say "logged in as"
/**
name:ehLogin
parm:string CSS_Class
parm:string DOM_ID
parm:string Username
parm:bool horizontal
returns:echo

Provides a login/logout box on the screen.

This routine outputs one of two things.  If a user is logged in,
it says, "Welcome -Username-!" and gives a logout button.  If nobody
is logged in, it presents a login box and a password box.

The output is inside of a table.  The items are stacked on top of
each other, so the first row says "Username:" and the second row has
a textbox, the third row says "Password:" and the fourth row has
another textbox, and finally the fifth row has a submit button.

If CSS_Class is provided, the TABLE and TD elements will both get
that class asignment.  If the DOM_ID element is provided, the TABLE
and TD elements will all get that ID assignment.

If the third parameter, Username, is provided, that will be the default
entry in the UserID box.

*/
function ehLogin($class='login',$id='',$username='') {
   ehFWLogin($class,$id,$username);
}
function ehFWLogin($class='login',$id='',$username='') {
   $hclass = hTagParm("class",$class).hTagParm("id",$id);
   $hValue  = hTagParm("value",$username);
   // Continue with original, the horizontal
   if(LoggedIn()) {
   ?>
   <table border=0 <?=$hclass?> >
    <tr>
    <td align="center" <?=$hclass?>>
     <div style="height:3px"></div>
     <span>
      <span style="font-size: 1.2em;" class="login">
      Welcome<br>
      <?=SessionGet('UID')?>!
      </span>
    <span>
    <br><br>
    <a href="?st2logout=1">Logout</a>
    </td>
    </tr>
   </table>
   <?php
   }
   else {
   ?>
   <form action="?gp_page=x_login&gp_posted=1" method="post">
   <table <?=$hclass?>>
     <tr>
      <td <?=$hclass?>>User Login:</td>
     </tr>
     <tr>
      <td ><input type="text" name="loginUID" <?=$hValue?>
            style="width:100%; background:ffffff;
                   color: #333333;
                   font-family: Geneva, Arial, Helvetica, san-serif;
                   font-size: 11px; Border: solid 1px1 #BABABA;"></td>
     </tr>
     <tr>
      <td <?=$hclass?>>Password:</td>
     </tr>
     <tr>
      <td ><input type="password" name="loginPWD" style="width:100%; background:ffffff; color: #333333; font-family: Geneva, Arial, Helvetica, san-serif; font-size: 11px; Border: solid 1px1 #BABABA;"></td>
     </tr>
     <tr>
      <td><input type="submit" value=" Login " name="submit" style="background:ffcc00; color: #000000; font-family: Geneva, Arial, Helvetica, san-serif; font-size: 11px;Border: solid 1px1 #BABABA;"></td>
     </tr>
   </table>
   </form>
   <br><br>
   <a href="?gp_page=x_password">Help with Password</a>
   <?php
   }
}

/**
name:ehLoginHorizontal
returns:echo

Echos a conventional UserID/Password form running horizontally, with
no class definitions, the objects should receive the styles of their
parents.

If the user is logged in, a logout button is also displayed.
*/
function ehLoginHorizontal() {
   if(!LoggedIn()) {
   ?>
      <form action="?gp_page=x_login&gp_posted=1" method="post" style="display:inline">
      UserID:  <input type="text"     size=10 name="loginUID" />
      Password:<input type="password" size=10 name="loginPWD" />
      <input type="submit" value=" Login " name="submit" />
      </form>
      <br/>
      <a href="/<?=tmpPathInsert()?>?gp_page=x_password">Help with Password</a>
   <?php } else { ?>
      <a href="?st2logout=1">Logout <?=SessionGet("UID")?></a>
   <?php } ?>
   <?php
}

/**
name:ehCommands
returns:echo

Echos a command window.  In the default Andromeda template there is a module
named "commands".  If this module is activated, it contains the content
generated by the ehCommands routine.  When an alternate template is used
and you want a command window, all you need is a wide bar.

At this writing we are contemplating moving the button bars up into
the command window.
*/
function ehModuleCommands() {
   ?>
   <script type="text/javascript">
   function DoCommand(e) {
      var kc = KeyCode(e);  // this routine is in raxlib.js
      if(kc==13) {
         var arg1 = encodeURIComponent(ob('object_for_f2').value);
         formPostString('gp_command='+arg1);
      }
   }
   </script>
   <div style="text-align: right">
   <span style="float: left">
   <?php if(vgfGet('x4')!==true) { ?>
   <a      id="object_for_f4"
         href="#"
      onclick="javascript:history.back()">
   (F4) Back</a>&nbsp;&nbsp;
   (F2) Command:
   <input size=20 maxlength=30
            id="object_for_f2"
          name="object_for_f2"
          value="<?=gp('gp_command')?>"
          onclick="this.focus()"
          onkeyup="DoCommand(event)"
      tabindex="0">
   <?php } else { ?>
   <a       id='object_for_f4'
          href='javascript:void(0)'
       onclick="x4GreenScreenTop();"
       >F4: Menu</a>&nbsp;&nbsp;
   <a       id='object_for_f6'
          href='javascript:void(0)'
       onclick="window.open('?gp_page=x4init')"
       >F6: New Window</a>
   <?php } ?>

   <span style="color: red"><?="&nbsp;&nbsp;".vgfGet('command_error')?></span>
   <?php
   if(gpExists('gp_gbt')) {
      ?>
      &nbsp;&nbsp;
      <a href="<?=gp('gp_gbrl')?>"><?=gp('gp_gbt')?></a>
      <?php
   }
   ?>
   </span>

   <span style="padding-right: 10px">
   <?=vgfGet('html_buttonbar')?>&nbsp;&nbsp;&nbsp;&nbsp;
   <?=vgfGet('html_navbar')?>
   <?php if(vgfGet('x4')===true) { ?>
       <?=ehLoginHorizontal()?>
   <?php } ?>
   </span>
   </div>
   <?php
}




// ==================================================================
// ==================================================================
// HTTP FUnctions
// ==================================================================
// ==================================================================
/**
name:_default_
parent:HTTP Functions
*/
/**
name:HTTP Functions
parent:Framework API Reference

These functions make it easier to find things like the current
website's address, without having to go through the $_SERVER superglobal.

Knowing the website's address is especially useful when you have
multiple instances of an application, such as when you have a test/live
setup or when you are hosting it for more than one customer.  These
functions let you write code that can build complete links back to the
site without hardcoding any addresses.
*/

/**
name:httpWebPagePath
returns:string Web_Address

This function returns the complete URL of the current page, as taken from
$_SERVER['HTTP_HOST'] and dirname($_SERVER['REQUEST_URI']), giving
results such as http://www.example.com/path/to/page.

This program will strip off the framework-supported 'fake' paths
of "rpath" and "pages".

*/
function httpWebPagePath() {
   $x=$_SERVER['REQUEST_URI'];
   $y=strpos($x,'/pages/');
   if($y!==false) {
      $x=substr($x,0,$y+1);
   }
   $y=strpos($x,'/rpath/');
   if($y!==false) {
      $x=substr($x,0,$y+1);
   }
   return
      'http://'
      .$_SERVER['HTTP_HOST']
      .$x;
}

/**
name:httpWebSite
returns:string Web_Address

This function returns the URL of the current page without the path, as
taken from $_SERVER['HTTP_HOST'].

*/
function httpWebSite() {
   return 'http://'.$_SERVER['HTTP_HOST'];
}

/**
name:httpHeadersForDownload
parm:string Filespec (dir + file)
parm:boolean Send_As_Attachement (default false)
testtypes:char,bool
test:*,true

This function sends out headers that are appropriate for sending a
file as a download.  The routine does not necessarily support all
headers, to see which ones are supported, send a "*" as the first
parameter and the program will dump supported values out onto
the screen.

By default the content is sent as in-line content.  If the second
parameter is true, a header will be sent indicating the file is being
sent as an attachment.

When using this function, you need to have the [[flag_buffer]] property
of your class set to false, and this must be set in the [[custom_construct]]
method, as in the example below.

!>example
!>php
<?php
class sendfile extends x_table2 {
   function custom_construct() {
      $this->flag_buffer=false;
   }

   function main() {
      $filename='/path/to/myfile.mp3';
      httpHeadersForDownload($filename);
      readfile($filename);
      exit; // Exit is required to avoid extraneous output
   }
}
?>
!<
!<

You can add new extensions by declaring an array [[httpMimeTypes]] at
the top of your [[applib.php]] file.

!>example:Adding your own types
!>php
<?php
// file: applib.php
$httpMimeTypes=array(
   'xyz'=>'application/xyz-handler'
);
?>
!<
!<


*/
function httpHeadersForDownload($filespec,$attachment=false) {
   $headers=array(
      'ai'=>'application/postscript'
     ,'aif'=>'audio/x-aiff'
     ,'aifc'=>'audio/x-aiff'
     ,'aiff'=>'audio/x-aiff'
     ,'asc'=>'text/plain'
     ,'atom'=>'application/atom+xml'
     ,'au'=>'audio/basic'
     ,'avi'=>'video/x-msvideo'
     ,'bcpio'=>'application/x-bcpio'
     ,'bin'=>'application/octet-stream'
     ,'bmp'=>'image/bmp'
     ,'cdf'=>'application/x-netcdf'
     ,'cgm'=>'image/cgm'
     ,'class'=>'application/octet-stream'
     ,'cpio'=>'application/x-cpio'
     ,'cpt'=>'application/mac-compactpro'
     ,'csh'=>'application/x-csh'
     ,'css'=>'text/css'
     ,'dcr'=>'application/x-director'
     ,'dir'=>'application/x-director'
     ,'djv'=>'image/vnd.djvu'
     ,'djvu'=>'image/vnd.djvu'
     ,'dll'=>'application/octet-stream'
     ,'dmg'=>'application/octet-stream'
     ,'dms'=>'application/octet-stream'
     ,'doc'=>'application/msword'
     ,'dtd'=>'application/xml-dtd'
     ,'dvi'=>'application/x-dvi'
     ,'dxr'=>'application/x-director'
     ,'eps'=>'application/postscript'
     ,'etx'=>'text/x-setext'
     ,'exe'=>'application/octet-stream'
     ,'ez'=>'application/andrew-inset'
     ,'gif'=>'image/gif'
     ,'gram'=>'application/srgs'
     ,'grxml'=>'application/srgs+xml'
     ,'gtar'=>'application/x-gtar'
     ,'hdf'=>'application/x-hdf'
     ,'hqx'=>'application/mac-binhex40'
     ,'htm'=>'text/html'
     ,'html'=>'text/html'
     ,'ice'=>'x-conference/x-cooltalk'
     ,'ico'=>'image/x-icon'
     ,'ics'=>'text/calendar'
     ,'ief'=>'image/ief'
     ,'ifb'=>'text/calendar'
     ,'iges'=>'model/iges'
     ,'igs'=>'model/iges'
     ,'jpe'=>'image/jpeg'
     ,'jpeg'=>'image/jpeg'
     ,'jpg'=>'image/jpeg'
     ,'js'=>'application/x-javascript'
     ,'kar'=>'audio/midi'
     ,'latex'=>'application/x-latex'
     ,'lha'=>'application/octet-stream'
     ,'lzh'=>'application/octet-stream'
     ,'m3u'=>'audio/x-mpegurl'
     ,'m4u'=>'video/vnd.mpegurl'
     ,'man'=>'application/x-troff-man'
     ,'mathml'=>'application/mathml+xml'
     ,'me'=>'application/x-troff-me'
     ,'mesh'=>'model/mesh'
     ,'mid'=>'audio/midi'
     ,'midi'=>'audio/midi'
     ,'mif'=>'application/vnd.mif'
     ,'mov'=>'video/quicktime'
     ,'movie'=>'video/x-sgi-movie'
     ,'mp2'=>'audio/mpeg'
     ,'mp3'=>'audio/mpeg'
     ,'mpe'=>'video/mpeg'
     ,'mpeg'=>'video/mpeg'
     ,'mpg'=>'video/mpeg'
     ,'mpga'=>'audio/mpeg'
     ,'ms'=>'application/x-troff-ms'
     ,'msh'=>'model/mesh'
     ,'mxu'=>'video/vnd.mpegurl'
     ,'nc'=>'application/x-netcdf'
     ,'oda'=>'application/oda'
     ,'ogg'=>'application/ogg'
     ,'pbm'=>'image/x-portable-bitmap'
     ,'pdb'=>'chemical/x-pdb'
     ,'pdf'=>'application/pdf'
     ,'pgm'=>'image/x-portable-graymap'
     ,'pgn'=>'application/x-chess-pgn'
     ,'png'=>'image/png'
     ,'pnm'=>'image/x-portable-anymap'
     ,'ppm'=>'image/x-portable-pixmap'
     ,'ppt'=>'application/vnd.ms-powerpoint'
     ,'ps'=>'application/postscript'
     ,'qt'=>'video/quicktime'
     ,'ra'=>'audio/x-pn-realaudio'
     ,'ram'=>'audio/x-pn-realaudio'
     ,'ras'=>'image/x-cmu-raster'
     ,'rdf'=>'application/rdf+xml'
     ,'rgb'=>'image/x-rgb'
     ,'rm'=>'application/vnd.rn-realmedia'
     ,'roff'=>'application/x-troff'
     ,'rtf'=>'text/rtf'
     ,'rtx'=>'text/richtext'
     ,'sgm'=>'text/sgml'
     ,'sgml'=>'text/sgml'
     ,'sh'=>'application/x-sh'
     ,'shar'=>'application/x-shar'
     ,'silo'=>'model/mesh'
     ,'sit'=>'application/x-stuffit'
     ,'skd'=>'application/x-koan'
     ,'skm'=>'application/x-koan'
     ,'skp'=>'application/x-koan'
     ,'skt'=>'application/x-koan'
     ,'smi'=>'application/smil'
     ,'smil'=>'application/smil'
     ,'snd'=>'audio/basic'
     ,'so'=>'application/octet-stream'
     ,'spl'=>'application/x-futuresplash'
     ,'src'=>'application/x-wais-source'
     ,'sv4cpio'=>'application/x-sv4cpio'
     ,'sv4crc'=>'application/x-sv4crc'
     ,'svg'=>'image/svg+xml'
     ,'swf'=>'application/x-shockwave-flash'
     ,'t'=>'application/x-troff'
     ,'tar'=>'application/x-tar'
     ,'tcl'=>'application/x-tcl'
     ,'tex'=>'application/x-tex'
     ,'texi'=>'application/x-texinfo'
     ,'texinfo'=>'application/x-texinfo'
     ,'tgz'=>'application/x-gzip'
     ,'tif'=>'image/tiff'
     ,'tiff'=>'image/tiff'
     ,'tr'=>'application/x-troff'
     ,'tsv'=>'text/tab-separated-values'
     ,'txt'=>'text/plain'
     ,'ustar'=>'application/x-ustar'
     ,'vcd'=>'application/x-cdlink'
     ,'vrml'=>'model/vrml'
     ,'vxml'=>'application/voicexml+xml'
     ,'wav'=>'audio/x-wav'
     ,'wbmp'=>'image/vnd.wap.wbmp'
     ,'wbxml'=>'application/vnd.wap.wbxml'
     ,'wml'=>'text/vnd.wap.wml'
     ,'wmlc'=>'application/vnd.wap.wmlc'
     ,'wmls'=>'text/vnd.wap.wmlscript'
     ,'wmlsc'=>'application/vnd.wap.wmlscriptc'
     ,'wrl'=>'model/vrml'
     ,'xbm'=>'image/x-xbitmap'
     ,'xht'=>'application/xhtml+xml'
     ,'xhtml'=>'application/xhtml+xml'
     ,'xls'=>'application/vnd.ms-excel'
     ,'xml'=>'application/xml'
     ,'xpm'=>'image/x-xpixmap'
     ,'xsl'=>'application/xml'
     ,'xslt'=>'application/xslt+xml'
     ,'xul'=>'application/vnd.mozilla.xul+xml'
     ,'xwd'=>'image/x-xwindowdump'
     ,'xyz'=>'chemical/x-xyz'
     ,'zip'=>'application/zip'
   );

   $appheaders=ArraySafe($GLOBALS,'httpMimeTypes',array());
   $result=array_merge($headers,$appheaders);

   // Debugging output, display just the types we support
   $fparts=explode('.',$filespec);
   $ext=strtolower(array_pop($fparts));
   if($ext=='*') {
      echo "<h3>Framework Extensions</h3>";
      hprint_r($headers);
      echo "<h3>Application Extensions</h3>";
      echo "In case of duplicates, application values win.";
      hprint_r($appheaders);
      return;
   }

   $dispo=$attachment ? 'attachment' : 'inline';
   // These two are required to download files on unpatched IE 6
   // systems through SSL
   header('Cache-Control: maxage=3600'); //Adjust maxage appropriately
   header('Pragma:',true);  // required to prevent caching

   // These are the normal ones
   header(
     'Content-disposition: '.$dispo.'; filename="'.basename($filespec).'"'
   );
   header('Content-Type: '.ArraySafe($result,$ext,'text/html'));
   header('Content-Length: '.(string)(filesize($filespec)));
}


// ==================================================================
// ==================================================================
// User Maintenance Routines
// ==================================================================
// ==================================================================
/**
name:_default_
parent:User Maintenance Routines
*/
// ------------------------------------------------------------------
/**
name:User Maintenance Routines
parent:Framework API Reference

These routines are for creating users from inside of applications.
*/

/**
name:UserAdd
parm:string USER_ID
parm:string Password
parm:string Email
parm:cbool User_Active

Adds the user to the system and set password.  Also makes it part of the
login group for the current application.

The fourth parameter defaults to 'Y' and determines if the user should be
started as an active user.

This routine connects to the node manager database itself, you do not
have to connect to the node manager before calling it.

Any errors are registred with [[ErrorAdd]].  Check for success by
calling [[Errors]].  If it returns true the command failed.
*/
function UserAdd($UID,$PWD='',$email='',$user_active='Y') {
   $NEVERUSED=$user_active;
   $UID=MakeUserID($UID);
   scDBConn_Push('usermaint');
   SQL(
      "INSERT INTO users (user_id,member_password,email) "
      ." values ('$UID','$PWD','$email')");
   if(Errors()) {
      scDBConn_Pop();
      return false;
   }
   $app=$GLOBALS['AG']['application'];
   SQL("INSERT INTO usersxgroups (user_id,group_id)"
      ." values ('$UID','$app')");
   //SQL("ALTER USER $UID password '$PWD'");
   scDBConn_Pop();
   return true;
}

/**
name:MakeUserID
parm:string USER_ID_Candidate
returns:string USER_ID

Converts an email address into a string that will be accepted by
Postgres as a valid USER_ID.
*/
function MakeUserID($UID) {
   $UID=str_replace('@','_',$UID);
   $UID=str_replace('.','_',$UID);
   $UID=str_replace('-','_',$UID);
   $UID=strtolower($UID);
   $numbs=array('0','1','2','3','4','5','6','7','8','9');
   if(in_array(substr($UID,0,1),$numbs)) {
      $UID='x'.$UID;
   }
   return $UID;
}

/**
name:LoggedIn
returns:boolean

Returns true if a user has successfully logged in on the current
session, otherwise returns false.
*/
function LoggedIn() {
   // Technically this should never happen.  An empty UID should
   // be turned into the anonymous user.  But if we are wrong, better
   // a blank come back as false than as true.
   //
   if(SessionGet('UID')=='') return false;
   if(SessionGet('UID')==$GLOBALS['AG']['application']) return false;
   return true;
   // return SessionGet('UID')=='anonymous' ? false : true;
}

/**
function:PushToLogin

This function pushes the current [[GET-POST Variables]] to the stack
and then displays the login page.  When a successful login is processed,
the original [[GET-POST Variables]] are restored, and the user returns
to their original destination.  This was coded specifically with
shopping cart checkouts in mind.

This routine makes use of [[gpToSession]], which can be used to create
similar routines.

This routine cannot clear out HTML that has already been sent out, so
it should be called at the beginning of processing.
*/
function PushToLogin() {
   gpToSession();
   objPageMain('x_login');
}


// ==================================================================
// ==================================================================
// Debugging Functions
// ==================================================================
// ==================================================================
/**
name:_default_
parent:Debugging Functions
*/

/**
name:Debugging Functions
parent:Framework API Reference

These two functions provide wrappers to the two similar PHP
functions, so that the output is readable.

*/

/* DEPRECATED */
function HTML_vardump($array) {
	echo "<pre>\n";
	//var_dump(($array));
   print_r($array);
	echo "</pre>";
}


// ==================================================================
// ==================================================================
// Miscellaneous FUnctions
// ==================================================================
// ==================================================================
/**
name:_default_
parent:Miscellaneous Functions
*/
// ------------------------------------------------------------------
/**
name:Miscellaneous Functions
parent:Framework API Reference

These are all functions that do not fit into a neat category
with the rest of the functions.
*/

/**
name:objPage
parm:string Page_Name
returns:Object
flag:framework

Returns an object following Andromeda Object conventions.

If the class Page_Name exists inside of a file by the same name,
then that class is instantiated.

If there is no file by the name of Page_Name, then an instance of
[[x_table2]] is instantiated and initialized for table Page_Name.

If there is no table named Page_Name, an uninitialized instance of
[[x_table2]] is returned.
*/
function objPage($gp_page) {
   return DispatchObject($gp_page);
}

function objReport($oParent,$orient='P') {
   include_once('x_fpdf.php');
   $retval= new x_fpdf($orient);
   $retval->trackback=$oParent;
   return $retval;
}

/* DEPRECATED */
function scTableObject($gp_page) {
   return raxTableObject($gp_page);
}

/* DEPRECATED */
function raxTableObject($gp_page) {
   return DispatchObject($gp_page);
}

/* DEPRECATED */
function scObject($object_name) {
   include_once($object_name.'.php');
   return new $object_name;
}

/* DEPRECATED */
function DispatchObject($gp_page) {
	// Get the One True Class loaded.  All table
	// processing uses it directly or uses a subclass of it
	//
	//include_once("x_table.php");   // old version
	include_once("x_table2.php");  // How can there be two "One True" classes?

	// Always attempt to load a class for the page, and then
   // look for the named class
   $obj_page=null;
	if (FILE_EXISTS_INCPATH($gp_page.".php")) {
		include_once("$gp_page.php");

      $class_page = "x_table_$gp_page";
      if(class_exists($class_page)) {
         // case 1, extension of x_table (original)
         $obj_page = new $class_page();
         $obj_page->table_id = $gp_page;
         $obj_page->x_table_DDLoad();
      }
      elseif (class_exists($gp_page)) {
         // case 2, extension of x_table2 (new way to do it)
         $obj_page = new $gp_page();
      }
	}
   // if no object found, must make a default
   if(is_null($obj_page)) {
      $default=vgaGet('x_table','x_table2');
      if($default=='x_table') {
         $obj_page = new x_table();
         $obj_page->table_id = $gp_page;
         $obj_page->x_table_DDLoad();
      }
      else {
         $obj_page = new x_table2($gp_page);
      }
   }
	return $obj_page;
}

/**
name:objPageMain
parm:string PHP_Class
returns:echos HTML

This routine will accept the name of a class, instantiates an object,
and call's the object's "main" method.  In Andromeda, the "main"
method always outputs HTML.

This is a handy way to "redirect" from one page to another.  If
execution has passed to Page1.main, and the code determines that
execution must go to Page2.main, then you can issue

<div class="PHP">
objPageMain('Page2');
</div>

This routine will ''not'' wipe out HTML that has been output before it
is called.  To avoid the HTML from one page appearing on the next,
be sure to call this routine before HTML has been generated.
*/
function objPageMain($class) {
   $obj=objPage($class);
   $obj->main();
}

/**
name:File_Exists_IncPath
parm:string filename

Returns true if the named file exists in the include path.
*/
function FILE_EXISTS_INCPATH($file) {
    $paths = explode(PATH_SEPARATOR, get_include_path());

    foreach ($paths as $path) {
        // Formulate the absolute path
        $fullpath = $path . DIRECTORY_SEPARATOR . $file;

        // Check it
        if (file_exists($fullpath)) {
            return true;
        }
    }
    return false;
}

/**
name:fsMakeDirNested
parm:string Base_Path
parm:string New_Path

Ensures that a complete directory path exists by issuing successive
"mkdir" commands for each segment of New_Path inside of Base_Path.

If Base_Path is "/var/www/localhost/htdocs/app" and New_Path is
"level1/level2/level3", then this routine issues successive PHP Mkdir
commands until the complete path
"/var/www/localhost/htdocs/app/level1/level2/level3" exists.


*/
function fsMakeDirNested($Base_Path,$New_Path) {
   $adirs=explode('/',$New_Path);
   $sdirs='';
   $dirx=$Base_Path;
   foreach($adirs as $adir) {
      $dirx.="/".$adir;
      if(!is_dir($dirx)) mkdir($dirx);
   }
}

function fsFileFromArray($name,$array,$arrayname) {
   $annoying=$arrayname;
	$retval = "";
   $level = 0;
   $retval=fsFileFromArrayWalk($array,0);

   $retval="<?php
\$$arrayname = array(
$retval
);
?>";
   global $AG;
   file_put_contents($AG['dirs']['dynamic'].$name,$retval);
 //  hprint_r($array);
}

function fsFileFromArrayWalk($array,$level) {
   $retval='';
	foreach ($array as $key=>$value) {
      $key=trim($key);
      if($retval) $retval.=",";
		$retval .= "\n".str_repeat("   ",$level)."'$key'=>";
		if (is_array($value)) {
			$retval.=" array(";
			$retval.=fsFileFromArrayWalk($value,$level+1).")";
		}
		else {
			$retval.="'$value'";
		}
	}
   return $retval;
}


/**
name:fsGets
parm:resource File_handle
returns:string Line

Reads a line from an open file using PHP fgets(), then removes any CR or
LF characters, so it can be split in array or otherwise handled w/o
worries about Unix/Mac/Windows compatibility or stray characters.
*/
function fsGets($FILE) {
   $line=fgets($FILE,5000);
   if(!$line) return $line;
   $line=str_replace(chr(13),'',$line);
   $line=str_replace(chr(10),'',$line);
   return $line;
}

/**
name:AddSlash
parm:string Input
returns:String

Adds a slash to the end of a directory if not already present.
*/
function AddSlash($input,$prefix='') {
	// Justin Dearing 12/26/07, detects windows
    // KFD NOTE: rem'd out because untested on windows
  	$input = trim($input);
  	$prefix = trim($prefix);
  	$dir_delimeter = isWindows() ? "\\" : '/';

   	if ($prefix!='') {
   		if (substr($input,0,strlen($prefix))!=$prefix) {
   			$input = $prefix.$input;
   		}
   	}
	if (substr($input,-1) <> $dir_delimeter) $input.='/';
	return $input;
}


/* DEPRECATED */
function regHidden($varname,$val='') {
   arrDefault($GLOBALS['AG'],'hidden',array());
   $GLOBALS['AG']['hidden'][$varname]=$val;
}
/* DEPRECATED */
function regHiddenRepeat($varname,$default='') {
   $val=cleanGet($varname,$default,false);
   regHidden($varname,$default);
}
/* DEPRECATED */
function regDataValue($varname,$varvalue) {
   arrDefault($GLOBALS['AG'],'data',array());
   $GLOBALS['AG']['data'][$varname]=$varvalue;
}

/**
name:ehFWDevNotice
returns:echo

Displays a notice that says "This page is waiting for design".  Intended
to be used during development for pages that must be viewable by staff
and clients, but which have not been layed out yet by a designer.  Usually
a page like this will have plain-vanilla dumps of details from a database,
so that a designer knows what must appear on the final page.

The notice is put into a DIV block of class "devnotice".  That class
is defined in the appropriate CSS skin file (default: [[skin_tc.css]]).

*/
function ehFWDevNotice() {
   ?>
   <div class="devnotice">This page is waiting for design</div>
   <?php
}

/**
name:UTSFirstofMonth
parm:Unix_TS date_input
returns:Unix_TS
group:Date/Time Functions

Returns a Unix timestamp of the first day of the month.  If a date is
passed in, returns the first day of that month, else the first day of
the current month.
*/
function UTSFirstOfMonth($dx=null) {
   if(is_null($dx)) $dx=time();
   $date=SdFromUnixTS($dx);
   return strtotime(
      substr($date,4,2)
      .'/01/'
      .substr($date,0,4)
   );
}

/**
name:UTSFirstofYear
parm:Unix_TS date_input
returns:Unix_TS
group:Date/Time Functions

Returns a Unix timestamp of the first day of the year.  If a date is
passed in, returns the first day of that month, else the first day of
the current year.
*/
function UTSFirstOfYear($dx=null) {
   if(is_null($dx)) $dx=time();
   $date=SdFromUnixTS($dx);
   return strtotime('01/01/'.substr($date,4,2));
}

function unixtsFromSD($sd) {
    return strtotime(
      substr($sd,4,2).'/'
      .substr($sd,6,2).'/'
      .substr($sd,0,4));
}


/**
name:Paypal_SimulatePaid
parm:array Paypal_info

Call this function to simulate a successful payment on paypal.  More
information is available at our [[Paypal]] page.
*/

function Paypal_SimulatePaid($paypal) {
   gpSet('invoice',$paypal['invoice']);
   $log=SysLogOPen('PAYPAL');
   PayPal_IPN_Success($log);
   SysLogClose($log);
}

// ==================================================================
// ==================================================================
// ARRAY AND LIST ROUTINES
// ==================================================================
// ==================================================================
/**
name:_default_
parent:Array Functions
*/
/**
name:Array Functions
parent:Framework API Reference

Array functions exist to supplement PHP's already impressive and
powerful array library.

Some function shere provide provide the general
Andromeda flavor to things, such as "ArraySafe" which provides the
Andromeda behavior of a [[Standard Default Value]].

Other functions provide the kind of handling you need for database
rows or data dictionary activities.
*/

/**
name:Rows Array

A numerically indexed array of [[Row Array]]s.  This would be returned by
from [[SQL_AllRows]].

*/
/**
name:Associative Rows Array

An array of [[Row Array]]s in which the key value was take from one
of the columns.

These arrays are very useful when the index column is a single-column
primary key of a table.  Use [[KeyRowsFromRows]] to get one of these
nifty arrays.
*/

/**
name:Mixed Rows Array

A complex associate array.  The keys at the top level all name
tables, and point to [[Rows Array]]s.
*/

/* DEPRECATED */
function ArrayDefault(&$arr,$key,$value) {
	if(!isset($arr[$key])) { $arr[$key]=$value; }
}


/* NO DOCUMENTATION */
function ArrayKeyAndValue(&$arr,$colkey,$colvalue) {
	$retval = array();
   if (is_array($arr)) {
      foreach ($arr as $onerow) {
         $retval[trim($onerow[$colkey])] = $onerow[$colvalue];
      }
   }
	return $retval;
}

// returns a number-indexed array of values from the
// numbered "column" in an rows array
//
function arrFromColumn($arr,$index=0) {
    $retval = array();
    foreach($arr as $row) {
        $retval[] = trim($row[$index]);
    }
    return $retval;
}

/**
name:arrofArrays
parm:array Keys
returns:Array

Generates an associative array of keys pointint to empty arrays. The
keys are taken from the input.
*/
function arrOfArrays($keys) {
   $retval=array();
   foreach($keys as $key) {
      $retval[$key]=array();
   }
   return $retval;
}

/**
function:aSliceFromKeys
parm:array Haystack
parm:array Needles
parm:bool fully_populate
returns:array Row

Accepts a [[Row Array]], the haystack, and builds a new Row Array
using only the keys found in [[List Array]] Needles.

The third parameter, fully_populate, determines what happens when
an item in Needles is not found in Haystack.  By default the value is
false and the returned array contains no entry for the missing value.
If the third parameter is true, the return array contains an empty
element for the missing value.

*/
function asliceFromKeys(&$haystack,$needles,$fullpop=false) {
   if(!is_array($needles)) $needles=explode(',',$needles);

   $retval=array();
   foreach($needles as $needle) {
      if(isset($haystack[$needle])) {
         $retval[$needle] = $haystack[$needle];
      }
      else {
         if($fullpop) {
            $retval[$needle] = '';
         }
      }
   }
   return $retval;
}

function asliceValsFromKeys(&$source,$keyvaltopull,$keylist) {
   if(!is_array($keylist)) $keylist=explode(',',$keylist);
   $retval=array();
   foreach($keylist as $key) {
      if(isset($source[$key][$keyvaltopull])) {
         $retval[$key] = $source[$key][$keyvaltopull];
      }
   }
   return $retval;
}


// Recursive version of built-in function
function raxarr_Change_Key_Case($array,$case=CASE_LOWER) {
   $retval = array_Change_key_Case($array,$case);
   $keys = array_keys($retval);
   foreach($keys as $key) {
      if (is_array($retval[$key])) {
         $retval[$key] = raxarr_Change_Key_Case($retval[$key]);
      }
   }
   return $retval;
}

function arrDefault(&$array,$key,$value) {
   if(!isset($array[$key])) $array[$key]=$value;
}

/**
name:arrayStripNumericIndexes
parm:array Input

Processes an array an unsets any numeric indexes.
*/
function arrayStripNumericIndexes(&$array) {
   $keys =array_keys($array);
   foreach($keys as $key) {
      if(is_numeric($key)) {
         unset($array[$key]);
      }
   }
}

/* NO DOCUMENTATION */
function raxarr_PrefixAdd($array,$prefix,$recurse=true,$lower=false) {
   $retval = array();
   $keys = array_keys($array);
   foreach($keys as $key) {
      $sub = $array[$key];
      $keynew= is_numeric($key) ? $key : $prefix.$key;
      $keynew= $lower ? strtolower($keynew) : $keynew;
      if ($recurse && is_array($sub)) {
         $sub = raxArr_PrefixAdd($sub,$prefix,$recurse,$lower);
      }
      $retval[$keynew]=$sub;
   }
   return $retval;
}

/* NO DOCUMENTATION */
function avkFromRows(&$rows,$colname) {
   return ancFromRows($rows,$colname);
}
/* NO DOCUMENTATION */
function ancFromRows(&$rows,$colname) {
   $retval=array();
   $rowkeys=array_keys($rows);
   $count=0;
   foreach($rowkeys as $rowkey) {
      $retval[$rows[$rowkey][$colname]]=$count;
      $count++;
   }
   return $retval;
}
/* NO DOCUMENTATION */
function asrFromMixed(&$array) {
   $retval=array();
   $keys=array_keys($array);
   foreach($keys as $key) {
      if (is_numeric($key) && !is_array($array[$key])) {
         $retval[$array[$key]] = array();
      }
      else {
         $retval[$key] = $array[$key];
      }
   }
   return $retval;
}

/**
name:AAFromRows
parm:array Rows_Array
parm:string Key_Column
parm:string Value_Column
returns:array

Processes a [[Rows Array]] and returns an associative array.  The
resulting array is a simple associative array.  One column is used
to generate the key values and the other column is used to assign
values to the array elements.
*/
function AAFromRows($rows,$colkey,$colval) {
   $aa = array();
   foreach($rows as $row) {
      $aa[$row[$colkey]]=$row[$colval];
   }
   return $aa;
}

/**
name:KeyRowsFromRows
parm:array Rows_Array
parm:string Key_Column
returns:array

Processes a [[Rows Array]] and returns an [[Associative Rows Array]].

For each row in the input, the value of Key_Column is used as the
key value in the resulting array.  The individual rows are the same
in both input and output, only the key is different.
*/
function KeyRowsFromRows($rows,$colkey) {
   $aa = array();
   foreach($rows as $row) {
      if(isset($row[$colkey])) {
         $aa[$row[$colkey]] = $row;
      }
   }
   return $aa;
}


// ==================================================================
// ==================================================================
// HTML FOR PRINT
// CODE PURGE KFD 7/6/07, lose this entire section
// ==================================================================
// ==================================================================
/**
name:_default_
parent:HTML For Print
*/
/**
name:HTML For Print
parent:Framework API Reference

These routines output HTML fragments that are suitable for
printed output.
*/

/**
name:HTMLP_Pos
parm:string HTML_Fragment
parm:int top
parm:int left
parm:string CSS_Class
parm:string CSS_Style

Returns an HTML DIV element.  The DIV will be absolutely positioned
at coordinates top and left.
*/
/*
function HTMLP_Pos($HTML,$top=0,$left=0,$class="",$style="") {
	return "\n".
		'<div class="pabs "'.$class.'"'.
		' style="top: '.$top.'mm; left: '.$left.'mm; '.$style.'">'.
		$HTML.
		"</div>\n";
}
*/

/**
name:HTMLP_PageBreak
parm:int Pagenumber

Outputs a page break.
*/
/*
function HTMLP_PageBreak(&$pageno) {
	$retval = "";
	if ($pageno > 1) $retval = "\n<div class=\"pagebreak\"></div>";
	$pageno++;
	return $retval;
}
*/

// ==================================================================
// ==================================================================
// Report function stub(s)
// ==================================================================
// ==================================================================
/**
name:Reporting System

All reports are run from a common reporting system.  It has a single
object, [[x_report]], which can be used to run reports.

There is a stub function, [[ehReport]], that can be embedded into HTML
pages and which displays the actual output of a report.
*/
/**
name:ehReport
parent:Reporting System
parm:string Report_ID
parm:string Display

This function runs a report and echos the output directly.  The first
parameter names the report to run.  The second parameter can be either
'HTML' or 'PDF'.

A PDF report is a paged PDF document, while an HTML report is a single
long document with a header at top and a footer at bottom and the content
in a scrollable div in the middle.
*/
function ehReport($report_id,$display) {
   include_once('x_report.php');
   $oReport=new x_report($report_id,$display);
   $oReport.ehMain();
}

// ==================================================================
// ==================================================================
// WIKI ROUTINES
// ==================================================================
// ==================================================================
/**
name:_default_
parent:Wiki Functions
*/
/**
name:Wiki Functions
status:EXPERIMENTAL
parent:Framework API Reference

These are a collection of experimental routines that allow wiki-like
processing.

Most of the wiki processing is in [[x_docview.php]].

*/
/**
name:hWiki
parm:string PagesTable
parm:string PageText
parm:bool Use_Name_For_Title

Takes wiki-formatted text and returns HTML.  The first parameter names the
table that the wiki pages are stored in, the second parameter names the
page.

The third parameter instructs the wiki formatter to use the page
name as the title.  This parameter is by default true.  If you pass in
a false, there will be no H1 title on the page.

It is assumed that the table of pages has a column 'pagename' and a
column 'pagewiki'.

The wiki functionality is stored in the class [[x_wiki]].  This function
instantiates x_wiki and hands processing to that class.
*/
function hWiki($table_id,$pagename,$flag_title=true) {
   include_once('x_wiki.php');
   $wiki=new x_wiki($table_id);
   return $wiki->hWikiFromTable($table_id,$pagename,$flag_title);
}
function hWikiFromText($text) {
   $table_id='NEVERUSED';
   include_once('x_wiki.php');
   $wiki=new x_wiki($table_id);
   return $wiki->hWikiFromText($text);
}



/**
name:adocs_makeMenu
parm:string Page_Root
parm:string Page_Current
parm:array Parents
parm:array Peers
returns:stores Menu

This routine generates a menu and stores it with vgaSet('menu').
For an example of its use, see the source code for the Andromeda
documentation.
*/
// CODE PURGE, almost certainly can lose this
function adocs_makeMenu($pageroot,$pn,$parents=array(),$peers=array()) {
   $menu0=adocs_Menulink($pageroot,'class="bigger"');

   $menu1='';
   if(count($parents)>1) {
      $menu1=adocs_Menulink($parents[count($parents)-1],'class="bigger"');
   }

   $menu2='';
   foreach($peers as $peer) {
      $class=$peer==$pn ? 'class="selected"' : '';
      $menu2.=adocs_MenuLink($peer,$class);
   }

   if($menu1<>'' || $menu2<>'') {
      if($menu1<>'') {
         $menu0.="<hr>";
         if ($menu2<>'') {
            $menu1.="<hr>";
         }
      }
      else {
         $menu0.="<hr>";
      }
   }


   vgaSet('menu',$menu0.$menu1.$menu2);
   return;
}

/**
name:adocs_MenuLink
parm:string pagename
parm:string class (optional)

Accepts a wiki page name, such as "PHP Framework" and generates a
link to that page, using itself as the caption.
*/
function adocs_MenuLink($pagename,$class='') {
   return
      "<a $class href=\"?gp_page=x_docview&gppn=".urlencode($pagename)."\">"
      .$pagename
      ."</a>";
}


// ==================================================================
// ==================================================================
// LANGUAGE EXTENSIONS
// ==================================================================
// ==================================================================
/**
name:_default_
parent:PHP Compatibility Functions
*/
/**
name:PHP Compatibility Functions
parent:Framework API Reference

These are functions to replace functions deprecated or removed from
PHP that have no replacement as simple or useful as the original.

*/

/**
name:mime_content_type
parm:string FileSpec
parm:string mime type

Replaces the nifty PHP function mime_coment_type which does not exist
on the gentoo version of PHP due to a misunderstanding between the
words 'deprecated' and 'eliminate with extreme prejudice'.

There can be no meaningful explanation for why something so simple and
useful was removed and replaced with something much more complicated.
*/
if(!function_exists('mime_content_type')) {
   function mime_content_type($filename) {
      $command='file -bi '.escapeshellarg ($filename);
      echo $command;
      ob_start();
      $retval=system(trim($command)) ;
      ob_end_clean();
      return $retval;
   }
}


// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
// ==================================================================
// ==================================================================
//
// DOCUMENTATION LINE.
//
// EVERYTHING ABOVE HERE has been reviwed and documented.
// Everything below has not.
//
// Some of the lower stuff however is very important framework
// stuff.  Just because it is undocumented does not mean it is
// unimportant.
//
// ==================================================================
// ==================================================================
// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


function minmax($val1,$val2) {
   return array(min($val1,$val2),max($val1,$val2));
}

function aNextPrevFromContext($table_id,$skey=0) {
   // early abort
   if ($skey==0) return array(0,0);

   // Pull context and next early abort
   $skeys = ContextGet("tables_".$table_id."_skeys",array());
   if(!isset($skeys[$skey])) return array(0,0);

   // Get the keys as their own array, so we can go back/forward
   // Note that everything is zero-indexed
   $skordinal=array_flip($skeys);
   $skprev = $sknext = 0;
   if ($skeys[$skey]<>0) {
      $skprev = $skordinal[$skeys[$skey]-1];
   }
   if ($skeys[$skey]<>count($skeys)-1) {
      $sknext = $skordinal[$skeys[$skey]+1];
   }
   return array($skprev,$sknext);
}

function sqlOBFromContext($table_id) {
   $table=DD_TableRef($table_id);

   // Get old and new
   $obold    = ContextGet("tables_".$table_id."_orderby");
   $obnew    = CleanGet("gp_ob_".$table_id,'',false);
   $obascold = ContextGet("tables_".$table_id."_orderasc");

   // Possibility 1, new values are blank, no new request
   if ($obnew=='') {
      // 1.A, old is also blank, clear it out return blank
      if ($obold=='') {
         ContextSet("tables_".$table_id."_orderby" ,"");
         ContextSet("tables_".$table_id."_orderasc","");
         return '';
      }
      // 1.B, old is there, use it
      else {
         return "ORDER BY $obold $obascold";
      }
   }
   // Changed value means accept as ascending.  Repeat
   // means flip ascending/descending
   if ($obold <> $obnew) {
      // for new column, always go ascending
      $obascnew= 'ASC';
      ContextSet("tables_".$table_id."_orderby" ,$obnew);
      ContextSet("tables_".$table_id."_orderasc","ASC");
   }
   else {
      // if repeating the same column, flip ascending/descending
      $obascnew = ($obascold=="ASC") ? "DESC" : "ASC";
      ContextSet("tables_".$table_id."_orderasc",$obascnew);
   }
   ContextSet("tables_".$table_id."_orderby",$obnew);

   return "ORDER BY $obnew $obascnew ";
}
// ------------------------------------------------------------------
// Drilldown routines.
// ------------------------------------------------------------------
function DrilldownReset() {
	//$keys = array_keys($_SESSION);
   $keys = array_keys($GLOBALS['gpContext']);
	foreach ($keys as $key) {
		if (substr($key,0,9)=="drilldown") {
			unset($GLOBALS['gpContext'][$key]);
		}
	}
	ContextSet("drilldown_level",0);
}

function DrilldownLevel() {
	return ContextGet('drilldown_level',0);
}

function DrilldownGet($level) {
	return ContextGet("drilldown_".$level);
}
function DrillDownTop() {
   $dd=ContextGet("drilldown");
   return array_pop($dd);
}

function DrilldownValues($nesting=0) {
	$level=DrilldownLevel()-$nesting;
	return DrilldownGet($level);
}

/**
name:DrillDownMatches
returns:array Row

Returns a [[Row Array]] specifying the columns to match to produce
a drilldown resultset in a child table.

*/
function DrillDownMatches() {
   $dd = ContextGet('drilldown',array());
   if(count($dd)==0) {
      return array();
   }
   else {
      $dd0=array_pop($dd);
      return $dd0['matches'];
   }
}
// ------------------------------------------------------------------
// File handling functions
// Dynamic functions are mixed in here, need to be sorted out
// ------------------------------------------------------------------
function DynFromh($filename,$contents) {
   DynamicSave($filename,$contents);
}

function DynamicSave($filename,$contents) {
	$FILE=fopen($GLOBALS["AG"]["dirs"]["dynamic"]."/".$filename,"w");
	fwrite($FILE,$contents);
	fclose($FILE);
}
function DynamicLoad($filename) {
	$file = $GLOBALS["AG"]["dirs"]["dynamic"]."/".$filename;
	if (file_exists($file))
		return file_get_contents($file);
	else
		return "";
}
function DynamicClear($filename) {
	$file = $GLOBALS["AG"]["dirs"]["dynamic"]."/".$filename;
	if (file_exists($file)) unlink($file);
}

function hFromDyn($filename) {
   return DynamicLoad($filename);
}


function CacheMember_Profiles() {
   $sq="select * from member_profiles "
      ." where user_id='".SessionGet('UID')."'";
   $mp=SQL_OneRow($sq);
   DynFromA('member_profiles_'.SessionGet('UID'),$mp);
}

/**
name:aFromDyn
parm:string Key
returns:array

Looks for the cached elemented named by "Key".  If found, returns it,
if not found returns an empty array.

Expects the element to be an array.  Saving a scalar value and then
using this function to retrieve it produces undefined results.
*/
function aFromDyn($filename) {
   $serialized=DynamicLoad($filename);
   if(empty($serialized)) return array();
   else return unserialize($serialized);
}
/**
name:FromDynA
parm:string Key
parm:array AnyArray

Caches an array for later retrieval by [[aFromDyn]].  The cache is
visible to all users in all sessions.
*/
function DynFromA($filename,$contents) {
   DynamicSave($filename,serialize($contents));
}


function CellGet($table_id,$val,$col) {
   $retval=CacheRead('table_'.$table_id.$val.$col);
   if($retval=='') {
      $table=DD_TableRef($table_id);
      $sq="SELECT $col FROM $table_id "
         ." WHERE ".$table['pks'].' = '
         .SQL_Format($table['flat'][$table['pks']]['type_id'],$val);
      $retval= SQL_OneValue($col,$sq);
      CacheWrite('table_'.$table_id.$val.$col,$retval);
   }
   return $retval;
}
function rowsFromCache($name,$sq) {
   $ser=CacheRead($name);
   if($ser=='') {
      $rs=SQL_AllRows($sq);
      CacheWrite($name,serialize($rs));
   }
   else {
      $rs=unserialize($ser);
   }
   return $rs;
}
function CacheRead($name) {
   return DynamicLoad($name);
}
function CacheWrite($name,$value) {
   DynamicSave($name,$value);
}

// ------------------------------------------------------------------
// General Purpose Element listing, with specialized output
// ------------------------------------------------------------------
function ElementAdd($type,$msg) {
    if($type=='script' || $type=='jqueryDocumentReady') {
        $msg = preg_replace("/<script>/i",'',$msg);
        $msg = preg_replace("/<\/script>/i",'',$msg);
    }
    $GLOBALS["AG"][$type][]=$msg;
}
function ElementInit($type) { $GLOBALS["AG"][$type]=array(); }
function ElementReturn($type,$default=array()) {
	global $AG;
   if(!isset($AG[$type])) return $default;
   else return $AG[$type];
}
function Element($type) {
	global $AG;
   if(!isset($AG[$type])) return false;
	if (count($AG[$type])>0) return true; else return false;
}
function ElementImplode($type,$implode="\n") {
    if(!isset($GLOBALS['AG'][$type])) return '';
    else return implode($implode,$GLOBALS['AG'][$type]);
}
function ElementOut($type,$dohtml=false) {
	global $AG;

   // Hardcoded row we want in there
   if($type=='script') {
      //$calcRow=vgaGet('calcRow');
      //ElementAdd('script',"\nfunction calcRow() {\n$calcRow\n}");

      $ajaxTM=vgfGet('ajaxTM',0)==1 ? '1' : '0';
      ElementAdd('script',"\nvar ajaxTM=$ajaxTM  /* Controls AJAX table maintenance */");

      //$clearBoxes=implode("\n",ArraySafe($AG,'clearBoxes',array()));
      //ElementAdd('script',"\nfunction clearBoxes() {\n$clearBoxes\n}");
   }

   $array=ArraySafe($AG,$type,array());
	$retval="";
	$extra="";
	if ($dohtml) { $extra="<br/>"; }
   foreach ($array as $msg) {
      $retval.=$msg.$extra."\n";
   }
	$AG[$type]=array();
	if (!$dohtml) { return $retval; }
	if (!$retval) { return ""; }
	else { return "<div class=\"$type\">$retval</div>"; }
}


// ------------------------------------------------------------------
// UNDOCUMENTED HTML FUNCTIONS
// ------------------------------------------------------------------
/**
name:HTML Output

These functions all return or output fragments of HTML.

Some of them output huge amounts of HTML, while others have the
advantage of avoiding a confusing mix of HTML and PHP.
*/

/* DECPRECATED */
function HTML_Format_DD($table_id,$colname,$value) {
   $table=DD_TableRef($table_id);
   $type = $table['flat'][$colname]['type_id'];
   return HTML_Format($type,$value);
}



/* DEPRECATED */
// see hSanitize
function HTML_Sanitize($v) {
   return htmlentities($v);
}

function hSanitize($v) {
   return htmlentities($v);
}

/**
name:hx
parm:string Anything
returns:string HTML_Sanitized

This is a shortcut to PHP's function htmlentities.
*/
function hx($in) {
   return htmlentities($in);
}

function hFormat($t,$v) {
   return HTML_Format($t,$v);
}

/**
name:hFormat
parm:string Type_id
parm:mixed value

Returns the value in generic format suitable for the type.
*/

function HTML_Format($t,$v) {
   switch ($t) {
      case 'mime-x':
         return HTMLE_IMG_INLINE($v);
         break;
		case "char":
		case "vchar":
		case "text":
		case "url":
		case "obj":
		case "cbool":
      case 'ssn':
      case 'ph12':
		case "gender":
			return htmlentities(trim($v));
			break;
		case "dtime":
         if(!is_numeric($v)) {
            if($v=='') return '';
            $v=strtotime($v);
         }
         return date('m/d/Y h:m:s',$v);
			//if ($v=="") return "";
			//else return HTML_TIMESTAMP($v);
			break;
		case "date":
         if(!is_numeric($v)) {
            if($v=='') return '';
            $v=strtotime($v);
         }
         return hDate($v,'mm/dd/yyyy');
		case "money":
		case "numb":
		case "int":
			if ($v=="") { return "0"; } else { return trim($v); }
		case "time":
			// Originally we were making users type this in, and here we tried
			// to convert it.  Now we use time drop-downs, which are nifty because
			// the display times while having values of numbers, so we don't need
			// this in some cases.
			//if (strpos($v,":")===false) {	return $v; }
			//$arr = explode(":",$v);
			//return ($arr[0]*60) + $arr[1];
         return hTime($v);
	}
}



// ELIMINATE: Nothing should reference this code,
//            these routines should be eliminated
function hHidden($key,$value) {
   return html_hidden($key,$value);
}
function HTML_Hidden($key,$value) {
	return
		'<input type="hidden" '.
		' name="'.$key.'" id="'.$key.'" '.
		' value="'.$value."\"/>\n";
}

function HTML_Dropdown($name,$resall,$value="value",$inner="inner") {
	$retval = "<select id=".$name." name=$name>";
	foreach ($resall as $row) {
		//echo "Reading a row";
		$retval .= "<option value=\"".$row[$value]."\">".$row[$inner]."</option>";
	}
	$retval.= "</select>";
	return $retval;
}


/* DEPCRECATED */
function HTML_DATE($date) {
	return strftime('%b %d, %Y',$date);
}
function hDateUSFromSD($sd) {
   return
      intval(substr($sd,4,2)).'/'
      .intval(substr($sd,6,2)).'/'
      .intval(substr($sd,0,4));
}

/* DEPRECATED */
function HTML_DATEINPUT($date) {
	if (!$date) { return ""; }
	$year = substr($date,0,4);
	$mnth = substr($date,5,2);
	$day  = substr($date,8,2);
	return $mnth."-".$day."-".$year;
}
function HTML_TEXTDATE($date) {
	if (is_null($date)) { $date = time(); }
	return date('m/d/Y',$date);
}

// ==================================================================
// HTML Element Generation
// ==================================================================

function urlParmsFromArray($array,$prefix='') {
   $retval = '';
   foreach($array as $key=>$value) {
      $retval.=
         ListDelim($retval,'&')
         .$prefix.$key.'='
         .urlencode($value);
   }
   return $retval;
}

function ehTBodyFromSQL($sq) {
   $rows=SQL_AllRows($sq);
   ehTBodyFromRows($rows);
}

/**
name:hTbodyFromRows
returns: string HTML
parm:string CSS_class
parm:array Rows

accepts an [[Array of Rows]] and returns a list of HTML TR elements,
where each row is a TR, and each element of each array becomes an HTML
TD element.

Note that the keys of the [[Row Array]]s are not used, so they
can actually be [[List Array]]s.

Each table element is of class "CSS_class", or no class if the first
parameter is blank.  The first parameter may be blank, but it must
be provided.
*/
function hTBodyFromRows($class='',$rows) {
   $retval='';
   foreach($rows as $row) {
      $retval.=hTRFromArray($class,$row);
   }
   return $retval;
}

/* DEPRECATED */
/* The basic problem with this routine is that we tried to do
   everything, and it got unwieldy.  Later we figured out the idea
   was to have a lot of more specific hTable routines, these
   are named hTable_Method*
 */
function ehTBodyFromRows(&$rows,$columns=array(),$options=array()) {
   // For alternating dark/lite
   $flag_alt=false;
   if(isset($options['alternate'])) {
      $flag_alt = true;
   }
   $cssRow   = 'dlite';

   // Error check the parameters
   if(!is_array($rows)) {
      ErrorAdd("ehTBodyFromRows: 1st parm must be array of rows");
   }
   if(!is_array($columns)) {
      ErrorAdd("ehTBodyFromRows: 2nd parm must be array of columns");
   }
   // Create columns if it was not provided.
   if(count($columns)==0) {
      $colspre = array_keys($rows[0]);
      foreach($colspre as $colname) {
         if(!is_numeric($colname)) {
            if($colname!='skey') {
               $columns[$colname]=array();
            }
         }
      }
   }

   // Now flesh out various defaults, set hidden vars
   foreach($columns as $colname=>$colopts) {
      if(isset($colopts['cpage']) && !isset($colopts['ccol'])) {
         $columns[$colname]['ccol']='skey';
      }
      if (isset($columns[$colname]['ccol'])) {
         hidden('gp_'.$columns[$colname]['ccol'],'');
      }
   }

   // Run through the rows and output them
   $makehidden='';
   foreach($rows as $row) {
      echo "<tr>";
      foreach($columns as $colname=>$colopts) {
         $value=$row[$colname];
         if(isset($colopts['cpage'])) {
            $pg  =$colopts['cpage'];
            $ccol=$colopts['ccol'];
            $cval=$row[$ccol];
            $js = "SetAction('gp_page','$pg','gp_$ccol','$cval')";
            $value='<a href="javascript:'.$js.'">'.$value.'</a>';
         }
         echo hTD($cssRow,$value);
         if ($flag_alt) {
            $cssRow = $cssRow=='dlite' ? 'ddark' : 'dlite';
         }
      }
      echo "</tr>";
   }
}

/**
name:hTRFromArray
returns:string HTML
parm:string CSS_Class
parm:array Row
group:HTML Tables

Returns a complete TR row, with each element of the Row array
becoming an HTML TD element.  Each TD is assigned the CSS class name
of CSS_Class unless the first parameter is an empty string.
*/
function hTRFromArray($class,$array) {
   $retval="\n<tr>";
   foreach($array as $value) {
      $retval.=hTD($class,$value);
   }
   return $retval."\n</tr>";
}

/**
hTDsFromArray
name:hTDsFromArray
group:HTML Tables

html string = <b>hTDsFromArray</b>($class,$array)

Returns one or more TD elements row, with each element of the Row array
becoming an HTML TD element.  Each TD is assigned the CSS class name
of CSS_Class unless the first parameter is an empty string.

*/
function hTDSFromArray($class,$array) {
   $retval='';
   foreach($array as $value) {
      $retval.=hTD($class,$value);
   }
   return $retval;
}


function hTableSortable($table_id,$cols,$class='dhead') {
   // Since the table will be sortable, make a hidden variable
   // to keep the sort value
   $hid='gp_ob_'.$table_id;
   regHidden($hid,'');

   $retval
      ='<table width="100%" border="0" '
      .' cellpadding="0" cellspacing="0"'
      .' style="border-collapse: collapse;">'."\n";
   $retval.='<tr>';
   foreach($cols as $colname=>$colcap) {
      $href="javascript:SaveAndPost('".$hid."','".$colname."')";
      $retval.=
         '<td class="'.$class.'">'
         .'<a href="'.$href.'">'.$colcap.'</a>'
         .'</td>';
   }
   $retval.='</tr>';
   return $retval;
}

/**
name:hTable_methodAlternate
returns:string HTML
parm:array Rows
parm:string CSS_Class1
parm:string CSS_Class2
group:HTML Tables

Generates and returns one or more TR elements, where the rows alternate
between CSS_Class1 and CSS_Class2.  If the two classes have different
background colors, this produces alternating colored rows for the table,
which some people find easier to read.

The first parameter is an [[Array of Rows]].  Each [[Row Array]] becomes
a complete HTML TR element.  The individual elements of each Row become
HTML TD elements.

The class assignments are made to the TD elements.
*/
function hTable_MethodAlternate($rows,$class1,$class2) {
   $class=$class1;
   $retval='';
   foreach($rows as $row) {
      $retval.=hTRFromArray($class,$row);
      $class=($class==$class1) ? $class2 : $class1;
   }
   return $retval;
}


function HTML_SQLTimestamp($val) {
	return date('Y-m-d h:i:s A',X_SQLTS_TO_UNIX($val));
}
function HTML_UnixTimestamp($val) {
	return date('Y-m-d h:i:s A',$val);
}

function HTML_TIMESTAMP($date) {	return date("d-M-Y h:m:s a",$date); }

function hTime($time) { return html_time($time); }
function HTML_TIME($time) {
   if(is_null($time)) return '--:-- --';
	$ampm = "am";
	$hours = intval($time / 60);
	$mins = $time - ($hours * 60);
	$mins = str_pad($mins, 2, "0", STR_PAD_LEFT);
	if ($hours > 11) { $ampm="pm"; }
	if ($hours > 12) { $hours-=12; }
	if ($hours==0)   { $hours=12;  }
	return "$hours:".$mins." ".$ampm;
}

function HTML_TIMESLOT($slot) {
	if ($slot==0 ) { return 'Midnight'; }
	if ($slot==2 ) { return '12:30am'; }

	$ampm = "am";
	if ($slot > 47) { $ampm = "pm"; }
	if ($slot > 51) { $slot-=48; }
	if ($slot % 4==0) { $mins = "00"; }
	if ($slot % 4==1) { $mins = "15"; }
	if ($slot % 4==2) { $mins = "30"; }
	if ($slot % 4==3) { $mins = "45"; }
	$slot-= ($slot %4);
	$slot /= 4;
	return "$slot:".$mins." ".$ampm;
}

/**
name:FindAccessKey
parm:string HTML_Caption
returns:array (HTML_Caption,HTML_Parm_Accesskey)

Allows you to specify HMTL "Accesskey" for a hyperlink by putting a
backslash into the caption, so that "\Example" returns "<u>E</u>xample"
and 'accesskey="E"'.

Accepts a string, examines the string for a backslash character.  If
one is found, it removes the backslash and underlines the character
immediately after.

Returns the an array of two elements, first is the modified caption and
the next is an HTML fragment 'accesskey="X"' where 'X' is whatever character
was right after the backslash.

If there is no backslash in the caption, then the caption is returned
unchanged and the accesskey is empty.
*/
function FindAccessKey($caption) {
   $akey='';
   $x=strpos($caption,"\\");
   if ($x!==false) {
      $akey= " accesskey=\"".substr($caption,$x+1,1)."\" ";
      $caption
         =($x==0 ? '' : substr($caption,0,$x))
         ."<u>".substr($caption,$x+1,1)."</u>"
         .substr($caption,$x+2);
   }
   return array($caption,$akey);
   //return array($caption,'');
}

// ==================================================================
// Text Date Functions
// ==================================================================
/**
name:Partial Date Functions
parent:Framework API Reference

This is a category reserved for functions that take date parts, like a
month number, and return strings, and vice-versa.
*/

/**
name:_default_
parent:Partial Date Functions
*/

/**
name:dMonFromNum
parm:numb Month_Number
returns:string Month_Name_3_Letter

Returns a the three-letter name of the month, capitalized.
*/
function dMonFromNum($xmonth) {
   $arr=array(''
      ,'Jan','Feb','Mar','Apr','May','Jun'
      ,'Jly','Aug','Sep','Oct','Nov','Dec'
   );
   return $arr[$xmonth];
}

/**
name:dMonthEnd
parm:unix_ts/String Start Date
parm:int Months
returns:unix_ts End Date

Accepts a given date, and returns the last day of the month that
includes "Months" number of months.  Passing in '1/1/2007' and '1'
returns '1/31/2007'.  Passing in '6/1/2007' and 3 returns '8/31/2007'.

Useful for building SQL queries that include ranges of dates.

The first parameter can either be a unix ts or a string that will
successfully convert to a unix ts, such as '1/1/2007' or '2007-01-01'.
*/
function dMonthEnd($datein,$months) {
   $dateout=dEnsureTS($datein);
   $dateout=dEnsureTS( date('m/1/Y',$dateout) );
   $dateout=strtotime("+ $months months",$dateout);
   return strtotime("-1 day",$dateout);
}

/**
name:dEnsureTS
parm:unix_ts/string date
returns:unix_ts

Accepts a variable and returns a unix timestamp.  If the value passed in
is a number, the number is returned unchanged.  If the value is a string,
it is converted via strtotime.

Useful for writing resilient code when input values are not reliably
one or the other.
*/
function dEnsureTS($datein) {
   if(is_integer($datein)) return $datein;
   else return strtotime($datein);
}


/**
name:dMonthFromNum
parm:numb Month_Number
returns:string Month_Name

Returns the full name of a month, capitalized.
*/
function dMonthFromNum($xmonth) {
   $x=array(''
      ,'January','February','March','April','May','June'
      ,'July','August','Septempter','October','November','December'
   );
   return $x[$xmonth];
}

/**
name:dYear
returns:numb Current_Year

Simple shorthand for php date function, date('Y',time());
*/
function dYEar() {
   return date('Y',time());
}

// ==================================================================
// Joomla Compatibility Functions
// ==================================================================
/**
name:Joomla Compatibility
parent:Framework API Reference
flag:EXPERIMENTAL

The Joomla Compatibility framework allows 'drop-in' use of Joomla
templates for an Andromeda Application.

These features are EXPERIMENTAL.  They have not been used extensively.

To use a Joomla template, you must do the following:

* Call [[JoomlaCompatibility()]] from applib
* Create a 'templates' directory and put your template files there
*/

/**
name:JoomlaCompatibility
parm:string Template_Name
parm:string Template_Color

This function generates objects, variables and defines that
satisfy a Joomla template so that it will execute and serve up
Andromeda content.

The first parameter is the name of the template to use.  The template
files should be in a subdirectory of your app's "templates" directory,
and that subdirectory should have the same name as the template.

The second parameter, which defaults to blank,
is assigned to $GLOBALS['template_color'].

Other actions of this program are:

* defines constant _VALID_MOS as true
* defines constant _ISO as empty
* assigns the application's root directory to global
  variable $mosConfig_absolute_path.
* assigns an empty string to global variable $mosConfig_live_site.
* creates empty global $my object with property 'id' set to false
* creates empty global $mainframe object, whose getTemplate() method always
  returns the template name.

The universal dispatcher, [[index_hidden]], looks for the defined constant
_VALID_MOS, and if found it uses the named Joomla template instead of an
Andromeda template.  It also exposes the necessary global variables
that were defined above.

The compatibility layer provides a handful of functions to emulate the
functions used by Joomla.  The most important function is [[mosMainBody]],
which calls directly to [[ehStandardContent]].  The other functions tend
toward being more placeholders.

When you use a Joomla template, there are a handful of tasks that must
be performed:

* Insert a link to the Andromeda javascript library, raxlib.js into
  the template.
* Code up routine appCountModules, which handles calls to Joomla
  function [[mosCountModules]].
* Code up routine appShowModules, which handles calls to Joomla
  function [[mosLoadModules]].
* Identify the template's CSS classes for menu modules and menu items,
  and assign them in [[applib]] using [[vgaSet]] to 'MENU_CLASS_MODL' and
  'MENU_CLASS_ITEM'.
* Look for any hard-coded configuration parameters that you want to
  override and REM them out.
* Copy the x2.css file from andro/clib into the template's CSS
  directory, and link to it from the template main file.

*/
function JoomlaCompatibility($template_name,$template_color='') {
   // Templates won't run unless this is defined.
   define('_VALID_MOS',true);

   // These are 1.5 definitions
   define('_JEXEC',true);
   define('DS','/');

   // We don't know what this is
   define('_ISO','');

   // Create this fake object with $my->id=false, so templates go to
   // normal mode
   $GLOBALS['J']['my'] = new joomla_fake;

   // Joomla templates seem to want this?  This is how they know what
   // template they are using.
   $GLOBALS['J']['mainframe'] = new joomla_fake;
   $GLOBALS['J']['mainframe']->template_name = $template_name;

   // Joomla directory locations
   $GLOBALS['J']['mC_absolute_path'] = $GLOBALS['AG']['dirs']['root'];
   if(tmppathInsert()=='') {
      $GLOBALS['J']['mC_live_site']     = '';
   }
   else {
      // strip off trailing slash for Joomla.  Andromeda functions
      // expect a trailing slash, but Rockettheme Joomla templates
      // provide one themselves.  BTW, technically this does not
      // appear to be necessary, but it is cleaner.
      $tpi=tmpPathInsert();
      $tpi=substr($tpi,0,strlen($tpi)-1);
      $GLOBALS['J']['mC_live_site']     = '/'.$tpi;
   }

   $GLOBALS['J']['template_color']   = $template_color;
}

class joomla_fake {
   var $id=false;
   var $template_name='';
   // KFD 2/25/08 added for
   var $_session = array();

   function getTemplate() {
      return $this->template_name;
   }
}

/**
name:mosShowHead
parent:Joomla Compatibility

This is an empty routine that returns an empty string.
*/
function mosShowHead() {  return ''; }

/**
name:mosCountModules
parent:Joomla Compatibility
parm:string Module_name

Looks for the function [[appCountModules]] to exist.  If that routine
exists, it is called and the result is returned.  If that method does not
exist, always returns false.

Define and code the method [[appCountModules]] in your [[applib.php]] file.
*/
function mosCountModules($name) {
   //$content=vgaGet('JOOMLA_COUNT_'.$name,'');
   //if($content!=='') return $content;
   if(function_exists('appCountModules')) {
      return appCountModules($name);
   }
   elseif(function_exists('tmpCountModules')) {
      return tmpCountModules($name);
   }
   else {
      return true;
   }
}

/**
name:mosLoadModules
parent:Joomla Compatibility
parm:string Module_name
parm:number Unknown

Looks for the function [[appLoadModules]] to exist.  If that routine
exists, it is called.  That routine is expected to echo its output
directly.  If that routine does not exist, nothing happens.

Define and code the method [[appLoadModules]] in your [[applib.php]] file.

One handy way to explore a template is to code [[appLoadModules]] so that
it simply echoes the name of the module, that way the template will appear
with all of the module areas displaying their names.

*/
function mosLoadModules($name,$arg1=null) {
   //$content=vgaGet('JOOMLA_LOAD_'.$name);
   //if($content<>'') {
   //   echo $content;
   //}
   if(function_exists('appLoadModules')) {
      appLoadModules($name,$arg1);
   }
   elseif(function_exists('tmpLoadModules')) {
      tmpLoadModules($name,$arg1);
   }
   else {
      echo
         'Could not find appLoadModules() or tmpLoadModules(). '
         ." This message sponsored by module '$name'";
  }
}

/**
name:mosPathWay
parent:Joomla Compatibility

Returns an empty string.

In a joomla site, this would return the navigation hierarchy, which
Andromeda does not currently provide.
*/
function mosPathWay()  {
   //echo "mosPathway";
}

/**
name:mosMainBody
parent:Joomla Compatibility

echos [[ehStandardContent]].
*/
function mosMainBody() {
  ehStandardContent();
}

/**
name:tmpPathInsert
parent:Joomla Compatibility

This routine makes it possible to use friendly URL's together with
absolute paths in the special case where your files are stored in
a user's home directory on a local machine.

The function is only called in templates, and is always called inside
of links to CSS and JS files.

The function is actually pulling the value "localhost_suffix" from the
application's web_path.
*/
function tmpPathInsert() {
   return vgfGet("tmpPathInsert");
}

/**
name:ampReplace
parent:Joomla Compatibility
parm:string URL
returns:string URL

This routine exists in the Rocket Theme splitmenu code, and is
presumably a Joomla library routine.

*/
function ampReplace($input) {
   return str_replace("&","&amp;",$input);
}

/**
name:ampReplace
parent:Joomla Compatibility
parm:string URL
returns:string URL

This routine exists in the Rocket Theme splitmenu code, and is
presumably a Joomla library routine.

*/
function sefRelToAbs($input) {
   return "/".tmpPathInsert().$input;
}

// ==================================================================
// File Functions.  Mixed new and old
// ==================================================================
function scFileName($filespec) {
   $pathinfo=pathinfo($filespec);
   if (isset($pathinfo['basename'])) {
      return $pathinfo['basename'];
   }
   else {
      return '';
   }
}

function scBaseName($filespec) {
    $filename = scFileName($filespec);
    $list = explode(".",$filename);
    if(count($list)>1) {
        array_pop($list);
    }
    return implode(".",$list);
}

function scFileExt($filespec) {
   $pathinfo=pathinfo($filespec);
   if (isset($pathinfo['extension'])) {
      return $pathinfo['extension'];
   }
   else {
      return '';
   }
}

/**  Add a slash to a directory path
  *
  *  Puts a slash onto the end of string, if there is not
  *  one there already.  Good to make sure directory paths
  *  can always be safely used.
  *
  *  @category files
  *  @example  $complete = scAddSlash($dir).$filename;
  *  @param    $path string
  *  @return   string
  */
function raxAddSlash($path) {
   $path = trim($path);
   return $path. (substr($path,-1,1)=='/' ? '' : '/');
}
function scAddSlash($path) { return raxAddSlash($path); }

// ==================================================================
// String clipping functions
// ==================================================================
function scClipStart($input,$item) {
   $len = strlen($item);
   if (substr($input,0,$len)==$item) {
      $input = substr($input,$len);
   }
   return $input;
}
function scClipAfter($input,$item) {
   if (strpos($input,$item)!==false) {
      $input = substr($input,0,strpos($input,$item));
   }
   return $input;
}

// ==================================================================
// Type conversion functions
// ==================================================================
function X_SQLTS_TO_UNIX($dttm2timestamp_in){
	//    returns unix timestamp for a given date time string that comes from DB
	$date_time = explode(" ", $dttm2timestamp_in);
	$date = explode("-",$date_time[0]);
	if (!isset($date_time[1])) { $date_time[] = "00:00:00"; }
	$time = explode(":",$date_time[1]);
	unset($date_time);
	if (!isset($date[1]))
		list($year,$month,$day) = array(1970, 1, 1);
	else
		list($year, $month, $day)=$date;
	list($hour,$minute,$second)=$time;
	return mktime(intval($hour), intval($minute), intval($second), intval($month), intval($day), intval($year));
}


function X_UNIX_TO_SQLTS($dt,$skipquotes=false) {
	if ($skipquotes) { $q=""; } else { $q="'"; }
	return $q.date("Y-m-d h:i:s a",$dt).$q;
}
function X_UNIX_TO_SQLDATE($dt,$skipquotes=false) {
	if ($skipquotes) { $q=""; } else { $q="'"; }
	return $q.date("Y-m-d",$dt).$q;
}



// ==================================================================
// Options out of DD tables
// ==================================================================
function OptionGet($varname,$default='') {
   return Option_Get($varname,$default);
}

function getRegexOptionVal( $matches ) {

        //return OptionGet( $matches[1] );
        $query = "SELECT * FROM variables WHERE variable='" .$matches[1] ."'";
        $result = SQL_OneRow( $query );
        $ret = '';
        if ( count( $result ) > 0 ) {
                $ret = preg_replace_callback( "/%%(.+?)%%/"
                        ,"getRegexOptionVal",
                        $result['variable_value']
                );
        }
        return $ret;
}

function Option_Get($varname,$default='') {
    if($varname=='X') {
        unlink($GLOBALS['AG']['dirs']['dynamic'].'table_variables.php');
        unset($GLOBALS['AG']['table_variables']);
    }
   if(!file_exists_incpath('table_variables.php')) {
      // Retrieve the file
      $rows=SQL_AllRows("select * from variables");
      $cache=array();
      foreach($rows as $row) {
         $line = preg_replace_callback( "/%%(.+?)%%/"
             ,"getRegexOptionVal"
             ,$row['variable_value'] );
         $cache[$row['variable']]=$line;
      }
      fsFileFromArray(
         'table_variables.php'
         ,$cache
         ,"GLOBALS['AG']['table_variables']"
      );
   }
   include('table_variables.php');
   return ArraySafe($GLOBALS['AG']['table_variables'],trim($varname),$default);

   // KFD 6/8/07, retired the old code that queried database on every pull
   /*
	$rows = SQL_AllRows(
      "SELECT variable_value FROM variables WHERE variable = '".$varname."'"
   );
	if (count($rows)==0) return $default;
   else return $rows[0]['variable_value'];
   */
}




/** Use rows of a table to simulate columns
  *
  * Takes a table name, one column to pull for values
  * and other to pull as description, and makes a data
  * dictionary entry as "x_<table_id>" so that you can
  * make inputs, read inputs, and so forth.  Originally
  * created to flesh out uses of the "fullpop" flag
  */
function DD_RowsAsTable($table_id,$colcolname,$colcoldesc,$colinfo) {
	$table = DD_Table($table_id);
	$table["table_id"]="x_".$table_id;

	// Now build the list of fake columns
	$flat = array();
	$results = SQL(
		"Select ".$colcolname." as column_id,".$colcoldesc." as coldesc ".
		"  from $table_id ".
		" ORDER BY $colcolname ");
	while ($row = pg_fetch_array($results)) {
		$colname = trim($row["column_id"]);
		$flat[$colname] = $colinfo;
		$flat[$colname]["column_id"]   = $colname;
		$flat[$colname]["description"] = $row["coldesc"];
	}
	$table["flat"] = $flat;

	$GLOBALS["AG"]["tables"]["x_".$table_id] = $table;
}

function DDProjectionResolve(&$table,$projection='') {
   // Pass 1 is security projection.  Drop columns completely
   // if they are not in the view
   //
   $table_id=$table['table_id'];
   $view_id =DDTable_IDResolve($table_id);
   if($table_id<>$view_id) {
      $g2use = $table['tableresolve'][SessionGet('GROUP_ID_EFF')];
      $geff  = SessionGet('GROUP_ID_EFF');
      $g2use = substr($geff,0,strlen($geff)-5).substr($g2use,-5);
      if(substr($g2use,-5)<>'99999') {
         $cols2keep = &$table['views'][$g2use];
         foreach($table['flat'] as $colname=>$colinfo) {
            if(!isset($cols2keep[$colname])) {
               unset($table['flat'][$colname]);
            }
            else {
               if($cols2keep[$colname]==0) {
                   $table['flat'][$colname]['securero']='Y';
                  $table['flat'][$colname]['upd']='N';
                  $table['flat'][$colname]['ins']='N';
               }
            }
         }
      }
   }


   // If projection does not exist (including case of not specificied),
   // use all columns.  If projection is an array, it must be a list of
   // columns
   if(is_array($projection)) {
      $projcand = $projection;
   }
   else {
      if(!isset($table['projections'][$projection])) {
         // This case also catches where no projection was specified
         $projcand = array_keys($table['flat']);
      }
      else {
         $projcand = explode(',',$table['projections'][$projection]);
      }
   }
   $acols = array();
   foreach($projcand as $colname) {
      if(!isset($table['flat'][$colname])) continue;
      if($colname=='skey') continue;
      if(ArraySafe($table['flat'][$colname],'uino')=='Y' ) continue;
      $acols[]=$colname;
   }
   return $acols;
}
// ==================================================================
// Validation Stuff
// ==================================================================
function CheckTextDate($input) {
	$arr = explode("/",$input);
	if (Count($arr)!=3) { return false; }
	else { return checkdate($arr[0],intval($arr[1]),intval($arr[2])); }
}

// ==================================================================
// The subroutine that eats like a meal.  Does everything with
// the data-oriented posted variables, independent of what page
// we are on or going to.  Stores search criteria, does updates,
// deletes, inserts.
// ==================================================================
// This is original name of routine
function databaseFromPost() {
   return processPost();
}

function processPost() {
   // If they are doing a dupe check, convert it into a
   // search
   if(gp('gp_action')=='dupecheck') {
      gpSet('gpx_mode','search');
   }

   // 5/10/06  Added this call.  See routine for details.
   //KenDebug('Entered process post');
   $row=aFromGP('x2t_');
   if(count($row)>0) {
      //KenDebug('Going into textboxes');
      processPost_TextBoxes($row);
   }


   // this is database inserts/updates, gets its own subroutine
   // Unless AddControl() was used to build controls, gpControls will
   // be blank and this is not called.
   //
   // AS OF 5/10/06, these are not regularly used in x_table2
   //
   if(gp('gpControls','')<>'') {
      $data=processPost_Database();
      vgfSet('array_post',$data);
   }

   // Database deletions, form: gp_delskey_<table>=<skey_value>
   //
   // AS OF 5/29/07, revived for Ajax_x3 initiative
   // AS OF 5/10/06, these are not regularly used in x_table2
   //
   $dels=aFromGP("gp_delskey_");
   foreach($dels as $table_id=>$skey) {
      if(intval($skey)>0) {
         $view_id=DDTable_IDResolve($table_id);
         $sq="DELETE FROM $view_id where skey=$skey";
         SQL($sq);
         processPost_TableSearchResultsClear($table_id);
      }
   }

   // Look for gp_ob controls, change order-by.  Only make
   // a change if you find a value, no action on blanks
   $obs=aFromGP("gp_ob_");
   foreach($obs as $table_id=>$obnew) {
      $table=DD_TableRef($table_id);
      $obold    = ConGet("table",$table_id,"orderby");
      $obascold = ConGet("table",$table_id,"orderasc");
      if ($obnew<>'') {
         if ($obold <> $obnew) {
            // for new column, always go ascending
            ConSet("table",$table_id,"orderasc","ASC");
         }
         else {
            // if repeating the same column, flip ascending/descending
            $obascnew = ($obascold=="ASC") ? "DESC" : "ASC";
            ConSet("table",$table_id,"orderasc",$obascnew);
         }
         ConSet("table",$table_id,"orderby",$obnew);
         processPost_TableSearchResultsClear($table_id);
      }
   }

   // Now look for page-turners, where they are advancing to
   // a new page on a search results display
   $obs=aFromGP("gp_spage_");
   foreach($obs as $table_id=>$pagecommand) {
      list($spage,$srows,$srppg,$maxpage)=arrPageInfo($table_id);
      switch($pagecommand) {
         case '':  $newpage=0;
         case '0': $newpage=1;                            break;
         case '1': $newpage=($spage<=1) ? 1 : $spage - 1; break;
         case '2': $newpage=($spage>=$maxpage) ? $maxpage : $spage + 1; break;
         case '3': $newpage=$maxpage;                      break;
      }
      if ($newpage<>0) {
         ConSet('table',$table_id,'spage',$newpage);
      }
   }

   // Check to see if an onscreen child table row was saved
   if(gp('gp_child_onscreen','')<>''){
      $parent_skey   = gp('gp_skey');
      $table      = gp('gp_child_onscreen');
      $cols       = aFromGP('gp_onscreen_'.$table.'_');
      $table_dd   = dd_tableRef($table);
      SQLX_Insert($table_dd,$cols);
      gpSet('gp_skey',$parent_skey);
   }
}

function processPost_TableSearchResultsClear($table_id) {
   ConSet("table",$table_id,"skeys",'');   // wipe out results
   ConSet("table",$table_id,"spage",'0');  // set to page one
}

function arrPageInfo($table_id) {
   $spage=ConGet('table',$table_id,'spage',1);
   $srows=ConGet('table',$table_id,'srows',0);
   $srppg=ConGet('table',$table_id,'rppage',25);
   $maxpg=intval($srows / $srppg);
   if($srows % $srppg > 0) ++$maxpg;
   return array($spage,$srows,$srppg,$maxpg);
}


function processPost_Database() {
   // Obtain controls.  Convert to set of tables that
   // can be processed as inserts, updates, or deletes
   $data = array();
   $controls=gpControls();

   foreach($controls as $index=>$info) {
      $value=null;
      if(!gpExists('array_'.$index)) {
         // tough call. If did not come back, assume an
         // unchecked check box.
         if($info['v']=='Y') $value='N';
      }
      else {
         $x=gp('array_'.$index);
         if(trim($x)!==trim($info['v'])) {
            $value=$x;
         }
         // on inserts, take every value
         if($info['s']<0) $value=$x;
      }
      if(!is_null($value)) {
         $data[$info['t']][$info['s']][$info['c']]=$value;
      }
   }

   // These become part of all rows written to child tables
   //$parents=array();
   //if(drilldownlevel()>0) {
   //   $ddx=DrillDownTop();
   //   $parents=$ddx['parent'];
      //html_vardump($parents);
   //}

   // Now process all updates and inserts.
   foreach($data as $table_id=>$rows) {
      $table=DD_TableRef($table_id);
      foreach($rows as $skey=>$row) {
         if($skey=='s') {
            ConSet('table',$table_id,'search',$row);
            processPost_TableSearchResultsClear($table_id);
         }
         if($skey>=1) {
            $row['skey']=$skey;
            SQLX_Update($table,$row);
         }
         if($skey<0)  {
            if(gp('gp_skey')=='X') {
               // Merge in any values from parent and claim they
               // were there all along
               //$rowins = array_merge($row,$parents);
               $data[$table_id][$skey]=$row;
               $skey=SQLX_Insert($table,$row,false);
               if($table_id==gp('gp_page')) {
                  gpSet('gp_skey',$skey);
               }
               processPost_TableSearchResultsClear($table_id);
            }
         }
      }
   }
   return $data;
}

function processPost_Textboxes($row) {
   $gp_action=gp('gp_action');
   $gp_mode  =gp('gpx_mode');
   $gp_skey  =gp('gpx_skey');
   $table_id =gp('gpx_page');
   $table    =DD_TableREf($table_id);

   // Cache flags.  This was introduced for Worldcare 5/22/06.
   // For worldcare the setting is made in applib, it is not set
   // anywhere in the data dictionary.  The idea is that a table
   // that is 'cache_table_pk' gets each row cached by the pk.  Perhaps
   // also we would have 'cache_table' for things like states, where
   // the entire table is cached.
   //
   $user_pref=($table_id==vgaGet('user_preferences')) ? true : false;

   // Deletion is pretty simple
   if($gp_action=='del') {
      $view_id=DDTable_IDResolve($table_id);
      $sq="DELETE FROM $view_id where skey=$gp_skey";
      SQL($sq);
      processPost_TableSearchResultsClear($table_id);
      return;
      // <<<<<<<<<< RETURN
   }

   // Saving an insert requires an explicit command
   if($gp_action=='save' && $gp_mode=='ins') {
      // KFD 6/15/07, remove blanks from an insert.
      foreach($row as $key=>$value) {
         if(trim($value=='')) unset($row[$key]);
      }
      $skey=SQLX_Insert($table,$row);
      if(Errors()) {
         // ERRORROW CHANGE 5/30/07, moved to SQLX_* routines
         //vgfSet('ErrorRow',$row);
      }
      else {
         if($user_pref) UserPrefsLoad();
         processPost_TableSearchResultsClear($table_id);

         // If there was a page set to return to, do that now
         if(SessionGet('gp_aftersave','')<>'') {
            gpSet('gp_page',SessionGet('gp_aftersave'));
            $rowx=SQL_OneRow("Select * from $table_id where skey=$skey");
            SessionSet("ROW_".strtoupper($table_id),$rowx);
         }
      }
      return;
      // <<<<<<<<<< RETURN
   }

   // If the old mode was search, then set the new search criteria
   if($gp_mode=='search') {
      ConSet('table',$table_id,'search',$row);
      processPost_TableSearchResultsClear($table_id);
      return;
      // <<<<<<<<<< RETURN
   }

   // Finally, if the old mode was view (update), then look for
   // changed values and figure out if we need to do an update
   if($gp_mode=='upd') {

      //echo "i am trying to update, here are old controls: ";
      $controls=ContextGet('OldRow',array());
      //html_vardump($controls);
      $changed=array();
      $errrow=$controls;
      foreach($controls as $colname=>$colvalue) {
         $value=null;
         if(!isset($row[$colname])) {
            // tough call. If did not come back, assume an
            // unchecked check box.
            if($colvalue=='Y') $changed[$colname]='N';
         }
         else {
            // KFD 6/27/07, allow explicit force save of all values
            if(   gpExists('gp_forcesave')
               || trim($colvalue)!==trim($row[$colname])
            ) {
               $changed[$colname]=$row[$colname];
               $errrow[$colname] =$row[$colname];
            }
         }
      }
      if(count($changed)>0) {
         $changed['skey']=$gp_skey;
         $table=DD_TableREf($table_id);
         SQLX_Update($table,$changed,$errrow);
         if(Errors()) {
            // ERRORROW CHANGE 5/30/07, moved to SQLX_* routines
            //vgfSet('ErrorRow',$row);
         }
         else {
            if($user_pref) UserPrefsLoad();
            /*
            if($cache_pk<>'') {
                DynFromA($cache_pk,$row);
                if ($reload_user) {
                  $up=SQL_OneRow(
                     "Select * from $table_id WHERE user_id='".$row['user_id']."'"
                  );
                  vgaSet('this_user_prefs',$up);
                }
            }
            */
         }
      }
      //html_vardump($row);
      //html_vardump($controls);
      //html_vardump($changed);
      return;
      // <<<<<<<<<< RETURN
   }
}

// - - - - - - - - - - - - - - - - -  -
// Closely related routine, depends
// entirely upon the context
// - - - - - - - - - - - - - - - - -  -
function rowsFromUserSearch(&$table,$lcols=null,$matches=array()) {
    $table_id=$table['table_id'];
    $view_id =DDTable_IDResolve($table_id);
    // If search has not been performed, do it now
    $skeys = ConGet('table',$table_id,'skeys','');
    if(!is_array($skeys)) {
        $skeys = rowsFromUserSearch_Execute($table,$matches);
    }

    // Now go in and retrieve the rows for the skey values
    // that we want
    $gp_spage = ConGet('table',$table_id,'spage',1);
    $gp_rpp   = ConGet('table',$table_id,'rppage',25);
    $colob = ConGet('table',$table_id,'orderby');
    //$gp_ob
    //    ="Order By ".$colob
    //    .' '.ConGet('table',$table_id,'orderasc');

    // This is weird trick that some columns may have a natural 2nd
    // column that should sort
    //$table_opts=vgaGet('table_opts',array());
    //if(isset($table_opts[$table_id]['sortnext'][$colob])) {
    //    $colob2=$table_opts[$table_id]['sortnext'][$colob];
    //    $gp_ob.=', '.$colob2.' '.ConGet('table',$table_id,'orderasc');
    //}

    $skeysl =array_slice($skeys,($gp_spage-1)*$gp_rpp,$gp_rpp);
    $skeysl =implode(',',$skeysl);
    if($skeysl=='') return array();  // Early return

    if(is_null($lcols)) $cols=$table['projections']['_uisearch'];
    $colslist='skey,'.$lcols;

    $sob = ConGet('table',$table_id,'complex_orderby');
    $sq="SELECT $colslist FROM $view_id "
        ." WHERE skey in ($skeysl) ORDER BY $sob";
        //." $gp_ob";
    return SQL_AllRows($sq);
}

// This routine retrieves the skey values only
function rowsFromUserSearch_Execute(&$table,$matches=array()) {
   $table_id = $table["table_id"];
   $tabflat  = $table["flat"];
   $filters=ConGet('table',$table_id,'search',array());
   if(count($matches)==0) {
      $matches=DrillDownMatches();
   }


   $rows = rowsFromFilters($table,$filters,'skey',$matches);
   $skeys=array();
   foreach($rows as $row) {
      $skeys[]=$row['skey'];
   }

   // Save the vital stats on the search
   ConSet('table',$table_id,'skeys',$skeys);
   ConSet('table',$table_id,'spage',1);
   ConSet('table',$table_id,'srows',count($skeys));
   return $skeys;
}

function rowsFromFilters(&$table,$filters,$cols,$matches=array()) {
    $tabflat  =$table['flat'];
    $table_id = $table['table_id'];
    $view_id  = DDTable_IDResolve($table_id);
    //echo SessionGet("GROUP_ID_EFF");
    // Set user-requested filters
    $sw = array();
    foreach ($tabflat as $colname=>$colinfo) {
      if (isset($matches[$colname])) {
         $tcv = trim($matches[$colname]);
         if ($tcv != "") {
            $tcsql = SQL_Format($colinfo["type_id"],$tcv);
            $sw[]=$colname."=".$tcsql;
            //$sql_where.=ListDelim($sql_where," AND ").$colname."=".$tcsql;
         }
      }
      elseif (isset($filters[$colname])) {
         $tcv = trim($filters[$colname]);
         $tid = $colinfo['type_id'];
         if($tid=='dtime' || $tid=='date') {
            $tcv=dEnsureTS($tcv);
         }
         if ($tcv != "") {
            // trap for a % sign in non-string
            $sw[]='('.rff_OneCol($colinfo,$colname,$tcv).')';
         }
      }
    }
    $sql_where= implode(' AND ',$sw);


    // Set identity-security filters
    // NOPE, Rem'd out 10/26/06 when moved server-side
    //$sql_where2 = S*QLX_Filters($tabflat);
    //if ($sql_where2!="") {
    //   $sql_where.=ListDelim($sql_where," AND ").$sql_where2;
    //}
    if ($sql_where!="") { $sql_where= " WHERE ".$sql_where; }

    // KFD 10/24/07.  ASC/DESC used to be after the clause below,
    //                but we need to get it first because we have
    //                to assign it to each column
    $obasc = ConGet("table",$table_id,"orderasc");
    if ($obasc=="") {
        $obasc = "ASC";
        ConSet("table",$table_id,"orderasc",$obasc);
    }
    $SQLOB = $obasc;

    // KFD: 10/24/07.  Order by all columns, not just the
    //       the selected one.  But order by the selected one
    //       first.
    $ob  = ConGet("table",$table_id,"orderby");
    $lob = explode(',',$table['projections']['_uisearch']);
    if($ob=='') {
        foreach($lob as $onecol) {
            $aid = $table['flat'][$onecol]['automation_id'];
            if(in_array($aid,array('SEQUENCE','SEQDEFAULT'))) continue;
            $ob = $onecol;
            ConSet('table',$table_id,'orderby',$ob);
        }
    }
    $sob = $ob.' '.$obasc;
    foreach($lob as $onecol) {
        $aid = $table['flat'][$onecol]['automation_id'];
        if(in_array($aid,array('SEQUENCE','SEQDEFAULT'))) continue;
        if($onecol <> $ob) {
            $sob.="\n, ".$onecol.' '.$obasc;
        }
    }
    ConSet('table',$table_id,'complex_orderby',$sob);

    // Retrieve the limit as a vgaget, defaulting to 300
    // DJO 4-8-2008 Allow for system variable override, 0 would be all records
    $SQL_Limit = OptionGet( 'SQL_LIMIT', vgaGet( 'SQL_Limit', 300 ) );

    // Execute the sql, pull down the skey values
    $skeys=array();
    $sq="SELECT ".$cols." FROM ".$view_id.$sql_where
      ." ORDER BY ".$sob .( $SQL_Limit > 0 ? " LIMIT ".$SQL_Limit : '' );
    $rows =SQL_ALLRows($sq);
    $retval=($rows===false) ? array() : $rows;
    return $retval;
}

// KFD 5/17/07, support lists, ranges, and greater/lesser
//
function rff_OneCol($colinfo,$colname,$tcv) {
    $tid = $colinfo['type_id'];
    $uiid= ArraySafe($colinfo,'uisearch_ignore_dash','N');
    $values=explode(',',$tcv);
    $sql_new=array();
    foreach($values as $tcv) {
        $aStrings=array('char'=>0,'vchar'=>0,'text'=>0);
        if(substr($tcv,0,1)=='>' || substr($tcv,0,1)=='<') {
            // This is a greater than/less than situation,
            // we ignore anything else they may have done
            $new=$colname.substr($tcv,0,1).SQL_FORMAT($tid,substr($tcv,1));
        }
        elseif(strpos($tcv,'-')!==false && $uiid<>'Y' ) {
            list($beg,$end)=explode('-',$tcv);
            $new=$colname.' BETWEEN '
                .SQL_Format($tid,$beg)
                .' AND '
                .SQL_Format($tid,$end);
        }
        else {
            if(! isset($aStrings[$tid]) && strpos($tcv,'%')!==false) {
                $new="cast($colname as varchar) like '$tcv'";
            }
            else {
                $tcsql = SQL_Format($tid,$tcv);
                if(substr($tcsql,0,1)!="'" || $tid=='date' || $tid=='dtime') {
                    $new=$colname."=".$tcsql;
                }
                else {
                    $tcsql = str_replace("'","''",$tcv);
                    $new
                        ="(    LOWER($colname) like '".strtolower($tcsql)."%'"
                        ."\n          OR "
                        ."     UPPER($colname) like '".strtoupper($tcsql)."%')";
                }
            }
        }
        $sql_new[]="($new)";
    }
    return implode("\n        OR ",$sql_new);
}


// Completely generalized updating of any number
// of tables in a database, any number of rows, based
// on posted information.
//

/**
name:AddControl
parm:string Table_id
parm:int skey
parm:string Column_id
parm:any Column_Value
returns:string Control_Name

Use this routine to register a form control and its value.  The information
about the control is saved in the [[Context]].

Returns the name of the control.  Use this as the HTML name property when
putting the control onto the form.
*/
function AddControl($table_id,$skey,$colname,$colvalue) {
   $controls=vgfGet('gpControls',array());
   $index = count($controls)+1;
   $controls[$index] = array(
       't'=>$table_id
      ,'s'=>$skey
      ,'c'=>$colname
      ,'v'=>$colvalue
   );
   vgfSet('gpControls',$controls);
   return 'array_'.$index;
}


function ahFromRows(&$rows,$inputs,$table_id=null) {
   // Generate the appropriate input types for the table
   $hinputs=ahInputsFromProjection($table_id,$inputs);

   $hrows=array();
   foreach($rows as $row) {
      $hrow = array();
      foreach($inputs as $colname=>$allowread) {
         if($allowread<>'Y') {
            $hrow[] = $row[$colname];
         }
         else {
            $input_name=AddControl(
               $table_id,$row['skey'],$colname,$row[$colname]
            );
            //$index=count($controls)+1;
            //$input_name = 'array_'.$index;
            $checked='';
            if($hinputs[$colname]['type_id']=='cbool') {
               $checked = $row[$colname]=='Y' ? ' CHECKED ' : '';
               $row[$colname]='Y';  // forces it to always come back as 'Y'
            }
            $hrow[]=$hinputs[$colname]['open']
               .' name="'.$input_name.'" id="'.$input_name.'"'
               .$checked
               .' value="'.$row[$colname].'">'
               .$hinputs[$colname]['close'];
         }
      }
      $hrows[]=$hrow;
   }
   return $hrows;
}


/**
name:hWidget
parm:string type_id
parm:string name
parm:string value (optional)

This function is new as of 3/9/07, and not yet fully populated.  At the
time of its creation, all widget generation is in [[ahInputsComprehensive]],
with no ability to generate individual widgets as needed.  This will be
added to as needed to supply the various types.

If the type_id is cbool, then the HTML "value" property is always Y, and
the third parameter is taken to be the caption.


*/
function hWidget($type_id,$name,$value='',$opts=array()) {
   // Establish an array.  This uses the same basic structure
   // as ahInputsComprehensive below.
   $col=array(
      'parms'=>array(
         'name'=>$name
         ,'value'=>$value
         ,'type'=>'textbox'
         ,'maxlength'=>strlen($value)
         ,'size'=>strlen($value)+1
         ,'tabindex'=>hpTabIndexNext()
      )
      ,'hparms'=>''
      ,'html'=>''
      ,'input'=>'input'
      ,'hinner'=>''
   );
   if(ArraySafe($opts,'mode')) {
      $col['parms']['class']='inp-'.$opts['mode'];
   }

   switch ($type_id) {
      case 'cbool':
         $col['parms']['type']='checkbox';
         $col['parms']['value']='Y';  // checkboxes must always be 'Y'
         $col['hinner']=$value;       // Assign value as caption
         $col['parms']['type']='checkbox';
         unset($col['parms']['maxlength']);
         unset($col['parms']['size']);
         break;
      case 'date':
         $col['parms']['maxlength']=10;
         $col['parms']['size']=11;
   }

   // Any particular options
   if(ArraySafe($opts,'checked','')<>'') {
      $col['hparms'].=' CHECKED ';
   }
   //if(ArraySafe($opts,'disabled','')<>'') {
   //   $col['hparms'].=' DISABLED ';
   //}

   // Overwrite any parameters with what was passed in
   if(is_array(ArraySafe($opts,'parms',''))) {
      $col['parms'] = array_merge($col['parms'],$opts['parms']);
   }

   // Run out the parameters
   $hparms=$col['hparms'];
   foreach ($col['parms'] as $pname=>$pvalue) {
      if($pname=='value') {
         if($type_id=='date' && $pvalue<>'') {
            $pvalue=hDate(dEnsureTS($pvalue));
         }
         $pvalue=htmlentities($pvalue);
      }
      $hparms.=' '.$pname.' ="'.trim($pvalue).'"';
   }

   // Double the name as the ID
   $hparms.=' id="'.$col['parms']['name'].'"';

   // Make some final pieces and put it together
   $inp = $col['input'];
   $col['html']
      ="<".$inp.$hparms.">".$col['hinner']."</".$inp.">";

   // If its a date, put popup next to it
   if($type_id=='date') {
      $cname=$col['parms']['name'];
      $col['html']
         .="&nbsp;&nbsp;"
         ."<img src='clib/dhtmlgoodies_calendar_images/calendar1.gif' value='Cal'
                onclick=\"displayCalendar(ob('$cname'),'mm/dd/yyyy',this,true)\">";
   }

   return $col['html'];
}



/* KFD 5/10/06, big changes to use txt_ variables instead of array_ */
/*
THIS ROUTINE IS DEPRECATED.  WE HAVE LEARNED ALL THAT WE CAN FROM IT,
AND ARE MOVING OVER TO THE X3 FAMILY OF ROUTINES, AColInfoFromDD,
AColsModeProjOPtions, aColsModeProj, and ahColsFromACols, and others.

THESE NEW ROUTINES PROVIDE FAR SUPERIOR SEPARATION AND SEQUENCING
OF THE WORK, WHICH HAS MADE THE ADDITION OF NEW FEATURES MUCH
EASIER.

*/
function ahInputsComprehensive(
      &$table,$mode,$row=array(),$projection='',$opts=array()
      ) {
   $table_id=$table['table_id'];
   $ahcols=array();

   $stuff=aColInfoFromDD($table);

   // Grab these for later
   $colerrs=vgfget('errorsCOL',array());

   $acols=DDProjectionResolve($table,$projection);
   if(isset($opts['drilldownmatches']))
      $ddmatches=$opts['drilldownmatches'];
   else
      $ddmatches=DrillDownMatches();

   $name_prefix   = isset($opts['name_prefix'])
                  ? $opts['name_prefix']
                  : 'x2t_';
   /* KFD 5/10/06, will be used to store original values */
   $context_row=ContextGet('OldRow',array());

   // KFD 5/23/07, VERY IMPORTANT STRUCTURAL CHANGE TO CODE
   // From now on, all generated javascript for widgets will
   // be produced in this routine we are calling out to.
   // This returns ahInputsComprehensive to the role of just
   // generating HTML.
   //
   // NOPE.  CANCEL THAT as of 5/24/07, we figured out that
   // ahinputscomprehensive is not salvagable, it was split up
   // into about 6 other routines.  See comments up at top
   // of routine.
   $ajscols=ajsFromDD($table,$name_prefix);


   // KFD 1/12/07, parse out possible ajax options.
   $ajax_page='';
   if(is_array(ArraySafe($opts,'ajaxcallback',''))) {
      $ajax_page   =$opts['ajaxcallback']['page'];
      $ajax_columns=explode(',',$opts['ajaxcallback']['columns']);
   }

   $hpsize=ArraySafe($opts,'hpsize',25);

   $colparms=ArraySafe($opts,'colparms',array());

   // KFD 1/16/07, pull out the list of columns that we should save to
   // session as we go
   $savetosession=ArraySafe($opts,'savetosession','');
   $savetosession=explode(',',$savetosession);

   // KFD 1/16/07, begin to allow hard-coded overrides.  This is required
   //   to get it to recognize multi-column foreign keys
   $columnoverrides=ArraySafe($opts,'columnoverrides',array());
   $columndynparms =ArraySafe($opts,'columndynparms' ,array());

   // KFD 5/21/07, When in search mode, force the primary key
   // to be a dynamic lookup to itself.  Trust me, it makes sense.
   // Best way to understand is to go into search mode on something
   // like a customers table.  Well no, it doesn't make sense after
   // all, eliminates ability to use >, < - and lists
   //if($mode=='search') {
   //   $columnoverrides[$table['pks']]['table_id_fko']=$table_id;
   //}

   // *****  STEP 1 OF 3: Derivations
   // Loop through each control and put everything we know and
   // can figure out about it into the array, such as name,
   // value, class, enabled/disabled, size and so forth.
   //$tabindex=1;
   foreach($acols as $colname) {
      $colinfo = &$table['flat'][$colname];
      $value=ColumnValue($table,$row,$mode,$colname);

      /* KFD 5/10/06 */
      //$name=AddControl($table_id,$skey,$colname,$value);
      $name=$name_prefix.$colname;
      $context_row[$colname]=$value;

      // Establish if user can write, then set tabindex accordingly
      $writable=isset($ddmatches[$colname])
         ? false
         : DDColumnWritable($colinfo,$mode,$value);
      $propti=$writable ? hpTabIndexNext() : 999;

      $ahcols[$colname]=array(
         'writeable'=>$writable
         ,'hparms'=>''
         ,'hinner'=>''
         ,'hright'=>''
         ,'html'=>''
         ,'errors'=>''
         ,'parms'=>array(
            'value'=>$value
            ,'type'=>'textbox'
            ,'class'=>'inp-'.$mode
            ,'name'=>$name
            ,'size'=>min($hpsize,$colinfo['dispsize'])+1
            ,'maxlength'=>$colinfo['dispsize']
            ,'tabindex'=>$propti
         )
      );
      if(isset($opts['dispsize'])) {
         $ahcols[$colname]['parms']['size']
            =min($ahcols[$colname]['parms']['size'],$opts['dispsize']);
      }

      // decimals get an extra digit for maxlength
      if($colinfo['colscale']<>0) {
         $ahcols[$colname]['parms']['maxlength']=$colinfo['dispsize']+1;
      }

      // Trap keys for two of our modes
      if($mode=='search') {
         $ahcols[$colname]['parms']['onkeypress']="doButton(event,13,'but_lookup')";
      }
      //if($mode=='ins') {
      //   $ahcols[$colname]['parms']['onkeypress']="doButton(event,13,'but_save')";
      //}

      // Lose maxlength if in search mode
      if($mode=='search') {
         unset($ahcols[$colname]['parms']['maxlength']);
      }

      // These are passed in on the $opts array
      if(isset($colparms[$colname])) {
         foreach($colparms[$colname] as $name=>$value) {
            $ahcols[$colname]['parms'][$name]=$value;
         }
      }

      // Slip in the errors if they are there.
      $colerrsx=ArraySafe($colerrs,$colname,array());
      if(count($colerrsx)>0) {
         $ahcols[$colname]['errors']
            ="<span class=\"x2columnerr\">"
            .implode("<br/>",$colerrsx)
            ."</span>";
      }


      // refinement: PK caps
      if ($table["capspk"]=="Y" && $colinfo["primary_key"]=="Y") {
         $ahcols[$colname]['parms']['onBlur']=
				"javascript:this.value=this.value.toUpperCase();\" ";
      }

      // KFD 1/12/07  If an ajax callback column, put that in.
      if($ajax_page<>'') {
         if (in_array($colname,$ajax_columns)) {
            $ahcols[$colname]['parms']['onChange']=
               "javascript:AjaxCol('$colname',this.value);";
         }
      }

      // KFD 1/16/07, see if we need to save to session
      //if(in_array($colname,$savetosession)) {
      //   $x=$ahcols[$colname]['parms']['name'];
      //   $ahcols[$colname]['parms']['onchange']
      //      ="andrax('?gp_ajax2ssn=$colname&gp_val='+ob('$x').value)";
      //}
   }

   /* KFD 5/10/06, will be used to store original values */
   ContextSet("OldRow",$context_row);


   // *****  STEP 2 OF 3: HTML Decisions and derivations
   // Decide which kind of control to use for each one, and
   // do any overrides and extra stuff that may be necessary
   foreach($acols as $colname) {
      $col=&$ahcols[$colname];
      $colinfo = &$table['flat'][$colname];
      $col['input']='input';
      $coltype=$table['flat'][$colname]['type_id'];
      //echo "$colname is $coltype<br>";
      switch ($coltype) {
         case 'time':
            $col['input']='select';
            $v=$col['parms']['value'];
            if($v==='') {
               $hinner='\n<option SELECTED value="">--:-- --</option>';
            }
            else {
               $hinner='\n<option value="">--:-- --</option>';
            }
            for ($x=0;$x<=1425;$x+=15) {
               $s=$v===strval($x) ? ' SELECTED ' : '';
               $hinner.="\n<option $s value=\"$x\">".hTime($x)."</option>";
            }
            $col['hinner']=$hinner;
            //unset($col['parms']['class']);
            unset($col['parms']['type']);
            unset($col['parms']['size']);
            unset($col['parms']['maxlength']);
            break;
         case 'cbool':
            $col['parms']['type']='checkbox';
            if($col['parms']['value']=='Y') {
               $col['hparms'].=' CHECKED ';
            }
            $col['parms']['value']='Y';  // checkboxes must always be 'Y'
            break;
         case 'mime-x':
            $col['html'] = hImageFromBytes(
               $table_id,$colname,$row[$table['pks']],$row[$colname]
            );
            //unset($col['parms']['value']);
            break;
         case 'text':
         case 'mime-h':
            //$x=SQL_UNESCAPE_BINARY($col['parms']['value']);
            //ob_start();
            //ehFCKEditor($col['parms']['name'],$x);
            //$col['html']=ob_get_clean();
            //$col['html']=
            $col['input'] = 'textarea';
            $col['parms']['rows']
               = isset($colparms[$colname]['rows'])
               ?       $colparms[$colname]['rows']
               : '10';
            $col['parms']['cols']
               = isset($colparms[$colname]['cols'])
               ?       $colparms[$colname]['cols']
               : '50';
            //if($coltype=='mime-h') {
            //   $col['hinner']=base64_decode($col['parms']['value']);
            //}
            //else {
               $col['hinner']=$col['parms']['value'];
            //}
            // 10/20/06, thanks to csnyder@gmail.com via nyphp-talk
            $col['hinner']=htmlentities($col['hinner']);
            unset($col['parms']['value']);
            break;

         case 'numb':
         case 'int':
         case 'money':
            if(vgaGet('AJAX_X3',false)==true) {
               $col['parms']['size']=12;
               $col['parms']['style']='text-align: right';
               break;
            }
         //default:
         //   $col['parms']['type']='textbox';
         //   break;
      }

      // KFD 1/16/07, allow Dynamic list or Dropdown, and override
      //  if present
      $table_id_fko
         =isset($columnoverrides[$colname]['table_id_fko'])
         ? $columnoverrides[$colname]['table_id_fko']
         : $colinfo['table_id_fko'];
      if($table_id_fko<>'' && $colinfo['type_id']<>'date') {
         // Add in the html off to the right
         $col['hright']
            ="<a tabindex=999 href=\"javascript:Info2('"
            .$table_id_fko."','"
            .$col['parms']['name']."')\">Info</a>";

         // This nifty undocumented option causes an ajax request to
         // fetch a row after the value changes
         if(isset($columnoverrides[$colname]['fetchrow'])) {
            $col['parms']['onblur']
               ="FetchRow('$table_id_fko','".$col['parms']['name']."')";
         }

         if ($col['writeable']) {
            $x_table=DD_TableRef($table_id_fko);
            $fkdisplay=ArraySafe($x_table,'fkdisplay','');

            // This is unnecessary in either case
            unset($col['parms']['maxlength']);

            // This branch is the HTML SELECT, for small lists
            //if($fkdisplay<>'dynamic') {
            if($fkdisplay<>'dynamic') {
               // KFD 2/16/07.  Figure out if there is a filter column
               $fkk=$colinfo['table_id_fko'].$colinfo['suffix'];
               $uifc=trim(ArraySafe($table['fk_parents'][$fkk],'uifiltercolumn'));
               $matches=array();
               if($uifc<>'') {
                  $matches[$uifc]=ArraySafe($row,$uifc,'');
               }

               $col['input']='select';
               $col['hinner']=hOptionsFromTable(
                  $table_id_fko
                  ,ArraySafe($row,$colname)
                  ,''
                  ,$matches
               );
               // For search mode, or for allow-empty
               $allow_empty=$table['fk_parents'][$fkk]['allow_empty'];
               if($mode=='search' || $allow_empty) {
                  $col['hinner']='<OPTION></OPTION>'.$col['hinner'];
               }

               // These parms not used for html select
               //unset($col['parms']['class']);
               unset($col['parms']['type']);
               unset($col['parms']['size']);
            }
            else {
               // Turn on this branch with fk_coldisplay=dynamic,
               // gives us an ajax display
               //$fkparms='gp_dropdown='.$table_id.'&gp_col='.$colname;
               $fkparms='gp_dropdown='.$table_id_fko;
               $xcdps=ArraySafe($columndynparms,$colname,array());
               foreach($xcdps as $xcdp) {
                  $xcdpn=$name_prefix.$xcdp;
                  $fkparms.="&adl_$xcdp='+ob('$xcdpn').value+'";
               }
               if ($col['writeable']) {
                  //$col['input']='select';
                  $col['parms']['onkeyup']
                     ="androSelect_onKeyUp(this,'$fkparms',event)";
                  $col['parms']['onkeydown']
                     ="androSelect_onKeyDown(event)";
                  //$col['parms']['onkeyup']
                  //  ="ajax_showOptions(this,'$fkparms',event)";

               }
               // The dynamic list assigns value of key here:
               hidden($col['parms']['name'],$col['parms']['value']);
               $col['parms']['autocomplete']='off';
               $col['parms']['name'].='_select';
               $col['parms']['size']=10;
               $col['parms']['value']=hValueForSelect(
                  $table_id_fko,
                  $col['parms']['value']
               );
            }
         }
      }
   }

   // *****  STEP 3 OF 3: Generate actual HTML
   // Finally, generate the html for each one, with special
   // cases for checkboxes, readonly, and so forth
   foreach($acols as $colname) {
      // Some things, like Images, are completely setup earlier,
      // so just skip them completely
      if($ahcols[$colname]['html']<>'') {
         // echo "Did it for $colname";
         continue;
      }

      $col=&$ahcols[$colname];
      $colinfo = &$table['flat'][$colname];
      $hparms = $col['hparms'];
      if (!$col['writeable']) {
         $hparms.=" READONLY ";
         $col['parms']['class']='ro';
      }
      //if(isset($col['parms']['size'])) $col['parms']['size']=12;
      // now just run out the parms
      foreach ($col['parms'] as $pname=>$pvalue) {
         if($pname=='value') {
            if($colinfo['type_id']=='date' && $pvalue<>'') {
               $pvalue=hDate(strtotime($pvalue));
            }
            $pvalue=htmlentities($pvalue);
         }
         $hparms.=' '.$pname.' ="'.trim($pvalue).'"';
      }
      $hparms.=' id="'.$col['parms']['name'].'"';
      // If there is any generated stuff, do that also KFD 5/23/07
      if(isset($ajscols[$colname])) {
         foreach($ajscols[$colname] as $parm=>$code) {
            $hparms.=' '.$parm.' = "'.$code.'"';
         }
      }


      $inp = $col['input'];
      // 10/20/06, thanks to chsnyder@gmail.com via nyphp-talk
      //             for mentioning we left out html_entities
      $ahcols[$colname]['html']
         ="<".$inp.$hparms.">".$col['hinner']."</".$inp.">";

      // Now stick a date next to every last one of them
      if($table['flat'][$colname]['type_id']=='date') {
         if($col['writeable']) {
            $cname=$col['parms']['name'];
            $ahcols[$colname]['html']
               .="&nbsp;&nbsp;"
               ."<img src='clib/dhtmlgoodies_calendar_images/calendar1.gif' value='Cal'
                      onclick=\"displayCalendar(ob('$cname'),'mm/dd/yyyy',this,true)\">";
         }
      }

      // If there is an error, put that now
      $ahcols[$colname]['html'].=$ahcols[$colname]['errors'];

   }

   // return the entire comprehensive array
   return $ahcols;
}

// An experimental routine from an early draft of AJAX X3,
// will be retired and removed.
function ajsFromDD($table,$prefix) {
   // Generates all AJAX commands for each column in a table
   // based on various things like FETCHDEF and so forth.
   if(!vgaGet('AJAX_X3',false)==true) return array();

   // All controls get a CalcRow at the end
   $retval=array();
   foreach($table['flat'] as $colname=>$colinfo) {
      $retval[$colname]=array('onblur_x'=>array());
   }

   // Lets generate the FETCHDEF stuff
   foreach($table['fk_parents'] as $fkp) {
      $fk=$table['table_id']
         .$fkp['prefix']
         .'_'.$fkp['table_id_par']
         .'_'.$fkp['suffix'];

      // If there are FETCH/DIST entries then assign
      // the ajax call to each child table column
      if(isset($table['FETCHDIST'][$fk])) {
         // Obtain pk of parent table, list of cols in that pk
         $ddpar=DD_TableRef($fkp['table_id_par']);
         $apks=explode(',',$ddpar['pks']);

         // Convert the FETCHDIST list into controls and columns
         $acontrols=array();
         $acolumns =array();
         foreach($table['FETCHDIST'][$fk] as $fetchcol) {
            $acontrols[]=$prefix.$fetchcol['column_id'];
            $acolumns[]=$fetchcol['column_id_par'];
         }
         $cm="ajaxFetch("
            ."'".$fkp['table_id_par']."'"
            .",'$prefix'"
            .",'".$ddpar['pks']."'"
            .",'".implode(',',$acontrols)."'"
            .",'".implode(',',$acolumns)."'"
            .")";

         // Now apply this command to each child column
         foreach($apks as $pk) {
            $colchd=$fkp['prefix'].$pk.$fkp['suffix'];
            //$retval[$colchd]['onblur_x'][]="$cm";
            $retval[$colchd]['snippets']['onchange'][]=$cm;
         }
      }
   }

   // All controls get a CalcRow at the end
   foreach($table['flat'] as $colname=>$colinfo) {
      $retval[$colname]['onblur_x'][]='calcRow()';
   }

   // Collapse the entries down
   foreach($retval as $colname=>$info) {
      $retval[$colname]['onblur']=implode(";",$info['onblur_x']);
      unset($retval[$colname]['onblur_x']);
   }

   return $retval;
}



# DEPRECATED, could probably lose this.
function ahInputsFromProjection($table_id,$colnames) {
   $retval=array();
   if(is_null($table_id)) {
      foreach($colnames as $colname=>$x) {
         $hinputs[$colname]['open']='<input type="textbox"';
         $hinputs[$colname]['close']='</input>';
         $hinputs[$colname]['type_id']='';
      }
   }
   else {
      $table=DD_TableRef($table_id);
      foreach($colnames as $colname=>$readonly) {
         $hinputs[$colname]['open']='<input type="';
         $hinputs[$colname]['close']='</input>';
         $hinputs[$colname]['type_id']='';
         if(isset($table['flat'][$colname])) {
            switch ($table['flat'][$colname]['type_id']) {
               case 'cbool':
                  $hinputs[$colname]['open'].='checkbox"';
                  $hinputs[$colname]['type_id']='cbool';
                  break;
               default:
                  $hinputs[$colname]['open'].='textbox"';
            }
         }
      }
   }
   return $hinputs;
}

function ColumnValue(&$table,&$row,$mode,$colname) {
   if(isset($row[$colname])) {
      return $row[$colname];
   }

   if($mode<>'ins') return '';

   // All the rest is for inserts
   $colinfo = &$table['flat'][$colname];
   if($colinfo['automation_id']<>'DEFAULT') {
      return '';
   }
   else {
      // These are different kinds of default values
      if($colinfo['auto_formula']<>'%now') {
         $val=$colinfo['auto_formula'];
      }
      else {
         if ($colinfo['type_id']=='date') {
            $val=hDate(time());
         }
         else {
            $x=time() % 86400;
            $x=intval($x/60);
            $x-=($x % 15);
            $val=strval($x);
         }
      }
      $row[$colname]=$val;
      return $val;
   }
}




function CacheRowPutBySkey(&$table,$skey) {
   $sq="SELECT * FROM ".$table['table_id']
      ." WHERE skey = ".$skey;
   $row=SQL_OneRow($sq);
   $pkcol = $table['pks'];
   $pkval = $row[$pkcol];
   DynFromA($table['table_id']."_".$pkval,$row);
}
function CacheRowPut(&$table,&$row) {
   $pkcol = $table['pks'];
   $pkval = $row[$pkcol];
   $pktyp = $table['flat'][$pkcol]['type_id'];
   $sq="SELECT * FROM ".$table['table_id']
      ." WHERE $pkcol = ".SQL_Format($pktyp,$pkval);
   $row=SQL_OneRow($sq);
   DynFromA($table['table_id']."_".$pkval,$row);
}
function CacheRowGet($table_id,$pkval) {
   return AFromDyn($table_id."_".$pkval);
}


// ==================================================================
// Wrappers to normal functions or simple combos
// ==================================================================
function x_EchoFlush($message,$level=null) {
   $eopen = $eclose = "";
   if ($level) {
      $eopen = "<h$level>";
      $eclose= "</h$level>";
   }
   echo $eopen.$message.$eclose;
   if (!isset($_SERVER['HTTP_HOST'])) {
      echo "\n";
   }
   else {
      echo "<br><script type=\"text/javascript\">scroll(0,document.height);</script>\n";
   }
   if(ob_get_level()>0) {
      ob_flush();
      flush();
   }
}

/** Output a Line w/newline to a file stream
  *
  * For some reason the fputs() does not put out a newline,
  * so this routine does it for you.
  */
function raxFLn($FILE,$text) {
   fputs($FILE,$text."\n");
}

/** Returns array element or empty string
  *
  * This routine checks for the existence of a named
  * array element and returns it if found, otherwise
  * it returns an empty string.
  *
  * This routine allows you to pull array elements that
  * may not exist without tripping a notice.
  */
function raxArray(&$array,$key) {
   return isset($array[$key]) ? $array[$key] : '';
}

// ==================================================================
// Function Email Send.  NOT STUB
// DOES NOT LOG.  Requires from and to
// ==================================================================
function Email_Exp($from,$to,$subject,$body,$headers) {
   if (is_array($to))   { $to  =implode(",",$to);   }

   //html_vardump($from);
   //html_vardump($to);
   //html_vardump($subject);
   //html_vardump($body);

	include_once('Mail.php');
	$recipients =$to;
	$headers['From']    = trim($from);
   $headers['From'] = 'ken@secdat.com';
	$headers['To']      = $to;
	$headers['Subject'] = $subject;
	$headers['Date']    = date("D, j M Y H:i:s O",time());
   html_vardump($headers);
	$params['sendmail_path'] = '/usr/lib/sendmail';
   $params['host']='192.168.1.210';
	// Create the mail object using the Mail::factory method
	$mail_object = Mail::factory('smtp',$params);
	$mail_object->send($recipients, $headers, $body);

   html_vardump($mail_object);
//   if (!$mail_object) {
      //ErrorAdd("Email was not accepted by server");
//   }
  // else {
    //  echo "Tried to send email";
      //$table_ref =DD_TableRef('adm_emails');
      //SQLX_Insert($table_ref,$em,false);
      //$retval=false;
   //}
}

// ==================================================================
// Stubs that call out to other libraries.  These libraries are
// not part of universal operations and we do not want to bog down
// every round trip with loading them up.
//
// The strategy is to take the parameters passed in and assign them
// to some property of $AG.  Then we branch to the relevant library.
// The library should at very least read the inputs, do its thing,
// and then put some return values into the $AG object.  Ideally,
// the library will also detect when the $AG object does not exist
// and put up some HTML allowing the user to enter values manually,
// which makes for a great interactive testing system.
// ==================================================================
function EmailSend($to,$subject,$message) {
	include_once("ddtable_adm_emails.php");
	include_once("x_email.php");
	return X_EMAIL_SEND(
		ARRAY(
			"email_to"=>trim($to)
			,"email_subject"=>trim($subject)
			,"email_message"=>trim($message)
		)
	);
}



function XML_RPC($callcode,$arr_inputs) {
	global $AG;
	$AG["xmlrpc"] = ARRAY("callcode"=>$callcode,"inputs"=>$arr_inputs);
	include("x_xmlrpc.php");
}

function ehFCKEditor($name,&$value) {
   $x=$name;$x=$value; //annoying jedit compile warning
   ?>
   <textarea rows=15 cols=50 style="width: 100%" name="<?=$name?>" id="<?=$name?>"><?=$value?></textarea>
    <script language="javascript">
      editor_generate('<?=$name?>'); // field, width, height
    </script>

   <?php
   //<script language="javascript">
   //</script>
   //include_once('fckeditor.php');
   //$oFCKeditor = new FCKeditor($name) ;
   //$oFCKeditor->BasePath = 'FCKeditor/';
   //$oFCKeditor->Value = $value;
   //$oFCKeditor->Create() ;
}

function  hOptions($rows,$current,$colval,$coldis) {
   if (!is_array($coldis)) {
      $coldis = explode(',',$coldis);
   }

   $retval = '';
   foreach ($rows as $row) {
      if (count($coldis)==1) {
         $disp = X_SQLTS_TO_UNIX($row[$coldis[0]]);
         $disp = Date('F-j-y',$disp);
      }
      else {
         $disp = '';
         foreach ($coldis as $coldis1) {
            $disp.=$row[$coldis1].' ';
         }
      }
      $html_val = ' value="'.$row[$colval].'" ';
      $selected = ($current == $row[$colval]) ? ' SELECTED' : '';
      $retval .="<OPTION".$html_val.$selected.">".$disp."</OPTION>";
   }
   return $retval;
}

function hOptionsFromTable(
    $table_id,$curval=''
    ,$firstletters='',$matches=array()
    ,$distinct = ''
    ) {
   // Pull data dictionary of target table
   $table=DD_TableRef($table_id);

   $rows=rowsForSelect($table_id,$firstletters,$matches,$distinct);

   // now turn it into an option list
   $retval = '';
   $picked = false;
   foreach($rows as $row) {
      $hSELECTED = '';
      if(trim($row['_value'])==trim($curval)) {
         $hSELECTED = 'SELECTED';
         $picked=true;
      }
      $row['_value']=trim($row['_value']);
      $row['_value']=htmlentities($row['_value']);
      $retval
         .="<OPTION VALUE=\"{$row['_value']}\" $hSELECTED>"
         .htmlentities($row['_display'])
         .'</OPTION>';
   }

   // Slip in one at the top if nothing selected
   if(!$picked) {
      $retval
         ="<OPTION VALUE=\"\" SELECTED> </OPTION> "
         .$retval;
   }

   return $retval;
}



/**
name:hValueForSelect
parm:string table_id
parm:string PK_Value
returns:string Display_Value

Looks up a table's "dropdown" projection definition, then finds the row
for the given PK_Value, and displays the columns named in the dropdown
projection.

*/
function hValueForSelect($table_id,$pkval) {
   $table=DD_TableRef($table_id);
   if(!$pkval) return '';

   // Determine which columns to pull and get them
   if(ArraySafe($table['projections'],'dropdown')=='') {
      $proj=$table['pks'];
   }
   else {
      $proj=$table['projections']['dropdown'];
   }
   $collist=str_replace(','," || ' ' || ",$proj);

   $sq="SELECT $collist as _display
          FROM $table_id
         WHERE ".$table['pks']." = ".SQLFC($pkval);
   return SQL_OneValue("_display",$sq);
}


/**
name:hpTabIndexNext
returns:int

Maintains a counter of TABINDEX values and returns the next one to
be used.

Used by [[ahInputsComprehensive]].  Can be used whenever inputs are
being generated and you want them to have a precise tab order.

*/
function hpTabIndexNext($offset=0) {
   $tabindex=vgfGet('tabindex',0)+1;
   vgfSet('tabindex',$tabindex);
   return $tabindex+$offset;
}


/**
name:hOptionsFromRows
parm:array Rows
parm:string Column_value
parm:string Column_innerHTML
returns:string HTML_Fragment

Generates a string of HTML OPTION elements out of a [[Rows Array]], suitable
for inclusion into an HTML SELECT element.

The first paremeter is a [[Rows Array]].  The second parameter names the
column that is used to set the value properties of each OPTION element,
the second parameter names the column used to set the innerHTML of each
OPTION element.
*/
function hOptionsFromRows($rows,$col_value,$col_inner) {
   $retval='';
   foreach ($rows as $row) {
      $retval.="\n"
         .'<OPTION VALUE="'.$row[$col_value].'">'
         .$row[$col_inner]
         .'</OPTION>';
   }
   return $retval;
}


function H_SELECT_OPTS($dbrows,$skey,$table,$colsdsp,$init=false) {
   // If table dd was not passed, get it now
   if(!is_array($table)) {
      $table_id = $table;
      $table = DD_TableRef($table);
   }

   // Turn list of column(s) into an array
   if (!is_array($colsdsp)) {
      $colsdsp = explode(',',$colsdsp);
   }

   $retval= '';
   if ($init && $skey==0) {
      $retval="<OPTION SELECTED>--Select--</OPTION>";
   }

   foreach ($dbrows as $row) {
      $disp = '';
      foreach($colsdsp as $col) {
         $disp.=$row[$col].'  ';
      }
      $html_val = ' value="'.$row['skey'].'" ';
      $selected = ($skey == $row['skey']) ? ' SELECTED' : '';
      $retval .="<OPTION".$html_val.$selected.">".$disp."</OPTION>";
   }
   return $retval;
}

function hSelectMultiFromRows($name,$rows,$colkey,$colval,$selected='',$extra='') {
   $aa = AAFromRows($rows,$colkey,$colval);
   return hSelectMultiFromAA($aa,$name,$selected,$extra);
}

function hSelectFromRows($name,$rows,$colkey,$colval,$selected='',$extra='') {
   $aa = AAFromRows($rows,$colkey,$colval);
   return hSelectFromAA($aa,$name,$selected,$extra);
}

function hSelectFiltered($table_id,$columns,$name='',$selected='',$extra='',$failsafe=array()) {
   // Make an empty select to return on failure
   // Get the correct table_id
   $table_id_resolved   = DDTable_IDResolve($table_id);

   // Send this if we fail;
   $failed  = count($failsafe)>0
            ? hSelectFromAA($failsafe,$name,$selected,$extra)
            : hSelect($name,$selected,'',$extra);
   // Quit on obvious problem
   if(count($columns)==0 ||
      (!$table_id))  return $failed;

   // Find out what column we are missing
   // And generate the where clause for the upcoming select
   $dd_ref  = dd_tableRef($table_id);
   $pkeys   = explode(',',$dd_ref['pks']);
   $missing = array();
   $where   = array();
   foreach($pkeys as $index=>$pkey){
      if(isset($columns[$pkey])){
         $where[] = $pkey." = '".$columns[$pkey]."'";
      }
      else{
         $missing[] = $pkey;
      }
   }
   $where   = implode(' AND ',$where);;
   // Quit if we are not missing exactly 1 column
   if(count($missing)<>1)  return $failed;
   $missing = implode($missing,',');

   // Find the possible values of the missing key
   $possible_sq   = "SELECT distinct $missing
                       FROM $table_id_resolved
                      WHERE $where";
   $possibles  = SQL_AllRows($possible_sq,$missing);
   $retval     = $failsafe;
   // Let's rearrange the array to hand it to hSelectFromAA
   foreach($possibles as $val=>$aMissing){
      $retval[$val]  = $val;
   }
   return hSelectFromAA($retval,$name,$selected,$extra);
   exit;
}

function hSelectFromAA($aa,$name,$selected='',$extra='') {
   $hOpts = '';
   foreach($aa as $value=>$caption) {
      $hSELECTED = (trim($value)==trim($selected)) ? ' SELECTED ' : '';
      $hOpts
         .='<OPTION VALUE="'.$value.'" '.$hSELECTED.'>'
         .$caption
         .'</OPTION>';
   }
   return hSelect($name,$selected,$hOpts,$extra);
}

function hSelectMultiFromAA($aa,$name,$selected='',$extra='') {
   $hOpts = '';
   foreach($aa as $value=>$caption) {
      $hSELECTED = ($value==$selected) ? ' SELECTED ' : '';
      $hOpts
         .='<OPTION VALUE="'.$value.'" '.$hSELECTED.'>'
         .$caption
         .'</OPTION>';
   }
   return hSelectMulti($name,$selected,$hOpts,$extra);
}

function  hSELECT($name,$value,$inner,$extra='') {
   $inner = str_replace(
      'value"'.$value.'"'
      ,'selected value="'.$value.'"'
      ,$inner
   );
   return
      "<SELECT id=\"$name\" name=\"$name\" "
      ." value=\"$value\" $extra>"
      .$inner
      ."</SELECT>";
}

function  hSelectMulti($name,$value,$inner,$extra='') {
   $x=$extra;
   return
      '<SELECT id="'.$name.'" name="'.$name.'[]" '
      .' value = "'.$value.'" size=10 multiple style="width: 30em">'
      .$inner
      .'</SELECT>';
}

function rHE_IMG_Inline($src) {
   if ($src=='') {
      $srcfile = file_get_contents('rax-blank.jpg',true);
      $src = base64_encode($srcfile);
   }
   $pic = 'xx'.rand(100,999);
   $F=FOPEN($GLOBALS['AG']['dirs']['root'].'/'.$pic,'w');
   fputs($F,base64_decode($src));
   fclose($F);
   return
      '<span><image style="float:left;" '
      .'src="'.$pic.'"></span>';
   //return
   //   '<span><object style="float:left;"'
   //   .'  type="image/jpeg" data="data:;base64,'.$src.'">'
   //   .'</object></span>';

      //.'  type="image/jpeg" data="data:;base64,'.$src.'">'

}

function HLINK_Page($page,$caption,$skey) {
   return
      '<a href="index.php?gp_page='.$page.'&gp_skey='.$skey.'">'
      .$caption
      .'</a>';
}

function rH_Concat($listvals,$row) {
   $arrvals = explode(',',$listvals);
   $retval = '';
   foreach ($arrvals as $val) {
      $retval.=substr($val,0,1)=='@'? $row[substr($val,1)] : $val;
   }
   return $retval;
}


//---------------------------------------------------------------------
function raxarr_FindRow(&$arr,$keyname,$keyvalue) {
   $keys = array_keys($arr);
   foreach ($keys as $key) {
      if ($arr[$key][$keyname]==$keyvalue) {
         return $arr[$key];
      }
   }
   return false;
}

function raxIncludeArray($filename) {
   $temp=array();
   @include($filename);
   return $temp;
}

function raxCoalesce($args) {
   foreach ($args as $arg) {
      if (!is_null($arg)) {
         if ($arg<>'') return $arg;
      }
   }
   return '';
}

function CleanCoalesceSkey($default=0) {
   return raxCoalesce(
      array(
         CleanGet('gp_skey','',false)
         ,CleanGet('txt_skey','',false)
         ,$default
      )
   );
}

function raxExplodeToKeys($delim,$string) {
   $retval = array();
   $arr1 = explode($delim,$string);
   foreach ($arr1 as $value) {
      $retval[$value]=$value;
   }
   return $retval;
}


function HTMLE_Table(&$dbrows,$args=array()) {
   // Set up list of columns.  If An explicit list is passed,
   // use that.  If not, use all columns except the suppressed ones
   //
   if(isset($args['cols'])) {
      // use all parameters as they were passed in
      $cols = $args['cols'];
   }
   else {
      // Make list of non-numeric non-suppressed keys
      $colsx = raxExplodeToKeys(',',ArraySafe($args,'colsxlist',''));
      $x = array_keys($dbrows[0]);
      $cols = array();
      foreach ($x as $col) {
         if(!is_numeric($col) && !isset($colsx[$col])) { $cols[$col] = $col; }
      }
   }

   // Get the titles out. If no column details, use column name
   $retval = '';
   $retval .= "<table><tr>";
   foreach ($cols as $col) {
      $title = is_array($col) ? $col['caption'] : $col;
      $retval.='<td class="t-title3">'.$title.'</td>';
   }
   $retval .= "</tr>";

   // now spit out the rows
   foreach($dbrows as $row) {
      $retval .="<tr>";
      foreach ($cols as $colname=>$colinfo) {
         $inner = $row[$colname];
         if(is_array($colinfo)) {
            if (isset($colinfo['hlink'])) {
               $hlink = $colinfo['hlink'];
               $inner = HTMLE_A_ARRAY($row[$colname],array(
                  'gp_page'=>$hlink['gp_page']
                  ,'txt_'.$hlink['valpk']=>trim($row[$hlink['valpk']]))
               );
            }
         }
         $retval.='<td class="t-dark">'.$inner.'</td>';
      }
      $retval.= "</tr>";
   }
   return $retval."</table>";
}

function lFGets($F) {
   return raxFGETS($F);
}

function raxFGETS($F) {
   $retval= fgets($F);
   $retval= str_replace("\n","",$retval);
   $retval= str_replace("\r","",$retval);
   return $retval;
}

function raxLinkBySkey($page,$skey) {
   return 'index.php?gp_page='.$page.'&gp_skey='.$skey;
}

function raxNoDotZero($string) {
   return str_replace('.0','',$string);
}

function raxArrayTotals(&$dest,&$source) {
   foreach ($source as $key=>$value) {
      $old = isset($dest[$key]) ? $dest[$key] : 0;
      $dest[$key] = $value + $old;
   }
}

//---------------------------------------------------------------------
// Two functions for xml public feeds
//---------------------------------------------------------------------
// CODE PURGE 7/6/07, Almost certainly not used
function ehXMLDoc($feed_id,$atts,$xmlresult) {
   // Determine success or failure, default was fail
   if($atts['result']=='fail') {
      $feed_id='error';
      $xmlresult=array(
         'error'=>array(
            array('attributes'=>$atts)
         )
      );
   }
   else {
      $xmlresult=array($feed_id=>array($xmlresult));
      $xmlresult[$feed_id][0]['attributes']=$atts;
   }

   $aelements = array();
   genXMLDocType($xmlresult,$aelements);

   //ISO-8859-1
   header("Content-type: application/xml");
   echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
   echo "<!DOCTYPE $feed_id [ \n";
   foreach($aelements as $element=>$data) {
      if(!is_array($data)) {
         echo "<!ELEMENT $element (#PCDATA)>\n";
      }
      else {
         if (count($data['children'])==0) {
            echo "<!ELEMENT $element (#PCDATA)>\n";
         }
         else {
            $helement = '';
            foreach($data['children'] as $att) {
               $helement.=ListDelim($helement).$att;
            }
            echo "<!ELEMENT $element ($helement)>\n";
         }

         if (count($data['attributes'])>0) {
            $helement = '';
            foreach($data['attributes'] as $att) {
               $helement.="\n   $att CDATA #IMPLIED ";
            }
            echo "<!ATTLIST $element $helement >\n";
         }
      }
   }
   echo "]> \n";

   ehXMLData($xmlresult);
}

function genXMLdoctype(&$node,&$aelements) {
   // Each "node" is a collection of element names to process
   // and each element contains one or more rows to output
   foreach($node as $element=>$rows) {
      if(!isset($aelements[$element])) {
         $aelements[$element]=array(
            'attributes'=>array(),'children'=>array()
         );
      }
      foreach($rows as $row) {
         // if there are attributes, process those
         if (isset($row['attributes'])) {
            $atts='';
            foreach($row['attributes'] as $att=>$value) {
               $aelements[$element]['attributes'][$att]=$att;
            }
            unset($row['attributes']);
         }
         foreach($row as $child=>$data) {
            $aelements[$element]['children'][$child]=$child;
         }
         genXMLDoctype($row,$aelements);
      }
   }
}

function ehXMLData(&$node) {
   // Each "node" is a collection of element names to process
   // and each element contains one or more rows to output
   foreach($node as $element=>$rows) {
      foreach($rows as $row) {
         // if there are attributes, process those
         if (isset($row['attributes'])) {
            $atts='';
            foreach($row['attributes'] as $att=>$value) {
               $atts.=' '.$att.'="'.trim($value).'"';
            }
            unset($row['attributes']);
         }
         echo "<$element"."$atts>\n";
         ehXMLData($row);
         echo "</$element>\n";
      }
   }
}

//---------------------------------------------------------------------
// FUNCTION FAMILY: h  - returns HTML
//---------------------------------------------------------------------

/**
name:sdFromUnixTS
parm:Unix_TS
returns:string string_date
group:Date/Time Functions

returns a string in the form YYYYMMDD from a Unix timestamp.
This type of string has the advantage of being sortable.

The term 'sd' means "string date" from
an old dbase/Foxpro function StringDate().

*/
function sdFromUnixTS($unix_ts=null) {
   if(is_null($unix_ts)) $unix_ts=time();
   return date('Ymd',$unix_ts);
}


function NoZeroes($value) {
   if(intval($value)==0) return '';
   else return str_replace('.0','',$value);
}

function include_incpath($file) {
   if(file_exists_incpath($file)) {
      include($file);
   }
}

function isValidName($value) {
   $value=trim($value);
   if(strlen($value)==0) return false;
   $lkeep='a b c d e f g h i j k l m n o p q r s t u v w x y z'
      .' 0 1 2 3 4 5 6 7 8 9 _';
   $akeep=explode(' ',$lkeep);
   $new = str_replace($akeep,'',$value);
   if (strlen($new)==0) return true;
   else return false;
}



function eTimeBegin() {
   if (!isset($GLOBALS['AG']['etimes'])) $GLOBALS['AG']['etimes']=array();
   $GLOBALS['AG']['etimes'][] = time();
}
function eTimeEnd() {
   if (!isset($GLOBALS['AG']['etimes'])) return -1;
   if (count($GLOBALS['AG']['etimes'])==0)  return -2;
   return time() - array_pop($GLOBALS['AG']['etimes']);
}

function strFrompreg_match($pattern,$subject) {
   $matches=array();
   preg_match($pattern,$subject,$matches);
   return ArraySafe($matches,0,'');
}
function substrFrompreg_match($pattern,$subject) {
   $matches=array();
   preg_match($pattern,$subject,$matches);
   return ArraySafe($matches,1,'');
}


// =======================================================================
// TableRows Functions
// =======================================================================

/**
name:TableRows

These are data that has been structured so that HTML styles can easily
be applied for later rendering.

The basic idea is to pull some data from a database, convert it into
TableRows using something like [[TableRowsFromRows]], and then use
any number of style-applying functions like [[TableRowsSetColumnClass]]
or [[TableRowsClassAlternate]] to set CSS classes of rows and columns,
and finally to render with [[hTableRowsRender]].
*/

/**
name:_default_
parent:TableRows
*/


/**
name:hTableRowsRender
parm:array TableRows
returns:string HTML_Fragment

Accepts an array of [[TableRows]] and returns the HTML ready to go to
the screen.
*/
function hTableRowsRender(&$array) {
   ob_start();
   foreach($array as $tr) {
      echo "\n  <tr>";
      foreach ($tr as $index=>$td) {
         $class=hTagParm('class',ArraySafe($td,'c'));
         $value=ArraySafe($td,'v','');
         echo "\n    <td $class>$value</td>";
      }
      echo "\n  </tr>";
   }
   return ob_get_clean();
}

/**
name:TableRowsFromRows
parm:array Rows
returns:array TableRows

Accepts a [[Rows Array]] and returns a [[TableRows]] array.  None of the
cells of the [[TableRows]] array will have a class assignment.  This is
very important, as some routines such as [[TableRowsClassAlternate]] will
not override an existing class assignment.  Because of those routines that
do not override an existing class assignment, this routine makes no class
assignment.

This routine is normally used for body data, not for the row of header
cells.
*/
function TableRowsFromRows($rows) {
   $retval=array();
   foreach($rows as $row) {
      $newrow=array();
      foreach($row as $colname=>$colvalue) {
         $newrow[$colname]=array('v'=>$colvalue);
      }
      $retval[]=$newrow;
   }
   return $retval;
}

/**
name:TableRowFromArray
parm:array Input
returns:array TableRows

Accepts a numerically indexed array a [[TableRows]] array containing one
inner row.

This routine is normally used for header cells, so that they can be
output using [[hTableRowsRender]].

*/
function TableRowFromArray($array) {
   $retval=array();
   foreach($array as $index=>$value) {
      $retval[$index]=array('v'=>$value);
   }
   return array($retval);
}


/**
name:TableRowsSetClass
parm:array TableRows
parm:string CSS_Class
parm:bool override

Accepts a [[TableRows]] array by reference, and applies a single class
to all cells.  If the third parameter is true, it will override any
existing class assignments, else it will leave them alone.

*/
function TableRowsSetClass(&$rows,$class,$override=false) {
   $rowkeys=array_keys($rows);
   foreach($rowkeys as $rowkey) {
      $colkeys=array_keys($rows[$rowkey]);
      foreach($colkeys as $colkey) {
         if(isset($rows[$rowkey][$colkey]['c'])) {
            if($override) {
               $rows[$rowkey][$colkey]['c']=$class;
            }
         }
         else {
            $rows[$rowkey][$colkey]['c']=$class;
         }
      }
   }
}

/**
name:TableRowsSetColumnClass
parm:array TableRows
parm:string Column_Name
parm:string CSS_Class
parm:bool override

Accepts a [[TableRows]] array by reference, and applies a single class
to one column for all rows.  If the third parameter is true,
it will override any
existing class assignments, else it will leave them alone.

*/
function TableRowsSetColumnClass(&$rows,$colkey,$class,$override=false) {
   $rowkeys=array_keys($rows);
   foreach($rowkeys as $rowkey) {
      if(isset($rows[$rowkey][$colkey]['c'])) {
         if($override) {
            $rows[$rowkey][$colkey]['c']=$class;
         }
      }
      else {
         $rows[$rowkey][$colkey]['c']=$class;
      }
   }
}

/**
name:TableRowsSetClassAlternate
parm:array TableRows
parm:string CSS_Class1
parm:string CSS_Class2
parm:bool override

Accepts a [[TableRows]] array by reference, and applies alternating CSS
classes CSS_Class1 and CSS_Class2 to the rows.

If the third parameter is true, it will override any
existing class assignments, else it will leave them alone.

*/
function TableRowsSetClassAlternate(&$rows,$class1,$class2,$override=false) {
   $rowkeys=array_keys($rows);
   $class=$class1;
   foreach($rowkeys as $rowkey) {
      $colkeys=array_keys($rows[$rowkey]);

      foreach($colkeys as $colkey) {
         if(isset($rows[$rowkey][$colkey]['c'])) {
            if($override) {
               $rows[$rowkey][$colkey]['c']=$class;
            }
         }
         else {
            $rows[$rowkey][$colkey]['c']=$class;
         }
      }

      $class=($class==$class1) ? $class2 : $class1;
   }
}







// ==================================================================
// ==================================================================
// All other random deprecated functions
// ==================================================================
// ==================================================================
/* DEPRECATED */
function HTMLX_Errors() {
	global $AG;
	$retval="";
   if (isset($AG['trx_errors'])) {
      if (is_array($AG['trx_errors'])) {
         foreach ($AG["trx_errors"] as $err) {
            $retval.=ListDelim($retval,"<br><br>").$err."\n";
         }
      }
   }
	$AG["trx_errors"]=array();
	if ($retval=="") return "";
	else return $retval;
}

/* DEPRECATED */
/* CODE PURGE CANDIDATE */
/* this routine is not used by the framework */
function HTMLX_Notices() {
	global $AG;
	$retval="";
	foreach ($AG["messages"] as $err) {
		$retval.=ListDelim($retval,"<br><br>").$err."\n";
	}
	$AG["messages"]=array();
	if ($retval=="") return "";
	else return $retval;
}

/* DEPRECATED */
function ADMIN_LOG($code,$session="",$text="") {
   $code=$session=$text='';
   return;
   /*
	$hostip = $_SERVER["REMOTE_ADDR"];

	global $AG,$admin_cn;
	if (isset($_SESSION[$AG["application"]."_UID"])) {
		$uid = $_SESSION[$AG["application"]."_UID"];
	}
	else { $uid = "--NONE--"; }

	SQL(
		"insert into sys_logs (sys_log_type,session,hostip,uid,sys_log_text) ".
		" values (".$code.",'".$session."','$hostip','$uid','".$text."')");
   */
}


/* DEPRECATED */
function ADMIN_SESSIONCLOSE($session_id,$killcode) {
   $session_id=$killcode;
   /*
	$time = time();
	SQL(
		"update adm_sessions ".
		"  set sess_status='".$killcode."',".
		"  sess_end = ".X_UNIX_TO_SQLTS(time()).
		" where session = '".$session_id."'");
	foreach($_SESSION as $key=>$value) {
		$app = $GLOBALS['AG']['application'].'_';
		if (substr($key,0,strlen($app))==$app) {
			unset($_SESSION[$key]);
		}
	}
   */
}

/* DEPRECATED */
function ADMIN_SESSIONID($ts) {
	global $AG;
	//  Removed REMOTE_ADDR 5/18/05 experimentally to see if
	//  it was causing problems for a user
	//
	//return md5($AG["application"].$ts.$_SERVER["REMOTE_ADDR"]);
	return md5($AG["application"].$ts);
}

/* DEPRECATED */
function G($branch="",$varname=null,$val=null) {
	$branch = strtolower($branch);
	if (is_null($val)) {
		// In this branch we GET the values
		if (is_null($varname)) return ArraySafe($GLOBALS["AG"],$branch,Array());
		else return ArraySafe($GLOBALS["AG"][$branch],$varname);
	}
	else {
		// In this branch we SET the values
		if (is_array($varname)) {
			foreach ($varname as $key=>$value) {
				$GLOBALS["AG"]["hidden"][$key] = $value;
			}
		}
		else {
			$GLOBALS["AG"]["hidden"][$varname] = $val;
		}
		return true;
	}
}

/* DEPRECATED */
function ListDelim($input,$suffix=",") {
	if ($input=="") return ""; else return $suffix;
}

/* DEPRECATED */
function Hidden_make($varname) {
   if (!isset($GLOBALS['AG']['hidden'][$varname]))
      $GLOBALS['AG']['hidden'][$varname] = '';
}

/** Fetch and Repeat a Hidden Variable
  *
  * This routine returns the value of a hidden variable posted
  * in and also generates a new hidden variable to go out with
  * the same value.  Most often used for variable gp_page, but
  * also useful for any context variable that only has to be
  * preserved while visiting a single page.  Anything that has
  * to be preserved across different pages should be made part
  * of the context.
  */
/* DEPRECATED */
function HiddenRepeat($var,$default='') {
   $retval = CleanGet($var,$default,false);
   hidden($var,$retval);
   return $retval;
}

function explodeempty($delim,$string) {
   if($string=='') {
      return array();
   }
   else {
      return explode($delim,$string);
   }
}


// ================================================================
// SECTION: SECURITY FUNCTIONS
// ================================================================
/**
name:Security Functions
parent:Framework API Reference

[[Security]] functions allow you to customize the behavior of your
application and make use of various security features offered by
the framework.
*/


/**
name:login
parent:Security Functions
parm:string USER_ID
parm:string PASSWORD

This is an experimental routine.

This routine is used by public websites that accept user registration
information.  The idea is that after you create their account you call
this routine to log them in, saving them the annoyance of having to
re-type their username/password at a login screen.
*/
function Login($UID,$PWD) {
   // Make it look like the UID and PWD were passed in on the
   // request, that's where x_login wants to find them.
   gpSet('loginUID',$UID);
   gpSet('loginPWD',$PWD);

   // Create and run the login object
   $obj_login = raxTableObject('x_login');
   $obj_login->Login_Process();

   // If the login worked, disconnect whatever previous connection
   // we had and connect back as this user.  This usually means an
   // anonymous connection is killed.
   if(LoggedIn()) {
      scDBConn_Pop();
      scDBConn_PUsh();
   }
}

/**
name:POSClear

When this is called on a system using Point-of-Sale [[Security]], any
further action will require a user to authenticate again.  This is
usually done after a sales order is saved, or a credit memo made, or
any other type of transaction is completed and the terminal is expected
to be left open for the next user.

*/
function POSClear() {
   SessionSet("POS_PAGE","",'FW');
}

// ================================================================
// SECTION: 3RD GENERATION RENDERING
// ================================================================
/*
TODO:

* It does not know how to make columns read-only when on
  a drill-down detail page, either insert or update.

   * BIG ADDITION 2/29/08.  This code was written on the assumption
   *     that we would want to cache generated HTML that would be
   *     string-substituted at runtime. Such thinking has been
   *     overcome by events, and we will not be doing it that way.
   *     KFD added aWidgets(table,mode,row) that returns complete
   *     set of widgets for any particular use.

   AColInfoFromDD(table)
      takes dd information and renders it out column-by-column

   $aCols = AColsModeProj($table,$mode,$proj)
      calls AColInfoFromDD
      picks only the right mode
      selects only those columns by projection
      selects only those columns by security

   $aCols = AColsModeProjOptions($aCols,$options,$optname)
      modifies $aCols by overlaying options
      (not actually written, included here as placeholder)

   $ahCols = AhFromACols($aCols)
      using options specified, generates all final HTML, still
      on a per-widget basic, including
      all possible HTML options. Contains stuff like
      '--HINNER--' and '--NAME--' and '--TABINDEX--'

   $ah = hDetailCols($ahxCols, $name, $tabindex)
      Makes final assignment of name and tabindexes, produces the
      actual HTML that can be put onto a form, but with no values.
      --->   this is probably all you need to cache <---
      --->   whatever called this would know the    <---
      --->   table, mode, effective group,          <---
      --->   projection, and options, and whether   <---
      --->   it is a drilldown, and should          <---
      --->   save the end result instead of         <---
      --->   messing around with earlier ones       <---
      --->                                          <---
      ---> Actually though drilldown may mess this  <---
      ---> Up and force us to cache ahcols, or even <---
      ---> acols, because drilldowns flip the       <---
      ---> writable flag and fix values             <---

   $ajs= jsValues($ahCols, $name, $row)
      Creates header routines to assign values, allow for
      reset, and invokes those routines.  Hits errors also.




*/

// KFD 2/29/08 Return complete set of generated widgets
function aWidgets(&$table,$row=array(),$mode='upd',$projection='') {

    // Do the two basics
    $acols=aColsModeProj($table,$mode,$projection);
    $ahcols=aHColsfromACols($acols);

    // These calls are the top of hDetailFromAHCols
    // We are doing cargo-cult programming putting them here
    ahColsNames($ahcols,'x2t_',500);
    $calcRow=vgaGet('calcRow');
    $calcRow=str_replace('--NAME-PREFIX--','x2t_',$calcRow);
    vgaSet('calcRow',$calcRow);

    // This loop is directly lifted from hDetailFromAHCols
    foreach($ahcols as $colname=>$ahcol) {
        //  if no first focus, set it now
        if( vgfGet('HTML_focus')=='' && $ahcol['writable']) {
            vgfSet('HTML_focus',$ahcol['cname']);
        }

        // Replace out the HTML for MIME-H stuff
        // KFD 9/7/07, replace the HTML if it is a WYSIWYG column
        if($ahcol['type_id']=='mime-h') {
            $ahcols[$colname]['htmlnamed']
                = '--MIME-H--'.$ahcol['cname'].'--MIME-H--';
            //$html = '--MIME-H--'.$ahcol['cname'].'--MIME-H--';
        }
    }

    // Now we want to make use of the already written jsValues
    // code w/o copying and pasting too badly
    foreach($ahcols as $colname=>$ahcol) {
        $h = $ahcol['htmlnamed'];
        $ahcols[$colname]['htmlnamed'] =jsValuesOne(
            $ahcols,$colname,$ahcol,'x2t_',$row,$h
        );
    }
    return $ahcols;
}


// This routine generates the value assignments
function jsValues($ahcols,$name,$row,$h) {
   foreach($ahcols as $colname=>$ahcol) {
       $h = jsValuesOne($ahcols,$colname,$ahcol,$name,$row,$h);
   }
   return $h;
}

function jsValuesOne($ahcols,$colname,$ahcol,$name,$row,$h) {
    // KFD 9/7/07, slip this in for mime-h columns, they are
    //             much simpler.
    if($ahcol['type_id']=='mime-h') {
       $dir = $GLOBALS['AG']['dirs']['root'];
       @include_once($dir.'/clib/FCKeditor/fckeditor.php');
       $oFCKeditor = new FCKeditor($name.$colname);
       $oFCKeditor->BasePath   = 'clib/FCKeditor/';
       $oFCKeditor->ToolbarSet = 'Basic';
       $oFCKeditor->Width  = '275' ;
       $oFCKeditor->Height = '200' ;
       $oFCKeditor->Value = trim(ArraySafe($row,$colname,''));
       $hx = $oFCKeditor->CreateHtml();
       $h=str_replace('--MIME-H--'.$name.$colname.'--MIME-H--',$hx,$h);

       // Get rid of the error box completely on wysiwyg fields
       $h=str_replace($name.$colname.'--ERROR--','',$h);
       return $h;
    }

    // Set the value (which also sets x_value_original)
    // KFD 8/6/07, put in the TRIM.  Otherwise a user clicks on a field
    //             and it mysteriously won't accept input.  This is
    //             because it is full of blank spaces!
    $colvalue=trim(ArraySafe($row,$colname,''));
    if($colvalue=='' && $ahcol['mode']=='ins' && !is_null($ahcol['default'])){
        $colvalue=$ahcol['default'];
    }
    // KFD 6/28/07, use formatted value for all except time, and
    //    blank numbers on lookup
    // KFD 8/2/07,  removed this entirely, was putting in zeros
    //              for key columns.  These things should be handled
    //              entirely by defaults in the data dictionary.
    // KFD 8/6/07,  put formatting for dates back in, otherwise it
    //              was coming up 2007-08-07 for dates.
    // KFD 9/7/07,  put in a clause to handle double quotes in char values.
    if($ahcol['type_id']=='date') {
        $colvalue=hFormat($ahcol['type_id'],trim($colvalue));
    }
    elseif($ahcol['type_id']=='dtime') {
        if(trim($colvalue)<>'') {
            $colvalue=date('m/d/Y - h:m A',dEnsureTS($colvalue));
        }
    }
    if($ahcol['formshort']=='char' ||
      $ahcol['formshort']=='varchar' ||
      $ahcol['formshort']=='text'
      ) {
     $colvalue=str_replace('"','&quot;',$colvalue);
    }
    /*
    if($ahcol['type_id']<>'time') {
     if(!(   $ahcol['mode']=='search'
          && in_array($ahcol['formshort'],array('int','numb'))
          && trim($colvalue)==''
          )
        ) {
        $colvalue=hFormat($ahcol['type_id'],trim($colvalue));
     }
    }
    */
    //echo "Setting $name.$colname to $colvalue<br/>";
    $h=str_replace($name.$colname.'--VALUE--',htmlentities( $colvalue ),$h);
    $setInScript = false;
    # KFD 4/21/08, also set in script for value_min/max
    if($ahcol['type_id']=='time')   $setInScript = true;
    if($ahcol['type_id']=='cbool')  $setInScript = true;
    if($ahcol['type_id']=='gender') $setInScript = true;
    if($ahcol['value_min']<>'')     $setInScript = true;
    #if($ahcol['type_id']=='time' || 
    #    $ahcol['type_id']=='cbool' ||
    #    $ahcol['type_id']=='gender'
    # ) {
    if($setInScript) {
        if(gp('ajxBUFFER')) {
            ElementAdd('ajax',"_script|ob('$name$colname').value='$colvalue'");
        }
        else {
            ElementAdd('scriptend',"ob('$name$colname').value='$colvalue'");
        }
    }

    // KFD 3/3/08, translate y/n columns
    $replace_y = $colvalue=='Y' ? 'SELECTED' : '';
    $replace_n = $colvalue=='N' ? 'SELECTED' : '';
    $h=str_replace('--SELECTED-Y--',$replace_y,$h);
    $h=str_replace('--SELECTED-N--',$replace_n,$h);


    // If it's a select, we need to grab some hforSelect
    $innerHTML='';
    if($ahcol['table_id_fko']<>'' && $ahcol['fkdisplay']<>'dynamic') {
     // Generate uifiltercolumns
     $uifc=trim(ArraySafe($ahcol,'uifiltercolumn',''));
     $matches=array();
     if($uifc<>'') {
        $matches[$uifc]=ArraySafe($row,$uifc,'');
     }

     // KFD 10/8/07, application PROMAT needs compound foreign key
     $fkpks = explode(',',$ahcol['fk_pks']);
     $pull  = true;
     $dist  = '';
     if(count($fkpks)>1) {
         // This is a compound.  The first column gets distinct, the
         // second and further columns get nothing
         if(trim($colname)==trim($fkpks[0])) {
             $dist = $colname;  // pull distinct
         }
         else {
             // Don't pull.  Use ajax during runtime and make a
             // one-value dropdown now
             $pull = false;
             $innerHTML
                ="<option SELECTED value=\"$colvalue\">$colvalue</option>";
         }
     }

     // Pull the options
     if ($pull) {
         $innerHTML=hOptionsFromTable(
            $ahcol['table_id_fko']
            ,$colvalue
            ,''
            ,$matches
            ,$dist
         );
     }

     // In some cases there should be a blank value
     if(ArraySafe($ahcol,'allow_empty',false)) {
        if(substr($innerHTML,0,26)<>'<OPTION VALUE="" SELECTED>') {
           $innerHTML='<OPTION VALUE="" SELECTED></OPTION>'.$innerHTML;
        }
     }
    }
    $h=str_replace($name.$colname.'--HINNER--',$innerHTML,$h);

    // Slip in the errors if they are there.
    // Grab these for later
    $colerrs=vgfget('errorsCOL',array());
    $colerrsx=ArraySafe($colerrs,$colname,array());
    $herr='';
    if(count($colerrsx)>0) {
     $herr="<span class=\"x2columnerr\">"
        .implode("<br/>",$colerrsx)
        ."</span>";
     $scr="getNamedItem('x_class_base').value='err'";
     $scr="ob('$name$colname').attributes.".$scr;
     ElementAdd('ajax',"_script|$scr");
     ElementAdd('ajax',"_script|ob('$name$colname').className='x3err'");
    }
    $h=str_replace($name.$colname.'--ERROR--',$herr,$h);


    // ------------------------------------
    // Infinity plus one, register the clear
    // ------------------------------------
    $x="ob('$name$colname')";
    ElementAdd('clearBoxes',"if($x) { $x.value='' }");
   return $h;
}


function hDetailFromAHCols($ahcols,$name,$tabindex,$display='') {
   // Apply the names
   ahColsNames($ahcols,$name,$tabindex);
   //hprint_r($ahcols);
   //exit;

   // Always pull the previously generated calcrow and
   // update it with the name prefix, then save it back again.
   $calcRow=vgaGet('calcRow');
   $calcRow=str_replace('--NAME-PREFIX--',$name,$calcRow);
   vgaSet('calcRow',$calcRow);


   ob_start();
   $first='';
   if($display=='') echo "\n<table class=\"x3detail\" cellspacing=\"1\" cellpadding=\"0\" >";
   foreach($ahcols as $colname=>$ahcol) {
      // Establish names of crucial items
      $cname=$ahcol['cname'];
      $cnmer=$cname."_err";

      //  if no first focus, set it now
      if($first=='' && vgfGet('HTML_focus')=='' && $ahcol['writable']) {
         vgfSet('HTML_focus',$cname);
      }

      // Replace out the HTML
      $html=$ahcol['htmlnamed'];
      // KFD 9/7/07, replace the HTML if it is a WYSIWYG column
      if($ahcol['type_id']=='mime-h') {
          $html = '--MIME-H--'.$ahcol['cname'].'--MIME-H--';
      }

      // Replace out the stuff to the right
      $hrgt=$ahcol['hrgtnamed'];

      switch($display) {
      case '':
        echo "\n<tr><td class=\"x3caption\" >".$ahcol['description'] ."</td>";
        echo "\n<td class=\"x3input\">$html $hrgt</td>";
        echo "\n<td class=\"x3error\" id=\"$cnmer\">$cname--ERROR--</td>";
        echo "\n<td class=\"x3tooltip\">".$ahcol['tooltip'] ."</td></tr>";
        break;
     case 'tds':
        echo "\n<td class=\"x3input\">$html</td>";
     }
   }
   if ($display=='') echo "</table>";
   return ob_get_clean();
}


function WidgetFromAHCols(&$ahcols,$colname,$prefix,$value,$tabindex) {
   // First clear up the names
   ahcolNames($ahcols,$colname,$prefix,$tabindex);
   // Now the value
   $h=$ahcols[$colname]['htmlnamed'];
   $h=str_replace($prefix.$colname.'--VALUE--',htmlentities( $value ),$h);
   //if($ahcols[$colname]['type_id']=='Y') {
      $replace_y = $value=='Y' ? 'SELECTED' : '';
      $replace_n = $value=='N' ? 'SELECTED' : '';
      $h=str_replace('--SELECTED-Y--',$replace_y,$h);
      $h=str_replace('--SELECTED-N--',$replace_n,$h);
   //}
   return $h;
}


function AHColsNames(&$ahcols,$name,$tabindex) {
   foreach($ahcols as $colname=>$ahcol) {
      AHColNamesOne($ahcols,$colname,$name,$tabindex);
   }
}

function AHColNamesOne(&$ahcols,$colname,$name,$tabindex) {
   $cname=$name.$colname;
   $ahcol=$ahcols[$colname];
   $ahcols[$colname]['cname']=$cname;

   if($ahcol['writable']==false) $tabindex=10000;

   // Replace out the HTML
   $html=$ahcol['html'];
   $html=str_replace('--NAME--'       ,$cname     ,$html);
   $html=str_replace('--ID--'         ,$cname     ,$html);
   $html=str_replace('--TABINDEX--'   ,hpTabIndexNext($tabindex),$html);
   $html=str_replace('--NAME-PREFIX--',$name      ,$html);
   $ahcols[$colname]['htmlnamed']=$html;

   // Replace out the stuff to the right
   $hrgt=$ahcol['html_right'];
   $hrgt=str_replace('--NAME--',$cname,$hrgt);
   $hrgt=str_replace('--TABINDEX--',hpTabIndexNext($tabindex),$hrgt);
   $ahcols[$colname]['hrgtnamed']=$hrgt;
}

// Added back in by KFD 4/2/08, required by the classic
// medical program.  We don't care if its the same routine as something
// else because all of this code is now superseded by the html()
// class and related functions.
//
function AHColNames(&$ahcols,$colname,$name,$tabindex) {
   $cname=$name.$colname;
   $ahcol=$ahcols[$colname];
   $ahcols[$colname]['cname']=$cname;

   if($ahcol['writable']==false) $tabindex=10000;

   // Replace out the HTML
   $html=$ahcol['html'];
   $html=str_replace('--NAME--'       ,$cname     ,$html);
   $html=str_replace('--ID--'         ,$cname     ,$html);
   $html=str_replace('--TABINDEX--'   ,hpTabIndexNext($tabindex),$html);
   $html=str_replace('--NAME-PREFIX--',$name      ,$html);
   $ahcols[$colname]['htmlnamed']=$html;

   // Replace out the stuff to the right
   $hrgt=$ahcol['html_right'];
   $hrgt=str_replace('--NAME--',$cname,$hrgt);
   $hrgt=str_replace('--TABINDEX--',hpTabIndexNext($tabindex),$hrgt);
   $ahcols[$colname]['hrgtnamed']=$hrgt;
}



// Takes an array of column information and makes
// all HTML decisions.  Generates all snippets.  Main body
// is just a loop that goes through each column.
//
function ahColsFromaCols($acols,$matches=array()) {
   // Here we "inject" the drilldown values into the
   // array of information for future reference.  Then
   // drill-down matches become non-writable and we forget
   // about any foreign_key behavior
   if(count($matches)==0) {
      $matches=DrillDownMatches();
   }
   foreach($matches as $colname=>$colvalue) {
      if(isset($acols[$colname])) {
         $acols[$colname]['writable']=false;
         $acols[$colname]['table_id_fko']='';
      }
   }

   // Get list of columns
   $cols=array_keys($acols);

   // KFD 8/4/07, have each column determine its first and last
   foreach($cols as $x=>$col) {
      if($x<>0) {
         $acols[$col]['ctl_prv']=$cols[$x-1];
      }
      if($x<>(count($cols)-1)) {
         $acols[$col]['ctl_nxt']=$cols[$x+1];
      }
   }

   foreach($cols as $i) {
      $acol=&$acols[$i];
      ahColFromACol($acol);
   }
   return $acols;
}

function ahColFromACol(&$acol) {
   // Link to the subarray and assign any defaults
   $acol['html_element']='input';
   $acol['html_right']  ='';
   $acol['html_inner']  ='';
   $acol['text-align']  = 'left';
   $acol['hparms']=array(
      'class'=>'x3'.$acol['mode']
      ,'name'=>'--NAME--'
      ,'nameprefix'=>'--NAME-PREFIX--'
      ,'id'=>'--ID--'
      ,'tabindex'=>'--TABINDEX--'
      ,'tooltip'=>ArraySafe($acol,'tooltip','')
      ,'value'=>'--NAME----VALUE--'
      ,'x_value_original'=>($acol['mode']=='ins' ? '' : '--NAME----VALUE--')
      ,'x_class_suffix'=>''
      ,'x_error'=>'0'
      ,'x_class_base'=>$acol['mode']
      ,'x_mode'=>$acol['mode']
      ,'x_no_clear'=>ArraySafe($acol,'noclear','N')
      ,'x_ctl_prv'=>ArraySafe($acol,'ctl_prv','')
      ,'x_ctl_nxt'=>ArraySafe($acol,'ctl_nxt','')
      ,'x_value_focus'=>''
      ,'x_type_id'=>$acol['type_id']
   );

   $TOOLTIPS = OptionGet('TOOLTIPS','N');
   switch($TOOLTIPS) {
   case 'NONE':
       $acol['hparms']['title']   = '';
       $acol['hparms']['tooltip'] = '';
   case 'JQUERY_ALSO':
       $acol['hparms']['title'] = $acol['hparms']['tooltip'];
       break;
   case 'JQUERY_ONLY':
       $acol['hparms']['title'] = $acol['hparms']['tooltip'];
       unset($acol['hparms']['tooltip']);
   }

   // For read-onlies, add another class
   if(!$acol['writable']) {
      $acol['hparms']['class']='x3ro';
   }

   // A size correction
   $acol['size'] = min($acol['size'],24);

   // KFD 10/22/07.  For PROMAT application originally
   if(ArraySafe($acol,'pk_change')=='Y') {
     $acol['html_right']
        .="&nbsp;&nbsp;"
        ."<a href=\"javascript:void(0)\""
        .'onclick="ob(\'--NAME--\').readOnly=false;ob(\'--NAME--\').focus()">'
        .'change</a>';
   }


   // ------------------------------------
   // Big deal #1, decisions based on type
   // ------------------------------------
   switch($acol['type_id']) {
   case 'date':
      //  We might put a date button off to the right,
      //  if it is writable
      if($acol['writable']) {
         $acol['html_right']
            .="&nbsp;&nbsp;"
            ."<img src='clib/dhtmlgoodies_calendar_images/calendar1.gif' value='Cal'
               onclick=\"displayCalendar(ob('--NAME--'),'mm/dd/yyyy',this,true)\">";
      }
      $acol['hparms']['size']=$acol['size'];
      if(isset($acol['maxlength'])) {
         $acol['hparms']['maxlength'] = $acol['maxlength'];
      }
      break;
   case 'time':
      $acol['html_element']='select';
      $hinner='';
      $xmin=$acol['value_min'];
      $xmax=$acol['value_max'] ? $acol['value_max'] : 1425;
      for ($x=$xmin;$x<=$xmax;$x+=15) {
         $hinner.="\n<option value=\"$x\">".hTime($x)."</option>";
      }
      if($acol['mode']=='search') {
         $hinner="\n<option value=\"\"></option>".$hinner;
      }
      $acol['html_inner']=$hinner;
      break;
   case 'cbool':
      // DO 3-7-2008  Added if statement so that when column level security is present
      //              changes to field can be "disabled"
      if ( !$acol['writable'] ) {
              $acol['html_element']='input';
      } else {
              $acol['html_element']='select';
              $prefix=$acol['mode']=='search' ? '<option value=""></option>' : '';
              $acol['html_inner']
                 =$prefix
                 ."\n<option --SELECTED-Y-- value='Y'>Y</option>"
                 ."<option --SELECTED-N-- value='N'>N</option>";
      }
      break;
   case 'gender':
      // DO 3-7-2008  Added if statement so that when column level security is present
      //              changes to field can be "disabled"
      if ( !$acol['writable'] ) {
          $acol['html_element']='input';
      } else {
          $acol['html_element']='select';
          $prefix=$acol['mode']=='search' ? '<option value=""></option>' : '';
          $acol['html_inner']
             =$prefix
             ."\n<option value='M'>M</option>"
             ."<option value='F'>F</option>";
      }
      break;
   case 'text':
      $acol['html_element']='textarea';
      $acol['hparms']['rows']=$acol['uirows']==0 ? 4 : $acol['uirows'];
      $acol['hparms']['cols']=$acol['uicols']==0 ? 40 : $acol['uicols'];
      $acol['html_inner']='--NAME----VALUE--';
      $acol['value']='';
      break;
   case 'numb':
   case 'int':
   case 'money':
      if($acol['type_id']<>'int') {
         $acol['hparms']['size']=12;
      }
      else {
         $acol['hparms']['size']=$acol['size'];
      }
      $acol['text-align']='right';
      break;
    case 'mime-h':
       // Do nothing, it all gets done later.
   default:
      $acol['hparms']['size']=$acol['size'];
      if(isset($acol['maxlength'])) {
         $acol['hparms']['maxlength']=$acol['maxlength'];
      }
   }

    // ------------------------------------
    // Big deal GLEPH, value_min & value_max
    // ------------------------------------
    if(a($acol,'value_min','')<>'' && a($acol,'value_max','')<>'') {
        $acol['html_element']='select';
        $acol['hparms']['size']=1;
        $hinner='';
        $xmin = a($acol,'value_min');
        $xmax = a($acol,'value_max');
	// DJO 4-18-08 Add empty row during lookup mode
	if($acol['mode']=='search') {
		$hinner .= "\n<option value=\"\"></option>";
	}
    for ($x=$xmin;$x<=$xmax;$x++) {
            $hinner.="\n<option value=\"$x\">".$x."</option>";
        }
        $acol['html_inner']=$hinner;
        $acol['hparms']['style'] = 'text-align:left';
    }

   // ------------------------------------
   // Big deal B), foreign keys
   // ------------------------------------
   if($acol['table_id_fko']<>'' && $acol['type_id']<>'date') {
      // Says we want an info button next to it
      if($acol['mode']<>'search') {
         $acol['html_right']
            ="<a tabindex=999 href=\"javascript:Info2('"
            .$acol['table_id_fko']."'"
            .",'--NAME--')\">Info</a>";
      }

      if($acol['writable']) {
         // if numeric, set this back
         $acol['text-align']='left';

         if($acol['fkdisplay']<>'dynamic') {
            // HTML SELECT Branch
            $acol['html_element']='SELECT';
            $acol['html_inner']='--NAME----HINNER--';
            if(array_key_exists('size',$acol['hparms']))
               unset($acol['hparms']['size']);
            if(array_key_exists('maxlength',$acol['hparms']))
               unset($acol['hparms']['maxlength']);

            // KFD 10/8/07 compound foreign keys.  If its the first,
            // put in a snippet to pull the next
            $fkpks = explode(',',$acol['fk_pks']);
            if(count($fkpks)>1) {
                if(trim($acol['column_id'])==trim($fkpks[0])) {
                    $tfko = $acol['table_id_fko'];
                    $pk1  = $fkpks[0];
                    $pk2  = $fkpks[1];
                    $acol['snippets']['onblur'][]
                        ="fetchSELECT('$tfko',this,'$pk1',this.value,'$pk2',obv('x2t_$pk2'))";
                }
            }

         }
         else {
            // The core code just says do a dropdown
            $table_id_fko=$acol['table_id_fko'];
            $fkparms='gp_dropdown='.$table_id_fko;
            if ($acol['writable']) {
               //$col['input']='select';
               if(vgfGet('adlversion',2)==1) {
                   $acol['snippets']['onkeyup'][]
                      ="ajax_showOptions(this,'$fkparms',event)";               }
               else {
                   $acol['snippets']['onkeyup'][]
                      ="androSelect_onKeyUp(this,'$fkparms',event)";
                   $acol['snippets']['onkeydown'][]
                      ="androSelect_onKeyDown(event)";
               }
            }
            $acol['hparms']['autocomplete']='off';
         }
      }
   }

   // ------------------------------------
   // Big deal IV. change detection
   // ------------------------------------
   // Any item in update mode needs to get a snippet
   // KFD 8/8/07, JS_KEYSTROKE, see next section, all snippets
   //             for regular events are unconditional, the Js
   //             library routine decides what to do
   $acol['snippets']['onkeyup'][]='inputOnKeyUp(event,this)';

   // ------------------------------------
   // Big deal Epsilon, focus/unfocus
   // ------------------------------------
   // KFD 8/8/07, JS_KEYSTROKE.
   //             Call to javascript routines that will decide
   //             what to do, don't decide here
   $acol['snippets']['onfocus'][]='inputOnFocus(this)';
   $acol['snippets']['onblur'][]='inputOnBlur(this)';
   //if($acol['writable']) {
   //   $acol['snippets']['onfocus'][]='focusColor(this,true)';
   //   $acol['snippets']['onblur'][] ='focusColor(this,false)';
   //}

   // ------------------------------------
   // Big deal #6 execute lookup on ENTER
   // ------------------------------------
   if($acol['mode']=='search') {
      $acol['snippets']['onkeypress'][]="doButton(event,13,'but_lookup')";
   }


   // ------------------------------------
   // 2nd Big deal, execute FETCHes
   // ------------------------------------
   if(count(ArraySafe($acol,'fetches',array()))>0) {
      $fetches=$acol['fetches'];
      foreach($fetches as $fetch) {

         $acol['snippets']['onblur'][]
            ="ajaxFetch("
            ."'".$fetch['table_id_par']."'"
            .",'--NAME-PREFIX--'"
            .",'".$fetch['commapklist']."'"
            .",'".$fetch['commafklist']."'"
            .",'".$fetch['controls']."'"
            .",'".$fetch['columns']."'"
            .",this)";
      }
   }

   // ------------------------------------
   // Does this field force recalc?
   // ------------------------------------
   if($acol['calcs']) {
      // KFD 8/8/07 JS_KEYSTROKES, this will be done on server by
      //            calling back to the server when a value changes.
      //$acol['snippets']['onkeyup'][]="calcRow()";
      $acol['hparms']['autocomplete']='off';
   }


   // ------------------------------------
   // Big deal OMEGA, rendering the element
   // ------------------------------------
   $hparms='';
   foreach($acol['hparms'] as $parm=>$value) {
       $hparms.=$parm.'="'
        .($acol['type_id'] == 'mime-h' && $parm=='value'
            ? $value
            : hx($value)
        ).'"';
   }
   if($acol['text-align']=='right') {
      $hparms.=' style="text-align: right"';
   }
   $hcode='';
   if(isset($acol['snippets'])) {
      foreach($acol['snippets'] as $event=>$list) {
         $hcode.=$event.'="'.implode(';',$list).'"';
      }
   }


   // WE HAD A DISABLED HERE, BUT THEN IT WOULD NOT POST!
   $acol['html']=
      "<".$acol['html_element'].' '.$hparms
      .($acol['writable'] ? '' : ' READONLY ')
      .$hcode
      .'>'.$acol['html_inner']
      .'</'.$acol['html_element'].'>';
}



function aColsModeProj(&$table,$mode,$projection='') {
   if(!Is_array($table)) $table = dd_tableref($table);
   // begin with the info from the data dictionary
   $cols1=aColInfoFromDD($table);

   // Combine the base parameters with the parameters
   // for a particular mode
   $keys=array_keys($cols1['base']);
   $cols2=array();
   foreach($keys as $key) {
      $cols2[$key]=array_merge(
         $cols1['base'][$key]
         ,$cols1[$mode][$key]
      );
   }

   // Make a javascript routine to calculate extended values
   //
   aColsModeProjcalcRow($table,$cols2);

   // Call out to the projection resolver, which includes
   // nifty row-level security, and get the list of
   // columns we will handle
   //
   $colsp=DDProjectionResolve($table,$projection);

   foreach($colsp as $column_id) {
      $cols3[$column_id] = $cols2[$column_id];

      // while we're going row by row, set some props.
      // This way downstream stuff doesn't need to be
      // told again what mode we are in.
      $cols3[$column_id]['mode']=$mode;

      // KFD 3/1/08, correction for column security
      if(ArraySafe($table['flat'][$column_id],'securero')=='Y' && $mode <> 'search') {
          $cols3[$column_id]['writable'] = false;
      }

      // KFD 3/21/08, add in a calculated tooltip, if option is set
      if(OPtionGet('TOOLTIP','N')<>'NONE') {
          if(ArraySafe($table['flat'][$column_id],'tooltip')=='') {
              $tooltip = '';
              $aid = trim($table['flat'][$column_id]['auto_formula']);
              switch(trim($table['flat'][$column_id]['automation_id'])) {
              case 'SEQUENCE':
                  $tooltip="This value is automatically generated";
                  break;
              case 'SUM':
                  $tooltip = "This value is the calculated sum of ".$aid;
                  break;
              case 'MAX':
                  $tooltip = "This value is the calculated minimum from ".$aid;
                  break;
              case 'MIN':
                  $tooltip = "This value is the calculated minimum from ".$aid;
                  break;
              }
              if($tooltip <> '') {
                  $cols3[$column_id]['tooltip'] = $tooltip;
              }
          }
      }
   }

   return $cols3;
}

// This routine is a little different from the other two,
// it generates end-stage code that just needs --NAME-PREFIX
// replaced.
//
function aColsModeProjcalcRow(&$table,&$acols) {
   $retval=array();
   foreach($table['sequenced'] as $colname) {
      if(isset($acols[$colname])) {
         if(count($acols[$colname]['chaincalc'])>0) {
            $retval[]=aColsModeProjCalcRowColumn($acols[$colname]['chaincalc']);
         }
      }
   }
   vgaset('calcRow',implode("\n",$retval));
}
function aColsModeProjCalcRowColumn(&$chaincalc) {
   // Extremely limited, we return the value of the first test
   // unconditionally
   //
   $colname=$chaincalc['column_id'];

   // Pop off the first test, we'll just use that
   $test=array_shift($chaincalc['tests']);
   $expr=array();
   foreach($test['_return'] as $return) {
      if($return['literal_arg']<>'') {
         $expr[]=$return['literal_arg'];
      }
      else {
         $col=$return['column_id_arg'];
         $expr[]="ob('--NAME-PREFIX--$col').value";
      }
   }
   return "if(ob('--NAME-PREFIX--')) ob('--NAME-PREFIX--$colname').value="
      .implode(' '.trim($chaincalc['funcoper']).' ',$expr);
}




/**
name:aColInfoFromDD
parent:Framework Functions
parm:array DD_Table

Accepts a reference to a table's data dictionary array, and returns
an array of specific details by column.  This array contains sufficient
detail to generate HTML w/o further reference to the data dictionary.

This is a framework function, you would not normally call this in code.


*/
function aColInfoFromDD($table) {
   $retval = array();

   // Go column-by-column, then apply table-level stuff
   // to each column
   aColInfoFromDDColumns($table,$retval);
   aColInfoFromDDTable($table,$retval);


   // BIG DEAL: Give each column its derived sequence
   //
   foreach($table['sequenced'] as $sequence=>$colname) {
      if(isset($retval['ins'][$colname])) {
         $retval['ins'][$colname]['sequence']=$sequence;
         $retval['upd'][$colname]['sequence']=$sequence;
      }
   }

   return $retval;
}

function aColInfoFromDDColumns(&$table,&$retval) {
   $perm_upd = DDUserPerm($table['table_id'],'upd');
   // ----------------------------------------------
   // BIG DEAL A: Loop through each row
   // ----------------------------------------------
   foreach($table['flat'] as $colname=>$colinfo) {
      if(!isset($colinfo['uino'])) {
         hprint_r("ERROR IN BUILD, PLEASE CONTACT SECURE DATA SOFTWARE");
         echo "Column $colname";
         hprint_r($colinfo);
         exit;
      }

      // Early return, if there is no UI, don't generate at all
      if($colinfo['uino']=='Y') continue;

      // Clear out array
      $c=array();

      // Initialize a new array for the column, with some
      // basic info that is useful in all modes
      $c['base'] = array(
         'type_id'=>      $colinfo['type_id']
         ,'formshort'=>   $colinfo['formshort']
         ,'column_id'=>   $colname
         ,'colprec'=>     $colinfo['colprec']
         ,'colscale'=>    $colinfo['colscale']
         ,'description' =>$colinfo['description']
         ,'tooltip' => arraySafe($colinfo,'tooltip')
         ,'pk_change'=>   ArraySafe($colinfo,'pk_change','N')
      );
      $c['ins']['sequence']=0;
      $c['upd']['sequence']=0;

      // Load in any default snippets. As of this writing, 6/22/07, these
      // are not generated at build time, but can be added by
      // custom classes.
      $c['ins']['snippets']
         = isset($colinfo['ins']['snippets'])
         ? $colinfo['ins']['snippets']
         : array();
      $c['upd']['snippets']
         = isset($colinfo['upd']['snippets'])
         ? $colinfo['upd']['snippets']
         : array();


      // First property, writable.  Work this out for all
      // three modes.
      $c['search']['writable']=true;
      $c['ins']['writable']   =true;
      $c['upd']['writable']   =$perm_upd;
      if($colinfo['uiro']=='Y') {
         $c['ins']['writable']=false;
         $c['upd']['writable']=false;
      }
      else {
         $autos=array(
            'seqdefault','fetchdef','default'
            ,'blank','none','synch',''
            ,'queuepos','dominant'
         );
         $auto=strtolower(trim($colinfo['automation_id']));
         //echo "the auto for $colname is -".$auto."-<br/>";
         if(!in_array($auto,$autos)) {
            $c['ins']['writable']=false;
            $c['upd']['writable']=false;
         }

         // override for primary key
         if($colinfo['primary_key']=='Y') {
            $c['upd']['writable']=false;
         }
      }

      // This is the default size and maxlength.  Notice that
      // we don't seet a maxlength in search mode.
      //
      $size=$colinfo['dispsize']+1;
      $maxl=$colinfo['dispsize'];
      if(ArraySafe($colinfo,'colscale',0)<>0) {
         $maxl+=1;
      }
      $c['search']['size']  =$size;
      $c['ins']['size']     =$size;
      $c['ins']['maxlength']=$maxl;
      $c['upd']['size']     =$size;
      $c['upd']['maxlength']=$maxl;

      // This is a feature that the column should be all
      // caps, currently done only for primary keys
      if($table['capspk']=='Y' && $colinfo['primary_key']=='Y') {
         $snippet='javascript:this.value=this.value.toUpperCase()';
         $c['ins']['snippets']['onkeyup'][]=$snippet;
         $c['upd']['snippets']['onkeyup'][]=$snippet;
      }

      // set up foreign keys
      $c['search']['table_id_fko']='';
      $c['search']['fkdisplay']='';
      $c['ins']['table_id_fko']=$colinfo['table_id_fko'];
      $c['upd']['table_id_fko']=$colinfo['table_id_fko'];
      $c['ins']['fkdisplay']   =$colinfo['fkdisplay'];
      $c['upd']['fkdisplay']   =$colinfo['fkdisplay'];
      // If the foreign key is compound, give us the whole thing
      if(trim($colinfo['table_id_fko'])<>'') {
          $tabfk = dd_TableRef(trim($colinfo['table_id_fko']));
          $c['upd']['fk_pks'] = $tabfk['pks'];
          $c['ins']['fk_pks'] = $tabfk['pks'];
      }

      // If this column forces calculations, set a flag
      $c['upd']['calcs']=in_array($colname,$table['calcs']);
      $c['ins']['calcs']=$c['upd']['calcs'];
      $c['search']['calcs']=false;

      // Give the guy his chain information
      $c['upd']['chaincalc']=ArraySafe($colinfo,'chaincalc',array());
      $c['ins']['chaincalc']=$c['upd']['chaincalc'];
      $c['search']['chaincalc']=array();

      // Value min and max
      $c['search']['value_min']=$colinfo['value_min'];
      $c['search']['value_max']=$colinfo['value_max'];
      $c['upd']['value_min']=$colinfo['value_min'];
      $c['upd']['value_max']=$colinfo['value_max'];
      $c['ins']['value_min']=$colinfo['value_min'];
      $c['ins']['value_max']=$colinfo['value_max'];

      // uirows and uicols
      $c['upd']['uicols']=$colinfo['uicols'];
      $c['upd']['uirows']=$colinfo['uirows'];
      $c['ins']['uicols']=$colinfo['uicols'];
      $c['ins']['uirows']=$colinfo['uirows'];
      $c['search']['uicols']=$colinfo['uicols'];
      $c['search']['uirows']=$colinfo['uirows'];

      // defaults
      $c['upd']['default']=null;
      $c['search']['default']=null;
      $c['ins']['default']
         =$colinfo['automation_id']=='DEFAULT' && $colinfo['auto_formula']<>''
         ? $colinfo['auto_formula']
         : null;


      // Add results into final array
      $retval['base'][$colname]  =$c['base'];
      $retval['ins'][$colname]   =$c['ins'];
      $retval['upd'][$colname]   =$c['upd'];
      $retval['search'][$colname]=$c['search'];
   }
   //hprint_r($retval);
   return $retval;
}

function aColInfoFromDDTable(&$table,&$retval) {
   // ----------------------------------------------
   // BIG DEAL 2: Table-level stuff assigned to
   //             columns.  Loop through foreign
   //             keys looking for fetches.
   // ----------------------------------------------
   foreach($table['fk_parents'] as $fkp) {
      $fk=$table['table_id']
         .$fkp['prefix']
         .'_'.$fkp['table_id_par']
         .'_'.$fkp['suffix'];

      $cols=explode(',',$fkp['cols_chd']);
      foreach($cols as $col) {
         $retval['ins'][$col]['allow_empty']   =$fkp['allow_empty'];
         $retval['upd'][$col]['allow_empty']   =$fkp['allow_empty'];
         $retval['ins'][$col]['uifiltercolumn']=$fkp['uifiltercolumn'];
         $retval['upd'][$col]['uifiltercolumn']=$fkp['uifiltercolumn'];
      }

      // If there are FETCH/DIST entries then assign
      // relevant information to each child column
      if(isset($table['FETCHDIST'][$fk])) {
         // Obtain pk of parent table, list of cols in that pk
         $ddpar=DD_TableRef($fkp['table_id_par']);
         //$apks=explode(',',$ddpar['pks']);

         // Convert the FETCHDIST list into cols_chd and parent,
         // these are the columns to be fetched and the columns
         // to be written to
         //
         $acontrols=array();
         $acolumns =array();
         foreach($table['FETCHDIST'][$fk] as $fetchcol) {
            $acontrols[]='--NAME-PREFIX--'.$fetchcol['column_id'];
            $acolumns[]=$fetchcol['column_id_par'];
         }

         // Generate a list of details
         $details=array(
            'table_id_par'=>$fkp['table_id_par']
            ,'commapklist'=>$fkp['cols_par']
            ,'commafklist'=>$fkp['cols_chd']
            ,'controls'=>implode(',',$acontrols)
            ,'columns'=>implode(',',$acolumns)
         );

         // Add the details to the insert and update of all
         // affected fk columns
         $afks=explode(',',$fkp['cols_chd']);
         foreach($afks as $afk) {
            $retval['ins'][$afk]['fetches'][]=$details;
            $retval['upd'][$afk]['fetches'][]=$details;
         }
      }
   }
}

function acolBlank($type_id,$colprec=0,$colscale=0) {
   return array(
      'description'=>''
      ,'type_id'=>$type_id
      ,'formshort'=>$type_id
      ,'colprec'=>$colprec
      ,'colscale'=>$colscale
      ,'uiro'=>'N'
      ,'uino'=>'N'
      ,'automation_id'=>'NONE'
      ,'auto_formula'=>''
      ,'primary_key'=>'N'
      ,'pk_change'=>'N'
      ,'dispsize'=>$colprec
      ,'table_id_fko'=>''
      ,'fkdisplay'=>''
      ,'value_min'=>''
      ,'value_max'=>''
      ,'uicols'=>''
      ,'uirows'=>''
   );
}



class XMLTree {
    function XMLTree() {
        $this->stack=array(0);
        $this->nodes=array();
    }

    function openChild($node) {
        // Add the node to the flat list, then get reference to it
        $this->nodes[] = &$node;
        $newidx        = count($this->nodes)-1;

        // Add the reference to the kids of current
        $curidx = $this->stack[ count($this->stack)-1 ];
        $this->nodes[$curidx]['kids'][] = $newidx;

        // Add the reference to the stack, so it is the new current
        $this->stack[] = $newidx;
    }

    function addData($data) {
        $curidx = $this->stack[ count($this->stack)-1 ];
        // Absolutely do not know why these are here, they are being
        // put in by OO.org's output.
        $data = str_replace(chr(160),'',$data);
        $data = str_replace(chr(194),'',$data);
        $this->nodes[$curidx]['value'].=$data;
    }

    function closeChild() {
        array_pop($this->stack);
    }

    function nodeCDATA($idx) {
        $node = $this->nodes[$idx];
        $retval = '';
        for($x=0;$x<count($node['kids']);$x++) {
            $gkid = $this->nodes[ $node['kids'][$x] ];
            if($gkid['name']=='CDATA') {
                $retval.= $gkid['value'];
                break;
            }
        }
        return $retval;
    }

    function nodeHTML($idx) {
        $retval = '';

        $node = $this->nodes[$idx];
        if($node['name'] == 'CDATA') {
            // the cdata elements just get added to the output
            $open  =$node['value'];
            $close = '';
        }
        else {
            // but non-cdata elements get new tags and get recursed
            $attsx = array();
            $tag   = $node['name'];
            foreach($node['atts'] as $attname=>$attvalue) {
                if($attname=='STYLE') continue;
                $attsx[] = $attname.'="'.$attvalue.'"';
            }
            $hatts = implode(' ',$attsx);

            $open ="<$tag $hatts>";
            $close="</$tag>";
        }

        $inner = '';
        foreach($this->nodes[$idx]['kids'] as $kididx) {
            $inner.=$this->nodeHTML($kididx);
        }

        return $open.$inner.$close;
    }
}


function androloadXML($file) {
    $depth  = array();
    global $tree;
    $tree = new XMLTree();

    $xml_parser = xml_parser_create();
    xml_set_element_handler($xml_parser, "startElement", "endElement");
    xml_set_character_data_handler($xml_parser, "characterData");
    if (!($fp = fopen($file, "r"))) {
       die("could not open XML input");
    }
    xml_parse($xml_parser, file_get_contents($file), true);
    /*
    while ($data = fread($fp, 4096)) {
       if (!xml_parse($xml_parser, $data, feof($fp))) {
           die(sprintf("XML error: %s at line %d",
                       xml_error_string(xml_get_error_code($xml_parser)),
                       xml_get_current_line_number($xml_parser)));
       }
    }
    */
    xml_parser_free($xml_parser);
    return $tree;
}


function startElement($parser, $name, $attrs)
{
   // Our stuff.  Make a new node
   $newnode = array(
      'value'=>''
      ,'name'=>$name
      ,'atts'=>$attrs
      ,'kids'=>array()
   );
   global $tree;
   $tree->openChild($newnode);
}

function endElement($parser, $name)
{
   global $tree;
   $tree->closeChild();
}

function characterData($parser, $data) {
    global $tree;

    startElement($parser,'CDATA',array());
    $tree->AddData($data);
    endElement($parser,null);
}

function cssInclude($file,$force_immediate=false) {
    // This program echos out immediately if not in debug
    // mode, otherwise they all get output as one
    if(OptionGet('JS_CSS_DEBUG','Y')=='Y' || $force_immediate) {
        ?>
        <link rel='stylesheet' href='/<?=tmpPathInsert().$file?>' />
        <?php
    }
    else {
        $css = vgfGet('cssIncludes',array());
        $css[]=$file;
        vgfSet('cssIncludes',$css);
    }
}

function cssOutput() {
    // Get the array of files to output and combine
    $css = vgfGet('cssIncludes',array());
    if( count($css)==0 ) return;

    // To do a combo output, make up a filename, generate
    // the combinations, and create a link
    //
    $list = implode('|',$css);
    $md5  = substr(md5($list),0,15);
    $file = fsDirTop()."/clib/css-min-$md5.css";

    if(!file_exists($file)) {
        $string = '';
        foreach($css as $cssone) {
            $string.="\n/* FILE: $cssone */\n";
            $string.=file_get_contents( fsDirTop().$cssone );
        }
        file_put_contents($file,$string);
    }

    // Finally, put out the file
    ?>
    <link rel='stylesheet'
         href='/<?=tmpPathInsert()."clib/css-min-$md5.css"?>' />
    <?php
}

function jqPlugin( $file, $comments='') {
    $jqp = vgfGet('jqPlugins',array());
    $jqp[] = array('file'=>$file,'comments'=>$comments);
    vgfSet('jqPlugins',$jqp);
}

function jsInclude( $file, $comments='',$immediate=false ) {
    if($immediate) {
        ?>
        <script type="text/javascript"
                 src="/<?=tmpPathInsert().$file?>" >
        <?=$comments?>
        </script>
        <?php
    }
    else {
        $ajs = vgfGet('jsIncludes',array());
        $ajs[]=array('file'=>$file,'comments'=>$comments);
        vgfSet('jsIncludes',$ajs);
    }
}

function jsOutput() {
    // Get the array and see if there is anything to do
    $ajs = vgfGet('jsIncludes',array());
    $jqp = vgfGet('jqPlugins',array());
    $ajs = array_merge($ajs,$jqp);
    if( count($ajs)==0 ) return;

    // Initialize array of files that must be minified
    $aj = array();

    // Loop through each file and either add it to list of
    // files to minify or output it directly
    $debug = trim(OptionGet('JS_CSS_DEBUG','Y'));
    foreach($ajs as $js) {
        if($debug=='N') {
            $aj[] = $js['file'];
            if($js['comments']<>'') {
                ?>
                <!--
                <?=$js['comments']?>
                -->
                <?php
            }
        }
        else {
            ?>
            <script type="text/javascript"
                     src="/<?=tmpPathInsert().$js['file']?>" >
            <?=$js['comments']?>
            </script>
            <?php
        }
    }

    // If they needed minification, we have to work out now
    // what that file will be, maybe generate it, and create
    // a link to it
    //
    if(count($aj)==0) return;
    $list = implode('|',$aj);
    $md5  = substr(md5($list),0,15);
    $file = fsDirTop()."/clib/js-min-$md5.js";

    if(!file_exists($file)) {
        require 'jsmin-1.1.0.php';
        $string = '';
        foreach($aj as $ajone) {
            $f = fsDirTop().$ajone;
            $string.=JSMin::minify(file_get_contents($f));
        }
        file_put_contents($file,$string);
    }

    // Finally, put out the file
    ?>
    <script type="text/javascript"
             src="/<?=tmpPathInsert()."clib/js-min-$md5.js"?>" >
    </script>
    <?php
}

// ==================================================================
// ==================================================================
// BASIC DATABASE COMMANDS
// ==================================================================
// ==================================================================
/**
name:_default_
parent:Basic Database Commands
*/
// ------------------------------------------------------------------
/**
name:Basic Database Commands
parent:Framework API Reference

Andromeda provides a handful of basic database routines that serve several
purposes.  The primary purpose is simply to have efficient routines
that reduce the code you need in your application.

Multi-platform abstraction can always be added later if all basic
SQL commands are wrapped, so this is also a goal, though at this time
Andromeda targets only the Postgres database.
*/

// ==================================================================
// ALL DB routines are prefixed "SQL".  Some create connections,
// some execute commands, some format stuff
// ==================================================================


/**
name:SQL_ConnPush
parm:string User_id
parm:string Database_Name

This routine attempts to make a new database connection.  If
successfull, the currently open default connection, if there is one,
is pushed onto a stack, and this connection becomes the new default.

The connection is closed with [[SQL_ConnPop]].

If the first parameter is 'ADMIN', then the connection is made as the
superuser, otherwise the connection is always made as the username
retrieved by the [[SessionGet]] function for the variable "UID".
*/
function SQL_ConnPush($role='',$db='') {
   return scDBConn_Push($role,$db);
}

/* DEPRECATED */
function scDBConn_Push($role='',$db='') {
   $dbc = isset($GLOBALS['dbconn']) ? $GLOBALS['dbconn'] : null;
   scStackPush('dbconns',$dbc);

   // UID is either admin or logged in user
   if($role==$GLOBALS['AG']['application']) {
      //echo "Going for role!";
      $uid = $role;
      $pwd = $role;
   }
   elseif($role<>'' && $role<>$GLOBALS['AG']['application']) {
      $uid = $GLOBALS['AG']['application']."_".$role;
      $pwd = $GLOBALS['AG']['application']."_".$role;
   }
   else {
      $uid = SessionGet('UID');
      $pwd = SessionGet('PWD');
   }

   //$db = $db=='' ? $GLOBALS['AG']['application'] : $db;
   $db = $GLOBALS['AG']['application'];

   // Now make a connection
   $GLOBALS['dbconn'] = SQL_Conn($uid,$pwd,$db);

   // If the "impersonate" function is there, go with it
   if(SessionGET("UID_IMPERSONATE")<>'') {
       SQL("SET SESSION AUTHORIZATION ".SessionGet("UID_IMPERSONATE"));
   }
}


/**
name:SQL_ConnPop
returns:void

Closes the current default connection if one is open.

If there is a previous default connection on the stack, then that
is popped off and it becomes the current default connection.
*/
function SQL_ConnPop() {
   return scDBConn_Pop();
}

/**
name:SQL
parm:string SQL_Command
returns:resource DB_Rresult

The basic command for all SQL Pass-through operations.  Returns a
result resource that can be scanned.

Use this command when you want to pull rows from a database one-by-one.

There is also a collection of [[Specialized SQL Commands]].
*/
function SQL($sql,&$error=false) {
   return SQL2($sql,$GLOBALS["dbconn"],$error);
}


/**
name:SQL_Fetch_Array
parm:resource Result
parm:int rownum
parm:int type

Accepts a result resource returned by the [[SQL]] function and
returns the next row from the server.  Returns boolean false if
there are no more rows.

This is the preferred method for retrieving results if the row count
is likely to go over a hundred or so rows.  Below 100 rows, it can
be more convenient to use [[SQL_AllRows]].
*/
function SQL_fetch_array($results,$rownum=null,$type=null) {
	if (!is_null($type)) {
		return @pg_fetch_assoc($results,$rownum,$type);
	}
	if (!is_null($rownum)) {
		return pg_fetch_assoc($results,$rownum);
	}
	return pg_fetch_assoc($results);
}


/* DEPRECATED */
function scDBConn_Pop() {
   if (isset($GLOBALS['dbconn'])) {
      SQL_CONNCLOSE($GLOBALS['dbconn']);
   }
   $GLOBALS['dbconn'] = scStackPop('dbconns');
}

/* FRAMEWORK */
function SQL_CONNSTRING($tuid,$tpwd,$app="") {
	global $AG;
	if ($app=="") { $app = $AG["application"]; }
	return
		" dbname=".$app.
		" user=".strtolower($tuid).
		" password=".$tpwd;
}

/* FRAMEWORK */
function SQL_CONN($tuid,$tpwd,$app="") {
	global $AG;
	//if ($app=="") { $app = $AG["application"]; }
   $app = $AG["application"];
   //echo "$tuid $tpwd $app";
	$tcs = SQL_CONNSTRING($tuid,$tpwd,$app);
   if(function_exists('pg_connect')) {
      $conn = @pg_connect($tcs,PGSQL_CONNECT_FORCE_NEW );
   }
   else {
      $conn = false;
   }
	return $conn;
}

/* FRAMEWORK */
function SQL_CONNCLOSE($tconn) {
   @pg_close($tconn);
}

/* DEPRECATED */
/* Use SQL_ConnPush() */
function SQL_CONNDEFAULT() {
	global $AG;
	$uid = SessionGet('UID','');
	$pwd = SessionGet('PWD','');
	return SQL_CONN($uid,$pwd);
}

/* DEPRECATED */
function SQL3($sql) { return SQL2($sql,$GLOBALS["dbconn"]); }

/* FRAMEWORK */
function SQL2($sql,$dbconn,&$error=false)
{
	if ($dbconn==null) {
      // 4/4/07.  Rem'd out because if this is a problem we've usually
      // got pleny of other problems.  The only time this can happen
      // w/o a problem is on a new install, and we don't want stray
      // errors there.
		//echo "<b>ERROR: CALL TO SQL2 WITH NO CONNECTION</b>";
      return;
	}
	global $AG;
	$errlevel = error_reporting(0);
	pg_send_query($dbconn,$sql);
	$results=pg_get_result($dbconn);
	$t=pg_result_error($results);
   $error=false;
	if ($t) {
      $error=true;
      vgfSet('errorSQL',$sql);
      // Made conditional 1/24/07 KFD
      //echo "Error title is".vgfGet("ERROR_TITLE");
      if(SessionGet('ADMIN',false)) {
         ErrorAdd(
            "(ADMIN): You are logged in as an administrator, you will see more"
            ." detail than a regular user."
         );
         ErrorAdd("(ADMIN): ".$sql);
      }
      else {
         // KFD 6/27/07, prevent sending this message more than once
         if(!Errors()) {
            ErrorAdd("There was an error attempting to save:");
         }
      }
		$ts = explode(";",$t);
		foreach ($ts as $onerr) {
         if(trim($onerr)=='') continue;
         // KFD 6/27/07, display errors at top and at column level
         //if(SessionGet('ADMIN',true)) {
         //   ErrorAdd("(ADMIN): ".$onerr);
         //}
         ErrorComprehensive($onerr);

      }
	}
	error_reporting($errlevel);
	return $results;
}

/* FRAMEWORK */
// Comprehensive routine to work out what to do with errors
function ErrorComprehensive($onerr) {
   // POSTGRES hardcode, this is what they put in the beginning of a
   // string of errors.
   $onerr=str_replace('ERROR:','',$onerr);
   $onerr=str_replace("\t",'',$onerr);

   // Save the raw error if a programmer wants to do something with it
   $errsraw=vgfGet('errorsRAW',array());
   $errsraw[]=$onerr;
   vgfSet('errorsRAW',$errsraw);


   // Get previously created list of errors
   $colerrs=vgfGet('errorsCOL',array());

   // Get the column, error, and text, then see if the
   // application has overridden them.
   list($column,$error,$text) = explode(',',$onerr,3);
   $errorStrings=vgfGet('errorStrings',array());
   if(isset($errorStrings[$error])) {
       $text = $errorStrings[$error];
   }

   $column=trim($column);

   if($column=='*') {
      // A table-level error begins with an asterisk, report this
      // as an old-fashioned error that appears at the top of the page
      ErrorAdd($text);
   }
   else {
      // This is a column level error.  It is being stored for
      // display later.
      $colerrs[$column][]=$text;

      // KFD 6/27/07, by putting this here, every error gets reported
      // both at its column level and at the top
      ErrorAdd($column.": ".$text);
   }

   vgfSet('errorsCOL',$colerrs);
}

/**
name:SQL_Num_Rows
parm:resource DB_Result
returns:int

Accepts a result returned by a call to [[SQL]] and returns the
number of rows in the result.
*/
function SQL_NUM_ROWS($results) { return SQL_NUMROWS($results); }
function SQL_NUMROWS($results) {
	return pg_numrows($results);
}


function sqlFormatRow($tabdd,$row) {
   $flat  =$tabdd['flat'];
   $retval=array();
   foreach($row as $column=>$value) {
      if(isset($flat[$column])) {
         $retval[$column] = sql_Format($flat[$column]['type_id'],$value);
      }
   }
   return $retval;
}


/**
name:SQL_Format
parm:string Type_ID
parm:any Value
parm:int Clip_Length
returns:string

Takes any input value and type and formats it for direct substitution
into a SQL string.  So for instance character values are escaped for
quotes and then surrounded by single quotes.  Numerics are returned
as-is, dates are formatted and so forth.

The optional third parameter specifies a maximum length for character
and varchar fields.  If it is non-zero, the value will be clipped to
that length.

If you use this command for every value received from the browser when
you build SQL queries, then your code will be safe from SQL Injection
attacks.  All framework commands that build queries use this command for
all literals provided to them.
*/
function SQL_FORMAT($t,$v,$clip=0) {
	global $AG;
	switch ($t) {
    case 'mime-x':
        return "'".SQL_ESCAPE_BINARY($v)."'";
        break;
    case "char":
    case "vchar":
    case "text":
    case "url":
    case "obj":
    case "cbool":
    case 'ssn':
    case 'ph12':
    case "gender":
        if($clip>0 && strlen($v) > $clip) $v = substr($v,0,$clip);
        // KFD 9/10/07, one of the doctors wants all caps
        if(OptionGet('ALLCAPS')=='Y') {
            $v= strtoupper($v);
        }
        return "'".SQL_ESCAPE_STRING($v)."'";
        break;
    case "mime-h":
         if($clip>0 && strlen($v) > $clip) $v = substr($v,0,$clip);
			//return "'".SQL_ESCAPE_BINARY($v)."'";
			return "'".SQL_ESCAPE_STRING($v)."'";
			break;
    case "dtime":
        if ($v=="") return "null";
        //else return X_UNIX_TO_SQLTS($v);
        else return "'".date('r',dEnsureTS($v))."'";
        break;
    case "date":
    case "rdate":
         // A blank is sent as null to server
			if($v=="") return "null";
         if($v=='0') return 'null';

         // Try to detect case like 060507
         if(   strlen($v)==6
            && strpos($v,'/')===false
            && strpos($v,'-')===false) {

            $year=substr($v,4);
            $year = $year < 20 ? '20'.$year : '19'.$year;
            $v = substr($v,0,2).'/'.substr($v,2,2).'/'.$year;
            $v=strtotime($v);
         }
         // Try to detect case like 06052007
         elseif(   strlen($v)==8
            && strpos($v,'/')===false
            && strpos($v,'-')===false) {

            if(substr($v,0,2)=='19' || substr($v,0,2)=='20') {
               $v = substr($v,0,2).'/'.substr($v,2,2).'/'.substr($v,4);
            }
            else {
               $v = substr($v,4,2).'/'.substr($v,6,2).'/'.substr($v,0,4);
            }
            $v=strtotime($v);
         }
         elseif(!is_numeric($v)) {
            // A USA prejudice, assume they will always enter m-d-y, and
            // convert dashes to slashes so they can use dashes if they want
            $v = str_replace('-','/',$v);
            $parts=explode('/',$v);
            if(count($parts)==2) {
               $parts = array($parts[0],1,$parts[1]);
            }
            if(strlen($parts[0])==4) {
               $parts = array($parts[1],$parts[2],$parts[0]);
            }
            elseif(strlen($parts[2])==2) {
               $parts[2] = $parts[2] < 20 ? '20'.$parts[2] : '19'.$parts[2];
            }
            $v = implode('/',$parts);
            $v=strtotime($v);
         }

         // Any case not handled above we conclude was a unix timestamp
         // already.  So by now we are confident we have a unix timestamp
         return "'".date('Y-m-d',$v)."'";
			break;
		case "money":
		case "numb":
		case "int":
			if ($v=="") { return "0"; }
         else { return SQL_ESCAPE_STRING(trim($v)); }
		case "rtime":
		case "time":
			// Originally we were making users type this in, and here we tried
			// to convert it.  Now we use time drop-downs, which are nifty because
			// the display times while having values of numbers, so we don't need
			// this in some cases.
			//if (strpos($v,":")===false) {	return $v; }
         if($v=='') return 'null';
         return $v;
			//$arr = explode(":",$v);
			//return ($arr[0]*60) + $arr[1];
	}
}

/**
name:SQLFC
parm:string Value
returns:string SQL_Value

Shortcut to [[SQL_Format]] for string values.
*/
function SQLFC($value) { return SQL_Format('char',$value); }
/**
name:SQLFN
parm:numb Value
returns:string SQL_Value

Shortcut to [[SQL_Format]] for numeric values.
*/
function SQLFN($value) { return SQL_Format('numb',$value); }
/**
name:SQLFD
parm:date Value
returns:string SQL_Value

Shortcut to [[SQL_Format]] for date values.
*/
function SQLFD($value) { return SQL_Format('date',$value); }
/**
name:SQLFDT
parm:datetime Value
returns:string SQL_Value

Shortcut to [[SQL_Format]] for datetime values.
*/
function SQLFDT($value) { return SQL_Format('dtime',$value); }



/**
name:SQL_ESCAPE_STRING
parm:string Any_Value
returns:string

Wrapper for pg_escape_string, to provide forward-compatibility with
other back-ends.
*/
function SQL_ESCAPE_STRING($val) {
   // KFD 1/31/07 check for existence of pg_escape_string
   return function_exists('pg_escape_string')
      ? pg_escape_string(trim($val))
      : str_replace("'","''",trim($val));
	//return p*g_escape_string($val);
}
/* DEPRECATED */
function SQL_ESCAPE_BINARY($val) {
	return base64_encode($val);
}
/* DEPRECATED */
function SQL_UNESCAPE_BINARY($val) {
	return base64_decode($val);
}
// ==================================================================
// ==================================================================
// SPECIALIZED SQL Commands
// ==================================================================
// ==================================================================
/**
name:_default_
parent:Specialized SQL Commands
*/
// ------------------------------------------------------------------
/**
name:Specialized SQL Commands
parent:Framework API Reference

Specialized SQL commands allow you to use a single command for
many common tasks that would otherwise take several commands.  The
routine [[SQL_OneValue]] for instance executes a query and pulls a single
column out of the first row and returns it.

Some specialized SQL commands are also dictionary-aware, so that the
command [[SQLX_UpdateOrInsert]] will try to find a row based on the table's
primary key, and will also only issue commands for columns that it
recognizes.

Generous use of Specialized SQL Commands is one of the ways to make
the most of Andromeda, there is a command for most any common operation
you want to perform.

*/

/**
name:SQL_OneValue
parm:string Column_ID
parm:string SQL_Command

Accepts and executes a SQL command on the current default connection.
It then fetches the first row of the result, and if it can find the
named column, returns its value.

Any failure at any stage returns false.

Be careful that the SQL_Command actually return one or at most a few
rows, if a command is issued to the server that would return 1 million
rows, the server will execute the entire command, even though it only
returns the first row to PHP.
*/
function SQL_OneValue($column,$sql) {
   //echo $column;
   //echo $sql;
	$results = SQL($sql);
   if ($results===false) return false;
	$row = SQL_FETCH_ARRAY($results);
   if ($row===false) return false;
   if (!isset($row[$column])) return false;
	return $row[$column];
}

/**
name:SQL_OneRow
parm:string SQL_Query
returns:array Row

Accepts a SQL query and returns the first row only of the result.  If the
query returns zero rows the routine returns boolean false.

Note that the query itself should return only 1 or  a few rows, using this
routine is not a substitute for planning an efficient query.  If you hand
this routine a query that generates 1 million rows, the server will still
generate the entire result, even though it only gives back the first one.
*/
function SQL_OneRow($sql) {
	$results = SQL($sql);
	$row = SQL_FETCH_ARRAY($results);
	return $row;
}

/**
name:SQL_AllRows
parm:string SQL_Command
parm:string Column_id

Executes a SQL command and retrieves all rows into a [[Rows Array]].

If the second parameter is provided, then the values of the named
column are made into the keys for the rows in the result.

Extreme care should be taken with this command.  Experience has shown
that PHP's performance drops dramatically with the size of the result
set, so much so that anything over 100 rows or so should probably not
be contemplated for this command.
*/
function SQL_AllRows($sql,$colname='') {
   $results = SQL($sql);
   $rows = SQL_FETCH_ALL($results);
   if ($rows===false) return array();

   // Simple default is just the rows
   if ($colname=='') {
      return $rows;
   }

   // Maybe though they want each row referenced by some column value
   $retval = array();
   foreach($rows as $row) {
      $retval[trim($row[$colname])] = $row;
   }
   return $retval;

}


/* DEPRECATED */
function SQL_FETCH_ARRAY_Decode($dbres,$cols) {
   $retval = SQL_FETCH_ARRAY($dbres);
   foreach($cols as $colname) {
      $retval[$colname] = base64_decode($retval[$colname]);
   }
   return $retval;
}

/* FRAMEWORK */
function SQL_fetch_all($results) {
   // The only case where the function will not exist is on a
   // new install where it is missing.  In that case we don't want
   // errors all over the screen, we want to trap it and report it
   // gracefully
   if(function_exists('pg_fetch_all') && $results)
      return pg_fetch_all($results);
   else
      return false;
}

/* DEPRECATED */
function SQL_FKJOIN($pks,$fkey_suffix,$child="",$parent="") {
	$retval = "";
	$pksarr = explode(",",$pks);
	foreach ($pksarr as $colname) {
		$retval.=ListDelim($retval," AND ").
			$child.".".$colname.$fkey_suffix." = ".$parent.".".$colname;
	}
	return $retval;
}

/**
name:SQLX_TrxBegin
returns:void

Opens a transaction on the server.  On most platforms this is
equivalent to "BEGIN TRANSACTION".
*/
function SQLX_TrxBegin() {
	global $dbconn,$AG;
   if(!isset($AG['trxlevel'])) $AG['trxlevel']=0;
	if ($AG["trxlevel"]<>0) { ErrorsAdd("ERROR: Nested transactions are not allowed"); }
	else {
		$AG["trxlevel"]++;
		SQL("BEGIN TRANSACTION");
	}
}

/* FRAMEWORK */
function SQLX_TrxCommit() {
	global $AG;
   if(!isset($AG['trxlevel'])) $AG['trxlevel']=0;
	if ($AG["trxlevel"]<>1) {
		ErrorsAdd("Error, can only commit a trx when level is 1, it is now: ".$AG["trxlevel"]);
	}
	else {
		$AG["trxlevel"]--;
		SQL("COMMIT TRANSACTION");
	}
}

/* FRAMEWORK */
function SQLX_TrxRollback() {
	global $AG;
   if(!isset($AG['trxlevel'])) $AG['trxlevel']=0;
	if ($AG["trxlevel"]<>1) {
		ErrorsAdd("Error, can only rollback a trx when level is 1, it is now: ".$AG["trxlevel"]);
	}
	else {
		$AG["trxlevel"]--;
		SQL("ROLLBACK TRANSACTION");
	}
}

/**
name:SQLX_TrxClose
parm:string Trx_Type_Name

Attempts to commit a transaction.  If there are errors, it rollsback
the transaction and makes an entry in the [[syslogs]] table to
record the error.

If there is an error, and the second parameter has been provided, that
value will go to the "syslogs_name" column of the [[syslogs]] table.
*/
function SQLX_TrxClose($name='') {
	if (!Errors()) {
      SQLX_TrxCommit();
   }
   else {
      // In case of error in a transaction, we will report
      // the error
      SQLX_TrxRollBack();
      // First insert into the new syslogs table
      $table1=DD_TableRef('syslogs');
      $table2=DD_TableRef('syslogs_e');
      $row=array(
         'syslog_type'=>'ERROR'
         ,'syslog_subtype'=>'TRX'
         ,'syslog_name'=>$name
         ,'syslog_text'=>'See table syslogs_e'
      );
      $skey = SQLX_Insert($table1,$row);
      $log = SQL_OneValue(
         'syslog'
         ,'select syslog from syslogs where skey='.$skey
      );
      foreach ($GLOBALS['AG']['trx_errors'] as $err) {
         $row = array(
            'syslog'=>$log
            ,'syslog_etext'=>$err
         );
         SQLX_Insert($table2,$row);
      }
   }
}

/* FRAMEWORK */
function SQLX_TrxLevel() {
   global $AG;
   if(!isset($AG['trxlevel'])) $AG['trxlevel']=0;
   return $AG["trxlevel"];
}


/**
name:SQLX_Insert
parm:string/array table
parm:array Row
parm:bool Rewrite_Skey
parm:bool Clip_Fields
returns:int

In its most basic form, this routine accepts a [[Row Array]]
and attempts to insert it into a table.  Upon success, the routine
returns the skey value of the new row.

The first entry can be either a [[Table Reference]] or the name of
a table.  The second entry is always a [[Row Array]].  This function
makes use of the dictionary to determine the correct formatting of all
columns, and ignores any column in the [[Row Array]] that is not
in the table.

The third parameter is used by the framework, and should always be
false.  If the third parameter is set to true, then this routine
executes a [[gpSet]] with the value of skey for the new row, making
it look like this row came from the browser.

If the fourth parameter is true, values are clipped to column width
to prevent overflows.  This almost guarantees the insert will succeed,
but should only be done if it is acceptable to throw away the ends of
columns.
*/
function SQLX_Insert($table,$colvals,$rewrite_skey=true,$clip=false) {
   if(!is_array($table)) $table=DD_TableRef($table);
   //if (Errors()) return 0;
	$table_id= $table["table_id"];
   $view_id = DDTable_IDResolve($table_id);
 	$tabflat = &$table["flat"];

	$new_cols = "";
	$new_vals = "";
	foreach($tabflat as $colname=>$colinfo) {
		if (isset($colvals[$colname])) {
         //if($colvals[$colname]<>'') {
            if (DD_ColInsertsOK($colinfo,'db')) {
               $cliplen = $clip ? $colinfo['colprec'] : 0;
               $new_cols.=ListDelim($new_cols)." ".$colname;
               $new_vals
                  .=ListDelim($new_vals)." "
                  .SQL_FORMAT($colinfo["type_id"],$colvals[$colname],$cliplen);
            }
         //}
		}
	}
	$sql = "INSERT INTO ".$view_id." ($new_cols) VALUES ($new_vals)";
   //h*print_r($colvals);
   //h*print_r($sql);

   // ERRORROW CHANGE 5/30/07, big change, SQLX_* routines now save
   //  the row for the table if there was an error
   $errflag=false;
	SQL($sql,$errflag);
   if($errflag) {
      vgfSet('ErrorRow_'.$table_id,$colvals);
   }

	$notices = pg_last_notice($GLOBALS["dbconn"]);
   $retval = 0;
	//echo "notices: $notices<br>";
	$matches = array();
	preg_match_all("/SKEY(\D*)(\d*);/",$notices,$matches);
	if(isset($matches[2][0])) {
      $retval = $matches[2][0];
      if ($rewrite_skey) {
         CleanSet("gp_skey",$matches[2][0]);
         CleanSet("gp_action","edit");
      }
	}

   // Possibly cache the row
   $cache_pkey0=vgfget('cache_pkey',array());
   $cache_pkey=array_flip($cache_pkey0);
   if(isset($cache_pkey[$table_id])) {
      CacheRowPut($table,$colvals);
   }

   return $retval;
}

/**
name:SQLX_Inserts
parm:Array Mixed_Rows
parm:Array Constants
parm:boolean stop_on_error

Accepts a [[Mixed_Rows]] array and attempts to insert each row
into its respective table, using [[SQLX_Insert]].

If the 2nd parameter is provided, the values in that array will be
merged into the values of every row.  This is safe because any columns
that do not exist in some tables will be ignored.

If the third parameter is true, the operation will stop on the first
error, otherwise it will continue until every row is processed, even
if there are 10,000 rows and every one of them fails.
*/
function SQLX_Inserts(&$mixedrows,$constants=array(),$stop=false) {
   return SQLX_InsertMixed($mixedrows,$constants,$stop);
}

/* DEPRECATED */
function SQLX_InsertMixed(&$mixedrows,$constants=array(),$stop=false) {
   foreach ($mixedrows as $table_id=>$rows) {
      $table = DD_TableRef($table_id);
      foreach ($rows as $row) {
         $rownew = array_merge($row,$constants);
         SQLX_Insert($table,$rownew,false);
         if($stop==true && Errors()) return;
      }
   }
}

/**
name:SQLX_Update
parm:string/array table
parm:array Row

In its most basic form, this routine accepts a [[Row Array]]
and attempts to update that row in the table.

The first entry can be either a [[Table Reference]] or the name of
a table.  The second entry is always a [[Row Array]].  This function
makes use of the dictionary to determine the correct formatting of all
columns, and ignores any column in the [[Row Array]] that is not
in the table.

*/
function SQLX_Update($table,$colvals,$errrow=array()) {
    if(!is_array($table)) $table=DD_TableRef($table);
    $table_id= $table["table_id"];
    $view_id = DDTable_IDResolve($table_id);
    $tabflat = &$table["flat"];

    $sql = "";
    $st_skey = isset($colvals["skey"]) ? $colvals["skey"] : CleanGet("gp_skey");
    foreach($tabflat as $colname=>$colinfo) {
        if (isset($colvals[$colname])) {
            if (DD_ColUpdatesOK($colinfo)) {
                $sql.=ListDelim($sql).
                    $colname." = ".SQL_FORMAT($colinfo["type_id"],$colvals[$colname]);
            }
        }
    }
    if ($sql <> '') {
        $sql = "UPDATE ".$view_id." SET ".$sql." WHERE skey = ".$st_skey;

        // ERRORROW CHANGE 5/30/07, big change, SQLX_* routines now save
        //  the row for the table if there was an error
        $errflag=false;
        SQL($sql,$errflag);
        if($errflag) {
            vgfSet('ErrorRow_'.$table_id,$errrow);
        }

        // Possibly cache the row
        if(!Errors()) {
            $cache_pkey0=vgfget('cache_pkey',array());
            $cache_pkey=array_flip($cache_pkey0);
            if(isset($cache_pkey[$table_id])) {
                CacheRowPutBySkey($table,$st_skey);
            }
        }
    }
}

/* DEPRECATED */
//function  SQLX_Delete($table_id,$skey) {
//	SQL("Delete from ".$table_id." where skey = ".$skey);
//}

/* DEPRECATED */
function SQLX_FetchRow($table,$column,$value) {
	$t = SQL3("Select * FROM ".$table." WHERE ".$column." = '".$value."'");
	return SQL_FETCH_ARRAY($t);
}


/* DEPRECATED */
function scDBInserts($table_id,&$rows,$skey=false,$clip=false) {
   $table=DD_TableRef($table_id);
   foreach ($rows as $row) {
      SQLX_Insert($table,$row,$skey,$clip);
   }
}

/**
name:SQLX_Delete
parm:string table_id
parm:array Row

Accepts a [[Row Array]] and a [[table_id]] and builds a SQL delete
command out of the values of the [[Row Array]].

Can be extremely destructive!  This routine will delete all of the
rows of a table that match the given columns.  Calling this routine
on an orders table and providing only a customer ID will delete all
of the orders for that customer!
*/
function SQLX_Delete($table_id,$row) {
   $table_dd=DD_TableRef($table_id);
   $view_id = DDTable_IDResolve($table_id);


   $awhere=array();
   foreach ($row as $colname=>$colval) {
      $awhere[]
         =$colname.' = '
         .SQL_Format($table_dd['flat'][$colname]['type_id'],$row[$colname]);
   }

   $SQL="DELETE FROM $view_id WHERE ".implode(' AND ',$awhere);
   //echo $SQL;
   SQL($SQL);
}


/**
name:SQLX_UpdatesOrInserts
parm:string Table_ID
parm:array Rows
returns:void

This function accepts a [[Rows Array]] and processes each row.  Based
on primary key, if the row does not exist in the database it is inserted.
If it does exist, then any non-primary key value in the row will be
updated.

All of the rows are expected to be belong to table Table_ID.
*/
function SQLX_UpdatesOrInserts($table_id,&$rows) {
   return scDBUpdatesOrInserts($table_id,$rows);
}
/* DEPRECATED */
function scDBUpdatesOrInserts($table_id,&$rows) {
   $table=DD_TableRef($table_id);
   foreach ($rows as $row) {
      scDBUpdateOrInsert($table,$row);
   }
}

/**
name:SQLX_UpdatesOrInsert
parm:ref Table_Definition
parm:array Rows
returns:void

This function accepts a single [[Row Array]].  Based
on primary key, if the row does not exist in the database it is inserted.
If it does exist, then any non-primary key value in the row will be
updated.

The first parameter must be a data dictionary table definition, which
you can get with [[DD_TableRef]].
*/
function SQLX_UpdateOrInsert($table,$colvals) {
   return scDBUpdateOrInsert($table,$colvals);
}

function  scDBUpdateOrInsert($table,$colvals) {
   $table_id= $table["table_id"];
   $tabflat = &$table["flat"];

   // First query for the pk value.  If not found we will
   // just do an insert
   //
   $abort = false;
   $a_pk = explode(',',$table['pks']);
   $s_where = '';
   foreach ($a_pk as $colname) {
       if(!isset($colvals[$colname])) {
           $abort = true;
           break;
       }
      $a_where[]=
         $colname.' = '
         .SQL_Format($tabflat[$colname]['type_id'],$colvals[$colname]);
   }

   if($abort) {
       $skey = false;
   }
   else {
       $s_where=implode(' AND ',$a_where);

       $sql = 'SELECT skey FROM '.DDTable_IDResolve($table_id).' WHERE '.$s_where;
       $skey = SQL_OneValue('skey',$sql);
   }
   // STD says on 12/15/2006 that this routine should not put errors on screen
   //if (Errors()) echo HTMLX_Errors();

   if (!$skey) {
      //echo "insert into ".$table_id."\n";
      $retval = SQLX_Insert($table,$colvals,false);
      if (Errors()) {
         // STD says on 12/15/2006 that this routine should not put errors on screen
         //echo HTMLX_Errors();
         //echo $sql;
         $retval = 0;
      }
   }
   else {
      //echo "update ".$table_id." on $skey\n";
      $colvals['skey']=$skey;
      $retval = -$skey;
      SQLX_Update($table,$colvals);
      if (Errors()) {
         // STD says on 12/15/2006 that this routine should not put errors on screen
         //echo HTMLX_Errors();
         //echo $sql;
         $retval = 0;
      }
   }
   return $retval;
}

/* DEPRECATED */
function scDBInsert($table_id,$row,$rewrite_skey=true) {
   $table = DD_TableRef($table_id);
   return SQLX_Insert($table,$row,$rewrite_skey);
}

/* FRAMEWORK */
// Rem'd out 10/26/06, when row-level security was moved server-side
/*
function S*QLX_Filters($tabflat) {
	$ret = "";
	$groupuids = SessionGet("groupuids",array());
	foreach ($groupuids as $col) {
		if (isset($tabflat[$col])) {
			$ret .= ListDelim($ret," AND ")."UPPER($col)=UPPER('".SessionGet("UID")."')";
		}
	}
	return $ret;
}
*/

/* NO DOCUMENTATION */
function SQLX_ToDyn($table,$pkcol,$lcols,$filters=array()) {
   // Turn filters into two strings
   $filt_name=$filt_where='';
   foreach($filters as $colname=>$colvalue) {
      $filt_name .='_'.$colname.'_'.$colvalue;
      $filt_where.=$filt_where=='' ? '' : ' AND ';
      $filt_where.=" $colname = '$colvalue' ";
   }
   $filt_where=$filt_where=='' ? '' : ' WHERE '.$filt_where;

   // first get the name
   $fname='table_'.$table.'_'
      .str_replace(',','_',$lcols).$filt_name
      .'.rpk';

   // Pull from memory if processed, else cache
   if(!isset($GLOBALS['cache'][$fname])) {
      // not in memory, is it on disk?  If not, must
      // execute the query
      $rows=aFromDyn($fname);
      if($rows===false) {
         $rows=array();
         $sq="SELECT $pkcol,$lcols FROM $table $filt_where";
         $db=SQL($sq);
         while ($row=SQL_Fetch_Array($db)) {
            $rows[$row[$pkcol]] = $row;
         }
         DynFromA($fname,$rows);
      }
      $GLOBALS['cache'][$fname]=$rows;
   }
   $retval = &$GLOBALS['cache'][$fname];
   return $retval;
}

/* NO DOCUMENTATION */
function SQLX_SelectIntoTemp($cols,$from,$into) {
	// this is a postgres version
	global $dbconn;
	$sql = "SELECT ".$cols." INTO TEMPORARY ".$into." FROM ".$from;
	SQL2($sql,$dbconn);
}

/**
name:SQLX_Cleanup
parm:array Mixed_Rows

A complete general cleanup of a mixed set of rows to ensure
that they will all insert ok.  This will smear over errors, such
as a value of '-' will become integer 0, strings will be
truncated, and so forth.

There is no return value, the array is accepted by reference.
*/
function SQLX_Cleanup(&$mixedrows) {
   foreach ($mixedrows as $table_id=>$rows) {
      $table = DD_TableRef($table_id);
      $rowkeys = array_keys($rows);
      foreach ($rowkeys as $rowkey) {
         $row = &$mixedrows[$table_id][$rowkey];
         $colnames=array_keys($row);
         foreach ($colnames as $colname) {
            if (isset($table['flat'][$colname])) {
               switch($table['flat'][$colname]['type_id']) {
                  case 'int':
                     $row[$colname] = intval($row[$colname]);
                     break;
                  case 'money':
                  case 'numb':
                     $row[$colname] = floatval($row[$colname]);
                     break;
                  case 'cbool':
                  case 'gender':
                     $row[$colname] = substr($row[$colname],0,1);
                  case 'char':
                  case 'varchar':
                     $len = $table['flat'][$colname]['colprec'];
                     $row[$colname] = substr($row[$colname],0,$len);
                     break;
               }
            }
         }
      }
   }
}


/**
name:rowsForSelect
parm:string Table_id
parm:string First_Letters
return:array rows

Returns an array of rows that can be put into a drop-down select box.
The first column is always "_value" and the second is always "_display".

The second parameter, if provided, filters to the results so that
only values of _display that start with "First_Letters" are returned.

For a multiple-column primary key, this routine will filter for any pk
column that exists in the session array "ajaxvars".  This feature is
controlled by an (as-yet undocumented) feature in [[ahInputsComprehensive]]
that can make inputs use Ajax when their value changes to store their
value in the session on the server.

This was created 1/15/07 to work with Ajax-dynamic-list from
dhtmlgoodies.com.
*/
function RowsForSelect($table_id,$firstletters='',$matches=array(),$distinct='',$allcols=false) {
   $table=DD_TableRef($table_id);

   // Determine which columns to pull and get them
   // KFD 10/8/07, a DISTINCT means we are pulling a single column of
   //              a multiple column key, pull only that column
   if($distinct<>'') {
       $proj = $distinct;
   }
   else {
       if(ArraySafe($table['projections'],'dropdown')=='') {
          $proj=$table['pks'];
       }
       else {
          $proj=$table['projections']['dropdown'];
       }
   }
   $aproj=explode(',',$proj);
   $acollist=array();
   foreach($aproj as $aproj1) {
      $acollist[]="COALESCE($aproj1,'')";
   }
   $collist=str_replace(','," || ' - ' || ",$proj);
   //$collist = implode(" || ' - ' || ",$acollist);
   //syslog($collist);

   // Get the primary key, and resolve which view we have perms for
   // KFD 10/8/07, do only one column if passed
   if($distinct<>'') {
       $pk = $distinct;
   }
   else {
       $pk = $table['pks'];
   }
   $view_id=ddtable_idResolve($table_id);

   // Initialize the filters
   $aWhere=array();

   // Generate a filter for each pk that exists in session ajaxvars.
   // There is a BIG unchecked for issue here, which is that a multi-column
   //  PK must have *all but one* column supplied, and it then returns
   //  the unsupplied column.
   $pkeys   = explode(',',$table['pks']);
   $ajaxvars=afromGP('adl_');
   foreach($pkeys as $index=>$pkey) {
      if(isset($ajaxvars[$pkey])) {
         $aWhere[]="$pkey=".SQLFC($ajaxvars[$pkey]);
         // This is important!  Unset the pk column, we'll pick the leftover
         unset($pkeys[$index]);
      }
   }
   // If we did the multi-pk route, provide the missing column
   //  as the key value
   if(count($ajaxvars)>0) {
      $pk=implode(',',$pkeys);
   }

   // Determine if this is a filtered table
   if(isset($table['flat']['flag_noselect'])) {
      $aWhere[]= "COALESCE(flag_noselect,'N')<>'Y'";
   }

   // Add more matches on
   foreach($matches as $matchcol=>$matchval) {
      $aWhere[] = $matchcol.' = '.SQLFC($matchval);
   }

   // See if there is a hardcoded filter in the program class
   $obj = raxTableObject($table_id);
   if(method_exists($obj,'aSelect_where')) {
       $aWhere[] = $obj->aSelect_where();
       sysLog(LOG_NOTICE,$obj->aSelect_Where());
   }


   // If "firstletters" have been passed, we will filter each
   // select column on it
   //
   // KFD 8/8/07, a comma in first letters now means look in
   //             1st column only + second column only
   $SLimit='';
   $xWhere=array();
   if($firstletters<>'') {
      $SLimit="Limit 30 ";
      if(strpos($firstletters,',')===false) {
         // original code, search all columns
         $implode=' OR ';
         foreach($aproj as $aproj1) {
            $sl=strlen($firstletters);
            $xWhere[]
               ="SUBSTRING(LOWER($aproj1) FROM 1 FOR $sl)"
               ."=".strtolower(SQLFC($firstletters));
         }
      }
      else {
         // New code 8/8/07, search first column, 2nd, third only,
         // based on existence of commas
         $implode=' AND ';
         $afl = explode(',',$firstletters);
         foreach($afl as $x=>$fl) {
            $sl = strlen($fl);
            $xWhere[]
               ="SUBSTRING(LOWER({$aproj[$x+1]}) FROM 1 FOR $sl)"
               ."=".strtolower(SQLFC($fl));
         }
      }
   }
   if(count($xWhere)>0) {
      $aWhere[] = "(".implode($implode,$xWhere).")";
   }

   // Finish off the where clause
   if (count($aWhere)>0) {
      $SWhere = "WHERE ".implode(' AND ',$aWhere);
   }
   else {
      $SWhere = '';
   }

   // Execute and return
   $sDistinct = $distinct<>'' ? ' DISTINCT ' : '';
   $SOB=$aproj[0];
   if($allcols) {
       $sq="SELECT skey,$proj
              FROM $view_id
           $SWhere
            ORDER BY 2 $SLimit";
   }
   else {
       $sq="SELECT $sDistinct $pk as _value,$collist as _display
              FROM $view_id
           $SWhere
             ORDER BY $SOB $SLimit ";
   }
   /*
   openlog(false,LOG_NDELAY,LOG_USER);
   syslog(LOG_INFO,$table['projections']['dropdown']);
   syslog(LOG_INFO,$sq);
   closelog();
   */
   //echo 'echo|'.$sq;
   $rows=SQL_Allrows($sq);
   return $rows;
}

?>
