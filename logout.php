<?php
// Avvia la sessione
session_start();

// Svuota tutte le variabili di sessione
$_SESSION = [];

// Elimina il cookie della sessione se esiste
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        isset($params['path']) ? $params['path'] : '/',
        isset($params['domain']) ? $params['domain'] : '',
        isset($params['secure']) ? $params['secure'] : false,
        isset($params['httponly']) ? $params['httponly'] : false
    );
}

// Distrugge la sessione sul server
session_destroy();

// Reindirizza alla pagina principale
header('Location: index.php');
exit;
?>

