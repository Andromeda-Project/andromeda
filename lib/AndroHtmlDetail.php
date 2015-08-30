<?php
/****c* HTML Generation/AndroHtmlDetail
 *
 * NAME
 *    AndroHtmlDetail
 *
 * FUNCTION
 *   The PHP class AndroHtmlDetail provides an HTML table that can
 *   be populated with caption/input rows using addInput().
 *
 *   The object is a subclass of AndroHtml, and supports all of its
 *   methods such as addChild, addClass, etc.
 *
 *
 ******
 */
class AndroHtmlDetail extends AndroHtml
{
    public $firstFocus = false;

    public function __construct($table_id, $complete = false, $height = 300, $p = '')
    {
        $this->hp['x6plugin'] = 'detailDisplay';
        $this->hp['x6table'] = $table_id;
        $this->hp['id'] = 'ddisp_' . $table_id;
        $this->initPlugin();
        $this->hp['xHeight'] = $height;
        if ($complete) {
            $this->htype = 'div';
            $this->innerId = "ddisp_{$table_id}_inner";
            $this->makeComplete($table_id, $height, $p);
        } else {
            $this->htype = 'table';
            $this->inputsTable = $this;
            $this->addClass('x6Detail');
        }
    }
    // KFD 5/27/09 Google #21 Part of allowing override of a detail pane
    //             is to build one normally, then let the user wipe it
    //             out and start over.
    public function removeInner()
    {
        $this->children[1]->children = array();
        $this->children[1]->setHtml('');
        return $this->children[1];
    }

