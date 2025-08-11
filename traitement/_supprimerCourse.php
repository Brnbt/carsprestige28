<?php
declare(strict_types=1);

// Adapter le chemin si besoin
include_once '_fonctions.inc.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/ajouter_course.php') . '?err=method');
    exit;
}

$id_course = isset($_POST['id_course']) ? (int)$_POST['id_course'] : 0;
$force     = !empty($_POST['force']); // true si coché
$err       = null;

if ($id_course <= 0) {
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/ajouter_course.php') . '?err=id_course');
    exit;
}

$ok = supprimerCourse($id_course, $force, $err);

if ($ok) {
    $msg = urlencode("Course #{$id_course} supprimée avec succès.");
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/ajouter_course.php') . '?msg=' . $msg);
    exit;
}

// échec
$errMsg = $err ? $err : 'Erreur lors de la suppression.';
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/ajouter_course.php') . '?err=' . urlencode($errMsg));
exit;
