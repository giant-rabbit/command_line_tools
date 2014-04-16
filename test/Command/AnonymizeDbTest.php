<?php

class AnonymizeDbTest extends GR\TestCase\TestCase {

  protected function setup() {
    $this->gr = new \GR\GR() ;
  }
  
  public function testConstructorSetsDatabaseName() {
    $opts = array();
    $args = array('database_production');
    $cmd = new \GR\Command\AnonymizeDb($opts,$args);
    
    $this->assertEquals($cmd->get_database_name(), 'database_production');
  }
  
  public function testCheckDatabaseNameRejectsProd() {
    $opts = array();
    $args = array('database_production');
    $cmd = new \GR\Command\AnonymizeDb($opts,$args);
    
    $this->assertFalse($cmd->check_database_name());
  }
  
  public function testCheckDatabaseNameRejectsProdCaseInsensitive() {
    $opts = array();
    $args = array('DATABASE_PRODUCTION');
    $cmd = new \GR\Command\AnonymizeDb($opts,$args);
    
    $this->assertFalse($cmd->check_database_name());
  }
  
  public function testCheckDatabaseNameConfirmsNoStagOrDev() {
    $opts = array();
    $args = array('database_name');
    $cmd = new \GR\Command\AnonymizeDb($opts,$args);
    
    $this->assertEquals('CONFIRM',$cmd->check_database_name());
  }
  
  public function testCheckDatabaseNameReturnsOkWithStaging() {
    $opts = array();
    $args = array('database_staging');
    $cmd = new \GR\Command\AnonymizeDb($opts,$args);
    
    $this->assertEquals('OK',$cmd->check_database_name());
  }
  
  public function testCheckDatabaseNameReturnsOkWithDev() {
    $opts = array();
    $args = array('database_dev');
    $cmd = new \GR\Command\AnonymizeDb($opts,$args);
    
    $this->assertEquals('OK',$cmd->check_database_name());
  }
  
}