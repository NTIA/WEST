<?php
/**
 * The purpose of this file is to provide functions that are used multiple times throught
 * the rest of the program.  In general, user_id is the unique user_id found in the users 
 * table. user_number may have several entries with the same value in the users table, 
 * due to the fact that the test can take place at multiple locations. One subject at two 
 * different locations will have two user_id numbers.
 *
 * Andrew Catellier
 * updated Luke Connors 06/22/2012
 */

/**
 * The purpose of this function is to ask queries of the postgres database.
 *
 * $db_credential is a string of information used to connect to the database of the 
 * form: 'host=host dbname=db_name user=username password=pass_word'
 * $query is a string that represents a standard query, including semicolon
 * 
 * This function returns the result of the query from the database.  If there is an error 
 * with the query, this function will echo the error message to the user.
 * 
 * Andrew Catellier, 02/08/2011
 */
function ask_pg($db_credentials, $query)
{
	$db = pg_connect($db_credentials); //connect to the db
	
	$result = pg_query($query); //query the db
	if (!$result) { //if the query failed, show an error
		$errormessage = pg_last_error($db);
		echo "error with query: " . $errormessage;
		pg_close();
		exit();
	} else { //otherwise, return the result resource
		return $result;
		pg_close();
	}
}


/**
 * The purpose of this function is to write a table from the database to a csv file and then
 * send the file to the user to download. 
 *
 * $results is a table result from a query to the database 
 * $title is the title of the table to be displayed 
 * $handle is a file pointer resource for the csv file to be written to
 *
 * There is no return value, once the function is done, a file is sent to the user for 
 * download containing the requested information from the database.
 */
function dbtable_csv($result, $title) {
	// set the timezone inorder to get a time stamp inorder to create a unique file name to
	// write to
	date_default_timezone_set('America/Denver');
	$date = date_create($row[0]);
	$time_stamp = date_format($date, 'Y-m-d H.i.s');
	$filename = $_SERVER['DOCUMENT_ROOT'].'\\csv\\' . $time_stamp . '.csv';
	$handle = fopen($filename, 'w');
	
	if ($handle) {
		// fetch the first row
		$myrow = pg_fetch_assoc($result);
		
		$title_array[] = $title;
		$title_array[] = $time_stamp;
		fputcsv($handle, $title_array);

		// if database empty, exit function
		if (!$myrow) {
			echo "requested database table is empty";
			return;
		}
		   	
   		// go through all the columns and extract the name of each column and the first row
   		foreach($myrow as $key=>$value){
   			$header_array[] = $key;
   			$value_array[] = $value;
	   	}
   	
   		// write the column names and first row to csv file
  		fputcsv($handle, $header_array);
   		fputcsv($handle, $value_array);
   	
   		// loop through the rest of the results and write to csv file
   		while ($myrow = pg_fetch_assoc($result)){
   			//reset the $value_array so we have clean rows
   			$value_array = null;
   		
   			// go through all the columns and extract their value
	   		foreach($myrow as $key=>$value){
   				$value_array[] = $value;
   			}
   			// write the values to csv file
   			fputcsv($handle, $value_array);
   		}

   	fclose($handle);
   	}
	
	// set the correct headers to force a download for the user
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
    return;
}


/**
 * The purpose of this function is to display a table from the database to html. 
 *
 * $results is a table result from a query to the database 
 * $title is the title of the table to be displayed 
 * $explanation is an explanation of the data shown in the table
 *
 * There is no return value, once the function is done, the entire table from the database
 * is posted to the web page
 *
 * Andre Catellier
 * updated Luke Connors 10/19/2012
 */
function dbtable_disp($result, $title, $explanation) {
	echo $title;
	
	// fetch the first row
	$myrow = pg_fetch_assoc($result);
   	$header_string = "";
   	$value_string = "";

   	// if nothing in the database, then exit funtion
   	if (!$myrow) {
   		echo "<br> requested database table is empty";
   		return;
   	}
   	
   	// go through all the columns and extract the name of each column and the first row
   	foreach($myrow as $key=>$value){
   		$header_string = $header_string . "<th>" . $key . "</th>";
   		$value_string = $value_string . "<td>" . $value . "</td>";
   	}
   	
   	echo "<table>";
   	
   	// display the names of the column and the first row
   	echo "<tr>" . $header_string . "</tr>\n";
   	echo "<tr>" . $value_string . "</tr>\n";
   	
   	// loop through the rest of the results
   	while ($myrow = pg_fetch_assoc($result)){
   		// reset the $value_string so we have clean rows
   		$value_string = "";
   		
   		// go through all the columns and extract their value
	   	foreach($myrow as $key=>$value){
   			$value_string = $value_string . "<td>" . $value . "</td>";
   		}
   	// display the values
   	echo "<tr>" . $value_string . "</tr>\n";
   	}
   	echo "</table>";
   	echo $explanation;
}


