<?php return array (
  'query' => '
            SELECT c.name AS column_name, CAST(cd.value AS VARCHAR(255)) AS description
            FROM sysobjects AS t
            INNER JOIN syscolumns AS c ON c.id = t.id
            INNER JOIN sys.extended_properties AS cd ON (cd.major_id = c.id AND cd.minor_id = c.colid)
            WHERE cd.name = \'MS_Description\'
            AND t.type = \'u\'
            AND t.name = ?
        ',
  'bindings' => 
  array (
    0 => 'table1',
  ),
  'result' => 
  array (
    0 => 
    array (
      'column_name' => 'integer1',
      'description' => 'I am cool!',
    ),
    1 => 
    array (
      'column_name' => 'unsigned1',
      'description' => '(DC2Type:unsigned)',
    ),
    2 => 
    array (
      'column_name' => 'float1',
      'description' => 'a rose is a rose',
    ),
    3 => 
    array (
      'column_name' => 'string1',
      'description' => 'lola',
    ),
    4 => 
    array (
      'column_name' => 'array1',
      'description' => 'a rose is a rose (DC2Type:array)',
    ),
    5 => 
    array (
      'column_name' => 'json1',
      'description' => '(DC2Type:json)',
    ),
    6 => 
    array (
      'column_name' => 'object1',
      'description' => '(DC2Type:object)',
    ),
  ),
);
