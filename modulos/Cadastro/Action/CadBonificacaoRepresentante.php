<?php

/**
 * Classe CadBonificacaoRepresentante.
 * Camada de regra de neg�cio.
 *
 * @package  Cadastro
 * @author   RICARDO ROJO BONFIM <ricardo.bonfim@meta.com.br>
 *
 */
class CadBonificacaoRepresentante {

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
    const MENSAGEM_SUCESSO_CANCELAR           = "Registro cancelado com sucesso.";
    const MENSAGEM_NENHUM_REGISTRO            = "Nenhum registro encontrado.";
    const MENSAGEM_ERRO_PROCESSAMENTO         = "Houve um erro no processamento dos dados.";
    const MENSAGEM_HISTORICO                  = "O usu�rio <nome_usuario> <acao_usuario> o custo de efici�ncia operacional.";
    const MENSAGEM_BONIFICACAO_REPETIDA       = "O representante j� possui um registro em aberto cadastrado para este m�s.";


    /**
     * M�todo construtor.
     * @param $dao Objeto DAO da classe
     */
    public function __construct($dao = null) {


        $this->dao                   = (is_object($dao) ? $dao : NULL);
        $this->view                  = new stdClass();
        $this->view->mensagemErro    = '';
        $this->view->mensagemAlerta  = '';
        $this->view->mensagemSucesso = '';
        $this->view->dados           = null;
        $this->view->parametros      = null;
        $this->view->status          = false;
        $this->usuarioLogado         = isset($_SESSION['usuario']['oid']) ? $_SESSION['usuario']['oid'] : '';

        //Se nao tiver nada na sessao assume usuario AUTOMATICO (para CRON e WebService)
        $this->usuarioLogado         = (empty($this->usuarioLogado) ? 2750 : intval($this->usuarioLogado));
    }

