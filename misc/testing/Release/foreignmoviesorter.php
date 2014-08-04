<?php
require dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;
$pdo = new Settings();
$categorize = new Categorize(['Settings' => $pdo]);

$c = new ColorCLI();
if (isset($argv[1]) && $argv[1] === "true") {
	$results = getForeignMovies();
	foreach ($results as $result) {
		$cat = determineMovieCategory($result['searchname']);
		echo $c->headerOver("English track found for: ") . $c->primary($result['searchname'] . ": " . $cat . " moving...");
		updaterelease($result['id'], $cat);
	}
} else {
	exit($c->error("\nThis script attempts to recategorize foreign movies that have an english audio track.\n"
					. "php $argv[0] true       ...:recategorize foreign movies.\n"));
}

function getForeignMovies() {
	global $pdo;
	$like = 'ILIKE';
	if ($pdo->dbSystem() === 'mysql') {
		$like = 'LIKE';
	}
	return $pdo->query('SELECT r.id, r.searchname FROM releases r JOIN releaseaudio ra ON ra.releaseID = r.id WHERE ra.audiolanguage ' . $like . " '%English%' AND r.categoryid = 2010");
}

function updateRelease($id, $cat) {
	global $pdo;
	$pdo->queryExec(sprintf("UPDATE releases SET categoryid = %s WHERE id = %d", $cat, $id));
}

function determineMovieCategory($name) {
	// Determine sub category
	global $categorize;

	if ($categorize->isMovieSD($name)) {
		return "2030";
	}

	if ($categorize->isMovie3D($name)) {
		return "2060";
	}

	if ($categorize->isMovieHD($name)) {
		return "2040";
	}

	if ($categorize->isMovieBluRay($name)) {
		return "2050";
	}

	// Hack to catch 1080 named releases that didnt reveal their encoding.
	if (strrpos($name, '1080') != false) {
		return "2040";
	}

	// Hack to catch 720 named releases that didnt reveal their encoding.
	if (strrpos($name, '720') != false) {
		return "2040";
	}

	return "2020";
}
