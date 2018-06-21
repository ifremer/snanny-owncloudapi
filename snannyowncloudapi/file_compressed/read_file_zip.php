<?php


// $compressed_file = "/home/romain/Téléchargements/Test_donnees/U_TSR.zip";

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

$archive = new PharData($compressed_file);
$name_file = "";
foreach($archive as $file){
    $name_file = $file;
}
$fp = fopen("$name_file", "r") or die("impossible d'ouvrir le fichier '$name_file'");
if($fp){
    $decompressed_file = '';
    while(!feof($fp)){
        $decompressed_file .= fread($fp, 8192);
    }
    fclose($fp);
    if(strlen($decompressed_file) > 2000000){
        $decompressed_file = substr($decompressed_file, 1, 2000000);
    }
    // echo $compressed_file;
    echo $decompressed_file;
}

// $file = "U=0.6_TSR=0.0.txt.zip";
// $fp = fopen("zip:///home/romain/Téléchargements/Test_donnees/$file#U=0.6_TSR=0.0.txt", "r") or die("impossible d'ouvrir le fichier '$file'");
// if($fp){
//     $decompressed_file = '';
//     while(!feof($fp)){
//         $decompressed_file .= fread($fp, 8192);
//     }
//     fclose($fp);
//     echo $decompressed_file;
// }

?>