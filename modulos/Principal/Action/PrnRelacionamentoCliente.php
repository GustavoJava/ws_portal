<?php

/**
 *
 * Classe referente as a��es de relacionamento com o cliente.
 *
 * @author 	Leandro Alves Ivanaga
 * @email   leandroivanaga@brq.com
 * @version 17/10/2013
 * @since   17/10/2013
 * 
 * @STI 82907
 */

require_once _MODULEDIR_.'Principal/DAO/PrnRelacionamentoClienteDAO.php';
require_once _MODULEDIR_.'Principal/Action/PrnParametrosSiggo.class.php';
require_once _MODULEDIR_.'Principal/Action/ServicoEnvioEmail.php';
require_once _MODULEDIR_.'Cadastro/Action/SendLayoutEmails.php';


class PrnRelacionamentoCliente 
{
	private $dao;
	private $conn;
	private $servicoEmail;
	private $parametrosSiggo;
	
	/**
	 * M�todo construtor
	 */
	public function PrnRelacionamentoCliente() {
		
		global $conn;		
		$this->conn = $conn;
		$this->dao = new PrnRelacionamentoClienteDAO($conn);
		
	}
	
	/**
	 * M�todo de a��o padr�o
	 * @return boolean
	 */
	public function index() {
		return true;
	}	
	
	/**
	 * Fun��o: Se pagamento efetuado com sucesso enviar email de confirma��o para o cliente sobre a confirma��o de pagamento da taxa de instala��o por boleto
	 */
	public function processoEnvioEmailConfirmacaoPagamento() {
		
		try {
			$msg = "";
			
			// busca titulos pagos
			$titulosEfetuados = $this->titulosEfetuados();
			
			if (empty($titulosEfetuados) || $titulosEfetuados == false) {
				$msg = "Nenhum titulo encontrado pra enviar confirma��o ao cliente.";
				
				return $msg;
			}
			
			foreach ($titulosEfetuados AS $key => $dadosTitulo){
				// Verifica se ja foi enviado
				
				$msgHistorico = "Confirma��o de pagamento enviada com sucesso!";
				$verificaHistorico = $this->verificarHistorico($dadosTitulo['prpoid'], $msgHistorico);

				if ($verificaHistorico == false){
					
					// Busca os dados da proposta
					$dadosProposta = $this->dao->dadosProposta($dadosTitulo['prpoid']);
						
					// busca o layout a ser utilizado de acordo com a proposta
					$nome_parametro = "TITULO_LAYOUT_CONF_PAGTO";
					$layout = $this->getLayout($dadosProposta, $nome_parametro);
						
					$retornoEnvio = $this->enviarEmail($dadosProposta, $layout, $dadosTitulo["titoid"]);
						
					if ($retornoEnvio == true){
						
						$msg .= "Confirma��o enviada com sucesso para o contrato: " . $dadosTitulo["titriconoid"];
						$msg .= "<br />";
						
					} else{
						
						$msg .= "Falha ao enviar confirmacao para o contrato: " . $dadosTitulo["titriconoid"];
						$msg .= "<br />";
					}
					
					$listaContrato = array();
					
					// busca os contratos que compoe o titulo
					$listaContrato = $this->contratoTituloBoleto($dadosTitulo["titoid"]);
						
					$msgHistorico = "Confirma��o de pagamento enviada com sucesso!";
						
					// Verifica se houve erro no envio do email
					if ($retornoEmail['erro'] == 1) {
						$msgHistorico = "Confirma��o de pagamento com erro.";
					}
						
					// Salvar no hist�rico
					$retSalvaHistorico = $this->dao->salvarHistorico($listaContrato, $msgHistorico);
				}
			}
			
			if (empty($msg)){
				$msg = "Nenhum titulo encontrado pra enviar confirma��o ao cliente.";
			}
			
			return $msg;
			
		}catch (Exception $e){
			
			return "Ocorreu erro no processo cron de envio de confirmacao de pagamento taxa de instala��o.";
			
		}
	}
	
