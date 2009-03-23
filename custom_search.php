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
			DB_CustomSearch_Widget::__construct($params);
		}
		function __construct($params=array()){
			parent::__construct('Configurable',$params);
			add_action('admin_print_scripts', array(&$this,'print_admin_scripts'), 90);
		}
		function init(){
			new DB_CustomSearch_Widget();
		}

		function getInputs($params = false){
			if(is_array($params)){
				$id = $params['widget_id'];
			} else {
				$id = $params;
			}
			
			global $CustomSearchFieldInputs;
			if(!$CustomSearchFieldInputs[$id]){
			
				$config = $this->getConfig($id);

				$inputs = array();
				$nonFields = $this->getNonInputFields();
				if($config)
				foreach($config as $k=>$v){
					if(in_array($k,$nonFields)) continue;
					if(!(class_exists($v['input']) && class_exists($v['comparison']) && class_exists($v['joiner']))) continue;
					$inputs[] =  new CustomSearchField($v);

				}
				$CustomSearchFieldInputs[$id]=$inputs;
			}
			return $CustomSearchFieldInputs[$id];
		}
		function getTitle($params){
			$config = $this->getConfig($params['widget_id']);
			return $config['name'];
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

<?
			foreach($this->getClasses('input') as $class=>$desc) {
				if(class_exists($class))
					$form = new $class();
				else $form = false;
				if(method_exists($form,'getConfigForm')){
					if($form = $form->getConfigForm($pref.'[###TEMPLATE_ID###]',array('name'=>'###TEMPLATE_NAME###'))){
?>
	<div id='config-input-templates-<?=$class?>-<?=$prefId?>' style='display: none;'>
		<?=$form?>
	</div>
		
<?					}
				}
			}
 ?>
	<script type='text/javascript'>
		CustomSearch.create('<?=$prefId?>');
	</script>
	<div id='config-form-<?=$prefId?>'>
		<label for='<?=$prefId?>[name]'>Search Title</label><input type='text' class='form-title-input' id='<?=$prefId?>[name]' name='<?=$pref?>[name]' value='<?=$values['name']?>'/>
<?
			$defaults=array();
			if(!$values) $values = array(1=>$defaults);
			$nonFields = $this->getNonInputFields();
			foreach($values as $id => $val){
				if(in_array($id,$nonFields)) continue;
				echo "<div id='config-form-$prefId-$id'>".$this->singleFieldHTML($pref,$id,$val)."</div>";
			}
?>
	</div>

	<br/><a href='#' onClick="return CustomSearch['<?=$prefId?>'].add();">Add Field</a>
<?
		}

		function getNonInputFields(){
			return array('exists','name');
		}
		function singleFieldHTML($pref,$id,$values){
			$prefId = preg_replace('/^.*\[(\d+|%i%)\].*/','\\1',$pref);
			$pref = $pref."[$id]";
			$htmlId = $pref."[exists]";
			$output = "<input type='hidden' name='$htmlId' value='1'/>";
			$titles="<th>DB Field</th><th>Label</th>";
			$inputs="<td><input type='text' name='$pref"."[name]' value='$values[name]' class='form-field-title'/></td>";
			$inputs.="<td><input type='text' name='$pref"."[label]' value='$values[label]' class='form-field-title'/></td>";
			$output.="<table class='form-field-table'><tr>$titles</tr><tr>$inputs</tr></table>";
			$inputs='';$titles='';
			foreach(array('joiner'=>'Table','comparison'=>'Compare','input'=>'Widget') as $k=>$v){
				$dd = new AdminDropDown($pref."[$k]",$values[$k],$this->getClasses($k),array('onChange'=>'CustomSearch['.$prefId.'].updateOptions('.$id.')'));
				$titles.="<th>".$v."</th>";
				$inputs.="<td>".$dd->getInput()."</td>";
			}
			$output.="<table class='form-field-table'><tr>$titles</tr><tr>$inputs</tr></table>";
			$titles="<th>Numeric</th><th>Widget Config</th>";
			$inputs="<td><input type='checkbox' ".($values['numeric']?"checked='true'":"")." name='$pref"."[numeric]'/></td>";

			if(class_exists($widgetClass = $values['input'])){
				$widget = new $widgetClass();
				if(method_exists($widget,'getConfigForm'))
					$widgetConfig=$widget->getConfigForm($pref,$values);
			}


			$inputs.="<td><div id='$this->id"."-$prefId"."-$id"."-widget-config'>$widgetConfig</div></td>";
			$output.="<table class='form-field-table'><tr>$titles</tr><tr>$inputs</tr></table>";
			$output.="<a href='#' onClick=\"return CustomSearch['$prefId'].remove('$id');\">Remove Field</a>";
			return "<div class='field-wrapper'>$output</div>";
		}

		function getRootURL(){
			return WP_CONTENT_URL .'/plugins/' .  dirname(plugin_basename(__FILE__) ) . '/';
		}
		function print_admin_scripts($params){
			$jsRoot = $this->getRootURL().'js/';
			$cssRoot = $this->getRootURL().'css/';
			foreach(array('CustomSearch.js') as $file){
				echo "<script src='$jsRoot/$file' ></script>";
			}
			echo "<link rel='stylesheet' href='$cssRoot/admin.css' >";
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
						"TextField" => "Text Input",
						"DropDownField" => "Drop Down",
						"DropDownFromValues" => "Drop Down (DB Values)",
						"RadioButtonField" => "Radio Button",
						"RadioButtonFromValues" => "Radio Button (DB Values)",
					),
					"comparison"=>array(
						"EqualComparison" => "Equals",
						"LikeComparison" => "In",
						"LessThanComparison" => "Less Than",
						"MoreThanComparison" => "More Than",
						"RangeComparison" => "Range",
					)
				);
				$CustomSearchFieldTypes = apply_filters('custom_search_get_classes',$CustomSearchFieldTypes);
			}
			return $CustomSearchFieldTypes[$type];
		}
	}
	global $CustomSearchFieldInputs;
	$CustomSearchFieldInputs = array();
	global $CustomSearchFieldTypes;
	$CustomSearchFieldTypes = array();

	class AdminDropDown extends DropDownField {
		function AdminDropDown($name,$value,$options,$params=array()){
			AdminDropDown::__construct($name,$value,$options,$params);
		}
		function __construct($name,$value,$options,$params=array()){
			parent::__construct($options,$params);
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

