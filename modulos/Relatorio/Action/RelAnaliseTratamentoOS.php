<?php

require_once _SITEDIR_ . 'lib/Components/Paginacao/PaginacaoComponente.php';

/**
 * Classe RelAnaliseTratamentoOS.
 * Camada de regra de neg�cio.
 *
 * @package  Relatorio
 * @author   Robson Aparecido Trizotte da Silva <robson.silva@meta.com.br>
 *
 */
class RelAnaliseTratamentoOS {

    /**
     * Objeto DAO da classe.
     *
     * @var CadExemploDAO
     */
    private $dao;

    /**
     * Mensagem de alerta para campos obrigat�rios n�o preenchidos
     * @var String
     */

    const MENSAGEM_ALERTA_CAMPOS_OBRIGATORIOS = "Existem campos obrigat�rios n�o preenchidos.";

    /**
     * Mensagem de sucesso para inser��o do registro
     * @var String
     */
    const MENSAGEM_SUCESSO_INCLUIR = "Registro inclu�do com sucesso.";

    /**
     * Mensagem para nenhum registro encontrado
     * @var String
     */
    const MENSAGEM_NENHUM_REGISTRO = "Nenhum registro encontrado.";

    /**
     * Mensagem de erro para o processamentos dos dados
     * @var String
     */
    const MENSAGEM_ERRO_PROCESSAMENTO = "Houve um erro no processamento dos dados.";

    /**
     * Mensagem de erro para o processamentos do arquivo CSV
     * @var String
     */
    const MENSAGEM_ERRO_ARQUIVO = "Houve um erro no processamento do arquivo.";

    /**
     * Mensagem de sucesso para o processamentos do arquivo CSV
     * @var String
     */
    const MENSAGEM_SUCESSO_ARQUIVO = "Arquivo gerado com sucesso.";

    /**
     * Mensagem da data inicial maior que a final
     * @var String
     */
    const MENSAGEM_DATA_INICIAL_FINAL = 'A data inicial n�o pode ser maior que a data final';

    /**
     * Mensagem da data inicial maior que a final
     * @var String
     */
    const MENSAGEM_PERIODO_SUPERIOR = 'O per�odo informado n�o pode ser superior a 1 m�s.';

    /**
     * Cont�m dados a serem utilizados na View.
     *
     * @var stdClass
     */
    private $view;

    /**
     * M�todo construtor.
     *
     * @param CadExemploDAO $dao Objeto DAO da classe
     */
    public function __construct($dao = null) {

        //Verifica o se a vari�vel � um objeto e a instancia na atributo local
        if (is_object($dao)) {
            $this->dao = $dao;
        }

        //Cria objeto da view
        $this->view = new stdClass();
        //Mensagem
        $this->view->mensagemErro = '';
        $this->view->mensagemAlerta = '';
        $this->view->mensagemSucesso = '';

        //Dados para view
        $this->view->dados = null;

        //Filtros/parametros utlizados na view
        $this->view->parametros = null;

        //Status de uma transa��o
        $this->view->status = false;

        // Orden��o e pagina��o
        $this->view->ordenacao = null;
        $this->view->paginacao = null;
    }

    /**
     * M�todo padr�o da classe.
     *
     * Repons�vel tamb�m por realizar a pesquisa invocando o m�todo privado
     *
     * @return void
     */
    public function index() {

        try {
            $this->view->parametros = $this->tratarParametros();

            //Inicializa os dados
            $this->inicializarParametros();

            //Verificar se a a��o pesquisar e executa pesquisa
            if (isset($this->view->parametros->acao) && ( $this->view->parametros->acao == 'pesquisar' || $this->view->parametros->acao == 'gerarCSV')) {

                $this->validarCamposPesquisa($this->view->parametros);
                
                if ($this->view->parametros->acao != 'gerarCSV'){
                    switch ($this->view->parametros->tipo) {
                        case 1:
                            $this->view->dados = $this->pesquisar($this->view->parametros);
                            break;
                        case 2:
                            //Tratar os dados para exibi��o do relat�rio analitico
                            $this->view->dados = $this->pesquisarAnalitico($this->view->parametros);
                            $this->view->dadosQuantidadeOS = $this->prepararDadosQuantidadeOS();
                            $this->view->dadosQuantidadeAtendimentos = $this->prepararQuantidadeAtendimentos();
                            $this->view->dadosAtendimentosPorAcao = $this->prepararAtendimentosPorAcao();
                            $this->view->dadosQuantidadesProjetos = $this->prepararQuantidadesProjetos();
                            break;
                    }
                } else  {

					switch ($this->view->parametros->tipo) {
	                    case 1:

                            $resultadoPesquisa = $this->dao->pesquisar($this->view->parametros);
                            if (count($resultadoPesquisa->dados) == 0){
                                throw new Exception(self::MENSAGEM_NENHUM_REGISTRO);
                            }

	                        $this->view->dados = $resultadoPesquisa->dados;

	                        $this->gerarCSV();
	                        break;
	                    case 2:
                            //Tratar os dados para exibi��o do relat�rio analitico
                            $this->view->dados = $this->pesquisarAnalitico($this->view->parametros);
                            $this->view->dadosQuantidadeOS = $this->prepararDadosQuantidadeOS();
                            $this->view->dadosQuantidadeAtendimentos = $this->prepararQuantidadeAtendimentos();
                            $this->view->dadosAtendimentosPorAcao = $this->prepararAtendimentosPorAcao();
                            $this->view->dadosQuantidadesProjetos = $this->prepararQuantidadesProjetos();
	                    	$this->gerarCSVAnalitico();
                            
	                        break;
	                }		
                    $this->view->status = TRUE;
                    unset($resultadoPesquisa);
                }
            }
        } catch (ErrorException $e) {

            $this->view->mensagemErro = $e->getMessage();
        } catch (Exception $e) {

            $this->view->mensagemAlerta = $e->getMessage();
        }

        //Incluir a view padr�o
        //@TODO: Montar dinamicamente o caminho apenas da view Index
        require_once _MODULEDIR_ . "Relatorio/View/rel_analise_tratamento_os/index.php";
    }

