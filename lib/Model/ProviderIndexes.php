<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Model;


use OCA\FullTextSearch\Exceptions\IndexDoesNotExistException;
use OCP\FullTextSearch\Model\IIndex;


/**
 * Class ProviderIndexes
 *
 * @package OCA\FullTextSearch\Model
 */
class ProviderIndexes {


	/** @var IIndex[] */
	private $indexes;


	public function __construct(array $indexes) {
		$this->indexes = $indexes;
	}


	/**
	 * @return IIndex[]
	 */
	public function getIndexes(): array {
		return $this->indexes;
	}


	/**
	 * @param string $documentId
	 *
	 * @return IIndex
	 * @throws IndexDoesNotExistException
	 */
	public function getIndex(string $documentId): IIndex {
		if (!array_key_exists($documentId, $this->indexes)) {
			throw new IndexDoesNotExistException();
		}

		return $this->indexes[$documentId];
	}


}
