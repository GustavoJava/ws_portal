function GeraOsInstalacao() {
	jQuery.ajax({
        url: 'pre_cadastro.php',
        type: 'post',
        data: {tipo_contrato:$(".field_tipo_contrato").val(),acaoAjax:'verificarGeracaoOSInstalacao'},
        beforeSend: function(){
        	$('#container_prpgera_os_instalacao').hide();
        },
        success: function(data){
            try{
                //console.log(data);
                
                // Transforma a string em objeto JSON
                var resultado = jQuery.parseJSON(data);
                
                if (resultado.gera_os_instalacao == 'I') {
                	$('#container_prpgera_os_instalacao').show();
                	return;
                }
                
            }catch(e){
                
            }
        }
    });
}

$(document).ready(function(){
	if ($(".field_tipo_contrato").val() > 0) {
		GeraOsInstalacao();
	}
	
	$(".field_tipo_contrato").change(function(){		
	    GeraOsInstalacao();	    
	});	
	
	
	
	// Verifica se: 
	// A��o � Novo
	// E tem contrato a ser Migrado
	// Ent�o altera o tipo de proposta para Migra��o de Contrato
	// E depois seta o campo Contrato base: (*) com o numero do contrato
	if (jQuery("#acao").val() == "novo" && jQuery("#migrar_contrato").val().length > 0){
		
		jQuery("#id_proposta").val(4).trigger('change');
		
		var num_contrato = jQuery("#migrar_contrato").val();
		jQuery("#prptermo_original").val(num_contrato);
		
		//jQuery("#prptermo_original").trigger('onBlur');
		
		xajax_desenhaComboMotivo(num_contrato, jQuery("#prptipo_proposta").val()); 
		
		xajax_preenchePropostaContratoAntigo(num_contrato, jQuery("#prptipo_proposta").val(), jQuery("#prptpcoid").val(), jQuery("#indice_vet").val());
		
		xajax_verificaSubsTransVeiculoRoubado(jQuery("#prptipo_proposta").val(), jQuery("#prptermo_original").val(), jQuery("#prpmsuboid").val());
		
		xajax_verificaAtualizarDadosCliente(jQuery("#prptpcoid").val())
		
		var img = '<img src="images/progress.gif" id="progress_contrato" style="display:inline;">'
		
		jQuery("#prptermo_original").parent().append(img);
		
		jQuery("#migrar_contrato").val("");
	}

	// Mant�m o valor minimo e m�ximo da taxa de instala��o em '0,00'
	jQuery("#taxa_instalacao_valor_minimo").val("0,00");
	jQuery("#taxa_instalacao_valor_maximo").val("0,00");
	
	//// Alterar entre as formas de pagamento
	$("#prpforcoid").live('change',function(){
		carregarDiasCobranca();
		
		// se o valor da taxa de instala��o estiver desabilitada, indica que j� foi pago desta forma n�o carrega as formas de pagamento
		if (!jQuery("#taxa_instalacao_valor").is(":disabled") && jQuery("#prpforcoid").val() != ""){
			getFormaPagamentoTaxaInstalacao();
		}else{
			jQuery("#taxa_instalacao_pagamento").html("<option value=''>Escolha</option>");
		}
	});
	
    $("#prpcartao_validade").bind("keydown", function(e) {
        formatar(this, '@@/@@');
    });
        
        
	// VALIDA��ES DOS CAMPOS DO CART�O DE CR�DITO
    $("#prpcartao_validade").bind("keydown", function(e) {
        formatar(this, '@@/@@');
    });
    
    $("#taxa_instalacao_validade_cartao").bind("keydown", function(e) {
        formatar(this, '@@/@@');
    });
    
    //verificar se o endere�o � o mesmo
    if (jQuery('#prpccep').val() == jQuery('#prpno_cep1').val() && jQuery('#prpccep').val() != '') {
    	jQuery('#copiar_dados_cobranca').attr('checked','checked');
    }
    if (jQuery('#prpno_cep2').val() == jQuery('#prpno_cep1').val() && jQuery('#prpno_cep2').val() != '') {
    	jQuery('#copiar_dados').attr('checked','checked');
    }
    
    // Verificar evento change na combo SubProposta
    jQuery("#id_subproposta").live("change",function(){
    	// Se alterado por uma op��o diferente de "Escolha", Chama a fun��o para verificar se tem Taxa de Instala��o
    	if (jQuery("#id_subproposta").val() != ""){
    		verificaTaxaIntalacao();
    	}
    });
    
    // Verificar evento change na combo forma de pagamento da taxa de instala��o
    jQuery("#taxa_instalacao_pagamento").live("change",function(){
    	// Se alterado por uma op��o diferente de "Escolha", Chama a fun��o para verificar se tem Taxa de Instala��o
    	if (jQuery("#taxa_instalacao_pagamento").val() != ""){
    		getConfigFormaPagamentoTaxaInstalacao();
    		
    		jQuery("#taxa_instalacao_parcelamento").val("");
    		jQuery("#taxa_instalacao_parcelamento_hidden").val("");
    		
    		var forma = jQuery("#taxa_instalacao_pagamento").val();
    		jQuery("#taxa_instalacao_pagamento_hidden").val(forma);
    	}else{
    		jQuery("#div_msg_taxa_instalacao").hide();
    		jQuery("#taxa_instalacao_campos_cartao").hide();
    	}
    });
    
    jQuery("#taxa_instalacao_parcelamento").click(function(){
    	jQuery("#taxa_instalacao_parcelamento").attr('altManual','1');
    });
    
    jQuery("#taxa_instalacao_parcelamento").focusout(function(){
    	jQuery("#taxa_instalacao_parcelamento").attr('altManual','0');
    });
    
    // Verificar evento change na combo Parcelamento da Taxa de Instala��o
    jQuery("#taxa_instalacao_parcelamento").change(function(){
    	if (jQuery("#taxa_instalacao_parcelamento :selected").val() != ""){
    		
    		calcularTaxaInstalacao(0);
    		getValorTaxaParcela();
    		
    		var parcelamento_selected = jQuery("#taxa_instalacao_parcelamento :selected").val();
    		jQuery("#taxa_instalacao_parcelamento_hidden").val(parcelamento_selected);
    	}
    });
    
    // Verificar se o CheckBox 'O Mesmo' referente ao Pagamento de Taxa de Instala��o est� marcado
    jQuery("#taxa_instalacao_copiar").change(function(){
    	if (this.checked){
    		
    		copiaFormaPagamento();
    	}else{
    		jQuery("#taxa_instalacao_parcelamento").val("");
    		jQuery("#taxa_instalacao_parcelamento_hidden").val("");
    		
    		jQuery("#taxa_instalacao_pagamento_copia").remove();
    		
    		jQuery("#taxa_instalacao_campos_cartao").hide("");
    		jQuery("#taxa_instalacao_num_cartao").val("");
    		jQuery("#taxa_instalacao_validade_cartao").val("");
            jQuery("#taxa_instalacao_nome_portador").val("");
    		
    		jQuery("#div_msg_taxa_instalacao").val("").hide();
    		
    		jQuery("#taxa_instalacao_pagamento").show();
    	}
    });
    
    // Verificar se o valor da taxa de instala��o est� entre o valor m�nimo e o padr�o 
    jQuery("#taxa_instalacao_valor").blur(function(){
    	verificarValorTaxaInstalacao();
    });
    
    
    // Atualiza campos de cart�o de cr�dito da taxa de instala��o, quando alterar no pagamento de monitoramento
    jQuery("#prpcartao").blur(function(){
    	var num_cartao = jQuery("#prpcartao").val();
    	jQuery("#taxa_instalacao_num_cartao").val(num_cartao);
    });
    
    jQuery("#prpcartao_validade").blur(function(){
    	var venc_cartao = jQuery("#prpcartao_validade").val();
    	jQuery("#taxa_instalacao_validade_cartao").val(venc_cartao);
    });

    jQuery("#nome_portador").blur(function(){
        var nome_portador = jQuery("#nome_portador").val();
        jQuery("#taxa_instalacao_nome_portador").val(nome_portador);
    });
    
    
    // Alterado o valor da Combo Vig�ncia de Contrato
    // Se o tipo de proposta � 'M'igra��o de Contrato
    // Ent�o busca o valor da Multa Rescis�ria
    jQuery("#prpprazo_contrato").live("change",function(){	
    	getValorMultaRescisoria();
    })

    jQuery('body').on('keyup blur', '#nome_portador', function() {
        jQuery(this).val(jQuery(this).val().replace(/[^A-Za-z \-]/g, '').toUpperCase());
    });

    jQuery('body').on('keyup blur', '#taxa_instalacao_nome_portador', function() {
        jQuery(this).val(jQuery(this).val().replace(/[^A-Za-z \-]/g, '').toUpperCase());
    });
    //[ORGMKTOTVS-3437] Paulo Sergio
    jQuery("#chk_prpinscricao").click(function(){
        if(jQuery("#chk_prpinscricao").is(":checked")){
            jQuery("#prpinscricao").prop( "disabled", true ); 
            jQuery("#prpinscricao").css( "background", '#cdcdcd' ); 
            jQuery("#prpinscricao").val('ISENTO');
        }else{
            jQuery("#prpinscricao").prop( "disabled", false ); 
            jQuery("#prpinscricao").css( "background", '' ); 
            jQuery("#prpinscricao").val('');
        }
    }); 
    //[ORGMKTOTVS-3437] Paulo Sergio
    
  
    
});

