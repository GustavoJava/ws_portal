<?php
/**
 * @author	Emanuel Pires Ferreira
 * @email	epferreira@brq.com
 * @since	15/02/2013
 * @STI     80398
**/


require_once (_MODULEDIR_ . 'Cadastro/DAO/CadRestricoesVinculacaoAcessorioDAO.class.php');
//require_once ('modulos/Cadastro/DAO/CadRestricoesVinculacaoAcessorioDAO.class.php');

/**
 * Cadastro de Restri��es de Acess�rios por Projeto
 * Interface onde o usu�rio poder� definir as restri��es 
 * de acess�rios por projeto, e que ser� validado na vincula��o de 
 * Equipamentos e Acess�rios na Intranet e no Portal de Servi�os. 
 */
class CadRestricoesVinculacaoAcessorio {

	/**
	 * Fornece acesso aos dados necessarios para o m�dulo
	 * @property restricoesVinculacaoAcessorioDAO
	 */
	private $restricoesVinculacaoAcessorioDAO;
    
	/**
	 * Construtor, configura acesso a dados e par�metros iniciais do m�dulo
	 */
    public function __construct() 
    {
		global $conn;
        
        $this->restricoesVinculacaoAcessorioDAO = new CadRestricoesVinculacaoAcessorioDAO($conn);
    }
    
    /**
     * 
     */
    public function buscaEquipamentosProjeto()
    {
        return $this->restricoesVinculacaoAcessorioDAO->buscaEquipamentosProjeto();
    }
    
    /**
     * 
     */
    public function buscaEquipamentosClasse()
    {
        return $this->restricoesVinculacaoAcessorioDAO->buscaEquipamentosClasse();
    }
    
    /**
     * 
     */
    public function buscaEquipamentosVersao($encode)
    {
        return $this->restricoesVinculacaoAcessorioDAO->buscaEquipamentosVersao($encode);
    }

    /**
     * 
     */
    public function buscaEquipamentosTipoContrato()
    {
        return $this->restricoesVinculacaoAcessorioDAO->buscaEquipamentosTipoContrato();
    }
    
    /**
     * Action de pesquisa das restri��es
     */
    public function pesquisar()
    {
        return $this->restricoesVinculacaoAcessorioDAO->pesquisar();
    }
    
    /**
     * 
     */
    public function novo() 
    {
        
    }
    
    /**
     * 
     */
    public function buscaProdutos()
    {
        return $this->restricoesVinculacaoAcessorioDAO->buscaProdutos();
    }
    
    /**
     * 
     */
    public function atualizaProduto()
    {
        return $this->restricoesVinculacaoAcessorioDAO->atualizaProduto();
    }
    
    /**
     * 
     */
    public function verificaIntegridade()
    {
        return $this->restricoesVinculacaoAcessorioDAO->verificaIntegridade();
    }
    
    /**
     * 
     */
    public function salvar()
    {
        return $this->restricoesVinculacaoAcessorioDAO->salvar();
    }
    
    /**
     * 
     */
    public function excluiRestricao()
    {
        return $this->restricoesVinculacaoAcessorioDAO->excluiRestricao();
    }
    
    /**
     * Metodo que retorna o nome do produto pelo ID informado
     * 
     * @see     WS_Portal/submeterDadosAcessorioRp.php
     * @see     WS_Portal/submeterDadosEquipamentoRp.php
     * 
     * @STI     80398   WS52 / WS48
     */
    public function retornaNomeProduto($prdoid) 
    {
        return $this->restricoesVinculacaoAcessorioDAO->retornaNomeProduto($prdoid);
    }
    /**
     * Metodo que retorna os dados necess�rios para realizar 
     * valida��o das restri��es de acess�rios
     * 
     * STI 80398 - WS52
     */
    public function retornaDadosValidacaoRestricoes($consoid)
    {
        return $this->restricoesVinculacaoAcessorioDAO->retornaDadosValidacaoRestricoes($consoid);
    } 
    
