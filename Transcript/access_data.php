<?php
/**
 * this page will get information and statistics out of the database and present it in table form.
 * the cases are mostly documented already, but there are some gnarly database tricks in there
 * in order to get the right data out. data analysis is the hardest, especially if you aren't good
 * at designing the structures to store all the data in the first place.
 * 
 * Andrew Catellier 
 * updated Luke Connors, 10/27/12
 */

include 'functions.php';
include 'db_creds.php';
// store the database credentials
$db_credentials = $client_credentials;
// if the form was submitted
if (isset($_POST['db_submitter'])){
	// store the request in a variable
	$request = $_POST['request'];
	// then figure out what was requested.
	switch ($request){
		// this case will display the data from the votes table into multiple tables
		// one for each viewo
		case "by video/position":
			$query = "SELECT src, vi, ai, position FROM votes GROUP BY src, vi, ai, position ORDER BY src, position;";
			$result = ask_pg($db_credentials,$query);
			$title = "<p>Answers organized by video and position:</p>";
			$case = "vidbypos";
			$explanation = "<p>This displays the answers organized by it's video and position.</p>";
			break;
		// this case will display the raw data from the votes table
		case "votes table":
			$query = "SELECT * FROM votes";
			$result = ask_pg($db_credentials,$query);
			$title = "Votes Table";
			$explanation = "<p>This is the votes table stored in the database.</p>";
			break;
		// this case will display the data from the survey_response table organized
		// by question
		case "survey table by question":
			$query = "SELECT question, response, user_number FROM survey_response GROUP BY question, response, user_number ORDER BY question;";
			$result = ask_pg($db_credentials,$query);
			$title = "<p>Survey answers organized by question:</p>";
			$explanation = "<p>This displays the suvey answers organized by survey question.</p>";
			break;
		// this case will display the raw data from the suurvey_response table
		case "survey table":
			$query = "SELECT * FROM survey_response";
			$result = ask_pg($db_credentials,$query);
			$title = "Survey Table";
			$explanation = "<p>This is the survey table stored in the database.</p>";
			break;
		default:
			echo "No action performed.<br>";
	}
	// if outtype was specified in the form
	if (isset($_POST['outtype'])){
		// store the value in a variable
		$selected_radio = $_POST['outtype'];
		// then figure out which out type was 
	   switch($selected_radio){
	   	case "htmlout":
	   		// make an HTML table
	   		
	   		if($case == "vidbypos") {
	   			// for this case (by video/position) multiple tables need to be displayed 
	   			// representing the statistics for each video
	   			echo $title;
	   			$title = "";

	   			if (!$result) {
	   				echo "requested database table is empty";
	   				break;
	   			}

	   			// results has the list of file tags for the videos. for each video, a 
	   			// table is displayed with the responses for each position in the transcript
	   			while ($myrow = pg_fetch_assoc($result)) {
					$filetag = "";
					$filetag = $myrow['src'] . $myrow['vi'] . $myrow['ai'];
					// get the question for the video from the database
					$query = "SELECT * FROM questions WHERE filetag = '" . $filetag . "';";
					$result1 = ask_pg($db_credentials,$query);
					$q_after = pg_fetch_assoc($result1);
					echo "Video: " . $filetag . "<br>";
					echo $q_after['q_after'] . "<br>";
					// get the responses for the video from the database
					$query = "SELECT position, answer, user_response FROM votes WHERE src = '" . $myrow['src'] . "' AND vi = '" . $myrow['vi'] . "' AND ai = '" . $myrow['ai'] . "' ORDER BY position, user_response;";
					$result2 = ask_pg($db_credentials,$query);
					$explanation = "";
					dbtable_disp($result2, $title, $explanation);
					echo "<br>";
				}
	   		}
	   		// this case does not involve displaying multiple tables
	   		else dbtable_disp($result,$title,$explanation);
	   		break;
	   	case "csvout":
	   		if($case == "vidbypos") {
	   			// for this case (by video/position) multiple tables need to be written to
	   			// the csv file representing the statistics for each video
	   			
	   			if (!$result) {
	   				echo "requested database table is empty";
	   				break;
	   			}

	   			// get the timezone and create a unique csv file name with a time stamp
	   			date_default_timezone_set('America/Denver');
				$date = date_create($row[0]);
				$time_stamp = date_format($date, 'Y-m-d H.i.s');
				$filename = $_SERVER['DOCUMENT_ROOT'].'\\csv\\' . $time_stamp . '.csv';	   			
			
	   			$handle = fopen($filename, 'w');
	   			
	   			$title = "";
	   			// results has the list of file tags for the videos. for each video, a 
	   			// table is written to the csv file with the responses for each position 
	   			// in the transcript
	   			while ($myrow = pg_fetch_assoc($result)) {
					$filetag = "";
					$filetag = $myrow['src'] . $myrow['vi'] . $myrow['ai'];
					$query = "SELECT * FROM questions WHERE filetag = '" . $filetag . "';";
					$result1 = ask_pg($db_credentials,$query);
					$q_after = pg_fetch_assoc($result1);
					
					$title = null;
					$title[] = "Video: " . $filetag;
					$title[] = $q_after['q_after'];
					
					$query = "SELECT position, answer, user_response FROM votes WHERE src = '" . $myrow['src'] . "' AND vi = '" . $myrow['vi'] . "' AND ai = '" . $myrow['ai'] . "' ORDER BY position, user_response;";
					$result2 = ask_pg($db_credentials,$query);
					read_to_csv($handle, $result2, $title);
				}
				
				fclose($handle);
		
				header('Content-Description: File Transfer');
			    header('Content-Type: application/octet-stream');
	    		header('Content-Disposition: attachment; filename='.basename($filename));
		    	header('Content-Transfer-Encoding: binary');
			    header('Expires: 0');
		    	header('Cache-Control: must-revalidate');
			    header('Pragma: public');
		    	header('Content-Length: ' . filesize($filename));
		    	ob_clean();
		    	flush();
	    		readfile($filename);
	    		
	    		unlink($filename);
	   		}
	   		else dbtable_csv($result,$title);
	   		break;
	   }
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>graph scratch</title>
<script type="text/javascript" src="raphael.js"></script>

</head>

<body>
<!-- dropdown list of available data dumps -->
<form name="db_read" action="access_data.php" method="post">
<select name="request">
<option value="votes table">votes table</option>
<option value="by video/position">answers by video/position</option>
<option value="survey table">survey table</option>
<option value="survey table by question">survey table by question</option>
</select>
<input type="submit" name="db_submitter" value="submit">
<!-- specify output type -->
<fieldset class="radios">
<label class="label_radio" for="htmlout">
<input type="radio" id="htmlout" name="outtype" value="htmlout" checked >html table output
</label>
<label class="label_radio" for="csvout">
<input type="radio" id="csvout" name="outtype" value="csvout" unchecked >csv file output
</label>
</label>
</fieldset>

</form>
</script>
</body>
</html>
