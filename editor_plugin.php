<?php

require('../../../wp-config.php');

// anti hack
if(!current_user_can('edit_posts'))
	exit;

wpfilebase_inclib('common');
include_once(WPFB_PLUGIN_ROOT . 'wp-filebase_item.php');

$path = dirname(__FILE__);

function wpfilebase_editor_file_list($cat_id = 0)
{
	$content = '';
	
	$cat = ($cat_id != 0) ? WPFilebaseCategory::get_category($cat_id) : null;
	
	// back link
	if($cat)
		$content .= '<a href="javascript:;" onclick="getSubItems(' . $cat->cat_parent . ');" class="catlink">&lt;- ' . __('Go back') . '</a><br />';
	
	// sub cats
	$cats = $cat ? $cat->get_child_categories() : WPFilebaseCategory::get_categories();
	if(count($cats) > 0)
	{
		$content .= '<h3>' . __('Categories') . '</h3>';
		foreach($cats as $cat)
			$content .= '<a href="javascript:;" onclick="getSubItems(' . $cat->cat_id . ');" class="catlink">' . wp_specialchars($cat->cat_name) . '</a><br />';
	}

	// files
	$num_total_files = WPFilebaseFile::get_num_files();
	$files = $cat ? $cat->get_files() : WPFilebaseFile::get_files("WHERE file_category = 0");

	$content .= '<h3>' . __('Files') . '</h3>';
	foreach($files as $file)
		$content .= '<label><input type="radio" name="file" value="' . $file->file_id . '" title="' . attribute_escape($file->file_display_name) . '" />' . wp_specialchars($file->file_display_name) . '</label><br />';
	if(count($files) == 0 && $num_total_files == 0)
		$content .= '<i>' . sprintf(__('You did not upload a file. <a href="%s" target="_parent">Click here to add one.</a>'), get_option('siteurl') . '/wp-admin/tools.php?page=wpfilebase&amp;action=manage_files#addfile') . '</i>';
		
	return $content;
}

if(!empty($_REQUEST['action']) && $_REQUEST['action'] == 'get_sub_items')
{
	echo wpfilebase_editor_file_list(intval($_REQUEST['cat']));
	exit;
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"  dir="ltr" lang="en-US">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title><?php echo WPFB_PLUGIN_NAME; ?></title>
	<?php wp_enqueue_script('tinymce-popup', '/wp-includes/js/tinymce/tiny_mce_popup.js'); ?>
	<?php wp_enqueue_script('jquery'); ?>
	<?php wp_head(); ?>
	<style type="text/css">
	<!--
		h2{
			margin: 0 0 5px 0;
			font-size: 12px;
			padding: 0 0 4px 0;
			border-bottom: 1px #BAC3CA solid;
		}
		
		h3{
			font-size: 10px;
			margin-left: -4px;
		}
		
		a{
			color: #00457A;
		}
		
		#menu {
			text-align: center;
		}
		
		#filelist, #insfilelist {
			margin: 5px;
		}
	-->
	</style>
	<script type="text/javascript">	
	var currentContainer = '';
	var panelVisible = false;
	
	function showContainer(btn)
	{
		var el_fl = document.getElementById('filelist');
		var el_ifl = document.getElementById('insfilelist');
		
		if(btn.name == 'insfilelist')
		{
			el_fl.style.display = 'none';
			el_ifl.style.display = 'block';
		} else {
			el_ifl.style.display = 'none';
			el_fl.style.display = 'block';
		}
		
		document.getElementById('containertitle').innerHTML = btn.value;
		
		currentContainer = btn.name;
		
		if(!panelVisible) {
			document.getElementById('mceActionPanel').style.display = 'block';
			panelVisible = true;
		}
	}
	
	
	function getSubItems(cat)
	{
		jQuery('body').css('cursor', 'wait');
			
		var response = jQuery.ajax({
			type: 'POST',
			url: '<?php echo basename($_SERVER['PHP_SELF']); ?>',
			data: 'action=get_sub_items&cat=' + cat,
			async: false
		}).responseText;
		
		jQuery('body').css('cursor', 'default');
		
		document.getElementById('filelist').innerHTML = response;

		return true;
	}
	
	/*
	function getFileUrl(file)
	{
		jQuery('body').css('cursor', 'wait');			
		var response = jQuery.ajax({
			type: 'POST',
			url: '<?php echo basename($_SERVER['PHP_SELF']); ?>',
			data: 'action=get_file_url&file=' + file,
			async: false
		}).responseText;		
		jQuery('body').css('cursor', 'default');
		return response;
	}
	*/
	
	function getSelectedRadio(name)
	{
		if(!document.forms[0] || !document.forms[0].elements)
			return null;
			
		var els = document.forms[0].elements[name];		
		if(typeof(els.length) != 'undefined') {		
			for(var i = 0; i < els.length; ++i) {
				if(els[i].checked)
					return els[i];
			}
		} else if(typeof(els.value) != 'undefined') {
			return els;
		}
		
		return null;
	}
	
	function doInsert()
	{
		var form = document.forms[0];	
		var url = (currentContainer == 'insfileurl');
		var content = '';
		
		if(url)
			content += '<a href="';		
		content += '[filebase:';
		
		if(currentContainer == 'insfilelist')
		{
			var cat = getSelectedRadio('cat').value;			
			if(cat == 'attachments') {
				content += 'attachments';
			} else {
				content += 'filelist';
				if(cat != null && cat.length > 0 && cat != 'all')
					content += ':cat' + cat;
			}
			content += ']';
		} else {
			content += 'file';
			if(url)
				content += 'url';
			var radio = getSelectedRadio('file');
			var file = radio.value;
			if(file != null && file.length > 0)
				content += ':file' + file;
			else
				return;	
			content += ']';
			
			if(url)
			{
				var fileTitle = radio.title;
				var linkText = prompt('<?php _e('Enter link text:') ?>', fileTitle);
				if(!linkText || linkText == null || linkText == '')
					linkText = fileTitle;
				content += '">' + linkText + '</a>';
			}
		}
		
		tinyMCEPopup.execCommand("mceInsertContent", false, content);
		tinyMCEPopup.close();
	}
	</script>
	
