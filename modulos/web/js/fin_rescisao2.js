jQuery(document).ready(function() {
    "use strict";
    
   jQuery("#campo_data_solicitacao").hide();
   
    /**
     * Inclui na classe Number (Int e Float) m�todo para formatar moeda
     */
    Number.prototype.toMoney = function(num) {
        var num = this;
        var x = 0;
        if (num < 0) {
            num = Math.abs(num);
            x = 1;
        }
        if (isNaN(num)) {
            num = "0";
        }
        var cents = Math.floor((num * 100 + 0.5) % 100);
        num = Math.floor((num * 100 + 0.5) / 100).toString();

        if (cents < 10) {
            cents = "0" + cents;
        }
        for (var i = 0; i < Math.floor((num.length - (1 + i)) / 3); i++) {
            num = num.substring(0, num.length - (4 * i + 3)) + '.' + num.substring(num.length - (4 * i + 3));
        }
        var ret = num + ',' + cents;
        if (x == 1) {
            ret = ' - ' + ret;
        }

        return ret;
    };
    
    /**
     * Cria alerta lindchu com a mensagem enviada
     */
    function alerta(msg) {
        removeAlerta();
        criaAlerta(msg);
    };

    /**
     * Destaca elemento com erro e exibe mensagem
     */
    function addError(elm, msg) {
        elm.addClass('highlight');
        alerta(msg);
    }

    /**
     * Retira destaque dos campos com erro
     */
    function clearErrors() {
        jQuery('.highlight').removeClass('highlight');
        removeAlerta();
    }

    /**
     * Formata dinheiro em float para ser usado nos c�lculos do JS
     */
    function parseMoney(value) {
        try {
            if (typeof value == 'undefined') {
                return 0.00;
            }
            var valorCampo = value.replace(/[^a-zA-Z 0-9]+/g, '');
            var valornovo = parseFloat(valorCampo) / 100;

            if (isNaN(valornovo)) {
                valornovo = 0
            }
            return valornovo;

        } catch (e) {
            //alert(e);
        }
    }  

    /**
     * Periodos
     */


    /**
     * Formata elementos de layout
     */
    function layoutActions() {
        // D� "zebra" nas tabelas
        jQuery('.bloco_conteudo tbody tr:odd').addClass('par');

        // M�scara de dinheiro
        jQuery('.mask-money').maskMoney({
            thousands:     '.'
          , decimal:       ','
          , defaultZero:   true
          , allowZero:     true
          , allowNegative: false
        });

        // M�scara de inteiros
        jQuery('.mask-numbers')
            .keydown(function() {
                maskNumbersOnly(jQuery(this));
            })
            .keypress(function() {
                maskNumbersOnly(jQuery(this));
            })
            .keyup(function() {
                maskNumbersOnly(jQuery(this));
            })
            .change(function() {
                maskNumbersOnly(jQuery(this));
            })
            .focus(function() {
                maskNumbersOnly(jQuery(this));
            });

        /**
         * Remove caracteres n�o num�ricos de um campo
         */
        function maskNumbersOnly(elm) {
            if (/[^\d]/.test(elm.val())) {
                elm.val(elm.val().toString().replace(/[^\d]/g, ''));
            }
        }

        /**
         * Gambi para impedir 'ENTER' na busca de clientes
         */
        if (jQuery('.busca-cliente').length) {
            jQuery(document).keypress(function(e) {
                if (e.which == 13) {
                    e.preventDefault();
                }
            });
        }

        /**
         * Setup datepicker
         */
        jQuery('input.data').datepicker({
            dateFormat    : 'dd/mm/yy'
          , dayNamesMin   : [ 'D', 'S', 'T', 'Q', 'Q', 'S', 'S' ]
          , monthNames    : [ 'Janeiro', 'Fevereiro', 'Mar�o', 'Abril',
                'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro',
                'Novembro', 'Dezembro' ]
          , showOn      : 'both'
          , buttonImage     : 'images/calendar_cal.gif'
          , buttonImageOnly : true
        });

        // Datepicker data rescis�o
        /* jQuery('#resmfax').datepicker({
            dateFormat    : 'dd/mm/yy'
          , dayNamesMin   : [ 'D', 'S', 'T', 'Q', 'Q', 'S', 'S' ]
          , monthNames    : [ 'Janeiro', 'Fevereiro', 'Mar�o', 'Abril',
                'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro',
                'Novembro', 'Dezembro' ]
          , showOn      : 'both'
          , buttonImage     : 'images/calendar_cal.gif'
          , buttonImageOnly : true
          , maxDate         : 0
        }); */
    }
    
    layoutActions();

    /**
     * Exclui uma rescis�o via AJAX e redireciona para a p�gina inicial do m�dulo
     */
    jQuery('.rescisao-excluir').click(function() {
        var url = jQuery(this).data('url');

        var conf = confirm("Voc� tem certeza de que deseja excluir esta rescis�o?");

        if (conf === true) {
            jQuery.post(url, function(r) {
                window.location = 'fin_rescisao2.php';
            });
        }
    });

    /**
     * Busca o nome de um cliente via popup
     */
    jQuery('.busca-cliente').click(function(e) {
        // Gambi para n�o redirecionar a p�gina
        e.preventDefault();

        // Abre popup padr�o do sistema
        var searchWindow = window.open(
            'psq_cliente.php?campo_cod=clioid&campo_txt=cliente&nome_form=cliente_form'
          , 'ContrlWindow'
          , 'status,scrollbars=yes,menubar=no,toolbar=no,location=yes,width=480,height=220'
        );

        // buscaDataSolicitacao(searchWindow);
    });

    // Busca data de solicita��o de rescis�o ao digitar contrato
    /* jQuery('#connumero').change(function() {
        var connumero = jQuery(this).val()
          , url       = 'fin_rescisao.php?acao=buscaDataSolicitacaoContrato&connumero=' + connumero;

        // Apaga contratos carregados
        jQuery('.container-contratos').html('');

        jQuery.get(url, function(r) {
            if (r.length) {
                jQuery('#resmfax').val(r);
            }
        });
    }); */

    /**
     * Limpa a tela ao trocar a data da solicita��o de rescis�o
     */
    jQuery('#resmfax').change(function() {
        jQuery('.container-contratos').html('');
        jQuery('.container-multas').html('');
    });

    /**
     * Busca a data da �ltima solicita��o de rescis�o (data de pr�-rescis�o)
     */
    function buscaDataSolicitacao(searchWindow) {
        // Se o popup for fechado, busca a data
        if (searchWindow.closed) {
            var clioid = jQuery('#clioid').val()
              , url    = 'fin_rescisao2.php?acao=buscaDataSolicitacao&clioid=' + clioid;

            // Apaga contratos carregados
            jQuery('.container-contratos').html('');

            jQuery.get(url, function(r) {
                if (r.length) {
                    jQuery('#resmfax').val(r);
                }
            });
        } else {
            setTimeout(function() {
                buscaDataSolicitacao(searchWindow);
            }, 100);
        }
    };

    // ORGMKTOTVS-[3596] Cris
    /**
     * replicar a data de solicita��o para todos os contratos
     */
    jQuery("#data_solicitacao").change(function () {
        jQuery('.contrato-data-recisao').val(jQuery(this).val());
        $('.contrato-data-recisao').trigger('change');
    });
    
    /**
     * Busca contratos do cliente
     */
    jQuery('#pesquisar-contratos').click(function() {
        
        // ORGMKTOTVS-[3596] Cris
        // mostrar div de data de solicitacao
        jQuery("#campo_data_solicitacao").show();
        jQuery("#campo_data_solicitacao").val("");
        
        var clioid    = jQuery('#clioid').val()
          , connumero = jQuery('#connumero').val()
          // , resmfax   = jQuery('#resmfax').val()
          , loader    = jQuery('.container-contratos-loader')
          , containerContratos = jQuery('.container-contratos')
          , containerMultas    = jQuery('.container-multas');

        var mensagem_alerta = jQuery("#mensagem_alerta");

        mensagem_alerta.hideMessage();

        // Valida preenchimento de nome de cliente ou n�mero de contrato
        if (!clioid.length && !connumero.length) {
            $("#campo_data_solicitacao").hide();
            mensagem_alerta.html('� necess�rio informar o nome do cliente e/ou n�mero do contrato para realizar a pesquisa.').showMessage();
            return;
        }

        // Valida preenchimento da data de solicita��o
        /* if (!resmfax.length) {
            mensagem_alerta.html('� necess�rio informar a data da solicita��o.').showMessage();
            return;
        } */

        // Prepara par�metros para usar na URL (GET)
        var params = jQuery.param({
            acao:       'buscaContratos'
          , clioid:     clioid
          , connumero:  connumero
          // , resmfax:    resmfax
        });

        // Remove conte�dos do container e exibe loader
        containerContratos.html('');
        containerMultas.html('');
        loader.show();

        jQuery.get('fin_rescisao2.php?' + params, function(r) {
            containerContratos.html(r);
            loader.hide();
            layoutActions();

            jQuery('.data input').createDate();
        });
    });

    jQuery("#retornar-novo-contrato").click(function(){
        window.location.href ="fin_rescisao2.php";
    });

    jQuery("#novo-contrato").click(function(){
        window.location.href ="fin_rescisao2.php?acao=novo";
    });

    /**
     * Checkbox de sele��o de todos os contratos
     */
    jQuery('.container-contratos').on('click', '.selecionar-todos-contratos', function() {
        jQuery('.contrato-cliente').attr('checked', !!jQuery(this).attr('checked'));
    });

        
    /**
     * Busca as multas de um contrato.
     * Delega evento para funcionar com carregamento via AJAX
     */
    jQuery('.container-contratos').on('click', '.pesquisar-multas', function() {
        
        var contratos   = jQuery('.contrato-cliente:checked')
          , idsContrato = contratos.map(function() {
                              return jQuery(this).val();
                          });
        var multas = jQuery('.contrato-multa-porcentagem');

        var multaValroes     = {};
        var solicitacaoDatas = {};
        var isentar_monitoramento = {};
        var isentar_locacao = {};
        var calcular_descontos = {};

        idsContrato.each(function(index, valor){
          
          if (jQuery.trim(jQuery('#multa-' + valor).val())) {
            multaValroes[valor]     = jQuery('#multa-' + valor).val();
          }

          if(jQuery('#incluir_servicos_monitoramento_' + valor).is(':checked')) {
            isentar_monitoramento[valor] = true;
          }

          if(jQuery('#incluir_servicos_locacao_' + valor).is(':checked')) {
            isentar_locacao[valor] = true;
          }
          
          if(jQuery('#calcular_descontos_' + valor).is(':checked')) {
            calcular_descontos[valor] = true;
          }
          
          if (jQuery.trim(jQuery('#resmfax-' + valor).val())) {
            solicitacaoDatas[valor] = jQuery('#resmfax-' + valor).val();
          }
   
        });

        if (contratos.length === 0) {
            alerta('Voc� deve selecionar ao menos um contrato para efetuar a pesquisa.');
            return;
        }

        var params = jQuery.param({
                         acao                   :      'buscaMultas'
                       , connumero              : idsContrato.toArray()
                       , solicitacao            :   solicitacaoDatas
                       , multa                  : multaValroes
                       , clioid                 : jQuery('#clioid').val()
                       , isentar_monitoramento  : isentar_monitoramento
                       , isentar_locacao        : isentar_locacao
                       , calcular_descontos     : calcular_descontos
                     })
          , loader          = jQuery('.container-multas-loader')
          , containerMultas = jQuery('.container-multas');

        // Remove conte�dos do container e exibe loader
        containerMultas.html('');
        loader.show();

         jQuery.ajax({
          type: 'POST',
          url: 'fin_rescisao2.php?acao=buscaMultas',
          data: params,
          success: (function(r) {

            if(!r){
              alert("Ocorreu um erro ao calcular os contratos. ");
              jQuery('.container-multas-loader').hide();
              return false;
            }
            containerMultas.html(r);
            loader.hide();
            layoutActions();

            // Calcula a taxa de retirada e a
            // rescis�o ao terminar de carregar a p�gina
            calculaTaxaRetirada();
            calculaTaxaMultaNaoDevolucao();
            calculaTotalRescisao();
            
          }),
        });
    });

    /**
     * Checkbox de sele��o de todas as faturas
     */
    jQuery('.container-multas').on('click', '.selecionar-todos-multa-fatura', function() {
        jQuery('.multa-fatura').attr('checked', !!jQuery(this).attr('checked'));
        calculaTotalRescisao();
    });

    /**
     * Checkbox de sele��o de todas as observa��es de faturas
     */
    jQuery('.container-multas').on('click', '.selecionar-todos-multa-faturas-observacao', function() {
        jQuery('.multa-faturas-observacao').attr('checked', !!jQuery(this).attr('checked'));
    });

    /**
     * Altera a observa��o das multas de faturas selecionadas
     */
    jQuery('.container-multas').on('change', '.change-multa-faturas-observacao', function() {
        var observacao = jQuery(this).val();

        // Preenche os campos em cada row
        jQuery('.multa-faturas-observacao:checked').map(function() {
            jQuery(this).closest('tr')
                   .find('.multa-faturas-locacao-observacao-text')
                   .val(observacao);
        });
    });

    /**
     * Recalcula total de faturas ao clicar num checkbox
     */
    jQuery('.container-multas').on('click', '.multa-fatura', function() {
        calculaTotalRescisao();
    });
 
    
    jQuery('.container-contratos').on('change', '.contrato-data-recisao', function() {

        var dataInicial;
        var dataFinal;

        var row         = jQuery(this).closest('tr')
          , meses       = row.find('.contrato-multa-meses')
          , dataFim     = ''
          , contrato    = jQuery(this).parent('td').parent('tr').children('td:first-child').children('input').val();

        dataInicial = row.find('.contrato-multa-data-inicio')[0].innerText;
        dataFinal = row.find('.contrato-multa-data-fim')[0].innerText
      
        // Calcula a data final de vig�ncia do contrato
        meses = parseInt(jQuery(meses).val()) || 0;
        
        var parts = dataInicial.split('/');
        var data = new Date(parts[1] + '/' + parts[0] + '/' + parts[2]);

        data.setMonth(data.getMonth() + meses);

        var day   = data.getDate().toString();
        var month = (data.getMonth() + 1).toString();

        // Formatar a data no formato dd/mm/yyyy
        if (day.length == 1) {
            dataFim += '0';
        }

        dataFim += day + '/';

        if (month.length == 1) {
            dataFim += '0';
        }

        dataFim += month + '/';
        dataFim += data.getFullYear();
        
        // Calcula a quantidade de meses faltantes
        var dataAtual = (jQuery('#resmfax-' + contrato).val()).split('/');
       
        if(dataAtual != '') {
          
            dataAtual = dataAtual[1] + '/' + dataAtual[0] + '/' + dataAtual[2];
            dataAtual = new Date(dataAtual);

            var difMeses = (data.getTime() - dataAtual.getTime()) / 60 / 60 / 24 / 30.41666 / 1000;
            var parteDecimal = difMeses % 1;
            
            // arredondar para cima ou para baixo
            if (parteDecimal >= 0.50) {
                var mesesFaltantes = Math.ceil(difMeses);
            }else{
                 var mesesFaltantes = Math.trunc(difMeses);
            }
        
          mesesFaltantes = (mesesFaltantes > 0) ? mesesFaltantes : 0;
          row.find('.contrato-multa-meses-faltantes').val(mesesFaltantes);
        }
    });

    /**
     * Recalcula a multa de um contrato ao modificar inputs
     */
    jQuery('.container-contratos').on('keyup keydown keypress change', '.contrato-multa-recalcula', function() {
        // Busca campos da coluna atual
        var row        = jQuery(this).closest('tr')
          , meses      = parseInt(row.find('.contrato-multa-meses-faltantes').val())
          , multaPorcentagem   = parseInt(row.find('.contrato-multa-porcentagem').val())
          , valorMonitoramento = parseMoney(row.find('.contrato-multa-obrigacao').val())
          , totalMultaMonitoramento = row.find('.contrato-multa-valor');

        // Calcula o total da multa de monitoramento
        var valorTotalMonitoramento = meses * valorMonitoramento;
        row.find('.contrato-multa-monitoramento').val(valorTotalMonitoramento.toMoney());

        // Calcula o total da multa de monitoramento
        var multaMonit = meses * valorMonitoramento * multaPorcentagem / 100;

        multaMonit = multaMonit || 0;
        totalMultaMonitoramento.val(multaMonit.toMoney());

        // Calcula o total da multa com desconto
        var total = multaMonit - parseMoney(jQuery('.contrato-multa-desconto').val());
        total = (total && total > 0) ? total : 0;
        row.find('.contrato-multa-total').val(total.toMoney());
    });

    /**
     * Zera multa e atualiza valores 
     */
    jQuery('.container-multas').on('click', '.zerar-multa-locacao', function() {
        
        // Capturando linha do bot�o clicado
        var row = jQuery(this).closest('tr');
        
        var multaPorcentagem = row.find('.multa-locacao-porcentagem');
        var multaPorcentagemPago = row.find('.multa-locacao-porcentagem-pago');
        var valorMulta = row.find('.multa-locacao-total');
        
        // Capturando Input Valor Total da Multa
        var inputID = jQuery(this).attr('id');
        var inputValorTotalMulta = '#valorTotalMulta_' + inputID.substring(11);

        var valorTotalMulta = jQuery(inputValorTotalMulta).val();
        var totalGeralMultas = jQuery('.multa-locacao-soma-geral').val();
        var totalDiferencaIndevido = jQuery('#totalDiferencaIndevido').val();
        var totalMensalidadeEquipamento = jQuery('#totalMensalidadeEquipamento').val();
        var totalRescisao = jQuery('#totalRescisao').val();

        // Diminuindo valor da multa de total geral
        totalGeralMultas = parseMoney(totalGeralMultas) - parseMoney(valorMulta.val());

        // Calculando Total diferen�a indevido
        totalDiferencaIndevido = parseMoney(totalDiferencaIndevido) - parseMoney(valorMulta.val());

        // Calculando Total mensalidade equipamento
        totalMensalidadeEquipamento = parseMoney(totalMensalidadeEquipamento) - parseMoney(valorMulta.val());

        // Calculando Total rescis�o
        totalRescisao = parseMoney(totalRescisao) - parseMoney(valorMulta.val());
        
        // Diminuindo valor da multa de total da multa
        valorTotalMulta = parseMoney(valorTotalMulta) - parseMoney(valorMulta.val());
       
        // Atualizando total da multa
        jQuery(inputValorTotalMulta).val(valorTotalMulta.toMoney());

        // Atualizando Total Geral
        jQuery('.multa-locacao-soma-geral').val(totalGeralMultas.toMoney());
        

        // Atualizando Total Mensalidade Equipamento
        jQuery('#totalMensalidadeEquipamento').val(totalMensalidadeEquipamento.toMoney());
        
        // Atualizando Total Diferen�a Indevido
        jQuery('#totalDiferencaIndevido').val(totalDiferencaIndevido.toMoney());

        // Atualizando Total Rescis�o
        jQuery('#totalRescisao').val(totalRescisao.toMoney());

        // totalDiferencaIndevido + valorMultaMensalidadeFaltante + taxaRetirada;

        // Zerando Multa
        valorMulta.val((0.00).toMoney());

        // Zerando Porcentagem Multa
        multaPorcentagem.val(0);
        multaPorcentagemPago.val(0);
    });

    /**
     * Recalcula a multa de loca��o ao modificar porcentagem
     */
    jQuery('.container-multas').on('keyup keydown keypress change', '.multa-locacao-porcentagem', function() {
        // Busca campos da coluna atual
        var row                =  jQuery(this).closest('tr')
          , multaPorcentagem   = parseInt(jQuery(this).val())
          , multaValorContrato = parseMoney(row.find('.mult  a-locacao-porcentagem-contrato').val())
          , totalMulta         = row.find('.multa-locacao-total');

        // Calculo da multa
        var multaMonit = multaPorcentagem * multaValorContrato / 100;

        // Exibe total da multa
        multaMonit = multaMonit || 0;
        totalMulta.val(multaMonit.toMoney());

        recalculaMultasLocacao();
    });

    /**
     * Altera a porcentagem de todas as multas de loca��o
     */
    jQuery('.container-multas').on('keyup keydown keypress change', '.multa-locacao-porcentagem-geral', function() {
        var valor = parseInt(jQuery(this).val()) || 0;

        jQuery('.multa-locacao-porcentagem').val(valor);
        jQuery(this).val(valor);

        recalculaMultasLocacao();
    });

    /**
     * Altera a porcentagem de todas as multas de loca��o DE UMA NOTA FISCAL
     */
    jQuery('.container-multas').on('keyup keydown keypress change', '.multa-locacao-porcentagem-pornota', function() {
        var valor = parseInt(jQuery(this).val()) || 0;

        jQuery(this).parents('tfoot').prev('tbody').find('.multa-locacao-porcentagem').val(valor);
        jQuery(this).val(valor);

        recalculaMultasLocacao();
    });

    /**
     * Recalcula o valor de cada multa de loca��o, individualmente
     */
    function recalculaMultasLocacao() {
        jQuery('.multa-locacao-porcentagem').map(function() {
            var row            = jQuery(this).closest('tr')
          , multaPorcentagem   = parseInt(jQuery(this).val())
          //, multaValorContrato = parseMoney(row.find('.multa-locacao-porcentagem-contrato').val())
          , multaValorContrato = parseMoney(row.find('.multa-locacao-valor').val())
          , totalMulta         = row.find('.multa-locacao-total');

            // Calculo da multa
            var multaMonit = multaPorcentagem * multaValorContrato / 100;

            // Exibe total da multa
            multaMonit = multaMonit || 0;
            totalMulta.val(multaMonit.toMoney());
        });

        // Recalcula o total da rescis�o
        calculaTotalRescisao();
    }

    /**
     * Calcula o total das multas de loca��o
     */
    function calculaMultaLocacao() {
        // Soma o valor de todas as multas
        var antigo;
        var nota        = '';
        var somaGeral   = 0.00;
        var somaPorNota = 0.00;

        jQuery.each(jQuery('.multa-locacao-total'), function(index) {
            var codigo = jQuery.trim(jQuery(this).parents('tbody').children('tr:first-child').children('td:first-child').html());
            var valor  = parseFloat(parseMoney(jQuery(this).val()));

            if (codigo != nota) {
              nota = codigo;

              if (antigo) {
                antigo.parents('tbody').next('tfoot').find('.multa-locacao-soma-pornota').val(parseFloat(somaPorNota).toMoney());
              }

              somaPorNota = 0.00;
            }

            somaGeral   += valor;
            somaPorNota += valor;

            if (jQuery('.multa-locacao-total').length == index + 1) {
              jQuery(this).parents('tbody').next('tfoot').find('.multa-locacao-soma-pornota').val(parseFloat(somaPorNota).toMoney());
            }

            antigo = jQuery(this);
        });

        jQuery('.multa-locacao-soma-geral').val(parseFloat(somaGeral).toMoney());
    };

    /**
     * Checkbox de sele��o de todas as observa��es de multas de loca��o
     */
    jQuery('.container-multas').on('click', '.multa-locacao-observacao-selecionar-geral', function() {
        jQuery('.multa-locacao-observacao-check').attr('checked', !!jQuery(this).attr('checked'));
    });

    /**
     * Checkbox de sele��o de todas as observa��es de multas de loca��o DE UMA NOTA FISCAL
     */
    jQuery('.container-multas').on('click', '.multa-locacao-observacao-selecionar-pornota', function() {
        jQuery(this).parents('tfoot').prev('tbody').find('.multa-locacao-observacao-check').attr('checked', !!jQuery(this).attr('checked'));
    });

    /**
     * Altera a observa��o das multas de multas de loca��o selecionadas
     */
    jQuery('.container-multas').on('change', '.change-multa-locacao-observacao', function() {
        var observacao = jQuery(this).val();

        // Preenche os campos em cada row
        jQuery('.multa-locacao-observacao-check:checked').map(function() {
            jQuery(this).closest('tr')
                   .find('.multa-locacao-observacao')
                   .val(observacao);
        });
    });

    /**
     * Recalcula a taxa de retirada ao clicar num checkbox
     */
    jQuery('.container-multas').on('click', '.multa-retirada', function() {
        if(
            jQuery(this).parent().next().children('.multa-nao-retirada').attr('checked') == 'checked'
            && jQuery(this).attr('checked') == 'checked'
        ){
            jQuery(this).parent().next().children('.multa-nao-retirada').prop('checked', false);
        }
        calculaTaxaRetirada();
        calculaTaxaMultaNaoDevolucao();
        calculaTotalRescisao();
    });

    jQuery('.container-multas').on('click', '.multa-nao-retirada', function() {
        if(
            jQuery(this).parent().prev().children('.multa-retirada').attr('checked') == 'checked'
            && jQuery(this).attr('checked') == 'checked'
        ){
            jQuery(this).parent().prev().children('.multa-retirada').prop('checked', false);
        }
        calculaTaxaRetirada();
        calculaTaxaMultaNaoDevolucao();
        calculaTotalRescisao();
    });

    /**
     * Recalcula a taxa de retirada ao editar o valor da multa
     */
    jQuery('.container-multas').on('keyup keydown keypress', '.multa-retirada-valor', function() {
        calculaTaxaRetirada();
        calculaTotalRescisao();
    });

    jQuery('.container-multas').on('keyup keydown keypress', '.multa-nao-retirada-valor', function() {
        calculaTaxaMultaNaoDevolucao();
        calculaTotalRescisao();
    });

    /**
     * Recalcula o valor da taxa de retirada
     */
    function calculaTaxaRetirada() {
        var valorTotalTaxas = 0;

        jQuery('.multa-retirada:checked').map(function() {
            var row   = jQuery(this).closest('tr')
              , multa = parseMoney(row.find('.multa-retirada-valor').val());

            valorTotalTaxas += multa;
        });

        jQuery('.valor-total-taxas').val(valorTotalTaxas.toMoney());
    };

    function calculaTaxaMultaNaoDevolucao() {
        var valorTotalTaxas = 0;

        jQuery('.multa-nao-retirada:checked').map(function() {
            var row   = jQuery(this).closest('tr')
              , multa = parseMoney(row.find('.multa-nao-retirada-valor').val());

            valorTotalTaxas += multa;
        });

        jQuery('.valor-total-multa-nao-devolucao').val(valorTotalTaxas.toMoney());
    };

    /**
     * Recalula o valor das multas de servi�os n�o faturados ao trocar valor
     */
    jQuery('.container-multas').on('keyup keydown keypress', '.multa-servico', function() {
        calculaTotalRescisao();
    });

    /**
     * Recalcula o valor das multas de servi�os n�o faturados
     */
    function calculaMultaServicos() {
        var valorTotalMulta = 0;

        jQuery('.multa-servico').map(function() {
            valorTotalMulta += parseMoney(jQuery(this).val());
        });;

        jQuery('.multa-servico-total').val(valorTotalMulta.toMoney());
        jQuery('.valor-total-servicos').val(valorTotalMulta.toMoney());
    };

    /**
     * Recalcula o valor da rescis�o ao alterar o valor da taxa de retirada
     */
    jQuery('.container-multas').on('keyup keydown keypress blur change focus', '.valor-total-taxas', function() {
        calculaTotalRescisao();
    });

    /**
     * Calcula o valor total da rescis�o
     */
    function calculaTotalRescisao() {
        if (!jQuery('.valor-total-multa').val()
            || !jQuery('.valor-total-multa').val().length) {
            return;
        }

        // Recalcula a multa de loca��o
        calculaMultaLocacao();

        // Calcula o valor das multas de monitoramento e loca��o somadas
        var valorTotalMulta = 0;

        jQuery('.contrato-cliente:checked').map(function() {
            var row   = jQuery(this).closest('tr')
              , multa = parseMoney(row.find('.contrato-multa-total').val());

            valorTotalMulta += multa;
        });

        // Multas de loca��o
        // valorTotalMulta += parseMoney(jQuery('.multa-locacao-soma-geral').val());
        valorTotalMulta += parseMoney(jQuery('#totalMensalidadeEquipamento').val());

        // Seta o valor total da multa de monitoramente e de loca��o
        jQuery('.valor-total-multa').val(valorTotalMulta.toMoney());
        
        var valorFaltanteLocacao = valorTotalMulta - parseMoney(jQuery('#totalMensalidadeIndevido').val());
        jQuery('#totalDiferencaIndevido').val(valorFaltanteLocacao.toMoney());

        // Calcula o valor total das taxas de loca��o
        var valorTotalTaxas = parseMoney(jQuery('.valor-total-taxas').val());

        // Calcula o total das faturas
        var valorTotalFaturas = parseMoney(jQuery('.valor-total-faturas').val());


        /** Soma os valores pagos indevidos pelo cliente **/
        var valorIndevidoMonitoramento = parseMoney(jQuery('#valorPagoIndevidoMonitoramentoTotal').val());
        var valorIndevidoLocacao = parseMoney(jQuery('#totalMensalidadeIndevido').val());
        var somaIndevido = valorIndevidoMonitoramento + valorIndevidoLocacao

        //Calcula o valor a pagar do cliente monitoramento
        var diferencaValorMonitoramento = parseMoney(jQuery('#valorMultaMensalidade').val()) - valorIndevidoMonitoramento;
        if (diferencaValorMonitoramento < 0){
            jQuery("#valorMultaMensalidadeDevolver").val(Math.abs(diferencaValorMonitoramento).toMoney());
        } else{
            jQuery("#valorMultaMensalidadeFaltante").val((diferencaValorMonitoramento).toMoney());
        }
		
		//Calcula o valor a pagar do cliente locacao
		var diferencaValorLocacao = parseMoney(jQuery('#totalMensalidadeEquipamento').val()) - valorIndevidoLocacao;
		
        if(diferencaValorLocacao < 0){
			jQuery("#totalDiferencaIndevido").val((0).toMoney());
		}else{
			jQuery("#totalDiferencaIndevido").val((diferencaValorLocacao).toMoney());
		}		
		
		var valorNaoDevolucao = parseMoney(jQuery('.valor-total-multa-nao-devolucao').val());

        // Calcula o total da rescis�o
        var totalRescisao = valorTotalMulta
                          + valorTotalTaxas
                          + valorTotalFaturas
                          + valorNaoDevolucao;
        totalRescisao = totalRescisao - somaIndevido;

        jQuery('.total-rescisao').val(totalRescisao.toMoney());
    };

    /**
     * Finaliza a rescis�o e valida os campos do formul�rio
     */
    jQuery('body').on('click', '.rescisao-finalizar', function() {
        // Remove todos os alertas e elementos marcados
        clearErrors();
        var rescisaoMotivo     = jQuery('.rescisao-motivo')
          , rescisaoStatus     = jQuery('.rescisao-status')
          , rescisaoVencimento = jQuery('.rescisao-vencimento')
          , totalRescisao       =jQuery(".total-rescisao");

        if (rescisaoMotivo.val() == '0') {
            addError(rescisaoMotivo, '� necess�rio informar o motivo.');
            return;
        }

        if (rescisaoStatus.val() == '0') {
            addError(rescisaoStatus, '� necess�rio informar informar o status.');
            return;
        }

        if (rescisaoVencimento.val().length == 0) {
            addError(rescisaoVencimento, '� necess�rio informar informar a data de vencimento.');
            return;
        }

        if (parseFloat(totalRescisao.val()) <= 0) {
            addError(totalRescisao, 'N�o � poss�vel gerar rescis�o com valores negativos.');
            return;
        }


        // Esconde o bot�o Finalizar
        jQuery('#btnFinalizarRescisao').hide();

        // Recalcula o total da rescis�o
        //calculaTotalRescisao();

        // Busca os contratos que ser�o rescindidos
        var multasContratos = [];
        jQuery('.contrato-cliente:checked').map(function() {
            
            var self = jQuery(this)
              , row  = self.closest('tr');
           
            multasContratos.push({
                connumero:   parseInt(self.val())
              , meses:       parseInt(row.find('.contrato-multa-meses-faltantes').val())
              , porcentagem: parseInt(row.find('.contrato-multa-porcentagem').val())
              , multa:       parseMoney(row.find('.contrato-multa-valor').val())
              , resmfax:     row.find('.contrato-data-recisao').val()
              
            });
            
            
        });
    
        // Busca os valores das multas de loca��o
        var multasLocacao = [];
        jQuery('.contrato-multa-locacao').map(function() {
            
            var self   = jQuery(this);
            var row    = self.closest('tr');
            
            multasLocacao.push({
                    contrato:                   row.find('.contrato-multa-locacao').val(),
                    descontoProRataLocacao :    parseMoney(row.find('.multa-locacao-desconto-pro-rata').val()),
                    totalLocacaoeAcessorios:    parseMoney(row.find('.multa-locacao-total-multa-locacao-acessorio').val()),
                });
          });
        
        // busca as multas de monitoramento
        var multasMonitoramento = [];
        jQuery('.contrato-multa-monitoramento').map(function () {

            var self = jQuery(this);
            var row = self.closest('tr');
           
            multasMonitoramento.push({
                contrato: row.find('.contrato-multa-monitoramento').val(),
                descontoProRataMonitoramento : parseMoney(row.find('.multa-monitoramento-desconto-pro-rata').val()),
                totalMonitoramento: parseMoney(row.find('.multa-monitoramento-total-multa-monitoramento').val()),
            });

        });
        
        // Busca as multas de retirada
        var multasRetirada = [];
        jQuery('.multa-retirada:checked').map(function() {
            var self = jQuery(this);
            var row  = self.closest('tr');
            if(self.prop('checked')){
                multasRetirada.push({
                    contrato:         row.find('.multa-retirada-termo').val(),
                    item:             row.find('.multa-retirada-item').val(),
                    valorRetirada:    row.find('.multa-retirada-valorRetirada').val(),
                    obroidretirada:   row.find('.multa-retirada-obroidretirada').val(),
                    eqcobroid:        row.find('.multa-retirada-eqcobroid').val(),
                    valor:            parseMoney(row.find('.multa-retirada-valor').val())
                });
            }
        });

        // Busca as multas de n�o retirada
        var multasNaoRetirada = [];
        jQuery('.multa-nao-retirada:checked').map(function() {
            var self = jQuery(this);
            var row  = self.closest('tr');
            if(self.prop('checked')){
                multasNaoRetirada.push({
                    contrato:         row.find('.multa-nao-retirada-termo').val(),
                    item:             row.find('.multa-nao-retirada-item').val(),
                    valorRetirada:    row.find('.multa-nao-retirada-valorRetirada').val(),
                    obroidretirada:   row.find('.multa-nao-retirada-obroidretirada').val(),
                    eqcobroid:        row.find('.multa-retirada-eqcobroid').val(),
                    valor:            parseMoney(row.find('.multa-nao-retirada-valor').val())
                });
            }
        });
        
        var post = jQuery.param({
            clioid:                     jQuery('#clioid').val()
          , total_rescisao:             parseMoney(jQuery('.total-rescisao').val())
          , restaxa_remocao:            parseMoney(jQuery('.valor-total-taxas').val())
          , resmvl_nao_devolucao:       parseMoney(jQuery('.valor-total-multa-nao-devolucao').val())
          , resmultaMonitoramento:      parseMoney(jQuery('#valorMultaMensalidade').val())
          , resdescontoMonitoramento:   parseMoney(jQuery('#valorPagoIndevidoMonitoramentoTotal').val())
          , resmultaLocacao:            parseMoney(jQuery('#totalMensalidadeEquipamento').val())
          , resdescontoLocacao:         parseMoney(jQuery('#totalMensalidadeIndevido').val())
          , resmmrescoid:               jQuery('.rescisao-motivo').val()
          , resmstatus:                 jQuery('.rescisao-status').val()
          , vencimento:                 jQuery('.rescisao-vencimento').val()
          , observacao_carta:           jQuery('.observacao_carta:checked').val()
          , email:                      jQuery('#email').val()
          , contratos:                  multasContratos
          , multas_locacao:             multasLocacao
          , multas_monitoramento:       multasMonitoramento
          , multas_retirada:            multasRetirada
          , multas_nao_retirada:        multasNaoRetirada
        });
        
        // Mostra loader
        jQuery('.container-finalizacao-loader').show();

        // Posta os dados via AJAX
        jQuery.ajax({
          type: 'POST',
          url: 'fin_rescisao2.php?acao=finalizarRescisao',
          data: post,
          dataType: 'json',
          success: (function(r) {
            if(!r){
              alert("Ocorreu um erro ao gerar a rescis\u00e3o.");
              jQuery('.container-finalizacao-loader').hide();
              return false;
            }

            if(r.msgRetorno){
              alert(r.msgRetorno);
              jQuery('.container-finalizacao-loader').hide();
            } else if(r.resmoid) {
              
              var urlHref = 'fin_rescisao2.php?acao=imprimir&resmoid=' + r.resmoid + '&titven=' + r.titven + '&email=' + r.email;

              if(r.idsBaixa != null){
                urlHref += '&idsbaixa=' + r.idsBaixa;
              }

              jQuery(location).attr('href', urlHref);
              
            } else {
              alert("Ocorreu um erro ao gerar a rescis\u00e3o.");
              jQuery('.container-finalizacao-loader').hide();
            }

          }),
          error : (function(r) {
            
            alert("Ocorreu um erro ao gerar a rescis\u00e3o.");
            jQuery('.container-finalizacao-loader').hide();
            
          })
        });
    });

    /**
     * Abre janela para impress�o de segunda via de carta de rescis�o
     */
    jQuery('.imprimir-segunda-via').click(function() {
        jQuery('.imprimir-segunda-via-check:checked').map(function() {
            var self    = jQuery(this)
              , clioid  = self.data('clioid')
              , resmoid = self.data('resmoid');

            window.open('fin_rescisao2.php?acao=imprimir&segunda_via=true&resmoid=' + resmoid);
        });
    });


    jQuery("#data_inicial").periodo("#data_final");
    jQuery("#data_inicial").mask("99/99/9999");
    jQuery("#data_final").mask("99/99/9999");
});

