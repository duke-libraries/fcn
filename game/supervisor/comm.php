<?php
	/**
	* comm.php: Utility script that posts a general announcement to all collectors.
	*
	* @param msg (Via POST): The message for all collectors.
	*/

        ob_start( );
                require '../functions.php';
		require '../db.php';
        ob_end_clean( );

	$message = "<div style=\"display:inline;padding-left:50px;float:left;padding-right:5px;padding-top:5px;padding-bottom:5px;\">" . $_POST['msg'] . "</div>";
	$headline = "Announcement from Game Administrator";
	
	$query = $dbh->prepare( "INSERT INTO events( type, target, description, headline ) VALUES( ?, ?, ?, ? )" );
	$query->bindParam( 1, $E_MESSAGE_RECEIVED );
	$query->bindValue( 2, -1 );
	$query->bindParam( 3, $message );
	$query->bindParam( 4, $headline );

	$query->execute( );

	echo( "<h2>Message Sent</h2>" );
	echo( "You have sent this message to all collectors: <p/> " );

	echo $message;

	echo "<p/>";
	echo( "<button onclick=\"javascript:history.go(-1);\">Okay</button>" );

?>
