<?php

/**
 *
 *
 * @file FinRetornoCobrancaRegistrada.php
 * @author marcioferreira
 * @version 19/09/2014 15:27:30
 * @since 19/09/2014 15:27:30
 * @package SASCAR FinRetornoCobrancaRegistrada.php
 */

ini_set('memory_limit', '640M');
ini_set('max_execution_time', 0);
set_time_limit(0);


require_once "includes/php/auxiliares.php";
require "includes/php/cliente_funcoes.php";

include('includes/classes/UploadFile.class.php');

//manipula os dados no BD
require(_MODULEDIR_ . "Financas/DAO/FinRetornoCobrancaRegistradaDAO2.php");

//classe repons�vel em enviar os e-mails
//require_once _SITEDIR_ .'modulos/Principal/Action/ServicoEnvioEmail.php';


require_once 'lib/phpMailer/class.phpmailer.php';


/**
 * 
 */
class FinRetornoCobrancaRegistrada {
	
	/**
	 * Fornece acesso aos dados necessarios para o m�dulo
	 * @property FinRetornoCobrancaRegistradaDAO
	 */
	private $dao;
	
	/**
	 * Path da pasta onde a aplica��o gerencia os aquivos de importa��o
	 * @var string
	 */
	private $caminhoArquivo;
    
	/**
	 * Construtor, configura acesso a dados e par�metros iniciais do m�dulo
	 */
	
	/**
	 * Data atual (d/m/Y)
	 * @var date
	 */
	public $dataAtual;
	
	
	/**
	 * Id da tabela execucao arquivo
	 * @var int
	 */
	public $earoid;
	
	
	/**
	 * Nome do sistema em que o usu�rio est� logado
	 * @var string
	 */
	public $sistema;
	
	
	/**
	 * String com os dados de conex�o ao schema SBTEC no banco 
	 * @var string
	 */
	public $sbtec_conexao_string;
	
	
	
    public function __construct() 
    {
		global $conn;
        
		$this->dao  = new FinRetornoCobrancaRegistradaDAO($conn);
		
		$this->caminhoArquivo = _SITEDIR_."processar_cobr_registrada";
		
		$this->data = date('d/m/Y');
		
    }


    /**
     * Cria um arquivo(caso n�o exista) para sa�da de dados,
     * chama o arquivo que cont�m a classe e o m�todo respons�vel em efetuar a importa��o
     * dos dados do arquivo em background
     * 
     * @throws Exception
     * @return multitype:number string |multitype:number NULL
     */ 
    public function prepararImportacaoDados($cod_tipo = NULL){

    	try {
    		
    		
    		/*if($cod_tipo == 15){
    			$arquivo_log = 'importacao_cobranca_retorno';
    		}*/
    		
    		if (!is_dir(_SITEDIR_."processar_cobr_registrada_log")) {
    			
    			if (!mkdir(_SITEDIR_."processar_cobr_registrada_log", 0777)) {
    				throw new Exception('Falha ao criar pasta -> processar_cobr_registrada  de log.');
    			}
    		}
    		
    		chmod(_SITEDIR_."processar_cobr_registrada_log", 0777);
    		
    		if (!$handle = fopen(_SITEDIR_."processar_cobr_registrada_log/importacao_cobranca_retorno", "w")) {
    			throw new Exception('Falha ao criar arquivo de log.');
    		}
    		
    		fputs($handle, "Processo Iniciado \r\n");
    		fclose($handle);
    		chmod(_SITEDIR_."processar_cobr_registrada_log/importacao_cobranca_retorno", 0777);
    		
    		//$return = $this->importarDados();
    		
    		//processa o arquivo em background
    		passthru("/usr/bin/php "._SITEDIR_."CronProcess/fin_retorno_cobranca_registrada_upload2.php >> "._SITEDIR_."processar_cobr_registrada_log/importacao_cobranca_retorno 2>&1 &");
    		//passthru("C:\\xampp\\\php\\php.exe " . _SITEDIR_ . "CronProcess\\fin_retorno_cobranca_registrada_upload2.php >> " . _SITEDIR_ . "processar_cobr_registrada_log\\importacao_cobranca_retorno");
    		
    		return true;
    		 
    	} catch (Exception $e) {
    		
    		$this->dao->finalizarProcesso(false, $e->getMessage());
    			
    		return false;
    	}
    	
    }
    
	/**
	 * Retoran dados de um processo de importa��o em andamento
	 * 
	 * @return Ambigous <object, boolean>
	 */
    public function verificarProcessoAndamento(){
    	 
    	$processo = $this->dao->verificarProcessoAndamento();
    	 
    	return $processo;
    	 
    }
    
    
    public function getFormaCobranca(){
    	
    	$formas = $this->dao->getFormaCobranca();
    	
    	return $formas;
    	
    }
    
    
    
    /**
     * Faz as valida��es e o upload do arquivo de retorno
     *
     * @throws Exception
     * @return number|multitype:number string
     */
    public function upload($tipo){
    
    	try{
    		
    		$msgErro = array();
    		$file = "";
    		$cod_tipo = "";

    		$forma_cobranca = (isset($_POST['busca_forma_cobranca']{0})) ? trim($_POST['busca_forma_cobranca']) : '';
    		
    		// Verificando vari�veis obrigat�rias
    		if($forma_cobranca == ''){
    			throw new exception('Informe a forma de cobran�a');
    		}
    		
    		//valida��o para importa��o 
    		if($tipo == 'dadosRetornoCobranca'){
    			//cod do tipo de processo que est� sem execu��o (Cobran�a Registrada Retorno)
    			$cod_tipo = 15;
    			
    			//recebe o nome do  arquivo via post
    			$file = $_POST['arquivo_importar'];
    		}
    		
    		if(empty($file)){
    			throw new Exception('Falha: O arquivo para importa��o n�o foi informado.',0);
    		}
    		
    		//verifica se existe dados de inicio de processo na tabela
    		$processo = $this->dao->verificarProcessoAndamento();
    		 
    		if(is_object($processo)){
    			throw new Exception('Processo de importa��o j� foi iniciado por:  '.$processo->nm_usuario.' em :  '.$processo->data_inicio.' �s '.$processo->hora_inicio ,3);
    		}
    		
    		list($nome, $ext) = explode(".",$file);
    		
    		//RET para Ita�  / DAT para HSBC
    		if($ext != 'RET' && $ext != 'DAT'){
    			return 2;
    		}
    		
    		## valida tipo de arquivo com a forma de cobran�a escolhida
    		if(($ext == 'RET' && $forma_cobranca != 73 && $forma_cobranca != 85) || ($ext == 'DAT' && $forma_cobranca != 74 )) {
    			
    			$retorno['cod'] = 3;
    			$retorno['msg'] = 'A forma de cobran�a informada n�o corresponde com o tipo de arquivo para importa��o.';
    			return $retorno;
    		}
    		
    		
    		$arquivoPath = $this->caminhoArquivo.'/'.$file;

    		if(!is_readable($arquivoPath)){
    			throw new exception('O arquivo n�o pode ser lido.');
    		}
       
    		//grava inicio do processo para controlar a importa��o do arquivo
    		$resUpload = $this->dao->iniciarProcesso($file, $cod_tipo, $forma_cobranca, $this->sistema);

    		if($resUpload != 1){
    			throw new Exception($resUpload,0);
    		}

    		//inicia o processo de importa��o em background
    		$this->prepararImportacaoDados($cod_tipo);

    		return 1;


    	}catch(Exception $e){
    	
    		$msgErro['msg'] =  json_encode(utf8_encode($e->getMessage()));
    		$msgErro['cod'] =  $e->getCode();
    		
    		return $msgErro;
    	}
    }
    

