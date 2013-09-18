<?      
	/**
	* deleteClassified.php: Again, despite the misleading name, this is the script that's called when
	* a user stops an auction in progress.  Called from button-clicks in marketplace.php, "Manage Auctions"
	* tab.  
	*
	* @param id (via GET) - the id # of the auction being cancelled.  Corresponds to primary key of auctions table.
	* @param u (via GET) - the user ID of the person cancelling the auction.  We pass it from marketplace.php
	*   so we can double-check it against the $uuid session variable, ensuring that only the actual owner of this
	*   auction can delete it. 
	*
	* Side note: the reason many of these scripts accept parameters via GET rather than POST is that jQuery's ajax()
	* function seems to pass data more reliably via GET in WebKit browsers.  No idea why. 
	*
	* @author William Shaw <william.shaw@duke.edu>
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
	* @version 0.1., 2/2013 
	*/

	if(session_id() == '') {
        	session_start();
	}

        $gameinstance = $_SESSION['gameinstance'];
        $uname = $_SESSION['uname'];
        $uuid = $_SESSION['uuid'];
	$adId = $_GET['id'];
	$user = $_GET['u'];
        ob_start( );               
		require 'db.php';
		require 'functions.php';        
	ob_end_clean( );

        logVisit( $uuid, basename( __FILE__ ) );

	// Check for funny business: $uuid (session variable) must match the user ID embedded in the form ($_GET variable)
	if ( $uuid != $user ) {
		exit( );
	}

	$workId = 0;

	// Figure out the work ID associated with this auction...
	$stmt = $dbh->prepare( "SELECT wid FROM auctions WHERE id = ? LIMIT 1" );
	$stmt->bindParam( 1, $adId );
	$stmt->execute( );

	while( $row = $stmt->fetch( ) ) {
		$workId = $row['wid'];
	}

	// Drop the auction
	$stmt = $dbh->prepare( "DELETE FROM auctions WHERE id = ?" );
	$stmt->bindParam( 1, $adId );
	$stmt->execute( );

	// Drop the scheduled events for ending the auction and notifying involved parties.
	$query = $dbh->prepare( "DROP EVENT IF EXISTS auctionEnd" . $adId );
       	$query->execute( );
       	$query = $dbh->prepare( "DROP EVENT IF EXISTS notifyWinner" . $adId );
       	$query->execute( );
       	$query = $dbh->prepare( "DROP EVENT IF EXISTS notifySeller" . $adId );
       	$query->execute( );

	
?>