/**
 * Fun��o: Buscar o valor de acordo com o numero de parcelas selecionado
 */
function getValorTaxaParcela(){
	
	var busca = jQuery("#taxa_instalacao_parcelamento").attr('altManual');
	var num_parcelas = jQuery("#taxa_instalacao_parcelamento").val();
	
	jQuery("#taxa_instalacao_parcelamento").attr('altManual','0');
	
	if (busca == 1 && num_parcelas > 0) {
		
		var id_subproposta = "";
		var id_proposta = "";
		var prptpcoid = "";
		var prpeqcoid = "";
		
		if (jQuery("#id_subproposta").length > 0){
			id_subproposta = jQuery("#id_subproposta").val();
		}
		if (jQuery("#id_proposta").length > 0){
			id_proposta = jQuery("#id_proposta").val();
		}
		if (jQuery("#prptpcoid").length > 0){
			prptpcoid = jQuery("#prptpcoid").val();
		}
		if (jQuery("#prpeqcoid").length > 0){
			prpeqcoid = jQuery("#prpeqcoid").val();
		}
		
		var juros = jQuery("#taxa_instalacao_parcelamento :selected").attr('rel');
		
		jQuery.ajax({
			url: 'pre_cadastro.php',
			type: 'post',
			data: {
				acaoAjax : 'getValorTaxaParcela',
				id_proposta: id_proposta,
				id_subproposta: id_subproposta,
				prptpcoid: prptpcoid,
				prpeqcoid: prpeqcoid,
				num_parcelas: num_parcelas,
				juros: juros
			},
			success: function(data){

				var resultado = jQuery.parseJSON(data);
				
				if (resultado.erro == 0){
					jQuery("#taxa_instalacao_valor_unitario").val(resultado.tpivalor);
					jQuery("#taxa_instalacao_valor").val(resultado.tpivalor);
					
					calcularTaxaInstalacao(0);
					
				} else {
					criaAlerta(resultado.msg);
					return false;
				}
			}		
		});
	}
}


