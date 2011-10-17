<?php
class WPFB_AdminGuiFiles {
static $FilesPerPage = 50;

static function Display()
{
	global $wpdb, $user_ID;
	
	wpfb_loadclass('File', 'Category', 'Admin', 'Output');
	
	$_POST = stripslashes_deep($_POST);
	$_GET = stripslashes_deep($_GET);
	
	$action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : '';
	$clean_uri = remove_query_arg(array('message', 'action', 'file_id', 'cat_id', 'deltpl', 'hash_sync' /* , 's'*/)); // keep search keyword
	
	// nonce/referer check (security)
	if($action == 'updatefile' || $action == 'addfile') {
		$nonce_action = WPFB."-".$action;
		if($action == 'updatefile') $nonce_action .= $_POST['file_id'];
		if(!wp_verify_nonce($_POST['wpfb-file-nonce'],$nonce_action) || !check_admin_referer($nonce_action,'wpfb-file-nonce'))
			wp_die(__('Cheatin&#8217; uh?'));		
	}
	
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
			
			if(!empty($_POST['files'])) {
				if(!is_array($_POST['files'])) $_POST['files'] = explode(',',$_POST['files']);
				$files = array();
				foreach($_POST['files'] as $file_id) {
					$file_id = intval($file_id);
					if($file_id > 0) $files[] = WPFB_File::GetFile($file_id);
				}
				if(count($files) > 0)
					WPFB_Admin::PrintForm('file', $files, array('multi_edit' => true));
				else 
					wp_die('No files to edit.');
			} else {
				$file = &WPFB_File::GetFile($_GET['file_id']);
				WPFB_Admin::PrintForm('file', $file);
			}
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
	echo str_replace(array('(<','>)'),array('<','>'), sprintf(__('Manage Files (<a href="%s">add new</a>)', WPFB), '#addfile" class="add-new-h2'));
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
				$filesperpage = self::$FilesPerPage;
				
			$pagestart = ($pagenum - 1) * $filesperpage;
			$extra_sql = '';
			wpfb_loadclass('Search');
			$where = WPFB_Search::SearchWhereSql(true);
			$order = "$wpdb->wpfilebase_files." . ((!empty($_GET['order']) && in_array($_GET['order'], array_keys(get_class_vars('WPFB_File')))) ?
				($_GET['order']." ".(!empty($_GET['desc']) ? "DESC" : "ASC")) : "file_id DESC");
				
			if(!empty($_GET['file_category'])) 
				$where = (empty($where) ? '' : ("($where) AND ")) . "file_category = " . intval($_GET['file_category']);

			$files = WPFB_File::GetFiles2($where, true, $order, $filesperpage, $pagestart);
			
			if(empty($files) && !empty($wpdb->last_error)) {
				wp_die("<b>Database error</b>: ".$wpdb->last_error);
			}

			$page_links = paginate_links( array(
				'base' => add_query_arg( 'pagenum', '%#%' ),
				'format' => '',
				'total' => ceil(WPFB_File::GetNumFiles2($where, false) / $filesperpage),
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
				<th scope="col"><a href="<?php echo WPFB_Admin::AdminTableSortLink('file_user_roles') ?>"><?php _e('Access Permission',WPFB) ?></a></th>
				<th scope="col"><a href="<?php echo WPFB_Admin::AdminTableSortLink('file_added_by') ?>"><?php _e('Uploader',WPFB) ?></a></th>
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
					$user_roles = $file->GetUserRoles();
				?>
				<tr id='file-<?php echo $file_id ?>'<?php if($file->file_offline) { echo " class='offline'"; } ?>>
						    <th scope='row' class='check-column'><input type='checkbox' name='delete[]' value='<?php echo $file_id ?>' /></th>
						    <td class="num"><?php echo $file_id ?></td>
							<td class="wpfilebase-admin-list-row-title"><a class='row-title' href='<?php echo $file->GetEditUrl() ?>' title='&quot;<?php echo esc_attr($file->file_display_name); ?>&quot; bearbeiten'>
							<?php if(!empty($file->file_thumbnail)) { ?><img src="<?php echo $file->GetIconUrl(); ?>" height="32" /><?php } ?>
							<span><?php if($file->IsRemote()){echo '*';} echo esc_html($file->file_display_name); ?></span>
							</a></td>
							<td><a href="<?php echo $file->GetUrl() ?>"><?php echo esc_html($file->file_name); ?></a></td>
							<td><?php echo WPFB_Output::FormatFilesize($file->file_size); ?></td>
							<td><?php echo empty($file->file_description) ? '-' : esc_html($file->file_description); ?></td>
							<td><?php echo (!is_null($cat)) ? ('<a href="'.$cat->GetEditUrl().'">'.esc_html($file->file_category_name).'</a>') : '-'; ?></td>
							<td><?php echo empty($user_roles) ? ("<i>".__('Everyone',WPFB)."</i>") : join(', ', WPFB_Output::RoleNames($user_roles)) ?></td>
							<td><?php echo (empty($file->file_added_by) || !($usr = get_userdata($file->file_added_by))) ? '-' : esc_html($usr->user_login) ?></td>
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
	?><p>The following tags can be used in templates. For example, if you want to display the Artist of a MP3 File, put <code>%file_info/tags/id3v2/artist%</code> inside the template code.</p>
	<p><pre><?php self::PrintFileInfo(empty($info->value) ? $info : $info->value); ?></pre></p>
	
	<?php
	if(!empty($info->keywords)) {
		?><p><b>Keywords (used for search):</b> <?php echo esc_html($info->keywords) ?></p> <?php
	}
}
}


class WPFB_File_List_Table extends WP_List_Table {

	function __construct() {
		$this->detached = isset( $_REQUEST['detached'] ) || isset( $_REQUEST['find_detached'] );

		parent::__construct( array(
			'plural' => 'media'
		) );
	}

	function ajax_user_can() {
		return current_user_can('upload_files');
	}

	function prepare_items() {
		global $lost, $wpdb, $wp_query, $post_mime_types, $avail_post_mime_types;

		$q = $_REQUEST;

		if ( !empty( $lost ) )
			$q['post__in'] = implode( ',', $lost );

		list( $post_mime_types, $avail_post_mime_types ) = wp_edit_attachments_query( $q );

 		$this->is_trash = isset( $_REQUEST['status'] ) && 'trash' == $_REQUEST['status'];

		$this->set_pagination_args( array(
			'total_items' => $wp_query->found_posts,
			'total_pages' => $wp_query->max_num_pages,
			'per_page' => $wp_query->query_vars['posts_per_page'],
		) );
	}

	function get_views() {
		global $wpdb, $post_mime_types, $avail_post_mime_types;

		$type_links = array();
		$_num_posts = (array) wp_count_attachments();
		$_total_posts = array_sum($_num_posts) - $_num_posts['trash'];
		if ( !isset( $total_orphans ) )
				$total_orphans = $wpdb->get_var( "SELECT COUNT( * ) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_status != 'trash' AND post_parent < 1" );
		$matches = wp_match_mime_types(array_keys($post_mime_types), array_keys($_num_posts));
		foreach ( $matches as $type => $reals )
			foreach ( $reals as $real )
				$num_posts[$type] = ( isset( $num_posts[$type] ) ) ? $num_posts[$type] + $_num_posts[$real] : $_num_posts[$real];

		$class = ( empty($_GET['post_mime_type']) && !$this->detached && !isset($_GET['status']) ) ? ' class="current"' : '';
		$type_links['all'] = "<a href='upload.php'$class>" . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $_total_posts, 'uploaded files' ), number_format_i18n( $_total_posts ) ) . '</a>';
		foreach ( $post_mime_types as $mime_type => $label ) {
			$class = '';

			if ( !wp_match_mime_types($mime_type, $avail_post_mime_types) )
				continue;

			if ( !empty($_GET['post_mime_type']) && wp_match_mime_types($mime_type, $_GET['post_mime_type']) )
				$class = ' class="current"';
			if ( !empty( $num_posts[$mime_type] ) )
				$type_links[$mime_type] = "<a href='upload.php?post_mime_type=$mime_type'$class>" . sprintf( translate_nooped_plural( $label[2], $num_posts[$mime_type] ), number_format_i18n( $num_posts[$mime_type] )) . '</a>';
		}
		$type_links['detached'] = '<a href="upload.php?detached=1"' . ( $this->detached ? ' class="current"' : '' ) . '>' . sprintf( _nx( 'Unattached <span class="count">(%s)</span>', 'Unattached <span class="count">(%s)</span>', $total_orphans, 'detached files' ), number_format_i18n( $total_orphans ) ) . '</a>';

		if ( !empty($_num_posts['trash']) )
			$type_links['trash'] = '<a href="upload.php?status=trash"' . ( (isset($_GET['status']) && $_GET['status'] == 'trash' ) ? ' class="current"' : '') . '>' . sprintf( _nx( 'Trash <span class="count">(%s)</span>', 'Trash <span class="count">(%s)</span>', $_num_posts['trash'], 'uploaded files' ), number_format_i18n( $_num_posts['trash'] ) ) . '</a>';

		return $type_links;
	}

