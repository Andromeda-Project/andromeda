<?php
/** ensure this file is being included by a parent file */
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );
/* returns a properly nested UL menu */

//mosShowListMenu('mainmenu'); // currently hard-coded to mainmenu
ini_set('arg_separator.output','&amp;');

function mosShowListMenu($menutype) {
   // -------------------------------------------------------
   // Andromeda Code: If we are in an Andromeda situation
   // then everything is vastly simplified, we already have
   // the menu and we don't do much conversion
   // -------------------------------------------------------
   if(defined('_ANDROMEDA_JOOMLA')) {
      if (!LoggedIn()) return;
      // KFD 7/6/07, cache the menu so we don't have to do
      // this on every call.  
      // Cachegrind cost to build menu          : 259 / 199
      // Cachegrind cost logging in             : 140
      // Cachegrind cost login, cache to session: 2!!!!
      // Cachegrind cost to cache to disk       : 400!       
      $menu=SessionGet('menu','');
      if($menu<>'') {
         echo $menu;
         return;
      }
      ob_start();

      $children=array();
      $open=array();
      $indents = array(
      // block prefix / item prefix / item suffix / block suffix
      array( "<ul>", "<li>" , "</li>", "</ul>" ),
      );
      $class_sfx=null;
      $hilightid=SessionGET('AGMENU_MODULE');
      
      $menus=SessionGET("AGMENU");
      foreach($menus as $menuid=>$menuinfo) {
         //if($menuid=='datadict') continue;
         //if($menuid=='sysref')   continue;
         $x=new joomla_fake;
         $x->type='url';
         $x->id=$menuid;
         $x->link="?x_module=".urlencode($menuid);
         $x->browserNav='';
         $x->name=$menuinfo['description'];
         $children[0][]=$x;
         foreach($menuinfo['items'] as $page=>$pageinfo) {
            $x=new joomla_fake;
            $x->type='url';
            $x->id=$page;
            $x->link="?x_module=$menuid&gp_page=".urlencode($page);
            if(ArraySafe($pageinfo,'menu_parms')<>'') {
               $x->link.='&'.urlencode($pageinfo['menu_parms']);  
            }
            $x->browserNav='';
            $x->name=$pageinfo['description'];
            $children[$menuid][]=$x;
         }
      }
      mosRecurseListMenu( 0, 0, $children, $open, $indents, $class_sfx, $hilightid );
      
      $menu=ob_get_clean();
      echo $menu;
      SessionSet('menu',$menu);
      //$fsMenuFileHTML=ob_get_clean();
      //file_put_contents($fsMenuFile,$fsMenuFileHTML);
      //echo $fsMenuFileHTML;
      return;
   }
   // -------------------------------------------------------
   // Andromeda Code: END
   // -------------------------------------------------------
   
	global $database, $my, $cur_template, $Itemid;
	global $mosConfig_absolute_path, $mosConfig_live_site, $mosConfig_shownoauth;
    
	$class_sfx = null;
	error_reporting(E_ALL ^ E_NOTICE);
	$hilightid = null;

    /* If a user has signed in, get their user type */
	$intUserType = 0;
	if($my->gid){
		switch ($my->usertype)
		{
			case 'Super Administrator':
			$intUserType = 0;
			break;
			case 'Administrator':
			$intUserType = 1;
			break;
			case 'Editor':
			$intUserType = 2;
			break;
			case 'Registered':
			$intUserType = 3;
			break;
			case 'Author':
			$intUserType = 4;
			break;
			case 'Publisher':
			$intUserType = 5;
			break;
			case 'Manager':
			$intUserType = 6;
			break;
		}
	}
	else
	{
		/* user isn't logged in so make their usertype 0 */
		$intUserType = 0;
	}

   
	if ($mosConfig_shownoauth) {
     $database->setQuery("SELECT m.*, count(p.parent) as cnt" .
	"\nFROM #__menu AS m" .
	"\nLEFT JOIN #__menu AS p ON p.parent = m.id" .
    "\nWHERE m.menutype='$menutype' AND m.published='1'" .
	"\nGROUP BY m.id ORDER BY m.parent, m.ordering ");
      } else {
     $database->setQuery("SELECT m.*, sum(case when p.published=1 then 1 else 0 end) as cnt" .
	"\nFROM #__menu AS m" .
	"\nLEFT JOIN #__menu AS p ON p.parent = m.id" .
    "\nWHERE m.menutype='$menutype' AND m.published='1' AND m.access <= '$my->gid'" .
	"\nGROUP BY m.id ORDER BY m.parent, m.ordering ");
   }
   

	$rows = $database->loadObjectList( 'id' );
	echo $database->getErrorMsg();
	
		//work out if this should be highlighted
		$sql = "SELECT m.* FROM #__menu AS m"
		. "\nWHERE menutype='". $menutype ."' AND m.published='1'"; 
		$database->setQuery( $sql );
		$subrows = $database->loadObjectList( 'id' );
		$maxrecurse = 5;
		$parentid = $Itemid;

		//this makes sure toplevel stays hilighted when submenu active
		while ($maxrecurse-- > 0) {
			$parentid = getParentRow($subrows, $parentid);
			if (isset($parentid) && $parentid >= 0 && $subrows[$parentid]) {
				$hilightid = $parentid;
			} else {
				break;	
			}
		}	
  //echo "<!--[if lte IE 7]>\n";		
  include_once( "$mosConfig_absolute_path/templates/" . $cur_template . "/js/ie.js" );
  //echo "<![endif]-->\n";

	$indents = array(
	// block prefix / item prefix / item suffix / block suffix
	array( "<ul>", "<li>" , "</li>", "</ul>" ),
	);

    // establish the hierarchy of the menu
	$children = array();

    // first pass - collect children
    foreach ($rows as $v ) {
		$pt = $v->parent;
		$list = @$children[$pt] ? $children[$pt] : array();
		array_push( $list, $v );

        $children[$pt] = $list;

    }

    // second pass - collect 'open' menus
	$open = array( $Itemid );
	$count = 20; // maximum levels - to prevent runaway loop
	$id = $Itemid;
	while (--$count) {
		if (isset($rows[$id]) && $rows[$id]->parent > 0) {
			$id = $rows[$id]->parent;
			$open[] = $id;
		} else {
			break;
		}
	}

	$class_sfx = null;

    mosRecurseListMenu( 0, 0, $children, $open, $indents, $class_sfx, $hilightid );
}

