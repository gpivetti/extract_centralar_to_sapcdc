<?php
  class CustomerBaseClass {

    public static function getCustomerQuery($limit = 0, $type, $customer_or_origin = '', $date_start = '', $date_end = '') {
      $notInserted = true;
      $sqlLimit    = '';
      $sqlCustomer = '';
      if (!empty($customer_or_origin)) {
        if (is_numeric($customer_or_origin)) {
          $notInserted = false;
          $queryOrigem = '('.self::getCustomerOriginQuery('c.cod_cli').') as cliente_origem';
          $sqlCustomer = 'c.cod_cli = '.$customer_or_origin.' and ';
        } else {
          $queryOrigem = '"'.trim(strtoupper($customer_or_origin)) . '" as cliente_origem';
          $sqlCustomer = '('.self::getCustomerOriginQuery('c.cod_cli').') = "'.trim($customer_or_origin).'" and ';
        }
      } else {
        $queryOrigem = '('.self::getCustomerOriginQuery('c.cod_cli').') as cliente_origem';
      }
      if ($limit > 0) {
        $sqlLimit = 'limit '.$limit;
      }
      $sql = 'select  ped.num_ped, c.*, '.trim($queryOrigem).',
                      (select cli_codigo from cdc_data.clientes_errors ce where ce.cli_codigo = c.cod_cli limit 1) as customer_error
              from    centralar.pedidos ped
                      inner join centralar.clientes_cdc c on c.cod_cli = ped.cod_cli 
              where   '.$sqlCustomer.'
                      '.self::getWhereOfQueryByType($type).'
                      '.self::getBaseOfWhereQuery($date_start, $date_end, $type, $notInserted).'
              group by c.cod_cli 
              order by ped.num_ped 
              '.$sqlLimit;
      return $sql;
    }

    public static function getCustomersWithError($limit = 0, $type) {
      $queryOrigem = '('.self::getCustomerOriginQuery('c.cod_cli').') as cliente_origem';
      if ($limit > 0) {
        $sqlLimit = 'limit '.$limit;
      } else {
        $sqlLimit = '';
      }
      $sql = 'select    c.*, '.trim($queryOrigem).', ce.cli_codigo as customer_error
              from      cdc_data.clientes_errors ce
                        inner join centralar.clientes c on c.cod_cli = ce.cli_codigo 
              where     typePerson = "'.$type.'"
              order by  cli_codigo 
              '.$sqlLimit;
      return $sql;
    }

    public static function getInsertQuery($table, $type, $obj) {
      list($mktplace, $b2b, $b2c, $queryMarketplace) = self::getOriginsVariables($obj);
      if ($type == 'PF') {
        $sqlCustomer = "
          insert into cdc_data.".$table."
          (
            cli_codigo,
            cli_codigo_erp,
            cli_nome,
            cli_cpf,
            cli_rg,
            cli_data_nasc,
            cli_email,
            cli_sexo,
            cli_senha,
            cli_celular,
            cli_telefone,
            cli_origem,
            cli_marketplace,
            cli_b2b,
            cli_b2c,
            cli_canal_markeplace,
            cli_status,
            cli_dthr_cadastro,
            cli_dthr_atualizacao
          )
          values(
            ".$obj->cod_cli.",
            '".$obj->keyTOTVS."',
            '".addslashes($obj->nom_cli)."',
            '".$obj->cpf_cnpj_cli."',
            '".$obj->rg_ie_cli."',
            '".$obj->dat_nas_cli."',
            '".$obj->ema_cli."',
            ".(empty($obj->sex_cli) ? 'NULL' : "'".trim($obj->sex_cli)."'").",
            '".$obj->sen_cli."',						
            '".$obj->cel_cli."',
            '".$obj->tel_cli."',
            '".$obj->cliente_origem."',
            ".$mktplace.",
            ".$b2b.",
            ".$b2c.",
            ".$queryMarketplace.",		
            'A',
            ".(empty($obj->cli_dthr_cadastro) ? 'NULL' : "'".trim($obj->cli_dthr_cadastro)."'").",
            '".$obj->data_atualizacao."'
          );";
      } else {
        $sqlCustomer = "
          insert into cdc_data.".$table."
          (
            cli_codigo,
            cli_codigo_erp,
            cli_nome,
            cli_cnpj,
            cli_insc_est,
            cli_isento,
            cli_data_nasc,
            cli_email,
            cli_sexo,
            cli_senha,
            cli_celular,
            cli_telefone,
            cli_origem,
            cli_marketplace,
            cli_b2b,
            cli_b2c,
            cli_canal_markeplace,
            cli_status,
            cli_dthr_cadastro,
            cli_dthr_atualizacao
          )
          values(
            ".$obj->cod_cli.",
            '".$obj->keyTOTVS."',
            '".addslashes($obj->nom_cli)."',
            '".$obj->cpf_cnpj_cli."',
            '".$obj->rg_ie_cli."',
            ".(($obj->rg_ie_cli == 'ISENTO') ? 'true' : 'false').",
            '".$obj->dat_nas_cli."',
            '".$obj->ema_cli."',
            ".(empty($obj->sex_cli) ? 'NULL' : "'".trim($obj->sex_cli)."'").",
            '".$obj->sen_cli."',						
            '".$obj->cel_cli."',
            '".$obj->tel_cli."',
            '".$obj->cliente_origem."',
            ".$mktplace.",
            ".$b2b.",
            ".$b2c.",
            ".$queryMarketplace.",		
            'A',
            ".(empty($obj->cli_dthr_cadastro) ? 'NULL' : "'".trim($obj->cli_dthr_cadastro)."'").",
            '".$obj->data_atualizacao."'
          );";
      }
      return $sqlCustomer;
    }

    public static function getUpdateQuery($table, $type, $obj) {
      list($mktplace, $b2b, $b2c, $queryMarketplace) = self::getOriginsVariables($obj);
      if ($type == 'PF') {
        $sqlCustomer = "
          update 	cdc_data.".$table." as c_customer
          set		  c_customer.cli_codigo_erp 	      = '".$obj->keyTOTVS."',
                  c_customer.cli_nome 				      = '".addslashes($obj->nom_cli)."',
                  c_customer.cli_cpf 				        = '".$obj->cpf_cnpj_cli."',
                  c_customer.cli_rg 			  	      = '".$obj->rg_ie_cli."',
                  c_customer.cli_data_nasc 		      = '".$obj->dat_nas_cli."',
                  c_customer.cli_email 				      = '".$obj->ema_cli."',
                  c_customer.cli_sexo 			      	= ".(empty($obj->sex_cli) ? 'NULL' : "'".trim($obj->sex_cli)."'").",
                  c_customer.cli_senha 				      = '".$obj->sen_cli."',
                  c_customer.cli_celular 			      = '".$obj->cel_cli."',
                  c_customer.cli_telefone 		      = '".$obj->tel_cli."',
                  c_customer.cli_origem 			      = '".$obj->cliente_origem."',
                  c_customer.cli_marketplace 	      = ".$mktplace.",
                  c_customer.cli_b2b 				        = ".$b2b.",
                  c_customer.cli_b2c 				        = ".$b2c.",
                  c_customer.cli_canal_markeplace 	= ".$queryMarketplace.",
                  c_customer.cli_dthr_cadastro 	  	= ".(empty($obj->cli_dthr_cadastro) ? 'NULL' : "'".trim($obj->cli_dthr_cadastro)."'").",
                  c_customer.cli_dthr_atualizacao	  = '".$obj->data_atualizacao."'
          where	c_customer.cli_codigo = ".$obj->cod_cli.";";
      } else {
        $sqlCustomer = "
          update 	cdc_data.".$table." as c_customer
          set		  c_customer.cli_codigo_erp 	      = '".$obj->keyTOTVS."',
                  c_customer.cli_nome 				      = '".addslashes($obj->nom_cli)."',
                  c_customer.cli_cnpj				        = '".$obj->cpf_cnpj_cli."',
                  c_customer.cli_insc_est	  	      = '".$obj->rg_ie_cli."',
                  c_customer.cli_isento	  	        = ".(($obj->rg_ie_cli == 'ISENTO') ? 'true' : 'false').",
                  c_customer.cli_data_nasc 		      = '".$obj->dat_nas_cli."',
                  c_customer.cli_email 				      = '".$obj->ema_cli."',
                  c_customer.cli_sexo 			      	= ".(empty($obj->sex_cli) ? 'NULL' : "'".trim($obj->sex_cli)."'").",
                  c_customer.cli_senha 				      = '".$obj->sen_cli."',
                  c_customer.cli_celular 			      = '".$obj->cel_cli."',
                  c_customer.cli_telefone 		      = '".$obj->tel_cli."',
                  c_customer.cli_origem 			      = '".$obj->cliente_origem."',
                  c_customer.cli_marketplace 	      = ".$mktplace.",
                  c_customer.cli_b2b 				        = ".$b2b.",
                  c_customer.cli_b2c 				        = ".$b2c.",
                  c_customer.cli_canal_markeplace 	= ".$queryMarketplace.",
                  c_customer.cli_dthr_cadastro 	  	= ".(empty($obj->cli_dthr_cadastro) ? 'NULL' : "'".trim($obj->cli_dthr_cadastro)."'").",
                  c_customer.cli_dthr_atualizacao	  = '".$obj->data_atualizacao."'
          where	c_customer.cli_codigo = ".$obj->cod_cli.";";
      }
      return $sqlCustomer;
    }

    public static function getAddressQueryByCustomer($cod_cli) {
      $sqlCustomerAddress = '
        select    cli.cod_cli,
                  cli.end_cli as street,
                  cli.bai_cli as neighborhood,
                  cli.num_cli as `number`,
                  cli.ponto_referencia as referencePoint,
                  cli.com_cli as complement,
                  centralar.somenteNumeros(cli.cep_cli) as zipCode,
                  cli.cid_cli as city,
                  est.sigla as state,
                  CONCAT((
                    select 		SUBSTRING(l.MUN_NU, 1, 2) 
                    from 		centralar.LOCALIDADE l
                    where		not l.MUN_NU is null 
                          and l.UFE_SG = est.sigla 
                    limit		1 
                  ), cli.mun_nu) as cod_ibge,
                  1 as isDefault
        from      centralar.clientes  cli
                  inner join centralar.estados est on est.codigo = cli.est_cli  
        where     cod_cli = '.$cod_cli;
      return $sqlCustomerAddress;
    }

    public static function getAddressQueryByAddress($cod_cli) {
      $sqlCustomerAddress = '
        select    cli_end.cod_cli, 
                  cli_end.street, 
                  cli_end.neighborhood, 
                  cli_end.`number`, 
                  cli_end.referencePoint,
                  cli_end.complement,
                  centralar.somenteNumeros(cli_end.zipCode) as zipCode,
                  cli_end.city,
                  cli_end.state,
                  CONCAT((
                  select 	SUBSTRING(l.MUN_NU, 1, 2) 
                  from 	centralar.LOCALIDADE l
                  where	not l.MUN_NU is null 
                          and l.UFE_SG = cli_end.state 
                  limit	1 
                  ), cli.mun_nu) as cod_ibge,
                  0 as isDefault
        from	    centralar.clientes cli
                  inner join centralar.mds_client_address cli_end on cli_end.cod_cli = cli.cod_cli 
        where	    cli.cod_cli = '.$cod_cli.'
        order by  cli_end.code;';
      return $sqlCustomerAddress;
    }

    private static function getOriginsVariables($obj) {
      if ($obj->cliente_origem == "MARKETPLACE") {
        $mktplace = 'true';
        $queryMarketplace = "(
          select 		mpl.nome 
          from		  centralar.pedidos p
                    inner join centralar.mktplace_orders mo on mo.order_internal_num_ped = p.num_ped 
                    inner join centralar.mktplacesParceiroLojas mpl on mpl.parceiroId = mo.order_parceiro_id and mpl.lojaId = mo.order_loja_id 
          where		  p.cod_cli = ".$obj->cod_cli."				
          order by 	mo.mktplace_order_id asc
          limit		1						
        )";
      } else {
        $mktplace = 'false';
        $queryMarketplace = "NULL";
      }

      if ($obj->cliente_origem == "B2B") {
        $b2b = 'true';
      } else {
        $b2b = 'false';
      }

      if ($obj->cliente_origem == "B2C") {
        $b2c = 'true';
      } else {
        $b2c = 'false';
      }

      return array($mktplace, $b2b, $b2c, $queryMarketplace);
    }

    private static function getCustomerOriginQuery($cod_cli) {
      return 'select 		case
                          when not p.revendedor is null and p.revendedor > 0 then "B2B"
                          else 
                            case 
                              when p.origem = "MKTPLACE" or p.TIPO = "MKTPLACE" then "MARKETPLACE"
                              else "B2C"
                            end
                        end as orderOrigin
              from		  pedidos p 
              where 		p.cod_cli = '.$cod_cli.'
              order by 	p.num_ped asc
              limit		  1';
    }

    private static function getWhereOfQueryByType($type) {
      if ($type == 'PF') {
        return 'length(c.cpf_cnpj_cli) <= 11 and';
      } else {
        return 'length(c.cpf_cnpj_cli) > 11 and';
      }
    }

    private static function getBaseOfWhereQuery($date_start = '', $date_end = '', $type = '', $notInserted = true) {
      if (empty($date_start) or (!empty($date_start) and $date_start < BASE_DATE)) {
        $date_start = BASE_DATE;
      }
      if (!empty($date_end)){
        $whereDate = "ped.dat_ped BETWEEN '".$date_start."' and '".$date_end."'";
      } else {
        $whereDate = "ped.dat_ped >= '".$date_start."'";
      }
      if ($type == 'PF') {
        $table = 'clientes_pf';
      } else {
        $table = 'clientes_pj';
      }
      return 'c.cliente_teste != "S"
              and ped.sta_ped in ("P","F","D","E")
              '.($notInserted ? 'and c.cod_cli not in (
                select cli_codigo from cdc_data.'.$table.' cp where cp.cli_codigo = c.cod_cli
              )' : '').'
              and '.trim($whereDate);
    }

    public function deleteErrorQuery($cod_cli, $db) {
      $sqlDelete = 'delete from cdc_data.clientes_errors where cli_codigo = '.$cod_cli;
      $db->query($sqlDelete);
      $db->execute();
    }

    public function processingErrorQuery($sqlCustomer, $error, $typeQuery, $cod_cli, $typePerson, $db) {
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
          ".trim($cod_cli).",
          '".trim($typePerson)."',
          '".trim($typeQuery)."',
          '".substr(trim(addslashes($sqlCustomer)), 0, 19999)."',
          '".substr(trim(addslashes($error)), 0, 19999)."'
        )";
      $db->query($sqlError);
      $db->execute();
    }
  }
?>