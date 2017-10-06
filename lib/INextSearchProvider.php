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
	 * must returns a unique Id
	 *
	 * @return string
	 */
	public function getId();


	/**
	 * must returns a display name
	 *
	 * @return string
	 */
	public function getName();


	/**
	 * Load the search provider
	 */
	public function load();


	/**
	 * Called on switch to new user
	 *
	 * @param string $userId
	 *
	 * @return
	 */
	public function initUser($userId);


	/**
	 * Called when user is not needed anymore.
	 */
	public function endUser();


	/**
	 * Called at the end of the use of the provider
	 */
	public function unload();

	/**
	 * generate documents prior to the indexing
	 *
	 * @param int $chunkSize
	 *
	 * @return SearchDocument[]
	 */
	public function generateDocuments($chunkSize);


	/**
	 * @param SearchResult $searchResult
	 *
	 * @return mixed
	 */
	public function parseSearchResult(SearchResult $searchResult);

}