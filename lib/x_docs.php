<?php
// This page is only saved as PHP because our editor, jedit, 
// recognizes the documentation format
/**
name:Placeholder: Classes
group:PHP Reference

=Introduction=
Andromeda is a complete 3-tier system, which means it contains 
architectural elements for the browser, the web server, and the database.

Each of these tiers is fundamentally different from the others.  The browser
displays discreet pages, the programmer creates classes to execute on
the web server
and the database organizes data into tables.  Because of the differing 
nature of the layers, every n-tier architecture must make basic decisions
about how they will relate to each other.

Many architectures start out by declaring that each tier will
be made to act like one of the others, and then continue by building an 
entire architecture around trying to 
make one or more tiers fit all of the assumptions of the others.  These
architectures always declare something like "every table gets exactly one
class" or "Each page corresponds to a table in the database."  Intuitively
this approach is suspect, because presumably the different layers
were developed over time to meet different needs, and pounding them all
into the same shape does not seem like the best way to get the most out of
each tier.  What happens when you have two pages for one table, or a
wizard page that takes information from multiple pages, or 
many tables that can be manipulated entirely by library code so that 
coding up all of those classes would be a waste of time?

Andromeda seeks to handle each tier as is appropriate to its nature.
Because it is true that in many cases a page is a class
is a table, then in those cases 
Andromeda makes it easy to think and code in those terms.  But because
it is not always true that a page is a class is a table, these three 
are kept ''loosely coupled'' and it is always possible to match any
number of pages to a table, or classes to a page, and so forth.

=Tables=

The design of tables in Andromeda is governed by the classical rules
of [[normalization]] and by Andromeda's [[Database Automation Features]].
The automation that can be built into an Andromeda database allows all of
the business rules, including security, to be handled within the 
database server.  

HTML pages and PHP classes are never considered when designing tables.
The tables should be designed solely with the aim of accurately recording
data.  

=Classes and Objects=



=Pages=

The term "web page" is very loosely defined.  Andromeda uses the term in
the same that users do, they click on a button, the computer churns for
a seond or two, and there is a display.  This the user calls a "page". 
They click on something else, another page comes up.  

A page, therefore, is what goes back to the browser 

Some programmers will think of a page as equal to a file on disk.


?>
