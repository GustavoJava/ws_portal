<?php

require _MODULEDIR_ . 'Cadastro/DAO/CadControleFalhasAcessoriosNovoDAO.php';

/**
 * Classe respons�vel pelas regras de neg�cio de nova info. controle de falhas de acess�rios
  */
class CadControleFalhasAcessoriosNovo {

	/**	
	 * Vari�vel que armazena os resultados da pesquisa para uso na View
	 * @var array
	 */
	public $resultadoPesquisa;
	
	/**
	 * Vari�vel para armazenar o Serial que vem da View
	 * @var stdClass
	 */
	public $parametros;
	
	/**
	 * Vari�vel que armazena os dados da Ordem de Servi�o
	 * @var array
	 */
	public $ordemServico = false;
	
	/**
	 * Vari�vel que armazena um registro especifico do resultado
	 * @var stdClass
	 */
	public $registro;
	
	/**
	 * Vari�vel que armazena as tr�s combobox
	 * @var stdClass
	 */
	public $combos;
	
	/**
	 * Vari�vel que armazena a a��o da View
	 * @var string
	 */
	public $acao;
	
	 /**
	  * A variavel a��o pode ser alterada a medida que a funcao � executada
	  * a acaoOrigem, deve ser mantida e n�o alterada para fim de exebi��o correta das mensagens.
	  * @var string
	  */
	public $acaoOrigem;
	
	/**
	 * Vari�vel que armazena mensagens de erro
	 * @var string
	 */
	public $erro;
	
	/**
	 * Vari�vel que armazena mensagens de sucesso
	 * @var string
	 */
	public $mensagemSucesso;
	
	/**
	 * Vari�vel que armazena mensagens de alerta
	 * @var string
	 */
	public $mensagemAlerta;
	
	/**
	 * Vari�vel que armazena os campos que devem ser destacados
	 * @var string
	 */
	public $camposAlerta;
	
	/**
	 * Vari�vel que armazena os registros com a mesma Dt Entrada Lab e Imoboid
	 * que o registro que est� sendo inserido/editado
	 * @var array:stdClass
	 */
	public $registrosComuns;
		
	/**
	 * Regra: permite editar ou excluir somente registros com os status adicionados.
	 * @var array:integer
	 */
	private $statusImobEquipamento =  array(10,19,24,25);
	
	/**
	 * M�todo Construtor
	 */
	public function __construct() {
		global $conn;
		$this->parametros = new stdClass();
		$this->registro = new stdClass();
		$this->mensagemAlerta = "";
		$this->mensagemSucesso = "";
		$this->dao = new CadControleFalhasAcessoriosNovoDAO($conn);
		$this->erro = "";
	}
	
	/**
	 * M�todo que carrega as combos
	 */
	private function carregarCombos($tipo) {
		if ($tipo > 0) {
			$this->combos->defeitos = $this->dao->buscarDefeitosLab($tipo);
			$this->combos->acoes = $this->dao->buscarAcoesLab($tipo);
			$this->combos->componentes = $this->dao->buscarComponentesLabs($tipo);
		} else {
			throw new Exception("Erro ao carregar combobox.");
		}
	}
	
	/**
	 * M�todo Controlador que carrega a tela padr�o de pesquisa
	 */
	public function index() {
		$this->acao = "index";
		$this->erro = "";
		include(_MODULEDIR_ . 'Cadastro/View/cad_falhas_acessorios_novo/index.php');
	}
	
	/**
	 * M�todo controlador para a a��o de pesquisar
	 * @throws Exception
	 */
	public function pesquisar($validaPost=true) {
		$this->acao = "pesquisar";
		$this->erro = "";
		if($validaPost) $this->validarPost();
		$this->resultadoPesquisa = $this->dao->pesquisar($this->parametros->tipo, $this->parametros->serial);
		if (count($this->resultadoPesquisa) > 0) {
			$this->parametros->imoboid = $this->resultadoPesquisa[0]->imoboid;
			$this->verificarEdicaoExclusao();
			if ($this->dao->buscarDefeitosImoboid($this->resultadoPesquisa[0]) > "2") {
				//Avisar usu�rio
				$this->resultadoPesquisa[0]->avisarUsuario = true;
			} else {			
				$this->resultadoPesquisa[0]->avisarUsuario = false;
			}
		}
		include(_MODULEDIR_ . 'Cadastro/View/cad_falhas_acessorios_novo/index.php');
	}
	
