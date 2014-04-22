<?php
/**
 * Gets information contained within the NZB.
 *
 * Class NZBContents
 */
Class NZBContents
{
	/**
	 * @var nzedb\db\DB
	 */
	protected $db;

	/**
	 * @var NNTP
	 */
	protected $nntp;

	/**
	 * @var Nfo
	 */
	protected $nfo;

	/**
	 * @var PostProcess
	 */
	protected $pp;

	/**
	 * @var NZB
	 */
	protected $nzb;

	/**
	 * @var bool|stdClass
	 */
	protected $site;

	/**
	 * @var bool
	 */
	protected $alternateNNTP;

	/**
	 * @var int
	 */
	protected $lookuppar2;

	/**
	 * @var bool
	 */
	protected $echooutput;

	/**
	 * Construct.
	 *
	 * @param array $options
	 *     array(
	 *         'echo'  => bool        ; To echo to CLI or not.
	 *         'nntp'  => NNTP        ; Class NNTP.
	 *         'nfo'   => Nfo         ; Class Nfo.
	 *         'db'    => DB          ; Class nzedb\db\DB.
	 *         'pp'    => PostProcess ; Class PostProcess.
	 *     )
	 */
	public function __construct($options)
	{
		$this->echooutput = ($options['echo'] && nZEDb_ECHOCLI);
		$s = new Sites();
		$this->site = $s->get();
		$this->lookuppar2 = (isset($this->site->lookuppar2)) ? $this->site->lookuppar2 : 0;
		$this->alternateNNTP = ($this->site->alternate_nntp === '1' ? true : false);
		$this->db   = $options['db'];
		$this->nntp = $options['nntp'];
		$this->nfo  = $options['nfo'];
		$this->pp   = $options['pp'];
		$this->nzb  = new NZB();
	}

	/**
	 * Look for an .nfo file in the NZB, return the NFO message id.
	 * Gets the NZB completion.
	 * Looks for PAR2 files in the NZB.
	 *
	 * @param string $guid
	 * @param string $relID
	 * @param int    $groupID
	 * @param string $groupName
	 *
	 * @return bool
	 */
	public function getNfoFromNZB($guid, $relID, $groupID, $groupName)
	{
		$fetchedBinary = false;

		$messageID = $this->parseNZB($guid, $relID, $groupID, true);
		if ($messageID !== false) {
			$fetchedBinary = $this->nntp->getMessages($groupName, $messageID['ID'], $this->alternateNNTP);
			if ($this->nntp->isError($fetchedBinary)) {
				// NFO download failed, increment attempts.
				$this->db->queryExec(sprintf('UPDATE releases SET nfostatus = nfostatus - 1 WHERE id = %d', $relID));
				if ($this->echooutput) {
					echo 'f';
				}
				$fetchedBinary = false;
			}
			if ($this->nfo->isNFO($fetchedBinary, $guid) === true) {
				if ($this->echooutput) {
					echo ($messageID['hidden'] === false ? '+' : '*');
				}
			} else {
				if ($this->echooutput) {
					echo '-';
				}
				$this->db->queryExec(sprintf('UPDATE releases SET nfostatus = 0 WHERE id = %d', $relID));
			}
		} else {
			if ($this->echooutput) {
				echo '-';
			}
			$this->db->queryExec(sprintf('UPDATE releases SET nfostatus = 0 WHERE id = %d', $relID));
		}

		return $fetchedBinary;
	}

	/**
	 * Attempts to get the releasename from a par2 file
	 *
	 * @param string $guid
	 * @param int    $relID
	 * @param int    $groupID
	 * @param int    $namestatus
	 * @param int    $show
	 *
	 * @return bool
	 */
	public function checkPAR2($guid, $relID, $groupID, $namestatus, $show)
	{
		$nzbfile = $this->LoadNZB($guid);
		if ($nzbfile !== false) {
			foreach ($nzbfile->file as $nzbcontents) {
				if (preg_match('/\.(par[2" ]|\d{2,3}").+\(1\/1\)$/i', $nzbcontents->attributes()->subject)) {
					if ($this->pp->parsePAR2($nzbcontents->segments->segment, $relID, $groupID, $this->nntp, $show) === true && $namestatus === 1) {
						$this->db->queryExec(sprintf('UPDATE releases SET proc_par2 = 1 WHERE id = %d', $relID));
						return true;
					}
				}
			}
		}
		if ($namestatus === 1) {
			$this->db->queryExec(sprintf('UPDATE releases SET proc_par2 = 1 WHERE id = %d', $relID));
		}
		return false;
	}

	/**
	 * Gets the completion from the NZB, optionally looks if there is an NFO/PAR2 file.
	 *
	 * @param string $guid
	 * @param int    $relID
	 * @param int    $groupID
	 * @param bool   $nfoCheck
	 *
	 * @return array|bool
	 */
	public function parseNZB($guid, $relID, $groupID, $nfoCheck = false)
	{
		$nzbFile = $this->LoadNZB($guid);
		if ($nzbFile !== false) {
			$messageID = $hiddenID = '';
			$actualParts = $artificialParts = 0;
			$foundPAR2 = false;
			$foundNFO = $hiddenNFO = ($nfoCheck === false ? true : false);

			foreach ($nzbFile->file as $nzbcontents) {
				foreach ($nzbcontents->segments->segment as $segment) {
					$actualParts++;
				}

				$subject = $nzbcontents->attributes()->subject;
				if (preg_match('/(\d+)\)$/', $subject, $parts)) {
					$artificialParts = $artificialParts + $parts[1];
				}

				if ($foundNFO === false) {
					if (preg_match('/\.\b(nfo|inf|ofn)\b(?![ .-])/i', $subject)) {
						$messageID = (string)$nzbcontents->segments->segment;
						$foundNFO = true;
					}
				}

				if ($foundNFO === false && $hiddenNFO === false) {
					if (preg_match('/\(1\/1\)$/i', $subject) &&
						!preg_match('/\.(apk|bat|bmp|cbr|cbz|cfg|css|csv|cue|db|dll|doc|epub|exe|gif|htm|ico|idx|ini' .
							'|jpg|lit|log|m3u|mid|mobi|mp3|nib|nzb|odt|opf|otf|par|par2|pdf|psd|pps|png|ppt|r\d{2,4}' .
							'|rar|sfv|srr|sub|srt|sql|rom|rtf|tif|torrent|ttf|txt|vb|vol\d+\+\d+|wps|xml|zip)/i',
							$subject))
					{
						$hiddenID = (string)$nzbcontents->segments->segment;
						$hiddenNFO = true;
					}
				}

				if ($this->lookuppar2 == 1 && $foundPAR2 === false) {
					if (preg_match('/\.(par[2" ]|\d{2,3}").+\(1\/1\)$/i', $subject)) {
						if ($this->pp->parsePAR2((string)$nzbcontents->segments->segment, $relID, $groupID, $this->nntp, 1) === true) {
							$this->db->queryExec(sprintf('UPDATE releases SET proc_par2 = 1 WHERE id = %d', $relID));
							$foundPAR2 = true;
						}
					}
				}
			}

			if ($artificialParts <= 0 || $actualParts <= 0) {
				$completion = 0;
			} else {
				$completion = ($actualParts / $artificialParts) * 100;
			}
			if ($completion > 100) {
				$completion = 100;
			}

			$this->db->queryExec(sprintf('UPDATE releases SET completion = %d WHERE id = %d', $completion, $relID));
			if ($foundNFO === true) {
				return array('hidden' => false, 'ID' => $messageID);
			} elseif ($hiddenNFO === true) {
				return array('hidden' => true, 'ID' => $hiddenID);
			}
		}
		return false;
	}

	/**
	 * Decompress a NZB, load it into simplexml and return.
	 *
	 * @param string $guid Release guid.
	 *
	 * @return bool|SimpleXMLElement
	 */
	protected function LoadNZB($guid)
	{
		// Fetch the NZB location using the GUID.
		$nzbpath = $this->nzb->NZBPath($guid);
		if ($nzbpath === false) {
			if ($this->echooutput) {
				echo PHP_EOL . $guid . " appears to be missing the nzb file, skipping." . PHP_EOL;
			}
			return false;
		}

		$nzbpath = 'compress.zlib://' . $nzbpath;
		if (!$nzbpath) {
			if ($this->echooutput) {
				echo
					PHP_EOL .
					"Unable to decompress: " .
					$nzbpath .
					' - ' .
					fileperms($nzbpath) .
					" - may have bad file permissions, skipping." .
					PHP_EOL;
			}
			return false;
		}

		$nzbfile = @simplexml_load_file($nzbpath);
		if (!$nzbfile) {
			if ($this->echooutput) {
				echo PHP_EOL ."Unable to load NZB: " . $guid . " appears to be an invalid NZB, skipping." . PHP_EOL;
			}
			return false;
		}
		return $nzbfile;
	}
}
