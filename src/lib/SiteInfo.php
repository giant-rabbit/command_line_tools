<?php

class SiteInfo {
  public $root_path;
  public $environment;
  public $environment_class;
  public $web_writeable_paths;
  public $database_credentials;
  public $database_connection;

  public function __construct($start_path = NULL) {
    $this->get_root_path_and_environment($start_path);
    if ($this->root_path === FALSE) {
      throw new \Exception("Could not determine the website root path and environment. Please ensure you have installed and configured Drupal or Wordpress.");
    }
    $this->web_writeable_paths = call_user_func(array($this->environment_class, 'get_web_writeable_paths'), $this->root_path);
    $this->database_credentials = call_user_func(array($this->environment_class, 'get_database_credentials'), $this->root_path);
  }

  /**
   * Determine the webroot for a site.
   *
   * Most of this is cribbed from drush's implementation.
   */
  public function get_root_path_and_environment($start_path) {
    $root_path = FALSE;

    $start_path = empty($start_path) ? $this->getcwd() : $start_path;
    foreach (array(TRUE, FALSE) as $follow_symlinks) {
      $path = $start_path;
      if ($follow_symlinks && is_link($path)) {
        $path = realpath($path);
      }
      // Check the start path.
      if ($this->determine_valid_root($path)) {
        $root_path = $path;
        break;
      }
      else {
        // Move up dir by dir and check each.
        while ($path = $this->shift_path_up($path)) {
          if ($follow_symlinks && is_link($path)) {
            $path = realpath($path);
          }
          if ($this->determine_valid_root($path)) {
            $root_path = $path;
            break 2;
          }
        }
      }
    }
    $this->root_path = $root_path;
  }
     
  /**
   * Returns the current working directory.
   *
   * This is the directory as it was when the gr command started, not the
   * directory we are currently in. For that, use getcwd() directly.
   */
  public function getcwd() {
    // We use PWD if available because getcwd() resolves symlinks, which
    // could take us outside of the Drupal root, making it impossible to find.
    // $_SERVER['PWD'] isn't set on windows and generates a Notice.
    $path = isset($_SERVER['PWD']) ? $_SERVER['PWD'] : '';
    if (empty($path)) {
      $path = getcwd();
    }

    return $path;
  }

  /**
   * Returns parent directory.
   *
   * @param string
   *   Path to start from.
   *
   * @return string
   *   Parent path of given path.
   */
  public function shift_path_up($path) {
    if (empty($path)) {
      return FALSE;
    }
    $path = explode('/', $path);
    // Move one directory up.
    array_pop($path);
    return implode('/', $path);
  }

  /**
   * Determine if the given path is a valid web root.
   */
  public function determine_valid_root($path) {
    if (empty($path) || !is_dir($path) || !file_exists($path . '/index.php')) {
      return FALSE;
    }
    $environment_candidates = $this->get_environment_candidates();
    foreach ($environment_candidates as $environment => $candidates) {
      $valid_root = $this->determine_valid_root_from_candidates($path, $candidates);
      if ($valid_root === TRUE) {
        $this->environment = $environment;
        $this->environment_class = $this->format_environment_class($environment);
        break;
      }
    }

    return $valid_root;
  }

  public function determine_valid_root_from_candidates($path, $candidates) {
    $valid_root = TRUE;
    foreach ($candidates as $candidate) {
      if (!file_exists($path . '/' . $candidate)) {
        $valid_root = FALSE;
      }
    }

    return $valid_root;
  }

  public function get_environment_candidates() {
    $environment_candidates = array();
    $path = __DIR__ . "/Environment";
    $files = preg_grep('/^([^.])/', scandir($path));
    foreach ($files as $file) {
      $environment = preg_replace('/\\.php$/', '', $file);
      $environment_class = $this->format_environment_class($environment);
      $environment_candidates[strtolower($environment)] = $environment_class::get_environment_candidates();
    }

    return $environment_candidates;
  }

  public function get_database_connection($database_credentials = NULL) {
    if ($database_credentials === NULL) {
      $database_credentials = $this->database_credentials;
    }
    extract($database_credentials);
    $dsn = "mysql:host={$host};dbname={$database}";
    $database_connection = new \PDO($dsn, $username, $password);
    $database_connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $database_connection->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

    $this->database_connection = $database_connection;
  }

  public function format_environment_class($environment) {
    $environment_class = "\Environment\\" . ucfirst($environment);
    return $environment_class;
  }
}
