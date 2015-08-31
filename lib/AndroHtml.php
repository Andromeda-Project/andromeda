<?php
/****c* HTML Generation/AndroHtml
 *
 * NAME
 *    AndroHtml
 *
 * FUNCTION
 * The class AndroHtml is used to build object oriented html hierarchies  in order to prevent
 * the mixing of html and php code in andromeda.  Each AndroHtml object can have attributes,
 * custom parameters, children elements, styles, and inner html.  Essentially everything a typical
 * html element can have.
 *
 * AndroHtml elements are build exclusively through the factory function html, which handles
 * html children and parents.
 *
 * SEE ALSO
 * html
 *
 ******
 */
class AndroHtml
{
    /****v* AndroHtml/children
     *
     * NAME
     *    children
     *
     * FUNCTION
     * The variable children is an array that holds all of the AndroHtml elements held within this current
     * AndroHtml element.
     *
     ******
     */
    public $children = array();

    /****v* AndroHtml/hp
     *
     * NAME
     *    hp
     *
     * FUNCTION
     * The variable hp is an array that holds all of this AndroHtml element's html properties.  These are
     * properties that would show up in the html code on the page.  For instace, the property "href" is an
     * html property for the "a" element.
     *
     ******
     */
    public $hp = array();

    /****v* AndroHtml/code
     *
     * NAME
     *    code
     *
     * FUNCTION
     * The variable code is an array that holds all of this AndroHtml element's javascript code references.
     *
     ******
     */
    public $code = array();
    public $functions = array();

    /****v* AndroHtml/ap
     *
     * NAME
     *    ap
     *
     * FUNCTION
     * The variable ap is an array that holds all of this AndroHtml element's additional properties.  These
     * are self created properties for the object that do not show up in the html source code.
     *
     ******
     */
    public $ap = array();

    /****v* AndroHtml/styles
     *
     * NAME
     *    styles
     *
     * FUNCTION
     * The variable styles is an associative array that holds all of this AndroHtml element's css style
     * properties.  If you want to set a style property, you name the property as a key in the array, and
     * you set the value for the css property as the value for the key.
     *
     * EXAMPLE
     * $htmlObject->style = 'float: right';
     *
     ******
     */
    public $style = array();

    /****v* AndroHtml/innerHtml
     *
     * NAME
     *    innerHtml
     *
     * FUNCTION
     * The variable innerHtml is a string that contains all of the code and text held within this AndroHtml
     * element.
     *
     ******
     */
    public $innerHtml = '';

    /****v* AndroHtml/htype
     *
     * NAME
     *    htype
     *
     * FUNCTION
     * The variable htype is a string that contains the name of the type of html tag that this AndroHtml
     * object is representing.  For example, if this AndroHtml element is representing a div element, htype
     * would be equal to 'div';
     *
     ******
     */
    public $htype = '';

    /****v* AndroHtml/classes
     *
     * NAME
     *    classes
     *
     * FUNCTION
     * The variable classes is an array that holds all of this AndroHtml element's css classes.
     *
     ******
     */
    public $classes = array();

    /***** AndroHtml/autoFormat
     *
     * NAME
     *    autoFormat
     *
     * FUNCTION
     * The variable autoFormat is a boolean value which states whether this AndroHtml element is
     * autoformatted or not.
     *
     ******
     */
    public $autoFormat = false;

    /****v* AndroHtml/isParent
     *
     * NAME
     *    isParent
     *
     * FUNCTION
     * The variable isParent is a boolean value which states whether this AndroHtml element is a parent
     * element or not.
     *
     ******
     */
    public $isParent = false;

    /****m* AndroHtml/setHtml
     *
     * NAME
     *    setHtml
     *
     * FUNCTION
     * The method setHtml sets this AndroHtml element's innerHtml to the provided string.
     *
     * INPUTS
     * string $value - New innerHtml
     *
     ******
     */
    public function setHtml($value)
    {
        $this->innerHtml = $value;
    }

    /****m* AndroHtml/getHtml
     *
     * NAME
     *    getHtml
     *
     * FUNCTION
     * The method getHtml retrieves the innerHTML of an HTML
     *   object.  The HTML of nested children is not returned,
     *   only the literal HTML set by the setHTML() method.
     *
     * INPUTS
     * none
     *
     * RETURNS
     *   string
     *
     ******
     */
    public function getHtml()
    {
        return $this->innerHtml;
    }

    /****m* AndroHtml/clear
     *
     * NAME
     *    clear
     *
     * FUNCTION
     * The method clear removes all child elements and innerHtml from this AndroHtml element.
     *
     * SOURCE
     */
    public function clear()
    {
        $this->innerHtml = '';
        $this->children = array();
    }

    /******/

    /****m* AndroHtml/clearHP
     *
     * NAME
     *    clearHP
     *
     * FUNCTION
     * The method clearHP removes all Html properties from this AndroHtml element.
     *
     * SOURCE
     */
    public function clearHP()
    {
        $this->hp = array();
    }

    /******/

