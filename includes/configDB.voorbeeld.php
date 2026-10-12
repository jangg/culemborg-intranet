<?php
/* VOORBEELD van includes/configDB.php, zonder wachtwoorden (okt 2026).
   De echte configDB.php staat NIET in git (zie .gitignore): hij bevat wachtwoorden.
   Nieuwe installatie: kopieer dit bestand naar configDB.php en vul de wachtwoorden in. */
//***********************************************************************
//  CONSTS voor de local server jhmintra:8880
//  
//***********************************************************************
// Consts voor de lokale database
define("DB_DSN", "mysql:host=localhost;port=8889;dbname=jhcintradb");
define("DB_USERNAME", "root");
define("DB_PASSWORD", "");
define("EMAIL_SENDER", "info@jangg.nl");
define("ADMINISTRATOR", "admin");
define("ADMIN_PASSW", "");
// smtp gegevens voor forum email local
define("MAIL_SMTP_SERVER", "jobhulpculemborg.nl");
define("MAIL_USERID", "intra@jobhulpculemborg.nl");
define("MAIL_PASSWORD", "");
define("MAIL_SMTPSECURE", "ssl");
define("MAIL_SENDEREMAIL", "intra@jobhulpculemborg.nl");
define("MAIL_NOREPLYEMAIL", "no-reply@jobhulpculemborg.nl");
define("MAIL_LOGDIR", __DIR__ . "/../logs/");   // okt 2026: was "/logs/" (root van de schijf)
define("MAIL_LOGFILE", "mail_log.txt");
// // DEBUg: 0 = none, 2 = normal, 3 = a lot 
define("MAIL_DEBUG_IND", "0");
define("MAIL_TESTMODUS", false);   // ALLEEN LOKAAL: mail naar de PHP error log i.p.v. versturen (Tools::MailRoom)
// 
// // Overige CONSTANTS
// // JobHulp Culemborg
define("LOC_NOREPLY_EMAIL", "no-reply@jobhulpculemborg.nl");
define("LOC_COORD_EMAIL", "coordinator@jobhulpculemborg.nl");
define("LOC_NAME", "JobHulp Culemborg");
define("LOC_LOGO", "stichtingjobhulpculemborg4.png");
define("LOC_DOMAIN", "intra.jobhulpculemborg.nl");
define("LOC_WEBSITE", "https://intra.jobhulpculemborg.nl");
define("LOC_WEBSITE_PUB", "https://jobhulpculemborg.nl");   // publieke website, gebruikt in navbar.php
define("LOC_JG_NAAM", "JobGroepen");   // gebruikt in class/c_jobgroup_coll.php
define("LOC_JG_EMAIL", "jobhulp@gmail.com");


//***********************************************************************
//  CONSTS voor JobHulpMaatje Zoetermeer PROD intra.jhmz.nl
//  
//***********************************************************************
// Consts voor de JobHulpMaatje Zoetermeer database
// define("DB_DSN", "mysql:host=localhost;dbname=prohuyg144_jhmiB");
// define("DB_USERNAME", "prohuyg144_jangg");
// define("DB_PASSWORD", "");
// define("EMAIL_SENDER", "jang@jobhulpmaatjezoetermeer.nl");
// define("ADMINISTRATOR", "admin");
// define("ADMIN_PASSW", "");
// // smtp gegevens voor forum email JHMZ
// define("MAIL_SMTP_SERVER", "mail.axc.nl");
// define("MAIL_USERID", "intra@intra.jhmz.nl");
// define("MAIL_PASSWORD", "");
// define("MAIL_SMTPSECURE", "ssl");
// define("MAIL_SENDEREMAIL", "intra@intra.jhmz.nl");
// // DEBUg: 0 = none, 2 = normal, 3 = a lot 
// define("MAIL_DEBUG_IND", "0");
// 
// // // Overige CONSTANTS
// // // JobHulpMaatje Zoetermeer
// define("LOC_NOREPLY_EMAIL", "no-reply@intra.jhmz.nl");
// define("LOC_COORD_EMAIL", "coordinator@intra.jhmz.nl.nl");
// define("LOC_NAME", "JobHulpMaatje Zoetermeer");
// define("LOC_LOGO", "Logo_JobHulpMaatje_Zoetermeer.svg");
// define("LOC_DOMAIN", "intra.jhmz.nl");
// define("LOC_WEBSITE", "https://intra.jhmz.nl");

//***********************************************************************
//  CONSTS voor JobHulp Culemborg TEST jhc.machunter.nl
//  
//***********************************************************************
// Consts Consts voor de JobHulp Culemborg test database

// define("DB_DSN", "mysql:host=localhost;dbname=prohuyg144_jhcDB");
// define("DB_USERNAME", "prohuyg144_jangg"); 
// define("DB_PASSWORD", "");
// define("EMAIL_SENDER", "jang@jhc.machunter.nl");
// define("ADMINISTRATOR", "admin");
// define("ADMIN_PASSW", "");
// 
// // smtp gegevens voor forum email
// define("MAIL_SMTP_SERVER", "jhc.machunter.nl");
// define("MAIL_USERID", "intra@jhc.machunter.nl");
// define("MAIL_PASSWORD", "");
// define("MAIL_SMTPSECURE", "ssl");
// define("MAIL_SENDEREMAIL", "intra@jhc.machunter.nl");
// // DEBUg: 0 = none, 2 = normal, 3 = a lot 
// define("MAIL_DEBUG_IND", "0");
// 
// // // Overige CONSTANTS
// // // JobHulp Culemborg
// define("LOC_NOREPLY_EMAIL", "no-reply@jhc.machunter.nl");
// define("LOC_COORD_EMAIL", "coordinator@jhc.machunter.nl");
// define("LOC_NAME", "JobHulp Culemborg");
// define("LOC_LOGO", "stichtingjobhulpculemborg1.jpg");
// define("LOC_DOMAIN", "jhc.machunter.nl");
// define("LOC_WEBSITE", "https://jhc.machunter.nl");

// Documentenfunctie (okt 2026): map voor de geüploade documenten, BUITEN de website.
// Lokaal (MAMP): ~/Sites/jobhulp-documenten/culemborg/   Op de server: naast public_html, zie DOORLICHTING.md
define("DOC_DIR", __DIR__ . "/../../../jobhulp-documenten/culemborg/");

?>