    /**
     * Para um processo de importa��o no banco pelo n�mero do PID
     * 
     * */
    public function pararImportacao() {

    	$pid = $this->dao->getPidArquivoTxt();
    	
	   	if ($pid > 0) {
	   		$retornoKill = $this->dao->killProcessDB($pid);
	   	}
	   	
    	if ($retornoKill){
    		
    		$finalizarProcesso = $this->dao->finalizarProcesso(false,'Processo cancelado.', '');
    		
    		if ($finalizarProcesso){
    			
    			$dadosProcesso = new stdClass();
    			
    			//recupera os dados do processo cancelado
    			$dadosProcesso = $this->dao->verificarProcessoFinalizado('f','Processo cancelado.');
    			
    			$dadosProcesso->earoid = $this->earoid;
    		    $dadosProcesso->id_usuario = $this->dao->usuarioID;
    			
    		    $msg_email = '';
    			$this->enviarEmail($msg_email, false, $dadosProcesso, 'pararExecucao');
    			
    			$msg = "Importa��o cancelada com sucesso.";
    		
    		} else{
    			
    			$msg = "N�o foi poss�vel gravar log de cancelamento.";
    		}
    		
    	} else{
    		$msg = "N�o existe um arquivo sendo processado atualmente.";
    	}
    	
    	return array (
			"codigo"	=> 0,
			"msg"		=> $msg,
			"retorno"	=> array()
		);

    }
    
	
    /**
     * Este m�todo � consumido em backgound chamado pelo arquivo fin_retorno_cobranca_registrada_upload.php
     * Efetua a valida��o dos dados que ser�o importados do arquivo em uma pasta
     * Verifica os par�metros de in�cio de importa��o do BD, ou seja, os dados que precisa est�o
     * no BD e no arquivo que ser� baixado
     */
    public function importarDados(){
    			
 	    try{
 	    	
 	    	$nomeProcesso = 'fin_retorno_cobranca_registrada_upload2.php';
 	    	
 	        if(burnCronProcess($nomeProcesso) === true){
 	    		echo " O processo [$nomeProcesso] est� em processamento.";
 	    		return;
 	    	}
 	    	
	      	$processo = $this->dao->verificarProcessoAndamento();
	      	
	    	if(!is_object($processo)){
	    		throw new Exception('N�o foi poss�vel processar a importa��o de dados, processo no banco n�o iniciado.');
	    	}
	    	
	    	//pesquisa os par�metros para importa��o no bd
	    	$dadosImportacao = $this->dao->consultarDadosImportacao();
	    	
	    	if(!is_object($dadosImportacao)){
	    		throw new Exception('Dados para importa��o n�o encontrados');
	    	}
	    	
	    	$this->earoid = $dadosImportacao->earoid;
	    	   	
	    	
	    	if($dadosImportacao->eartipo_processo == 15){
	    		$tipo = 'dadosRetornoCobranca';
	    	}
	    	
	    	//reupera dados para importa��o
	    	$eaparametros = $dadosImportacao->earparametros;
	    	
	    	$param = explode("|", $eaparametros);
	    	
	    	$arquivo        = $dadosImportacao->earnomearquivo;
	    	$forma_cobranca = $param[1];
	    	
	    	$linha                = 1;
	    	$qtde_atualizado_cod  = 0;
	    	
	    	//verifica o usu�rio que iniciou o processo
	    	if(empty($this->dao->usuarioID)){
	    		$this->dao->usuarioID = $dadosImportacao->earusuoid;
	    	}
	    	
	    	$cd_usuario = $this->dao->usuarioID;
	    	
	    	//pasta de arquivos
	    	$caminhoArquivo = $this->caminhoArquivo.'/'.$arquivo;
	    	
	    	//verifica se o aquivo existe na pasta
	    	if(file_exists($caminhoArquivo)){

	    		$arquivo = fopen($caminhoArquivo,'r');
	    		
	    	}else{
	    		throw new Exception('Falha-> arquivo para importa��o n�o encontrado.');
	    	}
	    	
	    	
	    	// Array de identifica��es de bancos com layout cadastrados
	    	$arrBancos = array('399','341','664'); //HSBC - Ita� - Bradesco (237)
	    	
	    	$forccfbbanco = $this->dao->getLayoutBanco($forma_cobranca);
	    	
	    	// Se a descri��o da forma de cobran�a n�o estiver cadastrada no array de layouts, informa ao usu�rio
	    	if(!in_array($forccfbbanco,$arrBancos)){
	    		throw new exception('N�o foi poss�vel gerar arquivo. Layout n�o cadastrado para forma de cobran�a');
	    	}
	    								
	    	//se o processo foi realizado no sistema SBTEC, faz outra conex�o para acessar o schema SBTEC no banco
	    	if($dadosImportacao->earsistema == 'SBTEC'){
	    		
	    		//fecha a conex�o atual
				pg_close($this->dao->conn);
				
				//faz uma nova conex�o para acessar ao sistema SBTEC
				$sbtec_conn = pg_connect($this->sbtec_conexao_string);
				
				//redefine nova vari�vel de conex�o	
				global $conn;
							
				$conn = $sbtec_conn;
							
				$this->dao  = new FinRetornoCobrancaRegistradaDAO($conn);
				  			
	    	}
						
	    	$this->dao->begin();
			    	
	    	//recupera o pid do processo no BD para gravar na tabela execucao_arquivo
	    	$pid_processo = $this->dao->getPidProcessoDB();
	    	
	    	//atualiza o processo com o n�mero do PID
	        $this->dao->atualizarProcesso($pid_processo, $this->earoid);
					
	    	
	        // PREPARES
           // Verificando data de baixa do titulo
           $this->dao->prepararVerificaBaixaTitulo();
           
            
           // Lendo o arquivo
           while (!feof($arquivo)) {
           	 
           	$buffer = stream_get_line($arquivo, (1027*60), "\n");
           	
           	// C�digo de tipo de registro
           	$cod_registro = substr($buffer,0,1);
           	$tit_numero_registro_banco=0;
           	$nome_cliente = "";
           	 
           	// Header
           	if($cod_registro == '0' && $buffer != ''){
           		 
           		$data_credito_header = substr($buffer,113,6);
           		$data_credito_header = substr($data_credito_header,0,2).'/'.substr($data_credito_header,2,2).'/'.substr($data_credito_header,4,2);
           		 
           	}
           	
           	
           	// Verificando detalhe
           	if($cod_registro == '1'){
           		
           		// Tratando novo layout HSBC (399)
           		if( $forccfbbanco == 399 ){

           			// Array de configura��o de c�digos que d�o baixa nos titulos
           			$arrCodBaixa = array('06','07','08');
           			 
           			// C�digo do OID
           			$cod_oid  = trim(substr($buffer,37,16));
           			 
           			//Numero do t�tulo (Ser� usado nas consultas, ver $titoid)
           			$num_titulo = substr($cod_oid,6,7);
           			$num_titulo =  validaVar($num_titulo,'integer');
           			 
           			 
           			// C�digo de identifica��o do banco (Nosso N�mero)
           			$tit_numero_registro_banco = substr($buffer,67,11);
           			 
           			//Tipo do Registro
           			$tipo_registro  = substr($cod_oid,0,1);
           			 
           			// Nome do Sacado / Nome do Cliente
           			if(!empty($cod_oid)){
           				 
           				$nome_cliente = $this->dao->recuperarNomeCliente($num_titulo);
           				 
           				//$nome_cliente =  recuperarNomeCliente($num_titulo, $this->conn);
           			}else{
           				$nome_cliente   = " ";
           			}
           			 
           			// Identifica��o da Ocorr�ncia
           			$cod_ocorrencia = validaVar(substr($buffer,108,2),'integer');
           			 
           			// Data da ocorr�ncia
           			$data_ocorrencia = substr($buffer,110,6);
           			$data_ocorrencia = substr($data_ocorrencia,0,2).'/'.substr($data_ocorrencia,2,2).'/'.substr($data_ocorrencia,4,2);
           			 
           			//-- Data De Vencimento
           			$data_vencimento = substr($buffer,146,6);
           			$data_vencimento = substr($data_vencimento,0,2).'/'.substr($data_vencimento,2,2).'/'.substr($data_vencimento,4,2);
           			 
           			// Valor Nominal do T�tulo
           			$valor_titulo = substr($buffer,152,11).'.'.substr($buffer,163,2);
           			 
           			// Valor Desconto do T�tulo
           			$valor_desconto_titulo = substr($buffer,240,11).'.'.substr($buffer,251,2);
           			 
           			// Valor Juros do T�tulo
           			$valor_juros_titulo = substr($buffer,266,11).'.'.substr($buffer,277,2);
           			 
           			// Valor Creditado do T�tulo
           			$valor_credito_titulo = substr($buffer,253,11).'.'.substr($buffer,264,2);
           			 
           			// Data do credito
           			$data_credito = substr($buffer,82,6);
           			$data_credito = substr($data_credito,0,2).'/'.substr($data_credito,2,2).'/'.substr($data_credito,4,2);
           			 
           			//Data Cr�dito Header
           			$data_credito_header = $data_credito;
           			 
           		}
           		 
           		 
           		 
           		// Tratando layout ITAU 341
           		if( $forccfbbanco == 341 ){

           			// Array de configura��o de c�digos que d�o baixa nos titulos
           			$arrCodBaixa = array('06','07','08');
           			 
           			// C�digo de Inscri��o
           			// Identifica��o do tipo de inscri��o/empresa
           			// 01=CPF 02=CNPJ
           			$codigo_inscricao  = substr($buffer,1,2);
           			 
           			// Codigo de referencia enviado ao banco na remessa.
           			$cod_oid        = trim(substr($buffer,37,25));
           			 
           			// C�digo do OID, podendo ser titoid ou evtioid
           			$tipo_registro  = substr($cod_oid,0,1);
           			 
           			// Se n�o for do tipo T (titulo) ou E (evento) gera erro
           			//if($tipo_registro != 'T' && $tipo_registro != 'E'){
           			//    throw new exception('Erro: Linha '.$linha.' com c�digo de OID inv�lido');
           			//}
           			 
           			// Identifica��o da Ocorr�ncia
           			$cod_ocorrencia = validaVar(substr($buffer,108,2),'integer');
           			 
           			// Data de ocorr�ncia no Banco
           			$data_ocorrencia = substr($buffer,110,6);
           			$data_ocorrencia = substr($data_ocorrencia,0,2).'/'.substr($data_ocorrencia,2,2).'/'.substr($data_ocorrencia,4,2);
           			 
           			// NOSSO NUMERO, Confirma��o do n�mero do t�tulo no banco
           			$tit_numero_registro_banco = substr($buffer,126,8);
           			 
           			//-- Data De Vencimento
           			$data_vencimento = substr($buffer,146,6);
           			$data_vencimento = substr($data_vencimento,0,2).'/'.substr($data_vencimento,2,2).'/'.substr($data_vencimento,4,2);
           			 
           			// Valor Nominal do T�tulo
           			$valor_titulo = substr($buffer,152,11).'.'.substr($buffer,163,2);
           			 
           			// Valor Juros do T�tulo
           			$valor_juros_titulo = substr($buffer,266,11).'.'.substr($buffer,277,2);
           			 
           			// Valor Desconto do T�tulo
           			$valor_desconto_titulo = substr($buffer,240,11).'.'.substr($buffer,251,2);
           			 
           			// Valor Creditado do T�tulo
           			$valor_credito_titulo = substr($buffer,253,11).'.'.substr($buffer,264,2);
           			 
           			// Data do credito
           			$data_credito = trim(substr($buffer,295,6));
           			if (!empty($data_credito)){
           				$data_credito = substr($data_credito,0,2).'/'.substr($data_credito,2,2).'/'.substr($data_credito,4,2);
           			}
           			else{
           				$data_credito = $data_credito_header;
           			}
           			 
           			 
           			// Nome do Sacado / Nome do Cliente
           			$nome_cliente   = substr($buffer,324,29);
           			 
           			if(trim($nome_cliente)=='' || trim($nome_cliente)==NULL){
           				$num_titulo   =  trim(substr($buffer,63,19));
           				if(!empty($num_titulo)){

           					$nome_cliente = $this->dao->recuperarNomeCliente($num_titulo);

           					//$nome_cliente =  recuperarNomeCliente($num_titulo, $this->conn);
           				}else{
           					$nome_cliente   = " ";
           				}
           			}
           			 
           			// Registros rejeitados ou alega��o do sacado
           			$cod_rejeicao   = validaVar(substr($buffer,377,8),'string');
           			 
           		}
           		 
           		 
           		// Tratando layout Bradeco(664)  - Bradesco 237)
           		//Foi criado o c�digo 664 para identifica��o no Bradesco no sistema, pois
           		//a conta existente (para o banco 237) n�o receber� os movimentos da nova conta criada pelo bradesco
           		//migrada do HSBC para receber o pagamentos do boletos.
           		if( $forccfbbanco == 664 ){
           		           		
           			// Array de configura��o de c�digos que d�o baixa nos titulos
           			$arrCodBaixa = array('06','09','10','17');
           			 
           			// C�digo do OID
           			$num_controle_participante  = trim(substr($buffer,37,25));
           			 
           			//Numero do t�tulo (Ser� usado nas consultas, ver $titoid)
           			$num_titulo = substr($num_controle_participante,13,7);
           			$num_titulo =  validaVar($num_titulo,'integer');
           			
           			// C�digo de identifica��o do banco (Nosso N�mero)
           			$tit_numero_registro_banco = substr($buffer,70,12);
           			
           			// Nome do Sacado / Nome do Cliente
           			if(!empty($num_controle_participante)){
           		
           				$nome_cliente = $this->dao->recuperarNomeCliente($num_titulo);
           			
           			}else{
           				$nome_cliente   = " ";
           			}
           			 
           			// Identifica��o da Ocorr�ncia
           			$cod_ocorrencia = validaVar(substr($buffer,108,2),'integer');
           			 
           			// Data da ocorr�ncia
           			$data_ocorrencia = substr($buffer,110,6);
           			$data_ocorrencia = substr($data_ocorrencia,0,2).'/'.substr($data_ocorrencia,2,2).'/'.substr($data_ocorrencia,4,2);
           			 
           			//-- Data De Vencimento
           			$data_vencimento = substr($buffer,146,6);
           			$data_vencimento = substr($data_vencimento,0,2).'/'.substr($data_vencimento,2,2).'/'.substr($data_vencimento,4,2);
           			 
           			// Valor Nominal do T�tulo
           			$valor_titulo = substr($buffer,152,11).'.'.substr($buffer,163,2);

           			// Valor Desconto do T�tulo
           			$valor_desconto_titulo = substr($buffer,240,11).'.'.substr($buffer,251,2);
           			
           			// Valor Creditado do T�tulo
           			$valor_credito_titulo = substr($buffer,253,11).'.'.substr($buffer,264,2);
           			
           			// Valor Juros do T�tulo
           			$valor_juros_titulo = substr($buffer,266,11).'.'.substr($buffer,277,2);
           			 
           			// Data do credito
           			$data_credito = substr($buffer,295,6);
           			$data_credito = substr($data_credito,0,2).'/'.substr($data_credito,2,2).'/'.substr($data_credito,4,2);
           			 
           			//Data Cr�dito Header
           			$data_credito_header = $data_credito;
           		}
           		
           		
           		if(trim($cod_ocorrencia) < 0){
           			throw new exception('Erro linha '.$linha.': N�o foi poss�vel verificar codigo de retorno');
           		}
           		 
           		 
           		//Se Banco HSBC
           		if( $forccfbbanco == 399 ){
           			$titoid = $num_titulo;
           		}elseif( $forccfbbanco == 341){
           			$titoid = validaVar(substr($cod_oid,1,strlen($cod_oid)),'integer');
           		}elseif( $forccfbbanco == 664){
           			$titoid = $num_titulo;
           		}
           		 
           		
           		$result = 0;
           		$emite_boleto_recup_ativo = false;
           		$pagamento_titulo_consolidado = false;
           		
           		
           		if(trim($titoid) != ''){
           			$result = $this->dao->getDadosTitulo($titoid,'',true);
           		}
           		 
           		if (!is_array($result)){
           			
           			//se n�o achar na tabela titulo, pesquisa na tabela titulo_retencao
           			if(trim($titoid) != ''){
           				$result = $this->dao->getTituloRetencao($titoid,'');
           			}
           			 
           			if (!is_array($result)) {
           				 
           				//Se Banco HSBC
           				if( $forccfbbanco == 399 ){
           					$titoid =  substr($buffer,68,7);
           				}elseif($forccfbbanco == 341){
           					$titoid = trim(substr($buffer,63,19));
           				}
           				 
           				//7 d�gitos do Nosso N�mero
           				if(trim($titoid) != '') {
           					
           					$result = $this->dao->getTituloKernel($titoid);
           					
           					/*
           					 * MANTIS 3707:
           					* Caso a consulta a t�tulo_kernel retorne mais que 1 registro
           					* tentar bater o valor do t�tulo com o valor pago.
           					*/
           					// Se achou pega o t�tulo correspondente e o valor
           					if ($result > 0) {
           						
           						$resultado_kernel = 0;
           						$registros_kernel = 0;
           						
           						foreach ($result as $resu){
           							
           							$titkoid = $resu['titkoid'];
           							
           							$retorno = $this->dao->getDadosTitulo($titkoid, $valor_titulo);
           							
           							if(is_array($retorno)){
           								
           								$titoid                     = $retorno[0]['titoid'] ;
           							    $titdt_credito              = $retorno[0]['titdt_credito'] ;
           							    $tit_numero_registro_banco  = $retorno[0]['titnumero_registro_banco'] ;
           							    
           							    $resultado_kernel = 1;
           							    $registros_kernel = 1;
           							    
           							    break;
           							}
           							
           						}
           						
           						$resu   = $resultado_kernel;
           						$result = $registros_kernel;
           						
           						 
           					} else {
           						
           						$ret_dados_reg = $this->dao->getDadosTituloRegistroBanco($titoid);
           						 
           						$result = 0;
           						 
           						if(is_array($ret_dados_reg)){
           							$titoid                    = $ret_dados_reg[0]['titoid'];
           							$titdt_credito             = $ret_dados_reg[0]['titdt_credito'];
           							$tit_numero_registro_banco = $ret_dados_reg[0]['titnumero_registro_banco'];
           							$result = 1;
           						}
           						
           					}
           					
           					 
           					// Sen�o, tenta buscar no boleto seco
           					if ($result == 0) {
           						
           						$result = $this->dao->getTituloRetencao('',$titoid);
           						 
           						// Se n�o achou, tenta localizar com os 8 d�gitos do Nosso N�mero
           						if (!is_array($result)){
           							 
           							//Se Banco HSBC
           							if( $forccfbbanco == 399 ){
           								//tamanho 11- com digito verificador
           								//tamanho 10- ignora o digito verificador - caso nao encontre pesquisa novamente caso haja segunda via.
           								$titoid = trim(substr($buffer,42,11));
           							}elseif($forccfbbanco == 341){
           								$titoid = trim(substr($buffer,62,20)); // T�tulos n�o registrados pelo banco. Tenta buscar o n�mero do banco na posi��o 63 do arquivo.
           							}
           							 
           							// Primeiro no t�tulo
           							if(trim($titoid) != ''){
           								
           								$result = $this->dao->getDadosTituloRegistroBanco($titoid, true);
           								
           							}
           							 
           							// sen�o achou procura no boleto seco
           							if (!is_array($result)){
           								
           								$result = $this->dao->getTituloRetencao('',$titoid);
           								 
           								// Se ainda n�o achou, tenta no t�tulo pelo ID (7 d�gitos) combinado com valor no t�tulo
           								if (!is_array($result)) {
           									
           									//Se Banco HSBC
           									if( $forccfbbanco == 399 ){
           										$titoid =  substr($buffer,68,7);
           									}elseif($forccfbbanco == 341){
           										$titoid = trim(substr($buffer,63,19));
           									}

           									$result = $this->dao->getDadosTitulo($titoid,'', false);
           									
           									
           									// Ou no boleto seco, pelo ID combinado com valor no t�tulo
           									if (!is_array($result)) {
           										
           										
           										//verifica se � um t�tulo unificado somente para o sistema da SASCAR (schema PUBLIC)
           										//if($this->sistema == 'PUBLIC'){
           										
	           										###  PESQUISA SE � UM T�TULO PAI NA TABELA titulo_consolidado
	           										
	           										//verificar se � um t�tulo pai que foi unificado pela politica de desconto
	           										$titulos_filhos = $this->dao->getTituloFilhoPolitica($titoid);
           										//}
           										
           										if(is_array($titulos_filhos)){
           											
           											//se o t�tulo n�o estiver pago efetua as baixas
           											if($titulos_filhos[0]['titcdt_pagamento'] == ''){
           												
	           											//baixa o t�tulo pai na tabela titulo_consolidado
	           											$retorno_baixas = $this->setBaixarTituloPaiFilho($titoid,
	           													$titulos_filhos,
	           													$forccfbbanco,
	           													$cd_usuario,
	           													$data_credito,
	           													$forma_cobranca,
	           													$data_ocorrencia,
	           													$valor_credito_titulo,
	           													$tit_numero_registro_banco,
	           													$linha, 
	           													$cod_ocorrencia, 
	           													$arrCodBaixa);
	           										
	           											if($retorno_baixas['status']){
	           										
	           												$array_filhos = array();
	           													           												
	           												for($i=0 ; $i < count($titulos_filhos); $i++){
	           													$array_filhos[] = $titulos_filhos[$i]['titulo_filho'];
	           												}
	           													
	           												$_filhos = implode(', ', $array_filhos);
	           													
	           												$mErroProcessamento[] = 'O t�tulo pai '.$titoid.' , foi baixado juntamente com os titulos filhos: '.$_filhos.PHP_EOL.PHP_EOL;
	           													
	           												$pagamento_titulo_consolidado = 1;
	           												
	           												$quant_filhos_baixados = count($array_filhos);

                                                            $mTitulosBaixados_filhos[] = $retorno_baixas['dados_titulos_baixados'] ;
	           												
                                                            $mTitulosJaBaixados = is_array($mTitulosJaBaixados) ? $mTitulosJaBaixados : array();
                                                            $mTitulosJaBaixados = array_merge($mTitulosJaBaixados, $retorno_baixas['dados_titulos_ja_baixados']);
                                                                                                                       
                                                            $nTotal_Baixados_filhos += $retorno_baixas['vlr_total_titulos_baixados'];
                                                            $nTotal_Ja_Baixados     += $retorno_baixas['vlr_total_titulos_ja_baixados'];
	           												
	           											}else{
	           												$mErroProcessamento[] = $retorno_baixas['msg'];
	           											}
           										
	           											
           											//se j� tiver pago, exibe no relat�rio que j� possui baixa
           											}else{
           												$mErroProcessamento[] = 'O t�tulo pai '.$titoid.' , n�o pode ser baixado pois j� possui data de baixa.'.PHP_EOL;
           											}
           											
           											
           											//limpa id do t�tulo pai para n�o tentar baixar novamente, e continua com o restante da leitura do arquivo
           											$titoid = '';
           										
           										}else{
           										
           										
           										$result = $this->dao->getTituloRetencao($titoid, '', false);
           										
           										if (is_array($result)) {
           											
           											$titoid        = $result[0]['titoid'];
           											$titdt_credito = $result[0]['titdt_credito'];
           											$titnumero_registro_banco = $result[0]['titnumero_registro_banco'];
           											
           											/**
           											 * MANTIS 3383
           											 * Incidente N� 18560 / N�o est� gerando NF quando h� pagamento de boleto seco
           											*/
           											$emite_boleto_recup_ativo = true;
           											
           										} else {
           											
           											if( $forccfbbanco == 399 ){
           												//tamanho 11- com digito verificador
           												//tamanho 10- ignora o digito verificador - caso nao encontre pesquisa novamente caso haja segunda via.
           												$titoid = trim(substr($buffer,42,11));

           											}elseif($forccfbbanco == 341){
           												$titoid = trim(substr($buffer,62,20)); // T�tulos n�o registrados pelo banco. Tenta buscar o n�mero do banco na posi��o 63 do arquivo.
           											}

           											if (empty($titoid)) {
           												$mErroProcessamento[] = 'Erro linha '.$linha.': titulo esta em branco no arquivo ';
           											} else {
           												$mErroProcessamento[] = 'Erro linha '.$linha.': N�o foi poss�vel encontrar o titulo'.$titoid;
           											}
	           											
           										}
           										 
           										
           										}// fim is_array($titulos_filhos)
           										 
           									} else{
           										
           										$titoid        = $result[0]['titoid'];
           										$titdt_credito = $result[0]['titdt_credito'];
           										$titnumero_registro_banco = $result[0]['titnumero_registro_banco'];
           										
           									}
           									
           								} else {
           									
           									$titoid        = $result[0]['titoid'];
           									$titdt_credito = $result[0]['titdt_credito'];
           									$titnumero_registro_banco = $result[0]['titnumero_registro_banco']; 
           									 
           									/**
           										* MANTIS 3383
           										* Incidente N� 18560 / N�o est� gerando NF quando h� pagamento de boleto seco
           									*/
           									$emite_boleto_recup_ativo = true;
           								}
           								 
           							} else {
           								
           								$titoid        = $result[0]['titoid'];
           								$titdt_credito = $result[0]['titdt_credito'];
           								$titnumero_registro_banco = $result[0]['titnumero_registro_banco']; 
           								
           							}
           								
           							
           						} else {
           							
           							$titoid        = $result[0]['titoid']; 
           							$titdt_credito = $result[0]['titdt_credito']; 
           							$tit_numero_registro_banco_local = $result[0]['titnumero_registro_banco']; 
           							
           							if( in_array($cod_ocorrencia,$arrCodBaixa) and !empty($titoid)) {
           								/*
           								 * Gera nota fiscal de pagamento do boleto seco, quando o retorno do banco indicar uma baixa de t�tulo
           								* antes de executar verifica qual esquema de banco de dados ser� usado para a fun��o
           								*/
           								$resul  = $this->dao->setEmiteBoletoRecuperaAtivo($tit_numero_registro_banco_local, $valor_credito_titulo, $data_ocorrencia);
           								
           								if($resul == 2){
           									
           									$mErroProcessamento[] = 'Erro linha '.$linha.': Falha ao executar fun��o ->  emite_boleto_recup_ativo: <br/>'.
           									                         'T�tulo = '. $tit_numero_registro_banco_local.' <br/>'.
           															 'Valor = '. number_format($valor_credito_titulo, 2, ',', '.').' <br/>'.
           															 'Data do Pagamento = '. $data_ocorrencia.' <br/> <br/>';
           								}
           								
           							}
           						}
           						 
           					} else {
           						//
           						// ***** Ajuste STI-80853 carescimo Venda ou Vivo
           						// Verifica se o t�tulo existe em titulo_venda
           						
           						$result = $this->dao->getDadosTituloVenda($titoid);
           						           							
           						if(is_array($result)){
           							
           							$titdt_credito = $result[0]['titdt_credito']; //;pg_fetch_result($resu,0,"titdt_credito");
           							 
           							if( in_array($cod_ocorrencia,$arrCodBaixa) and !empty($titoid)) {
           								
           								$resuNN = $this->dao->getDadosTitulo($titoid,'');
           								
           								$titnumero_registro_bancoNN = $resuNN[0]['titnumero_registro_banco'];//pg_fetch_result($resuNN,0,"titnumero_registro_banco");
           								
           								/*
           								 * Gera nota fiscal de pagamento do boleto Venda Ou Vivo, quando o retorno do banco indicar uma baixa de t�tulo
           								* antes de executar verifica qual esquema de banco de dados ser� usado para a fun��o
           								*/
           								$resul  = $this->dao->setEmiteBoletoRecuperaAtivo($titnumero_registro_bancoNN, $valor_credito_titulo, $data_ocorrencia);
           								
           								if($resul == 2){
           								
           									$mErroProcessamento[] = 'Erro linha '.$linha.': Falha ao executar fun��o ->  emite_boleto_recup_ativo: <br/>'.
           									                         'T�tulo = '. $tit_numero_registro_banco_local.' <br/>'.
           															 'Valor = '. number_format($valor_credito_titulo, 2, ',', '.').' <br/>'.
           															 'Data do Pagamento = '. $data_ocorrencia.' <br/> <br/>';
           								}
           							}
           						}
           					}
           				}
           				
           			} else {
           				
           				$emite_boleto_recup_ativo = true;
           			}
           			
           		} else {
           			
           			$emite_boleto_recup_ativo = true;
           		}
           		 
           		if(!empty($titoid)){
           			$nome_cliente =  $this->dao->recuperarNomeCliente($titoid);
           		}
           		 
           		if ($emite_boleto_recup_ativo) {
           			
           			$titdt_credito = $result[0]['titdt_credito'];   
           			$tit_numero_registro_banco_local = $result[0]['titnumero_registro_banco']; 
           			
           			if( in_array($cod_ocorrencia,$arrCodBaixa) and !empty($titoid)) {
           				
           				/*
           				 * Gera nota fiscal de pagamento do boleto seco, quando o retorno do banco indicar uma baixa de t�tulo
           				* antes de executar verifica qual esquema de banco de dados ser� usado para a fun��o
           				*/
           				$resul  = $this->dao->setEmiteBoletoRecuperaAtivo($tit_numero_registro_banco_local, $valor_credito_titulo, $data_ocorrencia);
           				
           				if($resul == 2){
           				
           					$mErroProcessamento[] = 'Erro linha '.$linha.': Falha ao executar fun��o ->  emite_boleto_recup_ativo: <br/>'.
           									         'T�tulo = '. $tit_numero_registro_banco_local.' <br/>'.
           											 'Valor = '. number_format($valor_credito_titulo, 2, ',', '.').' <br/>'.
           											 'Data do Pagamento = '. $data_ocorrencia.' <br/> <br/>';
           				}
           			}
           		}
           		
           		 
           		// Atualizando titulo ou evento titulo
           		//
           		if ($cod_ocorrencia == '02'){
               
           			$retorno = $this->dao->setDadosTitulo($cod_ocorrencia, $tit_numero_registro_banco, $titoid, '');
           			
           			if(!$retorno){
           				$mErroProcessamento[] = 'Erro linha '.$linha.': N�o foi poss�vel atualizar titulo com c�digo de ocorr�ncia 02';
           			}
           			 
           			$qtde_atualizado_cod ++;
           			 
           		} elseif ($cod_ocorrencia == '03'){

           			$retorno = $this->dao->setDadosTitulo($cod_ocorrencia, $tit_numero_registro_banco, $titoid, $cod_rejeicao);
           			
           			if(!$retorno){
           				$mErroProcessamento[] = 'Erro linha '.$linha.': N�o foi poss�vel atualizar titulo com codigo da ocorr�ncia 03';
           			}
           			
           			
           			$qtde_atualizado_cod ++;
           			
           			 
           		}elseif ($cod_ocorrencia == '04'){
           			 
           			$tipo_codigo = 4;
           			$retorno = $this->dao->setDadosEventoTitulo($titoid, $cod_ocorrencia, $forccfbbanco, $tipo_codigo);
           			
           			if(!$retorno){
           				$mErroProcessamento[] = 'Erro linha '.$linha.': N�o foi poss�vel atualizar evento do titulo ocorr�ncia 04';
           			}
           			 
           			$qtde_atualizado_cod ++;
           			
           			
           		}elseif ($cod_ocorrencia == '12'){
           			 
           			$tipo_codigo = 4;
           			$retorno = $this->dao->setDadosEventoTitulo($titoid, $cod_ocorrencia, $forccfbbanco, $tipo_codigo);
           			
           			if(!$retorno){
           				$mErroProcessamento[] = 'Erro linha '.$linha.': N�o foi poss�vel atualizar evento do titulo ocorr�ncia 12';
           			}
           			
           			
           			$qtde_atualizado_cod ++;
           			
           			
           		}elseif ($cod_ocorrencia == '14'){
           			 
           			$tipo_codigo = 6;
           			$retorno = $this->dao->setDadosEventoTitulo($titoid, $cod_ocorrencia, $forccfbbanco, $tipo_codigo);
           			
           			if(!$retorno){
           				$mErroProcessamento[] = 'Erro linha '.$linha.': N�o foi poss�vel atualizar evento do titulo ocorr�ncia 14';
           			}
           			
           			
           			$qtde_atualizado_cod ++;
           			
           			
           		}elseif ($cod_ocorrencia == '15'){
           			
           			$tipo_codigo = 2;
           			$retorno = $this->dao->setDadosEventoTitulo($titoid, $cod_ocorrencia, $forccfbbanco, $tipo_codigo, $cod_rejeicao, $titdt_credito);
           			
           			if(!$retorno){
           				$mErroProcessamento[] = 'Erro linha '.$linha.': N�o foi poss�vel atualizar evento do titulo ocorr�ncia 15';
           			}
           			
           			$qtde_atualizado_cod ++;
           			
           			
           		}elseif ($cod_ocorrencia == '16' || $cod_ocorrencia == '17'){
           			 
           			$tipo_codigo = 31;
           			$retorno = $this->dao->setDadosEventoTitulo($titoid, $cod_ocorrencia, $forccfbbanco, $tipo_codigo, $cod_rejeicao, $titdt_credito);
           			
           			if(!$retorno){
           				$mErroProcessamento[] = 'Erro linha '.$linha.': N�o foi poss�vel atualizar evento do titulo ocorr�ncia 16 ou 17';
           			}
           			
           			 
           			$qtde_atualizado_cod ++;
           			
           			
           		}elseif ($cod_ocorrencia == '18'){
           			 
           			$tipo_codigo = '2,4';
           			$retorno = $this->dao->setDadosEventoTitulo($titoid, $cod_ocorrencia, $forccfbbanco, $tipo_codigo, $cod_rejeicao, $titdt_credito, $evticod_retorno_cobr_reg = 1);
           			
           			if(!$retorno){
           				$mErroProcessamento[] = 'Erro linha '.$linha.': N�o foi poss�vel atualizar evento do titulo ocorr�ncia 18';
           			}
           			
           			 
           			$qtde_atualizado_cod ++;
           		}
           		 
           		// Processo de baixa de t�tulo
           		// Se o codigo de ocorrencia est� setado como um codigo de baixa de titulo no layout executa a baixa
           		if( in_array($cod_ocorrencia,$arrCodBaixa) && $titoid > 0) {
           			 
           			// Verificar se o titulo j� n�o esta baixado
           			// Verificando se data de pagamento j� n�o foi setada
           			
           			$res = $this->dao->executarVerificaBaixaTitulo($titoid);
           			
           			if(!is_array($res)){
           				$mErroProcessamento[] = 'Erro: Linha: '.$linha.'Msg-> '. $res;
           			}
           			
           			 
           			if (is_array($res)) {
           				
           				$dt_credito         = $res[0]['titdt_credito'];  
           				$numero_nota_fiscal = $res[0]['numero_nota_fiscal'];  
           				$titclioid          = $res[0]['titclioid'];  
           				$titvl_pagamento    = $res[0]['titvl_pagamento'];  
           				 
           				$retorno = retirarClienteOperacoes ($titoid, $titclioid, $valor_credito_titulo, $cd_usuario, "Baixa cobran�a registrada");
           			}
           			 
           			if ($retorno != "Opera��o efetuada com sucesso") {
           				
           				$this->dao->rollback();
           				//pg_query($this->conn, "ROLLBACK;");
          				
         				echo "erro --> retirarClienteOperacoes ! titoid: ". $titoid . "clioid: ". $titclioid;
       					$mErroProcessamento[] = 'Erro linha '.$linha.': '.$retorno;
           			}
           			 
           			if($dt_credito == ''){
           				 
           				//$desconto = '';
           				 
           				// Verificando vari�veis
           				if($forccfbbanco == ''){
           					$mErroProcessamento[] = 'Erro linha '.$linha.': N�o foi poss�vel selecionar banco';
           				}
           				
           				if($data_ocorrencia == ''){
           					$mErroProcessamento[] = 'Erro linha '.$linha.': N�o foi poss�vel selecionar data de ocorrencia';
           				}
           				 
           				//Calcula multa e juros e atualiza o titulo
           				$this->dao->calcularAtualizarMultaJuros($titoid, $valor_credito_titulo, $valor_desconto_titulo);
           				
           				$aux_campos = $forccfbbanco." ".$cd_usuario." ".$data_credito." ".$forma_cobranca." ".$data_ocorrencia;
           				 
           				// Montando baixa dos titulos
           				// Monta sql invocando a fun��o para realizar a baixa dos t�tulos
           				$ret_baixa =  $this->dao->setBaixaContasReceber($titoid, $aux_campos, $cd_usuario);
           				
           				###  MANTIS 7680
           				//desvincula titulo filho do titulo pai (para caso o cliente pague um titulo filho que foi unificado sem pagar o titulo pai)
           				$this->dao->desvincularTituloConsolidado($titoid);
           				
           				if(!$ret_baixa){
           					$mErroProcessamento[] = 'Erro linha '.$linha.': N�o foi poss�vel baixar t�tulo';
           				}
           				
           		           				
           				$mTitulosBaixados[] = $titoid.',,'.
             				$nome_cliente.',,'.
             				$numero_nota_fiscal.',,'.
             				$valor_credito_titulo.',,'.
             				$data_ocorrencia.',,'.
             				$valor_desconto_titulo.',,'.
             				$valor_juros_titulo.',,'.
             				$data_vencimento.',,'.
             				$valor_titulo;

           				$nTotal_Baixados += $valor_credito_titulo;
           				 
           			}else{
           				 
           				$mTitulosJaBaixados[] = $titoid.',,'.
             				$nome_cliente.',,'.
             				$numero_nota_fiscal.',,'.
             				$valor_credito_titulo.',,'.
             				$data_ocorrencia.',,'.
             				$valor_desconto_titulo.',,'.
             				$valor_juros_titulo.',,'.
             				$data_vencimento.',,'.
             				$valor_titulo;
           				 
           				$nTotal_Ja_Baixados += $valor_credito_titulo;
           				 
           			}
           		}
           	}//FIM: if($cod_registro == '1')
           	 
           	$linha ++;
           	
           	echo $linha.',';
           	
           }//FIM: while (!feof($arquivo))
            
		   //apaga arquivo da pasta	
           unlink($caminhoArquivo);
            
           $msg = 'Arquivo processado com sucesso.';
            
           if($qtde_atualizado_cod > 0){
           		$msg_aux .= ' Total de <b>'.$qtde_atualizado_cod .'</b> registros atualizados com c�digo de retorno';
           }
            
           //-- Mostra o erro conforme a requisicao: 66248
           if( count($mErroProcessamento) > 0 ){
           	foreach($mErroProcessamento as $cErro){
           		$msg_aux .= "<font color=red>".$cErro."<br>";
           	}
           	$msg_aux .= '</font><br>';
           }
            
           if( count($mTitulosBaixados) > 0 || $quant_filhos_baixados > 0){
           	
           	    //junta os dados dos t�tulos consolidados com os titulos comuns
	           	if($quant_filhos_baixados > 0){
	           		
	           		 if(is_array($mTitulosBaixados) && is_array($mTitulosBaixados_filhos) && count($mTitulosBaixados_filhos) > 0){
	           		 	
	           		 	foreach($mTitulosBaixados_filhos as $dados){
	           		 		$mTitulosBaixados = array_merge($mTitulosBaixados, $dados);
	           		 	}
	           		 	
	           		 }else{
	           		 	
	           		 	if(count($mTitulosBaixados_filhos) > 0){
	           		 		$mTitulosBaixados = Array();
	           		 		foreach($mTitulosBaixados_filhos as $key => $dados){
	           		 			$mTitulosBaixados = array_merge($mTitulosBaixados, $dados);
	           		 		}
	           		 	}
	           		 
	           		 }
	           		 
	           		 //soma valores dos t�tulos consolidados com os demais t�tulos
	           		 $nTotal_Baixados = $nTotal_Baixados + $nTotal_Baixados_filhos;
	           	}
           	
           	 
           		$retorno_html = $this->montarHtmlTitulosBaixados($mTitulosBaixados, $nTotal_Baixados);
           		
           		$msg_aux .= $retorno_html['msg_aux'];
	           	
           		$mlista_titulo_html =  $retorno_html['mlista_titulo'];
	           	
	           	$resu = $this->dao->getTipoMovimentacaoBancaria();
	           	
	           	if(is_array($resu)){
	           		$historico    = $resu[0]['tmbhistorico'];
	           		$tipo_movim   = $resu[0]['tmbtipo'];    
	           		$conta_contab = $resu[0]['tmbplcoid'];  
	           		$cod_movim    = $resu[0]['tmboid'];     
	           	}
	           	 
	           	
	           	if($quant_filhos_baixados > 0){
	        	           		
	           		if(is_array($mlista_titulo_html)){
	           			//junta os arrays com os t�tulos normais com os filhos
	           			$mlista_titulo = array_merge($mlista_titulo_html, $array_filhos);
	           			
	           		}else{
	           			$mlista_titulo = $array_filhos;
	           		}
	         
	           	}
	           	
	           	 
	           	$parametros =" \"$forccfbbanco\"
					           	\"$data_credito_header\"
					           	\"$tipo_movim\"
					           	\"NULL\"
					           	\"$historico\"
					           	\"$nTotal_Baixados\"
					           	\"$conta_contab\"
					           	\"NULL\"
					           	\"NULL\"
					           	\"NULL\"
					           	\"NULL\"
					           	\"$cod_movim\"
					           	\"$cd_usuario\"
					           	\"NULL\"
					           	\"\"
					           	\"\"
					           	\"NULL\"
					           	\"NULL\"
					           	\"NULL\"
					           	\"NULL\" ";
	           	
	           	//insere movimenta��o banc�ria e atualiza os t�tulos baixados
	           $ret_movimento =  $this->dao->setMovimentacaoBancaria($parametros, $mlista_titulo);
	           
	           if(!$ret_movimento){
	           	   throw new Exception($ret_movimento);
	           }
	           	
           }
            
           
           if( count($mTitulosJaBaixados) > 0 ){
           	
             	$msg_aux .= $this->montarHtmlTitulosComBaixa($mTitulosJaBaixados,$nTotal_Ja_Baixados );
           }
           
           //gera o arquivo CSV
           $arquivo_csv = $this->gerarArquivoCsv($msg, $qtde_atualizado_cod, $mErroProcessamento , $mTitulosBaixados, $nTotal_Baixados, $mTitulosJaBaixados, $nTotal_Ja_Baixados, $dadosImportacao->earnomearquivo);
           
           if($arquivo_csv['status']){
             	$planilhaRelatorio = $arquivo_csv['arquivo_gerado'];
           }else{
           		throw new Exception($arquivo_csv['msg_erro']);
           }
           
	    	$this->dao->commit();
	    	
	    	$msg_sucesso = 'Processo de importa��o finalizado com sucesso.';
	    	echo PHP_EOL.$msg_sucesso ;
	    	
	    	//finaliza processo com sucesso
	    	$finalizarProcesso = $this->dao->finalizarProcesso(true, $msg);
	    	
	    	//recupera os dados do processo finalizado com sucesso para enviar por e-mail
	    	$dadosProcesso = $this->dao->verificarProcessoFinalizado('t','');
	    	
	    	$dadosProcesso->earoid = $this->earoid;
	    	$dadosProcesso->msg = $msg_sucesso; 
	    	
	    	//envia email de sucesso
	    	$enviarEnmail = $this->enviarEmail($msg_aux, true, $dadosProcesso, $tipo, $planilhaRelatorio);
	    	
		    		    	
	    }catch (Exception $e){
	    	
	    	$this->dao->rollback();
	    	
	    	//finaliza processo com erro 
	    	$finalizarProcesso = $this->dao->finalizarProcesso(false, $e->getMessage());
	    	
	    	//recupera os dados do processo finalizado com erro para enviar por e-mail
	    	$dadosProcesso = $this->dao->verificarProcessoFinalizado('f','');
	    	
	    	$enviarEnmail = $this->enviarEmail($e->getMessage(), false, $dadosProcesso, 'erro');
	    	
	    	
	    	echo $e->getMessage();
	    	return $e->getMessage();
	    }
    }

    
    /**
    * 
    * 
    * @param unknown $titoid_pai
    * @param unknown $titulos_filhos
    * @param unknown $forccfbbanco
    * @param unknown $cd_usuario
    * @param unknown $data_credito
    * @param unknown $forma_cobranca
    * @param unknown $data_ocorrencia
    * @param unknown $valor_pago
    * @param unknown $tit_numero_registro_banco
    * @param unknown $linha
    * @return Ambigous <boolean, multitype:>
    */
    public function setBaixarTituloPaiFilho($titoid_pai, $titulos_filhos, $forccfbbanco, $cd_usuario, $data_credito, $forma_cobranca, $data_ocorrencia, $valor_pago, $tit_numero_registro_banco, $linha, $cod_ocorrencia, $arrCodBaixa){
    	
        $ret_baixa_pai = false;
        $mTitulosJaBaixados = array();
        $nTotal_Ja_Baixados = 0;
    	
    	//veifica se o c�digo da ocorr�ndia pode dar baixa no t�tulo
    	if(in_array($cod_ocorrencia, $arrCodBaixa) && !empty($titoid_pai)) {
    	
	    	//baixa o titulo pai na tabela titulo_consolidado
	    	$ret_baixa_pai = $this->dao->setBaixaTituloPai($titoid_pai, $cd_usuario, $valor_pago, $tit_numero_registro_banco, $forccfbbanco);
    	
    	}else{
    		
    		$mErroProcessamento[] = 'Erro linha '.$linha.': N�o foi poss�vel baixar o t�tulo pai '.$titoid_pai.' ->  ocorr�ncia = '. $cod_ocorrencia;
    	}
    	
    	//baixa os t�tulos filhos
    	if($ret_baixa_pai){
    		
    		$nome_cliente =  $this->dao->recuperarNomeCliente($titulos_filhos[0]['titulo_filho']);
    		
    		$aux_campos = $forccfbbanco." ".$cd_usuario." ".$data_credito." ".$forma_cobranca." ".$data_ocorrencia;
    		 
    		//baixa os t�tulos filhos um a um
    		for($i=0 ; $i < count($titulos_filhos); $i++){
    			
    			
    			// Verifica a baixa do t�tulo
    			$res = $this->dao->executarVerificaBaixaTitulo($titulos_filhos[$i]['titulo_filho']);
    		
    			if(!$res){
    				$mErroProcessamento[] = 'Erro: Linha: '.$linha.' N�o foi poss�vel verificar titulo baixado';
    			}
    			
    			if (is_array($res)) {
    				
                    $titoid_filho       = $titulos_filhos[$i]['titulo_filho'];
    				$dt_credito         = $res[0]['titdt_credito'];
    				$titclioid          = $res[0]['titclioid'];
    				$titvl_pagamento    = $res[0]['titvl_pagamento'];
    				$titdt_vencimento   = $res[0]['titdt_vencimento'];
    				$titvl_titulo       = $res[0]['titvl_titulo']; 
    				$titvl_juros        = $res[0]['titvl_juros'];
    				$titvl_multa        = $res[0]['titvl_multa'];
    				$titvl_desconto     = $res[0]['titvl_desconto'];
    			    $titdt_pagamento    = $res[0]['titdt_pagamento'];
                    $numero_nota_fiscal = $res[0]['numero_nota_fiscal'];
    		
    				$retorno = retirarClienteOperacoes($titulos_filhos[$i]['titulo_filho'], $titclioid, $titvl_pagamento, $cd_usuario, "Baixa cobran�a registrada");
    			}
    			 
    			if ($retorno != "Opera��o efetuada com sucesso") {
    				 
    				//$this->dao->rollback();
    				//pg_query($this->conn, "ROLLBACK;");
    				$msg_err_opera = "erro ao retirar cliente opera��es! titoid: ". $titulos_filhos[$i]['titulo_filho'] . "clioid: ". $titclioid;
    				
    				echo $msg_err_opera;
    				
    				$mErroProcessamento[] =  $msg_err_opera .'<br/>Erro linha '.$linha.': '.$retorno;
    			}
			
    			if($dt_credito == ''){	
    				//atualiza os valores dos t�tulos filhos, Valor do juros pago e multa, e desconto se houver
        			$atualiza_valor_filho = $this->dao->atualizarValoresTituloFilho($titulos_filhos[$i]['titulo_filho']);
        			
        			// Montando baixa dos titulos
        			// Monta sql invocando a fun��o para realizar a baixa dos t�tulos
        			$ret_baixa =  $this->dao->setBaixaContasReceber($titulos_filhos[$i]['titulo_filho'], $aux_campos, $cd_usuario);
        		
        			if(!$ret_baixa){
        				
        				$mErroProcessamento[] = 'Erro linha '.$linha.': N�o foi poss�vel baixar t�tulo';
        			
        			}else{
        			
	        			$valoresTitulosFilhos = $this->dao->getValoresTitulosFilhos($titulos_filhos[$i]['titulo_filho']);
	        			
	        			$valorTitulo     = $valoresTitulosFilhos[0]['titvl_titulo'];
	        			$titvl_pagamento = $valoresTitulosFilhos[0]['titvl_pagamento'];
	        			$juros_multa     = $valoresTitulosFilhos[0]['titvl_juros_multa'];
	        			$titvl_desconto  = $valoresTitulosFilhos[0]['titvl_desconto']; 
	        			
	        			//dados dos t�tulos filhos baixados
	                        $mTitulosBaixados[] = $titoid_filho.',,'.
	        					$nome_cliente.',,'.
	        					$numero_nota_fiscal.',,'.
	        					$titvl_pagamento.',,'.
	        					$data_ocorrencia.',,'.
	        					$titvl_desconto.',,'.
	        					$juros_multa.',,'.
	        					$titdt_vencimento.',,'.
	        					$valorTitulo ;
	        			$nTotal_Baixados += $titvl_titulo;
        			
        			}
        		
    			}else{
    				
                    $juros_multa = $titvl_juros + $titvl_multa;

                    $mTitulosJaBaixados[] = $titoid_filho.',,'.
                                $nome_cliente.',,'.
                                $numero_nota_fiscal.',,'.
                                $res[0]['valor_titulo_baixado'].',,'.
                                $data_credito.',,'.
                                $titvl_desconto.',,'.
                                $juros_multa.',,'.
                                $titdt_vencimento.',,'.
                                $titvl_titulo;

                    $nTotal_Ja_Baixados += $titvl_titulo;
				}
    		}//fim for	
    		
    	}//fim $ret_baixa_pai
    	

    	$ret_pro['status']                        = true;
    	$ret_pro['dados_titulos_baixados']        = $mTitulosBaixados;
    	$ret_pro['dados_titulos_ja_baixados']     = $mTitulosJaBaixados;
    	$ret_pro['vlr_total_titulos_baixados']    = $nTotal_Baixados;
    	$ret_pro['vlr_total_titulos_ja_baixados'] = $nTotal_Ja_Baixados;
    	
    	if(count($mErroProcessamento) > 0){
    	
    		for($i=0; $i < count($mErroProcessamento);$i++){
    			$msg_err_processamento  .= $mErroProcessamento[$i].'<br/>';
    		}
    	
    		$ret_pro['msg'] = $msg_err_processamento;
    	
    	}else{
    		$ret_pro['msg']    = '';
    	}
    	
    	return $ret_pro;
    }
    
    
    
