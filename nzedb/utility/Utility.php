<?php
namespace nzedb\utility;

/*
 * General util functions.
 * Class Util
 */
class Utility
{
	/**
	 *  Regex for detecting multi-platform path. Use it where needed so it can be updated in one location as required characters get added.
	 */
	const PATH_REGEX = '(?P<drive>[A-Za-z]:|)(?<path>[/\w.-]+|)';

	static public function getDirFiles (array $options = null)
	{
		$defaults = array(
			'dir'	=> false,
			'ext'	=> '', // no full stop (period) separator should be used.
			'path'	=> '',
			'regex'	=> '',
		);
		$options += $defaults;

		$files = array();
		$dir = new \DirectoryIterator($options['path']);
		foreach ($dir as $fileinfo) {
			$file = $fileinfo->getFilename();
			switch (true) {
				case $fileinfo->isDot():
					break;
				case !$options['dir'] && $fileinfo->isDir():
					break;
				case !empty($options['ext']) && $fileinfo->getExtension() != $options['ext'];
					break;
				case !preg_match($options['regex'], str_replace('\\', '/', $file)):
					break;
				default:
					$files[] = $fileinfo->getPathname();
				}
		}
		return $files;
	}

	/**
	 * Detect if the command is accessible on the system.
	 * @param $cmd
	 * @return bool|null Returns true if found, false if not found, and null if which is not detected.
	 */
	static public function hasCommand($cmd)
	{
		if (HAS_WHICH) {
			$returnVal = shell_exec("which $cmd");
			return (empty($returnVal) ? false : true);
		} else {
			return null;
		}
	}

	/**
	 * Check for availability of which command
	 */
	static public function hasWhich()
	{
		exec('which which', $output, $error);
		return !$error;
	}

	static public function isWin()
	{
		return (strtolower(substr(PHP_OS, 0, 3)) === 'win');
	}

	static public function setCoversConstant($path)
	{
		if (!defined('nZEDb_COVERS')) {
			define('nZEDb_COVERS', $path == '' ? nZEDb_WWW . 'covers' . DS : $path);
		}
	}

    static public function stripBOM (&$text)
	{
		$bom = pack("CCC", 0xef, 0xbb, 0xbf);
		if (0 == strncmp($text, $bom, 3)) {
			$text = substr($text, 3);
		}
	}

	static public function trailingSlash(&$path)
	{
		if (substr($path, strlen($path) - 1) != '/') {
			$path .= '/';
		}
	}

	/**
	 * Check if user is running from CLI.
	 *
	 * @return bool
	 */
	static public function isCLI()
	{
		return ((strtolower(PHP_SAPI) === 'cli') ? true : false);
	}
}

/**
 * Get human readable size string from bytes.
 *
 * @param int $bytes     Bytes number to convert.
 * @param int $precision How many floating point units to add.
 *
 * @return string
 */
function bytesToSizeString ($bytes, $precision = 0)
{
	if ($bytes == 0) {
		return '0B';
	}
	$unit = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');
	return round($bytes / pow(1024, ($i = floor(log($bytes, 1024)))), $precision) . $unit[(int)$i];
}

function checkStatus ($code)
{
	return ($code == 0) ? true : false;
}

/**
 * Convert Code page 437 chars to UTF.
 *
 * @param string $str
 *
 * @return string
 */
