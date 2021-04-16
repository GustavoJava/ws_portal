<?php require_once $this->view->caminho.'cabecalho.php'; ?>

<?php if (!empty($this->view->mensagem->info)) : ?>
    <div class="mensagem info"><?php echo $this->view->mensagem->info; ?></div>
<?php endif; ?>

<div id="div_mensagem_geral" class="mensagem invisivel"></div>

<?php if (!empty($this->view->mensagem->alerta)) : ?>
    <div class="mensagem alerta"><?php echo $this->view->mensagem->alerta; ?></div>
<?php endif; ?>

<?php if (!empty($this->view->mensagem->erro)) : ?>
    <div class="mensagem erro"><?php echo $this->view->mensagem->erro; ?></div>
<?php endif; ?>

<?php if (!empty($this->view->mensagem->sucesso)) : ?>
    <div class="mensagem sucesso"><?php echo $this->view->mensagem->sucesso; ?></div>
<?php endif; ?>

<?php require_once $this->view->caminho.'index_formulario.php'; ?>

<?php if (isset($this->view->dados->arquivo) && $this->view->dados->arquivo) : ?>
    <?php require_once $this->view->caminho.'index_conteudo.php'; ?>
<?php endif; ?>

<?php if (isset($this->view->dados->pesquisa) && $this->view->dados->pesquisa) : ?>
    <?php require_once $this->view->caminho.'index_listagem.php'; ?>
<?php endif; ?>

<?php require_once $this->view->caminho.'rodape.php'; ?>