/**
 * The purpose of this file is to check that in the random sequence $ind_order, there are
 * no two identical video sources that play directly after one another.  $ind_order is 
 * designed to be an array randomly filled with all intigers from zero to the length of 
 * $ind_order, which is the same as the length of $all_files.  $all_files is an 
 * associative array with the tags of all the video files that need to be randomized.  
 * Eventually, each video will be played in the order of which there index number appears in 
 * $ind_order.  Before that is done $ind_order needs to be checked to make sure there 
 * are no cases where two videos play next to each other and have the same source.
 * The process of checking $ind_order involves iterating over $ind_order and finding the
 * filename that corresponds with the current value in $ind_order, then making sure that 
 * the filename is not the same as the filename that corresponds to the next value in 
 * $ind_order.  If they have the two have the same source, the current value in $ind_order 
 * is moved to the end of the array.
 *
 * $ind_order is an array of random integers who's values correspond to indicies 
 * in $all_files
 * $all_files is an associative array containing information (filetags) about all 
 * videofiles that are being put into a random order
 * 
 * The return value of this function is an array filled with random indices corresponding 
 * to videos in $all_files with no adjacent videos with the same source.
 *
 * Andrew Catellier
 * updated Luke Connors 06/22/2012
 */
function dupe_check($ind_order, $all_files)
{
	$rand_len = count($ind_order);
	$ind = 0;
	// iterate over ind_order checking each corresponding filename with the next value 
	// in ind_order.
	while ($ind < $rand_len) {
		if ($all_files[$ind_order[$ind]]['src'] == $all_files[$ind_order[$ind + 1]]['src']) {
			// if there are two adjacent file sources in $ind_order, move one to the back
			// of the array. 
			$temp = $ind_order[$ind];
			unset($ind_order[$ind]);
			$ind_order[] = $temp;
			$ind_order = array_values($ind_order);
			
			// is equal checks to see if all the remaining values in ind_order have the
			// same corresponding filename source
			if (!isequal($ind, $rand_len, $ind_order, $all_files)) {
				// subtract ind by one because you want the value of ind to stay the same
				// for the next iteration of the while loop, and one is added to ind later
				// in the loop
				$ind--;
			} 
			else {
				// we are in this branch because the last several are the same
				// the goal of this branch is to find a new place for the last couple that 
				// have the same source elsewhere in ind_order, where the source for 
				// adjacent files will not be the same
				$recurseind = 0;
				while ($all_files[$ind_order[$ind]]['src'] == $all_files[$ind_order[$ind + 1]]['src']) {
					if ($all_files[$ind_order[$ind]]['src'] == $all_files[$ind_order[$recurse_ind]]['src']) {
						// we add two here because if the current is equal to the first, 
						// it won't be equal to the second. so on the next iteration, it 
						// checks to see if it's equal to the third, and if it isn't, it 
						// slips it in.
						$recurseind+=2;
					} 
					else {
						$temp = array_pop($ind_order);
						array_unshift($ind_order, $temp);
					}
				}
			}
		}
		$ind++;
	}

	return $ind_order;		//return the vector of randomly place indices

}


/** 
 * This simple function retrieves a resource containing a list of filenames
 * that have previously been stored in a random order. 
 *
 * $db_connection is in the form: 
 * 'host=host dbname=db_name user=username password=pass_word'
 * $user_number corresponds to the user number entered at the beginning of the test
 * $session is a string indicating which session the user is currently completing
 *
 * The return value is an associate array with all the applicable rows from the database 
 * table user_rand
 *
 * Andrew Catellier, 02/08/2011
 * updated Luke Connors 06/22/2012
*/
function get_fnames($db_credentials, $user_number, $session, $location) 
{
	$query = "SELECT * FROM user_rand WHERE user_number = " . $user_number . " AND ses = '" . $session . "' AND location = '" . $location . "';";
	$rand_fnames = ask_pg($db_credentials,$query);
	return $rand_fnames;
}


