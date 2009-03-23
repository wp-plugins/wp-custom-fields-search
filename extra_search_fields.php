<?php
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

/*
 *  This library is required for the configurable search plugin.
 *
 *  It could also be used to make static unconfigurable search plugins.
 *
 * Author: Don Benjamin
 * Author URI: http://don-benjamin.co.uk
 * */

class DB_WP_Widget {
	function DB_WP_Widget($name,$params=array()){
		DB_WP_Widget::__construct($name,$params);
	}
	function __construct($name,$params=array()){
		$this->name = $name;
		$this->setParams($params);
		$this->id = strtolower(get_class($this));
		$options = get_option($this->id);


//		register_sidebar_widget($this->name,array(&$this,'renderWidget'));
		$doesOwnConfig = $this->params['doesOwnConfig'];
		$desc = $this->getParam('description',$this->name);
		$widget_ops = array('classname' => $this->id, 'description' => __($desc));
		$control_ops = array('width' => 400, 'height' => 350, 'id_base' => $this->id);
		$name = $this->name;
	
		$id = false;
		do {
			if($options)
			foreach ( array_keys($options) as $o ) {
				// Old widgets can have null values for some reason
				if ( !isset($options[$o]['exists']) )
					continue;
				$id = "$this->id-".abs($o); // Never never never translate an id
				wp_register_sidebar_widget($id, $name, array(&$this,'renderWidget'), $widget_ops, array( 'number' => $o ));
				wp_register_widget_control($id, $name, array(&$this,'configForm'), $control_ops, array( 'number' => $o ));
			}
			$options = array( -1=>array('exists'=>1));
		} while(!$id);
	}

	function setParams($params){
		$this->params = $this->overrideParams($params);
	}

	function getParam($key,$default=null){
		if(array_key_exists($key,$this->params)){
			return $this->params[$key];
		} else {
			return $default;
		}
	}

	function getDefaults(){
		return array('doesOwnConfig'=>false);
	}
	function overrideParams($params){
		foreach($this->getDefaults() as $k=>$v){
			if(!array_key_exists($k,$params)) $params[$k] = $v;
		}
		return $params;
	}

	function renderWidget(){
		echo "<h1>Unconfigured Widget</h1>";
	}

	function defaultWidgetConfig(){
		return array('exists'=>'1');
	}
	function getConfig($id){
		$options = get_option($this->id);
		$id = preg_replace('/^.*-(\d+)$/','\\1',$id);
		return $options[$id];
	}
	function configForm($args){
		static $first;
		global $wp_registered_widgets;

		if ( is_numeric($args) )
			$args = array( 'number' => $args );

		$args = wp_parse_args($args,array('number'=>-1));
		static $updated = array();

		$options = get_option($this->id);

		if(!$updated[$this->id] && $_POST['sidebar']){
			$updated[$this->id]=true;
			$sidebar = (string) $_POST['sidebar'];
			$default_options=$this->defaultWidgetConfig();

			$sidebars_widgets = wp_get_sidebars_widgets();
			if ( isset($sidebars_widgets[$sidebar]) )
				$this_sidebar = $sidebars_widgets[$sidebar];
			else
				$this_sidebar = array();

			foreach ( $this_sidebar as $_widget_id ) {
				$callback = $wp_registered_widgets[$_widget_id]['callback'];
			       if(is_array($callback) && get_class($callback[0])==get_class($this) && isset($wp_registered_widgets[$_widget_id]['params'][0]['number']) ) {{
				       $widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
			       }
				if ( !in_array( "$this->id-$widget_number", $_POST['widget-id'] ) ) 
					unset($options[$widget_number]);
				}
			}
			foreach ((array)$_POST[$this->id] as $widget_number => $posted) {
				if(!isset($posted['exists']) && isset($options[$widget_number]))continue;

				$widgetOptions = $this->form_processPost($posted,$options[$widget_number]);
				$options[$widget_number] = $widgetOptions;
			}
			update_option($this->id,$options);
		}
		global $mycount;
		if(-1==$args['number']){
			$args['number']='%i%';
			$values = $default_options;
		} else {
			$values = $options[$args['number']];
		}
		$this->form_outputForm($values,$this->id.'['.$args['number'].']');
	}
	function form_processPost($post,$old){
		return array('exists'=>1);
	}
	function form_outputForm($old,$pref){
		$this->form_existsInput($pref);
	}
	function form_existsInput($pref){
		echo "<input type='hidden' name='".$pref."[exists]' value='1'/>";
	}

