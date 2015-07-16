<?php

class SiteInfo {
  public $root_path;
  public $environment;
  public $web_writeable_paths;

  public function __construct($start_path = NULL) {
    $this->get_root_path_and_environment($start_path);
    if ($this->root_path === FALSE) {
      throw new \Exception("Could not determine the website root path and environment. Please ensure you have installed and configured Drupal or Wordpress.");
    }
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
    $environment_candidates = $this->environment_candidates();
    foreach ($environment_candidates as $environment => $candidates) {
      $valid_root = $this->determine_valid_root_from_candidates($path, $candidates);
      if ($valid_root === TRUE) {
        $this->environment = $environment;
        $this->get_environment_web_writeable_paths($path);
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

  public function environment_candidates() {
    return array(
      'drupal' => array(
        'includes/common.inc',
        'misc/drupal.js',
        'modules/field/field.module',
      ),
      'wordpress' => array(
        'wp-login.php',
      ),
    );
  }

  public function get_environment_web_writeable_paths($path) {
    if ($this->environment == 'drupal') {
      $this->web_writeable_paths = glob($path . "/sites/*/files");
    }
    elseif ($this->environment == 'wordpress') {
      $this->web_writeable_paths = glob($path . "/wp-content/uploads");
      $this->web_writeable_paths = array_merge($this->web_writeable_paths, glob($path . "/wp-content/themes/*/cache"));
      $this->web_writeable_paths[] = $path . "/wp-content/blogs.dir";
      $this->web_writeable_paths[] = $path . "/wp-content/plugins/really-simple-captcha/tmp";
    }
  }
}
