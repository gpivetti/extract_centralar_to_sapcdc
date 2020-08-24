<?php
  class PartnerBaseClass {

    public static function getPartnerToConvertQuery() {
      if ($limit > 0) {
        $sqlLimit = 'limit '.$limit;
      }      
      $sql = 'insert  into centralar.parceiros_cdc 
              select 	ins.cod_ins, "N", "N", NULL
              from	  instaladores ins
              where	  ins.cadastro_status = "A"
                      and exists (
                        select 	p.revendedor 
                        from 	centralar.pedidos p 
                        where	p.revendedor = ins.cod_ins 
                                and p.sta_ped in ("P","F","D","E")
                                and p.dat_ped >= "'.BASE_DATE.'"
                      )
              order by ins.cod_ins 
              '.$sqlLimit;
      return $sql;
    }

    public static function getPartnerQuery($limit = 0, $partner = '', $date_start = '', $date_end = '') {
      $sqlLimit    = '';
      $sqlParner = '';
      
      if (!empty($partner)) {
        $sqlParner = 'ins.cod_ins = '.$partner.' and ';
      }

      if ($limit > 0) {
        $sqlLimit = 'limit '.$limit;
      }

      $sql = 'select  ins.*
              from    centralar.parceiros_cdc ins_cdc
                      inner join centralar.instaladores ins on ins.cod_ins = ins_cdc.cod_ins
              where   '.$sqlParner.'
                      ins_cdc.higienizado = "N"
              order by ins.cod_ins 
              '.$sqlLimit;
      return $sql;
    }

    public static function getInsertQuery($table, $type, $obj) {
      $sqlPartner = "
        insert into cdc_data.".$table."
        (
          par_codigo,
					par_codigo_erp,
					par_nome,
					par_cpf,
					par_funcao,					
					par_rg,
					par_rg_ufemissor,
					par_sexo,
					par_tratamento,
					par_contato_principal,
					par_segmentos,
					par_concorrentes,
					par_categorias,
					par_marcas,
					par_marcas_autorizadas,
					par_motivo_compra_concorrente,
					par_interesses,
					par_perfil_clientes,
					par_data_nasc,
					par_uf_naturalidade,
					par_telefone,
					par_celular,
					par_celular_adicional,
					par_tel_comercial,
					par_email,
					par_senha,
					par_tipo_conta,
					par_cod_banco,
					par_nome_banco,
					par_num_agencia,
					par_num_conta,
					par_cpf_conta,
					par_pis_pasesp,
					par_status,
					par_dthr_cadastro,
					par_dthr_atualizacao,
					par_dthr_ativacao
        )
        values(
          ".$obj->keyTOTVS.",
          ".$obj->nom_tit_con.",
          ".$obj->cpf_tit_con.",
          'Parceiro',
          ".$obj->rg_ins.",
          ".$obj->UFEmissor.",
          ".$obj->sex_ins.",
          ".$obj->gostaria_ser_chamado.",
          ".$obj->contato_principal.",
          (
            select 		GROUP_CONCAT(seg.nome)
            from		  centralar.instaladores_segmentos iseg
                      inner join centralar.segmentos seg on seg.id = iseg.cod_segmento
            where		  iseg.cod_ins = ".$obj->cod_ins." 
            group by 	iseg.cod_ins 
          ),
          (
            select 		GROUP_CONCAT(con.nome)
            from		  centralar.instaladores_concorrentes icon 
                      inner join centralar.concorrentes con on con.id = icon.cod_concorrente 
            where		  icon.cod_ins = ".$obj->cod_ins." 
            group by 	icon.cod_ins 
          ),
          (
            select 		GROUP_CONCAT(cat.titulo)
            from	  	centralar.instaladores_categorias icat 
                      inner join centralar.categoria cat on cat.cod_cat = icat.cod_cat 
            where		  icat.cod_ins = ".$obj->cod_ins." 
            group by 	icat.cod_ins 
          ),
          (
            select 		GROUP_CONCAT(fab.nome)
            from		  centralar.instaladores_fabricantes ifab
                      inner join centralar.fabricante fab on fab.codigo = ifab.cod_fab 
            where		  ifab.cod_ins = ".$obj->cod_ins."
            group by 	ifab.cod_ins 
          ),
          (
            select 		GROUP_CONCAT(fab.nome)
            from		  centralar.instaladores_fabricantes ifab
                      inner join centralar.fabricante fab on fab.codigo = ifab.cod_fab 
            where		  ifab.cod_ins = ".$obj->cod_ins."
                      and ifab.autorizado = 'S'
            group by 	ifab.cod_ins 
          ),
          ".$obj->motivo_compra_concorrente.",
          ".$obj->interesses.",
          ".$obj->perfil_clientes.",
          ".$obj->dat_nas_ins.",
          ".$obj->UFNatural.",
          ".$obj->par_telefone.",
          ".$obj->par_celular.",
          ".$obj->par_celular_adicional.",
          ".$obj->par_tel_comercial.",
          ".$obj->ema_ins.",
          ".$obj->sen_ins.",
          ".$obj->tipo_conta."
          bancos.ID_TOTVS,
          bancos.BANCO,
          ".$obj->nro_agencia_correto.",
          ".$obj->conta_correto.",
          ".$obj->cpf_ins.",
          ".$obj->pis_pasesp.",
          'A',
          ".(empty($obj->par_dthr_cadastro) ? 'NULL' : "'".trim($obj->par_dthr_cadastro))."'".",
          ".$obj->data_atualizacao_cadastral.",
          ".$obj->data_atualizacao_cadastral."
        );";
      return $sqlPartner;
    }

    public static function getUpdateQuery($table, $type, $obj) {
      $sqlPartner = "
        update 	cdc_data.".$table." as par
                left join centralar.bancos_relacao1 as bancos on bancos.ID = ".$obj->banco_novo."						
        set		  par.par_codigo_erp					      = ".$obj->keyTOTVS.",
                par.par_nome						          = ".$obj->nom_tit_con.",
                par.par_cpf							          = ".$obj->cpf_tit_con.",
                par.par_funcao						        = 'Parceiro',
                par.par_rg							          = ".$obj->rg_ins.",
                par.par_rg_ufemissor				      = ".$obj->UFEmissor.",
                par.par_sexo						          = ".$obj->sex_ins.",
                par.par_tratamento					      = ".$obj->gostaria_ser_chamado.",
                par.par_contato_principal			    = ".$obj->contato_principal.",
                par.par_segmentos					        = (
                                                    select 		GROUP_CONCAT(seg.nome)
                                                    from		  centralar.instaladores_segmentos iseg
                                                              inner join centralar.segmentos seg on seg.id = iseg.cod_segmento
                                                    where		  iseg.cod_ins = ".$obj->cod_ins." 
                                                    group by 	iseg.cod_ins 
                                                  ),
                par.par_concorrentes				      = (
                                                    select 		GROUP_CONCAT(con.nome)
                                                    from		  centralar.instaladores_concorrentes icon 
                                                              inner join centralar.concorrentes con on con.id = icon.cod_concorrente 
                                                    where		  icon.cod_ins = ".$obj->cod_ins." 
                                                    group by 	icon.cod_ins 
                                                  ),
                par.par_categorias					      = (
                                                    select 		GROUP_CONCAT(cat.titulo)
                                                    from	  	centralar.instaladores_categorias icat 
                                                              inner join centralar.categoria cat on cat.cod_cat = icat.cod_cat 
                                                    where		  icat.cod_ins = ".$obj->cod_ins." 
                                                    group by 	icat.cod_ins 
                                                  ),
                par.par_marcas						        = (
                                                    select 		GROUP_CONCAT(fab.nome)
                                                    from		  centralar.instaladores_fabricantes ifab
                                                              inner join centralar.fabricante fab on fab.codigo = ifab.cod_fab 
                                                    where		  ifab.cod_ins = ".$obj->cod_ins."
                                                    group by 	ifab.cod_ins 
                                                  ),
                par.par_marcas_autorizadas			  = (
                                                    select 		GROUP_CONCAT(fab.nome)
                                                    from		  centralar.instaladores_fabricantes ifab
                                                              inner join centralar.fabricante fab on fab.codigo = ifab.cod_fab 
                                                    where		  ifab.cod_ins = ".$obj->cod_ins."
                                                              and ifab.autorizado = 'S'
                                                    group by 	ifab.cod_ins 
                                                  ),
                par.par_motivo_compra_concorrente	= ".$obj->motivo_compra_concorrente.",
                par.par_interesses					      = ".$obj->interesses.",
                par.par_perfil_clientes				    = ".$obj->perfil_clientes.",
                par.par_data_nasc					        = ".$obj->dat_nas_ins.",
                par.par_uf_naturalidade				    = ".$obj->UFNatural.",
                par.par_telefone					        = ".$obj->par_telefone.",
                par.par_celular						        = ".$obj->par_celular.",
                par.par_celular_adicional			    = ".$obj->par_celular_adicional.",
                par.par_tel_comercial				      = ".$obj->par_tel_comercial.",
                par.par_email						          = ".$obj->ema_ins.",
                par.par_senha						          = ".$obj->sen_ins.",
                par.par_tipo_conta					      = ".$obj->tipo_conta."
                par.par_cod_banco					        = bancos.ID_TOTVS,
                par.par_nome_banco					      = bancos.BANCO,
                par.par_num_agencia					      = ".$obj->nro_agencia_correto.",
                par.par_num_conta					        = ".$obj->conta_correto.",
                par.par_cpf_conta					        = ".$obj->cpf_ins.",
                par.par_pis_pasesp					      = ".$obj->pis_pasesp.",
                par.par_dthr_cadastro				      = ".(empty($obj->par_dthr_cadastro) ? 'NULL' : "'".trim($obj->par_dthr_cadastro))."'".",
                par.par_dthr_atualizacao			    = ".$obj->data_atualizacao_cadastral.",
                par.par_dthr_ativacao				      = ".$obj->data_atualizacao_cadastral."
        where	par.par_codigo = ".$obj->cod_ins;
      return $sqlPartner;
    }

    public static function getAddressQueryByPartner($cod_ins) {
      $sqlPartnerAddress = '
        select    ins.cod_ins,
                  ins.end_ins as street,
                  ins.bai_ins as neighborhood,
                  ins.end_ins as `number`,
                  NULL, as referencePoint,
                  ins.com_ins as complement,
                  centralar.somenteNumeros(ins.cep_ins) as zipCode,
                  ins.cid_ins as city,
                  ins.est_ins as state,
                  CONCAT((
                    select 	SUBSTRING(l.MUN_NU, 1, 2) 
                    from 		centralar.LOCALIDADE l
                    where		not l.MUN_NU is null 
                            and l.UFE_SG = ins.est_ins 
                    limit		1 
                  ), ins.mun_nu) as cod_ibge,
                  1 as isDefault
        from      centralar.instaladores ins
        where     cod_ins = '.$cod_ins;
      return $sqlPartnerAddress;
    }

    public static function deleteError($cod_ins, $db) {
      $sqlDelete = 'update centralar.parceiros_cdc set error = "N", error_msg = NULL where cod_cli = '.$cod_ins;
      $db->query($sqlDelete);
      $db->execute();
    }

    public static function processingError($error_msg, $cod_ins, $db) {
      $sqlError = 'update centralar.parceiros_cdc set error = "S", error_msg = "'.addslashes(trim($error_msg)).'" where cod_ins = '.$cod_ins;
      $db->query($sqlError);
      $db->execute();
    }

    public static function isCustomer($cpf, $email, $db) {
      $sql = 'select 		cli_cdc.cod_cli 
              from 		  centralar.clientes cli
                        inner join centralar.clientes_cdc cli_cdc on cli.cod_cli = cli_cdc.cod_cli 
              where 		cli.cpf_cnpj_cli = :cpf_cnpj_cli or UPPER(cli.ema_cli)  = :ema_cli
              order by 	cli.cod_cli desc
              limit		  1';
      $db->query($sql);
      $db->bind(':cpf_cnpj_cli', trim($cpf));
      $db->bind(':ema_cli', trim(strtoupper($email)));
      $row = $db->single();
      if($db->rowCount() > 0){
        return $row->cod_cli;
      } else {
        return 0;
      }
    }
  }
?>