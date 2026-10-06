<?php
/* ------------------------------------------------------------------
 * Rechten voor JobHulp Culemborg
 *
 * Per recht staat hier welke beheerind-niveaus het hebben.
 * range(1, 9) betekent: 1, 2, 3, 4, 5, 6, 7, 8 en 9.
 * Dit bestand verschilt per site; config.php, navbar.php en de pagina's niet.
 * Nieuw recht nodig? Voeg hier een regel toe (in BEIDE sites) en gebruik
 * heeft_recht('naam') of require_recht('naam').
 * ------------------------------------------------------------------ */
define('RECHTEN', [
	'forum_lezen'       => range(1, 9),
	'forum_schrijven'   => range(2, 9),
	'redactie'          => [4, 6, 7, 8, 9],
	'was_lezen'         => range(5, 9),
	'was_wijzigen'      => range(5, 9),
	'maatjes_wijzigen'  => [7, 8, 9],
	'presentie_anderen' => [7, 8, 9],   // aanwezigheid van anderen aan/uit zetten
	'beheerder'         => [9],
]);
