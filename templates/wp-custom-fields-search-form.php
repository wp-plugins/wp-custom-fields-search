<?php if($title && !(@$params['noTitle'])){
	echo $params['before_title'].$title.$params['after_title'];
}?>
	<form method='get' class='<?php echo $formCssClass?>' action='<?php echo $formAction?>'>
<?php echo $hidden ?>
		<div class='searchform-params'>
<?php		foreach($inputs as $input){?>
<div class='<?php echo $input->getCSSClass()?>'><?php echo $input->getInput()?></div>
<?php		}?>
</div>
<div class='searchform-controls'>

<input type='submit' name='search' value='Search'/>
</div>
<div class='searchform-spoiler'><a href='http://www.don-benjamin.co.uk/wordpress-plugins/custom-search/'>Powered By DB</a></div>
</form>
