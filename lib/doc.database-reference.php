<?php
/* ------------------------------------------------------ *\
   ------------------------------------------------------
   KEN: the last thing to do here is automations
   ------------------------------------------------------
\* ------------------------------------------------------ */

/**
name:Database Reference
parent:Documentation

This section lists all of the elements and their properties that
can be defined in an Andromeda database. 

Some of these elements will make sense to anybody who has built a 
database, such as [[table]] and [[column]] and [[table.index]], while others
are unique to Andromeda, such as [[table.column.chain]].


*/

/**
name:_default_
parent:Database Reference
*/


/**
name:column

In Andromeda a column is ''defined'' outside of a table and then
''placed'' into one or more tables.   The top-level column definition
establishes a name and (at minimum) a type_id for a column.  Once a column is
defined, it can go into any number of tables.

!>example
!>noformat
column column_id:
   # required
   descripton: string
   type_id: ( char, vchar, int...)
   # optional
   colprec: numeric 
   colscale: numeric 
   automation_id: ( FETCH, DISTRIBUTE, SUM ... )
   auto_formula: table.column
   value_min:  
   value_max:
   uiro: Y/N
   uino: Y/N
   uicols: numeric
   uirows: numeric
   required: Y/N
   dispsize: numeric
   uiwithnext: Y/N
   alltables: Y/N
!<
!<

A column name is unique in a database.  You cannot have
two columns with the same name in different tables that have different
type, precision or scale.  (Technically this can be subverted using
the [[table.column#prefix]] and [[table.column#suffix]] properties, but
sound naming conventions generally avoid that).

Columns are placed into tables using the [[table.column]] or 
[[table.foreign_key]] definitions.



==Properties==



#column_id
''column column_id:''.  The first line of a column definition begins
with the keyword 'column', a space, and then the unique column name
(column_id)  followed by a colon.  

The first line of the definition should not be indented. 

Each line that follows the definition contains exactly one property/value
assignment.  All of the property/value assignments must be indented,
and they must all be indented at the same level.
The bare minimum of properties are ''type_id'' and 
''description''.


#description
''description''.  The description of the column that will be used in the UI
   for labels, captions, documentation, errors, and so forth.

#type_id
''type_id''.  The column type.  Column types are:

* char, same as standard SQL char.  Requires ''colprec''.
* vchar, same as standard SQL varchar.  Requires ''colprec''.
* numb, a SQL numeric, requires ''colprec'' and ''colscale''.
* int, same as standard SQL int
* cbool, a char(1) that only accepts Y,y,N, or n as values.
* dtime, a datetime (called a timestamp on some platforms)
* date, a standard SQL date
* time, minutes since midnight, stores an int restricted to values 0-1439.
* text, unlimited length text (aka clob).
* money, shortcut for numeric(14,2).
* gender, a char(1) that only accepts M,m,F or f as values.


#colprec
''colprec''.  Column Precision.  Length of column in characters. Required
for types ''char'', ''vchar'', and ''numb''.

#colscale
''colscale''.  Column Scale, used only for type ''numb'', the number of
digits to the right of the decimal.  If a table has colprec 6 and colscale
3, then its highest positive value is 999.999.

#automation_id
''automation_id''.  (See also [[Automations]], [[table.column.chain]]
and [[table.upsave]]).  Specifies one of the simple automated or default
formulas for a column.

* DEFAULT.  The column will get a default value specifed
in ''auto_formula''.
* SEQUENCE.  The column will get a auto-incremented integer value.  Note
that there may be gaps in the sequence.  Uniqueness is guaranteed but
strict no-gaps sequencing is not.
* SEQDEFAULT.  The column will get a SEQUENCE at insert unless the user
provides a value.
* BLANK.  The column will by default get a type-specific blank value, which
is an empty string for character/text, the number 0 for any numeric type,
and NULL for date or datetime.  Undefined for ''cbool'' and ''gender''.
* FETCH.  Retrieves a value from a parent table.  The table and column
are specified in ''auto_formula''.  The column's value is FETCHed whenever
the value of the foreign key (in the child row)
changes on either INSERT or UPDATE.  Requires a foreign key be declared
to the parent table.
* DISTRIBUTE.  Also retrieves a value from a parent table.  Like a FETCH,
the value is retrieved whenever the foreign key changes on the child row.
But unlike a FETCH, this automation causes the parent value to be re-copied
to ''all child rows'' if the vaue in the parent table changes.  Requires
a foreign key to be declared to the parent table.
* SUM.  Calculates a value as the sum of values in a child table.  The
value of ''auto_formula'' specifies the child table and column.  Requires
a foreign key be placed in the child table referring to the parent.
* COUNT.  Same as SUM, but counts rows.
* MIN.  Same as SUM but calculates a minimum.
* MAX.  Same as SUM but calculates a maximum.
* TS_INS.  Timestamp of insert.  Column must be of type dtime.
* UID_INS.  User_id of insert.  Column should be vchar 20.
* TS_UPD.  Timestampe of update.  Column must be of type dtime.
* UID_UP.  User_id of update.  Column should be vchar 20.
* QUEUPOS.  Used to maintain ordered lists, see details
at [[Automations]].
* DOMINANT.  If a user enters 'Y' in this column, all other rows
are set to 'N'.  See details as [[Automations]].


#auto_formula.
''auto_formula''.  See also [[Automations]].  Reqired by some values
of ''automation_id''.

* If ''automation_id'' is DEFAULT, the literal value of the default.  
Strings and dates do not require any quotes.
* If ''automation_id'' is FETCH, DISTRIBUTE, SUM, COUNT, MIN, or MAX,
names the table and column for the operation, separated by a period, 
as in a FETCH of items.price or a SUM of orders.order_total.

#value_min
''value_min''.  The minimum allowed value for the column.

#value_max
''value_max''.  The maximum allowed value for the column.

#uiro
''uiro''.  A Y/N flag to make this column read-only on the UI.  This is NOT a 
security measure, it should be used only for convenience and should be
considered subvertible by an attacker.

#uino
''uino''. A Y/N flag to suppress the column completely from the UI.  This is NOT a 
security measure, it should be used only for convenience and should be
considered subvertible by an attacker.

#uicols
''uicols''.  A column of type_id 'text' will be displayed onscreen using
an HTML TEXTAREA element.  The properties uicols and uirows control the
row and column sizing of that element.  The defaults are 4 and 30.

#uirows
''uirows''.  See uicols above.

#required
''required''. A Y/N flag indicating that a non-empty value is required.

#dispsize
''dispsize''.  An explicit setting of display size in characters, which overrides the
framework's calculation of a suitable display size.  For instance, a 
char column of colprec: 10 will display as 11.  You can override that to
5 to make it smaller.

#uiwithnext
''uiwithnext''.  An experimental UI flag that applies to default input
screens.  On a normal default screen the input fields are displayed 
one to a line, one after another.  If this flag is set, the next input
field will actuall occur on the same line.  Given two columns named
"state" and "zip" you might set 'uiwithnext: "Y"' on "state".

#alltables
''alltables''.  A flag which causes the column to automatically be placed
into all tables in the database.  Obviously should be used with caution!
The framework defines three columns that have this flag set, being
[[skey]], [[_agg]], and [[skey_quiet]].

*/