    /**
     * Trata os parametros do POST/GET. Preenche um objeto com os parametros
     * do POST e/ou GET.
     *
     * @return stdClass Parametros tradados
     *
     * @retrun stdClass
     */
    private function tratarParametros() {
        $retorno = new stdClass();

        if (count($_POST) > 0) {
            foreach ($_POST as $key => $value) {
                $retorno->$key = isset($_POST[$key]) ? $value : '';
            }
        }

        if (count($_GET) > 0) {
            foreach ($_GET as $key => $value) {

                //Verifica se atributo j� existe e n�o sobrescreve.
                if (!isset($retorno->$key)) {
                    $retorno->$key = isset($_GET[$key]) ? $value : '';
                }
            }
        }
        return $retorno;
    }

    /**
     * Popula os arrays para os combos de estados e cidades
     *
     * @return void
     */
    private function inicializarParametros() {

        //Verifica se os parametro existem, sen�o iniciliza todos
        $this->view->parametros->aotoid = isset($this->view->parametros->aotoid) ? trim($this->view->parametros->aotoid) : '';
        $this->view->parametros->aoamoid_acao = isset($this->view->parametros->aoamoid_acao) ? trim($this->view->parametros->aoamoid_acao) : '';
        $this->view->parametros->aoamoid_motivo = isset($this->view->parametros->aoamoid_motivo) ? trim($this->view->parametros->aoamoid_motivo) : '';
        $this->view->parametros->data_inicial = isset($this->view->parametros->data_inicial) ? trim($this->view->parametros->data_inicial) : date('d/m/Y');
        $this->view->parametros->data_final = isset($this->view->parametros->data_final) ? trim($this->view->parametros->data_final) : date('d/m/Y');
        $this->view->parametros->tpcoid = isset($this->view->parametros->tpcoid) ? trim($this->view->parametros->tpcoid) : '';
        $this->view->parametros->cd_usuario = isset($this->view->parametros->cd_usuario) ? trim($this->view->parametros->cd_usuario) : '';
        $this->view->parametros->clinome = isset($this->view->parametros->clinome) ? trim($this->view->parametros->clinome) : '';

        //Busca as a��es
        $this->view->parametros->acoes = $this->dao->buscarAcoes();

        //Busca os motivos
        $filtroMotivos = new stdClass();

        if (intval($this->view->parametros->aoamoid_acao) > 0) {
            $filtroMotivos->aoampai = intval($this->view->parametros->aoamoid_acao);
            $this->view->parametros->motivos = $this->dao->buscarMotivos($filtroMotivos);
        } else {
            $filtroMotivos->aoampai = '';
            $this->view->parametros->motivos = $this->dao->buscarMotivos($filtroMotivos);
        }

        //Busca os Tipos Contratos
        $this->view->parametros->tipocontrato = $this->dao->buscarTipoContrato();

        //Busca os Atendentes
        $this->view->parametros->atendentes = $this->dao->buscarAtendentes();
    }

    /**
     * Respons�vel por tratar e retornar o resultado da pesquisa.
     *
     * @param stdClass $filtros Filtros da pesquisa
     *
     * @return array
     */
    private function pesquisar(stdClass $filtros) {
        $paginacao = new PaginacaoComponente();

        /*
            MANTIS 3878
            Para evitar time out na pesquisa foram removidos os WS's contidos nos seguintes metodos:
            
            $this->dao->buscarUltimaLocalizaoDataVeiculo
            $this->dao->verificaGPSValido
        */
        // Consulta quantidade de registros
        //$consultaQuantidade = $this->dao->pesquisar($filtros);
        //$quantidadePesquisa = count($consultaQuantidade->dados);
        $quantidadePesquisa = $this->dao->pesquisarQuantidade($filtros);

        //Valida se houve resultado na pesquisa
        if ($quantidadePesquisa == 0) {
            $resultadoPesquisa = array();

            throw new Exception(self::MENSAGEM_NENHUM_REGISTRO);
        } else {
            $campos = array(
                'clinome' => 'Cliente',
                'veiplaca' => 'Placa',
                'eprnome' => 'Projeto',
                'conmodalidade' => 'Modalidade',
                'tpcdescricao' => 'Tipo',
                'osdfdescricao' => 'Defeito Alegado',
                'ordoid' => 'O.S.',
                'ossdescricao' => 'Status',
                'veipdata_os' => 'Data Posi��o OS',
                'orddt_ordem_ordenacao' => 'Data Abertura OS',
                'veipdata_atual_ordenacao' => 'Data Posi��o Atual',
                'osdata_ordenacao' => 'Data Agendada',
                'aoamdescricao_acao' => 'A��o',
                'aoamdescricao_motivo' => 'Motivo'
            );

            if ($paginacao->setarCampos($campos)) {
                $this->view->ordenacao = $paginacao->gerarOrdenacao('osdata');
                $this->view->paginacao = $paginacao->gerarPaginacao($quantidadePesquisa);
            }

            $resultadoPesquisa = $this->dao->pesquisar($filtros, $paginacao->buscarPaginacao(), $paginacao->buscarOrdenacao());
        }
        
        $this->view->quantidade = $quantidadePesquisa;
        $this->view->status = TRUE;

        return $resultadoPesquisa->dados;
    }

