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
        $isValid = $this->validatePartner($obj);
        if ($isValid) {
          // Addresses of Partner
          $address = $this->getAddressOfPartner($obj->cod_ins);
          if (count($address) > 0) {
            $obj->address = $address;
            $partners[$obj->cod_ins] = $obj;
          } else {
            $noAddress++;
            echo 'Codigo '.$obj->cod_ins.' => ENDERECO INVALIDO'.$this->newLine;
          }
        } else {
          $invalids++;
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
      $obj->ema_ins               = normaliza_email($obj->ema_ins);
      $obj->sex_ins               = normaliza_sexo($obj->sex_ins);
      $obj->par_telefone          = normaliza_telefone($obj->zzf_dddfi.$obj->zzf_fonfi);
      $obj->par_celular           = normaliza_telefone($obj->zzf_dddc1.$obj->zzf_fonc1);
      $obj->par_celular_adicional = normaliza_telefone($obj->zzf_dddc2.$obj->zzf_fonc2);
      $obj->par_tel_comercial     = normaliza_telefone($obj->zzf_dddc3.$obj->zzf_fonc3);
      $obj->par_dthr_cadastro     = retornaDataHoraCadastro($obj->dat_cad, $obj->hor_cad);
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
        echo 'Codigo '.$obj->cod_ins.' => CPF INVALIDO'.$this->newLine;
        return false;
      }

      if (!empty($obj->cpf_ins)) {
        $isValid = validaCPF(trim($obj->cpf_ins));
        if(!$isValid) {
          echo 'Codigo '.$obj->cod_ins.' => CPF DA CONTA INVALIDO'.$this->newLine;
          return false;
        }
      }
      return true;
    }

    private function getAddressOfPartner($cod_ins) {
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
          $address    = $row;
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