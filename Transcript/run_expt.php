<?php
/**
 * The purpose of this file is to show the user videos from a specific sessions and 
 * collect and record votes on the videos.
 * The first thing this file does is check if the cookies for session, user_number, and 
 * location.  If the necessary cookies are not set, the user will be redirected to 
 * index.php.  Next this file checks to see if the user has watched all the videos in the 
 * current session.  If the user has completed the currents session, they are redirected 
 * to finished.php.  
 *
 * Luke Connors 06/20/2012
 * modified by andrew catellier, 08/30/2013
 */
// include all the functions defined
include 'functions.php';
include 'db_creds.php';
// store the database credentials
$client_credentials = $client_credentials;
// check to see if the session, user_number, and location cookies are set
if( isset($_COOKIE['session']) && isset($_COOKIE['user_number']) && 
	isset($_COOKIE['location'])) {
	$user_number = $_COOKIE['user_number'];
	$session = $_COOKIE['session'];
	$location = $_COOKIE['location'];
	$tally = $_COOKIE['tally'];
	
	// If user is done with the current session, send them to finished.php
	$finished = user_check_done($client_credentials, $user_number, $session, $location);
	if ($finished) {
		header('Location: finished.php');
		return;
	}
}
else {
	header('Location: index.php');
	return;
}

// get the list of randomized filenames from the database for this user/session/
// location combination
$fnames = get_fnames($client_credentials, $user_number, $session, $location);
// if a form has been submitted from 'mossubmitter' (meaning a vote has been
// cast) record the vote in the database
if (isset($_POST['mossubmitter'])) {
	// learn the index of the video that should be playing on this iteration
	$fname_index = user_seq_nextval($client_credentials, $user_number, $session, $location);
		
	// get filename info from the previous result in order to correctly enter the vote
	$prev_fname = pg_fetch_assoc($fnames,($fname_index - 1));
	$prev_src = $prev_fname['src'];
	$prev_vi = $prev_fname['vi'];
	$prev_ai = $prev_fname['ai'];
	$filetag = $prev_src .  $prev_vi . $prev_ai;
	// figure out how many answers applied to this filetag
	$query = "SELECT count(*) FROM answers WHERE filetag = '" . $filetag . "';";
	$a_count = ask_pg($client_credentials,$query);
	$a_count = pg_fetch_assoc($a_count);
	// there should be exactly as many responses to this query as there are actual responses
	// in the post.
	// there should always be at least two responses to this query, the first one will be 
	// the pre-video question, which doesn't have a set of answers associated with it
	$a_count = $a_count['count'];
	// Look through everything in the POST except for the actual submit button
	if ($a_count == (count($_POST) - 2)) {
		// first check to see if the file tag that was submitted is the file tag that
		// for which we would be entering scores
		$posted_filetag = $_POST['posted_filetag'];
		if ($posted_filetag == $filetag) {
			// if the filetags match up, and all responses were submitted, loop through them
			// TODO: check to make sure that all fields are set, so we don't submit empty vars
			//		 to the database
			foreach ($_POST as $key => $value) {
				if ($key != 'mossubmitter' && $key != 'posted_filetag') {
					// This will format the string correctly to be put in the database
					$response = pg_escape_string($value);
					$split = preg_split('/_/',$key);
					$qv_no = pg_escape_string($split[1]);
					$position = pg_escape_string($split[2]);
					// now get the answer for this filetag, qv_no, and position --- this 
					// makes way too many database requests, but again, I'm a poor planner
					$query = "SELECT * FROM answers WHERE filetag = '" . $filetag . "' AND qv_no = '" . $qv_no . "' AND position = '" . $position . "';";
					$result = ask_pg($client_credentials,$query);
					$result = pg_fetch_assoc($result);
					$curr_answer = $result['answer'];
					// submit the current response to the database
					$query = "INSERT INTO votes(user_number, qv_no, user_response, answer, location, ses, src, vi, ai, position) VALUES('" . $user_number . "', '" . $qv_no . "', '" . $response . "', '" . $curr_answer . "', '" . $location . "', '" . $session . "', '" . $prev_src . "', '" . $prev_vi . "', '" . $prev_ai . "', '" . $position . "');";
					ask_pg($client_credentials,$query);
				}
			}
		}
		// if the filetags don't match up, that means that someone pushed a reload button.
		// the browser is submitting old data (data that has already been entered into the 
		// database), and in order to prevent skipping a stimulus, we change $fname_index
		// to the value of user_seq_currval.
		else {
			$fname_index = user_seq_currval($client_credentials, $user_number, $session, $location);
		}
	}
	// if not all answers have been submitted, it's just like if no answers were submitted
	else {
		$fname_index = user_seq_currval($client_credentials, $user_number, $session, $location);
	}
}
// if the form has not been submitted,
else {

	// learn the index of the video that should be playing on this iteration
	// currval is used here because the function will return the actual number of 
	// votes already entered, and indexing in php starts at 0. so it's returning
	// the current number of votes, but the next index to be voted on.
   	$fname_index = user_seq_currval($client_credentials, $user_number, $session, $location);
}

