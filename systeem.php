<?php
/* ------------------------------------------------------------------
 * Systeem (nov 2026) — gebruikers beheren, alleen voor de beheerder
 *
 *  - Nieuwe gebruiker: kies een maatje dat nog geen account heeft, geef een
 *    inlognaam en kies een profiel. Er komt een tijdelijk wachtwoord op het
 *    scherm (één keer); bij de eerste keer inloggen moet hij dat wijzigen.
 *  - Wijzigen (?id=12): rechten (vinkjes, met profielknoppen), meldingen
 *    voor forum en berichten, een nieuw wachtwoord, blokkeren.
 *  - Blokkeren = person.delind wordt 'j' (zoals "maatje verwijderen" in het
 *    WAS). Hij kan dan niet meer inloggen en verdwijnt uit de lijsten.
 *    Deblokkeren zet het terug. Documenten en forumberichten blijven staan.
 *
 * Vaste regels (besluit Jan, 6 nov 2026):
 *  - Alleen een maatje kan gebruiker worden (eerst het maatje aanmaken in het WAS).
 *  - De inlognaam wordt hier gemaakt en kan daarna niet meer worden gewijzigd.
 *  - Een beheerdersaccount kan hier NOOIT worden geblokkeerd, en zijn rechten
 *    kunnen hier niet worden gewijzigd. Het vinkje "beheerder" bestaat hier
 *    daarom niet; beheerder maken kan alleen in phpMyAdmin.
 *
 * Welke rechten en profielen er zijn: includes/rechten.php.
 * Alles wat iets wijzigt, gaat via POST en daarna een doorverwijzing terug
 * (Post/Redirect/Get, zoals in documenten.php). De melding gaat via de sessie mee.
 * ------------------------------------------------------------------ */
require_once('config.php');
include_once('class/c_user.php');
include_once('class/c_maatje.php');

require_recht('beheerder');
$curr_user = new User('id', $_SESSION['userid']);
$mijnId    = (int)$_SESSION['userid'];

function melding (string $soort, string $tekst): void
{
	$_SESSION['sys_melding'][] = [$soort, $tekst];
}

function terug (?int $id = null): void
{
	header('Location: systeem.php' . ($id ? '?id=' . $id : ''));
	exit();
}

/* Een leesbaar tijdelijk wachtwoord, bijvoorbeeld "k7pm-x3qa-9fhd".
   Zonder tekens die op elkaar lijken (0/o, 1/l/i), zodat het makkelijk
   is over te nemen. random_int() is cryptografisch veilig. */
function tijdelijkWachtwoord (): string
{
	$tekens = 'abcdefghjkmnpqrstuvwxyz23456789';
	$groepen = [];
	for ($g = 0; $g < 3; $g++) {
		$groep = '';
		for ($i = 0; $i < 4; $i++) $groep .= $tekens[random_int(0, strlen($tekens) - 1)];
		$groepen[] = $groep;
	}
	return implode('-', $groepen);
}

/* De rechten uit het formulier: alleen bestaande rechten, nooit 'beheerder',
   en wat een recht VEREIST komt er automatisch bij (zie includes/rechten.php). */
function rechtenUitFormulier (array $invoer): array
{
	$rechten = array_values(array_filter($invoer, fn($r) => is_string($r) && isset(RECHTEN[$r]) && $r !== 'beheerder'));
	foreach ($rechten as $r) {
		foreach (VEREIST[$r] ?? [] as $nodig) $rechten[] = $nodig;
	}
	$rechten = array_values(array_unique($rechten));
	sort($rechten);
	return $rechten;
}

/* Welk profiel past precies bij deze rechten? Anders "aangepast". */
function profielVan (array $rechten): string
{
	if (in_array('beheerder', $rechten, true)) return 'Beheerder';
	if (!$rechten) return 'Geen rechten';
	sort($rechten);
	foreach (PROFIELEN as $naam => $profiel) {
		sort($profiel);
		if ($profiel === $rechten) return $naam;
	}
	return 'Aangepast';
}

/* Dezelfde mail als bij "wachtwoord vergeten" (vergeten.php), met een
   eenmalige link die 1 uur geldig is. */