/**
 * Fun��o: Buscar o valor da multa rescis�ria
 */
function getValorMultaRescisoria(){
	if (jQuery("#prpprazo_contrato").val().length == 0 || jQuery("#prptipo_proposta").val() != "M"){
		return false;
	}
	jQuery.ajax({
		url: 'pre_cadastro.php',
		type: 'post',
		data: {
				acaoAjax : 'getValorMultaRescisoria',
				vigencia_contrato: jQuery("#prpprazo_contrato").val()
			},
		success: function(data){
		
			var resultado = jQuery.parseJSON(data);
			
			if (resultado.erro == 0){
				jQuery("#prpagmulta_rescissoria").val(resultado.multa_rescisoria);
			} else {
				criaAlerta(resultado.msg);
			}
		}		
	});
}

/**
 * Fun��o: Verificar se o valor da multa rescis�ria est� entre o valor 0,00 e 100,00 %
 */
function validaValorMultaRescisoria(valor) {
	
	if (valor >= 0 && valor <= 10000){
		jQuery("#prpagmulta_rescissoria").css("background-color","#FFFFFF");
		return true;
	} else{
    	alert('A porcentagem da multa rescis�ria deve ser entre 0,00 at� 100,00 %.');
		jQuery("#prpagmulta_rescissoria").css("background-color","#FFFFC0");
		
		if (jQuery("#prpprazo_contrato").val().length == 0){
			jQuery("#prpagmulta_rescissoria").val("");
		}

		getValorMultaRescisoria();
		return false;
	}
}


/**
 * Fun��o: Verificar se o valor da taxa de instala��o est� entre o valor m�nimo e o padr�o  
 */
function verificarValorTaxaInstalacao(){
	var valor = jQuery("#taxa_instalacao_valor").val();
	
	var max = jQuery("#taxa_instalacao_valor_maximo").val();
	var min = jQuery("#taxa_instalacao_valor_minimo").val();
	
	jQuery("#taxa_instalacao_valor_unitario").val(valor);
	
	min_temp = min.replace(".","");
	min_temp = min.replace(",","");	
	
	min_temp = parseInt(min_temp);
	
	valor = valor.replace(".","");
	valor = valor.replace(",","");		
	
	valor = parseInt(valor);
		
	jQuery("#div_msg_taxa_instalacao").hide();
	
	if (valor < min_temp){
		jQuery("#div_msg_taxa_instalacao").text("O valor da taxa de instala��o deve ser no m�nimo R$: "+min+".");
		jQuery("#div_msg_taxa_instalacao").show();
	}else{
		calcularTaxaInstalacao(1);
	}
}

/**
 * Fun��o: Verificar se tem SubProposta selecionada.
 * 				Se possui chama a fun��o de verificaTaxaIntalacao
 */

function verificaSubProposta(){
	var id_subproposta = "";
			
	// Pega o valor selecionado na combo SubProposta
	if (jQuery("#id_subproposta").length > 0 && jQuery("#id_subproposta").is(':visible') == true){
		id_subproposta = jQuery("#id_subproposta").val();
		id_subproposta = parseInt (id_subproposta);
		
		// Chama a fun��o para verificar se tem taxa de instala��o.
		if (id_subproposta > 0){
			setTimeout("verificaTaxaIntalacao()", 500);
			//verificaTaxaIntalacao();
			
		}else{
			jQuery('#dvValorTaxaInstalacao').show();
                        jQuery('#valorTaxaInstalacao').removeAttr("disabled");//Mantis 5937
			jQuery("#taxa_instalacao_campos").hide();
		}
	}else{
		jQuery('#dvValorTaxaInstalacao').show();
                jQuery('#valorTaxaInstalacao').removeAttr("disabled");//Mantis 5937
		jQuery("#taxa_instalacao_campos").hide();
	}
	
}

/**
 * Fun��o: Verificar se deve habilitar campos Taxa Instala��o.
 */
