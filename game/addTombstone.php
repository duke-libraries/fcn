<?php
	/**
	* addTombstone.php: form allowing players to add a tombstone (Artist name and lifespan) to
	* works in their collection.  The input given here is processed by tombstoneProcessor.php.  
	*
	* @param w (via GET): work ID we're editing.  Corresponds with primary key of works table.  
	*
	* @author William Shaw <william.shaw@duke.edu>
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
	* @version 0.1, 8/2012
	*/

	if(session_id() == '') {
        	session_start();
	}
        
	$gameinstance = $_SESSION['gameinstance'];
        $uname = $_SESSION['uname'];
        $uuid = $_SESSION['uuid'];
	$workId = $_GET['w'];

	// Import database header and functions...
        ob_start( );
		require 'db.php';
                require 'functions.php';
        ob_end_clean( );

	// Log player's visit to this page
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
	// Set up the form submission button
	$(document).ready( function( ) {
		$( "button#submit" ).button( );
		$( "button#submit" ).click( function( ) {
			$("#tombstoneForm").submit( );
			return false; // prevent reload on WebKit
		} );	
	} ); 
</script>
</head>
<body style="background-color:#fff">
<form id="tombstoneForm" action="tombstoneProcessor.php" method="post">
<h2>Tombstone Information Form</h2>
<img src="img.php?img=<?php echo $workId;?>" style="width:300;margin-left:auto;margin-right:auto;display:block;"/>
<input type="hidden" name="work" value="<?php echo $workId;?>"/>
<p/>
	<label for="artist">Artist Name:</label><br/>
	<input id="artist" name="artist"/>
	<p/>
	<label for="born">Born:</label><br/>
	<input id="born" name="born"/>	
	<p/>
        <label for="died">Died:</label> <br/>
        <input id="died" name="died"/> 
	<p/>
	<label for="wt">Work Title:</label> <br/>
        <input id="wt" name="wt" size="80"/> 
	<p/>
        <label for="wd">Work Date:</label> <br/>
        <input id="wd" name="wd"/> 
<p/>
<div style="float:right;clear:both;"><button id="submit">Submit</button></div>
</div>
</form>
</body>
</html>
