<?php
// Déposer une annonce

require_once 'fonctions.php';
require_once 'connexion_db.php';

exiger_connexion();

$user_id = $_SESSION['user_id'];
$erreur  = '';
$donnees = []; // Pour re-remplir le formulaire en cas d'erreur

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Récupère et nettoie toutes les valeurs
    $donnees = [
        'titre'         => trim($_POST['titre']       ?? ''),
        'marque'        => trim($_POST['marque']      ?? ''),
        'modele'        => trim($_POST['modele']      ?? ''),
        'annee'         => (int)($_POST['annee']      ?? 0),
        'prix'          => (float)($_POST['prix']     ?? 0),
        'carburant'     => $_POST['carburant']        ?? '',
        'boite'         => $_POST['boite']            ?? '',
        'motorisation'  => trim($_POST['motorisation']?? ''),
        'kilometrage'   => (int)($_POST['kilometrage']?? 0),
        'nb_places'     => (int)($_POST['nb_places']  ?? 5),
        'nb_portes'     => (int)($_POST['nb_portes']  ?? 5),
        'critair'       => $_POST['critair']          !== '' ? (int)$_POST['critair'] : null,
        'couleur'       => trim($_POST['couleur']     ?? ''),
        'longueur'      => $_POST['longueur']         !== '' ? (int)$_POST['longueur'] : null,
        'largeur'       => $_POST['largeur']          !== '' ? (int)$_POST['largeur']  : null,
        'hauteur'       => $_POST['hauteur']          !== '' ? (int)$_POST['hauteur']  : null,
        'poids'         => $_POST['poids']            !== '' ? (int)$_POST['poids']    : null,
        'description'   => trim($_POST['description'] ?? ''),
    ];

    $carburants_valides = ['Essence','Diesel','Électrique','Hybride'];
    $boites_valides     = ['Manuelle','Automatique'];

    // Validations
    if (empty($donnees['titre'])) {
        $erreur = 'Le titre est obligatoire.';
    } elseif (empty($donnees['marque']) || empty($donnees['modele'])) {
        $erreur = 'La marque et le modèle sont obligatoires.';
    } elseif ($donnees['annee'] < 1900 || $donnees['annee'] > (int)date('Y') + 1) {
        $erreur = 'Année invalide.';
    } elseif ($donnees['prix'] <= 0) {
        $erreur = 'Le prix doit être supérieur à 0.';
    } elseif (!in_array($donnees['carburant'], $carburants_valides)) {
        $erreur = 'Type de carburant invalide.';
    } elseif (!in_array($donnees['boite'], $boites_valides)) {
        $erreur = 'Type de boîte invalide.';
    } elseif ($donnees['kilometrage'] < 0) {
        $erreur = 'Kilométrage invalide.';
    } else {
        // Insertion de l'annonce
        $stmt = $pdo->prepare('
            INSERT INTO annonces
                (id_utilisateur, titre, marque, modele, annee, prix, carburant, boite,
                 motorisation, kilometrage, nb_places, nb_portes, critair, couleur,
                 longueur, largeur, hauteur, poids, description)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ');
        $stmt->execute([
            $user_id,
            $donnees['titre'],   $donnees['marque'],  $donnees['modele'],
            $donnees['annee'],   $donnees['prix'],     $donnees['carburant'],
            $donnees['boite'],   $donnees['motorisation'] ?: null,
            $donnees['kilometrage'], $donnees['nb_places'], $donnees['nb_portes'],
            $donnees['critair'], $donnees['couleur']  ?: null,
            $donnees['longueur'],$donnees['largeur'], $donnees['hauteur'],
            $donnees['poids'],   $donnees['description'] ?: null,
        ]);

        $id_annonce = $pdo->lastInsertId();

        // Gestion des photos uploadées
        if (!empty($_FILES['photos']['tmp_name'][0])) {
            $dossier = "uploads/annonces/{$id_annonce}/";
            if (!is_dir($dossier)) mkdir($dossier, 0755, true);

            $ext_autorisees = ['jpg','jpeg','png','webp'];
            $premiere       = true;

            foreach ($_FILES['photos']['tmp_name'] as $i => $tmp) {
                if (empty($tmp) || $_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) continue;
                if ($_FILES['photos']['size'][$i] > 5 * 1024 * 1024) continue; // 5 Mo max par photo

                $ext = strtolower(pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION));
                if (!in_array($ext, $ext_autorisees)) continue;

                $nom_fichier = "photo_" . ($i + 1) . "." . $ext;
                $chemin      = $dossier . $nom_fichier;

                if (move_uploaded_file($tmp, $chemin)) {
                    $stmt = $pdo->prepare('
                        INSERT INTO images (id_annonce, chemin, est_principale, ordre)
                        VALUES (?, ?, ?, ?)
                    ');
                    $stmt->execute([$id_annonce, $chemin, $premiere, $i]);
                    $premiere = false;
                }
            }
        }

        rediriger("page_annonce.php?id={$id_annonce}", 'succes', 'Annonce publiée avec succès !');
    }
}

