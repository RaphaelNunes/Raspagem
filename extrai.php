<html>

    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    </head>
    
    <body>
        <?php
        
            //importa a biblioteca de raspagem
            require './simple_html_dom.php';
            include  './upgrade.database.php';
            
            //variaveis para controlar a raspagem
            $prox_estado = 0;
            $prox_cidade = 0;
            $prox_cand = 0;
     
            $arquivo = fopen("control.txt", "r");
            $linha =  fgets($arquivo);
            
            //raspagem está começando do inicio
            if($linha == ''){
                echo "Iniciando Raspagem do zero";
                fclose($arquivo);
                $arquivo = fopen("control.txt", "w");
                fwrite($arquivo, "0 0 0"); 
            }
            
            else{
                $estado_cidade = explode(" ", $linha);
                $prox_estado = $estado_cidade[0];
                $prox_cidade = $estado_cidade[1];
                $prox_cand =$estado_cidade[2];
                echo 'Reiniciado a raspagem do Estado:'.$prox_estado.' e Cidade:'.$prox_cidade.'</br>';
            }
            
            fclose($arquivo);
            //url de que sera feita a raspagem
            $url = "http://divulgacand2012.tse.jus.br/divulgacand2012/ResumoCandidaturas.action";

            //carrega a pagina na variavel $html
            $html = file_get_html($url);

            //Pega as tags que possui informações sobre cada estado
            $estados = $html->find("area[shape=poly]");

            $contEstado = 0;
            $contCidade = 0;
            foreach ($estados as $estado) {

                //Usar apenas o estado de alagoas
                if ($contEstado >= $prox_estado) {

                    //pega a url que leva ao estado
                    $urlEstado = $estado->href;
                    
                    //pega a sigla do estado
                    $siglaUF = substr($urlEstado, strlen($urlEstado) - 2);
                    
                    //arruma a url do acre
                    if(strcmp($siglaUF, "e;") == 0)
                    {   //tira palavras a mais na url do Acre
                        $urlEstado = str_replace("'); return false;", '', $urlEstado);
                        $siglaUF = substr($urlEstado, strlen($urlEstado) - 2);
                    }

                    //junta a url principal com a url que leva ao estado
                    $urlEstado = substr_replace($url, $urlEstado, 50);

                    $html->clear();

                    //carrega a pagina do estado atual na variavel $html
                    $html = file_get_html($urlEstado);

                    //pega a tag img que possui a chamada ao java script que exibe os prefeitos ou vereadores de uma cidade
                    $cidades = $html->find("tr[class=odd gradeX] img");
                    
                    foreach ($cidades as $cidade) {
                        
                        if ($contCidade >= $prox_cidade) {

                            //limpa os elementos do onclick deixando apenas o id do cargo(11 ou 13) e da Cidade 
                            $idCidPoli = str_replace('onPesquisaClick(this, ', '', $cidade->onclick);
                            $idCidPoli = str_replace(',', '', $idCidPoli);
                            $idCidPoli = str_replace('"', '', $idCidPoli);
                            $idCidPoli = str_replace('\'', '', $idCidPoli);
                            $idCidPoli = str_replace(');', '', $idCidPoli);

                            //guarda a posição do espaço que separa o id do prefeito ou vereador do id do municipio
                            $espaco = strripos($idCidPoli, ' ');

                            //guarda o id do prefeito ou do vereador que é 11 ou 13
                            $codigoCargo = substr($idCidPoli, 0, $espaco);

                            //guarda o id da cidade
                            $codigoMunicipio = substr($idCidPoli, $espaco + 1);

                            //modifica a url do ajax que é exibida na tela 
                            $urlAjaxPrefeitoVereador = "http://divulgacand2012.tse.jus.br/divulgacand2012/pesquisarCandidato.action?siglaUFSelecionada=" . $siglaUF . "&codigoMunicipio=" . $codigoMunicipio . "&codigoCargo=" . $codigoCargo . "&codigoSituacao=0";

                            $html->clear();

                            //carrega o html com todos prefeitos||vereadores vereadores da cidade
                            $html = file_get_html($urlAjaxPrefeitoVereador);

                            //pega os input com o id e a ultima atualização do politico
                            $candidato = $html->find("tr[class=odd gradeX] input");

                            //array para guardar os id dos candidatos e id da ultima atualização da cidade
                            $array = array("sqCandidato", "dtUltimaAtualizacao");

                            $i = 0;
                            $j = 0;
                            foreach ($candidato as $elemento2) {
                                if (strcmp($elemento2->name, "sqCandidato") == 0) {
                                    $array["sqlCandidato"][$i] = $elemento2->value;
                                    $i++;
                                } else {
                                    $array["dtUltimaAtualizacao"][$j] = $elemento2->value;
                                    $j++;
                                }
                            }

                            $i = 0;
                            //pega os dados(id prefeito||vereador e id ult atualização) de cada prefeito||vereador da cidade
                            for ($i; $i < $j; $i++) {
                                if($i >= $prox_cand){
                                    //monta a url que leva aos dados de cada candidato
                                $urlDadosCandidato = "http://divulgacand2012.tse.jus.br/divulgacand2012/mostrarFichaCandidato.action?sqCandidato=" . $array['sqlCandidato'][$i] . "&codigoMunicipio=" . $codigoMunicipio . "&dtUltimaAtualizacao=" . $array['dtUltimaAtualizacao'][$i];

                                raspaDados($urlDadosCandidato , $codigoMunicipio);
                                
                                $arquivo = fopen("control.txt", "w+");    
                                fwrite($arquivo, $contEstado." ".$contCidade." ".($i + 1));
                                fclose($arquivo);  
                                
                                }
                            }
                        $prox_cand = 0;
                        $arquivo = fopen("control.txt", "w+");    
                        fwrite($arquivo, $contEstado." ".($contCidade+1)." 0");
                        fclose($arquivo);    
                       }
                        $contCidade++;
                    } 
                    $prox_cidade = 0;
                    $arquivo = fopen("control.txt", "w+");
                    fwrite($arquivo, ($contEstado+1)." 0 0");
                    fclose($arquivo);
                }
                
                
                $contEstado++;
            }
            
            
            
            
            
            
            
            
            function raspaDados($urlDadosCandidato , $codigoMunicipio){
                                
                                $html = file_get_html($urlDadosCandidato);

                                $formulario = array();
                                $formulario["Nome para urna eletrônica:"] = "nomeUrna";
                                $formulario["Número:"] = "numero";
                                $formulario["Nome completo:"] = "nomeCompleto";
                                $formulario["Sexo:"] = "sexo";
                                $formulario["Data de nascimento:"] = "dataNascimento";
                                $formulario["Estado civil:"] = "estadoCivil";
                                $formulario["Nacionalidade:"] = "nacionalidade";
                                $formulario["Naturalidade:"] = "naturalidade";
                                $formulario["Grau de instrução:"] = "grauInstrucao";
                                $formulario["Ocupação:"] = "ocupacao";
                                $formulario["Endereço do site do candidato:"] = "enderecoSite";
                                $formulario["Partido:"] = "partido";
                                $formulario["Coligação:"] = "coligacao";
                                $formulario["Composição da coligação:"] = "composicaoColigacao";
                                $formulario["No. processo:"] = "nProcesso";
                                $formulario["No. protocolo:"] = "nProtocolo";
                                $formulario["CNPJ de campanha:"] = "cnpj";
                                $formulario["Limite de gastos:"] = "limiteGasto";
                                $formulario["Endereço do site do candidato:"] = "endSite";


                                //pega a tabela com os dados do prefeito
                                $tabelaDados = $html->find("table", 2);

                                //echo $urlDadosCandidato.'<br/>';
                                //variavel para contar a posição da tag td
                                $dtNumero = 0;
                                
                                //array para guardar os dados
                                $dados = array();
                                
                                /*busca todos os tds dentro tabela com os dados do prefeito e confere a informação de
                                cada um */
                                foreach($tabelaDados->find("td") as $td){
                                    
                                    //pega o conteudo do td
                                    $label = $td->plaintext;

                                    //passa de ISO para UTF-8
                                    $label = iconv("ISO-8859-1", "UTF-8", $label);
                                    //Tira o html encode
                                    $label = html_entity_decode($label);
                                    
                                    //caso exista um label no formulario que deve ser pego, pega seu input
                                    if(isset($formulario[$label])){
                                        
                                        $output = $tabelaDados->find("td", $dtNumero + 1)->plaintext;
                                        $output = iconv("ISO-8859-1", "UTF-8", $output);
                                        $output = html_entity_decode($output);
                                        
                                        $dados[$formulario[$label]] = $output;
                                        
                                        //echo $label . "" . $dados[$formulario[$label]] . '<br/>';
                                    }
                                    else
                                        $dados[$formulario[$label]] = NULL;
                                    
                                    $dtNumero++;
                                }
                                
                                //array para guardas a descrição sobre o bem do candidato
                                $bens = array();
                                //variavel para contar os bens do candidato
                                $numeroBens = 0;
                                //retorna um array mesmo só existindo uma tabela
                                $tabelaBens = $html->find('table[id="bemCandidato"]');
                                foreach ($tabelaBens as $t1){
                                    $bens = $t1->find('tr[class="odd"] , tr[class="even"]');
                                    foreach ($bens as $t2){

                                        $palavra = $t2->find("td", 1)->plaintext;
                                        $palavra = iconv("ISO-8859-1", "UTF-8", $palavra);
                                        $palavra = html_entity_decode($palavra);
                                        $bens["DescricaoBem"][$numeroBens] = $palavra;

                                        $palavra = $t2->find("td", 2)->plaintext;
                                        $palavra = iconv("ISO-8859-1", "UTF-8", $palavra);
                                        $palavra = html_entity_decode($palavra);
                                        $bens["TipoBem"][$numeroBens] = $palavra;

                                        $palavra = $t2->find("td", 3)->plaintext;
                                        $palavra = iconv("ISO-8859-1", "UTF-8", $palavra);
                                        $palavra = html_entity_decode($palavra);
                                        $bens["ValorBem"][$numeroBens] = $palavra;

                                        $numeroBens++;
                                    }
                                }
                                
                                $vicePrefeito = $tabelaDados->find('img[src="img/icones/vice.png"]',0);
                                if(isset($vicePrefeito)){
                                    $vicePrefeito = str_replace('visualizarDadosVice(', '', $vicePrefeito->onclick);
                                    $vicePrefeito = str_replace(',', '', $vicePrefeito);
                                    $vicePrefeito = str_replace('"', '', $vicePrefeito);
                                    $vicePrefeito = str_replace('\'', '', $vicePrefeito);
                                    $vicePrefeito = str_replace(');', '', $vicePrefeito);
                                    
                                    $espaco = strripos($vicePrefeito, ' ');
                                    $codigoVice = substr($vicePrefeito, 0, $espaco);
                                    $codigoUltAtulizacao = substr($vicePrefeito, $espaco + 1);
                                    
                                    $urlVicePrefeito = "http://divulgacand2012.tse.jus.br/divulgacand2012/mostrarFichaCandidato.action?sqCandSuperior=".$codigoVice."&codigoMunicipio=".$codigoMunicipio."&dtUltimaAtualizacao=".$codigoUltAtulizacao;
 
                                }
                                $arq = fopen("dados.txt", "a+");
                                fwrite($arq, $dados["nomeCompleto"]."\n");
                                fclose($arq);
                                /*
                                foreach ($dados as $elemento3)
                                    echo $elemento3.'<br/>';
                                 */
                                //salva os dados do politico
                                //politico($dados["nomeCompleto"], $dados["nomeUrna"], NULL, NULL, NULL, $dados["sexo"], NULL, $dados["dataNascimento"], $dados["estadoCivil"], $dados["ocupacao"], $dados["grauInstrucao"], $dados["nacionalidade"], NULL, NULL, NULL, NULL, $dados["enderecoSite"], NULL, NULL, NULL, $dados["partido"], NULL);
                                
                                //imprime os bens do prefeito ou vereador
                                
                                /*
                                $num = 0;
                                while($num < $numeroBens){
                                    echo $bens["DescricaoBem"][$num].'<br/>';
                                    echo $bens["TipoBem"][$num].'<br/>';
                                    echo $bens["ValorBem"][$num].'<br/>';
                                    echo '<br/>';
                                    $num++;
                                }
                                 */
                                if(isset($vicePrefeito))
                                    raspaDados($urlVicePrefeito , $codigoMunicipio);
            }

?>
    </body>
    
</html>