/**
 * This function will check to see if the video source for the filename corresponding with 
 * the value in the array $ind_order is the same from index $curr_ind until the end of the 
 * array $ind_order.
 * 
 * $curr_ind is the index to begin the search of $ind_order
 * $array_len is the length of $ind_order
 * $ind_order is an array of integers corresponding to indices of $array_ut
 * $array_ut is an associated array of filenames of videos to be watched.
 *
 * This function will return TRUE if all the indices (from $curr_ind to the end) in the 
 * array $ind_order correspond to the same video source, otherwise the return value is 
 * FALSE
 *
 * Andrew Catellier
 * updated Luke Connors 06/22/2012
 */
function isequal($curr_ind, $array_len, $ind_order, $array_ut)
{
	$equalness = true;
	//minus one here so we don't try to access past the array
	for ($i = $curr_ind; $i < $array_len - 1; $i++) { 
		if ($array_ut[$ind_order[$i]]['src'] == $array_ut[$ind_order[$i + 1]]['src']) {
			//equalness stays set to true
		} else {
			$equalness = false;
			return $equalness;
			break;
		}
	}
	return $equalness;
}


/**
 * This function will make a display order specified by a test administrator for the 
 * current user for all files in each session for the experiment. It gathers the list of 
 * sessions from the sessions table then the list of filename stubs corresponding to each 
 * session
 * from the table filenames. It then creates a display order by coping over the filenames
 * for each session into user_rand for retrieval when needed. Finally, the function adds 
 * the current user to the user list with each session marked as incomplete.
 *
 * $db_connection is in the form: 
 * 'host=host dbname=db_name user=username password=pass_word'
 * $user_number corresponds to the user number entered at the beginning of the test
 * $location corresponds to the location entered at the beginning of the test
 *
 * This function has no return value.  After this function is done running, the user_rand
 * table and the users table are initiated with the new user_number and location.
 *
 * written Luke Connors 07/05/2012
 */
function make_ordered_file_sequence($db_credentials,$user_number,$location)
{
	//get a list of all the sessions
	$query = "SELECT * FROM sessions;";	
	$sessions = ask_pg($db_credentials, $query);
	while ($this_session = pg_fetch_assoc($sessions)) {
		$ses_list[] = $this_session;
	}
	
	// $query3 and $query4 are used to accumulate up one long query to update the users table
	$query3 = "INSERT INTO users(user_number, ";
	$query4 = "";
	
	// loop over the list of all the sessions to create an ordered list of the files 
	// belonging to each session for the specified user
	foreach ($ses_list as $ses_key => $ses_value) {
	
		//get a list of all filename variants for session
		$query1 = "SELECT * FROM filenames WHERE ses = '" . $ses_value['ses_name'] . "';";
		$fname_stubs = ask_pg($db_credentials, $query1);
		$file_list = NULL;
		while ($fname = pg_fetch_assoc($fname_stubs)) {
			$file_list[] = $fname;
		}
		
		// loop of the list of files for current session and copy those files into 
		// user_rand keeping the same order
		foreach ($file_list as $file_key => $file_value) {
			$query2 = "INSERT INTO user_rand(user_number, ses, src, vi, ai, ord_ind, location) VALUES('" . $user_number . "', '" . $ses_value['ses_name'] . "', '" . $file_value['src'] . "', '" . $file_value['vi'] . "', '" . $file_value['ai'] . "', '" . ($file_key + 1) ."','" . $location . "');";
			ask_pg($db_credentials, $query2);
		}
		
		// update $query3 and $query4 with current session information 
		$query3 = $query3 . "\"ses_" . $ses_value['ses_name'] . "\", ";
		$query4 = $query4 . " FALSE,";
	}
	
	// concat $query3 and $query4 to make full query to update users table
	$query3 = $query3 . "location) VALUES(" . $user_number . "," . $query4 . " '".  $location . "');";
	ask_pg($db_credentials, $query3);
}


