<?php
  class StorePartners {

    private $db;
    private $type;
    private $table;
    private $newLine;

    public function __construct($db) {
      $this->db = $db;
      $this->type = $type;
      $this->$table = 'instaladores';
      $this->newLine = "<br>";
    }

    public function storeAll($limit = 0) {
      $sql = PartnerBaseClass::getPartnerQuery($limit, $this->type, "");
      $this->storePartners($sql);
    }

    public function storeByPartnerId($cod_ins) {
      $sql = PartnerBaseClass::getPartnerQuery(0, $this->type, $cod_ins);
      $this->storePartners($sql);
    }

    public function storeByPeriod($data_start, $data_end, $limit = 0) {
      $sql = PartnerBaseClass::getPartnerQuery($limit, $this->type, '', $data_start, $data_end);
      $this->storePartners($sql);
    }

    public function convertParnters($limit = 0) {
      echo $sql = PartnerBaseClass::getPartnerToConvertQuery($limit);
      $this->db->query($sql);
      $this->db->execute(); 
      $errorQuery = $this->db->error();
      if (empty($errorQuery)) {
        echo $this->newLine.$this->newLine.'SUCESSO'.$this->newLine;
      } else {
        echo $this->newLine.$this->newLine.$errorQuery.$this->newLine;
      }
    }

    private function storePartners($sql) {
      $inserted = 0;
      $updated  = 0;

      // Get Data (Partner and Address of them)
      $partners = $this->getPartners($sql);

      // Process Partners
      if (count($partners) > 0) {
        echo "TOTAL DE PARCEIROS PARA IMPORTACAO: ".count($partners).$this->newLine;
        foreach($partners as $item => $obj){
          echo $this->newLine."Código: ".$obj->cod_ins;
          
          // Verify Partner
          if ($this->partnerExists($obj->cod_ins)) { // update
            $updated++;
            echo " => UPDATE";
            $sqlPartner = PartnerBaseClass::getUpdateQuery($this->table, $this->type, $obj);
          } else { // insert
            $inserted++;
            echo " => INSERT";
            $sqlPartner = PartnerBaseClass::getInsertQuery($this->table, $this->type, $obj);
          }

          // Insert/Udate Partner
          $this->db->query($sqlPartner);
          $this->db->execute();

          // Partner Address
          $this->storePartnerAddresses($obj->cod_ins, $obj->addresses);
        }
      }

      echo $this->newLine.$this->newLine;
      echo ' ---------------------------------------------- '.$this->newLine;
      echo 'INSERIDOS: '.$inserted.$this->newLine;
      echo 'ATUALIZADOS: '.$updated.$this->newLine;
      echo ' ---------------------------------------------- ';
    }

    private function getPartners($sql) {
      $this->db->query($sql);
      $row = $this->db->multiple();
      
      // Auxiliaries
      $noAddress  = 0;
      $invalids   = 0;
      $partners  = array();

      foreach($row as $item => $obj){
        $obj = $this->serializePartner($obj);
        $errorMsg = $this->validatePartner($obj);
        if (empty($errorMsg)) {
          // Get the last customer code by CPF
          $cod_cli = PartnerBaseClass::isCustomer($obj->cpf_tit_con, $obs->ema_ins, $this->db);

          // Get the addresses of partner and customer
          $address = $this->getAddressOfPartner($obj->cod_ins, $cod_cli);
          if (count($address) > 0) {
            $obj->addresses = $address;
            $partners[$obj->cod_ins] = $obj;
          } else {
            $noAddress++;
            echo 'Codigo '.$obj->cod_ins.' => ENDERECO INVALIDO'.$this->newLine;
          }
        } else {
          $invalids++;
          PartnerBaseClass::processingError($errorMsg, $obj->cod_ins, $this->db);
        }
      }

      // Printing invalids address
      if ($invalids > 0) {
        echo $this->newLine.'TOTAL PARCEIROS INVALIDOS: '.$invalids.$this->newLine;
      }

      // Printing invalids address
      if ($noAddress > 0) {
        echo $this->newLine.'TOTAL PARCEIROS COM ENDERECO INVALIDO: '.$invalids.$this->newLine;
      }

      if ($invalids > 0 or $noAddress > 0) {
        echo $this->newLine.' --------------------------------------------------------------------- '.$this->newLine.$this->newLine;
      }

      return $partners;
    }

    private function serializePartner($obj) {
      $obj->cpf_tit_con           = somenteNumeros(trim($obj->cpf_tit_con));
      $obj->sex_ins               = normaliza_sexo($obj->sex_ins);
      $obj->sen_ins               = normaliza_senha($obj->sen_ins);
      $obj->par_telefone          = normaliza_telefone($obj->zzf_dddfi.$obj->zzf_fonfi, 'T');
      $obj->par_celular           = normaliza_telefone($obj->zzf_dddc1.$obj->zzf_fonc1, 'M');
      $obj->par_celular_adicional = normaliza_telefone($obj->zzf_dddc2.$obj->zzf_fonc2, 'M');
      $obj->par_tel_comercial     = normaliza_telefone($obj->zzf_dddc3.$obj->zzf_fonc3, 'T');
      $obj->ema_ins               = normaliza_email($obj->ema_ins);
      $obj->dat_cad               = normaliza_data_cadastro($obj->dat_cad);
      $obj->par_dthr_cadastro     = retornaDataHoraCadastro($obj->dat_cad, $obj->hor_cad);
      $obj->dat_nas_ins           = normaliza_data_nascimento($obj->dat_nas_ins, $obj->dat_cad);
      
      // Default phone
      if (empty($obj->par_telefone) and empty($obj->par_celular) and 
          empty($obj->par_celular_adicional) and empty($obj->par_tel_comercial)
      ) {
          $obj->par_telefone = '1136363636';  
      }
      
      // Type of CC
      if (trim(strtoupper($obj->tipo_conta)) == 'PP') {
        $obj->tipo_conta = 'P';
      } else {
        $obj->tipo_conta = 'C';
      }

      return $obj;
    }

    private function validatePartner($obj) {
      $isValid = validaCPF(trim($obj->cpf_tit_con));
      if(!$isValid) {
        $errorMsg = 'CPF INVALIDO';
        echo 'Codigo '.$obj->cod_ins.' => '.$errorMsg.$this->newLine;
        return trim($errorMsg);
      }

      if (!empty($obj->cpf_ins)) {
        $isValid = validaCPF(trim($obj->cpf_ins));
        if(!$isValid) {
          $errorMsg = 'CPF DA CONTA INVALIDO';
          echo 'Codigo '.$obj->cod_ins.' => '.$errorMsg.$this->newLine;
          return trim($errorMsg);
        }
      }

      return '';
    }

    private function getAddressOfPartner($cod_ins, $cod_cli = 0) {
      // Auxiliaries
      $cep      = '';
      $street   = '';
      $num      = '';
      $invalids = 0;
      $address  = array();

      // Table: Instaladores
      $sqlAddressByPartner = PartnerBaseClass::getAddressQueryByPartner($cod_ins);
      $this->db->query($sqlAddressByPartner);
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

      // If customer, insert the addresses
      if ($cod_cli > 0) {
        $sqlAddressByAddress= CustomerBaseClass::getAddressQueryByAddress($cod_cli); // mds_address
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

    private function storePartnerAddresses($cod_ins, $addresses) {
      $addressTable = $this->table . '_enderecos';

      // Delete Address
      $sqlDelete = 'delete from cdc_data.'.$addressTable.' where cli_codigo = '.$cod_ins;
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
              ".$cod_ins.",
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

    private function partnerExists($cod_ins) {
      $sql = 'select cli.cli_codigo from cdc_data.'.$this->table.' cli where cli.cli_codigo = :cli_codigo ';
      $this->db->query($sql);
      $this->db->bind(':cli_codigo', $cod_ins);
      $row = $this->db->single();
      if($this->db->rowCount() > 0){
        return true;
      } else {
        return false;
      }
    }
  }
?>