/**
* Utility function to recursively work through a vertically indented
* hierarchial menu
*/
function mosRecurseListMenu( $id, $level, &$children, $open, &$indents, $class_sfx, $highlight ) {
	global $Itemid;
	global $HTTP_SERVER_VARS, $mosConfig_live_site;

	if (@$children[$id]) {
		$n = min( $level, count( $indents )-1 );
		
	

        echo $indents[$n][0];

		foreach ($children[$id] as $row) {

		
		        switch ($row->type) {
          		case 'separator':
          		// do nothing
          		$row->link = "seperator";

          		break;
          
          		case 'url':
          		if ( eregi( 'index.php\?', $row->link ) ) {
        				if ( !eregi( 'Itemid=', $row->link ) ) {
        					$row->link .= '&Itemid='. $row->id;
        				}
        			}
          		break;
          
          		default:
          			$row->link .= "&Itemid=$row->id";
          
          		break;
          	}

            $li =  "\n".$indents[$n][1] ;
            /* ANDROMEDA CHANGE */
            if(defined('_ANDROMEDA_JOOMLA')) {
               $current_itemid='';
               if($row->id==SessionGet('AGMENU_MODULE')) {
                  $li="<li class='active'>";
               }
            }
            else {
               $current_itemid = trim( mosGetParam( $_REQUEST, 'Itemid', 0 ) );
               if ($row->link != "seperator" &&
                           $current_itemid == $row->id || 
                     $row->id == $highlight || 
                   (sefRelToAbs( substr($_SERVER['PHP_SELF'],0,-9) . $row->link)) == $_SERVER['REQUEST_URI'] ||
                   (sefRelToAbs( substr($_SERVER['PHP_SELF'],0,-9) . $row->link)) == $HTTP_SERVER_VARS['REQUEST_URI']) {
                        $li = "<li class='active'>";
                   }
            }
	         echo $li;
								
            // KFD change 4/9/07, AJAX-ification effort
            //echo hjxCheckFirst($row->name,$row->link);
            //txt = "<a href=\"$mitem->link\">$mitem->name</a>";
            echo mosGetLink( $row, $level, $class_sfx );
						mosRecurseListMenu( $row->id, $level+1, $children, $open, $indents, $class_sfx, "" );
            echo $indents[$n][2];

        }
		echo "\n".$indents[$n][3];

	}
}

