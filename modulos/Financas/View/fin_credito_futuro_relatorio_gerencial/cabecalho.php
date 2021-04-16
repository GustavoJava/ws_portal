<?php cabecalho(); ?>

<!-- CSS -->
<link type="text/css" rel="stylesheet" href="lib/layout/1.1.0/style.css" />
<link type="text/css" rel="stylesheet" href="lib/layout/1.1.0/jquery-ui/jquery-ui.custom.min.css" />

<!-- JAVASCRIPT -->
<script type="text/javascript" src="lib/layout/1.1.0/jquery/jquery.min.js"></script>
<script type="text/javascript" src="lib/layout/1.1.0/jquery-ui/jquery-ui.custom.min.js"></script>
<script type="text/javascript" src="lib/layout/1.1.0/jquery/jquery.maskedinput.min.js"></script>
<script type="text/javascript" src="lib/layout/1.1.0/bootstrap.js"></script>

<!-- Arquivo javascript da demanda -->
<script type="text/javascript" src="modulos/web/js/fin_credito_futuro_relatorio_gerencial.js"></script>

<style type="text/css">
	ul.ui-autocomplete {
        overflow-x: hidden !important;
        overflow-y: scroll !important;
        width: 244px !important;
    }

 	.total_linha{
 		background: #BAD0E5;
 	}

</style>

<div class="modulo_titulo">Cr�dito Futuro - Relat�rio Gerencial</div>
<div class="modulo_conteudo">
	<ul class="bloco_opcoes">
	    <li class="<?php echo $this->view->parametros->aba_ativa == 'credito_conceder' ? 'ativo' : ($this->view->parametros->aba_ativa != 'credito_concedidos' && $this->view->parametros->aba_ativa != 'campanhas_vigentes' ? 'ativo' : '') ?>">
            <a href="rel_descontos_conceder.php" title="Relat�rio de Descontos a Conceder">
                Relat�rio de Descontos a Conceder
            </a>
        </li>
	    <li class="<?php echo $this->view->parametros->aba_ativa == 'credito_concedidos' ? 'ativo' : '' ?>">
            <a href="fin_credito_futuro_relatorio_gerencial.php?acao=relatorioCreditosConcedidos" title="Relat�rio de Descontos a Concedidos">
                Relat�rio de Descontos Concedidos
            </a>
        </li>
	    <li class="<?php echo $this->view->parametros->aba_ativa == 'campanhas_vigentes' ? 'ativo' : '' ?>">
            <a href="fin_credito_futuro_relatorio_gerencial.php?acao=listarCampanhasVigentes" title="Campanhas Promocionais Vigentes">
                Campanhas Promocionais Vigentes
            </a>
        </li>
	</ul>