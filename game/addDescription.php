<?php
        /**             
        * addDescription.php: form allowing players to add a description to works in their
	* collection.  The input given here is processed by descriptionProcessor.php.  
        *       
	* @param w (via GET): the id of the work we're editing.  Corresponds with primary key of works table.
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

	// Turn out output buffering so the script doesn't try to send any output before we load
	// the database headers and functions...
        ob_start( );
		require 'db.php';
                require 'functions.php';
        ob_end_clean( );

	// Record the user's visit to this page 
        logVisit( $uuid, basename( __FILE__ ) );
?>
<html>
<head>
<!-- Use Google JSAPI to load jQuery / jQuery UI current versions. -->
<script type="text/javascript" src="https://www.google.com/jsapi"></script><script type="text/javascript">
        google.load( "jquery", "1" );
        google.load( "jqueryui", "1" );
</script>
<link rel="stylesheet" type="text/css" href="resources/fcn.css"/>
<link rel="stylesheet" type="text/css" href="resources/jquery-ui.css"/>

<script>
	// jQuery ready function: set up the form submission button. 
	$(document).ready( function( ) {
		$( "button#submit" ).button( );
		$( "button#submit" ).click( function( ) {
			$("#descForm").submit( );
			return false;	// Prevents page from reloading in WebKit
		} );	
	} ); 
</script>
</head>
<body style="background-color:#fff">
<form id="descForm" action="descriptionProcessor.php" method="post">
<h2>Edit Description</h2>
Here, you can add some information about this work.  
<p/>
<!-- Display the work image.  If it already has a tombstone (artist name/birth-death dates), show that as well. -->
<img src="img.php?img=<?php echo $workId;?>" style="width:300;margin-left:auto;margin-right:auto;display:block;"/>
<center>
<?php
	if ( workHasTombstone( $workId ) ) { echo getTombstone( $workId ); }
?>
</center>
<!-- Work ID is passed as a hidden field.  For the "mode" field, we must be updating the description if the work
 	already has one, so set the value accordingly. -->
<input type="hidden" name="work" value="<?php echo $workId;?>"/>
<input type="hidden" name="mode" value="<?php echo ( workHasDescription( $workId ) ? "update" : "new" ); ?>"/>
<p/>
	<!-- textarea for the description.  Contains the existing description if there is one. -->
        <textarea id="desc" style="width:600;height:200;" name="desc"><?php if ( workHasDescription( $workId ) ) {echo getDescription( $workId );}
	?>
	</textarea>
<p/>
<div style="float:right;clear:both;"><button id="submit">Update</button></div>
</div>
</form>
</body>
</html>
