<?php
include_once('config.php');
include_once('class/c_user.php');

/************************
Dit stukje is nodig om misbruik van de website voorkomen
*************************/
if (!isset($_SESSION['username'])) {
	header('location:index.php');
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
		<?php include('includes/head.php'); ?>
	<!-- Custom styles for this template -->
		<!-- <link href="css/jumbotron.css" rel="stylesheet"> -->
		<script>
			$(document).ready(function(){
				$("#1").click(function(){
					$(".nieuwsbrief").hide();
					$("#2020081").show();
				});
				$("#2").click(function(){
					$(".nieuwsbrief").hide();
					$("#2020071").show();
				});
			});
		</script>
		<style>
			.bluefont {
				color: #304280;
				font-weight: 300;
			}
			li p {
				margin: 0;
				padding: 0;
			}
		</style>

	</head>
 
<body class="bodystyle">
	
	<?php include('includes/navbar.php'); ?>
	<main role="main">
		<!-- Main jumbotron for a primary marketing message or call to action -->
		<div class="container">
			<h1 class="display-4 bluefont p-3 mt-md-5">Nieuwsbrieven</h1>
			<p>Op deze pagina kun je de nieuwsbrieven die JobHulp Culemborg verspreidt, terug lezen.</p>
			<p>De originele nieuwsbrieven zijn uiteraard beschikbaar in je email zodra ze worden verspreid.</p>
			<br/><br/>
		</div>
		<div class="container">
			<div class="row">
				<div class="col-12">
					<h2 class="bluefont">Uitgave nieuwsbrief</h2>
				</div>
			</div>
			<div class="row">
				<div class="col-md-4">
					<!-- <ul>
					<li><p><a id="45" href="#">Maart 2026</a></p></li>
					<li><p><a id="44" href="#">Februari 2026</a></p></li>
					<li><p><a id="43" href="#">December 2025</a></p></li>
					<li><p><a id="42" href="#">September 2025</a></p></li>
					<li><p><a id="41" href="#">April 2025</a></p></li>
					</ul> -->
				</div>
				<div class="col-md-4">
				</div>
				<div class="col-md-4">
				</div>
			</div>
			<div class="row">				
				<div class="col-12">
					<!-- <div id="20260128" class="nieuwsbrief"  style="display: none;">
						<?php include('nieuwsbrieven/nb20260128.html'); ?>
					</div>
					<div id="20260305" class="nieuwsbrief"  style="display: block;">
						<?php include('nieuwsbrieven/nb20260305.html'); ?>
					</div> -->


				</div>
			</div>
			<!-- <p><a class="btn btn-primary btn-lg" href="#" role="button">Learn more &raquo;</a></p> -->
		</div>								
	</main>
	<?php include('includes/footer.php'); ?>
</body>
</html>