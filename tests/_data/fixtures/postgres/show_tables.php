<?php return array (
  'query' => '
            SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = ?
            AND table_name != \'geometry_columns\'
            AND table_name != \'spatial_ref_sys\'
            AND table_type != \'VIEW\'
            ORDER BY table_name
        ',
  'bindings' => 
  array (
    0 => 'public',
  ),
  'result' => 
  array (
    0 => 
    array (
      'table_name' => 'table1',
    ),
    1 => 
    array (
      'table_name' => 'table2',
    ),
  ),
);
