/*
 * @author Jorge A. D. Kautzmann <jorge.kautzmann@sascar.com.br>
 * @description	M�dulo para STI - JS
 * @version 10/10/2012 [0.0.1]
 * @package SASCAR Intranet
 * 
*/

jQuery(document).ready(function () {

    jQuery('body').delegate('#horas_utilizadas', 'click', function() {
        mostraDetalhes();
    });

});

/*
 * Fun��o para validar e enviar o form de pesquisa
*/
function pesquisar() {
	if(document.solstiform.pesq_periodo_inicial.value == '' ||  document.solstiform.pesq_periodo_final.value == ''){
		alert('O Per�odo da pesquisa � de preenchimento obrigat�rio!');
		return;
	}
	if(!validarDatasPeriodo(document.solstiform.pesq_periodo_inicial.value, document.solstiform.pesq_periodo_final.value)){
		alert('O Per�odo informado � inv�lido!');
		return;
	}
	document.solstiform.acao.value='pesquisar';
	document.solstiform.origem_req.value='form-submit';
	document.solstiform.target='_top';
	document.solstiform.submit();
}

/*
 * Fun��o para abrir itens da aba principal
*/
function abreAbas(acao){
	location='ti_acompanhamento_sti.php?acao=' + acao;
}

/*
 * Fun��o abrir popup de pesquisa de solicitante
*/
function pesquisarSolicitantePop(){
	window.open('ti_acompanhamento_sti.php?acao=pesquisar-solicitante','ContrlWindow','status,scrollbars=yes,menubar=no,toolbar=no,width=640,height=320');
}
/*
 * Fun��o para submeter form de pesquisa de solicitante (PoPup)
*/
function pesquisarSolicitante() {
	document.solstiform.acao.value='pesquisar-solicitante';
	document.solstiform.origem_req.value='form-submit';
	document.solstiform.target='_top';
	document.solstiform.submit();
}
/*
 * Fun��o para limpar form de pesquisa de solicitante (PoPup)
*/
function limparPesquisaSolicitante() {
	document.solstiform.sti_solicitante.value='';
	document.solstiform.cd_usuario_solicitante.value='';
}
/*
 * Fun��o para retorno de dados da popup de pesquisa para o form principal
*/
function voltarPesquisaSolicitante(cd_usuario, nm_usuario){
	window.opener.document.solstiform.sti_solicitante.value=nm_usuario;
	window.opener.document.solstiform.cd_usuario_solicitante.value=cd_usuario;
	window.close();
}

/*
 * Fun��o para validar um per�odo
*/
function validarDatasPeriodo(dataInicio, dataFim){
	var diaInicio = (dataInicio.split('/')[0]);
	var mesInicio = (dataInicio.split('/')[1]);
	var anoInicio = (dataInicio.split('/')[2]);

	var diaFim = (dataFim.split('/')[0]);
	var mesFim = (dataFim.split('/')[1]);
	var anoFim = (dataFim.split('/')[2]);

	var dataInicio = anoInicio+'-'+mesInicio+'-'+diaInicio;
	var dataFim = anoFim+'-'+mesFim+'-'+diaFim;
	if(Date.parse(dataInicio) > Date.parse(dataFim)){
		return false;
	}
	return true;
}

/*
 * Fun��o redireciona para formul�rio de detalhes da STI
*/
function exibirDetalhes(stiID){
	document.location='ti_acompanhamento_sti.php?acao=exibir-dados&reqioid=' + stiID;
}

/*
 * Fun��o para confirmar a conclus�o de uma STI (checkbox)
*/
function concluirSTICbx(){
	if(document.solstiform.sti_concluida.checked == true && document.solstiform.sti_suspender.checked == true){
		alert('N�o � poss�vel concluir e suspender uma STI ao mesmo tempo!');
		document.solstiform.sti_concluida.checked = false;
		return;
	}
	if(document.solstiform.sti_concluida.checked == true && document.solstiform.sti_novo_fluxo.checked == true){
		alert('N�o � poss�vel concluir uma STI e registrar novo fluxo ao mesmo tempo!');
		document.solstiform.sti_concluida.checked = false;
		return;
	}
	if(document.solstiform.sti_concluida.checked == true){
		if(confirm('Depois de Finalizada, n�o ser� mais poss�vel a edi��o da STI. Deseja realmente concluir a STI?')){
			document.solstiform.sti_concluida.checked = true;
		}else{
			document.solstiform.sti_concluida.checked = false;
		}
	}
}

