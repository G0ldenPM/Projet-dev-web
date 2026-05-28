<?php
//Modifier le profil

require_once 'fonctions.php';
require_once 'connexion_db.php';

exiger_connexion();

$user_id = $_SESSION['user_id'];
$erreur  = '';

// Récupère les infos actuelles
$stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// --- Traitement formulaire infos personnelles ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'infos') {

    $nom        = trim($_POST['nom']        ?? '');
    $prenom     = trim($_POST['prenom']     ?? '');
    $email      = trim($_POST['email']      ?? '');
    $telephone  = trim($_POST['telephone']  ?? '');
    $adresse    = trim($_POST['adresse']    ?? '');
    $ville      = trim($_POST['ville']      ?? '');
    $code_postal= trim($_POST['code_postal']?? '');
    $date_naissance = trim($_POST['date_naissance'] ?? '');

    if (empty($nom) || empty($prenom) || empty($email)) {
        $erreur = 'Nom, prénom et email sont obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Adresse e-mail invalide.';
    } else {
        // Vérifie que l'email n'est pas déjà pris par quelqu'un d'autre
        $stmt = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ? AND id != ?');
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $erreur = 'Cette adresse e-mail est déjà utilisée par un autre compte.';
        } else {
            $stmt = $pdo->prepare('
                UPDATE utilisateurs
                SET nom=?, prenom=?, email=?, telephone=?, adresse=?, ville=?, code_postal=?, date_naissance=?
                WHERE id=?
            ');
            $stmt->execute([
                $nom, $prenom, $email,
                $telephone ?: null,
                $adresse   ?: null,
                $ville     ?: null,
                $code_postal ?: null,
                $date_naissance ?: null,
                $user_id
            ]);

            // Met à jour la session
            $_SESSION['user_nom']    = $nom;
            $_SESSION['user_prenom'] = $prenom;
            $_SESSION['user_email']  = $email;

            // Recharge les données fraîches
            $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE id = ?');
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            rediriger('page_profil.php', 'succes', 'Profil mis à jour avec succès !');
        }
    }
}

// --- Traitement formulaire mot de passe ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mdp') {

    $ancien  = $_POST['ancien_mdp']  ?? '';
    $nouveau = $_POST['nouveau_mdp'] ?? '';
    $confirm = $_POST['confirm_mdp'] ?? '';

    if (!password_verify($ancien, $user['mot_de_passe'])) {
        $erreur = 'Mot de passe actuel incorrect.';
    } elseif (strlen($nouveau) < 8) {
        $erreur = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
    } elseif ($nouveau !== $confirm) {
        $erreur = 'Les nouveaux mots de passe ne correspondent pas.';
    } else {
        $hash = password_hash($nouveau, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?');
        $stmt->execute([$hash, $user_id]);
        rediriger('page_profil.php', 'succes', 'Mot de passe modifié avec succès !');
    }
}