    /**
     * Metodo que verifica restri��es por projeto 
     * e vers�o do acess�rio a ser vinculado
     *
     * @see     WS_Portal/submeterDadosAcessorioRp.php
     * @see     WS_Portal/submeterDadosEquipamentoRp.php
     *  
     * @STI     80398   WS52 / WS48
     * @param   int     $eproid - Id do projeto a ser validado
     * @param   int     $eveoid - Id da vers�o a ser validada
     * @param   int     $prdoid - Id do acess�rio vinculado
     */
    public function verificaRestricaoProjetoVersao($eproid, $eveoid, $prdoid)
    {
        return $this->restricoesVinculacaoAcessorioDAO->verificaRestricaoProjetoVersao($eproid, $eveoid, $prdoid);
    }
    
    /**
     * Metodo que verifica restri��es por 
     * projeto do acess�rio a ser vinculado
     *
     * @see     WS_Portal/submeterDadosAcessorioRp.php
     * @see     WS_Portal/submeterDadosEquipamentoRp.php
     *  
     * @STI     80398   WS52 / WS 48
     * @param   int     $eproid - Id do projeto a ser validado
     * @param   int     $prdoid - Id do acess�rio vinculado
     */
    public function verificaRestricaoProjeto($eproid, $prdoid)
    {
        return $this->restricoesVinculacaoAcessorioDAO->verificaRestricaoProjeto($eproid, $prdoid);
    }

    /**
     * Metodo que verifica restri��es por 
     * tipo de contrato a ser vinculado
     *
     * @STI     83867
     * @param   int     $connumero - Numero do contrato
     * @param   int     $equoid - Id do equipamento
     */
    public function verificaRestricaoEquipamentoTipoContrato($connumero, $equoid)
    {
        return $this->restricoesVinculacaoAcessorioDAO->verificaRestricaoEquipamentoTipoContrato($connumero, $equoid);
    }

    /**
     * Metodo que verifica restri��es por 
     * tipo de contrato a ser vinculado
     *
     * @STI     83867
     * @param   int     $tpcoid - Id do tipo de contrato
     * @param   int     $prdoid - Id do acess�rio vinculado
     */
    public function verificaRestricaoAcessorioTipoContrato($tpcoid, $prdoid)
    {
        return $this->restricoesVinculacaoAcessorioDAO->verificaRestricaoAcessorioTipoContrato($tpcoid, $prdoid);
    }

    /**
     * Metodo que verifica restri��es por 
     * classe do acess�rio a ser vinculado
     * 
     * @see     WS_Portal/submeterDadosAcessorioRp.php
     * 
     * @STI     80398   WS52
     * @param   int     $eqcoid - Id da classe a ser validada
     * @param   int     $prdoid - Id do acess�rio vinculado
     */
    public function verificaRestricaoClasse($eqcoid, $prdoid)
    {
        return $this->restricoesVinculacaoAcessorioDAO->verificaRestricaoClasse($eqcoid, $prdoid);
    }
    
    /**
     * Metodo que retorna os dados necess�rios para realizar 
     * valida��o das restri��es de equipamento
     * 
     * @see     WS_Portal/submeterDadosEquipamentoRp.php
     * 
     * @STI     80398   WS48
     * @param   int     $ordconnumero - N�mero do Contrato
     */
    public function retornaDadosValidacaoRestricoesEquipamento($ordconnumero)
    {
        return $this->restricoesVinculacaoAcessorioDAO->retornaDadosValidacaoRestricoesEquipamento($ordconnumero);
    }
    
    
    
