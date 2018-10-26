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


class Tick {

	/** @var int */
	private $id;

	/** @var string */
	private $source;

	/** @var array */
	protected $data;

	/** @var int */
	private $tick;

	/** @var int */
	private $firstTick;

	/** @var string */
	private $status;

	/** @var string */
	private $action;


	public function __construct($source, $id = 0) {
		$this->source = $source;
		$this->id = $id;
	}


	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param int $id
	 *
	 * @return $this
	 */
	public function setId($id) {
		$this->id = $id;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getSource() {
		return $this->source;
	}


	/**
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * @param array $data
	 *
	 * @return $this
	 */
	public function setData($data) {
		$this->data = $data;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getTick() {
		return $this->tick;
	}

	/**
	 * @param int $tick
	 *
	 * @return $this
	 */
	public function setTick($tick = 0) {
		if ($tick === 0) {
			$tick = time();
		}

		$this->tick = $tick;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getFirstTick() {
		return $this->firstTick;
	}

	/**
	 * @param int $tick
	 *
	 * @return $this
	 */
	public function setFirstTick($tick = 0) {
		if ($tick === 0) {
			$tick = time();
		}

		$this->firstTick = $tick;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @param string $status
	 *
	 * @return $this
	 */
	public function setStatus($status) {
		$this->status = $status;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getAction() {
		return $this->action;
	}

	/**
	 * @param string $action
	 *
	 * @return $this
	 */
	public function setAction($action) {
		$this->action = $action;

		return $this;
	}


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