    /****m* AndroHtml/clearAP
     *
     * NAME
     *    clearAP
     *
     * FUNCTION
     * The method clearAP removes all additional properties from this AndroHtml element.
     *
     * SOURCE
     */
    public function clearAP()
    {
        $this->ap = array();
    }

    /******/

    /****m* AndroHtml/addClass
     *
     * NAME
     *    addClass
     *
     * FUNCTION
     * The method addClass adds the provided css class to this AndroHtml object.
     *
     * INPUTS
     * string $value - Css class to add
     *
     * SOURCE
     */
    public function addClass($value)
    {
        if (is_array(value)) {
            foreach ($value as $class) {
                $this->classes[] = $class;
            }
        } else {
            $this->classes[] = $value;
        }
    }

    /******/

    /****m* AndroHtml/addStyle
     *
     * NAME
     *    addStyle
     *
     * FUNCTION
     * The method addStyle adds the provided CSS rule to
     *   any that are already defined for the node.
     *
     * INPUTS
     * string $value - Css class to add
     *
     * SOURCE
     */
    public function addStyle($value)
    {
        if (substr($value, -1) != ';') {
            $value.= ';';
        }
        if (!isset($this->hp['style'])) {
            $this->hp['style'] = $value;
        } else {
            $this->hp['style'].= $value;
        }
    }

    /******/

    /****m* AndroHtml/removeClass
     *
     * NAME
     *    removeClass
     *
     * FUNCTION
     * The method removeClass removes the provided css class from this AndroHtml object.
     *
     * INPUTS
     * string $value - Css class to remove from this object
     *
     * SOURCE
     */
    public function removeClass($value)
    {
        $index = array_search($value, $this->classes);
        if ($index) {
            unset($this->classes[$index]);
        }
    }

    /******/

    /****m* AndroHtml/addChild
     *
     * NAME
     *    addChild
     *
     * FUNCTION
     * The method addChild adds a child html element to this AndroHtml object.
     *
     * INPUTS
     * object $object - AndroHtml element to add to this object as a child element
     *
     * SOURCE
     */
    public function addChild($object)
    {
        $this->children[] = $object;
    }

    /******/

    /****m* AndroHtml/html
     *
     * NAME
     *    html
     *
     * FUNCTION
     * The method html acts a lot like the library function html, however it adds the created html element
     * directly to this AndroHtml element, specifying that this AndroHtml element is the parent element.
     *
     * INPUTS
     * string $tag - Name of html tag.
     * mixed $innerHml - Innerhtml for the created html element.
     * string $class - Css class for the html element.
     * SOURCE
     */
    public function html($tag, $innerHTML = '', $class = '')
    {
        $x = html($tag, $this, $innerHTML);
        if ($class <> '') {
            $x->addClass($class);
        }
        return $x;
    }

    /******/

    /****m* AndroHtml/h
     *
     * NAME
     *    h
     *
     * FUNCTION
     * The method h is a shortcut for the method html.
     *
     * SEE ALSO
     * html
     *
     * SOURCE
     */
    public function h($tag, $innerHTML = '', $class = '')
    {
        return $this->html($tag, $innerHTML, $class);
    }

    /******/

    /****m* AndroHtml/form
     *
     * NAME
     *    AndroHtml/form
     *
     * FUNCTION
     * The PHP method form creates an HTML FORM node, adds it as a
     *   child to the current node, and returns a reference to it.
     *
     *   All inputs are optional.
     *
     * INPUTS
     * string $name - the name of the form.  Default: "Form1"
     *   string $method - the method (GET or POST).  Default: POST
     *   string $action - the URI to route to.  Default "index.php"
     * string $x6page - the value of x6page to go to, defaults to none.
     *
     * SOURCE
     */
    public function form($name = 'Form1', $method = 'POST', $action = "index.php", $x6page = '')
    {
        $form = $this->h('form');
        $form->hp['id'] = $name;
        $form->hp['name'] = $name;
        $form->hp['method'] = $method;
        $form->hp['action'] = $action;
        $form->hp['enctype'] = "multipart/form-data";
        if ($x6page <> '') {
            $symbol = strpos($action, '?') === false ? '?' : '&';
            $form->hp['action'].= $symbol . "x6page=" . $x6page;
        }
        return $form;
    }

    /******/

    /****m* AndroHtml/hidden
     *
     * NAME
     *    hidden
     *
     * FUNCTION
     * The method hidden adds a hidden value to this AndroHtml object.  A hidden variable is stored in
     * an input html element that has the html property 'type' set to 'hidden'.  This enables the passing
     * of variables back and forth from the server to the client browser after refreshes.
     *
     * INPUTS
     * string $name - Name of the hidden variable
     * string $value - Value for the hidden variable
     *
     * SOURCE
     */
    public function hidden($name, $value = '')
    {
        $h = $this->h('input');
        $h->hp['type'] = 'hidden';
        $h->hp['id'] = $name;
        $h->hp['name'] = $name;
        $h->hp['value'] = $value;
        return $h;
    }

    /******/

