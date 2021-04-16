<?php

/**
 * Classe CadParametrizacaoRsCalculoRepasse.
 * Camada de regra de neg�cio.
 *
 * @package  Cadastro
 * @author   Ricardo Marangoni da Mota <ricardo.mota@meta.com.br>
 *
 */
class CadRetencaoImpostos {

    /**
     * Objeto DAO da classe.
     *
     * @var CadExemploDAO
     */
    private $dao;

    /**
     * Mensagem de alerta para campos obrigat�rios n�o preenchidos
     * @const String
     */

    const MENSAGEM_ALERTA_CAMPOS_OBRIGATORIOS = "Existem campos obrigat�rios n�o preenchidos.";

    /**
     * Mensagem de sucesso para inser��o do registro
     * @const String
     */
    const MENSAGEM_SUCESSO_INCLUIR = "Registro inclu�do com sucesso.";

    /**
     * Mensagem de sucesso para altera��o do registro
     * @const String
     */
    const MENSAGEM_SUCESSO_ATUALIZAR = "Registro alterado com sucesso.";

    /**
     * Mensagem de sucesso para exclus�o do registro
     * @const String
     */
    const MENSAGEM_SUCESSO_EXCLUIR = "Registro exclu�do com sucesso.";

    /**
     * Mensagem para nenhum registro encontrado
     * @const String
     */
    const MENSAGEM_NENHUM_REGISTRO = "Nenhum registro encontrado.";

    /**
     * Mensagem de erro para o processamentos dos dados
     * @const String
     */
    const MENSAGEM_ERRO_PROCESSAMENTO = "Houve um erro no processamento dos dados.";
    /**
     * Cont�m dados a serem utilizados na View.
     *
     * @var stdClass
     */
    private $view;

    /**
     * M�todo construtor.
     *
     * @param CadExemploDAO $dao Objeto DAO da classe
     */
    public function __construct($dao = null) {

        //Verifica o se a vari�vel � um objeto e a instancia na atributo local
        if (is_object($dao)) {
            $this->dao = $dao;
        }

        //Cria objeto da view
        $this->view = new stdClass();
        //Mensagem
        $this->view->mensagemErro = '';
        $this->view->mensagemAlerta = '';
        $this->view->mensagemSucesso = '';

        //Dados para view
        $this->view->dados = null;

        //Filtros/parametros utlizados na view
        $this->view->parametros = null;

        //Status de uma transa��o
        $this->view->status = false;
    }

    /**
     * M�todo padr�o da classe.
     *
     * Repons�vel tamb�m por realizar a pesquisa invocando o m�todo privado
     *
     * @return void
     */
    public function index() {

        try {
            $this->view->parametros = $this->tratarParametros();

            //Inicializa os dados
            $this->inicializarParametros();

            $this->view->dadosPesquisa = $this->pesquisar($this->view->parametros);


        } catch (ErrorException $e) {

            $this->view->mensagemErro = $e->getMessage();

        } catch (Exception $e) {

            $this->view->mensagemAlerta = $e->getMessage();

        }

        if(count($this->view->dadosPesquisa) > 0) {
            $this->editar($this->view->dadosPesquisa[0]->prsrioid);
        } else {
            require_once _MODULEDIR_ . "Cadastro/View/cad_retencao_impostos/index.php";
        }

    }

    public function historico() {

        try {
            $this->parametros = $this->tratarParametros();

            //Inicializa os dados
            $this->inicializarParametros();


            $this->view->dados = $this->pesquisarHistorico($this->parametros);


        } catch (ErrorException $e) {

            $this->view->mensagemErro = $e->getMessage();

        } catch (Exception $e) {

            $this->view->mensagemAlerta = $e->getMessage();

        }

        //Inclir a view padr�o
        //@TODO: Montar dinamicamente o caminho apenas da view Index
        require_once _MODULEDIR_ . "Cadastro/View/cad_retencao_impostos/historico.php";
    }

