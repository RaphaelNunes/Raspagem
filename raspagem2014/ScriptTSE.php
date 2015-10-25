<html>
 <head>
  <meta  charset=utf-8 />
  <title> TSE Raspagem </title>
    <script>
            function recarregaPagina() {
                location.reload();
            }
    </script>
 </head>
 <body onload="recarregaPagina()">
    <?php
        include("../simple_html_dom.php");
        include('controleRaspagem.php');
        include('../upgrade.database.php');
        $url = "http://divulgacand2014.tse.jus.br/divulga-cand-2014/menu/2014";
        //$html= file_get_html($url);
        $html = new simple_html_dom();
        $html->load_file($url);
        if($html != null){
            $i=0;
            foreach($html->find('a[class="btn-sm"]') as $est){
                    $estados[$i]=$est->href;
                $i++; 
            }
            $arquivo = file('controlador.txt');
            //$arquivo = file('/var/www/html/ligados/ligadopoliticos/controlador.txt');
                $t = (int)$arquivo[0];
                $c = 0;
                $url0="http://divulgacand2014.tse.jus.br".$estados[0];//cada indice corresponde a um link de um estado e o indice 0 a pagina dos presidenciaveis 
                //$html0= file_get_html($url0);
                $html0 = new simple_html_dom();
                $html0->load_file($url0);
                $k=0;
                foreach($html0->find('div[class="col-md-4"]') as $button){// escolher entre governador, vice, senadors, dep federal e estadual
                    foreach($button->find('ul[class="dropdown-menu"]') as $dropdown){

                        foreach($dropdown->find('a') as $cat){
                                $categoria[$k] = "http://divulgacand2014.tse.jus.br".$cat->href;
                                //$html00 = file_get_html($categoria[$k]);
                                $html00 = new simple_html_dom();
                                $html00->load_file($categoria[$k]);
                                foreach($html00->find('table[id="tbl-candidatos"]') as $candidatos){
                                    $i=0;
                                    foreach($candidatos->find('tr') as $tbody){
                                        if($i>2){
                                        $pol[$i] = $tbody;
					$p = 0;
					foreach($pol[$i]->find('td') as $parti){
						if($p == 4 ) { $partido = $parti->plaintext;}
						$p++;
					    }
                                            foreach($pol[$i]->find('a') as $li){
                                                if($c >= $t){
                                                    $link= "http://divulgacand2014.tse.jus.br".$li->href;//cada link corresponde a um politico
                                                    $resposta = rasPolitico($link,$partido);//passado para a funcao o link da pagina do politico e a sigla do seu partido
                                                    $t++;
                                                    //file_put_contents('/var/www/html/ligados/ligadopoliticos/controlador.txt',$t);
                                                    file_put_contents('controlador.txt',$t);
                                                }
                                                $c++;
                                            }
                                        }$i++;  
                                    }  
                                } 
                        $k++;    
                        }
                    }
                } 
        }
    ?>
 </body>
</html> 
