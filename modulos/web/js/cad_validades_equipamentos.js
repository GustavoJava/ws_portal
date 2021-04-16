/**
 * @author	Diego Nogu�s
 * @email 	diegocn@brq.com
 * @since	06/08/2013
 */

jQuery(function() {    	

	if (jQuery('#mensagem').text() == '') {
        jQuery('#mensagem').hide();
    }
	
	// A��o de editar
	jQuery('body').delegate('.clickEditar',  'click', function(){
		jQuery('#veqpoid').val(jQuery(this).attr('id'));
		// Troca a��o para o valor correspondente e d� submit no form
		jQuery('#acao').val('editar').closest('form').submit();
	});

	// A��es do form
	jQuery('body').delegate('#buttonNovo,#buttonCancelar,#buttonExcluir,#buttonPesquisar', 'click', function(){
		// Pega value do bot�o clicado
		var acaoValor = $(this).val();
		// Troca a��o para o valor correspondente e d� submit no form
		jQuery('#acao').val(acaoValor).closest('form').submit();
	});
	
	jQuery("#veqpdescricao").on("blur", function() {
		jQuery(this).val(jQuery.trim(jQuery(this).val()));
	});

	jQuery('body').delegate('.camponum', 'keyup', function(){    
        var valor = jQuery(this).val();
        if(!$.isNumeric(valor))
        {
            jQuery(this).val(valor.replace(/[^\d]/g, ''));
        }
    });
});    