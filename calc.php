<?php
error_reporting(E_ERROR);
/**
    Berechne die Stirnneigung aus 10 Markern in einer PNG-Datei:
    
    Die Marker sind einzelne Pixel in der Farbe 0x00FFFF (aqua)
    
    Annahmen:
	Blick nach rechts. "Basis" (z.B. Frankfurter
    	Horizontale) muss nicht horizontal liegen.
    
    Alle PNG-Dateien müssen im Unterverzeichnis pics/ liegen.
    
    Ausgabe im CSV-Format: dateiname;gradmaßwinkel1;...;gradmaßwinkelx.
    
    Start mit php -f calc.php __ parameter
*/    
$_GET['mode'] = '1to10';
$settings = array( 
	'mode' => $_GET['mode'], // one of '1,4', '3,7,8', '2,5,6', '1to10'
	'verbose' => true,
	'marker0' => 0x00FFFF,
	'marker1' => 0xFF32C9
);

calc($settings);

function calc($settings) {
    $subdir = getSubdir();    
    echo "Untersuche alle Bilder in ".$subdir."\n\n";
  
    // create output streams
    $output_filename = 'ergebnis_'.@date('YmdHis').'.csv';
    $output_file = fopen($output_filename,'w');
    $echo_output = fopen('php://output','w');
    $header = array("Dateiname", "Winkel1", "...");
    fputcsv($output_file, $header, ";");
    fputcsv($echo_output, $header, ";");
  
    // scan directory:
    $path = dirname(__FILE__).'/'.$subdir;
    $entries = glob($path.'*.png'); // scan dir    
    $error_files = array();
    // for all png files:
	  foreach($entries as $entry) {
        // handle the file
        $result = handleFile($entry, $settings);
        // output results
        fputcsv($output_file, $result, ";");
        fputcsv($echo_output, $result, ";");
        //count errors
        if(!$result[count($result)-1]) {
            $error_files[] = $result[0];
        }
	  }    
    // close output streams
    fclose($output_file);
    fclose($echo_output);
    
    // echo overall results
    echo "\nFertig. Ergebnisdatei wurde erstellt ($output_filename).\n";
    switch(count($error_files)) {
        case 0:
            echo "Keine Fehler gefunden.\n";
            break;
        case 1:
            echo "Es gab einen Fehler in ". $error_files[0]. ".\n";
            break;
        case 2:
            echo "Es gab zwei Fehler in ". $error_files[0] ." und " . $error_files[1] . ".\n";
            break;
        default:
            echo "Es gab ".count($error_files)." Fehler, u.a. in ". $error_files[0] ." und " . $error_files[1] . ".\n";
    }    
}

/**
    Return the sub directory to work on.
    It can be passed via GET parameter "subdir".
    (For bash use parameter "subdir=pics/deep/deeper".)
    If no subdir is given, "pics" will be used.
    @return the directory with the png files to analyse.
*/
function getSubdir() {
    if(!isset($_GET['subdir'])) {
        $subdir = 'pics';
    } else {
        $subdir = $_GET['subdir'];
    }
    $subdir = trim($subdir, '/ \t\n\r\0\x0B').'/';
    return $subdir;
}

/**
    Calculate angle from png file input.
    Image is scanned for markers, that
    defined lines whose intersecting angle
    is calculated.
    @return array(filename, angle, success?)
*/
function handleFile($filename, $settings) {
	// -----------------
    // load image:
	// -----------------
    $img = imagecreatefrompng ($filename);
    $width = imagesx($img);
    $height = imagesy($img);
	
	$marker0 = $settings['marker0'];
	$marker1 = $settings['marker1'];
	//echo __LINE__."\n";    
	// -----------------
    // scan for marker:
	// -----------------
    $value1 = -1;
    $value2 = -1;
    $p = array('all' => array(), $marker0 => array(), $marker1 => array());
    for($y = 0; $y < $height; $y ++){ // all lines
        for($x = 0; $x < $width; $x++) { // all rows
            $rgb = @imagecolorat($img, $x, $y);            
            //check, if marker found
            if(array_key_exists($rgb, $p)) {
                // store point
                $p[$rgb][] = array('x' => $x, 'y' => $height - $y);
				        $p['all'][] = array('x' => $x, 'y' => $height - $y);
            }
                       
        }               
    }

	// -----------------
	// calculacte angle
	// -----------------
	//echo __LINE__."\n";
	$result = calcAngle($p, $settings);    	
	//echo "result for $filename: ". print_r($result, true) ."\n";
    return array_merge(array(basename($filename)), $result); // push filename in front of result
} 

function calcAngle($p, $settings) {
	// Vorgabe, wie viele Punkte (welcher Farbe) vorhanden sein müssen
	$numberOfPoints = array(
		'1to10' => array(10,0)
	);
	if( count($p[$settings['marker0']]) != $numberOfPoints[$settings['mode']][0]
	|| count($p[$settings['marker1']]) != $numberOfPoints[$settings['mode']][1] ) {
		return array('Anzahl der Punkte fehlerhaft fuer diese Definition(en).',
			'Definitionen '.$settings['mode'].', Anzahl Punkte erwartet: '.$numberOfPoints[$settings['mode']][0].','.$numberOfPoints[$settings['mode']][1]. ' Anzahl Punkte real: '.count($p[$settings['marker0']]).','.count($p[$settings['marker1']]),
			false);
	}
	switch($settings['mode']) {		
		case '1to10':
			return calc1to10($p, $settings);
	}
}

