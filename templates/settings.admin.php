<?php

use OCA\FullNextSearch\AppInfo\Application;
use OCP\Util;

Util::addScript(Application::APP_NAME, 'admin');
Util::addStyle(Application::APP_NAME, 'admin');

?>

<div class="section">
	<h2><?php p($l->t('Full Next Search')) ?></h2>

	<table cellpadding="10" cellpadding="5">
		<tr class="lane">
			<td colspan="2" class="left">Allow something:<br/>
				<em>This is an example of a checkbox.</em></td>
			<td class="right">
				<input type="checkbox" value="1" id="test_fullnextsearch"/>
			</td>
		</tr>
	</table>
</div>