<?php
// courses_par_semaine.php — Affichage + détails inline (robuste aux noms de colonnes)
// Requiert getCoursesGroupedByWeek() (+ optionnel: getDepensesGroupedByWeek(), getCoursesBetween(), getDepensesBetween())

include_once 'affichage/_debut.inc.php';
header('X-Content-Type-Options: nosniff');

// --- petit helper pour lire la 1ère clé dispo dans une ligne PDO ---
if (!function_exists('firstVal')) {
  function firstVal(array $row, array $keys, $default = null) {
    foreach ($keys as $k) {
      if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') return $row[$k];
    }
    return $default;
  }
}

// Récup clients pour le filtre
$clients  = getClients();

// Paramètres GET
$today = new DateTimeImmutable('today');
$defaultTo   = $today->format('Y-m-d');
$defaultFrom = $today->sub(new DateInterval('P84D'))->format('Y-m-d'); // 12 semaines

$from     = isset($_GET['from']) ? preg_replace('~[^0-9\-]~', '', $_GET['from']) : $defaultFrom;
$to       = isset($_GET['to'])   ? preg_replace('~[^0-9\-]~', '', $_GET['to'])   : $defaultTo;
$clientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
$limit    = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 52;

// Pour l’affichage inline
$openWeek = isset($_GET['week']) ? preg_replace('~[^0-9W]~', '', $_GET['week']) : '';
$wsGet    = isset($_GET['ws']) ? preg_replace('~[^0-9\-]~', '', $_GET['ws']) : '';
$weGet    = isset($_GET['we']) ? preg_replace('~[^0-9\-]~', '', $_GET['we']) : '';

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
        'depenses'    => 0.0,
        'net'         => 0.0,
    ];
}

/** ----------------------------------------------------------------
 * 2) Dépenses groupées par semaine (helper ou fallback SQL)
 * ----------------------------------------------------------------*/
$depensesByWeek = [];

if (function_exists('getDepensesGroupedByWeek')) {
    $depRows = getDepensesGroupedByWeek($from, $to, $clientId ?: null);
    foreach ($depRows as $d) {
        $key = (string)$d['yw']; // "YYYYWww"
        $depensesByWeek[$key] = (float)$d['total_depenses'];
    }
} else {
    try {
        if (!function_exists('gestionnaireDeConnexion')) {
            throw new RuntimeException("gestionnaireDeConnexion() manquant.");
        }
        $db = gestionnaireDeConnexion();

        if ($clientId > 0) {
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
            $key = date('o', $ts) . 'W' . date('W', $ts); // ISO week
            $depensesByWeek[$key] = ($depensesByWeek[$key] ?? 0.0) + (float)$row['montant'];
        }
    } catch (Throwable $e) {
        echo '<div class="alert error" style="margin:1rem 0;">Dépenses indisponibles: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES) . '</div>';
    }
}

/** ----------------------------------------------------------------
 * 3) Fusion dépenses + semaines sans course
 * ----------------------------------------------------------------*/
foreach ($depensesByWeek as $key => $dep) {
    if (!isset($byWeek[$key])) {
        if (preg_match('~^(\d{4})W(\d{2})$~', $key, $m)) {
            $isoYear = (int)$m[1];
            $isoWeek = (int)$m[2];
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
            continue;
        }
    }
    $byWeek[$key]['depenses'] = (float)$dep;
}

// Calcul net
foreach ($byWeek as $k => $v) {
    $byWeek[$k]['net'] = (float)$v['total_prix'] - (float)$v['depenses'];
}

// Tri descendant
uksort($byWeek, fn($a,$b)=> strcmp($b,$a));
$rows = array_values($byWeek);

// Index par clé (YYYYWww)
$rowsByKey = [];
foreach ($rows as $r) { $rowsByKey[(string)$r['yw']] = $r; }

/** ----------------------------------------------------------------
 * 4) Totaux globaux
 * ----------------------------------------------------------------*/
$totCourses  = array_sum(array_column($rows, 'nb_courses'));
$totKm       = array_sum(array_map(fn($r)=>(float)$r['total_km'], $rows));
$totPrix     = array_sum(array_map(fn($r)=>(float)$r['total_prix'], $rows));
$totDepenses = array_sum(array_map(fn($r)=>(float)$r['depenses'], $rows));
$totNet      = $totPrix - $totDepenses;
?>

