<?php
/**
 * Classe respons�vel em recuperar dados de venda e disponibilizar arquivo para downlaod via menu na intranet
 * 
 * @file RelatorioDownloadArquivoVenda.php
 * @author marcioferreira
 * @version 16/05/2013 16:14:36
 * @since 16/05/2013 16:14:36
 * @package SASCAR RelatorioDownloadArquivoVenda.php 
 */

require_once _MODULEDIR_ . 'Relatorio/DAO/RelDownloadArquivoVendaDAO.php';

class RelDownloadArquivoVenda{

	//atributos
	private $conn;//boolean
	
	//vari�vel do caminho completo do arquivo
	private $file;//string
	
	//armazena o nome do arquivo que foi gerado para ser aberto posteriormente
	private $nomeArquivo;
	
	//tipo de relat�rio que ser� gerado
	private $tipoRelatorio;//string
	
	//pasta onde ser� armazenado o arquivo ap�s gerado
	private $diretorio;//string
	
	//armazena a url do ambiente que se encontra o arquivo
	private $urlArquivo;
	
	//armazena o caminho completo do arquivo
	private $pathArquivo;
	
	// Construtor
	public function __construct() {

		global $conn;

		//seta vari�vel de conex�o
		$this->conn = $conn;
		
		// Objeto  - DAO
		$this->dao = new RelDownloadArquivoVendaDAO($conn);
		
		//pasta onde ser� armazenamento tempor�rio do arquivo para download
		$this->setDiretorio( _SITEDIR_.'rel_arquivos_venda/');
	}

	/**
	 * Met�do respons�vel em buscar, gerar e disponibilizar o arquivo gerado para download
	 * @author M�rcio Sampaio Ferreira
	 * 
	 * @throws Exception
	 * @return boolean
	 */
	public function getDadosRelatorio(){
			
		try{
			
			$this->setTipoRelatorio(isset($_POST['tipoRelatorio']) ? $_POST['tipoRelatorio'] : "");
			
			//seta nome do arquivo que ser� gerado de acordo o relat�rio informado
			if($this->getTipoRelatorio() == 'online'){
					
				$this->setNomeArquivo('Teste_Conceito_Online_'.date('dmYHis').'.csv');
			
			}elseif($this->getTipoRelatorio() == 'd1'){
					
				$this->setNomeArquivo('Teste_Conceito_D1_'.date('dmYHis').'.csv');
					
			}else{
				throw new Exception('O tipo de relatorio deve ser informado');
			}
			
			//recupera os dados de acordo o tipo de relat�rio 
			$dadosRelatorio = $this->dao->getDadosVendas($this->getTipoRelatorio());
			
			if($dadosRelatorio == '001'){
				throw new Exception('Erro ao buscar dados do relatorio.');
			}
			
			$this->criaArquivo();
			
			//faz a inser��o de dados linha por linha
			foreach ($dadosRelatorio as $dados){
				
				if($this->tipoRelatorio == 'd1'){

					//verifica se as datas s�o iguais ao do dia da consulta, se for, limpa os campos
					$data_faturamento             = ($this->validarDataOntem($dados['data_faturamento'])) ? '' : $dados['data_faturamento'];
					$data_pagamento               = ($this->validarDataOntem($dados['data_pagamento'])) ? '' : $dados['data_pagamento'];
					$data_identificacao_pagamento = ($this->validarDataOntem($dados['data_identificacao_pagamento'])) ? '' : $dados['data_identificacao_pagamento'];
					$data_agendamento             = ($this->validarDataOntem($dados['data_agendamento'])) ? '' : $dados['data_agendamento'];
					$data_instalacao              = ($this->validarDataOntem($dados['data_instalacao'])) ? '' : $dados['data_instalacao'];
				  
				}else{
						
					$data_faturamento             = $dados['data_faturamento'];
					$data_pagamento               = $dados['data_pagamento'];
					$data_identificacao_pagamento = $dados['data_identificacao_pagamento'];
					$data_agendamento             = $dados['data_agendamento'];
					$data_instalacao              = $dados['data_instalacao'];
				}
				
				$cnpj = $this->formata_cgc_cpf($dados['cnpj']);
				
				$linha = array($dados['uf'],$cnpj, $dados['razao_social'],$dados['gerente_contas'],$dados['data_cadastro'], $data_faturamento, $data_pagamento, $data_identificacao_pagamento ,$dados['placa'], $data_agendamento, $data_instalacao, $dados['linha']);
				fputcsv($this->file, $linha,';');
			}
			
			$this->fechaArquivo();
			
			//verifica se o arquivo est� dispon�vel para download
			if(file_exists($this->getPathArquivo())){
				
				$obj->nomeArquivo = $this->getNomeArquivo();
		        $obj->pathArquivo = $this->getPathArquivo();
		        $obj->ulrArquivo  = $this->getUrlArquivo();
		        
		        return $obj;
				
			}else{
				return $obj = 'null';
			}
			
		}catch(Exception $e){

			return $obj = 'null';
		}

	}
	
	/**
	 * M�todo respons�vel para validar data anterior
	 * Se a data dos campos informados forem maior que a data de ontem, exibe os campos sem dados
	 * 
	 * */
	private function validarDataOntem($data){
		
		$dadosData = strtotime($data);
		$dataOntem = strtotime(date("d-m-Y",time()-86400));
		
		if($dadosData > $dataOntem){
			return true;
		}else{
			return false;
		}
	}
	
	
	/**
	 * Helper para formatar o cnpj
	 * 
	 */
	private function formata_cgc_cpf($numero){
		
		if(strlen($numero)<=11){
			$buf=@str_repeat("0",11-strlen($numero)).$numero;
			$buf=substr($buf,0,3).".".substr($buf,3,3).".".substr($buf,6,3)."-".substr($buf,9,2);
		}else{
			$buf=@str_repeat("0",14-strlen($numero)).$numero;
			$buf=substr($buf,0,2).".".substr($buf,2,3).".".substr($buf,5,3)."/".substr($buf,8,4)."-".substr($buf,12,2);
		}
		
		return $buf;
	}
	
