<?php

/**
 * Classe de persist�ncia de dados
 *
 * @author Vanessa Rabelo <vanessa.rabelo@meta.com.br>
 */
class FinCreditoFuturoParametrizacaoMotivoCreditoDAO {

    /**
     * Conex�o com o 
     * @var connection  
     */
    private $conn;
    private $parametros;

    public function __construct($conn) {
        $this->parametros = new stdClass();
        $this->conn = $conn;
    }

    /**
     * M�todo Pesquisar
     * @param stdClass $parametros
     * @return array:object
     */
    public function visualisar(stdClass $parametros) {
        $resultadoPesquisa = array();
        $this->parametros->descricao = isset($parametros->descricao) ? strtolower($parametros->descricao) : "";
        $this->parametros->cfmctipo = isset($parametros->cfmctipo) ? $parametros->cfmctipo : "null";

        $sql = "SELECT 
    			  cfmcoid,
    					cfmcdescricao,
    					CASE 
                            WHEN cfmctipo = 0 THEN 'Outros' 
                            WHEN cfmctipo = 1 THEN 'Contesta��o'
                            WHEN cfmctipo = 2 THEN 'Indica��o de Amigo'
                            WHEN cfmctipo = 3 THEN 'Isen��o de Monitoramento'
                            WHEN cfmctipo = 4 THEN 'D�bito Autom�tico'
                            WHEN cfmctipo = 5 THEN 'Cart�o de Cr�dito'
                            ELSE 'Todos' 
                            END AS cfmctipo,
    					cfmcobservacao
    			FROM
    					credito_futuro_motivo_credito
                WHERE 
                        cfmcdt_exclusao IS NULL
               ";

        if (trim($this->parametros->descricao) != "") {
            $sql .= " AND LOWER(TRANSLATE(cfmcdescricao, '����������������������������','aaaaeeiiooouucAAAAEEIIOOUUC'))  ILIKE '%" . pg_escape_string(trim($this->parametros->descricao)) . "%'";
        }

        if (trim($this->parametros->cfmctipo) != "") {
            $sql .= " AND cfmctipo = " . intval($this->parametros->cfmctipo) . "";
        }

        $sql .= " ORDER BY cfmcdescricao ASC";

        if ($resultado = pg_query($sql)) {
            if (pg_num_rows($resultado) > 0) {
                while ($objeto = pg_fetch_object($resultado)) {
                    $resultadoPesquisa[] = $objeto;
                }
            }
        }


        return $resultadoPesquisa;
    }

    /**
     * M�todo que verifica a exist�ncia de uma tabela.
     *
     * @param String $tabela Nome da tabela.
     *
     * @return Boolean
     * @throws ErrorException
     */
    public function verificarUsoMotivo($parametros) {


        $sql = "SELECT
                        cfocfmcoid
                    FROM
                        credito_futuro 
                    WHERE
                        cfocfmcoid = " . intval($parametros->id) . "
                        AND cfodt_exclusao IS NULL 
                    LIMIT 1";

        $sql2 = "SELECT
                        cfcpcfmccoid
                    FROM
                        credito_futuro_campanha_promocional 
                    WHERE
                        cfcpcfmccoid = " . intval($parametros->id) . " 
                        AND cfcpdt_exclusao IS NULL
                    LIMIT 1";

        
        if (!$rs = pg_query($this->conn, $sql)) {
            throw new ErrorException(self::MENSAGEM_ERRO_PROCESSAMENTO);
        }

        if (!$rs2 = pg_query($this->conn, $sql2)) {
            throw new ErrorException(self::MENSAGEM_ERRO_PROCESSAMENTO);
        }

        if (pg_num_rows($rs) > 0 || pg_num_rows($rs2) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Excluir motivo de cr�dito
     * @param stdClass $parametros
     * @return boolean
     */
    public function excluirMotivo(stdClass $parametros) {
        $this->parametros->id = isset($parametros->id) ? $parametros->id : "";

        $this->parametros->cfmcoid = isset($parametros->cfmcoid) ? $parametros->cfmcoid : "";

        if (empty($this->parametros->id)) {
            throw new Exception('Selecione o registro.');
        } else {
            $sql = "UPDATE
    					credito_futuro_motivo_credito
            		SET 
            			cfmcdt_exclusao = NOW()
    				WHERE
    					cfmcoid = " . $this->parametros->id;
            if ($resultado = pg_query($sql)) {
                return true;
            }
            return false;
        }
    }

    /**
     * Verificar Exist�ncia Cadastro
     * Verifica se a descri��o j� est� cadastrada
     * @param stdClass $parametros
     * @return number
     */
    public function verificarExistenciaDescricao(stdClass $parametros) {
        $this->parametros->descricao = isset($parametros->descricao) ? strtolower($parametros->descricao) : "";

        $sql = "SELECT
    					COUNT(1) as total
    			FROM
    					credito_futuro_motivo_credito
    			WHERE
        		        cfmcdt_exclusao IS NULL         
    					AND LOWER(TRANSLATE(cfmcdescricao, '����������������������������','aaaaeeiiooouucAAAAEEIIOOUUC')) ILIKE '" . pg_escape_string($this->parametros->descricao) . "'";

        if ($resultado = pg_query($sql)) {
            $res = pg_fetch_object($resultado);
            return $res->total;
        }
        return 0;
    }

    /**
     * M�todo Cadastrar
     * @param stdClass $parametros
     * @throws Exception
     * @return boolean
     */
    public function cadastrar(stdClass $parametros) {


        $this->parametros->cfmctipo = $parametros->cfmctipo;
        $this->parametros->descricao = isset($parametros->descricao) ? $parametros->descricao : "";

        $this->parametros->cfmcobservacao = isset($parametros->cfmcobservacao) ? $parametros->cfmcobservacao : "";

        if (empty($this->parametros->descricao)) {
            throw new Exception('Existem campos obrigat�rios n�o preenchidos.');
        }


        $sql = "INSERT INTO
						credito_futuro_motivo_credito
    						(
							cfmcdescricao,
							cfmctipo,
							cfmcobservacao	)
    			VALUES
    						('" . pg_escape_string($this->parametros->descricao) . "',
    						 " . $this->parametros->cfmctipo . ",
    						 '" . pg_escape_string($this->parametros->cfmcobservacao) . "' 			
    							)";

        if ($resultado = pg_query($sql)) {
            if (pg_affected_rows($resultado) > 0) {
                return true;
            }
        }
        return false;
    }

}

