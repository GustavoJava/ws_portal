<?php

require_once _SITEDIR_ . 'lib/phpMailer/class.phpmailer.php';

/**
 * Classe FinIsencaoFaturamento.
 * Camada de regra de neg�cio.
 *
 * @package  Financas
 * @author   MARCELLO BORRMANN <marcello.borrmann@meta.com.br>
 *
 */
class FinIsencaoFaturamento {

    /** Objeto DAO da classe */
    private $dao;

	/** propriedade para dados a serem utilizados na View. */
    private $view;

	/** Usuario logado */
	private $usuarioLogado;

    const MENSAGEM_ALERTA_CAMPOS_OBRIGATORIOS 		= "Existem campos obrigat�rios n�o preenchidos.";
    //const MENSAGEM_SUCESSO_INCLUIR            		= "Registro(s) inclu�do(s) com sucesso.";
    const MENSAGEM_SUCESSO_ATUALIZAR          		= "Registro(s) alterado(s) com sucesso.";
    const MENSAGEM_SUCESSO_EXCLUIR            		= "Registro(s) exclu�do(s) com sucesso.";
    const MENSAGEM_NENHUM_REGISTRO            		= "Nenhum registro encontrado.";
    const MENSAGEM_ERRO_PROCESSAMENTO         		= "Houve um erro no processamento dos dados.";

    /**
     * M�todo construtor.
     * @param $dao Objeto DAO da classe
     */
    public function __construct($dao = null) {


        $this->dao                   = (is_object($dao)) ? $this->dao = $dao : NULL;
        $this->view                  = new stdClass();
        $this->view->mensagemErro    = '';
        $this->view->mensagemAlerta  = '';
        $this->view->mensagemSucesso = '';
        $this->view->dados           = null;
        $this->view->camposDestaque           = null;
        $this->view->parametros      = null;
        $this->view->status          = false;
        $this->usuarioLogado         = isset($_SESSION['usuario']['oid']) ? $_SESSION['usuario']['oid'] : '';

        //Se nao tiver nada na sessao assume usuario AUTOMATICO (para CRON e WebService)
        $this->usuarioLogado         = (empty($this->usuarioLogado)) ? 2750 : intval($this->usuarioLogado);
    }
    

    /**
     * Repons�vel tamb�m por realizar a pesquisa invocando o m�todo privado
     * @return void
     */
    public function index() {

        try {
            $this->view->parametros = $this->tratarParametros();

            //Inicializa os dados
            $this->inicializarParametros();

            //Verificar se a a��o pesquisar e executa pesquisa
            if ( isset($this->view->parametros->acao) && $this->view->parametros->acao == 'pesquisar' ) {
                $this->view->dados = $this->pesquisar($this->view->parametros);
            }

        } catch (ErrorException $e) {

            $this->view->mensagemErro = $e->getMessage();

        } catch (Exception $e) {

            $this->view->mensagemAlerta = $e->getMessage();

        }

        //Incluir a view padr�o
        require_once _MODULEDIR_ . "Financas/View/fin_isencao_faturamento/index.php";
    }
    

    /**
     * Trata os parametros submetidos pelo formulario e popula um objeto com os parametros
     *
     * @return stdClass Parametros tradados
     * @return stdClass
     */
    private function tratarParametros() {

	   $retorno = new stdClass();

       if (count($_GET) > 0) {
            foreach ($_GET as $key => $value) {

                //Verifica se atributo ja existe e nao sobrescreve.
                if (!isset($retorno->$key)) {
                    
                	if(is_array($value)) {
                		
                		// Tratamento de GET com Arrays
                		foreach ($value as $chave => $valor) {
                			$value[$chave] = trim($valor);
                		}
                		$retorno->$key = isset($_GET[$key]) ? $_GET[$key] : array();
                	
                	} else {
                		$retorno->$key = isset($_GET[$key]) ? trim($value) : '';
                	}
                }
            }
        }

        if (count($_POST) > 0) {
            foreach ($_POST as $key => $value) {

                if(is_array($value)) {

                    //Tratamento de POST com Arrays
                    foreach ($value as $chave => $valor) {
                        $value[$chave] = trim($valor);
                    }
                    $retorno->$key = isset($_POST[$key]) ? $_POST[$key] : array();

                } else {
                    $retorno->$key = isset($_POST[$key]) ? trim($value) : '';
                }

            }
        }

        if (count($_FILES) > 0) {
           foreach ($_FILES as $key => $value) {

               //Verifica se atributo j� existe e n�o sobrescreve.
               if (!isset($retorno->$key)) {
                    $retorno->$key = isset($_FILES[$key]) ? $value : '';
               }
           }
        }

        return $retorno;
    }
    

