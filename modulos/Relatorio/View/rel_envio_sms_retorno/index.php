		<?php require_once _MODULEDIR_ . "Relatorio/View/rel_envio_sms_retorno/cabecalho.php"; ?>
    	
	    <!-- Mensagens-->
	    <div id="mensagem_erro" class="mensagem erro <?php if (empty($this->view->mensagemErro)): ?>invisivel<?php endif;?>">
	        <?php echo $this->view->mensagemErro; ?>
	    </div>
	    
	    <div id="mensagem_alerta" class="mensagem alerta <?php if (empty($this->view->mensagemAlerta)): ?>invisivel<?php endif;?>">
	        <?php echo $this->view->mensagemAlerta; ?>
	    </div>
	    
	    <div id="mensagem_sucesso" class="mensagem sucesso <?php if (empty($this->view->mensagemSucesso)): ?>invisivel<?php endif;?>">
	        <?php echo $this->view->mensagemSucesso; ?>
	    </div>
	    
	    <!-- Corpo P�gina Pesquisa-->
	    <form id="form" name="form" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
	    	<input type="hidden" id="acao" name="acao" value="pesquisar"/>
	    
	    	<?php require_once _MODULEDIR_ . "Relatorio/View/rel_envio_sms_retorno/formulario_pesquisa.php"; ?>
	    
	    	<div id="resultado_pesquisa" >
	    
			    <?php 
		        if ($this->view->status && count($this->view->dados) > 0) { 
		            require_once _MODULEDIR_ . "Relatorio/View/rel_envio_sms_retorno/resultado_pesquisa.php"; 
		        } 
		        elseif ($this->view->nome_arquivo != '') {
		        	require_once _MODULEDIR_ . "Relatorio/View/rel_envio_sms_retorno/csv.php";
				}	        
		        ?>
		    
	    	</div>
	    </form>
    	
		<?php require_once _MODULEDIR_ . "Relatorio/View/rel_envio_sms_retorno/rodape.php"; ?>