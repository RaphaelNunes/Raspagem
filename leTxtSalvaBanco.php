<?php
    
include  './upgrade.database.php';
include './properties.php';
include './consultasSPARQL.php';


$arquivo = fopen('dadosTeste.txt', 'r');
while(!feof($arquivo)){
    $linha = fgets($arquivo);
    //Não pega a ultima linha do arquivo
    if($linha != ''){
        $dados = explode("\|de|/", $linha);
        foreach ($dados as $dado)
            if ($dado == "")
                $dado = NULL;
        
        salvaDadosAllegro($dados);
    }
}

fclose($arquivo);

function salvaDadosAllegro($dados){
    $nomePol = $dados[5];
    $dataNas = $dados[8];
    $cargo = $dados[2];
    $numProtocolo = $dados[21];
        
    //colocar o cargo e o protocolo
    if($nomePol != NULL && $dataNas != NULL && $cargo != NULL && $numProtocolo != NULL){
        echo "Cargo: ".$cargo."numPro: ".$numProtocolo."Nome: ".$nomePol.'</br>';
        $id = existePoli($nomePol, $dataNas);
        $anosEleicoes = NULL;
        if($id != 0){
            $consulta ='select ?ano
                    where{
                     <http://ligadonospoliticos.com.br/politico/'.$id.'> polbr:election ?election.
                     ?election timeline:atYear ?ano                                                    
                    }
                    group by ?ano';
            $anosEleicoes = consultaSPARQL($consulta);
        }
        
        $naoConcorreuDepois2012 = TRUE;
        if($anosEleicoes != NULL){
            foreach ($anosEleicoes as $ano)
                if(isset ($ano['ano']))
                    if($ano['ano'] > 2012)
                        $naoConcorreuDepois2012 = FALSE;
        }
        
       //confere se o candidato não possui site
       if(strcmp($dados[17], "NULL") == 0)
                $dados[17] = NULL;
       
        if($id == 0 || $naoConcorreuDepois2012){
            $id = politico_Prefeito_Vereador($dados[5], $dados[9], $dados[12], $dados[8], $dados[13], $dados[15], $dados[16], $dados[14], $dados[6], $dados[7], $dados[17], $dados[2], $dados[1], $dados[0], $dados[3], $dados[4]);
        }
        eleicao_Prefeito_Vereador($id, "2012", $dados[10], $dados[11], $dados[3], $dados[2], $dados[1], $dados[0], NULL, $dados[18], $dados[19], $dados[4], $dados[21], $dados[20], $dados[22]);
          
        $bensEleicao = $dados[23];
        //confere se o politico possui bens
        if(strcmp($bensEleicao, "\n") != 0){
            $bensEleicao = str_replace("\|db|/\n", "", $bensEleicao);
            $bens = explode("\|db|/", $bensEleicao);
            if(strcmp($bensEleicao, "\n") != 0){
                $i = 0;
                while ($i < count($bens)){
                    if($bens[$i] != "" && $bens[$i+1] != "" && $bens[$i+2] != "")
                        declaracao_bens($id, "2012", $bens[$i++], $bens[$i++], $bens[$i++]);
                    else
                        $i = $i+3;
                } 
            }
        }
    }
}

?>

