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
  credentials to connect to an arbitrary database.
  
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


  protected $database_name;
  protected $database_connection;

  public function __construct($opts=false,$args=false) {
    parent::__construct($opts,$args) ;
    $this->database_name = $args[0];
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

    if (!$this->check_database_name()) {
      $msg =  "! The database name {$this->database_name} looks like a production database." ;
      $msg .= "\n  If you're REALLY SURE you want to anonymize it, you can run this command";
      $msg .= "\n  with the --clobber option.";
      $this->exit_with_message($msg);
    }
    
    if ($this->check_database_name() === 'CONFIRM') {
      $prompt =  "\n! I can't tell for sure that {$this->database_name} is a non-production database.";
      $prompt .= "\n  If you're sure you want to anonymize it, please type the database name again.";
      $prompt .= "\n  Otherwise, simply press <return> to abort";
      $prompt .= "\n\nConfirm Database Name: ";
      $db_conf = $this->prompt($prompt,null);
      
      if ($db_conf != $this->database_name) {
        $this->exit_with_message("bye");
      }
    }
    
    $this->print_line("Anonymizing database (not really)");
  }
  
  
  public function check_database_name() {
    $db_name = strtolower($this->database_name);
    
    if (strpos($db_name, 'prod') !== false) {
      return false;
    }
    
    if (strpos($db_name, 'stag') === false
    &&  strpos($db_name, 'dev') === false) {
      return 'CONFIRM';
    }
    
    return 'OK';
  }
  
  public function get_database_name() {
    return $this->database_name;
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
    
    $specs->add("clobber", "Disregard any safeguards and anonymize the given database.") ;
    $specs->add("n|no-backup", "Do not back up database before anonymizing") ;  //
    $specs->add("backup-to:", "Directory to put backup in. Defaults to PHP's sys_get_temp_dir()");
    
    $specs->add("u|user:", "MySQL User. If run from a Drupal or Wordpress root, will attempt to retrieve this value from site config");
    $specs->add("p|password", "Flag to spec password for MySQL. The tool will prompt for the password after command input");
    $specs->add("host", "MySQL Host. Defaults to localhost, or if run from a Drupal or Wordpress root, will attempt to retrieve this value from site config");
    
    //-------------------------------------------------------++
    
    return $specs ; // DO NOT DELETE
  }
}