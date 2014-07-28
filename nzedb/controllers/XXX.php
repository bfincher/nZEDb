<?php

require_once nZEDb_LIBS . 'adultdvdempire.php';
require_once nZEDb_LIBS . 'popporn.php';

use nzedb\db\Settings;
use nzedb\utility;

/**
 * Class XXX
 */
class XXX
{
	public $pdo;

	/**
	 * We used AdultDVDEmpire or PopPorn class -- used for template and trailer information
	 *
	 * @var string
	 */
	protected $whichclass = '';

	/**
	 * Current title being passed through various sites/api's.
	 * @var string
	 */
	protected $currentTitle = '';

	/**
	 * @var Debugging
	 */
	protected $debugging;

	/**
	 * @var bool
	 */
	protected $debug;

	/**
	 * @var ReleaseImage
	 */
	protected $releaseImage;

	protected $currentRelID;

	/**
	 * @param bool $echoOutput
	 */
	public function __construct($echoOutput = false)
	{
		$this->c = new ColorCLI();
		$this->pdo = new Settings();
		$this->releaseImage = new ReleaseImage($this->pdo);
		$this->movieqty = ($this->pdo->getSetting('maxxxxprocessed') != '') ? $this->pdo->getSetting('maxxxxprocessed') : 100;
		$this->showPasswords = ($this->pdo->getSetting('showpasswordedrelease') != '') ? $this->pdo->getSetting('showpasswordedrelease') : 0;
		$this->debug = nZEDb_DEBUG;
		$this->echooutput = ($echoOutput && nZEDb_ECHOCLI);
		$this->imgSavePath = nZEDb_COVERS . 'xxx' . DS;
		$this->cookie = nZEDb_TMP . 'xxx.cookie';

		if (nZEDb_DEBUG || nZEDb_LOGGING) {
			$this->debug = true;
			$this->debugging = new Debugging('XXX');
		}
	}

	/**
	 * Get info for a xxx id.
	 *
	 * @param int $xxxid
	 *
	 * @return array|bool
	 */
	public function getXXXInfo($xxxid)
	{
		return $this->pdo->queryOneRow(sprintf("SELECT * FROM xxxinfo WHERE id = %d", $xxxid));
	}

	/**
	 * Get movies for movie-list admin page.
	 *
	 * @param int $start
	 * @param int $num
	 *
	 * @return array
	 */
	public function getRange($start, $num)
	{
		return $this->pdo->query(
			sprintf('
				SELECT *
				FROM xxxinfo
				ORDER BY createddate DESC %s',
				($start === false ? '' : ' LIMIT ' . $num . ' OFFSET ' . $start)
			)
		);
	}

	/**
	 * Get count of movies for movie-list admin page.
	 *
	 * @return int
	 */
	public function getCount()
	{
		$res = $this->pdo->queryOneRow('SELECT COUNT(id) AS num FROM xxxinfo');
		return ($res === false ? 0 : $res['num']);
	}

	/**
	 * Get count of movies for movies browse page.
	 *
	 * @param       $cat
	 * @param       $maxAge
	 * @param array $excludedCats
	 *
	 * @return int
	 */
	public function getXXXCount($cat, $maxAge = -1, $excludedCats = array())
	{
		$catSearch = $this->formCategorySearchSQL($cat);

		$res = $this->pdo->queryOneRow(
			sprintf("
				SELECT COUNT(DISTINCT r.xxxinfo_id) AS num
				FROM releases r
				INNER JOIN xxxinfo m ON m.id = r.xxxinfo_id
				WHERE r.nzbstatus = 1
				AND m.cover = 1
				AND m.title != ''
				AND r.passwordstatus <= %d
				AND %s %s %s %s ",
				$this->showPasswords,
				$this->getBrowseBy(),
				$catSearch,
				($maxAge > 0
					?
					'AND r.postdate > NOW() - INTERVAL ' .
					($this->pdo->dbSystem() === 'mysql'
						? $maxAge . 'DAY '
						: "'" . $maxAge . "DAYS' "
					)
					: ''
				),
				(count($excludedCats) > 0 ? ' AND r.categoryid NOT IN (' . implode(',', $excludedCats) . ')' : '')
			)
		);
		return ($res === false ? 0 : $res['num']);
	}

