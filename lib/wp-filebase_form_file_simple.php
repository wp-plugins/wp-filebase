<?php

$file = &$item;

if ( !empty($file) && !empty($file->file_id) ) {
	$heading = $submit_text = __('Edit File');
	$form = '<form enctype="multipart/form-data" name="editfile" id="editfile" method="post" action="?page=' . $_GET['page'] . '&amp;action=manage_files&amp;pagenum=' . $_GET['pagenum'] . '" class="validate">';
	$action = 'updatefile';
	$nonce_action = 'update-file_' . $file->file_id;	
	
} else {
	$heading = $submit_text = __('Upload File');
	$form = '<form enctype="multipart/form-data" name="addfile" id="addfile" method="post" action="?page=' . $_GET['page'] . '&amp;action=manage_files&amp;pagenum=' . $_GET['pagenum'] . '" class="add:the-list: validate">';
	$action = 'addfile';
	$nonce_action = 'add-file';	
	$file = null;
}

$parent_cats = WPFilebaseCategory::get_categories();
$parent_cat_list = '<option value="0">Keine</option>';

foreach($parent_cats as $pc)
{
	if((int)$pc->cat_parent <= 0)
		$parent_cat_list .= wpfilebase_parent_cat_seletion_tree($pc, $file);	
}

$file_members_only = ($file->file_required_level > 0);

?>

<div class="wrap">
<h2><?php echo $heading ?></h2>
<a href="<?php echo remove_query_arg('exform') ?>&exform=0" class="button"><?php _e('Extended Form') ?></a>
<?php echo $form ?>
<input type="hidden" name="action" value="<?php echo $action ?>" />
<input type="hidden" name="file_id" value="<?php echo $file->file_id ?>" />
<?php wp_nonce_field($nonce_action); ?>
	<table class="form-table">
		<tr>
			<th scope="row" valign="top"><label for="file_upload"><?php _e('Choose File') ?></label></th>
			<td class="form-field"><input type="file" name="file_upload" id="file_upload" />
			<?php
			if(!empty($file->file_name)) {
				echo '<br /><b>' . $file->file_name . '</b> (' . $file->get_formatted_size() . ')';
			} ?>
			</td>
			<th></th><td></td>
		</tr>
		<tr><th scope="row"></th><td colspan="3"><?php _e('The following fields are optional.') ?></td></tr>
		<tr class="form-field form-required">
			<th scope="row" valign="top"><label for="file_display_name"><?php _e('Title') ?></label></th>
			<td><input name="file_display_name" id="file_display_name" type="text" value="<?php echo attribute_escape($file->file_display_name); ?>" size="40" /></td>
			<th scope="row" valign="top"><label for="file_version"><?php _e('Version') ?></label></th>
			<td><input name="file_version" id="file_version" type="text" value="<?php echo attribute_escape($file->file_version); ?>" size="20" /></td>
		</tr>
		<tr class="form-field form-required">
			<th scope="row" valign="top"><label for="file_author"><?php _e('Author') ?></label></th>
			<td><input name="file_author" id="file_author" type="text" value="<?php echo attribute_escape($file->file_author); ?>" size="40" /></td>
			<th scope="row" valign="top"><label for="file_category"><?php _e('Category') ?></label></th>
			<td><select name="file_category" id="file_category" class="postform"><?php echo $parent_cat_list ?></select></td>
		</tr>

		<tr class="form-field">
			<th scope="row" valign="top"><label for="file_post_id"><?php _e('Post') ?> ID</label></th>
			<td>
				<input type="text" name="file_post_id" class="small-text" id="file_post_id" value="<?php echo attribute_escape($file->file_post_id); ?>" /> <a href="javascript:;" class="button" onclick="openPostBrowser('<?php echo attribute_escape(get_option('siteurl')) ?>');"><?php _e('Browse...') ?></a>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="file_description"><?php _e('Description') ?></label></th>
			<td colspan="3"><textarea name="file_description" id="file_description" rows="5" cols="50" style="width: 97%;"><?php echo wp_specialchars($file->file_description); ?></textarea></td>
		</tr>
	</table>
<p class="submit"><input type="submit" class="button" name="submit" value="<?php echo $submit_text ?>" /></p>
</form>
</div>