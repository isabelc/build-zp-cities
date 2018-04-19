<?php
/**
 * Handles the ajax request to pluck unique cities from allCountries.txt.
 * 
 * Steps include:
 * 
 * 1. Extract only cities (& towns, villages...) from GeoNames datafile allCountries.txt,
 * 2. and parse them to pluck only unique cities by city|admin1|country,
 * 3. and place these in a new file, /tmp/cities_tmp.txt
 *
 * Only the needed fields will be extracted:
 *
 * [0]	geonameid
 * [1]	name
 * [4]	latitude
 * [5]	longitude
 * [8]	country_code
 * [10]	admin1_code
 * [17]	timezone
 * [18]	modification_date
 *
 *
 * The new fields will be:
 *
 * [0]	geonameid
 * [1]	name
 * [2]	latitude
 * [3]	longitude 
 * [4]	country_code
 * [5]	admin1_code
 * [6]	timezone
 * [7]	modification_date
 *
 */
$clear = isset($_GET['v']) ? $_GET['v'] : '';
if ('pluck' !== $clear) {
	echo json_encode(array("status" => "error","message" => "error"));
	exit;	
}
set_time_limit(360);
ini_set('memory_limit', '-1');
$time_start = microtime(true);

include_once '../includes.php';

// check if completion file exists
$cities_file = $dir . '/cities_tmp.txt';

if (file_exists($cities_file)) {
	$status = "info";
	$msg = "The file, $cities_file, already exists, so this step was already completed.";
} else {

	$tmp = array();
	$handle = @fopen($dir . '/allCountries.txt','r');

	if (false === $handle) {
		$status = "error";
		$msg = "missing file (allCountries.txt)";
	} else {

		while(($v = fgets($handle, 4096))!==false) {

			$v = str_replace('"',' ',$v);
			$e = explode("\t", $v);

			// validate that mandatory fields are not empty
			if(!empty($e[0]) && !empty($e[1]) && !empty($e[17]) && isset($e[18]) && !empty($e[6]) && !empty($e[7])) {

				// Limit to only cities, towns, villages...(feature class P)
				$feature_codes = array('PPL','PPLA','PPLA2','PPLA3','PPLA4','PPLC');
				if('P' == $e[6] && in_array($e[7],$feature_codes)) {

					/* Remove unwanted fields. Keep only these:
					 * [0]	geonameid
					 * [1]	name
					 * [4]	latitude
 					 * [5]	longitude
					 * [8]	country_code
					 * [10]	admin1_code
					 * [17]	timezone
					 * [18]	modification_date
					 */
					unset($e[16],$e[15],$e[14],$e[13],$e[12],$e[11],$e[9],$e[7],$e[6],$e[3],$e[2]);

					/****************************************************
					*
					* Only keep rows with a unique combination of these 3 for a city:
					*
					*	name, admin1_code, country_code
					*
					* This will be a unique index (key) in the database.
					* 
					****************************************************/
					// unique city name|admin1_code|country_code
					$id = $e[1] . "|" . $e[10] . "|" . $e[8];
					isset($tmp[$id]) or $tmp[$id] = implode("\t", $e);

				}
			}

		}
		fclose($handle);
		$unique_array = array_values($tmp);
		$s = implode(PHP_EOL, $unique_array);
		file_put_contents($cities_file, $s);
		$time_end = microtime(true);
		$tot = ($time_end - $time_start)/60;
		$exec_time = number_format($tot, 1);
		$status = "success";
		$msg = "Unique cities were successfully plucked to cities_tmp.txt. Total Time: $exec_time minutes.";
	}
}

set_time_limit(30);// restore to default
ini_set('memory_limit','128M');// restore to default
echo json_encode(array("status" => $status,"message" => $msg));
