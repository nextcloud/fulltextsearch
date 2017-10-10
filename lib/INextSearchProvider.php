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

namespace OCA\FullNextSearch;

use OCA\FullNextSearch\Model\SearchDocument;
use OCA\FullNextSearch\Model\SearchResult;

interface INextSearchProvider {


	/**
	 * return a unique Id of the Provider
	 *
	 * @return string
	 */
	public function getId();


	/**
	 * return a display name of the Provider
	 *
	 * @return string
	 */
	public function getName();


	/**
	 * Called when loading the provider
	 */
	public function loadProvider();


	/**
	 * initialize a new index
	 *
	 * @param INextSearchPlatform $platform
	 * @param string $userId
	 *
	 * @return
	 */
	public function initializeIndex(INextSearchPlatform $platform, $userId);


	/**
	 * Index is over
	 */
	public function finalizeIndex();


	/**
	 * not used yet.
	 */
	public function unloadProvider();


	/**
	 * generate documents prior to the indexing.
	 * $chunksize is a maximum chunk size, please use it if possible.
	 *
	 * throw NoResultException if no more result
	 *
	 * @param int $chunkSize
	 *
	 * @return SearchDocument[]
	 */
	public function generateDocuments($chunkSize);


	/**
	 * after a search, improve the result
	 *
	 * @param SearchResult $searchResult
	 *
	 * @return mixed
	 */
	public function improveSearchResult(SearchResult $searchResult);

}