<?php include_once 'affichage/_debut.inc.php'
// --- Derniers clients ---
$clients = getClients();

// --- Courses récentes ---
$courses = getCourses();

// --- Dépenses récentes ---
$depenses = getDepenses();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Accueil</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <!-- Header -->
  <header class="vtc-header">
    <div class="logo"><h2>🚖 Mon VTC</h2></div>
    <nav class="nav-links">
      <a href="course.php">Courses</a>
      <a href="depense.php">Dépenses</a>
      <a href="facture.php">Factures</a>
    </nav>
  </header>

  <main class="container">
    <section>
      <h1 class="section-title">Derniers Clients</h1>
      <div class="features">
        <?php foreach($clients as $c): ?>
          <div class="card">
            <h3><?= htmlspecialchars($c['nom']) ?> <?= htmlspecialchars($c['prenom']) ?></h3>
            <p class="muted"><?= htmlspecialchars($c['telephone']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section>
      <h1 class="section-title">Courses Récentes</h1>
      <div class="features">
        <?php foreach($courses as $co): ?>
          <div class="card">
            <h3>Course #<?= $co['id'] ?></h3>
            <p>De: <?= htmlspecialchars($co['depart']) ?> → <?= htmlspecialchars($co['arrivee']) ?></p>
            <p class="muted"><?= $co['date_course'] ?> | <?= $co['prix'] ?> €</p>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section>
      <h1 class="section-title">Dépenses Récentes</h1>
      <div class="features">
        <?php foreach($depenses as $d): ?>
          <div class="card">
            <h3><?= htmlspecialchars($d['titre']) ?></h3>
            <p class="muted"><?= $d['date_depense'] ?> | <?= $d['montant'] ?> €</p>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  </main>

  <footer>
    <p>&copy; <?= date('Y') ?> Mon VTC - Tableau de bord</p>
  </footer>
</body>
</html>
