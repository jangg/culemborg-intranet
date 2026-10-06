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
 * Toegangscontrole (noodreparatie oktober 2026)
 *
 * require_login($minLevel)
 *   Zet je bovenaan een pagina, direct na de include van config.php.
 *   - Niet ingelogd?  Dan naar het inlogscherm en stoppen.
 *   - $minLevel > 0?  Dan moet beheerind (authLevel) minstens zo hoog zijn,
 *     anders krijgt de bezoeker "403 Forbidden" en stopt het script.
 *   Belangrijk: exit() na header() is nodig. header() stuurt alleen een
 *   instructie naar de browser; zonder exit() draait de rest van de
 *   pagina gewoon door (en wordt bijvoorbeeld de PDF tóch gemaakt).
 *
 * require_cli_or_admin()
 *   Voor scripts die door een cronjob (command line) worden gedraaid.
 *   PHP_SAPI is 'cli' als het script via "php bestand.php" draait en
 *   bijvoorbeeld 'fpm-fcgi' als het via de webserver wordt aangeroepen.
 *   Via de browser mag alleen een ingelogde beheerder (niveau 9) het starten.
 * ------------------------------------------------------------------ */
function require_login(int $minLevel = 0): void
{
	if (!isset($_SESSION['userid'])) {
		header('Location: /index.php');
		exit();
	}
	if ($minLevel > 0) {
		include_once __DIR__ . '/class/c_user.php';
		$user = new User('id', $_SESSION['userid']);
		if ((int)$user->authLevel() < $minLevel) {
			http_response_code(403);
			exit('Je hebt geen toegang tot deze pagina.');
		}
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
	require_login(9);
}

?>