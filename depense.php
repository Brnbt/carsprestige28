<?php
/* =============================================================
 * FICHIER: depense.php
 * Page d'ajout de dépense, inspirée de course.php
 * Prérequis: table `depense` (voir script CREATE TABLE fourni plus haut)
 * ============================================================= */
?>
<?php
require_once 'affichage/_debut.inc.php';

$chauffeurs = getAllChauffeur();
$courses    = function_exists('getCourses') ? getCourses() : [];
$vehicules  = function_exists('getVehicules') ? getVehicules() : [];

$message = '';
if (!empty($_GET['msg'])) $message = '<p style="color:green;">' . htmlspecialchars($_GET['msg']) . '</p>';
if (!empty($_GET['err'])) $message = '<p style="color:red;">'   . htmlspecialchars($_GET['err']) . '</p>';
?>
<div class="page-depense"><h2 class="page-title">Ajouter une dépense</h2>
<?= $message ?>

<form id="form-depense" method="post" action="traitement/_ajouterDepense.php">
  <fieldset>
    <legend>Donnée principale</legend>

    <label>Date & heure :</label>
    <input type="datetime-local" name="date_depense" required>

    <label style="margin-left:16px;">Type :</label>
    <select name="type_depense" required>
      <option value="carburant">Carburant</option>
      <option value="péage">Péage</option>
      <option value="parking">Parking</option>
      <option value="location_vehicule">Location véhicule</option>
      <option value="entretien">Entretien</option>
      <option value="autre">Autre</option>
    </select>

    <br><br>
    <label>Montant (€) :</label>
    <input type="number" name="montant" step="0.01" min="0" required>

    <label style="margin-left:16px;">Remboursement :</label>
    <select name="mode_remboursement" required>
      <option value="non_rembourse">Non remboursé</option>
      <option value="cash">Cash</option>
      <option value="virement">Virement</option>
    </select>
  </fieldset>

  <br>
  <fieldset>
    <legend>Liens (facultatifs sauf chauffeur)</legend>

    <label>Chauffeur :</label>
    <select name="id_chauffeur" required>
      <option value="" disabled selected>— Sélectionner un chauffeur —</option>
      <?php foreach ($chauffeurs as $ch): ?>
        <option value="<?= (int)$ch['id_chauffeur'] ?>">
          <?= htmlspecialchars(($ch['nom'] ?? '') . ' ' . ($ch['prenom'] ?? '') . ' — ' . ($ch['telephone'] ?? '')) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <br><br>
    <label>Course (si liée) :</label>
    <select name="id_course">
      <option value="">— Aucune —</option>
      <?php foreach ($courses as $co): ?>
        <option value="<?= (int)$co['id_course'] ?>">
          #<?= (int)$co['id_course'] ?> — <?= htmlspecialchars($co['point_depart'] . ' → ' . $co['point_arrivee']) ?> (<?= htmlspecialchars($co['date_course']) ?>)
        </option>
      <?php endforeach; ?>
    </select>

    <label style="margin-left:16px;">Véhicule :</label>
    <select name="id_vehicule">
      <option value="">— Non spécifié —</option>
      <?php foreach ($vehicules as $v): ?>
        <option value="<?= (int)$v['id_vehicule'] ?>">
          <?= htmlspecialchars($v['marque'] . ' ' . $v['modele'] . ' — ' . $v['immatriculation']) ?>
        </option>
      <?php endforeach; ?>
    </select>

  </fieldset>

  <br>
  <!-- <fieldset>
    <legend>Notes</legend>
    <label>Description :</label>
    <textarea name="description" rows="3" style="width:100%;max-width:720px;" placeholder="Ex: plein avant Cergy → Argenteuil, reçu n°123..."></textarea>

    <div style="margin-top:8px;">
      <label>
        <input type="checkbox" name="refacturable_client" value="1"> Refacturable au client
      </label>
    </div>
  </fieldset> -->

  <br>
  <button type="submit" class="btn btn--primary">Ajouter la dépense</button>
</form>

</div>
<?php require_once 'affichage/_fin.inc.php'; ?>


<?php
/* =============================================================
 * FICHIER: traitement/_ajouterDepense.php
 * Traite l'ajout d'une dépense via POST
 * ============================================================= */
?>
