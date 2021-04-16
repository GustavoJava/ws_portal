<?php

/**
 * Classe FinManutencaoFaturamentoUnificadoDAO.
 * Camada de modelagem de dados.
 *
 * @package  Financas
 * @author   Andr� Luiz Zilz <andre.zilz@meta.com.br>
 *
 */
class FinManutencaoFaturamentoUnificadoDAO {

    /**
     * Conex�o com o banco de dados
     * @var resource
     */
    private $conn;

    /**
     * Mensagem de erro para o processamentos dos dados
     * @const String
     */
    const MENSAGEM_ERRO_PROCESSAMENTO = "Houve um erro no processamento dos dados.";


    /**
     * Construtor da Classe
     * @param object $conn
     */
    public function __construct($conn) {
    //Seta a conex�o na classe
    $this->conn = $conn;
    }

    public function buscarClienteContrato($contrato) {

        $sql = "SELECT 
                    conclioid 
                FROM 
                    contrato 
                WHERE 
                    connumero = " . intval($contrato);

        $rs = pg_query($this->conn, $sql);

        $clioid = '';
        if ($rs && pg_num_rows($rs) > 0) {
            $clioid = pg_fetch_result($rs, 0, 'conclioid');
        }

        return $clioid;
    }

    public function buscarTipoObrigacao($obroid) {

        $sql = " SELECT 
                    CASE 
						 WHEN alisercodigoservico = '126' THEN 
                            'L'
						 WHEN alisercodigoservico = '184' THEN 
                            'M'
                         WHEN alisercodigoservico = '143' THEN 
                            'M'
						 ELSE
                            ''
                    END AS tipo_obrigacao
                FROM 
                    obrigacao_financeira
                INNER JOIN
                    aliquota_servico ON aliseroid = obraliseroid
                WHERE 
                    obroid = " . intval($obroid);

        $rs = pg_query($this->conn, $sql);

        $tipoObrigacao = '';
        if ($rs && pg_num_rows($rs) > 0) {
            $tipoObrigacao = pg_fetch_result($rs, 0, 'tipo_obrigacao');
        }

        return $tipoObrigacao;
    }

    /**
     * Valida os OIDs dos dados vitais
     *
     * @param stdClass $dados
     * @return boolean
     */
    public function validarIDs(stdClass $dados) {

        $erro = '';

        /*
         * Verificar numero contrato
         */
        $sql = "
            SELECT EXISTS
                        (
                        SELECT
                            1
                        FROM
                            contrato
                        WHERE
                            connumero = ".$dados->prefconnumero."
                        )
                        AS existe ";

        $rs = pg_query($this->conn, $sql);
        $retorno = pg_fetch_object($rs);
        $erro .= ($retorno->existe == 'f') ? 'Contrato n�o localizado. ' : '';

        /*
         * Verificar codigo cliente
         */
        $sql = "
            SELECT EXISTS
                        (
                        SELECT
                            1
                        FROM
                            clientes
                        WHERE
                            clioid = ".$dados->prefclioid."
                        )
                        AS existe ";

        $rs = pg_query($this->conn, $sql);
        $retorno = pg_fetch_object($rs);
        $erro = ($retorno->existe == 'f') ? 'Cliente n�o localizado. ' : '';

		/*
		 * Verificar c�digo do cliente � o mesmo do contrato
		 */
		$sql = "SELECT EXISTS
							(
								SELECT
									1	
								FROM
									contrato
								WHERE
									connumero = ".$dados->prefconnumero."
								AND conclioid = ".$dados->prefclioid."
							)
							AS existe ";

		$rs = pg_query($this->conn, $sql);
        $retorno = pg_fetch_object($rs);
        $erro .= ($retorno->existe == 'f') ? 'O contrato informado n�o pertence ao cliente. ' : '';

        /*
        * Verificar codigo Obriga��o Financeira
        */
        $sql = "
            SELECT EXISTS
                        (
                        SELECT
                            1
                        FROM
                            obrigacao_financeira
                        WHERE
                            obroid = ".$dados->prefobroid."
                        )
                        AS existe ";

        $rs = pg_query($this->conn, $sql);
        $retorno = pg_fetch_object($rs);
        $erro .= ($retorno->existe == 'f') ? 'C�digo da obriga��o financeira inv�lido. ' : '';

        if(empty($dados->prefvalor)) {
            $erro .= 'Valor precisa ser maior ou igual a zero e ter formato monet�rio. ';
        }

        if(empty($dados->preftipo_obrigacao)) {
            $erro .= 'Tipo inv�lido, informar L = "Loca��o" ou M = "Monitoramento". ';
        }

        if(empty($dados->operacao)) {
            $erro .= 'Opera��o inv�lida, informar I = "Inclus�o" ou R = "Remo��o". ';
    }

        if($dados->operacao == 'I') {
            $sql = "
                SELECT EXISTS
                (
                    SELECT
                        1
                    FROM
                        previsao_faturamento
                    WHERE
                        prefclioid = ".$dados->prefclioid."
                        AND prefobroid = ".$dados->prefobroid."
                        AND prefconnumero = ".$dados->prefconnumero."
                        AND preftipo_obrigacao = '".$dados->preftipo_obrigacao."'
                        AND TO_CHAR(prefdt_referencia, 'mm/yyyy') = '".substr($dados->prefdt_referencia, 3, 7)."'
                )
                AS existe";

            $rs = pg_query($this->conn, $sql);
            $retorno = pg_fetch_object($rs);
            $erro .= ($retorno->existe == 't') ? 'Cobran�a em duplicidade, verifique. ' : '';
        }

        return $erro;
    }

