<?php
/* ------------------------------------------------------------------
 * Documenten (okt 2026)
 *
 * Tabbladen:
 *   ?r=eigen     Mijn documenten        (recht documenten_eigen, niveau 2-9)
 *   ?r=gedeeld   Gedeelde documenten    (recht documenten_gedeeld, niveau 4-9)
 *   ?r=beheer    Overzicht per persoon  (alleen beheerder)
 * Verder: ?map=12 opent een map, ?q=tekst zoekt, en de beheerder kan met
 * ?r=eigen&u=7 de documenten van gebruiker 7 bekijken.
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

require_recht('documenten_eigen');
$curr_user = new User('id', $_SESSION['userid']);
$mijnId    = (int)$_SESSION['userid'];
$beheerder = heeft_recht('beheerder');

/* ---------- Welke ruimte, van wie, en welke map? ---------- */
$r = (string)($_POST['r'] ?? $_GET['r'] ?? 'eigen');
if (!in_array($r, ['eigen', 'gedeeld', 'beheer'], true)) $r = 'eigen';
if ($r === 'gedeeld' && !heeft_recht('documenten_gedeeld')) $r = 'eigen';
if ($r === 'beheer'  && !$beheerder) $r = 'eigen';

$eigenaar = null;
if ($r === 'eigen') {
	$u = (int)($_POST['u'] ?? $_GET['u'] ?? 0);
	$eigenaar = ($beheerder && $u > 0) ? $u : $mijnId;
}
$mapId = (int)($_POST['map'] ?? $_GET['map'] ?? 0) ?: null;
if ($mapId !== null && $r !== 'beheer') {
	// De map moet echt bij deze ruimte (en eigenaar) horen; anders terug naar boven.
	$m = DocMap::zoek($mapId);
	if (!$m || $m['ruimte'] !== $r || ($m['eigenaar_id'] === null ? null : (int)$m['eigenaar_id']) !== $eigenaar) {
		$mapId = null;
	}
}
if ($r !== 'beheer' && !Document::magRuimte($r, $eigenaar, $mijnId)) {
	http_response_code(403);
	exit('Je hebt geen toegang tot deze documenten.');
}

/* Het adres van de huidige plek, voor links en de doorverwijzing na een POST. */
function plek (array $extra = []): string
{
	global $r, $eigenaar, $mapId, $mijnId;
	$p = ['r' => $r];
	if ($r === 'eigen' && $eigenaar !== $mijnId) $p['u'] = $eigenaar;
	if ($mapId !== null) $p['map'] = $mapId;
	$p = array_merge($p, $extra);
	$p = array_filter($p, fn($v) => $v !== null);
	return 'documenten.php?' . http_build_query($p);
}
function melding (string $soort, string $tekst): void
{
	$_SESSION['doc_melding'][] = [$soort, $tekst];
}

