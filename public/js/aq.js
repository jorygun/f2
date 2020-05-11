/* code below is to receive an id from postMessage() in the child window. */

	window.addEventListener("message", receiveMessage, false);

function receiveMessage(event) {
  var id = event.data;
 /*  alert ('recvd id ' + id); */
  var ids = $('#assetids').val();
  ids = ids.trim();
  var comma;
  if (ids){comma = ', ';}
  else{ comma = '';}
  
  $('#assetids').val(ids + comma + id);
  
}