function verificaTaxaIntalacao(){
	//alert("Criar a fun��o verificar Taxa Instala��o. ID: " + jQuery("#id_subproposta").val())
	
	var id_subproposta = "";
	var id_proposta = "";
	var prptpcoid = "";
	var prpeqcoid = "";
	
	if (jQuery("#id_subproposta").length > 0){
		id_subproposta = jQuery("#id_subproposta").val();
	}
	if (jQuery("#id_proposta").length > 0){
		id_proposta = jQuery("#id_proposta").val();
	}
	if (jQuery("#prptpcoid").length > 0){
		prptpcoid = jQuery("#prptpcoid").val();
	}
	if (jQuery("#prpeqcoid").length > 0){
		prpeqcoid = jQuery("#prpeqcoid").val();
	}
	
	var id_subproposta = jQuery("#id_subproposta").val();
	jQuery.ajax({
		url: 'pre_cadastro.php',
		type: 'post',
		data: {acaoAjax : 'verificaTaxaIntalacao', id_subproposta : id_subproposta},
		
		success: function(data){
			
			var resultado = jQuery.parseJSON(data);
			
			// Verificar se houve erro
			if (resultado.erro == 1){
				criaAlerta(resultado.msg);
			}else{
				// Verificar se deve habilitar os campos de taxa de instala��o
				if (resultado.taxaInstalacao == true){
					
					if (jQuery("#prpforcoid").val() != ""){
						getFormaPagamentoTaxaInstalacao(); // Busca as formar de pagamento para taxa de instala��o
					}
					
					jQuery('#dvValorTaxaInstalacao').hide();
					jQuery('#valorTaxaInstalacao').attr("disabled","disabled");//Mantis 5937
                                        
					jQuery("#taxa_instalacao_campos").show();
					
					if (jQuery("#prpoid").val() != ""){
						
						// verifica se titulo est� pago
						jQuery.ajax({
							url: 'pre_cadastro.php',
							type: 'post',
							data: {
								acaoAjax 	: 'tituloPago',
								prpoid		: jQuery("#prpoid").val(),
								id_proposta: id_proposta,
								id_subproposta: id_subproposta,
								prptpcoid: prptpcoid,
								prpeqcoid: prpeqcoid
							},
							success: function(data){		
								
								
								var resultado = jQuery.parseJSON(data);
								
								jQuery("#taxa_instalacao_valor").val(resultado.valor);
								jQuery("#taxa_instalacao_valor_unitario").val(resultado.valor);
								jQuery("#taxa_instalacao_pagamento").val(resultado.forma);
								jQuery("#taxa_instalacao_parcelamento_hidden").val(resultado.parcela);
								jQuery("#taxa_instalacao_pagamento_hidden").val(resultado.forma);
							
								var forma = resultado.forma;
								
								setTimeout("jQuery('#taxa_instalacao_pagamento').val('"+forma+"')", 1500);
								setTimeout("jQuery('#taxa_instalacao_pagamento_hidden').val('"+forma+"')", 1500);
								
								setTimeout("jQuery('#taxa_instalacao_parcelamento').val('"+resultado.parcela+"')", 1500);
								setTimeout("jQuery('#taxa_instalacao_parcelamento_hidden').val('"+resultado.parcela+"')", 1500);
								
								
								setTimeout("getConfigFormaPagamentoTaxaInstalacao()", 2000);
								if (resultado.tituloPago == 1){
																
									jQuery("#taxa_instalacao_valor").attr("disabled","disabled");
									jQuery("#taxa_instalacao_pagamento").attr("disabled","disabled");
									jQuery("#taxa_instalacao_parcelamento").attr("disabled","disabled");
									jQuery("#taxa_instalacao_codigo_seguranca").attr("disabled","disabled");
									
									jQuery("#taxa_instalacao_parcela").val(resultado.valor);
									
									jQuery("#taxa_instalacao_copiar").parent().parent().hide();
									
									jQuery("#div_msg_taxa_instalacao").text("Taxa de instala��o quitada.").show();
								}else{
									// Busca valor da taxa de instala��o
									getValorTaxaInstalacao();
								}
							}
						});
					}else{
						getValorTaxaInstalacao();
					}
					
				}else{
					jQuery('#dvValorTaxaInstalacao').show();
                                        jQuery('#valorTaxaInstalacao').removeAttr("disabled");//Mantis 5937
					jQuery("#taxa_instalacao_campos").hide();
				}
			}			
		}		
	});
}

/**
 * Fun��o: Carregar as poss�veis formas de pagamento para a taxa de instala��o
 */
function getFormaPagamentoTaxaInstalacao(){		
	
	var id_subproposta = "";
	var id_proposta = "";
	var prptpcoid = "";
	var prpeqcoid = "";
	
	if (jQuery("#id_subproposta").length > 0){
		id_subproposta = jQuery("#id_subproposta").val();
	}
	if (jQuery("#id_proposta").length > 0){
		id_proposta = jQuery("#id_proposta").val();
	}
	if (jQuery("#prptpcoid").length > 0){
		prptpcoid = jQuery("#prptpcoid").val();
	}
	if (jQuery("#prpeqcoid").length > 0){
		prpeqcoid = jQuery("#prpeqcoid").val();
	}
	
	jQuery.ajax({
		url: 'pre_cadastro.php',
		type: 'post',
		data: {
				acaoAjax : 'getFormaPagamentoTaxaInstalacao',
				forma_pagamento: jQuery("#prpforcoid").val(),
				id_proposta: id_proposta,
				id_subproposta: id_subproposta,
				prptpcoid: prptpcoid,
				prpeqcoid: prpeqcoid
			},
		success: function(data){
					
			var forma_pagamento_selecionada = jQuery("#taxa_instalacao_pagamento").val();
			
			var resultado = jQuery.parseJSON(data);
			
			var copia = false;
			
			// Verificar se houve erro
			if (resultado.erro == 1){
				criaAlerta(resultado.msg);
			}else{
				jQuery("#taxa_instalacao_pagamento").html("<option value=''>Escolha</option>");
				// Preenche com de forma de pagamento taxa de instala��o
				jQuery.each(resultado.formaPagamento, function(index,value){
					jQuery("#taxa_instalacao_pagamento").append("<option value='"+value.offcforcoid+"'>"+value.forcnome+"</option> ");
				
					if (jQuery("#prpforcoid").val() == value.offcforcoid){
						copia = true;
					}
				});
				jQuery("#taxa_instalacao_pagamento").val(jQuery("#taxa_instalacao_pagamento_hidden").val()).change();
				
				if (forma_pagamento_selecionada != ""){
					jQuery("#taxa_instalacao_pagamento").val(forma_pagamento_selecionada).change();
				}
				
				// Se estiver marcado o mesmo
				if (jQuery("#taxa_instalacao_copiar").is(":checked") && copia == true){
					
					jQuery("#taxa_instalacao_pagamento_copia").remove();
					copiaFormaPagamento();
				}else{
					jQuery("#taxa_instalacao_copiar").removeAttr("checked");
					jQuery("#taxa_instalacao_pagamento_copia").remove();
					jQuery("#taxa_instalacao_pagamento").show();
				}
			}	
		}		
	});
}

/**
 * Fun��o: Carregar Valor da Taxa de Instala��o
 */
