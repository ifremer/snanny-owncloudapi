/**
 * 
 * Enable extensions of file upload
 *
 */
(function() {
	/**
	 * @namespace
	 */
	ObservationUpload = {

		/**
		 * Extend file upload done event
		 *
		 * @param e Event type
		 * @param data file uploaded with success
		 */
		done: function(e, data) {
			var result = JSON.parse(data.response().result);
			if (result[0] && result[0].status === 'success') {
				var name = result[0].name;
				var mimetype = result[0].mimetype;
				var check = false;
				if (mimetype === 'text/csv' && name.endsWith('.csv')) {
					OCA.SnannyOwncloudAPI.ObservationUpload.metas(result[0]);
				} else if (mimetype === 'application\/octet-stream' && (name.endsWith('.nc') || name.endsWith('.nav'))) {
					OCA.SnannyOwncloudAPI.ObservationUpload.metas(result[0]);
				}
			}

		},

		metas: function(item) {
			urlGen = OC.generateUrl('/apps/snannyowncloudapi/data/' + item.id + '/info');
			$.ajax({
				type: 'GET',
				url: urlGen,
				dataType: 'json',
				success: function(response) {
					if (response.status === 'failure') {
						OCA.SnannyOwncloudAPI.ObservationUpload.prompt(item);
					}
				},
			});
		},

		prompt: function(item) {
			$.when(OCA.TemplateUtil.getTemplate('snannyowncloudapi', 'om_descriptor.html')).then(function($tmpl) {
				var dialogName = 'oc-dialog-' + OCdialogs.dialogsCounter + '-content';
				var dialogId = '#' + dialogName;
				var $dlg = $tmpl.octemplate({
					dialog_name: dialogName,
					filename: item.name,
					icon: item.icon
				});

				var functionToCall = function() {
					var dialog = $(dialogId);
					var form = [dialog.find('#observationName'), dialog.find('#observationDesc'), dialog.find('#observationSystem')];
					if (OCA.SnannyOwncloudAPI.ObservationUpload._validate(form)) {
						var _data = OCA.TemplateUtil.extractData(form);
						OCA.SnannyOwncloudAPI.ObservationUpload._send(item.id, _data, function(result) {
							console.log(result);
							//if(result.status === 'success'){
							$(dialogId).ocdialog('close');
							$(dialogId).remove();
							//}else{

							//}
						});
					}
				};

				var buttonlist = [];
				buttonlist[0] = {
					text: t('core', 'Ok'),
					click: functionToCall,
					defaultButton: true
				}

				$('body').append($dlg);
				$(dialogId).ocdialog({
					closeOnEscape: true,
					modal: true,
					buttons: buttonlist
				});

				var titleSpan = $(dialogId).parent().children('.oc-dialog-title');
				titleSpan.text(n('snannyowncloudapi',
					'No observation model found for file {file}',
					'No observation model found for file {file}',
					1, {
						file: item.name
					}
				));

				OCdialogs.dialogsCounter++;

			});
		},

		_send: function(id, data, callback) {
			urlGen = OC.generateUrl('/apps/snannyowncloudapi/data/' + id);
			$.ajax({
				type: 'POST',
				url: urlGen,
				dataType: 'json',
				data: data,
				success: callback,
			});
		},

		_validate: function(inputs) {
			var result = true;
			$.each(inputs, function(idx, entry) {
				if (entry.val() === '') {
					OCA.TemplateUtil.displayError(entry, 'Required field');
					result = false;
				}
			});
			return result;
		}

	};

	OCA.SnannyOwncloudAPI = OCA.SnannyOwncloudAPI || {};
	OCA.SnannyOwncloudAPI.ObservationUpload = ObservationUpload;
})();

$(document).ready(function() {
	$('#file_upload_start').bind('fileuploaddone', OCA.SnannyOwncloudAPI.ObservationUpload.done);
});