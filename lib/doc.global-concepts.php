<?php
// Putting this file into a PHP block gives me 
// syntax coloring in my editor.  --KFD 4/4/07
/**
name:_default_
parent:Global Concepts
*/

/**
name:Security
date:May 22, 2007

Security in Andromeda is entirely table-based.  The user interface
(web pages) are meant to reflect the priveleges that any particular
user has within the database.  For instance, if they are allowed to
work at all with the ORDERS table, they will see a menu entry "Orders".

If they are allowed to INSERT into the ORDERS table, the "New Entry" 
and "Import" buttons will be lit.  If they are allowed to update, the
textboxes on the detail screen will be enabled, otherwise they will
be disabled.

Andromeda connects users to the database with their own credentials, it
does not connect as a super-user and arbitrate security in code.  For this
reason, we have no need to obfuscate our HTTP parameter schemes, and in
principle we do not even need to prevent SQL Injection!  If a malicious
user works out the parameter scheme and types in a URL meant to make 
an update to a table, and they are not allowed to UPDATE that table, they 
will just see a database error.

=Public Accounts=

Every database has a public group and user.  By default Andromeda 
denies access to all tables to all groups, so this user can do nothing
unless explicitly granted.  The deny-by-default policy can be a hassle
when making public web sites, where lots of anonymous access is required,
so in those kinds of sites the public user is given at very least SELECT
priveleges to the public tables.

=Point-of-Sale Security=

Point-of-Sale (POS) systems have special security requirements, and cannot use
the security model of either a public web site or something like an
accounting system.

A POS system requires that users identify themselves
every time they perform certain tasks, the normal concept of "Logging in" 
in the morning and staying logged in all day really does not apply.
Each time a salesperson goes to enter an order, they must identify themselves.

There are two reasons for this.  First, the computer is often in a public
or semi-public place, and it may not be possible to keep it attended at 
all times.  Second, the computer may be shared by multiple staff members,
and it would be burdensome to ask them to keep logging in and out every time
they walked up to the machine.

POS security does not require any particular settings in the 
[[Database Specification]], it is all about the User Interface.

When POS Security is activated, authentication is required in either of
these two circumstances:

* When the user goes from one page to another (See [[Pages, Classes, and Tables]]).
* When any PHP code executes the [[POSClear]] function.

A super user is exempt from POS Security, it will be deactivated for
a super-user.

You can activate POS security by making the following setting in 
[[applib.php]].

!>example:Activating Point-of-Sale Security
!>php
<?php
// File applib.php, application library for my application
vgaSet('POS_SECURITY',true);
?>
!<
!<

You can require re-authentication at the completion of some items with
explicit code:

!>example:Explicitly requiring re-authentication
!>php
<?php
class special_action extends x_table2 {
   function main() {
      // Special code to give myself a bonus
      $row=array('user_id'=>SessionGet('UID'),'dollars'=>1000);
      SQLX_Insert("bonuses",$row);
      // Don't let the next person do this, they have to authenticate again
      // even if coming back to the same page.
      POSClear();
   }
}
?>
!<
!<

*/

/**
name:Run-Time Error Handling
date:April 11, 2007

Andromeda implements all business rules in the database server, therefore
all run-time errors come from the database server.  The web framework 
parses the errors and presents them to the user.

Andromeda triggers do not fail on the first error.  Processing continues
and the trigger attempts to determine all of the errors on the input, then it
reports them all at once.

Errors are returned in a semicolon-delimited list.  Each error is itself
a comma-seperated list of 3 items.  The first is the affected column, or
'*' if it is a table-level error.  The second is an error number, and the
third is the error text.

Here is an example of what happens if you attempt to insert a duplicate
row into the APPLICATIONS table of the node manager:


!>example:Errors Returned From Server
!>noformat:This SQL Command on a typical node manager

insert into applications (application) values ('andro')

!<
!>noformat:Will result in this error string:

ERROR:  application,1002,Duplicate Value;webpath,1005,Required Value;

!<
!<

The framework parses the errors and handles them as follows:

* The user is returned to the screen that they tried to save.
* Table-level errors are displayed at the top of the screen.
* Column-level errors are displayed next to the particular column.
* If the user is a superuser, the offending SQL command is printed
at the top of the screen.

If you wish to retrieve the errors yourself in special code, you can use
the use the [[vgfGet]] function:

!>example:Obtaining Error Information
!>php
<?php
vgfGet('errorSQL','');  // will return the offending SQL Statement
vgfGet('errorsRAW',array()); // array of unparsed errors
vgfGet('errorsCOL',array()); // array of parsed errors
?>
!<
!<

In cases where there is a multi-column primary key or foreign key, the
server repeats some messages for each applicable column.  An attempt to
insert a duplicate into a table with a two-column key will result in two
semicolon-delimited errors, one for each column in the PK.  


!>example:Multiple-Column Primary Keys
!>noformat:This SQL Command on a typical node manager

insert into instances (application,instance) values ('cms','live');

!<
!>noformat:Will result in this error string:

ERROR:  application,1002,Duplicate Value;instance,1002,Duplicate Value;

!<
!<


The error numbers are:

* ''1001'' Null value of primary key column.
* ''1002'' Duplicate primary key value.
* ''1003'' Attempt to change primary key columns.
* ''1004'' Attempt to change foreign key columns (if applicable).
* ''1005'' Null values in foreign key columns.
* ''1006'' RI child insert failure. 
* ''1007'' RI parent delete failure (''Table level'' error).
* ''3001'',''3002'' Assignment of sequenced columns on insert or update.
* ''3303'',''3004'' Assignment of value to type "ts_ins" column on insert
or update (ts_ins is an insert timestamp).
* ''3305'',''3006'' Assignment of value to type "ts_upd" column on insert
or update (ts_upd is an update timestamp).
* ''3307'',''3008'' Assignment of value to type "uid_ins" column on insert
or update (uid_ins tags the user who made an insert).
* ''3309'',''3010'' Assignment of value to type "uid_upd" column on insert
or update (uid_upd tags the user who made an update).
* ''5001'' Assignment of value to a FETCH/DISTRIBUTE column.
* ''5002'' Assignment of value to a CHAIN calculated column.
* ''5003'' Failure of a column-level constraint chain.
* ''4001'' Column may not be empty.
* ''6001'' Invalid value in [[cbool]] column.
* ''6002'' Invalid value in a [[gender]] column.
* ''6003'' Invalid value in a [[time]] column.
* ''6010'' Value is less than allowed [[value_min]].
* ''6011'' Value is greater than allowed [[value_max]].
* ''7001'' Update not allowed (''Table level'' error).
* ''7002'' Delete not allowed (''Table level'' error).
* ''8001'' Attempt to create a duplicate user.
* ''9001'' Attempt to create a duplicate user in Node Manager.


*/



?>
