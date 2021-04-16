<?php
/**
 * @file CadContratoServicos.class.php
 * @author Rafael Mitsuo Moriya <rafaelmoriya@brq.com>
 * @version 12/09/2013
 * @since 12/09/2013
 * @package SASCAR CadContratoServicos.class.php  
 */
require_once(_MODULEDIR_."Cadastro/DAO/CadContratoServicosDAO.class.php");

/**
 * Action do Cadastro de codigo de venda da cargo track
 */
class CadContratoServicos {
	
	private $dao;
	
	public function __construct() {		

		$this->dao = new CadContratoServicosDAO();		
	}
    
    public function __set($var, $value) {
        $this->$var = $value;
    }
    
    public function __get($var) {
        return $this->$var;
    }	

	public function index($acao = 'index', $resultadoPesquisa = array(), $mensagem = '') {
				
		// exibe mensagem quando existir
		if($mensagem != '')
			$this->retorno = $mensagem;
		
		// Se cadastro ocorreu com sucesso, limpa o Post para n�o preencher automaticamente as combos
		if ($mensagem['status'] == "sucesso") {
			$this->limpaPost();
		}
						
		// passa variaveis para a view
		if($acao == "pesquisar"){
			$this->acao = $acao;
		}
		$this->resultadoPesquisa = $resultadoPesquisa;
		
		// chama a view
		$this->view($acao);
	}
	
	public function integracao($funcao, $params = null, $tiporetorno = null) {
	    
	    if(isset($_POST['params'])){
	        //RECUPERA DADOS ENVIADOS
	        $params      = $_POST['params'];
	        $tiporetorno = $_POST['tiporetorno'];
	    } 
	    
	    if($funcao == 'integracao'){
	        $funcao = $_POST['funcao'];
	    }
	    	    
	    $retorno = $this->$funcao($params); // retornar array
	    
	    // se for tiporetorno = json faz conversao
	    if($tiporetorno == 'json'){
	        echo json_encode($retorno);
	        exit();
	    }
	    
	    return $retorno;
	    
	}

