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
    public $id = false;
    public $template_name = '';

    // KFD 2/25/08 added for
    public $_session = array();

    public function getTemplate()
    {
        return $this->template_name;
    }
}
// @codingStandardsIgnoreEnd

