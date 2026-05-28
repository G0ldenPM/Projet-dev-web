<?php
//Inscription utilisateur
require_once 'fonctions.php';
require_once 'connexion_db.php';

if (est_connecte()) {
    header('Location: Page_recherche.php');
    exit;
}

$erreur  = '';
$succes  = '';
$donnees = []; // pour re-remplir le formulaire en cas d'erreur

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $donnees = [
        'nom'            => trim($_POST['nom']            ?? ''),
        'prenom'         => trim($_POST['prenom']         ?? ''),
        'email'          => trim($_POST['email']          ?? ''),
        'telephone'      => trim($_POST['telephone']      ?? ''),
        'date_naissance' => trim($_POST['date_naissance'] ?? ''),
    ];
    $mot_de_passe    = $_POST['mot_de_passe']    ?? '';
    $mot_de_passe2   = $_POST['mot_de_passe2']   ?? '';

    // Validations
    if (empty($donnees['nom']) || empty($donnees['prenom']) || empty($donnees['email']) || empty($mot_de_passe)) {
        $erreur = 'Veuillez remplir tous les champs obligatoires.';
    } elseif (!filter_var($donnees['email'], FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Adresse e-mail invalide.';
    } elseif (strlen($mot_de_passe) < 8) {
        $erreur = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif ($mot_de_passe !== $mot_de_passe2) {
        $erreur = 'Les mots de passe ne correspondent pas.';
    } else {
        // Vérifier que l'email n'existe pas déjà
        $stmt = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ?');
        $stmt->execute([$donnees['email']]);
        if ($stmt->fetch()) {
            $erreur = 'Cette adresse e-mail est déjà utilisée.';
        } else {
            // Insertion
            $hash = password_hash($mot_de_passe, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('
                INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, telephone, date_naissance)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $donnees['nom'],
                $donnees['prenom'],
                $donnees['email'],
                $hash,
                $donnees['telephone'] ?: null,
                $donnees['date_naissance'] ?: null,
            ]);

            // Connexion automatique après inscription
            $new_id = $pdo->lastInsertId();
            $_SESSION['user_id']     = $new_id;
            $_SESSION['user_nom']    = $donnees['nom'];
            $_SESSION['user_prenom'] = $donnees['prenom'];
            $_SESSION['user_email']  = $donnees['email'];

            rediriger('Page_recherche.php', 'succes', 'Compte créé ! Bienvenue sur AUTOMARKET, ' . $donnees['prenom'] . ' !');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription — AUTOMARKET</title>
    <link rel="stylesheet" href="CSS.css">
</head>
<body>
<div class="login-wrapper">
    <div class="login-card" style="max-width:500px;">
        <h1>AUTO<br>MARKET</h1>
        <p class="subtitle">Créer un compte</p>

        <?php if ($erreur): ?>
            <p style="color:var(--rouge); font-size:0.88rem; margin-bottom:12px;
                       border-left:3px solid var(--rouge); padding-left:10px;">
                <?= e($erreur) ?>
            </p>
        <?php endif; ?>

        <form method="POST">
            <div style="display:flex; gap:12px;">
                <div style="flex:1;">
                    <label>Nom *</label>
                    <input type="text" name="nom" placeholder="Dupont"
                           value="<?= e($donnees['nom'] ?? '') ?>" required>
                </div>
                <div style="flex:1;">
                    <label>Prénom *</label>
                    <input type="text" name="prenom" placeholder="Jean"
                           value="<?= e($donnees['prenom'] ?? '') ?>" required>
                </div>
            </div>
            <div>
                <label>Adresse e-mail *</label>
                <input type="email" name="email" placeholder="exemple@mail.com"
                       value="<?= e($donnees['email'] ?? '') ?>" required>
            </div>
            <div>
                <label>Téléphone</label>
                <input type="text" name="telephone" placeholder="06 00 00 00 00"
                       value="<?= e($donnees['telephone'] ?? '') ?>">
            </div>
            <div>
                <label>Date de naissance</label>
                <input type="date" name="date_naissance"
                       value="<?= e($donnees['date_naissance'] ?? '') ?>">
            </div>
            <div>
                <label>Mot de passe * <span style="color:var(--gris-moyen); font-size:0.75rem;">(8 caractères min.)</span></label>
                <input type="password" name="mot_de_passe" placeholder="••••••••" required>
            </div>
            <div>
                <label>Confirmer le mot de passe *</label>
                <input type="password" name="mot_de_passe2" placeholder="••••••••" required>
            </div>
            <button type="submit" style="margin-top:8px;">Créer mon compte</button>
        </form>

        <div style="margin-top:16px; text-align:center;">
            <a href="connection.php" style="font-size:0.85rem; color:var(--gris-clair);">
                Déjà un compte ? <span style="color:var(--rouge-vif);">Se connecter</span>
            </a>
        </div>
    </div>
</div>
</body>
</html>
