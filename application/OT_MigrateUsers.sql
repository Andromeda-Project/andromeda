-- FIRST YOU MUST DO THIS:
-- In the node manager, build the app/instance
-- drop the database, recreate it
-- restore the backup into the new database
-- execute the three sets of commands below to migrate the users

-- capture existing records
select * into x_usersxgroups from usersxgroups;
--select * from x_usersxgroups

-- disable triggers and purge out old values
alter table usersxgroups disable trigger all;
delete from usersxgroups;
alter table usersxgroups enable trigger all;

-- Transfer them over
insert into usersxgroups (user_id,group_id)
 SELECT user_id,'emr_' || SUBSTRING(group_id from 6) from x_usersxgroups