/**
 * This function will make a unique display order using all files to be used
 * in each session for the experiment. It gathers the list of sessions from the 
 * sessions table then the list of filename stubs corresponding to each session
 * from the table filenames. It then randomly creates a display order unique to 
 * this user for each session, and stores the order in the table user_rand for 
 * retrieval when needed. Finally, the function adds the user to the user list
 * with each session marked as incomplete.
 *
 * $db_connection is in the form: 
 * 'host=host dbname=db_name user=username password=pass_word'
 * $user_number corresponds to the user number entered at the beginning of the test
 * $location corresponds to the location entered at the beginning of the test
 *
 * This function has no return value.  After this function is done running, the user_rand
 * table and the users table are initiated with the new user_number and location.
 *
 * andrew catellier, 02/08/2011
 * updated Luke Connors 06/19/2012
 */
function make_random_file_sequence($db_credentials,$user_number,$location) 
{
	//get a list of all the sessions
	$query = "SELECT * FROM sessions;";	
	$sessions = ask_pg($db_credentials, $query);
	while ($this_session = pg_fetch_assoc($sessions)) {
		$ses_list[] = $this_session;
	}
	
	// $query3 and $query4 are used to accumulate up one long query to update the users table
	$query3 = "INSERT INTO users(user_number, ";
	$query4 = "";
	
	// loop over the list of all the sessions to create a random order of the files 
	// belonging to each session for the specified user
	foreach ($ses_list as $ses_key => $ses_value) {
		
		//get a list of all filename variants for session
		$query1 = "SELECT * FROM filenames WHERE ses = '" . $ses_value['ses_name'] . "';";
		$fname_stubs = ask_pg($db_credentials, $query1);
		$file_list = NULL;
		while ($fname = pg_fetch_assoc($fname_stubs)) {
			$file_list[] = $fname;
		}
				
		//find out how many rows there are
		$num_rows = pg_num_rows($fname_stubs);	
		
		//in order to have a unique seed, check the database to see how many entries there
		//are in the user table, and then add a multiple of that number to the seed.
		$query = "SELECT count(*) FROM users;";
		$seed_addend = pg_fetch_assoc(ask_pg($db_credentials, $query));
		$seed_addend = $seed_addend['count'] * 27;
		
		//make a unique order, based on $user_number and ses_id from sessions table
		$rand_order = randperm($num_rows, $user_number + $ses_value['ses_id'] + $seed_addend);
		$rand_order = dupe_check($rand_order, $file_list);
		
		//based of off unique random order, fill user_rand with file tags
		foreach ($rand_order as $rand_key => $rand_value){
			$query2 = "INSERT INTO user_rand(user_number, ses, src, vi, ai, ord_ind, location) VALUES('" . $user_number . "', '" . $ses_value['ses_name'] . "', '" . $file_list[$rand_value]['src'] . "', '" . $file_list[$rand_value]['vi'] . "', '" . $file_list[$rand_value]['ai'] . "', '" . ($rand_key + 1) ."','" . $location . "');";
			$re_store = ask_pg($db_credentials, $query2);
		}
		
		// update $query3 and $query4 with current session information 
		$query3 = $query3 . "\"ses_" . $ses_value['ses_name'] . "\", ";
		$query4 = $query4 . " FALSE,";
	}
	
	// concat $query3 and $query4 to make full query to update users table
	$query3 = $query3 . "location) VALUES(" . $user_number . "," . $query4 . " '".  $location . "');";
	ask_pg($db_credentials, $query3);
}


/**
 * This function creates a vector of length $vec_len where values from 1
 * to $vec_len are placed randomly within the vector. 
 * $seed is optional, but sets the state of the random number generator
 * so repeatable results are possible if desired.
 * In order to achieve the same results, $vec_len and $seed must be consistent
 * among calls.
 *
 * $vec_len describes the max integer number for the range of numbers who's order will be 
 * randomized (min is always 1)
 * $seed describes a unique number to seed the random number generator
 *
 * The return value of this function is an array of length $vec_len with all integers 1 to
 * $vec_len randomly placed in a random index of the array
 * 
 * andrew catellier, 02/08/2011
 * updated Luke Connors 06/19/2012
 */
function randperm($vec_len, $seed) 
{	
	if ($seed){
		srand($seed); 			//if present, seed the random number generator
	}
	
	for ($i = 0; $i<$vec_len; $i++){
		$rand_array[$i] = rand(); 		//fill a vector with random numbers
	}	
	
	asort($rand_array); 		//sort the vector and keep track of the original index
	
	foreach($rand_array as $key => $value){
		$ind_order[] = $key; 		//store the indices in a new vector
	}
	return $ind_order;
}


