<?php
/****c* HTML Generation/AndroHtmlGrid
 *
 * NAME
 *    AndroHtmlGrid
 *
 * FUNCTION
 *   The PHP class AndroHtmlGrid simulates an HTML Table element
 *   using only Divs.
 *
 *   The object is a subclass of AndroHtml, and supports all of its
 *   methods such as addChild, addClass, etc.
 *
 *
 ******
 */
class AndroHtmlGrid extends AndroHtml
{
    public $columns = array();
    public $headers = array();
    public $lastRow = false;
    public $lastCell = 0;
    public $scrollable = false;
    public $colWidths = 0;
    public $rows = array();
    public $buttonBar = false;
    public $colOptions = array();

    public function __construct($height = 300, $table = '', $lookups = false, $sortable = false, $bb = false, $edit = false)
    {
        $this->lookups = $lookups;
        $this->sortable = $sortable;
        $this->htype = 'div';
        $this->addClass('tdiv box3');
        $this->hp['x6plugin'] = 'grid';
        $this->hp['x6table'] = $table;
        $this->hp['id'] = 'grid_' . $table; //.'_'.rand(100,999);
        $this->hp['style'] = "height: {$height}px;";
        $this->height = $height;
        $cssLineHeight = x6cssHeight('div.thead div div');
        $this->hp['cssLineHeight'] = $cssLineHeight;
        $this->hp['xRowsVisible'] = intval($height / $cssLineHeight);
        $this->hp['xGridHeight'] = $height;
        $this->hp['xLookups'] = $lookups ? 'Y' : 'N';
        $this->hp['xSortable'] = $sortable ? 'Y' : 'N';
        $this->hp['xInitKeyboard'] = 'Y';
        // Figure the tbody height.  If lookups has
        // been set, double the amount we subtract
        $height-= x6cssHeight('div.thead div div');
        if ($lookups) {
            $height-= x6cssHeight('div.thead div div');
        }
        if ($bb) {
            $height-= $this->bbHeight();
        }
        // create default options
        $this->hp['xGridHilight'] = 'Y';
        // Very first thing we add is a style, we will
        // overwrite it later
        $this->h('style');
        // notice: we slip one div inside of thead,
        //         we assume there will always be
        //         only one row, the column headers
        $x = $this->h('div');
        $x->addClass('thead');
        $this->dhead0 = $x;
        $this->dhead = $x->h('div');
        // Again, add button bar if required
        if ($bb) {
            if (is_string($bb)) {
                $this->addButtonBar($bb);
            } else {
                $this->addButtonBar();
            }
        }
        $this->hp['xButtonBar'] = $bb ? 'Y' : 'N';
        // Add features if editInPlace
        $this->editable = false;
        if ($edit) {
            $this->hp['uiNewRow'] = 'Y';
            // vs. nothing
            $this->hp['uiEditRow'] = 'Y';
            // vs. nothing
            $this->editable = true;
        }
        // The body is empty, we have to add row by row
        $this->dbody = $this->h('div');
        $this->dbody->addClass('tbody');
        $this->dbody->hp['id'] = 'tbody_' . $table;
        $this->dbody->hp['style'] = "height: {$height}px;";
        // The footer is like the header, we go ahead
        // and insert the only row, assuming they will
        // be adding
        $x = $this->h('div');
        $this->dfoot = $x->h('div');
        $this->dfoot->addClass('tfoot');
        // KFD 12/18/08.  Figured that this should always be the last
        //     command, never up in the middle.  Reason is that an object
        //     may put other plugins onto itself and then expect them to
        //     be active while it is initializing.  By putting this last,
        //     we ensure that that is the case.
        $this->initPlugin();
    }

    public function setColumnOptions($options)
    {
        $this->colOptions = $options;
    }

    public function inputsRow()
    {
        $dd = ddTable($this->hp['x6table']);
        // Make an input for each column and build up
        // a string of HTML for these.
        $html = '';
        $tabIndex = 1000;
        $count = 0;
        $tabLoop = null;
        foreach ($this->columnsById as $colname => $colinfo) {
            $options = a($this->colOptions, $colname, array());
            $wrapper = html('div');
            $wrapper->hp['gColumn'] = $count;
            $count++;
            $input = input($dd['flat'][$colname], $tabLoop, $options);
            $input->hp['tabindex'] = $tabIndex++;
            // KFD 3/6/09 Sourceforge 2668359
            if ($input->htype == 'textarea') {
                $input->setHtml("*VALUE_$colname*");
            } else {
                $input->hp['value'] = "*VALUE_$colname*";
            }
            $input->hp['xClassRow'] = 0;
            $input->hp['xTabGroup'] = 'rowEdit';
            $wrapper->addClass($this->hp['id'] . '_' . $colname);
            if (!in_array($colinfo['type_id'], array('cbool', 'gender'))) {
                unset($input->hp['size']);
            }
            $wrapper->addChild($input);
            $html.= $wrapper->bufferedRender(null, true);
        }
        $html = str_replace("\n", "", $html);
        $strLeft = 'x6.byId("' . $this->hp['id'] . '").zRowEditHtml';
        jqDocReady("$strLeft = \"$html\"", true);
    }