	function get_bulk_actions() {
		$actions = array();
		$actions['delete'] = __( 'Delete Permanently' );
		if ( $this->detached )
			$actions['attach'] = __( 'Attach to a post' );

		return $actions;
	}

	function extra_tablenav( $which ) {
		global $post_type;
		$post_type_obj = get_post_type_object( $post_type );
?>
		<div class="alignleft actions">
<?php
		if ( 'top' == $which && !is_singular() && !$this->detached && !$this->is_trash ) {
			$this->months_dropdown( $post_type );

			do_action( 'restrict_manage_posts' );
			submit_button( __( 'Filter' ), 'secondary', false, false, array( 'id' => 'post-query-submit' ) );
		}

		if ( $this->detached ) {
			submit_button( __( 'Scan for lost attachments' ), 'secondary', 'find_detached', false );
		} elseif ( $this->is_trash && current_user_can( 'edit_others_posts' ) ) {
			submit_button( __( 'Empty Trash' ), 'button-secondary apply', 'delete_all', false );
		} ?>
		</div>
<?php
	}

	function current_action() {
		if ( isset( $_REQUEST['find_detached'] ) )
			return 'find_detached';

		if ( isset( $_REQUEST['found_post_id'] ) && isset( $_REQUEST['media'] ) )
			return 'attach';

		if ( isset( $_REQUEST['delete_all'] ) || isset( $_REQUEST['delete_all2'] ) )
			return 'delete_all';

		return parent::current_action();
	}

