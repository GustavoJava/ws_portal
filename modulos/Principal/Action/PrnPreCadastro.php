<?php

require _MODULEDIR_ . 'Principal/DAO/PrnPreCadastroDAO.php';
require_once _MODULEDIR_.'Principal/Action/PrnParametrosSiggo.class.php';

/**
 * Camada Action do Pr�-Cadastro
 * @author rafael.dias
 *
 */
class PrnPreCadastro 
{
	private $dao;
	private $conn;
	private $conn_oracle;
	private $tipo_contrato;
	
	/**
	 * M�todo construtor
	 */
	public function PrnPreCadastro() {
		
		global $conn;		
		$this->conn = $conn;
		$this->dao = new PrnPreCadastroDAO($conn);
		
		$this->tipo_contrato = (!empty($_POST['tipo_contrato'])) ? $_POST['tipo_contrato'] : "";
	}
	
	/**
	 * M�todo de a��o padr�o
	 * @return boolean
	 */
	public function index() {
		return true;
	}
	
	/**
	 * Verifica se o tipo de contrato escolhido gera ou n�o 
	 * DUM 80240
	 */
	public function verificarGeracaoOSInstalacao() {
		
		$retorno = array();
		
		try {
			
			if ($this->tipo_contrato == "") {
				throw new Exception("Tipo de contrato n�o foi informado.");
			}
			
			$tipoContrato = $this->tipoContratoParametrizacao($this->tipo_contrato);			
			$gera_os_instalacao = $tipoContrato['resultado'][0]['tcpgera_os_instalacao'];
			
			$retorno = array(
					"gera_os_instalacao"	=>	$gera_os_instalacao,
					"msg"					=>	""
			);
			
			echo json_encode($retorno);
			exit();
		} 
		catch (Exception $e) {
			
			$retorno = array(
					"gera_os_instalacao"	=>	"",
					"msg"					=>	utf8_decode($e->getMessage())
			);
			
			echo json_encode($retorno);
			exit();
		}
		
		
	}
	
	public function tipoContratoParametrizacao($tipo_contrato) {	
		try {
			
			$retorno = array(
					"erro"		=>	0,
					"msg"		=>	"",
					"resultado"	=>	$this->dao->tipoContratoParametrizacao($tipo_contrato)
			);
			
			return $retorno;
			
		}
		catch (Exception $e) {
						
			$retorno = array(
					"erro"		=>	1,
					"msg"		=>	utf8_decode($e->getMessage()),
					"resultado"	=>	0
			);

			return $retorno;
		}
	}
	
	/**
	* Recupera os tipos de proposta
	*
	* @param $filtroDtExclusao | filtra os tipos de proposta
	* @return array
	*/
	public function tipoProposta($filtroDtExclusao = false) {

		$filtro = '';

		if($filtroDtExclusao){
			$filtro = " AND tppdt_exclusao IS NULL";
		}

		try {

			$retorno = array(
					"erro"		=>	0,
					"msg"		=>	"",
					"resultado"	=>	$this->dao->tipoProposta($filtro)
			);

			return $retorno;

		}
		catch (Exception $e) {

			$retorno = array(
					"erro"		=>	1,
					"msg"		=>	utf8_decode($e->getMessage()),
					"resultado"	=>	0
			);

			return $retorno;
		}
	}
	
	public function subProposta($proposta) {	
		try {
			
			if (!empty($proposta)){
			$retorno = array(
					"erro"		=>	0,
					"msg"		=>	"",
					"resultado"	=>	$this->dao->subProposta($proposta)
			);
			} else {
				$retorno = array(
						"erro"		=>	0,
						"msg"		=>	"",
						"resultado"	=>	""
				);
			}
			return $retorno;
			
		}
		catch (Exception $e) {
						
			$retorno = array(
					"erro"		=>	1,
					"msg"		=>	utf8_decode($e->getMessage()),
					"resultado"	=>	0
			);

			return $retorno;
		}
	}
	
	/**
	 * STI 81493
	 * Fun��o: Verificar se deve mostrar os campos referentes a taxa de instalacao
	 */
	public function verificaTaxaIntalacao(){	
		try {
			$taxaInstalacao = "GERAR_TAXA_ADESAO";
			$habilitaTaxa = false;
			
			$configuracao = $this->dao->verificaTaxaIntalacao();
			
			// Verificar se retornou algum erro na busca de configura��o
			if ($configuracao['erro']){
				throw new Exception($configuracao['msg']);
			}
			
			// Verificar se est� configurado a Taxa de Instala��o
			foreach ($configuracao as $index => $config){
				if ($config['apccodigo'] == $taxaInstalacao){
					$habilitaTaxa = true;
					break;
				}
			}

			$retorno = array(
					"erro"					=>	0,
					"taxaInstalacao"		=>	$habilitaTaxa
			);
			
			echo json_encode($retorno);
			exit();
		}
		catch (Exception $e) {
		
			$retorno = array(
					"erro"		=>	1,
					"msg"		=>	utf8_encode($e->getMessage())
			);
		
			echo json_encode($retorno);
			exit();
		}
	}

