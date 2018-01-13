<?php
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

namespace OCA\FullTextSearch\Api\v1;


use OCA\FullTextSearch\AppInfo\Application;
use OCA\FullTextSearch\Model\ExtendedIndex;
use OCA\FullTextSearch\Model\Index;
use OCA\FullTextSearch\Service\IndexService;
use OCA\FullTextSearch\Service\ProviderService;
use OCA\FullTextSearch\Service\SearchService;
use OCP\AppFramework\QueryException;
use OCP\Util;

class FullTextSearch {

	const API_VERSION = [0, 1, 0];

	protected static function getContainer() {
		$app = new Application();

		return $app->getContainer();
	}


	/**
	 * returns app name
	 *
	 * @return string
	 */
	public static function appName() {
		return Application::APP_NAME;
	}


	/**
	 * FullTextSearch::version();
	 *
	 * returns the current version of the API
	 *
	 * @return int[]
	 */
	public static function version() {
		return self::API_VERSION;
	}


	/**
	 *
	 */
	public static function addJavascriptAPI() {
		Util::addStyle(Application::APP_NAME, 'fulltextsearch');
		Util::addScript(Application::APP_NAME, 'fulltextsearch.v1.api');
		Util::addScript(Application::APP_NAME, 'fulltextsearch.v1.settings');
		Util::addScript(Application::APP_NAME, 'fulltextsearch.v1.searchbar');
		Util::addScript(Application::APP_NAME, 'fulltextsearch.v1.result');
		Util::addScript(Application::APP_NAME, 'fulltextsearch.v1.navigation');
		Util::addScript(Application::APP_NAME, 'fulltextsearch.v1');
	}


	/**
	 * @param string $providerId
	 * @param string|int $documentId
	 *
	 * @return ExtendedIndex
	 * @throws QueryException
	 */
	public static function getIndex($providerId, $documentId) {
		$c = self::getContainer();

		return $c->query(IndexService::class)
				 ->getIndex($providerId, $documentId);
	}


	/**
	 * @param $providerId
	 * @param $documentId
	 * @param string $ownerId
	 *
	 * @return mixed
	 * @throws QueryException
	 */
	public static function createIndex($providerId, $documentId, $ownerId = '') {
		$index = new Index($providerId, $documentId);
		$index->setOwnerId($ownerId);

		return self::updateIndexes([$index]);
	}


	/**
	 * @param string $providerId
	 * @param string|int $documentId
	 * @param int $status
	 * @param bool $reset
	 *
	 * @return mixed
	 * @throws QueryException
	 */
	public static function updateIndexStatus($providerId, $documentId, $status, $reset = false) {
		$c = self::getContainer();

		return $c->query(IndexService::class)
				 ->updateIndexStatus($providerId, $documentId, $status, $reset);
	}


	/**
	 * @param Index[] $indexes
	 *
	 * @return mixed
	 * @throws QueryException
	 */
	public static function updateIndexes($indexes) {
		$c = self::getContainer();

		return $c->query(IndexService::class)
				 ->updateIndexes($indexes);
	}


	/**
	 * @param string $providerId
	 * @param string|int $search
	 *
	 * @return mixed
	 * @throws QueryException
	 */
	public static function search($providerId, $search) {
		$c = self::getContainer();

		return $c->query(SearchService::class)
				 ->search($providerId, null, $search);
	}


	/**
	 * @param $providerId
	 *
	 * @return mixed
	 * @throws QueryException
	 */
	public static function isProviderIndexed($providerId) {
		$c = self::getContainer();

		return $c->query(ProviderService::class)
				 ->isProviderIndexed($providerId);

	}
}