function getValorTaxaInstalacao(){		
	
	var id_subproposta = "";
	var id_proposta = "";
	var prptpcoid = "";
	var prpeqcoid = "";
	
	if (jQuery("#id_subproposta").length > 0){
		id_subproposta = jQuery("#id_subproposta").val();
	}
	if (jQuery("#id_proposta").length > 0){
		id_proposta = jQuery("#id_proposta").val();
	}
	if (jQuery("#prptpcoid").length > 0){
		prptpcoid = jQuery("#prptpcoid").val();
	}
	if (jQuery("#prpeqcoid").length > 0){
		prpeqcoid = jQuery("#prpeqcoid").val();
	}
	
	jQuery.ajax({
		url: 'pre_cadastro.php',
		type: 'post',
		data: {
				acaoAjax : 'getValorTaxaInstalacao',
				contrato_numero : jQuery("#termo_veiculo").val(),
				prpoid			: jQuery("#prpoid").val(),
				forma_pagamento : jQuery("#taxa_instalacao_pagamento").val(),
				id_proposta: id_proposta,
				id_subproposta: id_subproposta,
				prptpcoid: prptpcoid,
				prpeqcoid: prpeqcoid
			},
		success: function(data){
			
			var resultado = jQuery.parseJSON(data);
			
			// Verificar se houve erro
			if (resultado.erro == 1){
				criaAlerta(resultado.msg);
			}else{
				if (jQuery("#taxa_instalacao_valor").val() == "0,00" || jQuery("#taxa_instalacao_valor").val() == ""){
					// Preenche o valor da taxa de instala��o
										
					jQuery("#taxa_instalacao_valor_maximo").val(resultado.padrao.tpivalor);
					jQuery("#taxa_instalacao_valor_minimo").val(resultado.padrao.tpivalor_minimo);
				}
								
				if (resultado.contrato != ""){
					jQuery("#taxa_instalacao_valor").val(resultado.contrato.valorTaxaContrato);
					jQuery("#taxa_instalacao_valor_unitario").val(resultado.contrato.valorTaxaContrato);
					jQuery("#taxa_instalacao_pagamento").val(resultado.contrato.formaPagamento).change();
					jQuery("#taxa_instalacao_parcelamento_hidden").val(resultado.contrato.parcela);
					jQuery("#taxa_instalacao_pagamento_hidden").val(resultado.contrato.formaPagamento);
					
					if (resultado.contrato.tituloPago == 1){
						jQuery("#taxa_instalacao_valor").attr("disabled","disabled");
						jQuery("#taxa_instalacao_pagamento").attr("disabled","disabled");
						jQuery("#taxa_instalacao_parcelamento").attr("disabled","disabled");
						jQuery("#taxa_instalacao_codigo_seguranca").attr("disabled","disabled");
						
						jQuery("#taxa_instalacao_copiar").parent().parent().hide();
						
						jQuery("#div_msg_taxa_instalacao").text("Taxa de instala��o quitada.").show();
					}
					
					// N�o exibir mensagem padr�o 
					//jQuery("#exibeMsg").val(0);
					getConfigFormaPagamentoTaxaInstalacao();			
				}else{
					
					if (jQuery("#taxa_instalacao_hidden").val() != ""){
						
						var valor = jQuery("#taxa_instalacao_hidden").val();
						var parce = jQuery("#taxa_instalacao_parcelamento_hidden").val();
						var forma = jQuery("#taxa_instalacao_pagamento_hidden").val();
						
						
						jQuery("#taxa_instalacao_valor").val(valor);
						jQuery("#taxa_instalacao_valor_unitario").val(valor);
						jQuery("#taxa_instalacao_pagamento").val(forma);
						jQuery("#taxa_instalacao_parcelamento_hidden").val(parce);
						jQuery("#taxa_instalacao_pagamento_hidden").val(forma);
						
					}else if (jQuery("#taxa_instalacao_valor").val() == "0,00" || jQuery("#taxa_instalacao_valor").val() == ""){
						jQuery("#taxa_instalacao_valor_unitario").val(resultado.padrao.tpivalor);
						jQuery("#taxa_instalacao_valor").val(resultado.padrao.tpivalor);
					}
				
					// Calcula a taxa de instala��o
					calcularTaxaInstalacao(0);
				}
			}
		}		
	});	
}

/**
 * Fun��o: Buscar as configura��es da forma de pagamento da taxa de instala��o selecionada. (Numero de parcelas; E se � cart�o de cr�dito)
 */
function getConfigFormaPagamentoTaxaInstalacao(){	
	
	var id_subproposta = "";
	var id_proposta = "";
	var prptpcoid = "";
	var prpeqcoid = "";
	
	if (jQuery("#id_subproposta").length > 0){
		id_subproposta = jQuery("#id_subproposta").val();
	}
	if (jQuery("#id_proposta").length > 0){
		id_proposta = jQuery("#id_proposta").val();
	}
	if (jQuery("#prptpcoid").length > 0){
		prptpcoid = jQuery("#prptpcoid").val();
	}
	if (jQuery("#prpeqcoid").length > 0){
		prpeqcoid = jQuery("#prpeqcoid").val();
	}
	
	if (jQuery("#taxa_instalacao_pagamento").val() == ""){
		return false;
	}
	
	jQuery.ajax({
		url: 'pre_cadastro.php',
		type: 'post',
		data: {
				acaoAjax : 'getConfigFormaPagamento', 
				forma_pagamento : jQuery("#taxa_instalacao_pagamento").val(),
				id_proposta: id_proposta,
				id_subproposta: id_subproposta,
				prptpcoid: prptpcoid,
				prpeqcoid: prpeqcoid
			},
		success: function(data){
			
			var resultado = jQuery.parseJSON(data);
			
			// Verificar se houve erro
			if (resultado.erro == 1){
				criaAlerta(resultado.msg);
			}else{
				jQuery("#taxa_instalacao_parcelamento").html("<option value=''>Escolha</option>")
					
				// Chama a fun��o para exibir ou ocultar campos do cart�o de cr�dito para taxa de instala��o
				camposCartaoCredito(resultado.credito);
				
				// Chama fun��o para preencher combo de parcelas
				carregarParcelas(resultado.parcelas);
			}
		}		
	});	
}

