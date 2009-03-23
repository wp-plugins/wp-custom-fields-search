/*
 * Copyright 2009 Don Benjamin
 * Licensed under the Apache License, Version 2.0 (the "License"); 
 * you may not use this file except in compliance with the License. 
 * You may obtain a copy of the License at 
 *
 * 	http://www.apache.org/licenses/LICENSE-2.0 
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, 
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. 
 * See the License for the specific language governing permissions and 
 * limitations under the License.
 */
CustomSearch = Class.create( {
	init : function (id) {
		this.id=id;
	},
	add: function (){
		var html = jQuery('#config-template-'+this.id).html();
		var oldHtml = false;
		var count=0;
		do {
			newId = 'config-form-'+this.id+'-'+(++count);
		} while(jQuery("#"+newId).attr('id'));

		html = this.replaceAll(html,'###TEMPLATE_ID###',count);
		html=html.replace('config-template-'+this.id,newId);
		jQuery('<div id="'+newId+'">'+html+"</div>").appendTo('#config-form-'+this.id);

		
		return false;
	},
	replaceAll: function(haystack,find,replace){
		do {
			oldHaystack = haystack;
			haystack = haystack.replace(find,replace);
		} while(haystack!=oldHaystack);
		return haystack;
	},

	getForm: function (id){
		return jQuery('#config-form-'+this.id+'-'+id);
	},
	remove: function (id){
		this.getForm(id).remove();
		return false;
	},

	updateOptions: function(id) {
		type = this.getForm(id).find('[@name="db_customsearch_widget['+this.id+']['+id+'][input]"]').val();
		template = jQuery('#config-input-templates-'+type+'-'+this.id);
		div = jQuery(hid = '#db_customsearch_widget-'+this.id+'-'+id+'-widget-config');
		html = template.html();
		if(!html) html='';

		html = this.replaceAll(html,'###TEMPLATE_ID###',id);
		name='';
		html = this.replaceAll(html,'###TEMPLATE_NAME###',name);
		div.html(html);
	}
});

CustomSearch.create = function(id){
	CustomSearch[id] = new CustomSearch(id);
};

var testing=false;
if(testing)
jQuery(document).ready(function(){
	jQuery('.widget-control-edit').click();
});
	dbg = function(obj){
		output='DEBUG:';
		output+=obj;
		count=0;
		for(prop in obj){
			output+="\n"+(typeof(obj[prop]))+":	"+prop;
			if(count++>=30) {
				if(!confirm(output)) return;
				output="";
				count=0;
			}
		}
		alert(output);
	};
