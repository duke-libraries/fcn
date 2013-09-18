<?php
	/**
	* promote.php: Helper that allows game admin to promote individual users to Connoisseur status.
	*/

        ob_start( );                
		require '../functions.php';        
		require '../db.php';
	ob_end_clean( );

	$player = $_GET['collector'];
	$message = $_GET['desc'];
	$points = $_GET['points'];

	createNotification( $player, $E_ACHIEVEMENT, $message );

	$retVal = "";

	// FIXME: hard coded levels/magic #	
	if ( isConnoisseur( $player ) ) {
		$retVal = "The player has been demoted.";
		setLevel( $player, 1 );
	} else {
		$retVal = "Promotion complete!";
		setLevel( $player, 10 );
        	$newsFeedMsg = "<div style=\"display:inline;padding-left:50px;float:left;padding-right:5px;padding-top:5px;padding-bottom:5px;\">" . getUserName( $player ) . " has earned the Connoisseur badge!  As a reward for excellent gameplay, " . getUserName( $player ) . " can now earn extra " . $CURRENCY_SYMBOL . " by validating other players' tombstone entries.</div>";
        	$headline = getUserName( $player ) . " is now a Connoisseur!";
        
        	$query = $dbh->prepare( "INSERT INTO events( type, target, description, headline ) VALUES( ?, ?, ?, ? )" );
		$query->bindParam( 1, $E_ACHIEVEMENT );
		$query->bindParam( 2, $player );
		$query->bindParam( 3, $newsFeedMsg );
		$query->bindParam( 4, $headline );
		$query->execute( );
	}


	echo( $retVal );


?>

