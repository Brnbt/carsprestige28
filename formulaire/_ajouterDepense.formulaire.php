<?php
include_once 'traitement/_fonctions.inc.php';

$chauffeurs = getAllChauffeur();
$courses    = function_exists('getCourses') ? getCourses() : [];
$vehicules  = function_exists('getVehicules') ? getVehicules() : [];
?>

<div class="page-depense" >
  <h2 class="page-title">Ajouter une dépense</h2>

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

    <button type="submit" class="btn btn--primary">Ajouter la dépense</button>
  </form>
