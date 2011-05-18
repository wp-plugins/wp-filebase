<?php
class WPFB_Core {
static $load_js = false;

static function InitClass()
{
	global $wp_query, $wpfb_post_url_cache;
	$wpfb_post_url_cache = array();

	WPFB_Core::LoadLang();
	
	//add_action('wp_head', array(__CLASS__, 'Header'));
	add_action('wp_footer', array(__CLASS__, 'Footer'));
	add_action('parse_query', array(__CLASS__, 'ParseQuery')); // search
	add_action(WPFB.'_cron', array(__CLASS__, 'Cron'));
	
	// for attachments and file browser
	add_filter('the_content',	array(__CLASS__, 'ContentFilter'), 10); // must be lower than 11 (before do_shortcode) and after wpautop (>9)
	add_shortcode('wpfilebase', array(__CLASS__, 'ShortCode'));
	

	// some misc filters & actions
	//add_filter('query_vars', array(__CLASS__, 'QueryVarsFilter'));	
	add_filter('ext2type', array(__CLASS__, 'Ext2TypeFilter'));
	add_action('generate_rewrite_rules', array(__CLASS__, 'GenRewriteRules'));
	
	// register treeview stuff
	//wp_register_script('jquery-cookie', WPFB_PLUGIN_URI.'extras/jquery/jquery.cookie.js', array('jquery'));
	wp_register_script('jquery-treeview', WPFB_PLUGIN_URI.'extras/jquery/treeview/jquery.treeview.js', array('jquery'));
	wp_register_script('jquery-treeview-edit', WPFB_PLUGIN_URI.'extras/jquery/treeview/jquery.treeview.edit.js', array('jquery-treeview'));
	wp_register_script('jquery-treeview-async', WPFB_PLUGIN_URI.'extras/jquery/treeview/jquery.treeview.async.js', array('jquery-treeview-edit'));
	wp_register_style('jquery-treeview', WPFB_PLUGIN_URI.'extras/jquery/treeview/jquery.treeview.css', array(), WPFB_VERSION);
		
	
	wp_register_script(WPFB, WPFB_PLUGIN_URI.'js/common.js', array('jquery'), WPFB_VERSION); // cond loading (see Footer)
	wp_enqueue_style(WPFB, WPFB_PLUGIN_URI.'wp-filebase_css.php', array(), WPFB_VERSION, 'all');
	
	
	if((is_admin() && !empty($_GET['page']) && strpos($_GET['page'], 'wpfilebase_') !== false) || defined('WPFB_EDITOR_PLUGIN'))
	{
		wpfb_loadclass('Admin');
	}
	
	// live admin
	if(current_user_can('upload_files') && !is_admin()) {
		wp_enqueue_script(WPFB.'-live-admin', WPFB_PLUGIN_URI.'js/live-admin.js', array('jquery'), WPFB_VERSION);
		if(self::GetOpt('admin_bar'))
			add_action( 'admin_bar_menu', array(__CLASS__, 'AdminBar'), 80 );
		if(self::GetOpt('file_context_menu')) {
			wp_enqueue_script('jquery-contextmenu', WPFB_PLUGIN_URI.'extras/jquery/contextmenu/jquery.contextmenu.js', array('jquery'));
			wp_enqueue_style('jquery-contextmenu', WPFB_PLUGIN_URI.'extras/jquery/contextmenu/jquery.contextmenu.css', array(), WPFB_VERSION);
		}
	}
	
	// widgets
	wp_register_sidebar_widget(WPFB_PLUGIN_NAME, WPFB_PLUGIN_NAME .' '. __('File list', WPFB), array(__CLASS__, 'FileWidget'), array('description' => __('Lists the latest or most popular files', WPFB)));
	wp_register_sidebar_widget(WPFB_PLUGIN_NAME.'_cats', WPFB_PLUGIN_NAME.' ' . __('Category list', WPFB), array(__CLASS__, 'CatWidget'), array('description' => __('Simple listing of file categories', WPFB)));
			
	// for admin
	if (current_user_can('edit_posts') || current_user_can('edit_pages'))
		self::MceAddBtns();
		
	add_action('wp_dashboard_setup', array(__CLASS__, 'AdminDashboardSetup'));	

	self::DownloadRedirect();
}

static function ParseQuery(&$query)
{
	// conditional loading of the search hooks
	global $wp_query;
	if (!empty($wp_query->query_vars['s']))
		wpfb_loadclass('Search');
}

/* // this was used to load the file browser js, now done directly in the post
static function Header() {
	global $wp_query;
	
	/*
	// conditionally loading the treeview		
	if(!empty($wp_query->post->ID) && $wp_query->post->ID > 0 && $wp_query->post->ID == WPFB_Core::GetOpt('file_browser_post_id') && !is_feed() && (is_single() || is_page())) {
		wpfb_loadclass('Output');
		WPFB_Output::InitFileTreeView('wpfilebase-file-browser');
	}
	*//*
}*/

static function AdminInit() { 
	wpfb_loadclass('AdminLite');
	if(!empty($_GET['page']) && strpos($_GET['page'], 'wpfilebase_') !== false)
		wpfb_loadclass('Admin');
}
static function AdminMenu() { wpfb_call('AdminLite', 'SetupMenu'); }

static function LoadLang() {
	static $loaded = false;
	if(!$loaded) {
		$lang_dir = basename(WPFB_PLUGIN_ROOT).'/languages';
		load_plugin_textdomain(WPFB, 'wp-content/plugins/'.$lang_dir, $lang_dir);
		$loaded = true;
	}
}

static function GetOpt($name = null) {
	$options = get_option(WPFB_OPT_NAME);
	if(empty($name)) return $options;
	elseif(isset($options[$name])) return $options[$name];
	return null;
}

static function FileWidget($args) { return wpfb_call('Widget', 'FileList', $args); }
static function CatWidget($args) { return wpfb_call('Widget', 'CatList', $args); }

static function DownloadRedirect()
{
	global $wpdb;
	$file = null;
	
	if(!empty($_GET['wpfb_dl'])) {
		wpfb_loadclass('File');
		$file = WPFB_File::GetFile((int)$_GET['wpfb_dl']);
		@ob_end_clean(); // FIX: clean the OB so any output before the actual download is truncated (OB is started in wp-filebase.php)
	} else {
		$base = WPFB_Core::GetOpt('download_base');
		if(!$base || is_admin()) return;
		$dl_url_path = parse_url(home_url($base.'/'), PHP_URL_PATH);
		$pos = strpos($_SERVER['REQUEST_URI'], $dl_url_path);
		if($pos !== false && $pos == 0) {
			$filepath = trim(urldecode(substr($_SERVER['REQUEST_URI'], strlen($dl_url_path))), '/');
			if(!empty($filepath)) {
				wpfb_loadclass('File','Category');
				$file = WPFB_File::GetByPath($filepath);
			}
		}
	}
	
	if(!empty($file) && is_object($file)) {
		$file->Download();		
		exit;
	} else {
		// no download, a normal request: set site visited coockie to disable referer check
		if(empty($_COOKIE[WPFB_OPT_NAME])) {
			@setcookie(WPFB_OPT_NAME, '1');
			$_COOKIE[WPFB_OPT_NAME] = '1';
		}
	}
}

static function Ext2TypeFilter($arr) {
	$arr['interactive'][] = 'exe';
	$arr['interactive'][] = 'msi';
	return $arr;
}

/*
// conditionally loading
add_filter('the_posts', 'wpfilebase_posts_filter');
function wpfilebase_posts_filter($posts) {
	global $id, $wpfb_loaded_output;
	print_r($posts);
	if(!empty($wpfb_loaded_output) || empty($posts))
		return $posts;
	$fb_id = WPFB_Core::GetOpt('file_browser_post_id');
	if($id > 0 && $id == $fb_id) {
		wpfilebase_load_output_scripts();
	} else {		
		foreach($posts as $post) {
		if(strpos($post->post_content, '[filebase') !== false || $post->id == $fb_id) {
				wpfilebase_load_output_scripts();
				break;
			}
		}
	}
	return $posts;
} */

function ContentFilter($content)
{
	global $id;
	
	if(!WPFB_Core::GetOpt('parse_tags_rss') && is_feed())
		return $content;	
		
	// all tags start with '[filebase'
	/*
	if(strpos($content, '[filebase') !== false)
	{
		wpfb_loadclass('Output');
		WPFB_Output::wpfilebase_parse_content_tags($content);
	}
	*/
	
	if(!empty($id) && $id > 0 && (is_single() || is_page()))
	{
		if($id == WPFB_Core::GetOpt('file_browser_post_id'))
		{
			wpfb_loadclass('Output', 'File', 'Category');
			WPFB_Output::FileBrowser($content, 0, empty($_GET['wpfb_cat']) ? 0 : intval($_GET['wpfb_cat']));
		}
	
		if(WPFB_Core::GetOpt('auto_attach_files'))
		{
			wpfb_loadclass('Output');
			$content .= WPFB_Output::PostAttachments(true);
		}
	}

    return $content;
}

static function ShortCode($atts) {	
	return wpfb_call('Output', 'ProcessShortCode', shortcode_atts(array(
		'tag' => 'list', // file, fileurl, attachments
		'id' => -1,
		'tpl' => null,
		'sort' => null,
		'showcats' => false,
		'num' => 0,
		'pagenav' => 1,
	), $atts));
}


static function Footer() {
	// TODO: use enque and no cond loading ?
	if(!empty(self::$load_js)) {
		self::PrintJS();
	}
}


static function GenRewriteRules() {
    global $wp_rewrite;
	$fb_pid = intval(WPFB_Core::GetOpt('file_browser_post_id'));
	if($fb_pid > 0) {
		$is_page = (get_post_type($fb_pid) == 'page');
		$redirect = 'index.php?'.($is_page?'page_id':'p')."=$fb_pid";
		$base = trim(substr(get_permalink($fb_pid), strlen(home_url())), '/');
		$pattern = "$base/(.+)$";
		$wp_rewrite->rules = array($pattern => $redirect) + $wp_rewrite->rules;
	}
}

/*// removed, no need of adding the query vars
static function QueryVarsFilter($qvars){
	$qvars[] = 'wpfb_cat_path';
	$qvars[] = 'wpfb_cat';
	$qvars[] = 'wpfb_dl';
    return $qvars;
} */

static function MceAddBtns() {
	add_filter('mce_external_plugins', array('WPFB_Core', 'McePlugins'));
	add_filter('mce_buttons', array('WPFB_Core', 'MceButtons'));
}

static function McePlugins($plugins) { wpfb_loadclass('AdminLite'); return WPFB_AdminLite::McePlugins($plugins); }
static function MceButtons($buttons) { wpfb_loadclass('AdminLite'); return WPFB_AdminLite::MceButtons($buttons); }

static function UpdateOption($name, $value = null) {
	$options = get_option(WPFB_OPT_NAME);
	$options[$name] = $value;
	update_option(WPFB_OPT_NAME, $options);
}

static function UploadDir() {
	$upload_path = trim(WPFB_Core::GetOpt('upload_path'));
	if (empty($upload_path))
		$upload_path = WP_CONTENT_DIR . '/uploads/filebase';
	return path_join(ABSPATH, $upload_path);
}

static function GetPermalinkBase() {
	return trailingslashit(get_option('home')).trailingslashit(WPFB_Core::GetOpt('download_base'));	
}

static function GetPostUrl($id) {
	global $wpfb_post_url_cache;
	$id = intval($id);
	if(isset($wpfb_post_url_cache[$id]))
		return $wpfb_post_url_cache[$id];
	return ($wpfb_post_url_cache[$id] = get_permalink($id));
}

static function GetTraffic()
{
	$traffic = WPFB_Core::GetOpt('traffic_stats');
	$time = intval($traffic['time']);
	$year = intval(date('Y', $time));
	$month = intval(date('m', $time));
	$day = intval(date('z', $time));
	
	$same_year = ($year == intval(date('Y')));
	if(!$same_year || $month != intval(date('m')))
		$traffic['month'] = 0;
	if(!$same_year || $day != intval(date('z')))
		$traffic['today'] = 0;
		
	return $traffic;
}

static function UserLevel2Role($level)
{
	if($level >= 8) return 'administrator';
	if($level >= 5)	return 'editor';
	if($level >= 2)	return 'author';
	if($level >= 1)	return 'contributor';
	if($level >= 0)	return 'subscriber';
	return null;
}

static function UserRole2Level($role)
{
	switch($role) {
	case 'administrator': return 8;
	case 'editor': return 5;
	case 'author': return 2;
	case 'contributor': return 1;
	case 'subscriber': return 0;
	default: return -1;
	}
}

static function GetFileListSortSql($sort=null)
{
	global $wpdb;
	static $fields = array(
		'file_id','file_name','file_size','file_date','file_path','file_display_name','file_hits',
		'file_description','file_version','file_author','file_license',
		'file_required_level','file_category','file_category_name','file_post_id',
		'file_added_by','file_hits','file_last_dl_time');
	
	if(!empty($_REQUEST['wpfb_file_sort']))
		$sort = $_REQUEST['wpfb_file_sort'];
	elseif(empty($sort))
		$sort = WPFB_Core::GetOpt('filelist_sorting');
	
	$sort = str_replace(array('&gt;','&lt;'), array('>','<'), $sort);
	
	$desc = WPFB_Core::GetOpt('filelist_sorting_dir');
	if($sort{0} == '<') {
		$desc = false;
		$sort = substr($sort,1);
	} elseif($sort{0} == '>') {
		$desc = true;
		$sort = substr($sort,1);
	}
	
	if(!in_array($sort, $fields)) $sort = WPFB_Core::GetOpt('filelist_sorting');
	
	$sort = $wpdb->escape($sort);
	$sortdir = $desc ? 'DESC' : 'ASC';	
	return "ORDER BY `$sort` $sortdir";
}

static function PrintJS() {
	wp_print_scripts(WPFB);
	
	$context_menu = current_user_can('upload_files') && self::GetOpt('file_context_menu');
	
	$conf = array(
		'ql'=>1, // querylinks with jQuery
		'hl'=> (int)self::GetOpt('hide_links'), // hide links
		'pl'=>(self::GetOpt('disable_permalinks') ? 0 : (int)!!get_option('permalink_structure')), // permlinks
		'hu'=> trailingslashit(home_url()),// home url
		'db'=> self::GetOpt('download_base'),// urlbase
		'fb'=> self::GetPostUrl(self::GetOpt('file_browser_post_id')),
		'cm'=>(int)$context_menu,
		'ajurl'=>WPFB_PLUGIN_URI.'wpfb-ajax.php'
	);
	
	if($context_menu) {
		$conf['fileEditUrl'] = admin_url("admin.php?page=wpfilebase_files&action=editfile&file_id=");
		
		wp_print_scripts('jquery-contextmenu');
		wp_print_styles	('jquery-contextmenu');
	}
	
	$js = WPFB_Core::GetOpt('dlclick_js');
	if(empty($js)) $js = '';
	
	echo "<script type=\"text/javascript\">\n//<![CDATA[\n",'wpfbConf=',json_encode($conf),';';
	
	//if(!empty($wpfb_file_paths)) echo 'wpfbFPaths=',json_encode($wpfb_file_paths),';';
	//else echo 'wpfbFPaths={};';
	
	//if(!empty($wpfb_cat_urls)) echo 'wpfbCPaths=',json_encode($wpfb_cat_urls),';',"\n";
	//else echo 'wpfbCPaths={};',"\n";
	
	if($context_menu) {
		echo
"wpfbContextMenu=[
	{'",__('Edit'),"':{onclick:wpfb_menuEdit,icon:'".WPFB_PLUGIN_URI."extras/jquery/contextmenu/page_white_edit.png'}, },
	jQuery.contextMenu.separator,
	{'",__('Delete'),"':{onclick:wpfb_menuDel,icon:'".WPFB_PLUGIN_URI."extras/jquery/contextmenu/delete_icon.gif'}}
];\n";
		
	}
	
