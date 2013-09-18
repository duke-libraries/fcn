<?      
	/**
	* bugProcessor.php: Form handler for bug.php.  Simply insert bug report/suggestion into the
	* bugs table.
	*
	* @param report (via POST): The text of the bug report or suggestion. 
	*
	* @author William Shaw <william.shaw@duke.edu>
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
	* @version 0.1, 8/2012
	*/

	if(session_id() == '') {
        	session_start();
	}

        $uuid = $_SESSION['uuid'];
	$message = strip_tags( $_POST['report'] );

        ob_start( );
		require 'db.php';
		require 'functions.php';        
	ob_end_clean( );

        logVisit( $uuid, basename( __FILE__ ) );
?>
<html>  
<head>  
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">
        google.load( "jquery", "1" );
        google.load( "jqueryui", "1" );
</script>
<link rel="stylesheet" href="resources/fcn.css" type="text/css"/>
<link rel="stylesheet" type="text/css" href="resources/jquery-ui.css"/>
<script type="text/javascript">
	$(document).ready( function( ) {
		// The bug report form is opened in a Shadowbox; the dismissal button here
		// calls the close() function to clear the overlay
		$( "#dismiss" ).button( );
		$( "#dismiss" ).click( function( ) {
			window.parent.Shadowbox.close( );	
		} );
	} );
</script>
</head>
<!-- Have to set the background style in overlays created by Shadowbox.js, even if you include
     CSS that sets background color; not sure why, but it seems to be the case in Firefox and
     WebKit. -->
<body style="background-color:#fff">
<?php
	/** 
	* Dump the data into bug_reports.  No need to wrangle $message, since bindParam() 
	* handles protection against SQL injection etc.    
	*/	
	$stmt = $dbh->prepare( "INSERT INTO bug_reports(uid,text) VALUES( ?, ? )" );
	$stmt->bindParam( 1, $uuid );
	$stmt->bindParam( 2, $message );
	$stmt->execute( );
?>
<h2>Bug Reported</h2>
Your bug report has been filed.  Thank you!
<p/>
<button id="dismiss">Okay</button>
</body>
</html>
