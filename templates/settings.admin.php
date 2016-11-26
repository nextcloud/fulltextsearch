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
					href="https://github.com/nextcloud/nextant/wiki" target="_blank">find
						few guides on how to install one on the Wiki</a></em></td>
		</tr>
		<tr valign="top">
			<td>
				<table style="width: 550px;">
					<tr>
						<td>&nbsp;</td>
					</tr>
					<tr class="nextant_admin_head">
						<td><div id="nextant_saving_settings">Saving</div></td>
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
						<td class="nextant_admin_left"><?php p($l->t('Timeout (in seconds) :')) ?></td>
						<td><input type="text" id="solr_timeout" value=""
							style="width: 100px;"></td>
					</tr>

					<tr style="height: 30px;">
						<td></td>
						<td>
							<button type="button" id="nextant_apply" style="width: 264px"><?php p($l->t('Test and Save')) ?></button>
						</td>
					</tr>
				</table>
				<div id="nextant_suboptions">
					<table style="width: 550px;">
						<tr>
							<td>&nbsp;</td>
						</tr>
						<tr class="nextant_admin_head" id="solr_index_files_cont">
							<td><div id="nextant_saving_files">Saving</div></td>
							<td colspan="2" class="nextant_admin_head"><input type="checkbox"
								name="solr_index_files" id="solr_index_files" value="1"
								style="margin: 5px; margin-right: 15px;">Indexing Files</td>
						</tr>
					</table>
					<div id="solr_sub_files">
						<table style="width: 550px;">

							<tr style="height: 30px;">
								<td class="nextant_admin_left">
	    <?php p($l->t('Maximum File Size (Mb) :')) ?></td>
								<td><input type="text" id="solr_index_files_max_size"
									style="width: 100px;"></td>
								<td style="width: 60px; padding-right: 10px;">[<span
									style="font-size: 12px;" id="solr_switch_filters">Edit your
										filters</span>]
								</td>
							</tr>

							<tr style="height: 30px;">
								<td class="nextant_admin_left">
	    <?php p($l->t('Index Files Tree :')) ?></td>
								<td colspan="2"><input type="checkbox"
									name="solr_index_files_tree" id="solr_index_files_tree"
									value="1" style="margin: 10px;"> <span
									id="solr_index_files_nextant_only_span"><input type="checkbox"
										name="solr_index_files_nextant_only"
										id="solr_index_files_nextant_only" value="1"
										style="margin: 10px;"> Disable default search from Files </span>
								</td>
							</tr>

							<tr style="height: 30px;">
								<td class="nextant_admin_left">
	    <?php p($l->t('Index Public Links :')) ?></td>
								<td colspan="2"><input type="checkbox"
									name="solr_index_files_sharelink"
									id="solr_index_files_sharelink" value="1" style="margin: 10px;"></td>
							</tr>

							<tr style="height: 30px;">
								<td class="nextant_admin_left">
	    <?php p($l->t('Index Federated Shares :')) ?></td>
								<td colspan="2"><input type="checkbox"
									name="solr_index_files_federated"
									id="solr_index_files_federated" value="1" style="margin: 10px;"></td>
							</tr>

							<tr style="height: 30px;">
								<td class="nextant_admin_left">
	    <?php p($l->t('Index External Storage :')) ?></td>
								<td colspan="2"><input type="checkbox"
									name="solr_index_files_external" id="solr_index_files_external"
									value="1" style="margin: 10px;"></td>
							</tr>

							<tr style="height: 30px;">
								<td class="nextant_admin_left">
	    <?php p($l->t('Index Encrypted Files :')) ?></td>
								<td colspan="2"><input type="checkbox"
									name="solr_index_files_encrypted"
									id="solr_index_files_encrypted" value="1" style="margin: 10px;"></td>
							</tr>

							<tr style="height: 30px;">
								<td class="nextant_admin_left">
	    <?php p($l->t('Last index :')) ?></td>
								<td colspan="2"><div id="solr_index_files_last"></div></td>
							</tr>

						</table>
					</div>

					<table style="width: 550px;">
						<tr>
							<td>&nbsp;</td>
						</tr>
						<tr class="nextant_admin_head" id="solr_index_bookmarks_cont">
							<td><div id="nextant_saving_bookmarks">Saving</div></td>
							<td class="nextant_admin_head"><input type="checkbox"
								name="solr_index_bookmarks" id="solr_index_bookmarks" value="1"
								style="margin: 5px; margin-right: 15px;">Indexing Bookmarks</td>
						</tr>
					</table>

					<div id="solr_sub_bookmarks">
						<table style="width: 550px;">
							<tr style="height: 30px;">
								<td colspan="2">
									<div id="nextant_bookmarks_appdisabled">
										The App <i>Bookmarks</i> is not installed/enabled.
									</div>
									<div id="nextant_bookmarks_appenabled">
										The App <i>Bookmarks</i> is enabled, your bookmarks will be
										indexed.
									</div>
								</td>
							</tr>
							<tr style="height: 30px;">
								<td class="nextant_admin_left">
	    <?php p($l->t('Last index :')) ?></td>
								<td><div id="solr_index_bookmarks_last"></div></td>

							</tr>

						</table>
					</div>

					<table style="width: 550px;">

						<tr>
							<td>&nbsp;</td>
						</tr>
						<tr class="nextant_admin_head">
							<td><div id="nextant_saving_status">Saving</div>
								<div id="nextant_saving_check">Checking</div></td>
							<td class="nextant_admin_head">Nextant Status</td>
						</tr>

						<tr style="height: 30px;">
							<td class="nextant_admin_left">					
	    <?php p($l->t('Resource Level :')); ?>
						</td>
							<td colspan="2"><select name="solr_resource_level"
								id="solr_resource_level">
									<!-- <option value="5">Higher Level</option> -->
									<option value="4">High performance</option>
									<!-- <option value="3">Mid level</option> -->
									<option value="2">Low performance</option>
									<option value="1">Lower performance</option>
							</select></td>
						</tr>

						<tr style="height: 30px;">
							<td class="nextant_admin_left">					
	    <?php p($l->t('Live Index :')); ?>
						</td>
							<td colspan="2"><input type="checkbox" name="solr_index_live"
								id="solr_index_live" value="1" style="margin: 10px;"> <input
								type="text" id="solr_index_live_queuekey"
								style="width: 100px; font-size: 12px;" /></td>
						</tr>

						<tr style="height: 30px;">
							<td class="nextant_admin_left">
	    <?php p($l->t('Background Index delay :')) ?></td>
							<td><input type="text" name="solr_index_delay"
								id="solr_index_delay" value="2" style="width: 50px;" /> hours</td>
						</tr>

						<tr style="height: 30px;">
							<td class="nextant_admin_left">
	    <?php p($l->t('Number of documents :')) ?></td>
							<td><div id="solr_current_docs"></div></td>
						</tr>
						<tr style="height: 30px;">
							<td class="nextant_admin_left">
	    <?php p($l->t('Number of segments :')) ?></td>
							<td><div id="solr_current_segments"></div></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
						</tr>
						<tr style="height: 30px;">

							<td></td>
							<td>
								<div id="nextant_first_index"
									style="width: 270px; font-size: 12px; white-space: normal;">
									<i></i> we recommand doing the very first indexing of your
									files using the <b>./occ nextant:index</b> command.<br />
									However, you can force it to be started as a background
									process: </i> <br />
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
				</div>
			</td>
			<td
				style="padding-left: 30px; padding-top: 40px; vertical-align: top">
				<div id="nextant-display" class="nextant-box">
					<div id="ping" class="nextant-display-line">
						<img id="icon_check" class="nextant_display_icon"
							src="<?php print_unescaped(OCP\image_path('nextant', 'check.png')); ?>" />
						<img id="icon_fail" class="nextant_display_icon"
							src="<?php print_unescaped(OCP\image_path('nextant', 'fail.png')); ?>" />
						<div id="text" class="nextant_display_text"></div>
					</div>

					<div id="schema" class="nextant-display-line" style="top: 40px;">
						<img id="icon_check" class="nextant_display_icon"
							src="<?php print_unescaped(OCP\image_path('nextant', 'check.png')); ?>" />
						<img id="icon_fail" class="nextant_display_icon"
							src="<?php print_unescaped(OCP\image_path('nextant', 'fail.png')); ?>" />
						<div id="text" class="nextant_display_text"></div>
					</div>

					<div id="extract" class="nextant-display-line" style="top: 70px;">
						<img id="icon_check" class="nextant_display_icon"
							src="<?php print_unescaped(OCP\image_path('nextant', 'check.png')); ?>" />
						<img id="icon_fail" class="nextant_display_icon"
							src="<?php print_unescaped(OCP\image_path('nextant', 'fail.png')); ?>" />
						<div id="text" class="nextant_display_text"></div>
					</div>

					<div id="update" class="nextant-display-line" style="top: 100px;">
						<img id="icon_check" class="nextant_display_icon"
							src="<?php print_unescaped(OCP\image_path('nextant', 'check.png')); ?>" />
						<img id="icon_fail" class="nextant_display_icon"
							src="<?php print_unescaped(OCP\image_path('nextant', 'fail.png')); ?>" />
						<div id="text" class="nextant_display_text"></div>
					</div>

					<div id="search" class="nextant-display-line" style="top: 130px;">
						<img id="icon_check" class="nextant_display_icon"
							src="<?php print_unescaped(OCP\image_path('nextant', 'check.png')); ?>" />
						<img id="icon_fail" class="nextant_display_icon"
							src="<?php print_unescaped(OCP\image_path('nextant', 'fail.png')); ?>" />
						<div id="text" class="nextant_display_text"></div>
					</div>

					<div id="delete" class="nextant-display-line" style="top: 160px;">
						<img id="icon_check" class="nextant_display_icon"
							src="<?php print_unescaped(OCP\image_path('nextant', 'check.png')); ?>" />
						<img id="icon_fail" class="nextant_display_icon"
							src="<?php print_unescaped(OCP\image_path('nextant', 'fail.png')); ?>" />
						<div id="text" class="nextant_display_text"></div>
					</div>

					<div id="save" class="nextant-display-line" style="top: 200px;">
						<img id="icon_check" class="nextant_display_icon"
							src="<?php print_unescaped(OCP\image_path('nextant', 'check.png')); ?>" />
						<img id="icon_fail" class="nextant_display_icon"
							src="<?php print_unescaped(OCP\image_path('nextant', 'fail.png')); ?>" />
						<div id="text" class="nextant_display_text"></div>
					</div>

				</div>

				<div id="nextant-filters" class="nextant-box">

					<div id="nextant_saving_files_filters"
						style="position: absolute; top: 10px; right: 25px;">Saving</div>

					<div class="solr_filters_item_large solr_filters_title">Type of
						files to extract</div>

					<div class="solr_filters_item_large">
						<input type="checkbox" name="solr_filters_text"
							id="solr_filters_text" value="1" style="margin: 10px;"> Text File
						(Text, Html, Subtitles, Source Code, ...)
					</div>
					<div class="solr_filters_item">
						<input type="checkbox" name="solr_filters_pdf"
							id="solr_filters_pdf" value="1" style="margin: 10px;"> E-pub /
						PDF / rtf
					</div>
					<div class="solr_filters_item">
						<input type="checkbox" name="solr_filters_office"
							id="solr_filters_office" value="1" style="margin: 10px;"> Office
						Documents
					</div>
					<div class="solr_filters_item">
						<input type="checkbox" name="solr_filters_image"
							id="solr_filters_image" value="1" style="margin: 10px;"> Image
						(Jpeg, Tiff)
					</div>
					<div class="solr_filters_item">
						<input type="checkbox" name="solr_filters_audio"
							id="solr_filters_audio" value="1" style="margin: 10px;"> Audio
						File (mp3, flac)
					</div>

					<div class="solr_filters_item_large solr_filters_title"
						style="margin-top: 15px;">Extension you wish to ignore (.java
						.xml)</div>
					<div class="solr_filters_item_large">
						<textarea name="" style="width: 435px; height: 75px;"
							id="solr_filters_extensions"></textarea>
					</div>

				</div>


			</td>
		</tr>

	</table>

</div>


