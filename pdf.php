<?php
// facture.php
require __DIR__ . '/fpdf/fpdf.php';
require_once __DIR__ . '/traitement/_fonctions.inc.php';

header('X-Content-Type-Options: nosniff');

$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($courseId <= 0) { http_response_code(400); die('Paramètre id manquant ou invalide.'); }

// Récupère la course avec client/chauffeur
$course = getCourseWithClientById($courseId);
if (!$course) { http_response_code(404); die("Course #{$courseId} introuvable."); }

// --- Normalisation ---
$id_course   = (string)$course['id_course'];
$date_raw    = (string)($course['date_course'] ?? '');
$depart      = (string)($course['point_depart'] ?? '');
$arrivee     = (string)($course['point_arrivee'] ?? '');
$distanceKm  = (float)($course['distance_km'] ?? 0);
$prixTTC     = (float)($course['prix'] ?? 0);
$mode_paiement = (string)($course['mode_paiement'] ?? 'carte');

$clientNom   = trim((string)($course['client_nom'] ?? ''));
$clientPre   = trim((string)($course['client_prenom'] ?? ''));
$clientTel   = (string)($course['client_telephone'] ?? '');
$clientEmail = (string)($course['client_email'] ?? '');
$client_fullname = trim($clientNom.' '.$clientPre);

// Dates
$ts = $date_raw ? strtotime(str_replace(' ', 'T', $date_raw)) : false;
$date_facture = date('d/m/Y');
$date_prise   = $ts ? date('d/m/Y', $ts) : ($date_raw ?: '');
$heure_prise  = $ts ? date('H:i', $ts) : '';


// TVA (fixe 20 % pour l’exemple)
$tauxTVA = 10;
$total_ttc = $prixTTC;
$total_ht   = round($total_ttc / (1 + $tauxTVA/100), 2);
$montant_tva = round($total_ttc - $total_ht, 2);
$tva10 = 0.0; $tva10 = $montant_tva;

$kilometres = $distanceKm;
$echeance   = "À réception";

// ENTREPRISE
$ent_nom    = "Cars Prestige 28";
$ent_statut = "Entrepreneur individuel de Transport";
$ent_addr   = "48B rue Philibert Delorme 28260 ANET";
$ent_tel    = "06 61 55 39 83";
$ent_tva    = "";
$ent_siret  = "N° SIRET 928294875";

// Helpers
function t($s){ return iconv('UTF-8','windows-1252//TRANSLIT',$s); }
function eur($n){ return number_format((float)$n, 2, ',', ' ') . ' €'; }

$anneeFacture   = $ts ? date('Y', $ts) : date('Y');
$annee  = $ts ? date('Y', $ts) : date('Y');
$mois   = $ts ? date('m', $ts) : date('m');
$jour   = $ts ? date('d', $ts) : date('d');
$numero_facture = 'FAC'.$annee.$mois.$jour.sprintf('%06d', (int)$id_course);


