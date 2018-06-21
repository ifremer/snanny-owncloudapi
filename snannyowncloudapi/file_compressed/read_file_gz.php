<?php

// $compressed_file = "/home/romain/Téléchargements/Test_donnees/U_TSR.gz";
// $compressed_file = "/home/romain/Téléchargements/Test_donnees/U_TSR.tar.gz";

$baseURI = $_POST["baseURI"];
// "//localhost/owncloud/index.php/apps/files/?dir=/Test/data&fileid=119"
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

$file = gzopen($compressed_file, 'rb');
if($file){
    $decompressed_file = '';
    while(!gzeof($file)){
        $decompressed_file .= gzread($file, 1024);
    }
    gzclose($file);
}
if(strlen($decompressed_file) > 2000000){
    $decompressed_file = substr($decompressed_file, 0, 2000000);
}
echo $decompressed_file;

?>