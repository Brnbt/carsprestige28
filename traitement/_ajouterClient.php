<?php
include_once '_fonctions.inc.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ajouter_client.php?err=Méthode invalide.');
  exit;
}

$nom       = trim($_POST['nom'] ?? '');
$prenom    = trim($_POST['prenom'] ?? '');
$telephone = trim($_POST['telephone'] ?? '');
$email     = trim($_POST['email'] ?? '');

if ($nom === '' || $prenom === '' || $telephone === '') {
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

// ✅ Mise en forme
$nom    = strtoupper($nom); // tout en majuscules
$prenom = ucfirst(strtolower($prenom)); // première lettre en majuscule, le reste en minuscule

$id_client = insertClient($nom, $prenom, $telephone, $email);

if ($id_client) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
} else {
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}
