<?php

class Error {

    private $erros = array();


     public function __construct() {

        $this->setErro();

     }


     public function getErro($idErro) {

        $erro = $this->erros[$idErro];

        if(empty($erro)) {
            $erro = "Houve um erro no processamento dos dados.";
        }

        return $erro;

     }

     public function setErro() {

        //Erros comuns ao agendamentos Unitario e Frotas
        $this->erros['0001'] = '#0001: Erro ao atualizar o prestador de servi�o na ordem de servi�o.';
        $this->erros['0002'] = '#0002: Erro ao gravar hist�rico na ordem de servi�o.';
        $this->erros['0003'] = '#0003: Erro ao atualizar o instalador na ordem de servi�o.';
        $this->erros['0004'] = '#0004: Erro ao enviar a solicita��o ao BackOffice.';
        $this->erros['0005'] = '#0005: Erro limpar dados do local de instala��o.';

        $this->erros['0007'] = '#0007: Erro ao criar a atividade no OFSC.';
        $this->erros['0008'] = '#0008: Erro ao reservar estoque.';
        $this->erros['0009'] = '#0009: Erro ao consultar estoque.';
        $this->erros['0010'] = '#0010: Erro ao consultar o OFSC.';
        $this->erros['0011'] = '#0011: Erro ao cancelar a atividade no OFSC.';
        $this->erros['0012'] = '#0012: N�o foi poss�vel cancelar a reserva de estoque.';
        $this->erros['0013'] = '#0013: N�o foi poss�vel cancelar a solicita��o de estoque ao centro e distribui��o.';
        $this->erros['0014'] = '#0014: O estoque n�o est� mais dispon�vel.';

        $this->erros['0015'] = '#0015: Erro ao cancelar o agendamento anterior.';


     }

}

?>