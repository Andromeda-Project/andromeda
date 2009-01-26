<?php
# ===========================================================
# 
# The applib.php file is loaded on every call to your
# website.  Use it for these purposes:
#
#  1) To execute code in global scope at the top
#     of each request
#
#  2) To code up routines that the framework looks for
#     at certain stages that allow you to modify
#     behavior
#
#  3) To store routines and classes that are small 
#     or need to be accessed from many places
#
#
#  All examples in this file are rem'd out, so this
#  skeleton file has no actual effect on your program.
# ===========================================================

# -----------------------------------------------------------
#
# GLOBAL SCOPE EXECUTION
#
# Everything at the top of this file is called 
# immediately after the framework is loaded, but before
# anything else happens.  This is most suitable for
# creating global variables, but more specific processing
# should occur inside of routines
#
#
# -----------------------------------------------------------

#    Andromeda is a DENY BY DEFAULT system.  A user who is
#    not logged in can only get to the login page and the
#    forgot password page.  To make pages public, you must
#    list them in this array:
#
#    NOTE: Use the page name as the index, the actual
#         value you assign it is not used.
#
#$MPPages['example_page'] = 1;
#$MPPages['example_page_2'] = 1;

# -----------------------------------------------------------
#
# Application routine example
#
#
# -----------------------------------------------------------
/*
function mySpecialFunction() {
    # Do something here that is very
    # common in your application
}
*/


# -----------------------------------------------------------
#
#  FUNCTION: app_x6profiles() 
#
#  The admin interface uses the .dd.yaml setting 'x6profile'
#  to determine how to display each table.  You can override
#  those settings in code in this routine:
#
# -----------------------------------------------------------
/*
function app_x6profiles() {
    $GLOBALS['AG']['x6profiles']['some_table'] = 'twosides';
}
*/


# -----------------------------------------------------------
#
#  FUNCTION: app_login_process($uid,$pwd,$admin,$groups)
#
#  If this function exists, it will be called after a 
#  user who is logging in has been successfully 
#  authenticated.  You can do any processing you 
#  may require at this stage, and if you return false
#  the login is aborted.
#
# -----------------------------------------------------------
/*
function app_login_process($uid,$pwd,$admin,$groups) {
    # $uid: the user Id
    # $pwd: the password 
    # $admin: NOT USED
    # $groups: NOT USED 
    
    # find out if user is in particular group and
    # do something
    if(inGroup('admin')) {
        return false;   
    }
}
*/


# -----------------------------------------------------------
#
# FUNCTION: app_after_db_connect()
#
#  Andromeda always connects to the database on every
#  call.  If the user
#  is not logged in, Andromeda connects as the "anonymous"
#  user, which is always named after the application.
#  Application "example" has anonymous user "example".
#
#  If the user has logged in, Andromeda caonnects to the
#  database with their credentials.
#
#  If this routine exists, it is called right after
#  connection to the database.  
#
# -----------------------------------------------------------
/*
function app_after_db_connect() {
    # Find out the user's id
    var $uid = SessionGet('UID');
    
    if(inGroup('some_group_name')) {
        # do something
    }
*/


# -----------------------------------------------------------
#
#  FUNCTION: app_nopage()
#
#  When a user first hits your site, and no specific
#  page is requested, Andromeda sends them to the login
#  page.  You can override this by putting a page
#  called "x_home" into your application directory.
#
#  However, if you want programmatic control over what
#  page to go to when none is specified, code up this
#  routine.
#
# -----------------------------------------------------------
/*
function app_nopage() {
    if(condition) {
        return 'example_one';
    }
    else {
        return 'example_two';
    }
}
*/

# -----------------------------------------------------------
#
#  FUNCTION: app_template()
#
#  The "template" is the set of HTML,CSS and JS files that
#  determines the overall look of your site.  The admin
#  interface uses the 'x6' template.  Public websites
#  will always have their own template.
#
#  Most of the time template selection is done automatically
#  by Andromeda, public users stay on the public template and
#  admins end up on the x6 template.
#
#  You can override the template selection by coding up
#  app_template().
#
# -----------------------------------------------------------
/*
function app_template() {
    if(condition) {
        return 'public_template';
    }
    else {
        return 'x6';
    }
}
*/




?>
