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
 * @subpackage Classe core / cadastro de ve�culos
 * @copyright Copyright (c) Sascar Tecnologia Automotiva S/A (http://www.sascar.com.br)
 */

namespace module\Veiculo;

class VeiculoService{
    
    /**
     * Busca dados de um ve�culo
     *
     * @author Jorge A. D. kautzmann <jorge.kautzmann@sascar.com.br>
     * @version 20/09/2013
     * @param mixed $valKey (valor da chave de busca)
     * @param string $tpKey (tipo da chave de busca ID=ID/PL=PLACA/RE=RENAVAN/CH=CHASSI)
     * @return Response $response:
     *     mixed $response->dados (Array com dados do ve�culo=OK /false = falha)
     *     string $response->codigo (c�digo do erro)
     *     string $response->mensagem (mensagem emitida)
    */
    public static function veiculoGetDados($valKey='', $tpKey='ID') {
        $veiculo = new VeiculoController();
        return $veiculo->getDados($valKey, $tpKey);
    }
    
    /**
     * Busca dados do propriet�rio de um ve�culo.
     * Caso veiveipoid n�o for NULO, busca da tabela veiculo_proprietario, sen�o busca na tabela veiculo.
     *
     * @author Jorge A. D. kautzmann <jorge.kautzmann@sascar.com.br>
     * @version 20/09/2013
     * @param int $veioid (ID do ve�culo)
     * @return Response $response:
     *     mixed $response->dados (Array com dados do propriet�rio ve�culo=OK /false = falha)
     *     string $response->codigo (c�digo do erro)
     *     string $response->mensagem (mensagem emitida)
    */
    public static function veiculoProprietarioGetDados($veioid=0) {
        $veiculo = new VeiculoController();
        return $veiculo->getVeiculoProprietario($veioid);
    }

    /**
     * Grava um registro de ve�culo
     *
     * @author Jorge A. D. kautzmann <jorge.kautzmann@sascar.com.br>
     * @version 20/09/2013
     * @param int $usuoid (ID do usuario que deletou o registro)
     * @param array $arrayVeiculo (array associativo com dados do ve�culo)
     *        OBS: campos obrigat�rios:
     *         string veiplaca, 
     *         string veino_renavan, 
     *         string veichassi, 
     *         int veimlooid, 
     *         string veicor, 
     *         int veino_ano 
     *        OBS: campos N�O obrigat�rios:
     *         demais campos tabela ve�culo
     * @return Response $response:
     *     mixed $response->dados ($veioid=OK /false = falha)
     *     string $response->codigo (c�digo do erro)
     *     string $response->mensagem (mensagem emitida)
    */
    public static function veiculoInclui($usuoid=0, $arrayVeiculo=array()) {
        $veiculo = new VeiculoController();
        $arrayVeiculo['veiusuoid'] = (int) $usuoid;
        return $veiculo->veiculoSetDados($arrayVeiculo, 'I');
    }


    /**
     * Exclus�o l�gica de registro de ve�culo
     *
     * @author Jorge A. D. kautzmann <jorge.kautzmann@sascar.com.br>
     * @version 25/09/2013
     * @param int $veioid (ID do ve�culo)
     * @param int $usuoid (ID do usuario que deletou o registro)
     * @return Response $response:
     *     boolean $response->dados (true=OK /false = falha)
     *     string $response->codigo (c�digo do erro)
     *     string $response->mensagem (mensagem emitida)
    */
    public static function veiculoExclui($veioid=0, $usuoid=0) {
        $veiculo = new VeiculoController();
        return $veiculo->veiculoDelete($veioid, $usuoid);
    }

    /**
     * Altera um registro de ve�culo
     *
     * @author Jorge A. D. kautzmann <jorge.kautzmann@sascar.com.br>
     * @version 20/09/2013
     * @param int $veioid (ID do ve�culo)
     * @param array $arrayVeiculo (array associativo com dados do ve�culo)
     *        OBS: campos obrigat�rios:
     *         string veiplaca, 
     *         string veino_renavan, 
     *         string veichassi, 
     *         int veimlooid, 
     *         string veicor, 
     *         int veino_ano 
     *        OBS: campos N�O obrigat�rios:
     *         demais campos tabela ve�culo
     * @return Response $response:
     *     mixed $response->dados ($veioid=OK /false = falha)
     *     string $response->codigo (c�digo do erro)
     *     string $response->mensagem (mensagem emitida)
    */
    public static function veiculoAtualiza($veioid=0, $arrayVeiculo=array()) {
        $veiculo = new VeiculoController();
        $arrayVeiculo['veioid'] = (int) $veioid;
        return $veiculo->veiculoSetDados($arrayVeiculo, 'U');
    }

    /**
     * Grava um registro de propriet�rio em veiculo_proprietario
     * OBS: atualiza tamb�m os dados do propriet�rio na tabela veiculo
     *
     * @author Jorge A. D. kautzmann <jorge.kautzmann@sascar.com.br>
     * @version 20/09/2013
     * @param int $usuoid (ID do usuario que deletou o registro)
     * @param array $arrayProprietario (array associativo com dados do Proprietario)
     *     campos obrigat�rios: 
     *      char(1) veiptipopessoa, 
     *      string veipnome, 
     *      string veipcnpjcpf
     *     campos N�O obrigatorios de endere�o conforme Tabela veiculo_proprietario
     *      string veipcep-> endere�o CEP
     *      char(2) veipuf-> endere�o UF
     *      string veipcidade -> endere�o cidade
     *      string veipbairro -> endere�o bairro
     *      string veiplogradouro -> endere�o logradouro
     *      int veipnumero -> endere�o n�mero
     *      string veipcomplemento -> endere�o complemento
     *      string veipfone -> endere�o fone
     *     
     * @return Response $response:
     *     mixed $response->dados ($veipoid=OK /false = falha)
     *     string $response->codigo (c�digo do erro)
     *     string $response->mensagem (mensagem emitida)
    */
    public static function veiculoProprietarioInclui($usuoid=0, $arrayProprietario=array()) {
        $veiculo = new VeiculoController();
        $arrayProprietario['veipusuoid_cadastro'] = (int) $usuoid;
        return $veiculo->veiculoProprietarioSetDados($arrayProprietario, 'I');
    }

    /**
     * Exclus�o l�gica de um registro de propriet�rio
     *
     * @author Jorge A. D. kautzmann <jorge.kautzmann@sascar.com.br>
     * @version 20/09/2013
     * @param int $veipoid (ID do Proprietario)
     * @param int $usuoid (ID do usu�rio que excluiu o registro)
     * @return Response $response:
     *     boolean $response->dados (true=OK /false = falha)
     *     string $response->codigo (c�digo do erro)
     *     string $response->mensagem (mensagem emitida)
    */
    public static function veiculoProprietarioExclui($veipoid=0, $usuoid=0) {
        $veiculo = new VeiculoController();
        return $veiculo->veiculoProprietarioDelete($veipoid, $usuoid);
    }

    
   /**
     * Atualiza um registro de propriet�rio em veiculo_proprietario
     * OBS: atualiza tamb�m os dados do propriet�rio na tabela veiculo
     *
     * @author Jorge A. D. kautzmann <jorge.kautzmann@sascar.com.br>
     * @version 20/09/2013
     * @param int $veipoid (ID do Proprietario)
     * @param array $arrayProprietario (array associativo com dados do Proprietario)
     *     campos obrigat�rios: 
     *      char(1) veiptipopessoa, 
     *      string veipnome, 
     *      string veipcnpjcpf
     *     campos N�O obrigatorios de endere�o conforme Tabela veiculo_proprietario
     *      string veipcep-> endere�o CEP
     *      char(2) veipuf-> endere�o UF
     *      string veipcidade -> endere�o cidade
     *      string veipbairro -> endere�o bairro
     *      string veiplogradouro -> endere�o logradouro
     *      int veipnumero -> endere�o n�mero
     *      string veipcomplemento -> endere�o complemento
     *      string veipfone -> endere�o fone
     *     
     * @return Response $response:
     *     mixed $response->dados ($veipoid=OK /false = falha)
     *     string $response->codigo (c�digo do erro)
     *     string $response->mensagem (mensagem emitida)
    */
    public static function veiculoProprietarioAtualiza($veipoid=0, $arrayProprietario=array()) {
        $veiculo = new VeiculoController();
        $arrayProprietario['veipoid'] = (int) $veipoid;
        return $veiculo->veiculoProprietarioSetDados($arrayProprietario, 'U');
    }
    
        
}