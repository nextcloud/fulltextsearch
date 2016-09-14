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

	<table>
		<tr>
			<td colspan="2"><em><?php p($l->t('Specify the URL of your Solr servlet, and the name of the core to use')) ?></em></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<?php

if ($_['installed'] == '1') {
    ?>
		<tr>
			<td style="width: 250px; text-align: right;"><label>
	    <?php p($l->t('Number of documents :')) ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label></td>
			<td><?php ($_['current_docs'] == false) ? p('Solr Servlet is down') : p($_['current_docs']); ?></td>
		</tr>
<?php } ?>
		<tr>
			<td style="text-align: right;"><label for="solr_url">
	    <?php p($l->t('Address of your Solr Servlet :')) ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label></td>
			<td><input type="text" name="solr_url" id="solr_url"
				value="<?php p($_['solr_url'])?>" style="width: 360px;"></td>
		</tr>
		<tr>
			<td style="text-align: right;"><label for="solr_core"><?php p($l->t('Core :')) ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label></td>
			<td><input type="text" name="solr_core" id="solr_core"
				value="<?php p($_['solr_core'])?>" style="width: 360px;"></td>
		</tr>

		<tr>
			<td></td>
			<td>
				<button type="button" id="nextant_apply" style="width: 370px"><?php p($l->t('Test and Save')) ?></button>
			</td>
		</tr>
		<tr style="height: 40px;">
			<td colspan="2" style="text-align: center; width: 550px;"><span
				id="nextant-admin-msg" class="msg"></span></td>
		</tr>
	</table>

</div>


