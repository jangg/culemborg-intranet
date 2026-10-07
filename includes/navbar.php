<?php
/***************************************
 * Wie wat mag, staat per site in includes/rechten.php.
 * Hier wordt alleen gevraagd: heeft deze gebruiker dit recht?
 * Zo gebruiken het menu en de pagina's zelf altijd dezelfde regels.
 ***************************************/
$canForumRead  = heeft_recht('forum_lezen');
$canForumWrite = heeft_recht('forum_schrijven');
$canRedactie   = heeft_recht('redactie');
$canWas        = heeft_recht('was_lezen');
$canAll        = heeft_recht('beheerder');
?>
<nav class="navbar navbar-expand-xl navbar-dark fixed-top bg-primary">
	<div class="container">
		<a href="/home.php"><span class="navbar-brand"><?php echo LOC_NAME; ?> Intranet</span></a>
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>
		<div class="collapse navbar-collapse" id="navbarResponsive">
			<ul class="navbar-nav mr-auto">
				<li class="nav-item <?php if (basename($_SERVER['PHP_SELF']) == 'home.php') echo 'active'; ?>">
					<a class="nav-link" href="/home.php">Home</a>
				</li>
				<li class="nav-item <?php if (basename($_SERVER['PHP_SELF']) == 'agenda.php') echo 'active'; ?>">
					<a class="nav-link" href="/agenda.php">Agenda</a>
				</li>				
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" id="navbardrop" data-toggle="dropdown">
						Overige info
					</a>
					<div class="dropdown-menu">
						<a class="dropdown-item" href="/faces.php">Wie is wie?</a>
						<a class="dropdown-item" href="/nieuwsbrief.php">Nieuwsbrieven</a>
						<a class="dropdown-item" target="_new" href="<?php echo LOC_WEBSITE_PUB ?>">Publieke website</a>
					</div>
				</li>

				<li class="nav-item <?php if (basename($_SERVER['PHP_SELF']) == 'overz_forum.php' || basename($_SERVER['PHP_SELF']) == 'overz_cat.php' || basename($_SERVER['PHP_SELF']) == 'overz_topic.php') echo 'active'; ?>">
					<a class="nav-link <?= h($canForumRead ? '' : 'disabled') ?>" href="<?= h($canForumRead ? '/forum/overz_forum.php' : '#') ?>">Forum</a>
				</li>
				
				
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" id="navbardrop" data-toggle="dropdown">Beheer</a>
					<div class="dropdown-menu">
					<a class="dropdown-item <?= h($canRedactie ? '' : 'disabled') ?>"
					   href="<?= h($canRedactie ? '/beheerred/beheer.php' : '#') ?>"
					   <?= h($canRedactie ? '' : 'aria-disabled="true"') ?>>Redactie</a>
					
					<a class="dropdown-item <?= h($canWas ? '' : 'disabled') ?>"
					   href="<?= h($canWas ? '/beheerwas/beheer.php' : '#') ?>"
					   <?= h($canWas ? '' : 'aria-disabled="true"') ?>>de WAS</a>					
				   </div>
				</li>
			</ul>
			<li class="navbar-nav dropdown">
				<a class="nav-link dropdown-toggle text-white" href="#" id="navbardrop" data-toggle="dropdown">
					Ingelogd als: <?php if (isset($curr_user)) echo $curr_user->username; else echo '----';?>
				</a>
				<div class="dropdown-menu">
					<a class="dropdown-item" href="/wijzwacht.php">Wijzig wachtwoord</a>
					<a class="dropdown-item"  href="/logout.php">Log uit</a>
				</div>
			</li>
		</div>
	</div>
</nav>