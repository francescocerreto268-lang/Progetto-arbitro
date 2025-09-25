<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';

session_start(); // serve per usare la sessione


function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return !empty($token) 
        && !empty($_SESSION['csrf_token']) 
        && hash_equals($_SESSION['csrf_token'], $token);
}


$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF non valido.';
    } else {
        $nome = trim($_POST['nome']);
        $cognome = trim($_POST['cognome']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $password2 = $_POST['password2'];
        $sezione = trim($_POST['sezione']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email non valida.';
        } elseif (strlen($password) < 6) {
            $error = 'Password troppo corta (min 6 caratteri).';
        } elseif ($password !== $password2) {
            $error = 'Le password non coincidono.';
        } else {
            // controllo email unica
            $stmt = $conn->prepare("SELECT id FROM utente WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res->num_rows > 0) {
                $error = 'Email già registrata.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO utente (nome, cognome, email, password, sezione) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $nome, $cognome, $email, $hash, $sezione);
                if ($stmt->execute()) {
                    header("Location: login.php?registered=1");
                    exit;
                } else {
                    $error = 'Errore durante la registrazione.';
                }
            }
        }
        $conn->close();
    }
}

$csrf = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Registrazione utente</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        form { max-width: 400px; padding: 20px; border: 1px solid #ccc; border-radius: 8px; }
        label { display: block; margin-top: 10px; }
        input { width: 100%; padding: 8px; margin-top: 4px; }
        button { margin-top: 15px; padding: 10px; width: 100%; }
        .error { color: red; margin-top: 10px; }
    </style>
</head>
<body>
    <h2>Registrati</h2>

    <?php if($error): ?>
        <p class="error"><?= esc($error) ?></p>
    <?php endif; ?>

    <form method="post" action="">
        <input type="hidden" name="csrf_token" value="<?= esc($csrf) ?>">

        <label>Nome</label>
        <input name="nome" required>

        <label>Cognome</label>
        <input name="cognome" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <label>Conferma password</label>
        <input type="password" name="password2" required>

        <label>Sezione</label>
        <input name="sezione">

        <button type="submit">Registrati</button>
    </form>
</body>
</html>
