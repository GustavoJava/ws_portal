<?php
/**
 * Classe que mant�m os dados da forma de pagamento de d�bito autom�tico
 * 
 * @file PrnDa.php
 * @author marcioferreira
 * @version 06/03/2013 16:56:58
 * @since 06/03/2013 16:56:58
 * @package SASCAR PrnDa.php 
 */

// require para persist�ncia de dados - classe DAO
require_once _MODULEDIR_ . 'Principal/DAO/PrnDaDO.php';

//classe para gerenciar dados de cobran�a
require_once _MODULEDIR_ . 'Principal/Action/PrnDadosCobranca.php';

//classe para gerenciar dados do cliente
require_once _MODULEDIR_ . 'Principal/Action/PrnCliente.php';

//classe para envio de email
require_once _SITEDIR_ . 'lib/phpMailer/class.phpmailer.php';

//classe para gerenciar forma de cobran�a
require_once _MODULEDIR_ . 'Principal/Action/PrnManutencaoFormaCobrancaCliente.php';


class PrnDa{
	
	/**
	 * Atributo para acesso a persist�ncia de dados
	 */
	private $dao;
	private $conn;
	
	/**
	 * Construtor
	 * @autor M�rcio Sampaio Ferreira
	 * @email marcioferreira@brq.com
	 */
	public function __construct(){
	
		global $conn;
	
		$this->conn = $conn;
	
		// Objeto  - DAO
		$this->dao = new PrnDaDO($conn);
	}
	
	/**
	 * Insere forma de cobran�a de d�bito autom�tico
	 *
	 * @autor M�rcio Sampaio Ferreira
	 * @email marcioferreira@brq.com
	 */
	public function insereDebitoAutomatico($insereDa, $dadosConfirma){
		
		try {
			//inst�ncia da classe de clientes
			$prnCliente = new PrnCliente();

			//insere o hist�rico de d�bito autom�tico passando os dados
			if(!$this->inserirHistoricoDebAutomatico($insereDa, $dadosConfirma)){
				throw new Exception('ERRO: Falha ao inserir historico de debito automatico.');
			}
			
			//insere hist�rico do cliente
			if(!$prnCliente->inserirHistoricoCliente($insereDa, $dadosConfirma)){
				throw new Exception('ERRO: Falha ao inserir historico do cliente.');
			}
		
			// Insere hist�rico para todos os contratos ativos e que n�o estejam excluidos do cliente
			if(!$this->inserirHistoricoContrato($insereDa, $dadosConfirma)){
				throw new Exception('ERRO: Falha ao inserir historico do contrato.');
			}

			return true;
			
		}catch (Exception $e) {
           return $e->getMessage();
        }
	}
	