    /**
     * Monta html para enviar por e-mail com os t�tulo que foram baixados
     * 
     * @param ARRAY $mTitulosBaixados
     * @param INT $nTotal_Baixados
     */
    public function montarHtmlTitulosBaixados($mTitulosBaixados, $nTotal_Baixados){
    	
    	
    	$msg_aux .= '<table width="100%" class="tableMoldura">';
    	$msg_aux .= '<tr><td align=left colspan=5>Titulos baixados como pago</td></tr>';
    	$msg_aux .= '<tr class="tableSubTitulo">';
    	$msg_aux .= '<td align=left><b>Cliente</b></td>';
    	$msg_aux .= '<td align=center><b>C�d. T�tulo</b></td>';
    	$msg_aux .= '<td align=center><b>NF</b></td>';
    	$msg_aux .= '<td align=right width=100><b>Valor Titulo</b></td>';
    	$msg_aux .= '<td align=center width=120><b>Data Pagamento</b></td>';
    	$msg_aux .= '<td align=center><b>Data Vencimento</b></td>';
    	$msg_aux .= '<td align=right><b>Juros+Multa</b></td>';
    	$msg_aux .= '<td align=right><b>Desconto</b></td>';
    	$msg_aux .= '<td align=right><b>Valor Pago</b></td>';
    	$msg_aux .= '</tr>';
    	
    	foreach($mTitulosBaixados as $cConteudo){
    		 
    		list($nId,
    				$cNome,
    				$cNF,
    				$valor_credito_titulo,
    				$cDataPagamento,
    				$valor_desconto_titulo,
    				$valor_juros_titulo,
    				$data_vencimento,
    				$valor_titulo) = explode(",,",$cConteudo);
    	
    		$msg_aux .= '<tr>';
    		$msg_aux .= "<td align=left>$cNome</td>";
    		$msg_aux .= "<td align=center>$nId</td>";
    		$msg_aux .= "<td align=center>$cNF</td>";
    		$msg_aux .= "<td align=right>".number_format($valor_titulo,2,',','.')."</td>";
    		$msg_aux .= "<td align=center>".$cDataPagamento."</td>";
    		$msg_aux .= "<td align=center>".$data_vencimento."</td>";
    		$msg_aux .= "<td align=right>".number_format($valor_juros_titulo,2,',','.')."</td>";
    		$msg_aux .= "<td align=right>".number_format($valor_desconto_titulo,2,',','.')."</td>";
    		$msg_aux .= "<td align=right>".number_format($valor_credito_titulo,2,',','.')."</td>";
    		$msg_aux .= '</tr>';
    		$mlista_titulo[]=$nId;
    	}
    	 
    	$msg_aux .= '<tr>';
    	$msg_aux .= "<td align=left colspan=3></td>";
    	$msg_aux .= "<td align=right><b>".number_format($nTotal_Baixados,2,',','.')."</b></td>";
    	$msg_aux .= "<td align=center></td>";
    	$msg_aux .= '</tr>';
    	
    	$msg_aux .= '</table>';
    	
    	$dados['msg_aux'] = $msg_aux;
    	$dados['mlista_titulo'] = $mlista_titulo;
    	
    	return $dados;
    	
    }
    
    
    /** Monta html  para enviar por e-mail com os t�tulos que n�o puderam ser baixados, pois j� constam com baixa no sistema
     * 
     * @param ARRAY $mTitulosJaBaixados
     * @param INT $nTotal_Ja_Baixados
     * @return string
     */
    public function montarHtmlTitulosComBaixa($mTitulosJaBaixados,$nTotal_Ja_Baixados){
    	
    	$msg_aux .= '<table width="100%" class="tableMoldura">';
    	$msg_aux .= '<tr><td align=left colspan=5>Os seguintes t�tulos n�o puderam ser baixados pois j� possuem data de baixa.</td></tr>';
    	$msg_aux .= '<tr class="tableSubTitulo">';
    	$msg_aux .= '<td align=left><b>Cliente</b></td>';
    	$msg_aux .= '<td align=center><b>C�d. T�tulo</b></td>';
    	$msg_aux .= '<td align=center><b>NF</b></td>';
    	$msg_aux .= '<td align=right width=100><b>Valor Titulo</b></td>';
    	$msg_aux .= '<td align=center width=120><b>Data Pagamento</b></td>';
    	$msg_aux .= '<td align=center><b>Data Vencimento</b></td>';
    	$msg_aux .= '<td align=right><b>Juros+Multa</b></td>';
    	$msg_aux .= '<td align=right><b>Desconto</b></td>';
    	$msg_aux .= '<td align=right><b>Valor Pago</b></td>';
    	$msg_aux .= '</tr>';
    	
    	foreach($mTitulosJaBaixados as $cConteudo){
    		list($nId,
    				$cNome,
    				$cNF,
    				$valor_credito_titulo,
    				$cDataPagamento,
    				$valor_desconto_titulo,
    				$valor_juros_titulo,
    				$data_vencimento,
    				$valor_titulo) = explode(",,",$cConteudo);
    		 
    		$msg_aux .= '<tr>';
    		$msg_aux .= "<td align=left>$cNome</td>";
    		$msg_aux .= "<td align=center>$nId</td>";
    		$msg_aux .= "<td align=center>$cNF</td>";
    		$msg_aux .= "<td align=right>".number_format($valor_titulo,2,',','.')."</td>";
    		$msg_aux .= "<td align=center>".$cDataPagamento."</td>";
    		$msg_aux .= "<td align=center>".$data_vencimento."</td>";
    		$msg_aux .= "<td align=right>".number_format($valor_juros_titulo,2,',','.')."</td>";
    		$msg_aux .= "<td align=right>".number_format($valor_desconto_titulo,2,',','.')."</td>";
    		$msg_aux .= "<td align=right>".number_format($valor_credito_titulo,2,',','.')."</td>";
    		$msg_aux .= '</tr>';
    	}
    	$msg_aux .= '<tr>';
    	$msg_aux .= "<td align=left colspan=3></td>";
    	$msg_aux .= "<td align=right><b>".number_format($nTotal_Ja_Baixados,2,',','.')."</b></td>";
    	$msg_aux .= "<td align=center></td>";
    	$msg_aux .= '</tr>';
    	
    	$msg_aux .= '</table>';
    	
    	
    	return $msg_aux;
    	
    }
    
    
    
