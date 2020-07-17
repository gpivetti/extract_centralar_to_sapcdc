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

    public function storeAll($limit = 0) {
      $sql = CustomerBaseClass::getCustomerQuery($limit, $this->type, "");
      $this->storeCustomers($sql);
    }

    public function storeByCustomerId($cod_cli) {
      $sql = CustomerBaseClass::getCustomerQuery(0, $this->type, $cod_cli);
      $this->storeCustomers($sql);
    }

    public function storeByCustomerByOrigin($origin, $limit = 0) {
      $sql = CustomerBaseClass::getCustomerQuery($limit, $this->type, $origin);
      $this->storeCustomers($sql);
    }

    public function storeByPeriod($data_start, $data_end, $limit = 0) {
      $sql = CustomerBaseClass::getCustomerQuery($limit, $this->type, '', $data_start, $data_end);
      $this->storeCustomers($sql);
    }

    public function storeErrors($limit = 0) {
      $sql = CustomerBaseClass::getCustomersWithError($limit, $this->type);
      $this->storeCustomers($sql);
    }

    private function storeCustomers($sql) {
      $errors   = 0;
      $inserted = 0;
      $updated  = 0;

      // Get Data (Customer and Address of them)
      echo $this->newLine.'BUSCANDO DADOS: '.$this->newLine.$sql.$this->newLine.$this->newLine;
      $customers = $this->getCustomers($sql);

      // Process Customers
      if (count($customers) > 0) {
        echo '== INICIANDO PROCESSO =='.$this->newLine.$this->newLine;
        echo "TOTAL DE CLIENTES PARA IMPORTACAO: ".count($customers).$this->newLine;
        foreach($customers as $item => $obj){
          echo $this->newLine."Código: ".$obj->cod_cli;

          // Verifying Customer
          $customerExists = $this->customerExists($obj->cod_cli);
          if ($customerExists) { // update
            echo " => UPDATE";
            $sqlCustomer = CustomerBaseClass::getUpdateQuery($this->table, $this->type, $obj);
          } else { // insert
            echo " => INSERT";
            $sqlCustomer = CustomerBaseClass::getInsertQuery($this->table, $this->type, $obj);
          }

          // Insert/Update Customer
          $this->db->query($sqlCustomer);
          $this->db->execute();

          // Verifying errors
          if (empty($this->db->error())) {
            if ($customerExists) {
              $updated++;
            } else {
              $inserted++;
            }
            if (!empty($obj->customer_error)) {
              $sqlDelete = 'delete from cdc_data.clientes_errors where cli_codigo = '.$obj->cod_cli;
              $this->db->query($sqlDelete);
              $this->db->execute();
            }
          } else {
            echo " => ERROR : ".trim($this->db->error())." (".trim($sqlCustomer).')';
            $errors++;
            if (empty($obj->customer_error)) {
              if ($customerExists) {
                $typeQuery = 'U';
              } else {
                $typeQuery = 'I';
              }
              $sqlError = "
                insert into 
                cdc_data.clientes_errors 
                (
                  cli_codigo,
                  typePerson,
                  typeQuery,
                  query,
                  message
                )
                values (
                  ".trim($obj->cod_cli).",
                  '".trim($this->type)."',
                  '".trim($typeQuery)."',
                  '".substr(trim(addslashes($sqlCustomer)), 0, 19999)."',
                  '".substr(trim(addslashes($this->db->error())), 0, 19999)."'
                )";
              $this->db->query($sqlError);
              $this->db->execute();
            }
          }

          // Customer Address
          $this->storeCustomerAddresses($obj->cod_cli, $obj->addresses);
        }
      }

      echo $this->newLine.$this->newLine;
      echo '--------------------------------------------------------------------- '.$this->newLine;
      echo 'INSERIDOS: '.$inserted.$this->newLine;
      echo 'ATUALIZADOS: '.$updated.$this->newLine;
      echo 'ERROS: '.$errors.$this->newLine;
      echo '--------------------------------------------------------------------- '.$this->newLine;
    }

    private function getCustomers($sql) {
      $this->db->query($sql);
      $row = $this->db->multiple();
      echo '== VALIDANDO DADOS (CLIENTES: '.count($row).') =='.$this->newLine;
      
      // Auxiliaries
      $noAddress  = 0;
      $invalids   = 0;
      $customers  = array();

      foreach($row as $item => $obj){
        echo $this->newLine.'Codigo '.$obj->cod_cli;
        $obj = $this->serializeCustomer($obj);
        $isValid = $this->validateCustomer($obj);
        if ($isValid) {
          // Addresses of Customer
          $address = $this->getAddressesOfCustomer($obj->cod_cli);
          if (count($address) > 0) {
            $obj->addresses = $address;
            $customers[$obj->cod_cli] = $obj;
          } else {
            $noAddress++;
            echo ' => ENDERECOS INVALIDOS';
          }
        } else {
          $invalids++;
        }
      }

      // Printing invalids address
      if ($invalids > 0) {
        echo $this->newLine.'TOTAL CLIENTES INVALIDOS: '.$invalids.$this->newLine;
      }

      // Printing invalids address
      if ($noAddress > 0) {
        echo $this->newLine.'TOTAL CLIENTES COM ENDERECOS INVALIDOS: '.$invalids.$this->newLine;
      }

      if ($invalids > 0 or $noAddress > 0) {
        echo '--------------------------------------------------------------------- '.$this->newLine.$this->newLine;
      } else {
        echo $this->newLine.$this->newLine;
      }

      return $customers;
    }

    private function serializeCustomer($obj) {
      echo ' => SERIALIZE';
      $obj->cpf_cnpj_cli      = somenteNumeros(trim($obj->cpf_cnpj_cli));
      $obj->sex_cli           = normaliza_sexo($obj->sex_cli);
      $obj->tel_cli           = normaliza_telefone($obj->tel_cli);
      $obj->cel_cli           = normaliza_telefone($obj->cel_cli);
      $obj->ema_cli           = normaliza_email($obj->ema_cli);
      $obj->cli_dthr_cadastro = retornaDataHoraCadastro($obj->dat_cad, $obj->hor_cad);
      return $obj;
    }

    private function validateCustomer($obj) {
      echo ' => VALIDATE';
      if (strlen(trim($obj->cpf_cnpj_cli)) <= 11) {
        $isValid = validaCPF(trim($obj->cpf_cnpj_cli));
        if (!$isValid) {
          echo 'Codigo '.$obj->cod_cli.' => CPF INVALIDO ('.trim($obj->cpf_cnpj_cli).')'.$this->newLine;
          return false;
        }
      } else {
        $isValid = validaCNPJ(trim($obj->cpf_cnpj_cli));
        if (!$isValid) {
          echo 'Codigo '.$obj->cod_cli.' => CNPJ INVALIDO ('.trim($obj->cpf_cnpj_cli).')'.$this->newLine;
          return false;
        }
      }
      return true;
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

      // Delete Address
      $sqlDelete = 'delete from cdc_data.'.$addressTable.' where cli_codigo = '.$cod_cli;
      $this->db->query($sqlDelete);
      $this->db->execute();
      
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
      }
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