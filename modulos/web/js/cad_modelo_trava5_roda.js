jQuery(document).ready(function(){
   
   //bot�o novo
   jQuery("#bt_novo").click(function(){
       window.location.href = "cad_modelo_trava5_roda.php?acao=cadastrar";
   });
   
   //bot�o voltar
   jQuery("#bt_voltar").click(function(){
       window.location.href = "cad_modelo_trava5_roda.php";
   });
      
   //botao editar
    jQuery("table").on('click','.editar',function(event) {

        event.preventDefault();

        id = jQuery(this).data('tmooid');

        window.location.href = "cad_modelo_trava5_roda.php?acao=editar&tmooid="+id;
    });
    
    // bot�o excluir
    jQuery("table").on('click','.excluir',function(event) {

        event.preventDefault();

        id = jQuery(this).data('tmooid');

        jQuery("#mensagem_excluir").dialog({
            title: "Confirma��o de Exclus�o",
            resizable: false,
            modal: true,
            buttons: {
                "Sim": function() {                    
                    jQuery( "#acao" ).val("excluir");
                    jQuery( "#tmooid" ).val(id);
                    jQuery( "#form" ).submit();

                },
                "N�o": function() {
                    jQuery( this ).dialog( "close" );
                }
            }
        });
    });
   
    // Esconde todas as mensagens e erros     
    function esconderMensagens() {
        jQuery('#msg_alerta').hide();
        jQuery('#msg_sucesso').hide();
        jQuery('#msg_erro').hide();
        jQuery('.obrigatorio').removeClass('erro');
    }
   
});