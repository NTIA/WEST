<?php
/**
 * The purpose of this file is to store the all the cookies that will be used later when
 * running the experiment.  This file is designed to succeed index.php and precede either
 * run_expt.php or finished.php depending on whether or not the user is done with the 
 * experiment.
 * First this file checks if the cookies are all set. If the cookies are set and the user
 * is done with the current session, the user will be redirected to finished.php. If the 
 * cookies are set and the user is not done with the current session, the user will be 
 * redirected to run_expt.php. If the cookies are not set, this file tries to find the
 * the information that the user input on the index.php page. If this info is not found, 
 * the user will be redirected to index.php inorder to enter the necessary info. If the 
 * info from index.php is found, cookies will be set for user_number, location, and
 * session based of of the info from index.php. 
 * If the session is selected by the user in index.php, it will run that session.
 * Alternatively, a session for the user to complete is chosen based off of the following 
 * rules:
 * 		1) If there is a practice session that has not been completed, that session must 
 *			always be completed first.
 *		2) If there are any sessions that are partially complete, those session must be
 *			completed before starting a new session.
 *		3) If there are no sessions to complete that fall in the first two categories, an
 *			incomplete session must be chosen randomly to start.
 *		4) If the user has completed all sessions, they are redirected to finished.php
 * In the case that a user has not been initiated yet, they are initiated by creating a 
 * unique, random order to watch the videos in each session and then are added to the users
 * table. Then a session is chosen for that user to start.
 * After all cookies have been set for a user, they are redirected to the run_expt.php
 * page 
 * 
 * Luke Connors 06/18/2011
 */

// include all the functions defined
include 'functions.php';
include 'db_creds.php';
// store the database credentials
$db_credentials = $client_credentials;

//check to see if the session, user_number, and location cookies are set
if( isset($_COOKIE['session']) && isset($_COOKIE['user_number']) && 
	isset($_COOKIE['location'])) {
	$user_number = $_COOKIE['user_number'];
	$session = $_COOKIE['session'];
	$location = $_COOKIE['location'];
	
	// If user is done with the current session, send them to finished.php
	if(user_check_done($db_credentials, $user_number, $session, $location)) {
		header('Location: finished.php');
		return;
	}
	
	// If user is not done with current session, send the m to run_expt.php to finish 
	// session
	header('Location: run_expt.php');
	return;

}

// check to see if the index page has submitted the proper information, if so save 
// information into cookies
if (isset($_POST['infosubmitter'])) {
	//set a cookie to expire in a little over an hour
	$expire_time = time() + 3601;
		
	$user_number = $_POST['usernumber'];
	//make sure the submitted data is in the correct format, to prevent
	//database corruption
	if (!preg_match('/^[0-9]+$/',$user_number)) {
		header('Location: index.php');
		return;
	}
	setcookie('user_number',$user_number,$expire_time);
	
	$location = $_POST['location'];
	//make sure the submitted data is in the correct format, to prevent
	//database corruption
	// apparently you can't specify something like {,20}
	if (!preg_match('/^[a-zA-Z0-9 ]{1,20}+$/',$location)) {
		header('Location: index.php');
		return;
	}
	setcookie('location',$location,$expire_time);
	
	$query = "SELECT * FROM random;" ;
	$orders = ask_pg($db_credentials, $query);
	$orders= pg_fetch_assoc($orders);
		
	// check if the user has started the experiment yet, if not create a random order for
	// the user to watch the videos from each session
	if (!(user_check_exist($db_credentials,$user_number,$location))) {
		if ($orders['videos'] == 'ordered') {
			make_ordered_file_sequence($db_credentials,$user_number,$location);
		}
		else if ($orders['videos'] == 'random') {
			make_random_file_sequence($db_credentials,$user_number,$location);
		}
	}
	// set a cookie specifying whether we should be displaying videos or not
	if ($orders['tally'] == 'f') {
		setcookie('tally', 'FALSE', $expire_time);
	}
	else {
		setcookie('tally', 'TRUE', $expire_time);
	}
	
	// In this case index.php is set up so that the user selects the session on that page
	if ($_POST['session']) {
		$session = $_POST['session'];
		setcookie('session', $session ,$expire_time);
	}
	// In this case index.php is NOT set up for the user to select a session on that page
	else {
		$session = 'not_set';		// used to find random session if there are no incomplete sessions
		$continue = FALSE;			// used in html to figure out if starting a new sessions or continuing a previous session
		$to_finish = user_check_done_global($db_credentials, $user_number, $location);
		
		// if user has finished all sessions, send to finished.php
		if (!$to_finish) {
			header('Location: finished.php');
	  		break;
		}	
		// if there is a practice session that needs to be finished, always complete that 
		// session first
		if (in_array('practice', $to_finish)) {
			$session = 'practice'; 
			setcookie('session', $session, $expire_time);
		}
		else if (in_array('Practice', $to_finish)) {
			$session = 'Practice'; 
			setcookie('session', $session, $expire_time);
		}
		
		// this case is reached if there is no practice session to check for any sessions that
		// that are only partially completed, the user needs to complete those sessions first
		else {
			foreach($to_finish as $value) {
				$query = "SELECT count(*) FROM votes WHERE user_number = " . $user_number . " AND location = '" . $location .  "' AND ses = '" . $value . "';" ;
				$completed = ask_pg($db_credentials, $query);
				$completed = pg_fetch_assoc($completed);
				// if there exists votes for current user in current location for a session
				// in the array $to_finish, this session is partially completed
				if ($completed['count'] > 0) {
					$session = $value;
					$continue = TRUE;
					setcookie('session', $session, $expire_time);
					break;
				}
			}
		}
			
		// if session cookie has not been yet (there are no partially completed sessions, 
		// choose a random session from the remaning sessions to be completed
		if ($session == 'not_set') {
			// check to see if sessions are supposed to be ordered or random
			if($orders['sessions'] == 'ordered') {
				$session = $to_finish[0];
			}
			// if sessions are supposed to be random
			else if($orders['sessions'] == 'random') {
				// generate random index
				$size = count($to_finish);
				srand($size * $user_number * time());
				$ses_index = rand(0, $size - 1);
				// use random index to pick a session
				$session = $to_finish[$ses_index];
			}
			setcookie('session', $session ,$expire_time);
		}
	}
}

// if no cookies and no infosubmitter from last page, redirect to index.php
else {
	header('Location: index.php');
	return;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta http-equiv="refresh" content="3;run_expt.php">
<title>Session</title>
<link rel="stylesheet" href="west.css" />
</head>

<body>
<?php
if (($session == 'practice') || ($session == 'Practice')) {
	if ($continue) {
		echo "<h1>Now continuing " . $session . " session. </h1>";
	}
	else {
		echo "<h1>Now starting " . $session . " session. </h1>";
	}
}
else {
	if ($continue) {
		echo "<h1>Now continuing previous session. </h1>";
	}
	else {
		echo "<h1>Now starting new session. </h1>";
	}
}

?>
<script type="text/javascript">
// when the window loads, prevent scrolling on iOS devices.
window.onload = function(){
		document.addEventListener('touchmove',function(event){
			event.preventDefault();
		},false);};
</script>
</body>
</html>
