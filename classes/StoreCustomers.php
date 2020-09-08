<?php
  class StoreCustomers {

    private $db;
    private $type;
    private $table;
    private $newLine;

    public function __construct($db, $type = 'PF') {
      $this->db = $db;
      $this->type = $type;
      if ($type == 'PF') {
        $this->table = 'clientes_pf';
      } else {
        $this->table = 'clientes_pj';
      }
      $this->newLine = "\n";
    }

    public function storeAll($limit = 0, $withErrors = false) {
      $sql = CustomerBaseClass::getCustomerQuery($limit, $this->type, '', '', '', $withErrors);
      $this->storeCustomers($sql);
    }

    public function storeByCustomerId($cod_cli) {
      $sql = CustomerBaseClass::getCustomerQuery(0, $this->type, $cod_cli);
      $this->storeCustomers($sql);
    }

    public function storeByCustomerOrigin($origin, $limit = 0, $withErrors = false) {
      $sql = CustomerBaseClass::getCustomerQuery($limit, $this->type, $origin, '', '', $withErrors);
      $this->storeCustomers($sql, $origin);
    }

    public function storeByPeriod($data_start, $data_end, $limit = 0, $withErrors = false) {
      $sql = CustomerBaseClass::getCustomerQuery($limit, $this->type, '', $data_start, $data_end, $withErrors);
      $this->storeCustomers($sql);
    }

    public function convertCustomers($limit = 0) {
      echo $sql = CustomerBaseClass::getCustomerToConvertQuery($limit);
      $this->db->query($sql);
      $this->db->execute(); 
      $errorQuery = $this->db->error();
      if (empty($errorQuery)) {
        echo $this->newLine.$this->newLine.'SUCESSO'.$this->newLine;
      } else {
        echo $this->newLine.$this->newLine.$errorQuery.$this->newLine;
      }
    }

    private function storeCustomers($sql, $origin = null) {
      $errors     = 0;
      $inserteds  = 0;
      $updateds   = 0;

      // Auxiliaries
      $noAddresses  = 0;
      $invalids     = 0;
      $customers    = array();

      // Get Data (Customer and Address of them)
      echo $this->newLine.'BUSCANDO DADOS: '.$this->newLine.$sql.$this->newLine.$this->newLine;
      $this->db->query($sql);
      $customers = $this->db->multiple();

      // Processing Customers
      if (count($customers) > 0) {
        $total = count($customers);
        echo '== INICIANDO PROCESSO =='.$this->newLine.$this->newLine;
        echo "TOTAL DE CLIENTES PARA IMPORTACAO: ".$total.$this->newLine.$this->newLine;

        foreach($customers as $item => $obj) {
          $porcentagem = (($item+1)*100)/$total;
          $porcentagem = number_format($porcentagem,2,'.','');
          echo "[".$porcentagem."%] Código: ".$obj->cod_cli;
          
          // Get Customer Data (merge with code)
          $objCustomer = CustomerBaseClass::getCustumerData($obj->cod_cli, $this->db);
          if (!$objCustomer) {
            continue;
          }
          else {
            $obj = (object) array_merge((array) $obj, (array) $objCustomer);
          }

          list($isValid, $noAddress, $insert, $update) = $this->validateAndStoreCustomer($obj);

          // Verifying store
          if ($isValid) {
            if ($noAddress) {
              $noAddress++;
            } else {
              // Succeso?
              if ($insert) {
                $inserteds++;
              } else if ($update) {
                $updateds++;
              } else {
                $errors++;
              }
            }
          } else {
            $invalids++;
          }
          echo $this->newLine;
        }

        // Printing invalids address
        if ($invalids > 0) {
          echo $this->newLine.'TOTAL CLIENTES INVALIDOS: '.$invalids.$this->newLine;
        }

        // Printing invalids address
        if ($noAddress > 0) {
          echo $this->newLine.'TOTAL CLIENTES COM ENDERECOS INVALIDOS: '.$invalids.$this->newLine;
        }

        echo '--------------------------------------------------------------------- '.$this->newLine.$this->newLine;
      } else {
        echo 'No customers to add';
      }

      echo '--------------------------------------------------------------------- '.$this->newLine;
      echo 'INSERIDOS: '.$inserteds.$this->newLine;
      echo 'ATUALIZADOS: '.$updateds.$this->newLine;
      echo 'ERROS: '.$errors.$this->newLine;
      echo '--------------------------------------------------------------------- '.$this->newLine;
    }

    private function validateAndStoreCustomer($obj) {
      // Auxiliaries
      $insert     = false;
      $update     = false;
      $noAddress  = false;

      $obj        = $this->serializeCustomer($obj);
      $errorMsg   = $this->validateCustomer($obj);
      if (empty($errorMsg)) {
        $isValid = true;
        $address = $this->getAddressesOfCustomer($obj->cod_cli);
        if (count($address) > 0) {
          $obj->addresses = $address;

          // Verifying if it will insert or udpate Customer (getting query)
          $customerExists = $this->customerExists($obj->cod_cli);
          if ($customerExists) { // update
            echo " => UPDATE";            
            $update       = true;
            $sqlCustomer  = CustomerBaseClass::getUpdateQuery($this->table, $this->type, $obj);
          } else { // insert
            echo " => INSERT";
            $insert       = true;
            $sqlCustomer  = CustomerBaseClass::getInsertQuery($this->table, $this->type, $obj);
          }
          $this->db->query($sqlCustomer);
          $this->db->execute();
          $errorQuery = $this->db->error();

          // Store Addresses if no error
          if (empty($errorQuery)) {
            $errorQuery = $this->storeCustomerAddresses($obj->cod_cli, $obj->addresses);
          }

          // Verifying errors after all
          if (empty($errorQuery)) {
            CustomerBaseClass::changeToHiginized($obj->cod_cli, $this->db);
            if (!empty($obj->error)) {              
              CustomerBaseClass::deleteError($obj->cod_cli, $this->db);
            }
          } else {
            $insert = false;
            $update = false;
            echo " => ERROR : ".trim($errorQuery)." (".trim($sqlCustomer).')';
            CustomerBaseClass::processingError($errorQuery, $obj->cod_cli, $this->db);
          }
        } else {
          $noAddress = true;
          echo ' => ENDERECOS INVALIDOS';
        }
      }
      else {
        $isValid = false;
        CustomerBaseClass::processingError($errorMsg, $obj->cod_cli, $this->db);
      }
      return array($isValid, $noAddress, $insert, $update);
    }

    private function serializeCustomer($obj) {
      echo ' => SERIALIZE';
      $obj->cpf_cnpj_cli      = somenteNumeros(trim($obj->cpf_cnpj_cli));
      $obj->sex_cli           = normaliza_sexo($obj->sex_cli);
      $obj->sen_cli           = normaliza_senha($obj->sen_cli);
      $obj->tel_cli           = normaliza_telefone($obj->tel_cli, 'T');
      $obj->cel_cli           = normaliza_telefone($obj->cel_cli, 'M');
      $obj->ema_cli           = normaliza_email($obj->ema_cli);
      $obj->dat_cad           = normaliza_data_cadastro($obj->dat_cad);
      $obj->cli_dthr_cadastro = retornaDataHoraCadastro($obj->dat_cad, $obj->hor_cad);
      $obj->dat_nas_cli       = normaliza_data_nascimento($obj->dat_nas_cli, $obj->dat_cad);

      // Return other phones whether tel_cli and cel_cli are empty
      if (empty($obj->tel_cli) and empty($obj->cel_cli)) {
        if (!empty($obj->tel_com_cli)) {
          $obj->tel_cli = normaliza_telefone($obj->tel_com_cli, 'T');
        } else if(!empty($obj->fax_cli)) {
          $obj->tel_cli = normaliza_telefone($obj->fax_cli, 'T');
        }

        // Default phone
        if (empty($obj->tel_cli)) {
          $obj->tel_cli = '1136363636';  
        }
      }

      return $obj;
    }

    private function validateCustomer($obj) {
      echo ' => VALIDATE';

      // invalid CPF or CNPJ
      if (strlen(trim($obj->cpf_cnpj_cli)) <= 11) {
        $isValid = validaCPF(trim($obj->cpf_cnpj_cli));
        if (!$isValid) {
          $errorMessage = 'CPF INVALIDO ('.trim($obj->cpf_cnpj_cli).')';
          echo ' => '.$errorMessage;
          return trim($errorMessage);
        }
      } else {
        $isValid = validaCNPJ(trim($obj->cpf_cnpj_cli));
        if (!$isValid) {
          $errorMessage = 'CNPJ INVALIDO ('.trim($obj->cpf_cnpj_cli).')';
          echo ' => '.$errorMessage;
          return trim($errorMessage);
        }
      }

      // invalid e-mail
      $isValid = validaEmail(trim($obj->ema_cli));
      if (!$isValid) {
        $errorMessage = 'EMAIL INVALIDO ('.trim($obj->ema_cli).')';
        echo ' => '.$errorMessage;
        return trim($errorMessage);
      }

      // repeated cpf or cnpj
      if (strlen(trim($obj->cpf_cnpj_cli)) <= 11) {
        $exists = CustomerBaseClass::cpfExists($obj->cod_cli, trim($obj->cpf_cnpj_cli), $this->db);
        if ($exists) {
          $errorMessage = 'CPF REPETIDO ('.trim($obj->cpf_cnpj_cli).')';
          echo ' => '.$errorMessage;
          return trim($errorMessage);
        }
      } else {
        $exists = CustomerBaseClass::cnpjExists($obj->cod_cli, trim($obj->cpf_cnpj_cli), $this->db);
        if ($exists) {
          $errorMessage = 'CNPJ REPETIDO ('.trim($obj->cpf_cnpj_cli).')';
          echo ' => '.$errorMessage;
          return trim($errorMessage);
        }
      }

      // repeated e-mail
      $exists = CustomerBaseClass::emailExists($obj->cod_cli, trim($obj->ema_cli), trim($this->table), $this->db);
      if ($exists) {
        $errorMessage = 'EMAIL REPETIDO ('.trim($obj->ema_cli).')';
        echo ' => '.$errorMessage;
        return trim($errorMessage);
      }

      // is a partner?
      $cod_ins = CustomerBaseClass::isPartner(trim($obj->cpf_cnpj_cli), trim($obj->ema_cli), $this->db);
      if ($cod_ins > 0) {
        $errorMessage = 'CLIENTE PARCEIRO ('.$cod_ins.')';
        echo ' => '.$errorMessage;
        return trim($errorMessage);
      }

      return '';
    }

    private function getAddressesOfCustomer($cod_cli) {
      // Auxiliaries
      $cep      = '';
      $street   = '';
      $num      = '';
      $invalids = 0;
      $address  = array();

      // Table: Clientes
      $sqlAddressByCustomer = CustomerBaseClass::getAddressQueryByCustomer($cod_cli);
      $this->db->query($sqlAddressByCustomer);
      $row = $this->db->single();
      if ($this->db->rowCount() > 0) {
        $row = $this->serializeAddress($row);
        $isValid = $this->validateAddress($row);
        if ($isValid) {
          $cep        = trim(strtoupper($row->zipCode));
          $street     = trim(strtoupper($row->street));
          $num        = trim(strtoupper($row->number));
          $address[]  = $row;
        }
      }
      
      // Table: MDS Address
      $sqlAddressByAddress= CustomerBaseClass::getAddressQueryByAddress($cod_cli);
      $this->db->query($sqlAddressByAddress);
      $row = $this->db->multiple();
      foreach($row as $item => $obj){
        $obj = $this->serializeAddress($obj);
        if ($obj->zipCode != $cep or $obj->street != $street or $obj->number != $num) {
          $isValid = $this->validateAddress($obj);
          if ($isValid) {
            $address[] = $obj;
          }
        }
      }

      return $address;
    }

    private function serializeAddress($obj) {
      $obj->street  = trim(strtoupper($obj->street));
      $obj->number  = trim(strtoupper($obj->number));
      $obj->zipCode = normalizaCep($obj->zipCode);
      return $obj;
    }

    private function validateAddress($obj) {
      $isValid = validaCep(trim($obj->zipCode));
      if(!$isValid) {
        return false;
      } else {
        return true;
      }
    }

    private function storeCustomerAddresses($cod_cli, $addresses) {
      $addressTable = $this->table . '_enderecos';
      $this->deleteCustomerAddresses($cod_cli);
      foreach ($addresses as $key => $value) {
        $sqlAddress = "
          insert into 
          cdc_data.".trim($addressTable)." 
          (
            cli_codigo,
            cod_end,
            endereco,
            bairro,
            numero,
            ponto_referencia,
            complemento,
            cep,
            cidade,
            estado,
            cod_ibge_municipio,
            endereco_principal
          )
          values(
              ".$cod_cli.",
              ".(++$key).",
              '".$value->street."',
              '".$value->neighborhood."',
              '".$value->number."',
              '".$value->referencePoint."',
              '".$value->complement."',
              '".$value->zipCode."',
              '".$value->city."',
              '".$value->state."',
              ".$value->cod_ibge.",
              ".$value->isDefault."
          )";
          $this->db->query($sqlAddress);
          $this->db->execute();
          if (!empty($this->db->error())) {
            $this->deleteCustomerAddresses($cod_cli);
            return $this->db->error();
          }
      }
      return '';
    }

    private function deleteCustomerAddresses($cod_cli) {
      $addressTable = $this->table . '_enderecos';
      $sqlDelete = 'delete from cdc_data.'.trim($addressTable).' where cli_codigo = '.$cod_cli;
      $this->db->query($sqlDelete);
      $this->db->execute();
    }

    private function customerExists($cod_cli) {
      $sql = 'select cli.cli_codigo from cdc_data.'.$this->table.' cli where cli.cli_codigo = :cli_codigo ';
      $this->db->query($sql);
      $this->db->bind(':cli_codigo', $cod_cli);
      $row = $this->db->single();
      if($this->db->rowCount() > 0){
        return true;
      } else {
        return false;
      }
    }
  }
?>