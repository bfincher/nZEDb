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
#        $query = sprintf("delete from bookinfo where ID = %d", $r['id']);
#        echo "query = ".$query."\n";
	$pdo->queryExec(sprintf("delete from bookinfo where ID = %d", $r['id']));
	$file = "../../resources/covers/book/".$r['id'].".jpg";
	unlink($file);

}

?>
