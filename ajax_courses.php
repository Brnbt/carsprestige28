<?php
include_once 'traitement/_fonctions.inc.php';

$from     = $_GET['from'] ?? '';
$to       = $_GET['to'] ?? '';
$clientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
$limit    = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 1000;

$courses = getCourses($from, $to, $clientId, $limit); // à adapter selon ta fonction existante

if (!$courses) {
    echo "<p>Aucune course trouvée.</p>";
    exit;
}

echo "<table class='table-detail'>";
echo "<thead><tr><th>Client</th><th>Prix (€)</th></tr></thead>";
echo "<tbody>";
foreach ($courses as $c) {
    echo "<tr>";
        echo "<td>" . number_format((float)$c['prix'], 2, ',', ' ') . "</td>";

    echo "<td>" . number_format((float)$c['prix'], 2, ',', ' ') . "</td>";
    echo "</tr>";
}
echo "</tbody></table>";


