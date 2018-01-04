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


/** global: OC */
/** global: next_settings */
/** global: result */
/** global: search */
/** global: nav */


var api = {


	search: function (request, callback) {
		var res = {status: -1};

		$.ajax({
			method: 'GET',
			url: OC.generateUrl('/apps/fullnextsearch/v1/search'),
			data: {
				request: JSON.stringify(request)
			}
		}).done(function (res) {
			result.displayResult(res);
			nav.onResultDisplayed(res);
			api.onCallback(callback, res);
		}).fail(function () {
			nav.failedToAjax();
			api.onCallback(callback, res);
		});
	},


	onCallback: function (callback, result) {
		if (callback && (typeof callback === 'function')) {
			if (typeof result === 'object') {
				callback(result);
			} else {
				callback({status: -1});
			}
		}
	}

};