/**
 * The purpose of this function is to write a table from the database to a csv file.
 *
 * $results is a table result from a query to the database 
 * $title is the title of the table to be displayed 
 * $handle is the result from 
 *
 * There is no return value, once the function is done, the required information is written
 * to the requested csv file.
 * 
 * Luke Connors, 11/02/2012
 */
function read_to_csv($handle, $result, $title) {
	if ($handle) {
		// fetch the first row
		$myrow = pg_fetch_assoc($result);
		
		fputcsv($handle, $title);

		// if database empty, exit function
		if (!$myrow) {
			return;
		}
		   	
   		// go through all the columns and extract the name of each column and the first row
   		foreach($myrow as $key=>$value){
   			$header_array[] = $key;
   			$value_array[] = $value;
	   	}
   	
   		// write the column names and first row to csv file
  		fputcsv($handle, $header_array);
   		fputcsv($handle, $value_array);
   	
   		// loop through the rest of the results and write to csv file
   		while ($myrow = pg_fetch_assoc($result)){
   			//reset the $value_array so we have clean rows
   			$value_array = null;
   		
   			//go through all the columns and extract their value
	   		foreach($myrow as $key=>$value){
   				$value_array[] = $value;
   			}
   			// write the values to csv file
   			fputcsv($handle, $value_array);
   		}

   	}
    return;
}


/**
 * This function checks to see if a specific user has finished taking a session of the
 * test. set_cookies.php as well as run_expt.php uses this function before querying for 
 * a video file and serving up the vote form, and if it returns true, it redirects to 
 * finished.php
 *
 * $db_connection is in the form: 
 * 'host=host dbname=db_name user=username password=pass_word'
 * $user_number corresponds to the user number entered at the beginning of the test
 * $session is a string indicating which session is being checked for completion
 * $location corresponds to the location entered at the beginning of the test
 *
 * This function returns TRUE if the user is done with the specified session, FALSE if
 * the user is not, and NULL in the case of invalid information in the database
 *
 * andrew catellier, 02/10/201
 * Luke Connors, 06/18/2012
 */
function user_check_done($db_credentials, $user_number, $session, $location) 
{
	$query = "SELECT * FROM users WHERE user_number = " . $user_number . " AND  location = '" . $location . "';";
	$user_status = ask_pg($db_credentials,$query);
	// store information from users table into an associated array
	$user_status_cols = pg_fetch_assoc($user_status);
	if ($user_status_cols["ses_" . $session] == 't'){
		return TRUE;
	} elseif ($user_status_cols["ses_" . $session] == 'f') {
		return FALSE;
	} else {
		return NULL;
		echo "invalid response from database (not 't' or 'f') in function user_check_done<br>";
	}
}

/** 
 * This function checks to see if a user has finished all the sessions in the test.
 * finished.php uses this function in order to check to see if it should 
 * tell the user if he or she has completed the whole experiment.
 *
 * $db_connection is in the form: 
 * 'host=host dbname=db_name user=username password=pass_word'
 * $user_number corresponds to the user number entered at the beginning of the test
 * $location corresponds to the location entered at the beginning of the test
 *
 * This function returns an array strings corresponding to the sessions the user still has 
 * to finish in their current location.  If the user has no devices left to finish, an
 * array filled with NULL is returned
 *
 * Andrew Catellier, 02/10/201
 * updated Luke Connors 06/19/2012
 */
function user_check_done_global($db_credentials, $user_number, $location) 
{
	$query = "SELECT * FROM users WHERE user_number = " . $user_number . " AND location = '" . $location . "';";
	$user_status = ask_pg($db_credentials,$query);
	//get all the keys/data from users table into an associated array
	$user_status_cols = pg_fetch_assoc($user_status);
	//loop through the array
	foreach($user_status_cols as $key => $value){
		//if a column returns as 'f', meaning "false" from postgres, store
		//the key value
		if ($value == 'f'){
			//in this table, the session name can be learned by reading the text
			//before after the first underscore
			$ses_ind = strpos($key,"_");
			//store the found text in the output variable
			$to_finish[] = substr($key,$ses_ind + 1);
		}
	}
	//return the list of devices left to finish
	return $to_finish;
}


/**
 * The purpose of this function is to check weather or not a user exists.  This function
 * finds out if a user exists by searching for them in the user table in the database.
 *
 * $db_connection is in the form: 
 * 'host=host dbname=db_name user=username password=pass_word'
 * $user_number corresponds to the user number entered at the beginning of the test
 * $location corresponds to the location entered at the beginning of the test
 *
 * Andrew Catellier
 * updated Luke Connors 06/22/2012
 */
