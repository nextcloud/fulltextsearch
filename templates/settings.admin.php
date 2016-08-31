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
script('nextant', 'settings.admin');
?>
<div class="section" id="nextant">
	<h2><?php p($l->t('Nextant (Solr)')) ?></h2>
	<label for="wopi_url"><?php p($l->t('Apache Solr server :')) ?></label>
	<input type="text" name="solr_url" id="solr_url"
		value="<?php p($_['solr_url'])?>" style="width: 300px;"> <br /> <em><?php p($l->t('URL:port/path of the Apache Solr server.')) ?></em>
	<br />
	<button type="button" id="nextant_apply"><?php p($l->t('Apply')) ?></button>
	<span id="nextant-admin-msg" class="msg"></span>
</div>