	/**
	 * Manda sinal para o WS para ativar o produto
	 */
	public function chamaAtivacao($params){		
	    //$serial = null,$placa = null,$rf = null,$local = null,$cpf = null,$consoid = null,$connumero = null
		
		if(empty($params['consoid'])){
		    $params['consoid'] = '';
		}		
		if(empty($params['connumero'])){
		    $params['connumero'] = '';
		}
		
		//get company_id
		$company_id = $this->dao->getCompanyId($params['connumero'],$params['consoid']);
		
		// Verifica se status for aguardando ativa��o ou ativado ent�o n�o entra
		if($this->dao->getStatusAtivacao($params['serial']) == 'N'){
			// Verifica o CPF
			$retorno = $this->autenticarInstalador($params['cpf']);
			
			// Seta api-key
			$api_key = $retorno->retorno->api_key;

			// Entra aqui caso seja autenticado
			if($retorno->codigo == '0001'){				
				
				// Chama WS que vericica se o equipamento est� dispon�vel para ativa��o
				$retorno = $this->disponivel($api_key,$params['serial']);

				// Entra aqui caso esteja pronto para ser ativado
				if($retorno['status'] == 'sucesso'){
					
					// Verifica se o equipamento j� est� em processo de ativa��o em outro contrato
					if($this->dao->verificaExisteProcessoAtivacao($params['serial'],$params['consoid']) == false){
						
						// Chama WS que vericica se ativa o equipamento
						$retorno = $this->ativar($api_key,$params['serial'],$params['placa'],$params['rf'],$params['local'],$company_id);
						
						if($retorno->codigo == '0005'){
							
							// Grava o status da ativa��o
							$retorno = $this->dao->gravaStatusAtivacao($params['serial'],$params['consoid'],$params['rf'],"R",$api_key,$params['connumero'], $retorno->protocolo, $params['usuario']);
	
							if($retorno == 'true' || $retorno == 1){
								$array['status'] 	= "Sucesso";
								$array['mensagem'] 	= "Aguardando Retorno";
							}else{
								$array['status'] 	= "Error";
								$array['mensagem'] 	= utf8_encode("Erro ao gravar status do equipamento");;
								$array['codstatus'] = 1805;// Erro ao gravar status do equipamento
							}
							
							
						}else{
							
							// Grava o status da ativa��o
							$this->dao->gravaStatusAtivacao($params['serial'],$params['consoid'],$params['rf'],"N",$api_key,$params['connumero'],'', $params['usuario']);
	
							$array['status'] 	= "Error";
							$array['mensagem'] 	= utf8_encode("N&atilde;o foi poss�vel ativar o equipamento");
							$array['codstatus'] = 1804;// N�o foi poss�vel ativar o equipamento
							
						}
						
					}else{
						$array['status'] 	= "Error";
						$array['mensagem'] 	= utf8_encode("Este equipamento j&aacute; est&aacute; em processo de ativa&ccedil;&atilde;o em outro contrato");
						$array['codstatus'] = 1803;// Este equipamento j� est� em processo de ativa��o em outro contrato
					}
					
				}elseif($retorno['status'] != 'sucesso' && !empty($retorno['mensagem'])){
					$array['status'] 	= "Error";
					$array['mensagem'] 	= utf8_encode($retorno['mensagem']);
					$array['codstatus'] = 1806; //Este equipamento ja enviou #numposicoes# posicoes. O limite e #maxposicoes#
					$array['numposicoes'] = $retorno['numposicoes'];
					$array['maxposicoes'] = $retorno['maxposicoes'];

				}else{
					
					// Grava o status da ativa��o
					$this->dao->gravaStatusAtivacao($params['serial'],$params['consoid'],$params['rf'],"N",$api_key,$params['connumero'],'', $params['usuario']);
					
					$array['status'] 	= "Error";
					$array['mensagem'] 	= utf8_encode("Equipamento n&atilde;o est&aacute; dispon&iacute;vel para ativa&ccedil;&atilde;o");
					$array['codstatus'] = 1802;// Equipamento n�o est� dispon�vel para ativa��o
										
				}
				
			}else{
				// Grava o status da ativa��o
				$this->dao->gravaStatusAtivacao($params['serial'],$params['consoid'],$params['rf'],"N",$api_key,$params['connumero'],'',$params['usuario']);

				$array['status'] 	= "Error";
				$array['mensagem'] 	= utf8_encode("Instalador n&atilde;o autorizado para instalar o equipamento");
				$array['codstatus'] = 1801;// Instalador n�o autorizado para instalar o equipamento
				
			}
		}else{
			$array['status'] 	= "Sucesso";
			$array['mensagem'] 	= "Aguardando retorno";
		}
		
		return $array;
		
	}
	
	/**
	 * Pega os dados do contrato, veiculo e equipamento para chamar a ativa��o
	 */
	public function chamaAtivacaoEquipamentoPrincipal($params){	    
	    //$serial = null, $connumero = null, $instalador = null, $rf = null	       
	    
	    $params['placa']  = $this->dao->getPlaca($params['connumero']);	
	    $params['cpf']    = $this->dao->getCpfInstalador($params['itloid']);    	    
	    $params['local']  = $this->dao->getLocalInstalacao($params['connumero']);	    
	    $params['rfBase'] = $this->dao->getRfEquipamento($params['serial']);
	    
	    // caso o RF esteja importado na base o mant�m
	    if(!empty($params['rfBase'])){
	        $params['rf'] = $params['rfBase'];
	    }	    
	    
	    if(!empty($params['placa']) && !empty($params['cpf']) && !empty($params['local']) && !empty($params['rf'])){
	        // $serial,$placa,$rf,$local,$cpf,null,$connumero,false	        
	        $array = $this->chamaAtivacao($params);	
	    }else{
	    	$error_msg = "Faltam dados no contrato para esta ativacao. ";

			if(empty($params['cpf'])){
				$error_msg .= 'CPF do instalador n&atilde;o encontrado. ';
			}
			if(empty($params['local'])){
				$error_msg .= 'Local de Instala��o n&atilde;o encontrado. ';
			}

	    	if(empty($params['rf'])) {
	    		$error_msg .= 'Radio Frequencia do equipamento n&atilde;o encontrado. ';
	    	}

	        $array['status'] 	= "Error";
	        $array['mensagem'] 	= $error_msg;
	    }
	    
	    return $array;
	}
		
