<?php
  set_time_limit(0) ;
  ini_set("memory_limit", "-1");
  
  include_once __DIR__.'/definitions.php';
  include_once __DIR__.'/functions.php';
  include_once __DIR__.'/database.php';
  include_once __DIR__.'/../classes/base/CustomerBaseClass.php';
  include_once __DIR__.'/../classes/base/PartnerBaseClass.php';
  include_once __DIR__.'/../classes/StoreCustomers.php';
  include_once __DIR__.'/../classes/StorePartners.php';

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
?>