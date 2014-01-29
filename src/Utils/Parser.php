<?php
namespace Gr\Utils ;

class Parser {

  public static function parse_wp_config($path) {
    $a = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ;
    $ret = array() ;
    foreach ($a as $ln) {
      $regex_single = '/define\(\'(.+)\',\s*\'(.+)\'\)/' ;
      $regex_double = '/define\(\"(.+)\",\s*\"(.+)\"\)/' ;

      if (preg_match($regex_single, $ln, $match)) {
        $ret['constants'][$match[1]] = $match[2] ;
      }
      
      if (preg_match($regex_double, $ln, $match)) {
        $ret['constants'][$match[1]] = $match[2] ;
      }
      
    }
    
    return $ret ;
  }
  
  public static function parse_drupal_settings($path) {
    $txt = file_get_contents($path) ;
    $databases = array() ;
    $ret = array() ;
    
    // parse database creds
    //$db_pattern = '/^\s*\$databases\s*\=\s*array\((.+)\)\s*\;/' ;
    $db_pattern = '/\n\s*\$databases\s*\=\s*[A|a]rray(.+?)\;/s' ;
    preg_match_all($db_pattern, $txt, $matches) ;
    foreach($matches as $match) {
      $db_str = $match[0] ;
      $database = array() ;
      foreach (array('database', 'username', 'password', 'host') as $key) {
        $line_pattern = "/[\'|\"]{$key}[\'|\"]\s*\=\>\s*[\'|\"](.+?)[\'|\"]/" ;
        preg_match($line_pattern, $db_str, $line_match) ;
        $database[$key] = $line_match[1] ;
      }
      
      $databases[] = $database ;
    }

    $ret['databases'] = $databases ;
    return $ret ;
  }
}