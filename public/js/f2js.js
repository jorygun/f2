function showDiv(divid) {
    // hide or reveal a division
    var div = document.getElementById(divid);
    if (div.style.display == 'block'){
        div.style.display = 'none';
    }
    else {
        div.style.display = 'block';
    }
}
function check_the_box (obj,state){
	// checks/unchecks a checkbox obj (true/false)
	document.getElementById(obj).checked = state;
}
	
function check_form(params) {
    var err='';
  for (i=0; i<params.length; i++) {
    //alert ("testing " + params[i]);
    var txt = document.getElementById(params[i]).value;
    if (txt.length == 0){
        var newerr =  'Field '+params[i]+' must not be empty. ' + "\n";
        //alert (newerr);
        err += newerr;
    }
    }

    if  (err != ''){
         alert(err);
         return false;
    }
  return true;
}

function gotoNewId(url,idvar){
    /* replaces the 'newid' text in url with the value of idvar
    if it is a number, or the value of the object whose id is named in idvar
    
    */
    var newid=0;
    if (Number.isInteger(idvar) ){ newid=idvar;}
    else {
    	newid=document.getElementById(idvar).value;
    }
    var newurl = url.replace('newid',newid);
    window.location=newurl;

}

function playsound(soundid){
    aid = document.getElementById(soundid);
    aid.play();
    // separately define <audio id='xxx' src='/audio/file.mp3'>
}

function clearForm(oForm) {
    // call from name of form
  var elements = oForm.elements;
  oForm.reset();

  for(i=0; i<elements.length; i++) {
	field_type = elements[i].type.toLowerCase();
	switch(field_type) {
		case "text":
		case "password":
		case "textarea":
	    //case "hidden":
			elements[i].value = "";
			break;
		case "radio":
		case "checkbox":
  			if (elements[i].checked) {
   				elements[i].checked = false;
			}
			break;
		case "select-one":
		case "select-multi":
            		elements[i].selectedIndex = 0; //for first in list. otherwise -1
			break;
		default:
			break;
	  }
    }
}
function new_edit_win(url,winname){
    var wwidth=650
    var screenwidth = window.screen.availWidth;
    var screenheight = window.screen.availHeight;
    var leftedge = screenwidth - wwidth;
    var winparams = 'height=' + screenheight + ',width=' + wwidth + ',left=' + leftedge + ',scrollbars';
   // alert ("wid  " + screenwidth + 'height ' + screenheight + 'params ' + winparams);
    ewin=window.open( url,winname, winparams);
    return false;
}

function validateEmail(email) {
    var re = /^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i;
    return re.test(email);
}

function showWarn(content){
    var warn = window.open('','warn')
    warn.writeln ('<html><body onLoad="self.focus()">'
    +'test message'
    +'</body></html');
    warn.document.close()
    }
    
    
function setCaretPosition(elemId, caretPos) {
// use to determine how many characcters have been typed in a text area
// to prevent submitting sparse data
    var elem = document.getElementById(elemId);

    if(elem != null) {
        if(elem.createTextRange) {
            var range = elem.createTextRange();
            range.move('character', caretPos);
            range.select();
        }
        else {
            if(elem.selectionStart) {
                elem.focus();
                elem.setSelectionRange(caretPos, caretPos);
            }
            else
                elem.focus();
        }
    }
}
// selects all  used in asset screen??
function check_select_all(obj) {
	var sbox = document.getElementById('all_active');
	if (obj.value == ''){
		sbox.checked = true;
	}
	else {sbox.checked = false;}
}
