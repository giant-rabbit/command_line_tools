<?php

class GRTest extends GR\TestCase\TestCase {


  public function testParseArgsOptionsOnly() {
    $args = $this->cmd_to_argv('gr -h') ;
    $gr = new GR\GR() ;
    $gr->parse_args($args) ;
    $this->assertEquals(array('help' => 1), $gr->opts, "GR::opts should be array with only help=>1") ;
    $this->assertEmpty($gr->args, "GR::args should be empty") ;
    $this->assertEmpty($gr->subcommands, "GR::subcommands should be empty") ;
  }
  
  public function testParseArgsOptionsArgumentsNoSubcommands() {
    $args = $this->cmd_to_argv('gr -h foo1 foo2') ;
    $gr = new GR\GR() ;
    $gr->parse_args($args) ;
    $this->assertEquals(array('help' => 1), $gr->opts, "GR::opts should be array with only help=>1") ;
    $this->assertEquals(array('foo1','foo2'), $gr->args, "GR::args should be array of foo1, foo2") ;
    $this->assertEmpty($gr->subcommands, "GR::subcommands should be empty") ;
  }
  
  public function testParseArgsOptionsSubcommandSubOption() {
    $testVal = array(
      'example' => array(
        'options' => array(
          'foo' => 1,
          'bar' => 'baz'
        ),
        'arguments' => array()
      )
    ) ;
  
    $args = $this->cmd_to_argv('gr -h example -f -b baz') ;
    $gr = new GR\GR() ;
    $gr->parse_args($args) ;
    $this->assertEquals(array('help' => 1), $gr->opts, "GR::opts should be array with only help=>1") ;
    $this->assertEmpty($gr->args, "GR::args should be empty") ;
    $this->assertEquals($testVal, $gr->subcommands, "GR::subcommands should reflect subcommand example with options") ;
  }
  
  public function testParseArgsOptionsSubcommandOptionAndArgs() {
    $testVal = array(
      'example' => array(
        'options' => array(
          'foo' => 1,
          'bar' => 'baz'
        ),
        'arguments' => array(
          'arg1',
          'arg2'
        )
      )
    ) ;
  
    $args = $this->cmd_to_argv('gr -h example -f -b baz arg1 arg2') ;
    $gr = new GR\GR() ;
    $gr->parse_args($args) ;
    $this->assertEquals(array('help' => 1), $gr->opts, "GR::opts should be array with only help=>1") ;
    $this->assertEmpty($gr->args, "GR::args should be empty") ;    
    $this->assertEquals($testVal, $gr->subcommands, "GR::subcommands should reflect subcommand example with options") ;
  }

  public function testRunWithNoParamsPrintsUsage() {
    $this->expectOutputRegex('/gr <app options> <subcommand> <subcommand options>/') ;
    $gr = new GR\GR() ;
    $gr->run() ;
  }
  
  public function testRunWithHFlagPrintsHelp() {
    $this->expectOutputRegex('/GR Command Line Tools Help/') ;
    $args = $this->cmd_to_argv('gr -h') ;
    $gr = new GR\GR($args) ;
    $gr->run() ;
  }
  
}
