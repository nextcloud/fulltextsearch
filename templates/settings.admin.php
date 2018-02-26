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
			<td colspan="2"><em><?php p($l->t('Specify the address of your Solr, and the name of the core to use.')); ?>
			<br /> <a href="https://github.com/nextcloud/nextant/wiki"
					target="_blank">
					<?php p($l->t('If you do not have a Solr servlet running, installation-guides can be found on the wiki.')); ?></a>
					<br /> <a
					href="https://github.com/nextcloud/nextant/wiki/Admin-Interface">
						<?php p($l->t('You can also find more information there about this interface.')); ?></a></em></td>
		</tr>
		<tr valign="top">
			<td>
				<table style="width: 650px;">
					<tr>
						<td>&nbsp;</td>
					</tr>
					<tr class="nextant_admin_head">
						<td><div id="nextant_saving_settings"><?php p($l->t('Saving')); ?></div></td>
						<td class="nextant_admin_head"><?php p($l->t('Settings')); ?></td>
					</tr>
					<tr style="height: 30px;">
						<td class="nextant_admin_left">
	    <?php p($l->t('Nextant version:')) ?></td>
						<td><div id="nextant_version"></div></td>
					</tr>
					<tr style="height: 30px;">
						<td class="nextant_admin_left">
	    <?php p($l->t('Address of your Solr servlet:')) ?></td>
						<td><input type="text" name="solr_url" id="solr_url" value=""
							style="width: 250px;"></td>
					</tr>

					<tr style="height: 30px;">
						<td class="nextant_admin_left"><?php p($l->t('Core:')) ?></td>
						<td><input type="text" id="solr_core" value=""
							style="width: 250px;"></td>
					</tr>

					<tr style="height: 30px;">
						<td class="nextant_admin_left"><?php p($l->t('Timeout (in seconds):')) ?></td>
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
					<table style="width: 650px;">
						<tr>
							<td>&nbsp;</td>
						</tr>
						<tr class="nextant_admin_head" id="solr_index_files_cont">
							<td><div id="nextant_saving_files"> <?php p($l->t('Saving')); ?></div></td>
							<td colspan="2" class="nextant_admin_head"><input type="checkbox"
								name="solr_index_files" id="solr_index_files" value="1"
								style="margin: 5px; margin-right: 15px;"><?php p($l->t('Indexing Files')); ?></td>
						</tr>
					</table>
					<div id="solr_sub_files">
						<table style="width: 650px;">

							<tr style="height: 30px;">
								<td class="nextant_admin_left">
	    <?php p($l->t('Maximum File Size (Mb):')) ?></td>
								<td><input type="text" id="solr_index_files_max_size"
									style="width: 100px;"></td>
								<td style="width: 60px; padding-right: 10px;">[<span
									style="font-size: 12px;" id="solr_switch_filters"><?php p($l->t('Edit your filters')); ?></span>]
								</td>
							</tr>

							<tr style="height: 30px;">
								<td class="nextant_admin_left">
	    <?php p($l->t('Index Files Tree:')) ?></td>
								<td colspan="2"><input type="checkbox"
									name="solr_index_files_tree" id="solr_index_files_tree"
									value="1" style="margin: 10px;"> <span
									id="solr_index_files_nextant_only_span"><input type="checkbox"
										name="solr_index_files_nextant_only"
										id="solr_index_files_nextant_only" value="1"
										style="margin: 10px;"><?php p($l->t('Disable default search from Files')); ?></span>
								</td>
							</tr>

							<tr style="height: 30px;">
								<td class="nextant_admin_left">
	    <?php p($l->t('Index File Trash:')) ?></td>
								<td colspan="2"><input type="checkbox"
									name="solr_index_files_trash" id="solr_index_files_trash"
									value="1" style="margin: 10px;"></td>
							</tr>

							<tr style="height: 30px;">
								<td class="nextant_admin_left">
	    <?php p($l->t('Index Public Links:')) ?></td>
								<td colspan="2"><input type="checkbox"
									name="solr_index_files_sharelink"
									id="solr_index_files_sharelink" value="1" style="margin: 10px;"></td>
							</tr>

							<tr style="height: 30px;">
								<td class="nextant_admin_left">
	    <?php p($l->t('Index Federated Shares:')) ?></td>
								<td colspan="2"><input type="checkbox"
									name="solr_index_files_federated"
									id="solr_index_files_federated" value="1" style="margin: 10px;"></td>
							</tr>

							<tr style="height: 30px;">
								<td class="nextant_admin_left">
	    <?php p($l->t('Index External Storages:')) ?></td>
								<td colspan="2"><input type="checkbox"
									name="solr_index_files_external" id="solr_index_files_external"
									value="1" style="margin: 10px;"></td>
							</tr>

							<tr style="height: 30px;">
								<td class="nextant_admin_left">
	    <?php p($l->t('Index Encrypted Files:')) ?></td>
								<td colspan="2"><input type="checkbox"
									name="solr_index_files_encrypted"
									id="solr_index_files_encrypted" value="1" style="margin: 10px;"></td>
							</tr>

							<tr style="height: 30px;">
								<td class="nextant_admin_left">
	    <?php p($l->t('Last index:')) ?></td>
								<td colspan="2"><div id="solr_index_files_last"></div></td>
							</tr>

						</table>
					</div>

					<table style="width: 650px;">
						<tr>
							<td>&nbsp;</td>
						</tr>
						<tr class="nextant_admin_head" id="solr_index_bookmarks_cont">
							<td><div id="nextant_saving_bookmarks"><?php p($l->t('Saving')); ?></div></td>
							<td class="nextant_admin_head"><input type="checkbox"
								name="solr_index_bookmarks" id="solr_index_bookmarks" value="1"
								style="margin: 5px; margin-right: 15px;"><?php p($l->t('Indexing Bookmarks')); ?></td>
						</tr>
					</table>

					<div id="solr_sub_bookmarks">
						<table style="width: 650px;">
							<tr style="height: 30px;">
								<td colspan="2">
									<div id="nextant_bookmarks_appdisabled">
										<?php p($l->t('The App Bookmarks is not installed/enabled.')); ?>
									</div>
									<div id="nextant_bookmarks_appenabled">
										<?php p($l->t('"App Bookmarks" enabled, your bookmarks will be indexed.')); ?>
									</div>
								</td>
							</tr>
							<tr style="height: 30px;">
								<td class="nextant_admin_left">
	    <?php p($l->t('Last index:')) ?></td>
								<td><div id="solr_index_bookmarks_last"></div></td>
							</tr>

						</table>
					</div>

					<table style="width: 650px;">

						<tr>
							<td>&nbsp;</td>
						</tr>
						<tr class="nextant_admin_head">
							<td><div id="nextant_saving_status"><?php p($l->t('Saving')); ?></div>
								<div id="nextant_saving_check"><?php p($l->t('Checking')); ?></div></td>
							<td class="nextant_admin_head"><?php p($l->t('Nextant Status')); ?></td>
						</tr>

						<tr style="height: 30px;">
							<td class="nextant_admin_left">					
	    <?php p($l->t('Resource Level:')); ?>
						</td>
							<td colspan="2"><select name="solr_resource_level"
								id="solr_resource_level" style="width: 200px">
									<option value="5"><?php p($l->t('Higher performance')); ?></option>
									<option value="4"><?php p($l->t('High performance')); ?></option>
									<!-- <option value="3"><?php p($l->t('Medium performance')); ?></option> -->
									<option value="2"><?php p($l->t('Low performance')); ?></option>
									<option value="1"><?php p($l->t('Lower performance')); ?></option>
							</select></td>
						</tr>

						<tr style="height: 30px;">
							<td class="nextant_admin_left">					
	    <?php p($l->t('Live Index:')); ?>
						</td>
							<td colspan="2"><select id="solr_index_live" style="width: 200px">
									<option value="0"><?php p($l->t('Disabled')); ?></option>
									<!-- <option value="1"><?php p($l->t('Using Semaphore')); ?></option>  -->
									<option value="2"><?php p($l->t('Using Database')); ?></option>
							</select> <!-- 							<input type="text" id="solr_index_live_queuekey"
								style="width: 100px; font-size: 12px;" /> --></td>
						</tr>

						<tr style="height: 30px;">
							<td class="nextant_admin_left" style="text-valign: top;">
	    <?php p($l->t('Minimum Background Index delay:')) ?></td>
							<td><input type="text" name="solr_index_delay_min"
								id="solr_index_delay_min" value="2"
								style="width: 50px; margin-right: 10px;" /><?php p($l->t('hours')); ?>
								</td>
						</tr>

						<tr style="height: 30px;">
							<td class="nextant_admin_left" style="text-valign: top;">
	    <?php p($l->t('Maximum Background Index delay:')) ?></td>
							<td><input type="text" name="solr_index_delay_max"
								id="solr_index_delay_max" value="2"
								style="width: 50px; margin-right: 10px;" /><?php p($l->t('days')); ?>
								</td>
						</tr>

						<tr style="height: 30px;">
							<td class="nextant_admin_left">					
	    <?php p($l->t('Nextcloud Cron:')); ?>
						</td>
							<td colspan="2"><input type="checkbox" name="solr_use_cron"
								id="solr_use_cron" value="1" style="margin: 10px;" />
						
						</tr>

						<tr style="height: 30px;">
							<td class="nextant_admin_left">
	    <?php p($l->t('Number of documents:')) ?></td>
							<td><div id="solr_current_docs"></div></td>
						</tr>
						<tr style="height: 30px;">
							<td class="nextant_admin_left">
	    <?php p($l->t('Number of segments:')) ?></td>
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
									<i>
									<?php p($l->t('it is recommended to do the very first indexing of your files using the ./occ nextant:index command.')); ?>
									<?php p($l->t('However, you can force it to be started as a background process.')); ?>
									</i> <br />
									<button type="button" id="nextant_force_first_index"
										style="width: 270px"><?php p($l->t('Force first index as a cronjob')) ?></button>
								</div>
								<button type="button" id="nextant_force_index"
									style="width: 270px"><?php p($l->t('Force index')) ?></button>
								<div id="nextant_index_scheduled">
									<i><?php p($l->t('Indexing is scheduled within the next few hours (Cron)')); ?></i>
								</div>
								<div id="nextant_index_inprogress">
									<i><?php p($l->t('Indexing in progress')); ?></i>
								</div>
							</td>
						</tr>

					</table>
				</div>
			</td>
			<td
				style="padding-left: 30px; padding-top: 40px; vertical-align: top; position: relative;">
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
						style="position: absolute; top: 10px; right: 25px;"><?php p($l->t('Saving')); ?></div>

					<div class="solr_filters_item_large solr_filters_title"><?php p($l->t('Type of files to extract')); ?></div>

					<div class="solr_filters_item_large">
						<input type="checkbox" name="solr_filters_text"
							id="solr_filters_text" value="1" style="margin: 10px;"> <?php p($l->t('Text File (Text, HTML, Subtitles, Source Code…)')); ?>
					</div>
					<div class="solr_filters_item">
						<input type="checkbox" name="solr_filters_pdf"
							id="solr_filters_pdf" value="1" style="margin: 10px;"> <?php p($l->t('EPUB / PDF / RTF')); ?>
					</div>
					<div class="solr_filters_item">
						<input type="checkbox" name="solr_filters_office"
							id="solr_filters_office" value="1" style="margin: 10px;"> <?php p($l->t('Office Documents')); ?>
					</div>
					<div class="solr_filters_item">
						<input type="checkbox" name="solr_filters_image"
							id="solr_filters_image" value="1" style="margin: 10px;"> <?php p($l->t('Image (JPEG, TIFF)')); ?>
					</div>
					<div class="solr_filters_item">
						<input type="checkbox" name="solr_filters_audio"
							id="solr_filters_audio" value="1" style="margin: 10px;"> <?php p($l->t('Audio File (MP3, FLAC)')); ?>
					</div>

					<div class="solr_filters_item_large solr_filters_title"
						style="margin-top: 15px;"><?php p($l->t('File extensions you wish to ignore (.java .xml)')); ?></div>
					<div class="solr_filters_item_large">
						<textarea name="" style="width: 435px; height: 75px;"
							id="solr_filters_extensions"></textarea>
					</div>

				</div>

			</td>
		</tr>

	</table>

</div>


