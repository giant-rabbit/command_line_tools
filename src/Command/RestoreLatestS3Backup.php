<?php

namespace GR\Command ;
use GR\Command as Command ;

class RestoreLatestS3Backup extends Command {

  const DESCRIPTION = "Overwrites a site's database and files directory using the most recent backup from an Amazon S3 bucket" ;
  const HELP_TEXT = <<<EOT

  Ideally, given the site name, this tool can find or make intelligent guesses
  about all of the values it needs to perform the backup. However, if you are
  setting up a copy of the site for the first time, or if it is in a non-standard
  directory structure, you may need to explicitly pass some or all of the site
  root and S3 credentials.

  The tool will run `gr fix-definer` on the database dump before importing unless
  passed the option --no-fix-definer.

* Usage
  ---------

  gr restore-latest-s3-backup <options>
    eg. `gr restore-latest-s3-backup
    or  `gr restore-latest-s3-backup --id foo --secret bar --bucket smptebackups

EOT;

  public $site_info;

  public function __construct($opts = FALSE, $args = FALSE) {
    parent::__construct($opts, $args);
    $root = \GR\Hash::fetch($opts, 'root');
    $this->site_info = new \GR\SiteInfo($root);
    $this->site_info->get_database_connection();
    $this->bootstrap_s3();
  }

  public function run() {
    if (!parent::run()) { return FALSE; }

    $contents = $this->get_bucket_contents();
    $db_array = array();
    $files_array = array();

    if (empty($contents)) $this->exit_with_message("Nothing found in bucket. Check your credentials and bucket name.");

    foreach ($contents as $file) {
      if (substr($file['name'],-9) == '.mysql.gz') {
        $db_array[$file['time']] = $file['name'];
      }

      if (substr($file['name'],-7) == '.tar.gz') {
        $files_array[$file['time']] = $file['name'];
      }
    }

    ksort($db_array);
    ksort($files_array);

    $db_latest = array_pop($db_array);
    $files_latest = array_pop($files_array);

    $should_restore_db_latest = false;
    if ($db_latest) {
      $db_prompt = "Restore DB from file {$db_latest}?";
      $should_restore_db_latest = \GR\Hash::fetch($this->opts,'no-prompts') ? true : $this->confirm($db_prompt);
    } else {
      $this->print_line("No database backup found. Looking for files that match *.mysql.gz");
    }

    $should_restore_files_latest = false;
    if ($files_latest && !\GR\Hash::fetch($this->opts,'exclude-files')) {
      $files_prompt = "Restore files from tarball {$files_latest}? This will remove everything that's currently in sites/default/files" ;
      $should_restore_files_latest = \GR\Hash::fetch($this->opts,'no-prompts') ? true : $this->confirm($files_prompt);
    }

    if ($should_restore_db_latest)    $this->restore_database($db_latest);
    if ($should_restore_files_latest) $this->restore_files($files_latest);
  }

  public function bootstrap_s3() {
    if (!isset($this->opts['id'])
    || !isset($this->opts['secret'])
    || !isset($this->opts['bucket'])) {
      $this->fetch_aws_credentials() ;
    }

    \S3::setAuth($this->opts['id'], $this->opts['secret']) ;
  }

  public function get_bucket_contents() {
    $bucket = $this->opts['bucket'];
    $prefix = NULL;
    if (array_key_exists('prefix', $this->opts)) {
      $prefix = $this->opts['prefix'];
    }
    return \S3::getBucket($bucket, $prefix);
  }

  public function fetch_aws_credentials() {
    $environment_fetch_aws_credentials_function = "fetch_aws_credentials_{$this->site_info->environment}";
    if (!is_callable(array($this, $environment_fetch_aws_credentials_function))) {
      throw new \Exception("Invalid database type {$this->type}. Must be either 'drupal' or 'wordpress'");
    }
    $this->$environment_fetch_aws_credentials_function();
  }

  protected function fetch_aws_credentials_drupal() {
    // Backup and Migrate 3 changed column name from "type" to "subtype".
    $col_qry = $this->site_info->database_connection->query("SHOW COLUMNS FROM backup_migrate_destinations LIKE '%type'");
    $col_info = $col_qry->fetch();
    $col_name = $col_info['Field'];

    $qry = $this->site_info->database_connection->query("SELECT * FROM backup_migrate_destinations WHERE {$col_name}='S3'") ;

    if ($qry->rowCount() === 1) {
      $row = $qry->fetch() ;
      $url = $row['location'] ;
      $creds = $this->parse_aws_url($url) ;

      if (!isset($this->opts['id'])) { $this->opts['id'] = $creds['id'] ; }
      if (!isset($this->opts['secret'])) { $this->opts['secret'] = $creds['secret'] ; }
      if (!isset($this->opts['bucket'])) { $this->opts['bucket'] = $creds['bucket'] ; }
      if (!isset($this->opts['prefix'])) { $this->opts['prefix'] = $creds['prefix'] ; }
      return true ;
    }

    if (!$qry->rowCount()) { $this->exit_with_message("No S3 locations found in database. Please try again and specify id, secret, and bucket."); }
    if ($qry->rowCount() > 1) { $this->exit_with_message("Multiple S3 locations found in database. Please try again and specify bucket"); }

    return false ;
  }

  public function fetch_aws_credentials_wordpress() {
    $this->exit_with_message("Automatic AWS credential detection not currently supported.");
    exit;
  }

