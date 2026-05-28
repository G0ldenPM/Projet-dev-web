-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : jeu. 28 mai 2026 à 15:00
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `automarket`
--

-- --------------------------------------------------------

--
-- Structure de la table `annonces`
--

CREATE TABLE `annonces` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_utilisateur` int(10) UNSIGNED NOT NULL,
  `titre` varchar(150) NOT NULL,
  `marque` varchar(80) NOT NULL,
  `modele` varchar(80) NOT NULL,
  `annee` year(4) NOT NULL,
  `prix` decimal(10,2) NOT NULL,
  `carburant` enum('Essence','Diesel','Électrique','Hybride') NOT NULL,
  `boite` enum('Manuelle','Automatique') NOT NULL,
  `motorisation` varchar(80) DEFAULT NULL,
  `kilometrage` int(10) UNSIGNED NOT NULL,
  `nb_places` tinyint(3) UNSIGNED DEFAULT 5,
  `nb_portes` tinyint(3) UNSIGNED DEFAULT 5,
  `critair` tinyint(3) UNSIGNED DEFAULT NULL,
  `couleur` varchar(50) DEFAULT NULL,
  `longueur` smallint(5) UNSIGNED DEFAULT NULL,
  `largeur` smallint(5) UNSIGNED DEFAULT NULL,
  `hauteur` smallint(5) UNSIGNED DEFAULT NULL,
  `poids` smallint(5) UNSIGNED DEFAULT NULL,
  `description` text DEFAULT NULL,
  `statut` enum('active','pause','vendue','supprimée') NOT NULL DEFAULT 'active',
  `date_publication` datetime NOT NULL DEFAULT current_timestamp(),
  `date_modification` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `annonces`
--

INSERT INTO `annonces` (`id`, `id_utilisateur`, `titre`, `marque`, `modele`, `annee`, `prix`, `carburant`, `boite`, `motorisation`, `kilometrage`, `nb_places`, `nb_portes`, `critair`, `couleur`, `longueur`, `largeur`, `hauteur`, `poids`, `description`, `statut`, `date_publication`, `date_modification`) VALUES
(1, 1, 'Peugeot 308 SW - Très bon état', 'Peugeot', '308 SW', '2021', 15900.00, 'Diesel', 'Manuelle', '1.5 BlueHDi 130ch', 62000, 5, 5, 2, 'Gris Platinium', 4640, 1852, 1470, 1385, 'Véhicule en excellent état, révision faite, pneus neufs.', 'active', '2026-05-19 08:59:24', '2026-05-26 08:34:20'),
(2, 1, 'Renault Clio V - Faible kilométrage', 'Renault', 'Clio V', '2020', 9800.00, 'Essence', 'Manuelle', '1.0 TCe 100ch', 38000, 5, 5, 1, 'Blanc Nacré', 4050, 1798, 1440, 1180, 'Première main, carnet d entretien complet, garantie 6 mois.', 'active', '2026-05-19 08:59:24', '2026-05-19 08:59:24'),
(3, 2, 'BMW Série 3 - Full options', 'BMW', 'Série 3', '2019', 22500.00, 'Diesel', 'Automatique', '2.0d 190ch', 91000, 5, 4, 2, 'Noir Saphir', 4713, 1827, 1435, 1555, 'Pack M Sport, toit ouvrant, sièges chauffants, CarPlay.', 'active', '2026-05-19 08:59:24', '2026-05-19 08:59:24'),
(4, 2, 'Audi A4 Avant - Quasi neuve', 'Audi', 'A4 Avant', '2022', 28900.00, 'Essence', 'Automatique', '2.0 TFSI 150ch', 31000, 5, 5, 1, 'Bleu Navarre', 4762, 1847, 1461, 1570, 'Garantie constructeur jusqu en 2025, aucun défaut.', 'active', '2026-05-19 08:59:24', '2026-05-19 08:59:24'),
(5, 3, 'Volkswagen Golf 8 - Hybride rechargeable', 'Volkswagen', 'Golf 8', '2021', 21000.00, 'Hybride', 'Automatique', '1.4 GTE 245ch', 47000, 5, 5, 1, 'Rouge Tornado', 4284, 1789, 1452, 1580, 'Autonomie électrique 60 km, charge rapide, entretien VW.', 'active', '2026-05-19 08:59:24', '2026-05-19 08:59:24'),
(6, 3, 'Tesla Model 3 - Longue autonomie', 'Tesla', 'Model 3', '2023', 36990.00, 'Électrique', 'Automatique', 'Grande Autonomie', 18000, 5, 4, 0, 'Blanc Nacré', 4694, 1933, 1440, 1830, 'Autonomie 580 km, Autopilot inclus, 0 à 100 en 4.4s.', 'active', '2026-05-19 08:59:24', '2026-05-19 08:59:24'),
(7, 1, 'annonce test modifiée', 'Peugeot', '206', '2000', 10001.00, 'Essence', 'Manuelle', '1.2 L', 60000, 5, 3, 2, 'blanc', NULL, NULL, NULL, NULL, 'voiture test', 'supprimée', '2026-05-26 08:26:12', '2026-05-26 08:32:17'),
(8, 4, 'annonce test', 'peugoet', '206', '2000', 4901.00, 'Essence', 'Manuelle', '1.2 L', 69999, 5, 3, 2, 'blanc', NULL, NULL, NULL, NULL, NULL, 'active', '2026-05-26 10:28:14', '2026-05-26 10:28:14');

-- --------------------------------------------------------

--
-- Structure de la table `images`
--

CREATE TABLE `images` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_annonce` int(10) UNSIGNED NOT NULL,
  `chemin` varchar(255) NOT NULL,
  `est_principale` tinyint(1) NOT NULL DEFAULT 0,
  `ordre` tinyint(3) UNSIGNED DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `images`
