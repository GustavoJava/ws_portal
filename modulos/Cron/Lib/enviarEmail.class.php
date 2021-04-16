<?php

/**
 * Classe de Envio de Email do processamento
 *
 * @file    enviarEmail.php
 * @author  BRQ
 * @since   07/08/2012
 * @version 07/08/2012
 * 
 * Exemplo de uso:
 * 
 * $conector = 2;
 * $dataHora = date("m/d/y H:i:s"); 
 * $resumo = "Foram enviados 50 clientes";
 * 
 * $email = new enviarEmail(2); // Conector 2
 * $email->cabecalhoResumo($dataHora, $resumo); // DataHora e o Resumo de envio/recebimento do conector
 * $arrCampos = array(15,  85662139, 'Jo�o Maria');
 * $email->addLinhaDetalhes($arrCampos); 
 * $email->enviar();
 * 
 */
#############################################################################################################
#   Hist�rico
#       10/07/2012 - Diego C. Ribeiro(BRQ)
#           Cria��o do arquivo - DUM 79924
#############################################################################################################

require_once _SITEDIR_ . 'lib/phpMailer/class.phpmailer.php';
require_once 'funcoesEBS.php';

/**
 * Envio de Email do Processamento
 * 
 *  - O e-mail dever� ser enviado para os seguintes endere�os:
 *      osmar.s@sascar.com.br
 *      felipe.beni@sascar.com.br
 * 
 * Ap�s registrar o LOG, dever� ser enviado um e-mail para os endere�os abaixo, 
 * mostrando o resultado da execu��o dos conectores. 
 * Neste email dever� constar o mesmo resultado que ser� armazenado no LOG:
 * 
 * Para todos os conectores:
 *  - Data/Hora da execu��o
 *  - Nome do conector e processo executado 
 *      (Ex; Conector WS1 ? Recebimento de Notas Fiscais de Sa�da)
 *  - Resumo do processamento
 *      Ex: 12 Notas Fiscais recebidas com sucesso na Intranet
 *          03 Notas Fiscais recebidas com erros que n�o puderam ser recebidas na Intranet
 * 
 * Para o Conector WS1:
 *  - Enviar o detalhamento do LOG mostrando quais notas foram recebidas 
 *      (entrada e sa�da), quais apresentaram erros e quais foram os erros 
 *      encontrados, conforme apresentado no prot�tipo 1.
 * 
 * Para o Conector WS2:
 * Neste e-mail, dever� ser apresentado o Numero do Pedido, o CNPJ e o NOME do cliente.
 * 
 * Para conectores WS3, e WS4:
 *  - Dever�o ser enviados quais produtos e clientes/fornecedores foram enviados para o FOX 
 *  - Apresentar c�digo e Nome
 * 
 */
class enviarEmail {

    /**
     * @var int N�mero do Conector (1,2,3 ou 4)
     */
    protected $conector = null;

    /**
     * @var string C�digo HTML com o cabe�alho do Resumo de Envio 
     */
    protected $cabecalhoResumo = null;

    /**
     * @var string C�digo HTML com o corpo do email
     */
    protected $corpoMensagem = null;

    /**
     * @var string Data e hora da execu��o do script
     */
    protected $dataHora = null;

    /**
     * @var string Descri��o do conector na tabela (HTML) de resumo, tamb�m
     * � utilizada como assunto do email que ser� enviado
     */
    protected $msgConector = null;

    /**
     * Conte�do dos v�rios emails a enviar
     * @var type 
     */
    protected $arrEmail = false;
        
    /**
     *
     * @var String que cont�m o email a ser enviado
     */
    public $msgEmail= false;

    /**
     * M�todo Construtor, seta o n�mero do conector
     * 
     * @param int $conector
     * @param datetime $dataHora
     * @param string $resumo 
     */
    public function __construct($conector = false, $emailErro = false, $emailMultiplo = false) {

        $this->conector = $conector;

        switch ($this->conector) {
            case 1:
                $this->msgConector = "Conector WS1";
                break;
            case 2: $this->msgConector = "Conector WS2 Envio de Pedidos de Venda";
                break;
            case 3: $this->msgConector = "Conector WS3 Envio de Clientes e Fornecedores";
                break;
            case 4: $this->msgConector = "Conector WS 4 Envio de Produtos";
                break;
            default:
                break;
        }

        // Limita a estrutura a um �nico cabe�alhoResumo e cabe�alhoDetalhes
        if ($emailMultiplo === false) {

            if ($emailErro === false) {
                // Cria o cabe�alho da Tabela de Detalhes do Envio
                $this->cabecalhoDetalhes();
            } else {
                // Cria o cabe�alho da Tabela de Detalhes do Envio dos Erros
                $this->cabelhoDetalhesEmailErro();
            }

            // Possui v�rias estruturas de tabelas, para v�rios 'envios' aglutinados
            // Todo o processo para gerar o cabe�alho e os detalhes ser� de forma manual
        } else {
            $emailMultiplo = true;
        }
    }

