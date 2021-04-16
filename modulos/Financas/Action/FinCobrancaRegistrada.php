<?php

/**
 * Classe FinCobrancaRegistrada.
 * Camada de regra de neg�cio.
 *
 * @package  Financas
 * @author   Gustavo Molitor Porcides <gustavo.porcides.ext@sascar.com.br>
 * 
 */

require_once _SITEDIR_ . 'lib/Components/Paginacao/PaginacaoComponente.php';
require_once _SITEDIR_ . 'modulos/core/infra/autoload.php';

use module\Boleto\BoletoService as Boleto;
use module\BoletoRegistrado\BoletoRegistradoModel;
use module\Parametro\ParametroCobrancaRegistrada;
use module\EscritorRemessaCNABSantander\EscritorSegmentoPRemessaCNABSantanderModel;

class FinCobrancaRegistrada {

    /**
     * Objeto DAO da classe.
     * 
     * @var CadExemploDAO
     */
    private $dao;

    /**
     * Mensagem de alerta para campos obrigat�rios n�o preenchidos
     * @const String
     */
    const MENSAGEM_ALERTA_CAMPOS_OBRIGATORIOS = "Existem campos obrigat�rios n�o preenchidos.";

    /**
     * Mensagem de sucesso para inser��o do registro
     * @const String
     */
    const MENSAGEM_SUCESSO_INCLUIR = "Registro inclu�do com sucesso.";

    /**
     * Mensagem de sucesso para altera��o do registro
     * @const String
     */
    const MENSAGEM_SUCESSO_ATUALIZAR = "Registro alterado com sucesso.";

    /**
     * Mensagem de sucesso para exclus�o do registro
     * @const String
     */
    const MENSAGEM_SUCESSO_EXCLUIR = "Registro exclu�do com sucesso.";

    /**
     * Mensagem para nenhum registro encontrado
     * @const String
     */
    const MENSAGEM_NENHUM_REGISTRO = "Nenhum registro encontrado.";
    
    /**
     * Mensagem de erro para o processamentos dos dados
     * @const String
     */
    const MENSAGEM_ERRO_PROCESSAMENTO = "Houve um erro no processamento dos dados.";

    /**
     * Mensagem de erro para data inv�lida
     * @const String
     */
    const MENSAGEM_ERRO_DATA_INVALIDA = "Data selecionada � inv�lida.";

    /**
     * Mensagem de erro para data inv�lida
     * @const String
     */
    const MENSAGEM_ERRO_QTDE_TITULOS_INVALIDA = "Quantidade de t�tulos fora do limite permitido.";

    /**
     * Cont�m dados a serem utilizados na View.
     * 
     * @var stdClass 
     */
    private $view;

    /**
     * Id do usuario logado
     * @var stdClass
     */
    public $usuoid;

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
        $this->view->mensagemInfo = 'Os campos com * s�o obrigat�rios.';

        // Dados para view
        $this->view->dados = null;

        // Filtros/parametros utlizados na view
        $this->view->parametros = null;

        // Status de uma transa��o 
        $this->view->status = false;

        $this->view->paginacao = null;
        $this->view->totalResultados = 0;

        $this->usuoid = isset($_SESSION->usuario->oid) ? $_SESSION->usuario->oid : 2750;
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

            $this->view->dados = new stdClass();

            $this->view->parametros = $this->tratarParametros();
            
            // Inicializa os dados
            $this->inicializarParametros($this->view->parametros->acao);

            // Popula combos do formulario
            $this->populaCombosFormulario();

            // Realiza chamada da pesquisa de remessa
            if(isset($this->view->parametros->acao) && $this->view->parametros->acao == 'remessa' && ($this->view->parametros->origem == 'P' || isset($this->view->parametros->pagina))) {

                $this->view->dados->resultados = $this->pesquisaRemessa($this->view->parametros);
                $this->view->totalResultados = count($this->view->dados->resultados);

                if(isset($_SESSION['paginacao'])) {
                    unset($_SESSION['paginacao']);
                }

                $paginacao = new PaginacaoComponente();

                $this->view->paginacao = $paginacao->gerarPaginacao($this->view->totalResultados);

                $this->view->dados->resultadoPesquisaRemessa = $this->pesquisaRemessa($this->view->parametros, $paginacao->buscarPaginacao());

                if(count($this->view->dados->resultadoPesquisaRemessa) <= 0) {
                    throw new Exception(self::MENSAGEM_NENHUM_REGISTRO);
                }

                $this->view->dados->mostraPesquisaRemessa = true;
            }

            // Realiza chamada da gera��o de CSV da remessa
            if(isset($this->view->parametros->acao) && $this->view->parametros->acao == 'gerarCSVRemessa' && $this->view->parametros->origem == 'P') {

                $this->view->parametros->acao = 'remessa';

                $dadosArquivo = $this->geraCSVRemessa($this->view->parametros);
                $this->view->dados->mostraDownloadRemessa = true;

                $this->view->dados->nomeArquivoRemessa = $dadosArquivo->file_name;
                $this->view->dados->caminhoDownloadRemessa = $dadosArquivo->file_path . $dadosArquivo->file_name;      
            }

            // Realiza chamada da pesquisa de rejeitado
            if(isset($this->view->parametros->acao) && $this->view->parametros->acao == 'rejeitado' && ($this->view->parametros->origem == 'P' || isset($this->view->parametros->pagina))) {
                $this->view->dados->resultados = $this->pesquisaRejeitado($this->view->parametros, null);
                $this->view->totalResultados = count($this->view->dados->resultados);

                if(isset($_SESSION['paginacao'])) {
                    unset($_SESSION['paginacao']);
                }

                $paginacao = new PaginacaoComponente();

                $this->view->paginacao = $paginacao->gerarPaginacao($this->view->totalResultados);

                $this->view->dados->resultadoPesquisaRejeitado = $this->pesquisaRejeitado($this->view->parametros, $paginacao->buscarPaginacao());

                if(count($this->view->dados->resultadoPesquisaRejeitado) <= 0) {
                    throw new Exception(self::MENSAGEM_NENHUM_REGISTRO);
                }

                $this->view->dados->mostraPesquisaRejeitado = true;
            }
            
            // Realiza chamada da gera��o de CSN de rejeitado
            if(isset($this->view->parametros->acao) && $this->view->parametros->acao == 'gerarCSVRejeitado' && $this->view->parametros->origem == 'P') {
                $this->view->parametros->acao = 'rejeitado';

                $dadosArquivo = $this->geraCSVRejeitado($this->view->parametros);
                $this->view->dados->mostraDownloadRejeitado = true;

                $this->view->dados->nomeArquivoRejeitado = $dadosArquivo->file_name;
                $this->view->dados->caminhoDownloadRejeitado = $dadosArquivo->file_path . $dadosArquivo->file_name; 
            }

