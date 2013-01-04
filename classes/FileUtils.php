<?php class WPFB_FileUtils {

static function GetFileSize($file)
{
	$size = filesize($file);
	
	return $size;
}

static function CreateThumbnail($src_img, $max_size)
{
	if(!function_exists('image_make_intermediate_size')) {
		require_once(ABSPATH . 'wp-includes/media.php');
		if(!function_exists('image_make_intermediate_size'))
		{
			//if($tmp_src) @unlink($src_image);
			wp_die('Function image_make_intermediate_size does not exist!');
			return false;
		}
	}
	
	$thumb = @image_make_intermediate_size($src_img, $max_size, $max_size);
	return dirname($src_img).'/'.$thumb['file'];
}

static function IsValidImage($img, &$img_size = null) {
	$s = @getimagesize($img);
	if($s !== false) $img_size = $s;
	return $s !== false;
}

static function FileHasImageExt($name) {	
	$name = strtolower(substr($name, strrpos($name, '.') + 1));
	return ($name == 'png' || $name == 'gif' || $name == 'jpg' || $name == 'jpeg' || $name == 'bmp');
}
	
}