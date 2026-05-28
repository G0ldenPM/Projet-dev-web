<?php
//Page de recherche

require_once 'fonctions.php';
require_once 'connexion_db.php';

// --- Construction de la requête avec filtres ---
$conditions = ["a.statut = 'active'"];
$params     = [];

// Filtre : marque
if (!empty($_GET['marque'])) {
    $conditions[] = 'a.marque = ?';
    $params[]     = $_GET['marque'];
}

// Filtre : kilométrage
if (!empty($_GET['kilometrage'])) {
    switch ($_GET['kilometrage']) {
        case 'moins50':
            $conditions[] = 'a.kilometrage < 50000';  break;
        case '50-100':
            $conditions[] = 'a.kilometrage BETWEEN 50000 AND 100000'; break;
        case 'plus100':
            $conditions[] = 'a.kilometrage > 100000'; break;
    }
}

// Filtre : boîte de vitesse
if (!empty($_GET['boite'])) {
    $conditions[] = 'a.boite = ?';
    $params[]     = $_GET['boite'];
}

// Filtre : carburant
if (!empty($_GET['carburant'])) {
    $conditions[] = 'a.carburant = ?';
    $params[]     = $_GET['carburant'];
}

// Filtre : prix
if (!empty($_GET['prix'])) {
    switch ($_GET['prix']) {
        case 'moins10':
            $conditions[] = 'a.prix < 10000';  break;
        case '10-20':
            $conditions[] = 'a.prix BETWEEN 10000 AND 20000'; break;
        case '20-30':
            $conditions[] = 'a.prix BETWEEN 20000 AND 30000'; break;
        case 'plus30':
            $conditions[] = 'a.prix > 30000';  break;
    }
}

$where = 'WHERE ' . implode(' AND ', $conditions);

// Récupère les annonces avec la photo principale
$sql = "
    SELECT a.*, 
           u.nom AS vendeur_nom, u.prenom AS vendeur_prenom, u.ville AS vendeur_ville,
           i.chemin AS photo_principale
    FROM annonces a
    JOIN utilisateurs u ON u.id = a.id_utilisateur
    LEFT JOIN images i ON i.id_annonce = a.id AND i.est_principale = 1
    $where
    ORDER BY a.date_publication DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$annonces = $stmt->fetchAll();

// Récupère les marques disponibles pour le filtre
$marques = $pdo->query("SELECT DISTINCT marque FROM annonces WHERE statut = 'active' ORDER BY marque")
               ->fetchAll(PDO::FETCH_COLUMN);

