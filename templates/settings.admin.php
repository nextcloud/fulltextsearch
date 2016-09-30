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
style('nextant', 'admin');

?>
<div class="section" id="nextant">
	<h2><?php p($l->t('Nextant (Full-Text Search)')) ?></h2>

	<table>
		<tr>
			<td colspan="2"><em><?php p($l->t('Specify the URL of your Solr servlet, and the name of the core to use.')); ?>
			<br />If you do not have a Solr servlet running, you can <a
					href="https://github.com/daita/nextant/wiki" target="_blank">find
						few guides on how to install one on the Wiki</a></em></td>
		</tr>
		<tr>
			<td>
				<table>
					<tr>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<td style="text-align: right;"><label>
	    <?php p($l->t('Address of your Solr Servlet :')) ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label></td>
						<td><input type="text" name="solr_url" id="solr_url"
							value="<?php p($_['solr_url'])?>" style="width: 250px;"></td>
					</tr>

					<tr>
						<td style="text-align: right;"><label><?php p($l->t('Core :')) ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label></td>
						<td><input type="text" id="solr_core"
							value="<?php p($_['solr_core'])?>" style="width: 250px;"></td>
					</tr>

					<tr>
						<td></td>
						<td>
							<button type="button" id="nextant_apply" style="width: 270px"><?php p($l->t('Test and Save')) ?></button>
						</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
					</tr>
					<!--  					<tr style="height: 40px;">
						<td colspan="2" style="text-align: center; width: 300px;"><span
							id="nextant-admin-msg" class="msg"></span></td>
					</tr>
							-->
		<?php

if ($_['configured'] == '1') {
    ?>
		
					<tr style="height: 30px;">
						<td style="text-align: right;"><label>
	    <?php p($l->t('Live Extract :')) ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label></td>
						<td><input type="checkbox" name="solr_live_extract"
							id="solr_live_extract" value="1"
							<?php if ($_['live_extract'] == '1') { p('CHECKED'); } ?>
							style="margin: 10px;"> (<a
							href="https://github.com/daita/nextant/wiki/Extracting-&-Live-Update"
							target="_blank">help</a>)</td>
					</tr>

					<tr style="height: 30px;">
						<td style="text-align: right;"><label>
	    <?php p($l->t('Live Document Update :')) ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label></td>
						<td><input type="checkbox" name="solr_live_docupdate"
							id="solr_live_docupdate" value="1"
							<?php if ($_['live_docupdate'] == '1') { p('CHECKED'); } ?>
							style="margin: 10px;"></td>
					</tr>
					<tr style="height: 20px;">
						<td style="width: 250px; text-align: right;"><label>
	    <?php p($l->t('Last index:')) ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label></td>
						<td><?php
    
    if ($_['last_index'] > 0) {
        p(date('r', $_['last_index']));
        ?></td>

					</tr>
					<tr style="height: 20px;">
						<td style="width: 250px; text-align: right;"><label>
	    <?php p($l->t('Number of documents :')) ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label></td>
						<td><?php ($_['current_docs'] === false) ? p('Solr Servlet is down') : p($_['current_docs']); ?></td>
					</tr>

					<tr>
						<td></td>

						<td><?php
        if ($_['needed_index'] == 0) {
            ?>
			          <button type="button" id="nextant_force_index"
								style="width: 270px"><?php p($l->t('Force re-index')) ?></button>
			          <?php
        } else 
            if ($_['needed_index'] == 2) {
                ?>            <b>execute <i>./occ nextant:index</i></b> <?php
            } else
                p('index scheduled');
    } else
        p('never');
    ?></td>
					</tr>
						<?php } ?>
					
				</table>
			</td>
			<td style="padding-left: 40px;">
				<div id="nextant-display">
					<div id="ping" class="nextant-display-line">
						<img id="icon_check" class="nextant_display_icon"
							src="<?php print_unescaped(OCP\image_path('nextant', 'check.png')); ?>" />
						<img id="icon_fail" class="nextant_display_icon"
							src="<?php print_unescaped(OCP\image_path('nextant', 'fail.png')); ?>" />
						<div id="text" class="nextant_display_text"></div>
					</div>

					<div id="schema" class="nextant-display-line" style="top: 30px;">
						<img id="icon_check" class="nextant_display_icon"
							src="<?php print_unescaped(OCP\image_path('nextant', 'check.png')); ?>" />
						<img id="icon_fail" class="nextant_display_icon"
							src="<?php print_unescaped(OCP\image_path('nextant', 'fail.png')); ?>" />
						<div id="text" class="nextant_display_text"></div>
					</div>

					<div id="extract" class="nextant-display-line" style="top: 60px;">
						<img id="icon_check" class="nextant_display_icon"
							src="<?php print_unescaped(OCP\image_path('nextant', 'check.png')); ?>" />
						<img id="icon_fail" class="nextant_display_icon"
							src="<?php print_unescaped(OCP\image_path('nextant', 'fail.png')); ?>" />
						<div id="text" class="nextant_display_text"></div>
					</div>

					<div id="update" class="nextant-display-line" style="top: 90px;">
						<img id="icon_check" class="nextant_display_icon"
							src="<?php print_unescaped(OCP\image_path('nextant', 'check.png')); ?>" />
						<img id="icon_fail" class="nextant_display_icon"
							src="<?php print_unescaped(OCP\image_path('nextant', 'fail.png')); ?>" />
						<div id="text" class="nextant_display_text"></div>
					</div>

					<div id="search" class="nextant-display-line" style="top: 120px;">
						<img id="icon_check" class="nextant_display_icon"
							src="<?php print_unescaped(OCP\image_path('nextant', 'check.png')); ?>" />
						<img id="icon_fail" class="nextant_display_icon"
							src="<?php print_unescaped(OCP\image_path('nextant', 'fail.png')); ?>" />
						<div id="text" class="nextant_display_text"></div>
					</div>

					<div id="delete" class="nextant-display-line" style="top: 150px;">
						<img id="icon_check" class="nextant_display_icon"
							src="<?php print_unescaped(OCP\image_path('nextant', 'check.png')); ?>" />
						<img id="icon_fail" class="nextant_display_icon"
							src="<?php print_unescaped(OCP\image_path('nextant', 'fail.png')); ?>" />
						<div id="text" class="nextant_display_text"></div>
					</div>

					<div id="save" class="nextant-display-line" style="top: 190px;">
						<img id="icon_check" class="nextant_display_icon"
							src="<?php print_unescaped(OCP\image_path('nextant', 'check.png')); ?>" />
						<img id="icon_fail" class="nextant_display_icon"
							src="<?php print_unescaped(OCP\image_path('nextant', 'fail.png')); ?>" />
						<div id="text" class="nextant_display_text"></div>
					</div>

				</div>
			</td>
		</tr>

	</table>
</div>


