	<div class="separador"></div>
	<div class="bloco_titulo"><?php echo "Obriga��es  Financeiras";?></div>
	<div class="bloco_conteudo">
		 <div class="listagem">
			<table>
			<?php if($listObrigacoes && count($listObrigacoes)): ?>
				<thead>
					<tr>
						<th style="text-align: center;">ID</th>
						<th style="text-align: center;"><?php echo "Obriga��es Financeira";?></th>
					</tr>
				</thead>
				<tbody>	
					
					<?php foreach ($listObrigacoes as $key => $obrigacao ): 
               				$class = $class == '' ? 'class="par"' : ''; ?>								
					<tr <?=$class?> style="cursor: pointer;" onclick="jQuery.fn.selecionaObr(<?=$obrigacao['obroid']?>,'<?=$obrigacao['obrobrigacao']?>','<?=$obrigacao['tipo_item']?>');jQuery.fn.valorUnitarioObr('<?=$obrigacao['obrvl_obrigacao']?>')">
						<td class="l_obroid link" title="Clique para usar esta obriga��o.">
							<a href="javascript:void(0)"><?=$obrigacao['obroid']?></a>
						</td>
						<td>
							<a href="javascript:void(0)"><?=$obrigacao['obrobrigacao']?></a>
						</td>
					</tr>
					<?php endforeach; ?>							
				</tbody>
				<tfoot>								
					<tr><td colspan="7" style="text-align: center;"><?=count($listObrigacoes)?> registro(s) encontrado(s)</td></tr>					
				</tfoot>	
			<?php else: ?>
			<thead><tr><th style="text-align: center;"><?php echo "Nenhuma Obriga��es encontrada.";?></th></tr></thead>
			<?php endif ?>
			</table>
		</div>
		
	</div>
	<div class="separador"></div>