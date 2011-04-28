<?php

class WPFB_Widget {

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
	$files =& WPFB_File::GetFiles( (!empty($options['filelist_cat']) ? ('WHERE file_category = '.(int)$options['filelist_cat']) : '') . ' ORDER BY ' . $options['filelist_order_by'] . ($options['filelist_asc'] ? ' ASC' : ' DESC') . ' LIMIT ' . (int)$options['filelist_limit']);

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
		if($file->CurUserCanAccess(true))
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
			<select name="wpfilebase-filelist-cat" id="wpfilebase-filelist-cat"><?php echo WPFB_Output::CatSelTree(array('selected'=>$options['filelist_cat'],'none_label'=>__('All'))) ?></select>
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
?>