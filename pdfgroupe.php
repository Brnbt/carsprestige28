<?php
// facture_groupee.php
declare(strict_types=1);

header('X-Content-Type-Options: nosniff');
header('Content-Type: application/pdf');

require __DIR__ . '/fpdf/fpdf.php';
require_once __DIR__ . '/traitement/_fonctions.inc.php';

// ------- Helpers encodage -------
function t($s){
    $s = (string)$s;
    if (function_exists('iconv')) {
        $r = @iconv('UTF-8','windows-1252//TRANSLIT',$s);
        if ($r !== false) return $r;
    }
    return utf8_decode($s);
}
function eur($n){ return number_format((float)$n, 2, ',', ' ') . ' €'; }

// ------- Récup des IDs (GET ou POST) -------
$idsParam = '';
if (isset($_POST['ids']) && is_array($_POST['ids'])) {
    $idsParam = implode(',', array_map('strval', $_POST['ids']));
} else {
    $idsParam = isset($_GET['ids']) ? trim((string)$_GET['ids']) : '';
}
if ($idsParam === '') { http_response_code(400); die('Aucun id fourni.'); }

$ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $idsParam)), fn($n)=>$n>0)));
if (!count($ids)) { http_response_code(400); die('Liste d’IDs invalide.'); }

// ------- Charger les courses -------
$courses = [];
$clientRef = null;
foreach ($ids as $cid) {
    $c = getCourseWithClientById($cid);
    if (!$c) continue;

    // normalisation
    $c = [
        'id_course'        => (string)($c['id_course'] ?? $cid),
        'date_course'      => (string)($c['date_course'] ?? ''),
        'point_depart'     => (string)($c['point_depart'] ?? ''),
        'point_arrivee'    => (string)($c['point_arrivee'] ?? ''),
        'distance_km'      => (float)($c['distance_km'] ?? 0),
        'prix'             => (float)($c['prix'] ?? 0),
        'mode_paiement'    => (string)($c['mode_paiement'] ?? 'carte'),
        'client_nom'       => trim((string)($c['client_nom'] ?? '')),
        'client_prenom'    => trim((string)($c['client_prenom'] ?? '')),
        'client_telephone' => (string)($c['client_telephone'] ?? ''),
        'client_email'     => (string)($c['client_email'] ?? ''),
    ];

    // verrou : même client
    $lower = function(string $s){ return function_exists('mb_strtolower') ? mb_strtolower($s) : strtolower($s); };
    $thisClientKey = $lower(($c['client_nom'] ?? '').'|'.($c['client_prenom'] ?? '').'|'.($c['client_email'] ?? ''));
    if ($clientRef === null) $clientRef = $thisClientKey;
    if ($thisClientKey !== $clientRef) {
        http_response_code(400);
        die("Toutes les courses doivent appartenir au même client pour une facture groupée.");
    }

    $courses[] = $c;
}
if (!count($courses)) { http_response_code(404); die("Aucune course trouvée."); }

// Tri par date
usort($courses, function($a,$b){
    $tsa = $a['date_course'] ? strtotime(str_replace(' ','T',$a['date_course'])) : 0;
    $tsb = $b['date_course'] ? strtotime(str_replace(' ','T',$b['date_course'])) : 0;
    return $tsa <=> $tsb;
});

// ------- Infos entreprise -------
$ent_nom    = "Cars Prestige 28";
$ent_statut = "Entrepreneur individuel de Transport";
$ent_addr   = "48B rue Philibert Delorme 28260 ANET";
$ent_tel    = "06 61 55 39 83";
$ent_tva    = "";
$ent_siret  = "N° SIRET 928294875";

// ------- TVA -------
$tauxTVA = 10;

// ------- Client -------
$C0 = $courses[0];
$client_fullname = trim(($C0['client_nom'] ?? '').' '.($C0['client_prenom'] ?? ''));
$clientTel   = (string)($C0['client_telephone'] ?? '');
$clientEmail = (string)($C0['client_email'] ?? '');
$contacts    = trim(($clientTel ? "Tél: ".$clientTel : '').($clientEmail ? " · ".$clientEmail : ''));

