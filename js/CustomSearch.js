
CustomSearch = function(id) {
	this.id=id;
	this.add = function (){
		alert("Added to "+this.id);
	};
	this.remove = function (id){
	};
};

CustomSearch.create = function(id){
	CustomSearch[id] = new CustomSearch(id);
}