// find the total number of videos (filsenames) in the session
$fnames_rows = pg_num_rows($fnames);

// here, check to ensure that $fname_index < $fnames_rows. if 
// $fname_index >= $fnames_rows, redirect to the "finished" page,
// mark the user as "finished" in the user table
if ($fname_index >= $fnames_rows){
  	user_set_done($client_credentials, $user_number, $session, $location);
  	header('Location: finished.php');
  	break;
}
  			

//get current filename in order to pull the correct video to display now
$curr_fname = pg_fetch_assoc($fnames,($fname_index));
$src = $curr_fname['src'];
$vi = $curr_fname['vi'];
$ai = $curr_fname['ai'];
$curr_fname_expanded = $src .  $vi . $ai . ".mp4";
$filetag = $src .  $vi . $ai;

// Form a query to get questions for current video
$query = "SELECT * FROM questions WHERE filetag = '" . $filetag . "';";
$questions = ask_pg($client_credentials,$query);

// store questions into an array to create...questions in html
while ($this_question = pg_fetch_assoc($questions)) {
	$q_list[$this_question['qv_no']] = $this_question['question'];
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, maximum-scale=1.0, user-scalable=0"/>
<meta name="apple-mobile-web-app-capable" content="yes" /> 
<meta name="apple-mobile-web-app-status-bar-style" content="black" />
<title>View</title>
<link rel="stylesheet" href="west.css" />
<script type="text/javascript" src="west.js">
</script>
</head>

<body>
<?php
if ($tally=="TRUE"){
	echo "<p class=\"tally\">Clip Number " . ($fname_index + 1) . "</p>";
	echo '<!--';
}
?>
<video preload="auto" webkit-playsinline id="video"/>
	<source src="video/<?php echo $curr_fname_expanded; ?>" type="video/mp4" />
</video>
<?php
if ($tally=="TRUE"){echo '-->';}
?>
<button class="goToVote" name="tovote" id="to_vote" value="Ready to Vote" onclick = "goToVote()">Ready To Vote</button>

<div class="vote" id="divmosform">
<form name="mos" id="mosform" action="run_expt.php" method="post">
<?php

foreach ($q_list as $qkey => $qvalue) {
	if ($qkey != 0) {
		$this_q = preg_split('/[\[\]]/', $qvalue);
		echo "<h1>";
		foreach($this_q as $key => $value) {
			if (is_numeric($value)) {
				echo "<input type=\"text\" name=\"userresponse_" . $qkey . "_" . $value . "\" size=\"15\" required=\"required\" style=\"font-size: 20px\">";
			}
			else {
				echo $value;
			}
		}
		echo "</h1>";
	}
}
echo "<input type=\"hidden\" name=\"posted_filetag\" value=\"" . $filetag . "\">";
?>
<input class="submitLink" type="submit" name="mossubmitter" value="Submit Answer" id="subbut">
</form>
</div>

<div class="vote" id="divquestion">
<h1><?php echo $q_list[0]; ?></h1>
<button class="watchVid" name="tovid" id="to_vid" value="Ready View Video" onclick = "goToVid()">Begin Video</button>
</div>

</body>
</html>
