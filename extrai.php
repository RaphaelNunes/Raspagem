<html>

    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <script>
            function recarregaPagina() {
                location.reload();
            }
        </script>
    </head>
    
    <body onload="recarregaPagina()">
        <?php
        
            //importa a biblioteca de raspagem
            require './simple_html_dom.php';
            include  './upgrade.database.php';
            include './properties.php';
            include "./consultasSPARQL.php";
            
            //variaveis para controlar a raspagem
            $prox_estado = 0;
            $prox_cidade = 0;
            $prox_cand = 0;
            //abre o arquivo de texto que fala da onde continuar a raspagem
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
                echo 'Reiniciado a raspagem do Estado:'.$prox_estado.' e Cidade:'.(($prox_cidade / 2) + 1).'</br>';
            }
            fclose($arquivo);
            
            //url de que sera feita a raspagem
            $url = "http://divulgacand2012.tse.jus.br/divulgacand2012/ResumoCandidaturas.action";

            //carrega a pagina na variavel $html
            $html = file_get_html($url);

            //Pega as tags que possui informações sobre cada estado
            $estados = $html->find('area[shape="poly"]');

            $contEstado = 0;
            $contCidade = 0;
            foreach ($estados as $estado) {

                //Usar apenas o estado de alagoas
                if ($contEstado == 2) {
                //if ($contEstado >= $prox_estado) {

                    //pega a url que leva ao estado
                    $urlEstado = $estado->href;
                    
                    //pega a sigla do estado
                    $siglaUF = substr($urlEstado, strlen($urlEstado) - 2);
                    
                    //URL do ACRE está com problema, caso seja ela o código abaixo arruma
                    if(strcmp($siglaUF, "e;") == 0)
                    {   //tira palavras a mais na url do Acre
                        $urlEstado = str_replace("'); return false;", '', $urlEstado);
                        $siglaUF = substr($urlEstado, strlen($urlEstado) - 2);
                    }

                    //junta a url principal com a url que leva ao estado
                    $urlEstado = substr_replace($url, $urlEstado, 50);

                    $html->clear();
                    unset($html);

                    //carrega a pagina do estado atual na variavel $html
                    $html = file_get_html($urlEstado);

                    //pega a tag img que possui a chamada ao java script que exibe os prefeitos ou vereadores de uma cidade
                    $cidades = $html->find('tr[class="odd gradeX"] img');
                    
                    
                    //$candidatos = $html->find('div[class="qtdCandidatos"] div');
                    //echo count($candidatos).'</br>';
                    /*
                    $qtdCandidatos = array();
                    $indice = 0;
                    foreach ($candidatos as $cand){
                        $qtdCandidatos[$indice] = limpaPalavra($cand->plaintext);
                        $indice++;
                    }
                     */
        
                    //embaralha $cidades shuffle
                    
                    
                    
                    //$qtPoliticos = $html->find('td[class=')
                    //cada cidade é contada duas vezes, um para o prefeito e outra para o vereador
                    foreach ($cidades as $cidade) {
                        //if ($contCidade < 1) {
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
                            $urlAjaxPrefeitoVereador = "http://divulgacand2012.tse.jus.br/divulgacand2012/pesquisarCandidato.action?siglaUFSelecionada=".$siglaUF."&codigoMunicipio=".$codigoMunicipio."&codigoCargo=".$codigoCargo."&codigoSituacao=0";
                            
                            $html->clear();
                            unset($html);
                            
                            //carrega o html que possui todos prefeitos ou vereadores da cidade
                            //$html;
                            //$qtdPoliticos = $qtdCandidatos[0]->plaintext;
                            //do{
                            $html = file_get_html($urlAjaxPrefeitoVereador);
                            //} while (count($html) != $qtdPoliticos);
                            //echo $qtdCandidatos[1].'</br>';
                            
                           // echo 'Numero de Politicos: '.$qtdPoliticos.' Cidade: '.$contCidade.'</br>';
                            
                            //pega os input com o id e a ultima atualização do politico
                            $candidato = $html->find('tr[class="odd gradeX"] input');

                            //array para guardar ids dos candidatos e ids da ultima atualização do candidato
                            $array = array("sqCandidato", "dtUltimaAtualizacao");

                            $i = 0;
                            $j = 0;
                            foreach ($candidato as $elemento) {
                                if (strcmp($elemento->name, "sqCandidato") == 0) {
                                    $array["sqCandidato"][$i] = $elemento->value;
                                    $i++;
                                } else {
                                    $array["dtUltimaAtualizacao"][$j] = $elemento->value;
                                    $j++;
                                }
                            }
                           
                            $i = 0;
                            //pega os dados(id prefeito||vereador e id ult atualização) de cada prefeito||vereador da cidade
                            for ($i; $i < $j; $i++) {
                                if($i >= $prox_cand){
                                    //monta a url que leva aos dados de cada candidato
                                    $urlDadosCandidato = "http://divulgacand2012.tse.jus.br/divulgacand2012/mostrarFichaCandidato.action?sqCandidato=".$array['sqCandidato'][$i]."&codigoMunicipio=".$codigoMunicipio."&dtUltimaAtualizacao=".$array['dtUltimaAtualizacao'][$i];
                                    //echo $urlDadosCandidato.'</br>';
                                    
                                    raspaDados($urlDadosCandidato , $codigoMunicipio , $codigoCargo);

                                    $arquivo = fopen("control.txt", "w+");    
                                    fwrite($arquivo, $contEstado." ".$contCidade." ".($i + 1));
                                    fclose($arquivo);

                                    $arq = fopen("dados1.txt", "a+");
                                    fwrite($arq, $contEstado." ".$contCidade." ".($i + 1)." -- For do \$i \n");
                                    fclose($arq);
                                
                                }
                            }
                            $prox_cand = 0;
                            
                            /*
                            $arquivo = fopen("control.txt", "w+");    
                            fwrite($arquivo, $contEstado." ".($contCidade+1)." 0");
                            fclose($arquivo);    
                             */
                        
                            $arq = fopen("dados1.txt", "a+");
                            fwrite($arq, $contEstado." ".($contCidade+1)." 0 -- foreach de cidades \n");
                            fclose($arq);
                             
                        }
                        $contCidade++;
                    }
                    
                    $contCidade = 0;
                    $prox_cidade = 0;
                    /*
                    $arquivo = fopen("control.txt", "w+");
                    fwrite($arquivo, ($contEstado+1)." 0 0");
                    fclose($arquivo);
                     */
                }
                
                
                $contEstado++;
            }
            
            function raspaDados($urlDadosCandidato , $codigoMunicipio , $codigoCargo){
                                
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
                                $formulario["Partido:"] = "partido";
                                $formulario["Coligação:"] = "coligacao";
                                $formulario["Composição da coligação:"] = "composicaoColigacao";
                                $formulario["No. processo:"] = "nProcesso";
                                $formulario["No. protocolo:"] = "nProtocolo";
                                $formulario["CNPJ de campanha:"] = "cnpj";
                                $formulario["Limite de gastos:"] = "limiteGasto";
                                $formulario["Endereço do site do candidato:"] = "endSite";
                                
                                //array para guardar os dados
                                $dados = array();
                                
                                $tabelaSituacao = $html->find("table",0);
                                $texto = $tabelaSituacao->find("td",4);
                                $texto = iconv("ISO-8859-1", "UTF-8", $texto->plaintext);
                                $texto = html_entity_decode($texto);
                                $dados['situacao'] =  limpaPalavra($texto);
                                $imagem = $tabelaSituacao->find("img",0);
                                $dados['img'] = 'http://divulgacand2012.tse.jus.br/divulgacand2012/'.$imagem->src;

                                
                                //pega a tabela com os dados do prefeito
                                $tabelaDados = $tabelaSituacao->find("table", 1);
                                
                                //pega a tag com o registro de candidatura e sepera a cidade e o estado
                                $cidade_estado = $tabelaDados->find("td",0);
                                $pedacos = explode("(", $cidade_estado->plaintext);
                                $tamanho = count($pedacos);
                                $pedacos[$tamanho - 1] = str_replace("&nbsp;/&nbsp;", "||", $pedacos[$tamanho - 1]);
                                $pedacos[$tamanho - 1] = str_replace(")", "", $pedacos[$tamanho - 1]);
                                //guarda a cidade e o estado
                                $pedacos[$tamanho - 1] = iconv("ISO-8859-1", "UTF-8", $pedacos[$tamanho - 1]);
                                $pedacos[$tamanho - 1] = html_entity_decode($pedacos[$tamanho - 1]);
                                $cidade_estado = explode("||", $pedacos[$tamanho - 1]);
                                $dados['cidade_cand'] = limpaPalavra($cidade_estado[0]);
                                $dados['estado_cand'] = limpaPalavra($cidade_estado[1]);
                                
                                if($codigoCargo == 11)
                                    $dados['cargo'] = 'Prefeito';
                                else if($codigoCargo == 13)
                                    $dados['cargo'] = 'Vereador';
                                else
                                    $dados['cargo'] = 'Vice-prefeito';
                                
                                //variavel para contar a posição da tag td
                                $dtNumero = 0;
                                
                                $tds = $tabelaDados->find("td");
                                
                                //busca todos os tds dentro tabela com os dados do prefeito e confere a informação de
                                //cada um
                                foreach($tds as $td){
                                    
                                    //pega o conteudo do td
                                    $label = $td->plaintext;

                                    //passa de ISO para UTF-8
                                    $label = iconv("ISO-8859-1", "UTF-8", $label);
                                    //Tira o html encode
                                    $label = html_entity_decode($label);
                                    
                                    //caso exista um label no formulario que deve ser pego, pega seu input
                                    if(isset($formulario[$label])){
                                        
                                        $output = $tds[$dtNumero + 1]->plaintext;
                                        $output = str_replace("&nbsp;", " ", $output);
                                        $output = iconv("ISO-8859-1", "UTF-8", $output);
                                        $output = html_entity_decode($output);
                                        $output = limpaPalavra($output);
                                        
                                        $dados[$formulario[$label]] = $output;
                                    }
                                    
                                    $dtNumero++;
                                }
                                
                                //confere se o candidato possui site
                                if($dados['endSite'] == "")
                                    $dados['endSite'] = "NULL";
                                
                                //pega a cidade o estado de nascimento do candidato
                                $cid_est_nasc = explode("/", $dados["naturalidade"]);
                                $tam = count($cid_est_nasc);
                                $dados['estado_nascimento'] = limpaPalavra($cid_est_nasc[$tam - 1]);
                                $cidade_nas;
                                for($ind = 0 ; $ind < $tam - 1 ; $ind ++)
                                    $cidade_nas = $cid_est_nasc[$ind]." ";
                                $dados['cidade_nascimento'] = limpaPalavra($cidade_nas);
                                
                                //pega a sigla do partido
                                $patido = explode('-', $dados['partido']);
                                $siglaPartido = $patido[count($patido) - 2];
                                $dados['partido'] = limpaPalavra($siglaPartido);
                                
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
                                        $palavra = str_replace(".", "", $palavra);
                                        $palavra = str_replace(",", ".", $palavra);
                                        $bens["ValorBem"][$numeroBens] = $palavra;

                                        $numeroBens++;
                                    }
                                }
                                
                                //salvarDadosNoAllegro($dados, $bens, $numeroBens);
                                salvaDadosTxt($dados, $bens, $numeroBens);
                                
                                $vicesPrefeito = $tabelaDados->find('img[src="img/icones/vice.png"]');
                                foreach ($vicesPrefeito as $vicePrefeito){
                                    
                                    $vicePrefeito = str_replace('visualizarDadosVice(', '', $vicePrefeito->onclick);
                                    $vicePrefeito = str_replace(',', '', $vicePrefeito);
                                    $vicePrefeito = str_replace('"', '', $vicePrefeito);
                                    $vicePrefeito = str_replace('\'', '', $vicePrefeito);
                                    $vicePrefeito = str_replace(');', '', $vicePrefeito);
                                    
                                    $espaco = strripos($vicePrefeito, ' ');
                                    $codigoVice = substr($vicePrefeito, 0, $espaco);
                                    $codigoUltAtulizacao = substr($vicePrefeito, $espaco + 1);
                                    
                                    $urlVicePrefeito = "http://divulgacand2012.tse.jus.br/divulgacand2012/mostrarFichaCandidato.action?sqCandSuperior=".$codigoVice."&codigoMunicipio=".$codigoMunicipio."&dtUltimaAtualizacao=".$codigoUltAtulizacao;
                                    
                                    raspaDados($urlVicePrefeito , $codigoMunicipio , 15);
                                    
                                }
                            
                            }
            
            function salvarDadosNoAllegro($dados , $bens , $numeroBens){
                $id = existePoli($dados['nomeCompleto'], $dados['dataNascimento']);
                echo $dados['nomeCompleto'].' '.$dados['dataNascimento'].'</br>';
                
                $consulta = 'select ?ano
                            where{
                                <http://ligadonospoliticos.com.br/politico/'.$id.'> polbr:election ?election.
                                ?election timeline:atYear ?ano                                                    
                            }';
                $consulta = consultaSPARQL($consulta);
                
                $candDepois = FALSE;
                foreach ($consulta as $resul)
                    if($resul['ano'] > 2012)
                        $candDepois = TRUE;
                
                //caso o candidadto ainda não esteja cadastrado no banco
                //Ou caso o candidato tenha concorrido só em 2010
                if($id == 0 || !$candDepois){
                    $id = politico_Prefeito_Vereador($dados['nomeCompleto'], $dados['img'], $dados['sexo'], $dados['dataNascimento'], $dados['estadoCivil'], $dados['ocupacao'], $dados['grauInstrucao'], $dados['nacionalidade'], $dados['cidade_nascimento'], $dados['estado_nascimento'], $dados['endSite'], $dados['cargo'],$dados['cidade_cand'] ,$dados['estado_cand'], $dados['partido'], NULL);
               }
                    $resultado = NULL;
                    eleicao_Prefeito_Vereador($id, "2012", $dados['nomeUrna'], $dados['numero'], $dados['partido'], $dados['cargo'], $dados['cidade_cand'], $dados['estado_cand'], $resultado, $dados['coligacao'], $dados['composicaoColigacao'], $dados['situacao'], $dados['nProtocolo'], $dados['nProcesso'], $dados['cnpj']);
                    foto_politico($dados['img'], $id);
                  
                    $num = 0;
                    while ($num < $numeroBens) {
                        declaracao_bens($id, "2012", $bens["DescricaoBem"][$num], $bens["TipoBem"][$num], $bens["ValorBem"][$num]);
                        $num++;
                    }
            }
            
            function salvaDadosTxt($dados , $bens , $numeroBens){
                $arq = fopen("dadosCompletos.txt", "a+");
                fwrite($arq, $dados['estado_cand']."\|de|/".$dados['cidade_cand']."\|de|/".$dados['cargo']."\|de|/".$dados['partido']."\|de|/".$dados['situacao']."\|de|/".$dados['nomeCompleto']."\|de|/".$dados['cidade_nascimento']."\|de|/".$dados['estado_nascimento']."\|de|/".$dados['dataNascimento']."\|de|/".$dados['img']."\|de|/".$dados['nomeUrna']."\|de|/".$dados['numero']."\|de|/".$dados['sexo']."\|de|/".$dados['estadoCivil']."\|de|/".$dados['nacionalidade']."\|de|/".$dados['ocupacao']."\|de|/".$dados['grauInstrucao']."\|de|/".$dados['endSite']."\|de|/".$dados['coligacao']."\|de|/".$dados['composicaoColigacao']."\|de|/".$dados['nProcesso']."\|de|/".$dados['nProtocolo']."\|de|/".$dados['cnpj']."\|de|/");
                
                $num = 0;
                while ($num < $numeroBens) {
                    //declaracao_bens($id, "2012", $bens["DescricaoBem"][$num], $bens["TipoBem"][$num], $bens["ValorBem"][$num]);
                    fwrite($arq, $bens["DescricaoBem"][$num]."\|db|/".$bens["TipoBem"][$num]."\|db|/".$bens["ValorBem"][$num]."\|db|/");
                    $num++;
                }
                fwrite($arq, "\n");
                fclose($arq);
}
            
            function limpaPalavra($palavra){
                //retira quebras de linha e espaços em branco antes e depois da palavra
                $palavra = trim($palavra);
                $palavra = str_replace("\r", "", $palavra);
                $palavra = str_replace("\n", "", $palavra);
                $palavra = str_replace("\r\n", "", $palavra);
                $palavra = str_replace("\t", "", $palavra);
                $palavra = preg_replace("/(<br.*?>)/i","", $palavra);
                //deixa apenas um espaço entre as palavras
                $palavra = preg_replace('/\s(?=\s)/', '', $palavra);
                return $palavra;
            }

?>
    </body>
    
</html>