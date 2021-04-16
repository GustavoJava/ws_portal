<?php
/**
 * Classe respons�vel em remover o cart�o de cr�dito do cliente via Webservice da SoftExpress
 *  
 * @file RemoverCartao.class.php
 * @author marcioferreira
 * @version 27/05/2013 11:02:58
 * @since 27/05/2013 11:02:58
 * @package SASCAR RemoverCartao.class.php
 */

// Report all PHP errors
//error_reporting(E_ALL);

require_once (_MODULEDIR_ . 'eSitef/Action/IntegracaoSoftExpress.class.php');
require_once (_MODULEDIR_ . 'eSitef/DAO/RemoverCartaoDAO.php');


class RemoverCartao{
	
	private $hashCartao;
	private $integracaoSoftExpress;
	private $tentativas;
	
	// Construtor
	public function __construct() {
	
		global $conn;
		
		$this->conn = $conn;
	
		// Objeto  - DAO
		$this->dao = new RemoverCartaoDAO($conn);

		// inst�ncia da classe de Integra��o com e-Sitef
		$this->integracaoSoftExpress = new IntegracaoSoftExpress();
		
		//quantidade de tentativas para cosultar no banco os dados recebidos do POST
		$this->tentativas = 6; // cada consulta aguarda 15 segundos, m�ximo de 90 segundos   15 * 6 = 90
	}
	
	
	/**
	 * Consulta os dados do cart�o pelo id do cliente e envia os dados para o m�todo beginRemoveStoredCard
	 * do Webservice SoftExpress
	 * 
	 * @param $clioid int
	 * @return $wsRetorno array
	 */
	public function processarRemocaoCartao($clioid, $transacaoID){
		
		try{
			
			if(empty($clioid)){
				throw new Exception('O id do cliente deve ser informado.');
			}
			
			if(empty($transacaoID)){
				throw new Exception('ID da transa��o deve ser informado.');
			}
			
			$dadosTransacao = new stdClass();
			
			$dadosTransacao->cliente = $clioid;
			
			//recupera o hash do cart�o na tabela cliente_cobranca_credito
			$dadosHash = $this->dao->getDadosCartao($clioid, "");
			
			if(!empty($dadosHash)){

				$rs = pg_query($this->conn, "BEGIN;");
				
				//Inica o processo de remo��o na SE
				//retorna uma mensagem de texto simples indicando o resultado status=OK para sucesso ou	 outra mensagem indicando o erro.
				$wsRetorno = $this->integracaoSoftExpress->beginRemoveStoredCard($dadosHash->ccchash, $dadosHash->ctcoid);
				
				if($wsRetorno->status != 'OK'){
						
					//como ainda n�o existe c�digos de retorno de erros e todos os erros s�o retornados na atributo 'status'
					// ent�o, � verificado pela string, nos casos abaixo o processo n�o � interrompido
					if(strstr($wsRetorno->status , 'Card not found' ) || strstr($wsRetorno->status, 'ERROR: Card already removed' )){

						//insere um log com o erro retornado da SE
						$atualizaTransacao = $this->dao->atualizarTransacaoCartao($transacaoID, $statusTransacao = 't', $dadosTransacao, $wsRetorno->status );
						
						return true;

					}else{
							
						throw new Exception('Falha ao solicitar remo��o do cart�o de cr�dito ->.'. $wsRetorno->status .'');
					}

				}else{
						
					//fez o POST na p�gina recuperaTransacao.php
					
					//vari�veis para controlar a consulta dos dados do POST gravados no BD
					$novaPesquisa = false;
					$consultas    = 0;
					
					do{
						//se n�o encontrou os dados recebidos do POST no banco d� mais um tempo para o sistema processar o POST
						if($novaPesquisa){
							sleep(15);
						}
						
						//consulta os dados persistidos em banco recebidos via POST para confirmar o cancelamento
						$dadosParaConfirmacao = $this->dao->getDadosConfirmacaoRemocaoCartao($dadosTransacao->cliente, $dadosHash->ctcoid, $status = 'NOV');
						
						if(is_object($dadosParaConfirmacao)){
							
							//envia dados para confirmar a remo��o do cart�o
							$retornoConfirmacao = $this->confirmarRemocaoCartao($transacaoID, $dadosHash->ctcoid, $dadosParaConfirmacao);
													
							if($retornoConfirmacao == 1){
																
								return true;

							}else{
									
								throw new Exception($retornoConfirmacao);
							}
							
						}else{

							$novaPesquisa = true;
							
							if($consultas == $this->tentativas){
								
								throw new Exception('Dados para confirma��o de remo��o n�o encontrados [POST]');
							}
							
							$consultas++;
						}

					}while ($novaPesquisa && $consultas <= $this->tentativas);
					
					$rs = pg_query($this->conn, "COMMIT;");
					
					return true;
					
				}
				
			}else{
				throw new Exception('Dados do cart�o n�o foram encontrados para solicitar remo��o');
			}
			
		}catch(Exception $e){

			//atualiza a transa��o
			$atualizaTransacao = $this->dao->atualizarTransacaoCartao($transacaoID, $statusTransacao = 'f', $dadosTransacao, $e->getMessage());
			
			$rs = pg_query($this->conn, "COMMIT;");
			
			return $e->getMessage();
		}
	}
	
	
	/**
	 * 
	 * Persiste os dados retornados via POST da SE para confirmar remo��o de um cart�o solicitado antes
	 * pelo m�todo beginRemoveStoreCard
	 * 
	 * @param object $dadosRetornoPost
	 * @return boolean
	 */
	public function inserirDadosPostRemocaoCartao($dadosRetornoPost){
		
		try{
			
			if(!is_object($dadosRetornoPost)){
				throw new Exception('Dados do POST n�o pode ser vazio');
			}
			
			//se for do tipo cancelamento do cart�o, grava os dados no BD
			if(isset($dadosRetornoPost->cancelamentoCartao)){
				
				if($dadosRetornoPost->cancelamentoCartao === 'true'){
	
					//para evitar erro de banco, pois h� casos em que o cliente est� retornando com valor =  ? (interroga��o)
					if(is_numeric($dadosRetornoPost->cliente)){
	
						if($dadosRetornoPost->status === 'CON' || $dadosRetornoPost->status === 'NOV'){
							$statusTransacao = 't';
						}else{
							$statusTransacao = 'f';
						}
	
						//insere dados da SE recebidos no POST
						$retorno = $this->dao->inserirDadosPostRemocaoCartao($dadosRetornoPost, $statusTransacao);
							
						if($retorno){
							return true;
						}else{
							throw new Exception($retorno);
						}
					}
				}
			}
			
		}catch(Exception $e){
			echo $e->getMessage();
		}
	}
	
		
	/**
	 * Confirma a remo��o do cart�o ap�s o envio os dados para SoftExpress que 
	 * retorna os dados de remocao via post
	 * 
	 * @param object $dadosTransacao
	 * @throws Exception
	 * @return boolean
	 */
	
