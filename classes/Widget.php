<?php
class WPFB_Widget {
	
static function InitClass() {
	add_action('widgets_init', array(__CLASS__, 'RegisterWidgets'));
}

static function RegisterWidgets()
{
	register_widget('WPFB_UploadWidget');
	register_widget('WPFB_AddCategoryWidget');
}

function FileList($args)
{
	wpfb_loadclass('File', 'Category', 'Output');
	
	extract($args);
	
	$options = &WPFB_Core::GetOpt('widget');
	
	if(empty($options['filelist_title'])) $options['filelist_title'] = __('Files', WPFB);

	echo $before_widget;
	echo $before_title . $options['filelist_title'] . $after_title;
	
	// load all categories
	WPFB_Category::GetCats();
	$files =& WPFB_File::GetFiles2(
		!empty($options['filelist_cat']) ?  array('file_category'=>(int)$options['filelist_cat']) : null,
		WPFB_Core::GetOpt('hide_inaccessible'),
		array($options['filelist_order_by'] => ($options['filelist_asc'] ? 'ASC' : 'DESC')),
	 	(int)$options['filelist_limit']
	);
	
	//$files =& WPFB_File::GetFiles( (!empty($options['filelist_cat']) ? ('WHERE file_category = '.(int)$options['filelist_cat']) : '') . ' ORDER BY ' . $options['filelist_order_by'] . ($options['filelist_asc'] ? ' ASC' : ' DESC') . ' LIMIT ' . (int)$options['filelist_limit']);
	
	// add url to template
	/*
	if(strpos($options['filelist_template'], '%file_display_name%') !== false)
		$options['filelist_template'] = str_replace('%file_display_name%', '<a href="%file_url%">%file_display_name%</a>', $options['filelist_template']);
	else
		$options['filelist_template'] = '<a href="%file_url%">' . $options['filelist_template'] . '</a>';
	*/
	
	if(empty($options['filelist_template_parsed']) && !empty($options['filelist_template']))
	{
		wpfb_loadclass('TplLib');
		$options['filelist_template_parsed'] = WPFB_TplLib::Parse($options['filelist_template']);
		WPFB_Core::UpdateOption('widget', $options);
	}
	
	echo '<ul>';
	$tpl =& $options['filelist_template_parsed'];
	foreach($files as $file){
		echo '<li>',$file->GenTpl($tpl, 'widget'),'</li>';
	}
	echo '</ul>';
	
	echo $after_widget;     
}

function FileListCntrl()
{
	wpfb_loadclass('File', 'Category', 'Output', 'Admin');
	
	$options = WPFB_Core::GetOpt('widget');

	if ( !empty($_POST['wpfilebase-filelist-submit']) )
	{
		$options['filelist_title'] = strip_tags(stripslashes($_POST['wpfilebase-filelist-title']));
		$options['filelist_cat'] = max(0, intval($_POST['wpfilebase-filelist-cat']));
		$options['filelist_order_by'] = strip_tags(stripslashes($_POST['wpfilebase-filelist-order-by']));
		$options['filelist_asc'] = !empty($_POST['wpfilebase-filelist-asc']);
		$options['filelist_limit'] = max(1, (int)$_POST['wpfilebase-filelist-limit']);
		
		$options['filelist_template'] = stripslashes($_POST['wpfilebase-filelist-template']);
		if(strpos($options['filelist_template'], '<a ') === false)
			$options['filelist_template'] = '<a href="%file_url%">' . $options['filelist_template'] . '</a>';
		wpfb_loadclass('TplLib');
		$options['filelist_template_parsed'] = WPFB_TplLib::Parse($options['filelist_template']);
		WPFB_Core::UpdateOption('widget', $options);
	}
	?>
	<div>
		<p><label for="wpfilebase-filelist-title"><?php _e('Title:'); ?>
			<input type="text" id="wpfilebase-filelist-title" name="wpfilebase-filelist-title" value="<?php echo esc_attr($options['filelist_title']); ?>" />
		</label></p>
		
		<p>
			<label for="wpfilebase-filelist-cat"><?php _e('Category:', WPFB); ?></label>		
			<select name="wpfilebase-filelist-cat" id="wpfilebase-filelist-cat"><?php echo WPFB_Output::CatSelTree(array('selected'=>empty($options['filelist_cat'])?0:$options['filelist_cat'],'none_label'=>__('All'))) ?></select>
		</p>
		
		<p>
			<label for="wpfilebase-filelist-order-by"><?php _e('Sort by:'/*def*/); ?></label>
			<select id="wpfilebase-filelist-order-by" name="wpfilebase-filelist-order-by">
			<?php
				$order_by_options = array('file_id', 'file_name', 'file_size', 'file_date', 'file_display_name', 'file_hits', /*'file_rating_sum' TODO ,*/ 'file_last_dl_time');
				$field_descs = &WPFB_Admin::TplVarsDesc();
				foreach($order_by_options as $tag)
				{
					echo '<option value="' . esc_attr($tag) . '" title="' . esc_attr($field_descs[$tag]) . '"' . ( ($options['filelist_order_by'] == $tag) ? ' selected="selected"' : '' ) . '>' . $tag . '</option>';
				}
			?>
			</select><br />
			<label for="wpfilebase-filelist-asc0"><input type="radio" name="wpfilebase-filelist-asc" id="wpfilebase-filelist-asc0" value="0"<?php checked($options['filelist_asc'], false) ?>/><?php _e('Descending'); ?></label>
			<label for="wpfilebase-filelist-asc1"><input type="radio" name="wpfilebase-filelist-asc" id="wpfilebase-filelist-asc1" value="1"<?php checked($options['filelist_asc'], true) ?>/><?php _e('Ascending'); ?></label>
		</p>
		
		<p><label for="wpfilebase-filelist-limit"><?php _e('Limit:', WPFB); ?>
			<input type="text" id="wpfilebase-filelist-limit" name="wpfilebase-filelist-limit" size="4" maxlength="3" value="<?php echo $options['filelist_limit']; ?>" />
		</label></p>
		
		<p>
			<label for="wpfilebase-filelist-template"><?php _e('Template:', WPFB); ?><br /><input class="widefat" type="text" id="wpfilebase-filelist-template" name="wpfilebase-filelist-template" value="<?php echo esc_attr($options['filelist_template']); ?>" /></label>
			<br />
			<?php					
				echo WPFB_Admin::TplFieldsSelect('wpfilebase-filelist-template', true);
			?>
		</p>
		<input type="hidden" name="wpfilebase-filelist-submit" id="wpfilebase-filelist-submit" value="1" />
	</div>
	<?php
}

function CatList($args)
{
	// if no filebrowser this widget doosnt work
	if(WPFB_Core::GetOpt('file_browser_post_id') <= 0)
		return;
		
	wpfb_loadclass('Category', 'Output');
	
	extract($args);
	
	$options = &WPFB_Core::GetOpt('widget');

	echo $before_widget;
	echo $before_title , (empty($options['catlist_title']) ? __('File Categories', WPFB) : $options['catlist_title']), $after_title;
	
	$tree = !empty($options['catlist_hierarchical']);
	
	// load all categories
	WPFB_Category::GetCats();
	
	$cats = WPFB_Category::GetCats(($tree ? 'WHERE cat_parent = 0 ' : '') . 'ORDER BY cat_name ASC' /* . $options['catlist_order_by'] . ($options['catlist_asc'] ? ' ASC' : ' DESC') /*. ' LIMIT ' . (int)$options['catlist_limit']*/);
	
	echo '<ul>';
	foreach($cats as $cat){
		if($cat->CurUserCanAccess(true))
		{
			if($tree)
				self::CatTree($cat);
			else
				echo '<li><a href="'.$cat->GetUrl().'">'.esc_html($cat->cat_name).'</a></li>';
		}
	}
	echo '</ul>';
	echo $after_widget;
}

function CatTree(&$root_cat)
{
	echo '<li><a href="'.$root_cat->GetUrl().'">'.esc_html($root_cat->cat_name).'</a>';
	
	$childs =& $root_cat->GetChildCats();
	if(count($childs) > 0)
	{
		echo '<ul>';
		foreach(array_keys($childs) as $i) self::CatTree($childs[$i]);
		echo '</ul>';
	}
	
	echo '</li>';
}


function CatListCntrl()
{
	if(WPFB_Core::GetOpt('file_browser_post_id') <= 0) {
		echo '<div>';
		_e('Before you can use this widget, please set a Post ID for the file browser in WP-Filebase settings.', WPFB);
		echo '<br /><a href="'.admin_url('admin.php?page=wpfilebase_sets#file-browser').'">';
		_e('Goto File Browser Settings');
		echo '</a></div>';
		return;
	}
	
	wpfb_loadclass('Admin');
	
	$options = WPFB_Core::GetOpt('widget');

	if ( !empty($_POST['wpfilebase-catlist-submit']) )
	{
		$options['catlist_title'] = strip_tags(stripslashes($_POST['wpfilebase-catlist-title']));
		//$options['catlist_order_by'] = strip_tags(stripslashes($_POST['wpfilebase-catlist-order-by']));
		//$options['catlist_asc'] = !empty($_POST['wpfilebase-catlist-asc']);
		//$options['catlist_limit'] = max(1, (int)$_POST['wpfilebase-catlist-limit']);
		$options['catlist_hierarchical'] = !empty($_POST['wpfilebase-catlist-hierarchical']);
		WPFB_Core::UpdateOption('widget', $options);
	}
	?>
	<div>
		<p><label for="wpfilebase-catlist-title"><?php _e('Title:'/*def*/); ?>
			<input type="text" id="wpfilebase-catlist-title" name="wpfilebase-catlist-title" value="<?php echo esc_attr($options['catlist_title']); ?>" />
		</label></p>
		
		<p>
			<input type="checkbox" class="checkbox" id="wpfilebase-catlist-hierarchical" name="wpfilebase-catlist-hierarchical"<?php checked( !empty($options['catlist_hierarchical']) ); ?> />
			<label for="wpfilebase-catlist-hierarchical"><?php _e( 'Show hierarchy' ); ?></label>
		</p> 
		
		<!-- 
		<p>
			<label for="wpfilebase-catlist-order-by"><?php _e('Sort by:'/*def*/); ?></label>
			<select id="wpfilebase-catlist-order-by" name="wpfilebase-catlist-order-by">
			<?php
				$order_by_options = array('cat_id', 'cat_name', 'cat_folder', 'cat_num_files', 'cat_num_files_total');
				$field_descs = &WPFB_Admin::TplVarsDesc(true);
				foreach($order_by_options as $tag)
				{
					echo '<option value="' . esc_attr($tag) . '" title="' . esc_attr($field_descs[$tag]) . '"' . ( ($options['catlist_order_by'] == $tag) ? ' selected="selected"' : '' ) . '>' . $tag . '</option>';
				}
			?>
			</select><br />
			<label><input type="radio" name="wpfilebase-catlist-asc" value="0" <?php checked($options['catlist_asc'], false) ?>/><?php _e('Descending'); ?></label>
			<label><input type="radio" name="wpfilebase-catlist-asc" value="1" <?php checked($options['catlist_asc'], true) ?>/><?php _e('Ascending'); ?></label>
		</p>
		
		
		<p><label for="wpfilebase-catlist-limit"><?php _e('Limit:', WPFB); ?>
			<input type="text" id="wpfilebase-catlist-limit" name="wpfilebase-catlist-limit" size="4" maxlength="3" value="<?php echo $options['catlist_limit']; ?>" />
		</label></p> -->
		<input type="hidden" name="wpfilebase-catlist-submit" id="wpfilebase-catlist-submit" value="1" />
	</div>
	<?php
}
}