  protected function parse_aws_url($url) {
    //$regex = "/^[http|https]\:\/\/(.+?):(.+?)@s3\.amazonaws\.com\/(.+?)\/?$/" ;
    $regex = "/^http[s]?\:\/\/(.+?):(.+?)@s3\.amazonaws\.com\/(.+?)$/" ;
    if (preg_match($regex, $url, $matches)) {

      $bucket_and_prefix = $matches[3];
      $a = explode('/',$bucket_and_prefix);
      $bucket = $a[0];
      $prefix = sizeof($a) > 1 ? $a[1] : null;

      $ret = array(
        'id' => urldecode($matches[1]),
        'secret' => urldecode($matches[2]),
        'bucket' => urldecode($bucket),
        'prefix' => urldecode($prefix)
      ) ;
      return $ret;
    } else {
      throw new \Exception("Could not parse AWS URL") ;
    }
  }

  public function restore_database($db_dump) {

    $this->print_line("  Downloading DB Dump...");
    $bucket = $this->opts['bucket'];
    $tmp_dir = sys_get_temp_dir();
    $timestamp = date("U");
    $db_dest = str_replace('/','_',$db_dump);
    $dest = "{$tmp_dir}/{$timestamp}_{$db_dest}";
    \S3::getObject($bucket, $db_dump, $dest);

    $should_fix_definer = !\GR\Hash::fetch($this->opts,'no-fix-definer');
    if ($should_fix_definer) {
      $this->print_line("  Stripping DEFINER clauses from DB dump...");
      $import_me = preg_replace("/\.mysql.gz$/",".stripped.mysql.gz", $dest);
      \GR\Shell::command("gr fix-definer --output '{$import_me}' '{$dest}'");
      if (!is_file($import_me)) {
        $this->exit_with_message("Error stripping definer");
      }
    }

    $this->print_line("  Loading DB Dump...");
    $creds = $this->site_info->database_credentials;
    $sql_import = "gunzip -c {$import_me} | mysql -u {$creds['username']} -p{$creds['password']} -h{$creds['host']} {$creds['database']}";
    \GR\Shell::command($sql_import);

    if (!isset($this->opts['keep-schedules']) || !$this->opts['keep-schedules']) {
      $this->print_line("  Disabling Backup and Migrate Schedules...");
      $this->disable_backup_migrate_schedules();
    }

    $this->print_line('Database successfully restored from backup.');
    $this->print_line('');
  }

  public function restore_files($files_tarball) {
    $this->print_line("  Downloading tarball...");
    $bucket = $this->opts['bucket'];
    $tmp_dir = sys_get_temp_dir();
    $timestamp = date("U");
    $files_dest = str_replace('/','_',$files_tarball);
    $tmp_dest = "{$tmp_dir}/{$timestamp}_{$files_dest}";
    \S3::getObject($bucket, $files_tarball, $tmp_dest);

    $this->print_line("  Deleting contents of sites/default/files...");
    \GR\Shell::command("rm -rf sites/default/files");

    $tmp_extracted_dest = "{$tmp_dest}_extracted";
    if (!is_dir($tmp_extracted_dest)) {
      $result = mkdir($tmp_extracted_dest);
      if ($result === FALSE) {
        throw new Exception("Error opening '$tmp_extracted_dest': " . print_r(error_get_last(), TRUE));
      }
    }
    $this->print_line("  Unzipping to $tmp_extracted_dest");
    $cmd = "tar -xvf {$tmp_dest} -C {$tmp_extracted_dest}";
    \GR\Shell::command($cmd, array('throw_exception_on_nonzero'=>true));
    $files_path = "$tmp_extracted_dest/sites/default/files";
    $found = FALSE;
    if (is_dir($files_path)) {
      $found = TRUE;
    } else {
      $dir = opendir($tmp_extracted_dest);
      if ($dir === FALSE) {
        throw new \Exception("Unable to open '$tmp_extracted_dest': " . print_r(error_get_last(), TRUE));
      }
      $sub_dir_names = array();
      while (($entry = readdir($dir)) !== FALSE) {
        if ($entry == "." || $entry == "..") {
          continue;
        }
        $sub_dir_names[] = $entry;
      }
      foreach ($sub_dir_names as $sub_dir_name) {
        $files_path = "$tmp_extracted_dest/$sub_dir_name/files";
        if (is_dir($files_path)) {
          $found = TRUE;
          break;
        }
      }
    }
    if (!$found) {
      throw new \Exception("Couldn't find files directory in extracted files at '$tmp_extracted_dest'.");
    }
    \GR\Shell::command("mv {$files_path} {$this->site_info->root_path}/sites/default/");
    $this->print_line('Files successfully restored from backup.');
    $this->print_line("\nYou may need to run `gr set-perms`");
    $this->print_line('');
  }

  public function disable_backup_migrate_schedules() {
    return $this->site_info->database_connection->exec("UPDATE backup_migrate_schedules SET enabled=0");
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
    $specs->add("p|prefix?", "Prefix to filter results in bucket");
    $specs->add("no-fix-definer", "Don't run `gr fix-definer` on MySQL backup before importing");
    $specs->add("no-prompts", "Execute command with no confirmation prompts. Useful for running in automated processes.");
    $specs->add("exclude-files", "Don't restore files directories") ;
    $specs->add('keep-schedules', "Don't disable Backup and Migrate schedules after restoring database");

    return $specs ; // DO NOT DELETE
  }
}
