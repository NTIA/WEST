<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>User Status</title>
<link rel="stylesheet" href="west.css" />

</head>

<body>
<?php 
// this page will report the completion status for the most current user.
//
// andrew catellier 6/20/2011

// include all the functions needed to interact with the database
include 'functions.php';
include 'db_creds.php';
// store the database credentials
$db_credentials = $client_credentials;

// this is a great place to set this.
date_default_timezone_set('America/Denver');
// if a status request is received, 
if (isset($_POST['userstatus_submitter'])){
	// store the request
	$request = $_POST['request'];
	// then figure out what to do with it.
	switch ($request){
		// if the current user status is selected
		case "current user status":
			// get info about the most recent vote from the server
			$query = "SELECT * FROM votes WHERE vote_id = (SELECT max(vote_id) FROM votes);";
			$last_row = ask_pg($db_credentials,$query);
			$last_row = pg_fetch_assoc($last_row);
			$current_user = $last_row['user_number'];
			if (!$current_user) {
				echo "<p>No votes submitted yet.</p>";
				break;
			}
			$current_session = $last_row['ses'];
			// current time could arguably mean the time of the current vote, but 
			// i think the actual, current time provides more meaningful results
			//$current_time = strtotime($last_row['time_stamp']);
			$current_time = time();
			$current_location = $last_row['location'];
			// since we have to account for the possibility of multiple questions per trial,
			// we can't count on vote_id arithmetic to tell us how many trials have been 
			// completed.
			$query = "SELECT count(DISTINCT src) FROM votes WHERE user_number = " . $current_user . " AND location = '" . $current_location . "' AND ses = '" . $current_session . "';";
			$total_trials = ask_pg($db_credentials,$query);
			$total_trials = pg_fetch_assoc($total_trials);
			$current_trial = $total_trials['count'];
			// get info about the first vote (from this user, on this device, in this location) from the server
			$query = "SELECT * FROM votes WHERE user_number = " . $current_user . " AND ses = '" . $current_session . "' AND location = '" . $current_location . "' AND qv_no = 1 LIMIT 1;";
			$first_vote = ask_pg($db_credentials,$query);
			$first_vote = pg_fetch_assoc($first_vote);
			$first_trial = $first_vote['vote_id'];
			$first_time = strtotime($first_vote['time_stamp']);
			// ok, now get info about how long this session actually is
			$query = "SELECT count(*) FROM filenames WHERE ses = '" . $current_session . "';";
			$result = ask_pg($db_credentials,$query);
			$num_trials = pg_fetch_assoc($result);
			$num_trials = $num_trials['count'];
			//start at one less than the total number of videos per device because of the way counting works
			$remaining = $num_trials - $current_trial;
			// display the number of trials remaining and the amount of time elapsed
			echo "<p>User " . $current_user . ", in the " . $current_location . ", has " . $remaining . " trials remaining in session " . $current_session . "</p>";
			echo "<p>" . ($current_time - $first_time) / 60 . " minutes elapsed </p>";
			break;
		default:
			echo "No action performed.<br>";
	}
}
?>
<form name="user_status" action="user_status.php" method="post">
<select name="request">
<option value="current user status">current user status</option>
</select>
<input type="submit" name="userstatus_submitter" value="submit">
</form>

</body>
</html>
