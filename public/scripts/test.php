<html>
<head>
<script type='text/javascript'>
	function check_form(params) {
		var err='';
	  for (i=0; i<params.length; i++) {
		//alert ("testing " + params[i]);
		var txt = document.getElementById(params[i]).value;
	    if (txt.length == 0){
	    	var newerr =  'Field '+params[i]+' must not be empty. ' + "\n";
	    	//alert (newerr);
	        err += newerr;
		}
		}

		if  (err != ''){
			 alert(err);
			 return false;
	  	}
	  return true;
	}
</script>
</head>
<body>
<p>test form</p>

<?php
if ($_SERVER[REQUEST_METHOD] == 'GET'){form();}
if ($_SERVER[REQUEST_METHOD] == 'POST'){
    $vars = post();
    form($vars);
}
function form($vars){
  echo <<<EOT
    <hr>
<form method='POST' onsubmit="return check_form(['id','t2']);">
<input name='id' type='text' id='id' value="$vars[id]">
<input name='t2' type='text' id='t2' value="$vars[t2]">
<button type='button'  onclick = "not_empty(['id','t2']);">test</button>
<input type='submit'">
</form>
EOT;

}

function post(){

$vars = $_POST;
echo "Data posted<br>\n";
echo "<pre>" . print_r ($vars) . "</pre>";
return $vars;
}
?>
</body></html>