    /**
	 * Verifica se existe solicita��o, o status � R e faz mais de X minutos
	 */
	public function getStatusAtivacao($serial){
		
	    return $this->dao->getStatusAtivacao($serial);
		
	}
	
	/**
	 * Manda sinal para o WS para desativar o produto
	 */
	public function chamaDesativacao($params){
		//$serial = null,$cpf = null,$consoid = null, $retorno_json = true
			    
	    if(!isset($params['consoid'])){
	        $params['consoid'] = '';
	    }
		
		// Autentica o instalador
		$retorno = $this->autenticarInstalador($params['cpf']);
			
		// Entra aqui caso seja autenticado
		if($retorno->codigo == '0001'){

			// Seta api-key
			$api_key = $retorno->retorno->api_key;

			if($retorno->codigo != '0001'){
			    $array['status'] 	= "Error";
			    $array['mensagem'] 	= $retorno->mensagem;
			    
			    return $array;
			}
			
			// Chama ws para ativa��o do equipamento
			$retorno = $this->desativar($api_key,$params['serial']);
			
			if($retorno->codigo == '0015'){
			
				// Grava Status da desativa��o
				$this->dao->gravaStatusDesativacao($params['serial'],$params['consoid'],$params['connumero'],$params['usuario']);
			
				$array['status'] 	= "Sucesso";
				$array['mensagem'] 	= "Aguardando Retorno";
			
			}else{
			    $mensagem = json_decode($retorno->error);
				$array['status'] 	= "Error";
				$array['mensagem'] 	= $mensagem->mensagem;
			}
			
		}else{
			$array['status'] 	= "Error";
			$array['mensagem'] 	= $retorno->mensagem;
		}
		
		return $array;
	}
	
	/**
	 * Pega os dados do contrato, veiculo e equipamento para chamar a ativa��o
	 */
	public function chamaDesativacaoEquipamentoPrincipal($params){
	    //$serial = null, $connumero = null, $instalador = null, $usuario
	    
	    $params['cpf'] = $this->dao->getCpfInstalador($params['itloid']);    	    
	    
	    if(!empty($params['cpf'])){
	        $array = $this->chamaDesativacao($params);	
	    }else{
	        $array['status'] 	= "Error";
	        $array['mensagem'] 	= "Cadastro do instalador n&atilde;o foi localizado para a desativacao.";	        
	    } 
	    
	    return $array;
	}
	
	/**
	 * Manda sinal para o WS para desativar o produto
	 */
	public function desativar($api_key,$serial){
		
		$ccid = $this->dao->getCcid($serial);
		
		if($ccid == 0){
			$array['status'] 	= "Error";
			$array['mensagem'] 	= "CCID n&atilde;o encontrado";
			 
			echo json_encode($array);
			exit();
		}
		
		$params['api_key'] 	= $api_key;
		$params['ccid'] 	= $ccid;
		
		$resultado 	= $this->wsRest("POST",_URLDESATIVACAO_,$params);

		return $resultado;
	}
	
	/**
	 * Manda sinal para o WS para verificar a posi��o
	 */
	public function posicao($api_key,$serial,$method = "GET"){

		$ccid = $this->dao->getCcid($serial);
		
		if($ccid == 0){
			$array['status'] 	= "Error";
			$array['mensagem'] 	= "CCID n�o encontrado";
		
			echo json_encode($array);
			exit();
		}
		
		$params['ccid'] 	= $ccid;
		$params['api_key'] 	= $api_key;
		
		$resultado 	= $this->wsRest($method,_URLPOSICAO_,$params);

		return $resultado;
	}
	
