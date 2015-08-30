
<?php
class AndroHtmlTableController extends AndroHtml
{
    public function __construct($table_id)
    {
        $this->htype          = 'div';
        $this->hp['x6plugin'] = 'tableController';
        $this->hp['x6table']  = $table_id;
        $this->hp['id']       = 'tc_'.$table_id;
        $this->hp['class']    = 'table table-striped table-condensed table-bordered table-hover';

        $this->ap['xPermSel'] = ddUserPerm($table_id, 'sel');
        $this->ap['xPermIns'] = $this->permResolve('ins');
        $this->ap['xPermUpd'] = $this->permResolve('upd');
        $this->ap['xPermDel'] = $this->permResolve('del');

        $this->initPlugin();
    }

    public function permResolve($perm)
    {
        $tryfirst  = ddUserPerm($this->hp['x6table'], $perm);
        $dd        = ddTable($this->hp['x6table']);
        $trysecond = arr($dd, 'ui'.$perm, 'Y');

        return $trysecond == 'N'?'N':$tryfirst;
    }
}
