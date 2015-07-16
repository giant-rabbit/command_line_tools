<?php

namespace GR\Command ;
use GR\Command as Command ;


/**
 * This command doesn't do anything, but is here as a template for people
 * to create more commands within the framework. Copy this file into the
 * src/Command folder and name it with the camel-cased version of your
 * hyphenated command name. For instance, if your command name is foo-bar,
 * save this file as src/Command/FooBar.php. Rename the class with the same
 * filename, ie `class FooBar extends Command`
 *
 * Also, this file is set to read-only, so you'll need to run `chmod u+w <newfile>`
 * on your newly created file in order to edit it.
 */
class AnonymizeDb extends Command {

  /**
   * The DESCRIPTION constant is used as a short summary of the command
   * when the user runs the help command for the main gr tool (`gr -h`)
   */
  const DESCRIPTION = "Anonymizes staging or development databases";

  /**
   * The HELP_TEXT constant is shown when the user runs help on the specific
   * command (ie `gr example -h`). It should be as long as necessary to give
   * complete usage information. Place all of your text between the <<<EOT and EOT;
   * delimiters. It is VERY IMPORTANT that the EOT; delimiter is the first and only
   * thing on its line (no leading whitespace) or you will get a parse error.
   */
  const HELP_TEXT = <<<EOT

  Usage: gr anonymize-db <options> <db-name>

  This tool will anonymize a database by changing all non-GR email addresses to
  the form <client-alias>+<uid>@giantrabbit.com. If run from a Drupal or WordPress
  root, it will attempt to connect to that database, or can be passed mysql
  credentials to connect to an arbitrary database. In that case, you must also
  specify a --type option (drupal or wordpress) so the command knows which tables
  to anonymize.

  Safety Measures
  ---------------
  There are a number of safeguards in place to prevent users from anonymizing
  production databases. By default, the tool makes a backup of the database before
  anonymizing. The tool will abort if it finds the string "prod" in the
  database name (can be overridden with the option --clobber) and it will confirm
  the database name with the user if it does NOT find the string "stag" or "dev".

  That said, users should be **EXTREMELY CAREFUL** not to unintentionally
  anonymize a production DB.

  You have been warned.

EOT;


  protected $database_connection;
  protected $database;
  protected $username;
  protected $password;
  protected $host;
  protected $required_arguments = array(
    'database',
    'username',
    'password',
    'host',
    'domain',
    'alias'
  );

  public function __construct($opts=false,$args=false) {
    parent::__construct($opts,$args) ;
    if (isset($opts['help'])) return true;
    $this->site_info = new \SiteInfo();
    $this->database = \GR\Hash::fetch($args, 0);
    $this->type = \GR\Hash::fetch($opts, 'type', $this->site_info->environment);
    $this->get_options_from_environment();
  }


  /**
   * Runs the command with the opts and args defined in the constructor
   *
   * This is the meat of your command. Keep the call to
   * parent::run(), but replace everything else with
   * your own content
   *
   * See the function option_kit() below for how to define
   * and use the command-line parameters for your command
   */
 public function run() {
    // keep this line
    if (!parent::run()) { return false ; }

    $this->validate_arguments();
    $this->verify_database_with_user();

    if (!isset($this->no_backup) || !$this->no_backup) {
      $this->backup_database();
    } else {
      $this->print_line("* Skipping database backup...");
    }

    $this->connect_to_database();
    $this->anonymize_database();

    $this->print_line("\ndone.");
  }


  public function check_database_name() {
    if (isset($this->clobber) && $this->clobber) {
      return 'OK';
    }

    $db_name = strtolower($this->database);
    $env = new \GR\ServerEnv($this->site_info->root_path);
    $env->setEnvVars();
    if (strpos($db_name, 'prod') !== false || getEnv('APP_ENV') === 'prod') {
      return false;
    }

    if (strpos($db_name, 'stag') === false
    &&  strpos($db_name, 'dev') === false) {
      return 'CONFIRM';
    }

    return 'OK';
  }

  public function get_database() {
    return $this->database;
  }

  public function get_host() {
    return $this->host;
  }

  public function get_username() {
    return $this->username;
  }

  public function get_password() {
    return $this->password;
  }

  protected function anonymize_database() {
    switch($this->type) {
      case 'drupal':
        $this->anonymize_drupal_database();
        break;

      case 'wordpress':
        $this->anonymize_wordpress_database();
        break;

      default:
        throw new \Exception("Invalid database type {$this->type}. Must be either 'drupal' or 'wordpress'");
    }
  }

  protected function anonymize_drupal_database() {
    $dbc = $this->database_connection;
    $this->print_line("\n* Anonymizing Drupal `users` table");
    $qry =  "UPDATE users SET mail=CONCAT('{$this->alias}', '+', uid, '@giantrabbit.com') ";
    $qry .= "WHERE mail NOT LIKE '%@giantrabbit.com' AND mail NOT LIKE CONCAT('%@','{$this->domain}')";
    $this->print_line("  {$qry}");
    $stmt = $dbc->prepare($qry);
    $stmt->execute();

    $this->anonymize_civi_tables_if_present();
  }

  protected function anonymize_wordpress_database() {
    $dbc = $this->database_connection;

    $tbl_qry = "SHOW TABLES LIKE '%_users'";
    $this->print_line($tbl_qry);
    $tbl_stmt = $dbc->prepare($tbl_qry);
    $tbl_stmt->execute();

    $rows = $tbl_stmt->fetchAll(\PDO::FETCH_NUM);

    foreach ($rows as $row) {
      $tbl_name = $row[0];
      if ($tbl_name) {
        $this->print_line("\n* Anonymizing WordPress table {$tbl_name}");
        $qry =  "UPDATE {$tbl_name} SET user_email=CONCAT('{$this->alias}', '+', ID, '@giantrabbit.com') ";
        $qry .= "WHERE user_email NOT LIKE '%@giantrabbit.com' AND user_email NOT LIKE CONCAT('%@','{$this->domain}')";
        $stmt = $dbc->prepare($qry);
        $stmt->execute();
      } else {
        $this->exit_with_message("Could not determine table name for users table");
      }
    }

    $this->anonymize_civi_tables_if_present();
  }

