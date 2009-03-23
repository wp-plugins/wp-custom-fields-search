<?
/*
* Plugin Name: Custom Search Forms
* Plugin URI: http://wp.don-benjamin.co.uk/custom_search
* Description: [INCOMPLETE] Allows user to build custom search forms.
* Version: 0.1
* Author: Don Benjamin
* Author URI: http://don-benjamin.co.uk
* */
	require_once(dirname(__FILE__).'/extra_search_fields.php');

	//Add Widget for configurable search.
	add_action('plugins_loaded',array('DB_CustomSearch_Widget','init'));

	class DB_CustomSearch_Widget extends DB_Search_Widget {
		function DB_CustomSearch_Widget($params=array()){
			$this->__construct($params);
		}
		function __construct($params=array()){
			parent::__construct('Configurable',$params);
			add_action('admin_print_scripts', array(&$this,'print_admin_scripts'), 90);
		}
		function init(){
			new DB_CustomSearch_Widget();
		}

		function getInputs($params){
			$id = $params['widget_id'];

			$config = $this->getConfig($id);
			var_dump($config);
			return parent::getInputs($params);
		}

		function form_processPost($post,$old){
			unset($post['###TEMPLATE_ID###']);
			if(!$post) $post=array('exists'=>1);
			return $post;
		}
		function form_outputForm($values,$pref){
			$prefId = preg_replace('/^.*\[(\d+|%i%)\].*/','\\1',$pref);
			$this->form_existsInput($pref);
			$rand = rand();
?>
	<div id='config-template-<?=$prefId?>' style='display: none;'>
		<?= $this->singleFieldHTML($pref,'###TEMPLATE_ID###',null);?>
	</div>
	<script type='text/javascript'>
		CustomSearch.create('<?=$prefId?>');
	</script>
	<div id='config-form-<?=$prefId?>'>
<?
			$defaults=array();
			unset($values['exists']);
			if(!$values) $values = array(1=>$defaults);
			foreach($values as $id => $val){
				echo "<div id='config-form-$prefId-$id'>".$this->singleFieldHTML($pref,$id,$val)."</div>";
			}
?>
	</div>

	<br/><a href='#' onClick="return CustomSearch['<?=$prefId?>'].add();">Add Field</a>
<?
		}
		function singleFieldHTML($pref,$id,$values){
			$prefId = preg_replace('/^.*\[(\d+|%i%)\].*/','\\1',$pref);
			$pref = $pref."[$id]";
			$htmlId = $pref."[exists]";
			$output = "<input type='hidden' name='$htmlId' value='1'/>";
			$titles="<th>Field</th>";
			$inputs="<td><input type='text' name='$pref"."[name]' value='$values[name]'/></td>";
			foreach(array('joiner'=>'Table','comparison'=>'Compare','input'=>'Widget') as $k=>$v){
				$dd = new AdminDropDown($pref."[$k]",$values[$k],$this->getClasses($k));
				$titles.="<th>".$v."</th>";
				$inputs.="<td>".$dd->getInput()."</td>";
			}
			$output.="<table><tr>$titles</tr><tr>$inputs</tr></table>";
			$output.="<a href='#' onClick=\"return CustomSearch['$prefId'].remove('$id');\">Remove Field</a>";
			return $output;
		}

		function getRootURL(){
			return WP_CONTENT_URL .'/plugins/' .  dirname(plugin_basename(__FILE__) ) . '/';
		}
		function print_admin_scripts($params){
			$jsRoot = $this->getRootURL().'js/';
			foreach(array('CustomSearch.js') as $file){
				echo "<script src='$jsRoot/$file' ></script>";
			}
		}

		function getJoiners(){
			return $this->getClasses('joiner');
		}
		function getComparisons(){
			return $this->getClasses('comparison');
		}
		function getInputTypes(){
			return $this->getClasses('input');
		}
		function getClasses($type){
			global $CustomSearchFieldTypes;
			if(!$CustomSearchFieldTypes){
				$CustomSearchFieldTypes = array(
					"joiner"=>array(
						"CustomFieldJoiner" => "Custom Field",
						"CategoryJoiner" => "Category" 
					),
					"input"=>array(
						"TextInput" => "Text Input",
						"DropDownField" => "Drop Down",
						"DropDownFromValues" => "Drop Down (DB Values)",
						"RadioButtonInput" => "Radio Button",
						"RadioButtonFromValues" => "Radio Button (DB Values)",
					),
					"comparison"=>array(
						"EqualComparison" => "Equals",
						"RangeComparison" => "Range",
						"LikeComparison" => "In" 
					)
				);
				$CustomSearchFieldTypes = apply_filters('custom_search_get_classes',$CustomSearchFieldTypes);
			}
			return $CustomSearchFieldTypes[$type];
		}
	}
	global $CustomSearchFieldTypes;
	$CustomSearchFieldTypes = array();

	class AdminDropDown extends DropDownField {
		function DropDown($name,$value,$options){
			$this->__construct($name,$value,$options);
		}
		function __construct($name,$value,$options){
			parent::__construct($options);
			$this->name = $name;
			$this->value = $value;
		}
		function getHTMLName(){
			return $this->name;
		}
		function getValue(){
			return $this->value;
		}
		function getInput(){
			return parent::getInput($this->name,null);
		}
	}