	/**
	 * Fun��o: Buscar as formas de pagamentos para a taxa de instala��o
	 */
	public function getFormaPagamentoTaxaInstalacao(){
		try{
			$retorno = array();
			
			$formaPagamentoTaxaInstalacao = $this->dao->getFormaPagamentoTaxaInstalacao();
							
			if ($formaPagamentoTaxaInstalacao['erro'] == 1){
				throw new Exception($formaPagamentoTaxaInstalacao['msg']);
			}

			foreach ($formaPagamentoTaxaInstalacao as $index => $forma){
				$formaPagamento[$index]['offcforcoid'] = $forma['offcforcoid'];
				$formaPagamento[$index]['forcnome'] = utf8_encode($forma['forcnome']);
			}
			
			$retorno = array(
					"erro"					=>	0,
					"formaPagamento"		=>	$formaPagamento
			);
				
			echo json_encode($retorno);
			exit();
						
		}catch (Exception $e){
			$retorno = array(
					"erro"		=>	1,
					"msg"		=>	utf8_encode($e->getMessage())
			);
			
			echo json_encode($retorno);
			exit();
		}
	}
	
	/**
	 * Fun��o: Buscar as configura��es de acordo com a forma de pagamento selecionada. 
	 */
	public function getConfigFormaPagamento(){
		try{
			$retorno = array();
				
			// Buscar parcelas de acordo com a forma de pagamento escolhida
			$parcelas = $this->dao->getParcelasFormaPagamento();
			$credito  = $this->dao->formaPagamentoIsCredito(); 	
			
			if ($parcelas['erro'] == 1){
				throw new Exception($parcelas['msg'], $parcelas['codigoErro']);
			}
			if ($credito['erro'] == 1){
				throw new Exception($credito['msg']);
			}
			
			$retorno['erro'] = 0;
			$retorno['parcelas'] = $parcelas;
			$retorno['credito'] = $credito['credito'];
				
			echo json_encode($retorno);
			exit();
		
		}catch (Exception $e){
			$retorno = array(
					"erro"			=>	1,
					"codigoErro"	=>	$e->getCode(),
					"msg"			=>	utf8_encode($e->getMessage())
			);
				
			echo json_encode($retorno);
			exit();
		}
	}
	
	/**
	 * Fun��o: Buscar o Valor da Taxa de Instala��o
	 */
	public function getValorTaxaInstalacao(){
		try{
			
			$retorno = array();
		
			$retorno['contrato'] = "";
			
			// Buscar o valor padr�o da taxa de instala��o
			$valor = $this->dao->getValorTaxaInstalacao();
			
			$contrato_numero = $_POST['contrato_numero'];
			if (!empty($contrato_numero)){
				// Se tem numero de contrato
				// Buscar o valor da taxa de instala��o cadastrado para o contrato
				$valorTaxaContrato = $this->dao->getValorTaxaInstalacaoContrato();

				if ($valorTaxaContrato['erro'] == 1){
					throw new Exception($valorTaxaContrato['msg']);
				}else if($valorTaxaContrato['titulo'] > 0){
					// Encontrou um t�tulo
					$retorno['contrato'] = $valorTaxaContrato;
				}
			}			
				
			if ($valor['erro'] == 1){
				throw new Exception($valor['msg']);
			}
			
			$retorno['padrao'] = $valor;
			$retorno['erro'] = 0;

			echo json_encode($retorno);
			exit();
		
		}catch (Exception $e){
			$retorno = array(
					"erro"			=>	1,
					"msg"			=>	utf8_encode($e->getMessage())
			);
		
			echo json_encode($retorno);
			exit();
		}
	}
	
	public function tituloPago(){
		$titulo = $this->dao->getValorTaxaInstalacaoContrato();
		
		// Verifica se retornou como titulo pago
		if ($titulo['erro'] == 1){
			throw new Exception($titulo['msg']);
		}else{
			
			$retono['erro'] = 0; // Sem erros
			
			$retono['valor'] = $titulo['valorTaxaContrato'];
			$retono['forma'] = $titulo['formaPagamento'];
			$retono['parcela'] = $titulo['parcela'];
			
			if ($titulo['tituloPago'] == 1){
				$retono['tituloPago'] = 1;
			}else{
				$titulo['tituloPago'] = 0;
			}
			
			echo json_encode($retono);
			exit();
		}
		
	}
	
