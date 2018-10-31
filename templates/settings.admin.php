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


use OCA\FullTextSearch\AppInfo\Application;
use OCP\Util;


Util::addScript(Application::APP_NAME, 'admin.elements');
Util::addScript(Application::APP_NAME, 'admin.settings');
Util::addScript(Application::APP_NAME, 'admin');

Util::addStyle(Application::APP_NAME, 'admin');

?>


<div id="fns" class="section">
	<span>
		<a href="https://github.com/nextcloud/fulltextsearch/wiki" target="_blank">
			<?php p(
				$l->t(
					'Please check the wiki for documentation related to the installation and the configuration of the full text search within your Nextcloud'
				)
			); ?></a>

	</span>
	&nbsp;<br/>
	&nbsp;<br/>
	<h2><?php p($l->t('General')) ?></h2>
	<div class="div-table">

		<div class="div-table-row">
			<div class="div-table-col div-table-col-left">
				<span class="leftcol"><?php p($l->t('Search Platform')); ?>:</span>
				<br/>
				<em><?php p(
						$l->t('Select the app to index content and answer search queries.')
					); ?></em>
			</div>
			<div class="div-table-col">
				<select id="fts_platforms">
					<option></option>
				</select>
			</div>
		</div>

		<div class="div-table-row">
			<div class="div-table-col div-table-col-left">
				<span class="leftcol"><?php p($l->t('Navigation Icon')); ?>:</span>
				<br/>
				<em><?php p($l->t('Enable global search within all your content.')); ?></em>
			</div>
			<div class="div-table-col">
				<input type="checkbox" id="fts_navigation" value="1"/>
			</div>
		</div>

	</div>


</div>
