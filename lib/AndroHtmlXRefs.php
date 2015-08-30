<?php
/****c* HTML Generation/AndroHtmlXRefs
 *
 * NAME
 *    AndroHtmlXRefs
 *
 * FUNCTION
 *   The PHP class AndroHtmlxRefs generates a tabbed list of child
 *   tables to the named parent.  Only child tables with the
 *   "x6xref" property set are included.  The only supported value
 *   for "x6xref" at this time is "checkboxes".
 *
 *   The object is a subclass of AndroHtml, and supports all of its
 *   methods such as addChild, addClass, etc.
 *
 *   IMPORTANT! If there are no qualifying child tables, the object
 *   is still created and returned, but it will have "display: none"
 *   and will effectively not exist for the user.
 *
 * INPUTS
 *   * string table_id - the parent table
 *   * number height - the total height available for the display
 *
 * RETURNS
 *   object - AndroHtmlXRefs object.
 *
 ******
 */
class AndroHtmlXRefs extends AndroHtml
{
    public $firstFocus = false;

    public function __construct($table_id, $height = 300)
    {
        // Extreme basics for child tables.
        $this->htype = 'div';
        $this->hp['x6table'] = $table_id;
        $this->hp['xCount'] = 0;
        // First bit of business is to run through and find
        // out if we actually have any kids.
        $dd = ddTable($table_id);

        $kids = array();
        $atts = array();
        foreach ($dd['fk_children'] as $table_kid => $info) {
            if (arr($info, 'x6xref', '') <> '') {
                $kids[$table_kid] = $info['x6xref'];
                $atts[] = "$table_kid:{$info['x6xref']}";
            }
        }
        // If no kids, set ourselves to be invisible
        if (count($kids) == 0) {
            $this->hp['style'] = 'display: none;';
            return;
        }
        $this->hp['xCount'] = count($kids);

        $options = array('x6profile' => 'x6xrefs', 'x6table' => $table_id, 'styles' => array('overflow-y' => 'scroll'));
        $tabs = $this->addTabs($table_id . '_xrefs', $height, $options);
        $tabs->ul->hp['kids'] = implode("|", $atts);
        // If we are still here, we have at least one kid.  Let's
        // put in a tab bar and start adding the kids.
        foreach ($kids as $kid => $x) {
            $pane = $tabs->addTab($dd['fk_children'][$kid]['description']);
        }
    }
}
