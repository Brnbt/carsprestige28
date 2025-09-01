<?php
include_once 'traitement/_fonctions.inc.php';

$clients    = getClients();
$chauffeurs = getChauffeurs();
?>
<!-- Google Maps JS API + Places : langue FR, région FR, et callback -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBL6krtGwyPmc-D4ROqX_QNDPfUVZD-hE8&libraries=places&language=fr&region=FR&callback=initMaps" defer></script>

<div class="page-course"><h2 class="zzz">Ajouter une course</h2>

<div class="alert info" style="margin:1rem 0;padding:0.8rem 1rem;border:1px solid #cce5ff;background:#e9f5ff;border-radius:6px;color:#004085;">
  <strong>ℹ️ Utilisation :</strong> 
  Remplissez le formulaire ci-dessous pour enregistrer une nouvelle course.  
  <br><br>
  <strong>Client :</strong> Le <em>nom</em>, le <em>prénom</em> et le <em>numéro de téléphone</em> doivent impérativement être renseignés.  
  <em>L’adresse email est facultative.</em>  
  <br><br>
  Une fois validé, la course apparaîtra automatiquement dans la page facture.
  <br><br>
  <strong>⚠️ Attention :</strong> Si un client n’a pas de numéro, demandez au développeur d’en rajouter un.
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
    <!-- Adresse avec autocomplétion -->
    <input id="adresse_depart" type="text" name="point_depart" placeholder="Saisir l'adresse de départ" autocomplete="off" required style="min-width:320px;">
    <br><br>

    <label>Point d'arrivée :</label>
    <!-- Adresse avec autocomplétion -->
    <input id="adresse_arrivee" type="text" name="point_arrivee" placeholder="Saisir l'adresse d'arrivée" autocomplete="off" required style="min-width:320px;">
    <br><br>

    <label>Distance (km) :</label>
    <input id="distance_km" type="number" step="0.1" name="distance_km" required readonly title="Calculé automatiquement à partir des adresses">
    <small id="distance_info" style="margin-left:8px;color:#555;"></small>
    <br><br>

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

    <!-- Champs cachés pour place_id et coordonnées (utile côté serveur si besoin) -->
    <input type="hidden" id="depart_place_id" name="depart_place_id">
    <input type="hidden" id="arrivee_place_id" name="arrivee_place_id">
    <input type="hidden" id="depart_lat" name="depart_lat">
    <input type="hidden" id="depart_lng" name="depart_lng">
    <input type="hidden" id="arrivee_lat" name="arrivee_lat">
    <input type="hidden" id="arrivee_lng" name="arrivee_lng">

  </fieldset>

  <br>
  <button type="submit" class="btn btn--primary">Ajouter la course</button>
</form>

<script>
// === Autocomplétion + Distance Matrix ===
let departAutocomplete, arriveeAutocomplete;
let departPlace, arriveePlace;

function initMaps() {
  const departInput  = document.getElementById('adresse_depart');
  const arriveeInput = document.getElementById('adresse_arrivee');

const opt = {
  componentRestrictions: { country: ['fr','be','ch'] },
  fields: ['name', 'types', 'formatted_address', 'geometry', 'place_id', 'address_components'],
};



  departAutocomplete  = new google.maps.places.Autocomplete(departInput, opt);
  arriveeAutocomplete = new google.maps.places.Autocomplete(arriveeInput, opt);

  departAutocomplete.addListener('place_changed', () => onPlaceSelected('depart'));
  arriveeAutocomplete.addListener('place_changed', () => onPlaceSelected('arrivee'));

  // Recalcule si on modifie à la main après sélection
  departInput.addEventListener('change', resetIfManual);
  arriveeInput.addEventListener('change', resetIfManual);
}

