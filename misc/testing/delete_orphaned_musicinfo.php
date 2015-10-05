<?php
define('FS_ROOT', realpath(dirname(__FILE__)));
require_once realpath(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'indexer.php');

$pdo = new nzedb\db\Settings();
$sql = "select mi.ID as ID from releases r right join musicinfo mi on r.musicinfoID = mi.ID where r.musicinfoID is null;";
$rel = $pdo->query($sql);
#echo "Deleting ".count($rel)." orphaned musicinfo entries\n";
foreach ($rel as $r) { 
#	echo var_dump($r);
        echo $r['id']."\n";
	$file = "../../resources/covers/music/".$r['id'].".jpg";
	unlink($file);

	$pdo->queryExec(sprintf("delete from musicinfo where ID = %d", $r['id']));
}

?>