<?

	/**
	* tradeApproval.php: Input handler for accepting/rejecting trades.  Called by buttons on 
	* marketplace.php.
	* 
	* @author William Shaw <william.shaw@duke.edu>
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
	* @version 0.1, 8/2012
	*
	* @param tid (via POST) - trade id, corresponding to primary key of trades table
	* @param a (via POST) - approval action; 1 = accept, 0 = reject
	* @param originId (via POST) - user ID of the person who originated this trade
	* @param destination (via POST) - user ID of the trade recipient
	* @param tradeMessage (via POST) - (optional) comment explaining why a trade was rejected.
	* @param offered (via POST) - works offered by the initiator of this trade
	* @param traded_for (via POST) - works requested by the initiator of this trade
	*/

	if(session_id() == '') {
        	session_start();
	}

        $gameinstance = $_SESSION['gameinstance'];
        $uname = $_SESSION['uname'];
        $uuid = $_SESSION['uuid'];

	// Make sure we have the data we actually need to continue...
        if ( ( !isset( $_POST['tid'] ) ) || ( !isset( $_POST['a'] ) ) )
                exit();

       	$tid = $_POST['tid' ];
	$approvalAction = $_POST['a'];
	$origin = $_POST['originId'];
	$destination = $_POST['destinationId'];
	$tradeMessage = $_POST['tradeComment'];
	$workOfferedByOrigin = $_POST['offered'];
	$workRequestedFromDestination = $_POST['traded_for'];

        ob_start( );
                require 'functions.php';
		require 'db.php';
        ob_end_clean( );

        logVisit( $uuid, basename( __FILE__ ) );

