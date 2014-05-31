<?php 

namespace GR\Command ;
use GR\Command as Command ;


class FixDefiner extends Command {

  const DESCRIPTION = "Given an SQL dump from a CiviCRM-enabled site, this command strips out the definer clause and outputs the result." ;

  const HELP_TEXT = <<<EOT

Usage: gr fix-definer /path/to/sql_or_gzip_file

This command takes the input file, strips out the definer clause, and
outputs the result to the file with -definer-fixed appended to the name
of the file.

Arguments: path to input file
EOT;

  static $expected_extensions = array
  (
    '.mysql',
    '.sql',
    '.mysql.gz',
    '.sql.gz',
  );

  public function __construct($opts,$args) {
    parent::__construct($opts,$args) ;
    
    $input_file_name = \GR\Hash::fetch($args,0) ;
    if (!$input_file_name && !$opts['help']) {
      throw new \Exception("This command takes one argument - the path to the file to be stripped.");
    }
    
    $this->input_file_name = $input_file_name;
    $this->output_file_name = NULL;
    if (isset($this->opts['output'])) {
      $this->output_file_name = $this->opts['output'];
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

    $dir_name = NULL;
    $base_file_name = NULL; 
    $extension = NULL;
    $use_gzip = FALSE;
    foreach (static::$expected_extensions as $expected_extension) {
      $regex = str_replace('.', '\.', $expected_extension);
      if (preg_match("/$regex$/", $this->input_file_name)) {
        $base_name = basename($this->input_file_name, $expected_extension);
        $dir_name = dirname($this->input_file_name);
        $extension = $expected_extension;
        break;
      }
    }
    if ($base_name === NULL) {
      $base_name = basename($this->input_file_name);
      $dir_name = dirname($this->input_file_name);
    }
    if (preg_match("/.*\\.gz$/", $extension)) {
      $use_gzip = TRUE;
    }
    if ($this->output_file_name == NULL) {
      $this->output_file_name = "$base_name-definer-fixed$extension";
    }
    if ($use_gzip) {
      $this->input_file = gzopen($this->input_file_name, 'r');
      if ($this->input_file === FALSE) {
        $this->throw_last_error("Error opening input file {$this->input_file_name}");
      }
      $this->output_file = gzopen($this->output_file_name, 'w');
      if ($this->output_file === FALSE) {
        $this->throw_last_error("Error openeing output file {$this->output_file_name}");
      }
    } else {
      $this->input_file = fopen($this->input_file_name, 'r');
      if ($this->input_file === FALSE) {
        $this->throw_last_error("Error opening input file {$this->input_file_name}");
      }
      $this->output_file = fopen($this->output_file_name, 'w');
      if ($this->output_file === FALSE) {
        $this->throw_last_error("Error opening output file {$this->output_file_name}");
      }
    }
    $this->file_get_contents_chunked(4096, $this, function ($chunk, $iteration, $fix_definer){
      $regex = '/\/\*[^*]*DEFINER=[^*]*\*\//';
      $result = fwrite($fix_definer->output_file, preg_replace($regex, '', $chunk));
      if ($result === FALSE) {
        $this->throw_last_error("Error writing to output file {$fix_definer->output_file_name}");
      }
    });

    if ($use_gzip) {
      $result = gzclose($this->input_file);
      if ($result === FALSE) {
        $this->throw_last_error("Error closing input file {$this->input_file_name}");
      }
      $result = gzclose($this->output_file);
      if ($result === FALSE) {
        $this->throw_last_error("Error closing input file {$this->input_file_name}");
      }
    } else {
      $result = fclose($this->input_file);
      if ($result === FALSE) {
        $this->throw_last_error("Error closing input file {$this->input_file_name}");
      }
      $result = fclose($this->output_file);
      if ($result === FALSE) {
        $this->throw_last_error("Error closing input file {$this->input_file_name}");
      }
    }
  }
  
  protected function file_get_contents_chunked($chunk_size, $fix_definer, $callback) {
    
    $i = 0;
    while (!feof($fix_definer->input_file)){
      $data = fread($fix_definer->input_file, $chunk_size);
      if ($data === FALSE) {
        $fix_definer->throw_last_error("Error reading from input file {$fix_definer->input_file_name}");
      }
      call_user_func_array($callback, array($data, $i, $fix_definer));
      $i++;
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
    $specs->add("o|output:", "Output to this file name or php://stdout for stdout");
    return $specs ; // DO NOT DELETE
  }

  public function throw_last_error($message) {
    $error_info = error_get_last();
    throw new Exception("$message: " . print_r($error_info, TRUE));
  }
}
