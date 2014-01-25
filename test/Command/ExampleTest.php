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
class ExampleTest extends Gr\TestCase\TestCase {

  protected function setup() {
    $this->gr = new \Gr\Gr() ;
  }
  
  /**
   * Test names must be in the form `testWhatever`. That is, lowercase `test`
   * followed by CamelCase descriptor, often the name of the method being tested.
   */
  public function testWillAlwaysPass() {
    $this->assertTrue(true) ;
  }
  
}