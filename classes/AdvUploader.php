<?php class WPFB_AdvUploader {
	
	var $uploader;
	var $form_url;
	
	public function __construct($form_url)
	{
		$uploader_class = (version_compare(get_bloginfo('version'), '3.2.1') <= 0) ? 'SWFUpload' : 'PLUpload';
		wpfb_loadclass($uploader_class);
		$uploader_class = "WPFB_".$uploader_class;
		$this->uploader = new $uploader_class;
		$this->form_url = $form_url;
	}
	
	function PrintScripts()
	{
		$this->uploader->Scripts();
		
		// getUserSetting dummy function!
		?>
		<script type='text/javascript'>
		if(typeof(getUserSetting) != 'function') {
			function getUserSetting( name, def ) {
				return false;
			}
		}
		</script>
		<?php
	}
	
	function Display()
	{
		$this->uploader->Display($this->form_url);
	}
}