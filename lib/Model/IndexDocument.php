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

class IndexDocument implements \JsonSerializable {

	/** @var string|int */
	private $id;

	/** @var string */
	private $providerId;

	/** @var DocumentAccess */
	private $access;

	/** @var int */
	private $modifiedTime = 0;

	/** @var string */
	private $title;

	/** @var string */
	private $content;

	/** @var string */
	private $link = '';

	/** @var array */
	private $excerpts = [];

	/** @var string */
	private $score;

	/** @var array */
	private $info;


	public function __construct($providerId, $id) {
		$this->providerId = $providerId;
		$this->id = $id;
	}


	/**
	 * @param string $id
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
	public function getId() {
		return $this->id;
	}


	/**
	 * @param string $providerId
	 *
	 * @return $this
	 */
	public function setProviderId($providerId) {
		$this->providerId = $providerId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getProviderId() {
		return $this->providerId;
	}


	/**
	 * @param int $modifiedTime
	 *
	 * @return $this
	 */
	public function setModifiedTime($modifiedTime) {
		$this->modifiedTime = $modifiedTime;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getModifiedTime() {
		return $this->modifiedTime;
	}

	/**
	 * @param int $time
	 *
	 * @return bool
	 */
	public function isOlderThan($time) {
		return ($this->modifiedTime < $time);
	}


	/**
	 * @param DocumentAccess $access
	 *
	 * @return $this
	 */
	public function setAccess(DocumentAccess $access) {
		$this->access = $access;

		return $this;
	}

	/**
	 * @return DocumentAccess
	 */
	public function getAccess() {
		return $this->access;
	}


	/**
	 * @param string $title
	 *
	 * @return $this
	 */
	public function setTitle($title) {
		$this->title = $title;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}


	/**
	 * @param string $content
	 *
	 * @return $this
	 */
	public function setContent($content) {
		$this->content = $content;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getContent() {
		return $this->content;
	}


	/**
	 * @param string $link
	 *
	 * @return $this
	 */
	public function setLink($link) {
		$this->link = $link;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getLink() {
		return $this->link;
	}


	/**
	 * @param array $excerpts
	 *
	 * @return $this
	 */
	public function setExcerpts($excerpts) {
		$this->excerpts = $excerpts;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getExcerpts() {
		return $this->excerpts;
	}

	/**
	 * @param string $excerpt
	 */
	public function addExcerpt($excerpt) {
		$this->excerpts[] = $excerpt;
	}


	/**
	 * @param string $score
	 *
	 * @return $this
	 */
	public function setScore($score) {
		$this->score = $score;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getScore() {
		return $this->score;
	}


	/**
	 * @param string $info
	 * @param mixed $value
	 *
	 * @return $this
	 */
	public function setInfo($info, $value) {
		$this->info[$info] = $value;

		return $this;
	}


	/**
	 * @param string $info
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function getInfo($info, $default = '') {
		if (!key_exists($info, $this->info)) {
			return $default;
		}

		return $this->info[$info];
	}


	/**
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			'id'       => $this->getId(),
			'title'    => $this->getTitle(),
			'link'     => $this->getLink(),
			'excerpts' => $this->getExcerpts(),
			'score'    => $this->getScore()
		];
	}

}