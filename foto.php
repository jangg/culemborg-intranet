<?php
/* ------------------------------------------------------------------
 * Persoonsfoto's alleen voor ingelogde gebruikers (okt 2026)
 *
 * De .htaccess in fotoos_person/ en fotoos_wkz/ stuurt elk verzoek
 * hierheen, bijvoorbeeld:  /fotoos_person/jan.jpg
 *                     ->  /foto.php?map=fotoos_person&f=jan.jpg
 * ------------------------------------------------------------------ */
require_once __DIR__ . '/config.php';

// Welke mappen mogen, en welk recht is daarvoor nodig (NULL = alleen ingelogd).
$mappen = [
	'fotoos_person' => NULL,
	'fotoos_wkz'    => 'was_lezen',
];

$map = (string)($_GET['map'] ?? '');
$f   = (string)($_GET['f'] ?? '');

if (!array_key_exists($map, $mappen)) {
	http_response_code(404);
	exit();
}
// Niet ingelogd: geen doorverwijzing naar het inlogscherm (dit is een plaatje),
// maar gewoon "403 Forbidden".
if (!isset($_SESSION['userid']) || ($mappen[$map] !== NULL && !heeft_recht($mappen[$map]))) {
	http_response_code(403);
	exit();
}

// realpath() maakt er een volledig pad van en lost ../ op. Daarna controleren
// we of het bestand echt binnen de map ligt. Zo kan niemand met f=../../config.php
// andere bestanden opvragen.
$basis  = realpath(__DIR__ . '/' . $map);
$bestand = realpath($basis . '/' . $f);
if ($basis === false || $bestand === false || !is_file($bestand)
	|| !str_starts_with($bestand, $basis . DIRECTORY_SEPARATOR)) {
	http_response_code(404);
	exit();
}

// Alleen afbeeldingen doorgeven (geen .htaccess, .php of andere bestanden).
$soorten = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
            'gif' => 'image/gif', 'webp' => 'image/webp'];
$ext = strtolower(pathinfo($bestand, PATHINFO_EXTENSION));
if (!isset($soorten[$ext])) {
	http_response_code(404);
	exit();
}

// De sessie is niet meer nodig; sluiten zodat andere verzoeken niet hoeven te wachten.
session_write_close();

header('Content-Type: ' . $soorten[$ext]);
header('Content-Length: ' . filesize($bestand));
// "private": de browser mag de foto een uur bewaren, een gedeelde proxy niet.
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');
readfile($bestand);
