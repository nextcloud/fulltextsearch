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
$app = new \OCA\Nextant\AppInfo\Application();
$c = $app->getContainer();

$application->add(new OCA\Nextant\Command\Check($c->query('SolrService'), $c->query('SolrToolsService'), $c->query('SolrAdminService')));
$application->add(new OCA\Nextant\Command\Clear($c->query('ConfigService'), $c->query('SolrService'), $c->query('SolrAdminService')));
$application->add(new OCA\Nextant\Command\Index(OC::$server->getUserManager(), $c->query('RootFolder'), $c->query('IndexService'), $c->query('QueueService'), $c->query('SolrService'), $c->query('SolrAdminService'),  $c->query('ConfigService'), $c->query('FileService'), $c->query('BookmarkService'), $c->query('MiscService')));
$application->add(new OCA\Nextant\Command\Optimize($c->query('SolrService'), $c->query('SolrToolsService')));
$application->add(new OCA\Nextant\Command\Live($c->query('QueueService'), $c->query('IndexService'), $c->query('SolrService'), $c->query('SolrToolsService'), $c->query('ConfigService'), $c->query('FileService'), $c->query('BookmarkService'), $c->query('MiscService')));


