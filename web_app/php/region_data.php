<?php

//
//	Copyright (c) 2014-2015, Emory University
//	All rights reserved.
//
//	Redistribution and use in source and binary forms, with or without modification, are
//	permitted provided that the following conditions are met:
//
//	1. Redistributions of source code must retain the above copyright notice, this list of
//	conditions and the following disclaimer.
//
//	2. Redistributions in binary form must reproduce the above copyright notice, this list
// 	of conditions and the following disclaimer in the documentation and/or other materials
//	provided with the distribution.
//
//	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY
//	EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
//	OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT
//	SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
//	INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
//	TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR
//	BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
//	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY
//	WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
//	DAMAGE.
//
//

	require '../db/accounts.php';
	require 'hostspecs.php';

	$datasetName = $_POST['datasetName'];
	$projectName = $_POST['projectName'];
	$datasetFile = $_POST['datasetSel'];
	$datasetPath = '../../userdata/'.$datasetFile;
	$linkPath = '../../../../../localdata/datasets/'.$projectName.'/'.$datasetFile;

	$boundaryDir = $_POST['boundarySel'];
	$boundaryPath = '../userdata/'.$boundaryDir;
	$slideListPath = '../userdata/'.$_POST['slideSel'];

	// should be changed into ../userdata/
	$boundaryFile = 'boundarieZ.txt';
	$pathToboundaryFile = '../userdata/'.$boundaryFile;
	$pyramidSel = $_POST['pyramidSel'];
	$pathTopyramidsInfo = '../userdata/'.$pyramidSel;

	// empty check for project name
	if( empty($projectName) ) {
		echo "<script type='text/javascript'>window.alert('Project name is empty !! ');
		window.location.href = '../region_data.html';</script>";
		exit;
	}
	// empty check for dataset name
	if( empty($datasetName) ) {
		echo "<script type='text/javascript'>window.alert('Dataset name is empty !! ');
		window.location.href = '../region_data.html';</script>";
		exit;
	}

	/************	Start existing dataset and slide name check ************/
	// dataset name check

	$dbConn = mysqli_connect("localhost", $guestAccount, $guestPass, "nuclei");

	if( !$dbConn ) {
		echo("<p>Unable to connect to the database server</p>" . mysqli_connect_error() );
		exit;
	}

	$sql = 'SELECT name FROM datasets WHERE name="'.$datasetName.'"';

	if( $result = mysqli_query($dbConn, $sql) ) {
		while( $array = mysqli_fetch_row($result) ) {
			$isDatasetName = $array[0];
		}
		mysqli_free_result($result);
	}

	mysqli_close($dbConn);

	// check if dataset name already exists.
	if(isset($isDatasetName)) {
		echo "<script type='text/javascript'>window.alert('Dataset: $isDatasetName already exists !! ');
		window.location.href = '../region_data.html';</script>";
		exit;
	}


	if( file_exists($boundaryPath) ) {

		/************	Start checking and removing slide name for boundaries ************/

		// dataset name check

		$dbConn = mysqli_connect("localhost", $guestAccount, $guestPass, "nuclei");
		if( !$dbConn ) {
			echo("<p>Unable to connect to the database server</p>" . mysqli_connect_error() );
			exit;
		}
		// slide name check
		$sql = 'SELECT DISTINCT slide FROM sregionboundaries';
		$slideArray = array();
		if( $result = mysqli_query($dbConn, $sql) ) {
			// read file
			$file = fopen($slideListPath,'r');
			while( $array = mysqli_fetch_row($result) ) {
					while ($line = fgets($file)) {
					if( strcmp($array, $line) == 0){
						$line = explode("\n", $line);
						$slideArray[] = $line[0];
					}
				}
			}
			fclose($file);
			mysqli_free_result($result);
		}
		mysqli_close($dbConn);

		// remove duplicated slides from sregionboundaries
		// dataset name check

		$dbConn = mysqli_connect("localhost", $guestAccount, $guestPass, "nuclei");
		if( !$dbConn ) {
			echo("<p>Unable to connect to the database server</p>" . mysqli_connect_error() );
			exit;
		}

		foreach ($slideArray as $slide) {
			$sql = 'DELETE FROM sregionboundaries WHERE slide="'.$slide.'"';
			if( $result = mysqli_query($dbConn, $sql) ) {
					mysqli_free_result($result);
			}
		}

		mysqli_close($dbConn);

		/************	End checking and removing slide name for boundaries ************/

		/************	Start boundary file generating ************/
		// check existing boundary file

		if( file_exists($pathToboundaryFile) ) {
			$cmd = 'rm '.$pathToboundaryFile;
			exec($cmd, $output, $result);
		}

		// create a new boundary file
		$cmd = 'cd ../scripts && '.'./boundaryExtractforingestion.sh '.$boundaryPath.' '.$pathToboundaryFile;
		exec($cmd, $output, $result);

		if( $result != 0 ) {
			echo "<script type='text/javascript'>window.alert('Boundaries: Cannot extract boundaries from $boundaryDir !! ');
			window.location.href = '../region_data.html';</script>";
			exit;
		}

		/************	End boundary file generating ************/


		/************	Start boundary file importing ************/

		$link = mysqli_init();
		mysqli_options($link, MYSQLI_OPT_LOCAL_INFILE, true);
		mysqli_real_connect($link, "localhost", $guestAccount, $guestPass, "nuclei");

		$sql = 'LOAD DATA LOCAL INFILE "'.$pathToboundaryFile.'"
			INTO TABLE sregionboundaries fields terminated by \'\t\'
			lines terminated by \'\n\'	(slide, centroid_x, centroid_y, boundary)';

		if( $result = mysqli_query($link, $sql) ) {
			mysqli_free_result($result);
		}

		mysqli_close($link);

		/************	End boundary file importing ************/

		if( $_POST['slideInfo'] == 'yes' ) {

				/************	Start existing slide name check ************/
				$link = mysqli_init();
				mysqli_options($link, MYSQLI_OPT_LOCAL_INFILE, true);
				mysqli_real_connect($link, "localhost", $guestAccount, $guestPass, "nuclei");

				$sql = 'SELECT name, id FROM slides';
				if( $result = mysqli_query($link, $sql) ) {
					// read csv file
					//$file = fopen($pathTopyramidsInfo,'r');
					$lines = file($slideListPath, FILE_IGNORE_NEW_LINES);
					foreach ($lines as $line)
					{
					  $slidename = explode('\n', $line);

						while( $array = mysqli_fetch_row($result) ) {
						  if ($slidename[0]==$array[0]){
								echo "<script type='text/javascript'>window.alert('Slides: $slidename[0] already exist !! ');
								window.location.href = '../region_data.html';</script>";
								exit;

							}
						}
					}
					mysqli_free_result($result);
				}
				else {
					echo "<script type='text/javascript'>window.alert('Slides: Cannot retrieve slide names from database !! ');
					window.location.href = '../region_data.html';</script>";
					exit;
				}

				/************	End existing slide name check ************/

				/************	Start slide information importing ************/
				$link = mysqli_init();
				mysqli_options($link, MYSQLI_OPT_LOCAL_INFILE, true);
				mysqli_real_connect($link, "localhost", $guestAccount, $guestPass, "nuclei");

				$sql = 'LOAD DATA LOCAL INFILE "'.$pathTopyramidsInfo.'"
						INTO TABLE slides fields terminated by \',\' lines
						terminated by \'\n\' (name, x_size, y_size, pyramid_path, scale)';

				if( $result = mysqli_query($link, $sql) ) {
					mysqli_free_result($result);
				}
				else{
					echo "<script type='text/javascript'>window.alert('Boundaries: Cannot import slide information to database !! ');
					window.location.href = '../region_data.html';</script>";
					exit;
				}
				mysqli_close($link);
				/************	End slide information importing ************/
		}

		/************	Start dataset importing************/

		// create a project directory if project name does not exist
		if( !file_exists('../../../../../localdata/datasets/'.$projectName) ) {
			$cmd = 'mkdir '.'../../../../../localdata/datasets/'.$projectName;
			exec($cmd, $output, $result);
		}

		// check if dataset link already exists
		if( file_exists($linkPath) ) {
			// remove dataset link
			$cmd = 'rm '.$linkPath;
			exec($cmd, $output, $result);
		}

		// link datasetFile to /localdata/datasets/
		$cmd = 'ln -s '.$datasetPath.' '.$linkPath;
		exec($cmd, $output, $result);


		// add datasets and dataset_slides tables
		$result = shell_exec('python ../scripts/create_dataset.py guest '.
				escapeshellarg($datasetName) . ' '.
				escapeshellarg($projectName.'/'.$datasetFile) .' '.
				escapeshellarg($slideListPath));

		if( $result != 0 ) {
			echo "<script type='text/javascript'>window.alert('Dataset: Cannot import dataset to database !! ');
			window.location.href = '../region_data.html';</script>";
			exit;
		}
		else{
			echo "<script type='text/javascript'>window.alert('Data import is completed !! ');
			window.location.href = '../region_index.html';</script>";
			exit;
		}
		/************	End dataset importing************/

	}
?>
