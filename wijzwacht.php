<?php
require_once ('config.php');
include_once('class/c_user.php');

require_login();

if (isset($_POST['terug']))
{
	header('location:index.php');
	exit();
}

$error   = 0;	// welk veld rood wordt: 1 = huidig, 2 = nieuw, 3 = bevestiging
$melding = '';
if (isset($_POST['wijzigen']) && $_POST['wijzigen'] == 'wijzigen')
{
	$ww1 = (string)($_POST['ww1'] ?? '');
	$ww2 = (string)($_POST['ww2'] ?? '');
	$ww3 = (string)($_POST['ww3'] ?? '');
	$user = new User('id', $_SESSION['userid']);

	if (!$user->verifyPassword($ww1))
	{
		$error   = 1;
		$melding = 'Je huidige wachtwoord klopt niet.';
	}
	elseif (($fout = User::wachtwoordFout($ww2)) !== NULL)
	{
		$error   = 2;
		$melding = $fout;
	}
	elseif ($ww2 !== $ww3)
	{
		$error   = 3;
		$melding = 'De twee nieuwe wachtwoorden zijn niet gelijk.';
	}
	else
	{
		$user->setPassword($ww2);
		$user->password_mod = 'n';
		$user->wisResetToken();		// een openstaande "vergeten"-link vervalt
		$user->updateToDB();
		// Nieuw sessie-id: wie het oude id ergens had opgevangen, kan er niets meer mee.
		session_regenerate_id(true);
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
	<body>

		<div class="container">
			<div class="row mt-5">
				<div class="col-sm-3"></div>
				<div class="col-sm-6" style="margin: 20px auto; text-align: center;">
					<a href="index.php"><img src="logos/<?= LOC_LOGO ?>" alt="Logo <?= LOC_NAME ?>" width="223"></a>
					<h3 class="mt-lg-3"><?= LOC_NAME ?></h3>
				</div>
				<div class="col-sm-3"></div>
			</div>
			<div class="row">
				<div class="col-sm-3"></div>
				<div class="col-sm-6">
					<h4>Wachtwoord wijzigen</h4>
					<p>Geef hier je nieuwe wachtwoord op. Het moet minimaal 8 tekens lang zijn.
						Je mag letters, cijfers, leestekens en spaties gebruiken. Een zin van een paar woorden is makkelijk te onthouden en moeilijk te raden.</p>
					<?php if ($melding != ''): ?>
						<div class="alert alert-danger"><?= htmlspecialchars($melding, ENT_QUOTES, 'UTF-8') ?></div>
					<?php endif; ?>
				<form role="form" method="POST" action="wijzwacht.php">
					<div class="form-group">
						<label for="ww1">Huidig wachtwoord</label>
						<input name="ww1" type="password" class="form-control" id="ww1" autocomplete="current-password"<?= veldstijl($error, 1) ?>>
					</div>
					<div class="form-group">
						<label for="ww2">Nieuw wachtwoord</label>
						<input name="ww2" type="password" class="form-control" id="ww2" minlength="8" autocomplete="new-password"<?= veldstijl($error, 2) ?>>
					</div>
					<div class="form-group">
						<label for="ww3">Bevestig nieuw wachtwoord</label>
						<input name="ww3" type="password" class="form-control" id="ww3" minlength="8" autocomplete="new-password"<?= veldstijl($error, 3) ?>>
					</div><br/>
					<button name="wijzigen" value="wijzigen" type="submit" class="btn btn-primary">Wijzigen</button>
					<button name="reset" value="reset" type="reset" class="btn btn-secondary">Reset</button>
					<button name="terug" value="terug" type="submit" class="btn btn-secondary" formnovalidate>Terug</button>
				</form>
				</div>
				<div class="col-sm-3"></div>
			</div>
			<div class="row footer">
				<div class="col-sm-3"></div>
				<div class="col-sm-6 text-center my-5">
					&copy; <?= date("Y") . ' ' . LOC_NAME ?>
				</div>
				<div class="col-sm-3"></div>
			</div>
		</div>
	</body>
</html>