// numéro facture
$today = new DateTime('now');
$numero_facture = 'FACG'.$today->format('Ymd').'-'.substr(sha1(implode(',',$ids)),0,6);

// ------- PDF -------
class PDF extends FPDF {
  function Header() {
    $logo = __DIR__ . '/img/logopdf.png';
    if (is_file($logo)) {
      try { $this->Image($logo, 15, 10, 40); } catch (\Throwable $e) {}
    }
    $this->Ln(25);
  }
  function Footer() {
    $this->SetY(-15);
    $this->SetFont('Arial','',8);
    $this->Cell(0,10,t('Page ').$this->PageNo().'/{nb}',0,0,'C');
  }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->SetMargins(15,15,15);
$pdf->SetAutoPageBreak(true, 20);
$pdf->AddPage();

// Entête
$yStart = $pdf->GetY();
$pdf->SetFont('Arial','B',11);
$pdf->MultiCell(100,6,t($ent_nom));
$pdf->SetFont('Arial','',10);
$pdf->MultiCell(100,5,t($ent_statut));
$pdf->MultiCell(100,5,t($ent_addr));
$pdf->MultiCell(100,5,t($ent_tel));
if ($ent_tva)   $pdf->MultiCell(100,5,t($ent_tva));
if ($ent_siret) $pdf->MultiCell(100,5,t($ent_siret));

$pdf->SetXY(130, $yStart);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(70,8,t('FACTURE groupée n°'),0,1,'R');
$pdf->SetFont('Arial','',11);
$pdf->SetXY(130, $pdf->GetY());
$pdf->Cell(70,7,t($numero_facture),0,1,'R');
$pdf->SetXY(130, $pdf->GetY());
$pdf->Cell(70,7,t('Le '.$today->format('d/m/Y')),0,1,'R');
$pdf->Ln(6);

// Styles
$colLabelW = 60; $colValueW = 115; $rowH = 8;
$pdf->SetDrawColor(210,210,210);
$pdf->SetLineWidth(0.2);

// section title
$sec = function($txt) use ($pdf){
  $pdf->SetFillColor(12, 15, 46);
  $pdf->SetTextColor(255,255,255);
  $pdf->SetFont('Arial','B',11);
  $pdf->Cell(0,9,t($txt),0,1,'L',true);
  $pdf->Ln(2);
  $pdf->SetTextColor(0,0,0);
};

// helpers tableau Libellé / Valeur
function tableHeader($pdf, $lw, $vw){
  $pdf->SetFont('Arial','B',10);
  $pdf->SetFillColor(245,245,245);
  $pdf->Cell($lw,8,t('Libellé'),1,0,'C',true);
  $pdf->Cell($vw,8,t('Valeur'),1,1,'C',true);
}
function kvRow($pdf, $label, $value, $lw, $vw, $h, $fill=false){
  $pdf->SetFont('Arial','',10);
  $pdf->SetFillColor($fill ? 250 : 255, 255, 255);
  $pdf->Cell($lw,$h,t($label),1,0,'L',true);
  $pdf->Cell($vw,$h,t($value),1,1,'L',true);
}

// Infos client
$sec('Informations client');
tableHeader($pdf, $colLabelW, $colValueW);
$fill=false;
kvRow($pdf,'Nom :',      $client_fullname ?: '—', $colLabelW,$colValueW,$rowH,$fill); $fill=!$fill;
kvRow($pdf,'Contacts :', $contacts ?: '—',        $colLabelW,$colValueW,$rowH,$fill); $fill=!$fill;
$pdf->Ln(4);

/* ------------------------------------------------------------------
   Bloc "Désignation" : détails par course (Libellé / Valeur)
------------------------------------------------------------------- */
/* ------------------------------------------------------------------
   Bloc "Désignation" : détails par course (Libellé / Valeur)
------------------------------------------------------------------- */
$sec('Désignation');

foreach ($courses as $i => $c) {
    // 👉 Saut de page toutes les 2 courses déjà imprimées
    if ($i > 0 && $i % 2 === 0) {
        $pdf->AddPage();
        $sec('Désignation'); // remettre le titre sur la nouvelle page
    }

    // Titre de sous-bloc par course
    $pdf->SetFont('Arial','B',10);
    $titreCourse = 'Course '.($i+1).' — #'.($c['id_course'] ?? '');
    $pdf->Cell(0,8,t($titreCourse),0,1,'L');

    // En-tête Libellé / Valeur
    tableHeader($pdf, $colLabelW, $colValueW);
    $fill=false;

    // Valeurs formatées
    $ts = $c['date_course'] ? strtotime(str_replace(' ','T',$c['date_course'])) : false;
    $dt = $ts ? date('d/m/Y \à H:i',$ts) : ($c['date_course'] ?: '—');
    $depart  = $c['point_depart']  ?: '—';
    $arrivee = $c['point_arrivee'] ?: '—';
    $km   = ($c['distance_km'] ?? 0) > 0 ? number_format((float)$c['distance_km'], 1, ',', ' ').' km' : '—';
    $prix = (float)($c['prix'] ?? 0);

    // Lignes Libellé / Valeur
    kvRow($pdf,'Date et heure de prise en charge :', $dt,     $colLabelW,$colValueW,$rowH,$fill); $fill=!$fill;
    kvRow($pdf,'Lieu de prise en charge :',          $depart, $colLabelW,$colValueW,$rowH,$fill); $fill=!$fill;
    kvRow($pdf,'Destination :',                      $arrivee,$colLabelW,$colValueW,$rowH,$fill); $fill=!$fill;
    kvRow($pdf,'Kilomètres parcourus :',             $km,     $colLabelW,$colValueW,$rowH,$fill); $fill=!$fill;
    kvRow($pdf,'Montant TTC :',                      eur($prix),$colLabelW,$colValueW,$rowH,$fill);

    $pdf->Ln(4);
}


// ------------------------------------------------------------------
// Totaux & TVA (en-têtes : Libellé | Valeur)
// ------------------------------------------------------------------
$total_ttc  = array_reduce($courses, fn($acc,$c)=>$acc + (float)($c['prix'] ?? 0), 0.0);
$total_ht    = round($total_ttc / (1 + $tauxTVA/100), 2);
$montant_tva = round($total_ttc - $total_ht, 2);

$leftW=120; $rightW=60;
$pdf->SetFont('Arial','B',10);
$pdf->SetFillColor(245,245,245);
$pdf->Cell($leftW,8,t('Libellé'),1,0,'L',true);
$pdf->Cell($rightW,8,t('Valeur'),1,1,'R',true);

$pdf->SetFont('Arial','',10);
$pdf->Cell($leftW,8,t('Total HT'),1,0,'L');
$pdf->Cell($rightW,8,t(eur($total_ht)),1,1,'R');

$pdf->Cell($leftW,8,t('TVA 10 %'),1,0,'L');
$pdf->Cell($rightW,8,t(eur($montant_tva)),1,1,'R');

$pdf->SetLineWidth(0.4);
$pdf->SetFont('Arial','B',11);
$pdf->Cell($leftW,10,t('Total TTC'),1,0,'L');
$pdf->Cell($rightW,10,t(eur($total_ttc)),1,1,'R');
$pdf->SetLineWidth(0.2);
$pdf->Ln(6);

// Paiement résumé
$modes = array_values(array_unique(array_map(fn($c)=>strtolower((string)$c['mode_paiement']), $courses)));
$modeTxt = count($modes)===1 ? ucfirst($modes[0]) : 'Modes multiples';
$pdf->SetFont('Arial','B',10);
$pdf->SetFillColor(245,245,245);
$pdf->Cell(60,8,t('Paiement / Échéance'),1,0,'L',true);
$pdf->SetFont('Arial','',10);
$pdf->Cell(120, 8, t($modeTxt), 1, 1, 'L');

// Contact
$pdf->Ln(12);
$pdf->SetFont('Arial','I',9);
$pdf->MultiCell(0,5,t("\nTéléphone : ".$ent_tel."  ·  Email : desire.betabelet@gmail.com"),0,'C');

// -------- SORTIE --------
if (ob_get_length()) { @ob_end_clean(); }
$pdf->Output('I', $numero_facture.'.pdf');
