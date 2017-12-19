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

use OCA\FullNextSearch\Model\Index;
use OCA\FullNextSearch\Model\IndexDocument;
use OCA\FullNextSearch\Model\Runner;
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
	 * @param Runner $runner
	 */
	public function setRunner(Runner $runner);


	/**
	 * returns all indexable document for a user.
	 * There is no need to fill the document with content at this point.
	 *
	 * @param string $userId
	 *
	 * @return IndexDocument[]
	 */
	public function generateIndexableDocuments($userId);


	/**
	 * fill a chunk of documents with more content, prior to index.
	 *
	 * @param IndexDocument[] $chunk
	 *
	 * @return IndexDocument[]
	 */
	public function fillIndexDocuments($chunk);


	/**
	 * update a document regarding the current Index' status
	 *
	 * @param Index $index
	 *
	 * @return IndexDocument
	 */
	public function updateDocument(Index $index);


	/**
	 * @param INextSearchPlatform $platform
	 */
	public function onInitializingIndex(INextSearchPlatform $platform);


	/**
	 * @param INextSearchPlatform $platform
	 * @param array $arr
	 */
	public function onIndexingDocument(INextSearchPlatform $platform, &$arr);


	/**
	 * @param INextSearchPlatform $platform
	 */
	public function onRemovingIndex(INextSearchPlatform $platform);


	/**
	 * @param INextSearchPlatform $platform
	 * @param array $arr
	 *
	 * @return array
	 */
	public function onSearchingQuery(INextSearchPlatform $platform, &$arr);


	/**
	 * after a search, improve the result
	 *
	 * @param SearchResult $searchResult
	 *
	 * @return mixed
	 */
	public function improveSearchResult(SearchResult $searchResult);


	/**
	 * not used yet.
	 */
	public function unloadProvider();

}