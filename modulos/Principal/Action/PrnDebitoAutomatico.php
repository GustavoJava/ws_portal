<?php

/*
 * require para persist�ncia de dados - classe DAO 
 */
require _MODULEDIR_ . 'Principal/DAO/PrnDebitoAutomaticoDAO.php';

require_once _SITEDIR_ . 'lib/phpMailer/class.phpmailer.php';

/**
 * PrnDebitoAutomatico.php
 * 
 * Classe para desburocratizar o d�bito autom�tico
 * Atualiza dados do cliente, forma de cobranca, endere�o de cobranca, insere hist�rico,
 * entre outras funcionalidades para ades�o, exclus�o/suspens�o de d�bito automatico
 * 
 * 
 * @author	Renato Teixeira Bueno
 * @email renato.bueno@meta.com.br
 * @since 19/09/2012
 * @package Principal
 * 
 */
class PrnDebitoAutomatico {

    /**
     * Atributo para acesso a persist�ncia de dados
     */
    private $dao;
    private $conn;

    /**
     * Atributos relacionados as variaveis recebidas no array do contrutor
     */
    private $parametros;
    private $id_cliente;
    private $id_usuario;
    private $email_cliente;
    private $cliente_email_nfe;
    private $motivo;
    private $protocolo;
    private $entrada;
    private $tipo_operacao;
    private $forma_cobranca_posterior;
    private $banco_posterior;
    private $agencia_posterior;
    private $conta_corrente_posterior;

    /**
     * Atributos relacionados aos dados anteriores de cobranca do cliente
     */
    private $forma_cobranca_anterior;
    private $descricao_forma_cobranca_anterior;
    private $banco_anterior;
    private $nome_banco_anterior;
    private $agencia_anterior;
    private $conta_corrente_anterior;
    private $debito_em_conta_anterior;
    
    /**
     * Endere�o
     */
    private $endereco_cep;
    private $endereco_pais;
    private $endereco_estado;
    private $endereco_cidade;
    private $endereco_bairro;
    private $endereco_logradouro;
    private $endereco_numero;
    private $endereco_complemento;
    private $endereco_ddd;
    private $endereco_telefone;

    /**
     *
     * Atributos relacionados aos dados do usu�rio
     *
     */
    private $nome_usuario;

    /**
     * Atributos relacionados aos dados do banco (Itau, Bradesco, etc)
     */
    private $nome_banco_posterior;
    
    /**
     * Atributo que define a origem da chamada
     */
    private $origem_chamada;

   /**
     * Flag para boleto
     */
    private $boleto;

   /**
    * @author	Willian Ouchi
    * @email	willian.ouchi@meta.com.br
    * @return	String json com os clientes retornados
    * */
    public function pesquisar() {

        $result = $this->dao->getClientes();
        
        $resultado = array();
        $resultado['clientes'] = array();
        
        $cont = 0;
        while ($rcliente = pg_fetch_assoc($result)) {
        
        	$resultado['clientes'][$cont]['clioid'] = utf8_encode($rcliente['clioid']);
        	$resultado['clientes'][$cont]['clinome'] = utf8_encode($rcliente['clinome']);
        	$resultado['clientes'][$cont]['clitipo'] = empty($rcliente['clitipo']) ? '' : $rcliente['clitipo'];
        	$resultado['clientes'][$cont]['clino_documento'] = empty($rcliente['clino_documento']) ? '' : $rcliente['clino_documento'];
        	$resultado['clientes'][$cont]['clitipo'] = empty($rcliente['clitipo']) ? '' : $rcliente['clitipo'];
        	$resultado['clientes'][$cont]['forcnome'] = empty($rcliente['forcnome']) ? '' : utf8_encode($rcliente['forcnome']);
        	$resultado['clientes'][$cont]['bannome'] = empty($rcliente['bannome']) ? '' : ($rcliente['forcdebito_conta'] == 'f') ? '' : utf8_encode($rcliente['bannome']);
        	$resultado['clientes'][$cont]['clicagencia'] = empty($rcliente['clicagencia']) ? '' : $rcliente['clicagencia'];
        	$resultado['clientes'][$cont]['clicconta'] = empty($rcliente['clicconta']) ? '' : $rcliente['clicconta'];
        
        	$cont++;
        }
        
        $resultado['total_registros'] = utf8_encode('A pesquisa retornou ' . pg_num_rows($result) . ' registro(s).');
               
        
        echo json_encode($resultado);
        exit;
    }   
    
    
    public function buscaFormaCobranca(){
    	
    	$debito_automatico = (!empty($_POST['debito'])) ? $_POST['debito'] : '';
    	
    	$arrParams = array();
    	    	    	
    	if($debito_automatico == 1){
    		$arrParams = array(' AND forcdebito_conta IS TRUE ');
    	}
    	
    	$resultado = $this->dao->buscaDadosFormaCobranca($arrParams);
    	
    	echo json_encode($resultado);
    	exit;
    	
    }
     
    
   /**
    * @author	Willian Ouchi
    * @email	willian.ouchi@meta.com.br
    * @return	String json com o detalhamento de um cliente
    * */
	public function carregarInformacoes() {

        $informacoes = array();        
               
        $informacoes['motivos'] = $this->dao->getDadosMotivos();
        $informacoes['formas_pagamento'] = $this->dao->getDadosFormaCobranca();
        $informacoes['paises'] = $this->dao->getDadosPaises();
        $informacoes['cliente'] = $this->dao->getDadosCliente();         
        $id_banco = ($informacoes['cliente']['forcdebito_conta'] == 't')? $informacoes['cliente']['bancodigo'] : null;
        $informacoes['bancos'] = $this->dao->getDadosBanco($id_banco);
        $informacoes['estados'] = $this->dao->getDadosEstados($informacoes['cliente']['endpaisoid']);
        
        /*
         *  Formata o campo n� Documento conforme o tipo do cliente 
         */
        if ($informacoes['cliente']['clitipo'] == 'F'){
            
            $quantidade_faltante = 11 - strlen($informacoes['cliente']['clino_documento']);
            for ($cont=0; $cont<$quantidade_faltante; $cont++){
                $informacoes['cliente']['clino_documento'] = '0' . $informacoes['cliente']['clino_documento'];
            }
            
            $informacoes['cliente']['clino_documento'] = 
                substr($informacoes['cliente']['clino_documento'], 0 , 3) . "." .
                substr($informacoes['cliente']['clino_documento'], 3 , 3) . "." .
                substr($informacoes['cliente']['clino_documento'], 6 , 3) . "-" .
                substr($informacoes['cliente']['clino_documento'], 9 , 2);
        }
        elseif ($informacoes['cliente']['clitipo'] == 'J'){
            
            $quantidade_faltante = 14 - strlen($informacoes['cliente']['clino_documento']);
            for ($cont=0; $cont<$quantidade_faltante; $cont++){
                $informacoes['cliente']['clino_documento'] = '0' . $informacoes['cliente']['clino_documento'];
            }
            
            $informacoes['cliente']['clino_documento'] = 
                substr($informacoes['cliente']['clino_documento'], 0 , 2) . "." .
                substr($informacoes['cliente']['clino_documento'], 2 , 3) . "." .
                substr($informacoes['cliente']['clino_documento'], 5 , 3) . "/" .
                substr($informacoes['cliente']['clino_documento'], 8 , 4) . "-" .
                substr($informacoes['cliente']['clino_documento'], 12 , 2);
        }
        
        $informacoes['cliente']['endno_cep'] = str_pad($informacoes['cliente']['endno_cep'], 8, '0', STR_PAD_LEFT);
       
        
        echo json_encode($informacoes);
        exit;
    }
        
    
    /**
    * @author	Willian Ouchi
    * @email	willian.ouchi@meta.com.br
    * @return	String json com a listagem de estados
    * */
    public function listarEstados() {
        
        $pais = (isset($_POST['pais'])) ? $_POST['pais'] : null;
        
        $informacoes = array();        
                
        $informacoes['estados'] = $this->dao->getDadosEstados($pais);
        
        echo json_encode($informacoes);
        exit;
    }    
    
