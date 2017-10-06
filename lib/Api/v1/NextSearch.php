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

namespace OCA\FullNextSearch\Api\v1;


use OCA\FullNextSearch\AppInfo\Application;
use OCA\FullNextSearch\Service\SearchService;
use OCP\Util;

class NextSearch {

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
	 * FullNextSearch::version();
	 *
	 * returns the current version of the API
	 *
	 * @return int[]
	 */
	public static function version() {
		return self::API_VERSION;
	}


	public static function addJavascriptAPI() {
		Util::addStyle(Application::APP_NAME, 'notyf');
		Util::addScript(Application::APP_NAME, 'vendor/notyf');
		Util::addStyle(Application::APP_NAME, 'nextsearch');
		Util::addScript(Application::APP_NAME, 'nextsearch.v1.api');
		Util::addScript(Application::APP_NAME, 'nextsearch.v1.settings');
		Util::addScript(Application::APP_NAME, 'nextsearch.v1.navigation');
		Util::addScript(Application::APP_NAME, 'nextsearch.v1');
	}


	public static function search($providerId, $search) {
		$c = self::getContainer();

		return $c->query(SearchService::class)
				 ->search($providerId, null, $search);
	}

}