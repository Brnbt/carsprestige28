<?php
// courses_par_semaine.php — Affichage uniquement
// Dépend de _fonctions.inc.php pour getCoursesGroupedByWeek()
// + (optionnel) getDepensesGroupedByWeek()

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

/** ----------------------------------------------------------------
 * 1) Données courses groupées par semaine (existant)
 * ----------------------------------------------------------------*/
$rows = getCoursesGroupedByWeek($from, $to, $clientId ?: null, $limit);

// Index par clé semaine pour fusion ensuite
$byWeek = [];
foreach ($rows as $r) {
    $key = (string)$r['yw']; // ex: 2025W32
    $byWeek[$key] = [
        'yw'          => $key,
        'week_start'  => $r['week_start'],
        'week_end'    => $r['week_end'],
        'nb_courses'  => (int)$r['nb_courses'],
        'total_km'    => (float)$r['total_km'],
        'total_prix'  => (float)$r['total_prix'],
        // champs dépenses ajoutés après
        'depenses'    => 0.0,
        'net'         => 0.0,
    ];
}

/** ----------------------------------------------------------------
 * 2) Données dépenses groupées par semaine
 *    - si getDepensesGroupedByWeek existe, on l'utilise
 *    - sinon on fait un fallback local
 * ----------------------------------------------------------------*/
$depensesByWeek = [];

if (function_exists('getDepensesGroupedByWeek')) {
    $depRows = getDepensesGroupedByWeek($from, $to, $clientId ?: null);

    foreach ($depRows as $d) {
        $key = (string)$d['yw']; // même format "YYYYWww"
        $depensesByWeek[$key] = (float)$d['total_depenses'];
    }
} else {
    // Fallback léger: requête directe
    // Hypothèses:
    //  - Table depense(date_depense DATETIME, montant DECIMAL, id_course nullable)
    //  - Si filtre client: on ne retient que les dépenses liées à une course rattachée au client
    //  - Sinon: toutes les dépenses de la période
    try {
        if (!function_exists('gestionnaireDeConnexion')) {
            throw new RuntimeException("gestionnaireDeConnexion() manquant.");
        }
        $db = gestionnaireDeConnexion();

        if ($clientId > 0) {
            // jointure sur course pour filtrer par client
            $sql = "
                SELECT d.date_depense, d.montant
                FROM depense d
                INNER JOIN course c ON c.id_course = d.id_course
                WHERE c.id_client = :clid
                  AND d.date_depense >= :from
                  AND d.date_depense <  DATE_ADD(:to, INTERVAL 1 DAY)
            ";
            $st = $db->prepare($sql);
            $st->bindValue(':clid', $clientId, PDO::PARAM_INT);
            $st->bindValue(':from', $from . ' 00:00:00');
            $st->bindValue(':to',   $to   . ' 23:59:59');
        } else {
            $sql = "
                SELECT d.date_depense, d.montant
                FROM depense d
                WHERE d.date_depense >= :from
                  AND d.date_depense <  DATE_ADD(:to, INTERVAL 1 DAY)
            ";
            $st = $db->prepare($sql);
            $st->bindValue(':from', $from . ' 00:00:00');
            $st->bindValue(':to',   $to   . ' 23:59:59');
        }

        $st->execute();
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $ts  = strtotime($row['date_depense']);
            // Clé semaine ISO "YYYYWww"
            $key = date('o', $ts) . 'W' . date('W', $ts);
            $depensesByWeek[$key] = ($depensesByWeek[$key] ?? 0.0) + (float)$row['montant'];
        }
    } catch (Throwable $e) {
        // En cas d’erreur SQL, on affiche une note discrète + on continue sans dépenses
        echo '<div class="alert error" style="margin:1rem 0;">Dépenses indisponibles: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES) . '</div>';
    }
}

/** ----------------------------------------------------------------
 * 3) Fusion des dépenses dans les semaines courses
 *    + on ajoute les semaines qui auraient des dépenses sans course
 * ----------------------------------------------------------------*/
