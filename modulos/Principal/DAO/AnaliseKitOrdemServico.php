<?php
/*
 * @author	Gabriel Luiz Pereira
 * @email	gabriel.pereira@meta.com.br
 * @since	21/08/2012
 * */

/**
 * An�lise de kits de insta��o para ordem de servi�o.
 */
 class AnaliseKitOrdemServico {

 	/**
 	 * Link de conex�o com o banco
 	 * @var resource
 	 */
 	private $conn;

 	/**
 	 * OID da ordem de servi�o em quest�o
 	 * @var integer
 	 */
 	private $ordoid;


 	/**
 	 * Construtor
 	 * @param integer $ordoid	OID da ordem de servi�o
 	 * @throws Exception
 	 */
 	public function __construct($ordoid) {

 		global $conn;

 		if (empty($ordoid)) {
 			throw new Exception("Favor informar  uma ordem de servi�o.");
 		}
 		else if (empty($conn)) {
 			throw new Exception("Link de conex�o com o banco n�o encontrado.");
 		}
 		else if (!is_numeric($ordoid)) {
 			throw new Exception("O n�mero da ordem de servi�o deve ser n�merica.");
 		}
 		else {

 			// Aloca valores iniciais obrigat�rios
 			$this->ordoid 	= $ordoid;
 			$this->conn	 	= $conn;
 		}
 	}

 	/**
 	 * Executa o processo de an�lise e inser��o de Kits da ordem de servi�o.
 	 * @throws Exception
 	 */
 	public function executar() {

 		if (!empty($this->ordoid)) {

 			try {

 				// Se a ordem de servi�o n�o for de assist�ncia
 				if ($this->verificaOSTipoAssistencia() === false) {

 					$rsMotivos 			= $this->consultaMotivosOS();
 					$totalMotivos		= pg_num_rows($rsMotivos);

 					$rsKitsInseridos 	= $this->buscaKitsOrdemServico();
 					$totalKitsInseridos = pg_num_rows($rsKitsInseridos);

					$listaMotivos		= array(); // Array inicial de motivos
 					$listaKitsInstalados= array(); // Lista de kits encontrados
 					$listaKitsInseridos = array(); // Lista de kits incluidos ao final do processo

 					$listaAtuadoresAnalise= array();
 					$listaMotivosAnalise  = array();

 					/**
 					 * Listas para an�lise de Kit de Instalacao
 					 */
 					 $listaAtuadoresI	= array();
 					 $listaMotivosI		= array();
 					 $listaMotivosIB	= array(); // Lista de motivos para an�lise somente com itens b�sicos.

 					 $listaAtuadoresObrigatorios	= array();
 					 $listaMotivosObrigatorios		= array();

 					 $kitsObrigatorio	= array();
 					 $kitsNObrigatorio	= array();


 					$contaMotivosA 		= 0;

 					$ostoid				= '';


					/**
					 * Se n�o retornar nenhum motivo e retornar kits j� inseridos,
					 * n�o executa o processo
					 */
 					if (!($totalKitsInseridos > 0 && $totalMotivos == 0)) {


 						// Monta a lista de kits j� instalados antes do processo
 						while ($kit = pg_fetch_object($rsKitsInseridos)) {

 							$listaKitsInstalados[] = array(
 								'ositotioid'		=> $kit->ositotioid,
 								'instalado'			=> true
 							);
 						}

 						// Monta array inicial com os motivos
 						while ($motivo = pg_fetch_object($rsMotivos)) {

 							/**
 							 * Se o motivo for do tipo 'V' (Avulso) e retornar um kit,
 							 * adiciona o kit na lista de inseridos.
 							 *
 							 * Caso contrario, ignore o motivo e n�o inclua na lista de motivos a serem tratados.
 							 */
 							if ($motivo->otitipo_kit_instalacao == 'V') {

 							 	if ($motivo->kit_instalacao > 0) {

	 							 	// Insere o kit na lista
							 		$listaKitsInstalados[] = array(
		 								'ositotioid'		=> $motivo->kit_instalacao,
		 								'instalado'			=> false
		 							);
 							 	}
 							}
 							else {
 								// Caso n�o seja tipo 'V'

 								// Conta a quantidade de motivos do tipo 'A'
 								if ($motivo->otitipo_kit_instalacao == 'A') {
 									$contaMotivosA++;
 								}

 								// Adiciona na lista inicial
	 							$listaMotivos[] = array(
	 								'ordoid'					=> $motivo->ordoid,
	 								'ordstatus'					=> $motivo->ordstatus,
	 								'ositeqcoid'				=> $motivo->ositeqcoid,
	 								'eqcdescricao'				=> $motivo->eqcdescricao,
	 								'ostoid'					=> $motivo->ostoid,
	 								'ostdescricao'				=> $motivo->otidescricao,
	 								'ositstatus'				=> $motivo->ositstatus,
	 								'ositotioid'				=> $motivo->ositotioid,
	 								'otiattoid'					=> $motivo->otiattoid,
	 								'otitipo_kit_instalacao'	=> $motivo->otitipo_kit_instalacao,
	 								'otitipo_kit_visualizacao'	=> $motivo->otitipo_kit_visualizacao,
	 								'kit_instalacao'			=> $motivo->kit_instalacao
	 							);
 							}

 							// Preenche o ostoid para uso posterior
 							if (empty($ostoid)) {
 								$ostoid	= $motivo->ostoid;
 							}
						}

 						/**
 						 * Se existir valores no array Dever� ser percorrido o array verificando
 						 * quantos motivos de otitipo_kit_instalacao do tipo 'A' existem
 						 */
 						if (count($listaMotivos) > 0) {

 							/**
 							 * Se o numero de motivos otitipo_kit_instalacao tipo 'A' > 2,
        					 */
 							if ($contaMotivosA > 2) {

 							 	/**
 							 	 * criar 2 listas para guardar os itens que ser�o buscados
	 							 * na composi��o dos Kits, as listas ser�o diferenciada pelos
	 							 * c�digos de Atuadores e Motivos.
 							 	 */
 							 	foreach($listaMotivos as $chave => $motivo) {

 							 		if ($motivo['otitipo_kit_instalacao'] == 'A') {

	 							 		if ($motivo['otitipo_kit_visualizacao'] == 'M') {

	 							 			/**
	 							 			 * Guardar  o valor do ositotioid na lista de Lista_Motivos_ositotioid
	 							 			 */
	 							 			$listaMotivosAnalise[] = $motivo['ositotioid'];
	 							 		}

	 							 		if ($motivo['otitipo_kit_visualizacao'] == 'A') {

	 							 			/**
	 							 			 * Guardar  o valor do ositotioid na lista de Lista_Atuadores_ositotioid
	 							 			 */
	 							 			$listaAtuadoresAnalise[] = $motivo['otiattoid'];
	 							 		}
 							 		}
 							 	}

 							 	// Consulta 3
						 		$rsBuscaKitsMotivoAtuador = $this->buscaKitMotivoAtuador(
						 			$ostoid,
						 			implode(',', $listaMotivosAnalise),
						 			implode(',', $listaAtuadoresAnalise)
						 		);

						 		// Se possuir resultados
						 		$buscaKitTotalLinhas = pg_num_rows($rsBuscaKitsMotivoAtuador);


						 		if ($buscaKitTotalLinhas > 0) {

						 			// Trata se houve inclus�o de kit para os acess�rios ou n�o
						 			$kitAcessorioIncluido = false;

						 			// Reorganiza o array
						 			sort($listaAtuadoresAnalise);
						 			sort($listaMotivosAnalise);

						 			/**
						 			 * No resultado da Consulta 3 ser�o retornados todos os Kits
						 			 * que contem os valores solicitados, devemos verificar se nos
						 			 * resultados existem um Kit  que seja composto exatamente pela
						 			 * composi��o de Motivos + Atuadores.
						 			 */
									while ($kitMotivoAtuador = pg_fetch_object($rsBuscaKitsMotivoAtuador)) {

										// Listas Motivos/Atuadores para compara��o com o Kit
										if ($kitMotivoAtuador->atuador != '') {

											$atuadoresDoKit 	= explode(',', $kitMotivoAtuador->atuador);
										}
										else {

											$atuadoresDoKit 	= array();
										}

										if ($kitMotivoAtuador->motivo != '') {

											$motivosDoKit 		= explode(',', $kitMotivoAtuador->motivo);
										}
										else {

											$motivosDoKit = array();
										}


										// Organiza o array
										sort($atuadoresDoKit);
										sort($motivosDoKit);

										// Verifica se o Kit tem a mesma composi��o de Motivos e Atuadores
										$diferencaAtuadores	= array_diff_assoc($atuadoresDoKit, $listaAtuadoresAnalise);

										$diferencaMotivos	= array_diff_assoc($motivosDoKit,   $listaMotivosAnalise);

										// Verifica a diferen�a reversa entre itens da O.S. e a lista de kits
										$diferencaAtuadoresAnalise = array_diff_assoc($listaAtuadoresAnalise, $atuadoresDoKit);

										$diferencaMotivosAnalise = array_diff_assoc($listaMotivosAnalise, $motivosDoKit);

										// Se a estrutura do kit for exatamente igual (N�o deve haver nenhuma das diferen�as)
										if (count($diferencaAtuadores) == 0
											&& count($diferencaMotivos) == 0
											&& count($diferencaAtuadoresAnalise) == 0
											&& count($diferencaMotivosAnalise) == 0)
										{

											$kitNaoInserido = true;

											// Verifica se o Kit j� esta inserido
			 							 	for($i = 0; $i <= count($listaKitsInstalados)-1; $i++) {

			 							 		if ($kitMotivoAtuador->kiosotioid_kit == $listaKitsInstalados[$i]['ositotioid']) {
			 							 			$kitNaoInserido = false;
			 							 		}
			 							 	}

			 							 	// Kit ainda n�o inserido?
			 							 	if ($kitNaoInserido == true) {

			 							 		// Ent�o insere com flag de instalado = false
			 							 		$listaKitsInstalados[] = array(
			 							 			'ositotioid'		=> $kitMotivoAtuador->kiosotioid_kit,
 													'instalado'			=> false
			 							 		);


			 							 		$kitAcessorioIncluido = true;
			 							 		$totalListaMotivos = count($listaMotivos);

			 							 		// Remove itens do Array inicial
			 							 		for ($i = 0; $i < $totalListaMotivos; $i++) {

			 							 			if ($listaMotivos[$i]['otitipo_kit_instalacao'] == 'A') {

														unset($listaMotivos[$i]);
			 							 			}
			 							 		}
			 							 		sort($listaMotivos);
			 							 	}
			 							 	else {
			 							 		continue;
			 							 	}
										}
									}

									if ($kitAcessorioIncluido === false) {

										 /**
										 * Se n�o encontrou kit para adicionar
										 * Verificar os Kits avulsos dos itens do tipo acess�rio onde otitipo_kit_instalacao = 'A'
										 */
									 	foreach($listaMotivos as $chave => $motivo) {

									 		if ($motivo['otitipo_kit_instalacao'] == 'A') {

									 			$rsBuscaKitAvulso = $this->buscaKitAvulso($motivo['ositotioid']);

									 			if (pg_num_rows($rsBuscaKitAvulso) > 0) {

									 				// Controla a inser��o do kit caso j� o tenha na lista de instalados
									 				$insereKit = true;
									 				$kitAvulsoAssistencia = pg_fetch_object($rsBuscaKitAvulso);

									 				// Valida��o de kit existente
									 				for($i = 0; $i <= count($listaKitsInstalados)-1; $i++) {

									 					if ($kitAvulsoAssistencia->kiosotioid_kit == $listaKitsInstalados[$i]['ositotioid']) {

									 						$insereKit = false;
									 					}
									 				}

									 				// Se o Kit ainda n�o foi inserido na lista
									 				if ($insereKit == true) {

									 					// Adiciona kit
									 					$listaKitsInstalados[] = array(
									 						'ositotioid'		=> $kitAvulsoAssistencia->kiosotioid_kit,
															'instalado'			=> false
									 					);

									 					// Remove motivo da lista de an�lise
									 					unset($listaMotivos[$chave]);
									 				}
									 			}
									 		}
									 	}
									}

						 		}
 							 }


							 /**
						 	 * Executar regra de Kit de instala��o ( Ver RN 6.9)
						 	 *
						 	 * Ao percorrer o array_inicial  dever� ser verificado o campo otitipo_kit_visualizacao.
						 	 * Verificar se existem motivos do Tipo otitipo_kit_instalacao  com 'A e 'B'
						 	 */
						 	 // Verifica se tem tipo A e B
						 	 $temA = false;
						 	 $temB = false;

						 	 foreach($listaMotivos as $chave => $motivo) {

						 	 		if ($motivo['otitipo_kit_instalacao'] == 'A' && $temA === false) {
						 	 			$temA = true;
						 	 		}

						 	 		if ($motivo['otitipo_kit_instalacao'] == 'B' && $temB === false) {
						 	 			$temB = true;
						 	 		}
						 	 }


						 	 if ($temA || $temB) {

						 	 	foreach($listaMotivos as $chave => $motivo) {

						 	 		if ($motivo['otitipo_kit_instalacao'] == 'A') {

						 	 			if ($motivo['otitipo_kit_visualizacao'] == 'M') {

						 	 				$listaMotivosI[] = $motivo['ositotioid'];
						 	 				$listaMotivosIB[] = $motivo['ositotioid'];
						 	 			}
						 	 			else {
						 	 				$listaAtuadoresI[] = $motivo['otiattoid'];
						 	 			}

						 	 		}

						 	 		if ($motivo['otitipo_kit_instalacao'] == 'B') {

						 	 			$listaMotivosI[] = $motivo['ositotioid'];
						 	 			$listaMotivosIB[] = $motivo['ositotioid'];
						 	 		}

						 	 	}

						 	 	/**
						 	 	 * An�lise de kits de instala��o considerando acess�rios.
						 	 	 */
								if(is_array($listaMotivosI) and count($listaMotivosI)){
									$rsKitsInstalacao = $this->buscaKitsInstalacao(
										$ostoid,
										implode(',', $listaMotivosI),
										implode(',', $listaAtuadoresI)
									);


									if (pg_num_rows($rsKitsInstalacao) > 0) {

										// Montagem dos kits para avalia��o
										while ($kit = pg_fetch_object($rsKitsInstalacao)) {

											// Trata espa�os em branco
											if (empty($kit->motivo)) {
												$motivosKit = array();
											}
											else {
												$motivosKit = explode(',', $kit->motivo);
											}

											if (empty($kit->atuador)) {
												$atuadoresKit = array();
											}
											else {
												$atuadoresKit = explode(',', $kit->atuador);
											}


											// Monta listas de obrigatorios e n�o obrigatorios
											if ($kit->kiosobrigatorio == 't') {

												$kitsObrigatorio[] = array(
													'kiosotioid_kit'	=> $kit->kiosotioid_kit,
													'motivos'			=> $motivosKit,
													'atuadores'			=> $atuadoresKit
												);
											}
											else {

												$kitsNObrigatorio[] = array(
													'kiosotioid_kit'	=> $kit->kiosotioid_kit,
													'motivos'			=> $motivosKit,
													'atuadores'			=> $atuadoresKit
												);
											}

											// Ordena as listas
											sort($kitsObrigatorio);
											sort($kitsNObrigatorio);
										}

										sort($listaMotivosI);
										sort($listaMotivosIB);
										sort($listaAtuadoresI);


										// An�lise dos kits (itens Obrigat�rios), aqui eliminamos os kits que n�o ser�o usados.
										for($i = 0; $i < count($kitsObrigatorio); $i++) {

											$diferencaKitMotivos = 0;
											$diferencaKitAtuadores = 0;

											sort($kitsObrigatorio[$i]['atuadores']);
											sort($kitsObrigatorio[$i]['motivos']);

											$diferencaKitMotivos   = count(array_diff($kitsObrigatorio[$i]['motivos'],   $listaMotivosI));

											$diferencaKitAtuadores   = count(array_diff_assoc($kitsObrigatorio[$i]['atuadores'],   $listaAtuadoresI));

											if ($diferencaKitMotivos > 0 || $diferencaKitAtuadores > 0) {

												// Remove o mesmo kit da lista de itens n�o obrigat�rios
												for ($j = 0; $j < count($kitsNObrigatorio); $j++) {
													if ($kitsNObrigatorio[$j]['kiosotioid_kit'] == $kitsObrigatorio[$i]['kiosotioid_kit']) {

														unset($kitsNObrigatorio[$j]);
														sort($kitsObrigatorio);
													}
												}

												unset($kitsObrigatorio[$i]);
												sort($kitsObrigatorio);
												$i = 0;
											}
										}

										// Se depois da an�lise eliminat�ria existir algum kit,
										if (count($kitsObrigatorio) > 0) {

											sort($kitsObrigatorio);
											sort($kitsNObrigatorio);

											$diferencaAtuadoresObrigatorios = 0;
											$diferencaMotivosObrigatorios	= 0;

											$diferencaAtuadoresInversa 		= 0;
											$diferencaMotivosInversa		= 0;

											$pesoDiferencaInversa			= null;
											$diferencaInversa				= 0;
											$kitMaisProximo					= null;


											// Efetua a an�lise para saber se exite algum kit que contempla a lista de
											// itens que sobraram da ordem de servi�o
											for ($k = 0; $k < count($kitsObrigatorio); $k++) {

												$insereKit = false;

												sort($kitsObrigatorio[$k]['atuadores']);
												sort($kitsObrigatorio[$k]['motivos']);

												sort($listaAtuadoresI);
												sort($listaMotivosI);

												if (count($listaAtuadoresI) > 0) {
													$diferencaAtuadoresObrigatorios = count(array_diff_assoc($kitsObrigatorio[$k]['atuadores'], $listaAtuadoresI));
													$diferencaAtuadoresInversa		= count(array_diff_assoc($listaAtuadoresI, $kitsObrigatorio[$k]['atuadores']));
												}

												if (count($listaMotivosI) > 0) {
													$diferencaMotivosObrigatorios   = count(array_diff($kitsObrigatorio[$k]['motivos'],   $listaMotivosI));
													$diferencaMotivosInversa		= count(array_diff($listaMotivosI, $kitsObrigatorio[$k]['motivos']));
												}


												// Se o kit n�o tem difer�n�a, ent�o entra para an�lise dos itens da O.S.
												if ($diferencaMotivosObrigatorios == 0 && $diferencaAtuadoresObrigatorios == 0) {


													// An�lise das diferen�as

													if (count($listaMotivosI) > 0 && count($listaAtuadoresI) > 0) {

														$diferencaInversa = $diferencaAtuadoresInversa + $diferencaMotivosInversa;
													}
													else if (count($listaMotivosI) > 0) {

														$diferencaInversa = $diferencaMotivosInversa;
													}
													else if (count($listaAtuadoresI) > 0) {

														$diferencaInversa = $diferencaAtuadoresInversa;
													} else {
														$diferencaInversa = 0;
													}

													// An�lisa o peso entre as diferen�as
													if ($pesoDiferencaInversa === null) {

														$kitMaisProximo		  = $k;
														$pesoDiferencaInversa = $diferencaInversa;

														if (count($kitsObrigatorio)-1 == $k) {

															$insereKit = true;
														}
													}
													else if ($diferencaInversa < $pesoDiferencaInversa) {

														$kitMaisProximo		  = $k;
														$pesoDiferencaInversa = $diferencaInversa;

														if ($k == count($kitsObrigatorio)-1) {

															$insereKit = true;
														}
													}
													else {


														if ($k == count($kitsObrigatorio)-1) {

															$insereKit = true;
														}
													}
												}

												// Inser��o do kit
												if ($insereKit == true && $kitMaisProximo !== null) {

													$kitValido = true;

													// Verifica se o kit j� esta inserido, se estiver, n�o insere
													foreach($listaKitsInstalados as $kit) {

														if ($kit['ositotioid'] == $kitsObrigatorio[$kitMaisProximo]['kiosotioid_kit']) {
															$kitValido = false;
														}
													}

													if ($kitValido == true) {

														// Insere o Kit encontrado
														$listaKitsInstalados[] = array(
															'ositotioid'		=> $kitsObrigatorio[$kitMaisProximo]['kiosotioid_kit'],
															'instalado'			=> false
														);
													}

													// TRATA OBRIGAT�RIOS

													// Se existir motivos na an�lise
													if (count($listaMotivosI) > 0) {

														// Remove ele da lista de motivos
														for($x = 0; $x < count($listaMotivosI); $x++) {

															if (in_array($listaMotivosI[$x], $kitsObrigatorio[$kitMaisProximo]['motivos'])) {

																for($m = 0; $m < count($listaMotivos); $m++) {

																	if ($listaMotivos[$m]['ositotioid'] == $listaMotivosI[$x]) {

																		unset($listaMotivos[$m]);
																		sort($listaMotivos);
																		$m = count($listaMotivos);
																	}
																}
																unset($listaMotivosIB[$x]);
															}
														}
													}

													// Se existir atuadores na an�lise
													if (count($listaAtuadoresI) > 0) {

														// Remove ele da lista de motivos
														for($x = 0; $x < count($listaAtuadoresI); $x++) {

															if (in_array($listaAtuadoresI[$x], $kitsObrigatorio[$kitMaisProximo]['atuadores'])) {

																for($m = 0; $m < count($listaMotivos); $m++) {

																	if ($listaMotivos[$m]['otiattoid'] == $listaAtuadoresI[$x]) {

																		unset($listaMotivos[$m]);
																		sort($listaMotivos);
																		$m = count($listaMotivos);
																	}
																}
															}
														}
													}

													// Encontra o indice do mesmo Kit para itens n�o obrigat�rios
													$indeKitNObrigatorio = null;
													for ($n = 0; $n < count($kitsNObrigatorio); $n++) {

														if ($kitsNObrigatorio[$n]['kiosotioid_kit'] == $kitsNObrigatorio[$kitMaisProximo]['kiosotioid_kit']) {

															$indeKitNObrigatorio = $n;
														}
													}

													// TRATA N�O OBRIGAT�RIOS
													if ($indeKitNObrigatorio !== null) {

														sort($kitsNObrigatorio[$indeKitNObrigatorio]['motivos']);
														sort($listaMotivos);

														// Se existir motivos na an�lise
														if (count($listaMotivosI) > 0) {

															// Remove ele da lista de motivos
															for($x = 0; $x <= count($listaMotivosI); $x++) {

																if (in_array($listaMotivosI[$x], $kitsNObrigatorio[$indeKitNObrigatorio]['motivos'])) {

																	for($m = 0; $m < count($listaMotivos); $m++) {

																		if ($listaMotivos[$m]['ositotioid'] == $listaMotivosI[$x]) {
																			unset($listaMotivos[$m]);
																			unset($listaMotivosIB[$x]);
																			sort($listaMotivos);
																			break;
																		}
																	}
																}

															}

														}
													}

												}
											}
										}


										/**
										 * An�lise de kits de instala��o considerando somente itens b�sico
										 * (Identico ao processo acima, considerando apenas itens com visualiza��o do tipo 'B').
										 */
										 if (count($listaMotivosIB)) {

											$rsKitsInstalacao = $this->buscaKitsInstalacao(
												$ostoid,
												implode(',', $listaMotivosIB),
												''
											);

											if (pg_num_rows($rsKitsInstalacao) > 0) {

												// Montagem dos kits para avalia��o
												while ($kit = pg_fetch_object($rsKitsInstalacao)) {

													// Trata espa�os em branco
													if (empty($kit->motivo)) {
														$motivosKit = array();
													}
													else {
														$motivosKit = explode(',', $kit->motivo);
													}

													// Monta listas de obrigatorios e n�o obrigatorios
													if ($kit->kiosobrigatorio == 't') {

														$kitsObrigatorio[] = array(
															'kiosotioid_kit'	=> $kit->kiosotioid_kit,
															'motivos'			=> $motivosKit
														);
													}
													else {

														$kitsNObrigatorio[] = array(
															'kiosotioid_kit'	=> $kit->kiosotioid_kit,
															'motivos'			=> $motivosKit
														);
													}

													// Ordena as listas
													sort($kitsObrigatorio);
													sort($kitsNObrigatorio);
												}

												sort($listaMotivosIB);

												// An�lise dos kits (itens Obrigat�rios), aqui eliminamos os kits que n�o ser�o usados.
												for($i = 0; $i < count($kitsObrigatorio); $i++) {

													$diferencaKitMotivos = 0;

													sort($kitsObrigatorio[$i]['motivos']);

													$diferencaKitMotivos   = count(array_diff($kitsObrigatorio[$i]['motivos'],   $listaMotivosIB));

													if ($diferencaKitMotivos > 0) {

														// Remove o mesmo kit da lista de itens n�o obrigat�rios
														for ($j = 0; $j < count($kitsNObrigatorio); $j++) {

															if ($kitsNObrigatorio[$j]['kiosotioid_kit'] == $kitsObrigatorio[$i]['kiosotioid_kit']) {

																unset($kitsNObrigatorio[$j]);
																sort($kitsObrigatorio);
															}
														}

														unset($kitsObrigatorio[$i]);
														sort($kitsObrigatorio);
														$i = 0;
													}
												}

												// Se depois da an�lise eliminat�ria existir algum kit,
												if (count($kitsObrigatorio) > 0) {

													sort($kitsObrigatorio);
													sort($kitsNObrigatorio);

													$diferencaMotivosObrigatorios	= 0;

													$diferencaMotivosInversa		= 0;

													$pesoDiferencaInversa			= null;
													$diferencaInversa				= 0;
													$kitMaisProximo					= null;


													// Efetua a an�lise para saber se exite algum kit que contempla a lista de
													// itens que sobraram da ordem de servi�o
													for ($k = 0; $k < count($kitsObrigatorio); $k++) {

														$insereKit = false;

														sort($kitsObrigatorio[$k]['motivos']);

														sort($listaMotivosIB);


														if (count($listaMotivosI) > 0) {
															$diferencaMotivosObrigatorios   = count(array_diff($kitsObrigatorio[$k]['motivos'],   $listaMotivosIB));
															$diferencaMotivosInversa		= count(array_diff($listaMotivosIB, $kitsObrigatorio[$k]['motivos']));
														}


														// Se o kit n�o tem difer�n�a, ent�o entra para an�lise dos itens da O.S.
														if ($diferencaMotivosObrigatorios == 0) {

															$diferencaInversa = 0;
															// An�lise das diferen�as

															if (count($listaMotivosIB) > 0) {

																$diferencaInversa = $diferencaMotivosInversa;
															}

															// An�lisa o peso entre as diferen�as
															if ($pesoDiferencaInversa === null) {

																$kitMaisProximo		  = $k;
																$pesoDiferencaInversa = $diferencaInversa;

																if (count($kitsObrigatorio)-1 == $k) {

																	$insereKit = true;
																}
															}
															else if ($diferencaInversa < $pesoDiferencaInversa) {

																$kitMaisProximo		  = $k;
																$pesoDiferencaInversa = $diferencaInversa;

																if ($k == count($kitsObrigatorio)-1) {

																	$insereKit = true;
																}
															}
															else {


																if ($k == count($kitsObrigatorio)-1) {

																	$insereKit = true;
																}
															}
														}

														// Inser��o do kit
														if ($insereKit == true && $kitMaisProximo !== null) {

															$kitValido = true;

															// Verifica se o kit j� esta inserido, se estiver, n�o insere
															foreach($listaKitsInstalados as $kit) {

																if ($kit['ositotioid'] == $kitsObrigatorio[$kitMaisProximo]['kiosotioid_kit']) {
																	$kitValido = false;
																}
															}

															if ($kitValido == true) {

																// Insere o Kit encontrado
																$listaKitsInstalados[] = array(
																	'ositotioid'		=> $kitsObrigatorio[$kitMaisProximo]['kiosotioid_kit'],
																	'instalado'			=> false
																);

															}

															// TRATA OBRIGAT�RIOS
															sort($listaMotivosIB);

															// Se existir motivos na an�lise
															if (count($listaMotivosIB) > 0) {

																// Remove ele da lista de motivos
																for($x = 0; $x < count($listaMotivosIB); $x++) {

																	if (in_array($listaMotivosIB[$x], $kitsObrigatorio[$kitMaisProximo]['motivos'])) {

																		for($m = 0; $m < count($listaMotivos); $m++) {

																			if ($listaMotivos[$m]['ositotioid'] == $listaMotivosIB[$x]) {

																				unset($listaMotivos[$m]);
																				sort($listaMotivos);
																				break;
																			}
																		}
																	}
																}
															}


															// Encontra o indice do mesmo Kit para itens n�o obrigat�rios
															$indeKitNObrigatorio = null;
															for ($n = 0; $n < count($kitsNObrigatorio); $n++) {

																if ($kitsNObrigatorio[$n]['kiosotioid_kit'] == $kitsNObrigatorio[$kitMaisProximo]['kiosotioid_kit']) {

																	$indeKitNObrigatorio = $n;
																}
															}
															sort($kitsNObrigatorio[$indeKitNObrigatorio]['motivos']);

															// TRATA N�O OBRIGAT�RIOS
															// Se exitir atuadores na an�lise
															if ($indeKitNObrigatorio !== null) {

																sort($listaMotivosIB);
																sort($kitsNObrigatorio[$indeKitNObrigatorio]['motivos']);

																// Se existir motivos na an�lise
																if (count($listaMotivosIB) > 0) {

																	// Remove ele da lista de motivos
																	for($x = 0; $x < count($listaMotivosIB); $x++) {

																		if (in_array($listaMotivosIB[$x], $kitsNObrigatorio[$indeKitNObrigatorio]['motivos'])) {

																			for($m = 0; $m < count($listaMotivos); $m++) {

																				if ($listaMotivos[$m]['ositotioid'] == $listaMotivosIB[$x]) {

																					unset($listaMotivos[$m]);
																					sort($listaMotivos);
																					break;
																				}
																			}
																		}
																	}

																}
															}
														}
													}
												}
											}
										 }


									}
								}
						 	 } // FIM temA e temB

 							 /**
 							  * REGRA :  An�lise dos itens restantes que n�o entraram em um kit.
 							  * Verificar se inda existe itens no array_inicial  para fazer analise de itens avulsos.
 							  * Se existir (VERIFICA��O DE KITS DO TIPO AVULSOS (SOBRA) Percorrer o array_inicial Executar
 							  * a CONSULTA 2 para cada item restante do array.
 							  *
 							  * Verifica se o Kit j� n�o est� cadastrado na O.S.
 							  *
 							  * SE N�O EXISTIR Inclui o Kit no array kits_instalacao flega ele como novo.
 							  * FIM SE
 							  * Remove os itens do array_inicial que compoem o Kit
 							  */

 							 sort($listaMotivos);
 							 $totalListaMotivos = count($listaMotivos);

 							 if ( $totalListaMotivos > 0 ) {
 							 	// Se ainda houver itens no array inicial

 							 	for($l = 0; $l < $totalListaMotivos; $l++) {
 							 		// Para cada motivo restante, buscar kit avulso

 							 		$rskitAvulso    = $this->buscaKitAvulso( $listaMotivos[$l]['ositotioid'] );
 							 		$itemkitAvulso  = pg_fetch_object($rskitAvulso);

 							 		$kiosotioid_kit = $itemkitAvulso->kiosotioid_kit; // C�digo do kit

 							 		if (!empty($kiosotioid_kit) && $kiosotioid_kit > 0) {
 							 			// Se este motivo possuir um kit avulso

 							 			// Verifica se o kit j� n�o foi inserido
 							 			$kitInserido = false;

 							 			for($y = 0; $y < count($listaKitsInstalados); $y++) {

 							 				if ($listaKitsInstalados[$y]['ositotioid'] == $kiosotioid_kit) {

 							 					$kitInserido = true;
 							 				}
 							 			}

 							 			if ($kitInserido === false) {

 							 				// Adiciona kit
						 					$listaKitsInstalados[] = array(
						 						'ositotioid'		=> $kiosotioid_kit,
												'instalado'			=> false
						 					);

						 					// Remove motivo da lista de an�lise
						 					unset( $listaMotivos[$l] );
 							 			}
 							 		}
 							 	}
 							 }
 						}


 						/**
 						 * Se houver kits na lista
 						 */
 						 if (count($listaKitsInstalados) > 0) {

							pg_query($this->conn, 'BEGIN;');
 						 	for ($i = 0; $i < count($listaKitsInstalados); $i++) {

 						 		if ($listaKitsInstalados[$i]['instalado'] === false) {

 						 			$listaKitsInseridos[] = $listaKitsInstalados[$i]['ositotioid'];
 						 			$this->incluirKitInstalacao($listaKitsInstalados[$i]['ositotioid']);
 						 		}
 						 	}
 						 }
 						pg_query($this->conn, 'COMMIT;');
 						return $listaKitsInseridos;
 					}

 				} else {

 					/*
 					 * Buscar todos os motivos do tipo 'Assist�ncia' na OS que est� sendo analisada, que
 					 * estejam conclu�dos. Armazenar esses motivos num ARRAY de motivos a ser analisado.
 					 */

 					//Lista de retorno com id(s) do(s) kit(s) inserido(s) por este processo
 					$listaKitsInseridos = array();

 					//Lista de motivos da Ordem de Servi�o do tipo 'Assist�ncia'
 					$listaMotivosAssistencia = array();

 					$rsMotivosAssistencia = $this->buscaMotivosOSAssistencia();

 					/*
 					 * Se houver registros de motivos retornados coloca na lista 'listaMotivosAssistencia'
 					 * Sen�o interrompe o processamento, pois n�o h� motivos cadastrados para a OS.
 					 */
 					if(pg_num_rows($rsMotivosAssistencia) > 0){
 						$listaMotivosAssistencia = pg_fetch_all($rsMotivosAssistencia);
 					} else {
 						return $listaKitsInseridos;
 					}

 					/*
 					 * Lista de kits j� incluidos/instalados da ordem de servi�o
 					 * Extrai c�digos dos kits retornados para a lista 'listaKitsExistentesOS'
 					 */
 					$listaKitsExistentesOS = array();

 					$rsKitsExistentesOS = $this->buscaKitsOrdemServico();

 					if(pg_num_rows($rsKitsExistentesOS) > 0){
 						while(($row = pg_fetch_assoc($rsKitsExistentesOS)) != false){
 							array_push($listaKitsExistentesOS, $row['ositotioid']);
 						}
 					}


 					//Lista de kits que ser�o processados/analisados posteriormente
 					$listaKitsAssistencia = array();


 					/*
 					 * Se dentro dos motivos retornados, tiver motivo que seja um KIT, ou seja, que o campo
 					 * 'otitipo_kit' for do tipo 'S � ASSISTENCIA', armazenar esses motivos num ARRAY de Kits
 					 * que ser� usado em todo o processo, e remover esse motivo da lista de Motivos que ser�o
 					 * analisados nas pr�ximas etapas.
 					 */
 					if(is_array($listaMotivosAssistencia)){
	 					foreach($listaMotivosAssistencia as $key => $motivoAssistencia){

	 						$otitipo_kit = $motivoAssistencia['otitipo_kit'];

	 						if($otitipo_kit == 'S'){

	 							$ositotioid = $motivoAssistencia['ositotioid'];

	 							if(!in_array($ositotioid, $listaKitsAssistencia)) {
	 								array_push($listaKitsAssistencia, $ositotioid);
	 							}

	 							unset($listaMotivosAssistencia[$key]);
	 						}
	 					}
 					}

 					/* Se houver motivos do tipo ASSISTENCIA para analisar, para cada motivo buscar o KIT que
 					 * o motivo esta inclu�do. Para isso basta consultar na tabela 'kit_instalacao_ordem_servico'
 					 * usando como filtro o campo 'kiosotioid_item' como sendo o motivo que esta sendo analisado,
 					 * desde que o item esteja ativo (campo 'kiosdt_exclusao = NULL')
					 */
 					if(is_array($listaMotivosAssistencia)){
	 					foreach($listaMotivosAssistencia as $key => $motivoAssistencia){

	 						$ositotioid = $motivoAssistencia['ositotioid'];

	 						if(!empty($ositotioid)){

	 							$dadosKit = $this->buscaKitMotivoAssistencia($ositotioid);

	 							/*
	 							 * Se encontrou dados do kit em que o motivo est� inclu�do, pega a chave
	 							 * do kit 'kiosotioid_kit' e adiciona na lista de kits (a menos que
	 							 * este estiver vazio ou j� existir no array de kits).
	 							 */
	 							if(is_array($dadosKit))
	 							{
	 								foreach($dadosKit as $kit){
		 								$kiosotioid_kit = $kit['kiosotioid_kit'];

		 								if(!empty($kiosotioid_kit) && !in_array($kiosotioid_kit, $listaKitsAssistencia)){
		 									array_push($listaKitsAssistencia, $kiosotioid_kit);
		 								}
	 								}
	 							}
	 						}
	 					}
 					}

 					/*
 					 * Uma vez finalizado, os KITs encontrados dever�o ser inclu�dos na Ordem de Servico com status
 					 * 'C � CONCLUIDO'. Usar a fun��o 'incluirKitInstalacao' para incluir o KIT. IMPORTANTE: Se for
 					 * um KIT que j� existe na OS, esse n�o dever� ser inclu�do novamente.
 					 */
 					if(count($listaKitsAssistencia) > 0){

 						pg_query($this->conn, 'BEGIN;');

	 					for($i = 0; $i < count($listaKitsAssistencia); $i++){

	 						$ositotioid = $listaKitsAssistencia[$i];

	 						if(!in_array($ositotioid, $listaKitsExistentesOS)){

	 							$this->incluirKitInstalacao($ositotioid);

	 							$listaKitsInseridos[] = $ositotioid;
	 						}
	 					}

	 					pg_query($this->conn, 'COMMIT;');
 					}

 					return $listaKitsInseridos;
 				}

 			} catch(Exception $e) {
 				pg_query($this->conn, 'ROLLBACK;');
 				throw $e;
 			}

 		} else {
 			throw new Exception("Favor informar uma ordem de servi�o");
 		}
 	}


 	/**
 	 * CONSULTA 1
 	 *
 	 * Usada para buscar os motivos da ordem de sevi�o.
 	 *
 	 * @param $filtraTpKitInstNulo boolean - Flag para indicar se a consulta de
 	 * 		motivos deve ou n�o filtrar o tipo do kit instala��o vazio na consulta.
 	 * 		Esta flag existe devido ao fato de que kits assist�ncia s�o verificados
 	 * 		utilizando a mesma consulta e eles n�o possuem este campo cadastrado.
 	 *
 	 * @return pg_result Resultado da Consulta 1.]
 	 * @throws Exception
 	 */
 	public function consultaMotivosOS($filtraTpKitInstVazio = true) {

 		if (!empty($this->ordoid)) {

 			// Monta consulta 1
 			$sqlBuscaMotivos = "
			SELECT
				ordoid,
				ordstatus,
				ositeqcoid,
				eqcdescricao,
				ostoid,
				ostdescricao,
				otidescricao,
				ositstatus,
				ositotioid,
				otiattoid,
				otitipo_kit_instalacao,
				otitipo_kit_visualizacao,
				CASE
					WHEN otitipo_kit_instalacao = 'V' THEN
						(
						SELECT
							kiosotioid_kit
						FROM
							kit_instalacao_ordem_servico
							JOIN os_tipo_item ON otioid = kiosotioid_kit
						WHERE
							kiosotioid_item = ositotioid
							AND otitipo_kit = 'V'
							AND kiosdt_exclusao IS NULL
						)
					ELSE
						NULL
				END AS kit_instalacao
			FROM
				ordem_servico
				JOIN ordem_servico_item ON ordoid=ositordoid
				JOIN os_tipo_item ON otioid=ositotioid
				JOIN os_tipo ON otiostoid=ostoid
				JOIN equipamento_classe ON eqcoid=ositeqcoid
			WHERE
				ordoid=".$this->ordoid."
				AND ositexclusao IS NULL
				AND ositstatus = 'C'";

 			if($filtraTpKitInstVazio){
				$sqlBuscaMotivos .= " AND otitipo_kit_instalacao <> ''";
 			}

			$sqlBuscaMotivos .= " ORDER BY otitipo_kit_instalacao;";

			$rsBuscaMotivos = pg_query($this->conn, $sqlBuscaMotivos);

			if (!$rsBuscaMotivos) {
				throw new Exception("Erro ao executar consulta 1.");
			}
			else {
				return $rsBuscaMotivos;
			}
 		}
 		else {
 			throw new Exception("Favor informar  uma ordem de servi�o");
 		}
 	}


 	/**
 	 * Verifica se a ordem de servi�o � do tipo assist�ncia.
 	 * @return boolean True, se o tipo da ordem for igual a 4 (Assist�ncia). False se n�o for.
 	 */
 	public function verificaOSTipoAssistencia() {

 		if (!empty($this->ordoid)) {

 			$rsMotivos = $this->consultaMotivosOS(false);
 			$retorno   = false;

 			while ($motivoOS = pg_fetch_object($rsMotivos)) {
 				if ($motivoOS->ostoid == 4) {
 					$retorno = true;
 				}
 			}

 			return $retorno;
 		}
 		else {
 			throw new Exception("Favor informar  uma ordem de servi�o");
 		}
 	}


 	/**
 	 * Busca por Kits j� inseridos na ordem de servi�o
 	 * @return pg_result
 	 * @throws Exception
 	 */
 	public function buscaKitsOrdemServico() {

 		if (!empty($this->ordoid)) {

 			$sqlBuscaKitsOrdemServico = "
 			SELECT
				ositotioid
			FROM
				ordem_servico_item,
				kit_instalacao_ordem_servico
			WHERE
				ositotioid = kiosotioid_kit
 				AND ositstatus = 'C'
				AND ositordoid  = ".$this->ordoid.
				" AND kiosdt_exclusao IS NULL;";

 			$rsBuscaKitsOrdemServico = pg_query($this->conn, $sqlBuscaKitsOrdemServico);
 			if (!$rsBuscaKitsOrdemServico) {
 				throw new Exception("Erro ao buscar kits da ordem de servi�o.");
 			}
 			else {
 				return $rsBuscaKitsOrdemServico;
 			}
 		}
 		else {
 			throw new Exception("Favor informar  uma ordem de servi�o");
 		}
 	}

 	/**
 	 * Efetua a busca do kit avulso pelo motivo
 	 * @param integer $otioid OID do motivo
 	 * @return pg_result Retorna o resultado da consulta kit avulso
 	 * @throws Exception
 	 */
 	public function buscaKitAvulso($otioid) {

 		if (!empty($this->ordoid)) {

 			$sqlBuscaKitAvulso = "
 			SELECT
				kiosotioid_kit
			FROM
				kit_instalacao_ordem_servico
				INNER JOIN os_tipo_item ON otioid = kiosotioid_kit
			WHERE
				kiosotioid_item = $otioid
				AND otitipo_kit = 'V'
				AND kiosdt_exclusao IS NULL;";

 			$rsBuscaKitAvulso = pg_query($this->conn, $sqlBuscaKitAvulso);

 			if (!$rsBuscaKitAvulso) {
 				throw new Exception("Erro ao efetuar a busca pelo kit avulso.");
 			}
 			else {
 				return $rsBuscaKitAvulso;
 			}
 		}
 		else {
 			throw new Exception("Favor informar uma ordem de servi�o");
 		}
 	}

 	/**
 	 * Consulta 3 - Busca kits e as listas de Atuadores e Motivos
 	 * @param integer $ostoid C�digo do tipo da ordem de servi�o
 	 * @param string $motivos Lista de motivos separados por virgula
 	 * @param string $atuadores Lista de atuadores separados por virgula
 	 * @throws Exception
 	 */
 	public function buscaKitMotivoAtuador($ostoid, $motivos, $atuadores) {

 		$sqlMotivos = '';
 		$sqlAtuadores = '';

 		if (empty($ostoid)) {
 			throw new Exception("� necessario informar um ostoid para a Consulta 3.");
 		}

 		if (!empty($motivos)) {
 			$sqlMotivos = " AND kiosotioid_item IN($motivos) ";
 		}

 		if (!empty($atuadores)) {
 			$sqlAtuadores = "
 			AND kiosotioid_kit IN(
				SELECT
					kiosotioid_kit
				FROM
					kit_instalacao_ordem_servico
					INNER JOIN os_tipo_item ON otioid = kiosotioid_kit
				WHERE kiosdt_exclusao IS NULL
				AND otidt_exclusao IS NULL
				AND	otitipo_kit = 'A'  -- Tipo do Kit
				AND kiosattoid_item IN($atuadores) -- Lista de Atuadores a ser pesquisado
			)";
 		}

 		if (!empty($this->ordoid)) {

 			$sqlBuscaKitMotivoAtuador = "
 			SELECT
				kiosotioid_kit,
				concatena(kiosotioid_item) AS motivo, -- Lista de Motivos que existem nos Kits retornados
				concatena(kiosattoid_item) AS atuador -- Lista de atuadores que existem nos Kits retornados
			FROM
				kit_instalacao_ordem_servico
				INNER JOIN os_tipo_item ON otioid = kiosotioid_kit
			WHERE
				kiosdt_exclusao IS NULL
			AND
				kiosotioid_kit IN(
					SELECT
						kiosotioid_kit
					FROM
						kit_instalacao_ordem_servico
						INNER JOIN os_tipo_item ON otioid = kiosotioid_kit
					WHERE
						otitipo_kit = 'A' -- Tipo do Kit
						AND kiosdt_exclusao IS NULL
						AND otidt_exclusao IS NULL
						AND otiostoid = $ostoid
						$sqlMotivos
						$sqlAtuadores
				)
			GROUP BY
				kiosotioid_kit
 			";

 			$rsBuscaKitMotivoAtuador = pg_query($this->conn, $sqlBuscaKitMotivoAtuador);
 			if (!$rsBuscaKitMotivoAtuador) {
 				throw new Exception("Erro ao executar busca de listas de motivo e atuador, Consulta 3");
 			}
 			else {
 				return $rsBuscaKitMotivoAtuador;
 			}
 		}
 		else {
 			throw new Exception("Favor informar  uma ordem de servi�o");
 		}
 	}


 	/**
 	 * Busca Kits com a lista de itens obrigat�rios e n�o obrigat�rios
 	 *
 	 * @param integer $ostoid Tipo da Ordem de Servi�o
 	 * @param string $atuadores Lista de atuadores separados por vigula, n�o � um par�metro obrigat�rio
 	 * @param string $motivos Lista de motivos separados por vigula
 	 * @return pg_result
 	 * @throws Exception
 	 */
 	public function buscaKitsInstalacao($ostoid, $motivos, $atuadores) {

 		// Se for passado o par�metro atuadores, ser� adicionado mais clausulas aqui
 		$sqlAtuadores = '';
 		$ordeqcoid 	  = '';

 		if (empty($this->ordoid)) {
 			throw new Exception("Ordem de servi�o n�o informada");
 		}

 		if (empty($ostoid)) {
 			throw new Exception("� necess�rio informar o tipo da ordem de servi�o");
 		}

 		if (empty($motivos)) {
 			throw new Exception("� necess�rio informar a lista de motivos separados por vigula");
 		}

 		if (!empty($atuadores)) {

 			$sqlAtuadores = "
 			AND kiosotioid_kit IN(
				SELECT
					kiosotioid_kit
				FROM
					kit_instalacao_ordem_servico
					INNER JOIN os_tipo_item ON otioid = kiosotioid_kit
				WHERE
					otitipo_kit = 'I'  -- Tipo do Kit
					AND kiosdt_exclusao IS NULL
					AND otiostoid = $ostoid -- Tipo do Kit
					AND kiosattoid_item IN($atuadores) -- Lista de Atuadores a ser pesquisado
			)";
 		}

 		// Busca a classe na ordem de servi�o
 		$sqlBuscaClasseOrdemServico = "
 		SELECT
			coneqcoid
		FROM
			ordem_servico
			INNER JOIN contrato ON connumero = ordconnumero
		WHERE
			ordoid = ".$this->ordoid."
 		";
 		$rsBuscaClasseOrdemServico = pg_query($this->conn, $sqlBuscaClasseOrdemServico);
 		if (!$rsBuscaClasseOrdemServico) {
 			throw new Exception("Erro ao buscar classe na ordem de servi�o");
 		}
 		else {
 			if (pg_num_rows($rsBuscaClasseOrdemServico) > 0) {
 				$ordemServico = pg_fetch_object($rsBuscaClasseOrdemServico);
 			}
 			else {
 				throw new Exception("Erro ao buscar classe na ordem de servi�o, sem resultados");
 			}
 		}

 		$sqlBuscaKitInstalacao = "
 		SELECT
			kiosotioid_kit,
			kiosobrigatorio,
			concatena(kiosotioid_item) AS motivo, -- Lista de Motivos que existem nos Kits retornados
			concatena(kiosattoid_item) AS atuador -- Lista de atuadores que existem nos Kits retornados
		FROM
			kit_instalacao_ordem_servico
			INNER JOIN os_tipo_item ON otioid = kiosotioid_kit
		WHERE
			kiosotioid_kit IN(
				SELECT
					kiosotioid_kit
				FROM
					kit_instalacao_ordem_servico
					INNER JOIN os_tipo_item ON otioid = kiosotioid_kit
				WHERE
					otitipo_kit = 'I' -- Tipo do Kit
					AND kiosdt_exclusao IS NULL
					AND otiostoid = $ostoid
					AND kiosotioid_item IN($motivos) -- Lista de motivos a ser pesquisado

					-- Se existir motivos com visualiza��o igual a atuador
					$sqlAtuadores
			)
			AND kiosdt_exclusao IS NULL
			AND kiosotioid_kit IN (
			SELECT
				kiosotioid_kit
			FROM
				kit_instalacao_ordem_servico_classe
			WHERE
				kioscdt_exclusao IS NULL
				AND kiosceqcoid = ".$ordemServico->coneqcoid."
			)
		GROUP BY
			kiosotioid_kit,
			kiosobrigatorio
		ORDER BY
			kiosotioid_kit
 		";
 		$rsBuscaKitInstalacao = pg_query($this->conn, $sqlBuscaKitInstalacao);
 		if (!$rsBuscaKitInstalacao) {
 			throw new Exception("Erro ao buscar lista de kits para instala��o");
 		}
 		else {
 			return $rsBuscaKitInstalacao;
 		}
 	}

 	/**
 	 * Insere kit instala��o para a ordem de servi�o.
 	 *
 	 * @param integer $ostoid OID do Kit de instala��o a inserir
 	 * @return boolean Em caso de sucesso retorna true
 	 * @throws Exception
 	 */
 	public function incluirKitInstalacao($otioid) {

 		// Valida entrada
 		if (empty($otioid)) {
 			throw new Exception("� necessario informar o c�digo do kit para inserir");
 		}

 		// Valida preenchimento da ordem de servi�o
 		if (!empty($this->ordoid)) {

 			$sqlIncluirKitInstalacao = "
 			INSERT INTO
				ordem_servico_item
				(
					ositotioid,
					ositordoid,
					ositobs,
					ositstatus,
					ositeqcoid
				)
			VALUES
				(
					$otioid,
					".$this->ordoid.",
					'Kit inclu�do automaticamente',
					'C',
					(
					SELECT
						coneqcoid
					FROM
						ordem_servico
						INNER JOIN contrato ON connumero = ordconnumero
					WHERE
						ordoid = ".$this->ordoid."
					LIMIT 1
					)
				)
			 RETURNING
					ositoid
 			";
 			$rsIncluirKitInstalacao = pg_query($this->conn, $sqlIncluirKitInstalacao);
 			if (!$rsIncluirKitInstalacao) { // Erro de SQL
 				throw new Exception("Erro ao tentar inserir kit de instala��o.");
 			}
 			else if (pg_affected_rows($rsIncluirKitInstalacao) == 0) { // Erro de SQL
 				throw new Exception("Erro ao tentar inserir kit de instala��o.");
 			}
 			else {

 				if (pg_num_rows($rsIncluirKitInstalacao) > 0) {

 					$cdUsuario 	= isset($_SESSION['usuario']['oid']) ? $_SESSION['usuario']['oid'] : 4873 ;
 					$clioid 	= null;

 					$sqlBuscaClioid = "
 					SELECT
 						conclioid
 					FROM
 						ordem_servico
 						INNER JOIN contrato ON connumero = ordconnumero
 					WHERE
 						ordoid = ".$this->ordoid."
 					";
 					$rsBuscaClioid = pg_query($this->conn, $sqlBuscaClioid);
 					if ($rsBuscaClioid && pg_num_rows($rsBuscaClioid) > 0) {

 						$clioid = pg_fetch_object($rsBuscaClioid);
 					}

	 				$item 		= pg_fetch_object($rsIncluirKitInstalacao);
	 				$observacao = "Gera��o de comiss�o automatica dos Kits de Instala��o";

	 				$parametros = "\"".$item->ositoid."\" \"".$this->ordoid."\" \"".$clioid->conclioid."\" \"".$cdUsuario."\" \"".$observacao."\"";
	 				$sqlGeraComissao = "SELECT comissao_tecnica_i('".$parametros."') AS retorno";

	 				$rsGeraComissao = pg_query($this->conn, $sqlGeraComissao);
	 				if (!$rsGeraComissao) {
	 					throw new Exception("Erro ao gerar comiss�o do kit $otioid.");
	 				}
 				}

 				return true;
 			}
 		}
 		else {
 			throw new Exception("Nenhuma ordem de servi�o informada.");
 		}
 	}

 	/**
 	 * Busca motivos do tipo 'assist�ncia' da Ordem de Servi�o analisada
 	 * que estejam com status 'C' - conclu�do
 	 * @return pg_result Retorna o resultado da consulta motivos assist�ncia
 	 * @throws Exception
 	 */
 	public function buscaMotivosOSAssistencia() {

 		if (!empty($this->ordoid)) {

 			$sqlBuscaMotivoAssist = " SELECT ordoid, ordstatus, ositeqcoid, eqcdescricao, ostoid, ostdescricao,
									  otidescricao, ositstatus, ositotioid, otiattoid, otitipo_kit
									  FROM ordem_servico
									  INNER JOIN ordem_servico_item ON ordoid	 = ositordoid
									  INNER JOIN os_tipo_item 		ON otioid 	 = ositotioid
									  INNER JOIN os_tipo 			ON otiostoid = ostoid
									  INNER JOIN equipamento_classe ON eqcoid	 = ositeqcoid
									  WHERE otiostoid = 4
									  AND ositstatus  = 'C'
									  AND ositexclusao IS NULL
									  AND ordoid = ".$this->ordoid.";";

 			$rsBuscaMotivoAssist = pg_query($this->conn, $sqlBuscaMotivoAssist);

 			if (!$rsBuscaMotivoAssist) {
 				throw new Exception("Erro ao efetuar a busca pelos motivos do tipo 'Assist�ncia'.");
 			} else {
 				return $rsBuscaMotivoAssist;
 			}
 		}
 		else {
 			throw new Exception("Favor informar uma ordem de servi�o");
 		}
 	}

 	/**
 	 * Busca o(s) kit(s) em que o motivo do tipo assistencia est� inclu�do.
 	 * @return array Array contendo dados do(s) kit(s) correspondente(s) ao motivo
 	 * @throws Exception
 	 */
 	public function buscaKitMotivoAssistencia($ositotioid) {

 		if (!empty($ositotioid)) {

 			$arrKitMotivo = array();

 			$sqlBuscaKitMotivoAssist = " SELECT kiosotioid_kit
 										 FROM kit_instalacao_ordem_servico
										 WHERE kiosotioid_item = $ositotioid
										 AND kiosdt_exclusao IS NULL;";

 			$rsBuscaKitMotivoAssist = pg_query($this->conn, $sqlBuscaKitMotivoAssist);

 			if (!$rsBuscaKitMotivoAssist) {
 				throw new Exception("Erro ao efetuar a busca do kit de motivo assist�ncia (ositotioid: $ositotioid).");
 			} else {

 				$arrKitMotivo = pg_fetch_all($rsBuscaKitMotivoAssist);

 				return $arrKitMotivo;
 			}
 		}
 		else {
 			throw new Exception("Favor informar o par�metro 'ositotioid'.");
 		}
 	}
 }