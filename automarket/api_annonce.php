<?php
//API annonces (AJAX)
require_once 'fonctions.php';
require_once 'connexion_db.php';

header('Content-Type: application/json');

if (!est_connecte()) {
    echo json_encode(['ok' => false, 'erreur' => 'Non connecté.']);
    exit;
}

$data   = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$user   = utilisateur_courant();

if ($action === 'statut') {
    $id     = (int)($data['id'] ?? 0);
    $statut = $data['statut'] ?? '';

    $statuts_valides = ['active', 'pause', 'vendue'];
    if (!$id || !in_array($statut, $statuts_valides)) {
        echo json_encode(['ok' => false, 'erreur' => 'Paramètres invalides.']);
        exit;
    }

    // Vérifie que l'annonce appartient bien à l'utilisateur connecté
    $stmt = $pdo->prepare('SELECT id_utilisateur FROM annonces WHERE id = ?');
    $stmt->execute([$id]);
    $annonce = $stmt->fetch();

    if (!$annonce || $annonce['id_utilisateur'] != $user['id']) {
        echo json_encode(['ok' => false, 'erreur' => 'Action non autorisée.']);
        exit;
    }

    $stmt = $pdo->prepare('UPDATE annonces SET statut = ? WHERE id = ?');
    $stmt->execute([$statut, $id]);

    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => false, 'erreur' => 'Action inconnue.']);
