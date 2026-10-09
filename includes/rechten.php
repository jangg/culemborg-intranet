<?php
/* ------------------------------------------------------------------
 * Rechten — GELIJK voor beide intranetten (Culemborg en Zoetermeer)
 *
 * Per recht staat hier welke beheerind-niveaus (tabel user) het hebben.
 * range(1, 9) betekent: 1, 2, 3, 4, 5, 6, 7, 8 en 9.
 * Dit bestand moet in beide sites gelijk blijven (vergelijk.sh controleert dat).
 * Nieuw recht nodig? Voeg hier een regel toe en gebruik
 * heeft_recht('naam') of require_recht('naam').
 *
 * Niveaus (besluit Jan, 9 oktober 2026):
 *   0 = wel inloggen, geen forum
 *   1 = buitenstaander: forum lezen
 *   2 = maatje: 1 + forum schrijven
 *   3 = 2 + redactie
 *   4 = 2 + WAS lezen
 *   5 = 2 + WAS lezen en schrijven
 *   6 = 5 + maatjes wijzigen/verwijderen
 *   7 = redactie + WAS lezen            (3 + 4)
 *   8 = redactie + WAS lezen en schrijven (3 + 5)
 *   9 = alles (beheerder)
 * ------------------------------------------------------------------ */
define('RECHTEN', [
	'forum_lezen'       => range(1, 9),
	'forum_schrijven'   => range(2, 9),
	'redactie'          => [3, 7, 8, 9],
	'was_lezen'         => [4, 5, 6, 7, 8, 9],
	'was_wijzigen'      => [5, 6, 8, 9],
	'maatjes_wijzigen'  => [6, 9],
	'presentie_anderen' => [6, 9],      // aanmelden van anderen voor maatjesavonden (pages/presence_list.php)
	'beheerder'         => [9],
]);
