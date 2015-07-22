<?php

namespace GR;

class WordpressTest extends \PHPUnit_Framework_TestCase {

  protected $wp_root = "files/wordpress";

  public function testGetDatabaseCredentials() {
    chdir($this->wp_root) ;
    $creds = Wordpress::get_database_credentials() ;
    $this->assertEquals($creds['host'],     'wordpress_database_host', 'database host should equal `wordpress_database_host`') ;
    $this->assertEquals($creds['database'], 'wordpress_database_name', 'database name should equal `wordpress_database_name`') ;
    $this->assertEquals($creds['username'], 'wordpress_database_user', 'database username should equal `wordpress_database_user`') ;
    $this->assertEquals($creds['password'], 'wordpress_database_password', 'database name should equal `wordpress_database_password`') ;
  }
}

