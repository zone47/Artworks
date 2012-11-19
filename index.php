<?php
/* / */

//error_reporting(E_ALL ^ E_NOTICE);
error_reporting(0);
ini_set("display_errors", 0);

set_time_limit(120);

$l="fr"; // Langue par défaut
$nb=20; // Nombre de résultats par défaut
$p=1; // numéro de page par défaut

if (isset($_GET['l']))
	$l=$_GET['l'];
if (isset($_GET['nb']))
	$nb=$_GET['nb'];
if (isset($_GET['p']))
	$p=$_GET['p'];

$host = 'localhost'; //Votre host, souvent localhost
$user = 'root'; //Votre login
$pass = ''; //Votre mot de passe
$db = 'dbartworks'; // Le nom de la base de donnée

$link = mysql_connect ($host,$user,$pass) or die ('Erreur : '.mysql_error());
mysql_select_db($db) or die ('Erreur :'.mysql_error());

// Author: John Wright
// Website: http://johnwright.me/blog
// This code is live @
// http://johnwright.me/code-examples/sparql-query-in-code-rest-php-and-json-tutorial.php
function getUrlDbpediaAbstract($query,$lg_dbpedia){
	// Fonction établissant la requête envoyée au Endpoint Sparql
	$format = 'json';
	if ($lg_dbpedia=="en")
		$lg_dbpedia="";
	else
		$lg_dbpedia.=".";
	$query ="
PREFIX owl: <http://www.w3.org/2002/07/owl#>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX foaf: <http://xmlns.com/foaf/0.1/>
PREFIX dc: <http://purl.org/dc/elements/1.1/>
PREFIX : <http://".$lg_dbpedia."dbpedia.org/resource/>
PREFIX dbpedia2: <http://".$lg_dbpedia."dbpedia.org/property/>
PREFIX dbpedia: <http://".$lg_dbpedia."dbpedia.org/>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>".$query;
 
	$searchUrl = 'http://'.$lg_dbpedia.'dbpedia.org/sparql?'
      .'query='.urlencode($query)
      .'&format='.$format;
	//echo  "<br>".$searchUrl."<br>"; //Debug
	return $searchUrl;
}

function request($url){
	// Fonction d'envoi de la requête au Endpoint Sparql
	if (!function_exists('curl_init'))
		die('CURL is not installed!');
	// get curl handle
	$ch= curl_init();
	// set request url
	curl_setopt($ch,
      CURLOPT_URL,
      $url);
	// return response
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	$response = curl_exec($ch);
	curl_close($ch);
	
	return $response;
}

