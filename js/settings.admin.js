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

						save : function() {
							$('#nextant_apply').attr('disabled', true);
							$('#solr_url').attr('disabled', true);
							$('#solr_core').attr('disabled', true);
							$('#solr_live_extract').attr('disabled', true);
							$('#solr_live_docupdate').attr('disabled', true);
							nextantSettings.test('ping');
						},

						test_standby : function(command) {
							setTimeout(function() {
								nextantSettings.test(command);
							}, 1200);
						},
						test : function(command) {

							var data = {
								solr_url : $('#solr_url').val(),
								solr_core : $('#solr_core').val(),
								live_extract : $('#solr_live_extract').prop(
										'checked'),
								live_docupdate : $('#solr_live_docupdate')
										.prop('checked'),
								command : command
							}

							switch (command) {
							case 'ping':
								OC.msg.startAction('#nextant-admin-msg', t(
										'nextant',
										'Ping querying your Solr Server'));
								break;

							case 'schema':
								OC.msg.startAction('#nextant-admin-msg',
										t('nextant',
												'Verifying Schema integrity'));
								break;

							case 'extract':
								OC.msg.startAction('#nextant-admin-msg', t(
										'nextant',
										'Test simple text extract query'));
								break;

							case 'update':
								OC.msg.startAction('#nextant-admin-msg',
										t('nextant',
												'Test update document query'));
								break;

							case 'search':
								OC.msg.startAction('#nextant-admin-msg', t(
										'nextant', 'Test search query'));
								break;

							case 'delete':
								OC.msg.startAction('#nextant-admin-msg',
										t('nextant',
												'Removing the test document'));
								break;

							case 'save':
								OC.msg
										.startAction(
												'#nextant-admin-msg',
												t('nextant',
														'All test went fine. Saving your configuration'));
								break;
							}

							$.post(OC.filePath('nextant', 'ajax/settings',
									'admin.php'), data, nextantSettings.tested);

						},

						tested : function(response) {

							OC.msg.finishedAction('#nextant-admin-msg',
									response);
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
							$('#solr_live_extract').attr('disabled', false);
							$('#solr_live_docupdate').attr('disabled', false);
							$('#nextant_apply').attr('disabled', false);
						},

						forceindex : function() {
							$.post(OC.filePath('nextant', 'ajax/settings',
									'forceindex.php'), {},
									nextantSettings.forced);

						},

						forced : function() {
							$('#nextant_force_index').hide();
						}
					}

					$('#nextant_apply').on('click', nextantSettings.save);
					$('#nextant_force_index').on('click',
							nextantSettings.forceindex);

				});
