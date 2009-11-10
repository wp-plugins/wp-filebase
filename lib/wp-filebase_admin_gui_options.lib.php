<?php
wpfilebase_inclib('admin');
wpfilebase_inclib('output');

function wpfilebase_admin_options()
{
	global $wpdb;
		
	if(!current_user_can('manage_options'))
		wp_die(__('Cheatin&#8217; uh?'));
		
	wpfilebase_version_update_check();
	
	$action = ( !empty($_POST['action']) ? $_POST['action'] : ( !empty($_GET['action']) ? $_GET['action'] : '' ) );
	$messages = array();
	$errors = array();
	
	$options = get_option(WPFB_OPT_NAME);
	$option_fields = &wpfilebase_options();
	
	if(isset($_POST['reset']))
	{
		wpfilebase_inclib('setup');
		wpfilebase_reset_options();
		$messages[] = __('Options reseted.');		
		$options = get_option(WPFB_OPT_NAME);
	}
	elseif(isset($_POST['submit']))
	{		
		// cleanup
		foreach($option_fields as $opt_tag => $opt_data)
		{
			if(isset($_POST[$opt_tag]))
			{
				if(get_magic_quotes_gpc() == 1)
					$_POST[$opt_tag] = stripslashes($_POST[$opt_tag]);				
				$_POST[$opt_tag] = trim($_POST[$opt_tag]);
				
				if($opt_data['type'] == 'number')
					$_POST[$opt_tag] = intval($_POST[$opt_tag]);
			}
		}
		
		$_POST['download_base'] = trim($_POST['download_base'], '/');
		if(wpfilebase_wpcache_reject_uri($_POST['download_base'] . '/', $options['download_base'] . '/'))
			$messages[] = sprintf(__('/%s/ added to rejected URIs list of WP Super Cache.'), $_POST['download_base']);
		
		if(!empty($_POST['allow_srv_script_upload']))
			$messages[] = __('WARNING: Script upload enabled!');
		
		$attach_template = stripslashes($_POST['template_file']);
		if(!empty($attach_template) && (empty($options['template_file_parsed']) || $attach_template != $options['template_file']))
		{
			wpfilebase_inclib('template');
			$start_time = microtime(true);
			$attach_template = wpfilebase_parse_template($attach_template);
			$result = wpfilebase_check_template($attach_template);
			$time_span = (microtime(true) - $start_time);
			
			if(!$result['error']) {
				$options['template_file_parsed'] = $attach_template;
				$messages[] = __('Template successfully parsed.' /* in %f ms.'*/);
			} else {
				$errors[] = sprintf(__('Could not parse template: error (%s) in line %s.'), $result['msg'], $result['line']);
			}
		}
		
		// save options
		foreach($option_fields as $opt_tag => $opt_data)
		{
			$val = isset($_POST[$opt_tag]) ? $_POST[$opt_tag] : '';
			$options[$opt_tag] = stripslashes(trim($val));
		}
		
		update_option(WPFB_OPT_NAME, $options);
		
		wpfilebase_protect_upload_path();
		
		if(count($errors) == 0)
			$messages[] = __('Options updated.');
	}
	
	$action_uri = $_SERVER['PHP_SELF'] . '?page=' . $_GET['page'] . '&amp;updated=true';

	if (!empty($messages)) :
	$message = '';
	foreach($messages as $msg)
		$message .= '<p>' . $msg . '</p>';
?>
<div id="message" class="updated fade"><?php echo $message; ?></div>
<?php
	endif;

	if (!empty($errors)) : 
	$error = '';
	foreach($errors as $err)
		$error .= '<p>' . $err . '</p>';
?>
<div id="message" class="error fade"><?php echo $error; ?></div>
<?php endif; ?>

<div class="wrap">
<h2><?php echo WPFB_PLUGIN_NAME; echo ' '; _e("Options"); ?></h2>

<form method="post" action="<?php echo $action_uri; ?>" name="wpfilebase-options">
	<?php wp_nonce_field('update-options'); ?>
	<p class="submit">
	<input type="submit" name="submit" value="<?php _e('Save Changes') ?>" class="button-primary" />
	<input type="submit" id="deletepost" name="reset" value="<?php _e('Reset options') ?>" onclick="return confirm('<?php _e('Are you sure?'); ?>')" class="button delete" />
	</p>
	<table class="form-table">	
	<?php
	$page_option_list = '';
	
	foreach($option_fields as $opt_tag => $field_data)
	{	
		$opt_val = $options[$opt_tag];
		echo "\n".'<tr valign="top">'."\n".'<th scope="row">' . $field_data['title']. '</th>'."\n".'<td>';
		$style_class = '';
		if(!empty($field_data['class']))
			$style_class .= ' class="'.$field_data['class'].'"';
		if(!empty($field_data['style']))
			$style_class .= ' style="'.$field_data['style'].'"';
		switch($field_data['type'])
		{
			case 'text':
			case 'number':
			case 'checkbox':
				echo '<input name="' . $opt_tag . '" type="' . $field_data['type'] . '" id="' . $opt_tag . '"';
				echo ((!empty($field_data['class'])) ? ' class="' . $field_data['class'] . '"' : '');
				if($field_data['type'] == 'checkbox') {
					echo ' value="1" ';
					checked('1', $opt_val);
				} elseif($field_data['type'] == 'number')
					echo ' value="' . intval($opt_val) . '" size="5"';
				else {
					echo ' value="' . esc_attr($opt_val) . '"';
					if(isset($field_data['size']))
						echo ' size="' . (int)$field_data['size'] . '"';
				}
				echo $style_class . ' />';
				break;
				
			case 'textarea':
				$code_edit = (strpos($opt_tag, 'template_') !== false || (isset($field_data['class']) && strpos($field_data['class'], 'code') !== false));
				$nowrap = !empty($field_data['nowrap']);
				echo '<textarea name="' . $opt_tag . '" id="' . $opt_tag . '"';
				if($nowrap || $code_edit) {
					echo ' cols="100" wrap="off" style="width: 100%;' . ($code_edit ?  'font-size: 9px;' : '') . '"';
				} else
					echo ' cols="50"';
				echo ' rows="' . ($code_edit ? 20 : 5) . '"';
				echo $style_class;
				echo '>';
				echo wp_specialchars($opt_val);
				echo '</textarea>';
				break;
			case 'select':
				echo '<select name="' . $opt_tag . '" id="' . $opt_tag . '">';
				foreach($field_data['options'] as $opt_v => $opt_n)
					echo '<option value="' . esc_attr($opt_v) . '"' . (($opt_v == $opt_val) ? ' selected="selected" ' : '') . $style_class . '>' . (!is_numeric($opt_v) ? (wp_specialchars($opt_v) . ': ') : '') . wp_specialchars($opt_n) . '</option>';
				echo '</select>';
				break;
		}
		
		if(!empty($field_data['unit']))
			echo ' ' . $field_data['unit'];
			
		if(!empty($field_data['desc']))
			echo "\n".'<br />' . $field_data['desc'];
		echo "\n</td>\n</tr>";		
		$page_option_list .= $opt_tag . ',';
	}
	?>
	</table>
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="page_options" value="<?php echo $page_option_list; ?>" />
	<p class="submit">
	<input type="submit" name="submit" value="<?php _e('Save Changes') ?>" class="button-primary" />
	<input type="submit" id="deletepost" name="reset" class="button delete" value="<?php _e('Reset options') ?>" onclick="return confirm('<?php _e('Are you sure?'); ?>')" />
	</p>
</form>
</div>	<!-- wrap -->	
<?php
}
?>