    public function validaEquipamento($connumero, $equoid)
    {
        
        $retorno = "";
        
        /**
         * RETORNA INFORMA��ES PARA VALIDA��O DE RESTRI��ES
         */
        $arrDadosRestricao = $this->retornaDadosValidacaoRestricoesEquipamento($connumero);

        //Caso tenha registros
        if(is_array($arrDadosRestricao)) {
            
            $arrPrdOid = array();
            
            foreach($arrDadosRestricao as $dados) {
                
                if($dados['oftctabela'] == '' ) {
                        
                    $dados['oftctabela']            = 'imobilizado';
                    $dados['oftcprefixo']           = 'imob';
                    $dados['oftcprefixo_status']    = 'ims';
                    $dados['oftcprefixo_historico'] = 'imobh';
                    $dados['oftcimotoid']           = 24;    
                }

                // inclui produto vinculado na obrigacao financeira na validacao de restricoes
                if(isset($dados['obrprdoid']) && $dados['obrprdoid'] > 0 && $dados['consrefioid'] == ''){
                    $arrPrdOid[] = $dados['obrprdoid'];
                }
                
               
                $cpserial = 'oid';                 
                if($dados['consrefioid'] > 0){
                    if($dados['oftctabela'] != 'imobilizado'){

                        $ret = $this->restricoesVinculacaoAcessorioDAO->retornaPrdOidNaoImobilizado($dados['oftcprefixo'], $dados['oftcprefixo_status'], $dados['oftctabela'], $cpserial, $dados['consrefioid']);

                        if(strripos($ret, '-') === false)
                            $arrPrdOid[] = $ret;

                    }else{

                        $ret = $this->restricoesVinculacaoAcessorioDAO->retornaPrdOidImobilizado($dados['oftcimotoid'], $dados['consrefioid'], $cpserial);

                        if(strripos($ret, '-') === false)
                            $arrPrdOid[] = $ret;

                    }
                }
            }
			
            $arrDadosProjetoVersao = $this->restricoesVinculacaoAcessorioDAO->retornaProjetoVersao($equoid);
            
            $eprnome = $arrDadosProjetoVersao['eprnome'];

            $tpcoid = $dados['conno_tipo'];
            $tpcdescricao = $dados['tpcdescricao'];
			
            /**
             * VALIDA SE TEM RESTRI��O PARA PROJETO E VERS�O 
             * DO EQUIPAMENTO INFORMADO
             */
            for($a=0;$a<count($arrPrdOid);$a++) {
                $restricaoV = $this->verificaRestricaoProjetoVersao($arrDadosProjetoVersao['eveprojeto'], $arrDadosProjetoVersao['eveoid'], $arrPrdOid[$a]);

                $prdnome = $this->retornaNomeProduto($arrPrdOid[$a]);

                //se houver restri��o, retorna erro
                if($restricaoV > 0) {
                    $retorno = array(
                        'status'  => 'erro',
                        'prdnome' => $prdnome,
                        'eprnome' => $eprnome,
                        'idmsg'   => '1503',
                        'msg'     => 'O equipamento '.$eprnome.' n�o pode ser instalado, pois o acess�rio '.$prdnome.' possui uma restri��o para este equipamento'
                    );
                    
                    return $retorno;
                }
            }
            
            /**
             * VALIDA SE TEM RESTRI��O PARA PROJETO 
             * DO EQUIPAMENTO INFORMADO
             */
            for($a=0;$a<count($arrPrdOid);$a++) {
            	
                $restricaoP = $this->verificaRestricaoProjeto($arrDadosProjetoVersao['eveprojeto'], $arrPrdOid[$a]);

                $prdnome = $this->retornaNomeProduto($arrPrdOid[$a]);
                
                //se houver restri��o, retorna erro
                if($restricaoP > 0) {
                    $retorno = array(
                        'status'  => 'erro',
                        'prdnome' => $prdnome,
                        'eprnome' => $eprnome,
                        'idmsg'   => '1503',
                        'msg'     => 'O equipamento '.$eprnome.' n�o pode ser instalado, pois o acess�rio '.$prdnome.' possui uma restri��o para este equipamento'
                    );
				
					return $retorno;
                }          
            }
        }

        /**
         * VALIDA SE TEM RESTRI��O PARA TIPO DE CONTRATO 
         * DO EQUIPAMENTO INFORMADO
         */
        $restricaoT = $this->verificaRestricaoEquipamentoTipoContrato($connumero, $equoid);
        
        //se houver restri��o, retorna erro
        if(!$restricaoT) {
            $dadosTC = $this->restricoesVinculacaoAcessorioDAO->retornaDadosTipoContrato($connumero);
            if (count($dadosTC) > 0) {
                $tpcdescricao = $dadosTC['tpcdescricao'];
            } else {
                $tpcdescricao = "INDEFINIDO";
            }

            $arrDadosProjetoVersao = $this->restricoesVinculacaoAcessorioDAO->retornaProjetoVersao($equoid);
            $eprnome = $arrDadosProjetoVersao['eprnome'];

            $retorno = array(
                'status'  => 'erro',
                'prdnome' => '',
                'eprnome' => $eprnome,
                'idmsg'   => '1503',
                'msg'     => 'O equipamento '.$eprnome.' n�o pode ser instalado, pois exite uma restri��o para o Tipo de Contrato '.$tpcdescricao
            );
        
            return $retorno;
        }          

        return $retorno;
    }

