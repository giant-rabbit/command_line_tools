<?php 

namespace GR\Command ;
use GR\Command as Command ;

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

  
  
  public function run() {
    if (!parent::run()) { return false ; }

    $bucket = $this->opts['bucket'] ;
    $contents = \S3::getBucket($bucket) ;
    foreach ($contents as $file) {
      echo "{$file}\n" ;
    }
  }
  
  public function bootstrap_s3() {
    $this->pdo = $this->get_database_connection() ;
    if (!isset($this->opts['id']) 
    || !isset($this->opts['secret'])
    || !isset($this->opts['bucket'])) {
      $this->fetch_aws_credentials() ;
    }

    \S3::setAuth($this->opts['id'], $this->opts['secret']) ;
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
  
  public function get_database_credentials() {
    $env = $this->get_environment($this->working_directory) ;
    
    if ('wordpress' == $env) {
      return $this->get_wordpress_database_credentials($this->working_directory) ;
    } elseif ('drupal' == $env) {
      return $this->get_drupal_database_credentials($this->working_directory) ;
    } else {
      throw new \Exception("Could not determine environment. Please ensure you have installed and configured Drupal or Wordpress.") ;
    }
  }
  
  public function fetch_aws_credentials() {
    $env = $this->get_environment() ;
    if ('drupal' === $env) {
      $qry = $this->pdo->query("SELECT * FROM backup_migrate_destinations WHERE type='S3'") ;
      
      if ($qry->rowCount() === 1) {
        $row = $qry->fetch() ;
        $url = $row['location'] ;
        $creds = $this->parse_aws_url($url) ;
        
        if (!isset($this->opts['id'])) { $this->opts['id'] = $creds['id'] ; }
        if (!isset($this->opts['secret'])) { $this->opts['secret'] = $creds['secret'] ; }
        if (!isset($this->opts['bucket'])) { $this->opts['bucket'] = $creds['bucket'] ; }
        return true ;
      }

      if (!$qry->rowCount())    { $this->exit_with_message("No S3 locations found in database. Please try again and specify id, secret, and bucket."); }
      if ($qry->rowCount() > 1) { $this->exit_with_message("Multiple S3 locations found in database. Please try again and specify bucket"); }
      
      return false ;
    } elseif ('wordpress' === $env) {
      $this->exit_with_message("WORDPRESS NOT YET SUPPORTED") ;
      exit ;
    }
  }
  
  protected function get_database_connection($options=null) {
    $creds = $this->get_database_credentials() ;
    extract($creds) ;
    $dsn = "mysql:host={$host};dbname={$database}" ;
    $dbh = new \PDO($dsn, $username, $password, $options) ;
    $dbh->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION ); 
    $dbh->setAttribute( \PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC );
    return $dbh ;
  }
  
  protected function get_wordpress_database_credentials($dir) {
    $f = $dir . "/wp-config.php" ;
    $parsed = \GR\Parser::parse_wp_config($f) ;
    return array(
      'host' => $parsed['constants']['DB_HOST'] ,
      'username' => $parsed['constants']['DB_USER'] ,
      'password' => $parsed['constants']['DB_PASSWORD'] ,
      'database' => $parsed['constants']['DB_NAME'] ,
    );
  }
  
  protected function get_drupal_database_credentials($dir) {
    $f = $dir . "/sites/default/settings.php" ;
    $parsed = \GR\Parser::parse_drupal_settings($f) ;
    return array(
      'host'     => $parsed['databases']['default']['default']['host'] ,
      'username' => $parsed['databases']['default']['default']['username'] ,
      'password' => $parsed['databases']['default']['default']['password'] ,
      'database' => $parsed['databases']['default']['default']['database'] ,
    );
  }
  
  protected function fetch_aws_url() {
    $db_creds = $this->get_database_credentials() ;
    
  }
  
  protected function parse_aws_url($url) {
    //$regex = "/^[http|https]\:\/\/(.+?):(.+?)@s3\.amazonaws\.com\/(.+?)\/?$/" ;
    $regex = "/^http[s]?\:\/\/(.+?):(.+?)@s3\.amazonaws\.com\/(.+?)\/?$/" ;
    if (preg_match($regex, $url, $matches)) {      
      return array(
        'id' => $matches[1],
        'secret' => $matches[2],
        'bucket' => $matches[3],
      ) ;
    } else {
      throw new Exception("Could not parse AWS URL") ;
    }
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
    $specs->add("b|bucket?", "S3 Bucket from which to retrieve backup") ;
    $specs->add("exclude-files", "Don't restore files directories") ;
    
    return $specs ; // DO NOT DELETE
  }
}