/**
 * Fun��o: Verificar se deve exibir campos referente ao cart�o de cr�dito para a taxa de instala��o
 */
function camposCartaoCredito(credito){
	// Forma do tipo cart�o de cr�dito
	if (credito == 't'){
		jQuery("#taxa_instalacao_campos_cartao").show();
		
		//if (jQuery("#exibeMsg").val() != 0){
		
		// Se o campo n�o estiver desabilitado, ainda n�o foi pago o titulo, e exibe a mensagem
		if (!jQuery("#taxa_instalacao_valor").is(":disabled")){
			jQuery("#div_msg_taxa_instalacao").text("Ser�o utilizados os mesmos dados do cart�o de cr�dito utilizado no monitoramento.").show();
		}else{
			// campo desabilitado -> titulo pago
			jQuery("#div_msg_taxa_instalacao").text("Taxa de instala��o quitada.").show();
		}
		
		
		//}
		
		var numCartaoCreditoMonitoramento = jQuery("#prpcartao").val();
		var validadeCartaoCreditoMonitoramento = jQuery("#prpcartao_validade").val();
        var nomePortadorCartaoCreditoMonitoramento = jQuery("#nome_portador").val();
		
		jQuery("#taxa_instalacao_num_cartao").val(numCartaoCreditoMonitoramento);
		jQuery("#taxa_instalacao_validade_cartao").val(validadeCartaoCreditoMonitoramento);
        jQuery("#taxa_instalacao_nome_portador").val(nomePortadorCartaoCreditoMonitoramento);
		
		jQuery("#taxa_instalacao_num_cartao").attr('disabled','disabled');
		jQuery("#taxa_instalacao_validade_cartao").attr('disabled','disabled');
        jQuery("#taxa_instalacao_nome_portador").attr('disabled','disabled');
		
		//jQuery("#exibeMsg").val(1);
	}else{
		jQuery("#taxa_instalacao_campos_cartao").hide();
		
		if (!jQuery("#taxa_instalacao_valor").is(":disabled")){
			jQuery("#div_msg_taxa_instalacao").text("").hide();
		}
	}
}

/**
 * Fun��o: Realizar calculo da taxa de instala��o e parcelamento
 * Vari�veis: 
 * 			valorTaxa: 		   	Valor da taxa (com possivel altera��o do usu�rio)
 * 			valorTaxaUnitario:	Valor por veiculo
 * 			valorTaxaMinimo: 	Valor m�nimo que pode ser pago (desconto)
 * 			qntdVeiculos:    	Quantidade de veiculos salvo (em processo de cadastro)
 * 			qntdVeiculosCalculado: Quantidade de veiculos que foi realizado o calculo ( para determinar quando houve exclus�o de algum veiculo)
 * 			numParcelas:    	Quantidade de parcelas escolhida para a taxa de instala��o
 * 			cobraJuros:	     	Identifica se a parcela escolhida possui cobranca juros
 * 			alteradoManual:		Identifica se o valor da taxa foi alterado manualmente pelo usu�rio (1 = TRUE - 0 = FALSE)
 */

function calcularTaxaInstalacao(alteradoManual){
	var quantidade_veiculos = jQuery("#quantidade_veiculos").val();
	
	//Verifica se possui algum ve�culolo
	if (quantidade_veiculos > 0){
		
		// Pega os valores para realizar calculo
		var valorTaxa		 	= jQuery("#taxa_instalacao_valor").val();
		var valorTaxaUnitario	= jQuery("#taxa_instalacao_valor_unitario").val();
		var valorTaxaMaximo		= jQuery("#taxa_instalacao_valor_maximo").val();
		var valorTaxaMinimo 	= jQuery("#taxa_instalacao_valor_minimo").val();
		var qntdVeiculos		= jQuery("#quantidade_veiculos").val();
		var qntdVeiculosCalculado= jQuery("#taxa_instalacao_qntd_veiculos").val();
		
		var numParcelas = jQuery("#taxa_instalacao_parcelamento :selected").val();
		var cobraJuros 	= jQuery("#taxa_instalacao_parcelamento :selected").attr("rel");
			
		var id_subproposta = "";
		var id_proposta = "";
		var prptpcoid = "";
		var prpeqcoid = "";
		
		if (jQuery("#id_subproposta").length > 0){
			id_subproposta = jQuery("#id_subproposta").val();
		}
		if (jQuery("#id_proposta").length > 0){
			id_proposta = jQuery("#id_proposta").val();
		}
		if (jQuery("#prptpcoid").length > 0){
			prptpcoid = jQuery("#prptpcoid").val();
		}
		if (jQuery("#prpeqcoid").length > 0){
			prpeqcoid = jQuery("#prpeqcoid").val();
		}
		
		jQuery.ajax({
			url: 'pre_cadastro.php',
			type: 'post',
			data: {
					acaoAjax : 'calculaTaxaInstalacaoVeiculos', 
					valorTaxa: valorTaxa,
					valorTaxaUnitario: valorTaxaUnitario,
					valorTaxaMaximo: valorTaxaMaximo,
					valorTaxaMinimo: valorTaxaMinimo,
					qntdVeiculos: qntdVeiculos,
					numParcelas: numParcelas,
					cobraJuros: cobraJuros,
					alteradoManual: alteradoManual,
					id_proposta: id_proposta,
					id_subproposta: id_subproposta,
					prptpcoid: prptpcoid,
					prpeqcoid: prpeqcoid
				},
			success: function(data){
				var resultado = jQuery.parseJSON(data);			
				// Preenche os campos
				jQuery("#taxa_instalacao_valor").val(resultado.valor_total_taxa);
				jQuery("#taxa_instalacao_valor_unitario").val(resultado.valor_taxa_unitario);
				jQuery("#taxa_instalacao_valor_minimo").val(resultado.valor_total_minimo);
				jQuery("#taxa_instalacao_valor_maximo").val(resultado.valor_total_maximo);
				
				jQuery("#taxa_instalacao_qntd_veiculos").val(resultado.quantidade_veiculos);				

				jQuery("#taxa_instalacao_parcela").val(resultado.valor_parcela);
				jQuery("#taxa_instalacao_parcela").attr("disabled","disabled");
			}		
		});	
	}else{
		var valor_calculado = "0,00";
		jQuery("#taxa_instalacao_valor").val(valor_calculado);
		jQuery("#taxa_instalacao_parcela").val(valor_calculado);
		jQuery("#taxa_instalacao_parcela").attr("disabled","disabled");
	}
}

