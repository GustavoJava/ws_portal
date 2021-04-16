<?php

include _SITEDIR_ . 'lib/phpMailer/class.phpmailer.php';

require _MODULEDIR_ . '/Financas/DAO/FinFaturamentoUnificadoDAO.php';

class FinFaturamentoUnificado extends FinFaturamentoUnificadoDAO {

    private $dao;
    public $data;
    public $doc;
    public $tipo;
    public $cliente;
    public $tipo_contrato;
    public $contrato;
    public $placa;
    public $obroids;
    public $feedback;
    public $acao;
    public $pid_processo;

    public function FinFaturamentoUnificado($shell_exec) {
        global $conn;
		
		$this->dao = new FinFaturamentoUnificadoDAO($conn);
		
		if (!$shell_exec) {

			$this->dao->data 			= (!empty($_POST['frm_data'])) 				? $_POST['frm_data'] 					: '';
			$this->dao->doc 			= ($this->onlyNumbers($_POST['frm_doc'])>0) ? $this->onlyNumbers($_POST['frm_doc'])	: 0;
			$this->dao->tipo 			= (!empty($_POST['frm_tipo'])) 				? $_POST['frm_tipo'] 					: '';
			$this->dao->cliente 		= (!empty($_POST['frm_cliente'])) 			? $_POST['frm_cliente'] 				: '';
			$this->dao->tipo_contrato 	= (!empty($_POST['frm_tipo_contrato'])) 	? $_POST['frm_tipo_contrato'] 			: '';
			$this->dao->contrato 		= (!empty($_POST['frm_contrato'])) 			? $_POST['frm_contrato'] 				: '';
			$this->dao->placa 			= (!empty($_POST['frm_placa'])) 			? $_POST['frm_placa'] 					: '';
			$this->dao->obroids 		= (isset($_POST['obroid'])) 				? $_POST['obroid'] 						: '';
			$this->dao->usuario 		= ($_SESSION["usuario"]["oid"] > 0) 		? $_SESSION["usuario"]["oid"] 			: 1;
			$this->dao->acao 			= (isset($_POST['acaoForm'])) 				? $_POST['acaoForm'] 					: '';
			
			if (is_array($this->obroid)) {
				$this->dao->obroids = implode(",",$this->obroid);
			} elseif ($_POST['obroid_aux'] != "") {
				$this->dao->obroids = $_POST['obroid_aux'];
			} else {
				$this->dao->obroids = 0;
			}
			
			$this->dao->setarFiltros();
			
        } else {
			
			$res = pg_fetch_assoc($this->dao->recuperarParametros(false));
			
			$param = explode("|", $res['exfparametros']);
	
			
			$this->dao->data 			= $param[0];
			$this->dao->doc 			= $param[1];
			$this->dao->tipo 			= $param[2];
			$this->dao->cliente 		= $param[3];
			$this->dao->tipo_contrato 	= $param[4];
			$this->dao->contrato 		= $param[5];
			$this->dao->placa 			= $param[6];
			$this->dao->obroids 		= $param[7];
			$this->dao->usuario 		= $param[8];
			
			$this->dao->setarFiltros();
		}
    }

    public function index() {
    	
        $this->view = new stdClass();
    	$this->view->tiposContrato = $this->dao->tiposContratosAtivos();
    	
    	return $this->view;
    }
	
