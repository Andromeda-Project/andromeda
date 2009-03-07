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
# SECTION: Get-Post Variables
#
# Prefix: gp
#
# ==============================================================
/****h* PHP API/GET and POST Variables
*
* NAME
*   GET and POST Variables
*
* FUNCTION
*   This family of PHP functions allows you to retrieve
*   HTTP Request parameters.
*
*   Andromeda has a special system for obtaining GET and POST
*   variables.  When you request a variable, it checks first
*   in the $_GET superglobal and next in the $_POST superglobal.
*   This frees the programmer from having to track these two
*   superglobals independently.
*
******
*/

/****f* GET and POST Variables/gp
*
* NAME
*    gp
*
* FUNCTION
*    The PHP function gp retrieves the Get/Post value associated with the variable
*    name provided.  A user can provide a default value that should be returned
*    in case the requested variable does not exist.
*
* INPUTS
*    * string - Requested variable name
*    * string - value to return if the variable does not exist
*
* RETURNS
*    mixed - either the value of the variable if it was passed
*    to the browser, or the default value if provided, or
*    an empty string.
*
* SOURCE
*/
function gp($key,$vardefault='') {
    #echo "<br/>Gp called with -$key- -$vardefault-";
	$post=$GLOBALS["AG"]["clean"];
    if (!isset($post[$key])) {
        #echo "<br/> -> not set, returning $vardefault";
        return $vardefault;
    }
	else {
        #echo "<br/> -> key exists, returning {$post[$key]}";
        return $post[$key];
    }
}
/******/

/****f* GET and POST Variables/gpExists
*
* NAME
*    gpExists
*
* FUNCTION
*    The PHP function gpExists checks to see if the requested variable is inside
*    the Get/Post parameters.
*
*
* INPUTS
*    string $key	- Requested variable name
*
* RETURNS
*    boolean	- returns true if the variable exists, false if it does not.
*
* SOURCE
*/
function gpExists($key) {
	return isset($GLOBALS["AG"]["clean"][$key]);
}
/******/

/*---f* GET and POST Variables/hgp
*
* NAME
*    hgp
*
* FUNCTION
*	The PHP function hgp retrieves the Get/Post value associated with the
*	variable name provided.  However, this function html encodes the value
*	before it is returned to the user.  A user can provide a default value
*	that should be returned in case the requested variable does not exist.
*
* INPUTS
*    string $key	- Requested variable name
*    string $vardefault - value to return if the variable does not exist
*
* RETURNS
*    mixed - either the value of the variable if it was passed
*    to the browser, or the default value if provided, or
*    an empty string.  Return is html encoded.
*
* SOURCE
*/
function hgp($key,$default='') {
   $temp=gp($key,$default);
   return htmlentities($temp);
}
/*---**/

/*---f* GET and POST Variables/rowFromgp
*
* NAME
*    rowFromgp
*
* FUNCTION
*	The PHP function rowFromgp fetches all values from the Get/Post variables
*	that have names that begin with the provided prefix.
*
* INPUTS
*    string $prefix	- Prefix of the Get/Post variables
*
* RETURNS
*    mixed - either the value of the variable if it was passed
*    to the browser, or the default value if provided, or
*    an empty string.  Return is html encoded.
*
* SOURCE
*/
function rowFromgp($prefix) {
   return aFromgp($prefix);
}
/*---**/

/*---f* GET and POST Variables/removetrailingnewlines
*
* NAME
*    removetrailingnewlines
*
* FUNCTION
*	The PHP function removetrailingnewlines returns a new array with the new
*	line characters filtered out from the end of the provided input.
*
* INPUTS
*    string $input	- input to filter
*
* RETURNS
*    string - Input provided without the trailing newline characters
*
* SOURCE
*/
function removetrailingnewlines($input) {
   while(substr($input,-1,1)=="\n") {
      $input=substr($input,0,strlen($input)-1);
   }
   return $input;
}
/*---**/

/****f* GET and POST Variables/aFromgp
*
* NAME
*    aFromgp
*
* FUNCTION
*	The PHP function aFromGP captures zero or more GET/POST
*   variables and returns an associative array.  The function
*   is called by provided a prefix string.  Every GET/POST
*   variable whose name begins with that prefix is captured
*   and returned.  The keys in the associative array do not
*   contain the prefix, the prefix is stripped.
*
* INPUTS
*   string prefix
*
* RETURNS
*   associative array
*
******/
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

/****f* GET and POST Variables/gpSet
*
* NAME
*    gpSet
*
* FUNCTION
*	The PHP function gpSet 'fakes' a GET/POST variable, making it
*   appear as if it had beent sent from the browser.  The first
*   parameter names the variable, the second parameter is the value.
*   If the named parameter actually came from the browser, the
*   browser's value will be overwritten.
*
* INPUTS
*	string $key	 - Name of variable
*	mixed $value - Value to assign
*
* SOURCE
*/
function gpSet($key,$value='') {
	$GLOBALS["AG"]["clean"][$key] = $value;
}
/******/

/****f* GET and POST Variables/gpSetFromArray
*
* NAME
*    gpSetFromArray
*
* FUNCTION
*	The PHP function gpSetFromArray adds all variables in the provided array
*	into the Get/Post variables.  In the array, the keys are the names of
*	the variables, while the values are the values for the variables.  If
*	a prefix is provided, the prefix will be appended to the beginning of
*	every variable name that is added.
*
* INPUTS
*	string $prefix	- Prefix to add at the beginning of each variable name
*	array $array	- Variables and their values to add into the Get/Post
*			  parameters.
* SOURCE
*/
function gpSetFromArray($prefix,$array) {
   foreach($array as $key=>$value) {
      gpSet($prefix.$key,$value);
   }
}
/******/

/*---f* GET and POST Variables/gpUnSet
*
* NAME
*    gpUnSet
*
* FUNCTION
*	The PHP function gpUnSet removes the variable with the provided name from
*	the Get/Post parameters.
*
* INPUTS
*	string $key	- Variables' name
*
* SOURCE
*/
function gpUnSet($key) {
	if (isset($GLOBALS["AG"]["clean"][$key])) {
      unset($GLOBALS["AG"]["clean"][$key]);
   }
}
/*---**/

/*---f* GET and POST Variables/gpUnsetPrefix
*
* NAME
*    gpUnsetPrefix
*
* FUNCTION
*	The PHP function gpUnsetPrefix removes all variables from the Get/Post
*	parameters with names that begin with the provided prefix.
*
* INPUTS
*    string $prefix	- Prefix of the variables to remove
*
* SOURCE
*/
function gpUnsetPrefix($prefix) {
   foreach($GLOBALS['AG']['clean'] as $key=>$value) {
       $len = substr($key,0,strlen($prefix));
       if($len==$prefix) {
           gpUnset($key);
      }
   }
}
/*---**/

/*---f* GET and POST Variables/gpControls
*
* NAME
*    gpControls
*
* FUNCTION
*	The PHP function gpControls retrieves the current Get/Post parameter
*	controls.
*
* RETURNS
*    The Get/Post parameter controls.
*
* SOURCE
*/
function gpControls() {
   return unserialize(base64_decode(gp('gpControls')));
}
/*---**/

/*---f* GET and POST Variables/rowFromgpInputs
*
* NAME
*	rowFromgpInputs
*
* FUNCTION
*	Deprecated
*
*---**/
function rowFromgpInputs() {
   return afromgp('txt_');
}


/**
* INPUTS
* @deprecated
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

/**
* Puts the current Get/Post parameters into the current session.
*
* INPUTS
*/

/*---f* GET and POST Variables/gpToSession
*
* NAME
*    gpToSession
*
* FUNCTION
*	The PHP function gpToSession takes all of the current Get/Post parameters
*	and dumps them into the current session parameters.
*
* SOURCE
*/
function gpToSession() {
   SessionSet('clean',$GLOBALS['AG']['clean']);
}
/*---**/

# ==============================================================
#
# SECTION: JSON RETURNS
#
# Add elements to the JSON RETURN ARRAY
# ==============================================================
// KFD X4
/****h* PHP API/JSON Returns
*
* NAME
*	JSON Returns
*
* FUNCTION
*	JSON-Return functions are used to save certain variables for later processing.  Usually these
*	variables are used to display information to the user.  The variables that get returned are usually
*	sent to the user via ajax.
*
******/

/****f* JSON Returns/x6Error
*
* NAME
*	x6Error
*
* FUNCTION
*
*	Saves an error to be sent to the browser.  Once the complete JSON
*   call is returned to the browser, errors are displayed in an alert
*   and processing is stopped.
*
* INPUTS
*	string $parm1 - the error to save
*
* SOURCE
*/
function x6Error($parm1) { return x4Error($parm1); }
function x4Error($parm1) {
    $GLOBALS['AG']['x4']['error'][] = $parm1;
}
/******/

/*---f* JSON Returns/x6Notice
*
* NAME
*	x6Notice
*
* FUNCTION
*	Saves a notice for later processing
*
* INPUTS
*	string $parm1 - the notice to save
*
* SOURCE
*/
function x6Notice($parm1) { return x4Notice($parm1); }
function x4Notice($parm1) {
    $GLOBALS['AG']['x4']['notice'][] = $parm1;
}
/*---**/

/*---f* JSON Returns/x6Print_r
*
* NAME
*	x6Print_r
*
* FUNCTION
*	The PHP function x4Print_r dumps the provided variable and saves the output as a notice
*	for later processing.
*
* INPUTS
*	mixed $var - the variable to dump
*
* SOURCE
*/
function x6Print_r($var) { x4Print_r($var); }
function x4Print_r($var) {
    ob_start();
    print_r($var);
    x4Notice(ob_get_clean());
}
/*---**/

/****f* JSON Returns/x6Debug
*
* NAME
*	x6Debug
*
* FUNCTION
*	The PHP function x6Debug saves a debug datum to be returned to
*   the browser.  The browser takes no action with these items, they
*   are meant to be inspected using Firebug or a similar tool.
*
* INPUTS
*	string $parm1 - the debug info to save
*
* SOURCE
*/
function x6Debug($parm1) { return x4Debug($parm1); }
function x4Debug($parm1) {
    $GLOBALS['AG']['x4']['debug'][] = $parm1;
}
/******/

/*f* JSON Returns/x6DebugClean
*
* NAME
*	x6DebugClean
*
* FUNCTION
*	The PHP function x6DebugClean saves debug information and sends it back
*   to the browser in a JSON call.  This routine differes from x6Debug
*   because this method also clears out newlines and compresses whitespace.
*
* INPUTS
*	string $parm1 - the debug info to save
*
* SOURCE
*/
function x6DebugClean($parm1) { return x4DebugClean($parm1); }
function x4DebugClean($parm1) {
    $parm1 = str_replace("\n",'' ,$parm1);
    $parm1 = str_replace("\t",' ',$parm1);
    $parm1 = preg_replace('/\s{2,}/',' ',$parm1);
    $GLOBALS['AG']['x4']['debug'][] = $parm1;
}
/******/



function x4DebugSQL($parm1) {
    $parm1 = str_replace("\n","",$parm1);
    x4Debug($parm1);
}

/****f* JSON Returns/x6HTML
*
* NAME
*	x6HTML
*
* FUNCTION
*	The PHP function x6HTML is saves the HTML fragment to be sent
*   back to the browser.  The first parameter names the ID of the
*   element whose HTML is to be replaced, and the second parameter
*   contains the HTML.  
*
* INPUTS
*	* string $parm1 - the html id 
*	* string $parm2 - the html value
*
* SOURCE
*/
function x6HTML($parm1,$parm2) { return x4html($parm1,$parm2); }
function x4HTML($parm1,$parm2) {
    if(!isset($GLOBALS['AG']['x4']['html'][$parm1])) {
        $GLOBALS['AG']['x4']['html'][$parm1] = '';
    }
    $GLOBALS['AG']['x4']['html'][$parm1] .= $parm2;
}
/******/

/**
* Dumps anything out to the main HTML display.
* Intended for debugging
*
* INPUTS
*	string $parm1  variable
*/

/*f* JSON Returns/x4HtmlDump
*
* NAME
*	x4HtmlDump
*
* FUNCTION
*	The PHP function x4HtmlDump dumps anything out to themain HTML display.  It is intended for
*	debugging.
*
* INPUTS
*	string $parm1 - var to dump
*
* SOURCE
*/
function x4HtmlDump($parm1) {
    ob_start();
    hprint_r($parm1);
    x4HTML('*MAIN*',ob_get_clean());
}
/******/

/**
*
*
* INPUTS
*	string $parm1	script to store
*/

/****f* JSON Returns/x6Script
*
* NAME
*	x6Script
*
* FUNCTION
*	The PHP function x6Script stores a script fragment to be returned
*   to the browser.  If this is called during normal page processing,
*   it is equivalant to a call to jqDocReady.  If it is called on a
*   JSON call, the script is returned to the browser and executed if
*   there are no errors.
*
*   This function strips the <script> and </script> tags from $parm1 and
*	so that you can include the script tags if you like.
*
* INPUTS
*	string - the javascript to execute.
*
* SOURCE
*/
function x6script($parm1) { return x4script($parm1); }
function x4SCRIPT($parm1,$insert = false) {
    $parm1 = preg_replace("/<script>/i",'',$parm1);
    $parm1 = preg_replace("/<\/script>/i",'',$parm1);
    if($insert) {
        array_unshift($GLOBALS['AG']['x4']['script'],$parm1);
    }
    else {
        $GLOBALS['AG']['x4']['script'][] = $parm1;
    }
}
/******/
function x6scriptKill() {
    $GLOBALS['AG']['x4']['script'] = array();
}

/**
* JSON encodes the data to be saved as javascript for later processing.
*
*	mixed $data
*/

/****f* JSON Returns/x6Data
*
* NAME
*	x6Data
*
* FUNCTION
*	The PHP function x6Data takes a variable and sends it to the
*   browser.  After the page is loaded, or the JSON call is processed,
*   the data will appear as a property of the x6.data object.
*
* INPUTS
*	* string - name of data element
*	* mixed - the value or array
*
* SOURCE
*/
function x6Data($name,$data) { 
    $script = "\nx6.data.$name = ".json_encode_safe($data).";";
    x6Script($script);
}
function x4Data($name,$data) {
    if(vgfGet('x6',false)) return x6Data($name,$data);
    $script = "\n\$a.data.$name = ".json_encode_safe($data).";";
    x4Script($script);
}
/******/

function jsonPrint_r($data) {
    ob_start();
    hprint_r();
    $GLOBALS['AG']['x4']['html']['*MAIN*'].=ob_get_clean();
}

/**
* Checks to see if current php version contains JSON functions.
* If it does, then encodes the data.  Otherwise it outputs an
* error.  Safe way to use json_encode because not all PHP setups
* have JSON functions.
*
* INPUTS
*	mixed $data	data to be encoded
* RETURN
*	string	json encoded string
*/

/****f* JSON Returns/json_encode_safe
*
* NAME
*	json_encode_safe
*
* FUNCTION
*	The PHP function json_encode_safe checks to see if current php version contains JSON functions.
*	If it does, then encodes the data.  Otherwise it outputs an
*	error.  Safe way to use json_encode because not all PHP setups
*	have JSON functions.
*
* INPUTS
*	string $data - the data to encode
*
* SOURCE
*/
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
/******/

# ==============================================================
#
# Page/Object handling
#
# ==============================================================
/**
* Builds a dispatch page object for the page $gp_page
*
* INPUTS
*	string $x4page	page to be dispatched
* RETURN
*	object $obj_page	the x4 object that handles that table/page
*/
function x4Object($x4Page) {
    include_once 'androX4.php';
    $class  = 'androX4';
    $file = strtolower($x4Page)=='menu'
        ? 'androX4Menu.php'
        : "x4$x4Page.php";
    if(file_exists_incpath($file)) {
        include_once($file);
        $class = 'x4'.$x4Page;
    }
    $object = new $class($x4Page);
    return $object;
}

/**
* Builds a dispatch page object for the page $gp_page
*
* INPUTS
*	string $gp_page	page to be dispatched
* RETURN
*	object $obj_page	the object representing the dispatched page.
*/
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

# ==============================================================
#
# SECTION: HTML RENDERING
#
# INITIATED KFD 3/24/08, FINAL FORM OF RENDERING LIBRARY AFTER
# MANY EXPERIMENTS WITH MANY DIFFERENT KINDS.  GOAL IS ABSOLUTE
# MINIMUM CODE TO CREATE OBJECT-ORIENTED HTML ELEMENTS.
# ==============================================================
// KFD X4

/****h* PHP API/HTML Generation
*
* NAME
*	HTML Generation
*
* FUNCTION
*	Andromeda uses an object oriented html system in order to prevent the mix of php and html code
*	in andromeda pages.
*
*	To make HTML in andromeda, you use the html function, which builds an html element as an
*	androHtml object.  The object allows you to modify the element in any way you would normally
*	in html.  It is very flexible.
*
*	In order to display your object oriented html, you must render it.  Call the render function on your
*	androHtml parent object to render the whole page.
*
******/

/****f* HTML Generation/hSizepx
*
* NAME
*    hSizepx
*
* FUNCTION
*	The PHP function hSizepx computes a width by examining the size cookie.
*	It assumes that the baseline is 1024, and returns a string of the form
*	"999px" that is scaled up or down based on what skin the user is
*   actually using.
*
*   This function returns a string with 'px' appended.
*
* INPUTS
*    number - Size in 1024x768 mode
*
* SOURCE
*/
function hSizepx($x1024) {
    $app  = $GLOBALS['AG']['application'];
    $size = a($_REQUEST,$app."_size",'1024');
    $final = intval(($x1024 * $size)/1024);
    return $final.'px';
}
/******/

/****f* HTML Generation/hSize
*
* NAME
*    hSize
*
* FUNCTION
*	The PHP function hSizepx computes a width by examining the size cookie.
*	It assumes that the baseline is 1024, and returns number that is 
*   scaled up or down based on what skin the user is
*   actually using.
*
*
* INPUTS
*    number - Size in 1024x768 mode
*
* SOURCE
*/
function hSize($x1024) {
    $app  = $GLOBALS['AG']['application'];
    $size = a($_REQUEST,$app."_size",'1024');
    $final = intval(($x1024 * $size)/1024);
    return $final.'px';
}
/******/


/****f* HTML Generation/html
*
* NAME
*    html
*
* FUNCTION
*	The PHP function html acts as a factory function which creates HTML element
*	objects based on the parameters passed.  HTML element objects are used
*	to prevent the mixing of html and php code.
*
*	You can provide inner html information, and parent elements.  This function
*	will automatically add the created child html element to the provided parent
*	element.
*
* INPUTS
*	string $tag	- HTML tag
*	reference $parent - Reference to parent element
*	mixed $innerHTML - Inner html in this element
*
* RETURN VALUE
*	androHtml object built specifically for the provided parameters.

******
*/
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
        $retval->hp['href']='#';
    }

    if($parent != null) {
        $parent->children[] = $retval;
    }
    return $retval;
}

/****f* HTML Generation/htmlMacroTop
*
* NAME
*    htmlMacroTop
*
* FUNCTION
*	The PHP function htmlMacroTop is a shortcut for creating a top-level
*   div that has class "fadein" (so that it fades in after the page is
*   initialzied) and contains an H1 taken from the page or menu entry's
*   title from the data dictionary.
*
* INPUTS
*	string $page - The page, which is a table name or menu entry
*
* RETURNS
*	androHtml DIV object with a title built in and having class "fadeIn".
******
*/
function &htmlMacroTop($page,$center=false) {
    $retval = html('div');
    $retval->addClass('fadein');
    $h1 = $retval->h('h1',ddPageDescription($page));
    if($center) $h1->hp['style'] = 'text-align: center'; 
    return $retval;
}


/****f* HTML Generation/htmlMacroGridWithData
*
* NAME
*    htmlMacroGridWithData
*
* FUNCTION
*	The PHP function htmlMacroGridWithData is a shortcut 
*   that creates a grid for editing existing data.
*   The grid does not contain any buttons for [New], [Save], etc.,
*   it contains inputs that save immediately when the user changes
*   a value.
*
*   In standard usage, you execute a query using SQL_AllRows, then
*   call this macro to set up a grid allowing the user to edit
*   the data.  
*
*   This macro assumes the grid is the only element on the page.
*   It will be high enough to leave space for an H1 above it and
*   and blank space below it.  It will be shifted about 1/3 across
*   the screen horizontally.
*
* INPUTS
*	array $dd - The data dictionary of the table being edited
*   mixed $cols - comma-separated list of columns, or array of columns
*   array $rows - an array of row arrays.  The skey column must be included.
*
* RETURNS
*	androHtml GRID object with inputs that save each value as the
*   user changes them.  Returns a free-standing HTML object, use
*   addChild() to add it into an existing HTML object.
******
*/
/*
function &htmlMacroGridWithData($dd,$cols,$rows) {
    # Make sure the list of columns is an array
    if(!is_array($cols)) {
        $cols = explode(',',$cols);   
    }
    
    # This is convenient to have
    $table_id = $dd['table_id'];

    # Work out grid height as inside height less two h1's, one
    # at top and a blank space at bottom
    $hinside = x6cssDefine('insideheight');
    $hh1     = x6cssHeight('h1');
    $gheight = $hinside - ($hh1 * 2);
    
    # create a tabdiv and add the columns
    $tabDiv = new androHTMLGrid($gheight,$table_id);
    foreach($cols as $col) {
        $tabDiv->addColumn($dd['flat'][$col]);
    }
    $gwidth = $tabDiv->lastColumn();
    
    # Get the width of the grid and wiggle it over               
    $remain = x6cssDefine('insidewidth') - $gwidth;
    $remain = intval($remain/3);
    $tabDiv->hp['style'] .= "; margin-left: {$remain}px";
    
    foreach($rows as $row) {
        $tabDiv->addRow($row['skey']);
        foreach($cols as $col) {
            $colinfo = &$dd['flat'][$col];
            if($colinfo['uiro'] == 'Y') {
                $tabDiv->addCell($row[$col]);   
            }
            else {
                $inp = input($dd['flat'][$col]);
                if(isset($inp->style['text-align'])) {
                    $inp->hp['size']-=2;   
                }
                $inp->hp['value'] = arr($row,$col,'&nbsp;');
                $tabDiv->addCell($inp);
            }
        }
    }
    return $tabDiv;
}
            */
 

/****c* HTML Generation/androHtml
*
* NAME
*    androHtml
*
* FUNCTION
*	The class androHtml is used to build object oriented html hierarchies  in order to prevent
*	the mixing of html and php code in andromeda.  Each androHtml object can have attributes,
*	custom parameters, children elements, styles, and inner html.  Essentially everything a typical
*	html element can have.
*
*	androHtml elements are build exclusively through the factory function html, which handles
*	html children and parents.
*
* SEE ALSO
*	html
*
******
*/
class androHtml {

    /****v* androHtml/children
    *
    * NAME
    *    children
    *
    * FUNCTION
    *	The variable children is an array that holds all of the androHtml elements held within this current
    *	androHtml element.
    *
    ******
    */
    var $children = array();

    /****v* androHtml/hp
    *
    * NAME
    *    hp
    *
    * FUNCTION
    *	The variable hp is an array that holds all of this androHtml element's html properties.  These are
    *	properties that would show up in the html code on the page.  For instace, the property "href" is an
    *	html property for the "a" element.
    *
    ******
    */
    var $hp   = array();

    /****v* androHtml/code
    *
    * NAME
    *    code
    *
    * FUNCTION
    *	The variable code is an array that holds all of this androHtml element's javascript code references.
    *
    ******
    */
    var $code = array();
    var $functions=array();
    
    /****v* androHtml/ap
    *
    * NAME
    *    ap
    *
    * FUNCTION
    *	The variable ap is an array that holds all of this androHtml element's additional properties.  These
    *	are self created properties for the object that do not show up in the html source code.
    *
    ******
    */
    var $ap   = array();

    /****v* androHtml/styles
    *
    * NAME
    *    styles
    *
    * FUNCTION
    *	The variable styles is an associative array that holds all of this androHtml element's css style
    *	properties.  If you want to set a style property, you name the property as a key in the array, and
    *	you set the value for the css property as the value for the key.
    *
    * EXAMPLE
    *	$htmlObject->style = 'float: right';
    *
    ******
    */
    var $style= array();

    /****v* androHtml/innerHtml
    *
    * NAME
    *    innerHtml
    *
    * FUNCTION
    *	The variable innerHtml is a string that contains all of the code and text held within this androHtml
    *	element.
    *
    ******
    */
    var $innerHtml  = '';

    /****v* androHtml/htype
    *
    * NAME
    *    htype
    *
    * FUNCTION
    *	The variable htype is a string that contains the name of the type of html tag that this androHtml
    *	object is representing.  For example, if this androHtml element is representing a div element, htype
    *	would be equal to 'div';
    *
    ******
    */
    var $htype      = '';

    /****v* androHtml/classes
    *
    * NAME
    *    classes
    *
    * FUNCTION
    *	The variable classes is an array that holds all of this androHtml element's css classes.
    *
    ******
    */
    var $classes    = array();

    /***** androHtml/autoFormat
    *
    * NAME
    *    autoFormat
    *
    * FUNCTION
    *	The variable autoFormat is a boolean value which states whether this androHtml element is
    *	autoformatted or not.
    *
    ******
    */
    var $autoFormat = false;

    /****v* androHtml/isParent
    *
    * NAME
    *    isParent
    *
    * FUNCTION
    *	The variable isParent is a boolean value which states whether this androHtml element is a parent
    *	element or not.
    *
    ******
    */
    var $isParent   = false;

    /****m* androHtml/setHtml
    *
    * NAME
    *    setHtml
    *
    * FUNCTION
    *	The method setHtml sets this androHtml element's innerHtml to the provided string.
    *
    * INPUTS
    *	string $value - New innerHtml
    *
    ******
    */
    function setHtml($value) {
        $this->innerHtml = $value;
    }

    /****m* androHtml/getHtml
    *
    * NAME
    *    getHtml
    *
    * FUNCTION
    *	The method getHtml retrieves the innerHTML of an HTML
    *   object.  The HTML of nested children is not returned,
    *   only the literal HTML set by the setHTML() method.
    *
    * INPUTS
    *	none
    *
    * RETURNS
    *   string
    *
    ******
    */
    function getHtml() {
        return $this->innerHtml;
    }
    
    /****m* androHtml/clear
    *
    * NAME
    *    clear
    *
    * FUNCTION
    *	The method clear removes all child elements and innerHtml from this androHtml element.
    *
    * SOURCE
    */
    function clear() {
        $this->innerHtml = '';
        $this->children = array();
    }
    /******/

    /****m* androHtml/clearHP
    *
    * NAME
    *    clearHP
    *
    * FUNCTION
    *	The method clearHP removes all Html properties from this androHtml element.
    *
    * SOURCE
    */
    function clearHP() {
        $this->hp = array();
    }
    /******/

    /****m* androHtml/clearAP
    *
    * NAME
    *    clearAP
    *
    * FUNCTION
    *	The method clearAP removes all additional properties from this androHtml element.
    *
    * SOURCE
    */
    function clearAP() {
        $this->ap = array();
    }
    /******/

    /****m* androHtml/addClass
    *
    * NAME
    *    addClass
    *
    * FUNCTION
    *	The method addClass adds the provided css class to this androHtml object.
    *
    * INPUTS
    *	string $value - Css class to add
    *
    * SOURCE
    */
    function addClass($value) {
        $this->classes[] = $value;
    }
    /******/

    /****m* androHtml/addStyle
    *
    * NAME
    *    addStyle
    *
    * FUNCTION
    *	The method addStyle adds the provided CSS rule to
    *   any that are already defined for the node.
    *
    * INPUTS
    *	string $value - Css class to add
    *
    * SOURCE
    */
    function addStyle($value) {
        if(substr($value,-1)!=';') $value.=';';
        if(!isset($this->hp['style'])) {
            $this->hp['style'] = $value;
        }
        else {
            $this->hp['style'].= $value;
        }
    }
    /******/

    /****m* androHtml/removeClass
    *
    * NAME
    *    removeClass
    *
    * FUNCTION
    *	The method removeClass removes the provided css class from this androHtml object.
    *
    * INPUTS
    *	string $value - Css class to remove from this object
    *
    * SOURCE
    */
    function removeClass($value) {
        $index = array_search($value,$this->classes);
        if($index) unset($this->classes[$index]);
    }
    /******/

    /****m* androHtml/addChild
    *
    * NAME
    *    addChild
    *
    * FUNCTION
    *	The method addChild adds a child html element to this androHtml object.
    *
    * INPUTS
    *	object $object - androHtml element to add to this object as a child element
    *
    * SOURCE
    */
    function addChild($object) {
        $this->children[] = $object;
    }
    /******/

    /****m* androHtml/html
    *
    * NAME
    *    html
    *
    * FUNCTION
    *	The method html acts a lot like the library function html, however it adds the created html element
    *	directly to this androHtml element, specifying that this androHtml element is the parent element.
    *
    * INPUTS
    *	string $tag - Name of html tag.
    *	mixed $innerHml - Innerhtml for the created html element.
    *	string $class - Css class for the html element.
    * SOURCE
    */
    function html($tag,$innerHTML='',$class='') {
        $x = html($tag,$this,$innerHTML);
        if($class<>'') $x->addClass($class);
        return $x;
    }
    /******/

    /****m* androHtml/h
    *
    * NAME
    *    h
    *
    * FUNCTION
    *	The method h is a shortcut for the method html.
    *
    * SEE ALSO
    *	html
    *
    * SOURCE
    */
    function h($tag,$innerHTML='',$class='') {
        return $this->html($tag,$innerHTML,$class);
    }
    /******/

    
    /****m* androHtml/form
    *
    * NAME
    *    androHTML/form
    *
    * FUNCTION
    *	The PHP method form creates an HTML FORM node, adds it as a 
    *   child to the current node, and returns a reference to it.
    *
    *   All inputs are optional.
    *
    * INPUTS
    *	string $name - the name of the form.  Default: "Form1"
    *   string $method - the method (GET or POST).  Default: POST
    *   string $action - the URI to route to.  Default "index.php"
    *	string $x6page - the value of x6page to go to, defaults to none.
    *
    * SOURCE
    */
    function &form($name='Form1',$method='POST',$action="index.php",$x6page=''){
        $form = $this->h('form');
        $form->hp['id']     = $name;
        $form->hp['name']   = $name;
        $form->hp['method'] = $method;
        $form->hp['action'] = $action;
        $form->hp['enctype']="multipart/form-data";
        if($x6page <> '') {
            $symbol = strpos($action,'?')===false ? '?' : '&';
            $form->hp['action'].=$symbol."x6page=".$x6page;
        }
        return $form;
    }
    /******/
    
    /****m* androHtml/hidden
    *
    * NAME
    *    hidden
    *
    * FUNCTION
    *	The method hidden adds a hidden value to this androHtml object.  A hidden variable is stored in
    *	an input html element that has the html property 'type' set to 'hidden'.  This enables the passing
    *	of variables back and forth from the server to the client browser after refreshes.
    *
    * INPUTS
    *	string $name - Name of the hidden variable
    *	string $value - Value for the hidden variable
    *
    * SOURCE
    */
    function hidden($name,$value = '') {
        $h = $this->h('input');
        $h->hp['type'] = 'hidden';
        $h->hp['id']   = $name;
        $h->hp['name'] = $name;
        $h->hp['value']= $value;
        return $h;
    }
    /******/

    /* DEPRECATED */    
    function detailRow($dd,$column,$options=array()) {
        # something we need for b/w compatibility that is
        # easier to declare and ignore than it is to
        # try to get rid of. (Also, getting rid of it will
        # break some of my older apps).
        $tabLoop=array();
        
        $tr = $this->h('tr');
        $td = $tr->h('td',$dd['flat'][$column]['description']);
        $td->addClass('x4Caption');
        
        $input=input($dd['flat'][$column],$tabLoop,$options);
        $td = $tr->h('td');
        $td->setHtml($input->bufferedRender());
        $td->addClass('x4Input');
    }
    /******/
    

    /****m* androHtml/tr
    *
    * NAME
    *    tr
    *
    * FUNCTION
    *	The method tr adds a table row html element to this androHtml element.  Slightly shorter than
    *	using the html method.
    *
    * INPUTS
    *		mixed $innerHTML - inner html
    *		string $class - Css class
    *
    * SOURCE
    */
    function tr($innerHTML='',$class='') {
        return $this->html('tr',$innerHTML,$class);
    }
    /******/

    /****m* androHtml/td
    *
    * NAME
    *    td
    *
    * FUNCTION
    *	The method td adds a table colunn element to this androHtml object.
    *
    * INPUTS
    *	mixed $innerHTML - inner html for the td element
    *	string $class - Css class for this td element
    *
    * SOURCE
    */
    function td($innerHTML='',$class='') {
        return $this->html('td',$innerHTML,$class);
    }
    /******/

    function a($innerHTML,$href,$class='') {
        $a = $this->h('a',$innerHTML,$class);
        $a->hp['href'] = $href;
        return $a;
    }
    
    /****m* androHtml/link
    *
    * NAME
    *    androHtml.link
    *
    * FUNCTION
    *	The PHP method link adds a hyperlink to his androHtml object.
    *
    * INPUTS
    *	string $href - Hypertext reference
    *	string $innerHTML - inner html for the 'a' tag
    *
    * SOURCE
    */
    function link($href,$innerHTML) {
        $a = $this->h('a',$innerHTML);
        $a->hp['href'] = $href;
        return $a;
    }
    /******/

    /****m* androHtml/br
    *
    * NAME
    *    br
    *
    * FUNCTION
    *	The method br adds the provided amount of break elements to this androHtml object as children
    *	elements.
    *
    * INPUTS
    *	number $count - Number of break elements to add
    *
    * SOURCE
    */
    function br($count=1) {
        for($x=1;$x<=$count;$x++) {
            $this->children[] = '<br/>';
        }
    }
    /******/

    /****m* androHtml/hr
    *
    * NAME
    *    hr
    *
    * FUNCTION
    *	The method hr adds the provided number of horizontal rule elements to this androHtml object
    *	as children elements.
    *
    * INPUTS
    *	number $count - Number of horizontal rule elements to add
    *
    * SOURCE
    */
    function hr($count=1) {
        for($x=1;$x<=$count;$x++) {
            $this->children[] = '<hr/>';
        }
    }
    /******/

    /****m* androHtml/nbsp
    *
    * NAME
    *    nbsp
    *
    * FUNCTION
    *	The method nbsp adds the provided number of non-breaking spaces to this androHtml object as
    *	children elements.
    *
    * INPUTS
    *	number $count - Number of non-breaking spaces to add.
    *
    * SOURCE
    */
    function nbsp($count=1) {
        for($x=1;$x<=$count;$x++) {
            $this->children[] = '&nbsp;';
        }
    }
    /******/
    
    /****m* androHtml/hiddenInputs
    *
    * NAME
    *    androHtml.hiddenInputs
    *
    * FUNCTION
    *    The PHP method hiddenInputs adds an invisible div to the
    *    current node and fills it with inputs for provided table.
    *    These can cloned (using jQuery) in browser-side code to
    *    place inputs on-the-fly anywhere on the screen.
    *
    *    The function returns a reference to the div.  The div 
    *    contains an associative array indexed on column name
    *    that contains references to the inputs.
    *
    * INPUTS
    *    mixed - either a table name or a data dictionary array reference.
    *
    * RETURNS
    *    reference - reference to the invisible div.
    *
    ******/
    function &hiddenInputs($x) {
        # Get a data dictionary
        if(is_array($x)) {
            $dd = $x;
        }
        else {
            $dd = ddTable($x);
        }
        
        # Make the master Div
        $div = $this->h('div');
        $div->hp['style'] = 'display: none';
        
        # Loop through the dictionary, not skipping any column
        foreach($dd['flat'] as $colname=>$colinfo) {
            $wrapper = $div->h('div');
            $wrapper->hp['id'] 
                ='wrapper_'.$dd['table_id'].'_'.$colinfo['column_id'];
            $input = input($colinfo);
            $div->inputs[$colname] = $input;
            $wrapper->addChild($input);
        }
        return $div;        
    }
    
