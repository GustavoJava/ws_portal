<?php

/**
 * Classe padr�o para DAO
 *
 * @author robson.silva
 */
class CadGrupoTrabalhoDAO {

    /**
     * Conex�o com o banco de dados.
     * 
     * @var resource
     */
    private $conn;

    /**
     * Mensagem de erro para o processamentos dos dados
     * @const String
     */

    const MENSAGEM_ERRO_PROCESSAMENTO = "Houve um erro no processamento dos dados.";

    /**
     * Construtor da classe. Obrigat�rio passar a conex�o por parametro.
     * 
     * @param resource $conn Conex�o com o banco de dados
     */
    public function __construct($conn) {
        //Seta a conex�o na classe
        $this->conn = $conn;
    }

    /**
     * M�todo para realizar a pesquisa de varios registros.
     * 
     * @param stdClass $parametros Filtros da pesquisa.
     * 
     * @return array
     * @throws ErrorException
     */
    public function pesquisar() {

        $retorno = array();

        $sql = "SELECT 
					gtroid, 
					gtrnome, 
					gtrdt_inclusao, 
					gtrdt_alteracao, 
					gtrvisualizacao_individual, 
					gtrlancamento_edicao
				FROM 
					grupo_trabalho
				WHERE 
					gtrdt_exclusao IS NULL
                ORDER BY 
                    gtrnome ASC";




        if (!$rs = pg_query($this->conn, $sql)) {
            throw new ErrorException(self::MENSAGEM_ERRO_PROCESSAMENTO);
        }

        while ($row = pg_fetch_object($rs)) {
            $row->gtrvisualizacao_individual = ($row->gtrvisualizacao_individual == 't') ? 'Sim' : 'N�o';
            $row->gtrlancamento_edicao = ($row->gtrlancamento_edicao == 't') ? 'Sim' : 'N�o';
            $retorno[] = $row;
        }

        return $retorno;
    }

    /**
     * Respons�vel por atualizar os registros.
     * 
     * @param stdClass $dados Dados a serem gravados.
     * 
     * @return boolean
     * @throws ErrorException
     */
    public function atualizar(stdClass $dados) {

        //Valida os dados
        /* Esta op��o ser� sempre verdadeira.
		 * $dados->gtrvisualizacao_individual = ($dados->gtrvisualizacao_individual == '1') ? 'TRUE' : 'FALSE';
		 */
		$dados->gtrvisualizacao_individual = 'TRUE';
		$dados->gtrlancamento_edicao = ($dados->gtropcoes == 'gtrlancamento_edicao') ? 'TRUE' : 'FALSE';
        

        $sql = "UPDATE
					grupo_trabalho
				SET
					gtrusuoid_alteracao        = " . intval($dados->cd_usuario) . ",
					gtrdt_alteracao            = NOW(),
					gtrvisualizacao_individual = " . $dados->gtrvisualizacao_individual . ",
					gtrlancamento_edicao       = " . $dados->gtrlancamento_edicao . "
				WHERE 
					gtroid = " . intval($dados->gtroid) . "";

        if (!pg_query($this->conn, $sql)) {
            throw new ErrorException(self::MENSAGEM_ERRO_PROCESSAMENTO);
        }

        return true;
    }

    /**
     * Abre a transa��o.
     * 
     * @return void
     */
    public function begin() {
        pg_query($this->conn, 'BEGIN');
    }

    /**
     * Finaliza um transa��o.
     * 
     * @return void
     */
    public function commit() {
        pg_query($this->conn, 'COMMIT');
    }

    /**
     * Aborta uma transa��o.
     * 
     * @return void
     */
    public function rollback() {
        pg_query($this->conn, 'ROLLBACK');
    }

}

?>