	function nameAsId(){
		return strtolower(str_replace(" ","_",$this->name));
	}
}

class DB_Search_Widget extends DB_WP_Widget {
	var $inputs = array();

	function DB_Search_Widget($name){
		DB_Search_Widget::__construct($name);
	}
	function __construct($name='Custom',$params=array()){
		parent::__construct("$name Search",$params);
		add_filter('posts_join',array(&$this,'join_meta'));
		add_filter('posts_where',array(&$this,'sql_restrict'));
		add_filter('home_template',array(&$this,'rewriteHome'));
		add_filter('page_template',array(&$this,'rewriteHome'));
		add_action('wp_head', array(&$this,'outputStylesheets'), 1);
	}
	function addInput($input){
		$this->inputs[] = $input;
	}
	function outputStylesheets(){
		echo "\n".'<style type="text/css" media="screen">@import "'. WP_CONTENT_URL .'/plugins/custom-search/css/searchforms.css";</style>'."\n";
	}

	function getInputs($params){
		return $this->inputs;
	}

	function getTitle(){
		return $this->getParam('description',$this->name);
	}

	function renderWidget($params=array(),$p2 = array()){
		if($title=$this->getTitle($params)){
			echo $params['before_title'].$title.$params['after_title'];
		}
		echo "<form method='get' class='custom_search_widget custom_search_".$this->nameAsId()."' action='".get_option('siteurl')."'>";
		echo "<div class='searchform-params'>";
		foreach($this->getInputs($params) as $input){
			$inputClass = method_exists($input,'getCSSClass')?$input->getCSSClass():get_class($input);
			echo "<div class='$inputClass'>".$input->getInput()."</div>";
		}
		echo "</div>";
		echo "<div class='searchform-controls'>";

		echo "<input type='submit' name='search' value='Search'/>";
		echo "<input type='hidden' name='search-class' value='".$this->getPostIdentifier()."'/>";
		echo "<input type='hidden' name='widget_number' value='".$p2['number']."'/>";
		echo "</div>";
		echo "<div class='searchform-spoiler'><a href='http://www.don-benjamin.co.uk/wordpress-plugins/custom-search/'>Wordpress Configurable Search</a></div>";
		echo "</form>";
	}

	function isPosted(){
		return $_GET['search-class'] == $this->getPostIdentifier();
	}
	function getPostIdentifier(){
		return get_class($this).'-'.$this->id;
	}
	function isHome($isHome){
		return $isHome && !$this->isPosted();
	}
	function rewriteHome($homeTemplate){
		if($this->isPosted()) return get_query_template('search');
		return $homeTemplate;
	}

	function join_meta($join){
		if($this->isPosted()){
			foreach($this->getInputs($_REQUEST['widget_number']) as $input){
				$join = $input->join_meta($join);
			}
		}
		return $join;
	}
	function sql_restrict($where){
		if($this->isPosted()){
			foreach($this->getInputs($_REQUEST['widget_number']) as $input){
				$where = $input->sql_restrict($where);
			}
		}
		return $where;
	}
}


class SearchFieldBase {
	function SearchFieldBase(){
		SearchFieldBase::__construct();
	}
	function __construct(){
		add_filter('search_params',array(&$this,'form_inputs'));
		static $index;
		$this->index = ++$index;
	}
	function form_inputs($form){
		die("Unimplemented function ".__CLASS__.".".__FUNCTION__);
	}
	function sql_restrict($where){
		die("Unimplemented function ".__CLASS__.".".__FUNCTION__);
	}
}

class Field {
	function Field(){
		$this->__construct();
	}
	function __construct(){
	}

	function param($key,$default=null){
		if(array_key_exists($key,$this->params)) return $this->params[$key];
		return $default;
	}

	function getValue($name){
		return $_REQUEST[$this->getHTMLName($name)];
	}

