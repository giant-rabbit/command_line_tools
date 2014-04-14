<?php 

function detectEnvironment($dir=false) {
  $dir = realpath($dir) ?: getcwd();
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


function commandToClassname($command_name) {
  $a = explode('-',$command_name) ;
  $b = array_map('ucfirst', $a) ;
  return implode('',$b) ;
}


function classnameToCommand($class_name) {
  preg_match_all('/[A-Z][^A-Z]*/', $class_name, $results) ;
  $a = array_map('strtolower', $results[0]) ;
  $ret = implode('-',$a) ;
  return $ret ;
}

function gr_array_fetch($array,$idx,$default=false) {
  if (isset($array[$idx])) {
    return $array[$idx] ;
  } else {
    return $default ;
  }
}