    /* DEPRECATED */
    public function detailRow($dd, $column, $options = array())
    {
        // something we need for b/w compatibility that is
        // easier to declare and ignore than it is to
        // try to get rid of. (Also, getting rid of it will
        // break some of my older apps).
        $tabLoop = array();

        $tr = $this->h('tr');
        $td = $tr->h('td', $dd['flat'][$column]['description']);
        $td->addClass('x4Caption');

        $input = input($dd['flat'][$column], $tabLoop, $options);
        $td = $tr->h('td');
        $td->setHtml($input->bufferedRender());
        $td->addClass('x4Input');
    }

    /******/

    /****m* AndroHtml/tr
     *
     * NAME
     *    tr
     *
     * FUNCTION
     * The method tr adds a table row html element to this AndroHtml element.  Slightly shorter than
     * using the html method.
     *
     * INPUTS
     *   mixed $innerHTML - inner html
     *   string $class - Css class
     *
     * SOURCE
     */
    public function tr($innerHTML = '', $class = '')
    {
        return $this->html('tr', $innerHTML, $class);
    }

    /******/

    /****m* AndroHtml/td
     *
     * NAME
     *    td
     *
     * FUNCTION
     * The method td adds a table colunn element to this AndroHtml object.
     *
     * INPUTS
     * mixed $innerHTML - inner html for the td element
     * string $class - Css class for this td element
     *
     * SOURCE
     */
    public function td($innerHTML = '', $class = '')
    {
        return $this->html('td', $innerHTML, $class);
    }

    /******/

    public function a($innerHTML, $href, $class = '')
    {
        $a = $this->h('a', $innerHTML, $class);
        $a->hp['href'] = $href;
        return $a;
    }

    /****m* AndroHtml/link
     *
     * NAME
     *    AndroHtml.link
     *
     * FUNCTION
     * The PHP method link adds a hyperlink to his AndroHtml object.
     *
     * INPUTS
     * string $href - Hypertext reference
     * string $innerHTML - inner html for the 'a' tag
     *
     * SOURCE
     */
    public function link($href, $innerHTML)
    {
        $a = $this->h('a', $innerHTML);
        $a->hp['href'] = $href;
        return $a;
    }

    /******/

    /****m* AndroHtml/br
     *
     * NAME
     *    br
     *
     * FUNCTION
     * The method br adds the provided amount of break elements to this AndroHtml object as children
     * elements.
     *
     * INPUTS
     * number $count - Number of break elements to add
     *
     * SOURCE
     */
    public function br($count = 1)
    {
        for ($x = 1; $x <= $count; $x++) {
            $this->children[] = '<br/>';
        }
    }

    /******/

    /****m* AndroHtml/hr
     *
     * NAME
     *    hr
     *
     * FUNCTION
     * The method hr adds the provided number of horizontal rule elements to this AndroHtml object
     * as children elements.
     *
     * INPUTS
     * number $count - Number of horizontal rule elements to add
     *
     * SOURCE
     */
    public function hr($count = 1)
    {
        for ($x = 1; $x <= $count; $x++) {
            $this->children[] = '<hr/>';
        }
    }

    /******/

    /****m* AndroHtml/nbsp
     *
     * NAME
     *    nbsp
     *
     * FUNCTION
     * The method nbsp adds the provided number of non-breaking spaces to this AndroHtml object as
     * children elements.
     *
     * INPUTS
     * number $count - Number of non-breaking spaces to add.
     *
     * SOURCE
     */
    public function nbsp($count = 1)
    {
        for ($x = 1; $x <= $count; $x++) {
            $this->children[] = '&nbsp;';
        }
    }

    /******/

    /****m* AndroHtml/hiddenInputs
     *
     * NAME
     *    AndroHtml.hiddenInputs
     *
     * FUNCTION
     *    The PHP method hiddenInputs adds an invisible div to the
     *    current node and fills it with inputs for provided table.
     *    These can cloned (using jQuery) in browser-side code to
     *    place inputs on-the-fly anywhere on the screen.
     *
     *    The function returns a reference to the div.  The div
     *    contains an associative array indexed on column name
     *    that contains references to the inputs.
     *
     * INPUTS
     *    mixed - either a table name or a data dictionary array reference.
     *
     * RETURNS
     *    reference - reference to the invisible div.
     *
     ******/
    public function hiddenInputs($x)
    {
        // Get a data dictionary
        if (is_array($x)) {
            $dd = $x;
        } else {
            $dd = ddTable($x);
        }
        // Make the master Div
        $div = $this->h('div');
        $div->hp['style'] = 'display: none';
        // Loop through the dictionary, not skipping any column
        foreach ($dd['flat'] as $colname => $colinfo) {
            $wrapper = $div->h('div');
            $wrapper->hp['id'] = 'wrapper_' . $dd['table_id'] . '_' . $colinfo['column_id'];
            $input = input($colinfo);
            $div->inputs[$colname] = $input;
            $wrapper->addChild($input);
        }
        return $div;
    }

    public function addXRefs($table_id, $top, $width, $height)
    {
        $child = new AndroHtmlXrefs($table_id, $top, $width, $height);
        $this->addChild($child);
    }

