<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use OCA\FullTextSearch\AppInfo\Application;
use OCP\Util;

Util::addScript(Application::APP_ID, 'navigate');
Util::addStyle(Application::APP_ID, 'navigate');

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
