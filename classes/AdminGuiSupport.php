<?php
class WPFB_AdminGuiSupport {
static function Display()
{
	global $wpdb, $user_ID;
	
	wpfb_loadclass('Admin', 'Output');
	
	$_POST = stripslashes_deep($_POST);
	$_GET = stripslashes_deep($_GET);	
	$action = (!empty($_POST['action']) ? $_POST['action'] : (!empty($_GET['action']) ? $_GET['action'] : ''));
	$clean_uri = remove_query_arg(array('message', 'action', 'file_id', 'cat_id', 'deltpl', 'hash_sync' /* , 's'*/)); // keep search keyword
	
	?><div class="wrap"><?php
	
	switch($action)
	{
	default:		
		$lang = 'en_US';
		$supported_langs = array('en_US', 'de_DE', 'fr_FR', 'es_ES', 'it_IT', 'ja_JP', 'pl_PL', 'nl_NL');
		
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
<div id="wpfilebase-donate">
<p><?php _e('If you like WP-Filebase I would appreciate a small donation to support my work. You can additionally add an idea to make WP-Filebase even better. Just click the button below. Thank you!', WPFB) ?></p>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="AF6TBLTYLUMD2">
<input type="image" src="https://www.paypal.com/<?php echo $lang ?>/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypal.com/<?php echo $lang ?>/i/scr/pixel.gif" width="1" height="1">
</form>	
</div>
<?php  
		break;
	}	
	?>
</div> <!-- wrap -->
<?php
}
}
?>