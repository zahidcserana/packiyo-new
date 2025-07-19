<?php

$lengthMenu = env('DATATABLE_LENGTH_MENU', '10,25,50,100,1000');
$lengthMenuPersistable = env('DATATABLE_LENGTH_MENU_PERSISTABLE', '10,25,50,100');

return [
    'length_menu' => array_map('intval', explode(',', $lengthMenu)),
    'length_menu_persistable' => array_map('intval', explode(',', $lengthMenuPersistable))
];