/**
 * Fun��o: Verificar e copiar a forma de pagamento para a taxa de instala��o
 */
function copiaFormaPagamento(){
	jQuery("#div_msg_taxa_instalacao").hide();
	
	jQuery("#taxa_instalacao_pagamento_copia").remove();
	jQuery("#taxa_instalacao_copiar").attr("disabled","disabled");
	
	// Se a forma de pagamento do monitoramento n�o estiver preenchida
	if (jQuery("#prpforcoid :selected").val() == ""){
		jQuery("#div_msg_taxa_instalacao").text("A forma de pagamento de monitoramento deve estar preenchida, para utilizar na taxa de instala��o.");
		jQuery("#div_msg_taxa_instalacao").show();
		//jQuery('html, body').animate({scrollTop:0}, 'slow');
		jQuery("#taxa_instalacao_copiar").removeAttr("checked");
		return false;
	}
	
	// Pegar a forma de pagamento do monitoramento e copiar para a taxa de instala��o
	var idPagamentoMonitoramento = jQuery("#prpforcoid :selected").val();
	var textoPagamentoMonitoramento = jQuery("#prpforcoid :selected").text();

	var id_subproposta = "";
	var id_proposta = "";
	var prptpcoid = "";
	var prpeqcoid = "";
	
	if (jQuery("#id_subproposta").length > 0){
		id_subproposta = jQuery("#id_subproposta").val();
	}
	if (jQuery("#id_proposta").length > 0){
		id_proposta = jQuery("#id_proposta").val();
	}
	if (jQuery("#prptpcoid").length > 0){
		prptpcoid = jQuery("#prptpcoid").val();
	}
	if (jQuery("#prpeqcoid").length > 0){
		prpeqcoid = jQuery("#prpeqcoid").val();
	}
	
	// Verificar se a forma a ser c�pia pode ser utilizada na taxa de instala��o
	jQuery.ajax({
		url: 'pre_cadastro.php',
		type: 'post',
		data: {
				acaoAjax : 'getConfigFormaPagamento',
				forma_pagamento : idPagamentoMonitoramento,
				id_proposta: id_proposta,
				id_subproposta: id_subproposta,
				prptpcoid: prptpcoid,
				prpeqcoid: prpeqcoid
			},
		success: function(data){
								
			var resultado = jQuery.parseJSON(data);
			
			// Verificar se houve erro
			if (resultado.codigoErro == 1){
				jQuery("#div_msg_taxa_instalacao").text("A forma de pagamento selecionada no monitoramento n�o pode ser utilizada na taxa de instala��o.");
				jQuery("#div_msg_taxa_instalacao").show();
				//jQuery('html, body').animate({scrollTop:0}, 'slow');
				jQuery("#taxa_instalacao_copiar").removeAttr("checked");
				return false;
			}else{
				
				// Pegar dados referente ao cart�o de cr�dito
				var numCartaoCreditoMonitoramento = jQuery("#prpcartao").val();
				var validadeCartaoCreditoMonitoramento = jQuery("#prpcartao_validade").val();
                var nomePortadorCartaoCreditoMonitoramento = jQuery("#nome_portador").val();
				
				jQuery("#taxa_instalacao_pagamento").hide().val("");
				
				// Cola os valores do monitoramento
				jQuery("#taxa_instalacao_pagamento").parent().append("<select id='taxa_instalacao_pagamento_copia' name='taxa_instalacao_pagamento_copia'></select>");
				jQuery("#taxa_instalacao_pagamento_copia").html("<option value='"+idPagamentoMonitoramento+"'>"+textoPagamentoMonitoramento+"</option>")
				
				jQuery("#taxa_instalacao_num_cartao").val(numCartaoCreditoMonitoramento);
				jQuery("#taxa_instalacao_validade_cartao").val(validadeCartaoCreditoMonitoramento);
                jQuery("#taxa_instalacao_nome_portador").val(nomePortadorCartaoCreditoMonitoramento);
				
				// Chama a fun��o para exibir ou ocultar campos do cart�o de cr�dito para taxa de instala��o
				camposCartaoCredito(resultado.credito);
				
				// Chama fun��o para preencher combo de parcelas
				carregarParcelas(resultado.parcelas);
				
				// Desabilita os campos para edi��o
				jQuery("#taxa_instalacao_pagamento_copia").attr("readonly","readonly");
				jQuery("#taxa_instalacao_num_cartao").attr("disabled","disabled");
				jQuery("#taxa_instalacao_validade_cartao").attr("disabled","disabled");
                jQuery("#taxa_instalacao_nome_portador").attr("disabled","disabled");
			}
			
			jQuery("#taxa_instalacao_copiar").removeAttr("disabled");
		}		
	});
}

