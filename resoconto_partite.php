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

// Funzione per sanificare output
function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Generazione token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
           && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Recupero partita
$partita_id = (int)($_GET['id'] ?? 0);

$partita = $conn->query("SELECT * FROM partite WHERE id=$partita_id")->fetch_assoc();

if (!$partita) die("Partita non trovata.");

// Recupero dati della partita (se esistono già)
$dati = $conn->query("SELECT * FROM dati_partite WHERE partita_id=$partita_id")->fetch_assoc();
$error = "";

// Gestione POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Token CSRF non valido.");
    }

    // Salvataggio/aggiornamento dati partita
    if (isset($_POST['salva_dati'])) {
        $gol_casa   = (int)$_POST['gol_casa'];
        $gol_ospite = (int)$_POST['gol_ospite'];
        $km         = (float)$_POST['km_percorsi'];
        $meteo      = $conn->real_escape_string($_POST['meteo']);
        $note       = $conn->real_escape_string($_POST['note']);

        if ( $km < 0 ) {
            $error = "Il campo km deve essere maggiore di zero";
        } else {
            if ($dati) {
                // UPDATE
                $conn->query("UPDATE dati_partite 
                              SET gol_casa=$gol_casa, gol_ospite=$gol_ospite, 
                                  km_percorsi=$km, meteo='$meteo', note='$note'
                              WHERE partita_id=$partita_id");
            } else {
                // INSERT
                $conn->query("INSERT INTO dati_partite 
                              (partita_id, gol_casa, gol_ospite, km_percorsi, meteo, note)
                              VALUES ($partita_id, $gol_casa, $gol_ospite, $km, '$meteo', '$note')");
            }
            header("Location: resoconto_partita.php?id=$partita_id");
            exit;
        }
    }

    // Eliminazione evento
    // Aggiunta nuovo evento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tipo_evento'], $_POST['minuto'], $_POST['giocatore'], $_POST['tempo'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        if($is_ajax){
            echo json_encode(['success'=>false,'msg'=>'Token CSRF non valido']);
            exit;
        } else {
            die("Token CSRF non valido");
        }
    }

    $tipo = $conn->real_escape_string(trim($_POST['tipo_evento']));
    $minuto = (int)$_POST['minuto'];
    $giocatore = (int)$_POST['giocatore'];
    $tempo = (int)$_POST['tempo'];

    // Validazioni
    if ($minuto <= 0 || $giocatore <= 0 || !in_array($tipo,['gol','giallo','rosso','altro']) || !in_array($tempo,[1,2])) {
        $msg = 'Controlla tutti i campi: minuto e giocatore > 0, tipo evento e tempo validi';
        if($is_ajax){
            echo json_encode(['success'=>false,'msg'=>$msg]);
            exit;
        } else {
            $error = $msg;
        }
    } else {
        $sql = "INSERT INTO eventi_partita (tipo_evento, minuto, giocatore, partita_id, tempo)
                VALUES ('$tipo',$minuto,$giocatore,$partita_id,$tempo)";

        if (!$conn->query($sql)) {
            $msg = "Errore MySQL: " . $conn->error;
            if($is_ajax){
                echo json_encode(['success'=>false,'msg'=>$msg]);
                exit;
            } else {
                $error = $msg;
            }
        } else {
            if($is_ajax){
                // Restituisci anche dati evento per aggiornare tabella senza reload
                echo json_encode([
                    'success'=>true,
                    'msg'=>'Evento aggiunto',
                    'evento'=>[
                        'id'=>$conn->insert_id,
                        'minuto'=>$minuto,
                        'giocatore'=>$giocatore,
                        'tipo_evento'=>$tipo,
                        'tempo'=>$tempo
                    ]
                ]);
                exit;
            } else {
                header("Location: resoconto_partita.php?id=$partita_id");
                exit;
            }
        }
    }
}

}

// Recupero eventi
$eventi = $conn->query("SELECT * FROM eventi_partita WHERE partita_id=$partita_id ORDER BY minuto ASC");

// Controllo se i km sono già stati inseriti
$km_inseriti = false;
$km_sql = $conn->query("SELECT km_percorsi FROM dati_partite WHERE partita_id=$partita_id AND km_percorsi > 0 LIMIT 1");
if ($km_sql && $km_sql->num_rows > 0) {
    $km_inseriti = true;
    $row_km = $km_sql->fetch_assoc();
    $km_percorsi = $row_km['km_percorsi'];
} else {
    $km_percorsi = null;
}

