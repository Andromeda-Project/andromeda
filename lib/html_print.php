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
?>
<?php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
<!DOCTYPE html
 PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<style type="text/css">
	body { font-family: sans-serif; margin: 0px; padding: 0px; border: 0px; }
	h1 { border-top: 2px solid black; border-bottom: 2px solid black; }
	
	div.footer { border-top: 1px solid black; margin-top: 3em; padding-top: .5em; }
	div.pagebreak { page-break-after: always; }

	div.pageall {
			font: 11pt serif;
			font-weight: bolder;
			position: relative;
			margin: 0px; padding: 0px;
			width: 200mm;
			height: 265mm;
			background-color: silver;
	}
	
	div serif8  { font: 8pt  serif; }
	div serif9  { font: 9pt  serif; }
	div serif10 { font: 10pt serif; }
	div serif11 { font: 11pt serif; }
	div serif12 { font: 12pt serif; }
	div sans8   { font: 8pt  serif; }
	div sans9   { font: 9pt  serif; }
	div sans10  { font: 10pt serif; }
	div sans11  { font: 11pt serif; }
	div sans12  { font: 12pt serif; }
	
	
		
	div.pabs {
		position: absolute;
	}
	
	div.money {
		position: absolute;
		width: 20mm;
		text-align: right;
	}

	div.smallprint {  font-size: 2.7mm; } 
	
	</style>
   <title><?php echo V("PageTitle")?></title>
</head>
<body>

<?php echo V("HTML")?>

</body>
<script>window.print();</script>
</html>

