<?php
class x6skinJob extends androX6 {
    function x6main() {
        ?>
        <h1>CSS Skin Generator</h1>
        
        <p>This generator creates skins for use with the x6
           template.  The x6 template is the foundation of 
           Andromeda's business interface.  The skin system
           gives a programmer the flexibility to change the
           appearance of the interface without needing to 
           recode an entire template.
        </p>
        
        <p>The default skin is defined in 
           /templates/x6/skinsources/x6skin.Default.yaml.  Additional
           skins that are included with Andromeda are
           also in the /templates/x6/skinsources directory.
        </p>
        
        <p>You can define your own skins in your application
           directory.  They should be named x6skin.-name-.yaml.
           A custom skin should begin as a copy of the default
           file, afterwhich you can change anything you like.
        
        <p>Finally, the program writes a serialized array
           into /templates/x6/skins/skins.serialized.txt, which
           contains the names and descriptions of all
           generated skins.  This is used by the template 
           to produce an HTML SELECT that displays the
           available skins.
        </p>
        
        <?php
        
        $errors = $this->mainprocess();
        
        if(count($errors)>0) {
            echo "<h2>Errors Found</h2>";
            echo "No output was generated.";
            hprint_r($errors);
        }
    }
    
    # ==================================================================
    #
    # MAIN PROCESS.  Top level processing routine
    #
    # ==================================================================
    function mainProcess() {
        $errors = array();
        
        # Make x6main scrollable
        jqDocReady("$('.x6body').css('overflow-y','scroll')");
        
        # Define the directories
        $dirx6   = fsDirTop().'templates/x6/';
        $dirx6src= $dirx6.'skinsources/';
        $dirapp  = fsDirTop().'application/';

        # Now scan for other skins and process those
        $dirs = array($dirx6src,$dirapp);
        foreach($dirs as $dir) {
            $files = scandir($dir);
            
            foreach($files as $file) {
                # These lines filter out entries that are not skins
                $apieces = explode('.',$file);
                if(count($apieces)!=3       ) continue;
                if($apieces[0]    !='x6skin') continue;
                if($apieces[2]    !='yaml'  ) continue;
                
                # Load the file and process it.  We assume the
                # middle piece of the file is the name of the
                # template.
                $yaml = loadYaml($dir.$file);
                $this->writeCSS($apieces[1],$yaml['defines'],$yaml['css']);
            }
        }
        
    }
    
    # ==================================================================
    #
    # CREATE A SKIN. Top-level processing to parse out the
    #                arrays and then call the master routine
    #
    # ==================================================================
    function writeCSS($name,$defines,$css) {
        # Convert colors @ signs to # signs
        $colors = array();
        foreach($defines['colors'] as $group=>$values) {
            foreach($values as $key=>$value) {
                $colors[$group][$key] = str_replace('@','#',$value);
            }
        }
        unset($defines['colors']);
        
        # Extract sizes
        $constants = $defines['sizes']['fixed'];
        $sSizes    = $defines['sizes']['scalable'];
        unset($defines['sizes']);
        
        # Flatten all other constants, add them to fixed sizes
        foreach($defines as $group=>$values) {
            foreach($values as $key=>$value) {
                $constants[$key] = $value;
            }
        }

        # Make an array of size combinations
        $sCombos = array(
            array(800,600)
            ,array(1024,768)
            ,array(1280,1024)
            ,array(1440,900)
            ,array(1400,1050)
            ,array(1650,1050)
        );
        foreach($colors as $cgName=>$colorValues) {
            foreach($sCombos as $sCombo) {
                $const2 = array_merge($colorValues,$constants);
                echo "<h3>Skin variation</h3>";
                echo "Color: $cgName<br/>";
                echo "Sizes: {$sCombo[0]}, {$sCombo[1]}<br/>";
                list($constants,$cssFinal) = $this->resolveCSS(
                    $const2,$sSizes,$sCombo,$css
                );
                $this->writeFiles(
                    $constants,$name,$cgName,$sCombo[0],$cssFinal
                );
                $skinFiles["$name - $cgName - {$sCombo[0]}"] 
                    = "$name.$cgName.{$sCombo[0]}"; 
            }
        }
        
        # Finally, write the list of skins out to apppub
        file_put_contents(
            fsDirTop().'templates/x6/skinsphp/x6skins.ser.txt'
            ,serialize($skinFiles)
        );

    }
    
    
    # ==================================================================
    #
    # resolveCSS.  Actually process a set of files
    #
    # ==================================================================
    function resolveCSS($constants,$sSizes,$sCombo,$css) {
        # Work out the size scaling and modify all sizes, adding
        # them to the flat list of contants
        $ratiox = $sCombo[0]/1024;
        $ratioy = $sCombo[1]/768 ;
        foreach($sSizes['heights'] as $key=>$value) {
            $numvalue = str_replace('px','',$value);
            $constants[$key] = intval($numvalue * $ratioy).'px';
        }
        foreach($sSizes['widths']  as $key=>$value) {
            $numvalue = str_replace('px','',$value);
            $constants[$key] = intval($numvalue * $ratiox).'px';
        }
        
        # Call the resolver first to have constants resolved
        # against each other, then resolve the final CSS 
        # with the constants.
        $x = array();
        $constants = $this->resolveVars( $constants,$constants);
        $cssFinal  = $this->resolveRules($constants,$css      );
        
        return array($constants,$cssFinal);
    }
    