	/**
	 * Fun��o: Salvar taxa de instala��o, do pr�-cadatro
	 */
	public function salvarTaxaInstalacao(){
		try{			
			
			// Verificar se deve salvar a taxa de instala��o (Varejo)
			$taxaInstalacao = "GERAR_TAXA_ADESAO";
			$salvarTaxaInstalacao = false;
	
			// Verificar se � do tipo varejo
			if (!empty($_POST['id_subproposta'])){
				$configuracao = $this->dao->verificaTaxaIntalacao();
			}		
				
			// Verificar se retornou algum erro na busca de configura��o
			if ($configuracao['erro']){
				throw new Exception($configuracao['msg']);
			}
				
			if(count($configuracao) > 0) {
			// Verificar se est� configurado a Taxa de Instala��o
			foreach ($configuracao as $index => $config){
				if ($config['apccodigo'] == $taxaInstalacao){
					$salvarTaxaInstalacao = true;
					break;
					}
				}
			}
			
			// � do tipo varejo
			if ($salvarTaxaInstalacao == true){
				
				// Busca se titulo esta pago
				$tituloPago = $this->dao->tituloPago();
				
				// Verifica se retornou como titulo pago
				if ($tituloPago['erro'] == 1){
					throw new Exception($tituloPago['msg']);
				}else{
					if ($tituloPago['tituloPago'] > 0){
						
						// tentando salvar um titulo mais j� esta pago, retorna para o pr�-cadastro, evitando cria��o de novo t�tulo
						
						$returnoSalva['erro'] = 0; // Sem erros
						$returnoSalva['tituloPago'] = $tituloPago['tituloPago'];
						
						return $returnoSalva;
					}
				}
	
				// Busca se foi realizado alguma altera��o na taxa de instala��o
				$tituloAlterado = $this->dao->tituloAlterado();
				
				// Verifica se o valor do titulo ou taxa foi alterada
				if ($tituloAlterado['erro'] == 1){
					throw new Exception($tituloAlterado['msg']);
				}else{
					if ($tituloAlterado['tituloAlterado'] == 0){
						
						$returnoSalva['erro'] = 0; // Sem erros
						$returnoSalva['tituloAlterado'] = $tituloAlterado['tituloAlterado'];
						
						return $returnoSalva;
					}					
				}
				
				// Verificar se a forma de pagamento � cart�o de cr�dito
				$credito  = $this->dao->formaPagamentoIsCredito();
				if ($credito['erro'] == 1){
					throw new Exception($credito['msg']);
				}
				
				// A forma � do tipo cart�o de cr�dito
				if ($credito['credito'] == 't'){
					
					// Tipo cart�o de cr�dito
					$returnoSalva = $this->dao->salvarTituloCartaoCredito();
					
				}else{
					// Tipo Boleto
					$returnoSalva = $this->dao->salvarTituloBoleto();
				}
				
				// Houve erro
				if ($returnoSalva['erro'] == 1){
					throw new Exception($returnoSalva['msg']);
				}else{
					// Sucesso, sem erro
					return $returnoSalva;
				}
			}else{
				// N�o � do tipo taxa de instala��o
				return "";
			}
		}catch (Exception $e){
			$retorno = array(
					'erro' 			=> true,
					'msg'  			=> $e->getMessage()
			);
			return $retorno;
		}
	}
	