    /**
     * Popula e trata os parametros bidirecionais entre view e action
     * @return void
     */
    private function inicializarParametros() {

        //Verifica se os parametros existem, sen�o iniciliza todos
        // Busca
		$this->view->parametros->placa_busca 			= isset($this->view->parametros->placa_busca) && !empty($this->view->parametros->placa_busca) ? trim($this->view->parametros->placa_busca) : ""; 		
		$this->view->parametros->conoid_busca 			= isset($this->view->parametros->conoid_busca) && !empty($this->view->parametros->conoid_busca) ? trim($this->view->parametros->conoid_busca) : "";
		$this->view->parametros->cliente_busca 			= isset($this->view->parametros->cliente_busca) && !empty($this->view->parametros->cliente_busca) ? trim($this->view->parametros->cliente_busca) : ""; 
		$this->view->parametros->docto_busca 			= isset($this->view->parametros->docto_busca) && !empty($this->view->parametros->docto_busca) ? trim($this->view->parametros->docto_busca) : ""; 
		$this->view->parametros->tipo_cliente_busca 	= isset($this->view->parametros->tipo_cliente_busca) && !empty($this->view->parametros->tipo_cliente_busca) ? trim($this->view->parametros->tipo_cliente_busca) : "";
		// Cadastro/ Edi��o
		$this->view->parametros->periodo_isencao		= isset($this->view->parametros->periodo_isencao) && !empty($this->view->parametros->periodo_isencao) ? trim($this->view->parametros->periodo_isencao) : "";
		$this->view->parametros->parfemail_contato		= isset($this->view->parametros->parfemail_contato) && !empty($this->view->parametros->parfemail_contato) ? trim($this->view->parametros->parfemail_contato) : "";
		$this->view->parametros->arrayParfoid       	= count($this->view->parametros->arrayParfoid) > 0 && !empty($this->view->parametros->arrayParfoid) ? $this->view->parametros->arrayParfoid : array();
		$this->view->parametros->arrayContratos       	= count($this->view->parametros->arrayContratos) > 0 && !empty($this->view->parametros->arrayContratos) ? $this->view->parametros->arrayContratos : array();    	
		$this->view->parametros->parfoid 				= isset($this->view->parametros->parfoid) ? $this->view->parametros->parfoid : "" ; 		
		$this->view->parametros->parfconoid 			= isset($this->view->parametros->parfconoid) && trim($this->view->parametros->parfconoid) != "" ? trim($this->view->parametros->parfconoid) : 0 ; 
		$this->view->parametros->parfclioid 			= isset($this->view->parametros->parfclioid) && !empty($this->view->parametros->parfclioid) ? trim($this->view->parametros->parfclioid) : ""; 
		$this->view->parametros->parfdt_ini_cobranca 	= isset($this->view->parametros->parfdt_ini_cobranca) ? $this->view->parametros->parfdt_ini_cobranca : "" ; 		
		$this->view->parametros->parfdt_fin_cobranca 	= isset($this->view->parametros->parfdt_fin_cobranca) ? $this->view->parametros->parfdt_fin_cobranca : "" ; 

    }
    
    
    /**
     * Valida os campos obrigat�rios.
     *
     * @return
     */
    public function validarCamposConsulta(stdClass $dados) {
    
    	//Campos para destacar na view em caso de erro
    	$camposDestaques = array();
    
    	//Verifica se houve erro
    	$error = false;
    
    	// Verifica os campos obrigat�rios
    	if ((!isset($dados->placa_busca) || trim($dados->placa_busca) == '') && 
    		(!isset($dados->conoid_busca) || trim($dados->conoid_busca) == '') && 
    		(!isset($dados->cliente_busca) || trim($dados->cliente_busca) == '') && 
    		(!isset($dados->docto_busca) || trim($dados->docto_busca) == '')) 
    	{
    		$camposDestaques[] = array(
    			'campo' => 'placa_busca'
    		);
    		$camposDestaques[] = array(
    			'campo' => 'conoid_busca'
    		);
    		$camposDestaques[] = array(
    			'campo' => 'cliente_busca'
    		);
    		$camposDestaques[] = array(
    			'campo' => 'docto_busca'
    		);
    		$error = true;
    	} 
    	// Verifica CPF/ CNPJ v�lidos 
    	elseif (isset($dados->docto_busca) && trim($dados->docto_busca) != ''){
			// Carrega o arquivo de valida��o de CPF/ CNPJ
    		require_once "lib/funcoes_validacao.php";
			
			// Deixa apenas n�meros no docto
			$docto = preg_replace( '/[^0-9]/', '', trim($dados->docto_busca));
			
			// Garante que o docto � uma string
			$docto = (string)$docto;
			
			// Verifica se docto � CNPJ (14 caracteres)
			if ( strlen($docto) === 14 ) {
				//Caso n�o seja um docto (CNPJ) v�lido
				if (!verifica_cnpj($docto)){
		    		$camposDestaques[] = array(
		    			'campo' => 'docto_busca'
		    		);    				
					$error_docto = true;					
				}
			} 
			// ou CPF (11 caracteres)  
			else {
				//Caso n�o seja um docto (CPF) v�lido
				if (!verifica_cpf($docto)){
		    		$camposDestaques[] = array(
		    			'campo' => 'docto_busca'
		    		);    				
					$error_docto = true;
				}
			}    		
    	}
    
    	if ($error) {
    		$this->view->camposDestaque = $camposDestaques;    		
    		throw new Exception("� obrigat�rio preencher ao menos um campo para realizar a pesquisa.");
    	}
    	elseif ($error_docto) {
    		$this->view->camposDestaque = $camposDestaques;    		
    		throw new Exception("CPF/ CNPJ inv�lido.");
    	}
    
    }
    

