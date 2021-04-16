jQuery(document).ready(function(){
      
	/*
	 *Apolica��o das  Mascaras 
	 */
    jQuery('#cpgparcelas').mask('9?99', {
        placeholder: ''
    }); 
    

    jQuery("#cpgvencimentos").keyup(function(){
        
    	this.value = this.value.replace(/[^0-9\;]/g,'');
    	
    });
    
    /*
     * Bot�o novo da tela de pesquisa.
     */
    jQuery('#bt_novo').click(function(){
    	
    	limparMensagens();
    	
        window.location = "cad_condicao_pagamento.php?acao=cadastrar";

    });
    
    //Fun��o para Volta a home
    jQuery('#bt_voltar').click(function(){
    	
    	limparMensagens();
    	
        window.location = "cad_condicao_pagamento.php";
    });
    
    
    jQuery('body').delegate('.deletar', 'click', function(){
        
        if (!confirm('Deseja realmente excluir o registro?')){
            return false;
        }

    });
    

      
});

function limparMensagens() {
	
	jQuery('#mensagem_erro').hide();
	jQuery('#mensagem_alerta').hide();
	jQuery('#mensagem_sucesso').hide();
	
	
};