class WPFB_UploadWidget extends WP_Widget {

	function WPFB_UploadWidget() {
		parent::WP_Widget( false, WPFB_PLUGIN_NAME .' '.__('File Upload'), array('description' => __('Allows users to upload files from the front end.',WPFB)) );
	}

	function widget( $args, $instance ) {			
		if(!current_user_can('upload_files'))
			return;

		wpfb_loadclass('File', 'Category', 'Output');
		
		$instance['category'] = empty($instance['category']) ? 0 : (int)$instance['category'];
		
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);		
		echo $before_widget;
		echo $before_title . (empty($title) ? __('Upload File',WPFB) : $title) . $after_title;
		
		$prefix = "wpfb-upload-widget-".$this->id_base;
		$form_url = add_query_arg('wpfb_upload_file', 1);
		$nonce_action = "$prefix-".((int)!empty($instance['overwrite']))."-".$instance['category'];
		?>		
		<form enctype="multipart/form-data" name="<?php echo $prefix ?>form" method="post" action="<?php echo $form_url ?>">
		<?php wp_nonce_field($nonce_action, 'wpfb-file-nonce'); ?>
			<input type="hidden" name="overwrite" value="<?php echo !empty($instance['overwrite']) ?>" />
			<input type="hidden" name="prefix" value="<?php echo $prefix ?>" />
			<input type="hidden" name="cat" value="<?php echo $instance['category'] /* for noncing*/ ?>" />
			<p>
				<label for="<?php echo $prefix ?>file_upload"><?php _e('Choose File', WPFB) ?></label>
				<input type="file" name="file_upload" id="<?php echo $prefix ?>file_upload" style="width: 160px" size="10" /><br />
				<small><?php printf(str_replace('%d%s','%s',__('Maximum upload file size: %d%s'/*def*/)), WPFB_Output::FormatFilesize(WPFB_Core::GetMaxUlSize())) ?></small>
				<?php if($instance['category'] == -1) { ?><br />
				<label for="<?php echo $prefix ?>file_category"><?php _e('Category') ?></label>
				<select name="file_category" id="<?php echo $prefix ?>file_category"><?php echo WPFB_Output::CatSelTree() ?></select>
				<?php } else {?>
				<input type="hidden" name="file_category" value="<?php echo $instance['category'] ?>" />
				<?php } ?>
			</p>	
			<p style="text-align:right;"><input type="submit" class="button-primary" name="submit-btn" value="<?php _ex('Add New', 'file') ?>" /></p>
		</form>
	<?php
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		wpfb_loadclass('Category');
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['category'] = ($new_instance['category'] > 0) ? (is_null($cat=WPFB_Category::GetCat($new_instance['category'])) ? 0 : $cat->GetId()) : (int)$new_instance['category'];
		$instance['overwrite'] = !empty($new_instance['overwrite']);
        return $instance;
	}
	
	function form( $instance ) {
		wpfb_loadclass('Output');
		if(!isset($instance['title'])) $instance['title'] = __('Upload File',WPFB);
		?><div>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($instance['title']); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('category'); ?>"><?php _e('Category:'); ?>
				<select id="<?php echo $this->get_field_id('category'); ?>" name="<?php echo $this->get_field_name('category'); ?>">
					<option value="-1"  style="font-style:italic;"><?php _e('Selectable by Uploader',WPFB); ?></option>
					<?php echo WPFB_Output::CatSelTree(array('none_label' => __('Upload to Root',WPFB), 'selected'=>empty($instance['category'])?0:$instance['category'])); ?>
				</select>
			</label></p>
			<p><input type="checkbox" id="<?php echo $this->get_field_id('overwrite'); ?>" name="<?php echo $this->get_field_name('overwrite'); ?>" value="1" <?php checked(!empty($instance['overwrite'])) ?> /> <label for="<?php echo $this->get_field_id('overwrite'); ?>"><?php _e('Overwrite existing files', WPFB) ?></label></p>
		</div><?php
	}
}

