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
// ==================================================================
//
// READ ME FIRST:
//
// Certain spots in the code are marked with "DEBUG HINT", and 
// these spots contain instructions for how to observe what
// is going on in this file
//
// --KFD 9/13/07
//
// ==================================================================
// ==================================================================
// X4 Files
// Here is a basic history of Andromeda.  In the beginning we
// had index->index_hidden->raxlib and class x_table.  These files
// evolved and changed from July 2004 - July 2007.
//
// The first real big change was to go from x_table to x_table2
// in 2005.  This class worked much better as far as the
// framework went, but offered nothing new for programming.
//
// In late 2006 it became very clear that the database features of
// Andromeda were doing pretty well, and it also became pretty 
// clear that we had no coherent UI strategy to match the DB
// strategy.  Various experimental efforts were done until July
// 2007 with a general designation of "x3".  None of these was
// expected to be a real strategy.
//
// In August 2007 I worked up the final strategy for the UI side
// of things, and so was born "x4", the fourth generation of 
// framework code.  Unlike x_table2, which was only one class,
// and x3, which was a collection of experiments, x4 is meant to
// gather 3 years of experience into an eventual complete rewrite
// of the code.  Some basic ideas are:
//
// ONE:    x4IndexLib.php will eventually contain all of the 
//         "core" code used for all most common tasks, including
//         library code and class code.  There will be no 
//         2nd file for a library and no 2nd file for a class.
//         This is purely for performance reasons, for any given
//         amount of code that must be loaded, the fewer actual
//         files the better.  Also, x4 puts so much code into the
//         browser that there is just not much going on 
//         here any more.
//
// TWO:    For performance reasons, most core code is not object
//         oriented.  Everything is in functions.  The exception 
//         is the UI, because I find OOPy code seems to work very
//         well for UI stuff.
//
// THREE:  This script is executing at global scope, but we never
//         create global variables.  Our equivalent is the global
//         associative array $AG (which means all globals).
//         NOTE: one exception: $AGdir, a global variable containing
//         the application's root directory, w/o trailing slash.
//
// FOUR:   Our function naming convention is to:
//         -> Begin each function with either a prefix or
//            a return type (like 'row' for a row) in lower case
//         -> Do not use underscores to separate words
//         -> Use sentence word order: getABlueLabel();
//         -> Capitalize the first letter of each word, as in
//            dbConnect()
//
// FIVE:   The library prefixes are:
//         NOTE: as of 9/13/07, these need to be reviewed.
//         db:   all general purpose db routines
//         sql:  a sql command, as in sqlInsert,sqlDelete, etc.
//         a:    returns any kind of array
//         row:  returns a row
//         arow: returns a numerically indexed array of rows
//
// SIX:    The roadmap for this file is to assume the existence
//         of the original framework for all features, and then
//         add features into this file one-by-one.  
// 
//
// SEVEN:  We do not abstract against projected cases.  In other
//         words, if we *think* we *might* need a function in the
//         future, we DO NOT MAKE A FUNCTION NOW, because we don't
//         need it now.  This allows complete focus on the job 
//         at hand without cluttering it up.  It also prevents
//         the problems we get when eventually we need the 
//         function but the need is different than we thought and
//         the function is not quite right, so we either convolute
//         the function or make a new one, making matters worse.
//         The time to make a function is when you need it, that's
//         the only time you know you can get it right.
//
// EIGHT:  We make a function when:
//         -> We need generalization.  So on the big day when we
//            finally add mySQL support, we'll need a 
//            function dbConnect() that will generalize
//            pg_connect() and mysql_connect().
//         -> We need functionality in more than one place.  So
//            if we need to create a second database connection,
//            we take the literal code in this file, move it into
//            a function, call that function from the original
//            spot, then call that function from the new spot.
//
// NINE:   We use the same variables over and over in this
//         file.  These variables are:
//
//         $table  The name of a table
//         $view   Name of a view.  Presumably the name returned
//                 by ddViewFromTab(), which works out column- and
//                 row-security and gives us the view the user
//                 is allowed to use.
//         $tabdd  Reference to a table's data dictionary
//         $row    Associative array containing a database row,
//                    keys are column names and values are, well,
//                    values.  This is used for raw data coming 
//                    from browser or database, which has not yet
//                    been escaped or formatted.
//         $sfrow  Associative array containing SQL-formatted values.
//                    Keys are column names, and are valid.  
//                    Values are formatted, escaped and quoted, such 
//                    as ('date'=>"'2007-08-07'",'name'=>"'O''Reilly'")
//         $whr    Associative array containing where clause values
//         $sfwhr  See sfrow above
//         $flat   alias to $tabdd['flat']
//         $type   An Andromeda type, including simple types like
//                    char and complex types like ssn.
//
//         $retval The value that will be returned from a function
//         $x      temporary variable used for a line or three
//
// ==================================================================

// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
//
// CODE BEGINS HERE
//
// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
// This first stuff you see makes possible "friendly URLS", by 
// making possible absolute path references to CSS and JS files.
//
// The global value 'tmpPathInsert' is used by templates to create
// absolute references to CSS files and JS files that will work 
// in all three deployment modes, which are:
//   -> On a localhost,         like http://localhost/~userdir/andro/....
//   -> On a domain,            like http://www.example.com/....
//   -> On a domain, admin mode like http://dhost2.secdat.com/app/....
//
// This code must also be smart enough to figure out the following
// cases it might find:
//
// REQUEST_URI =  /~userdir/app/index.php?parmstring....
// REQUEST_URI =  /~userdir/app/?parmstring....
// REQUEST_URI =  /~userdir/app/
//
// Note that it checks first to see if tmpPathInsert has already been
// created, because upstream files like "pages" might have done this
// already.
//
//   -- KFD 3/15/07
//
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
if(!isset($AG['tmpPathInsert'])) {
   $ruri=$_SERVER['REQUEST_URI'];
   // If there is a "?", strip that off and everything past it
   $ruriqm =strpos($ruri,'?'); 
   if($ruriqm!==false) $ruri=substr($ruri,0,$ruriqm);
   // If there is an "index.php" then strip that off
   $ruri=preg_replace('/index.php/i','',$ruri);
   $ruri=preg_replace('/x4index.php/i','',$ruri);
   // Now remove the leading slash that is always there (unless it ain't)
   if(substr($ruri,0,1)=='/') $ruri = substr($ruri,1);
   $AG['tmpPathInsert']=$ruri;
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
// PATH.  We do not set a path.  We only need to know the
//        top-level directory.
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
// ini_set("include_path")=....
//$AG['dir']=realpath(dirname(__FILE__)).'/';

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
// INI settings
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
ini_set("allow_url_open",false);
ini_set("error_reporting",E_ALL);
ini_set("display_errors",true);
ini_set("log_errors",true);
ini_set("short_tag_open",true);

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
// Start the session
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
session_start();
header("Cache-control: private");  // added at advice of tutorial

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
// Get the appinfo stuff, this is an array that was put
// into the /generated directory by the build program.
// If it is not there we are in some kind of bootstrap situation.
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
@include("$AGdir/generated/appinfo.php");
if (!isset($AG['application'])) {
   $AG['application'] = 'andro';
   $AG['app_desc'] = 'Unknown';
}
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
// Add the POST variables to our own array, then add the GET
// variables to the same array.  Note that a GET overwrites 
// a POST of the same name.  If magic quotes is on, go 
// through and reverse the effects.
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
$AG['gp']=array_merge($_POST,$_GET);
if(get_magic_quotes_gpc()==1) {
   foreach($AG['gp'] as $key=>$value) {
      $AG['gp'][$key]=stripslashes($value);
   }
}


// command line programs now exit, otherwise proceed
// to routing
if(!isset($_SERVER['HTTP_HOST'])) return;
if(isset($force_cli))   return;
if(isset($header_mode)) return;


// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
// Connect to database.  This is not in a function because we 
// never need to call it except for here.  Earlier versions of
// Andromeda support multiple connections as other users and
// even to other databases, but the db side of things is now
// complete enough that we don't need that any more.
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
if(SessionGet('UID')=='') {
   SessionSet('UID',$AG['application']);
   SessionSet('PWD',$AG['application']);
}
$xstr=" dbname=".$AG['application']
		." user=".strtolower(SessionGet('UID'))
		." password=".SessionGet('PWD');
if(function_exists('pg_connect')) {
   $AG['dbconn'] = @pg_connect($xstr,PGSQL_CONNECT_FORCE_NEW );
}
else {
   $AG['dbconn'] = false;
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
// Dispatching
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
// x4 naming conventions for GET/POST parameters are:
// x4x*  control parameters
// x4c_* contents of widgets like input and textarea
// x4w_* where clause values for selects, updates and deletes
// 
// x4xAjax         Ajax direct database access
// x4xRowRet       1 if we should return row after ins and upd
// x4xPage         means a base page request
// x4xPageFunction means a page function call (anticipated) 
// x4xTable        as required, names a table
// x4xColumn       as required, names a column
// x4xValue        as required, names a column value

// Our first support is only for ajax calls.  This is really
// raw database access, which is only possible because we 
// implement security in the server. 
// 
if( ($x4xAjax = gp('x4xAjax')) <> '') {
   x4index_ajax($x4xAjax);
}
if( ($x4xPage = gp('x4xPage')) <> '') {
   x4index_page($x4xPage);
}

// Take the return values we care about and put them 
// out as JSON.
echo returnJSON(returnItems());

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
// Close database connection 
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
if($AG['dbconn']) {
   @pg_close($AG['dbconn']);
}
return;
// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
//
// TOP-LEVEL EXECUTION NOW ENDS
//
// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
// ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// DISPATCH HANDLING: Ajax direct database access
//
// You may be saying: OH NO!! DIRECT DATABASE ACCESS!!!  
// If so, read up on Andromeda Security.
// ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
function x4index_ajax($x4xAjax) {
   // For all raw access, there will be an array
   // of column values, and a table to hit.
   $row=rowFromGP('x4c_');  // values
   $whr=rowFromGP('x4w_');  // where clause values
   $table=gp('x4xTable');     // The table name
   $rr =gp('x4xRetRow',0);  // row return command
   
   // There are four different database functions, so there
   // are four library routines we might call.
   $ra=$r1=false;
   switch(strtolower($x4xAjax)) {
   case 'del':   x4sqlDel($table,$whr);              break;
   case 'sel':   $ra=x4sqlSel($table,$whr);          break;
   case 'ins':   $r1=x4sqlIns($table,$row,$rr);      break;
   case 'upd':   $r1=x4sqlUpd($table,$row,$whr,$rr); break;
   case 'bsrch': searchBrowse($table,$whr);          break;
   }
   if(is_array($r1)) {
      ri('data',$table,$r1);
   }
   if(is_array($ra)) {
      foreach($ra as $ra1) {
         ri('data',$table,$ra1);
      }
   }
}
// ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// DISPATCH HANDLING: Base Page request
//
// Here is where a user says, 'give me the customers page' or
// 'give me the orders page'
// ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
function x4index_page($x4xPage) {
   // No code in the custom branch yet.  We'll do that next.
   if(file_exists('application/'.$x4xPage.'.php')) {
      // nothing happens here yet
   } 
   else {
      $ox4xPage = new x4Page($x4xPage);
      $ox4xPage->makeDefaultLayout();
   }
   // Return the item's HTML as the main content
   $ox4xPage->makeJSONForBrowser();
}
// ==================================================================
// LIBRARY: Get / Post Variables
// Prefix/Suffix: gp
// ==================================================================
/**
function:gp
parm:string GP_Name
parm:any GP_Default (optional)
returns: string

Returns the value of a [[GET-POST Variable]].  

If the variable was not received on the current request, and there is
no second parameter, gp returns an empty string.

If the variable was not received on the current request, and there is
a second parameter, gp returns that value.  This makes for convenient
coding of default values.

<pre class="code">
$value=gp('user_id','anonymous');
$value=SQLFC($value);
$sq="Select option from member_profiles WHERE user_id=$value";
</pre>
   
*/
function gp($key,$vardefault='') {
	$post=$GLOBALS['AG']['gp'];
	if (!isset($post[$key])) return $vardefault;
	else return $post[$key];
}

/**
name:gpExists
parm:string GP_Name
returns:bool

Returns true if the named [[GET-POST Variable]] was sent by the browser
in the current request.
*/
function gpExists($key) {
	return isset($GLOBALS['AG']['gp'][$key]);   
}

/**
name:rowFromGP
parm:string GP_Prefix
returns:array Row

Returns a [[Row Array]] taken from a subset of the GET-POST Variables
sent by the browser.  Only variables that begin with GP_Prefix will
be returned, and the GP_Prefix will be stripped off of the key.

!>example:Using rowFromGP
!>php:If an HTML Form contains these controls:
<input name='txt_control1' value='Foo'>
<input name='txt_control2' value='bar'>
!<
!>php:When the user submits the form, we use rowFromGP
<?php
$row=rowFromGP('txt_');
print_r($row)
?>
!<
!>output:Which will output the following
control1:control2;Foo:bar
!<
!<

*/
function rowFromgp($prefix) {
	$strlen = strlen($prefix);
	$row = array();
	foreach ($GLOBALS['AG']['gp'] as $colname=>$colvar) {
		if (substr($colname,0,$strlen)==$prefix) {
         $row[substr($colname,$strlen)] = $colvar;
		}
	}
	return $row;
}
// ==================================================================
// LIBRARY: Return items
// Prefix/Suffix: ret
//
// These are anything generated by routines that are meant to
// go back to the browser, including data, messages, and 
// HTML snippets.  They might be fetched by a template and put
// out directly into an outgoing HTML file, or they might be
// output as JSON to return to an Ajax call.
// ==================================================================
/**
name:ri
parm:string rettype
parm:string retname
parm:any value

Shortcut to [[returnItem]].
*/
function ri($rettype,$retname,$retvalue) {
   returnItem($rettype,$retname,$retvalue);
}

/**
name:returnItem
parm:string rettype
parm:string retname
parm:any value

ANTICIPATORY DOCUMENTATION.  This entry documents features not
yet fully implemented.  I am currently working only on the
'data' portion for ajax returns.

Use this function to store values that must be returned to the
browser.  This includes data, messages, and HTML snippets.

The main purpose of this function is to have something you can
use for both regular page requests and for ajax requests.  The
idea is you keep registering the items to return, then they are
either fetched during HTML generation or sent back as JSON to
the browser, depending on how the request came in.  As programmers
all we have to know is that the data/message/snippet will get 
where it belongs.

The first parameter is the type.  We expect to be supporting
'data', 'message', and 'html'.  The second parameter is a name, 
which varies according to the first parameter.

If the first parameter is 'data', the second parameter is the name
of a table and the third paramter is a [[row]] array.  You can
call this function as often as needed for any number of tables,
it will accumulate the results.

If the first parameter is 'message', the second parameter can
be either 'error' or 'info', and the third parameter should be
a string.

If the first parameter is 'html', the second parameter should be
the ID of the html element, and the third parameter is the 
innerHTML of that element.

*/
function returnItem($rettype,$retname,$retvalue) {
   $r=arraySafe($GLOBALS['AG'],'returnItems',array());
   $r[$rettype][$retname][] = $retvalue;
   $GLOBALS['AG']['returnItems'] = $r;
}

/**
name:returnItems
returns:array

This is a framework function, which you would not normally use
in application code.  To see examples of output, go to page
"flat2" in the [[Development Application]].

Returns the complete array of items that have been generated
on a call that must go back to the browser.  See [[returnItem]]
for more details.
*/
function returnItems() {
   if(isset($GLOBALS['AG']['returnItems'])) {
      $retval = &$GLOBALS['AG']['returnItems'];
      return $retval;
   }
   else {
      return array();
   }
}
     
   
// ==================================================================
// LIBRARY: Session Variables
// Prefix/Suffix: session
// ==================================================================
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

function returnJSON($array) {
    // DEBUG HINT:
    // 
    // Follow these instructions using the DEVELOP project
    // (distributed with Andromeda)
    // 
    // FIRST, unrem the lines below
    // SECOND, enter this URL: http:/...../x4index.php?x4xPage=flat1
    // 
    // This will show you, in the form of javascript, what the
    // browser is going to be given.
    /*
    echo "<pre>";
    print_r($array);
    echo "</pre>";
    return;
    */
    // DEBUG HINT: END
    if(function_exists('json_encode')) {
        x4Debug("json_encode exists");
        return json_encode($array);
    }   
    else {
        x4Debug("json_encode does not exist");
        return "JSON extension not available";
    }      
}

// ==================================================================
// LIBRARY: Data Dictionary Access
// Prefix/Suffix: dd
// ==================================================================
function ddTable($table) {
    global $AGdir;
    @include_once("$AGdir/generated/ddtable_$table.php");
    if (!isset($GLOBALS['AG']['tables'][$table])) {
        x4Error('No data dictionary available for '.$table);
        return array();
    }
    else {
        $retval=&$GLOBALS['AG']['tables'][$table];
        return $retval;
    }
}

/**
name:ddViewFromTab
parm:string $table
returns:string $view

Accepts the name of a table and returns the appropriate view to
access based on the user's security permissions.

The name of a view is only returned if there is some reason
to redirect the user to a view.  In very many cases, oftentimes 
in all cases, the function returns the base table name itself, 
such as:

* If no column or row security is on the table
* If the user is a root user
* If the user is the anonymous (login) user

*/
function ddViewFromTab($table) {
   // Super User gets original table
   if(SessionGet("ROOT")) {
      return $table;
   }
   
   $tabdd=ddTable($table);
   // This is case of nonsense table, give them back original table
   if(count($tabdd)==0) return $table;
   
   $views=ArraySafe($tabdd,'tableresolve',array());
   if(count($views)==0) 
      return $table;
   else
      return $views[SessionGet('GROUP_ID_EFF')];
}

/**
name:ddNoWrites
returns:array

Returns an array of the values for [[automation_id]] that do not
allow direct writes by a user.  Used by the framework to build
SQL statements that avoid writing to columns that cannot be written
to, such as SEQUENCE columns, FETCH columns and calculated columns.
*/
function ddNoWrites() {
   return array(
      'SEQUENCE'
      ,'FETCH','DISTRIBUTE'
      ,'EXTEND'
      ,'SUM','MIN','MAX','COUNT','LATEST'
      ,'TS_INS','TS_UPD','UID_INS','UID_UPD'
   );
}

/**
name:ddColumnsFromProjection
parm:String table
parm:Special projection

Returns a numerically indexed array of columns that the current
user is allowed to see within a certain projection.

The first parameter names a table.

The second parameter can be blank, or it can be the name of a
projection, or it can be an array that
holds a list of desired columns.

There are various errors that can occur that do not have defined
results, such as having a projection and column by the same name,
requesting non-existent columns, naming a non-existent projection,
and so forth.

*/
function ddColumnsFromProjection($table,$projection='') {
    $tabdd = ddTable($table);
    // Pass 1 is security projection.  Drop columns completely
    // if they are not in the view
    //
    $view =ddViewFromTab($table);
    if($table<>$view) {
        $g2use = $table['tableresolve'][SessionGet('GROUP_ID_EFF')];
        $geff  = SessionGet('GROUP_ID_EFF');
        $g2use = substr($geff,0,strlen($geff)-5).substr($g2use,-5);
        if(substr($g2use,-5)<>'99999') {
            $cols2keep = &$tabdd['views'][$g2use];
            foreach($tabdd['flat'] as $colname=>$colinfo) {
                if(!isset($cols2keep[$colname])) {
                    // Delete outright a disallowed column
                    unset($tabdd['flat'][$colname]);
                }
                else {
                    // If person cannot write to this column, set
                    // the UIRO flag.
                    if($cols2keep[$colname]==0) {
                        $table['flat'][$colname]['uiro']='Y';
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
        if(!isset($tabdd['projections'][$projection])) {
            // This case also catches where no projection was specified
            $projcand = array_keys($tabdd['flat']);
        }
        else {
            $projcand = explode(',',$tabdd['projections'][$projection]);
        }
    }
    
    // Now loop through the projection candidates and figure out which
    // ones to include
    $acols = array();
    foreach($projcand as $colname) {
        if(!isset($tabdd['flat'][$colname])) continue;
        if($colname=='skey') continue;
        //if($colname=='_agg') continue;
        //if($colname=='_agg') continue;
        if(ArraySafe($tabdd['flat'][$colname],'uino')=='Y' ) continue;
        $acols[]=$colname; 
    }
    return $acols;
}


// ==================================================================
// LIBRARY: General language extensions
// Prefix/Suffix: any
// ==================================================================
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
// ==================================================================
// LIBRARY: Error Storing and Retrieving
// Prefix/Suffix: x4Error
//
// These are shortcuts into the returnItems arrays
// ==================================================================
function x4Error($msg) {
   ri('message','error',$msg);
}

function x4Errors() {
   $ri=returnItems();
   $errs1=ArraySafe($ri,'message',array());
   $errs2=ArraySafe($errs1,'error',array());
   if(count($errs2)==0) {
      return false;
   }
   else {
      return $errs2;
   }
}
function x4Debug($msg) {
    if(is_array($msg) || is_object($msg)) {
        ob_start();
        print_r($msg);
        $msg = ob_get_clean();
    }
    ri('message','debug',$msg);
}
// ==================================================================
// LIBRARY: Formatting and conversions
// Format for output to HTML and for SQL
// ==================================================================
function sqlFormatRow($tabdd,$row) {
   $flat  =$tabdd['flat'];
   $retval=array();
   foreach($row as $column=>$value) {
      if(isset($flat[$column])) {
         $retval[$column] = sqlFormat($flat[$column]['type_id'],$value);
      }
   }
   return $retval;
}

/**
name:sqlFormat
parm:string type
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
function sqlFormat($t,$v,$clip=0) {
	global $AG;
	switch ($t) {
      case 'mime-x':
			return "'".base64_encode($v)."'";
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
			return "'".sqlEscapeString($v)."'";
      case "mime-h":
         if($clip>0 && strlen($v) > $clip) $v = substr($v,0,$clip);
			return "'".base64_encode($v)."'";
			break;
		case "dtime":
			if ($v=="") return "null"; 
			//else return X_UNIX_TO_SQLTS($v);
         else return "'".date('r',tsFromAny($v))."'";
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
         else { return sqlEscapeString(trim($v)); }
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
name:tsFromAny
parm:unix_ts/string date
returns:unix_ts

Accepts a variable and returns a unix timestamp.  If the value passed in
is a number, the number is returned unchanged.  If the value is a string,
it is converted via strtotime.

Useful for writing resilient code when input values are not reliably
one or the other.
*/
function tsFromAny($datein) {
   if(is_integer($datein)) return $datein;
   else return strtotime($datein);
}

/**
name:sqlEscapeString
parm:string Any_Value
returns:string

Wrapper for pg_escape_string, to provide forward-compatibility with
other back-ends.
*/
function sqlEscapeString($val) {
   // KFD 1/31/07 check for existence of pg_escape_string  
   return function_exists('pg_escape_string')
      ? pg_escape_string(trim($val))
      : str_replace("'","''",trim($val));
	//return p*g_escape_string($val);
}

function hprint_r($var) {
   ob_start();
   print_r($var);
   x4Debug(ob_get_clean());
}

// ==================================================================
// LIBRARY: SQL Aliasing
// Prefix/Suffix: x4sql
// ==================================================================
function x4sqlIns($table,$row,$rowret=0) {
   $tabdd = ddTable($table);
   if(count($tabdd)==0) {
      x4Error('Cannot insert to '.$table.', no data dictionary');
      return;
   }
 	$flat = &$tabdd["flat"];

   // Convert all row values into sql-formatted values,
   // only known columns come back from this call
   $sfrow=sqlFormatRow($tabdd,$row);

   // Drop the columns we are not allowed to insert to
   $noWrites=ddNoWrites();
   foreach($sfrow as $column=>$value) {
      if(in_array($flat[$column]['automation_id'],$noWrites)) {
         unset($sfrow[$column]);
      }
   }
   
   // Assemble and execute the SQL
   $view = ddViewFromTab($table);
	$sq='INSERT INTO '.$view
      .' ('.implode(',',array_keys($sfrow)).')'
      .' values '
      .' ('.implode(',',$sfrow).')';
   x4SQL($sq);
   
   // Fetch the skey value 
	$notices = pg_last_notice($GLOBALS['AG']['dbconn']);
   $anotices=explode(' ',$notices);
   $retval = 0;
   if(count($anotices)>1) {
      $retval = array_pop($anotices);
   }
	//$matches = array();
	//preg_match_all("/SKEY(\D*)(\d*);/",$notices,$matches);
   //hprint_r($matches);
	//if(isset($matches[2][0])) {
   //   $retval = $matches[2][0];
	//}
   
   // if row return was true, and no errors, return
   // the row instead of the skey value
   if($rowret==0) {
      return $retval;
   }
   else {
      if(x4Errors()) {
         return array();
      }
      else {
         $sq="SELECT * FROM $view WHERE skey=$retval";
         return x4sqlOneRow($sq);
      }
   }
}
  
function x4sqlDel($table,$whr) {
   $tabdd= ddTable($table);
   $view = ddViewFromTab($table);
   $sfwhr= sqlFormatRow($tabdd,$whr);

   // Turn where clause into statements
   $awhere=array();
   foreach($sfwhr as $column=>$value) {
      $awhere[]=$column.'='.$value;
   }
   
   // Resolve the table id and generate and run SQL
   $view = ddViewFromTab($table);
	$sq='DELETE FROM '.$view
      .' WHERE '.implode(' AND ',$awhere);
   x4SQL($sq);
}


function x4sqlSel($table,$whr) {
    $tabdd= ddTable($table);
    $view = ddViewFromTab($table);
    $sfwhr= sqlFormatRow($tabdd,$whr);
    
    // Turn where clause into statements
    $awhere=array();
    foreach($sfwhr as $column=>$value) {
      $awhere[]=$column.'='.$value;
    }

    // WHERE Clause
    $swhere = '';
    if(count($awhere)>0) {
        $swhere = ' WHERE '.implode(' AND ',$awhere);
    }

    $sortCol = gp('sortCol');
    $sortDir = gp('sortDir');
    $sSort   = '';
    if($sortCol<>'') {
        $sSort = ' ORDER BY '.$sortCol.' '.$sortDir;
    }
    
    $sq='SELECT * FROM '.$view.$swhere.$sSort;
    return x4SQLAllRows($sq);
}


function x4sqlUpd($table,$row,$whr,$retrow=0) {
   $tabdd= ddTable($table);
   $view = ddViewFromTab($table);
   $sfrow= sqlFormatRow($tabdd,$row);
   $sfwhr= sqlFormatRow($tabdd,$whr);

   // Turn where clause into statements
   $awhere=array();
   foreach($sfwhr as $column=>$value) {
      $awhere[]=$column.'='.$value;
   }
   // Turn the update columns into statements
   $aupdate=array();
   foreach($sfrow as $column=>$value) {
      $aupdate[]=$column.'='.$value;
   }
   
   // Resolve the table id and generate and run SQL
   $view = ddViewFromTab($table);
	$sq='UPDATE '.$view
      .'   SET '.implode(',',$aupdate)
      .' WHERE '.implode(' AND ',$awhere);
   x4SQL($sq);
   
   // If retrow
   if($retrow==0) {
      return true; 
   }
   else {
      $rr=x4SQLOneRow(
         "SELECT * FROM $view WHERE ".implode(' AND ',$awhere)
      );
      return $rr;
   }
}


function x4SQL($sql) {
    $dbconn = $GLOBALS['AG']['dbconn'];
    $errlevel = error_reporting(0);
    pg_send_query($dbconn,$sql);
    $results=pg_get_result($dbconn);
    $t=pg_result_error($results);
    $error=false;
    if ($t) {
        // Made conditional 1/24/07 KFD
        // In x4, made universal
        //if(SessionGet('ADMIN',false)) {
            x4Error(
                "(ADMIN): You are logged in as an administrator, you will see "
                ." more detail than a regular user."
            );
            x4Error("(ADMIN): ".$sql);
            
            // Save them separately so they can be dealt with on the
            // browser if need be
            ri('message','sql',$sql);
        //}
        
        $ts = explode(";",$t);
        foreach ($ts as $onerr) {
            if(trim($onerr)=='') continue;
            x4Error($onerr);
        }
        
        // Now save the original unsplit errors for return to browser
        $t = trim(str_replace('ERROR: ','',$t));
        $t = str_replace("\n",'',$t);
        ri('message','sqlerr',$t);
    }
    error_reporting($errlevel);
    return $results;   
}

function x4SQLOneRow($sql) {
	$results = x4SQL($sql);
	$row = pg_fetch_array($results);
	return $row;
}

function x4SQLAllRows($sql,$colname='') {
   $results = x4SQL($sql);
   $rows = pg_fetch_all($results);
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

// ==================================================================
// LIBRARY: Searches
// Prefix: search
//
// This is not in a general SELECT because the search function
// is really fundamentally different, it's all about the LIKE stuff.
// ==================================================================
function searchBrowse($table,$whr) {
   // This function requires a few other things also
   $sortCol = gp('sortCol');
   $sortDir = gp('sortDir');
   $columns = gp('columns');
   $limit   = gp('limit');
   
   $tabdd = ddTable($table);
   $flat  = &$tabdd['flat'];
   
   // Loop through and build "like" clauses
   $sflike = array();
   foreach ($flat as $colname=>$colinfo) {
      if (isset($whr[$colname])) {
         $tcv  = trim($whr[$colname]);
         $type = $colinfo['type_id'];
         if($type=='dtime' || $type=='date') {
            $tcv=tsFromAny($tcv);
         }
         if ($tcv != "") {
            // trap for a % sign in non-string
            $sflike[]='('.searchBrowseOneCol($type,$colname,$tcv).')';
         }
      }
   }
   
   // If there are no where clauses, forget it, return w/o doing anything
   if(count($sflike)==0) return;
   
   $view=ddViewFromTab($table);
   $sq="SELECT *
          FROM $view
         WHERE ".implode(' AND ',$sflike)."
         ORDER BY $sortCol $sortDir
         LIMIT $limit";
   $results=x4SQLAllRows($sq);
   foreach($results as $row) {
      ri('data',$table,$row);
   }
}

// KFD 5/17/07, support lists, ranges, and greater/lesser
// Was rff_OneCol in raxlib.php, converted to browseSearchOneCol()
// by KFD 8/29/07
function searchBrowseOneCol($type,$colname,$tcv) {
   $values=explode(',',$tcv);
   $sql_new=array();
   foreach($values as $tcv) {
      $aStrings=array('char'=>0,'vchar'=>0,'text'=>0);
      if(substr($tcv,0,1)=='>' || substr($tcv,0,1)=='<') {
         // This is a greater than/less than situation,
         // we ignore anything else they may have done
         $new=$colname.substr($tcv,0,1).sqlFormat($type,substr($tcv,1));
      }
      elseif(strpos($tcv,'-')!==false && $type<>'ph12') {
         list($beg,$end)=explode('-',$tcv);
         $new=$colname.' BETWEEN ' 
            .sqlFormat($type,$beg)
            .' AND '
            .sqlFormat($type,$end);
      }
      else {
         if(! isset($aStrings[$type]) && strpos($tcv,'%')!==false) {
            $new="cast($colname as varchar) like '$tcv'";
         }
         else {
            $tcsql = sqlFormat($type,$tcv);
            if(substr($tcsql,0,1)!="'" || $type=='date' || $type=='dtime') {
               $new=$colname."=".$tcsql;
            }
            else {
               $tcsql = str_replace("'","''",$tcv); 
               $new
                  ="(    LOWER($colname) like '".strtolower($tcsql)."%'"
                  ."  OR "
                  ."     UPPER($colname) like '".strtoupper($tcsql)."%')";
            }
         }
      }
      $sql_new[]="($new)";
   }
   return implode(" OR ",$sql_new);
}
// ==================================================================
// ==================================================================
//
// CLASS: x4Page 
//
// The basic class for generating a page.  The basic purpose
// of this class is to build a hierarchy of "UI Objects".  These
// are objects with properties that describe the UI.  In the case
// of framework default code, all of these properties come 
// straight from the data dictionary.
//
// The object hierarchy is then converted into javascript that
// describes an "x4global" object.  This script is sent to the
// browser as JSON, where it is eval'd, which creates the 
// x4global object.  The return handler in the browser must then
// walk through the x4global object and create objects out of
// Javascript prototypes and tell them to initialize themselves.
// ==================================================================
// ==================================================================
class x4Page {
    // ##############################################################
    // We believe this will likely be the only method a programmer
    // would override.
    // ##############################################################
    function makeDefaultLayout() {
        // Add a tab bar, then the browse and then the detail
        // That's it!  Everything else occurs in the browser!
        $this->x4root = new x4UIObject('blank');
        $this->x4root->AddUIObject('titleBar',$this->x4Page);
        $tab = $this->x4root->AddUIObject('tabBar'); 
        $tab->AddUIObject('browse',$this->x4Page);
        $tab->AddUIObject('detail',$this->x4Page);

        // Now look for child tables that are displayed in grids
        $tabdd = ddTable($this->x4Page);
        $kids  = ArraySafe($tabdd,'fk_children',array());
        foreach($kids as $table_child=>$table_info) {
            $display = trim(ArraySafe($table_info,'uidisplay','drilldown'));
            if($display=='grid') {
                $tab->AddUIObject('grid',$table_child,$this->x4Page);
            }
        }
    }
    
    // ##############################################################
    // These are the methods that a programmer would not override
    // ##############################################################
    // Constructor assigns table name only, loads data dictionary
    function x4Page($x4Page='') {
        $this->x4Page = $x4Page;
        
        global $AGdir;
        $PAGES = array();                // avoid annoying compiler warning
        include("$AGdir/generated/ddpages.php");  // provided by builder
        $this->pageTitle = $PAGES[$x4Page];
    }

    function makeJSONForBrowser() {
        ob_start();
        echo "\nx4global = new Object();";
        echo "\nx4global.pageTitle = '".$this->pageTitle."'";
        $this->makeJSONForBrowserRecurse($this->x4root,'x4global');
        $script = ob_get_clean();
        ri('script','x4global',$script);
    }
    
    function makeJSONForBrowserRecurse($ArrOrObj,$prefix) {
        foreach($ArrOrObj as $key=>$value) {
            if(is_array($value) || is_object($value)) continue;
            if(strpos($key,'Function')===false) {
                $value = "'$value'";
            }
            $xkey = '[\''.$key.'\']';
            echo "\n$prefix$xkey = $value;";
        }
        // Second pass, recurse sub-arrays and sub-objects
        foreach($ArrOrObj as $key=>$value) {
            if(!is_array($value) && !is_object($value)) continue;
            //if(is_numeric($key)) $key = 'x'.$key;
            $xkey = '[\''.$key.'\']';
            echo "\n$prefix$xkey = new Object();";
            $this->makeJSONForBrowserRecurse($value,$prefix.$xkey);
        }
    }   
}
// ----- END OF CLASS x4Page, if you can believe that!

// ==================================================================
// ==================================================================
//
// CLASS: x4UIObject
//
// The job of x4Page (above) is to create a hierarchy of
// x4UIObject Objects.  The constructor expects a type and
// possibly a table.  This class contains code that will
// handle all simple framework types, such as titleBar, 
// tabBar, detail, browse, and grid.  There are no subclasses
// for those items, as things are not complex as of 9/14/07
// to need subclasses.
//
// ==================================================================
// ==================================================================
class x4UIObject {
    // Default constructor creates a blank item and then
    // calls any framework code to assign special properties. 
    function x4UIObject($objectType='blank',$table='',$parentTable='') {
        $this->objectType = $objectType;

        // These routines will add specialized properties 
        // that will be used by the browser to generate HTML elements.
        // Note that there is absolutely no actual HTML generation
        // going on in PHP, we are generating them on the browser.
        switch($objectType) {
        case 'tabBar':   
        case 'blank':    break;
        case 'titleBar': $this->makeTitleBar($this,$table);
        case 'detail':   $this->makeDetail(  $this,$table);
        case 'grid':
        case 'browse':   $this->makeBrowse(  $this,$table,$parentTable);
        }
    }
    
    // This constructor makes code in x4Page make more sense,
    // see the x4Page::makeDefaultLayout() to see how it is used
    function AddUIObject($objectType='blank',$table='blank',$parentTable='') {
        // Make a new object
        $x = new X4UIObject($objectType,$table,$parentTable);
        $this->kids[] = $x;
        $ref = &$x;
        return $ref;
    }    
    
    // This is a shortcut for adding HTML objects instead of our
    // framework-style objects
    function AddHTMLObject($tag,$innerHTML='',$className='') {
        $x = new X4UIObject($tag);
        $x->className = $className;
        $x->innerHTML = $innerHTML;
        $x->flagHTML  = 'Y';
        $this->kids[] = $x;
        $ref = &$x;
        return $ref;
    }

    // This is a constructor to put in a stand-alone input
    function AddInput($info) {
        $x = new X4UIObject('input');
        $x->column = $info;
        $this->kids[] = $x;
        $ref = &$x;
        return $ref;
    }
    
    // ---------------------------------------------------------
    // MAKE ROUTINE: titleBar
    // ---------------------------------------------------------
    function makeTitleBar(&$ref,$x4Page) {
        $ref->x4Page = $x4Page;
        
        global $AGdir;
        $PAGES = array();                // avoid annoying compiler warning
        include("$AGdir/generated/ddpages.php");  // provided by builder
        $ref->title = $PAGES[$x4Page];
    }
        
    // ---------------------------------------------------------
    // MAKE ROUTINE: detail
    // ---------------------------------------------------------
    function makeDetail(&$ref,$x4Page,$custom=false) {
        $ref->x4Page = $x4Page;
        $ref->table  = $x4Page;
        
        // Custom is boring, just leave now so they can do their
        // own thing.
        if($custom) return;
        
        // Get a list of the columns we will be using, then load
        // up the data dictionary.
        $acols = ddColumnsFromProjection($x4Page);
        $tabdd = ddTable($x4Page);
        
        // Now build a standard layout
        $htab = $this->AddHTMLObject('TABLE','','x4Detail');
        foreach($acols as $colname) {
            // Make a table row
            $hrow = $htab->AddHTMLObject('TR');
            // Add the caption as a simple HTML element with innerhtml
            // and a class assignment
            $hrow->AddHTMLObject(
                'TD'
                ,$tabdd['flat'][$colname]['description']
                ,'x4StandardCaption'
            );
            // Add the input as an HTML element with no innerHTML, and
            // a class assignment, because....
            $hinp = $hrow->AddHTMLObject('TD','','x4StandardInput');
            // ...we will add the input as a framework object
            $ci = $this->columnStats($tabdd['flat'][$colname]);
            $hinp->AddInput($ci);
        }
    }

    // ---------------------------------------------------------
    // MAKE ROUTINE: browse
    //
    // As of 9/13/07 this is basically a copy of detail, 
    // with the only difference being the projection that
    // was pulled.  Here we pull '_uisearch'
    // On 9/14/07 put in a few more differences for browse/grid
    // ---------------------------------------------------------
    function makeBrowse(&$ref,$x4Page,$parentTable='') {
        $ref->x4Page = $x4Page;
        $ref->table  = $x4Page;

        // Get a list of the columns we will be using, then load
        // up the data dictionary.
        $acols = ddColumnsFromProjection($x4Page,'_uisearch');
        $tabdd = ddTable($x4Page);

        // Assume grid if a parent table is named
        if($parentTable<>'') {
            $ref->caption     = $tabdd['description'];
            $ref->parentTable = $parentTable;
            
            // get pk of parent table and provide it
            $tabddp = ddTable($parentTable);
            $ref->parTabPK = $tabddp['pks'];
        }
        else {
            // This is assumed to be a browse
            $ref->limit   = 15;
            $ref->sortCol = $acols[0];
            $ref->sortDir = 'ASC';
        }
        // This holds info about data returned from ajax calls
        $ref->collist = implode(',',$acols);
        $ref->data['rows'] = array();
        $ref->data['rowCount']   = 0;
        $ref->data['rowSelected']= 0;
        
        // Loop through the columns and assign properties
        foreach($acols as $colname) {
            if(isset($tabdd['flat'][$colname])) {
                // $ci is "column Information"
                $ci = &$tabdd['flat'][$colname];
                // Build the array and assign it
                $ref->columns[$colname] = $this->columnStats($ci);
            }
        }
    }
    
    // ---------------------------------------------------------
    // HELPER ROUTINE: columnStats
    //
    // Builds an array of the essential information about a
    // column and returns it.  Used to generate the properties
    // that are returned to the browser in x4global.
    // ---------------------------------------------------------
    function columnStats($ci) {
        $col = array();
        $this->columnStatsOne($col,$ci,'name','column_id');
        $this->columnStatsOne($col,$ci,'table','table_id');
        $this->columnStatsOne($col,$ci,'description');
        $this->columnStatsOne($col,$ci,'type_id');
        $this->columnStatsOne($col,$ci,'formshort');
        $this->columnStatsOne($col,$ci,'dispsize');
        $this->columnStatsOne($col,$ci,'colprec');
        $this->columnStatsOne($col,$ci,'colscale');
        $this->columnStatsOne($col,$ci,'uiro');
        $this->columnStatsOne($col,$ci,'primary_key');
        $this->columnStatsOne($col,$ci,'automation_id');
        $this->columnStatsOne($col,$ci,'auto_formula');
        $this->columnStatsOne($col,$ci,'table_id_fko');
        $this->columnStatsOne($col,$ci,'fkdisplay');
        return $col;
    }
    
    function columnStatsOne(&$dest,&$source,$dname,$sname='') {
        if($sname=='') $sname = $dname;
        
        // If the source value is blank, don't send it, that reduces
        // data sent over the wire.  The javascript on the other end
        // has code that will return blank values for non-existent
        // properties, which makes it all come out in the end.
        if(trim($source[$sname])=='') {
            return;
        }
        else {
            $dest[$dname] = trim($source[$sname]);
        }
    }
}
?>