function cbClick(checked) {
    document.querySelectorAll('.lav').forEach(function(e) { 
	let d = 'none';
	if (checked) d = 'table-cell';
	e.style.display = d;
    });
}