    /****m* AndroHtml/addButtonBar
     *
     * NAME
     *    AndroHtml.addButtonBar
     *
     * FUNCTION
     *    The PHP method addButtonBarStandard adds a div to the
     *    current node that contains the standard buttons for
     *    [New], [Duplicate], [Save], [Abandon], and [Remove].
     *
     *    The div has class x6buttonBar (note capitalization).
     *
     *    The function returns a reference to the div.  The div
     *    contains an array called buttons that is indexed on
     *    the button action.  The actions are:
     *    * new
     *    * duplicate
     *    * save
     *    * remove
     *    * abandon
     *
     * INPUTS
     *    * string - the name of the table the buttons operate on
     *    * mixed - a list of buttons to include, comma separated.
     *      default is to include all five buttons.
     *
     *
     * RETURNS
     *    reference - reference to the div.
     *
     ******/
    // overrides default addButtonbar
    public function bbHeight()
    {
        return x6cssHeight('div.x6buttonBar a.button');
    }

    public function addButtonBar($list = 'new,save,cancel,delete')
    {
        $bbHeight = $this->bbHeight();
        $table_id = $this->hp['x6table'];
        $abuts = explode(',', $list);
        // Tell us which buttons it has, default to none
        $this->hp['butnew'] = 'N';
        $this->hp['butins'] = 'N';
        $this->hp['butsave'] = 'N';
        $this->hp['butcancel'] = 'N';
        $this->hp['butdelete'] = 'N';
        // First trick is to create the div that will be
        // slipped in above the titles.
        $this->buttonBar = html('div');
        $this->buttonBar->hp['class'] = 'subnav pull-right';
        if (arr($this->hp, 'x6plugin', '') == 'grid') {
            array_unshift($this->dhead0->children, $this->buttonBar);
        } else {
            $this->addChild($this->buttonBar);
        }
        $bb = $this->buttonBar;
        $bb->addClass('x6buttonBar');

        $pad0 = x6cssDefine('pad0');
        // KFD 1/22/09, create two divs, drop buttons into them
        //              this makes it possible to drop in
        //              custom buttons in a new div that is float:left
        $sl = $bb->h('div');
        $sl->hp['style'] = 'float: left';
        $sr = $bb->h('div');
        $sr->hp['style'] = 'float: right';

        if (in_array('new', $abuts)) {
            $this->hp['butnew'] = 'Y';
            $a = $sl->h('a-void', 'New');
            $a->addClass('button_disabled button-first');
            $a->hp['style'] = 'margin-left: 0px';
            $a->hp['x6table'] = $table_id;
            $a->hp['x6plugin'] = 'buttonNew';
            $a->hp['id'] = 'buttonNew_' . $table_id;
            $a->hp['style'] = 'float: left';
            $bb->buttons['new'] = $a;
            $a->initPlugin();

            if (in_array('ins', $abuts)) {
                $this->hp['butins'] = 'Y';
                $a = $sl->h('a-void', 'Insert');
                $a->addClass('button_disabled');
                $a->hp['style'] = 'margin-left: 0px';
                $a->hp['x6table'] = $table_id;
                $a->hp['x6plugin'] = 'buttonInsert';
                $a->hp['id'] = 'buttonInsert_' . $table_id;
                $a->hp['style'] = 'float: left';
                $bb->buttons['ins'] = $a;
                $a->initPlugin();
            }
        }
        if (in_array('save', $abuts)) {
            $this->hp['butsave'] = 'Y';
            $a = $sl->h('a-void', 'Save');
            $a->addClass('button_disabled');
            $a->hp['x6table'] = $table_id;
            $a->hp['x6plugin'] = 'buttonSave';
            $a->hp['id'] = 'buttonSave_' . $table_id;
            $a->hp['style'] = 'float: left';
            $bb->buttons['save'] = $a;
            $a->initPlugin();
        }
        if (in_array('cancel', $abuts)) {
            $this->hp['butcancel'] = 'Y';
            $a = $sr->h('a-void', 'Delete');
            $a->addClass('button_disabled');
            $a->hp['x6table'] = $table_id;
            $a->hp['x6plugin'] = 'buttonDelete';
            $a->hp['id'] = 'buttonDelete_' . $table_id;
            $a->hp['style'] = "float: right; margin-right: {$pad0}px";
            $bb->buttons['remove'] = $a;
            $a->initPlugin();
        }
        if (in_array('delete', $abuts)) {
            $this->hp['butdelete'] = 'Y';
            $a = $sr->h('a-void', 'Cancel');
            $a->addClass('button_disabled');
            $a->hp['x6table'] = $table_id;
            $a->hp['x6plugin'] = 'buttonCancel';
            $a->hp['id'] = 'buttonCancel_' . $table_id;
            $a->hp['style'] = 'float: right';
            $bb->buttons['abandon'] = $a;
            $a->initPlugin();
        }

        return $bb;
    }
    // overrides default addButtonbar
    public function addCustomButtons($obj)
    {
        if ($obj === false) {
            return;
        }

        $this->buttonBar->addChild($obj);
    }