/*
 * Fun��o para confirmar a suspens�o de uma STI (checkbox)
*/
function suspenderSTICbx(){
	if(document.solstiform.sti_suspender.checked == true && document.solstiform.sti_novo_fluxo.checked == true){
		alert('N�o � poss�vel suspender uma STI e registrar novo fluxo ao mesmo tempo!');
		document.solstiform.sti_suspender.checked = false;
		return;
	}
	if(document.solstiform.sti_suspender.checked == true){
		if(confirm('Deseja realmente suspender a STI?')){
			document.solstiform.sti_suspender.checked = true;
		}else{
			document.solstiform.sti_suspender.checked = false;
		}
	}
}

/*
 * Fun��o para confirmar o registro de novo fluxo de uma STI (checkbox)
*/
function registrarNovoFluxoSTICbx(){
	if(document.solstiform.sti_novo_fluxo.checked == true && document.solstiform.sti_suspender.checked == true){
		alert('N�o � poss�vel suspender uma STI e registrar novo fluxo ao mesmo tempo!');
		document.solstiform.sti_novo_fluxo.checked = false;
		return;
	}
	if(document.solstiform.sti_novo_fluxo.checked == true){
		if(confirm('Deseja realmente registrar um novo fluxo para a STI?')){
			document.solstiform.sti_novo_fluxo.checked = true;
		}else{
			document.solstiform.sti_novo_fluxo.checked = false;
		}
	}
}

/*
 * Fun��o para validar e enviar o form de classifica��o (edi��o de dados principais)
*/
function confirmarClassificacao() {

	var teveErro = false;
	document.getElementById('tipo_sti').className 		= 'inputNormal';
	document.getElementById('fluxo').className		 	= 'inputNormal';
	document.getElementById('solicitante').className 	= 'inputNormal';
	document.getElementById('centro_custo').className 	= 'inputNormal';
	document.getElementById('natureza').className 		= 'inputNormal';
	document.getElementById('responsavel').className 	= 'inputNormal';
	document.getElementById('assunto').className 		= 'inputNormal';
	document.getElementById('descricao').className 		= 'inputNormal';
	
	if(document.solstiform.tipo_sti.value == ''){
		teveErro = true;
		document.getElementById('tipo_sti').className = 'inputError';
	}
	if(document.solstiform.fluxo.value == ''){
		teveErro = true;
		document.getElementById('fluxo').className = 'inputError';
	}
	if(document.solstiform.solicitante.value == ''){
		teveErro = true;
		document.getElementById('solicitante').className = 'inputError';
	}
	if(document.solstiform.centro_custo.value == ''){
		teveErro = true;
		document.getElementById('centro_custo').className = 'inputError';
	}
	if(document.solstiform.natureza.value == ''){
		teveErro = true;
		document.getElementById('natureza').className = 'inputError';
	}
	if(document.solstiform.responsavel.value == ''){
		teveErro = true;
		document.getElementById('responsavel').className = 'inputError';
	}
	if(document.solstiform.assunto.value == ''){
		teveErro = true;
		document.getElementById('assunto').className = 'inputError';
	}
	if(document.solstiform.descricao.value == ''){
		teveErro = true;
		document.getElementById('descricao').className = 'inputError';
	}
	if(teveErro){
		alert('Existem campos de preenchimento obrigat�rio que n�o foram preenchidos!');
		return;
	}
	if(document.solstiform.sti_suspender.checked == true){
		if(document.solstiform.justificativa.value == ''){
			alert('Necess�rio Informar a Justificativa!');
			return;
		}
	}
	if(document.solstiform.sti_novo_fluxo.checked == true){
		if(document.solstiform.justificativa.value == ''){
			alert('Necess�rio Informar a Justificativa!');
			return;
		}
	}
	document.solstiform.acao.value='confirmar-classificacao';
	document.solstiform.target='_top';
	document.solstiform.submit();
}


