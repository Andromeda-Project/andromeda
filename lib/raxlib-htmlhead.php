<?php
// These are things that belong in the header of any template
?>
	<title><?=vgfGet("PageTitle")?></title>

   <style type="text/css">
   /* Styles generated specifically for a page */
   <?=ElementOut("styles");?>
   </style>
   <script language="javascript" type="text/javascript">
   /* Script generated specifically for a page */
   <?=ElementOut("script");?>
   </script>

   <!-- Standard andromeda js library -->
   <script type="text/javascript"  src="/<?=tmpPathInsert()?>clib/raxlib.js?unq=<?=md5(Session_Id())?>"> </script>

   <!-- This brings in Calendar from HTML Goodies -->
	<link rel="stylesheet" 
        href="/<?=tmpPathInsert()?>clib/dhtmlgoodies_calendar.css" media="screen" />
	<script type="text/javascript" 
            src="/<?=tmpPathInsert()?>clib/dhtmlgoodies_calendar.js?unq=<?=md5(Session_Id())?>">
   </script>
   
   <!-- This brings in AJAX select box from HTML Goodies -->
   <script type="text/javascript" 
            src="/<?=tmpPathInsert()?>clib/ajax-dynamic-list/js/ajax-dynamic-list.js?unq=<?=md5(Session_Id())?>">
   </script>
   <script type="text/javascript"
            src="/<?=tmpPathInsert()?>clib/ajax-dynamic-list/js/ajax.js?unq=<?=md5(Session_Id())?>">
   </script>         
	<link rel="stylesheet" 
        href="/<?=tmpPathInsert()?>clib/ajax-dynamic-list/ajax-dynamic-list.css" media="screen" />
        
   <!-- This brings in the DHTML Goodies tooltip -->
	<link rel="stylesheet" href="/<?=tmpPathInsert()?>clib/dhtml-tt/css/form-field-tooltip.css" media="screen" type="text/css">
	<script type="text/javascript" src="/<?=tmpPathInsert()?>clib/dhtml-tt/js/rounded-corners.js?unq=<?=md5(Session_Id())?>"></script>
	<script type="text/javascript" src="/<?=tmpPathInsert()?>clib/dhtml-tt/js/form-field-tooltip.js?unq=<?=md5(Session_Id())?>"></script>
   
   
   <!-- Positioning styles, neutral to color, font, etc. -->
   <link rel="stylesheet" href="/<?=tmpPathInsert()?>clib/andromeda.css" />

   <!-- x2 styles -->
   <link href="<?='/'.tmpPathInsert().'templates/'.$mainframe->getTemplate().'/css/x2.css'?>" rel="stylesheet" type="text/css" />
   
   <!-- x4 styles and script -->
   <link href="<?='/'.tmpPathInsert().'clib/x4.css?unq='.md5(Session_Id())?>'" rel="stylesheet" type="text/css" />
   <script type="text/javascript"  src="/<?=tmpPathInsert()?>clib/x4.js?unq=<?=md5(Session_Id())?>"> </script>
   

   <!-- Public variables -->
   <script type="text/javascript">
   var function_return_value=false;
   </script>
   
   