	/**
	 * Manda sinal para o WS para ativar o produto
	 */
	public function ativar($api_key,$serial,$placa,$rf,$local,$company_id){
		
		$ccid = $this->dao->getCcid($serial);
		
		if($ccid == 0){
			$array['status'] 	= "Error";
			$array['mensagem'] 	= "CCID n&atilde;o encontrado";
		
			echo json_encode($array);
			exit();
		}
		
		$params['api_key'] 	= $api_key;
		$params['ccid'] 	= $ccid;
		$params['placa'] 	= $placa;
		$params['rf'] 		= $rf;
		$params['cod_local']= $local;
		$params['company_id'] = empty($company_id['company_id']) ? 396 : $company_id['company_id'];
		$params['success_callback_url'] = _URLCALLBACK_.'?acao=retornoAtivacao&status=sucesso&ccid='.$params['ccid'].'&apikey='.$api_key.''; 
		$params['error_callback_url'] = _URLCALLBACK_.'?acao=retornoAtivacao&status=falha&ccid='.$params['ccid'].'&apikey='.$api_key.'&motivo=_MOTIVO_'; 
		$params['seguradora_id'] = $company_id['conno_tipo']; // id tipo contrato
		$params['seguradora_descricao'] = utf8_encode($company_id['tpcdescricao']); // descricao tipo contrato
						
		$resultado 	= $this->wsRest("POST",_URLATIVACAO_,$params);

		return $resultado;
	}
	
	/**
	 * Manda sinal para o WS para alterar placa vinculada ao equipamento
	 */
	public function substituir($serial,$placa_atual,$placa_nova){
	
		$ccid = $this->dao->getCcid($serial);
		
		if($ccid == 0){
			$array['status'] 	= "Error";
			$array['mensagem'] 	= "CCID n&atilde;o encontrado - Equipamento ($serial) sem linha vinculada";
			echo json_encode($array);
			exit();
		}
		
	    // Autentica com o CPF padr�o
	    $autentica = $this->autenticarInstalador(_CPFDESATIVACAO_);
	    
	    if($autentica->codigo == '0001'){
	    
	        $api_key = $autentica->retorno->api_key;
	        
	        if($autentica->codigo != '0001'){
	            $array['status'] 	= "Error";
	            $array['mensagem'] 	= $retorno->mensagem;
	        
	            echo json_encode($array);
	            exit();
	        }
	    	        
    		$params['api_key'] 	    = $api_key;
    		$params['ccid'] 	    = $ccid;
    		$params['placa_atual'] 	= $placa_atual;
    		$params['placa_nova'] 	= $placa_nova;
    		
		    $resultado = $this->wsRest("POST",_URLSUBSTITUIR_,$params);
	        
	    }else{
	        $resultado = 'erro'.$autentica->codigo.' // '.$autentica->mensagem;
	    }
	    
		return $resultado;
	}
	
	/**
	 * Verifica disponibilidade
	 * @param string $token
	 * @param int $serial
	 * @return array $array
	 */
	public function disponivel($api_key,$serial){
		
		$ccid = $this->dao->getCcid($serial);
		
		if($ccid == 0){
			$array['status'] 	= "Error";
			$array['mensagem'] 	= "CCID n&atilde;o encontrado";
		
			echo json_encode($array);
			exit();
		}
	
		$params['api_key'] 	= $api_key;
		$params['ccid'] 	= $ccid;
	
		$resultado 	= $this->wsRest("GET",_URLDISPONIBILIDADE_,$params);
		
		if($resultado->codigo == '0003'){
			$nrPosicoes = $this->dao->getNumeroPosicoesSemDescarregarBateria($params['ccid']);

			//TODO : fazer valida��o quando ws retornar numero de posicoes
			if($resultado->posicoes > $nrPosicoes){
				$array['status'] 	= "error";
				$array['mensagem'] 	= "Este equipamento ja enviou " . $resultado->posicoes . " posicoes. O limite e " . $nrPosicoes . ".";
				$array['numposicoes'] = $resultado->posicoes;
				$array['maxposicoes'] = $nrPosicoes;
			}else{
				$array['status'] 	= "sucesso";
			}
		}
		
		return $array;
	}