    public function __get($name) {
        return $this->$name;
    }

    public function __set($name, $value) {
        if ($name == 'arrEmail') {
            $this->arrEmail[] = $value;
            
        }else if($name == 'msgConector'){
            $this->msgConector = $value;
        }
    }

    public function cabelhoDetalhesEmailErro() {

        $this->corpoMensagem .= "<table class=\"bordaum\">
                                    <tr>
                                        <td colspan='3' class='detalhes'>Detalhes</td>
                                    </tr>
                                    <tr>
                                        <th>Pedido</th>
                                        <th>CPF/CNPJ</th>
                                    </tr>";
    }

    /**
     * Seta na vari�vel $this->corpoMensagem  o cabe�alho da 'tabela de detalhes'
     * de acordo com o conector selecionado
     */
    public function cabecalhoDetalhes() {

        switch ($this->conector) {
            case 1: $this->corpoMensagem = "<table class=\"bordaum\">
                                        <tr>
                                            <td colspan='3' class='detalhes'>Detalhes</td>
                                        </tr>
                                        <tr>
                                            <th>Nota Fiscal</th>
                                            <th>Serie</th>
                                            <th>Status do Processamento</th>
                                        </tr>";
                break;

            case 2: $this->corpoMensagem = "<table class=\"bordaum\">
                                        <tr>
                                            <td colspan='3' class='detalhes'>Detalhes</td>
                                        </tr>
                                        <tr>
                                            <th width='100' align='center'>N� do Pedido</th>
                                            <th width='150'>CPF/CNPJ</th>
                                            <th>Nome do Cliente</th>
                                        </tr>";
                break;

            case 3: $this->corpoMensagem = "<table class=\"bordaum\">
                                         <tr>
                                            <td colspan='3' class='detalhes'>Detalhes</td>
                                        </tr>
                                        <tr>
                                            <th>Cliente/Fornecedor</th>
                                            <th>Tipo do Cadastro</th>
                                            <th>CpfCnpj</th>
                                        </tr>";
                break;

            case 4: $this->corpoMensagem = "<table class=\"bordaum\">
                                        <tr>
                                            <td colspan='2' class='detalhes'>Detalhes</td>
                                        </tr>
                                        <tr>
                                            <th>C�digo Produto</th>
                                            <th>Descri��o</th>
                                        </tr>";
                break;
        }
    }