foreach ($depensesByWeek as $key => $dep) {
    if (!isset($byWeek[$key])) {
        // Semaine sans course → on crée une ligne "vide" côté courses
        // Pour les bornes semaine, on reconstruit via un lundi ISO
        // $key format "YYYYWww" → extraire
        if (preg_match('~^(\d{4})W(\d{2})$~', $key, $m)) {
            $isoYear = (int)$m[1];
            $isoWeek = (int)$m[2];
            // Lundi de la semaine ISO
            $weekStart = new DateTime();
            $weekStart->setISODate($isoYear, $isoWeek, 1)->setTime(0,0,0);
            $weekEnd = clone $weekStart;
            $weekEnd->modify('+6 days')->setTime(23,59,59);

            $byWeek[$key] = [
                'yw'          => $key,
                'week_start'  => $weekStart->format('Y-m-d'),
                'week_end'    => $weekEnd->format('Y-m-d'),
                'nb_courses'  => 0,
                'total_km'    => 0.0,
                'total_prix'  => 0.0,
                'depenses'    => 0.0,
                'net'         => 0.0,
            ];
        } else {
            // clé inattendue → on skip
            continue;
        }
    }
    $byWeek[$key]['depenses'] = (float)$dep;
}

// Calcul du net maintenant que tout est fusionné
foreach ($byWeek as $k => $v) {
    $byWeek[$k]['net'] = (float)$v['total_prix'] - (float)$v['depenses'];
}

// Ordonner par semaine décroissante (même logique que $rows initial)
uksort($byWeek, function($a, $b){
    return strcmp($b, $a); // desc
});

// Reconstituer $rows finaux
$rows = array_values($byWeek);

/** ----------------------------------------------------------------
 * 4) Totaux globaux (sur la période)
 * ----------------------------------------------------------------*/
$totCourses  = array_sum(array_column($rows, 'nb_courses'));
$totKm       = array_sum(array_map(fn($r)=>(float)$r['total_km'], $rows));
$totPrix     = array_sum(array_map(fn($r)=>(float)$r['total_prix'], $rows));
$totDepenses = array_sum(array_map(fn($r)=>(float)$r['depenses'], $rows));
$totNet      = $totPrix - $totDepenses;
?>
<div class="wrap">
  <div class="page-course"><h2 class="page-title">Courses par semaine</h2>

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
        <div class="kpi"><span class="muted">Dépenses (€) :</span> <span class="v"><?= number_format((float)$totDepenses, 2, ',', ' ') ?></span></div>
        <div class="kpi"><span class="muted">Net (€) :</span> <span class="v"><?= number_format((float)$totNet, 2, ',', ' ') ?></span></div>
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
            <th>CA (€)</th>
            <th>Dépenses (€)</th>
            <th>Net (€)</th>
            <th>Détails</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!count($rows)): ?>
            <tr><td colspan="8" class="nores">Aucune donnée sur la période.</td></tr>
          <?php else:
            foreach ($rows as $r):
              $label = htmlspecialchars($r['yw'], ENT_QUOTES); // ex: 2025W32
              $per   = htmlspecialchars(date('d/m/Y', strtotime($r['week_start'])) . ' — ' . date('d/m/Y', strtotime($r['week_end'])), ENT_QUOTES);
              $nb    = (int)$r['nb_courses'];
              $km    = number_format((float)$r['total_km'], 2, ',', ' ');
              $eur   = number_format((float)$r['total_prix'], 2, ',', ' ');
              $dep   = number_format((float)$r['depenses'], 2, ',', ' ');
              $net   = number_format((float)$r['net'], 2, ',', ' ');
              // lien “voir les courses de la semaine” — à toi d’ajuster la cible si besoin
              $detailUrl = 'courses_par_client.php?client_id='.(int)$clientId
                           .'&q='
                           .'&limit=1000';
          ?>
            <tr>
              <td><span class="badge"><?= $label ?></span></td>
              <td><?= $per ?></td>
              <td><?= $nb ?></td>
              <td><?= $km ?></td>
              <td><?= $eur ?></td>
              <td><?= $dep ?></td>
              <td><?= $net ?></td>
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

<?php include_once 'affichage/_fin.inc.php'; ?>
