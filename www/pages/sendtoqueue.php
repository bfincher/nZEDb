<?php
if (!$users->isLoggedIn()) {
	$page->show403();
}

if (empty($_GET["id"])) {
	$page->show404();
}

$user = $users->getById($users->currentUserId());
if ($user['queuetype'] == 1) {

	$sab = new SABnzbd($page);
	if (empty($sab->url)) {
		$page->show404();
	}
	if (empty($sab->apikey)) {
		$page->show404();
	}
	$sab->sendToSab($_GET["id"]);

} elseif ($user['queuetype'] == 2) {
	$nzbget = new NZBGet($page);
	$nzbget->sendToNZBGet($_GET['id']);
}