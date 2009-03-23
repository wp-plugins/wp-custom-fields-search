<?
require_once(dirname(__FILE__).'/../extra_search_fields.php');

class GreatRealEstateJoiner {
	function GreatRealEstateJoiner($name=null){
		GreatRealEstateJoiner::__construct($name);
	}
	function __construct($name){
		$this->name = $name;
	}
	function sql_restrict($name,$index,$value,$comparison){
		if($this->name) $name=$this->name;
		$table = 'meta'.$index;
		return " AND ( ".$comparison->addSQLWhere("$table.$name",$value).") ";
	}
	function sql_join($name,$index,$value){
		if($this->name) $name=$this->name;
		global $wpdb;
		$table = 'meta'.$index;
		return " JOIN $wpdb->gre_listings $table ON $table.pageid=$wpdb->posts.id";
	}
	function getAllOptions($fieldName){
		if($this->name) $fieldName=$this->name;
		global $wpdb;
		$q = mysql_query($sql = "SELECT DISTINCT $fieldName FROM $wpdb->gre_listings");
		if($e = mysql_error()){
			die("<h1>$sql</h1>".$e);
		}
		$options = array();
		while($r = mysql_fetch_row($q))
			$options[$r[0]] = $r[0];
		return $options;
	}
	function process_where($where){
		global $wpdb;
		$cleared = preg_replace("/AND $wpdb->posts.post_type = '(post|page)'/","",$where);
		$cleared = preg_replace("/$wpdb->posts.ID = '\d+'/","1",$cleared);
		return $cleared;
	}
}



add_filter('dollar_price','nigerianise_price');
function nigerianise_price($price){
	return str_replace("$","&#x20A6;",$price);
}
add_filter('custom_search_get_classes','add_real_estate_search_fields');
function add_real_estate_search_fields($classes){
	if(function_exists('greatrealestate_init'))
		$classes['joiner']['GreatRealEstateJoiner']='Great Real Estate';
	return $classes;
}

?>
