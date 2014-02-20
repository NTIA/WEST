// this document listens for changes
// to the form and modifies the displayed, simulated form elements appropriately.
//
//
// andrew catellier 06/20/2011

// when the page is loaded
onload = function() {
	// store the form elements in a variable
	var formEl = document.getElementById('divmosform');
	var buttonEl = document.getElementById('to_vote');
	var qestionEl = document.getElementById('divquestion');
	var textEl = document.getElementsByClassName('radios');
	// the question that is displaid before the video must be visible
	qestionEl.style.visibility = "Visible";
	// hide the form elements.
	formEl.style.visibility = "Hidden";
	buttonEl.style.visibility = "Hidden";
	for(var i = 0; i < textEl.length; i++) {
		textEl[i].style.visibility = "Hidden";
	}
	// if we are only collecting votes, don't try to glom onto the video element
	var tally_or_not = document.cookie.match('(^|;) ?tally=FALSE([^;]*)(;|$)');
	if (tally_or_not){
		// store the video element in a variable
		var videoEl = document.getElementById('video');
		// listen for when the video has finished playing
		videoEl.addEventListener('ended',function(){
			vidOver();}
		,false);
		// this bit was added because Chrome at some point stopped responding to the
		// video 'ended' event.
		videoEl.addEventListener('timeupdate',function(){
			var videoEl = document.getElementById('video');
			if (this.duration - this.currentTime < 0.2) {
				vidOver();}
			},false);
		// listen for when the video starts playing
		videoEl.addEventListener('play',function(){
			// when it does, force "player mode" on iOS devices (not certain this does anything)
			//var videoEl = document.getElementById('video');
			//videoEl.webkitEnterFullscreen();
			// and also hide the form elements to prevent voting (for iPad and larger screens)
			var formEl = document.getElementById('divmosform');
			formEl.style.visibility = "Hidden";}
		,false);
	} else {
		//formEl.style.visibility = "Hidden";
		goToVid();
	}
	// prevent scrolling on iOS devices
	document.addEventListener('touchmove',handleTouchMove,false);
	// do some form validation to make sure that all votes have been cast
	document.getElementById('subbut').onsubmit = function () {
		
	};
};

// when a button is pressed,
onkeypress = function(evt){
	// the || is probably a browser compatibility hack
	evt = evt || window.event;
	// figure out which key was pressed
	var charcode = evt.keyCode || evt.which;
	// if the enter button was pressed, 
	/*if (charcode === 13){
		frm = document.getElementById("mosform");
		// submit the form
		frm.submit();
	}*/
};

function handleTouchMove(event){
	event.preventDefault();
	return false;
}

function goToVote() {
	var buttonEL = document.getElementById('to_vote');
	buttonEL.style.visibility ="Hidden";
	var formEl = document.getElementById('divmosform');
  	formEl.style.visibility = "Visible";
	var textEl = document.getElementsByClassName('radios');
  	for(var i = 0; i < textEl.length; i++) {
		textEl[i].style.visibility = "Visible";
	}
  	var button2EL = document.getElementById('subbut');
  	buttonEL.style.visibility ="Visible";
	// reallow scrolling on iOS devices
	document.removeEventListener('touchmove',handleTouchMove);
};
function goToVid() {
	var buttonEL = document.getElementById('to_vid');
	buttonEL.style.visibility ="Hidden";
	var formEl = document.getElementById('divquestion');
  	formEl.style.visibility = "Hidden";
	var videoEl = document.getElementById('video');
	if (videoEl) {
  		videoEl.play();
	}
	else {
		setTimeout("goToVote();",10000);
	}
};
function vidOver() {
	// figure out if you're on a tiny iOS device
	var ua = navigator.userAgent;
	var checker = {
		iPhoneOriPod: ua.match(/(iPhone|iPod)/),
		blackberry: ua.match(/BlackBerry/),
		android: ua.match(/Android/)
	};
	// when the video is over, exit out of "player mode" on iOS devices
	var videoEl = document.getElementById('video');
	if (checker.iPhoneOriPod) {
		videoEl.webkitExitFullscreen();
	} 	
	videoEl.style.visibility = "Hidden";
	var tovidEl = document.getElementById('divquestion');
	tovidEl.style.visibility = "Hidden";
   	// and display the form elements to allow voting
 	var formEl = document.getElementById('divmosform');
  	formEl.style.visibility = "Visible";
  	var textEl = document.getElementsByClassName('radios');
  	for(var i = 0; i < textEl.length; i++) {
		textEl[i].style.visibility = "Visible";
	}
  	var button2EL = document.getElementById('subbut');
  	button2EL.style.visibility ="Visible";
	// reallow scrolling on iOS devices
	document.removeEventListener('touchmove',handleTouchMove);
};
