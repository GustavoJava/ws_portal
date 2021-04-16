<?php

/**
 * Classe CadCidadeMapeadaBairro.
 * Camada de regra de neg�cio.
 *
 * @package  Cadastro
 * @author   MARCIO SAMPAIO FERREIRA <marcioferreira@brq.com>
 *
 */


//Pagina��o
require_once _SITEDIR_ . 'lib/Components/Paginacao/PaginacaoComponente.php';

require_once _MODULEDIR_ ."/Cadastro/DAO/CadCidadeMapeadaBairroDAO.php";


class CadCidadeMapeadaBairro {

    /** Objeto DAO da classe */
    private $dao;

	/** propriedade para dados a serem utilizados na View. */
    private $view;

	/** Usuario logado */
	private $usuarioLogado;

    const MENSAGEM_ALERTA_CAMPOS_OBRIGATORIOS = "Existem campos obrigat�rios n�o preenchidos.";
    const MENSAGEM_SUCESSO_INCLUIR            = "Registro inclu�do com sucesso.";
    const MENSAGEM_SUCESSO_ATUALIZAR          = "Registro alterado com sucesso.";
    const MENSAGEM_SUCESSO_EXCLUIR            = "Registro exclu�do com sucesso.";
    const MENSAGEM_NENHUM_REGISTRO            = "Nenhum registro encontrado.";
    const MENSAGEM_ERRO_PROCESSAMENTO         = "Houve um erro no processamento dos dados.";
    const MENSAGEM_ALERTA_REGISTRO_DUPLICADO    = "Registro Duplicado.";
    

    /**
     * M�todo construtor.
     * @param $dao Objeto DAO da classe
     */
    public function __construct($dao = null) {

    	global $conn;

        $this->dao                   = new CadCidadeMapeadaBairroDAO($conn);
        $this->view                  = new stdClass();
        $this->view->mensagemErro    = '';
        $this->view->mensagemAlerta  = '';
        $this->view->mensagemSucesso = '';
        $this->view->dados           = null;
        $this->view->parametros      = null;
        $this->view->status          = false;
        $this->usuarioLogado         = isset($_SESSION['usuario']['oid']) ? $_SESSION['usuario']['oid'] : '';

        //Se nao tiver nada na sessao assume usuario AUTOMATICO (para CRON e WebService)
        $this->usuarioLogado         = (empty($this->usuarioLogado)) ? 2750 : intval($this->usuarioLogado);
        
        // Orden��o e pagina��o
        $this->view->ordenacao = null;
        $this->view->paginacao = null;
        $this->view->totalResultados = 0;
    }

    /**
     * Repons�vel tamb�m por realizar a pesquisa invocando o m�todo privado
     * @return void
     */
    public function index($param = NULL) {

        try {
        	
        	$populaFiltro = true;
        	
        	if(isset($param['gravado']) && $param['gravado'] == 'Ok'){
        		$populaFiltro = false;
        	}
        	
            $this->view->parametros = $this->tratarParametros();

            //Inicializa os dados
            $this->inicializarParametros();

            //Verificar se a a��o pesquisar e executa pesquisa
            if ( isset($this->view->parametros->acao) && $this->view->parametros->acao == 'pesquisar' ) {
            	
            	$paginacao = new PaginacaoComponente();
            	 
            	$quantPesquisa = $this->dao->pesquisar($this->view->parametros);
            	 
            	$this->view->totalResultados = $quantPesquisa[0]->total_registros;
            	
            	$campos = array(
            			''          => 'Escolha',
            			'estuf' => 'UF',
            			'clcnome' => 'Cidade',
            			'cbanome' => 'Bairro'
            	);
            	
            	 
            	if ($paginacao->setarCampos($campos)) {
            		$this->view->ordenacao = $paginacao->gerarOrdenacao('estuf, clcnome, cbanome');
            		$this->view->paginacao = $paginacao->gerarPaginacao($this->view->totalResultados);
            	}
            	 
            	$this->view->dados = $this->pesquisar($this->view->parametros, $paginacao->buscarPaginacao(), $paginacao->buscarOrdenacao());
                
            }
            
        } catch (ErrorException $e) {

            $this->view->mensagemErro = $e->getMessage();

        } catch (Exception $e) {

            $this->view->mensagemAlerta = $e->getMessage();

        }

        //Incluir a view padr�o
        require_once _MODULEDIR_ . "Cadastro/View/cad_cidade_mapeada_bairro/index.php";
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
                     $retorno->$key = isset($_GET[$key]) ? trim($value) : '';
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

        //Verifica se os parametro existem, sen�o iniciliza todos
    	$this->view->parametros->cmboid = isset($this->view->parametros->cmboid) && !empty($this->view->parametros->cmboid) ? trim($this->view->parametros->cmboid) : "";
		$this->view->parametros->cmbestoid = isset($this->view->parametros->cmbestoid) && !empty($this->view->parametros->cmbestoid) ? trim($this->view->parametros->cmbestoid) : ""; 
		$this->view->parametros->cmbclcoid = isset($this->view->parametros->cmbclcoid) && !empty($this->view->parametros->cmbclcoid) ? trim($this->view->parametros->cmbclcoid) : ""; 
		$this->view->parametros->cmbcbaoid = isset($this->view->parametros->cmbcbaoid) && !empty($this->view->parametros->cmbcbaoid) ? trim($this->view->parametros->cmbcbaoid) : ""; 

    }


