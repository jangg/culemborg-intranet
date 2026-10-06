<?php
include_once 'config.php';
echo MAIL_LOGDIR . '<br/>';
Tools::MailRoom('Klaas de Wilde', 'jan@prohunter.nl', 'Welkom!', '<b>De melding komt van <a href="https://jobhulpculemborg.nl">JobHulp Culemborg</a>!</b>');
Tools::closeMailer();
echo "✅ Mail verzonden!";

?>