<?php
namespace digitalmx\flames;

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

if (!$login->checkLogin(4)) {exit;}
$idmsg = '';
$contributor = $_SESSION['login']['username'];
$vintage = date('Y');

if (isset($_POST['submit'])){
  $asseta = new AssetAdmin();
  if ($id = $asseta->postAssetFromForm($_POST) ) {
  		$idmsg = "<script>alert('Posted new id $id');</script> ";
  	}
  	else {
  		$idmsg = "Failed to post new asset";
  	}
  
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
<title>Quick Asset</title>
</head>
<body>
<?=$idmsg?>
<h4>Create New Asset</h4>
	
<form  method="POST" enctype="multipart/form-data"  name="asset_form" id="asset_form">
	
<input type='hidden' name ='id' value = '0' >
<input type='hidden' name ='astatus' value = 'N' >
<input type='hidden' name='contributor' value = '<?=$contributor?>' >

<table class='assettable'>
<tr><td>Asset (reqd)</td><td>
Upload file <input type="file" name="uasset"> <br>
    or URL: <input type='text' name='asset_url' size=40>

</td></tr>
<tr><td>Title (reqd)</td>
	<td><input type='text' size='40' name='title' id='title' class='required'></td></tr>
<tr><td>Caption</td>
	<td><textarea  name='caption' rows=2 cols=40></textarea></td></tr>
<tr><td>Vintage</td>
	<td><input type='text' name='vintage' size="6" value='<?=$vintage?>'> </td></tr>
<tr><td>Attribution </td><td><input type='text' name='source' size="30"> </td></tr>


</table>
<input type='submit' name='submit'>
</form>
</body>
</html>
