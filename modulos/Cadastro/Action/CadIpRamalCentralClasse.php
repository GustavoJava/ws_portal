<?php

/**
 * Classe CadIpRamalCentralClasse.
 * Camada de regra de neg�cio.
 *
 * @package  Cadastro
 * @author   Harry Luiz Janz <harry.janz@sascar.com.br>
 *
 */
class CadIpRamalCentralClasse {

    /** Objeto DAO da classe */
    private $dao;

	/** propriedade para dados a serem utilizados na View. */
    private $view;

	/** Usuario logado */
	private $usuarioLogado;

    const MENSAGEM_ERRO_PROCESSAMENTO         = "Houve um erro no processamento dos dados.";
    const MENSAGEM_SUCESSO_INCLUIR            = "Registro inclu�do com sucesso.";
    const MENSAGEM_SUCESSO_ATUALIZAR          = "Registro alterado com sucesso.";
    const MENSAGEM_SUCESSO_EXCLUIR            = "Registro exclu�do com sucesso.";
    const MENSAGEM_NENHUM_REGISTRO            = "Nenhum registro encontrado.";
    const MENSAGEM_ALERTA_CAMPOS_OBRIGATORIOS = "Existem campos obrigat�rios n�o preenchidos.";
    const MENSAGEM_ALERTA_CAMPOS_TAMANHO      = "O C�digo e a Descri��o devem ter no m�nimo tr�s d�gitos.";
    const MENSAGEM_ALERTA_DUPLICIDADE         = "J� existe um registro com mesmo ramal e/ou IP.";
    const MENSAGEM_IP_INVALIDO                = "IP inserido � inv�lido.";


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

            //Valida se o departamento e cargo do susu�rio logado pussuem permiss�o para a pagina acessada
            if(!$this->dao->validarPermissaoPagina()) {
                header('Location: acesso_invalido.php');
            }

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
        require_once _MODULEDIR_ . "Cadastro/View/cad_ip_ramal_central/index.php"; 

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
        $this->view->parametros->oid = isset($this->view->parametros->oid) && !empty($this->view->parametros->oid) ? trim($this->view->parametros->oid) : ""; 

        $this->view->parametros->ripramal = isset($this->view->parametros->ripramal) && !empty($this->view->parametros->ripramal) ? trim($this->view->parametros->ripramal) : ""; 

        $this->view->parametros->ripip = isset($this->view->parametros->ripip) && !empty($this->view->parametros->ripip) ? trim($this->view->parametros->ripip) : "";

        $this->view->parametros->ripdt_cadastro = isset($this->view->parametros->ripdt_cadastro) && !empty($this->view->parametros->ripdt_cadastro) ? trim($this->view->parametros->ripdt_cadastro) : "";

        $this->view->parametros->ripdt_exclusao = isset($this->view->parametros->ripdt_exclusao) && !empty($this->view->parametros->ripdt_exclusao) ? trim($this->view->parametros->ripdt_exclusao) : "";

        $this->view->parametros->ripdescricao = isset($this->view->parametros->ripdescricao) && !empty($this->view->parametros->ripdescricao) ? trim($this->view->parametros->ripdescricao) : "";

