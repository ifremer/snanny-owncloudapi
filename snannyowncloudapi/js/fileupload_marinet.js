(function() {
	/**
	 * @namespace
	 */
	ObservationUpload_marinet = {

		metas: function(item, e, data) {
			urlGen = OC.generateUrl('/apps/snannyowncloudapi/data/' + item.id + '/info');
			var that = OCA.SnannyOwncloudAPI.ObservationUpload_marinet;
			$.ajax({
				type: 'GET',
				url: urlGen,
				dataType: 'json',
				success: function(response) {
					if (response.status === 'failure') {
						that.prompt(item, e, data);
					}
				},
			});
		},

		prompt: function(item, e, data) {
			$.when(OCA.TemplateUtil.getTemplate('snannyowncloudapi', 'om_descriptor_marinet.html')).then(function($tmpl) {
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
					
					//////////////////////////////////////////////////////////////////
					///// permet de récupérer la liste des séparateur du fichier /////
					//////////////////////////////////////////////////////////////////
					var nb_checked = 0;
                    for(var i = 0; i<document.getElementsByName("checkbox").length;i++){
                        if(document.getElementsByName("checkbox")[i].checked){
                            nb_checked++;
                        }
                    }
                    var separators = new Array(nb_checked);
                    nb_checked = 0;
                    for(var i = 0; i<5;i++){
                        if(document.getElementsByName("checkbox")[i].checked){
                            separators[nb_checked]=document.getElementsByName("checkbox")[i].value;
                            nb_checked++;
                        }
					}
					//////////////////////////////////////////////////////////////////
					///////// Permet de récupérer le titre de chaque colonne /////////
					//////////////////////////////////////////////////////////////////
					if(typeof $('#preview_table')[0].children[1] != 'undefined'){
						var title_table = new Array();
						for(var i = 0;i<$('#preview_table')[0].children[1].children[0].cells.length;i++){
							title_table[i] = $('#preview_table')[0].children[1].children[0].cells[i].innerText;
						}

					}
					//////////////////////////////////////////////////////////////////
					
					if (OCA.SnannyOwncloudAPI.ObservationUpload_marinet._validate(form, dialogId)) {
						var _data = OCA.TemplateUtil.extractData(dialog);
						OCA.SnannyOwncloudAPI.ObservationUpload_marinet._send(item.id, _data, function(result) {
							$(dialogId).ocdialog('close');
							FileList.add(result, {});
							FileList.highlightFiles([result.name]);
						});
					}
				};
				var that = OCA.SnannyOwncloudAPI.ObservationUpload_marinet;
				var dialogItem = {'id':dialogId, 'tmpl':$dlg, 'callback':functionToCall, 'item':item};
				if (that._currentDialog) {
					that._prompt.push(dialogItem);
				} else {
					that._showDialog(dialogItem, e, data);
				}
				OCdialogs.dialogsCounter++;
			});
		},

		_showDialog: function(dialog, e, data){
			var dialogId = dialog.id;
			this._currentDialog = dialogId;
			var buttonlist = [];
			buttonlist[0] = { // Bouton page précédente
				text: t('core', 'Precedent'),
				click: function(){

					// permet de savoir à quelle page on est
					if(document.getElementById('id_page').innerText == "3"){
						document.getElementById('id_page').innerText = "2";
					}
					else if(document.getElementById('id_page').innerText == "2"){
						document.getElementById('id_page').innerText = "1";
					}

					// permet d'appliquer des styles differents suivant la page qu'on a
					switch(document.getElementById('id_page').innerText){
						case "1" :
							document.getElementById('id_informations').style.display="initial";
							document.getElementById('id_delimiters').style.display="none";
							document.getElementById('id_entete_etape3').style.display="none";
							document.getElementById('id_table').style.display="none";
							document.getElementById('id_box_parameters').style.display="none";
							var tab_buttonlist = document.getElementsByClassName('oc-dialog-buttonrow');
							tab_buttonlist[0].children[0].style.backgroundColor = 'darkgray';
							tab_buttonlist[0].children[0].style.cursor = 'default';
							tab_buttonlist[0].children[1].style.backgroundColor = '#1d2d44';
							tab_buttonlist[0].children[1].style.cursor = 'pointer';
							tab_buttonlist[0].children[2].style.backgroundColor = '#1d2d44';
							break;
						case "2" :
							var confirmation = confirm("Vous risquez de perdre votre progression, voulez-vous continuez ?");
							if(confirmation){
							document.getElementById('id_informations').style.display="none";
							document.getElementById('id_delimiters').style.display="initial";
							document.getElementById('id_entete_etape3').style.display="none";
							document.getElementById('id_table').style.display="initial";
							document.getElementById('id_box_parameters').style.display="none";
							var tab_buttonlist = document.getElementsByClassName('oc-dialog-buttonrow');
							tab_buttonlist[0].children[0].style.backgroundColor = '#1d2d44';
							tab_buttonlist[0].children[0].style.cursor = 'pointer';
							tab_buttonlist[0].children[1].style.backgroundColor = '#1d2d44';
							tab_buttonlist[0].children[1].style.cursor = 'pointer';
							tab_buttonlist[0].children[2].style.backgroundColor = '#1d2d44';
							document.getElementById("button_delete_line").style.display="none";
							document.getElementById("button_new_line").style.display="initial";
							}
							break;
						default :
							break;
					}
				},
				//defaultButton: true
			};
			buttonlist[1] = { // Bouton page suivante
				text: t('core', 'Suivant'),
				click: function(){
					
					// Permet de savoir à quelle page on est
					if(document.getElementById('id_page').innerText == "1"){
						document.getElementById('id_page').innerText = "2";
					}
					else if(document.getElementById('id_page').innerText == "2"){
						document.getElementById('id_page').innerText = "3";
					}
					
					// Permet d'appliquer des styles differents suivant la page qu'on a
					switch(document.getElementById('id_page').innerText){						
						case "2" :
							document.getElementById('id_informations').style.display="none";
							document.getElementById('id_delimiters').style.display="initial";
							document.getElementById('id_entete_etape3').style.display="none";
							document.getElementById('id_table').style.display="initial";
							document.getElementById('id_box_parameters').style.display="none";
							var tab_buttonlist = document.getElementsByClassName('oc-dialog-buttonrow');
							tab_buttonlist[0].children[0].style.backgroundColor = '#1d2d44';
							tab_buttonlist[0].children[0].style.color = 'white';
							tab_buttonlist[0].children[0].style.cursor = 'pointer';
							tab_buttonlist[0].children[1].style.backgroundColor = '#1d2d44';
							tab_buttonlist[0].children[1].style.color = 'white';
							tab_buttonlist[0].children[1].style.cursor = 'pointer';
							tab_buttonlist[0].children[2].style.backgroundColor = '#1d2d44';
							tab_buttonlist[0].children[2].style.color = 'white';


							var possibleDelimiters = new Array(2);
							document.getElementsByName("checkbox")[0].value = "\t";
							possibleDelimiters[0] = [document.getElementsByName("checkbox")[0].value, document.getElementsByName("checkbox")[1].value,document.getElementsByName("checkbox")[2].value,document.getElementsByName("checkbox")[3].value];
							possibleDelimiters[1] = [true, true, true, true];
							for(var i=0; i<possibleDelimiters[0].length;i++){
								var contentfile = document.getElementById('fileDisplayArea').value;
								var nb_ligne_contentfile = contentfile.trim().split("\n").length; // on regarde le nombre de ligne
								var tableau_contentfile = new Array(nb_ligne_contentfile); // tableau qui contient chacune de nos lignes
								var tableau_contentfile_separators = new Array(nb_ligne_contentfile);
								for(var a=0;a<nb_ligne_contentfile;a++){
									tableau_contentfile_separators[a] = new Array();
								}
								var tableau_length_line = new Array(nb_ligne_contentfile);
								var delimiter = possibleDelimiters[0][i];
								for (var j = 0; j < nb_ligne_contentfile; j++){
									tableau_contentfile[j] = contentfile.substring(0, contentfile.indexOf("\n",0));
									contentfile = contentfile.replace(tableau_contentfile[j]+"\n", "");
									tableau_contentfile_separators[j] = tableau_contentfile[j].split(delimiter);
									tableau_length_line[j] = tableau_contentfile_separators[j].length;
									if(tableau_length_line[j]!=tableau_length_line[0] || tableau_contentfile[j].split(delimiter).length == 1){
										possibleDelimiters[1][i] = false;
									}
								}
							}

							for(var i=0; i<possibleDelimiters[0].length;i++){
								if(possibleDelimiters[1][i] == true){
									document.getElementsByName("checkbox")[i].checked = true;
									calcul_separator();
								}
							}

							break;
						case "3" :

							var nb_checked = 0;
							for(var i = 0; i<5;i++){
								if(document.getElementsByName("checkbox")[i].checked){
									nb_checked++; // on regarde le nombre de case(s) cochée(s) pour créer notre tableau à la bonne dimension
								}
							}
							if(nb_checked==0){
								alert("Veuillez saisir un séparateur");
							}
							else{
								document.getElementById('id_informations').style.display="none";
								document.getElementById('id_delimiters').style.display="none";
								document.getElementById('id_entete_etape3').style.display="inherit";
								document.getElementById('id_table').style.display="initial";
								document.getElementById('id_box_parameters').style.display="initial";
								var tab_buttonlist = document.getElementsByClassName('oc-dialog-buttonrow');
								tab_buttonlist[0].children[0].style.backgroundColor = '#1d2d44';
								tab_buttonlist[0].children[0].style.cursor = 'pointer';
								tab_buttonlist[0].children[1].style.backgroundColor = 'darkgray';
								tab_buttonlist[0].children[1].style.cursor = 'default';
								tab_buttonlist[0].children[2].style.backgroundColor = '#1d2d44';
							}
							break;
						default :
							break;
					}

				},
				defaultButton: true
			};
			buttonlist[2] = {
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
					OCA.SnannyOwncloudAPI.ObservationUpload_marinet._showNext();
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
			
			document.getElementById('id_page').innerText = "1";
			buttonlist[0].click();

			////////////////////////////////////////////////////////////////////////
			//// permet :                                                       ////
			////          de recuperer le contenu du fichier,                   ////
			////          recuperer la liste des seraparateurs                  ////
			////          et de découper le contenu en fonction des separateurs ////
			////////////////////////////////////////////////////////////////////////
			document.getElementById("checkbox_tab").addEventListener("change", function(){ calcul_separator();});
			document.getElementById("checkbox_semi_column").addEventListener("change", function(){ calcul_separator();});
			document.getElementById("checkbox_space").addEventListener("change", function(){ calcul_separator();});
			document.getElementById("checkbox_comma").addEventListener("change", function(){ calcul_separator();});
			document.getElementById("checkbox_other").addEventListener("change", function(){calcul_separator();});

			document.getElementById("value_other").addEventListener("keyup", function(){
				document.getElementById("checkbox_other").value=document.getElementById("value_other").value;
				if(document.getElementById("value_other").value == ""){document.getElementById("checkbox_other").checked = false;}
				else{document.getElementById("checkbox_other").checked = true;}
			});

			//read_file();

			// fonction permettant de recuperer les 5 premieres lignes du fichier et de les mettre dans fileDisplayArea
			//function read_file(){
				
				var fileInput = data.files[0]; //recupere le fichier
				var baseURI = e.target.baseURI;
			
				// Si c'est un .tar.bz2 (mais pas avec un trop gros volume)
				if( fileInput.name.substr(-8)==".tar.bz2" && (fileInput.type=="application/x-bzip" || fileInput.type=="application/x-bzip2") ){
					var url = '../../../apps/snannyowncloudapi/file_compressed/read_file_tarbz2.php';
					$.post(url,
						{
							username : oc_current_user,
							filename : fileInput.name,
							baseURI : baseURI
						},
						function(data){
							// document.getElementById("fileDisplayArea").value = data;
							var content = data;
							// var start = 0;
							// if((content.length - 1)>2000000){
							// 	var stop = 2000000;
							// }else{
							// 	var stop = content.length - 1;
							// }
							document.getElementById("fileDisplayArea").style.display="initial";
							var fileDisplayArea = document.getElementById('fileDisplayArea'); // Recupere l'emplacement où va s'afficher le contenu du fichier
							var nb_ligne_content = content.trim().split("\n").length; // permet de compter le nombre de ligne dans le fichier
							if(nb_ligne_content > 5){nb_ligne_content = 5;} // on ne veut pas montrer l'ensemble du contenu
							var tableau_content = new Array(nb_ligne_content);
							fileDisplayArea.value = "";
							for (var i = 0; i < nb_ligne_content; i++){
								tableau_content[i] = content.substring(0, content.indexOf("\n",0)); // recupere une ligne
								content = content.replace(tableau_content[i]+"\n", ""); // permet d'enlever la ligne recupéré
								fileDisplayArea.value += tableau_content[i]+"\n"; // affichage du contenu
							}
						}
					)
				}
				// Si c'est un .bz2 (mais pas avec un trop gros volume)
				else if( fileInput.name.substr(-8)!=".tar.bz2" && fileInput.name.substr(-4)!=".bz2" && (fileInput.type=="application/x-bzip" || fileInput.type=="application/x-bzip2") ){
					var url = '../../../apps/snannyowncloudapi/file_compressed/read_file_bz2.php';
					$.post(url,
						{
							username : oc_current_user,
							filename : fileInput.name,
							baseURI : baseURI
						},
						function(data){
							// document.getElementById("fileDisplayArea").value = data;
							var content = data;
							// var start = 0;
							// if((content.length - 1)>2000000){
							// 	var stop = 2000000;
							// }else{
							// 	var stop = content.length - 1;
							// }
							document.getElementById("fileDisplayArea").style.display="initial";
							var fileDisplayArea = document.getElementById('fileDisplayArea'); // Recupere l'emplacement où va s'afficher le contenu du fichier
							var nb_ligne_content = content.trim().split("\n").length; // permet de compter le nombre de ligne dans le fichier
							if(nb_ligne_content > 5){nb_ligne_content = 5;} // on ne veut pas montrer l'ensemble du contenu
							var tableau_content = new Array(nb_ligne_content);
							fileDisplayArea.value = "";
							for (var i = 0; i < nb_ligne_content; i++){
								tableau_content[i] = content.substring(0, content.indexOf("\n",0)); // recupere une ligne
								content = content.replace(tableau_content[i]+"\n", ""); // permet d'enlever la ligne recupéré
								fileDisplayArea.value += tableau_content[i]+"\n"; // affichage du contenu
							}
						}
					)
				}
				// sinon Si c'est un .tar.gz (mais pas avec un trop gros volume)
				else if( fileInput.name.substr(-7)==".tar.gz" && (fileInput.type=="application/gzip" || fileInput.type=="application/x-gzip") ){
					var url = '../../../apps/snannyowncloudapi/file_compressed/read_file_targz.php';
					$.post(url,
						{
							username : oc_current_user,
							filename : fileInput.name,
							baseURI : baseURI
						},
						function(data){
							// document.getElementById("fileDisplayArea").value = data;
							var content = data;
							// var start = 0;
							// if((content.length - 1)>2000000){
							// 	var stop = 2000000;
							// }else{
							// 	var stop = content.length - 1;
							// }
							document.getElementById("fileDisplayArea").style.display="initial";
							var fileDisplayArea = document.getElementById('fileDisplayArea'); // Recupere l'emplacement où va s'afficher le contenu du fichier
							var nb_ligne_content = content.trim().split("\n").length; // permet de compter le nombre de ligne dans le fichier
							if(nb_ligne_content > 5){nb_ligne_content = 5;} // on ne veut pas montrer l'ensemble du contenu
							var tableau_content = new Array(nb_ligne_content);
							fileDisplayArea.value = "";
							for (var i = 0; i < nb_ligne_content; i++){
								tableau_content[i] = content.substring(0, content.indexOf("\n",0)); // recupere une ligne
								content = content.replace(tableau_content[i]+"\n", ""); // permet d'enlever la ligne recupéré
								fileDisplayArea.value += tableau_content[i]+"\n"; // affichage du contenu
							}
						}
					)
				}
				// sinon Si c'est un .gz (mais pas avec un trop gros volume)
				else if( fileInput.name.substr(-7)!=".tar.gz" && fileInput.name.substr(-3)==".gz" && (fileInput.type=="application/gzip" || fileInput.type=="application/x-gzip") ){
					var url = '../../../apps/snannyowncloudapi/file_compressed/read_file_gz.php';
					$.post(url,
						{
							username : oc_current_user,
							filename : fileInput.name,
							baseURI : baseURI
						},
						function(data){
							// document.getElementById("fileDisplayArea").value = data;
							var content = data;
							// var start = 0;
							// if((content.length - 1)>2000000){
							// 	var stop = 2000000;
							// }else{
							// 	var stop = content.length - 1;
							// }
							document.getElementById("fileDisplayArea").style.display="initial";
							var fileDisplayArea = document.getElementById('fileDisplayArea'); // Recupere l'emplacement où va s'afficher le contenu du fichier
							var nb_ligne_content = content.trim().split("\n").length; // permet de compter le nombre de ligne dans le fichier
							if(nb_ligne_content > 5){nb_ligne_content = 5;} // on ne veut pas montrer l'ensemble du contenu
							var tableau_content = new Array(nb_ligne_content);
							fileDisplayArea.value = "";
							for (var i = 0; i < nb_ligne_content; i++){
								tableau_content[i] = content.substring(0, content.indexOf("\n",0)); // recupere une ligne
								content = content.replace(tableau_content[i]+"\n", ""); // permet d'enlever la ligne recupéré
								fileDisplayArea.value += tableau_content[i]+"\n"; // affichage du contenu
							}
						}
					)
				}
				// sinon Si c'est un .zip (mais pas avec un trop gros volume)
				else if(fileInput.type.match("application/zip") || fileInput.type.match("application/x-zip-compressed")){ 
					
					var url = '../../../apps/snannyowncloudapi/file_compressed/read_file_zip.php';
					$.post(url, 
						{ 
							username : oc_current_user,
							filename : fileInput.name,
							baseURI : baseURI
						},
						function(data){
							var content = data;
							var start = 0;
							if((content.length - 1)>2000000){
								var stop = 2000000;
							}else{
								var stop = content.length - 1;
							}
							//document.getElementById("fileDisplayArea").style.display="initial";
							var fileDisplayArea = document.getElementById('fileDisplayArea'); // Recupere l'emplacement où va s'afficher le contenu du fichier
							var nb_ligne_content = content.trim().split("\n").length; // permet de compter le nombre de ligne dans le fichier
							if(nb_ligne_content > 5){nb_ligne_content = 5;} // on ne veut pas montrer l'ensemble du contenu
							var tableau_content = new Array(nb_ligne_content);
							fileDisplayArea.value = "";
							for (var i = 0; i < nb_ligne_content; i++){
								tableau_content[i] = content.substring(0, content.indexOf("\n",0)); // recupere une ligne
								content = content.replace(tableau_content[i]+"\n", ""); // permet d'enlever la ligne recupéré
								fileDisplayArea.value += tableau_content[i]+"\n"; // affichage du contenu
							}
						}
					)
				}
				//si c'est pas un .bz2, .tar.bz2, .gz, .tar.gz ou un zip
				else{
					document.getElementById("fileDisplayArea").style.display="initial";
					var fileDisplayArea = document.getElementById('fileDisplayArea'); // Recupere l'emplacement où va s'afficher le contenu du fichier
					if (fileInput.type.match("text/*") || fileInput.type.match("application/*") || fileInput.type=="") { // Permet de comparer le fichier
						
						// var start = 0;
						// if((fileInput.size - 1)>2000000){
						// 	var stop = 2000000;
						// }else{
						// 	var stop = fileInput.size - 1;
						// }
						
						var reader = new FileReader();
						reader.onload = function(e) {
							var contentfile = reader.result;
							var nb_ligne_contentfile = contentfile.trim().split("\n").length; // permet de compter le nombre de ligne dans le fichier
							if(nb_ligne_contentfile > 5){nb_ligne_contentfile = 5;} // on ne veut pas montrer l'ensemble du contenu
							var tableau_contentfile = new Array(nb_ligne_contentfile);
							//fileDisplayArea.innerText = "";
							fileDisplayArea.value = "";
							for (var i = 0; i < nb_ligne_contentfile; i++){
								tableau_contentfile[i] = contentfile.substring(0, contentfile.indexOf("\n",0)); // recupere une ligne
								contentfile = contentfile.replace(tableau_contentfile[i]+"\n", ""); // permet d'enlever la ligne recupéré
								//fileDisplayArea.innerText += tableau_contentfile[i]+"\n"; // affichage du contenu
								fileDisplayArea.value += tableau_contentfile[i]+"\n"; // affichage du contenu
							}
						}
						var blob = fileInput.slice(start, stop + 1);
						reader.readAsBinaryString(blob);
						// reader.readAsText(fileInput);
					}
					else {
						//fileDisplayArea.innerText = "File not supported !";
						fileDisplayArea.value = "File not supported !";
					}
				}
			//}

			// fonction permettant de savoir le nombre de séparateurs selectionnés et lesquels ont été selectionnés
			function calcul_separator(){

				document.getElementById("fileDisplayArea").style.display="none";

				var nb_checked = 0;
				for(var i = 0; i<5;i++){
					if(document.getElementsByName("checkbox")[i].checked){
						nb_checked++; // on regarde le nombre de case(s) cochée(s) pour créer notre tableau à la bonne dimension
					}
				}
				var separators = new Array(nb_checked);
				nb_checked = 0;
				for(var i = 0; i<5;i++){
					if(document.getElementsByName("checkbox")[i].checked){
						separators[nb_checked]=document.getElementsByName("checkbox")[i].value; // on recupere la valeur des cases cochées
						nb_checked++;
					}
				}
				decoupe_file(separators, nb_checked);
			}

			// fonction permettant de couper le contenu en fonction des séparateurs choisi
			var content_preview_table;
			function decoupe_file(separators, nb_checked){
				var fileDisplayArea = document.getElementById('fileDisplayArea'); //recupere l'emplacement où va s'afficher le contenu du fichier
				// var contentfile = fileDisplayArea.innerText; // recuperation de ce qu'il y avait deja écrit
				var contentfile = fileDisplayArea.value; // recuperation de ce qu'il y avait deja écrit
				var nb_ligne_contentfile = contentfile.trim().split("\n").length; // on regarde le nombre de ligne (normalement 5)
				if(nb_ligne_contentfile > 5){nb_ligne_contentfile = 5;}
				var tableau_contentfile = new Array(nb_ligne_contentfile);
				var tableau_contentfile_separators = new Array(nb_ligne_contentfile);
				// fileDisplayArea.innerText = "";
				fileDisplayArea.value = "";
				for (var i = 0; i < nb_ligne_contentfile; i++){
					tableau_contentfile[i] = contentfile.substring(0, contentfile.indexOf("\n",0));
					contentfile = contentfile.replace(tableau_contentfile[i]+"\n", "");
					tableau_contentfile_separators[i] = tableau_contentfile[i].split(new RegExp(separators.join('|'), 'g'));
					// fileDisplayArea.innerText += tableau_contentfile[i]+"\n";
					fileDisplayArea.value += tableau_contentfile[i]+"\n";
				}

				// Script js permettant de faire la table preview
				$.getScript("../../../apps/snannyowncloudapi/js/datatables.min.js")
				.done(function( script, textStatus ) {
					
					// permet de savoir la taille maximale des differents tableaux
					var length_max_tableau_contentfile_separators = 0;
					for(var i=0;i<tableau_contentfile_separators.length;i++){
						if(tableau_contentfile_separators[i].length>length_max_tableau_contentfile_separators){
							length_max_tableau_contentfile_separators = tableau_contentfile_separators[i].length;
						}
					}

					// contenu des colonnes
					var tableau_contentfile_separators_values = new Array();//Array(tableau_contentfile_separators.length);
					for(var i = 0; i< tableau_contentfile_separators.length;i++){
						tableau_contentfile_separators_values[i]= new Array();//Array(length_max_tableau_contentfile_separators);
						for(var j=0;j<length_max_tableau_contentfile_separators;j++){
							if(j<tableau_contentfile_separators[i].length){
								tableau_contentfile_separators_values[i][j]=tableau_contentfile_separators[i][j]; // contient les valeurs de chaque colonne
							}else{
								tableau_contentfile_separators_values[i][j]="";
							}

						}
					}					

					// pour faire un head qui conviendra peu importe la taille du tableau de contenu
					length_max_tableau_contentfile_separators = 100;
					
					var columns_head_hide = "[";
					for(var i=0;i<length_max_tableau_contentfile_separators;i++){
						if(i<length_max_tableau_contentfile_separators-1){
							columns_head_hide +="{\"title\" : \"title\"},"
						}else{
							columns_head_hide +="{\"title\" : \"title\"}"
						}
					}
					columns_head_hide += "]";

   					$(document).ready( function(){
						$.fn.dataTable.ext.errMode = 'none';
						$('#preview_table')[0].innerHTML = '';
						content_preview_table = $('#preview_table').DataTable( {
							"bPaginate": false,
							"bFilter": false,
							"bSort": false,
							"bInfo": false,
							"scrollX": true,//horizontale
							"scrollY": true,//verticale
							data: tableau_contentfile_separators_values,
							columns: JSON.parse(columns_head_hide)
						});

					});

					// permet de changer la valeur de la premiere ligne de choisie de la colonne que l'utilisateur a choisi
					$('#preview_table tbody').unbind('dblclick');
					$('#preview_table tbody').on( 'dblclick', 'td', function () {
						if(document.getElementById("button_delete_line").style.display=="initial"){
							//var selected_column = content_preview_table.cell(this).index().column;
							for(var num_ligne=0;num_ligne<$('#preview_table tbody tr').length;num_ligne++){
								for(var num_colonne=0;num_colonne<$('#preview_table tbody tr')[num_ligne].children.length;num_colonne++){
									if(this==$('#preview_table tbody tr')[num_ligne].children[num_colonne]){
										var selected_column = num_colonne;
									}
								}
							}
							var reponse = prompt("Quelle valeur voulez-vous mettre ?", "<Entrez votre valeur>");
							$('#preview_table tbody')[0].children[0].childNodes[selected_column].innerText = reponse;
						}
					});

					//document.getElementById("preview_table").addEventListener('click', function () {
					$('#preview_table tbody').unbind('click');
					$('#preview_table tbody').on( 'click', 'td', function () {
						// recupere la colonne choisie par l'utilisateur
						//var selected_column = content_preview_table.cell(this).index().column;
						for(var num_ligne=0;num_ligne<$('#preview_table tbody tr').length;num_ligne++){
							for(var num_colonne=0;num_colonne<$('#preview_table tbody tr')[num_ligne].children.length;num_colonne++){
								if(this==$('#preview_table tbody tr')[num_ligne].children[num_colonne]){
									var selected_column = num_colonne;
								}
							}
						}
						// document.getElementById("id_selected_box").innerText = content_preview_table.cell(0, selected_column).data();
						document.getElementById("id_selected_box").innerText = $('#preview_table tbody')[0].children[0].childNodes[selected_column].innerText;
						// Permet de changer la couleur de fond de la cellule
						$('#preview_table tbody tr td').css('background-color', 'white');
						// 	$('#preview_table tbody tr td').eq(selected_column).css('background-color', 'palegreen');
						$('#preview_table tbody tr td').eq(selected_column).css('background-color', 'rgb(220, 215, 215)');
					});

					
				})
			}

			// Bouton permettant d'ajouter une nouvelle ligne en haut du tableau
			document.getElementById("button_new_line").addEventListener('click', function () {
				content_preview_table.row.add("").draw(false);
				for(var i=0;i<$('#preview_table tbody')[0].children[4].childNodes.length;i++){
					$('#preview_table tbody')[0].children[5].childNodes[i].innerText=$('#preview_table tbody')[0].children[4].childNodes[i].innerText;
					$('#preview_table tbody')[0].children[4].childNodes[i].innerText=$('#preview_table tbody')[0].children[3].childNodes[i].innerText;
					$('#preview_table tbody')[0].children[3].childNodes[i].innerText=$('#preview_table tbody')[0].children[2].childNodes[i].innerText;
					$('#preview_table tbody')[0].children[2].childNodes[i].innerText=$('#preview_table tbody')[0].children[1].childNodes[i].innerText;
					$('#preview_table tbody')[0].children[1].childNodes[i].innerText=$('#preview_table tbody')[0].children[0].childNodes[i].innerText;
					$('#preview_table tbody')[0].children[0].childNodes[i].innerText="<Entrez votre valeur>";
				}
				document.getElementById("button_delete_line").style.display="initial";
				document.getElementById("button_new_line").style.display="none";
			});

			// Bouton permettant de supprimer la ligne qui a été ajouté au tableau
			document.getElementById("button_delete_line").addEventListener('click', function () {
				var confirmation = confirm("Voulez-vous supprimer la ligne définitivement ?");
				if(confirmation){
					content_preview_table.row($('#preview_table tbody')[0].children[0]).remove().draw(false);
					document.getElementById("button_delete_line").style.display="none";
					document.getElementById("button_new_line").style.display="initial";
				}
			});

				var path_json = "../../../apps/snannyowncloudapi/skos/";
				
				// charge le contenu de p06.json dans un tableau
				var tableau_unit = new Array(2);
				tableau_unit[0] = new Array();
				tableau_unit[1] = new Array();
				$.getJSON(path_json+"json/p06.json", function(data) { // pour l'auto-complétion des unités
					data["rdf:RDF"]["skos:Collection"]["skos:member"].forEach(function(element) {
						tableau_unit[0].push(element["skos:Concept"]["skos:prefLabel"]["content"]);
						tableau_unit[1].push(element["skos:Concept"]["rdf:about"]);
					});
					var awesomplete_unit = new Awesomplete(document.getElementById("id_unit"), {minChars: 1, maxItems: 50, /*autoFirst: true*/});
					awesomplete_unit.list = tableau_unit[0];
				});	

				// switch permettant de jongler entre "chemical parameter" et "hierarchical search"
				document.getElementById("id_switch_checkbox").addEventListener("change", function(){ 
					if(document.getElementById('id_one_parameter').style.display=="initial"){
						document.getElementById('id_one_parameter').style.display="none";
						document.getElementById('id_multiple_parameters').style.display="initial";
					}
					else{
						document.getElementById('id_one_parameter').style.display="initial";
						document.getElementById('id_multiple_parameters').style.display="none";
					}
				});

				// Ajoute les noms des bassins dans un tableau
				var tableau_bassin = new Array();
				tableau_bassin = ["Ifremer","Houle boulogne","Hydrodynamiques Brest","Labo comportement structure mer","KHL","CNR-INSEAN 1","CNR-INSEAN 2"];
				var awesomplete_bassin = new Awesomplete(document.getElementById("id_bassin"), {minChars: 1, maxItems: 50, /*autoFirst: true*/});
				awesomplete_bassin.list = tableau_bassin;

				document.getElementById("id_bassin").addEventListener("change", function(){ 
					if(document.getElementById('id_bassin').value == tableau_bassin[0]){
						document.getElementById('image_bassin').style.display = "initial";
						document.getElementById('image_bassin').src = "../../../apps/snannyowncloudapi/photos_bassins/bassin_bretagne.PNG";
					}
					else if(document.getElementById('id_bassin').value == tableau_bassin[1]){
						document.getElementById('image_bassin').style.display = "initial";
						document.getElementById('image_bassin').src = "../../../apps/snannyowncloudapi/photos_bassins/bassin_houle_boulogne.PNG";
					}
					else if(document.getElementById('id_bassin').value == tableau_bassin[2]){
						document.getElementById('image_bassin').style.display = "initial";
						document.getElementById('image_bassin').src = "../../../apps/snannyowncloudapi/photos_bassins/bassin_hydrodynamiques_brest.PNG";
					}
					else if(document.getElementById('id_bassin').value == tableau_bassin[3]){
						document.getElementById('image_bassin').style.display = "initial";
						document.getElementById('image_bassin').src = "../../../apps/snannyowncloudapi/photos_bassins/labo_comportement_structure_mer.PNG";
					}
					else{
						document.getElementById('image_bassin').style.display = "none";
					}
					if(document.getElementById('image_bassin').height > 300){
						document.getElementById('image_bassin').height = 300;
					}
					//document.getElementById('image_bassin').width = ;
				});

				document.getElementById("id_unit").addEventListener("focus",function(){
					if(document.getElementById("id_selected_box").innerText == ""){
						alert("Veuillez choisir la colonne à renseigner avant de saisir une unité");
						this.blur();
					}
				});

				///////////////////////////////////////////////////////////////////////////////////////////////////////////////
				///////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// Evenement permettant de recupérer la valeur selectionner dans les différents champs pour les sauvergarder //
				//       pour ainsi pouvoir remettre les valeurs saisies si on revient sur une colonne déjà renseignée       //
				///////////////////////////////////////////////////////////////////////////////////////////////////////////////
				///////////////////////////////////////////////////////////////////////////////////////////////////////////////

				// permet de récupérer le "about" avec la valeur selectionnée dans unit
				var about_unit = "";
				document.getElementById("id_unit").addEventListener("change", function(){
					if(document.getElementById('id_unit').value!=""){
						for(var i=0;i<tableau_unit[0].length;i++){
							if(document.getElementById('id_unit').value==tableau_unit[0][i]){
								about_unit = tableau_unit[1][i];
							}
						}
						// alert("unit about : "+about_unit);
					}
					else{
						about_unit = "";
					}
					//save_table_parameter();
				});

				// Tableau qui contiendra les parametres pour chaque colonne du tableau de valeurs
				tableau_save_parameter = new Array(); // p06 (unités)


				// fonction permettant de trouver les parametres correspondants aux infos saisies
				function save_table_parameter(){
					// nombre de colonne dans notre preview
					var length_max_tableau_contentfile_separators = document.getElementById('preview_table').rows[1].cells.length;

					// permet de savoir quelle colonne on a choisi de renseigner
					var add_param_current_column = -1;
					for(var i=0;i<length_max_tableau_contentfile_separators;i++){
						tableau_save_parameter[0][i] = document.getElementById('preview_table').rows[1].cells[i].innerText;
						if(tableau_save_parameter[0][i]==document.getElementById('id_selected_box').innerText){
							add_param_current_column = i;
						}
					}
					// Remplissage avec les informations saisies
					tableau_save_parameter[1][add_param_current_column]=about_parameter;
					tableau_save_parameter[2][add_param_current_column]=about_unit;
					tableau_save_parameter[3][add_param_current_column]=about_measurement_matrix_relationship;
					tableau_save_parameter[4][add_param_current_column]=about_measurement_property;
					tableau_save_parameter[5][add_param_current_column]=about_measurement_statistical_qualifer;
					tableau_save_parameter[6][add_param_current_column]=about_matrix;
					tableau_save_parameter[7][add_param_current_column]=about_chemical_substance;

					document.getElementById('table_save_parameter').value = tableau_save_parameter;

					research_parameter_builder();

					// Sauvegarde les parametres saisies dans la table id=table_save_parameter
					//$.getScript("../../../apps/snannyowncloudapi/js/datatables.min.js")
					//.done(function( script, textStatus ) {
						var columns_head_hide = "[";
						for(var i=0;i<$('#preview_table')[0].children[1].children[0].cells.length;i++){
							if(i<$('#preview_table')[0].children[1].children[0].cells.length-1){
								columns_head_hide +="{\"title\" : \"title\"},"
							}else{
								columns_head_hide +="{\"title\" : \"title\"}"
							}
						}
						columns_head_hide += "]";   					
						
						$(document).ready( function(){
							$.fn.dataTable.ext.errMode = 'none';
							$('#table_save_parameter')[0].innerHTML = '';
							content_preview_table = $('#table_save_parameter').DataTable( {
								"bPaginate": false,
								"bFilter": false,
								"bSort": false,
								"bInfo": false,
								"scrollX": true,//horizontale
								"scrollY": true,//verticale
								data: tableau_save_parameter,
								columns: JSON.parse(columns_head_hide)
							});
	
						});

					//});

				}


				// fonction qui permet de ne garder que les parameters qui ont comme ressource les données saisies dans les différentes auto-complétions
				function research_parameter_builder(){

					// tableau_full_infos[0] // P01 (prefLabel de P01)
					// tableau_full_infos[1] // S02 (ressoure de P01 et about de S02) measurement_matrix_relationship
					// tableau_full_infos[2] // S06 (ressoure de P01 et about de S06) measurement_property
					// tableau_full_infos[3] // S07 (ressoure de P01 et about de S07) measurement_statistical_qualifer
					// tableau_full_infos[4] // S26 (ressoure de P01 et about de S26) matrix
					// tableau_full_infos[5] // S27 (ressoure de P01 et about de S27) chemical_substance

					// Copie le tableau pour ne pas perdre l'ensemble des données
					var tableau_builder = new Array(tableau_full_infos.length);
					for(var i = 0;i<tableau_full_infos.length;i++){
						tableau_builder[i] = new Array(tableau_full_infos[i].length);
						for(var j=0;j<tableau_full_infos[i].length;j++){
							tableau_builder[i][j] = tableau_full_infos[i][j];
						}
					}

				}


			var onChangeOrSelect = function (event, ui) {

			    	var selected = ui.item;
			    	if(selected){
			    		$('#system').val(selected.uuid);
						$('#startDate').val(selected.startDate);
						$('#endDate').val(selected.endDate);
						$("#searchNotFound").toggleClass("hidden", true);
			   		}else{
			   			$('#system').val('');
			   			$('#startDate').val('');
			   			$('#endDate').val('');
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
									var label = item.label;
									var startLabel = '';
									var endLabel = '';
									if(item.startDate !== null || item.endDate !== null) {
										startLabel = ' [';
										endLabel = ']';
										if(item.startDate !== null) {
											startLabel = startLabel +  new Date(parseInt(item.startDate) * 1000).toLocaleString() + ',';
										}
										if(item.endDate !== null) {
											endLabel = ' ' + new Date(parseInt(item.endDate) * 1000).toLocaleString() + endLabel;
										}
									}
									label = label + startLabel + endLabel;

					                return { 
					                    label: label,
					                    uuid: item.uuid,
										startDate: item.startDate,
										endDate: item.endDate
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
			if(typeof this._prompt != 'undefined'){
				var value = this._prompt.pop();
				if(value){
					this._showDialog(value);
				}else{
					this._currentDialog = undefined;
				}
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
	OCA.SnannyOwncloudAPI.ObservationUpload_marinet = ObservationUpload_marinet;
})();

// $(document).ready(function() {
// 	$('#file_upload_start').bind('fileuploaddone', OCA.SnannyOwncloudAPI.ObservationUpload_original.done);
// });