/**
name:dominant

This is a value for the automation_id of a [[column]] 
or [[table.column]].

This automation allows you to define a flag and then make sure
that the flag is only set to 'Y' for one row.  For instance,
if you have a list of vendors that are approved for an item, 
one of them may be the first choice while the others are
alternates.  In that case, you would want that vendor marked
as "dominant" to mark it out from the others.

The way to do this is to set the automation_id to 'DOMINANT'.
When the end-user marks
a row as 'Y', Andromeda will go look for the current
row that has 'Y' and set it to 'N'.  This way only one row at
any given time can be dominant.

Setting the auto_formula is important.  If you do not specify
a value for auto_formula, then only one row in the entire table
can have the flag set.  Usually people want to set this flag
within a group, such as the above example, where there is one
dominant vendor for a given item.  When this is the case your
table will have a [[foreign_key]] to the items table, and
so you name "items" as the value of auto_formula, as in
this example:

!>example
!>noformat
table itemsxvendors:
    foreign_key items:
        primary_key: "Y"
    foreign_key vendors:
        primary_key: "Y"
    column dominant:
        auto_formula: items
!<
!<

...which means that, ''for a given item'', only one vendor
can be flagged a dominant.
        
*/

/* --------------------------------------------------- *\
   ---------------------------------------------------
   Groups
   ---------------------------------------------------
\* --------------------------------------------------- */

/**
name:group

An Andromeda database specification will contain security assignments.
The security system is based on the idea of assigning rights to groups.
Any particular security assignment allows a group of users to access 
a table (or rows and columns within a table).

!>example
!>noformat
group group_id:
   # Required
   description: string
   # Optional application-wide assignments
   permsel: Y/N
   permins: Y/N
   permupd: Y/N
   permdel: Y/N
   permrole:Y/N
   nomenu:  Y/N
   solo:    Y/N
   # Any
   module module_id:
   
!<
!<

The database specification contains the group definitions, and the granting
of priveleges to those groups.  Priveleges are granted to tables  with the
definitions [[module.group]], [[group.module]], and [[table.group]].
Column-level security can be granted with [[table.column#permsel]] and 
[[table.column#permupd]].  Row-level security can be granted with
[[table.column#permrow]].

Any privelege granted within a group definition will apply to all tables
in the database.  These defaults are overridden by assignments made in
[[module.group]], [[group.module]], and [[table.group]].

For an actual user to gain priveleges, a [[user administrator]] must 
add them to one or more groups at runtime.  



==Properties==



#group_id
''group group_id''.  The first line of a group definition begins with
the keyword 'group', a space, and then the unique group name (group_id)
followed by a colon.


#description
''description''.  The friendly description of the group.

#permsel
''permsel''.  The group's default SELECT permission database-wide.

#permins
''permins''.  The group's default INSERT permission database-wide.

#permupd
''permupd''.  The group's default UPDATE permission database-wide.

#permdel
''permdel''.  The group's default DELETE permission database-wide.

#permrole
''permrole''.  If Y, members of this group may create new users and
assign privileges.  Any user who is put into this group has effective
total control of the application, as they can assign any kind of
security to themselves or others.

#solo
''solo''.  If Y, members of this group may not belong to any other
group (except the login group).  Note that this feature is '''subvertible'''
under very specific circumstances.  Users with ''permrole'' authority
who have direct access to a database console can assign other users into
any group or combination of groups without regard for this setting.

Within a group definition you can further define the group's permissions
for specific modules by including [[group.module]] defintions.
         
There is a hard-coded group known as the $LOGIN group.  The group is
not actually named '$LOGIN', it always takes the name of the database,
such as 'andro' or 'finance'.  If you wish to make assignments to this
default group, such as allowing that group SELECT access to some tables,
you can make assignments to the $LOGIN group.

*/


/**
name:group.module


A group.module definition assigns security priveleges to a group for
a module.  This definition is nested inside of the [[group]] definition.
This has exactly the same effect as a [[module.group]] definition.

!>example
!>noformat
group group_id:
   # ... group properties assigned here...
   # Here is the group.module definition:
   module module_id:
      permsel: Y/N
      permins: Y/N
      permupd: Y/N
      permdel: Y/N 
      nomenu:  Y/N
!<
!<

Any privelege granted within a group.module definition will 
apply to all tables in the named module.  These can be overridden
by direct assignments at the table level using [[table.group]].



==Properties==



#module_id
''module module_id''.  The first line of a group.module definition
begins with the keyword 'module', a space, and then the
unique module name (module_id) followed by a colon.

#description
''description''.  The friendly description of the group.

#permsel
''permsel''.  The group's SELECT permission for this module.

#permins
''permins''.  The group's INSERT permission for this module.

#permupd
''permupd''.  The group's UPDATE permission for this module.

#permdel
''permdel''.  The group's DELETE permission for this module.

*/


/* --------------------------------------------------- *\
   ---------------------------------------------------
   Modules
   ---------------------------------------------------
\* --------------------------------------------------- */

