<?php return array (
  'query' => 'SELECT column_name, content FROM _comments WHERE table_name = ? AND column_name IS NOT NULL',
  'bindings' => 
  array (
    0 => 'table1',
  ),
  'result' => 
  array (
    0 => 
    array (
      'column_name' => 'array1',
      'content' => 'a rose is a rose (DC2Type:array)',
    ),
    1 => 
    array (
      'column_name' => 'float1',
      'content' => 'a rose is a rose',
    ),
    2 => 
    array (
      'column_name' => 'integer1',
      'content' => 'I am cool!',
    ),
    3 => 
    array (
      'column_name' => 'json1',
      'content' => '(DC2Type:json)',
    ),
    4 => 
    array (
      'column_name' => 'object1',
      'content' => '(DC2Type:object)',
    ),
    5 => 
    array (
      'column_name' => 'string1',
      'content' => 'lola',
    ),
  ),
);