    public function dd($txt) {
        $log = "/var/www/log/log_".date('Y-m-d').".txt";
        $fp = fopen($log, "a+");
        $txt = json_encode([
            'date' => date('Y-m-d H:i:s'),
            'message' => $txt
        ]);
        $txt = $txt."\n";
        fwrite($fp, $txt);
        fclose($fp);
    }

	/**
	 * Ws para autenticar instalador por CPF
	 * @return Array $array
	 */
	public function autenticarInstalador($cpf){
	    
	    $cpf = $this->getCPF(preg_replace("/[^0-9]/","",$cpf)); // mant�m somente numeros da string e completa com zeros a esquerda
		$params['cpf']	= $this->mascaraString('###.###.###-##',$cpf); // adiciona mascara de formata��o CPF

        $this->dd(_URLAUTENTICACAO_);
        $this->dd($params);

		$resultado 		= $this->wsRest("POST",_URLAUTENTICACAO_,$params);
		return $resultado;
	}
	
	/**
	 * Ws para autenticar instalador por CPF
	 * @return Array $array
	 */
	public function retornoAtivacao(){

	    if(isset($_GET['ccid'])){
	        $ccid = $_GET['ccid'];
	    }
		if(isset($_GET['apikey'])){
			$api_key = $_GET['apikey'];
		}
		if(isset($_GET['status'])){
		    if($_GET['status'] == 'falha'){
			    $status = 'N'; // n�o ativado
			    $motivo = $_GET['motivo'];
		    }else{
		        $status = 'A'; // ativado
			    $motivo = null;
		    }
		}
		
		$resultado = $this->dao->atualizaStatusAtivacao($ccid,$status,$api_key,$motivo);
		
		return $resultado;
	}
	
	/**
	 * Pega parametros para ativa��o atrav�s do serial
	 * @param int $serial
	 * @return Array $array
	 */
	public function getDadosAtivacao($serial = null, $connumero){
	
		if(isset($_POST['serial'])){
			// Pega os parametros via post
			$serial = $_POST['serial'];
		}
	
		return $this->dao->getAtributosAtivacao($serial, $connumero);
	}
	
	/**
	 * Pega parametros para ativa��o atrav�s do serial
	 * @param int $serial
	 * @return Array $array
	 */
	public function getRF($serial = null){
	
		if(isset($_POST['serial'])){
			// Pega os parametros via post
			$serial = $_POST['serial'];
		}
	
		return $this->dao->getRF($serial);
	}
	
	/**
	 * Valida se equipamento � CargoTracck
	 * @param int $serial
	 * @return boolean
	 */
	public function validaEquipamentoCT($serial = null){
	
		if(isset($_POST['serial'])){
			// Pega os parametros via post
			$serial = $_POST['serial'];
		}
	
		return $this->dao->validaEquipamentoCT($serial);
	}
	
	/**
	 * Valida se contrato servi�o � CargoTracck
	 * @param int $serial
	 * @return boolean
	 */
	public function validaContratoServicoCT($consoid){
		
		return $this->dao->validaContratoServicoCT($consoid);
	}
	
	/**
	 * Funcao para webservice REST
	 * @param string $method
	 * @param string $url
	 * @param string $data
	 */
	private function wsRest($method,$url, $data = false) {
	    
		switch($method)
		{
			case 'GET':
				if ($data)
					$url = sprintf("%s?%s", $url, http_build_query($data));
					$curl = curl_init($url);
				break;
			case 'POST':
				$curl = curl_init($url);
				curl_setopt($curl, CURLOPT_POST, true);
				if ($data)
					curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				break;
			case 'PUT':
				$curl = curl_init($url);
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
				if ($data)
					curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				break;
		}
		
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		
		if($_SERVER['HTTP_HOST'] == '192.168.56.101' && _URLPROXY_ && _USERPROXY_){

		    /* Utilizado Proxy no ambiente BRQ de desenvolvimento*/
		    curl_setopt($curl, CURLOPT_PROXY, _URLPROXY_);
		    curl_setopt($curl, CURLOPT_PROXYUSERPWD, _USERPROXY_);
		    curl_setopt($curl, CURLOPT_PROXYPORT, 3128);
		    /**/	    
		    
		}		
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:'));
		
		
		$curl_response = curl_exec($curl);
		
		if ($curl_response === false) {
		    $info = curl_getinfo($curl);
		    curl_close($curl);
		    return false;
		}

		curl_close($curl);
		$decoded = json_decode($curl_response);
		
		$this->dao->gravaLOG($method,$url,implode(",", $data),$curl_response);
		
		return $decoded;
	}
	
