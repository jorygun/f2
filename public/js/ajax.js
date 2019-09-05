function addVote(id,rank) {
    // test for id >0, rank = up | down
	$.ajax({
	url: "/action.php",
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
	url: "/action.php",
	data:'ajax=bulkmail&job='+job,
	type: "POST",
	
	success: function(bjobs){
		$('#in_bulk_queue').html(bjobs);
	}
	});
}

function setTitle() {
     var title = $('#title_text').val();
	$.ajax({
	url: "/action.php",

	data:'ajax=setNewsTitle&title='+title,
	type: "POST",
	
	success: function(response){
		alert (response);
	}
	});
}
// this scriput used for verifyEmail, sendLogin,

function takeAction (action,uid=0,affectid='',message='') {
   $.ajax({
   url: "/action.php",
   data: 'ajax='+action+'&uid='+uid,
   type: "POST",
   success: function (response) {
            if (message != '') {alert (message);}
            if (affectid != '') { $('#'+affectid).html(response);}
        }
 });  
}

function verifyEmail(uid) {
    $.ajax ({
        url: "/action.php",
        data: 'ajax=verifyEmail&uid='+uid,
        type: "POST",
        success: function (response) {
            alert ("Verified");
            $('#emver-'+uid).html(response);
            $('#emstat-'+uid).html('Y');
        }
       
    
 });
}
function runStatus(pid) {
    var ptime = $('#'+pid).val();
   $.ajax ({
    url: "/action.php",
    data: 'ajax=runStatus&uid=' + ptime,
    type: "POST",
    success: function (response) {
        alert (response);
        }
   
 });
   
}

function getMessage (type) {
   $.ajax({
   url: "/action.php",
   data: 'ajax=getmess&type='+type,
   dataType: 'json',
  method: 'post',
  
   success: function (messdata) {
      console.log(messdata);
      var result = messdata;
     
      $('#mcontent').html(result['text']);
      $('#msubject').val(result['subject']);
   },
   error: function( jqXhr, textStatus, errorThrown ){
                    console.log( errorThrown );
   },
   });
}