/*
 * Fun��o para validar a incluis�o de novo planejamento de fase
*/
function incluirPlanejamentoFase(){

	var teveErro = false;
	document.getElementById('sti_pfase').className 			= 'inputNormal';
	document.getElementById('sti_recurso').className		= 'inputNormal';
	document.getElementById('sti_fase_inicio').className 	= 'inputNormal';
	document.getElementById('sti_fase_final').className 	= 'inputNormal';
	document.getElementById('sti_fase_horas').className 	= 'inputNormal';
	
	if(document.solstiform.sti_pfase.value == ''){
		teveErro = true;
		document.getElementById('sti_pfase').className = 'inputError';
	}
	if(document.solstiform.sti_recurso.value == ''){
		teveErro = true;
		document.getElementById('sti_recurso').className = 'inputError';
	}
	if(document.solstiform.sti_fase_inicio.value == ''){
		teveErro = true;
		document.getElementById('sti_fase_inicio').className = 'inputError';
	}
	if(document.solstiform.sti_fase_final.value == ''){
		teveErro = true;
		document.getElementById('sti_fase_final').className = 'inputError';
	}
	if(document.solstiform.sti_fase_horas.value == ''){
		teveErro = true;
		document.getElementById('sti_fase_horas').className = 'inputError';
	}
	if(teveErro){
		alert('Existem campos de preenchimento obrigat�rio que n�o foram preenchidos!');
		return;
	}
	if(!validarDatasPeriodo(document.solstiform.sti_fase_inicio.value, document.solstiform.sti_fase_final.value)){
		alert('O Per�odo informado � inv�lido!');
		return;
	}
	document.solstiform.acao.value='incluir-planejamento-fase';
	document.solstiform.target='_top';
	document.solstiform.submit();
}


/*
 * Fun��o para validar a altera��o de novo planejamento de fase
*/
function alterarPlanejamentoFase(){

	var teveErro = false;
	document.getElementById('sti_pfase').className 			= 'inputNormal';
	document.getElementById('sti_recurso').className		= 'inputNormal';
	document.getElementById('sti_fase_inicio').className 	= 'inputNormal';
	document.getElementById('sti_fase_final').className 	= 'inputNormal';
	document.getElementById('sti_fase_horas').className 	= 'inputNormal';
	
	if(document.solstiform.sti_pfase.value == ''){
		teveErro = true;
		document.getElementById('sti_pfase').className = 'inputError';
	}
	if(document.solstiform.sti_recurso.value == ''){
		teveErro = true;
		document.getElementById('sti_recurso').className = 'inputError';
	}
	if(document.solstiform.sti_fase_inicio.value == ''){
		teveErro = true;
		document.getElementById('sti_fase_inicio').className = 'inputError';
	}
	if(document.solstiform.sti_fase_final.value == ''){
		teveErro = true;
		document.getElementById('sti_fase_final').className = 'inputError';
	}
	if(document.solstiform.sti_fase_horas.value == ''){
		teveErro = true;
		document.getElementById('sti_fase_horas').className = 'inputError';
	}
	if(teveErro){
		alert('Existem campos de preenchimento obrigat�rio que n�o foram preenchidos!');
		return;
	}
	if(!validarDatasPeriodo(document.solstiform.sti_fase_inicio.value, document.solstiform.sti_fase_final.value)){
		alert('O Per�odo informado � inv�lido!');
		return;
	}
	document.solstiform.acao.value='alterar-planejamento-fase';
	document.solstiform.target='_top';
	document.solstiform.submit();
}

 /*
 * Fun��o para validar a exclus�o de um planejamento de fase
*/
function excluirPlanejamentoFase(reqieroid){
	if(confirm('Deseja realmente excluir este item do planejamneto?')){
		document.solstiform.reqieroid.value=reqieroid;
		document.solstiform.acao.value='excluir-planejamento-fase';
		document.solstiform.target='_top';
		document.solstiform.submit();
	}else{
		return;
	}
}
 
 /*
 * Fun��o para abrir/fechar aba de detalhes de uma STI na rela��o
*/
function abreAbaDetalheSTIRelacao(reqioid){
	var divID = 'iDetRel_' + reqioid;
	var divIDLinkAbrir = 'iDetRelA_' + reqioid;
	var divIDLinkFechar = 'iDetRelF_' + reqioid;
	if(document.getElementById(divID).style.display == 'none'){
		xajax_getDetalheSTIRelacao(reqioid);
		document.getElementById(divID).style.display = 'block';
		document.getElementById(divIDLinkAbrir).style.display = 'none';
		document.getElementById(divIDLinkFechar).style.display = 'block';
	}else{
		document.getElementById(divID).style.display = 'none';
		document.getElementById(divIDLinkAbrir).style.display = 'block';
		document.getElementById(divIDLinkFechar).style.display = 'none';
	}
}

