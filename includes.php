<?php
/**
 * Globally available functions and variables
 */
$dir = sys_get_temp_dir();

/**
 * Checks the size of a downloaded file to make sure
 * it matches the official size from GeoNames.
 * @param string $filename Just the name of the file
 * @param string $file Path to the downloaded file
 */
function bzptza_is_filesize_good($filename,$file) {
	$size = filesize($file);
	$head = array_change_key_case(get_headers("http://download.geonames.org/export/dump/$filename", TRUE));
	$official_size = $head['content-length'];
	return ($size >= $official_size);
}
