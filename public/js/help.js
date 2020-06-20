/*
This jquery script assigns a listener to a help button
to open a new window with the contents of a help document.

This button goes on the main document.  'helpdoc' points to the file
containing the help text in regular html.

	<button id='help-button' value='helpdoc'>Help</button>

The helpdoc is located at
	(public ) /help/helpdoc.html

It is an simple ordinary html page, without any php or menus.

Pressing the button on the main page calls this script,
which opens a new window on the right side of the screen and loads
it with the html document at
	/help/helpdoc.html

Theres some code to close the window when focus is removed, but that
turned out to be a bad idea, because you should be able to go back
and forth betweenthe help document and the page you're getting help on.

*/
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
		hwin = window.open('/help/'+hsrc+'.html','help',params);
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
