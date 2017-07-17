<?php return array (
  'query' => '
            SELECT
              i.relname as index_name,
              a.attname as column_name,
              ix.indisunique,
              ix.indisprimary
            FROM pg_index AS ix
            INNER JOIN pg_class AS t ON t.oid = ix.indrelid
            INNER JOIN pg_class AS i ON i.oid = ix.indexrelid
            INNER JOIN pg_namespace AS n ON t.relnamespace = n.oid
            INNER JOIN pg_attribute AS a ON (a.attrelid = t.oid AND a.attnum = ANY(ix.indkey))
            WHERE t.relkind = \'r\'
            AND n.nspname = ?
            AND t.relname = ?
            ORDER BY i.relname
        ',
  'bindings' => 
  array (
    0 => 'public',
    1 => 'table1',
  ),
  'result' => 
  array (
    0 => 
    array (
      'index_name' => 'table1_integer1_unique',
      'column_name' => 'integer1',
      'indisunique' => true,
      'indisprimary' => false,
    ),
    1 => 
    array (
      'index_name' => 'table1_pkey',
      'column_name' => 'id',
      'indisunique' => true,
      'indisprimary' => true,
    ),
    2 => 
    array (
      'index_name' => 'table1_string1_string2_index',
      'column_name' => 'string1',
      'indisunique' => false,
      'indisprimary' => false,
    ),
    3 => 
    array (
      'index_name' => 'table1_string1_string2_index',
      'column_name' => 'string2',
      'indisunique' => false,
      'indisprimary' => false,
    ),
  ),
);
