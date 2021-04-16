jQuery(document).ready(function(){

	tabListener();

	formSubmitListener();

	checkboxListener();

	validaCamposSomenteNumero();

	desvincularRejeitado();

	alteraEstadoBotaoDesvincular();
	
});

function validaCamposSomenteNumero() {
	jQuery("#txt_num_remessa").keydown(function() {
		var valor = jQuery("#txt_num_remessa");
		if(isNaN(valor.val())) {
			valor.val(valor.val().substring(0, valor.val().length - 1));
		}

		valor.val(valor.val().replace(" ", ""));
	});

	jQuery("#txt_num_remessa").keyup(function() {
		var valor = jQuery("#txt_num_remessa");
		if(isNaN(valor.val())) {
			valor.val(valor.val().substring(0, valor.val().length - 1));
		}

		valor.val(valor.val().replace(" ", ""));
	});

	jQuery("#txt_num_remessa").blur(function() {
		var valor = jQuery("#txt_num_remessa");
		if(isNaN(valor.val())) {
			valor.val('');
		}

		valor.val(valor.val().replace(" ", ""));
	});

	jQuery("#txt_num_titulo_rejeitado").keydown(function() {
		var valor = jQuery("#txt_num_titulo_rejeitado");
		if(isNaN(valor.val())) {
			valor.val(valor.val().substring(0, valor.val().length - 1));
		}

		valor.val(valor.val().replace(" ", ""));
	});

	jQuery("#txt_num_titulo_rejeitado").keyup(function() {
		var valor = jQuery("#txt_num_titulo_rejeitado");
		if(isNaN(valor.val())) {
			valor.val(valor.val().substring(0, valor.val().length - 1));
		}

		valor.val(valor.val().replace(" ", ""));
	});

	jQuery("#txt_num_titulo_rejeitado").blur(function() {
		var valor = jQuery("#txt_num_titulo_rejeitado");
		if(isNaN(valor.val())) {
			valor.val('');
		}

		valor.val(valor.val().replace(" ", ""));
	});

	jQuery("#txt_qtde_titulos").keydown(function() {
		var valor = jQuery("#txt_qtde_titulos");
		if(isNaN(valor.val())) {
			valor.val(valor.val().substring(0, valor.val().length - 1));
		}

		valor.val(valor.val().replace(" ", ""));
	});

	jQuery("#txt_qtde_titulos").keyup(function() {
		var valor = jQuery("#txt_qtde_titulos");
		if(isNaN(valor.val())) {
			valor.val(valor.val().substring(0, valor.val().length - 1));
		}

		valor.val(valor.val().replace(" ", ""));
	});

	jQuery("#txt_qtde_titulos").blur(function() {
		var valor = jQuery("#txt_qtde_titulos");
		if(isNaN(valor.val())) {
			valor.val('');
		}

		valor.val(valor.val().replace(" ", ""));
	});
}

function excluirLinhaRemessa(obj, numRemessa, idRemessa, tipo) {
	if(confirm("Deseja excluir a remessa " + numRemessa + "?")) {

		jQuery.ajax({
        url: 'fin_cobranca_registrada.php',
        type: 'POST',
        data: {
            acao: 'excluirRemessa',
            idRemessa: idRemessa,
            tipo: tipo
        },
        beforeSend: function() {
        	jQuery("#excluir_remessa_" + idRemessa).attr("src", "modulos/web/images/ajax-loader-circle.gif");
        },
        success: function(data) {
            jQuery('#form_remessa #respostaSucesso').val("Remessa exclu�da com sucesso.");
			jQuery("#form_remessa").submit();
        },
        error: function (xhr, ajaxOptions, thrownError) {
        	alert(xhr.responseText);
        	jQuery("#excluir_remessa_" + idRemessa).attr("src", "images/icon_error.png");
        	jQuery('#form_remessa #respostaErro').val("Ocorreu um erro ao tentar excluir a remessa " + numRemessa + ".");
			jQuery("#form_remessa").submit();
      	}
    });
	}
}

function desvincularRejeitado() {
	jQuery("#btn_desvincular_rejeitado").click(function() {
		selecionados = jQuery('input.chk_rejeitado[type="checkbox"]:checked').length;
		pergunta = selecionados > 1 ? "Deseja desvincular os t�tulos selecionados?" : "Deseja desvincular o t�tulo selecionado?";

		if(confirm(pergunta)) {
			titulos = "";
			tipos = "";

			// Concatena os t�tulos que ser�o desvinculados
			jQuery('input.chk_rejeitado[type="checkbox"]:checked').each(function() {
				titulos += jQuery(this).attr("data-id") + ",";
				tipos += jQuery(this).attr("data-tipo") + ",";
			});

			// Remove a �ltima virgula
			titulos = titulos.slice(0, -1);
			tipos = tipos.slice(0, -1);

			jQuery.ajax({
		        url: 'fin_cobranca_registrada.php',
		        type: 'POST',
		        data: {
		            acao: 'desvinculaRejeitado',
		            idTitulo: titulos,
		            tipo: tipos
		        },
		        success: function(data) {
		        	jQuery('#form_rejeitado #respostaSucesso').val("T�tulos desvinculados com sucesso.");
					jQuery("#form_rejeitado").submit();
		        },
		        error: function (xhr, ajaxOptions, thrownError) {
		        	jQuery('#form_rejeitado #respostaErro').val("Ocorreu um erro ao desvincular os t�tulos da remessa.");
					jQuery("#form_rejeitado").submit();
		      	}
			 });
		}
	});
}

