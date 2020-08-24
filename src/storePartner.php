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
  if (isset($array_parameters['errors']) and trim(strtoupper($array_parameters['errors'])) == 'S') {
    $withErrors = true;
  } 
  else {
    $withErrors = false;
  }

  // Starting classes
  $db = new Database();
  $partner = new StorePartners($db);

  // Storing just one partner
  if (isset($array_parameters['parceiro']) and !empty($array_parameters['parceiro'])) {
    $partner->storeByPartnerId($array_parameters['parceiro']);
    exit;
  }

  // Storing by period
  if (!empty($start) or !empty($end)) {
    $partner->storeByPeriod($start, $end, $limit, $withErrors);
    exit;
  }
  
  // Storing all
  $partner->storeAll($limit, $withErrors);
?>