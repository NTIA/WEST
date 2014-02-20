<?php
/**
 * This page is what shows up when a person has finished viewing all videos meant for one
 * device. First the file checks that the cookies for session, user_number, and location
 * set.  If the necessary cookies are not set, the user is redirected to index.php.  Next
 * this file checks if the user is done with their current session.  If the user has not 
 * completed the current session, they will be redirected to functions.php inorder to do 
 * so. Next this file checks to see if the user is done with all sessions.  If the user is
 * done with all session, they will be notified.  If the user still has sessions to 
 * complete they will be notified how many session they still need to complete.
 *
 * andrew catellier 02/10/2011
 * updated Luke Connors 06/20/2012
*/

// include all the database interface functions
include 'functions.php';
include 'db_creds.php';
// store the database credentials
$db_credentials = $client_credentials;
// figure out which user is accessing the page and where the user is

if (isset($_COOKIE['location']) && isset($_COOKIE['user_number'])) {
	$user_number = intval($_COOKIE['user_number']);
	$location = $_COOKIE['location'];
	if(isset($_COOKIE['session'])) {
		$session = $_COOKIE['session'];
	
		// if the user is not done with current session, redirect to complete
		$finished = user_check_done($db_credentials, $user_number, $session, $location);
		if(!$finished) { 
			header('Location: run_expt.php');
			return;
		}
	}
}
// if cookies are not set, redirect to index.php
else {
	header('Location: index.php');
	return;
}

// check to see if the user is done viewing all devices
$to_finish = user_check_done_global($db_credentials, $user_number, $location);

// check for survey
$query = "SELECT count(*) FROM survey_questions;";
$questions = pg_fetch_assoc(ask_pg($db_credentials,$query));
$questions = $questions['count'];

//destroy the cookies
$expire_time = time() - 3600;
setcookie('session',"",$expire_time);


?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>Finished!</title>
<script type="text/javascript" src="devicedetect.js"></script>
<link rel="stylesheet" href="west.css" />
</head>

<body>
<?php
// if $to_finish is empty,
if(!$to_finish){
	// tell the user to go home.
	echo "<h1>You're finished! Please take survey.</h1>";
	// if survey exists the allow button to go to survey
	if ($questions > 0) {
		echo "<form name=\"to_survey\" action=\"survey.php\" method=\"post\"><input type=\"submit\" name=\"to_survey\"  class=\"submitLink\" value=\"Take Survey\" id=\"subbut\"></form>";
	}	// otherwise
} else {
	// tell the user he/she is finished with this device, and how many devices remain
	echo "<h1>You finished this session! User " . $user_number . " has " . count($to_finish) . " sessions to go in the " . $location . ".</h1>";
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
