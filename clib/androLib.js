/* ================================================================== *\
   (C) Copyright 2005 by Secure Data Software, Inc.
   This file is part of Andromeda
   
   Andromeda is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   Andromeda is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with Andromeda; if not, write to the Free Software
   Foundation, Inc., 51 Franklin St, Fifth Floor,
   Boston, MA  02110-1301  USA 
   or visit http://www.gnu.org/licenses/gpl.html
\* ================================================================== */

var fadeSpeed = 'medium';

/*
 * The x4Menu object accepts control when an x4Menu is passed back
 * from the server.  It will then pass control up to other page
 * type controllers 
 *
 */
var x4Menu = {
    divId: '',
    lastFocusId: '',
    
    init: function() {
        // Tell the layers manager that we are the only layer, 
        // and ask it for our name
        this.divId = x4Layers.init(this);

        // Capture a public variable
        this.grid = grid;

        $('#'+this.divId).fadeIn(fadeSpeed,function() {
            $("#"+x4Menu.divId+" td a:first").focus();
        });
    },
    
    // Called back by x4Layers when a higher layer has exited
    // and this layer is getting focus back
    restore: function() {
        $("#"+this.lastFocusId).focus();
    },
    
    open: function(page) {
        x4.json.init('x4Page',page);
        if(x4.json.execute()) {
            var divId = x4Layers.push(x4Browse);
            // open a layer, process it, initialize new controller
            x4.json.process(divId);
            x4Browse.init(divId);
        }
    },
    
    click: function(e,col,row) {
        var keyLabel = x4.keyLabel(e);
        if(keyLabel == 'Tab' || keyLabel == 'ShiftTab') {
            e.stopPropagation();
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

        if(nextId) {
            $('#'+nextId).focus();
            e.stopPropagation();
            return false;
        }
    }
}

/*
 * The x4Browse object accepts control for a generic
 * database table search.
 *
 */
var x4Browse = {
    divId: '',          // ID of the div that holds our display
    sortCol: '',        // search order column
    sortAD: '',         // search ascending/descending
    rowId: false,       // current rowId
    rowCount: 0,        // count of rows
    lastFocusId: '',    // ID of last input that had focus 

    /*
     * The restore method puts focus back on 
     * the last object that had focus
     *
     */
    restore: function() {
        $('#'+this.lastFocusId).focus();
    },
    
    /**
      * x4Browse.init()
      *
      * Initializes a browse after being received 
      * from the server
      *
      */
    init: function(divId) {
        this.divId = divId;
        
        // initialize the tab loop and the order by
        $('#'+this.divId).fadeIn(fadeSpeed, function() {
            x4Forms.tabLoopInit(x4Browse.divId,x4Browse);
        });
        this.setOrderBy();
        
        // Copy data from the last request to here
        for(var x in x4.json.data) {
            if(x=='dd') continue;
            this[x] = x4.json.data[x];
        }
        x4dd.dd = x4.json.data.dd;
        
        // If this is a returnall, force a fetch now
        if(this.details.returnAll=='Y') {
            this.fetch(true);
        }
        
        /* - - - - - - - - - - - - - - - - - - */
        /* KEYBOARD HANDLER, inputs, keyup     */
        /* - - - - - - - - - - - - - - - - - - */
        x4Forms.ctrlCapture('#'+divId);

        $(".x4browseinput").keyup(function(event) {
            // Key label comes first
            var keyLabel = x4.keyLabel(event);
            
            if(keyLabel == 'Esc') {
                if(x4Browse.rowCount > 0 && x4Browse.details.returnAll!='Y') {
                    x4Browse.clear();
                }
                else {
                    x4Layers.pop();
                }
                event.stopPropagation();
                return;
            }
            
            // Initialize this flag now
            var doFetch = false;
            if(keyLabel == 'ShiftUpArrow') {
                x4Browse.setOrderBy(this.x_column_id,'ASC');
                doFetch = true;
            }
            if(keyLabel == 'ShiftDownArrow') {
                x4Browse.setOrderBy(this.x_column_id,'DESC');
                doFetch = true;
            }
            
            if(x4Browse.rowId){
                if(keyLabel == 'UpArrow') {
                    x4Browse.moveUp();
                    event.stopPropagation();
                    return;
                }
                if(keyLabel == 'DownArrow') {
                    x4Browse.moveDown();
                    event.stopPropagation();
                    return;
                }
                if(keyLabel == 'PageUp') {
                    x4Browse.moveTop();
                    event.stopPropagation();
                    return;
                }
                if(keyLabel == 'PageDown') {
                    x4Browse.moveBottom();
                    event.stopPropagation();
                    return;
                }
                if(keyLabel == 'Enter') {
                    $('#'+x4Browse.rowId).click(); // ENTER is click
                    event.stopPropagation();
                    return;
                }
            }
            
            // All other cases, possibly execute a search, but
            // not if they hit shift
            if(x4Browse.details.returnAll != 'Y') {
                x4Browse.fetch(doFetch);
            }
        });
    },
    
    /**
      * Move up a row or down a row, and return the skey
      * that was passed
      */
    moveUp: function() {
        var x = x4.byId(this.rowId);
        if(x.previousSibling!=null) {
            $('#'+x.previousSibling.id).mouseover();
        }
        return this.skey();
    },
    moveDown: function() {
        var x = x4.byId(this.rowId);
        if(x.nextSibling!=null) {
            $('#'+x.nextSibling.id).mouseover();
        }
        return this.skey();
    },
    moveTop: function() {
        if(this.rowId != x4.byId('x4browsetbody').firstChild.id) {
            $('#x4browsetbody tr:first').mouseover();
        }
        return this.skey();
    },
    moveBottom: function() {
        if(this.rowId != x4.byId('x4browsetbody').lastChild.id) {
            $('#x4browsetbody tr:last').mouseover();
        }
        return this.skey();
    },
    
    skey: function() {
        return this.rowId.slice(6);        
    },
    
    /**
      * Sets the sort order for searches.  Expects a
      * column name and literal "ASC" or "DESC".  If column
      * name is not passed it makes the first column
      * sortable
      *
      */
    setOrderBy: function(inputId,direction) {
        if(inputId==null) {
            inputId = $(".x4browseinput:first")[0].x_column_id;
            direction = 'ASC';
        }
        this.sortCol = inputId;
        this.sortAD = direction;
    },
    
    /**
      * Clear search results
      *
      */
    clear: function() {
        this.rowId = false;
        this.rowCount = 0;
        x4.byId("x4browsetbody").innerHTML = '';
        for(var idx in this.inputs) {
            x4.byId(this.inputs[idx]).value='';
        }
    },
    
    
    fetch: function(doFetch) {
        if(doFetch==null) doFetch=false;
        
        x4.json.init();
        for(var idx in x4Browse.inputs) {
            inp = x4.byId(x4Browse.inputs[idx]);
            var lastValue = x4.aProp(inp,'x_value','');
            if(inp.value!=lastValue) {
                doFetch = true;  // one of them changed, send the request
                inp.x_value = inp.value;
            }
            x4.json.addParm('x4w_'+inp.x_column_id,inp.value);
        }        
        
        if(doFetch) {
            x4.json.addParm('x4Page' ,x4Browse.details.table_id);
            x4.json.addParm('sortCol',x4Browse.sortCol);
            x4.json.addParm('sortAD' ,x4Browse.sortAD);
            x4.json.addParm('x4Action','browseFetch');
            x4.json.addParm('x4Limit',300);
            if( x4.json.execute(true)) {
                // Tell x4Browse how many rows it has
                x4Browse.rowCount = x4.byId("x4browsetbody").rows.length;

                $(".x4brrow").click(function() {
                    // Create the new layer
                    $('#'+x4Browse.lastFocusId).blur();
                    var divId = x4Layers.push(x4Detail);
                    
                    x4Detail.init(
                        divId
                        ,x4Browse.details.table_id
                        ,x4Browse.skey()
                    );
                });
                
                $(".x4brrow").mouseover( function() {
                    $("#"+x4Browse.rowId).removeClass('highlight');
                    $(this).addClass('highlight');
                    x4Browse.rowId = this.id;
                });
                
                $(".x4brrow:first").mouseover();
            }
        }
    },
    
    new: function() {
        var divId = x4Layers.push(x4Detail);
        x4Detail.init(divId,this.details.table_id,0);
    },
    
    delete: function() {
        if(!this.rowId) {
            x4.dialogs.alert('I cannot delete because there is nothing selected.');
        }
        else {
            x4.json.init('x4Page',this.details.table_id);
            x4.json.addParm('x4Action','delete');
            x4.json.addParm('skey',this.skey());
            if(x4.json.execute()) {
                x4.dialogs.alert('The selected row was deleted.');
                this.fetch();
            }
        }
        this.restore();
    },
    
    help: function() {
        var help = x4.aProp(this,'helpFlag',false);
        if(help) {
            $('#help').fadeOut(fadeSpeed);
            this.helpFlag = false;
        }
        else {
            $('#help').fadeIn(fadeSpeed);
            this.helpFlag = true;
        }
        this.restore();        
    }
    
}

/*
 * The default Detail context handler
 *
 */ 
var x4Detail = {
    data: { },
    table_id: '',
    divId: '',
    skey: 0,
    lastFocusId: '',
    
    /*
     * Open the detail tab for display 
     *
     */
    init: function(divId,table_id,skey) {
        this.table_id = table_id;
        this.divId    = divId;
        
        // Invoke the JSON, put it onto the page
        x4.json.init('x4Page',table_id);
        x4.json.addParm('x4Action','detail');
        if(!x4.json.execute()) {
            x4.byId(divId).innerHTML=
                '<br/><br/><a href="javascript:x4Layers.pop()">Return</a>';
            $('#'+this.divId).fadeIn('fast');
            return;
        }
        x4.json.process(divId);

        // Copy data from the last request to here
        for(var x in x4.json.data) {
            this[x] = x4.json.data[x];
        }

        // Fetch row uses a magic number, if skey = 0 
        // we assume a new row
        this.fetchRow(skey);

        // Capture all ctrl keys        
        x4Forms.ctrlCapture('#'+divId);

        // Add the universal key handlers for this layer
        var jqId = '#'+divId;
        $(jqId).find(":input").keyup( function(event) {
            var keyLabel = x4.keyLabel(event);
            if(keyLabel=='') return;
            
            if(keyLabel=='Esc') {
                if(x4Detail.okToLeave()) {
                    x4Layers.pop();
                }
                event.stopPropagation();
                return;
            }
        }).keypress( function(event) {
            var keyLabel = x4.keyLabel(event);
            if(keyLabel=='') return;
            var labels ='PageUp PageDown CtrlPageDown CtrlPageUp';
            if(labels.indexOf(keyLabel)>-1) {
                if(x4Detail.okToLeave()) {
                    if(     keyLabel=='PageUp') 
                        var skey = x4Browse.moveUp();
                    else if(keyLabel=='PageDown')
                        var skey = x4Browse.moveDown();
                    else if(keyLabel=='CtrlPageUp')
                        var skey = x4Browse.moveTop();
                    else if(keyLabel=='CtrlPageDown')
                        var skey = x4Browse.moveBottom();
                    if(skey!=x4Detail.skey) {
                        x4Detail.fetchRow(skey);
                    }
                }
            }
        }).focus( function() {
            $(this).addClass('x4Focus'); 
        }).blur( function() {
            $(this).removeClass('x4Focus'); 
        })
        
        // Always activate the tab loop
        $('#'+this.divId).fadeIn(fadeSpeed, function() {
            x4Forms.tabLoopInit(x4Detail.divId,x4Detail);
        });
        
        
    },
    
    // Magic number alert: zero equals a new row, insert mode
    fetchRow: function(skey) {
        this.skey = skey;
        
        // If not a new row, go fetch it
        if(skey!=0) {
            x4.json.init();
            x4.json.addParm('x4Page',this.table_id);
            x4.json.addParm('x4Action','fetchRow');
            x4.json.addParm('x4w_skey',skey);
            x4.json.execute(true);
            var row = x4.json.data.row;
            for(var idx in this.inputs) {
                var id    = this.inputs[idx];
                var input = x4.byId(id);
                var column_id = x4.byId(id).x_column_id;
                var value = x4.aProp(row,column_id,'');
                if(value==null) value='';
                if(input.x_type_id=='dtime') {
                    if(value=='') {
                        input.value = '';
                    }
                    else {
                        value = value.slice(5,7)+'/'+value.slice(8,9)
                            +'/'+value.slice(0,4)
                            +' '+value.slice(11,19);
                        input.value = value;
                    }
                }
                else {
                    input.value   = x4.aProp(row,column_id,'');
                }
                input.x_value = input.value;
            }
        }
        
        // Get the title
        if(skey==0) {
            var title = 'New ' + x4dd.dd.singular;
        }
        else {
            var col1  = x4dd.firstPkColumn();
            var title = x4dd.dd.singular + ": "+ row[col1]; 
            if(x4dd.pkColumnCount()>1) {
                title += '...';
            }
        }
        $("#x4h1").html(title);

        // Set the read-only and the coloring, and defaults for new
        for(var idx in this.inputs) {
            var inp = x4.byId(this.inputs[idx]);
            var col = inp.x_column_id;
            var ro = skey==0 ? inp.x_ro_ins : inp.x_ro_upd;
            if(ro==' ') ro = 'N';
            if(ro==null) ro = 'N';
            if( ro=='Y') {
                inp.readOnly = true;
                $(inp).addClass('x4inputReadOnly');
                inp.tabIndex = 0;
            }
            else {
                inp.readOnly = false;
                $(inp).addClass('x4input');
            }
            
            // Now for coloring
            if(skey==0 && ro == 'N') {
                $(inp).addClass('x4Insert');
            }
            else {
                $(inp).removeClass('x4Insert');
            }
            
            if(skey==0) {
                var autoid = x4dd.dd.flat[col].automation_id;
                if(autoid=='DEFAULT') {
                    inp.value = x4dd.dd.flat[col].auto_formula;
                }
            }
        }
    },
    
    delete: function() {
        x4.json.init('x4Page',this.details.table_id);
        x4.json.addParm('x4Action','delete');
        x4.json.addParm('skey',this.skey);
        if(x4.json.execute()) {
            x4.dialogs.alert('The selected row was deleted.');
        }
        x4Layers.pop();
    },
    
    new: function() {
        if(this.okToLeave()) {
            this.fetchRow(0);
        }
    },

    tryToLeave: function() {
        if(this.okToLeave()) x4Layers.pop();
    },
    
    okToLeave: function() {
        var mustSave = false;
        x4.json.init();
        for(var idx in this.inputs) {
            var input = x4.byId(this.inputs[idx]);
            if (input.value != input.x_value) {
                mustSave=true;
                x4.json.addParm('x4v_'+input.x_column_id,input.value);
            }
        }
        
        if(!mustSave) return true;
        
        if (mustSave) {
            x4.json.addParm('x4v_skey',this.skey);
            x4.json.addParm('x4Page'  ,this.table_id);
            x4.json.addParm('x4Action','update');
            x4.json.execute();
            if(x4.json.jdata.error.length==0)
                return true;
            else 
                return false;
        }
        
        return true;
    }
}

/*
 * The x4dd contains the data dictionary of the current
 * table.  It is normally populated by the x4Browse
 * init routine, which pulls it from the json data and
 * puts it here.
 *            
 */
var x4dd = {
    dd: { },
    
    firstPkColumn: function() {
        var list = this.dd.pks.split(',');
        return list.pop();
    },
    pkColumnCount: function() {
        return this.dd.pks.split(',').length;
    }
}

/*
 * Add and remove layers as requested.  This is on the layer above
 * x4 itself.  It can call down to x4 and to plugins, but not
 * to higher levels.  The exception is when it is told to pass control
 * back to the
 *            
 */
var x4Layers = {
    layerCount: 1,
    layers: [ ],
    objects: [ ],

    /*
     * Take current object inside of #Form1 and make it 
     * the bottom stack entry
     * 
     */
    init: function(obj) {
        var id = $('#Form1 div:first')[0].id;
        this.layerCount = 1;
        this.layers = [ id ];
        this.objects= [ obj ];
        return id;
    },
    
    push: function(parent) {
        // Make current layer invisible.  I've been 
        // making these instantaneous because having
        // one layer disappearing while another appears
        // seems to really be slow on my pc
        var id = this.layers[this.layers.length-1];
        //$('#'+id).fadeOut(fadeSpeed);
        x4.byId(id).style.display='none';

        // Add a new div        
        this.layerCount++;
        var id = 'x4divLayer_'+this.layerCount;
        var div = document.createElement('div');
        div.className = 'x4Layer';
        div.id = id;
        x4.byId('Form1').appendChild(div);
        this.layers.push(id);
        this.objects.push(parent);
        return id;
    },
    
    pop: function() {
        // Prevent accidental repeated calls
        if(this.layerCount == 0) {
            return;
        }
        
        // Remove current layer.  I did this as a straight remove 
        // because when I tried a fade it seemed to drag on 
        // my laptop, probably won't work well.
        var obj = this.objects.pop();
        $('#'+obj.lastFocusId).blur();
        
        var id = this.layers.pop();
        $('#'+id).remove();
        
        // Tell the old controller it is back in charge
        var controller = this.objects[this.objects.length-1];
        var id = this.layers[this.layers.length-1];
        $('#'+id).fadeIn(fadeSpeed,function() {
            var controller = x4Layers.objects[x4Layers.objects.length-1];
            controller.restore();
        });
        //x4.byId(id).style.display = '';
    },    
}

/**
  * x4 Forms, routines that are generic to any form, like
  *    tab looping.
  *            
  */
var x4Forms = {
    tabLoopInit: function(divId,objParent) {
        var jqid = '#'+divId;
        
        // Assign first to shift back to the last
        $(jqid).find(":input:not([@readonly]):first")[0].tabPrev =
            '#' + $(jqid).find(":input:not([@readonly]):last")[0].id;
        $(jqid).find(":input:not([@readonly]):first").keypress( function(event) {
            var label = x4.keyLabel(event);
            if(label=='ShiftTab') {
                $(event.currentTarget.tabPrev).focus();
                event.preventDefault();
            }
        });

        // Assign last to shift to the first
        $(jqid).find(":input:not([@readonly]):last")[0].tabNext =
            '#' + $(jqid).find(":input:not([@readonly]):first")[0].id;
        $(jqid).find(":input:not([@readonly]):last").keypress( function(event) {
            var label = x4.keyLabel(event);
            // A tab key on the last element will loop around    
            if(label=='Tab') {
                $(event.currentTarget.tabNext).focus();
                event.preventDefault();
            }
        });
        
        // Tell all inputs who their controller is
        this.objParent = objParent
        $(jqid).find(":input").each(function() {
            this.x_parent = x4Forms.objParent;
        });
        this.objParent = false;
        $(jqid).find(":input").focus( function() {
            this.x_parent.lastFocusId = this.id;
        });
        
        // TAB LOOP: Put focus on first non-readonly element
        $(jqid +' :input:not([@readonly]):first').focus();      
    }, 
    
    arrowKeysNav: function(divId) {
        var jqid = "#"+divId;
        $(jqid).find(":input").keypress( function(event) {
            var label = x4.keyLabel(event);
            var max = x4Detail.inputs.length - 1;
            var idx = x4Detail.inputs.indexOf(this.id);
            if(label == 'DownArrow') {
                var idxnew = (idx==max) ? 0 : ++idx;
                $('#'+x4Detail.inputs[idxnew]).focus().select();
                return false;
            }
            if(label == 'UpArrow') {
                var idxnew = idx==0 ? max : --idx; 
                $('#'+x4Detail.inputs[idxnew]).focus().select();
                return false;
            }
            return true;
        });
    },

    ctrlCapture: function(jqid) {    
        $(jqid).keydown(function(event) {
            // If ctrl, look for item with that id
            var letter = x4.charLetter(event.which);
            if(event.ctrlKey) {
                var x = $("[@accesskey="+letter+"]");
                // count if there is a match
                if(x.length > 0) {
                    x.click();
                }
                event.stopPropagation();
                return false;
            }
        });
    }
}


/**
  * x4 Object, Contains generic functions and leaf functions.
  *    Functions here may call to JQuery only or each other
  *    only, but not to any other level.  This is the "bottom"
  *    service level for x4.
  *            
  */
var x4 = {
    /*
     * Dialogs.  Placeholders to use JQuery plugins
     *
     */
    dialogs: {
        alert: function(msg) {
            alert(msg);
        }
    }, 
    
    /**
      * Sub object for making calls to the server to retrieve
      * stuff in JSON format
      *
      */
    json: {
        callString: '',
        jdata: { },
        data: { },
        init: function(name,value) {
            this.callString = '';
            if(name!=null) {
                this.addParm(name,value);
            }
        },
        addParm: function(name,value) {
            if(this.callString!='') this.callString+="&";
            this.callString += name + '=' + encodeURIComponent(value);
        },

        /**
          * Make a synchronous call to the server, expecting
          * to receive a JSON array of stuff back.
          *
          */
        execute: function(autoProcess) {
            if(autoProcess==null) autoProcess=false;
            
            // Create an object
            var browser = navigator.appName;
            if(browser == "Microsoft Internet Explorer"){
                var http = new ActiveXObject("Microsoft.XMLHTTP");
            }else{
                var http = new XMLHttpRequest();
            }
            
            // Execute the call
            var entireGet = 'index.php?json=1&'+this.callString;
            http.open('GET' , entireGet, false);
            http.send(null);
            
            // Attempt to evaluate the JSON
            try {
                eval('this.jdata = '+http.responseText);
            }
            catch(e) { 
                alert('Could not process server response!');
                return false;
            }

            // Fatal errors are thrown up immediately.
            if(this.jdata.fatal!='') {
                x4.dialogs.alert(this.jdata.fatal);
                return false;
            }
            
            // If there were server errors, report those
            //if(this.jdata.errors.length>1) {
            //    x4.dialogs.alert(this.jdata.errors.combine("\n\n"));
            //}
            
            if(autoProcess) {
                this.process();
            }
            
            return true;
        },
        
        process: function(divMain) {
            for(var x in this.jdata.html) {
                if(x=='*MAIN*') {
                    $('#'+divMain).html(this.jdata.html[x]);
                }
                else {
                    $('#'+x).html(this.jdata.html[x]);
                }
            }
            
            // Execute any script that was provided
            for(var x in this.jdata.script) {
                eval(this.jdata.script[x]); 
            }
            
            return true;
        }
    },

    byId: function(id) {
        return document.getElementById(id );
    },

    // Retrieve an Andromeda property, creating it if not
    // there and assigning it the default    
    aProp: function(obj,propname,defvalue) {
        if(typeof(obj[propname])=='undefined') {
            if(defvalue==null) defvalue=false;
            obj[propname]=defvalue;
        }
        return obj[propname];
    },

    keyLabel: function(e) {
        var x = e.keyCode;
        
        var x4Keys = { };
        x4Keys['9']  = 'Tab';
        x4Keys['13'] = 'Enter';
        x4Keys['16'] = '';   // actually Shift, but prefix will take care of it
        x4Keys['17'] = '';   // actually Ctrl,  but prefix will take care of it
        x4Keys['18'] = '';   // actually Alt,   but prefix will take care of it
        x4Keys['20'] = 'CapsLock';
        x4Keys['27'] = 'Esc';
        x4Keys['33'] = 'PageUp';
        x4Keys['34'] = 'PageDown';
        x4Keys['35'] = 'End';
        x4Keys['36'] = 'Home';
        x4Keys['37'] = 'LeftArrow';
        x4Keys['38'] = 'UpArrow';
        x4Keys['39'] = 'RightArrow';
        x4Keys['40'] = 'DownArrow';
        x4Keys['45'] = 'Insert';
        x4Keys['46'] = 'Delete';
        x4Keys['112']= 'F1' ;
        x4Keys['113']= 'F2' ;
        x4Keys['114']= 'F3' ;
        x4Keys['115']= 'F4' ;
        x4Keys['116']= 'F5' ;
        x4Keys['117']= 'F6' ;
        x4Keys['118']= 'F7' ;
        x4Keys['119']= 'F8' ;
        x4Keys['120']= 'F9' ;
        x4Keys['121']= 'F10';
        x4Keys['122']= 'F11';
        x4Keys['123']= 'F12';
    
        // If they did not hit a key we know about, return empty
        if(typeof(x4Keys[x])=='undefined') return '';
    
        // otherwise put on any prefixes and return
        var prefix = '';
        if(e.ctrlKey)  prefix = 'Ctrl';
        if(e.altKey)   prefix += 'Alt';
        if(e.shiftKey) prefix += 'Shift';
        
        var retval = prefix + x4Keys[x];
        return retval;
    },   
    
    charLetter: function(charcode) {
        var letters = 
            [ 'a', 'b', 'c', 'd', 'e', 'f', 'g',
              'h', 'i', 'j', 'k', 'l', 'm', 'n',
              'o', 'p', 'q', 'r', 's', 't', 'u',
              'v', 'w', 'x', 'y', 'z' ];
        if(charcode >= 65 && charcode <= 90) {
            return letters[charcode - 65];
        }
        
        var numbers = [ '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ];
        if(charcode >= 48 && charcode <= 57) {
            return numbers[charcode - 48];
        }
    }
}


