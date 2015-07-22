<?php

namespace GR;

class DrupalTest extends \PHPUnit_Framework_TestCase {

  protected $drupal_root = "files/drupal";

  public function testGetDatabaseCredentials() {
    chdir($this->drupal_root) ;
    $creds = Drupal::get_database_credentials() ;
    $this->assertEquals($creds['host'],     'drupal_database_host', 'database host should equal `drupal_database_host`') ;
    $this->assertEquals($creds['database'], 'drupal_database_name', 'database name should equal `drupal_database_name`') ;
    $this->assertEquals($creds['username'], 'drupal_database_user', 'database username should equal `drupal_database_user`') ;
    $this->assertEquals($creds['password'], 'drupal_database_password', 'database name should equal `drupal_database_password`') ;
  } 
}

