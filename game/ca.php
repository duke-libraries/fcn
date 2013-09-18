<?php
	/**
	* ca.php: The cryptic name is short for 'challenge approver.'  This script is called from home.php;
	*  it's available to players who have obtained coinisseur status and allows them to validate other
	*  players' tombstone submissions.  They earn 10 FCGs for doing so.
	*
	* @param tombstoneId (via GET) - the ID of the tombstone (corresponds to primary key of tombstones table)
	* @param uuid (via GET) - the user ID of the player who's submitting this approval form.
	* @param action (via GET) - whether approved or rejected.  1 = accepted; 0 = rejected 
	* @param player (via GET) - the player who submitted the tombstone currently being approved (or rejected)
	* @param work (via GET) - the work id attached to this tombstone.  Corresponds to the primary key of
	*   of the works table. 
	*
	* @author William Shaw <william.shaw@duke.edu>
        * @author Katherine Jentleson <katherine.jentleson@duke.edu>, designer
	* @version 0.1, 10/2012
	*
	*/
        ob_start( );
		require 'db.php';
		require 'functions.php';        
	ob_end_clean( );

	$challengeId = $_GET['tombstoneId'];	
	$approver = $_GET['uuid'];
	$action = $_GET['action'];
	$player = $_GET['player'];		
	$work = $_GET['work'];			
?>
<?php
		// APPROVED column in these tables: 0 = rejected; 1 = accepted; 2 = pending.  
		// These values really need to be global variables.  Magic numbers abound.  FIXME
		$approvalAction = ( $action === "approve" ? 1 : 0 );

		$stmt = $dbh->prepare( "UPDATE tombstones SET approved = ? WHERE id = ?" );
		$stmt->bindParam( 1, $approvalAction );
		$stmt->bindParam( 2, $challengeId );
		$stmt->execute( );

		if ( $action === "approve" ) {
			// Award the player who submitted the tombstone 10 FCGs and notify them.
			adjustPoints( $player, 10 );
			createNotification( $player, $E_ACHIEVEMENT, "Your tombstone for " . getTombstone( $work, true ) . " was approved by " . getUserName( $approver ) . "!  You receive " . $CURRENCY_SYMBOL . "10." );
		} else {
			// ...or tell them they got it wrong.
			createNotification( $player, $E_ACHIEVEMENT, "Your tombstone for " . getTombstone( $work, true ) . " was rejected by " . getUserName( $approver ) . "!  You can create a new tombstone to try again." );

			// Delete the tombstone attempt.  There's a mismatch here between the way we use
			// approval flags (0/1) and the fact that we just drop rejected attempts from the
			// table altogether, but fixing the problem will require rewriting some utility
			// functions in functions.php.
			$stmt = $dbh->prepare( "DELETE FROM tombstones WHERE id = ?" );
			$stmt->bindParam( 1, $challengeId );
			$stmt->execute( );
		}
		// Finally, award the approver 10 points.
		adjustPoints( $approver, 10 );
?>