function cp437toUTF ($str)
{
	$out = '';
	for ($i = 0; $i < strlen($str); $i++) {
		$ch = ord($str{$i});
		switch ($ch) {
			case 128:
				$out .= 'Ç';
				break;
			case 129:
				$out .= 'ü';
				break;
			case 130:
				$out .= 'é';
				break;
			case 131:
				$out .= 'â';
				break;
			case 132:
				$out .= 'ä';
				break;
			case 133:
				$out .= 'à';
				break;
			case 134:
				$out .= 'å';
				break;
			case 135:
				$out .= 'ç';
				break;
			case 136:
				$out .= 'ê';
				break;
			case 137:
				$out .= 'ë';
				break;
			case 138:
				$out .= 'è';
				break;
			case 139:
				$out .= 'ï';
				break;
			case 140:
				$out .= 'î';
				break;
			case 141:
				$out .= 'ì';
				break;
			case 142:
				$out .= 'Ä';
				break;
			case 143:
				$out .= 'Å';
				break;
			case 144:
				$out .= 'É';
				break;
			case 145:
				$out .= 'æ';
				break;
			case 146:
				$out .= 'Æ';
				break;
			case 147:
				$out .= 'ô';
				break;
			case 148:
				$out .= 'ö';
				break;
			case 149:
				$out .= 'ò';
				break;
			case 150:
				$out .= 'û';
				break;
			case 151:
				$out .= 'ù';
				break;
			case 152:
				$out .= 'ÿ';
				break;
			case 153:
				$out .= 'Ö';
				break;
			case 154:
				$out .= 'Ü';
				break;
			case 155:
				$out .= '¢';
				break;
			case 156:
				$out .= '£';
				break;
			case 157:
				$out .= '¥';
				break;
			case 158:
				$out .= '₧';
				break;
			case 159:
				$out .= 'ƒ';
				break;
			case 160:
				$out .= 'á';
				break;
			case 161:
				$out .= 'í';
				break;
			case 162:
				$out .= 'ó';
				break;
			case 163:
				$out .= 'ú';
				break;
			case 164:
				$out .= 'ñ';
				break;
			case 165:
				$out .= 'Ñ';
				break;
			case 166:
				$out .= 'ª';
				break;
			case 167:
				$out .= 'º';
				break;
			case 168:
				$out .= '¿';
				break;
			case 169:
				$out .= '⌐';
				break;
			case 170:
				$out .= '¬';
				break;
			case 171:
				$out .= '½';
				break;
			case 172:
				$out .= '¼';
				break;
			case 173:
				$out .= '¡';
				break;
			case 174:
				$out .= '«';
				break;
			case 175:
				$out .= '»';
				break;
			case 176:
				$out .= '░';
				break;
			case 177:
				$out .= '▒';
				break;
			case 178:
				$out .= '▓';
				break;
			case 179:
				$out .= '│';
				break;
			case 180:
				$out .= '┤';
				break;
			case 181:
				$out .= '╡';
				break;
			case 182:
				$out .= '╢';
				break;
			case 183:
				$out .= '╖';
				break;
			case 184:
				$out .= '╕';
				break;
			case 185:
				$out .= '╣';
				break;
			case 186:
				$out .= '║';
				break;
			case 187:
				$out .= '╗';
				break;
			case 188:
				$out .= '╝';
				break;
			case 189:
				$out .= '╜';
				break;
			case 190:
				$out .= '╛';
				break;
			case 191:
				$out .= '┐';
				break;
			case 192:
				$out .= '└';
				break;
			case 193:
				$out .= '┴';
				break;
			case 194:
				$out .= '┬';
				break;
			case 195:
				$out .= '├';
				break;
			case 196:
				$out .= '─';
				break;
			case 197:
				$out .= '┼';
				break;
			case 198:
				$out .= '╞';
				break;
			case 199:
				$out .= '╟';
				break;
			case 200:
				$out .= '╚';
				break;
			case 201:
				$out .= '╔';
				break;
			case 202:
				$out .= '╩';
				break;
			case 203:
				$out .= '╦';
				break;
			case 204:
				$out .= '╠';
				break;
			case 205:
				$out .= '═';
				break;
			case 206:
				$out .= '╬';
				break;
			case 207:
				$out .= '╧';
				break;
			case 208:
				$out .= '╨';
				break;
			case 209:
				$out .= '╤';
				break;
			case 210:
				$out .= '╥';
				break;
			case 211:
				$out .= '╙';
				break;
			case 212:
				$out .= '╘';
				break;
			case 213:
				$out .= '╒';
				break;
			case 214:
				$out .= '╓';
				break;
			case 215:
				$out .= '╫';
				break;
			case 216:
				$out .= '╪';
				break;
			case 217:
				$out .= '┘';
				break;
			case 218:
				$out .= '┌';
				break;
			case 219:
				$out .= '█';
				break;
			case 220:
				$out .= '▄';
				break;
			case 221:
				$out .= '▌';
				break;
			case 222:
				$out .= '▐';
				break;
			case 223:
				$out .= '▀';
				break;
			case 224:
				$out .= 'α';
				break;
			case 225:
				$out .= 'ß';
				break;
			case 226:
				$out .= 'Γ';
				break;
			case 227:
				$out .= 'π';
				break;
			case 228:
				$out .= 'Σ';
				break;
			case 229:
				$out .= 'σ';
				break;
			case 230:
				$out .= 'µ';
				break;
			case 231:
				$out .= 'τ';
				break;
			case 232:
				$out .= 'Φ';
				break;
			case 233:
				$out .= 'Θ';
				break;
			case 234:
				$out .= 'Ω';
				break;
			case 235:
				$out .= 'δ';
				break;
			case 236:
				$out .= '∞';
				break;
			case 237:
				$out .= 'φ';
				break;
			case 238:
				$out .= 'ε';
				break;
			case 239:
				$out .= '∩';
				break;
			case 240:
				$out .= '≡';
				break;
			case 241:
				$out .= '±';
				break;
			case 242:
				$out .= '≥';
				break;
			case 243:
				$out .= '≤';
				break;
			case 244:
				$out .= '⌠';
				break;
			case 245:
				$out .= '⌡';
				break;
			case 246:
				$out .= '÷';
				break;
			case 247:
				$out .= '≈';
				break;
			case 248:
				$out .= '°';
				break;
			case 249:
				$out .= '∙';
				break;
			case 250:
				$out .= '·';
				break;
			case 251:
				$out .= '√';
				break;
			case 252:
				$out .= 'ⁿ';
				break;
			case 253:
				$out .= '²';
				break;
			case 254:
				$out .= '■';
				break;
			case 255:
				$out .= ' ';
				break;
			default :
				$out .= chr($ch);
		}
	}
	return $out;
}

