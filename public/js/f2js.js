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

/*
 * Date Format 1.2.3
 * (c) 2007-2009 Steven Levithan <stevenlevithan.com>
 * MIT license
 *
 * Includes enhancements by Scott Trenda <scott.trenda.net>
 * and Kris Kowal <cixar.com/~kris.kowal/>
 *
 * Accepts a date, a mask, or a date and a mask.
 * Returns a formatted version of the given date.
 * The date defaults to the current date/time.
 * The mask defaults to dateFormat.masks.default.
 */

var dateFormat = function () {
  var	token = /d{1,4}|m{1,4}|yy(?:yy)?|([HhMsTt])\1?|[LloSZ]|"[^"]*"|'[^']*'/g,
		timezone = /\b(?:[PMCEA][SDP]T|(?:Pacific|Mountain|Central|Eastern|Atlantic) (?:Standard|Daylight|Prevailing) Time|(?:GMT|UTC)(?:[-+]\d{4})?)\b/g,
		timezoneClip = /[^-+\dA-Z]/g,
		pad = function (val, len) {
			val = String(val);
			len = len || 2;
			while (val.length < len) val = "0" + val;
			return val;
		};

	// Regexes and supporting functions are cached through closure
	return function (date, mask, utc) {
		var dF = dateFormat;

		// You can't provide utc if you skip other args (use the "UTC:" mask prefix)
		if (arguments.length == 1 && Object.prototype.toString.call(date) == "[object String]" && !/\d/.test(date)) {
			mask = date;
			date = undefined;
		}

		// Passing date through Date applies Date.parse, if necessary
		date = date ? new Date(date) : new Date;
		if (isNaN(date)) throw SyntaxError("invalid date");

		mask = String(dF.masks[mask] || mask || dF.masks["default"]);

		// Allow setting the utc argument via the mask
		if (mask.slice(0, 4) == "UTC:") {
			mask = mask.slice(4);
			utc = true;
		}

		var	_ = utc ? "getUTC" : "get",
			d = date[_ + "Date"](),
			D = date[_ + "Day"](),
			m = date[_ + "Month"](),
			y = date[_ + "FullYear"](),
			H = date[_ + "Hours"](),
			M = date[_ + "Minutes"](),
			s = date[_ + "Seconds"](),
			L = date[_ + "Milliseconds"](),
			o = utc ? 0 : date.getTimezoneOffset(),
			flags = {
				d:    d,
				dd:   pad(d),
				ddd:  dF.i18n.dayNames[D],
				dddd: dF.i18n.dayNames[D + 7],
				m:    m + 1,
				mm:   pad(m + 1),
				mmm:  dF.i18n.monthNames[m],
				mmmm: dF.i18n.monthNames[m + 12],
				yy:   String(y).slice(2),
				yyyy: y,
				h:    H % 12 || 12,
				hh:   pad(H % 12 || 12),
				H:    H,
				HH:   pad(H),
				M:    M,
				MM:   pad(M),
				s:    s,
				ss:   pad(s),
				l:    pad(L, 3),
				L:    pad(L > 99 ? Math.round(L / 10) : L),
				t:    H < 12 ? "a"  : "p",
				tt:   H < 12 ? "am" : "pm",
				T:    H < 12 ? "A"  : "P",
				TT:   H < 12 ? "AM" : "PM",
				Z:    utc ? "UTC" : (String(date).match(timezone) || [""]).pop().replace(timezoneClip, ""),
				o:    (o > 0 ? "-" : "+") + pad(Math.floor(Math.abs(o) / 60) * 100 + Math.abs(o) % 60, 4),
				S:    ["th", "st", "nd", "rd"][d % 10 > 3 ? 0 : (d % 100 - d % 10 != 10) * d % 10]
			};

		return mask.replace(token, function ($0) {
			return $0 in flags ? flags[$0] : $0.slice(1, $0.length - 1);
		});
	};
}();

// Some common format strings
dateFormat.masks = {
	"default":      "ddd mmm dd yyyy HH:MM:ss",
	shortDate:      "m/d/yy",
	mediumDate:     "mmm d, yyyy",
	longDate:       "mmmm d, yyyy",
	fullDate:       "dddd, mmmm d, yyyy",
	shortTime:      "h:MM TT",
	mediumTime:     "h:MM:ss TT",
	longTime:       "h:MM:ss TT Z",
	isoDate:        "yyyy-mm-dd",
	isoTime:        "HH:MM:ss",
	isoDateTime:    "yyyy-mm-dd'T'HH:MM:ss",
	isoUtcDateTime: "UTC:yyyy-mm-dd'T'HH:MM:ss'Z'"
};

// Internationalization strings
dateFormat.i18n = {
	dayNames: [
		"Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat",
		"Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"
	],
	monthNames: [
		"Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec",
		"January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"
	]
};

// For convenience...
Date.prototype.format = function (mask, utc) {
	return dateFormat(this, mask, utc);
};