/**
name:module

Andromeda organizes tables into modules.  All tables must belong to a
module and therefore all databases must define at least one module.

The default menu system is built on modules and tables.  Each module
becomes a menu pad at the top of the page, and the tables within it
become the "dropdown" menu items.

!>example
!>noformat
module module_id:
   # Required
   description: string
   # Optional
   uisort: number
   nomenu: Y/N
   # Optional group security definitions:
   group group_id:
      #...module.group property/value assignments
!<
!<




==Properties==



#module
''module module_id''.  The first line of a module definition begins with
the keyword 'module', a space, and then the unique module name (module_id)
followed by a colon.

#description
''description''.  The friendly name. Will be used on the menu system.

#uisort
''uisort''.  Determines the sequence of this module on the menu.

#nomenu
''nomenu''.  A Y/N flag to suppress this entire module from the menu.

A module definition can contain a nested [[module.grup]] definition 
which will assign security priveleges to a group for this module.

*/

/**
name:module.group


A module.group definition assigns security priveleges to a group for
a module.  This definition is nested inside of the [[module]] definition.
This has exactly the same effect as a [[group.module]] definition.

!>example
!>noformat
module module_id:
   # ... module properties assigned here...
   # Here is the module.group definition:
   group group_id:
      permsel: Y/N
      permins: Y/N
      permupd: Y/N
      permdel: Y/N 
      nomenu:  Y/N
!<
!<

Any privelege granted within a module.group definition will 
apply to all tables in the named module.  These can be overridden
by direct assignments at the table level using [[table.group]].




==Properties==



#module_id
''group group_id''.  The first line of a module.group definition
begins with the keyword 'group', a space, and then the
unique group name (group_id) followed by a colon.

#permsel
''permsel''.  The group's SELECT permission for this module.

#permins
''permins''.  The group's INSERT permission for this module.

#permupd
''permupd''.  The group's UPDATE permission for this module.

#permdel
''permdel''.  The group's DELETE permission for this module.

#nomenu
''nomenu''.  This module should not appear on the menu for this group.

*/


/* --------------------------------------------------- *\
   ---------------------------------------------------
   Menu
   ---------------------------------------------------
\* --------------------------------------------------- */
/**
name:menu

An Andromeda menu by default lists all of the tables in the database,
grouped by their [[module]]s.  If you need to code up a special program 
and put it onto the menu, then use the ''menu'' definition.

At runtime the framework will look for a file with the same name as the
menu containing a class of the same name, and will 
execute function ''main''.

!>example
!>noformat
menu menu_id:
   # Required menu caption
   description: string
   # Optional forced sequencing
   uisort: number
   # Optional group security assignments
   group group_id:
      #...menu.group security assignments
!<
!>php:A program file is required for explicit menu entries:
<?php
// FILE: application/menu_id.php
class menu_id extends x_table2 {
   function main() {
      echo "You have made a custom menu entry!"
   }
}
?>
!<
!<




==Properties==



#menu_id
''menu menu_id''.  The first line of a menu definition begins with
the keyword 'menu', a space, and then the unique class name (menu_id)
followed by a colon.  A program file must exist with the same name, and
it must contain a class of the same name, and function ''main()''.


#description
''description''.  The caption for the menu entry on the menu.

#uisort
''uisort''.  The sort order of the entry with respect to other [[table]]s and
[[menu]] entries in the same module.

A menu entry can contain a [[menu.group]] security definition which
specifies the precise security for a group on that menu entry.

*/

/**
name:menu:group

Creates a security assignment to a menu entry for a specific group. 
Overrides any module-level security for the group. 
Overrides the group's default database-wide security.

!>example
!>noformat
menu menu_id:
   # ...menu property/value assignments go here 
   # Here is the menu.group security assignment
   group group_id:
      nomenu: "N"   # put it on the menu!
!<
!<




==Properties==



#group_id
''group group_id''.  The first line of a menu.group definition begins with
the keyword 'group', a space, and then the unique group name (group_id)
followed by a colon.

#nomenu
''nomenu''. A Y/N option to suppress this menu entry for this group.  

Use of the group menu.group definition is ''not a security setting'', 
because it makes no changes to a user's ability to read or write 
tables.  It is merely a ''user-interface setting'', because it affects
what people see.  

*/



/* --------------------------------------------------- *\
   ---------------------------------------------------
   Table.  The big deal.  Everything after here is
   some nested definition inside of a table.
   ---------------------------------------------------
\* --------------------------------------------------- */


/**
name:table

The table is of course the heart of the database.  In Andromeda you
define your [[column]]s, then define a table and ''place'' the columns
into the table.  

!>example
!>noformat
table table_id:
   # Required
   module: module_id
   description: string
   # Optional
   uisort: Y/N
   nomenu: Y/N
   capspk: Y/N
   # This flag is optional
   fkdisplay: dynamic
   # Comments are optional
   comments: > 
   # At least one column placement is required
   column column_id:
      #...possible property assignmnents to the column
   # Other optional nested definitions
   foreign_key table_id:
      #...possible property assignments to the foreign key
   group group_id:
      #...possible property assignments to the group
   projection projection_id:
      #...possible property assignmnents to the index
   index index_id:
      #...possible property assignmnents to the index
   upsave upsave_id:
      #...possible property assignments to the upsave
   history history_id:
      #...possible property assignments to the history 
      
!<
!<




==Properties==



#table_id
''table table_id''.  The first line of a table definition begins with
the keyword 'table', a space, and then the unique table name (table_id)
followed by a colon.

#description
''description''.  The friendly name of the table as used on the menu, as
  page titles, and the like.

#nomenu
''nomenu''.  A Y/N flag to keep this table off the menu.  This can be
overridden by a [[table.group]] definition. 

#capspk
''capspk''.  Causes the primary key columns to be converted to uppercase 
when a new row is inserted.

#uisort
''uisort''.  A numerical value that determines this table's placement on
the menu with respect to other [[menu]] entries and tables in the
same [[module]].

#fkdisplay
''fkdisplay''.  Set this to 'dynamic' to make use of the Ajax Dynamic List
(possibly one of the coolest Andromeda features), or leave it off to have
lookups occur with conventional HTML SELECT widgets.


==Nested Objects==

The following objects can be placed within a table:

* [[table.column]]
* [[table.foreign_key]]
* [[table.projection]]
* [[table.index]]
* [[table.upsave]]
* [[table.history]]

*/