function tabListener() {

	jQuery("#aba_remessa").click(function() {
		window.location.href = window.location.href.split('?')[0] + '?acao=remessa&origem=A';
	});

	jQuery("#aba_rejeitado").click(function() {
		window.location.href = window.location.href.split('?')[0] + '?acao=rejeitado&origem=A';
	});

	jQuery("#aba_arquivo").click(function() {
		window.location.href = window.location.href.split('?')[0] + '?acao=arquivo&origem=A';
	});

}

function formSubmitListener() {

	jQuery("#btn_pesquisar_remessa").click(function(){
		jQuery('#form_remessa #origem').val('P');
		jQuery("#form_remessa").submit();
	});
	
	jQuery("#btn_pesquisar_rejeitado").click(function() {
		jQuery('#form_rejeitado #origem').val('P');
		jQuery("#form_rejeitado").submit();
	});

	// Gera��o da planilha de remessa
	jQuery("#btn_gerar_csv_remessa").click(function() {
		jQuery('#form_remessa #origem').val('P');
		jQuery('#form_remessa #acao').val('gerarCSVRemessa');
        jQuery('#form_remessa').submit();
	});

	// Gera��o da planilha de rejeitado
	jQuery("#btn_gerar_csv_rejeitado").click(function() {
		jQuery('#form_rejeitado #origem').val('P');
		jQuery('#form_rejeitado #acao').val('gerarCSVRejeitado');
        jQuery('#form_rejeitado').submit();
	});

	// Gera��o da planilha de arquivo
	//jQuery(".btn_gerar_csv_arquivo").click(function() {
		/* sti 86972 */
		
		//jQuery('#form_arquivo #origem').val('P');
        // jQuery('#form_arquivo').submit();
	//});
}

function checkboxListener() {
	// Faz uma verifica��o inicial para alterar o bot�o quando faz post na p�gina ao clicar no bot�o de gerar CSV da aba Remessa
	if(jQuery("#chk_titulos_sem_remessa").prop("checked")) {
		jQuery("#btn_pesquisar_remessa").hide();
		jQuery("#btn_gerar_csv_remessa").show();
	}

	// Verifica��o inicial para acertar o estado do bot�o no carregamento da p�gina
	alteraEstadoBotaoDesvincular();

	// Verifica estado do checkbox de remessa para organizar os bot�es e limpar e habilitar/desabilitar campos
	jQuery("#chk_titulos_sem_remessa").click(function() {
		if(jQuery(this).prop("checked")) {
			jQuery("#btn_pesquisar_remessa").hide();
			jQuery("#btn_gerar_csv_remessa").show();

			jQuery("#txt_num_remessa").prop("disabled", true);
			jQuery("#ddl_status_remessa").prop("disabled", true);

			jQuery("#txt_num_remessa").val("");
			jQuery("#ddl_status_remessa").val("0");

			jQuery('#lbl_periodo_dt_vencimento').show();
			jQuery('#lbl_periodo_envio').hide();
		} else {
			jQuery("#btn_pesquisar_remessa").show();
			jQuery("#btn_gerar_csv_remessa").hide();

			jQuery("#txt_num_remessa").prop("disabled", false);
			jQuery("#ddl_status_remessa").prop("disabled", false);

			jQuery('#lbl_periodo_dt_vencimento').hide();
			jQuery('#lbl_periodo_envio').show();
		}
	});

	// Habilitar/desabilitar bot�o de desvincular
	jQuery('input.chk_rejeitado[type="checkbox"]').click(function() {
		alteraEstadoBotaoDesvincular();
	});
}

function alteraEstadoBotaoDesvincular() {
	qtde_selecionados = jQuery('input.chk_rejeitado[type="checkbox"]:checked').length;

	if(qtde_selecionados > 0) {
		jQuery("#btn_desvincular_rejeitado").prop("disabled", false);
	} else {
		jQuery("#btn_desvincular_rejeitado").prop("disabled", true);
	}
}

function escondeMensagens() {
	jQuery("#mensagem_erro, #mensagem_alerta, #mensagem_sucesso").hide();
}