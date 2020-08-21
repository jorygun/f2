/* code below is to receive an id from postMessage() in the child window.
 // aq1 puts retrieved asset id in asset_list field, replacing whatevers there.
 // aq ADDs the retrieved asset id to existing content.
 // use aq1 for comments (only 1 asset) and aq for article editor (multiples.)
*/



function addAsset(aid) {

 /*  alert ('recvd id ' + aid); */


  $('#asset_list').val(aid);

}
