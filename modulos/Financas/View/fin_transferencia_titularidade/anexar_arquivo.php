<div class="mensagem alerta" id="msgalerta2" style="display: none;"></div>
<div class="modulo_titulo"><?php echo "Anexos Arquivos"; ?></div>
<div class="bloco_conteudo">
<div class="mensagem alerta" id="msgalertaaddarquivo" style="display: none;"></div>
	<form id="formularioAnexo" name="formularioAnexo" method="post" enctype="multipart/form-data">

	<table class="tableMoldura">

		<tr>
			<td ><label>Arquivo*:</label></td>
		</tr>
		<tr>
			<td><input type="file" value="nenhum arquivo selecionado" size="20" name="arqAnexoReqs" id="arqAnexoReqs" class="form_field"></td>
			<td>
		</tr>
		<Tr>
			<td><label><?php echo "Descri��o*";?></label></td>
		</tr>
		<tr>

			<td>
			
			<input type="text" name="arqAnexoReqDescricao" id="arqAnexoReqDescricao"  size="40" maxlength="100"></td>
			
		</tr>

		<tr>
			<td colspan="4"> 
		<?php if(trim($retorno['ptrastatus_conclusao_proposta']) != 'CA' && trim($retorno['ptrastatus_conclusao_proposta']) != 'C' && trim($retorno['ptrastatus_conclusao_proposta']) != 'F') {?>
			<input class="botao"
			type="button" value="Adicionar" name="bt_add_arquivos" id="bt_add_arquivos"/>
		<?php 	}?>
			</td>
		</tr>
		<tr>
			<td colspan="4">&nbsp;</td>
		</tr>
	</table>

	</form>
	
		<div class="listagem" id="listaAnexos"> </div>
	<div class="listagem" id="anexosProposta">
	<?php
	$resposta = $control->listaAnexosProposta($id);
	if(count($resposta) > 0 && !empty($resposta) || $resposta != null) {?>

<table>
	<thead>
	<tr>
				<th style='text-align: center';>Arquivo</th>
				<th style='text-align: center';>Descri��o</th>
				<th style='text-align: center';>Data</th>
				<th style='text-align: center';>Usu�rio</th>
					<th style="text-align: center;"><?php echo 'A��es';?></th>
				</tr>
			</thead>
			<tbody>
				<?php 
		
				$class = '';
				foreach ($resposta as $row) :
				$descricao = utf8_decode($row['ptadescricao']);
				$nome = utf8_decode($row['nm_usuario']);
				
				$class = $class == '' ? 'par' : '';
				?>
				<tr class="<?=$class?>">
					<input type="hidden" id="idAnexo" name="idAnexo" value="<?php echo $row[ptaoid];?>" />
					<input type="hidden" id="idpropAnexo" name="idpropAnexo" value="<?php echo $row[ptaptraoid];?>" />
					<td style="text-align: center;"><a title=Downloads target="_blank" href="download.php?arquivo=<?php echo _SITEDIR_ ."faturamento/transferencia_titularidade/".$row['ptanm_arquivo']; ?>"><?=utf8_decode($row['ptanm_arquivo'])?></a></td>
	                <td style="text-align: center;"><?=$descricao?></td>
	                <td style="text-align: center;"><?=$row['data']?></td>
					<td style="text-align: center;"><?=$nome?></td>
		
			
					<td class="acao centro">
					<?php if(trim($retorno['ptrastatus_conclusao_proposta']) != 'CA' && trim($retorno['ptrastatus_conclusao_proposta']) != 'C' && trim($retorno['ptrastatus_conclusao_proposta']) != 'F') {?>
			           <a title=Excluir rel="<?php  echo $row['ptanm_arquivo']; ?>" id="btn_excluir_arquivo" href="javascript:void(0);">
			           		<IMG class=icone alt=Excluir src="images/icon_error.png"></a>
		           	<?php 	}?>
					</td>


				</tr>

			
			    <?php endforeach;  ?>
			    
					<tfoot>
			<tr class='center'>
			<td align='center' colspan='5'>
			</td>
			</tr>
			</tfoot>
		</table>

			<?php  } ?>
			  
	</div>
	</div>