	public function verificarProcesso($finalizado) {
		try {
			
			// Verifica concorr�ncia entre processos
			$res = $this->dao->recuperarParametros($finalizado);
			
			if (pg_num_rows($res) > 0) {
				
				$param = pg_fetch_assoc($res);
				
				if ($param['exftipo_processo'] == 'F') {
					$msg = "Faturamento Iniciado por  ".$param['nm_usuario']." �s ".$param['inicio']." &nbsp;&nbsp;-&nbsp;&nbsp;  ".number_format($param['exfporcentagem'], 1, ',', '.')." % conclu�do.";
				} else {
					$msg = "Resumo Iniciado por =>  ".$param['nm_usuario']." �s ".$param['inicio'];			
				}
				
				return array(
						"codigo"	=> 2,
						"msg"		=>	$msg,
						"retorno"	=>	$param
				);
			} else {

				return array(
						"codigo"	=> 0,
						"msg"		=>	'',
						"retorno"	=>	$param
				);
			}
			
    	} catch (Exception $e) {
    		return array(
    				"codigo"	=> 1,
					"msg"		=> "Falha ao verificar concorr�ncia. Tente novamente.",
    				"retorno"	=> array()
    		);
	
					//INICIO
					$this->dao->finalizarProcesso('F');
	
					echo '\r\n'.$e->getMessage().'\r\n'; 
            
					//Pesquisar Par�metros
					$res = $this->dao->recuperarParametros(true);
					$param = pg_fetch_assoc($res);
                    
					//Envio de E-mail
					$mail  = new PHPMailer();
					$mail ->isSmtp();
					$mail ->From = "teste_desenv@sascar.com.br";
					$mail ->FromName = "Sascar";
                     
					$mail->ClearAllRecipients();
					$mail->Subject = "SASCAR - Falha na Gera��o de Resumo";
                     
					echo $msg = "O processo de gera��o de resumo iniciado �s ".$param['exfdt_inicio']." falhou com a seguinte descri��o de erro: ".$e->getMessage();
                    
					$mail->MsgHTML($msg);
					$mail->AddAddress($param['usuemail']);
                    
					$mail->Send();
					 
					$data_hora_atual = date("Y-m-d H:i:s");
					$fp = fopen("/tmp/log_faturamento_unificado","a");
					fputs ($fp,"$data_hora_atual - $msg\n");
					fclose($fp);
					//FIM
			
        }

	}


    public function verificarPendencias($todos="") {
        try {
			
			// Pesquisar Previs�o
			$resultado = $this->dao->verificarPendencias($todos);
			
			return array(
					"codigo"	=> 0,
					"msg"		=>	'',
					"retorno"	=>	$resultado
			);
    	} catch (Exception $e) {
    		return array(
    				"codigo"	=> 1,
					"msg"		=> "Falha ao verificar pend�ncias",
    				"retorno"	=> array()
    		);
    	}
    }
	
    
	public function consultarResumo() {
		try {
			
			// Pesquisar Previs�o
			$resultado = $this->dao->verificarPendencias(false);
			
			return array(
				"codigo"	=> 0,
				"msg"		=>	'Resumo consultado com sucesso.',
				"retorno"	=>	$resultado
			);
    	} catch (Exception $e) {
    		return array(
				"codigo"	=> 1,
				"msg"		=> "Falha ao consultar resumo",
				"retorno"	=> array()
    		);
    	}
	}
	
	
	public function limparResumo() {
		try {

			$this->dao->limparResumo();

			echo json_encode(array(
				"codigo"	=> 0,
				"msg"		=>	'Resumo limpo com sucesso.',
				"retorno"	=>	array()
			));
			exit();
    	} catch (Exception $e) {
    		echo json_encode(array(
    	    	"codigo"	=> 1,
				"msg"		=> "Falha ao limpar resumo",
				"retorno"	=> array()
    		));
    		exit();
    	}
	}
	
	public function prepararResumo() {
		
		// Preparar Faturamento
		try {
			$this->dao->prepararFaturamento('R');
			
			if (!is_dir(_SITEDIR_."faturamento")) {
				if (!mkdir(_SITEDIR_."faturamento", 0777)) {
					throw new Exception('Falha ao criar arquivo de log.');
				}
			}
			chmod(_SITEDIR_."faturamento", 0777);
			
			if (!$handle = fopen(_SITEDIR_."faturamento/resumo_faturamento", "w")) {
				throw new Exception('Falha ao criar arquivo de log.');
			}
			
			fputs($handle, "Resumo Iniciado\r\n");
			fclose($handle);
			chmod(_SITEDIR_."faturamento/resumo_faturamento", 0777);
			
			passthru("/usr/bin/php "._SITEDIR_."CronProcess/gerar_previsao_faturamento.php >> "._SITEDIR_."faturamento/resumo_faturamento 2>&1 &");

			return $this->verificarProcesso(false);
    	
    	} catch (Exception $e) {
    		
			$this->dao->finalizarProcesso('F');
		
    		return array(
				"codigo"	=> 1,
				"msg"		=>  $e->getMessage(),
				"retorno"	=> array()
			);
		}
	}
	