	/**
	 * M�todo controlador para a a��o de incluir registros 
	 */
	public function salvar() {
		$this->acao = "salvar";
		$this->validarPost();
		try {
			$this->carregarCombos($this->parametros->tipo);
			$this->parametros->editar = false;
			
			$edita=false;
			if ($this->parametros->cfaoid > 0) {
				$this->parametros->editar = true;
				$edita = $this->editar();
			} else {			
				$edita = $this->incluir();	
			}
			
			if($edita){
				$resultadoEntradaLab = $this->dao->buscarDataEntradaLab($this->registro->imoboid);
				$this->registro->dataEntradaLab = $resultadoEntradaLab->data;
	
				if (empty($this->registro->dataEntradaLab) && !$this->parametros->editar && $this->mensagemAlerta == "") {
					$this->mensagemAlerta = "N�o existe data de entrada no laborat�rio.";
					$this->acao = "pesquisar";
				} else if (!empty($this->registro->dataEntradaLab) && $this->mensagemAlerta == "") {
					if ($this->parametros->imoboid == 0) {
						$this->parametros->imoboid = $this->registro->imoboid;
					}
					$registros = $this->dao->buscarRegistrosSerialEntradaLab($this->parametros->imoboid,  
																						 $this->registro->dataEntradaLab);
					$this->registrosComuns = array();
					foreach ($registros as $registro) {
						if($registro->cfaoid != $this->registro->cfaoid){
							$this->registrosComuns[] = $this->dao->buscarPorCfaoid($registro->cfaoid);
						}
					}
					
				}
				
				$this->registro->imobhoid = $resultadoEntradaLab->imobhoid;
				
				$connumero = $this->dao->buscarUltimoContratoCadastrado($this->parametros->imoboid);
				$this->ordemServico = false;
				if (!empty($connumero)) {
					$this->ordemServico = $this->dao->buscarDadosOsAssistenciaRetirada($connumero, $this->parametros->imoboid);
				}
			}
			else{
				$this->pesquisar();
				return;
			}
			
		} catch (Exception $e) {
			throw new Exception('Erro ao carregar/processar inclus�o');	
		}
		include(_MODULEDIR_ . 'Cadastro/View/cad_falhas_acessorios_novo/index.php');
	}
	
	/**
	 * M�todo para gravar registros
	 */
	public function gravar() {
		$retorno = $this->validarPost();
        
		//ob_start();
        
		if($retorno['status']){
			$resultadoEntradaLab = $this->dao->buscarDataEntradaLab($this->parametros->imoboid);
			$this->parametros->imobhoid = $resultadoEntradaLab->imobhoid;
			$this->parametros->dataEntradaLab = $resultadoEntradaLab->data;
			            
            $registro = $this->dao->buscarPorImoboid($this->parametros->imoboid);
            
            if(!in_array($registro->imobimsoid, $this->statusImobEquipamento)){
                $retorno['status']  = false;
				$retorno['tipoErro']  = 'alerta';
				$retorno['mensagem']  = utf8_encode('Status n�o corresponde.');
            } else {
                $gravou = $this->dao->inserirRegistro($this->parametros);
                
                if(!$gravou) {
                    $retorno['status']  = false;
                    $retorno['tipoErro']  = 'erro';
                    $retorno['mensagem']  = 'Houve algum erro no processamento dos dados.';
                } else {
                    $retorno['status']    = true;
                    $retorno['dados']     = $this->dao->buscarPorCfaoid($gravou); 
                    $retorno['tipoErro']  = 'sucesso';

                    $retorno['dados']->ifddescricao=utf8_encode(wordwrap($retorno['dados']->ifddescricao, 25, "<br />", true));
                    $retorno['dados']->ifadescricao=utf8_encode(wordwrap($retorno['dados']->ifadescricao, 25, "<br />", true));
                    $retorno['dados']->ifcdescricao=utf8_encode(wordwrap($retorno['dados']->ifcdescricao, 25, "<br />", true));				
                }
            } 
			
		}
                
		//ob_end_clean();
		echo json_encode($retorno);
	}
	
	
	
