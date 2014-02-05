<?php

class RestoreLatestS3BackupTest extends GR\TestCase\TestCase {

  public function testGetEnvironmentDetectsWordpress() {
    chdir($this->wp_root) ;
    $cmd = new \GR\Command\RestoreLatestS3Backup() ;
    $env = $cmd->get_environment() ;
    $this->assertEquals('wordpress', $env, "get_environment should return 'wordpress'") ;
  }

  public function testGetEnvironmentDetectsDrupal() {
    chdir($this->drupal_root) ;
    $cmd = new \GR\Command\RestoreLatestS3Backup() ;
    $env = $cmd->get_environment() ;
    $this->assertEquals('drupal', $env, "get_environment should return 'drupal'") ;
  }
  
  public function testGetEnvironmentReturnsFalseIfNotWordpressOrDrupal() {
    $dir = TEST_ROOT . '/files' ;
    chdir($dir) ;
    $cmd = new \GR\Command\RestoreLatestS3Backup() ;
    $env = $cmd->get_environment() ;
    $this->assertFalse($env, "get_environment should return false") ;
  }
  
  public function testGetDatabaseCredentialsWordpress() {
    chdir($this->wp_root) ;
    $cmd = new \GR\Command\RestoreLatestS3Backup() ;
    $creds = $cmd->get_database_credentials() ;
    $this->assertEquals($creds['host'],     'wordpress_database_host', 'database host should equal `wordpress_database_host`') ;
    $this->assertEquals($creds['database'], 'wordpress_database_name', 'database name should equal `wordpress_database_name`') ;
    $this->assertEquals($creds['username'], 'wordpress_database_user', 'database username should equal `wordpress_database_user`') ;
    $this->assertEquals($creds['password'], 'wordpress_database_password', 'database name should equal `wordpress_database_password`') ;
  }

  public function testGetDatabaseCredentialsDrupal() {
    chdir($this->drupal_root) ;
    $cmd = new \GR\Command\RestoreLatestS3Backup() ;
    $creds = $cmd->get_database_credentials() ;
    $this->assertEquals($creds['host'],     'drupal_database_host', 'database host should equal `drupal_database_host`') ;
    $this->assertEquals($creds['database'], 'drupal_database_name', 'database name should equal `drupal_database_name`') ;
    $this->assertEquals($creds['username'], 'drupal_database_user', 'database username should equal `drupal_database_user`') ;
    $this->assertEquals($creds['password'], 'drupal_database_password', 'database name should equal `drupal_database_password`') ;
  }
  
  public function testFetchAwsCredentialsDrupal() {
    $this->markTestIncomplete() ;
  }

}
