<?php
//BEIN START
	require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';;
	if (f2_security_below(1)){exit;}
	$pdo = MyPDO::instance();

//END START
#opportunity editor

echo <<<EOT
<html>
<head>
<title>Opportunity Editor</title>
<script type='text/javascript'>
	function set_expired(){
		var d = new Date();
	    d.setDate(d.getDate() - 1); //subtract 1 day
	  // alert ("Date = " + d);
	date = d.getFullYear() + '-' +
    ('00' + (d.getMonth()+1)).slice(-2) + '-' +
    ('00' + d.getDate()).slice(-2) + ' '
    ;
    alert ("New expiration: " + date);
		document.getElementById('exdate').value = date;
		document.getElementById('opped').submit();

	}

</script>
</head>
<body>
<h4>Opportunity Editor</h4>

EOT;
if ($_SERVER[REQUEST_METHOD] == 'GET'){
	if (! $id = $_GET[opp]){
		echo "No ID supplied";
		exit;
	}
	if ($id == 'new'){
		$row['id'] = 0;
		$row['created'] = sql_now();
		$row['owner'] = $_SESSION[username];
		$row['owner_email'] = $_SESSION[user_email];
		$row['expired'] = date('Y-m-d',strtotime('+ 90 days'));
	}
	else {
		$row = get_opp($id);
	}
		show_form($row);
}

else if ($_SERVER[REQUEST_METHOD] == 'POST'){
    $pdo = MyPDO::instance();
	#list ($clean,$safe) = clear_safe[$_POST];
	$id = $_POST[id];
	$sqla = array ();


	if (empty($_POST['expired']) or $_POST['id'] == 0 ){
	    $_POST['expired'] = date('Y-m-d',strtotime('+ 90 days'));}
	else{
	    $_POST['expired'] = date('Y-m-d',strtotime($_POST['expired']));
	    }


	foreach (
	    array ('title','location','owner','owner_email','link','description','expired')
	    as $f){
		$v = stripslashes($_POST[$f]);
		$uv = $v;
		$sqla[] = "$f = '$uv'";
		#echo "F $f; V $v UV $uv <br>";
	}

	$sqlset = implode (",",$sqla);

	if ($id == 0){ #new record
		$sql = "INSERT into opportunities SET created='$_POST[created]',$sqlset;";

	}
	else {
		$sql = "UPDATE opportunities SET $sqlset WHERE id = '$id';";
	}
	#echo $sql;

	 $result = $pdo->query($sql);
	if ($id == 0){#new record

		  $id = $pdo::lastInsertId;
	  	$row = $result->fetch();
	  	$id = $row[id];
	}

	#$row = get_opp($id);
	#show_form($row);
	echo "<script>window.close();</script>\n";
}

function get_opp($id){
    $pdo = MyPDO::instance();
	$sql = "
            SELECT *
            FROM opportunities
            WHERE id = '$id'
            ;
            ";


	 $result = $pdo->query($sql);
	$num_rows = $result->rowCount();


	if ($num_rows == 0){
		echo "No id $id Found.";
		exit;
	}
	echo "Retriving $num_rows results";

	$row = $result->fetch();

	return $row;
}
function show_form($row){
	$id = $row[id];
	$expired = $row[expired];
	$date = time();
	$xdate = strtotime($expired);

 $status = ($row['expired'] == '0000-00-00' ||
        (time() ) < strtotime($row['expired']) ) ? 'Active':'Expired';

	 $readonlyowner = 'READONLY';
	if(!empty($uid = get_id_from_name($row[owner])[0])){ #name found
		$checkedowner = "👌$uid";
	}
	else {$checkedowner= 'X';}


	if ($_SESSION[level]>6){ #admin status
		$readonlyowner = '';

    }


	echo <<<EOT
	<p>Enter the information for a job opportunity here.  You can enter a url
	if more information is posted online somewhere, or you can just write the
	information in the description field, or you can email a pdf file to
	the <a href="mailto:editor@amdflames.org">editor</a>.</p>
	<p>You can close your opportunity by changing the expiration date
	or clicking the button at the bottom of the screen.</p>

	<form method='POST' id='opped' >
	<input type='hidden' name='id' value='$row[id]'>

	<table>

	<tr><td>id</td><td>$row[id]</td></tr>
	<tr><td>status </td><td>$status</td></tr>
	<tr><td>created</td><td><input type='text' name='created' value='$row[created]' READONLY</td></tr>
	<tr><td>title</td><td><input type='text' name='title' value='$row[title]' size='40'></td></tr>
	<tr><td>location</td><td><input type='text' name='location' value='$row[location]' size='40'></td></tr>

	<tr><td>owner (read only) </td><td><input type='text' name='owner' value='$row[owner]' $readonlyowner>$checkedowner</td></tr>
	<tr><td>email (you can change this.)</td><td><input type='text' name='owner_email' value='$row[owner_email]'></td></tr>

	<tr><td>url for more information<br>
	(must start 'http://')</td><td><input type='text' name='link' value='$row[link]' size='40'></td></tr>

	<tr><td>description</td><td><textarea name='description' rows=8 cols=60>$row[description]</textarea></td></tr>
EOT;
	if ($id != 0){
		echo "<tr><td>expiration</td><td><input type='text' name='expired' id='exdate' value='$row[expired]'>
		</td></tr>";
	}
echo "
	</table>
	<p><button onClick = 'form.submit();'>Update Job</button></p>
	</form>
	";
if ($status == 'Active'){
	echo <<<EOT
	<p><button onClick = "set_expired();document.getElementById('opped').submit();">Expire and close window</button></p>"
EOT;
	}

}

?>