	/**
	 * M�todo que carrega a a��o de editar
	 */
	public function editar() {		
		$this->registro = $this->dao->buscarPorCfaoid($this->parametros->cfaoid);
		return true;
		
	}
	
	/**
	 * M�todo que carrega a a��o de incluir
	 */
	public function incluir() {
		$this->resultadoPesquisa = $this->dao->pesquisarImobilizado($this->parametros->tipo, $this->parametros->serial);
		if (count($this->resultadoPesquisa) > 0) {
			$this->registro = $this->resultadoPesquisa[0];
			return true;
		} else {
			$this->mensagemAlerta = "Esse n�mero de serial n�o existe no sistema.";
			$this->camposAlerta[] = "serial";
			$this->acao = "pesquisar";
			return false;
		}
	}

	/**
	 * Verifica se os registros devem estar com edi��o/exclus�o habilitadas
	 */
	private function verificarEdicaoExclusao() {
		
		foreach ($this->resultadoPesquisa as $key => $registro) {
			$data = $this->dao->buscarDataEntradaLab($registro->imoboid);
			$editarExcluir=false;
			if (empty($data->data)) {
				$editarExcluir = false;
			} else if ($registro->cfadt_entrada == $data->data && in_array($registro->imobimsoid, $this->statusImobEquipamento)) {
				$editarExcluir = true;
			}
			$this->resultadoPesquisa[$key]->editarExcluir = $editarExcluir;
		}
			
	}
	
	/**
	 * Valida as vari�veis que v�m do POST
	 */
	private function validarPost() {
		$retorno = array(
				'status' => true,
				'dados'  => array()
		);
		
		$this->acaoOrigem		 			= (isset($_POST['acao']) 			? $_POST['acao'] 			  : (isset($_GET['acao']) 			? $_GET['acao'] 			  : ""));
		$this->parametros->serial 			= isset($_POST['serial']) 			? $_POST['serial'] 			  : "";
		$this->parametros->tipo   			= isset($_POST['tipo'])   			? $_POST['tipo']   			  : 0;
		$this->parametros->defeito_lab  	= isset($_POST['defeito_lab']) 		? $_POST['defeito_lab'] 	  : 0;
		$this->parametros->acao_lab 		= isset($_POST['acao_lab']) 		? $_POST['acao_lab'] 		  : 0;
		$this->parametros->componente_lab  	= isset($_POST['componente_lab']) 	? $_POST['componente_lab'] 		  : 0;
		$this->parametros->cfaoid			= isset($_POST['cfaoid'])			? $_POST['cfaoid']			  : 0;
		$this->parametros->chk_codigo		= isset($_POST['chk_codigo'])		? (array)$_POST['chk_codigo'] : array();
		$this->parametros->imoboid			= isset($_POST['imoboid'])			? intval($_POST['imoboid'])	  : 0;
		
		return $retorno;
	}
	
	/**
	 * M�todo para exclus�o de registros
	 */
	public function excluir() {
		$this->validarPost();
		if (count($this->parametros->chk_codigo) > 0) {
			foreach($this->parametros->chk_codigo as $cfaoid) {
				$registro = $this->dao->buscarPorCfaoid($cfaoid);
				if(in_array($registro->imobimsoid, $this->statusImobEquipamento)){
					$this->dao->excluirRegistro($cfaoid);
				}
			}
			$this->mensagemSucesso = "Registro exclu�do com sucesso.";
		} else {
			$this->mensagemAlerta = "Nenhum item selecionado";
		}
		
		$this->acaoOrigem='pesquisar';
		$this->pesquisar(false);
	}
	
}
