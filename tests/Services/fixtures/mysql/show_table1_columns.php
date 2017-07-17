<?php return array (
  'query' => 'SHOW FULL COLUMNS FROM `table1`',
  'bindings' => 
  array (
  ),
  'result' => 
  array (
    0 => 
    array (
      'Field' => 'id',
      'Type' => 'int(10) unsigned',
      'Collation' => NULL,
      'Null' => 'NO',
      'Key' => 'PRI',
      'Default' => NULL,
      'Extra' => 'auto_increment',
      'Privileges' => 'select,insert,update,references',
      'Comment' => '',
    ),
    1 => 
    array (
      'Field' => 'small1',
      'Type' => 'smallint(6)',
      'Collation' => NULL,
      'Null' => 'YES',
      'Key' => '',
      'Default' => '33',
      'Extra' => '',
      'Privileges' => 'select,insert,update,references',
      'Comment' => '',
    ),
    2 => 
    array (
      'Field' => 'integer1',
      'Type' => 'int(11)',
      'Collation' => NULL,
      'Null' => 'NO',
      'Key' => 'UNI',
      'Default' => '-44',
      'Extra' => '',
      'Privileges' => 'select,insert,update,references',
      'Comment' => 'I am cool!',
    ),
    3 => 
    array (
      'Field' => 'unsigned1',
      'Type' => 'int(10) unsigned',
      'Collation' => NULL,
      'Null' => 'YES',
      'Key' => '',
      'Default' => '55',
      'Extra' => '',
      'Privileges' => 'select,insert,update,references',
      'Comment' => '',
    ),
    4 => 
    array (
      'Field' => 'bigint1',
      'Type' => 'bigint(20)',
      'Collation' => NULL,
      'Null' => 'YES',
      'Key' => '',
      'Default' => '66',
      'Extra' => '',
      'Privileges' => 'select,insert,update,references',
      'Comment' => '',
    ),
    5 => 
    array (
      'Field' => 'numeric1',
      'Type' => 'decimal(10,0)',
      'Collation' => NULL,
      'Null' => 'NO',
      'Key' => '',
      'Default' => '1234567890',
      'Extra' => '',
      'Privileges' => 'select,insert,update,references',
      'Comment' => '',
    ),
    6 => 
    array (
      'Field' => 'numeric2',
      'Type' => 'decimal(4,1)',
      'Collation' => NULL,
      'Null' => 'YES',
      'Key' => '',
      'Default' => '123.4',
      'Extra' => '',
      'Privileges' => 'select,insert,update,references',
      'Comment' => '',
    ),
    7 => 
    array (
      'Field' => 'float1',
      'Type' => 'double',
      'Collation' => NULL,
      'Null' => 'NO',
      'Key' => '',
      'Default' => '3.14',
      'Extra' => '',
      'Privileges' => 'select,insert,update,references',
      'Comment' => 'a rose is a rose',
    ),
    8 => 
    array (
      'Field' => 'string1',
      'Type' => 'varchar(255)',
      'Collation' => 'utf8_unicode_ci',
      'Null' => 'NO',
      'Key' => 'MUL',
      'Default' => 'Panama',
      'Extra' => '',
      'Privileges' => 'select,insert,update,references',
      'Comment' => 'lola',
    ),
    9 => 
    array (
      'Field' => 'string2',
      'Type' => 'varchar(50)',
      'Collation' => 'latin1_general_cs',
      'Null' => 'YES',
      'Key' => '',
      'Default' => NULL,
      'Extra' => '',
      'Privileges' => 'select,insert,update,references',
      'Comment' => '',
    ),
    10 => 
    array (
      'Field' => 'text1',
      'Type' => 'text',
      'Collation' => 'latin1_general_cs',
      'Null' => 'YES',
      'Key' => '',
      'Default' => NULL,
      'Extra' => '',
      'Privileges' => 'select,insert,update,references',
      'Comment' => '',
    ),
    11 => 
    array (
      'Field' => 'guid1',
      'Type' => 'varchar(36)',
      'Collation' => 'utf8_unicode_ci',
      'Null' => 'YES',
      'Key' => '',
      'Default' => NULL,
      'Extra' => '',
      'Privileges' => 'select,insert,update,references',
      'Comment' => '(DC2Type:guid)',
    ),
    12 => 
    array (
      'Field' => 'binary1',
      'Type' => 'varbinary(2)',
      'Collation' => NULL,
      'Null' => 'YES',
      'Key' => '',
      'Default' => NULL,
      'Extra' => '',
      'Privileges' => 'select,insert,update,references',
      'Comment' => '',
    ),
    13 => 
    array (
      'Field' => 'binary2',
      'Type' => 'varbinary(3)',
      'Collation' => NULL,
      'Null' => 'YES',
      'Key' => '',
      'Default' => NULL,
      'Extra' => '',
      'Privileges' => 'select,insert,update,references',
      'Comment' => '',
    ),
    14 => 
    array (
      'Field' => 'blob1',
      'Type' => 'blob',
      'Collation' => NULL,
      'Null' => 'YES',
      'Key' => '',
      'Default' => NULL,
      'Extra' => '',
      'Privileges' => 'select,insert,update,references',
      'Comment' => '',
    ),
    15 => 
    array (
      'Field' => 'boolean1',
      'Type' => 'tinyint(1)',
      'Collation' => NULL,
      'Null' => 'YES',
      'Key' => '',
      'Default' => '1',
      'Extra' => '',
      'Privileges' => 'select,insert,update,references',
      'Comment' => '',
    ),
    16 => 
    array (
      'Field' => 'date1',
      'Type' => 'date',
      'Collation' => NULL,
      'Null' => 'YES',
      'Key' => '',
      'Default' => NULL,
      'Extra' => '',
      'Privileges' => 'select,insert,update,references',
      'Comment' => '',
    ),
    17 => 
    array (
      'Field' => 'datetime1',
      'Type' => 'datetime',
      'Collation' => NULL,
      'Null' => 'YES',
      'Key' => '',
      'Default' => NULL,
      'Extra' => '',
      'Privileges' => 'select,insert,update,references',
      'Comment' => '',
    ),
    18 => 
    array (
      'Field' => 'timestamp1',
      'Type' => 'timestamp',
      'Collation' => NULL,
      'Null' => 'NO',
      'Key' => '',
      'Default' => 'CURRENT_TIMESTAMP',
      'Extra' => '',
      'Privileges' => 'select,insert,update,references',
      'Comment' => '',
    ),
    19 => 
    array (
      'Field' => 'time1',
      'Type' => 'time',
      'Collation' => NULL,
      'Null' => 'YES',
      'Key' => '',
      'Default' => NULL,
      'Extra' => '',
      'Privileges' => 'select,insert,update,references',
      'Comment' => '',
    ),
    20 => 
    array (
      'Field' => 'array1',
      'Type' => 'text',
      'Collation' => 'utf8_unicode_ci',
      'Null' => 'YES',
      'Key' => '',
      'Default' => NULL,
      'Extra' => '',
      'Privileges' => 'select,insert,update,references',
      'Comment' => 'a rose is a rose (DC2Type:array)',
    ),
    21 => 
    array (
      'Field' => 'json1',
      'Type' => 'text',
      'Collation' => 'utf8_unicode_ci',
      'Null' => 'YES',
      'Key' => '',
      'Default' => NULL,
      'Extra' => '',
      'Privileges' => 'select,insert,update,references',
      'Comment' => '(DC2Type:json)',
    ),
    22 => 
    array (
      'Field' => 'object1',
      'Type' => 'text',
      'Collation' => 'utf8_unicode_ci',
      'Null' => 'YES',
      'Key' => '',
      'Default' => NULL,
      'Extra' => '',
      'Privileges' => 'select,insert,update,references',
      'Comment' => '(DC2Type:object)',
    ),
  ),
);
