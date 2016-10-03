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

					(function(func) {
						$.fn.addClass = function() {
							func.apply(this, arguments);
							this.trigger('classChanged');
							return this;
						}
					})($.fn.addClass);

					(function(func) {
						$.fn.removeClass = function() {
							func.apply(this, arguments);
							this.trigger('classChanged');
							return this;
						}
					})($.fn.removeClass);

					$('#searchresults').on('classChanged', function() {
						if ($('#searchresults').attr('class') == 'hidden')
							$('#nextantList').hide();
						else
							$('#nextantList').show();
					});

					var nextantCurrentSearch = '';
					var nextant = {

						init : function() {
							$('#searchbox').on('input', function(e) {
								nextant.search($('#searchbox').val());
							});
						},

						search : function(query) {
							if (query == nextantCurrentSearch)
								return;
							nextantCurrentSearch = query;

							var data = {
								query : query,
								current_dir : nextant.get('dir')
							}

							nextant.searchRequest(data);
						},

						searchRequest : function(data) {
							$.post(
									OC
											.filePath('nextant', 'ajax',
													'search.php'), data,
									nextant.searchResult);
						},

						searchResult : function(response) {
							if (response == null)
								return;

							if (!$('#nextantList').length) {
								// $('#searchresults').prepend(
								$('#fileList')
										.append(
												'<tr><td colspan="3" style="margin: 0px; padding: 0px;"><div id="nextantList"></div></td></tr>');
							}
							$('#nextantList').empty();

							response
									.forEach(function(entry) {
										$row = nextant
												.template_entry()
												.replace(/%ID%/gi, entry.id)
												.replace(/%TYPE%/gi, entry.type)
												.replace(/%SIZE%/gi, entry.size)
												.replace(/%MIME%/gi, entry.mime)
												.replace(/%MTIME%/gi,
														entry.mtime)
												.replace(/%BASEFILE%/gi,
														entry.basefile)
												.replace(/%WEBDAV%/gi,
														entry.webdav)
												.replace(/%EXTENSION%/gi,
														entry.extension)
												.replace(/%PATH%/gi, entry.path)
												.replace(/%DIRNAME%/gi,
														entry.basepath)
												.replace(/%HIGHLIGHT1%/gi,
														entry.highlight1)
												.replace(/%HIGHLIGHT2%/gi,
														entry.highlight2)

												.replace(/%FILENAME%/gi,
														entry.filename);

										$('#nextantList').append($row);
									});
						},

						template_entry : function() {

							$tmpl = '<tr data-id="%ID%" data-type="%TYPE%" data-size="%SIZE%" data-file="%FILENAME%" data-mime="%MIME%" data-mtime="%MTIME%000" data-etag="" data-permissions="" data-has-preview="false" data-path="%DIRNAME%" data-share-permissions="">';
							$tmpl += '<td class="filename ui-draggable">';
							$tmpl += '<a href="#" class="action action-favorite " data-original-title="" title="">';
							$tmpl += '<span class="icon icon-star"></span><span class="hidden-visually">Favorite</span></a>';
							$tmpl += '<input id="select-files-%ID%" class="selectCheckBox checkbox" type="checkbox">';
							$tmpl += '<label for="select-files-%ID%"><div class="thumbnail" style="background-image:url(/core/img/filetypes/application-pdf.svg); background-size: 32px;"></div>';
							$tmpl += '<span class="hidden-visually">Select</span></label>';

							$tmpl += '<a class="name" href="%WEBDAV%">';
							$tmpl += '<div>';
							$tmpl += '<span class="nextant_line1">%PATH%</span>';
							$tmpl += '<span class="nextant_line2">%HIGHLIGHT1%</span>';
							$tmpl += '<span class="nextant_line3">%HIGHLIGHT2%</span>';
							$tmpl += '</div>';

							// $tmpl += '<span class="fileactions"><a
							// class="action action-share permanent" href="#"
							// data-action="Share" data-original-title=""
							// title="">';
							// $tmpl += '<span class="icon
							// icon-share"></span><span
							// class="hidden-visually"></span></a>';
							// $tmpl += '<a class="action action-menu permanent"
							// href="#" data-action="menu"
							// data-original-title="" title=""><span class="icon
							// icon-more"></span>';
							// $tmpl += '<span
							// class="hidden-visually">Actions</span></a></span></a>';
							$tmpl += '</td>';
							$tmpl += '<td class="filesize" style="color:rgb(-17,-17,-17)">13.3 MB</td><td class="date"><span class="modified" title="" style="color:rgb(155,155,155)" data-original-title="September 1, 2016 12:05 PM">a month ago</span></td></tr>';

							return $tmpl;

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
									' '));
						}

					}

					nextant.init();

				});
