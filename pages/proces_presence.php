<?php
//
include_once('../config.php');
include_once('../class/c_user.php');

// Noodreparatie okt 2026: zonder deze regel was deze pagina zonder inloggen bereikbaar.
require_login();

// echo $_POST['userid'];
if(isset($_POST['userid']) && is_numeric($_POST['userid']))
{
	// Je eigen aanwezigheid mag je altijd wijzigen, die van anderen alleen met het recht presentie_anderen
	if ((int)$_POST['userid'] !== (int)$_SESSION['userid'] && !heeft_recht('presentie_anderen')) {
		http_response_code(403);
		exit('Je mag alleen je eigen aanwezigheid wijzigen.');
	}
	$user = new User('id', $_POST['userid']);
	if ($user->presentInd != 'j')
		$user->presentInd = 'j';
	else
		$user->presentInd = 'n';
	$user->updateToDB();
	// error_log('De userid is ' . $_POST['userid'] . '\n');
	header('location: presence_list.php');
	exit();
}

?>