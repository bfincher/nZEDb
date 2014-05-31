<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program (see LICENSE.txt in the base directory.  If
 * not, see:
 *
 * @link      <http://www.gnu.org/licenses/>.
 * @author    niel
 * @copyright 2014 nZEDb
 */
namespace nzedb\db;

if (!defined('nZEDb_INSTALLER')) {
	require_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR .
				 'config.php';
}

use nzedb\utility\Utility;

class Settings extends DB
{
	private $settings;

	public function __construct(array $options = array())
	{
		parent::__construct($options);
		$result         = parent::exec("describe site", true);
		$this->settings = ($result === false) ? 'settings' : 'site';
		$this->setCovers();

		return self::$pdo;
	}

	/**
	 * Retrieve one or all settings from the Db as a string or an array;
	 *
	 * @param array|string $options Name of setting to retrieve (null for all settings)
	 *                              or array of 'feature', 'section', 'name' of setting{s} to retrieve
	 *
	 * @return string|array|bool
	 */
	public function getSetting($options = array())
	{
		if (!is_array($options)) {
			$options = ['name' => $options];
		}
		$defaults = array(
			'section'    => '',
			'subsection' => '',
			'name'       => null,
		);
		$options += $defaults;

		if ($this->settings == 'settings') {
			$result = $this->_getFromSettings($options);
		} else {
			$result = $this->_getFromSites($options);
		}
		return $result;
	}

	public function setCovers()
	{
		$path = $this->getSetting([
				'section' 		=> 'site',
				'subsection'	=> 'main',
				'name' 			=> 'coverspath',
				'setting' 		=> 'coverspath',
			]);
		Utility::setCoversConstant($path);
	}

	/**
	 * Set a setting in the database.
	 *
	 * @TODO not completed yet, do not use
	 *
	 * @param array $options Array containing the mandatory keys of 'section', 'subsection', and 'value'
	 */
	public function setSetting(array $options)
	{
		$defaults = [
			'section'    => '',
			'subsection' => '',
			'value'      => '',
			'setting'    => '',
		];
		$options += $defaults;
		$temp1 = $options['section'] . $options['subsection'] . $options['value'];
		$temp2 = $options['section'] . $options['subsection'] . $options['setting'];
		if (empty($temp1) && empty($temp2)) {
			return false;
		}

		extract($options);
	}

	public function settings()
	{
		return $this->settings;
	}

	protected function _getFromSettings($options)
	{
		$result = array();
		$sql     = 'SELECT value FROM settings ';
		$where   = $options['section'] . $options['subsection'] . $options['name']; // Can't use expression in empty() < PHP 5.5
		if (!empty($where)) {
			$sql .= "WHERE section = '{$options['section']}' AND subsection = '{$options['subsection']}'";
			$sql .= empty($options['name']) ? '' : " AND name = '{$options['name']}'";
		} else {
			$sql .= "WHERE setting = '{$options['setting']}'";
		}
		$sql .= ' ORDER BY section, subsection, name';
		$result = $this->queryOneRow($sql);

		return isset($result['value']) ? $result['value'] : null;
	}

	protected function _getFromSites($options)
	{
		$setting = empty($options['setting']) ? $options['name'] : $options['setting'];
		$sql     = 'SELECT value FROM site ';
		if (!empty($setting)) {
			$sql .= "WHERE setting = '$setting'";
		}

		$result = $this->queryOneRow($sql);

		return $result['value'];
	}
}

/*
 * Putting procedural stuff inside class scripts like this is BAD. Do not use this as an excuse to do more.
 * This is a temporary measure until a proper frontend for cli stuff can be implemented with li3.
 */
if (Utility::isCLI()) {
	if (isset($argc) && $argc > 1) {
		$settings = new Settings();
		echo $settings->getSetting($argv[1]);
	}
}

?>