$user = utilisateur_courant();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Recherche — AUTOMARKET</title>
    <link rel="stylesheet" href="CSS.css">
    <style>
        /* Filtre actif */
        .dropbtn.actif {
            border-color: var(--rouge);
            color: var(--rouge-vif);
            background-color: rgba(204,17,17,0.1);
        }
        /* Compteur de résultats */
        .nb-resultats {
            font-family: var(--font-label);
            font-size: 0.8rem;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--gris-clair);
            margin: 0 20px 10px;
        }
        .nb-resultats span { color: var(--rouge-vif); }

        /* Grille d'annonces */
        .grille-annonces {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 14px;
            margin: 0 20px 30px;
        }

        /* Carte annonce */
        .carte-annonce {
            background-color: var(--noir-3);
            border: 1px solid var(--gris-fonce);
            border-top: 3px solid var(--rouge);
            border-radius: var(--radius);
            overflow: hidden;
            transition: transform var(--transition), box-shadow var(--transition);
            cursor: pointer;
        }
        .carte-annonce:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 28px rgba(0,0,0,0.5), 0 0 12px var(--rouge-glow);
        }
        .carte-annonce a { color: inherit; display: block; }

        .carte-photo {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background-color: var(--noir-4);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gris-moyen);
            font-size: 2.5rem;
        }
        .carte-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .carte-corps {
            padding: 16px 18px;
        }
        .carte-titre {
            font-family: var(--font-titre);
            font-size: 1.3rem;
            letter-spacing: 1px;
            color: var(--blanc-pur);
            margin-bottom: 4px;
        }
        .carte-sous-titre {
            font-size: 0.82rem;
            color: var(--gris-clair);
            font-family: var(--font-label);
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }
        .carte-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 14px;
        }
        .tag {
            background-color: var(--noir-4);
            border: 1px solid var(--gris-fonce);
            color: var(--gris-clair);
            font-family: var(--font-label);
            font-size: 0.72rem;
            letter-spacing: 0.8px;
            padding: 3px 8px;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .carte-pied {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid var(--gris-fonce);
            padding-top: 12px;
            margin-top: 4px;
        }
        .carte-prix {
            font-family: var(--font-titre);
            font-size: 1.6rem;
            color: var(--rouge-vif);
            letter-spacing: 1px;
        }
        .carte-vendeur {
            font-size: 0.78rem;
            color: var(--gris-moyen);
            text-align: right;
            font-family: var(--font-label);
        }

        /* Aucun résultat */
        .aucun-resultat {
            text-align: center;
            padding: 60px 20px;
            color: var(--gris-moyen);
            grid-column: 1 / -1;
        }
        .aucun-resultat p:first-child {
            font-size: 3rem;
            margin-bottom: 12px;
        }

        /* Barre de recherche texte */
        .barre-recherche {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 14px 20px 0;
        }
        .barre-recherche input {
            flex: 1;
            max-width: 400px;
        }

        /* Lien reset filtres */
        .reset-filtres {
            font-family: var(--font-label);
            font-size: 0.75rem;
            letter-spacing: 1px;
            color: var(--gris-moyen);
            text-decoration: none;
            text-transform: uppercase;
            transition: color var(--transition);
            margin-left: 8px;
        }
        .reset-filtres:hover { color: var(--rouge-vif); }
    </style>
</head>
<body>

<!-- ====== HEADER ====== -->
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

<?= afficher_flash() ?>

<!-- ====== FILTRES ====== -->
<form method="GET" action="Page_recherche.php">
    <div class="barre2">
        <div class="barre_filtres">
            <p>Filtres</p>

            <!-- Marque -->
            <div class="dropdown">
                <button type="button" class="dropbtn <?= !empty($_GET['marque']) ? 'actif' : '' ?>">
                    <?= !empty($_GET['marque']) ? e($_GET['marque']) : 'Marque' ?> ▾
                </button>
                <div class="dropdown-content">
                    <?php foreach ($marques as $m): ?>
                        <a href="#" onclick="setFiltre('marque','<?= e($m) ?>'); return false;">
                            <?= e($m) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <input type="hidden" name="marque" id="f_marque" value="<?= e($_GET['marque'] ?? '') ?>">

            <!-- Kilométrage -->
            <div class="dropdown">
                <button type="button" class="dropbtn <?= !empty($_GET['kilometrage']) ? 'actif' : '' ?>">
                    <?php
                        $labels_km = ['moins50'=>'- 50 000 km','50-100'=>'50-100k km','plus100'=>'+ 100 000 km'];
                        echo !empty($_GET['kilometrage']) ? $labels_km[$_GET['kilometrage']] : 'Kilométrage';
                    ?> ▾
                </button>
                <div class="dropdown-content">
                    <a href="#" onclick="setFiltre('kilometrage','moins50'); return false;">Moins de 50 000 km</a>
                    <a href="#" onclick="setFiltre('kilometrage','50-100'); return false;">50 000 – 100 000 km</a>
                    <a href="#" onclick="setFiltre('kilometrage','plus100'); return false;">Plus de 100 000 km</a>
                </div>
            </div>
            <input type="hidden" name="kilometrage" id="f_kilometrage" value="<?= e($_GET['kilometrage'] ?? '') ?>">

            <!-- Boîte -->
            <div class="dropdown">
                <button type="button" class="dropbtn <?= !empty($_GET['boite']) ? 'actif' : '' ?>">
                    <?= !empty($_GET['boite']) ? e($_GET['boite']) : 'Boîte' ?> ▾
                </button>
                <div class="dropdown-content">
                    <a href="#" onclick="setFiltre('boite','Manuelle'); return false;">Manuelle</a>
                    <a href="#" onclick="setFiltre('boite','Automatique'); return false;">Automatique</a>
                </div>
            </div>
            <input type="hidden" name="boite" id="f_boite" value="<?= e($_GET['boite'] ?? '') ?>">

            <!-- Carburant -->
            <div class="dropdown">
                <button type="button" class="dropbtn <?= !empty($_GET['carburant']) ? 'actif' : '' ?>">
                    <?= !empty($_GET['carburant']) ? e($_GET['carburant']) : 'Motorisation' ?> ▾
                </button>
                <div class="dropdown-content">
                    <a href="#" onclick="setFiltre('carburant','Essence'); return false;">Essence</a>
                    <a href="#" onclick="setFiltre('carburant','Diesel'); return false;">Diesel</a>
                    <a href="#" onclick="setFiltre('carburant','Électrique'); return false;">Électrique</a>
                    <a href="#" onclick="setFiltre('carburant','Hybride'); return false;">Hybride</a>
                </div>
            </div>
            <input type="hidden" name="carburant" id="f_carburant" value="<?= e($_GET['carburant'] ?? '') ?>">

            <!-- Prix -->
            <div class="dropdown">
                <button type="button" class="dropbtn <?= !empty($_GET['prix']) ? 'actif' : '' ?>">
                    <?php
                        $labels_prix = ['moins10'=>'- 10 000 €','10-20'=>'10-20k €','20-30'=>'20-30k €','plus30'=>'+ 30 000 €'];
                        echo !empty($_GET['prix']) ? $labels_prix[$_GET['prix']] : 'Prix';
                    ?> ▾
                </button>
                <div class="dropdown-content">
                    <a href="#" onclick="setFiltre('prix','moins10'); return false;">Moins de 10 000 €</a>
                    <a href="#" onclick="setFiltre('prix','10-20'); return false;">10 000 – 20 000 €</a>
                    <a href="#" onclick="setFiltre('prix','20-30'); return false;">20 000 – 30 000 €</a>
                    <a href="#" onclick="setFiltre('prix','plus30'); return false;">30 000 € et plus</a>
                </div>
            </div>
            <input type="hidden" name="prix" id="f_prix" value="<?= e($_GET['prix'] ?? '') ?>">

            <?php if (!empty(array_filter($_GET ?? []))): ?>
                <a href="Page_recherche.php" class="reset-filtres">✕ Réinitialiser</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (est_connecte()): ?>
    <div style="margin: 0 20px 4px; text-align:right;">
        <button type="button" class="boutton" onclick="location.href='nouvelle_annonce.php'"
                style="font-size:0.82rem; padding:8px 16px;">
            + Déposer une annonce
        </button>
    </div>
    <?php endif; ?>
</form>

<!-- ====== RÉSULTATS ====== -->
<p class="nb-resultats">
    <span><?= count($annonces) ?></span> annonce<?= count($annonces) > 1 ? 's' : '' ?> trouvée<?= count($annonces) > 1 ? 's' : '' ?>
</p>

<div class="grille-annonces">
    <?php if (empty($annonces)): ?>
        <div class="aucun-resultat">
            <p>🔍</p>
            <p style="font-family:var(--font-titre); font-size:1.5rem; color:var(--blanc);">Aucune annonce trouvée</p>
            <p style="font-size:0.9rem; margin-top:8px;">Essayez de modifier vos filtres</p>
        </div>
    <?php else: ?>
        <?php foreach ($annonces as $a): ?>
        <div class="carte-annonce">
            <a href="page_annonce.php?id=<?= $a['id'] ?>">
                <div class="carte-photo">
                    <?php if ($a['photo_principale']): ?>
                        <img src="<?= e($a['photo_principale']) ?>" alt="<?= e($a['titre']) ?>">
                    <?php else: ?>
                        🚗
                    <?php endif; ?>
                </div>
                <div class="carte-corps">
                    <div class="carte-titre"><?= e($a['marque']) ?> <?= e($a['modele']) ?></div>
                    <div class="carte-sous-titre"><?= e($a['annee']) ?> · <?= e($a['motorisation'] ?? '') ?></div>
                    <div class="carte-tags">
                        <span class="tag"><?= formater_km($a['kilometrage']) ?></span>
                        <span class="tag"><?= e($a['carburant']) ?></span>
                        <span class="tag"><?= e($a['boite']) ?></span>
                        <?php if ($a['couleur']): ?>
                            <span class="tag"><?= e($a['couleur']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="carte-pied">
                        <div class="carte-prix"><?= formater_prix($a['prix']) ?></div>
                        <div class="carte-vendeur">
                            <?= e($a['vendeur_prenom']) ?><br>
                            <?= e($a['vendeur_ville'] ?? '') ?>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
// Sélectionne un filtre et soumet le formulaire automatiquement
function setFiltre(nom, valeur) {
    document.getElementById('f_' + nom).value = valeur;
    document.querySelector('form').submit();
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
