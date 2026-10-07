<?php
/* ------------------------------------------------------------------
 * Instellingen voor TCPDF (de PDF-bibliotheek in tcpdf/) – okt 2026
 *
 * Vroeger stonden deze waarden in tcpdf/config/tcpdf_config.php, dus in de
 * bibliotheek zelf. Bij elke update van TCPDF moest je ze dan opnieuw
 * invoeren. Nu staan ze hier, en blijft de map tcpdf/ een ongewijzigde
 * kopie van TCPDF. Updaten = de map tcpdf/ vervangen (de .htaccess bewaren).
 *
 * K_TCPDF_EXTERNAL_CONFIG = true zegt tegen TCPDF: lees je eigen
 * config-bestand niet; alles wat hier niet staat, krijgt de standaardwaarde.
 * Dit bestand moet geladen worden VÓÓR tcpdf.php (zie class/c_intakeformpdf.php)
 * en NA config.php (voor LOC_NAME).
 * ------------------------------------------------------------------ */
define('K_TCPDF_EXTERNAL_CONFIG', true);

define('K_PATH_IMAGES', __DIR__ . '/../img/');		// map voor afbeeldingen met een relatieve naam
define('K_BLANK_IMAGE', '_blank.png');
define('PDF_HEADER_LOGO', '');					// het logo komt uit IntakeformPDF::Header()
define('PDF_HEADER_LOGO_WIDTH', 30);

define('PDF_PAGE_FORMAT', 'A4');
define('PDF_PAGE_ORIENTATION', 'P');
define('PDF_UNIT', 'mm');
define('PDF_CREATOR', 'TCPDF');
define('PDF_AUTHOR', LOC_NAME);
define('PDF_HEADER_TITLE', '');
define('PDF_HEADER_STRING', LOC_NAME . ' intakeformulier');

// Marges in mm (TCPDF-standaard tussen haakjes)
define('PDF_MARGIN_HEADER', 15);	// (5)
define('PDF_MARGIN_FOOTER', 20);	// (10)
define('PDF_MARGIN_TOP', 47);		// (27)
define('PDF_MARGIN_BOTTOM', 35);	// (25)
define('PDF_MARGIN_LEFT', 22);		// (15)
define('PDF_MARGIN_RIGHT', 22);		// (15)

define('PDF_FONT_NAME_MAIN', 'helvetica');
define('PDF_FONT_SIZE_MAIN', 10);
define('PDF_FONT_NAME_DATA', 'helvetica');
define('PDF_FONT_SIZE_DATA', 8);
define('PDF_FONT_MONOSPACED', 'courier');
define('PDF_IMAGE_SCALE_RATIO', 1.25);

define('HEAD_MAGNIFICATION', 1.1);
define('K_CELL_HEIGHT_RATIO', 1.25);
define('K_TITLE_MAGNIFICATION', 1.3);
define('K_SMALL_RATIO', 2/3);
define('K_THAI_TOPCHARS', true);
define('K_TCPDF_CALLS_IN_HTML', false);		// veilig: geen TCPDF-commando's via HTML
define('K_TCPDF_THROW_EXCEPTION_ERROR', false);
define('K_TIMEZONE', 'UTC');
