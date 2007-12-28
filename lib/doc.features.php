<?php
/**
name:Features List
parent:Introduction

This is the definitive guide to all of the features of
Andromeda.  There are no separate lists for end-user features
or programmer features, all are included here.  This page itself
is the current focus of documentation efforts.


Each feature is given one of these statuses:

* COMPLETE.  Indicates no further development is contemplated.
* BETA.  Currently under development, the feature is considered
  well-defined and is being tested for final tweaks and debugging.
* ALPHA.  Currently under development, the feature may undergo 
  considerable revision that would break systems that depend upon it.
  May also have glaring bugs, and should not be used in production.
* DISCUSS.  The feature is at very least being talked about, but
  beyond that nothing has yet been determined.

There is no status for the case of a completed feature undergoing
significant revision.  This is because it is more of a priority now
to solidify the existing features and introduce new features.  Andromeda
will be more stable if we leave complete features alone except for
debugging, and implement new ideas as new features.

=Database Descriptions=

The category of Database Descriptions includes any feature that 
the programmer can make use of to craft a database.  They fall broadly
into the categories of table design, security, constraints, and 
automations.

==Columns, Tables, and Indexes==

This section added May 14, 2007.

|++
|Feature
|Status
|Discussion

|-
|Columns and Tables
|Complete
|The basic ability to specify column names, types, precisions
and scales and have Andromeda build tables.  See also [[column]]
and [[table]].

|-
|Primary Keys
|Complete
|Andromeda allows any number of columns in the primary key of 
a table.  Columns can be any type.  Andromeda implements primary
keys in trigger code to accommodate our [[Error Reporting]] features.
See also [[primary_key]] and [[table.column]].

|-
|Range Primary keys
|Beta
|A range primary key considers two separate column and prevents
overlapping pairs of values.  Given integer columns "From" and "To",
the pairs {1,5} and {5,6} would be disallowed, while {1,5} and {6,6}
would be allowed.  Likewise an existing pair {1,5} would prevent
the addition of {2,7}, a partial overlap, {0,6}, an outer
nesting, and {2,4}, an inner nesting.

In a previous draft, we contemplated allowing nested intervals,
such as allowing 3-5 inside of 2-10,
but this proved very counter-intuitive to the users and it was
removed.

See also [[range_from]] and [[range_to]] and [[table.column]].

|-
|Foreign Keys
|Complete
|Andromeda relies very heavily upon foreign keys for a variety
of automations.  Andromeda also implements foreign keys in such
a way that automatically ensures that the columns in the two tables
always match up on type and precision.  New users should definitely
review how these work, they are '''not the same as other SQL systems'''.

Support for delete cascade is included.

See more at [[table.foreign_key]].

|-
|Indexes
|Complete
|Any number of indexes can be specified on any table.  An index
may have one or more columns.

It is not necessary to create indexes on primary key or foreign key
columns, those are created automaticaly.  See also [[table.index]].

|-
|Functional Indexes
|Discuss
|PostgreSQL includes the ability to specify indexes using functins,
as in UPPER(first_name).  This would be a very nice feature to support
for searching functions.

|-
|Unique Constraints
|Complete
|Every table must be unique on its primary key, but you can also 
specify that any other column or group of columns be unique.
There is a property of the [[table.index]] that does this.

|-
|Defaults
|Complete
|Andromeda allows column definitions to specify a default value.

|-
|Default Foreign Keys
|Discuss
|You cannot specify a default value for a foreign key in the
database definition because you do not know the values the 
foreign key table will have.  It would be nice if one row in
the parent table could be specified as the default value to
be used on inserts into child tables.

|-
|Sequences
|Complete
|Also known as auto-incremented ID columns, Andromeda allows any
number of columns within a table to be automatically assigned.
The purpose of this feature is to create a unique but otherwise
meaningless column value, there is no guarantee that values will
be in any order without gaps.


|--

==Database Security==

This section modified May 14, 2007.

|++
|Feature
|Status
|Description

|-
|Per-Database Login Permissions
|Complete
|Andromeda allows a server to host separate applications that
are kept completely separate.  A user can be given an account on
the server and permissions on one database without having any
permissions for any other application.

|-
|Separation of Login from Other Permissions
|Complete
|An Andromeda user must be given rights to login to an application
as a distinct security permission.  If this permission is revoked
they have no ability to make use of their other granted permissions.
Granting the login permission grants no other permissions.

|-
|Deny by Default
|Complete
|A standard Andromeda database will not allow any user to access
any table.  Access must be explicitly granted.

|-
|Groups
|Complete
|Security is defined in terms of group, using the [[group]] 
definition.  Groups can be given default database-wide priveleges.
All security definitions relate to groups.

|-
|Modules
|Complete
|Modules are used to group together tables and then assign
permissions to them.  See also [[module]].

|-
|Table Security
|Complete
|Security can be assigned directly to a table for a group.
See also [[table.group]].

|-
|Column Security
|Beta
|Security can be specified for groups at the column level.  Groups
can be assigned the ability to view (permsel) or update/insert 
to a column.

|-
|Row Security
|Beta
|Security can be specified at the row level in two ways.  One way
is to specify that a user can only access rows where a particular
column matches their user_id.  Another approach specifies that
a particular column is used to reference a lookup table that will
list zero or more users who may access that row.

|--

==Constraints==

|++
|Feature
|Status
|Description

|-
|Insert/Update Constraints
|Complete
|Andromeda implements a generalized ability to express a constraint
in terms of the columns in the current row.  One examle would be
disallowing a save that would have a gl debit not equal to
a gl credit.  


|-
|Update Prevention
|Complete
|A constraint can be defined that determines when a row cannot
be updated at all.  This is useful in financial applications
where something like a GL batch may not be updated once it is
closed.

|-
|Delete Prevention
|Complete
|A constraint can be defined that determines when a row cannot
be deleted at all.  This is useful in financial applications
where something like a GL batch may not be deleted once it is
closed.

|--


==Automations==

This section modified May 14, 2007.

Automations are the cornerstone of the Andromeda approach (see
[[Definition-Oriented Programming]] and
[[Normalization and Automation]].  Automations allow a database
specification to be '''complete''', meaning that no necessary
statements about the database have been left out.

Sometimes it is possible to take code-based automations and
directly transpose them into Andromeda-style concepts, but oftentimes
a code-based bit of business logic will need to be decomposed
into its original purposes and then reconstructed with
Andromeda automations.

|++
|Feature
|Status
|Description


|-
|Column FETCH and FETCHDEF
|Complete
|A "fetch" is the copying of a value from a parent table into a
child table, such as copying price from an items table onto 
an order lines table.  A "fetch" operation assumes there is a foreign
key between the two tables, with the destination table being the
child table and the source being the parent.  The copy operation
occurs when the foreign key value in the child table changes, either
through an insert or update.

The FETCHDEF variation only executes the fetch if a user has not
provided a value, and the user can always overwrite the 
FETCHed value.

If the value changes in the parent table there is no action.

|-
|Column DISTRIBUTE
|Complete
|This is the same as a FETCH with a significant difference.  When the
source value in the parent table changes, the new value is copied
down into all matching rows in the chld table (it is distributed).

|-
|Column Aggregates
|beta
|An aggregate is an operation like SUM, COUNT, MIN, or MAX that
takes values from multiple rows in a child table and writes the
answer to a parent table.  An example would be the column in 
an ORDERS table, named "lines_total" which would be the SUM of all
of the values of "line_amount" in the ORDERS_LINES table.

These features are listed as "beta" because the current 
implementation can produce performance bottlenecks in some situations.
The optimization is coded and known to work on Linux, but
is waiting until we have plPerl installing easily on Windows.

|-
|Upsave
|complete
|An upsave is the most flexible way to write values from a child
table to a parent table.  A single defined upsave can update one 
or more columns, can be made to fire only under some conditions,
and can be defined to run only after inserts, updates, or both.

See also [[table.upsave]].

|-
|Foreign Key Auto-Insert
|complete
|This feature is particular to the case where an insert into a
child table contains invalid foreign key values.  When this 
flag is set on a [[table.foreign_key]] then a matching row will
be inserted into the parent table, allowing the insert in the
child table to succeed. 

This is the basis for Andromeda's technique for making summary
tables (and why we have no need for materialized views).  An
auto-insert foreign_key is defined to a table that contains
various SUM, COUNT and other summary columns from the child
table, and there you have a materialized real time summary table.

|--

=User Interface Features=

User interface features are those that are of interest or benefit
only to end users.  This section does not include features that 
programmers will find useful in creating user interfaces, those
are in a separate section.

The purpose of a user interface in the Andromeda approach is to
expose the database as it has already been defined.  Guiding 
principles include:

* Everything the user is allowed to do should appear on the
menu or in some way be visible to the user.
* Most actions can use plain-vanilla generated table maintenance
forms.
* Special forms such as wizards, calendars, shortcuts and the
like are coded manually to suplement the plain-vanilla table 
maintenance forms.


==Keyboard Navigation Features==

This section added May 18, 2007.

Because Andromeda is all about business applications, many users will
want easy navigation without switching between keyboard and mouse.  

The end-goal is to have a default Andromeda application navigable 
'''entirely via the keyboard''' and to have all keyboard options
'''clearly visible''' at all times.

|++
|Feature
|Status
|Description

|-
|Menu Bar Focus
|Beta
|When a user first logs in, the first item on the top menu is 
given focus, so that if they hit ENTER they pick that module.  The
other top-level menu entries (which name the modules in the application)
are given TABINDEX settings so that the TAB key will move from one 
to the next.  

This item is considered beta because it was recently implemented and
we have no feedback positive or negative.

|-
|Menu Item Focus
|Beta
|When a user selects a module from the menu, the individual menu entries
are then listed on the left.  To assist in keyboard navigation, the first
entry is given focus, and hitting TAB will go down through the entries.
At the end of the menu focus jumps back to the menu bar.

This item is considered beta because it was recently implemented and
we have no feedback positive or negative.

|-
|Browse Mode Focus
|Beta
|When a user first selects a menu entry for a table that has no custom
code, Andromeda searches for the first 300 rows in the table and then
presents the first page of 25 in a browse grid.  The first column of that
grid is a hyperlink to go to the detail page for that row.  Andromeda
gives focus to the first hyperlink, and hitting TAB progresses through
the rows.

When the user hits TAB on the last row, focus jumps back to the left-menu,
and when the user TABs through that focus jumps to the top menu bar.

This item is considered beta even though it has been implemented for over
a year because we have no feedback.

|-
|Input Mode Focus
|Complete
|When a user goes into one of the three input modes, new entry, edit,
or lookup, focus is given to the first input widget.


|-
|Underlined Access Keys
|Complete
|All of the links that are available during regular table maintenance
are accessible via hotkeys.  So for example the Lookup button can be
accessed with ALT-L, save with ALT-S and so on.

Note that firefox Version 2.x has '''broken this feature''', please see
the [[Requirements Reference]] page to fix this.

|-
|Use of ENTER key
|alpha
|Most users of desktop software expect the ENTER key to have a generic
context-sensitive "I'm finished" function.  If they are in lookup
mode and enter a value in a search field, they expect to hit ENTER
and execute the search.  Needing to hit ALT-L (or ALT-B, or ALT-S etc)
is counter-intuitive for many users.

On May 18, 2007 we introduced the beginnings of this feature to lookup
mode and new entry mode.  In both of those modes, if the user is on a 
textbox or other input field, and hits ENTER, it would be as if they
hit ALT-L or ALT-S respectively.

This feature is in Alpha, it was introduced May 18, 2007.  We are looking
for feedback and more places where it might be used.


|-
|Advanced Keyboard Actions
|discuss
|Browsers do not have the same range of features as desktop apps when
it comes to capturing keyboard events and binding them to code.  We have
no desire to reinvent the wheel here, but it would be nice if there were
a hotkey to activate the menu bar, if the arrow keys worked to navigate
through text inputs, and so forth.

|-
|"Unfocused" Activities
|discuss
|To realize the goal of complete keyboard navigation, we must identify
any situation where a link or input field is visible but does not
get focus or cannot easily be gotten to w/o resorting to the mouse.
Then we need an approach to make sure it is very easy to always
have all links navigable.  

One such area is the "More detail" and "go back" links for child tables,
these are "unfocused" right now, and there is at present no strategy
for making them easily navigable.


|--

==Lookup Mode Features==

On May 18, 2007 Andromeda gained a few features in Lookup mode that
make is substantially more powerful than it was.  These features are
listed here.

|++
|Feature
|Status
|Description

|-
|On-screen Help Text
|beta
|When a user goes into lookup mode, the features described here are
presented in a panel off to the right.

|-
|Wildcard
|beta
|This has always been present, but never documented.  A user can enter
a percent sign anywhere as a wildcard.

|-
|Lists
|beta
|A user can enter a comma-seperated list of values.  For a date search
a user can enter 5/1/07,10/1/07 and get all rows back for those
date values.  This works for all data types.

|-
|Greater and Less Than
|beta
|A user can enter a search value such as "&gt;x" or "&lt;1/1/07" and see
the corresponding results.  This works for all data types.


|-
|Range
|beta
|A user can enter two values separated by a dash, such as '100-200' to
get all values in the range.  The endpoints are included in the search
results.  This works for all data types.

|-
|Combinations
|beta
|All of the features listed here can be combined.  When looking up 
sales orders, a user can enter '100,&lt;50,200-220,&gt;9000' and get
all matching results.

|--

*/


?>
