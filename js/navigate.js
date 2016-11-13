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

		/**
		 * Initialize the file search
		 */
		initialize : function() {

			var self = this;

			// detect if Files App is loaded
			this.fileAppLoaded = function() {
				return !!OCA.Files && !!OCA.Files.App;
			};

			// search request
			this.searchRequest = function(data) {
				$.post(OC.filePath('nextant', 'ajax', 'search.php'), data,
						self.searchResult);
			};

			// display search result
			this.searchResult = function(result) {

				if (self.fileList == null)
					return;

				var data = [];
				_.each(result, function(item) {
					data.push(item.entry);
				});

				try {
					self.fileList.setFiles(data);
				} catch (e) {
				}

				// We edit each row
				_.each(result, function(item) {
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
					if (self.fileList == null) {
						_.each(OC.Plugins.getPlugins('OCA.Search'), function(
								plugin) {
							if (plugin instanceof OCA.Search.Files)
								self.fileList = plugin.fileList;
						});
					}

					// sending the ajax request
					var data = {
						query : query,
						current_dir : self.get('dir')
					}

					self.searchRequest(data);
				}
			});

		}
	};
	OCA.Search.Nextant = Nextant;
	OCA.Search.nextant = new Nextant();
})();
