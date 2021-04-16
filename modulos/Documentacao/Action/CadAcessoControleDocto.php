<?php

require _MODULEDIR_ . 'Documentacao/DAO/CadAcessoControleDoctoDAO.php';

class CadAcessoControleDocto {
    
    public function __construct() {
		$this->CadAcessoControleDocto = new CadAcessoControleDoctoDAO();
    }

	/**
	 * Grava permiss�o de acesso
	 * @param string $itoid - ID do documento
	 * @return type
     */
	public function confirmar($itoid){
		// Percorre documentos
		for ($i=0; $i<count($itoid); $i++){
			$itaitoid = $itoid[$i];
			
			// Visualiza��o
			if (is_array($_POST['ck_cgo_v'])){				
				$retorno = $this->confirmarVisualizar($itaitoid);
			}
			
			// Edi��o
			if (is_array($_POST['ck_cgo_e'])){				
				$retorno = $this->confirmarEditar($itaitoid);
			}

			$retorno['msg'] = ($retorno['msg'] == "") ? "J� existe(m) registro(s) para o(s) documento(s) e cargo(s) selecionados." : $retorno['msg'];
		}

		return $retorno;
	}
	
	/**
	 * Editar permiss�es de acesso
	 * @param string $itaoid - ID da permissao
	 * @param string $itatipo_acesso - (E)Editar ou (V)Visualizar
	 * @return type
     */
	public function editar($itaoid, $itatipo_acesso){
		
		// Atualiza as permiss�es dos cargos
		$sql_upd = $this->CadAcessoControleDocto->setUpdateAcessoCargo($itaoid, $itatipo_acesso);

        if (!$sql_upd){
			throw new exception ('Houve um erro ao alterar registro.');
		}
		
		// Confere permiss�es em vers�es pendentes para atualizar
		$acessoVersao = $this->CadAcessoControleDocto->getPermissaoVersao($itaoid);

		if($acessoVersao){
			$updateVersao = $this->CadAcessoControleDocto->updateVersaoPendente($acessoVersao['itoid'], $acessoVersao['itaprhoid'], $itatipo_acesso);
		}

		if (!$updateVersao) {
			throw new exception ('Houve um erro ao atualizar a vers�o "'.$acessoVersao['itversao'].'".');
		}

	}
	
	/**
	 * Excluir permiss�es de acesso
	 * @param string $itaoid - ID da permissao
	 * @return type
     */
	public function excluir($itaoid){
	
		// Carrega permiss�es da vers�o
		$acessoVersao = $this->CadAcessoControleDocto->getPermissaoVersao($itaoid);

		// Deletar permiss�o da vers�o
		if($acessoVersao){

			$excPermissao = $this->CadAcessoControleDocto->excluirPermissaoVersao($acessoVersao['itoid'], $acessoVersao['itaprhoid']);

			if (!$excPermissao) {
				throw new exception ('Houve um erro ao deletar a permiss�o da vers�o "'.$acessoVersao['itversao'].'".');
			}
		}

		// Deleta permiss�o
		$excluirPermissao = $this->CadAcessoControleDocto->excluirPermissao($itaoid);

        if (!$excluirPermissao) {
			throw new exception ('Houve um erro ao excluir registro.');
		}
	}

	
	// FUN��ES DE CONFIRMA��O 'Visualizar e Editar'
	// Permiss�es de acesso Visualizar
	private function confirmarVisualizar($itaitoid){
	
		// Percorre deptos
		foreach ($_POST['ck_cgo_v'] as $depto => $value) {				
			// Percorre cargos
			foreach ($_POST['ck_cgo_v'][$depto] as $chave => $itaprhoid) {
				
				//Busca registros para o documento e cargo selecionados
				$res_ita = $this->CadAcessoControleDocto->getRegistros($itaitoid, $itaprhoid, "V");

				// Se n�o houver registro, faz insert
				if(pg_num_rows($res_ita)==0){
					// Para cada documento insere o(s) cargo(s) selecionado(s)
					$InsAcessoCargo = $this->CadAcessoControleDocto->setInsertAcessoCargo($itaitoid, $itaprhoid, 'V');

					// Insere acessos para vers�es pendentes
					$InsAcessoVersao = $this->CadAcessoControleDocto->setInsertAcessoVersao($itaitoid, $itaprhoid, 'V');

					if($InsAcessoVersao){
						throw new exception ('Houve um erro ao inserir registro para vers�o "'.$InsAcessoVersao.'".');
					}
					
					if(!$InsAcessoCargo){
						throw new exception ('Houve um erro ao inserir registro.');
					}

					$retorno['msg'] = "Registro(s) inserido(s) com sucesso.";
				}
				// Se houver registro e o tipo de acesso foi alterado, faz update
				else{
					$itaoid 		= pg_fetch_result($res_ita,0,'itaoid');
					$itatipo_acesso = pg_fetch_result($res_ita,0,'itatipo_acesso');
					
					// Se o tipo do acesso for diferente de "V"isualiza��o faz update
					if($itatipo_acesso != 'V'){
						
						// Atualiza o tipo de acesso
						$UpdAcessoCargo = $this->CadAcessoControleDocto->setUpdateAcessoCargo($itaoid, 'V');
						
						// Atualiza o tipo de acesso da vers�o
						$UpdAcessoVersao = $this->CadAcessoControleDocto->setUpdateAcessoVersao($itaoid, 'V');
						
						if($UpdAcessoVersao){
							throw new exception ('Houve um erro ao alterar registro para vers�o "'.$UpdAcessoVersao.'".');
						}
						
						if(!$UpdAcessoCargo){
							throw new exception ('Houve um erro ao alterar registro.');
						}

						$retorno['msg'] = "Registro(s) inserido(s) com sucesso.";
					}
				}
			}
		}
		
		return $retorno;
	}
	