	/**
	 * Fun��o: Calcular a taxa de instala��o de acordo com a quantidade de ve�culos e valor de parcelas
	 */
	public function calculaTaxaInstalacaoVeiculos(){	

		// Pega os valores
		$valor_total_taxa 			= str_replace(".","", $_POST['valorTaxa']);
		$valor_unitario 			= str_replace(".","", $_POST['valorTaxaUnitario']);
		
		$valor_total_taxa 			= str_replace(",",".", $valor_total_taxa);
		$valor_unitario 			= str_replace(",",".", $valor_unitario);
		
		$valor_maximo 				= $_POST['valorTaxaMaximo'];
		$valor_minimo 				= $_POST['valorTaxaMinimo'];
		$quantidade_veiculos 		= $_POST['qntdVeiculos'];
		$numero_parcelas 			= $_POST['numParcelas'];
		$cobra_juros 				= $_POST['cobraJuros'];
		$alterado_manual 			= $_POST['alteradoManual'];
		
		
		// Buscar o valor padr�o da taxa de instala��o
		$valor = $this->dao->getValorTaxaInstalacao();
	
		$valor_maximo_padrao = $valor['tpivalor'];
		$valor_minimo_padrao = $valor['tpivalor_minimo'];
		
		// Calculo do valor m�nimo e m�ximo da taxa
		$retorno['valor_total_maximo']	= $valor_maximo_padrao * $quantidade_veiculos;
		$retorno['valor_total_minimo']	= $valor_minimo_padrao * $quantidade_veiculos;
		
		// Alterado manualmente o valor pelo usuario
		if ($alterado_manual == 1){
		
			$retorno['valor_total_taxa'] 	= $valor_total_taxa;
			$retorno['valor_taxa_unitario'] = $valor_total_taxa / $quantidade_veiculos;
		
		}else{
			// Valores alterados automaticamente
			
			$valor_total_taxa = $valor_unitario * $quantidade_veiculos;
			$retorno['valor_total_taxa'] 	= $valor_total_taxa;
			$retorno['valor_taxa_unitario'] = $valor_unitario;
		}
					
		// Se foi selecionado algum parcelamento, calcula o valor da parcela
		if (!empty($numero_parcelas) && $numero_parcelas > 0){
			$valor_parcela = $valor_total_taxa / $numero_parcelas;
		}else{
			$valor_parcela = $valor_total_taxa;
		}
		// Formata valor parcelas
		$valor_parcela = ($valor_parcela != "") ? number_format($valor_parcela, 2, ",", ".") : "0,00";
		$retorno['valor_parcela'] = $valor_parcela;
		
		// Formata valores
		$retorno['valor_total_maximo'] 	= ($retorno['valor_total_maximo'] != "") ? number_format($retorno['valor_total_maximo'], 2, ",", ".") : "0,00";
		$retorno['valor_total_minimo'] 	= ($retorno['valor_total_minimo'] != "") ? number_format($retorno['valor_total_minimo'], 2, ",", ".") : "0,00";
		$retorno['valor_total_taxa'] 	= ($retorno['valor_total_taxa']  != "") ? number_format($retorno['valor_total_taxa'] , 2, ",", ".") : "0,00";
		$retorno['valor_taxa_unitario'] = ($retorno['valor_taxa_unitario'] != "") ? number_format($retorno['valor_taxa_unitario'], 2, ",", ".") : "0,00";
		$retorno['quantidade_veiculos']	= $quantidade_veiculos;
	
		echo json_encode($retorno);
		exit();
	}

	public function gerarOSAutomatica($contrato, $proposta){
		try {
			
			$confereGerarAuto = $this->dao->confereGerarConOSAuto($contrato, $proposta);
			
			if($confereGerarAuto == false){
				throw new Exception("Contrato e OS n�o configurados para gerar automaticamente.");
			}

			foreach($confereGerarAuto as $key => $val){
				$acaoContrato[] = $val['apcoid'];
			}
			
			// Busca o c�digo da a��o de acordo com seu nome
			$resultado	= $this->dao->getAcaoContrato("GERAR_OS_AUTOMATICAMENTE");
			if ($resultado['erro'] == 0){
				$gerarOsAuto = $resultado['resultado']->apcoid;
			}
			
			$resultado	= $this->dao->getAcaoContrato("GERAR_OS_AUTOMATICAMENTE_POS_PGTO");
			if ($resultado['erro'] == 0){
				$gerarOsAutoPosPagm = $resultado['resultado']->apcoid;
			}

			// Gerar OS Automatica
			if(in_array($gerarOsAuto, $acaoContrato)){

				$retorno = array(
					"erro"		=>	0,
					"msg"		=>	"",
					"resultado"	=>	'S'
				);
			}
			//exit;
			
			// Gerar OS Automatica Ap�s Pagamento
			if(in_array($gerarOsAutoPosPagm, $acaoContrato)){

				$getDadosOSPagamento = $this->dao->getDadosOSPagamento($contrato);
			
				if($getDadosOSPagamento == false){
					throw new Exception("N�o � poss�vel gerar ordem de servi�o automaticamente.");
				}

				$retorno = array(
						"erro"		=>	0,
						"msg"		=>	"",
						"resultado"	=>	'S'
				);
			}
			
			return $retorno;
		}
		catch (Exception $e) {

			$retorno = array(
					"erro"		=>	1,
					"msg"		=>	utf8_decode($e->getMessage()),
					"resultado"	=>	'N'
			);

			return $retorno;
		}
	}
	
	
	// Fun��o para verificar o status do gestor de credito da proposta
	public function verificaStatusGestorCredito($prpoid) {
		
		try {
			
			
			// Busca na tabela de Parametros Siggo o Id de Status aprovado de acordo com o Gestor de Credito
			$parametrosSiggo = new PrnParametrosSiggo();
			
			$paramsPesquisa = array(
					'id_tipo_proposta'		=>	0,
					'nome_parametro'		=> 	'ID_STATUS_FINANCEIRO_APROVADO'
			);
			
			$retornoValor = $parametrosSiggo->getValorParametros($paramsPesquisa);
			
			$idCreditoAprovadoGestorCredito = $retornoValor['valor'];
			
			
			// Verifica o Status do Gestor de Credito da Proposta
			$resultado = $this->dao->verificaStatusGestorCredito($prpoid);

			// Comparar o Status da Proposta com o ID da tabela parametros_siggo
			$status_proposta = "false";
			if ($resultado->prppsfoid == $idCreditoAprovadoGestorCredito){
				$status_proposta = "true";
			}
			
			$retorno = array(
					"erro"				=> 0,
					"status_id"			=> $resultado->prppsfoid,
					"status_nome"		=> $resultado->psfdescricao,
					"status_proposta"	=> $status_proposta
					);

			return $retorno;
		}
		catch (Exception $e) {
		
			$retorno = array(
					"erro"		=>	1,
					"msg"		=>	utf8_decode($e->getMessage())
			);
		
			return $retorno;
		}
	}
	
