<?php
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

// ==================================================================
// Very old get/post stuff
// ==================================================================

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
		#if ($reportmissing) {
		#	ErrorAdd("System Error, Received variable does not exist: ".$key);
		#}
		return $tdefault;
	}
	else {
		return $post[$key];
	}
}

// ==================================================================
// HTML Generation
// ==================================================================
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

/* DEPCRECATED */
function HTML_DATE($date) {
	return strftime('%b %d, %Y',$date);
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

// ==================================================================
// ==================================================================
// Pages as objects
// ==================================================================
// ==================================================================

/* DEPRECATED */
function HTML_vardump($array) {
	echo "<pre>\n";
	//var_dump(($array));
   print_r($array);
	echo "</pre>";
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


# DEPRECATED BY KFD 7/25/08
# ElementAdd('script')  no replacement
# ElementAdd('styles')  use a separate style sheet or put it
#                       directly into your html
#
# ElementAdd('jqueryDocumentReady')
# ElementAdd('scriptend')    for both of these, use
#                            jqDocReady('...script...');
#
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


?>
