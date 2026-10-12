<?php
/* ------------------------------------------------------------------
 * Instellingen van de documentenfunctie (alleen beheerder, okt 2026)
 *  - welke soorten bestanden zijn toegestaan
 *  - maximale grootte per bestand
 *  - maximum per persoon (alles wat iemand heeft geüpload, eigen + gedeeld)
 * Plus een controle of database en documentenmap nog bij elkaar passen.
 * ------------------------------------------------------------------ */
require_once('config.php');
include_once('class/c_user.php');
include_once('class/c_documenten.php');

require_recht('beheerder');
$curr_user = new User('id', $_SESSION['userid']);

$fouten = [];
if (isset($_POST['opslaan'])) {
	// Extensies: alleen letters en cijfers, kleine letters, zonder punt, geen dubbele
	$ext = preg_split('/[\s,;]+/', strtolower((string)($_POST['extensies'] ?? '')), -1, PREG_SPLIT_NO_EMPTY);
	$ext = array_unique(array_map(fn($e) => ltrim($e, '.'), $ext));
	$verboden = array_intersect($ext, Instelling::VERBODEN);
	$ext = array_filter($ext, fn($e) => preg_match('/^[a-z0-9]{1,10}$/', $e) && !in_array($e, Instelling::VERBODEN, true));
	if ($verboden) $fouten[] = 'Deze soorten zijn om veiligheidsredenen nooit toegestaan en zijn weggelaten: ' . implode(', ', $verboden) . '.';
	if (!$ext)     $fouten[] = 'Geef minstens één soort bestand op.';

	$max    = str_replace(',', '.', (string)($_POST['max_mb'] ?? ''));
	$quotum = str_replace(',', '.', (string)($_POST['quotum_mb'] ?? ''));
	if (!is_numeric($max) || $max <= 0)       $fouten[] = 'De maximale grootte per bestand moet een getal groter dan 0 zijn.';
	if (!is_numeric($quotum) || $quotum <= 0) $fouten[] = 'Het maximum per persoon moet een getal groter dan 0 zijn.';

	if ($ext) Instelling::set('doc_extensies', implode(',', $ext));
	if (is_numeric($max) && $max > 0)       Instelling::set('doc_max_mb', (string)(float)$max);
	if (is_numeric($quotum) && $quotum > 0) Instelling::set('doc_quotum_mb', (string)(float)$quotum);
	if (!$fouten) {
		header('Location: documenten_beheer.php?opgeslagen=1');
		exit();
	}
}

$opslag   = Document::opslagmap();
$controle = Document::controleer();
$phpMax   = Instelling::phpMaxBytes();
$maxMb    = (float)Instelling::get('doc_max_mb');
?>
<!DOCTYPE html>
<html lang="nl-NL">
<head>
	<?php include('includes/head.php'); ?>
	<style>.bluefont { color: #304280; font-weight: 300; }</style>
</head>
<body class="bodystyle">
<?php include('includes/navbar.php'); ?>
<main role="main">
<div class="container mb-5" style="max-width: 760px;">
	<h1 class="display-4 bluefont p-3 mt-md-5">Instellingen documenten</h1>
	<p><a href="documenten.php?r=beheer"><i class="fa-solid fa-arrow-left"></i> Terug naar de documenten</a></p>

	<?php if (isset($_GET['opgeslagen'])): ?><div class="alert alert-success">De instellingen zijn opgeslagen.</div><?php endif; ?>
	<?php foreach ($fouten as $f): ?><div class="alert alert-warning"><?= h($f) ?></div><?php endforeach; ?>

	<form method="POST" action="documenten_beheer.php">
		<?= csrf_veld() ?>
		<div class="form-group">
			<label for="extensies"><strong>Toegestane soorten bestanden</strong></label>
			<input type="text" class="form-control" id="extensies" name="extensies" value="<?= h(implode(', ', Instelling::extensies())) ?>">
			<small class="form-text text-muted">De extensies, gescheiden door komma's, bijvoorbeeld: pdf, docx, xlsx, jpg.
				Programma's en webpagina's (php, exe, html, js, svg en dergelijke) worden altijd geweigerd.</small>
		</div>
		<div class="form-row">
			<div class="form-group col-sm-6">
				<label for="max_mb"><strong>Maximaal per bestand (MB)</strong></label>
				<input type="text" class="form-control" id="max_mb" name="max_mb" value="<?= h(Instelling::get('doc_max_mb')) ?>">
				<small class="form-text <?= $maxMb * 1048576 > $phpMax ? 'text-danger' : 'text-muted' ?>">
					De server laat maximaal <?= h(Document::mb($phpMax)) ?> toe
					<?= $maxMb * 1048576 > $phpMax ? '. Daarom geldt nu dat maximum. Hoger kan alleen via de PHP-instellingen in DirectAdmin (upload_max_filesize en post_max_size).' : '' ?></small>
			</div>
			<div class="form-group col-sm-6">
				<label for="quotum_mb"><strong>Maximaal per persoon (MB)</strong></label>
				<input type="text" class="form-control" id="quotum_mb" name="quotum_mb" value="<?= h(Instelling::get('doc_quotum_mb')) ?>">
				<small class="form-text text-muted">Alles wat iemand heeft toegevoegd, eigen en gedeeld samen. Geldt niet voor de beheerder.</small>
			</div>
		</div>
		<button type="submit" name="opslaan" value="1" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Opslaan</button>
	</form>

	<h4 class="mt-5">Controle</h4>
	<ul>
		<li>Documentenmap:
			<?php if ($opslag === null): ?>
				<span class="text-danger">niet ingesteld of niet beschrijfbaar. Zet in includes/configDB.php
				<code>define("DOC_DIR", __DIR__ . "/../../documenten/");</code> (een map naast public_html).</span>
			<?php else: ?>
				<span class="text-success">in orde</span><?= str_contains(realpath($opslag) ?: '', realpath($_SERVER['DOCUMENT_ROOT']) ?: "\0") ? ' <span class="text-danger">(maar hij staat BINNEN de website; zet hem naast public_html)</span>' : '' ?>.
			<?php endif; ?></li>
		<li>Bestanden zonder regel in de database: <?= count($controle['zonder_regel']) ?></li>
		<li>Documenten waarvan het bestand ontbreekt: <?= count($controle['zonder_bestand']) ?></li>
	</ul>
	<?php if ($controle['zonder_regel'] || $controle['zonder_bestand']): ?>
		<p class="small text-muted">Dat kan gebeuren als alleen de database óf alleen de documentenmap is teruggezet uit een back-up.
			Zet ze altijd samen terug.</p>
	<?php endif; ?>
</div>
</main>
</body>
</html>
