<div class="">
	<ul class="bloco_opcoes">
		<li>
			<a href="cad_parametrizacao_rs_calculo_repasse.php" title="C�lculo do Repasse">C�lculo do Repasse</a>
		</li>
        <li class="ativo">
            <a href="cad_parametrizacao_rs_calculo_repasse.php?acao=historico" title="Hist�rico C�lculo do Repasse">Hist�rico C�lculo do Repasse</a>
        </li>
		<li class="">
            <a href="cad_retencao_impostos.php" title="Reten��o de Impostos">Reten��o de Impostos</a>
        </li>
        <li class="">
            <a href="cad_retencao_impostos.php?acao=historico" title="Hist�rico Reten��o de Impostos">Hist�rico Reten��o de Impostos</a>
        </li>
	</ul>
</div>
<div class="bloco_titulo">Dados para Pesquisa</div>
<div class="bloco_conteudo">
    <div class="formulario">
    	<div class="campo data periodo">
            <div class="inicial">
                <label for="data_inicial">Per�odo *</label>
                <input type="text" class="campo" value="<?php echo isset($this->parametros->data_inicial) && !empty($this->parametros->data_inicial) ? $this->parametros->data_inicial : '' ?>" id="data_inicial" name="data_inicial">
            </div>
            <div class="campo label-periodo">�</div>
            <div class="final">
                <label for="data_final">&nbsp;</label>
                <input type="text" class="campo" value="<?php echo isset($this->parametros->data_final) && !empty($this->parametros->data_final) ? $this->parametros->data_final : '' ?>" id="data_final" name="data_final">

            </div>
        </div>
        <div class="campo menor">
        	<label for="prscroid">C�digo</label>
        	<input id="prscroid" maxlength="11" name="prscroid" value="<?php echo isset($this->parametros->prscroid) ? $this->parametros->prscroid : '' ?>" class="campo" type="text">
        </div>
        <div class="clear"></div>
    </div>
</div>

<div class="bloco_acoes">
    <button type="submit" id="bt_pesquisar">Pesquisar</button>
</div>