    function resolveVars($source,$destination) {
        $changed = 1;
        $count = 0;
        while($changed<>0) {
            $count++;
            $changed = 0;
            foreach($destination as $name=>$value) {
                $values = explode(' ',$value);
                $xvals  = array();
                foreach($values as $val) {
                    if(substr($val,0,1)!='*') {
                        $xvals[] = $val;
                    }
                    else {
                        $key = substr($val,1);
                        if(!isset($source[$key])) {
                            echo "Error: no value named $key";
                        }
                        else {
                            $changed++;
                            $xvals[] = $source[$key];
                        }
                    }
                }
                $destination[$name] = implode(' ',$xvals);
            }
            if($count > 1000) {
                echo "<h3>STOPPING AFTER 1000, probable circular dependency";
                break;
            }
        }
        return $destination;
    }
    
    function resolveRules($source,$css) {
        # Make a list of constants, we will report those
        # that are not used.
        $notused = array();
        foreach($source as $key=>$value) {
            $notused[$key] = 1;   
        }
        
        $count   = 0;
        $changed = 1;
        $retval=array();
        foreach($css as $section=>$rules) {
            foreach($rules as $rule=>$properties) {
                foreach($properties as $name=>$value) {
                    $values = explode(' ',$value);
                    $xvals  = array();
                    foreach($values as $val) {
                        if(substr($val,0,1)!='*') {
                            $xvals[] = $val;
                        }
                        else {
                            $key = substr($val,1);
                            if(!isset($source[$key])) {
                                echo "Error: no value named"
                                    ."<b>$key</b><br/>";
                            }
                            else {
                                $changed++;
                                if(isset($notused[$key])) 
                                    unset($notused[$key]);
                                $xvals[] = $source[$key];
                            }
                        }
                    }
                    $retval[$rule][$name] = implode(' ',$xvals);
                }
            }
        }
        if(count($notused)>0) {
            echo "<br><b>These constants were not used:</b>";
            hprint_r($notused);
        }
        
        return $retval;
    }
    
    # ==================================================================
    #
    # Write out the files
    #
    # ==================================================================
    function writeFiles($constants,$skin,$color,$size,$cssFinal) {
        # At long last, after all of that work, we are ready
        # to write the file.  The last bit of processing we
        # do as we go along here is to remove empty or null
        # values to reduce clutter.
        ob_start();
        ?>
/* ==================================================== *\
 * Andromeda Generated CSS file                         
 * Generated: <?php echo date('r',time())."\n"?>
 * Skin, color, size: <?php echo "$skin, $color, $size\n"?>
\* ==================================================== */
        <?php
        echo "\n";
        foreach($cssFinal as $selector=>$rules) {
            if(substr($selector,0,1)=='@') $selector = '#'.substr($selector,1);
            $selector = str_replace(';',':',$selector);
            echo "$selector {\n";
            foreach($rules as $rule=>$value) {
                if(trim($value)=='' || is_null($value)) continue;
                echo "    ".str_pad($rule.':',25,' ',STR_PAD_RIGHT)."$value;\n";
            }
            echo "}\n\n";
        }
        $filename 
            = fsDirTop()."templates/x6/skins/x6skin.$skin.$color.$size.css";
        $cssDone = ob_get_clean();
        file_put_contents($filename,$cssDone);
        
        # Another job, write out the CSS as a serialized
        # associative array so that plugins and other code
        # knows how big things are.  Also add in all of the
        # options to the array so we know the original
        # constants by their nicknames.
        #
        $phpStuff = array('defines'=>$constants,'css'=>$cssFinal);
        $filename = fsDirTop()
            ."templates/x6/skinsphp/x6skin.$skin.$color.$size.ser.txt";
        file_put_contents($filename,serialize($phpStuff));
    }
}
?>