/*
 * Fun��o para confirmar o in�cio da execu��o de tarefa (recurso-fase planejada)
*/
function iniciarExecucaoCbx(){
	if(document.solstiform.cbx_iniciar_execucao.checked == true){
		if(confirm('Deseja realmente Iniciar a execu��o desta tarefa?')){
			document.solstiform.cbx_iniciar_execucao.checked = true;
		}else{
			document.solstiform.cbx_iniciar_execucao.checked = false;
		}
	}
}

/*
 * Fun��o para confirmar a conclus�o de tarefa (recurso-fase planejada)
*/
function concluirExecucaoCbx(){
	if(document.solstiform.cbx_concluir_execucao.checked == true){
		if(confirm('Deseja realmente Concluir a execu��o desta tarefa?')){
			document.solstiform.cbx_concluir_execucao.checked = true;
		}else{
			document.solstiform.cbx_concluir_execucao.checked = false;
		}
	}
}


/*
 * Fun��o para validar a altera��o de novo planejamento de fase
*/
function confirmarAbaFase(){

	var teveErro = false;
	var percent = 0;

	document.getElementById('reqierdt_previsao_inicio').className 	= 'inputNormal';
	document.getElementById('reqierdt_previsao_fim').className		= 'inputNormal';
	document.getElementById('reqierdescricao_defeito').className 	= 'inputNormal';

	percentProgresso = parseInt(document.solstiform.reqierprogresso.value);
	
	if(document.solstiform.reqierdt_previsao_inicio.value == ''){
		teveErro = true;
		document.getElementById('reqierdt_previsao_inicio').className = 'inputError';
	}

	if(document.solstiform.reqierdt_previsao_fim.value == ''){
		teveErro = true;
		document.getElementById('reqierdt_previsao_fim').className = 'inputError';
	}

	if((percentProgresso < 0) || (percentProgresso > 100)){
		alert('Percentual de progresso inv�lido!');
		document.getElementById('reqierprogresso').className = 'inputError';
		return;
	}

	if(teveErro){
		alert('Existem campos de preenchimento obrigat�rio que n�o foram preenchidos!');
		return;
	}

	if(!validarDatasPeriodo(document.solstiform.reqierdt_previsao_inicio.value, document.solstiform.reqierdt_previsao_fim.value)){
		alert('O Per�odo Previsto informado � inv�lido!');
		document.getElementById('reqierdt_previsao_inicio').className = 'inputError';
		document.getElementById('reqierdt_previsao_fim').className = 'inputError';
		return;
	}

    // acrescentar valida��o
    if(document.solstiform.reqierdt_inicio.value != '' && document.solstiform.reqierdt_conclusao.value != ''){
        if(!validarDatasPeriodo(document.solstiform.reqierdt_inicio.value, document.solstiform.reqierdt_conclusao.value)){
            alert('O Per�odo Realizado informado � inv�lido!');
            document.getElementById('reqierdt_inicio').className = 'inputError';
            document.getElementById('reqierdt_conclusao').className = 'inputError';
            return;
        }
	}

	xajax_setFaseExecucaoRecurso(xajax.getFormValues('solstiform',true));
}

