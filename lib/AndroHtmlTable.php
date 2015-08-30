<?php
/****c* HTML Generation/AndroHtmlTable
 *
 * NAME
 *    AndroHtmlTable
 *
 * FUNCTION
 *   The PHP class AndroHtmlTable models an HTML Table element, with
 *   special properties and methods for easily manipulating rows
 *   and cells.
 *
 *   The object is a subclass of AndroHtml, and supports all of its
 *   methods such as addChild, addClass, etc.
 *
 *
 ******
 */
class AndroHtmlTable extends AndroHtml
{

    /****v* AndroHtmlTable/cells
     *
     * NAME
     *    cells
     *
     * FUNCTION
     *   The PHP property AndroHtmlTable::cells is a two-dimensional
     *   numeric-indexed array of all cells added to the table.
     *
     *   This array is only updated for cells created with the
     *   methods tr and td.  If you use $table->h('td') or similar
     *   methods the resulting cell will not be in the array.
     *
     ******
     */
    public $lastBody = false;

    public $lastRow = false;

    public $lastCell = false;

    public function __construct()
    {
        $this->htype = 'table';
    }

    public function tbody()
    {
        $x = $this->h('tbody');
        $this->bodies[] = $x;
        $this->lastBody = $x;
        return $x;
    }

    public function thead()
    {
        $x = $this->h('thead');
        $this->bodies[] = $x;
        $this->lastBody = $x;
        return $x;
    }

    public function tr()
    {
        if (!$this->lastBody) {
            $this->tbody();
        }
        $this->lastRow = $this->lastBody->h('tr');
        return $this->lastRow;
    }

    public function td($mixed = '', $tag = 'td')
    {
        // Now get us a row if we don't have one
        if (!$this->lastRow) {
            $this->tr();
        }
        $td = $this->lastRow->h('td', $mixed);
        // And finally add
        /*
        while (count($adds)>0) {
            $value = array_shift($adds);
            $this->lastRow->h($tag,$value);
        }
        */
        return $td;
    }

    public function th($mixed = '')
    {
        return $this->td($mixed, 'th');
    }
}
