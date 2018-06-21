<?php


$username = $_POST["username"];
$filename = $_POST["filename"];
$message_mail = $_POST["message_mail"];


// Définition du sujet
$sujet = "Upload des données sur Ownloud";

// Délaration de l'adresse de destination
$to = 'romain.balanche@ifremer.fr';

// Filtre les serveurs qui rencontrent des bogues.
if (!preg_match("#^[a-z0-9._-]+@(hotmail|live|msn).[a-z]{2,4}$#", $to)){ 
    $passage_line = "\r\n";
}
else{
    $passage_line = "\n";
}

// Envoi du mail
$send_mail = mail($to, $sujet, $message_mail);

echo $send_mail;

?>