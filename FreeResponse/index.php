<?php
/**
 * This is the landing page for devices accessing the test.	users will enter a user number 
 * or other information, push submit, and then the server will serve the correct content.
 * This page will delete any cookies that are set because they will be reset according to 
 * the information the user enters on this page.
 *
 * Andrew Catellier 5/25/2011
 * updated Luke Connors 06/20/2012s
 */

// if either of these cookies are set, remove them so they can be properly set by the 
// included javascript.

include 'functions.php';
include 'db_creds.php';
// store the database credentials
$db_credentials = $client_credentials;

if (isset($_COOKIE['session'])) {
	$expire_time = time() - 3601;
	setcookie('session',"",$expire_time);
}
if (isset($_COOKIE['location'])) {
	$expire_time = time() - 3601;
	setcookie('location',"",$expire_time);
}
if (isset($_COOKIE['user_number'])) {
	$expire_time = time() - 3601;
	setcookie('user_number',"",$expire_time);
}
// Test is set up so that the user chooses the session the wish to complete on the
// home screen. Alteratlively, the software can choose a session randomly, or based
// of the order stored in database. If you wish to implement remove the option to 
// select the session on index/php
$query = "SELECT * FROM sessions;";
$result = ask_pg($db_credentials,$query);
while ($myrow = pg_fetch_assoc($result)) {
	$ses_list[] = $myrow['ses_name'];
}
$query = "SELECT * FROM random;" ;
$orders = ask_pg($db_credentials, $query);
$orders= pg_fetch_assoc($orders);
if ($orders['sessions'] == 'ordered') {
	$display_sessions = true;
}
else if ($orders['sessions'] == 'random') {
	$display_sessions = false;
}
else {
	$display_sessions = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, maximum-scale=1.0, user-scalable=0"/> 
<meta name="apple-mobile-web-app-capable" content="yes" /> 
<meta name="apple-mobile-web-app-status-bar-style" content="black" />
<title>Welcome</title>
<link rel="stylesheet" href="west.css" />
</head>

<body>

<h1 id="greeting"> Welcome to the test! Please enter your user number.</h1>
<!-- ask for user number and location, submit info to view_vote.php -->
<form name="userinfo" action="set_cookies.php" method="post" id="userinfo">
<!-- todo: make CSS/javascript that will notify the user of an incorrect input -->
<input type="number" min="1" pattern="^[0-9]+$" name="usernumber" placeholder="User Number" required>
<!-- this code creates the selecter for the session the user wishes to complete -->

<?php
if ($display_sessions === true || $display_sessions === null) {
	echo "<select name=\"session\">";
	foreach($ses_list as $key => $value) {
		echo "<option value=\"" . $value . "\">" . $value . "</option>";
	}
	echo "</select>";
}
?>
<select name="location">
<option value="Lab">Lab</option>
<option value="Home">Home</option>
</select>

<input type="submit" name="infosubmitter" value="submit">
</form>

</body>
</html>
