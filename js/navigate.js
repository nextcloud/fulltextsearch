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

					var nextantCurrentSearch = '';
					var nextant = {

						init : function() {
							$('#searchbox').on('input', function(e) {
								nextant.search($('#searchbox').val());
							});
						},

						search : function(string) {
							if (string == nextantCurrentSearch)
								return;
							nextantCurrentSearch = string;

							request = {
								search : string,
								current_dir : nextant.get('dir')
							}

							nextant.searchRequest(request);
						},

						searchRequest : function(request) {
							// window.alert('nextant/searchRequest: '
							// + request.current_dir);
							$.post(
									OC
											.filePath('nextant', 'ajax',
													'search.php'), request,
									nextant.searchResult);
						},

						searchResult : function(response) {
//							window.alert('nextant/searchResult: '
//									+ response.message + ' size: '
//									+ response.data.length);

							// OC.search.resultTypes.lucene = t('search_lucene',
							// 'In');

							// OC.search.customResults.lucene = function ($row,
							// item){
							// $row.find('td.result
							// .text').text(t('search_lucene',
							// 'Score: {score}', {score:
							// Math.round(item.score*100)/100}));
						},

						get : function(name, url) {
							if (!url)
								url = window.location.href;
							name = name.replace(/[\[\]]/g, "\\$&");
							var regex = new RegExp("[?&]" + name
									+ "(=([^&#]*)|&|#|$)"), results = regex
									.exec(url);
							if (!results)
								return null;
							if (!results[2])
								return '';
							return decodeURIComponent(results[2].replace(/\+/g,
									" "));
						}

					}

					nextant.init();

				});
