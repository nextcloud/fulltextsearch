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

/*******************************************************************************
 * This script is now Useless - It is not even loaded.
 ******************************************************************************/
$(document).ready(
	function() {

	    var nextantCurrentSearch = '';
	    var nextant = {
		init : function() {
		    console.log('nextant/init');
		    $('#searchbox').bind('input', function() {
			setTimeout(function() {
			    nextant.search($('#searchbox').val());
			}, 1000);
		    });
		},

		search : function(string) {

		    if (string == nextantCurrentSearch)
			return;
		    nextantCurrentSearch = string;

		    request = {
			search : string
		    }

		    console.log('nextant/search: ' + request.search);
		    nextant.searchRequest(request);
		},

		searchRequest : function(request) {
		    console.log('nextant/searchRequest: ' + request.search)
		    $.post(OC.filePath('nextant', 'ajax', 'search.php'),
			    request, nextant.searchResult);
		},

		searchResult : function(response) {
		    console.log('nextant/searchResult: ' + response.message
			    + ' size: ' + response.data.length);

		    // OC.search.resultTypes.lucene = t('search_lucene', 'In');

		    // OC.search.customResults.lucene = function ($row, item){
		    // $row.find('td.result .text').text(t('search_lucene',
		    // 'Score: {score}', {score:
		    // Math.round(item.score*100)/100}));
		}
	    };

	    nextant.init();
	});
