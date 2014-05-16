<?php
require dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIB . 'utility' . DS . 'CopyFileTree.php';

use nzedb\db\DB;

$reorg = nZEDb_MISC . 'testing' . DS . 'DB' . DS . 'nzb-reorg.php';
$s = new Sites();
$site = $s->get();
$db = new DB();
$level = $site->nzbsplitlevel;
$nzbpath = $site->nzbpath;

if (!isset($argv[1])) {
	exit("WARNING: Run convert_from_newznab.php BEFORE running this script.\nUsage php copy_from_newznab.php path_to_newznab_base\n");
} else if (isset($argv[1]) && !file_exists($argv[1])) {
	exit("$argv[1]) is an invalid path\n");
} else {
	$source = realpath($argv[1] . DS . 'nzbfiles');
	$files = new \nzedb\utility\CopyFileTree($argv[1], $nzbpath);
	echo "Copying nzbs from " . $argv[1] . "\n";
	$files->copy('*');

	$source = realpath($argv[1] . DS . 'www' . DS . 'covers'); // NN+ path, do not change.
	$files = new \nzedb\utility\CopyFileTree($source, nZEDb_COVERS);
	echo "Copying covers from $source\n";
	$files->copy('*');

	echo "Setting nzbstatus for all releases\n";
	$db->queryExec("UPDATE releases SET nzbstatus = 1");

	system("php $reorg $level $nzbpath");
}

?>
