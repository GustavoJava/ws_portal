
</div>
<div class="separador"></div>

<?php if (count($this->view->campos) > 0) : ?>
	<!--  Caso contenha erros, exibe os campos destacados  -->
	<script type="text/javascript" >jQuery(document).ready(function() {
		showFormErros(<?php echo json_encode($this->view->campos); ?>); 
	});
	</script>

<?php endif; ?>

<!-- Rodap� -->
<?php require_once 'lib/rodape.php'; ?>