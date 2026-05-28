<?php
//Page de compte

require_once 'fonctions.php';
require_once 'connexion_db.php';

exiger_connexion();

$user_id = $_SESSION['user_id'];

// Récupère les infos complètes de l'utilisateur
$stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Récupère ses annonces avec la photo principale
$stmt = $pdo->prepare("
    SELECT a.*, i.chemin AS photo_principale
    FROM annonces a
    LEFT JOIN images i ON i.id_annonce = a.id AND i.est_principale = 1
    WHERE a.id_utilisateur = ? AND a.statut != 'supprimée'
    ORDER BY a.date_publication DESC
");
$stmt->execute([$user_id]);
$annonces = $stmt->fetchAll();

// Compte par statut
$nb_actives = count(array_filter($annonces, fn($a) => $a['statut'] === 'active'));
$nb_pause   = count(array_filter($annonces, fn($a) => $a['statut'] === 'pause'));
$nb_vendues = count(array_filter($annonces, fn($a) => $a['statut'] === 'vendue'));

// Récupère les messages reçus non lus
$stmt = $pdo->prepare("
    SELECT COUNT(*) as nb FROM messages WHERE id_destinataire = ? AND lu = 0
");
$stmt->execute([$user_id]);
$nb_messages = $stmt->fetch()['nb'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon compte — AUTOMARKET</title>
    <link rel="stylesheet" href="CSS.css">
    <style>
        .page-compte {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Carte profil */
        .carte-profil {
            background: linear-gradient(135deg, var(--noir-3), var(--noir-4));
            border: 1px solid var(--gris-fonce);
            border-top: 3px solid var(--rouge);
            border-radius: var(--radius);
            padding: 28px;
            display: flex;
            align-items: center;
            gap: 24px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .profil-photo {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--rouge);
            flex-shrink: 0;
        }
        .profil-infos { flex: 1; min-width: 200px; }
        .profil-nom {
            font-family: var(--font-titre);
            font-size: 2rem;
            letter-spacing: 2px;
            margin-bottom: 4px;
        }
        .profil-email {
            color: var(--gris-clair);
            font-size: 0.88rem;
            font-family: var(--font-label);
            letter-spacing: 0.5px;
        }
        .profil-meta {
            display: flex;
            gap: 16px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .profil-meta span {
            color: var(--gris-moyen);
            font-size: 0.82rem;
            font-family: var(--font-label);
            letter-spacing: 0.5px;
        }
        .profil-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex-shrink: 0;
        }

        /* Stats */
        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }
        .stat-carte {
            background-color: var(--noir-3);
            border: 1px solid var(--gris-fonce);
            border-radius: var(--radius);
            padding: 16px;
            text-align: center;
        }
        .stat-nb {
            font-family: var(--font-titre);
            font-size: 2.4rem;
            color: var(--rouge-vif);
            line-height: 1;
        }
        .stat-label {
            font-family: var(--font-label);
            font-size: 0.72rem;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--gris-moyen);
            margin-top: 4px;
        }

        /* Filtres onglets */
        .onglets {
            display: flex;
            gap: 4px;
            margin-bottom: 14px;
            border-bottom: 1px solid var(--gris-fonce);
            padding-bottom: 0;
        }
        .onglet {
            font-family: var(--font-label);
            font-size: 0.8rem;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 8px 16px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            color: var(--gris-moyen);
            transition: color var(--transition), border-color var(--transition);
            background: none;
            border-top: none;
            border-left: none;
            border-right: none;
            border-radius: 0;
            transform: none;
            box-shadow: none;
        }
        .onglet:hover { color: var(--blanc); border-bottom-color: var(--gris-fonce); }
        .onglet.actif { color: var(--rouge-vif); border-bottom-color: var(--rouge); }

        /* Liste annonces */
        .ligne-annonce {
            background-color: var(--noir-3);
            border: 1px solid var(--gris-fonce);
            border-left: 3px solid var(--rouge);
            border-radius: var(--radius);
            padding: 14px 18px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: transform var(--transition), box-shadow var(--transition);
        }
        .ligne-annonce:hover {
            transform: translateX(3px);
            box-shadow: -2px 0 10px var(--rouge-glow);
        }
        .ligne-annonce.pause  { border-left-color: #ff9800; }
        .ligne-annonce.vendue { border-left-color: var(--gris-moyen); opacity: 0.7; }

        .annonce-vignette {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            background-color: var(--noir-4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            flex-shrink: 0;
            color: var(--gris-fonce);
        }
        .annonce-vignette img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 4px;
        }

        .annonce-infos { flex: 1; min-width: 0; }
        .annonce-titre {
            font-family: var(--font-titre);
            font-size: 1.2rem;
            letter-spacing: 1px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .annonce-meta {
            font-size: 0.8rem;
            color: var(--gris-clair);
            font-family: var(--font-label);
            letter-spacing: 0.5px;
            margin-top: 2px;
        }

        .annonce-prix {
            font-family: var(--font-titre);
            font-size: 1.4rem;
            color: var(--rouge-vif);
            flex-shrink: 0;
        }
        .annonce-actions {
            display: flex;
            gap: 6px;
            flex-shrink: 0;
        }
        .annonce-actions button {
            padding: 6px 12px;
            font-size: 0.75rem;
        }

        .badge {
            display: inline-block;
            font-family: var(--font-label);
            font-size: 0.68rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 2px 8px;
            border-radius: 3px;
            margin-left: 8px;
        }
        .badge-active { background:rgba(76,175,80,0.15); color:#4caf50; border:1px solid #4caf50; }
        .badge-pause  { background:rgba(255,152,0,0.15);  color:#ff9800; border:1px solid #ff9800; }
        .badge-vendue { background:rgba(150,150,150,0.15); color:#999;   border:1px solid #555; }

        @media (max-width: 600px) {
            .stats { grid-template-columns: repeat(2,1fr); }
            .ligne-annonce { flex-wrap: wrap; }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<div class="bandeau_acceuil">
    <h1>AUTOMARKET</h1>
    <div style="display:flex; gap:10px; align-items:center;">
        <button class="boutton" onclick="location.href='Page_recherche.php'">Recherche</button>
        <button class="boutton boutton-ghost" onclick="location.href='deconnexion.php'">Déconnexion</button>
    </div>
</div>

<?= afficher_flash() ?>

<div class="page-compte">

    <!-- Carte profil -->
    <div class="carte-profil">
        <img src="<?= e($user['photo_profil']) ?>" alt="Photo de profil" class="profil-photo">
        <div class="profil-infos">
            <div class="profil-nom"><?= e($user['prenom']) ?> <?= e($user['nom']) ?></div>
            <div class="profil-email"><?= e($user['email']) ?></div>
            <div class="profil-meta">
                <?php if ($user['ville']): ?>
                    <span>📍 <?= e($user['ville']) ?></span>
                <?php endif; ?>
                <?php if ($user['telephone']): ?>
                    <span>📞 <?= e($user['telephone']) ?></span>
                <?php endif; ?>
                <span>📅 Membre depuis <?= date('F Y', strtotime($user['date_inscription'])) ?></span>
            </div>
        </div>
        <div class="profil-actions">
            <button onclick="location.href='page_profil.php'">✏ Modifier le profil</button>
            <button class="boutton-ghost" onclick="location.href='nouvelle_annonce.php'">+ Nouvelle annonce</button>
            <?php if ($nb_messages > 0): ?>
                <button onclick="location.href='messages.php'"
                        style="background-color:var(--rouge-sombre);">
                    ✉ Messages <span style="background:var(--rouge); border-radius:10px;
                    padding:1px 7px; font-size:0.75rem;"><?= $nb_messages ?></span>
                </button>
            <?php else: ?>
                <button class="boutton-ghost" onclick="location.href='messages.php'">✉ Messages</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats">
        <div class="stat-carte">
            <div class="stat-nb"><?= count($annonces) ?></div>
            <div class="stat-label">Total annonces</div>
        </div>
        <div class="stat-carte">
            <div class="stat-nb" style="color:#4caf50;"><?= $nb_actives ?></div>
            <div class="stat-label">En ligne</div>
        </div>
        <div class="stat-carte">
            <div class="stat-nb" style="color:#ff9800;"><?= $nb_pause ?></div>
            <div class="stat-label">En pause</div>
        </div>
        <div class="stat-carte">
            <div class="stat-nb" style="color:var(--gris-moyen);"><?= $nb_vendues ?></div>
            <div class="stat-label">Vendues</div>
        </div>
    </div>

    <!-- Onglets -->
    <div class="onglets">
        <button class="onglet actif" onclick="filtrerAnnonces('toutes', this)">
            Toutes (<?= count($annonces) ?>)
        </button>
        <button class="onglet" onclick="filtrerAnnonces('active', this)">
            Actives (<?= $nb_actives ?>)
        </button>
        <button class="onglet" onclick="filtrerAnnonces('pause', this)">
            En pause (<?= $nb_pause ?>)
        </button>
        <button class="onglet" onclick="filtrerAnnonces('vendue', this)">
            Vendues (<?= $nb_vendues ?>)
        </button>
    </div>

    <!-- Liste des annonces -->
    <div id="liste-annonces">
        <?php if (empty($annonces)): ?>
            <div style="text-align:center; padding:50px; color:var(--gris-moyen);">
                <p style="font-size:2.5rem; margin-bottom:12px;">🚗</p>
                <p style="font-family:var(--font-titre); font-size:1.4rem; color:var(--blanc);">
                    Aucune annonce pour l'instant
                </p>
                <p style="margin:10px 0 20px; font-size:0.9rem;">
                    Publiez votre première annonce dès maintenant
                </p>
                <button onclick="location.href='nouvelle_annonce.php'">+ Déposer une annonce</button>
            </div>
        <?php else: ?>
            <?php foreach ($annonces as $a): ?>
            <div class="ligne-annonce <?= $a['statut'] ?>" data-statut="<?= $a['statut'] ?>">
                <!-- Vignette -->
                <div class="annonce-vignette">
                    <?php if ($a['photo_principale']): ?>
                        <img src="<?= e($a['photo_principale']) ?>" alt="<?= e($a['titre']) ?>">
                    <?php else: ?>
                        🚗
                    <?php endif; ?>
                </div>

                <!-- Infos -->
                <div class="annonce-infos">
                    <div class="annonce-titre">
                        <?= e($a['marque']) ?> <?= e($a['modele']) ?>
                        <span class="badge badge-<?= $a['statut'] ?>">
                            <?= ['active'=>'Active','pause'=>'Pause','vendue'=>'Vendue'][$a['statut']] ?>
                        </span>
                    </div>
                    <div class="annonce-meta">
                        <?= e($a['annee']) ?> · <?= formater_km($a['kilometrage']) ?> · <?= e($a['carburant']) ?>
                        · Publié le <?= date('d/m/Y', strtotime($a['date_publication'])) ?>
                    </div>
                </div>

                <!-- Prix -->
                <div class="annonce-prix"><?= formater_prix($a['prix']) ?></div>

                <!-- Actions -->
                <div class="annonce-actions">
                    <button onclick="location.href='page_annonce.php?id=<?= $a['id'] ?>'">Voir</button>
                    <button onclick="location.href='modifier_annonce.php?id=<?= $a['id'] ?>'">Modifier</button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<script>
function filtrerAnnonces(statut, btn) {
    // Met à jour les onglets
    document.querySelectorAll('.onglet').forEach(o => o.classList.remove('actif'));
    btn.classList.add('actif');

    // Filtre les lignes
    document.querySelectorAll('.ligne-annonce').forEach(el => {
        el.style.display = (statut === 'toutes' || el.dataset.statut === statut) ? 'flex' : 'none';
    });
}
</script>

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
