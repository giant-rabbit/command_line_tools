<?php 

namespace Gr\Command ;
use Gr\Command as Command ;

class FooBar extends Command {

  const DESCRIPTION = "Command description for FooBar" ;
  const HELP_TEXT = "Extended help text for FooBar" ;

  public function __construct($args) {
    parent::__construct($args) ;
  }
  
  public function run() {
    parent::run() ;
    print_r($this->args) ; 
  }
  
  public static function option_kit() {
    $specs = Command::option_kit() ;
    $specs->add("f|foo", "Option foo is a flag") ;
    $specs->add("b|bar:", "(REQUIRED) bar takes a value") ;
    return $specs ;
  }
}