	/**
	 * Popula valores post
	 * @param boolean $clearPost
	 * @param array $params
	 * @return $array
	 */
	public function populaValoresPost($clearPost = false, $params = null) {	
		if(!is_null($params)):
			$data = $params;
		else:
			$data = $_POST;
		endif;
		
		foreach($data as $key => $value):
			if($clearPost === false) {
				// TODO: alterar strtoupper para mb_strtoupper assim que estiver habilitado o mb_string
				$this->$key = (is_string($value))?strtoupper($value):$value;				
			} else
				unset($this->$key);
		endforeach;		
		
		return $data;		
	}	


	public function mascaraString($mascara,$string) {
	    $string = str_replace(" ","",$string);
	    for($i=0;$i<strlen($string);$i++) {
	        $mascara[strpos($mascara,"#")] = $string[$i];
	    }
	    return $mascara;
	}
	
    public function getCPF($cpf) {
        return str_pad($cpf, 11, 0, STR_PAD_LEFT);
    }
	
	public function view($action, $layoutCompleto = true) {	
		if($layoutCompleto)
			include _MODULEDIR_.'Cadastro/View/cad_codigo_vendaCT/header.php';
		
		include _MODULEDIR_.'Cadastro/View/cad_codigo_vendaCT/'.$action.'.php';
		
		if($layoutCompleto)
			include _MODULEDIR_.'Cadastro/View/cad_codigo_vendaCT/footer.php';
	}


	/**
	* M�todo para validar status da linha
	**/
	public function validaStatusLinha($equno_serie)
	{

		$result = $this->dao->validaStatusLinha($equno_serie);

		$mensagens = array(
            'habilitarLinha' => "A linha vinculada a esse equipamento encontra-se com o status %s. \n\nVoc� n�o poder� vincular esse equipamento a outro contrato enquanto a linha n�o estiver habilitada.",
        );

		$regras = array(
			//Para os status abaixo, trava a migra��o e mostra alerta solicitando abertura de ASM para reativa��o da linha
            2  => array ('trava' => true, 'alerta' => $mensagens['habilitarLinha']), //Suspensa
            22 => array ('trava' => true, 'alerta' => $mensagens['habilitarLinha']), //Aguardando verifica��o
            25 => array ('trava' => true, 'alerta' => $mensagens['habilitarLinha']), //Aguardando suspens�o
            26 => array ('trava' => true, 'alerta' => $mensagens['habilitarLinha']), //Stand by
            0  => array ('trava' => true, 'alerta' => $mensagens['habilitarLinha']), //SEM LINHA
            3  => array ('trava' => true, 'alerta' => $mensagens['habilitarLinha']), //Cancelada
            5  => array ('trava' => true, 'alerta' => $mensagens['habilitarLinha']), //Aguardando cancelamento
            12 => array ('trava' => true, 'alerta' => $mensagens['habilitarLinha']), //Dispon�vel para troca de chip
            21 => array ('trava' => true, 'alerta' => $mensagens['habilitarLinha']), //Aguardando troca de chip
		);

		if ($regras[ $result->lincsloid ]){
			$regra = $regras[ $result->lincsloid ];
			$regra['alerta'] = utf8_encode(sprintf($regra['alerta'], strtoupper($result->cslstatus)));
			return $regra;
		
		}else{
			return '';
		}
	}

    /**
     * Valida posi��o selecionada
     * @return bool
     */
    public function validaRegistroPosicaoAcessorioCarreta($consconoid, $consobroid, $constcpaoid, $consoid){

        return $this->dao->validaRegistroPosicaoAcessorioCarreta($consconoid, $consobroid, $constcpaoid, $consoid);
    }

}