            //[ORGMKTOTVS-837] - bloqueio CRIS
            if (!INTEGRACAO_TOTVS_ATIVA) {
                // Realiza chamada da gera��o de arquivo CSV da aba arquivo
                if (isset($this->view->parametros->acao) && $this->view->parametros->acao == 'arquivo' && $this->view->parametros->origem == 'arquivo') {
                    $dadosArquivo = $this->geraCSVArquivo($this->view->parametros);
                    $this->view->dados->mostraDownloadArquivo = true;

                    $this->view->dados->nomeArquivo = $dadosArquivo->file_name;
                    $this->view->dados->caminhoDownloadArquivo = $dadosArquivo->file_path . $dadosArquivo->file_name;
                }
                // Realiza chamada do CRON
                if(isset($this->view->parametros->acao) && $this->view->parametros->acao == 'arquivoCron' && $this->view->parametros->origem == 'arquivo') {
                    if($this->prepararEnvioArquivoAFT()){
                       $this->view->mensagemSucesso = 'Opera��o conclu�da com sucesso.';
                   }
               }
            }
            // Fim - [ORGMKTOTVS-837] - bloqueio CRIS

       } catch (ErrorException $e) {

        $this->view->mensagemErro = $e->getMessage();

    } catch (Exception $e) {

        $this->view->mensagemAlerta = $e->getMessage();

    }

        //Incluir a view padr�o
        //@TODO: Montar dinamicamente o caminho apenas da view Index
    require_once _MODULEDIR_ . "Financas/View/fin_cobranca_registrada/index.php";
}

public function conciliacao(){

    try {

        $this->view->parametros = $this->tratarParametros();
        $this->view->parametros->acao = 'conciliacao';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            require_once _MODULEDIR_ . "Financas/Action/RetornoBoletoSantanderReader.php";
            require_once _MODULEDIR_ . "Financas/DAO/FinTitulosDAO.php";

            if(empty($_FILES['arquivo_retorno'])){
                throw new Exception("Falha ao enviar arquivo");
            }

            $arquivoRetorno = @fopen($_FILES['arquivo_retorno']['tmp_name'], "r");

            if(!$arquivoRetorno){
                throw new Exception('Obrigat�rio carregar arquivo CSV do banco.');
            }

            $retornoBoletoSantanderReader = new RetornoBoletoSantanderReader($arquivoRetorno);

            $titulosImportados = $retornoBoletoSantanderReader->getTitulos();

            global $conn;

            $finTitulosDAO = new FinTitulosDAO($conn);

            $titulosErp = $finTitulosDAO->getTitulosSantanderRegistradosPorPeriodo($retornoBoletoSantanderReader->getDataInicial(), $retornoBoletoSantanderReader->getDataFinal());

            $arrTitulosSantander = array();
            $arrTitulosERP = array();

            foreach($titulosImportados as $tituloSantander){
                $arrTitulosSantander[] = $tituloSantander['seu_numero'];
            }

            foreach($titulosErp as $tituloErp){
                $arrTitulosERP[] = $tituloErp['evtititoid'];
            }

                // Existe no arquivo e n�o existe no ERP
            $titulosBancoErp = array_diff($arrTitulosSantander, $arrTitulosERP);
            $titulosBancoErp = array_filter($titulosBancoErp);
            $relatorioBancoErp = array();

                // Existe no ERP e n�o exist no arquivo
            $titulosErpBanco = array_diff($arrTitulosERP, $arrTitulosSantander);
            $titulosErpBanco = array_filter($titulosErpBanco);
            $relatorioErpBanco = array();

            if(!empty($titulosBancoErp) || !empty($titulosErpBanco)){

                $arrCabecalhoCsv = array(
                    'Seu Numero',
                    'Nosso Numero',
                    'Parcela',
                    'Data Registro',
                    'Situa��o do T�tulo',
                    'Valor Titulo',
                    'Vencimento',
                    'Nome Pagador'
                );

                $relatorioBancoErp[] = $arrCabecalhoCsv;
                $relatorioErpBanco[] = $arrCabecalhoCsv;

                    // Popula informa��es do relat�rio "Existe no arquivo e n�o existe no ERP"
                foreach($titulosBancoErp as $titulo){

                    foreach($titulosImportados as $tituloSantander){

                        if($titulo == $tituloSantander['seu_numero']){

                            $explode_vencimento = explode('/', $tituloSantander['vencimento']);
                            $data_vencimento = strtotime($explode_vencimento[2] . '-'. $explode_vencimento[1] . '-' . $explode_vencimento[0]);

                            $relatorioBancoErp[] = array(
                                    $tituloSantander['seu_numero'], // Seu numero
                                    $tituloSantander['nosso_numero'], // Nosso numero
                                    '-', // Parcela
                                    '-', // Data Registro
                                    ($data_vencimento >= strtotime('now') ? '� vencer' : 'Vencido'), // Situa��o do t�tulo
                                    $tituloSantander['valor'], // Valor do t�tulo
                                    $tituloSantander['vencimento'], // Vencimento
                                    $tituloSantander['pagador'] // Pagador
                                );

                            break;                                
                        }

                    }

                }

                    // Popula informa��es do relat�rio "Existe no ERP e n�o existe no arquivo"
                foreach($titulosErpBanco as $titulo){

                    foreach($titulosErp as $tituloErp){

                        if($titulo == $tituloErp['evtititoid']){

                            $data_vencimento = strtotime($tituloErp['titdt_vencimento']);

                            $relatorioErpBanco[] = array(
                                    $tituloErp['evtititoid'], // Seu numero
                                    $tituloErp['evtititoid'], // Nosso numero
                                    (!empty($tituloErp['titno_parcela']) ? $tituloErp['titno_parcela'] : '-'), // Parcela
                                    $tituloErp['evtidt_geracao'], // Data Registro
                                    ($data_vencimento >= strtotime('now') ? '� vencer' : 'Vencido'), // Situa��o do t�tulo
                                    $tituloErp['titvl_titulo'], // Valor do t�tulo
                                    $tituloErp['titdt_vencimento'], // Vencimento                                  
                                    $tituloErp['clinome'] // Pagador
                                );


                        }

                    }

                }

                $nomeRelatorioBancoErp = "NAO_ENCONTRADOS_NO_ERP_" . date('d_m_Y_His') . ".csv";
                $nomeRelatorioErpBanco = "NAO_ENCONTRADOS_NO_BANCO_" . date('d_m_Y_His') . ".csv";

                if(!empty($titulosErpBanco)){
                    $this->gerarCsv($nomeRelatorioErpBanco, $relatorioErpBanco);
                    $this->view->nomeRelatorioErpBanco = $nomeRelatorioErpBanco;
                }

                if(!empty($titulosBancoErp)){
                    $this->gerarCsv($nomeRelatorioBancoErp, $relatorioBancoErp);
                    $this->view->nomeRelatorioBancoErp = $nomeRelatorioBancoErp;                            
                }

                $this->view->parametros->respostaInfo  = 'Concilia��o dos registros de t�tulos gerou arquivo(s) com diferen�as entre o ERP e o banco';

            }else{

                $this->view->parametros->respostaSucesso = 'Concilia��o dos registros de t�tulos com sucesso sem diferen�as entre o ERP e o banco';

            }

        }

    }catch(Exception $e){

        $this->view->parametros->respostaErro = $e->getMessage();

    }

    if(!empty($arquivoRetorno))
    {
        fclose($arquivoRetorno);
    }

    require_once _MODULEDIR_ . "Financas/View/fin_cobranca_registrada/aba_conciliacao_titulos.php";

}

