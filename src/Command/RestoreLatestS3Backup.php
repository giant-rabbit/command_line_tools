<?php 

namespace Gr\Command ;
use Gr\Command as Command ;

class RestoreLatestS3Backup extends Command {

  const DESCRIPTION = "Overwrites a site's database using the most recent backup from an Amazon S3 bucket" ;
  const HELP_TEXT = <<<EOT

Ideally, given the site name, this tool can find or make intelligent guesses about all of the values it needs to perform the backup. However, if you are setting up a copy of the site for the first time, or if it is in a non-standard directory structure, you may need to explicitly pass some or all of the site root and S3 credentials.

* Usage
  ---------
  
  gr restore-latest-s3-backup <options> <sitename>
    eg. `gr restore-latest-s3-backup smpte.org`
    or  `gr restore-latest-s3-backup --id foo --secret bar smpte.org

EOT;

  
  public function __construct($opts=false,$args=false) {
    parent::__construct($opts,$args) ;
  }
  
  public function run() {
    // keep this line
    if (!parent::run()) { return false ; }
    $this->get_database_credentials() ;
  }
  
  public function get_environment($dir=false) {
    $dir = $dir ?: $this->working_directory ;
    $wp_config = $dir . '/wp-config.php' ;
    $drupal_config = $dir . '/sites/default/settings.php' ;

    if (is_file($wp_config)) {
      return 'wordpress' ;
    }
    
    if (is_file($drupal_config)) {
      return 'drupal' ;
    }
    
    return false ;
  }
  
  function get_database_credentials() {
    $env = $this->get_environment($this->working_directory) ;
    
    if ('wordpress' == $env) {
      return $this->get_wordpress_database_credentials($this->working_directory) ;
    } elseif ('drupal' == $env) {
      return $this->get_drupal_database_credentials($this->working_directory) ;
    } else {
      throw new \Exception("Could not determine environment. Please ensure you have installed and configured Drupal or Wordpress.") ;
    }
  }
  
  protected function get_wordpress_database_credentials($dir) {
    $f = $dir . "/wp-config.php" ;
    $parsed = \Gr\Utils\Parser::parse_wp_config($f) ;
    return array(
      'host' => $parsed['constants']['DB_HOST'] ,
      'username' => $parsed['constants']['DB_USER'] ,
      'password' => $parsed['constants']['DB_PASSWORD'] ,
      'database' => $parsed['constants']['DB_NAME'] ,
    );
  }
  
  
  /**
   * Returns the available options for your command
   *
   * The flag 'h|help' is inherited from the base GR\Command class,
   * so you don't need to define it. Otherwise, you define your options
   * here in the form:
   *
   * $specs->add("x|xray", "Description of xray option") ;
   *
   * where 'x' is the short form (-x) and 'xray' is the long
   * form (--xray).
   *
   * Detailed spec for defining options:
   *  v|verbose    flag option (with boolean value true)
   *  d|dir:       option requires a value (MUST require)
   *  d|dir+       option with multiple values.
   *  d|dir?       option with optional value
   *  dir:=s       option with type constraint of string
   *  dir:=string  option with type constraint of string
   *  dir:=i       option with type constraint of integer
   *  dir:=integer option with type constraint of integer
   *  d            single character only option
   *  dir          long option name
   *
   * More info at https://github.com/c9s/php-GetOptionKit
   */
  public static function option_kit() {
    $specs = Command::option_kit() ; // DO NOT DELETE THIS LINE
    
    $specs->add("r|root?",   "Local root directory of site") ;
    $specs->add("i|id?",     "AWS Access Key ID") ;
    $specs->add("s|secret?", "AWS Secret Access Key") ;
    $specs->add("exclude-files", "Don't restore files directories") ;
    
    return $specs ; // DO NOT DELETE
  }
}