	// Fun��o para buscar o valor parametrizado da multa rescis�ria
	public function getValorMultaRescisoria(){

		try {
			$vigencia_contrato = $_POST['vigencia_contrato'];
			
			// Busca o valor da multa rescis�ria
			$resultado = $this->dao->getValorMultaRescisoria($vigencia_contrato);
				
			if ($resultado['erro'] == 1){
				throw new Exception($resultado['msg']);
			}
			
			$retorno = array(
					"erro"					=> 0,
					"multa_rescisoria"		=> number_format($resultado['multa_rescisoria'], 2, ",", ".") 
			);
			
			echo json_encode($retorno);
			exit();
		}
		catch (Exception $e) {
		
			$retorno = array(
					"erro"		=>	1,
					"msg"		=>	utf8_decode($e->getMessage())
			);
		
			echo json_encode($retorno);
			exit();
		}
		
	}
	
	/**
	 * Fun��o: Verificar qual o tipo da forma de pagamento da taxa de instala��o
	 */
	public function formaPagamentoIsCredito(){
		try{
			$retorno = array();
	
			
			// Verificar se deve salvar a taxa de instala��o (Varejo)
			$taxaInstalacao = "GERAR_TAXA_ADESAO";
			$salvarTaxaInstalacao = false;
			
			// Verificar se � do tipo varejo
			if (!empty($_POST['id_subproposta'])){
				$configuracao = $this->dao->verificaTaxaIntalacao();
			}
			
			// Verificar se retornou algum erro na busca de configura��o
			if ($configuracao['erro']){
				throw new Exception($configuracao['msg']);
			}
			
			if(count($configuracao) > 0) {
				// Verificar se est� configurado a Taxa de Instala��o
				foreach ($configuracao as $index => $config){
					if ($config['apccodigo'] == $taxaInstalacao){
						$salvarTaxaInstalacao = true;
						break;
					}
				}
			}
			
			// Verifica se tem taxa de instal��o (� cliente Varejo)
			if ($salvarTaxaInstalacao != true) {
				$retorno['erro'] = 0;
				$retorno['taxa_inst'] = 'f';
				return $retorno;
			}
			
			
			$tipo = $this->dao->formaPagamentoIsCredito();
				
			if ($tipo['erro'] == 1){
				throw new Exception($tipo['msg']);
			}
				
			$retorno['erro'] = 0;
			$retorno['cartaoCredito'] = $tipo['credito'];
			return $retorno;
	
		}catch (Exception $e){
			$retorno = array(
					"erro"			=>	1,
					"codigoErro"	=>	$e->getCode(),
					"msg"			=>	utf8_encode($e->getMessage())
			);
			
			return $retorno;
		}
	}	
	
	/**
	 * Fun��o: Buscar os valores da taxa de instala��o de acordo com a proposta
	 */
	public function getTaxaInstalacaoProposta($prpoid){
		try{
			$retorno = array();
	
			$dados = $this->dao->getTaxaInstalacaoProposta($prpoid);
				
			if ($tipo['erro'] == 1){
				throw new Exception($tipo['msg']);
			}
				
			$retorno = $dados;
			return $retorno;
	
		}catch (Exception $e){
			$retorno = array(
					"erro"			=>	1,
					"msg"			=>	utf8_encode($e->getMessage())
			);
			
			return $retorno;
		}
	}	
	
	/**
	 * Fun��o: Buscar os valores da taxa de instala��o de acordo com o numero de parcelas escolhido e se possui juros
	 */
	public function getValorTaxaParcela(){
		try{
			$retorno = array();
		
			$busca = $_POST;
			
			$dados = $this->dao->getValorTaxaParcela($busca);
			
			if ($dados['erro'] == 1){
				throw new Exception($dados['msg']);
			}
			$dados['erro']	= 0;
		
			echo json_encode($dados);
			exit();
		
		}catch (Exception $e){
			$retorno = array(
					"erro"			=>	1,
					"msg"			=>	utf8_encode($e->getMessage())
			);
				
			echo json_encode($retorno);
			exit();
		}
	}
	
