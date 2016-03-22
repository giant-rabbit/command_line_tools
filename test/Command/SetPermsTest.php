<?php

require_once('vendor/autoload.php');

use \GR\Path;
use \GR\Command\SetPerms;
use \Mockery;

class SetPermsTest extends \PHPUnit_Framework_TestCase {
  function tearDown() {
    Mockery::close();
  }

  function set_perms_options() {
    return array(
      'directory' => Path::join(__DIR__, '..', 'files', 'drupal'),
    );
  }

  function test_apply_exception_rules() {
    $process_user = posix_getpwuid(posix_geteuid());
    $user_name = $process_user['name'];
    $command_mock = Mockery::mock('alias:GR\Shell');
    $drupal_base_path = realpath(Path::join(__DIR__, '..', 'files', 'drupal'));
    $command_mock->shouldReceive('command')->with("chown -R www-data:www-data $drupal_base_path/foo", Mockery::any());
    $command_mock->shouldReceive('command')->with("find $drupal_base_path/foo -type d -print0 | xargs -0 chmod 2775", Mockery::any())->once();
    $command_mock->shouldReceive('command')->with("find $drupal_base_path/foo -type f -print0 | xargs -0 chmod 0664", Mockery::any())->once();
    $command_mock->shouldReceive('command')->with("find $drupal_base_path/foo -type f | wc -l")->once();
    $command_mock->shouldReceive('command')->with("chown -R $user_name:giantrabbit $drupal_base_path/foo bar bif", Mockery::any())->once();
    $command_mock->shouldReceive('command')->with("find $drupal_base_path/foo bar bif -type d -print0 | xargs -0 chmod 2775", Mockery::any())->once();
    $command_mock->shouldReceive('command')->with("find $drupal_base_path/foo bar bif -type f -print0 | xargs -0 chmod 0664", Mockery::any())->once();
    $command_mock->shouldReceive('command')->with("find $drupal_base_path/foo bar bif -type f | wc -l")->once();
    $command_mock->shouldReceive('command')->with("chown -R $user_name:giantrabbit $drupal_base_path/set-perms.txt", Mockery::any())->once();
    $command_mock->shouldReceive('command')->with("chmod 0664 $drupal_base_path/set-perms.txt", Mockery::any())->once();
    $set_perms = new SetPerms($this->set_perms_options());
    $set_perms->apply_exception_rules();
  }
 
  function test_load_exception_rules() {
    $set_perms = new SetPerms($this->set_perms_options());
    $exception_rules = $set_perms->load_exception_rules();
    $expected_rules = array(
      array('www-data', 'foo'),
      array('user', 'foo bar bif'),
      array('user', 'set-perms.txt'),
    );
    $this->assertEquals($expected_rules, $exception_rules);
  }
}