function calc1to10($p, $settings) {
	// markers with highest x-values are op and i (where op is above i)
	unset($p[$settings['marker0']]);
	unset($p[$settings['marker1']]);

	$opAndI = array();
	for($i = 0; $i < 2; $i++) {
		$index = getIndexOfMinX($p['all']); // use MAX, if skulls are turned to left
		$opAndI[] = array('x'=>$p['all'][$index]['x'], 'y'=>$p['all'][$index]['y']);
		array_splice($p['all'], $index, 1);		
	}
	$p['OpI'] = array();
	if($opAndI[0]['y'] < $opAndI[1]['y']) {
		$p['OpI'][0] = $opAndI[1];
		$p['OpI'][1] = $opAndI[0];
	} else {
		$p['OpI'][0] = $opAndI[0];
		$p['OpI'][1] = $opAndI[1];
	}
	
	// All points are detected correctly. But note that Or and Po might be interchanged.

	$alphaIG = angleToAxis($p, 'OpI', 1, 'all', 4 );
	$alphaOpG = angleToAxis($p, 'OpI', 0, 'all', 4 );
	
	// calculate angle of the line g-b (relative to x axis)
	$alphaGB = angleToAxis($p, 'all', 0, 'all', 4);
	
	// calculate angle of the line g-x (relative to x axis)
	$alphaGX = angleToAxis($p, 'all', 4, 'all', 2, $alphaGB);

	// Frankfurt base. Note that  6 and 7 might be interchanged	
	$alphaBase = angleToAxis($p, 'all', 6, 'all', 7);
	
	// calculate angle of the line sg-b (relative to x axis)
	$alphaSgB = angleToAxis($p, 'all', 0,'all',  3);
	
	// calculate angle of the line sg-n (relative to x axis)
	$alphaSgN = angleToAxis($p, 'all', 3, 'all', 5, $alphaSgB);

	// calculate angle of the line sg-i (relative to x axis)
	$alphaSgI = angleToAxis($p, 'OpI', 1, 'all', 3, $alphaSgB);
	
	// calculate angle of the line b-n (relative to x axis)
	$alphaBN = angleToAxis($p, 'all', 0, 'all', 5);

	$alphaIN = angleToAxis($p, 'OpI', 1, 'all', 5);
	
	// calculate angle of the line m-n (relative to x axis)
	$alphaMN = angleToAxis($p, 'all', 1, 'all', 5, $alphaBN);

	// calculate angle of the line sg-x (relative to x axis)
	$alphaSgX = angleToAxis($p, 'all', 2, 'all', 3);		

	// angle between the two lines
	$alpha[1-1] = abs($alphaMN-$alphaBase);
	$alpha[2-1] = abs($alphaGX-$alphaIG);
	$alpha[3-1] = abs($alphaBN-$alphaIN);
	$alpha[4-1] = abs($alphaBN-$alphaBase);
	$alpha[5-1] = abs($alphaGB-$alphaIG);
	$alpha[6-1] = abs($alphaGB-$alphaOpG);
	$alpha[7-1] = abs($alphaSgN-$alphaIN);
	$alpha[8-1] = abs($alphaSgB-$alphaIN);
	$alpha[9-1] = abs($alphaGX-$alphaBase);
	$alpha[10-1] = abs($alphaSgX-$alphaBase);
	// convert to usual format used in Germany (degree, comma)
	$value = array();
	for($i = 0; $i < count($alpha); $i++) {
		$value[$i] = format($alpha[$i]);
	}
	$value[count($alpha)] = true;
	
	if($settings['verbose']) {		
		return $value;
	} else {
		return $value;
	}
}

/**
 If compareTo is set, then the resulting angle is compared to compareTo. If both show in different directions,
 then the complement of the resulting angle is used.
*/
function angleToAxis($p, $markerI, $i, $markerJ, $j, $compareTo = null) {
	// calculate angle of the line defined by markers/i/j (relative to x axis)

	if($p[$markerI][$i]['x']-$p[$markerJ][$j]['x'] != 0) {
		// points do not define a vertical line, use arctan
		$alpha = atan(($p[$markerI][$i]['y']-$p[$markerJ][$j]['y'])/($p[$markerI][$i]['x']-$p[$markerJ][$j]['x']));
	} else {
		// points do not define a vertical line, use 90∞
		$alpha = M_PI_2; // 90∞ // XXX THIS MIGHT BE AN ERROR IN SOME CASES, WHERE -90∞ IS EXPECTED
	}
	if(isset($compareTo)) {
		if($compareTo > 0 && $alpha < 0) {
			// THIS IS MADE FOR LEFT-SIDED PICTURES. TO BE TESTED!
			//echo "\ncompareTo was $compareTo, alpha was $alpha, new alpha is $alpha-pi\n";
			$alpha = $alpha + M_PI;
		} elseif ($compareTo < 0 && $alpha > 0) {
			$alpha = $alpha - M_PI;
		}
	}

	return $alpha;
}

function format($alpha) {
	return str_replace('.',',',rad2deg($alpha));  
}

function p2str($p) {
	return $p['x'].'|'.$p['y'];
}

function getIndexOfMinX($p) { // call with $p['all']
	$minXInd = 0;
	for($i = 1; $i < count($p); $i++) {
		if($p[$i]['x'] < $p[$minXInd]['x']) {
			$minXInd = $i;
		}
	}
	return $minXInd;
}

?>
