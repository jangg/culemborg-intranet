<?php
/* ------------------------------------------------------------------
 * Documenten (okt 2026; nov 2026 ingericht volgens de keuze van de
 * organisaties: A = gedeelde map, C = documenten per werkzoekende)
 *
 * Tabbladen:
 *   ?r=gedeeld            A. Gedeelde documenten (recht documenten_gedeeld)
 *   ?r=werkzoekende       C. Lijst van werkzoekenden: met recht documenten_eigen (B)
 *                            de eigen werkzoekenden, met documenten_werkzoekenden (C) alle
 *   ?r=werkzoekende&w=7   C. De documenten van werkzoekende 7 (werkzkd.id)
 * Verder: ?map=12 opent een map, ?q=tekst zoekt.
 * De beheerder komt via de knop Instellingen bij documenten_beheer.php.
 *
 * Alles wat iets wijzigt gaat via POST (met CSRF-token) en daarna een
 * doorverwijzing terug naar de pagina (Post/Redirect/Get). Zo verstuurt
 * de browser het formulier niet opnieuw als iemand op "vernieuwen" drukt.
 * De melding ("3 documenten toegevoegd") gaat via de sessie mee.
 * Wie wat mag: zie class/c_documenten.php (Document::magRuimte/magWijzigen).
 * ------------------------------------------------------------------ */
require_once('config.php');
include_once('class/c_user.php');
include_once('class/c_documenten.php');

require_login();
if (!Document::magPagina()) {	// minstens één van de rechten A, B of C (nov 2026)
	http_response_code(403);
	exit('Je hebt geen toegang tot deze pagina.');
}
$curr_user = new User('id', $_SESSION['userid']);
$mijnId    = (int)$_SESSION['userid'];
$beheerder = heeft_recht('beheerder');
$magGedeeld = heeft_recht('documenten_gedeeld');
$magWzk     = heeft_recht('documenten_eigen') || heeft_recht('documenten_werkzoekenden');

/* ---------- Welke ruimte, van wie, en welke map? ----------
 * $r        de ruimte: 'gedeeld' of 'werkzoekende'
 * $eigenaar bij 'werkzoekende' het werkzkd.id (w=...), bij 'gedeeld' altijd null
 * $lijstWzk true: nog geen werkzoekende gekozen, dus de lijst van werkzoekenden tonen */
$r = (string)($_POST['r'] ?? $_GET['r'] ?? ($magGedeeld ? 'gedeeld' : 'werkzoekende'));
if (!in_array($r, ['gedeeld', 'werkzoekende'], true)) $r = 'werkzoekende';
if ($r === 'gedeeld' && !$magGedeeld) $r = 'werkzoekende';
if ($r === 'werkzoekende' && !$magWzk) $r = 'gedeeld';	// alleen recht A

$eigenaar = null;
if ($r === 'werkzoekende') {
	$eigenaar = (int)($_POST['w'] ?? $_GET['w'] ?? 0) ?: null;
}
$lijstWzk = ($r === 'werkzoekende' && $eigenaar === null);

$mapId = (int)($_POST['map'] ?? $_GET['map'] ?? 0) ?: null;
if ($mapId !== null) {
	// De map moet echt bij deze ruimte (en eigenaar) horen; anders terug naar boven.
	$m = DocMap::zoek($mapId);
	if (!$m || $m['ruimte'] !== $r || ($m['eigenaar_id'] === null ? null : (int)$m['eigenaar_id']) !== $eigenaar) {
		$mapId = null;
	}
}
if (!$lijstWzk && !Document::magRuimte($r, $eigenaar, $mijnId)) {
	http_response_code(403);
	exit('Je hebt geen toegang tot deze documenten, of de werkzoekende bestaat niet meer.');
}
$wzk = $r === 'werkzoekende' ? Document::werkzoekende($eigenaar) : null;	// het Werkzoekende-object, of null

/* Het adres van de huidige plek, voor links en de doorverwijzing na een POST. */
function plek (array $extra = []): string
{
	global $r, $eigenaar, $mapId;
	$p = ['r' => $r];
	if ($eigenaar !== null) $p['w'] = $eigenaar;
	if ($mapId !== null) $p['map'] = $mapId;
	$p = array_merge($p, $extra);
	$p = array_filter($p, fn($v) => $v !== null);
	return 'documenten.php?' . http_build_query($p);
}
/* $lijst (optioneel): regels die onder de melding als opsomming verschijnen. */
function melding (string $soort, string $tekst, array $lijst = []): void
{
	$_SESSION['doc_melding'][] = [$soort, $tekst, $lijst];
}

