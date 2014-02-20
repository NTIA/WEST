<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>Database Operations</title>
<link rel="stylesheet" href="west.css" />
<script type="text/javascript" src="db_tasks.js">
</script>
</head>

<body>
<?php 
/**
 * the purpose of this page is to provide easy access to database commands used to 
 * administer the test software.there are lots of powerful commands here, be careful not 
 * to do the "Test Reset", "Configure Sessions, Questions, and Answers", "Configure Survey", 
 * "Survey Reset", or "Create Database" in the middle of a test! this file should probably 
 * be moved to a separate folder with no public access.
 *
 * andrew catellier, 05/25/2011, modified 06/20/2011
 * updated Luke Connors 06/20/2012
 */

// include all the functions necessary to interact with the database
include 'functions.php';
include 'db_creds.php';
// store the database credentials
$admin_credentials = $admin_credentials;

// check POST to see if an action was submitted
if (isset($_POST['db_submitter'])) {
	// store the requested action and figure out which one it is
	// there should probably be some input checking here, though i'm not sure it's necessary
	$request = $_POST['request'];
	switch ($request){
		// "demo reset" will delete all votes, users, user order randomization tables, and resets appropriate
		// sequence values to 1
		case "Test Reset":
			$query = "TRUNCATE TABLE users; SELECT setval('u_inds',1,false); TRUNCATE TABLE user_rand; SELECT setval('r_inds',1,false); TRUNCATE TABLE votes; SELECT setval('v_inds',1,false); SELECT setval('a_inds',1,false); SELECT setval('q_inds',1,false); SELECT setval('s_inds',1,false); SELECT setval('f_inds',1,false); SELECT setval('sq_inds',1,false); SELECT setval('sa_inds',1,false); SELECT setval('re_inds',1,false); SELECT setval('r_inds',1,false); SELECT setval('ra_inds',1,false);";
			ask_pg($admin_credentials,$query);
			echo "Test Reset performed.<br>";
			break;
			
		// "Configure Sessions, Questions, and Answers" will first truncate the table 
		// "filenames", "sessions", and will delete the table "users".  Next each table
		// will be recreated according to the text file made by an administrator.  
		// This text file will indicate the number of sessions, the name of each session,
		// and the videos each user is to watch for each session.
		// This selection will also store information for the questions for each video in
		// the database which are specified in a text file made by an administrator.
		// This selection will also store the information for indicating weather the 
		// sessions and videos are to be completed in a random or specific order.
		case "Configure Sessions, Questions, and Answers":
			// get information from admin for order of sessions and videos
			$ses_rand = $_POST['sessions'];
			$vid_rand = $_POST['videos'];
			$tally = $_POST['tally'];
			
			// The user table needs to be deleted if it exists because the structure of
			// the user table is dependent on the name/number of the sessions specified by
			// the administrator.
			$query = "TRUNCATE TABLE filenames; SELECT setval('f_inds',1,false); TRUNCATE TABLE random; SELECT setval('ra_inds',1,false); TRUNCATE TABLE sessions; SELECT setval('s_inds',1,false); TRUNCATE TABLE answers; SELECT setval('a_inds',1,false);  TRUNCATE TABLE questions; SELECT setval('q_inds',1,false); SELECT setval('u_inds',1,false); DROP TABLE IF EXISTS users";
			ask_pg($admin_credentials, $query);
			// Save the or settings for sessions and videos into the database
			if ($tally == 'display videos') {
				$tally_bool = "FALSE";
			}
			else if ($tally == 'scores only') {
				$tally_bool = "TRUE";
			}
			else {
				echo "<p>error: tally not set.</p>";
			}
			$query = "INSERT INTO random(sessions,videos,tally) VALUES('" . $ses_rand . "','" . $vid_rand . "','" . $tally_bool . "');";
			ask_pg($admin_credentials, $query);
			// this query will create the users table according to the specified text file
			$query1 = "CREATE TABLE users(user_id INT PRIMARY KEY DEFAULT nextval('u_inds'), user_number INT, "; 
			
			// First the sessions file needs to be scanned for a Practice session because 
			// no matter what order the practice session information is stored in sessions.txt 
			// (if a practice session exists) it must be stored first in the session table and
			// users table.
			$handle = fopen("sessions.txt", "r");
			// check to make sure text file opened correctly
			if ($handle) {		
				while (!feof($handle)) {
					$buffer = fgets($handle, 66);
					$buffer = str_replace(array("\r", "\n"), '', $buffer);
					// search for Practice or practice as a session name in sessions.txt
					if ((strpos($buffer, 'Practice') > -1) || 
						(strpos($buffer, 'practice') > -1)) {
						$current = 'practice';
						$ses_name = pg_escape_string($buffer);
						// when found insert session name into database
						$query1 = $query1 . "\"ses_" . $ses_name . "\" boolean, ";
						$query2 = "INSERT INTO sessions(ses_name) VALUES('" . $ses_name . "');";
						ask_pg($admin_credentials, $query2);
					}
					// if practice session is found set current to file so that next 
					// iteration filenames will begin to store
					else if (($current == 'practice') && (strpos($buffer, '+') > -1)) {
						$current = 'file';
					}
					// once practice files are stored, set current to reset so that no
					// more info is stored on this pass through sessions.txt
					else if ((strpos($buffer, '*') > -1)) {
						$current = 'reset';
					}
					// if the current line of text file is a string of numbers and letters
					// this indicates that this line is a file name
					else if ($current == 'file') {
						if (empty($buffer)) {
							// don't put anything into the DB if there's nothing to put in there
						}
						else {
							$filetag = preg_split('/ /', $buffer, -1);
							$src = pg_escape_string($filetag[0]);
							$vi = pg_escape_string($filetag[1]);
							$ai = pg_escape_string($filetag[2]);
							$query3 = "INSERT INTO filenames(ses,src,vi,ai) VALUES('" . $ses_name . "','" . $src . "','" . $vi . "','" . $ai . "');";
							ask_pg($admin_credentials, $query3);
						}
					}
				} 
			}
			fclose($handle);
			// pass through sessions.txt again to retrive the information for all sessions 
			// that are not practice sessions 
			$handle = fopen("sessions.txt", "r");
			// check to make sure text file opened correctly
			if ($handle) {		
				while (!feof($handle)) {
					$buffer = fgets($handle, 66);
					$buffer = str_replace(array("\r", "\n"), '', $buffer);
					// each line before a session title is a line containing only '*' 
					if (strpos($buffer, '*') > -1) {
						$current = 'session';
					}
					// if current session is a practice, do not store line from session.txt
					else if ($current == 'practice') {
					}
					// each line before all the file names for a session is a line 
					// containing only '+' 
					else if (strpos($buffer, '+') > -1) {
							$current = 'file';
					}
					// if current line is a session, make sure it is not a practice 
					// session and store the session name
					else if ($current == 'session') {
						if ((strpos($buffer, 'Practice') > -1) || 
							(strpos($buffer, 'practice') > -1)) {
							$current = 'practice';
						}
						else {
							$ses_name = pg_escape_string($buffer);
							$query1 = $query1 . "\"ses_" . $ses_name . "\" boolean, ";
							$query2 = "INSERT INTO sessions(ses_name) VALUES('" . $ses_name . "');";
							ask_pg($admin_credentials, $query2);
						}
					}
					// if the current line of text file is a string of numbers and letters
					// this indicates that this line is a file name
					else if ($current == 'file') {
						if (empty($buffer)) {
							// don't put anything into the DB if there's nothing to put in there
						}
						else {
							$filetag = preg_split('/ /', $buffer, -1);
							$src = pg_escape_string($filetag[0]);
							$vi = pg_escape_string($filetag[1]);
							$ai = pg_escape_string($filetag[2]);
							$query3 = "INSERT INTO filenames(ses,src,vi,ai) VALUES('" . $ses_name . "','" . $src . "','" . $vi . "','" . $ai . "');";
							ask_pg($admin_credentials, $query3);
						}
					}			
				} 
			}
			fclose($handle);
			// finish query to create new users table
			$query1 = $query1 . "location VARCHAR(20));";
			ask_pg($admin_credentials, $query1);
			
			// Now the answers table needs to be set up, inorder to do this scan through 
			// answers.txt and store each answer with it's corresponding video tag
			$handle = fopen("answers.txt", "r");
			// check to make sure text file opened correctly
			if ($handle) {		
				while (!feof($handle)) {
					$buffer = fgets($handle, 300);
					$buffer = str_replace(array("\r", "\n"), '', $buffer);
					// each line before a video tag title is a line containing only '*' 
					if (strpos($buffer, '*') > -1) {
						$current = 'filetag';
					}
					// each line before all the answers for a video is a line containing
					// only '-'
					else if (strpos($buffer, '++') > -1) {
						$qv_no_a++;
					}
					else if (strpos($buffer, '+') > -1) {
						$current = 'answer';
						$qv_no_a = 1;
					}
					// store filetag for the next answers to be stored
					else if ($current == 'filetag') {
						$filetag = pg_escape_string($buffer);
						$current = 'questions';
						$qv_no_q = 0;
					}
					else if ($current == 'questions') {
						if (empty($buffer)) {
							// For questions, we have a special case. if it's the first question,
							// it means display before the video is played. if there's nothing
							// on that line, it means nothing should be displayed, but to
							// make it simple for the display logic, it's easier to tell it to
							// display an empty string. therefore if $current == 'questions',
							// and the buffer is empty, and $qv_no_q == 0, store an empty string.
							// otherwise, don't put empty junk in the DB.
							if ($qv_no_q == 0) {
								$question = '';
								$query = "INSERT INTO questions(qv_no, filetag, question) VALUES('" . $qv_no_q . "','" . $filetag . "','" . $question . "');";
								ask_pg($client_credentials, $query);
								$qv_no_q++;
							}
							else {
								// don't put anything into the DB if there's nothing to put in there
							}
						}
						else {
							$question = pg_escape_string($buffer);
							$query = "INSERT INTO questions(qv_no, filetag, question) VALUES('" . $qv_no_q . "','" . $filetag . "','" . $question . "');";
							ask_pg($client_credentials, $query);
							$qv_no_q++;
						}
					}
					// add answer with current file tag to the answerss table in the
					// database
					else if ($current == 'answer') {
						if (empty($buffer)) {
							// don't put anything into the DB if there's nothing to put in there
						}
						else {
							$answer = preg_split('/[\[\]]/', $buffer);
							$query = "INSERT INTO answers(qv_no, filetag, position, answer) VALUES('" . $qv_no_a . "','" . $filetag . "','" . (int)$answer[1] . "','" . pg_escape_string($answer[2]) . "');";
							ask_pg($client_credentials, $query);
						}
					}
				}
			}
			
			
			// give user access to newly created user table
			$query = "GRANT SELECT, INSERT, UPDATE ON ALL TABLES IN SCHEMA public TO \"" . $db_client . "\"; GRANT UPDATE, SELECT ON ALL SEQUENCES IN SCHEMA public TO \"" . $db_client . "\";";
			ask_pg($admin_credentials, $query);
			
			echo "Sessions, Questions, and Answers have been configured with sessions " . $ses_rand . " and videos " . $vid_rand  . ".<br>";
			break;

		// "Configure Survey" will create the following tables in the database: survey 
		// questions and survey answers based on information provided in survey.txt by an
		// administrator. "Configure Survey" will not delete the information about user
		// responses in the database already
		case "Configure Survey":
			// first the tables survey_questions and survey_answers must be truncated 
			$query = "TRUNCATE TABLE survey_questions; TRUNCATE TABLE survey_answers;";
			ask_pg($admin_credentials, $query);
			
			// survey.txt will be parsed to receive each question, question type and the
			// answers for each question based on a specific format.
			$handle = fopen("survey.txt", "r");

			// check to make sure text file opened correctly
			if ($handle) {		
				while (!feof($handle)) {
					$buffer = fgets($handle, 200);
					$buffer = str_replace(array("\r", "\n"), '', $buffer);
					// each line before the question type is a line containing only '*' 
					if (strpos($buffer, '*') > -1) {
						$current = 'type';
					}
					// each line before all the answers for a question is a line 
					// containing only '-'
					else if (strpos($buffer, '+') > -1) {
						$current = 'answer';
					}
					// store types for the question to be stored with
					else if ($current == 'type') {
						$type = $buffer;
						$current = 'question';
					}
					else if ($current == 'question') {
						if (empty($buffer)) {
							// don't put anything into the DB if there's nothing to put in there
						}
						else {
							$question = pg_escape_string($buffer);
							$query = "INSERT INTO survey_questions(question, type) VALUES('" . $question . "','" . $type . "');";
							ask_pg($admin_credentials, $query);
						}
					}
					// add answer with current question to the survay_answers table in the
					// database
					else if ($current == 'answer') {
						if (empty($buffer)) {
							// don't put anything into the DB if there's nothing to put in there
						}
						else {
							$answer =pg_escape_string($buffer);
							$query = "INSERT INTO survey_answers(question, answer) VALUES('" . $question . "','" . $answer . "');";
							ask_pg($admin_credentials, $query);
						}
					}
				}
			}
			echo "Survey has been configured with questions and answers.";
			break;
			
		// "Survey Reset" will truncate the Survey Responses table, deleting all the information
		// reagarding user responses to survey questtions.
		case "Survey Reset":
			$query = "TRUNCATE TABLE survey_response; SELECT setval('r_inds',1,false);";
			ask_pg($admin_credentials, $query);
			echo "Survey has been reset.";
			break;
		// "Create Database" will add all the necissary tables to the database inorder to configure
		// and run the test. Create database should only be called once when setting up the test for
		// the very first time.
		case "Create Database":
			$query = "	
						CREATE SEQUENCE s_inds START 1;
						CREATE TABLE sessions(ses_id INT PRIMARY KEY DEFAULT nextval('s_inds'), ses_name VARCHAR(50));
						CREATE SEQUENCE ra_inds START 1;
						CREATE TABLE random(rand_id INT PRIMARY KEY DEFAULT nextval('ra_inds'), sessions VARCHAR(50), videos VARCHAR(12), tally BOOLEAN);
						CREATE SEQUENCE f_inds START 1;
						CREATE TABLE filenames(src_id INT PRIMARY KEY DEFAULT nextval('f_inds'), ses VARCHAR(50), src VARCHAR(50), vi VARCHAR(7), ai VARCHAR(7));
						CREATE SEQUENCE a_inds START 1;
						CREATE TABLE answers(sen_id INT PRIMARY KEY DEFAULT nextval('a_inds'), filetag VARCHAR(64), qv_no INT, position INT, answer VARCHAR(80));
						CREATE SEQUENCE q_inds START 1;
						CREATE TABLE questions(q_id INT PRIMARY KEY DEFAULT nextval('q_inds'), qv_no INT, filetag VARCHAR(64), question VARCHAR(300));
						CREATE SEQUENCE sq_inds START 1;
						CREATE TABLE survey_questions(sq_id INT PRIMARY KEY DEFAULT nextval('sq_inds'), question VARCHAR(200), type VARCHAR(20));
						CREATE SEQUENCE sa_inds START 1;
						CREATE TABLE survey_answers(answer_id INT PRIMARY KEY DEFAULT nextval('sa_inds'), question VARCHAR(200), answer VARCHAR(200));
						CREATE SEQUENCE re_inds START 1;
						CREATE TABLE survey_response(resp_id INT PRIMARY KEY DEFAULT nextval('re_inds'), user_number INT, sq_id INT, question VARCHAR(200), response VARCHAR(600));
						CREATE SEQUENCE u_inds START 1;
						CREATE SEQUENCE v_inds START 1;
						CREATE TABLE votes(vote_id INT PRIMARY KEY DEFAULT nextval('v_inds'), user_number INT, qv_no INT, user_response VARCHAR(80), answer VARCHAR(80), location VARCHAR(20), ses VARCHAR(50), src VARCHAR(50), vi VARCHAR(7), ai VARCHAR(7), position INT, time_stamp TIMESTAMP DEFAULT NOW());
						CREATE SEQUENCE r_inds START 1;
						CREATE TABLE user_rand(order_id INT PRIMARY KEY DEFAULT nextval('r_inds'), user_number INT, ses VARCHAR(50), src VARCHAR(50), vi VARCHAR(7), ai VARCHAR(7), ord_ind INT, location VARCHAR(20));
						GRANT SELECT, INSERT, UPDATE ON ALL TABLES IN SCHEMA public TO \"" . $db_client . "\";
						GRANT ALL ON ALL TABLES IN SCHEMA public TO \"" . $db_admin . "\";
						GRANT UPDATE, SELECT ON ALL SEQUENCES IN SCHEMA public TO \"" . $db_client . "\";
						GRANT UPDATE, SELECT ON ALL SEQUENCES IN SCHEMA public TO \"" . $db_admin . "\";";
			ask_pg($admin_credentials, $query);
			echo "database has been created";
	}
}
?>
<!-- create the HTML form. Here I have commented out destructive commands -->
<form name="db_maintenance" id="tasks" action="db_tasks.php" method="post">
<select class="select" name="request" id="task_sel">
<option value="Test Reset">Test Reset</option>
<option value="Configure Sessions, Questions, and Answers">Configure Sessions, Questions, and Answers</option>
<option value="Configure Survey">Configure Survey</option>
<option value="Survey Reset">Survey Reset</option>
<option value="Create Database">Create Database</option>
</select>
<input class="submitLink" type="submit" name="db_submitter" value="submit">

<div id="random">
<p style="font-size: 20px">Sessions:
<select class="select" name="sessions" id="ses_rand" title="sessions">
<option value="random">random</option>
<option value="ordered">ordered</option>
</select></p>
<p style="font-size: 20px">Videos:
<select class="select" name="videos" id="vid_rand" title="videos">
<option value="random">random</option>
<option value="ordered">ordered</option>
</select></p>
<p style="font-size: 20px">Record scores only:
<select class="select" name="tally" id="tally" title="tally">
<option value="display videos">display videos</option>
<option value="scores only">scores only</option>
</select></p>
</div>
</form>


</body>
</html>
