<?php
require_once './config.php';
require_once nZEDb_LIB . 'anidb.php';
require_once nZEDb_LIB . 'adminpage.php';

$page = new AdminPage();

if (isset($_GET['id']))
{
	$AniDB = new AniDB();
	$AniDB->deleteTitle($_GET['id']);
}

$referrer = $_SERVER['HTTP_REFERER'];
header("Location: " . $referrer);

?>