    function addXRefs($table_id,$top,$width,$height) {
        $child = new androHTMLxrefs($table_id,$top,$width,$height);
        $this->addChild($child);
    }
    
    
    /****m* androHtml/addButtonBar
    *
    * NAME
    *    androHtml.addButtonBar
    *
    * FUNCTION
    *    The PHP method addButtonBarStandard adds a div to the
    *    current node that contains the standard buttons for
    *    [New], [Duplicate], [Save], [Abandon], and [Remove].
    *
    *    The div has class x6buttonBar (note capitalization).
    *
    *    The function returns a reference to the div.  The div
    *    contains an array called buttons that is indexed on
    *    the button action.  The actions are:
    *    * new 
    *    * duplicate
    *    * save
    *    * remove
    *    * abandon
    *
    * INPUTS
    *    * string - the name of the table the buttons operate on
    *    * mixed - a list of buttons to include, comma separated.
    *      default is to include all five buttons.
    *  
    *
    * RETURNS
    *    reference - reference to the div.
    *
    ******/
    # overrides default addButtonbar
    function bbHeight() { return x6cssHeight('div.x6buttonBar a.button');} 
    function addButtonBar($list='new,save,cancel,delete') {
        $bbHeight = $this->bbHeight();
        $table_id = $this->hp['x6table'];
        $abuts = explode(',',$list);
        
        # Tell us which buttons it has, default to none
        $this->hp['butnew']    = 'N';
        $this->hp['butins']    = 'N';
        $this->hp['butsave']   = 'N';
        $this->hp['butcancel'] = 'N';
        $this->hp['butdelete'] = 'N';
        
        # First trick is to create the div that will be
        # slipped in above the titles.
        $this->buttonBar = html('div');
        $this->buttonBar->hp['style'] = "height: {$bbHeight}px;";
        if(arr($this->hp,'x6plugin','')=='grid') {
            array_unshift($this->dhead0->children,$this->buttonBar);
        }
        else {
            $this->addChild($this->buttonBar);
        }
        $bb = $this->buttonBar;
        $bb->addClass('x6buttonBar');
        
        $pad0 = x6cssDefine('pad0');
        
        # KFD 1/22/09, create two divs, drop buttons into them
        #              this makes it possible to drop in 
        #              custom buttons in a new div that is float:left
        $sl = $bb->h('div');
        $sl->hp['style'] = 'float: left';
        $sr = $bb->h('div');
        $sr->hp['style'] = 'float: right';

        if(in_array('new',$abuts)) {
            $this->hp['butnew'] = 'Y';
            $a=$sl->h('a-void','New');
            $a->addClass('button_disabled button-first');
            $a->hp['style'] = 'margin-left: 0px';
            $a->hp['x6table']  = $table_id;
            $a->hp['x6plugin'] = 'buttonNew';
            $a->hp['id']       = 'buttonNew_'.$table_id;
            $a->hp['style']    = 'float: left';
            $bb->buttons['new'] = $a;
            $a->initPlugin();

            if(in_array('ins',$abuts)) {
                $this->hp['butins'] = 'Y';
                $a=$sl->h('a-void','Insert');
                $a->addClass('button_disabled');
                $a->hp['style'] = 'margin-left: 0px';
                $a->hp['x6table']  = $table_id;
                $a->hp['x6plugin'] = 'buttonInsert';
                $a->hp['id']       = 'buttonInsert_'.$table_id;
                $a->hp['style']    = 'float: left';
                $bb->buttons['ins'] = $a;
                $a->initPlugin();
            }
        }
        if(in_array('save',$abuts)) {
            $this->hp['butsave'] = 'Y';
            $a=$sl->h('a-void','Save');
            $a->addClass('button_disabled');
            $a->hp['x6table']  = $table_id;
            $a->hp['x6plugin'] = 'buttonSave';
            $a->hp['id']       = 'buttonSave_'.$table_id;
            $a->hp['style']    = 'float: left';
            $bb->buttons['save'] = $a;
            $a->initPlugin();
        }
        if(in_array('cancel',$abuts)) {
            $this->hp['butcancel'] = 'Y';
            $a=$sr->h('a-void','Delete');
            $a->addClass('button_disabled');
            $a->hp['x6table']  = $table_id;
            $a->hp['x6plugin'] = 'buttonDelete';
            $a->hp['id']       = 'buttonDelete_'.$table_id;
            $a->hp['style']    = "float: right; margin-right: {$pad0}px";
            $bb->buttons['remove'] = $a;
            $a->initPlugin();
        }
        if(in_array('delete',$abuts)) {
            $this->hp['butdelete'] = 'Y';
            $a=$sr->h('a-void','Cancel');
            $a->addClass('button_disabled');
            $a->hp['x6table']  = $table_id;
            $a->hp['x6plugin'] = 'buttonCancel';
            $a->hp['id']       = 'buttonCancel_'.$table_id;
            $a->hp['style']    = 'float: right';
            $bb->buttons['abandon'] = $a;
            $a->initPlugin();
        }
            
        return $bb;
    }
    
    # overrides default addButtonbar
    function addCustomButtons($obj) {
        if($obj===false) return;
        
        $this->buttonBar->addChild($obj);
    }
    
    function &addCustomButton($table,$action,$key,$caption,$permins,$permupd){
        $b = $this->h('a-void',$caption);
        $b->addClass('button');
        $b->hp['buttonKey'] = $key;
        $b->hp['x6table'] = $table;
        $b->hp['x6plugin']= 'buttonCustom';
        $b->hp['action'] =$action;
        $b->hp['id']     =$action;
        $b->hp['permins']=$permins;
        $b->hp['permupd']=$permupd;
        $b->hp['style'  ]='float: left;';
        $b->initPlugin();
        jqDocReady("x6events.fireEvent('disable_{$action}_$table')");
        return $b;
    }
    
    
    
    function &addButtonBarOld($table_id,$buts=null) {
        if(is_null($buts)) {
            $buts = 'new,duplicate,save,remove,abandon';
        }
        $abuts = explode(',',$buts);
        
        $bb = $this->h('div');
        $bb->addClass('x6buttonBar');
        if(in_array('new',$abuts)) {
            $a=$bb->h('a-void','New');
            $a->addClass('button button-first');
            $a->hp['style'] = 'margin-left: 0px';
            $a->hp['x6table']  = $table_id;
            $a->hp['x6plugIn'] = 'buttonNew';
            $a->hp['style']    = 'float: left';
            $bb->buttons['new'] = $a;
        }
        #if(in_array('duplicate',$abuts)) {
        #    $a=$bb->h('a-void','Duplicate');
        #    $a->addClass('button');
        #    $a->hp['x6table']  = $table_id;
        #    $a->hp['x6plugIn'] = 'buttonDuplicate';
        #    $a->hp['style']    = 'float: left';
        #    $bb->buttons['duplicate'] = $a;
        #    #jqDocReady("x6events.fireEvent('disable_duplicate')");
        #}
        if(in_array('save',$abuts)) {
            $a=$bb->h('a-void','Save');
            $a->addClass('button');
            $a->hp['x6table']  = $table_id;
            $a->hp['x6plugIn'] = 'buttonSave';
            $a->hp['style']    = 'float: left';
            $bb->buttons['save'] = $a;
            jqDocReady("x6events.fireEvent('disable_save')");
        }
        if(in_array('remove',$abuts)) {
            $a=$bb->h('a-void','Remove');
            $a->addClass('button');
            $a->hp['x6table']  = $table_id;
            $a->hp['x6plugIn'] = 'buttonRemove';
            $a->hp['style']    = 'float: right';
            $bb->buttons['remove'] = $a;
            jqDocReady("x6events.fireEvent('disable_remove')");
        }
        if(in_array('abandon',$abuts)) {
            $a=$bb->h('a-void','Abandon');
            $a->addClass('button');
            $a->hp['x6table']  = $table_id;
            $a->hp['x6plugIn'] = 'buttonAbandon';
            $a->hp['style']    = 'float: right';
            $bb->buttons['abandon'] = $a;
            jqDocReady("x6events.fireEvent('disable_abandon')");
        }
            
        return $bb;
    }
    

    /****m* androHtml/autoFormat
    *
    * NAME
    *    autoFormat
    *
    * FUNCTION
    *	The method autoFormat sets whether this androHtml element is autoFormatted or not.  The default
    *	input for this function is true.
    *
    * INPUTS
    *	boolean $setting - True for autoFormatting, false for none.
    *
    * SOURCE
    */
    function autoFormat($setting=true) {
        $this->autoFormat = $setting;
    }
    /******/

    /****m* androHtml/tabIndex
    *
    * NAME
    *    tabIndex
    *
    * FUNCTION
    *	The PHP method tabIndex sets the HTML attribute "tabindex" on
    *   the object.  The first time it is called, this routine 
    *   sets the index at 1000.  Subsequence calls go to 1001,1002 etc.
    *
    *   The first time you call this method it also marks the 
    *   object as getting focus when the page loads.  To force focus
    *   to begin on some other object, call tabFocus instead of
    *   tabIndex for that object.
    *
    * INPUTS
    *   int (optional) starting value.  If this value is supplied,
    *   the object will get this value for its tabIndex, and 
    *   subsequent calls will increment from there.  Defaults
    *   to 1000.
    *
    * EXAMPLE
    *   Here is an example:
    *     <?php
    *     $div = html('div');
    *     $input = $div->h('input');
    *     $input->tabIndex();
    *     // more poperty settings...
    *     $input = $div->h('input'); // reuse var, make another input
    *     $input->tabIndex();
    *     ?>
    *  
    * SEE ALSO
    *   tabFocus
    *
    ******/
    function tabIndex($startHere=null) {
        if(!is_null($startHere)) {
            $tabIndex = $startHere;
        }
        else {
            $tabIndex = vgfGet('tabindex',0);
            if($tabIndex == 0) {
                $this->hp['x6firstFocus']='Y';
                $tabIndex = 1000;
            }
        }
        $this->hp['tabIndex'] = $tabIndex;
        vgfSet('tabindex',++$tabIndex);
        if(is_object(vgfGet('lastTab',0))) {
            $obj = vgfGet('lastTab');
            $obj->hp['xNextTab'] = $this->hp['tabIndex'];
            $this->hp['xPrevTab'] = $obj->hp['tabIndex'];
        }
        vgfSet('lastTab',$this);
    }

    /****m* androHtml/tabFocus
    *
    * NAME
    *    tabFocus
    *
    * FUNCTION
    *	The PHP method tabFocus does exactly the same thing
    *   as tabIndex, with one additional action.  When the page
    *   loads, this object will start out with focus.
    *
    *   Calling this method more than once while building a page
    *   causes focus to begin on the last object that made 
    *   the call.
    *
    *   The first time you call tabIndex, it acts like a call
    *   to tabFocus, so there is no reason to ever call tabFocus
    *   unless you want focus to begin somewhere other than the
    *   first tabbable object.
    *
    * INPUTS
    *   int (optional) starting value.  If this value is supplied,
    *   the object will get this value for its tabIndex, and 
    *   subsequent calls will increment from there.
    *
    * EXAMPLE
    *   Here is an example:
    *     <?php
    *     $div = html('div');
    *     $input = $div->h('input');
    *     $input->tabFocus();   // first input should get focus
    *     // more poperty settings...
    *     $input = $div->h('input'); // reuse var, make another input
    *     $input->tabIndex();
    *     ?>
    *  
    *
    ******/
    function tabFocus($startHere=null) {
        $this->tabIndex($startHere);
        $this->hp['x6firstFocus']='Y';
    }
    
    # KFD BLUNT WEAPON.  This really is meant for very simple
    #                    elements where you just make it scrollable
    function scrollable($height='') {
        $this->addStyle('overflow-y: scroll;');
        $this->addStyle("height: $height");
    }
    
        
    /****m* androHtml/TbodyRows
    *
    * NAME
    *    TbodyRows
    *
    * FUNCTION
    *	The method TbodyRows adds a set of elements to something with striping option.
    *
    * INPUTS
    *	array $rows - rows of elements to add
    *	array $options - striping options
    *
    ******/
    function &TbodyRows($rows,$options=array()) {
        $rowIdPrefix='row_';
        $stripe = $stripe1 = $stripe2 = $stripe3 = 0;
        if(a($options,'stripe',0)>0) {
            $stripe1 = $options['stripe'];
            $stripe2 = $stripe1 - 1;
            $stripe3 = $stripe1 * 2;
        }
        $stripe = a($options,'stripeCss')=='' ? 0 : 1;
        $tbody = html('tbody',$this);
        foreach($rows as $index=>$row) {
            $tr = html('tr',$tbody);
            $tr->hp['id'] = $rowIdPrefix.($index+1);
            $tr->hp['valign'] = 'top';
            
            if($stripe1 > 0) {
                $i = $index % $stripe3;
                if($i > $stripe2) {
                    $tr->addClass('lightgray');
                }
                else {
                    if($i < $stripe2) {
                        $tr->addClass('lightgraybottom');
                    }
                }
            }

            foreach($row as $colname=>$colvalue) {
                html('td',$tr,$colvalue);
            }
        }
        return $tbody;
    }

    /***** androHtml/addInput
    *
    * NAME
    *    addInput
    *
    * FUNCTION
    *	The PHP method androHtml::addInput adds an HTML input as
    *   a child of the current node.  
    *
    * INPUTS
    *   array - dictionary info on the field, e.g. $dd['flat']['price']
    *
    * RETURNS
    *   node - a reference to the input.
    *
    * SEE ALSO
    *   androHtmlTable
    *  
    ******/
    function &addInput($colinfo) {
        $input =  input($colinfo);
        $this->addChild($input);
        return $input;
    }
    
    /****m* androHtml/addTable
    *
    * NAME
    *    addTable
    *
    * FUNCTION
    *	The PHP method androHtml::addTable adds an instance of 
    *   class androHTMLTable as a child node.  
    *   The resulting table has special
    *   routines for easily adding thead, tbody, tr, th and td
    *   cells.
    *
    * SEE ALSO
    *   androHtmlTable
    *  
    ******/
    function &addTable() {
        $newTable = new androHTMLTable();
        $this->addChild($newTable);
        return $newTable;
    }
    
    function &addTableController($table_id) {
        $retval = new androHTMLTableController($table_id);
        $this->addChild($retval);
        return $retval;
    }

    /****m* androHtml/addGrid
    *       
    * NAME
    *    addTabGrid
    *
    * FUNCTION
    *	The PHP method androHtml::addGrid adds an instance of 
    *   class androHTMLGrid as a child node.  
    *   A "Grid" is a simulated HTML table that uses divs 
    *   instead of TD elements.  The two main reasons for doing
    *   this are that you cannot put an onclick() routine onto
    *   a TR in Internet Explorer (as of IE7 oct 2008) and
    *   the scrollable body is easier to get going on 
    *   a DIV.
    *
    * INPUTS
    *   HEIGHT: The total height of the table including borders,
    *   header, and footer.
    *   
    *
    * SEE ALSO
    *   androHtmlTable
    *  
    ******/
    function &addGrid(
        $height,$table_id,$lookups=false,$sortable=false,$bb=false,$edit=false
    ) {
        $newTable = new androHTMLGrid(
            $height,$table_id,$lookups,$sortable,$bb,$edit
        );
        $this->addChild($newTable);
        
        return $newTable;
    }

    /****m* androHtml/addDetail
    *
    * NAME
    *    addTabDiv
    *
    * FUNCTION
    *	The PHP method androHtml::addDetail adds an instance of 
    *   class androHTMLDetail as a child node.  This is an HTML
    *   TABLE that will contain rows of inputs - caption on the
    *   left and input on the right.
    *
    * INPUTS
    *   string table_id - the name of the table being edited
    *   
    *
    * SEE ALSO
    *   androHtmlDetail
    *  
    ******/
    function &addDetail($table_id,$complete=false,$height=300,$p='') {
        $newDetail = new androHTMLDetail($table_id,$complete,$height,$p);
        $this->addChild($newDetail);
        return $newDetail;
    }

    /****m* androHtml/addTabs
    *
    * NAME
    *   addTabs
    *
    * FUNCTION
    *	The PHP method androHtml::addTabs adds an instance of
    *   class androHTMLTabs as a child node.  
    *
    *   The androHTMLTabs class depends on jQuery's UI/Tabs
    *   feature.
    *
    * SEE ALSO
    *   androHtmlTabs
    *  
    ******/
    function &addTabs($id,$height=500,$options=array()) {
        $newTabs = new androHTMLTabs($id,$height,$options);
        $this->addChild($newTabs);
        return $newTabs;
    }
    
    /****m* androHtml/addCheckList
    *
    * NAME
    *    addCheckList
    *
    * FUNCTION
    *	The PHP method androHtml::addCheckList adds an instance of 
    *   class androHTMLCheckList as a child node.  
    *
    * SEE ALSO
    *   androHtmlCheckList
    *  
    ******/
    function &addCheckList() {
        $newTable = new androHTMLCheckList();
        $this->addChild($newTable);
        return $newTable;
    }
    
    /* DEPRECATED */
    function makeThead($thvalues,$class='dark') {
        # Make it an array if it is not already
        if(!is_array($thvalues)) {
            $thvalues = explode(',',$thvalues);
        }
        $thead = html('thead',$this);
        $tr    = html('tr',$thead);
        foreach($thvalues as $th) {
            $tr->h('th',$th,$class);
        }
        return $thead;
    }

    /* DEPRECATED */
    function addItems($tag,$values) {
        if(!is_array($values)) {
            $values = explode(',',$values);
        }
        foreach($values as $value) {
            html($tag,$this,$value);
        }
    }


    /****m* androHtml/addOptions
    *
    * NAME
    *    addOptions
    *
    * FUNCTION
    *	The PHP Method addOptions takes an array of rows and
    *   creates on HTML OPTION object for each row.  These
    *   are added to the parent object, which is assumed to be
    *   an HTML SELECT object.
    *
    * INPUTS
    *	array - an array of rows
    *	string - name of column to use as value
    *   string - name of column to use as display
    *
    * SOURCE
    */
    function addOptions($rows,$value,$desc) {
        foreach($rows as $row) {
            $opt = $this->h('option',$row[$desc]);
            $opt->hp['value'] = $row[$value];
        }
    }
    /******/


    /****m* androHtml/setAsParent
    *
    * NAME
    *    setAsParent
    *
    * FUNCTION
    *	The method setAsParent sets a flag for this androHtml element to work as parent.
    *
    * SOURCE
    */
    function setAsParent() {
        $this->isParent = true;
    }
    /******/

    # Internal use only
    function initPlugin() {
        $plugin = $this->hp['x6plugin'];
        $table  = $this->hp['x6table'];
        jqDocReady("var plugin = x6.byId('{$this->hp['id']}');");
        jqDocReady("x6plugins.$plugin(plugin,plugin.id,'$table')");
    }

    /****m* androHtml/firstChild
    *
    * NAME
    *    firstChild
    *
    * FUNCTION
    *	The method firstChild returns a reference to the first child html element in this androHtml object.
    *
    * RETURN VALUE
    *	reference - reference to first child element
    *
    * SOURCE
    */
    function firstChild() {
        if(count($this->children)==0) {
            return null;
        }
        else {
            $retval = &$this->children[0];
            return $retval;
        }
    }
    /******/

    /****m* androHtml/lastChild
    *
    * NAME
    *    lastChild
    *
    * FUNCTION
    *	The method lastChild returns a reference to the last child element in this androHtml object.
    *
    * RETURN VALUE
    *	reference - reference to last child
    *
    * SOURCE
    */
    function lastChild() {
        if(count($this->children)==0) {
            return null;
        }
        else {
            $retval = &$this->children[count($this->children)-1];
            return $retval;
        }
    }
    /******/
    
   
    /****m* androHtml/print_r
    *
    * NAME
    *    print_r
    *
    * FUNCTION
    *	The method dumps a variable into the innerHTML of an
    *   and Andromeda HTML Object.
    *
    * SOURCE
    */
    function print_r($value) {
        ob_start();
        print_r($value);
        $pre = $this->h('pre',ob_get_clean());
        $pre->hp['class'] = 'border: 1px solid gray; background-color:white;
            color: black;';
    }
    /******/

    /****m* androHtml/bufferedRender
    *
    * NAME
    *    bufferedRender
    *
    * FUNCTION
    *	The method bufferedRender rendered this androHtml object in a buffer, instead of directly outputing
    *	it to the browser.
    *
    * SOURCE
    */
    function bufferedRender($parentId='',$singleQuotes=false) {
        ob_start();
        $this->render($parentId,$singleQuotes);
        return ob_get_clean();
    }
    /******/
    
    
    

    /****m* androHtml/render
    *
    * NAME
    *    render
    *
    * FUNCTION
    *	The method render renders this androHtml object.  It builds 
    *   all of the html code based on the objects attributes, 
    *   children elements, parent elements, etc.  Render directly
    *   outputs all html out to the browser.  User bufferedRender 
    *   to get the html as a string instead of outputting to the browser.
    *
    * INPUTS
    *	string $parentId - parent id for this androHtml object
    ******/
    function render($parentId='',$singleQuotes=false,$x6wrapperPane='') {
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
        
        # KFD 12/30/08, IE Compatibility.  All inputs, selects and
        #               so forth must have an ID.  This is actually
        #               due to jQuery returning strange items with
        #               the :input selector, and we can only distinguish
        #               real from bogus by looking for IDs
        if(in_array($this->htype,array('input','select','checkbox'))) {
            if(arr($this->hp,'id','')=='') {
                $id = rand(1000,9999);
                while(isset($GLOBALS['AG']['id'][$id])) {
                    $id = rand(1000,9999);
                }
                $this->hp['id'] = 'id_'.$id;
                $GLOBALS['AG']['id'][$id] = 1;
            }
        }
        
        # Set the x6 parent tab if exists
        if(arr($this->hp,'x6plugin') == 'x6tabs') {
            $this->hp['x6wrapperPane'] = $x6wrapperPane;
        }

        # Before we render, we are going to take the code
        # snippets and generate top-level functions for them
        $twoparms = array('click','keypress','keyup','keydown');
        $snippet_id = a($this->hp,'id');
        if($snippet_id == '') {
            $snippet_id = 'snip_'.rand(1,100000);
        }
        foreach($this->code as $event=>$snippet) {
            $fname = $snippet_id.'_'.$event;
            jqDocReady("window.$fname = $snippet");
            if(in_array($event,$twoparms))
                $this->hp['on'.$event] = "$fname(this,event)";
            else
                $this->hp['on'.$event] = "$fname(this)";
        }
        foreach($this->functions as $name=>$snippet) {
            jqDocReady("x6.byId('{$this->hp['id']}').$name = ".$snippet);
        }
        
        # KFD 10/7/08 if data has been attached, send it as json
        if(isset($this->data)) {
            $js = "x6.byId('".$this->hp['id']."').zData = "
                .json_encode($this->data);
            jqDocReady($js);
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
        $q = $singleQuotes ? "'" : '"';
        foreach($this->hp as $parmname=>$parmvalue) {
            if($parmname=='href') {
                $parmvalue=preg_replace('/&([a-zA-z])/','&amp;$1',$parmvalue);
            }
            $parms.="\n  $parmname=$q$parmvalue$q";
        }
        foreach($this->ap as $parmname=>$parmvalue) {
            $parms.="\n  $parmname=$q$parmvalue$q";
        }
        echo "<".$this->htype.' '.$parms.'>'.$this->innerHtml;
        foreach($this->children as $child) {
            if(is_string($child)) {
                echo $child;
            }
            else {
                if(arr($this->hp,'x6plugin')=='x6tabsPane') {
                    $x6wrapperPane = $this->hp['id'];
                }
                $child->render($parentId,$singleQuotes,$x6wrapperPane);
            }
        }
        echo "</$this->htype \n>";
        if($this->autoFormat) {
            echo "\n<!-- ELEMENT ID ".$this->hp['id']." (END) -->";
            //echo "$indent\n<!-- ELEMENT ID ".$this->hp['id']." (END) -->";
        }
    }
}


class androHTMLTableController extends androHTML {
    function androHTMLTableController($table_id) {
        $this->htype = 'div';
        $this->hp['x6plugin'] = 'tableController';
        $this->hp['x6table']  = $table_id;
        $this->hp['id']       = 'tc_'.$table_id;
        
        $this->ap['xPermSel'] = ddUserPerm($table_id,'sel');
        $this->ap['xPermIns'] = $this->permResolve('ins');
        $this->ap['xPermUpd'] = $this->permResolve('upd');
        $this->ap['xPermDel'] = $this->permResolve('del');
        
        $this->initPlugin();
    }
    
    function permResolve($perm) {
        $tryfirst = ddUserPerm($this->hp['x6table'],$perm);
        $dd = ddTable($this->hp['x6table']);
        $trysecond= arr($dd,'ui'.$perm,'Y');
        
        return $trysecond=='N' ? 'N' : $tryfirst;
    }
}



/****c* HTML Generation/androHtmlTabs
*
* NAME
*    androHtmlTabs
*
* FUNCTION
*   The class androHtmlTabBar is used to create on-screen Tab Bars
*   without having to manually create all of the various HTML elements.
*
*   The object is a subclass of androHtml, and supports all of its
*   methods such as addChild, addClass, etc.
*
*   
*
* EXAMPLE
*   A typical usage example might be something like this:
*
*      <?php
*      # Create a top-level div
*      $div = html('div');
*      $div->h('h1','Here is the title');
*
*      # now put in a tab bar with 3 tabs
*      $tabBar = new androHtmlTabBar('id');
*      $div->addChild($tabBar);
*      $tabBar->addTab('Users');  // this is the caption *and* the id
*      $tabBar->addTab('Groups');
*      $tabBar->addTab('Tables');
*   
*      # Now you can access the tabs like this:
*      $tabBar->tabs['Users']->h('h2','Welcome to the users tab.');
*      # ...and so on
*      ?>
*
* SEE ALSO
*	addChild
*
******
*/
class androHTMLTabs extends androHTML {
    /****v* androHtml/androHTMLTabs
    *
    * NAME
    *    tabs
    *
    * FUNCTION
    *   The class property tabs is an associative array that
    *   can be used to add HTML to the various tabs in the
    *   tab bar.
    *
    * EXAMPLE
    *   Normal usage looks like this:
    *      <?php
    *      $tabBar = new androHtmlTabBar('id');
    *      $tabBar->addTab('Users');  // this is the caption *and* the id
    *      $tabBar->tabs['Users']->h('h2','Hello! Welcome to users tab');
    *      ?>
    *
    ******/
    var $tabs = array();
    
    function androHTMLTabs($id='',$height=500,$options=array()) {
        # Example HTML from jquery tabs 
        /*
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" 
                    "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <script src="http://code.jquery.com/jquery-latest.js"></script>
  <link rel="stylesheet" href="http://dev.jquery.com/view/tags/ui/latest/themes/flora/flora.all.css" type="text/css" media="screen" title="Flora (Default)">
  <script type="text/javascript" src="http://dev.jquery.com/view/tags/ui/latest/ui/ui.core.js"></script>
  <script type="text/javascript" src="http://dev.jquery.com/view/tags/ui/latest/ui/ui.tabs.js"></script>


  <script>
  $(document).ready(function(){
    $("#example > ul").tabs();
  });
  </script>
  
</head>
<body>
  
        <div id="example" class="flora">
            <ul>

                <li><a href="#fragment-1"><span>One</span></a></li>
                <li><a href="#fragment-2"><span>Two</span></a></li>
                <li><a href="#fragment-3"><span>Three</span></a></li>
            </ul>
            <div id="fragment-1">
                <p>First tab is active by default:</p>
                <pre><code>$('#example > ul').tabs();</code></pre>
            </div>
            <div id="fragment-2">
                Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat.
                Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat.
            </div>
            <div id="fragment-3">
                Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat.
                Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat.
                Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat.
            </div>
        </div>
</body>
</html>
        */

        # Build the HTML that looks like the sample above
        # We actually bind all of the options and stuff to the
        #  UL, not the parent div, because that is what the
        #  jQuery stuff is operating on
        $this->htype  = 'div';
        $this->height =$height;
        $this->options=$options;

        $this->ul = $this->h('ul');
        $this->ul->hp['x6plugin'] = 'tabs';
        $this->ul->hp['id']       = $id;
        $this->tabs = array();

        # Set various options on the tab itself
        foreach($options as $option=>$value) {
            $this->ul->hp[$option]=$value;
        }
        if(!isset($this->ul->hp['x6table'])) {
            $this->ul->hp['x6table'] = '*';
        }
        
        # Register the script to turn on the tabs
        jqDocReady(" \$('#$id').tabs(); ");
        
        # Now initialize the plugin. 
        $this->ul->initPlugin();
    }
    
    /****m* androHtmlTabs/addTab
    *
    * NAME
    *    androHtmlTabs.addTab
    *
    * FUNCTION
    *   This PHP class method addTab is the basic method
    *   of the androHTMLTabs class,
    *   call this function once for each tab you wish to add
    *   to your tabbar.
    *
    * INPUTS
    *   - $caption string, becomes both caption and ID 
    *   
    * RETURNS
    *   - androHTML, reference to a div where you can put content
    *   for the new tab.
    *
    ******/
    function &addTab($caption,$disable=false) {
        # Make an index, and add it in.
        $index = $this->ul->hp['id'].'-'.(count($this->tabs)+1);
        
        # Get the offset, if they gave one, for setting
        # CTRL+Number key activation
        $offset = arr($this->ul->hp,'xOffset',0);
        $key    = $offset + count($this->tabs).': ';
        if($key>9) $key='';
        
        # Make a style setting just for this element, otherwise
        # jquery ui clobbers the height setting
        $this->h('style',"#$index { height: {$this->height}px;}");
        
        # First easy thing is to do the li entry
        $inner = "<a href='#$index'><span>$key$caption</span></a></li>";
        $this->ul->h('li',$inner);
        
        # Next really easy thing to do is make a div, give it
        # the id, and return it
        $div = $this->h('div');
        $div->hp['xParentId'] = $this->ul->hp['id'];
        $div->hp['x6plugin']  = 'x6tabsPane';
        $this->tabs[] = $div;
        $div->hp['id'] = "$index";
        if(isset($this->options['styles'])) {
            $div->hp['style']='';
            foreach($this->options['styles'] as $rule=>$value) {
                $div->hp['style'].="$rule: $value";
            }
        }
        return $div;
    }
}


/****c* HTML Generation/androHtmlTable
*
* NAME
*    androHtmlTable
*
* FUNCTION
*   The PHP class androHtmlTable models an HTML Table element, with
*   special properties and methods for easily manipulating rows
*   and cells.
*
*   The object is a subclass of androHtml, and supports all of its
*   methods such as addChild, addClass, etc.
*
*
******
*/
class androHTMLTable extends androHTML {
    /****v* androHtmlTable/cells
    *
    * NAME
    *    cells
    *
    * FUNCTION
    *   The PHP property androHTMLTable::cells is a two-dimensional
    *   numeric-indexed array of all cells added to the table. 
    *   
    *   This array is only updated for cells created with the
    *   methods tr and td.  If you use $table->h('td') or similar
    *   methods the resulting cell will not be in the array.
    *
    ******
    */
    var $lastBody = false;
    
    var $lastRow = false;
    
    var $lastCell = false;
    
    function androHTMLTable() {
        $this->htype = 'table';        
    }
    
    function &tbody() {
        $x = $this->h('tbody');
        $this->bodies[] = $x;
        $this->lastBody = $x;
        return $x;
    }
    function &thead() {
        $x = $this->h('thead');
        $this->bodies[] = $x;
        $this->lastBody = $x;
        return $x;
    }
    function &tr() {
        if(!$this->lastBody) {
            $this->tbody();
        }
        $this->lastRow = $this->lastBody->h('tr');
        return $this->lastRow;
    }
    
    function &td($mixed='',$tag='td') {
        # Turn the input into an array no matter what
        # we were given
        /*
        if(is_array($mixed)) {
            # already an array, pass it right over
            $adds=$mixed;
        }
        else {
            if(!is_numeric($mixed)) {
                # Not numeric, must be a single value
                $adds = array($mixed);
            }
            else {
                # a numeric was a request for a certain
                # number of cells.
                while($mixed>0) {
                    $adds[] = '';
                    $mixed--;
                }
            }
        }
        */
        
        # Now get us a row if we don't have one
        if(!$this->lastRow) {
            $this->tr();
        }
        $td = $this->lastRow->h('td',$mixed);
        
        # And finally add 
        /*
        while (count($adds)>0) {
            $value = array_shift($adds);
            $this->lastRow->h($tag,$value);
        }
        */
        return $td;
    }
    
    function &th($mixed='') {
        return $this->td($mixed,'th');
    }    
}

/****c* HTML Generation/androHtmlGrid
*
* NAME
*    androHtmlGrid
*
* FUNCTION
*   The PHP class androHtmlGrid simulates an HTML Table element
*   using only Divs.  
*
*   The object is a subclass of androHtml, and supports all of its
*   methods such as addChild, addClass, etc.
*
*
******
*/
class androHTMLGrid extends androHTML {
    var $columns    = array();
    var $headers    = array();
    var $lastRow    = false;
    var $lastCell   = 0;
    var $scrollable = false;
    var $colWidths  = 0;
    var $rows       = array();
    var $buttonBar  = false;
    var $colOptions = array();
    
    function androHTMLGrid(
        $height=300,$table,$lookups=false,$sortable=false,$bb=false,$edit=false
    ) {
        $this->lookups = $lookups;
        $this->sortable= $sortable;
        $this->htype = 'div';
        $this->addClass('tdiv box3');
        $this->hp['x6plugin'] = 'grid';
        $this->hp['x6table']  = $table;
        $this->hp['id']       = 'grid_'.$table;  #.'_'.rand(100,999);
        $this->hp['style'] = "height: {$height}px;";
        $this->height = $height;
        $cssLineHeight             = x6cssHeight('div.thead div div');
        $this->hp['cssLineHeight'] = $cssLineHeight;
        $this->hp['xRowsVisible']  = intval($height/$cssLineHeight);
        $this->hp['xGridHeight']   = $height;
        $this->hp['xLookups']      = $lookups ? 'Y' : 'N';
        $this->hp['xSortable']     = $sortable? 'Y' : 'N';
        $this->hp['xInitKeyboard'] = 'Y';
        
        # Figure the tbody height.  If lookups has
        # been set, double the amount we subtract
        $height-=x6cssHeight('div.thead div div');
        if($lookups) {
            $height-=x6cssHeight('div.thead div div');
        }
        if($bb) {
            $height  -= $this->bbHeight();
        }
        
        # create default options
        $this->hp['xGridHilight'] = 'Y';
        
        # Very first thing we add is a style, we will
        # overwrite it later
        $this->h('style');
        
        # notice: we slip one div inside of thead, 
        #         we assume there will always be
        #         only one row, the column headers
        $x = $this->h('div');
        $x->addClass('thead');
        $this->dhead0= $x;
        $this->dhead = $x->h('div');
        
        # Again, add button bar if required
        if($bb) {
            if(is_string($bb)) {
                $this->addButtonBar($bb);
            }
            else {
                $this->addButtonBar();
            }
        }
        $this->hp['xButtonBar'] = $bb ? 'Y' : 'N';
        
        # Add features if editInPlace
        $this->editable = false;
        if($edit) {
            $this->hp['uiNewRow' ] = 'Y'; // vs. nothing
            $this->hp['uiEditRow'] = 'Y'; // vs. nothing
            $this->editable = true;
        }
        
        # The body is empty, we have to add row by row
        $this->dbody= $this->h('div');
        $this->dbody->addClass('tbody');
        $this->dbody->hp['id'] = 'tbody_'.$table;
        $this->dbody->hp['style'] = "height: {$height}px;";
        
        # The footer is like the header, we go ahead
        # and insert the only row, assuming they will
        # be adding 
        $x = $this->h('div');
        $this->dfoot= $x->h('div');
        $this->dfoot->addClass('tfoot');

        # KFD 12/18/08.  Figured that this should always be the last
        #     command, never up in the middle.  Reason is that an object
        #     may put other plugins onto itself and then expect them to
        #     be active while it is initializing.  By putting this last,
        #     we ensure that that is the case.
        $this->initPlugin();
    }
    
    function setColumnOptions($options) {
        $this->colOptions = $options;
    }
    