/**
name:table.group
A table.group definition assigns security priveleges to a group for
a specific table. 
This definition is nested inside of the [[table]] definition.


!>example
!>noformat
table table_id:
   # ... table properties assigned here...
   # Here is the table.group definition:
   group group_id:
      permsel: Y/N
      permins: Y/N
      permupd: Y/N
      permdel: Y/N 
      nomenu:  Y/N
!<
!<

A security definition made at the table level is final.
This definition overrides any defaults assigned to a group through
the [[group]] definition and any [[group.module]] or [[module.group]]
definitions. 




==Properties==



#group_id
''group group_id''.  The first line of a table.group definition
begins with the keyword 'group', a space, and then the
unique group name (group_id) followed by a colon.

#permsel
''permsel''.  The group's SELECT permission on this table.

#permins
''permins''.  The group's INSERT permission on this table.

#permupd
''permupd''.  The group's UPDATE permission on this table.

#permdel
''permdel''.  The group's DELETE permission on this table.

#nomenu
''nomenu''.  This module should not appear on the menu for this group.


*/



/**
name:table.column

In Andromeda we say that we ''place'' a column into a table.  The column
must be previously defined with a [[column]] definition.   When the column
is placed into a table any of its properties can be overridden except for
[[type_id]], [[colprec]], [[colscale]] and [[alltables]].

!>example
!>noformat
table table_id:
   # ...table property assignments
   column column_id:
      # When placing a column in a table, there are no required properties
      uisearch: Y/N
      primary_key: Y/N
      pk_change: Y/N
      range_from: column_id
      range_to: column_id
      suffix: string
      prefix: string
      # These are properties of a column that can be overridden
      # in each table they are placed in
      description: string
      automation_id: ( FETCH, DISTRIBUTE, SUM ... )
      auto_formula: table.column
      value_min:
      value_max:
      uiro: Y/N
      uino: Y/N
      uicols: numeric
      uirows: numeric
      required: Y/N
      dispsize: numeric
      # These are row-level security
      permrow: Y/N
      table_id_row: string
!<
!<




==Properties==



#primary_key
''primary_key''.  A Y/N flag that adds the column to the table's primary
key definition.

#pk_change
''pk_change''.  A Y/N flag that allows a primary key to be changed.
Defaults to no.  This flag must be set for each column in the primary
key.  When a primary key value is updated in the database it will
cascade to all child tables.  


''range_from'' and ''range_to'' modify the primary key.  These are
discussed below under 'Range Primary Keys'.

#uisearch
''uisearch''.  A Y/N flag that adds the column to the list of columns
displayed on regular search results.

''suffix'' and ''prefix'' are used to modify the column's name.  These
are discussed below under 'Reusing and Renaming columns'.

#automation_id
''automation_id''.  See the description of [[column#automation_id]].

#auto_formula.
''auto_formula''.  See the description of [[column#auto_formula]].

#value_min
''value_min''.  The minimum allowed value for the column.

#value_max
''value_max''.  The maximum allowed value for the column.

#uiro
''uiro''.  See the description of [[column#uiro]].

#uino
''uino''. See the description of [[column#uino]].

#uicols
''uicols''. See the description of [[column#uicols]].

#uirows
''uirows''. See the description of [[column#uirows]].

#required
''required''. See the description of [[column#required]].

#dispsize
''dispsize''.  See the description of [[column#dispsize]].

#uiwithnext
''uiwithnext''.  See the description of [[column#uiwithnext]].

#permrow
''permrow''.  When this is set to Y, andromeda will only allow a user to
see rows in the table where their user_id matches the value of this
column.  This is the first of two methods for row-level security.  Using
this method, no two users can ever see the same rows.

#table_id_row
''table_id_row''.  This property implement's Andromeda's second method of
row-level security.  This method has the advantage of allowing multiple people
to gain access to the same rows.  When "table_id_row" is set to the name
of a table, then it is assumed that:

* This column must be a foreign key to said table
* The parent table contains a column called user_id
* A user will be able to see the row if their user_id matches in the
  parent table.


==A Range Primary Key==  

Andromeda allows you to specify that a primary key can cover a ''range'' of
values.  Consider the case where you need to define billing rates for your
customers.  The rates change from time to time, but do not follow predictable
patterns like always falling exactly into a year or a month.  So you must
specify that customer X is billed at rate Y from 4/15/07 to 6/1/08, and
at rate Z from 6/2/08 to 12/31/08.  The ranges may not overlap.  This is done
with the 'range_from' and 'range_to' properties.  These must be specified
with the 'primary_key' flag to establish a range:

!>example
!>noformat:A billing rates table
table rates:
   foreign_key customers:  # make customers part of primary key
      primary_key: "Y"
   column date_begin:
      suffix: _begin   # 'suffix' is explained in the next section 
      primary_key: "Y"
      range_to: date_end
   column date_end:
      suffix: _end   
      primary_key: "Y"
      range_from: date_begin
!<
!<

It should be noted hat this feature has been tried by others, but is not
in wide-spread use outside of Andromeda (at least not that we know of).
For this reason it should be expected that the feature itself will become
better defined as it is used by more people, and it should be understood
that best practices have yet to emerge.
      
    
==Re-using and Re-naming Columns==

Andromeda supports the re-use of columns by placing them into tables with
different suffixes or prefixes.  The basic idea is that you may have a 
column called 'phone' but you want to put 'fax' and 'cell' into a 
table and keep their column names similar and not have to define all
three columns.

If you make use of Andromeda pre-defined columns and the [[#prefix]] and
[[#suffix]] properties then you will actually not need to define that many
columns at all.  Andromeda predefines columns like 'amt' (for amount), and
'date' and 'first_name' and 'description' and so forth.  The following 
example from an POS application shows the predefine column 'amt' being
used twice:

Andromeda does not support the complete renaming of columns, such as 
putting a 'phone' column into a table with the name 'cell'.

!>example
!>noformat:Making three phone entries
table example:  
   column phone:      # An andromeda pre-defined columns
   column phone_fax:  # specify the complete new column name
      suffix: _fax    # tell us what part of the name is the suffix
   column phone_cell:
      suffix: _cell   
   column pre_phone_suf:  # this is silly, but allowed
      prefix: pre_
      suffix: _suf
!<
!<

*/

