<?php class WPFB_AdvUploader {
	
	var $uploader;
	var $form_url;
	
	static function GetAjaxAuthData($json=false)
	{
		$dat = array(
			"auth_cookie" => (is_ssl() ? @$_COOKIE[SECURE_AUTH_COOKIE] : @$_COOKIE[AUTH_COOKIE]),
			"logged_in_cookie" => @$_COOKIE[LOGGED_IN_COOKIE],
			"_wpnonce" => wp_create_nonce(WPFB.'-async-upload'),
			"frontend_upload" => !is_admin()
		);
		return $json ? trim(json_encode($dat),'{}') : $dat;
	}
	
	public function __construct($form_url)
	{
		$uploader_class = (version_compare(get_bloginfo('version'), '3.2.1') <= 0) ? 'SWFUpload' : 'PLUpload';
		wpfb_loadclass($uploader_class);
		$uploader_class = "WPFB_".$uploader_class;
		$this->uploader = new $uploader_class;
		$this->form_url = $form_url;
	}
	
	function PrintScripts($prefix='', $auto_submit=false)
	{
		$this->uploader->Scripts($prefix);
		?>
		
<script type="text/javascript">
//<![CDATA[

function fileQueued(fileObj) {
	jQuery('#file-upload-progress').show().html('<div class="progress"><div class="percent">0%</div><div class="bar" style="width: 30px"></div></div><div class="filename original"> ' + fileObj.name + '</div>');

	jQuery('.progress', '#file-upload-progress').show();
	jQuery('.filename', '#file-upload-progress').show();

	jQuery("#media-upload-error").empty();
	jQuery('.upload-flash-bypass').hide();
	
	jQuery('#file-submit').prop('disabled', true);
	jQuery('#cancel-upload').show().prop('disabled', false);

	 // delete already uploaded temp file	
	if(jQuery('#file_flash_upload').val() != '0') {
		jQuery.ajax({type: 'POST', async: true, url:"<?php echo esc_attr( WPFB_PLUGIN_URI.'wpfb-async-upload.php' ); ?>",
		data: {<?php echo WPFB_AdvUploader::GetAjaxAuthData(true) ?> , "delupload":jQuery('#file_flash_upload').val()},
		success: (function(data){})
		});
		jQuery('#file_flash_upload').val(0);
	}
}
           
function wpFileError(fileObj, message) {
	jQuery('#media-upload-error').show().html(message);
	jQuery('.upload-flash-bypass').show();
	jQuery("#file-upload-progress").hide().empty();
	jQuery('#cancel-upload').hide().prop('disabled', true);
}


function uploadError(fileObj, errorCode, message, uploader) {
	wpFileError(fileObj, "Error "+errorCode+": "+message);
}

function uploadSuccess(fileObj, serverData) {
	// if async-upload returned an error message, place it in the media item div and return
	if ( serverData.match('media-upload-error') ) {
		wpFileError(fileObj, serverData);
		return;
	}
	jQuery('#file_flash_upload').val(serverData);
	jQuery('#file-submit').prop('disabled', false);

	<?php if($auto_submit) { ?>
	jQuery('#file_flash_upload').closest("form").submit();
	<?php } ?>
}

function uploadComplete(fileObj) {
	jQuery('#cancel-upload').hide().prop('disabled', true);
}
	

if(typeof(getUserSetting) != 'function') {
	function getUserSetting( name, def ) { // getUserSetting dummy function!
		return false;
	}
}
//]]>
</script>
		<?php
	}
	
	function Display()
	{
		$this->uploader->Display($this->form_url);
	}
}