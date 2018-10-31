/*
 * FullTextSearch - Full text search framework for Nextcloud
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2018
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
 */


/** global: OC */
/** global: settings */
/** global: result */
/** global: search */
/** global: nav */


var api = {


	search: function (request, callback, callbackError) {
		var res = {
			status: -1,
			error: 'Failed to reach server. Try reloading the page'
		};

		nav.onSearchRequest(request);

		$.ajax({
			method: 'GET',
			url: OC.generateUrl('/apps/fulltextsearch/v1/search'),
			data: {
				request: JSON.stringify(request)
			}
		}).done(function (res) {
			if (res.status === -1) {
				result.displayError(res);
				nav.onError(res.message);
				api.onCallback(callbackError, res);
				return;
			}

			result.displayResult(res);
			nav.onResultDisplayed(res);
			api.onCallback(callback, res);
		}).fail(function () {
			if (!api.onCallback(callbackError, res)) {
				nav.onError(res.message);
			}
		});
	},


	retrieveOptions: function (providerId, callback) {
		var res = {status: -1};

		$.ajax({
			method: 'GET',
			url: OC.generateUrl('/apps/fulltextsearch/options/' + providerId)
		}).done(function (res) {
			searchbox.onOptionsLoaded(res);
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

			return true;
		}

		return false;
	}

};
