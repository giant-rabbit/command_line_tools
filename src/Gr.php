<?php 

namespace Gr ;

class Gr {

  public function __construct($args) {
    $this->create_option_kit() ;
    $this->parse_args($args) ;    
  }
  
  public function get_subcommands() {
    $ret = array() ;
    foreach (glob(__DIR__ . "/Command/*.php") as $path) {
      $className = basename($path,'.php') ;
      $ret[] = classnameToCommand($className) ;
    }
    sort($ret) ;
    return $ret ;
  }
  
  public function parse_args($args) {

    $found_args = false ;
    $app_args = array() ;
    $sub_args = array() ;
    $current_index = false ;
    
    foreach ($args as $arg) {
      if ( in_array($arg, $this->get_subcommands()) ) {
        $sub_args[$arg] = array() ;
        $current_index = $arg ;
      }
      
      if ($current_index) {
        $sub_args[$current_index][] = $arg ;
      } else {
        $app_args[] = $arg ;
      }
    }

    $app_parsed = $this->optionKit->parse($app_args) ;
    if (!empty($app_parsed->keys)) { 
      foreach ($app_parsed->keys as $key=>$obj) {
        $found_args = true ;
        $this->args[$key] = $obj->value ;
      }
    } ;
    
    if (!empty($sub_args)) { 
      foreach($sub_args as $subcommand => $cmd_args) {
        $found_args = true ;
        $className = "Gr\\Command\\" . commandToClassname($subcommand) ;
        $parsed = $className::option_kit()->parse($cmd_args) ;
        $parsed_sub[$subcommand] = array() ;
        if (!empty($parsed->keys)) {
          foreach ($parsed->keys as $key => $obj) {
            $parsed_sub[$subcommand][$key] = $obj->value ;
          }
        }
  
        $this->args['subcommands'] = $parsed_sub ;
  
      }
    }
    
    return $found_args ; // true if args present, false if not.
  }
  
  public function print_help() {
    echo "\n\n" ;
    echo "GR Command Line Tools Help\n" ;
    echo "===================================\n\n" ;
    echo "* Available Application Options:\n" ;
    echo "  ------------------------------\n" ;
    $this->optionKit->specs->printOptions() ;
    echo "\n\n" ;
    echo "* Available Subcommands:\n" ;
    echo "  ----------------------\n" ;
    
    echo "  (For help with specific subcommands, type `gr <subcommand> -h`)\n" ;
    
    foreach ($this->get_subcommands() as $subcommand) {
      $className = "Gr\\Command\\" . commandToClassname($subcommand) ;
      echo "\n  {$subcommand}\n" ;
      echo "    " . $className::DESCRIPTION . "\n";
    }
    
    echo "\n* Usage:\n" ;
    echo "  --------\n" ;
    echo "  gr <app options> <subcommand> <subcommand options>\n" ;
    echo "  For usage and options for specific subcommands, type `gr <subcommand> -h`\n" ;
    
    echo "\n" ;
  }
  
  public function print_usage() {
    echo "\n+-----------------------------------------------------------+\n" ;
    echo "| Usage: gr <app options> <subcommand> <subcommand options> |\n" ;
    echo "| For help type `gr -h`                                     |\n" ;
    echo "+-----------------------------------------------------------+\n\n" ;
  }
  

  public function run() {
  
    if (empty($this->args)) {
      $this->print_usage() ;
      exit ;
    }
  
    if (isset($this->args['help']) && $this->args['help']) {
      $this->print_help() ;
      exit ;
    }
    
    if ($this->args['subcommands']) {
      foreach ($this->args['subcommands'] as $subcommand => $args) {
        $className = "Gr\\Command\\" . commandToClassname($subcommand) ;
        $command = new $className($args) ;
        $command->run() ;
      }
    }
  }
  


  /**
   * This is meant to be called once in the constructor
   */
  protected function create_option_kit() {
    if (isset($this->optionKit) && is_a($this->optionKit, "GetOptionKit")) {
      return $this->optionKit ; // don't generate again
    }
  
    $specs = new \GetOptionKit\GetOptionKit() ;
    $specs->add('h|help', "Prints help and usage information for the GR tool.") ;
    $this->optionKit = $specs ;
    return $specs ;    
  }
}