  protected function anonymize_civi_tables_if_present() {
    $dbc = $this->database_connection;
    $check_qry = "SHOW TABLES LIKE 'civicrm_email'";
    $check_stmt = $dbc->prepare($check_qry);
    $check_stmt->execute();

    if ($check_stmt->fetch()) {
      $this->print_line("\n* Anonymizing civicrm_email table");
      $qry =  "UPDATE civicrm_email ";
      $qry .= "SET email = CONCAT('{$this->alias}','+',contact_id,'@giantrabbit.com') ";
      $qry .= "WHERE email NOT LIKE '%@giantrabbit.com' AND email NOT LIKE CONCAT('%@', '{$this->domain}')";
      $this->print_line("  {$qry}");
      $stmt = $dbc->prepare($qry);
      $stmt->execute();
    }
  }

  protected function backup_database() {
    if (isset($this->backup_to)) {
      $backup_to = realpath($this->backup_to);
      if (is_dir($this->backup_to)) {
        $backup_location = $this->backup_to . "/" . $this->database . "_" . date("U") . ".sql";
      } else {
        $backup_location = $this->backup_to;
      }
    } else {
      $backup_location = $this->get_tmp_backup_location();
    }

    $this->print_line("* Backing up database {$this->database} to {$backup_location}");
    $cmd = "mysqldump -u {$this->username} -p{$this->password} -h {$this->host} {$this->database} > {$backup_location}";
    $streams = \GR\Shell::command($cmd);
    $this->print_line($streams[0]);
  }

  protected function get_tmp_backup_location() {
    $tmp = sys_get_temp_dir();
    $db = $this->database;
    $ts = date("U");
    $loc = "{$tmp}/{$db}_{$ts}.sql";

    return $loc;
  }

  protected function validate_arguments() {
    parent::validate_arguments();
    if (isset($this->opts['type'])
    &&  strtolower($this->opts['type']) != 'drupal'
    &&  strtolower($this->opts['type']) != 'wordpress') {
      throw new \GR\Exception\InvalidArgumentException("--type takes only 'drupal' or 'wordpress' as its values");
    }
  }

  protected function connect_to_database() {
    $this->print_line("* Connecting to database {$this->database} as user {$this->username}@{$this->host}");
    $dbn = "mysql:host={$this->host};dbname={$this->database}";
    $dbc = new \PDO($dbn,$this->username,$this->password);
    $dbc->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $this->database_connection = $dbc;
  }

  protected function get_options_from_environment() {
    $creds = false;
    if ($this->site_info->environment == 'drupal') {
      $creds = \GR\Drupal::get_database_credentials($this->working_directory);
    }
    elseif ($this->site_info->environment == 'wordpress') {
      $creds = \GR\Wordpress::get_database_credentials($this->working_directory);
    }

    if ($creds) {
      foreach ($creds as $key => $value) {
        if (!isset($this->{$key})) $this->{$key} = $value ;
      }
    }

    if (!isset($this->host)) $this->host = 'localhost' ;
  }

  protected function verify_database_with_user() {
    if (!$this->check_database_name()) {
      $msg =  "! The database name {$this->database} looks like a production database." ;
      $msg .= "\n  If you're REALLY SURE you want to anonymize it, you can run this command";
      $msg .= "\n  with the --clobber option.";
      $this->exit_with_message($msg);
    }

    if ($this->check_database_name() === 'CONFIRM') {
      $prompt =  "\n! I can't tell for sure that {$this->database} is a non-production database.";
      $prompt .= "\n  If you're sure you want to anonymize it, please type the database name again.";
      $prompt .= "\n  Otherwise, simply press <return> to abort";
      $prompt .= "\n\nConfirm Database Name: ";
      $db_conf = $this->prompt($prompt,null);

      if (!$db_conf || $db_conf != $this->database) {
        $this->exit_with_message("bye");
      }
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
    $break = "\n" . str_repeat(' ',27); // use this to break descriptions into multiple lines
    $specs = Command::option_kit() ; // DO NOT DELETE THIS LINE

    $specs->add("clobber", "Disregard any safeguards and anonymize the given database.") ;
    $specs->add("n|no-backup", "Do not back up database before anonymizing") ;  //
    $specs->add("backup-to:", "Directory to put backup in. Defaults to PHP's sys_get_temp_dir()");

    $specs->add("u|username:", "MySQL User.{$break}If run from a Drupal or Wordpress root, will attempt to retrieve{$break}this value from site config");
    $specs->add("p|password:", "Flag to spec password for MySQL.{$break}The tool will prompt for the password after command input");
    $specs->add("host:", "MySQL Host.{$break}Defaults to localhost, or if run from a Drupal or Wordpress root,{$break}will attempt to retrieve this value from site config");
    $specs->add("t|type:", "Database Type [drupal|wordpress].{$break}If not given, the tool makes an intelligent guess{$break}based on the your current directory.");
    $specs->add("d|domain:", "Client's email domain");
    $specs->add("a|alias:", "Client's email alias or your email username{$break}(eg 'ecomod' or 'bwilhelm')");

    //-------------------------------------------------------++

    return $specs ; // DO NOT DELETE
  }
}
