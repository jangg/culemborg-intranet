<?php
/* ------------------------------------------------------------------
 * Uitleg documenten (nov 2026): alle instructievideo's over de
 * documentenmodule op één pagina. Iedereen ziet alleen de video's die
 * bij zijn of haar rechten passen (zie includes/uitleg_videos.php).
 * ------------------------------------------------------------------ */
require_once('config.php');
include_once('class/c_user.php');
include_once('class/c_documenten.php');
include_once('includes/uitleg_videos.php');

require_login();
if (!Document::magPagina()) {
	http_response_code(403);
	exit('Je hebt geen toegang tot deze pagina.');
}
$curr_user = new User('id', $_SESSION['userid']);	// voor de navigatiebalk ("Ingelogd als")
$videos = uitleg_videos();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
	<?php include('includes/head.php'); ?>
	<style>
		.bluefont { color: #304280; font-weight: 300; }
		.uitleg-kaart video { width: 100%; height: auto; background: #000; border-radius: 6px; }
		.uitleg-kaart h5 { margin-top: .6rem; }
	</style>
</head>
<body class="bodystyle">
<?php include('includes/navbar.php'); ?>
<main role="main">
<div class="container mb-5">
	<h1 class="display-4 bluefont p-3 mt-md-5">Uitleg documenten</h1>
	<p><a href="documenten.php"><i class="fa-solid fa-arrow-left"></i> Terug naar de documenten</a></p>
	<p class="text-muted">Korte filmpjes (ongeveer een minuut) over het werken met documenten.
		Klik op een filmpje om het af te spelen. Met de knop linksboven in het filmpje maak je het beeld groter.</p>

	<div class="row">
	<?php foreach ($videos as $v): ?>
		<div class="col-md-6 mb-4 uitleg-kaart">
			<video controls playsinline preload="metadata" poster="video/<?= h($v['bestand']) ?>.jpg">
				<source src="video/<?= h($v['bestand']) ?>.mp4" type="video/mp4">
				Je browser kan dit filmpje niet afspelen.
			</video>
			<h5><?= h($v['titel']) ?></h5>
		</div>
	<?php endforeach; ?>
	</div>
</div>
</main>
<script>
	// Er speelt er steeds maar één: start je een filmpje, dan stopt het vorige.
	document.querySelectorAll('.uitleg-kaart video').forEach(v => v.addEventListener('play', () => {
		document.querySelectorAll('.uitleg-kaart video').forEach(a => { if (a !== v) a.pause(); });
	}));
</script>
</body>
</html>
