<?php 

namespace GR\Command ;
use GR\Command as Command ;


class Hipsum extends Command {

  /**
   * The DESCRIPTION constant is used as a short summary of the command
   * when the user runs the help command for the main gr tool (`gr -h`)
   */
  const DESCRIPTION = "Hipster Ipsum generator.  See hipsteripsum.me" ;

  /** 
   * The HELP_TEXT constant is shown when the user runs help on the specific
   * command (ie `gr example -h`). It should be as long as necessary to give
   * complete usage information. Place all of your text between the <<<EOT and EOT;
   * delimiters. It is VERY IMPORTANT that the EOT; delimiter is the first and only
   * thing on its line (no leading whitespace) or you will get a parse error.
   */
  const HELP_TEXT = <<<EOT

Usage: gr hipsum <num_paragraphs> - Defaults to four paragraphs

EOT;

  
  /**
   * Class constructor for your command
   *
   * If you need to override the constructor, be sure 
   * to keep the call to the parent constructor in place.
   * The $opts and $args params are automatically given to the constructor
   * by the main GR object and they are stored to $this->opts. 
   * and $this->args respectively. These are associative array of the 
   * arguments passed via the command line.
   * 
   * Eg. `gr example -f -b bar-value arg1 arg2` will create $opts as 
   * Array [
   *   foo => 1
   *   bar => bar-value
   * ]
   * and $args as
   * Array [
   *   [0] => arg1,
   *   [1] => arg2
   * ]
   * See the option_kit method below for more info on how
   * to define your options
   */
  public function __construct($opts=false,$args=false) {
    parent::__construct($opts,$args) ;
    $this->num_pars = (int)$args[0] ?: 4 ;
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

    $paramString = "paras={$this->num_pars}&type=hipster-centric&html=false" ;
    $url = "http://hipsterjesus.com/api/?{$paramString}";
    $ch = curl_init() ;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $resp = curl_exec($ch) ;
    curl_close($ch) ;
    
    $rslt = json_decode($resp) ;
    $text = str_replace("\n","\n\n",$rslt->text) ;
    
    echo $text ;
    echo "\n" ;
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
    return $specs ; // DO NOT DELETE
  }
}