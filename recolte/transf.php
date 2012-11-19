<?php
// Script de transfert des données de la base de récolte vers la base principale qui est écrasée
/* / */
set_time_limit(3600);
error_reporting(E_ALL ^ E_NOTICE);

$host = 'localhost'; //Votre host, souvent localhost
$user = 'root'; //Votre login
$pass = ''; //Votre mot de passe
$db = 'dbartworks'; // Le nom de la base de donnée

$link = mysql_connect ($host,$user,$pass) or die ('Erreur : '.mysql_error());
mysql_select_db($db) or die ('Erreur :'.mysql_error());

$rep=mysql_query("DROP TABLE artworks");

$rep=mysql_query("CREATE TABLE artworks LIKE artworks2 ");

$rep=mysql_query("INSERT INTO artworks SELECT * FROM artworks2");
mysql_close();
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Transfert bdd</title>
</head>
<body>

<h1>Transfert bdd</h1>
<div>
Done
</div>
</body>
</html>