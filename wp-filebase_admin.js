function openPostBrowser(siteurl)
{
	var postId = document.getElementById('file_post_id').value;
	var browserWindow = window.open(siteurl + "/wp-content/plugins/wp-filebase/wp-filebase_post_browser.php?post=" + postId + "&el_id=file_post_id", "PostBrowser", "width=300,height=400,menubar=no,location=no,resizable=no,status=no,toolbar=no");
	browserWindow.focus();
}

function wpfilebaseAddTplField(select, input)
{
	if(select.selectedIndex == 0 || select.options[select.selectedIndex].value == '')
		return;
		
	var tag = '%' + select.options[select.selectedIndex].value + '%';
	var inputEl = select.form.elements[input];
	
	if (document.selection)
	{
		inputEl.focus();
		sel = document.selection.createRange();
		sel.text = tag;
	}
	else if (inputEl.type == 'textarea' && typeof(inputEl.selectionStart) != 'undefined' && (inputEl.selectionStart || inputEl.selectionStart == '0'))
	{
		var startPos = inputEl.selectionStart;
		var endPos = inputEl.selectionEnd;
		inputEl.value = inputEl.value.substring(0, startPos) + tag + inputEl.value.substring(endPos, inputEl.value.length);
	}
	else
	{
		inputEl.value += tag;
	}
	
	select.selectedIndex = 0;
}