<?php
error_reporting(E_ERROR);

calc($settings);

function calc($settings) {

    $path = dirname(__FILE__).'/'.$subdir;
    $entries = glob($path.'*.jpg'); // scan dir    

    // for all png files:
	foreach($entries as $entry) {
		$img = imagecreatefromjpeg($entry);
		if ($img !== false) {
			$success = imagepng($img,basename($entry).'.png');
		} else {
			$sucesss = false;
		}
		echo "$entry converted to PNG.\n";
	}    

}

?>