    /**
    * @author	Willian Ouchi
    * @email	willian.ouchi@meta.com.br
    * @return	String json com a listagem de cidades
    * */
    public function listarCidades() {

        $estado = (isset($_POST['estado'])) ? $_POST['estado'] : null;
        
        $informacoes = array();

        $informacoes['cidades'] = $this->dao->getDadosCidades($estado); 

        echo json_encode($informacoes);            
        exit;
    }
    
    
    /**
    * @author	Willian Ouchi
    * @email	willian.ouchi@meta.com.br
    * @return	String json com a listagem de Bairros
    * */
    public function listarBairros() {

        $estado = (isset($_POST['estado'])) ? $_POST['estado'] : null;        
        $cidade = (isset($_POST['cidade'])) ? $_POST['cidade'] : null;
        
        $informacoes = array();

        $informacoes['bairros'] = $this->dao->getDadosBairros($estado, $cidade); 

        echo json_encode($informacoes);            
        exit;
    }
        
    
    /**
    * @author	Willian Ouchi
    * @email	willian.ouchi@meta.com.br
    * @return	String json com o endere�o
    * */
    public function buscarEndereco() {

        $cep = (isset($_POST['cep'])) ? $_POST['cep'] : null; 
        
        $informacoes = array();
        $informacoes['endereco'] = $this->dao->getDadosEndereco($cep); 
        $informacoes['estados'] = $this->dao->getDadosEstados(1); 
        echo json_encode($informacoes);            
        
        exit;
    }
    
    /**
    * @author	Willian Ouchi
    * @email	willian.ouchi@meta.com.br
    * @return	String json com a forma de pagamento
    * */
    public function buscarFormaCobranca() {
        
        $forma_pagamento = (isset($_POST['forma_cobranca'])) ? $_POST['forma_cobranca'] : null;
		$id_proposta = (isset($_POST['id_proposta'])) ? $_POST['id_proposta'] : null;
        
        $informacoes = array();        
                
        $informacoes['forma_cobranca'] = $this->dao->getDadosFormaCobranca($forma_pagamento, $id_proposta);
		
		if($id_proposta){
		
			foreach($informacoes['forma_cobranca'] as $formas_cobrancas){
				$banco = $this->dao->getDadosBanco($formas_cobrancas['banco']);
				$informacoes['bancos'][] = $banco[0];
			}
			
		} else {
			$id_banco = ($informacoes['forma_cobranca'][0]['debito_em_conta'] == 't')? $informacoes['forma_cobranca'][0]['banco'] : null;
			$informacoes['bancos'] = $this->dao->getDadosBanco($id_banco);
		}
		
       
        
        echo json_encode($informacoes);
        exit;
    }
    
    
    /**
    * @author	Willian Ouchi
    * @email	willian.ouchi@meta.com.br
    * @return	String json com a valida��o de e-mail
    * */
    public function validarEmail() {
        
        $email = (isset($_POST['email'])) ? $_POST['email'] : null;
        
        $informacoes = true;         	
		
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$informacoes = false;
		}
		
