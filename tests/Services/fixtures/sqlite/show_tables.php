<?php return array (
  'query' => '
            SELECT name 
            FROM sqlite_master 
            WHERE type = \'table\'
            AND name <> \'sqlite_sequence\' 
            AND name <> \'_comments\' 
            ORDER BY name
        ',
  'bindings' => 
  array (
  ),
  'result' => 
  array (
    0 => 
    array (
      'name' => 'table1',
    ),
    1 => 
    array (
      'name' => 'table2',
    ),
  ),
);
