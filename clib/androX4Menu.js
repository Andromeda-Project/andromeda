function x4Menu(self) {
    
    // Capture a public variable generated in PHP
    self.grid = $a.data.grid;
        
    // Hijack the ctrl key for the entire page.  The idea here is
    // to prevent CTRL-N or other nasties from confusing the user
    $(this).find("a").keydown(function(event) {
        // If ctrl, look for item with that id
        var letter = $a.charLetter(event.which);
        if(event.ctrlKey) {
            // Copy-n-paste operations are allowed
            if(letter=='x') return true;
            if(letter=='v') return true;
            if(letter=='c') return true;
            return false;
        }
    });
    
    self.activate = function() {
        // Turn on the focus tracking, then fade in and set focus
        $(this).fadeIn(x4.fadeSpeed,function() {
                x4.debug("x4Menu activate, id: x4Menu");
                window.x4Menu=this;
                if(typeof(this.lastFocusId)=='undefined') this.lastFocusId='';
                if(this.lastFocusId!='') {
                    $('#'+this.lastFocusId).focus();
                }
                else {
                    console.log(this);
                    $(this).find("td a:first").focus();
                }
        });
    }
    
    self.x4KeyUp = function(e,col,row) {
        var keyLabel = $a.label(e);
        x4.debug("in x4KeyUp on menu object for " + keyLabel);
        x4.debug(e);
        
        if(keyLabel=='Enter') {
            window.location = e.target.href;
            return false;
        }
        
        
        if(keyLabel == 'Tab' || keyLabel == 'ShiftTab') {
            return false;
        }
        var nextId = false;
        if(keyLabel == 'UpArrow') {
            if(row > 0) nextId = this.grid[col][row-1];
        }
        if(keyLabel == 'DownArrow') {
            var maxRow = this.grid[col].length - 1;
            if(row < maxRow) nextId = this.grid[col][row+1];
        }
        if(keyLabel == 'LeftArrow') {
            if(col > 0) {
                col--;
                if( row > this.grid[col].length - 1) 
                    row = this.grid[col].length - 1;
                nextId = this.grid[col][row];
            }
        }
        if(keyLabel == 'RightArrow') {
            if(col < this.grid.length - 1) {
                col++;
                if( row > this.grid[col].length - 1) 
                    row = this.grid[col].length - 1;
                nextId = this.grid[col][row];
            }
        }
        /* This code expects numbers across top 
        if(e.keyCode >= 49 && e.keyCode <= 57) {
            if ( (e.keyCode - 48) <= this.grid.length ) { 
                col = e.keyCode - 49;
                if( row > this.grid[col].length - 1) 
                    row = this.grid[col].length - 1;
                nextId = this.grid[col][row];
            }
        }
        if(e.keyCode >= 65 && e.keyCode <= 90) {
            if ( (e.keyCode - 65) <= (this.grid[col].length-1) ) {
                nextId = this.grid[col][e.keyCode - 65];
            }
        }
        */
        if(e.keyCode >= 65 && e.keyCode <= 90) {
            if ( (e.keyCode - 64) <= this.grid.length ) { 
                col = e.keyCode - 65;
                if( row > this.grid[col].length - 1) 
                    row = this.grid[col].length - 1;
                nextId = this.grid[col][row];
            }
        }
        if(e.keyCode >= 48 && e.keyCode <= 57) {
            if ( (e.keyCode - 48) <= (this.grid[col].length-1) ) {
                nextId = this.grid[col][e.keyCode - 48];
            }
        }

        if(nextId) {
            $('#'+nextId).focus();
            return false;
        }
    }
}
