<?php
/* een sessie blijft hoogstens 4 uur in de lucht. Daarna moet er opnieuw worden ingelogd */
ini_set("session.cookie_lifetime","14400");
/** Session hardening (zet dit liefst in config.php vóór session_start) */
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
	ini_set('session.cookie_secure', '1');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
if (isset($_SESSION['userid'])) {
	// idle time, na 30 minuten wordt de sessie afgebroken en moet opnieuw ingelogd worden.
	$ttl = 30 * 60; // 30 min
	if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > $ttl) {
		session_unset();
		session_destroy();
		header('Location: /index.php');
		exit();
	}
	$_SESSION['last_activity'] = time();
}

include ('includes/configDB.php');
include_once ('class/c_tools.php');
error_reporting(E_ALL);
setlocale(LC_ALL, 'nl_NL');

function openDB ()
{
	global $connection;
	if (!is_a($connection, 'PDO'))
	{
		$connection = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'') );
		$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		// error_log('Het openen van de database lukt wel!');	
	}
}
openDB();

/* ------------------------------------------------------------------
 * Toegangscontrole
 *
 * Welk beheerind-niveau welk recht geeft, verschilt per site. Dat staat
 * daarom niet hier, maar in includes/rechten.php (één bestand per site).
 * Dit bestand (config.php) is in beide sites gelijk.
 *
 * require_login()
 *   Niet ingelogd? Dan naar het inlogscherm en stoppen.
 *   Let op: exit() na header() is nodig. header() stuurt alleen een
 *   instructie naar de browser; zonder exit() draait de rest van de
 *   pagina gewoon door.
 *
 * heeft_recht('naam')
 *   Geeft true of false. Handig om een knop wel of niet te tonen.
 *
 * require_recht('naam')
 *   Zet je bovenaan een pagina of bovenaan een stuk code dat iets opslaat.
 *   Geen recht? Dan "403 Forbidden" en het script stopt.
 *   Een knop verbergen is niet genoeg: iemand kan het formulier ook
 *   zonder knop versturen. Daarom controleert de server het nog een keer.
 *
 * require_cli_or_admin()
 *   Voor scripts die door een cronjob worden gedraaid. Via de browser mag
 *   alleen iemand met het recht 'beheerder' het starten.
 * ------------------------------------------------------------------ */
include_once __DIR__ . '/includes/rechten.php';

function require_login(): void
{
	if (!isset($_SESSION['userid'])) {
		header('Location: /index.php');
		exit();
	}
}

function huidig_niveau(): int
{
	// "static" onthoudt de waarde tussen aanroepen binnen hetzelfde verzoek.
	// Zo wordt de gebruiker maar één keer uit de database gehaald, ook als
	// heeft_recht() op een pagina tien keer wordt aangeroepen.
	static $niveau = null;
	if ($niveau === null) {
		if (!isset($_SESSION['userid'])) {
			return -1;   // niet ingelogd: geen enkel recht
		}
		include_once __DIR__ . '/class/c_user.php';
		$user   = new User('id', $_SESSION['userid']);
		$niveau = (int)$user->authLevel();
	}
	return $niveau;
}

function heeft_recht(string $recht): bool
{
	if (!isset(RECHTEN[$recht])) {
		// Tikfout in de naam? Dan liever geen toegang dan wel, en een melding in de log.
		error_log("heeft_recht: onbekend recht '$recht'");
		return false;
	}
	// true als derde argument: vergelijk ook het type (int met int).
	return in_array(huidig_niveau(), RECHTEN[$recht], true);
}

function require_recht(string $recht): void
{
	require_login();
	if (!heeft_recht($recht)) {
		http_response_code(403);
		exit('Je hebt geen toegang tot deze pagina.');
	}
}

function require_cli_or_admin(): void
{
	// Een webverzoek heeft altijd een REQUEST_METHOD (GET, POST, ...).
	// Een cronjob niet, ook niet als de server daarvoor de CGI-versie van
	// PHP gebruikt (dan is PHP_SAPI 'cgi-fcgi' in plaats van 'cli').
	// Een bezoeker kan REQUEST_METHOD niet weglaten, dus dit is veilig.
	if (PHP_SAPI === 'cli' || !isset($_SERVER['REQUEST_METHOD'])) {
		return;
	}
	require_recht('beheerder');
}

?>