    /**
     * Repons�vel tamb�m por realizar a pesquisa invocando o m�todo privado
     * @return void
     */
    public function index() {

        try {

            $this->view->permissao = $_SESSION['funcao']['cadastro_bonificacao_rt'];

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
        require_once _MODULEDIR_ . "Cadastro/View/cad_bonificacao_representante/index.php";
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
        $this->view->categorias = $this->dao->buscarCategoriasBonificacao();
        $this->view->representantes = $this->dao->buscarRepresentantes();
        $this->view->parametros->bonrerepoid = isset($this->view->parametros->bonrerepoid) && !empty($this->view->parametros->bonrerepoid) ? trim($this->view->parametros->bonrerepoid) : ""; 
        $this->view->parametros->bonredt_bonificacao = isset($this->view->parametros->bonredt_bonificacao) && !empty($this->view->parametros->bonredt_bonificacao) ? trim($this->view->parametros->bonredt_bonificacao) : ""; 
        $this->view->parametros->bonrebonrecatoid = isset($this->view->parametros->bonrebonrecatoid) && !empty($this->view->parametros->bonrebonrecatoid) ? trim($this->view->parametros->bonrebonrecatoid) : ""; 
        $this->view->parametros->bonrevalor_bonificacao = isset($this->view->parametros->bonrevalor_bonificacao) && trim($this->view->parametros->bonrevalor_bonificacao) != "" ? trim($this->view->parametros->bonrevalor_bonificacao) : 0 ; 
        $this->view->parametros->bonreqtd_min_os = isset($this->view->parametros->bonreqtd_min_os) && trim($this->view->parametros->bonreqtd_min_os) != "" ? trim($this->view->parametros->bonreqtd_min_os) : 0 ; 


    }


    /**
     * Respons�vel por tratar e retornar o resultado da pesquisa.
     * @param stdClass $filtros Filtros da pesquisa
     * @return array
     */
    private function pesquisar(stdClass $filtros) {

        $resultadoPesquisa = $this->dao->pesquisar($filtros);

        //Valida se houve resultado na pesquisa
        if (count($resultadoPesquisa) == 0) {
            throw new Exception(self::MENSAGEM_NENHUM_REGISTRO);
        }

        $this->view->status = TRUE;
        $this->view->permissao = $_SESSION['funcao']['cadastro_bonificacao_rt'];

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
            $this->index();
        } else {

            require_once _MODULEDIR_ . "Cadastro/View/cad_bonificacao_representante/cadastrar.php";
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
            if (isset($parametros->bonreoid) && intval($parametros->bonreoid) > 0) {
                //Realiza o CAST do parametro
                $parametros->bonreoid = (int) $parametros->bonreoid;

                //Pesquisa o registro para edi��o
                $dados = $this->dao->pesquisarPorID($parametros->bonreoid);
                $dados->historico = $this->dao->buscarHistorico($parametros->bonreoid);

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

        //Validar os campos
        $this->validarCamposCadastro($dados);

        $this->validarBonificacaoRepetida($dados);

        //Inicia a transa��o
        $this->dao->begin();

        //Grava��o
        $gravacao = null;

        $dados->bonrevalor_bonificacao = str_replace('.', '', $dados->bonrevalor_bonificacao);
        $dados->bonrevalor_bonificacao = str_replace(',', '.', $dados->bonrevalor_bonificacao);

        if ($dados->bonreoid > 0) {
            //Efetua a grava��o do registro
            $gravacao = $this->dao->atualizar($dados);

            $mensagemHistorico = self::MENSAGEM_HISTORICO;
            $mensagemHistorico = str_replace('<nome_usuario>', $_SESSION['usuario']['nome_completo'], $mensagemHistorico);
            $mensagemHistorico = str_replace('<acao_usuario>', 'editou', $mensagemHistorico);
            $this->dao->inserirHistorico($dados->bonreoid, $mensagemHistorico);

            $this->view->mensagemSucesso = self::MENSAGEM_SUCESSO_ATUALIZAR;

            //Seta a mensagem de atualiza��o
        } else {
            //Efetua a inser��o do registro
            $dados->bonreoid = $this->dao->inserir($dados);
            $gravacao = true;

            $mensagemHistorico = self::MENSAGEM_HISTORICO;
            $mensagemHistorico = str_replace('<nome_usuario>', $_SESSION['usuario']['nome_completo'], $mensagemHistorico);
            $mensagemHistorico = str_replace('<acao_usuario>', 'criou', $mensagemHistorico);
            $this->dao->inserirHistorico($dados->bonreoid, $mensagemHistorico);

            $this->view->mensagemSucesso = self::MENSAGEM_SUCESSO_INCLUIR;
        }

        //Comita a transa��o
        $this->dao->commit();

        $_POST = array();

        return $gravacao;
    }

    private function validarBonificacaoRepetida(stdClass $parametros) {
        $erro = false;
        $dadosPesquisa = new stdClass();

        $dadosPesquisa->bonrestatus = 'A';
        $dadosPesquisa->bonredt_bonificacao = $parametros->bonredt_bonificacao;
        $dadosPesquisa->bonrerepoid = $parametros->bonrerepoid;

        $bonificacoes = $this->dao->pesquisar($dadosPesquisa);

        foreach ($bonificacoes as $bonificacao) {
            if ($bonificacao->bonreoid != $parametros->bonreoid) {
                $erro = true;
            }
        }

        if ($erro) {
            throw new Exception(self::MENSAGEM_BONIFICACAO_REPETIDA);
        }

        return true;        
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
        if (!isset($dados->bonrerepoid) || trim($dados->bonrerepoid) == '') {
            $camposDestaques[] = array(
                'campo' => 'bonrerepoid'
            );
        }

        if (!isset($dados->bonredt_bonificacao) || trim($dados->bonredt_bonificacao) == '') {
            $camposDestaques[] = array(
                'campo' => 'bonredt_bonificacao'
            );
        }

        if (!isset($dados->bonrebonrecatoid) || trim($dados->bonrebonrecatoid) == '') {
            $camposDestaques[] = array(
                'campo' => 'bonrebonrecatoid'
            );
        }

        if (!isset($dados->bonrevalor_bonificacao) || trim($dados->bonrevalor_bonificacao) == '' || (int) $dados->bonrevalor_bonificacao == 0) {
            $camposDestaques[] = array(
                'campo' => 'bonrevalor_bonificacao'
            );
        }

        if (!isset($dados->bonreqtd_min_os) || trim($dados->bonreqtd_min_os) == '' || (int) $dados->bonreqtd_min_os == 0) {
            $camposDestaques[] = array(
                'campo' => 'bonreqtd_min_os'
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
            if (!isset($parametros->bonreoid) || trim($parametros->bonreoid) == '') {
                throw new ErrorException(self::MENSAGEM_ERRO_PROCESSAMENTO);
            }

            //Inicia a transa��o
            $this->dao->begin();

            //Realiza o CAST do parametro
            $parametros->bonreoid = (int) $parametros->bonreoid;

            //Remove o registro
            $confirmacao = $this->dao->excluir($parametros->bonreoid);

            if ($confirmacao) {

                $mensagemHistorico = self::MENSAGEM_HISTORICO;
                $mensagemHistorico = str_replace('<nome_usuario>', $_SESSION['usuario']['nome_completo'], $mensagemHistorico);
                $mensagemHistorico = str_replace('<acao_usuario>', 'excluiu', $mensagemHistorico);
                $this->dao->inserirHistorico($parametros->bonreoid, $mensagemHistorico);

                $this->view->mensagemSucesso = self::MENSAGEM_SUCESSO_EXCLUIR;
            }

            //Comita a transa��o
            $this->dao->commit();

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

    public function cancelarBonificacao() {

        try {

            //Retorna os parametros
            $parametros = $this->tratarParametros();

            //Verifica se foi informado o id
            if (!isset($parametros->bonreoid) || trim($parametros->bonreoid) == '') {
                throw new ErrorException(self::MENSAGEM_ERRO_PROCESSAMENTO);
            }

            //Inicia a transa��o
            $this->dao->begin();

            //Realiza o CAST do parametro
            $parametros->bonreoid = (int) $parametros->bonreoid;

            //Remove o registro
            $confirmacao = $this->dao->cancelarBonificacao($parametros->bonreoid);

            if ($confirmacao) {

                $mensagemHistorico = self::MENSAGEM_HISTORICO;
                $mensagemHistorico = str_replace('<nome_usuario>', $_SESSION['usuario']['nome_completo'], $mensagemHistorico);
                $mensagemHistorico = str_replace('<acao_usuario>', 'cancelou', $mensagemHistorico);
                $this->dao->inserirHistorico($parametros->bonreoid, $mensagemHistorico);

                $this->dao->limparComissoesInstalacao($parametros->bonreoid);

                $this->view->mensagemSucesso = self::MENSAGEM_SUCESSO_CANCELAR;
            }

            //Comita a transa��o
            $this->dao->commit();

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

