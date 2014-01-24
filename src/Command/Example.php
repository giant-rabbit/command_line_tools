<?php 

namespace Gr\Command ;
use Gr\Command as Command ;


/**
 * This command doesn't do anything, but is here as a template for people 
 * to create more commands within the framework. Copy this file into the 
 * src/Command folder and name it with the camel-cased version of your 
 * hyphenated command name. For instance, if your command name is foo-bar,
 * save this file as src/Command/FooBar.php. Rename the class with the same
 * filename, ie `class FooBar extends Command`
 *
 * Also, this file is set to read-only, so you'll need to run `chmod u+w <newfile>`
 * on your newly created file in order to edit it.
 */
class Example extends Command {

  /**
   * The DESCRIPTION constant is used as a short summary of the command
   * when the user runs the help command for the main gr tool (`gr -h`)
   */
  const DESCRIPTION = "This command does nothing. It's a template for developers to create more commands." ;

  /** 
   * The HELP_TEXT constant is shown when the user runs help on the specific
   * command (ie `gr example -h`). It should be as long as necessary to give
   * complete usage information. Place all of your text between the <<<EOT and EOT;
   * delimiters. It is VERY IMPORTANT that the EOT; delimiter is the first and only
   * thing on its line (no leading whitespace) or you will get a parse error.
   */
  const HELP_TEXT = <<<EOT

Pass any of the available flags or options (other than -h) and the command will store them and print out its `args` property.

More help text could go here. Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.

EOT;

  
  /**
   * Class constructor for your command
   *
   * If you need to override the constructor, be sure 
   * to keep the call to the parent constructor in place.
   * The $args param is automatically given to the constructor
   * by the main Gr object and gets stored to $this->args. It is
   * an associative array of the arguments passed via the command line.
   * 
   * Eg. `gr example -f -b bar-value` will create $args as 
   * Array [
   *   foo => 1
   *   bar => bar-value
   * ]
   * See the option_kit method below for more info on how
   * to define your options
   */
  public function __construct($args) {
    parent::__construct($args) ;
  }
  
  
  /** 
   * Runs the command with the args defined in the constructor
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
    
    // replace this area with your own options --------------++    
    $specs->add("f|foo", "Option foo is a flag") ;           //
    $specs->add("b|bar:", "(REQUIRED) bar takes a value") ;  //
    //-------------------------------------------------------++
    
    return $specs ; // DO NOT DELETE
  }
}