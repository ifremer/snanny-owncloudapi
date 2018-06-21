<?php


$message_mail = $_POST["message_mail"];

// vérification existence fichier
$file = fopen(/*adresse + nom du fichier*/, 'a+');
    
fputs(/*adresse + nom du fichier*/, $message_mail);

fclose(/*adresse + nom du fichier*/);

echo $copy;

?>