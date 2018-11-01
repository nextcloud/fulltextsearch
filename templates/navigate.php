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

Util::addScript(Application::APP_NAME, 'navigate');
Util::addStyle(Application::APP_NAME, 'navigate');

?>

<div id="app-navigation">
	<ul id="search_navigation">
		<li style="height: 50px">
			<div id="search_header">
				<div>
					<input id="search_input"
						   placeholder="<?php p(
							   $l->t('Search on %s', [$_['themingName']])
						   ); ?>">
				</div>
			</div>
		</li>
	</ul>
</div>

<div id="app-content">
	<div id="search_error"></div>
	<div id="search_result"></div>
</div>

<!-- <div id="search_json"></div> -->


<script id="template_entry" type="text/template">
	<div class="result_entry_default">
		<div class="result_entry_left">
			<div id="title">&nbsp;</div>
			<div id="line1">&nbsp;</div>
			<div id="line2">&nbsp;</div>
		</div>
		<div class="result_entry_right">
			<div id="source">&nbsp;</div>
			<div id="score">&nbsp;</div>
		</div>
	</div>

</script>
