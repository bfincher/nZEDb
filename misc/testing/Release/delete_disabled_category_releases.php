<?php
/* Deletes releases in categories you have disabled here : http://localhost/admin/category-list.php */
require dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\DB;

$c = new ColorCLI();

if (isset($argv[1]) && $argv[1] == "true") {

	$timestart = TIME();
	$db = new DB();
	$releases = new Releases();
	$category = new Category();
	$catlist = $category->getDisabledIDs();
	$relsdeleted = 0;
	if (count($catlist > 0)) {
		foreach ($catlist as $cat) {
			if ($rels = $db->query(sprintf("SELECT id, guid FROM releases WHERE categoryid = %d", $cat['id']))) {
				foreach ($rels as $rel) {
					$relsdeleted++;
					$releases->fastDelete($rel['id'], $rel['guid']);
				}
			}
		}
	}
	$time = TIME() - $timestart;
	if ($relsdeleted > 0) {
		echo $c->header($relsdeleted . " releases deleted in " . $time . " seconds.");
	} else {
		exit($c->info("No releases to delete."));
	}
} else {
	exit($c->error("\nDeletes releases in categories you have disabled here : http://localhost/admin/category-list.php\n"
			. "php $argv[0] true    ...: run this script.\n"));
}
