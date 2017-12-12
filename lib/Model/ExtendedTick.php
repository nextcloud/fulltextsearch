<?php
/**
 * FullNextSearch - Full Text Search your Nextcloud.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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
 *
 */


namespace OCA\FullNextSearch\Model;


class ExtendedTick extends Tick {

	/**
	 * @param string $key
	 * @param string|int $value
	 *
	 * @return $this
	 */
	public function setInfo($key, $value) {
		$this->data[$key] = $value;

		return $this;
	}

	public function unsetInfo($key) {
		unset($this->data[$key]);
	}

	/**
	 * @param $key
	 * @param int|string $default
	 *
	 * @return int|string
	 */
	public function getInfo($key, $default = '') {
		if (!array_key_exists($key, $this->data)) {
			return $default;
		}

		return $this->data[$key];

	}

}