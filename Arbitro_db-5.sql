-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Creato il: Set 25, 2025 alle 18:10
-- Versione del server: 10.4.28-MariaDB
-- Versione PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `Arbitro.db`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `eventi_partita`
--

CREATE TABLE `eventi_partita` (
  `id` int(11) NOT NULL,
  `tipo_evento` enum('gol','giallo','rosso','altro') NOT NULL,
  `minuto` int(11) DEFAULT NULL,
  `giocatore` int(100) DEFAULT NULL,
  `partita_id` int(11) NOT NULL,
  `km_partita` float DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `partite`
--

CREATE TABLE `partite` (
  `id` int(11) NOT NULL,
  `s_casa` varchar(100) DEFAULT NULL,
  `s_ospite` varchar(100) DEFAULT NULL,
  `indirizzo` varchar(100) DEFAULT NULL,
  `rimborso` float DEFAULT NULL,
  `km_percorsi` float DEFAULT NULL,
  `arbitro_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `partite`
--

INSERT INTO `partite` (`id`, `s_casa`, `s_ospite`, `indirizzo`, `rimborso`, `km_percorsi`, `arbitro_id`) VALUES
(1, 'asd Bellusco', 'virtus Adda', 'via adamello 4', 44, 30, 2),
(4, 'test', 'abc', 'ave', 3, 1, 3);

-- --------------------------------------------------------

--
-- Struttura della tabella `utente`
--

CREATE TABLE `utente` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `cognome` varchar(100) DEFAULT NULL,
  `email` varchar(200) NOT NULL,
  `password` varchar(255) NOT NULL,
  `sezione` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `utente`
--

INSERT INTO `utente` (`id`, `nome`, `cognome`, `email`, `password`, `sezione`) VALUES
(1, 'salvatore', 'cerreto', 'salvatore.cerreto@gmail.com', '$2y$10$cYJcbaUuetQTdQ7a3ad93.3PvbyCHvhrotqfTmjCkpWBRCAN1Q6YG', 'milano'),
(2, 'Francesco', 'Cerreto', 'francescocerreto268@gmail.com', '$2y$10$J13lCfa7GkkMjZOcvptr/O4rvHhMPGvo/AkALG7pp7x0IJbJ9SUWa', 'Monza'),
(3, 'saverio', 'cerreto', 'saverio.cerreto@gmail.com', '$2y$10$00gPT5NIEWqkWs3RvkVvpOmiP60vYzbMPLWG3zVik52vBGy.Dbnua', 'caserta');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `eventi_partita`
--
ALTER TABLE `eventi_partita`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_eventi_partita_partita` (`partita_id`);

--
-- Indici per le tabelle `partite`
--
ALTER TABLE `partite`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_partita_arbitro` (`arbitro_id`);

--
-- Indici per le tabelle `utente`
--
ALTER TABLE `utente`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `eventi_partita`
--
ALTER TABLE `eventi_partita`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT per la tabella `partite`
--
ALTER TABLE `partite`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT per la tabella `utente`
--
ALTER TABLE `utente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `eventi_partita`
--
ALTER TABLE `eventi_partita`
  ADD CONSTRAINT `fk_eventi_partita_partita` FOREIGN KEY (`partita_id`) REFERENCES `partite` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `partite`
--
ALTER TABLE `partite`
  ADD CONSTRAINT `fk_partita_arbitro` FOREIGN KEY (`arbitro_id`) REFERENCES `utente` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
