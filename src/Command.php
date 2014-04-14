<?php 

namespace GR ;

class Command {

  protected $working_directory ;
  
  public function __construct($opts=false,$args=false) {
    $className = get_class($this) ;
    $this->optionKit = $className::option_kit() ;
    $this->opts = $opts ? $opts : array() ;
    $this->args = $args ? $args : array() ;
    $this->working_directory = $this->get_cli_dir() ;   
  }

  public function run() {
    if (gr_array_fetch($this->opts,'help')) {
      $this->print_help() ;
      return false ;
    }
    return true ;
  }
  
  public function command_name() {
    $a = explode("\\", get_class($this)) ;
    $className = $a[sizeof($a)-1] ;
    return classnameToCommand($className) ;
  }
  
  public function get_cli_dir() {
    $a = Shell::command('pwd') ;
    return trim($a[0]) ;
  }
  
  public function set_working_directory($dir) {
    $this->working_directory = $dir ;
  }
    
  public function print_help() {
    echo "\n\n" ;
    echo "GR Help: {$this->command_name()}\n" ;
    echo "==================================================\n\n" ;
    $className = get_class($this) ;
    echo $className::DESCRIPTION . "\n" ;
    echo $className::HELP_TEXT . "\n\n" ;
    echo "* Available Command Options:\n" ;
    echo "  ------------------------------\n" ;
    $this->optionKit->specs->printOptions() ;
    echo "\n\n" ;
  }
  
  protected function exit_with_message($msg) {
    echo "\n{$msg}\n" ;
    exit ;
  }
  
  protected function print_line($msg) {
    echo "{$msg}\n" ;
  }
  
  /**
   * function prompt
   * @param (string) $prompt
   * @param (array) $valid_inputs
   * @param (string) $default (optional)
   * @return (string) User provided value, filtered through $valid_inputs
   * 
   * Prompts user for a response
   */
  protected function prompt($prompt, $valid_inputs, $default = '') { 
    while(!isset($input) || (is_array($valid_inputs) && !in_array($input, $valid_inputs)) || ($valid_inputs == 'is_file' && !is_file($input))) { 
      echo $prompt; 
      $input = strtolower(trim(fgets(STDIN))); 
      if(empty($input) && !empty($default)) { 
        $input = $default; 
      } 
    } 
    return $input; 
  }

  /**
   * function confirm 
   * @param $prompt
   * @return (bool) True if 'y', false if 'n'
   *
   * Prompts user with Yes/no question
   */  
  protected function confirm($prompt) {
    $prompt = "{$prompt} [Y/n]: ";
    $yn = $this->prompt($prompt, array('y','n'));
    return $yn == 'y';
  }
  
  /**
   * This method should be extended in subclasses to 
   * return options relevant to that command
   */
  protected static function option_kit() {
    $specs = new \GetOptionKit\GetOptionKit() ;
    $specs->add('h|help', "Prints help and usage information for this subcommand.") ;
    return $specs ;
  }
}