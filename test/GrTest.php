<?php

class GrTest extends Gr\TestCase\TestCase {


  public function testParseArgsOptionsOnly() {
    $args = $this->cmd_to_argv('gr -h') ;
    $gr = new Gr\Gr() ;
    $gr->parse_args($args) ;
    $this->assertEquals(array('help' => 1), $gr->opts, "Gr::opts should be array with only help=>1") ;
    $this->assertEmpty($gr->args, "Gr::args should be empty") ;
    $this->assertEmpty($gr->subcommands, "Gr::subcommands should be empty") ;
  }
  
  public function testParseArgsOptionsArgumentsNoSubcommands() {
    $args = $this->cmd_to_argv('gr -h foo1 foo2') ;
    $gr = new Gr\Gr() ;
    $gr->parse_args($args) ;
    $this->assertEquals(array('help' => 1), $gr->opts, "Gr::opts should be array with only help=>1") ;
    $this->assertEquals(array('foo1','foo2'), $gr->args, "Gr::args should be array of foo1, foo2") ;
    $this->assertEmpty($gr->subcommands, "Gr::subcommands should be empty") ;
  }
  
  public function testParseArgsOptionsSubcommandSubOption() {
    $testVal = array(
      'example' => array(
        'options' => array(
          'foo' => 1,
          'bar' => 'baz'
        )
      )
    ) ;
  
    $args = $this->cmd_to_argv('gr -h example -f -b baz') ;
    $gr = new Gr\Gr() ;
    $gr->parse_args($args) ;
    $this->assertEquals(array('help' => 1), $gr->opts, "Gr::opts should be array with only help=>1") ;
    $this->assertEmpty($gr->args, "Gr::args should be empty") ;
    $this->assertEquals($testVal, $gr->subcommands, "Gr::subcommands should reflect subcommand example with options") ;
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
    $gr = new Gr\Gr() ;
    $gr->parse_args($args) ;
    $this->assertEquals(array('help' => 1), $gr->opts, "Gr::opts should be array with only help=>1") ;
    $this->assertEmpty($gr->args, "Gr::args should be empty") ;    
    $this->assertEquals($testVal, $gr->subcommands, "Gr::subcommands should reflect subcommand example with options") ;
  }

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