	/**
	 * M�todo respons�vel em gerar a pasta para armazenar o arquivo caso n�o exista, e gerar o arquivo CSV
	 * @author M�rcio Sampaio Ferreira
	 */
	private function criaArquivo(){

		try{
			
			//cria a pasta caso n�o exista
			if(!is_dir($this->getDiretorio())){
				mkdir($this->getDiretorio(), 0777, true);
			}
			
			//verifica novamente se a o diret�rio foi criado
			if(!is_dir($this->getDiretorio())){
				throw new Exception('Erro ao gerar pasta para armazenar arquivo');
			}
			
			//set o path do arquivo para verificar se o arquivo existe
			$this->setPathArquivo($this->getDiretorio().$this->getNomeArquivo());
			
			//seta url completa do arquivo
			$this->setUrlArquivo(_SITEURL_.'rel_arquivos_venda/'.$this->getNomeArquivo());
			
			//abre o arquivo 
			$this->file = fopen($this->getPathArquivo(), 'a+');
						
			//cria o titulo da coluna do arquivo
			$cabecalho = array("UF","CNPJ","Raz�o Social","Gerente de Contas","Data Cadastro","Data Faturamento","Data Pagamento","Data Identifica��o do Pagamento","Placa","Data Agendamento","Data Instala��o","Linha");
		
			fputcsv($this->file, $cabecalho,';');
			
	
		}catch(Exception $e){
			return $e->getMessage();
		}
	}
	
	/**
	 * M�todo respons�vel em exibir tela no browser para abrir ou fazer download do arquivo 
	 * @author M�rcio Sampaio Ferreira
	 */
	public function getArquivo(){

		//recebe a url do arquivo
		$arquivo = $_POST['arquivo_gerado'];
		//recebe o nome do arquivo gerado
		$nome_arquivo = $_POST['nome_arquivo'];
		//recebe o path absoluto do servidor
		$path_arquivo = $_POST['path_arquivo'];

		try{
			
			$extensaoArquivo = strtolower(substr(strrchr($arquivo,"."),1));

			switch ($extensaoArquivo) {
				case "pdf": $tipo="application/pdf"; break;
				case "exe": $tipo="application/octet-stream"; break;
				case "zip": $tipo="application/zip"; break;
				case "doc": $tipo="application/msword"; break;
				case "xls": $tipo="application/vnd.ms-excel"; break;
				case "ppt": $tipo="application/vnd.ms-powerpoint"; break;
				case "gif": $tipo="image/gif"; break;
				case "png": $tipo="image/png"; break;
				case "jpg": $tipo="image/jpg"; break;
				case "mp3": $tipo="audio/mpeg"; break;
				case "csv": $tipo="text/csv"; break;
				case "php": // deixar vazio por seuran�a
				case "htm": // deixar vazio por seuran�a
				case "html": // deixar vazio por seuran�a
			}


			if (file_exists($path_arquivo)) {

				// Configuramos os headers que ser�o enviados para o browser
				header('Content-Description: File Transfer');
				header('Content-Disposition: attachment; filename="'.$nome_arquivo.'"');
				header('Content-Type: '.$tipo);
				header('Content-Transfer-Encoding: binary');
				header('Content-Length: ' . filesize($path_arquivo));
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');
				header('Expires: 0');
				ob_clean();
				flush();
				// Envia o arquivo para o cliente
				readfile($arquivo);
				unlink($path_arquivo);

			}else{
				throw new Exception(0);
				unlink($path_arquivo);
			}
			
		}catch(Exception $e){
			echo $e->getMessage();
		}
	}
	
	
	/**
	 * M�todo respons�vel em fechar o arquivo criado depois de populado 
	 * 
	 * */
	private function fechaArquivo()	{
		fclose($this->file);
	}
	
	//seta o diret�rio onde ser� armazenado o arquivo gerado
	public function setDiretorio($valor){
		$this->diretorio = $valor;
	}	
	
	//retorna o diret�rio onde ser� armazenado o arquivo gerado
	public function getDiretorio(){
		return $this->diretorio;
	}
	
	//seta situa��o da opera��o
	public function setSituacao($valor){
		$this->situacao = $valor;
	}
	
	//retorna a situacao para o index se o arquivo foi encontrado ou n�o
	public function getSituacao(){
		return $this->situacao;
	}
		
	//seta nome do arquivo gerado
	public function setNomeArquivo($valor){
		$this->nomeArquivo = $valor;
	}

	//retorna nome do arquivo gerado
	public function getNomeArquivo(){
		return $this->nomeArquivo;
	}
	
	//seta a url do arquivo que ser� disponibilizado via web
	public function setUrlArquivo($valor){
		$this->urlArquivo = $valor;
	} 
	
	//retorna url para download do arquivo
	public function getUrlArquivo(){
		return $this->urlArquivo;
	}
	
	//set ao path do arquivo 
	public function setPathArquivo($valor){
		$this->pathArquivo = $valor;
	}
	
	//recupera ao path do arquivo
	public function getPathArquivo(){
		return $this->pathArquivo;
	}
	
	//seta o tipo de relal�rio solicitado pelo usu�rio
	public function setTipoRelatorio($tipo){
		$this->tipoRelatorio = $tipo;
	}
	
	//retorna o tipo de relat�rio
	public function getTipoRelatorio(){
		return $this->tipoRelatorio;
	}
	
}