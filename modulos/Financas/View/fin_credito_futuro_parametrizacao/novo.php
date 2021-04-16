<div class="modulo_titulo">Motivo do Cr�dito</div>
<div class="modulo_conteudo">

    <?php echo $this->exibirMensagem(); ?>

    <div class="bloco_titulo">Dados Principais</div>
    <div class="bloco_conteudo">
        <div class="formulario">

            <div class="campo maior">
                <label id="lbl_cfmcdescricao" for="cfmcdescricao">Descri��o *</label>
                <input tabindex="1"  type="text" id="cfmcdescricao" name="descricao" class="campo" maxlength="80" value="<?php echo (isset($filtros->descricao)) ? htmlentities($filtros->descricao) : ''; ?>"  />
            </div>  

            <div class="clear"></div>
            <div class="campo maior">
                <label id="lbl_cfmctipo" for="cfmctipo">Tipo do Motivo:</label>
                <select tabindex="1" id="cfmctipo" name="cfmctipo" class="combo_pesquisa">
                    <option value=0>Outros</option>
                    <option value=1>Contesta��o</option>
                    <option value=2>Indica��o de Amigo</option>
                    <option value=3>Isen��o de Monitoramento</option>
                    <option value=4>D�bito Autom�tico</option>
                    <option value=5>Cart�o de Cr�dito</option>
                </select>
            </div>

            <div class="clear"></div>  
            <div class="campo maior">
                <label id="lbl_cfmcobservacao" for="cfmcobservacao">Observa��o</label>
                <textarea tabindex="4"  style="resize: none;" id="cfmcobservacao" rows="5" name="cfmcobservacao" class="campo" value="<? echo $cfmcobservacao; ?>"></textarea>
            </div>
            <div class="clear"></div> 

        </div>
    </div>
    <div class="bloco_acoes">
        <button tabindex="5"  type="button" id="cadastrarMotivoCredito">Cadastrar</button>
        <button tabindex="6" type="button" id="retornarMotivoCredito">Retornar</button>
    </div>


</div>