/* code below is to receive an id from postMessage() in the child window. */

window.addEventListener("message", receiveMessage, false);

function receiveMessage(event) {
  var id = event.data;
 /*  alert ('recvd id ' + id); */

  $('#asset_list').val(id);

}
