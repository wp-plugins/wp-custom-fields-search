
CustomSearch = function(id,count) {
	this.id=id;

	this.add = function (){
		var html = jQuery('#config-template-'+this.id).html();
		var oldHtml = false;
		var count=0;
		do {
			newId = 'config-form-'+this.id+'-'+(++count);
		} while(jQuery("#"+newId).attr('id'));

		do {
			oldHtml = html;
			html = html.replace('###TEMPLATE_ID###',count);
		} while(html!=oldHtml);
		html=html.replace('config-template-'+this.id,newId);
		jQuery('<div id="'+newId+'">'+html+"</div>").appendTo('#config-form-'+this.id);

		
		return false;
	};
	this.remove = function (id){
		jQuery('#config-form-'+this.id+'-'+id).remove();
		return false;
	};
};

CustomSearch.create = function(id){
	CustomSearch[id] = new CustomSearch(id);
}

//jQuery(document).ready(function(){
//	jQuery('.widget-control-edit').click();
//});
