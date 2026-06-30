-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 23 juin 2026 à 07:55
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
-- Base de données : `gestion_restaurant`
--

-- --------------------------------------------------------

--
-- Structure de la table `commande`
--

CREATE TABLE `commande` (
  `idcom` varchar(20) NOT NULL,
  `nomcli` varchar(100) NOT NULL,
  `typecom` varchar(20) NOT NULL,
  `idtable` varchar(10) DEFAULT NULL,
  `datecom` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ligne_commande`
--

CREATE TABLE `ligne_commande` (
  `idligne` int(11) NOT NULL,
  `idcom` varchar(20) NOT NULL,
  `idplat` varchar(10) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `menu`
--

CREATE TABLE `menu` (
  `idplat` varchar(10) NOT NULL,
  `nomplat` varchar(100) NOT NULL,
  `pu` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `menu`
--

INSERT INTO `menu` (`idplat`, `nomplat`, `pu`) VALUES
('P001', 'Jus naturel', 2500),
('P002', 'Mine sao poulet', 10000),
('P003', 'Romazava', 1000),
('P004', 'Ravitoto', 7500),
('P005', 'Coca-cola', 3000),
('P006', 'Riz sauce tomate', 5000),
('P007', 'Poulet grillé', 12000),
('P008', 'Salade verte', 4000);

-- --------------------------------------------------------

--
-- Structure de la table `reserver`
--

CREATE TABLE `reserver` (
  `idreserv` varchar(20) NOT NULL,
  `idtable` varchar(10) NOT NULL,
  `date_de_reserv` datetime NOT NULL,
  `date_reserve` datetime NOT NULL,
  `nomcli` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `table_`
--

CREATE TABLE `table_` (
  `idtable` varchar(10) NOT NULL,
  `designation` varchar(100) NOT NULL,
  `occupation` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `commande`
--
ALTER TABLE `commande`
  ADD PRIMARY KEY (`idcom`),
  ADD KEY `idtable` (`idtable`);

--
-- Index pour la table `ligne_commande`
--
ALTER TABLE `ligne_commande`
  ADD PRIMARY KEY (`idligne`),
  ADD KEY `idcom` (`idcom`),
  ADD KEY `idplat` (`idplat`);

--
-- Index pour la table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`idplat`);

--
-- Index pour la table `reserver`
--
ALTER TABLE `reserver`
  ADD PRIMARY KEY (`idreserv`),
  ADD KEY `idtable` (`idtable`);

--
-- Index pour la table `table_`
--
ALTER TABLE `table_`
  ADD PRIMARY KEY (`idtable`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `ligne_commande`
--
ALTER TABLE `ligne_commande`
  MODIFY `idligne` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `commande`
--
ALTER TABLE `commande`
  ADD CONSTRAINT `commande_ibfk_1` FOREIGN KEY (`idtable`) REFERENCES `table_` (`idtable`);

--
-- Contraintes pour la table `ligne_commande`
--
ALTER TABLE `ligne_commande`
  ADD CONSTRAINT `ligne_commande_ibfk_1` FOREIGN KEY (`idcom`) REFERENCES `commande` (`idcom`) ON DELETE CASCADE,
  ADD CONSTRAINT `ligne_commande_ibfk_2` FOREIGN KEY (`idplat`) REFERENCES `menu` (`idplat`);

--
-- Contraintes pour la table `reserver`
--
ALTER TABLE `reserver`
  ADD CONSTRAINT `reserver_ibfk_1` FOREIGN KEY (`idtable`) REFERENCES `table_` (`idtable`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
