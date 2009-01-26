<?php
# ==============================================================
#
#  This is an example x6 page file.  
#
#  If you create this link:    ?x6page=skeleton
#  ...then this file's x6main() will be run
#
#  Notice the naming conventions:
#   -> the class is 'x6' + the page name
#   -> the file  is 'x6' + the page name + '.php'
#   -> the class always extends 'androX6', only the 'X' is uppercase
#   -> the file is put into your 'application' directory
#
#  If this is a PUBLIC page, and people should be able to
#  see it without logging in, be sure to list it in
#  your application/applib.php file.  That file was 
#  created with this application and contains examples
#  and instructions (it's really easy).
#
#  There are many things you can do with this file, 
#  find out more at:
# 
#      http://www.andromeda-project.org/pages/cms/web+programming.html
#
# ==============================================================
class x6example extends androX6 {
    # ----------------------------------------------------------
    #
    # The x6main function is the default action for the
    # file.  Here is where you generate html and branch
    # off to processing routines.
    #
    # ----------------------------------------------------------
    function x6main() {
        echo "My Example Page!";
        ?>
        <div id="example">This div has an id.</div>
        <?php
    }
    
    # ----------------------------------------------------------
    #
    # The x6script function allows you to put in script that
    # will be sent out with the page.  This script will be
    # executed after the page is loaded and after all other
    # javascript libraries are loaded, so you can expect your
    # entire context to be present.
    #
    # NOTE: global variables and objects must be prepended
    #       with the string 'window.' because this code will
    #       not execute in global scope.
    #
    # ----------------------------------------------------------
    function x6script() {
        # The script tag is not necessary, but your editor
        # should provide syntax coloring, so it is usually
        # easier to put in the script tag.
        ?>
        <script>
        // Create a global variable
        window.exampleVar = 5;
        
        // Create a global function
        window.myFunction = function(input) {
            alert("The function received: "+input);
        }
        
        // Use jQuery!  It's built-in
        $('a').css('color','green');
        
        // Add functions to DOM objects
        $('#example').click(
            function() {
                alert("you clicked the div!");
            }
        );
        </script>
        <?php
    }
}
?>
