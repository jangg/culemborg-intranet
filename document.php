<?php
/* ------------------------------------------------------------------
 * Eén document downloaden of bekijken (okt 2026)
 *
 *   document.php?id=12            downloaden
 *   document.php?id=12&bekijk=1   in de browser openen (alleen pdf en foto's)
 *
 * De bestanden staan buiten public_html (DOC_DIR); alleen dit script kan
 * ze ophalen, en pas nadat gecontroleerd is of je het document mag zien.
 * Werkt zoals foto.php voor de pasfoto's.
 * ------------------------------------------------------------------ */
require_once __DIR__ . '/config.php';
include_once __DIR__ . '/class/c_documenten.php';

if (!isset($_SESSION['userid'])) {
	header('Location: /index.php');
	exit();
}
$doc = Document::zoek((int)($_GET['id'] ?? 0));
$eigenaar = ($doc && $doc['eigenaar_id'] !== null) ? (int)$doc['eigenaar_id'] : null;

// Bestaat niet, of je mag het niet zien: in beide gevallen "niet gevonden",
// zodat niemand kan uitproberen welke nummers bestaan.
if (!$doc || !Document::magRuimte($doc['ruimte'], $eigenaar, (int)$_SESSION['userid'])) {
	http_response_code(404);
	exit('Dit document bestaat niet (meer).');
}
$pad = Document::pad($doc);
if ($pad === null || !is_file($pad)) {
	error_log('document.php: bestand ontbreekt voor document ' . $doc['id']);
	http_response_code(404);
	exit('Het bestand van dit document is niet gevonden. Meld dit aan de beheerder.');
}

// Alleen pdf en foto's mogen in de browser open; al het andere wordt gedownload.
$bekijkbaar = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
$bekijk = !empty($_GET['bekijk']) && isset($bekijkbaar[$doc['extensie']]) && $doc['mime'] === $bekijkbaar[$doc['extensie']];

session_write_close();		// sessie niet meer nodig; andere pagina's hoeven niet te wachten

// Bestandsnaam in de kop: een eenvoudige versie (zonder é en dergelijke) voor oude
// browsers, en de echte naam via filename* (RFC 5987) voor alle moderne browsers.
$naam    = $doc['naam'];
$simpel  = preg_replace('/[^A-Za-z0-9._ -]/u', '_', $naam);	// /u: é telt als één teken
header('Content-Type: ' . ($bekijk ? $bekijkbaar[$doc['extensie']] : 'application/octet-stream'));
header('Content-Length: ' . filesize($pad));
header('Content-Disposition: ' . ($bekijk ? 'inline' : 'attachment')
	. '; filename="' . $simpel . '"; filename*=UTF-8\'\'' . rawurlencode($naam));
header('X-Content-Type-Options: nosniff');			// browser mag de soort niet zelf gaan raden
header('Cache-Control: private, no-store');			// niet bewaren op gedeelde computers
readfile($pad);
