<?php
/* een sessie blijft hoogstens 4 uur in de lucht. Daarna moet er opnieuw worden ingelogd */
ini_set("session.cookie_lifetime","14400");
/** Session hardening (zet dit liefst in config.php vóór session_start) */
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
	ini_set('session.cookie_secure', '1');
}

// SameSite=Lax: de browser stuurt de sessie-cookie niet mee met POST-verzoeken
// die vanaf een ándere website komen. Extra slot op de deur tegen CSRF (zie onder).
ini_set('session.cookie_samesite', 'Lax');

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
// Nederlandse tijd voor date(), DateTime e.d., ongeacht wat er in php.ini staat
// (MAMP gebruikte UTC: in het logboek van cleanDB.php stond de tijd een uur te vroeg).
date_default_timezone_set('Europe/Amsterdam');

function openDB ()
{
	global $connection;
	if (!is_a($connection, 'PDO'))
	{
		// utf8mb4 via de DSN (charset=...) in plaats van PDO::MYSQL_ATTR_INIT_COMMAND:
		// die constante is verouderd in PHP 8.5. Dit werkt in elke PHP-versie hetzelfde.
		// Een eventuele andere charset in DB_DSN (bijv. utf8) wordt vervangen door utf8mb4.
		$dsn = preg_replace('/;?charset=[^;]*/i', '', DB_DSN) . ';charset=utf8mb4';
		$connection = new PDO( $dsn, DB_USERNAME, DB_PASSWORD );
		$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		// error_log('Het openen van de database lukt wel!');	
	}
}
openDB();

/* ------------------------------------------------------------------
 * Toegangscontrole
 *
 * Welk beheerind-niveau welk recht geeft, staat in includes/rechten.php.
 * Dat bestand en dit bestand (config.php) zijn in beide sites gelijk.
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

function h($waarde): string
{
	// h() = "html-veilig maken". Gebruik dit voor ELKE waarde uit de database of
	// uit een formulier die in de HTML komt, bijvoorbeeld:  echo h($wkz->voornaam);
	// htmlspecialchars() zet < > & " ' om in &lt; &gt; &amp; &quot; &#039;, zodat de
	// browser ze als tekst toont en niet als HTML of JavaScript uitvoert.
	// ENT_SUBSTITUTE: ongeldige tekens (bijv. latin1 in een utf8-pagina) worden
	// vervangen in plaats van dat de hele tekst leeg wordt.
	// (string)($waarde ?? ''): NULL en getallen worden netjes tekst.
	// false (double_encode): codes die al in de database staan, zoals &eacute; in
	// "Ren&eacute;", blijven staan en de browser toont gewoon é. Zonder deze
	// parameter werd het &amp;eacute; en zag je de code zelf.
	// < > " ' worden nog steeds omgezet, dus dit is net zo veilig.
	return htmlspecialchars((string)($waarde ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
}

function knop_uit(string $recht): string
{
	// Voor knoppen die iets wijzigen, bijvoorbeeld in HTML:
	//   <button + (echo knop_uit('was_wijzigen')) + name="opslaan" ...>
	// Let op: een vraagteken met > erachter mag niet in een //-commentaar staan,
	// want PHP ziet dat als einde van de PHP-code, ook binnen commentaar.
	// Zonder het recht wordt de knop uitgeschakeld (grijs, niet klikbaar).
	// De server controleert het daarna nog een keer met require_recht().
	return heeft_recht($recht) ? '' : ' disabled';
}

/* ------------------------------------------------------------------
 * CSRF-bescherming (okt 2026)
 *
 * CSRF = een andere website laat jouw browser een formulier naar het
 * intranet sturen. De browser stuurt dan je sessie-cookie mee, dus het
 * intranet denkt dat jij het bent. Bescherming: elk formulier bevat een
 * geheim token dat alleen het intranet zelf kent (het staat in de sessie).
 * Een andere website kan dat token niet lezen en dus niet meesturen.
 *
 * csrf_token()  het token van deze sessie (wordt de eerste keer gemaakt)
 * csrf_veld()   het verborgen invoerveld; in elk formulier met method="POST":
 *                   echo csrf_veld();
 * Bij AJAX stuurt jQuery het token mee in de kop "X-CSRF-Token"
 * (zie includes/head.php).
 * De controle zelf staat onderaan dit bestand en gebeurt bij ELKE POST
 * automatisch, zodat je hem nergens kunt vergeten.
 * ------------------------------------------------------------------ */
function csrf_token(): string
{
	if (empty($_SESSION['csrf_token'])) {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	}
	return $_SESSION['csrf_token'];
}

function csrf_veld(): string
{
	return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function verwijderknop(string $actie, array $velden, string $vraag): string
{
	// Een prullenbak-knop als klein POST-formulier, in plaats van een link als
	// "?del=j". Een link (GET) kan door een andere website worden "aangeklikt",
	// bijvoorbeeld via <img src="...?del=j">, en kan geen CSRF-token meesturen.
	// json_encode() maakt van $vraag een geldige JavaScript-tekst (met aanhalingstekens);
	// h() maakt die daarna veilig voor in het HTML-attribuut onsubmit="...".
	$html = '<form method="POST" action="' . h($actie) . '" style="display: inline;"'
		  . ' onsubmit="return confirm(' . h(json_encode($vraag)) . ');">' . csrf_veld();
	foreach ($velden as $naam => $waarde) {
		$html .= '<input type="hidden" name="' . h($naam) . '" value="' . h($waarde) . '">';
	}
	// font-size en line-height: 1 houden de knop klein, zodat tabelregels niet hoger worden.
	return $html . '<button type="submit" class="btn btn-link p-0 m-0 align-baseline" title="Verwijderen"'
		 . ' style="font-size: 0.85rem; line-height: 1;">'
		 . '<i class="fa-regular fa-trash-can"></i></button></form>';
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

/* ------------------------------------------------------------------
 * Automatische CSRF-controle bij elk POST-verzoek.
 * Het token komt uit het formulier ($_POST['csrf_token']) of, bij AJAX,
 * uit de kop X-CSRF-Token (PHP maakt daar $_SERVER['HTTP_X_CSRF_TOKEN'] van).
 * hash_equals() vergelijkt in constante tijd (zie ook verifyPassword()).
 * Cronjobs (command line) hebben geen POST en vallen er dus buiten.
 * ------------------------------------------------------------------ */
if (PHP_SAPI !== 'cli' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
	$meegestuurd = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
	if (empty($_SESSION['csrf_token']) || !is_string($meegestuurd)
		|| !hash_equals($_SESSION['csrf_token'], $meegestuurd)) {
		http_response_code(403);
		exit('<!DOCTYPE html><html lang="nl"><head><meta charset="utf-8"><title>Formulier verlopen</title></head>'
			. '<body style="font-family: sans-serif; margin: 3em;"><h2>Het formulier kon niet worden verwerkt</h2>'
			. '<p>Waarschijnlijk was de pagina te lang open of is je sessie verlopen '
			. '(of was een bijlage te groot). Er is niets opgeslagen.</p>'
			. '<p><a href="javascript:history.back()">Ga terug</a>, laad de pagina opnieuw en probeer het nog eens.</p></body></html>');
	}
}
