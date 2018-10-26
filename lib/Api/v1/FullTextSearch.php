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


use OCA\FullTextSearch\Service\ConfigService;
use OCA\FullTextSearch\AppInfo\Application;
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
	 * @return array
	 * @throws QueryException
	 */
	public static function version() {
		$c = self::getContainer();

		return [
			[
				'fulltextsearch' => $c->query(ConfigService::class)
									  ->getAppValue('installed_version')
			],
			['api' => self::API_VERSION]
		];
	}


	/**
	 *
	 */
	public static function addJavascriptAPI() {
		Util::addStyle(Application::APP_NAME, 'fulltextsearch');
		Util::addScript(Application::APP_NAME, 'fulltextsearch.v1.api');
		Util::addScript(Application::APP_NAME, 'fulltextsearch.v1.settings');
		Util::addScript(Application::APP_NAME, 'fulltextsearch.v1.searchbox');
		Util::addScript(Application::APP_NAME, 'fulltextsearch.v1.result');
		Util::addScript(Application::APP_NAME, 'fulltextsearch.v1.navigation');
		Util::addScript(Application::APP_NAME, 'fulltextsearch.v1');
	}


	/**
	 * @param string $providerId
	 * @param string|int $documentId
	 *
	 * @return Index
	 * @throws QueryException
	 */
	public static function getIndex($providerId, $documentId) {
		$c = self::getContainer();

		return $c->query(IndexService::class)
				 ->getIndex($providerId, $documentId);
	}


	/**
	 * @param string $providerId
	 * @param string|int $documentId
	 * @param string $ownerId
	 * @param int $status
	 *
	 * @return mixed
	 * @throws QueryException
	 */
	public static function createIndex($providerId, $documentId, $ownerId, $status = 0) {
		$index = new Index($providerId, $documentId);
		$index->setOwnerId($ownerId);
		$index->setStatus($status);

		return self::updateIndexes([$index]);
	}


	/**
	 * @param string $providerId
	 * @param string|int|array $documentIds
	 * @param int $status
	 * @param bool $reset
	 *
	 * @return mixed
	 * @throws QueryException
	 */
	public static function updateIndexStatus($providerId, $documentIds, $status, $reset = false) {
		$c = self::getContainer();

		if (!is_array($documentIds)) {
			$documentIds = [$documentIds];
		}

		return $c->query(IndexService::class)
				 ->updateIndexesStatus($providerId, $documentIds, $status, $reset);
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
