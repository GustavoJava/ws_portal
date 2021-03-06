

<?php require_once _MODULEDIR_ . "Cadastro/View/cad_categoria_bonificacao_representante/cabecalho.php"; ?>


    
    <!-- Mensagens-->
    <div id="mensagem_info" class="mensagem info">Os campos com * s?o obrigat?rios.</div>
    
    <div id="mensagem_erro" class="mensagem erro <?php if (empty($this->view->mensagemErro)): ?>invisivel<?php endif;?>">
        <?php echo $this->view->mensagemErro; ?>
    </div>
    
    <div id="mensagem_alerta" class="mensagem alerta <?php if (empty($this->view->mensagemAlerta)): ?>invisivel<?php endif;?>">
        <?php echo $this->view->mensagemAlerta; ?>
    </div>
    
    <div id="mensagem_sucesso" class="mensagem sucesso <?php if (empty($this->view->mensagemSucesso)): ?>invisivel<?php endif;?>">
        <?php echo $this->view->mensagemSucesso; ?>
    </div>
    
    
    <form id="form_cadastrar"  method="post" action="">
       <?php 
    	if(count($this->view->editar)> 0) {
    		$acao = "editar";
    	}else{
    		$acao = "cadastrar";
    	}
    ?>
    <input type="hidden" id="acao" name="acao" value="<?php echo $acao;?>"/>
    <input type="hidden" id="bonrecatoid" name="bonrecatoid" value="<?php echo $this->view->editar->bonrecatoid;?>"/>
    
    
    <?php require_once _MODULEDIR_ . "Cadastro/View/cad_categoria_bonificacao_representante/formulario_cadastro.php"; ?>
    
    </form>
    
    <?php if (count($this->view->dados) > 0) : ?>
    <!--  Caso contenha erros, exibe os campos destacados  -->
    <script type="text/javascript" >jQuery(document).ready(function() {
        showFormErros(<?php echo json_encode($this->view->dados); ?>); 
    });
    </script>
    
    <?php endif; ?>

<?php require_once _MODULEDIR_ . "Cadastro/View/cad_categoria_bonificacao_representante/rodape.php"; ?>