    public function gerarResumo() {
    	
    	try {

			// Gerar Pevis�o
			$this->dao->gerarPrevisoes();
	
	        $this->dao->finalizarProcesso('S');
			
			// Pesquisar Par�metros
			$res = $this->dao->recuperarParametros(true);
			$param = pg_fetch_assoc($res);
			
			// Envio de e-mail
			$mail  = new PHPMailer();
			$mail ->isSmtp();
			$mail ->From = "sascar@sascar.com.br";
			$mail ->FromName = "Sascar";
			
			$mail->ClearAllRecipients(); 
			$mail->Subject = "SASCAR - Faturamento Unificado";
			
			echo
			$msg = "O processo de gera��o de resumo iniciado �s ".$param['inicio']." foi finalizado com sucesso �s ".$param['termino'];
			
			$mail->MsgHTML($msg);
			$mail->AddAddress($param['usuemail']);
			
			$mail->Send();
			
    	} catch (Exception $e) {
		
			$this->dao->finalizarProcesso('F');
			
			echo '\r\n'.$e->getMessage().'\r\n';
    		
			// Pesquisar Par�metros
			$res = $this->dao->recuperarParametros(true);
			$param = pg_fetch_assoc($res);
			
			// Envio de e-mail
			$mail  = new PHPMailer();
			$mail ->isSmtp();
			$mail ->From = "sascar@sascar.com.br";
			$mail ->FromName = "Sascar";
			
			$mail->ClearAllRecipients();
			$mail->Subject = "SASCAR - Faturamento Unificado";
			
			echo
			$msg = "O processo de gera��o de resumo iniciado �s ".$param['inicio']." falhou com a seguinte descri��o de erro: ".$e->getMessage();
			
			$mail->MsgHTML($msg);
			$mail->AddAddress($param['usuemail']);
			
			$mail->Send();
    	}
    }
    

   	public function gerarRelatorioPendenciasCSV() {
    	try {
			
			$res = $this->dao->gerarRelatorioPendenciasCSV();
    		
			if (pg_num_rows($res) > 0) {
				$csv = "Data de refer�ncia;".$this->data.";;;\n";				
				$csv .= "CNPJ/CPF;Cliente;Valor Loca��o;Valor Monitoramento;Valor Total\n";
				
				$totalGeral = 0;
				while ($row = pg_fetch_array($res)) 
				{
					if ($row['tipo'] == 'J') {
						$csv .= self::applyMask($row['cnpj'], '99.999.999/9999-99') .';';
					} else {
						$csv .= self::applyMask($row['cpf'], '999.999.999-99') .';';
					}
					$csv .= str_replace(';', '', $row['cliente']).';';
					$csv .= $row['valor_locacao'] . ';';
					$csv .= $row['valor_monitoramento'] . ';';
					$csv .= $row['valor_total'] ."\n";
					$totalGeral += $row['valor_total'];
				}
				$csv .= ";;;Total Geral;".$totalGeral."\n";
				
				$resultado['file_path'] = '/var/www/docs_temporario/';
				$resultado['file_name'] = 'rel_pendencias_faturamento_' . date('dmYHis') . '.csv';
				file_put_contents($resultado['file_path'] . $resultado['file_name'], $csv);
				
				$arquivo = $resultado['file_path'] . $resultado['file_name'];
				$msg = 'Relat�rio de pend�ncias gerado com sucesso.';
				
			} else {
				$msg = 'Nenhum pend�ncia encontrado.';
			}
			
			// Pesquisar Previs�o
			$resultado = $this->dao->verificarPendencias(true);
			
			return array(
					"codigo"	=> 0,
					"msg"		=> $msg,
					"arquivo"	=> $arquivo,
					"retorno"	=> $resultado
			);
			
    	} catch (Exception $e) {
    		return array(
					"codigo"	=> 1,
					"msg"		=> 'Falha ao gerar relat�rio de pend�ncias.',
					"retorno"	=> array()
			);
    	}
    }
	
    
	public function gerarRelatorioPendenciasCSV2() {
		
    	$res = $this->dao->gerarRelatorioPendenciasCSV();
		
		if (pg_num_rows($res) > 0) {
			$csv = "Data de refer�ncia;".$this->data.";;;\n";				
			$csv .= "CNPJ/CPF;Cliente;Valor Loca��o;Valor Monitoramento;Valor Total\n";
			
			$totalGeral = 0;
			while ($row = pg_fetch_array($res)) 
			{
				if ($row['tipo'] == 'J') {
					$csv .= self::applyMask($row['cnpj'], '99.999.999/9999-99') .';';
				} else {
					$csv .= self::applyMask($row['cpf'], '999.999.999-99') .';';
				}
				$csv .= str_replace(';', '', $row['cliente']).';';
				$csv .= $row['valor_locacao'] . ';';
				$csv .= $row['valor_monitoramento'] . ';';
				$csv .= $row['valor_total'] ."\n";
				$totalGeral += $row['valor_total'];
			}
			$csv .= ";;;Total Geral;".$totalGeral."\n";
			
			$resultado['file_path'] = '/var/www/docs_temporario/';
			$resultado['file_name'] = 'rel_pendencias_faturamento_' . date('dmYHis') . '.csv';
			file_put_contents($resultado['file_path'] . $resultado['file_name'], $csv);
			
			return $resultado['file_path'] . $resultado['file_name'];
			
		}
		
		return false;
    }

