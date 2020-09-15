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


namespace OCA\FullTextSearch\Controller;


use OCA\FullTextSearch\AppInfo\Application;
use OCA\FullTextSearch\Service\ConfigService;
use OCA\FullTextSearch\Service\MiscService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\FullTextSearch\IFullTextSearchManager;
use OCP\IConfig;
use OCP\IRequest;


/**
 * Class NavigationController
 *
 * @package OCA\FullTextSearch\Controller
 */
class NavigationController extends Controller {


	/** @var IConfig */
	private $config;

	/** @var IFullTextSearchManager */
	private $fullTextSearchManager;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;


	/**
	 * NavigationController constructor.
	 *
	 * @param IRequest $request
	 * @param IConfig $config
	 * @param IFullTextSearchManager $fullTextSearchManager
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IRequest $request, IConfig $config, IFullTextSearchManager $fullTextSearchManager,
		ConfigService $configService, MiscService $miscService
	) {
		parent::__construct(Application::APP_NAME, $request);
		$this->config = $config;
		$this->fullTextSearchManager = $fullTextSearchManager;
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @return TemplateResponse
	 */
	public function navigate(): TemplateResponse {
		$themingName = $this->config->getAppValue('theming', 'name', 'Nextcloud');
		$data = ['themingName' => $themingName];

		$this->fullTextSearchManager->addJavascriptAPI();

		return new TemplateResponse(Application::APP_NAME, 'navigate', $data);
	}

}

