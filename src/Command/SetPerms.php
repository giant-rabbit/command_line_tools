<?php 

namespace GR\Command ;

use GR\Command;
use GR\Path;

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

  public function __construct($opts=false,$args=false) {
    parent::__construct($opts,$args) ;
    $this->directory = realpath(\GR\Hash::fetch($opts, 'directory', '.'));
    $this->site_info = new \GR\SiteInfo($this->directory);
    $process_user = posix_getpwuid(posix_geteuid());
    $this->user = \GR\Hash::fetch($opts, 'user', $process_user['name']);
    $this->group = \GR\Hash::fetch($opts, 'group', 'giantrabbit');
    $this->web_user = \GR\Hash::fetch($opts, 'web-user', 'www-data');
    // @todo figure out why the optionkit parser won't take multiple values here
    $addl_files = \GR\Hash::fetch($opts, 'additional-site-files');
    if ($addl_files) {
      trigger_error("set-perms cannot currently accept more than one value for --additional-site-files\n", E_USER_NOTICE);
      $this->site_info->web_writeable_paths = array_merge($this->site_info->web_writeable_paths, $addl_files);
    }    
    
  }

  public function apply_exception_rules() {
    $exception_rules = $this->load_exception_rules();
    $rule_index = -1;
    foreach ($exception_rules as $exception_rule) {
      $rule_index += 1;
      $user_type = $exception_rule[0];
      $relative_path = $exception_rule[1];
      $user_name = NULL;
      $group_name = NULL;
      if ($user_type === 'www-data') {
        $user_name = $this->web_user;
        $group_name = $this->web_user;
      } elseif ($user_type === 'user') {
        $user_name = $this->user;
        $group_name = $this->group;
      } else {
        throw new \Exception("Invalid user_type ($user_type) for rule index $rule_index: " . print_r($exception_rule, TRUE));
      }
      $full_path = Path::join($this->directory, $relative_path);
      $this->set_perms($user_name, $group_name, $full_path);
    }
    return \GR\Path::join($this->directory, '.gr-set-perms');
  }

  public function load_exception_rules() {
    if (!file_exists($this->exceptions_file_path())) {
      return array();
    }
    $exception_data = file_get_contents($this->exceptions_file_path());
    $exception_lines = mb_split("\n", $exception_data);
    $exception_rules = array();
    foreach ($exception_lines as $exception_line) {
      $exception_line = trim($exception_line);
      if ($exception_line !== '') {
        $exception_rules[] = mb_split("\s+", $exception_line, 2);
      }
    }
    return $exception_rules;
  }

  public function exceptions_file_path() {
    return \GR\Path::join($this->directory, '.gr-set-perms');
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
    if (!parent::run()) { 
      return false ; 
    }

    if (!$this->user || !$this->group) {
      $this->print_usage();
      exit;
    }

    $opts = array('print_command' => true);
    $this->set_perms($this->user, $this->group, $this->directory);

    foreach ($this->site_info->web_writeable_paths as $path) {
      $this->set_perms($this->web_user, $this->web_user, $path);
    }
    $this->apply_exception_rules();
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
  
  public static function option_kit() {
    $specs = Command::option_kit();
    
    $specs->add('u|user?', "User to own files. Defaults to the user who initiated the command.");
    $specs->add('g|group?', "Group to own files. Defaults to giantrabbit.");
    $specs->add("d|directory?", "Root directory of Drupal/Wordpress install. Defaults to current directory.");
    $specs->add("a|additional-site-files+", "Files or Directories other than sites/*/files or wp-content/uploads that should be owned by www-data");
    $specs->add("w|web-user?", "(optional) User under which the web process runs. Defaults to www-data");
    return $specs;
  }

  public function set_perms($user_name, $group_name, $path) {
    $opts = array('print_command' => true);
    if (file_exists($path)) {
      \GR\Shell::command("chown -R {$user_name}:{$group_name} $path", $opts);
      if (is_dir($path)) {
        \GR\Shell::command("find $path -type d -print0 | xargs -0 chmod 2755", $opts);
        if ($this->directory_contains_files($path)) {
          $command = "find {$path} -type f -print0 | xargs -0 chmod 0664";
          \GR\Shell::command("find {$path} -type f -print0 | xargs -0 chmod 0664", $opts);
        }
      } else {
        \GR\Shell::command("chmod 0664 $path", $opts);
      }
    }
  }
}
