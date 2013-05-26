<?php
define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../www/config.php");
require_once(FS_ROOT."/../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../www/lib/releases.php");
require_once(FS_ROOT."/../../www/lib/category.php");

$releases = new Releases();
$db = new Db;
$sql = "select ID from releases where categoryID between 2000 and 2999 and size < 60000000";
$rel = $db->query($sql);
echo "Deleting ".count($rel)." Release(s) from Movies\n";
foreach ($rel as $r) { $releases->delete($r['ID']); }

$sql = "select ID from releases where categoryID between 5000 and 5999 and size < 50000000";
$rel = $db->query($sql);
echo "Deleting ".count($rel)." Release(s) from TV\n";
foreach ($rel as $r) { $releases->delete($r['ID']); }

?>
