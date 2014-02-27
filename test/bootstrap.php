<?php

define('TEST_ROOT',__DIR__) ;
foreach (glob(__DIR__ . "/../src/helpers/*.php") as $file) { require_once $file; }
$loader = require_once __DIR__ . '/../vendor/autoload.php' ;