    public function validaAcessorios($ordoid, $consoid, $consrefioid, $prdoid)
    {
        $retorno = "";
        /**
         * RETORNA INFORMA��ES PARA VALIDA��O DE RESTRI��ES
         */
        $arrDadosRestricao = $this->retornaDadosValidacaoRestricoes($consoid);
        
        /**
         * RETORNA NOME DO ACESS�RIO
         */
        $prdnome = $this->retornaNomeProduto($prdoid);
        
        if(is_array($arrDadosRestricao)) {
            /**
             * VALIDA SE TEM RESTRI��O PARA PROJETO E VERS�O 
             * DO ACESS�RIO INFORMADO
             */
            $restricaoV = $this->verificaRestricaoProjetoVersao($arrDadosRestricao['eproid'], $arrDadosRestricao['eveoid'], $prdoid);
            
            //se houver restri��o, retorna erro
            if($restricaoV > 0) {
                $retorno = array(
                    'status'       => 'erro',
                    'prdnome'      => $prdnome,  
                    'eprnome'      => $arrDadosRestricao['eprnome'], 
                    'eveversao'    => $arrDadosRestricao['eveversao'], 
                    'eqcdescricao' => $arrDadosRestricao['eqcdescricao'],
                    'idmsg'        => '1500',
                    'msg'          => 'O acess�rio '.$prdnome.' n�o pode ser instalado, pois existe uma restri��o para o Equipamento '.$arrDadosRestricao['eprnome'].', vers�o '.$arrDadosRestricao['eveversao']
                );
				
				return $retorno;
            }
            
            /**
             * VALIDA SE TEM RESTRI��O PARA PROJETO 
             * DO ACESS�RIO INFORMADO
             */
            $restricaoP = $this->verificaRestricaoProjeto($arrDadosRestricao['eproid'], $prdoid);
             
            //se houver restri��o, retorna erro
            if($restricaoP > 0) {
                $retorno = array(
                    'status'  => 'erro',
                    'prdnome'      => $prdnome,  
                    'eprnome'      => $arrDadosRestricao['eprnome'], 
                    'eveversao'    => $arrDadosRestricao['eveversao'], 
                    'eqcdescricao' => $arrDadosRestricao['eqcdescricao'],
                    'idmsg'   => '1501',
                    'msg'     => 'O acess�rio '.$prdnome.' n�o pode ser instalado, pois existe uma restri��o para o Equipamento '.$arrDadosRestricao['eprnome']
                );
				
				return $retorno;
            }

            /**
             * VALIDA SE TEM RESTRI��O PARA TIPO DE CONTRATO 
             * DO ACESS�RIO INFORMADO
             */
                
            $restricaoT = $this->verificaRestricaoAcessorioTipoContrato($arrDadosRestricao['conno_tipo'], $prdoid);
            
            //se houver restri��o, retorna erro
            if($restricaoT > 0) {
                $retorno = array(
                    'status'  => 'erro',
                    'prdnome'      => $prdnome,  
                    'eprnome'      => $arrDadosRestricao['eprnome'], 
                    'eveversao'    => $arrDadosRestricao['eveversao'], 
                    'eqcdescricao' => $arrDadosRestricao['eqcdescricao'],
                    'idmsg'   => '1503',
                    'msg'     => 'O acess�rio '.$prdnome.' n�o pode ser instalado, pois exite uma restri��o para o Tipo de Contrato '.$arrDadosRestricao['tpcdescricao']
                );
            
                return $retorno;
            }          
            
            /**
             * VALIDA SE TEM RESTRI��O PARA CLASSE 
             * DO ACESS�RIO INFORMADO
             */
            $restricaoC = $this->verificaRestricaoClasse($arrDadosRestricao['eqcoid'], $prdoid);
            
            //se houver restri��o, retorna erro
            if($restricaoC > 0) {
                $retorno = array(
                    'status'       => 'erro',
                    'prdnome'      => $prdnome,  
                    'eprnome'      => $arrDadosRestricao['eprnome'], 
                    'eveversao'    => $arrDadosRestricao['eveversao'], 
                    'eqcdescricao' => $arrDadosRestricao['eqcdescricao'],
                    'idmsg'        => '1502',
                    'msg'          => 'O acess�rio '.$prdnome.' n�o pode ser instalado, pois existe uma restri��o para a classe '.$arrDadosRestricao['eqcdescricao']
                );
				
				return $retorno;
            }
        }
        
        return $retorno;
    }
    
