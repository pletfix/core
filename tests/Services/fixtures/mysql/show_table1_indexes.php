<?php return array (
  'query' => 'SHOW INDEX FROM `table1`',
  'bindings' => 
  array (
  ),
  'result' => 
  array (
    0 => 
    array (
      'Table' => 'table1',
      'Non_unique' => 0,
      'Key_name' => 'PRIMARY',
      'Seq_in_index' => 1,
      'Column_name' => 'id',
      'Collation' => 'A',
      'Cardinality' => 0,
      'Sub_part' => NULL,
      'Packed' => NULL,
      'Null' => '',
      'Index_type' => 'BTREE',
      'Comment' => '',
      'Index_comment' => '',
    ),
    1 => 
    array (
      'Table' => 'table1',
      'Non_unique' => 0,
      'Key_name' => 'table1_integer1_unique',
      'Seq_in_index' => 1,
      'Column_name' => 'integer1',
      'Collation' => 'A',
      'Cardinality' => 0,
      'Sub_part' => NULL,
      'Packed' => NULL,
      'Null' => '',
      'Index_type' => 'BTREE',
      'Comment' => '',
      'Index_comment' => '',
    ),
    2 => 
    array (
      'Table' => 'table1',
      'Non_unique' => 1,
      'Key_name' => 'table1_string1_string2_index',
      'Seq_in_index' => 1,
      'Column_name' => 'string1',
      'Collation' => 'A',
      'Cardinality' => 0,
      'Sub_part' => NULL,
      'Packed' => NULL,
      'Null' => '',
      'Index_type' => 'BTREE',
      'Comment' => '',
      'Index_comment' => '',
    ),
    3 => 
    array (
      'Table' => 'table1',
      'Non_unique' => 1,
      'Key_name' => 'table1_string1_string2_index',
      'Seq_in_index' => 2,
      'Column_name' => 'string2',
      'Collation' => 'A',
      'Cardinality' => 0,
      'Sub_part' => NULL,
      'Packed' => NULL,
      'Null' => 'YES',
      'Index_type' => 'BTREE',
      'Comment' => '',
      'Index_comment' => '',
    ),
  ),
);
