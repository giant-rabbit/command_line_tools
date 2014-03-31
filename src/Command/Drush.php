<?php 

namespace GR\Command ;
use GR\Command as Command ;


class Drush extends Command {

  const DESCRIPTION = "This command is a wrapper function for drush that sets the approriate APP_ENV and APP_NAME environment variables for the website and then runs the drush command." ;

  const HELP_TEXT = <<<EOT

Usage: gr drush <any drush command>

Example: gr drush cc all

This commands runs drush with the APP_ENV and APP_NAME environment variables set to match those of the site it's run within.

Arguments: any normally available drush command
Options: none

EOT;
  
  public function __construct($opts,$args) {
    parent::__construct($opts,$args) ;
  }
  
  public function run() {
    if (!parent::run()) { return false ; }
    $drupal_root = \GR\Shell::command("drush st 'Drupal root' --pipe");
    $drupal_root = trim($drupal_root[0]);
    if (empty($drupal_root)) {
      throw new \Exception("Unable to determine the Drupal root directory. Make sure you are running this command inside a Drupal website's root directory.");
    }
    $vhost_config_file_name = basename($drupal_root);
    $vhost_config_path = "/etc/apache2/sites-enabled/{$vhost_config_file_name}";
    if (!file_exists($vhost_config_path)) {
      throw new \Exception("No apache virtualhost configuration file exists at {$vhost_config_path}.");
    }
    $conf = new \Config();
    $vhost_config_root = $conf->parseConfig($vhost_config_path, 'apache');
    $vhost_config = $vhost_config_root->getItem('section', 'VirtualHost');
    $i = 0;
    while ($item = $vhost_config->getItem('directive', 'SetEnv', NULL, NULL, $i++)) {
      $env_variable = explode(' ', $item->content);
      if ($env_variable[0] == 'APP_ENV' || $env_variable[0] == 'APP_NAME') {
        $env_variable_name = strtolower($env_variable[0]);
        $$env_variable_name = $env_variable[1];
        if (putenv("{$env_variable[0]}={$env_variable[1]}") === FALSE) {
          throw new \Exception("Unable to set {$env_variable[0]} environment variable.");
        }
      }
    }
    if (getenv("APP_ENV") === FALSE) {
      throw new \Exception("No APP_ENV environment variable is set for this site.");
    }
    if (getenv("APP_NAME") === FALSE) {
      throw new \Exception("No APP_NAME environment variable is set for this site.");
    }
    $args = implode(' ', $this->args);
    system("drush {$args}");
  }
  
  /**
   * Returns the available options for your command
   *
   * More info at https://github.com/c9s/php-GetOptionKit
   */
  public static function option_kit() {
    $specs = Command::option_kit() ; // DO NOT DELETE THIS LINE
    // no options yet...
    return $specs ; // DO NOT DELETE
  }
}
