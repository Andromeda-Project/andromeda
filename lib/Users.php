<?php
class Users extends XTable2
{
    public function __construct()
    {
        parent::__construct();
        if (ArraySafe($GLOBALS['AG'], 'flag_pwmd5', 'N')=='Y') {
            unset($this->table['flat']['member_password']);
        }
    }
}
