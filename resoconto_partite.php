<?php
session_start();
include 'db.php';

// 🔒 Controllo accesso
if (empty($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit;
}

// Funzione per sanificare output
function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Generazione token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Recupero partita
$partita_id = (int)($_GET['id'] ?? 0);
$partita = $conn->query("SELECT * FROM partite WHERE id=$partita_id")->fetch_assoc();
if (!$partita) die("Partita non trovata.");

// Gestione aggiunta o eliminazione evento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Token CSRF non valido.");
    }

    // Eliminazione evento
    if (!empty($_POST['delete_event_id'])) {
        $del_id = (int)$_POST['delete_event_id'];
        $conn->query("DELETE FROM eventi_partita WHERE id=$del_id");
        header("Location: resoconto_partita.php?id=$partita_id");
        exit;
    }

    // Aggiunta nuovo evento
    if (!empty($_POST['tipo_evento']) && !empty($_POST['minuto']) && !empty($_POST['giocatore'])) {
        $tipo = $conn->real_escape_string($_POST['tipo_evento']);
        $minuto = (int)$_POST['minuto'];
        $giocatore = $conn->real_escape_string($_POST['giocatore']);
        
        // Km della partita (solo una volta)
        $km = isset($_POST['km_partita']) ? (float)$_POST['km_partita'] : 0;

        $conn->query("INSERT INTO eventi_partita (tipo_evento, minuto, giocatore, partita_id, km_partita)
                      VALUES ('$tipo', $minuto, '$giocatore', $partita_id, $km)");
        header("Location: resoconto_partita.php?id=$partita_id");
        exit;
    }
}

// Recupero eventi
$eventi = $conn->query("SELECT * FROM eventi_partita WHERE partita_id=$partita_id ORDER BY minuto ASC");

// Calcolo media km percorsi per tutte le partite dell'arbitro
$media_res = $conn->query("
    SELECT AVG(km_partita) AS media_km
    FROM eventi_partita
    WHERE partita_id IN (
        SELECT id FROM partite WHERE arbitro_id = ".$_SESSION['id']."
    )
");
$media_km = 0;
if ($media_res && $row = $media_res->fetch_assoc()) {
    $media_km = (float)$row['media_km'];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Resoconto partita</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">

<h2>Resoconto partita: <?= esc($partita['s_casa'] . " vs " . $partita['s_ospite']) ?></h2>

<!-- Pulsante torna a partite -->
<a href="partite.php" class="btn btn-outline-secondary mb-3">🔙 Torna a partite</a>

<!-- Mostra media km -->
<p><strong>Media km percorsi (tutte le partite):</strong> <?= esc(number_format($media_km, 2)) ?> km</p>

<!-- Aggiungi evento -->
<h4>Aggiungi evento</h4>
<form method="post" class="mb-4">
<input type="hidden" name="csrf_token" value="<?= esc($_SESSION['csrf_token']) ?>">
<div class="row g-2">
    <div class="col-md-2">
        <input type="text" name="giocatore" class="form-control" placeholder="Giocatore" required>
    </div>
    <div class="col-md-2">
        <input type="text" name="tipo_evento" class="form-control" placeholder="Tipo evento" required>
    </div>
    <div class="col-md-2">
        <input type="number" name="minuto" class="form-control" placeholder="Minuto" required>
    </div>
    <div class="col-md-2">
        <input type="number" step="0.01" name="km_partita" class="form-control" placeholder="Km partita">
        <small class="text-muted">Inserire solo una volta per partita</small>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-success">➕ Aggiungi</button>
    </div>
</div>
</form>

<!-- Eventi partita -->
<h4>Eventi partita</h4>
<table class="table table-bordered">
<thead>
<tr>
<th>Minuto</th>
<th>Giocatore</th>
<th>Tipo Evento</th>
<th>Km partita</th>
<th>Azioni</th>
</tr>
</thead>
<tbody>
<?php if ($eventi->num_rows > 0): ?>
    <?php while ($ev = $eventi->fetch_assoc()): ?>
        <tr>
            <td><?= esc($ev['minuto']) ?></td>
            <td><?= esc($ev['giocatore']) ?></td>
            <td><?= esc($ev['tipo_evento']) ?></td>
            <td><?= esc($ev['km_partita']) ?> km</td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= esc($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="delete_event_id" value="<?= $ev['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Eliminare questo evento?');">🗑 Elimina</button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
<tr><td colspan="5" class="text-center">Nessun evento registrato.</td></tr>
<?php endif; ?>
</tbody>
</table>

</body>
</html>
