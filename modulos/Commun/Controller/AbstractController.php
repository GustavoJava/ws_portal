<?php

namespace modulos\Commun\Controller;

use modulos\Commun\VO\AutorAcaoVO;

require_once _SITEDIR_ . '/lib/gerar_logs/Logger.php';
require_once _MODULEDIR_ . '/Commun/VO/AutorAcaoVO.php';

/**
 * 
 * @author alexandre.reczcki
 *
 */
abstract class AbstractController {
	
	/**
	 * Armazena informa��es do Post
	 * @var $_POST
	 */
	public $params;
	
	/** @var String Armazenar Mensagem de sucesso das a��es */
	public $mensagemSucesso;
	
	/** @var String Armazenar Mensagem de erros das a��es */
	public $mensagemErro;
	
 	/** @var AutorAcaoVO */
 	public $autorAcao;
 	
 	public $arrayLog = array();
	
	public function __construct() {
	    
	    $this->configurarUsuarioVO();
	    
		$this->params = $this->populaValoresPost();
		$this->mensagemSucesso = NULL;
		$this->mensagemErro = NULL;
	}
	
	/**
	 * Manter valores no formul�rio ap�s submit
	 * 
	 * @param string $clearPost
	 * @param string $params
	 * 
	 * @return array $data
	 */
	protected function populaValoresPost($clearPost = false, $params = null) {
		if(!is_null($params)):
			$data = $params;
		else:
			$data = $_POST;
		endif;
		foreach($data as $key => $value):
			if($clearPost === false) {
				$this->$key = (is_string($value))?strtoupper($value):$value;
			} else
				unset($this->$key);
			endforeach;
		return $data;
	}

	/**
	 * 
	 * Para manter os valores ap�s a busca
	 * 
	 * @param string $action
	 * @param boolean $layoutCompleto
	 */
	protected function view($action='index', $layoutCompleto = true){
		if($action == 'index'){
		}
		if($layoutCompleto){
			include _MODULEDIR_.'Commun/view/header.php';
		}
		//include _MODULEDIR_.'Login/view/cadastro/'.$action.'.php';
		include _MODULEDIR_. $action.'.php';
		if($layoutCompleto){
			include _MODULEDIR_.'Commun/view/footer.php';
		}
	}
	
	abstract protected function verificarPermissaoDeAcesso();
	
	protected  function verificarAcessoPagina(){
	    if ( !in_array($this->autorAcao->departamento, $this->idDepartamentosAcessoPagina) ){
	        return header('Location: acesso_invalido.php');
	    }
	    return true;
	}
	
	protected function configurarUsuarioVO(){
	    $this->autorAcao = new AutorAcaoVO();
	    $this->autorAcao->id                  	= $_SESSION['usuario']['oid'];
	    $this->autorAcao->departamento        	= $_SESSION['usuario']['depoid'];
	    $this->autorAcao->nome 					= $_SESSION['usuario']['nome'];
		$this->autorAcao->login 				= $_SESSION['usuario']['login'];
	    $this->autorAcao->sistema             	= 'modulos';
	    $this->autorAcao->classeConsumidora   	= __CLASS__;
	    //$this->autorAcao->senha 				= $_SESSION['usuario']['senha'];
	    $this->autorAcao->token               	= md5($_SESSION['usuario']['depoid'] . $_SESSION['usuario']['depoid']);
	}
	
}