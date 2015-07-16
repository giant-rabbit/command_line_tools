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
class SetPerms extends Command {

  const DESCRIPTION = "Sets user, group, and mode for drupal or wordpress sites" ;
  const HELP_TEXT = <<<EOT

This is the GR port of our trusty old set_perms tool, but works on wordpress sites as well as drupal
EOT;

  
  /**
   * Class constructor for your command
   *
   * If you need to override the constructor, be sure 
   * to keep the call to the parent constructor in place.
   * The $opts and $args params are automatically given to the constructor
   * by the main GR object and they are stored to $this->opts. 
   * and $this->args respectively. These are associative array of the 
   * arguments passed via the command line.
   * 
   * Eg. `gr example -f -b bar-value arg1 arg2` will create $opts as 
   * Array [
   *   foo => 1
   *   bar => bar-value
   * ]
   * and $args as
   * Array [
   *   [0] => arg1,
   *   [1] => arg2
   * ]
   * See the option_kit method below for more info on how
   * to define your options
   */
  public function __construct($opts=false,$args=false) {
    parent::__construct($opts,$args) ;
    $this->directory = realpath(\GR\Hash::fetch($opts,'directory','.'));
    $this->site_info = new \SiteInfo($this->directory);
    $process_user = posix_getpwuid(posix_geteuid());
    $this->user = \GR\Hash::fetch($opts, 'user', $process_user['name']);
    $this->group = \GR\Hash::fetch($opts, 'group', 'giantrabbit');
    $web_user = exec('whoami');
    $this->web_user = \GR\Hash::fetch($opts, 'web-user', $web_user);
    // @todo figure out why the optionkit parser won't take multiple values here
    $addl_files = \GR\Hash::fetch($opts, 'additional-site-files');
    if ($addl_files) {
      trigger_error("set-perms cannot currently accept more than one value for --additional-site-files\n", E_USER_NOTICE);
      $this->site_info->web_writeable_paths = array_merge($this->site_info->web_writeable_paths, $addl_files);
    }    
    
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
    
    if (!$this->user || !$this->group) {
      $this->print_usage();
      exit;
    }

    $opts = array('print_command' => true);
    \GR\Shell::command("chown -R {$this->user}:{$this->group} $this->directory", $opts);
    \GR\Shell::command("find {$this->directory} -type d -print0 | xargs -0 chmod 2775", $opts);
    \GR\Shell::command("find {$this->directory} -type f -print0 | xargs -0 chmod 664", $opts);

    foreach ($this->site_info->web_writeable_paths as $file) {
      if (is_dir($file)) {
        \GR\Shell::command("chown -R {$this->web_user}:{$this->web_user} {$file}", $opts);
	\GR\Shell::command("find {$file} -type d -print0 | xargs -0 chmod 2775", $opts);
        if ($this->directory_contains_files($file)) {
          \GR\Shell::command("find {$file} -type f -print0 | xargs -0 chmod 0664", $opts);
        }
      }
    }
  }

  public function directory_contains_files($directory_path) {
    list($stdout_data, $stderr_data) = \GR\Shell::command("find {$directory_path} -type f | wc -l");
    if (trim($stdout_data) == "0") {
      return FALSE;
    }
    return TRUE;
  }
  
  public function print_usage() {
    $this->print_line("Usage: gr set-perms [-u <user> -g <group> -d <directory>]");
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
    
    $specs->add('u|user?', "User to own files. Defaults to the user who initiated the command.");
    $specs->add('g|group?', "Group to own files. Defaults to giantrabbit.");
    $specs->add("d|directory?", "Root directory of Drupal/Wordpress install. Defaults to current directory.");
    $specs->add("a|additional-site-files+", "Files or Directories other than sites/*/files or wp-content/uploads that should be owned by www-data");
    $specs->add("w|web-user?", "(optional) User under which the web process runs. Defaults to www-data") ;
    return $specs ; // DO NOT DELETE
  }
}
