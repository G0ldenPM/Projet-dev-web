<?php
//Fiche détaillée d'une annonce

require_once 'fonctions.php';
require_once 'connexion_db.php';

// Récupère l'ID depuis l'URL, vérifie qu'il est valide
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    rediriger('Page_recherche.php', 'erreur', 'Annonce introuvable.');
}

// Récupère l'annonce + infos vendeur
$stmt = $pdo->prepare("
    SELECT a.*, 
           u.nom AS vendeur_nom, u.prenom AS vendeur_prenom,
           u.email AS vendeur_email, u.telephone AS vendeur_telephone,
           u.ville AS vendeur_ville, u.photo_profil AS vendeur_photo
    FROM annonces a
    JOIN utilisateurs u ON u.id = a.id_utilisateur
    WHERE a.id = ? AND a.statut != 'supprimée'
");
$stmt->execute([$id]);
$a = $stmt->fetch();

if (!$a) {
    rediriger('Page_recherche.php', 'erreur', 'Cette annonce n\'existe plus.');
}

// Récupère toutes les photos de l'annonce
$photos = $pdo->prepare("SELECT * FROM images WHERE id_annonce = ? ORDER BY est_principale DESC, ordre ASC");
$photos->execute([$id]);
$photos = $photos->fetchAll();

// Traitement du formulaire de contact
$msg_contact = '';
$err_contact = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'contact') {
    if (!est_connecte()) {
        $err_contact = 'Vous devez être connecté pour contacter un vendeur.';
    } else {
        $contenu = trim($_POST['contenu'] ?? '');
        if (empty($contenu)) {
            $err_contact = 'Le message ne peut pas être vide.';
        } elseif (strlen($contenu) < 10) {
            $err_contact = 'Le message est trop court (10 caractères minimum).';
        } else {
            $user = utilisateur_courant();
            if ($user['id'] == $a['id_utilisateur']) {
                $err_contact = 'Vous ne pouvez pas vous contacter vous-même.';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO messages (id_annonce, id_expediteur, id_destinataire, contenu)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$id, $user['id'], $a['id_utilisateur'], $contenu]);
                $msg_contact = 'Votre message a bien été envoyé !';
            }
        }
    }
}

