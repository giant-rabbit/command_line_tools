<?php

class ParserTest extends Gr\TestCase\TestCase {

  public function testParseWordpressConfig() {
    $path = TEST_ROOT . '/files/wordpress/wp-config.php' ;
    $parsed = \Gr\Utils\Parser::parse_wp_config($path) ;
    $this->assertEquals($parsed['constants']['DB_HOST'],     'wordpress_database_host', 'database host should equal `wordpress_database_host`') ;
    $this->assertEquals($parsed['constants']['DB_NAME'], 'wordpress_database_name', 'database name should equal `wordpress_database_name`') ;
    $this->assertEquals($parsed['constants']['DB_USER'], 'wordpress_database_user', 'database username should equal `wordpress_database_user`') ;
    $this->assertEquals($parsed['constants']['DB_PASSWORD'], 'wordpress_database_password', 'database name should equal `wordpress_database_password`') ;
    $this->assertEquals($parsed['constants']['AUTH_SALT'], 'test_value_auth_salt', 'auth_salt constant should equal `test_value_auth_salt`') ;
  }

  public function testParseDrupalConfig() {
    $path = TEST_ROOT . '/files/drupal/sites/default/settings.php' ;
    $parsed = \Gr\Utils\Parser::parse_drupal_settings($path) ;
    $this->assertEquals($parsed['databases'][0]['host'],     'drupal_database_host', 'database host should equal `drupal_database_host`') ;
    $this->assertEquals($parsed['databases'][0]['database'], 'drupal_database_name', 'database name should equal `drupal_database_name`') ;
    $this->assertEquals($parsed['databases'][0]['username'], 'drupal_database_user', 'database username should equal `drupal_database_user`') ;
    $this->assertEquals($parsed['databases'][0]['password'], 'drupal_database_password', 'database name should equal `drupal_database_password`') ;
  }
}