$annee_actuelle = (int)date('Y');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Déposer une annonce — AUTOMARKET</title>
    <link rel="stylesheet" href="CSS.css">
    <style>
        .page-annonce-form {
            max-width: 800px;
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
        .grille {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px 20px;
        }
        .grille .col-3 { grid-template-columns: 1fr 1fr 1fr; }
        .pleine { grid-column: 1 / -1; }

        select {
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
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23999' stroke-width='1.5' fill='none'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
        }
        select:focus { border-color: var(--rouge); }
        select option { background-color: var(--noir-3); }

        textarea {
            width: 100%;
            background-color: var(--noir-4);
            border: 1px solid var(--gris-fonce);
            border-radius: var(--radius);
            color: var(--blanc);
            font-family: var(--font-corps);
            font-size: 0.92rem;
            padding: 10px 14px;
            resize: vertical;
            min-height: 120px;
            outline: none;
            transition: border-color var(--transition);
            box-sizing: border-box;
        }
        textarea:focus { border-color: var(--rouge); }

        /* Zone d'upload photos */
        .zone-upload {
            border: 2px dashed var(--gris-fonce);
            border-radius: var(--radius);
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: border-color var(--transition), background-color var(--transition);
            position: relative;
        }
        .zone-upload:hover, .zone-upload.survol {
            border-color: var(--rouge);
            background-color: rgba(204,17,17,0.05);
        }
        .zone-upload input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }
        .zone-upload p { color: var(--gris-clair); font-size:0.9rem; pointer-events:none; }
        .zone-upload .icone { font-size:2.5rem; margin-bottom:8px; pointer-events:none; }

        /* Aperçu des photos sélectionnées */
        .apercu-photos {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
        }
        .apercu-photos img {
            width: 100px;
            height: 75px;
            object-fit: cover;
            border-radius: 4px;
            border: 2px solid var(--gris-fonce);
        }
        .apercu-photos img:first-child {
            border-color: var(--rouge);
        }

        .hint {
            color: var(--gris-moyen);
            font-size: 0.78rem;
            font-family: var(--font-label);
            letter-spacing: 0.5px;
            margin-top: 4px;
        }

        .erreur-msg {
            color: var(--rouge);
            font-size: 0.88rem;
            border-left: 3px solid var(--rouge);
            padding-left: 10px;
            margin-bottom: 16px;
            padding: 10px 14px;
            background: rgba(204,17,17,0.08);
            border-radius: 0 var(--radius) var(--radius) 0;
        }

        @media (max-width: 600px) {
            .grille { grid-template-columns: 1fr; }
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

<div class="page-annonce-form">

    <h1 style="font-size:2rem; margin-bottom:6px; letter-spacing:2px;">Déposer une annonce</h1>
    <p style="color:var(--gris-clair); font-size:0.88rem; font-family:var(--font-label);
               letter-spacing:1px; margin-bottom:20px;">
        Les champs marqués d'un * sont obligatoires
    </p>

    <?php if ($erreur): ?>
        <p class="erreur-msg"><?= e($erreur) ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <!-- SECTION 1 : Titre & identification -->
        <div class="section">
            <h2>Identification du véhicule</h2>
            <div class="grille">
                <div class="pleine">
                    <label>Titre de l'annonce *</label>
                    <input type="text" name="titre" required
                           placeholder="ex: Peugeot 308 SW - Très bon état, révision faite"
                           value="<?= e($donnees['titre'] ?? '') ?>">
                    <p class="hint">Un bon titre attire plus d'acheteurs</p>
                </div>
                <div>
                    <label>Marque *</label>
                    <input type="text" name="marque" required placeholder="ex: Peugeot"
                           value="<?= e($donnees['marque'] ?? '') ?>">
                </div>
                <div>
                    <label>Modèle *</label>
                    <input type="text" name="modele" required placeholder="ex: 308 SW"
                           value="<?= e($donnees['modele'] ?? '') ?>">
                </div>
                <div>
                    <label>Année *</label>
                    <input type="number" name="annee" required
                           min="1900" max="<?= $annee_actuelle + 1 ?>"
                           placeholder="<?= $annee_actuelle ?>"
                           value="<?= e($donnees['annee'] ?? '') ?>">
                </div>
                <div>
                    <label>Motorisation</label>
                    <input type="text" name="motorisation" placeholder="ex: 1.5 BlueHDi 130ch"
                           value="<?= e($donnees['motorisation'] ?? '') ?>">
                </div>
                <div>
                    <label>Couleur</label>
                    <input type="text" name="couleur" placeholder="ex: Gris Platinium"
                           value="<?= e($donnees['couleur'] ?? '') ?>">
                </div>
                <div>
                    <label>Prix (€) *</label>
                    <input type="number" name="prix" required min="1" step="100"
                           placeholder="15900"
                           value="<?= e($donnees['prix'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- SECTION 2 : Motorisation & transmission -->
        <div class="section">
            <h2>Motorisation & transmission</h2>
            <div class="grille">
                <div>
                    <label>Carburant *</label>
                    <select name="carburant" required>
                        <option value="">-- Choisir --</option>
                        <?php foreach (['Essence','Diesel','Électrique','Hybride'] as $c): ?>
                            <option value="<?= $c ?>" <?= ($donnees['carburant'] ?? '') === $c ? 'selected' : '' ?>>
                                <?= $c ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Boîte de vitesse *</label>
                    <select name="boite" required>
                        <option value="">-- Choisir --</option>
                        <?php foreach (['Manuelle','Automatique'] as $b): ?>
                            <option value="<?= $b ?>" <?= ($donnees['boite'] ?? '') === $b ? 'selected' : '' ?>>
                                <?= $b ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Kilométrage (km) *</label>
                    <input type="number" name="kilometrage" required min="0"
                           placeholder="62000"
                           value="<?= e($donnees['kilometrage'] ?? '') ?>">
                </div>
                <div>
                    <label>Crit'Air</label>
                    <select name="critair">
                        <option value="">-- Non renseigné --</option>
                        <?php for ($i = 0; $i <= 5; $i++): ?>
                            <option value="<?= $i ?>" <?= isset($donnees['critair']) && $donnees['critair'] === $i ? 'selected' : '' ?>>
                                Crit'Air <?= $i ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- SECTION 3 : Dimensions -->
        <div class="section">
            <h2>Caractéristiques détaillées</h2>
            <div class="grille">
                <div>
                    <label>Nombre de places</label>
                    <select name="nb_places">
                        <?php for ($i = 2; $i <= 9; $i++): ?>
                            <option value="<?= $i ?>" <?= ($donnees['nb_places'] ?? 5) == $i ? 'selected' : '' ?>>
                                <?= $i ?> places
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label>Nombre de portes</label>
                    <select name="nb_portes">
                        <?php foreach ([2,3,4,5] as $p): ?>
                            <option value="<?= $p ?>" <?= ($donnees['nb_portes'] ?? 5) == $p ? 'selected' : '' ?>>
                                <?= $p ?> portes
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Longueur (mm)</label>
                    <input type="number" name="longueur" min="0" placeholder="4365"
                           value="<?= e($donnees['longueur'] ?? '') ?>">
                </div>
                <div>
                    <label>Largeur (mm)</label>
                    <input type="number" name="largeur" min="0" placeholder="1852"
                           value="<?= e($donnees['largeur'] ?? '') ?>">
                </div>
                <div>
                    <label>Hauteur (mm)</label>
                    <input type="number" name="hauteur" min="0" placeholder="1470"
                           value="<?= e($donnees['hauteur'] ?? '') ?>">
                </div>
                <div>
                    <label>Poids (kg)</label>
                    <input type="number" name="poids" min="0" placeholder="1385"
                           value="<?= e($donnees['poids'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- SECTION 4 : Description -->
        <div class="section">
            <h2>Description</h2>
            <label>Décrivez votre véhicule</label>
            <textarea name="description"
                      placeholder="État général, historique d'entretien, options, défauts éventuels..."
            ><?= e($donnees['description'] ?? '') ?></textarea>
            <p class="hint">Une description détaillée rassure les acheteurs et accélère la vente</p>
        </div>

        <!-- SECTION 5 : Photos -->
        <div class="section">
            <h2>Photos</h2>
            <div class="zone-upload" id="zoneUpload">
                <input type="file" name="photos[]" multiple
                       accept="image/jpeg,image/png,image/webp"
                       onchange="previewPhotos(this)"
                       id="inputPhotos">
                <div class="icone">📷</div>
                <p><strong>Cliquez ou glissez vos photos ici</strong></p>
                <p style="margin-top:4px; font-size:0.82rem;">JPG, PNG, WEBP · 5 Mo max par photo · 8 photos max</p>
                <p style="color:var(--rouge); font-size:0.78rem; margin-top:6px;">
                    La première photo sera la photo principale
                </p>
            </div>
            <div class="apercu-photos" id="apercuPhotos"></div>
        </div>

        <!-- Boutons -->
        <div style="display:flex; gap:12px; margin-top:4px;">
            <button type="submit" style="padding:12px 28px; font-size:0.9rem;">
                ✓ Publier l'annonce
            </button>
            <button type="button" class="boutton-ghost"
                    onclick="location.href='page_compte.php'">
                Annuler
            </button>
        </div>

    </form>
</div>

<script>
// Aperçu des photos avant upload
function previewPhotos(input) {
    const container = document.getElementById('apercuPhotos');
    container.innerHTML = '';
    const files = Array.from(input.files).slice(0, 8); // max 8 photos
    files.forEach((file, i) => {
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.createElement('img');
            img.src   = e.target.result;
            img.title = i === 0 ? 'Photo principale' : 'Photo ' + (i + 1);
            container.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
    if (files.length > 0) {
        document.getElementById('zoneUpload').style.borderColor = 'var(--rouge)';
    }
}

// Drag & drop visuel
const zone = document.getElementById('zoneUpload');
zone.addEventListener('dragover',  e => { e.preventDefault(); zone.classList.add('survol'); });
zone.addEventListener('dragleave', () => zone.classList.remove('survol'));
zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('survol');
    const input = document.getElementById('inputPhotos');
    input.files = e.dataTransfer.files;
    previewPhotos(input);
});
</script>
</body>
</html>