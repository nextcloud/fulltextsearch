<?php
/**
 * FullTextSearch - Full text search framework for Nextcloud
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2018
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\FullTextSearch\Model;


class IndexOptions implements \JsonSerializable {

	/**
	 * @var array
	 */
	private $options = [];


	public function __construct($options = []) {
		$this->options = $options;
	}

	/**
	 * @return array
	 */
	public function getOptions() {
		return $this->options;
	}

	/**
	 * @param array $options
	 */
	public function setOptions($options) {
		$this->options = $options;
	}

	/**
	 * @param string $k
	 * @param string $v
	 */
	public function addOption($k, $v) {
		$this->options[$k] = $v;
	}

	/**
	 * @param string $k
	 * @param array $array
	 */
	public function addOptionArray($k, $array) {
		$this->options[$k] = $array;
	}

	/**
	 * @param string $k
	 * @param bool $bool
	 */
	public function addOptionBool($k, $bool) {
		$this->options[$k] = $bool;
	}


	/**
	 * @param string $k
	 * @param string $default
	 *
	 * @return string
	 */
	public function getOption($k, $default = '') {
		if (array_key_exists($k, $this->options)) {
			return $this->options[$k];
		}

		return $default;
	}


	/**
	 * @param string $option
	 * @param array $default
	 *
	 * @return array
	 */
	public function getOptionArray($option, $default = []) {
		if (array_key_exists($option, $this->options)) {
			$options = $this->options[$option];
			if (is_array($options)) {
				return $this->options[$option];
			}
		}

		return $default;
	}


	/**
	 * @param string $option
	 * @param bool $default
	 *
	 * @return bool
	 */
	public function getOptionBool($option, $default) {
		if (array_key_exists($option, $this->options)) {
			$options = $this->options[$option];
			if (is_bool($options)) {
				return $this->options[$option];
			}
		}

		return $default;
	}


	/**
	 * Specify data which should be serialized to JSON
	 *
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 * @since 5.4.0
	 */
	public function jsonSerialize() {
		return $this->options;
	}
}