<?php
include_once ('config.php');
include_once('class/c_user.php');

/* ------------------------------------------------------------------
 * Wachtwoord vergeten (okt 2026)
 *
 * Wie zijn e-mailadres invult, krijgt een mail met een eenmalige link
 * naar reset.php. Daar stel je zelf een nieuw wachtwoord in.
 * - Het oude wachtwoord blijft werken tot de reset is voltooid. Een ander
 *   kan je dus niet buitensluiten door jouw adres hier in te vullen.
 * - De pagina zegt altijd hetzelfde, of het adres nu bekend is of niet.
 *   Zo kan niemand uitzoeken wie er een account heeft.
 * - De link is 1 uur geldig en werkt maar één keer.
 * ------------------------------------------------------------------ */

if (isset($_POST['terug']))
{
	header('location:index.php');
	exit();
}

function stuurResetMail (User $user, string $token): void
{
	$link = LOC_WEBSITE . '/reset.php?token=' . $token;
	$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
	$mail_body = '<html><head></head><body><p>
	Beste ' . $h($user->voornaam) . ',<br/><br/>
	Er is gevraagd om een nieuw wachtwoord voor het intranet van ' . $h(LOC_NAME) . '.<br/>
	Je gebruikersnaam is: <b>' . $h($user->username) . '</b><br/><br/>
	Klik op deze link om een nieuw wachtwoord in te stellen:<br/>
	<a href="' . $h($link) . '">' . $h($link) . '</a><br/><br/>
	De link is 1 uur geldig en werkt één keer.<br/><br/>
	Heb je dit niet zelf aangevraagd? Dan kun je deze mail negeren. Je wachtwoord blijft dan gewoon hetzelfde.
	</p></body></html>';

	Tools::MailRoom($user->voornaam, $user->emailadres, 'Nieuw wachtwoord voor het intranet', $mail_body);
	Tools::closeMailer();
}

$emailadres = '';
$verstuurd  = FALSE;

if (isset($_POST['wijzigen']))
{
	$emailadres = trim((string)($_POST['email'] ?? ''));
	if ($emailadres != '')
	{
		$user = new User('emailadres', $emailadres);
		if ($user->id != NULL && !$user->resetRecentAangevraagd())
		{
			$token = $user->maakResetToken();
			$user->updateToDB();
			stuurResetMail($user, $token);
		}
		// Altijd dezelfde melding, ook als het adres onbekend is.
		$verstuurd = TRUE;
	}
}
?>
<!DOCTYPE html>
<html>
	<head>
		<?php include('includes/head.php'); ?>
	</head>
	<body class="bodystyle">

		<div class="container">
			<div class="row mt-5 text-center">
				<div class="col-lg-3"></div>
				<div class="col-lg-6">
					<a href="index.php"><img src="logos/<?= LOC_LOGO ?>" alt="Logo <?= LOC_NAME ?>" width="223"></a><br/><br/>
					<h3><?= LOC_NAME ?> Intranet</h3>
				</div>
				<div class="col-lg-3"></div>
			</div>
			<div class="row">
				<div class="col-lg-3"></div>
				<div class="col-lg-6">
					<h2>Wachtwoord vergeten</h2>
				<?php if ($verstuurd): ?>
					<div class="alert alert-success">
						Als dit e-mailadres bij ons bekend is, krijg je binnen een paar minuten een e-mail met een link.
						Via die link stel je zelf een nieuw wachtwoord in. De link is 1 uur geldig.
					</div>
					<p>Zie je geen e-mail? Kijk dan ook in je map met spam of ongewenste e-mail.</p>
					<form method="POST" action="vergeten.php"><?= csrf_veld() ?>
						<button name="terug" type="submit" class="btn btn-primary">Terug naar inloggen</button>
					</form>
				<?php else: ?>
					<p>Vul je e-mailadres in. Je krijgt dan een e-mail met je gebruikersnaam en een link waarmee je zelf een nieuw wachtwoord instelt.
						Tot je dat gedaan hebt, blijft je huidige wachtwoord gewoon werken.</p>
					<form role="form" method="POST" action="vergeten.php"><?= csrf_veld() ?>
						<div class="form-group">
							<label for="email">E-mailadres</label>
							<input name="email" type="email" class="form-control" id="email" required autocomplete="email" value="<?= htmlspecialchars($emailadres, ENT_QUOTES, 'UTF-8') ?>">
						</div><br/>
						<button name="wijzigen" value="true" type="submit" class="btn btn-primary">Stuur link</button>
						<button name="terug" type="submit" class="btn btn-secondary" formnovalidate>Terug</button>
					</form>
				<?php endif; ?>
				</div>
				<div class="col-lg-3"></div>
			</div>
			<div class="row footer">
				<div class="col-lg-3"></div>
				<div class="col-lg-6 text-center my-5">
					&copy; <?= date("Y") . ' ' . LOC_NAME ?>
				</div>
				<div class="col-lg-3"></div>
			</div>
		</div>
	</body>
</html>
