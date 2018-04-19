<?php
/**
 * ZodiacPress cities.txt Builder
 *
 * This file is part of "ZodiacPress cities.txt Builder".
 * 
 * "ZodiacPress cities.txt Builder" is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 * 
 * "ZodiacPress cities.txt Builder" is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with "ZodiacPress cities.txt Builder". If not, see <http://www.gnu.org/licenses/>.
 *
 * @author     Isabel Castillo
 * @copyright  2018 Isabel Castillo
 * @license    https://www.gnu.org/licenses/gpl-2.0.html  GNU GPLv2
 */
?>
<!DOCTYPE html>
<html>
<head>
	<title>Toolkit to build ZodiacPress cities.txt</title>
	<script src="toolkit.js"></script>
	<link rel="stylesheet" href="style.css">
</head>
<body>
<div id="main">
<h1>ZodiacPress <code>cities.txt</code> Builder</h1>
<p>This is a toolkit to build the <code>cities.txt</code> data file (which is available <a href="https://download.cosmicplugins.com/" target="_blank">here</a>). The <code>cities.txt</code> file is created from data that is taken from GeoNames export files, and modifed/optimized for use with ZodiacPress.</p>

<div id="working"></div>
<div id="notices"></div>

<?php
ini_set('display_errors',1);// @test

include_once 'includes.php';

$complete = '<span class="okay">&#x2713; Complete</span>';
?>
<form id="import-geonames-form">

	<table>
		<thead>
			<tr>
				<th colspan="3">Complete steps in order</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<?php
				$basename = 'allCountries';
				$filename = 'allCountries.zip';
				?>
				<td id="import-geonames-<?php echo $basename; ?>-control">
					<?php
					// if file exists, and is the right size, show 'Complete' instead of button.
					$file = $dir . '/' . $filename;
					if (file_exists($file) && bzptza_is_filesize_good($filename,$file)) {
						echo $complete;
					} else {
						?>
						<button id="import-geonames-<?php echo $basename; ?>" class="cd-button" value="<?php echo $filename; ?>">Download Data File  <span id="import-geonames-<?php echo $basename; ?>-spinner" class="loading-spinner"></span></button>
						<?php
					}
					?>
				</td>
				<td>
					<span id="import-geonames-<?php echo $basename; ?>label">Download the <code><?php echo $filename; ?></code> data file from GeoNames.  <mark>NOTE: this can take up to 2 minutes to complete.</mark></span>
				</td>
			</tr>

		<?php

		/* List of controls: id, completion file, button text, description */
		$function_controls = array(
			array('unzip','allCountries.txt','Unzip File','Unzip <code>allCountries.zip</code>. NOTE: this can take about <mark>20 seconds</mark>.'),
			array('pluck','cities_tmp.txt','Pluck Cities','Pluck only cities (& towns, villages...) from <code>allCountries.txt</code>. <mark>NOTE: this can take about 3 minutes to complete.</mark>'),
			array('mapcodes','cities.txt','Replace Codes','Replace codes for country and region with their real names. NOTE: this can take <mark>up to 3 minutes</mark>.'),
			array('checkupdate','cities_updates.txt','Check For Updates','Check allCountries.txt for updates.')
		);

		$build_controls = array_slice($function_controls, 0, 3);

		foreach($build_controls as $v) {
			?>
			<tr>
				<td id="<?php echo $v[0]; ?>-control">

					<?php 
					// if the completion file for this task exists, show 'Complete' instead of button.
					if (file_exists($dir . '/' . $v[1])) {
						echo $complete;
					} else {
						?>
						<button id="<?php echo $v[0]; ?>" class="cd-button" name="<?php echo $v[0]; ?>" value="<?php echo $v[0]; ?>"><?php echo $v[2]; ?> <span id="<?php echo $v[0]; ?>-spinner" class="loading-spinner"></span></button>
						<?php	
					}
					?>

				</td>

				<td>
					<span id="<?php echo $v[0]; ?>-label"><?php echo $v[3]; ?></span>
				</td>
			</tr>
			<?php

		}
		?>
		</tbody>

	</table>
	
</form>

<table id="checkupdates">
		<thead>
			<tr>
				<th colspan="3">Check for updates</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<?php
				$updates_control = $function_controls[3];

				?>
				<td id="<?php echo $updates_control[0]; ?>-control">

					<?php 
					// if the completion file for this task exists, show 'Complete' instead of button.
					if (file_exists($dir . '/' . $updates_control[1])) {
						echo $complete;
					} else {
						?>
						<button id="<?php echo $updates_control[0]; ?>" class="cd-button" name="<?php echo $updates_control[0]; ?>" value="<?php echo $updates_control[0]; ?>"><?php echo $updates_control[2]; ?> <span id="<?php echo $updates_control[0]; ?>-spinner" class="loading-spinner"></span></button>
						<?php	
					}
					?>

				</td>

				<td>
					<span id="<?php echo $updates_control[0]; ?>-label"><?php echo $updates_control[3]; ?></span>
				</td>
			</tr>
		</tbody>
</table>


</div>

</body>
</html>

