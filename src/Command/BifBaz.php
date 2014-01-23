<?php 

namespace Gr\Command ;
use Gr\Command as Command ;

class BifBaz extends Command {

  const DESCRIPTION = "Command description for BifBaz" ;
  const HELP_TEXT = "Extended help text for BifBaz" ;

  public function __construct($args) {
    parent::__construct($args) ;
  }
  
  public function run() {
    parent::run() ;
    print_r($this->args) ;
  }

  public static function option_kit() {
    $specs = Command::option_kit() ;
    $specs->add("b|bif", "Option bif") ;
    $specs->add("z|baz", "Option baz") ;
    return $specs ;
  }
}