    public function gerarRelatorioPreFaturamento() {
    	try {
			// Gerar Relat�rio
			$resultado = $this->dao->gerarRelatorioPreFaturamento();
	
	        return array(
					"codigo"	=> 0,
					"msg"		=> 'Relat�rio de pr�-faturamento gerado com sucesso.',
					"retorno"	=> $resultado
			);
	        
    	} catch (Exception $e) {
    		return array(
					"codigo"	=> 1,
					"msg"		=> 'Falha ao gerar relat�rio de pr�-faturamento.',
					"retorno"	=> array()
			);
    	}
    }

    public function gerarRelatorioPreFaturamentoCSV() {
    	
		try {
			// Gerar Relat�rio
			$resultado = $this->dao->gerarRelatorioPreFaturamento();
			
			$content = "Relat�rio Pr�-faturamento\n\n";
            $content .= "Cliente;Termo;Dt. Instala��o;Tipo;Status;Placa;Classe;Obrig. Financ.;Valor\n";
						
						
					while ($retorno = pg_fetch_array($resultado)) {
						$content .= str_replace(';', '', $retorno['clinome']).";";
						$content .= $retorno['connumero'].";";
						$content .= $retorno['condt_ini_vigencia'].";";
						$content .= str_replace(';', '', $retorno['tpcdescricao']).";";
						$content .= str_replace(';', '', $retorno['csidescricao']).";";
						$content .= str_replace(';', '', $retorno['veiplaca']).";";
						$content .= str_replace(';', '', $retorno['eqcdescricao']).";";
						$content .= str_replace(';', '', $retorno['obrobrigacao']).";";
						$content .= number_format($retorno['prefvalor'], 2, ',', '')."\n";
			
						$total_geral += $retorno['prefvalor'];
					}
						
					$content .= ";;;;;;;TOTAL GERAL:;".number_format($total_geral, 2, ',', '')."\n";
			
			$arquivo['file_path'] = "/var/www/docs_temporario/";
			$arquivo['file_name'] = "rel_pre_faturamento_" . date('dmY') . ".csv";
			file_put_contents($arquivo['file_path'] . $arquivo['file_name'], $content);
			
			// Pesquisar Previs�o
			$resultado = $this->dao->verificarPendencias(false);
			
	        return array(
					"codigo"	=> 0,
					"msg"		=> 'Planilha CSV gerada com sucesso.',
					"arquivo"	=> $arquivo['file_path'] . $arquivo['file_name'],
					"retorno"	=> $resultado
			);
	        
    	} catch (Exception $e) {
    		return array(
					"codigo"	=> 1,
					"msg"		=> 'Falha ao gerar planilha CSV.',
					"retorno"	=> array()
			);
    	}
    }
	
