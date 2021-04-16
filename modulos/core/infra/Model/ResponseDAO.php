<?php
/**
 * Sascar - Sistema Corporativo
 *
 * LICENSE
 *
 * Sascar Tecnologia Automotiva S/A - Todos os Direitos Reservados
 *
 * @version 11/11/2013
 * @since 11/11/2013
 * @package Core
 * @subpackage Superclasse Model de Acesso a Dados (Response)
 * @copyright Copyright (c) Sascar Tecnologia Automotiva S/A (http://www.sascar.com.br)
 */
namespace infra;

use infra\ComumDAO;

class ResponseDAO extends ComumDAO{
	
    public function getMessage($codigo='0') {
    	$vMessage = array(
    	        '0' => 'A��o executada com sucesso.',
    			'CBR001' => 'N�o foi poss�vel registrar o t�tulo',
    			'CBR002' => 'Ocorreu um erro durante o preenchimento do boleto seco',
    			'CBR003' => 'Ocorreu um erro durante a cria��o do boleto em arquivo pdf',
    			'CBR004' => 'Ocorreu um erro durante o cancelamento do t�tulo. Verifique os dados e tente novamente',
    			'CLI001' => 'N�o foi poss�vel inserir os dados do cliente.',
    			'CLI002' => 'N�o foi poss�vel atualizar os dados do cliente.',
    			'CLI003' => 'N�o foi poss�vel inserir os dados de endere�o do cliente.',
    			'CLI004' => 'N�o foi poss�vel atualizar os dados de endere�o do cliente.',
    			'CLI005' => 'N�o foi poss�vel excluir o cliente.',
                'CLI006' => 'Cliente j� existe na base de dados.',
    	        'CTT001' => 'Contrato criado com sucesso.', 
    	        'CTT002' => 'N�o foi poss�vel criar o contrato.',
    	        'CTT003' => 'Dados da proposta n�o localizados.',
    	        'CTT004' => 'Erro ao migrar dados da proposta: fase 1. Contrato N�O foi gerado com sucesso!',
    	        'CTT005' => 'Contrato(s) gerado(s) com sucesso.',
    	        'CTT006' => 'Proposta n�o cont�m itens.',
    	        'CTT007' => 'Erro ao migrar servicos.',
    	        'CTT008' => 'Erro ao migrar Contaos do Cliente.',
    	        'CTT009' => 'Erro ao migrar dados de pagamento.',
    	        'CTT010' => 'Erro ao migrar benef�cios de assist�ncia.',
    	        'CTT011' => 'Erro ao migrar Pacote de benef�cios.',
    	        'CTT012' => 'Erro ao migrar dados de comiss�o.',
    	        'CTT013' => 'Erro ao migrar dados de Zona e Regi�o comercial.',
    	        'CTT014' => 'Erro ao migrar dados de Gerenciadoras.',
    	        'CTT015' => 'Erro ao migrar dados de Vencimento Cliente.',
    	        'CTT016' => 'Erro ao migrar dados de Telemetria.',
    	        'CTT017' => 'Erro ao migrar dados da proposta: fase 2. Contrato N�O foi gerado com sucesso!',
    	        'CTT051' => 'N�o foi encontrada uma proposta vinculada a este contrato.',
    	        'INF001' => 'Chave de busca inv�lida.', 
    	        'INF002' => 'N�o h� registros.', 
    	        'INF003' => 'Est� faltando um ou mais campos obrigat�rios.',
    			'INF004' => 'Opera��o inv�lida', 
    	        'INF005' => 'Verifique os par�metros obrigat�rios.', 
    	        'INF006' => 'Par�metro esperado � inv�lido.',
                'INF007' => 'N�o foi poss�vel recuperar as informa��es: PARAMETROS_CONFIGURACOES_SISTEMAS, entre em contato com o Administrador do sistema.',
                'INF008' => 'N�o foi poss�vel recuperar as informa��es: COD_MOVIMENTO_PERMITE_ATERACAO, entre em contato com o Administrador do sistema.',
    			'INF009' => 'N�o foi poss�vel recuperar as informa��es: FORMAS_COBRANCA_PARA_REGISTRO, entre em contato com o Administrador do sistema.',
    			'PRP001' => 'Proposta criada com sucesso.', 
    	        'PRP002' => 'N�o foi poss�vel criar a proposta.',
    			'PRP003' => 'Proposta Hist�rico gravado com sucesso.', 
    	        'PRP004' => 'N�o foi poss�vel gravar Proposta Hist�rico.',
    			'PRP005' => 'Proposta atualizada com sucesso.', 
    	        'PRP006' => 'N�o foi poss�vel atualizar a proposta.',
    			'PRP007' => 'Proposta existente.', 
    	        'PRP008' => 'Essa proposta n�o existe.',
    			'PRP009' => 'Item da proposta criado com sucesso.', 
    	        'PRP010' => 'N�o foi poss�vel criar o item da proposta.',
    			'PRP011' => 'Item da proposta atualizado com sucesso.', 
    	        'PRP012' => 'N�o foi poss�vel atualizar o item da proposta.',
    			'PRP013' => 'Cliente vinculado a proposta com sucesso.', 
    	        'PRP014' => 'N�o foi poss�vel vincular o cliente a proposta.',
    			'PRP015' => 'Os dados de pagamento for�o vinculados a proposta.', 
    	        'PRP016' => 'N�o foi poss�vel vincular os dados de pagamento a proposta.',
    			'PRP017' => 'Proposta Servi�o exclu�da com sucesso.', 
    	        'PRP018' => 'N�o foi poss�vel excluir a proposta servi�o.',
    			'PRP019' => 'N�o � poss�vel excluir uma proposta servi�o do tipo B�sico.', 
    	        'PRP020' => 'N�o foi poss�vel incluir um acess�rio na proposta.',
    			'PRP021' => 'Acess�rio(s) inclu�do(s) com sucesso.', 
    	        'PRP022' => 'Acess�rio da proposta exclu�do com sucesso.',
    			'PRP023' => 'N�o foi poss�vel excluir o acess�rio da proposta.', 
    	        'PRP024' => 'N�o foi poss�vel incluir as informa��es na Proposta Comiss�o.',
    			'PRP025' => 'N�o � poss�vel incluir uma nova gerenciadora, limite m�ximo atingido', 
    	        'PRP026' => 'Gerenciadora inclu�da com sucesso.',
    			'PRP027' => 'N�o foi poss�vel incluir a gerenciadora.', 
    	        'PRP028' => 'Gerenciadora exclu�da com sucesso.',
    			'PRP029' => 'N�o foi poss�vel excluir a gerenciadora.',	
    	        'PRP030' => 'Contato inclu�do com sucesso.',
    			'PRP031' => 'N�o foi poss�vel incluir o contato.', 
    	        'PRP032' => 'Contato exclu�do com sucesso.',
    			'PRP033' => 'N�o foi poss�vel excluir o contato.', 
    	        'PRP034' => 'Status da proposta alterado com sucesso.',
    			'PRP035' => 'N�o foi poss�vel alterar o status da proposta.', 
    	        'PRP036' => 'N�o foi poss�vel incluir o Opcional na proposta.',
    			'PRP037' => 'Opcional vinculado na proposta com sucesso.', 
    	        'PRP038' => 'Opcional exclu�do com sucesso.',
    			'PRP039' => 'N�o foi poss�vel excluir o Opcional da proposta.',
                'PRP040' => 'Classe/Produto inexistente ou desativada.',
                'PRP041' => 'N�o foi poss�vel vincular o n�mero de s�rie a proposta.',
                'PRP042' => 'Item da proposta exclu�do com sucesso.', 
                'PRP043' => 'N�o foi poss�vel excluir o item da proposta.',
                'PRP044' => 'Itens da proposta exclu�dos com sucesso.', 
                'PRP045' => 'N�o foi poss�vel excluir os itens da proposta.',
    	        'PEP001' => 'Proposta n�o possui cliente.',
    			'PEP002' => 'Proposta n�o possui itens.',
    			'PEP003' => 'Proposta n�o possui classe do equipamento.',
    			'PEP004' => 'Proposta n�o possui forma de pagamento.',
    			'PEP005' => 'Proposta n�o possui informa��es de valores.',
    			'PEP006' => 'O status financeiro da proposta n�o permite gerar contrato.',
    			'VEI001' => 'Ve�culo n�o localizado.', 
    	        'VEI002' => 'N�o foi poss�vel cadastrar o ve�culo.', 
    	        'VEI003' => 'N�o foi poss�vel atualizar o ve�culo',
    			'VEI004' => 'N�o foi poss�vel cadastrar os dados do propriet�rio do ve�culo.', 
    	        'VEI005' => 'N�o foi poss�vel atualizar os dados do propriet�rio do ve�culo.',
    			'VEI006' => 'Propriet�rio do ve�culo exclu�do com sucesso.', 
    	        'VEI007' => 'N�o foi poss�vel excluir o propriet�rio do ve�culo.',
    			'VEI008' => 'Ve�culo exclu�do com sucesso.', 
    	        'VEI009' => 'N�o foi poss�vel excluir o ve�culo.',
    			'ORD010' => 'N�o foi poss�vel cadastrar o item de ordem de servi�o.',
    			
    			'TAX001' => 'N�o foi poss�vel armazenar o t�tulo.',
    			'TAX002' => 'N�o foi poss�vel armazenar o nosso n�mero do t�tulo.',
    			'TAX003' => 'N�o foi poss�vel armazenar o t�tulo na controle de envio.',
    			'TAX004' => 'N�o foi poss�vel gerar o arquivo referente ao t�tulo.',
    			'TAX004' => 'N�o foi poss�vel gerar o arquivo referente ao t�tulo.',
    			'TAX005' => 'N�o foi poss�vel armazenar os dados de pagamento.',
    			'TAX006' => 'N�o foi poss�vel realizar o pagamento do t�tulo por cart�o de cr�dito.',
                'TAX007' => 'Verifique os par�metros obrigat�rios para recuperar os dados banc�rios.',
                'TAX008' => 'O retorno dos dados banc�rios est� vazio, entre em contato com o administrador do sistema.',
    			);
    	
    	return $vMessage[$codigo];
    }
}