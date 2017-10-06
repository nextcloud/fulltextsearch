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

/** global: search */
/** global: api */

var settings = {

	parent: null,
	delay_provider: 600,
	delay_result: 300,
	resultContainer: null,
	entryTemplate: null,
	entryTemplateDefault: null,

	generateDefaultTemplate: function () {

		var tmpl = $('<div>', {
			class: 'result_entry_default'
		});

		var left = $('<div>', {class: 'result_entry_left'});
		left.append($('<div>', {id: 'title'}));

		var right = $('<div>', {class: 'result_entry_right'});
		right.append($('<div>', {id: 'score'}));

		tmpl.append(left);
		tmpl.append(right);

		settings.entryTemplateDefault = $('<div>').append(tmpl);
	},


	setEntryTemplateId: function (templateId, parent) {
		settings.entryTemplate = $('#' + templateId);
		settings.parent = parent;
	},

	setResultContainerId: function (containerId) {
		settings.resultContainer = $('#' + containerId);
	},

	parentHasMethod: function (method) {
		if (settings.parent === null) {
			return false;
		}
		return (typeof eval('settings.parent. ' + method) === "function");
	}
};
