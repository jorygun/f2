<?php
namespace DigitalMx\Flames;

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

$login->checkLevel(4);

$idmsg = '';
$contributor = $_SESSION['login']['username'];
$contributor_id = $_SESSION['login']['user_id'];

$vintage = date('Y');

if (isset($_POST['submit'])){
  $asseta = $container['asseta'];
  if ($id = $asseta->postAssetFromForm($_POST) ) {
  	echo "<a href='/asset_editor.php?id=$id' target='asset_editor'>View in Editor</a>" . BRNL;

  	}
  	else {
  		echo  'Failed to post asset' . BRNL; exit ;
  	}
 exit;
}

?>

<!DOCTYPE html>
<head>
<style>
	table {border:1px solid black; border-collapse:collapse; }
	.assettable td {size:1em;}

	.assettable {width:550px;}
	.required {background:#FFC;}
	body {width:800px;}

</style>
<script src='https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js'></script>
  <script src="http://malsup.github.com/jquery.form.js"></script>
<script>

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
</script>


<title>Quick Asset</title>
</head>
<body>
<?=$idmsg?>
<h4>Create New Asset</h4>

<form  method="POST" enctype="multipart/form-data"  name="asset_form" id="asset_form" action = '/aq.php'>

<input type='hidden' name ='id' value = '0' >
<input type='hidden' name ='astatus' value = 'N' >
<input type='hidden' name='contributor_id' value = '<?=$contributor_id?>' >
<input type='hidden' name='contributor' value = '' >

<table class='assettable'>
<tr><td>Asset (reqd)</td><td>
Upload file <input type="file" name="uasset"> <br>
    or URL: <input type='text' name='asset_url' size=40>

</td></tr>
<tr><td>Title (reqd)</td>
	<td><input type='text' size='40' name='title' id='title' class='required'></td></tr>
<tr><td>Caption</td>
	<td><textarea  name='caption' id='caption' rows=2 cols=40></textarea></td></tr>
<tr><td>Vintage</td>
	<td><input type='text' name='vintage' id='vintage' size="6" value='<?=$vintage?>'> </td></tr>
<tr><td>Attribution </td><td><input type='text' name='source' id='source' size="30"> </td></tr>


</table>
<input type='submit' name='submit' id='submit'>
</form>
</body>
</html>