/**
 * Fun��o: Preenche combo com op��es de parcelamento
 * Parametro: parcelamento -> Array com a quantidade de parcelas e valor de juros
 */

function carregarParcelas(parcelamento){
	var numParcelasSemJuros = parcelamento.maxparcsemjuros;
	var numParcelasComJuros = parcelamento.maxparccomjuros;
	
	var opcao = "";
	var juros = false; // Informa se a op��o possui juros
	
	jQuery("#taxa_instalacao_parcelamento").html("<option value='' rel='false' >Escolha</option>");
	// Parcelamento Sem Juros
	for(var i = 1; i <= numParcelasSemJuros; i++){
		if (i == 1){
			opcao = "� Vista";
		}else{
			opcao = i + "X Sem Juros";
		}
		jQuery("#taxa_instalacao_parcelamento").append("<option value='"+i+"' rel='"+juros+"' >"+opcao+"</option>");
	}
	
	juros = true; // Se houver outras parcelas s�o definidas com juros
	// Parcelamento Com Juros (Somente se numero de parcelas com juros for maior que o numero de parcelas sem juros)
	if (numParcelasComJuros > numParcelasSemJuros){
		for(var i = numParcelasSemJuros + 1; i <= numParcelasComJuros; i++){
			opcao = i + "X Com Juros";
			
			jQuery("#taxa_instalacao_parcelamento").append("<option value='"+i+"' rel='"+juros+"' >"+opcao+"</option>")
		}
	}
	
	if (jQuery("#taxa_instalacao_parcelamento_hidden").val() != ""){
		var parcela = jQuery("#taxa_instalacao_parcelamento_hidden").val();
		jQuery("#taxa_instalacao_parcelamento").val(parcela).change();
	}
}

function carregarDiasCobranca(carregamento){
	//alert('para carregar os dias chama funcao buscarDiaCobranca() dentro do modulo prn_manutencao_forma_cobranca_cliente');
	if (jQuery('#prpforcoid').val() == ""){
		return false;
	}

	jQuery.ajax({
        url: 'prn_manutencao_forma_cobranca_cliente.php', //// arquivo da requisi��o
        type: 'post',       //// enviar parametro via post
        dataType: 'json',   //// tipo de retorno
        data: 'acao=buscarDiaCobranca&forcoid='+jQuery('#prpforcoid').val(),  //// parametros
        beforeSend: function(){
        	//Loading de carregamento do select de data de vencimento
        	jQuery('#prpdia_vcto_boleto').after('<img src="images/progress4.gif" id="loading_data" />');
            
            //Manter select data vencimento desabilitado enquanto carrega as datas
            jQuery('#prpdia_vcto_boleto').attr('disabled','disabled');
        },
        success: function(data){
        	        	
        	 //var resultado = jQuery.parseJSON(data);
             
             jQuery('#prpdia_vcto_boleto').html('<option value="">Escolha</option>');                                                                         
             
             jQuery.each(data, function(){
            	 jQuery("#prpdia_vcto_boleto").append(jQuery('<option></option>').attr("value", this.dia_pagamento).text(this.dia_pagamento));                                               
             });
             
             if (carregamento == 1){
	             var dataVencimento = jQuery("#dataVencimentoAtualCliente").val();
	             jQuery("#prpdia_vcto_boleto").val(dataVencimento);  
             }
            
             //jQuery("#dataVencimentoAtual").remove();
             
             // Libera��o do campo para preencher a data de vencimento
             jQuery('#prpdia_vcto_boleto').removeAttr('disabled');
             
             // Remove o loading do carregamento da data de vencimento
             jQuery('#loading_data').remove();
        	
        }
    });
}

/* Servi�os agregados */

/*
 * Fun��o para setar valor do servi�o conforme valor da obriga��o financeira correspondente
*/
function setValorServicoOf(obroid) {
    xajax_setValorServicoOf(obroid);
}

/*
 * Fun��o para setar valor do servi�o como embutido ou n�o ao valor do monitoramento
*/
function setValorServicoMonitoramento(obroid) {
    if(document.getElementById('servvalor_agregado_monitoramento').checked){
         document.getElementById('servvalor').value = '0,00';
    }else{
        xajax_setValorServicoOf(obroid);
    }
}

function adicionaServicoOpcional(){

	var teveErro = false;
    
	document.getElementById('servvalor').className 	= 'inputNormal';
	document.getElementById('servcontrato').className 	= 'inputNormal';
	
	if(document.getElementById('servvalor').value == ''){
		teveErro = true;
		document.getElementById('servvalor').className = 'inputError';
	}
	if(document.getElementById('servcontrato').value == ''){
		teveErro = true;
		document.getElementById('servcontrato').className = 'inputError';
	}
    
	if(teveErro){
		alert('Existem campos de preenchimento obrigat�rio que n�o foram preenchidos!');
		return;
	}
    // Chamada fun��o ajax
    xajax_adicionaServicoOpcional(xajax.getFormValues('frm_edicao'));

}

function excluiServicoOpcional()
{
    if (confirm("Deseja realmente excluir o servi�o?")) {
        xajax_exclui_servico(indice_vet, posicao_servico, prpoid);
    } else {
        return;
    }
}


function clienteDuplicado(prpno_cpf_cgc, tipoPessoa)
{
	alert('Cliente com documento duplicado, ajuste na tela de Cadastro/Cliente para prosseguir com o cadastro desta proposta.');
	
	var doc_busca = 'cpf_busca';
	
	if (tipoPessoa == 'J'){
		doc_busca = 'cnpj_busca';
	}
	
	window.location.href = 'cliente.php?' + doc_busca + '=' + prpno_cpf_cgc + '&pesq_clitipo=' + tipoPessoa;
}


//fim arquivo