    /**
     * Validar os campos obrigat�rios do cadastro.
     *
     * @param stdClass $dados Dados a serem validados
     * @throws Exception
     * @return void
     */
    private function validarCamposCadastro(stdClass $dados) {

        //Campos para destacar na view em caso de erro
        $camposDestaques = array();

        /**
         * Verifica os campos obrigat�rios
         */
        if (!isset($dados->periodo_isencao) || trim($dados->periodo_isencao) == '') {
            $camposDestaques[] = array(
                'campo' => 'periodo_isencao'
            );
            $error = true;
        }
        if (!isset($dados->parfemail_contato) || trim($dados->parfemail_contato) == '') {
            $camposDestaques[] = array(
                'campo' => 'parfemail_contato'
            );
            $error = true;
        }
        elseif ($this->validaEmail($dados->parfemail_contato) === false) {
        	$camposDestaques[] = array(
        			'campo' => 'parfemail_contato'
        	);
        	$error_email = true;
        }

        if ($error) {
            $this->view->camposDestaque = $camposDestaques;
            throw new Exception(self::MENSAGEM_ALERTA_CAMPOS_OBRIGATORIOS);
        }
    	elseif ($error_email) {
    		$this->view->camposDestaque = $camposDestaques;    		
    		throw new Exception("Endere�o de e-mail inv�lido.");
    	}
    }


    /**
     * Respons�vel por tratar e retornar o resultado da pesquisa.
     * @param stdClass $filtros Filtros da pesquisa
     * @return array
     */
    private function pesquisar(stdClass $filtros) {

    	try{
    		 
    		$this->validarCamposConsulta($filtros);
    		
    		//Formata o docto para ser utilizado na pesquisa e atribui o tipo de pessoa para auxiliar na busca
			if (isset($filtros->docto_busca) && !empty($filtros->docto_busca)) {
	    		// Deixa apenas n�meros no docto_busca
	    		$filtros->docto_busca = $this->apenasNumeros($filtros->docto_busca);
		    	// CPF
		    	if (strlen($filtros->docto_busca) <= 11) {
		    		$filtros->tipo_cliente_busca = 'F';
		    	} 
		    	// CNPJ
		    	else {
		    		$filtros->tipo_cliente_busca = 'J';	    		
		    	}
			}
	    	
			$tiposContrato = $this->dao->pesquisarTipoContrato();
	
	    	$resultadoPesquisa = $this->dao->pesquisar($filtros,$tiposContrato);
	
	        //Valida se houve resultado na pesquisa
	        if (count($resultadoPesquisa) == 0) {
	            throw new Exception(self::MENSAGEM_NENHUM_REGISTRO);
	        }
	
	        $this->view->status = TRUE;
	
	        return $resultadoPesquisa;
    		
    	} catch (Exception $e) {
    		
    		$this->view->mensagemAlerta = $e->getMessage();
    		
    	}

    	//Formata o docto para ser exibido com m�scara
    	if (isset($filtros->docto_busca) && !empty($filtros->docto_busca)) {
    		$filtros->docto_busca = $this->docto_format($this->apenasNumeros($filtros->docto_busca));
    	}
    	
    	//Incluir a view padr�o
    	require_once _MODULEDIR_ . "Financas/View/fin_isencao_faturamento/index.php";
    }
    

    /**
     * Respons�vel por receber exibir o formul�rio de cadastro ou invocar
     * o metodo para salvar os dados
     * @param stdClass $parametros
     * @return void
     */
    public function confirmar() {
    	$dados = new stdClass();
        $dados->status = true;
        $dados->mensagem = new stdClass();
        
        //identifica se o registro foi gravado
        $registroGravado = FALSE;
        try{
        	
        	$this->view->parametros = $this->tratarParametros();

            //Incializa os parametros
            $this->inicializarParametros();
    		 
    		//
            $this->validarCamposCadastro($this->view->parametros);
                        
            //Verificar se foi submetido o formul�rio e grava o registro em banco de dados
            if (isset($_POST) && !empty($_POST)) {
                $registroGravado = $this->salvar($this->view->parametros);
            }

            $dados->status          = true;
            $dados->mensagem->tipo  = 'sucesso';
            $dados->mensagem->texto = utf8_encode($this->view->mensagemSucesso);

        } catch (ErrorException $e) {

            //Rollback em caso de erro
            $this->dao->rollback();
            $dados->status          = false;
            $dados->mensagem->tipo  = 'erro';
            $dados->mensagem->texto = utf8_encode($e->getMessage());
            $dados->camposdestaque	= $this->view->dados;

        } catch (Exception $e) {

            //Rollback em caso de erro
            $this->dao->rollback();
            $dados->status          = false;
            $dados->mensagem->tipo  = 'alerta';
            $dados->mensagem->texto = utf8_encode($e->getMessage());
            $dados->camposdestaque	= $this->view->dados;
            
        }
        
        echo json_encode($dados);
    }


