<?php
class PhpInfo extends XTable2
{
    public function main()
    {
        if (SessionGet('ADMIN', false)==false) {
            echo "Sorry, admins only";
        } else {
            hprint_r($_SERVER);
            phpinfo();
        }
    }
}