	/**
	 * Get movie releases with covers for xxx browse page.
	 *
	 * @param       $cat
	 * @param       $start
	 * @param       $num
	 * @param       $orderBy
	 * @param       $maxAge
	 * @param array $excludedCats
	 *
	 * @return bool
	 */
	public function getXXXRange($cat, $start, $num, $orderBy, $maxAge = -1, $excludedCats = array())
	{
		$order = $this->getXXXOrder($orderBy);
		if ($this->pdo->dbSystem() === 'mysql') {
			$sql = sprintf("
				SELECT
				GROUP_CONCAT(r.id ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_id,
				GROUP_CONCAT(r.rarinnerfilecount ORDER BY r.postdate DESC SEPARATOR ',') as grp_rarinnerfilecount,
				GROUP_CONCAT(r.haspreview ORDER BY r.postdate DESC SEPARATOR ',') AS grp_haspreview,
				GROUP_CONCAT(r.passwordstatus ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_password,
				GROUP_CONCAT(r.guid ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_guid,
				GROUP_CONCAT(rn.id ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_nfoid,
				GROUP_CONCAT(groups.name ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_grpname,
				GROUP_CONCAT(r.searchname ORDER BY r.postdate DESC SEPARATOR '#') AS grp_release_name,
				GROUP_CONCAT(r.postdate ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_postdate,
				GROUP_CONCAT(r.size ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_size,
				GROUP_CONCAT(r.totalpart ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_totalparts,
				GROUP_CONCAT(r.comments ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_comments,
				GROUP_CONCAT(r.grabs ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_grabs,
				m.*, groups.name AS group_name, rn.id as nfoid FROM releases r
				LEFT OUTER JOIN groups ON groups.id = r.group_id
				LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id
				INNER JOIN xxxinfo m ON m.id = r.xxxinfo_id
				WHERE r.nzbstatus = 1
				AND m.cover = 1
				AND m.title != ''
				AND r.passwordstatus <= %d AND %s %s %s %s
				GROUP BY m.id ORDER BY %s %s %s",
				$this->showPasswords,
				$this->getBrowseBy(),
				$this->formCategorySearchSQL($cat),
				($maxAge > 0
					? 'AND r.postdate > NOW() - INTERVAL ' . $maxAge . 'DAY '
					: ''
				),
				(count($excludedCats) > 0 ? ' AND r.categoryid NOT IN (' . implode(',', $excludedCats) . ')' : ''),
				$order[0],
				$order[1],
				($start === false ? '' : ' LIMIT ' . $num . ' OFFSET ' . $start)
			);
		} else {
			$sql = sprintf("
				SELECT STRING_AGG(r.id::text, ',' ORDER BY r.postdate DESC) AS grp_release_id,
				STRING_AGG(r.rarinnerfilecount::text, ',' ORDER BY r.postdate DESC) as grp_rarinnerfilecount,
				STRING_AGG(r.haspreview::text, ',' ORDER BY r.postdate DESC) AS grp_haspreview,
				STRING_AGG(r.passwordstatus::text, ',' ORDER BY r.postdate) AS grp_release_password,
				STRING_AGG(r.guid, ',' ORDER BY r.postdate DESC) AS grp_release_guid,
				STRING_AGG(rn.id::text, ',' ORDER BY r.postdate DESC) AS grp_release_nfoid,
				STRING_AGG(groups.name, ',' ORDER BY r.postdate DESC) AS grp_release_grpname,
				STRING_AGG(r.searchname, '#' ORDER BY r.postdate) AS grp_release_name,
				STRING_AGG(r.postdate::text, ',' ORDER BY r.postdate DESC) AS grp_release_postdate,
				STRING_AGG(r.size::text, ',' ORDER BY r.postdate DESC) AS grp_release_size,
				STRING_AGG(r.totalpart::text, ',' ORDER BY r.postdate DESC) AS grp_release_totalparts,
				STRING_AGG(r.comments::text, ',' ORDER BY r.postdate DESC) AS grp_release_comments,
				STRING_AGG(r.grabs::text, ',' ORDER BY r.postdate DESC) AS grp_release_grabs,
				m.*, groups.name AS group_name,
				rn.id as nfoid
				FROM releases r
				LEFT OUTER JOIN groups ON groups.id = r.group_id
				INNER JOIN xxxinfo m ON m.id = r.xxxinfo_id AND m.title != ''
				LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id AND rn.nfo IS NOT NULL
				WHERE r.nzbstatus = 1
				AND r.passwordstatus <= %s
				AND %s %s %s %s
				GROUP BY m.id, groups.name, rn.id
				ORDER BY %s %s %s",
				$this->showPasswords,
				$this->getBrowseBy(),
				$this->formCategorySearchSQL($cat),
				($maxAge > 0
					?
					'AND r.postdate > NOW() - INTERVAL ' .  "'" . $maxAge . "DAYS' "
					: ''
				),
				(count($excludedCats) > 0 ? ' AND r.categoryid NOT IN (' . implode(',', $excludedCats) . ')' : ''),
				$order[0],
				$order[1],
				($start === false ? '' : ' LIMIT ' . $num . ' OFFSET ' . $start)
			);
		}
		return $this->pdo->query($sql);
	}

	/**
	 * Form category search SQL.
	 *
	 * @param $cat
	 *
	 * @return string
	 */
	protected function formCategorySearchSQL($cat)
	{
		$catSearch = '';
		if (count($cat) > 0 && $cat[0] != -1) {
			$catSearch = '(';
			$Category = new Category();
			foreach ($cat as $category) {
				if ($category != -1) {

					if ($Category->isParent($category)) {
						$children = $Category->getChildren($category);
						$chList = '-99';
						foreach ($children as $child) {
							$chList .= ', ' . $child['id'];
						}

						if ($chList != '-99') {
							$catSearch .= ' r.categoryid IN (' . $chList . ') OR ';
						}
					} else {
						$catSearch .= sprintf(' r.categoryid = %d OR ', $category);
					}
				}
			}
			$catSearch .= '1=2)';
		}
		return $catSearch;
	}

	/**
	 * Get the order type the user requested on the movies page.
	 *
	 * @param $orderBy
	 *
	 * @return array
	 */
	protected function getXXXOrder($orderBy)
	{
		$orderArr = explode('_', (($orderBy == '') ? 'MAX(r.postdate)' : $orderBy));
		switch ($orderArr[0]) {
			case 'title':
				$orderField = 'm.title';
				break;
			case 'posted':
			default:
				$orderField = 'MAX(r.postdate)';
				break;
		}

		return array($orderField, ((isset($orderArr[1]) && preg_match('/^asc|desc$/i', $orderArr[1])) ? $orderArr[1] : 'desc'));
	}

	/**
	 * Order types for xxx page.
	 *
	 * @return array
	 */
	public function getXXXOrdering()
	{
		return array('title_asc', 'title_desc');
	}

	/**
	 * @return string
	 */
	protected function getBrowseBy()
	{
		$browseBy = ' ';
		$browseByArr = array('title', 'director', 'actors', 'genre');
		foreach ($browseByArr as $bb) {
			if (isset($_REQUEST[$bb]) && !empty($_REQUEST[$bb])) {
				$bbv = stripslashes($_REQUEST[$bb]);
				if($bb == "genre"){
				$bbv = $this->getgenreid($bbv);
				}
					$browseBy .= 'm.' . $bb . ' LIKE (' . $this->pdo->escapeString('%' . $bbv . '%') . ') AND ';
			}
		}
		return $browseBy;
	}

	/**
	 * Create click-able links to actors/genres/directors/etc..
	 *
	 * @param $data
	 * @param $field
	 *
	 * @return string
	 */
	public function makeFieldLinks($data, $field)
	{
		if (!isset($data[$field]) || $data[$field] == '') {
			return '';
		}

		$tmpArr = explode(',', $data[$field]);
		$newArr = array();
		$i = 0;
		foreach ($tmpArr as $ta) {
			if($field == "genre" ){
			$ta = $this->getGenres(true,$ta);
			$ta = $ta["title"];
			}
			if ($i > 5) {
				break;
			} //only use first 6
			$newArr[] = '<a href="' . WWW_TOP . '/xxx?' . $field . '=' . urlencode($ta) . '" title="' . $ta . '">' . $ta . '</a>';
			$i++;
		}
		return implode(', ', $newArr);
	}
	/**
	 * Update movie on movie-edit page.
	 *
	 *@param $id
	 * @param $title
	 * @param $tagline
	 * @param $plot
	 * @param $genre
	 * @param $director
	 * @param $actors
	 * @param $cover
	 * @param $backdrop
	 */
	public function update(
		$id = '', $title = '', $tagline = '', $plot = '', $genre = '', $director = '',
		$actors = '', $cover = '', $backdrop = ''
	)
	{
		if (!empty($id)) {

			$this->pdo->queryExec(
				sprintf("
					UPDATE xxxinfo
					SET %s, %s, %s, %s, %s, %s, %d, %d, updateddate = NOW()
					WHERE id = %d",
					(empty($title)    ? '' : 'title = '    . $this->pdo->escapeString($title)),
					(empty($tagLine)  ? '' : 'tagline = '  . $this->pdo->escapeString($tagLine)),
					(empty($plot)     ? '' : 'plot = '     . $this->pdo->escapeString($plot)),
					(empty($genre)    ? '' : 'genre = '    . $this->pdo->escapeString($genre)),
					(empty($director) ? '' : 'director = ' . $this->pdo->escapeString($director)),
					(empty($actors)   ? '' : 'actors = '   . $this->pdo->escapeString($actors)),
					(empty($cover)    ? '' : 'cover = '    . $cover),
					(empty($backdrop) ? '' : 'backdrop = ' . $backdrop),
					$id
				)
			);
		}
	}

	/**
	 * Fetch xxx info for the movie.
	 *
	 * @param $xxxmovie
	 *
	 * @return bool
	 */
	public function updateXXXInfo($xxxmovie)
	{

		$res = false;
		$this->whichclass = '';
		// Check Adultdvdempire for xxx info.
		$mov = new adultdvdempire();
		$mov->searchterm = $xxxmovie;
		$res = $mov->search();
		$this->whichclass = "ade";
		if ($res === false) {
			$this->whichclass = "pop";
			// IF no result from Adultdvdempire check popporn
			$mov = new popporn();
			$mov->cookie = $this->cookie;
			$mov->searchterm = $xxxmovie;
			$res = $mov->search();
		}
		// If a result is true getall information.
		if ($res !== false) {
			if ($this->echooutput) {
				$this->c->doEcho($this->c->primary("Fetching XXX info for: " . $xxxmovie));
			}
			$res = $mov->_getall();
		} else {
			return false;
		}

		$mov = array();

		$mov['trailers'] = (isset($res['trailers'])) ? serialize($res['trailers']) : '';
		$mov['extras'] = (isset($res['extras'])) ? serialize($res['extras']) : '';
		$mov['productinfo'] = (isset($res['productinfo'])) ? serialize($res['productinfo']) : '';
		$mov['backdrop'] = (isset($res['backcover'])) ? $res['backcover'] : '';
		$mov['cover'] = (isset($res['boxcover'])) ? $res['boxcover'] : '';
		$res['cast'] = (isset($res['cast'])) ? join(",", $res['cast']) : '';
		$res['genres'] = (isset($res['genres'])) ? $this->getgenreid($res['genres']) : '';
		$mov['title'] = html_entity_decode($res['title'], ENT_QUOTES, 'UTF-8');
		$mov['plot'] = (isset($res['sypnosis'])) ? html_entity_decode($res['sypnosis'], ENT_QUOTES, 'UTF-8') : '';
		$mov['tagline'] = (isset($res['tagline'])) ? html_entity_decode($res['tagline'], ENT_QUOTES, 'UTF-8') : '';
		$mov['genre'] = html_entity_decode($res['genres'], ENT_QUOTES, 'UTF-8');
		$mov['director'] = (isset($res['director'])) ? html_entity_decode($res['director'], ENT_QUOTES, 'UTF-8') : '';
		$mov['actors'] = html_entity_decode($res['cast'], ENT_QUOTES, 'UTF-8');
		$mov['directurl'] = html_entity_decode($res['directurl'], ENT_QUOTES, 'UTF-8');
		$mov['classused'] = $this->whichclass;
		$check = $this->pdo->queryOneRow(sprintf('SELECT id FROM xxxinfo WHERE title = %s',	$this->pdo->escapeString($mov['title'])));
		$xxxID=null;
		if($check === false){
		if ($this->pdo->dbSystem() === 'mysql') {
			$xxxID = $this->pdo->queryInsert(
				sprintf("
					INSERT INTO xxxinfo
						(title, tagline, plot, genre, director, actors, extras, productinfo, trailers, directurl, classused, cover, backdrop, createddate, updateddate)
					VALUES
						(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, %d, NOW(), NOW())
					ON DUPLICATE KEY UPDATE
						title = %s, tagline = %s, plot = %s, genre = %s, director = %s, actors = %s, extras = %s, productinfo = %s, trailers = %s, directurl = %s, classused = %s, cover = %d, backdrop = %d, updateddate = NOW()",
					$this->pdo->escapeString($mov['title']),
					$this->pdo->escapeString($mov['tagline']),
					$this->pdo->escapeString($mov['plot']),
					$this->pdo->escapeString(substr($mov['genre'], 0, 64)),
					$this->pdo->escapeString($mov['director']),
					$this->pdo->escapeString($mov['actors']),
					$this->pdo->escapeString($mov['extras']),
					$this->pdo->escapeString($mov['productinfo']),
					$this->pdo->escapeString($mov['trailers']),
					$this->pdo->escapeString($mov['directurl']),
					$this->pdo->escapeString($mov['classused']),
					0,
					0,
					$this->pdo->escapeString($mov['title']),
					$this->pdo->escapeString($mov['tagline']),
					$this->pdo->escapeString($mov['plot']),
					$this->pdo->escapeString(substr($mov['genre'], 0, 64)),
					$this->pdo->escapeString($mov['director']),
					$this->pdo->escapeString($mov['actors']),
					$this->pdo->escapeString($mov['extras']),
					$this->pdo->escapeString($mov['productinfo']),
					$this->pdo->escapeString($mov['trailers']),
					$this->pdo->escapeString($mov['directurl']),
					$this->pdo->escapeString($mov['classused']),
					0,
					0
				)
			);
		} else if ($this->pdo->dbSystem() === 'pgsql') {
				$xxxID = $this->pdo->queryInsert(
					sprintf("
						INSERT INTO xxxinfo
							(title, tagline, plot, genre, director, actors, extras, productinfo, trailers, directurl, classused, cover, backdrop, createddate, updateddate)
						VALUES
							(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, %d, NOW(), NOW())",
						$this->pdo->escapeString($mov['title']),
						$this->pdo->escapeString($mov['tagline']),
						$this->pdo->escapeString($mov['plot']),
						$this->pdo->escapeString($mov['genre']),
						$this->pdo->escapeString($mov['director']),
						$this->pdo->escapeString($mov['actors']),
						$this->pdo->escapeString($mov['extras']),
						$this->pdo->escapeString($mov['productinfo']),
						$this->pdo->escapeString($mov['trailers']),
						$this->pdo->escapeString($mov['directurl']),
						$this->pdo->escapeString($mov['classused']),
						0,
						0
					)
				);
			}
		if($xxxID !== false){

			// BoxCover.
			if(isset($mov['cover'])){
			$mov['cover'] = $this->releaseImage->saveImage($xxxID . '-cover', $mov['cover'], $this->imgSavePath);

			}
			// BackCover.
			if(isset($mov['backdrop'])){
			$mov['backdrop'] = $this->releaseImage->saveImage($xxxID . '-backdrop', $mov['backdrop'], $this->imgSavePath, 1920, 1024);
			}
			$this->pdo->queryExec(sprintf('UPDATE xxxinfo SET cover = %d, backdrop = %d  WHERE id = %d', $mov['cover'], $mov['backdrop'], $xxxID));
		}
		}else{
		// If xxxinfo title is found, update release with the current xxxinfo id because it was nulled before..
			$this->pdo->queryExec(sprintf('UPDATE releases SET xxxinfo_id = %d  WHERE id = %d', $check['id'], $this->currentRelID));
			$xxxID=$check['id'];
		}
		if ($this->echooutput) {
			$this->c->doEcho(
				$this->c->headerOver(($xxxID !== false ? 'Added/updated movie: ' : 'Nothing to update for xxx movie: ')) .
				$this->c->primary($mov['title'])
			);
		}
		return $xxxID;
	}

	/**
	 * Process releases with no xxxinfo ID's.
	 *
	 */

	public function processXXXReleases()
	{
		// Get all releases without an IMpdo id.
			$res = $this->pdo->query(
				sprintf("
					SELECT r.searchname, r.id
					FROM releases r
					WHERE r.nzbstatus = 1
					AND r.xxxinfo_id = 0
					AND r.categoryid BETWEEN 6000 AND 6040
					AND r.isrenamed = 1
					LIMIT %d",
					$this->movieqty
				)
			);
			$movieCount = count($res);

		if ($movieCount > 0) {
			if ($this->echooutput && $movieCount > 1) {
				$this->c->doEcho($this->c->header("Processing " . $movieCount . " XXX releases."));
			}

			// Loop over releases.
			foreach ($res as $arr) {
				// Try to get a name.
				if ($this->parseXXXSearchName($arr['searchname']) === false) {
					//We didn't find a name, so set to -2 so we don't parse again.
					$this->pdo->queryExec(sprintf("UPDATE releases SET xxxinfo_id = %d WHERE id = %d", -2, $arr["id"]));
					continue;
				} else {
					$this->currentRelID = $arr['id'];

					$movieName = $this->currentTitle;

					if ($this->echooutput) {
						$this->c->doEcho($this->c->primaryOver("Looking up: ") . $this->c->headerOver($movieName), true);
						$idcheck = $this->updateXXXInfo($movieName);
					}
					if($idcheck == false){
						// No Release was found, set to -2 so we don't parse again.
						$this->pdo->queryExec(sprintf('UPDATE releases SET xxxinfo_id = %d WHERE id = %d', -2, $arr['id']));
						continue;
					}else{
						// Release Found, set xxxinfo_id
						$this->pdo->queryExec(sprintf('UPDATE releases SET xxxinfo_id = %d  WHERE id = %d',$idcheck, $this->currentRelID));
						continue;
					}


				}
			}
		}
	}

	/**
	 * Parse a xxx name from a release search name.
	 *
	 * @param string $releaseName
	 *
	 * @return bool
	 */
	protected function parseXXXSearchName($releaseName)
	{
		// Check if it's foreign ?
		$cat = new Categorize();
		if (!$cat->isMovieForeign($releaseName)) {
			$name = '';
			$followingList = '[^\w]((1080|480|720)p|AC3D|Directors([^\w]CUT)?|DD5\.1|(DVD|BD|BR)(Rip)?|BluRay|divx|HDTV|iNTERNAL|LiMiTED|(Real\.)?Proper|RE(pack|Rip)|Sub\.?(fix|pack)|Unrated|WEB-DL|(x|H)[-._ ]?264|xvid|XXX|BTS)[^\w]';

			/* Initial scan of getting a name.
			 * [\w. -]+ Gets 0-9a-z. - characters, most scene movie titles contain these chars.
			 * ie: [61420]-[FULL]-[a.b.foreignEFNet]-[ Coraline.2009.DUTCH.INTERNAL.1080p.BluRay.x264-VeDeTT ]-[21/85] - "vedett-coralien-1080p.r04" yEnc
			 */
			if (preg_match('/([^\w]{2,})?(?P<name>[\w .-]+?)' . $followingList . '/i', $releaseName, $matches)) {
				$name = $matches['name'];
			}

			// Check if we got something.
			if ($name !== '') {

				// If we still have any of the words in $followingList, remove them.
				$name = preg_replace('/' . $followingList . '/i', ' ', $name);
				// Remove periods, underscored, anything between parenthesis.
				$name = preg_replace('/\(.*?\)|[._]/i', ' ', $name);
				// Finally remove multiple spaces and trim leading spaces.
				$name = trim(preg_replace('/\s{2,}/', ' ', $name));
					// Check if the name is long enough and not just numbers and not file (d) of (d).
				if (strlen($name) > 5 && !preg_match('/^\d+$/', $name) && !preg_match('/(- File \d+ of \d+|\d+.\d+.\d+)/',$name)) {
					if ($this->debug && $this->echooutput) {
						$this->c->doEcho("DB name: {$releaseName}", true);
					}
					$this->currentTitle = $name;
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Get all genres for search-filter.tpl
	 *
	 * @param bool $activeOnly
	 *
	 * @return array|null
	 */
	public function getallgenres($activeOnly=false){
		$i=0;
		$res = null;
		$ret = null;
		if ($activeOnly) {
			$res= $this->pdo->query("SELECT title FROM genres WHERE disabled = 0 AND type = 6000 ORDER BY title");
		} else {
			$res= $this->pdo->query("SELECT title FROM genres WHERE disabled = 1 AND type = 6000 ORDER BY title");
		}
		foreach($res as $arr => $value){
			$ret[] = $value['title'];

		}
		return $ret;
	}

	/**
	 * Get Genres for activeonly and/or an ID
	 *
	 * @param bool $activeOnly
	 * @param null $gid
	 *
	 * @return array|bool
	 */
	public function getGenres($activeOnly=false, $gid=null)
	{
		if(isset($gid)){
		$gid = " AND id = ". $this->pdo->escapeString($gid) . " ORDER BY title";
		}else{
		$gid = " ORDER BY title";
		}
		if ($activeOnly) {
			return $this->pdo->queryOneRow("SELECT title FROM genres WHERE disabled = 0 AND type = 6000".$gid);
		} else {
			return $this->pdo->queryOneRow("SELECT title FROM genres WHERE disabled = 1 AND type = 6000".$gid);
		}
	}

	/**
	 * Get Genre ID's Of the title
	 *
	 * @param $arr - Array or String
	 *
	 * @return string - If array .. 1,2,3,4 if string .. 1
	 */
	private function getGenreID($arr)
	{
			$ret = null;
		if(!is_array($arr)){
		$res = $this->pdo->queryOneRow("SELECT id FROM genres WHERE title = " . $this->pdo->escapeString($arr));
		if($res !== false){
		return $res["id"];
		}
		}
			foreach($arr as $key => $value){
			$res = $this->pdo->queryOneRow("SELECT id FROM genres WHERE title = ".$this->pdo->escapeString($value));
			if($res !== false){
				$ret .= "," . $res["id"];
			}else{
			$ret .= "," . $this->insertGenre($value);
	}
			}
		$ret = ltrim($ret,",");
		return ($ret);
		}

	/**
	 * Inserts Genre and returns last affected row (Genre ID)
	 *
	 * @param $genre
	 *
	 * @return bool
	 */
	private function insertGenre($genre)
	{
		if (isset($genre)) {
			$res = $this->pdo->queryInsert(sprintf("INSERT INTO genres (title, type, disabled) VALUES (%s ,%d ,%d)",$this->pdo->escapeString($genre),	6000, 0));
			return $res;
		}
	}

	/**
	 * Inserts Trailer Code by Class
	 *
	 * @param $whichclass
	 * @param $res
	 *
	 * @return string
	 */
	public function insertswf($whichclass, $res)
	{
		if ($whichclass === "ade") {
			$ret = '';
			if (!empty($res)) {
				$trailers = unserialize($res);
				$ret .="<object width='360' height='240' type='application/x-shockwave-flash' id='EmpireFlashPlayer' name='EmpireFlashPlayer' data='".	$trailers['url'] . "'>";
				$ret .= "<param name='flashvars' value= 'streamID=" . $trailers['streamid'] . "&amp;autoPlay=false&amp;BaseStreamingUrl=" . $trailers['baseurl'] . "'>";
				$ret .= "</object>";

				return ($ret);
			}
		}
		if ($whichclass === "pop") {
			$ret = '';
			if (!empty($res)) {
				$trailers = unserialize($res);
				$ret .= "<embed id='trailer' width='480' height='360'";
				$ret .= "flashvars='" .	$trailers['flashvars'] . "' allowfullscreen='true' allowscriptaccess='always' quality='high' name='trailer' style='undefined'";
				$ret .= "src='" . $trailers['baseurl'] . "' type='application/x-shockwave-flash'>";

				return ($ret);
			}
		}
}
}