    /**
     * Respons�vel por tratar e retornar o resultado anal�tico da pesquisa.
     *
     * @param stdClass $filtros Filtros da pesquisa
     *
     * @return array
     */
    private function pesquisarAnalitico(stdClass $filtros) {

        $resultadoPesquisa = $this->dao->pesquisarAnalitico($filtros);

        //Valida se houve resultado na pesquisa
        if (count($resultadoPesquisa) == 0) {
            throw new Exception(self::MENSAGEM_NENHUM_REGISTRO);
        }

        $this->view->status = TRUE;

        return $resultadoPesquisa;
    }

    /**
     * Respons�vel por gerar CSV a partir do resultado da pesquisa.
     *
     * @param array $resultadoPesquisa
     *
     * @return void
     */
    private function gerarCSV() {

        //Diret�rio do Arquivo
        $caminho = '/var/www/docs_temporario/';
        //Nome do arquivo
        $nomeArquivo = 'AnaliseOS_' . date("Y") . '_' . date("m") . '_' . date("d") . '_' . date("H") . '_' . date("i") . '.csv';
        //Flag para identificar se o arquivo foi gerado
        $arquivo = false;

        if (file_exists($caminho)) {

            // Instanciar CSV
            $csvWriter = new CsvWriter($caminho . $nomeArquivo, ';', '', true);

            // Adicionar t�tulo
            $csvWriter->addLine('An�lise de Ordem Servi�o � Equipamento Atualizando');

            // Gerar cabe�alho
            $cabecalho = array(
                "Cliente",
                "Placa",
                "Projeto",
                "Modalidade",
                "Tipo",
                "Defeito Alegado",
                "O.S.",
                "Status",
                "Data Posi��o OS",
                "Data Abertura OS",
                "Data Posi��o Atual",
                "Data Agendada",
                "A��o",
                "Motivo"
            );
            // Adicionar cabe�alho
            $csvWriter->addLine($cabecalho);

            //Total de registros
            $this->countRelatorio = count($this->view->dados);

            if ($this->countRelatorio > 0) {
                foreach ($this->view->dados as $relatorio) {

                    //Trata os dados
                    $relatorio->clinome = (!empty($relatorio->clinome) ) ? $relatorio->clinome : ' ';
                    $relatorio->veiplaca = (!empty($relatorio->veiplaca) ) ? $relatorio->veiplaca : ' ';
                    $relatorio->eprnome = (!empty($relatorio->eprnome) ) ? $relatorio->eprnome : ' ';
                    $relatorio->conmodalidade = (!empty($relatorio->conmodalidade) ) ? $relatorio->conmodalidade : ' ';
                    $relatorio->tpcdescricao = (!empty($relatorio->tpcdescricao) ) ? $relatorio->tpcdescricao : ' ';
                    $relatorio->osdfdescricao = (!empty($relatorio->osdfdescricao) ) ? $relatorio->osdfdescricao : ' ';
                    $relatorio->ordoid = (!empty($relatorio->ordoid) ) ? $relatorio->ordoid : ' ';
                    $relatorio->ossdescricao = (!empty($relatorio->ossdescricao) ) ? $relatorio->ossdescricao : ' ';
                    $relatorio->veipdata_os = (!empty($relatorio->veipdata_os) ) ? $relatorio->veipdata_os : ' ';
                    $relatorio->orddt_ordem = (!empty($relatorio->orddt_ordem) ) ? $relatorio->orddt_ordem : ' ';
                    $relatorio->veipdata_atual = (!empty($relatorio->veipdata_atual) ) ? $relatorio->veipdata_atual : ' ';
                    $relatorio->osdata = (!empty($relatorio->osdata) ) ? $relatorio->osdata : ' ';
                    $relatorio->aoamdescricao_acao = (!empty($relatorio->aoamdescricao_acao) ) ? $relatorio->aoamdescricao_acao : ' ';
                    $relatorio->aoamdescricao_motivo = (!empty($relatorio->aoamdescricao_motivo) ) ? $relatorio->aoamdescricao_motivo : ' ';
                    //Adicionar linha
                    $csvWriter->addLine(
                            array(
                                $relatorio->clinome,
                                $relatorio->veiplaca,
                                $relatorio->eprnome,
                                $relatorio->conmodalidade,
                                $relatorio->tpcdescricao,
                                $relatorio->osdfdescricao,
                                $relatorio->ordoid,
                                $relatorio->ossdescricao,
                                $relatorio->veipdata_os,
                                $relatorio->orddt_ordem,
                                $relatorio->veipdata_atual,
                                $relatorio->osdata,
                                $relatorio->aoamdescricao_acao,
                                $relatorio->aoamdescricao_motivo
                            )
                    );
                } //Foreach
            } //IF Count do Relat�rio
        } //IF File_exists
        $msgRegistrosEncontrados = '';
        // Adicionar total registros encontrados
        if ($this->countRelatorio > 1) {

            $msgRegistrosEncontrados = $this->countRelatorio . " registros encontrados.";
        } else if ($this->countRelatorio == 1) {

            $msgRegistrosEncontrados = $this->countRelatorio . " registro encontrado.";
        } 

        if ($msgRegistrosEncontrados != ''){
            $csvWriter->addLine($msgRegistrosEncontrados);    
        }
        

        //Verifica se o arquivo foi gerado

        $arquivo = file_exists($caminho . $nomeArquivo);

        if ($arquivo === false) {

            throw new Exception(self::MENSAGEM_ERRO_ARQUIVO);
        } elseif ($this->countRelatorio > 0) {

            //Mensagem do arquivo gerado
            $this->view->mensagemSucesso = self::MENSAGEM_SUCESSO_ARQUIVO;
        }

        $this->view->csv = TRUE;
        $this->view->nomeArquivo = $nomeArquivo;
    }