    public function addCustomButton($table, $action, $key, $caption, $permins, $permupd)
    {
        $b = $this->h('a-void', $caption);
        $b->addClass('button');
        $b->hp['buttonKey'] = $key;
        $b->hp['x6table'] = $table;
        $b->hp['x6plugin'] = 'buttonCustom';
        $b->hp['action'] = $action;
        $b->hp['id'] = $action;
        $b->hp['permins'] = $permins;
        $b->hp['permupd'] = $permupd;
        $b->hp['style'] = 'float: left;';
        $b->initPlugin();
        jqDocReady("x6events.fireEvent('disable_{$action}_$table')");
        return $b;
    }

    public function addButtonBarOld($table_id, $buts = null)
    {
        if (is_null($buts)) {
            $buts = 'new,duplicate,save,remove,abandon';
        }
        $abuts = explode(',', $buts);

        $bb = $this->h('div');
        $bb->addClass('x6buttonBar');
        if (in_array('new', $abuts)) {
            $a = $bb->h('a-void', 'New');
            $a->addClass('button button-first');
            $a->hp['style'] = 'margin-left: 0px';
            $a->hp['x6table'] = $table_id;
            $a->hp['x6plugIn'] = 'buttonNew';
            $a->hp['style'] = 'float: left';
            $bb->buttons['new'] = $a;
        }
        //if(in_array('duplicate',$abuts)) {
        //    $a=$bb->h('a-void','Duplicate');
        //    $a->addClass('button');
        //    $a->hp['x6table']  = $table_id;
        //    $a->hp['x6plugIn'] = 'buttonDuplicate';
        //    $a->hp['style']    = 'float: left';
        //    $bb->buttons['duplicate'] = $a;
        //    #jqDocReady("x6events.fireEvent('disable_duplicate')");
        //}
        if (in_array('save', $abuts)) {
            $a = $bb->h('a-void', 'Save');
            $a->addClass('button');
            $a->hp['x6table'] = $table_id;
            $a->hp['x6plugIn'] = 'buttonSave';
            $a->hp['style'] = 'float: left';
            $bb->buttons['save'] = $a;
            jqDocReady("x6events.fireEvent('disable_save')");
        }
        if (in_array('remove', $abuts)) {
            $a = $bb->h('a-void', 'Remove');
            $a->addClass('button');
            $a->hp['x6table'] = $table_id;
            $a->hp['x6plugIn'] = 'buttonRemove';
            $a->hp['style'] = 'float: right';
            $bb->buttons['remove'] = $a;
            jqDocReady("x6events.fireEvent('disable_remove')");
        }
        if (in_array('abandon', $abuts)) {
            $a = $bb->h('a-void', 'Abandon');
            $a->addClass('button');
            $a->hp['x6table'] = $table_id;
            $a->hp['x6plugIn'] = 'buttonAbandon';
            $a->hp['style'] = 'float: right';
            $bb->buttons['abandon'] = $a;
            jqDocReady("x6events.fireEvent('disable_abandon')");
        }

        return $bb;
    }

    /****m* AndroHtml/autoFormat
     *
     * NAME
     *    autoFormat
     *
     * FUNCTION
     * The method autoFormat sets whether this AndroHtml element is autoFormatted or not.  The default
     * input for this function is true.
     *
     * INPUTS
     * boolean $setting - True for autoFormatting, false for none.
     *
     * SOURCE
     */
    public function autoFormat($setting = true)
    {
        $this->autoFormat = $setting;
    }

    /******/

    /****m* AndroHtml/tabIndex
     *
     * NAME
     *    tabIndex
     *
     * FUNCTION
     * The PHP method tabIndex sets the HTML attribute "tabindex" on
     *   the object.  The first time it is called, this routine
     *   sets the index at 1000.  Subsequence calls go to 1001,1002 etc.
     *
     *   The first time you call this method it also marks the
     *   object as getting focus when the page loads.  To force focus
     *   to begin on some other object, call tabFocus instead of
     *   tabIndex for that object.
     *
     * INPUTS
     *   int (optional) starting value.  If this value is supplied,
     *   the object will get this value for its tabIndex, and
     *   subsequent calls will increment from there.  Defaults
     *   to 1000.
     *
     * EXAMPLE
     *   Here is an example:
     *     <?php
     *     $div = html('div');
     *     $input = $div->h('input');
     *     $input->tabIndex();
     *     // more poperty settings...
     *     $input = $div->h('input'); // reuse var, make another input
     *     $input->tabIndex();
     *     ?>
     *
     * SEE ALSO
     *   tabFocus
     *
     ******/
    public function tabIndex($startHere = null)
    {
        if (!is_null($startHere)) {
            $tabIndex = $startHere;
        } else {
            $tabIndex = vgfGet('tabindex', 0);
            if ($tabIndex == 0) {
                $this->hp['x6firstFocus'] = 'Y';
                $tabIndex = 1000;
            }
        }
        $this->hp['tabIndex'] = $tabIndex;
        vgfSet('tabindex', ++$tabIndex);
        if (is_object(vgfGet('lastTab', 0))) {
            $obj = vgfGet('lastTab');
            $obj->hp['xNextTab'] = $this->hp['tabIndex'];
            $this->hp['xPrevTab'] = $obj->hp['tabIndex'];
        }
        vgfSet('lastTab', $this);
    }

