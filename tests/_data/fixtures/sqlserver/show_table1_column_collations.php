<?php return array (
  'query' => '
            SELECT c.name, c.collation_name 
            FROM sys.columns AS c
            JOIN sys.objects AS t ON c.object_id = t.object_id
            WHERE t.type = \'U\' 
            AND t.name = ?
            AND c.collation_name IS NOT NULL
        ',
  'bindings' => 
  array (
    0 => 'table1',
  ),
  'result' => 
  array (
    0 => 
    array (
      'name' => 'string1',
      'collation_name' => 'Latin1_General_CI_AS',
    ),
    1 => 
    array (
      'name' => 'string2',
      'collation_name' => 'Latin1_General_CS_AS',
    ),
    2 => 
    array (
      'name' => 'text1',
      'collation_name' => 'Latin1_General_CS_AS',
    ),
    3 => 
    array (
      'name' => 'array1',
      'collation_name' => 'Latin1_General_CI_AS',
    ),
    4 => 
    array (
      'name' => 'json1',
      'collation_name' => 'Latin1_General_CI_AS',
    ),
    5 => 
    array (
      'name' => 'object1',
      'collation_name' => 'Latin1_General_CI_AS',
    ),
  ),
);