function user_check_exist($db_credentials, $user_number, $location) {
	$query = "SELECT * FROM users WHERE user_number = " . $user_number . " AND location = '" . $location . "';";
	$user_status = ask_pg($db_credentials,$query);
	$user_status_cols = pg_num_rows($user_status);
	return $user_status_cols;
}


/** 
 * The purpose of this function is to find the index for the current video that the user
 * should be  watching in the current sessions and location.  This is done by counting the 
 * number of votes the user has made in the current session and location.
 *
 * $db_connection is in the form: 
 * 'host=host dbname=db_name user=username password=pass_word'
 * $user_number corresponds to the user number entered at the beginning of the test
 * $session is a string indicating which session is currently being completed
 *
 * The return value is an integer representing the number of votes completed by the user
 * for the current session and the index of the video the user should currently view
 *
 * andrew catellier, 02/08/2011
 * updated Luke Connors 06/20/2012
 */
function user_seq_currval($db_credentials,$user_number,$session,$location)
{
	$query = "SELECT count(DISTINCT src) FROM votes WHERE user_number=" . $user_number . " AND location = '" . $location . "' AND ses= '" . $session . "';" ;
	$curr_val = pg_fetch_assoc(ask_pg($db_credentials,$query));
	return $curr_val['count'];
}


/** 
 * The purpose of this function is to find the index for the next video that the user
 * should be  watching in the current sessions and location.  This is done by counting the 
 * number of votes the user has made in the current session and location, and adding one.  
 *
 * $db_connection is in the form: 
 * 'host=host dbname=db_name user=username password=pass_word'
 * $user_number corresponds to the user number entered at the beginning of the test
 * $session is a string indicating which session is currently being completed
 *
 * The return value is an integer representing the index of the video the user should 
 * view next
 *
 * andrew catellier, 02/08/2011
 * updated Luke Connors 06/20/2012
 */
function user_seq_nextval($db_credentials, $user_number, $session, $location){
	$query = "SELECT count(DISTINCT src) FROM votes WHERE user_number=" . $user_number . " AND location = '" . $location . "' AND ses= '" . $session . "';" ;
	$next_val = pg_fetch_assoc(ask_pg($db_credentials,$query));
	$next_val = $next_val['count'] + 1;
	return $next_val;
}

/** 
 * This function will update the table "users", noting that a certain user
 * has finished the test on a device/location. This allows view_vote.php to check to see
 * whether a user/device/location combination has been completed, thus preventing 
 * corrupt data in the database.
 *
 * $db_connection is in the form: 
 * 'host=host dbname=db_name user=username password=pass_word'
 * $user_number corresponds to the user number entered at the beginning of the test
 * $session is a string indicating which session is currently being completed
 *
 * There is no return value, once the function is done, the user table is just updated
 *
 * andrew catellier, 02/10/2011
 * updated Luke Connors 06/20/2012
 */
function user_set_done($db_credentials, $user_number, $session, $location) 
{
	$query = "UPDATE users SET \"ses_" . $session . "\" = TRUE WHERE user_number = " . $user_number . " AND location = '" . $location . "';";
	ask_pg($db_credentials,$query);
}

/******************************** UNUSED FUNCTIONS **************************************/
/*
function user_del_sequences($db_credentials,$user_number){
// this function will drop all the sequences related to a given user. this
// will prevent the database from filling up with many useless sequences.
// this function could be generalized using the "\ds" command and some
// regex checking, but i haven't quite figured out how to make that query
// work in postgresql.
// $db_connection is in the form: 
// 'host=host dbname=db_name user=username password=pass_word'
// $user_number corresponds to the user number entered at the beginning of the test
//
// andrew catellier, 02/10/2011
// 
// this function probably won't be used in the test, because sequences and nextval
// turned out to be a problematic way to control the test.
	$query = "DROP SEQUENCE user_" . $user_number . "_toshiba; DROP SEQUENCE user_" . $user_number . "_sony; DROP SEQUENCE user_" . $user_number . "_ipodtouch; DROP SEQUENCE user_" . $user_number . "_iphone4; DROP SEQUENCE user_" . $user_number . "_ipad; DROP SEQUENCE user_" . $user_number . "_dell;";
	ask_pg($db_credentials,$query);
}
*/
?>
