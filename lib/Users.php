<?php
class Users extends XTable2
{
    public function __construct()
    {
        global $AG;
        parent::__construct();
        if (ArraySafe($AG, 'flag_pwmd5', 'N')=='Y') {
            unset($this->table['flat']['member_password']);
        }
    }
}
