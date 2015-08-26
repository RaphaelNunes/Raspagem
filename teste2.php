<?php
   
    /*
    include './simple_html_dom.php';
    $html = file_get_html('http://divulgacand2012.tse.jus.br/divulgacand2012/mostrarFichaCandidato.action?sqCandidato=20000000519&codigoMunicipio=27014&dtUltimaAtualizacao=20121025121033');
    $foto = $html->find("img",0);
    echo 'http://divulgacand2012.tse.jus.br/divulgacand2012/'.$foto->src;
    
    //$url = 'http://s3.amazonaws.savoir.com.br/cea.com.br/imagem/cadastrocqlv/imagem/cadastrocqlv-53440.jpg';
    $url = 'http://divulgacand2012.tse.jus.br/divulgacand2012/'.$foto->src;;
    $enderecoImg = "./imagem1/tucano.jpg";
    $image = file_get_contents($url);
    file_put_contents($enderecoImg, $image);
    fclose($enderecoImg);
     
     */

    include './properties.php';
    include "./consultasSPARQL.php";
    include './upgrade.database.php';
    
    //$nome = 
    
    $id = existePoli("JOSÉ RODRIGUES GOMES", "13/09/1953");
    
    echo $id.'</br>';
    
    //não existe politico no banco
    if($id == 0){
        //politico();
        //eleicao();
        //dadosPessoais();
    }
    //politco existe no banco
    else{
        //Politico já existe no banco
        if(count($consultaSparql) == 0){
            // chamar a função politico
            echo "polico ainda não existe no banco";
        }
        else
            "politico já existi no banco";
        //function election
        //function dados pessoais
    }
    
    
?>