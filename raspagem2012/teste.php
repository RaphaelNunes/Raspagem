<?php

$palavra = "http://márcos>deoliveiras@gmail.com";
//$palavra = htmlentities($palavra);
//echo $palavra;
$palavra = str_replace(">",".",$palavra);
echo $palavra;
?>