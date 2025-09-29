<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'db.php';

// 🔒 Controllo accesso
if (empty($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit;
}

$arbitro_id = $_SESSION['id'];

// ✅ Gestione eliminazione partita via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['elimina_id'])) {
    $id = (int) $_POST['elimina_id'];

    $stmt = $conn->prepare("DELETE FROM partite WHERE id = ? AND arbitro_id = ?");
    $stmt->bind_param("ii", $id, $arbitro_id);
    $stmt->execute();

    // Risposta semplice al fetch
    echo "ok";
    exit; // importante, interrompe il resto della pagina
}

// Recupero partite dell'arbitro con i risultati se disponibili
$partite = $conn->query("
    SELECT p.*, dp.gol_casa, dp.gol_ospite 
    FROM partite p 
    LEFT JOIN dati_partite dp ON p.id = dp.partita_id 
    WHERE p.arbitro_id = $arbitro_id 
    ORDER BY p.id DESC
");

// Calcola la somma totale dei rimborsi
$totale_rimborsi = $conn->query("
    SELECT SUM(rimborso) as totale 
    FROM partite 
    WHERE arbitro_id = $arbitro_id
")->fetch_assoc();

$totale = $totale_rimborsi['totale'] ?? 0;

// Cartellini
$cartellini_gialli = $conn->query("
    SELECT * 
    FROM eventi_partita ep
    JOIN partite p ON ep.partita_id = p.id 
    WHERE ep.tipo_evento ='giallo' AND p.arbitro_id = $arbitro_id
")->fetch_all();
$cartellini_rossi = $conn->query("
    SELECT * 
    FROM eventi_partita ep
    JOIN partite p ON ep.partita_id = p.id 
    WHERE ep.tipo_evento ='rosso' AND p.arbitro_id = $arbitro_id
")->fetch_all();

$numero_cartellini_gialli = sizeof($cartellini_gialli);
$numero_parite = $partite->num_rows;
$numero_cartellini_rossi = sizeof($cartellini_rossi);

$media_gialli = $numero_parite > 0 ? $numero_cartellini_gialli / $numero_parite : 0;
$media_rossi = $numero_parite > 0 ? $numero_cartellini_rossi / $numero_parite : 0;

// Funzione per sanificare output
function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Le mie partite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">

<h2>Le mie partite</h2>

<a href="index.php" class="btn btn-outline-secondary mb-3">🔙 Torna alla home</a>
<a href="add_partite.php" class="btn btn-primary mb-3">➕ Aggiungi partita</a>

<div class="alert alert-info mb-3">
    <h5>Totale rimborsi : <strong><?= esc(number_format($totale, 2)) ?> €</strong></h5>
    <h5>Media gialli : <strong><?= esc(number_format($media_gialli, 2)) ?></strong></h5>
    <h5>Media rossi : <strong><?= esc(number_format($media_rossi, 2)) ?></strong></h5>
</div>

<table class="table table-bordered">
    <thead>
        <tr>
            <th>ID</th>
            <th>Squadra Casa</th>
            <th>Squadra Ospite</th>
            <th>Risultato</th>
            <th>Indirizzo</th>
            <th>Rimborso</th>
            <th>Km Percorsi</th>
            <th>Data e ora</th>
            <th>Azioni</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($partite->num_rows > 0): ?>
            <?php while ($partita = $partite->fetch_assoc()): ?>
                <tr id="row-<?= esc($partita['id']) ?>">
                    <td><?= esc($partita['id']) ?></td>
                    <td><?= esc($partita['s_casa']) ?></td>
                    <td><?= esc($partita['s_ospite']) ?></td>
                    <td>
                        <?php if (!empty($partita['gol_casa']) || !empty($partita['gol_ospite'])): ?>
                            <strong><?= esc($partita['gol_casa'] ?? 0) ?> - <?= esc($partita['gol_ospite'] ?? 0) ?></strong>
                        <?php else: ?>
                            <span class="text-muted">Non disputata</span>
                        <?php endif; ?>
                    </td>
                    <td><?= esc($partita['indirizzo']) ?></td>
                    <td><?= esc($partita['rimborso']) ?> €</td>
                    <td><?= esc($partita['km_percorsi']) ?> km</td>
                    <td><?= esc($partita['data_ora_partita']) ?></td>
                    <td>
                        <a href="resoconto_partita.php?id=<?= $partita['id'] ?>" class="btn btn-sm btn-info">📊 Resoconto</a>
                        <a href="resoconto_partita.php?id=<?= $partita['id'] ?>" class="btn btn-sm btn-warning">✏️ Modifica</a>
                        <button class="btn btn-sm btn-danger btn-elimina" data-id="<?= $partita['id'] ?>">🗑 Elimina</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="9" class="text-center">Nessuna partita trovata.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<script>
document.querySelectorAll('.btn-elimina').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        if (confirm('Eliminare questa partita?')) {
            const formData = new FormData();
            formData.append('elimina_id', id);

            fetch('partite.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(data => {
                if (data.trim() === 'ok') {
                    alert('Partita eliminata!');
                    document.getElementById('row-' + id).remove();
                } else {
                    alert('Errore durante l\'eliminazione');
                }
            })
            .catch(err => alert('Errore: ' + err));
        }
    });
});
</script>

</body>
</html>
