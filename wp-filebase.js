
function wpfilebase_filedetails(id) {
	var elDetails = document.getElementById('wpfilebase-filedetails' + id);
	
	var visible = (elDetails.style.display != 'none');
	
	if(visible) {
		elDetails.style.display = 'none';
	} else {
		elDetails.style.display = 'block';
	}
	
	return false;
}