function atualizarDefeitosAbaFase()
{
	xajax_atualizarDefeitosAbaFase(xajax.getFormValues('solstiform',true));
}


/*
 * Fun��o para validar e enviar arquivo de evidencia ou anexo
*/
function enviarArquivoAnexo(){
	document.getElementById('arquivoAnexo').className = 'inputNormal';
	document.getElementById('arquivoAnexoDescricao').className = 'inputNormal';
	if(document.solstiform.arquivoAnexo.value == ''){
		alert('Necess�rio selecionar o arquivo a ser anexado!');
		document.getElementById('arquivoAnexo').className = 'inputError';
		return;
	}
	if(document.solstiform.arquivoAnexoDescricao.value == ''){
		alert('Necess�rio informar uma descri��o para o arquivo de evid�ncia!');
		document.getElementById('arquivoAnexoDescricao').className = 'inputError';
		return;
	}
	document.solstiform.acao.value='enviar-arquivo-anexo';
	document.solstiform.target='iframeUploader';
	document.solstiform.submit();
}


/*
 * Fun��o para tratar retorno de envio de arquivo de evid�ncia
*/
function retornoArquivoAnexo(msg_retorno, st_retorno, reqieroid){
	document.getElementById('fases_form_area_recurso_msg').innerHTML = '<span class="msg">' + msg_retorno + '</span>';
	/* seta dados dos campos de arquivo e descri��o para campos em branco*/
	if(st_retorno == 'ok'){
		document.getElementById('arquivoAnexo').value = '';
		document.getElementById('arquivoAnexoDescricao').value = '';
		xajax_getFasesAnexos(reqieroid);
		xajax_getFasesHistorico(reqieroid);
	}else{
		document.getElementById('arquivoAnexo').className = 'inputError';
		document.getElementById('arquivoAnexoDescricao').className = 'inputError';
	}
}


/*
 * Fun��o para validar a exclus�o de um anexo sti-reqieroid_sel-
*/
function validaExcluirAnexo(reqioid, reqieroid, riaoid){
	if(confirm('Deseja realmente excluir este anexo?')){
		xajax_excluiAnexo(reqioid, reqieroid, riaoid);
	}else{
		return;
	}
}


/*
 * Fun��o manipular dados de Fluxos
*/
function fluxoBtChange(){
	document.solstiform.acao.value='gerenciar-fluxos';
	document.solstiform.target='_top';
	document.solstiform.submit();
}
/*
 * Fun��o validar inclus�o de novo Fluxo
*/
function confirmarNovoFluxo(){
	var teveErro = false;
	if(document.solstiform.reqifoid.value != ''){
		alert('Selecione a op��o Novo Fluxo!');
		return;
	}
	document.getElementById('reqifdescricao').className	= 'inputNormal';
	document.getElementById('reqifusuoid_responsavel').className = 'inputNormal';
	if(document.solstiform.reqifdescricao.value == ''){
		teveErro = true;
		document.getElementById('reqifdescricao').className = 'inputError';
	}
	if(document.solstiform.reqifusuoid_responsavel.value == ''){
		teveErro = true;
		document.getElementById('reqifusuoid_responsavel').className = 'inputError';
	}
	if(teveErro){
		alert('Existem campos de preenchimento obrigat�rio que n�o foram preenchidos!');
		return;
	}
	document.solstiform.acao.value='confirmar-novo-fluxo';
	document.solstiform.target='_top';
	document.solstiform.submit();
}


