<?php
/* ------------------------------------------------------------------
 * Eenmalig: alle leesbare wachtwoorden omzetten naar een hash (okt 2026)
 *
 * Voorwaarde: migraties/2026-10-07_wachtwoord-hash.sql is uitgevoerd.
 *
 * Gebruik in de browser (ingelogd als beheerder):
 *   /maintenance/hash_wachtwoorden.php            -> laat alleen zien hoeveel
 *   /maintenance/hash_wachtwoorden.php?uitvoeren  -> zet ze echt om
 * Of op de command line:
 *   php maintenance/hash_wachtwoorden.php [uitvoeren]
 *
 * Het script kan veilig vaker draaien: gebruikers die al een hash
 * hebben, worden overgeslagen.
 * ------------------------------------------------------------------ */
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_cli_or_admin();

$uitvoeren = isset($_GET['uitvoeren']) || in_array('uitvoeren', $argv ?? [], true);
$nl = PHP_SAPI === 'cli' ? "\n" : "<br>\n";

// Bestaat de nieuwe kolom al? Zo niet, dan eerst de migratie draaien.
$kolom = $connection->query("SHOW COLUMNS FROM user LIKE 'password_hash'")->fetch();
if (!$kolom) {
	exit("De kolom password_hash bestaat nog niet. Voer eerst migraties/2026-10-07_wachtwoord-hash.sql uit.$nl");
}

$rijen = $connection->query(
	"SELECT id, username, password FROM user
	 WHERE (password_hash IS NULL OR password_hash = '') AND password <> ''"
)->fetchAll(PDO::FETCH_ASSOC);

echo count($rijen) . " gebruiker(s) met een leesbaar wachtwoord gevonden.$nl";
if (!$uitvoeren) {
	exit("Er is niets gewijzigd. Voeg ?uitvoeren toe aan de URL om ze om te zetten.$nl");
}

// Een transactie: alles of niets. Gaat er halverwege iets mis, dan blijft
// de database zoals hij was (rollBack).
$stmt = $connection->prepare("UPDATE user SET password_hash = :hash, password = '' WHERE id = :id");
$connection->beginTransaction();
try {
	foreach ($rijen as $rij) {
		$stmt->execute([
			':hash' => password_hash($rij['password'], PASSWORD_DEFAULT),
			':id'   => $rij['id'],
		]);
	}
	$connection->commit();
} catch (Throwable $e) {
	$connection->rollBack();
	error_log('hash_wachtwoorden: ' . $e->getMessage());
	exit("Er ging iets mis; er is niets gewijzigd. Zie de error log.$nl");
}
echo count($rijen) . " wachtwoord(en) omgezet naar een hash. De leesbare versies zijn gewist.$nl";