    /**
     * M�todo que � chamado por AJAX para montar o modal.
     */
    public function editar(){
		
    	$this->view->parametros = $this->tratarParametros();
    	
    	//Incializa os parametros
    	$this->inicializarParametros();
    	
    	if (! empty($this->view->parametros->arrayParfoid)) {
    		
    		$idParametro = $this->view->parametros->arrayParfoid[0];
    		
		    // Busca dados do Par�metro
		    if ($dadosParametro = $this->dao->pesquisarParametro($idParametro)){
		    	$this->view->parametros->periodo_isencao = $dadosParametro->periodo_isencao;
		    	$this->view->parametros->parfdt_ini_cobranca = $dadosParametro->parfdt_ini_cobranca;
		    	$this->view->parametros->parfemail_contato = $dadosParametro->parfemail_contato; 
		    	
		    	//Se o status for 'Em Isen��o' ou 'N�o Isent�vel'
		    	if ($dadosParametro->status == 'EI' || $dadosParametro->status == 'NI') {
		    		// Exibe o campo Cancelar Isen��o
		    		$this->view->parametros->opcoes_cancelar = $this->montaCancelar($dadosParametro->parfdt_ini_cobranca, $dadosParametro->periodo_isencao, $dadosParametro->status);
		    	}
		    }
    		
    	}
	    elseif (! empty($this->view->parametros->arrayContratos)) {
    		
    		$idContrato = $this->view->parametros->arrayContratos[0];

	    	// Busca dados do Contrato
	    	if ($dadosContrato = $this->dao->pesquisarContrato($idContrato)){

	    		foreach ($dadosContrato as $resultado) {
	    			$this->view->parametros->parfemail_contato = $resultado->cliemail;
	    		}
	    	}
	    }
    		
		require_once _MODULEDIR_ . "Financas/View/fin_isencao_faturamento/formulario_cadastro.php";
		
		//var_dump($this->view->parametros);
    
    }


    /**
     * M�todo que � chamado por AJAX para montar o modal.
     */
    public function visualizar(){
		
    	$this->view->parametros = $this->tratarParametros();
    	
    	//Incializa os parametros
    	$this->inicializarParametros();
    	
    	if (! empty($this->view->parametros->parfoid)) {
    		
    		$idParametro = $this->view->parametros->parfoid;
    		
		    // Busca dados do Par�metro
		    if ($dadosParametro = $this->dao->pesquisarParametro($idParametro)){
		    	$this->view->parametros->periodo_isencao = $dadosParametro->periodo_isencao;
		    	$this->view->parametros->parfdt_ini_cobranca = $dadosParametro->parfdt_ini_cobranca;
		    	$this->view->parametros->parfemail_contato = $dadosParametro->parfemail_contato;
		    	
		    	//Se o status for 'Em Isen��o' ou 'N�o Isent�vel'
		    	if ($dadosParametro->status == 'EI' || $dadosParametro->status == 'NI') {
		    		// Exibe o campo Cancelar Isen��o
		    		$this->view->parametros->opcoes_cancelar = $this->montaCancelar($dadosParametro->parfdt_ini_cobranca, $dadosParametro->periodo_isencao, $dadosParametro->status);
		    	}
		    }
    		
    	}    		
		require_once _MODULEDIR_ . "Financas/View/fin_isencao_faturamento/formulario_cadastro.php";
    
    }

