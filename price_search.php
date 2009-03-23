<?
/*
* Plugin Name: Price Search Fields
* Plugin URI: http://wp.don-benjamin.co.uk/price-search-fields
* Description: Adds extra fields to the search form and relates these to custom fields
* Version: 0.1
* Author: Don Benjamin
* Author URI: http://don-benjamin.co.uk
* */
require_once(dirname(__FILE__).'/extra_search_fields.php');


class DB_PriceSearch_Widget extends DB_Search_Widget {
	function DB_PriceSearch_Widget(){
		DB_PriceSearch_Widget::__construct();
	}
	function __construct(){
		parent::__construct("Price");
		$this->addInput(new PriceSearchField());
		$this->addInput( new CustomSearchField('bedrooms',
				new DropDownFromValues('bedrooms'),
				new EqualComparison(),
				new GreatRealEstateJoiner('bedrooms')));
		$this->addInput(new CustomSearchField('Type',
				new DropDownFromValues('propertyType'),
				new EqualComparison(),
				new GreatRealEstateJoiner("propertyType")));
		$this->addInput( new CustomSearchField('bathrooms',
				new DropDownFromValues('bathrooms'),
				new EqualComparison(),
				new GreatRealEstateJoiner('bathrooms')));
		$this->addInput(new CustomSearchField('city',
				new DropDownFromValues('city'),
				new EqualComparison(),
				new GreatRealEstateJoiner('city')));
	}
	function init(){
		new DB_PriceSearch_Widget();
	}
}

class PriceSearchField extends CustomSearchField {
	function PriceSearchField(){
		$this->__construct();
	}
	function __construct(){
		parent::__construct('List Price',new DropDownField(
			apply_filters('price_search_options',array(
				''=>'ANY',
				'0:10'=>'0 to 10',
				'10:50'=>'10 to 50',
				'50:200'=>'50 to 200',
				'200:'=>'200+'))
			), new RangeComparison(),
				new GreatRealEstateJoiner("ListPrice"));
	}
}

class GreatRealEstateJoiner {
	function GreatRealEstateJoiner($name=null){
		$this->__construct($name);
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

add_action('plugins_loaded',array('DB_PriceSearch_Widget','init'));


add_filter('dollar_price','nigerianise_price');
function nigerianise_price($price){
	return str_replace("$","&#x20A6;",$price);
}

?>
