<?php
include_once 'traitement/_fonctions.inc.php';

$chauffeurs = getAllChauffeur();
$courses    = function_exists('getCourses') ? getCourses() : [];
$vehicules  = function_exists('getVehicules') ? getVehicules() : [];
?>

<?php
// ====== FILTRES ======
$filtre_chauffeur = isset($_GET['f_chauffeur']) && ctype_digit($_GET['f_chauffeur']) ? (int)$_GET['f_chauffeur'] : null;

$filtre_from_raw = $_GET['f_from'] ?? null; // attendu: YYYY-MM-DD
$filtre_to_raw   = $_GET['f_to']   ?? null; // attendu: YYYY-MM-DD

$filtre_from = $filtre_from_raw ? ($filtre_from_raw . ' 00:00:00') : null;
$filtre_to   = $filtre_to_raw   ? ($filtre_to_raw   . ' 23:59:59') : null;

// Récup via ta fonction existante
$depenses = getDepenses($filtre_chauffeur, $filtre_from, $filtre_to);
?>

<br><hr><br>

<br>

<h2>Liste des dépenses</h2>
<?php if (empty($depenses)): ?>
  <p>Aucune dépense trouvée pour ce filtre.</p>
<?php else: ?>
  <table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;">
    <thead style="background:#f0f0f0;">
      <tr>
        <th>ID</th>
        <th>Date</th>
        <th>Type</th>
        <th>Montant (€)</th>
        <th>Remboursement</th>
        <th>Chauffeur</th>
        <th>Immat.</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($depenses as $dep): ?>
        <tr>
          <td><?= (int)$dep['id_depense'] ?></td>
          <td><?= htmlspecialchars($dep['date_depense']) ?></td>
          <td><?= htmlspecialchars($dep['type_depense']) ?></td>
          <td><?= number_format((float)$dep['montant'], 2, ',', ' ') ?></td>
          <td><?= htmlspecialchars($dep['mode_remboursement']) ?></td>
          <td><?= htmlspecialchars(trim(($dep['nom'] ?? '') . ' ' . ($dep['prenom'] ?? ''))) ?></td>
          <td><?= htmlspecialchars($dep['immatriculation'] ?? '') ?></td>
          <td style="text-align:center;">
            <form method="post" action="traitement/_supprimerDepense.php" onsubmit="return confirm('Supprimer définitivement la dépense #<?= (int)$dep['id_depense'] ?> ?');" style="display:inline;">
  <input type="hidden" name="id_depense" value="<?= (int)$dep['id_depense'] ?>">
  <input type="hidden" name="back" value="depense">
  <button type="submit" class="btn btn--ghost" style="background:red;color:white;padding:4px 8px;font-size:0.9em;">
    Supprimer
  </button>
</form>

          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>


</div>