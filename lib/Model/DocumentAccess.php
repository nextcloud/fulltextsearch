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


class DocumentAccess {

	/** @var string */
	private $ownerId;

	/** @var string */
	private $viewerId;

	/** @var array */
	private $users = [];

	/** @var array */
	private $groups = [];

	/** @var array */
	private $circles = [];

	/** @var array */
	private $links = [];

	/**
	 * DocumentAccess constructor.
	 *
	 * @param string $ownerId
	 */
	function __construct($ownerId = '') {
		$this->ownerId = $ownerId;
	}


	/**
	 * @return string
	 */
	public function getOwner() {
		return $this->ownerId;
	}


	/**
	 * @param string $viewerId
	 */
	public function setViewer($viewerId) {
		$this->viewerId = $viewerId;
	}

	/**
	 * @return string
	 */
	public function getViewer() {
		return $this->viewerId;
	}


	/**
	 * @param array $users
	 */
	public function setUsers($users) {
		$this->users = $users;
	}

	/**
	 * @return array
	 */
	public function getUsers() {
		return $this->users;
	}


	/**
	 * @param array $groups
	 */
	public function setGroups($groups) {
		$this->groups = $groups;
	}

	/**
	 * @return array
	 */
	public function getGroups() {
		return $this->groups;
	}


	/**
	 * @param array $circles
	 */
	public function setCircles($circles) {
		$this->circles = $circles;
	}

	/**
	 * @return array
	 */
	public function getCircles() {
		return $this->circles;
	}


	/**
	 * @param array $links
	 */
	public function setLinks($links) {
		$this->links = $links;
	}

	/**
	 * @return array
	 */
	public function getLinks() {
		return $this->links;
	}

}