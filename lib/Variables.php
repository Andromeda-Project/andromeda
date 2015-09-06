<?php
class Variables extends XTable2
{
    public function aLinksExtra($mode)
    {
        if ($mode<>'upd') {
            return array();
        } else {
            $link='gp_skey='.$this->row['skey']
            .'&gp_cache=1';
            return array(
                "<a href=\"javascript:formPostString('$link')\">"
                ."Force Cache Reload"
                ."</a>"
            );
        }
    }
    
    public function main()
    {
        if (gpExists('gp_xajax')) {
            $sq="UPDATE variables
                    SET variable_value = ".SQLFC(gp('varval'))."
                  WHERE variable = ".SQLFC(gp('variable'));
            SQL($sq);
        }
        if (gpExists('gp_cache')) {
            OptionGet('X');
        }
        if (gpExists('gp_xajax')) {
            return;
        }
        parent::main();
    }
}