/**
 * Use cURL To download a web page into a string.
 *
 * @param string $url       The URL to download.
 * @param string $method    get/post
 * @param string $postdata  If using POST, post your POST data here.
 * @param string $language  Use alternate langauge in header.
 * @param bool   $debug     Show debug info.
 * @param string $userAgent User agent.
 * @param string $cookie    Cookie.
 *
 * @return bool|mixed
 */
function getUrl ($url, $method = 'get', $postdata = '', $language = "", $debug = false,
				 $userAgent = '', $cookie = '')
{
	switch ($language) {
		case 'fr':
		case 'fr-fr':
			$language = "fr-fr";
			break;
		case 'de':
		case 'de-de':
			$language = "de-de";
			break;
		case 'en':
			$language = 'en';
			break;
		case '':
		case 'en-us':
		default:
			$language = "en-us";
	}
	$header[] = "Accept-Language: " . $language;

	$ch      = curl_init();
	$options = array(
		CURLOPT_URL            => $url,
		CURLOPT_HTTPHEADER     => $header,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_FOLLOWLOCATION => 1,
		CURLOPT_TIMEOUT        => 15,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_SSL_VERIFYHOST => false,
	);
	curl_setopt_array($ch, $options);

	if ($userAgent !== '') {
		curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
	}

	if ($cookie !== '') {
		curl_setopt($ch, CURLOPT_COOKIE, $cookie);
	}

	if ($method === 'post') {
		$options = array(
			CURLOPT_POST       => 1,
			CURLOPT_POSTFIELDS => $postdata
		);
		curl_setopt_array($ch, $options);
	}

	if ($debug) {
		$options =
			array(
				CURLOPT_HEADER      => true,
				CURLINFO_HEADER_OUT => true,
				CURLOPT_NOPROGRESS  => false,
				CURLOPT_VERBOSE     => true
			);
		curl_setopt_array($ch, $options);
	}

	$buffer = curl_exec($ch);
	$err    = curl_errno($ch);
	curl_close($ch);

	if ($err !== 0) {
		return false;
	} else {
		return $buffer;
	}
}

/**
 * Fetches an embeddable video to a IMDB trailer from http://www.traileraddict.com
 *
 * @param $id
 *
 * @return string
 */
function imdb_trailers ($id)
{
	$xml = getUrl('http://api.traileraddict.com/?imdb=' . $id);
	if ($xml !== false) {
		if (preg_match('/(<iframe.+?<\/iframe>)/i', $xml, $html)) {
			return $html[1];
		}
	}
	return '';
}

// Check if O/S is windows.
function isWindows ()
{
	return Utility::isWin();
}

// Convert obj to array.
function objectsIntoArray ($arrObjData, $arrSkipIndices = array())
{
	$arrData = array();

	// If input is object, convert into array.
	if (is_object($arrObjData)) {
		$arrObjData = get_object_vars($arrObjData);
	}

	if (is_array($arrObjData)) {
		foreach ($arrObjData as $index => $value) {
			// Recursive call.
			if (is_object($value) || is_array($value)) {
				$value = objectsIntoArray($value, $arrSkipIndices);
			}
			if (in_array($index, $arrSkipIndices)) {
				continue;
			}
			$arrData[$index] = $value;
		}
	}
	return $arrData;
}

/**
 * Run CLI command.
 *
 * @param string $command
 * @param bool   $debug
 *
 * @return array
 */
function runCmd ($command, $debug = false)
{
	$nl = PHP_EOL;
	if (isWindows() && strpos(phpversion(), "5.2") !== false) {
		$command = "\"" . $command . "\"";
	}

	if ($debug) {
		echo '-Running Command: ' . $nl . '   ' . $command . $nl;
	}

	$output = array();
	$status = 1;
	@exec($command, $output, $status);

	if ($debug) {
		echo '-Command Output: ' . $nl . '   ' . implode($nl . '  ', $output) . $nl;
	}

	return $output;
}

/**
 * Remove unsafe chars from a filename.
 *
 * @param string $filename
 *
 * @return string
 */
function safeFilename ($filename)
{
	return trim(preg_replace('/[^\w\s.-]*/i', '', $filename));
}

// Central function for sending site email.
function sendEmail($to, $subject, $contents, $from)
{
	if (isWindows()) {
		$n = "\r\n";
	} else {
		$n = "\n";
	}
	$body = '<html>' . $n;
	$body .= '<body style=\'font-family:Verdana, Verdana, Geneva, sans-serif; font-size:12px; color:#666666;\'>' . $n;
	$body .= $contents;
	$body .= '</body>' . $n;
	$body .= '</html>' . $n;

	$headers = 'From: ' . $from . $n;
	$headers .= 'Reply-To: ' . $from . $n;
	$headers .= 'Return-Path: ' . $from . $n;
	$headers .= 'X-Mailer: nZEDb' . $n;
	$headers .= 'MIME-Version: 1.0' . $n;
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . $n;
	$headers .= $n;

	return mail($to, $subject, $body, $headers);
}
