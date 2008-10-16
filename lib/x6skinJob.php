<?php
class x6skinJob extends x_table2 {
    
    function main() {
        ?>
        <h1>EXPERIMENTAL CSS Generator</h1>
        
        <p>This is an experimental program being used by Ken 
           and Jack to create a skinnable css system.  It is
           <i>not</i> suitable for production use and make
           change significantly.
        </p>
        
        <p>This program must be called with specific parameters:</p>
        <ul><li>skin=skin.yaml
            <li>styles=styles.yaml
        </ul>
        <p>An example might be: </p>
        <pre>
        ?gp_page=x6skinJob&amp;skin=skin.yaml&amp;styles=styles.yaml
        </pre>
        
        <p>The input files are expected to be in the application
           directory.
        </p>
        
        <p>The resulting file is written into clib as "x6-{user}.css".
           It can be used by specifically including that file in
           experimental programs that make use of the styles it
           defines.
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
        
        # Load the skin and check for errors
        $skinfile = fsDirTop().'application/'.gp('skin');
        $skinYaml=loadYaml($skinfile);

        # First read through the skin and resolve
        # all references into values
        $skin = array();
        if(!isset($skinYaml['skin'])) {
            $errors[] = "FATAL: the skin file does not contain 'skin:'
               as its top-level element.  Please examine file
               lib/skeleton.skin.yaml for proper structure.";
            return $errors;
        }
        if(!is_array($skinYaml['skin'])) {
            $errors[] = "FATAL: the skin file is not properly 
               formatted.  Please examine file
               lib/skeleton.skin.yaml for proper structure.";
            return $errors;
        }
        foreach($skinYaml['skin'] as $property=>$value) {
            $value = trim($value);
            if(substr($value,0,1)=='*') {
                $key = substr($value,1);
                if(isset($skin[$key])) {
                    $value = $skin[$key];
                }
                else {
                    $errors[] = "Skin file refers to previous value
                       *$key but that is not defined yet.  Referenced
                       values must be defined before they are referenced.";
                }
            }
            $skin[$property] = $value;
        }
        
        
        # Load the styles and process
        $styleFile = fsDirTop().'application/'.gp('styles');
        $styleYaml=loadYaml($styleFile);
        $output= '';
        foreach($styleYaml as $selector=>$rules) {
            if($selector=='skin') continue;

            $output.=trim($selector)." {\n";
            foreach($rules as $property=>$value) {
                $value = trim($value);
                if(substr($value,0,1)=='*') {
                    $key = substr($value,1);
                    if(isset($skin[$key])) {
                        $value = $skin[$key];
                    }
                    else {
                        $errors[] = "Style selector $selector, property 
                            $property refers to value
                           *$key but that is not defined in the skin
                           file.  Referenced
                           values must be defined in the skin file.";
                    }
                }
                if(substr($value,0,1)=='x' && strlen($value)==7) {
                    $value = '#'.substr($value,1);
                }
                
                $output.="    $property: ".trim($value).";\n";
            }
            
            $output.="}\n\n";
        }
        hprint_r($output);
        file_put_contents(fsDirTop().'clib/x6-'.SessionGet('UID').'.css',$output);
        return $errors;
    }
}
?>
