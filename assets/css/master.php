<?php

header('Content-type: text/css');

$files = scandir('./');
foreach ($files as $file) {
	if (substr($file, strlen($file)-3) == 'css') {
		echo "@import url('$file');\n";
	}
}

?>
