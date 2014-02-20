// This document provides javascript to support the html buttons in db_tasks.php.  The 
// javascript on this page allows the options to make sessions and videos random or 
// ordered only when  "create filenames, sessions, and users tables" task is selected.
//
// Luke Connors 08/02/2012

// store the DOM in a variable
var d = document;
// when the page is loaded
onload = function() {
    // store the form elements in a variable
 	var formEl = document.getElementById('tasks');
    var randEl = document.getElementById('random');
    randEl.style.visibility = "Hidden";
    formEl.onchange=function(){
    	var selectEL = document.getElementById('task_sel');
		var selection = selectEL.options[selectEL.selectedIndex].text;
		if (selection == "Configure Sessions, Questions, and Answers") {
			randEl.style.visibility = "Visible";
		}
		else {
		    randEl.style.visibility = "Hidden";
		}
    }
}