    /**
     * Respons�vel por tratar e retornar o resultado da pesquisa.
     * @param stdClass $filtros Filtros da pesquisa
     * @return array
     */
    private function pesquisar(stdClass $filtros, $paginacao, $ordenacao) {
    	
        $resultadoPesquisa = $this->dao->pesquisar($filtros, $paginacao, $ordenacao);
        
        //Valida se houve resultado na pesquisa
        if (count($resultadoPesquisa) == 0) {
            throw new Exception(self::MENSAGEM_NENHUM_REGISTRO);
        }

        $this->view->status = TRUE;

        return $resultadoPesquisa;
    }

    /**
     * Respons�vel por receber exibir o formul�rio de cadastro ou invocar
     * o metodo para salvar os dados
     * @param stdClass $parametros
     * @return void
     */
    public function cadastrar($parametros = null) {
    	
        //identifica se o registro foi gravado
        $registroGravado = FALSE;
        
        try{

            if (is_null($parametros)) {
                $this->view->parametros = $this->tratarParametros();
            } else {
                $this->view->parametros = $parametros;
            }

            //Incializa os parametros
            $this->inicializarParametros();

            //Verificar se foi submetido o formul�rio e grava o registro em banco de dados
            if (isset($_POST) && !empty($_POST)) {
                $registroGravado = $this->salvar($this->view->parametros);
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

        //Verifica se o registro foi gravado e chama a index, caso contr�rio chama a view de cadastro.
        if ($registroGravado){
        	
        	$param['gravado'] = 'Ok';
                    	
        	$this->index($param);
        
        } else {

            require_once _MODULEDIR_ . "Cadastro/View/cad_cidade_mapeada_bairro/cadastrar.php";
        }
    }

    /**
     * Respons�vel por receber exibir o formul�rio de edi��o ou invocar
     * o metodo para salvar os dados
     * @return void
     */
    public function editar() {

        try {
            //Parametros
            $parametros = $this->tratarParametros();

            //Verifica se foi informado o id do cadastro
            if (isset($parametros->cmboid) && intval($parametros->cmboid) > 0) {
            	
                //Realiza o CAST do parametro
                $parametros->cmboid = (int) $parametros->cmboid;

                //Pesquisa o registro para edi��o
                $dados = $this->dao->pesquisarPorID($parametros->cmboid);

                //Chama o metodo para edi��o passando os dados do registro por parametro.
                $this->cadastrar($dados);
            } else {
                $this->index();
            }

        } catch (ErrorException $e) {
            $this->view->mensagemErro = $e->getMessage();
            $this->index();
        }
    }

    /**
     * Grava os dados na base de dados.
     *
     * @param stdClass $dados Dados a serem gravados
     * @return void
     */
    private function salvar(stdClass $dados) {
    	
    	//Grava��o
    	$gravacao = null;
    	
        //Validar os campos
        $this->validarCamposCadastro($dados);
        
        //verifica se j� existe registro gravado na base
        $dados_gravados =  $this->dao->pesquisarDuplicados($this->view->parametros);
        
        if($dados_gravados[0]->count > 0){

        	$this->view->mensagemAlerta =self::MENSAGEM_ALERTA_REGISTRO_DUPLICADO;
        	
        	return $gravacao;
        }
        
        //Inicia a transa��o
        $this->dao->begin();
        
        if ($dados->cmboid > 0) {
            //Efetua a grava��o do registro
            $gravacao = $this->dao->atualizar($dados);

            //Seta a mensagem de atualiza��o
            $this->view->mensagemSucesso = self::MENSAGEM_SUCESSO_ATUALIZAR;
        } else {
            //Efetua a inser��o do registro
            $gravacao = $this->dao->inserir($dados);
            $this->view->mensagemSucesso = self::MENSAGEM_SUCESSO_INCLUIR;
        }

        //Comita a transa��o
        $this->dao->commit();

        return $gravacao;
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
        if ((!isset($dados->cmbestoid) || trim($dados->cmbestoid) == '' || trim($dados->cmbestoid) == 'Escolha') 
        		|| (!isset($dados->cmbclcoid) || trim($dados->cmbcbaoid) == '' || trim($dados->cmbcbaoid) == 'Escolha' ) 
        		|| (!isset($dados->cmbcbaoid) || trim($dados->cmbcbaoid) == '' || trim($dados->cmbcbaoid) == 'Escolha' ) ) {
            $camposDestaques[] = array(
                'cmbestoid' => 'UF',
            	'cmbclcoid' => 'Cidade',
            	'cmbcbaoid' => 'Bairro'
            );
        }

        if (!empty($camposDestaques)) {
            $this->view->dados = $camposDestaques;
            throw new Exception(self::MENSAGEM_ALERTA_CAMPOS_OBRIGATORIOS);
        }
    }

    /**
     * Executa a exclus�o de registro.
     * @return void
     */
    public function excluir() {

        try {

            //Retorna os parametros
            $parametros = $this->tratarParametros();

            //Verifica se foi informado o id
            if (!isset($parametros->cmboid) || trim($parametros->cmboid) == '') {
                throw new ErrorException(self::MENSAGEM_ERRO_PROCESSAMENTO);
            }

            //Inicia a transa��o
            $this->dao->begin();

            //Realiza o CAST do parametro
            $parametros->cmboid = (int) $parametros->cmboid;

            //Remove o registro
            $confirmacao = $this->dao->excluir($parametros->cmboid);

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
     * Retorna a lista de UF's dos estados brasileiro
     * @return array object
     */
    public function getEstados(){
    	
    	$ufs = $this->dao->getEstados();
    	
    	return $ufs;
    }
    
    
    /**
     * Retorna as cidades dos estado informado
     * 
     * @return multitype:object
     */
    public function getCidades($id_estado = null){
    	
    	try {
    		 
     		$idEstado = isset($_POST['idEstado']) && $_POST['idEstado'] != '' ? $_POST['idEstado'] : $id_estado;
    		
    		if($idEstado == null){
    			throw new Exception('O ID do estado deve ser informado para pesquisar as cidades.');
    		}
    		
    		$cidades = $this->dao->getCidades($idEstado);
    		
    		foreach ($cidades as $key => $value) {
    	            $ret_cidades[$key]['clcoid'] = $value->clcoid;
    	            $ret_cidades[$key]['clcnome'] = $this->removerAcentos($value->clcnome);
    		}
    		
    		if($id_estado == null){
    			echo json_encode($ret_cidades);
    			exit();
    		}else{
    			return $ret_cidades;
    		}
    		
    	} catch (Exception $e) {
    		
    		 $this->view->mensagemErro = $e->getMessage();
    	}
    	
    }


    
    /**
     * Retorna os bairros da cidade informada
     *
     * @return multitype:object
     */
    public function getBairros($id_cidade = null){
    	 
    	try {
    
    		$idCidade = isset($_POST['idCidade']) && $_POST['idCidade'] != '' ? $_POST['idCidade'] : $id_cidade;
    
    		if($idCidade == null){
    			throw new Exception('O ID da Cidade deve ser informada para pesquisar os bairros.');
    		}
    
    		$bairros = $this->dao->getBairros($idCidade);
    
    		foreach ($bairros as $key => $value) {
    			$ret_bairros[$key]['cbaoid'] = $value->cbaoid;
    			$ret_bairros[$key]['cbanome'] = $this->removerAcentos($value->cbanome);
    		}
    
    		if($id_cidade == null){
    			echo json_encode($ret_bairros);
    			exit();
    			
    		}else{
    			return $ret_bairros;
    		}
    		
    	} catch (Exception $e) {
    
    		$this->view->mensagemErro = $e->getMessage();
    	}
    	 
    }
    
    
    /**
     * Remove acentua��o de string.
     * @param String $str
     * @return String
     */
    public function removerAcentos($str){
    	 
    	$busca     = array("�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�", "'", '"','�','�','�', '&');
    	$substitui = array("a","a","a","a","a","e","e","e","e","i","i","i","i","o","o","o","o","o","u","u","u","u","c", "" , "" ,'' ,'' ,'', '');
    	 
    	$str       = str_replace($busca,$substitui,$str);
    	 
    	$busca     = array("�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�", "<", ">" );
    	$substitui = array("A","A","A","A","A","E","E","E","E","I","I","I","I","O","O","O","O","O","U","U","U","U","C", ""  ,"" , "" , "");
    	 
    	$str       = str_replace($busca,$substitui,$str);
    	return $str;
    }
    

}