function qr_label($uriresource,$lg_db){
	// Fonction pour rechercher le libellé d'une ressource selon une langue 
	// On cherche d'abord selon la langue d'interface choisie
	global $l;
	$lab="";
	$req="
		SELECT DISTINCT ?label
		WHERE{
		<".$uriresource."> rdfs:label ?label 
		FILTER( lang(?label) = \"".$l."\" )
		}";
	$requestURL = getUrlDbpediaAbstract($req,$lg_db);
	$dblabel = json_decode(
		request($requestURL),
		true);
	$lab=$dblabel["results"]["bindings"][0]["label"]["value"];
	
	// Si on ne trouve pas on récupère le libellé dans la langue de la dbpedia d'où est extraite la ressource
	if ($lab==""){
		$req="
			SELECT DISTINCT ?label
			WHERE{
			<".$uriresource."> rdfs:label ?label 
			FILTER( lang(?label) = \"".$lg_db."\" )
			}";
		$requestURL = getUrlDbpediaAbstract($req,$lg_db);
		$dblabel = json_decode(
			request($requestURL),
			true);
		$lab=$dblabel["results"]["bindings"][0]["label"]["value"];

		if ($lab!="") {
			$lab=$lab." <span class=\"lg_other\">(".$lg_db.")</span>"; 
		}
	}
	return $lab;
	
}
function imgurl($imgthumb){
	// Fonction pour trouver la page de Wikimédia Commons correspond à la vignette
	if (strpos($imgthumb,"jpeg")){
		$pos=strpos($imgthumb,"jpeg");
		$urlimg=substr($imgthumb, 0, $pos)."jpeg";
	}
	else{
		$pos=strpos($imgthumb,"jpg");
		$urlimg=substr($imgthumb, 0, $pos)."jpg";
	}
	
	$pos=strrpos($urlimg,"/");
	$urlimg="http://commons.wikimedia.org/wiki/File:".substr($urlimg, $pos+1, strlen($urlimg));
	$urlimg=preg_replace("/\/thumb/","",$urlimg);
    return $urlimg;
}

function artwork($uriartwork,$lg_dbpedia,$uri_l_db){
	// Fonction pour retrouver les données d'une œuvre (titre , vignette, année, artiste, lieu de conservation à partir de son url
	// Comme les propriétés n'ont pas le même nom dans les différentes dbpedia, il est nécessaire de spécifier les propriétés selon chaque dbpedia  
	$req="SELECT DISTINCT ?label, ?thumb , ?year, ?artist, ?museum
	WHERE
	{
	<".$uriartwork."> rdfs:label ?label;";
	switch ($lg_dbpedia){
		case "en":
			$req.="<http://dbpedia.org/ontology/thumbnail> ?thumb
				OPTIONAL {
				<".$uriartwork."> dbpedia2:artist ?artist;
				dbpprop:museum ?museum;
				dbpprop:year ?year";
			break;
		case "fr":
			$req.="<http://dbpedia.org/ontology/thumbnail> ?thumb
				OPTIONAL {
				<".$uriartwork."> <http://fr.dbpedia.org/property/artiste> ?artist;
				<http://fr.dbpedia.org/property/musée> ?museum;
				<http://fr.dbpedia.org/property/année> ?year";
			break;
		case "de":
			$req.="<http://dbpedia.org/ontology/thumbnail> ?thumb
				OPTIONAL {
				<".$uriartwork."> <http://de.dbpedia.org/property/künstler> ?artist;
				<http://de.dbpedia.org/property/museum> ?museum;
				<http://de.dbpedia.org/property/jahr> ?year";
			break;
		case "es":
			$req.="<http://dbpedia.org/ontology/thumbnail> ?thumb
				OPTIONAL {
				<".$uriartwork."> dbpedia2:artist ?artist;
				dbpprop:museum ?museum;
				dbpprop:year ?year";
			break;
		case "el":
			$req.="<http://dbpedia.org/ontology/thumbnail> ?thumb
				OPTIONAL {
				<".$uriartwork."> <http://el.dbpedia.org/property/ζωγράφος> ?artist;
				<http://el.dbpedia.org/property/μουσείο> ?museum;
				<http://el.dbpedia.org/property/έτος> ?year";
			break;
	}

	
	$req.="
		}
		
	}";
	//echo $req; //Debug
	$requestURL = getUrlDbpediaAbstract($req,$lg_dbpedia);
	$dbartowrk = json_decode(
		request($requestURL),
		true);
	$label=$dbartowrk["results"]["bindings"][0]["label"]["value"];
	$thumb=$dbartowrk["results"]["bindings"][0]["thumb"]["value"];
	$year=$dbartowrk["results"]["bindings"][0]["year"]["value"];
	$artisturi=$dbartowrk["results"]["bindings"][0]["artist"]["value"];
	$museumuri=$dbartowrk["results"]["bindings"][0]["museum"]["value"];

	// Après avoir récupéré les url des données de l'œuvre, on cherche son libellé
	$labelart=qr_label($uriartwork,$lg_dbpedia);
	if ($labelart!="")
		$label=$labelart;
	$artist=qr_label($artisturi,$lg_dbpedia);
	$museum=qr_label($museumuri,$lg_dbpedia);
	
	// Page de Wikimédia Commons correspond à la vignette
	$img=imgurl($thumb);

	if ($thumb!=""){
		// On redimensionne les images en 150 px de largeur ou 200px de haut, de plus grand côté
		list($width, $height, $type, $attr) = getimagesize($thumb);
		$marg1=0;
		$marg2=0;
		if ($height/$width>0.75){
			$nvheight=150;
			$nvwidth=round($nvheight/$height*$width);
		}
		else{
			$nvwidth=200;
			$nvheight=round($nvwidth/$width*$height);
			$marg2=floor((150-$nvheight)/2);
			$marg1=ceil((150-$nvheight)/2);
		}

		// On affiche le cartel avec l'image
		echo "
		<li><div><div style=\"padding:".$marg1."px 0 ".$marg2."px 0\"><a href=\"".$img."\" title=\"zoom\"><img src=\"".$thumb."\" alt=\"\" width=".$nvwidth." ></a></div></div>";
		$uri_link=$uriartwork;
		if ($uri_l_db!='')
			$uri_link=$uri_l_db;
		$uri_link=preg_replace("/dbpedia.org\/resource/","wikipedia.org/wiki",$uri_link);
		echo "<a href=\"".$uri_link."\">$label</a><br><span>";
		echo $artist;
		if ($artist!='' and $year!='')
			echo ",";
		echo " ".$year;
		if ($museum!=""){
			echo " - ".$museum;
		}
		echo "</span>\n";
		echo "</li>";
	}

}

// nombre total d'œuvres
$sql="SELECT COUNT(id) AS compteur FROM artworks ";
$rep=mysql_query($sql)
	 or die('Erreur SQL ! '.$sql.'<br/>'.mysql_error());
$nb_awks=mysql_fetch_array($rep) ;

$nbpg=ceil($nb_awks["compteur"]/$nb); // nombre de pages


/* Requête œuvres */
$deb=($p-1)*$nb;
$sql="SELECT * FROM artworks  
LIMIT ".$deb.", ".$nb;
$rep=mysql_query($sql)
	 or die('Erreur SQL ! '.$sql.'<br/>'.mysql_error());
?>

<!doctype html>
<html lang="">
<head>
  <meta charset="utf-8">
  <title>DBpedia Artworks | Œuvres d'art | Gemälde | Schilderijen | Έργα Ζωγραφικής</title>
  <link rel="stylesheet" href="styles.css">
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
<script type="text/javascript">
//<![CDATA[
$(document).ready(function(){
	$('#lg').change(function() {
		$('#form').submit();
	});
	$('#nb').change(function() {
		$('#form').submit();
	});
});
//]]>
</script>
</head>
<body>
<form action="index.php" method="get" id="form" >
<!-- Langues d'interface -->
<label for="lg">Langue</label>
<select name="l" id="lg">

<?php
$lg = array(
"ca" => "Català", 
"cs" => "Česky",
"de" => "Deutsch",
"en" => "English",
"fr" => "Français",
"it" => "Italiano", 
"ja" => "日本語",
"ko" => "한국어",
"hu" => "Magyar",
"nl" => "Nederlands",
"no" => "‪norsk (bokmål)‬",
"pl" => "Polski",
"pt" => "Português",
"ro" => "Română", 
"ru" => "Русский",
"fi" => "Suomi",
"sv" => "Svenska",
"tr" => "Türkçe",
"uk" => "Українська",
"zh" => "中文") ;
foreach ($lg as $code => $langage){
    echo "	<option value=\"".$code."\"";
	if ($l==$code)
		 echo " selected=\"selected\"";
	echo ">".$langage."</option>\n";
}

?>



</select>
<?php
	if ($p!=1)
		echo "<input type=\"hidden\" value=\"".$p."\" name=\"p\" />\n";
?>
<!-- Nombre de résultats par page -->
<label for="nb">Nombre de résultats</label>
<select name="nb" id="nb">
	<option value="10" <?php if ($nb==10) echo "selected=\"selected\""; ?>>10</option>
    <option value="20" <?php if ($nb==20) echo "selected=\"selected\""; ?>>20</option>
    <option value="50" <?php if ($nb==50) echo "selected=\"selected\""; ?>>50</option>
</select>
<input type="submit" value="ok" />
</form>
<h1>DBpedia (<a href="http://dbpedia.org/">en</a>,
 <a href="http://fr.dbpedia.org/">fr</a>,
 <a href="http://de.dbpedia.org/">de</a>,
 <a href="http://nl.dbpedia.org/">nl</a>,
 <a href="http://el.dbpedia.org/">el</a>) Artworks | Œuvres d'art | Gemälde | Schilderijen | Έργα Ζωγραφικής</h1>
 
<div class="nav">

<?php
// Barre de navigation
$nav="";
$navlg="";
$navnb="";
if ($l!="fr") $navlg="&l=".$l;
if ($nb!="20") $navnb="&nb=".$nb;
	
if ($p!=1){
	$nav.='<a href="?p=1'.$navlg.$navnb.'">Début</a>&nbsp;&nbsp;';
	$nav.= '<a href="?p='.($p-1).$navlg.$navnb.'">Précédent</a>&nbsp;&nbsp;';
}
$nav.= " Page $p ";
if ($p!=$nbpg){
	$nav.= '&nbsp;&nbsp;<a href="?p='.($p+1).$navlg.$navnb.'">Suivant</a>';
	$nav.= '&nbsp;&nbsp;<a href="?p='.($nbpg).$navlg.$navnb.'">Fin</a>';
}
echo $nav;
?>

</div>
<ul>
	
<?php
// Boucle sur la requête des œuvres dans la base
// On récupère dans la base l'url de la ressource-œuvre et la langue de la dbpedia d'où elle est extraite
// Puis on va chercher à la volée les données de l'œuvre pour les affciher ensuite 
while($data = mysql_fetch_assoc($rep)) {
	$lg_db=$data['source'];
	$uri_l=$data[$l];
	artwork($data[$lg_db],$lg_db,$uri_l);
}

mysql_close();
?>

</ul>

<div class="nav navbas">

<?php
	echo $nav;
?>

</div>

<div id="bas">Les données sont extraites à la volée des <b>dbpedia (en, fr, de, nl, el)</b> à partir des url des ressources.<br>
</div>
</body>
</html>