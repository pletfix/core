<?php return [
    'PRIMARY'                      => ['name' => 'PRIMARY',                      'columns' => ['integer1'],           'unique' => true,  'primary' => true],
    'table1_string1_unique'        => ['name' => 'table1_string1_unique',        'columns' => ['string1'],            'unique' => true,  'primary' => false],
    'table1_string2_string3_index' => ['name' => 'table1_string2_string3_index', 'columns' => ['string2', 'string3'], 'unique' => false, 'primary' => false],
];