    public function makeComplete($table_id, $height, $parTable)
    {
        // The complete track is much more involved, adds
        // buttons and a status bar at bottom.
        $this->addClass('box2');
        $this->hp['xInitDisabled'] = 'Y';
        $pad0 = x6CssDefine('pad0');
        $this->hp['style'] = "height: {$height}px;
            padding-left: {$pad0}px;
            padding-right: {$pad0}px;";

        $this->hp['xInnerWidthLost'] = ($pad0 * 2)
            // padding left and right
            + x6cssRuleSize('.box2', 'border-left') + x6cssRuleSize('.box2', 'border-right') + x6cssRuleSize('.box1', 'border-left')
            // see below, inner
            + x6cssRuleSize('.box1', 'border-right')
            // box is box1
            + ($pad0 * 7);
        // padding left and right of box1

        // Always need this
        $dd = ddTable($table_id);
        // Now for the display
        // Put some buttons on users
        $this->addButtonBar();
        // KFD 1/29/09 break out pk/fk columns
        if ($parTable == '') {
            $colsFK = array();
        } else {
            //echo $table_id;
            //hprint_r($dd['fk_parents']);
            $x = $dd['fk_parents'][$parTable]['cols_both'];
            $x = explode(',', $x);
            foreach ($x as $pair) {
                list($chd, $par) = explode(':', $pair);
                $colsFK[$chd] = $par;
            }
        }
        // Put in a div that will be the inner box
        //
        $div = $this->h('div');
        $div->addClass('box1');
        $table = $div->h('table');
        $table->hp['style'] = 'float: left; margin-right: 20px';
        $table->addClass('x6Detail');
        $this->inputsTable = $table;
        $cols = projectionColumns($dd, '');
        // KFD 1/2/08.  Loop through columns and try to find anything
        //              with an x6breakafter.  If found, do not break
        //              every 17, use the instructions in x6breakafter.
        $break17 = true;
        foreach ($cols as $idx => $col) {
            if (arr($dd['flat'][$col], 'x6breakafter', '') <> '') {
                $break17 = false;
                break;
            }
        }
        // Define this outside the loop, it is used to make
        // xdefsrc inside of the loop
        $fetches = array('fetchdef', 'fetch', 'distribute');
        $options = array('xTabGroup' => 'ddisp_' . $table_id);
        foreach ($cols as $idx => $col) {
            if ($break17) {
                if ($idx > 0 && $idx % 17 == 0) {
                    $this->inputsTable = $div->h('table');
                    $this->inputsTable->hp['style'] = 'float: left';
                    $this->inputsTable->addClass('x6Detail');
                }
            }
            // KFD 1/29/09.  If detail that is child of a parent,
            //               see if this column needs to pull
            if (!isset($colsFK[$col])) {
                $xoptions = $options;
            } else {
                $xoptions = array_merge($options, array('attributes' => array('xdefsrc' => $parTable . '.' . $colsFK[$col])));
            }
            // KFD 2/4/09. If this is in the fetch family, set its
            //             xdefsrc
            $autoid = strtolower($dd['flat'][$col]['automation_id']);
            if (in_array($autoid, $fetches)) {
                $xoptions = array_merge($options, array('attributes' => array('xdefsrc' => strtolower($dd['flat'][$col]['auto_formula']))));
            }

            $this->addTRInput($dd, $col, $xoptions);
            $x6ba = trim(arr($dd['flat'][$col], 'x6breakafter', ''));
            if ($x6ba == 'column') {
                $this->inputsTable = $div->h('table');
                $this->inputsTable->hp['style'] = 'float: left';
                $this->inputsTable->addClass('x6Detail');
            }
            if ($x6ba == 'line') {
                $tr = $this->inputsTable->h('tr');
                $td = $tr->h('td', '&nbsp;');
                $td->hp['colspan'] = 2;
            }
        }
        // Calculate height of inner area
        $hinner = $height - ($pad0 * 2)
            // padding top and bottom
            - x6cssDefine('lh0')
            // for status bar at bottom
            - x6cssRuleSize('.box1', 'border-top') - x6cssRuleSize('.box1', 'border-bottom') - x6cssRuleSize('.box1', 'padding-top') - x6cssRuleSize('.box1', 'padding-bottom') - x6cssHeight('div.x6buttonBar a.button');
        $div->hp['style'] = "height: {$hinner}px; clear: both; 
            overflow-y: scroll; position: relative;
            padding: {$pad0}px;";
        // Keep track of the inner div for possible additions
        $div->hp['id'] = $this->innerId;
        $lineheight = x6cssHeight('td.x6Caption');
        $emptyHeight = $hinner - $lineheight * (count($cols))
            // computed height
            - (count($cols) - 1);
        // borders between rows
        $this->hp['xInnerHeight'] = $hinner;
        $this->hp['xInnerEmpty'] = $emptyHeight;

        $this->innerDiv = $div;

        $sb = $this->h('div');
        $sb->addClass('statusBar');
        $sbl = $sb->h('div');
        $sbl->addClass('sbleft');
        $sbl->hp['id'] = 'sbl_' . $table_id;
        $sbr = $sb->h('div');
        $sbr->addClass('sbright');
        $sbr->hp['id'] = 'sbr_' . $table_id;

        return $emptyHeight;
    }

    /****m* AndroHtml/addTRInput
     *
     * NAME
     *    AndroHtml.addTRInput
     *
     * FUNCTION
     * The PHP method AndroHtml.addInput adds a TR and two TD elements
     *   to a TABLE.  The left side has class "x6Caption" and contains the
     *   caption/label for the field.  The right side contains an input
     *   for the field and as class 'x6Input'.
     *
     *
     *
     * INPUTS
     *   array $dd - the table's complete data dictionary
     *   string $column - name of the database column (field)
     *
     *
     * SOURCE
     */
    public function addTrInput(&$dd, $column, $options = array())
    {
        // something we need for b/w compatibility that is
        // easier to declare and ignore than it is to
        // try to get rid of. (Also, getting rid of it will
        // break some of my older apps).
        $tabLoop = array();

        $tr = $this->inputsTable->h('tr');
        $td = $tr->h('td', $dd['flat'][$column]['description']);
        $td->addClass('x6Caption');

        $input = input($dd['flat'][$column], $tabLoop, $options);
        if (!$this->firstFocus) {
            $input->hp['x6firstFocus'] = 'Y';
            $this->firstFocus = true;
        }
        $td = $tr->h('td');
        $td->setHtml($input->bufferedRender());
        $td->addClass('x6Input');
    }
}
