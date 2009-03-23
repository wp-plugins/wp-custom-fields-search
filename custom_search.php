<?
/*
* Plugin Name: Custom Search Forms
* Plugin URI: http://wp.don-benjamin.co.uk/custom_search
* Description: Allows user to build custom search forms.
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
			return array('exists'=>1);
		}
		function form_outputForm($old,$pref){
			$pref = preg_replace('/^.*\[(\d+)\].*/','\\1',$pref);
?>
	<div id='config-template-<?=$pref?>' style='display: none;'>
		<h1>Field ###TEMPLATE_ID###</h1>
		<a href='#' onClick="CustomSearch[<?=$pref?>].remove('###TEMPLATE_ID###');">Remove Field</a>
		<script type='text/javascript'>
			CustomSearch.create('<?=$pref?>');
		</script>
	</div>
	<div id='config-form-<?=$pref?>'>
		<h1>Form</h1>
	</div>

	<br/><a href='#' onClick="CustomSearch[<?=$pref?>].add();">Add Field</a>
<?
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

