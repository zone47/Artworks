<?php
/* / */
// Script récoltant les url des œuvres d'art sur les différentes dbpedia
// Les données sont récoltées sur une base de récolte pour ensuite remplacer la base principale
set_time_limit(3600);
error_reporting(E_ALL ^ E_NOTICE);

$host = 'localhost'; //Votre host, souvent localhost
$user = 'root'; //Votre login
$pass = ''; //Votre mot de passe
$db = 'dbartworks'; // Le nom de la base de donnée

$link = mysql_connect ($host,$user,$pass) or die ('Erreur : '.mysql_error());
mysql_select_db($db) or die ('Erreur :'.mysql_error());

//on vide la table
mysql_query("TRUNCATE artworks2;");


// Point de départ pour le calcul du temps de traitement
list($g_usec, $g_sec) = explode(" ",microtime());
define ("t_start", (float)$g_usec + (float)$g_sec);

// Author: John Wright
// Website: http://johnwright.me/blog
// This code is live @
// http://johnwright.me/code-examples/sparql-query-in-code-rest-php-and-json-tutorial.php
function getUrlDbpediaAbstract($query,$lgdb){
	$format = 'json';
	$query ="
PREFIX owl: <http://www.w3.org/2002/07/owl#>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX foaf: <http://xmlns.com/foaf/0.1/>
PREFIX dc: <http://purl.org/dc/elements/1.1/>
PREFIX : <http://dbpedia.org/resource/>
PREFIX dbpedia2: <http://dbpedia.org/property/>
PREFIX dbpedia: <http://dbpedia.org/>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>".$query;

	if ($lgdb=="en")
 		$searchUrl = 'http://dbpedia.org/sparql?';
	else
		$searchUrl = 'http://'.$lgdb.'.dbpedia.org/sparql?';
	$searchUrl.=
	   'query='.urlencode($query)
      .'&format='.$format;

   return $searchUrl;
}
function request($url){
   // is curl installed?
   if (!function_exists('curl_init'))
      die('CURL is not installed!');
   // get curl handle
   $ch= curl_init();
   // set request url
   curl_setopt($ch,
      CURLOPT_URL,
      $url);
   // return response, don't print/echo
   curl_setopt($ch,
      CURLOPT_RETURNTRANSFER,
      true);
   $response = curl_exec($ch);
   curl_close($ch);
   return $response;
}

function checkRemoteFile($url){
	// fonction pour tester l'existence d'un fichier
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    // don't download content
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    if(curl_exec($ch)!==FALSE)
        return true;
    else
        return false;
}

function EnregArtW($uriartwork,$lg_ec,$url_img){
	// Fonction d'enregistrement d'une url ressource œuvre d'art
	
	// On cherche l'équivalent de la ressource dans les autres dbpedia
	$req="SELECT ?sameas
	WHERE
	{
	<".$uriartwork."> owl:sameAs ?sameas
	}";
	$requestURL = getUrlDbpediaAbstract($req,$lg_ec);

	$dbsameas = json_decode(
		request($requestURL),
		true);
	
	$i=-1;
	$sqlinsert="";
	
	// requête d'insertion
	$sqlinsert_deb="INSERT INTO artworks2 (source,thumb,".$lg_ec;
	$sqlinsert_suite=") VALUES (\"".$lg_ec."\",\"".$url_img."\",\"".$uriartwork."\"";
	if (is_array($dbsameas['results']['bindings'])){
		$enok=true;$frok=true;$deok=true;$elok=true;$nlok=true;//Flag pour qu'il n'y ait qu'une équivalence owl:sameAs par dbpedia (pb pour el.dbpedia)
		foreach($dbsameas['results']['bindings'] as $enreg=>$tabval){
			// Pour chaque équivalence on teste si elle est sur une dbpedia
			$i++;
			$res_sameas=$dbsameas["results"]["bindings"][$i]["sameas"]["value"];
			$db_lg = substr($res_sameas, 7, 2);
			if ($db_lg=="db")
				$db_lg ="en";
			switch($db_lg){
				case 'en':
				case 'fr':
				case 'de':
				case 'el':
				case 'nl':
					$lg_ok=true;
					break;
				default:
					$lg_ok=false;
					break;
			}
			// Si l'equivalence
			// - est bien dans une langue des autres dbpedia requêtées
			// - n'est pas sur la même dbpedia
			// - est bien sur une dbpedia
			// alors on l'enregistre
			
			if (($lg_ok)&&($db_lg!=$lg_ec)&&($res_sameas!="")&&(strpos($res_sameas,"dbpedia")>0)){
				if (($db_lg=="en")&&($enok) OR ($db_lg=="fr")&&($frok) OR ($db_lg=="de")&&($deok) OR ($db_lg=="el")&&($elok) OR ($db_lg=="nl")&&($nlok)){
					$sqlinsert_deb.=",".$db_lg;
					$sqlinsert_suite.=",\"".$res_sameas."\"";
					if ($db_lg=="en") $enok=false;if ($db_lg=="fr") $frok=false;if ($db_lg=="de") $deok=false;if ($db_lg=="el") $elok=false;if ($db_lg=="nl") $nlok=false;
				}
			}
		}
	}
	$sqlinsert=$sqlinsert_deb.$sqlinsert_suite.");";
	//echo $sqlinsert."<br/>\n"; // Debug
	mysql_query($sqlinsert);
}