function stuurResetMail (User $user, string $token): void
{
	$link = LOC_WEBSITE . '/reset.php?token=' . $token;
	$mail_body = '<html><head></head><body><p>
	Beste ' . h($user->voornaam) . ',<br/><br/>
	De beheerder van het intranet van ' . h(LOC_NAME) . ' heeft voor jou een link gemaakt om een (nieuw) wachtwoord in te stellen.<br/>
	Je gebruikersnaam is: <b>' . h($user->username) . '</b><br/><br/>
	Klik op deze link om je wachtwoord in te stellen:<br/>
	<a href="' . h($link) . '">' . h($link) . '</a><br/><br/>
	De link is 1 uur geldig en werkt één keer. Is hij verlopen, gebruik dan "Wachtwoord vergeten" op de inlogpagina.
	</p></body></html>';
	Tools::MailRoom($user->voornaam, $user->emailadres, 'Wachtwoord voor het intranet', $mail_body);
	Tools::closeMailer();
}

/* De gebruiker uit het formulier, ook als hij geblokkeerd is ('histid'). */
function gekozenGebruiker (): ?User
{
	$id = (int)($_POST['id'] ?? 0);
	if ($id <= 0) return null;
	$user = new User('histid', $id);
	return $user->id ? $user : null;
}

/* ================================================================
 * Verwerken (POST)
 * ================================================================ */
$actie = (string)($_POST['actie'] ?? '');

if ($actie === 'toevoegen') {
	$personId = (int)($_POST['person_id'] ?? 0);
	$username = trim((string)($_POST['username'] ?? ''));
	$profiel  = (string)($_POST['profiel'] ?? '');
	$maatjes  = array_column(Maatje::zonderGebruiker(), null, 'person_id');	// [person_id => maatje]

	if (!isset($maatjes[$personId])) {
		melding('danger', 'Kies een maatje dat nog geen account heeft.');
	} elseif (!preg_match('/^[A-Za-z0-9._@-]{3,60}$/', $username)) {
		melding('danger', 'De inlognaam moet 3 tot 60 tekens lang zijn: letters, cijfers en . _ - @ (geen spaties).');
	} elseif (User::usernameBestaat($username)) {
		melding('danger', 'De inlognaam "' . $username . '" bestaat al (misschien bij een geblokkeerde gebruiker). Kies een andere.');
	} elseif (!isset(PROFIELEN[$profiel])) {
		melding('danger', 'Kies een profiel.');
	} else {
		$wachtwoord = tijdelijkWachtwoord();
		$nieuwId = User::maak($personId, $username, $wachtwoord);
		if ($nieuwId === null) {
			melding('danger', 'Het aanmaken is mislukt. Kijk in de foutenlog van PHP.');
		} else {
			$nieuw = new User('histid', $nieuwId);
			$nieuw->slaRechtenOp(rechtenUitFormulier(PROFIELEN[$profiel]));
			$_SESSION['sys_wachtwoord'] = [$nieuwId, $username, $wachtwoord];
			melding('success', 'De gebruiker "' . $username . '" is aangemaakt met het profiel ' . $profiel . '.');
			terug($nieuwId);
		}
	}
	terug();
}

