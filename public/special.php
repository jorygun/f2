<?php
include_once "init.php";
 $nav = new NavBar(1);
    $navbar = $nav -> build_menu();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<title>AMD Flames Special Pages</title>
<link rel='stylesheet' href='/css/flames2.css'>

</head>


<body>

<?=$navbar?>


<h3>Special Pages</h3>
<p><a href="/spec/hbwjs80.php">Jerry Sanders' 80th Birthday</a></p>
<p><a href="/spec/anixter.php">Tribute to Ben Anixter</a></p>
<p><a href="/spec/Upward.php">AMDers who have gone on to found or lead other companies</a>
<p><a href="/galleries.php/?4547" target="gallery">AMD Ad Reprints</a>
<p><a href="/spec/spirit.php" >The Spirit of AMD</a>

</div>
</div>
</body></html>
