<?php
namespace Gr\TestCase ;

/**
 * This is a class from which all of your test cases should inherit.
 * If you have methods that you want to be available in all test cases,
 * put them here.
 */

class TestCase extends \PHPUnit_Framework_TestCase {

  protected $wp_root ;
  protected $drupal_root ;

  public function __construct() {
    parent::__construct() ;
    $this->wp_root = TEST_ROOT . '/files/wordpress' ;
    $this->drupal_root = TEST_ROOT . '/files/drupal' ;
  }
  
  /**
   * Explodes a string on the space character and returns the resulting array
   *
   * This is a convenience method used in test cases to convert a string command
   * such as `gr -x foo-bar -fb` into an array of arguments, simulating the $argv
   * variable that gets passed to the Gr object on instantiation.
   */
  protected function cmd_to_argv($cmd) {
    return explode(' ', $cmd) ;
  }
}