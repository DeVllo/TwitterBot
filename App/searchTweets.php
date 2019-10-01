<?php
session_start();
require_once("twitteroauth.php"); //Path to twitteroauth library
include('configsqli.php');

$mysqli = new mysqli($servername,$username,$password,$database);
$mysqli->query("SET NAMES 'utf8'");
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}
else{
    printf("conexion con la DB exitosa uwu");
    echo("</br></br>");
}
 
$search = "#fumoporrobot OR #fumoporro";
$notweets = 50;
$consumerkey = "";
$consumersecret = "";
$accesstoken = "";
$accesstokensecret = "";
 
function getConnectionWithAccessToken($cons_key, $cons_secret, $oauth_token, $oauth_token_secret) {
  $connection = new TwitterOAuth($cons_key, $cons_secret, $oauth_token, $oauth_token_secret);
  return $connection;
}
 
$connection = getConnectionWithAccessToken($consumerkey, $consumersecret, $accesstoken, $accesstokensecret);
 
$search = str_replace("#", "%23", $search);
$tweets = $connection->get("https://api.twitter.com/1.1/search/tweets.json?q=".$search."&count=".$notweets);
 
 
if ($stmt = $mysqli->prepare("SELECT MAX(ID) FROM frases")) {
    $stmt->execute();
    $stmt->bind_result($max_tweetid);
    $stmt->fetch();
    $stmt->close();
}

echo("</br></br>el id maximo en la db es:".$max_tweetid."</br></br>"); 

$twitts = json_decode($tweets);
$aborrar = "#fumoporro";
$error = 0;
$frases = [];
foreach($twitts->statuses as $item)
{
    
    if( $item->id_str >$max_tweetid)
    {
        $originalstring = strtolower($item->text);
        $stringa = str_replace($aborrar, "", $item->text);
        $stringo = str_replace("bot","",$stringa);
        $frasefinal = str_replace("fumo porro", "", $stringo);
        
        $tweetID = (int)$item->id_str;
        
        $autorizada = 1;
        $publicada = 0;

        /* EXCEPCIONES EN LOS TWITTS */
        $search1 = 'http';
        $search2 = 'RT @';
        $search3 = 'sigo a';
        $search4 = 'stan';
        $search5 = 'stanneo';
        $search7 = '#';
        $search6 = '@';
        $search8 = 'RT ';
        /*                          */
        
        if(preg_match("/{$search1}/i", $frasefinal) 
        OR preg_match("/{$search2}/i", $frasefinal) 
        OR preg_match("/{$search3}/i", $frasefinal)
        OR preg_match("/{$search4}/i", $frasefinal)
        OR preg_match("/{$search5}/i", $frasefinal)
        OR preg_match("/{$search6}/i", $frasefinal)
        OR preg_match("/{$search7}/i", $frasefinal)
        OR preg_match("/{$search8}/i", $frasefinal))
        {
        $error = 1;
        }
        
        $autor = "@".$item->user->screen_name;
        
        /*if(preg_match("/{$search7}/i", $frasefinal) OR preg_match("/{$search8}/i", $frasefinal))
        {
            $error = 1;
        }*/

        if($error != 1)
        {

            if ($sisoniguales = $mysqli->query("SELECT frase FROM frases WHERE frase = '$frasefinal'"))
            {
                $columnas_repetidas = $sisoniguales->num_rows;
                $sisoniguales->close();
            }

            if(!$columnas_repetidas > 0 )
            {
                //Cargar en base de datos.
                
                $stmt = $mysqli->prepare("INSERT INTO frases (frase,autorizada,publicada,twitterID,autor) 
                VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('siiis', 
                $frasefinal,
                $autorizada,
                $publicada,
                $tweetID,
                $autor);
                $stmt->execute(); 
                $stmt->close(); 
                echo("</br><h1 style='color:blue;'>Se acaba de agregar la frase: [fumo porro]+".$frasefinal." .</br>
                <p style='color:black'>Envíada por @".$item->user->screen_name."</p></br>");
                //Cargar frases en un array. ->
                array_push($frases, $frasefinal);
                
                
            }
            else
            {
                echo("<h1 style='color:black;'>YA EXTISTE LA FRASE '".$frasefinal."'</h1>");
            }
		
        }
        else
        {
        //cuando ignora una frase:
        echo("<h1 style='color:red;'>SE IGNORÓ LA FRASE: '".$frasefinal."'</h1></br>
        <p style='color:black'>Envíada por @".$item->user->screen_name."</p></br>");
        }
    }
    //no darle bola a esto.
    else
    {
        echo("no lo supera :(");
        echo($item->id_str);
    }

}

//Envía mail a administrador con frases cargadas.
$i = 0;
$frasessize = sizeof($frases);

$mensaje = '<html><body>';
$mensaje .= '<h1>Nuevas frases cargadas:</h1></br></br></br>';


$mailto = '';
$fromName = "TwitterBot - DEV";
$mailsubject = '[TwitterBot] - Nueva consultas cargadas:';
$from = '';
// Set content-type header for sending HTML email 
$headers = "MIME-Version: 1.0" . "\r\n"; 
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n"; 
 
// Additional headers 
$headers .= 'From: '.$fromName.'<'.$from.'>' . "\r\n"; 
$headers .= 'Cc: ' . "\r\n"; 
$headers .= 'Bcc: ' . "\r\n";

$htmlContent = ' 
    <html> 
    <head> 
        <title>Welcome to CodexWorld</title> 
    </head> 
    <body> 
        <h1>Thanks you for joining with us!</h1> </br>
        <p> 
    '; 

for($i; $i < $frasessize; $i++){
    
    $htmlContent .= "<b>[".$i."]</b> - ".$frases[$i]."</br>";
    
}

$htmlContent = '</p><footer>Twitter.com/tostad0r - twitter.com/fumoporrobot</footer></br>
</body></html>';

//$mensaje .= '</br></br> <center><p style="color:red;">twitter.com/tostad0r</p></center> </body></html>';

if(mail($mailto, $mailsubject, $htmlContent, $headers)){
    echo 'Your mail has been sent successfully.';
} else{
    echo 'Unable to send email. Please try again.';
}

?>