    /**
     * 
     * 
     * @param unknown $msg
     * @param unknown $qtde_atualizado_cod
     * @param unknown $mErroProcessamento
     * @param unknown $mTitulosBaixados
     * @param unknown $nTotal_Baixados
     * @param unknown $mTitulosJaBaixados
     * @param unknown $nTotal_Ja_Baixados
     * @throws Exception
     * @return string|unknown
     */
    public function gerarArquivoCsv($msg, $qtde_atualizado_cod, $mErroProcessamento , $mTitulosBaixados, $nTotal_Baixados, $mTitulosJaBaixados, $nTotal_Ja_Baixados, $nome_arquivo){
    	
    	try {
    	
    		$nome_arquivo = explode(".",$nome_arquivo);
    		
    		$nomeArquivo =  $nome_arquivo[0] . "_" . date('dmy_Hi') . ".csv";
    		$planilhaRelatorio = "/var/www/docs_temporario/" . $nomeArquivo;
    		$handle	= fopen($planilhaRelatorio, "w");
    		 
    		if(!$handle){
    			throw new Exception('Erro ao gerar o arquivo csv.');
    		}
    		 
    		//Mensagem de Erro
    		$linha	= "";
    		$linha .= $msg . ";";
    		$linha .= "\r\n";
    		fwrite($handle, $linha);
    		 
    		//Registros com c�d de Retorno Atualizados
    		if($qtde_atualizado_cod > 0){
    			$linha	= "";
    			$linha .= 'Total de '.$qtde_atualizado_cod .' registros atualizados com c�digo de retorno'. ";";
    			$linha .= "\r\n";
    			fwrite($handle, $linha);
    		}
    		 
    		//Mensagens de Erro
    		if(count($mErroProcessamento) > 0){
    			foreach($mErroProcessamento as $cErro){
    				$linha	= "";
    				$linha .= $cErro . ";";
    				$linha .= "\r\n";
    				fwrite($handle, $linha);
    			}
    		}
    		 
    		//Titulo Baixados
    		if(count($mTitulosBaixados) > 0 ){
    			//Cabe�alho principal dos T�tulos Baixados
    			$linha	= "";
    			$linha .= '"Titulos baixados como pago.";';
    			$linha .= "\r\n";
    			fwrite($handle, $linha);
    			 
    			//Cabe�alho das colunas dos T�tulos Baixados
    			$linha	= "";
    			$linha .= '"Cliente";';
    			$linha .= '"C�d. T�tulo";';
    			$linha .= '"NF";';
    			$linha .= '"Valor Titulo";';
    			$linha .= '"Data Pagamento";';
    			$linha .= '"Data Vencimento";';
    			$linha .= '"Juros+Multa";';
    			$linha .= '"Desconto";';
    			$linha .= '"Valor Pago";';
    			$linha .= "\r\n";
    			fwrite($handle, $linha);
    			 
    			//Detalhe dos T�tulos Baixados
    			 
    			foreach($mTitulosBaixados as $cConteudo){
    				list($nId,
    						$cNome,
    						$cNF,
    						$valor_credito_titulo,
    						$cDataPagamento,
    						$valor_desconto_titulo,
    						$valor_juros_titulo,
    						$data_vencimento,
    						$valor_titulo) = explode(",,",$cConteudo);
    				 
    				$linha	= "";
    				$linha .= $cNome . ';';
    				$linha .= $nId . ';';
    				$linha .= $cNF . ';';
    				$linha .= number_format($valor_titulo,2,',','.') . ';';
    				$linha .= $cDataPagamento . ';';
    				$linha .= $data_vencimento . ';';
    				$linha .= number_format($valor_juros_titulo,2,',','.') . ';';
    				$linha .= number_format($valor_desconto_titulo,2,',','.') . ';';
    				$linha .= number_format($valor_credito_titulo,2,',','.') . ';';
    				$linha .= "\r\n";
    				fwrite($handle, $linha);
    				 
    			}
    			//Rodap� dos T�tulos Baixados
    			$linha	= "";
    			$linha .= ";;;";
    			$linha .= number_format($nTotal_Baixados,2,',','.');
    			$linha .= "\r\n\r\n";
    			fwrite($handle, $linha);
    			 
    		}//FIM: if(count($mTitulosBaixados) > 0 )
    		 
    		 
    		//Titulos j� baixados
    		if(count($mTitulosJaBaixados) > 0 ){
    			//Cabe�alho principal dos T�tulos J� Baixados
    			$linha	= "";
    			$linha .= '"Os seguintes t�tulos n�o puderam ser baixados pois j� possuem data de baixa.";';
    			$linha .= "\r\n";
    			fwrite($handle, $linha);
    			 
    			//Cabe�alho das colunas dos T�tulos J� Baixados
    			$linha	= "";
    			$linha .= '"Cliente";';
    			$linha .= '"C�d. T�tulo";';
    			$linha .= '"NF";';
    			$linha .= '"Valor Titulo";';
    			$linha .= '"Data Pagamento";';
    			$linha .= '"Data Vencimento";';
    			$linha .= '"Juros+Multa";';
    			$linha .= '"Desconto";';
    			$linha .= '"Valor Pago";';
    			$linha .= "\r\n";
    			fwrite($handle, $linha);
    			 
    			//Detalhe dos T�tulos J� Baixados
    			 
    			foreach($mTitulosJaBaixados as $cConteudo){
    				list($nId,
    						$cNome,
    						$cNF,
    						$valor_credito_titulo,
    						$cDataPagamento,
    						$valor_desconto_titulo,
    						$valor_juros_titulo,
    						$data_vencimento,
    						$valor_titulo) = explode(",,",$cConteudo);
    	
    				$linha	= "";
    				$linha .= $cNome . ';';
    				$linha .= $nId . ';';
    				$linha .= $cNF . ';';
    				$linha .= number_format($valor_titulo,2,',','.') . ';';
    				$linha .= $cDataPagamento . ';';
    				$linha .= $data_vencimento . ';';
    				$linha .= number_format($valor_juros_titulo,2,',','.') . ';';
    				$linha .= number_format($valor_desconto_titulo,2,',','.') . ';';
    				$linha .= number_format($valor_credito_titulo,2,',','.') . ';';
    				$linha .= "\r\n";
    				fwrite($handle, $linha);
    	
    			}
    			//Rodap� dos T�tulos J� Baixados
    			$linha	= "";
    			$linha .= ";;;";
    			$linha .= number_format($nTotal_Ja_Baixados,2,',','.');
    			$linha .= "\r\n";
    			fwrite($handle, $linha);
    			 
    		}//FIM: if(count($mTitulosJaBaixados) > 0 )
    	
    		fclose($handle);
    		
    		$dados['status'] = true;
    		$dados['arquivo_gerado'] = $planilhaRelatorio;
    		
    		return 	$dados;
    	
    	} catch (Exception $e) {
    		
    		fclose($handle);
    		unlink($planilhaRelatorio);
    		
    		$dados['status'] = false;
    		$dados['msg_erro'] = $e->getMessage();
    		
    		return $dados;
    	}
    	
    }
    
    
    /**
     * Envia e-mail previamente par�metrizado no BD com o resultado da importa��o do arquivo
     * 
     * @param string $msg
     * @param boolean $status
     * @param object $dadosProcesso
     * @param string $tipo
     * @throws exception
     */
    
