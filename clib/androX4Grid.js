// Build a grid
function x4Grid(self) {
    // Function to set grid rows
    self.setCounts = function() {
        this.xRowCurrent = false;
        this.xRowCount   = false;
        if( $(this).find("tbody").length == 0) return;
        
        // If we have something, keep going
        this.xTbody = $(this).find('tbody')[0];
        this.xRowCount = this.xTbody.rows.length;
        this.selectRowByNumber(1);

        // Mouse support 
        $(this.xTbody).find("tr").mouseover(function() {
                if(!$(this).hasClass('highlight')) {
                        $(this).addClass('mouseover');
                }
        }).mouseout(function() {
            $(this).removeClass('mouseover');
        }).click(function() {
            var id = this.id.slice(4);
            this.parentNode.parentNode.selectRowByNumber(id);
            $(this).removeClass('mouseover');
        });
    }
    
    // Basic up and down behaviors
    self.nextRow = function() {
        if(this.xRowCurrent == false) {
            this.selectRowByNumber(1);
        }
        else {
            var rowNow = Number(this.xRowCurrent);
            if(rowNow < Number(this.xRowCount)) {
                this.selectRowByNumber(Number(rowNow) + 1);
            }
        }
    }
    self.prevRow = function() {
        if(this.xRowCurrent == false) {
            this.selectRowByNumber(1);
        }
        else {
            var rowNow = Number(this.xRowCurrent);
            if(rowNow > 1) {
                this.selectRowByNumber(rowNow - 1);
            }
        }
    }
    self.pageDown = function() {
        if(this.xRowCurrent == false) {
            this.selectRowByNumber(1);
        }
        else {
            var rowNow = Number(this.xRowCurrent);
            if(rowNow <= Number(this.xRowCount - 20)) {
                this.selectRowByNumber(Number(rowNow) + 20);
            }
            else {
                this.selectRowByNumber(this.xRowCount);
            }
        }
    }
    self.pageUp = function() {
        if(this.xRowCurrent == false) {
            this.selectRowByNumber(1);
        }
        else {
            var rowNow = Number(this.xRowCurrent);
            if(rowNow > 20) {
                this.selectRowByNumber(rowNow - 20);
            }
            else {
                this.selectRowByNumber(1);
            }
        }
    }
    
    // Select a row by number
    self.selectRowByNumber = function(rowNum) {
        // check if number exists
        var idNew = '#row_'+rowNum;
        var jq    = $(this.xTbody).find(idNew);
        if( jq.length == 0) return;

        // Remove highlight for old one        
        if(this.xRowCurrent) {
            var id = '#row_'+this.xRowCurrent;
            $(this.xTbody).find(id).removeClass('highlight');
        }
        
        // Set current row number
        this.xRowCurrent = rowNum;
        $(this.xTbody).find(idNew).addClass('highlight');
    }

    /* ---------------------------------------
     * INIT CODE
     * ---------------------------------------
     */
    // Create the basic tracking variables
    self.xRowCurrent = false;
    self.xRowCount   = false;
    
    // Establish counts
    self.setCounts();
}
