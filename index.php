<?php
include 'db.php';

// Messaggio di stato
$message = "";

// Inserimento partita
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === "inserisci") {
    $s_casa = $_POST['s_casa'];
    $s_ospite = $_POST['s_ospite'];
    $indirizzo = $_POST['indirizzo'];
    $rimborso = $_POST['rimborso'];
    $km = $_POST['km'];

    $sql = "INSERT INTO partite (s_casa, s_ospite, indirizzo, rimborso, km_percorsi)
            VALUES ('$s_casa', '$s_ospite', '$indirizzo', '$rimborso', '$km')";

    if ($conn->query($sql) === TRUE) {
        $message = "✅ Partita inserita con successo!";
    } else {
        $message = "❌ Errore: " . $conn->error;
    }
}

// Recupera partite
$sql = "SELECT * FROM partite ORDER BY id DESC";
$result = $conn->query($sql);

// Vista attiva (default = lista)
$page = isset($_GET['page']) ? $_GET['page'] : "lista";
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Partite</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 0;
        }
        header {
            background: #007BFF;
            color: white;
            padding: 15px;
            text-align: center;
        }
        nav {
            background: #0056b3;
            padding: 10px;
            text-align: center;
        }
        nav a {
            color: white;
            margin: 0 15px;
            text-decoration: none;
            font-weight: bold;
        }
        nav a:hover {
            text-decoration: underline;
        }
        .container {
            padding: 20px;
            max-width: 900px;
            margin: auto;
        }
        h2 {
            color: #333;
            text-align: center;
        }
        .message {
            text-align: center;
            font-weight: bold;
            margin: 15px 0;
        }
        .success { color: green; }
        .error { color: red; }

        /* Form */
        form label {
            display: block;
            margin: 10px 0 5px;
            color: #555;
        }
        form input[type="text"],
        form input[type="number"] {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            outline: none;
            transition: border 0.3s;
        }
        form input[type="text"]:focus,
        form input[type="number"]:focus {
            border: 1px solid #007BFF;
        }
        form input[type="submit"] {
            margin-top: 15px;
            width: 100%;
            padding: 10px;
            background: #007BFF;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }
        form input[type="submit"]:hover {
            background: #0056b3;
        }

        /* Tabella */
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        th, td {
            padding: 12px 15px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #007BFF;
            color: #fff;
        }
        tr:hover {
            background: #f1f1f1;
        }
    </style>
</head>
<body>

<header>
    <h1>Gestione Partite Arbitro</h1>
</header>

<nav>
    <a href="index.php?page=lista">📋 Lista Partite</a>
    <a href="index.php?page=inserisci">➕ Inserisci Partita</a>
</nav>

<div class="container">

    <?php if (!empty($message)): ?>
        <p class="message <?php echo (strpos($message, '✅') !== false) ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </p>
    <?php endif; ?>

    <?php if ($page === "inserisci"): ?>
        <!-- FORM INSERIMENTO -->
        <h2>Inserisci una nuova partita</h2>
        <form method="post">
            <input type="hidden" name="action" value="inserisci">

            <label>Squadra Casa:</label>
            <input type="text" name="s_casa" required>

            <label>Squadra Ospite:</label>
            <input type="text" name="s_ospite" required>

            <label>Indirizzo:</label>
            <input type="text" name="indirizzo" required>

            <label>Rimborso (€):</label>
            <input type="number" step="0.01" name="rimborso" required>

            <label>Km Percorsi:</label>
            <input type="number" step="0.1" name="km" required>

            <input type="submit" value="Inserisci Partita">
        </form>

    <?php else: ?>
        <!-- LISTA PARTITE -->
        <h2>Elenco Partite Inserite</h2>
        <?php if ($result->num_rows > 0): ?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Squadra Casa</th>
                    <th>Squadra Ospite</th>
                    <th>Indirizzo</th>
                    <th>Rimborso (€)</th>
                    <th>Km Percorsi</th>
                </tr>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['s_casa']); ?></td>
                    <td><?php echo htmlspecialchars($row['s_ospite']); ?></td>
                    <td><?php echo htmlspecialchars($row['indirizzo']); ?></td>
                    <td><?php echo number_format($row['rimborso'], 2, ',', '.'); ?></td>
                    <td><?php echo $row['km_percorsi']; ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p class="message error">❌ Nessuna partita trovata.</p>
        <?php endif; ?>
    <?php endif; ?>

</div>

</body>
</html>
