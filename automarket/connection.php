<?php
//Connexion utilisateur

require_once 'fonctions.php';
require_once 'connexion_db.php';

$erreur = '';

// Si déjà connecté, rediriger directement
if (est_connecte()) {
    header('Location: Page_recherche.php');
    exit;
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'connexion') {

    $email      = trim($_POST['email']      ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';

    if (empty($email) || empty($mot_de_passe)) {
        $erreur = 'Veuillez remplir tous les champs.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($mot_de_passe, $user['mot_de_passe'])) {
            // Connexion réussie : on crée la session
            $_SESSION['user_id']     = $user['id'];
            $_SESSION['user_nom']    = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_email']  = $user['email'];

            rediriger('Page_recherche.php', 'succes', 'Bienvenue, ' . $user['prenom'] . ' !');
        } else {
            $erreur = 'Email ou mot de passe incorrect.';
        }
    }
}

// Traitement du formulaire "mot de passe oublié"
$msg_reset = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset') {
    $email_reset = trim($_POST['reset_email'] ?? '');
//rajouter système envoi mail
    $msg_reset = 'Si cet email existe, un lien de réinitialisation a été envoyé.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion — AUTOMARKET</title>
    <link rel="stylesheet" href="CSS.css">
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">
        <h1>AUTO<br>MARKET</h1>
        <p class="subtitle">Connectez-vous à votre compte</p>

        <?php if ($erreur): ?>
            <p style="color: var(--rouge); font-size:0.88rem; margin-bottom:12px;
                       border-left: 3px solid var(--rouge); padding-left:10px;">
                <?= e($erreur) ?>
            </p>
        <?php endif; ?>

        <!-- Formulaire de connexion -->
        <form method="POST" id="loginForm">
            <input type="hidden" name="action" value="connexion">
            <div>
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email"
                       placeholder="exemple@mail.com"
                       value="<?= e($_POST['email'] ?? '') ?>" required>
            </div>
            <div>
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe"
                       placeholder="••••••••" required>
            </div>
            <button type="submit" style="margin-top:8px;">Se connecter</button>
        </form>

        <div style="margin-top:16px; text-align:center;">
            <a href="inscription.php" style="font-size:0.85rem; color: var(--gris-clair);">
                Pas encore de compte ? <span style="color:var(--rouge-vif);">S'inscrire</span>
            </a>
        </div>

        <!-- Mot de passe oublié -->
        <div style="margin-top:12px;">
            <button id="forgotBtn" class="boutton-ghost" style="width:100%;" type="button">
                Mot de passe oublié ?
            </button>
        </div>

        <div id="forgotSection" style="display:none; margin-top:20px;">
            <h2 style="font-size:1.3rem; margin-bottom:14px; color: var(--rouge);">Réinitialiser</h2>

            <?php if ($msg_reset): ?>
                <p style="color:#4caf50; font-size:0.88rem; margin-bottom:10px;"><?= e($msg_reset) ?></p>
            <?php endif; ?>

            <form method="POST" id="forgotForm">
                <input type="hidden" name="action" value="reset">
                <div>
                    <label for="reset_email">Votre adresse e-mail</label>
                    <input type="email" id="reset_email" name="reset_email"
                           placeholder="exemple@mail.com" required>
                </div>
                <button type="submit">Envoyer le lien</button>
            </form>
            <a href="#" id="backToLogin"
               style="display:block; margin-top:12px; font-size:0.85rem; color:var(--gris-clair);">
               ← Retour à la connexion
            </a>
        </div>
    </div>
</div>

<script>
    const forgotBtn     = document.getElementById('forgotBtn');
    const forgotSection = document.getElementById('forgotSection');
    const backToLogin   = document.getElementById('backToLogin');
    const loginForm     = document.getElementById('loginForm');

    forgotBtn.addEventListener('click', () => {
        loginForm.style.display      = 'none';
        forgotBtn.style.display      = 'none';
        forgotSection.style.display  = 'block';
    });

    backToLogin.addEventListener('click', (e) => {
        e.preventDefault();
        forgotSection.style.display = 'none';
        loginForm.style.display     = 'flex';
        forgotBtn.style.display     = 'block';
    });

    <?php if ($msg_reset): ?>
        forgotSection.style.display = 'block';
        loginForm.style.display     = 'none';
        forgotBtn.style.display     = 'none';
    <?php endif; ?>
</script>
</body>
</html>