if (in_array($actie, ['opslaan', 'wachtwoord', 'link', 'blokkeren', 'deblokkeren'], true)) {
	$user = gekozenGebruiker();
	if ($user === null) {
		melding('danger', 'Deze gebruiker bestaat niet.');
		terug();
	}
	$id = (int)$user->id;
	$isBeheerder = $user->isBeheerder();

	switch ($actie) {
		case 'opslaan':
			// Meldingen per mail: altijd te wijzigen, ook bij de beheerder
			$user->forumNoteInd = isset($_POST['forumNoteInd']) ? 'j' : 'n';
			$user->berichtInd   = isset($_POST['berichtInd']) ? 'j' : 'n';
			$user->updateToDB();
			if ($isBeheerder) {
				melding('success', 'De meldingen zijn opgeslagen. De rechten van een beheerder worden hier niet gewijzigd.');
			} elseif ($user->slaRechtenOp(rechtenUitFormulier((array)($_POST['rechten'] ?? [])))) {
				melding('success', 'De gegevens van "' . $user->username . '" zijn opgeslagen.');
			} else {
				melding('danger', 'De rechten konden niet worden opgeslagen. Er is niets veranderd aan de rechten.');
			}
			break;

		case 'wachtwoord':	// tijdelijk wachtwoord, één keer op het scherm
			if ($isBeheerder) { melding('warning', 'Een beheerder wijzigt zijn wachtwoord zelf (Ingelogd als > Wijzig wachtwoord).'); break; }
			$wachtwoord = tijdelijkWachtwoord();
			$user->setPassword($wachtwoord);
			$user->password_mod = 'j';		// bij de volgende keer inloggen zelf wijzigen
			$user->wisResetToken();			// een eerder gestuurde link werkt niet meer
			$user->updateToDB();
			$_SESSION['sys_wachtwoord'] = [$id, $user->username, $wachtwoord];
			break;

		case 'link':		// mail met een link om zelf een wachtwoord in te stellen
			if ($isBeheerder) { melding('warning', 'Een beheerder wijzigt zijn wachtwoord zelf (Ingelogd als > Wijzig wachtwoord).'); break; }
			if ($user->delind !== 'n') { melding('warning', 'Deze gebruiker is geblokkeerd. Deblokkeer hem eerst.'); break; }
			if (!filter_var((string)$user->emailadres, FILTER_VALIDATE_EMAIL)) {
				melding('danger', 'Er is geen geldig e-mailadres bekend. Vul het in bij het maatje in het WAS.');
				break;
			}
			$token = $user->maakResetToken();
			$user->updateToDB();
			stuurResetMail($user, $token);
			melding('success', 'Er is een mail met een link gestuurd naar ' . $user->emailadres . '. De link is 1 uur geldig.');
			break;

		case 'blokkeren':
			if ($isBeheerder || $id === $mijnId) { melding('danger', 'Een beheerdersaccount kan niet worden geblokkeerd.'); break; }
			$user->delind = 'j';
			$user->id_user_modified = $mijnId;
			$user->updateToDB();
			melding('success', '"' . $user->username . '" is geblokkeerd en kan niet meer inloggen. Het maatje staat ook niet meer in het WAS.');
			break;

		case 'deblokkeren':
			$user->delind = 'n';
			$user->id_user_modified = $mijnId;
			$user->updateToDB();
			melding('success', '"' . $user->username . '" kan weer inloggen en staat weer in het WAS.');
			break;
	}
	terug($id);
}

/* ================================================================
 * Gegevens voor het scherm
 * ================================================================ */
$gebruikers = User::lijstSysteem();
$zonderAcc  = Maatje::zonderGebruiker();
$meldingen  = $_SESSION['sys_melding'] ?? [];
unset($_SESSION['sys_melding']);
$nieuwWw    = $_SESSION['sys_wachtwoord'] ?? null;	// [id, inlognaam, wachtwoord]: alleen deze ene keer tonen
unset($_SESSION['sys_wachtwoord']);

$bewerk = null;
if (isset($_GET['id'])) {
	$bewerk = new User('histid', (int)$_GET['id']);
	if (!$bewerk->id) $bewerk = null;
}
$bewerkRechten = $bewerk ? $bewerk->rechten() : [];
$bewerkBeheerder = $bewerk ? $bewerk->isBeheerder() : false;

// De rechten per groep, voor de vinkjes (zonder 'beheerder')
$groepen = [];
foreach (RECHTEN as $rnaam => [$groep, $omschrijving]) {
	if ($rnaam !== 'beheerder') $groepen[$groep][$rnaam] = $omschrijving;
}
/* Geblokkeerd = person.delind is niet 'n'. (Ook een lege delind telt als
   geblokkeerd: zo iemand kan niet inloggen, zie User::readUserWithId().) */