/*
 * Fun��o para validar a exclus�o de fluxo
*/
function excluirFluxo(){
	if(document.solstiform.reqifoid.value == ''){
		alert('Selecione um Fluxo para exclus�o!');
		return;
	}
	if(confirm('Deseja realmente excluir este Fluxo?')){
		document.solstiform.acao.value='excluir-fluxo';
		document.solstiform.target='_top';
		document.solstiform.submit();
	}else{
		return;
	}
}

/**
 * Fun��o para atualizar o fluxo (Origem defeito)
 */
function atualizarFase(){
	if(confirm('Deseja realmente atualizar esta fase?')){
		document.solstiform.acao.value='atualizar-fase';
		document.solstiform.target='_top';
		document.solstiform.submit();
	}else{
		return;
	}
}

/*
 * Fun��o validar inclus�o de novo Fluxo
*/
function adicionarFaseFuncao(){
	var teveErro = false;
	document.getElementById('reqifoid').className	= 'inputNormal';
	document.getElementById('reqiffreqifsoid').className	= 'inputNormal';
	document.getElementById('reqiffordem').className	= 'inputNormal';
	document.getElementById('reqifdescricao').className	= 'inputNormal';
	if(document.solstiform.reqifoid.value == ''){
		teveErro = true;
		document.getElementById('reqifoid').className = 'inputError';
	}
	if(document.solstiform.reqiffreqifsoid.value == ''){
		teveErro = true;
		document.getElementById('reqiffreqifsoid').className = 'inputError';
	}
	if(document.solstiform.reqiffordem.value == ''){
		teveErro = true;
		document.getElementById('reqiffordem').className = 'inputError';
	}
	if(document.solstiform.reqiffreqifcoid.value == ''){
		teveErro = true;
		document.getElementById('reqiffreqifcoid').className = 'inputError';
	}
	if(teveErro){
		alert('Existem campos de preenchimento obrigat�rio que n�o foram preenchidos!');
		return;
	}
	document.solstiform.acao.value='adicionar-fase-fluxo';
	document.solstiform.target='_top';
	document.solstiform.submit();
}


/*
 * Fun��o para validar a exclus�o de fluxo-fase 
*/
function excluirFaseFuncao(reqiffoid){
	if(confirm('Deseja realmente excluir este Item?')){
		document.solstiform.reqiffoid.value=reqiffoid;
		document.solstiform.acao.value='excluir-fase-fluxo';
		document.solstiform.target='_top';
		document.solstiform.submit();
	}else{
		return;
	}
}

/* Controle de fases */

/*
 * Fun��o para manipular combo-box com fases
*/
function faseBtChange(){
	document.solstiform.acao.value='gerenciar-fases';
	document.solstiform.target='_top';
	document.solstiform.submit();
}
/*
 * Fun��o validar inclus�o de novo Fluxo
*/
function confirmarNovaFase(){
	var teveErro = false;
	if(document.solstiform.reqifsoid.value != ''){
		alert('Selecione a op��o Nova Fase!');
		return;
	}
	document.getElementById('reqifsoid').className	= 'inputNormal';
	document.getElementById('reqifsdescricao').className = 'inputNormal';
	if(document.solstiform.reqifsdescricao.value == ''){
		teveErro = true;
		document.getElementById('reqifsdescricao').className = 'inputError';
	}
	if(teveErro){
		alert('Existem campos de preenchimento obrigat�rio que n�o foram preenchidos!');
		return;
	}
	document.solstiform.acao.value='confirmar-nova-fase';
	document.solstiform.target='_top';
	document.solstiform.submit();
}


/*
 * Fun��o para validar a exclus�o de fase
*/
function excluirFase(){
	if(document.solstiform.reqifsoid.value == ''){
		alert('Selecione uma Fase para exclus�o!');
		return;
	}
	if(confirm('Deseja realmente excluir esta Fase?')){
		document.solstiform.acao.value='excluir-fase';
		document.solstiform.target='_top';
		document.solstiform.submit();
	}else{
		return;
	}
}

