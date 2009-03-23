<div class='presets-selector'><ul>
	<h4>Select Preset</h4>
<?php	foreach($presets as $key=>$name){ ?>
<li><a href='<?php echo $linkBase?>&selected-preset=<?php echo $key?>'><?php echo $name?></a></li>
<?php	}	?>
</ul></div>
<div class='presets-example-code'> 
	<h4>Template Code</h4>
To use this preset in your templates use this code:<br/>
	<?php if($preset=='preset-default') { ?> 
		<code><?php echo htmlspecialchars("<?php wp_custom_fields_search(); ?>")?></code>
	<?php } else { ?>
		<code><?php echo htmlspecialchars("<?php wp_custom_fields_search('$preset'); ?>")?></code>
	<? } ?>
</div>

	<form method='post'><div class='searchforms-config-form'><input type='hidden' name='selected-preset' value='$preset'>
		<h4>Edit Preset "<?php echo $plugin->getConfig($preset,'name')?>"</h4>
		<?php $plugin->configForm($preset,$_POST['selected-preset']) ?>
	<input type='submit'/>
</div></form>