$geblokkeerd = fn($delind) => $delind !== 'n';
?>
<!DOCTYPE html>
<html lang="nl-NL">
<head>
	<?php include('includes/head.php'); ?>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-table@1.27.1/dist/bootstrap-table.min.css">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.27.1/dist/bootstrap-table.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.27.1/dist/locale/bootstrap-table-nl-NL.min.js"></script>
	<style>
		.bluefont { color: #304280; font-weight: 300; }
		#gebruikers td { padding: .15rem .35rem; line-height: 1.3rem; font-size: .875rem; vertical-align: middle; }
		#gebruikers tr.geblokkeerd td { color: #999; }
		.rechtengroep { border: 1px solid #dee2e6; border-radius: 6px; padding: .5rem .8rem; margin-bottom: .6rem; }
		.rechtengroep h6 { margin-bottom: .3rem; color: #304280; }
		.wachtwoord { font-family: Cousine, monospace; font-size: 1.4rem; letter-spacing: .05em; }
		.knoppen form { display: inline; }
	</style>
</head>
<body class="bodystyle">
<?php include('includes/navbar.php'); ?>
<main role="main">
<div class="container mb-5">
	<h1 class="display-4 bluefont p-3 mt-md-5">Systeem</h1>

	<?php foreach ($meldingen as [$soort, $tekst]): ?>
		<div class="alert alert-<?= h($soort) ?>"><?= h($tekst) ?></div>
	<?php endforeach; ?>

	<?php if ($nieuwWw): ?>
		<div class="alert alert-info">
			Tijdelijk wachtwoord voor <strong><?= h($nieuwWw[1]) ?></strong>:<br>
			<span class="wachtwoord"><?= h($nieuwWw[2]) ?></span><br>
			Geef dit door (liefst niet in dezelfde mail als de inlognaam). Het wordt maar <strong>één keer</strong> getoond.
			Bij de eerste keer inloggen moet hij zelf een nieuw wachtwoord kiezen.
		</div>
	<?php endif; ?>

<?php if ($bewerk): /* ====================== WIJZIGEN ====================== */ ?>
	<div class="card mb-4">
		<div class="card-header d-flex justify-content-between align-items-center">
			<strong><?= h(trim($bewerk->voornaam . ' ' . $bewerk->tussenvoegsels . ' ' . $bewerk->achternaam)) ?></strong>
			<a href="systeem.php"><i class="fa-solid fa-xmark"></i> Sluiten</a>
		</div>
		<div class="card-body">
			<p class="mb-3">
				Inlognaam: <strong><?= h($bewerk->username) ?></strong> (kan niet worden gewijzigd)<br>
				E-mail: <?= h($bewerk->emailadres) ?: '<span class="text-danger">onbekend</span>' ?> <small class="text-muted">(wijzigen bij het maatje in het WAS)</small><br>
				Laatst actief: <?= h(Tools::ConvertTS($bewerk->activity)) ?>
				<?php if ($bewerk->password_mod === 'j'): ?><br><span class="badge badge-warning">moet wachtwoord nog wijzigen</span><?php endif; ?>
				<?php if ($geblokkeerd($bewerk->delind)): ?><br><span class="badge badge-secondary">geblokkeerd</span><?php endif; ?>
				<?php if ($bewerkBeheerder): ?><br><span class="badge badge-danger">beheerder</span><?php endif; ?>
			</p>

			<form method="POST" action="systeem.php" id="rechtenform">
				<?= csrf_veld() ?>
				<input type="hidden" name="id" value="<?= (int)$bewerk->id ?>">

				<h5>Meldingen per mail</h5>
				<div class="form-check">
					<input class="form-check-input" type="checkbox" name="forumNoteInd" id="forumNoteInd" <?= $bewerk->forumNoteInd === 'j' ? 'checked' : '' ?>>
					<label class="form-check-label" for="forumNoteInd">Mail bij nieuwe forumberichten (ForumNoteInd)</label>
				</div>
				<div class="form-check mb-3">
					<input class="form-check-input" type="checkbox" name="berichtInd" id="berichtInd" <?= $bewerk->berichtInd === 'j' ? 'checked' : '' ?>>
					<label class="form-check-label" for="berichtInd">Berichten ontvangen (BerichtInd)</label>
				</div>

				<h5>Rechten</h5>
				<?php if ($bewerkBeheerder): ?>
					<p class="text-muted">Dit is een beheerder: die mag alles. Zijn rechten worden hier niet gewijzigd, en hij kan niet worden geblokkeerd.</p>
				<?php else: ?>
					<p class="mb-2">Profiel:
						<?php foreach (PROFIELEN as $pnaam => $prechten): ?>
							<button type="button" class="btn btn-sm btn-outline-primary mb-1 profiel" data-rechten="<?= h(json_encode($prechten)) ?>"><?= h($pnaam) ?></button>
						<?php endforeach; ?>
						<button type="button" class="btn btn-sm btn-outline-secondary mb-1 profiel" data-rechten="[]">Niets</button>
						<br><small class="text-muted">Een profiel zet alleen de vinkjes. Daarna kun je nog afwijken. Pas met Opslaan wordt het bewaard.</small>
					</p>
					<div class="row">
					<?php foreach ($groepen as $groep => $rechten): ?>
						<div class="col-md-6"><div class="rechtengroep">
							<h6><?= h($groep) ?></h6>
							<?php foreach ($rechten as $rnaam => $omschrijving): ?>
							<div class="form-check">
								<input class="form-check-input recht" type="checkbox" name="rechten[]" value="<?= h($rnaam) ?>" id="r_<?= h($rnaam) ?>"
									data-vereist="<?= h(json_encode(VEREIST[$rnaam] ?? [])) ?>" <?= in_array($rnaam, $bewerkRechten, true) ? 'checked' : '' ?>>
								<label class="form-check-label" for="r_<?= h($rnaam) ?>"><?= h($omschrijving) ?></label>
							</div>
							<?php endforeach; ?>
						</div></div>
					<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<button type="submit" name="actie" value="opslaan" class="btn btn-primary mt-2"><i class="fa-solid fa-floppy-disk"></i> Opslaan</button>
			</form>

			<?php if (!$bewerkBeheerder): ?>
			<hr>
			<h5>Wachtwoord en toegang</h5>
			<div class="knoppen">
				<form method="POST" action="systeem.php" onsubmit="return confirm('Een tijdelijk wachtwoord maken? Het oude wachtwoord werkt dan niet meer.');">
					<?= csrf_veld() ?><input type="hidden" name="id" value="<?= (int)$bewerk->id ?>">
					<button name="actie" value="wachtwoord" class="btn btn-outline-primary mb-2"><i class="fa-solid fa-key"></i> Tijdelijk wachtwoord maken</button>
				</form>
				<?php if (!$geblokkeerd($bewerk->delind)): ?>
				<form method="POST" action="systeem.php">
					<?= csrf_veld() ?><input type="hidden" name="id" value="<?= (int)$bewerk->id ?>">
					<button name="actie" value="link" class="btn btn-outline-primary mb-2" <?= filter_var((string)$bewerk->emailadres, FILTER_VALIDATE_EMAIL) ? '' : 'disabled' ?>>
						<i class="fa-regular fa-envelope"></i> Mail een link om zelf een wachtwoord in te stellen</button>
				</form>
				<form method="POST" action="systeem.php" onsubmit="return confirm('Deze gebruiker blokkeren? Hij kan dan niet meer inloggen, en het maatje verdwijnt ook uit het WAS.');">
					<?= csrf_veld() ?><input type="hidden" name="id" value="<?= (int)$bewerk->id ?>">
					<button name="actie" value="blokkeren" class="btn btn-outline-danger mb-2"><i class="fa-solid fa-ban"></i> Blokkeren</button>
				</form>
				<?php else: ?>
				<form method="POST" action="systeem.php">
					<?= csrf_veld() ?><input type="hidden" name="id" value="<?= (int)$bewerk->id ?>">
					<button name="actie" value="deblokkeren" class="btn btn-outline-success mb-2"><i class="fa-solid fa-unlock"></i> Deblokkeren</button>
				</form>
				<?php endif; ?>
			</div>
			<small class="text-muted">Tijdelijk wachtwoord: verschijnt één keer hier op het scherm; hij moet het bij het inloggen wijzigen.
				Link: dezelfde mail als bij "Wachtwoord vergeten", 1 uur geldig. Blokkeren: documenten en forumberichten blijven staan.</small>
			<?php endif; ?>
		</div>
	</div>
<?php endif; ?>

	<!-- ====================== NIEUWE GEBRUIKER ====================== -->
	<h4>Nieuwe gebruiker</h4>
	<?php if (!$zonderAcc): ?>
		<p class="text-muted">Alle maatjes hebben al een account. Een nieuw maatje maak je eerst aan in het WAS.</p>
	<?php else: ?>
	<form method="POST" action="systeem.php" class="mb-4">
		<?= csrf_veld() ?>
		<div class="form-row">
			<div class="form-group col-md-4">
				<label for="person_id">Maatje (nog zonder account)</label>
				<select class="form-control" name="person_id" id="person_id" required>
					<option value="">— kies —</option>
					<?php foreach ($zonderAcc as $m): ?>
						<option value="<?= (int)$m['person_id'] ?>"><?= h($m['achternaam'] . ', ' . trim($m['voornaam'] . ' ' . $m['tussenvoegsels'])) ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="form-group col-md-4">
				<label for="username">Inlognaam</label>
				<input type="text" class="form-control" name="username" id="username" required pattern="[A-Za-z0-9._@\-]{3,60}" autocomplete="off">
				<small class="form-text text-muted">Kan later niet worden gewijzigd.</small>
			</div>
			<div class="form-group col-md-4">
				<label for="profiel">Profiel</label>
				<select class="form-control" name="profiel" id="profiel">
					<?php foreach (array_keys(PROFIELEN) as $pnaam): ?>
						<option <?= $pnaam === 'Maatje' ? 'selected' : '' ?>><?= h($pnaam) ?></option>
					<?php endforeach; ?>
				</select>
				<small class="form-text text-muted">De vinkjes kun je daarna nog aanpassen.</small>
			</div>
		</div>
		<button type="submit" name="actie" value="toevoegen" class="btn btn-primary"><i class="fa-solid fa-user-plus"></i> Aanmaken</button>
	</form>
	<?php endif; ?>

	<!-- ====================== LIJST ====================== -->
	<h4 class="mt-4">Gebruikers</h4>
	<table id="gebruikers" class="table-sm table-striped table-hover"
		   data-toggle="table" data-search="true" data-pagination="true" data-page-size="30" data-page-list="20, 40, 60, 80, all">
		<thead class="thead-dark">
			<tr>
				<th data-sortable="true">naam</th>
				<th data-sortable="true">inlognaam</th>
				<th data-sortable="true">profiel</th>
				<th data-sortable="true">laatst actief</th>
				<th>status</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($gebruikers as $g): ?>
			<tr class="<?= $geblokkeerd($g['delind']) ? 'geblokkeerd' : '' ?>">
				<td><?= h($g['achternaam'] . ', ' . trim($g['voornaam'] . ' ' . $g['tussenvoegsels'])) ?></td>
				<td><?= h($g['username']) ?></td>
				<td title="<?= h(implode(', ', $g['rechten'])) ?>"><?= h(profielVan($g['rechten'])) ?></td>
				<td><span style="display: none;"><?= h($g['activity']) ?></span><?= h(Tools::ConvertTS($g['activity'])) ?></td>
				<td><?= $geblokkeerd($g['delind']) ? 'geblokkeerd' : ($g['password_mod'] === 'j' ? 'nieuw wachtwoord nodig' : '') ?></td>
				<td><a href="systeem.php?id=<?= (int)$g['id'] ?>" title="Wijzigen"><i class="fa-solid fa-pen"></i></a></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<p class="small text-muted mt-2">Ga met de muis op een profiel staan om de rechten te zien. "Aangepast" = afwijkend van de profielen.</p>
</div>
</main>
<?php include('includes/footer.php'); ?>

<script>
/* Profielknop: zet de vinkjes precies zoals in het profiel. */
$('.profiel').on('click', function () {
	var rechten = $(this).data('rechten');	// jQuery maakt van de JSON vanzelf een lijst
	$('.recht').each(function () { this.checked = rechten.indexOf(this.value) !== -1; });
});
/* Een vinkje aan dat een ander recht nodig heeft? Dan dat er ook bij.
   (De server doet dit bij het opslaan ook, zie rechtenUitFormulier().) */
$('.recht').on('change', function () {
	if (!this.checked) return;
	$.each($(this).data('vereist'), function (i, nodig) { $('#r_' + nodig).prop('checked', true); });
});
</script>
</body>
</html>
