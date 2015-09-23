<?php
/*
<html>

    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        
        <script>
            function myFunction() {
                location.reload();
            }
</script>
    </head>
    
    <body onload="myFunction()">
        <?php
        echo rand(0, 10);
        //<meta HTTP-EQUIV='refresh' CONTENT='5;URL=teste2.php'>
        ?>
    </body>
</html>
 */
 $arq = fopen("dadosTeste.txt", "r+");
 
  
   while(!feof($arq)){
     $linha = fgets($arq);
     $palavras = explode(" ", $linha);
     $i = 0;
     foreach ($palavras as $palavra){
         echo $i.' '.$palavra.'</br>';
         $i++;
     }
     $i = 0;
     echo count($palavras);
 }
   
  
  
   /*
   $i = 0;
    while ($i < 10000) {
        fwrite($arq, "teste1 ");
        $i++;
    }
    */
   
    
    
 fclose($arq);
?>