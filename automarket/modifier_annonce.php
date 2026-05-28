<?php
// Modifier / Supprimer annonce

require_once 'fonctions.php';
require_once 'connexion_db.php';

exiger_connexion();

$user_id = $_SESSION['user_id'];

// Récupère l'ID de l'annonce depuis l'URL
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    rediriger('page_compte.php', 'erreur', 'Annonce introuvable.');
}

// Récupère l'annonce et vérifie que c'est bien la sienne
$stmt = $pdo->prepare("SELECT * FROM annonces WHERE id = ? AND statut != 'supprimée'");
$stmt->execute([$id]);
$annonce = $stmt->fetch();

if (!$annonce || $annonce['id_utilisateur'] != $user_id) {
    rediriger('page_compte.php', 'erreur', 'Vous n\'êtes pas autorisé à modifier cette annonce.');
}

// Récupère les photos existantes
$stmt = $pdo->prepare("SELECT * FROM images WHERE id_annonce = ? ORDER BY est_principale DESC, ordre ASC");
$stmt->execute([$id]);
$photos = $stmt->fetchAll();

$erreur = '';

// ACTION : Suppression définitive

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'supprimer') {
    // Supprime les fichiers photos du serveur
    foreach ($photos as $p) {
        if (file_exists($p['chemin'])) unlink($p['chemin']);
    }
    // Supprime en base (CASCADE supprime aussi les images et messages liés)
    $stmt = $pdo->prepare("UPDATE annonces SET statut = 'supprimée' WHERE id = ?");
    $stmt->execute([$id]);
    rediriger('page_compte.php', 'succes', 'Annonce supprimée.');
}

// ACTION : Suppression d'une photo

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'supprimer_photo') {
    $id_photo = (int)($_POST['id_photo'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM images WHERE id = ? AND id_annonce = ?");
    $stmt->execute([$id_photo, $id]);
    $photo = $stmt->fetch();

    if ($photo) {
        if (file_exists($photo['chemin'])) unlink($photo['chemin']);
        $pdo->prepare("DELETE FROM images WHERE id = ?")->execute([$id_photo]);

        // Si c'était la photo principale, passe la suivante en principale
        if ($photo['est_principale']) {
            $pdo->prepare("UPDATE images SET est_principale = 1 WHERE id_annonce = ? LIMIT 1")
                ->execute([$id]);
        }
        // Recharge les photos
        $stmt = $pdo->prepare("SELECT * FROM images WHERE id_annonce = ? ORDER BY est_principale DESC, ordre ASC");
        $stmt->execute([$id]);
        $photos = $stmt->fetchAll();
    }
}

// ACTION : Définir une photo comme principale

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'photo_principale') {
    $id_photo = (int)($_POST['id_photo'] ?? 0);
    $pdo->prepare("UPDATE images SET est_principale = 0 WHERE id_annonce = ?")->execute([$id]);
    $pdo->prepare("UPDATE images SET est_principale = 1 WHERE id = ? AND id_annonce = ?")->execute([$id_photo, $id]);
    header("Location: modifier_annonce.php?id={$id}");
    exit;
}

// ACTION : Upload de nouvelles photos

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ajouter_photos') {
    if (!empty($_FILES['photos']['tmp_name'][0])) {
        $dossier = "uploads/annonces/{$id}/";
        if (!is_dir($dossier)) mkdir($dossier, 0755, true);

        $ext_autorisees = ['jpg','jpeg','png','webp'];
        $nb_photos      = count($photos);

        foreach ($_FILES['photos']['tmp_name'] as $i => $tmp) {
            if (empty($tmp) || $_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) continue;
            if ($_FILES['photos']['size'][$i] > 5 * 1024 * 1024) continue;
            $ext = strtolower(pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION));
            if (!in_array($ext, $ext_autorisees)) continue;

            $nom_fichier = "photo_" . time() . "_{$i}." . $ext;
            $chemin      = $dossier . $nom_fichier;

            if (move_uploaded_file($tmp, $chemin)) {
                $est_principale = ($nb_photos === 0 && $i === 0);
                $stmt = $pdo->prepare("INSERT INTO images (id_annonce, chemin, est_principale, ordre) VALUES (?,?,?,?)");
                $stmt->execute([$id, $chemin, $est_principale, $nb_photos + $i]);
            }
        }
        header("Location: modifier_annonce.php?id={$id}");
        exit;
    }
}

