jQuery(function(){
                    
       
    /**
    * Click do botao pesquisar
    */
    jQuery('#bt_pesquisar').click(function(){
             
        removeAlerta();
     
       if (diferencaEntreDatas($("#data_fim_pesquisa").val(), $("#data_inicio_pesquisa").val()) < 0){
            criaAlerta("Data final menor que a data inicial."); 
            jQuery("#data_fim_pesquisa").addClass("inputError");
            return false;  
        }
             
  
                  
       
       
       
             jQuery("#acao").val('pesquisaIntegracao');
             jQuery("#frm_pesquisa_integracao").submit();
                    
      
        
    })
    
       
    // Formata��o para o campo "N�mero da Prioridade" aceitar apenas n�meros.
    
    jQuery("#numero_solicitacao").keypress(function(){
        formatar(this, '@');
    });
    jQuery("#numero_solicitacao").blur(function(){
        revalidar(this, '@', '');
    });
    
    
  

 
       
       


       
})