    function inputsRow() {
        $dd = ddTable($this->hp['x6table']);
        
        # Make an input for each column and build up 
        # a string of HTML for these.
        $html     = '';
        $tabIndex = 1000;
        $count    = 0;
        $tabLoop  = null;
        foreach($this->columnsById as $colname=>$colinfo) {
            $options = a($this->colOptions,$colname,array());
            $wrapper = html('div');
            $wrapper->hp['gColumn'] = $count;
            $count++;
            $input   = input($dd['flat'][$colname],$tabLoop,$options);
            $input->hp['tabindex'] = $tabIndex++;
            # KFD 3/6/09 Sourceforge 2668359
            if($input->htype=='textarea') {
            	$input->setHtml("*VALUE_$colname*");
            }
            else {
                $input->hp['value'] ="*VALUE_$colname*";
            }
            $input->hp['xClassRow'] = 0;
            $input->hp['xTabGroup'] = 'rowEdit';
            $wrapper->addClass($this->hp['id'].'_'.$colname);
            if(!in_array($colinfo['type_id'],array('cbool','gender'))){
                unset($input->hp['size']);
            }
            $wrapper->addChild($input);
            $html.=$wrapper->bufferedRender(null,true);
        }
        $html    = str_replace("\n","",$html);
        $strLeft = 'x6.byId("'.$this->hp['id'].'").zRowEditHtml'; 
        jqDocReady("$strLeft = \"$html\"",true);
    }
    
    
    /****m* androHtmlGrid/addColumn
    *
    * NAME
    *    addColumn
    *
    * FUNCTION
    *   This PHP class method addColumn specifies the 
    *   description and size of a new column.  Call it once
    *   for each column to be added to the tabdiv.
    *
    * INPUTS
    *   - $caption string, becomes both caption and ID 
    *   
    * RETURNS
    *   - androHTML, reference to the content area for the new tab
    *
    ******/
    function addColumn($options) {
        $column_id   = arr($options,'column_id' );
        if($column_id=='') $column_id = rand(100000,999999);
        $dispsize    = arr($options,'dispsize'   ,10);
        # KFD 3/6/09 Sourceforge 2668452, respect descshort if present
        $description = arr($options,'descshort','');
        if($description=='') $description=arr($options,'description','No Desc');
        $type_id     = arr($options,'type_id'    ,'char');
        $forcelong   = arr($options,'forcelong'  ,false);
        $table_id_fko= arr($options,'table_id_fko','');

        # Permanently store the column information, 
        # and increment the running total
        $width1 = max($dispsize,strlen(trim($description)));
        $width1++;

        # KFD 1/8/09, expand width (maybe) if this column 
        #             gets an x6select
        if($table_id_fko<>'') {
            if($type_id=='cbool' || $type_id=='gender') {
                if($width1 < 5) $width1 = 5;
            }
            else {
                $width1+=3;
            }
        }
        
        # Now that we have what we need from description,
        # turn spaces into &nbsp;
        $description = str_replace(' ','&nbsp;',$description);
        
        
        # KFD Calculated width of 14 12px chars is 110px
        #     This means avg width is 7.85 pixels
        #     This means the ratio of width to height is .654
        #     However, if you add sortable, it gets a LEETLE TOO TINY,
        #     so we kicked it up to....
        $width1*= x6CssDefine('bodyfs','12px')*.67;
        $width = $forcelong ? $width1 : intval(min($width1,200)); 
        $pad0   = x6CSSDefine('pad0');
        $bord   = 1;   // HARDCODE!
        $this->colWidths += $width + ($pad0*2) + ($bord*2);
        
        # Save the information about the column permanently,
        # we will need all of this when adding cells.
        $colinfo = array(
            'description'=>$description
            ,'dispsize'   =>$dispsize
            ,'type_id'    =>$type_id
            ,'column_id'  =>$column_id
            ,'width'      =>$width
            ,'colprec'    =>arr($options,'colprec' ,$dispsize)
            ,'colscale'   =>arr($options,'colscale',$dispsize)
            ,'uiro'       =>arr($options,'uiro'    ,'N')
        );
        $this->columns[]               = $colinfo;
        $this->columnsById[$column_id] = $colinfo;
        $cssExtra = '';
        if(in_array($type_id,array('int','numb','money'))) {
            $cssExtra = 'text-align: right';
        }
        $styleId = 'div.'.$this->hp['id'].'_'.$column_id;
        $this->colStyles[$styleId] ="width: {$width}px; $cssExtra";
        $iWidth = $width;
        if($table_id_fko <> '') {
            $iWidth -= x6cssdefine('bodyfs','12px')*.67*5;
            $this->colStyles[$styleId.' input'] 
                ="width: {$iWidth}px; $cssExtra";
        }
        else if($type_id == 'mime-f') {
            $iWidth -= x6cssdefine('bodyfs','12px')*.67*20;
            $this->colStyles[$styleId.' input'] 
                ="width: {$iWidth}px; $cssExtra";
        }
        else if(!in_array($type_id,array('cbool','gender'))) {
            $this->colStyles[$styleId.' input'] 
                ="width: {$iWidth}px; $cssExtra";
        }
        
        # Finally, generate the HTML.
        $div = $this->dhead->h('div',$description);
        $div->hp['xColumn'] = $column_id;
        $div->addclass($this->hp['id'].'_'.$column_id);
        $this->headers[] = $div;
    }
    /****m* androHtmlGrid/lastColumn
    *
    * NAME
    *    lastColumn
    *
    * FUNCTION
    *   This PHP class method lastColumn must be called
    *   after you have defined all of the columns in the
    *   table.  This method computes and assigns the
    *   final width of the overall table.
    *
    * INPUTS
    *   - $scrollable (boolean) if true, make table scrollable 
    *   
    ******/
    function lastColumn($scrollable=true) {
        # Save the scrollable setting, and compute the final
        # width of the table
        #$this->scrollable=$scrollable;
        if($scrollable) {
            $this->columns[] = array(
                'description'=>'&nbsp;'
                ,'dispsize'   =>0
                ,'type_id'    =>''
                ,'column_id'  =>''
                ,'width'      =>15
            );
            $pad0   = x6CSSDefine('pad0');
            $bord   = 1;   // HARDCODE!
            $this->colWidths+=15 + ($pad0*2) + ($bord*2);
            $div = $this->dhead->h('div','');
            $div->hp['style'] ="
                max-width: 15px;
                min-width: 15px;
                width:     15px;";
        }
        
        # Send the column structure back as JSON
        jqDocReady(
            "x6.byId('".$this->hp['id']."').zColsInfo="
            .json_encode($this->columns)
            ,true
        );
        jqDocReady(
            "x6.byId('".$this->hp['id']."').zColsById="
            .json_encode($this->columnsById)
            ,true
        );
        
        # If editable, add in the invisible row of inputs
        if($this->editable) {
            $this->inputsRow();
        }
        
        # If the lookups flag is set, add that now
        if($this->lookups) {
            $this->addLookupInputs();
        }
        
        # If the sortable flag was set, add that now
        if($this->sortable) {
            $this->makeSortable();
        }
        
        # Generate the cell styles
        $styles = "\n";
        foreach($this->colStyles as $selector=>$rules) {
            $styles.="$selector { ".$rules."}\n";
        }
        $this->children[0]->setHTML($styles);
        
        
        # Get the standard padding, we hardcoded
        # assuming 2 for border, 3 for padding left
        #--$extra = 5;
        
        # now work out the final width of the table by 
        # adding up the columns, adding one for each
        # column (the border) and two more for the table
        # border.
        $width = $this->colWidths;
        # JB:  Increased width of master table by 1px so it lines up
        #$width+= ((count($this->columns))*$extra)+1;  // border + padding
        #--$width+= (count($this->columns))*$extra;
        #$width+= 39;  // fudge factor, unknown
        $this->hp['style'].="width: {$width}px";
        $this->width = $width;
        return $width;
    }

    function makeSortable() { 
        $table_id = $this->hp['x6table'];
        foreach($this->headers as $idx=>$header) {
            $hdrhtml = $header->getHtml();
            $a = html('a-void');
            $a->setHtml($hdrhtml);
            #$a->setHtml('&hArr;');
            $col = $this->columns[$idx]['column_id'];
            $args="{xChGroup:'$table_id', xColumn: '$col'}";
            $a->hp['onclick'] = "x6events.fireEvent('reqSort_$table_id',$args)";
            $a->hp['xChGroup'] = $table_id;
            $a->hp['xColumn']  = $col;
            $this->headers[$idx]->setHtml(
                $a->bufferedRender()
            );
        }
    }
    
    function addRow($id,$thead=false) {
        if(!$thead) {
            $this->lastRow = $this->dbody->h('div');
        }
        else {
            $this->lastRow = $this->dhead0->h('div');
        }
        $this->rows[] = $this->lastRow;
        // KFD EXPERIMENTAL 12/9
        $this->lastRow->hp['id'] = $this->hp['x6table']."_$id";        
        #$this->lastRow->hp['id'] = 'row_'.$id;
        
        $this->lastCell = 0;

        # PHP-JAVASCRIPT DUPLICATION ALERT!
        # This code also exists in x6.js in browser-side
        # constructor of the tabDiv object.
        $table_id = $this->hp['x6table'];
        if($this->hp['xGridHilight'] == 'Y') {
            # Removes hilight from any other row, and hilights
            # this one if it is not selected (edited)
            $this->lastRow->hp['onmouseover']='x6grid.mouseover(this)';
            #    "$(this).siblings('.hilight').removeClass('hilight');
            #    $('#row_$id:not(.selected)').addClass('hilight')";
            if(!$thead) {
                $this->lastRow->hp['onclick']    
                    ="x6events.fireEvent('reqEditRow_$table_id',$id);";
            }
        }
        
        return $this->lastRow;
    }
    function addCell($child='',$class='',$id='',$convert=true) {
        if(is_object($child)) {
            $child=$child->bufferedRender();
        }
        else {
            if($convert) {
                $child=str_replace(' ','&nbsp;',$child);
            }
        }
        if(trim($child)=='') $child = '&nbsp;';
        # figure out if we need a new row
        $maxcols = count($this->columns);
        if($this->scrollable) $maxcols--;
        if($this->lastCell > $maxcols) {
            $this->addRow();
        }
        
        # now put out the actual div
        $info = $this->columns[$this->lastCell];
        $width= $info['width'];
        $div = $this->lastRow->h('div',$child);
        if($id<>'') {
            $div->hp['id'] = $id;
        }
        if($class!='') $div->addClass($class);
        $div->hp['gColumn'] = $this->lastCell;
        $div->addClass(
            $this->hp['id']
            .'_'.$this->columns[$this->lastCell]['column_id']
        );
            /*
        $div->hp['style'] ="
            overflow: hidden;
            max-width: {$width}px;
            min-width: {$width}px;
            width:     {$width}px;";
            */
        # Now for numerics do right-justified
        if(in_array($info['type_id'],array('int','numb','money'))) {
            $div->hp['style']='text-align: right';
        }
        
        # up the cell counter
        $this->lastCell++;
        
    }   
    
    function addData($rows) {
        $dd = ddTable($this->hp['x6table']);
        foreach($rows as $row) {
            $this->addRow($row['skey']);
            foreach($this->columns as $colinfo) {
                if($colinfo['column_id']=='') continue;
                
                $column_id = trim($colinfo['column_id']);
                if(isset($row[$column_id])) {
                    $type_id=$dd['flat'][$column_id]['type_id'];
                    $x6view =arr($dd['flat'][$column_id],'x6view','text');
                    if(!($type_id=='text' && $x6view=='window')) {
                        $value=hFormat($type_id,$row[$column_id]);
                    }
                    else {
                        $t = $this->hp['x6table'];
                        $c = $column_id;
                        $s = $row['skey'];
                        $a = html('a');
                        $a->setHtml('View');
                        $a->hp['href']
                            ="javascript:x6inputs.viewClob($s,'$t','$c')";
                        $value = $a;
                    }
                    $this->addCell($value);
                }
                else {
                    $this->addCell('');
                }
            }
        }
    }
    
    function noResults() {
        return;
        $div = $this->dbody->h('div');
        $div->hp['id'] = $this->hp['x6table'].'_noresults';
        $div->hp['style'] = 'text-align: center; padding-top: 20px';
        $div->setHTML('<b>No results found</b>');
    }
    
    function addLookupInputs() {
        $fakeCI = array('colprec'=>'10');
        
        $table_id = $this->hp['x6table'];
        $this->addRow('lookup',true);
        foreach($this->columns as $idx=>$colinfo) {
            # Skip the column that is for the scrollbar
            $column = trim($colinfo['column_id']);
            if($column=='') continue;
            
            $inpid = 'search_'.$table_id.'_'.$column;
            
            $width = $colinfo['width'] - (2* x6cssDefine('pad0')) - 2;
            
            $nothing= array();
            $options = array('forceinput'=>true);
            $inp= input($colinfo,$nothing,$options);
            if($idx==0) {
                $inp->ap['x6firstFocus']='Y';
            }
            $inp->hp['maxlength'] = 500;
            $inp->hp['id'] = $inpid;
            $inp->hp['autocomplete'] = 'off';
            $inp->hp['xValue']='';
            $inp->hp['xColumnId'] = $column;
            $inp->hp['xNoPassup'] = 'Y'; 
            $inp->hp['onkeyup']   = "x6.byId('".$this->hp['id']."').fetch()";
            $inp->hp['style'  ]   = "width: {$width}px";
            $inp->hp['xLookup']   = 'Y';
            $inp->hp['value']     = gp('pre_'.$column,'');
            if(isset($inp->hp['x6select'])) unset($inp->hp['x6select']);
            #$inp->ap['xParentId'] = $t->hp['id'];
            #$inp->ap['xNoEnter'] = 'Y';
            $this->addCell($inp,'linput');
        }
        
        if($this->scrollable) {
            $this->addCell('');
        }
        
    }
}

/****c* HTML Generation/androHtmlDetail
*
* NAME
*    androHtmlDetail
*
* FUNCTION
*   The PHP class androHtmlDetail provides an HTML table that can
*   be populated with caption/input rows using addInput().
*
*   The object is a subclass of androHtml, and supports all of its
*   methods such as addChild, addClass, etc.
*
*
******
*/
class androHTMLDetail extends androHTML {
    var $firstFocus = false;
    
    function androHTMLDetail($table_id,$complete=false,$height=300,$p=''){
        $this->hp['x6plugin'] = 'detailDisplay';
        $this->hp['x6table']  = $table_id;
        $this->hp['id'] = 'ddisp_'.$table_id;
        $this->initPlugin();
        $this->hp['xHeight'] = $height;
        if($complete) {
            $this->htype='div';
            $this->innerId  = "ddisp_{$table_id}_inner";
            $this->makeComplete($table_id,$height,$p);
        }
        else {
            $this->htype='table';
            $this->inputsTable=$this;
            $this->addClass('x6Detail');
        }
    }
    
    function makeComplete($table_id,$height,$parTable) {
        # The complete track is much more involved, adds
        # buttons and a status bar at bottom.
        $this->addClass('box2');
        $this->hp['xInitDisabled'] = 'Y';
        $pad0 = x6CssDefine('pad0');
        $this->hp['style'] = "height: {$height}px;
            padding-left: {$pad0}px;
            padding-right: {$pad0}px;";
            
        $this->hp['xInnerWidthLost'] 
            = ($pad0 * 2)     // padding left and right
            + x6cssRuleSize('.box2','border-left')
            + x6cssRuleSize('.box2','border-right')
            + x6cssRuleSize('.box1','border-left')    // see below, inner  
            + x6cssRuleSize('.box1','border-right')   // box is box1
            + ($pad0 * 7);  // padding left and right of box1

        # Always need this
        $dd = ddTable($table_id);
        
        # Now for the display
        # Put some buttons on users
        $this->addButtonBar();
        
        # KFD 1/29/09 break out pk/fk columns
        if($parTable == '') {
            $colsFK = array();
        }
        else {
            #echo $table_id;
            #hprint_r($dd['fk_parents']);
            $x = $dd['fk_parents'][$parTable]['cols_both'];
            $x = explode(',',$x);
            foreach($x as $pair) {
                list($chd,$par) = explode(':',$pair);
                $colsFK[$chd] = $par;
            }
        }
                
        # Put in a div that will be the inner box
        #
        $div = $this->h('div');
        $div->addClass('box1');
        $table = $div->h('table');
        $table->hp['style']='float: left; margin-right: 20px';
        $table->addClass('x6Detail');
        $this->inputsTable = $table;
        $cols  = projectionColumns($dd,'');

        # KFD 1/2/08.  Loop through columns and try to find anything
        #              with an x6breakafter.  If found, do not break
        #              every 17, use the instructions in x6breakafter.
        $break17 = true;
        foreach($cols as $idx=>$col) {
            if(arr($dd['flat'][$col],'x6breakafter','')<>'') {
                $break17 = false;
                break;
            }
        }

        # Define this outside the loop, it is used to make
        # xdefsrc inside of the loop
        $fetches = array('fetchdef','fetch','distribute');
        $options = array(
            'xTabGroup'=>'ddisp_'.$table_id
        );
        foreach($cols as $idx=>$col) {
            if($break17) {
                if($idx>0 && $idx % 17 == 0) {
                    $this->inputsTable=$div->h('table');
                    $this->inputsTable->hp['style'] = 'float: left';
                    $this->inputsTable->addClass('x6Detail');
                }
            }
            
            # KFD 1/29/09.  If detail that is child of a parent,
            #               see if this column needs to pull
            if(!isset($colsFK[$col])) 
                $xoptions = $options;
            else {
                $xoptions = array_merge(
                    $options
                    ,array('attributes'=>array(
                        'xdefsrc'=>$parTable.'.'.$colsFK[$col])
                    )
                );
            }
            
            # KFD 2/4/09. If this is in the fetch family, set its
            #             xdefsrc
            $autoid=strtolower($dd['flat'][$col]['automation_id']);
            if(in_array($autoid,$fetches)) {
                $xoptions = array_merge(
                    $options
                    ,array('attributes'=>array(
                        'xdefsrc'=>strtolower($dd['flat'][$col]['auto_formula'])
                    ))
                );
            }
            
            $this->addTRInput($dd,$col,$xoptions);
            $x6ba = trim(arr($dd['flat'][$col],'x6breakafter',''));
            if($x6ba=='column') {
                $this->inputsTable=$div->h('table');
                $this->inputsTable->hp['style'] 
                    = 'float: left';
                $this->inputsTable->addClass('x6Detail');
            }
            if($x6ba=='line') {
                $tr = $this->inputsTable->h('tr');
                $td = $tr->h('td','&nbsp;');
                $td->hp['colspan'] = 2;
            }
        }

        # Calculate height of inner area
        $hinner 
            =$height
            -($pad0 * 2)          // padding top and bottom
            -x6cssDefine('lh0')   // for status bar at bottom
            -x6cssRuleSize('.box1','border-top')
            -x6cssRuleSize('.box1','border-bottom')
            -x6cssRuleSize('.box1','padding-top')
            -x6cssRuleSize('.box1','padding-bottom')
            -x6cssHeight('div.x6buttonBar a.button');
        $div->hp['style'] = "height: {$hinner}px; clear: both; 
            overflow-y: scroll; position: relative;
            padding: {$pad0}px;";
            
        # Keep track of the inner div for possible additions
        $div->hp['id'] = $this->innerId;
        $lineheight = x6cssHeight('td.x6Caption');
        $emptyHeight  
            = $hinner
            - $lineheight * (count($cols))  // computed height
            - (count($cols)-1);              // borders between rows
        $this->hp['xInnerHeight'] = $hinner;
        $this->hp['xInnerEmpty']  = $emptyHeight;
        
        $this->innerDiv = $div;
        
        $sb = $this->h('div');
        $sb->addClass('statusBar');
        $sbl = $sb->h('div');
        $sbl->addClass('sbleft');
        $sbl->hp['id'] = 'sbl_'.$table_id;
        $sbr = $sb->h('div');
        $sbr->addClass('sbright');
        $sbr->hp['id'] = 'sbr_'.$table_id;
        
        return $emptyHeight;
    }
    
    /****m* androHtml/addTRInput
    *
    * NAME
    *    androHtml.addTRInput
    *
    * FUNCTION
    *	The PHP method androHtml.addInput adds a TR and two TD elements
    *   to a TABLE.  The left side has class "x6Caption" and contains the
    *   caption/label for the field.  The right side contains an input
    *   for the field and as class 'x6Input'.
    *
    * 
    *
    * INPUTS
    *   array $dd - the table's complete data dictionary
    *   string $column - name of the database column (field)
    *
    *
    * SOURCE
    */
    function addTrInput(&$dd,$column,$options=array()) {
        # something we need for b/w compatibility that is
        # easier to declare and ignore than it is to
        # try to get rid of. (Also, getting rid of it will
        # break some of my older apps).
        $tabLoop=array();
        
        $tr = $this->inputsTable->h('tr');
        $td = $tr->h('td',$dd['flat'][$column]['description']);
        $td->addClass('x6Caption');
        
        $input=input($dd['flat'][$column],$tabLoop,$options);
        if(!$this->firstFocus) {
            $input->hp['x6firstFocus'] = 'Y';
            $this->firstFocus = true;
        }
        $td = $tr->h('td');
        $td->setHtml($input->bufferedRender());
        $td->addClass('x6Input');
        
    }
    /******/
    
}

/****c* HTML Generation/androHtmlxrefs
*
* NAME
*    androHtmlxrefs
*
* FUNCTION
*   The PHP class androHtmlxRefs generates a tabbed list of child
*   tables to the named parent.  Only child tables with the
*   "x6xref" property set are included.  The only supported value
*   for "x6xref" at this time is "checkboxes".
*
*   The object is a subclass of androHtml, and supports all of its
*   methods such as addChild, addClass, etc.
*
*   IMPORTANT! If there are no qualifying child tables, the object
*   is still created and returned, but it will have "display: none"
*   and will effectively not exist for the user.
*
* INPUTS
*   * string table_id - the parent table
*   * number height - the total height available for the display
*
* RETURNS
*   object - androHTMLxrefs object.
*
******
*/
class androHTMLxrefs extends androHTML {
    var $firstFocus = false;
    