    /**
     * Grava os dados na base de dados.
     *
     * @param stdClass $dados Dados a serem gravados
     * @return void
     */
    public function salvar(stdClass $dados) {

        //Validar os campos
        $this->validarCamposCadastro($dados);

        //Inicia a transa��o
        $this->dao->begin();
        
        //Grava��o
        $gravacao = null;

        $arrayEditados = array();
        
        // Verifica se existem c�digos de parametros
		if (count($dados->arrayParfoid) > 0) {
			// Percorre os c�digos de parametros e edita
        	foreach ($dados->arrayParfoid as $parfoid) {
	        	// Busca a data de in�cio de paralisa��o cadastrada e o contrato
	        	if ($dadosParalisacao = $this->dao->pesquisarParametro($parfoid)) {
	        	
		        	// Monta um array de contratos editados
		        	$arrayEditados[] = $dadosParalisacao->parfconoid;
		        			
		        	// Calcula o tempo no in�cio de paralisa��o
	        		$dt_ini = $dadosParalisacao->parfdt_ini_cobranca;
	        				
		        	// Calcula a data de t�rmino de paralisa��o
        			$dadosDt = $this->CalculaDtFin($dados->periodo_isencao, $dt_ini);
        			$dt_fin = $dadosDt->dt_fin;
        			
					// Atribui os dados para edi��o
        			$dadosParalisacao->parfemail_contato	= $dados->parfemail_contato;
        			$dadosParalisacao->parfdt_fin_cobranca	= $dt_fin;
        			$dadosParalisacao->parfusuoid_alteracao	= $this->usuarioLogado;
        			$dadosParalisacao->parfoid				= $parfoid;
		        	// Atualiza Parametro do Faturamento
		        	$gravacao = $this->dao->atualizarParametro($dadosParalisacao);
		        				    	
			    	// Busca dados de E-mail
			    	$assunto = "Altera%o Isen%o Cliente";
		        	$dadosEmail = new stdClass();
		            if ($dadosEmail = $this->dao->pesquisarEmail($assunto)) {
			            /* 
			            	$dadosEmail->seecabecalho;
			            	$dadosEmail->seecorpo;
			            	$dadosEmail->seeimagem;
			            	$dadosEmail->seeimagem_anexo;
			            	$dadosEmail->seeremetente;
			            */
				    	if (count($dadosEmail) > 0) {
				    		// Atribui outros dados de E-mail
				    		$dadosEmail->parfemail_contato 	= $dados->parfemail_contato;
				    		$dadosEmail->periodo 			= $dt_ini . " a " . $dt_fin;
						    $dadosEmail->contrato 			= $dadosParalisacao->parfconoid;
				        	// Busca dados de Contrato para envio de email
				        	unset($resultado);
				        	if ($dadosContrato = $this->dao->pesquisarContrato($dadosParalisacao->parfconoid)) {
				        		foreach ($dadosContrato as $resultado) {
						    		$dadosEmail->clinome 			= $resultado->clinome;
						    		$dadosEmail->veiplaca 			= $resultado->veiplaca;
				        		}
				        	}
				    		// Envia email 
				    		$this->enviarEmail($dadosEmail);
				    	}
		            }
			    	
			    	// Atribui dados do Hist�rico de Paralisa��o
			    	$dadosLog = new stdClass();
			    	$dadosLog->hpfparfoid 	= $parfoid;
			    	$dadosLog->hpfconoid 	= $dadosParalisacao->parfconoid;
			    	$dadosLog->hpfusuoid 	= $this->usuarioLogado;
			    	$dadosLog->hpfprazo 	= $dadosDt->prazo;
			    	$dadosLog->hpfacao		= 'E';
			    	// Insere LOG de Paralisa��o
			    	$this->dao->inserirLog($dadosLog);
			    	
			    	// Atribui dados do Hist�rico do Termo
			    	$observacao = "
			    			Per�odo: ".$dadosParalisacao->parfdt_ini_cobranca." a ".$dadosParalisacao->parfdt_fin_cobranca.".
			    			E-mail contato: ".$dadosParalisacao->parfemail_contato.".
			    			";
			    	$dadosTermo = new stdClass();
			    	$dadosTermo->hitconnumero 	= $dadosParalisacao->parfconoid;
			    	$dadosTermo->hitusuoid 		= $this->usuarioLogado;
			    	$dadosTermo->hitobs 		= "Realizada altera��o da paralisa��o de faturamento.".$observacao;
			    	// Insere Hist�rico de Paralisa��o no Contrato
			    	$this->dao->inserirHistoricoTermo($dadosTermo);
		        	
	        	}
		        $this->view->mensagemSucesso = self::MENSAGEM_SUCESSO_ATUALIZAR;
        	}
        } 
        
        // Verifica se existem contratos
		if (count($dados->arrayContratos) > 0) { 
        	
        	// Calcula a data de in�cio de paralisa��o
        	$dt_ini = $this->CalculaDtIni();
        	
        	// Calcula a data de t�rmino de paralisa��o
        	$dadosDt = $this->CalculaDtFin($dados->periodo_isencao, $dt_ini);
        	$dt_fin = $dadosDt->dt_fin;
		    
		    // Percorre os contratos
		    foreach ($dados->arrayContratos as $connumero) {
		    	
		    	// Se o contrato n�o tiver sido editado deve ser inserida uma paralisa��o
		    	if (! in_array($connumero, $arrayEditados)) {
		    		// Busca dados do(s) contrato(s)/ obriga��es finanaceiras
			    	$dadosContrato = $this->dao->pesquisarContrato($connumero);
			    
			    	if (count($dadosContrato) > 0) {
			    		// Monta o array de obriga��es
			    		$obrigacoes = null;
			    		foreach ($dadosContrato as $resultado) {
			    			$obrigacoes.= (trim($resultado->nfiobroid) != "") ? trim($resultado->nfiobroid)."," : "";
			    		}
			    		// Atribui dados de Par�metros do Faturamento
			    		$dados->parfusuoid_cadastro	= $this->usuarioLogado;
			    		$dados->parfconoid 			= $connumero;
			    		$dados->parfclioid 			= $resultado->conclioid;
			    		$dados->parftpcoid 			= $resultado->conno_tipo;
			    		$dados->parfeqcoid			= $resultado->coneqcoid;
			    		$dados->parfdt_ini_cobranca	= $dt_ini;
			    		$dados->parfdt_fin_cobranca	= $dt_fin;
			    		$dados->parfdt_validade 	= $dt_fin;
			    		$dados->parfobroid 			= "{". substr_replace($obrigacoes, '', -1) ."}";
			    	}
		            // Insere Parametro do Faturamento
		            $parfoid = $this->dao->inserirParametro($dados);
		            
		            // Busca dados da Taxa de Paralisa��o
		            $dadosTaxa = $this->dao->pesquisarTaxa();
			    	if (count($dadosTaxa) > 0) {
			    		// Atribui outros dados da Taxa de Paralisa��o
			    		$dadosTaxa->futdt_referencia 	= $dados->parfdt_ini_cobranca;
			    		$dadosTaxa->futclioid 			= $dados->parfclioid;
			    		$dadosTaxa->futconnumero 		= $dados->parfconoid;
			    		// Insere Taxa de Paralisa��o
			    		$this->dao->inserirTaxa($dadosTaxa);
			    	}
			    	
			    	// Busca dados de E-mail
			    	$assunto = "Inclus%o Isen%o Cliente";
			    	$dadosEmail = new stdClass();
		            if ($dadosEmail = $this->dao->pesquisarEmail($assunto)) {
			            /*
			            	$dadosEmail->seecabecalho;
			            	$dadosEmail->seecorpo;
			            	$dadosEmail->seeimagem;
			            	$dadosEmail->seeimagem_anexo;
			            	$dadosEmail->seeremetente;
			            */
				    	if (count($dadosEmail) > 0) {
				    		// Atribui outros dados de E-mail
				    		$dadosEmail->parfemail_contato 	= $dados->parfemail_contato;
				    		$dadosEmail->clinome 			= $resultado->clinome;
				    		$dadosEmail->veiplaca 			= $resultado->veiplaca;
				    		$dadosEmail->contrato 			= $connumero;
				    		$dadosEmail->periodo 			= $dt_ini . " a " . $dt_fin;
				    		// Envia email 
				    		$this->enviarEmail($dadosEmail);
				    	}		            	
		            }
			    	
			    	// Atribui dados do Hist�rico de Paralisa��o
			    	$dadosLog = new stdClass();
			    	$dadosLog->hpfparfoid 	= $parfoid;
			    	$dadosLog->hpfconoid 	= $dados->parfconoid;
			    	$dadosLog->hpfusuoid 	= $this->usuarioLogado;
			    	$dadosLog->hpfprazo 	= $dadosDt->prazo;
			    	$dadosLog->hpfacao		= 'I';
			    	// Insere LOG de Paralisa��o
			    	$this->dao->inserirLog($dadosLog);
			    	
			    	// Atribui dados do Hist�rico do Termo
			    	$observacao = "
			    			Per�odo: ".$dados->parfdt_ini_cobranca." a ".$dados->parfdt_fin_cobranca.".
			    			E-mail contato: ".$dados->parfemail_contato.".
			    			";
			    	$dadosTermo = new stdClass();
			    	$dadosTermo->hitconnumero 	= $dados->parfconoid;
			    	$dadosTermo->hitusuoid 		= $this->usuarioLogado;
			    	$dadosTermo->hitobs 		= "Realizada inclus�o da paralisa��o de faturamento.".$observacao;
			    	// Insere Hist�rico de Paralisa��o no Contrato
			    	$this->dao->inserirHistoricoTermo($dadosTermo);
			    	
			    	$this->view->mensagemSucesso = "Cliente ficar� isento do faturamento de loca��o e monitoramento no per�odo de ".$dados->parfdt_ini_cobranca." a ".$dados->parfdt_fin_cobranca.".";
		    		
			    }
	     
	        }
	        
        }
	    $gravacao = TRUE;

        //Comita a transa��o
        $this->dao->commit();

        return $gravacao;
    } 
    

