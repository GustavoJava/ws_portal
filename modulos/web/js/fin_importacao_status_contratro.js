
        
    jQuery('#btn_confirmar').click(function(){
        
        if (!jQuery('#importacao_status_contrato').val()){
            criaAlerta('Voc� n�o possui permiss�o de acesso � este recurso.');
            return false;
        }
        
        if (!jQuery('#arquivo').val()){
            criaAlerta('Informe o arquivo.');
            return false;
        }
        else if (confirm('Confirma a importa��o de arquivo?')){

            jQuery('#acao').val('importaCSV');
            jQuery('#importa_informacoes').attr('action', 'fin_importacao_status_contrato.php');
            jQuery('#importa_informacoes').submit();        
        }

    });

   
    
