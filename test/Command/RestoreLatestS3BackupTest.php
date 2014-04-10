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
    $config = $this->get_config();
    $this->assertEquals($creds['host'],     $config->databases->wordpress->hostname, 'database host should equal config value') ;
    $this->assertEquals($creds['database'], $config->databases->wordpress->database, 'database name should equal config value') ;
    $this->assertEquals($creds['username'], $config->databases->wordpress->username, 'database username should equal config value') ;
    $this->assertEquals($creds['password'], $config->databases->wordpress->password, 'database password should equal config value') ;
  }

  public function testGetDatabaseCredentialsDrupal() {
    chdir($this->drupal_root) ;
    $cmd = new \GR\Command\RestoreLatestS3Backup() ;
    $creds = $cmd->get_database_credentials() ;
    $config = $this->get_config();
    $this->assertEquals($creds['host'],     $config->databases->drupal->hostname, 'database host should equal config value') ;
    $this->assertEquals($creds['database'], $config->databases->drupal->database, 'database name should equal config value') ;
    $this->assertEquals($creds['username'], $config->databases->drupal->username, 'database username should equal config value') ;
    $this->assertEquals($creds['password'], $config->databases->drupal->password, 'database password should equal config value') ;
  }
  
  public function testFetchAwsCredentialsDrupal() {
    $this->markTestIncomplete() ;
  }

}
