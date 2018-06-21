<?php


$username = $_POST["username"];
$filename = $_POST["filename"];
$old_baseURI = $_POST["old_baseURI"];
$new_baseURI = $_POST["new_baseURI"];

// permet de renommer et de bouger le fichier de place
// $rename = rename($old_baseURI,$new_baseURI); // oldname, newname

// Copie de fichier
$copy = copy($old_baseURI, $new_baseURI); // oldname, newname

// Suppression du fichier
// unlink($old_baseURI);

echo $copy;

?>