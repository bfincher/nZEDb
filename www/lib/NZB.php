<?php

/**
 * Class for reading and writing NZB files on the hard disk,
 * building folder paths to store the NZB files.
 */
class NZB {

	/**
	 * Instance of class site.
	 *
	 * @var object
	 * @access private
	 */
	private $s;

	/**
	 * Site settings.
	 *
	 * @var object
	 * @access private
	 */
	private $site;

	/**
	 * Determines if the site setting tablepergroup is enabled.
	 *
	 * @var int
	 * @access private
	 */
	private $tablepergroup;

	/**
	 * Default constructor.
	 *
	 * @access public
	 */
	public function __construct() {
		$this->s = new Sites();
		$this->site = $this->s->get();
		$this->tablepergroup =
			(isset($this->site->tablepergroup)) ? $this->site->tablepergroup : 0;
	}

	/**
	 * Write an NZB to the hard drive for a single release.
	 *
	 * @param int    $relid   The ID of the release in the DB.
	 * @param string $relguid The guid of the release.
	 * @param string $name    The name of the release.
	 * @param string $path    The location to save the NZB to.
	 * @param object $db      Instance of class DB.
	 * @param string $version The current version number of nZEDb.
	 * @param string $date    The current date and time.
	 * @param strint $ctitle  The name of the category this release is in.
	 * @param int    $group   The ID of the group this release is in.
	 *
	 * @return bool Have we succesfully written the NZB to the hard drive?
	 *
	 * @access public
	 */
	public function writeNZBforReleaseId($relid, $relguid, $name, $path, $db,
		$version, $date, $ctitle, $groupID) {
		// Set table names
		if ($this->tablepergroup == 1) {
			if ($groupID == '') {
				exit("$groupID is missing, are you running grabnzbs_threaded.py\n");
			}
			$group['cname'] = 'collections_'.$groupID;
			$group['bname'] = 'binaries_'.$groupID;
			$group['pname'] = 'parts_'.$groupID;
		} else {
			$group['cname'] = 'collections';
			$group['bname'] = 'binaries';
			$group['pname'] = 'parts';
		}

		if ($relid == '' || $relguid == '' || $path == '') {
			return false;
		}

		$fp = gzopen($path, 'w5');
		if ($fp) {
			$nzb_guid = '';
			gzwrite($fp,
			  "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<!DOCTYPE nzb PUBLIC "
			. "\"-//newzBin//DTD NZB 1.1//EN\" "
			. "\"http://www.newzbin.com/DTD/nzb/nzb-1.1.dtd\">\n<!-- NZB Generated by: nZEDb "
			. $version . ' ' . $date
			. " -->\n<nzb xmlns=\"http://www.newzbin.com/DTD/2003/nzb\">\n<head>\n"
			. ' <meta type="category">'
			. htmlspecialchars($ctitle, ENT_QUOTES, 'utf-8')
			. "</meta>\n <meta type=\"name\">"
			. htmlspecialchars($name, ENT_QUOTES, 'utf-8')
			. "</meta>\n</head>\n\n");

			$result = $db->query(sprintf(
				  'SELECT ' . $group['cname'] . '.*, ' . $group['cname']
				. '.date AS udate, groups.name AS groupname FROM '
				. $group['cname'] . ' INNER JOIN groups ON '
				. $group['cname'] .'.groupid = groups.id WHERE '
				. $group['cname'] .'.releaseid = %d', $relid));

			foreach ($result as $binrow) {
				$unixdate = strtotime($binrow['udate']);
				$result2 = $db->query(sprintf(
					 'SELECT id, name, totalparts FROM '
					. $group['bname']
					. ' WHERE collectionid = %d ORDER BY name', $binrow['id']));

				foreach ($result2 as $binrow2) {
					gzwrite($fp,
						  '<file poster="'
						. htmlspecialchars($binrow['fromname'], ENT_QUOTES, 'utf-8')
						. '" date="'.$unixdate . '" subject="'
						. htmlspecialchars($binrow2['name'], ENT_QUOTES, 'utf-8')
						. ' (1/'.$binrow2['totalparts']
						. ")\">\n <groups>\n  <group>" . $binrow['groupname']
						. "</group>\n </groups>\n <segments>\n");

					$resparts = $db->query(sprintf(
						 'SELECT DISTINCT(messageid), size, partnumber FROM '
						. $group['pname']
						. ' WHERE binaryid = %d ORDER BY partnumber', $binrow2['id']));

					foreach ($resparts as $partsrow) {
						if ($nzb_guid === '') {
							$nzb_guid = $partsrow['messageid'];
						}

						gzwrite($fp,
							  '  <segment bytes="' . $partsrow['size']
							. '" number="' . $partsrow['partnumber'] . '">'
							. htmlspecialchars($partsrow['messageid'], ENT_QUOTES, 'utf-8')
							. "</segment>\n");
					}
					gzwrite($fp, " </segments>\n</file>\n");
				}
			}
			gzwrite($fp, '</nzb>');
			gzclose($fp);

			if (file_exists($path)) {
				if ($nzb_guid === '') {
					$db->queryExec(sprintf(
						'UPDATE releases SET bitwise = (bitwise & ~256)|256 WHERE id = %d'
						, $relid));
				} else {
					$db->queryExec(sprintf(
						'UPDATE releases SET bitwise = (bitwise & ~256)|256, nzb_guid = %s WHERE id = %d'
						, $db->escapestring(md5($nzb_guid)), $relid));
				}

				// Chmod to fix issues some users have with file permissions.
				chmod($path, 0777);
				return true;
			} else {
				echo 'ERROR: '.$path." does not exist.\n";
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Compress an imported NZB and store it inside the nzbfiles folder.
	 *
	 * @param string $relguid    The guid of the release.
	 * @param string $nzb        String containing the imported NZB.
	 * @param bool   $echooutput Unused?
	 *
	 * @access public
	 *
	 * @TODO: Remove $echooutput ?
	 */
	public function copyNZBforImport($relguid, $nzb, $echooutput=false) {
		$version = $this->site->version;
		$page = new Page();
		$path = $this->getNZBPath(
			$relguid, $page->site->nzbpath, true, $page->site->nzbsplitlevel);
		$fp = gzopen($path, 'w5');
		if ($fp && $nzb) {
			$date1 = htmlspecialchars(date('F j, Y, g:i a O'), ENT_QUOTES, 'utf-8');
			$article = preg_replace(
				 '/dtd">\s*<nzb xmlns=/', "dtd\">\n<!-- NZB Generated by: nZEDb "
				. $version . ' ' . $date1 . " -->\n<nzb xmlns=", $nzb);

			gzwrite($fp, preg_replace(
				  '/<\/file>\s*(<!--.+)?\s*<\/nzb>\s*/si'
				, "</file>\n  <!--GrabNZBs-->\n</nzb>"
				, $article));

			gzclose($fp);
			// Chmod to fix issues some users have with file permissions.
			chmod($path, 0777);
			return true;
		} else {
			echo "ERROR: NZB already exists?\n";
			return false;
		}
	}

	/**
	 * Build a folder path on the hard drive where the NZB file will be stored.
	 *
	 * @param string $releaseGuid         The guid of the release.
	 * @param string $sitenzbpath         The master folder to store NZB files.
	 * @param bool   $createIfDoesntExist Create the folder if it doesn't exist.
	 * @param int    $levelsToSplit       How many sub-paths the folder will be in.
	 *
	 * @return string $nzbpath The path to store the NZB file.
	 *
	 * @access public
	 */
	public function buildNZBPath($releaseGuid, $sitenzbpath = '',
		$createIfDoesntExist = false, $levelsToSplit = 1) {
		if ($sitenzbpath == '') {

			$sitenzbpath = $this->site->nzbpath;
			if (substr($sitenzbpath, strlen($sitenzbpath) - 1) != '/') {
				$sitenzbpath = $sitenzbpath . '/';
			}
			$levelsToSplit = $this->site->nzbsplitlevel;
		}

		$subpath = '';

		for ($i = 0; $i < $levelsToSplit; $i++) {
			$subpath = $subpath . substr($releaseGuid, $i, 1) . '/';
		}

		$nzbpath = $sitenzbpath . $subpath;

		if ($createIfDoesntExist && !file_exists($nzbpath)) {
			mkdir($nzbpath, 0777, true);
		}

		return $nzbpath;
	}

	/**
	 * Retrieve path + filename of the NZB to be stored.
	 *
	 * @param string $releaseGuid         The guid of the release.
	 * @param string $sitenzbpath         The master folder to store NZB files.
	 * @param bool   $createIfDoesntExist Create the folder if it doesn't exist.
	 * @param int    $levelsToSplit       How many sub-paths the folder will be in.
	 *
	 * @return string Path+filename.
	 *
	 * @access public
	 */
	public function getNZBPath($releaseGuid, $sitenzbpath = '',
		$createIfDoesntExist = false, $levelsToSplit = 1) {
		$nzbpath = $this->buildNZBPath(
			$releaseGuid, $sitenzbpath, $createIfDoesntExist, $levelsToSplit);
		return $nzbpath . $releaseGuid . '.nzb.gz';
	}

	// Check if the NZB is there, returns path, else false.
	/**
	 * Determine is an NZB exists, returning the path+filename, if not return false.
	 *
	 * @param string $releaseGuid   The guid of the release.
	 * @param string $sitenzbpath   The master folder to store NZB files.
	 * @param int    $levelsToSplit How many sub-paths the folder will be in.
	 *
	 * @return bool   If false.
	 * @return string Path+file name of the nzb.
	 *
	 * @access public
	 */
	public function NZBPath($releaseGuid, $sitenzbpath = '', $levelsToSplit = 1) {
		$nzbfile = $this->getNZBPath(
			$releaseGuid, $sitenzbpath, false, $levelsToSplit);
		return !file_exists($nzbfile) ? false : $nzbfile;
	}

	/**
	 * Retrieve various information on a NZB file (the subject, # of pars,
	 * file extensions, file sizes, file completion, group names, # of parts).
	 *
	 * @param string $nzb The NZB contents in a string.
	 *
	 * @return array $result Empty if not an NZB or the contents of the NZB.
	 *
	 * @access public
	 */
	public function nzbFileList($nzb) {
		$num_pars = $i = 0;
		$result = array();

		$nzb = str_replace("\x0F", '', $nzb);
		$xml = @simplexml_load_string($nzb);
		if (!$xml || strtolower($xml->getName()) != 'nzb') {
			return $result;
		}

		foreach($xml->file as $file) {
			// Subject.
			$title = $file->attributes()->subject;

			// Amoune of pars.
			if (preg_match('/\.par2/i', $title)) {
				$num_pars++;
			}

			$result[$i]['title'] = $title;

			// Extensions.
			if (preg_match(
				  '/\.(\d{2,3}|7z|ace|ai7|srr|srt|sub|aiff|asc|avi|audio|bin|bz2|'
				. 'c|cfc|cfm|chm|class|conf|cpp|cs|css|csv|cue|deb|divx|doc|dot|'
				. 'eml|enc|exe|file|gif|gz|hlp|htm|html|image|iso|jar|java|jpeg|'
				. 'jpg|js|lua|m|m3u|mm|mov|mp3|mpg|nfo|nzb|odc|odf|odg|odi|odp|'
				. 'ods|odt|ogg|par2|parity|pdf|pgp|php|pl|png|ppt|ps|py|r\d{2,3}|'
				. 'ram|rar|rb|rm|rpm|rtf|sfv|sig|sql|srs|swf|sxc|sxd|sxi|sxw|tar|'
				. 'tex|tgz|txt|vcf|video|vsd|wav|wma|wmv|xls|xml|xpi|xvid|zip7|zip)'
				. '[" ](?!(\)|\-))/i', $file->attributes()->subject, $ext)) {

				if (preg_match('/\.r\d{2,3}/i', $ext[0])) {
					$ext[1] = 'rar';
				}

				$result[$i]['ext'] = strtolower($ext[1]);

			} else {
				$result[$i]['ext'] = '';
			}

			$filesize = $numsegs = 0;

			// File size.
			foreach($file->segments->segment as $segment) {
				$filesize += $segment->attributes()->bytes;
				$numsegs++;
			}
			$result[$i]['size'] = $filesize;

			// File completion.
			if (preg_match('/(\d+)\)$/', $title, $parts)) {
				$result[$i]['partstotal'] = $parts[1];
			}
			$result[$i]['partsactual'] = $numsegs;

			// Groups.
			if (!isset($result[$i]['groups'])) {
				$result[$i]['groups'] = array();
			}
			foreach ($file->groups->group as $g) {
				array_push($result[$i]['groups'], (string)$g);
			}

			// Parts.
			if (!isset($result[$i]['segments'])) {
				$result[$i]['segments'] = array();
			}
			foreach ($file->segments->segment as $s) {
				array_push($result[$i]['segments'], (string)$s);
			}

			unset($result[$i]['segments']['@attributes']);
			$i++;
		}
		return $result;
	}
}
?>
