<?php 

namespace GR ;

class GR {

  public $args = array() ;
  public $opts = array() ;
  public $subcommands = array() ;

  public function __construct($args=false) {
    $this->create_option_kit() ;
    if ($args) { $this->parse_args($args) ; }
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
    $subcommands = array() ;
    $arguments = array() ;
    $options = array() ;

    $parser = new \GetOptionKit\ContinuousOptionParser($this->optionKit->specs) ;
    $app_options = $parser->parse($args) ;
    while( ! $parser->isEnd() ) {
      if( in_array($parser->getCurrentArgument(), $this->get_subcommands() )) {
        $subcommand = $parser->advance() ;
        
        $className = "GR\\Command\\" . commandToClassname($subcommand) ;
        $specs = $className::option_kit()->specs ;
        $parser->setSpecs( $specs );
        $sub_options = $parser->continueParse();
        if (empty($subcommands[$subcommand])) { 
          $subcommands[$subcommand] = array(
            'options' => array(),
            'arguments' => array()
          ) ;
        }
        foreach ($sub_options->keys as $key => $option) {
          $subcommands[$subcommand]['options'][$key] = $option->value ;
        }
      } else {
        $arguments[] = $parser->advance();
      }
    }
    
    foreach ($app_options->keys as $key => $option) {
      $options[$key] = $option->value ;
    }
    $this->opts = $options ;
    $this->subcommands = $subcommands ;

    if (empty($this->subcommands)) {
      $this->args = $arguments ;
    } elseif (!empty($arguments)) {
      $k = array_keys($this->subcommands) ;
      $idx = $k[sizeof($k)-1] ;
      reset($this->subcommands) ;
      $this->subcommands[$idx]['arguments'] = $arguments ;
    }
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
      $className = "GR\\Command\\" . commandToClassname($subcommand) ;
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
    echo   "| Usage: gr <app options> <subcommand> <subcommand options> |\n" ;
    echo   "| For help type `gr -h`                                     |\n" ;
    echo   "+-----------------------------------------------------------+\n\n" ;
  }
  

  public function run() {
  
    if ($this->no_action()) {
      $this->print_usage() ;
      return ;
    }
  
    if (isset($this->opts['help']) && $this->opts['help']) {
      $this->print_help() ;
      return ;
    }
    
    if ($this->subcommands) {
      foreach ($this->subcommands as $subcommand => $arr) {
        $className = "GR\\Command\\" . commandToClassname($subcommand) ;
        $opts = $arr['options'] ;
        $args = $arr['arguments'] ;
        $command = new $className($opts,$args) ;
        $command->run() ;
      }
    }
  }
  
  
  protected function no_action() {
    return empty($this->args) && empty($this->opts) && empty($this->subcommands) ;
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