    /**
     * 
     */
    public function retornaPrdOid($consoid, $consrefioid, $relroid)
    {
        $arrDadosContrato = $this->restricoesVinculacaoAcessorioDAO->retornaDadosContrato($consoid);

        $prdoid = "";
        
        if(is_array($arrDadosContrato)) {

            $consobroid     = $arrDadosContrato['consobroid'];
            $conssituacao   = $arrDadosContrato['conssituacao'];
            $connumero      = $arrDadosContrato['consconoid'];
            $consqtde       = $arrDadosContrato['consqtde'];
            $nome_obrigacao = $arrDadosContrato['nome_obrigacao'];
            $nome_descricao = $arrDadosContrato['nome_descricao'];
            
            if($nome_descricao==''){
                $nome_descricao = $nome_obrigacao;
            }
            
            $linhas = 0;
            
            $arrDadosObrigacaoFinanceira = $this->restricoesVinculacaoAcessorioDAO->retornaDadosObrigacaoFinanceira($consobroid);
            
            if(is_array($arrDadosObrigacaoFinanceira)) {
                $obroftoid             = $arrDadosObrigacaoFinanceira["obroftoid"];             //TIPO (serial, quantidade, b�sico, upgrade)
                $oftcatgoid            = $arrDadosObrigacaoFinanceira["oftcatgoid"];            //GRUPO ATUADOR
                $oftctabela            = $arrDadosObrigacaoFinanceira["oftctabela"];            //Tabela do atuador
                $oftcprefixo           = $arrDadosObrigacaoFinanceira["oftcprefixo"];           //Prefixo da tabela do atuador
                $oftcprefixo_status    = $arrDadosObrigacaoFinanceira["oftcprefixo_status"];    //Prefixo da tabela status do atuador
                $oftcprefixo_historico = $arrDadosObrigacaoFinanceira["oftcprefixo_historico"]; //Prefixo da tabela historico do atuador
                $oftcimotoid           = $arrDadosObrigacaoFinanceira["oftcimotoid"];           //Tipo do Imobilizado.
                $obrprdoid             = $arrDadosObrigacaoFinanceira["obrprdoid"];             //C�digo do produto
                $oftcnome              = $arrDadosObrigacaoFinanceira["oftcnome"];              //Descri��o
                
                if($oftctabela==''){
                        
                    $oftctabela            = 'imobilizado';
                    $oftcprefixo           = 'imob';
                    $oftcprefixo_status    = 'ims';
                    $oftcprefixo_historico = 'imobh';
                    $oftcimotoid           = 24;
                    
                }
                
               
				$cpserial = 'oid';  
				
                 
                $descricao  = $oftcprefixo.$cpserial;
                $exclusao   = $oftcprefixo.'dt_exclusao';
                $col_status = $oftcprefixo.$oftcprefixo_status.'oid';
                $col_relr   = $oftcprefixo.'relroid';
                
                if($oftctabela != 'imobilizado'){
                    
                    $prdoid = $this->restricoesVinculacaoAcessorioDAO->retornaPrdOidNaoImobilizado($oftcprefixo, $oftcprefixo_status, $oftctabela, $cpserial, $consrefioid);
                    
                }else{
                
                    $prdoid = $this->restricoesVinculacaoAcessorioDAO->retornaPrdOidImobilizado($oftcimotoid, $consrefioid, $cpserial); 
                }
            }
        }

        return $prdoid;
        
    }

	public function retornaEquOid($no_serie)
	{
		return $this->restricoesVinculacaoAcessorioDAO->retornaEquOid($no_serie);
	}


