<?php
/* ------------------------------------------------------------------
 * Rechten (nov 2026: rechten per gebruiker)
 *
 * Elke gebruiker heeft zijn eigen lijst van rechten (tabel user_recht,
 * één regel per recht). Het Systeem-scherm (systeem.php, alleen voor de
 * beheerder) toont deze lijst als vinkjes.
 *
 * RECHTEN    alle rechten die er zijn, met een omschrijving voor het scherm.
 *            Nieuw recht nodig? Voeg hier een regel toe. Er verschijnt dan
 *            vanzelf een vinkje op het Systeem-scherm; aan de database
 *            verandert niets. Gebruik het in de code met
 *            heeft_recht('naam') of require_recht('naam') (zie config.php).
 * VEREIST    rechten die niet zonder een ander recht kunnen. Wie het eerste
 *            krijgt, krijgt het tweede er automatisch bij.
 * PROFIELEN  vaste combinaties. Op het Systeem-scherm zet één klik de vinkjes;
 *            daarna kun je per persoon nog afwijken. Een profiel wordt niet
 *            opgeslagen: alleen de vinkjes tellen.
 *
 * Het recht 'beheerder' staat niet als vinkje op het scherm: de beheerder
 * mag automatisch alles, en zijn account kan daar niet worden gewijzigd of
 * geblokkeerd. Beheerder maken kan alleen in phpMyAdmin (tabel user_recht).
 *
 * Tot 6 november 2026 gold hier een niveau (user.beheerind, 0-9). Hoe die
 * niveaus zijn omgezet: migraties/2026-11-06_rechten-per-gebruiker_jhmz.sql.
 * ------------------------------------------------------------------ */
define('RECHTEN', [
	// naam                       => [groep, omschrijving op het Systeem-scherm]
	'forum_lezen'              => ['Forum',      'Forum lezen'],
	'forum_schrijven'          => ['Forum',      'Forum: berichten schrijven'],
	'redactie'                 => ['Redactie',   'Redactie: nieuws, agenda, postits'],
	'was_lezen'                => ['WAS',        'WAS lezen'],
	'was_wijzigen'             => ['WAS',        'WAS wijzigen (werkzoekenden, intakes)'],
	'maatjes_wijzigen'         => ['WAS',        'Maatjes wijzigen en verwijderen'],
	'presentie_anderen'        => ['WAS',        'Anderen aanmelden voor maatjesavonden'],
	'documenten_gedeeld'       => ['Documenten', 'A. Gedeelde documenten'],
	'documenten_eigen'         => ['Documenten', 'B. Documenten van de eigen werkzoekenden'],
	'documenten_werkzoekenden' => ['Documenten', 'C. Documenten van alle werkzoekenden'],
	'beheerder'                => ['Beheer',     'Beheerder: mag alles'],
]);

define('VEREIST', [
	'forum_schrijven'  => ['forum_lezen'],
	'was_wijzigen'     => ['was_lezen'],
	'maatjes_wijzigen' => ['was_lezen', 'was_wijzigen'],
]);

define('PROFIELEN', [
	'Buitenstaander' => ['forum_lezen'],
	'Maatje'         => ['forum_lezen', 'forum_schrijven', 'documenten_eigen'],
	'Redactie'       => ['forum_lezen', 'forum_schrijven', 'documenten_eigen', 'redactie'],
	'Coördinator'    => ['forum_lezen', 'forum_schrijven', 'documenten_eigen', 'was_lezen', 'was_wijzigen',
	                     'documenten_gedeeld', 'documenten_werkzoekenden'],
	'Coördinator + maatjes' => ['forum_lezen', 'forum_schrijven', 'documenten_eigen', 'was_lezen', 'was_wijzigen',
	                     'documenten_gedeeld', 'documenten_werkzoekenden', 'maatjes_wijzigen', 'presentie_anderen'],
]);
