$(document).ready(function() {
	
	//mudan�a de tela
	if ($('#acao').val()=='efetuarPagamento') {
		$('#container-pesquisa').hide();
		$('#container-pagamento').show();
	} else {
		$('#container-pesquisa').show();
		$('#container-pagamento').hide();
	}
	
	/*
	 * A��o bot�o
	 */
	$('#btn_pesquisar').click(function() {
		
		if (!validarFormulario()) {
			return false;
		} 
		
		$('#acao').val('pesquisar');
		$('#frm').submit();
	});
	
	/*
	 * A��o bot�o
	 */
	$('#gerarComissao').click(function() {
		$('#acao').val('gerarComissao');
		
		var continua = true;
		var continua2 = false;
		var txtConfirm = "Foram selecionados itens que j� tiveram comiss�o calculada. ";
		txtConfirm += "Confirma a gera��o de comiss�o e atualiza��o das comiss�es dos itens selecionados?";
		
		$('.nota_indicador_gerada').each(function(){
			if ($(this).is(':checked')) {
				continua = false;
			}
		});
		
		$('.nota_indicador_pendente').each(function(){
			if ($(this).is(':checked')) {
				continua2 = true;
			}
		});
		
		if (!continua) {
			if (confirm(txtConfirm)) {
				$('#frm').submit();
			}
		} else {
			var txtConfirm2 = "Confirma a gera��o de comiss�o dos itens selecionados?";
			if (continua2) {
				if (confirm(txtConfirm2)) {
					$('#frm').submit();
				}
			}
		}
	});
	
	/*
	 * A��o bot�o
	 */
	$('#efetuarPagamento').click(function() {
		$('#acao').val('efetuarPagamento');
		
		var continua = true;
		var continua2 = false;
		var txtConfirm = "Foram selecionados itens que n�o tiveram comiss�o calculada, esses itens ser�o ignorados no processo de pagamento. Confirma?";
		
		$('.nota_indicador_pendente').each(function(){
			if ($(this).is(':checked')) {
				continua = false;
				continua2 = true;
			}
		});
		
		$('.nota_indicador_gerada').each(function(){
			if ($(this).is(':checked')) {
				continua2 = true;
			}
		});
		
		if (!continua) {
			if (confirm(txtConfirm)) {
				$('#frm').submit();
			}
		} else {
			if (continua2) {
				$('#frm').submit();
			}
		}
	});
	
	/*
	 * A��o bot�o
	 */
	$('#excluirComissao').click(function() {
		$('#acao').val('excluirComissao');
		
		var continua2 = false;
		var txtConfirm = "Deseja excluir os itens selecionados? ";
		
		$('.nota_indicador_pendente').each(function(){
			if ($(this).is(':checked')) {
				continua2 = true;
				txtConfirm = "Deseja excluir os itens selecionados? H� itens selecionados que est�o pendentes. Esses itens ser�o ignorados no processo de exclus�o. ";
			}
		});
		
		$('.nota_indicador_gerada').each(function(){
			if ($(this).is(':checked')) {
				continua2 = true;
			}
		});
		
		if (continua2) {
			if (confirm(txtConfirm)) {
				$('#frm').submit();
			}
		}
	});
	
	/*
	 * A��o bot�o
	 */
	$('#btn_confirmar').click(function() {
		$('#acao').val('confirmarPagamento');
		$('#frm').submit();
	});
	
	/*
	 * A��o bot�o
	 */
	$('#btn_cancelar').click(function() {
		$('#container-pesquisa').show();
		$('#container-pagamento').hide();		
	});
	
	/*
	 * checkbox para selecionar todos
	 */
	$('#selecionar_todas').click(function() {
		
		if ($(this).is(':checked')) {
			$('.nota_indicador').attr('checked', 'checked');
		}
		else {
			$('.nota_indicador').removeAttr('checked');
		}
		$('#selecionar_pendentes').removeAttr('checked');
	});
	
	/*
	 * checkbox para selecionar todos pendentes
	 */
	$('#selecionar_pendentes').click(function() {
		
		$('.nota_indicador').removeAttr('checked');
		if ($(this).is(':checked')) {
			$('.nota_indicador_pendente').attr('checked', 'checked');
		}
		else {
			$('.nota_indicador_pendente').removeAttr('checked');
		}
		$('#selecionar_todas').removeAttr('checked');
	});
});

function validarFormulario() {
	
	var data_ini = $('#data_inicial').val();
	var data_fim = $('#data_final').val();
	
	if (data_ini.length == 0 || data_fim.length == 0) { 
		
		jQuery("#data_inicial").addClass("inputError");
		jQuery("#data_final").addClass("inputError");
		removeAlerta();
		criaAlerta('� necessario informar o per�odo para a pesquisa.');
        return false;
	}
	
    if(diferencaEntreDatas(data_fim, data_ini) > 365) {
    	
    	jQuery("#data_inicial").addClass("inputError");
		jQuery("#data_final").addClass("inputError");
		removeAlerta();
        criaAlerta('A data de vencimento inicial e final do per�odo n�o pode ultrapassar o intervalo de 1 ano.');
        return false;
    }
    
    if(diferencaEntreDatas(data_fim, data_ini) < 0) {
    	
    	jQuery("#data_inicial").addClass("inputError");
		jQuery("#data_final").addClass("inputError");
		removeAlerta();
        criaAlerta('A data inicial deve ser menor que a data final do per�odo.');
        return false;
    }
    
    return true;
}