--

INSERT INTO `images` (`id`, `id_annonce`, `chemin`, `est_principale`, `ordre`) VALUES
(1, 1, 'uploads/annonces/1/principale.jpg', 1, 0),
(2, 2, 'uploads/annonces/2/principale.jpg', 1, 0),
(3, 3, 'uploads/annonces/3/principale.jpg', 1, 0),
(4, 4, 'uploads/annonces/4/principale.jpg', 1, 0),
(5, 5, 'uploads/annonces/5/principale.jpg', 1, 0),
(6, 6, 'uploads/annonces/6/principale.jpg', 1, 0),
(7, 8, 'uploads/annonces/8/photo_1.jpg', 1, 0);

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

CREATE TABLE `messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_annonce` int(10) UNSIGNED NOT NULL,
  `id_expediteur` int(10) UNSIGNED NOT NULL,
  `id_destinataire` int(10) UNSIGNED NOT NULL,
  `contenu` text NOT NULL,
  `lu` tinyint(1) NOT NULL DEFAULT 0,
  `date_envoi` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `messages`
--

INSERT INTO `messages` (`id`, `id_annonce`, `id_expediteur`, `id_destinataire`, `contenu`, `lu`, `date_envoi`) VALUES
(1, 3, 1, 2, 'test : message envoyé parjean dupont à sophie martin à propos de l\'annonce BMW serie 3', 0, '2026-05-19 09:22:52');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(80) NOT NULL,
  `prenom` varchar(80) NOT NULL,
  `email` varchar(180) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `code_postal` varchar(10) DEFAULT NULL,
  `date_naissance` date DEFAULT NULL,
  `photo_profil` varchar(255) DEFAULT 'images/photo-profil-defaut.jpg',
  `date_inscription` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `prenom`, `email`, `mot_de_passe`, `telephone`, `adresse`, `ville`, `code_postal`, `date_naissance`, `photo_profil`, `date_inscription`) VALUES
(1, 'Dupont', 'Jean', 'jean.dupont@mail.com', '$2y$10$3vX8exLU1iFIhgdr.t0LlOop4hzOgNoav67.wHBivqawpwHAZvFZO', '06 11 22 33 44', '12 rue de la Paix', 'Paris', '75001', '1990-05-14', 'images/photo-profil-defaut.jpg', '2026-05-19 08:59:24'),
(2, 'Martin', 'Sophie', 'sophie.martin@mail.com', '$2y$10$3vX8exLU1iFIhgdr.t0LlOop4hzOgNoav67.wHBivqawpwHAZvFZO', '07 22 33 44 55', '8 avenue des Fleurs', 'Lyon', '69003', '1985-11-30', 'images/photo-profil-defaut.jpg', '2026-05-19 08:59:24'),
(3, 'Leroy', 'Thomas', 'thomas.leroy@mail.com', '$2y$10$3vX8exLU1iFIhgdr.t0LlOop4hzOgNoav67.wHBivqawpwHAZvFZO', '06 33 44 55 66', '3 boulevard Gambetta', 'Marseille', '13001', '1992-03-22', 'images/photo-profil-defaut.jpg', '2026-05-19 08:59:24'),
(4, 'Maille', 'Pierre', 'pierre.maille@eleve.isep.fr', '$2y$10$SqNpzmio5iC9rWHamX.hOeqIc3LrLlWCHrsVVwWVhBe/5Mhy8AxPC', '0767795774', NULL, NULL, NULL, '2007-04-17', 'images/photo-profil-defaut.jpg', '2026-05-26 10:15:55'),
(5, 'el sebai', 'mohamed', 'email@email.fr', '$2y$10$NuGrRPZgkQY6oLRwhHYhUeL/AxMGjVll2quPx.2HE7WedEXjqjpam', '090009077789', NULL, NULL, NULL, '2026-05-08', 'images/photo-profil-defaut.jpg', '2026-05-26 12:08:48');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `annonces`
--
ALTER TABLE `annonces`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_utilisateur` (`id_utilisateur`),
  ADD KEY `idx_marque` (`marque`),
  ADD KEY `idx_prix` (`prix`),
  ADD KEY `idx_kilometrage` (`kilometrage`),
  ADD KEY `idx_carburant` (`carburant`),
  ADD KEY `idx_boite` (`boite`),
  ADD KEY `idx_statut` (`statut`);

--
-- Index pour la table `images`
--
ALTER TABLE `images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_annonce` (`id_annonce`);

--
-- Index pour la table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_annonce` (`id_annonce`),
  ADD KEY `id_expediteur` (`id_expediteur`),
  ADD KEY `id_destinataire` (`id_destinataire`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `annonces`
--
ALTER TABLE `annonces`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `images`
--
ALTER TABLE `images`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `annonces`
--
ALTER TABLE `annonces`
  ADD CONSTRAINT `annonces_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `images`
--
ALTER TABLE `images`
  ADD CONSTRAINT `images_ibfk_1` FOREIGN KEY (`id_annonce`) REFERENCES `annonces` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`id_annonce`) REFERENCES `annonces` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`id_expediteur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`id_destinataire`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
