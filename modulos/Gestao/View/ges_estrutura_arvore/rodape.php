
</div>
<div class="separador"></div>
<?php $this->layout->renderizarFooter(); ?>


<!-- Rodap� -->
<?php //require_once 'lib/rodape.php'; ?>

 <?php if (!empty($this->view->mensagemSucesso)): ?>
    <script type="text/javascript">
        jQuery(document).ready(function(){
        	window.parent.recarregaArvore();
        });
    </script>
 <?php endIf; ?>
</body>
</html>