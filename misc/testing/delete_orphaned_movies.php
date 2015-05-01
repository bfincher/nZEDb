<?php
define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../www/config.php");

$pdo = new nzedb\db\Settings();
$sql = "select mi.imdbid as ID from releases r right join movieinfo mi on r.imdbid = mi.imdbid where r.imdbid is null;";
$rel = $pdo->query($sql);
echo "Deleting ".count($rel)." orphaned movieinfo entries\n";
foreach ($rel as $r) { 
#	echo var_dump($r);
        echo $r['id']."\n";
##	$file = "../../www/covers/music/".$r['id'].".jpg";
##	unlink($file);

##	$pdo->queryExec(sprintf("delete from musicinfo where ID = %d", $r['id']));
}

?>
