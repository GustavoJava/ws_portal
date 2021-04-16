<table width="100%" style="border: 1px solid;">
	<tr>
		<td> 
			<b>RELAT�RIO DE ATENDIMENTO SASCAR</b>
		</td>
	</tr>
	<tr>
		<td style="border-bottom: 1px solid #000000; padding: 5px;">     
			<span style="float: left; margin-top: 10px; font-weight: bold;">Aprovado:</span> <?php echo ($this->atendimento['aprovado'] == 't') ? 'SIM' : 'N�O'; ?>
		</td>
	</tr>
	<tr>
		<td style="border-bottom: 1px solid #000000; padding: 5px;">     
			<span style="float: left; margin-top: 10px; font-weight: bold;">DATA:</span> <?php echo $this->atendimento['data_atendimento'] ?>
		</td>
	</tr>
	<tr>
		<td style="border-bottom: 1px solid #000000; padding: 5px;">      
			<span style="float: left; margin-top: 10px; font-weight: bold;">HORA DO ACIONAMENTO:</span> <?php echo $this->atendimento['hora_acionamento'] ?>
		</td>
	</tr>
	<tr>
		<td style="border-bottom: 1px solid; padding: 5px;">            
			<span style="float: left; margin-top: 10px; font-weight: bold;">HORA CHEGADA LOCAL:</span> <?php echo $this->atendimento['hora_chegada'] ?>
		</td>
	</tr>
	<tr>
		<td style="border-bottom: 1px solid #000000; padding: 5px;">  
			<span style="float: left; margin-top: 10px; font-weight: bold;">HORA ENCERRAMENTO:</span> <?php echo $this->atendimento['hora_encerramento'] ?>
		</td>
	</tr>
	<tr>
		<td style="border-bottom: 0px solid #000000; padding: 5px;">            
			<span style="float: left; margin-top: 10px; font-weight: bold;">LOCAL DO ACIONAMENTO:</span><br />
            CEP: <?php echo utf8_decode($this->atendimento['cep']) ?> <br />
			Endere�o: <?php echo utf8_decode($this->atendimento['logradouro']) ?>,  <?php echo $this->atendimento['end_numero'] ?><br />
			Bairro: <?php echo utf8_decode($this->atendimento['bairro']) ?> <br />
			Zona: <?php echo $this->atendimento['zona'] ?> <br />
			Cidade: <?php echo utf8_decode($this->atendimento['cidade']) ?> <br />
			UF: <?php echo $this->atendimento['uf'] ?> <br />
		</td>
	</tr>
	<tr>
		<td  style="border-bottom: 1px solid #000000; padding: 5px;">
			<span style="float: left; margin-top: 10px; font-weight: bold;">LATITUDE:</span> <?php echo $this->atendimento['latitude'] ?>
			<span style="float: left; margin-top: 10px; font-weight: bold; margin-left: 15px;">LONGITUDE:</span> <?php echo $this->atendimento['longitude'] ?>
		</td>
	</tr>
	<tr>
		<td style="border-bottom: 1px solid #000000; padding: 5px;">    
			<span style="float: left; margin-top: 10px; font-weight: bold;">OPERADOR SASCAR:</span> <?php echo $this->atendimento['nome_operador'] ?>
		</td>
	</tr>
	<tr>
		<td  style="border-bottom: 1px solid #000000; padding: 5px;">   
			<span style="float: left; margin-top: 10px; font-weight: bold;">TIPO DE OCORR�NCIA:</span> <br />
			<table>
				<tr>
					<td>
						<?php echo ($this->atendimento['tipo'] == 0) ? '<b>[X]</b>' : '[&nbsp;&nbsp;]'; ?> Cerca
						
						<?php echo ($this->atendimento['tipo'] == 1) ? '<b>[X]</b>' : '[&nbsp;&nbsp;]'; ?> Roubo
						
						<?php echo ($this->atendimento['tipo'] == 2) ? '<b>[X]</b>' : '[&nbsp;&nbsp;]'; ?> Furto
						
						<?php echo ($this->atendimento['tipo'] == 3) ? '<b>[X]</b>' : '[&nbsp;&nbsp;]'; ?> Suspeita
						
						<?php echo ($this->atendimento['tipo'] == 4) ? '<b>[X]</b>' : '[&nbsp;&nbsp;]'; ?> Sequestro
					</td>
				</tr>
			</table>
			<br />
		</td>
	</tr>
	<tr>
		<td style="border-bottom: 1px solid #000000; padding: 5px;">  
			<br />
			<?php echo ($this->atendimento['recuperado'] == 't') ? '<b>[X]</b>' : '[&nbsp;&nbsp;]'; ?> Recuperado
			<?php echo ($this->atendimento['recuperado'] == 'f') ? '<b>[X]</b>' : '[&nbsp;&nbsp;]'; ?> N�o recuperado
			<br />
		</td>
	</tr>
	<tr>
		<td  style="border-bottom: 1px solid #000000; padding: 5px;">    
			<span style="float: left; margin-top: 10px; font-weight: bold;">CLIENTE :</span> <?php echo utf8_decode($this->atendimento['cliente']) ?>
		</td>
	</tr>
	<tr>
		<td  style="border-bottom: 1px solid #000000; padding: 5px;">    
			<span style=" float: left; margin-top: 10px; font-weight: bold;">VE�CULO:</span> <br />
			<table width="100%">
				<tr>
					<td>            
						Placa: <?php echo $this->atendimento['veiculo_placa'] ?> <br />
						Cor: <?php echo $this->atendimento['veiculo_cor'] ?> <br />
						Ano: <?php echo $this->atendimento['veiculo_ano'] ?> <br />
                        Marca: <?php echo $this->atendimento['veiculo_marca'] ?> <br />
						Modelo: <?php echo $this->atendimento['veiculo_modelo'] ?> <br />
					</td>
				</tr>
			</table>
			<br />
		</td>
	</tr>
	<tr>
		<td  style="border-bottom: 1px solid #000000; padding: 5px;">
			<span style=" float: left; margin-top: 10px; font-weight: bold;">CARRETA:</span> <br />
			<table width="100%">
				<tr>
					<td>            
						Placa: <?php echo $this->atendimento['carreta_placa'] ?> <br />
						Cor: <?php echo $this->atendimento['carreta_cor'] ?> <br />
						Ano: <?php echo $this->atendimento['carreta_ano'] ?> <br />
                        Marca: <?php echo $this->atendimento['carreta_marca'] ?> <br />
						Modelo: <?php echo $this->atendimento['carreta_modelo'] ?> <br />
						Carga: <?php echo utf8_decode($this->atendimento['carreta_carga']) ?> <br />
                    </td>
				</tr>
			</table>
			<br />
		</td>
	</tr>
	<tr>
		<td  style="border-bottom: 1px solid #000000; padding: 5px;">    
			<span style="float: left; margin-top: 10px; font-weight: bold;">AGENTE DE APOIO:</span><br />
			<table width="100%">
				<tr>
					<td>            
						Placa do ve�culo utilizado nas buscas: <?php echo $this->atendimento['placa_busca'] ?>
					</td>
				</tr>
			</table>
			<br />
		</td>
	</tr>
	<tr>
		<td style="border-bottom: 1px solid #000000; padding: 5px;">    
			<span style="float: left; margin-top: 10px; font-weight: bold;">DESCRI��O DA OCORR�NCIA:</span><br /><br />
			&nbsp;<?php echo $this->atendimento['descricao'] ?>
		<br />
		</td>
	</tr>
	<tr>
		<td style="border-bottom: 1px solid #000000; padding: 5px;">            
			<span style="float: left; margin-top: 10px; font-weight: bold;">ENDERE�O DA RECUPERA��O:</span><br />
            CEP: <?php echo utf8_decode($this->atendimento['cep_recup']) ?> <br />
			Endere�o: <?php echo utf8_decode($this->atendimento['logradouro_recup']) ?>, <?php echo $this->atendimento['numero_recup'] ?> <br />
			Bairro: <?php echo utf8_decode($this->atendimento['bairro_recup']) ?> <br />
			Zona: <?php echo $this->atendimento['zona_recup'] ?> <br />
			Cidade: <?php echo utf8_decode($this->atendimento['cidade_recup']) ?> <br />
			UF: <?php echo $this->atendimento['uf_recup'] ?> <br />
		</td>
	</tr>
	<tr>
		<td style="border-bottom: 1px solid #000000; padding: 5px;">    
			<span style="float: left; margin-top: 10px; font-weight: bold;">DESTINA��O DO VE�CULO P�S RECUPERA��O:</span> <?php echo utf8_decode($this->atendimento['destino_veiculo']) ?>
		</td>
	</tr>
	<tr>
        <td>    
            <span style="float: left; margin-top: 10px; font-weight: bold;">LAUDO FOTOGR�FICO:</span>

            <?php $tipo_anterior = "";
            foreach($this->anexos as $anexo){
                echo ($tipo_anterior == $anexo['tipo_arquivo']) ? '<br />' : '<br /><br />', $anexo['nome_arquivo'], ' - ', $anexo['usuario'];

                $tipo_anterior = $anexo['tipo_arquivo'];
            }
            ?>
        </td>
     </tr>

</table>  
		