<div class="wrap">
  <div class="page-course"><h2 class="page-title">Courses par semaine</h2>

      <div class="alert info" style="margin:1rem 0;padding:0.8rem 1rem;border:1px solid #cce5ff;background:#e9f5ff;border-radius:6px;color:#004085;">
      <strong>ℹ️ Utilisation :</strong> 
      Sélectionnez une période et éventuellement un client pour filtrer les résultats.  
      Le tableau regroupe les courses et dépenses par semaine ISO (lundi → dimanche).  
      Cliquez sur le bouton <em>« Voir »</em> d’une ligne pour afficher le détail des courses et des dépenses de la semaine.
    </div>

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
            $label = trim($nom.' '.$pre.($tel?" · ".$tel:'')); ?>
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
          </tr>
        </thead>

        <?php
        $baseParams = [
          'from'      => $from,
          'to'        => $to,
          'client_id' => $clientId ?: '',
          'limit'     => $limit,
        ];
        ?>

        <tbody>
          <?php if (!count($rows)): ?>
            <tr><td colspan="8" class="nores">Aucune donnée sur la période.</td></tr>
          <?php else:
            foreach ($rows as $r):
              $label = htmlspecialchars($r['yw'], ENT_QUOTES);
              $per   = htmlspecialchars(date('d/m/Y', strtotime($r['week_start'])) . ' — ' . date('d/m/Y', strtotime($r['week_end'])), ENT_QUOTES);
              $nb    = (int)$r['nb_courses'];
              $km    = number_format((float)$r['total_km'], 2, ',', ' ');
              $eur   = number_format((float)$r['total_prix'], 2, ',', ' ');
              $dep   = number_format((float)$r['depenses'], 2, ',', ' ');
              $net   = number_format((float)$r['net'], 2, ',', ' ');

              $openUrl  = '?' . http_build_query($baseParams + [
                'week' => $r['yw'],
                'ws'   => $r['week_start'],
                'we'   => $r['week_end'],
              ]) . '#w-'.$r['yw'];
              $closeUrl = '?' . http_build_query($baseParams) . '#w-'.$r['yw'];

              $isOpen = ($openWeek && $openWeek === (string)$r['yw']);
          ?>
            <tr id="w-<?= $label ?>">
              <td><span class="badge"><?= $label ?></span></td>
              <td><?= $per ?></td>
              <td><?= $nb ?></td>
              <td><?= $km ?></td>
              <td><?= $eur ?></td>
              <td><?= $dep ?></td>
              <td><?= $net ?></td>
              <td>
                <?php if ($isOpen): ?>
                  <a class="btn link" href="<?= htmlspecialchars($closeUrl, ENT_QUOTES) ?>">Fermer</a>
                <?php else: ?>
                  <a class="btn link" href="<?= htmlspecialchars($openUrl, ENT_QUOTES) ?>">Voir</a>
                <?php endif; ?>
              </td>
            </tr>

            <?php if ($isOpen):
              // Bornes de la semaine
              $ws = $wsGet ?: ($r['week_start'] ?? null);
              $we = $weGet ?: ($r['week_end']   ?? null);
              if ($ws && $we):

              // --- Récup COURSES ---
              $courses = [];
              try {
                if (function_exists('getCoursesBetween')) {
                  $courses = getCoursesBetween($ws.' 00:00:00', $we.' 23:59:59', $clientId ?: null);
                } else {
                  if (!function_exists('gestionnaireDeConnexion')) {
                    throw new RuntimeException("gestionnaireDeConnexion() manquant.");
                  }
                  $db = gestionnaireDeConnexion();

                  if ($clientId > 0) {
                    // NB: pour éviter les colonnes inconnues, on sélectionne c.* uniquement
                    $sql = "
                      SELECT c.*, cl.nom AS _cl_nom, cl.prenom AS _cl_prenom, cl.telephone AS _cl_tel
                      FROM course c
                      INNER JOIN client cl ON cl.id_client = c.id_client
                      WHERE c.id_client = :clid
                        AND c.date_course >= :from
                        AND c.date_course <  DATE_ADD(:to, INTERVAL 1 DAY)
                      ORDER BY c.date_course ASC, c.id_course ASC
                    ";
                    $st = $db->prepare($sql);
                    $st->bindValue(':clid', $clientId, PDO::PARAM_INT);
                  } else {
                    $sql = "
                      SELECT c.*, cl.nom AS _cl_nom, cl.prenom AS _cl_prenom, cl.telephone AS _cl_tel
                      FROM course c
                      LEFT JOIN client cl ON cl.id_client = c.id_client
                      WHERE c.date_course >= :from
                        AND c.date_course <  DATE_ADD(:to, INTERVAL 1 DAY)
                      ORDER BY c.date_course ASC, c.id_course ASC
                    ";
                    $st = $db->prepare($sql);
                  }
                  $st->bindValue(':from', $ws.' 00:00:00');
                  $st->bindValue(':to',   $we.' 23:59:59');
                  $st->execute();
                  $courses = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
                }
              } catch (Throwable $e) {
                echo '<tr class="details"><td colspan="8"><div class="alert error">Erreur courses : '.htmlspecialchars($e->getMessage(), ENT_QUOTES).'</div></td></tr>';
                $courses = [];
              }

              // --- Récup DEPENSES ---
              $depenses = [];
              try {
                if (function_exists('getDepensesBetween')) {
                  $depenses = getDepensesBetween($ws.' 00:00:00', $we.' 23:59:59', $clientId ?: null);
                } else {
                  if (!isset($db)) {
                    if (!function_exists('gestionnaireDeConnexion')) {
                      throw new RuntimeException("gestionnaireDeConnexion() manquant.");
                    }
                    $db = gestionnaireDeConnexion();
                  }

                  if ($clientId > 0) {
                    // Même logique: pas de nom de colonne exotique dans SELECT
                    $sql = "
                      SELECT d.*, d.id_course
                      FROM depense d
                      INNER JOIN course c ON c.id_course = d.id_course
                      WHERE c.id_client = :clid
                        AND d.date_depense >= :from
                        AND d.date_depense <  DATE_ADD(:to, INTERVAL 1 DAY)
                      ORDER BY d.date_depense ASC, d.id_depense ASC
                    ";
                    $st = $db->prepare($sql);
                    $st->bindValue(':clid', $clientId, PDO::PARAM_INT);
                  } else {
                    $sql = "
                      SELECT d.*, d.id_course
                      FROM depense d
                      WHERE d.date_depense >= :from
                        AND d.date_depense <  DATE_ADD(:to, INTERVAL 1 DAY)
                      ORDER BY d.date_depense ASC, d.id_depense ASC
                    ";
                    $st = $db->prepare($sql);
                  }
                  $st->bindValue(':from', $ws.' 00:00:00');
                  $st->bindValue(':to',   $we.' 23:59:59');
                  $st->execute();
                  $depenses = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
                }
              } catch (Throwable $e) {
                echo '<tr class="details"><td colspan="8"><div class="alert error">Erreur dépenses : '.htmlspecialchars($e->getMessage(), ENT_QUOTES).'</div></td></tr>';
                $depenses = [];
              }

              // Totaux panneau, calculés en lisant les clés disponibles
              $totKmPanel = 0.0;
              $totPrixCourses = 0.0;
              foreach ($courses as $cRow) {
                $kmVal   = (float) firstVal($cRow, ['km','kms','distance_km','distance','nb_km','kilometres'], 0);
                $prixVal = (float) firstVal($cRow, ['prix','montant','prix_total','total','ca','amount','cout'], 0);
                $totKmPanel     += $kmVal;
                $totPrixCourses += $prixVal;
              }
              $totDepPanel = 0.0;
              foreach ($depenses as $dRow) {
                $depVal = (float) firstVal($dRow, ['montant','prix','amount','total','cout'], 0);
                $totDepPanel += $depVal;
              }
              $netPanel = $totPrixCourses - $totDepPanel;
            ?>
            <tr class="details">
              <td colspan="8">
                <div class="card" style="margin:0.5rem 0;">
                  <div class="kpis" style="margin-bottom:0.5rem;">
                    <div class="kpi"><span class="muted">Courses :</span> <span class="v"><?= count($courses) ?></span></div>
                    <div class="kpi"><span class="muted">Km :</span> <span class="v"><?= number_format($totKmPanel, 2, ',', ' ') ?></span></div>
                    <div class="kpi"><span class="muted">CA (€) :</span> <span class="v"><?= number_format($totPrixCourses, 2, ',', ' ') ?></span></div>
                    <div class="kpi"><span class="muted">Dépenses (€) :</span> <span class="v"><?= number_format($totDepPanel, 2, ',', ' ') ?></span></div>
                    <div class="kpi"><span class="muted">Net (€) :</span> <span class="v"><?= number_format($netPanel, 2, ',', ' ') ?></span></div>
                  </div>

                  <h4 style="margin:0.5rem 0;">Courses</h4>
                  <div class="table-wrap">
                    <table>
                      <thead>
                        <tr>
                          <th>Date</th>
                          <th>Client</th>
                          <th>Téléphone</th>
                          <th>Km</th>
                          <th>Prix (€)</th>
                          <th>#</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (!count($courses)): ?>
                          <tr><td colspan="6" class="nores">Aucune course sur la semaine.</td></tr>
                        <?php else: foreach ($courses as $c): 
                          $dateCourse = firstVal($c, ['date_course','date','datetime','created_at','timestamp']);
                          $kmVal   = (float) firstVal($c, ['km','kms','distance_km','distance','nb_km','kilometres'], 0);
                          $prixVal = (float) firstVal($c, ['prix','montant','prix_total','total','ca','amount','cout'], 0);
                          $idc     = (int) firstVal($c, ['id_course','id','course_id'], 0);
                          $clNom   = trim((string) firstVal($c, ['_cl_nom','nom_client','client_nom','nom'], ''));
                          $clPre   = trim((string) firstVal($c, ['_cl_prenom','prenom_client','client_prenom','prenom'], ''));
                          $clTel   = (string) firstVal($c, ['_cl_tel','telephone','tel','phone'], '');
                          ?>
                          <tr>
                            <td><?= $dateCourse ? htmlspecialchars(date('d/m/Y H:i', strtotime($dateCourse))) : '—' ?></td>
                            <td><?= htmlspecialchars(trim($clNom.' '.$clPre)) ?></td>
                            <td><?= htmlspecialchars($clTel) ?></td>
                            <td><?= number_format($kmVal, 2, ',', ' ') ?></td>
                            <td><?= number_format($prixVal, 2, ',', ' ') ?></td>
                            <td>#<?= $idc ?></td>
                          </tr>
                        <?php endforeach; endif; ?>
                      </tbody>
                    </table>
                  </div>

                  <h4 style="margin:0.5rem 0;">Dépenses</h4>
                  <div class="table-wrap">
                    <table>
                      <thead>
                        <tr>
                          <th>Date</th>
                          <th>Libellé</th>
                          <th>Montant (€)</th>
                          <th>Liée à la course</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (!count($depenses)): ?>
                          <tr><td colspan="4" class="nores">Aucune dépense sur la semaine.</td></tr>
                        <?php else: foreach ($depenses as $d):
                          $dateDep = firstVal($d, ['date_depense','date','datetime','created_at','timestamp']);
                          $lblDep  = (string) firstVal($d, ['libelle','label','motif','description','intitule','notes','commentaire'], '—');
                          $mntDep  = (float) firstVal($d, ['montant','prix','amount','total','cout'], 0);
                          $relId   = firstVal($d, ['id_course','course_id','idc'], null);
                          ?>
                          <tr>
                            <td><?= $dateDep ? htmlspecialchars(date('d/m/Y H:i', strtotime($dateDep))) : '—' ?></td>
                            <td><?= htmlspecialchars($lblDep) ?></td>
                            <td><?= number_format($mntDep, 2, ',', ' ') ?></td>
                            <td><?= $relId ? '#'.(int)$relId : '—' ?></td>
                          </tr>
                        <?php endforeach; endif; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </td>
            </tr>
            <?php endif; // ws/we ok ?>
            <?php endif; // $isOpen ?>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <div class="foot">
      <span class="muted">Agrégation par semaine ISO (lundi → dimanche). Clique “Voir” pour dérouler les détails.</span>
      <span></span>
    </div>
  </div>
</div>

<style>.kpis {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  justify-content: center;   /* centre tout le bloc */
  margin: 1rem 0;
}

.kpi {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.5rem 1rem;
  border: 2px solid #2b3b53ff;      /* fine bordure */
  border-radius: 50px;         /* arrondi type “pill” */
  background: #18202eff;            /* fond blanc pour contraste */
  box-shadow: 0 1px 3px rgba(0,0,0,0.05); /* léger relief */
    color: #fff;                  /* texte en blanc */

}

.kpi .muted {
  color: #fff;                  /* libellé en blanc */
  opacity: 0.9;                 /* un peu plus clair pour contraste */
}

.kpi .v {
  font-weight: bold;
  color: #fff;                  /* valeur en blanc aussi */
}
</style>

<?php include_once 'affichage/_fin.inc.php'; ?>
