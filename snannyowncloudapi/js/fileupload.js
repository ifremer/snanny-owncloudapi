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
		/**Request */
		_request: undefined,

		_prompt : [],

		_currentDialog : undefined,
		/**
		 * Extend file upload done event
		 *
		 * @param e Event type
		 * @param data file uploaded with success
		 */
		done: function(e, data) {
			//When all done 
			var result = JSON.parse(data.response().result);
			if (result[0] && result[0].status === 'success') {
				var name = result[0].name;
				var check = false;
				if (name.endsWith('.csv')) {
					OCA.SnannyOwncloudAPI.ObservationUpload.metas(result[0]);
				} else if (name.endsWith('.nc') || name.endsWith('.nav')) {
					OCA.SnannyOwncloudAPI.ObservationUpload.metas(result[0]);
				}
			}
		},

		metas: function(item) {
			urlGen = OC.generateUrl('/apps/snannyowncloudapi/data/' + item.id + '/info');
			var that = OCA.SnannyOwncloudAPI.ObservationUpload;
			$.ajax({
				type: 'GET',
				url: urlGen,
				dataType: 'json',
				success: function(response) {
					if (response.status === 'failure') {
						that.prompt(item);

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
					if (OCA.SnannyOwncloudAPI.ObservationUpload._validate(form, dialogId)) {
						var _data = OCA.TemplateUtil.extractData(dialog);
						OCA.SnannyOwncloudAPI.ObservationUpload._send(item.id, _data, function(result) {
							$(dialogId).ocdialog('close');
							FileList.add(result, {});
							FileList.highlightFiles([result.name]);
						});
					}
				};
				var that = OCA.SnannyOwncloudAPI.ObservationUpload;
				var dialogItem = {'id':dialogId, 'tmpl':$dlg, 'callback':functionToCall, 'item':item};
				if (that._currentDialog) {
					that._prompt.push(dialogItem);
				} else {
					that._showDialog(dialogItem);
				}
				OCdialogs.dialogsCounter++;
			});
		},

		_showDialog: function(dialog){
			var dialogId = dialog.id;
			this._currentDialog = dialogId;
			var buttonlist = [];
			buttonlist[0] = {
				text: t('core', 'Ok'),
				click: dialog.callback,
				defaultButton: true
			};

			$('body').append(dialog.tmpl);
			$(dialogId).ocdialog({
				closeOnEscape: true,
				modal: true,
				buttons: buttonlist,
				close:function(){
					$(dialogId).remove();
					OCA.SnannyOwncloudAPI.ObservationUpload._showNext();
				}
			});

			var titleSpan = $(dialogId).parent().children('.oc-dialog-title');
			titleSpan.text(n('snannyowncloudapi',
				'No observation model found for file {file}',
				'No observation model found for file {file}',
				1, {
					file: dialog.item.name
				}
			));

			var onChangeOrSelect = function (event, ui) {

			    	var selected = ui.item;
			    	if(selected){
			    		$('#system').val(selected.uuid);
						$("#searchNotFound").toggleClass("hidden", true);
			   		}else{
			   			$('#system').val('');
						$("#searchNotFound").toggleClass("hidden", true);
			   		}
			    };


			$(dialogId).find("#observationSystem").autocomplete({
			    serviceUrl:  OC.generateUrl('/apps/snannyowncloudapi/systems'),
			    change: onChangeOrSelect,
			    select: onChangeOrSelect,
			    source:function(request, response){
			    	var searchParam  = {'term':request.term};
					$.ajax({
				        url: OC.generateUrl('/apps/snannyowncloudapi/sml'),
						delay: 300,
				        data : searchParam,
				        dataType: "json",
				        type: "POST",
				        success: function (data) {
				        	if(data.length>0){
				        		$("#searchNotFound").toggleClass("hidden", true);
					            response($.map(data, function(item) {
					                return { 
					                    label: item.label,
					                    uuid: item.uuid
					                 };
					            }));
				        	}else{
								$("#searchNotFound").toggleClass("hidden", false);
								response(null);
				        	}
				        }
				    });
				}
			});
		},

		_showNext: function(){
			var value = this._prompt.pop();
			if(value){
				this._showDialog(value);
			}else{
				this._currentDialog = undefined;
			}
		},

		_send: function(id, data, callback) {
			urlGen = OC.generateUrl('/apps/snannyowncloudapi/data/' + id);
			$.ajax({
				type: 'POST',
				url: urlGen,
				dataType: 'json',
				data: data,
				success: callback,
				error : function(response){
					$(dialogId).find('errorMsg').html("unable to create O&M metadata")
				}
			});
		},

		_validate: function(inputs, dialogId) {
			var result = true;
			$.each(inputs, function(idx, entry) {
				if (entry.val() === '') {
					OCA.TemplateUtil.displayError(entry, 'Required field');
					result = false;
				}
			});
			if($(dialogId).find("#system").val() === ''){
				OCA.TemplateUtil.displayError($("#observationSystem"), 'Required field');
				result = false;
			}
			return result;
		}

	};

	OCA.SnannyOwncloudAPI = OCA.SnannyOwncloudAPI || {};
	OCA.SnannyOwncloudAPI.ObservationUpload = ObservationUpload;
})();

$(document).ready(function() {
	$('#file_upload_start').bind('fileuploaddone', OCA.SnannyOwncloudAPI.ObservationUpload.done);
});