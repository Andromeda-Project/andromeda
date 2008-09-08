<?php
class x4apppub extends androX4 {
    # =========================================================
    #
    # SECTION 1: Main Layout
    #
    # =========================================================
    function mainLayout($c) {
        # Get the basic title going
        $c->h('h1','Images and Files');
        $c->h('p','On this page you can upload images and other files to
            your site.  <b>Any file you upload is accessible to the general
            public, do not put sensitive files here.</b>');
        $c->br();

        # if there was an upload, process and report
        $this->mainUpload($c);
        
        # Make the left and right divs
        $left = $c->h('div');
        $left->hp['id'] = 'div-left';
        $left->hp['style'] = 'float: left';
        $right = $c->h('div');
        $right->hp['id'] = 'div-left';
        $right->hp['style'] = 'float: right';
        
        # Now make the input to load a file
        $divl = $left->h('div');
        $divr->hp['style'] = 'text-align: left';
        $form = $divl->h('form');
        $form->hp['method'] = 'POST';
        $form->hp['action'] = 'index.php?x4Page=apppub';
        $form->hp['enctype'] = 'multipart/form-data';
        $form->hidden('MAX_FILE_SIZE',1000000);
        $form->h('h3','Upload a New File');
        $input=$form->h('input');
        $input->hp['type'] = 'file';
        $input->hp['name'] = 'apppub';
        $form->br(2);
        $input=$form->h('input');
        $input->hp['type'] = 'submit';
        $input->hp['value']= 'Upload Now';
        
        # Now list existing files
        $dir = fsDirTop().'apppub/';
        $files = scandir($dir);
        asort($files);
        foreach($files as $file) {
            if($file=='.')    continue;
            if($file=='..')   continue;
            if(is_dir($dir.$file)) continue;
            
            $div = $right->h('div');
            $fileok = str_replace(' ','',$file);
            $fileok = str_replace(".",'',$file);
            $div->hp['id'] = 'div-'.$fileok;
            $a = $div->h('a');
            $a->hp['target'] = '_blank';
            $a->hp['href'] = 'apppub/'.$file;
            $a->setHtml($file);
            $div->nbsp(5);
            $a = $div->h('a-void','Delete');
            $a->hp['onclick'] = "killPub('$file','div-$fileok')";
            $div->br();
            $exts = array('gif','jpg','png','tiff');
            $ext  = strtolower(scFileExt($file));
            #if(in_array($ext,$exts)) {
            #    $img = $div->h('img');
            #    $img->hp['src'] = 'apppub/'.$file;
            #}
            
            $right->br(2);
        }
    }
    
    function mainUpload(&$c) {
        # Make sure we have a file
        if(!isset($_FILES['apppub'])) return;
        
        # If user is not logged in or a member of filemaint,
        # quietly ignore the request
        if(!LoggedIn()) return;
        if(!ingroup('filemaint')) return;
        
        # If an error, report it:
        $upload = $_FILES['apppub'];
        if($upload['error']<>0) {
            $c->h('hr');
            $c->h('h3','File Upload Error');
            $c->h('p','The upload error number was '.$upload['error']);
            $c->h('hr');
        }
        
        # OK, move the file
        $dest = fsDirTop()."apppub/".$upload['name'];
        move_uploaded_file($upload['tmp_name'],$dest);
        $c->h('hr');
        $c->h('h3','File Upload Successful');
        $c->h('p','Last uploaded file was "'.$upload['name'].'", size: '
            .number_format($upload['size'],0).' bytes, type: '
            .$upload['type']
        );
        $c->h('hr');
    }
    
    # =========================================================
    #
    # SECTION 2: Script
    #
    # =========================================================
    function extraScript() {
        ?>
        <script>
        window.killPub = function($file,$div) {
            if(u.dialogs.confirm("Delete file "+$file+"?")) {
                // Run asynchronously, we don't care about result
                ua.json.init('x4Page','apppub');
                ua.json.addParm('x4Action','delfile');
                ua.json.addParm('file',$file);
                ua.json.executeAsync();
                
                // Kill the guy visually
                $('#'+$div).fadeOut('medium',function() {
                        $(this).remove();
                });
            }
        }
        </script>
        <?php
    }
    
    # =========================================================
    #
    # SECTION 3: Server-Side Code
    #
    # =========================================================
    function delfile() {
        # If user is not logged in and not in file maintenance,
        # quietly ignore
        if(!LoggedIn()) return;
        if(!inGroup('filemaint')) return;
        
        $filename = fsDirTop().'apppub/'.gp('file');
        unlink($filename);
    }
}
?>