/**
name:table.foreign_key

Andromeda deals with foreign keys differently than most frameworks.  When 
you ''place'' a foreign key into an Andromeda table, several things 
happen:

* Andromeda identifies the column(s) that make the primary key
of the parent table.
* Andromeda places these same columns into the child table
* Andromeda puts referential integrity constraints onto both tables
(explained below)

When we use the SQL CREATE TABLE command to establish a foreign key,
we must specify the column type, precision and scale, and then declare
the reference to the parent.  In Andromeda all you have to do is name
the parent table, the rest is automatic.  

!>example
!>noformat
table table_id:
   #...various table property assignments go here
   foreign_key table_id:
      #...any optional foreign key properties
!<
!<

The default behavior for a foreign key is as follows:

* Any value in a child table must have a corresponding value
in the parent table (modifiable with ''allow_empty'', ''allow_orphans''
and ''auto_insert'').
* If a parent row has matching rows in the child table, the parent
row cannot be deleted (modifiable with ''delete_cascade'').




==Properties==




#table_id
''foreign_key table_id''.  The first line of a foreign key
definition begins with the keyword 'foreign_key', a space, and 
then the name of a parent table (table_id).  If you are using ''suffix''
and ''prefix'', then the table_id should be written out including the
suffix and prefix, as in this example:

!>
!>noformat
table orders:
   foreign_key customers:
   foreign_key customers_referring:
      suffix: _referring
!<
!<

#suffix
''suffix''.  If specified, all of the columns taken from the parent table
will have the ''suffix'' appended to their name.


#prefix
''prefix''.   If specified, all of the columns taken from the parent table
will have the prefix prepended to their name.

#primary_key
''primary_key''.  A Y/N flag that will cause all of the foreign key columns
to be in the primary key.

#uisearch
''uisearch''.  A Y/N flag that causes all of the foreign_key columns to
be in the normal search results.

#auto_insert
''auto_insert''.  A very powerful Y/N flag.  See [[Automations]].

#copysamecols
''copysamecols''.  Related to auto_insert, see [[Automations]].

#nocolumns
''nocolumns''.  A Y/N flag that stops the normal behavior of adding the
  columns to the table.  Required if more than one foreign key definitions
  would end up placing the same column in the table.


#allow_empty
''allow_empty''.  A Y/N flag that allows the foreign key value to be empty
  (or null).

#allow_orphans
''allow_orphans''.  A Y/N flag that completely disables reference checking,
  but still defines the columns.

#delete_cascade
''delete_cascade''.  A Y/N flag that causes all child table entries to be
deleted when a parent table row is deleted.  Overrides the normal behavior
which is to prevent the deletion of a parent table row if there exists
matching child entries.

*/

/**
name:table.group

A table.group definition assigns security priveleges to a group for
a table.  This definition is nested inside of the [[table]] definition.
Assignments made in this way are final, 
they override assignments made by [[group]] 
defaults and [[group.module]] and [[module.group]] defaults.  

!>example
!>noformat
table table_id:
   # ... table properties assigned here...
   # Define security for a particular group on this table
   group group_id:
      permsel: Y/N
      permins: Y/N
      permupd: Y/N
      permdel: Y/N 
      nomenu:  Y/N
!<
!<



==Properties==




#group_id
''group group_id''.  The first line of a table.group definition
begins with the keyword 'table', a space, and then the
unique group name (group_id) followed by a colon.

#permsel
''permsel''.  The group's SELECT permission for this table.

#permins
''permins''.  The group's INSERT permission for this table.

#permupd
''permupd''.  The group's UPDATE permission for this table.

#permdel
''permdel''.  The group's DELETE permission for this table.

#nomenu
''nomenu''.  This table should not appear on the menu for this group.

*/


/**
name:table.projection

A projection is nothing but a named list of columns, which you can
make use of in your application.  Projections have no impact on business
rules, they are purely for the use of the user interface.

If you create a projection named "dropdown", then the framework will
display that list of columns whenever this table is used as a lookup
list with an HTML SELECT or our Ajax Dynamic List.

The framework will also support in the future the use of a projection
named "new", which specifies which columns a user should see when they
are entering a new row.  Right now this feature does not exist yet, so a
user always sees the same detail in insert mode, lookup mode, and update
mode.

A projection is an ordered.  When you access the 
columns they will always be in the order they were defined.

!>example
!>noformat
table employees:An employees table with some projections
   column employee_id:
      primary_key: "Y"
      uisearch: "Y"
   column first_name:
      uisearch: "Y"
   column last_name:
      uisearch: "Y"
   
   # Here is an example of using the dropdown projection
   projection dropdown:
      column employee_id:
      column first_name:
      column last_name:
!<
!>php:Pull the projection at run-time
<?php
class employees extends x_table2 {
   function main() {
      $dd=dd_tableref('employees');
      $dropdown=$dd['projections']['dropdown'];
      echo $dropdown;
   }
}
?>
!<
!>noformat:will give a comma-separated list
employee_id,first_name,last_name
!<
!<


*/


/**
name:table.projection.column

See [[table.projection]]

*/


/**
name:table.index

A table.index definition is used to explicitly create indexes and
unique constraints on a table.

Andromeda by default builds indexes on all primary keys and foreign 
keys.  A primary key is also a unique constraint.  You can use the
table.index definition to create additional indexes on frequently-referenced
columns or column combinations.

!>example
!>
table table_id:
   index index_id:
      # this is optional, it creates a unique constraint
      idx_unique: Y/N
      # list each column on its own line:
      column column_id:
      column column_id:
!<
!<



==Properties==




#index
''index index_id''.  The first line of a table.index definition
begins with the keyword 'index', a space, and then an index name
that is unique within this table, (index_id) followed by a colon.

#idx_unique
''idx_unique''.  If set to Y, the index will also become a unique
constraint.


*/

