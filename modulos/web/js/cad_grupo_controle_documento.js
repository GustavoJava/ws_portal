jQuery(document).ready(function(){
   
   //bot�o novo
   jQuery("#bt_novo").click(function(){
       window.location.href = "cad_grupo_controle_documento.php?acao=cadastrar";
   });
   
   //bot�o voltar
   jQuery("#bt_voltar").click(function(){
       window.location.href = "cad_grupo_controle_documento.php?acao=pesquisar";
   });

   jQuery(".bt_excluir").click(function(event){
   	event.preventDefault();

   	if (confirm("Deseja realmente excluir o registro?")) {
   		window.location.href = jQuery(this).attr('href');
   	}

   });
   
});