	public function gerarRelatorioPreFaturamentoCSV2() {
		try {
			// Gerar Relat�rio
			$resultado = $this->dao->gerarRelatorioPreFaturamento();
			
			$content = "Relat�rio Pr�-faturamento\n\n";
            $content .= "Cliente;Termo;Dt. Instala��o;Tipo;Status;Placa;Classe;Obrig. Financ.;Valor\n";
						
						
					while ($retorno = pg_fetch_array($resultado)) {
						$content .= str_replace(';', '', $retorno['clinome']).";";
						$content .= $retorno['connumero'].";";
						$content .= $retorno['condt_ini_vigencia'].";";
						$content .= str_replace(';', '', $retorno['tpcdescricao']).";";
						$content .= str_replace(';', '', $retorno['csidescricao']).";";
						$content .= str_replace(';', '', $retorno['veiplaca']).";";
						$content .= str_replace(';', '', $retorno['eqcdescricao']).";";
						$content .= str_replace(';', '', $retorno['obrobrigacao']).";";
						$content .= number_format($retorno['prefvalor'], 2, ',', '')."\n";
			
						$total_geral += $retorno['prefvalor'];
					}
						
					$content .= ";;;;;;;TOTAL GERAL:;".number_format($total_geral, 2, ',', '')."\n";
			
			$arquivo['file_path'] = "/var/www/docs_temporario/";
			$arquivo['file_name'] = "rel_pre_faturamento_" . date('dmY') . ".csv";
			file_put_contents($arquivo['file_path'] . $arquivo['file_name'], $content);
			
			// Gerar Relat�rio
			$resultado = $this->dao->gerarRelatorioPreFaturamento();
			
	        return array(
					"codigo"	=> 0,
					"msg"		=> 'Planilha CSV gerada com sucesso.',
					"arquivo"	=> $arquivo['file_path'] . $arquivo['file_name'],
					"retorno"	=> $resultado
			);
	        
    	} catch (Exception $e) {
    		return array(
					"codigo"	=> 1,
					"msg"		=> 'Falha ao gerar planilha CSV.',
					"retorno"	=> array()
			);
    	}
    }

	public function prepararFaturamento($tipo = '') {
		
		// Preparar Faturamento
		try {
			$this->dao->prepararFaturamento('F');
			
			if (!is_dir(_SITEDIR_."faturamento")) {
				if (!mkdir(_SITEDIR_."faturamento", 0777)) {
					throw new Exception('Falha ao criar arquivo de log.');
				}
			}
			chmod(_SITEDIR_."faturamento", 0777);
			
			if (!$handle = fopen(_SITEDIR_."faturamento/geracao_faturamento", "w")) {
				throw new Exception('Falha ao criar arquivo de log.');
			}
			fputs($handle, "Faturamento Iniciado\r\n");
			fclose($handle);
			chmod(_SITEDIR_."faturamento/geracao_faturamento", 0777);
			
			passthru("/usr/bin/php "._SITEDIR_."CronProcess/gerar_faturamento_unificado.php >> "._SITEDIR_."faturamento/geracao_faturamento 2>&1 &");
			
			return array (
				"codigo"	=> 0,
				"msg"		=> "Faturamento iniciado com sucesso. Um e-mail ser� enviado com informativo de conclus�o.",
				"retorno"	=> $resultado,
				"arquivo"	=> $arquivo
			);
    	
    	} catch (Exception $e) {
			$this->dao->finalizarProcesso('F');
			
    		return array(
				"codigo"	=> 1,
				"msg"		=>  $e->getMessage(),
				"retorno"	=> array()
			);
		}
	}
	