    /****m* AndroHtml/tabFocus
     *
     * NAME
     *    tabFocus
     *
     * FUNCTION
     * The PHP method tabFocus does exactly the same thing
     *   as tabIndex, with one additional action.  When the page
     *   loads, this object will start out with focus.
     *
     *   Calling this method more than once while building a page
     *   causes focus to begin on the last object that made
     *   the call.
     *
     *   The first time you call tabIndex, it acts like a call
     *   to tabFocus, so there is no reason to ever call tabFocus
     *   unless you want focus to begin somewhere other than the
     *   first tabbable object.
     *
     * INPUTS
     *   int (optional) starting value.  If this value is supplied,
     *   the object will get this value for its tabIndex, and
     *   subsequent calls will increment from there.
     *
     * EXAMPLE
     *   Here is an example:
     *     <?php
     *     $div = html('div');
     *     $input = $div->h('input');
     *     $input->tabFocus();   // first input should get focus
     *     // more poperty settings...
     *     $input = $div->h('input'); // reuse var, make another input
     *     $input->tabIndex();
     *     ?>
     *
     *
     ******/
    public function tabFocus($startHere = null)
    {
        $this->tabIndex($startHere);
        $this->hp['x6firstFocus'] = 'Y';
    }
    // KFD BLUNT WEAPON.  This really is meant for very simple
    //                    elements where you just make it scrollable
    public function scrollable($height = '')
    {
        $this->addStyle('overflow-y: scroll;');
        $this->addStyle("height: $height");
    }

    /****m* AndroHtml/tBodyRows
     *
     * NAME
     *    tBodyRows
     *
     * FUNCTION
     * The method tBodyRows adds a set of elements to something with striping option.
     *
     * INPUTS
     * array $rows - rows of elements to add
     * array $options - striping options
     *
     ******/
    public function tBodyRows($rows, $options = array())
    {
        $rowIdPrefix = 'row_';
        $stripe = $stripe1 = $stripe2 = $stripe3 = 0;
        if (a($options, 'stripe', 0) > 0) {
            $stripe1 = $options['stripe'];
            $stripe2 = $stripe1 - 1;
            $stripe3 = $stripe1 * 2;
        }
        $stripe = a($options, 'stripeCss') == '' ? 0 : 1;
        $tbody = html('tbody', $this);
        foreach ($rows as $index => $row) {
            $tr = html('tr', $tbody);
            $tr->hp['id'] = $rowIdPrefix . ($index + 1);
            $tr->hp['valign'] = 'top';

            if ($stripe1 > 0) {
                $i = $index % $stripe3;
                if ($i > $stripe2) {
                    $tr->addClass('lightgray');
                } else {
                    if ($i < $stripe2) {
                        $tr->addClass('lightgraybottom');
                    }
                }
            }

            foreach ($row as $colname => $colvalue) {
                html('td', $tr, $colvalue);
            }
        }
        return $tbody;
    }

    /***** AndroHtml/addInput
     *
     * NAME
     *    addInput
     *
     * FUNCTION
     * The PHP method AndroHtml::addInput adds an HTML input as
     *   a child of the current node.
     *
     * INPUTS
     *   array - dictionary info on the field, e.g. $dd['flat']['price']
     *
     * RETURNS
     *   node - a reference to the input.
     *
     * SEE ALSO
     *   AndroHtmlTable
     *
     ******/
    public function addInput($colinfo)
    {
        $input = input($colinfo);
        $this->addChild($input);
        return $input;
    }

    /****m* AndroHtml/addTable
     *
     * NAME
     *    addTable
     *
     * FUNCTION
     * The PHP method AndroHtml::addTable adds an instance of
     *   class AndroHtmlTable as a child node.
     *   The resulting table has special
     *   routines for easily adding thead, tbody, tr, th and td
     *   cells.
     *
     * SEE ALSO
     *   AndroHtmlTable
     *
     ******/
    public function addTable()
    {
        $newTable = new AndroHtmlTable();
        $this->addChild($newTable);
        return $newTable;
    }

    public function addTableController($table_id)
    {
        $retval = new AndroHtmlTableController($table_id);
        $this->addChild($retval);
        return $retval;
    }

    /****m* AndroHtml/addGrid
     *
     * NAME
     *    addTabGrid
     *
     * FUNCTION
     * The PHP method AndroHtml::addGrid adds an instance of
     *   class AndroHtmlGrid as a child node.
     *   A "Grid" is a simulated HTML table that uses divs
     *   instead of TD elements.  The two main reasons for doing
     *   this are that you cannot put an onclick() routine onto
     *   a TR in Internet Explorer (as of IE7 oct 2008) and
     *   the scrollable body is easier to get going on
     *   a DIV.
     *
     * INPUTS
     *   HEIGHT: The total height of the table including borders,
     *   header, and footer.
     *
     *
     * SEE ALSO
     *   AndroHtmlTable
     *
     ******/
    public function addGrid($height, $table_id, $lookups = false, $sortable = false, $bb = false, $edit = false)
    {
        $newTable = new AndroHtmlGrid($height, $table_id, $lookups, $sortable, $bb, $edit);
        $this->addChild($newTable);

        return $newTable;
    }