    /**
     * Verifica se existe registro na tabela com a data vigente informada
     *
     * @param string $data
     * @return boolean
     */
    public function veririfcarDataReferencia($data) {

         /*
        * Verificar codigo Obriga��o Financeira
        */
        $sql = "
             SELECT EXISTS
                        (
                        SELECT
                            1
                        FROM
                            previsao_faturamento
                        WHERE
                            TO_CHAR(prefdt_referencia, 'mm/yyyy') = '".$data."'
                        )
                        AS existe";


        $rs = pg_query($this->conn, $sql);
        $retorno = pg_fetch_object($rs);
        $existe = ($retorno->existe == 'f') ? false : true;

        return $existe;

    }

    /**
     * Verifica se j� existe o registro na tabela
     *
     * @param stdClass $dados
     * @return boolean
     */
     public function veririfcarRegistroRedundante(stdClass $dados) {

         /*
        * Verificar codigo Obriga��o Financeira
        */
        $sql = "
            SELECT EXISTS
                        (
                        SELECT
                            1
                        FROM
                            previsao_faturamento
                        WHERE
                            prefclioid = ".$dados->prefclioid."
                        AND
                            prefobroid = ".$dados->prefobroid."
                        AND
                            prefconnumero = ".$dados->prefconnumero."
                        AND
                            preftipo_obrigacao = '".$dados->preftipo_obrigacao."'
                        )
                        AS existe";

        $rs = pg_query($this->conn, $sql);
        $retorno = pg_fetch_object($rs);
        $existe = ($retorno->existe == 'f') ? false : true;

   
        return $existe;

    }

    /**
     * Insere os Dados na tabela
     *
     * @param stdClassc $dados
     * @return boolean
     */
    public function inserirDados(stdClass $dados) {

        $sql = "
            EXECUTE
                insert_previsao_faturamento
                (
                ".$dados->prefclioid.",
                ".$dados->prefobroid.",
                ".$dados->prefvalor.",
                ".$dados->prefconnumero.",
                '".$dados->preftipo_obrigacao."',
                '".$dados->prefdt_referencia."'::timestamp
                )
            ";

        if(!$rs = pg_query($this->conn, $sql)) {
            return false;
        }

        return true;

    }

    /**
     * Prapra a query de insert
     */
    public function preparaQueryInsert() {

        pg_query($this->conn, 'DEALLOCATE ALL');

        $sql = "
            PREPARE
                insert_previsao_faturamento
                (
                integer,
                integer,
                numeric,
                integer,
                character,
                timestamp
                ) AS
            INSERT INTO
                previsao_faturamento
                (
                prefclioid,
                prefobroid,
                prefvalor,
                prefconnumero,
                preftipo_obrigacao,
                prefdt_referencia
                )
                VALUES
                ($1, $2, $3, $4, $5, $6);
             ";

            pg_query($this->conn, $sql);
    }

    /**
     * Prapra a query de Dele��o
     */
    public function preparaQueryDelete() {

        $sql = "
            PREPARE
                delete_previsao_faturamento
                AS
            DELETE FROM
                previsao_faturamento
            WHERE
                prefclioid = $1
            AND
                prefobroid = $2
            AND
                prefconnumero = $3
            AND
                TO_CHAR(prefdt_referencia, 'mm/yyyy') = $4
             ";

            pg_query($this->conn, $sql);
    }

     /**
     * Deleta os Dados na tabela
     *
     * @param stdClassc $dados
    * @param string $data
     * @return string
     */
    public function excluirDados(stdClass $dados, $data) {

      /*  $sql = "
            EXECUTE
                delete_previsao_faturamento
                (
                ".$dados->prefclioid.",
                ".$dados->prefobroid.",
                ".$dados->prefconnumero.",
                '".$dados->preftipo_obrigacao."',
                '".$data."'
                )
            ";*/
          
        $sql = "
        		DELETE FROM
                previsao_faturamento
            WHERE
                prefclioid = ".$dados->prefclioid."
            AND
                prefobroid = ".$dados->prefobroid."
            AND
                prefconnumero = ".$dados->prefconnumero."
            AND
                TO_CHAR(prefdt_referencia, 'mm/yyyy') = '".$data."'
        		";


        if(!$rs = pg_query($this->conn, $sql)) {
            return 'ERRO';
        }

        if(pg_affected_rows($rs) == 0) {
            return 'ZERO';
        }

        return 'OK';

    }

    /**
     * Abre a transa��o
     */
    public function begin(){
        pg_query($this->conn, 'BEGIN');
    }

    /**
     * Finaliza um transa��o
     */
    public function commit(){
        pg_query($this->conn, 'COMMIT');
    }

    /**
     * Aborta uma transa��o
     */
    public function rollback(){
        pg_query($this->conn, 'ROLLBACK');
    }
}
?>
