<?php 

namespace GR\Command ;
use GR\Command as Command ;


class UnitTest extends Command {

  const DESCRIPTION = "Runs the test suite on the GR Tools or subset thereof" ;

  const HELP_TEXT = <<<EOT

Run with no arguments, this command will run all unit tests. 

Run with a path to a unit test file or directory (relative to the test directory) it will run only the relevant tests.

* Usage:
  --------

  gr <options> unit-test
    runs all tests
  
  gr <options> unit-test Command
    runs all tests in the test/Command Directory 
  
  gr <options> unit-test Command/RestoreBackup
    runs the tests in the (fictitious) file test/Command/RestoreBackup.php
EOT;

  
  public function __construct($opts,$args) {
    parent::__construct($opts,$args) ;
  }
  
  public function get_phpunit_options() {
    $opts = array(
      'tap',
      'testdox',
      'colors',
      'stop-on-error',
      'stop-on-failure',
      'stop-on-skipped',
      'stop-on-incomplete',
      'strict',
      'verbose',
      'debug'
    );
    
    $ret = '' ;
    foreach ($opts as $opt) {
      if (gr_array_fetch($this->opts,$opt)) {
        $ret .= "--{$opt} " ;
      }
    }
    
    return trim($ret) ;
  }
  
  public function run() {
    // keep this line
    if (!parent::run()) { return false ; }
    
    $option_string = $this->get_phpunit_options() ;
    
    chdir(PROJECT_ROOT . '/test') ;
    $path = gr_array_fetch($this->args, 0, '.') ;
    $cmd = "phpunit {$option_string} {$path}" ;
    $streams = \GR\Shell::command($cmd, array('throw_exception_on_nonzero'=>false)) ;

    $rslt = $streams[0];
    echo $rslt;
    
    if (strpos($rslt,'FAILURES!') !== false) {
      exit(1);
    }

    exit(0);
  }                                                                           
  
  
  
  /**
   * Returns the available options for your command
   *
   * The flag 'h|help' is inherited from the base GR\Command class,
   * so you don't need to define it. Otherwise, you define your options
   * here in the form:
   *
   * $spec->add("x|xray", "Description of xray option") ;
   *
   * where 'x' is the short form (-x) and 'xray' is the long
   * form (--xray).
   *
   * Detailed spec for defining options:
   *  v|verbose    flag option (with boolean value true)
   *  d|dir:       option requires a value (MUST require)
   *  d|dir+       option with multiple values.
   *  d|dir?       option with optional value
   *  dir:=s       option with type constraint of string
   *  dir:=string  option with type constraint of string
   *  dir:=i       option with type constraint of integer
   *  dir:=integer option with type constraint of integer
   *  d            single character only option
   *  dir          long option name
   *
   * More info at https://github.com/c9s/php-GetOptionKit
   */
  public static function option_kit() {
    $specs = Command::option_kit() ; // DO NOT DELETE THIS LINE
    $specs->add('tap','Report test execution progress in TAP format.');
    $specs->add('testdox','Report test execution progress in TestDox format.');
    $specs->add('colors','Use colors in output.');
    $specs->add('stop-on-error','Stop execution upon first error.');
    $specs->add('stop-on-failure','Stop execution upon first error or failure.');
    $specs->add('stop-on-skipped','Stop execution upon first skipped test.');
    $specs->add('stop-on-incomplete','Stop execution upon first incomplete test.');
    $specs->add('strict','Run tests in strict mode.');
    $specs->add('v|verbose','Output more verbose information.');
    $specs->add('debug','Display debugging information during test execution.');
    return $specs ; // DO NOT DELETE
  }
}
