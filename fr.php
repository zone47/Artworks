<?php
/* / */
// thanks To Donna Summer - Love to loe you baby, version longue, sur laquelle une bonne partie du script a été écrite

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
;
 
   $searchUrl = 'http://'.$lg_dbpedia.'dbpedia.org/sparql?'
      .'query='.urlencode($query)
      .'&format='.$format;
	//echo  "<br>".$searchUrl."<br>";
   return $searchUrl;
}


function request($url){
 
   // is curl installed?
   if (!function_exists('curl_init')){
      die('CURL is not installed!');
   }
 
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
 
   /*
Here you find more options for curl:
http://www.php.net/curl_setopt
*/	
 
   $response = curl_exec($ch);
 
   curl_close($ch);
 
   return $response;
}


function printArray($array, $spaces = "")
{
   $retValue = "";

   if(is_array($array))
   {	
      $spaces = $spaces
         ."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

      $retValue = $retValue."<br/>";

      foreach(array_keys($array) as $key)
      {
         $retValue = $retValue.$spaces
            ."<strong>".$key."</strong>"
            .printArray($array[$key],
               $spaces);
      }	
      $spaces = substr($spaces, 0, -30);
   }
   else $retValue =
      $retValue." - ".$array."<br/>";

   return $retValue;
}

function qr_label($uriresource,$lg_db){
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
function checkRemoteFile($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    // don't download content
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    if(curl_exec($ch)!==FALSE)
    {
        return true;
    }
    else
    {
        return false;
    }
}

function artwork($uriartwork,$lg_dbpedia,$uri_l_db){

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
	//echo $req;
	$requestURL = getUrlDbpediaAbstract($req,$lg_dbpedia);
	$dbartowrk = json_decode(
		request($requestURL),
		true);
	$label=$dbartowrk["results"]["bindings"][0]["label"]["value"];
	$thumb=$dbartowrk["results"]["bindings"][0]["thumb"]["value"];
	$year=$dbartowrk["results"]["bindings"][0]["year"]["value"];
	$artisturi=$dbartowrk["results"]["bindings"][0]["artist"]["value"];
	$museumuri=$dbartowrk["results"]["bindings"][0]["museum"]["value"];

	$labelart=qr_label($uriartwork,$lg_dbpedia);
	if ($labelart!="")
		$label=$labelart;
	$artist=qr_label($artisturi,$lg_dbpedia);
	$museum=qr_label($museumuri,$lg_dbpedia);
	
	$img=imgurl($thumb);

	if ($thumb!=""){
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
		echo "</span>";
		//echo "<br/>$uriartwork";
		echo "</li>";
	}

}
/*************/

/* nb artworks */
$sql='SELECT COUNT(id) AS compteur FROM artworks where nl="" AND en="" AND de="" AND el=""';
//$sql='SELECT COUNT(id) AS compteur FROM artworks where nl="" AND en="" AND fr="" AND de="" AND es=""';
$rep=mysql_query($sql)
	 or die('Erreur SQL ! '.$sql.'<br/>'.mysql_error());
$nb_awks=mysql_fetch_array($rep) ;
//echo $nb_awks["compteur"];
$nbpg=ceil($nb_awks["compteur"]/$nb);


/* Requête artworks */
$deb=($p-1)*$nb;
$sql='SELECT * FROM artworks where nl="" AND en="" AND de="" AND el=""  
LIMIT '.$deb.', '.$nb;
//$sql='SELECT * FROM artworks where nl="" AND en="" AND fr="" AND de="" AND es="" LIMIT '.$deb.', '.$nb;
//echo $sql;
$rep=mysql_query($sql)
	 or die('Erreur SQL ! '.$sql.'<br/>'.mysql_error());
?>

<!doctype html>
<html lang="">
<head>
  <meta charset="utf-8">
  <title>DBpedia Œuvres d'art</title>
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
<label for="nb">Nombre de résultats</label>
<select name="nb" id="nb">
	<option value="10" <?php if ($nb==10) echo "selected=\"selected\""; ?>>10</option>
    <option value="20" <?php if ($nb==20) echo "selected=\"selected\""; ?>>20</option>
    <option value="50" <?php if ($nb==50) echo "selected=\"selected\""; ?>>50</option>
</select>
<input type="submit" value="ok" />
</form>
<h1>DBpedia (<del><a href="http://dbpedia.org/">en</a>,</del>
 <del><a href="http://fr.dbpedia.org/">fr</a>,
 <a href="http://de.dbpedia.org/">de</a>,
 <a href="http://es.dbpedia.org/">nl</a>,
 <a href="http://el.dbpedia.org/">el</a></del>) <del>Artworks |</del> Œuvres d'art <del>| Gemälde | Schilderijen | Έργα Ζωγραφικής</del></h1>
 
<div class="nav">

<?php
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

<div id="bas">Les données sont extraites à la volée de <b>dbpedia-fr</b> à partir des url des ressources.<br>
</div>
</body>
</html>