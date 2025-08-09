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
          <span class="muted">ou appelez-nous au <a href="tel:+33661553983"><strong>+33 6 61 55 39 83</strong></a></span>
        </div>
      </form>
    </div>
  </main>