<?php
/**
 * Handles the ajax requests to download data files from GeoNames.
 */
set_time_limit(360);
ini_set('memory_limit', '-1');

$filename = isset($_GET['f']) ? $_GET['f'] : '';
if (!$filename) {
	echo json_encode(array("status" => "error","message" => "error"));
	exit;
}
include_once '../includes.php';
$url = 'http://download.geonames.org/export/dump/' . $filename;
$file = $dir . '/' . $filename;
$retry = isset($_GET['retry']) ? $_GET['retry'] : 0;

function bzptza_curl_get_file($url, $filepath) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    $raw_file_data = curl_exec($ch);
    curl_close($ch);

    file_put_contents($filepath, $raw_file_data);

    if (filesize($filepath) > 2000) {
		echo json_encode(array("status" => "success","message" => "GeoNames data file was successfully downloaded."));
		exit;
	} else {
		return false;
	}

}

function bzptza_file_get_contents($url, $file, $filename) {
	$msg = $status = "error";

	$put = file_put_contents($file, file_get_contents($url));

	if (false === $put) {
		$msg = "GeoNames data file ($filename) could not be downloaded.";		
	} elseif (filesize($file) > 1000) {
		$status = "success";
		$msg = "GeoNames data file ($filename) was successfully downloaded.";
	}

	echo json_encode(array("status" => $status,"message" => $msg));
	exit;
}

// only check file if this is not a retry

if (empty($retry) && file_exists($file)) {

	// file exists, but check size

	if (bzptza_is_filesize_good($filename,$file)) {
		$status = "info";
		$msg = "The file, $filename, already exists, so you have already downloaded it.";		
	} else {
		/****************************************************
		* The file already exists, but it is incomplete. Trying download again....
		* send back ajax response of "working" to let it kick off another ajax to retry download.
		****************************************************/
		$status = "working";
		$msg = "$filename already exists, but it is incomplete. Trying to download again now....";
		
	}


} else {

	if (extension_loaded('curl')) {

		$curl = bzptza_curl_get_file($url, $file);

		if (false === $curl) {

			bzptza_file_get_contents($url, $file, $filename);
		}
			
	} else {
	
		bzptza_file_get_contents($url, $file, $filename);

	}
}

set_time_limit(30);// restore to default
ini_set('memory_limit','128M');// restore to default
echo json_encode(array("status" => $status,"message" => $msg));