    /**
     * Executa a exclus�o de registro.
     * @return void
     */
    public function excluir() {

        try {
        
        	$parametros = $this->tratarParametros();
        	
        	$dados = new stdClass();
        	$dados->arrayParfoid = explode(",", $parametros->arrayParfoid);
            
            //Verifica se foi informado o id
			if (count($dados->arrayParfoid) == 0) {
                throw new ErrorException(self::MENSAGEM_ERRO_PROCESSAMENTO);
            }

            //Inicia a transa��o
            $this->dao->begin();
            
		    // Para cada registro selecionado dever� exluir a paralisa��o referente
		    foreach ($dados->arrayParfoid as $parfoid) {

            	//Realiza o CAST do c�digo do parametro
            	$parfoid = (int) $parfoid;
            	
            	// Buscar dados (contrato, email cadastrado)
            	$dadosParametro = $this->dao->pesquisarParametro($parfoid);
            	/* 
				$dadosParametro->parfconoid;
				$dadosParametro->parfdt_ini_cobranca;
				$dadosParametro->parfemail_contato;
				$dadosParametro->periodo;
				 */
    
	            // Busca dados da Taxa de Paralisa��o
	            $dadosTaxa = $this->dao->pesquisarTaxa();
	            // $dadosTaxa->obroid
		    	
		    	if (count($dadosTaxa) > 0) {

		    		// Atribui outros dados da Taxa de Paralisa��o
		    		$dadosTaxa->futdt_referencia 	= $dadosParametro->parfdt_ini_cobranca;
		    		$dadosTaxa->futconnumero 		= $dadosParametro->parfconoid;
		    		// Exclui Taxa de Paralisa��o
		    		$this->dao->excluirTaxa($dadosTaxa);
		    	}
		    
	            // Exclui Parametro do Faturamento
	            $this->dao->excluirParametro($parfoid, $this->usuarioLogado);
	            
	            
		    	// Busca dados de E-mail
		    	$assunto = "Exclus%o Isen%o Cliente";
		    	$dadosEmail = new stdClass();
	            if ($dadosEmail = $this->dao->pesquisarEmail($assunto)) {
	            	/*
	            		$dadosEmail->seecabecalho;
	            		$dadosEmail->seecorpo;
	            		$dadosEmail->seeimagem;
	            		$dadosEmail->seeimagem_anexo;
	            		$dadosEmail->seeremetente;
	            	*/
	            	if (count($dadosEmail) > 0) {
	            		// Atribui outros dados de E-mail
	            		$dadosEmail->parfemail_contato 	= $dadosParametro->parfemail_contato;
	            		$dadosEmail->periodo 			= $dadosParametro->periodo;
	            		$dadosEmail->contrato 			= $dadosParametro->parfconoid;
	            		// Busca dados de Contrato para envio de email
	            		unset($resultado);
	            		if ($dadosContrato = $this->dao->pesquisarContrato($dadosParametro->parfconoid)) {
	            			foreach ($dadosContrato as $resultado) {
	            				$dadosEmail->clinome 			= $resultado->clinome;
	            				$dadosEmail->veiplaca 			= $resultado->veiplaca;
	            			}
	            		}
	            		// Envia email
	            		$this->enviarEmail($dadosEmail);
	            	}
	            }
		    	
		    	// Atribui dados do Hist�rico de Paralisa��o
		    	$dadosLog = new stdClass();
		    	$dadosLog->hpfparfoid 	= $parfoid;
		    	$dadosLog->hpfconoid 	= $dadosParametro->parfconoid;
		    	$dadosLog->hpfusuoid 	= $this->usuarioLogado;
		    	$dadosLog->hpfprazo 	= 0;
				$dadosLog->hpfacao		= 'X';
		    	// Insere LOG de Paralisa��o
		    	$this->dao->inserirLog($dadosLog);
		    	
		    	// Atribui dados do Hist�rico do Termo
		    	$observacao = "
		    			Per�odo: ".$dadosParametro->periodo.".
		    			E-mail contato: ".$dadosParametro->parfemail_contato.".
		    			";
		    	$dadosTermo = new stdClass();
		    	$dadosTermo->hitconnumero 	= $dadosParametro->parfconoid;
		    	$dadosTermo->hitusuoid 		= $this->usuarioLogado;
		    	$dadosTermo->hitobs 		= "Realizada exclus�o da paralisa��o de faturamento.".$observacao;
		    	// Insere Hist�rico de Paralisa��o no Contrato
		    	$this->dao->inserirHistoricoTermo($dadosTermo);
	     
	        }
	        $confirmacao = TRUE;

            //Comita a transa��o
            $this->dao->commit();

            if ($confirmacao) {

                $this->view->mensagemSucesso = self::MENSAGEM_SUCESSO_EXCLUIR;
            }

        } catch (ErrorException $e) {

            //Rollback em caso de erro
            $this->dao->rollback();

            $this->view->mensagemErro = $e->getMessage();
        } catch (Exception $e) {

            //Rollback em caso de erro
            $this->dao->rollback();

            $this->view->mensagemAlerta = $e->getMessage();
        }

        $this->index();
    }


