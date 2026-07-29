<?php
/**
 * Inventory360 example config — copy to private path, do not commit secrets.
 */
return [
    'db' => [
        'server' => 'localhost\\SQLEXPRESS',
        'database' => 'MOGHARE360_StockCenter',
        'trusted' => true,
        'username' => '',
        'password' => '',
    ],
    'app' => [
        'name' => 'Inventory360',
        'base_path' => '/moghare360/inventory360',
        'session_name' => 'INV360SESSID',
        'csrf_key' => 'inv360_csrf',
    ],
];
