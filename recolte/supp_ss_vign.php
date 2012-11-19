<?php
/* / */
// Page de suppression des url d'œuvres dont la vignette n'est pas accessible
// On fait ce traitement après la récolte sur une dpbedia pour avoir d'erreur 504 Gateway Timeout
// Ce script peut nécessiter plusieurs passes, voire se repositionner après les élements déjà traités
set_time_limit(3600);

$host = 'localhost'; //Votre host, souvent localhost
$user = 'root'; //Votre login
$pass = ''; //Votre mot de passe
$db = 'dbartworks'; // Le nom de la base de donnée

$link = mysql_connect ($host,$user,$pass) or die ('Erreur : '.mysql_error());
mysql_select_db($db) or die ('Erreur :'.mysql_error());

error_reporting(E_ALL ^ E_NOTICE);

function checkRemoteFile($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    if(curl_exec($ch)!==FALSE)
        return true;
    else
        return false;
}
	$suppr=0;
	//On cherche si il y a une vignette et si non on supprime l'enregistrement
	$sql="SELECT id, thumb FROM artworks2";
	//$sql="SELECT id, thumb FROM artworks2 WHERE id>3000";
	$rep2=mysql_query($sql);
	while ($row = mysql_fetch_assoc($rep2)) {
		if (!(checkRemoteFile($row['thumb']))){
			$sql="DELETE FROM artworks2 WHERE id=".$row['id'];
			$rep=mysql_query($sql);	
			$suppr++;
		}
	}
	echo " Suppr ".$suppr."<br />";
	
echo "Total ".$total."<br />";
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>suppr</title>
</head>
<body>

<h1>suppr</h1>
<div>
<?php

mysql_close();
echo "done";
?>
</div>
</body>
</html>