<?php 

namespace GR\Command ;
use GR\Command as Command ;


class SelfUpdate extends Command {

  const DESCRIPTION = "This command updates the GR tools master branch" ;

  /** 
   * The HELP_TEXT constant is shown when the user runs help on the specific
   * command (ie `gr example -h`). It should be as long as necessary to give
   * complete usage information. Place all of your text between the <<<EOT and EOT;
   * delimiters. It is VERY IMPORTANT that the EOT; delimiter is the first and only
   * thing on its line (no leading whitespace) or you will get a parse error.
   */
  const HELP_TEXT = <<<EOT

  Usage: gr self-update
  
  Mostly just a convenience wrapper for `git checkout master; git pull`, this
  command also ensures that git hooks are linked up properly. The command can be
  run from any directory, rather than needing to be run from the source directory.

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
    
    $this->link_git_hooks();
    $this->update_git_repo();
  }
  
  /**
   * Checks that git hooks are properly symlinked from .git/hooks to $app/git-hooks
   */
  
  protected function link_git_hooks() {
    $this->print_line('');
    $this->print_line('Checking git hooks...');
    chdir($this->app_root . "/.git/hooks");
    $pre_commit_path = realpath($this->app_root . "/git-hooks/pre-commit");
    $post_merge_path = realpath($this->app_root . "/git-hooks/post-merge");
    
    $pre_commit_link = is_file('pre-commit') ? realpath(readlink("pre-commit")) : false;
    $post_merge_link = is_file('post-merge') ? realpath(readlink("post-merge")) : false;
    
    $pre_commit_ok = $pre_commit_link == $pre_commit_path;
    if ($pre_commit_ok) $this->print_line("* pre-commit hook ok");
    $post_merge_ok = $post_merge_link == $post_merge_path;
    if ($post_merge_ok) $this->print_line("* post-merge hook ok");

    if (!$pre_commit_ok) {
      if ($pre_commit_link || is_file('pre-commit')) {
        $this->print_line("! It appears that you already have a pre-commit hook in place");
        $this->print_line("  at " . realpath('pre-commit') );
        $this->print_line("  Please remove it to allow the self-update script to link to its own.");
        $this->exit_with_message("Update aborted.");
      } else {
        $this->print_line("* Linking pre-commit hook to git-hooks/pre-commit");
        \GR\Shell::command("ln -s {$pre_commit_path} .");
      }
    }

    if (!$post_merge_ok) {
      if ($post_merge_link || is_file('post-merge')) {
        $this->print_line("! It appears that you already have a post-merge hook in place");
        $this->print_line("  at " . realpath('post-merge') );
        $this->print_line("  Please remove it to allow the self-update script to link to its own.");
        $this->exit_with_message("Update aborted.");
      } else {
        $this->print_line("* Linking post-merge hook to git-hooks/post-merge");
        \GR\Shell::command("ln -s {$post_merge_path} .");
      }
    }
  }
  
  protected function update_git_repo() {
    chdir($this->app_root);

    $this->print_line('');
    $this->print_line('* Checking out master...');
    $streams = \GR\Shell::command("git checkout master");
    echo $streams[0];

    $this->print_line('');
    $this->print_line('* Performing git pull...');
    $streams = \GR\Shell::command("git pull");
    echo $streams[0];
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
    return $specs ; // DO NOT DELETE
  }
}