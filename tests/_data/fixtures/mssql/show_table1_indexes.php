<?php return array (
  'query' => '
            SELECT 
                i.name, 
                co.[name] AS column_name,
                i.is_unique, 
                i.is_primary_key
            FROM sys.indexes i 
            INNER JOIN sys.index_columns ic ON ic.object_id = i.object_id  AND ic.index_id = i.index_id
            INNER JOIN sys.columns co ON co.object_id = i.object_id AND co.column_id = ic.column_id
            INNER JOIN sys.tables t ON t.object_id = i.object_id
            WHERE t.is_ms_shipped = 0 AND t.[name] = ?
            ORDER BY i.[name], ic.is_included_column, ic.key_ordinal
        ',
  'bindings' => 
  array (
    0 => 'table1',
  ),
  'result' => 
  array (
    0 => 
    array (
      'name' => 'PK__table1__3213E83F3C69FB99',
      'column_name' => 'id',
      'is_unique' => '1',
      'is_primary_key' => '1',
    ),
    1 => 
    array (
      'name' => 'table1_integer1_unique',
      'column_name' => 'integer1',
      'is_unique' => '1',
      'is_primary_key' => '0',
    ),
    2 => 
    array (
      'name' => 'table1_string1_string2_index',
      'column_name' => 'string1',
      'is_unique' => '0',
      'is_primary_key' => '0',
    ),
    3 => 
    array (
      'name' => 'table1_string1_string2_index',
      'column_name' => 'string2',
      'is_unique' => '0',
      'is_primary_key' => '0',
    ),
  ),
);
