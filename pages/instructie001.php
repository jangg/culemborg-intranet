<?php
include_once('../config.php');
include_once('../class/c_user.php');
/************************
Dit stukje is nodig om misbruik van de website voorkomen
*************************/
if (!isset($_SESSION['username'])) {
	header('location:../index.php');
	exit();
}
if (isset($_SESSION['userid']))
{
	$curr_user = new User ('id', $_SESSION['userid']);
} else
{
	$curr_user = new User ();
}
/**********************/

?>
<!DOCTYPE HTML>
<html lang="nl-NL">
	<head>
		<?php include('../includes/head.php'); ?>			
	<!-- Custom styles for this template -->
		<link href="../css/jumbotron.css" rel="stylesheet" type='text/css'>
		<link href="../css/mystyle.css" rel="stylesheet" type='text/css'>
		<link href="https://fonts.googleapis.com/css2?family=Courier+Prime&family=Source+Serif+Pro&display=swap" rel="stylesheet">
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
			bold {
				color: red;
				font-style: italic;
				font-weight: bold;
			}
			table { border-collapse: collapse; width: 100%; margin: 1rem 0; }
			th, td { border: 1px solid #ccd6e0; padding: .5rem .65rem; text-align: left; vertical-align: top; }
			th { background: #e7f1fb; }
			@media print { body { margin: 0; max-width: none; } h2 { page-break-after: avoid; } table, li { page-break-inside: avoid; } }
			
		</style>
		<!-- <style>
			body { font-family: Arial, Helvetica, sans-serif; font-size: 16px; line-height: 1.55; color: #222; max-width: 820px; margin: 2rem auto; padding: 0 1rem; }
			h1 { font-size: 1.9rem; color: #1f4e79; margin-bottom: .2rem; }
			h2 { font-size: 1.35rem; color: #1f4e79; margin-top: 2.2rem; border-bottom: 1px solid #d8e2ec; padding-bottom: .25rem; }
			.datum { color: #666; font-size: .9rem; margin-top: 0; }
			table { border-collapse: collapse; width: 100%; margin: 1rem 0; }
			th, td { border: 1px solid #ccd6e0; padding: .5rem .65rem; text-align: left; vertical-align: top; }
			th { background: #e7f1fb; }
			ul, ol { padding-left: 1.4rem; }
			li { margin-bottom: .4rem; }
			@media print { body { margin: 0; max-width: none; } h2 { page-break-after: avoid; } table, li { page-break-inside: avoid; } }
		</style> -->

	</head>
 
<body style="background-color: #dddddd; font-size: 16px;">
	
<?php include('../includes/navbar.php'); ?>
<div class="jumbotron">
	<div id="main">
		<div class="container verslag my-5">
			<h3 class="bluefont">19 november 2026</h4>
			<h2 class="text-black mb-2 bluefont">Handleiding documenten beheer</h2>
			<h5 class="text-black mb-5 bluefont">door Jan Geerdes</h5>
			<h1>Werken met documenten in het intranet</h1>
			
			<p>Alle documenten over werkzoekenden en het werk van de coördinatoren bewaar je voortaan in het intranet, onder <strong>Documenten</strong>. Dropbox is daarmee niet meer nodig.</p>
			<p>Deze handleiding is voor coördinatoren, intakers en maatjes van JobHulpMaatje Zoetermeer en JobHulp Culemborg. De intranetten van beide organisaties werken precies hetzelfde. De documenten blijven op onze eigen server en zijn alleen te zien voor wie ingelogd is én het recht heeft om ze te zien.</p>
			
			<h2>Waar vind je het?</h2>
			<p>Klik in de menubalk bovenaan op <strong>Documenten</strong>. Je ziet daar deze tabbladen:</p>
			<table>
				<tr><th>Tabblad</th><th>Wat staat erin</th><th>Wie ziet het</th></tr>
				<tr><td><strong>Gedeeld</strong></td><td>De gemeenschappelijke map van de coördinatoren: protocollen, formulieren, verslagen, alles wat niet bij één werkzoekende hoort.</td><td>Coördinatoren</td></tr>
				<tr><td><strong>Werkzoekenden</strong></td><td>Per werkzoekende een eigen map: cv, brieven, het intakeformulier.</td><td>Coördinatoren zien alle werkzoekenden. Een maatje ziet alleen de werkzoekenden die hij of zij begeleidt.</td></tr>
				<tr><td><strong>Uitleg</strong></td><td>Korte filmpjes (ongeveer een minuut) die laten zien hoe je met de documenten werkt.</td><td>Iedereen die bij Documenten kan. Je ziet alleen de filmpjes over wat je zelf mag.</td></tr>
			</table>
			<p>Klik bij <strong>Werkzoekenden</strong> op een naam om de map van die werkzoekende te openen. Ook werkzoekenden die niet meer actief zijn, staan in de lijst, met een label erbij.</p>
			<p>Zie je het menu-item of een tabblad niet? Dan heb je dat recht (nog) niet. Vraag het aan de beheerder.</p>
			
			<h2>Zo werk je ermee</h2>
			<table>
				<tr><th>Wat wil je doen?</th><th>Hoe</th></tr>
				<tr><td>Een document toevoegen</td><td>Open de juiste map en klik op <strong>Document toevoegen</strong>. Kies een of meer bestanden. Je kunt bestanden ook vanaf je computer naar het vak slepen.</td></tr>
				<tr><td>Een hele map toevoegen</td><td>Klik op <strong>Map toevoegen</strong> en kies een map op je computer. De map komt er met alle bestanden en submappen in.</td></tr>
				<tr><td>Een document openen</td><td>Klik op de naam. Een PDF of foto opent in een nieuw tabblad, andere bestanden worden gedownload.</td></tr>
				<tr><td>Een document downloaden</td><td>Klik op het pijltje naar beneden achter het document.</td></tr>
				<tr><td>Een nieuwe map maken</td><td>Klik op <strong>Nieuwe map</strong> en typ de naam.</td></tr>
				<tr><td>Een andere naam geven</td><td>Klik op het potloodje achter het document of de map.</td></tr>
				<tr><td>Iets verwijderen</td><td>Klik op de prullenbak en bevestig. Een map kan alleen weg als hij leeg is.</td></tr>
				<tr><td>Een document zoeken</td><td>Klik op <strong>Zoeken</strong> en typ een deel van de naam. Je zoekt dan in alle mappen van die werkzoekende of van de gedeelde map.</td></tr>
			</table>
			<p><strong>Verwijderen kan alleen bij wat je zelf hebt toegevoegd.</strong> Wat een ander heeft toegevoegd, kun je wel openen, maar niet verwijderen of hernoemen. Moet dat toch weg, vraag het dan aan wie het toevoegde of aan de beheerder. Verwijderen kan niet ongedaan worden gemaakt.</p>
			
			<h2>Het intakeformulier opslaan</h2>
			<p>Je kunt het ingevulde intakeformulier met één klik als PDF in de map van de werkzoekende zetten. Downloaden en opnieuw uploaden is niet meer nodig.</p>
			<ol>
				<li>Open het intakeformulier van de werkzoekende in het WAS.</li>
				<li>Bovenaan staan drie knoppen: <strong>maak PDF</strong>, <strong>PDF opslaan</strong> en <strong>terug</strong>.</li>
				<li>Klik op <strong>PDF opslaan</strong>.</li>
				<li>Er verschijnt een groene melding. Klik in die melding op <strong>Bekijk in Documenten</strong> om het formulier direct te zien.</li>
			</ol>
			<p>Het formulier krijgt automatisch de naam en de datum van vandaag, bijvoorbeeld <em>Intakeformulier Jan de Vries 2026-11-13.pdf</em>. Zo zie je in de map meteen welke versie de nieuwste is.</p>
			<p><strong>Al eens opgeslagen vandaag?</strong> Dan vraagt het intranet eerst of het oude bestand vervangen mag worden. Klik op <strong>OK</strong> om te vervangen, of op <strong>Annuleren</strong> om het oude te houden. Een formulier van een andere dag blijft altijd gewoon staan.</p>
			<p>De knop <strong>maak PDF</strong> werkt zoals altijd: het formulier verschijnt in je browser, om te bekijken of af te drukken. Dan wordt er niets opgeslagen.</p>
			<p>De knop werkt bij alle werkzoekenden, ook bij wie niet meer actief is.</p>
			
			<h2>Uitleg-video's</h2>
			<p>Wil je liever zien hoe het werkt dan lezen? In het intranet staan korte instructievideo's. Ze zijn in Zoetermeer en Culemborg hetzelfde.</p>
			<ul>
				<li><strong>Alle filmpjes bij elkaar:</strong> open <strong>Documenten</strong> en klik op het tabblad <strong>Uitleg</strong>.</li>
				<li><strong>Uitleg op de plek zelf:</strong> klik op het oranje vraagteken naast een knop of kopje. Het filmpje over dat onderdeel opent dan in een venster. Sluit je het venster, dan stopt het filmpje.</li>
				<li>Met de knop rechtsonder in het filmpje maak je het beeld groter.</li>
				<li>Je ziet alleen de filmpjes over wat je zelf mag. Een maatje ziet bijvoorbeeld geen filmpje over de gedeelde map.</li>
			</ul>
			
			<h2>Goed om te weten</h2>
			<ul>
				<li><strong>Hoeveel ruimte?</strong> Iedereen mag bij werkzoekenden samen 100 MB aan documenten toevoegen. Dat zijn honderden cv's en brieven. De gedeelde map heeft samen 4 GB. Hoeveel je al gebruikt, zie je onderaan de pagina Documenten.</li>
				<li><strong>Welke bestanden?</strong> Gewone documenten: PDF, Word, Excel, PowerPoint, tekst en foto's (JPG, PNG). Programma's en andere soorten worden geweigerd. Een te groot bestand ook; je krijgt dan een melding met het maximum.</li>
				<li><strong>Wat hoort waar?</strong> Alles over één werkzoekende in de map van die werkzoekende. Algemene stukken in <strong>Gedeeld</strong>. Bewaar geen persoonlijke stukken van werkzoekenden in de gedeelde map.</li>
				<li><strong>Privacy:</strong> download documenten alleen als het echt nodig is, en verwijder ze daarna weer van je eigen computer. In het intranet zijn ze beter beveiligd dan in je downloadmap.</li>
				<li><strong>Opruimen:</strong> als een werkzoekende uit het systeem wordt verwijderd, gaan zijn of haar documenten automatisch mee.</li>
				<li><strong>Hulp nodig?</strong> Kom je er niet uit, of zie je een foutmelding? Neem contact op met de beheerder. Of kijk eerst even naar de uitleg-video's (tabblad Uitleg bij Documenten).</li>
			</ul>
		</div>
	</div>
</div>
</body>
<?php include('../includes/footer.php'); ?>
</body>
</html>