        $this->view->parametros->ripponto_roteamento = isset($this->view->parametros->ripponto_roteamento) && !empty($this->view->parametros->ripponto_roteamento) ? trim($this->view->parametros->ripponto_roteamento) : "";

    }

    /**
     * Respons�vel por tratar e retornar o resultado da pesquisa.
     * @param stdClass $filtros Filtros da pesquisa
     * @return array
     */
    private function pesquisar(stdClass $filtros) {

        $filtros = $this->tratarParametros();

        $filtros->agcdescricaoConsulta = $this->removerAcentos($filtros->agcdescricao);

        $resultadoPesquisa = $this->dao->pesquisar($filtros);

        //Valida se houve resultado na pesquisa
        if (count($resultadoPesquisa) == 0) {

            $this->view->mensagemAlerta = self::MENSAGEM_NENHUM_REGISTRO;
        }

        $this->view->filtros = $filtros;
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
            }else{
                $this->view->mensagemInfo = "Para obter o RAMAL a ser vinculado ao IP disque <b>*570</b> e obtenha o n�mero da <b>identidade l�gica</b>.<br>Caso esteja cadastrando um RAMAL para ESTE terminal o <b>IP</b> a ser utilizado �: <b>".$this->getClientIp()."</b>.";
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
            require_once _MODULEDIR_ . "Cadastro/View/cad_ip_ramal_central/cadastrar.php"; 
        }

    }

    private function getClientIp() {

        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if(getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if(getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if(getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if(getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if(getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';

        return $ipaddress;

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

        //Inicia a transa��o
        $this->dao->begin();

        //Grava��o
        $gravacao = null;

        if ((int)$dados->oid > 0) {

            //verifica duplicidade
            $registroDuplicado = $this->dao->verificaDuplicidade($dados,2);

            if($registroDuplicado){
                //Seta a mensagem de alerta
                $this->view->mensagemAlerta = self::MENSAGEM_ALERTA_DUPLICIDADE;
            }else{
                //Efetua a grava��o do registro
                $gravacao = $this->dao->atualizar($dados);
                //Seta a mensagem de atualiza��o
                $this->view->mensagemSucesso = self::MENSAGEM_SUCESSO_ATUALIZAR;
            }

        } else {

            //verifica duplicidade
            $registroDuplicado = $this->dao->verificaDuplicidade($dados,1);

            if($registroDuplicado){
                //Seta a mensagem de duplicidade
                $this->view->mensagemAlerta = self::MENSAGEM_ALERTA_DUPLICIDADE;
            }else{
                //Efetua a inser��o do registro
                $gravacao = $this->dao->inserir($dados);

                //Seta a mensagem de sucesso
                $this->view->mensagemSucesso = self::MENSAGEM_SUCESSO_INCLUIR;
            }
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

        //Verifica os campos obrigat�rios
        if (!isset($dados->ripramal) || trim($dados->ripramal) == '') {
            $camposDestaques[] = array(
                'campo' => 'ripramal'
            );
        }
        if (!isset($dados->ripip) || trim($dados->ripip) == '') {
            $camposDestaques[] = array(
                'campo' => 'ripip'
            );
        }
        if (!isset($dados->ripdescricao) || trim($dados->ripdescricao) == '') {
            $camposDestaques[] = array(
                'campo' => 'ripdescricao'
            );
        }
        if (!empty($camposDestaques)) {
            $this->view->dados = $camposDestaques;
            throw new Exception(self::MENSAGEM_ALERTA_CAMPOS_OBRIGATORIOS);
            return false;
        }

        // verifica se ip � v�lido
        if (!filter_var(trim($dados->ripip), FILTER_VALIDATE_IP)) {
            $camposDestaques[] = array(
                'campo' => 'ripip' 
            );
            $this->view->dados = $camposDestaques;
            throw new Exception(self::MENSAGEM_IP_INVALIDO);
            return false;
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
            if (isset($parametros->oid) && intval($parametros->oid) > 0) {
                //Realiza o CAST do parametro
                $parametros->oid = (int) $parametros->oid;

                //Pesquisa o registro para edi��o
                $dados = $this->dao->pesquisarPorID($parametros->oid);

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
     * Executa a exclus�o de registro.
     * @return void
     */
    public function excluir() {

        $retorno = "OK";

        try {

            //Retorna os parametros
            $parametros = $this->tratarParametros();

            //Verifica se foi informado o id
            if (!isset($parametros->oid) || trim($parametros->oid) == '') {
                throw new ErrorException(self::MENSAGEM_ERRO_PROCESSAMENTO);
            }

            //Inicia a transa��o
            $this->dao->begin();

            //Realiza o CAST do parametro
            $parametros->oid = (int) $parametros->oid;

            //Remove o registro
            $confirmacao = $this->dao->excluir($parametros);

            if (!$confirmacao) {
                $retorno = "ERRO";
            }else{
                //Comita a transa��o
                $this->dao->commit();
            }

        } catch (ErrorException $e) {

            //Rollback em caso de erro
            $this->dao->rollback();
            $retorno = "ERRO";

        } catch (Exception $e) {

            //Rollback em caso de erro
            $this->dao->rollback();
            $retorno = "ERRO";

        }

        echo $retorno;
        exit;

    }

    /**
     * Remove acentos da string
     * @param  string $str
     * @return string
     */
    public function removerAcentos($str){
         
        $busca     = array("�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�", "'", '"', "%");
        $substitui = array("a","a","a","a","a","e","e","e","e","i","i","i","i","o","o","o","o","o","u","u","u","u","c", "\'" , '\"', "\\\%");
         
        $str       = str_replace($busca,$substitui,$str);
         
        $busca     = array("�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�", "<", ">" );
        $substitui = array("A","A","A","A","A","E","E","E","E","I","I","I","I","O","O","O","O","O","U","U","U","U","C", ""  ,"" , "" , "");
         
        $str       = str_replace($busca,$substitui,$str);
        return $str;
    }

}