	/**
	 * Remove forma de cobran�a de d�bito autom�tico
	 * 
	 * @autor M�rcio Sampaio Ferreira
	 * @email marcioferreira@brq.com
	 */
	public function removerDebitoAutomatico($removeDa, $dadosConfirma){
		
		try {
		
			//inst�ncia da classe de clientes
			$prnCliente = new PrnCliente();
			
			//insere o hist�rico de d�bito autom�tico passando os dados
			$insereHisto = $this->inserirHistoricoDebAutomatico($removeDa, $dadosConfirma);
			
			if(!empty($insereHisto)){
				throw new Exception($insereHisto);
			}
				
			//insere hist�rico do cliente
			if(!$prnCliente->inserirHistoricoCliente($removeDa, $dadosConfirma)){
				throw new Exception('ERRO: Falha ao inserir historico do cliente.');
			}
			
			// Insere hist�rico para todos os contratos ativos e que n�o estejam excluidos do cliente
			if(!$this->inserirHistoricoContrato($removeDa, $dadosConfirma)){
				throw new Exception('ERRO: Falha ao inserir historico do contrato.');
			}
			
			return true;
			
		} catch (Exception $e) {
            return $e->getMessage();
        }
	}	
	
	
	/**
	 * Altera dados do d�bito autom�tico quando mant�m o d�bito autom�tico,
	 * alterando banco, ag�ncia e conta
	 *
	 * @autor M�rcio Sampaio Ferreira
	 * @email marcioferreira@brq.com
	 */
	public function alterarDebitoAutomatico($alteraDa, $dadosConfirma){
		
		try {
			
			//inst�ncia da classe de clientes
			$prnCliente = new PrnCliente();

			//insere o hist�rico de d�bito autom�tico passando os dados
			if(!$this->inserirHistoricoDebAutomatico($alteraDa, $dadosConfirma)){
				throw new Exception('ERRO: Falha ao inserir historico de debito automatico.');
			}
			
			//insere hist�rico do cliente
			if(!$prnCliente->inserirHistoricoCliente($alteraDa, $dadosConfirma)){
				throw new Exception('ERRO: Falha ao inserir historico do cliente.');
			}
				
			// Insere hist�rico para todos os contratos ativos e que n�o estejam excluidos do cliente
			if(!$this->inserirHistoricoContrato($alteraDa, $dadosConfirma)){
				throw new Exception('ERRO: Falha ao inserir historico do contrato.');
			}
				
			return true;

		} catch (Exception $e) {
            return $e->getMessage();
        }
	}
	
	
	/**
	 * Insere registro no hist�rico de d�bito autom�tico
	 *
	 * @autor Renato Teixeira Bueno
	 * @email renato.bueno@meta.com.br
	 */
	private function inserirHistoricoDebAutomatico($dadosHistorico, $dadosConfirma) {

		try{
			
			// Se o tipo de opera��o for I - Inclus�o ou A - Altera��o
			// Ent�o for�a o motivo como nulo
			if($dadosHistorico->tipo_operacao === 'E'){
				if(empty($dadosConfirma->motivoAlteraDebito)){
					throw new Exception('O motivo de exclusao de debito deve ser informado.');
				}
				$historicoDa->motivoAlteraDebito = $dadosConfirma->motivoAlteraDebito;
			
			}else{
				$historicoDa->motivoAlteraDebito = 'null';
			}

			// Se o tipo da opera��o n�o for I -Inclus�o
			// Ent�o buscamos os dados banc�rios anteriores para inserir no hist�rico
			// - Banco anterior
			// - Agencia anterior
			// - Conta corrente anterior
			if ($dadosHistorico->tipo_operacao != 'I') {
				$historicoDa->banco_anterior          = $dadosHistorico->banco_anterior;
				$historicoDa->agencia_anterior        = $dadosHistorico->agencia_anterior;
				$historicoDa->conta_corrente_anterior = $dadosHistorico->conta_corrente_anterior;
			}

			// Se o tipo da opera��o for E - Exclus�o
			// Ent�o for�amos os dados banc�rios como nulos para inserir no hist�rico
			$historicoDa->banco_posterior          = ($dadosHistorico->tipo_operacao == 'E') ? '' : $dadosHistorico->banco_posterior;
			$historicoDa->agencia_posterior        = ($dadosHistorico->tipo_operacao == 'E') ? '' : $dadosHistorico->agencia_posterior;
			$historicoDa->conta_corrente_posterior = ($dadosHistorico->tipo_operacao == 'E') ? '' : $dadosHistorico->conta_corrente_posterior;

			$historicoDa->forma_cobranca_anterior  = $dadosHistorico->forma_cobranca_anterior;
			$historicoDa->tipo_operacao            = $dadosHistorico->tipo_operacao;
			
			// M�todo da classe DAO que insere o hist�rico de d�bito autom�tico
			$retornoHistorico = $this->dao->inserirHistoricoDebAutomatico($historicoDa, $dadosConfirma);
			
			if(!$retornoHistorico){
				throw new Exception('Falha ao inserir historico de debito automatico.');
			}
			
			return true;

		} catch (Exception $e) {
			return $e->getMessage();
		}
	}
	
	
	/**
	 * Insere o hist�rico do contrato atrav�s da function do banco de dados historico_termo_i
	 * para todos os contratos ativos e n�o estejam excluidos do cliente
	 *
	 * @autor Renato Teixeira Bueno
	 * @email renato.bueno@meta.com.br
	 */
	private function inserirHistoricoContrato($dadosHistorico, $dadosConfirma) {

		try{

			//inst�ncia da classe de dados de cobran�a
			$prnDadosCobranca = new PrnDadosCobranca();
			//Busca dados da forma de cobranca posteriores
			$dados_posteriores = $prnDadosCobranca->getDadosFormaCobranca($dadosConfirma->forma_cobranca_posterior);
			$descricao_forma_cobranca_posterior = $dados_posteriores->descricao_forma_cobranca;

			if($dadosHistorico->tipo_operacao == 'E') {
				$dadosHistorico->nome_banco_posterior = "";
				$dadosHistorico->agencia_posterior = "";
				$dadosHistorico->conta_corrente_posterior = "";
			}

			// Texto para inserir no historico do contrato de: para:
			$texto_alteracao = "Altera��o: forma de cobran�a de: $dadosHistorico->descricao_forma_cobranca_anterior para: $descricao_forma_cobranca_posterior ";
			$texto_alteracao .= "banco de: $dadosHistorico->nome_banco_anterior para: $dadosHistorico->nome_banco_posterior ";
			$texto_alteracao .= "ag�ncia de: $dadosHistorico->agencia_anterior para: $dadosHistorico->agencia_posterior ";
			$texto_alteracao .= "conta corrente de: $dadosHistorico->conta_corrente_anterior para: $dadosHistorico->conta_corrente_posterior ";

			//Valida��o para o id do cliente
			if(!empty($dadosConfirma->id_cliente)){

				// Busca os contratos ativos do cliente para atualizar o historico de todos
				$contratos_ativos = $this->dao->getContratosAtivosByCliente($dadosConfirma->id_cliente);

				if(count($contratos_ativos) > 0){

					foreach($contratos_ativos as $contrato_ativo){
							
						$paramHistorico->numero_contrato = $contrato_ativo['connumero'];
						$paramHistorico->id_usuario      = $dadosConfirma->id_usuario;
						$paramHistorico->texto_alteracao = $texto_alteracao;
						$paramHistorico->protocolo       = $dadosConfirma->protocolo;
							
						if(!$this->dao->inserirHistoricoContrato($paramHistorico)){
							throw new Exception('ERRO: Falha ao inserir historico do contrato.');
						}
					}
				}
			}

		} catch (Exception $e) {
			return $e->getMessage();
		}
	}
	
