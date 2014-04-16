<?php

define('TEST_ROOT',__DIR__) ;
foreach (glob(__DIR__ . "/../src/lib/*.php") as $file) { require_once $file; }
$loader = require_once __DIR__ . '/../vendor/autoload.php' ;


echo "* Reading config...\n";
$config = json_decode(file_get_contents("./config.json"));

if (empty($config)) {
  $err  = "\nYou are missing or have misconfigured your config.json file, ";
  $err .= "\nwhich specifies your testing databases. Please see config.example.json ";
  $err .= "\nfor example usage\n\n";
  $err .= "If this is not a development environment, you may disregard this message.";
  die($err);
} else {
  write_config_files($config);
}


echo "* Loading Drupal DB...";

$cnf = $config->databases->drupal ;
$cmd = "mysql -u {$cnf->username} -p{$cnf->password} {$cnf->database} < ./files/sql/drupal.sql";
$s = \GR\Shell::command($cmd);
echo $s[0];
echo "done.\n\n";





function write_config_files($config) {
  
  // Wordpress
  $db = $config->databases->wordpress;
  $config_tpl = TEST_ROOT . '/files/wordpress/wp-config.template.php';
  $config_file = TEST_ROOT . '/files/wordpress/wp-config.php';
  $config_contents = file_get_contents($config_tpl);
  $config_contents = str_ireplace('{{hostname}}', $db->hostname, $config_contents);
  $config_contents = str_ireplace('{{database}}', $db->database, $config_contents);
  $config_contents = str_ireplace('{{username}}', $db->username, $config_contents);
  $config_contents = str_ireplace('{{password}}', $db->password, $config_contents);
  file_put_contents($config_file, $config_contents);

  // Drupal
  $db = $config->databases->drupal;
  $config_tpl = TEST_ROOT . '/files/drupal/sites/default/settings.template.php';
  $config_file = TEST_ROOT . '/files/drupal/sites/default/settings.php';
  $config_contents = file_get_contents($config_tpl);
  $config_contents = str_ireplace('{{hostname}}', $db->hostname, $config_contents);
  $config_contents = str_ireplace('{{database}}', $db->database, $config_contents);
  $config_contents = str_ireplace('{{username}}', $db->username, $config_contents);
  $config_contents = str_ireplace('{{password}}', $db->password, $config_contents);
  file_put_contents($config_file, $config_contents);

}