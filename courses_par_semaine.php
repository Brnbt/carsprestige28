<?php
// courses_par_semaine.php — Affichage uniquement
// Dépend de _fonctions.inc.php pour la fonction getCoursesGroupedByWeek()

include_once 'affichage/_debut.inc.php';
header('X-Content-Type-Options: nosniff');

// Récup clients pour le filtre
$clients  = getAllClient();

// Paramètres GET
$today = new DateTimeImmutable('today');
$defaultTo   = $today->format('Y-m-d');
// par défaut: 12 semaines en arrière
$defaultFrom = $today->sub(new DateInterval('P84D'))->format('Y-m-d');

$from     = isset($_GET['from']) ? preg_replace('~[^0-9\-]~', '', $_GET['from']) : $defaultFrom;
$to       = isset($_GET['to'])   ? preg_replace('~[^0-9\-]~', '', $_GET['to'])   : $defaultTo;
$clientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
$limit    = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 52;

if (!function_exists('getCoursesGroupedByWeek')) {
    die("La fonction getCoursesGroupedByWeek() est manquante dans _fonctions.inc.php");
}

$rows = getCoursesGroupedByWeek($from, $to, $clientId ?: null, $limit);

// totaux globaux (sur la période)
$totCourses = array_sum(array_column($rows, 'nb_courses'));
$totKm      = array_sum(array_map(fn($r)=>(float)$r['total_km'], $rows));
$totPrix    = array_sum(array_map(fn($r)=>(float)$r['total_prix'], $rows));
?>
<style>
  :root { --bg:#0f172a; --panel:#111827; --muted:#9ca3af; --text:#e5e7eb; --accent:#22c55e; }
  html,body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,"Helvetica Neue",Arial,sans-serif;background:var(--bg);color:var(--text)}
  .wrap{max-width:1100px;margin:24px auto;padding:0 16px}
  h1{font-size:clamp(1.2rem,2.5vw,1.6rem);margin:0 0 12px 0}
  .card{background:linear-gradient(180deg,rgba(255,255,255,.05),transparent 60%) ,var(--panel);border:1px solid rgba(255,255,255,.08);border-radius:14px;box-shadow:0 10px 24px rgba(0,0,0,.25)}
  .head{display:flex;gap:12px;flex-wrap:wrap;align-items:center;justify-content:space-between;padding:16px}
  .filters{display:flex;gap:12px;flex-wrap:wrap;align-items:center}
  .select, .inp{appearance:none;background:#0b1220;border:1px solid rgba(255,255,255,.1);color:var(--text);border-radius:10px;padding:10px 12px}
  .btn{background:var(--accent);color:#052e16;border:none;border-radius:10px;padding:10px 14px;font-weight:600;cursor:pointer}
  .table-wrap{overflow:auto}
  table{border-collapse:collapse;width:100%}
  th,td{padding:12px 10px;border-bottom:1px solid rgba(255,255,255,.08);white-space:nowrap}
  th{text-align:left;font-size:.9rem;color:var(--muted);position:sticky;top:0;background:var(--panel)}
  tr:hover{background:rgba(255,255,255,.04)}
  .muted{color:var(--muted)}
  .badge{display:inline-block;padding:4px 8px;border-radius:999px;font-size:.8rem;border:1px solid rgba(255,255,255,.15)}
  .foot{padding:12px 16px;color:var(--muted);font-size:.9rem;display:flex;justify-content:space-between;gap:8px;align-items:center}
  .nores{padding:24px;text-align:center;color:var(--muted)}
  .kpis{display:flex;gap:12px;flex-wrap:wrap}
  .kpi{background:#0b1220;border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:10px 12px}
  .kpi .v{font-weight:700}
</style>

<div class="wrap">
  <h1>Courses par semaine</h1>

  <div class="card">
    <form class="head" method="get" action="">
      <div class="filters">
        <label class="muted" for="from">Période</label>
        <input id="from" name="from" type="date" class="inp" value="<?= htmlspecialchars($from, ENT_QUOTES) ?>">
        <span class="muted">→</span>
        <input id="to"   name="to"   type="date" class="inp" value="<?= htmlspecialchars($to, ENT_QUOTES) ?>">

        <label class="muted" for="client">Client</label>
        <select id="client" name="client_id" class="select">
          <option value="">— Tous —</option>
          <?php foreach ($clients as $cl):
            $id    = (int)($cl['id_client'] ?? $cl['id'] ?? 0);
            $nom   = $cl['nom'] ?? '';
            $pre   = $cl['prenom'] ?? '';
            $tel   = $cl['telephone'] ?? '';
            $label = trim($nom.' '.$pre.($tel?" · ".$tel:''));
          ?>
            <option value="<?= $id ?>" <?= $id === $clientId ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>

        <input type="hidden" name="limit" value="<?= (int)$limit ?>">
        <button class="btn" type="submit">Appliquer</button>
      </div>

      <div class="kpis">
        <div class="kpi"><span class="muted">Semaines :</span> <span class="v"><?= count($rows) ?></span></div>
        <div class="kpi"><span class="muted">Courses :</span> <span class="v"><?= (int)$totCourses ?></span></div>
        <div class="kpi"><span class="muted">Km :</span> <span class="v"><?= number_format((float)$totKm, 2, ',', ' ') ?></span></div>
        <div class="kpi"><span class="muted">CA (€) :</span> <span class="v"><?= number_format((float)$totPrix, 2, ',', ' ') ?></span></div>
      </div>
    </form>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Semaine</th>
            <th>Période</th>
            <th>Nb courses</th>
            <th>Total km</th>
            <th>Total €</th>
            <th>Détails</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!count($rows)): ?>
            <tr><td colspan="6" class="nores">Aucune donnée sur la période.</td></tr>
          <?php else:
            foreach ($rows as $r):
              $label = htmlspecialchars($r['yw'], ENT_QUOTES); // ex: 2025W32
              $per   = htmlspecialchars(date('d/m/Y', strtotime($r['week_start'])) . ' — ' . date('d/m/Y', strtotime($r['week_end'])), ENT_QUOTES);
              $nb    = (int)$r['nb_courses'];
              $km    = number_format((float)$r['total_km'], 2, ',', ' ');
              $eur   = number_format((float)$r['total_prix'], 2, ',', ' ');
              // lien “voir les courses de la semaine” — à toi d’ajuster la cible si besoin
              $detailUrl = 'courses_par_client.php?client_id='.(int)$clientId
                           .'&q=' // tu peux passer un q pré-rempli si tu veux filtrer côté page client
                           .'&limit=1000';
          ?>
            <tr>
              <td><span class="badge"><?= $label ?></span></td>
              <td><?= $per ?></td>
              <td><?= $nb ?></td>
              <td><?= $km ?></td>
              <td><?= $eur ?></td>
              <td><a class="btn link" href="<?= htmlspecialchars($detailUrl, ENT_QUOTES) ?>">Voir</a></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <div class="foot">
      <span class="muted">Agrégation par semaine ISO (lundi → dimanche). Ajuste la période ci-dessus.</span>
      <span></span>
    </div>
  </div>
</div>
