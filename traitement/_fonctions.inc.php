<?php
declare(strict_types=1);

/* =========================
   Connexion (inchangée)
   ========================= */
function gestionnaireDeConnexion()
{
    $user = 'carsprestige28';
    $pass = 'C9b51vf89*Dmy(bl';
    $dsn = 'mysql:host=192.168.1.175:9510;dbname=carsprestige29';

    try {
        $database = new PDO($dsn, $user, $pass);
        $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $database;
    } catch (PDOException $e) {
        die('Connection failed: ' . $e->getMessage());
    }
}

/* =========================
   Clients
   ========================= */

function getClients(): array
{
    $db = gestionnaireDeConnexion();
    $sql = "
        SELECT c.id_client, c.nom, c.prenom, c.telephone, c.email
        FROM client c
        ORDER BY c.nom, c.prenom
    ";
    return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function getClientById(int $id)
{
    $db = gestionnaireDeConnexion();
    $sql = "
        SELECT c.id_client, c.nom, c.prenom, c.telephone, c.email
        FROM client c
        WHERE c.id_client = :id
        LIMIT 1
    ";
    $st = $db->prepare($sql);
    $st->execute([':id' => $id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: false;
}

function insertClient(string $nom, string $prenom, string $telephone, ?string $email)
{
    $db  = gestionnaireDeConnexion();
    $sql = "INSERT INTO client (nom, prenom, telephone, email)
            VALUES (:nom, :prenom, :telephone, :email)";
    $ok  = $db->prepare($sql)->execute([
        ':nom'       => $nom,
        ':prenom'    => $prenom,
        ':telephone' => $telephone,
        ':email'     => ($email === '' ? null : $email),
    ]);
    return $ok ? $db->lastInsertId() : false;
}

/* --- alias rétrocompatibilité --- */
function getAllClient(): array { return getClients(); }

/* =========================
   Chauffeurs & Véhicules
   ========================= */

function getChauffeurById(int $id)
{
    $db = gestionnaireDeConnexion();
    $sql = "
        SELECT ch.id_chauffeur, ch.nom, ch.prenom, ch.telephone, ch.email
        FROM chauffeur ch
        WHERE ch.id_chauffeur = :id
        LIMIT 1
    ";
    $st = $db->prepare($sql);
    $st->execute([':id' => $id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: false;
}

function getChauffeurs(): array
{
    $db = gestionnaireDeConnexion();
    $sql = "
        SELECT
            ch.id_chauffeur, ch.nom, ch.prenom, ch.telephone, ch.email,
            ch.numero_permis, ch.date_validite_permis,
            v.marque AS vehicule_marque, v.modele AS vehicule_modele, v.immatriculation AS vehicule_immatriculation
        FROM chauffeur ch
        LEFT JOIN vehicule v ON ch.vehicule_id = v.id_vehicule
        ORDER BY ch.nom, ch.prenom
    ";
    return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function getVehicules(): array
{
    $db = gestionnaireDeConnexion();
    $sql = "
        SELECT v.id_vehicule, v.marque, v.modele, v.immatriculation, v.couleur, v.date_mise_en_service
        FROM vehicule v
        ORDER BY v.marque, v.modele
    ";
    return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/* --- alias rétrocompatibilité --- */
function getAllChauffeur(): array { return getChauffeurs(); }

/* =========================
   Courses
   ========================= */

function getCourses(): array
{
    $db = gestionnaireDeConnexion();
    $sql = "
        SELECT
            co.id_course, co.date_course, co.point_depart, co.point_arrivee,
            co.distance_km, co.prix, co.statut,
            cl.nom   AS nom_client,    cl.prenom   AS prenom_client,
            ch.nom   AS nom_chauffeur, ch.prenom   AS prenom_chauffeur,
            v.marque AS vehicule_marque, v.modele  AS vehicule_modele, v.immatriculation AS vehicule_immatriculation
        FROM course co
        INNER JOIN client   cl ON co.id_client    = cl.id_client
        INNER JOIN chauffeur ch ON co.id_chauffeur = ch.id_chauffeur
        LEFT  JOIN vehicule  v  ON ch.vehicule_id  = v.id_vehicule
        ORDER BY co.date_course DESC
    ";
    return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function insertCourse(
    string $date_course,
    string $point_depart,
    string $point_arrivee,
    float  $distance_km,
    float  $prix,
    string $mode_paiement,
    string $statut,
    int    $id_client,
    int    $id_chauffeur
) {
    $db  = gestionnaireDeConnexion();
    $sql = "
        INSERT INTO course (date_course, point_depart, point_arrivee, distance_km, prix, mode_paiement, statut, id_client, id_chauffeur)
        VALUES (:date_course, :point_depart, :point_arrivee, :distance_km, :prix, :mode_paiement, :statut, :id_client, :id_chauffeur)
    ";
    $ok = $db->prepare($sql)->execute([
        ':date_course'   => $date_course,
        ':point_depart'  => $point_depart,
        ':point_arrivee' => $point_arrivee,
        ':distance_km'   => $distance_km,
        ':prix'          => $prix,
        ':mode_paiement' => $mode_paiement,
        ':statut'        => $statut,
        ':id_client'     => $id_client,
        ':id_chauffeur'  => $id_chauffeur,
    ]);
    return $ok ? $db->lastInsertId() : false;
}

function getCoursesByClient(int $clientId, int $limit = 1000): array
{
    if ($clientId <= 0) return [];
    $limit = max(1, min(2000, $limit));

    $db = gestionnaireDeConnexion();
    $sql = "
        SELECT
            co.id_course, co.date_course, co.point_depart, co.point_arrivee,
            co.distance_km, co.prix, co.mode_paiement, co.statut,
            ch.nom AS chauffeur_nom, ch.prenom AS chauffeur_prenom
        FROM course co
        LEFT JOIN chauffeur ch ON ch.id_chauffeur = co.id_chauffeur
        WHERE co.id_client = :id_client
        ORDER BY co.date_course DESC
        LIMIT $limit
    ";
    $st = $db->prepare($sql);
    $st->execute([':id_client' => $clientId]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function getCourseWithClientById(int $id): array
{
    if ($id <= 0) return [];
    $db = gestionnaireDeConnexion();
    $sql = "
        SELECT
            co.id_course, co.date_course, co.point_depart, co.point_arrivee,
            co.distance_km, co.prix, co.statut,
            cl.id_client, cl.nom AS client_nom, cl.prenom AS client_prenom,
            cl.telephone AS client_telephone, cl.email AS client_email,
            ch.nom AS chauffeur_nom, ch.prenom AS chauffeur_prenom,
            fa.id_facture, fa.date_facture, fa.montant AS facture_montant,
            fa.mode_paiement, fa.statut AS facture_statut
        FROM course co
        JOIN client    cl ON cl.id_client    = co.id_client
        LEFT JOIN chauffeur ch ON ch.id_chauffeur = co.id_chauffeur
        LEFT JOIN facture  fa ON fa.id_course   = co.id_course
        WHERE co.id_course = :id
        LIMIT 1
    ";
    $st = $db->prepare($sql);
    $st->execute([':id' => $id]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: [];
}

/* =========================
   Factures
   ========================= */

function getFactures(): array
{
    $db = gestionnaireDeConnexion();
    $sql = "
        SELECT 
            f.id_facture, f.date_facture, f.montant, f.mode_paiement, f.statut AS statut_facture,
            c.date_course,
            cl.nom AS nom_client, cl.prenom AS prenom_client
        FROM facture f
        INNER JOIN course c ON f.id_course = c.id_course
        INNER JOIN client cl ON c.id_client = cl.id_client
        ORDER BY f.date_facture DESC
    ";
    return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function getFactureByCourseId(int $courseId): array
{
    if ($courseId <= 0) return [];
    $db  = gestionnaireDeConnexion();
    $sql = "SELECT * FROM facture WHERE id_course = :id LIMIT 1";
    $st  = $db->prepare($sql);
    $st->execute([':id' => $courseId]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: [];
}

/** Crée une facture si absente pour la course donnée. */
function createFactureIfMissing(int $courseId): array
{
    $db = gestionnaireDeConnexion();

    $fa = getFactureByCourseId($courseId);
    if (!empty($fa)) return $fa;

    $st = $db->prepare("SELECT prix FROM course WHERE id_course = :id");
    $st->execute([':id' => $courseId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) return [];

    $db->prepare("
        INSERT INTO facture (id_course, date_facture, montant, mode_paiement, statut)
        VALUES (:id_course, CURDATE(), :montant, 'carte', 'impayée')
    ")->execute([
        ':id_course' => $courseId,
        ':montant'   => (float)$row['prix'],
    ]);

    return getFactureByCourseId($courseId);
}

/* =========================
   Reporting
   ========================= */

function getCoursesGroupedByWeek(string $from, string $to, ?int $clientId = null, int $limit = 52): array
{
    $db    = gestionnaireDeConnexion();
    $limit = max(1, (int)$limit);
    $toEnd = $to . ' 23:59:59';

    $where  = "co.date_course BETWEEN :from AND :to";
    $params = [':from' => $from, ':to' => $toEnd];

    if (!empty($clientId)) {
        $where .= " AND co.id_client = :clientId";
        $params[':clientId'] = $clientId;
    }

    $sql = "
        SELECT
            CONCAT(SUBSTRING(YEARWEEK(co.date_course, 1), 1, 4), 'W',
                   LPAD(SUBSTRING(YEARWEEK(co.date_course, 1), 5, 2), 2, '0')) AS yw,
            DATE_FORMAT(STR_TO_DATE(CONCAT(YEARWEEK(co.date_course, 1), ' Monday'), '%x%v %W'), '%Y-%m-%d') AS week_start,
            DATE_FORMAT(DATE_ADD(STR_TO_DATE(CONCAT(YEARWEEK(co.date_course, 1), ' Monday'), '%x%v %W'), INTERVAL 6 DAY), '%Y-%m-%d') AS week_end,
            COUNT(*) AS nb_courses,
            SUM(COALESCE(co.distance_km, 0)) AS total_km,
            SUM(COALESCE(co.prix, 0))        AS total_prix
        FROM course co
        WHERE $where
        GROUP BY YEARWEEK(co.date_course, 1)
        ORDER BY YEARWEEK(co.date_course, 1) DESC
        LIMIT $limit
    ";

    $st = $db->prepare($sql);
    foreach ($params as $k => $v) $st->bindValue($k, $v);
    $st->execute();

    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/* =========================
   Dépenses
   ========================= */

function ajouterDepense(array $data, ?string &$err = null): bool
{
    $err = null;

    $id_chauffeur = isset($data['id_chauffeur']) ? (int)$data['id_chauffeur'] : 0;
    $id_course    = ($data['id_course']   ?? '') !== '' ? (int)$data['id_course']   : null;
    $id_vehicule  = ($data['id_vehicule'] ?? '') !== '' ? (int)$data['id_vehicule'] : null;
    $date_raw     = trim($data['date_depense'] ?? '');
    $type         = $data['type_depense'] ?? '';
    $montant      = isset($data['montant']) ? (float)$data['montant'] : null;
    $description  = trim($data['description'] ?? '');
    $refacturable = isset($data['refacturable_client']) ? 1 : 0;
    $mode_remb    = $data['mode_remboursement'] ?? 'non_rembourse';

    $allowedTypes = ['carburant','péage','parking','location_vehicule','entretien','autre'];
    $allowedRemb  = ['cash','virement','non_rembourse'];

    if ($id_chauffeur <= 0) { $err = 'Chauffeur obligatoire'; return false; }
    if ($date_raw === '')   { $err = 'Date de dépense manquante'; return false; }
    if (!in_array($type, $allowedTypes, true)) { $err = 'Type de dépense invalide'; return false; }
    if (!is_numeric($montant) || $montant < 0) { $err = 'Montant invalide'; return false; }
    if (!in_array($mode_remb, $allowedRemb, true)) { $err = 'Mode de remboursement invalide'; return false; }

    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $date_raw);
    if (!$dt) { $err = 'Format de date invalide'; return false; }
    $date_sql = $dt->format('Y-m-d H:i:s');

    try {
        $db  = gestionnaireDeConnexion();
        $sql = "
            INSERT INTO depense (
              id_chauffeur, id_course, id_vehicule,
              date_depense, type_depense, montant, description,
              refacturable_client, mode_remboursement
            ) VALUES (
              :id_chauffeur, :id_course, :id_vehicule,
              :date_depense, :type_depense, :montant, :description,
              :refacturable_client, :mode_remboursement
            )";
        return $db->prepare($sql)->execute([
            ':id_chauffeur'        => $id_chauffeur,
            ':id_course'           => $id_course,
            ':id_vehicule'         => $id_vehicule,
            ':date_depense'        => $date_sql,
            ':type_depense'        => $type,
            ':montant'             => $montant,
            ':description'         => ($description === '' ? null : $description),
            ':refacturable_client' => $refacturable,
            ':mode_remboursement'  => $mode_remb,
        ]);
    } catch (Throwable $e) {
        $err = 'SQL: ' . $e->getMessage();
        return false;
    }
}

function getDepenses(?int $id_chauffeur = null, ?string $from = null, ?string $to = null): array
{
    $db = gestionnaireDeConnexion();
    $where  = [];
    $params = [];

    if ($id_chauffeur) { $where[] = 'd.id_chauffeur = :idc'; $params[':idc'] = $id_chauffeur; }
    if ($from)         { $where[] = 'd.date_depense >= :from'; $params[':from'] = $from; }
    if ($to)           { $where[] = 'd.date_depense <= :to';   $params[':to']   = $to; }

    $sql = 'SELECT d.*, ch.nom, ch.prenom, v.immatriculation
            FROM depense d
            LEFT JOIN chauffeur ch ON ch.id_chauffeur = d.id_chauffeur
            LEFT JOIN vehicule  v  ON v.id_vehicule   = d.id_vehicule';
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= ' ORDER BY d.date_depense DESC LIMIT 200';

    $st = $db->prepare($sql);
    foreach ($params as $k => $v) $st->bindValue($k, $v);
    $st->execute();
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/* =========================
   Suppressions sécurisées
   ========================= */

function supprimerCourse(int $id_course, bool $force = false, ?string &$err = null): bool
{
    $err = null;
    if ($id_course <= 0) { $err = 'ID de course invalide.'; return false; }

    $db = gestionnaireDeConnexion();
    try {
        $db->beginTransaction();

        $st = $db->prepare("SELECT id_course FROM course WHERE id_course = :id FOR UPDATE");
        $st->execute([':id' => $id_course]);
        if (!$st->fetch(PDO::FETCH_ASSOC)) {
            $db->rollBack(); $err = 'Course introuvable.'; return false;
        }

        $sf = $db->prepare("SELECT id_facture, statut FROM facture WHERE id_course = :id LIMIT 1");
        $sf->execute([':id' => $id_course]);
        $facture = $sf->fetch(PDO::FETCH_ASSOC);

        if ($facture && !$force && strtolower((string)$facture['statut']) !== 'impayée') {
            $db->rollBack();
            $err = "La course possède une facture non 'impayée'. Utilisez \$force = true pour forcer la suppression.";
            return false;
        }

        if ($facture) {
            $db->prepare("DELETE FROM facture WHERE id_course = :id")->execute([':id' => $id_course]);
        }

        $db->prepare("UPDATE depense SET id_course = NULL WHERE id_course = :id")->execute([':id' => $id_course]);
        $db->prepare("DELETE FROM course WHERE id_course = :id")->execute([':id' => $id_course]);

        $db->commit();
        return true;

    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        $err = 'SQL: ' . $e->getMessage();
        return false;
    }
}

function supprimerDepense(int $id_depense, ?string &$err = null): bool
{
    $err = null;
    if ($id_depense <= 0) { $err = 'ID de dépense invalide.'; return false; }

    try {
        $db = gestionnaireDeConnexion();

        $chk = $db->prepare("SELECT id_depense FROM depense WHERE id_depense = :id LIMIT 1");
        $chk->execute([':id' => $id_depense]);
        if (!$chk->fetch(PDO::FETCH_ASSOC)) { $err = 'Dépense introuvable.'; return false; }

        $db->prepare("DELETE FROM depense WHERE id_depense = :id")->execute([':id' => $id_depense]);
        return true;

    } catch (Throwable $e) {
        $err = 'SQL: ' . $e->getMessage();
        return false;
    }
}