	// Permi��o de acessos Editar
	private function confirmarEditar($itaitoid){
		
		// Percorre deptos
		foreach ($_POST['ck_cgo_e'] as $depto => $value) {
			// Percorre cargos
			foreach ($_POST['ck_cgo_e'][$depto] as $chave => $itaprhoid) {
				
				$res_ita = $this->CadAcessoControleDocto->getRegistros($itaitoid, $itaprhoid,'E');

				// Se n�o houver registro, faz insert
				if(pg_num_rows($res_ita)==0){

					//Para cada documento insere o(s) cargo(s) selecionado(s)
					$InsAcessoCargo = $this->CadAcessoControleDocto->setInsertAcessoCargo($itaitoid, $itaprhoid, 'E');
					
					// Insere acessos para vers�es pendentes
					$InsAcessoVersao = $this->CadAcessoControleDocto->setInsertAcessoVersao($itaitoid, $itaprhoid, 'E');
					
					if($InsAcessoVersao){
						throw new exception ('Houve um erro ao inserir registro para vers�o "'.$InsAcessoVersao.'".');
					}
					
					if(!$InsAcessoCargo){
						throw new exception ('Houve um erro ao inserir registro.');
					}

					$retorno['msg'] = "Registro(s) inserido(s) com sucesso.";

				 // Se houver registro e o tipo de acesso foi alterado, faz update
				}else{
					$itaoid 		= pg_fetch_result($res_ita,0,'itaoid');
					$itatipo_acesso = pg_fetch_result($res_ita,0,'itatipo_acesso');

					// Se o tipo do acesso for diferente de "E"di��o faz update
					if($itatipo_acesso != 'E'){

						// Atualiza o tipo de acesso
						$UpdAcessoCargo = $this->CadAcessoControleDocto->setUpdateAcessoCargo($itaoid, 'E');
						
						// Atualiza o tipo de acesso da vers�o
						$UpdAcessoVersao = $this->CadAcessoControleDocto->setUpdateAcessoVersao($itaoid, 'E');
						
						if($UpdAcessoVersao){
							throw new exception ('Houve um erro ao alterar registro para vers�o "'.$UpdAcessoVersao.'".');
						}
						
						if(!$UpdAcessoCargo){
							throw new exception ('Houve um erro ao alterar registro.');
						}
						
						$retorno['msg'] = "Registro(s) inserido(s) com sucesso.";
					}
				}
			}
		}

		return $retorno;
	}
	
}