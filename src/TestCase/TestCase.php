<?php

namespace GR\TestCase;

require_once('vendor/autoload.php');

use \GR\Path;

/**
 * This is a class from which all of your test cases should inherit.
 * If you have methods that you want to be available in all test cases,
 * put them here.
 */

class TestCase extends \PHPUnit_Framework_TestCase {

  public $config;
  public $drupal_root;
  public $files_root;
  public $misc_root;
  public $test_root;
  public $wp_root;

  public function __construct() {
    parent::__construct() ;
    $this->test_root = Path::join(__DIR__, "..", "..", "test");
    $this->files_root = Path::join($this->test_root, 'files');
    $this->wp_root = Path::join($this->files_root, 'wordpress');
    $this->drupal_root = Path::join($this->files_root, 'drupal');
    $this->misc_root = Path::join($this->files_root, 'misc');
    
    $config = $this->get_config() ;
    $this->config = $config ;
  }
  
  /**
   * Explodes a string on the space character and returns the resulting array
   *
   * This is a convenience method used in test cases to convert a string command
   * such as `gr -x foo-bar -fb` into an array of arguments, simulating the $argv
   * variable that gets passed to the GR object on instantiation.
   */
  protected function cmd_to_argv($cmd) {
    return explode(' ', $cmd) ;
  }
  
  protected function get_config() {
    return json_decode(file_get_contents(Path::join($this->test_root, "config.json")));
  }

  protected function get_drupal_db() {
    $cnf = $this->config->databases->drupal ;
    $dbn = "mysql:host={$cnf->hostname};dbname={$cnf->database}";
    return new \PDO($dbn,$cnf->username,$cnf->password);
  }
}