    /****m* AndroHtmlGrid/addColumn
     *
     * NAME
     *    addColumn
     *
     * FUNCTION
     *   This PHP class method addColumn specifies the
     *   description and size of a new column.  Call it once
     *   for each column to be added to the tabdiv.
     *
     * INPUTS
     *   - $caption string, becomes both caption and ID
     *
     * RETURNS
     *   - AndroHtml, reference to the content area for the new tab
     *
     ******/
    public function addColumn($options)
    {
        $column_id = arr($options, 'column_id');
        if ($column_id == '') {
            $column_id = rand(100000, 999999);
        }
        $dispsize = arr($options, 'dispsize', 10);
        // KFD 3/6/09 Sourceforge 2668452, respect descshort if present
        $description = arr($options, 'descshort', '');
        if ($description == '') {
            $description = arr($options, 'description', 'No Desc');
        }
        $type_id = arr($options, 'type_id', 'char');
        $forcelong = arr($options, 'forcelong', false);
        $table_id_fko = arr($options, 'table_id_fko', '');
        // Permanently store the column information,
        // and increment the running total
        $width1 = max($dispsize, strlen(trim($description)));
        $width1++;
        // KFD 1/8/09, expand width (maybe) if this column
        //             gets an x6select
        if ($table_id_fko <> '') {
            if ($type_id == 'cbool' || $type_id == 'gender') {
                if ($width1 < 5) {
                    $width1 = 5;
                }
            } else {
                $width1+= 3;
            }
        }
        // Now that we have what we need from description,
        // turn spaces into &nbsp;
        $description = str_replace(' ', '&nbsp;', $description);
        // KFD Calculated width of 14 12px chars is 110px
        //     This means avg width is 7.85 pixels
        //     This means the ratio of width to height is .654
        //     However, if you add sortable, it gets a LEETLE TOO TINY,
        //     so we kicked it up to....
        $width1*= x6CssDefine('bodyfs', '12px') * .67;
        $width = $forcelong ? $width1 : intval(min($width1, 200));
        $pad0 = x6CSSDefine('pad0');
        $bord = 1;
        // HARDCODE!
        $this->colWidths+= $width + ($pad0 * 2) + ($bord * 2);
        // Save the information about the column permanently,
        // we will need all of this when adding cells.
        $colinfo = array('description' => $description, 'dispsize' => $dispsize, 'type_id' => $type_id, 'column_id' => $column_id, 'width' => $width, 'colprec' => arr($options, 'colprec', $dispsize), 'colscale' => arr($options, 'colscale', $dispsize), 'uiro' => arr($options, 'uiro', 'N'));
        $this->columns[] = $colinfo;
        $this->columnsById[$column_id] = $colinfo;
        $cssExtra = '';
        if (in_array($type_id, array('int', 'numb', 'money'))) {
            $cssExtra = 'text-align: right';
        }
        $styleId = 'div.' . $this->hp['id'] . '_' . $column_id;
        $this->colStyles[$styleId] = "width: {$width}px; $cssExtra";
        $iWidth = $width;
        if ($table_id_fko <> '') {
            $iWidth-= x6cssdefine('bodyfs', '12px') * .67 * 5;
            $this->colStyles[$styleId . ' input'] = "width: {$iWidth}px; $cssExtra";
        } elseif ($type_id == 'mime-f') {
            $iWidth-= x6cssdefine('bodyfs', '12px') * .67 * 20;
            $this->colStyles[$styleId . ' input'] = "width: {$iWidth}px; $cssExtra";
        } elseif (!in_array($type_id, array('cbool', 'gender'))) {
            $this->colStyles[$styleId . ' input'] = "width: {$iWidth}px; $cssExtra";
        }
        // Finally, generate the HTML.
        $div = $this->dhead->h('div', $description);
        $div->hp['xColumn'] = $column_id;
        $div->addclass($this->hp['id'] . '_' . $column_id);
        $this->headers[] = $div;
    }

