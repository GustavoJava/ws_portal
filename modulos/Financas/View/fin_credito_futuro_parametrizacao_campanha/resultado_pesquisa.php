<div class="separador"></div>
<?php if(count($this->parametroPesquisa->resultado) > 0): ?>
<div class="bloco_titulo">Resultado da Pesquisa</div>
<div class="bloco_conteudo">
    <div class="listagem">
        <table>
                <thead>
                        <tr>
                            <th class="esquerda">C�d. Identif.</th>
                            <th width="150" class="esquerda">Per�odo de Vig�ncia</th>
                            <th class="esquerda">Tipo de Camp. Promoc.</th>
                            <th class="esquerda">Motivo do Cr�dito</th>
                            <th class="esquerda">Tipo do Desconto</th>
                            <th class="esquerda">Percentual</th>
                            <th width="60" class="esquerda">Valor</th>
                            <th class="esquerda">Forma de Aplica��o</th>
                            <th class="esquerda">Qtd. Parcelas</th>
                            <th class="esquerda">Obriga��o Financeira</th>
                            <th class="esquerda">Usu�rio</th>
                            <th class="esquerda">A��o</th>
                        </tr>
                </thead>
                <tbody>
                <?php foreach ($this->parametroPesquisa->resultado as $item) : ?>
                    <?php $class = $class == 'par' ? '' : 'par'; ?>
                    <tr class="<?php echo $class ?>">                        
                        <?php $tipoDesconto = !empty($item->cfcptipo_desconto) && $item->cfcptipo_desconto == 'P' ? 'Percentual' : 'Valor'; ?>
                        <?php $formaAplica��o = !empty($item->cfcpaplicacao) && $item->cfcpaplicacao == 'I' ? 'Integral' : 'Parcela'; ?>
                        <?php $qtdParcelas = !empty($item->cfcpaplicacao) && $item->cfcpaplicacao == 'I' ? '1' : $item->cfcpqtde_parcelas ; ?>
                        <td class="direita" ><?php echo $item->cfcpoid; ?></td>
                        <td class="centro"><?php echo $item->cfcpdt_inicio_vigencia; ?> a <?php echo $item->cfcpdt_fim_vigencia; ?><!--Per�odo de Vig�ncia--></td>
                        <td class="esquerda"><?php echo $this->arrayTiposCampanha[ $item->cfcpcftpoid ] ?><!--Tipo de Camp. Prom.--></td>
                        <td class="esquerda"><?php echo $this->arrayMotivosCreditos[ $item->cfcpcfmccoid ] ?><!--Motivo do Cr�dito--></td>
                        <td class="esquerda"><?php echo $tipoDesconto; ?><!--Tipo do Desconto--></td>
                        <td class="direita" nowrap><?php echo !empty($item->cfcptipo_desconto) && $item->cfcptipo_desconto == 'P' ?  number_format($item->cfcpdesconto, '2', ',', '.').' %' : ''; ?><!--Percentual--></td>
                        <td class="direita" nowrap><?php echo !empty($item->cfcptipo_desconto) && $item->cfcptipo_desconto == 'V' ? 'R$ '.  number_format($item->cfcpdesconto, '2', ',', '.') : ''; ?><!--Valor--></td>
                        <td class="esquerda"><?php echo $formaAplica��o; ?><!--Forma de Aplica��o--></td>
                        <td class="direita"><?php echo $qtdParcelas; ?><!--Qtd. Parcelas--></td>
                        <td class="esquerda"><?php echo $item->obrobrigacao; ?><!--Obriga��o financeira--></td>
                        <td class="esquerda"><?php echo $item->nm_usuario; ?><!--Usu�rio--></td>
                        <td style="text-align: center;">
                            <a title="Editar" href="fin_credito_futuro_parametrizacao_campanha.php?acao=cadastro&id=<?php echo $item->cfcpoid ?>&campanha_id=<?php echo $item->cfcpcftpoid ?>">
                            <img class="icone" width="18" src="images/edit.png">    
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?> 
                
                <!--
                
					c.cfcpoid, 
					TO_CHAR(c.cfcpdt_inicio_vigencia,'DD/MM/YYYY') AS cfcpdt_inicio_vigencia,
					TO_CHAR(c.cfcpdt_fim_vigencia,'DD/MM/YYYY')    AS cfcpdt_fim_vigencia,
					c.cfcpcftpoid, 
					c.cfcpcfmccoid, 
					c.cfcpdesconto, 
					c.cfcptipo_desconto, 
					c.cfcpaplicacao, 
					c.cfcpqtde_parcelas, 
					c.cfcpobroid, 
					c.cfcpobservacao, 
					c.cfcpusuoid_exclusao, 
					c.cfcpdt_exclusao, 
					c.cfcpaplicar_sobre, 
					c.cfcpdt_inclusao, 
					c.cfcpusuoid_inclusao, 
					tc.cftpdescricao, 
					tc.cftpoid, 
					mc.cfmcdescricao, 
					mc.cfmcoid, 
					u.nm_usuario, 
					u.cd_usuario, 
					u.usuemail, 
					o.obrobrigacao, 
					o.obroid
                
                -->
                </tbody>
           </table>
    </div>
</div>
<div class="bloco_acoes">
<?php $s = count($this->parametroPesquisa->resultado) > 1 ? 's' : ''; ?>     
<p><?php echo count($this->parametroPesquisa->resultado); ?> registro<?php echo $s; ?> encontrado<?php echo $s; ?>.</p>
</div>
<?php elseif (isset($this->parametroPesquisa->resultado)): ?> 
<div class="mensagem alerta">Nenhum registro encontrado.</div>
<?php endif; ?>