	/**
	 * 
	 * Fun��o: Se pagamento efetuado com sucesso enviar email de confirma��o para o cliente sobre a confirma��o de pagamento da taxa de instala��o
	 */
	public function enviaEmailConfirmacaoPagamento($proposta_cod = null, $contrato_num = null){
		
		$msgHistorico = "Confirma��o de pagamento enviada com sucesso!";
		$retornoHistorico = $this->verificarHistorico($proposta_cod, $msgHistorico);
		
		// j� foi enviado a confirmacao de pagamento
		if ($retornoHistorico == true){
			return true;
		}
		
		// busca os dados do titulo
		$dadosTitulo = $this->dao->tituloPagamentoEfetuado($proposta_cod, $contrato_num);
		
		// se o titulo esta pago
		if ($dadosTitulo["erro"] == 0 && $dadosTitulo["tituloEfetuado"] > 0) {
			
			// Busca os dados da proposta
			$dadosProposta = $this->dao->dadosProposta($proposta_cod);
			
			// busca o layout a ser utilizado de acordo com a proposta
			$nome_parametro = "TITULO_LAYOUT_CONF_PAGTO";
			$layout = $this->getLayout($dadosProposta, $nome_parametro);
			
			$retornoEnvio = $this->enviarEmail($dadosProposta, $layout, $dadosTitulo["tituloEfetuado"]);
			
			if ($retornoEnvio == true){
				$listaContrato = array();
				// busca os contratos que compoe o titulo
				$listaContrato = $this->contratoTitulo($dadosTitulo["tituloEfetuado"]);
				
				$msgHistorico = "Confirma��o de pagamento enviada com sucesso!";
					
				// Verifica se houve erro no envio do email
				if ($retornoEmail['erro'] == 1) {
					$msgHistorico = "Confirma��o de pagamento com erro.";
				}
					
				// Salvar no hist�rico
				// $retSalvaHistorico = $this->dao->salvarHistorico($listaContrato, $msgHistorico);
					
				// if ($retSalvaHistorico === true){
					return true;
				// }
			}
			
			return false;
		}
	}
	
	/**
	 * Fun��o: Busca os titulo que ir�o vencer a 2 dias a partir da data atual e envia um lembrete pra o cliente
	 */
	public function processoEnvioEmailLembreteVencimento() {
	
		try {
			$msg = "";
				
			// busca titulos a vencer
			$titulosAVencer = $this->titulosAVencer();
				
			if (empty($titulosAVencer) || $titulosAVencer == false) {
				$msg = "Nenhum titulo encontrado pra enviar o lembrete ao cliente.";
	
				return $msg;
			}
				
			
			foreach ($titulosAVencer AS $key => $dadosTitulo){
				// Verifica se ja foi enviado
	
				$msgHistorico = "Lembrete de vencimento enviado com sucesso!";
				$verificaHistorico = $this->verificarHistorico($dadosTitulo['prpoid'], $msgHistorico);
	
				if ($verificaHistorico == false){
					
					// Busca os dados da proposta
					$dadosProposta = $this->dao->dadosProposta($dadosTitulo['prpoid']);

					// busca o layout a ser utilizado de acordo com a proposta
					$nome_parametro = "TITULO_LAYOUT_LEMB_VENC";
					$layout = $this->getLayout($dadosProposta, $nome_parametro);
	
					$retornoEnvio = $this->enviarEmail($dadosProposta, $layout, $dadosTitulo["titoid"]);

					if ($retornoEnvio == true){
	
						$msg .= "Lembrete de vencimento enviado com sucesso para o contrato: " . $dadosTitulo["titriconoid"];
						$msg .= "<br />";
	
					} else{
	
						$msg .= "Falha ao enviar o lembrete de vencimento para o contrato: " . $dadosTitulo["titriconoid"];
						$msg .= "<br />";
					}
						
					$listaContrato = array();
						
					// busca os contratos que compoe o titulo
					$listaContrato = $this->contratoTituloBoleto($dadosTitulo["titoid"]);
	
					$msgHistorico = "Lembrete de vencimento enviado com sucesso!";
	
					// Verifica se houve erro no envio do email
					if ($retornoEmail['erro'] == 1) {
						$msgHistorico = "Lembrete de vencimento com erro.";
					}
	
					// Salvar no hist�rico
					$retSalvaHistorico = $this->dao->salvarHistorico($listaContrato, $msgHistorico);
				}
			}
				
			if (empty($msg)){
				$msg = "Nenhum titulo encontrado pra enviar confirma��o ao cliente.";
			}
				
			return $msg;
				
		}catch (Exception $e){
				
			return "Ocorreu erro no processo cron de envio de confirmacao de pagamento taxa de instala��o.";
				
		}
	}
	
