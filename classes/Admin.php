<?php
class WPFB_Admin {
	

static function InitClass()
{
	wpfb_loadclass('AdminLite', 'Item', 'File', 'Category');
	
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-tabs');
	wp_enqueue_script(WPFB.'-admin', WPFB_PLUGIN_URI.'js/admin.js', array(), WPFB_VERSION);			
}

static function SettingsSchema()
{
	$multiple_entries_desc = __('One entry per line. Seperate the title and a short tag (not longer than 8 characters) with \'|\'.<br />All lines beginning with \'*\' are selected by default.', WPFB);
	$multiple_line_desc = __('One entry per line.', WPFB);
	$bitrate_desc = __('Limits the maximum tranfer rate for downloads. 0 = unlimited', WPFB);
	$traffic_desc = __('Limits the maximum data traffic. 0 = unlimited', WPFB);
	$dls_per_day = __('downloads per day', WPFB);
	$daily_limit_for = __('Daily limit for %s', WPFB);
	
	$upload_path_base = str_replace(ABSPATH, '', get_option('upload_path'));
	if($upload_path_base == '' || $upload_path_base == '/')
		$upload_path_base = 'wp-content/uploads';
	
	return array (
	
	// common
	'upload_path'			=> array('default' => $upload_path_base . '/filebase', 'title' => __('Upload Path', WPFB), 'type' => 'text', 'class' => 'code', 'size' => 65),
	'thumbnail_size'		=> array('default' => 120, 'title' => __('Thumbnail size'), 'type' => 'number', 'class' => 'num', 'size' => 8),
	'base_auto_thumb'		=> array('default' => true, 'title' => __('Auto-detect thumbnails'), 'type' => 'checkbox', 'desc' => __('Images are considered as thumbnails for files with the same name when syncing. (e.g `file.jpg` &lt;=&gt; `file.zip`)', WPFB)),
	
	'fext_blacklist'		=> array('default' => 'db,tmp', 'title' => __('Extension Blacklist', WPFB), 'desc' => __('Files with an extension in this list are skipped while synchronisation. (seperate with comma)', WPFB), 'type' => 'text', 'class' => 'code', 'size' => 100),
	
	
	// display
	'auto_attach_files' 	=> array('default' => true,'title' => __('Show attached files', WPFB), 'type' => 'checkbox', 'desc' => __('If enabled, all associated files are listed below an article', WPFB)),
	'filelist_sorting'		=> array('default' => 'file_display_name', 'title' => __('Default sorting', WPFB), 'type' => 'select', 'desc' => __('The file property lists are sorted by', WPFB), 'options' => self::FileSortFields()),
	'filelist_sorting_dir'	=> array('default' => 0, 'title' => __('Sort Order:'/*def*/), 'type' => 'select', 'desc' => __('The sorting direction of file lists', WPFB), 'options' => array(0 => __('Ascending'), 1 => __('Descending'))),
	'filelist_num'			=> array('default' => 0, 'title' => __('Number of files per page', WPFB), 'type' => 'number', 'desc' => __('Length of the file list per page. Set to 0 to disable the limit.', WPFB)),
	
	// limits
	'bitrate_unregistered'	=> array('default' => 0, 'title' => __('Bit rate limit for guests', WPFB), 'type' => 'number', 'unit' => 'KiB/Sec', 'desc' => &$bitrate_desc),
	'bitrate_registered'	=> array('default' => 0, 'title' => __('Bit rate limit for registered users', WPFB), 'type' => 'number', 'unit' => 'KiB/Sec', 'desc' => &$bitrate_desc),	
	'traffic_day'			=> array('default' => 0, 'title' => __('Daily traffic limit', WPFB), 'type' => 'number', 'unit' => 'MiB', 'desc' => &$traffic_desc),
	'traffic_month'			=> array('default' => 0, 'title' => __('Monthly traffic limit', WPFB), 'type' => 'number', 'unit' => 'GiB', 'desc' => &$traffic_desc),
	'traffic_exceeded_msg'	=> array('default' => __('Traffic limit exceeded! Please try again later.', WPFB), 'title' => __('Traffic exceeded message', WPFB), 'type' => 'text', 'size' => 65),
	'file_offline_msg'		=> array('default' => __('This file is currently offline.', WPFB), 'title' => __('File offline message', WPFB), 'type' => 'text', 'size' => 65),
		
	'daily_user_limits'		=> array('default' => false, 'title' => __('Daily user download limits', WPFB), 'type' => 'checkbox', 'desc' => __('If enabled, unregistered users cannot download any files. You can set different limits for each user role below.', WPFB)), 	
	'daily_limit_subscriber'	=> array('default' => 5, 'title' => sprintf($daily_limit_for, _x('Subscriber', 'User role')), 'type' => 'number', 'unit' => &$dls_per_day),
	'daily_limit_contributor'	=> array('default' => 10, 'title' => sprintf($daily_limit_for, _x('Contributor', 'User role')), 'type' => 'number', 'unit' => &$dls_per_day),
	'daily_limit_author'		=> array('default' => 15, 'title' => sprintf($daily_limit_for, _x('Author', 'User role')), 'type' => 'number', 'unit' => &$dls_per_day),
	'daily_limit_editor'		=> array('default' => 20, 'title' => sprintf($daily_limit_for, _x('Editor', 'User role')), 'type' => 'number', 'unit' => &$dls_per_day),
	'daily_limit_exceeded_msg'	=> array('default' => __('You can only download %d files per day.', WPFB), 'title' => __('Daily limit exceeded message', WPFB), 'type' => 'text', 'size' => 65),
	
	// download
	'disable_permalinks'	=> array('default' => false, 'title' => __('Disable download permalinks', WPFB), 'type' => 'checkbox', 'desc' => __('Enable this if you have problems with permalinks.', WPFB)),
	'download_base'			=> array('default' => 'download', 'title' => __('Download URL base', WPFB), 'type' => 'text', 'desc' => sprintf(__('The url prefix for file download links. Example: <code>%s</code> (Only used when Permalinks are enabled.)', WPFB), get_option('home').'/%value%/category/file.zip')),
	
	'file_browser_post_id'		=> array('default' => '', 'title' => __('Post ID of the file browser', WPFB), 'type' => 'number', 'unit' => '<span id="file_browser_post_title">'.(($fbid=WPFB_Core::GetOpt('file_browser_post_id'))?get_the_title($fbid):'').'</span> <a href="javascript:;" class="button" onclick="WPFB_PostBrowser(\'file_browser_post_id\',\'file_browser_post_title\')">' . __('Select') . '</a>', 'desc' => __('Specify the ID of the post or page where the file browser should be placed. If you want to disable this feature leave the field blank.', WPFB)),
	
	'file_browser_cat_sort_by'		=> array('default' => 'cat_name', 'title' => __('File browser category sorting', WPFB), 'type' => 'select', 'desc' => __('The category property categories in the file browser are sorted by', WPFB), 'options' => self::CatSortFields()),
	'file_browser_cat_sort_dir'	=> array('default' => 0, 'title' => __('Sort Order:'/*def*/), 'type' => 'select', 'desc' => '', 'options' => array(0 => __('Ascending'), 1 => __('Descending'))),
	
	'file_browser_file_sort_by'		=> array('default' => 'file_display_name', 'title' => __('File browser file sorting', WPFB), 'type' => 'select', 'desc' => __('The file property files in the file browser are sorted by', WPFB), 'options' => self::FileSortFields()),
	'file_browser_file_sort_dir'	=> array('default' => 0, 'title' => __('Sort Order:'/*def*/), 'type' => 'select', 'desc' => '', 'options' => array(0 => __('Ascending'), 1 => __('Descending'))),
	
	
	'cat_drop_down'			=> array('default' => false, 'title' => __('Category drop down list', WPFB), 'type' => 'checkbox', 'desc' => __('Use category drop down list in the file browser instead of listing like files.', WPFB)),

	'force_download'		=> array('default' => false, 'title' => __('Always force download', WPFB), 'type' => 'checkbox', 'desc' => __('If enabled files that can be viewed in the browser (like images, PDF documents or videos) can only be downloaded (no streaming).', WPFB)),
	'range_download'		=> array('default' => true, 'title' => __('Send HTTP-Range header', WPFB), 'type' => 'checkbox', 'desc' => __('Allows users to pause downloads and continue later. In addition download managers can use multiple connections at the same time.', WPFB)),
	'hide_links'			=> array('default' => false, 'title' => __('Hide download links', WPFB), 'type' => 'checkbox', 'desc' => sprintf(__('File download links wont be displayed in the browser\'s status bar. You should enable \'%s\' to make it even harder to find out the URL.', WPFB), __('Always force download', WPFB))),
	'ignore_admin_dls'		=> array('default' => true, 'title' => __('Ignore downloads by admins', WPFB), 'type' => 'checkbox'),
	'hide_inaccessible'		=> array('default' => true, 'title' => __('Hide inaccessible files and categories', WPFB), 'type' => 'checkbox', 'desc' => __('If enabled files tagged <i>For members only</i> will not be listed for guests or users whith insufficient rights.', WPFB)),
	'inaccessible_msg'		=> array('default' => __('You are not allowed to access this file!', WPFB), 'title' => __('Inaccessible file message', WPFB), 'type' => 'text', 'size' => 65, 'desc' => __('This message will be displayed if users try to download a file they cannot access', WPFB)),
	'inaccessible_redirect'	=> array('default' => false, 'title' => __('Redirect to login', WPFB), 'type' => 'checkbox', 'desc' => __('Guests trying to download inaccessible files are redirected to the login page if this option is enabled.', WPFB)),
	'login_redirect_src'	=> array('default' => false, 'title' => __('Redirect to referring page after login', WPFB), 'type' => 'checkbox', 'desc' => __('Users are redirected to the page where they clicked on the download link after logging in.', WPFB)),
	
	'http_nocache'			=> array('default' => false, 'title' => __('Disable HTTP Caching', WPFB), 'type' => 'checkbox', 'desc' => __('Enable this if you have problems with downloads while using Wordpress with a cache plugin.', WPFB)),
	
	'parse_tags_rss'		=> array('default' => true, 'title' => __('Parse template tags in RSS feeds', WPFB), 'type' => 'checkbox', 'desc' => __('If enabled WP-Filebase content tags are parsed in RSS feeds.', WPFB)),
	
	'allow_srv_script_upload'	=> array('default' => false, 'title' => __('Allow script upload', WPFB), 'type' => 'checkbox', 'desc' => __('If you enable this, scripts like PHP or CGI can be uploaded. <b>WARNING:</b> Enabling script uploads is a <b>security risk</b>!', WPFB)),
	'protect_upload_path'	=> array('default' => true, 'title' => __('Protect upload path', WPFB), 'type' => 'checkbox', 'desc' => __('This prevents direct access to files in the upload directory.', WPFB)),
	
	'accept_empty_referers'	=> array('default' => true, 'title' => __('Accept empty referers', WPFB), 'type' => 'checkbox', 'desc' => __('If enabled, direct-link-protected files can be downloaded when the referer is empty (i.e. user entered file url in address bar or browser does not send referers)', WPFB)),	
	'allowed_referers' 		=> array('default' => '', 'title' => __('Allowed referers', WPFB), 'type' => 'textarea', 'desc' => __('Sites with matching URLs can link to files directly.', WPFB).'<br />'.$multiple_line_desc),
	
	'decimal_size_format'	=> array('default' => false, 'title' => __('Decimal file size prefixes', WPFB), 'type' => 'checkbox', 'desc' => __('Enable this if you want decimal prefixes (1 MB = 1000 KB = 1 000 000 B) instead of binary (1 MiB = 1024 KiB = 1 048 576 B)', WPFB)),
	
	'admin_bar'	=> array('default' => true, 'title' => __('Add WP-Filebase to admin menu bar', WPFB), 'type' => 'checkbox', 'desc' => __('Display some quick actions for file management in the admin menu bar.', WPFB)),
	//'file_context_menu'	=> array('default' => true, 'title' => '', 'type' => 'checkbox', 'desc' => ''),
	
	'cron_sync'	=> array('default' => false, 'title' => __('Automatic Sync', WPFB), 'type' => 'checkbox', 'desc' => __('Schedules a cronjob to hourly synchronize the filesystem and the database.', WPFB)),
	
	'disable_id3' =>  array('default' => false, 'title' => __('Disable ID3 tag detection', WPFB), 'type' => 'checkbox', 'desc' => __('This disables all meta file info reading. Use this option if you have issues adding large files.', WPFB)),
	
	'search_id3' =>  array('default' => false, 'title' => __('Search ID3 Tags', WPFB), 'type' => 'checkbox', 'desc' => __('Search in file meta data, like ID3 for MP3 files, EXIF for JPEG... (this option does not increase significantly server load since all data is cached in a MySQL table)', WPFB)),
	
	
	'languages'				=> array('default' => "English|en\nDeutsch|de", 'title' => __('Languages'), 'type' => 'textarea', 'desc' => &$multiple_entries_desc),
	'platforms'				=> array('default' => "Windows 95|win95\n*Windows 98|win98\n*Windows 2000|win2k\n*Windows XP|winxp\n*Windows Vista|vista\n*Windows 7|win7\nLinux|linux\nMac OS X|mac", 'title' => __('Platforms', WPFB), 'type' => 'textarea', 'desc' => &$multiple_entries_desc, 'nowrap' => true),	
	'licenses'				=> array('default' =>
"*Freeware|free\nShareware|share\nGNU General Public License|gpl|http://www.gnu.org/copyleft/gpl.html\nGNU Lesser General Public License|lgpl\nGNU Affero General Public License|agpl\nCC Attribution-NonCommercial-ShareAlike|ccbyncsa|http://creativecommons.org/licenses/by-nc-sa/3.0/", 'title' => __('Licenses', WPFB), 'type' => 'textarea', 'desc' => &$multiple_entries_desc, 'nowrap' => true),
	'requirements'			=> array('default' =>
"PDF Reader|pdfread|http://www.foxitsoftware.com/pdf/reader/addons.php
Java|java|http://www.java.com/download/
Flash|flash|http://get.adobe.com/flashplayer/
Open Office|ooffice|http://download.openoffice.org/
.NET Framework 3.5|.net35|http://www.microsoft.com/downloads/details.aspx?FamilyID=333325fd-ae52-4e35-b531-508d977d32a6",
	'title' => __('Requirements', WPFB), 'type' => 'textarea', 'desc' => $multiple_entries_desc . ' ' . __('You can optionally add |<i>URL</i> to each line to link to the required software/file.', WPFB), 'nowrap' => true),
	
	
	'template_file'			=> array('default' =>
<<<TPLFILE
<div class="wpfilebase-attachment">
 <div class="wpfilebase-fileicon"><a href="%file_url%" title="Download %file_display_name%"><img align="middle" src="%file_icon_url%" alt="%file_display_name%" /></a></div>
 <div class="wpfilebase-rightcol">
  <div class="wpfilebase-filetitle">
   <a href="%file_url%" title="Download %file_display_name%">%file_display_name%</a><br />
   %file_name%<br />
   <!-- IF %file_version% -->%'Version:'% %file_version%<br /><!-- ENDIF -->
   <!-- IF %file_post_id% AND %post_id% != %file_post_id% --><a href="%file_post_url%" class="wpfilebase-postlink">%'View post'%</a><!-- ENDIF -->
  </div>
  <div class="wpfilebase-filedetails" id="wpfilebase-filedetails%uid%" style="display: none;">
  <p>%file_description%</p>
  <table border="0">
   <!-- IF %file_languages% --><tr><td><strong>%'Languages'%:</strong></td><td>%file_languages%</td></tr><!-- ENDIF -->
   <!-- IF %file_author% --><tr><td><strong>%'Author'%:</strong></td><td>%file_author%</td></tr><!-- ENDIF -->
   <!-- IF %file_platforms% --><tr><td><strong>%'Platforms'%:</strong></td><td>%file_platforms%</td></tr><!-- ENDIF -->
   <!-- IF %file_requirements% --><tr><td><strong>%'Requirements'%:</strong></td><td>%file_requirements%</td></tr><!-- ENDIF -->
   <!-- IF %file_category% --><tr><td><strong>%'Category:'%</strong></td><td>%file_category%</td></tr><!-- ENDIF -->
   <!-- IF %file_license% --><tr><td><strong>%'License'%:</strong></td><td>%file_license%</td></tr><!-- ENDIF -->
   <tr><td><strong>%'Date'%:</strong></td><td>%file_date%</td></tr>
   <!-- <tr><td><strong>%'MD5 Hash'%:</strong></td><td><small>%file_hash%</small></td></tr> -->
  </table>
  </div>
 </div>
 <div class="wpfilebase-fileinfo">
  %file_size%<br />
  %file_hits% %'Downloads'%<br />
  <a href="#" onclick="return wpfilebase_filedetails(%uid%);">%'Details'%...</a>
 </div>
 <div style="clear: both;"></div>
</div>
TPLFILE
	, 'title' => __('Default File Template', WPFB), 'type' => 'textarea', 'desc' => (self::TplFieldsSelect('template_file') . '<br />' . __('The template for attachments', WPFB)), 'class' => 'code'),

	'template_cat'			=> array('default' =>
<<<TPLCAT
<div class="wpfilebase-attachment-cat">
 <div class="wpfilebase-fileicon"><a href="%cat_url%" title="Goto %cat_name%"><img align="middle" src="%cat_icon_url%" alt="%cat_name%" /></a></div>
 <div class="wpfilebase-rightcol">
  <div class="wpfilebase-filetitle">
   <p><a href="%cat_url%" title="Goto category %cat_name%">%cat_name%</a></p>
   %cat_num_files% <!-- IF %cat_num_files% == 1 -->file<!-- ELSE -->files<!-- ENDIF -->
  </div>
 </div>
 <div style="clear: both;"></div>
</div>
TPLCAT
	, 'title' => __('Category Template', WPFB), 'type' => 'textarea', 'desc' => (self::TplFieldsSelect('template_cat', false, true) . '<br />' . __('The template for category lists (used in the file browser)', WPFB)), 'class' => 'code'),

	'dlclick_js'			=> array('default' =>
<<<JS
if(typeof pageTracker == 'object') {
	pageTracker._trackPageview(file_url); // new google analytics tracker
} else if(typeof urchinTracker == 'function') {	
	urchinTracker(file_url); // old google analytics tracker
}
JS
	, 'title' => __('Download JavaScript', WPFB), 'type' => 'textarea', 'desc' => __('Here you can enter JavaScript Code which is executed when a user clicks on file download link. The following variables can be used: <i>file_id</i>: the ID of the file, <i>file_url</i>: the clicked download url', WPFB), 'class' => 'code'),

	//'max_dls_per_ip'			=> array('default' => 10, 'title' => __('Maximum downloads', WPFB), 'type' => 'number', 'unit' => 'per file, per IP Address', 'desc' => 'Maximum number of downloads of a file allowed for an IP Address. 0 = unlimited'),
	//'archive_lister'			=> array('default' => false, 'title' => __('Archive lister', WPFB), 'type' => 'checkbox', 'desc' => __('Uploaded files are scanned for archives', WPFB)),
	//'enable_ratings'			=> array('default' => false, 'title' => __('Ratings'), 'type' => 'checkbox', 'desc' => ''),
	);
}

static function TplVarsDesc($for_cat=false)
{
	if($for_cat) return array(	
	'cat_name'				=> __('The category name', WPFB),
	'cat_description'		=> __('Short description', WPFB),
	
	'cat_url'				=> __('The category URL', WPFB),
	'cat_path'				=> __('Category path (e.g cat1/cat2/)', WPFB),
	'cat_folder'			=> __('Just the category folder name, not the path', WPFB),
	
	'cat_parent_name'		=> __('Name of the parent categories (empty if none)', WPFB),
	'cat_num_files'			=> __('Number of files in the category', WPFB),
	'cat_num_files_total'			=> __('Number of files in the category and all child categories', WPFB),
	
	'cat_required_level'	=> __('The minimum user level to view this category (-1 = guest, 0 = Subscriber ...)', WPFB),
	
	'cat_id'				=> __('The category ID', WPFB),
	'uid'					=> __('A unique ID number to identify elements within a template', WPFB),
	);
	else return array(	
	'file_name'				=> __('Name of the file', WPFB),
	'file_size'				=> __('Formatted file size', WPFB),
	'file_date'				=> __('Formatted file date', WPFB),
	'file_thumbnail'		=> __('Name of the thumbnail file', WPFB),
	'file_display_name'		=> __('Title', WPFB),
	'file_description'		=> __('Short description', WPFB),
	'file_version'			=> __('File version', WPFB),
	'file_author'			=> __('Author'),
	'file_languages'		=> __('Supported languages', WPFB),
	'file_platforms'		=> __('Supported platforms (operating systems)', WPFB),
	'file_requirements'		=> __('Requirements to use this file', WPFB),
	'file_license'			=> __('License', WPFB),
	'file_required_level'	=> __('The minimum user level to download this file (-1 = guest, 0 = Subscriber ...)', WPFB),
	'file_offline'			=> __('1 if file is offline, otherwise 0', WPFB),
	'file_direct_linking'	=> __('1 if direct linking is allowed, otherwise 0', WPFB),
	'file_category'			=> __('The category name', WPFB),
	//'file_update_of'		=>
	'file_post_id'			=> __('ID of the post/page this file belongs to', WPFB),
	'file_added_by'			=> __('User ID of the uploader', WPFB),
	'file_hits'				=> __('How many times this file has been downloaded.', WPFB),
	//'file_ratings'			=>
	//'file_rating_sum'		=>
	'file_last_dl_ip'		=> __('IP Address of the last downloader', WPFB),
	'file_last_dl_time'		=> __('Time of the last download', WPFB),
	
	'file_extension'		=> sprintf(__('Lowercase file extension (e.g. \'%s\')', WPFB), 'pdf'),
	'file_type'				=> sprintf(__('File content type (e.g. \'%s\')', WPFB), 'image/png'),
	
	'file_url'				=> __('Download URL', WPFB),
	'file_url_encoded'		=> __('Download URL encoded for use in query strings', WPFB),
	'file_post_url'			=> __('URL of the post/page this file belongs to', WPFB),
	'file_icon_url'			=> __('URL of the thumbnail or icon', WPFB),
	'file_path'				=> __('Category path and file name (e.g cat1/cat2/file.ext)', WPFB),
	
	'file_id'				=> __('The file ID', WPFB),
	
	'uid'					=> __('A unique ID number to identify elements within a template', WPFB),
	'post_id'				=> __('ID of the current post or page', WPFB),
	'wpfb_url'				=> sprintf(__('Plugin root URL (%s)',WPFB), WPFB_PLUGIN_URI)
	);
}

static function FileSortFields()
{
	return array(
	'file_display_name'		=> __('Title', WPFB),
	'file_name'				=> __('Name of the file', WPFB),
	'file_version'			=> __('File version', WPFB),
	
	'file_hits'				=> __('How many times this file has been downloaded.', WPFB),
	'file_size'				=> __('Formatted file size', WPFB),
	'file_date'				=> __('Formatted file date', WPFB),
	'file_last_dl_time'		=> __('Time of the last download', WPFB),
	
	'file_path'				=> __('Relative path of the file'),
	'file_id'				=> __('File ID'),
	
	'file_category_name'	=> __('Category Name', WPFB),
	'file_category'			=> __('Category ID', WPFB),
	
	'file_description'		=> __('Short description', WPFB),	
	'file_author'			=> __('Author', WPFB),
	'file_license'			=> __('License', WPFB),
	
	'file_post_id'			=> __('ID of the post/page this file belongs to', WPFB),
	'file_required_level'	=> __('The minimum user level to download this file (-1 = guest, 0 = Subscriber ...)', WPFB),
	'file_added_by'			=> __('User ID of the uploader', WPFB),
	
	//'file_offline'			=> __('Offline &gt; Online', WPFB),
	//'file_direct_linking'	=> __('Direct linking &gt; redirect to post', WPFB),
	
	);
}

static function CatSortFields()
{
	return array(
	'cat_name'			=> __('Category Name'),
	'cat_folder'		=> __('Name of the category folder', WPFB),
	'cat_description'	=> __('Short description', WPFB),	
	
	'cat_path'			=> __('Relative path of the category folder', WPFB),
	'cat_id'			=> __('Category ID'),
	'cat_parent'		=> __('Parent category ID', WPFB),
	
	'cat_num_files'		=> __('Number of files directly in the category', WPFB),
	'cat_num_files_total' => __('Number of all files in the category and all sub-categories', WPFB),
	
	'cat_required_level' => __('The minimum user level to access (-1 = guest, 0 = Subscriber ...)', WPFB)
	);
}

static function TplFieldsSelect($input, $short=false, $for_cat=false)
{
	$out = __('Add template variable:', WPFB) . ' <select name="_wpfb_tpl_fields" onchange="WPFB_AddTplVar(this, \'' . $input . '\')"><option value="">'.__('Select').'</option>';	
	foreach(self::TplVarsDesc($for_cat) as $tag => $desc)
		$out .= '<option value="'.$tag.'" title="'.$desc.'">'.$tag.($short ? '' : ' ('.$desc.')').'</option>';
	$out .= '</select>';
	$out .= '<small>(For some files there are more tags available. You find a list of all tags below the form when editing a file.)</small>';
	return $out;
}

// copy of wp's copy_dir, but moves everything
static function MoveDir($from, $to)
{
	require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php');
	require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php');
	
	$wp_filesystem = new WP_Filesystem_Direct(null);
	
	$dirlist = $wp_filesystem->dirlist($from);

	$from = trailingslashit($from);
	$to = trailingslashit($to);

	foreach ( (array) $dirlist as $filename => $fileinfo ) {
		if ( 'f' == $fileinfo['type'] ) {
			if ( ! $wp_filesystem->move($from . $filename, $to . $filename, true) )
				return false;
			$wp_filesystem->chmod($to . $filename, octdec(WPFB_PERM_FILE));
		} elseif ( 'd' == $fileinfo['type'] ) {
			if ( !$wp_filesystem->mkdir($to . $filename, octdec(WPFB_PERM_DIR)) )
				return false;
			if(!self::MoveDir($from . $filename, $to . $filename))
				return false;
		}
	}
	
	// finally delete the from dir
	@rmdir($from);
	
	return true;
}

static function InsertCategory($catarr)
{
	global $wpdb;
	
	$catarr = wp_parse_args($catarr, array('cat_id' => 0, 'cat_name' => '', 'cat_description' => '', 'cat_parent' => 0, 'cat_folder' => ''));
	extract($catarr, EXTR_SKIP);

	$cat_id = intval($cat_id);
	$cat_parent = intval($cat_parent);
	$update = ($cat_id > 0); // update or creating??
	$add_existing = !empty($add_existing);
	$cat = $update ? WPFB_Category::GetCat($cat_id) : new WPFB_Category(array('cat_id' => 0));
	$cat->Lock(true);
	
	// some validation
	if (empty($cat_name) && empty($cat_folder)) return array( 'error' => __('You must enter a category name or a folder name.', WPFB) );
	if(!$add_existing && !empty($cat_folder)) {
		$cat_folder = preg_replace('/\s/', ' ', $cat_folder);
		if(!preg_match('/^[0-9a-z-_.+,\s]+$/i', $cat_folder)) return array( 'error' => __('The category folder name contains invalid characters.', WPFB) );	
	}
	if (empty($cat_name)) $cat_name = WPFB_Output::Filename2Title($cat_folder, false);
	elseif(empty($cat_folder)) $cat_folder = strtolower(str_replace(' ', '_', $cat_name));
	

	$cat->cat_name = trim($cat_name);
	$cat->cat_description = trim($cat_description);
	$cat->cat_exclude_browser = (int)!empty($cat_exclude_browser);	
	$cat->cat_required_level = empty($cat_members_only) ? 0 : (WPFB_Core::UserRole2Level($cat_required_role)+1);
	
	if($update && !empty($cat_child_apply_perm))
	{
		$files = $cat->GetChildFiles(true);
		foreach($files as $child)
		{
			$child->file_required_level = $cat->cat_required_level;
			$child->DBSave();
		}		
		$cats = $cat->GetChildCats(true);
		foreach($cats as $child)
		{
			$child->cat_required_level = $cat->cat_required_level;
			$child->DBSave();
		}
	}
		
	// handle parent cat
	if($cat_parent <= 0 || $cat_parent == $cat_id) {
		$cat_parent = 0;
		$pcat = null;
	} else {
		$pcat = WPFB_Category::GetCat($cat_parent);
		if($pcat == null || ($update && $cat->IsAncestorOf($pcat))) $cat_parent = $cat->cat_parent;
	}
	
	$result = $cat->ChangeCategoryOrName($cat_parent, $cat_folder, $add_existing);
	if(!empty($result['error'])) return $result;	
		
	// icon
	if(!empty($cat_icon_delete)) {
		@unlink($cat->GetThumbPath());
		$cat->cat_icon = null;
	}
	if(!empty($cat_icon) && @is_uploaded_file($cat_icon['tmp_name']) && !empty($cat_icon['name'])) {
		$ext = strtolower(substr($cat_icon['name'], strrpos($cat_icon['name'], '.')+1));
		if($ext == 'jpg' || $ext == 'jpeg' || $ext == 'png' || $ext == 'gif') {
			if(!empty($cat->cat_icon))
				@unlink($cat->GetThumbPath());
			$cat->cat_icon = '_caticon.'.$ext;
			if(!@move_uploaded_file($cat_icon['tmp_name'], $cat->GetThumbPath()))
				return array( 'error' => __( 'Unable to move category icon!', WPFB));	
			@chmod($cat->GetThumbPath(), octdec(WPFB_PERM_FILE));
		}
	}
	
	// save into db
	$cat->Lock(false);
	$result = $cat->DBSave();	
	if(!empty($result['error']))
		return $result;		
	$cat_id = (int)$result['cat_id'];	
	
	return array( 'error' => false, 'cat_id' => $cat_id);
}

static function InsertFile($data)
{
	if(!is_object($data)) $data = (object)$data;
	
	$file_id = isset($data->file_id) ? (int)$data->file_id : 0;
	$file = null;
	if($file_id > 0) {
		$file = WPFB_File::GetFile($file_id);
		if($file == null) $file_id = 0;
	}	
	$update = ($file_id > 0 && $file != null && $file->is_file);	
	if(!$update) $file = new WPFB_File(array('file_id' => 0));
	$file->Lock(true);
	$add_existing = !empty($data->add_existing); // if the file is added by a sync (not uploaded)
	
	// are we uploading a file?
	$upload = (!$add_existing && (@is_uploaded_file($data->file_upload['tmp_name']) && !empty($data->file_upload['name'])));
	$remote_upload = (!$add_existing && !$upload && !empty($data->file_is_remote) && !empty($data->file_remote_uri) && (!$update || $file->file_remote_uri != $data->file_remote_uri));
	$remote_redirect = !empty($data->file_remote_redirect) && !empty($data->file_remote_uri);
	
	// are we uploading a thumbnail?
	$upload_thumb = (!$add_existing && @is_uploaded_file($data->file_upload_thumb['tmp_name']) && self::IsValidImage($data->file_upload_thumb['tmp_name']) !== false);
	
	$file_src_path = $upload ? $data->file_upload['tmp_name'] : ($remote_upload ? parse_url($data->file_remote_uri,PHP_URL_PATH) : ($add_existing ? $data->file_path : null));
	$file_name = $upload ? $data->file_upload['name'] : ((empty($file_src_path) && $update) ? $file->file_name : basename($file_src_path));
	
	// VALIDATION
	$current_user = wp_get_current_user();
	if(!$add_existing && empty($current_user->ID)) return array( 'error' => __('Could not get user id!', WPFB) );	
	
	if(!$update && !$add_existing && !$upload && !$remote_upload) return array( 'error' => __('No file was uploaded.', WPFB) );

	// check extension
	if($upload || $add_existing) {
		if(!self::_isAllowedFileExt($file_name)) {
			@unlink($file_src_path);
			return array( 'error' => sprintf( __( 'The file extension of the file <b>%s</b> is forbidden!', WPFB), $file_name ) );
		}
	}
	// check url
	if($remote_upload && !preg_match('/^https?:\/\//', $data->file_remote_uri))	return array( 'error' => __('Only HTTP links are supported.', WPFB) );
	
	
	// do some simple file stuff
	if($update && (!empty($data->file_delete_thumb) || $upload_thumb)) $file->DeleteThumbnail(); // delete thumbnail if user wants to	
	if($update && ($upload||$remote_upload)) $file->Delete(); // if we update, delete the old file
	

	// handle display name and version
	if(isset($data->file_version)) $file->file_version = $data->file_version;	
	if(isset($data->file_display_name)) $file->file_display_name = $data->file_display_name;	
	$result = self::ParseFileNameVersion($file_name, $file->file_version);	
	if(empty($file->file_version)) $file->file_version = $result['version'];
	if(empty($file->file_display_name)) $file->file_display_name = $result['title'];	

	
	// handle category & name
	$file_category = intval($data->file_category);
	$new_cat = null;
	if ($file_category > 0 && ($new_cat=WPFB_Category::GetCat($file_category)) == null) $file_category = 0;
	
	$result = $file->ChangeCategoryOrName($file_category, $file_name, $add_existing);
	if(!empty($result['error'])) return $result;	

	// if there is an uploaded file 
	if($upload) {
		if(@file_exists($file->GetLocalPath())) return array( 'error' => sprintf( __( 'File %s already exists. You have to delete it first!', WPFB), $file->GetLocalPath() ) );
		if(!@move_uploaded_file($file_src_path, $file->GetLocalPath()) || !@file_exists($file->GetLocalPath())) return array( 'error' => sprintf( __( 'Unable to move file %s! Is the upload directory writeable?', WPFB), $file->file_name ) );
	} elseif($remote_upload) {
		require_once(ABSPATH . 'wp-admin/includes/file.php');			
		$result = self::SideloadFile($data->file_remote_uri);
		if(!empty($result['error'])) return $result;
		$tmp = $result['file'];
		$file_name = $file->file_name = basename($tmp, '.tmp');
		if(!$update && @file_exists($file->GetLocalPath()))	
			return array( 'error' => sprintf( __( 'File %s already exists. You have to delete it first!', WPFB), $file->GetLocalPath() ) );
			
		if(!self::_isAllowedFileExt($file_name)) {
			@unlink($tmp);
			return array( 'error' => sprintf( __( 'The file extension of the file <b>%s</b> is forbidden!', WPFB), $file_name ) );
		}
			
		if (is_wp_error($tmp)) return array('error' => $tmp->get_error_message());
		if(!rename($tmp,$file->GetLocalPath())) return array( 'error' => sprintf( __( 'Unable to move file %s! Is the upload directory writeable?', WPFB), $file->file_name ) );	
	} elseif(!$add_existing && !$update) {
		return array( 'error' => __('No file was uploaded.', WPFB) );
	}
	
	if($upload || $remote_upload || $add_existing) {
		if($add_existing && !empty($data->file_thumbnail))
			$file->file_thumbnail = $data->file_thumbnail; // we already got the thumbnail on disk!		
		elseif(empty($file->file_thumbnail) && !$upload_thumb && ($file->GetExtension() == '.bmp' || @getimagesize($file->GetLocalPath()) !== false))
			$file->CreateThumbnail();	// check if the file is an image and create thumbnail
	}
	
	// get file info
	if((!$update || !$remote_redirect) && is_file($file->GetLocalPath()))
	{
		$file->file_size = filesize($file->GetLocalPath());
		$file->file_hash = md5_file($file->GetLocalPath());
		
		$file->SetModifiedTime(!empty($file_date) ? $file_date : gmdate('Y-m-d H:i:s', filemtime($file->GetLocalPath())));
		
		if(!WPFB_Core::GetOpt('disable_id3'))
		{
			wpfb_loadclass('GetID3');
			$file_info = WPFB_GetID3::AnalyzeFile($file->GetLocalPath());
		}
		
		if(!$upload_thumb && empty($data->file_thumbnail) && !empty($file_info['tags']['id3v2']['picture']))
		{
			$cover = $file->GetLocalPath();
			$cover = substr($cover,0,strrpos($cover,'.')).'.jpg';
			file_put_contents($cover, $file_info['tags']['id3v2']['picture'][0]['data']);
			$file->CreateThumbnail($cover, true);
			@unlink($cover);
		}
	}
	
	if($remote_redirect) {
		// when download redircet the actual files is not needed anymore
		@unlink($file->GetLocalPath());
	} else {
		// set permissions
		@chmod ($file->GetLocalPath(), octdec(WPFB_PERM_FILE));
		// no redirection, URI is not neede anymore
		$data->file_remote_uri = '';
		$file->file_remote_uri = '';
		
	}
	
	if(!empty($data->file_languages)) $file->file_language = implode('|', $data->file_languages);
	if(!empty($data->file_platforms)) $file->file_platform = implode('|', $data->file_platforms);
	if(!empty($data->file_requirements)) $file->file_requirement = implode('|', $data->file_requirements);
	
	$file->file_offline = (int)(!empty($data->file_offline));
	
	$file->file_required_level = empty($data->file_members_only) ? 0 : (WPFB_Core::UserRole2Level($data->file_required_role)+1);
	
	if(!isset($data->file_direct_linking))
		$data->file_direct_linking = 1; // allow direct linking by default
	$file->file_direct_linking = (int)!empty($data->file_direct_linking);

	$var_names = array('remote_uri', 'author', 'date', 'post_id', 'description', 'hits', 'license');
	for($i = 0; $i < count($var_names); $i++)
	{
		$vn = 'file_' . $var_names[$i];
		if(isset($data->$vn)) $file->$vn = $data->$vn;
	}		

	// set the user id
	if(!$update && !empty($current_user)) $file->file_added_by = $current_user->ID;	

	// if thumbnail was uploaded
	if($upload_thumb)
	{
		// delete the old thumbnail (if existing)
		$file->DeleteThumbnail();
		
		$thumb_dest_path = dirname($file->GetLocalPath()) . '/thumb_' . $data->file_upload_thumb['name'];
				
		if(@move_uploaded_file($data->file_upload_thumb['tmp_name'], $thumb_dest_path))
		{
			$file->CreateThumbnail($thumb_dest_path, true);
		}
	}
	
	// save into db
	$file->Lock(false);
	$result = $file->DBSave();	
	if(!empty($result['error'])) return $result;		
	$file_id = (int)$result['file_id'];
	
	if(!empty($file_info))
		WPFB_GetID3::StoreFileInfo($file_id, $file_info);
	
	return array( 'error' => false, 'file_id' => $file_id);
}

static function IsValidImage($img) {
	return @getimagesize($img) !== false;
}

static function ParseFileNameVersion($file_name, $file_version) {
	$fnwv = substr($file_name, 0, strrpos($file_name, '.'));// remove extension
	if(empty($file_version)) {
		$matches = array();		
		if(preg_match('/[-_\.]v?([0-9]{1,2}\.[0-9]{1,2}(\.[0-9]{1,2}){0,2})(-[a-zA-Z_]+)?$/', $fnwv, $matches)) {
			$file_version = $matches[1];
			if((strlen($fnwv)-strlen($matches[0])) > 1)
				$fnwv = substr($fnwv, 0, -strlen($matches[0]));
		}	
	} elseif(substr($fnwv, -strlen($file_version)) == $file_version) {		
		$fnwv = trim(substr($fnwv, 0, -strlen($file_version)), '-');
	}
	$title = wpfb_call('Output', 'Filename2Title', array($fnwv, false), true);	
	return array('title' => empty($title) ? $file_name : $title, 'version' => $file_version);
}

static function SideloadFile($url) {
	//WARNING: The file is not automatically deleted, The script must unlink() the file.
	
	if ( ! $url ) return array('error' => __('Invalid URL Provided.'));

	$tmpfname = wp_tempnam($url);
	if ( ! $tmpfname )
		return array('error' => __('Could not create Temporary file.'));

	wpfb_loadclass('Download');
	$result = WPFB_Download::SideloadFile($url, $tmpfname);
	if(!empty($result['error'])) return $result;
	
	$newname = $tmpfname;
	
	// do smart stuff with file extension
	if(!empty($response['headers']['content-disposition'])) {
		$matches = array();
		if(preg_match('/filename="(.+?)"/', $response['headers']['content-disposition'], $matches) == 1)
			$newname = dirname($tmpfname).'/'.$matches[1].'.tmp';
	} elseif(substr($tmpfname, -4, 4) == '.tmp') {
		wpfb_loadclass('Download');
		$ext = strrchr(parse_url($url,PHP_URL_PATH), '.');
		
		// compare extension type with http header type, if they are different deterime proper extension from content type
		$exType = WPFB_Download::GetFileType($ext);
		$hType = strtolower($response['headers']['content-type']);
		if($exType != $hType && ($e=WPFB_Download::FileType2Ext($hType)) != null)
			$ext = '.'.$e;
					
		if(strlen($ext)>1)
			$newname = substr($tmpfname, 0, -4).$ext.'.tmp';
	}
	
	rename($tmpfname, $newname);	
	return array('error'=>false,'file'=>$newname);
}

static function Sync($hash_sync=false)
{
	@set_time_limit(0);
	wpfb_loadclass("GetID3");
	require_once(ABSPATH . 'wp-admin/includes/file.php');
	
	$result = array('missing_files' => array(), 'missing_folders' => array(), 'changed' => array(), 'not_added' => array(), 'error' => array(), 'updated_categories' => array());
	WPFB_Admin::UpdateItemsPath();
	$files = &WPFB_File::GetFiles();
	$cats =& WPFB_Category::GetCats();
	
	$file_paths = array();
	foreach($files as $id => /* & PHP 4 compability */ $file)
	{
		$file_path = str_replace('\\', '/', $file->GetLocalPath(true));
		$file_paths[] = $file_path;
		if($file->GetThumbPath())
			$file_paths[] = str_replace('\\', '/', $file->GetThumbPath());
		
		// TODO: check for file changes remotly
		if($file->IsRemote())
			continue;
			
		if(!@is_file($file_path) || !@is_readable($file_path))
		{
			$result['missing_files'][$id] = $file;
			continue;
		}
		
		if($hash_sync) $file_hash = @md5_file($file_path);
		$file_size = (int)@filesize($file_path);
		$file_time = filemtime($file_path);
		$file_analyzetime = WPFB_Core::GetOpt('disable_id3') ? $file_time : WPFB_GetID3::GetFileAnalyzeTime($file);
		if(is_null($file_analyzetime)) $file_analyzetime = 0;
		
		if( ($hash_sync && $file->file_hash != $file_hash) || $file->file_size != $file_size || $file->GetModifiedTime() != $file_time || $file_analyzetime < $file_time)
		{
			$file->file_size = $file_size;
			$file->file_date = gmdate('Y-m-d H:i:s', $file_time);
			$file->file_hash = $hash_sync ? $file_hash : @md5_file($file_path);
			
			if(!WPFB_Core::GetOpt('disable_id3'))
				WPFB_GetID3::UpdateCachedFileInfo($file);
			
			$res = $file->DBSave();
			
			if(!empty($res['error']))
				$result['error'][$id] = $file;
			else
				$result['changed'][$id] = $file;
		}
	}
	
	foreach($cats as $id => $cat) {
		$cat_path = $cat->GetLocalPath(true);
		if(!@is_dir($cat_path) || !@is_readable($cat_path))
		{
			$result['missing_folders'][$id] = $cat;
			continue;
		}		
	}
	
	// search for not added files
	$upload_dir = str_replace('\\', '/', WPFB_Core::UploadDir());	
	$uploaded_files = str_replace('\\', '/', list_files($upload_dir));
	$upload_dir_len = strlen($upload_dir);
	
	$thumbnails = array();
	
	
	// look for thumnails
	// find files that have names formatted like thumbnails e.g. file-XXxYY.(jpg|jpeg|png|gif)
	for($i = 1; $i < count($uploaded_files); $i++)
	{
		$len = strrpos($uploaded_files[$i], '.');
		
		// file and thumbnail should be neighbours in the list, so only check the prev element for matching name
		if(strlen($uploaded_files[$i-1]) > ($len+2) && substr($uploaded_files[$i-1],0,$len) == substr($uploaded_files[$i],0,$len) && !in_array($uploaded_files[$i-1], $file_paths))
		{
			$suffix = substr($uploaded_files[$i-1], $len);
			
			$matches = array();
			if(preg_match('/^-([0-9]+)x([0-9]+)\.(jpg|jpeg|png|gif)$/i', $suffix, $matches) && ($is = getimagesize($uploaded_files[$i-1])))
			{
				if($is[0] == $matches[1] && $is[1] == $matches[2])
				{
					//ok, found a thumbnail here
					$thumbnails[$uploaded_files[$i]] = basename($uploaded_files[$i-1]);
					$uploaded_files[$i-1] = ''; // remove the file from the list
					continue;
				}
			}			
		}
	}
	

	if(WPFB_Core::GetOpt('base_auto_thumb')) {
		for($i = 0; $i < count($uploaded_files); $i++)
		{
			$len = strrpos($uploaded_files[$i], '.');
			$ext = strtolower(substr($uploaded_files[$i], $len+1));

			if($ext != 'jpg' && $ext != 'png' && $ext != 'gif') {
				$prefix = substr($uploaded_files[$i], 0, $len);

				for($ii = $i-1; $ii >= 0; $ii--)
				{
					if(substr($uploaded_files[$ii],0, $len) != $prefix) break;						
					$e = strtolower(substr($uploaded_files[$ii], $len+1));
					if($e == 'jpg' || $e == 'png' || $e == 'gif') {
						$thumbnails[$uploaded_files[$i]] = basename($uploaded_files[$ii]);
						$uploaded_files[$ii] = ''; // remove the file from the list		
						break;				
					}
				}
				
				for($ii = $i+1; $ii < count($uploaded_files); $ii++)
				{
					if(substr($uploaded_files[$ii],0, $len) != $prefix) break;						
					$e = strtolower(substr($uploaded_files[$ii], $len+1));
					if($e == 'jpg' || $e == 'png' || $e == 'gif') {
						$thumbnails[$uploaded_files[$i]] = basename($uploaded_files[$ii]);
						$uploaded_files[$ii] = ''; // remove the file from the list		
						break;				
					}
				}
			}
		}
	}
	
	$fext_blacklist = array_map('strtolower', array_map('trim', explode(',', WPFB_Core::GetOpt('fext_blacklist'))));
	
	for($i = 0; $i < count($uploaded_files); $i++)
	{
		$fn = $uploaded_files[$i];
		$fbn = basename($fn);
		if(strlen($fn) < 2 || $fbn{0} == '.' || $fbn == '_wp-filebase.css' || strpos($fbn, '_caticon.') !== false || in_array($fn, $file_paths) || !is_file($fn) || !is_readable($fn))
			continue;
			
		// check for blacklisted extension		
		if(!empty($fext_blacklist) && in_array(trim(strrchr($fbn, '.'),'.'), $fext_blacklist))
			continue;
					
		$res = self::AddExistingFile($fn, empty($thumbnails[$fn]) ? null : $thumbnails[$fn]);			
		if(empty($res['error']))
			$result['added'][] = substr($fn, $upload_dir_len);
		else
			$result['error'][] = $res['error'];

	}
	
	// chmod
	@chmod ($upload_dir, octdec(WPFB_PERM_DIR));
	for($i = 0; $i < count($file_paths); $i++)
	{
		if(file_exists($file_paths[$i]))
		{
			@chmod ($file_paths[$i], octdec(WPFB_PERM_FILE));
			if(!is_writable($file_paths[$i]) && !is_writable(dirname($file_paths[$i])))
				$result['warnings'][] = sprintf(__('File <b>%s</b> is not writable!', WPFB), substr($file_paths[$i], $upload_dir_len));
		}
	}	
	
	// sync categories
	$result['updated_categories'] = self::SyncCats();
	
	wpfb_call('Setup','ProtectUploadPath');
	
	return $result;
}

static function SyncCats()
{
	$updated_cats = array();
	
	// sync file count
	$cats = WPFB_Category::GetCats();
	foreach(array_keys($cats) as $i)
	{
		$cat = $cats[$i];
		$child_files = $cat->GetChildFiles(false);
		$num_files = (int)count($child_files);
		$num_files_total = (int)count($cat->GetChildFiles(true));
		if($num_files != $cat->cat_num_files || $num_files_total != $cat->cat_num_files_total)
		{
			$cat->cat_num_files = $num_files;
			$cat->cat_num_files_total = $num_files_total;
			$cat->DBSave();			
			$updated_cats[] = $cat;
		}
		
		// update category names
		if($child_files) {
			foreach($child_files as $file) {
				if($file->file_category_name != $cat->GetTitle()) {
					$file->file_category_name = $cat->GetTitle();
					if(!$file->locked)
						$file->DBSave();
				}
			}
		}
		
		@chmod ($cat->GetLocalPath(), octdec(WPFB_PERM_DIR));
	}
	
	return $updated_cats;
}

static function UpdateItemsPath() {
	wpfb_loadclass('File','Category');
	$cats = WPFB_Category::GetCats();
	$files = WPFB_File::GetFiles();	
	foreach(array_keys($cats) as $i) $cats[$i]->Lock(true);
	foreach(array_keys($files) as $i) $files[$i]->GetLocalPath(true);
	foreach(array_keys($cats) as $i) {
		$cats[$i]->Lock(false);
		$cats[$i]->DBSave();
	}
}

static function AddExistingFile($file_path, $thumb=null)
{
	$upload_dir = WPFB_Core::UploadDir();
	$rel_path = trim(substr($file_path, strlen($upload_dir)),'/');
	$rel_dir = dirname($rel_path);
	
	$last_cat_id = 0;
	
	if(!empty($rel_dir) && $rel_dir != '.')
	{
		$dirs = explode('/', $rel_dir);
		foreach($dirs as $dir) {
			if(empty($dir) || $dir == '.')
				continue;
			$cat = WPFB_Item::GetByName($dir, $last_cat_id);
			if($cat != null && $cat->is_category) {
				$last_cat_id = $cat->cat_id;
			} else {
				$result = self::InsertCategory(array('add_existing' => true, 'cat_parent' => $last_cat_id, 'cat_folder' => $dir));
				if(!empty($result['error']))
					return $result;
				elseif(empty($result['cat_id']))
					wp_die('Could not create category!');
				else
					$last_cat_id = intval($result['cat_id']);
			}
		}
	}
	
	return self::InsertFile(array('add_existing' => true, 'file_category' => $last_cat_id, 'file_path' => $file_path, 'file_thumbnail' => $thumb));
}

static function WPCacheRejectUri($add_uri, $remove_uri='')
{
	// changes the settings of wp cache
	
	global $cache_rejected_uri;
	
	$added = false;

	if(!isset($cache_rejected_uri))
		return false;

	// remove uri
	if(!empty($remove_uri))
	{
		$new_cache_rejected_uri = array();
			
		foreach($cache_rejected_uri as $i => $v)
		{
			if($v != $remove_uri)
				$new_cache_rejected_uri[$i] = $v;
		}
		
		$cache_rejected_uri = $new_cache_rejected_uri;
	}
	
	if(!in_array($add_uri, $cache_rejected_uri))
	{
		$cache_rejected_uri[] = $add_uri;
		$added = true;
	}
	
	return (self::WPCacheSaveRejectedUri() && $added);
}

static function WPCacheSaveRejectedUri()
{
	global $cache_rejected_uri, $wp_cache_config_file;
	
	if(!isset($cache_rejected_uri) || empty($wp_cache_config_file) || !function_exists('wp_cache_replace_line'))
		return false;	
	
	$text = var_export($cache_rejected_uri, true);
	$text = preg_replace('/[\s]+/', ' ', $text);
	wp_cache_replace_line('^ *\$cache_rejected_uri', "\$cache_rejected_uri = $text;", $wp_cache_config_file);

	return true;
}

static function MakeFormOptsList($opt_name, $selected = null, $add_empty_opt = false)
{
	$options = WPFB_Core::GetOpt($opt_name);	
	$options = explode("\n", $options);
	$def_sel = (is_null($selected) && !is_string($selected));
	$list = $add_empty_opt ? ('<option value=""' . ( (is_string($selected) && $selected == '') ? ' selected="selected"' : '') . '>-</option>') : '';
	$selected = explode('|', $selected);
	
	foreach($options as $opt)
	{
		$opt = trim($opt);
		$tmp = explode('|', $opt);
		$list .= '<option value="' . esc_attr(trim($tmp[1])) . '"' . ( (($def_sel && $opt{0} == '*') || (!$def_sel && in_array($tmp[1], $selected)) ) ? ' selected="selected"' : '' ) . '>' . esc_html(trim($tmp[0], '*')) . '</option>';
	}
	
	return $list;
}

static function AdminTableSortLink($order)
{
	$desc = (!empty($_GET['order']) && $order == $_GET['order'] && empty($_GET['desc']));
	$uri = add_query_arg(array('order' => $order, 'desc' => $desc ? '1' : '0'));
	return $uri;
}

private static function _isAllowedFileExt($ext)
{
	static $srv_script_exts = array('php', 'php3', 'php4', 'php5', 'phtml', 'cgi', 'pl', 'asp', 'py', 'aspx', 'jsp', 'jhtml', 'jhtm');	
	
	if(WPFB_Core::GetOpt('allow_srv_script_upload'))
		return true;
	
	$ext = strtolower($ext);	
	$p = strrpos($ext, '.');
	if($p !== false)
		$ext = substr($ext, $p + 1);
	
	return !in_array($ext, $srv_script_exts);
}

static function UninstallPlugin()
{
	wpfb_loadclass('Setup');
	WPFB_Setup::RemoveOptions();
	WPFB_Setup::DropDBTables();
	// TODO: remove user opt
}

static function PrintForm($name, $item=null, $vars=array())
{
	extract($vars);
	if(is_writable(WPFB_Core::UploadDir()))
		include(WPFB_PLUGIN_ROOT . 'lib/wpfb_form_' . $name . '.php');
}

static function Mkdir($dir)
{
	$parent = trim(dirname($dir), '.');
	if(trim($parent,'/\\') != '' && !is_dir($parent)) {
		$result = self::Mkdir($parent);
		if($result['error'])
			return $result;
	}
	return array('error' => !(@mkdir($dir, octdec(WPFB_PERM_DIR)) && @chmod($dir, octdec(WPFB_PERM_DIR))), 'dir' => $dir, 'parent' => $parent);
}

static function GetMaxUlSize() {
	$val = ini_get('upload_max_filesize');
    if (is_numeric($val))
        return $val;

	$val_len = strlen($val);
	$max_bytes = substr($val, 0, $val_len - 1);
	$unit = strtolower(substr($val, $val_len - 1));
	switch($unit) {
		case 'k':
			$max_bytes *= 1024;
			break;
		case 'm':
			$max_bytes *= 1048576;
			break;
		case 'g':
			$max_bytes *= 1073741824;
			break;
	}
	return $max_bytes;
}

static function ParseTpls() {
	wpfb_loadclass('TplLib');
	
	// parse default
	WPFB_Core::UpdateOption('template_file_parsed', WPFB_TplLib::Parse(WPFB_Core::GetOpt('template_file')));
	WPFB_Core::UpdateOption('template_cat_parsed', WPFB_TplLib::Parse(WPFB_Core::GetOpt('template_cat')));
	
	// parse widget
	$widget = WPFB_Core::GetOpt('widget');	
	$widget['filelist_template_parsed'] = WPFB_TplLib::Parse($widget['filelist_template']);	
	WPFB_Core::UpdateOption('widget', $widget);
		
	// parse custom
	update_option(WPFB_OPT_NAME.'_ptpls_file', WPFB_TplLib::Parse(WPFB_Core::GetFileTpls())); 
	update_option(WPFB_OPT_NAME.'_ptpls_cat', WPFB_TplLib::Parse(WPFB_Core::GetCatTpls())); 
}

static function FlushRewriteRules()
{
	global $wp_rewrite;
	if(!empty($wp_rewrite) && is_object($wp_rewrite))
		$wp_rewrite->flush_rules();
}

static function AddFileWidget() {
	wpfb_loadclass('Category');
	self::PrintForm('file', null, array('in_widget'=>true));
}

static function PrintPayPalButton() {
		$lang = 'en_US';
		$supported_langs = array('en_US', 'de_DE', 'fr_FR', 'es_ES', 'it_IT', 'ja_JP', 'pl_PL', 'nl_NL');
		
		/*
		 * fr_FR/FR
		 * https://www.paypalobjects.com/WEBSCR-640-20110401-1/en_US/FR/i/btn/btn_donateCC_LG.gif
		 * https://www.paypalobjects.com/WEBSCR-640-20110401-1/de_DE/DE/i/btn/btn_donateCC_LG.gif
		 * https://www.paypalobjects.com/WEBSCR-640-20110401-1/es_ES/ES/i/btn/btn_donateCC_LG.gif
		 * https://www.paypalobjects.com/WEBSCR-640-20110401-1/it_IT/i/btn/btn_donateCC_LG.gif
		 */
		
		// find out current language for the donate btn
		if(defined('WPLANG') && WPLANG && WPLANG != '') {
			if(in_array(WPLANG, $supported_langs))
				$lang = WPLANG;
			else {
				$l = strtolower(substr(WPLANG, 0, strpos(WPLANG, '_')));
				foreach($supported_langs as $sl) {
					$pos = strpos($sl,$l);
					if($pos !== false && $pos == 0) {
						$lang = $sl;
					}
				}
			}
		}
?>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick" />
<input type="hidden" name="hosted_button_id" value="AF6TBLTYLUMD2" />
<!-- <input type="image" src="https://www.paypalobjects.com/WEBSCR-640-20110401-1/<?php echo $lang ?>/i/btn/btn_donateCC_LG.gif" style="border:none;" name="submit" alt="PayPal - The safer, easier way to pay online!" /> -->
<input type="image" src="https://www.paypal.com/<?php echo $lang ?>/i/btn/btn_donateCC_LG.gif" style="border:none;" name="submit" alt="PayPal - The safer, easier way to pay online!" title="PayPal - The safer, easier way to pay online!" />
<!-- <img alt="" border="0" src="https://www.paypalobjects.com/WEBSCR-640-20110401-1/<?php echo $lang ?>/i/scr/pixel.gif" width="1" height="1" /> -->
<img alt="" border="0" src="https://www.paypal.com/<?php echo $lang ?>/i/scr/pixel.gif" width="1" height="1" />

</form>
<?php 
}

static function PrintFlattrHead() {
?>
<script type="text/javascript">
/* <![CDATA[ */
    (function() {
        var s = document.createElement('script'), t = document.getElementsByTagName('script')[0];
        s.type = 'text/javascript';
        s.async = true;
        s.src = 'http://api.flattr.com/js/0.6/load.js?mode=auto';
        t.parentNode.insertBefore(s, t);
    })();
/* ]]> */
</script>
<?php
}

static function PrintFlattrButton() {
?>
<p style="text-align: center;">
<a class="FlattrButton" style="display:none;" href="http://wordpress.org/extend/plugins/wp-filebase/"></a>
</p>
<noscript><p style="text-align: center;"><a href="http://flattr.com/thing/157167/WP-Filebase" target="_blank">
<img src="http://api.flattr.com/button/flattr-badge-large.png" alt="Flattr this" title="Flattr this" border="0" /></a></p></noscript>
<?php
}
}