<?php
/**
 * Handles the ajax request to unzip allCountries.zip
 */
$clear = isset($_GET['v']) ? $_GET['v'] : '';
if ('unzip' !== $clear) {
	echo json_encode(array("status" => "error","message" => "error"));
	exit;
}
set_time_limit(360);
ini_set('memory_limit', '-1');
include_once '../includes.php';
$text_file = $dir . '/allCountries.txt';
$zip_file = $dir . '/allCountries.zip';
if (file_exists($text_file )) {
	$status = "info";
	$msg = "The file was already unzipped.";
} else {
	$des = $dir . '/';
	ob_start();
	system("unzip -d $des $zip_file",$ret);
	$result = ob_get_clean();
	if (0 === $ret || 1 === $ret) {
		$status = "success";
		$msg = "The file '$text_file' was successfully extracted.";
	} else {
		$unzip_errors = array(
			// 1 => 'one or more warning errors were encountered. Some files may have been skipped.',
			2 => 'a generic error in the zipfile format was detected.',
			3 => 'a severe error in the zipfile format was detected.',
			4 => 'unzip was unable to allocate memory for one or more buffers during program initialization.',
			5 => 'unzip was unable to allocate memory or unable to obtain a tty to read the decryption password(s).',
			6 => 'unzip was unable to allocate memory during decompression to disk.',
			7 => 'unzip was unable to allocate memory during in-memory decompression.',
			8 => '[currently not used]',
			9 => 'the specified zipfiles were not found.',
			10 => 'invalid options were specified on the command line.',
			11 => 'no matching files were found.',
			50 => 'the disk is (or was) full during extraction.',
			51 => 'the end of the ZIP archive was encountered prematurely.',
			80 => 'the user aborted unzip prematurely with control-C (or similar)',
			81 => 'testing or extraction of one or more files failed due to unsupported compression methods or unsupported decryption.',
			82 => 'no files were found due to bad decryption password(s).'
		);
		$status = "error";
		$msg = "unzip failed because: $unzip_errors[$ret]";
	}
}
set_time_limit(30);// restore to default
ini_set('memory_limit','128M');// restore to default
echo json_encode(array("status" => $status,"message" => $msg));