	/**
	 * Envia email ao contato cadastrado
	 * 
	 * @param stdClass $email
	 * @return boolean
	 */
	private function enviarEmail(stdClass $email) {
		
		// Atribui destinat�rio conforme ambiente
		if ($_SESSION["servidor_teste"] == 1) {
			$email->destinatario = _EMAIL_TESTE_;
		} 
		else{
			$email->destinatario = $email->parfemail_contato;
		}
		
		// Substitui as TAGs definidas pelos valores referentes ao registro
		$email->seecorpo = str_replace('[CLIENTE]', 'Cliente: '.$email->clinome, $email->seecorpo);
		$email->seecorpo = str_replace('[PLACA]', 'Ve�culo: '.$email->veiplaca, $email->seecorpo);
		$email->seecorpo = str_replace('[CONTRATO]', 'Contrato: '.$email->contrato, $email->seecorpo);
		$email->seecorpo = str_replace('[PERIODO]', 'Per�odo: '.$email->periodo, $email->seecorpo);
		
		// Atribui as vari�veis para envio
		$phpmailer = new PHPMailer();
		$phpmailer->isSmtp();
		$phpmailer->From = $email->seeremetente;
		$phpmailer->FromName = "Sascar";
		$phpmailer->ClearAllRecipients();
		$phpmailer->AddAddress($email->destinatario);
		$phpmailer->Subject = $email->seecabecalho;
		$phpmailer->MsgHTML($email->seecorpo);
	
		if (!$phpmailer->Send()) {
			throw new Exception("Houve um erro ao enviar e-mail.");
		}
		return true;
	}
	

    /**
     * Retorna apenas n�meros de uma string
     * @return 
     */
    private function apenasNumeros($docto) {
        return preg_replace('/[^0-9]/', '', $docto);
    }
    

    /**
     * Retorna o documento (CPF/CNPJ) formatado (m�scara), conforme a quantidade de d�gitos
     * @return 
     */
    private function docto_format($num) {
    	// CPF   	
    	if (strlen($num) <= 11) {
    		$num = str_pad($num, 11, "0", STR_PAD_LEFT);
    		$docto = substr($num, 0, 3) . "." . substr($num, 3, 3) . "." . substr($num, 6, 3) . "-" . substr($num, 9, 2);
    	} 
    	// CNPJ
    	else {
    		$num = str_pad($num, 14, "0", STR_PAD_LEFT);
    		$docto = substr($num, 0, 2) . "." . substr($num, 2, 3) . "." . substr($num, 5, 3) . "/" . substr($num, 8, 4) . "-" . substr($num, 12, 2);
    	}
    	return $docto;
    }
    

