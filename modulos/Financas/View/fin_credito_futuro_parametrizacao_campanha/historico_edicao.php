<div class="separador"></div>
<?php if(count($this->parametroCadastro->historico) > 0): ?>
<div class="bloco_titulo">Resultado do Hist�rico</div>
<div class="bloco_conteudo">
    <div class="listagem">
        	<table>
                <thead>
                        <tr>
                            <th class="centro">Cadastro</th>
                            <th class="esquerda">Tipo</th>
                            <th class="esquerda">Observa��o</th>
                            <th class="esquerda">Usu�rio</th>
                        </tr>
                </thead>
                <tbody>
                <?php
                $f=1; 
                foreach ($this->parametroCadastro->historico as $item) : ?>
                    <?php $class = $class == 'par' ? '' : 'par'; ?>
                    <tr class="<?php echo $class ?>">
                    	<td class="centro" > <?php echo $item->cfchdt_registro; ?></td>
                        <td class="esquerda"><?php echo ( $f == count($this->parametroCadastro->historico) ? "Inclus�o" : "Altera��o" ); ?></td>
                        <td class="esquerda"><?php echo $item->cfchobservacao; ?></td>
                        <td class="esquerda"><?php echo $item->nm_usuario ?></td>
                    </tr>
                <?php 
                $f++;
                endforeach; ?>
                </tbody>
           </table>
    </div>
</div>
<?php endif; ?>
