<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use OCA\FullTextSearch\AppInfo\Application;
use OCP\Util;


Util::addScript(Application::APP_ID, 'admin.elements');
Util::addScript(Application::APP_ID, 'admin.settings');
Util::addScript(Application::APP_ID, 'admin');

Util::addStyle(Application::APP_ID, 'admin');

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
