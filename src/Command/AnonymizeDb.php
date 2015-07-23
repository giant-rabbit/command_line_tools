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

  public $site_info;
  public $database_credentials;
  public $type;
  public $domain;
  public $alias;

  protected $required_arguments = array(
    'database_credentials',
    'domain',
    'alias'
  );

  public function __construct($opts = FALSE, $args = FALSE) {
    parent::__construct($opts, $args) ;
    $this->site_info = new \GR\SiteInfo();
    $this->type = \GR\Hash::fetch($opts, 'type', $this->site_info->environment);
    $this->set_database_credentials();
    $this->domain = \GR\Hash::fetch($opts, 'domain', 'giantrabbit.com');
    $process_user = posix_getpwuid(posix_geteuid());
    $this->alias = \GR\Hash::fetch($opts, 'alias', $process_user['name']); 
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
    if (!parent::run()) { return FALSE; }
    $this->validate_arguments();
    $this->verify_database_with_user();

    if (!isset($this->no_backup) || !$this->no_backup) {
      $this->backup_database();
    } else {
      $this->print_line("* Skipping database backup...");
    }

    $this->print_line("* Connecting to database {$this->database_credentials['database']} as user {$this->database_credentials['username']}@{$this->database_credentials['host']}");
    $this->site_info->get_database_connection($this->database_credentials);
    $this->anonymize_database();

    $this->print_line("\nThe database was successfully anonymized.");
  }


  public function check_database_name() {
    if (isset($this->clobber) && $this->clobber) {
      return TRUE;
    }
    $db_name = strtolower($this->database_credentials['database']);
    $env = new \GR\ServerEnv($this->site_info->root_path);
    $env->setEnvVars($throw_exception = FALSE);
    if (strpos($db_name, 'prod') !== FALSE || getEnv('APP_ENV') === 'prod') {
      return FALSE;
    }
    if (strpos($db_name, 'stag') === FALSE && strpos($db_name, 'dev') === FALSE && strpos($db_name, 'local') === FALSE) {
      return 'CONFIRM';
    }

    return TRUE;
  }

  public function set_database_credentials() {
    $database_credentials = $this->site_info->database_credentials;
    $overridden_database_credentials = $database_credentials;
    $overridden_database_credentials['database'] = \GR\Hash::fetch($this->args, 0, $database_credentials['database']);
    foreach (array('username', 'password', 'host') as $option) {
      $overridden_database_credentials[$option] = \GR\Hash::fetch($this->opts, $option, $database_credentials[$option]);
    }
    $this->database_credentials = $overridden_database_credentials;
  }

  protected function anonymize_database() {
    $environment_anonymize_function = "anonymize_{$this->type}_database";
    if (!is_callable(array($this, $environment_anonymize_function))) {
      throw new \Exception("Invalid database type {$this->type}. Must be either 'drupal' or 'wordpress'");
    }
    $this->$environment_anonymize_function();
    $this->anonymize_civicrm_tables_if_present();
  }

  protected function anonymize_drupal_database() {
    $dbc = $this->site_info->database_connection;
    $this->print_line("\n* Anonymizing Drupal `users` table");
    $qry =  "UPDATE users SET mail=CONCAT('{$this->alias}', '+', uid, '@giantrabbit.com') ";
    $qry .= "WHERE mail NOT LIKE '%@giantrabbit.com' AND mail NOT LIKE CONCAT('%@','{$this->domain}')";
    $this->print_line("  {$qry}");
    $stmt = $dbc->prepare($qry);
    $stmt->execute();
  }

  protected function anonymize_wordpress_database() {
    $dbc = $this->site_info->database_connection;
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
  }

  protected function anonymize_civicrm_tables_if_present() {
    $dbc = $this->site_info->database_connection;
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
        $backup_location = $this->backup_to . "/" . $this->database_credentials['database'] . "_" . date("U") . ".sql";
      } else {
        $backup_location = $this->backup_to;
      }
    } else {
      $backup_location = $this->get_tmp_backup_location();
    }

    $this->print_line("* Backing up database {$this->database_credentials['database']} to {$backup_location}");
    $cmd = "mysqldump -u {$this->database_credentials['username']} -p{$this->database_credentials['password']} -h {$this->database_credentials['host']} {$this->database_credentials['database']} > {$backup_location}";
    $streams = \GR\Shell::command($cmd);
    $this->print_line($streams[0]);
  }

  protected function get_tmp_backup_location() {
    $tmp = sys_get_temp_dir();
    $db = $this->database_credentials['database'];
    $ts = date("U");
    $loc = "{$tmp}/{$db}_{$ts}.sql";

    return $loc;
  }

  protected function validate_arguments() {
    parent::validate_arguments();
    if (!in_array(strtolower($this->type), array('drupal', 'wordpress'))) {
      throw new \GR\Exception\InvalidArgumentException("--type takes only 'drupal' or 'wordpress' as its values");
    }
  }

  protected function verify_database_with_user() {
    if ($this->check_database_name() === FALSE) {
      $msg =  "! The database name {$this->database_credentials['database']} looks like a production database." ;
      $msg .= "\n  If you're REALLY SURE you want to anonymize it, you can run this command";
      $msg .= "\n  with the --clobber option.";
      $this->exit_with_message($msg);
    }

    if ($this->check_database_name() === 'CONFIRM') {
      $prompt =  "\n! I can't tell for sure that {$this->database_credentials['database']} is a non-production database.";
      $prompt .= "\n  If you're sure you want to anonymize it, please type the database name again.";
      $prompt .= "\n  Otherwise, simply press <return> to abort";
      $prompt .= "\n\nConfirm Database Name: ";
      $db_conf = $this->prompt($prompt,null);

      if (!$db_conf || $db_conf != $this->database_credentials['database']) {
        $this->exit_with_message("Database anonymization aborted.");
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
    $specs->add("d|domain?", "Client's email domain. Defaults to giantrabbit.com.");
    $specs->add("a|alias?", "Client's email alias or your email username. Defaults to the user initiating the command. {$break}(eg 'ecomod' or 'bwilhelm')");

    //-------------------------------------------------------++

    return $specs ; // DO NOT DELETE
  }
}
