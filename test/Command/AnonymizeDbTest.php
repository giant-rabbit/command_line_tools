<?php

class AnonymizeDbTest extends GR\TestCase\TestCase {

  protected function setup() {
    $this->gr = new \GR\GR() ;
  }
  
  public function testConstructorSetsDatabaseName() {
    $opts = array('type' => 'drupal');
    $args = array('database_production');
    $cmd = new \GR\Command\AnonymizeDb($opts,$args);
    
    $this->assertEquals($cmd->get_database(), 'database_production');
  }
  
  public function testCheckDatabaseNameRejectsProd() {
    $opts = array('type' => 'drupal');
    $args = array('database_production');
    $cmd = new \GR\Command\AnonymizeDb($opts,$args);
    
    $this->assertFalse($cmd->check_database_name());
  }
  
  public function testCheckDatabaseNameRejectsProdCaseInsensitive() {
    $opts = array('type' => 'drupal');
    $args = array('DATABASE_PRODUCTION');
    $cmd = new \GR\Command\AnonymizeDb($opts,$args);
    
    $this->assertFalse($cmd->check_database_name());
  }
  
  public function testCheckDatabaseClobber() {
    $opts = array('type' => 'drupal','clobber'=>true);
    $args = array('DATABASE_PRODUCTION');
    $cmd = new \GR\Command\AnonymizeDb($opts,$args);
    
    $this->assertEquals('OK',$cmd->check_database_name());
  }
  
  public function testCheckDatabaseNameConfirmsNoStagOrDev() {
    $opts = array('type' => 'drupal');
    $args = array('database_name');
    $cmd = new \GR\Command\AnonymizeDb($opts,$args);
    
    $this->assertEquals('CONFIRM',$cmd->check_database_name());
  }
  
  public function testCheckDatabaseNameReturnsOkWithStaging() {
    $opts = array('type' => 'drupal');
    $args = array('database_staging');
    $cmd = new \GR\Command\AnonymizeDb($opts,$args);
    
    $this->assertEquals('OK',$cmd->check_database_name());
  }
  
  public function testCheckDatabaseNameReturnsOkWithDev() {
    $opts = array('type' => 'drupal');
    $args = array('database_dev');
    $cmd = new \GR\Command\AnonymizeDb($opts,$args);
    
    $this->assertEquals('OK',$cmd->check_database_name());
  }
  
  /**
   * @expectedException Exception
   * 
   * Doc block is enough for phpunit to know that an exception should be thrown.
   * No explicit assertions are necessary in the test
   */
  public function testForRequiredValues() {
    $opts = array();
    $args = array();
    $cmd = new \GR\Command\AnonymizeDb($opts,$args);
    $cmd->run();
  }
  
  public function testHostOptionOverridesDefault() {
    $opts = array('host'=>'test','type'=>'drupal');
    $args = array('database_dev');
    $cmd = new \GR\Command\AnonymizeDb($opts,$args);
    $this->assertEquals('test',$cmd->get_host());
  }

  public function testGetsDbCredentialsDrupal() {
    chdir($this->drupal_root);
    $opts = array();
    $args = array();
    $cmd = new \GR\Command\AnonymizeDb($opts,$args);
    $config = $this->get_config();
    $this->assertNotEmpty($cmd->get_host());
    $this->assertNotEmpty($cmd->get_database());
    $this->assertNotEmpty($cmd->get_username());
    $this->assertNotEmpty($cmd->get_password());
    $this->assertEquals($cmd->get_host(),     $config->databases->drupal->hostname, 'database host should equal config value') ;
    $this->assertEquals($cmd->get_database(), $config->databases->drupal->database, 'database name should equal config value') ;
    $this->assertEquals($cmd->get_username(), $config->databases->drupal->username, 'database username should equal config value') ;
    $this->assertEquals($cmd->get_password(), $config->databases->drupal->password, 'database password should equal config value') ;
  }

  public function testGetsDbCredentialsWordpress() {
    chdir($this->wp_root);
    $opts = array();
    $args = array();
    $cmd = new \GR\Command\AnonymizeDb($opts,$args);
    $config = $this->get_config();
    $this->assertNotEmpty($cmd->get_host());
    $this->assertNotEmpty($cmd->get_database());
    $this->assertNotEmpty($cmd->get_username());
    $this->assertNotEmpty($cmd->get_password());
    $this->assertEquals($cmd->get_host(),     $config->databases->wordpress->hostname, 'database host should equal config value') ;
    $this->assertEquals($cmd->get_database(), $config->databases->wordpress->database, 'database name should equal config value') ;
    $this->assertEquals($cmd->get_username(), $config->databases->wordpress->username, 'database username should equal config value') ;
    $this->assertEquals($cmd->get_password(), $config->databases->wordpress->password, 'database password should equal config value') ;
  }
  
  public function testCommandLineArgsTakePrecendenceOverEnvironmentConfig() {
    chdir($this->drupal_root);
    $opts = array('username' => 'overridden');
    $args = array();
    $cmd = new \GR\Command\AnonymizeDb($opts,$args);
    $config = $this->get_config();
    $this->assertEquals($cmd->get_username(), 'overridden') ;
  }
  
  /**
   * @expectedException GR\Exception\MissingEnvironmentException
   */
  public function testCommandRequiresType() {
    $dir = realpath($this->drupal_root . "/..");
    $opts = array();
    $args = array('database_dev');
    $cmd = new \GR\Command\AnonymizeDb($opts,$args);
  }
  
  /**
   * @expectedException GR\Exception\InvalidArgumentException
   */
  public function testValidArgumentsForType() {
    $dir = realpath($this->drupal_root . "/..");
    $opts = array('username'=>'foo', 'password'=>'bar', 'domain'=>'test_domain', 'alias'=>'test_alias', 'type' => 'bif');
    $args = array('database_dev');
    $cmd = new \GR\Command\AnonymizeDb($opts,$args);
    $cmd->run();
  }
}