<?php

use OCA\FullNextSearch\AppInfo\Application;
use OCP\Util;

Util::addScript(Application::APP_NAME, 'admin.elements');
Util::addScript(Application::APP_NAME, 'admin.settings');
Util::addScript(Application::APP_NAME, 'admin');

Util::addStyle(Application::APP_NAME, 'admin');

?>

<div class="section">
	<h2><?php p($l->t('Full Next Search')) ?></h2>

	<div class="div-table">

		<div class="div-table-row">
			<div class="div-table-col div-table-col-left">
				<span class="leftcol">Search Platform :</span>
				<br/>
				<em>Select the app that will be used to index content and answer search queries.</em>
			</div>
			<div class="div-table-col">
				<select id="fns_platforms" class="hidden">
					<option></option>
				</select>
			</div>
		</div>

		<div class="div-table-row">
			<div class="div-table-col div-table-col-left">
				<span class="leftcol">Indexing Chunk Size :</span>
				<br/>
				<em>Lower the value if the server is short on memory.</em>
			</div>
			<div class="div-table-col">
				<input type="text" class="hidden small" id="fns_chunk_size"/>
			</div>
		</div>

	</div>


</div>