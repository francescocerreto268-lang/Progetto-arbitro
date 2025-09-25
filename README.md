<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';

session_start();

// Funzioni utili
function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Variabile per errori
$error = "";

// Gestione login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Recupero utente dal DB
    $stmt = $conn->prepare("SELECT id, nome, cognome, password FROM utente WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 1) {
        $row = $res->fetch_assoc();
        // Controllo password
        if (password_verify($password, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['loggedin'] = true;
            $_SESSION['id'] = $row['id'];
            $_SESSION['nome'] = $row['nome'];
            $_SESSION['cognome'] = $row['cognome'];

            header("Location: index.php");
            exit;
        } else {
            $error = "Password errata.";
        }
    } else {
        $error = "Utente non trovato.";
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Login - Arbitro App</title>
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
    <h2>Login</h2>

    <?php if ($error): ?>
        <p class="error"><?= esc($error) ?></p>
    <?php endif; ?>

    <form method="post" action="">
        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit">Accedi</button>
    </form>

    <p>Non hai un account? <a href="register.php">Registrati qui</a></p>
</body>
</html>