// --- Traitement upload photo de profil ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'photo') {
    if (!empty($_FILES['photo']['tmp_name'])) {
        $ext_autorisees = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $ext_autorisees)) {
            $erreur = 'Format non autorisé. Utilisez JPG, PNG ou WEBP.';
        } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
            $erreur = 'La photo ne doit pas dépasser 2 Mo.';
        } else {
            $dossier = "uploads/profils/";
            if (!is_dir($dossier)) mkdir($dossier, 0755, true);

            $nom_fichier = "profil_{$user_id}." . $ext;
            $chemin      = $dossier . $nom_fichier;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $chemin)) {
                $stmt = $pdo->prepare('UPDATE utilisateurs SET photo_profil = ? WHERE id = ?');
                $stmt->execute([$chemin, $user_id]);
                $user['photo_profil'] = $chemin;
                rediriger('page_profil.php', 'succes', 'Photo de profil mise à jour !');
            } else {
                $erreur = 'Erreur lors de l\'upload. Réessayez.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon profil — AUTOMARKET</title>
    <link rel="stylesheet" href="CSS.css">
    <style>
        .page-profil {
            max-width: 760px;
            margin: 0 auto;
            padding: 20px;
        }
        .section {
            background-color: var(--noir-3);
            border: 1px solid var(--gris-fonce);
            border-radius: var(--radius);
            padding: 26px 30px;
            margin-bottom: 18px;
        }
        .section h2 {
            font-size: 1.3rem;
            color: var(--rouge);
            letter-spacing: 2px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--gris-fonce);
        }
        .grille-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px 20px;
        }
        .grille-form .pleine-largeur { grid-column: 1 / -1; }

        /* Photo de profil */
        .photo-wrapper {
            display: flex;
            align-items: center;
            gap: 24px;
            flex-wrap: wrap;
        }
        .photo-actuelle {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--rouge);
            flex-shrink: 0;
        }
        .upload-zone {
            flex: 1;
            min-width: 200px;
        }

        /* Input file custom */
        input[type="file"] {
            background-color: var(--noir-4);
            border: 1px dashed var(--gris-moyen);
            border-radius: var(--radius);
            color: var(--gris-clair);
            padding: 10px 14px;
            width: 100%;
            font-family: var(--font-corps);
            font-size: 0.88rem;
            cursor: pointer;
            transition: border-color var(--transition);
        }
        input[type="file"]:hover { border-color: var(--rouge); }

        /* Input date */
        input[type="date"] {
            width: 100%;
            background-color: var(--noir-4);
            border: 1px solid var(--gris-fonce);
            border-radius: var(--radius);
            color: var(--blanc);
            font-family: var(--font-corps);
            font-size: 0.95rem;
            padding: 10px 14px;
            outline: none;
            transition: border-color var(--transition);
            color-scheme: dark;
        }
        input[type="date"]:focus { border-color: var(--rouge); }

        .erreur-msg {
            color: var(--rouge);
            font-size: 0.88rem;
            border-left: 3px solid var(--rouge);
            padding-left: 10px;
            margin-bottom: 16px;
        }

        @media (max-width: 600px) {
            .grille-form { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<div class="bandeau_acceuil">
    <h1>AUTOMARKET</h1>
    <div style="display:flex; gap:10px;">
        <button class="boutton" onclick="location.href='page_compte.php'">Mon compte</button>
        <button class="boutton boutton-ghost" onclick="location.href='deconnexion.php'">Déconnexion</button>
    </div>
</div>

<?= afficher_flash() ?>

<div class="page-profil">

    <h1 style="font-size:2rem; margin-bottom:20px; letter-spacing:2px;">Mon profil</h1>

    <?php if ($erreur): ?>
        <p class="erreur-msg"><?= e($erreur) ?></p>
    <?php endif; ?>

    <!-- Photo de profil -->
    <div class="section">
        <h2>Photo de profil</h2>
        <div class="photo-wrapper">
            <img src="<?= e($user['photo_profil']) ?>" alt="Photo de profil" class="photo-actuelle" id="apercu">
            <div class="upload-zone">
                <form method="POST" enctype="multipart/form-data"
                      style="display:flex; flex-direction:column; gap:10px;">
                    <input type="hidden" name="action" value="photo">
                    <label>Choisir une nouvelle photo</label>
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"
                           onchange="apercu(this)">
                    <p style="color:var(--gris-moyen); font-size:0.78rem; font-family:var(--font-label);">
                        JPG, PNG ou WEBP · 2 Mo max
                    </p>
                    <button type="submit" style="align-self:flex-start;">Mettre à jour</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Informations personnelles -->
    <div class="section">
        <h2>Informations personnelles</h2>
        <form method="POST" style="display:flex; flex-direction:column; gap:0;">
            <input type="hidden" name="action" value="infos">
            <div class="grille-form">
                <div>
                    <label>Nom *</label>
                    <input type="text" name="nom" value="<?= e($user['nom']) ?>" required>
                </div>
                <div>
                    <label>Prénom *</label>
                    <input type="text" name="prenom" value="<?= e($user['prenom']) ?>" required>
                </div>
                <div class="pleine-largeur">
                    <label>Adresse e-mail *</label>
                    <input type="email" name="email" value="<?= e($user['email']) ?>" required>
                </div>
                <div>
                    <label>Téléphone</label>
                    <input type="text" name="telephone" value="<?= e($user['telephone'] ?? '') ?>"
                           placeholder="06 00 00 00 00">
                </div>
                <div>
                    <label>Date de naissance</label>
                    <input type="date" name="date_naissance"
                           value="<?= e($user['date_naissance'] ?? '') ?>">
                </div>
                <div class="pleine-largeur">
                    <label>Adresse</label>
                    <input type="text" name="adresse" value="<?= e($user['adresse'] ?? '') ?>"
                           placeholder="12 rue de la Paix">
                </div>
                <div>
                    <label>Ville</label>
                    <input type="text" name="ville" value="<?= e($user['ville'] ?? '') ?>"
                           placeholder="Paris">
                </div>
                <div>
                    <label>Code postal</label>
                    <input type="text" name="code_postal" value="<?= e($user['code_postal'] ?? '') ?>"
                           placeholder="75001">
                </div>
            </div>
            <button type="submit" style="margin-top:20px; align-self:flex-start;">
                Enregistrer les modifications
            </button>
        </form>
    </div>

    <!-- Changer le mot de passe -->
    <div class="section">
        <h2>Changer le mot de passe</h2>
        <form method="POST" style="display:flex; flex-direction:column; gap:14px; max-width:380px;">
            <input type="hidden" name="action" value="mdp">
            <div>
                <label>Mot de passe actuel</label>
                <input type="password" name="ancien_mdp" placeholder="••••••••" required>
            </div>
            <div>
                <label>Nouveau mot de passe <span style="color:var(--gris-moyen); font-size:0.75rem;">(8 min.)</span></label>
                <input type="password" name="nouveau_mdp" placeholder="••••••••" required>
            </div>
            <div>
                <label>Confirmer le nouveau mot de passe</label>
                <input type="password" name="confirm_mdp" placeholder="••••••••" required>
            </div>
            <button type="submit" style="align-self:flex-start;">Changer le mot de passe</button>
        </form>
    </div>

</div>

<script>
// Aperçu de la photo avant upload
function apercu(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('apercu').src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>