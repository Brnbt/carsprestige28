<?php
setlocale(LC_TIME, 'fr_FR.UTF-8');
ob_start();
session_start();
include_once 'traitement/_fonctions.inc.php';

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="CP28">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta data-n-head="ssr" data-hid="og:type" name="og:type" property="og:type" content="website">
    <meta data-n-hd="ssr" data-hid="og:title" name="og:title" property="og:title" content="CP28">
    <meta data-n-head="ssr" data-hid="og:site_name" name="og:site_name" property="og:site_name" content="CP28">
    <meta data-n-head="ssr" data-hid="theme-color" name="theme-color" content="#090a0d">
    <link rel="apple-touch-icon" href="img/img/cp28icon.png">
    <link rel="icon" type="image/png" href="img/img/cp28icon.png">
    <link rel="apple-touch-startup-image" href="img/cp28icon.png">
    <link data-n-head="ssr" rel="shortcut icon" href="img/cp28icon.png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Krona+One&display=swap">
    <link rel="stylesheet" href="css/flag-icons-main/css/flag-icons.css">
    <link rel='stylesheet' href='css/boxicons/css/boxicons.min.css'>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geologica:wght@300&display=swap">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Righteous&family=Roboto:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="script.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<header class="vtc-header">
    <div class="logo">
<a href="index.php" class="logo-accueil" aria-label="Aller à l’accueil" title="Accueil">
  <img src="img/logoblanc.png" alt="Accueil — Logo Chauffeur VTC">
</a>    </div>
    <nav class="nav-links">
        <a href="#accueil">Accueil</a>
        <a href="#services">Services</a>
        <a href="#tarifs">Tarifs</a>
        <a href="facture.php">Facture</a>
        <a href="course.php">Course</a>
        <a href="#contact">Contact</a>
        <a href="#reserver" class="btn-reserver">Réserver</a>
    </nav>
</header>

