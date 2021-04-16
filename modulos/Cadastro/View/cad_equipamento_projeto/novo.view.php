<?php
/**
 * @author  Emanuel Pires Ferreira
 * @email   epferreira@brq.com
 * @since   24/01/2013
 */
?>
<tr>
    <td align="center">
        <form name="novoEquipamentoProjeto" id="novoEquipamentoProjeto" method="post" action="">
            <input type="hidden" name="eproid" id="eproid" value="" />
            <table class="tableMoldura dados_pesquisa">
                <tr class="tableSubTitulo">
                    <td colspan="4"><h2>Dados Principais</h2></td>
                </tr>
                <tr class="linha_vazia"></tr>
                <tr>
                    <td width="20%"><label for="eprnome">* Projeto:</label></td>
                    <td>
                        <input name="eprnome" id="eprnome" style="width: 350px;" />
                    </td>
                </tr>
                <tr>
                    <td><label for="eprmotivo">Motivo do Projeto:</label></td>
                    <td colspan="3">
                        <textarea name="eprmotivo" id="eprmotivo" style="width: 350px;"></textarea>
                    </td>
                </tr>
                <tr>
                    <td><label for="eprdescricao_tecnica">Descri��o T�cnica:</label></td>
                    <td colspan="3">
                        <textarea name="eprdescricao_tecnica" id="eprdescricao_tecnica" style="width: 350px;"></textarea>
                    </td>
                </tr>
                <tr>
                    <td><label for="nomedepara">Produto:</label></td>
                    <td colspan="3">
                        <input type="text" name="eprprdoid" id="eprprdoid" size="10" OnKeyUp="formatar(this,'@')" OnBlur="revalidar(this,'@');" />
                        <input type="text" name="nomedepara" id="nomedepara" size="50" />
                        <input type="button" name="btPesquisaProduto" id="btPesquisaProduto" class="botao" value="Pesquisar" style="width:70px;" />
                        <img align="absmiddle" onclick="mostrarHelpComment(this,'Digite ao menos tr�s caracteres e clique em pesquisar para buscar os produtos.','D' , '');" onmouseout="document.body.style.cursor='default';" onmouseover="document.body.style.cursor='pointer';" src="images/help10.gif"> 
                        
                        <div id="div_img_pesquisa_produto" style="display:none;">
                            <img src="images/progress.gif">
                        </div>                                                      
                        
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td>
                        <div id="result_pesq_produto"></div>
                    </td>
                </tr>
                <tr>
                    <td><label for="eprcompativel_jamming">* Compat�vel Jamming:</label></td>
                    <td colspan="3">
                        <select name="eprcompativel_jamming" id="eprcompativel_jamming">
                            <option value="">Selecione</option>
                            <option value="t">Sim</option>
                            <option value="f">N�o</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label for="eprquadriband">* Quadriband:</label></td>
                    <td colspan="3">
                        <select name="eprquadriband" id="eprquadriband">
                            <option value="">Selecione</option>
                            <option value="t">Sim</option>
                            <option value="f">N�o</option>
                        </select>
                    </td>
                </tr>
               <tr class="linha_vazia"></tr>
                <tr class="tableSubTitulo">
                    <td colspan="4"><h2>Configura��es Portal:</h2></td>
                </tr>
                <tr class="linha_vazia"></tr>
                <tr>
                    <td><label for="eprteste_portal">* Executa Testes no Portal:</label></td>
                    <td colspan="3">
                        <select name="eprteste_portal" id="eprteste_portal">
                            <option value="">Selecione</option>
                            <option value="t">Sim</option>
                            <option value="f">N�o</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label for="eprtipo">* Tipo Portal:</label></td>
                    <td colspan="3">
                        <select name="eprtipo" id="eprtipo" style="width:100px;">
                            <option value="">Selecione</option>
                            <option value="CG">Carga</option>
                            <option value="CO">Casco</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label for="eprprecisao_odometro_portal">Precis�o Od�metro:</label></td>
                    <td colspan="3">
                        <input name="eprprecisao_odometro_portal" id="eprprecisao_odometro_portal" style="width: 100px;" />
                        <span style="color:red; font-size:10px;">* Quantidade de casas decimais a ser usadas no Portal para o usu�rio informar o KM do Od�metro</span>
                    </td>
                </tr>
                <tr>
                    <td><label for="eprmultiplicador_odometro_posicao">Multiplicador Od�metro:</label></td>
                    <td colspan="3">
                        <input name="eprmultiplicador_odometro_posicao" id="eprmultiplicador_odometro_posicao" style="width: 100px;" />
                        <span style="color:red; font-size:10px;">* Multiplicador a ser usado na convers�o do KM retornado pelo Equipamento</span>
                    </td>
                </tr>
                <tr>
                    <td><label for="eprtolerancia_odometro">Toler�ncia Od�metro:</label></td>
                    <td colspan="3">
                        <input name="eprtolerancia_odometro" id="eprtolerancia_odometro" style="width: 100px;" />
                        <span style="color:red; font-size:10px;">* Toler�ncia do Od�metro a ser usado no Teste de Od�metro final. Informar a toler�ncia conforme retorno do equipamento (KM, metros, etc)</span>
                    </td>
                </tr>
                <tr>
                    <td><label for="eprqtd_testes_posicao">* Quantidade Testes Posi��o:</label></td>
                    <td colspan="3">
                        <input name="eprqtd_testes_posicao" id="eprqtd_testes_posicao" style="width: 100px;" />
                    </td>
                </tr>
                <tr>
                    <td><label for="eprintervalo_testes_posicao">* Intervalo entre Testes(Segundos):</label></td>
                    <td colspan="3">
                        <input name="eprintervalo_testes_posicao" id="eprintervalo_testes_posicao" style="width: 100px;" />
                    </td>
                </tr>
                <tr>
                    <td><label for="eprtipo">Origem Informa��es Tela Resumo:</label></td>
                    <td colspan="3">
                        <select name="eprresumo_configuracoes" id="eprresumo_configuracoes">
                            <option value="">Selecione</option>
                            <option value="E">Setup Equipamento</option>
                            <option value="C">Contrato Intranet</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label for="eprorigem_ultima_posicao">* Origem �ltima Posi��o:</label></td>
                    <td colspan="3">
                        <select name="eprorigem_ultima_posicao" id="eprorigem_ultima_posicao">
                            <option value="">Selecione</option>
                            <option value="B">Bin�rio</option>
                            <option value="O">Oracle</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label for="eprtempo_posicao_teste">Tempo Posicionamento Teste:</label></td>
                    <td colspan="3">
                        <input name="eprtempo_posicao_teste" id="eprtempo_posicao_teste" style="width: 100px;" />
                        <span style="color:red; font-size:10px;">* Tempo de Posicionamento do Equipamento a ser configurado durante per�odo de Testes</span>
                    </td>
                </tr>
                <tr>
                    <td><label for="eprtempo_posicao_final">Tempo Posicionamento Final:</label></td>
                    <td colspan="3">
                        <input name="eprtempo_posicao_final" id="eprtempo_posicao_final" style="width: 100px;" />
                        <span style="color:red; font-size:10px;">* Tempo de Posicionamento do Equipamento a ser configurado ap�s a finaliza��o dos Testes (Teste de Configura��o de Tempo)</span>
                    </td>
                </tr>
                <tr>
                    <td><label for="eprtempo_expiracao_bloqueio">Tempo de Expira��o Bloqueio:</label></td>
                    <td colspan="3">
                        <input name="eprtempo_expiracao_bloqueio" id="eprtempo_expiracao_bloqueio" maxlength="4" style="width: 100px;" />
                        <span style="color:red; font-size:10px;">* Tempo de Expira��o Bloqueio do comando de Bloqueio enviado pelo portal (minutos)</span>
                    </td>
                </tr>
                <tr>
                    <td><label for="eprvalor_ajuste_rpm">Ajuste da Marcha Lenta:</label></td>
                    <td colspan="3">
                        <input name="eprvalor_ajuste_rpm" id="eprvalor_ajuste_rpm" maxlength="6" style="width: 100px;" />
                        <span style="color:red; font-size:10px;"> Valor para ajuste de RPM M�ximo ou RPM M�nimo.</span>
                    </td>
                </tr>
                <tr>
                    <td><label for="egtgrupo">* Grupo Tecnologia:</label></td>
                    <td colspan="3">
                        <select name="egtgrupo" id="egtgrupo"  style="width: 350px;">
                            <option value="">Selecione</option>
                            <?php foreach($view as $dado): ?>
                            <option value="<?php echo $dado->egtoid; ?>"><?php echo $dado->egtgrupo;  ?></option>
                            <?php endForeach; ?>
                        </select>
                        <button id="btn_grupo_tecnologia" class="botao botao_pad"> Grupo Tecnologia </button>
                    </td>
                </tr>
                 <tr class="linha_vazia"></tr>
                <tr class="tableRodapeModelo1" style="height:23px;">
                    <td align="center" colspan="4">
                        <input type="button" name="bt_salvar" id="bt_salvar" value="Salvar" class="botao" onclick="jQuery('#novoEquipamentoProjeto').submit();" style="width:70px;">
                        <input type="button" name="bt_voltar" id="bt_voltar" value="Voltar" class="botao" onclick="window.location.href='cad_equipamento_projeto.php';" style="width:70px;">
                    </td>
                </tr>  
            </table>

            <?php require_once _MODULEDIR_ . 'Cadastro/View/cad_equipamento_projeto/_grupoTecnologia.php'; ?>
        </form>
    </td>
</tr>
