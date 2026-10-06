-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Oct 06, 2026 at 02:38 PM
-- Server version: 5.7.39
-- PHP Version: 8.3.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `jhcintradb`
--

-- --------------------------------------------------------

--
-- Table structure for table `aantekening`
--

CREATE TABLE `aantekening` (
  `id` int(11) NOT NULL,
  `delind` varchar(1) DEFAULT 'n',
  `id_user` int(11) DEFAULT NULL,
  `id_werkzkd` int(11) DEFAULT NULL,
  `datetime_created` timestamp NULL DEFAULT NULL,
  `tekst` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `agendaitem`
--

CREATE TABLE `agendaitem` (
  `id` int(11) NOT NULL,
  `delind` varchar(1) DEFAULT NULL,
  `id_user_created` int(11) DEFAULT NULL,
  `datetime_created` datetime DEFAULT NULL,
  `datetime_modified` datetime DEFAULT NULL,
  `type` varchar(4) DEFAULT NULL,
  `publmed` varchar(1) DEFAULT NULL,
  `datum` datetime NOT NULL,
  `begintijd` time(6) NOT NULL,
  `eindtijd` time(6) NOT NULL,
  `titel` varchar(500) DEFAULT NULL,
  `omschrijving` varchar(1000) NOT NULL DEFAULT '',
  `locatie` varchar(1000) NOT NULL DEFAULT '',
  `organisator` varchar(500) NOT NULL DEFAULT '',
  `picfile` varchar(60) NOT NULL,
  `freefld1` text,
  `freefld2` text,
  `freefld3` text,
  `freefld4` text,
  `freefld5` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `cat_name` varchar(255) NOT NULL,
  `cat_description` varchar(255) NOT NULL,
  `nbrTopics` int(11) DEFAULT NULL,
  `nbrPosts` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `enquete`
--

CREATE TABLE `enquete` (
  `id` int(11) NOT NULL,
  `id_enquete` int(11) DEFAULT NULL,
  `id_person` int(11) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `vrg01` varchar(1000) DEFAULT NULL,
  `vrg02` varchar(1000) DEFAULT NULL,
  `vrg03` varchar(1000) DEFAULT NULL,
  `vrg04` varchar(1000) DEFAULT NULL,
  `vrg05` varchar(1000) DEFAULT NULL,
  `vrg06` varchar(1000) DEFAULT NULL,
  `vrg07` varchar(1000) DEFAULT NULL,
  `vrg08` varchar(1000) DEFAULT NULL,
  `vrg09` varchar(1000) DEFAULT NULL,
  `vrg10` varchar(1000) DEFAULT NULL,
  `vrg11` varchar(1000) DEFAULT NULL,
  `vrg12` varchar(1000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `intakeform`
--

CREATE TABLE `intakeform` (
  `id` int(11) NOT NULL,
  `id_werkzkd` int(11) NOT NULL,
  `roepnaam` varchar(100) DEFAULT NULL,
  `gebplaats` varchar(100) DEFAULT NULL,
  `gebland` varchar(100) DEFAULT NULL,
  `nationaliteit` varchar(3) DEFAULT NULL,
  `legitimatieind` varchar(1) DEFAULT NULL,
  `relatie` varchar(3) DEFAULT NULL,
  `volw_kind` text,
  `partner_beroep` text,
  `aanmelding` varchar(3) DEFAULT NULL,
  `bron` text,
  `regeling` varchar(3) DEFAULT NULL,
  `uitdagingen` text,
  `beperking` text,
  `finsituatie` varchar(1) DEFAULT NULL,
  `redenen` text,
  `motivatie` text,
  `eisen` text,
  `netwerken` text,
  `andere_hulp` text,
  `CVind` varchar(1) DEFAULT NULL,
  `diploma` text,
  `studie` text,
  `werkervaring` text,
  `werk_gewenst` text,
  `voorwaarden` text,
  `taalbeh` varchar(1) DEFAULT NULL,
  `taalbeh_schr` varchar(1) DEFAULT NULL,
  `reistijd` text,
  `vervoer` text,
  `werkbijzh` text,
  `id_intaker` int(11) DEFAULT NULL,
  `overige_opm` text,
  `besprmis` varchar(1) DEFAULT NULL,
  `besprtkn` varchar(1) DEFAULT NULL,
  `besprvwk` varchar(1) DEFAULT NULL,
  `besprprv` varchar(1) DEFAULT NULL,
  `besprstatgeld` varchar(1) DEFAULT NULL,
  `besprkopie_ao` varchar(1) DEFAULT NULL,
  `besprvrijwbijd` varchar(1) DEFAULT NULL,
  `akkoord_datum` varchar(30) DEFAULT NULL,
  `akkoord_plaats` varchar(100) DEFAULT NULL,
  `akkoord_naam` varchar(100) DEFAULT NULL,
  `akkoord_handtek` varchar(100) DEFAULT NULL,
  `advjobgroup` varchar(1) DEFAULT NULL,
  `advmaatje` varchar(1) DEFAULT NULL,
  `advnietontv` text,
  `advopmerkingen` text,
  `advverwdatum` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `jgsessie`
--

CREATE TABLE `jgsessie` (
  `id` int(11) NOT NULL,
  `titel` varchar(200) DEFAULT NULL,
  `id_jobgroup` int(11) DEFAULT NULL,
  `sessienr` int(3) DEFAULT NULL,
  `id_locatie` varchar(200) DEFAULT NULL,
  `datetime_start` datetime DEFAULT NULL,
  `datetime_end` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `jobgroup`
--

CREATE TABLE `jobgroup` (
  `id` int(11) NOT NULL,
  `delind` varchar(1) NOT NULL DEFAULT 'n',
  `status` varchar(3) DEFAULT NULL,
  `titel` varchar(200) DEFAULT NULL,
  `id_locatie` varchar(200) DEFAULT NULL,
  `startdate` date DEFAULT NULL,
  `soort` varchar(10) DEFAULT NULL,
  `onlineind` varchar(1) NOT NULL,
  `nbrplaatsen` int(11) DEFAULT NULL,
  `omschrijving` varchar(2000) DEFAULT NULL,
  `jgleider1` varchar(100) DEFAULT NULL,
  `jgleider2` varchar(100) DEFAULT NULL,
  `opmerkingen` varchar(1000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `keytabel`
--

CREATE TABLE `keytabel` (
  `id` int(11) NOT NULL,
  `sleutel` char(20) CHARACTER SET utf8 DEFAULT NULL,
  `waarde` char(20) CHARACTER SET utf8 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `locatie`
--

CREATE TABLE `locatie` (
  `id` int(11) NOT NULL,
  `titel` varchar(200) DEFAULT NULL,
  `straat` varchar(200) DEFAULT NULL,
  `huisnummer` varchar(30) DEFAULT NULL,
  `postcode` varchar(7) DEFAULT NULL,
  `plaats` varchar(200) DEFAULT NULL,
  `sublocatie` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `maatje`
--

CREATE TABLE `maatje` (
  `id` int(11) NOT NULL,
  `id_person` int(11) DEFAULT NULL,
  `omschrijving` varchar(200) DEFAULT NULL,
  `functie` varchar(200) DEFAULT NULL,
  `actief_als` varchar(1) DEFAULT NULL,
  `mtjcrt_ind` varchar(1) NOT NULL DEFAULT 'n',
  `jglcrt_ind` varchar(1) NOT NULL DEFAULT 'n'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `newsitem`
--

CREATE TABLE `newsitem` (
  `id` int(11) NOT NULL,
  `delind` varchar(1) DEFAULT NULL,
  `id_user_created` int(11) DEFAULT NULL,
  `datetime_created` datetime DEFAULT NULL,
  `datetime_modified` datetime DEFAULT NULL,
  `titel` varchar(100) DEFAULT NULL,
  `subtitel` varchar(300) DEFAULT NULL,
  `tekst` mediumtext,
  `tekst_kort` varchar(5000) DEFAULT NULL,
  `tekst_samenvatting` varchar(7000) DEFAULT NULL,
  `tekst_knop` varchar(40) DEFAULT NULL,
  `link_knop` varchar(200) DEFAULT NULL,
  `pubind_intern` varchar(1) DEFAULT NULL,
  `datetime_pub_intern` datetime DEFAULT NULL,
  `pubind_extern` varchar(1) DEFAULT NULL,
  `datetime_pub_extern` datetime DEFAULT NULL,
  `picfile1` varchar(300) DEFAULT NULL,
  `picfile2` varchar(100) DEFAULT NULL,
  `picfile3` varchar(100) DEFAULT NULL,
  `picfile4` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `person`
--

CREATE TABLE `person` (
  `person_id` int(11) NOT NULL,
  `delind` varchar(1) DEFAULT NULL,
  `id_user_modified` int(11) DEFAULT NULL,
  `datetime_created` datetime DEFAULT NULL,
  `datetime_modified` datetime DEFAULT NULL,
  `voornaam` varchar(30) DEFAULT NULL,
  `tussenvoegsels` varchar(30) DEFAULT NULL,
  `achternaam` varchar(50) NOT NULL,
  `straat` varchar(60) DEFAULT NULL,
  `huisnummer` varchar(10) DEFAULT NULL,
  `postcode` varchar(7) DEFAULT NULL,
  `woonplaats` varchar(60) DEFAULT NULL,
  `emailadres` varchar(80) DEFAULT NULL,
  `telefoonnr` varchar(25) DEFAULT NULL,
  `type` char(3) DEFAULT NULL,
  `picfile` varchar(200) DEFAULT NULL,
  `link_linkedin` varchar(200) DEFAULT NULL,
  `presentInd` varchar(1) NOT NULL,
  `date_geboorte` date DEFAULT NULL,
  `geslacht` varchar(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `postit`
--

CREATE TABLE `postit` (
  `id` int(11) NOT NULL,
  `publInd` varchar(1) DEFAULT NULL,
  `publDatum` date NOT NULL,
  `titel` varchar(50) NOT NULL,
  `tekst` varchar(100) NOT NULL,
  `linkTekst` varchar(100) NOT NULL,
  `link` varchar(40) NOT NULL,
  `kleur` varchar(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `post_content` mediumtext NOT NULL,
  `post_date` datetime NOT NULL,
  `id_topic` int(11) NOT NULL,
  `id_user` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `processtap`
--

CREATE TABLE `processtap` (
  `id` int(11) NOT NULL,
  `delind` varchar(1) NOT NULL DEFAULT 'n',
  `id_werkzkd` int(11) DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `dt_stap` datetime DEFAULT NULL,
  `wzstatus` char(3) DEFAULT NULL,
  `drstrnaar` varchar(120) DEFAULT NULL,
  `toelichting` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `pubquizteams`
--

CREATE TABLE `pubquizteams` (
  `id` int(11) NOT NULL,
  `teamnaam` varchar(50) DEFAULT NULL,
  `dt_modified` timestamp NULL DEFAULT NULL,
  `vr01` varchar(1) NOT NULL,
  `vr02` varchar(1) NOT NULL,
  `vr03` varchar(1) NOT NULL,
  `vr04` varchar(1) NOT NULL,
  `vr05` varchar(1) NOT NULL,
  `vr06` varchar(1) NOT NULL,
  `vr07` varchar(1) NOT NULL,
  `vr08` varchar(1) NOT NULL,
  `vr09` varchar(1) NOT NULL,
  `vr10` varchar(1) NOT NULL,
  `vr11` varchar(1) NOT NULL,
  `vr12` varchar(1) NOT NULL,
  `vr13` varchar(1) NOT NULL,
  `vr14` varchar(1) NOT NULL,
  `vr15` varchar(1) NOT NULL,
  `vr16` varchar(1) NOT NULL,
  `vr17` varchar(1) NOT NULL,
  `vr18` varchar(1) NOT NULL,
  `vr19` varchar(1) NOT NULL,
  `vr20` varchar(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sucstor`
--

CREATE TABLE `sucstor` (
  `id` int(11) NOT NULL,
  `delind` varchar(1) DEFAULT NULL,
  `id_user_created` int(11) DEFAULT NULL,
  `datetime_created` datetime DEFAULT NULL,
  `datetime_modified` datetime DEFAULT NULL,
  `titel` varchar(100) DEFAULT NULL,
  `subtitel` varchar(300) DEFAULT NULL,
  `tekst` mediumtext,
  `tekst_kort` varchar(5000) DEFAULT NULL,
  `tekst_samenvatting` varchar(7000) DEFAULT NULL,
  `tekst_knop` varchar(40) DEFAULT NULL,
  `link_knop` varchar(200) DEFAULT NULL,
  `pubind_intern` varchar(1) DEFAULT NULL,
  `datetime_pub_intern` datetime DEFAULT NULL,
  `pubind_extern` varchar(1) DEFAULT NULL,
  `datetime_pub_extern` datetime DEFAULT NULL,
  `picfile1` varchar(300) DEFAULT NULL,
  `picfile2` varchar(100) DEFAULT NULL,
  `picfile3` varchar(100) DEFAULT NULL,
  `picfile4` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `topics`
--

CREATE TABLE `topics` (
  `id` int(11) NOT NULL,
  `topic_subject` varchar(255) NOT NULL,
  `topic_date` datetime NOT NULL,
  `id_cat` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `nbrPosts` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `id_person` int(11) NOT NULL,
  `username` varchar(128) NOT NULL,
  `password` varchar(40) NOT NULL,
  `password_mod` varchar(1) NOT NULL DEFAULT 'n',
  `activity` datetime NOT NULL,
  `beheerind` char(1) NOT NULL DEFAULT 'n',
  `berichtInd` varchar(1) NOT NULL,
  `berichtSentInd` varchar(1) NOT NULL,
  `forumNoteInd` varchar(1) NOT NULL DEFAULT 'n',
  `id_team` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `werkzkd`
--

CREATE TABLE `werkzkd` (
  `id` int(11) NOT NULL,
  `id_person` int(11) NOT NULL,
  `id_intakeform` int(11) DEFAULT NULL,
  `id_maatje` int(11) DEFAULT NULL,
  `id_jobgroup` int(11) DEFAULT NULL,
  `situatie` mediumtext,
  `opmerkingen` mediumtext,
  `status` char(3) DEFAULT NULL,
  `opties` int(7) DEFAULT NULL,
  `nnind` varchar(1) DEFAULT NULL,
  `startsituatie` varchar(10) DEFAULT NULL,
  `GAKind` varchar(1) DEFAULT NULL,
  `DBBind` varchar(1) NOT NULL DEFAULT 'n',
  `opleiding` varchar(30) DEFAULT NULL,
  `instroomtrede` char(3) DEFAULT NULL,
  `instroomscore` char(3) DEFAULT NULL,
  `uitstroomscore` char(3) DEFAULT NULL,
  `soortwerk` varchar(60) DEFAULT NULL,
  `toelichting` mediumtext,
  `date_aanmelding` date DEFAULT NULL,
  `date_uitstroom` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `werkzkdBU`
--

CREATE TABLE `werkzkdBU` (
  `id` int(11) NOT NULL,
  `id_person` int(11) NOT NULL,
  `id_intakeform` int(11) DEFAULT NULL,
  `id_maatje` int(11) DEFAULT NULL,
  `id_jobgroup` int(11) DEFAULT NULL,
  `situatie` mediumtext,
  `opmerkingen` mediumtext,
  `status` char(3) DEFAULT NULL,
  `opties` int(7) DEFAULT NULL,
  `nnind` varchar(1) DEFAULT NULL,
  `startsituatie` varchar(10) DEFAULT NULL,
  `GAKind` varchar(1) DEFAULT NULL,
  `opleiding` varchar(30) DEFAULT NULL,
  `instroomtrede` char(3) DEFAULT NULL,
  `instroomscore` char(3) DEFAULT NULL,
  `uitstroomscore` char(3) DEFAULT NULL,
  `soortwerk` varchar(60) DEFAULT NULL,
  `toelichting` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `wkzstatus`
--

CREATE TABLE `wkzstatus` (
  `id` int(11) NOT NULL,
  `code` varchar(3) NOT NULL,
  `omschrijving` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `aantekening`
--
ALTER TABLE `aantekening`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `agendaitem`
--
ALTER TABLE `agendaitem`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `enquete`
--
ALTER TABLE `enquete`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `intakeform`
--
ALTER TABLE `intakeform`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jgsessie`
--
ALTER TABLE `jgsessie`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jobgroup`
--
ALTER TABLE `jobgroup`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `keytabel`
--
ALTER TABLE `keytabel`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sleutel` (`sleutel`);

--
-- Indexes for table `locatie`
--
ALTER TABLE `locatie`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `maatje`
--
ALTER TABLE `maatje`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `newsitem`
--
ALTER TABLE `newsitem`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `person`
--
ALTER TABLE `person`
  ADD PRIMARY KEY (`person_id`),
  ADD KEY `achternaam` (`achternaam`),
  ADD KEY `emailadres` (`emailadres`);

--
-- Indexes for table `postit`
--
ALTER TABLE `postit`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `processtap`
--
ALTER TABLE `processtap`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pubquizteams`
--
ALTER TABLE `pubquizteams`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sucstor`
--
ALTER TABLE `sucstor`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `topics`
--
ALTER TABLE `topics`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `werkzkd`
--
ALTER TABLE `werkzkd`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `werkzkdBU`
--
ALTER TABLE `werkzkdBU`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wkzstatus`
--
ALTER TABLE `wkzstatus`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `aantekening`
--
ALTER TABLE `aantekening`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `agendaitem`
--
ALTER TABLE `agendaitem`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enquete`
--
ALTER TABLE `enquete`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `intakeform`
--
ALTER TABLE `intakeform`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jgsessie`
--
ALTER TABLE `jgsessie`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobgroup`
--
ALTER TABLE `jobgroup`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `keytabel`
--
ALTER TABLE `keytabel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `locatie`
--
ALTER TABLE `locatie`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maatje`
--
ALTER TABLE `maatje`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `newsitem`
--
ALTER TABLE `newsitem`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `person`
--
ALTER TABLE `person`
  MODIFY `person_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `postit`
--
ALTER TABLE `postit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `processtap`
--
ALTER TABLE `processtap`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pubquizteams`
--
ALTER TABLE `pubquizteams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sucstor`
--
ALTER TABLE `sucstor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `topics`
--
ALTER TABLE `topics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `werkzkd`
--
ALTER TABLE `werkzkd`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `werkzkdBU`
--
ALTER TABLE `werkzkdBU`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wkzstatus`
--
ALTER TABLE `wkzstatus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
