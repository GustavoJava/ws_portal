<?php
/**
 * Sascar - Sistema Corporativo
 *
 * LICENSE
 *
 * Sascar Tecnologia Automotiva S/A - Todos os Direitos Reservados
 *
 * @author Jorge A. D. kautzmann <jorge.kautzmann@sascar.com.br>
 * @version 29/08/2013
 * @since 29/08/2013
 * @package Core
 * @subpackage Classe Core de Contrato
 * @copyright Copyright (c) Sascar Tecnologia Automotiva S/A (http://www.sascar.com.br)
 */
namespace module\GestorCredito;

use module\GestorCredito\GestorCreditoController as Contralador;

class GestorCreditoService{
    
    /**
     * Realiza consulta (parametrizada) em uma �nica chamada.
     * 
     * @author Jorge A. D. kautzmann <jorge.kautzmann@sascar.com.br>
     * @version 24/10/2013
     * @param string $cpf_cnpj (CPF ou CNPJ do cliente)
     * @param array associativo $dadosAnalise
     *    OBS-> campos obrigat�rios do $dadosAnalise[]: 
     *      int formaPagamento -> forma de pagamento (forcoid)
     *      char(1) tipoPessoa -> tipo pessoa ('F'/'J')
     *      int tipoProposta -> Tipo de Proposta (tppoid)
     *      int tipoContrato -> Tipo de Contrato (tpcoid)
     *      int qtdEquipamentos -> Quantidade de equipamentos
     *      float valorTotalCompra -> valor total da compra/proposta
     *    OBS-> campos N�O obrigat�rios do $dadosAnalise[]: 
     *      int subTipoProposta ->  Subtipo de Proposta (tppoid_sub)
     * @param char(1) $opAmbSerasa (op��o de ambiente no serasa: 'H'= homologacao, 'P'= produ��o)
     * @return Response $response:
     *     mixed $response->dados (array dados da consulta/cr�dito=OK /false = falha)
     *     string $response->codigo (c�digo do erro)
     *     string $response->mensagem (mensagem emitida)
    */
    public static function analisaCredito($cpf_cnpj='', $dadosAnalise=array(), $opAmbSerasa='H'){
        $gestorCredito = new Contralador();
        return $gestorCredito->analisaCredito($cpf_cnpj, $dadosAnalise, $opAmbSerasa);
    }
    
    /**
     * Realiza consulta no SERASA.
     * 
     * @author Jorge A. D. kautzmann <jorge.kautzmann@sascar.com.br>
     * @version 24/10/2013
     * @param string $cpf_cnpj (CPF ou CNPJ do cliente)
     * @param array associativo $dadosAnalise
     *    OBS-> campos obrigat�rios do $dadosAnalise[]: 
     *      int formaPagamento -> forma de pagamento (forcoid)
     *      char(1) tipoPessoa -> tipo pessoa ('F'/'J')
     *      int tipoProposta -> Tipo de Proposta (tppoid)
     *      int tipoContrato -> Tipo de Contrato (tpcoid)
     *      int qtdEquipamentos -> Quantidade de equipamentos
     *      float valorTotalCompra -> valor total da compra/proposta
     *    OBS-> campos N�O obrigat�rios do $dadosAnalise[]: 
     *      N/A
     * @param char(1) $opAmbSerasa (op��o de ambiente no serasa: 'H'=homologacao, 'P'=produ��o)
     * @return Response $response:
     *     mixed $response->dados (array dados da consulta/cr�dito=OK /false = falha)
     *     string $response->codigo (c�digo do erro)
     *     string $response->mensagem (mensagem emitida)
    */
    public static function serasaAnalisaCredito($cpf_cnpj='', $dadosAnalise=array(), $opAmbSerasa='H'){
        $gestorCredito = new Contralador();
        return $gestorCredito->serasaAnalisaCredito($cpf_cnpj, $dadosAnalise, $opAmbSerasa);
    }
    
