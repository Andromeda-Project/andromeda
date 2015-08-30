<?php
/****c* HTML Generation/AndroHtmlTabs
 *
 * NAME
 *    AndroHtmlTabs
 *
 * FUNCTION
 *   The class AndroHtmlTabBar is used to create on-screen Tab Bars
 *   without having to manually create all of the various HTML elements.
 *
 *   The object is a subclass of AndroHtml, and supports all of its
 *   methods such as addChild, addClass, etc.
 *
 *
 *
 * EXAMPLE
 *   A typical usage example might be something like this:
 *
 *      <?php
 *      # Create a top-level div
 *      $div = html('div');
 *      $div->h('h1','Here is the title');
 *
 *      # now put in a tab bar with 3 tabs
 *      $tabBar = new AndroHtmlTabBar('id');
 *      $div->addChild($tabBar);
 *      $tabBar->addTab('Users');  // this is the caption *and* the id
 *      $tabBar->addTab('Groups');
 *      $tabBar->addTab('Tables');
 *
 *      # Now you can access the tabs like this:
 *      $tabBar->tabs['Users']->h('h2','Welcome to the users tab.');
 *      # ...and so on
 *      ?>
 *
 * SEE ALSO
 * addChild
 *
 ******
 */
class AndroHtmlTabs extends AndroHtml
{

    /****v* AndroHtml/AndroHtmlTabs
     *
     * NAME
     *    tabs
     *
     * FUNCTION
     *   The class property tabs is an associative array that
     *   can be used to add HTML to the various tabs in the
     *   tab bar.
     *
     * EXAMPLE
     *   Normal usage looks like this:
     *      <?php
     *      $tabBar = new AndroHtmlTabBar('id');
     *      $tabBar->addTab('Users');  // this is the caption *and* the id
     *      $tabBar->tabs['Users']->h('h2','Hello! Welcome to users tab');
     *      ?>
     *
     ******/
    public $tabs = array();

    public function __construct($id = '', $height = 500, $options = array())
    {
        $this->htype = 'div';
        $this->height = $height;
        $this->options = $options;

        $this->ul = $this->h('ul');
        $this->ul->hp['x6plugin'] = 'tabs';
        $this->ul->hp['id'] = $id;
        $this->tabs = array();
        // Set various options on the tab itself
        foreach ($options as $option => $value) {
            $this->ul->hp[$option] = $value;
        }
        if (!isset($this->ul->hp['x6table'])) {
            $this->ul->hp['x6table'] = '*';
        }
        // Register the script to turn on the tabs
        jqDocReady(" \$('#$id').tabs(); ");
        // Now initialize the plugin.
        $this->ul->initPlugin();
    }

    /****m* AndroHtmlTabs/addTab
     *
     * NAME
     *    AndroHtmlTabs.addTab
     *
     * FUNCTION
     *   This PHP class method addTab is the basic method
     *   of the AndroHtmlTabs class,
     *   call this function once for each tab you wish to add
     *   to your tabbar.
     *
     * INPUTS
     *   - $caption string, becomes both caption and ID
     *
     * RETURNS
     *   - AndroHtml, reference to a div where you can put content
     *   for the new tab.
     *
     ******/
    public function addTab($caption, $disable = false)
    {
        // Make an index, and add it in.
        $index = $this->ul->hp['id'] . '-' . (count($this->tabs) + 1);
        // Get the offset, if they gave one, for setting
        // CTRL+Number key activation
        $offset = arr($this->ul->hp, 'xOffset', 0);
        $key = $offset + count($this->tabs) . ': ';
        if ($key > 9) {
            $key = '';
        }
        // Make a style setting just for this element, otherwise
        // jquery ui clobbers the height setting
        $this->h('style', "#$index { height: {$this->height}px;}");
        // First easy thing is to do the li entry
        $inner = "<a href='#$index'><span>$key$caption</span></a></li>";
        $this->ul->h('li', $inner);
        // Next really easy thing to do is make a div, give it
        // the id, and return it
        $div = $this->h('div');
        $div->hp['xParentId'] = $this->ul->hp['id'];
        $div->hp['x6plugin'] = 'x6tabsPane';
        $this->tabs[] = $div;
        $div->hp['id'] = "$index";
        if (isset($this->options['styles'])) {
            $div->hp['style'] = '';
            foreach ($this->options['styles'] as $rule => $value) {
                $div->hp['style'].= "$rule: $value";
            }
        }
        return $div;
    }
}
