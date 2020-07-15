CREATE TABLE `etudiant` (
  `id_etudiant` int PRIMARY KEY AUTO_INCREMENT,
  `nom` varchar(255),
  `prenom` varchar(255)
);

CREATE TABLE `matiere` (
  `id_matiere` int PRIMARY KEY AUTO_INCREMENT,
  `libelle` varchar(255),
  `coefficient` integer
);

CREATE TABLE `note` (
  `id_note` int PRIMARY KEY AUTO_INCREMENT,
  `id_etudiant` int,
  `id_matiere` int,
  `note` float
);

ALTER TABLE `note` ADD FOREIGN KEY (`id_etudiant`) REFERENCES `etudiant` (`id`);

ALTER TABLE `note` ADD FOREIGN KEY (`id_matiere`) REFERENCES `matiere` (`id`);
