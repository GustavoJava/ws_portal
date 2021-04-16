<?php


require_once _MODULEDIR_ . 'Cron/DAO/VivoBloqueiosDAO.php';

/**
 * Classe respons�vel pelas regras de neg�cio Bloqueio Vivo
 * @author Vanessa Rabelo <vanessa.rabelo@meta.com.br>,Angelo Frizzo <angelo.frizzo@meta.com.br>
 * @since 01/10/2013
 * @category Class
 * @package  BloqueioVivo
 *
 */

class BloqueioVivo {

    private $dao ;


    /**
    * Busca os arquivos abaixo no diret�rio
    * caso existam todos os arquivos nesse diret�rio deve iniciar a atualiza��o.
    *  Todos os arquivos devem ser do tipo TXT e iniciados por FLEET_COBRANCA_.
    *
    *  @return string
    */
    public function importarArquivosServidor() {

        $caminho = "/var/www/ARQUIVO_BLOQUEIO_PARCEIRO/";

        $status = 1;

        if (is_dir($caminho)) {

            $diretorio = dir($caminho);

            $existeArquivos = scandir($caminho);

            if (count($existeArquivos) > 2) {
                //lendo os arquivos do diret�rio
                while ($arquivo = $diretorio->read()) {
                    //recupera a extens�o do arquivo em letras maiusculas
                    $extensao = strtoupper(end(explode(".", $arquivo)));

                    //verificar o tipo de arquivo
                    if (($extensao == 'DAT') && (strripos($arquivo, "FLEET_COBRANCA_") !== false)) {
                        echo "<br>ARQUIVO LOCALIZADO:".$arquivo;echo"<br>";
                        // Realiza RN004 - Bloqueio/Desbloqueio
                        $processou = $this->realizarBloqueioDesbloqueio($caminho."/".$arquivo);

                        if ($processou == 1) {
                                echo "<br>ARQUIVO PROCESSADO:".$arquivo;echo"<br>";
                                //gera Log de processamento
                                $this->gravarLog($arquivo, $processou);
                                //move o arquivo para a pasta arquivo morto
                                rename($caminho."/".$arquivo, "/var/www/ARQUIVO_MORTO_BLOQUEIO_PARCEIRO/".$arquivo);
                                echo "Movido para: /var/www/ARQUIVO_MORTO_BLOQUEIO_PARCEIRO/".$arquivo."<br/>";

                        } else {
                                echo "<br>ARQUIVO N�O PROCESSADO:".$arquivo;echo"<br>";
                                //gera Log de processamento
                                $this->gravarLog($arquivo, $processou);
                                //move o arquivo para a pasta arquivo morto
                                rename($caminho."/".$arquivo, "/var/www/ARQUIVO_MORTO_BLOQUEIO_PARCEIRO/".$arquivo);
                                echo "Movido para: /var/www/ARQUIVO_MORTO_BLOQUEIO_PARCEIRO/".$arquivo."<br/>";
                                $status = 0;
                        }
                    } else {
                        if ((trim($arquivo) != '.')&&(trim($arquivo) != '..')) {

                                    //gera Log de processamento
                                    $this->gravarLog($arquivo, false);
                                    //move o arquivo para a pasta arquivo morto
                                    rename($caminho."/".$arquivo, "/var/www/ARQUIVO_MORTO_BLOQUEIO_PARCEIRO/".$arquivo);
                                    echo "<br>Arquivo inv�lido Movido para: /var/www/ARQUIVO_MORTO_BLOQUEIO_PARCEIRO/".$arquivo."<br/>";
                                    $status = 0;
                        }
                    }
                }
            } else {
            	$status = 2;
            }

        } else {
        	$status = 0;
        	throw new Exception('Erro na importa��o de Arquivos.');
        }

        return $status;

    }


    /**
     * Grava log de sucesso e erro
     *
     * @param int ( $mensagem,$tipo)
     *
     * @return void
     */

    public function gravarLog($mensagem, $tipo) {
        $this->dao->gravarLog($mensagem, $tipo);
    }

