<?
session_start();
if ($_SESSION['level'] < 7){header("location: forbidden.php");}
echo "Path: " . get_include_path() ."<br><br>\n";
phpinfo();
?>
