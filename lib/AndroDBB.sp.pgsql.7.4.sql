/* ================================================================== *\
   (C) Copyright 2005 by Secure Data Software, Inc.
   This file is part of Andromeda
   
   Andromeda is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   Andromeda is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with Andromeda; if not, write to the Free Software
   Foundation, Inc., 51 Franklin St, Fifth Floor,
   Boston, MA  02110-1301  USA 
   or visit http://www.gnu.org/licenses/gpl.html
\* ================================================================== */

/* ============================================================== *\
 * This is a single file that contains all server-side code       *
 * required to build an Andromeda database                        *
 * ============================================================== *
 * This is for Andromeda Version 0.1                              *
 * Developed on PostgreSQL 7.4.3                                  *
 * Also applicable w/o changes to PostgreSQL 8.0.x                *
\* ============================================================== */

CREATE OR REPLACE FUNCTION zdd.Table_Sequencer() RETURNS void AS
$$
DECLARE
	rowcount integer := 1;
	lnSeq integer := 1;
BEGIN
	UPDATE zdd.tables_c set table_seq = 0;
	
	DELETE FROM zdd.table_deps;
	INSERT INTO zdd.table_deps 
		(table_id_par,table_id_chd)
		SELECT table_id_par, table_id 
		   FROM zdd.tabfky_c 
		   WHERE zdd.tabfky_c.nocolumns <> 'Y'
			  AND zdd.tabfky_c.table_id <> zdd.tabfky_c.table_id_par;
	UPDATE zdd.tables_c set table_seq = -1 
		FROM zdd.table_deps f 
		WHERE table_id = f.table_id_chd ;

	while rowcount > 0 LOOP
		UPDATE zdd.tables_c set table_seq = lnSeq
        	  FROM (SELECT t1.table_id_chd 
                          FROM zdd.table_deps t1 
                          JOIN zdd.tables_c t2 ON t1.table_id_par = t2.table_id
                         GROUP BY t1.table_id_chd
                        HAVING MIN(t2.table_seq) >= 0) fins
	         WHERE zdd.tables_c.table_id = fins.table_id_chd
        	   AND zdd.tables_c.table_seq = -1;

		lnSeq := lnSeq + 1;
		GET DIAGNOSTICS rowcount = ROW_COUNT;
	END LOOP;
	
	RETURN;
END;
$$
LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION zdd.column_sequencer()
  RETURNS void AS
$$
DECLARE
	rowcount integer := 1;
	lnSeq integer := 1;
BEGIN
	DELETE FROM zdd.column_seqs;
	INSERT INTO zdd.column_seqs
		(table_id,column_id,sequence)
		SELECT table_id,column_id,-1 FROM zdd.Column_deps
		UNION SELECT table_dep,column_dep,-1 FROM zdd.column_deps;
	
	UPDATE zdd.column_seqs set sequence = 0 
	  WHERE NOT EXISTS (Select table_id 
				FROM zdd.column_deps a
				 WHERE a.table_id = zdd.column_seqs.table_id
				   AND a.column_id = zdd.column_seqs.column_id
               AND a.table_id = a.table_dep
                                  );

	while rowcount > 0 LOOP
		UPDATE zdd.column_seqs set sequence = lnSeq
        	  FROM (SELECT t1.table_id,t1.column_id
                          FROM zdd.column_deps t1
                          JOIN zdd.column_seqs t2 
			    ON t1.table_dep = t2.table_id 
			   AND t1.column_dep = t2.column_id
                         WHERE t1.table_id = t1.table_dep
                         GROUP BY t1.table_id,t1.column_id
                        HAVING MIN(t2.sequence) >= 0) fins
	         WHERE zdd.column_seqs.table_id = fins.table_id
		   AND zdd.column_seqs.column_id = fins.column_id
        	   AND zdd.column_seqs.sequence = -1;

		lnSeq := lnSeq + 1;
		GET DIAGNOSTICS rowcount = ROW_COUNT;
	END LOOP;
	RETURN;
END;
$$
LANGUAGE 'plpgsql' VOLATILE;

/* ============================================================== *\
 * These are work-a-day functions to ease automation              *
\* ============================================================== */