    private function enviarEmail($msg, $status, $dadosProcesso, $tipo, $anexo = NULL){
    	
    	$dadosEmail = Array();
    	
    	//inst�ncia de classe de configura��es de servidores para envio de email
    	//$servicoEnvioEmail = new ServicoEnvioEmail();
    	
    	//recupera os dados do usu�rio que iniciou o processo
      	$emailUsuarioProcesso = $this->dao->getDadosUsuarioProcesso($dadosProcesso->id_usuario);

      	if(is_array($emailUsuarioProcesso)){
      		
      		$nomeUsuarioProcesso = $emailUsuarioProcesso[0]['nm_usuario'];
      		
      		//verifica se o us�rio possui email cadastrado
      		if(empty($emailUsuarioProcesso[0]['usuemail'])){
      			
      			$msg_erro_email = 'Falha ao enviar e-mail : Usu�rio [ '.$this->dao->usuarioID.' ] que iniciou o processo, n�o possui e-mail cadastrado.';
      			
      			//finaliza processo com sucesso mas com mensagem de erro de envio de email
      			$finalizarProcesso = $this->dao->finalizarProcesso(true, $msg_erro_email, $dadosProcesso->earoid);
      			
      			return true;
      		
      		}else{
      			
      			$assunto = 'Cobran�a Registrada - Retorno';
      			
      			if($status){
      				
      				$corpo_email = 'Sr(a). '.$emailUsuarioProcesso[0]['nm_usuario'].' o processamento do arquivo de retorno foi conclu�do, segue anexo o relat�rio de processamento. <br/><br/>';
      				$corpo_email .= $msg;
      				
      			}else{
      				
      				if($tipo == 'pararExecucao'){
      					
      					$assunto = 'Cobran�a Registrada - Retorno Relat�rio Processamento';
      					
      					$corpo_email = " Prezado usu�rio o processamento do arquivo de retorno do banco que voc� iniciou �s  $dadosProcesso->inicio_hora do dia  $dadosProcesso->inicio_data ,
    					                 foi parado pelo usu�rio  [ $nomeUsuarioProcesso ] e o arquivo foi disponibilizado para processamento novamente. ";
      				
      				}elseif($tipo == 'erro'){
      					
      					$corpo_email = 'Erro no processamento : '.$msg ;
      					
      				}
      				
      			}
      			
      			
      			//recupera e-mail de testes
      			if($_SESSION['servidor_teste'] == 1){
      			
      				//recupera email de testes da tabela parametros_configuracoes_sistemas_itens
      				$emailTeste = $this->dao->getEmailTeste();
      			
      				$emailUsuarioProcesso[0]['usuemail'] = $emailTeste->pcsidescricao;
      				
      				if(!is_object($emailTeste)){
      					throw new exception('E necessario informar um e-mail de teste em ambiente de testes.');
      				}
      			}
      			
      			
      			$mail = new PHPMailer();
      			$mail->isSMTP();
      			$mail->From = "sascar@sascar.com.br";
      			$mail->FromName = "sistema@sascar.com.br";
      			$mail->Subject = $assunto;
      			$mail->MsgHTML($corpo_email);
      			$mail->ClearAllRecipients();
      			$mail->AddAddress($emailUsuarioProcesso[0]['usuemail']);
      			$mail->AddAttachment($anexo);

      			if(!$mail->Send()) {
      				 
      				$msg_erro_email = $dadosProcesso->msg.' - Falha ao enviar e-mail -';

      				//atualiza o processo com mensagem de erro de envio de email
     				$this->dao->finalizarProcesso(true, $msg_erro_email, $dadosProcesso->earoid);
     					 
      			}
      				 
      			
      			//envia o email
//       			$envio_email = $servicoEnvioEmail->enviarEmail(
//       					$emailUsuarioProcesso[0]['usuemail'],
//       					$assunto,
//       					$corpo_email,
//       					$arquivo_anexo = $anexo,
//       					$email_copia = null,
//       					$email_copia_oculta = null,
//       					1,//sascar
//       					$emailTeste->pcsidescricao//$email_desenvolvedor = null
//       			);
      			
//       			if(!empty($envio_email['erro'])){
      			
//       				$msg_erro_email = 'Falha ao enviar e-mail : '.$envio_email['msg'].' ';
      			
//       				//finaliza processo com sucesso mas com mensagem de erro de envio de email
//       				$finalizarProcesso = $this->dao->finalizarProcesso(true, $msg_erro_email, $dadosProcesso->earoid);
      			
//       			}
      			
      			return true;
      			
      		}
      	
      	}
    	
    	return false;
    }
	
}