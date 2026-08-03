<?php
declare(strict_types=1);

function work360_config_example(): array
{
    return [
        'db' => [
            'server' => 'localhost\\SQLEXPRESS',
            'database' => 'moghare360_ERP',
            'trusted' => true,
            'username' => '',
            'password' => '',
        ],
        'app' => [
            'name' => 'Work360',
            'base_path' => '/moghare360/work360',
            'session_name' => 'WORK360SESSID',
            'csrf_key' => 'work360_csrf',
        ],
    ];
}
