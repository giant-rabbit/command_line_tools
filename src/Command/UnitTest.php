<?php 

namespace Gr\Command ;
use Gr\Command as Command ;


class UnitTest extends Command {

  const DESCRIPTION = "Runs the test suite on the GR Tools" ;

  const HELP_TEXT = <<<EOT

Run with no arguments, this command will run all unit tests. 

Run with a path to a unit test file or directory (relative to the test directory) it will run only the relevant tests.

Usage:
  gr unit-test
    runs all tests
  
  gr unit-test Command
    runs all tests in the test/Command Directory 
  
  gr unit-test Command/RestoreBackup
    runs the tests in the (fictitious) file test/Command/RestoreBackup.php
EOT;

  
  public function __construct($args) {
    parent::__construct($args) ;
  }
  
  
  public function run() {
    // keep this line
    if (!parent::run()) { return false ; }
    
    // remove everything from here to the end of this    ---------------------++
    // function and replace with your own content                             //
    echo "Passed args: " ;                                                    //
    if (empty($this->args))                                                   //
      echo "none\n\n" ;                                                       //
    else {                                                                    //
      print_r($this->args) ;                                                  //
      echo "\n\n" ;                                                           //
    }                                                                         //
                                                                              //
    echo "Type `gr example -h` for usage and available options.\n\n" ;        //
    //------------------------------------------------------------------------++
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
    
    //$specs->add("f|foo", "Option foo is a flag") ;
    
    return $specs ; // DO NOT DELETE
  }
}