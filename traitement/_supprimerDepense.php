<?php
// traitement/_supprimerDepense.php
declare(strict_types=1);

// 1) Charger tes fonctions (corrige le chemin relatif)
include_once '_fonctions.inc.php';

// 2) Récup connexion PDO (suivant ce que fournit ton projet)
$db = null;
if (function_exists('gestionnaireDeConnexion')) {
  $db = gestionnaireDeConnexion();
} elseif (function_exists('getPDO')) {
  $db = getPDO();
}
if (!($db instanceof PDO)) {
  redirectBack(['err' => "Connexion à la base indisponible."]);
}

// 3) Valider l'ID
$id = $_POST['id_depense'] ?? '';
if (!ctype_digit((string)$id) || (int)$id < 1) {
  redirectBack(['err' => "ID invalide."]);
}
$id = (int)$id;

// 4) Activer les erreurs PDO (utile en dev)
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
  // Vérifier existence (facultatif mais message plus clair)
  $stmt = $db->prepare('SELECT 1 FROM depense WHERE id_depense = :id');
  $stmt->execute([':id' => $id]);
  if (!$stmt->fetchColumn()) {
    redirectBack(['err' => "Aucune dépense #$id."]);
  }

  // Supprimer
  $del = $db->prepare('DELETE FROM depense WHERE id_depense = :id');
  $del->execute([':id' => $id]);

  if ($del->rowCount() > 0) {
    redirectBack(['msg' => "Dépense #$id supprimée."]);
  } else {
    redirectBack(['err' => "La suppression a échoué."]);
  }
} catch (Throwable $e) {
  // En dev tu peux temporairement exposer l'erreur :
  // redirectBack(['err' => "Erreur serveur : " . $e->getMessage()]);
  redirectBack(['err' => "Erreur serveur."]);
}

/**
 * Redirige vers la bonne page d’origine.
 * - Si POST['back'] = 'supprimer' => ../supprimer_depense.php
 * - Si POST['back'] = 'depense'   => ../depense.php
 * - Sinon essaie le referer, puis fallback depense.php
 */
function redirectBack(array $params): void {
  $back = $_POST['back'] ?? '';
  if ($back === 'supprimer') {
    $url = '../supprimer_depense.php';
  } elseif ($back === 'depense') {
    $url = '../depense.php';
  } else {
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $url = (strpos($ref, 'supprimer_depense.php') !== false) ? '../supprimer_depense.php' : '../depense.php';
  }

  $sep = (strpos($url, '?') === false) ? '?' : '&';
  header('Location: ' . $url . $sep . http_build_query($params));
  exit;
}