    /**
     * Respons�vel por gerar CSV a partir do resultado Anal�tico da pesquisa.
     *
     * @param array $resultadoPesquisa
     *
     * @return void
     */
    private function gerarCSVAnalitico() {

        //Diret�rio do Arquivo
        $caminho = '/var/www/docs_temporario/';
        //Nome do arquivo
        $nomeArquivo = 'AnaliseOS_' . date("Y") . '_' . date("m") . '_' . date("d") . '_' . date("H") . '_' . date("i") . '.csv';
        //Flag para identificar se o arquivo foi gerado
        $arquivo = false;

        if (file_exists($caminho)) {

            // Instanciar CSV
            $csvWriter = new CsvWriter($caminho . $nomeArquivo, ';', '', true);
			
            /*
             * Bloco 01 - Relat�rio Detalhado 
             */
            // Adicionar t�tulo
            $csvWriter->addLine('An�lise de Ordem Servi�o � Equipamento Atualizando');

            // Gerar cabe�alho
            $cabecalho = array(
                "Cliente",
                "Placa",
                "A��o",
                "Motivo",
            	"Atendente",
            	"Data Atendimento"	
            );
            // Adicionar cabe�alho
            $csvWriter->addLine($cabecalho);

            //Total de registros
            $this->countRelatorio = count($this->view->dados);

            if ($this->countRelatorio > 0) {
                foreach ($this->view->dados as $relatorio) {
                    //Trata os dados
                    $relatorio->clinome = (!empty($relatorio->clinome) ) ? $relatorio->clinome : ' ';
                    $relatorio->veiplaca = (!empty($relatorio->veiplaca) ) ? $relatorio->veiplaca : ' ';
                    $relatorio->aoamdescricao_acao = (!empty($relatorio->aoamdescricao_acao) ) ? $relatorio->aoamdescricao_acao : ' ';
                    $relatorio->aoamdescricao_motivo = (!empty($relatorio->aoamdescricao_motivo) ) ? $relatorio->aoamdescricao_motivo : ' ';
                    $relatorio->ds_login = (!empty($relatorio->ds_login) ) ? $relatorio->ds_login : ' ';
                    $relatorio->aotdt_cadastro = (!empty($relatorio->aotdt_cadastro) ) ? $relatorio->aotdt_cadastro : ' ';
                    //Adicionar linha
                    $csvWriter->addLine(
						array(
							$relatorio->clinome,
							$relatorio->veiplaca,
							$relatorio->aoamdescricao_acao,
							$relatorio->aoamdescricao_motivo,
							$relatorio->ds_login,
							$relatorio->aotdt_cadastro	
						)
                    );
                } //Foreach
            } //IF Count do Relat�rio

	        // Adicionar total registros encontrados
	        if ($this->countRelatorio > 1) {
	
	            $msgRegistrosEncontrados = $this->countRelatorio . " registros encontrados.";
	            
	        } else if ($this->countRelatorio == 1) {
	
	            $msgRegistrosEncontrados = $this->countRelatorio . " registro encontrado.";
	        }
	
	        $csvWriter->addLine($msgRegistrosEncontrados);

            /*
             * Bloco 02 - Relat�rio Resumido - Quantidade OS
             */
            // Adicionar t�tulo
	        $csvWriter->addLine(' ');
            $csvWriter->addLine('Quantidade de O.S.');

            // Gerar cabe�alho
            $cabecalho = array(
                "A��o",
                "Sub Total",
            );
            // Adicionar cabe�alho
            $csvWriter->addLine($cabecalho);

            //Total de registros
            $this->somaRelatorio = 0;

            if ($this->countRelatorio > 0) {
                foreach ($this->view->dadosQuantidadeOS as $relatorio) {
                    //Trata os dados
                    $relatorio['descricao'] = (!empty($relatorio['descricao']) ) ? $relatorio['descricao'] : ' ';
                    $relatorio['total'] = (!empty($relatorio['total']) ) ? $relatorio['total'] : ' ';
                    //Adicionar linha
                    $csvWriter->addLine(
						array(
							$relatorio['descricao'],
							$relatorio['total'],
						)
                    );
					$this->somaRelatorio += $relatorio['total'];
                } //Foreach
            } //IF Count do Relat�rio
            
	        // Adicionar soma total
			$csvWriter->addLine(
					array(
						'Total',
						$this->somaRelatorio,
					)
			);

            /*
             * Bloco 03 - Relat�rio Resumido - Quantidade Atendimentos
             */
            // Adicionar t�tulo
	        $csvWriter->addLine(' ');
            $csvWriter->addLine('Quantidade de Atendimentos');

            // Gerar cabe�alho
            $cabecalho = array(
                "Atendente",
                "Sub Total",
            );
            // Adicionar cabe�alho
            $csvWriter->addLine($cabecalho);

            //Total de registros
            $this->somaRelatorio = 0;

            if ($this->countRelatorio > 0) {
                foreach ($this->view->dadosQuantidadeAtendimentos as $relatorio) {
                    //Trata os dados
                    $relatorio['atendente'] = (!empty($relatorio['atendente']) ) ? $relatorio['atendente'] : ' ';
                    $relatorio['total'] = (!empty($relatorio['total']) ) ? $relatorio['total'] : ' ';
                    //Adicionar linha
                    $csvWriter->addLine(
                            array(
                                $relatorio['atendente'],
                                $relatorio['total'],
                            )
                    );
					$this->somaRelatorio += $relatorio['total'];
                } //Foreach
            } //IF Count do Relat�rio
            
	        // Adicionar soma total
			$csvWriter->addLine(
					array(
						'Total',
						$this->somaRelatorio,
					)
			);

            /*
             * Bloco 04 - Relat�rio Resumido - Atendimentos por A��o
             */
            // Adicionar t�tulo
	        $csvWriter->addLine(' ');
            $csvWriter->addLine('Atendimentos por A��o');

                
			if (count($this->view->dadosAtendimentosPorAcao) > 0): 
				$this->totalDadosAtendimentoAcaoGeral = 0; 
				foreach ($this->view->dadosAtendimentosPorAcao as $dadosAtendimentoAcao): 
					$this->totalDadosAtendimentoAcao = 0; 
					// Gerar sub cabe�alho
					$cabecalho = array(
						$dadosAtendimentoAcao['acao'],
						"Sub Total",
					);
					// Adicionar sub cabe�alho
					$csvWriter->addLine($cabecalho);
					foreach ($dadosAtendimentoAcao['motivos'] as $relatorio): 
						//Trata os dados
						$relatorio['descricao'] = (!empty($relatorio['descricao']) ) ? $relatorio['descricao'] : ' ';
						$relatorio['total'] = (!empty($relatorio['total']) ) ? $relatorio['total'] : ' ';
						//Adicionar linha
						$csvWriter->addLine(
								array(
									$relatorio['descricao'],
									$relatorio['total'],
								)
						);
						$this->totalDadosAtendimentoAcao += $relatorio['total']; 
					endforeach; 
					
					// Adicionar total por A��o
					$csvWriter->addLine(
						array(
							'Total',
							$this->totalDadosAtendimentoAcao,
						)
					);
					$this->totalDadosAtendimentoAcaoGeral += $this->totalDadosAtendimentoAcao; 
				endforeach; 
				// Adicionar total geral
				$csvWriter->addLine(
					array(
						'Total geral',
						$this->totalDadosAtendimentoAcaoGeral,
					)
				);
			endif; 

            /*
             * Bloco 05 - Relat�rio Resumido - Quantidade por Projeto
             */
            // Adicionar t�tulo
	        $csvWriter->addLine(' ');
            $csvWriter->addLine('Quantidade por Projeto');

			if (count($this->view->dadosQuantidadesProjetos['dados']) > 0): 
				$totalQtdSubtotal = 0; 
				$totalPorAcao = array(); 
				
				// Gerar cabe�alho
				$cabecalho = array();
				$cabecalho[] = "Projeto";
				foreach ($this->view->dadosQuantidadesProjetos['acoes'] as $acao):
					$cabecalho[] = $acao;
				endforeach;
				$cabecalho[] = "Sub Total";
				
				// Adicionar cabe�alho
				$csvWriter->addLine($cabecalho);
				
				// Adicionar Linhas
				
				foreach ($this->view->dadosQuantidadesProjetos['projetos'] as $projeto): 
					$linhas = array();
					$linhas[] = $projeto;
					foreach ($this->view->dadosQuantidadesProjetos['acoes'] as $acao): 
						$linhas[] = $this->view->dadosQuantidadesProjetos['dados'][$projeto][$acao]; 
						if (!isset($totalPorAcao[$acao])){
							$totalPorAcao[$acao] = 0;
						}
						$totalPorAcao[$acao] += $this->view->dadosQuantidadesProjetos['dados'][$projeto][$acao];
					endforeach; 
					$linhas[] = $this->view->dadosQuantidadesProjetos['dados'][$projeto]['subtotal'];

					//Adicionar linha
					$csvWriter->addLine($linhas);
						
					$totalQtdSubtotal += $this->view->dadosQuantidadesProjetos['dados'][$projeto]['subtotal']; 
					
				endforeach;
				
				// Gerar rodap�
				$rodape = array();
				$rodape[] = "Total";
				foreach ($this->view->dadosQuantidadesProjetos['acoes'] as $acao):
					$rodape[] = $totalPorAcao[$acao];
				endforeach;
				$rodape[] = $totalQtdSubtotal;
				
				// Adicionar rodap�
				$csvWriter->addLine($rodape);

			endif; 

		} //IF File_exists
        //Verifica se o arquivo foi gerado

        $arquivo = file_exists($caminho . $nomeArquivo);

        if ($arquivo === false) {

            throw new Exception(self::MENSAGEM_ERRO_ARQUIVO);
        } elseif ($this->countRelatorio > 0) {

            //Mensagem do arquivo gerado
            $this->view->mensagemSucesso = self::MENSAGEM_SUCESSO_ARQUIVO;
        }

        $this->view->csv = TRUE;
        $this->view->nomeArquivo = $nomeArquivo;
    }
    