        echo json_encode($informacoes);
        exit;
    }
    
    
    /**
     * @author	Willian Ouchi
     * @email	willian.ouchi@meta.com.br
     * @return	String json com o detalhamento de um cliente
     * */
    public function confirmar() {
        
        $pDebitoAutomatico = array();
        $pDebitoAutomatico['id_cliente']                = (isset($_POST['clioid'])) ? $_POST['clioid'] : null;
        $pDebitoAutomatico['id_usuario']                = $_SESSION['usuario']['oid'];
        $pDebitoAutomatico['motivo']                    = (isset($_POST['msdaoid'])) ? $_POST['msdaoid'] : null;
        $pDebitoAutomatico['protocolo']                 = (isset($_POST['protocolo'])) ? $_POST['protocolo'] : "";
        $pDebitoAutomatico['entrada']                   = "I";
        $pDebitoAutomatico['tipo_operacao']             = (isset($_POST['operacao'])) ? $_POST['operacao'] : null;
        $pDebitoAutomatico['forma_cobranca_posterior']  = (isset($_POST['forcoid'])) ? $_POST['forcoid'] : null;
        $pDebitoAutomatico['banco_posterior']           = (isset($_POST['bancodigo'])) ? $_POST['bancodigo'] : null;
        $pDebitoAutomatico['agencia_posterior']         = (isset($_POST['clicagencia'])) ? $_POST['clicagencia'] : null;
        $pDebitoAutomatico['conta_corrente_posterior']  = (isset($_POST['clicconta'])) ? $_POST['clicconta'] : null;
        $pDebitoAutomatico['email_cliente']             = (isset($_POST['cliemail'])) ? $_POST['cliemail'] : null;
        $pDebitoAutomatico['email_cliente_nfe']         = (isset($_POST['cliemail_nfe'])) ? $_POST['cliemail_nfe'] : null;      
        $pDebitoAutomatico['origem_chamada']	        = 'DA'; 
        $pDebitoAutomatico['endereco_ddd']              = (isset($_POST['endddd'])) ? $_POST['endddd'] : null; 
        $pDebitoAutomatico['endereco_telefone']         = (isset($_POST['endfone'])) ? $_POST['endfone'] : null;       
        $pDebitoAutomatico['endereco_cep']              = (isset($_POST['endno_cep'])) ? $_POST['endno_cep'] : null;
        $pDebitoAutomatico['endereco_pais']             = (isset($_POST['endpaisoid'])) ? $_POST['endpaisoid'] : null;
        $pDebitoAutomatico['endereco_estado']           = (isset($_POST['endestoid'])) ? $_POST['endestoid'] : null; 
        if ($pDebitoAutomatico['endereco_pais'] && $pDebitoAutomatico['endereco_estado']){
            $pDebitoAutomatico['endereco_estado_sigla']     = $this->dao->getDadosEstados($pDebitoAutomatico['endereco_pais'], $pDebitoAutomatico['endereco_estado']); 
        }
        $pDebitoAutomatico['endereco_cidade']           = (!empty($_POST['endcidade'])) ? $_POST['endcidade'] : null; 
        $pDebitoAutomatico['endereco_bairro']           = (!empty($_POST['endbairro'])) ? $_POST['endbairro'] : null;
        $pDebitoAutomatico['endereco_logradouro']       = (isset($_POST['endlogradouro'])) ? $_POST['endlogradouro'] : null;
        $pDebitoAutomatico['endereco_numero']           = (isset($_POST['endno_numero'])) ? $_POST['endno_numero'] : null;
        $pDebitoAutomatico['endereco_complemento']      = (isset($_POST['endcomplemento'])) ? $_POST['endcomplemento'] : null;
        $pDebitoAutomatico['forcdebito_conta_anterior'] = (isset($_POST['forcdebito_conta_anterior'])) ? $_POST['forcdebito_conta_anterior'] : null;
        $pDebitoAutomatico['forcdebito_conta']          = (isset($_POST['forcdebito_conta'])) ? $_POST['forcdebito_conta'] : null;
        
        $this->setParametros($pDebitoAutomatico);
              
        $this->processar(true);
        
       
    }

    
    /*
     * Insere registro no hist�rico de d�bito autom�tico
     * 
     * @autor Renato Teixeira Bueno
     * @email renato.bueno@meta.com.br
     */

    private function inserirHistoricoDebAutomatico() {
    	
        $this->parametros['forma_cobranca_anterior'] = $this->forma_cobranca_anterior;

        /*
         * Se o tipo de opera��o for I - Inclus�o ou A - Altera��o
         * Ent�o for�a o motivo como nulo
         */
        if($this->tipo_operacao == 'E'){
            if(empty($this->motivo)){
                throw new Exception('Motivo n�o informado.');
            }
            $this->parametros['motivo'] = $this->motivo;
        } else {
            $this->parametros['motivo'] = 'null';
        }
        
        /*
         * Se o tipo da opera��o n�o for I -Inclus�o
         * Ent�o buscamos os dados banc�rios anteriores para inserir no hist�rico
         * - Banco anterior
         * - Agencia anterior
         * - Conta corrente anterior
         */
        if ($this->tipo_operacao != 'I') {

            $this->parametros['banco_anterior'] = $this->banco_anterior;
            $this->parametros['agencia_anterior'] = $this->agencia_anterior;
            $this->parametros['conta_corrente_anterior'] = $this->conta_corrente_anterior;
        }
        
        /*
         * Se o tipo da opera��o for E - Exclus�o
         * Ent�o for�amos os dados banc�rios como nulos para inserir no hist�rico
         */
        $this->parametros['banco_posterior'] = ($this->tipo_operacao == 'E') ? null : $this->banco_posterior;
        $this->parametros['agencia_posterior'] = ($this->tipo_operacao == 'E') ? 'null' : "'" . $this->agencia_posterior . "'";
        $this->parametros['conta_corrente_posterior'] = ($this->tipo_operacao == 'E') ? 'null' : "'" . $this->conta_corrente_posterior . "'";

        $this->parametros['forma_cobranca_posterior'] = $this->forma_cobranca_posterior;
       
        /*
         * M�todo da classe DAO que insere o hist�rico de d�bito autom�tico
         */
        $this->dao->inserirHistoricoDebAutomatico($this->parametros);
    }

    /*
     * Atualiza dados das propostas, relacionadas aos contratos ativos do cliente
     * - agencia
     * - conta corrente
     * 
     * @autor Renato Teixeira Bueno
     * @email renato.bueno@meta.com.br
     */

    private function atualizarPropostas() {

        /*
         * Se o tipo da opera��o for E - Exclus�o
         * Ent�o for�amos a agencia e conta corrente como nulos
         */
        $agencia = ($this->tipo_operacao == 'E') ? 'null' : "'" . $this->agencia_posterior . "'";
        $conta_corrente = ($this->tipo_operacao == 'E') ? 'null' : "'" . $this->conta_corrente_posterior . "'";
        
        $this->dao->atualizarPropostas($this->id_cliente, $this->forma_cobranca_posterior, $agencia, $conta_corrente);
    }

    /*
     * Atualizar dados das propostas de pagamento, relacionadas as propostas relacionadas aos contratos ativos do cliente
     * - banco
     * - agencia
     * - conta corrente
     * 
     * @autor Renato Teixeira Bueno
     * @email renato.bueno@meta.com.br
     */

    private function atualizarPropostasPagamento() {

        /*
         * Se o tipo da opera��o for E - Exclus�o
         * Ent�o for�amos o banco, agencia e conta corrente como nulos
         */
        $banco = ($this->tipo_operacao == 'E') ? null : $this->banco_posterior;
        $agencia = ($this->tipo_operacao == 'E') ? 'null' : "'" . $this->agencia_posterior . "'";
        $conta_corrente = ($this->tipo_operacao == 'E') ? 'null' : "'" . $this->conta_corrente_posterior . "'";
       
        $this->dao->atualizarPropostasPagamento($this->id_cliente, $this->forma_cobranca_posterior, $banco, $agencia, $conta_corrente);
    }

    /*
     * Atualiza dados dos contratos de pagamento relacionados aos contratos ativos do cliente
     * - banco
     * - agencia
     * - conta corrente
     * 
     * @autor Renato Teixeira Bueno
     * @email renato.bueno@meta.com.br
     */

    private function atualizarContratosPagamento() {
		
        /*
         * Se o tipo da opera��o for E - Exclus�o
         * Ent�o for�amos o banco, agencia e conta corrente como nulos
         */
        $banco = ($this->tipo_operacao == 'E') ? null : $this->banco_posterior;
        $agencia = ($this->tipo_operacao == 'E') ? 'null' : "'" . $this->agencia_posterior . "'";
        $conta_corrente = ($this->tipo_operacao == 'E') ? 'null' : "'" . $this->conta_corrente_posterior . "'";
        
        $this->dao->atualizarContratosPagamento($this->id_cliente, $this->forma_cobranca_posterior, $banco, $agencia, $conta_corrente);
    }

    /*
     * Atualiza dados do cliente
     * Email, Email_nfe e forma de cobranca
     * 
     * @autor Renato Teixeira Bueno
     * @email renato.bueno@meta.com.br
     */

    private function atualizarCliente() {
        
        $campos_update_cliente = array("cliformacobranca = $this->forma_cobranca_posterior");
        
        /**
         * Usuario altera��o
         * 
         * Caso exista id do usuario que est� alterando o cliente, inserimos seu id
         * caso n�o exista inserimos o id do usu�rio autom�tico 2750
         */
        if(!empty($this->id_usuario)) {
            array_push($campos_update_cliente, "cliusuoid_alteracao = $this->id_usuario");
        } else {
            array_push($campos_update_cliente, "cliusuoid_alteracao = 2750");
        }
        
        /*
         * SE a origem da chamada for DA - D�bito Autom�tico
         * Atualiza os emails (email e email_nfe) do cliente se o tipo de opera��o n�o for E- Exclus�o
         * Ou se for uma Altera��o/Exclusao
         * 
         */
        if($this->origem_chamada == 'DA'){
    			
   			if ($this->tipo_operacao != 'E' || ($this->tipo_operacao == 'E' && $this->alteracao_exclusao)) {
   				
   				$clienteemail = (!empty($this->cliente_email)) ? "'".$this->cliente_email."'" : 'null';
   				 
   				array_push($campos_update_cliente, "cliemail = $clienteemail");
   				 
   				$clienteemail_nfe =  (!empty($this->cliente_email_nfe)) ? "'".$this->cliente_email_nfe."'" : 'null';
   				
   				array_push($campos_update_cliente, "cliemail_nfe = $clienteemail_nfe");	   				
   			}
	   		
       	}

        $campos = implode(', ', $campos_update_cliente);

        $this->dao->atualizarCliente($this->id_cliente, $campos);
    }

    /*
     * Atualiza somente os dados do endere�o que forem informados para atualizar
     * 
     * @autor Renato Teixeira Bueno
     * @email renato.bueno@meta.com.br
     */

    private function atualizarEnderecoCobranca() {

        $campos_update_endereco = array();
        
        /*
         * Atualiza o endere�o de cobran�a do cliente apenas se a origem da chamada for 
         * DA - D�bito Autom�tico
         */
        if($this->origem_chamada == 'DA') {

	        /*
	         * Mota um array com os campos que ser�o atualizados na tabela endere�o	
	         */
	        $cep = (!empty($this->endereco_cep)) ? (int) preg_replace('/[^\d]/', '', $this->endereco_cep) : 'null';
	
			array_push($campos_update_endereco, "endno_cep = $cep");
	
	
	        $pais = (!empty($this->endereco_pais)) ?(int) preg_replace('/[^\d]/', '', $this->endereco_pais) : 'null';
	
	        array_push($campos_update_endereco, "endpaisoid = $pais");
	
	            
	        $estado = (!empty($this->endereco_estado)) ? (int) preg_replace('/[^\d]/', '', $this->endereco_estado) : 'null';
	
			array_push($campos_update_endereco, "endestoid = $estado");
	
	            	
	        $sigla_estado = (!empty($this->endereco_estado_sigla)) ? "'".$this->endereco_estado_sigla."'" : 'null';
	            
	        array_push($campos_update_endereco, "enduf = $sigla_estado");
	        
	        	
	        $endereco_cidade = (!empty($this->endereco_cidade)) ? "'".utf8_decode($this->endereco_cidade)."'" : 'null';
	        	
	        array_push($campos_update_endereco, "endcidade = $endereco_cidade");
	
	        	
	        $endereco_bairro = (!empty($this->endereco_bairro)) ? "'".utf8_decode($this->endereco_bairro)."'" : 'null';
	        	
	        array_push($campos_update_endereco, "endbairro = $endereco_bairro");
	
	            
	        $endereco_logradouro = (!empty($this->endereco_logradouro)) ? "'".utf8_decode($this->endereco_logradouro)."'" : 'null';
	        	
	        array_push($campos_update_endereco, "endlogradouro =$endereco_logradouro");
	
	            
	        $numero =  (!empty($this->endereco_numero)) ? (int) preg_replace('/[^\d]/', '', $this->endereco_numero) : 'null';
	
			array_push($campos_update_endereco, "endno_numero = $numero");
	        
		
	        $endereco_complemento = (!empty($this->endereco_complemento)) ? "'".$this->endereco_complemento."'" : 'null';
	        	
			array_push($campos_update_endereco, "endcomplemento = $endereco_complemento");
	        
		
	        $ddd = (!empty($this->endereco_ddd)) ? (int) preg_replace('/[^\d]/', '', $this->endereco_ddd) : 'null';
	
			array_push($campos_update_endereco, "endddd = $ddd");
	        
		
	        $endereco_telefone = (!empty($this->endereco_telefone)) ? "'".$this->endereco_telefone."'" : 'null';
	        	
	        array_push($campos_update_endereco, "endfone = $endereco_telefone");
	        
	        
	        $fone_array  =  (!empty($this->endereco_ddd) && !empty($this->endereco_telefone))  ? "'{".$this->endereco_ddd.$this->endereco_telefone."}'" : 'null';
	        
	        array_push($campos_update_endereco, "endfone_array = $fone_array");
        }
        
        /*
         * Trata o array para enviar para a classe DAO
         */
        $campos = (!empty($campos_update_endereco)) ? implode(', ', $campos_update_endereco) : '';
        
		$this->dao->atualizarEnderecoCobranca($this->id_cliente, $campos);

    }

    /*
     * Insere o hist�rico do cliente atrav�s da function do banco de dados cliente_historico_i
     * 
     * @autor Renato Teixeira Bueno
     * @email renato.bueno@meta.com.br
     */

    private function inserirHistoricoCliente() {

        /*
         * Tipo da a��o executada (A = Altera��o cadastral)
         */
        $tipo_acao = 'A';

        $id_atendimento = 'null';

        /*
         * Busca dados da forma de cobranca posteriores
         */
        $dados_posteriores = $this->dao->getDadosFormaCobranca($this->forma_cobranca_posterior);
        $descricao_forma_cobranca_posterior = utf8_decode($dados_posteriores[0]['descricao_forma_cobranca']);
        
        /*
         * Se houver protocolo informado insere no hist�rico do cliente
         */
        $protocolo_cliente = "";
        if(!empty($this->protocolo)){
        	$protocolo_cliente = "N� Protocolo: $this->protocolo;";
        }
        
        /*
         * Texto para inserir no historico do cliente de: para:
         */
        $texto_alteracao = "Altera��o: forma de cobran�a de: $this->descricao_forma_cobranca_anterior para: $descricao_forma_cobranca_posterior;
                                       banco de: $this->nome_banco_anterior para: $this->nome_banco_posterior;
                                       ag�ncia de: $this->agencia_anterior para:  $this->agencia_posterior;
                                       conta corrente de: $this->conta_corrente_anterior para: $this->conta_corrente_posterior;
        							   $protocolo_cliente";
        
        $params = array(
            'id_cliente' => $this->id_cliente,
            'id_usuario' => $this->id_usuario,
            'texto_alteracao' => $texto_alteracao,
            'tipo' => $tipo_acao,
            'protocolo' => $this->protocolo,
            'id_atendimento' => $id_atendimento
        );

        $this->dao->inserirHistoricoCliente($params);
    }
    
   /*
    * Insere o hist�rico do contrato atrav�s da function do banco de dados historico_termo_i
    * para todos os contratos ativos e n�o estejam excluidos do cliente
    *
    * @autor Renato Teixeira Bueno
    * @email renato.bueno@meta.com.br
    */
    
    private function inserirHistoricoContrato() {
            
    	/*
    	 * Busca dados da forma de cobranca posteriores
    	*/
    	$dados_posteriores = $this->dao->getDadosFormaCobranca($this->forma_cobranca_posterior);
    	$descricao_forma_cobranca_posterior = utf8_decode($dados_posteriores[0]['descricao_forma_cobranca']);
    
        if($this->tipo_operacao == 'E') {
    		$this->nome_banco_posterior = "";
    		$this->agencia_posterior = "";
    		$this->conta_corrente_posterior = "";
    	}
    
    	/*
    	 * Texto para inserir no historico do contrato de: para:
    	*/
    	$texto_alteracao = "Altera��o: forma de cobran�a de: $this->descricao_forma_cobranca_anterior para: $descricao_forma_cobranca_posterior;
    	banco de: $this->nome_banco_anterior para: $this->nome_banco_posterior;
    	ag�ncia de: $this->agencia_anterior para:  $this->agencia_posterior;
    	conta corrente de: $this->conta_corrente_anterior para: $this->conta_corrente_posterior;";
    
    	//Valida��o para o id do cliente
    	if(!empty($this->id_cliente)){
    		
	    	// Busca os contratos ativos do cliente para atualizar o historico de todos
	        $contratos_ativos = $this->dao->getContratosAtivosByCliente($this->id_cliente);
	        	
	        if(count($contratos_ativos) > 0){
	        	
	        	foreach($contratos_ativos as $contrato_ativo){
	        			
	        		$params = array(
	        				'numero_contrato' 	=> $contrato_ativo['connumero'],
	        				'id_usuario' 		=> $this->id_usuario,
	        				'texto_alteracao' 	=> $texto_alteracao,
	        				'protocolo' 		=> $this->protocolo
	        		);
	        		        			
	        		$this->dao->inserirHistoricoContrato($params);
	        	}		
	        }
    	}
        	
        	
    }
    
   /*
    * Insere o hist�rico do pr�-cadastro
    *
    * @autor Renato Teixeira Bueno
    * @email renato.bueno@meta.com.br
    */
    
    private function inserirHistoricoProposta() {
    
        if($this->tipo_operacao == 'E') {
            $this->nome_banco_posterior = "";
            $this->agencia_posterior = "";
            $this->conta_corrente_posterior = "";
    	}
    
        /*
    	 * Busca dados da forma de cobranca posteriores
    	*/
    	$dados_posteriores = $this->dao->getDadosFormaCobranca($this->forma_cobranca_posterior);
    	$descricao_forma_cobranca_posterior = utf8_decode($dados_posteriores[0]['descricao_forma_cobranca']);
    
    	/*
    	 * Texto para inserir no historico da proposta de: para:
    	*/
    	$texto_alteracao = "Altera��o: forma de cobran�a de: $this->descricao_forma_cobranca_anterior para: $descricao_forma_cobranca_posterior;
    	banco de: $this->nome_banco_anterior para: $this->nome_banco_posterior;
    	ag�ncia de: $this->agencia_anterior para:  $this->agencia_posterior;
    	conta corrente de: $this->conta_corrente_anterior para: $this->conta_corrente_posterior;";
    
    	$params = array(
    			'id_proposta' => $this->identificador_historico,
    			'id_usuario' => $this->id_usuario,
    			'texto_alteracao' => $texto_alteracao
    	);
    	
    
    	$this->dao->inserirHistoricoProposta($params);
    }

    /*
     * Atualiza dados da cobran�a relacionada ao cliente
     * OBS: Deve ser o ultimo m�todo a ser chamado para garantir que os historicos sejam inseridos corretamentos
     * com as informa��es de antes e depois (de: para:)
     * 
     * @autor Renato Teixeira Bueno
     * @email renato.bueno@meta.com.br
     */

    private function atualizarCobranca() {

        /*
         * Se o tipo da opera��o for E - Exclus�o
         * Ent�o for�amos a agencia e conta corrente como nulos
         * 
         * Trata a vari�vel $agencia e $conta_corrente para n�o enviar para o banco com tra�o ("-")
         * Ex: conta corrente: 32758-1 => 327581
         * 	   agencia: 3218-3 => 32183
         */
        $agencia = ($this->tipo_operacao == 'E') ? 'null' : substr(preg_replace('/[^\d]/', '', $this->agencia_posterior), 0, 4);
        $conta_corrente = ($this->tipo_operacao == 'E') ? 'null' : preg_replace('/[^\d]/', '', $this->conta_corrente_posterior);
        

        $this->dao->atualizarCobranca($this->id_cliente, $this->forma_cobranca_posterior, $agencia, $conta_corrente);
    }

    /*
     * Atualiza os t�tulos futuros que n�o tenham gerado arquivo para o banco 
     * E cuja forma de cobran�a seja Cobran�a Registrada
     * Ou Boleto
     * Ou D�bito Autom�tico
     * relacionados ao cliente
     * 
     * @autor Renato Teixeira Bueno
     * @email renato.bueno@meta.com.br
     */

    private function atualizarTitulos() {

        /*
         * Se a for uma exclusao do d�bito automatico ou a forma de cobran�a posterior for boleto setamos a forma de cobranca default para 74 - Cobran�a Registrada HSBC
         * Sen�o setamos a forma de cobran�a escolhida pelo usu�rios
         * 
         * Trata a vari�vel $agencia e $conta_corrente para n�o enviar para o banco com tra�o ("-")
         * Ex: conta corrente: 32758-1 => 327581
         * 	   agencia: 3218-3 => 32183
         */
        $banco = ($this->tipo_operacao == 'E') ? 'null' : preg_replace('/[^\d]/', '', $this->banco_posterior);
        $conta_corrente = ($this->tipo_operacao == 'E') ? 'null' : preg_replace('/[^\d]/', '', $this->conta_corrente_posterior);

        $this->dao->atualizarTitulos($this->id_cliente, $this->forma_cobranca_posterior, $banco, $conta_corrente);
    }

    /*
     * Prepara o email com todas as regras de neg�cio
     * Se o tipo de opera��o for I- Inclus�o ou E - Exclusao
     * E exista pelo menos um contrato ativo relacionado ao cliente
     * Ent�o envia email com o termo de acordo com a opera��o ( Inclus�o uo Exclus�o).
     * 
     * Se o tipo de opera��o for A - Altera��o
     * E a forma de cobran�a anterior for d�bito autom�tico 
     * E a forma de cobranca ANTERIOR n�o for d�bito autom�tico
     * Ent�o envia email como EXCLUS�O
     * 
     * @autor Renato Teixeira Bueno
     * @email renato.bueno@meta.com.br
     */

    private function prepararEmail() {
    	
        $email_destinatario = $this->dao->getEmailCliente($this->id_cliente);
         
        /*
         * Envia email apenas se o cliente tiver um email cadastrado nos campos
         *  :cliemail ou :cliemail_nfe
         */
        
        if (!empty($email_destinatario->email_cliente)) {
        	
            $arrEmail = array();

            $enviar_email = false;

            /*
             * Titulo dos termos para efetuar a busca do texto de envio
             */
            $inclusao = 'Termo de INCLUS�O de D�bito Autom�tico';
            $exclusao = 'Termo de EXCLUS�O de D�bito Autom�tico';
           
            /*
             * Envia email apenas quando:
             * O tipo da opera��o for I- Inclusao ou E - Exclusao
             */
            if (in_array($this->tipo_operacao, array('I', 'E'))) {

                $arrEmail['subject'] = ($this->tipo_operacao == 'I') ? "Inclus�o de d�bito autom�tico" : "Exclus�o de d�bito autom�tico";

                if($this->tipo_operacao == 'I'){
	                $mensagem = $this->dao->getModeloTexto($inclusao);	
	                
                }else{
                	$mensagem = $this->dao->getModeloTexto($exclusao);
                }
                

                $arrEmail['msg'] = $mensagem->texto_mensagem;
                
                if(empty($arrEmail['msg'])){
                	throw new Exception('002');
                }

                $enviar_email = true;
            }

            /*
             * Se o tipo da opera��o for A - Altera��o 
             * E a forma de cobran�a anterior d�bito em conta
             * E a forma de cobran�a posterior n�o for d�bito em conta
             * Ent�o envia email como E - Exclusao
             */
            if ($this->tipo_operacao == 'E' && $this->alteracao_exclusao) {

                $arrEmail['subject'] = "Exclus�o de d�bito autom�tico";

                $mensagem = $this->dao->getModeloTexto($exclusao);
                
                $arrEmail['msg'] = $mensagem->texto_mensagem;
                
                if(empty($arrEmail['msg'])){
                	throw new Exception('002');
                }

                $enviar_email = true;
            }
            
            if (!$enviar_email) {
            	throw new Exception('002');
            }

            $arrEmail['add_address'] = $email_destinatario->email_cliente;

            return array('enviar' => true, 'mail' => $arrEmail);
        }

        return array('enviar' => false);
    }

    /*
     * Envia email para o cliente com os termo de Inclus�o ou Exclus�o do d�bito autom�tico
     * 
     * @autor Renato Teixeira Bueno
     * @email renato.bueno@meta.com.br
     */

    private function enviarEmail() {
    	
        $enviar_email = $this->prepararEmail();

        if(!$enviar_email['enviar']){
        	throw new Exception('002');
        }
        	
        $mail = new PHPMailer();
        $mail->ClearAllRecipients();

        $mail->IsSMTP();
        $mail->From = "sascar@sascar.com.br";
        $mail->FromName = "Sascar";

		$mail->Subject = $enviar_email['mail']['subject'];

        $mail->MsgHTML($enviar_email['mail']['msg']);

        if ($_SESSION['servidor_teste'] == 1){
            $mail->AddAddress('willian.ouchi@meta.com.br');
            $mail->AddAddress('renato.bueno@meta.com.br');
            $mail->AddAddress('lucas.mendes@sascar.com.br');
        }
        else{
            $mail->AddAddress($enviar_email['mail']['add_address']);
        }

        if(!$mail->Send()){
           	 throw new Exception('002');
		}
        
    }

    /**
     * M�todo principal, respons�vel por chamar todos os outros m�todos da classe em sua devida ordem.
     * 
     * @autor Renato Teixeira Bueno
     * @email renato.bueno@meta.com.br
     */
    public function processar($is_ajax = null) {

        try {
        	
			pg_query($this->conn, 'BEGIN');

            $this->inserirHistoricoDebAutomatico(); 
            $this->atualizarPropostas();
            $this->atualizarPropostasPagamento();
            $this->atualizarContratosPagamento();
            $this->atualizarCliente();
            
            /*
             * Insere historico para todos os contratos ativos e que n�o estejam excluidos do cliente
             */
            $this->inserirHistoricoContrato();
            
           /*
            * Insere o historico da proposta
            * Apenas se a origem da chamada for
            * 	PC - Pr�-Cadastro
            */
            if($this->origem_chamada == 'PC') {
            	$this->inserirHistoricoProposta();
            }
            
            /*
             * S� atualiza o endere�o de cobran�a se primeiramente a requisicao vier da tela de D�bito Autom�tico
             * E se vier ent�ao se o tipo de opera��o n�o for E - Exclus�o / Exclusao
             * entao alteramos os dados de endere�o
             */
            if($this->origem_chamada == 'DA'){
                if ($this->tipo_operacao != 'E' || ($this->tipo_operacao == 'E' && $this->alteracao_exclusao)) {
                    $this->atualizarEnderecoCobranca();
                }
            }

            $this->inserirHistoricoCliente();
            $this->atualizarCobranca();
            $this->atualizarTitulos();
            
            pg_query($this->conn, 'COMMIT');
            
            
            if($this->tipo_operacao == 'E' || $this->tipo_operacao == 'I'){
                // STI 71131 - Ajuste para que a funcionalidade n�o utilize servico de envio de email. 
                // Este ajuste � necessario devido ao ambiente de desenvolvimento n�o permitir a utiliza��o desta funcionalidade, 
                // ocasionando impacto no desenvolvimento de WS que consumir� este servi�o.
            	//$this->enviarEmail();
            	
            	/*
            	 * Validado de acordo com o servidor devido ao uso da classe pela BRQ atrav�s de webservice
            	 */            	
            	if($_SESSION['servidor_teste'] != 1){
            		
            		/*
            		 * Verifica se o cliente possui pelo menos um contrato ativo para envio de email
            		 */
            		$is_contrato_ativo = $this->dao->contratoAtivoCliente($this->id_cliente);
            		 
            		if($is_contrato_ativo){
            			$this->enviarEmail();
            		}
            	}
            	
            }
            if ($is_ajax){
                echo json_encode(array('error' => false));
                exit();
            }
            else{
                return array('error' => false);
            }
            
        } catch (Exception $e) {
            pg_query($this->conn, 'ABORT');
                       
            if ($is_ajax){
                echo json_encode(array('error' => true, 'message' => utf8_encode($e->getMessage())));
                exit();
            }
            else{
                return array('error' => true, 'message' => utf8_encode($e->getMessage()));
            }
        }
    }

    /*
     * M�todo respons�vel por receber os par�metros e setar os atributos necess�rios para o processo de d�bito autom�tico
     * 
     * @autor Renato Teixeira Bueno
     * @email renato.bueno@meta.com.br
     */

    public function setParametros($arrParams) {
    	
        
    	
    	/*
    	 * Origem da chamada
    	 * 	CF - Contrato Financeiro
		 *	CS - Contrato
		 *	DA - D�bito Autom�tico
		 *	PC - Pr�-Cadastro
    	 */
    	$this->origem_chamada = $arrParams['origem_chamada'];
    	
    	/*
    	 * Atributo variavel de acordo com a origem da chamada para inserir os historicos
    	 * Caso a origem da chamada seja Cf - Contrato Financeiro ou CS - Contrato recebe o numero do contrato    	 * 
    	 * E caso seja PC - pr�-cadastro recebe o id da proposta
    	 */    	
    	$this->identificador_historico = $arrParams['identificador_historico'];
    	
    	/*
    	 * Temos 2 tipo de exclusao de d�bito autom�tico
    	 * 1� Exclus�o / Exclus�o
    	 * 2� Altera��o / Exclus�o
    	 * 
    	 * Utilizamos esta flag para identificarmos com qual dos tipos de exclus�o estamos trabalhando
    	 */
    	$this->alteracao_exclusao = false;
    	
        $this->parametros = $arrParams;
        
        /*
         * POST debito em conta anterior e posterior
         */
        $this->debito_em_conta_anterior_post = $arrParams['forcdebito_conta_anterior'];
        $this->debito_em_conta_posterior_post = $arrParams['forcdebito_conta'];
        
        /*
         * Cliente
         */
        $this->id_cliente = $arrParams['id_cliente'];
        $this->cliente_email = $arrParams['email_cliente'];
        $this->cliente_email_nfe = $arrParams['email_cliente_nfe'];

        $this->id_usuario = $arrParams['id_usuario'];
        $this->motivo = $arrParams['motivo'];
        $this->protocolo = $arrParams['protocolo'];
        $this->entrada = $arrParams['entrada'];
        $this->tipo_operacao = $this->parametros['tipo_operacao'];

        /*
         * Dados banc�rios
         */
        $this->banco_posterior 			= $arrParams['banco_posterior'];
        $this->agencia_posterior 		= $arrParams['agencia_posterior'];
        $this->conta_corrente_posterior = $arrParams['conta_corrente_posterior'];
        
        /*
         * Flag para forma de cobran�a
         * Se for boleto setamos com true sen�o false
         */
        $this->boleto = ($arrParams['forma_cobranca_posterior'] == 1) ? true : false;
        
        /*
         * Caso de Exclusao de D�bito automatico
         */
        if($this->debito_em_conta_anterior_post == 't' && $this->debito_em_conta_posterior_post == 'f') {
        	
            $this->banco_posterior = '';
            $this->agencia_posterior = '';
            $this->conta_corrente_posterior = '';
            
            /*
             * Se a forma de cobranca posterior for diferente de 1 - Boleto
             * Verificamos o tipo da opera��o para setarmos a flag de alteracao_exclusao
             */
	            if($this->parametros['tipo_operacao'] == 'A'){
	            	$this->alteracao_exclusao = true;
	            }
            
            $this->tipo_operacao = 'E';
            $this->parametros['tipo_operacao'] = 'E';
            
        }
        
        
        /*
         * Se a forma de cobranca anterior do cliente n�o for D�bito Autom�tico e a forma de cobran�a posterior for
         * tratamos como uma Inclus�o de D�bito Autom�tico
         */
        if($this->debito_em_conta_anterior_post == 'f' && $this->debito_em_conta_posterior_post == 't') {
        	$this->tipo_operacao = 'I';
        	$this->parametros['tipo_operacao'] = 'I';
        }
        
        /*
         * Se a forma de cobranca anterior do cliente for D�bito Autom�tico e a forma de cobran�a posterior tamb�m for
         * Ou
         * A forma de cobran�a anterior do cliente n�o for D�bito Autom�tico e a forma de cobran�a posterior tamb�m n�o for
         * tratamos como uma Altera��o
         */
        
    	if($this->parametros['tipo_operacao'] != 'E'){
        
	        if(($this->debito_em_conta_anterior_post == 't' && $this->debito_em_conta_posterior_post == 't') || ($this->debito_em_conta_anterior_post == 'f' && $this->debito_em_conta_posterior_post == 'f')) {
	        	$this->tipo_operacao = 'A';
	        	$this->parametros['tipo_operacao'] = 'A';
	        }
    	}
                

        /*
         * Endere�o
         */
        $this->endereco_cep = $arrParams['endereco_cep'];
        $this->endereco_pais = $arrParams['endereco_pais'];
        $this->endereco_estado = $arrParams['endereco_estado'];
        $this->endereco_estado_sigla = $arrParams['endereco_estado_sigla'][0]['estuf'];
        $this->endereco_cidade = $arrParams['endereco_cidade'];
        $this->endereco_bairro = $arrParams['endereco_bairro'];
        $this->endereco_logradouro = $arrParams['endereco_logradouro'];
        $this->endereco_numero = $arrParams['endereco_numero'];
        $this->endereco_complemento = $arrParams['endereco_complemento'];
        $this->endereco_ddd = $arrParams['endereco_ddd'];
        $this->endereco_telefone = $arrParams['endereco_telefone'];

        /**
         *
         * Busca os dados anteriores de cobranca do cliente
         *
         */
        $dados_cobranca_anterior = $this->dao->getFormaCobrancaAnterior($this->id_cliente);
        $this->forma_cobranca_anterior = $dados_cobranca_anterior->forma_cobranca;
        $this->descricao_forma_cobranca_anterior = $dados_cobranca_anterior->descricao_forma_cobranca;
        $this->debito_em_conta_anterior = $dados_cobranca_anterior->debito_em_conta;
        
        $this->banco_anterior = '';
        $this->nome_banco_anterior = '';
        $this->agencia_anterior = '';
        $this->conta_corrente_anterior = '';
        
        if($this->debito_em_conta_anterior == 't'){
            $this->banco_anterior = $dados_cobranca_anterior->banco;
            $this->nome_banco_anterior = $dados_cobranca_anterior->nome_banco;
            $this->agencia_anterior = $dados_cobranca_anterior->agencia;
            $this->conta_corrente_anterior = $dados_cobranca_anterior->conta_corrente;
            
        }
        
        /*
         * Se for uma Exclusao da Tela de Debito Automatico e N�O for uma altera��o/exclusao ou se for BOLETO
         * setamos a forma de cobran�a como 74 - Cobran�a Registrada HSBC
         * Sen�o setamos a forma de cobran�a selecionada pelo usuario
         */
        $this->forma_cobranca_posterior = (($this->tipo_operacao == 'E' && $this->origem_chamada == 'DA' && !$this->alteracao_exclusao) || $this->boleto) ? 74 : $arrParams['forma_cobranca_posterior'];
        
        /**
         *
         * Busca dados do usu�rio que efetuou a a��o
         *
         */
        $dados_usuario = $this->dao->getDadosUsuario($this->id_usuario);
        $this->nome_usuario = $dados_usuario->nome_usuario;
        
        /**
         * Busca o nome do banco
         */
        if(!empty($this->banco_posterior)){
	        $dados_banco = $this->dao->getDadosBanco($this->banco_posterior);
	        $this->nome_banco_posterior = $dados_banco[0]['nome_banco'];
	        
        } else {
            
        	$dados_cobranca = $this->dao->getBancoPorFormaCobranca($this->forma_cobranca_posterior);
        	
                $this->banco_posterior = '';
        	$this->nome_banco_posterior = '';
        	
                if(count($dados_cobranca) > 0){
                    $this->banco_posterior = $dados_cobranca[0]['id_banco'];
                    $this->nome_banco_posterior = $dados_cobranca[0]['nome_banco'];
                }
        }
        
        
        /*
         * Verifica se a opera��o envolve o debito automatico para setar banco, agencia e conta como nulos
         */
        if($this->debito_em_conta_posterior_post == 'f'){
            $this->banco_posterior = "";
            $this->nome_banco_posterior = "";
            $this->agencia_posterior = "";
            $this->conta_corrente_posterior = "";
        }
        
        if($this->debito_em_conta_anterior_post == 'f'){
            $this->banco_anterior = '';
            $this->nome_banco_anterior = '';
            $this->agencia_anterior = '';
            $this->conta_corrente_anterior = '';
        }
                       
    }
    
    /*
     * Retorna os dados da forma de cobran�a
     */
    public function getFormaCobrancaCliente($id_cliente){
    	return $this->dao->getFormaCobrancaAnterior($id_cliente);
    }

    /*
     * Construtor
     *
     * @autor Renato Teixeira Bueno
     * @email renato.bueno@meta.com.br
     */

    public function PrnDebitoAutomatico() {

        global $conn;

        $this->conn = $conn;

        /**
         * Objeto
         * - DAO
         */
        $this->dao = new PrnDebitoAutomaticoDAO($conn);
    }

}