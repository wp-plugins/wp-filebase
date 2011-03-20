function wpfb_menuEdit(menuItem,menu) {
	var fi = wpfb_getFileInfo(menu.file_url);
	if(fi != null)
		window.location = wpfbConf.fileEditUrl + fi.id + '&redirect_to='+escape(window.location.href);
}

function wpfb_menuDel(menuItem,menu) {
	var fi = wpfb_getFileInfo(menu.file_url);
	if(fi != null && confirm('Do you really want to delete this file?'))
	{
		jQuery('body').css('cursor', 'wait');
		
		jQuery.ajax({
			type: 'POST',
			url: wpfbConf.ajurl,
			data: {action:'delete',file_id:fi.id},
			async: false,
			success: (function(data){
				if(data != '-1') {
					var el = jQuery(menu.target);
					el.css("textDecoration", "line-through");
					el.unbind('click').click((function(){return false;}));
					el.fadeTo('slow', 0.3);
				}
			})
		});
		
		jQuery('body').css('cursor', 'default');
	}
}

function wpfb_addContextMenu(el, url) {
	if(typeof(wpfbContextMenu) != 'undefined')
		el.contextMenu(wpfbContextMenu,{theme:'osx',showTransition:'fadeIn',hideTransition:'fadeOut',file_url:url});
}

function wpfb_manageAttachments(url,postId)
{
	var browserWindow = window.open("../wp-content/plugins/wp-filebase/wpfb-postbrowser.php?post=" + postId + "&inp_id=" + inputId + "&tit_id=" + titleId, "PostBrowser", "width=300,height=400,menubar=no,location=no,resizable=no,status=no,toolbar=no");
	browserWindow.focus();
}

function wpfb_toggleContextMenu() {
	wpfbConf.cm = !wpfbConf.cm;
	jQuery.ajax({url: wpfbConf.ajurl, data:'action=toggle-context-menu', async: false});
	return true;
}

function wpfb_print(obj) {
	var str = ' '+obj+':';
	for(var k in obj) str += ' ['+k+'] = '+obj[k]+'\n';
	alert(str);
}