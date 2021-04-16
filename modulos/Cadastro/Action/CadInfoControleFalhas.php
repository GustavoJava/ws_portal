<?php

/**
 * @author Gustavo H Mascarenhas Machado <gustavo.machado@meta.com.br>
 */

/**
 * Arquivo DAO respons�vel pelas requisi��es ao banco de dados
 */
require _MODULEDIR_ . 'Cadastro/DAO/CadInfoControleFalhasDAO.php';

class CadInfoControleFalhas
{
	protected $_dao;
	protected $_viewPath;
	
	public function __construct()
	{	
		$this->_dao = new CadInfoControleFalhasDAO();
		$this->_viewPath = _MODULEDIR_ . 'Cadastro/View/cad_info_controle_falhas/';
	}
	
	/**
	 *  Recupera par�metro recebido via POST
	 * @param	string		$param
	 * @return	string
	 */
	protected function _getPostParam($param)
	{
		return $this->_getParamFromRequest($param, $_POST);
	}
	
	/**
	 *  Recupera par�metro recebido via GET
	 * @param	string		$param
	 * @return	string
	 */
	protected function _getGetParam($param)
	{
		return $this->_getParamFromRequest($param, $_GET);
	}
	
	/**
	 *  Recupera par�metro recebido na requisi��o ($_REQUEST)
	 * @param	string		$param
	 * @return	string
	 */
	protected function _getRequestParam($param)
	{
		return $this->_getParamFromRequest($param, $_REQUEST);
	}
	
	/**
	 *  Recupera um par�metro recebido em determinado tipo de requisi��o
	 * @param	string		$param
	 * @param	string		$param	Requisi��o: $_POST, $_GET, $_REQUEST
	 * @return	string
	 */
	protected function _getParamFromRequest($param, $requestType)
	{
		return isset($requestType[$param]) ? $requestType[$param] : '';
	}
	
	/**
	 * Verifica se par�metro existe na requisi��o
	 * @param	string	$param
	 * @return	boolean
	 */
	protected function _hasParam($param)
	{
		$value = $this->_getRequestParam($param);
		return (bool) (strlen($value));
	}
	
	/**
	 * Verifica se uma requisi��o foi efetuada via POST
	 * @return	boolean
	 */
	protected function _isPost()
	{
		return $_SERVER['REQUEST_METHOD'] === 'POST';
	}
	
	/**
	 * Verifica se uma requisi��o foi efetuada via GET
	 * @return	boolean
	 */
	protected function _isGet()
	{
		return $_SERVER['REQUEST_METHOD'] === 'GET';
	}
	
	/**
	 * Verifica se uma requisi��o foi efetuada via AJAX
	 * @return	boolean
	 */
	protected function _isAjax()
	{
		return ($_SERVER['HTTP_X_REQUESTED_WITH']
				&& strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
	}
	
	/**
	 * Redireciona para uma p�gina do sistema
	 * @param	string	$target
	 * @return	void
	 */
	protected function _redirect($target)
	{
		// Recupera o protocolo utilizado (HTTP ou HTTPS)
		$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off")
						? "https://" : "http://";
		
		// Recupera o endere�o do servidor (IP ou URI)
		$server = $_SERVER['HTTP_HOST'] . '/';
		
		// Gambi para requisi��o local
		if (preg_match('/sistemaWeb/', $_SERVER['REQUEST_URI']))
		{
			$server .= 'sistemaWeb/';
		}
		
		$location = $protocol . $server . $target;
		
		header("Location: {$location}");
	}
	
	/**
	 * Guarda ou recupera uma flash message da sess�o
	 * @param	array	$message
	 * @return 	string|void
	 */
	public function flashMessage($message = null)
	{
		if ($message)
		{
			$_SESSION['flash_message'] = $message;			
		}
		else
		{
			$message = $_SESSION['flash_message'];
			unset($_SESSION['flash_message']);
			
			return $message;
		}
	}
	
	/**
	 * Verifica se h� uma flash message guardada na sess�o
	 * @return	boolean
	 */
	public function hasFlashMessage()
	{
		return (isset($_SESSION['flash_message'])
					&& strlen($_SESSION['flash_message']));
	}
	
	
	
	
	
	
	
	
	
	/*
	 * C�digo real abaixo, c�digo boilerplate acima. Poderia ser abstra�do em
	 * com heran�a.
	 */
	
	
	
	
	
	/**
	 * A��o inicial e de pesquisa
	 */
	public function index()
	{		
		// Popula comboboxes do formul�rio
		$equipamentos = $this->_dao->getListaEquipamentos();
		$falhas = $this->_dao->getListaFalhas();
		
		$resultado = null;
		if ($this->_hasParam('item_falha_id'))
		{
			try
			{
				$resultado = $this->_dao->getListaControleFalhas(array(
					'item_produto_id'	=> $this->_getRequestParam('item_produto_id'),
					'item_falha_id'		=> $this->_getRequestParam('item_falha_id'),
					'item_descricao'	=> $this->_getRequestParam('item_descricao')
				));			
			
				$itemFalha = $falhas[$this->_getRequestParam('item_falha_id')];
				
				$itemFalhaId = $this->_getRequestParam('item_falha_id');
			}
			catch (Exception $e)
			{
				$this->flashMessage($e->getMessage());
			}
		}
		
		require_once $this->_viewPath . 'index.php';
	}
	
	/**
	 * A��o de cadastro de novo item
	 */
	public function novo()
	{
		$arr = array(
			'item_produto_id'	=> $this->_getRequestParam('item_produto_id'),
			'item_falha_id'		=> $this->_getRequestParam('item_falha_id'),
			'item_descricao'	=> $this->_getRequestParam('item_descricao')
		);
		
		try
		{
			if ($this->_dao->inserir($arr))
			{
				$this->flashMessage('Registro Inclu�do.');
				
				// Monta query string da busca
				$queryString = http_build_query(array(
					'item_produto_id' 	=> $arr['item_produto_id'],
					'item_falha_id'		=> $arr['item_falha_id']
				));
				
				$this->_redirect('cad_info_controle_falhas.php?' . $queryString);
			}
			else
			{
				$this->_redirect('cad_info_controle_falhas.php');
			}
		}
		catch (Exception $e)
		{
			$this->flashMessage($e->getMessage());
			$this->_redirect('cad_info_controle_falhas.php');
		}
	}
	
	/**
	 * A��o de exclus�o de um item
	 */
	public function excluir()
	{
		try
		{
			$this->_dao->deletarItem(
				$this->_getPostParam('item_id_del'),
				$this->_getPostParam('item_falha_id')
			);
			
			$this->flashMessage('Registro Exclu�do.');
		}
		catch (Exception $e)
		{
			$this->flashMessage($e->getMessage());
		}
	}
}