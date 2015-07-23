<?php

namespace GR\Environment;

class Drupal {

  public static function get_environment_candidates() {
    return array(
      'includes/common.inc',
      'misc/drupal.js',
    );
  }

  public static function get_web_writeable_paths($root_path) {
    $web_writeable_paths = glob($root_path . "/sites/*/files");

    return $web_writeable_paths;
  }

  public static function get_database_credentials($root_path = NULL) {
    $root_path = isset($root_path) ? $root_path : getcwd();
    $env = new \GR\ServerEnv($root_path);
    $env->setEnvVars($throw_exception = FALSE);
    include("{$root_path}/sites/default/settings.php");
    $database_credentials = array();
    if (isset($databases)) {
      // Drupal 7
      $database_credentials = array(
        'database' => $databases['default']['default']['database'],
        'username' => $databases['default']['default']['username'],
        'password' => $databases['default']['default']['password'],
        'host' => $databases['default']['default']['host'],
      );
    }
    elseif ($db_url) {
      // Drupal 6
      $regex = "|^(.*?)://(.*?):(.*?)@(.*?)/(.*?)$|";
      $matches = array();
      preg_match($regex, $db_url, $matches);
      if (!empty($matches)) {
        $database_credentials = array(
          'database' => $matches[5],
          'username' => $matches[2],
          'password' => $matches[3],
          'host'     => $matches[4],
        );
      }
    }

    return $database_credentials;
  }
}
