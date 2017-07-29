<?php return array (
  'query' => '
            SELECT name 
            FROM sysobjects 
            WHERE type = \'U\' 
            AND name != \'sysdiagrams\' 
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
