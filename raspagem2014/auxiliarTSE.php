<html>
<head>
    <meta  charset=utf-8 />
    <title> TSE Raspagem </title>
</head>
<body>
<?php
    include("simple_html_dom/simple_html_dom.php");
    include('controleRaspagem.php');
    include('./upgrade.database1.php');

    $arquivo = file('/var/www/html/ligadopoliticos/controlador.txt');
    $t = (int)$arquivo[0];
    $c = 0;
    $html00 = new simple_html_dom();
    $html00->load_file("http://divulgacand2014.tse.jus.br/divulga-cand-2014/eleicao/2014/UF/SP/candidatos/cargo/7");
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
                        file_put_contents('/var/www/html/ligadopoliticos/controlador.txt',$t);
                    }
                    $c++;
                }
            }$i++;
        }
    }



    ?>
</body>
</html>
