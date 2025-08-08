<?php
declare(strict_types=1);

// Inclure les fonctions (adapter le chemin si besoin)
include_once '_fonctions.inc.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: ajouter_course.php?err=Méthode invalide.');
    exit;
}

// Récupération des champs
$id_client     = isset($_POST['id_client'])     ? (int)$_POST['id_client']     : 0;
$id_chauffeur  = isset($_POST['id_chauffeur'])  ? (int)$_POST['id_chauffeur']  : 0;
$date_course   = trim($_POST['date_course']   ?? '');
$point_depart  = trim($_POST['point_depart']  ?? '');
$point_arrivee = trim($_POST['point_arrivee'] ?? '');
$distance_km   = isset($_POST['distance_km']) ? (float)$_POST['distance_km'] : null;
$prix          = isset($_POST['prix'])        ? (float)$_POST['prix']        : null;
$statut        = $_POST['statut'] ?? 'en attente';

// Validations minimales (même logique que ton exemple)
if (
    $id_client <= 0 ||
    $id_chauffeur <= 0 ||
    $date_course === '' ||
    $point_depart === '' ||
    $point_arrivee === '' ||
    $distance_km === null || $distance_km < 0 ||
    $prix === null || $prix < 0
) {
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'ajouter_course.php'));
    exit;
}

// Normalisation de la date "YYYY-MM-DDTHH:MM" -> "YYYY-MM-DD HH:MM:SS"
$dt = DateTime::createFromFormat('Y-m-d\TH:i', $date_course);
if (!$dt) {
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'ajouter_course.php'));
    exit;
}
$date_sql = $dt->format('Y-m-d H:i:s');

// (Optionnel) Vérifier existence client/chauffeur si tu as ajouté ces fonctions
if (function_exists('getClientById') && !getClientById($id_client)) {
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'ajouter_course.php'));
    exit;
}
if (function_exists('getChauffeurById') && !getChauffeurById($id_chauffeur)) {
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'ajouter_course.php'));
    exit;
}

// Insertion
$id_course = insertCourse(
    $date_sql,
    $point_depart,
    $point_arrivee,
    $distance_km,
    $prix,
    $statut,
    $id_client,
    $id_chauffeur
);

// Même logique que ton exemple : on renvoie vers la page précédente
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'ajouter_course.php'));
exit;
