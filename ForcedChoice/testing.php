<?php

$blah = preg_split('/_/',"userresponse_1_2");
print_r($blah);

//	include 'functions.php';
//	$client_credentials = 'host=10.6.6.124 dbname=FC_Survey user=FCSadmin password=@dm1n';
//	$user_number = 1001;
//	$session = 'Practice';
//	$location = 'Lab';
//	
//	$filetag = 'f1femalepresidentV0A0';
//	
//	// get the list of randomized filenames from the database for this user/session/
//	// location combination
//	$fnames = get_fnames($client_credentials, $user_number, $session, $location);
//	$temps = pg_fetch_assoc($fnames);
//	print_r($temps);
//	echo $temps . "\r\n";
//	
//	// debug time, man. gar.
//	$fname_index = user_seq_nextval($client_credentials,$user_number,$session,$location);
//	echo $fname_index . "\r\n";
//	
//	// get filename info from the previous result in order to correctly enter the vote
//	$prev_fname = pg_fetch_assoc($fnames,($fname_index - 1));
//	$prev_src = $prev_fname['src'];
//	$prev_vi = $prev_fname['vi'];
//	$prev_ai = $prev_fname['ai'];
//	$filetag = $prev_src .  $prev_vi . $prev_ai;
//	print_r($filetag);
	
	
	// find out how many questions we have for this video
//	$query = "SELECT count(DISTINCT qv_no) FROM tempanswers WHERE filetag = '" . $filetag . "';";
//	$answer = ask_pg($client_credentials,$query);
//	$qvs = pg_fetch_assoc($answer);
//	echo $qvs['count'] . " \r";
	
	// Form a query to get questions for current video
//	$query = "SELECT * FROM questions WHERE filetag = '" . $filetag . "';";
//	$questions = ask_pg($client_credentials,$query);
//
//	// store questions into an array to create...questions in html
//	while ($this_question = pg_fetch_assoc($questions)) {
//		$q_list[$this_question['qv_no']] = $this_question['question'];
//	}
//	
//	// then loop over all the answers and put them into an array structured such that 
//	// it's not a pain to loop over
//	$answer_list = array();
//	$query = "SELECT * FROM answers WHERE filetag = '" . $filetag . "';";
//	$answers = ask_pg($client_credentials,$query);
//	// loop over all answers for this video
//	while($this_answer = pg_fetch_assoc($answers)){
//		// store all the answers in an associative array, grouping answers by question
//		$answer_list[$this_answer['qv_no']][] = $this_answer['answer'];
//	}
//
//print_r($answer_list);
//foreach ($q_list as $qkey => $qvalue) {
//	if ($qkey != 0) {
//		echo "<h1>" . $qvalue . "</h1>\r";
//		echo "<fieldset class=\"radios\" id=\"user_response_" . $qkey . "\">\r";
//		foreach ($answer_list[$qkey] as $akey => $avalue) {
//			echo "<label class=\"label_radio\" for=\"mos_" . $qkey . "_" . (string)($akey + 1) . "\">\r";
//			echo "<p><input type=\"radio\" id=\"mos_" . $qkey . "_" . (string)($akey + 1) . "\" name=\"user_response_" . $qkey . "_" . (string)($akey + 1) . "\" value=\"" . $avalue . "\" unchecked>" . $avalue . "</p>\r";
//			echo "</label>\r";
//		}
//		echo "</fieldset>\r";
//	}
//}

//$twodee = array(
//	1 => array(
//		"hundred",
//		"fifty",
//		"zero",
//	),
//	2 => array(
//		"hundred",
//		"fifty",
//		"zero",
//	),
//	3 => array(
//		"hundred",
//		"fifty",
//		"zero",
//	)
//);

//foreach ($twodee as $key => $value) {
//	foreach ($value as $k => $v) {
//		echo $key . "  " . $v . "\r";
//	}
//}

//foreach ($twodee[1] as $key => $value) {
//	echo $key . "  " . $value . "\r";
//}

//$query = "TRUNCATE TABLE tempanswers; TRUNCATE TABLE tempquestions;";
//ask_pg($client_credentials, $query);
//$handle = fopen("answers.txt", "r");
//// check to make sure text file opened correctly
//if ($handle) {		
//	while (!feof($handle)) {
//		$buffer = fgets($handle, 300);
//		$buffer = str_replace(array("\r", "\n"), '', $buffer);
//		// each line before a video tag title is a line containing only '*' 
//		if (strpos($buffer, '*') > -1) {
//			$current = 'filetag';
//		}
//		// each line before all the sentences for a video is a line containing
//		// only '-'
//		else if (strpos($buffer, '++') > -1) {
//			$qv_no_a++;
//		}
//		else if (strpos($buffer, '+') > -1) {
//			$current = 'answer';
//			$qv_no_a = 1;
//		}
//		// store filetag for the next sentences to be 
//		else if ($current == 'filetag') {
//			$filetag = $buffer;
//			$current = 'questions';
//			$qv_no_q = 0;
//		}
//		else if ($current == 'questions') {
//			$question = $buffer;
//			//$current = 'q2';
//			$query = "INSERT INTO tempquestions(qv_no, filetag, question) VALUES('" . $qv_no_q . "','" . $filetag . "','" . $question . "');";
//			ask_pg($client_credentials, $query);
//			$qv_no_q++;
//		}
//		// add sentence with current file tag to the sentences table in the
//		// database
//		else if ($current == 'answer') {
//		    $answer = preg_split('/[\[\]]/', $buffer);
//		    // fix for #25 requires refactoring this bad boy. gross.
//			$query = "INSERT INTO tempanswers(qv_no, filetag, correctness, answer) VALUES('" . $qv_no_a . "','" . $filetag . "','" . (int)$answer[1] . "','" . $answer[2] . "');";
//			ask_pg($client_credentials, $query);
//		}
//	}
//}


?>