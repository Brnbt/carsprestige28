<?php
// Diagnostic (temporaire)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php-error.log');

// Charger FPDF (chemin absolu sûr)
require __DIR__ . '/fpdf/fpdf.php';

// --- Génération PDF ---
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();

// Police core (latin1)
$pdf->SetFont('Arial', 'B', 16);

// Si ton texte initial est UTF-8, convertis-le en ISO-8859-1 :
$title = "Démo FPDF – Accents éèà ç (UTF-8)";
$title = utf8_decode($title); // pas besoin de mbstring

$pdf->Cell(0, 10, $title, 0, 1);
$pdf->Ln(4);

$pdf->SetFont('Arial', '', 12);
$body = "Hello Synology ! Ceci est un test FPDF sans mbstring.\n— MultiCell, accents, et images.";
$pdf->MultiCell(0, 7, utf8_decode($body));

// Exemple image (facultatif):
// $pdf->Image(__DIR__ . '/logo.png', 10, 50, 30); // nécessite extension gd activée

// Sortie (aucun echo avant !)
$pdf->Output('I', 'test.pdf');
