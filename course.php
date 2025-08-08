<?php
require_once 'affichage/_debut.inc.php';

$clients    = getAllClient();
$chauffeurs = getAllChauffeur(); // doit retourner un tableau de chauffeurs



$message = '';
if (!empty($_GET['msg'])) $message = '<p style="color:green;">' . htmlspecialchars($_GET['msg']) . '</p>';
if (!empty($_GET['err'])) $message = '<p style="color:red;">'   . htmlspecialchars($_GET['err']) . '</p>';
?>
<div class="page-course"><h2 class="page-title">Ajouter une course</h2>
<?= $message ?>

<!-- FORMULAIRE D’AJOUT DE CLIENT (caché par défaut) -->
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

<!-- FORMULAIRE D’AJOUT DE COURSE -->
<form id="form-course" method="post" action="traitement/_ajouterCourse.php">
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
    <!-- Fallback lien si tu veux garder l’ancienne page
    <a href="ajouter_client.php" style="margin-left:8px;">Page d’ajout</a>
    -->
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

<script>
document.addEventListener('DOMContentLoaded', function(){

// Toggle affichage du formulaire client
const btnShow = document.getElementById('btn-ajouter-client');
const btnCancel = document.getElementById('btn-annuler-ajout-client');
const blocClient = document.getElementById('bloc-ajout-client');

btnShow.addEventListener('click', () => {
  blocClient.style.display = (blocClient.style.display === 'none' || blocClient.style.display === '') ? 'block' : 'none';
});

btnCancel.addEventListener('click', () => {
  blocClient.style.display = 'none';
});

// Soumission AJAX pour ajouter le client et mettre à jour la liste
const formAjout = document.getElementById('form-ajout-client');
const selectClient = document.getElementById('select-client');

formAjout.addEventListener('submit', async (e) => {
  e.preventDefault();

  const formData = new FormData(formAjout);

  try {
    const res = await fetch(formAjout.action, {
      method: 'POST',
      body: formData,
      headers: {
        'Accept': 'application/json' // on “suggère” du JSON côté serveur
      }
    });

    // Deux cas :
    // 1) Le serveur renvoie du JSON { ok: true, client: { id_client, nom, prenom, telephone }, msg }
    // 2) Pas de JSON (ancienne implémentation) -> on recharge la page pour rester compatible
    const contentType = res.headers.get('content-type') || '';
    if (contentType.includes('application/json')) {
      const data = await res.json();
      if (!data.ok) throw new Error(data.msg || 'Erreur lors de l’ajout du client.');

      const c = data.client;
      // Crée l’option et la sélectionne
      const opt = document.createElement('option');
      opt.value = c.id_client;
      opt.textContent = `${c.nom} ${c.prenom} — ${c.telephone || ''}`.trim();
      selectClient.appendChild(opt);
      selectClient.value = String(c.id_client);

      // Reset + fermer le bloc
      formAjout.reset();
      blocClient.style.display = 'none';

      // Message visuel simple
      alert(data.msg || 'Client ajouté.');
    } else {
      // Fallback : si pas JSON, on recharge pour récupérer la nouvelle liste
      window.location.reload();
    }
  } catch (err) {
    console.error(err);
    alert(err.message || 'Une erreur est survenue.');
  }
});

});
</script>

</div>
<?php require_once 'affichage/_fin.inc.php'; ?>

