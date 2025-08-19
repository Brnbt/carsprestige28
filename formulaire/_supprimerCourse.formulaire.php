<?php
include_once 'traitement/_fonctions.inc.php';

$clients    = getClients();
$chauffeurs = getChauffeurs();
?>

<fieldset >
  <legend>Supprimer une course</legend>

  <h2 class="page-title">Supprimer une course</h2>

<div class="alert warning" style="margin:1rem 0;padding:0.8rem 1rem;border:1px solid #f5c6cb;background:#f8d7da;border-radius:6px;color:#721c24;">
  <strong>⚠️ Attention :</strong> 
  Vous êtes sur le point de supprimer une course.  
  Cette action est <u>définitive</u> et entraînera la perte des données associées (kilomètres, prix, dépenses liées).  
  <br><br>
  Pour confirmer, vous devez indiquer <strong>l’ID de la facture</strong> liée à cette course.  
  Vérifiez attentivement cet identifiant dans la liste des factures avant de valider la suppression.
</div>
<!-- ✅ Fin note -->


  <form id="form-supprimer-course" method="post" action="traitement/_supprimerCourse.php">
    <label for="id_course_del">ID de la course (exemple : FAC20250604000002 — les derniers chiffres à écrire) </label>
    <input type="number" min="1" step="1" id="id_course_del" name="id_course" required style="width:160px;">

    <br><br>
    <button type="submit" class="btn btn--ghost" style="background:red;">
      Supprimer la course
    </button>
  </form>

  <p style="color:#666;margin-top:8px;font-size:0.95em;">
    ⚠️ Action destructive. La facture sera complètement <strong>supprimée</strong>.
  </p>
</fieldset>

<script>
// petite confirmation avant suppression
document.getElementById('form-supprimer-course').addEventListener('submit', function(e){
  const id = document.getElementById('id_course_del').value.trim();
  if (!id) return; // le required gère le cas vide
  const force = this.querySelector('input[name="force"]:checked') ? ' (FORCÉE)' : '';
  if (!confirm('Supprimer définitivement la course #'+id+force+' ?')) {
    e.preventDefault();
  }
});
</script>

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