    /****m* AndroHtmlGrid/lastColumn
     *
     * NAME
     *    lastColumn
     *
     * FUNCTION
     *   This PHP class method lastColumn must be called
     *   after you have defined all of the columns in the
     *   table.  This method computes and assigns the
     *   final width of the overall table.
     *
     * INPUTS
     *   - $scrollable (boolean) if true, make table scrollable
     *
     ******/
    public function lastColumn($scrollable = true)
    {
        // Save the scrollable setting, and compute the final
        // width of the table
        //$this->scrollable=$scrollable;
        if ($scrollable) {
            $this->columns[] = array('description' => '&nbsp;', 'dispsize' => 0, 'type_id' => '', 'column_id' => '', 'width' => 15);
            $pad0 = x6CSSDefine('pad0');
            $bord = 1;
            // HARDCODE!
            $this->colWidths+= 15 + ($pad0 * 2) + ($bord * 2);
            $div = $this->dhead->h('div', '');
            $div->hp['style'] = "
                max-width: 15px;
                min-width: 15px;
                width:     15px;";
        }
        // Send the column structure back as JSON
        jqDocReady("x6.byId('" . $this->hp['id'] . "').zColsInfo=" . json_encode($this->columns), true);
        jqDocReady("x6.byId('" . $this->hp['id'] . "').zColsById=" . json_encode($this->columnsById), true);
        // If editable, add in the invisible row of inputs
        if ($this->editable) {
            $this->inputsRow();
        }
        // If the lookups flag is set, add that now
        if ($this->lookups) {
            $this->addLookupInputs();
        }
        // If the sortable flag was set, add that now
        if ($this->sortable) {
            $this->makeSortable();
        }
        // Generate the cell styles
        $styles = "\n";
        foreach ($this->colStyles as $selector => $rules) {
            $styles.= "$selector { " . $rules . "}\n";
        }
        $this->children[0]->setHTML($styles);
        // Get the standard padding, we hardcoded
        // assuming 2 for border, 3 for padding left
        //--$extra = 5;

        // now work out the final width of the table by
        // adding up the columns, adding one for each
        // column (the border) and two more for the table
        // border.
        $width = $this->colWidths;
        // JB:  Increased width of master table by 1px so it lines up
        //$width+= ((count($this->columns))*$extra)+1;  // border + padding
        //--$width+= (count($this->columns))*$extra;
        //$width+= 39;  // fudge factor, unknown
        $this->hp['style'].= "width: {$width}px";
        $this->width = $width;
        return $width;
    }

    public function makeSortable()
    {
        $table_id = $this->hp['x6table'];
        foreach ($this->headers as $idx => $header) {
            $hdrhtml = $header->getHtml();
            $a = html('a-void');
            $a->setHtml($hdrhtml);
            //$a->setHtml('&hArr;');
            $col = $this->columns[$idx]['column_id'];
            $args = "{xChGroup:'$table_id', xColumn: '$col'}";
            $a->hp['onclick'] = "x6events.fireEvent('reqSort_$table_id',$args)";
            $a->hp['xChGroup'] = $table_id;
            $a->hp['xColumn'] = $col;
            $this->headers[$idx]->setHtml($a->bufferedRender());
        }
    }

    public function addRow($id, $thead = false)
    {
        if (!$thead) {
            $this->lastRow = $this->dbody->h('div');
        } else {
            $this->lastRow = $this->dhead0->h('div');
        }
        $this->rows[] = $this->lastRow;

        // KFD EXPERIMENTAL 12/9
        $this->lastRow->hp['id'] = $this->hp['x6table'] . "_$id";
        //$this->lastRow->hp['id'] = 'row_'.$id;

        $this->lastCell = 0;
        // PHP-JAVASCRIPT DUPLICATION ALERT!
        // This code also exists in x6.js in browser-side
        // constructor of the tabDiv object.
        $table_id = $this->hp['x6table'];
        if ($this->hp['xGridHilight'] == 'Y') {
            // Removes hilight from any other row, and hilights
            // this one if it is not selected (edited)
            $this->lastRow->hp['onmouseover'] = 'x6grid.mouseover(this)';
            //    "$(this).siblings('.hilight').removeClass('hilight');
            //    $('#row_$id:not(.selected)').addClass('hilight')";
            if (!$thead) {
                $this->lastRow->hp['onclick'] = "x6events.fireEvent('reqEditRow_$table_id',$id);";
            }
        }

        return $this->lastRow;
    }