    public function gerarFaturamento() {

		try {
			$this->dao->faturar();

			$this->dao->finalizarProcesso('S');

			// Pesquisar Par�metros
			$res = $this->dao->recuperarParametros(true);
			$param = pg_fetch_assoc($res);

			// Envio de e-mail
			$mail  = new PHPMailer();
			$mail ->isSmtp();
			$mail ->From = "sascar@sascar.com.br";
			$mail ->FromName = "Sascar";

			$mail->ClearAllRecipients();
			$mail->Subject = "SASCAR - Faturamento Unificado";

			echo
			$msg = "O processo de gera��o de faturamento iniciado �s ".$param['data_inicio']." foi finalizado com sucesso �s ".$param['data_termino'];

			$mail->MsgHTML($msg);
			$mail->AddAddress($param['usuemail']);

			$mail->Send();

		} catch (Exception $e) {

			$this->dao->finalizarProcesso('F');

			// Envia e-mail com mensagem de erro
			echo '\r\n'.$e->getMessage().'\r\n';

			// Pesquisar Par�metros
			$res = $this->dao->recuperarParametros(true);
			$param = pg_fetch_assoc($res);

			// Envio de e-mail
			$mail  = new PHPMailer();
			$mail ->isSmtp();
			$mail ->From = "sascar@sascar.com.br";
			$mail ->FromName = "Sascar";

			$mail->ClearAllRecipients();
			$mail->Subject = "SASCAR - Faturamento Unificado";

			echo
			$msg = "O processo de gera��o de faturamento iniciado �s ".$param['exfdt_inicio']." falhou com a seguinte descri��o de erro: ".$e->getMessage();

			$mail->MsgHTML($msg);
			$mail->AddAddress($param['usuemail']);

			$mail->Send();

			echo "\r\n Falha ao gerar Faturamento";
		}
	}

	
    	
    private function onlyNumbers($num) {
        return preg_replace("/[^0-9]/", "",$num);
    }

    private function moeda($number) {
    	
    	if (count($test)==1) $number = $number.".00";
    	
    	if (($number*1)==0) {
    		return "0,00";
    	}
    	if (count($test)>1 && ($test[1]*1)>99) $number = round($number,2);
    	$number = str_replace(".",",",$number);
	    while (true) { 
	        $replaced = preg_replace('/(-?\d+)(\d\d\d)/', '$1.$2', $number); 
	        if ($replaced != $number) { 
	            $number = $replaced; 
	        } else { 
	            break; 
	        } 
	    }
	    $test2 = explode(",",$number);
	    if (strlen($test2[1])==1) {
	    	$number .= "0";
	    }
	    
	    return $number;
	} 

    public function getObrigacaoFinanceira($obroid) {

        $result = $this->dao->getObrigacaoFinanceira($obroid);

        $obrigacao = pg_fetch_assoc($result);
        $obrigacao['obrigacao_descricao'] = utf8_encode($obrigacao['obrigacao_descricao']);

        return($obrigacao);
    }

    public function getContrato($connumero) {

        $result = $this->dao->getContrato($connumero);
        $contrato = pg_fetch_assoc($result);
        
        $contrato['data_instalacao'] = empty($contrato['data_instalacao']) ? '' : $contrato['data_instalacao'];
        $contrato['tipo'] = empty($contrato['tipo']) ? '' : utf8_encode($contrato['tipo']);
        $contrato['status'] = empty($contrato['status']) ? '' : utf8_encode($contrato['status']);
        $contrato['placa'] = empty($contrato['placa']) ? '' : $contrato['placa'];
        $contrato['classe'] = empty($contrato['classe']) ? '' : utf8_encode($contrato['classe']);
                
        return($contrato);
    }


    public function getIGPM() {
    	try {
	    	# Pegar IGPM Acumulado
	    	$igpvl_referencia = $this->dao->valorReferenciaIGPM($this->data);
	    	
	    	if ($igpvl_referencia == '') {
	    		throw new exception("IGPM n�o cadastrado, deseja prosseguir?", 1);
	    	}
	    	
	    	echo json_encode(array(
	        		"msg"	=>	"",
	        		"code"	=>	0,
	        		"erro"	=>	0
	        ));
	    	exit();
        
    	} catch (Exception $e) {
    		echo json_encode(array(
    				"msg"	=>	utf8_encode($e->getMessage()),
    				"code"	=>	$e->getCode(),
    				"erro"	=>	1
    		));
	    	exit();
    	}
    }
    


    public function getINPC() {
    	try {
    		# Pegar INPC Acumulado
    		$inpvl_referencia = $this->dao->valorReferenciaINPC($this->data);

    		if ($inpvl_referencia  == '') {
    			throw new exception("INPC n�o cadastrado, deseja prosseguir?", 1);
    		}

    		echo json_encode(array(
    				"msg"	=>	"",
    				"code"	=>	0,
    				"erro"	=>	0
    		));
    		exit();

    	} catch (Exception $e) {
    		echo json_encode(array(
    				"msg"	=>	utf8_encode($e->getMessage()),
    				"code"	=>	$e->getCode(),
    				"erro"	=>	1
    		));
    		exit();
    	}
    }

    
    
