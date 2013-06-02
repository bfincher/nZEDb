<?php
define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../www/config.php");
require_once(FS_ROOT."/../../www/lib/framework/db.php");

$db = new Db;
$sql = "select ID from musicinfo where ID not in (select musicinfoID from releases where musicinfoID is not null)";
$rel = $db->query($sql);
echo "Deleting ".count($rel)." orphaned musicinfo entries\n";
foreach ($rel as $r) { 
	$file = "../../www/covers/music/".$r['ID'].".jpg";
	#unlink($file);

	$db->query(sprintf("delete from musicinfo where ID = %d", $r['ID']));
}

?>
