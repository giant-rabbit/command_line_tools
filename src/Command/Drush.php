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
    $this->site_info = new \SiteInfo();
  }

  public function run() {
    if (!parent::run()) { return false ; }
    if (empty($this->site_info->root_path) || $this->site_info->environment !== 'drupal') {
      throw new \Exception("Unable to determine the Drupal root directory. Make sure you are running this command inside a Drupal website's root directory.");
    }
    $env = new \GR\ServerEnv($this->site_info->root_path);
    $env->requireApacheConfFile();
    $env->setEnvVars();
    if (getenv("APP_ENV") === FALSE) {
      throw new \Exception("No APP_ENV environment variable is set for this site.");
    }
    if (getenv("APP_NAME") === FALSE) {
      throw new \Exception("No APP_NAME environment variable is set for this site.");
    }
    $args = implode(' ', $this->args);
    $command = "drush {$args}";
    passthru($command);
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
