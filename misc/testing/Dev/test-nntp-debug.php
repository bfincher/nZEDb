<?php
/**
 * This script will turn on debug in NNTP showing you everything you send and receive.
 * You need to set nZEDb_DEBUG to true in automated.config.php.
 */

// Get the config.php
require_once dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIBS . 'Yenc.php';
// Create instance of class at bottom of this script.
$d = new NNTPdebug();
// Create instance of NNTP.
$nntp = new NNTP();
// Set our above debugger class as the nntp logger.
$nntp->setLogger($d);
// Connect to usenet.
$connected = $nntp->doConnect();
// Check if connected.
if ($connected !== true) {
	exit();
}

////////////////////////////////////////////////////////////////////////////////
//////////////////// Put your test code under here. ////////////////////////////
////////////////////////////////////////////////////////////////////////////////

/** Example: **/

$x = $nntp->selectGroup('alt.binaries.zines');
$x = $nntp->get_Header($x['group'], $x['last']);
var_dump($x['From'], $x['Subject'], $x['Date']);


/**/

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

/**
 * Class NNTPdebug
 */
class NNTPdebug
{
	/**
	 * Construct.
	 */
	public function __construct()
	{
		if (defined('nZEDb_DEBUG')) {
			//define('PEAR_LOG_DEBUG', nZEDb_DEBUG);
			define('PEAR_LOG_DEBUG', true);
			$this->color = new ColorCLI();
		} else {
			//define('PEAR_LOG_DEBUG', false);
			define('PEAR_LOG_DEBUG', true);
		}
	}

	/**
	 * @param bool|null $value
	 *
	 * @return bool
	 */
	public function _isMasked($value)
	{
		if (is_bool($value)) {
			return $value;
		} else {
			return false;
		}
	}

	/**
	 * @param string $message
	 *
	 * @return void
	 */
	public function notice($message)
	{
		if (PEAR_LOG_DEBUG) {
			echo $this->color->notice($message);
		}
	}

	/**
	 * @param string $message
	 *
	 * @return void
	 */
	public function debug($message)
	{
		if (PEAR_LOG_DEBUG) {
			echo $this->color->debug($message);
		}
	}

	/**
	 * @param string $message
	 *
	 * @return void
	 */
	public function warning($message)
	{
		if (PEAR_LOG_DEBUG) {
			echo $this->color->warning($message);
		}
	}

	/**
	 * @param string $message
	 *
	 * @return void
	 */
	public function info($message)
	{
		if (PEAR_LOG_DEBUG) {
			echo $this->color->info($message);
		}
	}
}