private function gerarCsv($nome, $data){

    $file = fopen("/var/www/docs_temporario/$nome", 'w');

    foreach($data as $row){
        fputcsv($file, $row, ";");
    }

    fclose($file);

}

public function downloadCsv(){

    $diretorio = '/var/www/docs_temporario/';

    $arquivo = !empty($_GET['arquivo']) ? base64_decode($_GET['arquivo']) : null;

    if(!empty($arquivo)){

        header('Content-type: text/csv');
        header('Content-disposition: attachment; filename="'.$arquivo.'"');
        readfile($diretorio . $arquivo);

    }else{

        die('Arquivo inv�lido');

    }

}

    /**
     * Configura componente de pagina��o
     * @return [type] [description]
     */
    private function configuraPaginacao($totalResultados) {

    }

    /**
     * Popula combos do formulario
     * @return [type] [description]
     */
    private function populaCombosFormulario() {

        $this->view->comboFormaCobrancaRemessa = $this->dao->buscaFormasCobranca();
        $this->view->comboFormaCobrancaRejeitado = $this->dao->buscaFormasCobranca();
        $this->view->comboFormaCobrancaArquivo = $this->dao->buscaFormasCobranca();
    }

    /**
     * Realiza chamada para a listagem da remessa
     * @param  stdClass $parametros [description]
     * @return [type]               [description]
     */
    private function pesquisaRemessa(stdClass $parametros, $paginacao = null) {
        $this->validarCamposRemessa($parametros);

        return $this->dao->pesquisaRemessa($parametros, $paginacao);
    }

    /**
     * Exclui remessa pelo passthru
     * @param  stdClass $parametros [description]
     * @return [type]               [description]
     */
    public function excluirRemessa() {
        $parametros = $this->tratarParametros();
        $this->dao->excluirRemessa($parametros->idRemessa);
        return;
    }

    /**
     * Desvincula t�tulo rejeitado
     * @return [type] [description]
     */
    public function desvinculaRejeitado() {
        $parametros = $this->tratarParametros();

        $titulos = explode(',', $parametros->idTitulo);
        $tipo = explode(',', $parametros->tipo);

        $total_titulos = count($titulos);

        try {
            for($cont = 0; $cont < $total_titulos; $cont++) {
                $this->dao->desvinculaRejeitado($titulos[$cont], $tipo[$cont]);
            }
        } catch(Exception $e) {
            throw new Exception('Ocorreu um erro ao desvincular os t�tulos da remessa.');
        }
        return;
    }

    /**
     * Realiza chamada para fazer a pesquisa de rejeitados
     * @return [type] [description]
     */
    private function pesquisaRejeitado(stdClass $parametros, $paginacao = null) {
        $this->validarCamposRejeitado($parametros);

        $tipos = $this->dao->buscaTiposRejeitado();
        $tipos = $tipos[0]->tipos;

        return $this->dao->pesquisaRejeitado($parametros, $tipos, $paginacao);
    }

    /**
     * Realiza chamada para a gera��o do CSV da remessa
     * @param  stdClass $parametros [description]
     * @return [type]               [description]
     */
    private function geraCSVRemessa(stdClass $parametros) {

        $this->validarCamposRemessa($parametros);
        $arquivo = new stdClass();

        $tiposPermitidos = $this->dao->buscaTiposPermitidos();
        $tiposPermitidos = $tiposPermitidos[0]->tipos;

        $resultado = $this->dao->buscaDadosCSVRemessa($parametros, $tiposPermitidos);

        if(count($resultado) <= 0) {
            throw new Exception("N�o foi encontrado nenhum t�tulo para gera��o do arquivo.");
            return null;
        }

        $arquivo->file_path = "/var/www/docs_temporario/";
        $arquivo->file_name = "titulo_sem_remessa_santander" . date('_d_m_Y_His') . ".csv";

        $file = fopen($arquivo->file_path . $arquivo->file_name, 'a');
        
        foreach($resultado as $linha) {
            $data = new DateTime($linha->data_vencimento);
            $content = $linha->nome_banco . ';' . 
            $linha->forma_cobranca. ';' . 
            $linha->tipo_operacao. ';' .
            $linha->numero_titulo . ';' . 
            $linha->nome_cliente . ';' . 
            date_format($data, 'd/m/Y') . ';' . 
            $linha->valor . ';' . 
            PHP_EOL;
            fwrite($file, $content);
        }
        

        return $arquivo;
    }

    /**
     * Realiza chamada para a gera��o do CSV de rejeitado
     * @param  stdClass $parametros [description]
     * @return [type]               [description]
     */
    private function geraCSVRejeitado(stdClass $parametros) {
    	
        $this->validarCamposRejeitado($parametros);
        $arquivo = new stdClass();

        $tipos = $this->dao->buscaTiposRejeitado();
        
        $tipos = $tipos[0]->tipos;
        
        $resultado = $this->dao->pesquisaRejeitado($parametros, $tipos);

        $arquivo->file_path = "/var/www/docs_temporario/";
        $arquivo->file_name = "titulos_rejeitados_" . $banco . date('_d_m_Y_His') . ".csv";

        $file = fopen($arquivo->file_path . $arquivo->file_name, 'a');

        foreach($resultado as $linha) {
            $data = new DateTime($linha->data_cadastro);

            $cod_retorno = explode(',', substr($linha->cod_retorno, 1, -1));
            $msg_retorno = explode(',', substr($linha->msg_retorno, 1, -1));

            $campo_retorno = '';

            for($i = 0; $i < count($cod_retorno); $i++){
                if(!empty($cod_retorno[$i]) && $cod_retorno[$i] !== 'NULL'){
                    $campo_retorno .= $i > 0 ? ', ' : '';
                    $campo_retorno .= $cod_retorno[$i] . ' - ' . $msg_retorno[$i];
                }
            }

            $content =  $linha->nome_banco . ';' . 
            $linha->numero_remessa . ';' . 
            $linha->nome_cliente . ';' . 
            $linha->numero_titulo . ';' . 
            date_format($data, 'd/m/Y') . ';' . 
            $campo_retorno . ';' . 
            PHP_EOL;
            fwrite($file, $content);
        }

        return $arquivo;
    }


    public function formataData($data){

        $dia = substr($data, 0, 2);
        $mes = substr($data, 2, 2);
        $ano = substr($data, 4, 4);
        
        return "{$ano}-{$mes}-{$dia}";
    }

    /**
     * Realiza chamada para a gera��o do CSV do arquivo
     * @param  stdClass $parametros [description]
     * @return [type]               [description]
     */
    public function geraCSVArquivo(stdClass $parametros, $tipoEnvio = 'manual', $rtcrusuoid = '') {

        $this->validarCamposArquivo($parametros);
        $arquivo = new stdClass();

        require_once _MODULEDIR_.'Financas/DAO/FinCobrancaRegistradaDAO.php';
        
        global $conn;
        $this->dao = new FinCobrancaRegistradaDAO($conn);

        $caminhoPasta = ParametroCobrancaRegistrada::getPastaArquivoRemessa();

        if(!file_exists($caminhoPasta)) {
            @mkdir($caminhoPasta, 0777, true);
        }

        $banco = $this->dao->buscaBanco($parametros->ddl_forma_cobranca_arquivo);
        $agencia = $banco[0]->cfbagencia;
        $conta_corrente = $banco[0]->cfbconta_corrente;
        $banco = $banco[0]->cfbbanco;

        $formaCobranca = ParametroCobrancaRegistrada::getFormasCobrancaParaRegistro();
        
        try {

            $this->dao->begin();

            $titulos = $this->dao->buscaTitulosRemessa($parametros->txt_qtde_titulos, null, $formaCobranca);
            $total_titulos = count($titulos);

            $numBoletosRestantes = (int)$parametros->txt_qtde_titulos - $total_titulos;
            $numBoletosRestantes = $numBoletosRestantes < 0 ? 0 : $numBoletosRestantes;

            if($total_titulos <= 0) {
            	throw new Exception("N�o foi encontrado nenhum t�tulo para gera��o de remessa.");
            	return null;
            }

            $id_titulos = '';
            $id_titulos_retencao = '';
            $id_titulos_consolidado = '';

            if(!empty($titulos)){

                foreach($titulos as $titulo) {
                    switch ($titulo->tipo) {
                        case 'titulo':
                        $id_titulos .= $titulo->identificacao_titulo . ',';
                        break;
                        case 'retencao':
                        $id_titulos_retencao .= $titulo->identificacao_titulo . ',';
                        break;
                        case 'consolidado':
                        $id_titulos_consolidado .= $titulo->identificacao_titulo . ',';
                        break;
                    }
                }

            }

            $id_titulos = rtrim($id_titulos, ',');
            $id_titulos_retencao = rtrim($id_titulos_retencao, ',');
            $id_titulos_consolidado = rtrim($id_titulos_consolidado, ',');
            
            //Insere remessa na tabela e altera a forma de cobran�a do t�tulo para 84 = Cobran�a Registrada Samtamder
            $nova_remessa = $this->dao->insereRemessa($parametros->ddl_forma_cobranca_arquivo, $this->usuoid, $banco, $id_titulos, $id_titulos_retencao, $id_titulos_consolidado);

            // Monta o header da remessa
            $dadosHeaderRemessa = $this->dao->buscaDadosHeaderRemessaSantander();
            foreach($dadosHeaderRemessa as $resultado) {
                $resultado->num_sequencial_arquivo = $nova_remessa[0];

                $linhaHeaderRemessa =   $this->completaCampoLayout($resultado->cod_banco, 3, 'N') .
                $this->completaCampoLayout($resultado->lote_servico, 4, 'N') .
                $this->completaCampoLayout($resultado->tipo_registro, 1, 'N') .
                $this->completaCampoLayout('', 8, 'A') .
                $this->completaCampoLayout($resultado->tipo_inscricao_empresa, 1, 'N') .
                $this->completaCampoLayout(str_replace(str_split('/.-'), '', $resultado->inscricao_empresa), 15, 'N') .
                $this->completaCampoLayout($resultado->cod_transmissao, 15, 'N') .
                $this->completaCampoLayout('', 25, 'A') .
                $this->completaCampoLayout($resultado->nome_empresa, 30, 'A') .
                $this->completaCampoLayout($resultado->nome_banco, 30, 'A') .
                $this->completaCampoLayout('', 10, 'A') .
                $this->completaCampoLayout($resultado->cod_remessa, 1, 'N') .
                $this->completaCampoLayout($resultado->data_geracao_arquivo, 8, 'N') .
                $this->completaCampoLayout('', 6, 'A') .
                $this->completaCampoLayout($resultado->num_sequencial_arquivo, 6, 'N') .
                $this->completaCampoLayout($resultado->versao_layout, 3, 'N') .
                $this->completaCampoLayout('', 74, 'A') .
                PHP_EOL;
            }

            // Monta o header do lote
            $dadosHeaderLote = $this->dao->buscaDadosHeaderLoteSantander();
            foreach($dadosHeaderLote as $resultado) {
                $resultado->numero_remessa_retorno = $nova_remessa[0];

                $linhaHeaderLote =      $this->completaCampoLayout($resultado->cod_banco, 3, 'N') .
                $this->completaCampoLayout($resultado->lote_servico, 4, 'N') .
                $this->completaCampoLayout($resultado->tipo_registro, 1, 'N') .
                $this->completaCampoLayout($resultado->tipo_operacao, 1, 'A') .
                $this->completaCampoLayout($resultado->tipo_servico, 2, 'N') .
                $this->completaCampoLayout('', 2, 'A') .
                $this->completaCampoLayout($resultado->versao_layout_lote, 3, 'N') .
                $this->completaCampoLayout('', 1, 'A') .
                $this->completaCampoLayout($resultado->tipo_inscricao_empresa, 1, 'N') .
                $this->completaCampoLayout(str_replace(str_split('/.-'), '', $resultado->inscricao_empresa), 15, 'N') .
                $this->completaCampoLayout('', 20, 'A') .
                $this->completaCampoLayout($resultado->cod_transmissao, 15, 'N') .
                $this->completaCampoLayout('', 5, 'A') .
                $this->completaCampoLayout($resultado->nome_beneficiario, 30, 'A') .
                $this->completaCampoLayout($resultado->mensagem1, 40, 'A') .
                $this->completaCampoLayout($resultado->mensagem2, 40, 'A') .
                $this->completaCampoLayout($resultado->numero_remessa_retorno, 8, 'N') .
                $this->completaCampoLayout($resultado->data_gravacao, 8, 'N') .
                $this->completaCampoLayout('', 41, 'A').
                PHP_EOL;
            }

            // Faz um loop pelos t�tulos para montar os segmentos P, Q e R
            $sequencialSegmento = 1;

            if(!empty($titulos)){

                foreach($titulos as $resultado) {
                    $cpf = Null;
                    $cnpj = Null;
                    if($resultado->tipo_inscricao == "1"){
                        $cpf = str_replace(str_split('/.-'), '', $resultado->inscricao);
                    }elseif($resultado->tipo_inscricao == "2"){
                        $cnpj = str_replace(str_split('/.-'), '', $resultado->inscricao);
                    }
                    $mensagem = $resultado->mensagem1.' '.$resultado->mensagem2.' '.$resultado->mensagem3.' '.$resultado->mensagem4;


                    if(!isset($resultado->cancelamento) || $resultado->cancelamento == false){

                        $boletoRegistro = new BoletoRegistradoModel();
                        
                        $boletoRegistro->setTituloId($resultado->numero_documento);
                        $boletoRegistro->setCodigoOrigem(1);
                        $boletoRegistro->setCodigoBanco($resultado->cod_banco);
                        $boletoRegistro->setTipoDocumento($resultado->tipo_inscricao);
                        $boletoRegistro->setCpf($cpf);
                        $boletoRegistro->setCnpj($cnpj);
                        $boletoRegistro->setNome($resultado->nome);
                        $boletoRegistro->setEndereco($resultado->endereco);
                        $boletoRegistro->setBairro($resultado->bairro);
                        $boletoRegistro->setCidade($resultado->cidade);
                        $boletoRegistro->setUf($resultado->uf);
                        $boletoRegistro->setCep($resultado->cep);
                        $boletoRegistro->setDataVencimento($this->formataData($resultado->data_vencimento));
                        $boletoRegistro->setDataEmissao($this->formataData($resultado->data_emissao));
                        $boletoRegistro->setCodigoEspecie($resultado->especie_titulo);
                        $boletoRegistro->setValorNominal($resultado->valor_nominal);
                        $boletoRegistro->setPercentualMulta($resultado->valor_multa);
                        $boletoRegistro->setQuantidadeDiasMulta(0);
                        $boletoRegistro->setPercentualJuros($resultado->valor_mora); //// O valor � referente ao de juros, o nome da v�riavel que est� errado, por conta da query, cujo alias foi errado.
                        $boletoRegistro->setTipoDesconto($resultado->cod_desconto1);
                        $boletoRegistro->setValorDesconto(($resultado->valor_desconto1 != '0') ? $resultado->valor_desconto1 : 0);
                        $boletoRegistro->setDataLimiteDesconto($this->formataData($resultado->data_desconto1));
                        $boletoRegistro->setValorAbatimento(($resultado->valor_abatimento != '') ? $resultado->valor_abatimento : 0);
                        $boletoRegistro->setTipoProtesto(($resultado->cod_protesto != '0') ? $resultado->cod_protesto : 0);
                        $boletoRegistro->setQuantidadeDiasProtesto(($resultado->numero_dias_protesto != '') ? $resultado->numero_dias_protesto : 0);
                        $boletoRegistro->setQuantidadeDiasBaixa($resultado->numero_dias_baixa);
                        $boletoRegistro->setMensagem(($mensagem != "   ") ? $mensagem : "Null");
                        $boletoRegistro->setValorFace($resultado->valor_nominal); //// � o mesmo do valor nominal

                        $boletoId = $boletoRegistro->gerarBoletoRegistro();

                    }else{

                        $boletoId = $resultado->id_boleto;

                    }
                    
                    $posHifen = strpos($conta_corrente, '-');

                    if(!$posHifen) {
                        $resultado->numero_conta_corrente = '';
                        $resultado->digito_conta = '';
                    } else {
                        $resultado->numero_conta_corrente = substr($conta_corrente, 0, $posHifen);
                        $resultado->conta_fidc = substr($conta_corrente, 0, $posHifen);
                        $resultado->digito_conta = substr($conta_corrente, $posHifen + 1, strlen($conta_corrente));
                        $resultado->digito_conta_fidc = substr($conta_corrente, $posHifen + 1, strlen($conta_corrente));
                    }
                    
                    $resultado->sequencial_detalhe_p = $sequencialSegmento;
                    $sequencialSegmento++;

                    $linhaSegmento .=   $this->completaCampoLayout($resultado->cod_banco, 3, 'N') .
                    $this->completaCampoLayout($resultado->numero_lote, 4, 'N') .
                    $this->completaCampoLayout($resultado->tipo_registro_p, 1, 'N') .
                    $this->completaCampoLayout($resultado->sequencial_detalhe_p, 5, 'N') .
                    $this->completaCampoLayout($resultado->cod_segmento_p, 1, 'A') .
                    $this->completaCampoLayout('', 1, 'A') .
                    $this->completaCampoLayout($resultado->cod_movimento_p, 2, 'N') .
                    $this->completaCampoLayout($resultado->agencia_fidc, 4, 'N') .
                    $this->completaCampoLayout($resultado->digito_agencia_fidc, 1, 'N') .
                    $this->completaCampoLayout($resultado->numero_conta_corrente, 9, 'N') .
                    $this->completaCampoLayout($resultado->digito_conta, 1, 'N') .
                    $this->completaCampoLayout($resultado->conta_fidc, 9, 'N') .
                    $this->completaCampoLayout($resultado->digito_conta_fidc, 1, 'N') .
                    $this->completaCampoLayout('', 2, 'A') .
                                        $this->completaCampoLayout('', 13, 'N') . //nosso numero
                                        $this->completaCampoLayout($resultado->tipo_cobranca, 1, 'N') .
                                        $this->completaCampoLayout($resultado->forma_cadastramento, 1, 'N') .
                                        $this->completaCampoLayout($resultado->tipo_documento, 1, 'N') .
                                        $this->completaCampoLayout('', 1, 'A') .
                                        $this->completaCampoLayout('', 1, 'A') .
                                        $this->completaCampoLayout($boletoId, 15, 'A') .
                                        $this->completaCampoLayout($resultado->data_vencimento, 8, 'N') .
                                        $this->completaCampoLayout($resultado->valor_nominal, 15, 'N', 'M') .
                                        $this->completaCampoLayout($resultado->agencia_cobranca, 4, 'N') .
                                        $this->completaCampoLayout($resultado->digito_agencia_beneficiario, 1, 'N') .
                                        $this->completaCampoLayout('', 1, 'A') .
                                        $this->completaCampoLayout($resultado->especie_titulo, 2, 'N') .
                                        $this->completaCampoLayout($resultado->identificador_aceite, 1, 'A') .
                                        $this->completaCampoLayout($resultado->data_emissao, 8, 'N') .
                                        $this->completaCampoLayout($resultado->cod_juros_mora, 1, 'N') .
                                        $this->completaCampoLayout($resultado->data_juros_mora, 8, 'N') .
                                        $this->completaCampoLayout($resultado->valor_mora, 15, 'N', 'J') .
                                        $this->completaCampoLayout($resultado->cod_desconto1, 1, 'N') .
                                        $this->completaCampoLayout($resultado->data_desconto1, 8, 'N') .
                                        $this->completaCampoLayout($resultado->valor_desconto1, 15, 'N', 'M') .
                                        $this->completaCampoLayout($resultado->valor_iof, 15, 'N', 'M') .
                                        $this->completaCampoLayout($resultado->valor_abatimento, 15, 'N', 'M') .
                                        $this->completaCampoLayout($resultado->identificacao_titulo, 25, 'A') .
                                        $this->completaCampoLayout($resultado->cod_protesto, 1, 'N') .
                                        $this->completaCampoLayout($resultado->numero_dias_protesto, 2, 'N') .
                                        $this->completaCampoLayout($resultado->cod_baixa, 1, 'N') .
                                        $this->completaCampoLayout('', 1, 'N') .
                                        $this->completaCampoLayout($resultado->numero_dias_baixa, 2, 'N') .
                                        $this->completaCampoLayout($resultado->cod_moeda, 2, 'N') .
                                        $this->completaCampoLayout('', 11, 'A') . 
                                        PHP_EOL;

                                        if(isset($resultado->cancelamento) || $resultado->cancelamento == true){
                                            break;
                                        }

                                        $resultado->sequencial_detalhe_q = $sequencialSegmento;
                                        $sequencialSegmento++;

                                        $resultado->sufixo_cep = substr($resultado->cep, -3, 3);

                                        $linhaSegmento .=   $this->completaCampoLayout($resultado->cod_banco, 3, 'N') .
                                        $this->completaCampoLayout($resultado->numero_lote, 4, 'N') .
                                        $this->completaCampoLayout($resultado->tipo_registro_q, 1, 'N') .
                                        $this->completaCampoLayout($resultado->sequencial_detalhe_q, 5, 'N') .
                                        $this->completaCampoLayout($resultado->cod_segmento_q, 1, 'A') .
                                        $this->completaCampoLayout('', 1, 'A') .
                                        $this->completaCampoLayout($resultado->cod_movimento_q, 2, 'N') .
                                        $this->completaCampoLayout($resultado->tipo_inscricao, 1, 'N') .
                                        $this->completaCampoLayout(str_replace(str_split('/.-'), '', $resultado->inscricao), 15, 'N') .
                                        $this->completaCampoLayout($resultado->nome, 40, 'A') .
                                        $this->completaCampoLayout($resultado->endereco, 40, 'A') .
                                        $this->completaCampoLayout($resultado->bairro, 15, 'A') .
                                        $this->completaCampoLayout($resultado->cep, 5, 'N') .
                                        $this->completaCampoLayout($resultado->sufixo_cep, 3, 'N') .
                                        $this->completaCampoLayout($resultado->cidade, 15, 'A') .
                                        $this->completaCampoLayout($resultado->uf, 2, 'A') .
                                        $this->completaCampoLayout($resultado->tipo_avalista, 1, 'N') .
                                        $this->completaCampoLayout(str_replace(str_split('/.-'), '', $resultado->inscricao_avalista), 15, 'N') .
                                        $this->completaCampoLayout($resultado->nome_avalista, 40, 'A') .
                                        $this->completaCampoLayout($resultado->identificador_carne, 3, 'N') .
                                        $this->completaCampoLayout($resultado->sequencial_parcela, 3, 'N') .
                                        $this->completaCampoLayout($resultado->total_parcelas, 3, 'N') .
                                        $this->completaCampoLayout($resultado->numero_plano, 3, 'N') .
                                        $this->completaCampoLayout('', 19, 'A') .
                                        PHP_EOL;

                                        $resultado->sequencial_detalhe_r = $sequencialSegmento;
                                        $sequencialSegmento++;

                                        $linhaSegmento .=   $this->completaCampoLayout($resultado->cod_banco, 3, 'N') .
                                        $this->completaCampoLayout($resultado->numero_lote, 4, 'N') .
                                        $this->completaCampoLayout($resultado->tipo_registro_r, 1, 'N') .
                                        $this->completaCampoLayout($resultado->sequencial_detalhe_r, 5, 'N') .
                                        $this->completaCampoLayout($resultado->cod_segmento_r, 1, 'A') .
                                        $this->completaCampoLayout('', 1, 'A') .
                                        $this->completaCampoLayout($resultado->cod_movimento_r, 2, 'N') .
                                        $this->completaCampoLayout($resultado->cod_desconto2, 1, 'N') .
                                        $this->completaCampoLayout($resultado->data_desconto2, 8, 'N') .
                                        $this->completaCampoLayout($resultado->valor_desconto, 15, 'N', 'M') .
                                        $this->completaCampoLayout('', 24, 'A') .
                                        $this->completaCampoLayout($resultado->cod_multa, 1, 'N') .
                                        $this->completaCampoLayout($resultado->data_multa, 8, 'N') .
                                        $this->completaCampoLayout($resultado->valor_multa, 15, 'N', 'MU') .
                                        $this->completaCampoLayout('', 10, 'A') .
                                        $this->completaCampoLayout($resultado->mensagem3, 40, 'A') .
                                        $this->completaCampoLayout($resultado->mensagem4, 40, 'A') .
                                        $this->completaCampoLayout('', 61, 'A') .
                                        PHP_EOL;
                                    }

                                }

                                $dadosTrailerLote = $this->dao->buscaDadosTrailerLoteSantander();
                                foreach($dadosTrailerLote as $resultado) {
                // N�mero de registros + header e trailer do lote
                                    $resultado->quantidade_registros_lote = $sequencialSegmento + 1;

                                    $linhaTrailerLote = $this->completaCampoLayout($resultado->cod_banco, 3, 'N') .
                                    $this->completaCampoLayout($resultado->numero_lote, 4, 'N') .
                                    $this->completaCampoLayout($resultado->tipo_registro, 1, 'N') .
                                    $this->completaCampoLayout('', 9, 'A') .
                                    $this->completaCampoLayout($resultado->quantidade_registros_lote, 6, 'N') .
                                    $this->completaCampoLayout('', 217, 'A') .
                                    PHP_EOL;
                                }

                                $dadosTrailerArquivo = $this->dao->buscaDadosTrailerArquivoSantander();
                                foreach($dadosTrailerArquivo as $resultado) {
                // N�mero de registros + header e trailer de lote e arquivo
                                    $resultado->quantidade_registros_arquivo = $sequencialSegmento + 3;

                                    $linhaTrailerArquivo =  $this->completaCampoLayout($resultado->cod_banco, 3, 'N') .
                                    $this->completaCampoLayout($resultado->numero_lote, 4, 'N') .
                                    $this->completaCampoLayout($resultado->tipo_registro, 1, 'N') .
                                    $this->completaCampoLayout('', 9, 'A') .
                                    $this->completaCampoLayout($resultado->quantidade_lotes, 6, 'N') .
                                    $this->completaCampoLayout($resultado->quantidade_registros_arquivo, 6, 'N') .
                                    $this->completaCampoLayout('', 211, 'A');

                                }

                                $banco = $this->dao->buscaBanco($parametros->ddl_forma_cobranca_arquivo);
                                $banco = $banco[0]->cfbbanco;

                                $content =  $linhaHeaderRemessa . 
                                $linhaHeaderLote .
                                $linhaSegmento .
                                $linhaTrailerLote .
                                $linhaTrailerArquivo;

                                $arquivo->file_path = $caminhoPasta;
                                $arquivo->file_name = "rem_santander_" . $nova_remessa[0] . date('_d_m_Y_His') . ".txt";
                                file_put_contents($arquivo->file_path . $arquivo->file_name, $content);

                                if ($rtcrusuoid == ''){
                                    $rtcrusuoid = $this->usuoid;
                                }
                                $this->dao->atualizaRemessaArquivo(addslashes($arquivo->file_path . $arquivo->file_name), $nova_remessa[1], $tipoEnvio, $rtcrusuoid);

                            } catch(Exception $e) {
                                $this->dao->rollback();

                                if ($tipoEnvio == 'AFT' || $tipoEnvio == 'aft'){
                                   return false;
                               }else{
                                   throw new Exception('Nenhum T�tulo para gera��o encontrado');
                                   return null;
                               }
                           }

                           $this->dao->commit();

        /* 
         * Incluir condi��o no m�todo geraArquivoCSV para que quando o tipoEnvio for AFT,  retornar o nome do arquivo gerado e n�o exibi-lo para download. Se o par�metro tipoEnvio n�o for informado ou for Manual, seguir o fluxo do m�todo at� o fim, como j� est� implementado.
         */
        if ($tipoEnvio == 'AFT' || $tipoEnvio == 'aft') {
            return array('failed' => $arquivo);
        }

        return $arquivo;
    }

    /**
     * Cria arquivo de LOG e dispara o processamento do CRON 
	 * respons�vel pelo envio do arquivo AFT em background
     * @param  [type]				[description]
     * @return bool					[description]
     */ 
    public function prepararEnvioArquivoAFT() {
        try {
			//verifica processo em andamento
			//grava inicio do processo

			//Verifica se a pasta de logs est� criada
         if (!is_dir(_SITEDIR_."processar_cobr_registrada_log")) {
				//Cria a pasta de logs
            if (!mkdir(_SITEDIR_."processar_cobr_registrada_log", 0777)) {
               throw new Exception('Falha ao criar pasta -> processar_cobr_registrada_log.');
           }
       }
			//Concede ao sistema permiss�es de super usuario � pasta de logs
       chmod(_SITEDIR_."processar_cobr_registrada_log", 0777);

			//Cria o arquivo de log
       if (!$handle = fopen(_SITEDIR_."processar_cobr_registrada_log/importacao_cobranca_aft", "w")) {
        throw new Exception('Falha ao criar arquivo de log.');
    }

		//Grava in�cio do processo
        fputs($handle, "Processo Iniciado. \r\n");
        fclose($handle);

		//Concede ao sistema permiss�es de super usuario ao arquivo de logs
        chmod(_SITEDIR_."processar_cobr_registrada_log/importacao_cobranca_aft", 0777); 



        //Inicio - [ORGMKTOTVS-1091] - ERP - Inativar o CRON que envia arquivos de remessa do ERP para o AFT do banco Santander (Boletos)
        if(!INTEGRACAO_TOTVS_ATIVA) {

        //In�cio teste desenv
        $httpHost = $_SERVER['HTTP_HOST'];

			// Windows
            if( $httpHost == "10.20.4.97" || $httpHost =='localhost'){ 

            exec('"C:/xampp/php/php.exe" C:/var/www/html/desenvolvimento/STI-87123/CronProcess/crn_enviar_arquivo_aft.php >> C:/var/www/html/desenvolvimento/STI-87123/processar_cobr_registrada_log/importacao_cobranca_aft 2>&1 &');
            } //Fim teste desenv
		      // Linux
            else {
                passthru("/usr/bin/php " . _SITEDIR_ . "CronProcess/crn_enviar_arquivo_aft.php >> " . _SITEDIR_ . "processar_cobr_registrada_log/importacao_cobranca_aft 2>&1 &");
            }

		//grava o fim do processo

            return true;
        
        } else {

            throw new Exception('Arquivo AFT n�o gerado - INTEGRACAO TOTVS ATIVA.');
            return false;
        }
        //Fim - [ORGMKTOTVS-1091]


    }
        catch(Exception $e) {
         throw new Exception($e->getMessage());
         return false;
        }
    }

    /**
     * Completa o campo do layout para atingir o tamanho necess�rio
     * @param  stdClass $dados [description]
     * @return [type]          [description]
     */
    public function completaCampoLayout($campo, $tamanho, $tipoCampo, $ajustarCasasDecimais = null) {

        /* Parametro $ajustarCasasDecimais:
        null = n�o faz nada
        'M' = Moeda. Ajusta para duas casas decimais
        'P' = Percentual. Ajusta para cinco casas decimais 
        'MU' = Multa. Ajusta para duas casas decimais*/

        // Ajusta casas decimais para percentuais e valores monet�rios
        if($ajustarCasasDecimais == 'M') {
            $posicao = strpos($campo, '.');
            $campoTemp = substr($campo, 0, $posicao) . substr($campo . '0', $posicao, 3);
            $campo = str_replace('.', '', $campoTemp);
        } else if($ajustarCasasDecimais == 'P') {
            $posicao = strpos($campo, '.');
            $campoTemp = substr($campo, 0, $posicao) . substr($campo . '0', $posicao, 6);
            $campo = str_replace('.', '', $campoTemp);
        } else if($ajustarCasasDecimais == 'J') {
            $campo = str_replace('.', '', number_format($campo, 5));
        } else if ($ajustarCasasDecimais == 'MU') {
            $campo = str_replace('.', '', number_format($campo, 2));
        }

        // Remove acentos
        if($ajustarCasasDecimais == null) {
            $campo = preg_replace("/[^a-zA-Z0-9 ]/", "", strtr($campo, "��������������������������", "aaaaeeiooouucAAAAEEIOOOUUC"));
            $campo = strtoupper($campo);
        }

        $i = strlen($campo);

        // Caso j� esteja no tamanho certo, retorna
        if($i > $tamanho) {
            return substr($campo, 0, $tamanho);
        }

        // Preenche o campo com 0 ou ' ', dependendo do tipo, at� atingir o tamanho definido
        while($i < $tamanho) {
            // Alfanumerico/Numerico
            if($tipoCampo == 'A') {
                $campo = $campo . ' ';
                $i++;
            } else if ($tipoCampo == 'N') {
                $campo = '0' . $campo;
                $i++;
            }
        }

        return $campo;
    }

    /**
     * Valida campos da aba remessa
     * @param  stdClass $dados [description]
     * @return [type]          [description]
     */
    private function validarCamposRemessa(stdClass $dados) {

        // Campos obrigat�rios vazios
        if(!isset($dados->dt_ini_remessa) ||
            empty($dados->dt_ini_remessa) ||
            !isset($dados->dt_fim_remessa) ||
            empty($dados->dt_fim_remessa)) {

            throw new Exception(self::MENSAGEM_ALERTA_CAMPOS_OBRIGATORIOS);

    }

    $dataIni  = DateTime::createFromFormat('d/m/Y', $dados->dt_ini_remessa);
    $dataFim  = DateTime::createFromFormat('d/m/Y', $dados->dt_fim_remessa);

    if($dataIni > $dataFim) {
        throw new Exception(self::MENSAGEM_ERRO_DATA_INVALIDA);
    }
}

    /**
     * Valida os campos da aba rekeitado
     * @param  stdClass $dados [description]
     * @return [type]          [description]
     */
    private function validarCamposRejeitado(stdClass $dados) {

        // Campos obrigat�rios vazios
        if(((!isset($dados->txt_num_titulo_rejeitado) || empty($dados->txt_num_titulo_rejeitado)) &&
            (!isset($dados->txt_nome_cliente_rejeitado) || empty($dados->txt_nome_cliente_rejeitado))) &&
            (!isset($dados->dt_ini_rejeitado) ||
                empty($dados->dt_ini_rejeitado) ||
                !isset($dados->dt_fim_rejeitado) ||
                empty($dados->dt_fim_rejeitado))) {

            throw new Exception(self::MENSAGEM_ALERTA_CAMPOS_OBRIGATORIOS);

    }

    $dataIni  = DateTime::createFromFormat('d/m/Y', $dados->dt_ini_rejeitado);
    $dataFim  = DateTime::createFromFormat('d/m/Y', $dados->dt_fim_rejeitado);

    if($dataIni > $dataFim) {
        throw new Exception(self::MENSAGEM_ERRO_DATA_INVALIDA);
    }
}

    /**
     * Valida campos da aba arquivo
     * @param  stdClass $dados [description]
     * @return [type]          [description]
     */
    private function validarCamposArquivo(stdClass $dados) {
        if(!isset($dados->txt_qtde_titulos) ||
            empty($dados->txt_qtde_titulos)) {

            throw new Exception(self::MENSAGEM_ALERTA_CAMPOS_OBRIGATORIOS);

    }

    if((!is_numeric($dados->txt_qtde_titulos)) ||
        (is_numeric($dados->txt_qtde_titulos) && ($dados->txt_qtde_titulos < 1 || $dados->txt_qtde_titulos > 99999))) {

        throw new Exception(self::MENSAGEM_ERRO_QTDE_TITULOS_INVALIDA);

}
}

    /**
     * Trata os parametros do POST/GET. Preenche um objeto com os parametros
     * do POST e/ou GET.
     * 
     * @return stdClass Parametros tratados
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
     * Inicializa parametros
     * 
     * @return void
     */
    private function inicializarParametros() {

        //Verifica se os parametro existem, sen�o iniciliza todos
        if(isset($this->view->parametros->acao) && $this->view->parametros->acao == 'cotacao') {

            $pattern = '/[^0-9]/';
            $replacement = '';

            $this->view->parametros->combustivel = isset($this->view->parametros->combustivel) ? $this->view->parametros->combustivel : "" ;
            $this->view->parametros->uso_veiculo = isset($this->view->parametros->uso_veiculo) ? $this->view->parametros->uso_veiculo : "" ;

            $this->view->parametros->cpf_cnpj = isset($this->view->parametros->cpf_cnpj) ? 
            preg_replace($pattern, $replacement, $this->view->parametros->cpf_cnpj) : "" ;

            $this->view->parametros->cep = isset($this->view->parametros->cep) ? 
            preg_replace($pattern, $replacement, $this->view->parametros->cep) : "" ;
        }

        foreach ($this->view->parametros as $key => $value) {

            $this->view->parametros->$key = trim($value);

        }

    }

}

