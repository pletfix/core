<?php return array (
  'query' => '
            SELECT
              t.name AS table_name,
              CAST(td.value AS VARCHAR(255)) AS description
            FROM sysobjects AS t
            INNER JOIN sys.extended_properties AS td ON (td.major_id = t.id AND td.minor_id = 0)
            WHERE td.name = \'MS_Description\'
            AND t.type = \'u\'
        ',
  'bindings' => 
  array (
  ),
  'result' => 
  array (
    0 => 
    array (
      'table_name' => 'table1',
      'description' => 'A test table.',
    ),
  ),
);
