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
}