    /**
     * Verifica se o cliente pagador possui titulos em atraso h� mais de 15 dias na SASCAR.
     * 
     * @author Bruno B. Affonso [bruno.bonfim@sascar.com.br]
     * @version 18/10/2013
     * @param int $clioid
     * @return Response $response:
     *     mixed $response->dados (true=OK(CR�DITO APROVADO) /false = falha)
     *     string $response->codigo (c�digo do erro)
     *     string $response->mensagem (mensagem emitida)
    */
    public static function sascarAnalisaCredito($clioid=0){
        $gestorCredito = new Contralador();
        return $gestorCredito->sascarAnalisaCredito($clioid);
    }
    
    /**
     * Retorna n�mero de dias ou a m�dia de atraso do cliente conforme valor de $mDias
     *
     * @author Bruno B. Affonso [bruno.bonfim@sascar.com.br]
     * @version 23/10/2013
     * @param int $clioid (ID do Cliente)
     * @param boolean $mDias (Passe TRUE para retornar a media de atraso em dias)
     * @return Response $response:
     *     mixed $response->dados (n�mero ou m�dia de dias=OK /false = falha)
     *     string $response->codigo (c�digo do erro)
     *     string $response->mensagem (mensagem emitida)
    */
    public static function clienteMediaAtraso($clioid=0, $mDias=false){
        $gestorCredito = new Contralador();
        return $gestorCredito->clienteMediaAtraso($clioid, $mDias);
    }

    /**
     * M�todo retorna qual o cliente pagador da proposta/contrato
     *
     * @author Jorge A. D. kautzmann <jorge.kautzmann@sascar.com.br>
     * @version 24/10/2013
     * @param int $prptpcoid (ID do Tipo do Contrato)
     * @return Response $response:
     *     mixed $response->dados ($clioid=OK /false = falha)
     *     string $response->codigo (c�digo do erro)
     *     string $response->mensagem (mensagem emitida)
    */
    public static function clienteVerificaPagador($prptpcoid=0){
        $gestorCredito = new Contralador();
        return $gestorCredito->clienteVerificaPagador($prptpcoid);
    }
    /**
     * M�todo retorna o valor m�dio dos t�tulos do cliente
     *
     * @author Jorge A. D. kautzmann <jorge.kautzmann@sascar.com.br>
     * @version 24/10/2013
     * @param int $clioid (ID do cliente)
     * @return Response $response:
     *     mixed $response->dados (m�dia=OK /false = falha)
     *     string $response->codigo (c�digo do erro)
     *     string $response->mensagem (mensagem emitida)
    */
    public static function clienteValorMedioTitulosAtivos($clioid=0){
        $gestorCredito = new Contralador();
        return $gestorCredito->clienteValorMedioTitulosAtivos($clioid);
    }
    
    /**
     * M�todo retorna o N�MERO total de t�tulos ativos do cliente
     *
     * @author Jorge A. D. kautzmann <jorge.kautzmann@sascar.com.br>
     * @version 24/10/2013
     * @param int $clioid (ID do cliente)
     * @return Response $response:
     *     mixed $response->dados (N�mero de t�tulos=OK /false = falha)
     *     string $response->codigo (c�digo do erro)
     *     string $response->mensagem (mensagem emitida)
    */
    public static function clienteNumeroTotalTitulosAtivos($clioid=0){
        $gestorCredito = new Contralador();
        return $gestorCredito->clienteNumeroTotalTitulosAtivos($clioid);
    }
    
    /**
     * M�todo retorna o VALOR total de t�tulos ativos do cliente
     *
     * @author Jorge A. D. kautzmann <jorge.kautzmann@sascar.com.br>
     * @version 24/10/2013
     * @param int $clioid (ID do cliente)
     * @return float valor total de t�tulos
     * @return Response $response:
     *     mixed $response->dados (valor total t�tulos=OK /false = falha)
     *     string $response->codigo (c�digo do erro)
     *     string $response->mensagem (mensagem emitida)
    */
    public static function clienteValorTotalTitulosAtivos($clioid=0){
        $gestorCredito = new Contralador();
        return $gestorCredito->clienteValorTotalTitulosAtivos($clioid);
    }
    
    
}