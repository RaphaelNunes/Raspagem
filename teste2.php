<?php
    $palavra = "Registro de Candidatura - Vereador (ÁGUA BRANCA / AL)";
    
    $palavra = "Minas  Gerais";
    $palavras = explode(" ", $palavra);
    echo $palavras[2];
    
    
    //pegaCargoCidade($palavra);
    
    function pegaCargoCidade($cargoCidade){
                $cargoCidade = trim($cargoCidade);
                $cargoCidade = iconv("ISO-8859-1", "UTF-8", $cargoCidade);
                $cargoCidade = html_entity_decode($cargoCidade);
                
                $cargoCidade = str_replace('Registro de Candidatura - ', '', $cargoCidade);
                $cargoCidade = str_replace('(', '', $cargoCidade);
                $cargoCidade = str_replace(')', '', $cargoCidade);
                $cargoCidade = str_replace('/ ', '', $cargoCidade);
                
                
                
                echo $cargoCidade.'<br/>';
                
                $contaEspaco = 0;
                $cargo = "";  
                $ind = 0;
                
                for($ind ; $ind < strlen($cargoCidade);$ind++){
                    if($cargoCidade[$ind] == " ")
                        $contaEspaco++;
                    if($contaEspaco == 0)
                        $cargo = $cargo.$cargoCidade[$ind];
                }
                
                echo $cargo;
    }
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

?>