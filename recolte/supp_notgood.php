<?php
/* / */
// Supression d'entrées indiquées comme artworks dans la dbpedia en et qui n'en sont pas
set_time_limit(3600);
error_reporting(E_ALL ^ E_NOTICE);

$host = 'localhost'; //Votre host, souvent localhost
$user = 'root'; //Votre login
$pass = ''; //Votre mot de passe
$db = 'dbartworks'; // Le nom de la base de donnée

$link = mysql_connect ($host,$user,$pass) or die ('Erreur : '.mysql_error());
mysql_select_db($db) or die ('Erreur :'.mysql_error());

$ng= array();
$ng[0]="http://dbpedia.org/resource/Counter-Reformation";
$ng[1]="http://dbpedia.org/resource/Neo-impressionism";
$ng[2]="http://dbpedia.org/resource/Kappazuri";
$ng[3]="http://dbpedia.org/resource/Y%C5%8Dga_(art)";
$ng[4]="http://dbpedia.org/resource/S%C5%8Dtar%C5%8D_Yasui";

for ($i=0;$i<count($ng);$i++){
	$sql="SELECT id FROM artworks2 where en='".$ng[$i]."'";
	$rep2=mysql_query($sql);
	$row = mysql_fetch_assoc($rep2);
	$sql="DELETE FROM artworks2 WHERE id=".$row['id'];
	$rep=mysql_query($sql);	
}


?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>suppr</title>
</head>
<body>

<h1>suppr images pas bonnes</h1>
<div>
<?php

mysql_close();
echo "done";
?>
</div>
</body>
</html>