    /****m* AndroHtml/addDetail
     *
     * NAME
     *    addTabDiv
     *
     * FUNCTION
     * The PHP method AndroHtml::addDetail adds an instance of
     *   class AndroHtmlDetail as a child node.  This is an HTML
     *   TABLE that will contain rows of inputs - caption on the
     *   left and input on the right.
     *
     * INPUTS
     *   string table_id - the name of the table being edited
     *
     *
     * SEE ALSO
     *   AndroHtmlDetail
     *
     ******/
    public function addDetail($table_id, $complete = false, $height = 300, $p = '')
    {
        $newDetail = new AndroHtmlDetail($table_id, $complete, $height, $p);
        $this->addChild($newDetail);
        return $newDetail;
    }

    /****m* AndroHtml/addTabs
     *
     * NAME
     *   addTabs
     *
     * FUNCTION
     * The PHP method AndroHtml::addTabs adds an instance of
     *   class AndroHtmlTabs as a child node.
     *
     *   The AndroHtmlTabs class depends on jQuery's UI/Tabs
     *   feature.
     *
     * SEE ALSO
     *   AndroHtmlTabs
     *
     ******/
    public function addTabs($id, $height = 500, $options = array())
    {
        $newTabs = new AndroHtmlTabs($id, $height, $options);
        $this->addChild($newTabs);
        return $newTabs;
    }

    /****m* AndroHtml/addCheckList
     *
     * NAME
     *    addCheckList
     *
     * FUNCTION
     * The PHP method AndroHtml::addCheckList adds an instance of
     *   class AndroHtmlCheckList as a child node.
     *
     * SEE ALSO
     *   AndroHtmlCheckList
     *
     ******/
    public function addCheckList()
    {
        $newTable = new AndroHtmlCheckList();
        $this->addChild($newTable);
        return $newTable;
    }

    /* DEPRECATED */
    public function makeThead($thvalues, $class = 'dark')
    {
        // Make it an array if it is not already
        if (!is_array($thvalues)) {
            $thvalues = explode(',', $thvalues);
        }
        $thead = html('thead', $this);
        $tr = html('tr', $thead);
        foreach ($thvalues as $th) {
            $tr->h('th', $th, $class);
        }
        return $thead;
    }

    /* DEPRECATED */
    public function addItems($tag, $values)
    {
        if (!is_array($values)) {
            $values = explode(',', $values);
        }
        foreach ($values as $value) {
            html($tag, $this, $value);
        }
    }

    /****m* AndroHtml/addOptions
     *
     * NAME
     *    addOptions
     *
     * FUNCTION
     * The PHP Method addOptions takes an array of rows and
     *   creates on HTML OPTION object for each row.  These
     *   are added to the parent object, which is assumed to be
     *   an HTML SELECT object.
     *
     * INPUTS
     * array - an array of rows
     * string - name of column to use as value
     *   string - name of column to use as display
     *
     * SOURCE
     */
    public function addOptions($rows, $value, $desc)
    {
        foreach ($rows as $row) {
            $opt = $this->h('option', $row[$desc]);
            $opt->hp['value'] = $row[$value];
        }
    }

    /******/

    /****m* AndroHtml/setAsParent
     *
     * NAME
     *    setAsParent
     *
     * FUNCTION
     * The method setAsParent sets a flag for this AndroHtml element to work as parent.
     *
     * SOURCE
     */
    public function setAsParent()
    {
        $this->isParent = true;
    }

    /******/
    // Internal use only
    public function initPlugin()
    {
        $plugin = $this->hp['x6plugin'];
        $table = $this->hp['x6table'];
        jqDocReady("var plugin = x6.byId('{$this->hp['id']}');");
        jqDocReady("x6plugins.$plugin(plugin,plugin.id,'$table')");
    }

    /****m* AndroHtml/firstChild
     *
     * NAME
     *    firstChild
     *
     * FUNCTION
     * The method firstChild returns a reference to the first child html element in this AndroHtml object.
     *
     * RETURN VALUE
     * reference - reference to first child element
     *
     * SOURCE
     */
    public function firstChild()
    {
        if (count($this->children) == 0) {
            return null;
        } else {
            $retval = & $this->children[0];
            return $retval;
        }
    }

    /******/

    /****m* AndroHtml/lastChild
     *
     * NAME
     *    lastChild
     *
     * FUNCTION
     * The method lastChild returns a reference to the last child element in this AndroHtml object.
     *
     * RETURN VALUE
     * reference - reference to last child
     *
     * SOURCE
     */
    public function lastChild()
    {
        if (count($this->children) == 0) {
            return null;
        } else {
            $retval = & $this->children[count($this->children) - 1];
            return $retval;
        }
    }

    /******/

    /****m* AndroHtml/print_r
     *
     * NAME
     *    print_r
     *
     * FUNCTION
     * The method dumps a variable into the innerHTML of an
     *   and Andromeda HTML Object.
     *
     * SOURCE
     */
    public function printR($value)
    {
        ob_start();
        print_r($value);
        $pre = $this->h('pre', ob_get_clean());
        $pre->hp['class'] = 'border: 1px solid gray; background-color:white;
            color: black;';
    }