?>
<?
	$approved = "0";

	// See if there's any cash involved (offered or requested); assign those values (default = 0)
	// to $offered and $requested, respectively.   
	$stmt = $dbh->prepare( "SELECT fcn_from_origin,fcn_from_destination FROM trades WHERE id =?" );
	$stmt->bindValue( 1, $tid );
	$stmt->execute( );

	$requested = 0;
	$offered = 0;

	while( $row = $stmt->fetch( ) ) {
		$requested = $row['fcn_from_destination'];
		$offered = $row['fcn_from_origin'];	
	}

	// TODO: ditch all these cryptic codes/magic numbers that have equivalent meanings
	if ( $approvalAction == "a" ) {	// 1 = accept trade
		$approved = "1"; 
	} elseif ( $approvalAction == "r" ) {  // 0 = reject trade
		$approved = "0";
	} elseif ( $approvalAction == "tC" ) {  // 2 = cancel trade (as originator); -1 = pending response
		$approved = "2";
	}

	if ( $approvalAction == "a" ) {
		// If there's money involved, make sure the responsible parties have enough...
		if ( $requested > getPoints( $uuid ) ) {
			// TODO: Secondary verification of trade $ here -- we do it on the marketplace module,
			// but need to double-check 
		}
	}

	// Record the acceptance/rejection in the trade table
	$stmt = $dbh->prepare( "UPDATE trades SET accepted = ? WHERE id = ?" ); 
	$stmt->bindValue( 1, $approved );
	$stmt->bindValue( 2, $tid );
	$stmt->execute( );


	if ( $approved == "1" )
	{
		// The user approved the trade, so move works into/out of his collection.
		$originTable = $origin . "_" . $gameinstance . "_coll"; 
		$destTable = $destination . "_" . $gameinstance . "_coll"; 

		$worksOffered = explode( ' ', trim($workOfferedByOrigin) );
		$worksRequested = explode( ' ', trim($workRequestedFromDestination) );

		foreach( $worksOffered as $workOffered ) {
			// Remove offered work from origin's table
			$stmt = $dbh->prepare( "DELETE FROM " . $originTable . " WHERE work = ?" );
			$stmt->bindValue( 1, $workOffered );
			$stmt->execute( );

			// Add offered work to destination's table
			$stmt = $dbh->prepare( "INSERT INTO " . $destTable . " VALUES( ? )" );
			$stmt->bindValue( 1, $workOffered );
			$stmt->execute( );

			// Cancel any other trades or auctions involving this work.
			clearWorkFromOtherTransactions( $workOffered );
		}

		foreach( $worksRequested as $workRequested ) {
			// Remove requested work from destination's table
			$stmt = $dbh->prepare( "DELETE FROM " . $destTable . " WHERE work = ?" );
			$stmt->bindValue( 1, $workRequested );
			$stmt->execute( );

			// Add requested work to origin's table.
			$stmt = $dbh->prepare( "INSERT INTO " . $originTable . " VALUES( ? )" );
			$stmt->bindValue( 1, $workRequested );
			$stmt->execute( );
		
			// Cancel any other trades or auctions involving this work.  	
			clearWorkFromOtherTransactions( $workRequested );
		}

		// Create a global announcement that this trade has been accepted (or rejected)
		$headline = getUsername( $destination ) . " accepted " . getUsername( $origin ) . "'s trade proposal";
		$stmt = $dbh->prepare( "UPDATE events SET type = ?, headline = ? WHERE xref = ? AND type = ?" );
		$stmt->bindValue( 1, $E_TRADE_ACCEPTED );
		$stmt->bindValue( 2, $headline );
		$stmt->bindValue( 3, $tid );
		$stmt->bindValue( 4, $E_TRADE_PROPOSED );
		$stmt->execute( );

		createNotification( $origin, $E_TRADE_ACCEPTED, getUsername( $destination ) . " accepted your trade proposal." );

		// If there was a message associated with the rejection, notify the recipient.
		if ( $tradeMessage != "" ) {
		        $substmt = $dbh->prepare( "INSERT INTO msgs(uidf,uidt,gid,string,rr) VALUES( ?, ?, ?, ?, ? )" );
        		$substmt->bindParam( 1, $uuid );
        		$substmt->bindParam( 2, $origin );
        		$substmt->bindParam( 3, $gameinstance );
        		$substmt->bindParam( 4, $tradeMessage );
        		$substmt->bindValue( 5, 0 );
        		$substmt->execute( );

        		$mailNotification = "<a href=\"" . $FCN_ROOT . "mail.php\">" . getUsername( $uuid ) . " sent you a message about your recent trade.</a>";
        		createNotification( $origin, $E_MESSAGE_RECEIVED, $mailNotification );	
		}

		// If money was included, adjust points accordingly...
		if ( $offered > 0 ) {
			adjustPoints( $origin, -$offered );
			adjustPoints( $uuid, $offered );
		} 
		if ( $requested > 0 ) {
			adjustPoints( $origin, $requested );
			adjustPoints( $uuid, -$requested );
		}

	} 

	// FIXME this is nuts -- verbatim repetition of above
	if ( $approved == "0" ) {
                $headline = getUsername( $destination ) . " rejected " . getUsername( $origin ) . "'s trade proposal";
                $stmt = $dbh->prepare( "UPDATE events SET type = ?, headline = ? WHERE xref = ? AND type = ?" );
                $stmt->bindValue( 1, $E_TRADE_REJECTED );
                $stmt->bindValue( 2, $headline );
                $stmt->bindValue( 3, $tid );
                $stmt->bindValue( 4, $E_TRADE_PROPOSED );
                $stmt->execute( );
		createNotification( $origin, $E_TRADE_REJECTED, getUsername( $destination ) . " rejected your trade proposal." );

                if ( $tradeMessage != "" ) {
                        $substmt = $dbh->prepare( "INSERT INTO msgs(uidf,uidt,gid,string,rr) VALUES( ?, ?, ?, ?, ? )" );
                        $substmt->bindParam( 1, $uuid );
                        $substmt->bindParam( 2, $origin );
                        $substmt->bindParam( 3, $gameinstance );
                        $substmt->bindParam( 4, $tradeMessage );
                        $substmt->bindParam( 5, $false );
                        $substmt->execute( );

                        $mailNotification = "<a href=\"" . $FCN_ROOT . "mail.php\">" . getUsername( $uuid ) . " sent you a message about your trade proposal.</a>";
                        createNotification( $origin, $E_MESSAGE_RECEIVED, $mailNotification );
                }

	}

	// Value 2 = trade was cancelled.
	if ( $approved == "2" ) {
		createNotification( $origin, $E_TRADE_REJECTED, "You have cancelled the trade with " . getUsername( $destination ) . "." );
	}

?> 
<html> </html>
