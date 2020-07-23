<?php
  include __DIR__.'/../config/start.php';

  // Verifying limit
  if (isset($array_parameters['limit']) and 
      !empty($array_parameters['limit']) and
      is_numeric($array_parameters['limit'])
  ) {
    $limit = $array_parameters['limit'];
  } else {
    $limit = 0;
  }

  // Starting classes
  $db = new Database();
  $customer = new StoreCustomers($db, $typeCustomer);
  
  $customer->convertCustomers($limit);
?>