<?php

define('TEST_ROOT',__DIR__) ;
foreach (glob(__DIR__ . "/../src/lib/*.php") as $file) { require_once $file; }
$loader = require_once __DIR__ . '/../vendor/autoload.php' ;


echo "Reading config...\n";
$config = json_decode(file_get_contents("./config.json"));

if (empty($config)) {
  $err  = "\nYou are missing or have misconfigured your config.json file, ";
  $err .= "which specifies your testing databases. Please see config.example.json ";
  $err .= "for example usage\n\n";
  die($err);
}

echo "Loading Drupal DB...\n";

$cnf = $config->databases->drupal ;
$cmd = "mysql -u {$cnf->username} -p{$cnf->password} {$cnf->database} < ./files/sql/drupal.sql";
$s = \GR\Shell::command($cmd);
echo $s[0];
echo "  ...done.\n\n";
