<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

require_once "init.php";
	if (f2_security_below(0)){exit;}

echo "<html><head><title>Reindexing</title></head>
<body>
Starting indexing
";

require 'newsletter_index.class.php';

echo "<html><head><title>Reindexing</title></head>
<body>
Starting indexing
";

$nli = new NewsletterIndex();
$nli->build_html();

echo "done";
echo "</body></html>\n";

