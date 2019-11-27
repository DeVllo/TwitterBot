<?php
session_start();
require_once("twitteroauth.php"); //Path to twitteroauth library
include('configsql.php');

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
 //Palabras o keywords a buscar. Pueden ser hashtags o palabras simplemente.
$search = "#fumoporrobot OR #fumoporro";
$notweets = 100;
/*  ======= ACÁ VAN SUS CREDENCIALES DE DEV.TWITTER.COM =======  */
$consumerkey = "";
$consumersecret = "";
$accesstoken = "";
$accesstokensecret = "";
/* === FUNCIONES BASE DEL oAuth.php ========= */
function getConnectionWithAccessToken($cons_key, $cons_secret, $oauth_token, $oauth_token_secret) {
  $connection = new TwitterOAuth($cons_key, $cons_secret, $oauth_token, $oauth_token_secret);
  return $connection;
}
 
$connection = getConnectionWithAccessToken($consumerkey, $consumersecret, $accesstoken, $accesstokensecret);
 
$search = str_replace("#", "%23", $search);
$tweets = $connection->get("https://api.twitter.com/1.1/search/tweets.json?q=".$search."&count=".$notweets);
 
/* ===== SELECCIONAMOS EL ID MÁXIMO DE LA BASE DE DATOS ======= */ 
if ($stmt = $mysqli->prepare("SELECT MAX(ID) FROM frases")) {
    $stmt->execute();
    $stmt->bind_result($max_tweetid);
    $stmt->fetch();
    $stmt->close();
}

echo("</br></br>el id maximo en la db es:".$max_tweetid."</br></br>"); 

$twitts = json_decode($tweets); // Decodificamos el search/tweets.json? etc... ($tweets)
$aborrar = "#fumoporro"; //Frase a borrar
$error = 0;
$frases = []; //Array de frases que se agregaron.
foreach($twitts->statuses as $item)
{
    
    if( $item->id_str >$max_tweetid)
    {
        $originalstring = strtolower($item->text); //Pasamos a minúscula la frase
        $stringa = str_replace($aborrar, "", $item->text); //Borramos el $aborrar
        $stringo = str_replace("bot","",$stringa); //Por si quedó la palabra "bot" en la frase, se elimina.
        $frasefinal = str_replace("palabraaeliminar", "", $stringo); //La frase a salir, sin filtros aún.
        
        $tweetID = (int)$item->id_str;
        
        $autorizada = 1;
        $publicada = 0;
        
        
        /* EXCEPCIONES Y CONDICIONES PARA QUE LAS FRASES NO TENGAN SPAM */
        
        $tieneSpam = 0;
        $search1 = 'http'; //Error 1.
        $search2 = 'RT @'; //Error 2.
        $search3 = 'sigo a'; //Error 3.
        $search4 = 'stan'; //Error 4.
        $search5 = 'stanneo'; //Error 5.
        $search6 = '@'; //Error 6.
        $search7 = '#'; //Error 7.
        $search8 = 'RT '; //Error 8.
        
        
        if( strpos( $frasefinal, $search1 ) !== false) {  $tieneSpam = 1;}

        if( strpos( $frasefinal, $search2 ) !== false)
        {
            $tieneSpam = 1;
        }
        
        if( strpos( $frasefinal, $search3 ) !== false)
        {
            $tieneSpam = 1;
        }
        
        if( strpos( $frasefinal, $search4 ) !== false)
        {
            $tieneSpam = 1;
        }
        
        if( strpos( $frasefinal, $search5 ) !== false)
        {
            $tieneSpam = 1;
        }
        
        if( strpos( $frasefinal, $search6 ) !== false)
        {
            $tieneSpam = 1;
        }
        
        if( strpos( $frasefinal, $search7 ) !== false)
        {
            $tieneSpam = 1;
        }
        
        if( strpos( $frasefinal, $search8 ) !== false)
        {
            $tieneSpam = 1;
        }
        
        if( strlen($frasefinal) < 2)
        {
            $tieneSpam = 1;
        }
        $autor = "@".$item->user->screen_name;

        if($tieneSpam == 0)
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
        //
        switch($error)
        {
            case 1:
            {
                echo("Por contener la frase: <p style='color:#732BF1;'>".$search1."</p></br>");
            }
            case 2:
            {
                echo("Por contener la frase: <p style='color:#732BF1;'>".$search2."</p></br>");
            }
            case 3:
            {
                echo("Por contener la frase: <p style='color:#732BF1;'>".$search3."</p></br>");
            }
            case 4:
            {
                echo("Por contener la frase: <p style='color:#732BF1;'>".$search4."</p></br>");
            }
            case 5:
            {
                echo("Por contener la frase: <p style='color:#732BF1;'>".$search5."</p>'</br>");
            }
            case 6:
            {
                echo("Por contener la frase: <p style='color:#732BF1;'>".$search6."</p></br>");
            }
            case 7:
            {
                echo("Por contener la frase: <p style='color:#732BF1;'>".$search7."</p></br>");
            }
            case 8:
            {
                echo("Por contener la frase: <p style='color:#732BF1;'>".$search8."</p></br>");
            }
        }
        
        //
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


$mailto = 'tu@corre.com';
$fromName = "TwitterBot - DEV";
$mailsubject = '[TwitterBot] - Nueva consultas cargadas:';
$from = 'correo@dequienloenvia.com';
// Set content-type header for sending HTML email 
$headers = "MIME-Version: 1.0" . "\r\n"; 
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n"; 
 
// Additional headers 
$headers .= 'From: '.$fromName.'<'.$from.'>' . "\r\n"; 
$headers .= 'Cc: correo@aresponder.com' . "\r\n"; 
$headers .= 'Bcc: correo@dequienloenvia.com' . "\r\n";

$contenidoMail = '<html><head><title>TwitterBot App </tittle>
</head>
<body>
<h1>Nuevas consultas generadas automáticamente:</h1></br>
<table>
<thead>
<tr>
<th> # </th>
<th> Frase </th>
</tr>
</thead>
<tbody>';

$htmlContent = ' 
    <html> 
    <head> 
        <title>TwitterBot DeV</title> 
    </head> 
    <body> 
        <h1>Nuevas consultas generadas automáticamente:</h1> </br>
        <p> 
    '; 
$contenidoMail .= '<tr><th> 999 </th>
                   <th> Frase de prueba </th>
                   </tr>';

for($i; $i < $frasessize; $i++){
    
    $htmlContent .= "<b>[".$i."]</b> - ".$frases[$i]."</br>";
    /*$contenidoMail .= '<tr>
                       <th>'.$i.'</th>
                       <th>'.$frases[$i].'</th>
                       </tr>'; */
    
}

$htmlContent .= '</p><footer><p><a href="https://twitter.com/tostad0r">tostad0r</a> - <a href="https://twitter.com/fumoporrobot">FumoPorroBot</a> ♥</footer></br>
</body></html>';


if(mail($mailto, $mailsubject, $htmlContent, $headers)){
    echo 'Your mail has been sent successfully.';
} else{
    echo 'Unable to send email. Please try again.';
}

?>
