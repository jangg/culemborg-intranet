/* ------------------------------------------------------------------
 * Voorbeeldvenster voor documenten (nov 2026)
 *
 * Toont Word (.docx), Excel (.xlsx, .xls, .ods, .csv) en tekst (.txt)
 * in een venster op de pagina, zonder dat het bestand in de map
 * Downloads terechtkomt. Werkt in elke moderne browser: Edge en Chrome
 * op Windows 11, Safari en Chrome op de Mac, iPhone en iPad.
 *
 * Hoe het werkt:
 *  - De browser haalt het bestand op via document.php (dezelfde
 *    rechtencontrole als bij downloaden) en houdt het in het geheugen.
 *  - Word: docx-preview tekent het document als HTML.
 *  - Excel: SheetJS zet elk tabblad om in een tabel.
 *  - Alles komt in een afgeschermd <iframe> (sandbox zonder scripts):
 *    een kwaadaardig document kan daar geen JavaScript uitvoeren.
 *  - De bibliotheken staan in js/voorbeeld/ op onze eigen server en
 *    worden pas geladen als iemand voor het eerst op "bekijken" klikt.
 *    Er gaat geen document naar een externe dienst.
 *
 * Gebruik in de HTML: een link of knop met class "voorbeeld-link" en
 *   data-id, data-naam, data-ext en data-grootte (bytes).
 * ------------------------------------------------------------------ */