	/**
	 * Envia email para o cliente com os termo de Inclus�o ou Exclus�o do d�bito autom�tico
	 *
	 * @autor Renato Teixeira Bueno
	 * @email renato.bueno@meta.com.br
	 */
	public function enviarEmail($dadosConfirma , $dados_cobranca_anterior, $tipo_operacao) {

		$enviar_email = $this->prepararEmail($dadosConfirma ,$dados_cobranca_anterior, $tipo_operacao);

		if($enviar_email['enviar']){

			// regras para envio durante desenvolvimento e testes
			if(strstr($_SERVER['HTTP_HOST'], 'homologacao') ||
					strstr($_SERVER['HTTP_HOST'], 'desenvolvimento') ||
					strstr($_SERVER['HTTP_HOST'], 'teste') ||
					$_SERVER["SERVER_ADDR"] == '172.16.19.20' ||
					$_SERVER["SERVER_ADDR"] == '172.16.2.57' ){
					
				$assuntoEmail = '[TESTE] '.$enviar_email['mail']['subject'];
				$destinatario = "gfranca@sascar.com.br";
				
				//para n�o exibir erros de envio de e-mail em desenvolvimento, j� que a altera��o da forma n�o deve ser interrompida
				ini_set('display_errors', 0);
					
				//local
			}elseif($_SESSION["servidor_teste"] == 1) {
					
				$assuntoEmail = '[TESTE] '.$enviar_email['mail']['subject'];
				$destinatario = "teste_desenv@sascar.com.br";
					
			}else{
				$destinatario = $enviar_email['mail']['add_address'];
				$assuntoEmail = $enviar_email['mail']['subject'];
			}
				
			// Configura��es dos dados para o envio de email
			$mail = new PHPMailer();
			$mail->isSmtp();
			$mail->From = "sascar@sascar.com.br";
			$mail->FromName = "Sascar";
			$mail->Subject = $assuntoEmail;
			$mail->MsgHTML($enviar_email['mail']['msg']);
			$mail->ClearAllRecipients();
			$mail->AddAddress($destinatario);
			$mail->Send();

			return true;
		}
		
		return false;
	}
	