	echo "function wpfb_ondl(file_id,file_url,file_path){ {$js} }";	
	echo "\n//]]>\n</script>\n";
}

// gets custom template list or single if tag specified
static function GetFileTpls($tag=null) {
	if($tag == 'default') return self::GetOpt('template_file');
	$tpls = get_option(WPFB_OPT_NAME.'_tpls_file');
	return empty($tag) ? $tpls : $tpls[$tag];
}

static function GetCatTpls($tag=null) {
	if($tag == 'default') return self::GetOpt('template_cat');
	$tpls = get_option(WPFB_OPT_NAME.'_tpls_cat');
	return empty($tag) ? $tpls : $tpls[$tag];
}

static function GetTpls($type, $tag=null) { return ($type == 'cat') ? self::GetCatTpls($tag) : self::GetFileTpls($tag);}

static function SetFileTpls($tpls) { return is_array($tpls) ? update_option(WPFB_OPT_NAME.'_tpls_file', $tpls) : false; }
static function SetCatTpls($tpls) { return is_array($tpls) ? update_option(WPFB_OPT_NAME.'_tpls_cat', $tpls) : false; }

static function GetParsedTpl($type, $tag) {
	if(empty($tag)) return null;
	if($tag == 'default') return self::GetOpt("template_{$type}_parsed");
	$on = WPFB_OPT_NAME.'_ptpls_'.$type;
	$ptpls = get_option($on);
	if(empty($ptpls)) {
		$ptpls = wpfb_call('TplLib','Parse',self::GetTpls($type));
		update_option($on, $ptpls);
	}
	return $ptpls[$tag];
}