	function getHTMLName($name){
		return 'cs-'.str_replace(" ","_",$name);
	}

	function getInput($name){
		$htmlName = $this->getHTMLName($name);
		$value = $this->getValue($name);
		return "<input name='$htmlName' value='$value'/>";
	}
}
class TextField extends Field {
}
class TextInput extends TextField{}
class DropDownField extends Field {
	function DropDownField($options=null,$params=array()){
		DropDownField::__construct($options,$params);
	}
	function __construct($options = null,$params = array()){
		parent::__construct();
		if($params['dropdownoptions']){
			$options=array();
			$optionPairs = explode(',',$params['dropdownoptions']);
			foreach($optionPairs as $option){
				list($k,$v) = explode(':',$option);
				if(!$v) $v=$k;
				$options[$k]=$v;
			}
		}
		$this->options = $options;
		$this->params = $params;
	}

	function getOptions($joiner,$name){
		if($this->param('fromDb',!$this->options)){
			$options = array(''=>'ANY');
			$options +=$joiner->getAllOptions($name);
			return $options;
		} else {
			return $this->options;
		}
	}
	function getInput($name,$joiner){
		$v = $this->getValue($name);
		$id = $this->getHTMLName($name);

		$options = '';
		foreach($this->getOptions($joiner,$name) as $option=>$label){
			$checked = ($option==$v)?" selected='true'":"";
			$options.="<option value='$option'$checked>$label</option>";
		}
		$atts = '';
		if($this->params['onChange']) $atts = ' onChange="'.htmlspecialchars($this->params['onChange']).'"';
		return "<select name='$id'$atts>$options</select>";
	}
	function getConfigForm($id,$values){
		return "<label for='$id-dropdown-options'>Drop Down Options</label><input id='$id-dropdown-options' name='$id"."[dropdownoptions]' value='$values[dropdownoptions]'/>";
	}
}

/* TODO: Add Caching */
class CustomFieldReader {

}

class DropDownFromValues extends DropDownField {
	function DropDownFromValues(){
		DropDownFromValues::__construct();
	}

	function __construct($params=array()){
		$params['fromDb'] = true;
		parent::__construct(array(),$params);
	}

	function getConfigForm($id,$values){
		return "";
	}
}
class RadioButtonField extends Field {
	function RadioButtonField($options=array(),$params=array()){
		RadioButtonField::__construct($options,$params);
	}
	function __construct($options=array(),$params=array()){
		if($params['radiobuttonoptions']){
			$options=array();
			$optionPairs = explode(',',$params['radiobuttonoptions']);
			foreach($optionPairs as $option){
				list($k,$v) = explode(':',$option);
				if(!$v) $v=$k;
				$options[$k]=$v;
			}
		}
		$this->options = $options;
		$this->params = $params;
	}
	function getOptions($joiner,$name){
		if($this->param('fromDb',!$this->options)){
			return $joiner->getAllOptions($name);
		} else {
			return $this->options;
		}
	}
	function getInput($name,$joiner){
		$v = $this->getValue($name);
		$id = $this->getHTMLName($name);

		$options = '';
		foreach($this->getOptions($joiner,$name) as $option=>$label){
			$checked = ($option==$v)?" checked='true'":"";
			$htmlId = "$id-$option";

			$options.="<div class='radio-button-wrapper'><input type='radio' name='$id' id='$htmlId' value='$option'$checked> <label for='$htmlId'>$label</label></div>";
		}
		return $options;
	}
	function getCSSClass(){
		return "RadioButton";
	}
	function getConfigForm($id,$values){
		return "<label for='$id-radiobutton-options'>Radio Button Options</label><input id='$id-radiobutton-options' name='$id"."[radiobuttonoptions]' value='$values[radiobuttonoptions]'/>";
	}
}
class RadioButtonFromValues extends RadioButtonField {
	function RadioButtonFromValues($fieldName=null){
		RadioButtonFromValues::__construct($fieldName);
	}

	function __construct($fieldName=null,$params){
		$params['fromDb'] = true;
		parent::__construct($options,$params);
	}
	function getConfigForm($id,$values){
		return "";
	}
}

