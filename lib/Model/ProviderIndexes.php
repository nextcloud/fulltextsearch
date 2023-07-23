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
