(function() {
	/**
	 * @namespace
	 */
	ObservationUpload_bathy = {

		metas: function(item, e, data) { /////
			urlGen = OC.generateUrl('/apps/snannyowncloudapi/data/' + item.id + '/info');
			var that = OCA.SnannyOwncloudAPI.ObservationUpload_bathy;
			$.ajax({
				type: 'GET',
				url: urlGen,
				dataType: 'json',
				success: function(response) {
					if (response.status === 'failure') {

						var baseURI = data.fileInput[0].baseURI;

						// Vérification que c'est un zip et vérification du contenu du zip
						var message_mail = "";
						if ((data.files[0].name.substr(-4)==".zip") && (data.files[0].type.match("application/zip") || data.files[0].type.match("application/x-zip-compressed"))){

							message_mail += data.files[0].name+" : Format OK\n";

							var url = '../../../apps/snannyowncloudapi/file_compressed/read_file_zip.php';
							$.post(url, 
								{ 
									username : oc_current_user,
									filename : data.files[0].name,
									baseURI : baseURI
								},
								function(data){
									var content = data;									
								}
							)
						}
						else{
							message_mail += data.files[0].name+" : Format non OK\n";
						}

						// Vérification du fichier existant sinon création d'un fichier contenant ce que le mail devrait avoir
						var url_rename = '../../../apps/snannyowncloudapi/bathymetrie/file_mail_content.php';
						$.post(url_rename,
							{
								message_mail : message_mail
							},
							function(data){
								alert(data);
							}
						)

						// Récupération de l'emplacement du fichier
						var i = 0;
						while((baseURI.substr(i,7)!="&fileid")){
							i++;
						}
						baseURI = baseURI.substr(0, i)+"Test/"+baseURI.substr(i-baseURI.length);

						// Déplacement du fichier pour le mettre dans DATARMOR (à venir)
						// Copie du Fichier
						var url_rename = '../../../apps/snannyowncloudapi/bathymetrie/rename_and_move_file.php';
						$.post(url_rename,
							{
								username : oc_current_user,
								filename : data.files[0].name,
								old_baseURI : data.fileInput[0].baseURI,
								new_baseURI : baseURI
							},
							function(data){
								alert("renommer et deplacer : "+data);
							}
						)


						// Envoi de Mail
						var url_mail = '../../../apps/snannyowncloudapi/bathymetrie/envoi_mail.php';
						$.post(url_mail,
							{
								username : oc_current_user,
								filename : data.files[0].name,
								message_mail : message_mail
							},
							function(data){
							}
						)
						
					}
				},
			});
		}

	};

	OCA.SnannyOwncloudAPI = OCA.SnannyOwncloudAPI || {};
	OCA.SnannyOwncloudAPI.ObservationUpload_bathy = ObservationUpload_bathy;
})();

// $(document).ready(function() {
// 	$('#file_upload_start').bind('fileuploaddone', OCA.SnannyOwncloudAPI.ObservationUpload_bathy.done);
// });