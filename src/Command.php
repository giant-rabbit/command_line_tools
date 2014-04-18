<?php 

namespace GR ;

class Command {

  protected $working_directory;
  
  public function __construct($opts=false,$args=false) {
    $className = get_class($this) ;
    $this->optionKit = $className::option_kit() ;
    $this->opts = $opts ? $opts : array() ;
    $this->args = $args ? $args : array() ;
    $this->working_directory = $this->get_cli_dir() ;
    $this->environment = detectEnvironment($this->working_directory);
    $this->app_root = $this->get_app_root();
    $this->assign_options();
  }

  public function run() {
    if (gr_array_fetch($this->opts,'help')) {
      $this->print_help() ;
      return false ;
    }
    return true ;
  }
  
  public function command_name() {
    $a = explode("\\", get_class($this)) ;
    $className = $a[sizeof($a)-1] ;
    return classnameToCommand($className) ;
  }
  
  public function get_cli_dir() {
    $a = Shell::command('pwd') ;
    return trim($a[0]) ;
  }
  
  public function set_working_directory($dir) {
    $this->working_directory = $dir ;
  }
    
  public function print_help() {
    echo "\n\n" ;
    echo "GR Help: {$this->command_name()}\n" ;
    echo "==================================================\n\n" ;
    $className = get_class($this) ;
    echo $className::DESCRIPTION . "\n" ;
    echo $className::HELP_TEXT . "\n\n" ;
    echo "* Available Command Options:\n" ;
    echo "  ------------------------------\n" ;
    $this->optionKit->specs->printOptions() ;
    echo "\n\n" ;
  }
  
  protected function assign_options() {
    foreach ($this->opts as $key=>$val) {
      $key = str_replace('-','_',$key);
      if (isset($this->{$key})) {
        throw new \Exception("Cannot use '{$key}' as option name for class " . get_class($this) . ". Already in use as a property name.");
      }
      
      $this->{$key} = $val;
    }
  }
  
  protected function validate_arguments() {
    $req = $this->required_arguments;
    $missing = array();
    foreach ($req as $prop) {
      if (!$this->{$prop}) {
        $missing[] = $prop;
      }
    }
    
    if (!empty($missing)) {
      $string = implode(', ',$missing);
      throw new \Exception("Missing options or arguments: {$string}");
    }
  }
  
  protected function get_app_root($dir=false) {
    
    if ($dir == '/') {
      throw new \Exception("Could not find application root by searching for .git directory");
    }
    
    $dir = $dir ? realpath($dir) : __DIR__;
    $files = scandir($dir);
    if (in_array('.git',$files)) {
      return $dir;
    } else {
      return $this->get_app_root($dir . "/..");
    }
  }
  
  protected function exit_with_message($msg) {
    echo "\n{$msg}\n" ;
    exit ;
  }
  
  protected function print_line($msg) {
    echo "{$msg}\n" ;
  }
  
  /**
   * function prompt
   * @param (string) $prompt
   * @param (array) $valid_inputs
   * @param (string) $default (optional)
   * @return (string) User provided value, filtered through $valid_inputs
   * 
   * Prompts user for a response
   */
  protected function prompt($prompt, $valid_inputs, $default = '') { 
    while(!isset($input) || (is_array($valid_inputs) && !in_array($input, $valid_inputs)) || ($valid_inputs == 'is_file' && !is_file($input))) { 
      echo $prompt; 
      $input = strtolower(trim(fgets(STDIN))); 
      if(empty($input) && !empty($default)) { 
        $input = $default; 
      } 
    } 
    return $input; 
  }
  
  protected function prompt_hidden($prompt) {
    system('stty -echo');
    $ret = $this->prompt($prompt, false);
    system('stty echo');
    echo "\n";
    return $ret;
  }

  /**
   * function confirm 
   * @param $prompt
   * @return (bool) True if 'y', false if 'n'
   *
   * Prompts user with Yes/no question
   */  
  protected function confirm($prompt) {
    $prompt = "{$prompt} [Y/n]: ";
    $yn = $this->prompt($prompt, array('y','n'));
    return $yn == 'y';
  }
  
  /**
   * This method should be extended in subclasses to 
   * return options relevant to that command
   */
  protected static function option_kit() {
    $specs = new \GetOptionKit\GetOptionKit() ;
    $specs->add('h|help', "Prints help and usage information for this subcommand.") ;
    return $specs ;
  }
}