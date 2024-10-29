<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Model;


use OCA\FullTextSearch\Tools\Traits\TArrayTools;
use JsonSerializable;
use OCA\FullTextSearch\Service\CollectionService;
use OCP\FullTextSearch\Model\IIndex;
use OCP\IURLGenerator;


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

	private string $collection = '';

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
	 * @param string $collection
	 */
	public function __construct(string $providerId, string $documentId, string $collection = '') {
		$this->providerId = $providerId;
		$this->documentId = $documentId;
		$this->collection = $collection;
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
	 * @param string $collection
	 *
	 * @return Index
	 */
	public function setCollection(string $collection): self {
		$this->collection = $collection;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCollection(): string {
		return $this->collection;
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
			'message' => substr($message, 0, 1800),
			'exception' => $exception,
			'severity' => $sev
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
	 * @param IURLGenerator $urlGenerator
	 *
	 * @return array{url: string, status: int}
	 */
	public function asSitemap(IURLGenerator $urlGenerator): array {
		return  [
			'url' => $urlGenerator->linkToOCSRouteAbsolute('fulltextsearch.Collection.indexDocument',
														   [
															   'collection' => $this->getCollection(),
															   'providerId' => $this->getProviderId(),
															   'documentId' => $this->getDocumentId()
														   ]
			),
			'status' => $this->getStatus()
		];
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'ownerId' => $this->getOwnerId(),
			'providerId' => $this->getProviderId(),
			'collection' => $this->getCollection(),
			'source' => $this->getSource(),
			'documentId' => $this->getDocumentId(),
			'lastIndex' => $this->getLastIndex(),
			'errors' => $this->getErrors(),
			'errorCount' => $this->getErrorCount(),
			'status' => (int)$this->getStatus(),
			'options' => $this->getOptions()
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