function getParentRow($rows, $id) {
		if (isset($rows[$id]) && $rows[$id]) {
			if($rows[$id]->parent > 0) {
				return $rows[$id]->parent;
			}	
		}
		return -1;
	}

/**
* Utility function for writing a menu link
*/
function mosGetLink( $mitem, $level, $class_sfx='' ) {
	global $Itemid, $mosConfig_live_site;
	$txt = '';
	
	$mitem->link = str_replace( '&', '&amp;', $mitem->link );

   /* ANDROMEDA CHANGE */
   // Define first menu item with a specific name, so we can give it focus
   global $AndroFirstMenu;
   if(is_null($AndroFirstMenu)) {
      $AndroID='name="FIRSTMENU" id="FIRSTMENU"';
      $AndroFirstMenu=1;
   }
   else {
      $AndroID='';
   }
   /* ANDROMEDA CHANGE */
   // Put tab indexes on the top level.  NOTE the call 
   $AndroTABIndex="tabindex=\"$level\"";
   if($level==0) {
      $AndroTABIndex='tabindex="'.hpTabIndexNext(200).'"';
   }
   else {
      $AndroTABIndex='tabindex="9999"';
   }   
   
   /* ANDROMEDA CHANGE */
   if(!defined('_ANDROMEDA_JOOMLA')) {
      if (strcasecmp(substr($mitem->link,0,4),"http")) {
         $mitem->link = sefRelToAbs($mitem->link);
      }
   }


    switch ($mitem->browserNav) {
		// cases are slightly different
		case 1:
		// open in a new window
    if ($mitem->cnt > 0) {
		   if ($level == 0) {
                $txt = "<a class=\"topdaddy\" target=\"_window\"  href=\"$mitem->link\">$mitem->name</a>";
		   } else {
                $txt = "<a class=\"daddy\" target=\"_window\"  href=\"$mitem->link\">$mitem->name</a>";
		   }
		} else {
		   	$txt = "<a href=\"$mitem->link\" target=\"_window\" >$mitem->name</a>\n";
		}
		break;

		case 2:

         // open in a popup window
         // KFD 7/6/07, get rid of warning
         $menuclass='';
    if ($mitem->cnt > 0) {
				if ($level == 0) {
                $txt = "<a href=\"#\" class=\"topdaddy\" onClick=\"javascript: window.open('$mitem->link', '', 'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=780,height=550');\" class=\"$menuclass\">$mitem->name</a>\n";
		   } else {
                $txt = "<a href=\"#\" class=\"daddy\" onClick=\"javascript: window.open('$mitem->link', '', 'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=780,height=550');\" class=\"$menuclass\">$mitem->name</a>\n";
		   }
		} else {
		    $txt = "<a href=\"#\" onClick=\"javascript: window.open('$mitem->link', '', 'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=780,height=550');\" class=\"$menuclass\">$mitem->name</a>\n";
		}

		break;

		case 3:
		// don't link it
    if ($mitem->cnt > 0) {
		   if ($level == 0) {
                $txt = "<a class=\"topdaddy\">$mitem->name</a>";
		   } else {
                $txt = "<a class=\"daddy\">$mitem->name</a>";
		   }
		} else {
		   	$txt = "<a>$mitem->name</a>\n";
		}

		break;

		default:	// formerly case 2
		// open in parent window
		if (isset($mitem->cnt) && $mitem->cnt > 0) {
		    if ($level == 0) {
                $txt = "<a $AndroTABIndex $AndroID class=\"topdaddy\" href=\"$mitem->link\">$mitem->name</a>";
		   } else {
                $txt = "<a $AndroTABIndex $AndroID class=\"daddy\" href=\"$mitem->link\"> $mitem->name</a>";
		   }
		} else {
		   $txt = "<a $AndroTABIndex $AndroID  href=\"$mitem->link\">$mitem->name</a>";
		}
        break;
	}
    return $txt;
}
?>



