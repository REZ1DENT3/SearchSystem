<?php

return array(
    'default' => array(
	
        'user' => 'root',
        'password' => 'root',
        'driver' => 'PDO',
        //'Connection' is required if you use the PDO driver
        'connection' => "mysql:host=localhost;dbname=habr-search",

        // 'db' and 'host' are required if you use Mysql driver
        'db' => 'habr-search',
        'host' => 'localhost',

    )

);