    /**
    * Busca os veiculo parceiro na tabela relacionado ao n�mero  da conta para realizar  Bloqueio/desbloqueio de veiculo
    *
    * @param int $caminho dados do local do diretorio
    *
    * @return array
    */
    public function realizarBloqueioDesbloqueio($caminho) {

    	try {

	        //Inicia a transa��o
	        //$this->dao->begin();

	        $ponteiro = fopen($caminho, "r");
	        $acoes = array("H", "N", "F");
	        $processou = 1;
	        $veiculo = array();
            $totalLinhas = 0;

            if(!$ponteiro) {
                throw new Exception('Erro ao Abrir Arquivo') ;
            }

            while (!feof($ponteiro)) {
               $conteudo = fgets($ponteiro, 4096);
               if(trim($conteudo) == '') {
                   continue;
               }
               $totalLinhas++;
            }

            if ($totalLinhas < 3) {
                fclose($ponteiro);
                throw new Exception('Arquivo sem cabe�alho/rodap� ou linhas com conte�do.') ;
            }

            rewind($ponteiro);

	        while (!feof($ponteiro)) {

               $linha = fgets($ponteiro, 4096);

                if (stripos($linha,'FLEET')) {
                   continue;
                }

                //Pula o Cabe�alho e o rodap�
                if ((trim($linha) == '')) {
                    continue;
                }

	            $registro = explode("|", $linha);
	            $conta = trim($registro[0]);
	            $acao = trim($registro[1]);

	            if (empty($acao) || empty($conta)) {
                    fclose($ponteiro);
	            	throw new Exception('Nenhuma a��o/conta encontrada.') ;
	            }

		        // verifica e busca veiculo na tabela veiculo_pedido_parceiro
		        $veiculos = $this->dao->buscarVeiculo($conta);

		        if (!$veiculos) {

                    echo "<pre>";
                        print_r($registro);
                    echo "</pre>";
                    fclose($ponteiro);
		        	throw new Exception('Nenhum veiculo encontrado') ;
		        }

	           	foreach ($veiculos AS $veiculo) {

	               	echo "<br>Veiculo Encontrado: veioid = ".$veiculo['vppaveioid'];

                	if ($acao == "A") {
                		$sasweb = 1;
                	} else if (in_array($acao, $acoes)) {
                		$sasweb = 0;

                		if ($acao == 'F') {


                            // TODO: Desconmentar o trecho abaixo, assim que terminar
                			if (!$this->gerarRescisao($veiculo)){
                            fclose($ponteiro);
                			 throw new Exception('Erro ao gerar rescis�o.');
                			}

                            $modalidadeDoContrato = $this->dao->buscarModalidadeContrato($veiculo['connumero']);

                            //se for um contrato de loca��o gera OS de retirada.
                            if ($modalidadeDoContrato != false && !empty($modalidadeDoContrato) && trim($modalidadeDoContrato) == 'L') {

                                $this->gerarOSRetirada($veiculo);

                            }
                		}
                	}

                	// atualiza flag sasweb na tabela veiculos
                	$this->dao->atualizarVeiculoSasweb($veiculo['vppaveioid'], $sasweb);

                	// registra a��es na tabela veiculo_parceiro_bloqueio
                	$this->dao->gravarVeiculoBloqueio($veiculo['vppaveioid'], trim($acao));

	            }

	        }

	        fclose($ponteiro);

	        $this->dao->commit();

    	} catch (Exception $e) {

    		$this->dao->rollback();
            echo $e->getMessage();
    		$processou = 0;

    	}

    	return $processou;

    }


    private function gerarOSRetirada($dados) {

        //Verifica se N�O existe uma OS para o contrato
        $verificaOS = $this->dao->verificarExistenciaOS($dados['connumero']);
        if (!isset($verificaOS->ordoid)){
            //Gera OS
            $OSRetirada = $this->dao->geraOSRetirada($dados);
            if ($OSRetirada === false){
                throw new Exception(self::ERRO_DEFAULT);
            } else {
                $verificaOS->ordoid = $OSRetirada;
            }
        } else {
            //Caso existir a os atualiza as informa��es
            $this->dao->atualizarOSRetirada($verificaOS->ordoid);
        }

        //Cancela os servi�os (itens), n�o exclu�dos e cujo tipo n�o s�o de retirada, na OS gerada/ atualizada
        $this->dao->cancelarItensOSNRetirada($verificaOS->ordoid);


        /**
         * Verificar se na OS gerada/atualizada existe um servi�o,
         * cujo status seja: "Autorizado" ou "Pendente", n�o exclu�do,
         * do tipo retirada
         */
        if (!$this->dao->verificarExistenciaItemRetirada($verificaOS->ordoid)){
            //Se n�o existir, insere o servi�o de retirada
            $this->dao->inserirItemOS($verificaOS->ordoid, 110, $dados['classe']);
        }


        /**
         * Buscar o(s) tipo(s) de item, n�o exclu�do(s), do tipo Retirada,
         * cuja obriga��o financeira esteja relacionada ao acess�rio (servi�o)
         * n�o exclu�do, cuja situa��o seja Loca��o ou B�sico,
         * instalado e relacionado ao contrato
         */
        $itensServico = $this->dao->buscarItensContratoServico($dados['connumero']);
        //Insere um item na os para cada servi�o
        if (count($itensServico) > 0){
            foreach($itensServico as $servico){
                $this->dao->inserirItemOS($verificaOS->ordoid, $servico->otioid, $dados['classe']);
            }
        }

        $this->dao->incluirHistoricoOsRetirada($verificaOS->ordoid);

        return true;

    }

    public function gerarRescisao($dados) {

    	$observacao = 'Rescis�o Inadimpl�ncia VIVO';

    	$idRescisaoGerada = $this->dao->gerarRescisao($dados, $observacao);

    	if (!$idRescisaoGerada) {
    		return false;
    	}

    	if (!$this->dao->inserirHistoricoRescisao($idRescisaoGerada, $observacao)){
    		return false;
    	}

    	if (!$this->dao->cancelarContrato($dados['connumero'])){
    		return false;
    	}

    	if (!$this->dao->inserirHistoricoContrato($dados['connumero'], $observacao)){
    		return false;
    	}

    	if (!$this->dao->alterarStatusEquipamento($dados['equoid'])){
    		return false;
    	}

    	return true;
    }

    /**
     * Metodo Construtor
     */
    public function __construct() {
        $this->dao = new VivoBloqueiosDAO();
    }

}