/**
name:table.index.column

See [[table.index]]

*/

/**
name:table.upsave

A table.upsave is a very flexible way to write values from a child table
to a parent table.  Unlike automations such as [[SUM]] and [[COUNT]], an
upsave writes to columns that users can overwrite, and an upsave can
be made conditional.

A table.upsave is one of the most flexible ways that Andromeda has to 
write values from one table to another, with lots of options for 
specifying when and how the operation occurs.

!>example
!>noformat
table table_id:
   # An upsave will determine 
   upsave upsave_id:
      # These two properties are required
      table_id_dest: table_id  # a foreign key to this table must
                               # be defined in the table definition
      cascade_action: UPDATE    # required for historical purposes
      # At least one of these two must be present
      afterins: Y/N
      afterupd: Y/N
      # These are optional
      copysamecols: Y/N
      column_id_flag: column_id
      flag_reset: Y/N
      onlychanged: Y/N
      # These are explicit column assingments
      column column_id:      # the column in the parent table
         retcol:  column_id  # ...gets the value of a child column
      column column_id:      # this time the column in the parent table
         retval:  hello!     # ...gets a literal value, "hello!"
!<
!<



==Properties==



         
#upsave_id
''upsave upsave_id''.  The first line of a table.upsave definition
begins with the keyword 'upsave', a space, and then an upsave name
that is unique within this table, (upsave_id) followed by a colon.

#table_id_dest
''table_id_dest''.  Names the parent table.  An upsave definition is
always made in a child table, and the child table definition must always
contain a foreign key to the parent table being targetted by the upsave.

#cascade_action
''cascade_action''.  This is required and always takes the value of
UPDATE.  In future revisions this requirement will be removed.

#afterins
''afterins''.  If Y, the upsave will fire after inserts to the child table.
The ''afterins'' and ''afterupd'' properties can both be set, and at
least one of them must be set.

#afterupd
''afterupd''.  If Y, the upsave will fire after updates to the child table.
The ''afterins'' and ''afterupd'' properties can both be set, and at
least one of them must be set.

#copysamecols
''copysamecols''.  If Y, the upsave willc opy values from parent to 
child for all columns that have the same name in both tables.

#column_id_flag
''column_id_flag''.  This is an optional setting.  It names a column
in the child table.  The column must be of type cbool.  If such a column
is named, the upsave will only fire if the named column is 'Y'.

#flag_reset
''flag_reset''.  Only relevant if ''column_id_flag'' is set.  If the
''flag_reset'' property is Y, then the ''column_id_flag'' is reset to
N after the upsave fires.

#onlychanged
''onlychanged''.  Only changed values will be written to the parent table,
unchanged values will not be written.  Has no effect on inserts.

#column.retcol
''column.retcol''.  Allows you to explicitly name a column in the parent
table and have it get the value from a column in the child table.

#column.retval
''column.retval''.  Allows you to explicitly write a literal value
to a column in the parent table.


*/

/**
name:table.upsave.column

See [[table.upsave]]
*/


/**
name:table.history

A table.history is a very flexible way to record changes made to
a table.  A history definition specifies which columns in the history
table receive which values from the source table.

Best practice is to deny all access to the history table for normal users,
and grant read-only access to those who must run reports, and delete access
to those who will purge or roll-up the history tables.

Histories are written on all three actions, insert, update, and delete.
You can specify that any particular column will receive a constant value,
a difference between old and new, always the new, always the old, or
always a value.


!>example
!>noformat
table table_id:
   # Journal all changes to dollar amounts, record as well
   # if user changed the date.
   history track_the_money:
      # These two properties are required
      table_id_dest: journal
      # These are explicit column assingments
      column column_id:      #
         retcol:  column_id  # ...gets NEW on insert,
                             # ...gets OLD on update and delete
      column column_id:      #
         retval:  hello!     # ...gets a literal value, "hello!"
      column column_id:      
         retnew: column_source  # ...gets NEW value on insert and update
                                #    gets NULL on delete
      column column_id:      
         retold: column_source  # ...gets OLD value on update and delete
                                #    gets NULL on insert
      column column_id:      
         retdiff: column_source  # ...gets  NEW on insert
                                 #    gets -OLD on delete
                                 #    gets NEW-OLD on update
!<
!<



==Properties==
         
#history_id
''history history_id''.  The first line of a table.history definition
begins with the keyword 'history', a space, and then an upsave name
that is unique within this table, (history_id) followed by a colon.

#table_id_dest
''table_id_dest''.  Names the destination table.  A history definition is
created inside of the source table, the destination table must be defined
separately.

#column.retcol
''column.retcol''.  Allows you to explicitly name a column in the destination
table and have it get the value from a column in the child table.  It gets
the NEW values on INSERT, and the OLD value on UPDATE and DELETE.

#column.retval
''column.retval''.  Allows you to explicitly write a literal value
to a column in the destination table.

#column.retold
''column.retold''.  Copies the old value to the destination table.  Copies
a NULL on insert.

#column.retnew
''column.retnew''.  Copies the old value to the destination table.  Copies
a NULL on delete.

#column.retdiff
''column.retdiff''.  Copies the difference of values to the destination 
table.  Copies the NEW value on INSERT, the negative OLD on delete, and
NEW - OLD on update.


/**
name:table.chain

A table.chain is used to conditionally allow or prevent updates and
inserts to tables.

!>example
!>noformat
table table_id:
   chain chain_id:
      # a condition check
      test 00:
         compare: Chain Comparison Expression
         return: Chain Return Expression
      test 01:
         compare: Chain Comparison Expression
         return: Chain Return Expression
      # the unconditional default return value
      test 02:
         return: Chain Return Expression 
!<
!>noformat:Preventing updates and deletes to closed orders
table orders:
   chain update_pre:
      test 00:
         compare: @flag_closed = Y
         return: Order is closed, cannot update
   chain delete_pre:
      test 00:
         compare: @flag_closed = Y
         return: Order is closed, cannot delete
!<
!<



==Properties==




#chain_id
''chain chain_id".  A chain can be named 'update_pre' or 'delete_pre',
all lowercase. No other chains are recognized, any other chain name will
be ignored.  An update_pre chain fires before update and a delete_pre 
fires before delete.  The table.chain is fired before all other
business rules are checked.

#chain.test
''chain.test test_id''.  A chain consists of one or more tests.  Each
test must be given a unique test_id.  The test_id values can be anything,
but keep in mind that they will be evaluated in their sort order.  Best
practice is to name tests '00', '01', and so on.

#chain.test.compare
''chain.test.compare''.  A [[Chain Comparison Expression]].

#chain.test.return
''chain.test.return''.  A [[Chain Return Expression]].  

*/