	public function verificarHistorico($proposta_cod, $msgHistorico) {
		
		$retVerificaHistorico = $this->dao->verificarHistorico($proposta_cod, $msgHistorico);

		// verifica se j� tem
		if ($retVerificaHistorico == true){
			return true;
		}
		return false;
	}
	
	public function getLayout($dadosProposta, $nome_parametro) {
		/* Busca os dados do layout */
		$this->parametrosSiggo = new PrnParametrosSiggo();
			
		$paramsPesquisa = array(
				'id_tipo_proposta'		=>	$dadosProposta->tppoid_supertipo,
				'id_subtipo_proposta'	=>	$dadosProposta->prptppoid,
				'id_tipo_contrato'		=>	$dadosProposta->conno_tipo,
				'nome_parametro'		=> 	$nome_parametro
		);
			
		$retornoValor = $this->parametrosSiggo->getValorParametros($paramsPesquisa);
			
		$tituloLayout = $retornoValor['valor'];
			
		$dadosLayout = $this->dao->getTituloFuncionalidade($tituloLayout);

		/** BUSCAR LAYOUT **/
		$getEmail = array(
				'seeseefoid'		=> $dadosLayout->funcionalidade_id,
				'seeseetoid'		=> $dadosLayout->titulo_id,
		
				'supertipo'			=> $dadosProposta->tppoid_supertipo,
				'prptppoid'			=> $dadosProposta->prptppoid,
				'prptpcoid'			=> $dadosProposta->conno_tipo,
		);
			
		$this->layout = new SendLayoutEmails();
			
		$layout_id = $this->layout->buscaLayoutEmail($getEmail);
		$layout = $this->dao->getLayoutEmail($layout_id['seeoid']);
		
		return $layout;
	}
	
	
	public function contratoTitulo($titulo_cod){

		// busca os contratos que compoe o titulo
		$listaContrato = $this->dao->contratoTitulo($titulo_cod);
		
		return $listaContrato;
	}
	
	public function contratoTituloBoleto($titulo_cod){
	
		// busca os contratos que compoe o titulo
		$listaContrato = $this->dao->contratoTituloBoleto($titulo_cod);
	
		return $listaContrato;
	}
	
	
	public function enviarEmail($dadosProposta, $layout, $titulo_cod){
		// monta email e envia para o cliente
		$assunto = $layout->seecabecalho;
		$htmlEmail = $layout->seecorpo;
			
		$servidor = $layout->seesrvoid;
			
		$arquivo_anexo = null;
		$email_copia = null;
		$email_copia_oculta = null;
			
		$cliente_email = $dadosProposta->cliemail;
		$cliente_nome =	$dadosProposta->clinome;
			
		$email_teste 	= "teste_desenv@sascar.com.br";
			
		if ($_SESSION['servidor_teste'] == 1) {
			$destinatario = $email_teste;
		}
			
		$this->servicoEmail = new ServicoEnvioEmail();
		
		$retornoEmail = $this->servicoEmail->enviarEmail(
				$cliente_email,
				$assunto,
				$htmlEmail,
				$arquivo_anexo,
				$email_copia,
				$email_copia_oculta,
				$servidor,
				$email_teste
		);
			
		// Verifica se houve erro no envio do email
		if ($retornoEmail['erro'] != 1) {
			return true;
		}
		
		return false;
	}
	
	public function titulosEfetuados(){
		
		// busca todos os titulos que foram pagos no dia atual e anterior
		$titulosEfetuados = $this->dao->titulosEfetuados();

		return $titulosEfetuados;
	}
	
	public function titulosAVencer(){
	
		// busca todos os titulos que irao vencer em 2 dias
		$titulosAVencer = $this->dao->titulosAVencer();
	
		return $titulosAVencer;
	}
	
	
}
