<?php

define('WPFB_EDITOR_PLUGIN', 1);
if ( ! isset( $_GET['inline'] ) )
	define( 'IFRAME_REQUEST' , true );

//require_once(dirname(__FILE__).'/../../../wp-load.php');
// disable error reporting
//error_reporting(0);
require_once(dirname(__FILE__).'/../../../wp-admin/admin.php');
// enable error reporting again
//wp_debug_mode();

// anti hack
if(!current_user_can('publish_posts') && !current_user_can('edit_posts') && !current_user_can('edit_pages'))
	wp_die(__('Cheatin&#8217; uh?'));

function wpfb_editor_plugin_scripts() {
	//wp_enqueue_script('tiny-mce-popup', site_url().'/'.WPINC.'/js/tinymce/tiny_mce_popup.js');
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-treeview-async');
	wp_enqueue_script( 'postbox' );
}
add_action('admin_enqueue_scripts', 'wpfb_editor_plugin_scripts');
	
@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));

wpfb_loadclass('File', 'Category', 'Admin', 'ListTpl', 'Output');

$action = empty($_REQUEST['action']) ? '' : $_REQUEST['action'];
$post_id = empty($_REQUEST['post_id']) ? 0 : intval($_REQUEST['post_id']);
$file_id = empty($_REQUEST['file_id']) ? 0 : intval($_REQUEST['file_id']);
$file = ($file_id > 0) ? WPFB_File::GetFile($file_id) : null;

$manage_attachments = !empty($_REQUEST['manage_attachments']);

switch($action){
case 'rmfile':
	if($file && $file->file_post_id == $post_id) $file->SetPostId(0);
	$file = null;
	break;
	
case 'delfile':
	if($file) $file->Remove();
	$file = null;
	break;
	
case 'addfile':
	if ( !current_user_can('upload_files') ) wp_die(__('Cheatin&#8217; uh?'));
	break;
case 'change-order':
	foreach($_POST as $n => $v) {
		if(strpos($n, 'file_attach_order-') === 0)
		{
			$file_id = intval(substr($n, strlen('file_attach_order-')));
			$file = WPFB_File::GetFile($file_id);
			if(!is_null($file)) {
				$file->file_attach_order = intval($v);
				$file->DBSave();
			}
		}
	}
}

$post_attachments = ($post_id > 0) ? WPFB_File::GetAttachedFiles($post_id) : array();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php do_action('admin_xml_ns'); ?> <?php language_attributes(); ?>>
<head>
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
<title><?php echo WPFB_PLUGIN_NAME ?></title>

<?php
wp_enqueue_style( 'global' );
wp_enqueue_style( 'wp-admin' );
wp_enqueue_style( 'colors' );
wp_enqueue_style( 'media' );
wp_enqueue_style( 'ie' );
wp_enqueue_style('jquery-treeview');

do_action('admin_enqueue_scripts', 'media-upload-popup');
do_action('admin_print_styles-media-upload-popup');
do_action('admin_print_styles');
do_action('admin_print_scripts-media-upload-popup');
do_action('admin_print_scripts');
do_action('admin_head-media-upload-popup');
do_action('admin_head');
?>

<style type="text/css">
<!--
	h2{
		margin: 8px 0 5px 0;
		font-size: 12px;
		padding: 0 0 4px 0;
		border-bottom: 1px #BAC3CA solid;
	}
	
	h3{
		font-size: 10px;
		margin-left: -4px;
	}
	
	a {color: #00457A; }
	
	#menu {
		text-align: center;
	}
	
	#menu .button {
		width: 120px;
	}
	
	#filelist, #insfilelist {
		margin: 5px;
	}
	
	#tpllist {
		margin-top: 10px;
	}
	
	.media-item a {
		margin-top: 10px;
	}
	
	form, .container {
		padding: 0;
		margin: 10px;
	}
	
-->
</style>

<script type="text/javascript">
//<![CDATA[ 

var userSettings = {'url':'<?php echo SITECOOKIEPATH; ?>','uid':'<?php if ( ! isset($current_user) ) $current_user = wp_get_current_user(); echo $current_user->ID; ?>','time':'<?php echo time(); ?>'};
var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>', pagenow = 'wpfilebase-popup', adminpage = 'wpfilebase-popup', isRtl = <?php echo (int) is_rtl(); ?>;
var usePathTags = <?php echo (int)WPFB_Core::GetOpt('use_path_tags') ?>;
var yesImgUrl = '<?php echo admin_url( 'images/yes.png' ) ?>';

var theEditor;
var currentTab = '';
var selectedCats = [];
var includeAllCats = false;

