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
* Plugin Name: WP Custom Search
* Plugin URI: http://www.don-benjamin.co.uk/wordpress-plugins/wp-custom-search
* Description: Allows admin to build custom search form.  Allows the site admin to configure multiple html inputs for different fields including custom fields.  Also provides an extensible mechanism for integrating with other plugins data structures.
* Version: 0.1
* Author: Don Benjamin
* Author URI: http://www.don-benjamin.co.uk/
* */
	require_once(dirname(__FILE__).'/extra_search_fields.php');

	//Add Widget for configurable search.
	add_action('plugins_loaded',array('DB_CustomSearch_Widget','init'));


	class DB_CustomSearch_Widget extends DB_Search_Widget {
		function DB_CustomSearch_Widget($params=array()){
			DB_CustomSearch_Widget::__construct($params);
		}
		function __construct($params=array()){
			parent::__construct('Custom Fields ',$params);
			add_action('admin_print_scripts', array(&$this,'print_admin_scripts'), 90);
			add_action('admin_menu', array(&$this,'plugin_menu'), 90);
			if(version_compare("2.7",$GLOBALS['wp_version'])>0) wp_enqueue_script('dimensions');
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
		function getDefaultConfig(){
			return array('name'=>'Site Search', 
				1=>array(
					'label'=>'Key Words',
					'input'=>'TextField',
					'comparison'=>'WordsLikeComparison',
					'joiner'=>'PostDataJoiner',
					'name'=>'all'
				),
				2=>array(
					'label'=>'Category',
					'input'=>'DropDownField',
					'comparison'=>'EqualsComparison',
					'joiner'=>'CategoryJoiner'
				),
			);
		}
		function form_outputForm($values,$pref){
			$defaults=$this->getDefaultConfig();
			$prefId = preg_replace('/^.*\[([^]]*)\]$/','\\1',$pref);
			$this->form_existsInput($pref);
			$rand = rand();
?>
	<div id='config-template-<?php echo $prefId?>' style='display: none;'>
	<?php 
		$templateDefaults = $defaults[1];
		$templateDefaults['label'] = 'Field ###TEMPLATE_ID###';
		echo  $this->singleFieldHTML($pref,'###TEMPLATE_ID###',$templateDefaults);
	?>
	</div>

<?php
			foreach($this->getClasses('input') as $class=>$desc) {
				if(class_exists($class))
					$form = new $class();
				else $form = false;
				if(compat_method_exists($form,'getConfigForm')){
					if($form = $form->getConfigForm($pref.'[###TEMPLATE_ID###]',array('name'=>'###TEMPLATE_NAME###'))){
?>
	<div id='config-input-templates-<?php echo $class?>-<?php echo $prefId?>' style='display: none;'>
		<?php echo $form?>
	</div>
		
<?php					}
				}
			}
 ?>
	<div id='config-form-<?php echo $prefId?>'>
<?php
			if(!$values) $values = $defaults;
			$maxId=0;
?>
		<label for='<?php echo $prefId?>[name]'>Search Title</label><input type='text' class='form-title-input' id='<?php echo $prefId?>[name]' name='<?php echo $pref?>[name]' value='<?php echo $values['name']?>'/>
<?php
			$nonFields = $this->getNonInputFields();
			foreach($values as $id => $val){
				$maxId = max($id,$maxId);
				if(in_array($id,$nonFields)) continue;
				echo "<div id='config-form-$prefId-$id'>".$this->singleFieldHTML($pref,$id,$val)."</div>";
			}
?>
	</div>

	<br/><a href='#' onClick="return CustomSearch.get('<?php echo $prefId?>').add();">Add Field</a>
	<script type='text/javascript'>
			CustomSearch.create('<?php echo $prefId?>','<?php echo $maxId?>');
<?php
	foreach($this->getClasses('joiner') as $joinerClass=>$desc){
		if(compat_method_exists($joinerClass,'getSuggestedFields')){
			$options = eval("return $joinerClass::getSuggestedFields();");
			$str = '';
			foreach($options as $i=>$v){
				$k=$i;
				if(is_numeric($k)) $k=$v;
				$options[$i] = json_encode(array('id'=>$k,'name'=>$v));
			}
			$str = '['.join(',',$options).']';
			echo "CustomSearch.setOptionsFor('$joinerClass',".$str.");\n";
		}elseif(eval("return $joinerClass::needsField();")){
			echo "CustomSearch.setOptionsFor('$joinerClass',[]);\n";
		}
	}
?>
	</script>
<?php
		}

		function getNonInputFields(){
			return array('exists','name');
		}
		function singleFieldHTML($pref,$id,$values){
			$prefId = preg_replace('/^.*\[([^]]*)\]$/','\\1',$pref);
			$pref = $pref."[$id]";
			$htmlId = $pref."[exists]";
			$output = "<input type='hidden' name='$htmlId' value='1'/>";
			$titles="<th>Label</th>";
			$inputs="<td><input type='text' name='$pref"."[label]' value='$values[label]' class='form-field-title'/></td><td><a href='#' onClick='return CustomSearch.get(\"$prefId\").toggleOptions(\"$id\");'>Show/Hide Config</a></td>";
			$output.="<table class='form-field-table'><tr>$titles</tr><tr>$inputs</tr></table>";
			$output.="<div id='form-field-advancedoptions-$prefId-$id' style='display: none'>";
			$inputs='';$titles='';
			$titles="<th>Data Field</th>";
			$inputs="<td><div id='form-field-dbname-$prefId-$id' class='form-field-title-div'><input type='text' name='$pref"."[name]' value='$values[name]' class='form-field-title'/></div></td>";
			$count=1;
			foreach(array('joiner'=>'Data Type','comparison'=>'Compare','input'=>'Widget') as $k=>$v){
				$dd = new AdminDropDown($pref."[$k]",$values[$k],$this->getClasses($k),array('onChange'=>'CustomSearch.get("'.$prefId.'").updateOptions("'.$id.'","'.$k.'")'));
				$titles="<th>".$v."</th>".$titles;
				$inputs="<td>".$dd->getInput()."</td>".$inputs;
				if(++$count==2){
					$output.="<table class='form-field-table form-class-$k'><tr>$titles</tr><tr>$inputs</tr></table>";
					$count=0;
					$inputs = $titles='';
				}
			}
			if($titles){
				$output.="<table class='form-field-table'><tr>$titles</tr><tr>$inputs</tr></table>";
				$inputs = $titles='';
			}
			$titles.="<th>Numeric</th><th>Widget Config</th>";
			$inputs.="<td><input type='checkbox' ".($values['numeric']?"checked='true'":"")." name='$pref"."[numeric]'/></td>";

			if(class_exists($widgetClass = $values['input'])){
				$widget = new $widgetClass();
				if(compat_method_exists($widget,'getConfigForm'))
					$widgetConfig=$widget->getConfigForm($pref,$values);
			}


			$inputs.="<td><div id='$this->id"."-$prefId"."-$id"."-widget-config'>$widgetConfig</div></td>";
			$output.="<table class='form-field-table'><tr>$titles</tr><tr>$inputs</tr></table>";
			$output.="</div>";
			$output.="<a href='#' onClick=\"return CustomSearch.get('$prefId').remove('$id');\">Remove Field</a>";
			return "<div class='field-wrapper'>$output</div>";
		}

		function getRootURL(){
			return WP_CONTENT_URL .'/plugins/' .  dirname(plugin_basename(__FILE__) ) . '/';
		}
		function print_admin_scripts($params){
			$jsRoot = $this->getRootURL().'js/';
			$cssRoot = $this->getRootURL().'css/';
			$scripts = array('Class.js','CustomSearch.js','flexbox/jquery.flexbox.js');
			foreach($scripts as $file){
				echo "<script src='$jsRoot/$file' ></script>";
			}
			echo "<link rel='stylesheet' href='$cssRoot/admin.css' >";
			echo "<link rel='stylesheet' href='$jsRoot/flexbox/jquery.flexbox.css' >";
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
						"PostDataJoiner" => "Post Field",
						"CustomFieldJoiner" => "Custom Field",
						"CategoryJoiner" => "Category",
						"TagJoiner" => "Tag",
					),
					"input"=>array(
						"TextField" => "Text Input",
						"DropDownField" => "Drop Down",
						"RadioButtonField" => "Radio Button",
					),
					"comparison"=>array(
						"EqualComparison" => "Equals",
						"LikeComparison" => "Phrase In",
						"WordsLikeComparison" => "Words In",
						"LessThanComparison" => "Less Than",
						"MoreThanComparison" => "More Than",
						"RangeComparison" => "Range",
					)
				);
				$CustomSearchFieldTypes = apply_filters('custom_search_get_classes',$CustomSearchFieldTypes);
			}
			return $CustomSearchFieldTypes[$type];
		}
		function plugin_menu(){
			add_options_page('Form Presets','Custom Fields Search',8,__FILE__,array(&$this,'presets_form'));
		}
		function presets_form(){
			echo "<h1>Search Presets Config</h1>";
			$presets = array();
			foreach(array_keys($this->getConfig()) as $key){
				if(strpos($key,'preset-')===0) {
					$presets[$key] = $key;
				}
			}
			if(!$preset = $_REQUEST['selected-preset']){
				$preset = 'preset-default';
			}
			if(!$presets[$preset]){
				$defaults = $this->getDefaultConfig();
				$options = $this->getConfig();
				$options[$preset] = $defaults;
				if($n = $_POST[$this->id][$preset]['name'])
					$options[$preset]['name'] = $n;
				elseif($preset=='preset-default')
					$options[$preset]['name'] = 'Default';
				else
					$options[$preset]['name'] = 'New Preset';
				update_option($this->id,$options);
				$presets[] = "$preset";
			}

			if($deleteAllPresets=false){
				$options = $this->getConfig();
				foreach($options as $key=>$v){
					if(strpos($key,'preset-')===0) unset($options[$key]);
				}
				update_option($this->id,$options);
			}

			$index = 1;
			while($presets["preset-p$index"]) $index++;
			$presets["preset-p$index"] = 'New Preset';

			$linkBase = $_SERVER['REQUEST_URI'];
			$linkBase = preg_replace("/&?selected-preset=[^&]*(&|$)/",'',$linkBase);
			foreach($presets as $key=>$name){
				if($n = $_POST[$this->id][$preset]['name'])
				$config = $this->getConfig($name);
				if($config) $name=$config['name'];
				if($n = $_POST[$this->id][$key]['name'])
					$name = $n;
				echo "<li><a href='$linkBase&selected-preset=$key'>Preset $name</a></li>";
			}

			echo "\n<form method='post'><input type='hidden' name='selected-preset' value='$preset'>\n";
			$this->configForm($preset,$_POST['selected-preset']);
			echo "<input type='submit'/>";
			echo "</form>";
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
			$params['options'] = $options;
			$params['id'] = $params['name'];
			parent::__construct($params);
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

if (!function_exists('json_encode'))
{
  function json_encode($a=false)
  {
    if (is_null($a)) return 'null';
    if ($a === false) return 'false';
    if ($a === true) return 'true';
    if (is_scalar($a))
    {
      if (is_float($a))
      {
        // Always use "." for floats.
        return floatval(str_replace(",", ".", strval($a)));
      }

      if (is_string($a))
      {
        static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
        return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
      }
      else
        return $a;
    }
    $isList = true;
    for ($i = 0, reset($a); $i < count($a); $i++, next($a))
    {
      if (key($a) !== $i)
      {
        $isList = false;
        break;
      }
    }
    $result = array();
    if ($isList)
    {
      foreach ($a as $v) $result[] = json_encode($v);
      return '[' . join(',', $result) . ']';
    }
    else
    {
      foreach ($a as $k => $v) $result[] = json_encode($k).':'.json_encode($v);
      return '{' . join(',', $result) . '}';
    }
  }
}
function compat_method_exists($class,$method){
	return method_exists($class,$method) || in_array(strtolower($method),get_class_methods($class));
}
