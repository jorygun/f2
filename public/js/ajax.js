function addVote(id,rank) {
     
    // test for id >0, rank = up | down
    
	$.ajax({
	url: "/scripts/ajax.php",
	data:'ajax=vote&item_id='+id+'&this_vote='+rank,
	type: "POST",
	beforeSend: function(){
		$('#vpanel-'+id+' .btn-votes').html("<img src='/graphics/loadericon.gif' />");
	},
	success: function(vpanel){
		$('#vpanel-'+id).html(vpanel);
	}
	});
}

function cancel_bulk(job) {
     
	$.ajax({
	url: "/scripts/ajax.php",
	data:'ajax=bulkmail&job='+job,
	type: "POST",
	
	success: function(bjobs){
		$('#in_bulk_queue').html(bjobs);
	}
	});
}

function sendLogin(uid) {
   $.ajax({
   url: "/scripts/ajax.php",
   data: 'ajax=sendlogin&uid='+uid,
   type: "POST",
   success: function (response) {
            if (response.status === "success") {
                alert ("Login Sent");
            }
            else if (response.status === "error"){
                alert("Error : " + data.d[0]);
            }
        }
        
 });  
}