// ACTION : Sauvegarde des modifications

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'modifier') {

    $donnees = [
        'titre'        => trim($_POST['titre']        ?? ''),
        'marque'       => trim($_POST['marque']       ?? ''),
        'modele'       => trim($_POST['modele']       ?? ''),
        'annee'        => (int)($_POST['annee']       ?? 0),
        'prix'         => (float)($_POST['prix']      ?? 0),
        'carburant'    => $_POST['carburant']         ?? '',
        'boite'        => $_POST['boite']             ?? '',
        'motorisation' => trim($_POST['motorisation'] ?? ''),
        'kilometrage'  => (int)($_POST['kilometrage'] ?? 0),
        'nb_places'    => (int)($_POST['nb_places']   ?? 5),
        'nb_portes'    => (int)($_POST['nb_portes']   ?? 5),
        'critair'      => $_POST['critair'] !== '' ? (int)$_POST['critair'] : null,
        'couleur'      => trim($_POST['couleur']      ?? ''),
        'longueur'     => $_POST['longueur'] !== '' ? (int)$_POST['longueur'] : null,
        'largeur'      => $_POST['largeur']  !== '' ? (int)$_POST['largeur']  : null,
        'hauteur'      => $_POST['hauteur']  !== '' ? (int)$_POST['hauteur']  : null,
        'poids'        => $_POST['poids']    !== '' ? (int)$_POST['poids']    : null,
        'description'  => trim($_POST['description']  ?? ''),
        'statut'       => $_POST['statut']            ?? 'active',
    ];

    $carburants_valides = ['Essence','Diesel','Électrique','Hybride'];
    $boites_valides     = ['Manuelle','Automatique'];
    $statuts_valides    = ['active','pause','vendue'];

    if (empty($donnees['titre']) || empty($donnees['marque']) || empty($donnees['modele'])) {
        $erreur = 'Titre, marque et modèle sont obligatoires.';
    } elseif ($donnees['prix'] <= 0) {
        $erreur = 'Le prix doit être supérieur à 0.';
    } elseif (!in_array($donnees['carburant'], $carburants_valides)) {
        $erreur = 'Carburant invalide.';
    } elseif (!in_array($donnees['boite'], $boites_valides)) {
        $erreur = 'Boîte invalide.';
    } elseif (!in_array($donnees['statut'], $statuts_valides)) {
        $erreur = 'Statut invalide.';
    } else {
        $stmt = $pdo->prepare('
            UPDATE annonces SET
                titre=?, marque=?, modele=?, annee=?, prix=?,
                carburant=?, boite=?, motorisation=?, kilometrage=?,
                nb_places=?, nb_portes=?, critair=?, couleur=?,
                longueur=?, largeur=?, hauteur=?, poids=?,
                description=?, statut=?
            WHERE id=?
        ');
        $stmt->execute([
            $donnees['titre'],    $donnees['marque'],  $donnees['modele'],
            $donnees['annee'],    $donnees['prix'],     $donnees['carburant'],
            $donnees['boite'],    $donnees['motorisation'] ?: null,
            $donnees['kilometrage'], $donnees['nb_places'], $donnees['nb_portes'],
            $donnees['critair'],  $donnees['couleur']  ?: null,
            $donnees['longueur'], $donnees['largeur'],  $donnees['hauteur'],
            $donnees['poids'],    $donnees['description'] ?: null,
            $donnees['statut'],   $id
        ]);

        // Met à jour $annonce pour ré-affichage correct
        $annonce = array_merge($annonce, $donnees);
        rediriger("page_annonce.php?id={$id}", 'succes', 'Annonce mise à jour avec succès !');
    }
}

$annee_actuelle = (int)date('Y');
$titre_page = 'Modifier l\'annonce';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier l'annonce — AUTOMARKET</title>
    <link rel="stylesheet" href="CSS.css">
    <style>
        .page { max-width: 860px; margin: 0 auto; padding: 20px; }

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

        /* Grille photos */
        .grille-photos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }
        .photo-item {
            position: relative;
            border-radius: var(--radius);
            overflow: hidden;
            border: 2px solid var(--gris-fonce);
            aspect-ratio: 4/3;
            background-color: var(--noir-4);
        }
        .photo-item.principale { border-color: var(--rouge); }
        .photo-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .photo-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.6);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            opacity: 0;
            transition: opacity var(--transition);
        }
        .photo-item:hover .photo-overlay { opacity: 1; }
        .photo-overlay button {
            padding: 5px 10px;
            font-size: 0.72rem;
            width: 90%;
        }
        .badge-principale {
            position: absolute;
            top: 6px; left: 6px;
            background: var(--rouge);
            color: white;
            font-family: var(--font-label);
            font-size: 0.65rem;
            letter-spacing: 1px;
            padding: 2px 7px;
            border-radius: 3px;
            text-transform: uppercase;
        }

        /* Zone upload */
        .zone-upload {
            border: 2px dashed var(--gris-fonce);
            border-radius: var(--radius);
            padding: 22px;
            text-align: center;
            cursor: pointer;
            transition: border-color var(--transition), background-color var(--transition);
            position: relative;
        }
        .zone-upload:hover { border-color: var(--rouge); background-color: rgba(204,17,17,0.05); }
        .zone-upload input[type="file"] {
            position: absolute; inset: 0;
            opacity: 0; cursor: pointer;
            width: 100%; height: 100%;
        }
        .zone-upload p { color: var(--gris-clair); font-size:0.88rem; pointer-events:none; }

        /* Bloc danger */
        .section-danger {
            border-color: var(--rouge-sombre);
            background-color: rgba(139,0,0,0.08);
        }
        .section-danger h2 { color: var(--rouge-vif); }

        .erreur-msg {
            color: var(--rouge);
            font-size: 0.88rem;
            border-left: 3px solid var(--rouge);
            padding: 10px 14px;
            background: rgba(204,17,17,0.08);
            border-radius: 0 var(--radius) var(--radius) 0;
            margin-bottom: 16px;
        }

        /* Modal confirmation suppression */
        .modal-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.75);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.visible { display: flex; }
        .modal {
            background-color: var(--noir-3);
            border: 1px solid var(--rouge);
            border-top: 3px solid var(--rouge);
            border-radius: var(--radius);
            padding: 32px 36px;
            max-width: 420px;
            width: 90%;
            text-align: center;
        }
        .modal h3 {
            font-size: 1.6rem;
            letter-spacing: 2px;
            margin-bottom: 12px;
            color: var(--rouge-vif);
        }
        .modal p { color: var(--gris-clair); font-size: 0.9rem; margin-bottom: 24px; line-height: 1.6; }
        .modal-actions { display: flex; gap: 12px; justify-content: center; }

        @media (max-width: 600px) {
            .grille { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<div class="bandeau_acceuil">
    <h1 onclick="location.href='Page_recherche.php'" style="cursor:pointer;">AUTOMARKET</h1>
    <div style="display:flex; gap:10px;">
        <button class="boutton" onclick="location.href='page_compte.php'">Mon compte</button>
        <button class="boutton boutton-ghost" onclick="location.href='deconnexion.php'">Déconnexion</button>
    </div>
</div>

<?= afficher_flash() ?>

<div class="page">

    <!-- Fil d'ariane -->
    <p style="font-family:var(--font-label); font-size:0.78rem; color:var(--gris-moyen);
               letter-spacing:1px; margin-bottom:16px;">
        <a href="page_compte.php"   style="color:var(--gris-moyen);">Mon compte</a> ›
        <a href="page_annonce.php?id=<?= $id ?>" style="color:var(--gris-moyen);">
            <?= e($annonce['marque']) ?> <?= e($annonce['modele']) ?>
        </a> ›
        <span style="color:var(--blanc);">Modifier</span>
    </p>

    <h1 style="font-size:2rem; margin-bottom:6px; letter-spacing:2px;">Modifier l'annonce</h1>
    <p style="color:var(--gris-clair); font-size:0.88rem; font-family:var(--font-label);
               letter-spacing:1px; margin-bottom:20px;">
        Ref. #<?= $id ?> · Publiée le <?= date('d/m/Y', strtotime($annonce['date_publication'])) ?>
    </p>

    <?php if ($erreur): ?>
        <p class="erreur-msg"><?= e($erreur) ?></p>
    <?php endif; ?>

    <!-- formulaire principal -->
    <form method="POST">
        <input type="hidden" name="action" value="modifier">

        <!-- Identification -->
        <div class="section">
            <h2>Identification du véhicule</h2>
            <div class="grille">
                <div class="pleine">
                    <label>Titre *</label>
                    <input type="text" name="titre" required
                           value="<?= e($annonce['titre']) ?>">
                </div>
                <div>
                    <label>Marque *</label>
                    <input type="text" name="marque" required value="<?= e($annonce['marque']) ?>">
                </div>
                <div>
                    <label>Modèle *</label>
                    <input type="text" name="modele" required value="<?= e($annonce['modele']) ?>">
                </div>
                <div>
                    <label>Année *</label>
                    <input type="number" name="annee" required
                           min="1900" max="<?= $annee_actuelle + 1 ?>"
                           value="<?= e($annonce['annee']) ?>">
                </div>
                <div>
                    <label>Motorisation</label>
                    <input type="text" name="motorisation" value="<?= e($annonce['motorisation'] ?? '') ?>">
                </div>
                <div>
                    <label>Couleur</label>
                    <input type="text" name="couleur" value="<?= e($annonce['couleur'] ?? '') ?>">
                </div>
                <div>
                    <label>Prix (€) *</label>
                    <input type="number" name="prix" required min="1" step="100"
                           value="<?= e($annonce['prix']) ?>">
                </div>
                <div>
                    <label>Statut</label>
                    <select name="statut">
                        <?php foreach (['active'=>'Active','pause'=>'En pause','vendue'=>'Vendue'] as $val => $label): ?>
                            <option value="<?= $val ?>" <?= $annonce['statut'] === $val ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Motorisation -->
        <div class="section">
            <h2>Motorisation & transmission</h2>
            <div class="grille">
                <div>
                    <label>Carburant *</label>
                    <select name="carburant" required>
                        <?php foreach (['Essence','Diesel','Électrique','Hybride'] as $c): ?>
                            <option value="<?= $c ?>" <?= $annonce['carburant'] === $c ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Boîte *</label>
                    <select name="boite" required>
                        <?php foreach (['Manuelle','Automatique'] as $b): ?>
                            <option value="<?= $b ?>" <?= $annonce['boite'] === $b ? 'selected' : '' ?>><?= $b ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Kilométrage *</label>
                    <input type="number" name="kilometrage" required min="0"
                           value="<?= e($annonce['kilometrage']) ?>">
                </div>
                <div>
                    <label>Crit'Air</label>
                    <select name="critair">
                        <option value="">-- Non renseigné --</option>
                        <?php for ($i = 0; $i <= 5; $i++): ?>
                            <option value="<?= $i ?>" <?= $annonce['critair'] == $i ? 'selected' : '' ?>>Crit'Air <?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Caractéristiques -->
        <div class="section">
            <h2>Caractéristiques détaillées</h2>
            <div class="grille">
                <div>
                    <label>Nombre de places</label>
                    <select name="nb_places">
                        <?php for ($i = 2; $i <= 9; $i++): ?>
                            <option value="<?= $i ?>" <?= $annonce['nb_places'] == $i ? 'selected' : '' ?>><?= $i ?> places</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label>Nombre de portes</label>
                    <select name="nb_portes">
                        <?php foreach ([2,3,4,5] as $p): ?>
                            <option value="<?= $p ?>" <?= $annonce['nb_portes'] == $p ? 'selected' : '' ?>><?= $p ?> portes</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Longueur (mm)</label>
                    <input type="number" name="longueur" min="0" value="<?= e($annonce['longueur'] ?? '') ?>">
                </div>
                <div>
                    <label>Largeur (mm)</label>
                    <input type="number" name="largeur"  min="0" value="<?= e($annonce['largeur']  ?? '') ?>">
                </div>
                <div>
                    <label>Hauteur (mm)</label>
                    <input type="number" name="hauteur"  min="0" value="<?= e($annonce['hauteur']  ?? '') ?>">
                </div>
                <div>
                    <label>Poids (kg)</label>
                    <input type="number" name="poids"    min="0" value="<?= e($annonce['poids']    ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- Description -->
        <div class="section">
            <h2>Description</h2>
            <textarea name="description"><?= e($annonce['description'] ?? '') ?></textarea>
        </div>

        <!-- Boutons sauvegarde -->
        <div style="display:flex; gap:12px; margin-bottom:20px;">
            <button type="submit" style="padding:12px 28px;">✓ Enregistrer les modifications</button>
            <button type="button" class="boutton-ghost"
                    onclick="location.href='page_annonce.php?id=<?= $id ?>'">
                Annuler
            </button>
        </div>
    </form>

    <!-- GESTION DES PHOTOS -->
    <div class="section">
        <h2>Photos de l'annonce</h2>

        <!-- Photos existantes -->
        <?php if (!empty($photos)): ?>
        <div class="grille-photos">
            <?php foreach ($photos as $p): ?>
            <div class="photo-item <?= $p['est_principale'] ? 'principale' : '' ?>">
                <img src="<?= e($p['chemin']) ?>" alt="Photo annonce">
                <?php if ($p['est_principale']): ?>
                    <span class="badge-principale">Principale</span>
                <?php endif; ?>
                <div class="photo-overlay">
                    <?php if (!$p['est_principale']): ?>
                    <form method="POST" style="width:90%;">
                        <input type="hidden" name="action"    value="photo_principale">
                        <input type="hidden" name="id_photo"  value="<?= $p['id'] ?>">
                        <button type="submit" class="boutton-ghost" style="width:100%;">
                            ★ Mettre en principale
                        </button>
                    </form>
                    <?php endif; ?>
                    <form method="POST" style="width:90%;"
                          onsubmit="return confirm('Supprimer cette photo ?')">
                        <input type="hidden" name="action"   value="supprimer_photo">
                        <input type="hidden" name="id_photo" value="<?= $p['id'] ?>">
                        <button type="submit" style="width:100%; background-color:var(--rouge-sombre);">
                            🗑 Supprimer
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <p style="color:var(--gris-moyen); font-size:0.88rem; margin-bottom:14px;">
                Aucune photo pour cette annonce.
            </p>
        <?php endif; ?>

        <!-- Ajout de photos -->
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="ajouter_photos">
            <div class="zone-upload">
                <input type="file" name="photos[]" multiple
                       accept="image/jpeg,image/png,image/webp"
                       onchange="this.closest('form').submit()">
                <p>📷 Cliquez pour ajouter des photos</p>
                <p style="font-size:0.78rem; margin-top:4px; color:var(--gris-moyen);">
                    JPG, PNG, WEBP · 5 Mo max · upload automatique
                </p>
            </div>
        </form>
    </div>

    <!-- ZONE DANGER -->
    <div class="section section-danger">
        <h2>⚠ Zone de danger</h2>
        <p style="color:var(--gris-clair); font-size:0.9rem; margin-bottom:16px; line-height:1.6;">
            La suppression est définitive. L'annonce, ses photos et tous les messages associés
            seront supprimés et ne pourront pas être récupérés.
        </p>
        <button type="button"
                style="background-color: var(--rouge-sombre); border-color: var(--rouge);"
                onclick="document.getElementById('modalSuppr').classList.add('visible')">
            🗑 Supprimer définitivement cette annonce
        </button>
    </div>

</div>

<!-- Confirmation de suppression -->
<div class="modal-overlay" id="modalSuppr">
    <div class="modal">
        <h3>SUPPRIMER ?</h3>
        <p>
            Vous êtes sur le point de supprimer définitivement<br>
            <strong style="color:var(--blanc);">
                <?= e($annonce['marque']) ?> <?= e($annonce['modele']) ?> (<?= e($annonce['annee']) ?>)
            </strong><br><br>
            Cette action est irréversible. Tous les messages liés à cette annonce seront également supprimés.
        </p>
        <div class="modal-actions">
            <form method="POST">
                <input type="hidden" name="action" value="supprimer">
                <button type="submit" style="background-color:var(--rouge-sombre); border-color:var(--rouge);">
                    Oui, supprimer
                </button>
            </form>
            <button type="button" class="boutton-ghost"
                    onclick="document.getElementById('modalSuppr').classList.remove('visible')">
                Annuler
            </button>
        </div>
    </div>
</div>

<script>
// Fermer le modal en cliquant en dehors
document.getElementById('modalSuppr').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('visible');
});
</script>
</body>
</html>