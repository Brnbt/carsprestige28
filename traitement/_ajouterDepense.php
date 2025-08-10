<?php
/* =============================================================
 * FICHIER: traitement/_ajouterDepense.php
 * Traite l'ajout d'une dépense via POST, puis redirige vers depense.php
 * Dépend de la fonction ajouterDepense(array $data, ?string &$err): bool
 * ============================================================= */

// (Dé)commente/ajuste selon ton arborescence :
include_once '_fonctions.inc.php';
// require_once __DIR__ . '/../includes/fonctions.php';  // là où se trouve ajouterDepense()
// require_once __DIR__ . '/../_init.inc.php';           // autre bootstrap éventuel

// Démarre la session si pas déjà fait (utile pour un token CSRF éventuel)
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// URL de retour vers la page du formulaire
$redirectBase = '../depense.php';

// Petite fonction utilitaire de redirection sûre
function goBack(string $base, array $params = []): void {
    $qs = $params ? ('?' . http_build_query($params)) : '';
    header('Location: ' . $base . $qs);
    exit;
}

// 1) Méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    goBack($redirectBase, ['err' => 'Méthode invalide']);
}

if (!empty($_SESSION['csrf_token']) || !empty($_POST['csrf_token'])) {
    $tokSess = $_SESSION['csrf_token'] ?? '';
    $tokPost = $_POST['csrf_token'] ?? '';
    if (!$tokSess || !$tokPost || !hash_equals($tokSess, $tokPost)) {
        goBack($redirectBase, ['err' => 'Jeton CSRF invalide, veuillez réessayer.']);
    }
}

// 3) Normalisations avant envoi à la fonction métier
$data = $_POST;

// Montant: accepter la virgule française
if (isset($data['montant'])) {
    // on garde seulement chiffres, virgule, point et signe -
    $clean = preg_replace('/[^0-9,.\-]/', '', (string)$data['montant']);
    // remplace virgule par point pour casting float côté fonction
    $data['montant'] = str_replace(',', '.', $clean);
}

// Trim basique de quelques champs texte
foreach (['date_depense','type_depense','description','mode_remboursement'] as $k) {
    if (isset($data[$k])) $data[$k] = trim((string)$data[$k]);
}

// 4) Appel métier
$err = null;
try {
    if (!function_exists('ajouterDepense')) {
        goBack($redirectBase, ['err' => 'Fonction ajouterDepense introuvable.']);
    }

    $ok = ajouterDepense($data, $err);

    if ($ok) {
        goBack($redirectBase, ['msg' => 'Dépense ajoutée avec succès.']);
    } else {
        // $err peut venir de la validation ou de la couche SQL de ta fonction
        goBack($redirectBase, ['err' => $err ?: "Échec de l'ajout de la dépense."]);
    }
} catch (Throwable $e) {
    // Ne pas exposer les détails en prod ; ici on reste concis
    goBack($redirectBase, ['err' => 'Erreur inattendue: ' . $e->getMessage()]);
}
