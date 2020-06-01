/*
 takes the return result from submitting an asset_form.  The return
 result is the (new) id of the submitted asset.  This script makes that
 available to other scripts - specifically asset_quick edit and new comment -
 to put the new asset id into the 'asset_list' field.  Uses messaging to do it.

 The other scripts retrieve the id using the aq.js or qaq1.js scripts

*/
        // wait for the DOM to be loaded
        $( (function() {
            // bind 'myForm' and provide a simple callback function
            $('#asset_form').ajaxForm(function(responseText, statusText, xhr, $form) {
            	if(statusText != 'success') {alert ('Failed'); return false;}
            	// look for new id
            	var matches = responseText.match(/id=(\d*)/) ;
            	if (! matches ) {
            		alert ('No ID in return:' + responseText);
            		exit();
            	} else {
            		var newid = matches[1];
            	}

                alert('ID: ' + newid);
                var target = window.opener;
                target.postMessage(newid);

               window.close();
            });
        });