// Helper de detec��o de browser features
function css_browser_selector(u){var ua=u.toLowerCase(),is=function(t){return ua.indexOf(t)>-1},g='gecko',w='webkit',s='safari',o='opera',m='mobile',h=document.documentElement,b=[(!(/opera|webtv/i.test(ua))&&/msie\s(\d)/.test(ua))?('ie ie'+RegExp.jQuery1):is('firefox/2')?g+' ff2':is('firefox/3.5')?g+' ff3 ff3_5':is('firefox/3.6')?g+' ff3 ff3_6':is('firefox/3')?g+' ff3':is('gecko/')?g:is('opera')?o+(/version\/(\d+)/.test(ua)?' '+o+RegExp.jQuery1:(/opera(\s|\/)(\d+)/.test(ua)?' '+o+RegExp.jQuery2:'')):is('konqueror')?'konqueror':is('blackberry')?m+' blackberry':is('android')?m+' android':is('chrome')?w+' chrome':is('iron')?w+' iron':is('applewebkit/')?w+' '+s+(/version\/(\d+)/.test(ua)?' '+s+RegExp.jQuery1:''):is('mozilla/')?g:'',is('j2me')?m+' j2me':is('iphone')?m+' iphone':is('ipod')?m+' ipod':is('ipad')?m+' ipad':is('mac')?'mac':is('darwin')?'mac':is('webtv')?'webtv':is('win')?'win'+(is('windows nt 6.0')?' vista':''):is('freebsd')?'freebsd':(is('x11')||is('linux'))?'linux':'','js']; c = b.join(' '); h.className += ' '+c; return c;};
css_browser_selector(navigator.userAgent);

// Remove o evento do backspace
jQuery(document).unbind('keydown').bind('keydown', function (event) {
    var doPrevent = false;
    if (event.keyCode === 8) {
        var d = event.srcElement || event.target;
        if ((d.tagName.toUpperCase() === 'INPUT' && (d.type.toUpperCase() === 'TEXT' || d.type.toUpperCase() === 'PASSWORD'))
             || d.tagName.toUpperCase() === 'TEXTAREA') {
            doPrevent = d.readOnly || d.disabled;
        }
        else {
            doPrevent = true;
        }
    }

    if (doPrevent) {
        event.preventDefault();
    }
});

function forceCalc(){
    //Dispara funcao da linha 494 ap�s mudar a data da solicita��o
    jQuery('.contrato-multa-meses.contrato-multa-recalcula.mask-numbers').change();
}
