<?php

function retiraAcentoMaiusculo($str) {
  $from 		= array('À','Á','Ã','Â','È','É','Ê','Ì','Í','Ò','Ó','Õ','Ô','Ù','Ú','Ü','Ç','à','á','ã','â','è','é','ê','ì','í','ò','ó','õ','ô','ù','ú','ü','ç','\'','"','&','º','°','ª','\\','/','~','`','´','^','¨','@','&');
  $to   		= array('A','A','A','A','E','E','E','I','I','O','O','O','O','U','U','U','C','a','a','a','a','e','e','e','i','i','o','o','o','o','u','u','u','c','','','','','','','','','','','','','','','');
  $replace 	= str_replace($from, $to,trim($str));
  return strtoupper(trim($replace));
}

function retiraZeroIniciais($stringIn){		
  $tamanho = strlen(trim($stringIn));				
  if($tamanho >= 2){
    $flag = false;
    while($flag == false){
      if(substr($stringIn,0,1) == "0"){				
        $tamanho -= 1;
        if($tamanho <= 0){
          $stringIn = "";
          $flag = true;
        }else{
          $stringIn = substr($stringIn,1);
        }
      }else{
        $flag = true;
      }
    }
  }
  return $stringIn;		
}

/* Retorna somente os números de uma String */
function somenteNumeros($stringIn){	
  return preg_replace('/[^0-9]+/','',$stringIn);
}

function retornaDataHoraCadastro($dat_cad = '', $hor_cad = '') {
  $dthr_cadastro = trim($dat_cad);
  if(!empty($dthr_cadastro)) {
    if (!empty($hor_cad)) {
      $dthr_cadastro = $dthr_cadastro . ' ' . trim($hor_cad);
    } else {
      $dthr_cadastro = $dthr_cadastro . ' 00:00:00';
    }
  }
  return $dthr_cadastro;
}

function retornaFirstStringFromSplit($stringIn = '', $splitter = '') {
  $string = trim($stringIn);
  if (strpos($string, $splitter) !== false) {
    $array_email = explode($splitter, $string);
    if(is_array($array_email) and count($array_email) > 0) {
      $pos = 0;
      $arrayLength = count($array_email);
      while ($pos < $arrayLength) {
        if (isset($array_email[$pos]) and !empty(trim($array_email[$pos]))) {
          $string = trim($array_email[$pos]);
          break;
        }
        $pos++;
      }
    }
  }
  return $string;
}

function normaliza_telefone($telefoneString = '', $type = 'T') {  
  $telefone = retiraZeroIniciais(somenteNumeros(trim($telefoneString)));
  $telefone = trim($telefone);
  if (
    ($type == 'T' and strlen(trim($telefone)) >= 11) or
    ($type == 'M' and strlen(trim($telefone)) >= 12)
  ) {
    $tel = trim($telefone);
    if (substr($tel,0,2) == substr($tel,2,2)) {
      $tel = trim(substr($tel,2));
    }
    $telefone = trim(substr(trim($tel), 0, 11));
  }
  if (strlen(trim($telefone)) >= 10) {
    if ($type == 'M' and strlen(trim($telefone)) == 10) {
      $telefone = $telefone . '0';
    }
    return trim(substr(trim($telefone), 0, 11));
  } 
  else {
    return '';
  }
}

function normaliza_sexo($sexoString = '') {
  $sexo = trim(strtoupper($sexoString));
  if (empty($sexo) or ($sexo != 'M' and $sexo != 'F')) {
    $sexo = 'M';
  } else {
    $sexo = trim($sexo);
  }
  return $sexo;
}

function normaliza_senha($senha = '') {
  $senha = trim($senha);
  if (empty($senha) or strlen(trim($senha)) < 3) {
    $senha = '@senha';
  } else {
    $senha = trim($senha);
  }
  return $senha;
}

function normaliza_data_cadastro($dat_cad = '') {
  if (empty($dat_cad) or $dat_cad == '0000-00-00') {
    return date('Y-m-d');
  } else {
    return $dat_cad;
  }
}

function normaliza_data_nascimento($dat_nas_cli = '', $date_reference = '') {
  $dat_nas_cli = trim($dat_nas_cli);  
  if (empty($dat_nas_cli) or $dat_nas_cli == '0000-00-00') {
    return '1970-01-01';
  }
  $date_reference = trim($date_reference);
  if (empty($date_reference) or $date_reference == '0000-00-00') {
    $date_reference = date('Y-m-d');
  }
  else if ($dat_nas_cli >= $date_reference) {
    $date_array = explode('-', $dat_nas_cli);
    return '1972-'.$date_array[1].'-'.$date_array[2];
  } else {
    return $dat_nas_cli;
  }
}

