<? if($acao == 'pesquisar'){ 
    $cont = 0;
    
    if($resultado['pesquisa'])
    {
        $cont = count($resultado['pesquisa']);
				    $cor = 'par';