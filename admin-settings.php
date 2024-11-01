<?php
/**
 * Texy plugin - settings page in  administration
 */

if (@$_GET['updated'] AND TexyPlugin::$settings['visual_editor_disabled'])
	TexyPlugin::disable_visual_editor();

// maybe clear cache now
if (isset($_POST[TexyPlugin::$abbr . '_submit_clear_cache'])) {

	$result = TexyPlugin::clearCache();
	
	if ($result === false) {
	?><div class="error"><p><?php echo __('The cache couldn\'t be cleared. Maybe the folder is not writeable.', TexyPlugin::$abbr); ?></p></div><?php
	} 
	else {
	?><div class="updated"><p><?php printf (__('Cache was successfuly cleared. (%1s files was removed.)', TexyPlugin::$abbr), $result); ?></p></div><?php
	}

}

?>
<div class="wrap" id="<?php echo TexyPlugin::$abbr; ?>">


<?php
// echo '<h3>Temp :-)</h3>';

// echo 'http://localhost:8888/Projects/wpp%20texy/wp/wp-content/uploads/'.'<br />'.
// TexyPlugin::$uploads_dir_url;
// echo get_bloginfo('url');

?>



	<h2><?php echo TexyPlugin::$full_name; ?></h2>
	
	<form method="post" action="options.php">

		<h3>Main settings</h3>

		<?php $vars_to_save[] = 'heading' ?>		
  		<p><label>Top heading level <input type="text" name="<?php echo TexyPlugin::$abbr; ?>_heading" value="<?php echo TexyPlugin::$settings['heading'] ?>" size="1" /></label></p>

		<?php $vars_to_save[] = 'visual_editor_disabled' ?>
  		<p><label><input type="checkbox" name="<?php echo TexyPlugin::$abbr; ?>_visual_editor_disabled" <?php checked(TexyPlugin::$settings['visual_editor_disabled'], 'on') ?> /> Force disable visual editor for all users</label></p>

		<?php $vars_to_save[] = 'cache_enabled' ?>
		<p><label><input type="checkbox" name="<?php echo TexyPlugin::$abbr; ?>_cache_enabled" <?php checked(TexyPlugin::$settings['cache_enabled'], 'on') ?> /> Use Cache</label></p>
		
		<p class="submit">
			<?php wp_nonce_field('update-options') ?>
			<input type="hidden" name="action" value="update" />
			<input type="hidden" name="page_options" value="<?php foreach ($vars_to_save as $var) echo TexyPlugin::$abbr .'_'. $var .','; ?>" />
			<input type="submit" name="<?php echo TexyPlugin::$abbr ?>_submit_update" value="<?php _e('Save Changes') ?>" class="button button-primary" />
		</p>

	</form>


	<hr />
	
	<h3>Service</h3>
	
	<form method="post">	
		<p class="submit">
			<?php wp_nonce_field('update-options') ?>
			<input type="submit" name="<?php echo TexyPlugin::$abbr ?>_submit_clear_cache" value="<?php _e('Clear the cache now', TexyPlugin::$abbr) ?>" class="button" />
		</p>

	</form>

	<hr />

	<h3><?php _e('Report', TexyPlugin::$abbr) ?></h3>

	<table>
		<tr>
			<th>PHP version</th>
			<td><?php echo PHP_VERSION ?></td>
		</tr>
		<tr>
			<th>WP version</th>
			<td><?php global $wp_version; echo $wp_version ?></td>
		</tr>
		<tr>
			<th>Texy version</th>
			<?php require_once TexyPlugin::$dir_path . 'texy.compact.php';?>
			<td><?php echo TEXY_VERSION . ' (' . Texy::REVISION . ')'; ?></td>
		</tr>
		
	</table>

</div><!-- /wrap -->

<style type="text/css">

#<?php echo TexyPlugin::$abbr ?> th {text-align: left; padding-right: 1em;}

#<?php echo TexyPlugin::$abbr ?> th.ok {
	background-color:green;}

#<?php echo TexyPlugin::$abbr ?> textarea {
	font-family:monospace; font-size:90%;}

hr {background:none; border:0; border-bottom: 1px solid #ddd;}
</style>