function normaliza_email($emailString = '', $defaultEmail = '') {
  $defaultDomain = '@EMAIL.COM';
  $email = trim($emailString);
  if (!empty($email)) {
    // Replace ';' before @ (possibly it was a typo)
    $emailArray = explode('@', trim($email));
    $emailArray[0] = str_replace(array(';',','), '', $emailArray[0]);
    $email = implode('@',$emailArray);

    // Change characters
    $email = retornaFirstStringFromSplit($email, ';');
    $email = retornaFirstStringFromSplit($email, ',');
    $email = str_replace(',', '.', $email);
    $email = preg_replace('/[^A-Za-z0-9\-_.,@]/', '', $email);
    $email = preg_replace('/(\.)\1{1,}/', '.', $email);
    $email = str_replace('.@', '@', $email);
    $email = str_replace('@.', '@', $email);
    $email = (trim(substr($email,-1)) == '.') ? trim(substr($email,0,(strlen($email)-1))) : trim($email);

    // Spliting E-mail
    $emailArray = explode('@', trim($email));

    // Veryfing last char
    $exitValiation = false;
    $pattern       = "/[A-Za-z0-9]/i";
    $emailArray[0] = trim($emailArray[0]);
    while(!$exitValiation) {
      $lastChar = trim(strtoupper(substr($emailArray[0],-1)));
      if (!empty($lastChar) and preg_match($pattern, trim($lastChar)) !== 1) {
        $emailArray[0] = trim(substr($emailArray[0],0,(strlen($emailArray[0])-1)));
      } else {
        $exitValiation = true;
      }
    }

    // Veryfing first element (e-mail name)
    if (empty($emailArray[0])) {
      if (empty($defaultEmail)) {
        $emailArray[0] = getGUID();
      } else {
        $emailArray[0] = $defaultEmail;
      }
    }

    // Veryfing second element (e-mail domain)
    if (count($emailArray) >= 2) {
      // Adding .com
      if (strpos($emailArray[1], '.') === false) {
        if (strlen(trim($emailArray[1])) <= 1) {
          $emailArray[1] = trim($emailArray[1]) . 'EMAIL';
        }
        $email = trim(strtoupper($emailArray[0])) . '@' . trim(strtoupper($emailArray[1])) . '.COM';
      } else {
        $email = trim($emailArray[0]) . '@' . trim($emailArray[1]);
      }
      
      // Veryfing Last 2 chars
      $lastChars = trim(strtoupper(substr($email,-2)));
      if (trim($lastChars) == '.C') {
        $email = trim(strtoupper($email)) . 'OM';
      }
      else if (trim($lastChars) == '.B') {
        $email = trim(strtoupper($email)) . 'R';
      }

      // If domain dont matching, add a default one
      $emailArray = explode('@', trim($email));
      $pattern = "/^([a-z0-9]+)([._-]([0-9a-z]+))*([.]([a-z0-9]+){2,4})$/i";
      if (!isset($emailArray[1]) or empty($emailArray[1]) or preg_match($pattern, trim($emailArray[1])) !== 1) {
        $email = strtoupper($emailArray[0]) . trim($defaultDomain);
      }
    } else {
      // If dont have domain on the second element, add a default one
      $email = strtoupper($emailArray[0]) . trim($defaultDomain);
    }
  }
  return $email;
}

function normalizaCep($cep = null) {
  if(trim($cep) != ''){
    $cep = somenteNumeros(trim($cep));
    $cep = str_replace(array('-',' ','.','_'), '', $cep);
    if (strlen(trim($cep)) == 7 and substr(trim($cep),0,1) != '0') {
      $cep = '0' . trim($cep);
    }
    if (strlen(trim($cep)) < 8) {
      $cep = str_pad(trim($cep),8,'0');
    }
  }
  return trim($cep);
}

function validaEmail($email) {
  $email = trim($email);
  $pattern = "/^([a-z0-9]+)([._-]([0-9a-z_-]+))*@([a-z0-9]+)([._-]([0-9a-z]+))*([.]([a-z0-9]+){2,4})$/i";
  if (empty($email) or preg_match($pattern, $email) !== 1) {
    return false;
  } else {
    return true;
  }
}

function validaCep($cep = null) {
  if (is_numeric($cep) and (strlen($cep) == 8)) {
    return true;
  } else {
    return false;
  }
}

function validaCPF($cpf = null) {
  // Extrai somente os números
  $cpf = preg_replace( '/[^0-9]/is', '', $cpf );
  
  // Verifica se foi informado todos os digitos corretamente
  if (strlen($cpf) != 11) {
      return false;
  }

  // Verifica se foi informada uma sequência de digitos repetidos. Ex: 111.111.111-11
  if (preg_match('/(\d)\1{10}/', $cpf)) {
      return false;
  }

  // Faz o calculo para validar o CPF
  for ($t = 9; $t < 11; $t++) {
      for ($d = 0, $c = 0; $c < $t; $c++) {
          $d += $cpf[$c] * (($t + 1) - $c);
      }
      $d = ((10 * $d) % 11) % 10;
      if ($cpf[$c] != $d) {
          return false;
      }
  }
  return true;
}

function validaCNPJ($cnpj) {
  $cnpj = preg_replace('/[^0-9]/', '', (string) $cnpj);
	
	// Valida tamanho
	if (strlen($cnpj) != 14)
		return false;

	// Verifica se todos os digitos são iguais
	if (preg_match('/(\d)\1{13}/', $cnpj))
		return false;	

	// Valida primeiro dígito verificador
	for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++)
	{
		$soma += $cnpj[$i] * $j;
		$j = ($j == 2) ? 9 : $j - 1;
	}

	$resto = $soma % 11;

	if ($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto))
		return false;

	// Valida segundo dígito verificador
	for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++)
	{
		$soma += $cnpj[$i] * $j;
		$j = ($j == 2) ? 9 : $j - 1;
	}

	$resto = $soma % 11;

	return $cnpj[13] == ($resto < 2 ? 0 : 11 - $resto);
}

function getGUID() {
  if (function_exists('com_create_guid')){
      return com_create_guid();
  }else{
    mt_srand((double)microtime()*10000);
    $charid = md5(uniqid(rand(), true));
    $hyphen = "";
    $uuid = substr($charid, 0, 8).$hyphen
        .substr($charid, 8, 4).$hyphen
        .substr($charid,12, 4).$hyphen
        .substr($charid,16, 4).$hyphen
        .substr($charid,20,12);
    return $uuid;
  } 
}
?>