	function has_items() {
		return have_posts();
	}

	function no_items() {
		_e( 'No media attachments found.' );
	}

	function get_columns() {
		$posts_columns = array();
		$posts_columns['cb'] = '<input type="checkbox" />';
		$posts_columns['icon'] = '';
		/* translators: column name */
		$posts_columns['title'] = _x( 'File', 'column name' );
		$posts_columns['author'] = __( 'Author' );
		//$posts_columns['tags'] = _x( 'Tags', 'column name' );
		/* translators: column name */
		if ( !$this->detached ) {
			$posts_columns['parent'] = _x( 'Attached to', 'column name' );
			$posts_columns['comments'] = '<span class="vers"><img alt="Comments" src="' . esc_url( admin_url( 'images/comment-grey-bubble.png' ) ) . '" /></span>';
		}
		/* translators: column name */
		$posts_columns['date'] = _x( 'Date', 'column name' );
		$posts_columns = apply_filters( 'manage_media_columns', $posts_columns, $this->detached );

		return $posts_columns;
	}

	function get_sortable_columns() {
		return array(
			'title'    => 'title',
			'author'   => 'author',
			'parent'   => 'parent',
			'comments' => 'comment_count',
			'date'     => array( 'date', true ),
		);
	}

	function display_rows() {
		global $post, $id;

		add_filter( 'the_title','esc_html' );
		$alt = '';

		while ( have_posts() ) : the_post();

			if ( $this->is_trash && $post->post_status != 'trash'
			||  !$this->is_trash && $post->post_status == 'trash' )
				continue;

			$alt = ( 'alternate' == $alt ) ? '' : 'alternate';
			$post_owner = ( get_current_user_id() == $post->post_author ) ? 'self' : 'other';
			$att_title = _draft_or_post_title();
?>
	<tr id='post-<?php echo $id; ?>' class='<?php echo trim( $alt . ' author-' . $post_owner . ' status-' . $post->post_status ); ?>' valign="top">
<?php

list( $columns, $hidden ) = $this->get_column_info();
foreach ( $columns as $column_name => $column_display_name ) {
	$class = "class='$column_name column-$column_name'";

	$style = '';
	if ( in_array( $column_name, $hidden ) )
		$style = ' style="display:none;"';

	$attributes = $class . $style;

	switch ( $column_name ) {

	case 'cb':
?>
		<th scope="row" class="check-column"><?php if ( current_user_can( 'edit_post', $post->ID ) ) { ?><input type="checkbox" name="media[]" value="<?php the_ID(); ?>" /><?php } ?></th>
<?php
		break;

	case 'icon':
		$attributes = 'class="column-icon media-icon"' . $style;
?>
		<td <?php echo $attributes ?>><?php
			if ( $thumb = wp_get_attachment_image( $post->ID, array( 80, 60 ), true ) ) {
				if ( $this->is_trash ) {
					echo $thumb;
				} else {
?>
				<a href="<?php echo get_edit_post_link( $post->ID, true ); ?>" title="<?php echo esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;' ), $att_title ) ); ?>">
					<?php echo $thumb; ?>
				</a>

<?php			}
			}
?>
		</td>
<?php
		break;

	case 'title':
?>
		<td <?php echo $attributes ?>><strong><?php if ( $this->is_trash ) echo $att_title; else { ?><a href="<?php echo get_edit_post_link( $post->ID, true ); ?>" title="<?php echo esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;' ), $att_title ) ); ?>"><?php echo $att_title; ?></a><?php }; _media_states( $post ); ?></strong>
			<p>
<?php
			if ( preg_match( '/^.*?\.(\w+)$/', get_attached_file( $post->ID ), $matches ) )
				echo esc_html( strtoupper( $matches[1] ) );
			else
				echo strtoupper( str_replace( 'image/', '', get_post_mime_type() ) );
?>
			</p>
<?php
		echo $this->row_actions( $this->_get_row_actions( $post, $att_title ) );
?>
		</td>
<?php
		break;

	case 'author':
?>
		<td <?php echo $attributes ?>><?php the_author() ?></td>
<?php
		break;

	case 'tags':
?>
		<td <?php echo $attributes ?>><?php
		$tags = get_the_tags();
		if ( !empty( $tags ) ) {
			$out = array();
			foreach ( $tags as $c )
				$out[] = "<a href='edit.php?tag=$c->slug'> " . esc_html( sanitize_term_field( 'name', $c->name, $c->term_id, 'post_tag', 'display' ) ) . "</a>";
			echo join( ', ', $out );
		} else {
			_e( 'No Tags' );
		}
?>
		</td>
<?php
		break;

	case 'desc':
?>
		<td <?php echo $attributes ?>><?php echo has_excerpt() ? $post->post_excerpt : ''; ?></td>
<?php
		break;

	case 'date':
		if ( '0000-00-00 00:00:00' == $post->post_date && 'date' == $column_name ) {
			$t_time = $h_time = __( 'Unpublished' );
		} else {
			$t_time = get_the_time( __( 'Y/m/d g:i:s A' ) );
			$m_time = $post->post_date;
			$time = get_post_time( 'G', true, $post, false );
			if ( ( abs( $t_diff = time() - $time ) ) < 86400 ) {
				if ( $t_diff < 0 )
					$h_time = sprintf( __( '%s from now' ), human_time_diff( $time ) );
				else
					$h_time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
			} else {
				$h_time = mysql2date( __( 'Y/m/d' ), $m_time );
			}
		}
?>
		<td <?php echo $attributes ?>><?php echo $h_time ?></td>
<?php
		break;

	case 'parent':
		if ( $post->post_parent > 0 ) {
			if ( get_post( $post->post_parent ) ) {
				$title =_draft_or_post_title( $post->post_parent );
			}
?>
			<td <?php echo $attributes ?>>
				<strong><a href="<?php echo get_edit_post_link( $post->post_parent ); ?>"><?php echo $title ?></a></strong>,
				<?php echo get_the_time( __( 'Y/m/d' ) ); ?>
			</td>
<?php
		} else {
?>
			<td <?php echo $attributes ?>><?php _e( '(Unattached)' ); ?><br />
			<a class="hide-if-no-js" onclick="findPosts.open( 'media[]','<?php echo $post->ID ?>' );return false;" href="#the-list"><?php _e( 'Attach' ); ?></a></td>
<?php
		}
		break;

	case 'comments':
		$attributes = 'class="comments column-comments num"' . $style;
?>
		<td <?php echo $attributes ?>>
			<div class="post-com-count-wrapper">
<?php
		$pending_comments = get_pending_comments_num( $post->ID );

		$this->comments_bubble( $post->ID, $pending_comments );
?>
			</div>
		</td>
<?php
		break;

	default:
?>
		<td <?php echo $attributes ?>>
			<?php do_action( 'manage_media_custom_column', $column_name, $id ); ?>
		</td>
<?php
		break;
	}
}
?>
	</tr>
<?php endwhile;
	}