$user = utilisateur_courant();
$est_proprietaire = est_connecte() && $user['id'] == $a['id_utilisateur'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= e($a['marque']) ?> <?= e($a['modele']) ?> — AUTOMARKET</title>
    <link rel="stylesheet" href="CSS.css">
    <style>
        .page-annonce {
            max-width: 1100px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Galerie photos */
        .galerie {
            position: relative;
            background-color: var(--noir-4);
            border-radius: var(--radius);
            overflow: hidden;
            margin-bottom: 20px;
            border: 1px solid var(--gris-fonce);
        }
        .galerie-principale {
            width: 100%;
            height: 400px;
            object-fit: cover;
            display: block;
        }
        .galerie-placeholder {
            width: 100%;
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 6rem;
            color: var(--gris-fonce);
        }
        .galerie-vignettes {
            display: flex;
            gap: 8px;
            padding: 10px;
            background-color: var(--noir-3);
            overflow-x: auto;
        }
        .vignette {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: border-color var(--transition);
            flex-shrink: 0;
        }
        .vignette.active, .vignette:hover { border-color: var(--rouge); }

        /* Layout principal */
        .layout {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 20px;
        }

        /* Bloc caractéristiques */
        .bloc {
            background-color: var(--noir-3);
            border: 1px solid var(--gris-fonce);
            border-radius: var(--radius);
            padding: 22px 26px;
            margin-bottom: 16px;
        }
        .bloc h2 {
            font-size: 1.4rem;
            color: var(--rouge);
            letter-spacing: 2px;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--gris-fonce);
        }

        .grille-carac {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }
        .carac-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--noir-4);
            gap: 12px;
        }
        .carac-item:nth-child(odd)  { padding-right: 20px; border-right: 1px solid var(--noir-4); }
        .carac-item:nth-child(even) { padding-left: 20px; }
        .carac-label {
            font-family: var(--font-label);
            font-size: 0.75rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--gris-moyen);
        }
        .carac-valeur {
            font-weight: 500;
            color: var(--blanc);
            text-align: right;
        }

        /* Colonne droite */
        .colonne-droite { display: flex; flex-direction: column; gap: 16px; }

        /* Bloc prix */
        .bloc-prix {
            background: linear-gradient(135deg, var(--noir-3), var(--noir-4));
            border: 1px solid var(--gris-fonce);
            border-top: 3px solid var(--rouge);
            border-radius: var(--radius);
            padding: 22px;
            text-align: center;
        }
        .prix-montant {
            font-family: var(--font-titre);
            font-size: 3rem;
            color: var(--rouge-vif);
            letter-spacing: 2px;
            line-height: 1;
        }
        .prix-sous {
            color: var(--gris-clair);
            font-size: 0.82rem;
            font-family: var(--font-label);
            letter-spacing: 1px;
            margin-top: 4px;
        }

        /* Bloc vendeur */
        .vendeur-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 14px;
        }
        .vendeur-photo {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--rouge);
        }
        .vendeur-nom {
            font-family: var(--font-titre);
            font-size: 1.2rem;
            letter-spacing: 1px;
        }
        .vendeur-ville {
            font-size: 0.82rem;
            color: var(--gris-clair);
            font-family: var(--font-label);
        }

        /* Formulaire contact */
        .form-contact textarea {
            width: 100%;
            background-color: var(--noir-4);
            border: 1px solid var(--gris-fonce);
            border-radius: var(--radius);
            color: var(--blanc);
            font-family: var(--font-corps);
            font-size: 0.9rem;
            padding: 10px 14px;
            resize: vertical;
            min-height: 100px;
            outline: none;
            transition: border-color var(--transition);
            box-sizing: border-box;
        }
        .form-contact textarea:focus { border-color: var(--rouge); }

        /* Badge statut */
        .badge {
            display: inline-block;
            font-family: var(--font-label);
            font-size: 0.72rem;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 3px 10px;
            border-radius: 3px;
            margin-left: 10px;
            vertical-align: middle;
        }
        .badge-active  { background: rgba(76,175,80,0.15); color:#4caf50; border:1px solid #4caf50; }
        .badge-pause   { background: rgba(255,152,0,0.15);  color:#ff9800; border:1px solid #ff9800; }
        .badge-vendue  { background: rgba(204,17,17,0.15);  color:var(--rouge); border:1px solid var(--rouge); }

        @media (max-width: 768px) {
            .layout { grid-template-columns: 1fr; }
            .grille-carac { grid-template-columns: 1fr; }
            .carac-item:nth-child(odd) { border-right: none; padding-right: 0; }
            .carac-item:nth-child(even) { padding-left: 0; }
        }

        #voiceflow-chat {
            z-index: 999999 !important;
        }
    </style>
</head>

<body>

<!-- HEADER -->
<div class="bandeau_acceuil">
    <h1>AUTOMARKET</h1>
    <div style="display:flex; gap:10px; align-items:center;">
        <?php if (est_connecte()): ?>
            <span style="color:var(--gris-clair); font-family:var(--font-label); font-size:0.8rem; letter-spacing:1px;">
                <?= e($user['prenom']) ?> <?= e($user['nom']) ?>
            </span>
            <button class="boutton" onclick="location.href='page_compte.php'">Mon compte</button>
            <button class="boutton boutton-ghost" onclick="location.href='deconnexion.php'">Déconnexion</button>
        <?php else: ?>
            <button class="boutton" onclick="location.href='connection.php'">Connexion</button>
            <button class="boutton boutton-ghost" onclick="location.href='inscription.php'">S'inscrire</button>
        <?php endif; ?>
    </div>
</div>

<div class="page-annonce">

    <!-- Fil d'ariane -->
    <p style="font-family:var(--font-label); font-size:0.78rem; color:var(--gris-moyen);
               letter-spacing:1px; margin-bottom:16px;">
        <a href="Page_recherche.php" style="color:var(--gris-moyen);">Annonces</a>
        &rsaquo; <?= e($a['marque']) ?> <?= e($a['modele']) ?>
    </p>

    <!-- Titre + badge -->
    <h1 style="font-size:2.2rem; margin-bottom:4px;">
        <?= e($a['marque']) ?> <?= e($a['modele']) ?>
        <?php
            $badges = ['active'=>'badge-active','pause'=>'badge-pause','vendue'=>'badge-vendue'];
            $labels = ['active'=>'Active','pause'=>'En pause','vendue'=>'Vendue'];
        ?>
        <span class="badge <?= $badges[$a['statut']] ?? '' ?>"><?= $labels[$a['statut']] ?? '' ?></span>
    </h1>
    <p style="color:var(--gris-clair); font-family:var(--font-label); letter-spacing:1px;
               font-size:0.85rem; margin-bottom:20px;">
        <?= e($a['annee']) ?>
        <?php if ($a['motorisation']): ?> · <?= e($a['motorisation']) ?><?php endif; ?>
        · Ref. #<?= $a['id'] ?>
    </p>

    <!-- Galerie -->
    <div class="galerie">
        <?php if (!empty($photos)): ?>
            <img id="photo-principale" class="galerie-principale"
                 src="<?= e($photos[0]['chemin']) ?>" alt="<?= e($a['titre']) ?>">
            <?php if (count($photos) > 1): ?>
            <div class="galerie-vignettes">
                <?php foreach ($photos as $i => $p): ?>
                    <img src="<?= e($p['chemin']) ?>"
                         class="vignette <?= $i === 0 ? 'active' : '' ?>"
                         onclick="changerPhoto(this)"
                         alt="Photo <?= $i+1 ?>">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="galerie-placeholder">🚗</div>
        <?php endif; ?>
    </div>

    <!-- Layout 2 colonnes -->
    <div class="layout">

        <!-- Colonne gauche -->
        <div>
            <!-- Caractéristiques -->
            <div class="bloc">
                <h2>Caractéristiques</h2>
                <div class="grille-carac">
                    <div class="carac-item">
                        <span class="carac-label">Kilométrage</span>
                        <span class="carac-valeur"><?= formater_km($a['kilometrage']) ?></span>
                    </div>
                    <div class="carac-item">
                        <span class="carac-label">Année</span>
                        <span class="carac-valeur"><?= e($a['annee']) ?></span>
                    </div>
                    <div class="carac-item">
                        <span class="carac-label">Carburant</span>
                        <span class="carac-valeur"><?= e($a['carburant']) ?></span>
                    </div>
                    <div class="carac-item">
                        <span class="carac-label">Boîte</span>
                        <span class="carac-valeur"><?= e($a['boite']) ?></span>
                    </div>
                    <div class="carac-item">
                        <span class="carac-label">Places</span>
                        <span class="carac-valeur"><?= e($a['nb_places']) ?></span>
                    </div>
                    <div class="carac-item">
                        <span class="carac-label">Portes</span>
                        <span class="carac-valeur"><?= e($a['nb_portes']) ?></span>
                    </div>
                    <?php if ($a['critair'] !== null): ?>
                    <div class="carac-item">
                        <span class="carac-label">Crit'Air</span>
                        <span class="carac-valeur"><?= e($a['critair']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($a['couleur']): ?>
                    <div class="carac-item">
                        <span class="carac-label">Couleur</span>
                        <span class="carac-valeur"><?= e($a['couleur']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($a['longueur']): ?>
                    <div class="carac-item">
                        <span class="carac-label">Longueur</span>
                        <span class="carac-valeur"><?= e($a['longueur']) ?> mm</span>
                    </div>
                    <div class="carac-item">
                        <span class="carac-label">Largeur</span>
                        <span class="carac-valeur"><?= e($a['largeur']) ?> mm</span>
                    </div>
                    <div class="carac-item">
                        <span class="carac-label">Hauteur</span>
                        <span class="carac-valeur"><?= e($a['hauteur']) ?> mm</span>
                    </div>
                    <div class="carac-item">
                        <span class="carac-label">Poids</span>
                        <span class="carac-valeur"><?= e($a['poids']) ?> kg</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Description -->
            <?php if ($a['description']): ?>
            <div class="bloc">
                <h2>Description</h2>
                <p style="color:var(--gris-clair); line-height:1.7; font-size:0.92rem;">
                    <?= nl2br(e($a['description'])) ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Boutons propriétaire -->
            <?php if ($est_proprietaire): ?>
            <div class="bloc" style="border-color:var(--rouge-sombre);">
                <h2>Gérer mon annonce</h2>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <button onclick="location.href='modifier_annonce.php?id=<?= $a['id'] ?>'">
                        ✏ Modifier
                    </button>
                    <?php if ($a['statut'] === 'active'): ?>
                        <button class="boutton-ghost"
                                onclick="changerStatut(<?= $a['id'] ?>, 'pause')">
                            ⏸ Mettre en pause
                        </button>
                    <?php elseif ($a['statut'] === 'pause'): ?>
                        <button class="boutton-ghost"
                                onclick="changerStatut(<?= $a['id'] ?>, 'active')">
                            ▶ Remettre en ligne
                        </button>
                    <?php endif; ?>
                    <button class="boutton-ghost" style="border-color:var(--rouge);"
                            onclick="changerStatut(<?= $a['id'] ?>, 'vendue')">
                        ✓ Marquer comme vendue
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Colonne droite -->
        <div class="colonne-droite">

            <!-- Prix -->
            <div class="bloc-prix">
                <div class="prix-montant"><?= formater_prix($a['prix']) ?></div>
                <div class="prix-sous">Prix vendeur</div>
            </div>

            <!-- Vendeur -->
            <div class="bloc">
                <h2>Vendeur</h2>
                <div class="vendeur-header">
                    <img src="<?= e($a['vendeur_photo']) ?>" alt="Photo vendeur" class="vendeur-photo">
                    <div>
                        <div class="vendeur-nom"><?= e($a['vendeur_prenom']) ?> <?= e($a['vendeur_nom']) ?></div>
                        <?php if ($a['vendeur_ville']): ?>
                            <div class="vendeur-ville">📍 <?= e($a['vendeur_ville']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($est_proprietaire): ?>
                    <p style="color:var(--gris-moyen); font-size:0.85rem; font-family:var(--font-label);
                               text-align:center; letter-spacing:1px;">C'est votre annonce</p>

                <?php elseif (!est_connecte()): ?>
                    <p style="color:var(--gris-clair); font-size:0.85rem; margin-bottom:12px; text-align:center;">
                        Connectez-vous pour contacter le vendeur
                    </p>
                    <button onclick="location.href='connection.php'" style="width:100%;">
                        Se connecter
                    </button>

                <?php else: ?>
                    <!-- Formulaire de contact -->
                    <?php if ($msg_contact): ?>
                        <p style="color:#4caf50; font-size:0.88rem; margin-bottom:10px;
                                   border-left:3px solid #4caf50; padding-left:10px;">
                            ✓ <?= e($msg_contact) ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($err_contact): ?>
                        <p style="color:var(--rouge); font-size:0.88rem; margin-bottom:10px;
                                   border-left:3px solid var(--rouge); padding-left:10px;">
                            <?= e($err_contact) ?>
                        </p>
                    <?php endif; ?>

                    <form method="POST" class="form-contact" style="display:flex; flex-direction:column; gap:10px;">
                        <input type="hidden" name="action" value="contact">
                        <label>Votre message</label>
                        <textarea name="contenu" placeholder="Bonjour, je suis intéressé par votre annonce..."></textarea>
                        <button type="submit" style="width:100%;">Envoyer le message</button>
                    </form>

                    <?php if ($a['vendeur_telephone']): ?>
                        <p style="text-align:center; margin-top:12px; color:var(--gris-clair);
                                   font-family:var(--font-label); font-size:0.82rem; letter-spacing:1px;">
                            📞 <?= e($a['vendeur_telephone']) ?>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <a href="Page_recherche.php" class="retour-lien">Retour aux annonces</a>
</div>

<script>
// Galerie photos
function changerPhoto(el) {
    document.getElementById('photo-principale').src = el.src;
    document.querySelectorAll('.vignette').forEach(v => v.classList.remove('active'));
    el.classList.add('active');
}

// Changement de statut (propriétaire)
function changerStatut(id, statut) {
    const labels = {
        pause: 'mettre en pause',
        active: 'remettre en ligne',
        vendue: 'marquer comme vendue'
    };

    if (!confirm('Voulez-vous ' + labels[statut] + ' cette annonce ?')) return;

    fetch('api_annonce.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'statut', id, statut })
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) location.reload();
        else alert(d.erreur);
    });
}
</script>

<!-- Chatbot Voiceflow -->
<script type="text/javascript">
  (function(d, t) {
      var v = d.createElement(t), s = d.getElementsByTagName(t)[0];
      v.onload = function() {
        window.voiceflow.chat.load({
          verify: { projectID: '69e222e791d94940ea88a5e2' },
          url: 'https://general-runtime.voiceflow.com',
          versionID: 'production',
          voice: {
            url: "https://runtime-api.voiceflow.com"
          }
        });
      }
      v.src = "https://cdn.voiceflow.com/widget-next/bundle.mjs"; v.type = "text/javascript"; s.parentNode.insertBefore(v, s);
  })(document, 'script');
</script>

</body>
</html>