/* ---------- Uploaden (bestanden en hele mappen) ----------
 * Het script onderaan de pagina verstuurt de bestanden in porties (fetch),
 * omdat PHP maar een beperkt aantal bestanden per keer aanneemt
 * (max_file_uploads, meestal 20) en een map er honderden kan bevatten.
 *   actie=upload        één portie: bestanden[] met paden[] ("Cursus/2026/brief.pdf")
 *   actie=upload_klaar  het einde: zet de meldingen klaar en het script herlaadt de pagina
 * Alle verzoeken van één upload hebben hetzelfde upload_id; de tussenstand
 * (aantal gelukt, lijst van wat niet lukte) staat zolang in de sessie.
 * Antwoord is JSON, geen doorverwijzing. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['actie'] ?? '', ['upload', 'upload_klaar'], true)) {
	if ($lijstWzk) { http_response_code(400); exit('Kies eerst een werkzoekende.'); }
	$uid = preg_replace('/[^a-z0-9]/', '', (string)($_POST['upload_id'] ?? '')) ?: 'x';
	$stand = $_SESSION['doc_upload'][$uid] ?? ['gelukt' => 0, 'niet' => []];

	if ($_POST['actie'] === 'upload') {
		$bestanden = Document::normaliseerFiles($_FILES['bestanden'] ?? []);
		$paden = (array)($_POST['paden'] ?? []);
		$cache = [];
		foreach ($bestanden as $i => $b) {
			$pad = Document::schoonPad((string)($paden[$i] ?? $b['name']));
			$doel = DocMap::maakPad($r, $eigenaar, $mapId, $pad, $mijnId, $cache);
			$fout = Document::upload($b, $r, $eigenaar, $doel, $mijnId);
			if ($fout === null) $stand['gelukt']++; else $stand['niet'][] = "$pad: $fout";
		}
		$_SESSION['doc_upload'][$uid] = $stand;
	} else {
		unset($_SESSION['doc_upload'][$uid]);
		// Wat de browser zelf al heeft overgeslagen (verkeerde soort, te groot) of niet kon versturen
		$overgeslagen = json_decode((string)($_POST['overgeslagen'] ?? '[]'), true);
		foreach (is_array($overgeslagen) ? $overgeslagen : [] as $o) {
			if (is_array($o) && count($o) === 2) {
				$stand['niet'][] = Document::schoonPad((string)$o[0]) . ': ' . mb_substr((string)$o[1], 0, 200);
			}
		}
		$g = $stand['gelukt'];
		$n = count($stand['niet']);
		if ($g > 0) melding('success', $g == 1 ? 'Er is 1 document toegevoegd.' : "Er zijn $g documenten toegevoegd.");
		if ($n > 0) melding('warning', $n == 1 ? 'Dit bestand is niet toegevoegd:' : "Deze $n bestanden zijn niet toegevoegd:", $stand['niet']);
		if ($g == 0 && $n == 0) melding('info', 'Er was niets om toe te voegen.');
	}
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(['ok' => true]);
	exit();
}

/* ---------- Overige acties (POST) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$actie = (string)($_POST['actie'] ?? '');
	$id    = (int)($_POST['id'] ?? 0);
	if ($lijstWzk) $actie = '';		// in de lijst van werkzoekenden valt niets te wijzigen

	switch ($actie) {

		case 'nieuwe_map':
			$naam = DocMap::schoneNaam((string)($_POST['naam'] ?? ''));
			if ($naam === null) { melding('danger', 'Geef de map een naam.'); break; }
			DocMap::maak($r, $eigenaar, $mapId, $naam, $mijnId);
			melding('success', 'De map "' . $naam . '" is gemaakt.');
			break;

		case 'hernoem_map':
		case 'verwijder_map':
			$m = DocMap::zoek($id);
			if (!$m || !Document::magWijzigen($m, $mijnId)) { melding('danger', 'Dat mag je niet bij deze map.'); break; }
			if ($actie === 'hernoem_map') {
				$naam = DocMap::schoneNaam((string)($_POST['naam'] ?? ''));
				if ($naam === null) { melding('danger', 'Geef de map een naam.'); break; }
				DocMap::hernoem($id, $naam);
				melding('success', 'De naam van de map is gewijzigd.');
			} elseif (!DocMap::isLeeg($id)) {
				melding('warning', 'De map "' . $m['naam'] . '" is niet leeg. Verwijder of verplaats eerst wat erin staat.');
			} else {
				DocMap::verwijder($id);
				melding('success', 'De map "' . $m['naam'] . '" is verwijderd.');
			}
			break;

		case 'hernoem_doc':
		case 'verwijder_doc':
			$d = Document::zoek($id);
			if (!$d || !Document::magWijzigen($d, $mijnId)) { melding('danger', 'Dat mag je niet bij dit document.'); break; }
			if ($actie === 'hernoem_doc') {
				$naam = Document::schoneNaam((string)($_POST['naam'] ?? ''));
				if ($naam === null) { melding('danger', 'Geef het document een naam.'); break; }
				// Extensie vergeten? Dan de oude erachter zetten, anders opent het bestand later niet.
				if (strtolower(pathinfo($naam, PATHINFO_EXTENSION)) !== $d['extensie']) $naam .= '.' . $d['extensie'];
				Document::hernoem($id, $naam);
				melding('success', 'De naam is gewijzigd.');
			} else {
				Document::verwijder($d);
				melding('success', '"' . $d['naam'] . '" is verwijderd.');
			}
			break;
	}
	header('Location: ' . plek());
	exit();
}

/* ---------- Gegevens voor de pagina ---------- */
$meldingen = $_SESSION['doc_melding'] ?? [];
unset($_SESSION['doc_melding']);