function onPlaceSelected(which) {
  const ac = (which === 'depart') ? departAutocomplete : arriveeAutocomplete;
  const place = ac.getPlace();
  if (!place || !place.place_id || !place.geometry) return;

  const input = (which === 'depart')
    ? document.getElementById('adresse_depart')
    : document.getElementById('adresse_arrivee');

  let label = place.formatted_address || input.value;

  if (Array.isArray(place.types) && place.name) {
    if (place.types.includes('airport')) {
      const n = place.name.trim();
      label = n.toLowerCase().startsWith('aéroport') ? n : 'Aéroport ' + n;
    } else if (place.types.includes('train_station') || place.types.includes('transit_station')) {
      const n = place.name.trim();
      label = n.toLowerCase().startsWith('gare') ? n : 'Gare ' + n;
    }
  }

  input.value = label;

  if (which === 'depart') {
    departPlace = place;
    document.getElementById('depart_place_id').value = place.place_id || '';
    document.getElementById('depart_lat').value = place.geometry.location.lat();
    document.getElementById('depart_lng').value = place.geometry.location.lng();
  } else {
    arriveePlace = place;
    document.getElementById('arrivee_place_id').value = place.place_id || '';
    document.getElementById('arrivee_lat').value = place.geometry.location.lat();
    document.getElementById('arrivee_lng').value = place.geometry.location.lng();
  }

  tryComputeDistance();
}




function resetIfManual(e) {
  // Si l'utilisateur modifie l'input après la sélection, on efface place_id pour éviter incohérence
  const id = e.target.id;
  if (id === 'adresse_depart') {
    departPlace = null;
    document.getElementById('depart_place_id').value = '';
  } else if (id === 'adresse_arrivee') {
    arriveePlace = null;
    document.getElementById('arrivee_place_id').value = '';
  }
  document.getElementById('distance_km').value = '';
  document.getElementById('distance_info').textContent = '';
}

function tryComputeDistance() {
  if (!departPlace || !arriveePlace) return;

  const service = new google.maps.DistanceMatrixService();

  service.getDistanceMatrix(
    {
      // Utiliser Place ID pour plus de fiabilité
      origins: [{ placeId: departPlace.place_id }],
      destinations: [{ placeId: arriveePlace.place_id }],
      travelMode: google.maps.TravelMode.DRIVING,
      unitSystem: google.maps.UnitSystem.METRIC
    },
    (response, status) => {
      if (status !== 'OK') {
        console.warn('DistanceMatrix status:', status, response);
        document.getElementById('distance_info').textContent = 'Impossible de calculer la distance.';
        return;
      }
      const element = response.rows?.[0]?.elements?.[0];
      if (!element || element.status !== 'OK') {
        document.getElementById('distance_info').textContent = 'Adresse non desservie (vérifiez les points).';
        return;
      }

      // element.distance.value = mètres | element.distance.text = "123 km"
      const meters = element.distance.value;
      const km = meters / 1000;
      const kmRounded = Math.round(km * 10) / 10; // 0,1 km près
      document.getElementById('distance_km').value = kmRounded;

      const dureeText = element.duration?.text || '';
      document.getElementById('distance_info').textContent = dureeText ? `Durée estimée : ${dureeText}` : '';
    }
  );
}

// Optionnel : recalcul à l’envoi si l’utilisateur n’a pas cliqué dans les suggestions
document.getElementById('form-course').addEventListener('submit', function (e) {
  const hasDepart = document.getElementById('depart_place_id').value;
  const hasArrivee = document.getElementById('arrivee_place_id').value;
  const distanceField = document.getElementById('distance_km');

  if (!hasDepart || !hasArrivee) {
    // Dernier recours : lancer un calcul basé sur le texte (moins fiable)
    e.preventDefault();
    const service = new google.maps.DistanceMatrixService();
    service.getDistanceMatrix(
      {
        origins: [document.getElementById('adresse_depart').value],
        destinations: [document.getElementById('adresse_arrivee').value],
        travelMode: google.maps.TravelMode.DRIVING,
        unitSystem: google.maps.UnitSystem.METRIC
      },
      (response, status) => {
        if (status === 'OK') {
          const el = response.rows?.[0]?.elements?.[0];
          if (el && el.status === 'OK') {
            const km = el.distance.value / 1000;
            distanceField.value = Math.round(km * 10) / 10;
            this.submit(); // resoumettre avec distance remplie
            return;
          }
        }
        alert("Merci de sélectionner les adresses proposées par Google pour calculer la distance.");
      }
    );
  }
});
</script>
