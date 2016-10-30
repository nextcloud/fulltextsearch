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

					$('#searchbox').focusout(function() {
						nextantCurrentFocus = false;
						nextant.suggestShow();
					});

					$('#searchbox').focusin(function() {
						nextantCurrentFocus = true;
						nextant.suggestShow();
					});

					$(document).keypress(function(e) {
						if (e.which == 13 && nextantCurrentFocus) {
							nextant.search();
						}
					});

					var nextantCurrentSearch = '';
					var nextantCurrentFocus = false;
					var nextantSearchDelayTimer = null;
					var nextantSuggestDelayTimer = null;
					var nextantNoSpamSuggest = false;
					var nextant = {

						init : function() {
							$('#searchbox').on('input', function(e) {
								nextant.searchTimer();
								nextant.suggestTimer();
							});
						},

						searchTimer : function() {
							if (nextantSearchDelayTimer != null)
								clearTimeout(nextantSearchDelayTimer);

							nextantSearchDelayTimer = setTimeout(function() {
								nextant.search();
							}, 150);
						},

						suggestTimer : function() {
							if (nextantSuggestDelayTimer != null)
								clearTimeout(nextantSuggestDelayTimer);

							nextantSuggestDelayTimer = setTimeout(function() {
								nextant.suggest();
							}, 0);
						},

						search : function() {
							nextantSearchDelayTimer = null;

							var query = $('#searchbox').val();
							if (query == nextantCurrentSearch)
								return;
							nextantCurrentSearch = query;

							var data = {
								query : query,
								current_dir : nextant.get('dir')
							}

							nextant.searchRequest(data);
						},

						suggest : function() {
							nextantSuggestDelayTimer = null;
							var query = $('#searchbox').val();
							var data = {
								query : query
							}
							nextant.suggestRequest(data);
						},

						suggestRequest : function(data) {
							if (nextantNoSpamSuggest)
								return;
							$.post(
									OC.filePath('nextant', 'ajax',
											'suggest.php'), data,
									nextant.suggestResult);
						},

						suggestResult : function(response) {

							if (response == null || response.length == 0
									|| response == false) {
								
								window.alert('>> ' . response);
								if (response == false) {
									nextantNoSpamSuggest = true;
									setTimeout(function() {
										nextantNoSpamSuggest = false;
									}, 60000);
								}
								if ($('#nextantSugg_list').length)
									$('#nextantSugg_list').hide(200);
								return;
							}

							if (!$('#nextantSugg_list').length)
								$('#body-user').append(
										'<div id="nextantSugg_list"></div>');

							nextant.suggestShow();

							var offset = $('#searchbox').offset();
							var height = $('#searchbox').height();
							var top = offset.top + height + "px";
							var left = offset.left + "px";

							$('#nextantSugg_list').css({
								'position' : 'absolute',
								'left' : left,
								'top' : top
							});

							$('#nextantSugg_list').empty();
							for (var i = 0; i < response.length; i++) {
								var first = '';
								if (i == 0)
									first = 'nextantSugg_firstitem';

								$('#nextantSugg_list').append(
										'<div id="nextant_sugg_' + i
												+ '" class="nextantSugg_item '
												+ first + '">'
												+ response[i].suggestion
												+ '</div>');
							}

							$('.nextantSugg_item').click(function() {
								nextant.suggestReplace($(this).text());
								nextant.search();
							});

						},

						suggestShow : function() {

							if (!$('#nextantSugg_list').length)
								return;
							if (nextantCurrentFocus)
								$('#nextantSugg_list').show(200);
							else
								$('#nextantSugg_list').hide(200);
						},

						suggestReplace : function(txt) {
							$('#searchbox').val(txt + ' ');
							$('#searchbox').focus();
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

							if (!$('#nextantList').length)
								$('#fileList')
										.append(
												'<tr><td colspan="3" style="margin: 0px; padding: 0px;"><div id="nextantList"></div></td></tr>');

							$('#nextantList').empty();

							response
									.forEach(function(entry) {
										var row = nextant
												.template_entry()
												.replace(/%ID%/gi, entry.id)
												.replace(/%TYPE%/gi, entry.type)
												.replace(/%TITLE%/gi,
														entry.title)
												.replace(/%LINKMAIN%/gi,
														entry.link_main)
												.replace(/%FILENAME%/gi,
														entry.filename)
												.replace(/%DIRPATH%/gi,
														entry.dirpath)
												.replace(/%SIZE%/gi, entry.size)
												.replace(/%SIZEREAD%/gi,
														entry.size_readable)
												.replace(/%MIMETYPE%/gi,
														entry.mimetype)
												.replace(/%ICON%/gi, entry.icon)
												.replace(/%MTIME%/gi,
														entry.mtime).replace(
														/%HIGHLIGHT1%/gi,
														entry.highlight1)
												.replace(/%HIGHLIGHT2%/gi,
														entry.highlight2);

										row = row
												.replace(
														/%SHARED%/gi,
														(entry.shared != '') ? ' style="background-image:url('
																+ entry.shared
																+ ');"'
																: '');
										row = row
												.replace(
														/%DELETED%/gi,
														(entry.deleted != '') ? ' style="background-image:url('
																+ entry.deleted
																+ ');"'
																: '');

										$('#nextantList').append(row);
									});
						},

						template_entry : function() {

							$tmpl = '<tr data-id="%ID%" data-type="%TYPE%" data-size="%SIZE%" data-file="%FILENAME%" data-mime="%MIMETYPE%" data-mtime="%MTIME%000" data-etag="" ';
							$tmpl += ' data-permissions="" data-has-preview="false" data-path="%PATH%" data-share-permissions="">';
							$tmpl += '<td class="filename ui-draggable">';
							$tmpl += '<a class="action action-favorite " data-original-title="" title="">';
							$tmpl += '</a>';
							$tmpl += '<label for="select-files-%ID%"><div class="thumbnail" style="background-image:url(%ICON%); background-size: 32px;">';
							$tmpl += '<div class="nextant_details" %DELETED%%SHARED%></div>';
							$tmpl += '</div>';
							$tmpl += '<span class="hidden-visually">Select</span></label>';

							$tmpl += '<a class="nextant_file" href="%LINKMAIN%">';
							$tmpl += '<div>';
							$tmpl += '<span class="nextant_line nextant_line1">%TITLE%</span>';
							$tmpl += '<span class="nextant_line nextant_line2">%HIGHLIGHT1%</span>';
							$tmpl += '<span class="nextant_line nextant_line3">%HIGHLIGHT2%</span>';
							$tmpl += '</div></a>';
							$tmpl += '</td>';
							$tmpl += '<td class="filesize" style="color:rgb(-17,-17,-17)">%SIZEREAD%</td>';
							$tmpl += '<td class="date"><span class="modified" title="" style="color:rgb(155,155,155)" data-original-title=""></span></td></tr>';

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
