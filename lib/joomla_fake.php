<?php
/****c* Joomla-Compatibility/joomla_fake
 *
 * NAME
 * joomla_fake
 *
 * FUNCTION
 * Class needed by Joomla templates so they go into
 * normal mode.
 *
 * SOURCE
 */
// @codingStandardsIgnoreStart
class joomla_fake
{
    var $id = false;
    var $template_name = '';

    // KFD 2/25/08 added for
    var $_session = array();

    function getTemplate()
    {
        return $this->template_name;
    }
}
// @codingStandardsIgnoreEnd
