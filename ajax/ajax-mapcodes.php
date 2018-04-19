<?php
/**
 * Handles the ajax request to replace country codes and admin1 codes to the real name.
 * Creates the final cities.txt.
 *
 * Admin1 codes are from 'admin1CodesASCII.txt'
 * Country codes are from 'countryInfo.txt'
 *
 * 
****************************************************/

$clear = isset($_GET['v']) ? $_GET['v'] : '';
if ('mapcodes' !== $clear) {
	echo json_encode(array("status" => "error","message" => "error"));
	exit;	
}
set_time_limit(360);
ini_set('memory_limit', '-1');
$time_start = microtime(true);
include_once '../includes.php';

$cities_tmp = $dir . '/cities_tmp.txt';

// check if completion file exists
$cities_file = $dir . '/cities.txt';
if (file_exists($cities_file)) {
	$status = "info";
	$msg = "The file, $cities_file, already exists, so this step was already completed.";	
} else {

	// prep codes
	$files = array('countryInfo.txt','admin1CodesASCII.txt');
	foreach($files as $k => $f) {
		$file = 'http://download.geonames.org/export/dump/'.$f;
		$y = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$x = array();
		foreach($y as $line) {
			$e = explode("\t", $line);
			$x[] = $e;
		}
		unset($y);
	    if ('countryInfo.txt' == $f) {
		    $keys = array_column($x, 0);// ISO code
		    $values = array_column($x, 4);//country name
	    } else {
	        $keys = array_column($x, 0);//admin1 code
	        $values = array_column($x, 2);//admin1 name ascii
	    }
	    unset($x);
	    global ${"codes".$k};
	    ${"codes".$k} = array_combine($keys, $values);
	}

	$cities = @file($cities_tmp, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

	if (false === $cities) {
		$status = "error";
		$msg = "missing file ($cities_tmp)";
	} else {
		$out = array();
		foreach($cities as $c) {
			$e = explode("\t", $c);
		    $e[5] = isset($codes1["$e[4].$e[5]"]) ? trim($codes1["$e[4].$e[5]"]) : '';// replace admin1 code with name
		    $e[4] = isset($codes0["$e[4]"]) ? trim($codes0["$e[4]"]) : '';// replace country code with country name
		    $out[] = implode("\t", $e);			
		}
		$s = implode(PHP_EOL, $out);
		file_put_contents($cities_file, $s);
		$time_end = microtime(true);
		$tot = ($time_end - $time_start)/60;
		$exec_time = number_format($tot, 1);
		$status = "success";
		$msg = "The file '$cities_file' is complete. Codes for country and region were successfully replaced with their real names. Total Time: $exec_time minutes.";
	}
}
set_time_limit(30);// restore to default
ini_set('memory_limit','128M');// restore to default
echo json_encode(array("status" => $status,"message" => $msg));