static function AdminDashboardSetup() {
	if(current_user_can('upload_files')) {
		wpfb_loadclass('Admin');
		wp_add_dashboard_widget('wpfb-add-file-widget', WPFB_PLUGIN_NAME.': '.__('Add File', WPFB), array('WPFB_Admin', 'AddFileWidget'));
	}	
}

static function AdminBar() {
	global $wp_admin_bar;
	
	$wp_admin_bar->add_menu(array('id' => WPFB, 'title' => WPFB_PLUGIN_NAME, 'href' => admin_url('admin.php?page=wpfilebase_manage')));
	
	$wp_admin_bar->add_menu(array('parent' => WPFB, 'id' => WPFB.'-add-file', 'title' => __('Add File', WPFB), 'href' => admin_url('admin.php?page=wpfilebase_files#addfile')));
	
	$current_object = get_queried_object();
	if ( !empty($current_object) && !empty($current_object->post_type)) {
		$link = WPFB_PLUGIN_URI.'editor_plugin.php?manage_attachments=1&amp;post_id='.$current_object->ID;
		$wp_admin_bar->add_menu( array( 'parent' => WPFB, 'id' => WPFB.'-attachments', 'title' => __('Manage attachments', WPFB), 'href' => $link,
		'meta' => array('onclick' => 'window.open("'.$link.'", "wpfb-manage-attachments", "width=680,height=400,menubar=no,location=no,resizable=no,status=no,toolbar=no,scrollbars=yes");return false;')));
	}
	
	$wp_admin_bar->add_menu(array('parent' => WPFB, 'id' => WPFB.'-add-file', 'title' => __('Sync Filebase', WPFB), 'href' => admin_url('admin.php?page=wpfilebase_manage&action=sync')));
	
	$wp_admin_bar->add_menu(array('parent' => WPFB, 'id' => WPFB.'-toggle-context-menu', 'title' => __(self::GetOpt('file_context_menu')?'Disable file context menu':'Enable file context menu', WPFB), 'href' => '',
	'meta' => array('onclick' => 'return wpfb_toggleContextMenu();')));
	
}

static function Cron() {
	if(self::GetOpt('cron_sync'))
		wpfb_call('Admin', 'Sync');
}
}