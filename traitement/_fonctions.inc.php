<?php

function gestionnaireDeConnexion()
{
    $user = 'adminbbet';
    $pass = 'Pokedex3D&';
    $dsn = 'mysql:host=192.168.1.175:9510;dbname=cars_prestige_28';

    try {
        $database = new PDO($dsn, $user, $pass);
        $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $database;
    } catch (PDOException $e) {
        die('Connection failed: ' . $e->getMessage());
    }
}

function getAllClient()
{
    $pdo = gestionnaireDeConnexion();
    $requeteSql = "SELECT * FROM client";
    $pdoStatement = $pdo->query($requeteSql);
    $liste_client = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
    return $liste_client;
}

function getClientById(int $id)
{
    $db = gestionnaireDeConnexion();
    $sql = "
        SELECT
            c.id_client,
            c.nom,
            c.prenom,
            c.telephone,
            c.email
        FROM client c
        WHERE c.id_client = :id
        LIMIT 1
    ";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    return $client ?: false; // retourne le tableau ou false si introuvable
}

function getChauffeurById(int $id)
{
    $db = gestionnaireDeConnexion();
    $sql = "
        SELECT
            ch.id_chauffeur,
            ch.nom,
            ch.prenom,
            ch.telephone,
            ch.email
        FROM chauffeur ch
        WHERE ch.id_chauffeur = :id
        LIMIT 1
    ";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $chauffeur = $stmt->fetch(PDO::FETCH_ASSOC);
    return $chauffeur ?: false;
}



function getAllChauffeur()
{
    $pdo = gestionnaireDeConnexion();
    $requeteSql = "SELECT * FROM chauffeur";
    $pdoStatement = $pdo->query($requeteSql);
    $liste_chauffeur = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
    return $liste_chauffeur;
}

