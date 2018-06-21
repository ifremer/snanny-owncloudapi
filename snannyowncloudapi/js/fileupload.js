/**
 * 
 * Enable extensions of file upload
 *
 */
(function() {

	var extensions = ['.csv','.txt','.xyz','.nc','.nav','.zip','.gz','.bz2'];
	function isFilenameAcceptable(name){
		return extensions.some(function(extension){
	    	return name.endsWith(extension);
		});
	}
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

			var groupe_test = ["groupe_marinet","groupe_bathy","groupe_chimie"];

			if (result[0] && result[0].status === 'success') {
				var name = result[0].name;
				// Permet de savoir à quel groupe appartient l'utilisateur
				$.ajax({
					type: 'GET',
					// url: 'http://rbalanch:stargate3992@localhost/owncloud/ocs/v1.php/cloud/users/'+oc_current_user+'/groups',
					url: 'http://admin:12345678@visi-snanny-datacloud.ifremer.fr/owncloud/ocs/v1.php/cloud/users/'+oc_current_user+'/groups',
					success: function(response) {
						// $.getScript("../../../apps/snannyowncloudapi/js/xml_to_json.js")
						// .done(function( script, textStatus ) {
							var groups_list = xmlToJson(response);
							if(Array.isArray(groups_list.ocs.data.groups.element)){ // si c'est un tableau
								if(confirm("voulez-vous upload ce fichier en tant que membre du groupe : "+groupe_test[0]+" ?")){
									for(var i = 0;typeof groups_list.ocs.data.groups.element[i] != 'undefined';i++){
										if(groups_list.ocs.data.groups.element[i] == groupe_test[0]){ // Marinet
											if (isFilenameAcceptable(name)) {
												// $.getScript("../../../apps/snannyowncloudapi/js/fileupload_marinet.js")
												// .done(function( script, textStatus ) {
													OCA.SnannyOwncloudAPI.ObservationUpload_marinet.metas(result[0], e, data);
												// });
											}
										}
									}
								}
								else if(confirm("voulez-vous upload ce fichier en tant que membre du groupe : "+groupe_test[1]+" ?")){
									for(var i = 0;typeof groups_list.ocs.data.groups.element[i] != 'undefined';i++){
										if(groups_list.ocs.data.groups.element[i] == groupe_test[1]){ // Bathy
											if (isFilenameAcceptable(name)) {
												// $.getScript("../../../apps/snannyowncloudapi/js/fileupload_marinet.js")
												// .done(function( script, textStatus ) {
													OCA.SnannyOwncloudAPI.ObservationUpload_bathy.metas(result[0], e, data);
												// });
											}
										}
									}
								}
								else if(confirm("voulez-vous upload ce fichier en tant que membre du groupe : "+groupe_test[2]+" ?")){
									for(var i = 0;typeof groups_list.ocs.data.groups.element[i] != 'undefined';i++){
										if(groups_list.ocs.data.groups.element[i] == groupe_test[2]){ // Chimie
											if (isFilenameAcceptable(name)) {
												// $.getScript("../../../apps/snannyowncloudapi/js/fileupload_chimie.js")
												// .done(function( script, textStatus ) {
													OCA.SnannyOwncloudAPI.ObservationUpload_chimie.metas(result[0], e, data);
												// });
											}
										}
									}
								}
							}
							else{ // sinon c'est pas un tableau							
								if(groups_list.ocs.data.groups.element == groupe_test[0]){ // Marinet
									if (isFilenameAcceptable(name)) {
										// $.getScript("../../../apps/snannyowncloudapi/js/fileupload_marinet.js")
										// .done(function( script, textStatus ) {
											OCA.SnannyOwncloudAPI.ObservationUpload_marinet.metas(result[0], e, data);
										// });
									}
								}
								else if(groups_list.ocs.data.groups.element == groupe_test[1]){ // Bathy
									if (isFilenameAcceptable(name)) {
										// $.getScript("../../../apps/snannyowncloudapi/js/fileupload_bathy.js")
										// .done(function( script, textStatus ) {
											OCA.SnannyOwncloudAPI.ObservationUpload_bathy.metas(result[0], e, data);
										// });
									}
								}
								else if(groups_list.ocs.data.groups.element == groupe_test[2]){ // Chimie
									if (isFilenameAcceptable(name)) {
										// $.getScript("../../../apps/snannyowncloudapi/js/fileupload_chimie.js")
										// .done(function( script, textStatus ) {
											OCA.SnannyOwncloudAPI.ObservationUpload_chimie.metas(result[0], e, data);
										// });
									}
								}								
								else{
									// $.getScript("../../../apps/snannyowncloudapi/js/fileupload_original.js")
									// .done(function( script, textStatus ) {
										OCA.SnannyOwncloudAPI.ObservationUpload_original.metas(result[0], e, data); // SensorNanny
									// });
								}
							}
						// })
						// .fail(function(jqxhr, settings, exception){
						// 	alert("Le fichier xml_to_json.js n'a pas été trouvé");
						// });
					}
				});
			}
		}
	};
	OCA.SnannyOwncloudAPI = OCA.SnannyOwncloudAPI || {};
	OCA.SnannyOwncloudAPI.ObservationUpload = ObservationUpload;
})();

$(document).ready(function() {
	$('#file_upload_start').bind('fileuploaddone', OCA.SnannyOwncloudAPI.ObservationUpload.done);
});