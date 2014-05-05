<?php

// API functions.
$function = 's';
if (isset($_GET['t'])) {
	switch ($_GET['t']) {
		case 'd':
		case 'details':
			$function = 'd';
			break;
		case 'g':
		case 'get':
			$function = 'g';
			break;
		case 's':
		case 'search':
			$function = 's';
			break;
		case 'c':
		case 'caps':
			$function = 'c';
			break;
		case 'tv':
		case 'tvsearch':
			$function = 'tv';
			break;
		case 'm':
		case 'movie':
			$function = 'm';
			break;
		case 'r':
		case 'register':
			$function = 'r';
			break;
		default:
			showApiError(202, 'No such function (' . $_GET['t'] . ')');
	}
} else {
	showApiError(200, 'Missing parameter (t)');
}

$uid = $apiKey = '';
$catExclusions = array();
$maxRequests = 0;
// Page is accessible only by the apikey, or logged in users.
if ($users->isLoggedIn()) {
	$uid = $page->userdata['id'];
	$apiKey = $page->userdata['rsstoken'];
	$catExclusions = $page->userdata['categoryexclusions'];
	$maxRequests = $page->userdata['apirequests'];
} else {
	if ($function != 'c' && $function != 'r') {
		if (!isset($_GET['apikey'])) {
			showApiError(200, 'Missing parameter (apikey)');
		}
		$res = $users->getByRssToken($_GET['apikey']);
		$apiKey = $_GET['apikey'];

		if (!$res) {
			showApiError(100, 'Incorrect user credentials (wrong API key)');
		}

		$uid = $res['id'];
		$catExclusions = $users->getCategoryExclusion($uid);
		$maxRequests = $res['apirequests'];
	}
}

$page->smarty->assign('uid', $uid);
$page->smarty->assign('rsstoken', $apiKey);

// Record user access to the api, if its been called by a user (i.e. capabilities request do not require a user to be logged in or key provided).
if ($uid != '') {
	$users->updateApiAccessed($uid);
	$apiRequests = $users->getApiRequests($uid);
	if ($apiRequests['num'] > $maxRequests) {
		showApiError(500, 'Request limit reached (' . $apiRequests['num'] . '/' . $maxRequests . ')');
	}
}

$releases = new Releases();

if (isset($_GET['extended']) && $_GET['extended'] == 1) {
	$page->smarty->assign('extended', '1');
}
if (isset($_GET['del']) && $_GET['del'] == 1) {
	$page->smarty->assign('del', '1');
}

// Output is either json or xml.
$outputXML = true;
if (isset($_GET['o'])) {
	if ($_GET['o'] == 'json') {
		$outputXML = false;
	}
}

