<script type="text/javascript" src="modulos/web/js/fin_credito_futuro_parametrizacao_aba_email_aprovacao.js"></script>

<div class="modulo_titulo">E-mail para Aprova��o do Cr�dito Futuro</div>
<div class="modulo_conteudo">
    <?php echo $this->exibirMensagem(); ?>
	<?php include "form_email_aprovacao_busca.php" ?>
	<?php include "form_email_aprovacao_parametros.php" ?>
	<?php include "historico_email_aprovacao.php" ?>
</div>