class Comparison {
	function addSQLWhere($field,$value){
		die("Unimplemented function ".__CLASS__.".".__FUNCTION__);
	}
}
class EqualComparison extends Comparison {
	function addSQLWhere($field,$value){
		return "$field = '".mysql_escape_string($value)."'";
	}
}
class LikeComparison extends Comparison{
	function addSQLWhere($field,$value){
		return $this->getLikeString($field,$value);
	}
	function getLikeString($field,$value){
		return "$field LIKE '%".mysql_escape_string($value)."%'";
	}
}

class WordsLikeComparison extends LikeComparison {
	function addSQLWhere($field,$value){
		$words = explode(" ",$value);
		$like = array(1);
		foreach($words as $word){
			$like[] = $this->getLikeString($field,$word);
		}
		return "(".join(" AND ",$like).")";
	}
}
class LessThanComparison extends Comparison{
	function addSQLWhere($field,$value){
		return "$field < '".mysql_escape_string($value)."'";
	}
}
class MoreThanComparison extends Comparison{
	function addSQLWhere($field,$value){
		return "$feld > '".mysql_escape_string($value)."%'";
	}
}
class RangeComparison extends Comparison{
	function addSQLWhere($field,$value){
		$value = mysql_escape_string($value);
		list($min,$max) = explode("-",$value);
		$where=1;
		if(strlen($min)>0) $where.=" AND $field >= $min";
		if(strlen($max)>0) $where.=" AND $field <= $max";
		return $where;
	}
}

class BaseJoiner {
	function process_where($where){
		return $where;
	}
	function needsField(){
		return true;
	}
}
class CustomFieldJoiner extends BaseJoiner{
	function CustomFieldJoiner($name,$params){
		$this->__construct($name,$params);
	}
	function __construct($name,$params){
		$this->params = $params;
	}
	function param($key,$default=null){
		if(array_key_exists($key,$this->params)) return $this->params[$key];
		return $default;
	}
	function sql_restrict($name,$index,$value,$comparison){
		$table = 'meta'.$index;
		$field = "$table.meta_value".($this->param('numeric',false)?'*1':'');
		return " AND ( $table.meta_key='$name' AND ".$comparison->addSQLWhere($field,$value).") ";

	}
	function sql_join($name,$index,$value){
		if(!$value && !$this->params['required']) return $join;
		global $wpdb;
		$table = 'meta'.$index;
		return "$join JOIN $wpdb->postmeta $table ON $table.post_id=$wpdb->posts.id";
	}
	function getAllOptions($fieldName){
		global $wpdb;
		$q = mysql_query($sql = "SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key='$fieldName'");
		$options = array();
		while($r = mysql_fetch_row($q))
			$options[$r[0]] = $r[0];
		return $options;
	}
	function getSuggestedFields(){
		global $wpdb;
		$q = mysql_query($sql = "SELECT DISTINCT meta_key FROM $wpdb->postmeta WHERE meta_key NOT LIKE '_%'");
		$options = array();
		while($r = mysql_fetch_row($q))
			$options[$r[0]] = $r[0];
		return $options;
	}
}
class CategoryJoiner {
	function sql_restrict($name,$index,$value,$comparison){
		$table = 'meta'.$index;
		return " AND ( ".$comparison->addSQLWhere("$table.name",$value).") ";
	}
	function sql_join($join,$name,$index,$value){
		if(!$value && !$this->params['required']) return $join;
		global $wpdb;
		$table = 'meta'.$index;
		$rel = 'rel'.$index;
		return " JOIN $wpdb->term_relationships $rel ON $rel.object_id=$wpdb->posts.id JOIN  $wpdb->terms $table ON $table.term_id=$rel.term_taxonomy_id";
	}
	function getAllOptions($fieldName){
		global $wpdb;
		$q = mysql_query($sql = "SELECT distinct t.name FROM $wpdb->terms t JOIN $wpdb->term_relationships r ON r.term_taxonomy_id = t.term_id JOIN $wpdb->posts p ON r.object_id=p.id JOIN $wpdb->term_taxonomy x ON t.term_id=x.term_id WHERE post_status='publish' AND x.taxonomy='category'");
		$options = array();
		while($r = mysql_fetch_row($q))
			$options[$r[0]] = $r[0];
		return $options;
	}
	function needsField(){
		return false;
	}
}

