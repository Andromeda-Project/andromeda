<?php
// These are things that belong in the header of any template
$title=vgfGet("PageTitle");
?>
	<title><?=$title?></title>

   <style type="text/css">
   /* Styles generated specifically for a page */
   <?=ElementOut("styles");?>
   </style>
   <script language="javascript" type="text/javascript">
   /* Script generated specifically for a page */
   <?=ElementOut("script");?>
   </script>

   <!-- Standard andromeda js library -->
   <?php
        jsInclude( '/' .tmpPathInsert() .'clib/raxlib.js?unq=' .md5( Session_Id() ) );
   ?>
   <!-- CSS is objects generated in script only, all else is in template css -->
   <link rel='stylesheet' href="/<?=tmpPathInsert()?>clib/raxlib.css?unq=<?=md5(Session_Id())?>"> </script>

    <!-- This brings in Calendar from HTML Goodies -->
    <?php if(vgfGet('suppress_goodies_calendar')!==true) { ?>
    <link rel="stylesheet" 
        href="/<?=tmpPathInsert()?>clib/dhtmlgoodies_calendar.css" media="screen" />
    <?php
        jsInclude(  '/' .tmpPathInsert() .'clib/dhtmlgoodies_calendar.js?unq=' .md5(Session_Id() ) );
    ?>
    <?php } ?>
   
    <!-- This brings in AJAX select box from HTML Goodies -->
    <?php /* 
    <script type="text/javascript" 
            src="/<?=tmpPathInsert()?>clib/ajax-dynamic-list/js/ajax-dynamic-list.js?unq=<?=md5(Session_Id())?>">
    </script>
    <script type="text/javascript"
            src="/<?=tmpPathInsert()?>clib/ajax-dynamic-list/js/ajax.js?unq=<?=md5(Session_Id())?>">
    </script>         
	<link rel="stylesheet" 
        href="/<?=tmpPathInsert()?>clib/ajax-dynamic-list/ajax-dynamic-list.css" media="screen" />
    */ ?>
        
    <!-- This brings in the DHTML Goodies tooltip -->
    <?php if(vgfGet('suppress_goodies_tooltip')!==true) { ?>
    <link rel="stylesheet" href="/<?=tmpPathInsert()?>clib/dhtml-tt/css/form-field-tooltip.css" media="screen" type="text/css">
    <?php
        jsInclude( '/' .tmpPathInsert() .'clib/dhtml-tt/js/rounded-corners.js?unq=' .md5(Session_Id() ) );
        jsInclude( '/' .tmpPathInsert() .'clib/dhtml-tt/js/form-field-tooltip.js?unq=' .md5(Session_Id()) );
    }
    ?>
   
   
    <!-- Positioning styles, neutral to color, font, etc. -->
    <?php if(vgfGet('suppress_andromeda_css')!==true || vgfGet('x4')===true) { ?>
    <link rel="stylesheet" href="/<?=tmpPathInsert()?>clib/andromeda.css" />
    <?php } ?>

    <!-- x2 styles, only if not using x4 and logged in -->
    <?php if(! (vgfGet('x4')===true && LoggedIn()) ) { ?> 
    <link href="<?='/'.tmpPathInsert().'templates/'.$mainframe->getTemplate().'/css/x2.css'?>" rel="stylesheet" type="text/css" />
    <?php } ?>
   
    <!-- x4 styles and script -->
    <?php /*
    <?php if(vgfGet('suppress_x4')!==true) { ?>
    <link href="<?='/'.tmpPathInsert().'clib/x4.css?unq='.md5(Session_Id())?>'" rel="stylesheet" type="text/css" />
    <script type="text/javascript"  src="/<?=tmpPathInsert()?>clib/x4.js?unq=<?=md5(Session_Id())?>"> </script>
    <?php } ?>
    */ ?>
   

   <!-- Public variables -->
   <script type="text/javascript">
   var function_return_value=false;
   </script>
   
   
