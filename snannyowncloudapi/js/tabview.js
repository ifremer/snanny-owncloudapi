/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function() {

	Handlebars.registerHelper('list', function(items, options) {
	  if(items){
		  var out = "<ul>";


		  for(var i=0, l=items.length; i<l; i++) {
		    out = out + "<li>" + options.fn(items[i]) + "</li>";
		  }

		  return out + "</ul>";
	  }
	  return "";
	});

	var TEMPLATE_ITEM_OM =
		'<li><b>Uuid :</b> {{uuid}}</li>'
		+'<li><b>System :</b> {{systemUuid}}</li>'
		+'<li><b>Name :</b> {{name}}</li>'
		+'<li><b>Description :</b> {{description}}</li>'
		+'<li><b>ResultFile :</b> {{resultFile}}</li>'
		+'<li><br/><b>Index history :</b></li>'
		+'<li class="index"><table class="history"><thead><th>Date</th><th>Status</th><th>IndexedObservations</th></thead>'
		+'<tbody>{{#list index_history}}<tr><td>{{time}}</td><td>{{status}}</td><td>{{indexedObservations}}</td></tr>'
		+'<tr><td colspan="3">{{message}}</td></tr>'
		+'{{/list}}</tbody></table>'
		+'<li class="noIndex hidden">Not indexed</li>';

	var TEMPLATE_ITEM_SML =
		'<li><b>Uuid :</b> {{uuid}}</li>'
		+'<li><b>Name :</b> {{name}}</li>'
		+'<li><b>Description :</b> {{description}}</li>'
		+'<li class="ancestors"><br/><b>Ancestors : </b><table class="history"><thead><th>Name</th><th>Uuid</th></thead>'
		+'<tbody>{{#list ancestors}}<tr><td>{{name}}</td><td>{{uuid}}</td></tr>{{/list}}</tbody></table></li>'
		+'<li class="children"><br/><b>Chlidren : </b>'
		+'<table class="history"><thead><th>Name</th><th>Uuid</th></thead>'
		+'<tbody>{{#list children}}<tr><td>{{name}}</td><td>{{uuid}}</td></tr>{{/list}}</tbody></table></li>';


	var TEMPLATE =
		'<ul class="snannyowncloudapi"></ul>' +
		'<div class="clear-float"></div>' +
		'<div class="empty hidden">{{emptyResultLabel}}</div>' +
		'<div class="loading hidden" style="height: 50px"></div>';

	/**
	 * @memberof OCA.Versions
	 */
	var ObservationTabView = OCA.Files.DetailTabView.extend(
		/** @lends OCA.Versions.VersionsTabView.prototype */ {
		id: 'observationTabView',
		className: 'tab observationTabView',

		_template: null,

		$snannyContainer: null,

		_fileId:null,

		_info:null,

		_this:null,

		_type:null,


		initialize: function() {
			OCA.Files.DetailTabView.prototype.initialize.apply(this, arguments);
			_this = this;
		},

		getLabel: function() {
			return 'Informations';
		},

		template: function(data) {
			if (!this._template) {
				this._template = Handlebars.compile(TEMPLATE);
			}

			return this._template(data);
		},

		itemTemplateOM: function(data) {
			if (!this._itemTemplateOM) {
				this._itemTemplateOM = Handlebars.compile(TEMPLATE_ITEM_OM);
			}

			return this._itemTemplateOM(data);
		},

		itemTemplateSML: function(data) {
			if (!this._itemTemplateSML) {
				this._itemTemplateSML = Handlebars.compile(TEMPLATE_ITEM_SML);
			}

			return this._itemTemplateSML(data);
		},

		setFileInfo: function(fileInfo) {
			_fileId = fileInfo['id'];
			this.render();
			this._toggleLoading(true);
			var name = fileInfo.attributes.name;
			var urlGen = "";
			if(name.endsWith('sensorML.xml')){
				this._type='sml';
				urlGen = OC.generateUrl('/apps/snannyowncloudapi/sml/'+_fileId+'/info');
			} else if(name.endsWith(".xml")){
				this._type='om';
				urlGen = OC.generateUrl('/apps/snannyowncloudapi/om/'+_fileId+'/info');
			}
			$.ajax({
				type: 'GET',
				url: urlGen,
				dataType: 'json',
				success: function(response) {
					_info = response;
					_this.displayInfo();
				},
				error:function(error){
					_info = null;
					_this.displayInfo();
				}
			});

		},

		displayInfo :function(){
			this._toggleLoading(false);
			if (_info && _info['uuid']) {
				this._toggleEmpty(true);
				//Render item 
				if (this._type == 'om') {
					this.$snannyContainer.html(this.itemTemplateOM(_info));
					this.$snannyContainer.find('.index').toggleClass('hidden', !_info.indexed);
					this.$snannyContainer.find('.noIndex').toggleClass('hidden', _info.indexed);
				} else {
					this.$snannyContainer.html(this.itemTemplateSML(_info));
					this.$snannyContainer.find('.children').toggleClass('hidden', !_info.hasChildren);
					this.$snannyContainer.find('.ancestors').toggleClass('hidden', !_info.hasAncestors);
				}
			} else {
				this._toggleEmpty(false);
			}
		},

		/**
		 * Renders this details view
		 */
		render: function() {
			this.$el.html(this.template({
				emptyResultLabel: 'No informations availables',
			}));
			this.$snannyContainer = this.$el.find('ul.snannyowncloudapi');
			this.delegateEvents();
		},

		/**
		 * Returns true for files, false for folders.
		 *
		 * @return {bool} true for files, false for folders
		 */
		canDisplay: function(fileInfo) {
			if (!fileInfo) {
				return false;
			}
			if(!fileInfo.isDirectory()){
				var name = fileInfo.attributes.name;
				if(name.endsWith('.xml')){
					return true;
				}
			}
			return false;
		},

		_toggleLoading: function(state) {
			this._loading = state;
			this.$el.find('.loading').toggleClass('hidden', !state);
		},

		_toggleEmpty : function(state) {
			this._empty = state;
			this.$el.find('.empty').toggleClass('hidden', state);
		}

	});

	OCA.SnannyOwncloudAPI = OCA.SnannyOwncloudAPI || {};
	OCA.SnannyOwncloudAPI.ObservationTabView = ObservationTabView;
})();
