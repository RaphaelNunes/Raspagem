<html>

    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    </head>
    
    <body>
        <?php
        //importa a biblioteca de raspagem
            require './simple_html_dom.php';
            include  './upgrade.database.php';
            
            //url de que sera feita a raspagem
            $url = "http://divulgacand2012.tse.jus.br/divulgacand2012/ResumoCandidaturas.action";

            //carrega a pagina na variavel $html
            $html = new simple_html_dom();
            $html->load_file($url);
            
            $cont = 0;
            //Pega as tags que possui informações sobre cada estado
            $estados = $html->find("area[shape=poly]");
            
                foreach ($estados as $estado) {
                    
                    if($cont == 1){
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
                    $html->load_file($urlEstado);
                    
                    //pega a tag img que possui a chamada ao java script que exibe os prefeitos ou vereadores de uma cidade
                    $cidades = $html->find("tr[class=odd gradeX] img");
                    
                    $cont2 =0;
                    //(url prefeito cid 1,url vereador cid 1, url prefeito cid 2, url vereador cid 2 ,...)
                    foreach ($cidades as $cidade) {
                        //if para pegar apenas os prefeitos da primeira cidade
                        if ($cont2 <= 0) {

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
                            $html->load_file($urlAjaxPrefeitoVereador);

                            //pega os input com o id e com a ultima atualização do politico
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
                                //monta a url que leva aos dados de cada candidato
                                $urlDadosCandidato = "http://divulgacand2012.tse.jus.br/divulgacand2012/mostrarFichaCandidato.action?sqCandidato=" . $array['sqlCandidato'][$i] . "&codigoMunicipio=" . $codigoMunicipio . "&dtUltimaAtualizacao=" . $array['dtUltimaAtualizacao'][$i];
                                raspaDados($urlDadosCandidato , $codigoMunicipio);
                            }
                       }
                       $cont2++;
                    }
                }
                
                $cont++;
                    
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
                                
                                echo $urlDadosCandidato.'<br/>';
                                
                                //pega a tabela com os dados do prefeito
                                $tabelaDados = $html->find("table", 2);
                                
                                //pega a linha que possui o cargo e a cidade que ele está se candidatando
                                $titulo = $tabelaDados->find("td",0);
                                
                                // pega o conteudo da tag
                                $cargoCidade = $titulo->plaintext;
                                
                                //tira os espaços e coloca espaço ;P
                                $cargoCidade = str_replace('&nbsp;',' ', $cargoCidade);
                                
                                $cargoCidade = trim($cargoCidade);
                                $cargoCidade = iconv("ISO-8859-1", "UTF-8", $cargoCidade);
                                $cargoCidade = html_entity_decode($cargoCidade);
                                
                                //arruma os dados
                                $cargoCidade = str_replace('Registro de Candidatura - ', '', $cargoCidade);
                                $cargoCidade = str_replace('(', '', $cargoCidade);
                                $cargoCidade = str_replace(')', '', $cargoCidade);
                                $cargoCidade = str_replace(' /', '', $cargoCidade);
                                
                                $pedaco = explode(" ", $cargoCidade);
                                
                                //array para guardar os dados
                                $dados = array();
                                
                                $dados["cargo"] = $pedaco[0];
                                //cidade em que o prefeito está se candidatando
                                $dados["cidade_uf"] = "";
                                $ind = 1;
                                for($ind; $ind < count($pedaco) - 1; $ind++){
                                    if($ind != count($pedaco) - 2)
                                        $dados["cidade_uf"] = $dados["cidade_uf"].$pedaco[$ind]." ";
                                    else
                                        $dados["cidade_uf"] = $dados["cidade_uf"].$pedaco[$ind];
                                }
                                //Estado da cidade em que o prefeito é candidato
                                $dados["estado_uf"] = $pedaco[count($pedaco) - 1];
                                
                                //variavel para contar a posição da tag td
                                $dtNumero = 0;
                                
                                /*busca todos os tds dentro tabela com os dados do prefeito e confere a informação de
                                cada um */
                                foreach($tabelaDados->find("td") as $td){
                                    
                                    //pega o conteudo do td
                                    $label = $td->plaintext;

                                    //passa de ISO para UTF-8
                                    $label = iconv("ISO-8859-1", "UTF-8", $label);
                                    //Tira o html encode
                                    $label = html_entity_decode($label);
                                    
                                    //caso exista um label no formulario que deve ser pego, pega seu output
                                    if(isset($formulario[$label])){
                                        
                                        if(strcmp($label, "Endereço do site do candidato:") == 0){
                                            $td2 = $tabelaDados->find("td", $dtNumero + 1);
                                            $output = $td2->find("a")->href;
                                        }
                                        else
                                            $output = $tabelaDados->find("td", $dtNumero + 1)->plaintext;
                                        
                                        $output = iconv("ISO-8859-1", "UTF-8", $output);
                                        $output = html_entity_decode($output);
                                        $output = trim($output);
                                        
                                        //separa a cidade e o estado de nascimento
                                        if(strcmp($label, "Naturalidade:") == 0){
                                           
                                            $output = str_replace(' /', '', $output);
                                            $output = trim($output);
                                            $cidadeEstadoNas = explode(" ", $output);
                                            
                                            $numPedacos = count($cidadeEstadoNas);
                                            
                                            $dados["estadoNascimento"] = $cidadeEstadoNas[$numPedacos - 1];
                                            
                                            $dados["cidadeNascimento"] = "";
                                            $ind = 0;
                                            for ($ind; $ind < $numPedacos - 1; $ind++) {
                                                if ($ind != count($pedaco) - 2)
                                                    $dados["cidadeNascimento"] = $dados["cidadeNascimento"] . $cidadeEstadoNas[$ind] . " ";
                                                else
                                                    $dados["cidadeNascimento"] = $dados["cidadeNascimento"] . $cidadeEstadoNas[$ind];
                                            }
                                            echo 'Cidade nascimento:'.$dados["cidadeNascimento"].'<br/>';
                                            echo 'Estado nascimento:'.$dados["estadoNascimento"].'<br/>';
                                        }
                                        else
                                        {
                                            $dados[$formulario[$label]] = $output;
                                        
                                        echo $label . "" . $dados[$formulario[$label]] . '<br/>';
                                        }
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
                                
                                foreach ($dados as $elemento3)
                                    echo $elemento3.'<br/>';
                                //salva os dados do politico
                                //politico($dados["nomeCompleto"], $dados["nomeUrna"], NULL, NULL, NULL, $dados["sexo"], NULL, $dados["dataNascimento"], $dados["estadoCivil"], $dados["ocupacao"], $dados["grauInstrucao"], $dados["nacionalidade"], NULL, NULL, NULL, NULL, $dados["enderecoSite"], NULL, NULL, NULL, $dados["partido"], NULL);
                                
                                //imprime os bens do prefeito ou vereador
                                $num = 0;
                                while($num < $numeroBens){
                                    echo $bens["DescricaoBem"][$num].'<br/>';
                                    echo $bens["TipoBem"][$num].'<br/>';
                                    echo $bens["ValorBem"][$num].'<br/>';
                                    echo '<br/>';
                                    $num++;
                                }
                                if(isset($vicePrefeito))
                                    raspaDados($urlVicePrefeito , $codigoMunicipio);
            }
?>
    </body>
    
</html>