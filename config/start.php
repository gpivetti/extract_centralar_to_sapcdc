<?php
  set_time_limit(0) ;
  ini_set("memory_limit", "78M");

  $array_parameters = array();

  // Parameters by GET
  foreach ($_GET as $key => $value) {
    $array_parameters[$key] = $value;
  }

  // Parameters by codeline
  for ($i=1; $i < $argc; $i++) {
    $parameter = explode('=', $argv[$i]);
    $array_parameters[$parameter[0]] = $parameter[1];
  }

  // Functions and Database
  include_once __DIR__.'/enviroment.php';
  include_once __DIR__.'/functions.php';
  include_once __DIR__.'/database.php';  

  // Loader
  spl_autoload_register(function($className) {
    include_once __DIR__ . '/../classes/' . $className . '.php';
  });
?>