class PostDataJoiner extends BaseJoiner {
	function sql_restrict($name,$index,$value,$comparison){
		global $wpdb;
		$table = $wpdb->posts;
		if($name=='all'){
			$logic = array();
			foreach($this->getSuggestedFields() as $name=>$desc){
				if($name=='all') continue;
				$logic[] =  "( ".$comparison->addSQLWhere("$table.$name",$value).") ";
			}
			$logic = " AND (".join(" OR ",$logic).")";
			return $logic;
		} else {
			return " AND ( ".$comparison->addSQLWhere("$table.$name",$value).") ";
		}
	}
	function sql_join($join,$name,$index,$value){
		return $join;
	}
	function getAllOptions($fieldName){
		global $wpdb;
		$q = mysql_query($sql = "SELECT $fieldName FROM $wpdb->posts");
		$options = array();
		while($r = mysql_fetch_row($q))
			$options[$r[0]] = $r[0];
		return $options;
	}
	function getSuggestedFields(){
		return array('all'=>'All Fields','post_content'=>'Body Text','post_title'=>'Title','post_author'=>'Author','post_date'=>'Date');
	}
}

class CategorySearch {
}

class CustomSearchField extends SearchFieldBase {
	function CustomSearchField($nameOrParams,$input=false,$comparison=false,$joiner=false){
		CustomSearchField::__construct($nameOrParams,$input,$comparison,$joiner);
	}
	function __construct($nameOrParams,$input=false,$comparison=false,$joiner=false){
		parent::__construct();
		if(!is_array($nameOrParams)){
			$params = array('name'=>$nameOrParams);
		} else {
			$params = $nameOrParams;
		}
		$this->name = $params['name'];
		$this->params = $params;

		$this->joiner = $joiner;
		$this->comparison = $comparison;
		$this->input = $input;

		if(!$this->input){
			$input = $this->param('input','TextField');
			$this->input = new $input(array(),$params);
		}
		if(!$this->comparison){
			$comparison = $this->param('comparison','LikeComparison');
			$this->comparison = new $comparison();
		}
		if(!$this->joiner){
			$joiner = $this->param('joiner','CustomFieldJoiner');
			$this->joiner = new $joiner($this->param('name'),$this->params);
		}


	}
	function param($key,$default=null){
		if(array_key_exists($key,$this->params)) return $this->params[$key];
		return $default;
	}

	function stripInitialForm($form){
		$pref='<!--cs-form-->';
		if(preg_match("/^$pref/",$form)) return $form;
		else return $pref;
	}

	function form_inputs($form){
		$form = $this->stripInitialForm($form);
		return $form.$this->getInput($this->name,$this->joiner);
	}
	function sql_restrict($where){
		if($value = $this->getValue()){
			$value = $GLOBALS['wpdb']->escape($value);
			$where.=$this->joiner->sql_restrict($this->name,$this->index,$value,$this->comparison);
		}
		if(method_exists($this->joiner,'process_where'))
			$where = $this->joiner->process_where($where);
		return $where;
	}
	function join_meta($join){
		global $wpdb;
		$join=$this->joiner->sql_join($join,$this->name,$this->index,$value,$this->comparison);
		return $join;
	}

	function getOldValue(){ return $this->getValue(); }
	function getValue(){
		return $this->input->getValue($this->name);
	}
	function getLabel(){
		if(!$this->params['label']) $this->params['label'] = ucwords($this->name);
		return $this->params['label'];
	}

	function getInput(){
		return "<div class='searchform-param'><label class='searchform-label'>".$this->getLabel()."</label><span class='searchform-input-wrapper'>".$this->input->getInput($this->name,$this->joiner)."</span></div>";
	}
	function getCSSClass(){
		return method_exists($this->input,'getCSSClass')?$this->input->getCSSClass():get_class($this->input);
	}
}

$dir = opendir($path = dirname(__FILE__).'/bridges');
while($file = readdir($dir)){
	if(is_file("$path/$file") && preg_match("/^[^.].*\.php$/",$file)){
		require_once("$path/$file");
	}
}
?>
