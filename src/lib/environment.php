<?php

function detectEnvironment($dir=false) {
  $dir = $dir ?: getcwd();
  $wp_config = $dir . '/wp-config.php' ;
  $drupal_config = $dir . '/sites/default/settings.php' ;

  if (is_file($wp_config)) {
    return 'wordpress' ;
  }
  
  if (is_file($drupal_config)) {
    return 'drupal' ;
  }
  
  return false ;
}