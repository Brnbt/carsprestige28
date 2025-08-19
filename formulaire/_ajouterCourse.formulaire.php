<?php
include_once 'traitement/_fonctions.inc.php';

$clients    = getClients();
$chauffeurs = getChauffeurs();
?>


<div class="page-course"><h2 class="zzz">Ajouter une course</h2>

<div class="alert info" style="margin:1rem 0;padding:0.8rem 1rem;border:1px solid #cce5ff;background:#e9f5ff;border-radius:6px;color:#004085;">
  <strong>ℹ️ Utilisation :</strong> 
  Remplissez le formulaire ci-dessous pour enregistrer une nouvelle course.  
  <br><br>
  <strong>Client :</strong> Le <em>nom</em>, le <em>prénom</em> et le <em>numéro de téléphone</em> doivent impérativement être renseignés.  
  <em>L’adresse email est facultative.</em>  
  <br><br>
  Une fois validé, la course apparaîtra automatiquement dans la page facture.
</div>

<div id="bloc-ajout-client" style="display:none;margin:16px 0;padding:12px;border:1px solid red;border-radius:8px;">
  <h3 style="margin-top:0;">Nouveau client</h3>
  <form id="form-ajout-client" method="post" action="traitement/_ajouterClient.php">
    <label>Nom :</label>
    <input type="text" name="nom" required>

    <label style="margin-left:12px;">Prénom :</label>
    <input type="text" name="prenom" required>

    <br><br>
    <label>Téléphone :</label>
    <input type="text" name="telephone" required>

    <label style="margin-left:12px;">Email :</label>
    <input type="email" name="email">

    <br><br>
    <button type="submit" class="btn btn--primary">Ajouter le client</button>
    <button type="button" id="btn-annuler-ajout-client" style="margin-left:8px;" class="btn btn--ghost">Annuler</button>
  </form>
</div>

<form id="form-course"  method="post"  action="traitement/_ajouterCourse.php">
  <fieldset>
    <legend>Client</legend>

    <label>Client :</label>
    <select id="select-client" name="id_client" required>
      <option value="" disabled <?= empty($_GET['id_client']) ? 'selected' : '' ?>>— Sélectionner un client —</option>
      <?php foreach ($clients as $cl): ?>
        <option value="<?= htmlspecialchars((string)$cl['id_client']) ?>">
  <?= htmlspecialchars($cl['nom'].' '.$cl['prenom'].' — '.$cl['telephone']) ?>
</option>
          <?= htmlspecialchars($cl['nom'] . ' ' . $cl['prenom'] . ' — ' . $cl['telephone']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <button type="button" id="btn-ajouter-client" style="margin-left:8px; margin-top: 8px;" class="btn btn--primary">+ Ajouter un client</button>

  </fieldset>

  <br>

  <fieldset>
    <legend>Course</legend>

    <label>Date et heure :</label>
    <input type="datetime-local" name="date_course" required><br><br>

    <label>Point de départ :</label>
    <input type="text" name="point_depart" required><br><br>

    <label>Point d'arrivée :</label>
    <input type="text" name="point_arrivee" required><br><br>

    <label>Distance (km) :</label>
    <input type="number" step="0.1" name="distance_km" required><br><br>

    <label>Prix (€) :</label>
    <input type="number" step="0.01" name="prix" required><br><br>
        
    <label>Mode de paiement</label>
    <select name="mode_paiement" required>
      <option value="carte">Carte</option>
      <option value="espèces">Espèces</option>
      <option value="virement">Virement</option>
    </select>

    <label>Statut :</label>
    <select name="statut" required>
      <option value="en attente">En attente</option>
      <option value="en cours">En cours</option>
      <option value="terminée">Terminée</option>
    </select><br><br>

    <label>Chauffeur :</label>
<select name="id_chauffeur" required>
  <option value="" disabled selected>— Sélectionner un chauffeur —</option>
  <?php foreach ($chauffeurs as $ch): ?>
    <option value="<?= (int)$ch['id_chauffeur'] ?>">
      <?= htmlspecialchars($ch['nom'] . ' ' . $ch['prenom'] . ' — ' . ($ch['telephone'] ?? '')) ?>
    </option>
  <?php endforeach; ?>
</select>

  </fieldset>

  <br>
  <button type="submit" class="btn btn--primary">Ajouter la course</button>
</form>