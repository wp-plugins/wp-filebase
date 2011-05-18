<?php
class WPFB_AdminGuiFiles {
static function Display()
{
	global $wpdb, $user_ID;
	
	wpfb_loadclass('File', 'Category', 'Admin', 'Output');
	
	$_POST = stripslashes_deep($_POST);
	$_GET = stripslashes_deep($_GET);
	
	$action = (!empty($_POST['action']) ? $_POST['action'] : (!empty($_GET['action']) ? $_GET['action'] : ''));
	$clean_uri = remove_query_arg(array('message', 'action', 'file_id', 'cat_id', 'deltpl', 'hash_sync' /* , 's'*/)); // keep search keyword
	
	// switch simple/extended form
	if(isset($_GET['exform'])) {
		$exform = (!empty($_GET['exform']) && $_GET['exform'] == 1);
		update_user_option($user_ID, WPFB_OPT_NAME . '_exform', $exform); 
	} else {
		$exform = (bool)get_user_option(WPFB_OPT_NAME . '_exform');
	}
	
	if(!empty($_REQUEST['redirect']) && !empty($_REQUEST['redirect_to'])) WPFB_AdminLite::JsRedirect($_REQUEST['redirect_to']);
	
	?>
	<div class="wrap">
	<?php

	switch($action)
	{		
		case 'editfile':
			if(!current_user_can('upload_files')) wp_die(__('Cheatin&#8217; uh?'));
			$file_id = intval($_GET['file_id']);
			$file = &WPFB_File::GetFile($file_id);
			WPFB_Admin::PrintForm('file', $file);
			break;

		case 'updatefile':
			$file_id = (int)$_POST['file_id'];
			$update = true;
			
		case 'addfile':
			$update = !empty($update);
			
			if ( !current_user_can('upload_files') )
				wp_die(__('Cheatin&#8217; uh?'));
				
			/* // this was causing some trouble...
			foreach ( array('aa', 'mm', 'jj', 'hh', 'mn') as $timeunit ) {
				if ( !empty($_POST['hidden_' . $timeunit] ) && $_POST['hidden_' . $timeunit] != $_POST[$timeunit] ) {
					$edit_date = true;
					break;
				}
			}*/
			
			extract($_POST);
			if(isset($jj) && isset($ss))
			{
				$jj = ($jj > 31 ) ? 31 : $jj;
				$hh = ($hh > 23 ) ? $hh -24 : $hh;
				$mn = ($mn > 59 ) ? $mn -60 : $mn;
				$ss = ($ss > 59 ) ? $ss -60 : $ss;
				$_POST['file_date'] =  sprintf( "%04d-%02d-%02d %02d:%02d:%02d", $aa, $mm, $jj, $hh, $mn, $ss );
			}
			
			$result = WPFB_Admin::InsertFile(array_merge($_POST, $_FILES));
			if(isset($result['error']) && $result['error']) {
				$message = $result['error'];
			} else {
				$message = $update?__('File updated.', WPFB):__('File added.', WPFB);
			}

		default:		
			if(!current_user_can('upload_files'))
				wp_die(__('Cheatin&#8217; uh?'));
				
			if(!empty($_POST['deleteit'])) {
				foreach ( (array)$_POST['delete'] as $file_id ) {					
					if(is_object($file = WPFB_File::GetFile($file_id)))
						$file->remove();
				}
			}
?>
	<h2><?php
	printf(__('Manage Files (<a href="%s">add new</a>)', WPFB), '#addfile');
	if ( isset($_GET['s']) && $_GET['s'] )
		printf( '<span class="subtitle">' . __('Search results for &#8220;%s&#8221;'/*def*/) . '</span>', esc_html(stripslashes($_GET['s'])));
	?></h2>
	<?php if ( !empty($message) ) : ?><div id="message" class="updated fade"><p><?php echo $message; ?></p></div><?php endif; ?> 
	<form class="search-form topmargin" action="" method="get"><p class="search-box">
			<input type="hidden" value="<?php echo esc_attr($_GET['page']); ?>" name="page" />
			<label class="hidden" for="file-search-input"><?php _e('Search Files', WPFB); ?>:</label>
			<input type="text" class="search-input" id="file-search-input" name="s" value="<?php echo(isset($_GET['s']) ? esc_attr($_GET['s']) : ''); ?>" />
			<input type="submit" value="<?php _e('Search Files', WPFB); ?>" class="button" />
	</p></form>
	
	<br class="clear" />

	<form id="posts-filter" action="" method="post">
		<div class="tablenav">
			<?php
			$pagenum = max(isset($_GET['pagenum']) ? absint($_GET['pagenum']) : 0, 1);
			if( !isset($filesperpage) || $filesperpage < 0 )
				$filesperpage = 30;
				
			$pagestart = ($pagenum - 1) * $filesperpage;
			$extra_sql = '';
			
			$where = wpfb_call('Search','SearchWhereSql');
			if(!empty($where))
				$extra_sql .= "WHERE 0 $where";		
			
			if(!empty($_GET['order']) && in_array($_GET['order'], array_keys(get_class_vars('WPFB_File'))))
				$extra_sql .= "ORDER BY " . $_GET['order'] . " " . (!empty($_GET['desc']) ? "DESC" : "ASC");	
			else
				$extra_sql .= "ORDER BY file_id DESC";

			$files = &WPFB_File::GetFiles($extra_sql . " LIMIT $pagestart, $filesperpage");

			$page_links = paginate_links( array(
				'base' => add_query_arg( 'pagenum', '%#%' ),
				'format' => '',
				'total' => ceil(count(WPFB_File::GetFiles($extra_sql)) / $filesperpage),
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
				<th scope="col" class="num"><a href="<?php echo WPFB_Admin::AdminTableSortLink('file_id') ?>"><?php _e('ID'/*def*/) ?></a></th>	
				<th scope="col"><a href="<?php echo WPFB_Admin::AdminTableSortLink('file_display_name') ?>"><?php _e('Name'/*def*/) ?></a></th>	
				<th scope="col"><a href="<?php echo WPFB_Admin::AdminTableSortLink('file_name') ?>"><?php _e('Filename', WPFB) ?></a></th>    
				<th scope="col"><a href="<?php echo WPFB_Admin::AdminTableSortLink('file_size') ?>"><?php _e('Size'/*def*/) ?></a></th>    		
				<th scope="col"><a href="<?php echo WPFB_Admin::AdminTableSortLink('file_description') ?>"><?php _e('Description'/*def*/) ?></a></th>
				<th scope="col"><a href="<?php echo WPFB_Admin::AdminTableSortLink('file_category_name') ?>"><?php _e('Category'/*def*/) ?></a></th>
				<th scope="col" class="num"><a href="<?php echo WPFB_Admin::AdminTableSortLink('file_hits') ?>"><?php _e('Hits', WPFB) ?></a></th>
				<th scope="col"><a href="<?php echo WPFB_Admin::AdminTableSortLink('file_last_dl_time') ?>"><?php _e('Last download', WPFB) ?></a></th>
				<!-- TODO <th scope="col" class="num"><a href="<?php echo WPFB_Admin::AdminTableSortLink('file_') ?>"><?php _e('Rating'/*def*/) ?></th> -->
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
						
					$cat = $file->GetParent();
				?>
				<tr id='file-<?php echo $file_id ?>'<?php if($file->file_offline) { echo " class='offline'"; } ?>>
						    <th scope='row' class='check-column'><input type='checkbox' name='delete[]' value='<?php echo $file_id ?>' /></th>
						    <td class="num"><?php echo $file_id ?></td>
							<td class="wpfilebase-admin-list-row-title"><a class='row-title' href='<?php echo $file->GetEditUrl() ?>' title='&quot;<?php echo esc_attr($file->file_display_name); ?>&quot; bearbeiten'>
							<?php if(!empty($file->file_thumbnail)) { ?><img src="<?php echo $file->GetIconUrl(); ?>" height="32" /><?php } ?>
							<span><?php if($file->IsRemote()){echo '*';} echo esc_html($file->file_display_name); ?></span>
							</a></td>
							<td><?php echo esc_html($file->file_name); ?></td>
							<td><?php echo WPFB_Output::FormatFilesize($file->file_size); ?></td>
							<td><?php echo empty($file->file_description) ? '-' : esc_html($file->file_description); ?></td>
							<td><?php echo ($file->file_category > 0) ? ('<a href="'.$cat->GetEditUrl().'">'.esc_html($file->file_category_name).'</a>') : '-'; ?></td>
							<td class='num'><?php echo $file->file_hits; ?></td>
							<td><?php echo ( (!empty($file->file_last_dl_time) && $file->file_last_dl_time > 0) ? mysql2date(get_option('date_format'), $file->file_last_dl_time) : '-') ?></td>
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

<?php

	unset($file);
	WPFB_Admin::PrintForm('file', null, array('exform' => $exform));
	
	break; // default
	}	
	?>
</div> <!-- wrap -->
<?php
}


static function PrintFileInfo($info, $path='file_info')
{
	foreach($info as $key => $val)
	{
		$p = $path.'/'.$key;
		if(is_array($val) && count($val) == 1 && isset($val[0])) // if its a single array, just take the first element
			$val = $val[0];
		echo '<b>',$p,"</b> = ",$val,"\n";
		if(is_array($val) || is_object($val))
		{			
			self::PrintFileInfo($val, $p);
		}
	}
}

static function FileInfoPathsBox($info)
{
	?><p>The following tags can be used in templates. For example, if you want to display the Title of a MP3 File, put <code>%file_info/tags/id3v2/artist%</code> inside the template code.</p>
	<p><pre><?php self::PrintFileInfo($info); ?></pre></p>
	<?php
}
}