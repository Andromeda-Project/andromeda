<?php
class XWelcome extends XTable2
{
    public function main()
    {
        if (SessionGet('MMRMENU')=='Y') {
            return $this->NewMain();
        }
      
        if (SessionGet("WML")) {
            return $this->mainWML();
        }
        $this->PageSubtitle="Welcome to the System!";
        ?>
       <h1>Welcome!</h1>
      
       <p>This is the default content of the "x_welcome" class that
         is part of the Andromeda library.
       </p>
         
       <p>A person will see this page when they have logged in but
         have not requested an actual page, so it is often what they
         see immediately after a password is accepted.
       </p>
      
       <P>You can override this file by putting a file named "x_welcome.php"
         in your application directory and putting a class named
         "x_welcome" into that file.
       </p>
        <?php

    }

    // In WML Land, when you log in you get the menu   
    public function mainWML()
    {
        include "menu_wml_".SessionGet("UID").".php";
    }
   
    public function NewMain()
    {
        ?>
       <div style="padding-left:50px; padding-top: 40px; font-size:2em; font-family: Courier">
        <?php
        if (gp('x_menu')  <>'') {
            return $this->MenuTable();
        }
        if (gp('x_module')<>'') {
            return $this->MenuModule();
        }
      
        $AGMENU=SessionGet('AGMENU');
        ?>
        <?php
        $x=1;
        foreach ($AGMENU as $module_name=>$module_info) {
            $h=hLink('', $module_info['description'], "?x_module=$module_name");
            echo "$x - $h<br><br><br><br>";
            $x++;
        }
        ?>
       </div>
        <?php

    }
   
    public function MenuModule()
    {
        $AGMENU=SessionGet('AGMENU');
        $module=gp('x_module');
        $x=1;
        $ax=array();
        foreach ($AGMENU[$module]['items'] as $menu_name=>$menu_info) {
            $hl = "?x_module=$module&x_menu=$menu_name";
            $h=hLink('', $menu_info['description'], $hl);
            echo "$x - $h<br><br><br><br>";
            $x++;
            $ax[]=$x;
        }
        echo "Z - <a href=\"?gp_page=x_welcome\">Exit</a><br><br><br><br>";
        $ax[]='Z';
    }
   
    public function MenuTable()
    {
        $AGMENU=SessionGet('AGMENU');
        $module  =gp('x_module');
        $table_id=gp('x_menu');
        echo "<b>".$AGMENU[$module]['items'][$table_id]['description']."</b>";
        ?>
       <br><br><br><br>
       1 - <a href="?gp_page=<?php echo $table_id?>&gp_mode=browse">View</a>
       <br><br><br><br>
       2 - <a href="?gp_page=<?php echo $table_id?>&gp_mode=search">Edit</a>
       <br><br><br><br>
       3 - <a href="?gp_page=<?php echo $table_id?>&gp_mode=search">Delete</a>
       <br><br><br><br>
       4 - <a href="?gp_page=<?php echo $table_id?>&gp_mode=ins">Add</a>
       <br><br><br><br>
        <?php
        $x = 5;
        // SEAN: Here is where you put the extra stuff like "on hold"
        global $extramenu;
        $rapidchanges  = $extramenu;
        $rapidchanges  = ArraySafe($rapidchanges, $table_id);
        foreach ($rapidchanges as $change => $columns) {
            $desc = ArraySafe($columns, '_desc');
            unset($columns['_desc']);
            foreach ($columns as $name=>$val) {
                $columns[$name] = 'gp_upd_'.$name."=".$val;
            }
            $parms   = implode('&', $columns);
            ?>
            <?php echo $x?> - <a href="?gp_page=x_pkc&gp_table_upd=<?php echo $table_id.'&'.$parms?>"><?php echo $desc?></a>
            <br><br><br><br>
            <?php
            $x++;
        }
        //hprint_r($rapidchanges);
        ?>
       Z - <a href="?x_module=<?php echo $module?>">Exit</a>
       <br><br><br><br>
        <?php

    }
}
?>