/*
 * Fun��o para controlar combobox de Fun��es
*/
function funcaoBtChange(){
	document.solstiform.acao.value='gerenciar-funcoes';
	document.solstiform.target='_top';
	document.solstiform.submit();
}
/*
 * Fun��o validar inclus�o de nova Fun��o
*/
function confirmarNovaFuncao(){
	var teveErro = false;
	if(document.solstiform.reqifcoid.value != ''){
		alert('Selecione a op��o Nova Fun��o!');
		return;
	}
	document.getElementById('reqifcdescricao').className	= 'inputNormal';
	if(document.solstiform.reqifcdescricao.value == ''){
		teveErro = true;
		document.getElementById('reqifcdescricao').className = 'inputError';
	}
	if(teveErro){
		alert('Existem campos de preenchimento obrigat�rio que n�o foram preenchidos!');
		return;
	}
	document.solstiform.acao.value='confirmar-nova-funcao';
	document.solstiform.target='_top';
	document.solstiform.submit();
}


/*
 * Fun��o para validar a exclus�o de funcao
*/
function excluirFuncao(){
	if(document.solstiform.reqifcoid.value == ''){
		alert('Selecione uma Fun��o para exclus�o!');
		return;
	}
	if(confirm('Deseja realmente excluir esta Fun��o?')){
		document.solstiform.acao.value='excluir-funcao';
		document.solstiform.target='_top';
		document.solstiform.submit();
	}else{
		return;
	}
}

/*
 * Fun��o validar inclus�o de novo Fluxo
*/
function adicionarFuncaoUsuario(){
	var teveErro = false;
	document.getElementById('reqifcoid').className	= 'inputNormal';
	document.getElementById('reqifuusuoid').className	= 'inputNormal';
	document.getElementById('reqifureqieoid').className	= 'inputNormal';
	if(document.solstiform.reqifcoid.value == ''){
		teveErro = true;
		document.getElementById('reqifcoid').className = 'inputError';
	}
	if(document.solstiform.reqifuusuoid.value == ''){
		teveErro = true;
		document.getElementById('reqifuusuoid').className = 'inputError';
	}
	if(document.solstiform.reqifureqieoid.value == ''){
		teveErro = true;
		document.getElementById('reqifureqieoid').className = 'inputError';
	}
	if(teveErro){
		alert('Existem campos de preenchimento obrigat�rio que n�o foram preenchidos!');
		return;
	}
	document.solstiform.acao.value='adicionar-funcao-usuario';
	document.solstiform.target='_top';
	document.solstiform.submit();
}


/*
 * Fun��o para validar a exclus�o de fun��o-usu�rio
*/
function excluirFuncaoUsuario(reqifuoid){
	if(confirm('Deseja realmente excluir este Item?')){
		document.solstiform.reqifuoid.value=reqifuoid;
		document.solstiform.acao.value='excluir-funcao-usuario';
		document.solstiform.target='_top';
		document.solstiform.submit();
	}else{
		return;
	}
}

/*
 * Funcao para exibir detalhes dos apontamentos da STI
 */
function mostraDetalhes() {

    var sti = jQuery("#sti").val();
    var fase = jQuery('#navAbasFases table tr td .active').attr("fase");
    var lancamento = jQuery('#navAbasFases table tr td .active').attr("lancamento");

    jQuery.ajax({
        url: "ti_acompanhamento_sti.php",
        type: "GET",
        data: {
            "acao": "ajaxDesenhaDetalhesApontamentos",
            "sti": sti,
            "fase": fase,
            "lancamento": lancamento
        },
        beforeSend: function() {
            jQuery('#tabela_detalhes_apontamentos').html('');
        }

    }).success( function(data) {
        jQuery('#tabela_detalhes_apontamentos').html(data);

    }).done( function( data ) {

        if ( data != '' ) {

            jQuery("#exibir_detalhes_apontamentos").dialog({
                autoOpen: false,
                minHeight: 300 ,
                width: 700,
                modal: true,
            }).dialog('open');

        } else {
            alert("N�o existem apontamentos de hora para esta fase.");
        }

    });

    return false;    
}