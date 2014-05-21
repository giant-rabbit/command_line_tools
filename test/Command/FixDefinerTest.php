<?php

/**
 * This is an example test case. Just like the command you
 * created, copy it with the name <Command>Test.php and run
 * `chmod u+w <Command>Test.php` to in order to edit it. You
 * should have at minimum a test for each of the methods in 
 * your command's class
 *
 * For more information about writing php unit tests with PhpUnit, 
 * see http://phpunit.de/manual/current/en/writing-tests-for-phpunit.html
 *
 * To run ALL unit tests, cd into the `test` directory and run `phpunit .`
 * To run just this test, cd into `test` and run `phpunit path/to/this/file`
 */
class FixDefinerTest extends GR\TestCase\TestCase {

  protected function setup() {
    $this->gr = new \GR\GR() ;
  }

  static $expected_contents = <<<EOS
/*!50003 CREATE*/  /*!50003 TRIGGER civicrm_website_after_insert after insert ON civicrm_website FOR EACH ROW BEGIN
/*!50003 CREATE*/  /*!50003 TRIGGER civicrm_website_after_insert after insert ON civicrm_website FOR EACH ROW BEGIN
EOS;
  
  /**
   * Test names must be in the form `testWhatever`. That is, lowercase `test`
   * followed by CamelCase descriptor, often the name of the method being tested.
   */
  public function testFixDefinerOutput() {
    chdir($this->misc_root) ;
    @unlink('fix-definer-definer-fixed.sql');
    $cmd = "gr fix-definer fix-definer.sql" ;
    \GR\Shell::command($cmd);
    $this->assertFileExists('fix-definer-definer-fixed.sql');
    $contents = file_get_contents('fix-definer-definer-fixed.sql');
    $this->assertEquals(static::$expected_contents, $contents);
  }

  public function testFixDefinerGzip() {
    chdir($this->misc_root) ;
    @unlink('fix-definer-definer-fixed.sql');
    @unlink('fix-definer-definer-fixed.sql.gz');
    $cmd = "gr fix-definer fix-definer.sql.gz" ;
    \GR\Shell::command($cmd);
    $this->assertFileExists('fix-definer-definer-fixed.sql.gz');
    \GR\Shell::command("gunzip fix-definer-definer-fixed.sql.gz");
    $contents = file_get_contents('fix-definer-definer-fixed.sql');
    $this->assertEquals(static::$expected_contents, $contents);
  }

  public function testOutputOption() {
    chdir($this->misc_root) ;
    @unlink('fix-definer-different-output.sql');
    $cmd = "gr fix-definer --output fix-definer-different-output.sql fix-definer.sql" ;
    \GR\Shell::command($cmd);
    $this->assertFileExists('fix-definer-different-output.sql');
  }
  
}
