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
 * This program is free software: you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option) any
 * later version.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * 
 */
$(document)
		.ready(
				function() {

					var nextantSettings = {

						init : function() {
							nextantSettings.statusclearall(true);
							nextantSettings.checksuboptions(true);
							setInterval(function() {
								nextantSettings.checksuboptions(false)
							}, 60000);
						},

						savesuboptions : function(switched) {

							var needed_index = -1;
							if (switched == 'force_index')
								needed_index = 1;
							var data = {
								live_extract : ($('#solr_live_extract')
										.is(":checked")) ? 1 : 0,
								live_docupdate : ($('#solr_live_docupdate')
										.is(":checked")) ? 1 : 0,
								max_size : $('#solr_max_size').val(),
								display_result : $('#solr_display_result')
										.val(),
								needed_index : needed_index
							}

							if (switched == 'live_extract')
								data.live_extract = (data.live_extract == 1) ? 0
										: 1;
							if (switched == 'live_docupdate')
								data.live_docupdate = (data.live_docupdate == 1) ? 0
										: 1;

							$.post(OC.filePath('nextant', 'ajax/settings',
									'option.php'), data,
									nextantSettings.updatesuboptions);
						},

						checksuboptions : function(instant) {
							$.post(OC.filePath('nextant', 'ajax/settings',
									'updateSubOptions.php'), {
								instant : instant
							}, nextantSettings.updatesuboptions);
						},

						updatesuboptions : function(response) {
							var delay = 600;
							if (response.instant == 'true')
								delay = 0;

							if (response.configured == 1) {
								$("#nextant_suboptions :input").attr(
										"disabled", false);
								$('#nextant_help_link').unbind('click');
								$('#nextant_suboptions').fadeTo(delay, 1);
							} else {
								$("#nextant_suboptions :input").attr(
										"disabled", true);
								$('#nextant_help_link').bind('click',
										function(e) {
											e.preventDefault();
										})
								$('#nextant_suboptions').fadeTo(delay, 0.4);
							}

							$('#solr_live_extract').prop('checked',
									(response.live_extract == 1));
							$('#solr_live_docupdate').prop('checked',
									(response.live_docupdate == 1));

							if (response.last_index > 0)
								$('#solr_last_index').text(
										response.last_index_format);
							else
								$('#solr_last_index').text('never');

							$('#solr_max_size').val(response.max_size);

							$(
									'#solr_display_result option[value="'
											+ response.display_result + '"]')
									.prop('selected', true);

							if (response.configured == 0) {
								$('#solr_current_docs').text(
										'Nextant is not configured yet');
								$('#nextant_force_index').hide(delay);
								$('#nextant_index_scheduled').hide(delay);
							} else if (response.solr_ping == 'false')
								$('#solr_current_docs').text(
										'Solr Core is down');
							else {
								if (response.needed_index == 1) {
									$('#nextant_force_index').hide(delay);
									$('#nextant_index_scheduled').show(delay);
								} else {
									$('#nextant_index_scheduled').hide(delay);
									$('#nextant_force_index').show(delay);
								}
								if (response.current_docs > 0)
									$('#solr_current_docs').text(
											response.current_docs);
								else
									$('#solr_current_docs').text('none');
							}
						},

						statusclearall : function(instant) {
							nextantSettings.statusclear('#ping', instant);
							nextantSettings.statusclear('#schema', instant);
							nextantSettings.statusclear('#extract', instant);
							nextantSettings.statusclear('#update', instant);
							nextantSettings.statusclear('#search', instant);
							nextantSettings.statusclear('#delete', instant);
							nextantSettings.statusclear('#save', instant);
						},

						statusclear : function(vid, instant) {
							var delay = 200;
							if (instant)
								delay = 0;

							$('#nextant-display').children(vid).children(
									'#icon_fail').fadeTo(delay, 0);
							$('#nextant-display').children(vid).children(
									'#icon_check').fadeTo(delay, 0);
							$('#nextant-display').children(vid).children(
									'#text').fadeTo(delay, 0);
						},

						status : function(vid, text, level) {
							$('#nextant-display').children(vid).children(
									'#text').text(text).fadeTo(200, 1);
							var src = '';
							if (level == 1) {
								$('#nextant-display').children(vid).children(
										'#icon_check').fadeTo(200, 1);
								$('#nextant-display').children(vid).children(
										'#icon_fail').fadeTo(200, 0);
							}

							if (level == 2) {
								$('#nextant-display').children(vid).children(
										'#icon_check').fadeTo(200, 0);
								$('#nextant-display').children(vid).children(
										'#icon_fail').fadeTo(200, 1);

							}
						},

						save : function() {
							$('#nextant_apply').attr('disabled', true);
							$('#solr_url').attr('disabled', true);
							$('#solr_core').attr('disabled', true);
							nextantSettings.test('ping');
						},

						test_standby : function(command) {
							// setTimeout(function() {
							nextantSettings.test(command);
							// }, 400);
						},

						test : function(command) {

							var data = {
								solr_url : $('#solr_url').val(),
								solr_core : $('#solr_core').val(),
								command : command
							}

							switch (command) {
							case 'ping':
								if ($('#nextant-display').children('#ping')
										.children('#text').text() != '')
									nextantSettings.statusclearall(false);
								nextantSettings.status('#ping',
										'Ping querying your Solr Server', 0);
								break;

							case 'schema':
								nextantSettings.status('#schema',
										'Verifying Schema integrity', 0);
								break;

							case 'extract':
								nextantSettings.status('#extract',
										'Test simple text extract query', 0);
								break;

							case 'update':
								nextantSettings.status('#update',
										'Test update document query', 0);
								break;

							case 'search':
								nextantSettings.status('#search',
										'Test search query', 0);
								break;

							case 'delete':
								nextantSettings.status('#delete',
										'Removing the test document', 0);
								break;

							case 'save':
								nextantSettings
										.status(
												'#save',
												'All test went fine. Saving your configuration',
												0);
								nextantSettings.checksuboptions(false);
								break;
							}

							$.post(OC.filePath('nextant', 'ajax/settings',
									'admin.php'), data,
									nextantSettings.tested_standby);
						},

						tested_standby : function(response) {
							// setTimeout(function() {
							nextantSettings.tested(response);
							// }, 200);
						},

						tested : function(response) {
							nextantSettings.status('#' + response.command,
									response.message,
									(response.status == 'success') ? 1 : 2);

							switch (response.command) {
							case 'ping':
								if (response.status == 'success')
									nextantSettings.test_standby('schema');
								else
									nextantSettings.reset();
								break;

							case 'schema':
								if (response.status == 'success')
									nextantSettings.test_standby('extract');
								else
									nextantSettings.reset();
								break;

							case 'extract':
								if (response.status == 'success')
									nextantSettings.test_standby('update');
								else
									nextantSettings.reset();
								break;

							case 'update':
								if (response.status == 'success')
									nextantSettings.test_standby('search');
								else
									nextantSettings.reset();
								break;

							case 'search':
								if (response.status == 'success')
									nextantSettings.test_standby('delete');
								else
									nextantSettings.reset();
								break;

							case 'delete':
								if (response.status == 'success')
									nextantSettings.test_standby('save');
								else
									nextantSettings.reset();
								break;

							case 'save':
								nextantSettings.reset();
								break;
							}

						},

						reset : function() {
							$('#solr_url').attr('disabled', false);
							$('#solr_core').attr('disabled', false);
							$('#nextant_apply').attr('disabled', false);
						}
					}

					$('#nextant_apply').on('click', nextantSettings.save);

					$('#solr_live_extract').mousedown(function() {
						nextantSettings.savesuboptions('live_extract');
					});
					$('#solr_live_docupdate').mousedown(function() {
						nextantSettings.savesuboptions('live_docupdate');
					});
					$('#solr_max_size').on('input', function(e) {
						nextantSettings.savesuboptions();
					});
					$('#nextant_force_index').on('click', function() {
						nextantSettings.savesuboptions('force_index');
					});
					$('#solr_display_result').on('change', function() {
						nextantSettings.savesuboptions();
					});

					nextantSettings.init();

				});
