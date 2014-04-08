<?php

use nzedb\utility;

/**
 * Class SABnzbd
 */
class SABnzbd
{
	/**
	 * URL to the SAB server.
	 * @var string
	 */
	public $url = '';

	/**
	 * The SAB API key.
	 * @var string
	 */
	public $apikey = '';

	/**
	 * Download priority of the sent NZB file.
	 * @var string
	 */
	public $priority = '';

	/**
	 * Type of SAB API key (full/nzb).
	 * @var string
	 */
	public $apikeytype = '';

	/**
	 * @var int
	 */
	public $integrated = self::INTEGRATION_TYPE_NONE;

	/**
	 * ID of the current user, to send to SAB when downloading a NZB.
	 * @var string
	 */
	protected $uid = '';

	/**
	 * User's nZEDb API key to send to SAB when downloading a NZB.
	 * @var string
	 */
	protected $rsstoken = '';

	/**
	 * nZEDb Site URL to send to SAB to download the NZB.
	 * @var string
	 */
	protected $serverurl = '';

	/**
	 * Type of site integration.
	 */
	const INTEGRATION_TYPE_NONE     = 0; // Sab is completely disabled - no user can use it.
	const INTEGRATION_TYPE_SITEWIDE = 1; // Sab is enabled, 1 remote SAB server for the whole site.
	const INTEGRATION_TYPE_USER     = 2; // Sab is enabled, every user can use their own SAB server.

	/**
	 * Type of SAB API key.
	 */
	const API_TYPE_NZB  = 1;
	const API_TYPE_FULL = 2;

	/**
	 * Priority to send the NZB to SAB.
	 */
	const PRIORITY_PAUSED = -2;
	const PRIORITY_LOW = -1;
	const PRIORITY_NORMAL = 0;
	const PRIORITY_HIGH = 1;
	const PRIORITY_FORCE = 2;

	/**
	 * Construct.
	 *
	 * @param BasePage $page
	 */
	public function __construct(&$page)
	{
		$this->uid = $page->userdata['id'];
		$this->rsstoken = $page->userdata['rsstoken'];
		$this->serverurl = $page->serverurl;

		// Set up properties.
		switch ($page->site->sabintegrationtype) {
			case self::INTEGRATION_TYPE_USER:
				if (!empty($_COOKIE['sabnzbd_' . $this->uid . '__apikey']) && !empty($_COOKIE['sabnzbd_' . $this->uid . '__host'])) {
					$this->url = $_COOKIE['sabnzbd_' . $this->uid . '__host'];
					$this->apikey = $_COOKIE['sabnzbd_' . $this->uid . '__apikey'];
					$this->priority = (isset($_COOKIE['sabnzbd_' . $this->uid . '__priority'])) ? $_COOKIE['sabnzbd_' . $this->uid . '__priority'] : 0;
					$this->apikeytype = (isset($_COOKIE['sabnzbd_' . $this->uid . '__apitype'])) ? $_COOKIE['sabnzbd_' . $this->uid . '__apitype'] : 1;
				} else if (!empty($page->userdata['sabapikey']) && !empty($page->userdata['saburl'])) {
					$this->url = $page->userdata['saburl'];
					$this->apikey = $page->userdata['sabapikey'];
					$this->priority = $page->userdata['sabpriority'];
					$this->apikeytype = $page->userdata['sabapikeytype'];
				}
				$this->integrated = self::INTEGRATION_TYPE_USER;
				break;

			case self::INTEGRATION_TYPE_SITEWIDE:
				if (!empty($page->site->sabapikey) && !empty($page->site->saburl)) {
					$this->url = $page->site->saburl;
					$this->apikey = $page->site->sabapikey;
					$this->priority = $page->site->sabpriority;
					$this->apikeytype = $page->site->sabapikeytype;
				}
				$this->integrated = self::INTEGRATION_TYPE_SITEWIDE;
				break;

			case self::INTEGRATION_TYPE_NONE:
				$this->integrated = self::INTEGRATION_TYPE_NONE;
				break;
		}
		// Verify the URL is good, fix it if not.
		if (preg_match('/(?P<first>\/)?(?P<sab>[a-z]+)?(?P<last>\/)?$/i', $this->url, $matches)) {
			if (!isset($matches['first'])) {
				$this->url .= '/';
			}
			if (!isset($matches['sab'])) {
				$this->url .= 'sabnzbd';
			} elseif ($matches['sab'] !== 'sabnzbd') {
				$this->url .= 'sabnzbd';
			}
			if (!isset($matches['last'])) {
				$this->url .= '/';
			}
		}
	}

