<div class='presets-selector'><ul>
	<h4>Select Preset</h4>
<?php	foreach($presets as $key=>$name){ ?>
<li><a href='<?php echo $linkBase?>&selected-preset=<?php echo $key?>'><?php echo $name?></a></li>
<?php	}	?>
</ul></div>
<div class='presets-example-code'> 
	<h4>Template Code</h4>
To use this preset in your templates copy this code to the appropriate place in your template file:<br/>
	<?php if($preset=='preset-default') { ?> 
		<pre><code><?php echo htmlspecialchars("<?php if(function_exists('wp_custom_fields_search')) 
	wp_custom_fields_search(); ?>")?></code></pre>
	<?php } else { ?>
		<pre><code><?php echo htmlspecialchars("<?php if(function_exists('wp_custom_fields_search')) 
	wp_custom_fields_search('$preset'); ?>")?></code></pre>
	<?php } ?>
	<h4>Tag For Posts</h4>
To use this preset in your posts/pages copy this code to the appropriate place in your post/page:<br/>
	<?php if($preset=='preset-default') { ?> 
		<pre><code><?php echo htmlspecialchars("[wp-custom-fields-search]");?></pre></code>
	<?php } else { 
		$presetLabel = substr($preset,7);
	?>
		<pre><code><?php echo htmlspecialchars("[wp-custom-fields-search $presetLabel]");?></pre></code>
	<?php } ?>
</div>

<form method='post'><div class='searchforms-config-form'>
<?php echo $hidden?>
		<h4>Edit Preset "<?php echo $presets[$preset]?>"</h4>
		<?php $plugin->configForm($preset,$shouldSave) ?>
		<div class='options-controls'>
			<div class='options-button'>
				<input type='submit' value='Save Changes'/>
			</div>
			<div class='options-button'>
				<input type='submit' name='delete' value='Delete' onClick='return confirm("Are you sure you want to delete this preset?")'/>
			</div>
		</div>
</div></form>
