<?php
include_once ('config.php');
include_once('class/c_user.php');

/* ------------------------------------------------------------------
 * Nieuw wachtwoord instellen via de link uit vergeten.php (okt 2026)
 *
 * De link bevat een token: reset.php?token=...
 * User('resettoken', $token) zoekt de gebruiker bij die token, maar alleen
 * als de link nog geldig is (1 uur). Na gebruik wordt de token gewist,
 * dus de link werkt maar één keer.
 * ------------------------------------------------------------------ */

// Stuur de token nooit mee als "herkomst" naar een andere website
// (bijvoorbeeld bij het laden van een script van een CDN).
header('Referrer-Policy: no-referrer');

// De token komt bij de eerste keer uit de link (GET), daarna uit het formulier (POST).
$token = (string)($_POST['token'] ?? $_GET['token'] ?? '');
// Een geldige token bestaat uit precies 64 hexadecimale tekens; al het andere meteen weigeren.
$user  = preg_match('/^[0-9a-f]{64}$/', $token) ? new User('resettoken', $token) : new User();

$error   = 0;	// 2 = nieuw wachtwoord, 3 = bevestiging
$melding = '';
if ($user->id != NULL && isset($_POST['opslaan']))
{
	$ww2 = (string)($_POST['ww2'] ?? '');
	$ww3 = (string)($_POST['ww3'] ?? '');
	if (($fout = User::wachtwoordFout($ww2)) !== NULL)
	{
		$error   = 2;
		$melding = $fout;
	}
	elseif ($ww2 !== $ww3)
	{
		$error   = 3;
		$melding = 'De twee wachtwoorden zijn niet gelijk.';
	}
	else
	{
		$user->setPassword($ww2);
		$user->password_mod = 'n';
		$user->wisResetToken();		// link is nu gebruikt
		$user->updateToDB();
		header('location:index.php?gewijzigd=1');
		exit();
	}
}

function veldstijl (int $error, int $veld): string
{
	return $error == $veld ? ' style="background-color: red;"' : ' style="background-color: white;"';
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
					<h2>Nieuw wachtwoord instellen</h2>
				<?php if ($user->id == NULL): ?>
					<div class="alert alert-warning">
						Deze link is niet (meer) geldig. Een link werkt één keer en is 1 uur geldig.
					</div>
					<a class="btn btn-primary" href="vergeten.php">Vraag een nieuwe link aan</a>
					<a class="btn btn-secondary" href="index.php">Terug naar inloggen</a>
				<?php else: ?>
					<p>Gebruikersnaam: <b><?= htmlspecialchars((string)$user->username, ENT_QUOTES, 'UTF-8') ?></b></p>
					<p>Kies een nieuw wachtwoord van minimaal 8 tekens. Je mag letters, cijfers, leestekens en spaties gebruiken.</p>
					<?php if ($melding != ''): ?>
						<div class="alert alert-danger"><?= htmlspecialchars($melding, ENT_QUOTES, 'UTF-8') ?></div>
					<?php endif; ?>
					<form role="form" method="POST" action="reset.php"><?= csrf_veld() ?>
						<input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
						<div class="form-group">
							<label for="ww2">Nieuw wachtwoord</label>
							<input name="ww2" type="password" class="form-control" id="ww2" minlength="8" required autocomplete="new-password"<?= veldstijl($error, 2) ?>>
						</div>
						<div class="form-group">
							<label for="ww3">Bevestig nieuw wachtwoord</label>
							<input name="ww3" type="password" class="form-control" id="ww3" minlength="8" required autocomplete="new-password"<?= veldstijl($error, 3) ?>>
						</div><br/>
						<button name="opslaan" value="opslaan" type="submit" class="btn btn-primary">Opslaan</button>
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
