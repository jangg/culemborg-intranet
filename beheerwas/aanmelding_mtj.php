<?php
include_once('../config.php');
include_once('../class/c_user.php');
include_once('../class/c_maatje.php');

// Sessiecheck
if (!isset($_SESSION['username'])) {
	header('location:../index.php');
	exit();
}
// Rechtencontrole (zie includes/rechten.php)
require_recht('maatjes_wijzigen');

// Huidige gebruiker ophalen
if (isset($_SESSION['userid'])) {
	$curr_user = new User('id', $_SESSION['userid']);
} else {
	$curr_user = new User();
	$curr_user->id = '1';
}

// CSRF-token aanmaken als dat nog niet bestaat
if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/** Leeftijd berekenen op basis van datumstring 'jjjj-mm-dd' */
function calculateAge(string $date): int
{
	$birth = new DateTime($date);
	$today = new DateTime();
	return (int) $today->diff($birth)->y;
}

/** Veilige output: voorkomt XSS */
function h(?string $value): string
{
	return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Terug-knop
if (isset($_POST['backMtBut']) && $_POST['backMtBut'] === 'back') {
	header('location: overz_maatjes.php');
	exit();
}

$mtj = new Maatje();
$saveError = '';

if (isset($_POST['saveMtBut']) && $_POST['saveMtBut'] === 'bewaar') {

	// CSRF-validatie
	if (
		empty($_POST['csrf_token']) ||
		!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
	) {
		http_response_code(403);
		die('Ongeldige formulieraanvraag.');
	}

	$mtj->voornaam       = trim($_POST['voornaam']       ?? '');
	$mtj->achternaam     = trim($_POST['achternaam']     ?? '');
	$mtj->tussenvoegsels = trim($_POST['tussenvoegsels'] ?? '');
	$mtj->straat         = trim($_POST['straat']         ?? '');
	$mtj->huisnummer     = trim($_POST['huisnummer']     ?? '');
	$mtj->postcode       = trim($_POST['postcode']       ?? '');
	$mtj->woonplaats     = trim($_POST['woonplaats']     ?? '');
	$mtj->emailadres     = trim($_POST['emailadres']     ?? '');
	$mtj->telefoonnr     = trim($_POST['telefoonnr']     ?? '');
	$mtj->link_linkedin  = trim($_POST['link_linkedin']  ?? '');
	$mtj->omschrijving   = trim($_POST['omschrijving']   ?? '');
	$mtj->functie        = trim($_POST['functie']        ?? '');
	$mtj->type           = 'mtj';

	$geboortedatum = trim($_POST['date_geboorte'] ?? '');
	if (Tools::checkDate($geboortedatum, 'jjjj-mm-dd')) {
		$mtj->date_geboorte = $geboortedatum;
	}

	// Certificaat-indicatoren
	$mtj->mtjcrt_ind = isset($_POST['mtjcrt_ind']) ? 'j' : 'n';
	$mtj->jglcrt_ind = isset($_POST['jglcrt_ind']) ? 'j' : 'n';

	// actief_als: 'A' = maatje, 'B' = jobgroupleider, 'K' = beide, '' = geen
	$actiefMtj = isset($_POST['actiefmtj']);
	$actiefJgl = isset($_POST['actiefjgl']);
	if ($actiefMtj && $actiefJgl) {
		$mtj->actief_als = 'K';
	} elseif ($actiefMtj) {
		$mtj->actief_als = 'A';
	} elseif ($actiefJgl) {
		$mtj->actief_als = 'B';
	} else {
		$mtj->actief_als = '';
	}

	if ($mtj->achternaam === '' || $mtj->emailadres === '') {
		$saveError = 'Achternaam en emailadres zijn verplicht.';
	} else {
		$mtj->saveToDB();
		// Redirect voorkomt dubbele POST bij terug-navigeren
		header('location: overz_maatjes.php');
		exit();
	}
}
?>
<!DOCTYPE html>
<html lang="nl-NL">
<?php include('../includes/head.php'); ?>
<script>
$(document).ready(function () {

	// Datepicker geboortedatum
	$("#date_geboorte").datepicker({
		dateFormat: "yy-mm-dd",
		minDate: new Date(1945, 0, 1),
		maxDate: new Date(2004, 11, 31),
		changeMonth: true,
		changeYear: true,
	});
	$('div.ui-datepicker').css({ fontSize: '0.9em' });

	// Hulpfunctie: koppel enabled-staat van actief-checkbox aan certificaat-checkbox
	function koppelCertificaat(crtId, actId) {
		var $crt = $("#" + crtId);
		var $act = $("#" + actId);

		function sync() {
			if ($crt.prop("checked")) {
				$act.prop("disabled", false);
			} else {
				$act.prop("checked", false);
				$act.prop("disabled", true);
			}
		}

		sync(); // initiële staat
		$crt.on("click", sync);
	}

	koppelCertificaat("mtjcrt_ind", "actiefmtj");
	koppelCertificaat("jglcrt_ind", "actiefjgl");

	// Client-side validatie bij opslaan
	$("#saveMtBut").on("click", function (event) {
		if ($("#achternaam").val().trim() === '') {
			event.preventDefault();
			alert("Achternaam is verplicht.");
			$("#achternaam").focus();
		} else if ($("#emailadres").val().trim() === '') {
			event.preventDefault();
			alert("Emailadres is verplicht.");
			$("#emailadres").focus();
		}
	});
});
</script>
</head>
<body style="background-color: #dddddd;">

<div class="container">
	<?php include('../includes/navbar.php'); ?>
</div>

<div class="container" style="margin-top: 80px; background-color: #304280;">
	<div class="row header rounded text-white py-3">
		<h1 class="mx-auto">Aanmelding nieuw maatje</h1>
	</div>
</div>

<div class="container" style="padding-bottom: 80px;">

	<?php if ($saveError !== ''): ?>
		<div class="alert alert-danger mt-2"><?php echo h($saveError); ?></div>
	<?php endif; ?>

	<form method="POST" action="aanmelding_mtj.php" novalidate>
		<input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>">

		<div class="row">
			<div class="col-12 bg-light mt-2 pt-2">

				<div class="input-group input-group-sm mb-1">
					<div class="input-group-prepend" style="width: 30%;">
						<span class="input-group-text" style="width: 100%;">Voornaam</span>
					</div>
					<input type="text" name="voornaam" class="form-control" value="<?php echo h($mtj->voornaam); ?>">
				</div>

				<div class="input-group input-group-sm mb-1">
					<div class="input-group-prepend" style="width: 30%;">
						<span class="input-group-text" style="width: 100%;">Tussenvoegsels</span>
					</div>
					<input type="text" name="tussenvoegsels" class="form-control" value="<?php echo h($mtj->tussenvoegsels); ?>">
				</div>

				<div class="input-group input-group-sm mb-1">
					<div class="input-group-prepend" style="width: 30%;">
						<span class="input-group-text" style="width: 100%;">Achternaam</span>
					</div>
					<input type="text" id="achternaam" name="achternaam" class="form-control" value="<?php echo h($mtj->achternaam); ?>" required>
				</div>

				<div class="input-group input-group-sm mb-1">
					<div class="input-group-prepend" style="width: 30%;">
						<span class="input-group-text" style="width: 100%;">Emailadres</span>
					</div>
					<input type="email" id="emailadres" name="emailadres" class="form-control" value="<?php echo h($mtj->emailadres); ?>">
				</div>

				<div class="input-group input-group-sm mb-1">
					<div class="input-group-prepend" style="width: 30%;">
						<span class="input-group-text" style="width: 100%;">Telefoonnummer</span>
					</div>
					<input type="text" id="telefoonnr" name="telefoonnr" class="form-control" value="<?php echo h($mtj->telefoonnr); ?>">
				</div>

				<div class="input-group input-group-sm mb-1">
					<div class="input-group-prepend" style="width: 30%;">
						<span class="input-group-text" style="width: 100%;">URL LinkedIn</span>
					</div>
					<input type="text" name="link_linkedin" class="form-control" value="<?php echo h($mtj->link_linkedin); ?>">
				</div>

				<div class="input-group input-group-sm mb-1">
					<div class="input-group-prepend" style="width: 30%;">
						<span class="input-group-text" style="width: 100%;">Straat</span>
					</div>
					<input type="text" name="straat" class="form-control" value="<?php echo h($mtj->straat); ?>">
				</div>

				<div class="input-group input-group-sm mb-1">
					<div class="input-group-prepend" style="width: 30%;">
						<span class="input-group-text" style="width: 100%;">Huisnummer</span>
					</div>
					<input type="text" name="huisnummer" class="form-control" value="<?php echo h($mtj->huisnummer); ?>">
				</div>

				<div class="input-group input-group-sm mb-1">
					<div class="input-group-prepend" style="width: 30%;">
						<span class="input-group-text" style="width: 100%;">Postcode</span>
					</div>
					<input type="text" name="postcode" class="form-control" value="<?php echo h($mtj->postcode); ?>">
				</div>

				<div class="input-group input-group-sm mb-1">
					<div class="input-group-prepend" style="width: 30%;">
						<span class="input-group-text" style="width: 100%;">Woonplaats</span>
					</div>
					<input type="text" name="woonplaats" class="form-control" value="<?php echo h($mtj->woonplaats); ?>">
				</div>

				<div class="input-group input-group-sm mb-1">
					<div class="input-group-prepend" style="width: 30%;">
						<span class="input-group-text" style="width: 100%;">Geboortedatum</span>
					</div>
					<input type="text" name="date_geboorte" id="date_geboorte" class="form-control"
						   value="<?php echo h($mtj->date_geboorte); ?>" placeholder="jjjj-mm-dd">
				</div>

				<div class="input-group input-group-sm mb-1">
					<div class="input-group-prepend" style="width: 30%;">
						<span class="input-group-text" style="width: 100%;">Leeftijd</span>
					</div>
					<input type="text" class="form-control"
						   value="<?php echo ($mtj->date_geboorte ?? '') !== '' ? calculateAge($mtj->date_geboorte) : ''; ?>"
						   disabled>
				</div>

				<div class="input-group input-group-sm mb-1">
					<div class="input-group-prepend" style="width: 30%;">
						<span class="input-group-text" style="width: 100%;">Functie</span>
					</div>
					<textarea name="functie" rows="5" class="form-control"><?php echo h($mtj->functie); ?></textarea>
				</div>

				<div class="input-group input-group-sm mb-1">
					<div class="input-group-prepend" style="width: 30%;">
						<span class="input-group-text text-left text-wrap" style="width: 100%;">Maatje certificaat behaald</span>
					</div>
					<input type="checkbox" name="mtjcrt_ind" id="mtjcrt_ind" class="form-control" value="j"
						   style="margin-left: 15px;"<?php if ($mtj->mtjcrt_ind === 'j') echo ' checked'; ?>>
				</div>

				<div class="input-group input-group-sm mb-1">
					<div class="input-group-prepend" style="width: 30%;">
						<span class="input-group-text text-left text-wrap" style="width: 100%;">Actief als maatje</span>
					</div>
					<input type="checkbox" name="actiefmtj" id="actiefmtj" class="form-control" value="j"
						   style="margin-left: 15px;"<?php if ($mtj->actief_als === 'A' || $mtj->actief_als === 'K') echo ' checked'; ?>>
				</div>

				<div class="input-group input-group-sm mb-1">
					<div class="input-group-prepend" style="width: 30%;">
						<span class="input-group-text text-left text-wrap" style="width: 100%;">Jobgroupleider certificaat behaald</span>
					</div>
					<input type="checkbox" name="jglcrt_ind" id="jglcrt_ind" class="form-control" value="j"
						   style="margin-left: 15px;"<?php if ($mtj->jglcrt_ind === 'j') echo ' checked'; ?>>
				</div>

				<div class="input-group input-group-sm mb-1">
					<div class="input-group-prepend" style="width: 30%;">
						<span class="input-group-text text-left text-wrap" style="width: 100%;">Actief als jobgroupleider</span>
					</div>
					<input type="checkbox" name="actiefjgl" id="actiefjgl" class="form-control" value="j"
						   style="margin-left: 15px;"<?php if ($mtj->actief_als === 'B' || $mtj->actief_als === 'K') echo ' checked'; ?>>
				</div>

				<div class="input-group input-group-sm mb-1">
					<div class="input-group-prepend" style="width: 30%;">
						<span class="input-group-text" style="width: 100%;">Notities</span>
					</div>
					<textarea name="omschrijving" rows="5" class="form-control"><?php echo h($mtj->omschrijving); ?></textarea>
				</div>

				<div class="forms-group mb-1">
					<?php if (heeft_recht('maatjes_wijzigen')): ?>
						<button name="saveMtBut" id="saveMtBut" value="bewaar" type="submit" class="btn btn-primary btn-width btn-sm">Bewaar</button>
					<?php else: ?>
						<button type="button" class="btn btn-primary btn-width btn-sm" disabled>Bewaar</button>
					<?php endif; ?>
					<button name="backMtBut" value="back" type="submit" class="btn btn-secondary btn-width btn-sm">Terug</button>
				</div>

			</div>
		</div>
	</form>
</div>

<?php include('../includes/footer.php'); ?>
</body>
</html>
