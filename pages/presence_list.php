<?php
require_once('../config.php');
require_once('../class/c_user_coll.php');

// Toegangsbeveiliging: niet-ingelogde gebruikers worden omgeleid
if (!isset($_SESSION['username'])) {
	header('Location: ../index.php');
	exit();
}

$curr_user = isset($_SESSION['userid'])
	? new User('id', $_SESSION['userid'])
	: new User();

// Filters en sortering voor de gebruikerscollectie
$filters = [['person.type', 'mtj']];
$sorting = [['voornaam', 'ASC']];
// $filters = null;

$userColl = new User_coll($filters, $sorting);

$html = '';
$i    = 0;

foreach ($userColl->userColl as $user) {

	$presentInd     = $user->presentInd;
	$htmlextra      = '';
	$first_disabled = 'disabled';

	if ($curr_user->username === $user->username || $curr_user->beheerind > 6) {
		$first_disabled = '';
		// Let op: id-attribuut is vervangen door data-switch,
		// want meerdere elementen met hetzelfde id is ongeldige HTML.
		$htmlextra = ' data-switch="presence" value="' . htmlspecialchars((string)$user->id, ENT_QUOTES, 'UTF-8') . '"';
	}

	if ($presentInd === 'j') {
		$first_but   = 'checked';
		$first_text  = 'JA';
		$second_text = 'NEE';
	} elseif ($presentInd === 'n') {
		$first_but   = '';
		$first_text  = 'JA';
		$second_text = 'NEE';
	} else {
		$first_but   = '';
		$first_text  = 'JA';
		$second_text = '&amp;nbsp;'; // &nbsp; als zichtbare placeholder in data-attribuut
	}

	$voornaam       = htmlspecialchars((string)$user->voornaam,       ENT_QUOTES, 'UTF-8');
	$tussenvoegsels = htmlspecialchars((string)$user->tussenvoegsels, ENT_QUOTES, 'UTF-8');
	$achternaam     = htmlspecialchars((string)$user->achternaam,     ENT_QUOTES, 'UTF-8');
	$userId         = htmlspecialchars((string)$user->id,             ENT_QUOTES, 'UTF-8');

	$i++;

	$html .= "
		<tr>
			<td>{$i}</td>
			<td>{$voornaam} {$tussenvoegsels} {$achternaam}</td>
			<td>
				<label>komt naar de jaarafsluiting&nbsp;&nbsp;</label>
				<input class=\"usersel\" type=\"checkbox\" name=\"userid\"{$htmlextra}
					data-toggle=\"toggle\" data-size=\"sm\"
					data-on=\"{$first_text}\" data-off=\"{$second_text}\"
					data-onstyle=\"success\" data-offstyle=\"secondary\"
					data-width=\"80px\" data-userid=\"{$userId}\"
					{$first_but} {$first_disabled}>
			</td>
		</tr>
	";
}
?>
<!DOCTYPE HTML>
<html lang="nl-NL">
<head>
	<?php include('../includes/head.php'); ?>
	<link href="../css/jumbotron.css" rel="stylesheet" type="text/css">
	<!-- <link href="../css/mystyle.css" rel="stylesheet" type="text/css"> -->
	<link href="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/css/bootstrap4-toggle.min.css" rel="stylesheet">
	<script src="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/js/bootstrap4-toggle.min.js"></script>
	<script>
		$(document).ready(function () {
			$('.usersel').on('change', function () {
				const userid = $(this).data('userid');
				$.ajax({
					url:  'proces_presence.php',
					type: 'POST',
					data: { userid: userid },
					success: function (response) {
						// optioneel: feedback tonen bij succes
					},
					error: function () {
						// optioneel: feedback tonen bij fout
					}
				});
			});
		});
	</script>
	<style>
		.bluefont {
			color: #304280;
			font-weight: 300;
		}
		.bg-jhmz {
			background-color: #eeeeee;
		}
		.errormessage {
			color: red;
		}
	</style>
</head>

<body class="bodystyle">

<?php include('../includes/navbar.php'); ?>

<div class="jumbotron">
	<div id="main">
		<div class="container my-5">

			<h3 class="bluefont">20 mei 2026</h3>
			<h1 class="text-black mb-5 bluefont">Deelnamelijst Jaarafsluiting 25/26</h1>

			<p>Op vrijdagavond 3 juli 2026 sluit JobHulp Culemborg het jaar feestelijk af met een hapje en een drankje.</p>
			<p>Nu aanmelden of afmelden is belangrijk en simpel. De knop is grijs als je nog geen keuze hebt gemaakt.
			   Als je de schuifknop 1&times; aanklikt wordt het &lsquo;ja&rsquo; en geef je aan dat je komt.
			   Als je de knop 2&times; aanklikt wordt het, na refresh, &lsquo;nee&rsquo; en geef je dus aan dat je niet komt.</p>
			<p><strong>Schrijf je hier in en kom ook op 3 juli.</strong></p>

			<table class="table table-sm">
				<thead>
					<tr>
						<th></th>
						<th>Genodigde</th>
						<th>Response</th>
					</tr>
				</thead>
				<tbody>
					<?php echo $html; ?>
				</tbody>
			</table>

		</div>
	</div>
</div>

<?php include('../includes/footer.php'); ?>

</body>
</html>
