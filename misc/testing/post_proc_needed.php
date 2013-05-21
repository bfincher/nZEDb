<?php
define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../www/config.php");
require_once(FS_ROOT."/../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../www/lib/releases.php");
require_once(FS_ROOT."/../../www/lib/category.php");

$result = isPostProcNeeded();

function isPostProcNeeded() {
    $releases = new Releases();
    $db = new Db;

    $sqlArray = array( 'select ID from releases where consoleinfoID is null and categoryID between 1000 and 1999 and nzbstatus = 1',
                       'select ID from releases where imdbID is null and categoryID between 2000 and 2999 and nzbstatus = 1',
                       'select ID from releases where musicinfoID is null and categoryID between 3000 and 3999 and nzbstatus = 1',
                       'select ID from releases where imdbID is null and categoryID between 6000 and 6999 and nzbstatus = 1' ,
                       'select ID from releases where' ,
                       '',
 );

    $result = false;
    foreach ($sqlArray as $sql) {
        if(performQuery($db, $sql) == true)
            $result = true;
    } 
    return $result;
}

function performQuery($db, $sql) {
    echo $sql."\n";
    $rel = $db->query($sql);
    echo count($rel)."\n";

    if (count($rel) > 0) {
        return true;
    }

    return false;
}

?>