    /**
     * Seta o cabe�alho da Tabela de resumo do Processamento de cada Conector
     * @param type $dataHora
     * @param type $resumo 
     */
    public function cabecalhoResumo($dataHora, $resumo, $tipoNota = "") {

        $this->cabecalhoResumo = "
                            <style type=\"text/css\">
                                .bordaum {background:#FFFFFF; width:700px; border:1px solid #000000}
                                .bordaum th {background:#A2B5CD; font-size:12px; font-family:Arial}
                                .bordaum td {background:#FFFFFF border:3px solid #000000; font-size:12px; font-family:Arial}
                                .detalhes {background:#C6E2FF; text-align:center; font-size:12px; font-family:Arial}
                            </style>
                
                            <table class=\"bordaum\">
                              <tr>
                                <th>Conector</th>
                                <th>Data/hora execu��o</th>";

        switch ($tipoNota) {
            case 'entrada': $this->cabecalhoResumo .= "<th>Recebimento de Notas Entrada</th>";
                break;
            case 'saida': $this->cabecalhoResumo .= "<th>Recebimento de Notas Sa�da</th>";
                break;
            case 'cancelamento_entrada': $this->cabecalhoResumo .= "<th>Recebimento de Cancelamento de Notas de Entrada </th>";
                break;
            case 'cancelamento_saida': $this->cabecalhoResumo .= "<th>Recebimento de Cancelamento de Notas de Sa�da</th>";
                break;
            default: $this->cabecalhoResumo .= "<th>Resumo Processamento</th>";
                break;
        }

        $this->cabecalhoResumo .= "
                                
                              </tr>
                              <tr>
                                <td>$this->msgConector</td>
                                <td>" . data_e_hora_to($dataHora, false) . "</td>
                                <td>$resumo</td>
                              </tr>
                            </table>";
    }

    /**
     * O protocolo do conector WS2 ? Pedido de Venda de numero XXXX, 
     *  gerado no dia XX/XX/XXXX �s HH:MM:SS teve 
     *  erro ao ser processado pelo canal de Integra��o EBS.  
     */
    public function cabecalhoResumoEmailErro($protocolo, $dataHora, $resumo) {

        $this->cabecalhoResumo .= "
                            <style type=\"text/css\">
                                .bordaum {background:#FFFFFF; width:700px; border:1px solid #000000}
                                .bordaum th {background:#A2B5CD; font-size:12px; font-family:Arial}
                                .bordaum td {background:#FFFFFF border:3px solid #000000; font-size:12px; font-family:Arial}
                                .detalhes {background:#C6E2FF; text-align:center; font-size:12px; font-family:Arial}
                            </style>
                            <br>
                            <table class=\"bordaum\">
                              <tr>
                                <th>Protocolo</th>
                                <th>Data/hora execu��o</th>
                                <th>Erros</th>
                              </tr>
                              <tr>
                                <td>$protocolo</td>
                                <td>$dataHora</td>
                                <td>$resumo</td>
                              </tr>
                            </table><br>";
    }

    /*
     * Adiciona uma linha com os dados de Envio Detalhado de cada conector
     */

    public function addLinhaDetalhes($arrCampos) {

        if ($this->conector == 1 and count($arrCampos) != 3) {
            return "Aten��o! O conector WS1 exige 3 campos para a tabela de detalhes, verifique.";
        } elseif ($this->conector == 2 and count($arrCampos) != 3) {
            return "Aten��o! O conector WS2 exige 3 campos para a tabela de detalhes, verifique.";
        } elseif ($this->conector == 3 and count($arrCampos) != 3) {
            return "Aten��o! O conector WS3 exige 3 campos para a tabela de detalhes, verifique.";
        } elseif ($this->conector == 4 and count($arrCampos) != 2) {
            return "Aten��o! O conector WS4 exige 3 campos para a tabela de detalhes, verifique.";
        }

        $this->corpoMensagem .= "\n<tr>";
        foreach ($arrCampos as $value) {
            $this->corpoMensagem .= "<td>$value</td>";
        }
        $this->corpoMensagem .= "</tr>";
    }

    public function addLinhaDetalhesErro($arrCampos) {

        $this->corpoMensagem .= "<tr>";
        foreach ($arrCampos as $value) {
            $this->corpoMensagem .= "<td>$value</td>";
        }
        $this->corpoMensagem .= "</tr>";
    }

    public function fechaTabela() {
        $this->corpoMensagem .= "</table><br>";
    }

    /**
     * Envia os dados do envio do email
     * 
     */
    public function enviar() {
        
        $mensagem = "";
        
        // Envia o email de acordo com o conte�do da variavel msgEmail
        if(isset($this->msgEmail) and $this->msgEmail != ''){
            $mensagem =  $this->msgEmail; 
                     
        // Array de mensagens para enviar num �nico email
        }else if ($this->arrEmail != false) {
            if (is_array($this->arrEmail) and count($this->arrEmail) > 0) {
                foreach ($this->arrEmail as $value) {
                    $mensagem .= '<br>' . $value;
                }
            } else {
                $mensagem = "";
            }
        
        // Se o cabe�alho e corpo da mensagem foram inicializadas    
        }else if(isset($this->cabecalhoResumo) and isset($this->corpoMensagem)) {
            $mensagem = $this->cabecalhoResumo . $this->corpoMensagem;
            
        // N�o h� mensagem para enviar
        }else{
            echo 'N�o h� mensagens para enviar';
        }
     
        if ($mensagem != "") {
            
            echo '<p>Corpo do email a enviar para simples confer�ncia</p>';
            echo $mensagem;

            // Em homologa��o, comentar envio de e-mail
            $mail = new PHPMailer();
            $mail->IsSMTP();
            $mail->From = "sascar@sascar.com.br";
            $mail->FromName = "Sascar";
            $mail->Subject = $this->msgConector;
            $mail->MsgHTML($mensagem);
            $mail->ClearAllRecipients();

            /**/
            // Faz o envio
            if ($mail->Send()) {
                return "/n Email - OK /n";
            } else {
                return "/n Email - Erro /n";
            }
            /**/

            // Limpa os destinat�rios e os anexos
            $mail->ClearAllRecipients();
            $mail->ClearAttachments();
        }
    }

}