<?php
	/**
	* ca.php: Award players for entering tombstones or descriptions.  Called from a form on index.php.
	*
	* @param mode (via GET): Whether we're approving/rejecting a tombstone (ts) or description (d).
	* @param tombstoneId (via GET): The id of this tombstone (or description) -- primary key of the 
	* 	appropriate table.
	* @param action (Via GET): 'approve' or 'reject'	
	* @param player (via GET): player id we're awarding (or telling to try again)
	* @param work (via GET): work id, primary key of works table.
	*/
        ob_start( );                
		require '../functions.php';        
		require '../db.php';
	ob_end_clean( );

	$challengeId = $_GET['tombstoneId'];	// same parameter name even for descriptions...
	$mode = $_GET['mode'];			// ts for tombstones, d for descriptions
	$action = $_GET['action'];		// 'approve' or 'reject'
	$player = $_GET['player'];		// The player we're awarding for getting it right (we hope)
	$work = $_GET['work'];			// The id of the work in question
?>
<?php
	if ( $mode === "ts" )
	{
		// APPROVED column in these tables: 0 = rejected; 1 = accepted; 2 = pending
		// Todo define the constants in functions.php
		$approvalAction = ( $action === "approve" ? 1 : 0 );
		$stmt = $dbh->prepare( "UPDATE tombstones SET approved = ? WHERE id = ?" );
		$stmt->bindParam( 1, $approvalAction );
		$stmt->bindParam( 2, $challengeId );
		$stmt->execute( );
		if ( $action === "approve" ) {
			adjustPoints( $player, 10 );
			createNotification( $player, $E_ACHIEVEMENT, "Your tombstone for " . getTombstone( $work, true ) . " was approved by the game administrator!  You receive " . $CURRENCY_SYMBOL . "10." );
		} else {
			createNotification( $player, $E_ACHIEVEMENT, "Your tombstone for " . getTombstone( $work, true ) . " was rejected by the game administrator!  You can create a new tombstone to try again." );
			$stmt = $dbh->prepare( "DELETE FROM tombstones WHERE id = ?" );
			$stmt->bindParam( 1, $challengeId );
			$stmt->execute( );
		}
	} else if ( $mode === "d" )
	{
                $approvalAction = ( $action === "approve" ? 1 : 0 );
                $stmt = $dbh->prepare( "UPDATE work_descriptions SET approved = ? WHERE id = ?" );
                $stmt->bindParam( 1, $approvalAction );
                $stmt->bindParam( 2, $challengeId );
                $stmt->execute( );
                if ( $action === "approve" ) {
                        adjustPoints( $player, 10 );
                        createNotification( $player, $E_ACHIEVEMENT, "Your description for " . ( workHasTombstone( $work ) ? getTombstone( $work, true ) : "a work" ) . " was approved!  You receive " . $CURRENCY_SYMBOL . "10." );
                } else {
                        createNotification( $player, $E_ACHIEVEMENT, "Your description for " . ( workHasTombstone( $work ) ? getTombstone( $work, true ) : "a work" ) . " was rejected!  You can modify the description and try again." );
                }
	}	

?>