function getFactures()
{
    $db = gestionnaireDeConnexion();

    $sql = "
        SELECT 
            f.id_facture,
            f.date_facture,
            f.montant,
            f.mode_paiement,
            f.statut AS statut_facture,
            c.date_course,
            cl.nom AS nom_client,
            cl.prenom AS prenom_client
        FROM facture f
        INNER JOIN course c ON f.id_course = c.id_course
        INNER JOIN client cl ON c.id_client = cl.id_client
        ORDER BY f.date_facture DESC
    ";

    $stmt = $db->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getVehicules()
{
    $db = gestionnaireDeConnexion();
    $sql = "
        SELECT
            v.id_vehicule,
            v.marque,
            v.modele,
            v.immatriculation,
            v.couleur,
            v.date_mise_en_service
        FROM vehicule v
        ORDER BY v.marque, v.modele
    ";
    return $db->query($sql)->fetchAll();
}

function getChauffeurs()
{
    $db = gestionnaireDeConnexion();
    $sql = "
        SELECT
            ch.id_chauffeur,
            ch.nom,
            ch.prenom,
            ch.telephone,
            ch.email,
            ch.numero_permis,
            ch.date_validite_permis,
            v.marque AS vehicule_marque,
            v.modele AS vehicule_modele,
            v.immatriculation AS vehicule_immatriculation
        FROM chauffeur ch
        LEFT JOIN vehicule v ON ch.vehicule_id = v.id_vehicule
        ORDER BY ch.nom, ch.prenom
    ";
    return $db->query($sql)->fetchAll();
}

function getClients()
{
    $db = gestionnaireDeConnexion();
    $sql = "
        SELECT
            c.id_client,
            c.nom,
            c.prenom,
            c.telephone,
            c.email
        FROM client c
        ORDER BY c.nom, c.prenom
    ";
    return $db->query($sql)->fetchAll();
}

function getCourses()
{
    $db = gestionnaireDeConnexion();
    $sql = "
        SELECT
            co.id_course,
            co.date_course,
            co.point_depart,
            co.point_arrivee,
            co.distance_km,
            co.prix,
            co.statut,
            cl.nom   AS nom_client,
            cl.prenom AS prenom_client,
            ch.nom   AS nom_chauffeur,
            ch.prenom AS prenom_chauffeur,
            v.marque AS vehicule_marque,
            v.modele AS vehicule_modele,
            v.immatriculation AS vehicule_immatriculation
        FROM course co
        INNER JOIN client   cl ON co.id_client   = cl.id_client
        INNER JOIN chauffeur ch ON co.id_chauffeur = ch.id_chauffeur
        LEFT  JOIN vehicule  v  ON ch.vehicule_id = v.id_vehicule
        ORDER BY co.date_course DESC
    ";
    return $db->query($sql)->fetchAll();
}

function insertClient($nom, $prenom, $telephone, $email)
{
    $db = gestionnaireDeConnexion();

    $sql = "
        INSERT INTO client (nom, prenom, telephone, email)
        VALUES (:nom, :prenom, :telephone, :email)
    ";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':nom', $nom);
    $stmt->bindParam(':prenom', $prenom);
    $stmt->bindParam(':telephone', $telephone);

    if ($email === '') {
        $stmt->bindValue(':email', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindParam(':email', $email);
    }

    if ($stmt->execute()) {
        return $db->lastInsertId();
    }
    return false;
}


function insertCourse($date_course, $point_depart, $point_arrivee, $distance_km, $prix, $mode_paiement, $statut, $id_client, $id_chauffeur)
{
    $db = gestionnaireDeConnexion();

    $sql = "
        INSERT INTO course (date_course, point_depart, point_arrivee, distance_km, prix, mode_paiement, statut, id_client, id_chauffeur)
        VALUES (:date_course, :point_depart, :point_arrivee, :distance_km, :prix, :mode_paiement, :statut, :id_client, :id_chauffeur)
    ";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':date_course', $date_course);
    $stmt->bindParam(':point_depart', $point_depart);
    $stmt->bindParam(':point_arrivee', $point_arrivee);
    $stmt->bindParam(':distance_km', $distance_km);
    $stmt->bindParam(':prix', $prix);
    $stmt->bindParam(':mode_paiement', $mode_paiement);
    $stmt->bindParam(':statut', $statut);
    $stmt->bindParam(':id_client', $id_client);
    $stmt->bindParam(':id_chauffeur', $id_chauffeur);

    if ($stmt->execute()) {
        return $db->lastInsertId(); // retourne l'ID de la course insérée
    }
    return false;
}

function getCoursesByClient(int $clientId, int $limit = 1000): array {
    if ($clientId <= 0) return [];
    $limit = max(1, min(2000, $limit));
    $db = gestionnaireDeConnexion();
    $sql = "
        SELECT
            co.id_course,
            co.date_course,
            co.point_depart,
            co.point_arrivee,
            co.distance_km,
            co.prix,
            co.mode_paiement,
            co.statut,
            ch.nom     AS chauffeur_nom,
            ch.prenom  AS chauffeur_prenom
        FROM course co
        LEFT JOIN chauffeur ch ON ch.id_chauffeur = co.id_chauffeur
        WHERE co.id_client = :id_client
        ORDER BY co.date_course DESC
        LIMIT :lim
    ";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id_client', $clientId, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCourseWithClientById(int $id): array {
    if ($id <= 0) return [];
    $db = gestionnaireDeConnexion();

    $sql = "
        SELECT
            co.id_course,
            co.date_course,
            co.point_depart,
            co.point_arrivee,
            co.distance_km,
            co.prix, -- TTC
            co.statut,

            cl.id_client,
            cl.nom AS client_nom,
            cl.prenom AS client_prenom,
            cl.telephone AS client_telephone,
            cl.email AS client_email,

            ch.nom AS chauffeur_nom,
            ch.prenom AS chauffeur_prenom,

            fa.id_facture,
            fa.date_facture,
            fa.montant AS facture_montant,
            fa.mode_paiement,
            fa.statut AS facture_statut
        FROM course co
        JOIN client cl    ON cl.id_client = co.id_client
        LEFT JOIN chauffeur ch ON ch.id_chauffeur = co.id_chauffeur
        LEFT JOIN facture fa   ON fa.id_course = co.id_course
        WHERE co.id_course = :id
        LIMIT 1
    ";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

function getFactureByCourseId(int $courseId): array {
    if ($courseId <= 0) return [];
    $db = gestionnaireDeConnexion();
    $sql = "SELECT * FROM facture WHERE id_course = :id LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $courseId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Crée une facture si absente pour cette course.
 * - date_facture = CURDATE()
 * - montant = course.prix
 * - mode_paiement = 'carte'
 * - statut = 'impayée'
 * Retourne la facture (existante ou nouvellement créée).
 */
function createFactureIfMissing(int $courseId): array {
    $db = gestionnaireDeConnexion();

    // Existe déjà ?
    $fa = getFactureByCourseId($courseId);
    if (!empty($fa)) return $fa;

    // Récupère le prix TTC de la course
    $stmt = $db->prepare("SELECT prix FROM course WHERE id_course = :id");
    $stmt->bindValue(':id', $courseId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return [];

    $montant = (float)$row['prix'];

    // Crée la facture
    $ins = $db->prepare("
        INSERT INTO facture (id_course, date_facture, montant, mode_paiement, statut)
        VALUES (:id_course, CURDATE(), :montant, 'carte', 'impayée')
    ");
    $ins->bindValue(':id_course', $courseId, PDO::PARAM_INT);
    $ins->bindValue(':montant', $montant);
    $ins->execute();

    return getFactureByCourseId($courseId);
}

function getCoursesGroupedByWeek(string $from, string $to, ?int $clientId = null, int $limit = 52): array
{
    $db = gestionnaireDeConnexion();
    $limit = max(1, (int)$limit);
    $toEnd = $to . ' 23:59:59';

    $where = "co.date_course BETWEEN :from AND :to";
    $params = [
        ':from' => $from,
        ':to'   => $toEnd,
    ];

    if (!empty($clientId)) {
        $where .= " AND co.id_client = :clientId";
        $params[':clientId'] = $clientId;
    }

    // YEARWEEK(date, 1) => semaine ISO (lundi)
    // On calcule le lundi et le dimanche de la semaine via STR_TO_DATE avec %x (année ISO) et %v (semaine ISO).
    $sql = "
        SELECT
            CONCAT(
                SUBSTRING(YEARWEEK(co.date_course, 1), 1, 4),
                'W',
                LPAD(SUBSTRING(YEARWEEK(co.date_course, 1), 5, 2), 2, '0')
            ) AS yw,
            DATE_FORMAT(
                STR_TO_DATE(CONCAT(YEARWEEK(co.date_course, 1), ' Monday'), '%x%v %W'),
                '%Y-%m-%d'
            ) AS week_start,
            DATE_FORMAT(
                DATE_ADD(STR_TO_DATE(CONCAT(YEARWEEK(co.date_course, 1), ' Monday'), '%x%v %W'), INTERVAL 6 DAY),
                '%Y-%m-%d'
            ) AS week_end,
            COUNT(*) AS nb_courses,
            SUM(COALESCE(co.distance_km, 0)) AS total_km,
            SUM(COALESCE(co.prix, 0))        AS total_prix
        FROM course co
        WHERE $where
        GROUP BY YEARWEEK(co.date_course, 1)
        ORDER BY YEARWEEK(co.date_course, 1) DESC
        LIMIT :limit
    ";

    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
