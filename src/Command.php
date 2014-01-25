<?php 

namespace Gr ;

class Command {
  public function __construct($opts,$args) {
    $className = get_class($this) ;
    $this->optionKit = $className::option_kit() ;
    $this->opts = $opts ? $opts : array() ;
    $this->args = $args ? $args : array() ;
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
  
  public function command_name() {
    $a = explode("\\", get_class($this)) ;
    $className = $a[sizeof($a)-1] ;
    return classnameToCommand($className) ;
  }
  
  public function run() {
    if (isset($this->opts['help']) && $this->opts['help']) {
      $this->print_help() ;
      return false ;
    }
    return true ;
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