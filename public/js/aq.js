/* code below is to receive an id from postMessage() in the child window.
 // aq1 puts retrieved asset id in asset_list field, replacing whatevers there.
 // aq ADDs the retrieved asset id to existing content.
 // use aq1 for comments (only 1 asset) and aq for article editor (multiples.)
*/

$(window).addEventListener("message", receiveMessage, false);

function receiveMessage(event) {
  var id = event.data;
 /*  alert ('recvd id ' + id); */
  var ids = $('#asset_list').val();
  ids = ids.trim();
  var comma;
  if (ids){comma = ', ';}
  else{ comma = '';}

  $('#asset_list').val(ids + comma + id);

}
