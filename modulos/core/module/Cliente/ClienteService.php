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
 * @subpackage Classe Core de Cliente
 * @copyright Copyright (c) Sascar Tecnologia Automotiva S/A (http://www.sascar.com.br)
 */

namespace module\Cliente;
use module\Cliente\ClienteController as Controlador,
    infra\Helper\Mascara;

class ClienteService{
    
    /**
     * M�todo de busca de dados de cliente
     *
     * @author Jorge A. D. kautzmann <jorge.kautzmann@sascar.com.br>
     * @version 29/08/2013
     * @param mixed $valKey (valor da chave de busca)
     * @param string $tpKey (tipo da chave de busca ID/DOC)
     * @return Response $response:
     *     mixed $response->dados (Array com dados do cliente=OK /false = falha)
     *     string $response->codigo (c�digo do erro)
     *     string $response->mensagem (mensagem emitida)
    */
    public static function clienteGetDados($valKey='', $tpKey='ID') {
        $cliente = new Controlador();
        return $cliente->getDados($valKey, $tpKey);
    }
    
    /**
     * M�todo para inserir um registro de cliente
     *
     * @author Jorge A. D. kautzmann <jorge.kautzmann@sascar.com.br>
     * @version 29/08/2013
     * @param int $usuoid (ID do usu�rio que incluiu o registro)
     * @param array $arrayCliente (array associativo de dados tabela clientes)
     *     OBS: campos obrigat�rios de $arrayCliente
     *         char(1) $arrayCliente['clitipo'] = 'F' / 'J';
     *         string $arrayCliente['clinome'] = 'Nome do Cliente';
     *         string $arrayCliente['cliemail'] = 'E-mail do Cliente';
     *         SE ('J'):
     *           string $arrayCliente['clino_cgc'] = 'CNPJ do cliente';
     *           char(1) $arrayCliente['clireg_simples'] = 'Optante pelo regime simples S/N';
     *           
     *         SE ('F'):
     *           string $arrayCliente['clino_cpf'] = 'CPF do cliente';
     *           char(1) $arrayCliente['clisexo'] = 'Sexo cliente';
     *           string $arrayCliente['cliestado_civil'] = 'Esta do civil do cliente';
     *           date $arrayCliente['clidt_nascimento'] = 'Data de nascimento aaaa-mm-dd';
     *           string $arrayCliente['clino_rg'] = 'RG do cliente';
     *           string $arrayCliente['cliemissor_rg'] = 'Org�o Emissor do RG';
     *           date $arrayCliente['clidt_emissao_rg'] = 'Data de emiss�o do RG  aaaa-mm-dd';
     *           string $arrayCliente['cliemail'] = 'E-mail do cliente';
     *           string $arrayCliente['clinaturalidade'] = 'Naturalidade do cliente';
     *           string $arrayCliente['climae'] = 'Nome da M�e do cliente';
     *           
     *     OBS: campos N�O obrigat�rios de $arrayCliente
     *         demais campos da tabela clientes
     *           
     * @return Response $response:
     *     mixed $response->dados ($clioid=OK /false = falha)
     *     string $response->codigo (c�digo do erro)
     *     string $response->mensagem (mensagem emitida)
    */
    public static function clienteInclui($usuoid=0, $arrayCliente=array()) {
        $cliente = new Controlador();
        $arrayCliente['cliusuoid'] = Mascara::inteiro($usuoid);
        return $cliente->setDados($arrayCliente, 'I');
    }
    
    
    /**
     * M�todo para atualizar um registro de cliente
     *
     * @author Jorge A. D. kautzmann <jorge.kautzmann@sascar.com.br>
     * @version 29/08/2013
     * @param int $clioid (ID do cliente a ser alterado)
     * @param int $usuoid (ID do usuario que procedeu a atualizou o registro)
     * @param array $arrayCliente (array associativo de Dados)
     *     OBS: campos -> dados conforme necessidade proposta tabela clientes
     * @return Response $response:
     *     mixed $response->dados ($clioid=OK /false = falha)
     *     string $response->codigo (c�digo do erro)
     *     string $response->mensagem (mensagem emitida)
    */
    public static function clienteAtualiza($clioid=0, $usuoid=0, $arrayCliente=array()) {
        $cliente = new Controlador();
        $arrayCliente['clioid'] = Mascara::inteiro($clioid);
        if ($usuoid>0) {
        	$arrayCliente['cliusuoid_alteracao'] = Mascara::inteiro($usuoid);
        }
        return $cliente->setDados($arrayCliente, 'U');
    }

    /**
     * M�todo para excluir um registro de cliente
     *
     * @author Jorge A. D. kautzmann <jorge.kautzmann@sascar.com.br>
     * @version 29/08/2013
     * @param int $clioid (ID do cliente a ser excluido)
     * @param int $usuoid (ID do usuario que excluiu)
     * @return Response $response:
     *     boolean $response->dados (true=OK /false = falha)
     *     string $response->codigo (c�digo do erro)
     *     string $response->mensagem (mensagem emitida)
    */
    public static function clienteExclui($clioid=0, $usuoid=0) {
        $cliente = new Controlador();
        return $cliente->clienteExclui($clioid, $usuoid);
    }
        
    /**
     * Busca a lista de endere�os do Cliente (principal, cobran�a e instala��o)
     *
     * @author Jorge A. D. kautzmann <jorge.kautzmann@sascar.com.br>
     * @version 11/09/2013 
     * @param int $clioid
     * @return Response $response:
     *     mixed $response->dados (array associativo com o(s) endere�o(s) conforme a tabela endereco=OK /false = falha)
     *     string $response->codigo (c�digo do erro)
     *     string $response->mensagem (mensagem emitida)
    */
    public static function clienteGetEnderecoList($clioid=0) {
    	$cliente = new Controlador();
    	return $cliente->getEnderecos($clioid);
    }
    
    /**
     *  Busca um endere�o do Cliente pelo clioid e tipo
     *
     * @author Jorge A. D. kautzmann <jorge.kautzmann@sascar.com.br>
     * @version 11/09/2013
     * @param int $clioid
     * @param string $tipo ('P/C/I')
     * @return Response $response:
     *     mixed $response->dados (array com dados de um endere�o=OK /false = falha)
     *     string $response->codigo (c�digo do erro)
     *     string $response->mensagem (mensagem emitida)
    */
    public static function clienteGetEnderecoById($clioid=0, $tipo='P') {
        $cliente = new Controlador();
        return $cliente->getEndereco($clioid, $tipo);
    }
    
    /**
     *  M�todo para inserir um registro de endere�o do Cliente
     *
     * @author Jorge A. D. kautzmann <jorge.kautzmann@sascar.com.br>
     * @version 11/09/2013
     * @param int $clioid (ID do cliente)
     * @param int $usuoid (ID do usuario que esta realizando a inclusao)
     * @param array $arrayEndereco (Array associativo com dados conforme tabela endereco)
     *     OBS: campos obrigat�rios: 
     *      string endno_cep, 
     *      char(2) enduf, 
     *      string endcidade, 
     *      string endbairro,  
     *      string endlogradouro, 
     *      int endno_numero
     *      int endddd
     *      string endfone
     *     OBS: campos N�O obrigat�rios: 
     *      Demais campos da tabela endere�o 
     * @param string $tipo (Tipo do endere�o: P = Principal; C = Cobran�a; I = Instala��o)
     * @return Response $response:
     *     mixed $response->dados ($endoid=OK /false = falha)
     *     string $response->codigo (c�digo do erro)
     *     string $response->mensagem (mensagem emitida)
    */
    public static function clienteEnderecoInclui($clioid=0, $usuoid=0, $arrayEndereco=array(), $tipo='P') {
        $cliente = new Controlador();
        return $cliente->clienteEnderecoInclui($clioid, $usuoid, $arrayEndereco, $tipo);
    }
    
    /**
     *  M�todo para atualizar um registro de endere�o do Cliente
     *
     * @author Jorge A. D. kautzmann <jorge.kautzmann@sascar.com.br>
     * @version 11/09/2013
     * @param int $clioid (ID do cliente)
     * @param int $usuoid (ID do usuario que esta realizando a alteracao)
     * @param int $endoid (ID do endere�o)
     * @param array $arrayEndereco (Array associativo com dados conforme tabela endereco)
     *     OBS: Obrigatoriedade conforme regra de inclus�o
     * @param string $tipo (Tipo do endere�o: P = Principal; C = Cobran�a; I = Instala��o)
     * @return Response $response:
     *     mixed $response->dados ($endoid=OK /false = falha)
     *     string $response->codigo (c�digo do erro)
     *     string $response->mensagem (mensagem emitida)
    */
    public static function clienteEnderecoAtualiza($clioid=0, $usuoid=0, $endoid=0, $arrayEndereco=array(), $tipo='P') {
        $cliente = new Controlador();
        return $cliente->clienteEnderecoAtualiza($clioid, $usuoid, $endoid, $arrayEndereco, $tipo);
    }
    
    /**
     *  Inclui uma nova forma de cobran�a ao cliente, excluindo as anteriores
     *
     * @author Rafael Dias <rafael.dias@meta.com.br>
     * @version 31/01/2014
     * @param int $clioid (ID do cliente)
     * @param int $usuoid (ID do usuario que esta realizando a alteracao)
     * @param array $arrayFormaCobranca (Array associativo com dados conforme tabela cliente_cobranca)
     * @return Response $response: boolean
     */
    public static function clienteFormaCobrancaInclui($clioid=0, $usuoid=0, $arrayFormaCobranca=array()) {
    	$cliente = new Controlador();
    	return $cliente->clienteFormaCobrancaInclui($clioid, $usuoid, $arrayFormaCobranca);
    }   
    
    /**
     * M�todo est�tico para verificar se um cliente existe
     *
     * @author Jorge A. D. kautzmann <jorge.kautzmann@sascar.com.br>
     * @version 12/09/2013 
     * @param mixed $valKey, valor da chave de busca
     * @param string $tpKey, ('ID'/'CPF'/'CNPJ') tipo da chave de busca
     * @return Response $response:
     *     boolean $response->dados (true=OK /false = falha)
     *     string $response->codigo (c�digo do erro)
     *     string $response->mensagem (mensagem emitida)
    */
    public static function clienteExiste($valKey='', $tpKey='ID'){
    	$cliente = new Controlador();
    	return $cliente->clienteExiste($valKey, $tpKey);
    }    
    
}