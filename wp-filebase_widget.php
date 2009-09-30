<?php

function wpfilebase_widget_init() {
    if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
        return;
    
    function wpfilebase_widget_filelist($args)
	{
        extract($args);
        
        $options = &wpfilebase_get_opt('widget');

        echo $before_widget;
        echo $before_title . $options['filelist_title'] . $after_title;
		
		$files = WPFilebaseFile::get_files('ORDER BY ' . $options['filelist_order_by'] . ($options['filelist_asc'] ? ' ASC' : ' DESC') . ' LIMIT ' . (int)$options['filelist_limit']);
		
		// add url to template
		/*
		if(strpos($options['filelist_template'], '%file_display_name%') !== false)
			$options['filelist_template'] = str_replace('%file_display_name%', '<a href="%file_url%">%file_display_name%</a>', $options['filelist_template']);
		else
			$options['filelist_template'] = '<a href="%file_url%">' . $options['filelist_template'] . '</a>';
		*/
		
		if(empty($options['filelist_template_parsed']) && !empty($options['filelist_template']))
		{
			$options['filelist_template_parsed'] = wpfilebase_parse_template($options['filelist_template']);
			wpfilebase_update_opt('widget', $options);
		}
		
		echo '<ul>';
		foreach($files as $file)
		{
			echo '<li>' . $file->parse_template($options['filelist_template_parsed']) . '</li>';
		}
		echo '</ul>';
		
        echo $after_widget;     
    }
    

    function wpfilebase_widget_filelist_control()
	{
		wpfilebase_inclib('admin');
		
        $options = wpfilebase_get_opt('widget');

        if ( !empty($_POST['wpfilebase-filelist-submit']) )
		{
            $options['filelist_title'] = strip_tags(stripslashes($_POST['wpfilebase-filelist-title']));
            $options['filelist_order_by'] = strip_tags(stripslashes($_POST['wpfilebase-filelist-order-by']));
            $options['filelist_asc'] = !empty($_POST['wpfilebase-filelist-asc']);
			$options['filelist_limit'] = max(1, (int)$_POST['wpfilebase-filelist-limit']);
			
			$options['filelist_template'] = stripslashes($_POST['wpfilebase-filelist-template']);
			if(strpos($options['filelist_template'], '<a ') === false)
				$options['filelist_template'] = '<a href="%file_url%">' . $options['filelist_template'] . '</a>';
			$options['filelist_template_parsed'] = wpfilebase_parse_template($options['filelist_template']);
            wpfilebase_update_opt('widget', $options);
        }
        ?>
        <div>
            <p><label for="wpfilebase-filelist-title"><?php _e('Title:'); ?>
				<input type="text" id="wpfilebase-filelist-title" name="wpfilebase-filelist-title" value="<?php echo attribute_escape($options['filelist_title']); ?>" />
			</label></p>
			
			<p>
				<label for="wpfilebase-filelist-order-by"><?php _e('Sort by:'); ?></label>
				<select type="text" id="wpfilebase-filelist-order-by" name="wpfilebase-filelist-order-by">
				<?php
					$order_by_options = array('file_id', 'file_name', 'file_size', 'file_date', 'file_display_name', 'file_hits', 'file_rating_sum', 'file_last_dl_time');
					$field_descs = &wpfilebase_template_fields_desc();
					foreach($order_by_options as $tag)
					{
						echo '<option value="' . attribute_escape($tag) . '" title="' . attribute_escape(__($field_descs[$tag])) . '"' . ( ($options['filelist_order_by'] == $tag) ? ' selected="selected"' : '' ) . '>' . __($tag) . '</option>';
					}
				?>
				</select><br />
				<label for="wpfilebase-filelist-asc0"><input type="radio" name="wpfilebase-filelist-asc" id="wpfilebase-filelist-asc0" value="0"<?php echo (!$options['filelist_asc'])?' checked="checked"':'' ?>/><?php _e('Descending'); ?></label>
				<label for="wpfilebase-filelist-asc1"><input type="radio" name="wpfilebase-filelist-asc" id="wpfilebase-filelist-asc1" value="1"<?php echo ($options['filelist_asc'])?' checked="checked"':'' ?>/><?php _e('Ascending'); ?></label>
			</p>
			
            <p><label for="wpfilebase-filelist-limit"><?php _e('Limit:'); ?>
				<input type="text" id="wpfilebase-filelist-limit" name="wpfilebase-filelist-limit" size="4" maxlength="3" value="<?php echo $options['filelist_limit']; ?>" />
			</label></p>
			
			<p>
				<label for="wpfilebase-filelist-template"><?php _e('Template:'); ?><br /><input class="widefat" type="text" id="wpfilebase-filelist-template" name="wpfilebase-filelist-template" value="<?php echo attribute_escape($options['filelist_template']); ?>" /></label>
				<br />
				<?php					
					echo wpfilebase_template_fields_select('wpfilebase-filelist-template', true);
				?>
			</p>
            <input type="hidden" name="wpfilebase-filelist-submit" id="wpfilebase-filelist-submit" value="1" />
        </div>
        <?php
    }
    
    register_sidebar_widget(WPFB_PLUGIN_NAME, 'wpfilebase_widget_filelist');
    register_widget_control(WPFB_PLUGIN_NAME, 'wpfilebase_widget_filelist_control');
}

?>