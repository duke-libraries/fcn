<?php
	/**
	* love.php: Input processor for clicks on the love div in userCollection.php.  Simply updates
	* the likes table to reflect user's current opinion of a work.
	*
	* @author William Shaw <william.shaw@duke.edu>
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
	* @version 0.1, 1/2013
	* 
	* @param wid (via GET) - The work id.
	*/

        if(session_id() == '') {
                session_start();
        }
	$work = $_GET['wid'];
        $uname = $_SESSION['uname'];
        $uuid = $_SESSION['uuid'];
        ob_start( );                
		require 'db.php';
		require 'functions.php';        
	ob_end_clean( );
        logVisit( $uuid, basename( __FILE__ ) );

?>
<?php
	// See what player's current sentiment is and reverse it 
        $query = $dbh->prepare( "SELECT uid,wid FROM likes WHERE (uid = ?) AND (wid = ?)" );
        $query->bindParam( 1, $uuid );
        $query->bindParam( 2, $work );
        $query->execute( );

        if ( $query->rowCount() == 0 ) {
		// + love because player doesn't currently love this work.
		$update = $dbh->prepare( "INSERT INTO likes(wid, uid) VALUES(?,?)" );
		$update->bindParam( 1, $work );
		$update->bindParam( 2, $uuid );	
		$update->execute( );
	} else {
		// - love because they've had a change of heart about something...
		$update = $dbh->prepare( "DELETE FROM likes WHERE (uid = ?) AND (wid = ?)" );
		$update->bindParam( 1, $uuid );
		$update->bindParam( 2, $work );
		$update->execute( );
	}
?>
