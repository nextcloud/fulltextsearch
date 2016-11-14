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
		searchResult : [],
		locked : false,

		/**
		 * Initialize the file search
		 */
		initialize : function() {

			var self = this;

			// detect if Files App is loaded
			this.fileAppLoaded = function() {
				return !!OCA.Files && !!OCA.Files.App;
			};

			this.initFileList = function() {
				_.each(OC.Plugins.getPlugins('OCA.Search'), function(plugin) {
					if (plugin instanceof OCA.Search.Files)
						self.fileList = plugin.fileList;
				});

				if (!self.fileAppLoaded())
					return;

				/**
				 * Haven't found a way to include (or remove) a fileAction only
				 * in a specific place (only in search result)
				 */
				OCA.Files.fileActions.registerAction({
					name : 'nextant_gotofolder',
					displayName : 'Go To Folder',
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
			}

			// search request
			this.searchRequest = function(data) {
				$.post(OC.filePath('nextant', 'ajax', 'search.php'), data,
						self.searchRequestResult);
			};

			/**
			 * 
			 * display search result
			 * 
			 */
			this.searchRequestResult = function(result) {

				if (self.fileList == null)
					return;

				self.searchResult = result;

				var data = [];
				for (var i = 0; i < result.length; i++) {
					if (result[i].entry == null)
						continue;

					result[i].entry.score = 3;
					data.push(result[i].entry);
				}

				self.fileList.setSort('score', 'desc', false, false);
				self.fileList.setFiles(data);

				self.updateSearchResult();
			};

			this.updateSearchResult = function() {
				self.locked = false;

				var result = self.searchResult;

				// We edit each row
				_.each(result, function(item) {
					if (item.entry == null)
						return;

					tr = self.getElem(item.entry.name);

					if (!$(tr).length)
						return;

					// fix the deep-folder link
					var elemhref = $(tr).find('td.filename').find('a.name');
					if ($(elemhref).length)
						$(elemhref).attr('href',
								$(elemhref).attr('href').replace(/%2F/g, '/'));

					// fix the display and add few lines
					var elemname = $(tr).find('td.filename').find('a.name')
							.find('span.nametext');
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

					$(elemname).append(displaydiv);
					$(elemname).css('width', '800px');

					// Add overlay icon
					var elemicon = $(tr).find('td.filename').find(
							'div.thumbnail');

					if (item.data.shared)
						elemicon.append($('<div></div>').attr(
								{
									'class' : 'nextant_details',
									'style' : "background-image: url('"
											+ OC.imagePath('core',
													'actions/shared.svg')
											+ "')"
								}));
					if (item.data.deleted)
						elemicon.append($('<div></div>').attr(
								{
									'class' : 'nextant_details',
									'style' : "background-image: url('"
											+ OC.imagePath('core',
													'actions/delete.svg')
											+ "')"
								}));

					//
					// We're done modifying the row
				});

				self.locked = true;
			};

			this.mutationFileList = function(mutations, observer) {
				if (self.locked)
					self.updateSearchResult();
			};

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

				window.location = OC.generateUrl(
						'/apps/files/?dir={dir}&scrollto={scrollto}', {
							dir : dir,
							scrollto : filename
						})

				// window.alert('DIR: ' + dir + ' - FILENAME: ' + filename);
			};

			// get TR elem from filelist
			this.getElem = function(file) {
				var list = $('#fileList').children('tr');
				for (var i = 0; i < list.length; i++) {
					if ($(list[i]).attr('data-file') == file)
						return $(list[i]);
				}

				return null;
			};

			// get info from url
			this.get = function(name, url) {

				if (!url)
					url = window.location.href;
				name = name.replace(/[\[\]]/g, "\\$&");
				var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"), results = regex
						.exec(url);
				if (!results)
					return null;
				if (!results[2])
					return '';
				return decodeURIComponent(results[2].replace(/\+/g, ' '));
			};

			OC.Plugins.register('OCA.Search', this);
		},

		attach : function(search) {
			var self = this;

			// receiving search request in Files App
			search.setFilter('files', function(query) {
				if (self.fileAppLoaded()) {

					// init Search/FileList if needed
					if (self.fileList == null)
						self.initFileList();

					// sending the ajax request
					var data = {
						query : query,
						current_dir : self.get('dir')
					}

					self.searchRequest(data);
				}
			});

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

		}
	};
	OCA.Search.Nextant = Nextant;
	OCA.Search.nextant = new Nextant();

	// Sort per score (also index if score is identical)
	OCA.Files.FileList.Comparators.score = function(f1, f2) {
		result = OCA.Search.nextant.searchResult;

		var s1 = 0;
		var s2 = 0;
		var i1 = 99;
		var i2 = 99;
		for (var i = 0; i < result.length; i++) {

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