$total=0;
$lg = array(
"en",
"de",
"nl",
"el",
"fr"
) ;
//"es","ja","pt","cs","ko"
foreach ($lg as $langage){
	// Fonction pour retrouver url des œuvres 
	// Comme la propriété artwork/œuvre d'art n'a pas le même nom dans les différentes dbpedia, il est nécessaire de spécifier selon chaque dbpedia  

   	$req="
	SELECT DISTINCT ?predicate, ?thumb
	WHERE {";
	switch ($langage){
		case "en":
			$req.="?predicate dbpedia2:wikiPageUsesTemplate <http://dbpedia.org/resource/Template:Infobox_artwork>";
			break;
		case "de":
			$req.="?predicate <http://de.dbpedia.org/property/wikiPageUsesTemplate> <http://de.dbpedia.org/resource/Vorlage:Infobox_Gemälde>";
			break;
		case "fr":
			$req.="?predicate <http://fr.dbpedia.org/property/wikiPageUsesTemplate>  <http://fr.dbpedia.org/resource/Modèle:Infobox_Art>";
			break;
		case "el":
			$req.="?predicate <http://el.dbpedia.org/property/wikiPageUsesTemplate> <http://el.dbpedia.org/resource/Πρότυπο:Έργο_Ζωγραφικής>";
			break;
		case "nl":
			$req.="?predicate <http://nl.dbpedia.org/property/wikiPageUsesTemplate> <http://nl.dbpedia.org/resource/Sjabloon:Infobox_schilderij>";
			break;	
			
		/* Some others dbpedias */
		case "es":
			$req.="?predicate <http://es.dbpedia.org/property/wikiPageUsesTemplate> <http://es.dbpedia.org/resource/Plantilla:Ficha_de_pintura>";
			break;
		case "ja":
			$req.="?predicate <http://ja.dbpedia.org/property/wikiPageUsesTemplate> <http://ja.dbpedia.org/resource/Template:Infobox_絵画作品>";
			break;
    	case "pt":
			$req.="?predicate <http://pt.dbpedia.org/property/wikiPageUsesTemplate> <http://pt.dbpedia.org/resource/Predefinição:Info/Pintura>";
			break;
		case "cs":
			$req.="?predicate <http://pt.dbpedia.org/property/wikiPageUsesTemplate> <http://pt.dbpedia.org/resource/Šablona:Infobox_obraz>";
			break;		
		case "ko":
			$req.="?predicate <http://ko.dbpedia.org/property/> <http://ko.dbpedia.org/resource/Template:예술품_정보>";
			break;	
	}
	$req.=" ;
	<http://dbpedia.org/ontology/thumbnail> ?thumb
	 }
	";
	//LIMIT 10 //Debug
	//echo $req."<br />"; //Debug
	$requestURL = getUrlDbpediaAbstract($req,$langage);
	$responseArray = json_decode(
	request($requestURL),
	true);
	
	$i=-1;
	$j=0;
	$suppr=0;
	foreach($responseArray['results']['bindings'] as $enreg=>$tabval){
		// Traitement des url ressources-œuvres récoltées
		$i++;

		if ($langage=="en")
			$num_rows=false;
		else {//On cherche si la référence est déjà présente (sauf langue "en" faite en premier)   
			$sql="SELECT id FROM artworks2 WHERE ".$langage."=\"".$responseArray["results"]["bindings"][$i]["predicate"]["value"]."\"";
			$rep=mysql_query($sql);
			$num_rows = mysql_num_rows($rep);
		}
		
		if (!$num_rows){//Si référence pas présente, on cherche ses équivalences et on enregistre
			//On prend aussi l'url de la vignette
			$thumb=$responseArray["results"]["bindings"][$i]["thumb"]["value"];
			EnregArtW($responseArray["results"]["bindings"][$i]["predicate"]["value"],$langage,$thumb); 
			$j++;
		}

	}
	$total+=$j;
	echo "$langage Total Artworks ".($i+1)." Enreg ".$j."<br/>";
	

}
echo "Total ".$total."<br />";
list($g2_usec, $g2_sec) = explode(" ",microtime());
// Calcul et affichage du temps de traitement
define ("t_end", (float)$g2_usec + (float)$g2_sec);
print "<br>".round (t_end-t_start, 1)." secondes";	
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Harvest</title>
</head>
<body>

<h1>Harvest</h1>
<div>
<?php

mysql_close();
echo "done";
?>
</div>
</body>
</html>