<?php
class x6skinJob extends androX6 {
    function x6main() {
        ?>
        <h1>EXPERIMENTAL CSS Generator</h1>
        
        <p>This is an experimental program being used by Ken 
           and Jack to create a skinnable css system.  It is
           <i>not</i> suitable for production use and may
           change significantly.
        </p>
        
        <p>This program will use templates/x6/base.yaml to 
           obtain the list of required style rules that
           must be written.
        </p>
        
        <p>This program templates/x6/ for files named x6skin*.
           It expects to find a file of colors, and any number
           of files that define sizes.  An example for a color
           scheme of three sizes might be:
        </p>
        <ul><li>templates/x6/x6skin.win2k.blue.color.yaml
            <li>templates/x6/x6skin.win2k.800.size.yaml
            <li>templates/x6/x6skin.win2k.1024.size.yaml
            <li>templates/x6/x6skin.win2k.1400.size.yaml
        </ul>
        
        <p>The program also scans appclib for files that follow
           the same naming system, and puts the resulting CSS
           files into the apppub directory.
        
        <p>The program will generate CSS files with the
           same names as the yaml files, which can then be
           picked by the user at run-time to change their
           skin:
        </p>
        <ul><li>templates/x6/x6skin.win2k.blue.800.css
            <li>templates/x6/x6skin.win2k.blue.1024.css
            <li>templates/x6/x6skin.win2k.blue.1400.css
        </ul>
        
        <p>Finally, the program writes a serialized array
           into apppub/skins.serialized.txt, which
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
    
    
    function mainProcess() {
        $errors = array();
        
        # Make x6main scrollable
        jqDocReady("$('.x6body').css('overflow-y','scroll')");
        
        # Define a base directory
        $dir = fsDirTop().'templates/x6/';
        
        # Load the base program and display:
        $baseYaml=loadYaml($dir.'x6base.yaml');
        
        # Now create an empty array, and call out
        # to two different directories to get
        # skins
        $skins = array();
        $dir1 = $dir;
        $dir2 = fsDirTop().'application/';
        $this->scanForSkins($skins,$dir1);
        $this->scanForSkins($skins,$dir2);
        
        # Report to the user
        echo "<h2>Processing Report</h2>";
        foreach($skins as $skin=>$info){
            echo "Found skin: $skin<br/>";
        }
        
        # Now we have to make sure this is a base skin
        # file for each skin we found, oth
        foreach($skins as $skin=>$info){
            if(file_exists($dir1."x6skin.$skin.skin.yaml")) {
                $skinYaml = loadYaml($dir1."x6skin.$skin.skin.yaml");
            }
            else {
                if(file_exists($dir2."x6skin.$skin.skin.yaml")) {
                    $skinYaml = loadYaml($dir2."x6skin.$skin.skin.yaml");
                }
                else {
                    echo "ERROR: no file x6skin.$skin.skin.yaml in either
                      <br/>$dir1 or
                      <br/>$dir2
                      <br/>MUST DISCARD THIS SKIN<br/><br/>";
                    unset($skins[$skin]);
                }
            }
        }
        
        # Here is the core loop.  We are going to scan each
        # skin.  For each skin we will scan each color file,
        # and then within that loop scan each size combination.
        # So a skin that has 2 colors and 3 sizes will end 
        # up with 6 separate skin files.
        #
        # Along the way we are building up a list of skins
        # that we will output as a serialized PHP array.
        # The x6 template will read this to make an HTML
        # SELECT so the user can pick a skin.
        echo "<h3>Writing Skin Files Now</h3>";
        $skinFiles = array();
        foreach($skins as $skin=>$skininfo) {
            foreach($skininfo['colors'] as $color=>$colorfile) {
                foreach($skininfo['sizes'] as $size=>$sizefile) {
                    # The mainCore program actually builds
                    # the script file and writes it to the 
                    # apppub directory.
                    echo "<p>Processing $skin $color $size</p>";
                    $cYaml = loadYaml($colorfile);
                    $sYaml = loadYaml($sizefile);
                    $name = $this->mainCore(
                        $skin,$skinYaml
                       ,$color,$cYaml
                       ,$size,$sYaml
                       ,$baseYaml
                    );
                    if($name) {
                        $skinFiles[$name] = "$skin.$color.$size";
                    }
                }
            }
        }
        
        # Finally, write the list of skins out to apppub
        file_put_contents(
            fsDirTop().'generated/x6skins.ser.txt'
            ,serialize($skinFiles)
        );
    }
    
    # Scan a directory for skin files and add them
    # to the array.  Look for files 
    # that are named x6skin.{name}.{color}.color.yaml and
    # x6skin.{name}.{size}.size.yaml and create entries
    # in the $skins array that list each color and size
    # combination for the skin.
    function scanForSkins(&$skins,$dir) {
        $files = scandir($dir);
        
        foreach($files as $file) {
            # These lines filter out entries that are not skins
            $apieces = explode('.',$file);
            if(count($apieces)!=5       ) continue;
            if($apieces[0]    !='x6skin') continue;
         
            # Very simple slotting.  We want to record the
            # complete directory and file path so later
            # code does not have to remember any directories.
            if(substr($file,-11)=='.color.yaml') {
                $skins[$apieces[1]]['colors'][$apieces[2]] = $dir.$file;   
            }
            if(substr($file,-10)=='.size.yaml' ) {
                $skins[$apieces[1]]['sizes'][$apieces[2]] = $dir.$file;   
            }
        }
    }
        
        
    function mainCore($skin,$skinYaml,$color,$aColor,$size,$aSize,$baseYaml) {
        # Make the name as users will see it when picking the 
        $name   = $skinYaml['name'].' - '.$aColor['name'].' - '.$aSize['name']; 
        
        # We will need these much later on.  We are going to
        # build a list of selectors out of the 'rules'
        # sections of the skin files.  This is the master list
        # of selectors we will end up writing.  We have to do
        # this now because when we "roll up" the selectors,
        # the next step below, we won't have them anymore.
        # 
        $selectors = array_keys($skinYaml['defines']);
        
        # The first bit of magic is to scan the array and look
        # for elements that are themselves arrays.  We will replace
        # those with new elements by combining the names, so that:
        # [h1]=>array(
        #    [prop1] = value
        #    [prop2] = value
        # )
        # 
        # becomes:
        #
        # [h1prop1] => value
        # [h1prop2] => value
        #   
        $this->rollUpArray($aColor  ,'defines');
        $this->rollUpArray($aSize   ,'defines');
        $this->rollUpArray($skinYaml,'defines');

        # Now combine into one master array.  Each file contains
        # two sub-arrays: options and required.  There should be
        # no elements in common in them.  The division into
        # 'options' and 'required' makes it easier to code the
        # files and keep required elements separated from anything
        # else the programmer makes up to make his life easier.
        $aMaster = array();
        $aMaster = array_merge(
            $skinYaml['defines']
           ,$aSize['defines']
           ,$aColor['defines']
        );
        
        # We cannot specify colors as '#f0f0f0' in YAML because
        # the "#" sign gets treated as a comment.  So instead we
        # specify them in the YAML as @f0f0f0.  Now we do a simple
        # string substitution to turn those @ signs into # signs.
        #
        # We also take this opportunity to trim the values.  The
        # programmer might have used a lot of spacing to make the
        # file readable, and those spaces can end up padding the
        # values.
        foreach($aMaster as $key=>$value) {
            $aMaster[$key] = trim(str_replace('@','#',$value));   
        }
        
        # Now comes the 2nd bit of magic.  We will scan the array
        # over and over, looking for values that begin with an
        # asterisk "*".  An asterisk means the value is actually
        # a pointer to another value.  So we look at that source value.
        # If the source value itself is a pointer, we skip it.
        # If the source value is not a pointer, we take its value and
        # and the becomes the literal value of the original item.
        #
        # If a loop produces no changes, we stop.  If at that
        # point we still have pointers, we have an error, we could
        # not resolve all pointers.  This happens if there is
        # a circular reference.
        while(true) {
            $timeout   = 15;
            $xPointers = 0;
            $xChanged  = 0;
            $aPointers = array();
            foreach($aMaster as $key=>$value) {
                # don't waste time if no asterisks
                if(strpos($value,'*')===false) continue;
                
                # A value may itself be multiple values, like
                # "1px solid black".  So we split on spaces
                # and handle the individuals on their own
                $avals = explode(' ',$value);
                foreach($avals as $idx=>$aval) {
                    if(substr($aval,0,1)=='*') {
                        $xPointers++;
                        $pointer = substr($aval,1);
                        if(! isset($aMaster[$pointer])) {
                            echo "ERROR: Pointer to undefined variable: "
                                .$pointer;
                            return false;
                        }
                        $source = $aMaster[$pointer];
                        if(strpos($source,'*')===false) {
                            $xChanged++;
                            $avals[$idx]=$source;
                        }
                        else {
                            $aPointers[] = $aval;
                        }
                    }
                }
                $aMaster[$key] = implode(' ',$avals);
            }
            
            # no pointers found, we're done, break
            if($xPointers==0) break;
            
            # If there were pointers, and no substitution, this is
            # an error.  
            if($xChanged==0) {
                echo "FATAL ERROR: Circular pointer references";
                hprint_r($aPointers);
                break;
            }
            
            # As a final failsafe against infinite loops,
            # stop after 100, no matter what has happened
            $timeout--;
            if($timeout==0) break;
        }
        
        # We are ready to think about output, so here
        # is the array that will actually hold the selectors and rules.
        $aCSS = array();
        
        # The array has been converted completely to literals.
        # Now it is time to make the first pass of the actual
        # css output.  This pass looks at $baseYaml['elements']
        # and for each one it takes all defined values out
        # of the big array we've been building.
        $lookfor = array(
             'bort'=>'border-top'
            ,'borb'=>'border-bottom'
            ,'borl'=>'border-left'
            ,'borr'=>'border-right'
            ,'mart'=>'margin-top'
            ,'marb'=>'margin-bottom'
            ,'marl'=>'margin-left'
            ,'marr'=>'margin-right'
            ,'padt'=>'padding-top'
            ,'padb'=>'padding-bottom'
            ,'padl'=>'padding-left'
            ,'padr'=>'padding-right'
            ,'ff'  =>'font-family'
            ,'color' =>'color'
            ,'bgc'   =>'background-color'
            ,'fs'    =>'font-size'
            ,'fw'    =>'font-weight'
            ,'lh'    =>'line-height'
            ,'color' =>'color'
            ,'height'=>'height'
            ,'width' =>'width'
            ,'position'=>'position'
            ,'post'  =>'top'
            ,'posb'  =>'bottom'
            ,'posl'  =>'left'
            ,'posr'  =>'right'
            ,'vertical-align'=>'valign'
        ); 
        
        foreach($selectors as $selector) {
            foreach($lookfor as $src=>$rule) {               
                $source = $selector.$src;
                if(isset($aMaster[$source])) {
                    $aCSS[$selector][$rule] = $aMaster[$source];
                }
            }
        }
        
        # The second step is to pass through the
        # overrides in baseYaml and add them into
        # the selector rules.
        foreach($baseYaml['overrides'] as $selector=>$rules) {
            if(!is_array($rules)) {
                echo "<br/>ERROR: $selector has bad rules:";
                echo "<br/>This can happen if you use TAB instead of 
                    spaces to indent.";
                hprint_r($rules);
                continue;
            }
            foreach($rules as $rule=>$value) {
                # If the override is a pointer, fetch
                # the literal value
                if(substr($value,0,1)!='*') {
                    $aCSS[$selector][$rule] = $value;
                }
                else {
                    $source = substr($value,1);
                    if(isset($aMaster[$source])) {
                        $aCSS[$selector][$rule] = $aMaster[$source];
                    }
                    else {
                        echo "ERROR: overrides in base.yaml point to 
                        non-existent value: $source.";
                        hprint_r($rules);
                        return false;
                    }
                }
                
            }
        }
        
        # At long last, after all of that work, we are ready
        # to write the file.  The last bit of processing we
        # do as we go along here is to remove empty or null
        # values to reduce clutter.
        ob_start();
        ?>
/* ==================================================== *\
 * Andromeda Generated CSS file                         
 * Generated: <?=date('r',time())."\n"?>
 * Skin, color, size: <?="$skin, $color, $size\n"?>
\* ==================================================== */
        <?php
        echo "\n";
        foreach($aCSS as $selector=>$rules) {
            echo "$selector {\n";
            foreach($rules as $rule=>$value) {
                if(trim($value)=='' || is_null($value)) continue;
                echo "    ".str_pad($rule.':',25,' ',STR_PAD_RIGHT)."$value;\n";
            }
            echo "}\n\n";
        }
        $filename = fsDirTop()."clib/x6skin.$skin.$color.$size.css";
        file_put_contents($filename,ob_get_clean());
        
        # Another job, write out the CSS as a serialized
        # associative array so that plugins and other code
        # knows how big things are.  Also add in all of the
        # options to the array so we know the original
        # constants by their nicknames.
        #
        $phpStuff = array('defines'=>$aMaster,'css'=>$aCSS);
        $filename = fsDirTop()."generated/x6skin.$skin.$color.$size.ser.txt";
        file_put_contents($filename,serialize($phpStuff));
        
        # Return the name
        return $name;
    }
    
    function rollupArray(&$array,$mainkey) {
        foreach($array[$mainkey] as $key=>$value) {
            if(is_numeric($key)) {
                echo "<b>Error, numeric index.  This
                   means you forgot a colon, something like 'h1' instead
                   of 'h1:'.  Object:";
                hprint_r($value);
                exit;
            }
            if(is_array($value)) {
                foreach($value as $subkey=>$actualvalue) {
                if(is_numeric($subkey)) {
                    echo "<b>Error, numeric index.  This
                       means you forgot a colon, something like 'h1' instead
                       of 'h1:'.  Object:";
                    hprint_r($actualvalue);
                    exit;
                }
                    $array[$mainkey][$key.$subkey]=$actualvalue;
                }
                unset($array[$mainkey][$key]);
            }
        }
    }
}
?>
