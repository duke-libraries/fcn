<?php

	/**
	* msgThread.php: Display a message thread between two users.  Called from within
	* mail.php to display conversations.
	*
	* @param uidf The target user -- i.e., display messages from this person.
	*
	* @author William Shaw <william.shaw@duke.edu>
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
	* @version 0.1., 1/2013
	*/

	if(session_id() == '') {
		session_start();
	}
        
        $uname = $_SESSION['uname'];
        $uuid = $_SESSION['uuid'];
	$targetUser = $_GET['uidf'];
        ob_start( );
		require 'db.php';
                require 'functions.php';
        ob_end_clean( );


?>
<?php
	echo( "<h2>Messages with " . getUsername( $targetUser ) . "</h2>" );

	// Get all messages from this person to uuid or from uuid to this person...
        $query = $dbh->prepare( "SELECT * FROM msgs WHERE (uidf = ? AND uidt = ?) OR ( uidf = ? AND uidt = ?) ORDER BY ts ASC" );
        $query->bindParam( 1, $targetUser ); 
	$query->bindParam( 2, $uuid );
	$query->bindParam( 3, $uuid );
	$query->bindParam( 4, $targetUser );
        $query->execute( );
        while ( $row = $query->fetch( ) ) {
		// .. and display them in rounded conversation bubbles with different background
		// colors, depending on who's speaking.  
		if ( $row['uidf'] != $uuid ) {
			echo "<div style=\"float:left;background:#ededed;width:65%;padding:8px;margin:8px;-moz-border-radius:10px;border-radius:10px;\">\n";
		} else {
			echo "<div style=\"float:right;background:#ffffff;width:65%;padding:8px;border:1px solid lightgray;margin:8px;-moz-border-radius:10px;border-radius:10px;\">\n";
		}
			// Call the getMessageContent() convenience function to show the text; add a timestamp.
			echo getMessageContent( $row['mid'] );
			echo "<p/><span style=\"font-size:smaller;\">".$row['ts']."</span>";
			echo "</div>\n";
        }
?>
