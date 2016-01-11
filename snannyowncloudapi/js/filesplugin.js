/**
 * ownCloud - snannyowncloudapi
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Arnaud THOREL <athorel@asi.fr>
 * @copyright Arnaud THOREL 2015
 */

(function() {
	OCA.SnannyOwncloudAPI = OCA.SnannyOwncloudAPI || {};

	/**
	 * @namespace
	 */
	OCA.SnannyOwncloudAPI.Util = {
		/**
		 * Initialize the versions plugin.
		 *
		 * @param {OCA.Files.FileList} fileList file list to be extended
		 */
		attach: function(fileList) {
			if (fileList.id === 'trashbin' || fileList.id === 'files.public') {
				return;
			}
			fileList.registerTabView(new OCA.SnannyOwncloudAPI.ObservationTabView('observationTabView', {order: -10}));
		}
	};
})();

OC.Plugins.register('OCA.Files.FileList', OCA.SnannyOwncloudAPI.Util);