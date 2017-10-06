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

namespace OCA\FullNextSearch\AppInfo;

use OCA\FullNextSearch\Service\ConfigService;
use OCP\AppFramework\App;

class Application extends App {

	const APP_NAME = 'fullnextsearch';

	/**
	 * @param array $params
	 */
	public function __construct(array $params = array()) {
		parent::__construct(self::APP_NAME, $params);

		$this->registerHooks();
	}


	/**
	 * Register Hooks
	 */
	public function registerHooks() {
	}


	/**
	 * Register Navigation Tab
	 */
	public function registerNavigation() {

		/** @var ConfigService $configService */
		$configService = \OC::$server->query(ConfigService::class);
		if ($configService->getAppValue(ConfigService::APP_NAVIGATION) !== '1') {
			return;
		}

		$this->getContainer()
			 ->getServer()
			 ->getNavigationManager()
			 ->add($this->fullNextSearchNavigation());
	}


	public function fullNextSearchNavigation() {
		$urlGen = \OC::$server->getURLGenerator();
		$navName = \OC::$server->getL10N(self::APP_NAME)
							   ->t('Full Next Search');

		return [
			'id'    => self::APP_NAME,
			'order' => 5,
			'href'  => $urlGen->linkToRoute('fullnextsearch.Navigation.navigate'),
			'icon'  => $urlGen->imagePath(self::APP_NAME, 'ruler.svg'),
			'name'  => $navName
		];
	}


	public function registerSettingsAdmin() {
		\OCP\App::registerAdmin(self::APP_NAME, 'lib/admin');
	}

	public function registerSettingsPersonal() {
		\OCP\App::registerPersonal(self::APP_NAME, 'lib/personal');
	}


}

