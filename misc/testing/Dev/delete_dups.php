<?php
define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../www/config.php");
require_once(FS_ROOT."/../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../www/lib/releases.php");

$db = new Db;
$releases = new Releases();
$s = new Sites();
$site = $s->get();
$sql = "select ID, groupID, name, count(name) as cnt from releases group by name having cnt > 1 ";
$rel = $db->query($sql);
	echo count($rel)."\n";
foreach ($rel as $r) { 
	$sql2 = sprintf("select ID, guid from releases where name = '%s' and groupID = %d order by adddate", $r['name'], $r['groupID']);
	$rel2 = $db->query($sql2);
	echo count($rel2)."\n";
	$first = TRUE;
	foreach ($rel2 as $r2) {
		if ($first == FALSE) {
			$releases->fastDelete($r2['ID'], $r2['guid'], $site);
		}
		$first = FALSE;
        }
}

?>
