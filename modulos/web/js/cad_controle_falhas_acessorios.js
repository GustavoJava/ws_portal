jQuery(document).ready(function(){
     
    //Bot�o de Pesquisa
    jQuery("#bt_pesquisar").click(function(){
        
        removeAlerta();        
        
        if(!validarCombos()){        	
        	return false;
        };
        
        jQuery("#acao").val("buscarRegistro");
        jQuery("#form").submit();
        
    });
    
    //Bot�o de Inclus�o
    jQuery("#bt_novo").click(function(){
        
        removeAlerta();
        
        jQuery("#acessorio").removeClass('highlight');
        jQuery("#item_falha").removeClass('highlight');
        jQuery("#descricao").removeClass('highlight');

        if ( jQuery("#descricao").val() == ""){   
        	
        	 if(!validarCombos()){        	
        		 (jQuery("#descricao")).addClass('highlight');
        		 return false;
        	 }
        	
        	(jQuery("#descricao")).addClass('highlight');     
        	criaAlerta("Inserir no campo correspondente o texto a ser gravado."); 
        	return false;
        }
        
           
        if(!validarCombos()){        	
        	return false;
        }; 
        
        jQuery("#acao").val("inserirRegistro");
        jQuery("#form").submit();     
        
    });
    
    //Bot�o de Exclus�o
    jQuery("#bt_excluir").click(function(){    
    	
        removeAlerta();
        
        if (jQuery('.chk_codigo:checked').length == 0) {
        	criaAlerta('Selecionar um registro para exclus�o.');
			return false;
		}
      
        
        if ( confirm('Deseja realmente excluir o(s) item(ns)?') ){
        jQuery("#acao").val("inativarRegistro");
        jQuery("#form").submit();       
        
        }
    });   
    

 
    
    
    /**
	 * Evento de sele��o de todos os checkboxes
	 */
	$('#check_all').click(function() {
		
		var self = $(this);
				
		if (self.is(':checked')) {
			$('.chk_codigo').attr('checked', true);			
		} else {
			$('.chk_codigo').attr('checked', false);
		}
	});
	
	
	function validarCombos(){
		
		var retorno = true;
		
		jQuery("#acessorio").removeClass('highlight');
        jQuery("#item_falha").removeClass('highlight');
        
        if ( jQuery("#acessorio").val() == "0"){
        	
        	if(jQuery("#item_falha").val() == "0"){        	
	        	
	        	jQuery("#item_falha").addClass('highlight');	            
        	}
        	
        	(jQuery("#acessorio")).addClass('highlight');
        	criaAlerta("Preencher os campos obrigat�rios."); 
        	retorno = false;
        }
       
        if(jQuery("#item_falha").val() == "0"){
        	
        	if( jQuery("#acessorio").val() == "0"){
        		
        		jQuery("#acessorio").addClass('highlight');	        	
        	}
        	
        	(jQuery("#item_falha")).addClass('highlight');
            criaAlerta("Preencher os campos obrigat�rios.");
            retorno = false;
        }
        
        return retorno;
		
	}
    
    
});