    /**
     * Respons�vel por exibir o formul�rio de cadastro ou invocar
     * o metodo para salvar os dados
     *
     * @param stdClass $parametros Dados do cadastro, para edi��o (opcional)
     *
     * @return void
     */
    public function cadastrar($parametros = null) {

        //identifica se o registro foi gravado
        $registroGravado = FALSE;
        $historicoOsGravado = FALSE;
        //$historicoContratoGravado = FALSE;
        try {

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

                // Gravar Hist�rico da OS
                if ($registroGravado) {
                    
                    //Pesquisa o registro por OS
                    $dados = $this->dao->pesquisarPorID($this->view->parametros->aotordoid);

                    //Pega o nome do usu�rio
                    $nomeUsuario = isset($_SESSION['usuario']['nome']) ? $_SESSION['usuario']['nome'] : '';

                    $dados->situacao = date('d/m/Y') . " " . date('H:i:s') . " � " . $nomeUsuario;
                    $dados->situacao .= " - An�lise Ordem de Servi�o � Equipamento Atualizando, OS: " . $dados->ordoid;
                    $dados->situacao .= ", Placa: " . $dados->veiplaca . ", Defeito Alegado: " . $dados->osdfdescricao;
                    $dados->situacao .= ", A��o: " . $dados->aoamdescricao_acao . ", Motivo: " . $dados->aoamdescricao_motivo;

                    $dadosUltimaLocal = $this->buscarDadosUltimaLocalizacao($dados->veioid);
                    
                    if ($dadosUltimaLocal !== false) {

                        $dados->situacao .= ", �ltima Posi��o do equipamento: " . $dadosUltimaLocal . ".";
                    
                    }
                    
                    $historicoOsGravado = $this->salvarHistoricoOS($dados);

                    
                    /*J� INSERE HIST�RICO DA os NO CONTRATO ATRAV�S DE TRIGGER ACIMA
                    // Gravar Hist�rico da OS
                    if ($historicoOsGravado) {
                        $historicoContratoGravado = $this->salvarHistoricoContrato($dados);
                    }
                    */
                }
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
        if ($registroGravado && $historicoOsGravado) {
            echo json_encode(array('status' => true));
        } else {

            //@TODO: Montar dinamicamente o caminho apenas da view Cadastrar
            require_once _MODULEDIR_ . "Relatorio/View/rel_analise_tratamento_os/cadastrar.php";
        }
    }

    /**
     *  Busca data, hora e endere�o da ultima localiza��o do veiculo.
     * @return string
     */
    private function buscarDadosUltimaLocalizacao($veioid){

        //Busca dados da ultima localiza��o
        if ($_SESSION['servidor_teste']){
            $urlWebServiceUltLocalizacao = _PROTOCOLO_ . "sasweb-services-homolog.sascar.com.br/unificado_backend/posicao/obterUltimaPosicaoCentral/";
            $urlWebServiceUltLocalizacao = _PROTOCOLO_ . "10.0.110.1:7010/unificado_backend/posicao/obterUltimaPosicaoCentral/";
        } else {
            $urlWebServiceUltLocalizacao = _PROTOCOLO_ . "sasweb-services.sascar.com.br/unificado_backend/posicao/obterUltimaPosicaoCentral/";
        }

        
        if (!empty($veioid)){
            $urlWebServiceUltLocalizacao .= $veioid;           
        } else {
            return false;
        }
        

        $urlUltimaLocalizacao = file_get_contents($urlWebServiceUltLocalizacao);
        if ($urlUltimaLocalizacao === false){
            return false;
        } 

        $xmlUltimaLocalizacao = simplexml_load_string($urlUltimaLocalizacao);


        //seta as coordenadas        
        $coordenadax = isset($xmlUltimaLocalizacao->longitude) ? $xmlUltimaLocalizacao->longitude : '';
        $coordenaday = isset($xmlUltimaLocalizacao->latitude) ? $xmlUltimaLocalizacao->latitude : '';
        
        if ($coordenadax == '' || $coordenaday == ''){

            return false;
        }
        
        $dataChegadaFormatada = isset($xmlUltimaLocalizacao->dataChegadaFormatada) ? $xmlUltimaLocalizacao->dataChegadaFormatada : '';
        $dataHoraFormatada = isset($xmlUltimaLocalizacao->dataHoraFormatada) ? $xmlUltimaLocalizacao->dataHoraFormatada : '';

        //Busca dados de endere�o das coordenadas X e Y
        $coordX = trim($coordenadax);
        $coordY = trim($coordenaday);
        
        $urlConsulta = _URL_GOOGLE_MAPS_ . "?x=$coordX&y=$coordY&type=xml";
                
        $retornoUrl = file_get_contents($urlConsulta);
        
        if(!$retornoUrl) {
            return false;
        }
        
        $doc = new DOMDocument();
        
        if(!$doc->loadXML($retornoUrl, LIBXML_NOERROR)){
        
            $localizacao = "Localiza��o n�o encontrada com as coordenadas: " . $coordX . " / " . $coordY;
            return false;
        }
        
        $AddressLocation = $doc->getElementsByTagName( "AddressLocation" );
        
        foreach($AddressLocation as $nodoAddressLocation){
        
            $address = $nodoAddressLocation->getElementsByTagName('address');
        
            foreach ($address as $nodoAddress){
                $street = $nodoAddress->getElementsByTagName('street');
                $rua = utf8_decode($street->item(0)->nodeValue);
        
                $houseNumber = $nodoAddress->getElementsByTagName('houseNumber');
                $numero = $houseNumber->item(0)->nodeValue;
                
                if ($numero != "") {
                    $rua .=", ".$numero;
                }
                
                $name = $nodoAddress->getElementsByTagName('name');
                $cidade = utf8_decode($name->item(0)->nodeValue);
        
                $state = $nodoAddress->getElementsByTagName('state');
                $estado = $state->item(0)->nodeValue;
        
            }
        
        }
        
        $localizacao = ($rua . $numero. " - " . $cidade . " - " . $estado);
        
        $retorno = " Dt chegada: " . $xmlUltimaLocalizacao->dataChegadaFormatada;
        $retorno .= " Dt posi��o: " . $xmlUltimaLocalizacao->dataHoraFormatada;
        $retorno .= " Latitude: " . $coordY;
        $retorno .= " Longitude: " . $coordX;
        $retorno .= " Endere�o: " . $localizacao;
        
        return $retorno;

    }

    /**
     * Respons�vel por receber exibir o formul�rio de edi��o ou invocar
     * o metodo para salvar os dados
     *
     * @return void
     */
    public function editar() {

        try {
            //Parametros
            $parametros = $this->tratarParametros();

            //Verifica se foi informado o id do cadastro
            if (isset($parametros->aotoid) && intval($parametros->aotoid) > 0) {
                //Realiza o CAST do parametro
                $parametros->aotoid = (int) $parametros->aotoid;

                //Pesquisa o registro para edi��o
                $dados = $this->dao->pesquisarPorID($parametros->aotoid);

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
     *
     * @return void
     */
    private function salvar(stdClass $dados) {

        //Inicia a transa��o
        $this->dao->begin();

        //Grava��o
        $gravacao = null;

        //Efetua a inser��o do registro
        $gravacao = $this->dao->inserir($dados);

        //$this->view->mensagemSucesso = self::MENSAGEM_SUCESSO_INCLUIR;
        //Comita a transa��o
        $this->dao->commit();

        return $gravacao;
    }

    /**
     * Grava os dados de hsit�rico de OS na base de dados.
     *
     * @param stdClass $dados Dados a serem gravados
     *
     * @return void
     */
    private function salvarHistoricoOS(stdClass $dados) {

        //Inicia a transa��o
        $this->dao->begin();

        //Grava��o
        $gravacao = null;

        //Efetua a inser��o do registro
        $gravacao = $this->dao->inserirHistoricoOs($dados);

        //Comita a transa��o
        $this->dao->commit();

        return $gravacao;
    }

    /**
     * Grava os dados de hist�rico de Contrato na base de dados.
     *
     * @param stdClass $dados Dados a serem gravados
     *
     * @return void
     */
    private function salvarHistoricoContrato(stdClass $dados) {

        //Inicia a transa��o
        $this->dao->begin();

        //Grava��o
        $gravacao = null;

        //Efetua a inser��o do registro
        $gravacao = $this->dao->inserirHistoricoContrato($dados);

        $this->view->mensagemSucesso = self::MENSAGEM_SUCESSO_INCLUIR;

        //Comita a transa��o
        $this->dao->commit();

        return $gravacao;
    }

    /**
     * Validar os campos obrigat�rios da pesquisa.
     *
     * @param stdClass $dados Dados a serem validados
     *
     * @throws Exception
     *
     * @return void
     */
    private function validarCamposPesquisa(stdClass $dados) {

        //Campos para destacar na view em caso de erro
        $camposDestaques = array();

        //Verifica se houve erro
        $error = false;

        /**
         * Verifica os campos obrigat�rios
         */
        if (!isset($dados->data_inicial) || trim($dados->data_inicial) == '') {
            $camposDestaques[] = array(
                'campo' => 'data_inicial'
            );
            $error = true;
        }
        if (!isset($dados->data_final) || trim($dados->data_final) == '') {
            $camposDestaques[] = array(
                'campo' => 'data_final'
            );
            $error = true;
        }
        if (!isset($dados->tipo) || trim($dados->tipo) == '') {
            $camposDestaques[] = array(
                'campo' => 'tipo'
            );
            $error = true;
        }

        if ($error) {
            $this->view->json = $camposDestaques;
            throw new Exception(self::MENSAGEM_ALERTA_CAMPOS_OBRIGATORIOS);
        }


        //Valida se uma data � maior que a outra
        $validacaoData = $this->validarDataMaiorIntervalo($dados->data_inicial, $dados->data_final, 1);

        if ($validacaoData == -1) {
            $camposDestaques[] = array(
                'campo' => 'data_inicial'
            );
            $camposDestaques[] = array(
                'campo' => 'data_final'
            );
            $this->view->json = $camposDestaques;
            throw new Exception(self::MENSAGEM_DATA_INICIAL_FINAL);
        }

        if ($validacaoData == 0) {
            $camposDestaques[] = array(
                'campo' => 'data_inicial'
            );
            $camposDestaques[] = array(
                'campo' => 'data_final'
            );
            $this->view->json = $camposDestaques;
            throw new Exception(self::MENSAGEM_PERIODO_SUPERIOR);
        }
    }

    /**
     * M�todo carregarMotivos()
     * Respons�vel por carregar os motivos conforme a a��o informada
     *
     * @return json
     */
    public function carregarMotivos() {
        try {

            $retorno = array();

            $parametros = $this->tratarParametros();

            $dados = $this->dao->buscarMotivos($parametros);

            foreach ($dados as $motivo) {
                $retorno[] = array(
                    'id' => $motivo->aoamoid,
                    'label' => utf8_encode($motivo->aoamdescricao)
                );
            };

            echo json_encode($retorno);
        } catch (ErrorException $e) {

            //Rollback em caso de erro
            $this->dao->rollback();
            echo json_encode($retorno);
        } catch (Exception $e) {

            //Rollback em caso de erro
            $this->dao->rollback();
            echo json_encode($retorno);
        }
        exit;
    }

    /**
     * M�todo buscarAcaoMotivo()
     * Respons�vel por buscar e exibir as a��es e os motivos no resultado da pesquisa
     *
     * @return json
     */
    public function buscarAcaoMotivo() {
        try {

            $retorno = array();

            $parametros = $this->tratarParametros();

            $dados = $this->dao->buscarAcaoMotivos($parametros->ordoid);

            $retorno = array(
                'acaoDescricao' => utf8_encode($dados->aoamdescricao_acao),
                'motivoDescricao' => utf8_encode($dados->aoamdescricao_motivo)
            );

            echo json_encode($retorno);
        } catch (ErrorException $e) {

            //Rollback em caso de erro
            $this->dao->rollback();
            echo json_encode($retorno);
        } catch (Exception $e) {

            //Rollback em caso de erro
            $this->dao->rollback();
            echo json_encode($retorno);
        }
        exit;
    }

    /**
     *
     * @param type $dataInicial
     * @param type $dataFinal
     * @param type $meses
     * @return int -1 Se data inicial for maior que final, 0 = se diferen�a foi maior que os meses
     */
    private function validarDataMaiorIntervalo($dataInicial, $dataFinal, $meses) {
        $dataInicioArr = explode('/', $dataInicial);
        $dataFimArr = explode('/', $dataFinal);

        $dataInicioTS = strtotime($dataInicioArr[2] . '-' . $dataInicioArr[1] . '-' . $dataInicioArr[0]);
        $dataFimTS = strtotime($dataFimArr[2] . '-' . $dataFimArr[1] . '-' . $dataFimArr[0]);

        if ($dataInicioTS > $dataFimTS) {
            return -1;
        }

        $diaInicial = intval($dataInicioArr[0]);
        $diaFinal = intval($dataFimArr[0]);

        $mesInicial = intval($dataInicioArr[1]);
        $mesFinal = intval($dataFimArr[1]);

        $anoInicial = intval($dataInicioArr[2]);
        $anoFinal = intval($dataFimArr[2]);

        if ($dataInicioArr[2] != $dataFimArr[2]) {
            $dataFimArr[1]+= 12;
        }

        $anoDiferenca = 0;

        if ($anoInicial != $anoFinal) {
            $anoDiferenca = $anoFinal - $anoInicial;
            $mesFinal += $anoDiferenca * 12;
        }
        $direrenca = $mesFinal - $mesInicial;

        if ($direrenca == $meses) {
            if ($diaFinal > $diaInicial) {
                return 0;
            }
        }

        if ($direrenca > $meses) {
            return 0;
        }

        return 1;
    }

    /**
     * M�todo carregar Tipo contratos()
     * Respons�vel por carregar os Tipos de Contratos
     *
     * @return json
     */
    public function carregarTipoContrato() {
        try {

            $retorno = array();

            $parametros = $this->tratarParametros();

            $dados = $this->dao->buscarTipoContrato($parametros->tpcoid);

            $retorno = array(
                'tipoContratoDescricao' => utf8_encode($dados->tpcdescricao)
            );

            echo json_encode($retorno);
        } catch (ErrorException $e) {

            //Rollback em caso de erro
            $this->dao->rollback();
            echo json_encode($retorno);
        } catch (Exception $e) {

            //Rollback em caso de erro
            $this->dao->rollback();
            echo json_encode($retorno);
        }
        exit;
    }

    /**
     * M�todo preparar dados para sub-relat�rio Quantidade de OS
     *
     * @return array $retorno
     */
    private function prepararDadosQuantidadeOS() {

        $retorno = array();

        foreach ($this->view->dados as $linha) {
            if (!isset($retorno[intval($linha->aoamoid)])) {
                $retorno[intval($linha->aoamoid)] = array(
                    'descricao' => $linha->aoamdescricao_acao,
                    'total' => 0
                );
            }
            $retorno[intval($linha->aoamoid)]['total']++;
        }
        //echo "<pre>"; print_r($retorno); echo "</pre>";

        return $retorno;
    }

    /**
     * M�todo preparar dados para sub-relat�rio Quantidade Atendimentos
     *
     * @return array $retorno
     */
    private function prepararQuantidadeAtendimentos() {

        $retorno = array();

        foreach ($this->view->dados as $linha) {
            if (!isset($retorno[trim($linha->ds_login)])) {
                $retorno[trim($linha->ds_login)] = array(
                    'atendente' => $linha->ds_login,
                    'total' => 0
                );
            }
            $retorno[trim($linha->ds_login)]['total']++;
        }

        return $retorno;
    }

    /**
     * M�todo preparar dados para sub-relat�rio Quantidade Atendimentos por A��o
     *
     * @return array $retorno
     */
    private function prepararAtendimentosPorAcao() {

        $retorno = array();
        foreach ($this->view->dados as $linha) {

            if (!isset($retorno[intval($linha->aoamoid)])) {
                $retorno[intval($linha->aoamoid)] = array(
                    'acao' => $linha->aoamdescricao_acao
                );
            }

            if (!isset($retorno[intval($linha->aoamoid)]['motivos'][trim($linha->aoamdescricao_motivo)])) {
                $retorno[intval($linha->aoamoid)]['motivos'][trim($linha->aoamdescricao_motivo)]['descricao'] = $linha->aoamdescricao_motivo;
            }
            $retorno[intval($linha->aoamoid)]['motivos'][trim($linha->aoamdescricao_motivo)]['total']++;
        }
        return $retorno;
    }

    /**
     * M�todo preparar dados para sub-relat�rio Quantidade por Projeto
     *
     * @return array $retorno
     */
    private function prepararQuantidadesProjetos() {

        $retorno = array();
        $projetos = array();
        $acoes = array();
        $dados = array();

        foreach ($this->view->dados as $linha) {
            if (!in_array($linha->eprnome, $projetos)) {
                $projetos[] = $linha->eprnome;
            }
            if (!in_array($linha->aoamdescricao_acao, $acoes)) {
                $acoes[] = $linha->aoamdescricao_acao;
            }

            if (!isset($dados[$linha->eprnome])) {
                $dados[$linha->eprnome] = array();
                $dados[$linha->eprnome]['subtotal'] = 0;
            }
            if (!isset($dados[$linha->eprnome][$linha->aoamdescricao_acao])) {
                $dados[$linha->eprnome][$linha->aoamdescricao_acao] = 0;
            }
            $dados[$linha->eprnome][$linha->aoamdescricao_acao]++;
            $dados[$linha->eprnome]['subtotal']++;
        }

        foreach ($dados as $projeto => $dado) {
            foreach ($acoes as $acao) {
                if (!isset($dados[$projeto][$acao])) {
                    $dados[$projeto][$acao] = 0;
                }
            }
        }
        $retorno = array(
            'projetos' => $projetos,
            'acoes' => $acoes,
            'dados' => $dados
        );
        return $retorno;
    }

}

