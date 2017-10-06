/*
 * FullNextSearch - Full Text Search your Nextcloud.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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
 *
 */

/** global: OCA */
/** global: Notyf */
/** global: settings */
/** global: nav */
/** global: api */


(function () {

	/**
	 * @constructs NextSearch
	 */
	var NextSearch = function () {
		$.extend(NextSearch.prototype, settings);
		$.extend(NextSearch.prototype, nav);
		$.extend(NextSearch.prototype, api);

		settings.generateDefaultTemplate();
	};


	/**
	 * @constructs Notification
	 */
	var Notification = function () {
		this.initialize();
	};

	Notification.prototype = {

		initialize: function () {

			var notyf = new Notyf({
				delay: 5000
			});

			this.onSuccess = function (text) {
				notyf.confirm(text);
			};

			this.onFail = function (text) {
				notyf.alert(text);
			};

		}

	};

	OCA.NextSearch = NextSearch;
	OCA.NextSearch.api = new NextSearch();

	OCA.Notification = Notification;
	OCA.notification = new Notification();


})();
