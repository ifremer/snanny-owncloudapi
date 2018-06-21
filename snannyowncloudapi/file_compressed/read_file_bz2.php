<?php

// $compressed_file = "/home/romain/Téléchargements/Test_donnees/U_TSR.bz2"; // OK
// $compressed_file = "/home/romain/Téléchargements/Test_donnees/U_TSR.tar.bz2"; // OK

$baseURI = $_POST["baseURI"];

$i = 0;
while(substr($baseURI, $i, 7)!="/?dir=/"){
    $i++;
}
$baseURI = substr($baseURI, $i+7);
$j = 0;
while(substr($baseURI, $j, 7)!="&fileid"){
    $j++;
}
$baseURI = substr($baseURI, 0, $j);

// $compressed_file = "/var/www/owncloud/data/".$_POST["username"]."/files/$baseURI/".$_POST["filename"];
$compressed_file = "/export/home/data/owncloud/".$_POST["username"]."/files/$baseURI/".$_POST["filename"];


$bz = bzopen($compressed_file, "r") ;//or die("impossible d'ouvrir le fichier '$compressed_file'");
$decompressed_file = '';
while(!feof($bz)){
    $decompressed_file .= bzread($bz, 4096);
}
bzclose();

// $verif = substr($decompressed_file, 0, 50);
// while(preg_match('#[^a-zA-Z0-9\s.-]#',$verif)){
//     $decompressed_file = substr($decompressed_file, 1);
//     $verif =substr($decompressed_file, 0, 50);
// }

if(strlen($decompressed_file) > 2000000){
    $decompressed_file = substr($decompressed_file, 0, 2000000);
}
echo $decompressed_file;

?>