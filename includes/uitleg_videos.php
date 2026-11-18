<?php
/* ------------------------------------------------------------------
 * Instructievideo's (nov 2026)
 *
 * De video's staan in de map video/ (mp4, met een plaatje .jpg als voorvertoning).
 * Ze tonen alleen verzonnen gegevens uit een demo-database, daarom mogen ze
 * gewoon als los bestand op de server staan (geen inlogcontrole nodig).
 *
 * Gebruik:
 *   uitleg_knop('doc1')      een klein vraagteken dat de video in een venster opent
 *   uitleg_venster()         het venster zelf (Bootstrap-modal) + het scriptje; één keer
 *                            onderaan de pagina, ná alle uitleg_knop()-aanroepen
 *   uitleg_videos()          de video's die deze gebruiker mag zien (voor uitleg_documenten.php)
 *
 * 'wie' bepaalt wie een video te zien krijgt:
 *   'documenten'   iedereen die de documentenpagina mag gebruiken
 *   'werkzoekende' wie documenten van werkzoekenden mag zien (recht B of C)
 *   'gedeeld'      wie de gedeelde map mag zien (recht A)
 *   'beheerder'    alleen de beheerder
 * ------------------------------------------------------------------ */

const UITLEG_VIDEOS = [
	'doc1' => ['titel' => 'Een document toevoegen bij een werkzoekende', 'bestand' => 'doc1_toevoegen',    'wie' => 'werkzoekende'],
	'doc2' => ['titel' => 'Een document openen of downloaden',            'bestand' => 'doc2_openen',       'wie' => 'documenten'],
	'doc3' => ['titel' => 'Een map maken en gebruiken',                   'bestand' => 'doc3_map',          'wie' => 'documenten'],
	'doc4' => ['titel' => 'Een andere naam geven of verwijderen',         'bestand' => 'doc4_wijzigen',     'wie' => 'documenten'],
	'doc5' => ['titel' => 'De gedeelde map voor coördinatoren',           'bestand' => 'doc5_gedeeld',      'wie' => 'gedeeld'],
	'doc6' => ['titel' => 'Instellingen voor de beheerder',               'bestand' => 'doc6_instellingen', 'wie' => 'beheerder'],
];

/* Mag de ingelogde gebruiker deze video zien? */
function uitleg_mag(string $sleutel): bool
{
	$wie = UITLEG_VIDEOS[$sleutel]['wie'] ?? null;
	switch ($wie) {
		case 'beheerder':    return heeft_recht('beheerder');
		case 'gedeeld':      return heeft_recht('documenten_gedeeld');
		case 'werkzoekende': return heeft_recht('documenten_eigen') || heeft_recht('documenten_werkzoekenden');
		case 'documenten':   return heeft_recht('documenten_gedeeld') || heeft_recht('documenten_eigen') || heeft_recht('documenten_werkzoekenden');
	}
	return false;	// onbekende sleutel: niets tonen
}

/* Alle video's die deze gebruiker mag zien, in de vaste volgorde. */
function uitleg_videos(): array
{
	return array_filter(UITLEG_VIDEOS, fn($v, $k) => uitleg_mag($k), ARRAY_FILTER_USE_BOTH);
}

/* Een klein rond vraagteken. Klikken opent de video in het venster van uitleg_venster(). */
function uitleg_knop(string $sleutel): string
{
	if (!uitleg_mag($sleutel)) return '';
	$v = UITLEG_VIDEOS[$sleutel];
	return '<button type="button" class="btn btn-link uitleg-knop p-0 align-baseline" title="Bekijk de uitleg: ' . h($v['titel']) . '"'
		 . ' data-video="video/' . h($v['bestand']) . '.mp4" data-titel="' . h($v['titel']) . '">'
		 . '<i class="fa-regular fa-circle-question"></i></button>';
}

/* Het venster met de video. De video stopt als het venster dicht gaat. */
function uitleg_venster(): string
{
	return <<<'HTML'
<style>
	.uitleg-knop { font-size: 1.15rem; color: #e07b00; margin-left: .35rem; line-height: 1; }
	.uitleg-knop:hover { color: #b35f00; }
	#uitleg-venster video { width: 100%; height: auto; background: #000; border-radius: 6px; }
</style>
<div class="modal fade" id="uitleg-venster" tabindex="-1" role="dialog" aria-labelledby="uitleg-titel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="uitleg-titel">Uitleg</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Sluiten"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<video id="uitleg-video" controls playsinline preload="none"></video>
			</div>
			<div class="modal-footer">
				<a href="uitleg_documenten.php" class="mr-auto">Alle uitleg-video's</a>
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Sluiten</button>
			</div>
		</div>
	</div>
</div>
<script>
	// Klik op een vraagteken: titel en video in het venster zetten, venster openen en afspelen.
	$(document).on('click', '.uitleg-knop', function () {
		const v = document.getElementById('uitleg-video');
		document.getElementById('uitleg-titel').textContent = this.dataset.titel;
		v.src = this.dataset.video;
		$('#uitleg-venster').modal('show');
		v.play().catch(() => {});	// sommige browsers willen dat de gebruiker zelf op afspelen klikt
	});
	// Venster dicht: video stoppen (anders loopt het geluid door).
	$('#uitleg-venster').on('hidden.bs.modal', function () {
		const v = document.getElementById('uitleg-video');
		v.pause(); v.removeAttribute('src'); v.load();
	});
</script>
HTML;
}
