<?php

namespace App;

/**
 * Pixie dependency container
 *
 * @property-read \PHPixie\Cookie $cookie Cookie module
 * @property-read \PHPixie\Session $session Session module
 * @property-read \PHPixie\DB $db Database module
 * @property-read \PHPixie\ORM $orm ORM module
 * @property-read \PHPixie\Haml $haml HAML module
 */
class Pixie extends \PHPixie\Pixie
{

    protected $modules = array(
        'db' => '\PHPixie\DB',
        'orm' => '\PHPixie\ORM',
        'haml' => '\PHPixie\Haml',
        'cache' => '\PHPixie\Cache'
    );

    public function __construct()
    {
    }

    protected function after_bootstrap()
    {
        //Whatever code you want to run after bootstrap is done.
        date_default_timezone_set('Europe/Moscow');
        mb_internal_encoding("UTF-8");
        mb_regex_encoding("UTF-8");
    }

}
