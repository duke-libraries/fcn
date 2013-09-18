<?php

	/**
	* mailCompose.php: Form for composing messages in the in-game mail system.  Handled by 
	* mailProcessor.php.  Called by the new message button in mail.php; replies are handled directly
	* by mailProcesor.php and submitted via a textarea on mail.php.  In other words, this form is
	* used only for starting new threads.  
	*
	* @author William Shaw <william.shaw@duke.edu>
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
	* @version 0.1, 8/2012
	*/ 

	if(session_id() == '') {
        	session_start();
	}

        $gameinstance = $_SESSION['gameinstance'];;
        $uname = $_SESSION['uname'];
        $uuid = $_SESSION['uuid'];

        ob_start( );
                require 'functions.php';
		require 'db.php';
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
	$(document).ready( function( ) {
		// Set up autocomplete functionality for the address field (we populate the users
		// array below).
		$( "#address" ).autocomplete({
			source: users, delay: 0 
		});

		// Set up the send button.  
		$( "button#send" ).button( );
		$( "button#send" ).click( function( ) {
			if ( jQuery.inArray( $("#address").val( ), users ) < 0 )
			{
				// Back out if the address is invalid
				alert( "Recipient '" + $("#address").val( ) + "' doesn't exist." );
				return false;
			} 
			else 
			{
				$("#mailForm").submit( );
			}
			return false;
		} );	
	} ); 

	var users = [
<?php
	// Generate the array of usernames.
        $points = $dbh->prepare( "SELECT id,name FROM collectors WHERE id > -1" );
        $points->execute( );
        
        while( $pval = $points->fetch( ) )
        {
                echo( "\"" . $pval['name'] . "\", " );
        }
?>
		""
	];
</script>
</head>
<body style="background-color:#fff">
<form id="mailForm" action="mailProcessor.php" method="post">
<div class="ui-widget">
	<label for="address">To: </label><br/>
	<input id="address" name="address" <?php if ( $isReply === "1" ) { echo " value=\"" . getUsername( $recip ) . "\"";  } ?> />
<p/>
	<label for="string">Message: </label><br/>
	<textarea id="string" name="string" style="width:600;height:240;">Type your message here...</textarea>
<p/>
<div style="float:right;clear:both;"><button id="send">Send</button></div>
</div>
</form>
</body>
</html>
