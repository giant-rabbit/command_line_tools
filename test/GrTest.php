<?php

class GrTest extends Gr\TestCase\TestCase {

  public function testRunWithNoParamsPrintsUsage() {
    $this->expectOutputRegex('/Usage: gr <app options> <subcommand> <subcommand options>/') ;
    $gr = new Gr\Gr() ;
    $gr->run() ;
  }
  
  public function testRunWithHFlagPrintsHelp() {
    $this->expectOutputRegex('/GR Command Line Tools Help/') ;
    $args = $this->cmd_to_argv('gr -h') ;
    $gr = new Gr\Gr($args) ;
    $gr->run() ;
  }
}