switch ($function) {
	// Search releases.
	case 's':
		verifyEmptyParameter('q');
		$maxAge = maxAge();
		$users->addApiRequest($uid, $_SERVER['REQUEST_URI']);
		$categoryID = categoryID();
		$limit = limit();
		$offset = offset();

		if (isset($_GET['q'])) {
			$relData = $releases->search(
				$_GET['q'], -1, -1, -1, $categoryID, -1, -1, 0, 0, -1, -1, $offset, $limit, '', $maxAge, $catExclusions
			);
		} else {
			$totalRows = $releases->getBrowseCount($categoryID, $maxAge, $catExclusions);
			$relData = $releases->getBrowseRange($categoryID, $offset, $limit, '', $maxAge, $catExclusions);
			if ($totalRows > 0 && count($relData) > 0) {
				$relData[0]['_totalrows'] = $totalRows;
			}
		}

		printOutput($relData, $outputXML, $page, $offset);
		break;

	// Search tv releases.
	case 'tv':
		verifyEmptyParameter('q');
		verifyEmptyParameter('rid');
		verifyEmptyParameter('season');
		verifyEmptyParameter('ep');
		$maxAge = maxAge();
		$users->addApiRequest($uid, $_SERVER['REQUEST_URI']);
		$offset = offset();

		$relData = $releases->searchbyRageId(
			(isset($_GET['rid']) ? $_GET['rid'] : '-1'),
			(isset($_GET['season']) ? $_GET['season'] : ''),
			(isset($_GET['ep']) ? $_GET['ep'] : ''),
			$offset,
			limit(),
			(isset($_GET['q']) ? $_GET['q'] : ''),
			categoryID(),
			$maxAge
		);

		printOutput(addLanguage($relData), $outputXML, $page, $offset);
		break;

	// Search movie releases.
	case 'm':
		verifyEmptyParameter('q');
		verifyEmptyParameter('imdbid');
		$maxAge = maxAge();
		$users->addApiRequest($uid, $_SERVER['REQUEST_URI']);
		$offset = offset();

		$relData = $releases->searchbyImdbId(
			(isset($_GET['imdbid']) ? $_GET['imdbid'] : '-1'),
			$offset,
			limit(),
			(isset($_GET['q']) ? $_GET['q'] : ''),
			categoryID(),
			$maxAge
		);

		printOutput(addLanguage($relData), $outputXML, $page, $offset);
		break;

	// Get NZB.
	case 'g':
		if (!isset($_GET['id'])) {
			showApiError(200, 'Missing parameter (id is required for downloading an NZB)');
		}

		$relData = $releases->getByGuid($_GET['id']);
		if ($relData) {
			header(
				'Location:' .
				WWW_TOP .
				'/getnzb?i=' .
				$uid .
				'&r=' .
				$apiKey .
				'&id=' .
				$relData['guid'] .
				((isset($_GET['del']) && $_GET['del'] == '1') ? '&del=1' : '')
			);
		} else {
			showApiError(300, 'No such item (the guid you provided has no release in our database)');
		}
		break;

	// Get individual NZB details.
	case 'd':
		if (!isset($_GET['id'])) {
			showApiError(200, 'Missing parameter (id is required for downloading an NZB)');
		}

		$users->addApiRequest($uid, $_SERVER['REQUEST_URI']);
		$data = $releases->getByGuid($_GET['id']);

		$relData = array();
		if ($data) {
			$relData[] = $data;
		}

		printOutput($relData, $outputXML, $page, $offset);
		break;

	// Capabilities request.
	case 'c':
		$category = new Category();
		$page->smarty->assign('parentcatlist', $category->getForMenu());
		header('Content-type: text/xml');
		echo $page->smarty->fetch('apicaps.tpl');
		break;

	// Register request.
	case 'r':
		verifyEmptyParameter('email');

		if (!in_array((int)$page->site->registerstatus, array(Sites::REGISTER_STATUS_OPEN, Sites::REGISTER_STATUS_API_ONLY))) {
			showApiError(104);
		}

		// Check email is valid format.
		if (!$users->isValidEmail($_GET['email'])) {
			showApiError(106);
		}

		// Check email isn't taken.
		$ret = $users->getByEmail($_GET['email']);
		if (isset($ret['id'])) {
			showApiError(105);
		}

		// Create username/pass and register.
		$username = $users->generateUsername($_GET['email']);
		$password = $users->generatePassword();

		// Register.
		$userDefault = $users->getDefaultRole();
		$uid = $users->signup(
			$username, $password, $_GET['email'], $_SERVER['REMOTE_ADDR'], $userDefault['id'], $userDefault['defaultinvites']
		);

		// Check if it succeeded.
		$userData = $users->getById($uid);
		if (!$userData) {
			showApiError(107);
		}

		header('Content-type: text/xml');
		echo
			"<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
			'<register username="' . $username .
			'" password="' . $password .
			'" apikey="' . $userdata['rsstoken'] .
			"\"/>\n";
		break;
}

/**
 * Display error/error code.
 * @param int    $errorCode
 * @param string $errorText
 */
