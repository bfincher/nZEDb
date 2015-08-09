<?php
define('FS_ROOT', realpath(dirname(__FILE__)));
require_once realpath(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'indexer.php');

$pdo = new nzedb\db\Settings();
$sql = "select rage.ID from tvrage rage left join releases r on rage.ID = r.rageID where r.rageID is null;";
$rel = $pdo->query($sql);
#echo "Deleting ".count($rel)." orphaned musicinfo entries\n";
foreach ($rel as $r) { 
#	echo var_dump($r);
        echo $r['id']."\n";
#	$file = "../../resources/covers/music/".$r['id'].".jpg";
#	unlink($file);

	$pdo->queryExec(sprintf("delete from tvrage where ID = %d", $r['id']));
}

?>
