<?php
//Fonctions utilitaires

session_start();

// Vérifie que l'utilisateur est connecté, sinon redirige
function exiger_connexion() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: connection.php?msg=non_connecte');
        exit;
    }
}

// Nettoie une valeur pour l'affichage (évite les failles XSS)
function e(string $val): string {
    return htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
}

//Session

function est_connecte(): bool {
    return isset($_SESSION['user_id']);
}

function utilisateur_courant(): array {
    return [
        'id'     => $_SESSION['user_id']     ?? null,
        'nom'    => $_SESSION['user_nom']    ?? '',
        'prenom' => $_SESSION['user_prenom'] ?? '',
        'email'  => $_SESSION['user_email']  ?? '',
    ];
}

//Formatage

function formater_prix(float $prix): string {
    return number_format($prix, 0, ',', ' ') . ' €';
}

function formater_km(int $km): string {
    return number_format($km, 0, ',', ' ') . ' km';
}

// Redirections avec message flash

function rediriger(string $url, string $type = 'succes', string $message = ''): void {
    if ($message) {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }
    header("Location: $url");
    exit;
}

function afficher_flash(): string {
    if (!isset($_SESSION['flash'])) return '';
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $couleur = $flash['type'] === 'succes' ? '#4caf50' : '#cc1111';
    return '<p style="color:' . $couleur . '; font-family: var(--font-label);
            letter-spacing:1px; margin: 10px 20px; padding: 12px 16px;
            background: rgba(0,0,0,0.3); border-radius: 6px;
            border-left: 3px solid ' . $couleur . ';">'
           . e($flash['message']) . '</p>';
}
