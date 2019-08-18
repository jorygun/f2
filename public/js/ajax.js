function addVote(id,rank) {
     
    // test for id >0, rank = up | down
    
	$.ajax({
	url: "/action.php",
	data:'ajax=vote&item_id='+id+'&this_vote='+rank,
	type: "POST",
	beforeSend: function(){
		$('#vpanel-'+id+' .btn-votes').html("<img src='/graphics/loaderIcon.gif' />");
	},
	success: function(vpanel){
		$('#vpanel-'+id).html(vpanel);
	}
	});
}

function cancel_bulk(job) {
     
	$.ajax({
	url: "/action.php",
	data:'ajax=bulkmail&job='+job,
	type: "POST",
	
	success: function(bjobs){
		$('#in_bulk_queue').html(bjobs);
	}
	});
}


// this scriput used for verifyEmail, sendLogin,

function verifyEmail(uid) {

    $.ajax({
        url: "/action.php",
        data: 'ajax=verifyEmail&uid='+uid,
        type: "POST",
        success: function(response) {
            $('#emver').html(response);
            $('#emstat').html('Y');
        }
    });
}
function takeAction (uid,action,affects='',message='') {
    var objid='#'+affects;
   $.ajax({
   url: "/action.php",
   data: 'ajax='+action+'&uid='+uid,
   type: "POST",
   success: function (response) {
                if (message != ''){
                    alert (message);
                }
               if (affects != '') {
                    $(objid).html(response);
                }
        }
 });  
}
