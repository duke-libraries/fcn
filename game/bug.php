<?php

	/** 
	* bug.php: Form allowing users to submit bug reports and feature requests from anywhere in the game.
	*  Input to this form is handled by bugProcessor.php.  
	*
	* @author William Shaw <william.shaw@duke.edu>
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
	* @version 0.1, 10/2012 
	*/

	if(session_id() == '') {
        	session_start();
	}

        $uname = $_SESSION['uname'];
        $uuid = $_SESSION['uuid'];

        ob_start( );
		require 'db.php';
                require 'functions.php';
        ob_end_clean( );

        logVisit( $uuid, basename( __FILE__ ) );

?>
<html>
<head>
<script type="text/javascript" src="https://www.google.com/jsapi"></script><script type="text/javascript">
        google.load( "jquery", "1" );
        google.load( "jqueryui", "1" );
</script>
<link rel="stylesheet" type="text/css" href="resources/fcn.css"/>
<link rel="stylesheet" type="text/css" href="resources/jquery-ui.css"/>

<script>
	// Set up the submission form as a jQuery UI button
	$(document).ready( function( ) {
		$( "button#submit" ).button( );
		$( "button#submit" ).click( function( ) {
			$("#bugForm").submit( );
			return false;  // Prevent reload on WebKit
		} );	
	} ); 
</script>
</head>
<body style="background-color:#fff">
<form id="bugForm" action="bugProcessor.php" method="post">
<h2>Bug Report Form</h2>
Thanks for sharing your feedback about Fantasy Collecting.  Please enter your bug report below and press "submit" to send it
to game administrators, or press Escape to cancel.   
<p/>
	<!-- Very simple form - one textarea. --> 
	<textarea id="report" name="report" style="width:600;height:240;"> </textarea>
<p/>
<div style="float:right;clear:both;"><button id="submit">Submit</button></div>
</div>
</form>
</body>
</html>
