
<?php
include_once 'affichage/_debut.inc.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Cars Prestige 28</title>
  <meta name="description" content="Service VTC ponctuel, confortable et sécurisé. Transferts aéroport, trajets professionnels et privés. Réservation en 1 minute." />
  <meta property="og:title" content="Votre Chauffeur VTC" />
  <meta property="og:description" content="Transferts aéroport, trajets business & privés. Réservez votre chauffeur en 1 minute." />
  <meta property="og:type" content="website" />
  <meta property="og:locale" content="fr_FR" />
  <link rel="icon" href="/favicon.ico" />
</head>
<body>

  <!-- Hero / Accueil -->
  <main id="accueil" class="hero">
    <div class="container wrap">
      <div>
        <span class="badge">🚘 VTC premium • 24/7 • Paiement sécurisé</span>
        <h1 class="title">Vos trajets, à l'heure et sans stress</h1>
        <p class="lead">Transferts aéroport, rendez-vous professionnels, soirées ou déplacements privés. Un service ponctuel, discret et confortable, au prix fixé à l'avance.</p>
        <div style="display:flex;gap:12px;flex-wrap:wrap">
          <a href="#services" class="btn btn--primary">Voir les services</a>
        </div>
        <p class="note">Assistance 24/7 • Facture instantanée</p>
      </div>

      <form id="reservation" class="hero-card" onsubmit="event.preventDefault(); alert('Merci ! Nous confirmons votre demande sous peu.');">
        <div class="grid2">
          <div>
            <label for="depart">Départ</label>
            <input id="depart" placeholder="Adresse, aéroport…" required />
          </div>
          <div>
            <label for="arrivee">Arrivée</label>
            <input id="arrivee" placeholder="Adresse, hôtel…" required />
          </div>
          <div>
            <label for="date">Date</label>
            <input id="date" type="date" required />
          </div>
          <div>
            <label for="heure">Heure</label>
            <input id="heure" type="time" required />
          </div>
          <div>
            <label for="passagers">Passagers</label>
            <select id="passagers">
              <option>1</option><option>2</option><option>3</option><option>4</option><option>5</option><option>6+</option>
            </select>
          </div>
          <div>
            <label for="bagages">Bagages</label>
            <select id="bagages">
              <option>Léger</option><option>Standard</option><option>Beaucoup</option>
            </select>
          </div>
        </div>
        <div style="display:flex;gap:10px;align-items:center;margin-top:14px">
          <button class="btn btn--primary" type="submit">Obtenir un devis</button>
          <span class="muted">ou appelez-nous au <a href="tel:+33000000000"><strong>+33 0 00 00 00 00</strong></a></span>
        </div>
      </form>
    </div>
  </main>

  <!-- Services -->
  <section id="services">
    <div class="container">
      <h2 class="section-title">Services</h2>
      <p class="section-lead">Un chauffeur privé pour chaque besoin, avec une qualité constante et un prix clair.</p>
      <div class="features">
        <div class="card">
          <h3>Transferts aéroport & gare</h3>
          <p class="muted">Prise en charge à l'heure, suivi de vol, aide aux bagages.</p>
        </div>
        <div class="card">
          <h3>Business & événements</h3>
          <p class="muted">Mise à disposition à l'heure, confidentialité garantie pour vos rendez-vous.</p>
        </div>
        <div class="card">
          <h3>Trajets longue distance</h3>
          <p class="muted">Confort haut de gamme, arrêts à la demande. Alternative sereine au train.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Tarifs -->
  <section id="tarifs" style="background:var(--soft)">
    <div class="container">
      <h2 class="section-title">Tarifs clairs</h2>
      <p class="section-lead">Prix fixes tout compris (péages, bagages, attente). Recevez votre devis instantanément.</p>
      <div class="features">
        <div class="card">
          <h3>Forfait Aéroport</h3>
          <p class="muted">À partir de <strong>XX €</strong> selon l'adresse. Suivi de vol inclus.</p>
        </div>
        <div class="card">
          <h3>Mise à disposition</h3>
          <p class="muted">Dès <strong>XX €/h</strong> avec minimum 2 heures. Eau & Wi‑Fi offerts.</p>
        </div>
        <div class="card">
          <h3>Longue distance</h3>
          <p class="muted">Devis sur mesure au kilomètre. Demandez-nous un prix fixe.</p>
        </div>
      </div>
      <div style="margin-top:18px"><a href="#reservation" class="btn btn--primary">Demander un devis</a></div>
    </div>
  </section>

  <!-- Flotte -->
  <section>
    <div class="container">
      <h2 class="section-title">Nos vehicules</h2>
      <p class="section-lead">Berlines et vans récents, entretenus et équipés pour votre confort.</p>
      <div class="fleet">
        <article class="item">
          <img src="img/voiture/tesla.png" alt="Berline noire" />
          <div class="meta">
            <strong>Telsa Model S</strong>
            <p class="muted">1–3 passagers • 2 bagages</p>
          </div>
        </article>
        <article class="item">
          <img src="https://images.unsplash.com/photo-1511919884226-fd3cad34687c?q=80&w=1600&auto=format&fit=crop" alt="Van premium" />
          <div class="meta">
            <strong>Van</strong>
            <p class="muted">1–7 passagers • 7 bagages</p>
          </div>
        </article>
        <!-- <article class="item">
          <img src="https://images.unsplash.com/photo-1503376780353-7e6692767b70?q=80&w=1600&auto=format&fit=crop" alt="Berline luxe" />
          <div class="meta">
            <strong>Berline Luxe</strong>
            <p class="muted">1–3 passagers • Options premium</p>
          </div>
        </article> -->
      </div>
    </div>
  </section>

  <!-- Avis clients -->
  <section>
    <div class="container">
      <h2 class="section-title">Ils nous recommandent</h2>
      <div class="testi">
        <div class="card"><p>« Chauffeur ponctuel et très professionnel. Réservation facile et prix honnête. »</p><p class="muted">— Claire D.</p></div>
        <div class="card"><p>« Véhicule impeccable, conduite souple, parfaite prise en charge à l'aéroport. »</p><p class="muted">— Mehdi A.</p></div>
        <div class="card"><p>« Idéal pour nos déplacements clients, service discret et fiable. »</p><p class="muted">— Agence Akira</p></div>
      </div>
    </div>
  </section>

  <!-- Contact -->
  <section id="contact" style="background:var(--soft)">
    <div class="container">
      <h2 class="section-title">Contact</h2>
      <p class="section-lead">Une question ? Besoin d'un devis particulier ? Réponse rapide.</p>
      <div class="features">
        <div class="card">
          <h3>Téléphone</h3>
          <p><a href="tel:+33000000000">+33 0 00 00 00 00</a></p>
          <p class="muted">7j/7 • 6h–23h</p>
        </div>
        <div class="card">
          <h3>Email</h3>
          <p><a href="mailto:contact@votrevtc.fr">contact@votrevtc.fr</a></p>
          <p class="muted">Nous répondons sous 1h</p>
        </div>
        <div class="card">
          <h3>WhatsApp</h3>
          <p><a href="https://wa.me/33000000000" target="_blank" rel="noopener">Discuter maintenant</a></p>
          <p class="muted">Devis en direct</p>
        </div>
      </div>

  </section>

  <footer>
    <div class="container row" style="flex-wrap:wrap;gap:8px">
      <div>© <span id="year"></span> Cars Prestige 28 — Tous droits réservés</div>
      <div class="muted">SIREN XXXX XXXX • Mentions légales • CGV • Politique de confidentialité</div>
    </div>
  </footer>

  <script>
    // Année courante
    document.getElementById('year').textContent = new Date().getFullYear();
    // Défilement doux
    document.querySelectorAll('a[href^="#"]').forEach(a=>a.addEventListener('click',e=>{
      const id=a.getAttribute('href').slice(1);
      const el=document.getElementById(id);
      if(el){e.preventDefault();el.scrollIntoView({behavior:'smooth',block:'start'});}
    }));
  </script>
</body>
</html>

