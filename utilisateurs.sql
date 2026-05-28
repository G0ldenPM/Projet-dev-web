-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : jeu. 28 mai 2026 à 14:55
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
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
