<?php
// courses_par_client.php — Affichage uniquement (aucune fonction ici)
// Dépend de _fonctions.inc.php pour TOUTES les fonctions (connexion + requêtes)
// Nécessite que _fonctions.inc.php expose:
//   - getAllClient()
//   - getCoursesByClient(int $clientId, int $limit = 1000)

include_once 'affichage/_debut.inc.php';

header('X-Content-Type-Options: nosniff');

$clients  = getAllClient();
$clientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
$limit    = isset($_GET['limit']) ? (int)$_GET['limit'] : 500;
$q        = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

$rows = [];
if ($clientId > 0) {
    if (!function_exists('getCoursesByClient')) {
        die("La fonction getCoursesByClient() est manquante dans _fonctions.inc.php");
    }
    $rows = getCoursesByClient($clientId, $limit);
}
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
  .btn:disabled{opacity:.6;cursor:not-allowed}
  .table-wrap{overflow:auto}
  table{border-collapse:collapse;width:100%}
  th,td{padding:12px 10px;border-bottom:1px solid rgba(255,255,255,.08);white-space:nowrap}
  th{text-align:left;font-size:.9rem;color:var(--muted);position:sticky;top:0;background:var(--panel)}
  tr:hover{background:rgba(255,255,255,.04)}
  .muted{color:var(--muted)}
  .badge{display:inline-block;padding:4px 8px;border-radius:999px;font-size:.8rem;border:1px solid rgba(255,255,255,.15)}
  .foot{padding:12px 16px;color:var(--muted);font-size:.9rem;display:flex;justify-content:space-between;gap:8px;align-items:center}
  .nores{padding:24px;text-align:center;color:var(--muted)}
  .actions{display:flex;gap:8px;align-items:center}
  .btn.link{background:transparent;border:1px solid rgba(255,255,255,.2);color:var(--text)}
  .checkbox{width:18px;height:18px;cursor:pointer}
</style>

<div class="wrap">
  <h1>Factures des clients</h1>

  <div class="card">
    <form class="head" method="get" action="">
      <div class="filters">
        <label for="client" class="muted">Client</label>
        <select id="client" name="client_id" class="select" onchange="this.form.submit()">
          <option value="">— Sélectionner un client —</option>
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

        <input name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" class="inp" placeholder="Filtrer (départ, arrivée, chauffeur...)" />
        <input type="hidden" name="limit" value="<?= (int)$limit ?>" />
        <button class="btn" type="submit">Appliquer</button>
      </div>

      <div class="muted" id="info">
        <?= count($rows) ?> <?= count($rows) > 1 ? 'lignes' : 'ligne' ?>
      </div>
    </form>

    <div class="table-wrap">
      <table id="grid">
        <thead>
          <tr>
            <th><input type="checkbox" id="checkAll" class="checkbox" title="Tout cocher / décocher" /></th>
            <th>ID</th>
            <th>Date</th>
            <th>Départ</th>
            <th>Arrivée</th>
            <th>Km</th>
            <th>Prix (€)</th>
            <th>Paie</th>
            <th>Statut</th>
            <th>Chauffeur</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $printed = 0;
        foreach ($rows as $r) {
            // Filtre simple côté serveur
            if ($q !== '') {
                $hay = mb_strtolower(implode(' ', [
                    $r['point_depart'] ?? '',
                    $r['point_arrivee'] ?? '',
                    $r['mode_paiement'] ?? '',
                    $r['statut'] ?? '',
                    ($r['chauffeur_nom'] ?? '') . ' ' . ($r['chauffeur_prenom'] ?? ''),
                ]));
                if (mb_strpos($hay, mb_strtolower($q)) === false) continue;
            }

            $idCourse  = (string)($r['id_course'] ?? '');
            $dateStr   = isset($r['date_course']) ? (string)$r['date_course'] : '';
            $ts        = $dateStr ? strtotime(str_replace(' ', 'T', $dateStr)) : false;
            $dateAff   = $ts ? date('d/m/Y H:i', $ts) : htmlspecialchars($dateStr, ENT_QUOTES, 'UTF-8');
            $chauffeur = trim(($r['chauffeur_prenom'] ?? '') . ' ' . ($r['chauffeur_nom'] ?? ''));
        ?>
          <tr>
            <td><input type="checkbox" class="checkbox rowcheck" value="<?= htmlspecialchars($idCourse, ENT_QUOTES, 'UTF-8') ?>"></td>
            <td class="muted"><?= htmlspecialchars($idCourse, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= $dateAff ?></td>
            <td><?= htmlspecialchars((string)($r['point_depart'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($r['point_arrivee'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= ($r['distance_km'] !== null && $r['distance_km'] !== '') ? htmlspecialchars(number_format((float)$r['distance_km'], 2, ',', ' '), ENT_QUOTES, 'UTF-8') : '' ?></td>
            <td><?= ($r['prix'] !== null && $r['prix'] !== '') ? htmlspecialchars(number_format((float)$r['prix'], 2, ',', ' '), ENT_QUOTES, 'UTF-8') : '' ?></td>
            <td><span class="badge"><?= htmlspecialchars((string)($r['mode_paiement'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
            <td><span class="badge"><?= htmlspecialchars((string)($r['statut'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
            <td><?= htmlspecialchars($chauffeur, ENT_QUOTES, 'UTF-8') ?></td>
            <td class="actions">
              <a class="btn link" href="facture.php?id=<?= urlencode($idCourse) ?>" target="_blank" rel="noopener">Facture</a>
            </td>
          </tr>
        <?php $printed++; }
        if ($clientId > 0 && $printed === 0): ?>
          <tr><td colspan="11" class="nores">Aucune course ne correspond à ce filtre.</td></tr>
        <?php elseif ($clientId === 0): ?>
          <tr><td colspan="11" class="nores">Sélectionnez un client pour afficher ses courses.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="foot">
      <span>Astuce : filtre + « Appliquer ». Utilisez les cases pour générer plusieurs factures.</span>
      <span class="actions">
        <button id="btnSelectedInvoices" class="btn" type="button" disabled>Factures (PDF) pour la sélection</button>
      </span>
    </div>
  </div>
</div>

<script>
(function(){
  const checkAll = document.getElementById('checkAll');
  const rowchecks = Array.from(document.querySelectorAll('.rowcheck'));
  const btn = document.getElementById('btnSelectedInvoices');

  function updateBtn() {
    const hasAny = rowchecks.some(c => c.checked);
    btn.disabled = !hasAny;
    btn.textContent = hasAny
      ? `Factures (PDF) pour la sélection (${rowchecks.filter(c=>c.checked).length})`
      : 'Factures (PDF) pour la sélection';
  }

  if (checkAll) {
    checkAll.addEventListener('change', () => {
      rowchecks.forEach(c => { c.checked = checkAll.checked; });
      updateBtn();
    });
  }
  rowchecks.forEach(c => c.addEventListener('change', () => {
    if (!c.checked) checkAll.checked = false;
    updateBtn();
  }));

  if (btn) {
    btn.addEventListener('click', () => {
      const ids = rowchecks.filter(c=>c.checked).map(c=>c.value).filter(Boolean);
      if (!ids.length) return;
      // Ouvre chaque facture dans un nouvel onglet
      ids.forEach((id, i) => {
        // petit décalage pour éviter le blocage pop-up
        setTimeout(() => window.open(`facture.php?id=${encodeURIComponent(id)}`, '_blank', 'noopener'), i*60);
      });
    });
  }

  updateBtn();
})();
</script>