	function _get_row_actions( $post, $att_title ) {
		$actions = array();

		if ( $this->detached ) {
			if ( current_user_can( 'edit_post', $post->ID ) )
				$actions['edit'] = '<a href="' . get_edit_post_link( $post->ID, true ) . '">' . __( 'Edit' ) . '</a>';
			if ( current_user_can( 'delete_post', $post->ID ) )
				if ( EMPTY_TRASH_DAYS && MEDIA_TRASH ) {
					$actions['trash'] = "<a class='submitdelete' href='" . wp_nonce_url( "post.php?action=trash&amp;post=$post->ID", 'trash-attachment_' . $post->ID ) . "'>" . __( 'Trash' ) . "</a>";
				} else {
					$delete_ays = !MEDIA_TRASH ? " onclick='return showNotice.warn();'" : '';
					$actions['delete'] = "<a class='submitdelete'$delete_ays href='" . wp_nonce_url( "post.php?action=delete&amp;post=$post->ID", 'delete-attachment_' . $post->ID ) . "'>" . __( 'Delete Permanently' ) . "</a>";
				}
			$actions['view'] = '<a href="' . get_permalink( $post->ID ) . '" title="' . esc_attr( sprintf( __( 'View &#8220;%s&#8221;' ), $att_title ) ) . '" rel="permalink">' . __( 'View' ) . '</a>';
			if ( current_user_can( 'edit_post', $post->ID ) )
				$actions['attach'] = '<a href="#the-list" onclick="findPosts.open( \'media[]\',\''.$post->ID.'\' );return false;" class="hide-if-no-js">'.__( 'Attach' ).'</a>';
		}
		else {
			if ( current_user_can( 'edit_post', $post->ID ) && !$this->is_trash )
				$actions['edit'] = '<a href="' . get_edit_post_link( $post->ID, true ) . '">' . __( 'Edit' ) . '</a>';
			if ( current_user_can( 'delete_post', $post->ID ) ) {
				if ( $this->is_trash )
					$actions['untrash'] = "<a class='submitdelete' href='" . wp_nonce_url( "post.php?action=untrash&amp;post=$post->ID", 'untrash-attachment_' . $post->ID ) . "'>" . __( 'Restore' ) . "</a>";
				elseif ( EMPTY_TRASH_DAYS && MEDIA_TRASH )
					$actions['trash'] = "<a class='submitdelete' href='" . wp_nonce_url( "post.php?action=trash&amp;post=$post->ID", 'trash-attachment_' . $post->ID ) . "'>" . __( 'Trash' ) . "</a>";
				if ( $this->is_trash || !EMPTY_TRASH_DAYS || !MEDIA_TRASH ) {
					$delete_ays = ( !$this->is_trash && !MEDIA_TRASH ) ? " onclick='return showNotice.warn();'" : '';
					$actions['delete'] = "<a class='submitdelete'$delete_ays href='" . wp_nonce_url( "post.php?action=delete&amp;post=$post->ID", 'delete-attachment_' . $post->ID ) . "'>" . __( 'Delete Permanently' ) . "</a>";
				}
			}
			if ( !$this->is_trash ) {
				$title =_draft_or_post_title( $post->post_parent );
				$actions['view'] = '<a href="' . get_permalink( $post->ID ) . '" title="' . esc_attr( sprintf( __( 'View &#8220;%s&#8221;' ), $title ) ) . '" rel="permalink">' . __( 'View' ) . '</a>';
			}
		}

		$actions = apply_filters( 'media_row_actions', $actions, $post, $this->detached );

		return $actions;
	}
}

