<?php
declare(strict_types=1);


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


use daita\MySmallPhpTools\Traits\TArrayTools;
use JsonSerializable;
use OCP\FullTextSearch\Model\IIndex;


/**
 * Class Index
 *
 * @package OCA\FullTextSearch\Model
 */
class Index implements IIndex, JsonSerializable {


	use TArrayTools;


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
	public function __construct(string $providerId, string $documentId) {
		$this->providerId = $providerId;
		$this->documentId = $documentId;
	}


	/**
	 * @return string
	 */
	public function getProviderId(): string {
		return $this->providerId;
	}

	/**
	 * @return string
	 */
	public function getDocumentId(): string {
		return $this->documentId;
	}


	/**
	 * @param string $source
	 *
	 * @return IIndex
	 */
	public function setSource(string $source): IIndex {
		$this->source = $source;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getSource(): string {
		return $this->source;
	}


	/**
	 * @param string $ownerId
	 *
	 * @return IIndex
	 */
	public function setOwnerId(string $ownerId): IIndex {
		$this->ownerId = $ownerId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getOwnerId(): string {
		return $this->ownerId;
	}


	/**
	 * @param int $status
	 * @param bool $reset
	 *
	 * @return IIndex
	 */
	public function setStatus(int $status, bool $reset = false): IIndex {
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
	public function getStatus(): int {
		return $this->status;
	}

	/**
	 * @param int $status
	 *
	 * @return bool
	 */
	public function isStatus(int $status): bool {
		return (bool)((int)$status & $this->getStatus());
	}

	/**
	 * @param int $status
	 *
	 * @return IIndex
	 */
	public function unsetStatus(int $status): IIndex {
		if (!$this->isStatus($status)) {
			return $this;
		}

		$this->status -= $status;

		return $this;
	}


	/**
	 * @param string $option
	 * @param string $value
	 *
	 * @return IIndex
	 */
	public function addOption(string $option, string $value): IIndex {
		$this->options[$option] = $value;

		return $this;
	}

	/**
	 * @param string $option
	 * @param int $value
	 *
	 * @return IIndex
	 */
	public function addOptionInt(string $option, int $value): IIndex {
		$this->options[$option] = $value;

		return $this;
	}


	/**
	 * @param array $options
	 *
	 * @return IIndex
	 */
	public function setOptions(array $options): IIndex {
		$this->options = $options;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getOptions(): array {
		return $this->options;
	}


	/**
	 * @param string $option
	 * @param string $default
	 *
	 * @return string
	 */
	public function getOption(string $option, string $default = ''): string {
		return $this->get($option, $this->options, $default);
	}


	/**
	 * @param string $option
	 * @param int $default
	 *
	 * @return int
	 */
	public function getOptionInt(string $option, int $default = 0): int {
		return $this->getInt($option, $this->options, $default);
	}


	/**
	 * @param int $err
	 *
	 * @return IIndex
	 */
	public function setErrorCount(int $err): IIndex {
		$this->err = $err;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getErrorCount(): int {
		return $this->err;
	}

	/**
	 * @return array
	 */
	public function getLastError(): array {
		return array_values(array_slice($this->errors, -1))[0];
	}

	/**
	 * @return IIndex
	 */
	public function resetErrors(): IIndex {
		$this->setErrors([]);
		$this->setErrorCount(0);

		return $this;
	}

	/**
	 * @return array
	 */
	public function getErrors(): array {
		return $this->errors;
	}

	/**
	 * @param array $messages
	 *
	 * @return IIndex
	 */
	public function setErrors(array $messages): IIndex {
		$this->errors = $messages;

		return $this;
	}


	/**
	 * @param string $message
	 * @param string $exception
	 * @param int $sev
	 *
	 * @return IIndex
	 */
	public function addError(string $message, string $exception = '', int $sev = IIndex::ERROR_SEV_3
	): IIndex {
		$this->errors[] = [
			'message'   => substr($message, 0, 1800),
			'exception' => $exception,
			'severity'  => $sev
		];

		$this->err++;

		return $this;
	}


	/**
	 * @param int $lastIndex
	 *
	 * @return IIndex
	 */
	public function setLastIndex(int $lastIndex = -1): IIndex {
		if ($lastIndex === -1) {
			$lastIndex = time();
		}

		$this->lastIndex = $lastIndex;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getLastIndex(): int {
		return $this->lastIndex;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
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


	/**
	 *
	 */
	public function __destruct() {
		unset($this->providerId);
		unset($this->documentId);
		unset($this->source);
		unset($this->ownerId);
		unset($this->status);
		unset($this->options);
		unset($this->err);
		unset($this->errors);
		unset($this->lastIndex);
	}

}
