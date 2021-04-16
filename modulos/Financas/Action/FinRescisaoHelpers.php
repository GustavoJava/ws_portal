<?php

/**
 * View helpers da FinRescis�o
 * Fun��es utilizadas na exibi��o de dados
 */
 
/**
 * Formata um valor num�rico em formato monet�rio (999.999,99)
 * @param   float|int   $value
 * @return  string
 */
function toMoney($value)
{
    ob_start();
    echo (float) $value;
    ob_clean();    
    
    return number_format((float) $value, 2, ',', '.');
}