<?php 

namespace GR\Command ;
use GR\Command as Command ;


class FixDefiner extends Command {

  const DESCRIPTION = "Given a raw SQL dump from a CiviCRM-enabled site, this command strips out the definer clause and outputs the result." ;

  const HELP_TEXT = <<<EOT

Usage: gr fix-definer /path/to/raw.sql > /path/to/destination.sql

This command takes the input file, strips out the definer clause, and outputs the result to STDOUT.

Arguments: path to input file
Options: none.

EOT;

  
  public function __construct($opts,$args) {
    parent::__construct($opts,$args) ;
    
    $input_file = \GR\Hash::fetch($args,0) ;
    if (!$input_file && !$opts['help']) {
      throw new \Exception("This command takes one argument - the path to the file to be stripped.");
    }
    
    $this->input_file = $input_file ;
  }
  
  
  /** 
   * Runs the command with the opts and args defined in the constructor
   * 
   * This is the meat of your command. Keep the call to 
   * parent::run(), but replace everything else with 
   * your own content
   *
   * See the function option_kit() below for how to define
   * and use the command-line parameters for your command
   */ 
  public function run() {
    // keep this line
    if (!parent::run()) { return false ; }
    
    $sql = file_get_contents($this->input_file);
    $regex = '/\/\*[^*]*DEFINER=[^*]*\*\//';
    //$regex = '/DEFINER/';
    $rslt = preg_replace($regex, '', $sql) ;
    echo $rslt ;
  }
  
  
  
  /**
   * Returns the available options for your command
   *
   * The flag 'h|help' is inherited from the base GR\Command class,
   * so you don't need to define it. Otherwise, you define your options
   * here in the form:
   *
   * $specs->add("x|xray", "Description of xray option") ;
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
    // no options yet...
    return $specs ; // DO NOT DELETE
  }
}