    /**
     * Validando o Status da linha vinculada no equipamento
     * @param stdClass $obj | todos os atributos s�o OBRIGAT�RIOS
     * $obj->linha      = linha
     * $obj->lincsloid  = id do status da linha
     * $obj->cslstatus  = String com o nome do status
     * $obj->connumero  = numero do contrato
     * $obj->equoid     = ID do equipamento
     **/
    public function validaStatusLinha($obj)
    {
        /*
        // Mensagens a serem apresentadas
        $mensagens = array(
            'habilitarLinha'    => "A Linha %s est� com o status %s. Voc� precisa abrir uma ASM e solicitar a reativa��o da linha para vincular este equipamento ao contrato.",
            'avisoBloqueio'     => "A Linha %s est� com o status %s. O v�nculo desse equipamento no contrato n�o ser� poss�vel.",
            'aviso'             => "A Linha %s est� com o status %s. Deseja prosseguir com a Instala��o deste serial?"
        );
        $regras = array(
            2  => array ('trava' => true, 'alerta' => $mensagens['habilitarLinha']), //Suspensa
            22 => array ('trava' => true, 'alerta' => $mensagens['habilitarLinha']), //Aguardando verifica��o
            25 => array ('trava' => true, 'alerta' => $mensagens['habilitarLinha']), //Aguardando suspens�o
            26 => array ('trava' => true, 'alerta' => $mensagens['habilitarLinha']), //Stand by            
            0  => array ('trava' => true, 'alerta' => $mensagens['avisoBloqueio']), //SEM LINHA
            3  => array ('trava' => true, 'alerta' => $mensagens['avisoBloqueio']), //Cancelada
            5  => array ('trava' => true, 'alerta' => $mensagens['avisoBloqueio']), //Aguardando cancelamento
            12 => array ('trava' => true, 'alerta' => $mensagens['avisoBloqueio']), //Dispon�vel para troca de chip
            21 => array ('trava' => true, 'alerta' => $mensagens['avisoBloqueio']), //Aguardando troca de chip
        );
        */
        /**
        * Buscando as particularidades dos status das linhas parametrizadas no DB
        * o retorno deve ser um array estruturado da mesma forma que o array acima comentado
        **/
        $regras = $this->restricoesVinculacaoAcessorioDAO->recuperaParticularidadesStatusLinha();
        /**
         * Verificando se o equipamento em quest�o � o �ltimo que foi instalado no contrato e se o contrato est� sem equipamento
         **/
        $retUltimoEquipamento = $this->validaUltimoEquipamento($obj);
        if (!$retUltimoEquipamento){

            if ($regras[ $obj->lincsloid ]){
                $regra = $regras[ $obj->lincsloid ];
                $regra['alerta'] = sprintf($regra['alerta'], $obj->linha, strtoupper($obj->cslstatus));
                return $regra;
            }else{
                // Se for algum status n�o previsto, mostra apenas o alerta
                $regra['trava'] = false;
                $regra['alerta'] = sprintf("A Linha %s est� com o status %s. Deseja prosseguir com a Instala��o deste serial?", $obj->linha, strtoupper($obj->cslstatus));
                return $regra;
            }

        }else{
            // Se o equipamento for o �ltimo equipamento instalado no contrato... permite o v�nculo
            $regra['trava'] = false;
            $regra['alerta'] = sprintf("A Linha %s est� com o status %s. Deseja prosseguir com a Instala��o deste serial?", $obj->linha, strtoupper($obj->cslstatus));
            return $regra;
        }

    }

    /**
     * Validando o Status da linha vinculada no equipamento
     * @param stdClass $obj
     * $obj->connumero  = numero do contrato * OBRIGAT�RIO
     * $obj->equoid     = ID do equipamento * OBRIGAT�RIO
     **/
    public function validaUltimoEquipamento($obj)
    {
        return $this->restricoesVinculacaoAcessorioDAO->validaUltimoEquipamento($obj);
    }

    /**
     * Valida se o equipamento pode ser instalado para um cliente que � premium
     * @STI 86821
     */
    public function validaEquipamentoPremium($eqcserie)
    {
        return $this->restricoesVinculacaoAcessorioDAO->validaEquipamentoPremium($eqcserie);
    }
    
    /**
     * Valida se o acess�rio pode ser instalado para um cliente premium.
     * @STI 86821
     */
    public function validaAcessorioPremium($obrprdoid)
    {
        return $this->restricoesVinculacaoAcessorioDAO->validaAcessorioPremium($obrprdoid);
    }

}