	public function confirmarRemocaoCartao($transacaoID, $ctcoid, $dadosParaConfirmacao){

		try {
			
			
			if(!is_object($dadosParaConfirmacao)){
				throw new Exception('Faltam dados para processar confirma��o de exclus�o do cart�o de cr�dito');
			}
			
			if(empty($dadosParaConfirmacao->nita)){
				throw new Exception('Nita n�o encontrado para processar confirma��o de exclus�o do cart�o de cr�dito');
			}
			
			if(empty($dadosParaConfirmacao->cliente)){
				throw new Exception('Informe o id do cliente para confirmar a remo��o do cart�o de cr�dito');
			}
			
			if(empty($dadosParaConfirmacao->hash)){
				throw new Exception('Hash do cart�o de cr�dito n�o encontrado');
			}
			
			//envia para SE a confirma��o de remo��o do cart�o 
			$confirmaRemocao = $this->integracaoSoftExpress->doRemoveStoredCard($dadosParaConfirmacao->hash, $dadosParaConfirmacao->nita);
			
			if($confirmaRemocao->status != 'OK'){
				
				//como ainda n�o existe c�digos de retorno de erros e todos os erros s�o retornados na atributo 'status'
				// ent�o, � verificado pela string, nos casos abaixo o processo n�o � interrompido
				if(strstr($confirmaRemocao->status , 'Card not found' ) || strstr($confirmaRemocao->status, 'ERROR: Card already removed' )){
				
					//atualiza a transa��o
					$atualizaTransacao = $this->dao->atualizarTransacaoCartao($transacaoID, $statusTransacao = 't', $dadosParaConfirmacao, $confirmaRemocao->status );
					
					return true;
				
				}else{
				
					throw new Exception('Falha ao confirmar remo��o do cart�o de cr�dito -> '.$confirmaRemocao->status.' ');
				}
				
			}else{
				
				//invoca o m�todo callStatus para verificar se o cart�o foi removido
				$verificaRemocao = $this->integracaoSoftExpress->callStatus($dadosParaConfirmacao->nita);
				 	
				if($verificaRemocao->status != 'OK'){
			
					throw new Exception('Falha ao consultar Status do cart�o de cr�dito -> '.$verificaRemocao->status.' ');
					
				}else{
					
					//vari�veis para controlar a consulta dos dados do POST gravados no BD
					$novaPesquisa = false;
					$consultas    = 0;
						
					do{
						//se n�o encontrou os dados recebidos do POST no banco d� mais um tempo para o sistema processar o POST
						if($novaPesquisa){
							sleep(15);
						}
					
						//consulta os dados persistidos em banco recebidos via POST para confirmar o cancelamento
						$dadosConfirmacao = $this->dao->getDadosConfirmacaoRemocaoCartao($dadosParaConfirmacao->cliente, $ctcoid, $status = 'CON');
												
						if(is_object($dadosConfirmacao)){
							
							//atualiza a transa��o
							$dadosAtualiza = new stdClass();
							//passa somente o id do cliente requerido pelo m�todo
							$dadosAtualiza->cliente = $dadosParaConfirmacao->cliente;
							
							$atualizaTransacao = $this->dao->atualizarTransacaoCartao($transacaoID, $statusTransacao = 't', $dadosAtualiza, NULL );
							
							return true;
								
						}else{
							
							$novaPesquisa = true;
								
							if($consultas == $this->tentativas){
					
								throw new Exception('Dados para retornar a confirma��o de remo��o n�o encontrados [POST]');
							}
								
							$consultas++;
						}
					
					}while ($novaPesquisa && $consultas <= $this->tentativas);
				
				}//fim do else $verificaRemocao->status != 'OK'
				
			}//fim do else $confirmaRemocao->status != 'OK'

		}catch (Exception $e){
			return  $e->getMessage();
		}
	}
	
	/**
	 * Inclui uma transa��o do tipo remo��o de cart�o de cr�dito
	 * 
	 * @param unknown $clioid
	 * @return string
	 */
	
	public function iniciarTransacaoRemocaoCartaoCredito($clioid){
		
		return $this->dao->incluirTransacaoCartao($clioid);
		
	}
	
	/**
	 * Atualiza informa��es das transa��es durante o processo de remo��o de um cart�o de cr�dito
	 * 
	 * @param int $transacaoID
	 * @param string $statusTransacao
	 * @param object $dadosTransacao
	 * @return boolean
	 */
	public function atualizarTransacaoRemocaoCartaoCredito($transacaoID, $statusTransacao, $dadosTransacao, $motivo = null){
		
		return $this->dao->atualizarTransacaoCartao($transacaoID, $statusTransacao, $dadosTransacao, $motivo);
		
	}
	
	
	
}