    /******/

    /****m* AndroHtml/bufferedRender
     *
     * NAME
     *    bufferedRender
     *
     * FUNCTION
     * The method bufferedRender rendered this AndroHtml object in a buffer, instead of directly outputing
     * it to the browser.
     *
     * SOURCE
     */
    public function bufferedRender($parentId = '', $singleQuotes = false)
    {
        ob_start();
        $this->render($parentId, $singleQuotes);
        return ob_get_clean();
    }

    /******/

    /****m* AndroHtml/render
     *
     * NAME
     *    render
     *
     * FUNCTION
     * The method render renders this AndroHtml object.  It builds
     *   all of the html code based on the objects attributes,
     *   children elements, parent elements, etc.  Render directly
     *   outputs all html out to the browser.  User bufferedRender
     *   to get the html as a string instead of outputting to the browser.
     *
     * INPUTS
     * string $parentId - parent id for this AndroHtml object
     ******/
    public function render($parentId = '', $singleQuotes = false, $x6wrapperPane = '')
    {
        global $AG;
        // Accept a parentId, maybe assign one to
        if ($parentId <> '') {
            $this->ap['xParentId'] = $parentId;
        }
        if ($this->isParent) {
            $parentId = a($this->hp, 'id', '');
            if ($parentId == '') {
                echo "Object has no id but wants to be parent";
                hprint_r($this);
                exit;
            }
        }
        // KFD 12/30/08, IE Compatibility.  All inputs, selects and
        //               so forth must have an ID.  This is actually
        //               due to jQuery returning strange items with
        //               the :input selector, and we can only distinguish
        //               real from bogus by looking for IDs
        if (in_array($this->htype, array('input', 'select', 'checkbox'))) {
            if (arr($this->hp, 'id', '') == '') {
                $id = rand(1000, 9999);
                while (isset($AG['id'][$id])) {
                    $id = rand(1000, 9999);
                }
                $this->hp['id'] = 'id_' . $id;
                $AG['id'][$id] = 1;
            }
        }
        // Set the x6 parent tab if exists
        if (arr($this->hp, 'x6plugin') == 'x6tabs') {
            $this->hp['x6wrapperPane'] = $x6wrapperPane;
        }
        // Before we render, we are going to take the code
        // snippets and generate top-level functions for them
        $twoparms = array('click', 'keypress', 'keyup', 'keydown');
        $snippet_id = a($this->hp, 'id');
        if ($snippet_id == '') {
            $snippet_id = 'snip_' . rand(1, 100000);
        }
        foreach ($this->code as $event => $snippet) {
            $fname = $snippet_id . '_' . $event;
            jqDocReady("window.$fname = $snippet");
            if (in_array($event, $twoparms)) {
                $this->hp['on' . $event] = "$fname(this,event)";
            } else {
                $this->hp['on' . $event] = "$fname(this)";
            }
        }
        foreach ($this->functions as $name => $snippet) {
            jqDocReady("x6.byId('{$this->hp['id']}').$name = " . $snippet);
        }
        // KFD 10/7/08 if data has been attached, send it as json
        if (isset($this->data)) {
            $js = "x6.byId('" . $this->hp['id'] . "').zData = " . json_encode($this->data);
            jqDocReady($js);
        }

        if ($this->autoFormat) {
            echo "\n<!-- ELEMENT ID " . $this->hp['id'] . " (BEGIN) -->";

            //echo "$indent\n<!-- ELEMENT ID ".$this->hp['id']." (BEGIN) -->";
        }
        $parms = '';
        if (count($this->classes) > 0) {
            $this->hp['class'] = implode(' ', $this->classes);
        }
        if (count($this->style) > 0) {
            $style = '';
            foreach ($this->style as $prop => $value) {
                $style.= "$prop: $value;";
            }
            $this->hp['style'] = $style;
        }
        $q = $singleQuotes ? "'" : '"';
        foreach ($this->hp as $parmname => $parmvalue) {
            if ($parmname == 'href') {
                $parmvalue = preg_replace('/&([a-zA-z])/', '&amp;$1', $parmvalue);
            }
            $parms.= "\n  $parmname=$q$parmvalue$q";
        }
        foreach ($this->ap as $parmname => $parmvalue) {
            $parms.= "\n  $parmname=$q$parmvalue$q";
        }
        echo "<" . $this->htype . ' ' . $parms . '>' . $this->innerHtml;
        foreach ($this->children as $child) {
            if (is_string($child)) {
                echo $child;
            } else {
                if (arr($this->hp, 'x6plugin') == 'x6tabsPane') {
                    $x6wrapperPane = $this->hp['id'];
                }
                $child->render($parentId, $singleQuotes, $x6wrapperPane);
            }
        }
        echo "</$this->htype \n>";
        if ($this->autoFormat) {
            echo "\n<!-- ELEMENT ID " . $this->hp['id'] . " (END) -->";

            //echo "$indent\n<!-- ELEMENT ID ".$this->hp['id']." (END) -->";
        }
    }
}