function showApiError($errorCode = 900, $errorText = '')
{
	if ($errorText === '') {
		switch ($errorCode) {
			case 100:
				$errorText = 'Incorrect user credentials';
				break;
			case 101:
				$errorText = 'Account suspended';
				break;
			case 102:
				$errorText = 'Insufficient priviledges/not authorized';
				break;
			case 103:
				$errorText = 'Registration denied';
				break;
			case 104:
				$errorText = 'Registrations are closed';
				break;
			case 105:
				$errorText = 'Invalid registration (Email Address Taken)';
				break;
			case 106:
				$errorText = 'Invalid registration (Email Address Bad Format)';
				break;
			case 107:
				$errorText = 'Registration Failed (Data error)';
				break;
			case 200:
				$errorText = 'Missing parameter';
				break;
			case 201:
				$errorText = 'Incorrect parameter';
				break;
			case 202:
				$errorText = 'No such function';
				break;
			case 203:
				$errorText = 'Function not available';
				break;
			case 300:
				$errorText = 'No such item';
				break;
			case 500:
				$errorText = 'Request limit reached';
				break;
			case 501:
				$errorText = 'Download limit reached';
				break;
			default:
				$errorText = 'Unknown error';
				break;
		}
	}

	header('Content-type: text/xml');
	exit("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<error code=\"$errorCode\" description=\"$errorText\"/>\n");
}

/**
 * Verify maxage parameter.
 * @return int
 */
function maxAge()
{
	$maxAge = -1;
	if (isset($_GET['maxage'])) {
		if ($_GET['maxage'] == '') {
			showApiError(201, 'Incorrect parameter (maxage must not be empty)');
		} elseif (!is_numeric($_GET['maxage'])) {
			showApiError(201, 'Incorrect parameter (maxage must be numeric)');
		} else {
			$maxAge = (int)$_GET['maxage'];
		}
	}
	return $maxAge;
}

/**
 * Verify cat parameter.
 * @return array
 */
function categoryID()
{
	$categoryID[] = -1;
	if (isset($_GET['cat'])) {
		$categoryID = explode(',', $_GET['cat']);
	}
	return $categoryID;
}

/**
 * Verify limit parameter.
 * @return int
 */
function limit()
{
	$limit = 100;
	if (isset($_GET['limit']) && is_numeric($_GET['limit']) && $_GET['limit'] < 100) {
		$limit = (int)$_GET['limit'];
	}
	return $limit;
}

/**
 * Verify offset parameter.
 * @return int
 */
function offset()
{
	$offset = 0;
	if (isset($_GET['offset']) && is_numeric($_GET['offset'])) {
		$offset = (int)$_GET['offset'];
	}
	return $offset;
}

/**
 * Print XML or JSON output.
 * @param array    $data   Data to print.
 * @param bool     $xml    True: Print as XML False: Print as JSON.
 * @param BasePage $page
 * @param int      $offset Offset for limit.
 */
function printOutput($data, $xml = true, $page, $offset = 0)
{
	if ($xml) {
		$page->smarty->assign('offset', $offset);
		$page->smarty->assign('releases', $data);
		header('Content-type: text/xml');
		echo trim($page->smarty->fetch('apiresult.tpl'));
	} else {
		header('Content-type: application/json');
		echo json_encode($data);
	}
}

/**
 * Check if a parameter is empty.
 * @param string $parameter
 */
function verifyEmptyParameter($parameter)
{
	if (isset($_GET[$parameter]) && $_GET[$parameter] == '') {
		showApiError(201, 'Incorrect parameter (' . $parameter . ' must not be empty)');
	}
}

/**
 * Add language from media info XML to release search names.
 * @param array $releaseData
 * @return array
 */
function addLanguage($releaseData)
{
	$db = new nzedb\db\DB();
	$returnData = array();
	foreach ($releaseData as $release) {
		$audios = $db->query(sprintf('SELECT * FROM releaseaudio WHERE releaseid = %d', $release['id']));
		foreach ($audios as $audio) {
			if ($audio['audiolanguage'] != '') {
				$release['searchname'] = ($release['searchname'] . ' ' . $audio['audiolanguage']);
			}
		}
		$returnData[] = $release;
	}
	return $returnData;
}