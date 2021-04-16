<?php
require_once _MODULEDIR_.'Cadastro/Action/SendLayoutEmails.php';
/**
 * Classe CadCompatibilidadeAcessorioVeiculo.
 * Camada de regra de neg�cio.
 *
 * @package  Cadastro
 * @author   MARCELLO BORRMANN <marcello.b.ext@sascar.com.br>
 *
 */
class CadCompatibilidadeAcessorioVeiculo {

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

    /**
     * M�todo construtor.
     * @param $dao Objeto DAO da classe
     */
    public function __construct($dao = null) {

        $this->dao                   = is_object($dao) ? $this->dao = $dao : NULL;
        $this->view                  = new stdClass();
        $this->view->mensagemErro    = '';
        $this->view->mensagemAlerta  = '';
        $this->view->mensagemSucesso = '';
        $this->view->dados           = null;
        $this->view->parametros      = null;
        $this->view->status          = false;
        $this->usuarioLogado         = isset($_SESSION['usuario']['oid']) ? $_SESSION['usuario']['oid'] : '';
        $this->SendLayoutEmails      = new SendLayoutEmails ();

        //Se nao tiver nada na sessao assume usuario AUTOMATICO (para CRON e WebService)
        $this->usuarioLogado         = (empty($this->usuarioLogado)) ? 2750 : intval($this->usuarioLogado);
    }

