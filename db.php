<?php
$host = "localhost";
$user = "root";   // cambia con il tuo utente MySQL
$pass = "";       // cambia con la tua password MySQL
$db   = "Arbitro.db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
?>
