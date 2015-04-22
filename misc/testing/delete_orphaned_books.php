<?php
define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../www/config.php");

$pdo = new nzedb\db\Settings();
$sql = "select bi.ID from releases r right join bookinfo bi on r.bookinfoID = bi.ID where r.bookinfoID is null;";
$rel = $pdo->query($sql);
echo "Deleting ".count($rel)." orphaned bookinfo entries\n";
foreach ($rel as $r) { 
#	echo var_dump($r);
        echo $r['id']."\n";
	$file = "../../www/covers/book/".$r['id'].".jpg";
	unlink($file);

	$pdo->queryExec(sprintf("delete from musicinfo where ID = %d", $r['id']));
}

?>
