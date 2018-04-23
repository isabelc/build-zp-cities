<?php
/**
 * Handles the ajax request to check allCountries.txt for updates to any city records.
 */

$clear = isset($_GET['v']) ? $_GET['v'] : '';
if ('checkupdate' !== $clear) {
	echo json_encode(array("status" => "error","message" => "error"));
	exit;	
}
set_time_limit(360);
ini_set('memory_limit', '-1');
$time_start = microtime(true);

include_once '../includes.php';

// get last modified time of cities.txt file.
// Will use that as baseline date to check allCountries.txt modified dates against.
$url = 'https://download.cosmicplugins.com/cities.txt';
$h = get_headers($url, 1);
$dt = NULL;
if (!(!$h || strstr($h[0], '200') === FALSE)) {
    $dt = new \DateTime($h['Last-Modified']);
} else {
	echo json_encode(array("status" => "error","message" => "missing file ($url)"));
	exit;
}
$baseline_date = $dt->format('Y-m-d');

$updates = array();
$out = array();
$log = '';
$msg = '';

$handle = @fopen($dir . '/allCountries.txt','r');
if (false === $handle) {
	$status = "error";
	$msg = "missing file (allCountries.txt)";
} else {

	while(($v = fgets($handle, 4096))!==false) {

		$v = str_replace('"',' ',$v);
		$e = explode("\t", $v);

		if(isset($e[18])) {

			if ($e[18] > $baseline_date) {

				// this line was modified so add to updates, with Geonames id as key
				$updates[$e[0]] = $e;

			}

		}

	}

	fclose($handle);


	// Prep country, admin1 codes for reverse mapping
	$files = array('countryInfo.txt','admin1CodesASCII.txt');
	foreach($files as $k => $f) {
		$y = file('http://download.geonames.org/export/dump/' . $f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$x = array();
		foreach($y as $line) {
			$e = explode("\t", $line);
			$x[] = $e;
		}
		unset($y);
	    if ('countryInfo.txt' == $f) {
		    $keys = array_column($x, 4);//country name
		    $values = array_column($x, 0);// ISO code
	    } else {
	        $keys = array_column($x, 2);//admin1 name ascii
	        $values = array_column($x, 0);//admin1 code
	    }
	    unset($x);
	    ${"codes_by_name".$k} = array_combine($keys, $values);// $codes_by_name0 = country. $codes_by_name1 = admin1
	}
	$names_by_code0 = array_flip($codes_by_name0);
	$names_by_code1 = array_flip($codes_by_name1);


	if (count($updates)) {

		$lines_to_update = array();

		// tmp array of just the geonames ids of those updated
		$_tmp_updates_ids = array_keys($updates);

		//pluck these lines from cities.txt

		$current_cities_file = $url;

		$x = file($current_cities_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

		foreach( $x as $line ) {

			$e = explode("\t", $line);
			if (in_array($e[0], $_tmp_updates_ids)) {// if this id is one of our updated ids

				/****************************************************
				* @todo fix:

				Fatal error: Maximum execution time of 360 seconds exceeded in /var/www/html/build-zpatlas/ajax/ajax-checkupdate.php on line 102
				* 
				****************************************************/


				// did any of our fields actually change?
				/*

				Relevant fields on an $updates element

				* [1]	name
				* [4]	latitude
				* [5]	longitude
				* [8]	country_code
				* [10]	admin1_code
				* [17]	timezone
				* [18]	modification_date

				...and the same fields on cities.txt ($e)

				* [1]	name
				* [2]	latitude
				* [3]	longitude 
				* [4]	country name
				* [5]	admin name
				* [6]	timezone
				* [7]	modification_date

				*/

				$field_map = array(
					array(1,1,'name'),
					array(4,2,'latitude'),
					array(5,3,'longitude'),
					array(8,4,'country'),
					array(10,5,'admin1'),
					array(17,6,'timezone'),
					array(18,7,'modification_date')
				);

				foreach($field_map as $f) {

					if ('country' === $f[2]) {

						$new_value = trim($updates[ $e[0] ][ $f[0] ]);
						
						
						$old_country_name = $e[4]; // to log

						// convert name to code to check against new value

						$old_value = isset($codes_by_name0["$e[4]"]) ? trim($codes_by_name0["$e[4]"]) : '';// country code



						
					} elseif('admin1' === $f[2]) {


						// add new country code to new admin1code value
						$new_country_code = trim($updates[ $e[0] ][8]);
						$new_value = $new_country_code . '.'. trim($updates[ $e[0] ][ $f[0] ]);

						
						$old_admin1_name = $e[5];// to log


						// convert old name to code to check against new value

						$old_value = isset($codes_by_name1["$e[5]"]) ? trim($codes_by_name1["$e[5]"]) : '';


					} else {
						$new_value = trim($updates[ $e[0] ][ $f[0] ]);
						$old_value = trim($e[ $f[1] ]);
					}

					
					// Finally check for changes

					if ($new_value !== $old_value) {

						// This field is modified so update it


						// if this updated field is country or admin1, change its code to its name.

						if ('admin1' === $f[2]) {

					

							$e[5] = isset($names_by_code1["$new_value"]) ? trim($names_by_code1["$new_value"]) : '';

							// for logging message
							$new_value .= ' (' . $old_admin1_name . ' to ' . $e[5] . ')';



						} elseif('country' === $f[2]) {


							$e[4] = isset($names_by_code0["$new_value"]) ? trim($names_by_code0["$new_value"]) : '';

						} else {
							$e[ $f[1] ] = $new_value;
						}


						// log the change
						$log[] = $e[0] . ' : ' . $f[2] . ' changed from ' . $old_value . ' to ' . $new_value;



					}
				}

			}


			// put back the line
			$out[] = implode("\t", $e);

			
		}


		unset($x, $codes_by_name0, $names_by_code0, $codes_by_name1, $names_by_code1);


		// Only update the cities.txt file if we had any actual updates to our relevant fields

		if ($log) {

			$log = implode(PHP_EOL, $log);

			$s = implode(PHP_EOL, $out);
			$write = file_put_contents($current_cities_file, $s);// update the file
			if ((!$write ) || $write < 5 ) {
				$status = "error";
				$msg .= 'Update failed.'. PHP_EOL . $log . PHP_EOL;
			} else {

				$status = "success";
				$msg .= "Success. $current_cities_file was updated. Here is a log of the changes:" . PHP_EOL . PHP_EOL . $log . PHP_EOL . PHP_EOL;
			}

		} else {

			// no changes 
			$status = "success";
			$msg .= "There are no updates.";

		}


		$time_end = microtime(true);
		$tot = ($time_end - $time_start);
		$exec_time = number_format($tot, 1);
		
		$msg .= " Total Time: $exec_time seconds.";


	}

}
set_time_limit(30);// restore to default
ini_set('memory_limit','128M');// restore to default
echo json_encode(array("status" => $status,"message" => $msg));
