<?php

class RestoreLatestS3BackupTest extends Gr\TestCase\TestCase {

  protected function setup() {
    $this->cmd = new \Gr\Command\RestoreLatestS3Backup() ;
  }
    
  public function testGetEnvironmentDetectsWordpress() {
    $env = $this->cmd->get_environment($this->wp_root) ;
    $this->assertEquals('wordpress', $env, "get_environment should return 'wordpress'") ;
  }
  
  public function testGetEnvironmentDetectsDrupal() {
    $env = $this->cmd->get_environment($this->drupal_root) ;
    $this->assertEquals('drupal', $env, "get_environment should return 'drupal'") ;
  }
  
  public function testGetEnvironmentReturnsFalseIfNotWordpressOrDrupal() {
    $dir = TEST_ROOT . '/files' ;
    $env = $this->cmd->get_environment($dir) ;
    $this->assertFalse($env, "get_environment should return false") ;
  }
  
  public function testGetDatabaseCredentialsWordpress() {
    $this->cmd->set_working_directory($this->wp_root) ;
    $creds = $this->cmd->get_database_credentials() ;
    $this->assertEquals($creds['host'],     'wordpress_database_host', 'database host should equal `wordpress_database_host`') ;
    $this->assertEquals($creds['database'], 'wordpress_database_name', 'database name should equal `wordpress_database_name`') ;
    $this->assertEquals($creds['username'], 'wordpress_database_user', 'database username should equal `wordpress_database_user`') ;
    $this->assertEquals($creds['password'], 'wordpress_database_password', 'database name should equal `wordpress_database_password`') ;
  }

  public function testGetDatabaseCredentialsDrupal() {
  
  }
}