(function () {
	'use strict';

	const MAX_BYTES = 20 * 1024 * 1024;	// grotere bestanden: alleen downloaden (te zwaar voor de browser)
	const MAX_RIJEN = 2000;			// per Excel-tabblad niet meer rijen tonen dan dit
	const MAP       = 'js/voorbeeld/';

	// Welke extensie met welke methode getoond wordt.
	const SOORT = { docx: 'word', xlsx: 'excel', xls: 'excel', ods: 'excel', csv: 'excel', txt: 'tekst' };
	window.voorbeeldMogelijk = ext => Object.prototype.hasOwnProperty.call(SOORT, String(ext).toLowerCase());

	// ---------- bibliotheken pas laden als ze nodig zijn ----------
	const geladen = {};
	function laadScript (bestand) {
		if (!geladen[bestand]) {
			geladen[bestand] = new Promise((klaar, fout) => {
				const s = document.createElement('script');
				s.src = MAP + bestand;
				s.onload = klaar;
				s.onerror = () => { delete geladen[bestand]; fout(new Error('Kon ' + bestand + ' niet laden')); };
				document.head.appendChild(s);
			});
		}
		return geladen[bestand];
	}
	async function laadVoor (soort) {
		if (soort === 'word')  { await laadScript('jszip.min.js'); await laadScript('docx-preview.min.js'); }
		if (soort === 'excel') { await laadScript('xlsx.full.min.js'); }
	}

	// ---------- het venster (één keer aanmaken, Bootstrap 4 modal) ----------
	let venster = null;
	function maakVenster () {
		if (venster) return venster;
		const div = document.createElement('div');
		div.className = 'modal fade';
		div.id = 'voorbeeld-venster';
		div.tabIndex = -1;
		div.setAttribute('role', 'dialog');
		div.setAttribute('aria-labelledby', 'voorbeeld-titel');
		div.innerHTML =
			'<div class="modal-dialog modal-xl" role="document">' +
			' <div class="modal-content">' +
			'  <div class="modal-header">' +
			'   <h5 class="modal-title text-truncate" id="voorbeeld-titel"></h5>' +
			'   <button type="button" class="close" data-dismiss="modal" aria-label="Sluiten"><span aria-hidden="true">&times;</span></button>' +
			'  </div>' +
			'  <div class="modal-body p-0">' +
			'   <ul class="nav nav-tabs px-2 pt-2 d-none" id="voorbeeld-tabs"></ul>' +
			'   <div class="p-3 text-muted" id="voorbeeld-melding"></div>' +
			'   <iframe id="voorbeeld-frame" title="Voorbeeld van het document"' +
			'    sandbox="allow-same-origin allow-popups allow-popups-to-escape-sandbox"></iframe>' +
			'  </div>' +
			'  <div class="modal-footer">' +
			'   <small class="text-muted mr-auto">Dit is een voorbeeld. Wil je het document aanpassen? Download het dan.</small>' +
			'   <a class="btn btn-primary" id="voorbeeld-download" href="#"><i class="fa-solid fa-download"></i> Downloaden</a>' +
			'   <button type="button" class="btn btn-secondary" data-dismiss="modal">Sluiten</button>' +
			'  </div>' +
			' </div>' +
			'</div>';
		document.body.appendChild(div);

		const stijl = document.createElement('style');
		stijl.textContent =
			'#voorbeeld-frame { display:block; width:100%; height:72vh; border:0; background:#fff; }' +
			'#voorbeeld-melding:empty { display:none; }' +
			'#voorbeeld-tabs .nav-link { cursor:pointer; }' +
			'@media (max-width: 576px) { #voorbeeld-venster .modal-dialog { margin:0; max-width:none; }' +
			' #voorbeeld-frame { height:calc(100vh - 140px); } }';
		document.head.appendChild(stijl);

		// Bij sluiten het geheugen weer vrijgeven
		$(div).on('hidden.bs.modal', () => { leegFrame(); });
		venster = div;
		return div;
	}

	const el = id => document.getElementById(id);
	function melding (tekst) { el('voorbeeld-melding').textContent = tekst || ''; }

	// Het iframe opnieuw beginnen met een leeg document; wacht tot het klaar is.
	const BASIS_CSS =
		'body { margin:0; font-family: Calibri, Carlito, "Segoe UI", Arial, sans-serif; }' +
		'pre { margin:0; padding:1rem; white-space:pre-wrap; word-wrap:break-word; font: 14px/1.45 Consolas, Menlo, monospace; }' +
		'table.blad { border-collapse:collapse; margin:.5rem; font-size:13px; }' +
		'table.blad td { border:1px solid #d0d5e0; padding:2px 6px; white-space:nowrap; vertical-align:top; }' +
		'table.blad tr:nth-child(even) td { background:#f7f8fb; }' +
		'p.extra { margin:.5rem; color:#a0522d; font-size:13px; }';
	function leegFrame () {
		return new Promise(klaar => {
			const f = el('voorbeeld-frame');
			if (!f) return klaar(null);
			f.onload = () => { f.onload = null; klaar(f.contentDocument); };
			f.srcdoc = '<!DOCTYPE html><html lang="nl"><head><meta charset="utf-8"><style>' + BASIS_CSS + '</style></head><body></body></html>';
		});
	}

	// Extra voorzichtig: alleen gewone weblinks en e-mailadressen laten werken,
	// en die openen in een nieuw tabblad. Al het andere wordt onschadelijk gemaakt.
	function maakVeilig (doc) {
		doc.querySelectorAll('script, iframe, object, embed, form, base, meta[http-equiv]').forEach(n => n.remove());
		doc.querySelectorAll('*').forEach(n => {
			for (const a of Array.from(n.attributes)) {
				if (/^on/i.test(a.name)) n.removeAttribute(a.name);
			}
		});
		doc.querySelectorAll('a[href]').forEach(a => {
			const href = a.getAttribute('href').trim();
			if (href.startsWith('#')) return;			// verwijzing binnen het document
			if (/^(https?:|mailto:)/i.test(href)) {
				a.target = '_blank';
				a.rel = 'noopener noreferrer';
			} else {
				a.removeAttribute('href');
			}
		});
	}

	// ---------- tonen ----------
	async function toonWord (buf, doc) {
		await window.docx.renderAsync(buf, doc.body, doc.head, {
			inWrapper: true,		// grijze achtergrond met "papieren" pagina's, zoals in Word
			breakPages: true,
			ignoreLastRenderedPageBreak: true,
			renderHeaders: true,
			renderFooters: true,
			useBase64URL: true,		// afbeeldingen als data:-adres, werkt ook in het afgeschermde iframe
			experimental: false
		});
		maakVeilig(doc);
	}

	function toonTekst (buf, doc) {
		const pre = doc.createElement('pre');
		pre.textContent = new TextDecoder('utf-8').decode(buf);	// textContent: nooit als HTML uitgevoerd
		doc.body.appendChild(pre);
	}

	async function toonExcel (buf, ext) {
		const XLSX = window.XLSX;
		const wb = XLSX.read(buf, { type: 'array', dense: true, cellDates: true, codepage: 65001 });
		const verborgen = i => !!(wb.Workbook && wb.Workbook.Sheets && wb.Workbook.Sheets[i] && wb.Workbook.Sheets[i].Hidden);
		const bladen = wb.SheetNames.filter((n, i) => !verborgen(i));
		if (!bladen.length) throw new Error('geen tabbladen');

		const tabs = el('voorbeeld-tabs');
		tabs.innerHTML = '';
		tabs.classList.toggle('d-none', bladen.length < 2);

		async function toonBlad (naam) {
			tabs.querySelectorAll('.nav-link').forEach(a => a.classList.toggle('active', a.dataset.blad === naam));
			const doc = await leegFrame();
			const ws = wb.Sheets[naam];
			if (!ws || !ws['!ref']) { doc.body.innerHTML = '<p class="extra">Dit tabblad is leeg.</p>'; return; }
			const bereik = XLSX.utils.decode_range(ws['!ref']);
			const rijen = bereik.e.r - bereik.s.r + 1;
			let te_veel = false;
			if (rijen > MAX_RIJEN) { bereik.e.r = bereik.s.r + MAX_RIJEN - 1; te_veel = true; }
			const kopie = Object.assign({}, ws, { '!ref': XLSX.utils.encode_range(bereik) });
			// sheet_to_html zet de celinhoud om met escape, dus tekst in cellen wordt nooit HTML
			doc.body.innerHTML = XLSX.utils.sheet_to_html(kopie, { header: '', footer: '', editable: false });
			const t = doc.querySelector('table');
			if (t) t.className = 'blad';
			if (te_veel) {
				const p = doc.createElement('p');
				p.className = 'extra';
				p.textContent = 'Alleen de eerste ' + MAX_RIJEN + ' van de ' + rijen + ' rijen worden getoond. Download het bestand om alles te zien.';
				doc.body.appendChild(p);
			}
			maakVeilig(doc);
		}

		bladen.forEach(naam => {
			const li = document.createElement('li');
			li.className = 'nav-item';
			const a = document.createElement('a');
			a.className = 'nav-link';
			a.dataset.blad = naam;
			a.textContent = naam;			// textContent: een tabbladnaam kan geen HTML worden
			a.addEventListener('click', e => { e.preventDefault(); toonBlad(naam); });
			li.appendChild(a);
			tabs.appendChild(li);
		});
		await toonBlad(bladen[0]);
	}

	async function toonVoorbeeld (id, naam, ext, grootte) {
		ext = String(ext).toLowerCase();
		const soort = SOORT[ext];
		const downloadUrl = 'document.php?id=' + encodeURIComponent(id);
		if (!soort || grootte > MAX_BYTES) { window.location.href = downloadUrl; return; }

		maakVenster();
		el('voorbeeld-titel').textContent = naam;
		el('voorbeeld-download').href = downloadUrl;
		el('voorbeeld-tabs').classList.add('d-none');
		el('voorbeeld-frame').style.display = '';
		melding('Het document wordt geladen…');
		let doc = await leegFrame();
		$('#voorbeeld-venster').modal('show');

		try {
			const [antwoord] = await Promise.all([
				fetch(downloadUrl, { credentials: 'same-origin', cache: 'no-store' }),
				laadVoor(soort)
			]);
			if (!antwoord.ok) throw new Error('HTTP ' + antwoord.status);
			const buf = await antwoord.arrayBuffer();

			if (soort === 'word')  await toonWord(buf, doc);
			if (soort === 'tekst') toonTekst(buf, doc);
			if (soort === 'excel') await toonExcel(buf, ext);
			melding('');
		} catch (fout) {
			console.error('Voorbeeld mislukt:', fout);
			el('voorbeeld-frame').style.display = 'none';
			melding('Dit document kan niet als voorbeeld worden getoond. Gebruik de knop Downloaden om het te openen.');
		}
	}
	window.toonVoorbeeld = toonVoorbeeld;

	// Grijs oogje bij bestanden zonder voorbeeld: uitleg in een popover (Bootstrap 4).
	// Klikken of tikken toont de uitleg, ergens anders klikken sluit hem (trigger "focus").
	$(function () { $('.geen-voorbeeld').popover(); });

	// Eén klikhandler voor alle links en knoppen met class "voorbeeld-link".
	// Zonder JavaScript (of bij een fout vóór dit punt) werkt de gewone link: downloaden.
	document.addEventListener('click', e => {
		const a = e.target.closest('.voorbeeld-link');
		if (!a) return;
		e.preventDefault();
		toonVoorbeeld(a.dataset.id, a.dataset.naam, a.dataset.ext, parseInt(a.dataset.grootte, 10) || 0);
	});
})();