    /**
     * Trata os parametros do POST/GET. Preenche um objeto com os parametros
     * do POST e/ou GET.
     *
     * @return stdClass Parametros tradados
     *
     * @retrun stdClass
     */
    private function tratarParametros() {
        $retorno = new stdClass();

        if (count($_POST) > 0) {
            foreach ($_POST as $key => $value) {
                $retorno->$key = isset($_POST[$key]) ? $value : '';
            }
        }

        if (count($_GET) > 0) {
            foreach ($_GET as $key => $value) {

                //Verifica se atributo j� existe e n�o sobrescreve.
                if (!isset($retorno->$key)) {
                     $retorno->$key = isset($_GET[$key]) ? $value : '';
                }
            }
        }
        return $retorno;
    }

    /**
     * Popula os arrays para os combos de estados e cidades
     *
     * @return void
     */
    private function inicializarParametros() {

        //Verifica se os parametro existem, sen�o iniciliza todos
    	if (!isset($this->view->parametros)){
    		$this->view->parametros = new stdClass();
    	} 
        $this->view->parametros->dataUltimoHistorico = $this->dao->buscarDataUltimoHistorico();
        $this->view->parametros->usuoid = $_SESSION['usuario']['oid'];

    }


    /**
     * Respons�vel por tratar e retornar o resultado da pesquisa.
     *
     * @param stdClass $filtros Filtros da pesquisa
     *
     * @return array
     */
    private function pesquisar(stdClass $filtros) {

        $resultadoPesquisa = $this->dao->pesquisar($filtros);

        return $resultadoPesquisa;
    }

    /**
     * Respons�vel por tratar e retornar o resultado da pesquisa.
     *
     * @param stdClass $filtros Filtros da pesquisa
     *
     * @return array
     */
    private function pesquisarHistorico(stdClass $filtros) {

        $resultadoPesquisa = $this->dao->pesquisarHistorico($filtros);

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
     *
     * @param stdClass $parametros Dados do cadastro, para edi��o (opcional)
     *
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

        $this->index();
    }

    /**
     * Respons�vel por receber exibir o formul�rio de edi��o ou invocar
     * o metodo para salvar os dados
     *
     * @return void
     */
    public function editar($prsrioid) {

        try {
            //Parametros
            $parametros = $this->tratarParametros();
            $acao = $parametros->acao;

            //Verifica se foi informado o id do cadastro
            if (intval($prsrioid) > 0) {
                //Realiza o CAST do parametro
                $prsrioid = (int) $prsrioid;

                //Pesquisa o registro para edi��o
                $this->view->parametros = $this->dao->pesquisarPorID($prsrioid);
                $this->view->parametros->prsrioid = $prsrioid;
                $this->view->parametros->acao = $acao;

                $this->inicializarParametros();

                //Chama o metodo para edi��o passando os dados do registro por parametro.
                require_once _MODULEDIR_ . "Cadastro/View/cad_retencao_impostos/editar.php";
            } else {
                throw new Exception("� necess�rio informar um registro para a edi��o");

            }

        } catch (ErrorException $e) {
            $this->view->mensagemErro = $e->getMessage();
            require_once _MODULEDIR_ . "Cadastro/View/cad_retencao_impostos/editar.php";
        } catch (Exception $e) {
            $this->view->mensagemErro = $e->getMessage();
            require_once _MODULEDIR_ . "Cadastro/View/cad_retencao_impostos/editar.php";
        }
    }

