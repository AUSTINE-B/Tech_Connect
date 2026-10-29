<?php
// Copy to config.local.php and set your XAMPP MySQL credentials.
return [
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'techconnect',
    'user' => 'root',
    'password' => '',
    // Keep payment simulation disabled except on a local demo installation.
    'dev_payments' => true,
];
