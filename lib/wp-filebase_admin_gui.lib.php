<?php

wpfilebase_inclib('admin');
wpfilebase_inclib('output');

function wpfilebase_admin_manage()
{
	global $wpdb, $user_ID;
	
	$action = ( !empty($_POST['action']) ? $_POST['action'] : ( !empty($_GET['action']) ? $_GET['action'] : '' ) );

	$clean_uri = remove_query_arg(array('message', 'action', 'file_id', 'cat_id' /* , 's'*/)); // keep search keyword
	
	// switch simple/extended form
	if(isset($_GET['exform']))
	{
		$exform = (!empty($_GET['exform']) && $_GET['exform'] == 1);
		update_user_option($user_ID, WPFB_OPT_NAME . '_exform', $exform); 
	} else {
		$exform = (bool)get_user_option(WPFB_OPT_NAME . '_exform');
	}
	
	wpfilebase_version_update_check();
		
	switch($action)
	{
		case 'updatecat':
			$cat_id = (int)$_POST['cat_id'];
			$update = true;
		case 'addcat':
			$update = !empty($update);
			if ( !current_user_can('manage_categories') )
				wp_die(__('Cheatin&#8217; uh?'));
			
			$result = wpfilebase_insert_category($_POST);
			if(isset($result['error']) && $result['error']) {
				$message = $result['error'];
			} else {
				$message = __($update?'Category updated.':'Category added.');
			}
			
			//wp_redirect($clean_uri . '&action=manage_cats&message=' . urlencode($message));
			
		case 'manage_cats':		
			if(!current_user_can('manage_categories'))
				wp_die(__('Cheatin&#8217; uh?'));
				
			if(!empty($_POST['deleteit']))
			{
				foreach ( (array) $_POST['delete'] as $cat_id ) {
					if(is_object($cat = WPFilebaseCategory::get_category($cat_id)))
						$cat->delete();
				}
			}
?>

<div class="wrap">
	<h2><?php
	printf(__('Manage Categories (<a href="%s">add new</a>)'), '#addcat');
	if ( isset($_GET['s']) && $_GET['s'] )
		printf( '<span class="subtitle">' . __('Search results for &#8220;%s&#8221;') . '</span>', wp_specialchars(stripslashes($_GET['s'])));
	?></h2>

	<p><?php echo '<a href="' . $clean_uri . '" class="button">' . __('Go back') . '</a>'; ?></p>

	<?php if ( !empty($message) ) : ?><div id="message" class="updated fade"><p><?php echo $message; ?></p></div><?php endif; ?> 

	<form class="search-form topmargin" action="" method="get"><p class="search-box">
		<input type="hidden" value="<?php echo $_GET['page']; ?>" name="page" />
		<input type="hidden" value="<?php echo $_GET['action']; ?>" name="action" />
		<label class="hidden" for="category-search-input"><?php _e('Search Categories'); ?>:</label>
		<input type="text" class="search-input" id="category-search-input" name="s" value="<?php _admin_search_query(); ?>" />
		<input type="submit" value="<?php _e( 'Search Categories' ); ?>" class="button" />
	</p></form>	
	
	<br class="clear" />
	
	<form id="posts-filter" action="" method="post">
		<div class="tablenav">
			<?php
			$pagenum = isset($_GET['pagenum']) ? absint($_GET['pagenum']) : 0;
			if ( empty($pagenum) )
				$pagenum = 1;
			if( !isset($catsperpage) || $catsperpage < 0 )
				$catsperpage = 20;
				
			$pagestart = ($pagenum - 1) * $catsperpage;

			$extra_sql = '';
			if(!empty($_GET['s']))
			{
				$s = $wpdb->escape(trim(stripslashes($_GET['s'])));
				$extra_sql .= "WHERE cat_name LIKE '%$s%' OR cat_description LIKE '%$s%' OR cat_folder LIKE '%$s%' ";
			}
			
			if(!empty($_GET['order']) && in_array($_GET['order'], array_keys(get_class_vars('WPFilebaseCategory'))))
				$extra_sql .= "ORDER BY " . $_GET['order'] . " " . (!empty($_GET['desc']) ? "DESC" : "ASC");		

			$cats = &WPFilebaseCategory::get_categories($extra_sql . " LIMIT $pagestart, $catsperpage");

			$page_links = paginate_links( array(
				'base' => add_query_arg( 'pagenum', '%#%' ),
				'format' => '',
				'total' => ceil(count(WPFilebaseCategory::get_categories($extra_sql)) / $catsperpage),
				'current' => $pagenum
			));

			if ( $page_links )
				echo "<div class='tablenav-pages'>$page_links</div>";
			?>

			<div class="alignleft"><input type="submit" value="<?php _e('Delete'); ?>" name="deleteit" class="button delete" /><?php wp_nonce_field('bulk-categories'); ?></div>
			<br class="clear" />
		</div>
	
		<br class="clear" />

		<table class="widefat">
			<thead>
			<tr>
				<th scope="col" class="check-column"><input type="checkbox" /></th>
				<th scope="col"><a href="<?php echo wpfilebase_admin_table_sort_link('cat_name') ?>"><?php _e('Name') ?></a></th>
				<th scope="col"><a href="<?php echo wpfilebase_admin_table_sort_link('cat_description') ?>"><?php _e('Description') ?></a></th>
				<th scope="col" class="num"><a href="<?php echo wpfilebase_admin_table_sort_link('cat_files') ?>"><?php _e('Files') ?></th>
				<th scope="col"><?php _e('Category Parent') ?></th>
			</tr>
			</thead>
			<tbody id="the-list" class="list:cat">
			<?php
			foreach($cats as $cat_id => $cat)
			{
				$parent_cat = & $cat->get_parent();
			?>
			<tr id='cat-<?php echo $cat_id ?>'>
				<th scope='row' class='check-column'><input type='checkbox' name='delete[]' value='<?php echo $cat_id ?>' /></th>
				<td><a class='row-title' href='<?php echo $clean_uri; ?>&amp;action=editcat&amp;cat_id=<?php echo $cat_id ?>' title='&quot;<?php echo attribute_escape($cat->cat_name); ?>&quot; bearbeiten'><?php echo attribute_escape($cat->cat_name); ?></a></td>
				<td><?php echo wp_specialchars($cat->cat_description) ?></td>
				<td class='num'><?php echo $cat->cat_files ?></td>
				<td><?php echo wp_specialchars($parent_cat->cat_name) ?></td>
			</tr>
			<?php } ?>
			</tbody>
		</table>
		<div class="tablenav"><?php if ( $page_links ) { echo "<div class='tablenav-pages'>$page_links</div>"; } ?></div>
	</form>
	<br class="clear" />
	
	<?php if ( current_user_can('manage_categories') ) : ?>
		<p><?php _e('<strong>Note:</strong><br />Deleting a category does not delete the files in that category. Instead, files that were assigned to the deleted category are set to the parent category.') ?></p><?php
		wpfilebase_admin_form('cat');
		endif;
	?>	
</div> <!-- wrap -->
<?php
	break;
	
		case 'updatefile':
			$file_id = (int)$_POST['file_id'];
			$update = true;			
		case 'addfile':
		
			if(empty($update))
				$update = false;
			if ( !current_user_can('upload_files') )
				wp_die(__('Cheatin&#8217; uh?'));				
		
			foreach ( array('aa', 'mm', 'jj', 'hh', 'mn') as $timeunit ) {
				if ( !empty($_POST['hidden_' . $timeunit] ) && $_POST['hidden_' . $timeunit] != $_POST[$timeunit] ) {
					$edit_date = true;
					break;
				}
			}
			
			if(!empty($edit_date)) {
				extract($_POST);
				$jj = ($jj > 31 ) ? 31 : $jj;
				$hh = ($hh > 23 ) ? $hh -24 : $hh;
				$mn = ($mn > 59 ) ? $mn -60 : $mn;
				$ss = ($ss > 59 ) ? $ss -60 : $ss;
				$_POST['file_date'] =  sprintf( "%04d-%02d-%02d %02d:%02d:%02d", $aa, $mm, $jj, $hh, $mn, $ss );
			} elseif(!$update) {
				// if new and not date take current time
				$_POST['file_date'] =  current_time('mysql');
			}
			
			$result = wpfilebase_insert_file(array_merge($_POST, $_FILES));
			if(isset($result['error']) && $result['error']) {
				$message = $result['error'];
			} else {
				$message = __($update?'File updated.':'File added.');
			}

		case 'manage_files':
		
			if(!current_user_can('upload_files'))
				wp_die(__('Cheatin&#8217; uh?'));
				
			if(!empty($_POST['deleteit']))
			{
				foreach ( (array)$_POST['delete'] as $file_id ) {					
					if(is_object($file = WPFilebaseFile::get_file($file_id)))
						$file->remove();
				}
			}
?>
<div class="wrap">

	<h2><?php
	printf(__('Manage Files (<a href="%s">add new</a>)'), '#addfile');
	if ( isset($_GET['s']) && $_GET['s'] )
		printf( '<span class="subtitle">' . __('Search results for &#8220;%s&#8221;') . '</span>', wp_specialchars(stripslashes($_GET['s'])));
	?></h2>

	<p><?php echo '<a href="' . $clean_uri . '" class="button">' . __('Go back') . '</a>'; ?></p>

	<?php if ( !empty($message) ) : ?><div id="message" class="updated fade"><p><?php echo $message; ?></p></div><?php endif; ?> 

	<form class="search-form topmargin" action="" method="get"><p class="search-box">
			<input type="hidden" value="<?php echo $_GET['page']; ?>" name="page" />
			<input type="hidden" value="<?php echo $_GET['action']; ?>" name="action" />
			<label class="hidden" for="file-search-input"><?php _e('Search Files'); ?>:</label>
			<input type="text" class="search-input" id="file-search-input" name="s" value="<?php _admin_search_query(); ?>" />
			<input type="submit" value="<?php _e( 'Search Files' ); ?>" class="button" />
	</p></form>
	
	<br class="clear" />

	<form id="posts-filter" action="" method="post">
		<div class="tablenav">
			<?php
			$pagenum = isset($_GET['pagenum']) ? absint($_GET['pagenum']) : 0;
			if ( empty($pagenum) )
				$pagenum = 1;
			if( !isset($filesperpage) || $filesperpage < 0 )
				$filesperpage = 20;
				
			$pagestart = ($pagenum - 1) * $filesperpage;

			$extra_sql = '';
			if(!empty($_GET['s']))
			{
				$s = $wpdb->escape(trim(stripslashes($_GET['s'])));
				$extra_sql .= "WHERE file_name LIKE '%$s%' 
				OR file_thumbnail LIKE '%$s%'
				OR file_display_name LIKE '%$s%'
				OR file_description LIKE '%$s%'
				OR file_requirement LIKE '%$s%'
				OR file_version LIKE '%$s%'
				OR file_author LIKE '%$s%'
				OR file_language LIKE '%$s%'
				OR file_platform LIKE '%$s%'
				OR file_license LIKE '%$s%'	";
			}
		
			
			if(!empty($_GET['order']) && in_array($_GET['order'], array_keys(get_class_vars('WPFilebaseFile'))))
				$extra_sql .= "ORDER BY " . $_GET['order'] . " " . (!empty($_GET['desc']) ? "DESC" : "ASC");			

			$files = &WPFilebaseFile::get_files($extra_sql . " LIMIT $pagestart, $filesperpage");

			$page_links = paginate_links( array(
				'base' => add_query_arg( 'pagenum', '%#%' ),
				'format' => '',
				'total' => ceil(count(WPFilebaseFile::get_files($extra_sql)) / $filesperpage),
				'current' => $pagenum
			));

			if ( $page_links )
				echo "<div class='tablenav-pages'>$page_links</div>";
			?>
			<div class="alignleft">
				<input type="submit" value="<?php _e('Delete'); ?>" name="deleteit" class="button delete" />
				<?php wp_nonce_field('bulk-files'); ?>
			</div>
		</div> <!-- tablenav -->
		
		<br class="clear" />
		
		<table class="widefat">
			<thead>
			<tr>
				<th scope="col" class="check-column"><input type="checkbox" /></th>
				<th scope="col"><a href="<?php echo wpfilebase_admin_table_sort_link('file_display_name') ?>"><?php _e('Name') ?></th>	
				<th scope="col"><a href="<?php echo wpfilebase_admin_table_sort_link('file_name') ?>"><?php _e('Filename') ?></th>    
				<th scope="col"><a href="<?php echo wpfilebase_admin_table_sort_link('file_size') ?>"><?php _e('Size') ?></th>    		
				<th scope="col"><a href="<?php echo wpfilebase_admin_table_sort_link('file_description') ?>"><?php _e('Description') ?></th>
				<th scope="col"><a href="<?php echo wpfilebase_admin_table_sort_link('file_category') ?>"><?php _e('Category') ?></th>
				<th scope="col" class="num"><a href="<?php echo wpfilebase_admin_table_sort_link('file_hits') ?>"><?php _e('Hits') ?></th>
				<th scope="col"><a href="<?php echo wpfilebase_admin_table_sort_link('file_last_dl_time') ?>"><?php _e('Last download') ?></th>
				<!-- TODO <th scope="col" class="num"><a href="<?php echo wpfilebase_admin_table_sort_link('file_') ?>"><?php _e('Rating') ?></th> -->
			</tr>
			</thead>
			<tbody id="the-list" class="list:file wpfilebase-list">
			<?php
				foreach($files as $file_id => $file)
				{
					if($file->file_ratings > 0)
						$rating = round((float)$file->file_rating_sum / (float)$file->file_ratings, 2);
					else
						$rating = '-';
						
					$cat = &$file->get_parent();
				?>
				<tr id='file-<?php echo $file_id ?>'<?php if($file->file_offline) { echo " class='offline'"; } ?>>
						   <th scope='row' class='check-column'><input type='checkbox' name='delete[]' value='<?php echo $file_id ?>' /></th>
							<td><a class='row-title' href='<?php echo $clean_uri; ?>&amp;action=editfile&amp;file_id=<?php echo $file_id ?>' title='&quot;<?php echo attribute_escape($file->file_display_name); ?>&quot; bearbeiten'><?php echo wp_specialchars($file->file_display_name); ?></a></td>
							<td><?php echo wp_specialchars($file->file_name) ?></td>
							<td><?php echo wpfilebase_format_filesize($file->file_size) ?></td>
							<td><?php echo wp_specialchars($file->file_description) ?></td>
							<td><?php echo wp_specialchars($cat->cat_name) ?></td>
							<td class='num'><?php echo $file->file_hits ?></td>
							<td><?php echo mysql2date(get_option('date_format'), $file->file_last_dl_time) ?></td>
							<!-- TODO <td class='num'><?php echo $rating ?></td> -->
							
				</tr>
				<?php
				}
			?>
			</tbody>
		</table>		
		<div class="tablenav"><?php if ( $page_links ) { echo "<div class='tablenav-pages'>$page_links</div>"; } ?></div>		
	</form>
	
	<br class="clear" />
</div> <!-- wrap -->

<?php

	unset($file);
	wpfilebase_admin_form('file', null, $exform);
	
	break; // manage_files
		
		case 'editfile':
			if(!current_user_can('upload_files'))
				wp_die(__('Cheatin&#8217; uh?'));
			$file_id = intval($_GET['file_id']);
			$file = &WPFilebaseFile::get_file($file_id);
			wpfilebase_admin_form('file', &$file);
			break;			
			
		case 'editcat':
			if ( !current_user_can('manage_categories') )
				wp_die(__('Cheatin&#8217; uh?'));
				
			$cat_id = (int)$_GET['cat_id'];
			$file_category = &WPFilebaseCategory::get_category($cat_id);
			wpfilebase_admin_form('cat', &$file_category);
			break;
			
			
		case 'sync':
			$result = wpfilebase_sync();
			$num_changed = $num_added = $num_errors = 0;
			foreach($result as $tag => $group)
			{
				if(empty($group) || !is_array($group) || count($group) == 0)
					continue;
					
				$t = str_replace('_', ' ', $tag);
				$t{0} = strtoupper($t{0});
				
				if($tag == 'added')
					$num_added += count($group);
				elseif($tag == 'error')
					$num_errors++;
				else
					$num_changed += count($group);
				
				echo '<h2>' . __($t) . '</h2><ul>';
				foreach($group as $item)
					echo '<li>' . (is_object($item) ? $item->get_rel_path() : $item) . '</li>';
				echo '</ul>';
			}
			
			echo '<p>';
			if($num_changed == 0 && $num_added == 0)
				_e('Nothing changed!');

			if($num_changed > 0)
				printf(__('Changed %d items.'), $num_changed);
				
			if($num_added > 0) {
				echo '<br />';
				printf(__('Added %d files.'), $num_added);
			}
			echo '</p>';
			
			if( $num_errors == 0)
				echo '<p>' . __('Filebase successfully synced.') . '</p>';
			echo '<p><a href="' . $clean_uri . '" class="button">' . __('Go back') . '</a></p>';			
			
		break; // sync
		
		
		default:
			$clean_uri = remove_query_arg('pagenum', $clean_uri);
			?>
			<div class="wrap">
				<h2>Filebase</h2>
				<?php
					$upload_dir = wpfilebase_upload_dir();
					$abspath_len = strlen(ABSPATH);
					$chmod_cmd = "CHMOD 777 ".substr($upload_dir, $abspath_len);
					if(!is_dir($upload_dir))
					{
						$result = wpfilebase_mkdir($upload_dir);
						if($result['error'])
							$error_msg = sprintf(__('The upload directory <code>%s</code> does not exists. It could not be created automatically because the directory <code>%s</code> is not writable. Please create <code>%s</code> and make it writable for PHP by execution the following FTP command: <code>%s</code>'), substr($upload_dir, $abspath_len), substr($result['parent'], $abspath_len), substr($upload_dir, $abspath_len), $chmod_cmd);
					} elseif(!is_writable($upload_dir)) {
						$error_msg = sprintf(__('The upload directory <code>%s</code> is not writable. Please make it writable for PHP by executing the follwing FTP command: <code>%s</code>'), substr($upload_dir, $abspath_len), $chmod_cmd);
					}
					
					if(!empty($error_msg)) { ?><div class="error default-password-nag"><p><?php echo $error_msg ?></p></div><?php } ?>
				
				<?php if ( current_user_can('upload_files') ) : ?><a href="<?php echo $clean_uri; ?>&amp;action=manage_files" class="button"><?php _e('Manage files'); ?></a><?php endif; ?>
				<?php if ( current_user_can('manage_categories') ) : ?><a href="<?php echo $clean_uri; ?>&amp;action=manage_cats" class="button"><?php _e('Manage categories'); ?></a><?php endif; ?>	
				<a href="<?php echo $clean_uri; ?>&amp;action=sync" class="button"><?php _e('Sync Filebase'); ?></a>
				</p>
				
				<h2><?php _e('Traffic'); ?></h2>
				<table class="form-table">
				<?php
					$traffic_stats = wpfilebase_get_traffic();					
					$limit_day = (wpfilebase_get_opt('traffic_day') * 1048576);
					$limit_month = (wpfilebase_get_opt('traffic_month') * 1073741824);
				?>
				<tr>
					<th scope="row"><?php _e('Today'); ?></th>
					<td><?php
						if($limit_day > 0)
							wpfilebase_progress_bar($traffic_stats['today'] / $limit_day, wpfilebase_format_filesize($traffic_stats['today']) . '/' . wpfilebase_format_filesize($limit_day));
						else
							echo wpfilebase_format_filesize($traffic_stats['today']);
					?></td>
				</tr>
				<tr>
					<th scope="row"><?php _e('This Month'); ?></th>
					<td><?php
						if($limit_month > 0)
							wpfilebase_progress_bar($traffic_stats['month'] / $limit_month, wpfilebase_format_filesize($traffic_stats['month']) . '/' . wpfilebase_format_filesize($limit_month));
						else
							echo wpfilebase_format_filesize($traffic_stats['month']);
					?></td>
				</tr>
				</table>
				
				<?php wpfilebase_admin_form('file', null, $exform) ?>
				
				<h2><?php _e('Copyright'); ?></h2>
				<p>
				<?php echo WPFB_PLUGIN_NAME . ' ' . WPFB_VERSION ?> Copyright &copy; 2009 by Fabian Schlieper <a href="http://fabi.me/">
				<?php if(strpos($_SERVER['SERVER_PROTOCOL'], 'HTTPS') === false) { ?><img src="http://fabi.me/misc/wpfb_icon.gif" alt="" /><?php } ?> fabi.me</a><br/>
				Includes code of the thumbnail generator <a href="http://phpthumb.sourceforge.net">phpThumb()</a> by James Heinrich
				</p>
			</div> <!-- wrap -->
			<?
			break;
	}
}

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
		if(wpfilebase_wpcache_reject_uri('/' . $_POST['download_base'] . '/', '/' . $options['download_base'] . '/'))
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
	<input type="submit" name="submit" value="<?php _e('Save Changes') ?>" />
	<input type="submit" id="deletepost" name="reset" value="<?php _e('Reset options') ?>" onclick="return confirm('<?php _e('Are you sure?'); ?>')" />
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
					echo ' value="' . attribute_escape($opt_val) . '"';
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
					echo '<option value="' . attribute_escape($opt_v) . '"' . (($opt_v == $opt_val) ? ' selected="selected" ' : '') . $style_class . '>' . (!is_numeric($opt_v) ? (wp_specialchars($opt_v) . ': ') : '') . wp_specialchars($opt_n) . '</option>';
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
	<input type="submit" name="submit" value="<?php _e('Save Changes') ?>" />
	<input type="submit" id="deletepost" name="reset" value="<?php _e('Reset options') ?>" onclick="return confirm('<?php _e('Are you sure?'); ?>')" />
	</p>
</form>
<!--
<h2><?php _e('Custom templates'); ?></h2>
<form method="post" action="<?php echo $action_uri; ?>" name="wpfilebase-templates">
<?php
	$tpls = wpfilebase_get_opt('templates');
	
	foreach($tpls as $tpl_name => $tpl)
	{
		
	}
?>
</form>
-->
</div>	<!-- wrap -->	
<?php
}

?>