/**
name:table.column.chain

A table.column.chain is used for two distinct purposes.  It can be used
to put constraints onto a column, and it can be used to generate calculated
values for a column that are based on other columns in the same row.

A constraint chain goes through one or more tests.  If the chain returns
a value, the constraint is considered to have failed, and the value is
taken to be the error message.  If the chain gets to the end without
returning a value it will return an empty string and be considered to
have succeeded.

A calculation chain goes through one or more tests looking for a match.
If it finds a match it returns the [[Chain Return Expression]].  If no
match is found it returns a type-appropriate blank value, which is zero
for all numbers, empty string for all character/text types, or null
for dates and date-times.

!>example
!>noformat
table table_id:
   column column_id:
      chain chain_id:
         # a condition check
         test 00:
            compare: Chain Comparison Expression
            return: Chain Return Expression
         test 01:
            compare: Chain Comparison Expression
            return: Chain Return Expression
         # the unconditional default return value
         test 02:
            return: Chain Return Expression 
!<
!>noformat:A calculated and constrained value
table customers:
   # a credit limit, user-entered
   column amt_crlimit:
      suffix: _crlimit
   # the sum of the user's orders
   column amt_orders:
      suffix: _orders
      automation_id: SUM
      auto_formula: orders.amt_final
   # the sum of the user's invoices
   column amt_invoices:
      suffix: _invoices
      automation_id: SUM
      auto_formula: invoices.amt_open
   # put the two together and put a constraint on the result
   column amt_exposure:
      suffix: _exposure
      chain calc:
         # unconditional return
         test 00:
            return: @amt_orders + @amt_invoices
      chain cons:
         # only one test.  If it does not match, chain returns 
         # empty string and the constraint passes.
         test 00:
            compare: @amt_exposure > @amt_crlimit
            return: Credit Limit Exceeded            
!<
!<



==Properties==




#chain_id
''chain chain_id".  A chain can be named 'cons' or 'calc', 
all lowercase. No other chains are recognized, any other chain name will
be ignored.  A 'cons' chain defines a constraint, a 'calc' chain 
defines a calculation.

#chain.test
''chain.test test_id''.  A chain consists of one or more tests.  Each
test must be given a unique test_id.  The test_id values can be anything,
but keep in mind that they will be evaluated in their sort order.  Best
practice is to name tests '00', '01', and so on.

#chain.test.compare
''chain.test.compare''.  A [[Chain Comparison Expression]].

#chain.test.return
''chain.test.return''.  A [[Chain Return Expression]].  

*/

/**
name:Chain Comparison Expression

A Chain Comparison Expression is used in the [[table.chain]], and
the [[table.column.chain]].  The [[table.chain]] is used to conditionally
allow or prevent deletions and updates to tables.  
The [[table.column.chain]] is used to create calculated
columns and to put constraints onto column values.

A chain comparison expression is a column name, exactly
one space, an operator, exactly one space, and an optional column or 
literal value to compare to.

Column names must be prefixed with an '@' symbol, anything else is 
taken to be a literal value.

No quotes are required for string or date literal values.  Andromeda
knows the type of the column being compared to and will build the
appropriate SQL.

''Chain syntax rules are not very flexible.  Columns and operators must''
''be separated by exactly one space, never two or more.  Also, the''
''builder cannot detect if you leave an @ sign off, it will not''
''make any friendly suggestions like, "hey that looks like a column name,''
''did you forget an @sign?"''

!>example
!>noformat:Structure of an expression
compare: @column_id = Y
         |     |   ||||
         |     |   |||+--> a literal value
         |     |   ||+---> exactly one space
         |     |   |+----> the operator
         |     |   +-----> exactly one space
         |     +---------> a column name
         +---------------> the @sign must precede a column name
!<
!>noformat:Various 
table table_id:
   column order_status:
      chain calc:
         test 00:
            compare: @flag_cancelled = Y 
            return: CANCELLED
         test 01:
            compare: @date_invoice ISNULL
            return: OPEN
         test 03:
            return: INVOICED
!<
!<

The operators available are:

* EMPTY.  No second parameter.  Returns true if the first value is
type-appropriate empty, meaning 0 for numbers, empty string for all
character/text types, and null for dates and datetimes.
* !EMPTY.  Reverse of EMPTY.
* BETWEEN.  Three parameters, as in '@column_id BETWEEN x y'.  Notice
that the second and third parameters are listed one after the other
and separated by a space.
* !BETWEEN.  Reverse of BETWEEN
* NULL.  Value is null, as in '@column_id NULL'.
* !NULL.  Reverse of NULL
* IN.  Returns true if the first value is present in the second, where
the second is a comma-separated list of values, as in
'@column_id in a,b,c,d'.
* !IN.  Reverse of IN.
* >= greater than or equal to 
* > greater than
* <= Less than or equal to 
* < Less than 
* = Equal 
* <>Not Equal


*/