	/**
	 * Valida um endere�o de e-mail
	 * 
	 * @param unknown_type $email
	 * @return boolean
	 */
	private function validaEmail($email){
		if (substr_count($email , "@") == 0){
			// Verifica se o e-mail possui @
			return false;
		}
		$parseEmail = explode("@", $email);
		if (strlen($parseEmail[0]) < 3){
			//Verifica se o email tem mais de 3 caracteres
			return false;
		}
		if (!checkdnsrr($parseEmail[1], "MX")){
			// Verificar se o dom�nio existe
			return false;
		}
		return true;
	}


	/**
	 * Calcula a data de in�cio de paralisa��o
	 * 
	 * @param 
	 * @return date
	 */
	private function CalculaDtIni(){
        // Data refer�ncia = 1� do m�s atual
        $dt_ref = date('Y-m').'-01';
        $timestamp_ref = strtotime($dt_ref);
        
        /* MANTIS 7376
        // Se o dia atual � 30 ou 31, a data de in�cio da paralisa��o ser� no dia 1� daqui a dois meses
        if (date('d')>=30 || (date('m')==2 && date('d')>=28)) {
        	$timestamp_ini 	= strtotime('+2 months', $timestamp_ref);
        }
        // Sen�o, ser� no dia 1� do pr�ximo m�s
        else { 
        	$timestamp_ini 	= strtotime('+1 months', $timestamp_ref);
        }
         */
        
        // A data de in�cio da paralisa��o ser� no dia 1� do pr�ximo m�s
        $timestamp_ini 	= strtotime('+1 months', $timestamp_ref);
        
        // Formata data
        $dt_ini = date('d/m/Y', $timestamp_ini);
        
		return $dt_ini;
	}
	

	/**
	 * Calcula a data final de paralisa��o
	 * 
	 * @param
	 * @return std_class
	 */
	private function CalculaDtFin($periodo_isencao, $dt_ini){
		$retorno = new stdClass();

		// Prepara a data de in�cio
		$data = explode("/",$dt_ini);
		$dia = $data[0];
		$mes = $data[1];
		$ano = $data[2];

		// Atribui m�s e prazo conforme o per�odo de isen��o
		switch ($periodo_isencao) {
			case 30:
				$mes = ($mes + 1);
				$retorno->prazo = 1;
				break;
			case 60:
				$mes = ($mes + 2);
				$retorno->prazo = 2;
				break;
			case 90:
				$mes = ($mes + 3);
				$retorno->prazo = 3;
				break;
			case 120:
				$mes = ($mes + 4);
				$retorno->prazo = 4;
				break;
		}

		// Calcula a data de t�rmino de paralisa��o (�ltimo dia do m�s)
		$retorno->dt_fin = date('d/m/Y', mktime(0, 0, 0, $mes, 0, $ano));
		
		return $retorno;
		
	}
	
	
	/**
	 * Retorna o m�s por extenso
	 * 
	 * @param int $mes m�s num�rico
	 * @return string
	 */
	private function retornaMes($mes){
		
		switch ($mes) {
			case 1 : 
				$mes='Janeiro'; 
				break;
			case 2 : 
				$mes='Fevereiro';    
				break;
			case 3 : 
				$mes='Mar&ccedil;o';    
				break;
			case 4 : 
				$mes='Abril';    
				break;
			case 5 : 
				$mes='Maio';    
				break;
			case 6 : 
				$mes='Junho';    
				break;
			case 7 : 
				$mes='Julho';    
				break;
			case 8 : 
				$mes='Agosto';    
				break;
			case 9 : 
				$mes='Setembro'; 
				break;
			case 10 : 
				$mes='Outubro'; 
				break;
			case 11 : 
				$mes='Novembro';    
				break;
			case 12 : 
				$mes='Dezembro'; 
				break;
		}
		return $mes;
	}
	
	
	/**
	 * Retorna as op��es de checkbox referentes 
	 * aos meses do Cancelar Paralisa��o
	 * 
	 * @param 	date $dt_ini data de in�cio da paralisa��o
	 * 			int $periodo qtde meses paralisa��o
	 * @return string
	 */
	private function montaCancelar($dt_ini, $periodo, $status){
		
		$data = explode("/",$dt_ini);
		$mes_ini 	= intval($data[1]);
		$mes_fin 	= intval($mes_ini + $periodo);
		$mes_atu	= intval(date('m'));
		$count 		= 1;		

		for ($i=$mes_ini; $i<$mes_fin; $i++) {

			// Edit�vel apenas se o status for Em Isen��o.
			if ($status == 'EI'){	
				// Qquer outra op��o que n�o a �ltima deve ficar desabilitada
				$disabled = ($i != ($mes_fin-1) || $i == $mes_ini || $i <= $mes_atu) ? "disabled='DISABLED'" : "";
			}
			else{
				$disabled = "disabled='DISABLED'";
			}
			

			$opcao.= "<input id='cancelar_".$count."' name='cancelar_".$count."' value='".$i."' type='checkbox' checked='checked' ".$disabled." style='display:inline;'><label for='lbl_cancelar_".$count."'>".$this->retornaMes($i)."</label>";
			
			$count++;
		}
		
		return $opcao;
		
	}
	
}