    public function addCell($child = '', $class = '', $id = '', $convert = true)
    {
        if (is_object($child)) {
            $child = $child->bufferedRender();
        } else {
            if ($convert) {
                $child = str_replace(' ', '&nbsp;', $child);
            }
        }
        if (trim($child) == '') {
            $child = '&nbsp;';
        }
        // figure out if we need a new row
        $maxcols = count($this->columns);
        if ($this->scrollable) {
            $maxcols--;
        }
        if ($this->lastCell > $maxcols) {
            $this->addRow();
        }
        // now put out the actual div
        $info = $this->columns[$this->lastCell];
        $width = $info['width'];
        $div = $this->lastRow->h('div', $child);
        if ($id <> '') {
            $div->hp['id'] = $id;
        }
        if ($class != '') {
            $div->addClass($class);
        }
        $div->hp['gColumn'] = $this->lastCell;
        $div->addClass($this->hp['id'] . '_' . $this->columns[$this->lastCell]['column_id']);

        /*
        $div->hp['style'] ="
            overflow: hidden;
            max-width: {$width}px;
            min-width: {$width}px;
            width:     {$width}px;";
        */
        // Now for numerics do right-justified
        if (in_array($info['type_id'], array('int', 'numb', 'money'))) {
            $div->hp['style'] = 'text-align: right';
        }
        // up the cell counter
        $this->lastCell++;
    }

    public function addData($rows)
    {
        $dd = ddTable($this->hp['x6table']);
        foreach ($rows as $row) {
            $this->addRow($row['skey']);
            foreach ($this->columns as $colinfo) {
                if ($colinfo['column_id'] == '') {
                    continue;
                }

                $column_id = trim($colinfo['column_id']);
                if (isset($row[$column_id])) {
                    $type_id = $dd['flat'][$column_id]['type_id'];
                    $x6view = arr($dd['flat'][$column_id], 'x6view', 'text');
                    if (!($type_id == 'text' && $x6view == 'window')) {
                        $value = hFormat($type_id, $row[$column_id]);
                    } else {
                        $t = $this->hp['x6table'];
                        $c = $column_id;
                        $s = $row['skey'];
                        $a = html('a');
                        $a->setHtml('View');
                        $a->hp['href'] = "javascript:x6inputs.viewClob($s,'$t','$c')";
                        $value = $a;
                    }
                    $this->addCell($value);
                } else {
                    $this->addCell('');
                }
            }
        }
    }

    public function noResults()
    {
        return;
        $div = $this->dbody->h('div');
        $div->hp['id'] = $this->hp['x6table'] . '_noresults';
        $div->hp['style'] = 'text-align: center; padding-top: 20px';
        $div->setHTML('<b>No results found</b>');
    }

    public function addLookupInputs()
    {
        $fakeCI = array('colprec' => '10');

        $table_id = $this->hp['x6table'];
        $this->addRow('lookup', true);
        foreach ($this->columns as $idx => $colinfo) {
            // Skip the column that is for the scrollbar
            $column = trim($colinfo['column_id']);
            if ($column == '') {
                continue;
            }

            $inpid = 'search_' . $table_id . '_' . $column;

            $width = $colinfo['width'] - (2 * x6cssDefine('pad0')) - 2;

            $nothing = array();
            $options = array('forceinput' => true);
            $inp = input($colinfo, $nothing, $options);
            if ($idx == 0) {
                $inp->ap['x6firstFocus'] = 'Y';
            }
            $inp->hp['maxlength'] = 500;
            $inp->hp['id'] = $inpid;
            $inp->hp['autocomplete'] = 'off';
            $inp->hp['xValue'] = '';
            $inp->hp['xColumnId'] = $column;
            $inp->hp['xNoPassup'] = 'Y';
            $inp->hp['onkeyup'] = "x6.byId('" . $this->hp['id'] . "').fetch()";
            $inp->hp['style'] = "width: {$width}px";
            $inp->hp['xLookup'] = 'Y';
            $inp->hp['value'] = gp('pre_' . $column, '');
            if (isset($inp->hp['x6select'])) {
                unset($inp->hp['x6select']);
            }
            //$inp->ap['xParentId'] = $t->hp['id'];
            //$inp->ap['xNoEnter'] = 'Y';
            $this->addCell($inp, 'linput');
        }

        if ($this->scrollable) {
            $this->addCell('');
        }
    }
}
