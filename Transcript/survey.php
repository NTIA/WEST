<?php
/**
 * The purpose of this file is to prompt the user with a survey they must complete and then 
 * store the information from the user responses into the database.  The information on
 * how the survey should be set up/what questions should go on the survey are the 
 * survey_questions and survey_answers data tables of the database.  Once this information
 * is extracted fromt the database, it is used to set up the html for the survey form.  
 * After the form is submitted, the pager reloads and records the user responses to the 
 * data base.
 *
 * Luke Connors 08/20/2012
 */

// include all the functions defined
include 'functions.php';
include 'db_creds.php';
// store the database credentials
$db_credentials = $client_credentials;

// check to see if the session, user_number, and location cookies are set
if(isset($_COOKIE['user_number']) && isset($_COOKIE['location'])) {
	$user_number = $_COOKIE['user_number'];
	$location = $_COOKIE['location'];
	
	// If user is not done with all sessions, send them to index.php
	$to_finish = user_check_done_global($db_credentials, $user_number, $location);
	if ($to_finished) {
		header('Location: index.php');
		return;
	}
	// if user is done with all sessions get the list of survey questions from the 
	// database in order to allow the webpage to load the survey
	else {
		$query = "SELECT * FROM survey_questions;";
		$questions = ask_pg($db_credentials,$query);
		
		// store the list of questions from the database into an array
		while ($this_question = pg_fetch_assoc($questions)) {
			$question_list[] = $this_question;
		}
	}
}
// if the cookies are not set, send user to index.php
else {
	header('Location: index.php');
	return;
}

// If the form for the survey is set from the previous page, the user has just submitted
// the survey. The information that the user inputed to the survey must now be stored into
// the database.
if (isset($_POST['subbut'])) {
	// for each question on the survey, find the value (user response) for the question 
	// submitted on the previous page
	foreach($question_list as $key => $value) {
		// the name for each question html tag is associated with the question number
		$string = 'serveyq_' . ($key + 1);
		$response = $_POST[$string];
		$question = $value['question'];
		
		// If the question being entered into the database is of 'Check Box' type, the 
		// user response must be stored differently because there can be multiple user 
		// responses for one 'Check Box' question which are stored in an array.
		if ($value['type'] == 'Check Box') {
			// when being stored into the database, each checkbox must be separated by a 
			// semi colon
			$first = true;
			foreach($response as $key => $value) {
				if ($first)
				{
					$new_response = $value;
					$first = false;
				}
				else {
					$new_response = $new_response . "; " . $value;
				}
			}
			$response = $new_response;
		}
		
		// If the question being entered into the database is not of 'Check Box' type, 
		// there is only one user response for tat question and can be directly stored 
		// into the database.
		else if ($value['type'] == 'Free Response') {
			$response = pg_escape_string($response);
		}
		$query = "INSERT INTO survey_response(user_number, sq_id, question, response ) VALUES('" . $user_number . "','" . ($key + 1) . "','" . $question . "','" . $response . "');";	
		ask_pg($db_credentials,$query);	
	}
	
	$survey_done = true;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>Survey</title>
<link rel="stylesheet" href="west.css" />
</head>
<body>
<form name="survey" action="survey.php" method="post" id="survey">
<?php
	// If the survey has already been taken, display the below message instead of the 
	// survey
	if ($survey_done) {
		echo "<h1>Thank you for taking the survey!</h1>";
	}
	// If the survey has not been taken yet, display the survey for the user
	else {
		echo "<form name=\"survey\" action=\"survey.php\" method=\"post\" id=\"survey\">";
		$index = 0;
		
		// Loop through list of questions for the survey and based on the question type,
		// create the html for the question and answers
		foreach($question_list as $key => $value) {
			$index++;
			switch($value['type']) {
				case "Multiple Choice":
					$query = "SELECT * FROM survey_answers WHERE question = '" . $value['question'] . "';";
					$answers = ask_pg($db_credentials,$query);
					$answer_list = NULL;
					while ($this_answer = pg_fetch_assoc($answers)) {
						$answer_list[] = $this_answer['answer'];
					
					}
					echo "<p style=\"font-size: 24px\">" . $value['question'] . "</p>";
					foreach($answer_list as $key => $value) {
						echo "<p><input type=\"radio\" name=\"serveyq_" . (string)($index) . "\" value=\"" .  $value . "\" unchecked style=\"font-size: 20px;\"><label style=\"font-size: 20px;\">" . $value . "</label></p>";
					}
					break;
	
				case "Drop Down":
					$query = "SELECT * FROM survey_answers WHERE question = '" . $value['question'] . "';";
					$answers = ask_pg($db_credentials,$query);
					$answer_list = NULL;
					while ($this_answer = pg_fetch_assoc($answers)) {
						$answer_list[] = $this_answer['answer'];
					}
					echo "<p style=\"font-size: 24px\">" . $value['question'] . "</p>";
					echo "<select class=\"select\" name=\"serveyq_" . (string)($index) . "\">";
					foreach($answer_list as $key => $value) {
						echo "<option value=\"" . $value . "\"><label style=\"font-size: 20px;\">" . $value . "</label></option>";
					}
					echo "</select>";
					break;
				
				case "Free Response":
					echo "<p style=\"font-size: 24px\">" . $value['question'] . "</p>";
					echo "<input type=\"text\" name=\"serveyq_" . (string)($index) . "\" size=\"80\" style=\"font-size: 20px\"> </br>";
					break;
				
				case "Check Box":
					$query = "SELECT * FROM survey_answers WHERE question = '" . $value['question'] . "';";
					$answers = ask_pg($db_credentials,$query);
					$answer_list = NULL;
					while ($this_answer = pg_fetch_assoc($answers)) {
						$answer_list[] = $this_answer['answer'];
					}
					echo "<p style=\"font-size: 24px\">" . $value['question'] . "</p>";
					foreach($answer_list as $key => $value) {
						echo "<input style=\"font-size: 20px;\" type=\"checkbox\" name=\"serveyq_" . (string)($index) . "[" . $key . "]" . "\" value=\"" . $value . "\"><label style=\"font-size: 20px;\">" . $value . "</label></br>";
					}
					break;
			}
			echo "</br>";
		}
		echo "<input class=\"submitLink\" type=\"submit\" name=\"subbut\" value=\"Submit Survey\" id=\"subbut\"></form>";
	}
?>
</body>
</html>