// ---------- PDF ----------
class PDF extends FPDF {
  function Header() {
    $logo = __DIR__ . '/img/logopdf.png';  
    if (is_file($logo)) $this->Image($logo, 15, 10, 40);
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
$pdf->AddPage();
$pdf->SetMargins(15,15,15);

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
$pdf->Cell(70,8,t('FACTURE n°'),0,1,'R');
$pdf->SetFont('Arial','',11);
$pdf->SetXY(130, $pdf->GetY());
$pdf->Cell(70,7,t($numero_facture),0,1,'R');
$pdf->SetXY(130, $pdf->GetY());
$pdf->Cell(70,7,t('Le '.$date_prise),0,1,'R');
$pdf->Ln(6);

// Style commun
$colLabelW = 60; $colValueW = 115; $rowH = 8;
$pdf->SetDrawColor(210,210,210); $pdf->SetLineWidth(0.2);
function sectionTitle($pdf, $txt){
  $pdf->SetFillColor(12, 15, 46);
  $pdf->SetTextColor(255,255,255);
  $pdf->SetFont('Arial','B',11);
  $pdf->Cell(0,9,t($txt),0,1,'L',true);
  $pdf->Ln(2);
  $pdf->SetTextColor(0,0,0);
}
function tableHeader($pdf, $lw, $vw){
  $pdf->SetFont('Arial','B',10);
  $pdf->SetFillColor(245,245,245);
  $pdf->Cell($lw,8,t('Libellé'),1,0,'C',true);
  $pdf->Cell($vw,8,t('Valeur'),1,1,'C',true);
}
function kvRow($pdf, $label, $value, $lw, $vw, $h, $fill=false){
  $pdf->SetFont('Arial','',10);
  if($fill){ $pdf->SetFillColor(250,250,250); } else { $pdf->SetFillColor(255,255,255); }
  $pdf->Cell($lw,$h,t($label),1,0,'L',true);
  $pdf->Cell($vw,$h,t($value),1,1,'L',true);
}

// Infos client
sectionTitle($pdf, 'Informations client');
tableHeader($pdf, $colLabelW, $colValueW);
$fill=false;
$contacts = trim(($clientTel ? "Tél: ".$clientTel : '').($clientEmail ? " · ".$clientEmail : ''));
kvRow($pdf,'Nom :',     $client_fullname ?: '—', $colLabelW,$colValueW,$rowH,$fill); $fill=!$fill;
kvRow($pdf,'Contacts :',$contacts ?: '—',        $colLabelW,$colValueW,$rowH,$fill); $fill=!$fill;
$pdf->Ln(4);

// Désignation
sectionTitle($pdf, 'Désignation');
tableHeader($pdf, $colLabelW, $colValueW);
$fill=false;
kvRow($pdf,'Date et heure de prise en charge :', $date_prise.($heure_prise ? ' à '.$heure_prise : ''), $colLabelW,$colValueW,$rowH,$fill);
kvRow($pdf,'Lieu de prise en charge :', $depart ?: '—',     $colLabelW,$colValueW,$rowH,$fill); $fill=!$fill;
kvRow($pdf,'Destination :',             $arrivee ?: '—',    $colLabelW,$colValueW,$rowH,$fill); $fill=!$fill;
kvRow($pdf,'Kilomètres parcourus :',    ($kilometres>0?number_format($kilometres,1,',',' '):'0').' km', $colLabelW,$colValueW,$rowH,$fill); $fill=!$fill;
$pdf->Ln(6);

// Montants
$leftW=120; $rightW=60;
$pdf->SetFont('Arial','B',10);
$pdf->SetFillColor(245,245,245);
$pdf->Cell($leftW,8,t('Libellé'),1,0,'L',true);
$pdf->Cell($rightW,8,t('Montant (€)'),1,1,'R',true);

$pdf->SetFont('Arial','',10);
$pdf->Cell($leftW,8,t('Total HT'),1,0,'L');
$pdf->Cell($rightW,8,t(eur($total_ht)),1,1,'R');

$pdf->Cell($leftW,8,t('TVA 10 %'),1,0,'L');
$pdf->Cell($rightW,8,t(eur($tva10)),1,1,'R');

$pdf->SetLineWidth(0.4);
$pdf->SetFont('Arial','B',11);
$pdf->Cell($leftW,10,t('Total TTC'),1,0,'L');
$pdf->Cell($rightW,10,t(eur($total_ttc)),1,1,'R');
$pdf->SetLineWidth(0.2);
$pdf->Ln(6);

// Paiement
$pdf->SetFont('Arial','B',10);
$pdf->SetFillColor(245,245,245);
$pdf->Cell(60,8,t('Paiement / Échéance'),1,0,'L',true);
$pdf->SetFont('Arial','',10);
$pdf->Cell(120, 8, t(ucfirst(strtolower($mode_paiement))), 1, 1, 'L');

// --- Ajout bloc contact ---
$pdf->Ln(12);
$pdf->SetFont('Arial','I',9);
$pdf->MultiCell(
    0,
    5,
    t("\nTéléphone : ".$ent_tel."  ·  Email : desire.betabelet@gmail.com"),
    0,
    'C'
);

$pdf->Output('I', $numero_facture.'.pdf');