    /**
     * Grava os dados na base de dados.
     *
     * @param stdClass $dados Dados a serem gravados
     *
     * @return void
     */
    private function salvar(stdClass $dados) {

        //Validar os campos
        $this->validarCamposCadastro($dados);

        //Inicia a transa��o
        $this->dao->begin();

        //Grava��o
        $gravacao = null;

        if ($dados->prsrioid > 0) {
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
     *
     * @throws Exception
     *
     * @return void
     */
    private function validarCamposCadastro(stdClass $dados) {

        //Campos para destacar na view em caso de erro
        $camposDestaques = array();

        //Verifica se houve erro
        $error = false;

        /**
         * Verifica os campos obrigat�rios
         */
        /** Ex.:
        if (!isset($dados->excnome) || trim($dados->excnome) == '') {
            $camposDestaques[] = array(
                'campo' => 'excnome'
            );
            $error = true;
        }
		*/

        if (!isset($dados->prsriiss) || trim($dados->prsriiss) == '') {
            $camposDestaques[] = array(
                'campo' => 'prsriiss'
            );
            $error = true;
        }

        if (!isset($dados->prsripis) || trim($dados->prsripis) == '') {
            $camposDestaques[] = array(
                'campo' => 'prsripis'
            );
            $error = true;
        }

        if (!isset($dados->prsricofins) || trim($dados->prsricofins) == '') {
            $camposDestaques[] = array(
                'campo' => 'prsricofins'
            );
            $error = true;
        }


        if (!isset($dados->prsrivalor_chip) || trim($dados->prsrivalor_chip) == '') {
            $camposDestaques[] = array(
                'campo' => 'prsrivalor_chip'
            );
            $error = true;
        }

        if ($error) {
            $this->view->dados = $camposDestaques;
            throw new Exception(self::MENSAGEM_ALERTA_CAMPOS_OBRIGATORIOS);
        }


        /**
         * Verifica se os campos s�o maiores que 0
         */
        if ((int)$dados->prsricofins == 0) {
            $camposDestaques[] = array(
                'campo' => 'prsricofins'
            );
            $error = true;
        }

        if ((int)$dados->prsripis == 0) {
            $camposDestaques[] = array(
                'campo' => 'prsripis'
            );
            $error = true;
        }

        if ((int)$dados->prsriiss == 0) {
            $camposDestaques[] = array(
                'campo' => 'prsriiss'
            );
            $error = true;
        }


        if ((double)$dados->prsrivalor_chip == 0) {
            $camposDestaques[] = array(
                'campo' => 'prsrivalor_chip'
            );
            $error = true;
        }

        if ($error) {
            $this->view->dados = $camposDestaques;
            throw new Exception('Valores devem ser maior que zero (0).');
        }


        /**
         * Verifica se a porcentagem n�o ultrapassa 100%
         */
        if ((int)$dados->prsricofins > 100) {
            $camposDestaques[] = array(
                'campo' => 'prsricofins'
            );
            $error = true;
        }

        if ((int)$dados->prsripis > 100) {
            $camposDestaques[] = array(
                'campo' => 'prsripis'
            );
            $error = true;
        }

        if ((int)$dados->prsriiss > 100) {
            $camposDestaques[] = array(
                'campo' => 'prsriiss'
            );
            $error = true;
        }

        if ($error) {
            $this->view->dados = $camposDestaques;
            throw new Exception('Valores em porcentagem devem ter um limite de 100%.');
        }
    }

    /**
     * Executa a exclus�o de registro.
     *
     * @return void
     */
    public function excluir() {
        try {

            //Retorna os parametros
            $parametros = $this->tratarParametros();

            //Inicializa os dados
            $this->inicializarParametros();

            //Verifica se foi informado o id
            if (!isset($parametros->prsrioid) || trim($parametros->prsrioid) == '') {
                throw new ErrorException(self::MENSAGEM_ERRO_PROCESSAMENTO);
            }

            //Inicia a transa��o
            $this->dao->begin();

            //Realiza o CAST do parametro
            $parametros->prsrioid = (int) $parametros->prsrioid;

            $dados = $this->dao->pesquisarPorID($parametros->prsrioid);
            $dados->usuoid = $this->view->parametros->usuoid;
            //Remove o registro
            $confirmacao = $this->dao->excluir($parametros->prsrioid, $dados);

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


}