	public function verificaTipoVeiculoCarreta() {

		$retorno = array();

		try{

			$mlooid = $_POST['mlooid'] ?  $_POST['mlooid'] : NULL;

			if(!is_null($mlooid)) {

				$dadosModelo = $this->dao->buscarDadosModelo($mlooid);

				if(isset($dadosModelo['erro'])) {
					throw new Exception($dadosModelo['erro']);
				}

				$infoTipoVeiculo = $this->dao->infoTipoVeiculo($dadosModelo['result']['mlotipveioid']);

				if(isset($infoTipoVeiculo['erro'])) {
					throw new Exception($infoTipoVeiculo['erro']);
				}

				if(isset($infoTipoVeiculo['result']['tipvcarreta']) && $infoTipoVeiculo['result']['tipvcarreta'] == 't') {

					$tiposCarreta = $this->dao->tipoCarreta();
					$optionsTipoCarreta = '';

					if(!isset($tiposCarreta['erro'])) {
						while ($linha = pg_fetch_assoc($tiposCarreta)) {
							$optionsTipoCarreta .= '<option value="'.$linha['tipcoid'].'">' . $linha['tipcdescricao'] . '</option>';
						}
					}

					//Tipo carreta, ABS, EBS, acessorios pneu
					$retorno['tipcarreta'] = '<td><label>Tipo de carreta: (*)</label></td>
                                		<td>
                                			<select name="tipcarreta" id="tipcarreta" onchange="tipoCarretaCategoria()">
                            					<option value="">Escolha</option>
                            					:options_tipo_carreta
                                			</select>
                                		</td>';

                    $retorno['tipcarreta'] = str_replace(':options_tipo_carreta', utf8_encode($optionsTipoCarreta), $retorno['tipcarreta']);

					$retorno['abs'] = '<td><label>Possui ABS: </label></td>
			                    	   <td><input id="veiabs" name="veiabs" type="checkbox"></td>';

                    $retorno['ebs'] = '<td><label>Possui EBS: </label></td>
                                       <td><input id="veiebs" name="veiebs" type="checkbox" onclick="dadosEBS()"></td>';

                    $retorno['veiacessorios_pneu'] = utf8_encode('<td><label>Ser�o Instalados Sensores de Temperatura e Press�o de Pneu: </label></td>
                                					  <td><input id="veiacessorios_pneu" name="veiacessorios_pneu" type="checkbox" onclick="dadosAcessoriosPneu()"></td>');
                                        
				}
			}

		} catch(Exception $e) {
			$retorno['erro'] = utf8_encode($e->getMessage());
		}

		echo json_encode($retorno);exit;
	}

	public function dadosEBS() {
		$retorno = array();
		$options = '';
		
		try{

			$marcasModeloEBS = $this->dao->marcaModeloEBS();

			if(isset($marcasModeloEBS['erro'])) {
				throw new Exception($marcasModeloEBS['erro']);
			}

			while($linha = pg_fetch_assoc($marcasModeloEBS)) {
				$options .= '<option value="'.$linha['modeoid'].'">' . $linha['mmedescricao'] .'-'. $linha['modedescricao'] . '</option>';
			}

			$retorno['marcas_ebs'] = '<td><label>Marca/Modelo EBS: (*)</label></td>
                        		<td>
                        			<select name="veimodeoid" id="veimodeoid">
                        				<option value="">Escolha</option>
                        				:opt_marca_modelo_ebs
                        			</select>
                        		</td>';

			$retorno['marcas_ebs'] = str_replace(':opt_marca_modelo_ebs', utf8_encode($options), $retorno['marcas_ebs']);

		} catch (Exception $e) {
			$retorno['erro'] = utf8_encode($e->getMessage());
		}

		echo json_encode($retorno);exit;
	}

	public function dadosAcessoriosPneu() {
		$retorno = array();

		try{
			$optionsEixosCarreta = '';
			$optionsDimensaoPneus = '';

			// Verifica quais tipos de eixo est�o configurados para a carreta
			$tipoCarreta = isset($_POST['tipcarreta']) ? $_POST['tipcarreta'] : NULL;

			$tipceixos = $this->dao->tipoCarreta($tipoCarreta);

			if(isset($tipceixos['erro'])) {
				throw new Exception($tipceixos['erro']);
			}

			$eixos = pg_fetch_assoc($tipceixos);

			// Passa a configura��o dos eixos para busca na tabela
			$eixosCarreta = $this->dao->eixosCarreta($eixos['tipceixos']);

			if(isset($eixosCarreta['erro'])) {
				throw new Exception($eixosCarreta['erro']);
			}

			$dimensaoPneus =  $this->dao->dimensaoPneus();

			if(isset($dimensaoPneus['erro'])) {
				throw new Exception($dimensaoPneus['erro']);
			}

			while ($linha = pg_fetch_assoc($eixosCarreta)) {
				$optionsEixosCarreta .= '<option value="'.$linha['eixcoid'].'">' . $linha['eixcnumero'] .'-'. $linha['eixcdescricao'] . '</option>';
			}

			while ($linha = pg_fetch_assoc($dimensaoPneus)) {
				$optionsDimensaoPneus .= '<option value="'.$linha['dimpoid'].'">' . $linha['dimpdescricao'] . '</option>';
			}

			$retorno['veieixcoid'] = '<td><label>N� eixos Carreta: (*)</label></td>
			<td>
				<select name="veieixcoid" id="veieixcoid">
					<option value="">Escolha</option>
					:options_eixos_carreta
				</select>
			</td>';


			$retorno['veipneus_germinados'] = '<td><label>Pneus Geminados: (*)</label></td>
			<td>
				<select name="veipneus_germinados" id="veipneus_germinados">
					<option value="">Escolha</option>
					<option value="1">Sim</option>
					<option value="0">N�o</option>
				</select>
			</td>';


			$retorno['veidimpoid'] = '<td><label>Dimens�o Pneus: (*)</label></td>
			<td>
				<select name="veidimpoid" id="veidimpoid">
					<option value="">Escolha</option>
					:options_dimensao_pneus
				</select>
			</td>';

			$retorno['veicomprimento'] = '<td><label>Comprimento:</label></td>
			<td><input type="text" name="veicomprimento" id="veicomprimento" onkeyup="formatar(this, \'@\')" onblur="revalidar(this,\'@\');"></td>';
				
			$retorno['veicapacidade'] = '<td><label>Capacidade:</label></td>
			<td><input type="text" name="veicapacidade" id="veicapacidade"></td>';

			$retorno['veieixcoid'] = str_replace(':options_eixos_carreta', $optionsEixosCarreta, $retorno['veieixcoid']);
			$retorno['veidimpoid'] = str_replace(':options_dimensao_pneus', $optionsDimensaoPneus, $retorno['veidimpoid']);

			foreach ($retorno as $key => $value) {
				$retorno[$key] = utf8_encode($value);
			}

		} catch (Exception $e) {
			$retorno['erro'] = utf8_encode($e->getMessage());
		}

		echo json_encode($retorno);exit;
	}

	public function categoriasTipoCarreta() {
		$retorno = array();

		try{
			$tipcoid = isset($_POST['tipcarreta']) ? $_POST['tipcarreta'] : NULL;

			if(!is_null($tipcoid)) {

				//Verifica se o tipo de carreta aceita categoria
				$tipoCarreta = $this->dao->tipoCarreta($tipcoid);

				if(isset($tipoCarreta['erro'])) {
					throw new Exception($tipoCarreta['erro']);
				}

				$recebeCategoria = pg_fetch_assoc($tipoCarreta);

				// Se recebe categoria, devolve a combo "Categorias de Carreta, a ser exibida na tela"
				if(isset($recebeCategoria['tipcrecebe_categoria']) && $recebeCategoria['tipcrecebe_categoria'] == 't') {

					$optionsCategoriaCarreta = '';
					$categoriasCarreta = $this->dao->buscarTipoCarretaCategoria();

					if(isset($categoriasCarreta['erro'])) {
						throw new Exception($categoriasCarreta['erro']);
					}

					while ($linha = pg_fetch_assoc($categoriasCarreta)) {
						$optionsCategoriaCarreta .= '<option value="'.$linha['tccoid'].'">' . $linha['tccdescricao'] . '</option>';
					}

					$retorno['veitccoid'] = '<td><label>Categorias de Carreta: (*)</label></td>
					<td>
						<select name="veitccoid" id="veitccoid">
							<option value="">Escolha</option>
							:options_categorias_carreta
						</select>
					</td>';

					$retorno['veitccoid'] = str_replace(':options_categorias_carreta', $optionsCategoriaCarreta, $retorno['veitccoid']);

					foreach ($retorno as $key => $value) {
						$retorno[$key] = utf8_encode($value);
					}
				}
			}

		} catch (Exception $e) {
			$retorno['erro'] = utf8_encode($e->getMessage());
		}

		echo json_encode($retorno);exit;
	}

	public function retornaTipoCarretaCategoria() {

		$retorno = array();

		try {

			$retorno = $this->dao->buscarTipoCarretaCategoria();

			if(isset($retorno['erro'])) {
				throw new Exception($retorno['erro']);
			}

		} catch(Exception $e) {
			$retorno['erro'] = $e->getMessage();
		}

		return $retorno;
	}

	public function retornaTiposCarreta() {

		$retorno = array();

		try {

			$retorno = $this->dao->tipoCarreta();

			if(isset($retorno['erro'])) {
				throw new Exception($retorno['erro']);
			}

		} catch(Exception $e) {
			$retorno['erro'] = $e->getMessage();
		}

		return $retorno;
	}

	public function retornaDimensaoPneu() {
		$retorno = array();

		try {

			$retorno = $this->dao->dimensaoPneus();

			if(isset($retorno['erro'])) {
				throw new Exception($retorno['erro']);
			}

		} catch(Exception $e) {
			$retorno['erro'] = $e->getMessage();
		}

		return $retorno;
	}

	public function retornaEixosCarreta() {	
		$retorno = array();

		try {

			$retorno = $this->dao->eixosCarreta();

			if(isset($retorno['erro'])) {
				throw new Exception($retorno['erro']);
			}

		} catch(Exception $e) {
			$retorno['erro'] = $e->getMessage();
		}

		return $retorno;
	}

	public function retornaMarcaModeloEBS($modeoid = NULL) {
		$retorno = array();

		try {

			$retorno = $this->dao->marcaModeloEBS($modeoid);

			if(isset($retorno['erro'])) {
				throw new Exception($retorno['erro']);
			}

		} catch(Exception $e) {
			$retorno['erro'] = $e->getMessage();
		}

		return $retorno;
	}

	public function insereServicoContrato($arrValores) {
		$retorno = array();

		try {

			$retorno = $this->dao->insereServicoContrato($arrValores);

			if(isset($retorno['erro'])) {
				throw new Exception($retorno['erro']);
			}

		} catch(Exception $e) {
			$retorno['erro'] = $e->getMessage();
		}

		return $retorno;
	}
	
	public function inserePropostaServico($prpoid) {
		$retorno = array();

		try {

			$retorno = $this->dao->inserePropostaServico($prpoid);

			if(isset($retorno['erro'])) {
				throw new Exception($retorno['erro']);
			}

		} catch(Exception $e) {
			$retorno['erro'] = $e->getMessage();
		}

		return $retorno;
	}

	/**
	 * M�todo utilizado na transfer�ncia de titularidade para concluir alertas vinculados
	 * ao ve�culo no SASWEB.
	 *
	 * Ao executar uma transfer�ncia de titualiridade dentro do pr�-cadastro os alertas do ve�culo 
	 * ainda chegam ao propriet�rio antigo, pois a transfer�ncia foi efetivada no ERP por�m no SASWEB n�o.
	 * O m�todo a seguir foi feito para tratar esta situa��o constatada na ASM/Bug Nr 2581
	 *
	 * @author Harry Luiz Janz <harry.janz.ext@sascar.com.br>
	 *
	 * @param string $placa - Placa do ve�culo a ser desvinculado o alerta
	 * @param string $returnException - Se retornar� Exception ou n�o
  	 *
  	 * @return idefinido
	 **/

	public function concluirAlertaVeiculoSasweb($placa,$returnException = false) {

		// Verifica se o veiculo atrav�s da placa tem usu�rio no SASWEB
        $strSQL = "SELECT 
        				veiculo.veioid 
					FROM 
						veiculo
					INNER JOIN 
						contrato ON contrato.conveioid = veiculo.veioid 
					WHERE 
						veiculo.veiplaca = '$placa' 
						AND 
						veiculo.veivisualizacao_sasweb = 't'";

		$result = pg_query($this->conn,$strSQL);

		if(!$result) {
			if($returnException === false) {
				return false;
			}else{
				throw new Exception("Query did not execute: ". pg_last_error($this->conn));
			}
		} elseif (pg_num_rows($result) == 0) {
			if($returnException === false) {
				return false;
			}else{
				throw new Exception("Erro: Placa ".$placa." n�o encontrada.");
			}
		} else {
			while ($row = pg_fetch_row($result)) {

				$post = array();
		        $post["veioid"] = $row[0];
		        $post = json_encode($post);

				$curl = curl_init(_WS_SASWEB_."dao/alerta/core/desvincularRegraPorVeiculo");

				curl_setopt_array($curl, array(
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING => "",
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 30,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST => "POST",
					CURLOPT_POSTFIELDS => $post,
					CURLOPT_HTTPHEADER => array("content-type: application/json")
				));

				$response = curl_exec($curl);
				$err = curl_error($curl);
				$info = curl_getinfo($curl);
				curl_close($curl);

				if($response === false) {
					if($returnException === false) {
						return false;
					}else{
    					throw new Exception("Curl error: ". $err);
    				}
				} else {
					if($info["http_code"] == "500") {
						if($returnException === false) {
							return false;
						}else{
							throw new Exception("Curl error: 500 - Internal server error");
						}
					}else{
						if($info["http_code"] == "200"){
							$return = json_decode($response,FALSE);
		    				if($return->{'status'} == '200') {
		    					return $return;
		    				}
						}
					}
				}

			}
		}

	}

	// FIM CLASSE PrnPreCadastro	
}
