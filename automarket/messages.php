<?php
//Messagerie

require_once 'fonctions.php';
require_once 'connexion_db.php';

exiger_connexion();

$user_id = $_SESSION['user_id'];

// Envoi d'une réponse 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'repondre') {
    $id_annonce      = (int)($_POST['id_annonce']      ?? 0);
    $id_destinataire = (int)($_POST['id_destinataire'] ?? 0);
    $contenu         = trim($_POST['contenu']           ?? '');

    if ($id_annonce && $id_destinataire && strlen($contenu) >= 2) {
        $stmt = $pdo->prepare('
            INSERT INTO messages (id_annonce, id_expediteur, id_destinataire, contenu)
            VALUES (?, ?, ?, ?)
        ');
        $stmt->execute([$id_annonce, $user_id, $id_destinataire, $contenu]);
    }
    // Redirige vers la même conversation
    header("Location: messages.php?annonce={$id_annonce}&interlocuteur={$id_destinataire}");
    exit;
}

// --- Récupère toutes les conversations de l'utilisateur ---
// Une conversation = (annonce + interlocuteur)
$conversations = $pdo->prepare("
    SELECT
        a.id         AS id_annonce,
        a.marque, a.modele, a.annee,
        u.id         AS id_interlocuteur,
        u.nom        AS inter_nom,
        u.prenom     AS inter_prenom,
        u.photo_profil AS inter_photo,
        MAX(m.date_envoi) AS dernier_message,
        COUNT(CASE WHEN m.id_destinataire = ? AND m.lu = 0 THEN 1 END) AS non_lus,
        (
            SELECT contenu FROM messages
            WHERE (id_expediteur = ? OR id_destinataire = ?)
              AND (id_expediteur = u.id OR id_destinataire = u.id)
              AND id_annonce = a.id
            ORDER BY date_envoi DESC LIMIT 1
        ) AS apercu
    FROM messages m
    JOIN annonces a      ON a.id = m.id_annonce
    JOIN utilisateurs u  ON u.id = IF(m.id_expediteur = ?, m.id_destinataire, m.id_expediteur)
    WHERE m.id_expediteur = ? OR m.id_destinataire = ?
    GROUP BY a.id, u.id
    ORDER BY dernier_message DESC
");
$conversations->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
$conversations = $conversations->fetchAll();

// --- Conversation active ---
$id_annonce_active      = filter_input(INPUT_GET, 'annonce',       FILTER_VALIDATE_INT);
$id_interlocuteur_actif = filter_input(INPUT_GET, 'interlocuteur', FILTER_VALIDATE_INT);
$messages_conversation  = [];
$interlocuteur          = null;
$annonce_active         = null;

if ($id_annonce_active && $id_interlocuteur_actif) {
    // Marque les messages comme lus
    $stmt = $pdo->prepare('
        UPDATE messages SET lu = 1
        WHERE id_annonce = ?
          AND id_expediteur = ?
          AND id_destinataire = ?
    ');
    $stmt->execute([$id_annonce_active, $id_interlocuteur_actif, $user_id]);

    // Charge les messages de la conversation
    $stmt = $pdo->prepare("
        SELECT m.*, 
               u.nom AS exp_nom, u.prenom AS exp_prenom, u.photo_profil AS exp_photo
        FROM messages m
        JOIN utilisateurs u ON u.id = m.id_expediteur
        WHERE m.id_annonce = ?
          AND (
              (m.id_expediteur = ? AND m.id_destinataire = ?)
           OR (m.id_expediteur = ? AND m.id_destinataire = ?)
          )
        ORDER BY m.date_envoi ASC
    ");
    $stmt->execute([
        $id_annonce_active,
        $user_id, $id_interlocuteur_actif,
        $id_interlocuteur_actif, $user_id
    ]);
    $messages_conversation = $stmt->fetchAll();

    // Infos interlocuteur
    $stmt = $pdo->prepare('SELECT id, nom, prenom, photo_profil, telephone FROM utilisateurs WHERE id = ?');
    $stmt->execute([$id_interlocuteur_actif]);
    $interlocuteur = $stmt->fetch();

    // Infos annonce
    $stmt = $pdo->prepare('SELECT id, marque, modele, annee, prix FROM annonces WHERE id = ?');
    $stmt->execute([$id_annonce_active]);
    $annonce_active = $stmt->fetch();
}

$user = utilisateur_courant();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Messages — AUTOMARKET</title>
    <link rel="stylesheet" href="CSS.css">
    <style>
        .page-messages {
            max-width: 1100px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Layout messagerie */
        .messagerie {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 16px;
            height: calc(100vh - 160px);
            min-height: 500px;
        }

        /* Liste conversations */
        .liste-conversations {
            background-color: var(--noir-3);
            border: 1px solid var(--gris-fonce);
            border-radius: var(--radius);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .conversations-header {
            padding: 16px 18px;
            border-bottom: 1px solid var(--gris-fonce);
            font-family: var(--font-titre);
            font-size: 1.2rem;
            letter-spacing: 2px;
            color: var(--rouge);
            flex-shrink: 0;
        }

        .conversation-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border-bottom: 1px solid var(--noir-4);
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            transition: background-color var(--transition);
            position: relative;
        }
        .conversation-item:hover    { background-color: var(--noir-4); }
        .conversation-item.active   {
            background-color: rgba(204,17,17,0.1);
            border-left: 3px solid var(--rouge);
        }

        .conv-photo {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--gris-fonce);
            flex-shrink: 0;
        }
        .conv-photo.non-lu { border-color: var(--rouge); }

        .conv-infos { flex: 1; min-width: 0; }
        .conv-nom {
            font-family: var(--font-label);
            font-weight: 700;
            font-size: 0.88rem;
            letter-spacing: 0.5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--blanc);
        }
        .conv-annonce {
            font-size: 0.78rem;
            color: var(--rouge-vif);
            font-family: var(--font-label);
            letter-spacing: 0.5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 1px;
        }
        .conv-apercu {
            font-size: 0.78rem;
            color: var(--gris-moyen);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 3px;
        }
        .conv-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 5px;
            flex-shrink: 0;
        }
        .conv-date {
            font-size: 0.72rem;
            color: var(--gris-moyen);
            font-family: var(--font-label);
        }
        .badge-non-lu {
            background-color: var(--rouge);
            color: white;
            font-size: 0.68rem;
            font-family: var(--font-label);
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
        }

        /* Panneau conversation */
        .panneau-conversation {
            background-color: var(--noir-3);
            border: 1px solid var(--gris-fonce);
            border-radius: var(--radius);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* En-tête conversation */
        .conv-entete {
            padding: 14px 20px;
            border-bottom: 2px solid var(--rouge);
            display: flex;
            align-items: center;
            gap: 14px;
            background-color: var(--noir-4);
            flex-shrink: 0;
        }
        .conv-entete img {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--rouge);
        }
        .conv-entete-infos { flex: 1; }
        .conv-entete-nom {
            font-family: var(--font-titre);
            font-size: 1.2rem;
            letter-spacing: 1px;
        }
        .conv-entete-annonce {
            font-size: 0.8rem;
            color: var(--rouge-vif);
            font-family: var(--font-label);
            letter-spacing: 0.5px;
        }

        /* Zone messages */
        .zone-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .bulle-wrapper {
            display: flex;
            align-items: flex-end;
            gap: 8px;
        }
        .bulle-wrapper.moi { flex-direction: row-reverse; }

        .bulle-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
            border: 1px solid var(--gris-fonce);
        }

        .bulle {
            max-width: 65%;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 0.9rem;
            line-height: 1.5;
            word-break: break-word;
        }
        .bulle.autre {
            background-color: var(--noir-4);
            border: 1px solid var(--gris-fonce);
            border-bottom-left-radius: 3px;
            color: var(--blanc);
        }
        .bulle.moi {
            background: linear-gradient(135deg, var(--rouge-sombre), var(--rouge));
            border-bottom-right-radius: 3px;
            color: white;
        }
        .bulle-date {
            font-size: 0.68rem;
            color: var(--gris-moyen);
            font-family: var(--font-label);
            text-align: center;
            margin: 4px 0;
        }

        /* Zone de saisie */
        .zone-saisie {
            padding: 14px 18px;
            border-top: 1px solid var(--gris-fonce);
            background-color: var(--noir-4);
            display: flex;
            gap: 10px;
            align-items: flex-end;
            flex-shrink: 0;
        }
        .zone-saisie textarea {
            flex: 1;
            background-color: var(--noir-3);
            border: 1px solid var(--gris-fonce);
            border-radius: var(--radius);
            color: var(--blanc);
            font-family: var(--font-corps);
            font-size: 0.9rem;
            padding: 10px 14px;
            resize: none;
            min-height: 44px;
            max-height: 120px;
            outline: none;
            transition: border-color var(--transition);
        }
        .zone-saisie textarea:focus { border-color: var(--rouge); }
        .zone-saisie button {
            padding: 10px 18px;
            flex-shrink: 0;
            align-self: flex-end;
        }

        /* Écran vide */
        .ecran-vide {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--gris-moyen);
            gap: 12px;
        }
        .ecran-vide p:first-child { font-size: 3rem; }
        .ecran-vide p:nth-child(2) {
            font-family: var(--font-titre);
            font-size: 1.4rem;
            color: var(--blanc);
            letter-spacing: 2px;
        }

        /* Aucune conversation */
        .aucune-conv {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            color: var(--gris-moyen);
            text-align: center;
            gap: 10px;
        }

        /* Scrollbar custom */
        .zone-messages::-webkit-scrollbar,
        .liste-conversations::-webkit-scrollbar { width: 5px; }
        .zone-messages::-webkit-scrollbar-track,
        .liste-conversations::-webkit-scrollbar-track { background: var(--noir-3); }
        .zone-messages::-webkit-scrollbar-thumb,
        .liste-conversations::-webkit-scrollbar-thumb {
            background: var(--gris-fonce);
            border-radius: 3px;
        }

        @media (max-width: 700px) {
            .messagerie { grid-template-columns: 1fr; height: auto; }
            .panneau-conversation { min-height: 400px; }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<div class="bandeau_acceuil">
    <h1>AUTOMARKET</h1>
    <div style="display:flex; gap:10px; align-items:center;">
        <button class="boutton" onclick="location.href='Page_recherche.php'">Recherche</button>
        <button class="boutton" onclick="location.href='page_compte.php'">Mon compte</button>
        <button class="boutton boutton-ghost" onclick="location.href='deconnexion.php'">Déconnexion</button>
    </div>
</div>

<div class="page-messages">

    <h1 style="font-size:2rem; margin-bottom:16px; letter-spacing:2px;">Messages</h1>

    <div class="messagerie">

        <!-- ===== LISTE DES CONVERSATIONS ===== -->
        <div class="liste-conversations">
            <div class="conversations-header">Conversations</div>

            <?php if (empty($conversations)): ?>
                <div class="aucune-conv">
                    <p style="font-size:2.5rem;">✉</p>
                    <p style="font-family:var(--font-titre); font-size:1.2rem; color:var(--blanc);">
                        Aucun message
                    </p>
                    <p style="font-size:0.85rem;">
                        Les messages reçus sur vos annonces apparaîtront ici
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($conversations as $conv):
                    $est_active = $id_annonce_active == $conv['id_annonce']
                               && $id_interlocuteur_actif == $conv['id_interlocuteur'];
                    $url = "messages.php?annonce={$conv['id_annonce']}&interlocuteur={$conv['id_interlocuteur']}";
                ?>
                <a href="<?= e($url) ?>"
                   class="conversation-item <?= $est_active ? 'active' : '' ?>">
                    <img src="<?= e($conv['inter_photo']) ?>"
                         alt="<?= e($conv['inter_prenom']) ?>"
                         class="conv-photo <?= $conv['non_lus'] > 0 ? 'non-lu' : '' ?>">
                    <div class="conv-infos">
                        <div class="conv-nom">
                            <?= e($conv['inter_prenom']) ?> <?= e($conv['inter_nom']) ?>
                        </div>
                        <div class="conv-annonce">
                            <?= e($conv['marque']) ?> <?= e($conv['modele']) ?> <?= e($conv['annee']) ?>
                        </div>
                        <div class="conv-apercu">
                            <?= e(mb_strimwidth($conv['apercu'] ?? '', 0, 45, '...')) ?>
                        </div>
                    </div>
                    <div class="conv-meta">
                        <span class="conv-date">
                            <?php
                                $date = new DateTime($conv['dernier_message']);
                                $now  = new DateTime();
                                echo $date->format('d/m') === $now->format('d/m')
                                    ? $date->format('H:i')
                                    : $date->format('d/m/y');
                            ?>
                        </span>
                        <?php if ($conv['non_lus'] > 0): ?>
                            <span class="badge-non-lu"><?= $conv['non_lus'] ?></span>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- ===== PANNEAU CONVERSATION ===== -->
        <div class="panneau-conversation">

            <?php if ($annonce_active && $interlocuteur): ?>

                <!-- En-tête -->
                <div class="conv-entete">
                    <img src="<?= e($interlocuteur['photo_profil']) ?>"
                         alt="<?= e($interlocuteur['prenom']) ?>">
                    <div class="conv-entete-infos">
                        <div class="conv-entete-nom">
                            <?= e($interlocuteur['prenom']) ?> <?= e($interlocuteur['nom']) ?>
                        </div>
                        <div class="conv-entete-annonce">
                            À propos de :
                            <a href="page_annonce.php?id=<?= $annonce_active['id'] ?>"
                               style="color:var(--rouge-vif);">
                                <?= e($annonce_active['marque']) ?> <?= e($annonce_active['modele']) ?>
                                <?= e($annonce_active['annee']) ?>
                                — <?= formater_prix($annonce_active['prix']) ?>
                            </a>
                        </div>
                    </div>
                    <?php if ($interlocuteur['telephone']): ?>
                        <span style="color:var(--gris-clair); font-family:var(--font-label);
                                     font-size:0.82rem; letter-spacing:0.5px;">
                            📞 <?= e($interlocuteur['telephone']) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Messages -->
                <div class="zone-messages" id="zoneMessages">
                    <?php
                        $date_precedente = null;
                        foreach ($messages_conversation as $msg):
                            $est_moi    = $msg['id_expediteur'] == $user_id;
                            $date_msg   = (new DateTime($msg['date_envoi']))->format('d/m/Y');
                            $heure_msg  = (new DateTime($msg['date_envoi']))->format('H:i');
                    ?>
                        <?php if ($date_msg !== $date_precedente): ?>
                            <p class="bulle-date"><?= $date_msg ?></p>
                            <?php $date_precedente = $date_msg; ?>
                        <?php endif; ?>

                        <div class="bulle-wrapper <?= $est_moi ? 'moi' : '' ?>">
                            <img src="<?= e($msg['exp_photo']) ?>"
                                 alt="<?= e($msg['exp_prenom']) ?>"
                                 class="bulle-avatar">
                            <div>
                                <div class="bulle <?= $est_moi ? 'moi' : 'autre' ?>">
                                    <?= nl2br(e($msg['contenu'])) ?>
                                </div>
                                <p style="font-size:0.68rem; color:var(--gris-moyen);
                                           font-family:var(--font-label); margin-top:3px;
                                           text-align:<?= $est_moi ? 'right' : 'left' ?>;">
                                    <?= $heure_msg ?>
                                    <?php if ($est_moi && $msg['lu']): ?>
                                        · <span style="color:var(--rouge);">Lu</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Zone de saisie -->
                <div class="zone-saisie">
                    <form method="POST" style="display:contents;">
                        <input type="hidden" name="action"          value="repondre">
                        <input type="hidden" name="id_annonce"      value="<?= $annonce_active['id'] ?>">
                        <input type="hidden" name="id_destinataire" value="<?= $interlocuteur['id'] ?>">
                        <textarea name="contenu" id="saisie"
                                  placeholder="Écrivez votre message..."
                                  rows="1"
                                  onkeydown="envoyerEntree(event, this)"></textarea>
                        <button type="submit">Envoyer ↑</button>
                    </form>
                </div>

            <?php else: ?>
                <!-- Aucune conversation sélectionnée -->
                <div class="ecran-vide">
                    <p>💬</p>
                    <p>Sélectionnez une conversation</p>
                    <p style="font-size:0.88rem;">
                        Choisissez une conversation dans la liste à gauche
                    </p>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
// Scroll automatique vers le bas à l'ouverture
const zone = document.getElementById('zoneMessages');
if (zone) zone.scrollTop = zone.scrollHeight;

// Envoyer avec Entrée (Shift+Entrée = saut de ligne)
function envoyerEntree(e, textarea) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        if (textarea.value.trim().length >= 2) {
            textarea.closest('form').submit();
        }
    }
}

// Auto-resize du textarea
const saisie = document.getElementById('saisie');
if (saisie) {
    saisie.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
}
</script>
</body>
</html>
