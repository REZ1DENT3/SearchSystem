<?php

return array(
    'default' => array(

        //Supprted drivers are: apc, database, file, xcache
        'driver' => 'Apc',

        //Default liefetime for cached objects in seconds
        'default_lifetime' => 3600,

        //Cache directory for 'file' driver
        'cache_dir' => '/assets/cache/',

        //Database connection name for 'database' driver
        'connecton' => 'default'
    )
);