CREATE OR REPLACE FUNCTION andro_dow(timestamp) RETURNS char(5) AS 
$$
SELECT CASE WHEN EXTRACT(DOW FROM $1)=1 THEN 'SUN' 
            WHEN EXTRACT(DOW FROM $1)=2 THEN 'MON' 
            WHEN EXTRACT(DOW FROM $1)=3 THEN 'TUE' 
            WHEN EXTRACT(DOW FROM $1)=4 THEN 'WED' 
            WHEN EXTRACT(DOW FROM $1)=5 THEN 'THU' 
            WHEN EXTRACT(DOW FROM $1)=6 THEN 'FRI' ELSE 'SAT' END;
$$
LANGUAGE SQL;

/* ============================================================== *\
 * These two functions work together to speed up aggregates       *
 * They are required only because postgres does not expose        *
 *   modified data to per-statement triggers, making those        *
 *   triggers useless.  These use perl's %_SHARED hash            *
 *   to run up totals and then commit only once                   *
\* ============================================================== */

/* ------- clear outbound changes from a table   -------- */
/* ------- called per-statement before           -------- */
create or replace function AggInit(varchar) returns int as 
$BODY$
   $table_out=$_[0];
   #elog(NOTICE,"Clearing $table_out");
   # zap out any existing values going out from this table
   if( defined $_SHARED{$table_out}) {
      delete $_SHARED{$table_out};
   }
   return 0;
$BODY$
language plperl SECURITY DEFINER; 

/* ------- Register row-by-row changes to values -------- */
create or replace function 
   AggRegister(varchar, varchar, varchar, varchar, numeric, numeric) 
   returns int as 
$BODY$
   # CAPTURE VARIABLES, EXIT IMMEDIATELY IF NO CHANGE
   ($table_out, $table_in, $key, $colname, $new, $old) = @_;
   if ($new == $old) { return 0; }

   if (! defined $_SHARED{$table_out}{$table_in}{$key}{$colname} ) {
      $_SHARED{$table_out}{$table_in}{$key}{$colname}=0; 
   }
   #elog(NOTICE,"$table_out --> $table_in  $key  $colname  +$new -$old");
   $_SHARED{$table_out}{$table_in}{$key}{$colname} += ($new - $old);
   return 0;
$BODY$
language plperl SECURITY DEFINER; 

/* ------- Commit changes to values -------- */
CREATE OR REPLACE FUNCTION aggcommit(varchar, varchar, varchar)
  RETURNS int4 AS
$BODY$
   $table_out=$_[0];
   $table_in =$_[1];
   @cols = split /,/ , $_[2];
   if (defined $_SHARED{$table_out}{$table_in}) {
      while(($key,$colvals ) = each(%{ $_SHARED{$table_out}{$table_in} })) {
         # Determine the where clause based on key value
         @where = ();
         $pos   = 0;
         foreach $col (@cols) {
           ($colname,$coltype,$collen)=split /:/ , $col;
           $keyval = substr($key,$pos,$collen);
           $keyval =~ s/\'/\'\'/;
           $pos += $collen;
           if($coltype eq 'N') {
               push @where, "$colname = $keyval";
           }
           #elseif($coltype eq 'D') {
           #    ($colname1,$coname2)=split /;/ , $colname;
           #    push @where, "'$keyval' between $colname1 AND $colname2";
           #}
           else {
               $keyval =~ s/^\s+//;
               $keyval =~ s/\s+$//;
               push @where, "$colname = '$keyval'";
           }
         }
         $sql_where = join ' AND ' , @where;

         # Now execute for every column modified by this key
         @aset = ( );
         while (($colname,$diff) = each(%{ $colvals })) {
            push @aset, " $colname = $colname + ($diff) ";
         }
         $sql_set = join ', ' , @aset;
   
         $qu="UPDATE $table_in SET $sql_set WHERE $sql_where ";
         #elog(NOTICE,$qu);
         spi_exec_query($qu);
         
         delete $_SHARED{$table_out}{$table_in}{$key};
      }
   }
   return 0;
$BODY$
LANGUAGE 'plperl' VOLATILE SECURITY DEFINER;

