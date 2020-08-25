<?php
  include __DIR__.'/../config/start.php';
  
  $typeCustomer = trim(strtoupper($typeCustomer));
  if (empty($typeCustomer) or ($typeCustomer != 'PF' and $typeCustomer != 'PJ')) {
    die("ERROR ON STORING DATA");
  }

  // Verifying limit
  if (isset($array_parameters['limit']) and 
      !empty($array_parameters['limit']) and
      is_numeric($array_parameters['limit'])
  ) {
    $limit = $array_parameters['limit'];
  } else {
    $limit = 0;
  }

  // Verifying Start Date
  $start = '';
  if (isset($array_parameters['start']) and !empty($array_parameters['start'])) {
    $start = $array_parameters['start'];
  }

  // Verifying Final Date
  $end = '';
  if (isset($array_parameters['end']) and !empty($array_parameters['end'])) {
    $end = $array_parameters['end'];
  }    

  // Store with errors
  if (isset($array_parameters['error']) and trim(strtoupper($array_parameters['error'])) == 'S') {
    $withErrors = true;
  } 
  else {
    $withErrors = false;
  }

  // Starting classes
  $db = new Database();
  $customer = new StoreCustomers($db, $typeCustomer);

  // Storing just one customer
  if (isset($array_parameters['cliente']) and !empty($array_parameters['cliente'])) {
    $customer->storebyCustomerId($array_parameters['cliente']);
    exit;
  }
      
  // Storing by origin
  if (isset($array_parameters['origin']) and !empty($array_parameters['origin'])) {
    $customer->storeByCustomerOrigin($array_parameters['origin'], $limit, $withErrors);
    exit;
  } 

  // Storing by period
  if (!empty($start) or !empty($end)) {
    $customer->storeByPeriod($start, $end, $limit, $withErrors);
    exit;
  }
  
  // Storing all
  $customer->storeAll($limit, $withErrors);
?>