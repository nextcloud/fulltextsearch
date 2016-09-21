<?php

/**
 * Nextcloud - nextant
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
 * @copyright Maxence Lange 2016
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
namespace OCA\Nextant\Cron;

use \OCA\Nextant\AppInfo\Application;

class BackgroundIndex extends \OC\BackgroundJob\TimedJob
{

    private $configService;

    private $miscService;

    public function __construct()
    {
        $this->setInterval(60 * 60 * 24); // 1 minute
    }

    protected function run($argument)
    {
        return;
        $logger = \OC::$server->getLogger();
        
        $app = new Application();
        $c = $app->getContainer();
        
        $this->configService = $c->query('ConfigService');
        $this->miscService = $c->query('MiscService');
        
        $solr_locked = $this->configService->getAppValue('solr_lock');
        
        $this->miscService->log('@1 solr_locked: ' . $solr_locked);
        if ($solr_locked > 0)
            return;
        
        $this->configService->setAppValue('solr_lock', time());
        $this->extractDocuments();
        $this->configService->setAppValue('solr_lock', '0');
    }

    private function extractDocuments()
    {
        $this->miscService->log('@2 cache_execute');
    }
}