	/**
	 * Prepara o email com todas as regras de neg�cio
	* Se o tipo de opera��o for I- Inclus�o ou E - Exclusao
	* E exista pelo menos um contrato ativo relacionado ao cliente
	* Ent�o envia email com o termo de acordo com a opera��o ( Inclus�o uo Exclus�o).
	*
	* @autor Renato Teixeira Bueno
	* @email renato.bueno@meta.com.br
	*/
	private function prepararEmail($dadosConfirma, $dados_cobranca_anterior, $tipo_operacao) {
			
		$prnCliente = new PrnCliente();
		
		$prnDadosCobranca = new PrnDadosCobranca();
		
		$prnManutencaoFormaCobrancaCliente = new PrnManutencaoFormaCobrancaCliente();

		$email_destinatario = $prnCliente->getEmailCliente($dadosConfirma->id_cliente);
			
		// Envia email apenas se o cliente tiver um email cadastrado nos campos
		// :cliemail ou :cliemail_nfe
		if (!empty($email_destinatario->email_cliente)){

			$arrEmail = array();

			//  Titulo dos termos para efetuar a busca do texto de envio
			$inclusao = 'Termo de INCLUS�O de D�bito Autom�tico';
			$exclusao = 'Termo de EXCLUS�O de D�bito Autom�tico';

			// Envia email apenas quando:
			// O tipo da opera��o for I- Inclusao ou E - Exclusao
			if (in_array($tipo_operacao, array('I', 'E'))) {

				$arrEmail['subject'] = ($tipo_operacao == 'I') ? "Inclus�o de d�bito autom�tico" : "Exclus�o de d�bito autom�tico";
				
				//novos dados banc�rios
				$nome_banco_posterior = $prnManutencaoFormaCobrancaCliente->getNomeBanco($dadosConfirma->debitoBanco, $dadosConfirma->forma_cobranca_posterior );
				
				if($tipo_operacao == 'I'){
					
					$mensagem = $this->dao->getModeloTexto($inclusao);
					
					$dados_bancarios .= "Banco:   $nome_banco_posterior <br />
										 Ag�ncia: $dadosConfirma->debitoAgencia <br />
										 Conta:   $dadosConfirma->debitoConta ";

				}else{
					
					$mensagem = $this->dao->getModeloTexto($exclusao);
					
					$dados_bancarios .= "<br /><br />
										Banco:   $dados_cobranca_anterior->nome_banco <br />
										Ag�ncia: $dados_cobranca_anterior->agencia <br />
										Conta:   $dados_cobranca_anterior->conta_corrente ";
				}

				$arrEmail['msg'] = $mensagem->texto_mensagem.$dados_bancarios;
			}

			$arrEmail['add_address'] = $email_destinatario->email_cliente;

			return array('enviar' => true, 'mail' => $arrEmail);
		}

		return array('enviar' => false);
	}

	
	/**
	 * M�todo que busca e popula o combo de motivos de desist�ncia de d�bito autom�tico
	 *
	 * @autor Willian Ouchi
	 */
	public function getDadosMotivos() {

		return $this->dao->getDadosMotivos();

	}
	
}
//fim arquivo
?>