class WPFB_AddCategoryWidget extends WP_Widget {

	function WPFB_AddCategoryWidget() {
		parent::WP_Widget( false, WPFB_PLUGIN_NAME .' '.__('Add Category',WPFB), array('description' => __('Allows users to create file categories from the front end.',WPFB)) );
	}

	function widget( $args, $instance ) {			
		if(!current_user_can('upload_files'))
			return;

		wpfb_loadclass('File', 'Category', 'Output');
		
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);		
		echo $before_widget;
		echo $before_title . (empty($title) ? __('Add File Category',WPFB) : $title) . $after_title;
		
		$prefix = "wpfb-add-cat-widget-".$this->id_base;
		$form_url = add_query_arg('wpfb_add_cat', 1);
		$nonce_action = $prefix;
		?>		
		<form enctype="multipart/form-data" name="<?php echo $prefix ?>form" method="post" action="<?php echo $form_url ?>">
		<?php wp_nonce_field($nonce_action, 'wpfb-cat-nonce'); ?>
		<input type="hidden" name="prefix" value="<?php echo $prefix ?>" />
			<p>
				<label for="<?php echo $prefix ?>cat_name"><?php _e('New category name'/*def*/) ?></label>
				<input name="cat_name" id="<?php echo $prefix ?>cat_name" type="text" value="" />
			</p>
			<p>
				<label for="<?php echo $prefix ?>cat_parent"><?php _e('Parent Category'/*def*/) ?></label>
	  			<select name="cat_parent" id="<?php echo $prefix ?>cat_parent"><?php echo WPFB_Output::CatSelTree(array('selected'=>0,'exclude'=>0)) ?></select>
	  		</p>
			<p style="text-align:right;"><input type="submit" class="button-primary" name="submit-btn" value="<?php _e('Add New Category'/*def*/) ?>" /></p>
		</form>
	<?php
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		//$instance['overwrite'] = !empty($new_instance['overwrite']);
        return $instance;
	}
	
	function form( $instance ) {
		if(!isset($instance['title'])) $instance['title'] = __('Add File Category',WPFB);
		?><div>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($instance['title']); ?>" /></label></p>
		</div><?php
	}
}
?>