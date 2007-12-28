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
// ==================================================================
if(!isset($AG['tmpPathInsert'])) {
   $ruri=$_SERVER['REQUEST_URI'];
   // If there is a "?", strip that off and everything past it
   $ruriqm =strpos($ruri,'?'); 
   if($ruriqm!==false) $ruri=substr($ruri,0,$ruriqm);
   // If there is an "index.php" then strip that off
   $ruri=preg_replace('/index.php/i','',$ruri);
   // Now remove the leading slash that is always there (unless it ain't)
   if(substr($ruri,0,1)=='/') $ruri = substr($ruri,1);
   $AG['tmpPathInsert']=$ruri;
}


// ==================================================================
// >>> 
// >>> The path is based on the real location of the index.php
// >>> file.  
// >>> 
// ==================================================================
$dir = realpath(dirname(__FILE__)).'/';
$AG['dirs']['root']        = $dir;
$AG["dirs"]["dynamic"]     = $dir."dynamic/";
$AG["dirs"]["application"] = $dir."application/";
$AG["dirs"]["generated"]   = $dir."generated/";
$AG["dirs"]["lib"]         = $dir."lib/";

ini_set("include_path"
	,$AG["dirs"]["dynamic"].PATH_SEPARATOR
	.$AG["dirs"]["application"].PATH_SEPARATOR
	.$AG["dirs"]["generated"].PATH_SEPARATOR
	.$AG["dirs"]["lib"].PATH_SEPARATOR
	.ini_get("include_path")
);

// ==================================================================
// >>> 
// >>> Now pass control forever to the library routines
// >>> 
// ==================================================================
include('index_hidden.php'); 
?>