<?php

global $wpdb;

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
			
			<th scope="row" valign="top"><label for="file_upload_thumb"><?php _e('Thumbnail') ?></label></th>
			<td><input type="file" name="file_upload_thumb" id="file_upload_thumb" />
			<br /><?php _e('You can optionally upload a thumbnail here. If the file is a valid image, a thumbnail is generated automatically.'); ?>
			<?php
			if(!empty($file->file_thumbnail)) {
				?>
				<br /><img src="<?php echo $file->get_icon_url(); ?>" /><br />
				<b><?php echo $file->file_thumbnail; ?></b> <label for="file_delete_thumb"><input type="checkbox" value="1" name="file_delete_thumb" id="file_delete_thumb" /> <?php _e('Delete'); ?></label>
			<?php } ?>
			</td>
		</tr>
		<tr class="form-field form-required">
			<th scope="row" valign="top"><label for="file_display_name"><?php _e('Title') ?></label></th>
			<td><input name="file_display_name" id="file_display_name" type="text" value="<?php echo attribute_escape($file->file_display_name); ?>" size="40" /></td>
			<th scope="row" valign="top"><label for="file_version"><?php _e('Version') ?></label></th>
			<td><input name="file_version" id="file_version" type="text" value="<?php echo attribute_escape($file->file_version); ?>" size="20" /></td>
		</tr>
		<tr class="form-field form-required">
			<th scope="row" valign="top"><label for="file_author"><?php _e('Author') ?></label></th>
			<td><input name="file_author" id="file_author" type="text" value="<?php echo attribute_escape($file->file_author); ?>" size="40" /></td>
			<th scope="row" valign="top"><label for="file_date"><?php _e('Date') ?></label></th>
			<td><?php
				//create a comment object for the touch_time function
				global $comment;
				$comment = new stdClass();
				$comment->comment_date = false;
				if( $file != null)					
					$comment->comment_date = $file->file_date;
				?><div class="wpfilebase-date-edit"><?php
				touch_time(($file != null),0); ?></div></td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="file_category"><?php _e('Category') ?></label></th>
			<td><select name="file_category" id="file_category" class="postform"><?php echo $parent_cat_list ?></select></td>
			
			<th scope="row" valign="top"><label for="file_license"><?php _e('License') ?></label></th>
			<td><select name="file_license" id="file_license" class="postform"><?php echo wpfilebase_make_options_list('licenses', $file ? $file->file_license : null, true) ?></select></td>
		</tr>

		<tr class="form-field">
			<th scope="row" valign="top"><label for="file_post_id"><?php _e('Post') ?> ID</label></th>
			<td>
				<input type="text" name="file_post_id" class="small-text" id="file_post_id" value="<?php echo attribute_escape($file->file_post_id); ?>" /> <a href="javascript:;" class="button" onclick="openPostBrowser('<?php echo attribute_escape(get_option('siteurl')) ?>');"><?php _e('Browse...') ?></a>
			</td>

			<th scope="row" valign="top"><label for="file_hits"><?php _e('Download Counter') ?></label></th>
			<td><input type="text" name="file_hits" class="small-text" id="file_hits" value="<?php echo (int)$file->file_hits; ?>" /></td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="file_platforms[]"><?php _e('Platforms') ?></label></th>
			<td><select name="file_platforms[]" size="40" multiple="multiple" id="file_platforms[]" style="height: 80px;"><?php echo wpfilebase_make_options_list('platforms', $file ? $file->file_platform : null, true) ?></select></td>

			<th scope="row" valign="top"><label for="file_requirements[]"><?php _e('Requirements') ?></label></th>
			<td><select name="file_requirements[]" size="40" multiple="multiple" id="file_requirements[]" style="height: 80px;"><?php echo wpfilebase_make_options_list('requirements', $file ? $file->file_requirement : null, true) ?></select></td>
		</tr>
		<tr>
			<th scope="row" valign="top"><label for="file_languages[]"><?php _e('Languages') ?></label></th>
			<td  class="form-field"><select name="file_languages[]" size="40" multiple="multiple" id="file_languages[]" style="height: 80px;"><?php echo wpfilebase_make_options_list('languages', $file ? $file->file_language : null, true) ?></select></td>
			
			<th scope="row" valign="top"><label for="file_direct_linking"><?php _e('Direct linking') ?></label></th>
			<td>
				<fieldset><legend class="hidden"><?php _e('Direct linking') ?></legend>
					<label title="<?php _e('Yes') ?>"><input type="radio" name="file_direct_linking" value="1" <?php checked('1', $file->file_direct_linking); ?>/> <?php _e('Allow direct linking') ?></label><br />
					<label title="<?php _e('No') ?>"><input type="radio" name="file_direct_linking" value="0" <?php checked('0', $file->file_direct_linking); ?>/> <?php _e('Redirect to post') ?></label>
				</fieldset>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="file_description"><?php _e('Description') ?></label></th>
			<td colspan="3"><textarea name="file_description" id="file_description" rows="5" cols="50" style="width: 97%;"><?php echo wp_specialchars($file->file_description); ?></textarea></td>
		</tr>		
		<tr>
			<th scope="row" valign="top"><label for="file_offline"><?php _e('Offline') ?></label></th>
			<td><input type="checkbox" name="file_offline" value="1" <?php checked('1', $file->file_offline); ?>/></td>
			
			<th scope="row" valign="top"><label for="file_members_only"><?php _e('For members only') ?></label></th>
			<td>
				<input type="checkbox" name="file_members_only" value="1" <?php checked(true, $file_members_only) ?> onclick="checkboxShowHide(this, 'file_required_level')" />
				<label for="file_required_level"<?php if(!$file_members_only) { echo ' class="hidden"'; } ?>><?php printf(__('Minimum user level: (see %s)'), '<a href="http://codex.wordpress.org/Roles_and_Capabilities#Role_to_User_Level_Conversion" target="_blank">Role to User Level Conversion</a>') ?> <input type="text" name="file_required_level" class="small-text<?php if(!$file_members_only) { echo ' hidden'; } ?>" id="file_required_level" value="<?php echo max(0, intval($file->file_required_level) - 1); ?>" /></label>
			</td>
		</tr>
	</table>
<p class="submit"><input type="submit" class="button" name="submit" value="<?php echo $submit_text ?>" /></p>
</form>
</div>