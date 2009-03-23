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
		<?= $this->singleFieldHTML($pref,'###TEMPLATE_ID###');?>
	</div>
	<script type='text/javascript'>
		CustomSearch.create('<?=$prefId?>');
	</script>
	<div id='config-form-<?=$prefId?>'>
		<h1>Form</h1>
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
		function singleFieldHTML($pref,$id){
			$prefId = preg_replace('/^.*\[(\d+|%i%)\].*/','\\1',$pref);
			$htmlId = $pref."[$id][exists]";
return "		<h1>Field $id</h1>
		<a href='#' onClick=\"return CustomSearch['$prefId'].remove('$id');\">Remove Field</a>
			<input type='hidden' name='$htmlId' value='1'/>";
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
	}

