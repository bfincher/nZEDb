<?php
define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../www/config.php");
require_once(FS_ROOT."/../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../www/lib/releases.php");
require_once(FS_ROOT."/../../www/lib/category.php");

$releases = new Releases();
$db = new Db;
$sql = "select ID from releases where categoryID = '".CATEGORY::CAT_MISC."' AND adddate <= CURRENT_DATE - INTERVAL 4 HOUR";
$rel = $db->query($sql);
echo "Deleting ".count($rel)." Release(s) from Others > Misc\n";
foreach ($rel as $r) { $releases->delete($r['ID']); }

##$cats=array('".CATEGORY::CAT_TV_FOREIGN."', ".CATEGORY::CAT_XXX_DVD.", ".CATEGORY::CAT_XXX_WMV.", ".CATEGORY::CAT_XXX_XVID.", ".CATEGORY::CAT_XXX_X264.", ".CATEGORY::CAT_XXX_PACK.", ".CATEGORY::CAT_XXX_IMAGESET.", ".CATEGORY::CAT_XXX_OTHER.", ".CATEGORY::CAT_MOVIE_FOREIGN.");

#$cats=array(5020, 6010, 6020, 6030, 6040, 6050, 6060, 6070, 2010);

#foreach ($cats as $cat) {
#    $sql = "select ID from releases where categoryID = $cat";
#    $rel = $db->query($sql);
#    echo "Deleting ".count($rel)." Release(s) from $cat\n";
#    foreach ($rel as $r) { $releases->delete($r['ID']); }
#}

$sql = "select r.ID as ID, name from releases r left outer join musicinfo mi on mi.ID = r.musicInfoID left outer join genres g on mi.genreID = g.ID where g.title in ('Dance & Electronic', 'Blues', 'R&B', 'Rap & Hip-Hop', 'Broadway & Vocalists', 'Classical', 'New Age', 'Latin Music', 'World Music', 'Jazz')";
$rel = $db->query($sql);
echo "deleting ".count($rel). " \n";

foreach ($rel as $r) {$releases->delete($r['ID']); }

?>