$opslagOk = Document::opslagmap() !== null;
$zoek     = trim((string)($_GET['q'] ?? ''));
$mappen = $docs = $pad = $wzkLijst = $aantallen = [];
if ($lijstWzk) {
	$wzkLijst  = Document::werkzoekendenVoor($mijnId);		// Werkzoekende-objecten
	$aantallen = Document::aantallenPerWerkzoekende();		// [werkzkd.id => aantal documenten]
} elseif ($zoek !== '') {
	$docs = Document::zoekOpNaam($r, $eigenaar, $zoek);
} else {
	$mappen = DocMap::lijst($r, $eigenaar, $mapId);
	$docs   = Document::lijst($r, $eigenaar, $mapId);
	$pad    = DocMap::pad($mapId);
}
$extensies = Instelling::extensies();
$maxBytes  = Instelling::maxBytes();

$wzkNaam     = $wzk ? Document::naamVan($wzk) : '';
$titelRuimte = $r === 'gedeeld' ? 'Gedeelde documenten' : ($wzk ? 'Documenten van ' . $wzkNaam : 'Werkzoekenden');
?>
<!DOCTYPE html>
<html lang="nl-NL">
<head>
	<?php include('includes/head.php'); ?>
	<?php if ($lijstWzk): /* bootstrap-table, dezelfde versie als in het WAS-overzicht */ ?>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-table@1.27.1/dist/bootstrap-table.min.css">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.27.1/dist/bootstrap-table.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.27.1/dist/locale/bootstrap-table-nl-NL.min.js"></script>
	<?php endif; ?>
	<style>
		.bluefont { color: #304280; font-weight: 300; }
		.doc-tabel td { vertical-align: middle; }
		.doc-tabel .icoon { width: 2.2em; font-size: 1.4em; text-align: center; }
		.doc-tabel a.naam { font-size: 1.05rem; }
		.sleepvak { border: 3px dashed #c5cde0; border-radius: 10px; padding: 1.2rem; text-align: center;
			color: #5a6378; background: #f8f9fc; transition: background .15s, border-color .15s; }
		.sleepvak.actief { background: #e3ecff; border-color: #304280; }
		.melding-lijst { max-height: 18rem; overflow-y: auto; padding-left: 1.2rem; }
		#wzk-tabel td { padding: .15rem .35rem; line-height: 1.3rem; font-size: .875rem; vertical-align: middle; }	/* zoals in het WAS */
		.knoppen-rij .btn { margin: 0 .5rem .5rem 0; }
		.acties form, .acties button { display: inline; }
		.acties .btn-link { padding: 0 .35rem; }
	</style>
</head>
<body class="bodystyle">
<?php include('includes/navbar.php'); ?>
<main role="main">
<div class="container mb-5">
	<h1 class="display-4 bluefont p-3 mt-md-5">Documenten</h1>

	<ul class="nav nav-tabs mb-3">
		<?php if ($magGedeeld): ?>
		<li class="nav-item"><a class="nav-link <?= $r === 'gedeeld' ? 'active' : '' ?>" href="documenten.php?r=gedeeld"><i class="fa-solid fa-users"></i> Gedeeld</a></li>
		<?php endif; ?>
		<?php if ($magWzk): ?>
		<li class="nav-item"><a class="nav-link <?= $r === 'werkzoekende' ? 'active' : '' ?>" href="documenten.php?r=werkzoekende"><i class="fa-regular fa-address-card"></i> Werkzoekenden</a></li>
		<?php endif; ?>
		<?php if ($beheerder): ?>
		<li class="nav-item ml-auto"><a class="nav-link" href="documenten_beheer.php"><i class="fa-solid fa-sliders"></i> Instellingen</a></li>
		<?php endif; ?>
	</ul>

	<?php foreach ($meldingen as $m): $lijst = $m[2] ?? []; ?>
		<div class="alert alert-<?= h($m[0]) ?> alert-dismissible">
			<?= h($m[1]) ?><button type="button" class="close" data-dismiss="alert">&times;</button>
			<?php if ($lijst): ?>
			<ul class="melding-lijst mb-0 mt-2">
				<?php foreach ($lijst as $regel): ?><li><?= h($regel) ?></li><?php endforeach; ?>
			</ul>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>

	<?php if (!$opslagOk): ?>
		<div class="alert alert-danger">De documentenmap op de server is niet ingesteld of niet beschrijfbaar
			(DOC_DIR in includes/configDB.php). Uploaden lukt pas als dat in orde is.</div>
	<?php endif; ?>

<?php if ($lijstWzk): /* ====================== LIJST VAN WERKZOEKENDEN ====================== */ ?>
	<h4 class="mb-2">Werkzoekenden</h4>
	<p class="text-muted mb-3">
		<?php if ($beheerder || heeft_recht('documenten_werkzoekenden')): ?>
			Alle werkzoekenden. Klik op een naam voor de documenten van die werkzoekende (cv, brieven, afspraken).
		<?php else: ?>
			De werkzoekenden die jij begeleidt. Klik op een naam voor de documenten (cv, brieven, afspraken).
			Die zijn ook te zien voor de coördinatoren.
		<?php endif; ?>
	</p>
	<?php if (!$wzkLijst): ?>
		<p>Er zijn (nog) geen werkzoekenden aan jou gekoppeld. Klopt dat niet? Vraag het aan een coördinator.</p>
	<?php else: ?>
		<!-- Zelfde opmaak als het overzicht werkzoekenden in het WAS (beheerwas/overz_werkzoekenden.php):
		     bootstrap-table met zoekvak en pagina's, compacte gestreepte rijen, donkere kop. -->
		<table id="wzk-tabel" class="data-table table-sm table-striped table-hover"
			   data-toggle="table" data-search="true" data-pagination="true"
			   data-page-size="30" data-page-list="20, 40, 60, 80, all">
			<thead class="thead-dark">
				<tr>
					<th data-field="id" data-visible="false">id</th>
					<th data-field="naam" data-sortable="true" data-formatter="naamLink" data-sorter="sorteerTekst">naam</th>
					<th data-field="status" data-sortable="true" data-sorter="sorteerTekst" class="text-center">status</th>
					<th data-field="documenten" data-sortable="true" data-sorter="sorteerGetal" class="text-center">documenten</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($wzkLijst as $w): $n = $aantallen[(int)$w->id] ?? 0;
				// Zoals in het WAS: "achternaam, voornaam tussenvoegsels" (de lijst is gesorteerd op achternaam)
				$naam = trim($w->achternaam . ', ' . trim($w->voornaam . ' ' . $w->tussenvoegsels), ', ');
				// Actief of niet-actief, met dezelfde grens als in het WAS (zie Document::isActief).
				// De omschrijving van de status (bijv. "uitgeschreven") staat in de tooltip.
				$actief = Document::isActief($w); ?>
				<tr<?= $actief ? '' : ' class="text-muted"' ?>>
					<td><?= (int)$w->id ?></td>
					<td class="text-left"><?= h($naam) ?></td>
					<td class="text-center" title="<?= h(Tools::getStatusOms($w->status)) ?>"><?= $actief
						? '<span class="badge badge-success">actief</span>'
						: '<span class="badge badge-secondary">niet-actief</span>' ?></td>
					<td class="text-center"><?= $n ?: '' ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<script>
		// In de cel staat alleen de naam, zodat sorteren en zoeken op de naam gaan.
		// (Stond de link zelf in de cel, dan sorteerde bootstrap-table op de HTML van de link,
		// en die verschilt alleen in het nummer van de werkzoekende.)
		// De link maakt bootstrap-table pas bij het tonen, met deze "formatter".
		// value is de inhoud van de cel zoals PHP hem schreef, dus al met h() veilig gemaakt.
		function naamLink (value, row) {
			return '<a href="documenten.php?r=werkzoekende&amp;w=' + parseInt(row.id, 10) + '">' + value + '</a>';
		}
		// Sorteren op naam zoals in een Nederlandse lijst: hoofdletters en é tellen niet mee
		function sorteerTekst (a, b) { return String(a).localeCompare(String(b), 'nl', {sensitivity: 'base'}); }
		// Aantal documenten als getal (een lege cel telt als 0), anders komt 10 vóór 2
		function sorteerGetal (a, b) { return (parseInt(a, 10) || 0) - (parseInt(b, 10) || 0); }
		</script>
	<?php endif; ?>

<?php else: /* ====================== GEDEELD OF ÉÉN WERKZOEKENDE ====================== */ ?>

	<h4 class="mb-2"><?= h($titelRuimte) ?></h4>
	<p class="text-muted mb-3">
		<?php if ($r === 'gedeeld'): ?>
			Deze documenten zijn te zien voor alle coördinatoren. Alleen wie een document heeft toegevoegd, kan het verwijderen of een andere naam geven.
		<?php else: ?>
			Deze documenten zijn te zien voor het maatje van <?= h($wzkNaam) ?> en voor de coördinatoren.
			Alleen wie een document heeft toegevoegd, kan het verwijderen. Als <?= h($wzkNaam) ?> uit het systeem
			wordt verwijderd, gaan deze documenten ook weg.
			<?php if (heeft_recht('was_lezen')): ?><a href="beheerwas/mut_persoon.php?id=<?= (int)$eigenaar ?>">Naar de gegevens in het WAS</a>.<?php endif; ?>
		<?php endif; ?>
	</p>

	<!-- Kruimelpad: waar ben ik? -->
	<nav aria-label="Waar ben je">
		<ol class="breadcrumb bg-light">
			<?php if ($r === 'werkzoekende'): ?>
			<li class="breadcrumb-item"><a href="documenten.php?r=werkzoekende"><i class="fa-regular fa-address-card"></i> Werkzoekenden</a></li>
			<li class="breadcrumb-item"><a href="<?= h(plek(['map' => null, 'q' => null])) ?>"><?= h($wzkNaam) ?></a></li>
			<?php else: ?>
			<li class="breadcrumb-item"><a href="<?= h(plek(['map' => null, 'q' => null])) ?>"><i class="fa-solid fa-house"></i> <?= h($titelRuimte) ?></a></li>
			<?php endif; ?>
			<?php foreach ($pad as $i => $p): ?>
				<?php if ($i === count($pad) - 1 && $zoek === ''): ?>
					<li class="breadcrumb-item active"><?= h($p['naam']) ?></li>
				<?php else: ?>
					<li class="breadcrumb-item"><a href="<?= h(plek(['map' => (int)$p['id']])) ?>"><?= h($p['naam']) ?></a></li>
				<?php endif; ?>
			<?php endforeach; ?>
			<?php if ($zoek !== ''): ?><li class="breadcrumb-item active">Zoeken naar "<?= h($zoek) ?>"</li><?php endif; ?>
		</ol>
	</nav>

	<?php if ($zoek === ''): ?>
	<!-- Toevoegen: knoppen of slepen. Het versturen doet het script onderaan (in porties). -->
	<form id="uploadform" method="POST" action="documenten.php" enctype="multipart/form-data">
		<?= csrf_veld() ?>
		<input type="hidden" name="actie" value="upload">
		<input type="hidden" name="r" value="<?= h($r) ?>">
		<input type="hidden" name="w" value="<?= (int)$eigenaar ?>">
		<input type="hidden" name="map" value="<?= (int)$mapId ?>">
		<input type="file" id="bestanden" multiple class="d-none"
			   accept="<?= h(implode(',', array_map(fn($e) => '.' . $e, $extensies))) ?>">
		<!-- webkitdirectory: de browser laat een map kiezen in plaats van losse bestanden
		     (werkt in alle gangbare browsers, ondanks het "webkit" in de naam) -->
		<input type="file" id="mapkeuze" webkitdirectory multiple class="d-none">
		<div class="sleepvak mb-3" id="sleepvak">
			<div class="knoppen-rij">
				<button type="button" class="btn btn-primary btn-lg upload-knop" onclick="document.getElementById('bestanden').click();" <?= $opslagOk ? '' : 'disabled' ?>>
					<i class="fa-solid fa-upload"></i> Document toevoegen</button>
				<button type="button" class="btn btn-primary btn-lg upload-knop" onclick="document.getElementById('mapkeuze').click();" <?= $opslagOk ? '' : 'disabled' ?>>
					<i class="fa-solid fa-folder-open"></i> Map toevoegen</button>
				<button type="button" class="btn btn-outline-primary btn-lg upload-knop" onclick="nieuweMap();">
					<i class="fa-solid fa-folder-plus"></i> Nieuwe map</button>
			</div>
			<div>of sleep bestanden of een map naar dit vak</div>
			<div id="bezig" class="mt-2 d-none"><i class="fa-solid fa-spinner fa-spin"></i> <span id="bezig-tekst">Bezig met toevoegen, even geduld...</span></div>
		</div>
	</form>
	<?php endif; ?>

	<form class="form-inline mb-3" method="GET" action="documenten.php">
		<input type="hidden" name="r" value="<?= h($r) ?>">
		<?php if ($eigenaar !== null): ?><input type="hidden" name="w" value="<?= (int)$eigenaar ?>"><?php endif; ?>
		<input type="search" name="q" class="form-control mr-2" placeholder="Zoek op naam" value="<?= h($zoek) ?>">
		<button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i> Zoeken</button>
		<?php if ($zoek !== ''): ?><a class="btn btn-link" href="<?= h(plek(['map' => null])) ?>">Stoppen met zoeken</a><?php endif; ?>
	</form>

	<table class="table table-hover doc-tabel">
		<thead class="thead-light"><tr>
			<th class="icoon"></th><th>Naam</th><th class="d-none d-md-table-cell">Datum</th>
			<th class="d-none d-md-table-cell">Toegevoegd door</th>
			<th class="text-right d-none d-sm-table-cell">Grootte</th><th></th>
		</tr></thead>
		<tbody>
		<?php if ($mapId !== null && $zoek === ''):
			$boven = end($pad); $bovenId = $boven['parent_id'] === null ? null : (int)$boven['parent_id']; ?>
			<tr><td class="icoon"><i class="fa-solid fa-turn-up text-secondary"></i></td>
				<td colspan="5"><a href="<?= h(plek(['map' => $bovenId])) ?>">Terug naar boven</a></td></tr>
		<?php endif; ?>

		<?php foreach ($mappen as $m): ?>
			<tr>
				<td class="icoon"><i class="fa-solid fa-folder" style="color: #e0a800;"></i></td>
				<td><a class="naam" href="<?= h(plek(['map' => (int)$m['id']])) ?>"><?= h($m['naam']) ?></a></td>
				<td class="d-none d-md-table-cell"></td>
				<td class="d-none d-md-table-cell"></td>
				<td class="d-none d-sm-table-cell"></td>
				<td class="text-right acties text-nowrap">
				<?php if (Document::magWijzigen($m, $mijnId)): ?>
					<button type="button" class="btn btn-link" title="Andere naam" onclick='hernoem("hernoem_map", <?= (int)$m['id'] ?>, <?= h(json_encode($m['naam'])) ?>);'><i class="fa-regular fa-pen-to-square"></i></button>
					<?= verwijderknop(plek(), ['actie' => 'verwijder_map', 'id' => (int)$m['id']], 'De map "' . $m['naam'] . '" verwijderen? (Dat kan alleen als hij leeg is.)') ?>
				<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>

		<?php foreach ($docs as $d):
			$bekijk = in_array($d['extensie'], ['pdf', 'jpg', 'jpeg', 'png'], true); ?>
			<tr>
				<td class="icoon"><i class="<?= h(Document::icoon($d['extensie'])) ?>"></i></td>
				<td><a class="naam" href="document.php?id=<?= (int)$d['id'] ?><?= $bekijk ? '&amp;bekijk=1' : '' ?>"<?= $bekijk ? ' target="_blank"' : '' ?>><?= h($d['naam']) ?></a></td>
				<td class="d-none d-md-table-cell text-nowrap"><?= h(date('d-m-Y', strtotime($d['gemaakt_op']))) ?></td>
				<td class="d-none d-md-table-cell"><?= h($d['door_naam']) ?></td>
				<td class="text-right d-none d-sm-table-cell text-nowrap"><?= h(Document::mb((int)$d['grootte'])) ?></td>
				<td class="text-right acties text-nowrap">
					<a class="btn btn-link" title="Downloaden" href="document.php?id=<?= (int)$d['id'] ?>"><i class="fa-solid fa-download"></i></a>
				<?php if (Document::magWijzigen($d, $mijnId)): ?>
					<button type="button" class="btn btn-link" title="Andere naam" onclick='hernoem("hernoem_doc", <?= (int)$d['id'] ?>, <?= h(json_encode($d['naam'])) ?>);'><i class="fa-regular fa-pen-to-square"></i></button>
					<?= verwijderknop(plek(), ['actie' => 'verwijder_doc', 'id' => (int)$d['id']], '"' . $d['naam'] . '" verwijderen? Dit kan niet ongedaan worden gemaakt.') ?>
				<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>

		<?php if (!$mappen && !$docs): ?>
			<tr><td></td><td colspan="5" class="text-muted"><?= $zoek !== '' ? 'Niets gevonden.' : 'Hier staat nog niets.' ?></td></tr>
		<?php endif; ?>
		</tbody>
	</table>

	<p class="small text-muted">
		Toegestaan: <?= h(implode(', ', $extensies)) ?>. Maximaal <?= h(Document::mb($maxBytes)) ?> per bestand.
		<?php if ($r === 'gedeeld'): ?>De gedeelde map gebruikt <?= h(Document::mb(Document::gebruiktGedeeld())) ?> van de <?= h(Document::mb(Instelling::gedeeldBytes())) ?>.
		<?php elseif (!$beheerder): ?>Je hebt bij werkzoekenden <?= h(Document::mb(Document::gebruikt($mijnId))) ?> van de <?= h(Document::mb(Instelling::quotumBytes())) ?> in gebruik.<?php endif; ?>
	</p>

	<!-- Verborgen formulier voor "Nieuwe map" en "Andere naam" -->
	<form id="actieform" method="POST" action="documenten.php" class="d-none">
		<?= csrf_veld() ?>
		<input type="hidden" name="r" value="<?= h($r) ?>">
		<input type="hidden" name="w" value="<?= (int)$eigenaar ?>">
		<input type="hidden" name="map" value="<?= (int)$mapId ?>">
		<input type="hidden" name="actie"><input type="hidden" name="id"><input type="hidden" name="naam">
	</form>

	<script>
	// De grenzen uit de instellingen, zodat we al in de browser kunnen waarschuwen
	// (de server controleert het daarna nog een keer).
	const MAX_BYTES = <?= (int)$maxBytes ?>;
	const EXTENSIES = <?= json_encode($extensies) ?>;
	// Hoeveel bestanden en bytes er per verzoek mee mogen (PHP: max_file_uploads en post_max_size).
	// Ruim eronder blijven: het verzoek bevat naast de bestanden ook de formuliervelden.
	const PORTIE_AANTAL = <?= max(1, min(20, (int)ini_get('max_file_uploads') ?: 20)) ?>;
	const PORTIE_BYTES  = <?= (int)(Instelling::phpMaxBytes() * 0.9) ?>;
	// Systeembestanden van Mac en Windows in mappen: stil overslaan, die horen niet in de lijst
	const STIL_OVERSLAAN = ['thumbs.db', 'desktop.ini'];

	function verstuurActie (actie, id, naam) {
		const f = document.getElementById('actieform');
		f.actie.value = actie; f.id.value = id || ''; f.naam.value = naam || '';
		f.submit();
	}
	function nieuweMap () {
		const naam = prompt('Hoe moet de nieuwe map heten?');
		if (naam && naam.trim() !== '') verstuurActie('nieuwe_map', '', naam);
	}
	function hernoem (actie, id, huidig) {
		const naam = prompt('Nieuwe naam:', huidig);
		if (naam && naam.trim() !== '' && naam !== huidig) verstuurActie(actie, id, naam);
	}

	/* ---------- Uploaden ----------
	 * Alles wat geüpload wordt, wordt eerst een lijst van {file, pad}. pad is de plek
	 * binnen een gekozen of gesleepte map ("Cursus/2026/brief.pdf"), of alleen de
	 * naam bij losse bestanden. De server maakt de mappen uit het pad na.
	 */
	function mb (bytes) { return (bytes / 1048576).toLocaleString('nl-NL', {maximumFractionDigits: 1}) + ' MB'; }

	// Losse bestanden of een map via de knop. Bij een map vult de browser webkitRelativePath.
	function uitKeuze (files) {
		return Array.from(files).map(f => ({file: f, pad: f.webkitRelativePath || f.name}));
	}

	// Slepen. Een gesleepte map zit niet in dataTransfer.files; die moet je zelf
	// doorlopen via webkitGetAsEntry(). Let op: de items zijn alleen tijdens het
	// drop-moment zelf leesbaar, daarom eerst (zonder wachten) de entries ophalen.
	async function uitSleep (dt) {
		const items   = Array.from(dt.items || []).filter(i => i.kind === 'file');
		const entries = items.map(i => i.webkitGetAsEntry ? i.webkitGetAsEntry() : null).filter(e => e);
		const reserve = uitKeuze(dt.files);			// voor browsers zonder webkitGetAsEntry
		if (!entries.length) return reserve;
		const lijst = [];
		async function doorloop (entry, pad) {
			if (entry.isFile) {
				const f = await new Promise((ok, nok) => entry.file(ok, nok));
				lijst.push({file: f, pad: pad + f.name});
			} else if (entry.isDirectory) {
				const lezer = entry.createReader();
				let stuk;
				do {	// readEntries geeft de inhoud in stukken (vaak 100 per keer): doorgaan tot het leeg is
					stuk = await new Promise((ok, nok) => lezer.readEntries(ok, nok));
					for (const e of stuk) await doorloop(e, pad + entry.name + '/');
				} while (stuk.length);
			}
		}
		for (const e of entries) await doorloop(e, '');
		return lijst;
	}

	function bezig (aan, tekst) {
		document.getElementById('bezig').classList.toggle('d-none', !aan);
		if (tekst) document.getElementById('bezig-tekst').textContent = tekst;
		document.querySelectorAll('.upload-knop').forEach(k => k.disabled = aan);
		// Waarschuwen als iemand tijdens het uploaden de pagina wil verlaten
		window.onbeforeunload = aan ? () => true : null;
	}

	// Stuurt één verzoek naar de server. Geeft true als het is aangekomen.
	async function verstuur (velden, bestanden) {
		const fd = new FormData(document.getElementById('uploadform'));	// csrf_token, r, w, map
		for (const [k, v] of Object.entries(velden)) fd.set(k, v);
		for (const b of bestanden) { fd.append('bestanden[]', b.file, b.file.name); fd.append('paden[]', b.pad); }
		try {
			const antwoord = await fetch('documenten.php', {method: 'POST', body: fd, credentials: 'same-origin'});
			return antwoord.ok;
		} catch (e) {
			return false;
		}
	}

	async function uploadAlles (lijst) {
		// 1. Controleren in de browser: wat niet mag, gaat niet mee maar komt in de lijst
		const goed = [], overgeslagen = [];
		for (const b of lijst) {
			const naam = b.file.name.toLowerCase();
			if (naam.startsWith('.') || STIL_OVERSLAAN.includes(naam)) continue;
			const ext = naam.includes('.') ? naam.split('.').pop() : '';
			if (!EXTENSIES.includes(ext))      overgeslagen.push([b.pad, 'dit soort bestand is niet toegestaan']);
			else if (b.file.size === 0)        overgeslagen.push([b.pad, 'het bestand is leeg']);
			else if (b.file.size > MAX_BYTES)  overgeslagen.push([b.pad, 'te groot (' + mb(b.file.size) + ', maximaal ' + mb(MAX_BYTES) + ')']);
			else goed.push(b);
		}

		// 2. In porties verdelen
		const porties = [];
		let portie = [], bytes = 0;
		for (const b of goed) {
			if (portie.length && (portie.length >= PORTIE_AANTAL || bytes + b.file.size > PORTIE_BYTES)) {
				porties.push(portie); portie = []; bytes = 0;
			}
			portie.push(b); bytes += b.file.size;
		}
		if (portie.length) porties.push(portie);

		// 3. Versturen, portie na portie. Lukt een portie niet, dan komen die bestanden in de lijst.
		const uploadId = Date.now().toString(36) + Math.random().toString(36).slice(2);
		let verstuurd = 0;
		bezig(true, 'Bezig met toevoegen...');
		for (const p of porties) {
			bezig(true, 'Bezig met toevoegen: ' + verstuurd + ' van ' + goed.length + ' bestanden...');
			if (!await verstuur({actie: 'upload', upload_id: uploadId}, p)) {
				p.forEach(b => overgeslagen.push([b.pad, 'kon niet worden verstuurd (verbinding of sessie verlopen?)']));
			}
			verstuurd += p.length;
		}

		// 4. Afronden: de server zet de meldingen klaar (met de lijst), daarna de pagina opnieuw laden
		bezig(true, 'Bijna klaar...');
		const ok = await verstuur({actie: 'upload_klaar', upload_id: uploadId, overgeslagen: JSON.stringify(overgeslagen)}, []);
		bezig(false);
		if (!ok) alert('Het toevoegen is klaar, maar het overzicht kon niet worden opgehaald. Kijk in de lijst of alles er staat.');
		window.location.replace(window.location.href);
	}

	const vak = document.getElementById('sleepvak');
	if (vak) {
		for (const id of ['bestanden', 'mapkeuze']) {
			const invoer = document.getElementById(id);
			invoer.addEventListener('change', () => {
				if (invoer.files.length) uploadAlles(uitKeuze(invoer.files));
				invoer.value = '';		// zodat dezelfde keuze nog eens kan
			});
		}
		// Slepen: standaard opent de browser een gesleept bestand zelf; preventDefault() voorkomt dat.
		['dragenter', 'dragover'].forEach(e => vak.addEventListener(e, ev => { ev.preventDefault(); vak.classList.add('actief'); }));
		['dragleave', 'drop'].forEach(e => vak.addEventListener(e, ev => { ev.preventDefault(); vak.classList.remove('actief'); }));
		vak.addEventListener('drop', async ev => {
			if (document.querySelector('.upload-knop').disabled) return;	// er loopt al een upload
			const lijst = await uitSleep(ev.dataTransfer);
			if (lijst.length) uploadAlles(lijst);
		});
	}
	</script>
<?php endif; ?>
</div>
</main>
</body>
</html>
