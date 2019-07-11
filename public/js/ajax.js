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


// this scriput used for verifyEmail, sendLogin,

function takeAction (uid,action) {
   $.ajax({
   url: "/scripts/ajax.php",
   data: 'ajax='+action+'&uid='+uid,
   type: "POST",
   success: function (response) {
               alert (response);
        }
 });  
}

function getMessage (type) {
   $.ajax({
   url: "/scripts/ajax.php",
   data: 'ajax=getmess&type='+type,
   dataType: 'json',
  method: 'post',
  
   success: function (messdata) {
      console.log(messdata);
      var result = $.parseJSON(messdata);
      alert ("ajax: " + result['subject']);
      
      $('#mcontent').html(result['text']);
      $('#msubject').html(result['subject']);
   },
   error: function( jqXhr, textStatus, errorThrown ){
                    console.log( errorThrown );
   },
   });
}
