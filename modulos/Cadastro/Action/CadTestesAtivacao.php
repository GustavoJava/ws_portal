<?php

	/**
	 * @author	Felipe F. de Souza Carvalho
	 * @email	fscarvalho@brq.com
	 * @since	22/01/2013
	 *
	 * @author	Leandro Alves Ivanaga
	 * @email	leandroivanaga@brq.com
	 * @since 	30/01/2013
	 * */

	require_once (_MODULEDIR_ . 'Cadastro/DAO/CadTestesAtivacaoDAO.php');

	class CadTestesAtivacao {

		private $dao;
        private $view;

		public function __construct() {
			$this->dao = new CadTestesAtivacaoDAO();
            $this->view = new stdClass();
		}

		public function index(){

			cabecalho();

			require_once _MODULEDIR_ . 'Cadastro/View/cad_testes_ativacao/index.php';
		}

		public function cadastro (){
			cabecalho();

            $this->view->tipo_os = $this->dao->recuperarTipoOrdemServico();

            if( isset($_POST['eptpoid']) && ! empty($_POST['eptpoid']) ) {
                $this->view->id_teste = $_POST['eptpoid'];
            }

			require_once _MODULEDIR_ . 'Cadastro/View/cad_testes_ativacao/cadastro.php';
		}

		public function listarGruposTesteCadastrados(){
			try {

				$response = $this->dao->listarGruposTesteCadastrados();

			} catch(Exception $e) {
				$response = array('error' => true, 'message' => $e->getMessage());
			}

			echo json_encode($response);
		}

		public function pesquisar(){
			try {

				//Extrai par�metros da pesquisa
				$idGrupoTeste = (isset($_POST['cb_grupo_testes']))     ? $_POST['cb_grupo_testes']     : '';
				$descTeste 	  = (isset($_POST['cmp_descricao_teste'])) ? $_POST['cmp_descricao_teste'] : '';
				$siglaTeste   = (isset($_POST['cmp_sigla_teste'])) 	   ? $_POST['cmp_sigla_teste']	   : '';

				//Limpeza e tratamento de par�metros
				$descTeste  = utf8_decode($descTeste);
				$siglaTeste = utf8_decode($siglaTeste);

				//Executa a pesquisa atrav�s do dao
				$response = $this->dao->pesquisar($idGrupoTeste, $descTeste, $siglaTeste);

			} catch(Exception $e) {
				$response = array('error' => true, 'message' => $e->getMessage());
			}

			echo json_encode($response);
		}

		/**
		 * Fun��o para salvar os dados do formul�rio de cadastro de testes
		 */
		public function cadastraNovoTeste(){

			echo $this->dao->cadastraNovoTeste();

		}

		/**
		 * Fun��o para excluir um determinado teste
		 */
		public function excluiTeste(){

			echo $this->dao->excluiTeste();

		}

		/**
		 * Fun��o que carrega os dados do teste
		 */
		public function carregaDados(){
			$dados = $this->dao->carregaDados();

			echo json_encode($dados);
		}

		/**
		 * Fun��o que atualiza os dados de um teste
		 */
		public function editarTeste(){
			echo $this->dao->editarTeste();
		}


		/**
		* Recupera lista de WS parametrizados para teste
		* para popular a combo [N�mero WS teste]
		*
		**/
		public function listarWebServicesTeste(){

			try {

				$response = $this->dao->listarWebServicesTeste();

			} catch(Exception $e) {
				$response = array('error' => true, 'message' => 'Erro ao popular a combo [N�mero WS teste]');
			}

			echo json_encode($response);
		}

	}

?>
