<?php

$file_category = &$item;

$update = (!empty($file_category) && !empty($file_category->cat_id));

$title = __($update ? 'Edit Category' : 'Add Category');
if ( $update ) {	
	$form = '<form name="editcat" id="editcat" method="post" action="' . remove_query_arg('cat_id') . '" class="validate">';
	$action = 'updatecat';
	$nonce_action = 'update-filecat_' . $file_category->cat_id;		
} else {
	$form = '<form name="addcat" id="addcat" method="post" action="' . remove_query_arg('cat_id') . '" class="add:the-list: validate">';
	$action = 'addcat';
	$nonce_action = 'add-filecat';
	$file_category = null;
}
$cat_members_only = ($file_category->cat_required_level > 0);
?>

<div class="wrap">
<h2><?php echo $title ?></h2>
<div id="ajax-response"></div>
<?php echo $form ?>
<input type="hidden" name="action" value="<?php echo $action ?>" />
<input type="hidden" name="cat_id" value="<?php echo ($update ? $file_category->cat_id : 0) ?>" />
<?php wp_nonce_field($nonce_action); ?>
	<table class="form-table">
		<tr class="form-field form-required">
			<th scope="row" valign="top"><label for="cat_name"><?php _e('Category Name') ?></label></th>
			<td><input name="cat_name" id="cat_name" type="text" value="<?php echo attribute_escape($file_category->cat_name); ?>" size="40" aria-required="true" /></td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="cat_folder"><?php _e('Category Folder') ?></label></th>
			<td><input name="cat_folder" id="cat_folder" type="text" value="<?php echo attribute_escape($file_category->cat_folder); ?>" size="40" /><br />
            <?php _e('The &#8220;slug&#8221; is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.'); ?></td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="cat_parent"><?php _e('Category Parent') ?></label></th>
			<td>
	  			<select name="cat_parent" id="cat_parent" class="postform"><?php wpfilebase_cat_seletion_tree($update ? $file_category->cat_parent : 0, $update ? $file_category->cat_id : 0) ?></select><br />
                <?php _e('Categories, unlike tags, can have a hierarchy. You might have a Jazz category, and under that have children categories for Bebop and Big Band. Totally optional.'); ?>
	  		</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="cat_description"><?php _e('Description') ?></label></th>
			<td><textarea name="cat_description" id="cat_description" rows="5" cols="50" style="width: 97%;"><?php echo wp_specialchars($file_category->cat_description); ?></textarea></td>
		</tr>
		<tr>
			<th scope="row" valign="top"><label for="cat_members_only"><?php _e('For members only') ?></label></th>
			<td>
				<input type="checkbox" name="cat_members_only" value="1" <?php checked(true, $cat_members_only) ?> onclick="checkboxShowHide(this, 'cat_required_level')" />
				<label for="cat_required_level"<?php if(!$cat_members_only) { echo ' class="hidden"'; } ?>><?php printf(__('Minimum user level: (see %s)'), '<a href="http://codex.wordpress.org/Roles_and_Capabilities#Role_to_User_Level_Conversion" target="_blank">Role to User Level Conversion</a>') ?> <input type="text" name="cat_required_level" class="small-text<?php if(!$cat_members_only) { echo ' hidden'; } ?>" id="cat_required_level" value="<?php echo max(0, intval($file_category->cat_required_level) - 1); ?>" /></label>
			</td>
		</tr>
		<?php if($update) { ?>
		<tr>
			<th scope="row" valign="top"><label for="cat_child_apply_perm"><?php _e('Apply permission to all child files') ?></label></th>
			<td><input type="checkbox" name="cat_child_apply_perm" value="1" /></td>
		</tr>
		<?php } ?>
	</table>
<p class="submit"><input type="submit" class="button" name="submit" value="<?php echo _e('Submit') ?>" /></p>
</form>
</div>