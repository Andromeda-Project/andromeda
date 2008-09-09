<?php
class userssimple extends x_table2 {
    function main() {
        if(gpExists('gp_posted')) return $this->mainDoIt();
        
        # The basic idea here is to get all user-group assignments
        # from the old instance, and prompt the user
        $groups = SQL_AllRows("select * from zdd.groups",'group_id');
        $groupsx= SQL_AllRows(
            "Select distinct group_id from usersxgroups"
            ,'group_id'
        );
        
        $html = html('div');
        $html->h('h1','User Migration');
        $html->h('p','Use this program after you have migrated a database
            from one server to another.  It will create all users and
            put them into the correct groups.'
        );
        $html->h('p','<B>This works only for "simple" password systems.</b>');
            
        
        $html->h('h3','Group Reconciliation');
        $html->h('p','Please tell me how to re-assign groups:');
        
        $table =$html->h('table');
        $tbody =$table->h('tbody');
        $tr    =$tbody->h('tr');
        $tr->h('Anybody In this Group:');
        $tr->h('Put into this group:');
        foreach($groupsx as $groupx=>$x) {
            $tr = $tbody->h('tr');
            $td = $tr->h('td',$groupx);
            $td = $tr->h('td','&nbsp;&nbsp;&nbsp;&nbsp;');
            if(isset($groups[$groupx])) {
                $input = html('input');
                $input->hp['value'] = $groupx;
                $input->hp['readonly'] = true;
                $input->hp['style'] = 'border: 0';
            }
            else {
                $input = html('select');
                foreach($groups as $group=>$x) {                    
                    $option = $input->h('option',$group);
                    $option->hp['value'] = $group;
                }
            }
            $input->hp['name'] = 'grp_'.$groupx;
            $td = $tr->h('td');
            $td->addChild($input);
        }
        
        $html->hidden('gp_page','userssimple');
        $html->hidden('gp_posted',1);
        
        $html->br(3);
        $but=$html->h('input');
        $but->hp['type'] = 'submit';
        $but->hp['value'] = 'Run Now';
        
        $html->br(2);
        $p=$html->h('p'
            ,'<b>Please give the program up to five minutes to run.</p>'
        );
        $p->hp['style'] = 'color: red';
        
        $html->render();
    }
    
    function mainDoit() {
        # Take the list of group assignments and reslot
        # them into kills and changes.
        $graw  = aFromgp('grp_');
        $gsame = array();
        $gchg  = array();
        foreach($graw as $from=>$to) {
            if($from==$to) {
                $gsame[] = "'$to'";
            }
            else {
                $gchg[$from] = $to;
            }
        }
        
        # Step 1, make sure all users exist.  Pull the ones
        #         that don't and create them
        $users = SQL_AllRows("
            select user_id,member_password from users
             where COALESCE(member_password,'') <> ''
               AND not exists (
                   select rolname from pg_roles
                    where rolname = users.user_id::name
                    )"
        );
        echo "<br/>Re-creating ".count($users)." users.";
        foreach($users as $user) {
            $pwd = $user['member_password'];
            SQL("create role {$user['user_id']} login password '$pwd'");
        }
        
        # Step 2, for all assignments that do not change,
        #         explicitly grant the role
        $slist = implode(',',$gsame);
        $assigns = SQL_AllRows(
            "Select user_id,group_id 
               from usersxgroups
              WHERE group_id in ($slist)"
        );
        $count=0;
        foreach($assigns as $assign) {
            $count++;
            SQL("grant {$assign['group_id']} to {$assign['user_id']}");
        }
        echo "<br/>$count users had existing permissions re-established";
        errorsClear();
        
        # Step 3, for all assignments that change, 
        #         copy rows in usersxgroups, which also
        #         creates the role assignment
        foreach($gchg as $from=>$to) {
            $sql="insert into usersxgroups (user_id,group_id)
                 select user_id,'$to' FROM usersxgroups x
                 where group_id = '$from'
                   AND user_id in (Select rolname::varchar from pg_roles)
                   and not exists (
                       select * from usersxgroups x 
                        where user_id = x.user_id
                          AND group_id= '$to'
                   )";
             SQL($sql); 
        }
        echo "<br/>Migrated permissions for ".count($gchg)." groups";
        
        # Step 4, Delete all defunct user-group assignments
        foreach($gchg as $from=>$to) {
            SQL("Delete from usersxgroups where group_id = '$from'");
        }
        echo "<br/>Deleted old user-group rows for ".count($gchg)." groups";
        
        # Step 5, delete all defunct groups
        echo "<br/>Deleted ".count($gchg)." groups from old database";
        foreach($gchg as $from=>$to) {
            SQL("Delete from permxtables where group_id = '$from'");
            SQL("Delete from uimenugroups where group_id = '$from'");
            SQL("Delete from permxmodules where group_id = '$from'");
            SQL("Delete from groups where group_id = '$from'");
        }
        
    }
}
?>