/* ---------- Acties (POST) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$actie = (string)($_POST['actie'] ?? '');
	$id    = (int)($_POST['id'] ?? 0);

	switch ($actie) {
		case 'upload':
			$bestanden = Document::normaliseerFiles($_FILES['bestanden'] ?? []);
			$gelukt = 0;
			foreach ($bestanden as $b) {
				$fout = Document::upload($b, $r, $eigenaar, $mapId, $mijnId);
				if ($fout === null) $gelukt++; else melding('danger', $fout);
			}
			if ($gelukt > 0) melding('success', $gelukt == 1 ? 'Het document is toegevoegd.' : "$gelukt documenten zijn toegevoegd.");
			break;

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

		case 'verhuis':		// beheerder: alle eigen documenten van een (oud-)gebruiker naar zichzelf
			require_recht('beheerder');
			$van = (int)($_POST['van'] ?? 0);
			if ($van > 0 && $van !== $mijnId) {
				$naam = trim((string)($_POST['naam'] ?? '')) ?: "gebruiker $van";
				Document::verhuisNaar($van, $mijnId, DocMap::schoneNaam("Van $naam") ?? "Van gebruiker $van");
				melding('success', "De documenten van $naam staan nu bij jouw documenten, in de map \"Van $naam\".");
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
$mappen = $docs = $pad = [];
if ($r !== 'beheer') {
	if ($zoek !== '') {
		$docs = Document::zoekOpNaam($r, $eigenaar, $zoek);
	} else {
		$mappen = DocMap::lijst($r, $eigenaar, $mapId);
		$docs   = Document::lijst($r, $eigenaar, $mapId);
		$pad    = DocMap::pad($mapId);
	}
}
$extensies = Instelling::extensies();
$maxBytes  = Instelling::maxBytes();
$quotum    = Instelling::quotumBytes();
$gebruikt  = Document::gebruikt($mijnId);

// Naam van de eigenaar als de beheerder bij iemand anders kijkt
$andereNaam = '';
if ($r === 'eigen' && $eigenaar !== $mijnId) {
	$ander = new User('histid', $eigenaar);	// histid: ook als de persoon als verwijderd is gemarkeerd
	$andereNaam = $ander->id ? trim($ander->voornaam . ' ' . $ander->tussenvoegsels . ' ' . $ander->achternaam) : "gebruiker $eigenaar (bestaat niet meer)";
}
$titelRuimte = $r === 'gedeeld' ? 'Gedeelde documenten' : ($andereNaam !== '' ? 'Documenten van ' . $andereNaam : 'Mijn documenten');
?>
<!DOCTYPE html>
<html lang="nl-NL">
<head>
	<?php include('includes/head.php'); ?>
	<style>
		.bluefont { color: #304280; font-weight: 300; }
		.doc-tabel td { vertical-align: middle; }
		.doc-tabel .icoon { width: 2.2em; font-size: 1.4em; text-align: center; }
		.doc-tabel a.naam { font-size: 1.05rem; }
		.sleepvak { border: 3px dashed #c5cde0; border-radius: 10px; padding: 1.2rem; text-align: center;
			color: #5a6378; background: #f8f9fc; transition: background .15s, border-color .15s; }
		.sleepvak.actief { background: #e3ecff; border-color: #304280; }
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
		<li class="nav-item"><a class="nav-link <?= $r === 'eigen' && $eigenaar === $mijnId ? 'active' : '' ?>" href="documenten.php?r=eigen"><i class="fa-regular fa-user"></i> Mijn documenten</a></li>
		<?php if (heeft_recht('documenten_gedeeld')): ?>
		<li class="nav-item"><a class="nav-link <?= $r === 'gedeeld' ? 'active' : '' ?>" href="documenten.php?r=gedeeld"><i class="fa-solid fa-users"></i> Gedeeld</a></li>
		<?php endif; ?>
		<?php if ($beheerder): ?>
		<li class="nav-item"><a class="nav-link <?= $r === 'beheer' || $andereNaam !== '' ? 'active' : '' ?>" href="documenten.php?r=beheer"><i class="fa-solid fa-gear"></i> Per persoon</a></li>
		<?php endif; ?>
	</ul>

	<?php foreach ($meldingen as [$soort, $tekst]): ?>
		<div class="alert alert-<?= h($soort) ?> alert-dismissible">
			<?= h($tekst) ?><button type="button" class="close" data-dismiss="alert">&times;</button>
		</div>
	<?php endforeach; ?>

	<?php if (!$opslagOk): ?>
		<div class="alert alert-danger">De documentenmap op de server is niet ingesteld of niet beschrijfbaar
			(DOC_DIR in includes/configDB.php). Uploaden lukt pas als dat in orde is.</div>
	<?php endif; ?>

<?php if ($r === 'beheer'): /* ====================== BEHEERDER: PER PERSOON ====================== */ ?>
	<p>Hier zie je wie er eigen documenten heeft. Klik op een naam om ze te bekijken.
		Stopt iemand? Gebruik dan <strong>Naar mij</strong> vóórdat je het account in phpMyAdmin verwijdert:
		alles komt dan bij jouw documenten, in een map "Van ...". Accounts die al verwijderd zijn, staan bovenaan in het rood.</p>
	<p><a href="documenten_beheer.php" class="btn btn-outline-primary"><i class="fa-solid fa-sliders"></i> Instellingen documenten</a></p>
	<table class="table table-sm table-hover doc-tabel">
		<thead><tr><th>Naam</th><th>Gebruikersnaam</th><th class="text-right">Documenten</th><th class="text-right">Grootte</th><th></th></tr></thead>
		<tbody>
		<?php foreach (Document::overzichtPerGebruiker() as $g):
			$naam = $g['weg'] ? 'gebruiker ' . (int)$g['id'] : ($g['naam'] ?: $g['username']); ?>
			<tr class="<?= $g['weg'] ? 'table-danger' : '' ?>">
				<td><a href="documenten.php?r=eigen&amp;u=<?= (int)$g['id'] ?>"><?= h($naam) ?></a><?= $g['weg'] ? ' <small>(account bestaat niet meer)</small>' : '' ?></td>
				<td><?= h($g['username'] ?? '') ?></td>
				<td class="text-right"><?= (int)$g['aantal'] ?></td>
				<td class="text-right"><?= h(Document::mb((int)$g['grootte'])) ?></td>
				<td class="text-right">
				<?php if ((int)$g['id'] !== $mijnId): ?>
					<form method="POST" action="documenten.php" class="d-inline"
						  onsubmit="return confirm(<?= h(json_encode('Alle eigen documenten van ' . $naam . ' naar jouw documenten verplaatsen?')) ?>);">
						<?= csrf_veld() ?><input type="hidden" name="r" value="beheer"><input type="hidden" name="actie" value="verhuis">
						<input type="hidden" name="van" value="<?= (int)$g['id'] ?>"><input type="hidden" name="naam" value="<?= h($naam) ?>">
						<button class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-arrow-right-to-bracket"></i> Naar mij</button>
					</form>
				<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

