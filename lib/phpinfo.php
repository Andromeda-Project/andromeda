<?php
class phpinfo extends x_table2
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
