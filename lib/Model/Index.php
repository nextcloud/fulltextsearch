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

class Index implements \JsonSerializable {

	const STATUS_INDEX_THIS = 1;
	const STATUS_INDEX_DONE = 2;
	const STATUS_REMOVE_DOCUMENT = 4;
	const STATUS_DOCUMENT_REMOVED = 8;

	const ERROR_FAILED = 1;
	const ERROR_FAILED2 = 2;
	const ERROR_FAILED3 = 4;


	/** @var string */
	private $providerId;

	/** @var string */
	private $documentId;

	/** @var string */
	private $ownerId = '';

	/** @var int */
	private $status = 0;

	/** @var int */
	private $err = 0;

	/** @var string */
	private $lastIndex = '0';


	public function __construct($providerId, $documentId) {
		$this->providerId = $providerId;
		$this->documentId = $documentId;
	}


	/**
	 * @return string
	 */
	public function getProviderId() {
		return $this->providerId;
	}

	/**
	 * @return string
	 */
	public function getDocumentId() {
		return $this->documentId;
	}


	/**
	 * @param string $ownerId
	 *
	 * @return $this
	 */
	public function setOwnerId($ownerId) {
		$this->ownerId = $ownerId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getOwnerId() {
		return $this->ownerId;
	}


	/**
	 * @param int $status
	 * @param bool $reset
	 *
	 * @return $this
	 */
	public function setStatus($status, $reset = false) {
		if ($reset === true) {
			$this->status = $status;
		} else if (!$this->isStatus($status)) {
			$this->status += $status;
		}

		return $this;
	}

	/**
	 * @return int
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @param int $status
	 *
	 * @return int
	 */
	public function isStatus($status) {
		return ((int)$status & $this->getStatus());
	}


	/**
	 * @param string $err
	 *
	 * @return $this
	 */
	public function setError($err) {
		$this->err = $err;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getError() {
		return $this->err;
	}


	/**
	 * @param string $lastIndex
	 *
	 * @return $this
	 */
	public function setLastIndex($lastIndex = '') {
		if ($lastIndex === '') {
			$lastIndex = time();
		}

		$this->lastIndex = $lastIndex;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getLastIndex() {
		return $this->lastIndex;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			'ownerId'    => $this->getOwnerId(),
			'providerId' => $this->getProviderId(),
			'documentId' => $this->getDocumentId(),
			'lastIndex'  => $this->getLastIndex(),
			'status'     => (int)$this->getStatus()
		];
	}

}