    public static function applyMask($value, $mask, $pad_string=0, $pad_type=STR_PAD_LEFT) {
    
    	preg_match_all('/[^0-9]/', $mask, $matches, PREG_OFFSET_CAPTURE); // pega qualquer caracter que n�o seja num�rico na m�scara
    	$matches = current($matches);
    
    	if ($matches) {
    		$length = (strlen($mask) - count($matches));
    		$value = str_pad($value, $length, $pad_string, $pad_type); // garante que o valor tem o mesmo tamanho da mascara e preenche com 0 a esquerda caso seja menor
    			
    		foreach ($matches as $matche) { // percorre todos caracteres especias
    
    			list($accent, $pos) = $matche; // pega o caracter especial e sua posi��o
    
    			$newValue  =  substr($value, 0, $pos); // pega o valor at� a posi��o do caracter especial
    			$newValue .= $accent; // adiciona o acento no valor
    			$newValue .=  substr($value, $pos); // pega o valor depois da posi��o do caracter especial
    
    			$value = $newValue;
    		}
    	}
    
    	return $value;
    }
    
    public function pesquisarCliente($desc = '') {
    	
    	try {
	    	if (strlen($this->dao->cliente)<3) {
	    		throw new Exception("Digite pelo menos 3 caracteres para realizar a pesquisa.");
	    	}
    		
    		$resultado = $this->dao->pesquisarCliente($this->dao->cliente);
    		
    		$retorno = array(
	        		"erro"		=>	0,
    				"msg"		=>	"",
	        		"retorno"	=>	$resultado
	        );
        
    	} catch (Exception $e) {
    		$retorno = array(
    				"erro"		=>	1,
    				"msg"		=>	utf8_decode($e->getMessage()),
    				"retorno"	=>	array()
    		);
    	}
    	
    	echo json_encode($retorno);
    	exit();
    }
	
    /*
     * Metodo para atualizar a data de cancelamento da geracao de resumo
     *
     * @author Renato Teixeira Bueno <renato.bueno@meta.com.br>
     */
    public function pararResumo() {

	   	$pid = $this->verificaProcessoBD();

	   	if ($pid > 0) {
	   		$retornoKill = $this->dao->killProcessDB($pid);
	   	}

    	if ($retornoKill){
    		
    		$retorno = $this->dao->atualizarExecucaoFaturamento();
    		
    		if ($retorno){
    			$msg = "Gera��o de resumo parada com sucesso.";
    		} else{
    			$msg = "Erro ao tentar parar o resumo.";
    		}
    	} else{
    		$msg = "Erro ao tentar parar o resumo.";
    	}

    	return array (
			"codigo"	=> 0,
			"msg"		=> $msg,
			"retorno"	=> array()
		);

    }

    public function verificaProcessoCron(){

    	$handle = fopen(_SITEDIR_.'faturamento/PIDPROCESSO.txt', "rb");
    	$PIDPROCESSO = fread($handle, 8192);
    	fclose($handle);

    	if(is_readable("/proc/$PIDPROCESSO/cmdline") ) {

    		$cmdLineContents = '';

    		$handle = fopen("/proc/$PIDPROCESSO/cmdline", "rb");
    		$cmdLineContents .= fread($handle, 8192);
    		fclose($handle);

    		//Veirificando se o processo recuperado no pid � o mesmo que esta rodando
    		//Se o processo for o mesmo, � retornado para queimar a nova tentativa
    		if(strstr($cmdLineContents, 'gerar_previsao_faturamento.php')){
    			//MATAR PROCESSO
    			return $PIDPROCESSO;
    		}
    	}

    	return 0;
    }
    
    public function verificaProcessoBD(){
    
    	$handle = fopen(_SITEDIR_.'faturamento/pidProcessoBD.txt', "rb");
    	$PIDPROCESSO = fread($handle, 8192);
    	fclose($handle);
    
    	if($PIDPROCESSO > 0) {    
    		return $PIDPROCESSO;
		}
		
    	return 0;
    }
}

?>