<?php

$db_name = 'search';
$db_login = 'search';
$db_password = 'ExeK4vqG5NQxX873';

$c = $_SERVER['HTTP_HOST'];
if ($c == 'searchsystem') {
    $db_name = 'habr-search';
    $db_login = 'root';
    $db_password = '';
}

return array(

    'default' => array(
	
        'user' => $db_login,
        'password' => $db_password,
        'driver' => 'PDO',
        //'Connection' is required if you use the PDO driver
        'connection' => "mysql:host=localhost;dbname=$db_name",

        // 'db' and 'host' are required if you use Mysql driver
        'db' => $db_name,
        'host' => 'localhost',

    )

);
