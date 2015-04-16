<?php

$root = dirname(__DIR__);
$loader = require $root . '/vendor/autoload.php';
$loader->add('', $root . '/classes/');

//$str = "---- hello world INFORMATION-VISUALIZATION-SECOND-INTERACTIVE-TECHNOLOGIES";
//$str .= " " . md5($str);
//var_dump((new \App\SearchEngine(new \App\Pixie))->get_words($str));
//die;

$pixie = new \App\Pixie;
$pixie->bootstrap($root)->handle_http_request();