/**
name:Chain Return Expression

A Chain Return Expression is used in the [[table.chain]], and
the [[table.column.chain]].  The [[table.chain]] is used to conditionally
allow or prevent deletions and updates to tables.  
The [[table.column.chain]] is used to create calculated
columns and to put constraints onto column values.

A chain return expression can be as simple as a literal value, or it
may be a combination of an operator and a mix of columns and literals.

Column names must be prefixed with an '@' symbol, anything else is 
taken to be a literal value.

Chain return expressions may contain only one operator, they may not
contain parentheses, nested expressions or multiple operators.  Chain
return expressions are not evaluated as written, and they do not 
correspond to expressions as written in most languages.  Chain return
expressions are in fact lists of values that are used to build actual
calculations.

No quotes are required for string or date literal values.  Andromeda
knows the type of the column being compared to and will build the
appropriate SQL.

''Chain syntax rules are not very flexible.  Columns and operators must''
''be separated by exactly one space, never two or more.  Also, the''
''builder cannot detect if you leave an @ sign off, it will not''
''make any friendly suggestions like, "hey that looks like a column name,''
''did you forget an @sign?"''

!>example
!>noformat:Structure of a return expression
return: @column_id + 1 @column
        |     |   |||||  |
        |     |   |||||  +--> Third parameter, @sign plus column name 
        |     |   ||||+-> Exactly one space
        |     |   |||+--> a literal value
        |     |   ||+---> exactly one space
        |     |   |+----> the operator
        |     |   +-----> exactly one space
        |     +---------> a column name
        +---------------> the @sign must precede a column name
!<
!>noformat:Examples of return expressions
# return a literal string 'Y'
return: Y
# return string concatenation, a column value plus a literal
return: @column_id CONCAT _suffix
# sum four numbers together
return: @column_1 + @column_2 @column_3 @column_4
!<
!>noformat:Doing safe division
table table_id:
   column colunn_id:
   chain calc:
      test 00:
         compare: @amt_paid = 0
         return: 0
      test 01:
         return: @amt_booked / @amt_paid
!<
!<

The operators available are:

* + Add 
* - Subtract 
* * Multiply 
* / Divide 
* CON String concatenate; 
* CONU String concatenate w/underscores 
* subdyear  Date Subtract ret/Years
* EXTRACTYEAR  Year Part of Date
* EXTRACTMONTH Month Part of Date          
* EXTRACTDAY  Day Part of Date       
* REPLACE  String Replace 1st w/2nd 
* LPAD Pad out first parameter on left by number of spaces specified in
second parameter.
* RPAD Pad out first parameter on right by number of spaces specified
in second parameter.
* SUBS Substring.  Second parameter is start position, 3rd parameter
is number of characters.
* UPPER Upper Case               
* LOWER Lower Case               
* BITAND Bitwise AND              
* BITOR Bitwise OR              
* BITXOR Bitwise OR              
* BITNOT Bitwise NOT              

*/


/* =============================================== *\
   ===============================================
   List of elements
   ===============================================
\* =============================================== */
/**
name:Element Properties
parent:Database Reference

This section lists all properties that can be assigned to any
database element with pointers back to their main pages.
*/
   

/**
name:_default_
parent:Element Properties
*/

/**
name:alltables

A property of a [[column]].
*/
/**
name:afterins

A property of [[table.upsave]].
*/
/**
name:afterupd

A propety of [[table.upsave]].
*/
/**
name:automation_id

A property of a [[column]] or a [[table.column]].

See also [[Automations]].
*/

/**
name:auto_formula

A property of a [[column]] or a [[table.column]].

See also [[Automations]].
*/
/**
name:cascade_action

A property of [[table.upsave]].
*/
/**
name:colprec

A property of a [[column]].
*/

/**
name:colscale

A property of a [[column]].
*/
/**
name:copysamecols

A property of [[table.upsave]].
*/
/**
name:column_id_flag

A property of [[table.upsave]].
*/

/**
name:description

Description is a property of [[column]], [[table.column]], [[table]],
[[group]], [[module]], and [[table.foreign_key.column]].
*/

/**
name:dispsize

A property of a [[column]] or a [[table.column]].
*/
/**
name:flag_reset

A property of [[table.upsave]].
*/

/**
name:group_id

A property of [[group]], [[module.group]], [[table.group]].
*/
/**
name:idx_unique

A property of [[table.index]].
*/
/**
name:onlychanged

A property of an [[table.upsave]].
*/
/**
name:permdel

A property of [[group]], [[module.group]], [[group.module]],
and [[table.group]].
*/

/**
name:permins

A property of [[group]], [[module.group]], [[group.module]],
and [[table.group]].
*/
/**
name:permrole

A Y/N property of a [[group]] that determines if members of the group may
themselves create new users.
*/
/**
name:permrow

A Y/N property of a [[table.column]] that establishes row-level security
on that column.
*/

/**
name:permsel

A property of [[group]], [[module.group]], [[group.module]],
and [[table.group]].
*/
/**
name:permupd

A property of [[group]], [[module.group]], [[group.module]],
and [[table.group]].
*/
/**
name:primary_key

A property of a [[table.column]] and [[table.foreign_key]].
*/
/**
name:pk_change

A property of a [[table.column]].  Does not apply to [[table.foreign_key]].
*/
/**
name:range_from

A property of a [[table.column]].
*/
/** 
name:range_to

A property of a [[table.column]].
*/
/**
name:required

A property of a [[column]] or a [[table.column]].
*/
/**
name:solo

A property of a [[group]].  If Y, members of this group may not be in 
any other groups.
*/
/**
name:table_id_dest

A property of [[table.upsave]].
*/
/**
name:table_id_row

A property of a [[table.column]] that establishes row-level security.
*/

/**
name:uicols

A property of a [[column]] or a [[table.column]], applicable only
to columns of type 'text', that specifies how many columns should be
displayed in the HTML TEXTAREA used to edit the value.
*/
/**
name:uino

A property of a [[column]] or a [[table.column]].
*/
/**
name:uiro

A property of a [[column]] or a [[table.column]].
*/
/**
name:uirows

A property of a [[column]] or a [[table.column]], applicable only
to columns of type 'text', that specifies how many rows should be
displayed in the HTML TEXTAREA used to edit the value.
*/

/**
name:uisearch

A property of a [[table.column]] and [[table.foreign_key]].
*/
/**
name:uiwithnext

A property of a [[table.column]].
*/
/**
name:value_max

The maximum allowed value of a [[column]] or [[table.column]].
*/
/**
name:value_min

The minimum allowed value of a [[column]] or [[table.column]].
*/

?>
