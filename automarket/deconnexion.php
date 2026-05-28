<?php
//Déconnexion
require_once 'fonctions.php';

// Détruire toutes les données de session
$_SESSION = [];
session_destroy();

// Supprimer le cookie de session
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

header('Location: connection.php?msg=deconnecte');
exit;