    function androHTMLxrefs($table_id,$height = 300) {
        # Extreme basics for child tables.
        $this->htype         = 'div';
        $this->hp['x6table'] = $table_id;
        $this->hp['xCount']  = 0;

        # First bit of business is to run through and find
        # out if we actually have any kids.
        $dd       = ddTable($table_id);
        
        $kids = array();
        $atts = array();
        foreach($dd['fk_children'] as $table_kid=>$info) {
            if(arr($info,'x6xref','')<>'') {
                $kids[$table_kid] = $info['x6xref'];
                $atts[] = "$table_kid:{$info['x6xref']}";
            }
        }
        
        # If no kids, set ourselves to be invisible
        if(count($kids)==0) {
            $this->hp['style'] = 'display: none;';
            return;
        }
        $this->hp['xCount'] = count($kids);
        
        $options = array(
            'x6profile'=>'x6xrefs'
            ,'x6table'=>$table_id
            ,'styles'=>array(
                'overflow-y'=>'scroll'
            )
        );
        $tabs = $this->addTabs($table_id.'_xrefs',$height,$options);
        $tabs->ul->hp['kids'] = implode("|",$atts);
        # If we are still here, we have at least one kid.  Let's
        # put in a tab bar and start adding the kids.
        foreach($kids as $kid=>$x) {
            $pane = $tabs->addTab($dd['fk_children'][$kid]['description']);
        }
    }
}

function addModal($modal) {
    $modal->hp['x6modal'] = 'Y';
    if(!isset($GLOBALS['AG']['modals'])) {
        $GLOBALS['AG']['modals'] = array();
    }
    $GLOBALS['AG']['modals'][] = $modal;
}

/*
class androHTMLModal extends androHTML {
    function androHTMLModal($id,$title='') {
        # First stuff is basic stuff for any plugin
        $this->htype='div';
        $this->addClass('x6modal');
        $this->hp['id'] = $id;
        
        # look for a global list of modals
        if(!isset($GLOBALS['AG']['modals'])) {
            $GLOBALS['AG']['modals'] = array();
        }
        $GLOBALS['AG']['modals'][] = $this;
        return;
        
        $this->hp['x6plugin'] = 'modal';
        $this->hp['x6table']  = '*';
        $this->initPlugin();
        
        # Ugly hack, tell the javascript how much padding to
        # take away.
        $this->hp['xSpacing'] = x6CssDefine('pad0')*3;
        
        # Title and link go across top      
        $top = $this->h('div');
        $top->addClass('x6modaltop');
        $left = $top->h('div');
        $left->hp['style'] = 'float: left';
        $title = $left->h('b',$title);
        $right= $top->h('div');
        $right->hp['style'] = 'float:right';
        $a=$right->h('a','ESC: Exit');
        $a->hp['href'] = "javascript:x6.byId('$id').close();";

        $x = $this->h('div');
        $x->hp['style'] = 'clear:both';
        $this->inner = $this->h('div');
        $this->inner->addClass('x6modalinner');
    }
}
*/


/**
* Create a form with parent html element $parent.
*
* INPUTS
*	reference &$parent	parent html element to form
*	string $page	page of form (used to create action attribute)
*/

/****f* HTML Generation/htmlForm
*
* NAME
*    htmlForm
*
* FUNCTION
*	The PHP function htmlForm creates an html form element with the provided parent element.
*
* INPUTS
*	reference $parent - Reference to the parent element for this form
*	string $page - action page for this form
*
* RETURN VALUE
*	androHtml - Generated form
******/
function htmlForm(&$parent,$page='') {
    if($page=='') $page = gp('x4Page');
    if($page=='') $page = gp('gp_page');
    $form = html('form',$parent);
    $form->hp['method'] = 'POST';
    $form->hp['action'] = "index.php?x4Page=$page";
    $form->hp['id'] = 'Form1';
    $form->hp['enctype'] = 'multipart/form-data';
    $inp = html('input',$form);
    $inp->hp['type'] = 'hidden';
    $inp->hp['name'] = 'MAX_FILE_SIZE';
    $inp->hp['value']= '10000000';
    return $form;
}

/**
* Lower level routine to generate an input html element.  Place type
* of input into $colinfo array as 'type_id'.
*
* INPUTS
*	array $colinfo	column info
*	reference &$tabLoop
*	array $options	options
* RETURN
*	androHtml	object oriented input html
*/

/****f* HTML Generation/input
*
* NAME
*    input
*
* FUNCTION
*	The PHP function input is a lower level routine that generates an input html element.
*
* INPUTS
*	array $colinfo - column info
*	reference $tabLoop - tab loop
*	array $options - option elements
*
* RETURN VALUE
*	androHtml - generated input element
*
******/
function input($colinfo,&$tabLoop = null,$options=array()) {
    $formshort= a($colinfo,'formshort',a($colinfo,'type_id','char'));
    $type_id  = a($colinfo,'type_id');
    $colprec  = a($colinfo,'colprec');
    $colscale = a($colinfo,'colscale');
    $table_id = a($colinfo,'table_id');
    $column_id= a($colinfo,'column_id');

    $x6 = vgfGet('x6');
    if($x6) {
        $x6options = array(
            'onkeyup'=>'x6inputs.keyUp(this,event)'
            ,'onkeydown'=>'return x6inputs.keyDown(this,event)'
            ,'onfocus'=>'x6inputs.focus(this)'
            ,'onblur'=>'x6inputs.blur(this)'
            ,'attributes'=>array(
                'xClassRow'=>1
            )
            ,'tabIndex'=>true
        );
        $x6options['attributes'] = array_merge(
            array('xClassRow'=>1)
            ,arr($options,'attributes',array())
        );
        $options = array_merge($options,$x6options);
    }
    

    # Work out the read-only status for insert and update
    # Begin with unconditional
    $xRoIns = '';
    $xRoUpd = '';
    if( ($parent = arr($options,'parentTable'))<>'') {
        if($colinfo['table_id_fko']==$parent) {
            $xRoIns = 'Y';
            $xRoUpd = 'Y';
        }
    }
    if(!$xRoIns) {
        $xRoIns = arr($colinfo,'uiro','N');
        $xRoUpd = arr($colinfo,'uiro','N');
        # KFD Sourceforge 2668117 Added min/max to automations
        #                 that prevent user interaction
        $autos = array('SUM','COUNT','FETCH','DISTRIBUTE','SEQUENCE'
            ,'TS_INS','TS_UPD','UID_INS','UID_UPD','EXTEND'
            ,'MIN','MAX'
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
    if(arr($colinfo,'x6view','')=='window') {
        $input = html('a');
        $input->setHtml('View');
        $input->hp['href'] 
            ="javascript:x6inputs.viewClob(this,'$table_id','$column_id')";
        return $input;
    }
    if(trim($type_id)=='mime-f' && $x6) {
        $input = html('input');
        $input->hp['type'] = 'file';
        $input->addClass('x6fileupload');
    }
    elseif($type_id=='gender') {
        $input = html('select');
        $option = html('option',$input);  // this is a blank option
        $option = html('option',$input,'M');
        $option->hp['value']='M';
        $option = html('option',$input,'F');
        $option->hp['value']='F';
        $option = html('option',$input,'U');
        $option->hp['value']='U';
        $option = html('option',$input,'H');
        $option->hp['value']='H';
    }
    elseif($type_id=='cbool') {
        $input = html('select');
        $option = html('option',$input);  // this is a blank option
        $option = html('option',$input,'Y');
        $option->hp['value']='Y';
        $option = html('option',$input,'N');
        $option->hp['value']='N';
    }
    elseif(($type_id=='text' || $type_id=='mime-h' || $type_id=='mime-h-f')
            && arr($options,'forceinput',false)==false) {
        $input = html('textarea');
        $rows = a($colinfo,'uirows',10);
        $rows = $rows == 0 ? 10 : $rows;
        $cols = a($colinfo,'uicols',50);
        $cols = $cols == 0 ? 50 : $cols;
        $input->hp['rows'] = $rows;
        $input->hp['cols'] = $cols;
        $input->ap['xNoEnter'] = 'Y';
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

        /* KFD 5/28/08, experimental, always do dynamic */
        $input = html('input');
        if($ddfko['fkdisplay']<>'none') {
            $fkparms='gp_dropdown='.$colinfo['table_id_fko'];
            if($xRoIns<>'Y') {
                $input->hp['onkeyup']  ="androSelect_onKeyUp(  this,'$fkparms',event)";
                $input->hp['onkeydown']='androSelect_onKeyDown(event)';
                $input->hp['onblur'] = 'androSelect_onBlur()';
            }
        }

        # Give it a class that jQuery will recognize
        if( a($options,'noinfo','N')=='N') {
            $input->addClass('x4Info');
        }
        $input->ap['xTableIdPar'] = $colinfo['table_id_fko'];
        $input->ap['xMatches']    = a($colinfo,'matches');

        // If any columns are supposed to fetch from here,
        // set an event to go to server looking for fetches
        //
        if($table_id <> '') {
            $tabdd = ddTable($table_id);
            $fetchdist = $table_id."_".$table_id_fko."_";
            if(isset($tabdd['FETCHDIST'][$fetchdist])) {
                if(!isset($input->hp['onblur'])) $input->hp['onblur']='';
                $input->hp['onblur']
                    .=";a.forms.fetch("
                    ."'$table_id','$column_id',this.value,x4.parent(this),this"
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
    
    # Assign all inputs to a tab group.  If no optional
    # value was provided, use a default
    $input->hp['xTabGroup'] 
        = arr($options,'xTabGroup',arr($options,'tabGroup','tgdefault'));

    #  If we ended up with an INPUT above, set the size
    if($input->htype=='input') {
        # KFD 4/24/08, makes it easier to make widgets in
        #              in custom code, don't require 'dispsize';
        # KFD 6/9/08,  refine this to trap dates
        if(!isset($colinfo['dispsize'])) {
            if($type_id=='date') {
                $colinfo['dispsize'] = 11;
            }
            else if(isset($colinfo['colprec'])) {
                $colinfo['dispsize'] = $colinfo['colprec']+1;
            }
        }

        $input->hp['size'] = min(
            arr($colinfo,'dispsize',30)
            ,OptionGet('dispsize',30)
        );
        $input->hp['maxlength'] = arr($colinfo,'dispsize',10);
    }

    # Add classes that jquery recognizes to
    # extend out the stuff
    if($type_id=='time') {
        $input->addClass('x4Time');
    }
    if($type_id=='date') {
        $input->hp['onkeyup'] = 'x4.stdlib.inputKeyUpDate(event,this)';
    }

    # Establish identifying stuff
    $input->ap['xTableId']  = $table_id;
    $input->ap['xColumnId'] = $column_id;
    if(a($colinfo,'inputId','') <> '') {
        $input->hp['id'] = $colinfo['inputId'];
    }
    # KFD 8/7/08, allow options to contain a prefix
    elseif(a($options,'prefix','')<>'') {
        $input->hp['id'] = $options['prefix'].$table_id.'_'.$column_id;
    }
    elseif($table_id<>'') {
        $prefix = vgfGet('x6') ? 'x6inp_' : 'x4inp_';
        $input->hp['id'] = $prefix.$table_id.'_'.$column_id;
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

    # These are universal properties that were passed in
    $input->ap['xTypeId'] = $type_id;
    $input->ap['xColprec'] = $colprec;
    $input->ap['xColscale'] = $colscale;

    # KFD 11/1/08.  Grab an input mask if it has been
    #               provided.  Otherwise make one up for
    #               numerics, dates, etc.
    if(a($colinfo,'inputmask','')<>'') {
        $input->ap['xInputMask'] = $colinfo['inputmask'];
    }
    else {
        switch($colinfo['type_id']) {
        case 'numb':
            if($colinfo['colscale']==0) {
                $inputMask = str_repeat('9',$colinfo['colprec']);
            }
            else {
                $left = $colinfo['colprec'] - $colinfo['colscale'] - 1;
                $inputMask 
                    =str_repeat('9',$left)
                    .'.'.str_repeat('9',$colinfo['colscale']); 
            }
            break;
        case 'money':
            $inputMask = '9,999,999.99';
            break;
        case 'int':
            $inputMask = '999,999,999';
            break;
        case 'date':
            $inputMask = '99/99/9999';
            break;
        default:
            $inputMask = '';
        }
        if($inputMask!='') {
            $input->hp['xInputMask'] = $inputMask;
        }
    }

    # If the tabloop object has been passed in, add this
    # input to it
    if(!is_null($tabLoop)) {
        $tabLoop[] = &$input;
    }

    # Look for "on" options
    $list=array('onchange'
        ,'onkeyup','onkeydown','onkeypress'
        ,'onfocus','onblur'
        ,'onmouseover','onmouseout','onclick'
    );
    foreach($options as $key=>$value) {
        if(in_array($key,$list)) {
            $input->hp[$key] = $value;
        }
    }
    
    # KFD 11/29/08, while we are on options, for a SELECT
    #               in x6, onchange should set class.  This
    #               makes it change class even before 
    #               losing focus if user uses mouse
    if($x6 && $input->htype=='select') {
        $input->hp['onclick'] = 'x6inputs.setClass(this)';
    }
    
    # KFD 10/18/08, set tab index using new x6 if option is there
    if(arr($options,'tabindex',false)) {
        $input->tabIndex();
    }
    
    # KFD 10/18/08, accept a set of attributes from the
    #               options array
    $atts = arr($options,'attributes',array());
    #if(count($atts)>0) x6data($column_id,$atts);
    
    # KFD 2/5/09 modified so only specific situations are readonly
    $readonlies=array('fetch','distribute');
    $autoid    =arr($colinfo,'automation_id','');
    $tfko      =arr($colinfo,'table_id_fko' ,'');
    foreach($atts as $name=>$value) {
        $input->hp[$name]=$value;
        if($name=='xdefsrc') {
            if(in_array($autoid,$readonlies) || $tfko <> '') {
                $input->hp['xRoIns'] = 'Y';
                $input->hp['xRoUpd'] = 'Y';
            }
        }
    }
    
    # KFD 10/18/08, add any classes named in options
    $classes = arr($options,'classes',array());
    foreach($classes as $class) {
        $input->addClass($class);
    }
    
    # KFD 10/18/08, add default if present
    if(arr($colinfo,'automation_id','') == 'DEFAULT') {
        $input->hp['xDefault'] = $colinfo['auto_formula'];
    }

    #  KFD 12/10/08  Major redirection if doing x6, call
    #                out for possible wrapping of input
    #
    if($x6) return inputForX6($input,$colinfo,$options); 

    # For now that's all we are going to do.
    return $input;
}

function inputForX6($input,$colinfo,$options) {
    # First decide what kind of input to do
    
    if(in_array($colinfo['type_id'],array('cbool','gender'))) {
        $input->htype='input';
        $input->hp['size'] = 1;
        $input->hp['x6select'] = 'Y';
        $input->children = array();  // wipe out options
        $input->hp['xTitles'] = 'Value|Description';
        if($colinfo['type_id'] == 'cbool') {
            $input->hp['xValues'] = 'Y|Yes||N|No';
            $input->hp['x6rowCount'] = 2;
        }
        else {
            $input->hp['xValues'] 
                = 'M|Male||F|Female||U|Unknown||H|Hermaphrodite';
            $input->hp['x6rowCount'] = 4;
        }
    }
    else if(arr($colinfo,'table_id_fko')<>'') {
        $fko = $colinfo['table_id_fko'];
        $ddpar = ddTable($fko);
        $uis   = $ddpar['projections']['_uisearch'];
        $ccols = arr($ddpar['projections'],'dropdown',$uis);
        $cols  = explode(',',$ccols);
        $titles= array();
        foreach($cols as $col) {
            $titles[] = $ddpar['flat'][$col]['description'];
        }
        $input->hp['x6select'] = 'Y';
        $input->hp['xTitles'] = implode('|',$titles);
        $input->hp['x6seltab'] = $fko;
        $input->hp['x6profilePar'] = arr($ddpar,'x6profile','conventional');
        $x6profile = arr($ddpar,'x6profile','conventional');
        $pfs = array('tabDiv','twosides');
        if(arr($ddpar,'x6all','N')=='Y' || in_array($x6profile,$pfs)) {
            $rows  = SQL_AllRows(
                "Select $ccols
                   from ".$ddpar['viewname']."
                  order by ".$ddpar['pks']
            );
            $input->hp['x6rowCount'] = count($rows);
            $values = array();
            foreach($rows as $row) {
                foreach($row as $colname=>$colvalue) {
                    if(is_null($colvalue)) $row[$colname]='&nbsp;';
                }
                $values[] = implode('|',$row);
            }
            $input->hp['xValues'] = implode('||',$values);
        }
    }
    return $input;
}

function x6select() {
    $retval = html('div');
    $input  = $retval->h('input');
    $button = $retval->h('img');
    $button->hp['src'] = 'clib/mouseTest.png';
    return $retval;
}

/**
* Generate a set of inputs for a given projection on a given table,
* organized as an HTML TABLE with one row per input, captions on
* left and inputs on right.
*
*	array $dd	Data Dictionary for table
*	string $projection Name of projection, or array of columns,
*                           or comma-list of columns.
*/

/****f* HTML Generation/projection
*
* NAME
*    projection
*
* FUNCTION
*	The PHP function projection generates a set of inputs for a given projection on a given table, organized
*	as an HTML TABLE with one row per input, captions on left and inputs on right.
*
* INPUTS
*	* array $dd - Data Dictionary for table
*	* mixed $projection - name of proection, or array of columns, or comma-list of columns
*	* reference $tabLoop - Tab loop
*	* array $options - Options for the projection
*
* RETURN VALUE
*	androHtml - generated projection (html table)
*
******/
function projection($dd,$projection='',&$tabLoop,$options=array()) {
    $columns = projectionColumns($dd,$projection);

    # Create a top level container
    $ttop  = html('table');
    $ttop->inputs = array();
    $trtop = $ttop->h('tr');

    # Lay out the projection as a table and return it
    $tdx   = $trtop->html('td');
    $table = $tdx->html('table');
    $table->addClass('x4Detail');
    $uiwithnext = 'N';
    $td = false;
    # KFD 8/7/08, moved all options into the projection system
    $colcount   = 0;
    $colbreak   = a($options,'colbreak',17);
    $breakafter = a($options,'breakafter',array());
    foreach($columns as $column) {
        $input = input($dd['flat'][$column],$tabLoop,$options);
        $ttop->inputs[] = $input;
        if($uiwithnext == 'Y') {
            $td->nbsp(2);
            $td->h('span',$dd['flat'][$column]['description'].":");
            $td->nbsp();
            $td->addChild($input);
        }
        else {
            $tr = $table->h('tr');
            $tr->h('td',$dd['flat'][$column]['description'].':','x4Caption');

            $td = $tr->h('td','','x4Input');
            $td->addChild($input);
        }
        $uiwithnext = a($dd['flat'][$column],'uiwithnext','N');

        $colcount++;
        if(count($breakafter)>0) {
            $break = in_array($column,$breakafter);
        }
        else {
            $break = $colcount == $colbreak ? true : false;
        }
        if($break) {
            $colcount=0;
            $tdx = $trtop->html('td');
            $tdx->hp['style'] = 'width: 40px';
            $tdx = $trtop->html('td');
            $table=$tdx->html('table');
            $table->hp['style'] = 'margin-left: 30px';
            $table->addClass('x4Detail');
        }

    }
    return $ttop;
}

function projectionColumns($dd,$projection='') {
    # Work out what they gave us and make a list of
    # columns out of it
    if(is_array($projection)) {
        # they gave us an array of columns
        $columns = $projection;
    }
    else {
        if($projection == '') {
            $x6      = vgfGet('x6',false);
            $columns = array();
            foreach($dd['flat'] as $column_id=>$colinfo) {
                if($colinfo['uino']=='Y'   ) continue;
                if($column_id=='skey'      ) continue;
                if($column_id=='_agg'      ) continue;
                if($column_id=='skey_quiet') continue;
                if($x6) {
                    if($column_id=='ts_ins' ) continue;
                    if($column_id=='ts_upd' ) continue;
                    if($column_id=='uid_ins') continue;
                    if($column_id=='uid_upd') continue;
                }
                $columns[]=$column_id;
            }
        }
        elseif(isset($dd['projections'][$projection])) {
            # they named a projection
            $columns = explode(',',$dd['projections'][$projection]);
        }
        else {
            # assume a comma list of columns
            $columns = explode(',',$projection);
        }
    }
    return $columns;    
}

/****f* HTML Generation/inputsTabLoop
*
* NAME
*    inputsTabLoop
*
* FUNCTION
*	The PHP function inputsTabLoop adds keybindings and inputs to the provided tabloop.
*
* INPUTS
*	reference $tabLoop - tab loops
*	array $options - Options for the input.
*
******/
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
                ="\$a.byId('$xpId').zLastFocusId = this.id;"
                ."u.bb.vgfSet('focus',this);";
        }

    }
}

/****f* HTML Generation/inputFixupByType
*
* NAME
*    inputFixupByType
*
* FUNCTION
*	The PHP function inputFixupByType adds keyboard input if provided input has an additional attribute
*	xTypeId equal to date.
*
* NOTE
*	Moved to main input function
*
* INPUTS
*	androHtml - Input element
*
* RETURN VALUE
*	androHtml - Modified input element
******/
function inputFixupByType($input) {
    return $input;
    if($input->ap['xTypeId'] == 'date') {
        $input->hp['onkeyup']
            ='return x4.stdlib.inputKeyUpDate(event,this)';
    }
    return $input;
}



# ==============================================================
#
# SECTION: HTML RENDERING PART 2: Snippets
#
# These routines produce little snippet values
# ==============================================================
/****f* HTML Generation/hprint_r
*
* NAME
*	hprint_r
*
* FUNCTION
*	Dumps the variable $anyvalue, but wraps it in HTML PRE tags so
*	it is readable in a browser.
*
* INPUTS
*	mixed $anyvalue - variable to dump
*
* RETURN
*	string	HTML_Fragment
*
* SOURCE
*/
function hprint_r($anyvalue) {
	echo "<pre>\n";
   print_r($anyvalue);
	echo "</pre>";
}
/******/

/****f* HTML Generation/hx
*
* NAME
*	hx
*
* FUNCTION
*	Shortcut to PHP's htmlentities function
*
* INPUTS
*	string $in	string to be sanitized
*
* RETURN
*	string	sanitized string
*
* SOURCE
*/
function hx($in) {
   return htmlentities($in);
}
/******/


# ==============================================================
#
# SECTION: Get config values
#
# ==============================================================
/**
* Gets configuration data for $var.  If no config information found,
* returns $default. Skips all config values in $skip.  There are 4 levels
* of configuration variables.  The lowest level is the framework level.  The next
* is the application level, then the inst level and finally the user level. Each
* level overrides the previous' variables.
*
* INPUTS
*	string $var	value to get config data for
*	string $default	default value for $var
*	array $skip	values to skip in configuration
* RETURN
*	string	config value for $var
*/
function configGet($var,$default='',$skip=array()) {
    # clean up what they passed in
    $var = strtolower(trim($var));

    # Allow a programmer to override any option
    # by setting an application global
    if( ($ag=vgaGet($var,''))<>'' ) return $ag;

    # Define the arrays and then attempt to load them
    $configuser = $configinst = $configapp = $configfw = array();
    $alist = array('configfw','configapp','configinst');
    foreach($alist as $table_id) {
        $file = fsDirTop()."/dynamic/table_$table_id.php";
        if(file_exists($file)) {
            include($file);
        }
    }

    #hprint_r($configinst);

    # a special case is the user prefs, look for users's file
    $uid = SessionGet("UID");
    $file = fsDirTop()."/dynamic/table_configuser_$uid.php";
    if(file_exists($file)) {
        include($file);
    }

    # Now proceed to the first value we find
    if(!in_array('user',$skip)) {
        if( a($configuser,$var,'*null*')<>'*null*') return $configuser[$var];
    }
    if(!in_array('inst',$skip)) {
        if( a($configinst,$var,'*null*')<>'*null*') return $configinst[$var];
    }
    if(!in_array('app',$skip)) {
        if( a($configapp ,$var,'*null*')<>'*null*') return $configapp[$var];
    }
    if( a($configfw  ,$var,'*null*')<>'*null*') return $configfw[$var];
    return $default;
}

function configWrite($type) {
    # DUPLICATE CODE: THIS CODE IS DUPLICATE IN ANDROBUILD.PHP

    # First work out some differences based on which table
    if($type=='inst') {
        $table_id = 'configinst';
        $file  = fsDirTop().'/dynamic/table_configinst.php';
    }
    elseif($type=='app') {
        $table_id = 'configapp';
        $file  = fsDirTop().'/dynamic/table_configapp.php';
    }
    else {
        $table_id = 'configuser';
        $uid = SessionGet('UID');
        $file  = fsDirTop()."/dynamic/table_configuser_$uid.php";
    }

    # Retrieve the data
    $dd = ddTable($table_id);
    $data= SQL_OneRow("Select * From ".$dd['viewname']);

    # Write the array and save it
    $text ="<?php\n\$$table_id = array(";
    $docomma=false;
    $nocols = array('_agg','skey','skey_quiet','recnum');
    foreach($data as $column_id=>$value) {
        if(in_array($column_id,$nocols)) continue;
        if(is_null($value)) $value='*null*';
        $text.="\n    ";
        if($docomma) $text.=",";
        $docomma = true;
        # KFD 2/17/09 Sourceforge 2591306
        #             See also androBuild, code is duplicated.
        $value = str_replace("'","\'",$value);
        $text.="'$column_id'=>'$value'";
    }
    $text.="\n);\n?>";
    file_put_contents($file,$text);
}

function configLayoutX4($container,$type) {
    # get row saved on disk
    if($type=='Framework') {
        $configfw = array();
        $file = fsDirTop()."/dynamic/table_configfw.php";
        if(file_exists($file)) include($file);
        $row = $configfw;
    }
    else {
        $configapp = array();
        $file = fsDirTop()."/dynamic/table_configapp.php";
        if(file_exists($file)) include($file);
        $row = $configapp;
    }

    $top = $container;
    html('h1',$top,"$type Configuration");

    $text = "<p>Andromeda configuration settings are defined at four
    levels:</p>
    <ul>
    <li>Framework - default setting provided by Andromeda, these cannot
             be directly changed.
    <li>Application - default setting provided by the programmer, which can
              override framework settings.
    <li>Instance - Anything specified at this level overrides the application
             and framework settings.
    <li>User - Users can override some configuration settings to
               establish their own preferences.  User settings override
               all other levels.
    </ul>";
    html('div',$top,$text);
    $top->br();

    # We need the data dictionary for configapp for captions
    # This will have more columns that the framework table, that's
    # why use this instead of configfw
    $dd = ddTable('configapp');

    # set up the table
    $table = html('table',$top);
    $table->hp['id'] = 'x2data1';
    $thead = html('thead',$table);
    $tr = html('tr',$thead);
    $td = html('th',$tr,'Setting');
    $td = html('th',$tr,'Value');

    # spit out the values
    $tbody = html('tbody',$table);
    foreach($dd['flat'] as $column_id=>$colinfo) {
        $column_id = trim($column_id);
        if(!isset($dd['flat'][$column_id])) continue;
        if(!isset($row[$column_id])) continue;

        $tr = html('tr',$tbody);
        $td = html('td',$tr,$dd['flat'][$column_id]['description']);
        $td->hp['style'] = 'text-align: left';

        $td = html('td',$tr,htmlEntities($row[$column_id]));
    }
}

# ==============================================================
#
# SECTION: Generate plaintext business reports
#
# ==============================================================
class androText {
    var $pages = array();
    var $topMargin =  6;
    var $leftMargin=  5;
    var $cpi       = 10;
    var $cpl       = 85;
    var $lpp       = 66;
    
    function androText($topMargin=6,$leftMargin=5,$cpi=10) {
        $this->topMargin = $topMargin;
        $this->leftMargin= $leftMargin;
        $this->cpi       = $cpi;
        
        $this->cpl       = ($this->cpi * 8.5) - $this->leftMargin;
    }
    
    function newPage() {
        $this->pages[] = array();
    }
    
    function box($line,$position,$text,$orientation='L') {
        # Adjust for margins
        $line    += $this->topMargin;
        if($orientation=='R' && $position==0) {
            $position = $this->cpl;
        }
        else {
            $position+= $this->leftMargin;
        }
        
        # Always add a page if there is not one, then fetch
        # the page number
        if(count($this->pages)==0) {
            $this->newPage();
        }
        $page = count($this->pages)-1;
        
        # Create the line if it is not there, retrieve it
        if(!isset($this->pages[$page][$line])) {
            $lineLength = $this->cpl - $this->leftMargin;
            $this->pages[$page][$line] = str_repeat(' ',$lineLength);
        }
        $lineText = $this->pages[$page][$line];
        
        # If centered, work out the position and then fake
        # it as a left-oriented.
        if($orientation == 'C') {
            $position   = intval( ($this->cpl - strlen($text))/2 );
            $orientation='L';
        }
        
        # The only real switch is on orientation.  Otherwise
        # we are doing straight string substitution
        if($orientation=='L') {
            $lineText = substr($lineText,0,$position-1)
                .$text
                .substr($lineText, ($position+strlen($text))-1);
        }
        else {
            if($position==0) $position = $this->cpl;
            $lineText = substr($lineText,0,$position - strlen($text))
                .$text
                .substr($lineText,$position);
        }
        $this->pages[$page][$line] = $lineText;
        
    }
    
    function renderAsText() {
        $text = '';
        foreach($this->pages as $pagelines) {
            for($x=1;$x<=66;$x++) {
                if(isset($pagelines[$x])) {
                    $text.=$pagelines[$x];
                }
                else {
                    $text.=str_repeat(' ',$this->cpl);
                }
                $text.="\n";
            }
        }
        return $text;
    }
}


# ==============================================================
#
# SECTION: x6skin CSS Stuff
#
# ==============================================================
function x6CSS() {
    return isset($GLOBALS['AG']['x6skin']);
}
function x6CSSDefine($key,$default='') {
    if(!x6CSS()) return $default;
    global $AG;
    $retval= arr($AG['x6skin']['defines'],$key,$default);
    return str_replace('px','',$retval);
}

function x6CSSRule($selector,$rule,$default='') {
    if(!x6CSS()) return $default;
    
    $skin = $GLOBALS['AG']['x6skin']['css'];
    if(!isset($skin[$selector])) return $default;
    $retval = arr($skin[$selector],$rule,$default);
    return str_replace('px','',$retval);
}

function x6cssRuleSize($selector,$rule,$default=0) {
    if(!x6CSS()) return $default;
    
    $skin = $GLOBALS['AG']['x6skin']['css'];
    if(!isset($skin[$selector])) return $default;
    $retval = arr($skin[$selector],$rule,$default);
    $arv = explode(' ',$retval);
    foreach($arv as $arv1) {
        if(strpos($arv1,'px')!==false) {
            return str_replace('px','',$arv1);
        }
    }
    return $default;
}

function x6cssHeight($element) {
    $defaultLH = x6cssDefine('lh0',0);
    $height = x6CSSRuleSize($element,'line-height'   ,$defaultLH);
    $height+= x6CSSRuleSize($element,'border-top'    ,0);
    $height+= x6CSSRuleSize($element,'border-bottom' ,0);
    $height+= x6CSSRuleSize($element,'padding-top'   ,0);
    $height+= x6CSSRuleSize($element,'padding-bottom',0);
    $height+= x6CSSRuleSize($element,'margin-top'    ,0);
    $height+= x6CSSRuleSize($element,'margin-bottom' ,0);
    /*
    echo "<br/>".$element;
    echo "<br/>".x6CSSRuleSize($element,'line-height'   ,$defaultLH);
    echo "<br/>".x6CSSRuleSize($element,'border-top'    ,0);
    echo "<br/>".x6CSSRuleSize($element,'border-bottom' ,0);
    echo "<br/>".x6CSSRuleSize($element,'padding-top'   ,0);
    echo "<br/>".x6CSSRuleSize($element,'padding-bottom',0);
    echo "<br/>".x6CSSRuleSize($element,'margin-top'    ,0);
    echo "<br/>".x6CSSRuleSize($element,'margin-bottom' ,0);
    */
    return $height;
}

function x6cssHeightLessH1() {
    $retval = x6cssDefine('insideheight');
    $retval-= (x6cssHeight('h1')*2);
    return $retval;
}

# ==============================================================
#
# SECTION: All SQL Generation
#
# ==============================================================
/*h* PHP API/SQL-Generation
*
* NAME
*	SQL Generation
*
* FUNCTION
*	The SQL Generation functions create and sanitize SQL code for use in
*	SQL queries.
*
******
*/


/*f* SQL-Generation/SQL_FORMAT
*
* NAME
*    SQL_FORMAT
*
* FUNCTION
*	The PHP function SQL_FORMAT Takes any input value and type and formats
*   it for direct substitution
* 	into a SQL string.  So for instance character values are escaped for
* 	quotes and then surrounded by single quotes.  Numerics are returned
* 	as-is, dates are formatted and so forth.
*
*	The optional third parameter specifies a maximum length for character
* 	and varchar fields.  If it is non-zero, the value will be clipped to
* 	that length.
*
* 	If you use this command for every value received from the browser when
* 	you build SQL queries, then your code will be safe from SQL Injection
* 	attacks.  All framework commands that build queries use this command for
* 	all literals provided to them.
*
* INPUTS
*	string $t - Type_ID
*	mixed $v - any value
*	int $clip - clip length
*
* RETURN VALUE
*	string - sql formatted string
*
******/
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
    case 'mime-f':
        if($clip>0 && strlen($v) > $clip) $v = substr($v,0,$clip);
        // KFD 9/10/07, one of the doctors wants all caps
        if(configGet('ALLCAPS')=='Y') {
            $v= strtoupper($v);
        }
        return "'".SQL_ESCAPE_STRING($v)."'";
        break;
    case "mime-h-f":
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
			if ($v=="" || $v=='.') { return "0"; }
            else { return SQL_ESCAPE_STRING(trim($v)); }
		case "rtime":
		case "time":
			# KFD 7/8/08, if they returned a jquery time,
            # convert it to minutes since midnight
            if(strpos($v,'M')!==false) {
                list($time,$ampm) = explode(" ",$v);
                list($hours,$mins)= explode(":",$time);
                if($ampm=='PM' && $hours<>12) $hours+=12;
                if($ampm=='AM' && $hours==12) $hours = 0;
                return "'".(($hours*60)+$mins)."'";
            }
            else {
                return "'$v'";
            }
         if($v=='') return 'null';
         return $v;
			//$arr = explode(":",$v);
			//return ($arr[0]*60) + $arr[1];
	}
}

/*f* SQL-Generation/SQLFC
*
* NAME
*    SQLFC
*
* FUNCTION
*	The PHP function SQLFC is a shortcut to SQL_FORMAT for string values.
*
* INPUTS
*	string $value - string to be sanitized
*
* RETURN VALUE
*	string - sanitized string
*
* SOURCE
*/
function SQLFC($value) { return SQL_Format('char',$value); }
/******/

/*f* SQL-Generation/SQLFN
*
* NAME
*    SQLFN
*
* FUNCTION
*	The PHP function SQLFN is a shortcut to SQL_FORMAT for numeric values.
*
* INPUTS
*	mixed $value - value to be sanitized
*
* RETURN VALUE
*	string - sanitized string
*
* SOURCE
*/
function SQLFN($value) { return SQL_Format('numb',$value); }
/******/

/*f* SQL-Generation/SQLFD
*
* NAME
*    SQLFD
*
* FUNCTION
*	The PHP function SQLFD is a shortcut to SQL_FORMAT for date values.
*
* INPUTS
*	mixed $value - value to be sanitized
*
* RETURN VALUE
*	string - sanitized string
*
* SOURCE
*/
function SQLFD($value) { return SQL_Format('date',$value); }
/******/

/*f* SQL-Generation/SQLFDT
*
* NAME
*    SQLFDT
*
* FUNCTION
*	The PHP function SQLFDT is a shortcut to SQL_FORMAT for datetime values.
*
* INPUTS
*	mixed $value - value to be sanitized
*
* RETURN VALUE
*	string - sanitized string
*
* SOURCE
*/
function SQLFDT($value) { return SQL_Format('dtime',$value); }
/******/

/*f* SQL-Generation/sqlFilter
*
* NAME
*    sqlFilter
*
* FUNCTION
*	The PHP function sqlFilter generates a WHERE clause for a single column given its
* 	type and the search value.  Generates index-optimized
*	WHERE clauses to replace LIKE where possible, respects
*	commas to do lists, and double-dashes to do ranges.
* 	Respects the partial date values of m/d, m/yyyy, and
*	yyyy.
*
* INPUTS
*	array $colinfo - data dictionary column information
*	string $tvc - version of the value
*	string $table - Table Id for data dictionary
*
* RETURN VALUE
*	string - WHERE clause
*
******/
function sqlFilter($colinfo,$tcv,$table = '') {
    $type_id  = $colinfo['type_id'];
    $column_id= $colinfo['column_id'];
    $c        = $column_id;
    if($table<>'') {
        $c = "$table.$column_id";
        $table = "$table.";
    }

    # If the value is an asterisk, return an unconditional true
    if($tcv=='*') return '1=1';

    # Determine if we will use dashes
    $ignore_dashes = a($colinfo,'uisearch_ignore_dash','N');

    # Get the dash operator
    $dash = trim(configGet('uisearchdash','-'));
    x4Debug("dash is ".$dash);

    # Step one is to split on commas and handle each
    # value separately, then at bottom we recombine
    $values = explode(',',$tcv);
    $sql_new=array();
    foreach($values as $tcv) {
        $new = '';
        # This switch statement reproduces a lot of code for different
        # types, with small changes.  I decided to do it this way instead
        # of generalizing it because it will be easier to add type-specific
        # details going forward.
        switch($type_id) {
        case 'char':
        case 'vchar':
        case 'text':
        case 'ph12':
        case 'ssn':
        case 'cbool':
            if(substr($tcv,0,1)=='>' || substr($tcv,0,1)=='<') {
                $tcv = str_replace('%','',$tcv);
                if(strlen($tcv)>1) {
                    $new=$c.substr($tcv,0,1).SQLFC(substr($tcv,1));
                }
            }
            elseif(strpos($tcv,$dash)!==false && $ignore_dashes <>'Y' ) {
                list($beg,$end)=explode($dash,$tcv);
                if(strlen($beg)>0 && strlen($end)>0) {
                    $sbeg = SQLFC(trim(str_replace('%','',$beg)));
                    $send = SQLFC(trim(str_replace('%','',$end)).'z');
                    # Don't use between.  This allows a-a to still work
                    $new="$c >= $sbeg AND $c <= $send";
                }
            }
            elseif( strpos($tcv,'%')!==false) {
                # user has requested wildcard, cannot avoid a like
                $sval = SQLFC(trim($tcv));
                $new = "LOWER($c) LIKE LOWER($sval)";
            }
            else {
                if(strlen(trim($tcv))>0) {
                    if(gp('x6exactPre',false)) {
                        $new = "LOWER($c) = LOWER(".SQLFC(trim($tcv)).")";
                    }
                    else {
                        $sbeg = SQLFC(trim($tcv));
                        $send = SQLFC(trim($tcv).'z');
                        # The greater-equal allows us to avoid a like
                        # and make use of indexes for much faster performance
                        $new = "(LOWER($c) >= LOWER($sbeg)"
                            ." AND LOWER($c) < LOWER($send))";
                    }
                }
            }
            break;
        case 'dtime':
        case 'date':
            x4Debug($tcv);
            x4Debug(strtotime($tcv));
            if(substr($tcv,0,1)=='>' || substr($tcv,0,1)=='<') {
                $operator = substr($tcv,0,1);
                $tcv      = substr($tcv,1);
                if(strtotime($tcv)) {
                    $tcv = str_replace('%','',$tcv);
                    $tcv = SQLFD($tcv);
                    $new = "$c $operator $tcv";
                }
            }
            elseif(strpos($tcv,$dash)!==false && $ignore_dashes <>'Y' ) {
                list($beg,$end)=explode($dash,$tcv);
                if(strtotime($beg) && strtotime($end)) {
                    $sbeg = SQLFD(trim(str_replace('%','',$beg)));
                    $send = SQLFD(trim(str_replace('%','',$end)));
                    $new = "$c between $sbeg and $send";
                }
            }
            else {
                $pieces = explode('/',$tcv);
                if(count($pieces) == 1) {
                    if(strlen($pieces[0])==4) {
                        $new = "EXTRACT(YEAR FROM $c::timestamp)="
                            .SQLFN($pieces[0]);
                    }
                }
                else if(count($pieces)==2) {
                    if(strlen($pieces[1])==4) {
                        $new = "EXTRACT(MONTH FROM $c::timestamp)="
                            .SQLFN($pieces[0])
                            ." AND EXTRACT(YEAR FROM $c::timestamp)="
                            .SQLFN($pieces[1]);
                    }
                    else if(strlen($pieces[1])<3 && strlen($pieces[1])>0) {
                        $new = "EXTRACT(MONTH FROM $c::timestamp)="
                            .SQLFN($pieces[0])
                            ." AND EXTRACT(DAY FROM $c::timestamp)="
                            .SQLFN($pieces[1]);
                    }
                }
                else if(strtotime($tcv)) {
                    $tcv = str_replace('%','',$tcv);
                    $tcv = SQLFD($tcv);
                    $new = "$c = $tcv";
                }
            }
            break;
        case 'time':
        case 'int':
        case 'numb':
            if(substr($tcv,0,1)=='>' || substr($tcv,0,1)=='<') {
                $tcv = str_replace('%','',$tcv);
                if(strlen($tcv)>1) {
                    $new=$c.substr($tcv,0,1)
                        .SQLFN(floatval(substr($tcv,1)));
                }
            }
            elseif(strpos($tcv,$dash)!==false && $ignore_dashes <>'Y' ) {
                list($beg,$end)=explode($dash,$tcv);
                if(strlen($beg)>0 && strlen($end)>0) {
                    $sbeg = SQLFN(floatval(str_replace('%','',$beg)));
                    $send = SQLFN(floatval(str_replace('%','',$end)));
                    # Don't use between.  This allows a-a to still work
                    $new="$c >= $sbeg AND $c <= $send";
                }
            }
            elseif( strpos($tcv,'%')!==false) {
                # user has requested wildcard, cannot avoid a like
                $sval = SQLFC(floatval($tcv));
                $new = "$table$column_id::varchar LIKE $sval";
            }
            else {
                if(strlen(trim($tcv))>0) {
                    $sval = SQLFN(floatval($tcv));
                    $new = "$c = $sval";
                }
            }
            break;
        }

        # now add the new value into the list of clauses
        if(strlen($new) > 0) {
            $sql_new[] = $new;
        }
    }

    # If there are no search criteria, do nothing.  The
    # calling program must interpret this and avoid a search.
    if(count($sql_new)>0) {
        return implode("\n        OR ",$sql_new);
    }
    else {
        return '';
    }
}

/*f* SQL-Generation/sqlOrderBy
*
* NAME
*    sqlOrderBy
*
* FUNCTION
*	The PHP function sqlOrderBy builds the orderby part of an sql query.  If the Get/Post parameters have the
*	type of sort, it uses the type of sort passed.  Otherwise, it orders the columns in ascending order.
*
* INPUTS
*	array $vals - columns
*
* RETURN VALUE
*	string - ORDER BY clause
*
******/
function sqlOrderBy($vals) {
    # First see if an explicit sortAD and sortCol were passed
    if(gpExists('sortCol')) {
        if(gp('sortAsc',false)) {
            $ad = gp('sortAsc')=='true' ? ' ASC' : ' DESC';
        }
        else {
            $ad = gp('sortAD');
        }
        return array(gp('sortCol').' '.$ad);
    }

    # If not, order by the columns that were passed in with values
    $aorder = array();
    foreach($vals as $column_id=>$val) {
        if($val<>'') {
            $aorder[] = "$column_id ASC";
        }
    }

    return $aorder;
}

// ==================================================================
// Joomla Compatibility Functions
// ==================================================================
/*h* PHP API/Joomla-Compatibility
*
* NAME
*	Joomla Compatibility
*
* FUNCTION
*	The Joomla Compatibility framework allows 'drop-in' use of Joomla
*	templates for an Andromeda Application.
*
*
*	To use a Joomla template, you must do the following:
*
* 	Call [[JoomlaCompatibility()]] from applib
*	Create a 'templates' directory and put your template files there
*
******
*/

/*f* Joomla-CompatibilityJoomlaCompatibility
*
* NAME
*	JoomlaCompatibility
*
* FUNCTION
*	This function generates objects, variables and defines that
*	satisfy a Joomla template so that it will execute and serve up
*	Andromeda content.
*
*	The first parameter is the name of the template to use.  The template
*	files should be in a subdirectory of your app's "templates" directory,
*	and that subdirectory should have the same name as the template.
*
*	The second parameter, which defaults to blank,
*	is assigned to $GLOBALS['template_color'].
*
*	Other actions of this program are:
*
*	- defines constant _VALID_MOS as true
*	- defines constant _ISO as empty
*	- assigns the application's root directory to global
*	  variable $mosConfig_absolute_path.
*	- assigns an empty string to global variable $mosConfig_live_site.
*	- creates empty global $my object with property 'id' set to false
*	- creates empty global $mainframe object, whose getTemplate() method always
*	  returns the template name.
*
*	The universal dispatcher, [[index_hidden]], looks for the defined constant
*	_VALID_MOS, and if found it uses the named Joomla template instead of an
*	Andromeda template.  It also exposes the necessary global variables
*	that were defined above.
*
*	The compatibility layer provides a handful of functions to emulate the
*	functions used by Joomla.  The most important function is [[mosMainBody]],
*	which calls directly to [[ehStandardContent]].  The other functions tend
*	toward being more placeholders.
*
*	When you use a Joomla template, there are a handful of tasks that must
*	be performed:
*
*	- Insert a link to the Andromeda javascript library, raxlib.js into
*	  the template.
*	- Code up routine appCountModules, which handles calls to Joomla
*	  function [[mosCountModules]].
*	- Code up routine appShowModules, which handles calls to Joomla
*	  function [[mosLoadModules]].
*	- Identify the template's CSS classes for menu modules and menu items,
*	  and assign them in [[applib]] using [[vgaSet]] to 'MENU_CLASS_MODL' and
*	  'MENU_CLASS_ITEM'.
*	- Look for any hard-coded configuration parameters that you want to
*	  override and REM them out.
*	- Copy the x2.css file from andro/clib into the template's CSS
*	  directory, and link to it from the template main file.
*
* INPUTS
*	string Template_Name
*	string Template_Color
*
******
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
      $GLOBALS['J']['mC_live_site']     = $tpi;
   }

   $GLOBALS['J']['template_color']   = $template_color;
}

/****c* Joomla-Compatibility/joomla_fake
*
* NAME
*	joomla_fake
*
* FUNCTION
*	Class needed by Joomla templates so they go into
*	normal mode.
*
* SOURCE
*/
class joomla_fake {
   var $id=false;
   var $template_name='';
   // KFD 2/25/08 added for
   var $_session = array();

   function getTemplate() {
      return $this->template_name;
   }
}
/******/

/**
* This is an empty routine that returns an empty string.
*
* INPUTS
*/
function mosShowHead() {  return ''; }

/*f* Joomla-CompatibilitymosCountModules
*
* NAME
*	mosCountModules
*
* FUNCTION
*	Looks for the function [[appCountModules]] to exist.  If that routine
* 	exists, it is called and the result is returned.  If that method does not
*	exist, always returns false.
*	Define and code the method [[appCountModules]] in your [[applib.php]] file.
*
* INPUTS
*	string $name	parameter to pass to the appCountModules if it exists
*
* RETURN
*	mixed		returns result from appCountmodules if it exists.  If not, checks for
*			tmpCountModules.  If that doesn't exist also, returns true.
*
******
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

/*f* Joomla-CompatibilitymosLoadModules
*
* NAME
*	mosLoadModules
*
* FUNCTION
*	Looks for the function [[appLoadModules]] to exist.  If that routine
*	exists, it is called.  That routine is expected to echo its output
*	directly.  If that routine does not exist, nothing happens.
*	Define and code the method [[appLoadModules]] in your [[applib.php]] file.
*
*	One handy way to explore a template is to code [[appLoadModules]] so that
*	it simply echoes the name of the module, that way the template will appear
*	with all of the module areas displaying their names.
*
* INPUTS
*	string $name	name to pass to mosLoadModules
*	number $arg1	argument to pass to mosLoadModules
*
*******
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

/*f* Joomla-CompatibilitymosPathWay
*
* NAME
*	mosPathWay
*
* FUNCTION
*	Returns an empty string.
*
*	In a joomla site, this would return the navigation hierarchy, which
*	Andromeda does not currently provide.

* RETURN
*	string	empty string
*
******
*/
function mosPathWay()  {
   //echo "mosPathway";
}

/*f* Joomla-CompatibilitymosMainBody
*
* NAME
*	mosMainBody
*
* FUNCTION
*	echos [[ehStandardContent]].
*
*******
*/
function mosMainBody() {
  ehStandardContent();
}

/*f* Joomla-CompatibilitytmpPathInsert
*
* NAME
*	tmpPathInsert
*
* FUNCTION
*	The PHP function tmpPathInsert
*	makes it possible to use friendly URL's together with
*	absolute paths in the special case where your files are stored in
*	a user's home directory on a local machine.
*
*	The function is only called in templates, and is always called inside
*	of links to CSS and JS files.
*
*	The function is actually pulling the value "localhost_suffix" from the
*	application's web_path.
*
******
*/
function tmpPathInsert() {
   // DO Removed 9-5-2008 scriptPath() is more reliable and works with Friendly URL's
   //return vgfGet("tmpPathInsert");
   return scriptPath();
}

/*f* PHP API/scriptPath
*
* NAME
*	scriptPath
*
* FUNCTION
*	The PHP function scriptpath
*	makes it possible to use friendly URL's together with
*	absolute paths in the special case where your files are stored in
*	a user's home directory on a local machine.
*
*
******/

function scriptPath() {
    $path = $_SERVER['SCRIPT_NAME'];
    $path = str_replace( "index.php", "", $path );
    $path = str_replace( "//", "/", $path );
    if ( substr( $path, 0, 1 ) != '/' ) {
        $path = '/' .$path;
    }
    if (substr( $path, -1 ) != '/' ) {
        $path = $path .'/';
    }
    $path = str_replace( "//", "/", $path );
    return $path;
}

/*f* Joomla-CompatibilityampReplace
*
* NAME
*	ampReplace
*
* FUNCTION
*	This routine exists in the Rocket Theme splitmenu code, and is
*	presumably a Joomla library routine.
*
* INPUTS
*	string $input	url
*
* RETURN
*	string URL	url without &
******
*/
function ampReplace($input) {
   return str_replace("&","&amp;",$input);
}

/*f* Joomla-CompatibilitysefRelToAbs
*
* NAME
*	sefRelToAbs
*
* FUNCTION
*	The PHP function sefRelToAbs takes a SEF url and converts to an an absolute
*	path.
*
* INPUTS
*	string $input - SEF url
*
* RETURN VALUE
*	string - absolute path
*
* SOURCE
*/
function sefRelToAbs($input) {
   return tmpPathInsert().$input;
}
/******/

/*f* Joomla-CompatibilityfwModuleMenuRight
*
* NAME
*	fwModuleMenuRight
*
* FUNCTION
*	Load right-sided menu modules.  This code is coupled to
*	the rt_pixel template.  When we want to use it in another
*	template it should be generalized to only return the links
*	and the individual template should render it into HTML.
*
******
*/
function fwModuleMenuRight() {
    if(!LoggedIn()) return;
    $extra = '';
    # A few x4 options
    if(configGet('x4Welcome','N')=='Y') {
        # If help text exists, put a link to that
        if(vgfGet('htmlHelp')<>'') {
            $extra.='<li><a href="javascript:void(0)" onclick="x4.help()">Help</a></li>';
        }

        #  if they asked for a direct link to menu
        if(ConfigGet('x4padmenu','N')=='Y') {
            $extra.='<li><a href="?x4Page=menu">Menu</a></li>';
        }
    }
    ?>
    <ul class='right'>
        <?php echo $extra?>
        <li><a href='?st2logout=1'>Logout <?php echo SessionGet('UID')?></a></li>
    </ul>
    <?php
    return false;
}


# ==============================================================
#
# SECTION: DEPRECATED BUT IN USE
#
# Routines I want to get rid of but the framework is
# using them.
# ==============================================================
/**
* @deprecated
*/
function hLinkPostFromArray($class,$caption,$parms,$hExtra='') {
   $hclass = hTagParm("class",$class);
   $hparms=http_build_query($parms);
   return "<a ".$hExtra." $hclass href=\"javascript:formPostString('$hparms');\">"
      .$caption."</a>";
}

/**
* @deprecated
*/
function ListDelim($input,$suffix=",") {
	if ($input=="") return ""; else return $suffix;
}

/**
* @deprecated
*/
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

function loadYaml($filename) {
    include_once("spyc.php");
    $parser = new Spyc;
    $temparray = $parser->load($filename);
    return array($temparray,$parser->errors);    
}
function removeYamlLineNumbers(&$yaml) {
    foreach($yaml as $key=>$value) {
        if($key=='__yaml_line') {
            unset($yaml[$key]);
        }
        elseif(is_array($value)) {
            removeYamlLineNumbers($yaml[$key]);
        }
    }
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






// ------------------------------------------------------------------
/****h* PHP API/Session Handling
*
* NAME
*	Session Handling
*
* FUNCTION
*	Andromeda provides wrappers for accessing session variables.  The
*	PHP superglobal $_SESSION should not be directly accessed, instead
*	an Andromeda program should use [[SessionGet]] and [[SessionSet]].
*
*	Do not use session variables for storing information across different
*	requests, such as storing user replies going page-to-page through
*	a wizard.  Use [[Context Functions]] or [[Hidden Variables]]
*	for these instead, they are much more flexible and robust.
*
*	It may happen that you have multiple Andromeda applications on a server,
*	and that a browser is connected to more than one of them in multiple
*	tabls.  This would result in a collision if you were access $_SESSION
*	directly, because each app would overwrite the variables of the others.
*	Andromeda prevents these collisions automatically whenever
*	[[SessionGet]] and [[SessionSet]] are used.
*
*	Andromeda also prevents collissions between session variables used by
*	the framework and those you may put into your application.  All of the
*	Session variables accept an optional last parameter (not documented in
*	the individual functions)  The default value of this parameter is
*	'app', but some framework functions call it with a value of 'fw' to keep
*	these variables separate from application variables.  It should be noted
*	that sometimes the framework uses application session variables, so that
*	the application can find them if necessary.  Examples of this are session
*	variables UID (current user_id) and PWD (password of current user).
*
******/

/****f* Session Handling/SessionGet
*
* NAME
*	SessionGet
*
* FUNCTION
* 	The PHP function Session get returns a session variable.  The second parameter
* 	is a Standard Default Value and will be returned if the
* 	Session variable Var_Name does not exist.
*
* 	The framework itself tracks only 2 session variables.  These are UID, which
* 	is user_id, and PWD, which is user password.  An application must be
* 	careful not to overwrite those values, as the framework will make no
* 	provision to prevent such an accident.
*
* INPUTS
*	string $key	key for the value to receive from $_SESSION
*	mixed $default	default value for key
*
* RETURN VALUE
*	mixed		value associated with $key
*
* SOURCE
*/
function SessionGet($key,$default="",$sfx='app') {
   $xkey=$GLOBALS["AG"]["application"]."_".$sfx."_".$key;
	if (isset($_SESSION[$xkey])) {
		return $_SESSION[$xkey];
	}
	else return $default;
}
/******/

/****f* Session Handling/SessionSet
*
* NAME
*	SessionSet
*
* FUNCTION
*	This program sets a session variable.  The variable will exist
*	as long as the PHP session is alive.

*	The framework tracks only 2 session variables.  These are UID, which
*	is user_id, and PWD, which is user password.  An application must be
*	careful not to overwrite those values, as the framework will make no
*	provision to prevent such an accident.
*
* INPUTS
*	string $key - key to set
*	mixed $value -	value to set to $key
*
* SOURCE
*/
function SessionSet($key,$value,$sfx='app') {
   $xkey=$GLOBALS["AG"]["application"]."_".$sfx."_".$key;
	$_SESSION[$xkey] = $value;
}
/******/


/****f* Session Handling/SessionUnSet
*
* NAME
*	SessionUnSet
*
* FUNCTION
*	Destroys the named session variable.
*
*	The framework tracks only 2 session variables.  These are UID, which
*	is user_id, and PWD, which is user password.  An application should
*	never call SessionUnSet on these variables.
*
* INPUTS
*	string $key - variable to destroy
*
* SOURCE
*/
function SessionUnSet($key,$context='app',$sfx='app') {
   $x=$context;
   $xkey=$GLOBALS["AG"]["application"]."_".$sfx."_".$key;
	unset($_SESSION[$xkey]);
}
/******/

/****f* Session Handling/SessionReset
*
* NAME
*	SessionReset
*
* FUNCTION
*	Destroys all session variables for the current application.  We use this
*	instead of PHP session_destroy because it allows a user to be logged in
*	to several apps at once, because the framework makes effective sessions
*	for each separate application.
*
*	Note that this function destroys both application and framework session
*	variables, there is more information on what these are on the
*	[[Session Variables]] page.
******/
function SessionReset() {
   global $AG;
   foreach($_SESSION as $key=>$value) {
      $app = $AG['application'].'_';
      if (substr($key,0,strlen($app))==$app) {
         unset($_SESSION[$key]);
      }
   }
}

/****f* Session Handling/SessionUnSet_Prefix
*
* NAME
*	SessionUnSet_Prefix
*
* FUNCTION
*	DEPRECATED
******/
function SessionUnSet_Prefix($prefix) {
	$prefix = $GLOBALS["AG"]["application"]."_".$prefix;
	foreach ($_SESSION as $key=>$value) {
		if (substr($key,0,strlen($prefix))==$prefix) {
			unset($_SESSION[$key]);
		}
	}
}
# ==================================================================
#
# SECTION: GLOBAL VARIABLES
#
# ==================================================================
/****h* PHP API/Global Variables
*
* NAME
*    Global_Variables
*
* FUNCTION
*  Andromeda provides some wrapper that allow you to set and
*  retrieve PHP global variables without risking a collission with
*  framework global variables.
*
*  Use PHP API/vgaSet to set a variable, and PHP API/vgaGet
*  to retrieve a variable.
*
*  The framework uses the corresponding functions PHP API/vgfSet
*  and PHP API/vgfGet.
*
******
*/

/****f* Global Variables/vgaGet
*
* NAME
*	vgaGet
*
* FUNCTION
*	The PHP function vgaGet is a wrapper for getting PHP global variables.  You call vgaGet in order
*	to receive global variables set without conflicting with the framework.  You can also provide
*	a default value to return in the case that the global variable isn't found or isn't set.
*
* INPUTS
*	string $key - name of variable
*	mixed $default - default variable for variable
*
* RETURN VALUE
*	mixed - value for variable
*
* SOURCE
*/
function vgaGet($key,$default='') {
   return isset($GLOBALS['appdata'][$key])
      ? $GLOBALS['appdata'][$key]
      : $default;
}
/******/

/****f* Global Variables/vgaSet
*
* NAME
*    vgaSet
*
* FUNCTION
*    The PHP function vgaSet sets the value of a global variable.
*    The variable will exist during the current request and can be
*    accessed from any scope with the vgaGet function.
*
*    Use vgaGet and vgaSet to store and retrieve global
*    variables without worrying about naming collisions with the framework.
*
* INPUTS
*   string - the Variable Name
*   mixed - output
*
* SOURCE
*/
function vgaSet($key,$value='') {
   $GLOBALS['appdata'][$key]=$value;
}
/******/

/****f* Global Variables/vgfGet
*
* NAME
*	vgfGet
*
* FUNCTION
*	The PHP function vgfGet returns a [[Global Variable]].  The second parameter
*	names a [[Standard Default Value]] that will be returned if the
*	requested variable does not eixst.
*
*	The framework uses [[vgfGet]] and [[vgfSet]] to store and retrieve global
*	variables without worrying about naming collisions with an application.
*
* INPUTS
*	string $key	- key for the value you want to receive from the global variables
*	string $default - default value for the key
*
* RETURN VALUE
*	mixed - value for the key
******
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

/****f* Global Variables/vgfSet
*
* NAME
*	vgfSet
*
* FUNCTION
*	The PHP function vgfSet sets the value of a global variable.
*	The variable will exist during the current request and can be
*	accessed from any scope with the [[vgfGet]] function.
*
*	The framework uses [[vgfGet]] and [[vgfSet]] to store and retrieve global
*	variables without worrying about naming collisions with the framework.
*
* INPUTS
*	string $key - key to set into globals
*	mixed $value - value for the key
*
* SOURCE
*/
function vgfSet($key,$value='') {
    //echo $key." - ".$value;
    //$a=xdebug_get_function_stack();
    //hprint_r($a);
    //echo $key." - ".$value."<br/><br/>";
   $GLOBALS['fwdata'][$key]=$value;
}
/******/
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
  * INPUTS
  *	$stackname string
  */

/*---h* PHP API/Stack-Functions
*
* NAME
*	Stack-Functions
*
* FUNCTION
*	Stack functions are used to handle stacks in andromeda.
*
*---**
*/

/*---f* Stack-Functions/_scStackInit
*
* NAME
*	_scStackInit
*
* FUNCTION
*	The PHP function _scStackInit initializes a stack for scStackPush and scStackPop.
*
* INPUTS
*	string $stackname - name for the stack
*
* SOURCE
*/
function _scStackInit($stackname) {
   if (!isset($GLOBALS['STACK'])) {
      $GLOBALS['STACK']=array();
   }
   if (!isset($GLOBALS['STACK'][$stackname])) {
      $GLOBALS['STACK'][$stackname]=array();
   }
}
/*---**/

/*---f* Stack-Functions/scStackPush
*
* NAME
*	scStackPush
*
* FUNCTION
*	The PHP function scStackPush is used to push a provided value onto the stack with the provided name.
*
* INPUTS
*	string $stackname - name of stack
*	mixed $value - value to push onto stack
*
* SOURCE
*/
function scStackPush($stackname,$value) {
   _scStackInit($stackname);
   $GLOBALS['STACK'][$stackname][] = $value;
}
/*---**/

/*---f* Stack-Functions/scStackPop
*
* NAME
*	scStackPop
*
* FUNCTION
*	The PHP function scStackPop pops the last-added value from a named stack.  Returns null if the stack
*	is empty, an empty stack does not throw an error.
*
* INPUTS
*	string $stackname - name of stack
*
* RETURN
*	mixed - value ontop of the stack
*
* SOURCE
*/
function scStackPop($stackname) {
   _scStackInit($stackname);
   return array_pop($GLOBALS['STACK'][$stackname]);
}
/*---**/
// ------------------------------------------------------------------
// Routines to assemble return values
// ------------------------------------------------------------------
/*---h* PHP API/Ajax-Return-Assembly
*
* NAME
*	Ajax-Return-Assembly
*
* FUNCTION
*	The Ajax-Return-Assembly functions all manage the return data that gets
*	sent back to the browser after an ajax call.  A return is structured by
*	separating elements and their values with '|' characters.  Each return
*	is separated by the '|-|' character.
*
***---
*/

/*---f* Ajax-Return-Assembly/return_value_add
*
* NAME
*	return_value_add
*
* FUNCTION
*	The PHP function return_value_add adds the provided element and its value
*	to the ajax return values.
*
* INPUTS
*	string $element - element name
*	mixed $value - value for element
*
* SOURCE
*/
function return_value_add($element,$value) {
   global $AG;
   $retvals=ArraySafe($AG,'retvals',array());
   $retvals[$element]=$value;
   $GLOBALS['AG']['retvals']=$retvals;
}
/*---**/

/**
* Adds a new return command
*
*	string $command
*	string $element
*	mixed $value
*/

/*---f* Ajax-Return-Assembly/retCmd
*
* NAME
*	retCmd
*
* FUNCTION
*	Adds a new return command with the provided command, element, and value.
*	Shortcut for return_command_add.
*
* INPUTS
*	string $command - command
*	string $element - element name
*	mixed $value - value for element
*
*---**
*/
function retCmd($command,$element,$value) {
   return_command_add($command,$element,$value);
}

/**
* Adds a new return command.
*
*	string $command command
*	string $element element to add
*	mixed $value value of element
*/

/*---f* Ajax-Return-Assembly/return_command_add
*
* NAME
*	return_command_add
*
* FUNCTION
*	The PHP function return_command_add
*	adds a new return command with the provided command, element, and value.
*
* INPUTS
*	string $command - command
*	string $element - element name
*	mixed $value - value for element
*
* SOURCE
*/
function return_command_add($command,$element,$value) {
   global $AG;
   $retcommands=ArraySafe($AG,'retcommands',array());
   $retcommands[$command][$element]=$value;
   $GLOBALS['AG']['retcommands']=$retcommands;
}
/*---**/

/**
* Sends returns to the browser using ajax
*
*/

/*---f* Ajax-Return-Assembly/return_as_ajax
*
* NAME
*	return_as_ajax
*
* FUNCTION
*	The PHP function return_as_ajax sends the returns to the browser using ajax
*
*---**
*/
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
/****h* PHP API/Data Dictinary Routines
*
* NAME
*	Data Dictinary Routines
*
* FUNCTION
*	Data dictionaries are one of the most important aspects of andromeda.  Following the philosophy that
*	data is the most important part of the application, a data dictionary allows for andromeda to
*	generate screens and minimize the amount of code.
*
*	These data dictionary routines are used to handle data dicitonaries in andromeda.  Data dictionaries
*	are stored in separate php files, and are represented by php associative arrays.
*
******/

/*---f* Data Dictinary Routines/DD_EnsureREf
*
* NAME
*	DD_EnsureREf
*
* FUNCTION
*	The PHP function DD_EnsureREf checks to see if the provided reference is a reference to a data dictionary
*	table.  If it is, the function returns the data dictionary because it is valid.  If it isn't, the function
*	only returns the reference.
*
* INPUTS
*	reference $unknown - Reference to a possible data dictionary
*
* RETURN VALUE
*	mixed - Two possibilites.  One, the reference provided is to a data dictionary, so the data dictionary
*			is returns.  Two, the reference is not to a data dictionary, so the reference is returned.
*
* SOURCE
*/
function DD_EnsureREf(&$unknown) {
   if(is_array($unknown)) return $unknown;
   else return dd_TableRef($unknown);
}
/*---**/

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

/****f* Data Dictinary Routines/ddTable
*
* NAME
*	ddTable
*
* FUNCTION
*	The PHP function ddTable returns an associative array completely
*   describing a table, including the table's description, module,
*   and complete details on every column.  The array has been filtered
*   by the current user's security settings, so that columns the user
*   is not allowed to see are removed completely.
*
* INPUTS
*	string $table_id - Id for the data dictionary table you want to receive
*
* RETURN VALUE
*	reference - Returns a reference to the requested data dictionary table
*
*
*
******
*/
function &ddTable($table_id) {
    # Don't repeat all of this work. If this has already
    # been run don't run't it again
    if(is_array($table_id)) {
        $table_id = $table_id['table_id'];
    }
    if(isset($GLOBALS['AG']['tables'][$table_id])) {
        return $GLOBALS['AG']['tables'][$table_id];
    }

    # First run the include and get a reference
    if(!file_exists(fsDirTop()."generated/ddtable_$table_id.php")) {
        $GLOBALS['AG']['tables'][$table_id] = array(
            'flat'=>array()
            ,'description'=>$table_id
            ,'viewname'=>''
        );
    }
    else {
        include_once("ddtable_".$table_id.".php");
    }
    $tabdd = &$GLOBALS['AG']['tables'][$table_id];
    #echo "Here is first load:";
    #hprint_r($tabdd);

    # First action, assign the permissions from the session so
    # they are handy
    if ( SessionGet( 'TABLEPERMSMENU' ) != '' ) {
        $tabdd['perms']['menu']
            = in_array($table_id,SessionGet('TABLEPERMSMENU'));
    } else {
        $tabdd['perms']['menu'] = false;
    }
    if ( SessionGet( 'TABLEPERMSSEL' ) != '' ) {
        $tabdd['perms']['sel']
             = in_array($table_id,SessionGet('TABLEPERMSSEL'));
    } else {
        $tabdd['perms']['sel'] = false;
    }
    if ( SessionGet( 'TABLEPERMSINS' ) != '' ) {
        $tabdd['perms']['ins']
            = in_array($table_id,SessionGet('TABLEPERMSINS'));
    } else {
        $tabdd['perms']['ins'] = false;
    }
    if ( SessionGet( 'TABLEPERMSUPD' ) != '' ) {
        $tabdd['perms']['upd']
            = in_array($table_id,SessionGet('TABLEPERMSUPD'));
    } else {
        $tabdd['perms']['upd'] = false;
    }
    if ( SessionGet( 'TABLEPERMSDEL' )  != '' ) {
        $tabdd['perms']['del']
            = in_array($table_id,SessionGet('TABLEPERMSDEL'));
    } else {
        $tabdd['perms']['del'] = false;
    }

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
        $func($tabdd);
    }

    # --> EARLY RETURN
    #     If a root user, or there is no group, no point
    #     in continuing
    if(SessionGet('ROOT'))
        return $GLOBALS['AG']['tables'][$table_id];
    if(SessionGet('GROUP_ID_EFF','')=='')
        return $GLOBALS['AG']['tables'][$table_id];

    # Capture the effective group and keep going
    $group = SessionGet('GROUP_ID_EFF');

    # Check for a view assignment
    $view='';
    if(isset($tabdd['tableresolve'][$group])) {
        $tabdd['viewname'] = $tabdd['tableresolve'][$group];
        $view = $tabdd['tableresolve'][$group];
    }

    # If there is a view for my group, I have to knock out the columns
    # I will not be allowed to deal with on the server
    if(isset($tabdd['views'][$view])) {
        foreach($tabdd['flat'] as $column_id=>$colinfo) {
            # drop any column not listed
            if(!isset($tabdd['views'][$view][$column_id])) {
                unset($tabdd['flat'][$column_id]);
                continue;
            }

            # If there is a "0" instead of a one, set it read-only
            if($tabdd['views'][$view][$column_id]==0) {
                $tabdd['flat'][$column_id]['uiro'] = 'Y';
            }
        }
    }

    # Now modify all projections to knock out columns I cannot see
    if(isset($tabdd['views'][$view])) {
        foreach($tabdd['projections'] as $idx=>$list) {
            $alist = explode(',',$list);
            $alist2 = array();
            foreach($alist as $column_id) {
                if(isset($tabdd['flat'][$column_id])) $alist2[] = $column_id;
            }
            $tabdd['projections'][$idx] = implode(',',$alist2);
        }
    }

    #return &$GLOBALS['AG']['tables'][$table_id];
    return $tabdd;
}

/****f* Data Dictinary Routines/ddView
*
* NAME
*	ddView
*
* FUNCTION
*   If a table has row-level or column-level security, then direct
*   table access is denied to all users, all access must go through
*   views for that table.  This routine returns the name of the view
*   that can be used by the currently logged in user.
*
*   It is good practice when coding SQL manually to always use
*   this routine and never to manually hardcode table names, unless
*   you can be completely certain your application will never contain
*   row-level and column-level security.
*
*   When you use routines like SQLX_Insert and SQLX_Update you
*   can use the base table name, those routines do not require you
*   to pass in a view name.  Those routines make the call themselves
*   to get the view name.
*  
* INPUTS
*	array $tabx - Data Dictionary table
*
* RETURN VALUE
*	string - the view name of the table
*
* EXAMPLE
*   Here is an example of a manual query:
*
*       <?php
*       # Employees table has row-level security, so we must
*       # find out the name of the view the current user is
*       # able to access.
*       $view = ddView('employees');
*       # Now code the view into an ad-hoc query
*       $sql  = "select * from $view where dept='xyz'";
*       $emps = SQL_AllRows($sql);
*       ?>
*
******
*/
function ddView($tabx) {
    # If not given an array, assume we were given the name of
    # the table and go get the array
    if(!is_array($tabx)) {
        $tabx = ddTable($tabx);
    }

    # KFD 10/26/08.  If no view, reload with new loader routine.
    if(!isset($tabx['viewname'])) {
         unset($GLOBALS['AG']['tables'][$tabx['table_id']]);
         $tabx=ddTable($tabx);
    }
    return $tabx['viewname'];
}

/****f* Data Dictinary Routines/ddUserPerm
*
* NAME
*	ddUserPerm
*
* FUNCTION
*	The PHP function ddUserPerm will tell you if the user is granted a particular permission on a particular
*	table.
*
*	The permissions you can request are:
*		sel: May the user select?
*		ins: May the user insert?
*		upd: May the user update?
*		del: May the user delete?
*		menu: Does this person see this on the menu?  To return a true for this permission, the user
*			      must have menu permission and SELECT permission.
*
* INPUTS
*	string $table_id - Id for the data dictionary table
*	string $perm_id - Id for the permission (sel, ins, upd, del, menu)
*
* RETURN VALUE
*	boolean - if the user has the permission
*
******
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
      # KFD 7/16/08.  Make this universal OR.  Any of
      #               these causes it to appear
      #$retval = $pm && ($ps || SessionGet("ROOT"));
      $retval = $pm || $ps || SessionGet('ROOT');
      return $retval;
   }

   // These are pretty simple
   $perm_id=strtoupper($perm_id);

   # KFD 9/2/08.  This little hack is for one customer still using x2.
   #              It fools the framework into thinking it can access
   #              any table, though actual server-side security will
   #              prevent unauthorized access.
   if(!LoggedIn()) return true;
   #
   # KFD 9/2/08: Original code is here
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

/*---f* Data Dictinary Routines/DD_TableProperty
*
* NAME
*	DD_TableProperty
*
* FUNCTION
*	The PHP function DD_TableProperty fetches the value associated with the provided
*	property in a table with the provided table id.
*
* INPUTS
*	string $table_id - id for the table
*	string $property - property to look for
*
* RETURN VALUE
*	 mixed - value associated with $propery
*
*---**
*/
function DD_TableProperty($table_id,$property) {
    $table = &ddTable($table_id);
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

/*---f* Data Dictinary Routines/DDTable_IDResolve
*
* NAME
*	DDTable_IDResolve
*
* FUNCTION
*	The PHP function DDTable_IDResolve accepts the name of a table and returns the appropriate view to
*	access based on the user's effective group.
*
*	The name of a view is only returned if there is some reason to redirect
*	the user to a view.  In very many cases, oftentimes in all cases, the
*	function returns the base table name itself, such as:
*
*	- If no column or row security is on the table
*	- If the user is a root user
*	- If the user is the anonymous (login) user
*
* INPUTS
*	string $table_id - id for the table
*
* RETURN VALUE
*	string - the appropriate view
*
*---**
*/
function DDTable_IDResolve($table_id) {
    return ddView($table_id);
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

/*---f* Data Dictinary Routines/DD_ColInsertsOK
*
* NAME
*	DD_ColInsertsOK
*
* FUNCTION
*	The PHP function DD_ColInsertsOK accepts an array of dictionary information about a column and
*	then works out if inserts are allowed to that column.  Useful for
*	disabling HTML controls.
*
*	The optional 2nd parameter defaults to "html" but can also be "db".
*	If it is "html" it tells you if the user should be allowed to
*	specify a value, while the value of "db" determines if a SQL Insert
*	should be allowed to specify a value for this column.
*
* INPUTS
*	reference $colinfo - column info
*	string $mode - inserts mode
*
* RETURN VALUE
*	boolean - is inserts allowed in column
*
*---**
*/
function DD_ColInsertsOK(&$colinfo,$mode='html') {
   // If in a drilldown, any parent column is read-only
   if(DrillDownLevel()>0 & $mode=='html') {
      $dd=DrillDownTop();
      if(isset($dd['parent'][$colinfo['column_id']])) return false;
   }
	$aid = strtolower(trim(a($colinfo,"automation_id",'')));
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

/*---f* Data Dictinary Routines/DDColumnWritable
*
* NAME
*	DDColumnWritable
*
* FUNCTION
*	The PHP function DColumnWritable returns true if the provided column info says that the column
*	is writable.
*
* INPUTS
*	reference $colinfo - column info
*	string $gpmode - get/post mode
*
* RETURN VALUE
*	boolean - is the column writable
*
*---**
*/
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

/*---f* Data-Dicitonary-Routines/DD_TableRef
*
* NAME
*	DD_TableRef
*
* FUNCTION
*	The PHP function DD_TableRef loads the data dictionary for a given table and returns a reference.
*
* INPUTS
*	string $table_id - table id
*
* RETURN VALUE
*	reference - reference to data dictionary table
*
*---**
*/
function &DD_TableRef($table_id) {
    $retval= ddTable($table_id);
    return $retval;
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
*
* This function returns the path to the application's
* [[top directory]].  All other directories, such as the
* [[lib directory]] and the [[application directory]] are all
* directly below the [[top directory]].
*
* The return value already contains a trailing slash.
*
* INPUTS
* RETURN
*	string	Directory Path
*/
function fsDirTop() {
   return $GLOBALS['AG']['dirs']['root'];
}

// ------------------------------------------------------------------
// Generic Language Extensions
// ------------------------------------------------------------------
/**
* Allows you to safely retrieve the value of an array by index value,
* returning a [[Standard Default Value]] if the key does not exist.
*
* INPUTS
*	array &$arr	candidate array
*	string $key	array key
*	mixed $value	default value
* RETURN
*	mixed		value associated with $key in $arr.
*/
function ArraySafe(&$arr,$key,$value="") {
	if(isset($arr[$key])) return $arr[$key]; else return $value;
}


/**
* Wrapper for function {@link ArraySafe}.
*
* INPUTS
*	array &$arr	candidate array
*	string $key	array key
*	mixed $value	default value
* RETURN
*	mixed		value associated with $key in $arr.
*/
function arr($a, $key, $value='') {
    return ArraySafe($a,$key,$value);
}    
function a(&$a,$key,$value='') {
    return ArraySafe($a,$key,$value);
}


/*f* Array-Functions/array_copy
*
* FUNCTION
*   Returns a complete copy of an array instead of a reference
*   to the array.
*
* INPUTS
*	array $arr	candidate array
*
* RESULT
*	array - copy of the array
*/
function array_copy($source) {
    $retval = array();
    foreach($source as $key=>$value) {
        if(is_array($value)) {
            $retval[$key] = array_copy($value);
        }
        else {
            $retval[$key] = $value;
        }
    }
    return $retval;
}
/******/


// ------------------------------------------------------------------
// PHP functions that mimic Javascript DOM functions
// ------------------------------------------------------------------
/**
* Creates an element object of type $type.  Uses {@link androHElement}.
*
*	string $type type of element
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
/**
* Creates a log entry.  Used by the Andromeda framework.
*
* INPUTS
*	string $code	log code
*	string $desc	log description
*	string $arg1	extra argument
*	string $arg2	extra argument
*	string $arg3	extra argument
*/
function fwLogEntry($code,$desc,$arg1='',$arg2='',$arg3='') {
   xLogEntry('Y',$code,$desc,$arg1,$arg2,$arg3);
}

/**
* Creates a log entry.  Used by applications.
*
* INPUTS
*	string $code	log code
*	string $desc	log description
*	string $arg1	extra argument
*	string $arg2	extra argument
*	string $arg3	extra argument
*/
function appLogEntry($code,$desc,$arg1='',$arg2='',$arg3='') {
   xLogEntry('N',$code,$desc,$arg1,$arg2,$arg3);
}

/**
* Creates a log entry
*
* INPUTS
*	string $fw	log flag
*	string $code	log code
*	string $desc	log description
*	string $arg1	extra argument
*	string $arg2	extra argument
*	string $arg3	extra argument
*/
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
/****h* PHP API/System Log Functions
*
* NAME
*	System Log Functions
*
* FUNCTION
*	System Log functions are used when you must be certain that a log
*	entry will be written, even if the current transaction rolls back.
*	These kinds of logs are intended for use in debugging or tracking
*	invisible processes, such as a Paypal IPN transaction.
*
*	A log can be opened with [[SysLogOpen]], which returns a handle to
*	the log.  Log entries are made with [[SysLogEntry]] and the log is
*	eventually closed with [[SysLogClose]].
*
*	The logs are stored in tables [[syslogs]] and [[syslogs_e]].
*
*	The guarantee that the log entry will always be written comes at the
*	price of a separate connection to the database for each log.  In a
*	debugging situation you can open as many of them as you need, but in
*	a production system they should only be used in highest need.
*
******
*/

/****f* System Log Functions/SysLogOpen
*
* NAME
*	SysLogOpen
*
* FUNCTION
*	Use this function to open a system log.  Returns a LogNumber, which
*	is used for subsequent calls to [[SysLogEntry]].  When the log is
*	finished, close it with [[SysLogClose]].
*
*	Any number of system logs can be open at a time.
*
* INPUTS
*	string $name - name of log
*
* RETURN
*	int - log number
*
******
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

/****f* System Log Functions/SysLogEntry
*
* NAME
*	SysLogEntry
*
* FUNCTION
*	Writes an entry to a log opened with [[SysLogOpen]].  The value
*	of LogNumber is returned from [[SysLogOpen]].  The value of
*	EntryText is written to the log.
*
* INPUTS
*	int $syslog - log number
*	string $text - log text
*
******
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

/****f* System Log Functions/SysLogClose
*
* NAME
*	SysLogClose
*
* FUNCTION
*	Closes a system log, and closes the database connection that
*	was opened just for that log.
*
* INPUTS
*	int $skey - log number
*
******
*/
function SysLogClose($skey) {
   SysLogEntry($skey,'Log Close Command Received');
   SQL_ConnClose($GLOBALS['AG']['logs'][$skey]);
   unset($GLOBALS['AG']['logs'][$skey]);
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
/*h* PHP API/Hidden-Variables
*
* NAME
*	Hidden-Variables
*
* FUNCTION
*	Hidden variables are the simplest and most time-honoured way to
*	send data to the browser that will come back on the next form post.
*
*	Andromeda allows you to "register" hidden variables at any time
*	using the function [[Hidden]].  The framework function
*	[[ehHiddenAndData]] then outputs them when the HTML is being
*	generated.
*
******
*/

/**
* Registers a value that should be output as a hidden variable when the
* HTML is generated.
*
* The values get written by the framework function [[ehHiddenAndData]].
*
* INPUTS
*	string $varname	Variable name
*	string $val		Variable value
*/

/*f* Hidden-Variables/Hidden
*
* NAME
*	Hidden
*
* FUNCTION
*	The PHP function Hidden registers a variable and its value that should be
*	output as a hidden variable.
*
* INPUTS
*	string $varname - name of variable
*	mixed $val - value for variable
*
* SOURCE
*/
function Hidden($varname=null,$val=null) {
   arrDefault($GLOBALS['AG'],'hidden',array());
   $GLOBALS['AG']['hidden'][$varname]=$val;
}
/******/

/**
* Generates one hidden variable for each column in Table_id.  The name
* of the variables is formed as $table_id."_".$column_id.
*
* If the second parameter, a [[Row Array]], is passed, the hidden
* variables will be populated with values from that array, otherwise
* they will be blank.
*
* !>example
* !>php
* hiddenFromTable('nodes');
* !<
* !>output:Will produce this in the HTML
* <input type="hidden" name="nodes_node" value="">
* <input type="hidden" name="nodes_node_url" value="">
* ...and so forth...
* !<
* !<
*
* INPUTS
*	string $table_id	table id
*	array $row
*/

/*f* Hidden-Variables/HiddenFromTable
*
* NAME
*	hiddenFromTable
*
* FUNCTION
*	The PHP function hiddenFromTable generates one hidden variable for each column in Table_id.  The name
*	of the variables is formed as $table_id."_".$column_id.
*
*	If the second parameter, a [[Row Array]], is passed, the hidden
*	variables will be populated with values from that array, otherwise
*	they will be blank.
*
* INPUTS
*	string $table_id - id for table
*	array $row - hidden variable values
*
* SOURCE
*/
function hiddenFromTable($table_id,$row=array()) {
   $table_id=trim($table_id);
   $table=DD_TableRef($table_id);
   $cols = array_keys($table['flat']);
   foreach($cols as $col) {
      hidden($table_id."_".$col,ArraySafe($row,$col));
   }
}
/******/


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
/*---h* PHP API/Context-Variables
*
* NAME
*	Context Variables
*
* FUNCTION
*	Andromeda provides Context Functions as a more robust and flexible
*	alternative to [[Session Variables]].
*
*	A "context" is all of the state that is specific to a particular
*	browser tab.  If a user opens three browser windows
*	to your app, and three tabs in each browser, each of the nine tabs will
*	have its own context.
*
*	The context is returned to the server only when the user navigates by
*	POST.  If you need to use context, make sure you are using SUBMITs and
*	not hyperlinks.
*
*	[[Session Variables]] are stored on the server, but Context Variables
*	are sent out to the browser and then returned with each round trip.
*	This means that care must be taken only to add the most essential
*	information to context.
*
*	The Andromeda framework sometimes writes its own context variables.  You
*	can avoid collisions with these variables by always using the functions
*	whose names begin with "app".
*
*---**
*/


/*---f* Context-Variables/appconget
*
* NAME
*	appconget
*
* FUNCTION
*	Returns a context variable.  A context variable is completely
*	specified by three levels, a Category, a Name, and a Key.
*
*	Context variables are specific to each window the user has open,
*	and remain alive as long as the user navigates with form POSTs.  When
*	the user navigates with an HTML hyperlink the context is lost.
*
*	Use this routine instead of [[ConGet]] to ensure your variables do not
*	collide with framework context variables.
*
*	The fourth parameter is a [[Standard Default Value]], which is returned
*	if the variable cannot be found.
*
* INPUTS
*	string $category
*	string $name
*	string $key
*	mixed $default
*
* RETURN VALUE
*	mixed - context variable
*
*---**
*/
function appconget($category,$name,$key,$default='') {
   return ContextGet('app_'.$category.'_'.$name.'_'.$key,$default);
}

/*---f* Context-Variables/conget
*
* NAME
*	conget
*
* FUNCTION
*	The PHP function conget returns a context variable.  A context variable is completely
*	specified by at three levels, a Category, a Name, and a Key.
*
*	This function is used exclusively by the framework, your applications
*	should use [[appConGet]].
*
*	The fourth parameter is a [[Standard Default Value]].
*
* INPUTS
*	string $category
*	string $name
*	string $key
*	mixed $default
*
* RETURN VALUE
*	mixed - context variable
*
*---**
*/
function conget($category,$name,$key,$default='') {
   return ContextGet('fw_'.$category.'_'.$name.'_'.$key,$default);
}


/**
* This is the lowest-level routine that returns context variables.
*
* Applications should not use this routine, they should use [[appConGet]].
* Framework library code uses [[ConGet]].
*
* INPUTS
*	string $name
*	mixed $default
* RETURN
*	mixed
*/

/*---f* Context-Variables/ContextGet
*
* NAME
*	ContextGet
*
* FUNCTION
*	The PHP function ContextGet is the lowest-level routine that returns context variables.
*
*	Applications should not use this routine, they should use [[appConGet]].
*	Framework library code uses [[ConGet]].
*
* INPUTS
*	string $name
*	mixed $default
*
* RETURN VALUE
*	mixed - context variable
*
* SOURCE
*/
function ContextGet($name,$default='') {
   $sc=&$GLOBALS['AG']['clean']['gpContext'];
   return isset($sc[$name])
      ? $sc[$name]
      : $default;
}
/*---**/

/*---f* Context-Variables/appConSet
*
* NAME
*	appConSet
*
* FUNCTION
*	The PHP function appConSet sets a context variable.  A context variable is completely
*	specified by at three levels, a Category, a Name, and a Key.
*
*	Application code should use this routine to avoid naming collisions
*	with context variables set by the framework.
*
* INPUTS
*	string $category
*	string $name
*	string $key
*	mixed $value
*
* SOURCE
*/
function appConSet($category,$name,$key,$value='') {
   return ContextSet('app_'.$category.'_'.$name.'_'.$key,$value);
}
/*---**/

/*---f* Context-Variables/conSet
*
* NAME
*	conSet
*
* FUNCTION
*	The PHP function conSet sets a context variable.  A context variable is completely
*	specified by at three levels, a Category, a Name, and a Key.
*
*	This routine is reserved for use by the framework.
*	Application code should use [[appConSet]].
*
* INPUTS
*	string $category
*	string $name
*	string $key
*	mixed $value
* RETURN VALUE
*	mixed
*
* SOURCE
*/
function conSet($category,$name,$key,$value='') {
   return ContextSet('fw_'.$category.'_'.$name.'_'.$key,$value);
}
/*---**/


/*---f* Context-Variables/ContextSet
*
* NAME
*	ContextSet
*
* FUNCTION
*	The PHP function ContextSet is the lowest-level routine that sets context variables.
*
*	Applications should not use this routine, they should use [[appConSet]].
*	Framework library code uses [[ConSet]].
*
* INPUTS
*	string $name
*	mixed $default
*
* SOURCE
*/
function ContextSet($name,$value='') {
   $sc=&$GLOBALS['AG']['clean']['gpContext'];
   $sc[$name]=$value;
}
/*---**/


/*---f* Context-Variables/appConUnSet
*
* NAME
*	appConUnSet
*
* FUNCTION
*	Destroys a context variable.  A context variable is completely
*	specified by at three levels, a Category, a Name, and a Key.
*
*	Application code should use this routine to avoid naming collisions
*	with context variables set by the framework.
*
* INPUTS
*	string $category
*	string $name
*	string $key
*	mixed $default
*
* SOURCE
*/
function appConUnSet($category,$name,$key) {
   return ContextUnSet('app_'.$category.'_'.$name.'_'.$key);
}
/*---**/

/*---f* Context-Variables/conUnSet
*
* NAME
*	conUnSet
*
* FUNCTION
*	Destroys a framework context variable.  A context variable is completely
*	specified by at three levels, a Category, a Name, and a Key.
*
*	This routine is reserved for use by the framework.
*	Application code should use [[appConUnSet]].
*
* INPUTS
*	string $category
*	string $name
*	string $key
*
* RETURN VALUE
*	mixed
*
*---**
*/
function conUnSet($category,$name,$key) {
   return ContextUnSet('fw_'.$category.'_'.$name.'_'.$key);
}


/*---f* Context-Variables/ContextUnSet
*
* NAME
*	ContextUnSet
*
* FUNCTION
*	This is the lowest-level routine that destroys context variables.
*
*	Applications should not use this routine, they should use
*	[[appConUnSet]].  Framework library code should use [[ConUnSet]].
*
* INPUTS
*	string $name
*
* SOURCE
*/
function ContextUnSet($name) {
   if (isset($GLOBALS['gpContext'][$name]))
      unset($GLOBALS['gpContext'][$name]);
}
/*---**/


/*---f* Context-Variables/appConClear
*
* NAME
*	appConClear
*
* FUNCTION
*	Destroys all context variables.
*
*	Application code should use this routine to avoid naming collisions
*	with context variables set by the framework.
*
*---**
*/
function appConClear() {
   return ContextClear('app');
}


/*---f* Context-Variables/ConClear
*
* NAME
*	ConClear
*
* FUNCTION
*	Destroys all framework context variables.
*
*	This routine is reserved for use by the framework.
*	Application code should use [[appConClear]].
*
*---**
*/
function ConClear() {
   return ContextClear('fw');
}

/*---f* Context-Variables/ContextClear
*
* NAME
*	ContextClear
*
* FUNCTION
*	This is the lowest-level routine that destroys all context variables.
*
*	Applications should not use this routine, they should use
*	[[appConClear]].  Framework library code uses [[ConClear]].
*
*---**
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
* Adds a notice to the list of notices to report to the user.
*
* INPUTS
*	string $notice		notice text
*/
/* CODE PURGE CANDIDATE */
/* this routine is not used by the framework */
function NoticeAdd($notice) {
	$GLOBALS["AG"]["messages"][]=$notice;
}

/**
* Returns true if any notices have been registered with [[NoticeAdd]].
*
* INPUTS
* RETURN
*	boolean
*/
function Notices() {
	if (count($GLOBALS["AG"]["messages"])>0) return true; else return false;
}

/**
* Returns an array of the currently reported notices.
*
* INPUTS
* RETURN
*	array		notices texts
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
* Adds an error to the list of errors to report to the user.
*
* INPUTS
*	string $error	Error Text
*/
function ErrorAdd($error) {
   $error=preg_replace('/^[Ee][Rr][Rr][Oo][Rr]:\w*(.*)/','$1',$error);
	$GLOBALS["AG"]["trx_errors"][]=$error;
}

/**
* Adds a list of errors to the the list of errors to report to the user.
*
* INPUTS
*	array $semilist	list of errors to add
*/
function ErrorsAdd($semilist) {
	$arr = explode(";",$semilist);
	foreach ($arr as $err) {
		ErrorAdd($err);
	}
}

/**
* Clears the list of previously registered errors.
*
* INPUTS
*/
function ErrorsClear() {
   $GLOBALS['AG']['trx_errors']=array();
}

/**
* Returns true if any errors have been registered
*
* INPUTS
* RETURN
*	boolean
*/
function Errors($prefix='') { return ErrorsExist($prefix); }

/**
* INPUTS
* @deprecated
*/
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
* Returns an array of the currently reported errors.
*
* INPUTS
*	boolean $errorsclear
* RETURN
*	array		Error's Texts
*/
function ErrorsGet($errorsclear=false) {
   $retval= isset($GLOBALS['AG']['trx_errors'])
      ? $GLOBALS['AG']['trx_errors']
      : array();
   if ($errorsclear) ErrorsClear();
   return $retval;
}

/**
* INPUTS
* @deprecated
*/
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

/**
* INPUTS
* @deprecated
*/
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
* Returns an HTML DIV element containing all reported errors.  Each error
* is in an HTML P element.
*
* You can specify a CSS Class to assign to the DIV element.  If nothing
* is specified, the CSS Class "errorbox" is used.
*
* Your CSS that defines the errors might look like this:
*
* <div class="CSS">
* div.errorbox {
*    ...properties for the errorbox..
* }
* div.errorbox p {
*    ...properties for each error entry
* }
* </div>
*
* INPUTS
*	string $class		css class
* RETURN
*	string HTML_Fragment
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

/**
* Returns all reported errors as a string with each error separated by a new line.
*
* INPUTS
* RETURN
*	string	errors string
*/
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
* Returns an HTML DIV element containing all reported notices.  Each notice
* is in an HTML P element.
*
* You can specify a CSS Class to assign to the DIV element.  If nothing
* is specified, the CSS Class "noticebox" is used.
*
* Your CSS that defines the notices might look like this:
*
* <div class="CSS">
* div.noticebox {
*   ...properties for the noticebox..
* }
* div.noticebox p {
*    ...properties for each notice entry
* }
* </div>
*
* INPUTS
*	string $class	class for notices
* RETURN
*	string	html notices
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
* The User Preferences system is EXPERIMENTAL and may change
* considerably before Version 1.0 is released.
*
* The basic idea is to allow users to override system default
* behaviors, such as how dates are displayed.
*/

/**
* ''*EXPERIMENTAL*''
*
* Expects the user preferences to have been set with [[vgaSet]] under the
* name "this_user_prefs".  Expects the user preferences to be an array.
*
* If the Key is in the array, then the preference is returned, else the
* Default value is returned.
*
* Note that this is experimental and has been set up only for one client.
*
* INPUTS
*	string $key	key for preference
*	mixed $default	default value for preference
*/
function userPref($key,$default) {
   $array=vgfGet('this_user_prefs');
   return ArraySafe($array,$key,$default);
}


/**
* ''*EXPERIMENTAL*''
*
* Loads a [[Row Array]] of user preferences via [[vgfSet]] to
* framework variable 'this_user_prefs'.
*
* Normally if you want to make use of user preferences you put a call
* to this routine in applib.php.
*
* This function needs the application variable 'user_preferences' to be
* set to the name of the table that contains user preferences.  That
* table is expected to have column 'user_id' in it.
*
* The row selected is where user_id=SessionGet("UID").
*
* @category	User Preferences
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
* This handy routine returns either an HTML property assignment or
* an empty string.  It is a useful helper routine for building HTML
* element definitions when you don't know if the parameters being passed
* in are going to be empty.
*
* So for instance, if you have been passed a value of $CSS_class which
* may be empty, you can call:
*
* $class=hTagParm('class',$CSS_Class)
*
* if the value passed to $CSS_Class is empty it will give you back an
* empty string, otherwise it will give you the string 'class="-CSS_Class-"'.
*
* This allows for safe unconditional placement of $class into an HTML
* element definition.
*
* INPUTS
*	string $parmname
*	string $parmval
* RETURN
*	string	HTML Tag Property
*/
function hTagParm($parmname,$parmval) {
   return $parmval==''
      ? ''
      : $parmname. ' ="'.trim($parmval).'"';
}

/**
*
* This function generates a single arbitrary HTML element, with open and close tags
* and optional class asignment.  It does not save a great deal of typing
* but it does allow you to avoid to confusing mixtures of PHP and HTML.
*
* The first parameter, CSS_Class, can be an empty string.
*
* INPUTS
*	string $class	CSS Class
*	string $element	HTML Element
*	string $innerHTML	Inner HTML in Element
* RETURN
*	string	HTML Generated
*/
function hElement($class,$element,$innerHTML) {
   $hclass = hTagParm("class",$class);
   return "<".$element.' '.$hclass.'>'.$innerHTML."</$element>";
}


/**
*
* Returns an HTML TD element with open and close tags.
*
* The first parameter is the [[Optional CSS Class]].
*
* INPUTS
*	string $class CSS Class
*	string $value HTML inside the element
*	string $extra Extra properties
* RETURN
*	string	Generated HTML
*/
function hTD($class,$value,$extra='') {
   $class=hTagParm('class',$class);
   return "\n  <td $class ".$extra.">".$value."</td>";
}

/**
* Returns an HTML SPAN element with open and close tags.
*
* The first parameter is the [[Optional CSS Class]].
*
* INPUTS
*	string $class	CSS Class
*	string $value	HTML inside element
*	string $extra	Extra properties
* RETURN
*	string	Generated HTML
*/
function hSpan($class,$value,$extra='') {
   $class=hTagParm('class',$class);
   return "\n  <span $class ".$extra.">".$value."</span>";
}


/**
* Accepts a [[Row Array]] and returns a complete HTML TR element,
* populated with on TD element per element of the [[Row Array]].
*
* The first parameter is the [[Optional CSS Class]].  This class is
* assigned to the TR and to each of the TD elements.
*
* INPUTS
*	string $class	CSS Class
*	array $row
* RETURN
*	string	Generated HTML
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
* Returns an HTML TR with a single TD element of fixed height "Height".
* Good for putting spacers into table.
*
* The second parameter is an optional COLSPAN setting for the TD element.
*
* INPUTS
*	int $height	Height for each TD element
*	int $colspan	Colspan for each TD element
* RETURN
*	string	Generated HTML
*/
function hTRFiller($height,$colspan='') {
   $colspan=hTagParm('colspan',$colspan);
   return "<tr><td height=".$height." $colspan></td></tr>";
}

/**
* INPUTS
* @deprecated
*/
function hTable($width=0,$height=0) {
   $pw = $width=0  ? '' : ' WIDTH="'.$width.'%" ';
   $ph = $height=0 ? '' : ' HEIGHT="'.$height.'%" ';
   return '<table'.$pw.$ph.' border="0" cellpadding="0" cellspacing="0">';
}


/**
* Takes a number between 0 and 1 and returns a formatted string between
* 0 and 100, with an optional trailing % sign.
*
* INPUTS
*	mixed $inputval Number between 0 and 1
*	mixed $decimals Number of trailing decimals
*	bool $trailing_pct True if it has a trailing % sign
* RETURN
*	string	Generated HTML
*/
function hPct($inputval,$decimals=1,$trailing_pct=false) {
   $retval=number_format($inputval*100,$decimals);
   return $retval.($trailing_pct ? '%' : '');
}

/**
* date:3/31/07
* testtypes:char,char
* test:3/31/07,
* test:3/9/07,d-mm-yyyy
* test:3/9/07,dd-mm-yyyy
* test:3/9/07,ddd-mm-yyyy
* test:3/9/07,Ddd-mm-yyyy
* test:3/9/07,DDD-mm-yyyy
* test:3/9/07,dddd-mm-yyyy
* test:3/9/07,Dddd-mm-yyyy
* test:3/9/07,DDDD-mm-yyyy
* test:3/9/07,m/d/yy
* test:3/9/07,mm/d/yy
* test:3/9/07,mmm/d/yy
* test:3/9/07,Mmm/d/yy
* test:3/9/07,MMM/d/yy
* test:3/9/07,mmmm/d/yy
* test:3/9/07,Mddd/d/yy
* test:3/9/07,MMMM/d/yy
* test:3/9/07,m/d/yy
* test:3/9/07,m/d/yyyy
* test:1/1/07 ,m-d-y EXTRA m ** d ** yyyy
* test:12/31/07,m-d-y EXTRA mm ** ddd ** yyyy
*
* Accepts either a string or a unix timestamp and returns
* a string that can be sent to the browser.  This is a great
* function for people who cannot remember the
* formatting codes for the [[php:date]] function.
*
* If the first parameter is a string, hDate passes it through
* [[php:strototime]] to convert it into a timestamp.  If the first
* parameter is a number hDate assumes it is a unix timestamp.
*
* If no second parameter is provided, hDate calls
* [[php:date]] with the string 'm/d/Y', a standard US date format.
*
* The real value of hDate comes into play if you can never remember those
* strange formatting strings for [[php:date]].  The strings for
* hDate are much easier to remember.  They are:
*
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
*
* INPUTS
*	mixed $date	Date to be formated
*	string $format	Date format (optional)
* RETURN
*	string	Generated HTML
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

/**
* Helper function for hDate.  Not meant to be used by anything other than
* hDate.
*
* INPUTS
*/
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

/**
* Builds the final form of the date.  Meant to be used by the
* hDate function, and only by that function.
*
* INPUTS
*/
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
* Equivalent to calling the PHP function date('l, F j, Y',unix_ts), which
* returns a date as "Weekday, Month x, YYYY".
*
*	$unixts
* RETURN
*	string	Date with format "Weekday, Month x, YYYY".
*/
function hDateWords($unixts) {
   return date('l, F j, Y',$unixts);
}


/**
* Returns a "minimal" number.  Trailing decimal is removed
* if there are no decimals.  By default a blank string is returned
* if the value is zero, but if the second parameter is passed in
* then the second parameter is returned instead.
* Typical values for second parameter might
* be "-0-" or "n/a" or just plain "0".
*
* INPUTS
*	mixed $value	Number
*	mixed $zero	Return when $value == 0
* RETURN
*	string	Generated HTML
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
* Returns a number formatted with commas and decimal, padded to the
* left.  Useful for generated tables in fixed-width fonts or on reports.
*
* INPUTS
*	mixed $value	number to format
*	int $width	width (optional, default set to length)
*	int $decimals number of decimals(optional, default zero)
* RETURN
*	string 	Generated HTML
*/
function hNumFormat($value,$width=0,$decimals=0) {
   $retval=number_format($value,$decimals);
   if($width==0) return $retval;
   else return str_pad($retval,$width,' ',STR_PAD_LEFT);
}


/**
* This routine will return the first image that it can find in the [[apppub]]
* directory for the given table and (optionally) column.
*
* If no third parameter is passed in, the routine assumes the second parameter,
* "Value", is a value for the given table's primary key.  It looks for any
* file in "apppub/$Table" named after $Value and having an extension .jpg,
* .png, or .gif.  The routine returns an IMG tag pointing to the first
* such image it finds.
*
* If a third parameter is passed,the routine assumes the second parameter,
* "Value", is a value of that named column.  It looks for any file in
* "apppub/$Table/$Column" named after $Value and having an extension .jpg,
* .png, or .gif.  The routine returns an IMG tag pointing to the first
* such image it finds.
*
* INPUTS
*	string $table_id	Table with picture to look for
*	string $value		Value for table primary key
*	string $column 	Column to look in (optional)
* RETURN
*	string		Generated HTML
*/
function hImg($table_id,$value,$column='') {
   $afiles=aImg($table_id,$value,$column);

   // If we found anything, return it
   if(count($afiles)>0) {
      return hImgAppPub($column,$afiles[0]);
   }
   else return '';
}

/**
* INPUTS
* @deprecated
*/
function hImgAppPub($filename,$column='') {
   $THISROUTINEDEPRECATED=$filename;
   if($column<>'') $column.='/';
   return "<img src='apppub/$column$filename' border=0>";
}

/**
* This routine is almost identical to [[hImg]] except that it returns
* an array of images tags if more than one result is found, and it
* returns an empty array if none are found.
*
* INPUTS
*	string $table_id	table id
*	string $value	table primary key
*	string $column 	column to look in(optional)
* RETURN
*	array			Images with HTML
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
* Returns an array of image names in apppub
*
* INPUTS
*	string $table_id	table id
*	string $value		table primary key
*	string $column		column to look in (optional)
* RETURN
*	array			image names
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
* First passes number through [[hNumber]], then prefixes either a
* '+' or '-' sign if positive or negative.  Adds nothing if the value is zero.
*
* INPUTS
*	mixed $value	number to format
*	mixed $zero
* RETURN
*	string	Generated HTML
*/
function hNumberPlus($value,$zero='') {
   if($value==0) return $zero;
   if($value<=0) return hNumber($value,$zero);
   return '+'.hNumber($value,$zero);
}

/**
* Equivalent of number_format($input,2).
*
* INPUTS
*	mixed $input	number to format
* RETURN
*	string	Generated HTML
*/
function hMoney($input) {
   return number_format($input,2);
}

/**
* Inserts a dash into a zip code if it has more than 5 characters and
* there is no dash there already.  Allows you to have zip code columns
* that do not contain a dash, or that may or may not contain a dash.
*
* INPUTS
*	string $input	zip code
* RETURN
*	string 	Generated HTML
*/
function hZip9($input='') {
   if(strlen($input)<6) return $input;
   if(strpos($input,'-')!==false) return $input;
   return substr($input,0,5).'-'.substr($input,5);
}


/**
* INPUTS
* @deprecated
*/
function hSimpleNumber($value) {
   if(intval($value)==$value) {
      return number_format($value);
   }
   else {
      return $value;
   }
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
* This is a very simple routine that does not save a lot of typing but
* avoids a lot of intermixing of HTML and PHP.
*
* The first parameter is an [[Optional CSS Class]].  The link is built
* from the "Caption" and the "href" parameters.
*
* This routine was modified on 3/16/07 so that it would continue to work
* with friendly URLs.  The two changes are:
*
* Now returns absolute paths, always begins with '/'.
* Invoke [[tmpPathInsert]] and prefixes the result to the link
*
* !>example
* !>php
* <div class="moduletable">
*   <?php echo hLink('bolder','Link1','?explicit=links&parm=value')?>
*   <?php echo hLink('','Second Link','?second=example&parm=value')?>
* </div>
* !<
* !<
*
* INPUTS
*	string $class	CSS Class
*	string $caption Text inside <a> element
*	string $href	Hypertext reference
* RETURN
*	string	Generated HTML
*/
function hLink($class,$caption,$href,$extra='') {
   $class=hTagParm('class',$class);
   //if(substr($href,0,1)=='&') $href=substr($href,1);
   $prefix=tmpPathInsert();

   // Try to figure out if they need a question mark in front
   // if there is an equal sign but no question mark, put it in front
   if(substr($href,0,1)<>'?') {
      if(strpos($href,'=')!==false && strpos($href,'?')===false) {
         $prefix.='?';
      }
   }
   return "<a href=\"".$prefix.$href."\" ".$class." $extra>".$caption."</a>";
}

function ddPageDescription($page) {
    $PAGES='explicity assignment avoids compiler warning';
    include('ddpages.php'); //re-assings PAGES ARRAY
    return arr($PAGES,$page,'Page '.$page.' is not defined');
}


/**
* This is a simple routine that generates a framework-standard link to
* a page.  Please see [[Pages, Classes, and Tables]] for more information
* on the definition of a 'page'.
*
* !>example
* !>php
* <div>
*   You can jump straight to <?php echo hLinkPage('orders')?> from here.
* </div>
* !<
* !<
*
* INPUTS
*	string $class	CSS Class
*	string $page_id	Page ID
* RETURN
*	string Generated HTML
*/
function hLinkPage($class,$page_id) {
   // Load the list of pages.
   $PAGES='explicit assignment avoids compiler warning';
   include('ddpages.php');
   $caption=ArraySafe($PAGES,$page_id,'Link to unknown page: '.$page_id);
   return hlink($class,$caption,"?gp_page=".urlencode($page_id));
}

/**
* Returns a hyperlink that will invoke the javascript [[CheckFirst]] function
* before executing the link. The [[CheckFirst]] function makes sure it is safe
* to leave the current page, saving changes first and things like that.
*
* This function always builds links that explicitly go to index.php.
*
* There is no provision for specifying the class or id of the object
* at this time.  It is expected that the hyperlink will get its styles
* defined in descendant selectors.
*
* INPUTS
*	string $caption	Text inside <a> element
*	string $href		Hypertext Reference
*/
function hjxCheckFirst($caption,$href) {
   //if (substr($href,0,1)<>'?') $href='?'.$href;
   //$href='index.php'.$href;
   return "<a href=\"javascript:CheckFirst('$href')\">$caption</a>";
}



/**
* Use this routine when putting links that are internal to your site
* directly into literal HTML.  This routine is not necessary for links
* to outside pages.
*
* The hpHREF routine does two things.  First, it processes your href
* string through urlencode.  Second, it prepends the [[Site Prefix]] to
* the URL so that your link will work in any run-time situation, such
* as a development machine, a development server, or a live server.
*
* If the first parameter is an array, it is converted into a URL string.
*
* !>example:Using hpHREF
* !>php:Literal HTML should wrap HREFs
* <div id="someid">
* Please proceed to the
* <a href="<?php echo hpHREF('?gp_page=somepage')?>">Ordering Page</a>
* so that we can process your order.
* </div>
* !<
* !<
*
* INPUTS
*	mixed $params
* RETURN
*	string Generated HTML
*/
function hpHref($parms) {
   if(is_array($parms)) $parms=http_build_query($parms);
   return tmpPathInsert().$parms;
}

/**
* Returns an HTML Input control for a file upload, with a SUBMIT button
* that says "Upload Now".  File uploads are automatically moved to
* the [[files]] directory by [[index_hidden.php]] and the information about
* the file can be retrieved with [[vgfGet]]('files').
*
* INPUTS
* RETURN
*	string Generated HTML
*/
function hFileUpload() {
   ?>
   <input type="hidden" name="MAX_FILE_SIZE" value="150000000" />
   <input type="file" name="andro_file">&nbsp;&nbsp;
   <button type="submit" value="1">Upload Now</button>
   <?php
}


/**
* This is a simple routine that does not save a lot of typing but
* avoids a lot of intermixing of HTML and PHP.
*
* The first parameter is an [[Optional CSS Class]].  The parameter
* "page" is assigned to "gp_page" and the parameter "Column_Value" is assigned
* to "gp_colval".
*
* INPUTS
*	string $class CSS Class
*	string $caption Text within the <a> element
*	string $page Page
*	int $colval Column Value
* RETURN
*	string Generated HTML
*/
function hLinkPageRow($class,$caption,$page,$colval) {
   $class=hTagParm('class',$class);
   $href="gp_page=".$page."&gp_colval=".urlencode($colval);
   return "<a href=\"?".$href."\" ".$class.">".$caption."</a>";
}

/**
* This routine is useful when you need to make a lot of links that will be
* very similar.  First you assign a default or 'stub' hyperlink by using
* [[vgaSet]] to assign a value to 'hLinkStub'.
*
* When hLinkFromStub is called, it adds the value of 'hLinkStub' to the
* href for the link it returns.
*
* The first parameter is an [[Optional CSS Class]].  The value of the
* "href" is made by combining the [[Global Variable]] 'hLinkStub' to the
* value passed in.
*
* This routine strips a leading '&' or '?' from the HREF passed in, and
* then prepends an appropriate '&' or '?'.
*
* INPUTS
*	string $class CSS Class
*	string $caption Text within <a> element
*	string $href Hypertext reference
*/
function hLinkFromStub($class,$caption,$href) {
   $hStub=vgaGet('hLinkStub');
   $hPrefix=$hStub=='' ? '?' : '&';
   if(substr($href,0,1)=='?') $href=substr($href,1);
   if(substr($href,0,1)=='&') $href=substr($href,1);
   return hLink($class,$caption,$hStub.$hPrefix.$href);
}


/**
* Similar to [[hLink]], but the link will activate a popup form.
*
* INPUTS
*	string $class CSS Class
*	string $caption Text within the <a> element
*	array $parms Parameters for page
* RETURN
*	string Generated HTML
*/
function hLinkPopup($class,$caption,$parms) {
   $class=hTagParm('class',$class);
   $hparms = is_array($parms) ? http_build_query($parms) : $parms;
   return "<a href=\"javascript:Popup('index.php?$hparms','$caption')\""
      ." $class>"
      .$caption."</a>";
}

/**
* Returns a hyperlink that invokes the javascript function [[SetAndPost]],
* with caption given by Caption and GP_Variable_Name and GP_Value becoming
* the arguments to [[SetAndPost]].
*
* These links are handy for having a button that sets the value of a form
* variable and then posts the form.
*
* INPUTS
*	string $caption Text within <a> element
*	string $gp_var Get/Post Variable Name
*	string $gp_val Get/Post Variable Value
* RETURN
*	string Generated HTML
*/
function hLInkSetAndPost($caption,$gp_var,$gp_val) {
   return
      '<a href="javascript:SetAndPost('
      ."'".$gp_var."','".$gp_val."')\">".$caption."</a>";
}


/**
* Accepts the name of a table and column that is supposed to be a mime-x
* type, containing an image.  The PK_Value is primary key value.  Forces
* a save of the image to a dynamic file named Table_ID-Column_ID-PK_Value,
* and returns an HTML IMG element pointing to the file.
*
* INPUTS
*	string $table_id Table Id
*	string $colname Name of Column
*	string $pkval PK Values
*	string $bytes Image Bytes
* RETURN
*	string Generated HTML
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

/**
* Needs the CSS Class setting, and an option for checked
*
* INPUTS
* @deprecated
*/
function hCheckBox($name,$value) {
   return '<input type="checkbox" name="'.$name.'" value="'.$value.'">';
}

/**
* Needs the CSS Class setting, and an option for checked
*
* INPUTS
* @deprecated
*/
function hCheckBoxFromCBool($name,$cbool='N',$caption) {
   $checked='';
   if($cbool=='Y') $checked=' CHECKED ';
   return '<input type="checkbox" '
      .'name="'.$name.'" value="Y"'
      .$checked .' >'
      .$caption
      .'</input>';
}


/**
* INPUTS
* @deprecated
*/
function hDateVerbose($time) {
   return date('D, F j, Y',$time);
}

/**
* INPUTS
* @deprecated
*/
function hFlagLogin($caption) {
   hidden('gp_flaglogin','');
   $hHref = "javascript:SetAndPost('gp_flaglogin','1')";
   return '<a href="'.$hHref.'">'.$caption.'</a>';

}

/**
* Returns the name of the month from the number.
*
* Returns empty string if month is not between 1 and 12.
*
* INPUTS
*	mixed $month Month
* RETURN
*	string Name of month
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
/*h* PHP API/Template-Level-HTML
*
* NAME
*	Template-Level-HTML
*
* FUNCTION
*	These are functions that are only used at the very end of processing,
*	usually inside of your html_main template.  Each one outputs a
*	significant and very important part of the page.
*
*	If you make your own template, you will need to know about these.
*
******
*/


/****f* Template-Level-HTML/ehStandardContent
*
* NAME
*	ehStandardContent
*
* FUNCTION
*	This is the grand-daddy function that must be in every template.
*
*	During normal processing, control always passes to some instance of
*	[[x_table2]].  That object always echos HTML directly.  However, that
*	HTML is being buffered and is captured and saved.  This function
*	re-echos that HTML directly, plus it outputs all hidden variables
*	and various other essential goodies.
*
*	Invoke this command in the main content area of your template.
*
* INPUTS
*	boolean $dotitle True if has title
*
******
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
         <?php echo $scr1?>
         alert("end of fieldsSet");
      }

      function fieldsReset() {
         alert('Ran FieldsReset');
         <?php echo $scr2?>
         alert("end of fieldsReset");
      }
      </script>
      <?php
      */
   }

}

/****f* Template-Level-HTML/ehStandardOpen
*
* NAME
*	ehStandardOpen
*
* FUNCTION
*	This function outputs a standard HTML FORM open tag that will POST
*	results back to [[index.php]].  You will need to close the form manually
*	at then end of your content.  The name of the form should not be 'Form1',
*	as that will collide with the name of the standard form that is on all
*	pages.
*
*	For all normal Andromeda pages you never need to output an HTML FORM, all
*	of your main output is always automatically wrapped in a form, and so you
*	would not normally need this function.
*
*	You may need this function in cases where you have other forms on the page
*	that are outside of the main content, such as a login form that is always
*	sitting off on the left or something similar.
*
*	This function is not meant to save a lot of typing, its purpose is to give
*	you an HTML FORM that is framework-consistent.  It also helps to avoid
*	the eternal problem of [[Messy HTML and PHP]].
*
* INPUTS
*	string $id	Form name (default "Form1")
*
******
*/
function ehStandardFormOpen($id='Form1') {
   $x=$id; //annoying jedit compiler warning
   $style = vgfGet('x6') ? '' : 'style="height:100%"';
   ?>
   <form method="post" action="index.php" id="<?php echo $x?>"
                 enctype="multipart/form-data"
                 name="Form1" <?php echo $style?> >
   <?php
}

/**
* INPUTS
* @deprecated use hErrors()
*/
function ehErrors() {
    if(function_exists('app_ehErrors')) {
        $errors = ErrorsGet();
        if(count($errors)==0) return;
        ErrorsClear();
        app_ehErrors($errors);
    }
    
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

/****f* Template-Level-HTML/ehHiddenAndData
*
* NAME
*	ehHiddenAndData
*
* FUNCTION
*	Echos directly all hidden variables.  Not necessary if you
*	use [[ehStandardContent]].
*
******
*/
function ehHiddenAndData() {
   // Some parts of the framework create data that should
   // be sent out as hidden variables
   $x=vgfGet('gpControls','');
   if(is_array($x)) {
      hidden('gpControls',base64_encode(serialize($x)));
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

/****f* Template-Level-HTML/ehStandardMenu
*
* NAME
*	ehStandardMenu
*
* FUNCTION
*	Echos directly the current user's menu.  Intended to be used with
*	the "plain vanilla" Andromeda template.
*
******
*/
function ehStandardMenu() {
   $menufile = 'menu_'.SessionGet('UID').'.php';
   if (FILE_EXISTS_INCPATH($menufile)){
      include($menufile);
   }
}


// Either display login boxes or say "logged in as"
/****f* Template-Level-HTML/ehLogin
*
* NAME
*	ehLogin
*
* FUNCTION
*	Provides a login/logout box on the screen.
*
*	This routine outputs one of two things.  If a user is logged in,
*	it says, "Welcome -Username-!" and gives a logout button.  If nobody
*	is logged in, it presents a login box and a password box.
*
*	The output is inside of a table.  The items are stacked on top of
*	each other, so the first row says "Username:" and the second row has
*	a textbox, the third row says "Password:" and the fourth row has
* 	another textbox, and finally the fifth row has a submit button.
*
*	If CSS_Class is provided, the TABLE and TD elements will both get
*	that class asignment.  If the DOM_ID element is provided, the TABLE
*	and TD elements will all get that ID assignment.
*
*	If the third parameter, Username, is provided, that will be the default
*	entry in the UserID box.
*
* INPUTS
*	string $class CSS Class
*	string $id DOM ID
*	string $username Username
*
******
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
   <table border=0 <?php echo $hclass?> >
    <tr>
    <td align="center" <?php echo $hclass?>>
     <div style="height:3px"></div>
     <span>
      <span style="font-size: 1.2em;" class="login">
      Welcome<br>
      <?php echo SessionGet('UID')?>!
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
   <table <?php echo $hclass?>>
     <tr>
      <td <?php echo $hclass?>>User Login:</td>
     </tr>
     <tr>
      <td ><input type="text" name="loginUID" <?php echo $hValue?>
            style="width:100%; background:ffffff;
                   color: #333333;
                   font-family: Geneva, Arial, Helvetica, san-serif;
                   font-size: 11px; Border: solid 1px1 #BABABA;"></td>
     </tr>
     <tr>
      <td <?php echo $hclass?>>Password:</td>
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

/****f* Template-Level-HTML/ehLoginHorizontal
*
* NAME
*	ehLoginHorizontal
*
* FUNCTION
*	Echos a conventional UserID/Password form running horizontally, with
*	no class definitions, the objects should receive the styles of their
*	parents.
*
*	If the user is logged in, a logout button is also displayed.
*
******
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
      <a href="<?php echo tmpPathInsert()?>?gp_page=x_password">Help with Password</a>
   <?php } else { ?>
      <a href="?st2logout=1">Logout <?php echo SessionGet("UID")?></a>
   <?php } ?>
   <?php
}

/****f* Template-Level-HTML/ehModuleCommands
*
* NAME
*	ehModuleCommands
*
* FUNCTION
*	Echos a command window.  In the default Andromeda template there is a module
*	named "commands".  If this module is activated, it contains the content
* 	generated by the ehCommands routine.  When an alternate template is used
*	and you want a command window, all you need is a wide bar.
*
*	At this writing we are contemplating moving the button bars up into
*	the command window.
*
******
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
          value="<?php echo gp('gp_command')?>"
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

   <span style="color: red"><?php echo "&nbsp;&nbsp;".vgfGet('command_error')?></span>
   <?php
   if(gpExists('gp_gbt')) {
      ?>
      &nbsp;&nbsp;
      <a href="<?php echo gp('gp_gbrl')?>"><?php echo gp('gp_gbt')?></a>
      <?php
   }
   ?>
   </span>

   <span style="padding-right: 10px">
   <?php echo vgfGet('html_buttonbar')?>&nbsp;&nbsp;&nbsp;&nbsp;
   <?php echo vgfGet('html_navbar')?>
   <?php if(vgfGet('x4')===true) { ?>
       <?php echo ehLoginHorizontal()?>
   <?php } ?>
   </span>
   </div>
   <?php
}




// ==================================================================
// ==================================================================
// HTTP Functions
// ==================================================================
// ==================================================================
/*h* PHP API/HTTP-Functions
*
* NAME
*	HTTP-Functions
*
* FUNCTION
*	These functions make it easier to find things like the current
*	website's address, without having to go through the $_SERVER superglobal.
*
*	Knowing the website's address is especially useful when you have
*	multiple instances of an application, such as when you have a test/live
*	setup or when you are hosting it for more than one customer.  These
*	functions let you write code that can build complete links back to the
*	site without hardcoding any addresses.
*
******
*/

/*h* HTTP-Functions/httpWebPagePath
*
* NAME
*	httpWebPagePath
*
* FUNCTION
*	This function returns the complete URL of the current page, as taken from
*	$_SERVER['HTTP_HOST'] and dirname($_SERVER['REQUEST_URI']), giving
*	results such as http://www.example.com/path/to/page.
*
*	This program will strip off the framework-supported 'fake' paths
*	of "rpath" and "pages".
*
* RETURN
*	string - Web Address
*
******
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

/*f* HTTP-Functions/httpWebSite
*
* NAME
*	httpWebSite
*
* FUNCTION
*	This function returns the URL of the current page without the path, as
*	taken from $_SERVER['HTTP_HOST'].
*
* RETURN
*	string - Web Address
*
******
*/
function httpWebSite() {
   return 'http://'.$_SERVER['HTTP_HOST'];
}

/*f* HTTP-Functions/httpHeadersForDownload
*
* NAME
*	httpHeadersForDownload
*
* FUNCTION
*	This function sends out headers that are appropriate for sending a
*	file as a download.  The routine does not necessarily support all
*	headers, to see which ones are supported, send a "*" as the first
*	parameter and the program will dump supported values out onto
*	the screen.
*
*	By default the content is sent as in-line content.  If the second
*	parameter is true, a header will be sent indicating the file is being
*	sent as an attachment.
*
*	When using this function, you need to have the [[flag_buffer]] property
*	of your class set to false, and this must be set in the [[custom_construct]]
*	method, as in the example below.
*
*	<?php
*	class sendfile extends x_table2 {
*		function custom_construct() {
*		$this->flag_buffer=false;
*	}
*
*		function main() {
*			$filename='/path/to/myfile.mp3';
*			httpHeadersForDownload($filename);
*			readfile($filename);
*			exit; // Exit is required to avoid extraneous output
*		}
*	}
*	?>
*
*	You can add new extensions by declaring an array [[httpMimeTypes]] at
*	the top of your [[applib.php]] file.
*
*	Example: Adding your own types
*
*	<?php
*		// file: applib.php
*		$httpMimeTypes=array(
*   		'xyz'=>'application/xyz-handler'
*		);
*	?>
*
* INPUTS
*	string $filespec - (dir + file)
*	boolean $attachment - True if attatchment (default false)
*
******
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
     ,'csv'=>'application/vnd.ms-excel'
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
   if(file_exists($filespec)) {
       header('Content-Length: '.(string)(filesize($filespec)));
   }
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
* Adds the user to the system and set password.  Also makes it part of the
* login group for the current application.
*
* The fourth parameter defaults to 'Y' and determines if the user should be
* started as an active user.
*
* This routine connects to the node manager database itself, you do not
* have to connect to the node manager before calling it.
*
* Any errors are registred with [[ErrorAdd]].  Check for success by
* calling [[Errors]].  If it returns true the command failed.
*
* INPUTS
*	string $UID User Id
*	string $PWD Password (Default '')
*	string $email Email (Default '')
*	string $user_active Start as active user? (Y=yes, N=no) (Default 'Y')
* RETURN
*	boolean True if success, False if failure
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
* Converts an email address into a string that will be accepted by
* Postgres as a valid USER_ID.
*
* INPUTS
*	string $UID Email address to be converted
* RETURN
*	string Converted User Id
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
* Returns true if a user has successfully logged in on the current
* session, otherwise returns false.
*
* INPUTS
* RETURN
*	boolean
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
* Returns true if a the current user is in the
* specified group.
*
* INPUTS
*	string $group  Name of the group
* RETURN
*	boolean
*/
function inGroup($group) {
    $agroups = SessionGet('agroups',array());
    $agroups = array();
    if(count($agroups) == 0) {
        $app = $GLOBALS['AG']['application'];
        $appl= strlen($app);

        $groups = explode(',',SessionGet('groups'));
        foreach($groups as $grp) {
            # For some reason these were saved with single
            # quotes around them, get rid of them
            $grp = str_replace("'","",$grp);

            # Don't include the $LOGIN group
            if($grp == $app) continue;

            # The +1 is for the underscore.  A member
            # of group "admin" in application "example"
            # will actually be in group "admin_example"
            $agroups[] = substr($grp,$appl+1);
        }

        SessionSet('agroups',$agroups);
    }
    return in_array($group,$agroups);
}

/**
* Returns true if a the current user is able to
* do user maintenance.  Shortcut for a call
* to inGroup('usermaint').
*
* INPUTS
* RETURN
*	boolean
*/
function inUserMaint() {
    return inGroup('usermaint');
}

/**
* Returns true if a the current user is a
* "root" user on the node.  Shortcut to
* to SessionGet('ROOT').
*
* INPUTS
* RETURN
*	boolean
*/
function inRoot() {
    return SessionGet('ROOT');
}




/**
* This function pushes the current [[GET-POST Variables]] to the stack
* and then displays the login page.  When a successful login is processed,
* the original [[GET-POST Variables]] are restored, and the user returns
* to their original destination.  This was coded specifically with
* shopping cart checkouts in mind.
*
* This routine makes use of [[gpToSession]], which can be used to create
* similar routines.
*
* This routine cannot clear out HTML that has already been sent out, so
* it should be called at the beginning of processing.
*
* INPUTS
*/
function PushToLogin() {
   gpToSession();
   objPageMain('x_login');
}


// ==================================================================
// ==================================================================
// Miscellaneous Functions
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
* flag:framework
*
* Returns an object following Andromeda Object conventions.
*
* If the class Page_Name exists inside of a file by the same name,
* then that class is instantiated.
*
* If there is no file by the name of Page_Name, then an instance of
* [[x_table2]] is instantiated and initialized for table Page_Name.
*
* If there is no table named Page_Name, an uninitialized instance of
* [[x_table2]] is returned.
*
* INPUTS
*	string $gp_page Page Name
* RETURN
*	object Dispatch Page Object
*/
function objPage($gp_page) {
   return DispatchObject($gp_page);
}

/**
* Builds a business report object with orientation $orient and
* parent $parent.  Parent gets set as the trackback for the
* report.
*
*	mixed $oParent
*	string $orient
* RETURN
*	object x_fpdf
*/
function objReport($oParent,$orient='P') {
   include_once('x_fpdf.php');
   $retval= new x_fpdf($orient);
   $retval->trackback=$oParent;
   return $retval;
}

/**
* Generate a unique number out of microtime(), suitable
* for use as ID values for HTML elements.
*
* RETURN
*	integer a unique value
*/
function uniqueID() {
    $value = microtime();
    $value = str_replace('.','',$value);
    $value = str_replace('_','',$value);
    $value = str_replace(' ','',$value);
    return $value;
}


/**
* This routine will accept the name of a class, instantiates an object,
* and call's the object's "main" method.  In Andromeda, the "main"
* method always outputs HTML.
*
* This is a handy way to "redirect" from one page to another.  If
* execution has passed to Page1.main, and the code determines that
* execution must go to Page2.main, then you can issue
*
* <code>
* <div class="PHP">
* objPageMain('Page2');
* </div>
* </code>
*
* This routine will ''not'' wipe out HTML that has been output before it
* is called.  To avoid the HTML from one page appearing on the next,
* be sure to call this routine before HTML has been generated.
*
* INPUTS
*	string $class Class Name
*/
function objPageMain($class) {
   $obj=objPage($class);
   $obj->main();
}

/**
* Returns true if the named file exists in the include path.
*
* INPUTS
*	string $file Filename
* RETURN
*	boolean True if file exists in include path
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
* Ensures that a complete directory path exists by issuing successive
* "mkdir" commands for each segment of New_Path inside of Base_Path.
*
* If Base_Path is "/var/www/localhost/htdocs/app" and New_Path is
* "level1/level2/level3", then this routine issues successive PHP Mkdir
* commands until the complete path
* "/var/www/localhost/htdocs/app/level1/level2/level3" exists.
*
* INPUTS
*	string $Base_Path Base Path
*	string $New_Path Path inside Base Path
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

/**
* Writes the php code required to create array $array to the file
* in directory $dir with filename $name.  Uses $arrayname as the
* variable name storing the array.
*
* INPUTS
*	string $name name of file
*	array $array array to write
*	string $arrayname name of array variable
*	string $dir directory of file (default = '')
*/
function fsFileFromArray($name,$array,$arrayname,$dir='') {
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
   $file = fsDirTop().($dir=='' ? 'dynamic' : $dir)."/$name";
   file_put_contents($file,$retval);
 //  hprint_r($array);
}

/**
* Helper recursive function for fsFileFromArray.  Used to build the
* php array code as a string.  Handles nested arrays and tabs every
* printed key=> element association by $level tabs.
*
* INPUTS
*	array $array
*	int $level
* RETURN
*	string
*/
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
* Reads a line from an open file using PHP fgets(), then removes any CR or
* LF characters, so it can be split in array or otherwise handled w/o
* worries about Unix/Mac/Windows compatibility or stray characters.
*
* INPUTS
*	resource $FILE File Handle
* RETURN
*	string line
*/
function fsGets($FILE) {
   $line=fgets($FILE,5000);
   if(!$line) return $line;
   $line=str_replace(chr(13),'',$line);
   $line=str_replace(chr(10),'',$line);
   return $line;
}

/**
* Adds a slash to the end of a directory if not already present.
*
* INPUTS
*	string $input directory string
*	string $prefix prefix (optional, default '')
* RETURN
*	string directory with added slash
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



/**
* Displays a notice that says "This page is waiting for design".  Intended
* to be used during development for pages that must be viewable by staff
* and clients, but which have not been layed out yet by a designer.  Usually
* a page like this will have plain-vanilla dumps of details from a database,
* so that a designer knows what must appear on the final page.
*
* The notice is put into a DIV block of class "devnotice".  That class
* is defined in the appropriate CSS skin file (default: [[skin_tc.css]]).
*
* INPUTS
*/
function ehFWDevNotice() {
   ?>
   <div class="devnotice">This page is waiting for design</div>
   <?php
}

/**
* Returns a Unix timestamp of the first day of the month.  If a date is
* passed in, returns the first day of that month, else the first day of
* the current month.
*
* INPUTS
*	$dx date input
* RETURN
*	string Unix Timestamp
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
* Returns a Unix timestamp of the first day of the year.  If a date is
* passed in, returns the first day of that month, else the first day of
* the current year.
*
* INPUTS
*	string Date Input
* RETURN
*	string Unix Timestamp
*/
function UTSFirstOfYear($dx=null) {
   if(is_null($dx)) $dx=time();
   $date=SdFromUnixTS($dx);
   return strtotime('01/01/'.substr($date,4,2));
}

/**
* Builds a unix timestamp from a string date.
*
*	string string date
* RETURN
*	string unix timestamp
*/
function unixtsFromSD($sd) {
    return strtotime(
      substr($sd,4,2).'/'
      .substr($sd,6,2).'/'
      .substr($sd,0,4));
}


/**
* Call this function to simulate a successful payment on paypal.  More
* information is available at our [[Paypal]] page.
*
* INPUTS
*	array $paypall Paypall information
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

/**
* INPUTS
* @deprecated
*/
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

/**
* Returns a number-indexed array of values from the
* numbered "column" in an rows array
*
* INPUTS
*	array $arr Array to iterate through
*	int $index Index of column (default 0)
* RETURN
*	array
*/
function arrFromColumn($arr,$index=0) {
    $retval = array();
    foreach($arr as $row) {
        $retval[] = trim($row[$index]);
    }
    return $retval;
}

/**
* Generates an associative array of keys pointing to empty arrays. The
* keys are taken from the input.
*
* INPUTS
*	array $keys keys of associative array
* RETURN
*	array Associative array with $keys pointing to empty arrays
*/
function arrOfArrays($keys) {
   $retval=array();
   foreach($keys as $key) {
      $retval[$key]=array();
   }
   return $retval;
}

/**
* Accepts a [[Row Array]], the haystack, and builds a new Row Array
* using only the keys found in [[List Array]] Needles.
*
* The third parameter, fully_populate, determines what happens when
* an item in Needles is not found in Haystack.  By default the value is
* false and the returned array contains no entry for the missing value.
* If the third parameter is true, the return array contains an empty
* element for the missing value.
*
* INPUTS
*	array &$haystack Haystack
*	array $needles Needles
*	boolean $fullpop True if fully populate
* RETURN
*	array
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

/**
* Pulls the values associated with $keyvaltopnull in the arrays
* associated with the keys in $keylist.  Will take either an
* array or a string as &$source.  If it is a string, each key
* should be seperated by a comma(,).
*
* INPUTS
*	array &$source Array to pull values from
*	mixed $keyvaltopull Value to pull
*	array $keylist List of keys
* RETURN
*	array
*/
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


/**
* Recursive version of built-in function array_Change_key_Case.
* Change case in $array to $case.
*
* INPUTS
*	array $array Array to change case
*	int $case case to change to (default CASE_LOWER)
* RETURN
*	array
*/
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

/**
* Checks to see if associative array &$array contains an association to $key.
* If it does not, assigns $value to $key in the array.  Useful if you do
* not want to overwrite a key if it does exists, but want to add a value.
*
* INPUTS
*	array &$array
*	mixed $key key to check
*	mixed $value value to assign
*/
function arrDefault(&$array,$key,$value) {
   if(!isset($array[$key])) $array[$key]=$value;
}

/**
* Processes an array an unsets any numeric indexes.
*
* INPUTS
* @parm array &$array input
*/
function arrayStripNumericIndexes(&$array) {
   $keys =array_keys($array);
   foreach($keys as $key) {
      if(is_numeric($key)) {
         unset($array[$key]);
      }
   }
}

/**
* Adds prefix $prefix to the begging of all non-numeric keys in $array.
* Returns an array with the prefix-keys and $array values.  If the value
* associated with a key is an array, and you allow recursion, you include
* that array's keys with prefixes into the return value also.
*
* INPUTS
*	array $array
*	string $prefix prefix to add to keys
*	boolean $recurse true if recursion through multidimensional array
*	boolean $lower true if keys go to lowercase
* RETURN
*	array array with keys from $array with prefix added
*/
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
* Processes a [[Rows Array]] and returns an associative array.  The
* resulting array is a simple associative array.  One column is used
* to generate the key values and the other column is used to assign
* values to the array elements.
*
* INPUTS
*	array $rows the rows array
*	string $colkey key column
*	string $colval value column
* RETURN
*	array associative array
*/
function AAFromRows($rows,$colkey,$colval) {
   $aa = array();
   foreach($rows as $row) {
      $aa[$row[$colkey]]=$row[$colval];
   }
   return $aa;
}

/**
* Processes a [[Rows Array]] and returns an [[Associative Rows Array]].
*
* For each row in the input, the value of Key_Column is used as the
* key value in the resulting array.  The individual rows are the same
* in both input and output, only the key is different.
*
* INPUTS
*	array $rows rows array
*	string $colkey key column
* RETURN
*	array
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
* This function runs a report and echos the output directly.  The first
* parameter names the report to run.  The second parameter can be either
* 'HTML' or 'PDF'.
*
* A PDF report is a paged PDF document, while an HTML report is a single
* long document with a header at top and a footer at bottom and the content
* in a scrollable div in the middle.
*
* INPUTS
*	string $report_id report id
*	string $display display type (HTML or PDF)
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
* Takes wiki-formatted text and returns HTML.  The first parameter names the
* table that the wiki pages are stored in, the second parameter names the
* page.
*
* The third parameter instructs the wiki formatter to use the page
* name as the title.  This parameter is by default true.  If you pass in
* a false, there will be no H1 title on the page.
*
* It is assumed that the table of pages has a column 'pagename' and a
* column 'pagewiki'.
*
* The wiki functionality is stored in the class [[x_wiki]].  This function
* instantiates x_wiki and hands processing to that class.
*
* INPUTS
*	string $table_id wiki table
*	string $pagename wiki page
*	boolean $flag_title true to use name as title
* RETURN
*	string
*/
function hWiki($table_id,$pagename,$flag_title=true) {
   include_once('x_wiki.php');
   $wiki=new x_wiki($table_id);
   return $wiki->hWikiFromTable($table_id,$pagename,$flag_title);
}
/**
* Takes wiki-formatted text and returns html.  See hWiki().
*
* @see hWiki()
* INPUTS
*	$text
* RETURN
*	string
*/
function hWikiFromText($text) {
   $table_id='NEVERUSED';
   include_once('x_wiki.php');
   $wiki=new x_wiki($table_id);
   return $wiki->hWikiFromText($text);
}



/**
* This routine generates a menu and stores it with vgaSet('menu').
* For an example of its use, see the source code for the Andromeda
* documentation.
*
* INPUTS
*	string $pageroot
*	string $pn current page
*	array $parents (default array())
*	array $peers (default array())
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
* Accepts a wiki page name, such as "PHP Framework" and generates a
* link to that page, using itself as the caption.
*
* INPUTS
*	string $pagename
*	string $class (optional, default '')
* RETURN
*	string link to wiki page
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
* Replaces the nifty PHP function mime_coment_type which does not exist
* on the gentoo version of PHP due to a misunderstanding between the
* words 'deprecated' and 'eliminate with extreme prejudice'.
*
* There can be no meaningful explanation for why something so simple and
* useful was removed and replaced with something much more complicated.
*
* INPUTS
*	string $filename
* RETURN
*	string
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


/**
* Returns an array with the smallest element being in the first
* index and the largest element being the last.
*
* INPUTS
*	mixed $val1
*	mixed $val2
* RETURN
*	array
*/
function minmax($val1,$val2) {
   return array(min($val1,$val2),max($val1,$val2));
}

/**
* Returns an array with the previous key and the next key
* from $skey in the context data.  If $skey == 0 or if it
* is not found in context data, an array(0,0) is returned.
*
* INPUTS
*	string $table_id
*	int $skey
* RETURN
*	array
*/
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
/**
* Resets the Drilldown storage
*
* INPUTS
*/
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

/**
* Gets the current drill down level.  If there is none,
* the level is 0.
*
* RETURN
*	int
*/
function DrilldownLevel() {
	return ContextGet('drilldown_level',0);
}

/**
* Fetches the drilldown variable at level $level
*/
function DrilldownGet($level) {
	return ContextGet("drilldown_".$level);
}

/**
* Gets the topmost drilldown variable
*/
function DrillDownTop() {
   $dd=ContextGet("drilldown");
   return array_pop($dd);
}

function DrilldownValues($nesting=0) {
	$level=DrilldownLevel()-$nesting;
	return DrilldownGet($level);
}

/**
* Returns a [[Row Array]] specifying the columns to match to produce
* a drilldown resultset in a child table.
*
* INPUTS
* RETURN
*	array
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
/**
* Wrapper for DynamicSave.
*
* INPUTS
* @see DynamicSave()
*/
function DynFromh($filename,$contents) {
   DynamicSave($filename,$contents);
}

/**
* Saves $contents to $filename, assuming that $filename is a dyamic
* file.
*
* INPUTS
*	string $filename name of file
*	string $contents information to save
*/
function DynamicSave($filename,$contents) {
	$FILE=fopen($GLOBALS["AG"]["dirs"]["dynamic"]."/".$filename,"w");
	fwrite($FILE,$contents);
	fclose($FILE);
}

/**
* Returns the data held in file with file name $filename.  Assumed
* that file with $filename is a dynamic file.  If file doesn't exist,
* an empty string is returned.
*
* INPUTS
*	string $filename name of file
* RETURN
*	string data held in file
*/
function DynamicLoad($filename) {
	$file = $GLOBALS["AG"]["dirs"]["dynamic"]."/".$filename;
	if (file_exists($file))
		return file_get_contents($file);
	else
		return "";
}

/**
* Clears file with filename $filename.  Assumes that
* file is a dynamic file (inside the dynamic directory).
*
* INPUTS
*	string $filename name of file
*/
function DynamicClear($filename) {
	$file = $GLOBALS["AG"]["dirs"]["dynamic"]."/".$filename;
	if (file_exists($file)) unlink($file);
}

/**
* Returns data held in file with $filename.  Wrapper function for
* DynamicLoad().
*
*	string $filename
* RETURN
*	string data in file
*/
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
* Looks for the cached elemented named by $filename.  If found, returns it,
* if not found returns an empty array.
*
* Expects the element to be an array.  Saving a scalar value and then
* using this function to retrieve it produces undefined results.
*
* INPUTS
*	string $filename key
* RETURN
*	array
*/
function aFromDyn($filename) {
   $serialized=DynamicLoad($filename);
   if(empty($serialized)) return array();
   else return unserialize($serialized);
}

/**
* Caches an array for later retrieval by [[aFromDyn]].  The cache is
* visible to all users in all sessions.
*
* INPUTS
*	string $filename the key
*	array $contents
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

/**
* Returns the contents of a dynamic file with name
* $name.
*
*	string $name filename
*/
function CacheRead($name) {
   return DynamicLoad($name);
}

/**
* Writes $value to dynamic file with name $name.
*
*	string $name filename
*	string $value data
*/
function CacheWrite($name,$value) {
   DynamicSave($name,$value);
}

# ===================================================================
#
# JQUERY FUNCTIONS
#
# ===================================================================
/**
* Stores a fragment of script to run inside of
* jquery's documentReady() action.
*
*	* string   Script.  May be enclosed in html SCRIPT tags,
*   which you may want to do if your editor makes
*   it easier to work with it that way.
*   * boolean insert. If true, inserts script at top of list
*
* RETURN
*	false   Always returns false.
*/
function jqDocReady($script,$insert=false) {
    $script = preg_replace("/<script>/i",''  ,$script);
    $script = preg_replace("/<\/script>/i",'',$script);
    $jdr = vgfGet('jqDocReady',array());
    if($insert) {
        array_unshift($jdr,$script);
    }
    else {
        $jdr[] = $script;
    }
    vgfSet('jqDocReady',$jdr);
    
    # KFD 10/15/08. If we are in a json call, send this back
    #               as script instead
    if(gp('json')==1) {
        x4Script($script,$insert);
    }
    return false;
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

/**
* Sanitizes the html input $v.  Is a wrapper function for the
* php function htmlentities().
*
* INPUTS
*	string $v html to sanitize
* RETURN
*	string sanitized html
*/
function hSanitize($v) {
   return htmlentities($v);
}

/**
* Wrapper function for HTML_Format().
*
* INPUTS
* @see HTML_Format
*	string $t Type_id
*	mixed $v value
*/
function hFormat($t,$v) {
   return HTML_Format($t,$v);
}

/**
* Returns the value in generic format suitable for the type.
*
*	string $t type id
*	mixed $v value
* RETURN
*	formatted value
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
         return date('m/d/Y h:i:s',$v);
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
/**
* INPUTS
* @deprecated Elimination imminent
*/
function hHidden($key,$value) {
   return html_hidden($key,$value);
}
/**
* INPUTS
* @deprecated Elimination imminent
*/
function HTML_Hidden($key,$value) {
	return
		'<input type="hidden" '.
		' name="'.$key.'" id="'.$key.'" '.
		' value="'.$value."\"/>\n";
}

/**
* Creates an HTML Dropdown box element (select element).
*
* INPUTS
*	string $name name of selection
*	array $resall properties for the options
*	string $value key for value for options
*	string $inner key for inner text for options
* RETURN
*	string Generated HTML
*/
function HTML_Dropdown($name,$resall,$value="value",$inner="inner") {
	$retval = "<select id=".$name." name=$name>";
	foreach ($resall as $row) {
		//echo "Reading a row";
		$retval .= "<option value=\"".$row[$value]."\">".$row[$inner]."</option>";
	}
	$retval.= "</select>";
	return $retval;
}


function hDateUSFromSD($sd) {
   return
      intval(substr($sd,4,2)).'/'
      .intval(substr($sd,6,2)).'/'
      .intval(substr($sd,0,4));
}

// ==================================================================
// HTML Element Generation
// ==================================================================

/**
* Builds URL parameters from an associative array of keys and
* values.  Builds URL parameters like so: http://url.com?key=value&key=value&etc=.
*
*	array $array associative array
*	string $prefix prefix for keys
* RETURN
*	string
*/
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
* accepts an [[Array of Rows]] and returns a list of HTML TR elements,
* where each row is a TR, and each element of each array becomes an HTML
* TD element.
*
* Note that the keys of the [[Row Array]]s are not used, so they
* can actually be [[List Array]]s.
*
* Each table element is of class "CSS_class", or no class if the first
* parameter is blank.  The first parameter may be blank, but it must
* be provided.
*
* INPUTS
*	string $class CSS Class (default = '')
*	array $rows Array of Rows
* RETURN
*	string Generated HTML
*/
function hTBodyFromRows($class='',$rows) {
   $retval='';
   foreach($rows as $row) {
      $retval.=hTRFromArray($class,$row);
   }
   return $retval;
}


/**
* Returns a complete TR row, with each element of the Row array
* becoming an HTML TD element.  Each TD is assigned the CSS class name
* of CSS_Class unless the first parameter is an empty string.
*
* INPUTS
*	string $class CSS Class
*	array $array Array of Rows
* RETURN
*	string Generated HTML
*/
function hTRFromArray($class,$array) {
   $retval="\n<tr>";
   foreach($array as $value) {
      $retval.=hTD($class,$value);
   }
   return $retval."\n</tr>";
}

/**
* html string = <b>hTDsFromArray</b>($class,$array)
*
* Returns one or more TD elements row, with each element of the Row array
* becoming an HTML TD element.  Each TD is assigned the CSS class name
* of CSS_Class unless the first parameter is an empty string.
*
* INPUTS
*	string $class CSS Class
*	array $array
* RETURN
*	string Generated HTML
*/
function hTDSFromArray($class,$array) {
   $retval='';
   foreach($array as $value) {
      $retval.=hTD($class,$value);
   }
   return $retval;
}

/**
* Creates a sortable table using $cols as each column.  $cols should
* be an associative array where the column name is the key and the inner
* html the value.  Hidden variables are used to keep the sort value.
*
*	string $table_id table name
*	array $cols columns
*	string $class CSS Class (default = 'dhead')
* RETURN
*	string Generated Table
*/
function hTableSortable($table_id,$cols,$class='dhead') {
   // Since the table will be sortable, make a hidden variable
   // to keep the sort value
   $hid='gp_ob_'.$table_id;
   hidden($hid,'');

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
* Generates and returns one or more TR elements, where the rows alternate
* between CSS_Class1 and CSS_Class2.  If the two classes have different
* background colors, this produces alternating colored rows for the table,
* which some people find easier to read.
*
* The first parameter is an [[Array of Rows]].  Each [[Row Array]] becomes
* a complete HTML TR element.  The individual elements of each Row become
* HTML TD elements.
*
* The class assignments are made to the TD elements.
*
* INPUTS
*	array $rows Rows Array
*	string $class1 CSS Class 1
*	string $class2 CSS Class 2
* RETURN
*	string Generated HTML
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

/**
* Creates an HTML SQL formatted timestamp from SQL Timestamp $val
*
*	string $val
* RETURN
*	string SLQ HTML Timestamp
*/
function HTML_SQLTimestamp($val) {
	return date('Y-m-d h:i:s A',X_SQLTS_TO_UNIX($val));
}

/**
* Creates an HTML Unix Timestamp from the unix timestamp $val
*
*	string $val unix timestamp
* RETURN
*	string HTML Unix Timestamp
*/
function HTML_UnixTimestamp($val) {
	return date('Y-m-d h:i:s A',$val);
}


function HTML_TIMESTAMP($date) {	return date("d-M-Y h:i:s a",$date); }

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
* Allows you to specify HMTL "Accesskey" for a hyperlink by putting a
* backslash into the caption, so that "\Example" returns "<u>E</u>xample"
* and 'accesskey="E"'.
*
* Accepts a string, examines the string for a backslash character.  If
* one is found, it removes the backslash and underlines the character
* immediately after.
*
* Returns the an array of two elements, first is the modified caption and
* the next is an HTML fragment 'accesskey="X"' where 'X' is whatever character
* was right after the backslash.
*
* If there is no backslash in the caption, then the caption is returned
* unchanged and the accesskey is empty.
*
* INPUTS
*	string $caption caption for <a> element
* RETURN
*	array (HTML_Caption,HTML_Parm_Accesskey)
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
* Returns a the three-letter name of the month, capitalized.
*
* INPUTS
*	int $xmonth Month Number (1-12)
* RETURN
*	string Month Name 3 Letter
*/
function dMonFromNum($xmonth) {
   $arr=array(''
      ,'Jan','Feb','Mar','Apr','May','Jun'
      ,'Jly','Aug','Sep','Oct','Nov','Dec'
   );
   return $arr[$xmonth];
}

/**
* Accepts a given date, and returns the last day of the month that
* includes "Months" number of months.  Passing in '1/1/2007' and '1'
* returns '1/31/2007'.  Passing in '6/1/2007' and 3 returns '8/31/2007'.
*
* Useful for building SQL queries that include ranges of dates.
*
* The first parameter can either be a unix ts or a string that will
* successfully convert to a unix ts, such as '1/1/2007' or '2007-01-01'.
*
* INPUTS
*	string $datein unix_ts/String Start Date
*	int $months months
* RETURN
*	string unix_ts End Date
*/
function dMonthEnd($datein,$months) {
   $dateout=dEnsureTS($datein);
   $dateout=dEnsureTS( date('m/1/Y',$dateout) );
   $dateout=strtotime("+ $months months",$dateout);
   return strtotime("-1 day",$dateout);
}

/**
* Accepts a variable and returns a unix timestamp.  If the value passed in
* is a number, the number is returned unchanged.  If the value is a string,
* it is converted via strtotime.
*
* Useful for writing resilient code when input values are not reliably
* one or the other.
*
* INPUTS
*	string $datein unix_ts/string date
* RETURN
*	string unix_ts
*/
function dEnsureTS($datein) {
   if(is_numeric($datein)) return $datein;
   else return strtotime($datein);
}


/**
* Returns the full name of a month, capitalized.
*
* INPUTS
*	int $xmonth Month Number
* RETURN
*	string Month Name
*/
function dMonthFromNum($xmonth) {
   $x=array(''
      ,'January','February','March','April','May','June'
      ,'July','August','September','October','November','December'
   );
   return $x[$xmonth];
}

/**
* Simple shorthand for php date function, date('Y',time());
*
* INPUTS
* RETURN
*	int year
*/
function dYEar() {
   return date('Y',time());
}


// ==================================================================
// File Functions.  Mixed new and old
// ==================================================================

/**
* Gets the filename for the path $filespec
*
* INPUTS
*	string $filespec path
* RETURN
*	string filename
*/
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

/**
* Gets the extension to the path $filespec.  If no extension found,
* returns ''.
*
* INPUTS
*	string $filespec path
* RETURN
*	string extension to $filespec
*/
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
  *  INPUTS
  *  @example  $complete = scAddSlash($dir).$filename;
  * 	   string	$path
  *  RETURN
*	  string
  */
function raxAddSlash($path) {
   $path = trim($path);
   return $path. (substr($path,-1,1)=='/' ? '' : '/');
}
function scAddSlash($path) { return raxAddSlash($path); }

// ==================================================================
// String clipping functions
// ==================================================================

/**
* Clips off the string $item off of the beginning of the
* string $input.  If $item does not exist at the beginning of the string,
* it returns the string as it was.
*
* INPUTS
*	string $input input string
*	string $item string to remove from beginning of $input
* RETURN
*	string original string or string with $item removed from the beginning
*/
function scClipStart($input,$item) {
   $len = strlen($item);
   if (substr($input,0,$len)==$item) {
      $input = substr($input,$len);
   }
   return $input;
}

/**
* Clips off the string $item out of the string and returns
* everything from the string until that point.  If the $item
* is not at the very end of the string, you will lose the rest of
* the string after the $item, including the $item.
*
* INPUTS
*	string $input string input
*	string $item item to clip
* RETURN
*	string string without $item at the end
*/
function scClipAfter($input,$item) {
   if (strpos($input,$item)!==false) {
      $input = substr($input,0,strpos($input,$item));
   }
   return $input;
}

// ==================================================================
// Type conversion functions
// ==================================================================
/**
* Converts an SQL timestamp taken from the database to a
* unix timestamp and returns it.
*
* INPUTS
*	string $dttm2timestamp_in database timestamp
* RETURN
*	string Unix Timestamp
*/
function X_SQLTS_TO_UNIX($dttm2timestamp_in){
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

/**
* Converts a Unix timestamp to an SQL database timestamp and returns
* it.  Has the option to skip quotes or not.
*
* INPUTS
*	string $dt timestamp
*	boolean $skipquotes
* RETURN
*	string sql timestamp
*/
function X_UNIX_TO_SQLTS($dt,$skipquotes=false) {
	if ($skipquotes) { $q=""; } else { $q="'"; }
	return $q.date("Y-m-d h:i:s a",$dt).$q;
}

/**
* Converts a unix timestamp to an sql date.  Has
* option to skip quotes.
*
* INPUTS
*	string $dt unix timestamp
*	boolean $skipquotes
* RETURN
*	string sql date
*/
function X_UNIX_TO_SQLDATE($dt,$skipquotes=false) {
	if ($skipquotes) { $q=""; } else { $q="'"; }
	return $q.date("Y-m-d",$dt).$q;
}



// ==================================================================
// Options out of DD tables
// ==================================================================
# ===============================================================
# Table triggers
# ===============================================================

function variables_writeAfter($row) {
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
    # KFD 6/12/08, eliminate caching on disk, it is problematic
    #              in x4 and we want to replace it with memcached
    if($varname=='X') {
        unlink($GLOBALS['AG']['dirs']['dynamic'].'table_variables.php');
        unset($GLOBALS['AG']['table_variables']);
    }
    if(!file_exists_incpath('table_variables.php')) {
        # if not connected, connect now
        $dbconnected = SQLConnected();
        if(!$dbconnected) SQL_ConnPush();
        variables_writeAfter(array());
        if(!$dbconnected) SQL_ConnPop();

        /*
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
        */
    }
    include('table_variables.php');
    return ArraySafe($GLOBALS['AG']['table_variables'],trim($varname),$default);
}




/** Use rows of a table to simulate columns
  *
  * Takes a table name, one column to pull for values
  * and other to pull as description, and makes a data
  * dictionary entry as "x_<table_id>" so that you can
  * make inputs, read inputs, and so forth.  Originally
  * created to flesh out uses of the "fullpop" flag
  *
  * INPUTS
  *	string $table_id table name
  *	string $colcoldesc column with values
  *	string $colinfo column with data
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
      //$g2use = substr($geff,0,strlen($geff)-5).substr($g2use,-5);
      $g2use = $table_id.'_v_'.substr($g2use,-5);
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
/**
* Checks to see if date $input is valid.  It is only valid if
* it has 3 sections (month, day, year), and if the dates exists(no feb
* 33).
*
* INPUTS
*	string $input date input
* RETURN
*	boolean is it valid?
*/
function CheckTextDate($input) {
	$arr = explode("/",$input);
	if (Count($arr)!=3) { return false; }
	else { return checkdate($arr[0],intval($arr[1]),intval($arr[2])); }
}

/**
* The subroutine that eats like a meal.  Does everything with
* the data-oriented posted variables, independent of what page
* we are on or going to.  Stores search criteria, does updates,
* deletes, inserts.
*
* INPUTS
*/
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
         $table=DD_TableRef($table_id);
         #hprint_r($changed);
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
function rowsFromUserSearch(&$table,$lcols=null,$matches=array(),$child=false) {
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

    /* DJO 8-15-2008 Added to allow config override of rows
     * returned for a child table displayed with parent
     */
    if ( $child ) {
        $gp_rpp   = configGet( 'sql_limit', ConGet('table',$table_id,'rppage',25) );
    } else {
        $gp_rpp   = ConGet('table',$table_id,'rppage',25);
    }
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

    /* DJO 8-15-2008 Added to allow config override of rows
     * returned for a child table displayed with parent
     */
    if ( $gp_rpp == 0 ) {
        $skeysl = $skeys;
    } else {
        $skeysl = array_slice($skeys,($gp_spage-1)*$gp_rpp,$gp_rpp);
    }
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
            $sw[]='('.sqlFilter($colinfo,$tcv).')';
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
    /**
    * DJO 8-15-2008 No longer needed because of the Config System
    */
    //$SQL_Limit = OptionGet( 'SQL_LIMIT', vgaGet( 'SQL_Limit', 300 ) );
    $SQL_Limit = configGet('sql_limit',300);

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
/**
*  DEPRECATED.  Copied this to SQLFilter() and am going from there
* INPUTS
* @deprecated Copied to SQLFilter()
*/
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
* Use this routine to register a form control and its value.  The information
* about the control is saved in the [[Context]].
*
* Returns the name of the control.  Use this as the HTML name property when
* putting the control onto the form.
*
* INPUTS
*	string $table_id
*	int $skey
*	string $colname
*	mixed $colvalue
* RETURN
*	string Control Name
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
* This function is new as of 3/9/07, and not yet fully populated.  At the
* time of its creation, all widget generation is in [[ahInputsComprehensive]],
* with no ability to generate individual widgets as needed.  This will be
* added to as needed to supply the various types.
*
* If the type_id is cbool, then the HTML "value" property is always Y, and
* the third parameter is taken to be the caption.
*
* INPUTS
*	string $type_id
*	string $name
*	string $value (optional, default = '')
*	array $opts (optional, default = array())
* RETURN
*	string
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
/**
* @deprecated
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
       # HACK KFD 6/2/08.
       if ($colname=='') continue;
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
   #hprint_r($context_row);
   ContextSet("OldRow",$context_row);


   // *****  STEP 2 OF 3: HTML Decisions and derivations
   // Decide which kind of control to use for each one, and
   // do any overrides and extra stuff that may be necessary
   foreach($acols as $colname) {
       # HACK KFD 6/2/08.
       if ($colname=='') continue;
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
         case 'mime-h-f':
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
       # HACK KFD 6/2/08.
       if ($colname=='') continue;

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
/**
* @deprecated
*/
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
/**
* Displays message and wraps it in header tags with level
* $level if a level is passed.  Then flushes the buffer.
*
* INPUTS
*	string $message
*	string $level
*/
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
  *
  *	resource $FILE
  *	string $text
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
  *
  *	reference &$array
  *	string $key
  */
function raxArray(&$array,$key) {
   return isset($array[$key]) ? $array[$key] : '';
}

// ==================================================================
// Function Email Send.  NOT STUB
// DOES NOT LOG.  Requires from and to
// ==================================================================
/**
* Sends an email.  Warning: Does not log the email, and it is not a stub!
* Will not work without $from or $to.
*
* INPUTS
*	string $from
*	string $to
*	string $subject
*	string $body
*	string $headers
*/
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
/****h* PHP API/Email Functions
*
* NAME
*   Email functions
*
* PURPOSE
*
*   There are two email functions, both of which send emails.
*   One of them is to send plaintext emails, the other sends
*   HTML emails.
******/
/****f* Email Functions/EmailSend
*
* NAME
*   emailSend
*
* PURPOSE
*   Sends an a plaintext email.
*
* INPUTS
*   Accepts these inputs, all three required:
*   * to 
*   * subject 
*   * message body
*
*
* DEPENDS
*   Automatically includes these files:
*   * ddtable_adm_emails.php
*   * x_email.php
*
******/
function EmailSend($to,$subject,$message,$headers=array()) {
    ddTable('adm_emails');
	#include_once("ddtable_adm_emails.php");
	include_once("x_email.php");
	return X_EMAIL_SEND(
		ARRAY(
			"email_to"=>trim($to)
			,"email_subject"=>trim($subject)
			,"email_message"=>trim($message)
            ,'headers'=>$headers
		)
	);
}

/****f* Email Functions/EmailSendHTML
*
* NAME
*   emailSendHTML
*
* PURPOSE
*   Sends an an HTML email.
*
* INPUTS
*   Accepts these inputs, all three required:
*   * to 
*   * subject 
*   * message body
*
* NOTES
*   The subject body does not need to include the HTML
*   and BODY tags, those are added by this routine.
*
*   There is no provision for multi-part emails that
*   include a text portion also.
*
*
* DEPENDS
*   Automatically includes these files:
*   * ddtable_adm_emails.php
*   * x_email.php
*
******/
function EmailSendHtml($to,$subject,$message) {
	include_once("ddtable_adm_emails.php");
	include_once("x_email.php");
    
    $message = "<html><body>".$message."</body></html>";
	return X_EMAIL_SEND(
		ARRAY(
			"email_to"=>trim($to)
			,"email_subject"=>trim($subject)
			,"email_message"=>trim($message)
            ,'headers'=>array(
                'MIME-Version'=>'1.0'
                ,'Content-type'=>'text/html; charset=iso-8859-1'
            )
		)
	);
}



function XML_RPC($callcode,$arr_inputs) {
	global $AG;
	$AG["xmlrpc"] = ARRAY("callcode"=>$callcode,"inputs"=>$arr_inputs);
	include("x_xmlrpc.php");
}

/**
* Generates an editor textarea with name $name
* and inner text $value.
*
*	string $name
*	reference &$value
*/
function ehFCKEditor($name,&$value) {
   $x=$name;$x=$value; //annoying jedit compile warning
   ?>
   <textarea rows=15 cols=50 style="width: 100%" name="<?php echo $name?>" id="<?php echo $name?>"><?php echo $value?></textarea>
    <script language="javascript">
      editor_generate('<?php echo $name?>'); // field, width, height
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

/**
* Builds a string of HTML option elements from an array
* $rows.  After return, string is ready to be placed
* inside <select> elements.
*
*	array $rows array
*	mixed $current currently selected option
*	int $colval column with values
*	mixed $coldis columns with display
* RETURN
*	string Generated Option Elements
*/
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

/**
* Builds a string of option elements from a data dictionary
* table.  String is ready to be inserted into a <select> element
* upon return.
*
*	string $table_id
*	string $curval
*	string $firstletters
*	array $matches
*	string $distinct
* RETURN
*	string Generated HTML option element string
*/
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
* Looks up a table's "dropdown" projection definition, then finds the row
* for the given PK_Value, and displays the columns named in the dropdown
* projection.
*
*	string $table_id
*	string $pkval
* RETURN
*	string
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
* Maintains a counter of TABINDEX values and returns the next one to
* be used.
*
* Used by [[ahInputsComprehensive]].  Can be used whenever inputs are
* being generated and you want them to have a precise tab order.
*
*	int $offset (default 0)
* RETURN
*	int
*/
function hpTabIndexNext($offset=0) {
   $tabindex=vgfGet('tabindex',0)+1;
   vgfSet('tabindex',$tabindex);
   return $tabindex+$offset;
}


/**
* Generates a string of HTML OPTION elements out of a [[Rows Array]], suitable
* for inclusion into an HTML SELECT element.
*
* The first paremeter is a [[Rows Array]].  The second parameter names the
* column that is used to set the value properties of each OPTION element,
* the second parameter names the column used to set the innerHTML of each
* OPTION element.
*
*	array $rows
*	string $col_value value for option element
*	string $col_inner Inner HTML
* RETURN
*	string Generated HTML
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

/**

*/
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

/**
* Creates a selection element here multiple options are able to be
* selected at once.  Accepts a rows array as option data storage.
*
*	string $name
*	array $rows
*	string $colkey
*	mixed $colval
*	string $selected
*	string $extra
* RETURN
*	string Generated HTML selection element
*/
function hSelectMultiFromRows($name,$rows,$colkey,$colval,$selected='',$extra='') {
   $aa = AAFromRows($rows,$colkey,$colval);
   return hSelectMultiFromAA($aa,$name,$selected,$extra);
}

/**
* Creates a selection element out of rows array $rows.
*
*	string $name
*	array $rows
*	string $colkey
*	mixed $colval
*	string $selected
*	string $extra
* RETURN
*	string Generated HTML selection element
*/
function hSelectFromRows($name,$rows,$colkey,$colval,$selected='',$extra='') {
   $aa = AAFromRows($rows,$colkey,$colval);
   return hSelectFromAA($aa,$name,$selected,$extra);
}

/**

*/
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

/**
* Creates a <select> block by building option values from the
* associative array and then passing the option elements
* to hSelect in order to make a full <select> element.
*
* Associative array should be in a format with the "value" of the
* option as the key, and the inner text as the value.
*
*	array $aa associative array
*	string $name
*	string $selected
*	string $extra
* RETURN
*	string Generated <select> block
*/
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

/**
* Builds a select block where multiple options can be selected
* at once.  The select block is build from the associative array
* $aa.
*
* Associative array should be in a format with the "value" of the
* option as the key, and the inner text as the value.
*
*	array $aa associative array
*	string $name name of the select block
*	string $selected selected option value
*	string $extra extra properties for the select element
* RETURN
*	string Generated HTML Select element
*/
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

/**
* Builds a select block with $inner as the inner
* option elements.
*
*	string $name
*	string $value
*	string $inner
*	string $extra
* RETURN
*	string Generated Select Element
*/
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

/**
* Builds a select element where more than one option
* can be selected at one time.  It uses $inner as the
* inner option elements.
*
*	string $name
*	string $value
*	string $inner
*	string $extra
* RETURN
*	string Generated select element
*/
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

/**
* Builds a hypertext link to the get/post page $page
* with $caption as the inner html of the <a> element.
* Passes $skey as a $_GET variable
*
*	string $page pagename
*	string $caption inner <a> html
*	string $skey
* RETURN
*	string Generated HTML
*/
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

/**
* Returns the row in the associative array $arr where the keyname
* is equal to $keyname and the value associated with that key is
* equal to $keyvalue.  Returns false if it cannot find the row.
*
*	reference &$arr
*	string $keyname
*	string $keyvalue
* RETURN
*	mixed
*/
function raxarr_FindRow(&$arr,$keyname,$keyvalue) {
   $keys = array_keys($arr);
   foreach ($keys as $key) {
      if ($arr[$key][$keyname]==$keyvalue) {
         return $arr[$key];
      }
   }
   return false;
}

/**
* Includes an array that was written to a file.  $filename is the
* name of the file.  Returns an empty array if the file is not found.
*
*	string $filename name of file
* RETURN
*	array
*/
function raxIncludeArray($filename) {
   $temp=array();
   @include($filename);
   return $temp;
}

/**
* Returns the first non-null, non-empty string value in the
* array $args.  If there is no such value found, function returns
* an empty string ''.
*
*	array $args
* RETURN
*	mixed first non-null non-emptystring value
*/
function raxCoalesce($args) {
   foreach ($args as $arg) {
      if (!is_null($arg)) {
         if ($arg<>'') return $arg;
      }
   }
   return '';
}

/**
* Returns the first non-null value starting with the
* gp_skey, and then onto the txt_skey.  If niether are
* non-null, then function returns the default value $default.
*
*	int $default default value
* RETURN
*	mixed first non null value
*/
function CleanCoalesceSkey($default=0) {
   return raxCoalesce(
      array(
         CleanGet('gp_skey','',false)
         ,CleanGet('txt_skey','',false)
         ,$default
      )
   );
}

/**
* Explodes the string $string using delimeter $delim.  Returns an associative array
* where each value from the explode of $string is both a key and a value.
*
*	string $delim delimeter to use
*	string $string string to explode
* RETURN
*	array
*/
function raxExplodeToKeys($delim,$string) {
   $retval = array();
   $arr1 = explode($delim,$string);
   foreach ($arr1 as $value) {
      $retval[$value]=$value;
   }
   return $retval;
}

/**
* Builds an HTML Table from an array of rows.  Can be used for database
* rows, or any other kind of rows array.  Handles both explicit list
* of columns, and suppressed ones.  Columns should be passed in the $args
* parameter.  If title isn't held in the column array in $args, then the
* function takes the column's name as the title.
*
*	reference &$dbrows
*	array $args
* RETURN
*	string Generated HTML Table
*/
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

/**
* A wrapper function for raxFGETS().  It runs fgets() on the passed
* file $F, but then removes all newline and return characters from it.
*
* @see raxFGETS()
*	resource $F file pointer
* RETURN
*	string
*/
function lFGets($F) {
   return raxFGETS($F);
}

/**
* It runs fgets() on the passed file $F, but then removes all newline and
* return characters (\n or \r) from it.
*
*	resource $F file pointer
* RETURN
*	string
*/
function raxFGETS($F) {
   $retval= fgets($F);
   $retval= str_replace("\n","",$retval);
   $retval= str_replace("\r","",$retval);
   return $retval;
}

/**
* Returns a link with $_GET parameters gp_page set to $page
* and gp_skey as $skey.
*
*	string $page
*	string $skey
* RETURN
*	string
*/
function raxLinkBySkey($page,$skey) {
   return 'index.php?gp_page='.$page.'&gp_skey='.$skey;
}

/**
* Removes ".0" from anywhere inside the string $string.
*
*	string $string
* RETURN
*	string
*/
function raxNoDotZero($string) {
   return str_replace('.0','',$string);
}

/**
* Adds the total of the $dest array and $source array for each value
* and stores the total in $dest.  All previous data in $dest gets
* erased and replaced with the total values.
*
*	reference &$dest
*	reference &$source
*/
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
* returns a string in the form YYYYMMDD from a Unix timestamp.
* This type of string has the advantage of being sortable.
*
* The term 'sd' means "string date" from
* an old dbase/Foxpro function StringDate().
*
*	$unix Unix Timestamp
* RETURN
*	string string date
*/
function sdFromUnixTS($unix_ts=null) {
   if(is_null($unix_ts)) $unix_ts=time();
   return date('Ymd',$unix_ts);
}

/**
* Removes all zeroes from $value
*
*	string $value
* RETURN
*	string no zeroes
*/
function NoZeroes($value) {
   if(intval($value)==0) return '';
   else return str_replace('.0','',$value);
}

/**
* Checks if $file is in the include path.  If it is,
* then includes the file.
*
*	string $file
*/
function include_incpath($file) {
   if(file_exists_incpath($file)) {
      include($file);
   }
}

/**
* Checks to see if $value is valid.  Value is valid if it only includes
* letters a-z lowercase, and digits 0-9 and underscore.  Returns false
* if invalid.
*
*	string $value
* RETURN
*	boolean
*/
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


/**
* Stores the current time
*/
function eTimeBegin() {
   if (!isset($GLOBALS['AG']['etimes'])) $GLOBALS['AG']['etimes']=array();
   $GLOBALS['AG']['etimes'][] = time();
}

/**
* Returns the difference between the current time and the most
* recently stored time.  If the stored times has not been
* initialized, then the function returns -1.  If there are no stored
* times, then it returns -2.
*/
function eTimeEnd() {
   if (!isset($GLOBALS['AG']['etimes'])) return -1;
   if (count($GLOBALS['AG']['etimes'])==0)  return -2;
   return time() - array_pop($GLOBALS['AG']['etimes']);
}

/**
* Gets the first match between $pattern and $subject.  If there is
* no match, returns an empty string ''.
*
*	string $pattern regular expression pattern
*	string $subject string to search
* RETURN
*	string matched value
*/
function strFrompreg_match($pattern,$subject) {
   $matches=array();
   preg_match($pattern,$subject,$matches);
   return ArraySafe($matches,0,'');
}

/**
* Gets the second match between $subject and $pattern.  If there was no second
* match, returns an empty string ''.
*
*	string $pattern regular expression pattern
*	string $subject string to look for matches
* RETURN
*	string matched value
*/
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
* Accepts an array of [[TableRows]] and returns the HTML ready to go to
* the screen.
*
*	reference &$array
* RETURN
*	string Generated HTML
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
* Accepts a [[Rows Array]] and returns a [[TableRows]] array.  None of the
* cells of the [[TableRows]] array will have a class assignment.  This is
* very important, as some routines such as [[TableRowsClassAlternate]] will
* not override an existing class assignment.  Because of those routines that
* do not override an existing class assignment, this routine makes no class
* assignment.
*
* This routine is normally used for body data, not for the row of header
* cells.
*
*	array $rows
* RETURN
*	array Table Rows array
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
* Accepts a numerically indexed array a [[TableRows]] array containing one
* inner row.
*
* This routine is normally used for header cells, so that they can be
* output using [[hTableRowsRender]].
*
*	array $array
* RETURN
*	array
*/
function TableRowFromArray($array) {
   $retval=array();
   foreach($array as $index=>$value) {
      $retval[$index]=array('v'=>$value);
   }
   return array($retval);
}


/**
* Accepts a [[TableRows]] array by reference, and applies a single class
* to all cells.  If the third parameter is true, it will override any
* existing class assignments, else it will leave them alone.
*
*	reference &$rows
*	string $class css class
*	boolean $override (default = false)
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
* Accepts a [[TableRows]] array by reference, and applies a single class
* to one column for all rows.  If the third parameter is true,
* it will override any
* existing class assignments, else it will leave them alone.
*
*	reference &$rows
*	string $colkey
*	string $class css class
*	boolean $override (default = false)
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
* Accepts a [[TableRows]] array by reference, and applies alternating CSS
* classes CSS_Class1 and CSS_Class2 to the rows.
*
* If the third parameter is true, it will override any
* existing class assignments, else it will leave them alone.
*
*	reference &$rows
*	string $class1 css class 1
*	string $class2 css class 2
*	boolean $override (default = false)
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

/**
* @deprecated
*/
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
* This is an experimental routine.
*
* This routine is used by public websites that accept user registration
* information.  The idea is that after you create their account you call
* this routine to log them in, saving them the annoyance of having to
* re-type their username/password at a login screen.
*
*	string $UID User ID
*	string $PWD Password
*/
function Login($UID,$PWD) {
   // Make it look like the UID and PWD were passed in on the
   // request, that's where x_login wants to find them.
   gpSet('loginUID',$UID);
   gpSet('loginPWD',$PWD);

   // Create and run the login object
   $obj_login = DispatchObject('x_login');
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
* When this is called on a system using Point-of-Sale [[Security]], any
* further action will require a user to authenticate again.  This is
* usually done after a sales order is saved, or a credit memo made, or
* any other type of transaction is completed and the terminal is expected
* to be left open for the next user.
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
/**
* @deprecated
*/
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
        if($ahcol['type_id']=='mime-h'  || $ahcol['type_id'] == 'mime-h-f') {
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
/**
* @deprecated
*/
function jsValues($ahcols,$name,$row,$h) {
   foreach($ahcols as $colname=>$ahcol) {
       $h = jsValuesOne($ahcols,$colname,$ahcol,$name,$row,$h);
   }
   return $h;
}
/**
* @deprecated
*/
function jsValuesOne($ahcols,$colname,$ahcol,$name,$row,$h) {
    // KFD 9/7/07, slip this in for mime-h columns, they are
    //             much simpler.
    if($ahcol['type_id']=='mime-h' || $ahcol['type_id'] == 'mime-h-f' ) {
       $dir = $GLOBALS['AG']['dirs']['root'];
       @include_once($dir.'/clib/FCKeditor/fckeditor.php');
       $oFCKeditor = new FCKeditor($name.$colname);
       $oFCKeditor->BasePath   = 'clib/FCKeditor/';
       $oFCKeditor->ToolbarSet = ( $ahcol['type_id'] == 'mime-h' ? 'Basic' : 'Default' );
       $oFCKeditor->Width  = ( $ahcol['type_id'] == 'mime-h' ? '275' : '470' );
       $oFCKeditor->Height = ( $ahcol['type_id'] == 'mime-h' ? '200' : '400' );
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
            $colvalue=date('m/d/Y h:i A',dEnsureTS($colvalue));
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
            #E*lementAdd('ajax',"_script|ob('$name$colname').value='$colvalue'");
        }
        else {
            jqDocReady("ob('$name$colname').value='$colvalue'");
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
     #E*lementAdd('ajax',"_script|$scr");
     #E*lementAdd('ajax',"_script|ob('$name$colname').className='x3err'");
    }
    $h=str_replace($name.$colname.'--ERROR--',$herr,$h);


    // ------------------------------------
    // Infinity plus one, register the clear
    // ------------------------------------
    $x="ob('$name$colname')";
    #
    #ElementAdd('clearBoxes',"if($x) { $x.value='' }");
   return $h;
}

/**
* @deprecated
*/
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
      if($ahcol['type_id']=='mime-h' || $ahcol['type_id'] == 'mime-h-f') {
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

/**
* @deprecated
*/
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

/**
* @deprecated
*/
function AHColsNames(&$ahcols,$name,$tabindex) {
   foreach($ahcols as $colname=>$ahcol) {
      AHColNamesOne($ahcols,$colname,$name,$tabindex);
   }
}

/**
* @deprecated
*/
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
/**
* @deprecated
*/
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
/**
* @deprecated
*/
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
/**
* @deprecated
*/
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
    case 'mime-h-f':
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
        if($acol['type_id'] <> 'time') {
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
    }

   // ------------------------------------
   // Big deal B), foreign keys
   // ------------------------------------
   if($acol['table_id_fko']<>'' && $acol['type_id']<>'date') {
      // Says we want an info button next to it
      if($acol['mode']<>'search') {
         $acol['html_right']
            .="<a tabindex=999 href=\"javascript:Info2('"
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
        .(( $acol['type_id'] == 'mime-h' || $acol['type_id'] == 'mime-h-f' ) && $parm=='value'
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


/**
* @deprecated
*/
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
/**
* @deprecated
*/
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
/**
* @deprecated
*/
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
/**
* @deprecated
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
/**
* @deprecated
*/
function aColInfoFromDDColumns(&$table,&$retval) {
   $perm_upd = DDUserPerm($table['table_id'],'upd');
   // ----------------------------------------------
   // BIG DEAL A: Loop through each row
   // ----------------------------------------------
   foreach($table['flat'] as $colname=>$colinfo) {
      if(!isset($colinfo['uino'])) {
         # KFD 6/2/08, this line is required for some older programs
         # that SDS wrote that still use this family of routines.
         # Table constraints appear to be showing up as empty columns!
         if ($colname=='') continue;
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

/**
* @deprecated
*/
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
/**
* @deprecated
*/
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


/**
* @deprecated
*/
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

/**
* @deprecated
*/
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

/**
* @deprecated
*/
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
/**
* @deprecated
*/
function endElement($parser, $name)
{
   global $tree;
   $tree->closeChild();
}
/**
* @deprecated
*/
function characterData($parser, $data) {
    global $tree;

    startElement($parser,'CDATA',array());
    $tree->AddData($data);
    endElement($parser,null);
}
/**
* @deprecated
*/
function cssInclude($file,$force_immediate=false) {
    // This program echos out immediately if not in debug
    // mode, otherwise they all get output as one
    $cssExcludes = vgfGet('cssExcludes',array());
    if ( !in_array( $file, $cssExcludes ) ) {
        if(configGet('js_css_debug','Y')=='Y' || $force_immediate) {
            ?>
            <link rel='stylesheet' href='<?php echo tmpPathInsert().$file?>' />
            <?php
        }
        else {
            $css = vgfGet('cssIncludes',array());
            $css[]=$file;
            vgfSet('cssIncludes',$css);
        }
    }
}
/**
* @deprecated
*/
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
            if(file_exists(fsDirTop().$cssone)) {
                $string.=file_get_contents( fsDirTop().$cssone );
            }
        }
        file_put_contents($file,$string);
    }

    // Finally, put out the file
    ?>
    <link rel='stylesheet'
         href='<?php echo tmpPathInsert()."clib/css-min-$md5.css"?>' />
    <?php
}
/**
* @deprecated
*/
function jqPlugin( $file, $comments='') {
    $jqp = vgfGet('jqPlugins',array());
    $jqp[] = array('file'=>$file,'comments'=>$comments);
    vgfSet('jqPlugins',$jqp);
}
/**
* @deprecated
*/
function jsInclude( $file, $comments='',$immediate=false ) {
    # KFD 11/1/08.  Yet another mod to the meaning of immediate.
    #       It now means to slip it in as the first.  If you use
    #       this for more than one file you must do them in
    #       *reverse* order because the 2nd will go in ahead
    #       of the first and so forth.
    $ajs = vgfGet('jsIncludes',array());
    $newEntry =array(
        'file'=>$file
        ,'comments'=>$comments
        ,'immediate'=>$immediate
    ); 
    if($immediate=='Y') {
        array_unshift($ajs,$newEntry);
    }
    else {
        $ajs[]=$newEntry;
    }
    vgfSet('jsIncludes',$ajs);
}
/**
* @deprecated
*/
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
    $debug = trim(ConfigGet('js_css_debug','N'));
    if(vgfGet('x6')) $debug = 'Y';
    foreach($ajs as $js) {
        $external = false;
        if(substr($js['file'],0,7)=='http://') {
            $external = true;
        }

        if($debug=='N' && $external == false) {
        //if(false) {
            $aj[] = $js['file'];
            if($js['comments']<>'') {
                ?>
                <!--
                <?php echo $js['comments']?>
                -->
                <?php
            }
        }
        else {
            $insert = $external ? '' : tmpPathInsert();
            ?>
            <script type="text/javascript"
                     src="<?php echo $insert.$js['file']?>" >
            <?php echo $js['comments']?>
            </script>
            <?php
        }
    }

    // If they needed minification, we have to work out now
    // what that file will be, maybe generate it, and create
    // a link to it
    //
    # KFD 8/20/08, Now minifying files during the build, so we
    #              grab that file if we can find it.  See below
    /*
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
    */
    if(count($aj)==0) return;
    $list = implode('|',$aj);
    $md5  = substr(md5($list),0,15);
    $file = fsDirTop()."/clib/js-min-$md5.js";

    if(!file_exists($file)) {
        $string = '';
        foreach($aj as $ajone) {
            if(file_exists($ajone.'.mjs')) {
                $string.= file_get_contents(fsDirTop().$ajone.'.mjs');
            }
            else {
                $string.= file_get_contents(fsDirTop().$ajone);
            }
        }
        file_put_contents($file,$string);
    }

    // Finally, put out the file
    ?>
    <script type="text/javascript"
             src="<?php echo tmpPathInsert()."clib/js-min-$md5.js"?>" >
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

function SQLConnected() {
    if(!isset($GLOBALS['dbconn'])) return false;
    if(is_null($GLOBALS['dbconn'])) return false;
    return true;
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

    /*
    if (file_exists( $AG['dirs']['application'] .'pg_overrides.php' ) ) {
        include( $AG['dirs']['application'] .'pg_overrides.php' );
        if ( isset( $pg_overrides ) ) {
            foreach( $pg_overrides as $key=>$value ) {
                $$key = $value;
            }
        }
    }
    */
    $host = '';
    unset($host);
    
    if ($app=="") { $app = $AG["application"]; }
	return
        ( isset($host) ? " host=" .$host : "" )
		." dbname=".$app.
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
    } else {
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
    # KFD 12/31/08, Control by cookie and group setting
    $debug = inGroup('debugging') && arr($_COOKIE,'log_Server',0)==1;
	#$debug = trim(ConfigGet('js_css_debug','N'));
    # KFD 12/31/08  (END)
	if ( $debug =='Y') {
    	$mtime = microtime();
        $mtime = explode(" ",$mtime);
        $mtime = $mtime[1] + $mtime[0];
        $starttime = $mtime; 
    }
	pg_send_query($dbconn,$sql);
	if ( $debug =='Y') {
	    $mtime = microtime();
        $mtime = explode(" ",$mtime);
        $mtime = $mtime[1] + $mtime[0];
        $endtime = $mtime;
        $totaltime = ($endtime - $starttime); 
	}
    
    if ( $debug ) {
        $dbgsql['sql'] = $sql;
        $dbgsql['time'] = $totaltime;
        # KFD 10/18/08, add stack dump if xdebug installed
        if(function_exists('xdebug_get_function_stack')) {
            ob_start();
            var_dump(xdebug_get_function_stack());
            $dbgsql['stack'] = ob_get_clean();
        }
        else {
            $dbgsql['stack'] = 'xdebug not installed, no function '
                .'stack available.';
        }
        array_push( $GLOBALS['AG']['dbg']['sql'], $dbgsql );
    }
	$results=pg_get_result($dbconn);
	$t=pg_result_error($results);
   $error=false;
	if ($t) {
      $error=true;
      vgfSet('errorSQL',$sql);
      // Made conditional 1/24/07 KFD
      //echo "Error title is".vgfGet("ERROR_TITLE");
      if(SessionGet('ADMIN',false)) {
      //if(true) {
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
* Executes a SQL command and retrieves all rows into a [[Rows Array]].
*
* If the second parameter is provided, then the values of the named
* column are made into the keys for the rows in the result.
*
* Extreme care should be taken with this command.  Experience has shown
* that PHP's performance drops dramatically with the size of the result
* set, so much so that anything over 100 rows or so should probably not
* be contemplated for this command.
*
*	string $sql command
*	string $colname column id (default = '')
* RETURN
*	array
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
    # KFD 6/12/08, use new and improved
    errorsClear();
    if(!is_array($table)) $table=DD_TableRef($table);
	$table_id= $table["table_id"];
    $view_id = ddTable_idResolve($table_id);
 	$tabflat = &$table["flat"];

	$new_cols = "";
	$new_vals = "";
	foreach($tabflat as $colname=>$colinfo) {
		if (isset($colvals[$colname])) {
         //if($colvals[$colname]<>'') {
            if (DD_ColInsertsOK($colinfo,'db')) {
                # KFD 6/18/08, % signs really mess things up
                #if(strpos($colvals[$colname],'%')!==false) {
                #    ErrorAdd("The % sign may not be in a saved value");
                #    vgfSet('ErrorRow_'.$table_id,$colvals);
                #    return 0;
                #}
                $cliplen = $clip ? $colinfo['colprec'] : 0;
                $new_cols.=ListDelim($new_cols)." ".$colname;
                $new_vals
                   .=ListDelim($new_vals)." "
                   .SQL_FORMAT($colinfo["type_id"],$colvals[$colname],$cliplen);
            }
         //}
		}
	}
    if(!Errors()) {
        $sql = "INSERT INTO ".$view_id." ($new_cols) VALUES ($new_vals)";
    }
    x4Debug($sql);
    x4Debug(SessionGet('UID'));

    // ERRORROW CHANGE 5/30/07, big change, SQLX_* routines now save
    //  the row for the table if there was an error
    $errflag=false;
    SQL($sql,$errflag);
    if($errflag) {
        vgfSet('ErrorRow_'.$table_id,$colvals);
    }

	$notices = pg_last_notice($GLOBALS["dbconn"]);
    $retval = 0;
	$matches = array();
    # KFD 10/18/08. This venerable line has been quietly working forever,
    #               until today!  The problem turned out to be the table
    #               name had a number in it, which screwed it up!  So
    #               I've changed one line here.
	#preg_match_all("/SKEY(\D*)(\d*);/",$notices,$matches);    
    preg_match_all("/SKEY(.*\s)(\d*);/iu",$notices,$matches);
	if(isset($matches[2][0])) {
        $retval = $matches[2][0];
        if ($rewrite_skey) {
            gpSet("gp_skey",$matches[2][0]);
            gpSet("gp_action","edit");
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
*
* name: SQLX_Select
* parm: $tableId  optional  The name or data dictionary
*
* Select selected columns and all rows from a
* given table.  Automatically uses the correct view
* if necessary.
*
* Optionally, the second parameter can be a "*" or a
* comma-separated list of columns or an array of column
* names.
* If no column list is provided, all columns are
* provided, omitting functional columns like
* skey_quiet and _agg are omitted, but including skey.
*
*/
function SQLX_Select($tableId,$columns='',$options=array()) {
    $dd = ddTable($tableId);
    $view=$dd['viewname'];

    if( ($ob = a($options,'ob','')) <> '') {
        $ob = " ORDER BY $ob";
    }

    if(!is_array($columns)) {
        if($columns=='' || $columns=='*') {
            $columns = array_keys($dd['flat']);
            $columns = array_diff($columns,array('skey_quiet','_agg'));
            $columns = implode(',',$columns);
        }
    }

    return SQL_AllRows("Select $columns from $view $ob");
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
        #hprint_r($sql);
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
                    $row[$colname] = str_replace( '0', 'N', $row[$colname] );
                    $row[$colname] = str_replace( '1', 'Y', $row[$colname] );
                    $row[$colname] = substr($row[$colname],0,1);
                    break;
                  case 'gender':
                     $row[$colname] = substr($row[$colname],0,1);
                     break;
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
           if(!vgfGet('x6')) {
               $proj=$table['pks'];
           }
           else {
               $proj=$table['projections']['_uisearch'];
           }
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
   $obj = dispatchObject($table_id);
   if(method_exists($obj,'aSelect_where')) {
       $aWhere[] = $obj->aSelect_where();
       if ( ConfigGet( 'LOG_SQL', 'Y' ) == 'Y' ) {
           sysLog(LOG_NOTICE,$obj->aSelect_Where());
       }
   }


   // If "firstletters" have been passed, we will filter each
   // select column on it
   //
   // KFD 8/8/07, a comma in first letters now means look in
   //             1st column only + second column only
   $SLimit='';
   $xWhere=array();
   if($firstletters=='*') {
       // do nothing, no where clauses
   }

   elseif($firstletters<>'') {
      $SLimit="Limit 40 ";
      if(strpos($firstletters,',')===false) {
         // original code, search all columns
         $implode=' OR ';
         foreach($aproj as $aproj1) {
             $type_id = $table['flat'][$aproj1]['type_id'];
             $subs = '';
             if(!in_array($type_id,array('char','vchar','text'))) {
                 $subs='::varchar';
             }

            $sl=strlen($firstletters);
            $xWhere[]
            ="SUBSTRING(LOWER($aproj1$subs) FROM 1 FOR $sl)"
               ."=".strtolower(SQLFC($firstletters));
         }
      }
      else {
         // New code 8/8/07, search first column, 2nd, third only,
         // based on existence of commas
         $implode=' AND ';
         $afl = explode(',',$firstletters);
         foreach($afl as $x=>$fl) {
             $type_id = $table['flat'][$aproj1]['type_id'];
             $subs = '';
             if(!in_array($type_id,array('char','vchar','text'))) {
                 $subs='::varchar';
             }

             $sl = strlen($fl);
            $xWhere[]
            ="SUBSTRING(LOWER({$aproj[$x+1]}$subs) FROM 1 FOR $sl)"
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
       # KFD 6/9/08, added in automatic ordering on queuopos column
       $OB = isset($table['flat']['queuepos']) ? 'queuepos' : '2';
       $sq="SELECT skey,$proj
              FROM $view_id
           $SWhere
            ORDER BY $OB $SLimit";
   }
   else {
       $sq="SELECT $sDistinct $pk as _value,$collist as _display
              FROM $view_id
           $SWhere
             ORDER BY $SOB $SLimit ";
   }
   /*
   openlog(false,LOG_NDELAY,LOG_USER);
   if ( ConfigGet( 'flag_syslog', 'Y' ) == 'Y' ) {
	   syslog(LOG_INFO,$table['projections']['dropdown']);
	   syslogbodyRows
	   (LOG_INFO,$sq);
   }
   closelog();
   */
   if ( ConfigGet( 'flag_syslog', 'Y' ) == 'Y' ) {
       syslog(LOG_INFO,$sq);
   }
   $rows=SQL_Allrows($sq);
   return $rows;
}

/**
name:cssExclude
parm:string file

Removes an included css file from the css array.
This will not work if debugging is enabled or immedate was set.
*/

function cssExclude( $file ) {
    if ( !empty( $file ) ) {
        $css = vgfGet( 'cssIncludes', array() );
        $cssExcludes = vgfGet( 'cssExcludes',array() );
        $newcss = array();
        foreach( $css as $cssfile ) {
            if ( $cssfile != $file ) {
                $newcss[] = $cssfile;
            }
        }
        if ( !in_array( $file, $cssExcludes ) ) {
            $cssExcludes[] = $file;
        }
        vgfSet('cssExcludes', $cssExcludes );
        vgfSet('cssIncludes',$css);
    }
}

?>
