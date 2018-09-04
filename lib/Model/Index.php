<?php
/**
 * FullTextSearch - Full text search framework for extcloud
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

class Index implements \JsonSerializable {

	const INDEX_OK = 1;
	const INDEX_IGNORE = 2;

	const INDEX_META = 4;
	const INDEX_CONTENT = 8;
	const INDEX_FULL = 12;
	const INDEX_REMOVE = 16;

	const INDEX_DONE = 32;
	const INDEX_FAILED = 64;

	const ERROR_FAILED = 1;
	const ERROR_FAILED2 = 2;
	const ERROR_FAILED3 = 4;

	const ERROR_SEV_1 = 1;
	const ERROR_SEV_2 = 2;
	const ERROR_SEV_3 = 3;
	const ERROR_SEV_4 = 4;


	/** @var string */
	private $providerId;

	/** @var string */
	private $documentId;

	/** @var string */
	private $source = '';

	/** @var string */
	private $ownerId = '';

	/** @var int */
	private $status = 0;

	/** @var array */
	private $options = [];

	/** @var int */
	private $err = 0;

	/** @var array */
	private $errors = [];

	/** @var int */
	private $lastIndex = 0;


	/**
	 * Index constructor.
	 *
	 * @param string $providerId
	 * @param string $documentId
	 */
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
	 * @param string $source
	 *
	 * @return $this
	 */
	public function setSource($source) {
		$this->source = $source;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getSource() {
		return $this->source;
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
	 * @return bool
	 */
	public function isStatus($status) {
		return ((int)$status & $this->getStatus());
	}

	/**
	 * @param int $status
	 */
	public function unsetStatus($status) {
		if (!$this->isStatus($status)) {
			return;
		}

		$this->status -= $status;
	}


	/**
	 * @param string $option
	 * @param string|int $value
	 *
	 * @return $this
	 */
	public function addOption($option, $value) {
		$this->options[$option] = $value;

		return $this;
	}

	/**
	 * @param string $option
	 * @param int $value
	 *
	 * @return $this
	 */
	public function addOptionInt($option, $value) {
		$this->options[$option] = $value;

		return $this;
	}


	/**
	 * @param array $options
	 *
	 * @return $this
	 */
	public function setOptions($options) {
		$this->options = $options;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getOptions() {
		return $this->options;
	}


	/**
	 * @param string $option
	 * @param string $default
	 *
	 * @return string
	 */
	public function getOption($option, $default = '') {
		if (!array_key_exists($option, $this->options)) {
			return $default;
		}

		return $this->options[$option];
	}



	/**
	 * @param string $option
	 * @param int $default
	 *
	 * @return int
	 */
	public function getOptionInt($option, $default = 0) {
		if (!array_key_exists($option, $this->options)) {
			return $default;
		}

		return $this->options[$option];
	}

	/**
	 * @param int $err
	 *
	 * @return $this
	 */
	public function setErrorCount($err) {
		$this->err = $err;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getErrorCount() {
		return $this->err;
	}

	/**
	 * @return array
	 */
	public function getLastError() {
		return array_values(array_slice($this->errors, -1))[0];
	}


	/**
	 *
	 */
	public function resetErrors() {
		$this->setErrors([]);
		$this->setErrorCount(0);
	}

	/**
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @param array $messages
	 *
	 * @return Index
	 */
	public function setErrors($messages) {
		$this->errors = $messages;

		return $this;
	}


	/**
	 * @param string $message
	 * @param string $exception
	 * @param int $sev
	 */
	public function addError($message, $exception = '', $sev = self::ERROR_SEV_3) {
		$this->errors[] = [
			'message'  => substr($message, 0, 1800),
			'exception' => $exception,
			'severity' => $sev
		];

		$this->err++;
	}


	/**
	 * @param int $lastIndex
	 *
	 * @return $this
	 */
	public function setLastIndex($lastIndex = -1) {
		if ($lastIndex === -1) {
			$lastIndex = time();
		}

		$this->lastIndex = $lastIndex;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getLastIndex() {
		return $this->lastIndex;
	}


	/**
	 * @return array<string,string|integer>
	 */
	public function jsonSerialize() {
		return [
			'ownerId'    => $this->getOwnerId(),
			'providerId' => $this->getProviderId(),
			'source'     => $this->getSource(),
			'documentId' => $this->getDocumentId(),
			'lastIndex'  => $this->getLastIndex(),
			'errors'     => $this->getErrors(),
			'errorCount' => $this->getErrorCount(),
			'status'     => (int)$this->getStatus(),
			'options'    => $this->getOptions()
		];
	}


	public function __destruct() {
		unset($this->providerId);
		unset($this->documentId);
		unset($this->ownerId);
		unset($this->status);
		unset($this->options);
		unset($this->err);
		unset($this->message);
		unset($this->lastIndex);
	}

}