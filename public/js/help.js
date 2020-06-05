$(function(){
	$('#help-button').click(function() {
		var h = $(this).attr('value');
// 		alert ("h:" + h);
// 		return false;
		helpwin(h);

        });
});


function helpwin(hsrc) {

	// open or close a help window

	var myh = '500';
	var params;
	// var vh = Math.max(document.documentElement.clientHeight || 0, window.innerHeight || 0);
// 	var vw = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
	myh = Math.min(screen.height * 0.8,800);
	myw = Math.min(screen.width * 0.8,500);
	// positing right edge 12 px in from screen edge.
	var ledge = screen.width - myw - 12;
	params = "top=100,left="+ledge+",height="+ myh + ",width=" + myw;


	if (typeof hwin == "undefined" || hwin == null) {
		hwin = window.open('/help-template.html','help',params);
		hwin.focus();

// 	 hwin.onload = function() {
// 		hwin.document.getElementById("helpbox").innerHTML = 'new content';
// 		hwin.body.onBlur = hwin.close();
// 	};


	} else {

		hwin.close();
		hwin = null;

	}
	return false;
}
