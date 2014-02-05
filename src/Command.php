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