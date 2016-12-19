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
(function() {

	/**
	 * @constructs Nextant
	 */
	var Nextant = function() {
		this.initialize();
	};

	Nextant.prototype = {

		fileList : null,
		requesting : false,
		currFiles : null,
		oldQuery : '',
		searchResult : null,
		locked : false,
		config : null,

		nextant_sharelink : true,
		searchboxFocus : false,

		timerQuery : null,
		suggestNoSpam : false,

		/**
		 * Initialize the file search
		 */
		initialize : function() {

			var self = this;

			//
			// fileAppLoaded - detect if Files App is loaded
			this.fileAppLoaded = function() {
				return !!OCA.Files && !!OCA.Files.App;
			};

			//
			// initFileList - init fileList
			this.initFileList = function() {

				var query = $('#searchbox').val();
				if (self.fileList != null) {

					if (self.oldQuery == '')
						self.currFiles = self.fileList.files;

					self.oldQuery = $('#searchbox').val();
					return;
				}

				if (self.nextant_sharelink)
					self.fileList = OCA.Sharing.PublicApp.fileList;

				else {
					_.each(OC.Plugins.getPlugins('OCA.Search'),
							function(plugin) {
								if (plugin instanceof OCA.Search.Files)
									self.fileList = plugin.fileList;
							});
				}

				/*
				 * Haven't found a way to include (or remove) a fileAction only
				 * in a specific place (only in search result)
				 */
				if (self.nextant_sharelink || OCA.Files.App.fileList != null) // #82
					OCA.Files.fileActions.registerAction({
						name : 'nextant_gotofolder',
						displayName : t('nextant', 'Go to folder'),
						mime : 'all',
						permissions : OC.PERMISSION_READ,
						type : OCA.Files.FileActions.TYPE_DROPDOWN,
						icon : function() {
							return OC.imagePath('core', 'filetypes/folder');
						},
						actionHandler : function(filename, context) {
							self.onGoToFolder(filename, context);
						}
					});

				if (self.config == null)
					$.post(
							OC
									.filePath('nextant', 'ajax',
											'search_options.php'), {},
							self.getOptionsResult);

			};

			//
			// getOptionsResult - get config from server
			this.getOptionsResult = function(result) {
				if (self.config != null)
					return;

				self.config = result;
			}

			// initShareLink - init Share Link (only if needed)
			this.initShareLink = function() {
				if (!self.nextant_sharelink)
					return;

				$searchbox = '<form class="searchbox" action="#" method="post" role="search" novalidate="" style="padding-right: 300px;" o>';
				$searchbox += ' <label for="searchbox" class="hidden-visually">Search</label>';
				$searchbox += ' <input id="searchbox" name="query" value="" required="" autocomplete="off" tabindex="5" type="search">';
				$searchbox += '</form>';

				$('#logo-claim').after($searchbox);
				setTimeout(function() {
					$('#searchbox').fadeIn(500);
				}, 1000);

				$('#searchbox').on('input', function(e) {
					self.sendSuggestRequest();
					self.delayedSearchRequest();
				});

				$('DIV.crumb.svg.last').live('click', function() {
					$('#searchbox').val('');
					self.sendSearchRequest();
				});

				$('DIV.crumb.svg.ui-droppable').live('click', function() {
					$('#searchbox').val('');
					self.sendSearchRequest();
				});

				$("form.searchbox").submit(function() {
					return false;
				});

				$('#searchbox').focusout(function() {
					// nextantCurrentFocus = false;
					// nextant_share_link.suggestShow();
				});

				$('#searchbox').focusin(function() {
					// nextantCurrentFocus = true;
					// nextant_share_link.suggestShow();
				});

			};

			//
			// sendSearchRequest -
			this.sendSearchRequest = function() {

				var query = $('#searchbox').val();

				if (query == '') {
					if (self.searchResult != null) {
						self.searchResult = null;
						self.fileList.setSort('name', 'asc', false, false);
						self.fileList.setFiles(self.currFiles);
					}
					return;
				}

				if (self.requesting)
					return;

				self.requesting = true;
				$('#searchbox').addClass('searchbox_querying');

				var data = {
					query : query,
					current_dir : self.get('dir'),
					key : self.getShareLinkKey()
				}

				self.postSearchRequest(data);
			};

			//
			// sendSuggestRequest -
			this.sendSuggestRequest = function() {
				var query = $('#searchbox').val();

				if (query == '')
					return;

				if (self.requestingSuggest)
					return;

				self.requestingSuggest = true;

				var data = {
					query : query
				}

				self.postSuggestRequest(data);
			};

			//
			// searchRequest - search request
			this.postSearchRequest = function(data) {

				if (self.nextant_sharelink)
					$.post(OC.filePath('nextant', 'ajax', 'search_public.php'),
							data, self.searchRequestResult);
				else
					$.post(OC.filePath('nextant', 'ajax', 'search.php'), data,
							self.searchRequestResult);
			};

			//
			// suggest request
			this.postSuggestRequest = function(data) {

				if (self.suggestNoSpam)
					return;

				if (self.nextant_sharelink)
					$.post(
							OC
									.filePath('nextant', 'ajax',
											'suggest_public.php'), data,
							self.suggestRequestResult);
				else
					$.post(OC.filePath('nextant', 'ajax', 'suggest.php'), data,
							self.suggestRequestResult);
			};

			//
			// searchRequestResult - parse result from last request
			this.searchRequestResult = function(infos) {

				var result = infos.result;

				var origResult = [];
				if (self.config == null
						|| self.config.index_files_nextant_only != '1')
					origResult = self.currentFileResult();

				self.searchResult = origResult.concat(result);
				result = self.searchResult;

				if (result == null)
					return;

				var data = [];
				for (var i = 0; i < result.length; i++) {
					if (result[i] == null || result[i].entry == null)
						continue;

					var alr = false;
					for (var j = 0; j < data.length; j++) {
						if (data[j].id == result[i].entry.id) {
							alr = true;
							break;
						}
					}

					if (!alr)
						data.push(result[i].entry);
				}

				self.fileList.setSort('score', 'desc', false, false);
				self.fileList.setFiles(data);

				self.requesting = false;
				$('#searchbox').removeClass('searchbox_querying');

				self.updateSearchResult();

				if (infos.query && $('#searchbox').val() != infos.query)
					self.sendSearchRequest();
			};

			//
			// updateSearchResult - update and morph the filelist with result
			this.updateSearchResult = function() {

				var result = self.searchResult;
				if (result == null)
					return;

				// if (self.currQuery == '')
				// return;

				self.locked = false;

				// We edit each row
				_.each(result, function(item) {
					if (item == null)
						return;
					if (item.entry == null)
						return;

					tr = self.getElem(item.entry.name);

					if (!$(tr).length)
						return;

					self.__morphLink(tr, item);
					self.__morphResultDisplay(tr, item);
					self.__morphOverlayIcon(tr, item);
					self.__morphBookmarksFileAction(tr, item);
				});

				self.__morphSummary(result);
				self.__morphEmptyContent(result);

				// done
				self.locked = true;
			};

			//
			this.suggestRequestResult = function(response) {

				self.requestingSuggest = false;
				if (response.query && $('#searchbox').val() != response.query)
					self.sendSuggestRequest();

				if (response == null || response.status > 0
						|| response.result == null
						|| response.result.length == 0) {

					if (response.status > 0) {
						self.suggestNoSpam = true;
						setTimeout(function() {
							self.suggestNoSpam = false;
						}, 60000);
					}

					if ($('#nextant_suggestion').length)
						$('#nextant_suggestion').hide(200);
					return;
				}

				if (!$('#nextant_suggestion').length) {
					if (self.nextant_sharelink)
						$('#body-public').append(
								$('<div></div>').attr('id',
										'nextant_suggestion'));
					else
						$('#body-user').append(
								$('<div></div>').attr('id',
										'nextant_suggestion'));
				}

				self.suggestDisplay();

				$('#nextant_suggestion').empty();
				var result = response.result;
				for (var i = 0; i < result.length; i++) {
					var first = '';
					if (i == 0)
						first = 'nextant_suggestion_firstitem';

					$('#nextant_suggestion').append(
							$('<div></div>').attr('id', 'nextant_sugg_' + i)
									.attr('class',
											'nextant_suggestion_item ' + first)
									.html(result[i].suggestion));
				}

				$('.nextant_suggestion_item').click(function() {
					self.suggestReplace($(this).text());
					// self.searchRequest($('#searchbox'));
				});
			};

			//
			// init search on shared link
			this.delayedSearchRequest = function() {

				self.initFileList();
				// self.currQuery = query;

				if (self.timerQuery != null)
					window.clearTimeout(self.timerQuery);

				var delay = 250;
				if (self.config != null)
					switch (self.config.resource_level) {
					case '1':
						delay = 400;
						break;

					case '5':
						delay = 150;
						break;
					}

				self.timerQuery = setTimeout(function() {
					self.sendSearchRequest();
				}, delay);
			};

			//
			// return array containing current file from current directory that
			// fit the query.
			// (like current search in files app)
			this.currentFileResult = function() {
				var currFiles = self.currFiles;
				var data = [];

				if (currFiles == null || currFiles.length == 0)
					return data;

				for (var i = 0; i < currFiles.length; i++) {

					if (currFiles[i].name.toLowerCase().indexOf(
							$('#searchbox').val().toLowerCase()) === -1)
						continue;

					data.push({
						data : {
							id : currFiles[i].id,
							score : 9999
						},
						entry : currFiles[i]
					});
				}

				return data;
			};

			// 
			// refresh display of suggestion
			this.suggestDisplay = function() {

				var offset = $('#searchbox').offset();
				var height = $('#searchbox').height();
				var top = offset.top + height + "px";
				var left = offset.left + "px";

				$('#nextant_suggestion').css({
					'position' : 'absolute',
					'left' : left,
					'top' : top
				});

				if (!$('#nextant_suggestion').length)
					return;
				if (self.searchboxFocus)
					$('#nextant_suggestion').show(200);
				else
					$('#nextant_suggestion').hide(200);
			};

			//
			// replace search with suggestion
			this.suggestReplace = function(suggestion) {
				$('#searchbox').val(suggestion + ' ');
				$('#searchbox').focus();
			};

			//
			// detect mutation FileList
			this.mutationFileList = function(mutations, observer) {
				if (self.locked)
					self.updateSearchResult();
			};

			//
			// Go To Folder. Called on FileActions
			this.onGoToFolder = function(path, context) {
				var apath = path.split('/');

				var dir = '';
				var filename = '';
				for (var i = 0; i < apath.length; i++) {
					filename = apath[i];
					dir += '/';
					if (i < (apath.length - 1))
						dir += filename;
				}

				// window.alert('DIR: ' + dir + ' - FILENAME: ' + filename);

				var link = '';
				if (self.nextant_sharelink)
					link = '/s/' + self.getShareLinkKey()
							+ '?path={dir}&scrollto={scrollto}';

				if (link == '')
					link = '/apps/files/?dir={dir}&scrollto={scrollto}'

				window.location = OC.generateUrl(link, {
					dir : dir,
					scrollto : filename
				});

			};

			//
			// get TR elem from filelist
			this.getElem = function(file) {
				var list = $('#fileList').children('tr');
				for (var i = 0; i < list.length; i++) {
					if ($(list[i]).attr('data-file') == file)
						return $(list[i]);
				}

				return null;
			};

			//
			// get info from url
			this.get = function(name, url) {

				if (!url)
					url = window.location.href;
				name = name.replace(/[\[\]]/g, "\\$&");
				var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"), results = regex
						.exec(url);
				if (!results || !results[2])
					return '';

				return decodeURIComponent(results[2].replace(/\+/g, ' '));
			};

			this.getShareLinkKey = function() {
				if (!self.nextant_sharelink)
					return '';

				dir = window.location.href.split('/');
				key = dir[dir.length - 1];
				return key;
			};

			//
			// MORPH
			//

			//
			// fix the subfolder link
			//
			this.__morphLink = function(tr, item) {
				$(tr).attr('data-path', '/');
				var elemhref = $(tr).find('td.filename').find('a.name');
				if (!$(elemhref).length)
					return;

				if (self.nextant_sharelink) {
					var link = parent.location.protocol + '//' + location.host
							+ OC.generateUrl('/s/') + self.getShareLinkKey();
					link += '/download?path=' + item.entry.dirpath + '&files='
							+ item.entry.filename;
					$(elemhref).attr('href', link.replace(/%2F/g, '/'));
					// $(elemhref).attr('href',
					// $(elemhref).attr('href').replace(/%2F/g, '/'));
					return;
				}

				// if (item.data.type == 'files')
				// $(elemhref).attr('href',
				// $(elemhref).attr('href').replace(/%2F/g, '/'));

				// fix bookmark link
				if (item.data.type == 'bookmarks')
					$(elemhref).attr('href', item.data.path).attr('target',
							'_blank');
				else
					$(elemhref).attr('href',
							$(elemhref).attr('href').replace(/%2F/g, '/'));

			};

			//
			// fix the display and add few lines
			//
			this.__morphResultDisplay = function(tr, item) {
				var elemname = $(tr).find('td.filename').find('a.name').find(
						'span.nametext');

				if (item.data.lines == null)
					return;

				$(elemname).empty();
				var displaydiv = $('<span></span>');
				if (item.data.lines[1])
					displaydiv.append($('<span></span>').attr('class',
							'nextant_line nextant_line1').html(
							item.data.lines[1]));
				if (item.data.lines[2])
					displaydiv.append($('<span></span>').attr('class',
							'nextant_line nextant_line2').html(
							item.data.lines[2]));
				if (item.data.lines[3])
					displaydiv.append($('<span></span>').attr('class',
							'nextant_line nextant_line3').html(
							item.data.lines[3]));

				$(elemname).append(displaydiv);
				$(elemname).css('width', '800px');

			};

			//
			// Add overlay icon
			//
			this.__morphOverlayIcon = function(tr, item) {

				var elemicon = $(tr).find('td.filename').find('div.thumbnail');

				if (item.data.type == 'bookmarks')
					elemicon.append($('<div></div>').attr(
							{
								'class' : 'nextant_details',
								'style' : "background-image: url('"
										+ OC.imagePath('nextant',
												'bookmarks.svg') + "')"
							}));
				if (item.data.shared)
					elemicon.append($('<div></div>').attr(
							{
								'class' : 'nextant_details',
								'style' : "background-image: url('"
										+ OC.imagePath('core',
												'actions/shared.svg') + "')"
							}));
				if (item.data.deleted)
					elemicon.append($('<div></div>').attr(
							{
								'class' : 'nextant_details',
								'style' : "background-image: url('"
										+ OC.imagePath('core',
												'actions/delete.svg') + "')"
							}));
			};

			//
			// removing fileaction on bookmarks
			//
			this.__morphBookmarksFileAction = function(tr, item) {
				if (item.data.type != 'bookmarks')
					return;

				$(tr).find('.filename').on('click', function(e) {
					e.stopPropagation();
				});
				$(tr).find('.fileactions').empty().on('click', function(e) {
					e.stopPropagation();
				});
				$(tr).find('.filesize').empty().on('click', function(e) {
					e.stopPropagation();
				});
				$(tr).find('.date').on('click', function(e) {
					e.stopPropagation();
				});
			};

			//		
			// fix Summary
			//
			this.__morphSummary = function(files) {

				var cBookmarks = 0;
				for (var i = 0; i < files.length; i++) {
					if (files[i] == null)
						continue;
					if (files[i].data.type == 'bookmarks')
						cBookmarks++;
				}

				var elemsumm = $('tr.summary').find('span.info');
				if (!elemsumm.length)
					return;

				// First, fix the current cummary
				self.fileList.fileSummary.calculate(self.fileList.files);
				self.fileList.fileSummary.summary.totalFiles -= cBookmarks;
				self.fileList.fileSummary.update();

				// Then, add a summary for bookmarks
				elemsumm.find('span.bminfo').text(
						cBookmarks + ' bookmark'
								+ ((cBookmarks > 1) ? 's' : ''));

				if (cBookmarks == 0) {
					elemsumm.find('span.bminfo').addClass('hidden');
					elemsumm.find('span.bmconnector').addClass('hidden');
				} else {

					elemsumm.find('span.bminfo').removeClass('hidden');
					elemsumm.find('span.bmconnector').removeClass('hidden');

					if (files.length == cBookmarks) {
						$('tr.summary').removeClass('hidden');
						elemsumm.find('span.dirinfo').addClass('hidden');
						elemsumm.find('span.connector').addClass('hidden');
						elemsumm.find('span.fileinfo').addClass('hidden');
						elemsumm.find('span.bmconnector').addClass('hidden');

					} else if (elemsumm.find('span.fileinfo.hidden').length
							|| elemsumm.find('span.dirinfo.hidden').length) {
					} else
						elemsumm.find('span.connector').text(', ');
				}
			};

			//
			// fix empty result div
			//
			this.__morphEmptyContent = function(files) {
				if (files.length > 0)
					setTimeout(function() {
						$('#searchresults').find('div.emptycontent').addClass(
								'hidden')
					}, 200);
			};

			// register
			OC.Plugins.register('OCA.Search', this);
		},

		attach : function(search) {
			var self = this;

			// receiving search request in Files App
			search.setFilter('files', function(query) {
				self.sendSuggestRequest();
				self.delayedSearchRequest();
			});

			if (self.fileAppLoaded())
				self.nextant_sharelink = false;

			self.initShareLink();

			// init Search/FileList if needed
			self.initFileList();

			//
			// Add few elem Summary
			var elemsumm = $('tr.summary').find('span.info');
			elemsumm.find('span.fileinfo').after(function() {
				return $('<span></span>').attr('class', 'bminfo hidden');
			});
			elemsumm.find('span.fileinfo').after(
					function() {
						return $('<span></span>').attr('class',
								'bmconnector hidden').text(
								elemsumm.find('span.connector').text())
					});

			//
			// Stop Mutation on click
			$('#app-navigation').find('a').on('click', function(e) {
				// self.currQuery = '';
				self.fileList.setSort('name', 'asc', false, false);
			});

			//
			// Mutations
			MutationObserver = window.MutationObserver
					|| window.WebKitMutationObserver;

			var observerFileList = new MutationObserver(function(mutations,
					observer) {
				self.mutationFileList(mutations, observer);
			});

			observerFileList.observe($('#fileList')[0], {
				childList : true,
				attributes : true
			});

			// animate suggest popup
			$('#searchbox').focusout(function() {
				self.searchboxFocus = false;
				self.suggestDisplay();
			});

			$('#searchbox').focusin(function() {
				self.searchboxFocus = true;
				self.suggestDisplay();
			});

			$(window).resize(function() {
				self.suggestDisplay();
			});

		}
	};
	OCA.Search.Nextant = Nextant;
	OCA.Search.nextant = new Nextant();

	// Sort per score (also index if score is identical)
	OCA.Files.FileList.Comparators.score = function(f1, f2) {
		result = OCA.Search.nextant.searchResult;
		if (result == null)
			return;

		var s1 = 0;
		var s2 = 0;
		var i1 = 99;
		var i2 = 99;
		for (var i = 0; i < result.length; i++) {
			if (result[i] == null)
				continue;
			if (result[i].data.id == f1.id)
				i1 = i;
			if (result[i].data.id == f2.id)
				i2 = i;
			if (result[i].data.id == f1.id)
				s1 = result[i].data.score;
			if (result[i].data.id == f2.id)
				s2 = result[i].data.score;
		}

		if (s1 < s2)
			return -1;
		else if (s1 > s2)
			return 1;
		else {
			if (i1 > i2)
				return -1;
			else
				return 1;
		}
	};

})();
