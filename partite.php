<?php
session_start();

// 🔒 Controllo accesso
if (empty($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit;
}

// Connessione DB
include 'db.php';

// Funzione per sanificare output
function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ID arbitro loggato
$arbitro_id = (int)($_SESSION['id'] ?? 0);

// Recupero partite dell'arbitro loggato
$sql = "SELECT p.id, p.s_casa, p.s_ospite, p.indirizzo, p.rimborso, p.km_percorsi,
               u.nome AS arbitro_nome, u.cognome AS arbitro_cognome
        FROM partite p
        LEFT JOIN utente u ON p.arbitro_id = u.id
        WHERE p.arbitro_id = $arbitro_id
        ORDER BY p.id DESC";

$result = $conn->query($sql);

// Calcolo totale rimborso
$sql_totale = "SELECT SUM(rimborso) AS totale_rimborso FROM partite WHERE arbitro_id = $arbitro_id";
$res_totale = $conn->query($sql_totale);
$totale_rimborso = $res_totale->fetch_assoc()['totale_rimborso'] ?? 0;
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lista Partite</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index.php">Arbitro App</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link active" href="partite.php">Partite</a></li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout (<?= esc($_SESSION['nome'] ?? '') ?>)</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Container principale -->
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Lista partite</h2>
        <a href="add_partite.php" class="btn btn-success">➕ Aggiungi nuova partita</a>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-primary">
                <tr>
                    <th>ID</th>
                    <th>Squadra Casa</th>
                    <th>Squadra Ospite</th>
                    <th>Indirizzo</th>
                    <th>Rimborso</th>
                    <th>Km percorsi</th>
                    <th>Arbitro</th>
                    <th>Resoconto</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= esc($row['id']) ?></td>
                            <td><?= esc($row['s_casa']) ?></td>
                            <td><?= esc($row['s_ospite']) ?></td>
                            <td><?= esc($row['indirizzo']) ?></td>
                            <td><?= esc($row['rimborso']) ?> €</td>
                            <td><?= esc($row['km_percorsi']) ?> km</td>
                            <td><?= esc($row['arbitro_nome'] . " " . $row['arbitro_cognome']) ?></td>
                            <td>
                                <a href="resoconto_partita.php?id=<?= esc($row['id']) ?>" class="btn btn-info btn-sm">
                                    📄 Visualizza / Aggiungi
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">Nessuna partita trovata.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Totale rimborso -->
        <div class="mt-3">
            <h5>💰 Totale rimborso: <?= esc(number_format($totale_rimborso, 2)) ?> €</h5>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="bg-light text-center text-muted py-3 mt-5">
    &copy; <?= date('Y') ?> Arbitro App
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