jQuery(document).ready( function()
{
	jQuery(".media-item a").hide();
	jQuery(".media-item").hover(
		function(){jQuery("a",this).show();}, 
		function(){jQuery("a",this).hide();}
	);
	
<?php if(!$manage_attachments) { ?>
	var win = window.dialogArguments || opener || parent || top;
	if(win && typeof(win.tinymce) != 'undefined' && win.tinymce) theEditor = win.tinymce.EditorManager.activeEditor;
	else theEditor = null;

	tabclick(jQuery("a", jQuery('#sidemenu')).get(0));

	<?php if(!WPFB_Core::GetOpt('auto_attach_files')) { ?>
	if (theEditor && theEditor.getContent().search(/\[wpfilebase\s+tag\s*=\s*['"]attachments['"]/) != -1)
		jQuery('#no-auto-attach-note').hide(); 	// no notice if attachments tag is in
<?php }
} ?>
	refreshTrees();
});

function getTreeViewModel(data) {
	if(typeof data != 'object') data = {};
	data.action = "tree";
	return { url: "<?php echo WPFB_PLUGIN_URI."wpfb-ajax.php" ?>",
		ajax:{data:data,type:"post"},
		animated: "medium"
	};
}

function refreshTrees() {
	var model = getTreeViewModel({type:"fileselect",onselect:"selectFile(%d,'%s')",exclude_attached:true});
	jQuery("#attachbrowser").empty().treeview(model);
	
<?php if(!$manage_attachments) { ?>
	model.ajax.data.exclude_attached = false;
	jQuery("#filebrowser").empty().treeview(model);
	model = getTreeViewModel({type:"catselect",onselect:"selectCat(%d,'%s')", cat_id_fmt:'catsel-cat-%d'});
	jQuery("#catbrowser").empty().treeview(model);
<?php } ?>
}



function selectFile(id, name)
{
	var theTag = {"tag":currentTab, <?php echo WPFB_Core::GetOpt('use_path_tags') ? '"path": getFilePath(id)' : '"id":id'; ?>};
	var el = jQuery('span.file','#wpfb-file-'+id).first();
	
	if(<?php echo $manage_attachments?'true':'false' ?> || currentTab == 'attach') {
		jQuery.ajax({
			url: "<?php echo WPFB_PLUGIN_URI."wpfb-ajax.php" ?>",
			data: {
				action:"attach-file",
				post_id:<?php echo $post_id ?>,
				file_id:id
			},
			async: false});
		//delayedReload();
		el.css('background-image', 'url(<?php echo admin_url('images/yes.png') ?>)');
		return;
	} else if(currentTab == 'fileurl') {
		var linkText = prompt('<?php _e('Enter link text:', WPFB) ?>', name);
		if(!linkText || linkText == null || linkText == '')	return;
		theTag.linktext = linkText;
	} else {
		var tpl = jQuery('input[name=filetpl]:checked', '#filetplselect').val();
		if(tpl && tpl != '' && tpl != 'default') theTag.tpl = tpl;
	}
	insertTag(theTag);
}

function insBrowserTag()
{
	var tag = {tag:currentTab};
	var root = parseInt(jQuery('#browser-root').val());
	if(root > 0)
		<?php echo WPFB_Core::GetOpt('use_path_tags') ? 'tag.path = getCatPath(root);' : 'tag.id = root;'; ?>
		
	return insertTag(tag);
}

function insUploadFormTag()
{
	var tag = {tag:currentTab};
	var root = parseInt(jQuery('#uploadform-cat').val());
	if(root != 0) {
		if(usePathTags && root != -1)
			tag.path = getCatPath(root);
		else
			tag.id = root;
	}

	if(jQuery('#list-show-cats:checked').val())
		tag.overwrite = 1;
	return insertTag(tag);	
}

//]]>
</script>

<script type='text/javascript' src='<?php echo WPFB_PLUGIN_URI."js/editor-plugin.js" ?>'></script>


</head>
<body id="media-upload">

<div id="media-upload-header">
<?php if(!$manage_attachments) {?>
	<ul id='sidemenu'>
		<li><a href="#attach" onclick="return tabclick(this)"><?php _e('Attachments', WPFB) ?></a></li>
		<li><a href="#file" onclick="return tabclick(this)"><?php _e('Single file', WPFB) ?></a></li>
		<li><a href="#fileurl" onclick="return tabclick(this)"><?php _e('File URL', WPFB) ?></a></li>
		<li><a href="#list" onclick="return tabclick(this)"><?php _e('File list', WPFB) ?></a></li>
		<li><a href="#browser" onclick="return tabclick(this)"><?php _e('File Tree View', WPFB) ?></a></li>
		<!-- <li><a href="#uploadform" onclick="return tabclick(this)"><?php _e('Inline Upload Form', WPFB) ?></a></li> -->
	</ul>
<?php } ?>
</div>

<div id="attach" class="container">
<?php
if(!WPFB_Core::GetOpt('auto_attach_files')) {
	echo '<div id="no-auto-attach-note" class="updated">';
	printf(__('Note: Listing of attached files is disabled. You have to <a href="%s">insert the attachments tag</a> to show the files in the content.'),'javascript:insAttachTag();');
	echo '</div>';
}

if($action =='addfile' || $action =='updatefile')
{
	// nonce/referer check (security)
	$nonce_action = WPFB."-".$action;
	if($action == 'updatefile') $nonce_action .= $_POST['file_id'];
	$nonce_action .= "-editor";
	if(!wp_verify_nonce($_POST['wpfb-file-nonce'],$nonce_action) || !check_admin_referer($nonce_action,'wpfb-file-nonce'))
		wp_die(__('Cheatin&#8217; uh?'));
	
	$result = WPFB_Admin::InsertFile(array_merge($_POST, $_FILES));
	if(isset($result['error']) && $result['error']) {
		?><div id="message" class="updated fade"><p><?php echo $result['error']; ?></p></div><?php
		$file = new WPFB_File($_POST);
		unset($post_attachments); // hide attachment list on error
	} else {
		// success!!!!
		$file_id = $result['file_id'];
		if($action =='addfile')
			$post_attachments[] = WPFB_File::GetFile($file_id);
		else
			$file = null;
	}
}
	
if($action != 'editfile' && (!empty($post_attachments) || $manage_attachments)) {
	?>
	<form action="<?php echo add_query_arg(array('action'=>'change-order')) ?>" method="post">	
	<h3 class="media-title"><?php _e('Files', WPFB) ?></h3>
	<div id="media-items">
	<?php 
	if(empty($post_attachments)) echo "<div class='media-item'>",__('No items found.'),"</div>";
	else foreach($post_attachments as $pa) { ?>
		<div class='media-item'>
			<?php if(!empty($pa->file_thumbnail)) { ?><img class="pinkynail toggle" src="<?php echo $pa->GetIconUrl(); ?>" alt="" style="margin-top: 3px; display: block;" /><?php } ?>

			<a class='toggle describe-toggle-on' href="<?php echo add_query_arg(array('file_id'=>$pa->file_id,'action'=>'delfile')) ?>" title="<?php _e('Delete') ?>"><img style="display: inline;" src="<?php echo WPFB_PLUGIN_URI.'extras/jquery/contextmenu/delete_icon.gif'; ?>" /></a>
			<a class='toggle describe-toggle-on' href="<?php echo add_query_arg(array('file_id'=>$pa->file_id,'action'=>'rmfile')) ?>" title="<?php _e('Remove') ?>"><img src="<?php echo WPFB_PLUGIN_URI.'extras/jquery/contextmenu/page_white_delete.png'; ?>" /></a>
			<a class='toggle describe-toggle-on' href="<?php echo add_query_arg(array('file_id'=>$pa->file_id,'action'=>'editfile')) ?>" title="<?php _e('Edit') ?>"><img src="<?php echo WPFB_PLUGIN_URI.'extras/jquery/contextmenu/page_white_edit.png'; ?>" /></a>

			<div class='filename'>
				<input type="text" size="3" name="file_attach_order-<?php echo $pa->file_id ?>" value="<?php echo $pa->file_attach_order ?>" style="text-align: right; width: 30px;" />
				<span class='title'><?php echo $pa->file_display_name ?></span>
			</div>
		</div>
	<?php }	?>
	</div>
	<input type="submit" name="change-order" value="<?php _e('Change Order') ?>" />
	</form>
	<?php
}
WPFB_Admin::PrintForm('file', $file, array('in_editor'=>true, 'post_id'=>$post_id));
?>
<h3 class="media-title"><?php _e('Attach existing file', WPFB) ?></h3>
<ul id="attachbrowser" class="filetree"></ul>
</div> <!-- attach -->
	
<?php if(!$manage_attachments) {?>
<form id="filetplselect">
	<h2><?php _e('Select Template', WPFB) ?></h2>
	<label><input type="radio" name="filetpl" value="" checked="checked" /><i><?php _e('Default Template', WPFB) ?></i></label><br />
	<?php $tpls = WPFB_Core::GetFileTpls();
		if(!empty($tpls)) {
			foreach($tpls as $tpl_tag => $tpl_src)
				echo '<label><input type="radio" name="filetpl" value="' . esc_attr($tpl_tag) . '" />' . esc_html($tpl_tag) . '</label><br />';
		} ?>
	<i><a href="<?php echo admin_url('admin.php?page=wpfilebase_tpls#file') ?>" target="_parent"><?php _e('Add Template', WPFB) ?></a></i>
</form>
<div id="fileselect" class="container">
	<h2><?php _e('Select File', WPFB); ?></h2>
	<ul id="filebrowser" class="filetree"></ul>
</div>
<div id="catselect" class="container">
	<h2><?php _e('Select Category'/*def*/); ?></h2>
	<p><?php _e('Select the categories containing the files you would like to list.',WPFB); ?></p>
	<p><input type="checkbox" id="list-all-files" name="list-all-files" value="1" onchange="incAllCatsChanged(this.checked)"/> <label for="list-all-files"><?php _e('Include all Categories',WPFB); ?></label></p>
	<ul id="catbrowser" class="filetree"></ul>
</div>
<form id="listtplselect">
	<h2><?php _e('Select Template', WPFB) ?></h2>
	<?php $tpls = WPFB_ListTpl::GetAll();
		if(!empty($tpls)) {
			foreach($tpls as $tpl)
				echo '<label><input type="radio" name="listtpl" value="'.$tpl->tag.'" />'.$tpl->GetTitle().'</label><br />';
		} ?>
	<i><a href="<?php echo admin_url('admin.php?page=wpfilebase_tpls#list') ?>" target="_parent"><?php _e('Add Template', WPFB) ?></a></i>
</form>
<form id="list">
	<h2><?php _e('Sort Order:'); ?></h2>
	<p>
	<label for="list-sort-by"><?php _e("Sort by:") ?></label>
	<select name="list-sort-by" id="list-sort-by" style="width:100%">
		<option value=""><?php _e('Default'); echo ' ('.WPFB_Core::GetOpt('filelist_sorting').')'; ?></option>
		<?php $opts = WPFB_Admin::FileSortFields();
		foreach($opts as $tag => $name) echo '<option value="'.$tag.'">'.$tag.' - '.$name.'</option>'; ?>
	</select>	
	<input type="radio" checked="checked" name="list-sort-order" id="list-sort-order-asc" value="asc" />
	<label for="list-sort-order-asc" class="radio"><?php _e('Ascending'); ?></label>
	<input type="radio" name="list-sort-order" id="list-sort-order-desc" value="desc" />
	<label for="list-sort-order-desc" class="radio"><?php _e('Descending'); ?></label>
	</p>
	<p>
	<label for="list-show-cats"><?php _e('Files per page:',WPFB) ?></label>
	<input name="list-num" type="text" id="list-num" value="0" class="small-text" />
	<?php printf(__('Set to 0 to use the default limit (%d), -1 will disable pagination.',WPFB), WPFB_Core::GetOpt('filelist_num')) ?>
	</p>
	<p>
	<input type="checkbox" id="list-show-cats" name="list-show-cats" value="1" />
	<label for="list-show-cats"><?php _e('List selected Categories',WPFB) ?></label>
	</p>
	
	<p><a class="button" style="float: right;" href="javascript:void(0)" onclick="return insListTag()"><?php echo _e('Insert') ?></a></p>
</form>


<form id="browser">
	<p><?php _e('Select the root category of the tree view file browser:',WPFB); ?><br />	
	<select name="browser-root" id="browser-root"><?php echo WPFB_Output::CatSelTree(array('none_label' => __('All'))); ?></select>
	</p>
	
	<p><a class="button" style="float: right;" href="javascript:void(0)" onclick="return insBrowserTag()"><?php echo _e('Insert') ?></a></p>
</form>

<!-- 
<form id="uploadform">
	<p><?php _e('Category where uploaded files will be moved in:',WPFB); ?><br />	
	<select name="uploadform-cat" id="uploadform-cat">
		<option value="-1"  style="font-style:italic;"><?php _e('Selectable by Uploader',WPFB); ?></option>
		<?php echo WPFB_Output::CatSelTree(array('none_label' => __('Upload to Root',WPFB))); ?>
	</select>
	</p>

	<p><input type="checkbox" id="uploadform-overwrite" name="uploadform-overwrite" value="1" /> <label for="uploadform-overwrite"><?php _e('Overwrite existing files', WPFB) ?></label></p>
	
	<p><a class="button" style="float: right;" href="javascript:void(0)" onclick="return insUploadFormTag()"><?php echo _e('Insert') ?></a></p>
</form>

 -->
<?php } /*manage_attachments*/ ?>

<?php
do_action('admin_print_footer_scripts');
?>
<script type="text/javascript">if(typeof wpOnload=='function')wpOnload();</script>
<?php WPFB_Core::PrintJS(); /* only required for wpfbConf */ ?>
</body>
</html>