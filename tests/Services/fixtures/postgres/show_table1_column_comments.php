<?php return array (
  'query' => '
            SELECT c.column_name, pgd.description
            FROM pg_statio_all_tables AS st
            INNER JOIN pg_description pgd ON pgd.objoid = st.relid
            INNER JOIN information_schema.columns c ON (pgd.objsubid = c.ordinal_position AND c.table_schema = st.schemaname and c.table_name = st.relname)
            WHERE c.table_schema = ? 
            AND st.relname = ?
            AND pgd.description > \'\'
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
      'column_name' => 'blob1',
      'description' => '(DC2Type:blob)',
    ),
    5 => 
    array (
      'column_name' => 'array1',
      'description' => 'a rose is a rose (DC2Type:array)',
    ),
    6 => 
    array (
      'column_name' => 'object1',
      'description' => '(DC2Type:object)',
    ),
  ),
);
