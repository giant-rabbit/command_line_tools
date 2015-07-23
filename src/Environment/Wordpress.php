<?php 

namespace GR\Environment;

class Wordpress {

  public static function get_environment_candidates() {
    return array(
      'wp-login.php',
    );
  }

  public static function get_web_writeable_paths($root_path) {
    $web_writeable_paths = glob($root_path . "/wp-content/uploads");
    $web_writeable_paths = array_merge($web_writeable_paths, glob($root_path . "/wp-content/themes/*/cache"));
    $web_writeable_paths[] = $root_path . "/wp-content/blogs.dir";
    $web_writeable_paths[] = $root_path . "/wp-content/plugins/really-simple-captcha/tmp";

    return $web_writeable_paths;
  }

  public static function get_database_credentials($root_path = NULL) {
    $root_path = isset($root_path) ? $root_path : getcwd();
    $file_contents = file("{$root_path}/wp-config.php", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $config_values = array();
    foreach ($file_contents as $ln) {
      $regex_single = '/define\(\'(.+)\',\s*\'(.+)\'\)/';
      $regex_double = '/define\(\"(.+)\",\s*\"(.+)\"\)/';
      if (preg_match($regex_single, $ln, $match) || preg_match($regex_double, $ln, $match)) {
        $config_values[$match[1]] = $match[2];
      }
    }
    $database_credentials = array();
    if (!empty($config_values)) {
      $database_credentials = array(
        'database' => $config_values['DB_NAME'],
        'username' => $config_values['DB_USER'],
        'password' => $config_values['DB_PASSWORD'],
        'host' => $config_values['DB_HOST'],
      );
    }

    return $database_credentials;
  }
}
