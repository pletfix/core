<?php return array (
  'query' => '
            SELECT c.relname, d.description
            FROM pg_class AS c
            INNER JOIN pg_description AS d ON (d.objoid = c.oid AND d.objsubid = 0)
            INNER JOIN pg_namespace AS n ON c.relnamespace = n.oid
            WHERE c.relkind = \'r\'
            AND n.nspname = ?
            AND d.description > \'\'
        ',
  'bindings' => 
  array (
    0 => 'public',
  ),
  'result' => 
  array (
    0 => 
    array (
      'relname' => 'table1',
      'description' => 'A test table.',
    ),
  ),
);