<?php else: /* ====================== EIGEN OF GEDEELD ====================== */ ?>

	<h4 class="mb-2"><?= h($titelRuimte) ?></h4>
	<p class="text-muted mb-3">
		<?php if ($r === 'eigen'): ?>Deze documenten kun alleen jij zien.
		<?php else: ?>Deze documenten zijn te zien voor alle collega's met toegang tot de gedeelde map. Alleen wie een document heeft toegevoegd, kan het verwijderen.
		<?php endif; ?>
	</p>

	<!-- Kruimelpad: waar ben ik? -->
	<nav aria-label="Waar ben je">
		<ol class="breadcrumb bg-light">
			<li class="breadcrumb-item"><a href="<?= h(plek(['map' => null, 'q' => null])) ?>"><i class="fa-solid fa-house"></i> <?= h($titelRuimte) ?></a></li>
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
	<!-- Toevoegen: knop of slepen. Het formulier wordt meteen verstuurd zodra er bestanden gekozen zijn. -->
	<form id="uploadform" method="POST" action="documenten.php" enctype="multipart/form-data">
		<?= csrf_veld() ?>
		<input type="hidden" name="actie" value="upload">
		<input type="hidden" name="r" value="<?= h($r) ?>">
		<input type="hidden" name="u" value="<?= $r === 'eigen' ? (int)$eigenaar : 0 ?>">
		<input type="hidden" name="map" value="<?= (int)$mapId ?>">
		<input type="file" name="bestanden[]" id="bestanden" multiple class="d-none"
			   accept="<?= h(implode(',', array_map(fn($e) => '.' . $e, $extensies))) ?>">
		<div class="sleepvak mb-3" id="sleepvak">
			<div class="knoppen-rij">
				<button type="button" class="btn btn-primary btn-lg" onclick="document.getElementById('bestanden').click();" <?= $opslagOk ? '' : 'disabled' ?>>
					<i class="fa-solid fa-upload"></i> Document toevoegen</button>
				<button type="button" class="btn btn-outline-primary btn-lg" onclick="nieuweMap();">
					<i class="fa-solid fa-folder-plus"></i> Nieuwe map</button>
			</div>
			<div>of sleep bestanden naar dit vak</div>
			<div id="bezig" class="mt-2 d-none"><i class="fa-solid fa-spinner fa-spin"></i> Bezig met toevoegen, even geduld...</div>
		</div>
	</form>
	<?php endif; ?>

	<form class="form-inline mb-3" method="GET" action="documenten.php">
		<input type="hidden" name="r" value="<?= h($r) ?>">
		<?php if ($r === 'eigen' && $eigenaar !== $mijnId): ?><input type="hidden" name="u" value="<?= (int)$eigenaar ?>"><?php endif; ?>
		<input type="search" name="q" class="form-control mr-2" placeholder="Zoek op naam" value="<?= h($zoek) ?>">
		<button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i> Zoeken</button>
		<?php if ($zoek !== ''): ?><a class="btn btn-link" href="<?= h(plek(['map' => null])) ?>">Stoppen met zoeken</a><?php endif; ?>
	</form>

	<table class="table table-hover doc-tabel">
		<thead class="thead-light"><tr>
			<th class="icoon"></th><th>Naam</th><th class="d-none d-md-table-cell">Datum</th>
			<?php if ($r === 'gedeeld'): ?><th class="d-none d-md-table-cell">Toegevoegd door</th><?php endif; ?>
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
				<?php if ($r === 'gedeeld'): ?><td class="d-none d-md-table-cell"></td><?php endif; ?>
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
				<?php if ($r === 'gedeeld'): ?><td class="d-none d-md-table-cell"><?= h($d['door_naam']) ?></td><?php endif; ?>
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
		<?php if (!$beheerder): ?>Je gebruikt <?= h(Document::mb($gebruikt)) ?> van de <?= h(Document::mb($quotum)) ?>.<?php endif; ?>
	</p>

	<!-- Verborgen formulier voor "Nieuwe map" en "Andere naam" -->
	<form id="actieform" method="POST" action="documenten.php" class="d-none">
		<?= csrf_veld() ?>
		<input type="hidden" name="r" value="<?= h($r) ?>">
		<input type="hidden" name="u" value="<?= $r === 'eigen' ? (int)$eigenaar : 0 ?>">
		<input type="hidden" name="map" value="<?= (int)$mapId ?>">
		<input type="hidden" name="actie"><input type="hidden" name="id"><input type="hidden" name="naam">
	</form>

	<script>
	// De grenzen uit de instellingen, zodat we al in de browser kunnen waarschuwen
	// (de server controleert het daarna nog een keer).
	const MAX_BYTES = <?= (int)$maxBytes ?>;
	const EXTENSIES = <?= json_encode($extensies) ?>;

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

	// Controleer de gekozen bestanden en verstuur het formulier.
	function controleerEnVerstuur (files) {
		const fouten = [];
		for (const f of files) {
			const ext = f.name.includes('.') ? f.name.split('.').pop().toLowerCase() : '';
			if (!EXTENSIES.includes(ext)) fouten.push(f.name + ': dit soort bestand is niet toegestaan.');
			else if (f.size > MAX_BYTES) fouten.push(f.name + ': te groot (maximaal ' + Math.round(MAX_BYTES / 1048576) + ' MB).');
		}
		if (fouten.length) { alert(fouten.join('\n')); return false; }
		document.getElementById('bezig').classList.remove('d-none');
		document.getElementById('uploadform').submit();
		return true;
	}

	const invoer = document.getElementById('bestanden');
	const vak    = document.getElementById('sleepvak');
	if (invoer && vak) {
		invoer.addEventListener('change', () => { if (invoer.files.length) controleerEnVerstuur(invoer.files); });
		// Slepen: standaard opent de browser een gesleept bestand zelf; preventDefault() voorkomt dat.
		['dragenter', 'dragover'].forEach(e => vak.addEventListener(e, ev => { ev.preventDefault(); vak.classList.add('actief'); }));
		['dragleave', 'drop'].forEach(e => vak.addEventListener(e, ev => { ev.preventDefault(); vak.classList.remove('actief'); }));
		vak.addEventListener('drop', ev => {
			if (!ev.dataTransfer.files.length) return;
			invoer.files = ev.dataTransfer.files;		// de gesleepte bestanden in het formulier zetten
			controleerEnVerstuur(invoer.files);
		});
	}
	</script>
<?php endif; ?>
</div>
</main>
</body>
</html>
