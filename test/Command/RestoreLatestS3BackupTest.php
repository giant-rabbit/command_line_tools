<?php

class RestoreLatestS3BackupTest extends GR\TestCase\TestCase {

  public function testGetEnvironmentDetectsWordpress() {
    $this->markTestIncomplete(); // Wordpress not supported yet, and instantiating the command object will throw an error
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
  
  /**
   * @expectedException Exception
   * 
   * Doc block is enough for phpunit to know that an exception should be thrown.
   * No explicit assertions are necessary in the test
   */
  public function testThrowsExceptionIfNotWordpressOrDrupal() {
    $dir = TEST_ROOT . '/files' ;
    chdir($dir) ;
    $cmd = new \GR\Command\RestoreLatestS3Backup() ;
  }
  
  public function testGetDatabaseCredentialsWordpress() {
    $this->markTestIncomplete(); // Wordpress not supported yet, and instantiating the command object will throw an error
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
  
  public function testGetDatabaseConnectionDrupal() {
    chdir($this->drupal_root);
    $cmd = new \GR\Command\RestoreLatestS3Backup();
    $db = $cmd->get_database_connection();
    $this->assertInstanceOf('PDO', $db);
  }
  
  public function testFetchAwsCredentialsDrupal() {
    chdir($this->drupal_root);
    $cmd = new \GR\Command\RestoreLatestS3Backup();
    $fetched = $cmd->fetch_aws_credentials();
    $this->assertTrue($fetched);
    $this->assertEquals($cmd->opts['id'], 'test_access_key_id');
    $this->assertEquals($cmd->opts['secret'], 'test_secret_access_key');
    $this->assertEquals($cmd->opts['bucket'], 'test_bucket_name');
  }

  public function testFetchAwsCredentialsDrupalNoTrailingSlash() {
    chdir($this->drupal_root);
    $cmd = new \GR\Command\RestoreLatestS3Backup();
    $db = $cmd->get_database_connection();
    $location = 'https://test_access_key_id:test_secret_access_key@s3.amazonaws.com/test_bucket_name';
    $stm = $db->prepare("UPDATE backup_migrate_destinations SET location=:location");
    $stm->bindParam(':location',$location);
    $stm->execute();

    $cmd = new \GR\Command\RestoreLatestS3Backup();
    $fetched = $cmd->fetch_aws_credentials();
    $this->assertTrue($fetched);
    $this->assertEquals($cmd->opts['id'], 'test_access_key_id');
    $this->assertEquals($cmd->opts['secret'], 'test_secret_access_key');
    $this->assertEquals($cmd->opts['bucket'], 'test_bucket_name');
  }

  public function testRunRestoresDbAndFiles() {
    chdir($this->drupal_root);
    $cmd = $this->getMock("\GR\Command\RestoreLatestS3Backup", array('restore_database', 'restore_files', 'get_bucket_contents'));
    $cmd->opts['no-prompts'] = true;
    $cmd->expects($this->any())->method('get_bucket_contents')->will($this->returnValue(array(
      array(
        'name' => 'test.mysql.gz',
        'time' => '1'
      ),
      array(
        'name' => 'test.tar.gz',
        'time' => '1'
      )
    )));
    
    $cmd->expects($this->once())->method('restore_database');
    $cmd->expects($this->once())->method('restore_files');
    $cmd->run();
  }

  public function testRunWithExFilesOnlyRestoresDb() {
    chdir($this->drupal_root);
    $cmd = $this->getMock("\GR\Command\RestoreLatestS3Backup", array('restore_database', 'restore_files', 'get_bucket_contents'));
    $cmd->opts['no-prompts'] = true;
    $cmd->opts['exclude-files'] = true;
    $cmd->expects($this->any())->method('get_bucket_contents')->will($this->returnValue(array(
      array(
        'name' => 'test.mysql.gz',
        'time' => '1'
      ),
      array(
        'name' => 'test.tar.gz',
        'time' => '1'
      )
    )));
    
    $cmd->expects($this->once())->method('restore_database');
    $cmd->expects($this->exactly(0))->method('restore_files');
    $cmd->run();
  }
}