	/**
	 * Send a release to SAB.
	 *
	 * @param string $guid Release identifier.
	 *
	 * @return bool|mixed
	 */
	public function sendToSab($guid)
	{
		return nzedb\utility\getUrl(
			$this->url .
			'api?mode=addurl&priority=' .
			$this->priority .
			'&apikey=' .
			$this->apikey .
			'&name=' .
			urlencode(
				$this->serverurl .
				'getnzb/' .
				$guid .
				'&i=' .
				$this->uid .
				'&r=' .
				$this->rsstoken
			)
		);
	}

	/**
	 * Get JSON representation of the SAB queue.
	 *
	 * @return bool|mixed
	 */
	public function getQueue()
	{
		return nzedb\utility\getUrl(
			$this->url .
			"api?mode=qstatus&output=json&apikey=" .
			$this->apikey
		);
	}

	/**
	 * Get JSON representation of the full SAB queue.
	 *
	 * @return bool|mixed
	 */
	public function getAdvQueue()
	{
		return nzedb\utility\getUrl(
			$this->url .
			"api?mode=queue&start=START&limit=LIMIT&output=json&apikey=" .
			$this->apikey
		);
	}

	/**
	 * Delete a single NZB from the SAB queue.
	 *
	 * @param int $id
	 *
	 * @return bool|mixed
	 */
	public function delFromQueue($id)
	{
		return nzedb\utility\getUrl(
			$this->url .
			"api?mode=queue&name=delete&value=" .
			$id .
			"&apikey=" .
			$this->apikey
		);
	}

	/**
	 * Pause a single NZB in the SAB queue.
	 *
	 * @param int $id
	 *
	 * @return bool|mixed
	 */
	public function pauseFromQueue($id)
	{
		return nzedb\utility\getUrl(
			$this->url .
			"api?mode=queue&name=pause&value=" .
			$id .
			"&apikey=" .
			$this->apikey
		);
	}

	/**
	 * Resume a single NZB in the SAB queue.
	 *
	 * @param int $id
	 *
	 * @return bool|mixed
	 */
	public function resumeFromQueue($id)
	{
		return nzedb\utility\getUrl(
			$this->url .
			"api?mode=queue&name=resume&value=" .
			$id .
			"&apikey=" .
			$this->apikey
		);
	}

	/**
	 * Pause all NZB's in the SAB queue.
	 *
	 * @return bool|mixed
	 */
	public function pauseAll()
	{
		return nzedb\utility\getUrl(
			$this->url .
			"api?mode=pause" .
			"&apikey=" .
			$this->apikey
		);
	}

	/**
	 * Resume all NZB's in the SAB queue.
	 *
	 * @return bool|mixed
	 */
	public function resumeAll()
	{
		return nzedb\utility\getUrl(
			$this->url .
			"api?mode=resume" .
			"&apikey=" .
			$this->apikey
		);
	}

	/**
	 * Check if the SAB cookies are in the User's browser.
	 *
	 * @return bool
	 */
	public function checkCookie()
	{
		$res = false;
		if (isset($_COOKIE['sabnzbd_' . $this->uid . '__apikey'])) {
			$res = true;
		}
		if (isset($_COOKIE['sabnzbd_' . $this->uid . '__host'])) {
			$res = true;
		}
		if (isset($_COOKIE['sabnzbd_' . $this->uid . '__priority'])) {
			$res = true;
		}
		if (isset($_COOKIE['sabnzbd_' . $this->uid . '__apitype'])) {
			$res = true;
		}

		return $res;
	}

	/**
	 * Creates the SAB cookies for the user's browser.
	 *
	 * @param $host
	 * @param $apikey
	 * @param $priority
	 * @param $apitype
	 */
	public function setCookie($host, $apikey, $priority, $apitype)
	{
		setcookie('sabnzbd_' . $this->uid . '__host',     $host,     (time() + 2592000));
		setcookie('sabnzbd_' . $this->uid . '__apikey',   $apikey,   (time() + 2592000));
		setcookie('sabnzbd_' . $this->uid . '__priority', $priority, (time() + 2592000));
		setcookie('sabnzbd_' . $this->uid . '__apitype',  $apitype,  (time() + 2592000));
	}

	/**
	 * Deletes the SAB cookies from the user's browser.
	 */
	public function unsetCookie()
	{
		setcookie('sabnzbd_' . $this->uid . '__host',     '', (time() - 2592000));
		setcookie('sabnzbd_' . $this->uid . '__apikey',   '', (time() - 2592000));
		setcookie('sabnzbd_' . $this->uid . '__priority', '', (time() - 2592000));
		setcookie('sabnzbd_' . $this->uid . '__apitype',  '', (time() - 2592000));
	}
}
