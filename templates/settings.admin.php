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
		<tr valign="top">
			<td>
				<table>
					<tr>
						<td>&nbsp;</td>
					</tr>
					<tr class="nextant_admin_head">
						<td></td>
						<td class="nextant_admin_head">Settings</td>
					</tr>
					<tr style="height: 30px;">
						<td class="nextant_admin_left">
	    <?php p($l->t('Nextant Version :')) ?></td>
						<td><div id="nextant_version"></div></td>
					</tr>
					<tr style="height: 30px;">
						<td class="nextant_admin_left">
	    <?php p($l->t('Address of your Solr Servlet :')) ?></td>
						<td><input type="text" name="solr_url" id="solr_url" value=""
							style="width: 250px;"></td>
					</tr>

					<tr style="height: 30px;">
						<td class="nextant_admin_left"><?php p($l->t('Core :')) ?></td>
						<td><input type="text" id="solr_core" value=""
							style="width: 250px;"></td>
					</tr>

					<tr style="height: 30px;">
						<td></td>
						<td>
							<button type="button" id="nextant_apply" style="width: 270px"><?php p($l->t('Test and Save')) ?></button>
						</td>
					</tr>
				</table>
				<table id="nextant_suboptions">
					<tr>
						<td>&nbsp;</td>
					</tr>
					<tr class="nextant_admin_head">
						<td></td>
						<td class="nextant_admin_head">Indexing Files</td>
					</tr>
					<tr style="height: 30px;">
						<td class="nextant_admin_left">
	    <?php p($l->t('Maximum File Size (Mb) :')) ?></td>
						<td><input type="text" id="solr_index_files_max_size"
							style="width: 100px;"></td>
					</tr>

					<tr style="height: 30px;">
						<td class="nextant_admin_left">					
	    <?php p($l->t('Live Extract :')); ?>
						</td>
						<td><input type="checkbox" name="solr_index_files_live_extract"
							id="solr_index_files_live_extract" value="1"
							style="margin: 10px;"> (<a id="nextant_help_link"
							href="https://github.com/daita/nextant/wiki/Extracting,-Live-Update"
							target="_blank">help</a>)</td>
					</tr>

					<tr style="height: 30px;">
						<td class="nextant_admin_left">
	    <?php p($l->t('Live Document Update :')) ?></td>
						<td><input type="checkbox" name="solr_index_files_live_update"
							id="solr_index_files_live_update" value="1" style="margin: 10px;"></td>
					</tr>
					<tr style="height: 30px;">
						<td class="nextant_admin_left">
	    <?php p($l->t('Last index :')) ?></td>
						<td><div id="solr_index_files_last"></div></td>

					</tr>


					<!-- 
                    <tr style="height: 30px;">
						<td class="nextant_admin_left">
	    <?php p($l->t('Index External Storage :')) ?></td>
						<td><input type="checkbox" name="solr_index_files_external_index"
							id="solr_index_files_external_index" value="1" style="margin: 10px;"></td>
					</tr>
                    -->

					<!-- 

					<tr>
						<td>&nbsp;</td>
					</tr>
					<tr class="nextant_admin_head">
						<td></td>
						<td class="nextant_admin_head">Indexing Bookmarks</td>
					</tr>


					<tr style="height: 30px;">
						<td class="nextant_admin_left">
	    <?php p($l->t('Index bookmarks :')) ?></td>
						<td>
							<div id="nextant_bookmarks_appdisabled">
								The App <i>Bookmarks</i> is not installed/enabled.
							</div>
							<div id="nextant_bookmarks_appenabled">
								<input type="checkbox" name="solr_index_bookmarks"
									id="solr_index_bookmarks" value="1" style="margin: 10px;">								
							</div>
						</td>
					</tr>
                    -->


					<tr>
						<td>&nbsp;</td>
					</tr>
					<tr class="nextant_admin_head">
						<td><div id="nextant_saving">Saving</div></td>
						<td class="nextant_admin_head">Nextant Status</td>
					</tr>

					<tr style="height: 30px;">
						<td class="nextant_admin_left">
	    <?php p($l->t('Display result :')) ?></td>
						<td><select id="solr_display_result" style="width: 260px;">
								<option value="1">Nextant style</option>
								<option value="2">Integrated to Files</option>
						</select></td>
					</tr>

					<tr style="height: 30px;">
						<td class="nextant_admin_left">
	    <?php p($l->t('Number of documents :')) ?></td>
						<td><div id="solr_current_docs"></div></td>
					</tr>

					<tr>
						<td>&nbsp;</td>
					</tr>
					<tr style="height: 30px;">

						<td></td>
						<td>
							<div id="nextant_first_index"
								style="width: 270px; font-size: 12px; white-space: normal;">
								<i></i> we recommand doing the very first indexing of your files
								using the <b>./occ nextant:index</b> command.<br /> However, you
								can force it to be started as a background process: </i> <br />
								<button type="button" id="nextant_force_first_index"
									style="width: 270px"><?php p($l->t('Force first index as a cronjob')) ?></button>
							</div>
							<button type="button" id="nextant_force_index"
								style="width: 270px"><?php p($l->t('Force index')) ?></button>
							<div id="nextant_index_scheduled">
								<i>Indexing is scheduled within the next few hours (cron)</i>
							</div>
							<div id="nextant_index_inprogress">
								<i>Indexing in progress</i>
							</div>
						</td>
					</tr>

				</table>
			</td>
			<td
				style="padding-left: 30px; padding-top: 40px; vertical-align: top">
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