// Calcolo media km percorsi per tutte le partite dell'arbitro
$media_res = $conn->query("
    Select AVG(dp.km_percorsi) as media_km
    From dati_partite dp
    Join partite p on dp.partita_id = p.id WHERE p.arbitro_id = ".$_SESSION['id']." 
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="p-4">

<h2>Resoconto partita: <?= esc($partita['s_casa'] . " vs " . $partita['s_ospite']) ?></h2>

<a href="partite.php" class="btn btn-outline-secondary mb-3">🔙 Torna a partite</a>

<?php if ($error): ?>
<div class="alert alert-danger"><?= esc($error) ?></div>
<?php endif; ?>

<!-- Form dati partita -->
<h4>Dati Partita</h4>
<form method="post" class="mb-4">
    <input type="hidden" name="csrf_token" value="<?= esc($_SESSION['csrf_token']) ?>">
    <div class="row g-3">
        <div class="col-md-2">
            <label for="gol_casa" class="form-label">Gol Casa</label>
            <input type="number" name="gol_casa" class="form-control" value="<?= esc($dati['gol_casa'] ?? '') ?>" required>
        </div>
        <div class="col-md-2">
            <label for="gol_ospite" class="form-label">Gol Ospite</label>
            <input type="number" name="gol_ospite" class="form-control" value="<?= esc($dati['gol_ospite'] ?? '') ?>" required>
        </div>
        <div class="col-md-2">
            <label for="km_percorsi" class="form-label">Km Percorsi</label>
            <input type="number" step="0.01" name="km_percorsi" class="form-control" value="<?= esc($dati['km_percorsi'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
            <label for="meteo" class="form-label">Meteo</label>
            <input type="text" name="meteo" class="form-control" value="<?= esc($dati['meteo'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
            <label for="note" class="form-label">Note</label>
            <input type="text" name="note" class="form-control" value="<?= esc($dati['note'] ?? '') ?>">
        </div>
        <div class="col-12">
            <button type="submit" name="salva_dati" class="btn btn-primary">💾 Salva Dati Partita</button>
        </div>
    </div>
</form>

<p><strong>Media km percorsi (tutte le partite):</strong> <?= esc(number_format($media_km, 2)) ?> km</p>

<!-- Aggiungi evento -->
<h4>Aggiungi evento</h4>
<form method="post" id="form-evento" class="mb-4">
    <input type="hidden" name="csrf_token" value="<?= esc($_SESSION['csrf_token']) ?>">
    <div class="row g-2">
        <div class="col-md-2">
            <input type="number" name="giocatore" class="form-control" placeholder="Numero maglia" required min=0 max=99>
            <small class="text-muted">Numero maglia</small>
        </div>
        <div class="col-md-2">
            <select name="tipo_evento" class="form-control" required>
                <option value="">Seleziona tipo</option>
                <option value="gol">Gol</option>
                <option value="giallo">Giallo</option>
                <option value="rosso">Rosso</option>
                <option value="altro">Altro</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="number" name="minuto" class="form-control" placeholder="Minuto" required min=1 max=120>
        </div>
        <div class="col-md-2">
            <select name="tempo" class="form-control" required>
                <option value="1">1° Tempo</option>
                <option value="2">2° Tempo</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-success">💾 Aggiungi Evento</button>
            
        </div>
    </div>
</form>

<!-- Tabella eventi -->
<h4>Eventi partita</h4>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Minuto</th>
            <th>Tempo</th>
            <th>Giocatore</th>
            <th>Tipo</th>
            <th>Azioni</th>
        </tr>
    </thead>
    <tbody id="eventi-table">
        <?php while ($ev = $eventi->fetch_assoc()): ?>
        <tr id="evento-<?= esc($ev['id']) ?>">
            <td><?= esc($ev['minuto']) ?></td>
            <td><?= esc($ev['tempo']) ?>°</td>
            <td><?= esc($ev['giocatore']) ?></td>
            <td><?= esc($ev['tipo_evento']) ?></td>
            <td>
                <button class="btn btn-danger btn-sm btn-delete" data-id="<?= esc($ev['id']) ?>">🗑️</button>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<script>
$(document).ready(function(){

    // Elimina evento
    $(document).on('click', '.btn-delete', function(){
        if(!confirm('Confermi eliminazione evento?')) return;
        let id = $(this).data('id');
        $.post('', {delete_event_id: id, csrf_token: '<?= esc($_SESSION['csrf_token']) ?>'}, function(res){
            try {
                let r = typeof res === 'string' ? JSON.parse(res) : res;
                if(r.success){
                    $('#evento-' + id).fadeOut();
                } else {
                    alert('Errore: ' + r.msg);
                }
            } catch(e) { console.error(e); }
        });
    });

    // Aggiungi evento
    $('#form-evento').submit(function(e){
        e.preventDefault();
        $.post('', $(this).serialize(), function(res){
            try {
                let r = typeof res === 'string' ? JSON.parse(res) : res;
                if(r.success){
                    // aggiungi riga nuova in tabella senza reload
                    let ev = r.evento;
                    let row = `<tr id="evento-${ev.id}">
                        <td>${ev.minuto}</td>
                        <td>${ev.tempo}°</td>
                        <td>${ev.giocatore}</td>
                        <td>${ev.tipo_evento}</td>
                        <td><button class="btn btn-danger btn-sm btn-delete" data-id="${ev.id}">🗑️</button></td>
                    </tr>`;
                    $('#eventi-table').append(row);
                    $('#form-evento')[0].reset();
                } else {
                    alert('Errore: ' + r.msg);
                }
            } catch(e) { console.error(e); }
        });
    });

});