</head>
<body>

<form onsubmit="doInsert(); return false;" action="#">
	<div id="menu" class="mceActionPanel">
		<input type="button" name="insfile" class="button" onclick="showContainer(this);" value="<?php _e('Single file'); ?>" />
		<input type="button" name="insfileurl" class="button" onclick="showContainer(this);" value="<?php _e('File URL'); ?>" />
		<input type="button" name="insfilelist" class="button" onclick="showContainer(this);" value="<?php _e('File list'); ?>" />
	</div>
	
	<div style="height: 290px; overflow: auto;">
		<h2 id="containertitle"></h2>
		
		<div id="filelist" style="display: none;"><?php echo wpfilebase_editor_file_list(); ?></div>
		
		<div id="insfilelist" style="display: none;">
			<label><input type="radio" name="cat" value="all" /><i><?php _e('All Categories'); ?></i></label><br />
			<label><input type="radio" name="cat" value="attachments" /><i><?php _e('Attachments'); ?></i></label><br />
			<?php
				$cats = WPFilebaseCategory::get_categories();
				if(count($cats) > 0)
				{
					foreach($cats as $cat)
						echo '<label><input type="radio" name="cat" value="' . $cat->cat_id . '" title="' . attribute_escape($cat->cat_name) . '" />' . wp_specialchars($cat->cat_name) . '</label><br />';
				} else {
					echo '<i>';
					printf(__('You did not create a category. <a href="%s" target="_parent">Click here to create one.</a>'), get_option('siteurl') . '/wp-admin/tools.php?page=wpfilebase&amp;action=manage_cats#addcat');
					echo '</i>';
				}
			?>
		</div>
	</div>
	
	<div id="mceActionPanel" class="mceActionPanel" style="display: none;">
		<div style="float: left">
			<input type="button" id="cancel" name="cancel" value="{#cancel}" onclick="tinyMCEPopup.close();" />
		</div>

		<div style="float: right">
			<input type="submit" id="insert" name="insert" value="{#insert}" />
		</div>
	</div>
</form>

</body>
</html>