    /**
     * Repons�vel tamb�m por realizar a pesquisa invocando o m�todo privado
     * @return void
     */
    public function index() {

        try {
            $this->view->parametros = $this->tratarParametros();

            //Inicializa os dados
            $this->inicializarParametros();

            // Popula combos do formulario
            $this->popularFiltrosPesquisa();

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
        require_once _MODULEDIR_ . "Cadastro/View/cad_compatibilidade_acessorio_veiculo/index.php";
    }


	/**
	 * Popula combos do formulario.
     * @param
     * @throws 
	 * @return [type] [description]
	 */
	private function popularFiltrosPesquisa() {
		
		$this->view->marcaList 		= $this->dao->getMarcaList();
		$this->view->modeloCBList 	= $this->dao->getModeloCBList();
	}
	
	/**
	 * Retorna modelos de ve�culo de acordo com a marca.
     * @param
     * @throws 
	 * @return array
	 */
	public function buscarModelos() {
			
		$mcaoid 	= $_POST['mcaoid'];
		$modeloList	= $this->dao->getModeloList($mcaoid);
	  
		$retorno		= array(
				'erro'		=> false,
				'codigo'	=> 0,
				'retorno'	=> $modeloList
		);
 
		echo  json_encode($retorno) ;
		
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

        //Verifica se os parametro existem, sen�o iniciliza 
		$this->view->parametros->cavoid 			= isset($this->view->parametros->cavoid) && trim($this->view->parametros->cavoid) != "" ? trim($this->view->parametros->cavoid) : NULL ; 
		$this->view->parametros->cavmcaoid 			= isset($this->view->parametros->cavmcaoid) && trim($this->view->parametros->cavmcaoid) != "" ? trim($this->view->parametros->cavmcaoid) : NULL ; 
		$this->view->parametros->cavmlooid 			= isset($this->view->parametros->cavmlooid) && trim($this->view->parametros->cavmlooid) != "" ? trim($this->view->parametros->cavmlooid) : NULL ; 
		$this->view->parametros->cavano 			= isset($this->view->parametros->cavano) && trim($this->view->parametros->cavano) != "" ? trim($this->view->parametros->cavano) : NULL ; 
		$this->view->parametros->cavcbmooid 		= isset($this->view->parametros->cavcbmooid) && trim($this->view->parametros->cavcbmooid) != "" ? trim($this->view->parametros->cavcbmooid) : NULL ; 
		$this->view->parametros->cavstatus 			= isset($this->view->parametros->cavstatus) && trim($this->view->parametros->cavstatus) != "" ? trim($this->view->parametros->cavstatus) : NULL ; 
		
		$this->view->parametros->cavmcaoid_busca	= isset($this->view->parametros->cavmcaoid_busca) && trim($this->view->parametros->cavmcaoid_busca) != "" ? trim($this->view->parametros->cavmcaoid_busca) : NULL ; 
		$this->view->parametros->cavmlooid_busca	= isset($this->view->parametros->cavmlooid_busca) && trim($this->view->parametros->cavmlooid_busca) != "" ? trim($this->view->parametros->cavmlooid_busca) : NULL ; 
		$this->view->parametros->cavano_busca		= isset($this->view->parametros->cavano_busca) && trim($this->view->parametros->cavano_busca) != "" ? trim($this->view->parametros->cavano_busca) : NULL ; 
		$this->view->parametros->cavcbmooid_busca	= isset($this->view->parametros->cavcbmooid_busca) && trim($this->view->parametros->cavcbmooid_busca) != "" ? trim($this->view->parametros->cavcbmooid_busca) : NULL ; 
		$this->view->parametros->cavstatus_busca	= isset($this->view->parametros->cavstatus_busca) && trim($this->view->parametros->cavstatus_busca) != "" ? trim($this->view->parametros->cavstatus_busca) : NULL ;

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

            // Popula combos do formulario
            $this->popularFiltrosPesquisa();

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
            require_once _MODULEDIR_ . "Cadastro/View/cad_compatibilidade_acessorio_veiculo/cadastrar.php";
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
            if (isset($parametros->cavoid) && intval($parametros->cavoid) > 0) {
                //Realiza o CAST do parametro
                $parametros->cavoid = (int) $parametros->cavoid;

                //Pesquisa o registro para edi��o
                $dados = $this->dao->pesquisarPorID($parametros->cavoid);
                
                //Limpa o POST
                unset($_POST);

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

        //Inicia a transa��o
        $this->dao->begin();

        //Grava��o
        $gravacao = null;
        
        //Atribui usu�rio
        $dados->usuoid = $this->usuarioLogado;

        if ($dados->cavoid > 0) {
            // Verifica se est� duplicando registro
        	$this->dao->pesquisarDuplicidade($dados);
        	
            // Efetua a grava��o do registro
            $gravacao = $this->dao->atualizar($dados);

            if ($gravacao) {
                // Verificando se o status foi alterado para responder a solicita��o de homologa��o
                if ($dados->cavstatus != 'NULL'){
                    $result = $this->verificaNotificacaoHomologacao($dados);
                }
            }

            // Seta a mensagem de atualiza��o
            $this->view->mensagemSucesso = self::MENSAGEM_SUCESSO_ATUALIZAR;

        } else {
            // Verifica se est� duplicando registro
        	$this->dao->pesquisarDuplicidade($dados);
            
            // Efetua a inser��o do registro
            $gravacao = $this->dao->inserir($dados);
            
            // Seta a mensagem de inser��o
            $this->view->mensagemSucesso = self::MENSAGEM_SUCESSO_INCLUIR;
        }

        //Comita a transa��o
        $this->dao->commit();

        return $gravacao;
    }

    /**
    * Verificando se existe notifica��o para ser respondida
    **/
    public function verificaNotificacaoHomologacao($dados = null)
    {
        // Declara��o das vari�veis
        global $conn;
        $usuarios     = array(); // array que guarda o c�digo dos usu�rios que devem ser notificados
        $executivos   = array(); // array que guarda o c�digo dos executivos que devem ser notificados
        $emails       = array(); // array que guarda os e-mails que devem ser avisados
        $oids         = array(); // array que guarda os oids das notifica��es

        try{

            # Verificando se h� alguma notifica��o de homologa��o para ser respondida
            $notificacoes = $this->dao->verificaNotificacaoHomologacao($dados);
            if ($notificacoes) {
                
                // Tratando o que ser� apresentado no corpo do e-mail
                $status = ($dados->cavstatus == 'TRUE' ? 'COMPAT�VEL' : 'INCOMPAT�VEL'); 
                // Pegando o c�digo dos usu�rios e executivos
                foreach ($notificacoes as $notificacao) {
                    
                    if ($notificacao['cahnexecutivo'] == ""){
                    $usuarios[] = $notificacao['cahnusuoid_cadastro'];
                    }else{
                    $executivos[] = $notificacao['cahnexecutivo'];
                    }
                    
                    $cahnoids[] = $notificacao['cahnoid'];
                }
                // Limpando as posi��es vazias
                $usuarios = array_filter($usuarios);
                $executivos = array_filter($executivos);
                $cahnoids = array_filter($cahnoids);

                // Tratando os c�digos para buscar o e-mail
                $usuarios = implode(",", $usuarios);
                $executivos = implode(",", $executivos);
                $cahnoids = implode(",", $cahnoids);

                $where = "1=1 AND (";

                if (!empty($usuarios)){
                    $where .= " cd_usuario IN ({$usuarios}) ";
                }
                if (!empty($usuarios) && !empty($executivos)){
                    $where .= " OR ";
                }
                if (!empty($executivos)){
                    $where .= " funoid IN ({$executivos})";
                }
                $where .= ")";

                // Buscando os e-mails
                $ssql ="SELECT cd_usuario, nm_usuario, usuemail
                        FROM usuarios 
                        LEFT JOIN funcionario ON funoid = usufunoid
                        LEFT JOIN perfil_rh ON prhoid = funcargo
                        WHERE {$where} 
                        AND funexclusao IS NULL 
                        AND fundemissao IS NULL 
                        AND dt_exclusao IS NULL";
                
                $res = pg_query($conn, $ssql);
                if (pg_num_rows($res) > 0) {
                    $retorno = pg_fetch_all($res);
                }
                // tratando o array dos emails
                foreach ($retorno as $email) {
                    $emails[] = array($email['usuemail'], $email['nm_usuario']);
                }

                # Buscando o modelo do ve�culo
                $sql = "SELECT mlomodelo FROM modelo WHERE mlooid = ". $dados->cavmlooid .";";
                $res = pg_query($conn,$sql);
                if (pg_num_rows($res) > 0) {
                    $modeloVei = pg_fetch_result($res, 0, 0);
                }
                
                # Enviando e-mail com o resultado da homologa��o
                $resEnvioEmail = $this->enviaEmailResultadoHomologacao($emails, $modeloVei, $dados->cavano, $status);
                        
                if ($resEnvioEmail->sucesso){

                    # DELETANDO AS NOTIFICA��ES QUE FORAM RESPONDIDAS
                    $sqlUpdateData = "DELETE FROM compatibilidade_acessorio_historico_notificacao 
                                    WHERE cahnoid IN ({$cahnoids})";
                    pg_query($conn, $sqlUpdateData);

                    return (object)array("sucesso" => true, "mensagem" => "");

                }else{
                    throw new Exception($resEnvioEmail->mensagem);
                }

            }
        } catch(Exception $ex){
            return (object)array("sucesso" => false, "mensagem" => $ex->getMessage());
        }
    }

    /**
    * Envio e-mail Resultado da homologa��o
    * @param String $modelo
    * @param int $ano
    * @param int $cavoid
    **/
    public function enviaEmailResultadoHomologacao($destinatarios, $modelo, $ano, $status)
    {
        global $conn;

        try{

            // Buscando o layout do e-mail
            $SendLayoutEmails = new SendLayoutEmails();

            //retorna o codigo do titulo e da funcionalidade de acordo com o nome do titulo passado
            $dadosLayout = $SendLayoutEmails->getTituloFuncionalidade('Resultado de Homologa��o de ve�culo');

            if($dadosLayout == null || empty($dadosLayout) || !isset($dadosLayout) || count($dadosLayout) == 0 ){
                throw new Exception("Layout de e-mail n�o encontrado");
            }  

            $codigoLayout[] = $SendLayoutEmails->buscaLayoutEmail(array(
                    'seeseefoid' => $dadosLayout[0]['funcionalidade_id'],
                    'seeseetoid' => $dadosLayout[0]['titulo_id']
                    )
            );

            foreach ($codigoLayout as $chave => $valor) {
                //busca o layout de acordo com o ID do codigo do layout
                $layouts[] = $SendLayoutEmails->getLayoutEmailPorId ($valor['seeoid']);
            }

            # Substituindo as vari�veis do texto pela informa��o correta
            $subject = str_replace(array('[MODELO]', '[ANO]'), array($modelo, $ano), $layouts[0]['seecabecalho']);
            $body = str_replace(array('[MODELO]', '[ANO]', '[STATUS]'), array($modelo, $ano, $status), $layouts[0]['seecorpo']);

            $result = $this->sendMail(array(
                                    'subject' => $subject, 
                                    'body' => $body, 
                                    'to'=> $destinatarios
                                    )
                                );

            if ($result){
                return (object)array("sucesso" => true, "mensagem" => "");
            }else{
                throw new Exception("N�o foi poss�vel enviar o e-mail informando sobre a homologa��o.");
                
            }

        }catch(Exception $ex){
            return (object)array("sucesso" => false, "mensagem" => $ex->getMessage());
        }

    }


    /**
	* M�todo para enviar emails
	* @param array $emails array(email, nome)
	* @param String $subject assunto do e-mail
	* @param String $corpo corpo do e-mail
	**/
	public function sendMail($params)
	{
        try{
            $mail = new PHPMailer();
            
            $mail->isSMTP();
            $mail->From = "sascar@sascar.com.br";
            $mail->FromName = "sistema@sascar.com.br";
            $mail->Subject = $params['subject'];
            $mail->MsgHTML($params['body']);
            $mail->ClearAllRecipients();

            if ($_SESSION['servidor_teste'] == 1) {                
                foreach ($params['to'] as $to) {
                    $mail->AddAddress("teste_desenv@sascar.com.br", $email[1]);
                }
            } else {
                foreach ($params['to'] as $to) {
                    $mail->AddAddress($to[0], $to[1]);
                }
            }

            # Verifica se foi enviado
            return $mail->send();
            
        }catch(Exception $ex) {
            return $ex->getMessage();
        }

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
        if (!isset($dados->cavmcaoid) || trim($dados->cavmcaoid) == '') {
            $camposDestaques[] = array(
                'campo' => 'cavmcaoid'
            );
        }
        if (!isset($dados->cavmlooid) || trim($dados->cavmlooid) == '') {
            $camposDestaques[] = array(
                'campo' => 'cavmlooid'
            );
        }
        if (!isset($dados->cavano) || trim($dados->cavano) == '') {
            $camposDestaques[] = array(
                'campo' => 'cavano'
            );
        }
        if (!isset($dados->cavcbmooid) || trim($dados->cavcbmooid) == '') {
            $camposDestaques[] = array(
                'campo' => 'cavcbmooid'
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
            if (!isset($parametros->cavoid) || trim($parametros->cavoid) == '') {
                throw new ErrorException(self::MENSAGEM_ERRO_PROCESSAMENTO);
            }

            //Inicia a transa��o
            $this->dao->begin();

            //Realiza o CAST do parametro
            $parametros->cavoid = (int) $parametros->cavoid;
        
	        //Atribui usu�rio
	        $parametros->usuoid = $this->usuarioLogado;

            //Remove o registro
            $confirmacao = $this->dao->excluir($parametros);

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

