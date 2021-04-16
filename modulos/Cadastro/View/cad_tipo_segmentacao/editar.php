<?php cabecalho(); ?>

<!-- LINKS PARA CSS E JS -->
<?php require _MODULEDIR_ . 'Cadastro/View/cad_tipo_segmentacao/head.php' ?>

<div class="modulo_titulo">Cadastro de Tipos de Segmenta��o</div>
<div class="modulo_conteudo">
    <div class="mensagem info">Os campos com * s�o obrigat�rios.</div>
    <div id="msg_alerta" class="mensagem alerta invisivel"></div>				
    <div id="msg_sucesso" class="mensagem sucesso invisivel"></div>
    <div id="msg_erro" class="mensagem erro invisivel"></div>
    <form id="form_editar"  method="post">
        <input type="hidden" name="acao" value="atualizar" />
        <input type="hidden" name="tpsoid" value="<?php echo $this->TipoSegmentacao->tpsoid ?>" />
        <div class="bloco_titulo">Dados Principais</div>        
        <div class="bloco_conteudo">
            <div class="formulario">                
                <div class="campo medio">
                    <label for="tpsdescricao">Descri��o *</label>
                    <input type="text" id="tpsdescricao" name="tpsdescricao" value="<?php echo $this->TipoSegmentacao->tpsdescricao ?>" class="campo" />
                </div>   
                <div class="clear"></div>
                <fieldset class="medio">
                    <legend>Tipo Principal *</legend>
                    <input type="radio" id="tpsprincipal_sim" class="tpsprincipal" <?php echo $this->TipoSegmentacao->tpsprincipal == 't' ? 'checked="checked"' : '' ?>  name="tpsprincipal" value="sim" />
                    <label for="tpsprincipal_sim">Sim</label>
                    <input type="radio" id="tpsprincipal_nao" class="tpsprincipal" <?php echo $this->TipoSegmentacao->tpsprincipal == 'f' ? 'checked="checked"' : '' ?> name="tpsprincipal" value="nao" />
                    <label for="tpsprincipal_nao">N�o</label>                    
                </fieldset>
                <div class="clear"></div>
                <div id="combo_principal_novo" class="campo medio invisivel">
                    <label for="modulo_principal">M�dulo Principal *</label>
                    <select class="float-left" id="tpssegmentacao" name="tpssegmentacao" value="">
                       <option value="">Escolha</option>
                            <?php foreach($this->comboTiposSegmentacao as $tipoSegmentacao):  ?>
                            <option <?php echo $this->TipoSegmentacao->TipoSegmentacaoPai->tpsoid == $tipoSegmentacao['tpsoid'] ? 'selected="selected"' : '' ?> value="<?php echo $tipoSegmentacao['tpsoid'] ?>"><?php echo $tipoSegmentacao['tpsdescricao'] ?></option>  
                            <?php endforeach; ?>   
                    </select>                    
                    <img class="loaging-circle float-left" src="modulos/web/images/ajax-loader-circle.gif" />
                </div>
                <div class="clear"></div>
            </div>
        </div>
        <div class="bloco_acoes">           
            <button id="btn_editar" type="button">Alterar</button>
            <button id="btn_voltar" type="button">Voltar</button>
        </div>
    </form>
    <div class="separador"></div>
    <div class="div-loding">
        <img class="invisivel loading" src="modulos/web/images/loading.gif" />
    </div>
</div>
<div class="separador"></div>
<?php include "lib/rodape.php"; ?>